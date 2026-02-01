$(document).ready(function() {
    const API_BASE = "https://factory-monitoring.onrender.com";
    const machineId = "<?= $machine['machine_id'] ?>";

    async function updateDetailStatus() {
        try {
            const res = await fetch(`${API_BASE}/api/latest/${machineId}`);
            if (!res.ok) throw new Error('Network response was not ok');
            const data = await res.json();

            const temp = data.temperature || 0;
            const vib = data.vibration || 0;
            const cur = data.current || 0;
            const volt = data.voltage || 0;
            const power = data.power || 0;

            const isDanger = (temp >= 35 || vib >= 15 || cur >= 8 || volt >= 300 || power >= 20);
            const isWarning = (temp >= 34 || vib >= 5 || cur >= 5 || volt >= 250 || power >= 15);
            const isRunning = (power > 0.5); 

            let statusText = "";
            let statusColor = ""; // ตัวแปรเก็บสีตัวหนังสือ

            if (isDanger || isWarning) {
                statusText = "ผิดปกติ";
                statusColor = "#ffc107"; // สีเหลือง
            } else if (isRunning) {
                statusText = "กำลังทำงาน";
                statusColor = "#28a745"; // สีเขียว
            } else {
                statusText = "หยุดทำงาน";
                statusColor = "#dc3545"; // สีแดง
            }

            // อัปเดตเฉพาะตัวหนังสือและสี
            $('#detail-status-display')
                .text(statusText)
                .css('color', statusColor);

        } catch (error) {
            console.error("Error fetching status:", error);
            $('#detail-status-display')
                .text("เชื่อมต่อผิดพลาด")
                .css('color', '#6c757d');
        }
    }

    updateDetailStatus();
    setInterval(updateDetailStatus, 5000);
});

function viewRepairDetail(repairData) {
            document.getElementById('modalReporter').textContent = repairData.reporter || '-';
            document.getElementById('modalPosition').textContent = repairData.position || '-';
            document.getElementById('modalType').textContent = repairData.type || '-';
            document.getElementById('modalReportTime').textContent = new Date(repairData.report_time).toLocaleDateString('th-TH', {
                year: 'numeric',
                month: 'long',
                day: 'numeric',
                hour: '2-digit',
                minute: '2-digit'
            });
            document.getElementById('modalDetail').textContent = repairData.detail || '-';
            document.getElementById('modalStatus').textContent = repairData.status || '-';

            if (repairData.updated_at) {
                document.getElementById('modalUpdatedTime').textContent = new Date(repairData.updated_at).toLocaleDateString('th-TH', {
                    year: 'numeric',
                    month: 'long',
                    day: 'numeric',
                    hour: '2-digit',
                    minute: '2-digit'
                });
            } else {
                document.getElementById('modalUpdatedTime').textContent = '-';
            }

            if (repairData.comment) {
                document.getElementById('commentSection').style.display = 'block';
                document.getElementById('modalComment').textContent = repairData.comment;
            } else {
                document.getElementById('commentSection').style.display = 'none';
            }
        }