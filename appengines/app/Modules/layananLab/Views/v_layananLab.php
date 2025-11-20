<div class="row">
    <div class="col-md-12">
        <div class="card">
            <div class="card-header border-bottom pb-3">
                <div class="d-flex justify-content-between align-items-center mb-3">
                    <label class="card-title mb-0 fs-5 fw-bold"><?php echo $title ?></label>
                    <button id="add" class="btn btn-primary">
                        <i class="bi bi-plus-circle-dotted"></i> Tambah
                    </button>
                </div>
                <div class="row g-3 align-items-end">
                    <div class="col-md-6">
                        <label for="filter_jenKode" class="form-label">Filter Kategori Layanan</label>
                        <select id="filter_jenKode" name="filter_jenKode" class="form-select">
                            <option value="">-- Semua Kategori --</option>
                        </select>
                    </div>
                    <div class="col-md-6">
                        <form id="formDiskonULM" action="<?= base_url('layananLab/update_diskon') ?>" method="post" class="d-flex align-items-end">
                            <?= csrf_field() ?>
                        </form>
                    </div>
                </div>
            </div>

            <div class="card-body">
                <table id="data-table" class="saytable border-top-bottom">
                    <thead>
                     <tr>
                        <th show width="3%">No.</th>
                        <th show width="3%">Kategori Layanan</th>
                        <th show width="40%">Nama Layanan</th>
                        <th show width="20%">Penanggung Jawab</th>
                        <th show width="10%">Biaya</th>
                        <th show width="2%">Diskon</th>
                        <th show width="10%" class="action text-end">Aksi<i class="bi bi-code sort-icon"></i></th>
                    </tr>
                    </thead>
                    <tbody id="table-body"></tbody>
                </table>
            </div>
        </div>
    </div>
</div>

<script>
    var apiUrl = '<?php echo site_url("layananLab/datalist") ?>';
    var currentPage = 1;
    var currentLimit = 10;

    function loadTable(url) {
        return createTable({
            tableId: 'data-table',
            apiUrl: url
        });
    }

    table = loadTable(apiUrl + "?page=" + currentPage + "&limit=" + currentLimit);
    addAction();

    fetch('<?php echo site_url("layananLab/getoptions") ?>')
        .then(res => res.json())
        .then(data => {
            var filterSelect = document.getElementById('filter_jenKode');
            data.jenis.forEach(j => {
                filterSelect.innerHTML += `<option value="${j.jenKode}">${j.jenKode} - ${j.jenNama}</option>`;
            });
        });

    document.getElementById('filter_jenKode').addEventListener('change', function() {
        var val = this.value;
        var newUrl = apiUrl + "?page=" + currentPage + "&limit=" + currentLimit;
        if (val !== "") {
            newUrl = apiUrl + "?kode_jenis=" + encodeURIComponent(val) + "&page=" + currentPage + "&limit=" + currentLimit;
        }
        table = loadTable(newUrl);
        addAction();
    });

    // Event listener untuk button lihat tim
    document.addEventListener('click', function(e) {
        if (e.target.closest('.btn-lihat-tim')) {
            const btn = e.target.closest('.btn-lihat-tim');
            const id = btn.getAttribute('data-id');
            lihatTim(id);
        }
    });

    document.querySelector('#formDiskonULM').addEventListener('submit', function(e) {
        e.preventDefault();
        const form = e.target;
        const formData = new FormData(form);
        const actionUrl = form.getAttribute('action');
        showLoading();
        const csrfInput = form.querySelector('[name="<?= csrf_token() ?>"]');
        const csrfToken = csrfInput ? csrfInput.value : '';
        fetch(actionUrl, {
            method: 'POST',
            body: formData,
            headers: { 'X-CSRF-TOKEN': csrfToken }
        })
        .then(res => res.json())
        .then(data => {
            if (data.xname && data.xhash) {
                document.querySelectorAll('[name="' + data.xname + '"]').forEach(input => {
                    input.value = data.xhash;
                });
            }
            if (data.res === true) {
                sayAlert('successModal', 'Berhasil', 'Diskon berhasil diupdate.', 'success');
            } else {
                sayAlert('errorModal', 'Gagal', 'Diskon gagal diupdate.', 'warning');
            }
        })
        .catch(err => {
            sayAlert('errorModal', 'Error', 'Terjadi kesalahan sistem.', 'warning');
        })
        .finally(() => hideLoading());
    });

    document.querySelector('#btnSimpan').addEventListener('click', function(e) {
        e.preventDefault();
        const form = document.querySelector('#myform');
        const formData = new FormData(form);
        
        // Hapus data tim lama jika ada
        formData.delete('tim[]');
        formData.delete('tim');
        
        // Tambahkan data tim ke formData
        timManager.getAllTim().forEach(member => {
            formData.append('tim[]', member.user_id);
        });
        
        // Parse biaya dari format rupiah ke number
        const biayaInput = form.querySelector('[name="biaya"]');
        if (biayaInput) {
            const biayaParsed = parseRupiah(biayaInput.value);
            formData.set('biaya', biayaParsed);
        }
        
        const actionUrl = form.getAttribute('action');
        saveData({ url: actionUrl, formData: formData, onSuccess: function(data) {
            if (data.res === true) {
                if (typeof table !== 'undefined') table.fetchData({ reload: true });
                sayAlert('successModal', 'Berhasil', 'Data berhasil disimpan.', 'success');
                if ($('#modalForm').hasClass('show')) $('#modalForm').modal('hide');
            } else if (data.res === 'validation_error') {
                sayAlert('errorModal', 'Validasi Gagal', data.message, 'warning');
            } else if (data.res === false && data.msg) {
                sayAlert('errorModal', 'Gagal', data.msg, 'warning');
            }
        }});
    });

    function saveData({ url, formData, onSuccess, onError }) {
        showLoading();
        const csrfInput = document.querySelector('[name="<?= csrf_token() ?>"]');
        const csrfToken = csrfInput ? csrfInput.value : '';
        fetch(url, {
            method: 'POST',
            body: formData,
            headers: { 'X-CSRF-TOKEN': csrfToken }
        })
        .then(response => response.json())
        .then(data => {
            if (data.xname && data.xhash) {
                document.querySelectorAll('[name="' + data.xname + '"]').forEach(input => {
                    input.value = data.xhash;
                });
            }
            if (typeof onSuccess === 'function') {
                onSuccess(data);
                return;
            }
            if ($('#modalForm').hasClass('show')) $('#modalForm').modal('hide');
            if (data.res === true) {
                if (typeof table !== 'undefined') table.fetchData({ reload: true });
                sayAlert('successModal', 'Success', 'Data berhasil disimpan.', 'success');
            } else if (data.res === 'validation_error') {
                sayAlert('errorModal', 'Validasi Gagal', data.message || 'Silakan periksa kembali data yang diinput.', 'warning');
            } else if (data.res === false && data.msg) {
                sayAlert('errorModal', 'Error', data.msg, 'warning');
            } else {
                sayAlert('errorModal', 'Error', 'Data gagal disimpan.', 'warning');
            }
        })
        .catch(error => {
            if (typeof onError === 'function') {
                onError(error);
            } else {
                sayAlert('errorModal', 'Error', 'Terjadi kesalahan pada sistem.', 'warning');
            }
        })
        .finally(() => hideLoading());
    }

    // Global variables untuk menyimpan data tim
    if (!window.timManager) {
        window.timManager = {
            penyeliaList: [],
            manajerList: [],
            addPenyelia(user) {
                if (!this.penyeliaList.find(p => p.user_id == user.user_id)) {
                    this.penyeliaList.push(user);
                    renderPenyeliaTable();
                }
            },
            addManajer(user) {
                if (!this.manajerList.find(m => m.user_id == user.user_id)) {
                    this.manajerList.push(user);
                    renderManajerTable();
                }
            },
            removePenyelia(userId) {
                this.penyeliaList = this.penyeliaList.filter(p => p.user_id != userId);
                renderPenyeliaTable();
            },
            removeManajer(userId) {
                this.manajerList = this.manajerList.filter(m => m.user_id != userId);
                renderManajerTable();
            },
            reset() {
                this.penyeliaList = [];
                this.manajerList = [];
                renderPenyeliaTable();
                renderManajerTable();
            },
            getAllTim() {
                return [...this.penyeliaList, ...this.manajerList];
            }
        };
    }

    function loadOptions(selected = {}) {
        //  reset wrapper lama sebelum isi ulang
        document.querySelectorAll('[data-enhanced="true"]').forEach(el => {
            var wrapper = el.parentNode;
            if (wrapper.classList.contains("position-relative")) {
                wrapper.replaceWith(el); // balikin select ke posisi asli
                el.style.display = "";   // munculin select
                el.dataset.enhanced = "false";
            }
        });

        fetch('<?php echo site_url("layananLab/getoptions") ?>')
            .then(res => res.json())
            .then(data => {
                var jenis = document.querySelector('[name="kode_jenis"]');
                var alat  = document.querySelector('[name="kode_alat"]');
                var para  = document.querySelector('[name="kode_parameter"]');
                var selectPenyelia = document.querySelector('#selectPenyelia');
                var selectManajer = document.querySelector('#selectManajer');

                // Initialize arrays
                window.allPenyelia = data.penyelia || [];
                window.allManajer = data.manajer || [];

                if (jenis) {
                    jenis.innerHTML = '<option value="">-- Pilih Jenis --</option>';
                    data.jenis.forEach(j => {
                        jenis.innerHTML += `<option value="${j.jenKode}" ${selected.jenis==j.jenKode?"selected":""}>${j.jenNama}</option>`;
                    });
                }
                
                if (alat) {
                    alat.innerHTML  = '<option value="">-- Pilih Alat --</option>';
                    data.alat.forEach(a => {
                        alat.innerHTML += `<option value="${a.alatKode}" ${selected.alat==a.alatKode?"selected":""}>${a.alatNama}</option>`;
                    });
                }
                
                if (para) {
                    para.innerHTML  = '<option value="">-- Pilih Parameter --</option>';
                    data.parameter.forEach(p => {
                        para.innerHTML += `<option value="${p.paraKode}" ${selected.para==p.paraKode?"selected":""}>${p.paraNama}</option>`;
                    });
                }
                
                if (selectPenyelia) {
                    selectPenyelia.innerHTML = '<option value=""> Pilih Penyelia ... </option>';
                    window.allPenyelia.forEach(user => {
                        selectPenyelia.innerHTML += `<option value="${user.user_id}">${user.username}</option>`;
                    });
                }
                
                if (selectManajer) {
                    selectManajer.innerHTML = '<option value="">  Pilih Manajer Teknis ... </option>';
                    window.allManajer.forEach(user => {
                        selectManajer.innerHTML += `<option value="${user.user_id}">${user.username}</option>`;
                    });
                }

                // Load selected tim jika edit
                if (selected.tim && selected.tim.length > 0) {
                    selected.tim.forEach(tm => {
                        if (tm.role_id == 6) {
                            var user = window.allPenyelia.find(u => u.user_id == tm.user_id);
                            if (user) timManager.addPenyelia(user);
                        } else if (tm.role_id == 4) {
                            var user = window.allManajer.find(u => u.user_id == tm.user_id);
                            if (user) timManager.addManajer(user);
                        }
                    });
                }

                var namaLayananInput = document.querySelector('[name="nama_layanan"]');
                function autoFillNamaLayanan() {
                    if (!alat || !para || !namaLayananInput) return;
                    var alatText = alat.options[alat.selectedIndex]?.text || "";
                    var paraText = para.options[para.selectedIndex]?.text || "";
                    if (alatText && paraText) {
                        namaLayananInput.value = alatText + " - " + paraText;
                    }
                }
                if (alat) alat.addEventListener('change', autoFillNamaLayanan);
                if (para) para.addEventListener('change', autoFillNamaLayanan);

                // Aktifkan search untuk dropdown
                selectSearch('[name="kode_jenis"]');
                selectSearch('[name="kode_alat"]');
                selectSearch('[name="kode_parameter"]');
                selectSearch('#selectPenyelia');
                selectSearch('#selectManajer');
            });
    }

    // Event listener untuk menambah penyelia dan manajer
    document.addEventListener('change', function(e) {
        if (e.target.id === 'selectPenyelia' && e.target.value) {
            var userId = parseInt(e.target.value);
            var user = window.allPenyelia.find(u => u.user_id == userId);
            if (user) timManager.addPenyelia(user);
            e.target.value = '';
        }
        
        if (e.target.id === 'selectManajer' && e.target.value) {
            var userId = parseInt(e.target.value);
            var user = window.allManajer.find(u => u.user_id == userId);
            if (user) timManager.addManajer(user);
            e.target.value = '';
        }
    });

    function renderTimTable(tbodyId, list, emptyMsg, btnClass) {
        var tbody = document.querySelector(tbodyId);
        if (!tbody) return; // Exit if tbody element doesn't exist
        
        tbody.innerHTML = '';
        
        if (list.length === 0) {
            tbody.innerHTML = `<tr class="text-muted text-center"><td colspan="3"><em>${emptyMsg}</em></td></tr>`;
        } else {
            list.forEach((user, idx) => {
                tbody.innerHTML += `
                    <tr>
                        <td>${idx + 1}.</td>
                        <td>${user.username}</td>
                        <td class="text-center">
                            <span class="text-danger btn-action ${btnClass}" data-id="${user.user_id}" title="Hapus" style="cursor: pointer;">
                                <i class="bi bi-trash"></i>
                            </span>
                        </td>
                    </tr>
                `;
            });
        }
    }

    function renderPenyeliaTable() {
        renderTimTable('#tablePenyelia', timManager.penyeliaList, 'Belum ada penyelia dipilih', 'btn-hapus-penyelia');
    }

    function renderManajerTable() {
        renderTimTable('#tableManajer', timManager.manajerList, 'Belum ada manajer teknis dipilih', 'btn-hapus-manajer');
    }

    // Event listener untuk hapus
    document.addEventListener('click', function(e) {
        const targetPenyelia = e.target.closest('.btn-hapus-penyelia');
        if (targetPenyelia) {
            var userId = parseInt(targetPenyelia.getAttribute('data-id'));
            timManager.removePenyelia(userId);
        }
        
        const targetManajer = e.target.closest('.btn-hapus-manajer');
        if (targetManajer) {
            var userId = parseInt(targetManajer.getAttribute('data-id'));
            timManager.removeManajer(userId);
        }
    });

    document.querySelector('#add').addEventListener('click', function() {
        const form = document.querySelector('#myform');
        if (form) form.reset();
        
        const idInput = document.querySelector('[name="id"]');
        if (idInput) idInput.value = "";
        
        // Reset tim lists
        timManager.reset();
        loadOptions();
        $('#modalForm').modal('show');
    });

    function editItemLayananLab(e) {
        const id = e.target.closest('div').id;
        fetch('<?php echo site_url("layananLab/edit/") ?>' + id)
            .then(res => res.json())
            .then(data => {
                const idInput = document.querySelector('[name="id"]');
                if (idInput) idInput.value = data.id;
                
                const namaLayananInput = document.querySelector('[name="nama_layanan"]');
                if (namaLayananInput) namaLayananInput.value = data.nama_layanan;
                
                const satuanInput = document.querySelector('[name="satuan"]');
                if (satuanInput) satuanInput.value = data.satuan;
                
                const biayaInput = document.querySelector('[name="biaya"]');
                if (biayaInput) biayaInput.value = formatRupiah(data.biaya, false);
                
                const diskonInput = document.querySelector('[name="diskon"]');
                if (diskonInput) diskonInput.value = data.diskon;
                
                loadOptions({
                    jenis: data.kode_jenis,
                    alat: data.kode_alat,
                    para: data.kode_parameter,
                    tim: data.tim || []
                });
                $('#modalForm').modal('show');
            });
    }

    function lihatTim(id) {
        showLoading();
        fetch('<?php echo site_url("layananLab/getTim/") ?>' + id)
            .then(res => res.json())
            .then(data => {
                if (data.res && data.data) {
                    var tbody = document.querySelector('#timTableBody');
                    if (!tbody) {
                        hideLoading();
                        sayAlert('errorModal', 'Error', 'Elemen tabel tim tidak ditemukan.', 'warning');
                        return;
                    }
                    tbody.innerHTML = '';
                    
                    if (data.data.length === 0) {
                        tbody.innerHTML = '<tr><td colspan="3" class="text-center text-muted">Belum ada tim penanggung jawab</td></tr>';
                    } else {
                        // Group by role
                        var manajerList = data.data.filter(m => m.role_id == 4);
                        var penyeliaList = data.data.filter(m => m.role_id == 6);
                        
                        // Get max length to show all pairs
                        var maxLength = Math.max(manajerList.length, penyeliaList.length);
                        
                        for (var i = 0; i < maxLength; i++) {
                            var manajer = manajerList[i] ? manajerList[i].username : '';
                            var penyelia = penyeliaList[i] ? penyeliaList[i].username : '';
                            
                            tbody.innerHTML += `
                                <tr>
                                    <td class="text-center">${i + 1}</td>
                                    <td class="text-center">${manajer}</td>
                                    <td class="text-center">${penyelia}</td>
                                </tr>
                            `;
                        }
                    }
                    
                    $('#modalTim').modal('show');
                } else {
                    sayAlert('errorModal', 'Error', 'Gagal memuat data tim.', 'warning');
                }
            })
            .catch(err => {
                sayAlert('errorModal', 'Error', 'Terjadi kesalahan sistem.', 'warning');
            })
            .finally(() => hideLoading());
    }

    // ===== Dropdown dengan Search =====
    function selectSearch(selector) {
        const select = document.querySelector(selector);
        if (!select) return;

        // cegah duplikasi wrapper
        if (select.dataset.enhanced === "true") return;
        select.dataset.enhanced = "true";

        const wrapper = document.createElement("div");
        wrapper.className = "position-relative w-100";
        select.parentNode.insertBefore(wrapper, select);
        wrapper.appendChild(select);

        select.style.display = "none";

        const customSelect = document.createElement("div");
        customSelect.className = "form-select position-relative d-flex align-items-center justify-content-between";
        customSelect.style.cursor = "pointer";

        const selected = document.createElement("div");
        selected.className = "selected";
        selected.textContent = select.options[select.selectedIndex]?.text || "-- pilih data --";

        customSelect.appendChild(selected);

        const dropdownContainer = document.createElement("div");
        dropdownContainer.className = "dropdown-menu w-100 p-2 shadow";
        dropdownContainer.style.position = "absolute";
        dropdownContainer.style.top = "100%";
        dropdownContainer.style.left = "0";
        dropdownContainer.style.zIndex = "1050";
        dropdownContainer.style.display = "none";
        dropdownContainer.style.maxHeight = "250px";
        dropdownContainer.style.overflowY = "auto";
        dropdownContainer.style.fontSize = "0.9rem";

        const searchInput = document.createElement("input");
        searchInput.type = "text";
        searchInput.className = "form-control mb-2";
        searchInput.placeholder = "Search...";

        const dropdown = document.createElement("ul");
        dropdown.className = "list-unstyled m-0";

        function renderOptions() {
            dropdown.innerHTML = "";
            const filter = searchInput.value.toLowerCase();
            Array.from(select.options).forEach((option) => {
                if (option.value === "") return;
                if (option.text.toLowerCase().includes(filter)) {
                    const li = document.createElement("li");
                    li.className = "dropdown-item text-wrap";
                    li.textContent = option.text;
                    li.dataset.value = option.value;
                    li.style.cursor = "pointer";
                    li.addEventListener("click", () => {
                        select.value = option.value;
                        selected.textContent = option.text;
                        dropdownContainer.style.display = "none";
                        
                        // Trigger change event
                        const changeEvent = new Event("change", { bubbles: true });
                        select.dispatchEvent(changeEvent);
                    });
                    dropdown.appendChild(li);
                }
            });
        }

        renderOptions();
        searchInput.addEventListener("input", renderOptions);

        dropdownContainer.appendChild(searchInput);
        dropdownContainer.appendChild(dropdown);

        customSelect.addEventListener("click", () => {
            dropdownContainer.style.display = dropdownContainer.style.display === "none" ? "block" : "none";
            searchInput.focus();
        });

        document.addEventListener("click", (e) => {
            if (!wrapper.contains(e.target)) {
                dropdownContainer.style.display = "none";
            }
        });

        wrapper.appendChild(customSelect);
        wrapper.appendChild(dropdownContainer);
    }

    //  reset dropdown custom setiap kali modal ditutup
    $('#modalForm').on('hidden.bs.modal', function () {
        document.querySelectorAll('[data-enhanced="true"]').forEach(el => {
            var wrapper = el.parentNode;
            if (wrapper.classList.contains("position-relative")) {
                wrapper.replaceWith(el);
                el.style.display = "";
                el.dataset.enhanced = "false";
            }
        });
        
        // Reset tim lists
        timManager.reset();
    });

    initRupiahInputs();
</script>

<?= view('Modules\layananLab\Views\v_formAdd') ?>
<?= view('Modules\layananLab\Views\v_tim') ?>
