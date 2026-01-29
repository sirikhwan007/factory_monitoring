const API_BASE = `${window.location.protocol}//${window.location.hostname}:5000`;

// --- 1. Plugin และ Utility Functions ---

// Plugin สำหรับเข็ม (Needle) ของ Gauge
const gaugeNeedlePlugin = {
  id: 'gaugeNeedle',
  afterDatasetDraw(chart) {
    const { ctx, chartArea: { width, height, top, left }, data } = chart;
    const needleValue = data.datasets[0].needleValue ?? 0;
    const max = data.datasets[0].data.reduce((a, b) => a + b, 0) || 1;
    const angle = Math.PI + (needleValue / max) * Math.PI;

    const cx = left + width / 2;
    const cy = top + height * 0.75;

    ctx.save();
    ctx.translate(cx, cy);
    ctx.rotate(angle);
    ctx.beginPath();
    ctx.moveTo(0, -5);
    ctx.lineTo(height / 1.8, 0);
    ctx.lineTo(0, 5);
    ctx.fillStyle = "#1f2937";
    ctx.fill();
    ctx.restore();

    ctx.beginPath();
    ctx.arc(cx, cy, 6, 0, Math.PI * 2);
    ctx.fillStyle = "#1f2937";
    ctx.fill();
  }
};

// ฟังก์ชันสร้างสี Gradient ตามค่า
function valueToColor(value, min = 0, max = 100) {
  const ratio = Math.min(Math.max((value - min) / (max - min), 0), 1);
  const r = Math.round(255 * ratio);
  const g = Math.round(255 * (1 - ratio));
  return `rgb(${r},${g},0)`;
}

// Smooth data (ทำให้เส้นกราฟดูนุ่มนวลขึ้น)
function smoothData(arr, windowSize = 3) {
  if (arr.length < windowSize) return arr;
  let smoothed = [];
  for (let i = 0; i < arr.length; i++) {
    let start = Math.max(0, i - windowSize + 1);
    let subset = arr.slice(start, i + 1);
    let avg = subset.reduce((a, b) => a + b, 0) / subset.length;
    smoothed.push(avg);
  }
  return smoothed;
}

// --- 2. ฟังก์ชันสร้างกราฟ ---

// สร้าง Gauge ที่มีเข็ม
function createGauge(ctx, maxValue) {
  if (!ctx) return null;
  return new Chart(ctx, {
    type: 'doughnut',
    data: {
      datasets: [{
        data: [0, maxValue],
        backgroundColor: ['#4ade80', '#e5e7eb'],
        borderWidth: 0,
        cutout: '80%',
        rotation: -90,
        circumference: 180,
        needleValue: 0
      }]
    },
    options: {
      responsive: true,
      plugins: { legend: { display: false }, tooltip: { enabled: false } },
    },
    plugins: [gaugeNeedlePlugin]
  });
}

// สร้าง Line Chart (แก้ไขให้รองรับ Time Scale และ Double Click)
const createLineChart = (ctx, label, color) => {
  if (!ctx) return null;

  const chart = new Chart(ctx, {
    type: 'line',
    data: { 
        labels: [], 
        datasets: [{ 
            label, 
            data: [], 
            borderColor: color, 
            tension: 0.3, 
            fill: false,
            pointRadius: 0 // ซ่อนจุดเล็กๆ เพื่อให้กราฟดูสะอาดตาเมื่อข้อมูลเยอะ
        }] 
    },
    options: {
      responsive: true,
      interaction: {
        mode: 'index',
        intersect: false,
      },
      scales: {
        x: {
          type: 'time', // กำหนดแกน X เป็นเวลา
          time: {
            unit: 'minute', // หน่วยหลักเป็นชั่วโมง
            stepSize: 4,  // เริ่มต้น: ห่างช่องละ 4 ชม.
            displayFormats: {
              minute: 'HH:mm' // รูปแบบการแสดงผล
            },
            tooltipFormat: 'HH:mm:ss'
          },
          title: {
            display: true,
            text: 'Time'
          }
        },
        y: { 
          beginAtZero: true 
        }
      }
    }
  });

  // --- เพิ่ม Event Listener: ดับเบิ้ลคลิกเพื่อเปลี่ยนสเกลเวลา ---
  if (ctx.canvas) {
    ctx.canvas.addEventListener('dblclick', (e) => {
        const scaleX = chart.scales.x; // เข้าถึง Scale ปัจจุบัน
        
        // เช็คว่ากด Shift ค้างไว้ไหม (ถ้ากด Shift+Double Click ให้รีเซ็ตการซูม)
        if (e.shiftKey) {
            delete chart.options.scales.x.min;
            delete chart.options.scales.x.max;
            console.log(`${label}: Zoom Reset`);
        } else {
            // คำนวณช่วงเวลาปัจจุบัน (Range)
            const min = scaleX.min;
            const max = scaleX.max;
            const range = max - min;
            
            // กำหนดความแรงในการซูม (เช่น 0.5 คือซูมเข้า 50% หรือทีละ 2 เท่า)
            const zoomFactor = 0.5; 
            const newRange = range * zoomFactor;
            
            // คำนวณค่า min/max ใหม่ โดยให้ซูมเข้าหา "จุดกึ่งกลาง" ของช่วงปัจจุบัน
            const center = min + (range / 2);
            chart.options.scales.x.min = center - (newRange / 2);
            chart.options.scales.x.max = center + (newRange / 2);
            
            console.log(`${label}: Zoom In`);
        }

        chart.update(); // อัปเดตกราฟ
    });
}

  return chart;
};

// --- 3. ฟังก์ชันอัปเดตข้อมูล ---

// อัปเดตเข็มและสี
function updateGauge(gauge, value, maxValue) {
  if (!gauge) return;
  const color = valueToColor(value ?? 0, 0, maxValue);
  gauge.data.datasets[0].needleValue = value ?? 0;
  gauge.data.datasets[0].backgroundColor[0] = color;
  gauge.update();
}

// อัปเดตกราฟเส้น
function updateLineChart(chart, value, timeDateObject, smooth = true) {
  if (!chart) return;

  chart.data.labels.push(timeDateObject);
  chart.data.datasets[0].data.push(value ?? 0);

  if (chart.data.labels.length > 5000) {
    chart.data.labels.shift();
    chart.data.datasets[0].data.shift();
  }

  // ✅ smooth เฉพาะ live
  if (smooth) {
    chart.data.datasets[0].data =
      smoothData(chart.data.datasets[0].data, 3);
  }

  chart.update('none');
}


// --- 4. Main Execution ---

document.addEventListener("DOMContentLoaded", async () => {
  
  // สร้างกราฟ
  const tempChart = createLineChart(document.getElementById("tempChart"), "Temperature", "#f87171");
  const vibChart = createLineChart(document.getElementById("vibChart"), "Vibration", "#facc15");
  const voltChart = createLineChart(document.getElementById("voltChart"), "Voltage", "#60a5fa");
  const currChart = createLineChart(document.getElementById("currChart"), "Current", "#34d399");
  const powChart = createLineChart(document.getElementById("powChart"), "Power", "#a78bfa");

  // สร้าง Gauge
  const tempGauge = createGauge(document.getElementById("tempGauge"), 100);
  const vibGauge = createGauge(document.getElementById("vibGauge"), 10);
  const voltGauge = createGauge(document.getElementById("voltGauge"), 400);
  const currGauge = createGauge(document.getElementById("currGauge"), 50);
  const powGauge = createGauge(document.getElementById("powGauge"), 1000);


  //loadhistory
  /*async function loadHistory() {
  const res = await fetch(`${API_BASE}/api/history?range=1h`);
  const history = await res.json();

  history.temperature.forEach(p =>
  updateLineChart(tempChart, p.value, new Date(p.time), false)
);
history.vibration.forEach(p =>
  updateLineChart(vibChart, p.value, new Date(p.time), false)
);
history.voltage.forEach(p =>
  updateLineChart(voltChart, p.value, new Date(p.time), false)
);
history.current.forEach(p =>
  updateLineChart(currChart, p.value, new Date(p.time), false)
);
history.power.forEach(p =>
  updateLineChart(powChart, p.value, new Date(p.time), false)
);

}*/

  // ตรวจสอบสถานะ InfluxDB
async function checkInfluxStatus() {
  const influxStatusEl = document.getElementById("influx-status");
  try {
    const res = await fetch(`${API_BASE}/api/status`);
    const data = await res.json();
    if (influxStatusEl) {
      influxStatusEl.textContent = data.connected 
        ? "เชื่อมต่อสำเร็จ" 
        : "เชื่อมต่อล้มเหลว";
      influxStatusEl.className = data.connected 
        ? "badge bg-success" 
        : "badge bg-danger";
    }
  } catch {
    if (influxStatusEl) {
      influxStatusEl.textContent = "ไม่สามารถติดต่อ API ได้";
      influxStatusEl.className = "badge bg-danger";
    }
  }
}


  // ดึงข้อมูลจาก API
  async function fetchData() {
    try {
      const res = await fetch(`${API_BASE}/api/latest/${MACHINE_MAC}`);
      const data = await res.json();
      // console.log("API data:", data);
      if (!data || Object.keys(data).length === 0) return;

      // สร้าง Date Object ปัจจุบัน (สำคัญสำหรับแกน เวลา)
      const now = new Date();

      // อัปเดตตัวเลข Text
      const elTemp = document.getElementById("temp"); if(elTemp) elTemp.textContent = data.temperature?.toFixed(2) ?? "--";
      const elVib = document.getElementById("vib"); if(elVib) elVib.textContent = data.vibration?.toFixed(2) ?? "--";
      const elVolt = document.getElementById("volt"); if(elVolt) elVolt.textContent = data.voltage?.toFixed(2) ?? "--";
      const elCurr = document.getElementById("curr"); if(elCurr) elCurr.textContent = data.current?.toFixed(2) ?? "--";
      const elPow = document.getElementById("pow"); if(elPow) elPow.textContent = data.power?.toFixed(2) ?? "--";

      // อัปเดตสถานะเครื่องจักร
      // --- เพิ่มฟังก์ชันตรวจสอบสถานะ (ใส่ไว้ข้างนอกหรือใน fetchData ก็ได้) ---
function getMachineStatus(data) {
    let status = { text: "ปกติ", className: "bg-success", isAbnormal: false };

    // กำหนดเกณฑ์ตามที่คุณให้มา
    const isDanger = (
        data.temperature >= 35 ||
        data.vibration >= 15 ||
        data.current >= 8 ||
        data.voltage >= 300 ||
        data.power >= 20
    );

    const isWarning = (
        data.temperature >= 34 ||
        data.vibration >= 5 ||
        data.current >= 5 ||
        data.voltage >= 250 ||
        data.power >= 15
    );

    if (isDanger) {
        status = { text: "ผิดปกติ (อันตราย)", className: "bg-danger", isAbnormal: true };
    } else if (isWarning) {
        status = { text: "ผิดปกติ (เฝ้าระวัง)", className: "bg-warning text-dark", isAbnormal: true };
    } else if (data.power <= 0.5) {
        // เงื่อนไขเดิม: ถ้าไม่มีไฟเข้าเลยแสดงว่าหยุดทำงาน
        status = { text: "หยุดทำงาน", className: "bg-secondary", isAbnormal: false };
    }

    return status;
}
      // ในฟังก์ชัน fetchData()
const statusEl = document.getElementById("machine-status");
if (statusEl && data) {
    const machineStatus = getMachineStatus(data);
    
    statusEl.textContent = machineStatus.text;
    // ล้าง class เดิมออกก่อนแล้วใส่ class ใหม่
    statusEl.className = `badge ${machineStatus.className}`;
    
    // (เพิ่มเติม) ถ้าต้องการให้ตัวเลขค่าต่างๆ เป็นสีแดงเมื่อผิดปกติ
    // document.getElementById("temp").style.color = data.temperature >= 34 ? "red" : "black";
}
        
      const updatedEl = document.getElementById("updated");
      if (updatedEl) updatedEl.textContent = "Last update: " + now.toLocaleTimeString();

      // อัปเดตกราฟเส้น (ส่ง now ที่เป็น Date Object ไป)
      updateLineChart(tempChart, data.temperature, now);
      updateLineChart(vibChart, data.vibration, now);
      updateLineChart(voltChart, data.voltage, now);
      updateLineChart(currChart, data.current, now);
      updateLineChart(powChart, data.power, now);

      // อัปเดต Gauge
      updateGauge(tempGauge, data.temperature, 100);
      updateGauge(vibGauge, data.vibration, 10);
      updateGauge(voltGauge, data.voltage, 400);
      updateGauge(currGauge, data.current, 50);
      updateGauge(powGauge, data.power, 1000);

    } catch (err) {
      console.error("❌ Fetch error:", err);
    }
  }

  // เริ่มทำงาน
  checkInfluxStatus();
  //await loadHistory();
  fetchData();
  
  // Loop การทำงาน
  setInterval(fetchData, 1000);       // ดึงข้อมูลทุก 1 วินาที
  setInterval(checkInfluxStatus, 5000); // เช็คสถานะทุก 5 วินาที
});