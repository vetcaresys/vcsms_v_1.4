document.addEventListener("DOMContentLoaded", () => {
    fetch("fetch_income_data.php")
        .then(res => res.json())
        .then(data => {

            const dates = data.graph.map(item => item.date);
            const income = data.graph.map(item => parseFloat(item.income));

            const ctx = document.getElementById("incomeChart").getContext("2d");

            new Chart(ctx, {
                type: "line",
                data: {
                    labels: dates,
                    datasets: [{
                        label: "Income (₱)",
                        data: income,
                        borderWidth: 3,
                        tension: 0.3,
                        fill: true,
                        backgroundColor: "rgba(13,110,253,0.15)",
                        borderColor: "rgba(13,110,253,1)",
                        pointBackgroundColor: "rgba(0, 86, 179, 1)",
                        pointRadius: 4
                    }]
                },
                options: {
                    responsive: true,
                    scales: {
                        y: {
                            beginAtZero: true,
                            ticks: { callback: value => "₱" + value }
                        }
                    }
                }
            });

        });
});