<!-- Include Modal Keranjang -->
<?php echo view('Modules\KeranjangAdmin\Views\v_keranjang', ['categories' => $categories ?? [], 'users' => $users ?? []]); ?>

<!-- modal tabel utama -->
<div class="row">
    <div class="col-md-12">
        <div class="card">

            <div class="card-header d-flex justify-content-between align-items-center">
                <!-- Tetap kiri: Judul -->
                <div class="d-flex align-items-center" style="gap:12px;">
                    <label class="card-title mb-0"><?= $title ?></label>
                </div>

                <!-- Kanan: Filter + Tombol, urutan sesuai request -->
                <div class="d-flex align-items-center" style="gap:10px;">
                    <select id="statusFilter" class="form-select form-select-sm" style="width:180px;">
                        <option value="all">Semua Kategori</option>
                        <option value="1">In Review Manajer</option>
                        <option value="3">Belum direview</option>
                        <option value="4">Dalam pengujian</option>
                        <option value="5">LHUS diproses</option>
                        <option value="6">LHUS disetujui</option>
                        <option value="7">LHU proses</option>
                    </select>

                    <button id="add" class="btn btn-primary">
                        <i class="bi bi-plus-circle-dotted"></i> Pesan
                    </button>
                </div>
            </div>

            <div class="card-body">
                <table id="data-table" class="saytable border-top-bottom">
                    <thead>
                        <tr>
                            <th show width="5%">No.</th>
                            <th show width="25%">Pemesan</th>
                            <th show width="15%">No Invoice</th>
                            <th show width="12%">Status</th>
                            <th show width="12%">Status Pembayaran</th>
                            <th show width="13%">Detail Layanan</th>
                            <th show width="13%" class="action text-end">Aksi<i class="bi bi-code sort-icon"></i></th>
                        </tr>
                    </thead>
                    <tbody id="table-body"></tbody>
                </table>
            </div>
        </div>
    </div>
</div>

<!-- Modal detail Pesanan -->
<div class="modal fade" id="modalDetail" data-bs-backdrop="static" data-bs-keyboard="false" tabindex="-1"
    aria-labelledby="staticBackdropLabel" aria-hidden="true">
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
            } catch (err) {
                console.warn('Normalization (table) failed:', err);
            }
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
            table.fetchData({
                page: 1,
                reload: true
            });
        });
    }

    // ======= saveData tetap tersedia (dipakai oleh fitur lain) =======
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
                    if (typeof table !== 'undefined') table.fetchData({
                        reload: true
                    });
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
                const headers = {
                    'X-Requested-With': 'XMLHttpRequest'
                };

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
                            if (typeof table !== 'undefined') table.fetchData({
                                reload: true
                            });
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
                    return {
                        name: inp.name,
                        value: inp.value
                    };
                }
            }
        } catch (err) {
            console.warn('getCsrfTokenFromPage error', err);
        }
        return null;
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

    // Fungsi untuk pembayaran (sama seperti di Pelayanan)
    function lokasiPembayaran(lnKode) {
        window.location.href = '<?= site_url('pembayaran?kode=') ?>' + lnKode;
    }

    // ======= Pastiin #add membuka modalForm (dari KeranjangAdmin) =======
    (function() {
        var addBtn = document.querySelector('#add');
        if (!addBtn) return;

        addBtn.addEventListener('click', function() {
            try {
                var modalEl = document.getElementById('modalForm');
                if (!modalEl) {
                    console.warn('modalForm tidak ditemukan di DOM.');
                    return;
                }
                if (typeof bootstrap !== 'undefined') {
                    var modalInstance = new bootstrap.Modal(modalEl);
                    modalInstance.show();
                } else if (typeof $ !== 'undefined' && typeof $('#modalForm').modal === 'function') {
                    $('#modalForm').modal('show');
                } else {
                    console.warn('Bootstrap modal tidak tersedia — pastikan Bootstrap JS dimuat.');
                }
            } catch (err) {
                console.error('Gagal membuka modalForm:', err);
            }
        });
    })();
</script>
