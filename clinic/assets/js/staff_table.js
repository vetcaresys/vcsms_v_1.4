const table = document.getElementById("staffTable");
const rows = Array.from(table.querySelectorAll("tbody tr"));
const searchInput = document.getElementById("searchInput");
const rowsPerPageSelect = document.getElementById("rowsPerPage");
const pagination = document.getElementById("pagination");
const tableInfo = document.getElementById("tableInfo");

let currentPage = 1;
let rowsPerPage = parseInt(rowsPerPageSelect.value);
let filteredRows = [...rows];

function renderTable() {
    const start = (currentPage - 1) * rowsPerPage;
    const end = start + rowsPerPage;

    rows.forEach(row => row.style.display = "none");
    filteredRows.slice(start, end).forEach(row => row.style.display = "");
    
    tableInfo.textContent = `Showing ${start + 1} to ${Math.min(end, filteredRows.length)} of ${filteredRows.length} entries`;
}

function renderPagination() {
    pagination.innerHTML = "";
    const totalPages = Math.ceil(filteredRows.length / rowsPerPage);

    for (let i = 1; i <= totalPages; i++) {
        const li = document.createElement("li");
        li.className = `page-item ${i === currentPage ? 'active' : ''}`;
        li.innerHTML = `<a class="page-link" href="#">${i}</a>`;
        li.onclick = e => {
            e.preventDefault();
            currentPage = i;
            renderTable();
            renderPagination();
        };
        pagination.appendChild(li);
    }
}

searchInput.addEventListener("input", () => {
    const term = searchInput.value.toLowerCase();
    filteredRows = rows.filter(row =>
        row.textContent.toLowerCase().includes(term)
    );
    currentPage = 1;
    renderTable();
    renderPagination();
});

rowsPerPageSelect.addEventListener("change", () => {
    rowsPerPage = parseInt(rowsPerPageSelect.value);
    currentPage = 1;
    renderTable();
    renderPagination();
});

renderTable();
renderPagination();
