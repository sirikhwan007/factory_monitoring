document.addEventListener("DOMContentLoaded", () => {

    const sidebar = document.querySelector(".sidebar-operator");
    const btn = document.querySelector(".btn-hamburger");

    if (btn) {
        btn.addEventListener("click", () => {
            sidebar.classList.toggle("active");
        });
    }

    // active menu
    document.querySelectorAll(".op-ul a").forEach(a => {
        if (a.href === window.location.href) {
            a.style.background = "#8e44ad";
            a.style.color = "#fff";
            a.style.fontWeight = "bold";
        }
    });

});
