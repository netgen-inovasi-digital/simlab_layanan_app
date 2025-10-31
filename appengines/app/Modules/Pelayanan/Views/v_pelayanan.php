<!-- modal tabel utama -->
<div class="row">
    <div class="col-md-12">
        <div class="card">
            <div class="card-header d-flex justify-content-between align-items-center">
                <label class="card-title mb-0"><?php echo $title ?></label>
                <button id="add" class="btn btn-primary">
                    <i class="bi bi-plus-circle-dotted"></i> Pesan Layanan Baru
                </button>
            </div>
            <div class="card-body">
                <table id="data-table" class="saytable border-top-bottom table table-hover table-sm">
                    <thead>
                        <tr>
                            <th show width="5%">No.</th>
                            <th show width="15%">No. transaksi</th>
                            <th show width="15%">Status pesanan</th>
                            <th show width="15%">Status pembayaran</th>
                            <th show width="15%">File LHU</th>
                            <th show class="action text-center">Detail pesanan</th>
                        </tr>
                    </thead>
                    <tbody id="table-body">
                    </tbody>
                </table>
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
                        </tr>
                    </thead>
                    <tbody id="detail-body">
                        <tr>
                            <td colspan="6" class="text-center">Loading...</td>
                        </tr>
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

<!-- Modal Keranjang (Pesan Layanan Baru) -->
<div class="modal fade" id="modalForm" data-bs-backdrop="static" data-bs-keyboard="false" tabindex="-1"
    aria-labelledby="staticBackdropLabel" aria-hidden="true">
    <div class="modal-dialog modal-xl" role="document" style="margin: 2% auto">
        <div class="modal-content">
            <div class="modal-header">
                <h5 class="modal-title">Pilih Layanan</h5>
                <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
            </div>
            <div class="modal-body">
                <!-- CSRF Token -->
                <input type="hidden" name="<?= csrf_token() ?>" value="<?= csrf_hash() ?>" />

                <!-- Tabel Pilih Layanan -->
                <div class="mb-4">
                    <h6 class="fw-bold text-primary mb-3">
                        <i class="bi bi-list-check"></i> Daftar Layanan Tersedia
                    </h6>
                    <div class="mb-3 d-flex gap-2 align-items-center">
                        <label class="mb-0 fw-semibold">Kategori:</label>
                        <select id="jenFilter" class="form-select form-select-sm" style="width:220px;">
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

                    <!-- tabel list keranjang -->
                    <table id="layanan-table" class="saytable border-top-bottom">
                        <thead>
                            <tr>
                                <th width="5%">No</th>
                                <th width="25%">Parameter</th>
                                <th width="25%">Instrumen/Alat/Tempat</th>
                                <th width="15%">Biaya</th>
                                <th width="5%">Jumlah</th>
                                <th width="20%">Keterangan</th>
                                <th style="width:5%" class="text-center">Aksi</th>
                            </tr>
                        </thead>
                        <tbody id="layanan-table-body"></tbody>
                    </table>
                </div>

                <hr class="my-4">

                <!-- Tabel Preview Keranjang -->
                <div>
                    <div class="d-flex justify-content-between align-items-center mb-3">
                        <h6 class="fw-bold text-success mb-0">
                            <i class="bi bi-cart3"></i> Keranjang Anda
                            (<span id="jumlahItemKeranjang">0</span> Item)
                        </h6>
                        <!-- <button type="button" id="btnRefreshKeranjang" class="btn btn-sm btn-outline-secondary">
                            <i class="bi bi-arrow-clockwise"></i> Refresh
                        </button> -->
                    </div>

                    <div id="keranjangKosong" class="alert alert-warning text-center" style="display:none;">
                        <i class="bi bi-cart-x"></i> Keranjang masih kosong. Silakan pilih layanan di atas.
                    </div>

                    <table id="preview-keranjang-table" class="saytable border-top-bottom">
                        <thead>
                            <tr>
                                <th width="5%">No</th>
                                <th width="22%">Parameter</th>
                                <th width="22%">Instrumen/Alat/Tempat</th>
                                <th width="10%">Diskon</th>
                                <th width="15%">Biaya</th>
                                <th width="5%">Jumlah</th>
                                <th width="25%">Keterangan</th>
                                <th style="width:10%" class="text-center">Aksi</th>
                            </tr>
                        </thead>
                        <tbody id="preview-keranjang-table-body"></tbody>
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
                <button id="btnCheckoutFromModal" class="btn btn-success" disabled>
                    <i class="bi bi-cart-check"></i> Checkout Sekarang
                </button>
            </div>
        </div>
    </div>
</div>

<!-- Modal Tracking -->
<div class="modal fade" id="modalTracking" data-bs-backdrop="static" data-bs-keyboard="false" tabindex="-1" aria-labelledby="modalTrackingLabel" aria-hidden="true">
    <div class="modal-dialog modal-lg">
        <div class="modal-content">
            <div class="modal-header">
                <h5 class="modal-title">Status Tracking Layanan</h5>
                <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
            </div>
            <div class="modal-body">
                <div class="tracking-info mb-4">
                    <div class="mb-3">
                        <small class="text-muted">No. Layanan:</small>
                        <div class="h6 mb-0" id="trackingNoLayanan">-</div>
                    </div>
                    <div class="mb-3">
                        <small class="text-muted">Status:</small>
                        <div id="trackingStatus" class="h6 mb-0">-</div>
                    </div>
                </div>

                <div class="progress-track">
                    <div class="step-box" data-step="1">
                        <div class="step-icon">
                            <i class="bi bi-person-check"></i>
                        </div>
                        <div class="step-text">Review Manajer</div>
                    </div>
                    <div class="step-box reject-step" data-step="2">
                        <div class="step-icon">
                            <i class="bi bi-x-circle"></i>
                        </div>
                        <div class="step-text">Ditolak</div>
                    </div>
                    <div class="step-box" data-step="3">
                        <div class="step-icon">
                            <i class="bi bi-person-check"></i>
                        </div>
                        <div class="step-text">Review Admin</div>
                    </div>
                    <div class="step-box" data-step="4">
                        <div class="step-icon">
                            <i class="bi bi-gear"></i>
                        </div>
                        <div class="step-text">Pengujian</div>
                    </div>
                    <div class="step-box" data-step="5">
                        <div class="step-icon">
                            <i class="bi bi-file-text"></i>
                        </div>
                        <div class="step-text">Proses LHUS</div>
                    </div>
                    <div class="step-box" data-step="6">
                        <div class="step-icon">
                            <i class="bi bi-check-circle"></i>
                        </div>
                        <div class="step-text">LHUS Disetujui</div>
                    </div>
                    <div class="step-box" data-step="7">
                        <div class="step-icon">
                            <i class="bi bi-file-earmark-text"></i>
                        </div>
                        <div class="step-text">Proses LHU</div>
                    </div>
                    <div class="step-box" data-step="8">
                        <div class="step-icon">
                            <i class="bi bi-check-circle"></i>
                        </div>
                        <div class="step-text">LHU Disetujui</div>
                    </div>
                    <div class="step-box" data-step="9">
                        <div class="step-icon">
                            <i class="bi bi-flag"></i>
                        </div>
                        <div class="step-text">Selesai</div>
                    </div>
                </div>

                <!-- Detail Layanan -->
                <div class="tracking-details mt-4">
                    <h6 class="mb-3">Detail Layanan</h6>
                    <div class="table-responsive">
                        <table class="table table-bordered table-sm">
                            <thead class="table-light">
                                <tr>
                                    <th>Parameter</th>
                                    <th>Biaya</th>
                                    <th>Jumlah</th>
                                    <th>Keterangan</th>
                                    <th>Status</th>
                                </tr>
                            </thead>
                            <tbody id="trackingDetailBody">
                                <tr>
                                    <td colspan="5" class="text-center">Memuat data...</td>
                                </tr>
                            </tbody>
                        </table>
                    </div>
                </div>
            </div>
            <div class="modal-footer">
                <button type="button" class="btn btn-light" data-bs-dismiss="modal">
                    <i class="bi bi-x-circle"></i> Tutup
                </button>
            </div>
        </div>
    </div>
</div>

<style>
    .progress-track {
        display: flex;
        flex-wrap: wrap;
        gap: 1rem;
        padding: 1rem;
        background: #f8f9fa;
        border-radius: 0.5rem;
    }

    .step-box {
        flex: 1;
        min-width: 100px;
        text-align: center;
        position: relative;
        opacity: 0.5;
    }

    .step-box.active {
        opacity: 1;
    }

    .step-box.reject {
        opacity: 1;
    }

    .step-box .step-icon {
        width: 40px;
        height: 40px;
        margin: 0 auto 0.5rem;
        background: #fff;
        border: 2px solid #dee2e6;
        border-radius: 50%;
        display: flex;
        align-items: center;
        justify-content: center;
        font-size: 1.2rem;
        color: #6c757d;
    }

    .step-box.active .step-icon {
        background: #0d6efd;
        border-color: #0d6efd;
        color: #fff;
    }

    .step-box.reject .step-icon {
        background: #dc3545;
        border-color: #dc3545;
        color: #fff;
    }

    .step-box .step-text {
        font-size: 0.875rem;
        color: #6c757d;
    }

    .step-box.active .step-text {
        color: #0d6efd;
        font-weight: 500;
    }

    .step-box.reject .step-text {
        color: #dc3545;
        font-weight: 500;
    }

    .tracking-info {
        background: #f8f9fa;
        padding: 1rem;
        border-radius: 0.5rem;
    }

    .reject-step {
        position: absolute;
        top: 50%;
        left: 50%;
        transform: translate(-50%, -50%);
        display: none;
    }

    .reject-step.show {
        display: block;
    }
</style>

<script>
    /**
     * buildApiUrlWithOptionalParam
     * - path: path ke endpoint, mis. '<?= site_url("pelayanan/datalist") ?>'
     * - key/value: jika diberikan, tambahkan sebagai query string (tanpa page/limit)
     *
     * Output: path atau path + '?key=value'
     */
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
            // gunakan pathname (tanpa origin) agar konsisten dengan helper createTable yang mungkin
            return u.pathname + (s ? '?' + s : '');
        } catch (e) {
            if (typeof key === 'string' && key !== '' && typeof value !== 'undefined' && value !== null && String(value) !== '') {
                return path + '?' + encodeURIComponent(key) + '=' + encodeURIComponent(String(value));
            }
            return path;
        }
    }

    /**
     * normalizeDoubleQuestion
     * - ubah pola like "...?a=b?c=d" menjadi "...?a=b&c=d"
     * - collapse accidental "&&"
     */
    function normalizeDoubleQuestion(url) {
        if (typeof url !== 'string') return url;
        // ubah pertama kali "?...?" -> "?...&"
        let n = url.replace(/\?([^?]*)\?/, '?$1&');
        // collapse duplicate ampersand
        n = n.replace(/&{2,}/g, '&');
        return n;
    }

    //    Ambil kategori dari query string (jika ada)
    const urlParams = new URLSearchParams(window.location.search);
    const kategoriLayananFromUrl = urlParams.get('kategoriLayanan');
    const jenKodeFromUrl = urlParams.get('jenKode');

    // Inisialisasi tabel utama menggunakan createTable

    const baseMainPath = '<?= site_url("pelayanan/datalist") ?>';

    // Prioritas: kategoriLayanan > jenKode
    let initialParamKey = null;
    let initialParamValue = null;
    if (kategoriLayananFromUrl && kategoriLayananFromUrl !== '') {
        initialParamKey = 'kategoriLayanan';
        initialParamValue = kategoriLayananFromUrl;
    } else if (jenKodeFromUrl && jenKodeFromUrl !== '') {
        initialParamKey = 'jenKode';
        initialParamValue = jenKodeFromUrl;
    }

    const initialApiUrl = (initialParamKey) ?
        buildApiUrlWithOptionalParam(baseMainPath, initialParamKey, initialParamValue) :
        buildApiUrlWithOptionalParam(baseMainPath, '', '');

    // Inisialisasi createTable (helper existing)
    table = createTable({
        apiUrl: initialApiUrl,
        numbering: true,
        dataSrc: 'items',
        onData: function(items) {
            // default render
        }
    });

    // patch fetchData main table agar normalisasi jika helper menghasilkan '?ganda'
    if (table && typeof table.fetchData === 'function' && typeof table.getConfig === 'function') {
        const origFetch = table.fetchData.bind(table);
        table.fetchData = function(opts = {}) {
            try {
                const cfg = table.getConfig();
                if (cfg && cfg.apiUrl && typeof cfg.apiUrl === 'string') {
                    cfg.apiUrl = normalizeDoubleQuestion(cfg.apiUrl);
                }
            } catch (err) {
                console.warn('Normalization (table) failed:', err);
            }
            return origFetch(opts);
        };
    }

    //    Set dropdown/filters awal
    (function setInitialFilterFromUrl() {
        try {
            const sf = document.getElementById('jenFilter');
            if (!sf) return;
            if (kategoriLayananFromUrl) {
                sf.value = kategoriLayananFromUrl;
            } else if (jenKodeFromUrl) {
                sf.value = jenKodeFromUrl;
            }
        } catch (e) {
            /* ignore */ }
    })();

    /* Helper build URL layanan (untuk modal)
       - memastikan jika jen diberikan -> url ...?jenKode=A*/
    function buildLayananUrl(jen = '') {
        const base = '<?= site_url("pelayanan/keranjang/dataListLayanan") ?>';
        return buildApiUrlWithOptionalParam(base, (jen && jen !== '') ? 'jenKode' : '', jen || '');
    }

    //  Inisialisasi createModal untuk keranjang (layanan & preview)
    let layananTable = null;
    let previewKeranjangTable = null;
    const jenFilter = document.getElementById('jenFilter');


    //   createOrRefreshLayananTable(jen)
    //   - memastikan apiUrl yang dipakai sudah memuat jenKode (jika ada)
    //   - kalau instance ada, coba update & fetchData, jika gagal recreate

    function createOrRefreshLayananTable(jen) {
        const api = buildLayananUrl(jen || '');

        // jika sudah ada instance, coba update config then fetch
        if (layananTable && typeof layananTable.getConfig === 'function') {
            try {
                const cfg = layananTable.getConfig();
                if (cfg && typeof cfg === 'object') {
                    cfg.apiUrl = api;
                    // normalisasi bila helper menambahkan ? ganda
                    cfg.apiUrl = normalizeDoubleQuestion(cfg.apiUrl);
                }
                if (typeof layananTable.fetchData === 'function') {
                    layananTable.fetchData({
                        reload: true
                    });
                    return;
                }
            } catch (e) {
                console.warn('Update layananTable failed, will recreate', e);
                try {
                    if (typeof layananTable.destroy === 'function') layananTable.destroy();
                } catch (e2) {
                    /*ignore*/ }
                layananTable = null;
            }
        }

        // create/recreate layananTable
        layananTable = createModal({
            apiUrl: api,
            tableId: 'layanan-table',
            numbering: true,
            dataSrc: 'items',
            preserveQuery: true // hint ke helper (jika support)
        });

        // patch fetchData supaya before request normalisasi apiUrl
        if (layananTable && typeof layananTable.fetchData === 'function' && typeof layananTable.getConfig === 'function') {
            const orig = layananTable.fetchData.bind(layananTable);
            layananTable.fetchData = function(opts = {}) {
                try {
                    const cfg = layananTable.getConfig();
                    if (cfg && cfg.apiUrl && typeof cfg.apiUrl === 'string') {
                        cfg.apiUrl = normalizeDoubleQuestion(cfg.apiUrl);
                    }
                } catch (err) {
                    console.warn('Normalization (layananTable) failed:', err);
                }
                return orig(opts);
            };
        }
    }

    /* applyJenFilter: dipanggil saat select berubah */
    function applyJenFilter() {
        const jen = (jenFilter && jenFilter.value) ? jenFilter.value.trim() : '';
        // refresh layanan modal table
        createOrRefreshLayananTable(jen);

        // juga update main table (opsional — jika ingin filter di main table juga)
        try {
            if (typeof table !== 'undefined' && table && typeof table.getConfig === 'function') {
                const cfg = table.getConfig();
                if (cfg && typeof cfg === 'object') {
                    cfg.apiUrl = buildApiUrlWithOptionalParam(baseMainPath, (jen !== '') ? 'jenKode' : '', jen);
                    cfg.apiUrl = normalizeDoubleQuestion(cfg.apiUrl);
                }
                if (typeof table.fetchData === 'function') table.fetchData({
                    reload: true
                });
            }
        } catch (err) {
            console.warn('Failed to update main table with jen filter:', err);
        }
    }

    // event listener select change
    if (jenFilter) {
        jenFilter.addEventListener('change', function() {
            applyJenFilter();
        });
    }

    document.getElementById('modalForm').addEventListener('shown.bs.modal', function() {
        const currentJen = (jenFilter && jenFilter.value) ? jenFilter.value.trim() : '';
        createOrRefreshLayananTable(currentJen);

        // 🔹 Bersihkan isi tabel manual — pastikan tidak ada sisa baris lama
        const previewBody = document.querySelector('#preview-keranjang-table-body');
        if (previewBody) previewBody.innerHTML = '';

        // 🔹 Jika ada instance lama dari helper, hancurkan dulu
        if (previewKeranjangTable && typeof previewKeranjangTable.destroy === 'function') {
            try {
                previewKeranjangTable.destroy();
            } catch (e) {
                console.warn('Gagal destroy previewKeranjangTable:', e);
            }
        }
        previewKeranjangTable = null;

        //  Buat ulang tabel preview tanpa append
        previewKeranjangTable = createModal({
            apiUrl: '<?= site_url("pelayanan/keranjang/datalist") ?>',
            tableId: 'preview-keranjang-table',
            showFilter: false,
            numbering: true,
            treeview: true,
            itemsPerPage: 10,
            dataSrc: 'items',
            // Tambahkan hook ini supaya tidak duplikat
            onData: function(items) {
                const tbody = document.querySelector('#preview-keranjang-table-body');
                if (tbody) tbody.innerHTML = ''; // clear sebelum isi ulang
            }
        });

        //  Paksa fetchData selalu reload penuh, bukan append
        if (previewKeranjangTable && typeof previewKeranjangTable.fetchData === 'function') {
            const origFetch = previewKeranjangTable.fetchData.bind(previewKeranjangTable);
            previewKeranjangTable.fetchData = function(opts = {}) {
                return origFetch(Object.assign({}, opts, {
                    reload: true
                }));
            };

            //  Panggil reload pertama kali
            previewKeranjangTable.fetchData({
                reload: true
            });
        }

        //  Update counter dan total
        setTimeout(() => {
            updateKeranjangCounter();
            calculateGrandTotal();
        }, 400);
    });


    /* =========================
       Event tombol "Pesan Layanan Baru"
       ========================= */
    document.querySelector('#add').addEventListener('click', function() {
        fetch('<?php echo site_url("pelayanan/checkVerified") ?>', {
                method: 'GET',
                headers: {
                    'Accept': 'application/json'
                }
            })
            .then(r => r.json())
            .then(data => {
                if (data.verified) {
                    const modalForm = new bootstrap.Modal(document.getElementById('modalForm'));
                    modalForm.show();
                } else {
                    sayAlert('warningModal', 'Verifikasi Diperlukan', 'Akun anda belum diverifikasi. Silakan lengkapi data di halaman profil.', 'warning');
                    setTimeout(() => {
                        loadContent('<?php echo site_url("profilpw") ?>');
                    }, 1200);
                }
            })
            .catch(err => {
                console.error(err);
                sayAlert('errorModal', 'Error', 'Gagal memeriksa status verifikasi.', 'warning');
            });
    });

    /* =========================
       reloadTable helper
       ========================= */
    function reloadTable() {
        if (typeof table !== 'undefined' && typeof table.fetchData === 'function') {
            table.fetchData({
                reload: true
            });
        }
    }

    /* =========================
       saveData, loadDetail, keranjang actions, etc.
       (gunakan persis implementasi yang sudah Anda punya)
       ========================= */

    /* saveData */
    function saveData({
        url,
        formData,
        onSuccess,
        onError
    }) {
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
            .then(res => res.json())
            .then(data => {
                if (data.xname && data.xhash) {
                    document.querySelectorAll('[name="' + data.xname + '"]').forEach(i => i.value = data.xhash);
                }
                if (typeof onSuccess === 'function') {
                    onSuccess(data);
                    return;
                }

                if (data.res === true) {
                    reloadTable();
                    sayAlert('successModal', 'Success', 'Data berhasil disimpan.', 'success');
                } else if (data.res === 'refresh') {
                    loadContent(data.link);
                } else if (data.res === 'redirect') {
                    window.location.href = data.link;
                } else {
                    sayAlert('errorModal', 'Error', data.msg ?? 'Data gagal disimpan.', 'warning');
                }
            })
            .catch(err => {
                if (typeof onError === 'function') onError(err);
                else sayAlert('errorModal', 'Error', 'Terjadi kesalahan pada sistem.', 'warning');
            })
            .finally(() => hideLoading());
    }

    /* loadDetail (sama seperti implementasi kamu) */
    function showTrackingModal(id, kode, status) {
        // Reset semua step
        document.querySelectorAll('.step-box').forEach(step => {
            step.classList.remove('active', 'reject');
        });

        document.querySelector('.reject-step').classList.remove('show');

        // Update tracking info
        document.getElementById('trackingNoLayanan').textContent = kode;

        // Load data tracking
        fetch(`<?php echo site_url('pelayanan/getTrackingData/') ?>${id}`)
            .then(response => response.json())
            .then(result => {
                if (result.success) {
                    const data = result.data;

                    // Update status text dengan badge
                    const statusClass = status === 2 ? 'danger' :
                        status >= 9 ? 'success' :
                        status >= 6 ? 'info' : 'primary';
                    document.getElementById('trackingStatus').innerHTML =
                        `<span class="badge bg-${statusClass}">${data.statusText}</span>`;

                    // Update progress steps
                    if (status === 2) {
                        // Jika ditolak, tampilkan step reject
                        document.querySelector('.reject-step').classList.add('show');
                        document.querySelector(`.step-box[data-step="2"]`).classList.add('reject');
                    } else {
                        // Update progress sampai status terkini
                        document.querySelectorAll('.step-box').forEach(step => {
                            const stepNum = parseInt(step.dataset.step);
                            if (stepNum <= status) {
                                step.classList.add('active');
                            }
                        });
                    }

                    // Update detail table
                    const tbody = document.getElementById('trackingDetailBody');
                    if (data.details && data.details.length > 0) {
                        tbody.innerHTML = data.details.map(item => `
                        <tr>
                            <td>${item.parameter}</td>
                            <td class="text-end">Rp ${item.biaya}</td>
                            <td class="text-center">${item.jumlah}</td>
                            <td>${item.keterangan}</td>
                            <td>${item.status}</td>
                        </tr>
                    `).join('');
                    } else {
                        tbody.innerHTML = '<tr><td colspan="5" class="text-center">Tidak ada detail</td></tr>';
                    }
                } else {
                    sayAlert('errorModal', 'Error', result.message || 'Gagal memuat data tracking', 'error');
                }
            })
            .catch(error => {
                console.error('Error:', error);
                sayAlert('errorModal', 'Error', 'Terjadi kesalahan saat memuat data', 'error');
            });

        // Show modal
        const modal = new bootstrap.Modal(document.getElementById('modalTracking'));
        modal.show();
    }

    function loadDetail(id) {
        const url = '<?php echo site_url("pelayanan/detailList/") ?>' + id;
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

    /* updateKeranjangCounter & calculateGrandTotal (sama seperti implementasi Anda) */
    function updateKeranjangCounter() {
        fetch('<?= site_url("pelayanan/keranjang/datalist") ?>')
            .then(res => res.json())
            .then(data => {
                const jumlahItem = data.items ? data.items.length : 0;
                document.getElementById('jumlahItemKeranjang').textContent = jumlahItem;

                const keranjangKosong = document.getElementById('keranjangKosong');
                const previewTable = document.getElementById('preview-keranjang-table');
                const btnCheckout = document.getElementById('btnCheckoutFromModal');

                if (jumlahItem === 0) {
                    if (keranjangKosong) keranjangKosong.style.display = 'block';
                    if (previewTable) previewTable.style.display = 'none';
                    if (btnCheckout) btnCheckout.disabled = true;
                } else {
                    if (keranjangKosong) keranjangKosong.style.display = 'none';
                    if (previewTable) previewTable.style.display = 'table';
                    if (btnCheckout) btnCheckout.disabled = false;
                }
            })
            .catch(err => {
                console.error('Error updating counter:', err);
            });
    }

    function calculateGrandTotal() {
        fetch('<?= site_url("pelayanan/keranjang/datalist") ?>')
            .then(res => res.json())
            .then(data => {
                if (data.items && data.items.length > 0) {
                    let grandTotal = 0;
                    data.items.forEach(item => {
                        let totalStr = null;
                        for (let i = 0; i < item.length; i++) {
                            if (typeof item[i] === 'string' && item[i].indexOf('row-total') !== -1) {
                                const tmp = document.createElement('div');
                                tmp.innerHTML = item[i];
                                const rt = tmp.querySelector('.row-total');
                                if (rt) {
                                    totalStr = rt.textContent || rt.innerText || null;
                                    break;
                                }
                            }
                        }
                        if (!totalStr) {
                            for (let i = item.length - 1; i >= 0; i--) {
                                if (typeof item[i] === 'string' && item[i].indexOf('Rp') !== -1) {
                                    const tmp2 = document.createElement('div');
                                    tmp2.innerHTML = item[i];
                                    totalStr = (tmp2.textContent || tmp2.innerText || '').trim();
                                    break;
                                }
                            }
                        }
                        if (totalStr) {
                            let cleaned = totalStr.replace(/[^0-9,.-]/g, '');
                            cleaned = cleaned.replace(/\./g, '').replace(/,/g, '.');
                            const totalNum = parseFloat(cleaned);
                            if (!isNaN(totalNum)) grandTotal += totalNum;
                        }
                    });
                    document.getElementById('grandTotal').textContent = 'Rp ' + new Intl.NumberFormat('id-ID').format(grandTotal);
                } else {
                    document.getElementById('grandTotal').textContent = 'Rp 0';
                }
            })
            .catch(err => {
                console.error('Error calculating grand total:', err);
            });
    }

    /* =========================
       Event delegation: masukkan item / checkout / delete
       ========================= */
    document.addEventListener('click', function(e) {
        // Tombol masukkan
        if (e.target.closest('.btnMasukkan')) {
            let btn = e.target.closest('.btnMasukkan');
            let tr = btn.closest('tr');

            let biaya = parseFloat(btn.dataset.biaya) || 0;
            let diskon = parseFloat(btn.dataset.diskon) || 0;
            let jumlahInput = tr.querySelector('.jumlah');
            let jumlah = parseInt(jumlahInput ? jumlahInput.value : 1) || 1;
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
                detTotal: total,
                // tambahan:
                ujiPenyelia: btn.dataset.ujiPenyelia || '',
                ujiManajerTeknis: btn.dataset.ujiManajerteknis || btn.dataset.ujiManajerteknis === undefined ? (btn.getAttribute('data-uji-manajerteknis') || '') : btn.dataset.ujiManajerTeknis
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
            for (const key in data) formData.append(key, data[key]);

            let csrfInput = document.querySelector('input[name="<?= csrf_token() ?>"]');
            if (csrfInput) formData.append('<?= csrf_token() ?>', csrfInput.value);

            saveData({
                url: "<?= site_url('pelayanan/keranjang/submit') ?>",
                formData: formData,
                onSuccess: function(res) {
                    if (res.xname && res.xhash) {
                        let csrfField = document.querySelector('input[name="' + res.xname + '"]');
                        if (csrfField) csrfField.value = res.xhash;
                    }
                    if (res.res === true) {
                        if (typeof table !== 'undefined' && typeof table.fetchData === 'function') table.fetchData({
                            reload: true
                        });
                        if (previewKeranjangTable && typeof previewKeranjangTable.fetchData === 'function') {
                            previewKeranjangTable.fetchData({
                                reload: true
                            });
                            setTimeout(function() {
                                updateKeranjangCounter();
                                calculateGrandTotal();
                            }, 400);
                        }
                        if (jumlahInput) jumlahInput.value = 1;
                        if (tr.querySelector('.keterangan')) tr.querySelector('.keterangan').value = '';
                        sayAlert('successModal', 'Berhasil', res.msg ?? 'Layanan berhasil ditambahkan ke keranjang.', 'success');
                    } else {
                        sayAlert('errorModal', 'Gagal', res.msg ?? 'Terjadi kesalahan saat menambahkan ke keranjang.', 'error');
                    }
                },
                onError: function() {
                    sayAlert('errorModal', 'Gagal', 'Terjadi kesalahan koneksi ke server.', 'error');
                }
            });
        }

        if (e.target.closest('#btnCheckoutFromModal')) {
            e.preventDefault();
            const btn = e.target.closest('#btnCheckoutFromModal');
            const customMsg = btn ? (btn.getAttribute('data-confirm') || '') : '';
            const message = customMsg || 'Apakah Anda yakin ingin melakukan checkout?';

            // 'success' = hijau; label custom 'Ya, Checkout'
            sayConfirm('Konfirmasi Checkout', message, () => {
                doCheckout();
            }, 'success', 'checkout');
        }


    });

    /* deleteItemFromPreview */
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

        const idx = el.getAttribute('data-index');
        if (!idx) {
            console.warn('Data-index tidak ditemukan pada element hapus.');
            return;
        }

        fetch("<?= site_url('pelayanan/keranjang/delete/') ?>" + idx)
            .then(res => res.json())
            .then(data => {
                if (data.xname && data.xhash) {
                    document.querySelectorAll('[name="' + data.xname + '"]').forEach(input => input.value = data.xhash);
                }
                if (data.res === true) {
                    if (typeof table !== 'undefined' && typeof table.fetchData === 'function') table.fetchData({
                        reload: true
                    });
                    if (previewKeranjangTable && typeof previewKeranjangTable.fetchData === 'function') {
                        previewKeranjangTable.fetchData({
                            reload: true
                        });
                        setTimeout(function() {
                            updateKeranjangCounter();
                            calculateGrandTotal();
                        }, 400);
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

    /* doCheckout */
    function doCheckout() {
        const formData = new FormData();
        const csrfInput = document.querySelector('input[name="<?= csrf_token() ?>"]');
        if (csrfInput) formData.append('<?= csrf_token() ?>', csrfInput.value);

        fetch('<?= site_url("pelayanan/keranjang/checkout") ?>', {
                method: 'POST',
                body: formData
            })
            .then(res => res.json())
            .then(data => {
                if (data.xname && data.xhash) {
                    document.querySelectorAll('[name="' + data.xname + '"]').forEach(input => input.value = data.xhash);
                }
                if (data.res === true) {
                    if (typeof table !== 'undefined' && typeof table.fetchData === 'function') table.fetchData({
                        reload: true
                    });
                    if (previewKeranjangTable && typeof previewKeranjangTable.fetchData === 'function') {
                        previewKeranjangTable.fetchData({
                            reload: true
                        });
                        setTimeout(function() {
                            updateKeranjangCounter();
                            calculateGrandTotal();
                        }, 400);
                    }
                    const modalForm = bootstrap.Modal.getInstance(document.getElementById('modalForm'));
                    if (modalForm) modalForm.hide();
                    sayAlert('successModal', 'Sukses', data.msg, 'success');
                } else {
                    sayAlert('errorModal', 'Gagal', data.msg ?? 'Checkout gagal.', 'error');
                }
            })
            .catch(err => {
                sayAlert('errorModal', 'Error', 'Terjadi kesalahan koneksi ke server.', 'error');
            });
    }

    /* populate jenFilter via AJAX fallback */
    (function populateJenFilterFallback() {
        const select = document.getElementById('jenFilter');
        if (!select) return;

        if (select.options.length <= 1) {
            fetch('<?= site_url("pelayanan/keranjang/kategoriList") ?>', {
                    method: 'GET',
                    headers: {
                        'Accept': 'application/json'
                    }
                })
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
                .catch(err => {
                    console.warn('Gagal load kategori via AJAX:', err);
                });
        } else {
            if (typeof applyJenFilter === 'function') applyJenFilter();
        }
    })();
</script>