import express from "express";
import cors from "cors";
import mqtt from "mqtt";
import { InfluxDB } from "@influxdata/influxdb-client";

const app = express();
app.use(cors({
  origin: "*"
}));


// =======================
// ENV (Render)
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
// InfluxDB
// =======================
const influx = new InfluxDB({ url: INFLUX_URL, token: INFLUX_TOKEN });
const queryApi = influx.getQueryApi(INFLUX_ORG);

// =======================
// MQTT
// =======================
const client = mqtt.connect(process.env.MQTT_URL, {
  username: process.env.MQTT_USER,
  password: process.env.MQTT_PASS,
  reconnectPeriod: 5000
});

client.on("connect", () => {
  console.log("MQTT connected");
  client.subscribe("factory/esp32/sensor");
});


client.on("error", err => {
  console.error("MQTT error:", err.message);
});

// --- [2] ส่วนการเชื่อมต่อ MQTT (วางต่อจากตั้งค่า InfluxDB) ---
const mqttClient = mqtt.connect("mqtt://broker.freemqtt.com", {
    username: "freemqtt",
    password: "public"
});
mqttClient.on("connect", () => {
    console.log("✅ Connected to MQTT Broker");
    mqttClient.subscribe("test/sensor/data"); // Subscribe รอรับข้อมูลจาก ESP32
});


// --- [3] ฟังก์ชันเงื่อนไขและการบันทึกข้อมูล ---
mqttClient.on("message", (topic, message) => {
    try {
        const data = JSON.parse(message.toString());
        const temp = data.temperature || 0;
        const cur = data.pzem?.current || 0;
        const vib = data.accel_percent || 0;
        const volt = data.accel_percent || 0;
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

        // ... ส่วนบันทึก InfluxDB ...
    } catch (err) {
        console.error("❌ Logic Error:", err);
    }
});

// =======================
// API
// =======================
app.get("/", (req, res) => {
  res.send("Factory Monitoring API running");
});

app.get("/api/latest/:mac", async (req, res) => {
  const mac = req.params.mac.toLowerCase();

  const fluxQuery = `
    from(bucket: "${INFLUX_BUCKET}")
      |> range(start: -10m)
      |> filter(fn: (r) => r["device"] == "${mac.toLowerCase()}")
      |> last()
  `;

  const result = {};

  queryApi.queryRows(fluxQuery, {
    next: (row, tableMeta) => {
      const o = tableMeta.toObject(row);
      result[o._field] = o._value;
    },
    complete: () => {
      res
        .type("application/json; charset=utf-8")
        .json(result);
    },
    error: err => {
      res
        .status(500)
        .type("application/json; charset=utf-8")
        .json(err);
    }
  });
});

app.get("/api/status", async (req, res) => {
  try {
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


// =======================
app.listen(PORT, () => {
  console.log(`API running on port ${PORT}`);
});