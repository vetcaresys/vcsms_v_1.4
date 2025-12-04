<!-- Main Content -->
<div class="container py-5">
    <h2 class="mb-4 text-primary"><i class="bi bi-speedometer2"></i> Dashboard</h2>

    <div class="row g-4 dashboard-cards">

        <div class="col-md-3">
            <div class="dash-card">
                <div class="dash-icon bg-primary">
                    <i class="fa-solid fa-calendar-check"></i>
                </div>
                <div>
                    <p class="label">Total Appointments</p>
                    <h3 class="value"><?= $totalAppointments ?></h3>
                </div>
            </div>
        </div>

        <div class="col-md-3">
            <div class="dash-card">
                <div class="dash-icon bg-success">
                    <i class="bi bi-people-fill"></i>
                </div>
                <div>
                    <p class="label">Active Staff</p>
                    <h3 class="value"><?= $activeStaff ?></h3>
                </div>
            </div>
        </div>

        <div class="col-md-3">
            <div class="dash-card">
                <div class="dash-icon bg-danger">
                    <i class="bi bi-hospital"></i>

                </div>
                <div>
                    <p class="label">Services Offered</p>
                    <h3 class="value"><?= $servicesOffered ?></h3>
                </div>
            </div>
        </div>

        <div class="col-md-3">
            <div class="dash-card">
                <div class="dash-icon bg-warning text-dark">
                    <i class="bi bi-person-heart"></i>
                </div>
                <div>
                    <p class="label">Total Clients</p>
                    <h3 class="value"><?= $totalClients ?></h3>
                </div>
            </div>
        </div>

        <!-- Daily Income -->
        <div class="col-md-3">
            <div class="dash-card">
                <div class="dash-icon bg-info">
                    <i class="bi bi-cash-stack"></i>
                </div>
                <div>
                    <p class="label">Daily Income</p>
                    <h3 class="value">₱<?= number_format($dailyIncome, 2) ?></h3>
                </div>
            </div>
        </div>

        <!-- Weekly Income -->
        <div class="col-md-3">
            <div class="dash-card">
                <div class="dash-icon bg-secondary">
                    <i class="bi bi-calendar-week"></i>
                </div>
                <div>
                    <p class="label">Weekly Income</p>
                    <h3 class="value">₱<?= number_format($weeklyIncome, 2) ?></h3>
                </div>
            </div>
        </div>

        <!-- Monthly Income -->
        <div class="col-md-3">
            <div class="dash-card">
                <div class="dash-icon bg-dark">
                    <i class="bi bi-calendar-month"></i>
                </div>
                <div>
                    <p class="label">Monthly Income</p>
                    <h3 class="value">₱<?= number_format($monthlyIncome, 2) ?></h3>
                </div>
            </div>
        </div>

        <!-- Yearly Income -->
        <div class="col-md-3">
            <div class="dash-card">
                <div class="dash-icon bg-primary">
                    <i class="bi bi-calendar3"></i>
                </div>
                <div>
                    <p class="label">Yearly Income</p>
                    <h3 class="value">₱<?= number_format($yearlyIncome, 2) ?></h3>
                </div>
            </div>
        </div>

    </div>

    <div class="welcome-card mt-4 p-4">
        <div class="d-flex align-items-center gap-3">
            <div class="welcome-icon bg-primary text-white">
                <i class="bi bi-stars"></i>
            </div>
            <div>
                <h5 class="fw-bold mb-1">Welcome, <?= htmlspecialchars($name) ?>!</h5>
                <p class="text-muted mb-0">Navigation bar above to manage your clinic’s details, staff,
                    schedules, and services.</p>
            </div>
        </div>
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

    <div class="containers mt-4">

        <div class="container">

            <!-- MAIN BORDER WRAPPER -->
            <div class="card p-4 shadow-sm" style="border: 1px solid #dee2e6; border-radius: 12px;">

                <div class="d-flex justify-content-between align-items-center mb-4">
                    <h3 class="mb-0 text-primary">
                        <i class="bi bi-file-earmark-text"></i> Income Report (Full)
                    </h3>
                </div>

                <!-- Filters -->
                <div class="card p-3 mb-4 no-print" style="border: 1px solid #d0d0d0;">
                    <div class="row g-2 align-items-end">
                        <div class="col-md-3">
                            <label class="form-label">From</label>
                            <input type="date" id="startDate" class="form-control" value="<?= $start_default ?>">
                        </div>
                        <div class="col-md-3">
                            <label class="form-label">To</label>
                            <input type="date" id="endDate" class="form-control" value="<?= $end_default ?>">
                        </div>
                        <div class="col-md-3">
                            <label class="form-label">Quick Range</label>
                            <select id="quickRange" class="form-select">
                                <option value="">Custom</option>
                                <option value="7">Last 7 days</option>
                                <option value="30" selected>Last 30 days</option>
                                <option value="90">Last 90 days</option>
                                <option value="365">Last 365 days</option>
                            </select>
                        </div>
                        <div class="col-md-3 d-flex gap-2">
                            <button id="applyBtn" class="btn btn-primary w-100">Apply</button>
                        </div>
                    </div>
                </div>

                <!-- Summary -->
                <div class="row g-3 mb-4">
                    <div class="col-md-4">
                        <div class="card p-3 border-primary border-2">
                            <small class="text-muted">Total Income</small>
                            <div class="d-flex justify-content-between align-items-center">
                                <h4 class="mb-0" id="totalIncome">₱0.00</h4>
                                <span class="badge bg-success summary-badge" id="totalCount">0 items</span>
                            </div>
                        </div>
                    </div>
                    <div class="col-md-4">
                        <div class="card p-3 border-primary border-2">
                            <small class="text-muted">Highest Day</small>
                            <div class="d-flex justify-content-between align-items-center">
                                <h5 class="mb-0" id="highestDay">—</h5>
                                <span class="text-muted" id="highestAmount">₱0.00</span>
                            </div>
                        </div>
                    </div>
                    <div class="col-md-4">
                        <div class="card p-3 border-primary border-2">
                            <small class="text-muted">Average per Day</small>
                            <div class="d-flex justify-content-between align-items-center">
                                <h5 class="mb-0" id="averageDay">₱0.00</h5>
                                <span class="text-muted" id="daysCount">0 days</span>
                            </div>
                        </div>
                    </div>
                </div>

                <!-- Table + Yearly Chart -->
                <div class="row g-3">
                    <div class="no-print">
                        <button id="printBtn" class="btn btn-outline-secondary btn-sm me-2">
                            <i class="bi bi-printer"></i> Print
                        </button>
                        <button id="exportPdfBtn" class="btn btn-outline-primary btn-sm">
                            <i class="bi bi-file-earmark-pdf"></i> Export PDF
                        </button>
                    </div>
                    <div id="exportSection" class="row g-3">

                        <!-- Table -->
                        <div class="col-lg-8">
                            <div id="printSection">
                                <div class="card p-3 border-1" style="border:1px solid #dee2e6;">
                                    <div class="d-flex justify-content-between align-items-center mb-2">
                                        <h5 class="mb-0">Detailed Income Table</h5>
                                    </div>

                                    <div class="table-wrap">
                                        <table class="table table-hover table-bordered">
                                            <thead class="table-light sticky-top">
                                                <tr>
                                                    <th>Date Used</th>
                                                    <th>Record ID</th>
                                                    <th>Pet</th>
                                                    <th>Owner</th>
                                                    <th>Item</th>
                                                    <th>Quantity</th>
                                                    <th>Unit Price</th>
                                                    <th>Income (₱)</th>
                                                    <th>Staff</th>
                                                    <th>Notes</th>
                                                </tr>
                                            </thead>
                                            <tbody id="reportTableBody"></tbody>
                                        </table>
                                    </div>
                                </div>
                            </div>
                        </div>

                        <!-- Chart -->
                        <div class="col-lg-4">
                            <div class="card p-3 border-1" style="border:1px solid #dee2e6;">
                                <h5 class="mb-3">Yearly Income (12 months)</h5>
                                <canvas id="yearlyChart" height="220"></canvas>
                            </div>
                        </div>

                    </div>

                </div>
            </div> <!-- MAIN BORDER WRAPPER END -->
        </div>
    </div>
</div>