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
            <form id="formUpload" enctype="multipart/form-data">
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
                    <button type="submit" class="btn btn-primary" id="btnUpload">
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
            <form id="formProses">
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
                    <button type="submit" class="btn btn-success" id="btnProses">
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
    function uploadFile(id) {
        $('#upload_id').val(id);
        $('#file_invoice').val('');
        $('#modalUpload').modal('show');
    }

    /**
     * Handle upload file invoice
     */
    function handleUpload() {
        const formData = new FormData($('#formUpload')[0]);
        const btnUpload = $('#btnUpload');

        btnUpload.prop('disabled', true).html('<i class="bi bi-hourglass-split"></i> Uploading...');

        $.ajax({
            url: '<?php echo site_url("tagihan/upload") ?>',
            type: 'POST',
            data: formData,
            processData: false,
            contentType: false,
            dataType: 'json',
            success: function(response) {
                updateCSRF(response);

                if (response.status) {
                    sayAlert('successModal', 'Berhasil', response.message, 'success');
                    $('#modalUpload').modal('hide');
                    if (typeof table !== 'undefined') table.fetchData({
                        reload: true
                    });
                } else {
                    sayAlert('errorModal', 'Gagal', response.message, 'error');
                }
            },
            error: function() {
                sayAlert('errorModal', 'Error', 'Terjadi kesalahan saat upload file', 'error');
            },
            complete: function() {
                btnUpload.prop('disabled', false).html('<i class="bi bi-cloud-upload"></i> Upload');
            }
        });
    }

    /**
     * Buka modal proses tagihan
     */
    function prosesItem(id) {
        $('#proses_id').val(id);
        $('#no_invoice').val('');
        $('#modalProses').modal('show');
    }

    /**
     * Handle proses tagihan
     */
    function handleProses() {
        const formData = $('#formProses').serialize();
        const btnProses = $('#btnProses');

        btnProses.prop('disabled', true).html('<i class="bi bi-hourglass-split"></i> Memproses...');

        $.ajax({
            url: '<?php echo site_url("tagihan/proses") ?>',
            type: 'POST',
            data: formData,
            dataType: 'json',
            success: function(response) {
                updateCSRF(response);

                if (response.status) {
                    sayAlert('successModal', 'Berhasil', response.message, 'success');
                    $('#modalProses').modal('hide');
                    if (typeof table !== 'undefined') table.fetchData({
                        reload: true
                    });
                } else {
                    sayAlert('errorModal', 'Gagal', response.message, 'error');
                }
            },
            error: function() {
                sayAlert('errorModal', 'Error', 'Terjadi kesalahan saat memproses tagihan', 'error');
            },
            complete: function() {
                btnProses.prop('disabled', false).html('<i class="bi bi-check-circle"></i> Proses & Kirim');
            }
        });
    }

    /**
     * Hapus tagihan
     */
    function deleteItem(id) {
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
        $.ajax({
            url: '<?php echo site_url("tagihan/delete") ?>',
            type: 'POST',
            data: {
                id: id,
                '<?= csrf_token() ?>': $('.txt_csrfname').val()
            },
            dataType: 'json',
            success: function(response) {
                updateCSRF(response);

                if (response.status) {
                    sayAlert('successModal', 'Berhasil', response.message, 'success');
                    if (typeof table !== 'undefined') table.fetchData({
                        reload: true
                    });
                } else {
                    sayAlert('errorModal', 'Gagal', response.message, 'error');
                }
            },
            error: function() {
                sayAlert('errorModal', 'Error', 'Terjadi kesalahan saat menghapus data', 'error');
            }
        });
    }

    /**
     * Update CSRF token
     */
    function updateCSRF(response) {
        if (response['<?= csrf_token() ?>']) {
            $('.txt_csrfname').val(response['<?= csrf_token() ?>']);
        }
    }

    // Event handlers
    $(document).ready(function() {
        $('#formUpload').on('submit', function(e) {
            e.preventDefault();
            handleUpload();
        });

        $('#formProses').on('submit', function(e) {
            e.preventDefault();
            handleProses();
        });
    });
</script>