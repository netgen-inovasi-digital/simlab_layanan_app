<div class="row">
    <div class="col-md-12">
        <div class="card">
            <div class="card-header d-flex justify-content-between align-items-center">
                <label class="card-title mb-0"><i class="bi bi-shield-check"></i> <?php echo $title ?></label>
            </div>
            <div class="card-body">
                <table id="data-table" class="saytable border-top-bottom">
                    <thead>
                        <tr>
                            <th show width="5%">No</th>
                            <th show width="10%">No. Invoice</th>
                            <th show width="20%">Pemesan</th>
                            <th show width="12%">Total Biaya</th>
                            <th show width="10%">File Invoice</th>
                            <th show width="10%">Bukti Bayar</th>
                            <th show width="13%">Status</th>
                            <th show width="20%" class="text-end">Aksi</th>
                        </tr>
                    </thead>
                    <tbody id="table-body"></tbody>
                </table>
            </div>
        </div>
    </div>
</div>

<style>
    .btn-action[disabled] {
        cursor: not-allowed;
        opacity: 0.5;
        pointer-events: none;
    }
</style>

<!-- Modal Upload Bukti Bayar (Admin) -->
<div class="modal fade" id="modalUploadBukti" data-bs-backdrop="static" data-bs-keyboard="false" tabindex="-1">
    <div class="modal-dialog modal-dialog-centered">
        <div class="modal-content">
            <div class="modal-header bg-primary text-white">
                <h5 class="modal-title">
                    <i class="bi bi-upload"></i> Upload Bukti Bayar (Admin)
                </h5>
                <button type="button" class="btn-close btn-close-white" data-bs-dismiss="modal"></button>
            </div>
            <form id="formUploadBukti" enctype="multipart/form-data" method="post" onsubmit="return false;">
                <input type="hidden" name="<?= csrf_token() ?>" value="<?= csrf_hash() ?>" class="txt_csrfname">
                <input type="hidden" name="id" id="upload_bukti_id">

                <div class="modal-body">
                    <!-- Tombol Lihat Bukti yang sudah ada -->
                    <div class="mb-3">
                        <button type="button" class="btn btn-sm btn-outline-info w-100" id="btnViewExistingBukti" disabled>
                            <i class="bi bi-eye"></i> Lihat Bukti Bayar yang Sudah Ada
                        </button>
                    </div>

                    <div class="mb-3">
                        <label for="file_bukti" class="form-label">
                            Pilih File Bukti Bayar <span class="text-danger">*</span>
                        </label>
                        <input type="file" class="form-control" id="file_bukti" name="file_bukti"
                            accept=".pdf,.png,.jpg,.jpeg,.gif,.bmp,.webp" required>
                        <div class="form-text" id="bukti-selection">Format: PNG, JPG, PDF, dll. Maksimal 5MB</div>
                    </div>

                    <div class="alert alert-info">
                        <i class="bi bi-info-circle"></i>
                        Sebagai admin, Anda dapat mengupload bukti bayar untuk user yang tidak dapat mengupload sendiri.
                    </div>
                </div>

                <div class="modal-footer">
                    <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">
                        <i class="bi bi-x-circle"></i> Batal
                    </button>
                    <button type="button" class="btn btn-primary" id="btnUploadBukti" onclick="handleUploadBukti()">
                        <i class="bi bi-cloud-upload"></i> Upload
                    </button>
                </div>
            </form>
        </div>
    </div>
</div>

<!-- Modal Tolak Verifikasi -->
<div class="modal fade" id="modalTolak" data-bs-backdrop="static" data-bs-keyboard="false" tabindex="-1">
    <div class="modal-dialog modal-dialog-centered">
        <div class="modal-content">
            <div class="modal-header bg-danger text-white">
                <h5 class="modal-title">
                    <i class="bi bi-x-circle"></i> Tolak Verifikasi
                </h5>
                <button type="button" class="btn-close btn-close-white" data-bs-dismiss="modal"></button>
            </div>
            <form id="formTolak" method="post" onsubmit="return false;">
                <input type="hidden" name="<?= csrf_token() ?>" value="<?= csrf_hash() ?>" class="txt_csrfname">
                <input type="hidden" name="id" id="tolak_id">

                <div class="modal-body">
                    <div class="mb-3">
                        <label for="alasan" class="form-label">
                            Alasan Penolakan <span class="text-danger">*</span>
                        </label>
                        <textarea class="form-control" id="alasan" name="alasan" rows="4"
                            placeholder="Masukkan alasan penolakan..." required></textarea>
                        <small class="text-muted">User akan melihat alasan ini</small>
                    </div>

                    <div class="alert alert-warning">
                        <i class="bi bi-exclamation-triangle"></i> <strong>Perhatian:</strong>
                        <br>Setelah ditolak, user dapat mengupload ulang bukti bayar yang benar.
                    </div>
                </div>

                <div class="modal-footer">
                    <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">
                        <i class="bi bi-x-circle"></i> Batal
                    </button>
                    <button type="button" class="btn btn-danger" id="btnTolakSubmit" onclick="handleTolakVerifikasi()">
                        <i class="bi bi-x-circle"></i> Tolak Verifikasi
                    </button>
                </div>
            </form>
        </div>
    </div>
</div>

<script>
    // Inisialisasi tabel dengan sistem sayTable
    table = createTable({
        apiUrl: '<?php echo site_url("pembayaran_admin/dataList") ?>',
        dataSrc: 'items'
    });
    addAction();

    /**
     * Upload Bukti (Admin)
     */
    function uploadBukti(event) {
        const id = event.target.closest('.btn-action').parentElement.id;
        document.getElementById('upload_bukti_id').value = id;

        const fileInput = document.getElementById('file_bukti');
        if (fileInput) fileInput.value = '';

        const selText = document.getElementById('bukti-selection');
        if (selText) selText.textContent = 'Format: PNG, JPG, PDF, dll. Maksimal 5MB';

        const btnElement = event.target.closest('.btn-action');
        const fileUrl = btnElement.getAttribute('data-fileurl') || '';

        const viewBtn = document.getElementById('btnViewExistingBukti');
        if (viewBtn) {
            if (fileUrl && fileUrl !== '#' && fileUrl !== '') {
                viewBtn.removeAttribute('disabled');
                viewBtn.classList.remove('btn-outline-info');
                viewBtn.classList.add('btn-info');
                viewBtn.setAttribute('data-url', fileUrl);
            } else {
                viewBtn.setAttribute('disabled', 'disabled');
                viewBtn.classList.remove('btn-info');
                viewBtn.classList.add('btn-outline-info');
                viewBtn.removeAttribute('data-url');
            }
        }

        const modalElement = document.getElementById('modalUploadBukti');
        const modal = new bootstrap.Modal(modalElement);
        modal.show();
    }

    // Handler untuk tombol Lihat Bukti
    document.addEventListener('click', function(ev) {
        const btn = ev.target.closest('#btnViewExistingBukti');
        if (!btn) return;

        const url = btn.getAttribute('data-url') || '';
        if (url && url !== '#' && url !== '') {
            window.open(url, '_blank');
        } else {
            sayAlert('errorModal', 'Info', 'Tidak ada file bukti.', 'warning');
        }
    });

    // Show filename when user selects a file
    (function() {
        const fi = document.getElementById('file_bukti');
        const sel = document.getElementById('bukti-selection');
        const viewBtn = document.getElementById('btnViewExistingBukti');

        if (!fi) return;

        fi.addEventListener('change', function(e) {
            const f = e.target.files && e.target.files[0];
            if (f) {
                sel.textContent = 'File dipilih: ' + f.name + ' (' + (f.size / 1024).toFixed(2) + ' KB)';
                if (viewBtn) viewBtn.setAttribute('disabled', 'disabled');
            } else {
                sel.textContent = 'Format: PNG, JPG, PDF, dll. Maksimal 5MB';
            }
        });
    })();

    /**
     * Handle Upload Bukti (Admin)
     */
    function handleUploadBukti() {
        const form = document.getElementById('formUploadBukti');
        if (!form.checkValidity()) {
            form.reportValidity();
            return;
        }

        const formData = new FormData(form);
        const btnUpload = document.getElementById('btnUploadBukti');

        // Ambil CSRF token terbaru dari hidden input
        const csrfInput = document.querySelector('input.txt_csrfname');
        const csrfName = csrfInput ? csrfInput.getAttribute('name') : '<?= csrf_token() ?>';
        const csrfHash = csrfInput ? csrfInput.value : '<?= csrf_hash() ?>';
        formData.set(csrfName, csrfHash);

        btnUpload.disabled = true;
        btnUpload.innerHTML = '<i class="bi bi-hourglass-split"></i> Uploading...';

        fetch('<?php echo site_url("pembayaran_admin/uploadBukti") ?>', {
                method: 'POST',
                body: formData
            })
            .then(response => response.json())
            .then(data => {
                // Update CSRF token untuk request berikutnya
                if (data.xname && data.xhash) {
                    const allCsrfInputs = document.querySelectorAll('input.txt_csrfname');
                    allCsrfInputs.forEach(input => {
                        input.setAttribute('name', data.xname);
                        input.value = data.xhash;
                    });
                }

                btnUpload.disabled = false;
                btnUpload.innerHTML = '<i class="bi bi-cloud-upload"></i> Upload';

                if (data.res === 'success') {
                    sayAlert('successModal', 'Berhasil', data.msg, 'success');

                    const modalElement = document.getElementById('modalUploadBukti');
                    const modal = bootstrap.Modal.getInstance(modalElement);
                    if (modal) modal.hide();

                    if (typeof table !== 'undefined') table.fetchData({
                        reload: true
                    });
                } else {
                    sayAlert('errorModal', 'Gagal', data.msg, 'error');
                }
            })
            .catch(error => {
                btnUpload.disabled = false;
                btnUpload.innerHTML = '<i class="bi bi-cloud-upload"></i> Upload';
                sayAlert('errorModal', 'Error', 'Terjadi kesalahan: ' + error.message, 'error');
            });
    }

    /**
     * Terima Verifikasi
     */
    function terimaVerifikasi(event) {
        const btnElement = event.target.closest('.btn-action');
        if (btnElement.hasAttribute('disabled')) {
            sayAlert('warningModal', 'Perhatian', 'Tidak ada yang perlu diverifikasi', 'warning');
            return;
        }

        const id = btnElement.parentElement.id;

        // Ambil CSRF token terbaru dari hidden input
        const csrfInput = document.querySelector('input.txt_csrfname');
        const csrfName = csrfInput ? csrfInput.getAttribute('name') : '<?= csrf_token() ?>';
        const csrfHash = csrfInput ? csrfInput.value : '<?= csrf_hash() ?>';

        const formData = new FormData();
        formData.append('id', id);
        formData.append(csrfName, csrfHash);

        fetch('<?php echo site_url("pembayaran_admin/terimaVerifikasi") ?>', {
                method: 'POST',
                body: formData
            })
            .then(response => response.json())
            .then(data => {
                // Update CSRF token untuk request berikutnya
                if (data.xname && data.xhash) {
                    const allCsrfInputs = document.querySelectorAll('input.txt_csrfname');
                    allCsrfInputs.forEach(input => {
                        input.setAttribute('name', data.xname);
                        input.value = data.xhash;
                    });
                }

                if (data.res === true) {
                    sayAlert('successModal', 'Berhasil', data.msg, 'success');
                    if (typeof table !== 'undefined') table.fetchData({
                        reload: true
                    });
                } else {
                    sayAlert('errorModal', 'Gagal', data.msg, 'error');
                }
            })
            .catch(error => {
                sayAlert('errorModal', 'Error', 'Terjadi kesalahan: ' + error.message, 'error');
            });
    }

    /**
     * Tolak Verifikasi (buka modal)
     */
    function tolakVerifikasi(event) {
        const btnElement = event.target.closest('.btn-action');
        if (btnElement.hasAttribute('disabled')) {
            sayAlert('warningModal', 'Perhatian', 'Tidak bisa ditolak', 'warning');
            return;
        }

        const id = btnElement.parentElement.id;
        document.getElementById('tolak_id').value = id;

        const modalElement = document.getElementById('modalTolak');
        const modal = new bootstrap.Modal(modalElement);
        modal.show();
    }

    /**
     * Handle Tolak Verifikasi
     */
    function handleTolakVerifikasi() {
        const form = document.getElementById('formTolak');
        if (!form.checkValidity()) {
            form.reportValidity();
            return;
        }

        const alasan = document.getElementById('alasan').value.trim();
        if (!alasan) {
            sayAlert('warningModal', 'Perhatian', 'Alasan penolakan harus diisi!', 'warning');
            return;
        }

        const formData = new FormData(form);
        const btnTolak = document.getElementById('btnTolakSubmit');

        // Ambil CSRF token terbaru dari hidden input
        const csrfInput = document.querySelector('input.txt_csrfname');
        const csrfName = csrfInput ? csrfInput.getAttribute('name') : '<?= csrf_token() ?>';
        const csrfHash = csrfInput ? csrfInput.value : '<?= csrf_hash() ?>';
        formData.set(csrfName, csrfHash);

        btnTolak.disabled = true;
        btnTolak.innerHTML = '<i class="bi bi-hourglass-split"></i> Memproses...';

        fetch('<?php echo site_url("pembayaran_admin/tolakVerifikasi") ?>', {
                method: 'POST',
                body: formData
            })
            .then(response => response.json())
            .then(data => {
                // Update CSRF token untuk request berikutnya
                if (data.xname && data.xhash) {
                    const allCsrfInputs = document.querySelectorAll('input.txt_csrfname');
                    allCsrfInputs.forEach(input => {
                        input.setAttribute('name', data.xname);
                        input.value = data.xhash;
                    });
                }

                btnTolak.disabled = false;
                btnTolak.innerHTML = '<i class="bi bi-x-circle"></i> Tolak Verifikasi';

                if (data.res === true) {
                    sayAlert('successModal', 'Berhasil', data.msg, 'success');

                    const modalElement = document.getElementById('modalTolak');
                    const modal = bootstrap.Modal.getInstance(modalElement);
                    if (modal) modal.hide();

                    if (typeof table !== 'undefined') table.fetchData({
                        reload: true
                    });
                } else {
                    sayAlert('errorModal', 'Gagal', data.msg, 'error');
                }
            })
            .catch(error => {
                btnTolak.disabled = false;
                btnTolak.innerHTML = '<i class="bi bi-x-circle"></i> Tolak Verifikasi';
                sayAlert('errorModal', 'Error', 'Terjadi kesalahan: ' + error.message, 'error');
            });
    }
</script>