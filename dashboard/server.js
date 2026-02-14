// server version02
import express from "express";
import cors from "cors";
import mqtt from "mqtt";
import { InfluxDB, Point } from "@influxdata/influxdb-client";
import mysql from "mysql2/promise";

const app = express();
app.use(cors({ origin: "*" }));

// --- Config (ตรวจสอบค่าเหล่านี้ใน Environment Variables ของ Render) ---
const PORT = process.env.PORT || 5000;
const INFLUX_URL = process.env.INFLUX_URL;
const INFLUX_TOKEN = process.env.INFLUX_TOKEN;
const INFLUX_ORG = process.env.INFLUX_ORG;
const INFLUX_BUCKET = process.env.INFLUX_BUCKET;

const dbConfig = {
    host: 'bft7bcehnrmpxwyzktj4-mysql.services.clever-cloud.com',   // หรือ IP ของ Database Server
    user: 'urqpet8hr9e140i5',        // User ของ Database
    password: 'kHolbtlLF8xcItzwe1Qc',        // Password ของ Database
    database: 'bft7bcehnrmpxwyzktj4'        // ชื่อ Database ที่ต้องการเชื่อมต่อ
};

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
// --- ตัวแปรเก็บสถานะอุปกรณ์ ---
const deviceAlertState = {};

async function autoReportToMySQL(mac, temp, vib, cur) {
    let connection;
    try {
        connection = await mysql.createConnection(dbConfig);
        
        // 1. หา machine_id
        const [machines] = await connection.execute(
            'SELECT machine_id FROM machines WHERE mac_address = ?', 
            [mac]
        );
        const machineId = machines.length > 0 ? machines[0].machine_id : mac;

        // -------------------------------------------------------------
        // [เพิ่มส่วนนี้] 2. สุ่มหาช่าง 1 คน (Role = Technician)
        // -------------------------------------------------------------
        const [techs] = await connection.execute(
            "SELECT user_id FROM users WHERE role = 'Technician' ORDER BY RAND() LIMIT 1"
        );

        let technicianId = null;
        let technicianName = "System Pool"; // ถ้าไม่มีช่าง ให้กองไว้ที่ส่วนกลาง

        if (techs.length > 0) {
            technicianId = techs[0].user_id;
            technicianName = `Auto-Assigned to ID: ${technicianId}`;
        }
        // -------------------------------------------------------------

        const detail = `[Auto Alert] Danger detected > 1 min. (Temp:${temp}, Vib:${vib}, Amp:${cur})`;

        // 3. Insert โดยใส่ technician_id ลงไปด้วย
        const sql = `
            INSERT INTO repair_history 
            (machine_id, reporter, position, type, detail, status, report_time, technician_id) 
            VALUES (?, 'AI System', 'Monitoring Bot', 'Breakdown', ?, 'รอดำเนินการ', NOW(), ?)
        `;
        
        await connection.execute(sql, [machineId, detail, technicianId]);
        console.log(`🚨 Report created for ${machineId} -> Assigned to Technician ID: ${technicianId}`);

    } catch (err) {
        console.error("❌ MySQL Error:", err);
    } finally {
        if (connection) await connection.end();
    }
}


// --- [ฟังก์ชัน LED และการบันทึกข้อมูลที่หายไป] ---
mqttClient.on("message", async (topic, message) => {
    try {
        const payload = JSON.parse(message.toString());
        const mac = (payload.mac || "unknown").toLowerCase();
        
        // 1. ดึงค่าจาก Payload
        const temp = payload.temperature || 0;
        const vib = payload.accel_percent || 0;
        const pzem = payload.pzem || {};
        const volt = pzem.voltage || 0;
        const cur = pzem.current || 0;
        const power = pzem.power || 0;
        const energy = pzem.energy || 0;

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
            if (typeof energy === 'number') {
              p.floatField("energy", energy);
          }
            points.push(p);
        }

        if (points.length > 0) {
            writeApi.writePoints(points);
            console.log(` Recorded ${points.length} points for ${mac}`);
        }

        // 3. Logic ควบคุมไฟ LED (Alert System)
        let danger = (temp >= 80 || vib >= 80 || cur >= 8 || volt >= 300 || power >= 20 || energy >= 3000);
        let warning = (temp >= 60 || vib >= 50 || cur >= 5 || volt >= 250 || power >= 10 || energy >= 2500);

        // สร้าง State ให้ Mac นี้ถ้ายังไม่มี
        if (!deviceAlertState[mac]) {
            deviceAlertState[mac] = { dangerCount: 0, warningCount: 0, dangerStartTime: null, isReported: false };
        }

        if (danger) {
            deviceAlertState[mac].dangerCount++; // ถ้าเกินเกณฑ์ ให้บวกเพิ่ม
        } else {
            deviceAlertState[mac].dangerCount = 0; // ถ้ากลับมาปกติ ให้รีเซ็ตเป็น 0 ทันที
        }

        // --- Logic นับ Warning ---
        if (warning) {
            deviceAlertState[mac].warningCount++;
        } else {
            deviceAlertState[mac].warningCount = 0;
        }

        // --- [ส่วนใหม่] Logic จับเวลา 1 นาทีเพื่อแจ้งซ่อม ---
        if (danger) {
            // ถ้าเพิ่งเริ่มอันตราย ให้บันทึกเวลาเริ่มต้น
            if (!deviceAlertState[mac].dangerStartTime) {
                deviceAlertState[mac].dangerStartTime = Date.now();
                console.log(`⏱️ START Timer for ${mac} at ${new Date().toLocaleTimeString()}`);
            } else {
                // คำนวณเวลาที่ผ่านไป (ms)
                const elapsed = Date.now() - deviceAlertState[mac].dangerStartTime;
                const seconds = (elapsed / 1000).toFixed(1);
                console.log(`⏳ Timer Running: ${seconds}s / 10s | Reported: ${deviceAlertState[mac].isReported}`);
                // ถ้าเกิน 60,000 ms (1 นาที) และยังไม่ได้แจ้งซ่อม
                if (elapsed >= 10000 && !deviceAlertState[mac].isReported) {
                    await autoReportToMySQL(mac, temp, vib, cur); // เรียกฟังก์ชันแจ้งซ่อม
                    deviceAlertState[mac].isReported = true;
                    console.log("✅ Database Insert Requested."); // ล็อกไว้ไม่ให้แจ้งซ้ำ
                } else {
                        console.log("🔒 Already reported. Waiting for reset.");
                    }
            }
        } else {
            // ถ้ากลับมาปกติ ให้รีเซ็ตค่าทั้งหมด
            if (deviceAlertState[mac].dangerStartTime) {
                console.log(`🔄 Reset Timer for ${mac} (Conditions back to normal)`);
            }
            deviceAlertState[mac].dangerStartTime = null;
            deviceAlertState[mac].isReported = false;
        }

        console.log(`Dev: ${mac} | DangerCount: ${deviceAlertState[mac].dangerCount} | WarningCount: ${deviceAlertState[mac].warningCount}`);

        // --- ตัดสินใจเปลี่ยนสีไฟ (Threshold > 4) ---
        // จะติดก็ต่อเมื่อนับได้เกิน 4 ครั้งต่อเนื่อง (ครั้งที่ 5 เป็นต้นไปถึงจะติด)
        let finalDanger = deviceAlertState[mac].dangerCount > 2;
        let finalWarning = deviceAlertState[mac].warningCount > 2;


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
        |> filter(fn: (r) => r["_field"] == "temperature" or r["_field"] == "accel_percent" or r["_field"] == "voltage" or r["_field"] == "current" or r["_field"] == "power" or r["_field"] == "energy")
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
  //const mac = req.query.mac;
  const mac = req.query.mac;

  if (!mac) {
    return res.status(400).json({ error: "MAC address is required" });
  }

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
        r["_field"] == "power" or
        r["_field"] == "energy"
      )
      |> aggregateWindow(every: 5s, fn: mean, createEmpty: false)
  `;

  const result = {
    temperature: [],
    vibration: [],
    voltage: [],
    current: [],
    power: [],
    energy: []
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
      if (o._field === "energy") result.energy.push(point);
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

