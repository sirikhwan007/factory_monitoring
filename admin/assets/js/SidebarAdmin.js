$(document).ready(function () {

  $(".btn-hamburger").on("click", function () {
    $(".sidebar-wrapper").toggleClass("active");
    $(".sidebar-overlay").toggleClass("active");
  });

  $(".sidebar-overlay").on("click", function () {
    $(".sidebar-wrapper").removeClass("active");
    $(this).removeClass("active");
  });
});