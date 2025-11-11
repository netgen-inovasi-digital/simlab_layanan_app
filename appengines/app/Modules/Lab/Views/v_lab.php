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
                        <form id="formDiskonULM" action="<?= base_url('lab/update_diskon') ?>" method="post" class="d-flex align-items-end">
                            <?= csrf_field() ?>
                            <div class="flex-grow-1">
                                <label for="diskon_ulm" class="form-label">Diskon Civitas ULM (%)</label>
                                <input type="number" name="diskon" id="diskon_ulm" class="form-control" value="<?= isset($diskon_ulm) ? $diskon_ulm : '' ?>" min="0" max="100">
                            </div>
                            <div class="ms-2">
                                <button type="submit" class="btn btn-outline-primary">Update</button>
                            </div>
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
    var apiUrl = '<?php echo site_url("lab/datalist") ?>';
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

    fetch('<?php echo site_url("lab/getoptions") ?>')
        .then(res => res.json())
        .then(data => {
            let filterSelect = document.getElementById('filter_jenKode');
            data.jenis.forEach(j => {
                filterSelect.innerHTML += `<option value="${j.jenKode}">${j.jenKode} - ${j.jenNama}</option>`;
            });
        });

    document.getElementById('filter_jenKode').addEventListener('change', function() {
        let val = this.value;
        let newUrl = apiUrl + "?page=" + currentPage + "&limit=" + currentLimit;
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
        penyeliaList.forEach(p => {
            formData.append('tim[]', p.user_id);
        });
        manajerList.forEach(m => {
            formData.append('tim[]', m.user_id);
        });
        
        // Debug: log data yang akan dikirim
        console.log('===== DEBUG DATA TIM =====');
        console.log('Penyelia List:', penyeliaList);
        console.log('Manajer List:', manajerList);
        console.log('Total Tim Members:', penyeliaList.length + manajerList.length);
        console.log('FormData tim[]:', formData.getAll('tim[]'));
        
        // Log semua data di FormData
        console.log('===== ALL FORM DATA =====');
        for (let pair of formData.entries()) {
            console.log(pair[0] + ': ' + pair[1]);
        }
        console.log('========================');
        
        const actionUrl = form.getAttribute('action');
        saveData({ url: actionUrl, formData: formData, onSuccess: function(data) {
            console.log('===== RESPONSE FROM SERVER =====');
            console.log('Response:', data);
            if (data.debug_info) {
                console.log('Debug Info:', data.debug_info);
            }
            console.log('================================');
            
            if (data.res === true) {
                if (typeof table !== 'undefined') table.fetchData({ reload: true });
                sayAlert('successModal', 'Berhasil', 'Data berhasil disimpan.', 'success');
                if ($('#modalForm').hasClass('show')) $('#modalForm').modal('hide');
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
    let penyeliaList = [];
    let manajerList = [];
    let allPenyelia = [];
    let allManajer = [];

    function loadOptions(selected = {}) {
        //  reset wrapper lama sebelum isi ulang
        document.querySelectorAll('[data-enhanced="true"]').forEach(el => {
            let wrapper = el.parentNode;
            if (wrapper.classList.contains("position-relative")) {
                wrapper.replaceWith(el); // balikin select ke posisi asli
                el.style.display = "";   // munculin select
                el.dataset.enhanced = "false";
            }
        });

        fetch('<?php echo site_url("lab/getoptions") ?>')
            .then(res => res.json())
            .then(data => {
                let jenis = document.querySelector('[name="kode_jenis"]');
                let alat  = document.querySelector('[name="kode_alat"]');
                let para  = document.querySelector('[name="kode_parameter"]');
                let selectPenyelia = document.querySelector('#selectPenyelia');
                let selectManajer = document.querySelector('#selectManajer');

                jenis.innerHTML = '<option value="">-- Pilih Jenis --</option>';
                alat.innerHTML  = '<option value="">-- Pilih Alat --</option>';
                para.innerHTML  = '<option value="">-- Pilih Parameter --</option>';
                selectPenyelia.innerHTML = '<option value="">[ Pilih Penyelia ... ]</option>';
                selectManajer.innerHTML = '<option value="">[  Pilih Manajer Teknis ... ]</option>';

                data.jenis.forEach(j => {
                    jenis.innerHTML += `<option value="${j.jenKode}" ${selected.jenis==j.jenKode?"selected":""}>${j.jenNama}</option>`;
                });
                data.alat.forEach(a => {
                    alat.innerHTML += `<option value="${a.alatKode}" ${selected.alat==a.alatKode?"selected":""}>${a.alatNama}</option>`;
                });
                data.parameter.forEach(p => {
                    para.innerHTML += `<option value="${p.paraKode}" ${selected.para==p.paraKode?"selected":""}>${p.paraNama}</option>`;
                });
                
                // Populate dropdown penyelia dan manajer
                allPenyelia = data.penyelia || [];
                allManajer = data.manajer || [];
                
                allPenyelia.forEach(user => {
                    selectPenyelia.innerHTML += `<option value="${user.user_id}">${user.username}</option>`;
                });
                
                allManajer.forEach(user => {
                    selectManajer.innerHTML += `<option value="${user.user_id}">${user.username}</option>`;
                });

                // Load selected tim jika edit
                if (selected.tim && selected.tim.length > 0) {
                    selected.tim.forEach(tm => {
                        // Cek berdasarkan role_id
                        if (tm.role_id == 6) {
                            // Penyelia
                            let user = allPenyelia.find(u => u.user_id == tm.user_id);
                            if (user && !penyeliaList.find(p => p.user_id == user.user_id)) {
                                penyeliaList.push(user);
                            }
                        } else if (tm.role_id == 4) {
                            // Manajer Teknis
                            let user = allManajer.find(u => u.user_id == tm.user_id);
                            if (user && !manajerList.find(m => m.user_id == user.user_id)) {
                                manajerList.push(user);
                            }
                        }
                    });
                    renderPenyeliaTable();
                    renderManajerTable();
                }

                let namaLayananInput = document.querySelector('[name="nama_layanan"]');
                function autoFillNamaLayanan() {
                    let alatText = alat.options[alat.selectedIndex]?.text || "";
                    let paraText = para.options[para.selectedIndex]?.text || "";
                    if (alatText && paraText) {
                        namaLayananInput.value = alatText + " - " + paraText;
                    }
                }
                alat.addEventListener('change', autoFillNamaLayanan);
                para.addEventListener('change', autoFillNamaLayanan);

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
        console.log('Change event detected on:', e.target.id, 'Value:', e.target.value);
        
        if (e.target.id === 'selectPenyelia' && e.target.value) {
            let userId = parseInt(e.target.value);
            console.log('Penyelia selected, userId:', userId);
            console.log('Available penyelia:', allPenyelia);
            
            let user = allPenyelia.find(u => u.user_id == userId);
            console.log('Found user:', user);
            
            if (user && !penyeliaList.find(p => p.user_id == userId)) {
                penyeliaList.push(user);
                console.log('Added to penyeliaList:', penyeliaList);
                renderPenyeliaTable();
            } else {
                console.log('User already in list or not found');
            }
            e.target.value = '';
        }
        
        if (e.target.id === 'selectManajer' && e.target.value) {
            let userId = parseInt(e.target.value);
            console.log('Manajer selected, userId:', userId);
            console.log('Available manajer:', allManajer);
            
            let user = allManajer.find(u => u.user_id == userId);
            console.log('Found user:', user);
            
            if (user && !manajerList.find(m => m.user_id == userId)) {
                manajerList.push(user);
                console.log('Added to manajerList:', manajerList);
                renderManajerTable();
            } else {
                console.log('User already in list or not found');
            }
            e.target.value = '';
        }
    });

    function renderPenyeliaTable() {
        let tbody = document.querySelector('#tablePenyelia');
        tbody.innerHTML = '';
        
        if (penyeliaList.length === 0) {
            tbody.innerHTML = '<tr class="text-muted text-center"><td colspan="3"><em>Belum ada penyelia dipilih</em></td></tr>';
        } else {
            penyeliaList.forEach((user, idx) => {
                tbody.innerHTML += `
                    <tr>
                        <td>${idx + 1}.</td>
                        <td>${user.username}</td>
                        <td class="text-center">
                            <span class="text-danger btn-action btn-hapus-penyelia" data-id="${user.user_id}" title="Hapus" style="cursor: pointer;">
                                <i class="bi bi-trash"></i>
                            </span>
                        </td>
                    </tr>
                `;
            });
        }
    }

    function renderManajerTable() {
        let tbody = document.querySelector('#tableManajer');
        tbody.innerHTML = '';
        
        if (manajerList.length === 0) {
            tbody.innerHTML = '<tr class="text-muted text-center"><td colspan="3"><em>Belum ada manajer teknis dipilih</em></td></tr>';
        } else {
            manajerList.forEach((user, idx) => {
                tbody.innerHTML += `
                    <tr>
                        <td>${idx + 1}.</td>
                        <td>${user.username}</td>
                        <td class="text-center">
                            <span class="text-danger btn-action btn-hapus-manajer" data-id="${user.user_id}" title="Hapus" style="cursor: pointer;">
                                <i class="bi bi-trash"></i>
                            </span>
                        </td>
                    </tr>
                `;
            });
        }
    }

    // Event listener untuk hapus
    document.addEventListener('click', function(e) {
        const target = e.target.closest('.btn-hapus-penyelia');
        if (target) {
            let userId = parseInt(target.getAttribute('data-id'));
            penyeliaList = penyeliaList.filter(p => p.user_id != userId);
            renderPenyeliaTable();
        }
        
        const targetManajer = e.target.closest('.btn-hapus-manajer');
        if (targetManajer) {
            let userId = parseInt(targetManajer.getAttribute('data-id'));
            manajerList = manajerList.filter(m => m.user_id != userId);
            renderManajerTable();
        }
    });

    document.querySelector('#add').addEventListener('click', function() {
        document.querySelector('#myform').reset();
        document.querySelector('[name="id"]').value = "";
        // Reset tim lists
        penyeliaList = [];
        manajerList = [];
        loadOptions();
        $('#modalForm').modal('show');
    });

    function editItem(e) {
        const id = e.target.closest('div').id;
        fetch('<?php echo site_url("lab/edit/") ?>' + id)
            .then(res => res.json())
            .then(data => {
                document.querySelector('[name="id"]').value = data.id;
                document.querySelector('[name="nama_layanan"]').value = data.nama_layanan;
                document.querySelector('[name="satuan"]').value = data.satuan;
                document.querySelector('[name="biaya"]').value = data.biaya;
                document.querySelector('[name="diskon"]').value = data.diskon;
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
        fetch('<?php echo site_url("lab/getTim/") ?>' + id)
            .then(res => res.json())
            .then(data => {
                if (data.res && data.data) {
                    let tbody = document.querySelector('#timTableBody');
                    tbody.innerHTML = '';
                    
                    if (data.data.length === 0) {
                        tbody.innerHTML = '<tr><td colspan="3" class="text-center text-muted">Belum ada tim penanggung jawab</td></tr>';
                    } else {
                        // Group by role
                        let manajerList = data.data.filter(m => m.role_id == 4);
                        let penyeliaList = data.data.filter(m => m.role_id == 6);
                        
                        // Get max length to show all pairs
                        let maxLength = Math.max(manajerList.length, penyeliaList.length);
                        
                        for (let i = 0; i < maxLength; i++) {
                            let manajer = manajerList[i] ? manajerList[i].username : '';
                            let penyelia = penyeliaList[i] ? penyeliaList[i].username : '';
                            
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
                        console.log('Custom dropdown clicked:', select.id, 'Value:', option.value);
                        select.value = option.value;
                        selected.textContent = option.text;
                        dropdownContainer.style.display = "none";
                        
                        // Trigger change event
                        const changeEvent = new Event("change", { bubbles: true });
                        select.dispatchEvent(changeEvent);
                        console.log('Change event dispatched for:', select.id);
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
            let wrapper = el.parentNode;
            if (wrapper.classList.contains("position-relative")) {
                wrapper.replaceWith(el);
                el.style.display = "";
                el.dataset.enhanced = "false";
            }
        });
        
        // Reset tim lists
        penyeliaList = [];
        manajerList = [];
        renderPenyeliaTable();
        renderManajerTable();
    });
</script>

<div class="modal fade" id="modalForm" data-bs-backdrop="static" data-bs-keyboard="false" tabindex="-1" aria-labelledby="staticBackdropLabel" aria-hidden="true">
    <div class="modal-dialog modal-xl" role="document">
        <div class="modal-content">
            <div class="modal-header">
                <h5 class="modal-title">TAMBAH DATA</h5>
                <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
            </div>
            <?php echo form_open('lab/submit', array('id' => 'myform', 'novalidate' => '')) ?>
            <div class="modal-body p-4">
                <input type="hidden" value="" name="id" />
                <div class="row">
                    <div class="col-md-6 border-end">
                        <p class="text-muted small fw-bold">KLASIFIKASI LAYANAN</p>
                        <div class="mb-3">
                            <label class="form-label">Jenis Layanan</label>
                            <select name="kode_jenis" class="form-select" required>
                                <option value="">-- Pilih Jenis --</option>
                            </select>
                        </div>
                        <div class="mb-3">
                            <label class="form-label">Alat</label>
                            <select name="kode_alat" class="form-select" required>
                                <option value="">-- Pilih Alat --</option>
                            </select>
                        </div>
                        <div class="mb-3">
                            <label class="form-label">Parameter</label>
                            <select name="kode_parameter" class="form-select" required>
                                <option value="">-- Pilih Parameter --</option>
                            </select>
                        </div>
                    </div>
                    <div class="col-md-6">
                        <p class="text-muted small fw-bold">DETAIL LAYANAN</p>
                        <div class="mb-3">
                            <label class="form-label">Nama Layanan</label>
                            <input name="nama_layanan" type="text" class="form-control" required placeholder="Masukkan nama layanan">
                        </div>
                        <div class="row">
                            <div class="col-sm-7">
                                <div class="mb-3">
                                    <label class="form-label">Biaya</label>
                                    <div class="input-group">
                                        <span class="input-group-text">Rp</span>
                                        <input name="biaya" type="number" class="form-control" required placeholder="Masukkan biaya">
                                    </div>
                                </div>
                            </div>
                            <div class="col-sm-5">
                                <div class="mb-3">
                                    <label class="form-label">Satuan</label>
                                    <input name="satuan" type="text" class="form-control" required placeholder="Sampel/Jam/Ruangan">
                                </div>
                            </div>
                        </div>
                        <div class="mb-3">
                            <label class="form-label">Diskon (%)</label>
                            <input name="diskon" type="number" class="form-control" min="0" max="100" placeholder="Masukkan diskon">
                        </div>
                    </div>
                </div>
                <hr class="my-4">
                <div class="row">
                    <div class="col-12">
                        <p class="text-muted small fw-bold mb-3">TIM PENANGGUNG JAWAB</p>
                    </div>
                </div>

                <!-- Penyelia Section -->
                <div class="row mb-4">
                    <div class="col-md-6">
                        <label class="form-label fw-bold">PENYELIA </label>
                        <select id="selectPenyelia" class="form-select">
                            <option value="">[ Pilih Penyelia ... ]</option>
                        </select>
                        <div class="table-responsive border rounded mt-3">
                            <table class="table table-sm table-hover mb-0">
                                <thead class="table-light">
                                    <tr>
                                        <th width="10%">No.</th>
                                        <th width="70%">Username</th>
                                        <th width="20%" class="text-center">Aksi</th>
                                    </tr>
                                </thead>
                                <tbody id="tablePenyelia">
                                    <tr class="text-muted text-center">
                                        <td colspan="3"><em>Belum ada penyelia dipilih</em></td>
                                    </tr>
                                </tbody>
                            </table>
                        </div>
                        <p class="small text-muted mt-1 mb-0">(Pilih satu atau lebih penyelia)</p>
                    </div>

                    <!-- Manajer Teknis Section -->
                    <div class="col-md-6">
                        <label class="form-label fw-bold">MANAJER TEKNIS</label>
                        <select id="selectManajer" class="form-select">
                            <option value="">[Pilih Manajer Teknis ... ]</option>
                        </select>
                        <div class="table-responsive border rounded mt-3">
                            <table class="table table-sm table-hover mb-0">
                                <thead class="table-light">
                                    <tr>
                                        <th width="10%">No.</th>
                                        <th width="70%">Username</th>
                                        <th width="20%" class="text-center">Aksi</th>
                                    </tr>
                                </thead>
                                <tbody id="tableManajer">
                                    <tr class="text-muted text-center">
                                        <td colspan="3"><em>Belum ada manajer teknis dipilih</em></td>
                                    </tr>
                                </tbody>
                            </table>
                        </div>
                        <p class="small text-muted mt-1 mb-0">(Pilih satu atau lebih manajer teknis)</p>
                    </div>
                </div>
            </div>
            <div class="modal-footer">
                <button class="btn btn-light" type="button" data-bs-dismiss="modal"><i class="bi bi-x-circle"></i> Batal</button>
                <button class="btn btn-primary" id="btnSimpan" type="submit"><i class="bi bi-check2-circle"></i> Simpan</button>
            </div>
            </form>
        </div>
    </div>
</div>

<!-- Modal Lihat Tim -->
<div class="modal fade" id="modalTim" tabindex="-1" aria-labelledby="modalTimLabel" aria-hidden="true">
    <div class="modal-dialog modal-dialog-centered">
        <div class="modal-content">
            <div class="modal-header bg-primary text-white">
                <h5 class="modal-title" id="modalTimLabel">
                    <i class="bi bi-people-fill"></i> Tim Penanggung Jawab
                </h5>
                <button type="button" class="btn-close btn-close-white" data-bs-dismiss="modal" aria-label="Close"></button>
            </div>
            <div class="modal-body">
                <table class="table table-hover">
                    <thead class="table-light">
                        <tr>
                            <th width="10%" class="text-center">No</th>
                            <th width="45%" class="text-center">Manajer Teknis</th>
                            <th width="45%" class="text-center">Penyelia</th>
                        </tr>
                    </thead>
                    <tbody id="timTableBody">
                        <tr>
                            <td colspan="3" class="text-center">
                                <div class="spinner-border spinner-border-sm" role="status">
                                    <span class="visually-hidden">Loading...</span>
                                </div>
                            </td>
                        </tr>
                    </tbody>
                </table>
            </div>
            <div class="modal-footer">
                <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Tutup</button>
            </div>
        </div>
    </div>
</div>
