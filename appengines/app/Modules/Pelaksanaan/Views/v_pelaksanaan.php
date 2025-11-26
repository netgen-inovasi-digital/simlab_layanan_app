<style>
    .bubble-note {
        border-radius: 20px;
        background-color: #f8f9fa;
        border: 1px solid #dee2e6;
        padding: 1rem;
        width: 100%;
        resize: vertical;
        min-height: 120px;
    }
</style>

<div class="row">
    <div class="col-md-12">
        <div class="card">
            <div class="card-header d-flex justify-content-between align-items-center">
                <label class="card-title mb-0"><?php echo $title ?></label>

                <!-- [ADDED] Filter Status -->
                <div class="d-flex align-items-center" style="gap:8px;">
                    <label class="mb-0 small text-muted">Status:</label>
                    <select id="statusFilter" class="form-select form-select-sm" style="width:280px;">
                        <option value="">— Semua status —</option>
                        <!-- <option value="6">Memproses LHU (Semua)</option> -->
                        <option value="pending">Memproses LHU</option>
                        <option value="uploaded">LHU Terunggah</option>
                        <option value="7">LHU Disetujui</option>
                    </select>
                </div>
                <!-- [ADDED] end -->
            </div>
            <div class="card-body">
                <table id="data-table" class="saytable border-top-bottom">
                    <thead>
                        <tr>
                            <th show width="5%">No.</th>
                            <th show width="35%">Pemesan</th>
                            <th show width="10%">LHUS</th>
                            <th show width="15%">Status</th>
                            <th show width="25%" class="action text-end">Aksi</th>
                        </tr>
                    </thead>
                    <tbody id="table-body"></tbody>
                </table>
            </div>
        </div>
    </div>
</div>


<!--  Modal Detail -->
<div class="modal fade" id="modalDetail" data-bs-backdrop="static" data-bs-keyboard="false" tabindex="-1">
    <div class="modal-dialog modal-lg" role="document" style="margin: 2% auto">
        <div class="modal-content">
            <div class="modal-header">
                <h5 class="modal-title">Detail Item Layanan</h5>
                <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
            </div>
            <div class="modal-body">
                <table class="table table-bordered">
                    <thead>
                        <tr>
                            <th width="5%">No</th>
                            <th width="45%">Layanan</th>
                            <th width="10%">Jumlah</th>
                            <th width="15%">File LHUS</th>
                            <th width="15%">Upload LHUS</th>
                            <th width="15%">Acc LHUS</th>
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

<!-- Modal Upload LHU -->
<div class="modal fade" id="modalUploadLhu" data-bs-backdrop="static" data-bs-keyboard="false" tabindex="-1">
    <div class="modal-dialog modal-md" role="document" style="margin: 4% auto">
        <div class="modal-content">
            <div class="modal-header">
                <h5 class="modal-title">Upload & Kirim LHU</h5>
                <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
            </div>

            <div style="border-bottom:1px solid #e9ecef"></div>

            <div class="modal-body">
                <form id="formUploadLhu" action="<?php echo site_url('pelaksanaan/upload') ?>" method="post"
                    enctype="multipart/form-data" novalidate>
                    <!-- CSRF input (server-side) -->
                    <input type="hidden" name="<?= csrf_token() ?>" value="<?= csrf_hash() ?>">
                    <input type="hidden" name="id" id="upload_lhu_id" value="">
                    <input type="hidden" name="detKode" id="upload_detKode" value="">

                    <div class="mb-3">
                        <label for="lhu_file" class="form-label">Pilih File (jpg, png, pdf, docx, xlsx) <span
                                class="text-danger">*</span></label>

                        <div class="d-flex align-items-center gap-2">
                            <input type="file" name="lhu_file" id="lhu_file" class="form-control"
                                accept=".jpg,.jpeg,.png,.pdf,.doc,.docx,.xls,.xlsx" style="max-width:360px" required>
                            <button type="button" id="btnViewExistingLhu" class="btn btn-outline-primary btn-sm"
                                title="Lihat Bukti" disabled>
                                <i class="bi bi-eye"></i> <span class="d-none d-sm-inline">Lihat Bukti</span>
                            </button>
                        </div>

                        <div id="lhu-selection" class="form-text mt-2">Anda bisa unggah file baru untuk mengganti.</div>
                        <div class="form-text text-muted">Ukuran maksimal 5MB.</div>
                    </div>

                    <div class="mb-3">
                        <label for="tanggal_terbit_lhu" class="form-label">Tanggal Terbit LHU <span
                                class="text-danger">*</span></label>
                        <input type="date" class="form-control" id="tanggal_terbit_lhu" name="tanggal_terbit_lhu"
                            required>
                        <div class="form-text">Tentukan tanggal terbit LHU.</div>
                    </div>
                </form>
            </div>

            <div class="modal-footer justify-content-between">
                <div class="text-start">
                    <button class="btn btn-light" type="button" id="btnCancelUpload" data-bs-dismiss="modal">
                        <i class="bi bi-x-circle"></i> Batal
                    </button>
                </div>
                <div>
                    <button class="btn btn-success" id="btnUploadLhu" type="button">
                        <i class="bi bi-send-check"></i> Upload & Kirim
                    </button>
                </div>
            </div>
        </div>
    </div>
</div>


<!-- Modal Pengujian Ulang -->
<div class="modal fade" id="modalPengujianUlang" data-bs-backdrop="static" data-bs-keyboard="false" tabindex="-1">
    <div class="modal-dialog modal-md" role="document" style="margin: 4% auto">
        <div class="modal-content">
            <div class="modal-header">
                <h5 class="modal-title">Pengujian Ulang</h5>
                <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
            </div>
            <div class="modal-body">
                <form id="formPengujianUlang" action="<?= site_url('pelaksanaan/pengujian-ulang') ?>" method="post"
                    novalidate>
                    <input type="hidden" name="<?= csrf_token() ?>" value="<?= csrf_hash() ?>">
                    <input type="hidden" name="id" id="pengujianUlangId" value="">
                    <div class="mb-3">
                        <label for="catatanPengujianUlang" class="form-label">Catatan Pengujian Ulang <span
                                class="text-danger">*</span></label>
                        <textarea class="bubble-note" id="catatanPengujianUlang" name="catatan"
                            placeholder="Tuliskan alasan pengujian ulang..." required></textarea>
                        <div class="form-text">Catatan ini akan tersimpan sebagai catatan kaji ulang.</div>
                    </div>
                </form>
            </div>
            <div class="modal-footer justify-content-between">
                <button class="btn btn-light" type="button" data-bs-dismiss="modal">
                    <i class="bi bi-x-circle"></i> Batal
                </button>
                <button class="btn btn-warning" type="button" id="btnPengujianUlangSubmit">
                    <i class="bi bi-arrow-counterclockwise"></i> Buat Pengujian Ulang
                </button>
            </div>
        </div>
    </div>
</div>


<script>
    table = createTable({
        apiUrl: '<?php echo site_url("pelaksanaan/datalist") ?>',
        dataSrc: 'items'
    });
    addAction();

    function openPengujianUlangModal(encId) {
        const idInput = document.getElementById('pengujianUlangId');
        if (idInput) idInput.value = encId || '';

        const noteInput = document.getElementById('catatanPengujianUlang');
        if (noteInput) noteInput.value = '';

        const modalEl = document.getElementById('modalPengujianUlang');
        if (!modalEl) return;

        if (typeof bootstrap !== 'undefined' && bootstrap.Modal) {
            const modalInstance = bootstrap.Modal.getInstance(modalEl) || new bootstrap.Modal(modalEl);
            modalInstance.show();
        } else if (typeof $ !== 'undefined' && $('#modalPengujianUlang').modal) {
            $('#modalPengujianUlang').modal('show');
        }
    }

    // [ADDED] Helpers untuk build url + cache buster + binding filter
    if (typeof window.buildApiUrlWithOptionalParam !== 'function') {
        function buildApiUrlWithOptionalParam(path, key, value) {
            try {
                const u = new URL(path, window.location.origin);
                const params = new URLSearchParams(u.search);
                if (key && String(key) !== '') {
                    if (typeof value !== 'undefined' && value !== null && String(value) !== '') {
                        params.set(key, String(value));
                    } else {
                        params.delete(key);
                    }
                }
                const s = params.toString();
                return u.pathname + (s ? '?' + s : '');
            } catch (e) {
                if (key && String(key) !== '' && value !== null && String(value) !== '') {
                    return path + (path.includes('?') ? '&' : '?') + encodeURIComponent(key) + '=' + encodeURIComponent(String(value));
                }
                return path;
            }
        }
    }
    if (typeof window.normalizeDoubleQuestion !== 'function') {
        function normalizeDoubleQuestion(url) {
            if (typeof url !== 'string') return url;
            url = url.replace(/\?([^?]*)\?/, '?$1&');
            url = url.replace(/&{2,}/g, '&');
            url = url.replace(/\?&/, '?');
            if (url.endsWith('&')) url = url.slice(0, -1);
            return url;
        }
    }
    (function patchFetchData() {
        if (typeof table !== 'undefined' && table && typeof table.getConfig === 'function' && typeof table.fetchData === 'function' && !table.__fetchPatchedPELAK) {
            const _origFetch = table.fetchData.bind(table);
            let _currentAbort = null;
            table.fetchData = function (opts = {}) {
                try {
                    const cfg = table.getConfig();
                    if (cfg && typeof cfg.apiUrl === 'string') {
                        const u = new URL(cfg.apiUrl, window.location.origin);
                        u.searchParams.set('_ts', Date.now().toString());
                        cfg.apiUrl = normalizeDoubleQuestion(u.pathname + (u.search ? u.search : ''));
                    }
                } catch (err) { }
                try { if (_currentAbort) _currentAbort.abort(); } catch (e) { }
                try { _currentAbort = new AbortController(); opts.signal = _currentAbort.signal; } catch (e) { }
                return _origFetch(opts);
            };
            table.__fetchPatchedPELAK = true;
        }
    })();
    (function attachStatusFilter() {
        const sel = document.getElementById('statusFilter');
        if (!sel || sel.dataset.bound === '1') return;
        sel.addEventListener('change', function () {
            const val = (this.value || '').toString().trim();
            if (table?.getConfig) {
                const cfg = table.getConfig();
                cfg.apiUrl = normalizeDoubleQuestion(
                    buildApiUrlWithOptionalParam('<?php echo site_url("pelaksanaan/datalist") ?>', 'lnStatus', (val === '' ? null : val))
                );
                table.fetchData({ reload: true, page: 1 });
            }
        });
        sel.dataset.bound = '1';
    })();
    // [ADDED] end

    var btnSimpan = document.querySelector('#btnSimpan');
    if (btnSimpan) {
        btnSimpan.addEventListener('click', function (e) {
            e.preventDefault();

            const form = document.querySelector('#myform');
            const formData = new FormData(form);
            const actionUrl = form.getAttribute('action');

            saveData({
                url: actionUrl,
                formData: formData,
                onSuccess: function (data) {
                    if (data.res === true) {
                        if (typeof table !== 'undefined') table.fetchData({
                            reload: true
                        });
                        sayAlert('successModal', 'Berhasil', 'Data berhasil disimpan.', 'success');
                        if ($('#modalForm').hasClass('show')) $('#modalForm').modal('hide');
                    }
                }
            });
        });
    }

    /**
     * 🔹 Fungsi untuk simpan data ke server via AJAX
     */
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

                if ($('#modalForm').hasClass('show')) $('#modalForm').modal('hide');

                // 🔹 Kondisi response
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

    /**
     *  Tombol Proses (status 7 → LHU disetujui)
     */
    function prosesItem(e) {
        e.preventDefault();

        const trigger = e.currentTarget;
        const wrapper = trigger && trigger.closest ? trigger.closest('div') : null;
        const id = wrapper && wrapper.id ? wrapper.id : null;
        if (!id) return;

        // hindari double submit
        if (trigger.dataset.sending === '1') return;

        sayConfirm(
            'Konfirmasi',
            'Kirim LHU ini?',
            async () => {
                try {
                    trigger.dataset.sending = '1';
                    trigger.disabled = true;

                    const csrfInput = document.querySelector('[name="<?= csrf_token() ?>"]');
                    const csrfToken = csrfInput ? csrfInput.value : '';

                    const res = await fetch('<?php echo site_url("pelaksanaan/proses/") ?>' + id, {
                        method: 'POST',
                        headers: {
                            'X-Requested-With': 'XMLHttpRequest',
                            'X-CSRF-TOKEN': csrfToken
                        }
                    });

                    const data = await res.json();

                    // update CSRF jika ada
                    if (data.xname && data.xhash) {
                        document.querySelectorAll('[name="' + data.xname + '"]').forEach(input => input.value = data.xhash);
                    }

                    if (data.res) {
                        if (typeof table !== 'undefined') table.fetchData({ reload: true });
                        sayAlert('successModal', 'Berhasil', data.msg || 'Data berhasil diproses', 'success');
                    } else {
                        sayAlert('errorModal', 'Gagal', data.msg || 'Proses gagal dilakukan', 'warning');
                    }
                } catch (err) {
                    sayAlert('errorModal', 'Error', 'Terjadi kesalahan sistem', 'warning');
                } finally {
                    trigger.dataset.sending = '0';
                    trigger.disabled = false;
                }
            },
            'success',   // style tombol utama
            'Kirim',     // label tombol konfirmasi
            'Batal'      // label batal
        );
    }

    // 🔹 Tombol Lihat Detail
    function loadDetail(id) {
        const url = '<?php echo site_url("pelaksanaan/detaillist/") ?>' + id;
        const tbody = document.querySelector('#detail-body');

        // tampilkan loading
        tbody.innerHTML = '<tr><td colspan="6" class="text-center">Loading...</td></tr>';

        fetch(url, { headers: { 'X-Requested-With': 'XMLHttpRequest' } })
            .then(response => {
                if (!response.ok) {
                    return response.text().then(t => { throw new Error('HTTP ' + response.status + ': ' + t); });
                }
                return response.json();
            })
            .then(data => {
                tbody.innerHTML = '';
                if (data.items && data.items.length > 0) {
                    data.items.forEach(function (row) {
                        let tr = '<tr>';
                        row.forEach(function (col) { tr += '<td>' + col + '</td>'; });
                        tr += '</tr>';
                        tbody.innerHTML += tr;
                    });
                } else {
                    tbody.innerHTML = '<tr><td colspan="6" class="text-center">Tidak ada data</td></tr>';
                }
                // tampilkan modal
                if (typeof bootstrap !== 'undefined') {
                    const modalEl = document.getElementById('modalDetail');
                    const modal = new bootstrap.Modal(modalEl);
                    modal.show();
                } else {
                    $('#modalDetail').modal('show');
                }
            })
            .catch(error => {
                console.error('loadDetail error:', error);
                tbody.innerHTML = '<tr><td colspan="6" class="text-center text-danger">Error load data</td></tr>';
                if (typeof bootstrap !== 'undefined') {
                    const modalEl = document.getElementById('modalDetail');
                    const modal = new bootstrap.Modal(modalEl);
                    modal.show();
                } else {
                    $('#modalDetail').modal('show');
                }
            });
    }

    /* ------------------ Upload LHU modal integration ------------------ */
    function openUploadModal(encId, fileUrl = '#', detKode = '') {
        const inputId = document.getElementById('upload_lhu_id');
        if (inputId) inputId.value = encId || '';

        const inputDet = document.getElementById('upload_detKode');
        if (inputDet) inputDet.value = detKode || '';

        // reset file input
        const f = document.getElementById('lhu_file');
        if (f) f.value = '';

        // Reset tanggal terbit (kosongkan field)
        const tanggalInput = document.getElementById('tanggal_terbit_lhu');
        if (tanggalInput) {
            tanggalInput.value = '';
        }

        // set teks instruksi
        const sel = document.getElementById('lhu-selection');
        if (sel) sel.textContent = 'Anda bisa unggah file baru untuk mengganti.';

        const viewBtn = document.getElementById('btnViewExistingLhu');
        if (viewBtn) {
            if (fileUrl && fileUrl !== '#' && fileUrl !== '') {
                viewBtn.removeAttribute('disabled');
                viewBtn.setAttribute('data-url', fileUrl);
            } else {
                viewBtn.setAttribute('disabled', 'disabled');
                viewBtn.removeAttribute('data-url');
            }
        }

        // show modal
        if (typeof bootstrap !== 'undefined') {
            const modalEl = document.getElementById('modalUploadLhu');
            const modal = new bootstrap.Modal(modalEl);
            modal.show();
        } else {
            $('#modalUploadLhu').modal('show');
        }
    }

    /* buka data-url pada tombol #btnViewExistingLhu di tab baru */
    document.addEventListener('click', function (ev) {
        const target = ev.target;
        const btn = target.closest ? target.closest('#btnViewExistingLhu') : null;
        if (!btn) return;
        const url = btn.getAttribute('data-url') || btn.dataset.url || null;
        if (url && url !== '#' && url !== '') {
            const w = window.open('', '_blank');
            if (w) {
                try {
                    w.opener = null;
                    w.location = url;
                } catch (err) {
                    window.open(url, '_blank');
                }
            } else {
                window.open(url, '_blank');
            }
        } else {
            if (typeof sayAlert === 'function') {
                sayAlert('errorModal', 'Info', 'Tidak ada file bukti.', 'warning');
            } else {
                alert('Tidak ada file bukti.');
            }
        }
    });

    /* show filename when user selects a file */
    (function () {
        const fi = document.getElementById('lhu_file');
        const sel = document.getElementById('lhu-selection');
        if (!fi) return;
        fi.addEventListener('change', function (e) {
            const f = e.target.files && e.target.files[0];
            if (f) {
                if (sel) sel.textContent = 'Anda memilih: ' + f.name;
                const viewBtn = document.getElementById('btnViewExistingLhu');
                if (viewBtn) viewBtn.setAttribute('disabled', 'disabled');
            } else {
                if (sel) sel.textContent = 'Anda bisa unggah file baru untuk mengganti.';
            }
        });
    })();

    document.getElementById('btnUploadLhu')?.addEventListener('click', function (e) {
        e.preventDefault();
        const form = document.getElementById('formUploadLhu');
        const formData = new FormData(form);
        const url = form.getAttribute('action');
        const csrfInput = document.querySelector('[name="<?= csrf_token() ?>"]');
        const csrfToken = csrfInput ? csrfInput.value : '';

        // pastikan file dipilih
        const fileField = formData.get('lhu_file');
        if (!fileField || (fileField && fileField.size === 0)) {
            if (typeof sayAlert === 'function') sayAlert('errorModal', 'Error', 'Pilih file terlebih dahulu.', 'warning');
            else alert('Pilih file terlebih dahulu.');
            return;
        }

        // pastikan tanggal terbit diisi
        const tanggalTerbit = formData.get('tanggal_terbit_lhu');
        if (!tanggalTerbit || tanggalTerbit.trim() === '') {
            if (typeof sayAlert === 'function') sayAlert('errorModal', 'Error', 'Tanggal terbit LHU harus diisi.', 'warning');
            else alert('Tanggal terbit LHU harus diisi.');
            return;
        }

        showLoading();

        fetch(url, {
            method: 'POST',
            body: formData,
            headers: { 'X-CSRF-TOKEN': csrfToken }
        })
            .then(res => res.json())
            .then(data => {
                // update CSRF
                if (data.xname && data.xhash) {
                    document.querySelectorAll('[name="' + data.xname + '"]').forEach(input => input.value = data.xhash);
                }

                if (data.res === true) {
                    // alert sukses
                    if (typeof sayAlert === 'function') sayAlert('successModal', 'Berhasil', data.msg || 'File LHU berhasil diunggah dan dikirim.', 'success');

                    // tutup modal upload
                    if (typeof bootstrap !== 'undefined') {
                        const modalEl = document.getElementById('modalUploadLhu');
                        const modal = bootstrap.Modal.getInstance(modalEl);
                        if (modal) modal.hide();
                    } else {
                        $('#modalUploadLhu').modal('hide');
                    }

                    // ---- REFRESH TABEL UTAMA ----
                    if (typeof table !== 'undefined') table.fetchData({ reload: true });

                    // ---- UPDATE KOLOM STATUS MENJADI "LHU Disetujui" (status berubah ke 8) ----
                    const encId2 = document.getElementById('upload_lhu_id')?.value || '';
                    const statusEl = document.getElementById('status-cell-' + encId2);
                    if (statusEl) {
                        statusEl.innerHTML = '<span class="badge bg-success">LHU Disetujui</span>';
                    }

                    // ---- HAPUS BUTTON PROSES (karena sudah selesai) ----
                    const encId = document.getElementById('upload_lhu_id')?.value || '';
                    if (encId) {
                        const actionWrapper = document.getElementById(encId);
                        if (actionWrapper) {
                            actionWrapper.innerHTML = '<div class="text-muted"><i class="bi bi-check-circle"></i> Selesai</div>';
                        }
                    }

                    // ---- REFRESH MODAL DETAIL BILA SEDANG TERBUKA ----
                    const isDetailOpen = (
                        (typeof bootstrap !== 'undefined' && document.getElementById('modalDetail')?.classList.contains('show')) ||
                        (typeof $ !== 'undefined' && $('#modalDetail').hasClass('show'))
                    );
                    if (isDetailOpen && encId) {
                        // muat ulang isi detail untuk ln yang sama
                        loadDetail(encId);
                    }

                } else {
                    if (typeof sayAlert === 'function') sayAlert('errorModal', 'Gagal', data.msg || 'Upload gagal.', 'warning');
                    else alert(data.msg || 'Upload gagal.');
                }
            })
            .catch(err => {
                console.error(err);
                if (typeof sayAlert === 'function') sayAlert('errorModal', 'Error', 'Terjadi kesalahan saat upload.', 'warning');
                else alert('Terjadi kesalahan saat upload.');
            })
            .finally(() => {
                hideLoading();
            });
    });

    document.getElementById('btnPengujianUlangSubmit')?.addEventListener('click', function (e) {
        e.preventDefault();
        const form = document.getElementById('formPengujianUlang');
        if (!form) return;

        const idValue = (document.getElementById('pengujianUlangId')?.value || '').trim();
        const noteField = document.getElementById('catatanPengujianUlang');
        const noteValue = noteField ? noteField.value.trim() : '';

        if (idValue === '') {
            if (typeof sayAlert === 'function') sayAlert('errorModal', 'Error', 'ID layanan tidak valid.', 'warning');
            else alert('ID layanan tidak valid.');
            return;
        }

        if (noteValue === '') {
            if (typeof sayAlert === 'function') sayAlert('errorModal', 'Error', 'Catatan pengujian ulang wajib diisi.', 'warning');
            else alert('Catatan pengujian ulang wajib diisi.');
            return;
        }

        const formData = new FormData(form);
        const csrfInput = form.querySelector('[name="<?= csrf_token() ?>"]');
        const csrfToken = csrfInput ? csrfInput.value : '';

        showLoading();

        fetch(form.getAttribute('action'), {
            method: 'POST',
            body: formData,
            headers: { 'X-CSRF-TOKEN': csrfToken }
        })
            .then(res => res.json())
            .then(data => {
                if (data.xname && data.xhash) {
                    document.querySelectorAll('[name="' + data.xname + '"]').forEach(el => el.value = data.xhash);
                }

                if (data.res) {
                    if (typeof table !== 'undefined') {
                        table.fetchData({ reload: true });
                    }

                    if (typeof sayAlert === 'function') sayAlert('successModal', 'Berhasil', data.msg || 'Pengujian ulang berhasil dibuat.', 'success');
                    else alert(data.msg || 'Pengujian ulang berhasil dibuat.');

                    if (typeof bootstrap !== 'undefined' && bootstrap.Modal) {
                        const modalEl = document.getElementById('modalPengujianUlang');
                        const modalInstance = bootstrap.Modal.getInstance(modalEl);
                        if (modalInstance) modalInstance.hide();
                    } else if (typeof $ !== 'undefined' && $('#modalPengujianUlang').modal) {
                        $('#modalPengujianUlang').modal('hide');
                    }
                } else {
                    if (typeof sayAlert === 'function') sayAlert('errorModal', 'Error', data.msg || 'Pengujian ulang gagal.', 'warning');
                    else alert(data.msg || 'Pengujian ulang gagal.');
                }
            })
            .catch(err => {
                console.error(err);
                if (typeof sayAlert === 'function') sayAlert('errorModal', 'Error', 'Terjadi kesalahan saat memproses pengujian ulang.', 'warning');
                else alert('Terjadi kesalahan saat memproses pengujian ulang.');
            })
            .finally(() => {
                hideLoading();
            });
    });
</script>