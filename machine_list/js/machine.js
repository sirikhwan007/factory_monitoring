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
async function updateMachineStatus(cardElement) {
    const macAddress = cardElement.getAttribute('data-mac-address');
    const machineIdText = cardElement.querySelector('.machine-id').textContent;
    const machineId = machineIdText.replace("ID:", "").trim();
    const statusElement = document.getElementById(`status-${machineId}`);

    if (!macAddress) return;

    try {
        const res = await fetch(`${API_BASE}/api/latest/${macAddress}`);
        if (!res.ok) throw new Error('Network error');
        const data = await res.json();

        if (!data || Object.keys(data).length === 0) return;

        // --- ตรรกะการตัดสินใจ (Copy มาจาก machine_detail.js) ---
        const temp  = Number(data.temperature) || 0;
        const vib   = Number(data.vibration) || 0;
        const cur   = Number(data.current) || 0;
        const volt  = Number(data.voltage) || 0;
        const power = Number(data.power) || 0;

        // เกณฑ์ตามมาตรฐานหน้า Detail/Dashboard
        const isDanger = (temp >= 35 || vib >= 15 || cur >= 8 || volt >= 300 || power >= 20);
        const isWarning = (temp >= 34 || vib >= 5 || cur >= 5 || volt >= 250 || power >= 15);
        const isRunning = (power > 0.5); 

        let statusText = "";
        let color = "";

        if (isDanger) {
            statusText = "อันตราย"; 
            color = "#dc3545"; 
        } else if (isWarning) {
            statusText = "ผิดปกติ";
            color = "#ffc107";
        } else if (isRunning) {
            statusText = "กำลังทำงาน";
            color = "#28a745";
        } else {
            statusText = "หยุดทำงาน";
            color = "#6c757d";
        }


        // เก็บสถานะไว้ที่ตัว Card เพื่อใช้ในการกรอง (Filter)
        cardElement.dataset.statusText = statusText;

        if (statusElement) {
            statusElement.innerHTML = `สถานะ: <b>${statusText}</b>`;
            statusElement.style.color = color;
        }

    } catch (error) {
        console.error(`Error fetching for ${macAddress}:`, error);
        if (statusElement) {
            statusElement.innerText = "เชื่อมต่อผิดพลาด";
            statusElement.style.color = "#bbbbbb";
        }
    }
}

document.addEventListener("DOMContentLoaded", () => {
    initSearch(); // ฟังก์ชันค้นหาเดิมของคุณ

    const machineCards = document.querySelectorAll(".machine-card");
    
    machineCards.forEach(card => {
        // อัปเดตครั้งแรกทันที
        updateMachineStatus(card);

        // ตั้งเวลาอัปเดตทุก 5 วินาที
        setInterval(() => updateMachineStatus(card), 5000);
    });
});
function filterStatus(status, element) {
    if (element) {
        document.querySelectorAll(".btn-filter").forEach(b => b.classList.remove("active"));
        element.classList.add("active");
    }

    document.querySelectorAll(".machine-card").forEach(card => {
        const machineStatus = card.dataset.statusText || "";

        if (status === "all" || machineStatus.includes(status)) {
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
// เริ่มต้นทำงานเมื่อโหลด DOM เสร็จ
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
function handleUrlFilter() {
    const urlParams = new URLSearchParams(window.location.search);
    const statusFilter = urlParams.get('status');

    if (statusFilter) {
        // ค้นหาปุ่มที่มีข้อความตรงกับ status ที่ส่งมา
        const buttons = document.querySelectorAll('.btn-filter');
        let targetBtn = null;

        buttons.forEach(btn => {
            if (statusFilter === 'all' && btn.classList.contains('btn-all')) {
                targetBtn = btn;
            } else if (btn.textContent.trim().includes(statusFilter)) {
                targetBtn = btn;
            }
        });

        if (targetBtn) {
            // เรียกฟังก์ชันกรองข้อมูล
            filterStatus(statusFilter, targetBtn);
        }
    }
}

// เริ่มต้นทำงานเมื่อโหลด DOM เสร็จ
document.addEventListener("DOMContentLoaded", () => {
    initSearch();

    const machineCards = document.querySelectorAll(".machine-card");
    
    // สร้าง Promise array เพื่อรอให้การเช็คสถานะครั้งแรกเสร็จสิ้นก่อนค่อย Filter
    const statusPromises = Array.from(machineCards).map(card => {
        return updateMachineStatus(card); 
    });

    // เมื่อทุก Card อัปเดตสถานะรอบแรกเสร็จแล้ว (มี dataset.statusText แล้ว)
    Promise.all(statusPromises).then(() => {
        handleUrlFilter(); // ค่อยสั่ง Filter ตาม URL
    });

    // ตั้งเวลาอัปเดตต่อเนื่องทุก 5 วินาทีตามปกติ
    machineCards.forEach(card => {
        setInterval(() => updateMachineStatus(card), 5000);
    });
});