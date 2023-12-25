document.addEventListener("DOMContentLoaded", function () {
    const toggleButton = document.getElementById("toggleSidebar");
    const closeSidebarButton = document.getElementById("closeSidebar");
    const sidebar = document.getElementById("sidebar");
    const content = document.getElementById("content");

    toggleButton.addEventListener("click", function () {
        sidebar.classList.toggle("open");
        if (sidebar.classList.contains("open")) {
            content.style.marginLeft = "250px";
        } else {
            content.style.marginLeft = "0";
        }
    });

    closeSidebarButton.addEventListener("click", function () {
        sidebar.classList.remove("open");
        content.style.marginLeft = "0";
    });
});
