<div class="container py-5">
    <div class="d-flex justify-content-between align-items-center mb-4">
        <h2 class="fw-bold text-dark">Doctor Dashboard</h2>
        <span class="text-muted">Updated every 15s</span>
    </div>

    <div class="row g-4" id="dashboard-widgets">
        <!-- Realtime doctor cards -->
    </div>

    <div class="containers mt-4">
        <div class="calendar">
            <header>
                <button id="prev">&#8592;</button>
                <h2 id="monthYear"></h2>
                <button id="next">&#8594;</button>
            </header>
            <div class="days" id="calendarDays"></div>
        </div>

        <div class="appointments">
            <h3 id="selectedDate">Select a date</h3>
            <div id="appointmentList"></div>
        </div>
    </div>
</div>

<script>
    async function loadDashboard() {
        let res = await fetch("doctor_dashboard.php");
        let data = await res.json();

        document.getElementById("dashboard-widgets").innerHTML = `
    <div class="row g-4" id="dashboard-widgets">
  <!-- Example card -->
  <div class="col-md-3">
    <div class="card shadow-sm border-0 text-center dashboard-card">
      <div class="card-body">
        <div class="icon-wrapper bg-warning bg-opacity-10 text-warning mx-auto mb-3">
          <i class="bi bi-hourglass-split fs-3"></i>
        </div>
        <h6 class="text-muted">Pending Appointments</h6>
        <h4 class="fw-bold mb-0">${data.pending || 0}</h4>
      </div>
    </div>
  </div>

  <!-- Repeat for other cards -->
  <!-- Today’s Appointments -->
  <div class="col-md-3">
    <div class="card shadow-sm border-0 text-center dashboard-card">
      <div class="card-body">
        <div class="icon-wrapper bg-primary bg-opacity-10 text-primary mx-auto mb-3">
          <i class="bi bi-calendar-check fs-3"></i>
        </div>
        <h6 class="text-muted">Today’s Appointments</h6>
        <h4 class="fw-bold mb-0">${data.today || 0}</h4>
      </div>
    </div>
  </div>

  <!-- Completed -->
  <div class="col-md-3">
    <div class="card shadow-sm border-0 text-center dashboard-card">
      <div class="card-body">
        <div class="icon-wrapper bg-success bg-opacity-10 text-success mx-auto mb-3">
          <i class="bi bi-check-circle fs-3"></i>
        </div>
        <h6 class="text-muted">Completed</h6>
        <h4 class="fw-bold mb-0">${data.completed || 0}</h4>
      </div>
    </div>
  </div>

  <!-- Pets Handled -->
  <div class="col-md-3">
    <div class="card shadow-sm border-0 text-center dashboard-card">
      <div class="card-body">
        <div class="icon-wrapper bg-info bg-opacity-10 text-info mx-auto mb-3">
          <i class="bi bi-heart-pulse fs-3"></i>
        </div>
        <h6 class="text-muted">Pets Handled</h6>
        <h4 class="fw-bold mb-0">${data.pets || 0}</h4>
      </div>
    </div>
  </div>

  <!-- Records Added -->
  <div class="col-md-3">
    <div class="card shadow-sm border-0 text-center dashboard-card">
      <div class="card-body">
        <div class="icon-wrapper bg-secondary bg-opacity-10 text-secondary mx-auto mb-3">
          <i class="bi bi-journal-medical fs-3"></i>
        </div>
        <h6 class="text-muted">Records Added</h6>
        <h4 class="fw-bold mb-0">${data.records || 0}</h4>
      </div>
    </div>
  </div>
</div>
<style>
  .icon-wrapper {
    width: 60px;
    height: 60px;
    border-radius: 50%;
    display: flex;
    align-items: center;
    justify-content: center;
  }

  .dashboard-card {
    transition: transform 0.2s ease, box-shadow 0.2s ease;
  }

  .dashboard-card:hover {
    transform: translateY(-5px);
    box-shadow: 0 6px 20px rgba(0, 0, 0, 0.1);
  }
</style>


  `;
    }
    loadDashboard();
    setInterval(loadDashboard, 15000);

</script>