<!-- modal tabel utama -->
<div class="row">
    <div class="col-md-12">
        <div class="card">
            <div class="card-header d-flex justify-content-between align-items-center">
                <div class="d-flex align-items-center" style="gap:12px;">
                    <label class="card-title mb-0"><?php echo $title ?></label>
                </div>

                <div>
                    <button id="add" class="btn btn-primary">
                        <i class="bi bi-plus-circle-dotted"></i> Pesan Layanan
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


<!-- modal keranjang layanan -->
<div class="modal fade" id="modalKeranjang" data-bs-backdrop="static" data-bs-keyboard="false" tabindex="-1"
    aria-labelledby="staticBackdropLabel" aria-hidden="true">
    <div class="modal-dialog modal-xl" role="document" style="margin: 2% auto">
        <div class="modal-content">
            <div class="modal-header">
                <h5 class="modal-title">Pilih Layanan</h5>
                <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
            </div>

            <div class="modal-body">
                <div class="mb-3">
                    <h6 class="fw-bold text-primary mb-0">
                        <i class="bi bi-list-check"></i> Daftar Layanan Tersedia
                    </h6>
                </div>

                <hr class="my-3">

                <!-- pilih kategori -->
                <div class="row mb-4 align-items-end">
                    <!-- Kategori -->
                    <div class="col-md-8">
                        <label class="form-label mb-1 fw-semibold">Kategori</label>
                        <div class="d-flex gap-2 align-items-center">
                            <select id="jenFilter" class="form-select form-select-sm" style="max-width:60px;">
                                <option value="">— Semua —</option>
                                <?php if (!empty($categories) && (is_array($categories) || is_object($categories))): ?>
                                    <?php foreach ($categories as $c): ?>
                                        <?php
                                            $kode = isset($c->jenKode) ? $c->jenKode : (isset($c['jenKode']) ? $c['jenKode'] : '');
                                            $nama = isset($c->jenNama) && trim((string)$c->jenNama) !== '' ? $c->jenNama : $kode;
                                        ?>
                                        <option value="<?= esc($kode) ?>"><?= esc($nama) ?></option>
                                    <?php endforeach; ?>
                                <?php endif; ?>
                            </select>
                        </div>
                    </div>

                   <!-- Pelanggan -->
                    <div class="col-md-4 d-flex flex-column justify-content-end" style="padding-left: 20px;">
                    <label class="form-label mb-1 fw-semibold">Pelanggan</label>
                    <!-- pilih searching -->
                    <div class="d-flex justify-content-end align-items-center gap-2">
                        <select id="ker_pelanggan_select" class="form-select form-select-sm" style="max-width:400px;">
                        <option value=""></option>
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
                    </div>
                    </div>

                </div>

                <!-- CSRF Token -->
                <input type="hidden" name="<?= csrf_token() ?>" value="<?= csrf_hash() ?>" />

                <!-- tabel keranjang pilih layanan -->
                <div class="mb-4">
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

                <!-- tabel keranjang preview layanan -->
                <div>
                    <div class="d-flex justify-content-between align-items-center mb-3">
                        <h6 class="fw-bold text-success mb-0">
                            <i class="bi bi-cart3"></i> Keranjang Anda 
                            (<span id="ker_jumlahItemKeranjang">0</span> Item)
                        </h6>
                    </div>

                    <div id="ker_keranjangKosong" class="alert alert-warning text-center" style="display:none;">
                        <i class="bi bi-cart-x"></i> Keranjang masih kosong. Silakan pilih layanan di atas.
                    </div>
                    
                    <table id="ker_preview-keranjang-table" class="saytable border-top-bottom">
                        <thead>
                            <tr>
                                <th style="width:5%">No</th>
                                <th style="width:22%">Parameter</th>
                                <th style="width:22%">Instrumen/Alat/Tempat</th>
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
                            <tr class="table-active align-middle">
                                <td colspan="8">
                                    <div class="d-flex justify-content-end">
                                        <div class="fw-bold fs-5">
                                            TOTAL KESELURUHAN:
                                            <span id="grandTotal" class="text-primary">Rp 0</span>
                                        </div>
                                    </div>
                                </td>
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

<!-- Modal detail Pesanan -->
<div class="modal fade" id="modalDetail" data-bs-backdrop="static" data-bs-keyboard="false" tabindex="-1" aria-labelledby="staticBackdropLabel" aria-hidden="true">
    <div class="modal-dialog modal-xl" role="document" style="margin: 2% auto">
        <div class="modal-content">
            <div class="modal-header">
                <h5 class="modal-title">Detail Layanan</h5>
                <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close">
                </button>
            </div>
            <div class="modal-body">
                <table class="table table-bordered">
                    <thead>
                        <tr>
                            <th width="5%">No</th>
                            <th width="30%">Parameter</th>
                            <th width="20%">Biaya</th>
                            <th width="15%">Jumlah</th>
                            <th width="15%">Keterangan</th>
                            <th width="15%">Status</th>
                            <th width="15%">Keterangan Manajer</th>
                            <th width="10%">Acc</th>
                        </tr>
                    </thead>
                    <tbody id="detail-body">
                        <tr><td colspan="6" class="text-center">Loading...</td></tr>
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



<script>
    //   Utility: buildApiUrlWithOptionalParam & normalizeDoubleQuestion
    //   - mencegah pembentukan URL seperti "...?A?page=1"
    function buildApiUrlWithOptionalParam(path, key, value) {
        try {
            const u = new URL(path, window.location.origin);
            const params = new URLSearchParams(u.search);

            if (typeof key === 'string' && key !== '' && typeof value !== 'undefined' && value !== null && String(value) !== '') {
                params.set(key, String(value));
            } else {
                if (typeof key === 'string' && key !== '') params.delete(key);
            }

            const s = params.toString();
            // gunakan pathname agar sesuai helper createTable yang biasanya memakai path relatif
            return u.pathname + (s ? '?' + s : '');
        } catch (e) {
            if (typeof key === 'string' && key !== '' && typeof value !== 'undefined' && value !== null && String(value) !== '') {
                return path + '?' + encodeURIComponent(key) + '=' + encodeURIComponent(String(value));
            }
            return path;
        }
    }

    function normalizeDoubleQuestion(url) {
        if (typeof url !== 'string') return url;
        // Replace occurrences of '?...?' to '?...&' and collapse duplicate &.
        // First, replace any '?...?' pattern
        url = url.replace(/\?([^?]*)\?/, '?$1&');
        // remove duplicate ampersands
        url = url.replace(/&{2,}/g, '&');
        // fix any trailing & or ? if needed
        url = url.replace(/\?&/, '?');
        if (url.endsWith('&')) url = url.slice(0, -1);
        return url;
    }

    function buildKerLayananUrl(jen) {
    var base = '<?= site_url("formuliradmin/keranjangDataListLayanan") ?>';
    return buildApiUrlWithOptionalParam(base, (jen && jen !== '') ? 'jenKode' : '', jen || '');
}

function createOrRefreshKerLayananTable(jen) {
    const api = normalizeDoubleQuestion(buildKerLayananUrl(jen || ''));

    if (typeof ker_layananTable !== 'undefined' && ker_layananTable && typeof ker_layananTable.getConfig === 'function') {
        try {
            const cfg = ker_layananTable.getConfig();
            if (cfg && typeof cfg === 'object') cfg.apiUrl = api;
            if (typeof ker_layananTable.fetchData === 'function') { ker_layananTable.fetchData({ reload: true }); return; }
        } catch (err) {
            try { if (typeof ker_layananTable.destroy === 'function') ker_layananTable.destroy(); } catch(e){}
            ker_layananTable = null;
        }
    }

    ker_layananTable = createModal({
        apiUrl: api,
        tableId: 'ker_layanan-table',
        showFilter: true,
        treeview: true,
        numbering: true,
        itemsPerPage: 10,
        preserveQuery: true
    });

    if (ker_layananTable && typeof ker_layananTable.fetchData === 'function' && typeof ker_layananTable.getConfig === 'function') {
        const orig = ker_layananTable.fetchData.bind(ker_layananTable);
        ker_layananTable.fetchData = function(opts = {}) {
            try {
                const cfg = ker_layananTable.getConfig();
                if (cfg && cfg.apiUrl && typeof cfg.apiUrl === 'string') cfg.apiUrl = normalizeDoubleQuestion(cfg.apiUrl);
            } catch (err) { console.warn('Normalization (ker_layananTable) failed:', err); }
            return orig(opts);
        };
    }
}

function applyJenFilter() {
    try {
        const sel = document.getElementById('jenFilter');
        if (!sel) return;
        const jen = (sel.value || '').toString().trim();

        // update modal layanan
        createOrRefreshKerLayananTable(jen);

        // opsional: update main table juga
        try {
            if (typeof table !== 'undefined' && table && typeof table.getConfig === 'function') {
                const cfg = table.getConfig();
                cfg.apiUrl = buildApiUrlWithOptionalParam('<?php echo site_url("formuliradmin/datalist") ?>', (jen !== '') ? 'jenKode' : '', jen);
                cfg.apiUrl = normalizeDoubleQuestion(cfg.apiUrl);
                if (typeof table.fetchData === 'function') table.fetchData({ reload: true });
            }
        } catch (err) { console.warn('Failed to update main table with jen filter:', err); }
    } catch (e) { console.warn('applyJenFilter error', e); }
}

(function attachJenFilterListener(){
    const sel = document.getElementById('jenFilter');
    if (!sel) return;
    sel.addEventListener('change', function() { applyJenFilter(); });
})();

    // ambil parameter URL
    var urlParams = new URLSearchParams(window.location.search);
    var kategoriFromUrl = urlParams.get('kategoriLayanan');

    // Base API URL (normalisasi)
    var baseApiUrl = '<?php echo site_url("formuliradmin/datalist") ?>';
    if (kategoriFromUrl) {
        baseApiUrl = buildApiUrlWithOptionalParam(baseApiUrl, 'kategoriLayanan', kategoriFromUrl);
        baseApiUrl = normalizeDoubleQuestion(baseApiUrl);
    }

    // Inisialisasi table (fungsi createTable diasumsikan sudah ada di project)
    table = createTable({
        apiUrl: baseApiUrl,
        dataSrc: 'items'
    });

    // Patch table.fetchData untuk menormalisasi apiUrl bila helper createTable menambahkan '?ganda'
    if (table && typeof table.getConfig === 'function' && typeof table.fetchData === 'function') {
        const origFetch = table.fetchData.bind(table);
        table.fetchData = function(opts = {}) {
            try {
                const cfg = table.getConfig();
                if (cfg && cfg.apiUrl && typeof cfg.apiUrl === 'string') {
                    cfg.apiUrl = normalizeDoubleQuestion(cfg.apiUrl);
                }
            } catch (err) { console.warn('Normalization (table) failed:', err); }
            return origFetch(opts);
        };
    }

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
                tableConfig.apiUrl = buildApiUrlWithOptionalParam('<?php echo site_url("formuliradmin/datalist") ?>', 'kategoriLayanan', selectedValue);
            }
            tableConfig.apiUrl = normalizeDoubleQuestion(tableConfig.apiUrl);

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

    function confirmApprove(e) {
    try {
        if (e && typeof e.preventDefault === 'function') e.preventDefault();
        // dapatkan id dari elemen pembungkus (div#<id>)
        const el = (e && e.currentTarget) ? e.currentTarget : (e && e.target) ? e.target : null;
        let id = null;
        if (el && typeof el.closest === 'function') {
            const div = el.closest('div[id]');
            if (div) id = div.id;
        }
        // fallback: cari terdekat dengan attribute id pada parent
        if (!id && e && e.target) {
            const maybe = e.target.closest && e.target.closest('div') ? e.target.closest('div').id : null;
            if (maybe) id = maybe;
        }
        if (!id) return;

        // gunakan sayConfirm jika tersedia (konsisten dengan UI)
        const doApprove = function() {
            const csrf = getCsrfTokenFromPage();
const headers = { 'X-Requested-With': 'XMLHttpRequest' };

// set header menggunakan nama token dinamis jika tersedia
if (csrf && csrf.name && csrf.value) {
    headers[csrf.name] = csrf.value;
}

// jika server mengharuskan token di body, gunakan FormData
const body = new FormData();
// tambahkan token juga ke body agar kompatibel
if (csrf && csrf.name && csrf.value) body.append(csrf.name, csrf.value);

fetch('<?= site_url("formuliradmin/approve/") ?>' + id, {
    method: 'POST',
    headers: headers,
    body: body
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
        };

        if (typeof sayConfirm === 'function') {
            sayConfirm('Konfirmasi ', 'Setujui transaksi ini?', function() {
                doApprove();
            }, 'primary', 'Setujui');
           
        } else {
            // fallback ke native confirm jika sayConfirm tidak tersedia
            if (confirm('Yakin ingin approve data ini?')) {
                doApprove();
            }
        }
    } catch (err) {
        console.error('confirmApprove error', err);
    }
}

    /* deleteItemFromPreview */
/* deleteItemFromPreview */
/* ganti fungsi lama dengan ini */
function deleteItemFromPreview(eOrEl) {
    let el;
    if (eOrEl instanceof Event) {
        eOrEl.preventDefault();
        el = eOrEl.currentTarget || eOrEl.target;
    } else el = eOrEl;

    if (el && !el.hasAttribute('data-index')) el = el.closest('[data-index]');
    if (!el) {
        console.warn('Element untuk delete tidak ditemukan.');
        return;
    }

    let idx = el.getAttribute('data-index') || '';
    if (!idx) {
        console.warn('Data-index tidak ditemukan pada element hapus.');
        return;
    }

    // Normalisasi: jika idx berisi prefix "item-0" -> ambil angka di belakangnya
    if (typeof idx === 'string' && idx.indexOf('item-') === 0) {
        idx = idx.replace(/^item-/, '');
    }

    // Pastikan idx aman untuk URL
    idx = encodeURIComponent(String(idx));

    // Siapkan CSRF jika ada
    const csrfInput = document.querySelector('input[name="<?= csrf_token() ?>"]');
    const form = new FormData();

    if (csrfInput) {
        form.append('<?= csrf_token() ?>', csrfInput.value);
    } else {
        // fallback ke token dinamis
        const csrf = getCsrfTokenFromPage();
        if (csrf && csrf.name && csrf.value)
            form.append(csrf.name, csrf.value);
    }

    // Kirim POST ke endpoint keranjangDelete (sesuai routes yang ada)
    fetch('<?= site_url("formuliradmin/keranjangDelete/") ?>' + idx, {
        method: 'POST',
        body: form,
        headers: { 'X-Requested-With': 'XMLHttpRequest' }
    })
    .then(res => res.json())
    .then(data => {
        // update CSRF token jika server mengembalikan
        if (data.xname && data.xhash) {
            document.querySelectorAll('[name="' + data.xname + '"]').forEach(input => input.value = data.xhash);
        }

        if (data.res === true) {
            if (typeof table !== 'undefined' && typeof table.fetchData === 'function')
                table.fetchData({ reload: true });

            if (typeof ker_previewKeranjangTable !== 'undefined' &&
                ker_previewKeranjangTable &&
                typeof ker_previewKeranjangTable.fetchData === 'function') {

                ker_previewKeranjangTable.fetchData({ reload: true });

                setTimeout(function() {
                    ker_updateKeranjangCounter();
                    ker_calculateGrandTotal();
                }, 400);
            } else {
                // fallback manual update
                ker_updateKeranjangCounter();
                ker_calculateGrandTotal();
            }

            if (typeof sayAlert === 'function')
                sayAlert('successModal', 'Sukses', data.msg || 'Item berhasil dihapus.', 'success');
        } else {
            if (typeof sayAlert === 'function')
                sayAlert('errorModal', 'Gagal', data.msg || 'Hapus item gagal.', 'warning');
        }
    })
    .catch(err => {
        console.error('deleteItemFromPreview error', err);
        if (typeof sayAlert === 'function')
            sayAlert('errorModal', 'Error', 'Terjadi kesalahan koneksi ke server.', 'error');
    });
}



    /* loadDetail (sama seperti implementasi kamu) */
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
            $('#modalDetail').modal('show');
        })
        .catch(error => {
            console.error(error);
            tbody.innerHTML = '<tr><td colspan="7" class="text-center text-danger">Error load data</td></tr>';
            $('#modalDetail').modal('show');
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
            const layananApi = buildApiUrlWithOptionalParam('<?= site_url("formuliradmin/keranjangDataListLayanan") ?>', '', '');
            if (!ker_layananTable) {
                ker_layananTable = createModal({
                    apiUrl: normalizeDoubleQuestion(layananApi),
                    tableId: 'ker_layanan-table',
                    showFilter: true,
                    treeview: true,
                    numbering: true,
                    itemsPerPage: 10
                });
                // patch fetchData untuk normalize api url
                if (ker_layananTable && typeof ker_layananTable.fetchData === 'function' && typeof ker_layananTable.getConfig === 'function') {
                    const orig = ker_layananTable.fetchData.bind(ker_layananTable);
                    ker_layananTable.fetchData = function(opts = {}) {
                        try {
                            const cfg = ker_layananTable.getConfig();
                            if (cfg && cfg.apiUrl && typeof cfg.apiUrl === 'string') {
                                cfg.apiUrl = normalizeDoubleQuestion(cfg.apiUrl);
                            }
                        } catch (err) { console.warn('Normalization (ker_layananTable) failed:', err); }
                        return orig(opts);
                    };
                }
            } else {
                try { ker_layananTable.fetchData({ reload: true }); } catch(e){}
            }

            // init preview keranjang
            const previewApi = '<?= site_url("formuliradmin/keranjangDatalist") ?>';
            if (!ker_previewKeranjangTable) {
                ker_previewKeranjangTable = createModal({
                    apiUrl: normalizeDoubleQuestion(previewApi),
                    tableId: 'ker_preview-keranjang-table',
                    showFilter: false,
                    treeview: false,
                    numbering: true,
                    itemsPerPage: 100,
                    // hook supaya tidak menumpuk
                    onData: function(items) {
                        const tbody = document.querySelector('#ker_preview-keranjang-table-body');
                        if (tbody) tbody.innerHTML = ''; // clear sebelum isi ulang
                    }
                });

                // force reload behavior (no append)
                if (ker_previewKeranjangTable && typeof ker_previewKeranjangTable.fetchData === 'function') {
                    const orig = ker_previewKeranjangTable.fetchData.bind(ker_previewKeranjangTable);
                    ker_previewKeranjangTable.fetchData = function(opts = {}) {
                        return orig(Object.assign({}, opts, { reload: true }));
                    };
                }
            } else {
                try { ker_previewKeranjangTable.fetchData({ reload: true }); } catch(e){}
            }
            
            // bersihkan preview tbody sebelum render baru (defensive)
            const previewBody = document.getElementById('ker_preview-keranjang-table-body');
            if (previewBody) previewBody.innerHTML = '';

            setTimeout(function() {
                ker_updateKeranjangCounter();
                ker_calculateGrandTotal();
            }, 400);
        });

        // saat modal hide -> bersihkan instance preview agar tidak menyimpan state yang aneh
        modalKeranjangEl.addEventListener('hidden.bs.modal', function() {
            // optional: destroy instances if helper memberi method destroy
            try {
                if (ker_layananTable && typeof ker_layananTable.destroy === 'function') { ker_layananTable.destroy(); ker_layananTable = null; }
            } catch(e){}
            try {
                if (ker_previewKeranjangTable && typeof ker_previewKeranjangTable.destroy === 'function') { ker_previewKeranjangTable.destroy(); ker_previewKeranjangTable = null; }
            } catch(e){}
        });
    }

    /**
 * Ambil CSRF token dari input hidden di halaman (nama token dinamis)
 * Return { name: string, value: string } or null
 */
function getCsrfTokenFromPage() {
    try {
        // Cari input hidden yang berisi token (nama dinamis)
        const inputs = document.querySelectorAll('input[type="hidden"]');
        for (let i = 0; i < inputs.length; i++) {
            const inp = inputs[i];
            // heuristik: nama token di CI biasanya panjang & acak, tapi value mengandung hash -> gunakan token config
            // Kita cek apakah nama input bukan "_method" dan value panjang > 8
            if (!inp.name) continue;
            if (inp.name.toLowerCase() === '_method') continue;
            if ((inp.value || '').toString().length > 8) {
                return { name: inp.name, value: inp.value };
            }
        }
    } catch (err) {
        console.warn('getCsrfTokenFromPage error', err);
    }
    return null;
}

(function(){
  const sel = document.getElementById('ker_pelanggan_select');
  if (!sel) return;

  async function sendAndRefresh() {
    const opt = sel.options[sel.selectedIndex];
    const val = sel.value || '';
    if (!val) return;

    const form = new FormData();
    form.append('selectedUserId', val);
    form.append('name', opt?.dataset?.name || '');
    form.append('email', opt?.dataset?.email || '');
    form.append('status', opt?.dataset?.status || '');

    // CSRF jika ada
    const csrf = getCsrfTokenFromPage();
    if (csrf && csrf.name && csrf.value) form.append(csrf.name, csrf.value);

    try {
      const res = await fetch('<?= site_url("formuliradmin/keranjangSetPelanggan") ?>', {
        method: 'POST',
        body: form,
        headers: { 'X-Requested-With': 'XMLHttpRequest' }
      });
      const j = await res.json();

      // update CSRF token jika server kembalikan
      if (j.xname && j.xhash) {
        let f = document.querySelector('input[name="'+j.xname+'"]');
        if (f) f.value = j.xhash;
        else { let h=document.createElement('input'); h.type='hidden'; h.name=j.xname; h.value=j.xhash; document.body.appendChild(h); }
      }

      // update info pelanggan di modal (jika elemen tersedia)
      const infoEl = document.getElementById('ker_modalPelangganInfo');
      if (infoEl && j.pelanggan) {
        infoEl.style.display = 'block';
        infoEl.textContent = 'Untuk pelanggan: ' + (j.pelanggan.name || '-') + ' — ' + (j.pelanggan.email || '-') + ' — ' + (j.pelanggan.status || '-');
      }

      // Refresh preview keranjang (prioritas)
      if (typeof ker_previewKeranjangTable !== 'undefined' && ker_previewKeranjangTable && typeof ker_previewKeranjangTable.fetchData === 'function') {
        ker_previewKeranjangTable.fetchData({ reload: true });
      } else {
        ker_updateKeranjangCounter();
        ker_calculateGrandTotal();
      }

      // *** tambahan: Refresh daftar layanan di modal (ker_layananTable) ***
      if (typeof ker_layananTable !== 'undefined' && ker_layananTable && typeof ker_layananTable.fetchData === 'function') {
        try {
          // jika ada filter kategori aktif, pastikan apiUrl konsisten lalu reload
          const cfg = (typeof ker_layananTable.getConfig === 'function') ? ker_layananTable.getConfig() : null;
          if (cfg && cfg.apiUrl && typeof cfg.apiUrl === 'string') {
            cfg.apiUrl = normalizeDoubleQuestion(cfg.apiUrl);
          }
        } catch (err) { /* ignore */ }
        ker_layananTable.fetchData({ reload: true });
      }

      // Refresh main table (jika ada)
      if (typeof table !== 'undefined' && table && typeof table.fetchData === 'function') {
        table.fetchData({ reload: true });
      }

      // update grand total langsung dari response jika tersedia
      if (typeof j.grand_total !== 'undefined') {
        const el = document.getElementById('ker_grandTotal') || document.getElementById('grandTotal');
        if (el) el.textContent = 'Rp ' + new Intl.NumberFormat('id-ID').format(j.grand_total);
      }

      // update tombol checkout
      const btnCheckout = document.getElementById('ker_btnCheckoutFromModal');
      if (btnCheckout) {
        if ((j.items_count || 0) > 0 && j.pelanggan && j.pelanggan.user_id) btnCheckout.disabled = false;
        else btnCheckout.disabled = true;
      }
    } catch (err) {
      console.error('set pelanggan error', err);
      // Pastikan tabel tetap di-refresh walau request gagal
      if (typeof ker_previewKeranjangTable !== 'undefined' && ker_previewKeranjangTable && typeof ker_previewKeranjangTable.fetchData === 'function') {
        ker_previewKeranjangTable.fetchData({ reload: true });
      } else {
        ker_updateKeranjangCounter();
        ker_calculateGrandTotal();
      }

      // Pastikan juga daftar layanan direfresh walau server keranjangSetPelanggan error
      if (typeof ker_layananTable !== 'undefined' && ker_layananTable && typeof ker_layananTable.fetchData === 'function') {
        try { ker_layananTable.fetchData({ reload: true }); } catch(e){/* ignore */ }
      }

      if (typeof table !== 'undefined' && table && typeof table.fetchData === 'function') {
        table.fetchData({ reload: true });
      }
    }
  }

  sel.addEventListener('change', sendAndRefresh);
  sel.addEventListener('input', sendAndRefresh);
  document.addEventListener('select2:select', sendAndRefresh); // Select2 fallback
})();


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
                    if (keranjangKosong) 
                        keranjangKosong.style.display = 'none';
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

    // ======= hitung grand total (patch) =======
function ker_calculateGrandTotal() {
    fetch('<?= site_url("formuliradmin/keranjangDatalist") ?>')
        .then(res => res.json())
        .then(data => {
            // Cari elemen total; dukung dua id (backwards compatibility)
            let el = document.getElementById('ker_grandTotal') || document.getElementById('grandTotal');
            if (!el) {
                // kalau elemen tidak ada, coba cari span di footer lain
                el = document.querySelector('#ker_grandTotal, #grandTotal');
            }

            if (data.items && data.items.length > 0) {
                let grandTotal = 0;
                data.items.forEach(item => {
                    // Struktur item: [layanan, alat, biayaTampil, jumlah, diskon, total, keterangan, aksi]
                    const totalCell = item[5]; // <-- kolom 'Total' ada di index 5
                    if (!totalCell) return;

                    // totalCell bisa berisi HTML seperti "Rp 1.000.000" atau "<span>Rp 1.000</span>"
                    // ekstrak angka
                    const text = String(totalCell).replace(/<[^>]*>/g, ''); // hapus tag HTML
                    const digits = text.replace(/[^0-9]/g, '');
                    const totalNum = parseInt(digits || '0', 10);
                    if (!isNaN(totalNum) && totalNum > 0) grandTotal += totalNum;
                });

                const formatted = 'Rp ' + new Intl.NumberFormat('id-ID').format(grandTotal);
                if (el) el.textContent = formatted;
            } else {
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
        if (e.target.closest && e.target.closest('.btnMasukkan')) {
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
            } else {
                // fallback: ambil token dinamis jika nama berubah
                const csrf = getCsrfTokenFromPage();
                if (csrf && csrf.name && csrf.value) formData.append(csrf.name, csrf.value);
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
                    } else {
                        // jika tidak ada, tambahkan supaya request berikutnya valid
                        const hid = document.createElement('input');
                        hid.type = 'hidden';
                        hid.name = res.xname;
                        hid.value = res.xhash;
                        hid.style.display = 'none';
                        document.body.appendChild(hid);
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
        if (e.target.closest && e.target.closest('#ker_btnRefreshKeranjang')) {
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
        if (e.target.closest && e.target.closest('.btn-delete-item')) {
            e.preventDefault();
            keranjangDeleteItem(e);
        }
        
        // Tombol Checkout dari Modal
        if (e.target.closest && e.target.closest('#ker_btnCheckoutFromModal')) {
            e.preventDefault();
            
            // gunakan confirm custom jika sayConfirm tersedia (lebih konsisten UI)
            if (typeof sayConfirm === 'function') {
              sayConfirm('Konfirmasi', 'Apakah anda yakin ingin melakukan checkout?', function() {
                    ker_doCheckout();
                }, 'primary', 'Checkout');
            } else {
                if (confirm('Apakah Anda yakin ingin melakukan checkout?')) {
                    ker_doCheckout();
                }
            }
        }
    });

    
    // ======= Checkout =======
    function ker_doCheckout() {
        const formData = new FormData();
        const csrfInput = document.querySelector('input[name="<?= csrf_token() ?>"]');
        if (csrfInput) {
            formData.append('<?= csrf_token() ?>', csrfInput.value);
        } else {
            const csrf = getCsrfTokenFromPage();
            if (csrf && csrf.name && csrf.value) formData.append(csrf.name, csrf.value);
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

    
/* populate jenFilter via AJAX fallback */
(function populateJenFilterFallback(){
    const select = document.getElementById('jenFilter');
    if (!select) return;

    if (select.options.length <= 1) {
        fetch('<?= site_url("formuliradmin/kategoriList") ?>', { method: 'GET', headers: { 'Accept': 'application/json' } })
        .then(r => r.json())
        .then(resp => {
            if (!resp || !resp.categories) return;
            while (select.options.length > 1) select.remove(1);
            resp.categories.forEach(c => {
                const kode = (c.jenKode ?? '').toString().trim();
                const nama = (c.jenNama && c.jenNama.toString().trim() !== '') ? c.jenNama : kode;
                if (!kode) return;
                const opt = document.createElement('option');
                opt.value = kode;
                opt.textContent = nama;
                select.appendChild(opt);
            });
            if (typeof applyJenFilter === 'function') applyJenFilter();
        })
        .catch(err => { console.warn('Gagal load kategori via AJAX:', err); });
    } else {
        if (typeof applyJenFilter === 'function') applyJenFilter();
    }
})();

(function(){
  function applyOnce(selector) {
    const sel = document.querySelector(selector);
    if (!sel) return;
    if (sel.dataset.searchinit === '1') return;
    try {
      selectSearch(selector);
      sel.dataset.searchinit = '1';
      // set wrapper text bila ada value awal
      const wrapper = sel.parentElement ? sel.parentElement.querySelector('.selected') : null;
      if (wrapper) {
        const opt = sel.options[sel.selectedIndex];
        wrapper.textContent = (opt && opt.value) ? opt.text : '-- pilih pelanggan --';
      }
    } catch (err) {
      console.warn('applyOnce error for', selector, err);
    }
  }

  // apply pada DOM ready
  document.addEventListener('DOMContentLoaded', function() {
    applyOnce('#ker_pelanggan_select');
    applyOnce('#jenFilter');
  });

  // juga apply lagi saat modalKeranjang terbuka (untuk kasus option di-render dinamis)
  const modal = document.getElementById('modalKeranjang');
  if (modal) {
    modal.addEventListener('shown.bs.modal', function() {
      setTimeout(function() {
        applyOnce('#ker_pelanggan_select');
        applyOnce('#jenFilter');
      }, 30);
    });
  }
})();
</script>
