// ==============================================
//  Versi ISOLASI untuk modal (createTable1)
// ==============================================
let table1 = null;
let config1 = null;
let firstLoad1 = true;
let isDataLoaded1 = false;
let allItems1 = [];
let allItemsSorted1 = [];
let sortDirection1 = 'asc';
let lastSortedColumn1 = null;
let searchTerm1;

const defaultConfig1 = {
    apiUrl: '',
    currentPage: 1,
    tableId: 'layanan-modal-table',
    itemsPerPage: 10,
    showFilter: true,
    treeview: false,
    numbering: true,
};

// ===============================
// createTable1()
// ===============================
function createTable1(customConfig = {}) {
    config1 = { ...defaultConfig1, ...customConfig };
    allItems1 = [];
    allItemsSorted1 = [];
    isDataLoaded1 = false;
    let totalCount1 = 0;
    searchTerm1 = "";

    async function fetchData1({ page = config1.currentPage, reload = false } = {}) {
        config1.currentPage = page;
        if (firstLoad1) loadingTable1();

        try {
            let response;
            if (searchTerm1 === "")
                response = await fetch(`${config1.apiUrl}?page=${page}&limit=${config1.itemsPerPage}`);
            else
                response = await fetch(`${config1.apiUrl}?page=${page}&limit=${config1.itemsPerPage}&search=${encodeURIComponent(searchTerm1)}`);

            const data = await response.json();
            if (data.items !== undefined && data.items !== null) {
                if (data.total) {
                    allItems1 = data.items;
                    populateTable1(allItems1);
                    totalCount1 = data.total;
                    isDataLoaded1 = true;
                } else {
                    allItems1 = data.items;
                    const dataToUse = allItemsSorted1.length > 0 ? allItemsSorted1 : allItems1;
                    populateTable1(
                        dataToUse.slice(
                            (config1.currentPage - 1) * config1.itemsPerPage,
                            config1.currentPage * config1.itemsPerPage
                        )
                    );
                    totalCount1 = allItems1.length;
                }
                if (config1.showFilter) insertFilter1();
                if (totalCount1 > config1.itemsPerPage) {
                    insertPagination1();
                    setupPagination1(totalCount1);
                } else {
                    const paging = document.getElementById(`pagination-${config1.tableId}`);
                    if (paging) paging.remove();
                }
            }
        } catch (error) {
            console.error('Error fetching data modal:', error);
        } finally {
            const tr = document.getElementById('loading-table1');
            if (tr) tr.remove();
            firstLoad1 = false;
        }
    }

    fetchData1();

    return {
        refresh: (newConfig = {}) => {
            Object.assign(config1, newConfig);
            fetchData1();
        },
        fetchData1,
        getConfig: () => config1,
    };
}

// ===============================
// FUNGSI BANTUAN UNTUK VERSI 1
// ===============================
function loadingTable1() {
    const table = document.getElementById(config1.tableId);
    const tableBody = table.querySelector('tbody');
    const headers = table.querySelectorAll('th');
    const row = document.createElement('tr');
    const cell = document.createElement('td');
    row.id = 'loading-table1';
    cell.colSpan = headers.length;
    cell.innerHTML = '<div class="spinner-table"></div><em>Loading...</em>';
    row.appendChild(cell);
    tableBody.appendChild(row);
}

function insertFilter1() {
    const table = document.getElementById(config1.tableId);
    const filterContainer = document.getElementById("filter-container1");
    if (table && table.parentNode && !filterContainer) {
        const container = document.createElement("div");
        container.id = "filter-container1";
        container.className = "d-flex justify-content-between align-items-center mb-2";
        container.innerHTML = `
            <div>
                <label>Show:</label>
                <select id="items-per-page1">
                    <option value="10">10</option>
                    <option value="25">25</option>
                    <option value="50">50</option>
                </select>
            </div>
            <div>
                <input type="text" id="search-input1" placeholder="Search..." />
            </div>
        `;
        table.parentNode.insertBefore(container, table);

        document.getElementById('items-per-page1').addEventListener('change', (e) => {
            config1.itemsPerPage = parseInt(e.target.value);
            config1.currentPage = 1;
            createTable1().fetchData1({ page: 1 });
        });

        document.getElementById('search-input1').addEventListener('input', debounce1((e) => {
            searchTerm1 = e.target.value.toLowerCase();
            config1.currentPage = 1;
            createTable1().fetchData1({ page: 1 });
        }, 300));
    }
}

function populateTable1(data) {
    const table = document.getElementById(config1.tableId);
    const tbody = table.querySelector('tbody');
    tbody.innerHTML = '';
    if (!data || data.length === 0) {
        const tr = document.createElement('tr');
        const td = document.createElement('td');
        td.colSpan = table.querySelectorAll('th').length;
        td.textContent = 'No data available';
        tr.appendChild(td);
        tbody.appendChild(tr);
        return;
    }
    const offset = (config1.currentPage - 1) * config1.itemsPerPage;
    data.forEach((item, i) => {
        const tr = document.createElement('tr');
        if (config1.numbering) {
            const tdNum = document.createElement('td');
            tdNum.textContent = offset + i + 1;
            tr.appendChild(tdNum);
        }
        Object.values(item).forEach(v => {
            const td = document.createElement('td');
            td.innerHTML = v;
            tr.appendChild(td);
        });
        tbody.appendChild(tr);
    });
}

function insertPagination1() {
    const table = document.getElementById(config1.tableId);
    const pagination = document.getElementById(`pagination-${config1.tableId}`);
    if (!pagination) {
        const container = document.createElement('div');
        container.id = `pagination-${config1.tableId}`;
        container.className = 'paging mt-2';
        container.innerHTML = `
            <button class="btn btn-sm prev" id="prev-${config1.tableId}">Prev</button>
            <span id="page-${config1.tableId}" class="mx-2"></span>
            <button class="btn btn-sm next" id="next-${config1.tableId}">Next</button>
        `;
        table.insertAdjacentElement('afterend', container);
    }
}

function setupPagination1(totalItems) {
    const totalPages = Math.ceil(totalItems / config1.itemsPerPage);
    const prev = document.getElementById(`prev-${config1.tableId}`);
    const next = document.getElementById(`next-${config1.tableId}`);
    const pageInfo = document.getElementById(`page-${config1.tableId}`);
    pageInfo.textContent = `Page ${config1.currentPage} of ${totalPages}`;
    prev.disabled = config1.currentPage === 1;
    next.disabled = config1.currentPage === totalPages;

    prev.onclick = () => {
        if (config1.currentPage > 1) {
            config1.currentPage--;
            createTable1().fetchData1({ page: config1.currentPage });
        }
    };
    next.onclick = () => {
        if (config1.currentPage < totalPages) {
            config1.currentPage++;
            createTable1().fetchData1({ page: config1.currentPage });
        }
    };
}

function debounce1(fn, delay) {
    let timer;
    return (...args) => {
        clearTimeout(timer);
        timer = setTimeout(() => fn(...args), delay);
    };
}
