/**
 * การจัดการสถานะและการค้นหาเครื่องจักร
 */

const API_BASE = "https://factory-monitoring.onrender.com";

// ฟังก์ชันสำหรับค้นหาเครื่องจักร
function initSearch() {
    const searchInput = document.getElementById('searchInput');
    if (!searchInput) return;

    searchInput.addEventListener('input', function() {
        const keyword = this.value.toLowerCase();
        const cards = document.querySelectorAll('.machine-card');

        cards.forEach(card => {
            const name = card.querySelector('.machine-name').textContent.toLowerCase();
            const id = card.querySelector('.machine-id').textContent.toLowerCase();
            const status = card.querySelector('.machine-status').textContent.toLowerCase();
            const location = card.querySelector('.machine-location').textContent.toLowerCase();

            if (name.includes(keyword) || id.includes(keyword) || 
                status.includes(keyword) || location.includes(keyword)) {
                card.style.display = "block";
            } else {
                card.style.display = "none";
            }
        });
    });
}

// ฟังก์ชันอัปเดตสถานะเครื่องจักรจาก API
async function updateMachineStatus(machineId) {
    try {
        const res = await fetch(`${API_BASE}/api/latest/${machineId}`);
        const data = await res.json();

        const temp = data.temperature || 0;
        const vib = data.vibration || 0;
        const cur = data.current || 0;
        const volt = data.voltage || 0;
        const power = data.power || 0;

        const isDanger = (temp >= 35 || vib >= 15 || cur >= 8 || volt >= 300 || power >= 20);
        const isWarning = (temp >= 34 || vib >= 5 || cur >= 5 || volt >= 250 || power >= 15);
        const isRunning = (power > 0.5); 

        const statusElement = document.getElementById(`status-${machineId}`);
        if (!statusElement) return;

        let statusText = "";
        let color = "";

        if (isDanger) {
            statusText = "ผิดปกติ (อันตราย)";
            color = "#dc3545";
        } else if (isWarning) {
            statusText = "ผิดปกติ (เฝ้าระวัง)";
            color = "#ffc107";
        } else if (isRunning) {
            statusText = "กำลังทำงานปกติ";
            color = "#28a745";
        } else {
            statusText = "หยุดทำงาน";
            color = "#6c757d";
        }

        statusElement.innerHTML = `สถานะ: <span style="font-weight: bold;">${statusText}</span>`;
        statusElement.style.color = color;

    } catch (error) {
        console.error(`Error fetching machine ${machineId}:`, error);
        const statusElement = document.getElementById(`status-${machineId}`);
        if (statusElement) {
            statusElement.textContent = "สถานะ: ไม่พบข้อมูล";
            statusElement.style.color = "#6c757d";
        }
    }
}

// เริ่มต้นทำงานเมื่อโหลด DOM เสร็จ
document.addEventListener("DOMContentLoaded", () => {
    initSearch();

    const machineCards = document.querySelectorAll(".machine-card");
    machineCards.forEach(card => {
        const idText = card.querySelector(".machine-id").textContent;
        const machineId = idText.replace("ID:", "").trim();
        
        // อัปเดตครั้งแรกทันที
        updateMachineStatus(machineId);

        // ตั้งเวลาอัปเดตทุก 5 วินาที
        setInterval(() => updateMachineStatus(machineId), 5000);
    });
});