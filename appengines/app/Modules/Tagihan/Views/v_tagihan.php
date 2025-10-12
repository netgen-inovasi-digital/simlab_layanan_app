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
                            <th show width="15%">Nomor Invoice</th>
                            <th show width="20%">Pemesan</th>
                            <th show width="15%">Nilai Tagihan</th>
                            <th show width="15%">File Invoice</th>
                            <th show width="15%">Status</th>
                            <th show width="10%">Aksi</th>
                        </tr>
                    </thead>
                    <tbody id="table-body"></tbody>
                </table>
            </div>
        </div>
    </div>
</div>

<style>
    /* Styling untuk button disabled */
    .btn-action[disabled] {
        cursor: not-allowed;
        opacity: 0.5;
        pointer-events: none;
    }
</style>

<!-- Modal Upload File Invoice -->
<div class="modal fade" id="modalUpload" data-bs-backdrop="static" data-bs-keyboard="false" tabindex="-1">
    <div class="modal-dialog modal-dialog-centered">
        <div class="modal-content">
            <div class="modal-header bg-primary text-white">
                <h5 class="modal-title">
                    <i class="bi bi-upload"></i> Upload File Invoice
                </h5>
                <button type="button" class="btn-close btn-close-white" data-bs-dismiss="modal"></button>
            </div>
            <form id="formUpload" enctype="multipart/form-data" method="post" onsubmit="return false;">
                <input type="hidden" name="<?= csrf_token() ?>" value="<?= csrf_hash() ?>" class="txt_csrfname">
                <input type="hidden" name="id" id="upload_id">

                <div class="modal-body">
                    <div class="mb-3">
                        <label for="file_invoice" class="form-label">
                            Pilih File Invoice (PDF) <span class="text-danger">*</span>
                        </label>
                        <input type="file" class="form-control" id="file_invoice" name="file_invoice" accept=".pdf" required>
                        <div class="form-text">Format: PDF, Maksimal 5MB</div>
                    </div>
                </div>

                <div class="modal-footer">
                    <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">
                        <i class="bi bi-x-circle"></i> Batal
                    </button>
                    <button type="button" class="btn btn-primary" id="btnUpload" onclick="handleUploadInvoice()">
                        <i class="bi bi-cloud-upload"></i> Upload
                    </button>
                </div>
            </form>
        </div>
    </div>
</div>

<!-- Modal Proses Tagihan (Tambah Nomor Invoice) -->
<div class="modal fade" id="modalProses" data-bs-backdrop="static" data-bs-keyboard="false" tabindex="-1">
    <div class="modal-dialog modal-dialog-centered">
        <div class="modal-content">
            <div class="modal-header bg-success text-white">
                <h5 class="modal-title">
                    <i class="bi bi-check-circle"></i> Proses Tagihan
                </h5>
                <button type="button" class="btn-close btn-close-white" data-bs-dismiss="modal"></button>
            </div>
            <form id="formProses" method="post" onsubmit="return false;">
                <input type="hidden" name="<?= csrf_token() ?>" value="<?= csrf_hash() ?>" class="txt_csrfname">
                <input type="hidden" name="id" id="proses_id">

                <div class="modal-body">
                    <div class="mb-3">
                        <label for="no_invoice" class="form-label">
                            Nomor Invoice <span class="text-danger">*</span>
                        </label>
                        <input type="text" class="form-control" id="no_invoice" name="no_invoice"
                            placeholder="Contoh: INV/2025/001" required>
                        <div class="form-text">Masukkan nomor invoice yang unik</div>
                    </div>
                    <div class="alert alert-info">
                        <i class="bi bi-info-circle"></i>
                        Setelah diproses, status tagihan akan berubah menjadi <strong>"Terkirim"</strong>
                    </div>
                </div>

                <div class="modal-footer">
                    <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">
                        <i class="bi bi-x-circle"></i> Batal
                    </button>
                    <button type="button" class="btn btn-success" id="btnProses" onclick="handleProses()">
                        <i class="bi bi-check-circle"></i> Proses & Kirim
                    </button>
                </div>
            </form>
        </div>
    </div>
</div>


<script>
    // Inisialisasi tabel dengan sistem sayTable
    table = createTable({
        apiUrl: '<?php echo site_url("tagihan/dataList") ?>',
        dataSrc: 'items'
    });
    addAction();

    /**
     * Buka modal upload file
     */
    function uploadFile(event) {
        const id = event.target.closest('.btn-action').parentElement.id;
        console.log('Opening upload modal for ID:', id);

        // Set ID ke input hidden (vanilla JS)
        document.getElementById('upload_id').value = id;

        // Reset file input
        document.getElementById('file_invoice').value = '';

        // Show modal menggunakan Bootstrap API
        const modalElement = document.getElementById('modalUpload');
        const modal = new bootstrap.Modal(modalElement);
        modal.show();
    }

    /**
     * Handle upload file invoice - Tagihan Module
     */
    function handleUploadInvoice() {
        console.log('=== Tagihan: handleUploadInvoice called ===');

        // Validasi form menggunakan HTML5 validation
        const form = document.getElementById('formUpload');
        if (!form.checkValidity()) {
            form.reportValidity();
            return;
        }

        const formData = new FormData(form);
        const btnUpload = document.getElementById('btnUpload');

        // Debug: cek FormData
        console.log('Form ID:', document.getElementById('upload_id').value);
        console.log('CSRF Token:', document.querySelector('input[name="<?= csrf_token() ?>"]').value);

        // Log file jika ada
        const fileInput = document.getElementById('file_invoice');
        if (fileInput && fileInput.files && fileInput.files[0]) {
            console.log('File:', fileInput.files[0].name, fileInput.files[0].size, 'bytes');
        }

        // Disable button dan ubah text
        btnUpload.disabled = true;
        btnUpload.innerHTML = '<i class="bi bi-hourglass-split"></i> Uploading...';

        console.log('=== Tagihan: Sending fetch request ===');

        // Gunakan fetch() API (native JavaScript, tidak butuh jQuery)
        fetch('<?php echo site_url("tagihan/upload") ?>', {
                method: 'POST',
                body: formData
            })
            .then(response => {
                console.log('=== Tagihan: Response received ===', response.status);
                return response.json();
            })
            .then(data => {
                console.log('=== Tagihan: Upload response ===', data);

                // Update CSRF token
                if (data.xname && data.xhash) {
                    const csrfInput = document.querySelector('input[name="' + data.xname + '"]');
                    if (csrfInput) {
                        csrfInput.value = data.xhash;
                        console.log('=== Tagihan: CSRF token updated ===');
                    }
                }

                // Reset button
                btnUpload.disabled = false;
                btnUpload.innerHTML = '<i class="bi bi-cloud-upload"></i> Upload';

                if (data.res === 'success') {
                    sayAlert('successModal', 'Berhasil', data.msg, 'success');

                    // Tutup modal menggunakan Bootstrap API
                    const modalElement = document.getElementById('modalUpload');
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
                console.error('=== Tagihan: Upload error ===', error);

                // Reset button
                btnUpload.disabled = false;
                btnUpload.innerHTML = '<i class="bi bi-cloud-upload"></i> Upload';

                sayAlert('errorModal', 'Error', 'Terjadi kesalahan saat upload file: ' + error.message, 'error');
            });
    }

    /**
     * Buka modal proses tagihan
     */
    function prosesItem(event) {
        // Cek apakah button disabled
        const btnElement = event.target.closest('.btn-action');
        if (btnElement.hasAttribute('disabled')) {
            sayAlert('warningModal', 'Perhatian', 'Upload file invoice terlebih dahulu sebelum memproses tagihan', 'warning');
            return;
        }

        const id = btnElement.parentElement.id;

        // Set ID ke input hidden (vanilla JS)
        document.getElementById('proses_id').value = id;

        // Reset nomor invoice
        document.getElementById('no_invoice').value = '';

        // Show modal menggunakan Bootstrap API
        const modalElement = document.getElementById('modalProses');
        const modal = new bootstrap.Modal(modalElement);
        modal.show();
    }

    /**
     * Handle proses tagihan
     */
    function handleProses() {
        console.log('=== Tagihan: handleProses called ===');

        // Validasi form menggunakan HTML5 validation
        const form = document.getElementById('formProses');
        if (!form.checkValidity()) {
            form.reportValidity();
            return;
        }

        const formData = new FormData(form);
        const btnProses = document.getElementById('btnProses');

        console.log('=== Tagihan: Proses ID ===', document.getElementById('proses_id').value);
        console.log('=== Tagihan: No Invoice ===', document.getElementById('no_invoice').value);

        // Disable button
        btnProses.disabled = true;
        btnProses.innerHTML = '<i class="bi bi-hourglass-split"></i> Memproses...';

        // Gunakan fetch() API (native JavaScript)
        fetch('<?php echo site_url("tagihan/proses") ?>', {
                method: 'POST',
                body: formData
            })
            .then(response => {
                console.log('=== Tagihan: Proses response status ===', response.status);
                return response.json();
            })
            .then(data => {
                console.log('=== Tagihan: Proses response ===', data);

                // Update CSRF token
                if (data.xname && data.xhash) {
                    const csrfInput = document.querySelector('input[name="' + data.xname + '"]');
                    if (csrfInput) {
                        csrfInput.value = data.xhash;
                        console.log('=== Tagihan: CSRF token updated ===');
                    }
                }

                // Reset button
                btnProses.disabled = false;
                btnProses.innerHTML = '<i class="bi bi-check-circle"></i> Proses & Kirim';

                if (data.res) {
                    sayAlert('successModal', 'Berhasil', data.msg, 'success');

                    // Tutup modal menggunakan Bootstrap API
                    const modalElement = document.getElementById('modalProses');
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
                console.error('=== Tagihan: Proses error ===', error);

                // Reset button
                btnProses.disabled = false;
                btnProses.innerHTML = '<i class="bi bi-check-circle"></i> Proses & Kirim';

                sayAlert('errorModal', 'Error', 'Terjadi kesalahan saat memproses tagihan: ' + error.message, 'error');
            });
    }

    /**
     * Hapus tagihan
     */
    function deleteItem(event) {
        const id = event.target.closest('.btn-action').parentElement.id;
        sayConfirm(
            'Konfirmasi Hapus',
            'Apakah Anda yakin ingin menghapus tagihan ini? File invoice juga akan terhapus.',
            function() {
                executeDelete(id);
            }
        );
    }

    /**
     * Eksekusi hapus tagihan
     */
    function executeDelete(id) {
        console.log('=== Tagihan: executeDelete called for ID ===', id);

        const csrfToken = document.querySelector('.txt_csrfname').value;
        const csrfName = '<?= csrf_token() ?>';

        // Buat FormData untuk delete request
        const formData = new FormData();
        formData.append('id', id);
        formData.append(csrfName, csrfToken);

        // Gunakan fetch() API
        fetch('<?php echo site_url("tagihan/delete") ?>', {
                method: 'POST',
                body: formData
            })
            .then(response => {
                console.log('=== Tagihan: Delete response status ===', response.status);
                return response.json();
            })
            .then(data => {
                console.log('=== Tagihan: Delete response ===', data);

                // Update CSRF token
                if (data.xname && data.xhash) {
                    const csrfInput = document.querySelector('input[name="' + data.xname + '"]');
                    if (csrfInput) {
                        csrfInput.value = data.xhash;
                        console.log('=== Tagihan: CSRF token updated ===');
                    }
                }

                if (data.res) {
                    sayAlert('successModal', 'Berhasil', data.msg, 'success');
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
                console.error('=== Tagihan: Delete error ===', error);
                sayAlert('errorModal', 'Error', 'Terjadi kesalahan saat menghapus data: ' + error.message, 'error');
            });
    }
</script>