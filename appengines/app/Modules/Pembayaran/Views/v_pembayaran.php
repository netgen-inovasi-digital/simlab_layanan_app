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
                            <th show width="5%">No.</th>
                            <th show width="15%">No. Invoice</th>
                            <th show width="20%">Pemesan</th>
                            <th show width="12%">Nilai Tagihan</th>
                            <th show width="10%">File Invoice</th>
                            <th show width="12%">Bukti Bayar</th>
                            <th show width="10%">Status</th>
                            <th>Aksi<i class="bi bi-code sort-icon"></i></th>
                        </tr>
                    </thead>
                    <tbody id="table-body">
                    </tbody>
                </table>
            </div>
        </div>
    </div>
</div>

<!-- Modal Verifikasi Pembayaran -->
<div class="modal fade" id="modalVerifikasi" data-bs-backdrop="static" data-bs-keyboard="false" tabindex="-1">
    <div class="modal-dialog modal-dialog-centered">
        <div class="modal-content">
            <div class="modal-header bg-primary text-white">
                <h5 class="modal-title">
                    <i class="bi bi-check-circle"></i> Verifikasi Pembayaran
                </h5>
                <button type="button" class="btn-close btn-close-white" data-bs-dismiss="modal"></button>
            </div>
            <form id="formVerifikasi">
                <input type="hidden" name="<?= csrf_token() ?>" value="<?= csrf_hash() ?>" class="txt_csrfname">
                <input type="hidden" name="id" id="verifikasi_id">

                <div class="modal-body">
                    <div class="alert alert-info">
                        <i class="bi bi-info-circle"></i>
                        Apakah Anda yakin ingin memverifikasi pembayaran ini?
                        Status akan diubah menjadi <strong>Lunas</strong>.
                    </div>
                </div>

                <div class="modal-footer">
                    <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">
                        <i class="bi bi-x-circle"></i> Batal
                    </button>
                    <button type="submit" class="btn btn-primary" id="btnVerifikasi">
                        <i class="bi bi-check-circle"></i> Ya, Verifikasi
                    </button>
                </div>
            </form>
        </div>
    </div>
</div>

<script>
    // Inisialisasi tabel dengan sistem sayTable
    table = createTable({
        apiUrl: '<?php echo site_url("pembayaran/dataList") ?>',
        dataSrc: 'items'
    });
    addAction();

    /**
     * Buka modal verifikasi
     */
    function verifikasiItem(id) {
        $('#verifikasi_id').val(id);
        $('#modalVerifikasi').modal('show');
    }

    /**
     * Handle verifikasi pembayaran
     */
    function handleVerifikasi() {
        const formData = $('#formVerifikasi').serialize();
        const btnVerifikasi = $('#btnVerifikasi');

        btnVerifikasi.prop('disabled', true).html('<i class="bi bi-hourglass-split"></i> Memproses...');

        $.ajax({
            url: '<?php echo site_url("pembayaran/verifikasi") ?>',
            type: 'POST',
            data: formData,
            dataType: 'json',
            success: function(response) {
                updateCSRF(response);

                if (response.status) {
                    sayAlert('successModal', 'Berhasil', response.message, 'success');
                    $('#modalVerifikasi').modal('hide');
                    if (typeof table !== 'undefined') table.fetchData({
                        reload: true
                    });
                } else {
                    sayAlert('errorModal', 'Gagal', response.message, 'error');
                }
            },
            error: function() {
                sayAlert('errorModal', 'Error', 'Terjadi kesalahan saat memverifikasi pembayaran', 'error');
            },
            complete: function() {
                btnVerifikasi.prop('disabled', false).html('<i class="bi bi-check-circle"></i> Ya, Verifikasi');
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
        $('#formVerifikasi').on('submit', function(e) {
            e.preventDefault();
            handleVerifikasi();
        });
    });
</script>