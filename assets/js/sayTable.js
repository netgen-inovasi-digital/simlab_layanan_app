let table = null; let config = null;
let firstLoad = true; let isDataLoaded = false;
let allItems = []; let allItemsSorted = [];
let sortDirection = 'asc'; let lastSortedColumn = null;
let searchTerm;

let tables = {}; // Objek untuk menyimpan instance tabel
const defaultConfig = {
    apiUrl: '', currentPage: 1, tableId: 'data-table',
    itemsPerPage: 10,showFilter: true, treeview: true, numbering: true,
};

function createTable(customConfig = {}) {
    config = { ...defaultConfig, ...customConfig };
    tables[config.tableId] = { config };
    allItems = []; allItemsSorted = []; isDataLoaded = false; 
    let totalCount = 0; searchTerm = "";

    async function fetchData({ page = config.currentPage, reload = false } = {}) {
        config.currentPage = page; if (firstLoad) loadingTable();
        if (!isDataLoaded && allItems.length > 0 && reload == false) {
            displayCachedData(page, totalCount); return;
        }
        try {
            let response;
            if (searchTerm === "")
                response = await fetch(`${config.apiUrl}?page=${page}&limit=${config.itemsPerPage}`);
            else response = await fetch(`${config.apiUrl}?page=${page}&limit=${config.itemsPerPage}&search=${encodeURIComponent(searchTerm)}`);
            
            const data = await response.json();
            if (data.items !== undefined && data.items !== null) {
                if (data.total) {
                    allItems = data.items;
                    populateTable(allItems);
                    totalCount = data.total;
                    isDataLoaded = true;
                } else {
                    allItems = data.items;
                    const dataToUse = allItemsSorted.length > 0 ? allItemsSorted : allItems;
                    populateTable(
                        dataToUse.slice(
                            (config.currentPage - 1) * config.itemsPerPage,
                            config.currentPage * config.itemsPerPage)
                    ); totalCount = allItems.length;
                }
                if (config.showFilter) insertFilter();
                if (totalCount > config.itemsPerPage) {
                    insertPagination();
                    setupPagination(totalCount);
                } else {
                    const paging = document.getElementById(`pagination-${config.tableId}`);
                    if (paging) paging.remove();
                } 
            } else {
                throw new Error('Invalid data structure');
            }
        } catch (error) {
            console.error('Error fetching data:', error);
        } finally {
            const tr = document.getElementById('loading-table');
            if (tr) tr.remove(); firstLoad = false;
        }
    }
    fetchData();
    window.fetchData = fetchData;
    return {
        refresh: (newConfig = {}) => {
            Object.assign(config, newConfig);
            fetchData();
        }, fetchData,
        getConfig: () => config
    };
}

function displayCachedData(page, totalCount) {
    const dataToUse = allItemsSorted.length > 0 ? allItemsSorted : allItems;
    let dataToDisplay;
    if(searchTerm === "") {
        dataToDisplay = dataToUse.slice((page - 1) * config.itemsPerPage,page * config.itemsPerPage );
    } else {
        const filteredItems = dataToUse.filter(item => {
            return Object.values(item).some(value => String(value).toLowerCase().includes(searchTerm));
        });
        dataToDisplay = filteredItems.slice((page - 1) * config.itemsPerPage,page * config.itemsPerPage );
        totalCount = filteredItems.length;
    }
    populateTable(dataToDisplay);
    insertPagination();
    setupPagination(totalCount);
}

function loadingTable(){
    const table = document.getElementById(config.tableId);
    const tableBody = table.querySelector('tbody');
    const headers = table.querySelectorAll('th');
    const row = document.createElement('tr');
    const cell = document.createElement('td');
    row.id = 'loading-table';
    cell.colSpan = headers.length;
    cell.style.height = '70px'; 
    cell.innerHTML = '<div class="spinner-table"></div><em>Loading, silahkan tunggu...</em>';
    row.appendChild(cell);
    tableBody.appendChild(row);
}

function createFilter(){
    const container = document.createElement("div");
    container.setAttribute("id", "filter-container");
    container.setAttribute("class", "d-flex justify-content-between align-items-center mb-2");
    container.innerHTML = `
        <div><label for="items-per-page">Show:</label>
        <select id="items-per-page">
            <option value="10">10</option>
            <option value="25">25</option>
            <option value="50">50</option>
        </select></div>
        <div><input type="text" id="search-input" placeholder="Search..." /></div>
    `;
    return container;
}

function insertFilter() {
    const table = document.getElementById(config.tableId);
    const filterContainer = document.getElementById("filter-container");
    if ( table && table.parentNode && !filterContainer) {
        const filter = createFilter();
        table.parentNode.insertBefore(filter, table);
        const itemsPerPageSelect = document.getElementById('items-per-page');
        itemsPerPageSelect.addEventListener('change', (event) => {
            config.itemsPerPage = parseInt(event.target.value);
            const currentPage = 1;
            changePage(currentPage);
        });
        const searchInput = document.getElementById('search-input');
        searchInput.addEventListener('input', debounce((event) => {
            searchTerm = event.target.value.toLowerCase();
            config.currentPage = 1;
            fetchData({page: config.currentPage});
        }, 300));
    } 
}
// Fungsi debounce untuk menghindari terlalu banyak request API
function debounce(func, delay) {
    let timer;
    return function (...args) {
        clearTimeout(timer);
        timer = setTimeout(() => func.apply(this, args), delay);
    };
}
function populateTable(data) {
    const table = document.getElementById(config.tableId);
    const tableBody = table.querySelector('tbody');
    const fragment = document.createDocumentFragment();
    tableBody.innerHTML = ''; // Clear existing rows

    const headers = Array.from(document.querySelectorAll('th'));
    // Tampilkan pesan jika data kosong
    if (!data || data.length === 0) {
        const row = document.createElement('tr');
        const cell = document.createElement('td');
        cell.colSpan = headers.length;
        cell.textContent = 'No data available';
        row.appendChild(cell);
        fragment.appendChild(row);
        tableBody.appendChild(fragment);
        return;
    }
    // Penomoran baris berdasarkan halaman
    const offset = (config.currentPage - 1) * config.itemsPerPage;
    // Isi tabel
    data.forEach((item, index) => {
        const row = document.createElement('tr');
		let toggleIcon;
		if (config.numbering) {
            const numCell = document.createElement('td');
            numCell.textContent = String(offset + index + 1) + '.';
            if (config.treeview) {
                numCell.innerHTML = '';
                toggleIcon = document.createElement('span');
                toggleIcon.classList.add('treeview-icon');
                toggleIcon.textContent = '▶';
                numCell.appendChild(toggleIcon);
                numCell.append(` ${offset + index + 1}.`);
            }
            row.appendChild(numCell);
        }
        // Baris data utama
        Object.keys(item).forEach((key, cellIndex) => {
            const cell = document.createElement('td');
            const value = item[key];
            const isHTML = /<[^>]+>/.test(value);
            if (typeof value === 'string' && isHTML) {
                cell.innerHTML = value; // Izinkan HTML jika ada
            } else cell.textContent = value !== undefined ? value : ''; 
			if (config.treeview && !config.numbering && cellIndex === 0) {
                toggleIcon = document.createElement('span');
                toggleIcon.classList.add('treeview-icon');
                toggleIcon.textContent = '▶';
                cell.prepend(toggleIcon); // Sisipkan ikon di awal teks sel
            }
            // Sembunyikan kolom non-'show' di mobile
            if (config.treeview && !headers[cellIndex + (config.numbering ? 1 : 0)]?.hasAttribute('show')) {
                if (window.innerWidth <= 768) {
                    cell.style.display = 'none';
                }
            }
            row.appendChild(cell);
        });
		fragment.appendChild(row);
		if(config.treeview == true) {
			// Baris treeview untuk detail
			const hiddenDetailsRow = document.createElement('tr');
			const hiddenDetailsCell = document.createElement('td');
			// Set colSpan untuk mencakup semua kolom termasuk nomor dan toggle
			hiddenDetailsCell.colSpan = headers.length + ((config.numbering || config.treeview) ? 1 : 0);
			const detailList = document.createElement('ul');
			hiddenDetailsCell.appendChild(detailList);
			hiddenDetailsRow.appendChild(hiddenDetailsCell);
			hiddenDetailsRow.classList.add('treeview-row');
			hiddenDetailsRow.style.display = 'none'; // Sembunyikan awalnya
			// Menambahkan detail ke dalam treeview
			Object.keys(item).forEach((key, cellIndex) => {
				const headerIndex = config.numbering ? cellIndex + 1 : cellIndex;
				const header = headers[headerIndex];

				if (!header) return; // Skip jika header tidak tersedia

				if (!header.hasAttribute('show')) {
					const detailItem = document.createElement('li');
					const value = item[key] !== undefined ? item[key] : ''; // Hindari undefined
					detailItem.innerHTML = `${header.textContent}: ${value}`;
					detailList.appendChild(detailItem);
				}
			});
			// Event listener untuk toggle
			if (toggleIcon) {
                toggleIcon.addEventListener('click', () => {
                    const isVisible = hiddenDetailsRow.style.display === 'table-row';
                    hiddenDetailsRow.style.display = isVisible ? 'none' : 'table-row';
                    toggleIcon.textContent = isVisible ? '▶' : '▼';
                });
            }		
			fragment.appendChild(hiddenDetailsRow);
		}
    });
    tableBody.appendChild(fragment);
	attachSortListeners();
}
function insertPagination() {
    const table = document.getElementById(config.tableId);
    const pagination = document.getElementById(`pagination-${config.tableId}`);
    if (table && !pagination) {
        const container = document.createElement("div");
        container.setAttribute("id", `pagination-${config.tableId}`);
        container.setAttribute("class", 'paging'); 
        container.innerHTML = `
                <button class="btn btn-sm prev-button" id="prev-button-${config.tableId}">Previous</button>
                <div class="page-buttons" id="page-buttons-${config.tableId}"></div>
                <button class="btn btn-sm next-button" id="next-button-${config.tableId}">Next</button>
            `;
        table.insertAdjacentElement('afterEnd', container);
    } 
}
function setupPagination(totalItems) {
    const totalPages = Math.ceil(totalItems / config.itemsPerPage);
    const prevButton = document.getElementById(`prev-button-${config.tableId}`);
    const nextButton = document.getElementById(`next-button-${config.tableId}`);
    const pageButtonsContainer = document.getElementById(`page-buttons-${config.tableId}`);
    if (!prevButton || !nextButton || !pageButtonsContainer) {
        console.error('Pagination buttons not found!');
        return;
    }
    // Atur status tombol Previous dan Next
    prevButton.disabled = config.currentPage === 1;
    nextButton.disabled = config.currentPage === totalPages;
    // Kosongkan tombol halaman yang ada
    pageButtonsContainer.innerHTML = '';

    const maxVisibleButtons = 5; // Jumlah tombol halaman yang terlihat sebelum menampilkan titik-titik
    const sideButtons = Math.floor(maxVisibleButtons / 2); // Jumlah tombol di kiri dan kanan halaman saat ini
    let startPage = Math.max(1, config.currentPage - sideButtons);
    let endPage = Math.min(totalPages, config.currentPage + sideButtons);
    if (endPage - startPage + 1 < maxVisibleButtons) {
        if (config.currentPage <= sideButtons) {
            endPage = Math.min(totalPages, startPage + maxVisibleButtons - 1);
        } else if (config.currentPage + sideButtons >= totalPages) {
            startPage = Math.max(1, endPage - maxVisibleButtons + 1);
        }
    }
    // Tambahkan tombol "First" jika perlu
    if (startPage > 1) {
        const firstButton = document.createElement('button');
        firstButton.textContent = '1';
        firstButton.className = config.currentPage === 1 ? 'btn btn-sm active' : 'btn btn-sm';
        firstButton.onclick = () => {
            config.currentPage = 1;
            fetchData({page: config.currentPage});
        };
        pageButtonsContainer.appendChild(firstButton);
        if (startPage > 2) {
            const dots = document.createElement('span');
            dots.textContent = '...';
            dots.className = 'dots';
            pageButtonsContainer.appendChild(dots);
        }
    }
    // Tambahkan tombol angka halaman
    for (let i = startPage; i <= endPage; i++) {
        const pageButton = document.createElement('button');
        pageButton.textContent = i;
        pageButton.className = i === config.currentPage ? 'btn btn-sm active' : 'btn btn-sm';
        pageButton.onclick = () => {
            config.currentPage = i;
            fetchData({page: config.currentPage});
        };
        pageButtonsContainer.appendChild(pageButton);
    }
    // Tambahkan tombol "Last" jika perlu
    if (endPage < totalPages) {
        if (endPage < totalPages - 1) {
            const dots = document.createElement('span');
            dots.textContent = '...';
            dots.className = 'dots';
            pageButtonsContainer.appendChild(dots);
        }
        const lastButton = document.createElement('button');
        lastButton.textContent = totalPages;
        lastButton.className = config.currentPage === totalPages ? 'btn btn-sm active' : 'btn btn-sm';
        lastButton.onclick = () => {
            config.currentPage = totalPages;
            fetchData({page: config.currentPage});
        };
        pageButtonsContainer.appendChild(lastButton);
    }
    // Penanganan tombol Previous
    prevButton.onclick = () => {
        if (config.currentPage > 1) {
            config.currentPage--;
            fetchData({page: config.currentPage});
        }
    };
    // Penanganan tombol Next
    nextButton.onclick = () => {
        if (config.currentPage < totalPages) {
            config.currentPage++;
            fetchData({page: config.currentPage});
        }
    };
}
function changePage(page) {
    config.currentPage = page; // Perbarui currentPage
    fetchData({page: config.currentPage}); // Panggil fetchData langsung
}
function sortTable(columnIndex) {
    // Kolom penomoran diabaikan dalam sorting
    if (config.numbering === true) columnIndex -= 1;
    const columnKey = Object.keys(allItems[0])[columnIndex]; // Ambil key dari kolom
    if (lastSortedColumn === columnIndex) {
        sortDirection = sortDirection === "asc" ? "desc" : "asc";
    } else sortDirection = "asc";
    
    // Sorting data mentah menggunakan HTML apa adanya
    allItemsSorted  = [...allItems].sort((a, b) => {
        const aHTML = a[columnKey];
        const bHTML = b[columnKey];
        let compareValue = 0;
        // Ambil nilai teks untuk pembandingan, namun tetap simpan HTML asli
        const aText = aHTML.replace(/<\/?[^>]+(>|$)/g, "").trim(); // Hanya untuk pembanding
        const bText = bHTML.replace(/<\/?[^>]+(>|$)/g, "").trim(); // Hanya untuk pembanding
        if (!isNaN(aText) && !isNaN(bText)) {
            // Jika nilai numerik
            compareValue = parseFloat(aText) - parseFloat(bText);
        } else {
            // Jika nilai teks
            compareValue = aText.localeCompare(bText);
        }
        return sortDirection === "asc" ? compareValue : -compareValue;
    });
    lastSortedColumn = columnIndex;
    // Tampilkan data terurut di halaman saat ini
    populateTable(
        allItemsSorted.slice(
            (config.currentPage - 1) * config.itemsPerPage,
            config.currentPage * config.itemsPerPage
        )
    );
}
function attachSortListeners() {
    const table = document.getElementById(config.tableId);
    const headers = table.querySelectorAll('th');
    headers.forEach((header, index) => {
        if (!header.hasAttribute('data-listener')) {
			if (header.textContent !== "No.") {
				header.style.cursor = 'pointer';
				header.addEventListener('click', () => {
					sortTable(index);
				});
			} header.setAttribute('data-listener', 'true');
        }
    });
}
function adjustTableForMobile() {
    const screenWidth = window.innerWidth;
    const ths = document.querySelectorAll(`#${config.tableId} th`);
    const rows = document.querySelectorAll(`#${config.tableId} tbody tr`);

    ths.forEach((th, index) => {
        const shouldShow = th.hasAttribute('show') || screenWidth > 768;
        th.style.display = shouldShow ? '' : 'none';
        
        rows.forEach(row => {
            const cells = row.querySelectorAll('td');
            if (cells[index]) {
                cells[index].style.display = shouldShow ? '' : 'none';
            }
        });
    });
}
window.addEventListener('resize', adjustTableForMobile);