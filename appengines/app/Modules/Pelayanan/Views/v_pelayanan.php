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

<!-- Modal Riwayat LHU -->
<div class="modal fade" id="modalPelayananLhu" data-bs-backdrop="static" data-bs-keyboard="false" tabindex="-1">
    <div class="modal-dialog modal-lg" role="document">
        <div class="modal-content">
            <div class="modal-header">
                <h5 class="modal-title mb-0">Daftar LHU</h5>
                <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
            </div>
            <div class="modal-body">
                <div class="alert alert-info d-none" id="pelayanan-lhu-empty">Belum ada LHU yang bisa ditampilkan.</div>
                <div class="table-responsive">
                    <table class="table table-striped">
                        <thead>
                            <tr>
                                <th style="width:10%">No.</th>
                                <th style="width:45%">Tanggal terbit</th>
                                <th style="width:45%" class="text-center">Aksi</th>
                            </tr>
                        </thead>
                        <tbody id="pelayanan-lhu-body">
                            <tr>
                                <td colspan="3" class="text-center text-muted">Tidak ada data.</td>
                            </tr>
                        </tbody>
                    </table>
                </div>
            </div>
            <div class="modal-footer">
                <button type="button" class="btn btn-light" data-bs-dismiss="modal">Tutup</button>
            </div>
        </div>
    </div>
</div>

<script>
    /**
     * buildApiUrlWithOptionalParam
     * - path: path ke endpoint, mis. '<?= site_url("pelayanan/datalist") ?>'
        * - key / value: jika diberikan, tambahkan sebagai query string(tanpa page / limit)
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
        var n = url.replace(/\?([^?]*)\?/, '?$1&');
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
    var initialParamKey = null;
    var initialParamValue = null;
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
        onData: function (items) {
            // default render
        }
    });

    // patch fetchData main table agar normalisasi jika helper menghasilkan '?ganda'
    if (table && typeof table.fetchData === 'function' && typeof table.getConfig === 'function') {
        const origFetch = table.fetchData.bind(table);
        table.fetchData = function (opts = {}) {
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
                    data.items.forEach(function (row) {
                        var tr = '<tr>';
                        row.forEach(function (col) {
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

    function showPelayananLhuHistory(encId) {
        const modalEl = document.getElementById('modalPelayananLhu');
        if (!modalEl) return;

        const tbody = document.getElementById('pelayanan-lhu-body');
        const emptyAlert = document.getElementById('pelayanan-lhu-empty');

        if (emptyAlert) emptyAlert.classList.add('d-none');
        if (tbody) {
            tbody.innerHTML = '<tr><td colspan="3" class="text-center">Memuat data...</td></tr>';
        }

        const modalInstance = (typeof bootstrap !== 'undefined' && bootstrap.Modal)
            ? bootstrap.Modal.getOrCreateInstance(modalEl)
            : null;

        if (modalInstance) {
            modalInstance.show();
        } else if (typeof $ !== 'undefined' && $('#modalPelayananLhu').modal) {
            $('#modalPelayananLhu').modal('show');
        }

        fetch('<?= site_url("pelayanan/lhulist/") ?>' + encId, { headers: { 'X-Requested-With': 'XMLHttpRequest' } })
            .then(res => res.json())
            .then(data => {
                if (!tbody) return;
                const items = Array.isArray(data.items) ? data.items : [];
                if (items.length === 0) {
                    tbody.innerHTML = '<tr><td colspan="3" class="text-center text-muted">Tidak ada data.</td></tr>';
                    if (emptyAlert) emptyAlert.classList.remove('d-none');
                    return;
                }

                const rows = items.map(row => {
                    const safeDate = escapeHtml(row.tanggal ?? '-');
                    const hasUrl = row.url && row.url !== '#';
                    const actionHtml = hasUrl
                        ? '<a class="btn btn-sm btn-outline-primary" href="' + escapeHtml(row.url) + '" target="_blank" rel="noopener"><i class="bi bi-eye"></i> Lihat</a>'
                        : '<span class="text-muted">Tidak tersedia</span>';
                    return '<tr>' +
                        '<td>' + row.no + '.</td>' +
                        '<td>' + safeDate + '</td>' +
                        '<td class="text-center">' + actionHtml + '</td>' +
                        '</tr>';
                }).join('');

                tbody.innerHTML = rows;
            })
            .catch(err => {
                console.error(err);
                if (tbody) {
                    tbody.innerHTML = '<tr><td colspan="3" class="text-center text-danger">Gagal memuat data.</td></tr>';
                }
            });
    }

    function escapeHtml(value) {
        return String(value ?? '')
            .replace(/&/g, '&amp;')
            .replace(/</g, '&lt;')
            .replace(/>/g, '&gt;')
            .replace(/"/g, '&quot;')
            .replace(/'/g, '&#39;');
    }
</script>