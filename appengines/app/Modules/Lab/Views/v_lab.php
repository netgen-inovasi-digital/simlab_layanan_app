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
            newUrl = apiUrl + "?ujiJenKode=" + encodeURIComponent(val) + "&page=" + currentPage + "&limit=" + currentLimit;
        }
        table = loadTable(newUrl);
        addAction();
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
        const actionUrl = form.getAttribute('action');
        saveData({ url: actionUrl, formData: formData, onSuccess: function(data) {
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

    function loadOptions(selected = {}) {
        // ✅ reset wrapper lama sebelum isi ulang
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
                let jenis = document.querySelector('[name="ujiJenKode"]');
                let alat  = document.querySelector('[name="ujiAlatKode"]');
                let para  = document.querySelector('[name="ujiParaKode"]');
                let penyelia = document.querySelector('[name="ujiPenyelia"]');
                let manajer  = document.querySelector('[name="ujiManajerTeknis"]');

                jenis.innerHTML = '<option value="">-- Pilih Jenis --</option>';
                alat.innerHTML  = '<option value="">-- Pilih Alat --</option>';
                para.innerHTML  = '<option value="">-- Pilih Parameter --</option>';
                penyelia.innerHTML = '<option value="">-- Pilih Penyelia --</option>';
                manajer.innerHTML  = '<option value="">-- Pilih Manajer Teknis --</option>';

                data.jenis.forEach(j => {
                    jenis.innerHTML += `<option value="${j.jenKode}" ${selected.jenis==j.jenKode?"selected":""}>${j.jenNama}</option>`;
                });
                data.alat.forEach(a => {
                    alat.innerHTML += `<option value="${a.alatKode}" ${selected.alat==a.alatKode?"selected":""}>${a.alatNama}</option>`;
                });
                data.parameter.forEach(p => {
                    para.innerHTML += `<option value="${p.paraKode}" ${selected.para==p.paraKode?"selected":""}>${p.paraNama}</option>`;
                });
                data.penyelia.forEach(sp => {
                    penyelia.innerHTML += `<option value="${sp.user_id}" ${selected.penyelia==sp.user_id?"selected":""}>${sp.username}</option>`;
                });
                data.manajer.forEach(sm => {
                    manajer.innerHTML += `<option value="${sm.user_id}" ${selected.manajer==sm.user_id?"selected":""}>${sm.username}</option>`;
                });

                let namaLayananInput = document.querySelector('[name="ujiLayanan"]');
                function autoFillNamaLayanan() {
                    let alatText = alat.options[alat.selectedIndex]?.text || "";
                    let paraText = para.options[para.selectedIndex]?.text || "";
                    if (alatText && paraText) {
                        namaLayananInput.value = alatText + " - " + paraText;
                    }
                }
                alat.addEventListener('change', autoFillNamaLayanan);
                para.addEventListener('change', autoFillNamaLayanan);

                // Aktifkan search untuk semua dropdown
                selectSearch('[name="ujiJenKode"]');
                selectSearch('[name="ujiAlatKode"]');
                selectSearch('[name="ujiParaKode"]');
                selectSearch('[name="ujiPenyelia"]');
                selectSearch('[name="ujiManajerTeknis"]');
            });
    }

    document.querySelector('#add').addEventListener('click', function() {
        document.querySelector('#myform').reset();
        document.querySelector('[name="id"]').value = "";
        loadOptions();
        $('#modalForm').modal('show');
    });

    function editItem(e) {
        const id = e.target.closest('div').id;
        fetch('<?php echo site_url("lab/edit/") ?>' + id)
            .then(res => res.json())
            .then(data => {
                document.querySelector('[name="id"]').value = data.id;
                document.querySelector('[name="ujiLayanan"]').value = data.ujiLayanan;
                document.querySelector('[name="ujiSatuan"]').value = data.ujiSatuan;
                document.querySelector('[name="ujiBiaya"]').value = data.ujiBiaya;
                document.querySelector('[name="ujiDiskon"]').value = data.ujiDiskon;
                loadOptions({
                    jenis: data.ujiJenKode,
                    alat: data.ujiAlatKode,
                    para: data.ujiParaKode,
                    penyelia: data.ujiPenyelia,
                    manajer: data.ujiManajerTeknis
                });
                $('#modalForm').modal('show');
            });
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

        const caret = document.createElement("span");
        caret.innerHTML = "&#9662;";
        caret.style.fontSize = "0.8rem";

        customSelect.appendChild(selected);
        customSelect.appendChild(caret);

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
                        select.dispatchEvent(new Event("change"));
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

    // ✅ reset dropdown custom setiap kali modal ditutup
    $('#modalForm').on('hidden.bs.modal', function () {
        document.querySelectorAll('[data-enhanced="true"]').forEach(el => {
            let wrapper = el.parentNode;
            if (wrapper.classList.contains("position-relative")) {
                wrapper.replaceWith(el);
                el.style.display = "";
                el.dataset.enhanced = "false";
            }
        });
    });
</script>

<div class="modal fade" id="modalForm" data-bs-backdrop="static" data-bs-keyboard="false" tabindex="-1" aria-labelledby="staticBackdropLabel" aria-hidden="true">
    <div class="modal-dialog modal-lg" role="document">
        <div class="modal-content">
            <div class="modal-header">
                <h5 class="modal-title">Form Layanan Lab</h5>
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
                            <select name="ujiJenKode" class="form-select" required>
                                <option value="">-- Pilih Jenis --</option>
                            </select>
                        </div>
                        <div class="mb-3">
                            <label class="form-label">Alat</label>
                            <select name="ujiAlatKode" class="form-select" required>
                                <option value="">-- Pilih Alat --</option>
                            </select>
                        </div>
                        <div class="mb-3">
                            <label class="form-label">Parameter</label>
                            <select name="ujiParaKode" class="form-select" required>
                                <option value="">-- Pilih Parameter --</option>
                            </select>
                        </div>
                        <div class="mb-3">
                            <label class="form-label">Penyelia</label>
                            <select name="ujiPenyelia" class="form-select">
                                <option value="">-- Pilih Penyelia --</option>
                            </select>
                        </div>
                        <div class="mb-3">
                            <label class="form-label">Manajer Teknis</label>
                            <select name="ujiManajerTeknis" class="form-select">
                                <option value="">-- Pilih Manajer Teknis --</option>
                            </select>
                        </div>
                    </div>
                    <div class="col-md-6">
                        <p class="text-muted small fw-bold">DETAIL LAYANAN</p>
                        <div class="mb-3">
                            <label class="form-label">Nama Layanan</label>
                            <input name="ujiLayanan" type="text" class="form-control" required placeholder="Masukkan nama layanan">
                        </div>
                        <div class="row">
                            <div class="col-sm-7">
                                <div class="mb-3">
                                    <label class="form-label">Biaya</label>
                                    <div class="input-group">
                                        <span class="input-group-text">Rp</span>
                                        <input name="ujiBiaya" type="number" class="form-control" required placeholder="Masukkan biaya">
                                    </div>
                                </div>
                            </div>
                            <div class="col-sm-5">
                                <div class="mb-3">
                                    <label class="form-label">Satuan</label>
                                    <input name="ujiSatuan" type="text" class="form-control" required placeholder="Sampel/Jam/Ruangan">
                                </div>
                            </div>
                        </div>
                        <div class="mb-3">
                            <label class="form-label">Diskon (%)</label>
                            <input name="ujiDiskon" type="number" class="form-control" min="0" max="100" placeholder="Masukkan diskon">
                        </div>
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
