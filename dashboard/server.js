import express from "express";
import cors from "cors";
import mqtt from "mqtt";
import { InfluxDB, Point } from "@influxdata/influxdb-client";
import mysql from "mysql2/promise";

const app = express();
app.use(cors({ origin: "*" }));
app.use(express.json());

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

const deviceAlertState = {};

async function autoReportToMySQL(mac, temp, vib, cur, volt, power, energy, level) {
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
        // 2. สุ่มหาช่าง 1 คน
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

        let timeText = "";
        if (level === "Warning") {
            timeText = "> 30 sec.";
        } else {
            timeText = "> 1 min."; // สำหรับ Danger
        }
        // -------------------------------------------------------------

        const detail = `[Auto Alert] ${level} detected ${timeText} (` +
            `Temp:${Number(temp).toFixed(2)}, ` +
            `Vib:${Number(vib).toFixed(2)}, ` +
            `Amp:${Number(cur).toFixed(2)}, ` +
            `Volt:${Number(volt).toFixed(2)}, ` + 
            `Power:${Number(power).toFixed(2)}, ` +  
            `Energy:${Number(energy).toFixed(2)})`;

        // 3. Insert โดยใส่ technician_id ถ้ามี ถ้าไม่มีใส่เป็น NULL
        const sql = `
            INSERT INTO repair_history 
            (machine_id, reporter, position, type, detail, status, report_time, technician_id) 
            VALUES (?, 'System', 'Monitoring Bot', 'Breakdown', ?, 'รอดำเนินการ', DATE_ADD(NOW(), INTERVAL 7 HOUR), ?)
        `;
        
        await connection.execute(sql, [machineId, detail, technicianId]);
        console.log(` Report created for ${machineId} -> Assigned to Technician ID: ${technicianId}`);

    } catch (err) {
        console.error(" MySQL Error:", err);
    } finally {
        if (connection) await connection.end();
    }
}

// --- Threshold Cache System ---
let thresholdsCache = {};

async function loadThresholds() {
    let connection;
    try {
        connection = await mysql.createConnection(dbConfig);
        const [rows] = await connection.execute('SELECT * FROM thresholds');
        
        let newCache = {};
        rows.forEach(row => {
            newCache[row.mac_address] = row;
        });
        
        thresholdsCache = newCache;
        console.log(" โหลด Thresholds จาก Database อัปเดตล่าสุดแล้ว");
    } catch (err) {
        console.error(" พบปัญหาการโหลด Thresholds:", err);
    } finally {
        if (connection) await connection.end();
    }
}

loadThresholds();
setInterval(loadThresholds, 60000);

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
        //let danger = (temp >= 55 || vib >= 80 || cur >= 8 || volt >= 280 || power >= 1700 || energy >= 3000);
        //let warning = (temp >= 45 || vib >= 60 || cur >= 4 || volt >= 230 || power >= 800 || energy >= 2500);
        const t = thresholdsCache[mac] || thresholdsCache['default'];

        let danger = false;
        let warning = false;

        if (t) {
            // ใช้เกณฑ์จาก Database
            danger = (temp >= t.danger_temp || vib >= t.danger_vib || cur >= t.danger_cur || volt >= t.danger_volt || power >= t.danger_power || energy >= t.danger_energy);
            warning = (temp >= t.warn_temp || vib >= t.warn_vib || cur >= t.warn_cur || volt >= t.warn_volt || power >= t.warn_power || energy >= t.warn_energy);
        } else {
            // Fallback ถ้าไม่มีใน Database ใช้ค่าเดิม
            danger = (temp >= 55 || vib >= 80 || cur >= 8 || volt >= 280 || power >= 1700 || energy >= 3000);
            warning = (temp >= 45 || vib >= 60 || cur >= 4 || volt >= 230 || power >= 800 || energy >= 2500);
        }

        // สร้าง State ให้ Mac นี้ถ้ายังไม่มี
        if (!deviceAlertState[mac]) {
            deviceAlertState[mac] = { dangerCount: 0, warningCount: 0, dangerStartTime: null, isReported: false, warningStartTime: null, isWarningReported: false };
        }

        if (danger) {
            deviceAlertState[mac].dangerCount++; 
        } else {
            deviceAlertState[mac].dangerCount = 0; 
        }

        if (warning) {
            deviceAlertState[mac].warningCount++;
        } else {
            deviceAlertState[mac].warningCount = 0;
        }

        if (danger) {
            // ถ้าเพิ่งเริ่มอันตราย ให้บันทึกเวลาเริ่มต้น
            deviceAlertState[mac].warningStartTime = null;
            deviceAlertState[mac].isWarningReported = false;
            
            if (!deviceAlertState[mac].dangerStartTime) {
                deviceAlertState[mac].dangerStartTime = Date.now();
                console.log(` START Timer for ${mac} at ${new Date().toLocaleTimeString()}`);
            } else {
                // คำนวณเวลาที่ผ่านไป (ms)
                const elapsed = Date.now() - deviceAlertState[mac].dangerStartTime;
                const seconds = (elapsed / 1000).toFixed(1);
                console.log(` Timer Running: ${seconds}s / 10s | Reported: ${deviceAlertState[mac].isReported}`);
                // ถ้าเกิน 60,000 ms (1 นาที) 
                if (elapsed >= 60000 && !deviceAlertState[mac].isReported) {
                    await autoReportToMySQL(mac, temp, vib, cur, volt, power, energy, "Danger"); // เรียกฟังก์ชันแจ้งซ่อม
                    deviceAlertState[mac].isReported = true;
                    console.log(" Database Insert Requested."); // ล็อกไว้ไม่ให้แจ้งซ้ำ
                } 
            }

        }else if (warning) {
            // รีเซ็ตตัวจับเวลา Danger
            deviceAlertState[mac].dangerStartTime = null;
            deviceAlertState[mac].isReported = false;

            if (!deviceAlertState[mac].warningStartTime) {
                deviceAlertState[mac].warningStartTime = Date.now();
                console.log(` [WARNING] Timer Started for ${mac}`);
            } else {
                const elapsed = Date.now() - deviceAlertState[mac].warningStartTime;
                // เช็คเวลา 
                if (elapsed >= 30000 && !deviceAlertState[mac].isWarningReported) {
                    console.log(" Sending WARNING Report...");
                    // ส่งค่า "Warning" ไป
                    await autoReportToMySQL(mac, temp, vib, cur, volt, power, energy, "Warning");
                    deviceAlertState[mac].isWarningReported = true;
                }
            } 
          }else {
            // ถ้ากลับมาปกติ ให้รีเซ็ตค่าทั้งหมด
            if (deviceAlertState[mac].dangerStartTime || deviceAlertState[mac].warningStartTime) {
                console.log(` [NORMAL] Reset Timers for ${mac}`);
            }
            deviceAlertState[mac].dangerStartTime = null;
            deviceAlertState[mac].isReported = false;
            deviceAlertState[mac].warningStartTime = null;
            deviceAlertState[mac].isWarningReported = false;
        }

        console.log(`Dev: ${mac} | DangerCount: ${deviceAlertState[mac].dangerCount} | WarningCount: ${deviceAlertState[mac].warningCount}`);

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
  const { mac } = req.params;
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
//  API ดึงข้อมูลย้อนหลัง
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
        console.error(" InfluxDB error:", err);
        res.json({ connected: false });
      },
      complete: () => {
        res.json({ connected: isConnected });
      }
    });

  } catch (error) {
    console.error(" InfluxDB connection failed:", error);
    res.json({ connected: false });
  }
});

// API สำหรับบันทึกหรือแก้ไขเกณฑ์ (Thresholds)
app.post("/api/thresholds", async (req, res) => {
    const { 
        mac_address, 
        warn_temp, danger_temp, 
        warn_vib, danger_vib, 
        warn_cur, danger_cur,
        warn_volt, danger_volt,
        warn_power, danger_power,
        warn_energy, danger_energy 
    } = req.body;

    let connection;
    try {
        connection = await mysql.createConnection(dbConfig);
        
        
        const sql = `
            INSERT INTO thresholds 
            (mac_address, warn_temp, danger_temp, warn_vib, danger_vib, warn_cur, danger_cur, warn_volt, danger_volt, warn_power, danger_power, warn_energy, danger_energy)
            VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?)
            ON DUPLICATE KEY UPDATE 
            warn_temp=VALUES(warn_temp), danger_temp=VALUES(danger_temp),
            warn_vib=VALUES(warn_vib), danger_vib=VALUES(danger_vib),
            warn_cur=VALUES(warn_cur), danger_cur=VALUES(danger_cur),
            warn_volt=VALUES(warn_volt), danger_volt=VALUES(danger_volt),
            warn_power=VALUES(warn_power), danger_power=VALUES(danger_power),
            warn_energy=VALUES(warn_energy), danger_energy=VALUES(danger_energy)
        `;

        await connection.execute(sql, [
            mac_address || 'default', 
            warn_temp, danger_temp, 
            warn_vib, danger_vib, 
            warn_cur, danger_cur,
            warn_volt, danger_volt,
            warn_power, danger_power,
            warn_energy, danger_energy
        ]);

        await loadThresholds(); 

        res.json({ message: "Threshold updated and cache refreshed immediately!" });
        console.log(` Threshold for ${mac_address || 'default'} updated by Web UI`);

    } catch (err) {
        console.error(" API Error:", err);
        res.status(500).json({ error: "Failed to update thresholds" });
    } finally {
        if (connection) await connection.end();
    }
});

// API ดึงค่า Threshold ของแต่ละเครื่องจักร

app.get("/api/thresholds/:mac", async (req, res) => {
    const mac = req.params.mac;
    let connection;
    try {
        connection = await mysql.createConnection(dbConfig);

        const [rows] = await connection.execute('SELECT * FROM thresholds WHERE mac_address = ?', [mac]);
        
        if (rows.length > 0) {
            res.json(rows[0]);
        } else {

            const [defaultRows] = await connection.execute("SELECT * FROM thresholds WHERE mac_address = 'default'");
            if (defaultRows.length > 0) {
                res.json(defaultRows[0]);
            } else {

                res.json({
                    warn_temp: 45, danger_temp: 55,
                    warn_vib: 60, danger_vib: 80,
                    warn_cur: 4, danger_cur: 8,
                    warn_volt: 230, danger_volt: 280,
                    warn_power: 800, danger_power: 1700,
                    warn_energy: 2500, danger_energy: 3000
                });
            }
        }
    } catch (err) {
        console.error("❌ API Threshold Fetch Error:", err);
        res.status(500).json({ error: "Failed to fetch thresholds" });
    } finally {
        if (connection) await connection.end();
    }
});

app.listen(PORT, "0.0.0.0", () => {
  console.log(` API Server running on port ${PORT}`);
});

