$(document).ready(function () {

  /* ===============================
     Toggle Sub Menu (เฉพาะเมนูที่มี submenu)
  =============================== */
  $(".sb-ul li.has-sub > a").click(function (e) {
    e.preventDefault(); // ❗ กันเฉพาะเมนูที่มี submenu

    const parent = $(this).parent();

    $(".sb-sub-ul").not(parent.find(".sb-sub-ul")).slideUp();
    $(".chev-pos").not(parent.find(".chev-pos")).removeClass("chev-rotate");

    parent.find(".sb-sub-ul").slideToggle();
    parent.find(".chev-pos").toggleClass("chev-rotate");
  });

  /* ===============================
     Active Menu
  =============================== */
  $(".sb-ul li a").click(function () {
    $(".sb-ul li a").removeClass("sb-ul-active");
    $(this).addClass("sb-ul-active");
  });

  /* ===============================
     Hamburger (Responsive)
  =============================== */
  $(".btn-hamburger").click(function () {
    $(".sidebar").toggleClass("sidebar-active");
  });

  /* ===============================
     Auto show sidebar on desktop
  =============================== */
  $(window).on("resize", function () {
    if ($(window).width() > 768) {
      $(".sidebar").removeClass("sidebar-active");
    }
  });

});
