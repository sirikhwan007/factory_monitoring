$(document).ready(function() {
    const API_BASE = "https://factory-monitoring.onrender.com";
    let currentThresholds = null;

    async function loadThresholdsForDetail() {

        if (typeof MACHINE_MAC === 'undefined' || !MACHINE_MAC) return;
        
        try {
            const res = await fetch(`${API_BASE}/api/thresholds/${MACHINE_MAC}`);
            if (res.ok) {
                currentThresholds = await res.json();
            }
        } catch (err) {
            console.error("Error loading thresholds:", err);
        }
    }

    loadThresholdsForDetail();
    setInterval(loadThresholdsForDetail, 60000);

    async function updateDetailStatus() {
        if (typeof MACHINE_MAC === 'undefined' || !MACHINE_MAC) return;

        try {
            const res = await fetch(`${API_BASE}/api/latest/${MACHINE_MAC}`);
            if (!res.ok) throw new Error('Network response was not ok');
            const data = await res.json();

            if (!data || Object.keys(data).length === 0) throw new Error("No data");

            if (!currentThresholds) {
                $('#detail-status-display')
                    .html('<span class="text-secondary">กำลังโหลดเกณฑ์...</span>');
                return;
            }

            const t = currentThresholds;
            const temp = Number(data.temperature) || 0;
            const vib = Number(data.vibration) || 0;
            const cur = Number(data.current) || 0;
            const volt = Number(data.voltage) || 0;
            const pow = Number(data.power) || 0;
            const energy = Number(data.energy) || 0;

            const isDanger = (
                temp >= t.danger_temp || 
                vib >= t.danger_vib || 
                cur >= t.danger_cur || 
                volt >= t.danger_volt || 
                pow >= t.danger_power ||
                energy >= t.danger_energy
            );
            
            const isWarning = (
                temp >= t.warn_temp || 
                vib >= t.warn_vib || 
                cur >= t.warn_cur || 
                volt >= t.warn_volt || 
                pow >= t.warn_power ||
                energy >= t.warn_energy
            );

            const isRunning = (pow > 0.5);

            let statusText = "";
            let statusColor = "";

            if (isDanger) {
                statusText = "อันตราย";
                statusColor = "#dc3545"; 
            } else if (isWarning) {
                statusText = "ผิดปกติ";
                statusColor = "#ffc107"; 
            } else if (isRunning) {
                statusText = "กำลังทำงาน";
                statusColor = "#198754"; 
            } else {
                statusText = "หยุดทำงาน";
                statusColor = "#6c757d"; 
            }

            $('#detail-status-display')
                .text(statusText)
                .css('color', statusColor);

        } catch (error) {
            $('#detail-status-display')
                .text("Offline")
                .css('color', '#bbbbbb');
        }
    }

    // เรียกทำงานทันทีและวนซ้ำทุกๆ 5 วินาที
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