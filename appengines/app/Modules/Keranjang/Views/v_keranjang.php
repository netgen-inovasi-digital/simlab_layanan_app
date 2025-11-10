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

<!-- Load dependencies yang diperlukan untuk modal ini -->
<script src="<?= base_url('assets/js/sayTable.js?v=0.11') ?>"></script>

<script>
    /**
     * buildApiUrlWithOptionalParam
     * - path: path ke endpoint, mis. '<?= site_url("keranjang/datalist") ?>'
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

    //  Inisialisasi variabel global untuk tabel modal
    let layananTable = null;
    let previewKeranjangTable = null;
    const jenFilter = document.getElementById('jenFilter');

    /* Helper build URL layanan (untuk modal)
    - memastikan jika jen diberikan -> url ...?jenKode=A*/
    function buildLayananUrl(jen = '') {
        const base = '<?= site_url("keranjang/dataListLayanan") ?>';
        return buildApiUrlWithOptionalParam(base, (jen && jen !== '') ? 'jenKode' : '', jen || '');
    }


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
                    /*ignore*/
                }
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
            apiUrl: '<?= site_url("keranjang/datalist") ?>',
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
        fetch('<?php echo site_url("keranjang/checkVerified") ?>', {
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
                        loadContent('<?php echo site_url("profiluser") ?>');
                    }, 1200);
                }
            })
            .catch(err => {
                console.error(err);
                sayAlert('errorModal', 'Error', 'Gagal memeriksa status verifikasi.', 'warning');
            });
    });

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
                    // Refresh preview keranjang jika ada
                    if (previewKeranjangTable && typeof previewKeranjangTable.fetchData === 'function') {
                        previewKeranjangTable.fetchData({
                            reload: true
                        });
                    }
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
        const url = '<?php echo site_url("keranjang/detailList/") ?>' + id;
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
        fetch('<?= site_url("keranjang/datalist") ?>')
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
        fetch('<?= site_url("keranjang/datalist") ?>')
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
            for (const key in data) formData.append(key, data[key]);

            let csrfInput = document.querySelector('input[name="<?= csrf_token() ?>"]');
            if (csrfInput) formData.append('<?= csrf_token() ?>', csrfInput.value);

            saveData({
                url: "<?= site_url('keranjang/submit') ?>",
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

        fetch("<?= site_url('keranjang/delete/') ?>" + idx)
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

        fetch('<?= site_url("keranjang/checkout") ?>', {
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
            fetch('<?= site_url("keranjang/kategoriList") ?>', {
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