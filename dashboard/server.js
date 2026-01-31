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

// --- API Endpoints ---
app.get("/api/latest/:mac", (req, res) => {
    const mac = req.params.mac.toLowerCase();
    const fluxQuery = `
        from(bucket: "${INFLUX_BUCKET}")
          |> range(start: -24h)
          |> filter(fn: (r) => r["device"] == "${mac}")
          |> last()
    `;

    const result = {};
    queryApi.queryRows(fluxQuery, {
        next: (row, tableMeta) => {
            const o = tableMeta.toObject(row);
            result[o._field] = o._value;
        },
        complete: () => res.json(result),
        error: (err) => res.status(500).json({ error: err.message })
    });
});

app.get("/api/status", async (req, res) => {
    try {
        // ทดสอบดึงข้อมูลสั้นๆ เพื่อเช็คว่าติดต่อ InfluxDB ได้จริงไหม
        await queryApi.collectRows(`
            from(bucket: "${INFLUX_BUCKET}")
              |> range(start: -1m)
              |> limit(n:1)
        `);
        res.json({ connected: true });
    } catch (err) {
        res.json({ connected: false, error: err.message });
    }
});
app.listen(PORT, () => console.log(` Server running on port ${PORT}`));