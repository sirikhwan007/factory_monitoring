import express from "express";
import cors from "cors";
import mqtt from "mqtt";
import { InfluxDB, Point } from "@influxdata/influxdb-client";

const app = express();
app.use(cors({ origin: "*" }));

// =======================
// 1. Configuration (Environment Variables)
// =======================
const PORT = process.env.PORT || 5000;
const INFLUX_URL = process.env.INFLUX_URL;
const INFLUX_TOKEN = process.env.INFLUX_TOKEN;
const INFLUX_ORG = process.env.INFLUX_ORG;
const INFLUX_BUCKET = process.env.INFLUX_BUCKET;

const MQTT_URL = process.env.MQTT_URL;
const MQTT_USER = process.env.MQTT_USER;
const MQTT_PASS = process.env.MQTT_PASS;

// =======================
// 2. InfluxDB Setup
// =======================
const influx = new InfluxDB({ url: INFLUX_URL, token: INFLUX_TOKEN });
const queryApi = influx.getQueryApi(INFLUX_ORG);
const writeApi = influx.getWriteApi(INFLUX_ORG, INFLUX_BUCKET);

// =======================
// 3. MQTT Setup & Logic
// =======================
const mqttClient = mqtt.connect(MQTT_URL, {
    username: MQTT_USER,
    password: MQTT_PASS,
    reconnectPeriod: 5000
});

mqttClient.on("connect", () => {
    console.log("✅ MQTT Connected & Monitoring Started");
    mqttClient.subscribe("test/sensor/data"); 
});

mqttClient.on("error", (err) => {
    console.error("❌ MQTT Error:", err.message);
});

mqttClient.on("message", (topic, message) => {
    try {
        const payload = JSON.parse(message.toString());
        const mac = payload.mac || "unknown";
        const points = [];

        // --- ส่วนที่ 1: ดึงค่าจาก Payload มาเตรียมไว้ใช้ ---
        const temp = payload.temperature || 0;
        const vib = payload.accel_percent || 0;
        const pzem = payload.pzem || {};
        const volt = pzem.voltage || 0;
        const cur = pzem.current || 0;
        const power = pzem.power || 0;

        // --- ส่วนที่ 2: บันทึกลง InfluxDB (แทนที่ไฟล์ Python เดิม) ---
        // Temperature
        if (typeof payload.temperature === 'number') {
            points.push(new Point("DS18B20").tag("device", mac).floatField("temperature", temp));
        }
        // PZEM
        if (Object.keys(pzem).length > 0) {
            const p = new Point("PZEM004T").tag("device", mac);
            let hasData = false;
            ["voltage", "current", "power", "energy", "frequency", "power_factor"].forEach(key => {
                if (typeof pzem[key] === 'number') {
                    p.floatField(key, pzem[key]);
                    hasData = true;
                }
            });
            if (hasData) points.push(p);
        }
        // Vibration
        if (typeof payload.accel_percent === 'number') {
            points.push(new Point("MPU6050").tag("device", mac).floatField("accel_percent", vib));
        }

        if (points.length > 0) {
            writeApi.writePoints(points);
            console.log(`📊 InfluxDB: Recorded ${points.length} points for ${mac}`);
        }

        // --- ส่วนที่ 3: Logic ควบคุมไฟ LED (Alert System) ---
        let danger = false;
        let warning = false;

        if (temp >= 35 || vib >= 15 || cur >= 8 || volt >= 300 || power >= 20) {
            danger = true;
        } else if (temp >= 34 || vib >= 5 || cur >= 5 || volt >= 250 || power >= 15) {
            warning = true;
        }

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
        console.error("❌ Process Error:", err);
    }
});

// =======================
// 4. API Endpoints
// =======================
app.get("/", (req, res) => res.send("Factory Monitoring API running"));

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

app.listen(PORT, () => console.log(`🚀 Server ready on port ${PORT}`));