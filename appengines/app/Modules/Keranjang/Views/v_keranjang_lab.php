<!-- Modal Keranjang Sewa Ruangan Lab -->
<div class="modal fade" id="modalFormLab" data-bs-backdrop="static" data-bs-keyboard="false" tabindex="-1"
    aria-labelledby="staticBackdropLabel" aria-hidden="true">
    <div class="modal-dialog modal-xl" role="document" style="margin: 2% auto">
        <div class="modal-content">
            <div class="modal-header">
                <h5 class="modal-title">Pilih Ruangan Lab untuk Disewa</h5>
                <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
            </div>
            <div class="modal-body">
                <!-- CSRF Token -->
                <input type="hidden" name="<?= csrf_token() ?>" value="<?= csrf_hash() ?>" />

                <!-- Tabel Pilih Layanan Ruangan Lab -->
                <div class="mb-4">
                    <table id="layanan-lab-table" class="saytable border-top-bottom">
                        <thead>
                            <tr>
                                <th width="5%">No</th>
                                <th width="25%">Parameter</th>
                                <th width="25%">Nama Ruangan</th>
                                <th width="15%">Biaya / Hari</th>
                                <th width="10%">Jumlah Hari</th>
                                <th width="15%">Keterangan</th>
                                <th style="width:5%" class="text-center">Aksi</th>
                            </tr>
                        </thead>
                        <tbody id="layanan-lab-table-body"></tbody>
                    </table>
                </div>

                <hr class="my-4">

                <!-- Tabel Preview Keranjang Ruangan Lab -->
                <div>
                    <div class="d-flex flex-column flex-md-row justify-content-between align-items-md-end mb-3 gap-3">
                        <!-- Kiri -->
                        <div class="d-flex align-items-end">
                            <h6 class="fw-bold text-success mb-0">
                                <i class="bi bi-cart3"></i> Keranjang Anda
                                (<span id="jumlahItemKeranjangLab">0</span> Item)
                            </h6>
                        </div>

                        <!-- Kanan -->
                        <div class="col-md-3">
                            <label for="tglPelaksanaanLab" class="form-label mb-1">
                                Pilih Tanggal Pelaksanaan <span class="text-danger">*</span>
                            </label>
                            <input type="date" class="form-control" id="tglPelaksanaanLab" name="tglPelaksanaanLab"
                                required>
                        </div>

                        <!-- File Pendukung -->
                        <div class="col-md-3">
                            <label for="filePendukungLab" class="form-label mb-1">
                                File Pendukung <small class="text-muted">(opsional)</small>
                            </label>
                            <input type="file" class="form-control" id="filePendukungLab" name="filePendukungLab"
                                accept=".pdf,.doc,.docx,.xls,.xlsx,.jpg,.jpeg,.png">
                            <small class="text-muted">Max 2MB. Format: PDF, Word, Excel, Gambar</small>
                        </div>
                    </div>


                    <div id="keranjangLabKosong" class="alert alert-warning text-center" style="display:none;">
                        <i class="bi bi-cart-x"></i> Keranjang masih kosong. Silakan pilih ruangan lab di atas.
                    </div>

                    <table id="preview-keranjang-lab-table" class="saytable border-top-bottom">
                        <thead>
                            <tr>
                                <th width="5%">No</th>
                                <th width="22%">Parameter</th>
                                <th width="22%">Nama Ruangan</th>
                                <th width="10%">Diskon</th>
                                <th width="15%">Biaya/Hari</th>
                                <th width="10%">Jumlah Hari</th>
                                <th width="20%">Keterangan</th>
                                <th style="width:10%" class="text-center">Aksi</th>
                            </tr>
                        </thead>
                        <tbody id="preview-keranjang-lab-table-body"></tbody>
                        <tfoot>
                            <tr class="table-active align-middle">
                                <td colspan="8">
                                    <div class="d-flex justify-content-end">
                                        <div class="fw-bold fs-5">
                                            TOTAL KESELURUHAN:
                                            <span id="grandTotalLab" class="text-primary">Rp 0</span>
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
                <button id="btnCheckoutLabFromModal" class="btn btn-success" disabled>
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
     * - path: path ke endpoint, mis. '<?= site_url("keranjang_alat/datalist") ?>'
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
        let n = url.replace(/\?([^?]*)\?/, '?$1&');
        n = n.replace(/&{2,}/g, '&');
        return n;
    }

    //  Inisialisasi variabel untuk tabel modal Lab
    let layananLabTable = null;
    let previewKeranjangLabTable = null;

    /* Helper build URL layanan lab (untuk modal) - selalu kode_jenis='C' */
    function buildLayananLabUrl() {
        return '<?= site_url("keranjang_lab/dataListLayanan") ?>';
    }

    //   createOrRefreshLayananLabTable()
    function createOrRefreshLayananLabTable() {
        const api = buildLayananLabUrl();

        if (layananLabTable && typeof layananLabTable.getConfig === 'function') {
            try {
                const cfg = layananLabTable.getConfig();
                if (cfg && typeof cfg === 'object') {
                    cfg.apiUrl = api;
                    cfg.apiUrl = normalizeDoubleQuestion(cfg.apiUrl);
                }
                if (typeof layananLabTable.fetchData === 'function') {
                    layananLabTable.fetchData({
                        reload: true
                    });
                    return;
                }
            } catch (e) {
                try {
                    if (typeof layananLabTable.destroy === 'function') layananLabTable.destroy();
                } catch (e2) {
                    /*ignore*/
                }
                layananLabTable = null;
            }
        }

        layananLabTable = createModal({
            apiUrl: api,
            tableId: 'layanan-lab-table',
            numbering: true,
            dataSrc: 'items',
            preserveQuery: true
        });

        if (layananLabTable && typeof layananLabTable.fetchData === 'function' && typeof layananLabTable.getConfig === 'function') {
            const orig = layananLabTable.fetchData.bind(layananLabTable);
            layananLabTable.fetchData = function (opts = {}) {
                try {
                    const cfg = layananLabTable.getConfig();
                    if (cfg && cfg.apiUrl && typeof cfg.apiUrl === 'string') {
                        cfg.apiUrl = normalizeDoubleQuestion(cfg.apiUrl);
                    }
                } catch (err) {
                    // Error handling
                }
                return orig(opts);
            };
        }
    }

    document.getElementById('modalFormLab').addEventListener('shown.bs.modal', function () {
        createOrRefreshLayananLabTable();

        const previewBody = document.querySelector('#preview-keranjang-lab-table-body');
        if (previewBody) previewBody.innerHTML = '';

        if (previewKeranjangLabTable && typeof previewKeranjangLabTable.destroy === 'function') {
            try {
                previewKeranjangLabTable.destroy();
            } catch (e) {
                // Error handling
            }
        }
        previewKeranjangLabTable = null;

        previewKeranjangLabTable = createModal({
            apiUrl: '<?= site_url("keranjang_lab/datalist") ?>',
            tableId: 'preview-keranjang-lab-table',
            showFilter: false,
            numbering: true,
            treeview: true,
            itemsPerPage: 10,
            dataSrc: 'items',
            onData: function (items) {
                const tbody = document.querySelector('#preview-keranjang-lab-table-body');
                if (tbody) tbody.innerHTML = '';
            }
        });

        if (previewKeranjangLabTable && typeof previewKeranjangLabTable.fetchData === 'function') {
            const origFetch = previewKeranjangLabTable.fetchData.bind(previewKeranjangLabTable);
            previewKeranjangLabTable.fetchData = function (opts = {}) {
                return origFetch(Object.assign({}, opts, {
                    reload: true
                }));
            };

            previewKeranjangLabTable.fetchData({
                reload: true
            });
        }

        setTimeout(() => {
            updateKeranjangLabCounter();
            calculateGrandTotalLab();
        }, 400);
    });

    /* =========================
       Event tombol "Pesan Sewa Ruangan Lab Baru"
       ========================= */
    document.querySelector('#add_lab').addEventListener('click', function () {
        fetch('<?php echo site_url("keranjang_lab/checkVerified") ?>', {
            method: 'GET',
            headers: {
                'Accept': 'application/json'
            }
        })
            .then(r => r.json())
            .then(data => {
                if (data.verified) {
                    const modalFormLab = new bootstrap.Modal(document.getElementById('modalFormLab'));
                    modalFormLab.show();
                } else {
                    sayAlert('warningModal', 'Verifikasi Diperlukan', 'Akun anda belum diverifikasi. Silakan lengkapi data di halaman profil.', 'warning');
                    setTimeout(() => {
                        loadContent('<?php echo site_url("profiluser") ?>');
                    }, 1200);
                }
            })
            .catch(err => {
                sayAlert('errorModal', 'Error', 'Gagal memeriksa status verifikasi.', 'warning');
            });
    });

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
                    if (previewKeranjangAlatTable && typeof previewKeranjangAlatTable.fetchData === 'function') {
                        previewKeranjangAlatTable.fetchData({
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

    /* updateKeranjangLabCounter & calculateGrandTotalLab */
    function updateKeranjangLabCounter() {
        fetch('<?= site_url("keranjang_lab/datalist") ?>')
            .then(res => res.json())
            .then(data => {
                const jumlahItem = data.items ? data.items.length : 0;

                const counterEl = document.getElementById('jumlahItemKeranjangLab');
                if (counterEl) {
                    counterEl.textContent = jumlahItem;
                }

                const keranjangKosong = document.getElementById('keranjangLabKosong');
                const previewTable = document.getElementById('preview-keranjang-lab-table');
                const btnCheckout = document.getElementById('btnCheckoutLabFromModal');

                if (jumlahItem === 0) {
                    if (keranjangKosong) keranjangKosong.style.display = 'block';
                    if (previewTable) previewTable.style.display = 'none';
                    if (btnCheckout) {
                        btnCheckout.disabled = true;
                    }
                } else {
                    if (keranjangKosong) keranjangKosong.style.display = 'none';
                    if (previewTable) previewTable.style.display = 'table';
                    if (btnCheckout) {
                        btnCheckout.disabled = false;
                    }
                }
            })
            .catch(err => {
                // Error handling
            });
    }

    function calculateGrandTotalLab() {
        fetch('<?= site_url("keranjang_lab/datalist") ?>')
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
                    document.getElementById('grandTotalLab').textContent = 'Rp ' + new Intl.NumberFormat('id-ID').format(grandTotal);
                } else {
                    document.getElementById('grandTotalLab').textContent = 'Rp 0';
                }
            })
            .catch(err => {
                // Error handling
            });
    }

    /* =========================
       Event delegation: masukkan item / checkout / delete
       ========================= */
    document.addEventListener('click', function (e) {
        // Tombol masukkan ruangan lab
        if (e.target.closest('.btnMasukkanLab')) {
            let btn = e.target.closest('.btnMasukkanLab');
            let tr = btn.closest('tr');

            let biaya = parseFloat(btn.dataset.biaya) || 0;
            let diskon = parseFloat(btn.dataset.diskon) || 0;
            let jumlahInput = tr.querySelector('.jumlah');
            let jumlah = parseInt(jumlahInput ? jumlahInput.value : 1) || 1;
            if (jumlah < 1) jumlah = 1;
            let total = (biaya * jumlah) * (1 - (diskon / 100));

            let data = {
                detUjiKode: btn.dataset.kode,
                detRuangan: btn.dataset.ruangan,
                detBiaya: biaya,
                detParameter: btn.dataset.parameter,
                detNamaLayanan: btn.dataset.namaLayanan || '',
                detDiskon: diskon,
                detJumlah: jumlah,
                detKeterangan: tr.querySelector('.keterangan') ? tr.querySelector('.keterangan').value : '',
                kode: 'C', // Kode jenis untuk ruangan lab
                detTotal: total
            };

            if (!data.detUjiKode) {
                sayAlert('errorModal', 'Gagal', 'Kode Ruangan tidak ditemukan.', 'error');
                return;
            }
            if (parseInt(data.detJumlah) < 1) {
                sayAlert('errorModal', 'Gagal', 'Jumlah hari minimal 1.', 'error');
                return;
            }

            let formData = new FormData();
            for (const key in data) formData.append(key, data[key]);

            let csrfInput = document.querySelector('input[name="<?= csrf_token() ?>"]');
            if (csrfInput) formData.append('<?= csrf_token() ?>', csrfInput.value);

            saveData({
                url: "<?= site_url('keranjang_lab/submit') ?>",
                formData: formData,
                onSuccess: function (res) {
                    if (res.xname && res.xhash) {
                        let csrfField = document.querySelector('input[name="' + res.xname + '"]');
                        if (csrfField) csrfField.value = res.xhash;
                    }
                    if (res.res === true) {
                        if (typeof table !== 'undefined' && typeof table.fetchData === 'function') table.fetchData({
                            reload: true
                        });
                        if (previewKeranjangLabTable && typeof previewKeranjangLabTable.fetchData === 'function') {
                            previewKeranjangLabTable.fetchData({
                                reload: true
                            });
                            setTimeout(function () {
                                updateKeranjangLabCounter();
                                calculateGrandTotalLab();
                            }, 400);
                        }
                        if (jumlahInput) jumlahInput.value = 1;
                        if (tr.querySelector('.keterangan')) tr.querySelector('.keterangan').value = '';
                        sayAlert('successModal', 'Berhasil', res.msg ?? 'Ruangan lab berhasil ditambahkan ke keranjang.', 'success');
                    } else {
                        sayAlert('errorModal', 'Gagal', res.msg ?? 'Terjadi kesalahan saat menambahkan ke keranjang.', 'error');
                    }
                },
                onError: function () {
                    sayAlert('errorModal', 'Gagal', 'Terjadi kesalahan koneksi ke server.', 'error');
                }
            });
        }

        if (e.target.closest('#btnCheckoutLabFromModal')) {
            e.preventDefault();

            // Validasi tanggal pelaksanaan
            const tglPelaksanaanLab = document.getElementById('tglPelaksanaanLab').value;

            if (!tglPelaksanaanLab) {
                sayAlert('errorModal', 'Gagal', 'Harap pilih tanggal pelaksanaan sewa ruangan lab!', 'error');
                return;
            }

            const btn = e.target.closest('#btnCheckoutLabFromModal');
            const customMsg = btn ? (btn.getAttribute('data-confirm') || '') : '';
            const message = customMsg || 'Apakah Anda yakin ingin melakukan checkout untuk sewa ruangan lab?';

            sayConfirm('Konfirmasi Checkout', message, () => {
                doCheckoutLab();
            }, 'success', 'checkout');
        }
    });

    /* deleteItemFromPreview - NAMA HARUS SAMA dengan yang di aksiKeranjang() */
    function deleteItemFromPreview(eOrEl) {

        let el;
        if (eOrEl instanceof Event) {
            eOrEl.preventDefault();
            el = eOrEl.currentTarget || eOrEl.target;
        } else el = eOrEl;

        if (el && !el.hasAttribute('data-index')) el = el.closest('[data-index]');
        if (!el) {
            return;
        }

        const idx = el.getAttribute('data-index');

        if (!idx) {
            return;
        }

        const deleteUrl = "<?= site_url('keranjang_lab/delete/') ?>" + idx;

        fetch(deleteUrl)
            .then(res => res.json())
            .then(data => {
                if (data.xname && data.xhash) {
                    document.querySelectorAll('[name="' + data.xname + '"]').forEach(input => input.value = data.xhash);
                }
                if (data.res === true) {
                    if (typeof table !== 'undefined' && typeof table.fetchData === 'function') table.fetchData({
                        reload: true
                    });
                    if (previewKeranjangLabTable && typeof previewKeranjangLabTable.fetchData === 'function') {
                        previewKeranjangLabTable.fetchData({
                            reload: true
                        });
                        setTimeout(function () {
                            updateKeranjangLabCounter();
                            calculateGrandTotalLab();
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

    /* doCheckoutLab */
    function doCheckoutLab() {
        const formData = new FormData();
        const csrfInput = document.querySelector('input[name="<?= csrf_token() ?>"]');
        if (csrfInput) formData.append('<?= csrf_token() ?>', csrfInput.value);

        // Tambahkan tanggal pelaksanaan
        const tglPelaksanaanLab = document.getElementById('tglPelaksanaanLab').value;
        formData.append('tglPelaksanaan', tglPelaksanaanLab);

        // Tambahkan file pendukung jika ada
        const filePendukungInput = document.getElementById('filePendukungLab');
        if (filePendukungInput && filePendukungInput.files.length > 0) {
            formData.append('filePendukung', filePendukungInput.files[0]);
        }

        const checkoutUrl = '<?= site_url("keranjang_lab/checkout") ?>';

        fetch(checkoutUrl, {
            method: 'POST',
            body: formData
        })
            .then(res => {
                return res.json();
            })
            .then(data => {

                if (data.xname && data.xhash) {
                    document.querySelectorAll('[name="' + data.xname + '"]').forEach(input => input.value = data.xhash);
                }
                if (data.res === true) {
                    if (typeof table !== 'undefined' && typeof table.fetchData === 'function') table.fetchData({
                        reload: true
                    });
                    if (previewKeranjangLabTable && typeof previewKeranjangLabTable.fetchData === 'function') {
                        previewKeranjangLabTable.fetchData({
                            reload: true
                        });
                        setTimeout(function () {
                            updateKeranjangLabCounter();
                            calculateGrandTotalLab();
                        }, 400);
                    }
                    const modalFormLab = bootstrap.Modal.getInstance(document.getElementById('modalFormLab'));
                    if (modalFormLab) modalFormLab.hide();

                    // Reset tanggal pelaksanaan dan file pendukung
                    document.getElementById('tglPelaksanaanLab').value = '';
                    document.getElementById('filePendukungLab').value = '';

                    sayAlert('successModal', 'Sukses', data.msg, 'success');
                } else {
                    sayAlert('errorModal', 'Gagal', data.msg ?? 'Checkout gagal.', 'error');
                }
            })
            .catch(err => {
                sayAlert('errorModal', 'Error', 'Terjadi kesalahan koneksi ke server.', 'error');
            });
    }
</script>