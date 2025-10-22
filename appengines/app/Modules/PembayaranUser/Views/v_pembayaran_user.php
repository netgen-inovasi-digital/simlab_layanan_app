<div class="row">
    <div class="col-md-12">
        <div class="card">
            <div class="card-header d-flex justify-content-between align-items-center">
                <label class="card-title mb-0"><?php echo $title ?></label>
            </div>
            <div class="card-body">
                <table id="data-table" class="saytable border-top-bottom">
                    <thead>
                        <tr>
                            <th show width="5%">No</th>
                            <th show width="10%">No. Invoice</th>
                            <th show width="20%">Pemesan</th>
                            <th show width="12%">Total Biaya</th>
                            <th show width="12%">File Invoice</th>
                            <th show width="12%">Bukti Bayar</th>
                            <th show width="12%">Status</th>
                            <th show width="12%" class="text-end">Aksi</th>
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

<!-- Modal Upload Bukti Bayar -->
<div class="modal fade" id="modalUploadBukti" data-bs-backdrop="static" data-bs-keyboard="false" tabindex="-1">
    <div class="modal-dialog modal-dialog-centered">
        <div class="modal-content">
            <div class="modal-header bg-primary text-white">
                <h5 class="modal-title">
                    <i class="bi bi-upload"></i> Upload Bukti Bayar
                </h5>
                <button type="button" class="btn-close btn-close-white" data-bs-dismiss="modal"></button>
            </div>
            <form id="formUploadBukti" enctype="multipart/form-data" method="post" onsubmit="return false;">
                <input type="hidden" name="<?= csrf_token() ?>" value="<?= csrf_hash() ?>" class="txt_csrfname">
                <input type="hidden" name="id" id="upload_bukti_id">

                <div class="modal-body">
                    <!-- Tombol Lihat Bukti (jika ada file existing) -->
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
</div><!-- Modal Kirim Bukti Pembayaran -->
<div class="modal fade" id="modalKirimBukti" data-bs-backdrop="static" data-bs-keyboard="false" tabindex="-1">
    <div class="modal-dialog modal-dialog-centered">
        <div class="modal-content">
            <div class="modal-header bg-success text-white">
                <h5 class="modal-title">
                    <i class="bi bi-send"></i> Kirim Bukti Pembayaran
                </h5>
                <button type="button" class="btn-close btn-close-white" data-bs-dismiss="modal"></button>
            </div>
            <form id="formKirimBukti" method="post" onsubmit="return false;">
                <input type="hidden" name="<?= csrf_token() ?>" value="<?= csrf_hash() ?>" class="txt_csrfname">
                <input type="hidden" name="id" id="kirim_bukti_id">

                <div class="modal-body">
                    <div class="alert alert-info">
                        <i class="bi bi-info-circle"></i>
                        Setelah dikirim, bukti pembayaran akan diverifikasi oleh petugas lab.</strong>
                    </div>
                    <p>Apakah Anda yakin ingin mengirim bukti pembayaran?</p>
                </div>

                <div class="modal-footer">
                    <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">
                        <i class="bi bi-x-circle"></i> Batal
                    </button>
                    <button type="button" class="btn btn-success" id="btnKirimBukti" onclick="handleKirimBukti()">
                        <i class="bi bi-send"></i> Kirim
                    </button>
                </div>
            </form>
        </div>
    </div>
</div>


<script>
    // Inisialisasi tabel dengan sistem sayTable
    table = createTable({
        apiUrl: '<?php echo site_url("pembayaran_user/dataList") ?>',
        dataSrc: 'items'
    });
    addAction();

    /**
     * Buka modal upload bukti bayar
     */
    function uploadBukti(event) {
        const id = event.target.closest('.btn-action').parentElement.id;
        console.log('Opening upload bukti modal for ID:', id);

        // Set ID ke input hidden (vanilla JS)
        document.getElementById('upload_bukti_id').value = id;

        // Reset file input
        const fileInput = document.getElementById('file_bukti');
        if (fileInput) fileInput.value = '';

        // Reset teks instruksi
        const selText = document.getElementById('bukti-selection');
        if (selText) selText.textContent = 'Format: PNG, JPG, PDF, dll. Maksimal 5MB';

        // Cek apakah ada file existing di server (ambil dari data-fileurl attribute)
        const btnElement = event.target.closest('.btn-action');
        const fileUrl = btnElement.getAttribute('data-fileurl') || '';

        // Set tombol Lihat Bukti
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

        // Show modal menggunakan Bootstrap API
        const modalElement = document.getElementById('modalUploadBukti');
        const modal = new bootstrap.Modal(modalElement);
        modal.show();
    }

    // Handler untuk tombol Lihat Bukti Existing
    document.addEventListener('click', function(ev) {
        const btn = ev.target.closest('#btnViewExistingBukti');
        if (!btn) return;

        const url = btn.getAttribute('data-url') || '';
        if (url && url !== '#' && url !== '') {
            const w = window.open('', '_blank');
            if (w) {
                w.opener = null;
                w.location = url;
            }
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
                // Disable tombol lihat saat user memilih file baru
                if (viewBtn) viewBtn.setAttribute('disabled', 'disabled');
            } else {
                sel.textContent = 'Format: PNG, JPG, PDF, dll. Maksimal 5MB';
            }
        });
    })();

    /**
     * Handle upload bukti bayar - PembayaranUser Module
     */
    function handleUploadBukti() {
        console.log('=== PembayaranUser: handleUploadBukti called ===');

        // Validasi form menggunakan HTML5 validation
        const form = document.getElementById('formUploadBukti');
        if (!form.checkValidity()) {
            form.reportValidity();
            return;
        }

        const formData = new FormData(form);
        const btnUpload = document.getElementById('btnUploadBukti');

        // Debug: cek FormData
        console.log('Form ID:', document.getElementById('upload_bukti_id').value);
        console.log('CSRF Token:', document.querySelector('input[name="<?= csrf_token() ?>"]').value);

        // Log file jika ada
        const fileInput = document.getElementById('file_bukti');
        if (fileInput && fileInput.files && fileInput.files[0]) {
            console.log('File:', fileInput.files[0].name, fileInput.files[0].size, 'bytes');
        }

        // Explicitly add CSRF token to FormData
        const csrfName = '<?= csrf_token() ?>';
        const csrfHash = '<?= csrf_hash() ?>';
        formData.set(csrfName, csrfHash);
        console.log('=== CSRF Token Added ===', csrfName, '=', csrfHash);

        // Disable button dan ubah text
        btnUpload.disabled = true;
        btnUpload.innerHTML = '<i class="bi bi-hourglass-split"></i> Uploading...';

        console.log('=== PembayaranUser: Sending fetch request ===');

        // Gunakan fetch() API (native JavaScript, tidak butuh jQuery)
        fetch('<?php echo site_url("pembayaran_user/uploadBukti") ?>', {
                method: 'POST',
                body: formData
            })
            .then(response => {
                console.log('=== PembayaranUser: Response received ===', response.status);
                return response.json();
            })
            .then(data => {
                console.log('=== PembayaranUser: Upload response ===', data);

                // Update CSRF token
                if (data.xname && data.xhash) {
                    const csrfInput = document.querySelector('input[name="' + data.xname + '"]');
                    if (csrfInput) {
                        csrfInput.value = data.xhash;
                        console.log('=== PembayaranUser: CSRF token updated ===');
                    }
                }

                // Reset button
                btnUpload.disabled = false;
                btnUpload.innerHTML = '<i class="bi bi-cloud-upload"></i> Upload';

                if (data.res === 'success') {
                    sayAlert('successModal', 'Berhasil', data.msg, 'success');

                    // Tutup modal menggunakan Bootstrap API
                    const modalElement = document.getElementById('modalUploadBukti');
                    const modal = bootstrap.Modal.getInstance(modalElement);
                    if (modal) {
                        modal.hide();
                    } else {
                        bootstrap.Modal.getOrCreateInstance(modalElement).hide();
                    }

                    // Reload table
                    if (typeof table !== 'undefined') {
                        table.fetchData({
                            reload: true
                        });
                    }
                } else {
                    sayAlert('errorModal', 'Gagal', data.msg, 'error');
                }
            })
            .catch(error => {
                console.error('=== PembayaranUser: Upload error ===', error);

                // Reset button
                btnUpload.disabled = false;
                btnUpload.innerHTML = '<i class="bi bi-cloud-upload"></i> Upload';

                sayAlert('errorModal', 'Error', 'Terjadi kesalahan saat upload file: ' + error.message, 'error');
            });
    }

    /**
     * Buka modal kirim bukti
     */
    function kirimBukti(event) {
        // Cek apakah button disabled
        const btnElement = event.target.closest('.btn-action');
        if (btnElement.hasAttribute('disabled')) {
            sayAlert('warningModal', 'Perhatian', 'Upload bukti bayar terlebih dahulu', 'warning');
            return;
        }

        const id = btnElement.parentElement.id;

        // Set ID ke input hidden (vanilla JS)
        document.getElementById('kirim_bukti_id').value = id;

        // Show modal menggunakan Bootstrap API
        const modalElement = document.getElementById('modalKirimBukti');
        const modal = new bootstrap.Modal(modalElement);
        modal.show();
    }

    /**
     * Handle kirim bukti pembayaran
     */
    function handleKirimBukti() {
        console.log('=== PembayaranUser: handleKirimBukti called ===');

        // Validasi form menggunakan HTML5 validation
        const form = document.getElementById('formKirimBukti');
        if (!form.checkValidity()) {
            form.reportValidity();
            return;
        }

        const formData = new FormData(form);
        const btnKirim = document.getElementById('btnKirimBukti');

        console.log('=== PembayaranUser: Kirim ID ===', document.getElementById('kirim_bukti_id').value);

        // Explicitly add CSRF token to FormData
        const csrfName = '<?= csrf_token() ?>';
        const csrfHash = '<?= csrf_hash() ?>';
        formData.set(csrfName, csrfHash);
        console.log('=== CSRF Token Added ===', csrfName, '=', csrfHash);

        // Debug: Log semua FormData
        for (let pair of formData.entries()) {
            console.log('FormData:', pair[0], '=', pair[1]);
        }

        // Disable button
        btnKirim.disabled = true;
        btnKirim.innerHTML = '<i class="bi bi-hourglass-split"></i> Mengirim...';

        // Gunakan fetch() API (native JavaScript)
        fetch('<?php echo site_url("pembayaran_user/kirimBukti") ?>', {
                method: 'POST',
                body: formData
            })
            .then(response => {
                console.log('=== PembayaranUser: Kirim response status ===', response.status);
                return response.json();
            })
            .then(data => {
                console.log('=== PembayaranUser: Kirim response ===', data);

                // Update CSRF token
                if (data.xname && data.xhash) {
                    const csrfInput = document.querySelector('input[name="' + data.xname + '"]');
                    if (csrfInput) {
                        csrfInput.value = data.xhash;
                        console.log('=== PembayaranUser: CSRF token updated ===');
                    }
                }

                // Reset button
                btnKirim.disabled = false;
                btnKirim.innerHTML = '<i class="bi bi-send"></i> Kirim';

                if (data.res) {
                    sayAlert('successModal', 'Berhasil', data.msg, 'success');

                    // Tutup modal menggunakan Bootstrap API
                    const modalElement = document.getElementById('modalKirimBukti');
                    const modal = bootstrap.Modal.getInstance(modalElement);
                    if (modal) {
                        modal.hide();
                    } else {
                        bootstrap.Modal.getOrCreateInstance(modalElement).hide();
                    }

                    // Reload table
                    if (typeof table !== 'undefined') {
                        table.fetchData({
                            reload: true
                        });
                    }
                } else {
                    sayAlert('errorModal', 'Gagal', data.msg, 'error');
                }
            })
            .catch(error => {
                console.error('=== PembayaranUser: Kirim error ===', error);

                // Reset button
                btnKirim.disabled = false;
                btnKirim.innerHTML = '<i class="bi bi-send"></i> Kirim';

                sayAlert('errorModal', 'Error', 'Terjadi kesalahan saat mengirim bukti: ' + error.message, 'error');
            });
    }
</script>