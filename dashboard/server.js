// server version02
import express from "express";
import cors from "cors";
import mqtt from "mqtt";
import { InfluxDB, Point } from "@influxdata/influxdb-client";

const app = express();
app.use(cors({ origin: "*" }));

// --- Config (ตรวจสอบค่าเหล่านี้ใน Environment Variables ของ Render) ---
const PORT = process.env.PORT || 5000;
const INFLUX_URL = process.env.INFLUX_URL;
const INFLUX_TOKEN = process.env.INFLUX_TOKEN;
const INFLUX_ORG = process.env.INFLUX_ORG;
const INFLUX_BUCKET = process.env.INFLUX_BUCKET;

const influx = new InfluxDB({ url: INFLUX_URL, token: INFLUX_TOKEN });
const queryApi = influx.getQueryApi(INFLUX_ORG);
const writeApi = influx.getWriteApi(INFLUX_ORG, INFLUX_BUCKET);

// --- MQTT Connection ---
const mqttClient = mqtt.connect(process.env.MQTT_URL || "mqtt://broker.freemqtt.com", {
    username: process.env.MQTT_USER || "freemqtt",
    password: process.env.MQTT_PASS || "public"
});

mqttClient.on("connect", () => {
    console.log(" MQTT Connected & Monitoring Started");
    mqttClient.subscribe("test/sensor/data"); 
});

// --- [ฟังก์ชัน LED และการบันทึกข้อมูลที่หายไป] ---
mqttClient.on("message", (topic, message) => {
    try {
        const payload = JSON.parse(message.toString());
        const mac = payload.mac || "unknown";
        
        // 1. ดึงค่าจาก Payload
        const temp = payload.temperature || 0;
        const vib = payload.accel_percent || 0;
        const pzem = payload.pzem || {};
        const volt = pzem.voltage || 0;
        const cur = pzem.current || 0;
        const power = pzem.power || 0;

        // 2. บันทึกลง InfluxDB
        const points = [];
        if (typeof payload.temperature === 'number') {
            points.push(new Point("DS18B20").tag("device", mac).floatField("temperature", temp));
        }
        if (typeof payload.accel_percent === 'number') {
            points.push(new Point("MPU6050").tag("device", mac).floatField("accel_percent", vib));
        }
        if (Object.keys(pzem).length > 0) {
            const p = new Point("PZEM004T").tag("device", mac);
            if (volt) p.floatField("voltage", volt);
            if (cur) p.floatField("current", cur);
            if (power) p.floatField("power", power);
            points.push(p);
        }

        if (points.length > 0) {
            writeApi.writePoints(points);
            console.log(` Recorded ${points.length} points for ${mac}`);
        }

        // 3. Logic ควบคุมไฟ LED (Alert System)
        let danger = (temp >= 35 || vib >= 15 || cur >= 8 || volt >= 300 || power >= 20);
        let warning = (temp >= 34 || vib >= 5 || cur >= 5 || volt >= 250 || power >= 15);

        let ledStates = {
            green:  { pin: 33, value: (!danger && !warning) ? 1 : 0 },
            yellow: { pin: 32, value: (warning && !danger) ? 1 : 0 },
            red:    { pin: 19, value: (danger) ? 1 : 0 }
        };

        // ส่งคำสั่งกลับไปยัง ESP32
        Object.values(ledStates).forEach(led => {
            mqttClient.publish("test/cmd/led", JSON.stringify({ pin: led.pin, value: led.value }));
        });

    } catch (err) {
        console.error("Process Error:", err);
    }
});
app.get("/api/latest/:mac", async (req, res) => {
  const { mac } = req.params; // รับค่าจาก URL เช่น /api/latest/aa:bb:cc...
  try {
    const fluxQuery = `
      from(bucket: "${INFLUX_BUCKET}")
        |> range(start: -10m)
        |> filter(fn: (r) => r["device"] == "${mac.toLowerCase()}")
        |> filter(fn: (r) => r["_measurement"] == "DS18B20" or r["_measurement"] == "MPU6050" or r["_measurement"] == "PZEM004T")
        |> filter(fn: (r) => r["_field"] == "temperature" or r["_field"] == "accel_percent" or r["_field"] == "voltage" or r["_field"] == "current" or r["_field"] == "power")
        |> last() 
    `;

    let result = {
      temperature: 0,
      vibration: 0,
      voltage: 0,
      current: 0,
      power: 0,
      energy: 0,
      frequency: 0,
      power_factor: 0,
      accel_percent: 0
    };

    await queryApi.queryRows(fluxQuery, {
      next: (row, tableMeta) => {
        const o = tableMeta.toObject(row);
        switch (o._field) {
          case "temperature": result.temperature = o._value; break;
          case "accel_percent": result.vibration = o._value; break;
          case "voltage": result.voltage = o._value; break;
          case "current": result.current = o._value; break;
          case "power": result.power = o._value; break;
          case "energy": result.energy = o._value; break;
          case "frequency": result.frequency = o._value; break;
          case "power_factor": result.power_factor = o._value; break;
        }
      },
      complete: () => res.json(result),
      error: (err) => res.status(500).send(err)
    });
  } catch (err) {
    res.status(500).json({ error: err.message });
  }
});
//----------------------------
// =============================
//  API ดึงข้อมูลย้อนหลัง (ใส่ตรงนี้)
// =============================
app.get("/api/history", async (req, res) => {
  const range = req.query.range || "1h";

  const fluxQuery = `
    from(bucket: "${INFLUX_BUCKET}")
      |> range(start: -${range})
      |> filter(fn: (r) => r["device"] == "${mac.toLowerCase()}")
      |> filter(fn: (r) =>
        r["_measurement"] == "MPU6050" or
        r["_measurement"] == "DS18B20" or
        r["_measurement"] == "PZEM004T"
      )
      |> filter(fn: (r) =>
        r["_field"] == "temperature" or
        r["_field"] == "accel_percent" or
        r["_field"] == "voltage" or
        r["_field"] == "current" or
        r["_field"] == "power"
      )
      |> aggregateWindow(every: 5s, fn: mean, createEmpty: false)
  `;

  const result = {
    temperature: [],
    vibration: [],
    voltage: [],
    current: [],
    power: []
  };

  await queryApi.queryRows(fluxQuery, {
    next: (row, tableMeta) => {
      const o = tableMeta.toObject(row);
      const point = { time: o._time, value: o._value };

      if (o._field === "temperature") result.temperature.push(point);
      if (o._field === "accel_percent") result.vibration.push(point);
      if (o._field === "voltage") result.voltage.push(point);
      if (o._field === "current") result.current.push(point);
      if (o._field === "power") result.power.push(point);
    },
    complete: () => res.json(result),
    error: err => res.status(500).json(err)
  });
});

//-----------------------------
app.get("/api/status", async (req, res) => {
  try {
    let isConnected = false;

    await queryApi.queryRows('buckets()', {
      next: () => { isConnected = true; },
      error: (err) => {
        console.error("❌ InfluxDB error:", err);
        res.json({ connected: false });
      },
      complete: () => {
        res.json({ connected: isConnected });
      }
    });

  } catch (error) {
    console.error("❌ InfluxDB connection failed:", error);
    res.json({ connected: false });
  }
});

app.listen(PORT, "0.0.0.0", () => {
  console.log(` API Server running on port ${PORT}`);
});

