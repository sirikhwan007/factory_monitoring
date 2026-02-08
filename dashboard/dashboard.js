// dashboard version0.1
const urlParams = new URLSearchParams(window.location.search);
const API_BASE = "https://factory-monitoring.onrender.com";


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
const energyChart = createLineChart(document.getElementById("energyChart"), "Energy", "#f472b6");

  // สร้าง Gauge
  const tempGauge = createGauge(document.getElementById("tempGauge"), 100);
  const vibGauge = createGauge(document.getElementById("vibGauge"), 10);
  const voltGauge = createGauge(document.getElementById("voltGauge"), 400);
  const currGauge = createGauge(document.getElementById("currGauge"), 50);
  const powGauge = createGauge(document.getElementById("powGauge"), 1000);
  const energyGauge = createGauge(document.getElementById("energyGauge"), 5000);


 async function loadHistory() {
  try {
    const res = await fetch(
      `${API_BASE}/api/history?range=1h&mac=${MACHINE_MAC}`
    );
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
    history.energy.forEach(p =>
      updateLineChart(energyChart, p.value, new Date(p.time), false)
    );
  } catch (err) {
    console.error("History load error:", err);
  }
}

await loadHistory(); // เรียกใช้งาน!
  fetchData();

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
      const elEnergy = document.getElementById("energy"); if(elEnergy) elEnergy.textContent = data.energy?.toFixed(2) ?? "--";

      // อัปเดตสถานะเครื่องจักรตาม Power (W)
      // ในฟังก์ชัน fetchData()
// อัปเดตสถานะเครื่องจักรตามค่าเซ็นเซอร์หลายตัว (สอดคล้องกับ Logic ของ Server)
const statusEl = document.getElementById("machine-status");
if (statusEl) {
    const temp = data.temperature || 0;
    const vib = data.vibration || 0;
    const cur = data.current || 0;
    const volt = data.voltage || 0;
    const power = data.power || 0;
    const energy = data.energy || 0;

    // กำหนดเงื่อนไข Danger และ Warning
    const isDanger = (temp >= 35 || vib >= 15 || cur >= 8 || volt >= 300 || power >= 20 || energy >= 1000);
    const isWarning = (temp >= 34 || vib >= 5 || cur >= 5 || volt >= 250 || power >= 15 || energy >= 800);
    const isRunning = (power > 0.5); // เช็คว่าเครื่องเปิดอยู่หรือไม่

    if (isDanger) {
        // สถานะผิดปกติรุนแรง (เทียบเท่าไฟสีแดง)
        statusEl.className = "badge bg-danger";
        statusEl.textContent = "อันตราย";
    } else if (isWarning) {
        // สถานะเตือน (เทียบเท่าไฟสีเหลือง)
        statusEl.className = "badge bg-warning text-dark"; // ใช้ text-dark เพื่อให้ชัดเจนบนสีเหลือง
        statusEl.textContent = "ผิดปกติ";
    } else if (isRunning) {
        // สถานะทำงานปกติ (เทียบเท่าไฟสีเขียว)
        statusEl.className = "badge bg-success";
        statusEl.textContent = "กำลังทำงาน";
    } else {
        // สถานะหยุดทำงาน
        statusEl.className = "badge bg-secondary"; 
        statusEl.textContent = "หยุดทำงาน";
    }
}
        
      const updatedEl = document.getElementById("updated");
      if (updatedEl) updatedEl.textContent = "Last update: " + now.toLocaleTimeString();

      // อัปเดตกราฟเส้น (ส่ง now ที่เป็น Date Object ไป)
      updateLineChart(tempChart, data.temperature, now, false);
      updateLineChart(vibChart, data.vibration, now, false);
      updateLineChart(voltChart, data.voltage, now, false);
      updateLineChart(currChart, data.current, now, false);
      updateLineChart(powChart, data.power, now, false);
      updateLineChart(energyChart, data.energy, now, false);

      // อัปเดต Gauge
      updateGauge(tempGauge, data.temperature, 100);
      updateGauge(vibGauge, data.vibration, 10);
      updateGauge(voltGauge, data.voltage, 400);
      updateGauge(currGauge, data.current, 50);
      updateGauge(powGauge, data.power, 1000);
      updateGauge(energyGauge, data.energy, 5000);

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

// ฟังก์ชันแสดงประวัติ 24 ชั่วโมงด้วย SweetAlert2
async function show24hHistory() {
    // แสดง Loading ระหว่างรอข้อมูล
    Swal.fire({
        title: 'กำลังดึงข้อมูลย้อนหลัง...',
        allowOutsideClick: false,
        didOpen: () => { Swal.showLoading(); }
    });

    try {
        // ดึงข้อมูลจาก API (ใช้ range=24h)
        const res = await fetch(`${API_BASE}/api/history?range=24h&mac=${MACHINE_MAC}`);
        const history = await res.json();

        // ปิด Loading และเปิดหน้าต่างกราฟ
        Swal.fire({
            title: `ประวัติการทำงาน 24 ชม. (${MACHINE_MAC})`,
            html: '<canvas id="historyChart" width="400" height="250"></canvas>',
            width: '80%',
            confirmButtonText: 'ปิด',
            didOpen: () => {
                const ctx = document.getElementById('historyChart').getContext('2d');
                
                // รวมข้อมูลทุก Field เข้าด้วยกันเพื่อหา Labels (เวลา)
                // หมายเหตุ: ปรับโครงสร้างข้อมูลตามที่ API ส่งกลับมา
                const labels = history.temperature.map(p => new Date(p.time));

                new Chart(ctx, {
                    type: 'line',
                    data: {
                        labels: labels,
                        datasets: [
                            { label: 'Temp (°C)', data: history.temperature.map(p => p.value), borderColor: '#f87171', tension: 0.2, pointRadius: 0 },
                            { label: 'Vib (g)', data: history.vibration.map(p => p.value), borderColor: '#facc15', tension: 0.2, pointRadius: 0 },
                            { label: 'Power (W)', data: history.power.map(p => p.value), borderColor: '#a78bfa', tension: 0.2, pointRadius: 0 },
                            { label: 'Energy (Wh)', data: history.energy.map(p => p.value), borderColor: '#f472b6', tension: 0.2, pointRadius: 0 },
                            { label: 'Volt (V)', data: history.voltage.map(p => p.value), borderColor: '#60a5fa', tension: 0.2, pointRadius: 0 },
                            { label: 'Curr (A)', data: history.current.map(p => p.value), borderColor: '#34d399', tension: 0.2, pointRadius: 0 }
                        ]
                    },
                    options: {
                        responsive: true,
                        scales: {
                            x: { 
                                type: 'time', 
                                time: { unit: 'hour', displayFormats: { hour: 'HH:mm' } },
                                title: { display: true, text: 'เวลา' }
                            },
                            y: { beginAtZero: true }
                        }
                    }
                });
            }
        });
    } catch (err) {
        console.error("History Error:", err);
        Swal.fire('ผิดพลาด', 'ไม่สามารถดึงข้อมูลย้อนหลังได้', 'error');
    }
}