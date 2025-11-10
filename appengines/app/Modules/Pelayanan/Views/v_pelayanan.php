<!-- Include Modal Tracking -->
<?php require_once(__DIR__ . '/v_track_modal.php'); ?>

<!-- Include Modal Keranjang -->
<?php echo view('Modules\Keranjang\Views\v_keranjang', ['categories' => $categories ?? []]); ?>

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
                <table id="data-table" class="saytable border-top-bottom">
                    <thead>
                        <tr>
                            <th show width="5%">No.</th>
                            <th show width="20%">No. transaksi</th>
                            <th show width="20%">Status & Detail Pesanan</th>
                            <th show width="15%">Status Pembayaran</th>
                            <th show width="15%">File LHU</th>
                            <!-- <th show >Detail pesanan</th> -->
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
<!-- <div class="modal fade" id="modalDetail" data-bs-backdrop="static" data-bs-keyboard="false" tabindex="-1" aria-labelledby="staticBackdropLabel" aria-hidden="true">
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
</div> -->

<!-- Modal Tracking -->
<div class="modal fade" id="modalTracking" data-bs-backdrop="static" data-bs-keyboard="false" tabindex="-1" aria-labelledby="modalTrackingLabel" aria-hidden="true">
    <div class="modal-dialog modal-xl">
        <div class="modal-content">
            <div class="modal-header">
                <h5 class="modal-title" id="modalTrackingLabel">Progress & Detail Layanan</h5>
                <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
            </div>
            <div class="modal-body">
                <!-- Status Tracking Section -->
                <div class="statusbox">
                    <div class="mb-3">
                        <p class="mb-0">No. Layanan: <strong id="trackLnKode"></strong></p>
                    </div>

                    <div class="track">
                        <?php
                        $steps = [
                            1 => ['icon' => 'bi-person-check', 'text' => 'Review Manajer'],
                            2 => ['icon' => 'bi-person-check', 'text' => 'Review Admin'],
                            3 => ['icon' => 'bi-gear', 'text' => 'Pengujian'],
                            4 => ['icon' => 'bi-file-text', 'text' => 'Proses LHUS'],
                            5 => ['icon' => 'bi-check-circle', 'text' => 'LHUS Disetujui'],
                            6 => ['icon' => 'bi-file-earmark-text', 'text' => 'Proses LHU'],
                            7 => ['icon' => 'bi-check-circle', 'text' => 'LHU Disetujui'],
                            8 => ['icon' => 'bi-flag', 'text' => 'Selesai']
                        ];

                        foreach ($steps as $step => $info):
                        ?>
                            <div class="step" data-step="<?= $step ?>">
                                <span class="icon">
                                    <i class="bi <?= $info['icon'] ?>"></i>
                                </span>
                                <span class="text"><?= $info['text'] ?></span>
                            </div>
                        <?php endforeach; ?>
                    </div>

                    <div class="text-center">
                        <h6>Status Saat Ini:</h6>
                        <div id="trackStatus" class="badge bg-secondary p-2">
                            Memuat...
                        </div>
                    </div>
                </div>

                <!-- Detail Table Section -->
                <div class="detail-table">
                    <h6 class="mb-3">Detail Layanan:</h6>
                    <div class="table-responsive">
                        <table class="table table-bordered table-hover" id="tableDetail">
                            <thead class="table-light">
                                <tr>
                                    <th>No</th>
                                    <th>Parameter</th>
                                    <th>Biaya</th>
                                    <th>Jumlah</th>
                                    <th>Keterangan</th>
                                    <th>Status</th>
                                </tr>
                            </thead>
                            <tbody></tbody>
                        </table>
                    </div>
                </div>
            </div>
            <div class="modal-footer">
                <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">
                    <i class="bi bi-x-circle"></i> Tutup
                </button>
            </div>
        </div>
    </div>
</div>

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

    // Fungsi untuk menampilkan tracking modal
    function showTrackingModal(id, lnKode, lnStatus) {
        // Panggil fungsi dari v_track_modal.php
        showFullTrackingModal(id, lnKode, lnStatus);
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
</script>