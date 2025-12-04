document.getElementById('toggleSidebar').addEventListener('click', function () {
    document.getElementById('sidebarMenu').classList.toggle('active');
});

if (window.location.search.includes("msg=")) {
    history.replaceState({}, document.title, window.location.pathname);
}