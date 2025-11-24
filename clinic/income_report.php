<?php
// clinic/income_report.php
include '../config.php';
session_start();

if (!isset($_SESSION['user_id']) || $_SESSION['role'] !== 'clinic_owner') {
    header('Location: ../login.php');
    exit;
}

$clinic_id = $_SESSION['clinic_id'] ?? null;
if (!$clinic_id) {
    echo "No clinic selected.";
    exit;
}

// Default date range (last 30 days)
$start_default = date('Y-m-d', strtotime('-29 days'));
$end_default = date('Y-m-d');

?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="utf-8" />
    <title>Income Report - VetCareSys</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/css/bootstrap.min.css" rel="stylesheet">
    <link href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.10.5/font/bootstrap-icons.css" rel="stylesheet">
    <style>
        .card { border-radius: 12px; }
        .table-wrap { max-height: 420px; overflow:auto; }
        .summary-badge { font-size: 1rem; }
        @media print {
            .no-print { display:none !important; }
        }
    </style>
</head>
<body class="p-4">
    <div class="container">
        <div class="d-flex justify-content-between align-items-center mb-3">
            <h3 class="mb-0 text-primary"><i class="bi bi-file-earmark-text"></i> Income Report (Full)</h3>
            <div class="no-print">
                <a href="index.php" class="btn btn-outline-secondary me-2">Back to Dashboard</a>
            </div>
        </div>

        <!-- Filters -->
        <div class="card p-3 mb-3 no-print">
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
        <div class="row g-3 mb-3">
            <div class="col-md-4">
                <div class="card p-3">
                    <small class="text-muted">Total Income</small>
                    <div class="d-flex justify-content-between align-items-center">
                        <h4 class="mb-0" id="totalIncome">₱0.00</h4>
                        <span class="badge bg-success summary-badge" id="totalCount">0 items</span>
                    </div>
                </div>
            </div>
            <div class="col-md-4">
                <div class="card p-3">
                    <small class="text-muted">Highest Day</small>
                    <div class="d-flex justify-content-between align-items-center">
                        <h5 class="mb-0" id="highestDay">—</h5>
                        <span class="text-muted" id="highestAmount">₱0.00</span>
                    </div>
                </div>
            </div>
            <div class="col-md-4">
                <div class="card p-3">
                    <small class="text-muted">Average per Day</small>
                    <div class="d-flex justify-content-between align-items-center">
                        <h5 class="mb-0" id="averageDay">₱0.00</h5>
                        <span class="text-muted" id="daysCount">0 days</span>
                    </div>
                </div>
            </div>
        </div>

        <!-- Table + Chart -->
        <div class="row g-3">
            <div class="col-lg-8">
                <div class="card p-3">
                    <div class="d-flex justify-content-between align-items-center mb-2">
                        <h5 class="mb-0">Detailed Income Table</h5>
                        <div class="no-print">
                            <button id="printBtn" class="btn btn-outline-secondary btn-sm me-2"><i class="bi bi-printer"></i> Print</button>
                            <button id="exportPdfBtn" class="btn btn-outline-primary btn-sm"><i class="bi bi-file-earmark-pdf"></i> Export PDF</button>
                        </div>
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
                            <tbody id="reportTableBody">
                                <!-- Filled by JS -->
                            </tbody>
                        </table>
                    </div>
                </div>
            </div>

            <!-- Right column: Yearly Graph -->
            <div class="col-lg-4">
                <div class="card p-3">
                    <h5 class="mb-3">Yearly Income (12 months)</h5>
                    <canvas id="yearlyChart" height="220"></canvas>
                </div>
            </div>
        </div>
    </div>

    <!-- Dependencies -->
    <script src="https://cdn.jsdelivr.net/npm/chart.js"></script>
    <script src="https://cdn.jsdelivr.net/npm/html2canvas@1.4.1/dist/html2canvas.min.js"></script>
    <script src="https://cdnjs.cloudflare.com/ajax/libs/jspdf/2.5.1/jspdf.umd.min.js"></script>

    <script>
    // Helpers
    function fmt(num) {
        return new Intl.NumberFormat('en-PH', { minimumFractionDigits: 2, maximumFractionDigits: 2 }).format(num);
    }

    async function fetchRange(start, end) {
        const params = new URLSearchParams({ start, end });
        const res = await fetch(`fetch_income_range.php?${params.toString()}`);
        const data = await res.json();
        return data;
    }

    async function fetchYearly() {
        const res = await fetch('fetch_yearly_income.php');
        return await res.json();
    }

    async function loadReport(start, end) {
        document.getElementById('reportTableBody').innerHTML = '<tr><td colspan="10" class="text-center p-3">Loading...</td></tr>';
        const data = await fetchRange(start, end);

        if (data.error) {
            alert(data.error);
            return;
        }

        // populate table rows
        const tbody = document.getElementById('reportTableBody');
        tbody.innerHTML = '';
        let total = 0;
        let count = 0;
        const dailyTotals = {};

        data.rows.forEach(row => {
            count++;
            total += parseFloat(row.income);

            // sum per day
            dailyTotals[row.date_used] = (dailyTotals[row.date_used] || 0) + parseFloat(row.income);

            const tr = document.createElement('tr');
            tr.innerHTML = `
                <td>${row.date_used}</td>
                <td>${row.record_id}</td>
                <td>${row.pet_name || ''}</td>
                <td>${row.owner_name || ''}</td>
                <td>${row.item_name || ''}</td>
                <td>${row.quantity_used}</td>
                <td>₱${fmt(row.unit_price)}</td>
                <td>₱${fmt(row.income)}</td>
                <td>${row.staff_name || ''}</td>
                <td>${row.notes || ''}</td>
            `;
            tbody.appendChild(tr);
        });

        // summary
        document.getElementById('totalIncome').textContent = '₱' + fmt(total);
        document.getElementById('totalCount').textContent = `${count} rows`;

        // highest day
        let highestDay = null, highestAmt = 0;
        Object.entries(dailyTotals).forEach(([d, amt]) => {
            if (amt > highestAmt) { highestAmt = amt; highestDay = d; }
        });

        document.getElementById('highestDay').textContent = highestDay ?? '—';
        document.getElementById('highestAmount').textContent = '₱' + fmt(highestAmt);

        // avg per day
        const days = Object.keys(dailyTotals).length || 1;
        document.getElementById('averageDay').textContent = '₱' + fmt(total / days);
        document.getElementById('daysCount').textContent = `${days} days`;
    }

    // Print
    document.getElementById('printBtn').addEventListener('click', () => {
        window.print();
    });

    // Export PDF (jsPDF + html2canvas)
    document.getElementById('exportPdfBtn').addEventListener('click', async () => {
        const optTitle = `Income_Report_${document.getElementById('startDate').value}_${document.getElementById('endDate').value}`;
        const element = document.querySelector('.container');
        // capture
        const canvas = await html2canvas(element, { scale: 2 });
        const imgData = canvas.toDataURL('image/png');
        const { jsPDF } = window.jspdf;
        const pdf = new jsPDF('p', 'mm', 'a4');
        // Calculate width and height to fit A4
        const pageWidth = pdf.internal.pageSize.getWidth();
        const pageHeight = pdf.internal.pageSize.getHeight();
        const imgProps = pdf.getImageProperties(imgData);
        const pdfHeight = (imgProps.height * pageWidth) / imgProps.width;
        pdf.addImage(imgData, 'PNG', 0, 0, pageWidth, pdfHeight);
        pdf.save(optTitle + '.pdf');
    });

    // quick range logic
    document.getElementById('quickRange').addEventListener('change', (e) => {
        const val = e.target.value;
        if (!val) return;
        const days = parseInt(val);
        const end = new Date();
        const start = new Date();
        start.setDate(end.getDate() - (days - 1));
        document.getElementById('startDate').value = start.toISOString().slice(0,10);
        document.getElementById('endDate').value = end.toISOString().slice(0,10);
    });

    // apply button
    document.getElementById('applyBtn').addEventListener('click', () => {
        const s = document.getElementById('startDate').value;
        const e = document.getElementById('endDate').value;
        if (!s || !e) return alert('Please select a date range');
        loadReport(s, e);
    });

    // initial load
    (async function() {
        const s = document.getElementById('startDate').value;
        const e = document.getElementById('endDate').value;
        await loadReport(s, e);

        // Yearly chart
        const yearly = await fetchYearly();
        const ctx = document.getElementById('yearlyChart').getContext('2d');
        const months = yearly.months || [];
        const incomes = yearly.incomes || [];
        new Chart(ctx, {
            type: 'bar',
            data: {
                labels: months,
                datasets: [{
                    label: 'Income (₱)',
                    data: incomes,
                    borderRadius: 6,
                    borderWidth: 1
                }]
            },
            options: {
                responsive: true,
                scales: {
                    y: { beginAtZero: true, ticks: { callback: val => '₱' + val } }
                }
            }
        });

    })();
    </script>
</body>
</html>
