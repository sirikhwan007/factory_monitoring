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
    let currentIssue = 'all';

    function loadStatus() {
        $.ajax({
            url: "/api/get_all_machine_status.php",
            method: "GET",
            dataType: "json",
            success: function(res) {
                $("#activeCount").text(res.active);
                $("#errorCount").text(res.error);
                $("#dangerCount").text(res.danger); 
                $("#stopCount").text(res.stop);

                let errorCount = parseInt(res.error) || 0;
                let dangerCount = parseInt(res.danger) || 0;
                let stopCount = parseInt(res.stop) || 0; 

                let totalProblems = errorCount + dangerCount + stopCount;

                if (totalProblems > 0) {

                    $("#notification-bell").attr("title", "เครื่องจักรมีปัญหา " + totalProblems + " รายการ");
                    $("#alert-badge").text(totalProblems).removeClass("d-none");
                    $("#notification-bell i").addClass("ring-active");

                    if (dangerCount > 0) {
                        currentIssue = 'อันตราย';
                    } else if (errorCount > 0) {
                        currentIssue = 'ผิดปกติ';
                    } else {
                        currentIssue = 'หยุดทำงาน';
                    }
                } else {

                    $("#notification-bell").attr("title", "ไม่มีเครื่องจักรที่พบปัญหา");
                    $("#alert-badge").addClass("d-none");
                    $("#notification-bell i").removeClass("ring-active");
                    currentIssue = 'all';
                }
            },
            error: function() {
                console.error("ไม่สามารถดึงข้อมูลสถานะเครื่องจักรได้");
            }
        });
    }

    $("#notification-bell").on("click", function() {
        window.location.href = "/machine_list/machine.php?status=" + encodeURIComponent(currentIssue);
    });

    loadStatus();
    setInterval(loadStatus, 5000);
});