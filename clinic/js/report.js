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
    const printContents = document.getElementById('printSection').innerHTML;
    const w = window.open("", "", "height=900,width=1000");

    w.document.write("<html><head><title>Print</title>");
    w.document.write("<link rel='stylesheet' href='https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/css/bootstrap.min.css'>");
    w.document.write("<style>body { padding: 20px; }</style>");
    w.document.write("</head><body>");
    w.document.write(printContents);
    w.document.write("</body></html>");

    w.document.close();
    w.print();
});


// Export PDF (jsPDF + html2canvas)
document.getElementById('exportPdfBtn').addEventListener('click', async () => {
    const filename = `Income_Report_${document.getElementById('startDate').value}_${document.getElementById('endDate').value}`;
    const element = document.getElementById('exportSection');

    const canvas = await html2canvas(element, { scale: 2 });
    const imgData = canvas.toDataURL('image/png');
    const { jsPDF } = window.jspdf;
    const pdf = new jsPDF('p', 'mm', 'a4');

    const pageWidth = pdf.internal.pageSize.getWidth();
    const imgProps = pdf.getImageProperties(imgData);
    const pdfHeight = (imgProps.height * pageWidth) / imgProps.width;

    pdf.addImage(imgData, 'PNG', 0, 0, pageWidth, pdfHeight);
    pdf.save(filename + '.pdf');
});


// quick range logic
document.getElementById('quickRange').addEventListener('change', (e) => {
    const val = e.target.value;
    if (!val) return;
    const days = parseInt(val);
    const end = new Date();
    const start = new Date();
    start.setDate(end.getDate() - (days - 1));
    document.getElementById('startDate').value = start.toISOString().slice(0, 10);
    document.getElementById('endDate').value = end.toISOString().slice(0, 10);
});

// apply button
document.getElementById('applyBtn').addEventListener('click', () => {
    const s = document.getElementById('startDate').value;
    const e = document.getElementById('endDate').value;
    if (!s || !e) return alert('Please select a date range');
    loadReport(s, e);
});

// initial load
(async function () {
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
