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

function filterStatus(status) {
    const cards = document.querySelectorAll('.machine-card');
    
    // จัดการเรื่องความสวยงามของปุ่ม (Active class)
    const buttons = document.querySelectorAll('.status-filter .btn');
    buttons.forEach(btn => {
        if (btn.textContent.includes(status) || (status === 'all' && btn.textContent.includes('ทั้งหมด'))) {
            btn.classList.add('btn-primary', 'text-white');
            btn.classList.remove('btn-outline-primary', 'btn-outline-success', 'btn-outline-warning', 'btn-outline-secondary');
        } else {
            btn.classList.remove('btn-primary', 'text-white');
        }
    });

    cards.forEach(card => {
        const machineStatus = card.getAttribute('data-status-text') || "";
        
        if (status === 'all') {
            card.style.display = "block";
        } else if (machineStatus.includes(status)) {
            card.style.display = "block";
        } else {
            card.style.display = "none";
        }
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

        const card = document.querySelector(`.machine-card[onclick*="id=${machineId}"]`);
        const statusElement = document.getElementById(`status-${machineId}`);
        
        let statusText = "";
        let color = "";

        if (isDanger || isWarning) {
            statusText = "ผิดปกติ"; // ยุบรวมเพื่อให้ Filter ง่ายขึ้น
            color = (isDanger) ? "#dc3545" : "#ffc107";
        } else if (isRunning) {
            statusText = "กำลังทำงานปกติ";
            color = "#28a745";
        } else {
            statusText = "หยุดทำงาน";
            color = "#6c757d";
        }

        // เก็บสถานะไว้ที่ตัว Card เพื่อใช้ในการกรอง (Filter)
        if (card) {
            card.setAttribute('data-status-text', statusText);
        }

        if (statusElement) {
            statusElement.innerHTML = `สถานะ: <span style="font-weight: bold;">${statusText}</span>`;
            statusElement.style.color = color;
        }

    } catch (error) {
        console.error("Error:", error);
    }
}


function filterStatus(status, element) {
    // 1. สลับไฮไลท์สีปุ่ม (Active Class)
    const buttons = document.querySelectorAll('.btn-filter');
    buttons.forEach(btn => btn.classList.remove('active'));
    element.classList.add('active');

    // 2. กรองการ์ดเครื่องจักร
    const cards = document.querySelectorAll('.machine-card');
    cards.forEach(card => {
        // ดึงข้อความจากส่วนสถานะของการ์ดนั้นๆ
        const machineStatusText = card.querySelector('.machine-status').textContent || "";
        
        if (status === 'all') {
            card.style.display = "block";
        } else if (machineStatusText.includes(status)) {
            card.style.display = "block";
        } else {
            card.style.display = "none";
        }
    });
}

$(document).ready(function() {
    // 1. ดึงค่า status จาก URL
    const urlParams = new URLSearchParams(window.location.search);
    const statusFilter = urlParams.get('status');

    if (statusFilter) {
        // 2. ค้นหาปุ่มที่มีข้อความตรงกับ status ที่ส่งมา หรือส่งค่าเข้าฟังก์ชันกรอง
        let targetButton;
        
        if (statusFilter === 'all') {
            targetButton = document.querySelector(".btn-filter.btn-all");
            filterStatus('all', targetButton);
        } else {
            // ค้นหาปุ่มตามข้อความในปุ่ม (เช่น "กำลังทำงาน", "ผิดปกติ")
            $(".btn-filter").each(function() {
                if ($(this).text().trim().includes(statusFilter)) {
                    targetButton = this;
                    filterStatus(statusFilter, targetButton);
                }
            });
        }
    }
});

// ฟังก์ชัน filterStatus เดิมที่คุณมี (ปรับปรุงให้รับ element เพื่อเปลี่ยนสีปุ่ม)
function filterStatus(status, element) {
    if(!element) return;

    // เปลี่ยนสถานะปุ่ม Active
    $(".btn-filter").removeClass("active");
    $(element).addClass("active");

    // กรองการ์ดเครื่องจักร
    const cards = document.querySelectorAll('.machine-card');
    cards.forEach(card => {
        const machineStatusText = card.querySelector('.machine-status').textContent || "";
        
        if (status === 'all') {
            card.style.display = "block";
        } else if (machineStatusText.includes(status)) {
            card.style.display = "block";
        } else {
            card.style.display = "none";
        }
    });
}

// ในไฟล์ machine.js
document.addEventListener("DOMContentLoaded", () => {
    // 1. ตรวจสอบค่า status จาก URL Query String
    const urlParams = new URLSearchParams(window.location.search);
    const statusFilter = urlParams.get('status');

    if (statusFilter) {
        // 2. ค้นหาปุ่มที่มีข้อความตรงกับค่าใน URL
        const buttons = document.querySelectorAll('.btn-filter');
        let targetBtn = null;

        buttons.forEach(btn => {
            // เช็คว่าข้อความในปุ่มตรงกับ status ที่ส่งมาหรือไม่
            if (statusFilter === 'all' && btn.textContent.includes('ทั้งหมด')) {
                targetBtn = btn;
            } else if (btn.textContent.trim() === statusFilter) {
                targetBtn = btn;
            }
        });

        // 3. ถ้าเจอตัวปุ่ม ให้สั่งคลิกหรือเรียกฟังก์ชันกรอง
        if (targetBtn) {
            filterStatus(statusFilter, targetBtn);
        }
    }
});

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