$(document).ready(function() {

            function loadStatus() {
                $.ajax({
                    url: "/api/get_all_machine_status.php",
                    method: "GET",
                    dataType: "json",
                    success: function(res) {
                        $("#activeCount").text(res.active);
                        $("#errorCount").text(res.error);
                        $("#stopCount").text(res.stop);
                    }
                });
            }

            loadStatus();
            setInterval(loadStatus, 5000);
        });

        $(document).ready(function() {
            let alertCounter = 0;
            let currentIssue = 'all';

            function loadStatus() {
                $.ajax({
                    url: "/api/get_all_machine_status.php",
                    method: "GET",
                    dataType: "json",
                    success: function(res) {
                        // อัปเดตตัวเลขให้ตรงกับ ID ของแต่ละปุ่ม
                        $("#activeCount").text(res.active);
                        $("#errorCount").text(res.error);
                        $("#dangerCount").text(res.danger); // ตรวจสอบว่า API ส่งค่า res.danger มาให้
                        $("#stopCount").text(res.stop);

                        // ตรวจสอบลำดับความสำคัญในการแจ้งเตือน
                        if (parseInt(res.stop) > 0) {
                            currentIssue = 'หยุดทำงาน';
                            alertCounter += 5;
                        } else if (parseInt(res.danger) > 0) {
                            currentIssue = 'อันตราย';
                            alertCounter += 5;
                        } else if (parseInt(res.error) > 0) {
                            currentIssue = 'ผิดปกติ';
                            alertCounter += 5;
                        } else {
                            alertCounter = 0;
                            currentIssue = 'all';
                            resetBell();
                        }

                        if (alertCounter >= 10) {
                            triggerBell();
                        }
                    },
                    error: function() {
                        console.error("ไม่สามารถดึงข้อมูลสถานะเครื่องจักรได้");
                    }
                });
            }

            function triggerBell() {
                $("#notification-bell i").addClass("ring-active");
                $("#alert-badge").removeClass("d-none");
            }

            function resetBell() {
                $("#notification-bell i").removeClass("ring-active");
                $("#alert-badge").addClass("d-none");
            }

            $("#notification-bell").on("click", function() {
                window.location.href = "/machine_list/machine.php?status=" + encodeURIComponent(currentIssue);
            });

            // เริ่มทำงาน
            loadStatus();
            setInterval(loadStatus, 5000);
        });