function loadDashboardStats() {
    fetch("fetch_dashboard_stats.php")
        .then(res => res.json())
        .then(data => {
            document.getElementById("dashboardStats").innerHTML = `
        <div class="col-md-3">
            <div class="card text-center shadow-sm">
                <div class="card-body">
                    <h5 class="card-title">My Pets</h5>
                    <p class="display-6">${data.pets}</p>
                </div>
            </div>
        </div>
        <div class="col-md-3">
            <div class="card text-center shadow-sm">
                <div class="card-body">
                    <h5 class="card-title">Upcoming</h5>
                    <p class="display-6 text-primary">${data.upcoming}</p>
                </div>
            </div>
        </div>
        <div class="col-md-3">
            <div class="card text-center shadow-sm">
                <div class="card-body">
                    <h5 class="card-title">Completed</h5>
                    <p class="display-6 text-success">${data.completed}</p>
                </div>
            </div>
        </div>
        <div class="col-md-3">
            <div class="card text-center shadow-sm">
                <div class="card-body">
                    <h5 class="card-title">Records</h5>
                    <p class="display-6 text-secondary">${data.records}</p>
                </div>
            </div>
        </div>
    `;
        });
}

// Load once
loadDashboardStats();
// Refresh every 10s
setInterval(loadDashboardStats, 10000);