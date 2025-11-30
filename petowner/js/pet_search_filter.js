document.addEventListener("DOMContentLoaded", function () {

    const table = document.getElementById("petsTable");
    const rows = Array.from(table.querySelectorAll("tbody tr"));
    const searchInput = document.getElementById("searchInput");
    const showEntriesSelect = document.getElementById("showEntries");
    const pagination = document.getElementById("pagination");
    const noResults = document.getElementById("noResults");

    let currentPage = 1;
    let rowsPerPage = parseInt(showEntriesSelect.value);

    /* ----------------------- PAGINATION ------------------------ */

    function renderTable() {
        rows.forEach(row => row.style.display = "none");

        const filteredRows = rows.filter(row => row.matches(".visible"));
        const start = (currentPage - 1) * rowsPerPage;
        const end = start + rowsPerPage;

        filteredRows.slice(start, end).forEach(row => row.style.display = "");

        renderPagination(filteredRows.length);
    }

    function renderPagination(totalRows) {
        pagination.innerHTML = "";
        if (totalRows <= rowsPerPage) return;

        const totalPages = Math.ceil(totalRows / rowsPerPage);

        for (let i = 1; i <= totalPages; i++) {
            const li = document.createElement("li");
            li.className = "page-item " + (i === currentPage ? "active" : "");
            li.innerHTML = `<a class="page-link" href="#">${i}</a>`;
            li.addEventListener("click", function () {
                currentPage = i;
                renderTable();
            });
            pagination.appendChild(li);
        }
    }

    /* ----------------------- SEARCH + HIGHLIGHT ------------------------ */

    function filterSearch() {
        const term = searchInput.value.toLowerCase();

        let matchCount = 0;

        rows.forEach(row => {
            const text = row.innerText.toLowerCase();
            const visible = text.includes(term);

            row.classList.toggle("visible", visible);

            if (visible) matchCount++;
        });

        noResults.style.display = matchCount === 0 ? "block" : "none";
        currentPage = 1;
        renderTable();
    }

    /* ----------------------- SORTING ------------------------ */

    document.querySelectorAll(".sortable").forEach((header, index) => {
        header.style.cursor = "pointer";
        header.addEventListener("click", function () {
            const ascending = !header.classList.contains("asc");
            document.querySelectorAll(".sortable").forEach(h => h.classList.remove("asc", "desc"));

            header.classList.add(ascending ? "asc" : "desc");

            rows.sort((a, b) => {
                const textA = a.cells[index + 1].innerText.toLowerCase();
                const textB = b.cells[index + 1].innerText.toLowerCase();
                return ascending ? textA.localeCompare(textB) : textB.localeCompare(textA);
            });

            table.querySelector("tbody").append(...rows);
            filterSearch();
        });
    });

    /* ----------------------- EVENTS ------------------------ */

    searchInput.addEventListener("input", filterSearch);
    showEntriesSelect.addEventListener("change", function () {
        rowsPerPage = parseInt(this.value);
        currentPage = 1;
        renderTable();
    });

    rows.forEach(row => row.classList.add("visible"));
    renderTable();

    /* ----------------------- EXPORT TO EXCEL ------------------------ */
    document.getElementById("exportExcel").addEventListener("click", function () {
        const wb = XLSX.utils.table_to_book(document.getElementById("petsTable"), { sheet: "Pets" });
        XLSX.writeFile(wb, "Pets_List.xlsx");
    });

    /* ----------------------- PRINT ------------------------ */
    document.getElementById("printTable").addEventListener("click", function () {
        const printContent = document.getElementById("petsTable").outerHTML;
        const win = window.open("");
        win.document.write("<html><head><title>Print</title></head><body>" + printContent + "</body></html>");
        win.print();
        win.close();
    });

});