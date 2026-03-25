$(document).ready(function () {
  // เมื่อคลิกปุ่ม Hamburger
  $(".btn-hamburger").on("click", function () {
    $(".sidebar-wrapper").toggleClass("active");
    $(".sidebar-overlay").toggleClass("active");
  });

  // เมื่อคลิกที่ Overlay ให้ปิด Sidebar
  $(".sidebar-overlay").on("click", function () {
    $(".sidebar-wrapper").removeClass("active");
    $(this).removeClass("active");
  });
});