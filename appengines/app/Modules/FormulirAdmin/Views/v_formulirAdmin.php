<div class="row">
    <div class="col-md-12">
        <div class="card">
            <div class="card-header d-flex justify-content-between align-items-center">
                <div class="d-flex align-items-center" style="gap:12px;">
                    <label class="card-title mb-0"><?php echo $title ?></label>
                </div>

                <div>
                    <button id="add" class="btn btn-primary">
                        <i class="bi bi-plus-circle-dotted"></i> Pesan Layanan Baru
                    </button>
                </div>
            </div>
            <select id="statusFilter" class="form-select form-select-sm" style="width:180px; display:inline-block; margin-left:8px;">
                <option value="all">Semua Kategori</option>
                <option value="1">In Review Manajer</option>
                <option value="3">Belum direview</option>
                <option value="4">Dalam pengujian</option>
                <option value="5">LHUS diproses</option>
                <option value="6">LHUS disetujui</option>
                <option value="7">LHU proses</option>
            </select>
            <div class="card-body">
                <table id="data-table" class="saytable border-top-bottom">
                    <thead>
                        <tr>
                            <th show width="5%">No.</th>
                            <th show width="30%">Pemesan</th>
                            <th show width="15%">No Invoice</th>
                            <th show width="15%">Status</th>
                            <th show width="15%">Detail Layanan</th>
                            <th show width="15%" class="action text-end">Aksi<i class="bi bi-code sort-icon"></i></th>
                        </tr>
                    </thead>
                    <tbody id="table-body"></tbody>
                </table>
            </div>
        </div>
    </div>
</div>

<!-- ======= MODAL KERANJANG ======= -->
<div class="modal fade" id="modalKeranjang" data-bs-backdrop="static" data-bs-keyboard="false" tabindex="-1"
    aria-labelledby="staticBackdropLabel" aria-hidden="true">
    <div class="modal-dialog modal-xl" role="document" style="margin: 2% auto">
        <div class="modal-content">
            <div class="modal-header">
                <h5 class="modal-title">Pilih Layanan</h5>
                <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
            </div>

            <div class="modal-body">
                <!-- PEMILIH PELANGGAN DI DALAM MODAL -->
                <div class="mb-3">
                    <label class="form-label fw-semibold">Pelanggan</label>
                    <div class="d-flex" style="gap:8px; align-items:center;">
                        <select id="ker_pelanggan_select" class="form-select form-select-sm" style="min-width:320px;">
                            <option value="">-- Pilih Pelanggan (Nama — email — ULM/NON ULM) --</option>
                            <?php if (!empty($users) && is_array($users)): ?>
                                <?php foreach ($users as $u):
                                    $status = (isset($u->user_identity) && strtoupper($u->user_identity) === 'ULM') ? 'ULM' : 'NON ULM';
                                ?>
                                    <option value="<?= esc($u->user_id) ?>"
                                            data-email="<?= esc($u->user_email) ?>"
                                            data-status="<?= esc($status) ?>"
                                            data-name="<?= esc($u->user_name) ?>">
                                        <?= esc($u->user_name) ?> — <?= esc($u->user_email) ?> — <?= esc($status) ?>
                                    </option>
                                <?php endforeach; ?>
                            <?php endif; ?>
                        </select>

                        <div id="ker_pelanggan_badge" style="font-weight:600; font-size:0.95rem; margin-left:8px;">
                            <!-- info singkat pelanggan disini setelah dipilih -->
                        </div>
                    </div>
                </div>

                <!-- Tampilkan info pelanggan yang dipilih (redundan) -->
                <div id="ker_modalPelangganInfo" class="mb-3" style="font-weight:600; font-size:0.95rem; display:none;">
                    <!-- akan diisi oleh JS: "Untuk pelanggan: Nama — email — ULM" -->
                </div>

                <!-- CSRF Token -->
                <input type="hidden" name="<?= csrf_token() ?>" value="<?= csrf_hash() ?>" />
                
                <!-- Tabel Pilih Layanan -->
                <div class="mb-4">
                    <h6 class="fw-bold text-primary mb-3">
                        <i class="bi bi-list-check"></i> Daftar Layanan Tersedia
                    </h6>
                    <table id="ker_layanan-table" class="saytable border-top-bottom">
                        <thead>
                            <tr>
                                <th style="width:5%">No</th>
                                <th style="width:15%">Parameter</th>
                                <th style="width:15%">Instrumen/Alat/Tempat</th>
                                <th style="width:12%">Biaya</th>
                                <th style="width:12%">Jumlah</th>
                                <th style="width:18%">Keterangan</th>
                                <th style="width:5%" class="text-center">Aksi</th>
                            </tr>
                        </thead>
                        <tbody id="ker_layanan-table-body"></tbody>
                    </table>
                </div>

                <hr class="my-4">

                <!-- Tabel Preview Keranjang -->
                <div>
                    <div class="d-flex justify-content-between align-items-center mb-3">
                        <h6 class="fw-bold text-success mb-0">
                            <i class="bi bi-cart3"></i> Keranjang Anda 
                            (<span id="ker_jumlahItemKeranjang">0</span> Item)
                        </h6>
                        <button type="button" id="ker_btnRefreshKeranjang" class="btn btn-sm btn-outline-secondary">
                            <i class="bi bi-arrow-clockwise"></i> Refresh
                        </button>
                    </div>

                    <div id="ker_keranjangKosong" class="alert alert-warning text-center" style="display:none;">
                        <i class="bi bi-cart-x"></i> Keranjang masih kosong. Silakan pilih layanan di atas.
                    </div>
                    
                    <table id="ker_preview-keranjang-table" class="saytable border-top-bottom">
                        <thead>
                            <tr>
                                <th style="width:5%">No</th>
                                <th style="width:20%">Layanan</th>
                                <th style="width:12%">Biaya Satuan</th>
                                <th style="width:8%">Jumlah</th>
                                <th style="width:10%">Diskon (%)</th>
                                <th style="width:15%">Total</th>
                                <th style="width:20%">Keterangan</th>
                                <th style="width:10%" class="text-center">Aksi</th>
                            </tr>
                        </thead>
                        <tbody id="ker_preview-keranjang-table-body"></tbody>
                        <tfoot>
                            <tr class="table-active">
                                <td colspan="5" class="text-end fw-bold">TOTAL KESELURUHAN:</td>
                                <td id="ker_grandTotal" class="fw-bold text-primary fs-5">Rp 0</td>
                                <td colspan="2"></td>
                            </tr>
                        </tfoot>
                    </table>
                </div>
            </div>
            <div class="modal-footer">
                <button class="btn btn-light" type="button" data-bs-dismiss="modal">
                    <i class="bi bi-x-circle"></i> Tutup
                </button>
                <button id="ker_btnCheckoutFromModal" class="btn btn-success" disabled>
                    <i class="bi bi-cart-check"></i> Checkout Sekarang
                </button>
            </div>
        </div>
    </div>
</div>

<!-- ======= MODAL DETAIL ======= -->
<div class="modal fade" id="modalDetail" data-bs-backdrop="static" data-bs-keyboard="false" tabindex="-1" aria-hidden="true">
    <div class="modal-dialog modal-xl" role="document" style="margin: 2% auto">
        <div class="modal-content">
            <div class="modal-header">
                <h5 class="modal-title">Detail Layanan</h5>
                <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
            </div>
            <div class="modal-body">
                <table class="table table-bordered">
                    <thead>
                        <tr>
                            <th width="5%">No</th>
                            <th width="30%">Layanan</th>
                            <th width="10%">Biaya</th>
                            <th width="5%">Jumlah</th>
                            <th width="20%">Keterangan</th>
                            <th width="10%">Status</th>
                            <th width="20%">Keterangan Manajer</th>
                        </tr>
                    </thead>
                    <tbody id="detail-body">
                        <tr><td colspan="7" class="text-center">Loading...</td></tr>
                    </tbody>
                </table>
            </div>
            <div class="modal-footer">
                <button class="btn btn-light" type="button" data-bs-dismiss="modal">
                    <i class="bi bi-x-circle"></i> Tutup
                </button>
            </div>
        </div>
    </div>
</div>

<!-- ======= SCRIPTS ======= -->
<script>
    // ambil parameter URL
    var urlParams = new URLSearchParams(window.location.search);
    var kategoriFromUrl = urlParams.get('kategoriLayanan');

    // Base API URL
    var baseApiUrl = '<?php echo site_url("formuliradmin/datalist") ?>';
    if (kategoriFromUrl) {
        baseApiUrl += '?kategoriLayanan=' + kategoriFromUrl;
    }

    // Inisialisasi table (fungsi createTable diasumsikan sudah ada di project)
    table = createTable({
        apiUrl: baseApiUrl,
        dataSrc: 'items'
    });

    // Set dropdown sesuai parameter URL
    if (kategoriFromUrl) {
        var sf = document.getElementById('statusFilter');
        if (sf) sf.value = kategoriFromUrl;
    }

    // panggil fungsi tambahan (asumsi addAction tersedia)
    if (typeof addAction === 'function') addAction();

    // Event listener untuk filter dropdown
    var statusFilterEl = document.getElementById('statusFilter');
    if (statusFilterEl) {
        statusFilterEl.addEventListener('change', function() {
            const selectedValue = this.value;
            const tableConfig = table.getConfig();

            if (selectedValue === 'all') {
                tableConfig.apiUrl = '<?php echo site_url("formuliradmin/datalist") ?>';
            } else {
                tableConfig.apiUrl = '<?php echo site_url("formuliradmin/datalist") ?>?kategoriLayanan=' + selectedValue;
            }

            tableConfig.currentPage = 1;
            table.fetchData({ page: 1, reload: true });
        });
    }

    // ======= saveData tetap tersedia (dipakai oleh fitur lain) =======
    function saveData({ url, formData, onSuccess, onError }) {
        showLoading();

        const csrfInput = document.querySelector('[name="<?= csrf_token() ?>"]');
        const csrfToken = csrfInput ? csrfInput.value : '';

        fetch(url, {
            method: 'POST',
            body: formData,
            headers: {
                'X-CSRF-TOKEN': csrfToken
            }
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

            // Default handling untuk responses lainnya
            if (data.res === true) {
                if (typeof table !== 'undefined') table.fetchData({ reload: true });
                sayAlert('successModal', 'Success', 'Data berhasil disimpan.', 'success');
            } else if (data.res === 'reload') {
                sayAlert('successModal', 'Success', 'Data berhasil disimpan.', 'success');
            } else if (data.res === 'refresh') {
                loadContent(data.link);
                sayAlert('successModal', 'Success', 'Data berhasil disimpan.', 'success');
            } else if (data.res === 'redirect') {
                window.location.href = data.link;
            } else if (data.res === 'check') {
                sayAlert('errorModal', 'Error', data.link, 'warning');
            } else if (data.res === 'refresh-print') {
                loadContent(data.link);
                window.open(data.print, "_blank");
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
        .finally(() => {
            hideLoading();
        });
    }

    // ======= Aksi approve/delete/loadDetail (lengkap) =======
    function confirmApprove(e) {
        e.preventDefault();
        let id = e.currentTarget.closest('div').id;
        if (!id) return;

        if (confirm('Yakin ingin approve data ini?')) {
            const csrfInput = document.querySelector('[name="<?= csrf_token() ?>"]');
            const csrfToken = csrfInput ? csrfInput.value : '';

            fetch('<?php echo site_url("formuliradmin/approve/") ?>' + id, {
                method: 'POST',
                headers: {
                    'X-Requested-With': 'XMLHttpRequest',
                    'X-CSRF-TOKEN': csrfToken
                }
            })
            .then(res => res.json())
            .then(data => {
                if (data.res) {
                    if (typeof table !== 'undefined') table.fetchData({ reload: true });
                    sayAlert('successModal', 'Berhasil', 'Data berhasil diapprove', 'success');
                } else {
                    sayAlert('errorModal', 'Gagal', data.msg || 'Approve gagal dilakukan', 'warning');
                }

                if (data.xname && data.xhash) {
                    document.querySelectorAll('[name="' + data.xname + '"]').forEach(input => input.value = data.xhash);
                }
            })
            .catch(err => sayAlert('errorModal', 'Error', 'Terjadi kesalahan sistem', 'warning'));
        }
    }

    function deleteItem(e) {
        e.preventDefault();
        let id = e.currentTarget.closest('div').id;
        if (!id) return;

        if (confirm('Yakin ingin menghapus data ini?')) {
            const csrfInput = document.querySelector('[name="<?= csrf_token() ?>"]');
            const csrfToken = csrfInput ? csrfInput.value : '';

            fetch('<?php echo site_url("formuliradmin/delete/") ?>' + id, {
                method: 'POST',
                headers: {
                    'X-Requested-With': 'XMLHttpRequest',
                    'X-CSRF-TOKEN': csrfToken
                }
            })
            .then(res => res.json())
            .then(data => {
                if (data.res) {
                    if (typeof table !== 'undefined') table.fetchData({ reload: true });
                    sayAlert('successModal', 'Berhasil', 'Data berhasil dihapus', 'success');
                } else {
                    sayAlert('errorModal', 'Gagal', data.msg || 'Hapus gagal dilakukan', 'warning');
                }

                if (data.xname && data.xhash) {
                    document.querySelectorAll('[name="' + data.xname + '"]').forEach(input => input.value = data.xhash);
                }
            })
            .catch(err => sayAlert('errorModal', 'Error', 'Terjadi kesalahan sistem', 'warning'));
        }
    }

    function loadDetail(id) {
        const url = '<?php echo site_url("formuliradmin/detaillist/") ?>' + id;
        const tbody = document.querySelector('#detail-body');
        tbody.innerHTML = '<tr><td colspan="7" class="text-center">Loading...</td></tr>';

        fetch(url)
            .then(response => response.json())
            .then(data => {
                tbody.innerHTML = '';
                if (data.items && data.items.length > 0) {
                    data.items.forEach(function(row) {
                        let tr = '<tr>';
                        row.forEach(function(col) {
                            tr += '<td>' + col + '</td>';
                        });
                        tr += '</tr>';
                        tbody.innerHTML += tr;
                    });
                } else {
                    tbody.innerHTML = '<tr><td colspan="7" class="text-center">Tidak ada data</td></tr>';
                }
                if (typeof bootstrap !== 'undefined') {
                    const modalEl = document.getElementById('modalDetail');
                    const modal = new bootstrap.Modal(modalEl);
                    modal.show();
                } else {
                    $('#modalDetail').modal('show');
                }
            })
            .catch(error => {
                console.error(error);
                tbody.innerHTML = '<tr><td colspan="7" class="text-center text-danger">Error load data</td></tr>';
                if (typeof bootstrap !== 'undefined') {
                    const modalEl = document.getElementById('modalDetail');
                    const modal = new bootstrap.Modal(modalEl);
                    modal.show();
                } else {
                    $('#modalDetail').modal('show');
                }
            });
    }

    // ======= Pastiin #add membuka modalKeranjang (pelanggan ada di modal) =======
    (function(){
        var addBtn = document.querySelector('#add');
        if (!addBtn) return;

        addBtn.addEventListener('click', function () {
            try {
                var modalEl = document.getElementById('modalKeranjang');
                if (!modalEl) {
                    console.warn('modalKeranjang tidak ditemukan di DOM.');
                    return;
                }
                if (typeof bootstrap !== 'undefined') {
                    var modalInstance = new bootstrap.Modal(modalEl);
                    modalInstance.show();
                } else if (typeof $ !== 'undefined' && typeof $('#modalKeranjang').modal === 'function') {
                    $('#modalKeranjang').modal('show');
                } else {
                    console.warn('Bootstrap modal tidak tersedia — pastikan Bootstrap JS dimuat.');
                }
            } catch (err) {
                console.error('Gagal membuka modalKeranjang:', err);
            }
        });
    })();

    // ======= Inisialisasi modalKeranjang ketika terbuka =======
    let ker_layananTable;
    let ker_previewKeranjangTable;

    var modalKeranjangEl = document.getElementById('modalKeranjang');
    if (modalKeranjangEl) {
        modalKeranjangEl.addEventListener('shown.bs.modal', function () {
            // init layanan table
            if (!ker_layananTable) {
                ker_layananTable = createModal({
                    apiUrl: '<?= site_url("formuliradmin/keranjangDataListLayanan") ?>',
                    tableId: 'ker_layanan-table',
                    showFilter: true,
                    treeview: false,
                    numbering: true,
                    itemsPerPage: 10
                });
            } else {
                ker_layananTable.fetchData({ reload: true });
            }

            // init preview keranjang
            if (!ker_previewKeranjangTable) {
                ker_previewKeranjangTable = createModal({
                    apiUrl: '<?= site_url("formuliradmin/keranjangDatalist") ?>',
                    tableId: 'ker_preview-keranjang-table',
                    showFilter: false,
                    treeview: false,
                    numbering: true,
                    itemsPerPage: 100
                });
            } else {
                ker_previewKeranjangTable.fetchData({ reload: true });
            }
            
            setTimeout(function() {
                ker_updateKeranjangCounter();
                ker_calculateGrandTotal();
            }, 500);
        });
    }

    // ======= saat user mengganti pelanggan di modal, simpan ke session via endpoint =======
    document.addEventListener('change', function(e) {
        if (e.target && e.target.id === 'ker_pelanggan_select') {
            const sel = e.target;
            const selected = sel.options[sel.selectedIndex];
            // jika tidak ada value, kita tidak memanggil endpoint (biarkan kosong)
            if (!selected || !selected.value) {
                // optional: hapus session pelanggan via endpoint jika diperlukan
                return;
            }

            const form = new FormData();
            form.append('selectedUserId', selected.value);
            form.append('name', selected.dataset.name || '');
            form.append('email', selected.dataset.email || '');
            form.append('status', selected.dataset.status || '');

            fetch('<?= site_url("formuliradmin/keranjangSetPelanggan") ?>', {
                method: 'POST',
                body: form
            })
            .then(res => res.json())
            .then(json => {
                if (json.xname && json.xhash) {
                    document.querySelectorAll('[name="' + json.xname + '"]').forEach(inp => inp.value = json.xhash);
                }
                // update badge/info
                const badge = document.getElementById('ker_pelanggan_badge');
                const info = document.getElementById('ker_modalPelangganInfo');
                if (json.pelanggan) {
                    if (badge) badge.textContent = (json.pelanggan.name || '-') + ' — ' + (json.pelanggan.email || '-');
                    if (info) {
                        info.style.display = 'block';
                        info.textContent = 'Untuk pelanggan: ' + (json.pelanggan.name || '-') + ' — ' + (json.pelanggan.email || '-') + ' — ' + (json.pelanggan.status || '-');
                    }
                }
                // refresh preview keranjang (agar tombol checkout diperiksa kembali)
                if (typeof ker_previewKeranjangTable !== 'undefined' && ker_previewKeranjangTable) {
                    ker_previewKeranjangTable.fetchData({ reload: true });
                    setTimeout(function() {
                        ker_updateKeranjangCounter();
                        ker_calculateGrandTotal();
                    }, 400);
                }
            })
            .catch(err => {
                console.warn('Gagal set pelanggan ke session:', err);
            });
        }
    });

    // ======= update counter & tampilkan pelanggan jika ada =======
    function ker_updateKeranjangCounter() {
        fetch('<?= site_url("formuliradmin/keranjangDatalist") ?>')
            .then(res => res.json())
            .then(data => {
                const jumlahItem = data.items ? data.items.length : 0;
                const elCounter = document.getElementById('ker_jumlahItemKeranjang');
                if (elCounter) elCounter.textContent = jumlahItem;

                const keranjangKosong = document.getElementById('ker_keranjangKosong');
                const previewTable = document.getElementById('ker_preview-keranjang-table');
                const btnCheckout = document.getElementById('ker_btnCheckoutFromModal');

                if (jumlahItem === 0) {
                    if (keranjangKosong) keranjangKosong.style.display = 'block';
                    if (previewTable) previewTable.style.display = 'none';
                    if (btnCheckout) btnCheckout.disabled = true;
                } else {
                    if (keranjangKosong) keranjangKosong.style.display = 'none';
                    if (previewTable) previewTable.style.display = 'table';
                    // tombol checkout aktif hanya jika pelanggan telah dipilih
                    if (data.pelanggan && data.pelanggan.user_id) {
                        if (btnCheckout) btnCheckout.disabled = false;
                    } else {
                        if (btnCheckout) btnCheckout.disabled = true;
                    }
                }

                // jika ada info pelanggan dari response, tampilkan di modal header/badge
                if (data.pelanggan) {
                    const infoEl = document.getElementById('ker_modalPelangganInfo');
                    const badge = document.getElementById('ker_pelanggan_badge');
                    const sel = document.getElementById('ker_pelanggan_select');
                    if (infoEl) {
                        infoEl.style.display = 'block';
                        infoEl.textContent = 'Untuk pelanggan: ' + (data.pelanggan.name || '-') + ' — ' + (data.pelanggan.email || '-') + ' — ' + (data.pelanggan.status || '-');
                    }
                    if (badge) badge.textContent = (data.pelanggan.name || '-') + ' — ' + (data.pelanggan.email || '-');
                    // set selected option pada select (jika ada)
                    if (sel && data.pelanggan.user_id) {
                        try {
                            sel.value = data.pelanggan.user_id;
                        } catch(e){}
                    }
                }
            })
            .catch(err => {
                console.error('Error updating counter:', err);
            });
    }

    // ======= hitung grand total =======
    function ker_calculateGrandTotal() {
        fetch('<?= site_url("formuliradmin/keranjangDatalist") ?>')
            .then(res => res.json())
            .then(data => {
                if (data.items && data.items.length > 0) {
                    let grandTotal = 0;
                    data.items.forEach(item => {
                        const totalStr = item[4]; // kolom Total di output keranjang
                        if (totalStr) {
                            // ekstrak angka dari string "Rp 1.234.567"
                            const digits = totalStr.replace(/[^0-9]/g, '');
                            const totalNum = parseInt(digits || '0', 10);
                            if (!isNaN(totalNum)) {
                                grandTotal += totalNum;
                            }
                        }
                    });
                    
                    const el = document.getElementById('ker_grandTotal');
                    if (el) el.textContent = 'Rp ' + new Intl.NumberFormat('id-ID').format(grandTotal);
                } else {
                    const el = document.getElementById('ker_grandTotal');
                    if (el) el.textContent = 'Rp 0';
                }
            })
            .catch(err => {
                console.error('Error calculating grand total:', err);
            });
    }

    // ======= Delegated click handlers (Masukkan, Refresh, Hapus, Checkout) =======
    document.addEventListener('click', function(e) {
        // Tombol masukkan (.btnMasukkan)
        if (e.target.closest('.btnMasukkan')) {
            let btn = e.target.closest('.btnMasukkan');
            let tr = btn.closest('tr');

            // sebelum menambahkan item, pastikan pelanggan sudah dipilih di modal
            const selPel = document.getElementById('ker_pelanggan_select');
            const selectedPel = selPel ? selPel.value : '';
            if (!selectedPel) {
                if (typeof sayAlert === 'function') {
                    sayAlert('errorModal', 'Pelanggan belum dipilih', 'Pilih pelanggan di bagian atas modal sebelum menambahkan layanan.', 'warning');
                } else {
                    alert('Pilih pelanggan di bagian atas modal sebelum menambahkan layanan.');
                }
                return;
            }

            let biaya   = parseFloat(btn.dataset.biaya) || 0;
            let diskon  = parseFloat(btn.dataset.diskon) || 0;

            let jumlahInput = tr.querySelector('.jumlah');
            let jumlah  = parseInt(jumlahInput ? jumlahInput.value : 1) || 1;
            if (jumlah < 1) jumlah = 1;

            let total = (biaya * jumlah) * (1 - (diskon / 100));

            let data = {
                detUjiKode: btn.dataset.kode,
                detAlat: btn.dataset.alat,
                detBiaya: biaya,
                detParameter: btn.dataset.parameter,
                detDiskon: diskon,
                detInstansi: btn.dataset.instansi || '',
                detJumlah: jumlah,
                detKeterangan: tr.querySelector('.keterangan') ? tr.querySelector('.keterangan').value : '',
                detTotal: total
            };

            if (!data.detUjiKode) {
                sayAlert('errorModal', 'Gagal', 'Kode Uji tidak ditemukan.', 'error');
                return;
            }
            if (parseInt(data.detJumlah) < 1) {
                sayAlert('errorModal', 'Gagal', 'Jumlah minimal 1.', 'error');
                return;
            }

            let formData = new FormData();
            for (const key in data) {
                formData.append(key, data[key]);
            }

            let csrfInput = document.querySelector('input[name="<?= csrf_token() ?>"]');
            if (csrfInput) {
                formData.append('<?= csrf_token() ?>', csrfInput.value);
            }

            fetch('<?= site_url("formuliradmin/keranjangSubmit") ?>', {
                method: 'POST',
                body: formData
            })
            .then(res => res.json())
            .then(res => {
                if (res.xname && res.xhash) {
                    let csrfField = document.querySelector('input[name="' + res.xname + '"]');
                    if (csrfField) {
                        csrfField.value = res.xhash;
                    }
                }

                if (res.res === true) {
                    if (typeof table !== 'undefined') table.fetchData({ reload: true });
                    
                    if (ker_previewKeranjangTable) {
                        ker_previewKeranjangTable.fetchData({ reload: true });
                        setTimeout(function() {
                            ker_updateKeranjangCounter();
                            ker_calculateGrandTotal();
                        }, 500);
                    }
                    
                    if (jumlahInput) jumlahInput.value = 1;
                    if (tr.querySelector('.keterangan')) tr.querySelector('.keterangan').value = '';
                    
                    sayAlert('successModal', 'Berhasil', res.msg ?? 'Layanan berhasil ditambahkan ke keranjang.', 'success');
                } else {
                    sayAlert('errorModal', 'Gagal', res.msg ?? 'Terjadi kesalahan saat menambahkan ke keranjang.', 'error');
                }
            })
            .catch(() => {
                sayAlert('errorModal', 'Gagal', 'Terjadi kesalahan koneksi ke server.', 'error');
            });
        }

        // Tombol Refresh Keranjang
        if (e.target.closest('#ker_btnRefreshKeranjang')) {
            e.preventDefault();
            if (ker_previewKeranjangTable) {
                ker_previewKeranjangTable.fetchData({ reload: true });
                setTimeout(function() {
                    ker_updateKeranjangCounter();
                    ker_calculateGrandTotal();
                }, 500);
            }
        }

        // Hapus item dari preview
        if (e.target.closest('.btn-delete-item')) {
            e.preventDefault();
            keranjangDeleteItem(e);
        }
        
        // Tombol Checkout dari Modal
        if (e.target.closest('#ker_btnCheckoutFromModal')) {
            e.preventDefault();
            
            if (confirm('Apakah Anda yakin ingin melakukan checkout?')) {
                ker_doCheckout();
            }
        }
    });

    // ======= Delete item dari keranjang =======
    function keranjangDeleteItem(e) {
        e.preventDefault();
        let idx = e.target.closest('[data-index]') ? e.target.closest('[data-index]').getAttribute('data-index') : null;
        if (!idx) {
            idx = e.target.closest('.btn-delete-item') ? e.target.closest('.btn-delete-item').getAttribute('data-index') : null;
        }
        if (!idx) {
            idx = e.currentTarget && e.currentTarget.dataset && e.currentTarget.dataset.index ? e.currentTarget.dataset.index : null;
        }

        if (!idx && e.currentTarget) {
            idx = e.currentTarget.getAttribute('data-index') || null;
        }

        if (!idx) {
            const span = e.target.closest('span[data-index]');
            if (span) idx = span.getAttribute('data-index');
        }

        if (!idx) return;

        if (!confirm("Apakah Anda yakin ingin menghapus item ini dari keranjang?")) return;

        fetch('<?= site_url("formuliradmin/keranjangDelete/") ?>' + idx, {
            method: 'POST',
            headers: {
                'X-Requested-With': 'XMLHttpRequest'
            }
        })
        .then(res => res.json())
        .then(data => {
            if (data.xname && data.xhash) {
                document.querySelectorAll('[name="' + data.xname + '"]').forEach(input => {
                    input.value = data.xhash;
                });
            }

            if (data.res === true) {
                if (typeof table !== 'undefined') table.fetchData({ reload: true });
                
                if (ker_previewKeranjangTable) {
                    ker_previewKeranjangTable.fetchData({ reload: true });
                    setTimeout(function() {
                        ker_updateKeranjangCounter();
                        ker_calculateGrandTotal();
                    }, 500);
                }
                
                sayAlert('successModal', 'Sukses', data.msg, 'success');
            } else {
                sayAlert('errorModal', 'Gagal', data.msg ?? 'Hapus item gagal.', 'error');
            }
        })
        .catch(err => {
            sayAlert('errorModal', 'Error', 'Terjadi kesalahan koneksi ke server.', 'error');
        });
    }

    // ======= Checkout =======
    function ker_doCheckout() {
        const formData = new FormData();
        const csrfInput = document.querySelector('input[name="<?= csrf_token() ?>"]');
        if (csrfInput) {
            formData.append('<?= csrf_token() ?>', csrfInput.value);
        }

        // ambil selected user id dari select di modal (bukan di header)
        const sel = document.getElementById('ker_pelanggan_select');
        const selectedVal = sel ? sel.value : '';
        if (!selectedVal) {
            if (typeof sayAlert === 'function') {
                sayAlert('errorModal', 'Pelanggan tidak ditemukan', 'Pilih pelanggan di bagian atas modal sebelum checkout.', 'warning');
            } else {
                alert('Pilih pelanggan di bagian atas modal sebelum checkout.');
            }
            return;
        }
        formData.append('selectedUserId', selectedVal);

        fetch('<?= site_url("formuliradmin/keranjangCheckout") ?>', {
            method: 'POST',
            body: formData
        })
        .then(res => res.json())
        .then(data => {
            if (data.xname && data.xhash) {
                document.querySelectorAll('[name="' + data.xname + '"]').forEach(input => {
                    input.value = data.xhash;
                });
            }

            if (data.res === true) {
                if (typeof table !== 'undefined') table.fetchData({ reload: true });
                
                if (ker_previewKeranjangTable) {
                    ker_previewKeranjangTable.fetchData({ reload: true });
                    setTimeout(function() {
                        ker_updateKeranjangCounter();
                        ker_calculateGrandTotal();
                    }, 500);
                }
                
                const modalEl = document.getElementById('modalKeranjang');
                if (typeof bootstrap !== 'undefined') {
                    const modal = bootstrap.Modal.getInstance(modalEl);
                    if (modal) modal.hide();
                } else if (typeof $ !== 'undefined' && typeof $('#modalKeranjang').modal === 'function') {
                    $('#modalKeranjang').modal('hide');
                }
                
                sayAlert('successModal', 'Sukses', data.msg, 'success');
            } else {
                sayAlert('errorModal', 'Gagal', data.msg ?? 'Checkout gagal.', 'error');
            }
        })
        .catch(error => {
            sayAlert('errorModal', 'Error', 'Terjadi kesalahan koneksi ke server.', 'error');
        });
    }
</script>
