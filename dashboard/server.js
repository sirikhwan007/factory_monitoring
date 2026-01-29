//server.js ต่อได้
import express from "express";
import { InfluxDB, Point } from "@influxdata/influxdb-client";

const app = express();
const PORT = 5000;
import cors from "cors";
app.use(cors());

import mqtt from "mqtt";


// ตั้งค่าการเชื่อมต่อ InfluxDB
const url = "https://influxdb-tcesenior.as2.pitunnel.net";
const token = "mpiI63Hli-vbbRMj_GZk7sahDnsa2_fce8Gqb-sNzkSD1ibrPefDGfjsRJoxEphrORn9knZf0A59XqUivWLmTQ==";
const org = "b79809a86d9bbee5";
const bucket = "Motor-Monitoring";

const influx = new InfluxDB({ url, token });
const queryApi = influx.getQueryApi(org);
const writeApi = influx.getWriteApi(org, bucket);

// --- [2] ส่วนการเชื่อมต่อ MQTT (วางต่อจากตั้งค่า InfluxDB) ---
const mqttClient = mqtt.connect("mqtt://10.76.56.38", {
    username: "myuser",
    password: "0935160117"
});
mqttClient.on("connect", () => {
    console.log("✅ Connected to MQTT Broker");
    mqttClient.subscribe("test/sensor/data"); // Subscribe รอรับข้อมูลจาก ESP32
});


// --- [3] ฟังก์ชันเงื่อนไขและการบันทึกข้อมูล ---
mqttClient.on("message", (topic, message) => {
    try {
        const data = JSON.parse(message.toString());
        const temp = data.temperature;
        const cur = data.pzem?.current || 0;
        const vib = data.accel_percent;
        const volt = data.accel_percent;
        const power = data.pzem?.power || 0;

        // ประกาศสถานะไฟเบื้องต้น (ปิดหมด)
        let ledStates = {
            green: { pin: 33, value: 0 },
            yellow: { pin: 32, value: 0 },
            red: { pin: 19, value: 0 }
        };

// ระดับความรุนแรง
let danger = false;
let warning = false;

// -------- Temperature --------
if (temp >= 35) danger = true;
else if (temp >= 34) warning = true;

// -------- Vibration --------
if (vib >= 15) danger = true;
else if (vib >= 5) warning = true;

// -------- Current --------
if (cur >= 8) danger = true;
else if (cur >= 5) warning = true;

if (volt >= 300) danger = true;
else if (volt >= 250) warning = true;

if (power >= 20) danger = true;
else if (power >= 15) warning = true;

// -------- สรุปสถานะ --------
if (danger) {
  ledStates.red.value = 1;
}
else if (warning) {
  ledStates.yellow.value = 1;
}
else {
  ledStates.green.value = 1;
}

        // --- ส่งคำสั่ง MQTT ไปที่ ESP32 ---
        // ส่งสถานะไฟทั้ง 3 ดวงเพื่อให้ ESP32 อัปเดตพร้อมกัน
        Object.values(ledStates).forEach(led => {
            mqttClient.publish("test/cmd/led", JSON.stringify({ pin: led.pin, value: led.value }));
        });

        console.log(`[LOG] Temp: ${temp}C | Status updated.`);

        // ... ส่วนบันทึก InfluxDB ...
    } catch (err) {
        console.error("❌ Logic Error:", err);
    }
});

// สร้าง API /api/latest (ปรับปรุง)
// แก้ไข API /api/latest ให้รับ MAC Address
app.get("/api/latest/:mac", async (req, res) => {
  const { mac } = req.params; // รับค่าจาก URL เช่น /api/latest/aa:bb:cc...
  try {
    const fluxQuery = `
      from(bucket: "${bucket}")
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
// 🔥 API ดึงข้อมูลย้อนหลัง (ใส่ตรงนี้)
// =============================
app.get("/api/history", async (req, res) => {
  const range = req.query.range || "1h";

  const fluxQuery = `
    from(bucket: "${bucket}")
      |> range(start: -${range})
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
      |> aggregateWindow(every: 10s, fn: mean, createEmpty: false)
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
  console.log(`✅ API Server running on port ${PORT}`);
});

