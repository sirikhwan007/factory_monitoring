$(document).ready(function () {

  /* ===== Toggle Sidebar ===== */
  $(".btn-hamburger").click(function (e) {
    e.stopPropagation();
    $(".sidebar").toggleClass("sidebar-active");
  });

  /* ===== Close sidebar when click dashboard (mobile) ===== */
  $(".dashboard").click(function () {
    if ($(window).width() <= 768) {
      $(".sidebar").removeClass("sidebar-active");
    }
  });

  /* ===== Active Menu ===== */
  $(".sb-ul li a").click(function () {
    $(".sb-ul li a").removeClass("sb-ul-active");
    $(this).addClass("sb-ul-active");

    if ($(window).width() <= 768 && !$(this).parent().hasClass("has-sub")) {
      $(".sidebar").removeClass("sidebar-active");
    }
  });

  /* ===== Submenu Toggle ===== */
  $(".sb-ul li.has-sub > a").click(function (e) {
    e.preventDefault();
    const parent = $(this).parent();

    $(".sb-sub-ul").not(parent.find(".sb-sub-ul")).slideUp();
    parent.find(".sb-sub-ul").slideToggle();
  });

  /* ===== Reset on Desktop ===== */
  $(window).on("resize", function () {
    if ($(window).width() > 768) {
      $(".sidebar").removeClass("sidebar-active");
    }
  });

});
