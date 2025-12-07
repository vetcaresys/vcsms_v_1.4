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
    <div class="col-md-3">
      <div class="card shadow-sm border-0">
        <div class="card-body d-flex align-items-center">
          <div class="me-3 bg-warning bg-opacity-10 text-warning rounded-circle d-flex align-items-center justify-content-center" style="width:50px;height:50px;">
            <i class="bi bi-hourglass-split fs-4"></i>
          </div>
          <div>
            <h6 class="mb-1 text-muted">Pending Appointments</h6>
            <h4 class="mb-0">${data.pending || 0}</h4>
          </div>
        </div>
      </div>
    </div>

    <div class="col-md-3">
      <div class="card shadow-sm border-0">
        <div class="card-body d-flex align-items-center">
          <div class="me-3 bg-primary bg-opacity-10 text-primary rounded-circle d-flex align-items-center justify-content-center" style="width:50px;height:50px;">
            <i class="bi bi-calendar-check fs-4"></i>
          </div>
          <div>
            <h6 class="mb-1 text-muted">Today’s Appointments</h6>
            <h4 class="mb-0">${data.today || 0}</h4>
          </div>
        </div>
      </div>
    </div>

    <div class="col-md-3">
      <div class="card shadow-sm border-0">
        <div class="card-body d-flex align-items-center">
          <div class="me-3 bg-success bg-opacity-10 text-success rounded-circle d-flex align-items-center justify-content-center" style="width:50px;height:50px;">
            <i class="bi bi-check-circle fs-4"></i>
          </div>
          <div>
            <h6 class="mb-1 text-muted">Completed</h6>
            <h4 class="mb-0">${data.completed || 0}</h4>
          </div>
        </div>
      </div>
    </div>

    <div class="col-md-3">
      <div class="card shadow-sm border-0">
        <div class="card-body d-flex align-items-center">
          <div class="me-3 bg-info bg-opacity-10 text-info rounded-circle d-flex align-items-center justify-content-center" style="width:50px;height:50px;">
            <i class="bi bi-heart-pulse fs-4"></i>
          </div>
          <div>
            <h6 class="mb-1 text-muted">Pets Handled</h6>
            <h4 class="mb-0">${data.pets || 0}</h4>
          </div>
        </div>
      </div>
    </div>

    <div class="col-md-3">
      <div class="card shadow-sm border-0">
        <div class="card-body d-flex align-items-center">
          <div class="me-3 bg-secondary bg-opacity-10 text-secondary rounded-circle d-flex align-items-center justify-content-center" style="width:50px;height:50px;">
            <i class="bi bi-journal-medical fs-4"></i>
          </div>
          <div>
            <h6 class="mb-1 text-muted">Records Added</h6>
            <h4 class="mb-0">${data.records || 0}</h4>
          </div>
        </div>
      </div>
    </div>
  `;
    }
    loadDashboard();
    setInterval(loadDashboard, 15000);

</script>

<!-- Alert for the Login Successfully -->
<?php if (isset($_SESSION['success'])): ?>
    <script>
        Swal.fire({
            icon: 'success',
            title: 'Login Successful',
            text: 'Welcome back!',
            timer: 1500,
            showConfirmButton: false
        });
    </script>
    <?php unset($_SESSION['success']); endif; ?>