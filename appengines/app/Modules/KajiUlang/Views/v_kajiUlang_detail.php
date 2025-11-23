<div class="row">
    <div class="col-md-12">
        <div class="card">
            <div class="card-header d-flex justify-content-between align-items-center">
                <label class="card-title mb-0 d-flex">
                    <span style="cursor:pointer" class="me-2" onclick="loadContent('kajiulang')">
				        <i class="bi bi-arrow-left-circle fs-5 text-secondary"></i></span>
                    <div><?php echo $title ?></div>
                </label>
            </div>
            <div class="card-body">
                <div class="row mb-3 ps-3">
                    <div class="col-12 col-md-4 border-start">
                        <div>Pemesan:</div>
                        <div class="fw-medium"><?php echo esc($layananData->pemesan_name ?? '-'); ?></div>
                    </div>
                    <div class="col-12 col-md-4 border-start">
                        <div>Tanggal Layanan:</div>
                        <div class="fw-medium"><?php echo esc($layananData->lnTgl ? date('d/m/Y H:i', strtotime($layananData->lnTgl)) : '-'); ?></div>
                    </div>
                    <div class="col-12 col-md-4 border-start">
                        <div>Status Layanan:</div>
                        <div class="fw-medium"><?php echo $formattedStatus; ?></div>
                    </div>
                </div>
                <hr class="mb-2">

                <!-- Sample Identity Details Section -->
                <div class="detail-table mb-4" id="sampleIdentitySection">
                    <h6 class="mb-3">Identitas Sampel:</h6>
                    <div class="card">
                        <div class="card-body">
                            <div class="row">
                                <div class="col-md-6 mb-3">
                                    <label class="fw-bold text-muted small">Jenis Sampel:</label>
                                    <p class="mb-0" id="sampleJenis"><?php echo esc($sampleData['jenis'] ?? '-'); ?></p>
                                </div>
                                <div class="col-md-6 mb-3">
                                    <label class="fw-bold text-muted small">Kemasan Sampel:</label>
                                    <p class="mb-0" id="sampleKemasan"><?php echo esc($sampleData['kemasan'] ?? '-'); ?></p>
                                </div>
                                <div class="col-md-6 mb-3">
                                    <label class="fw-bold text-muted small">Sifat Sampel:</label>
                                    <p class="mb-0" id="sampleSifat"><?php echo esc($sampleData['sifat'] ?? '-'); ?></p>
                                </div>
                                <div class="col-md-6 mb-3">
                                    <label class="fw-bold text-muted small">Sisa Sampel:</label>
                                    <p class="mb-0" id="sampleSisa"><?php echo esc($sampleData['sisa'] ?? '-'); ?></p>
                                </div>
                                <div class="col-12 mb-3">
                                    <label class="fw-bold text-muted small">Deskripsi:</label>
                                    <p class="mb-0 text-wrap" id="sampleDeskripsi"><?php echo esc($sampleData['deskripsi'] ?? '-'); ?></p>
                                </div>
                                <div class="col-12">
                                    <label class="fw-bold text-muted small">Keterangan Khusus:</label>
                                    <p class="mb-0 text-wrap" id="sampleKeteranganKhusus"><?php echo esc($sampleData['keterangan_khusus'] ?? '-'); ?></p>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>

                <div class="d-flex justify-content-between align-items-center mb-3">
                    <h6 class="mb-0">Detail Item Layanan</h6>
                    <div>
                        <button type="button" id="btnSaveKomentar" class="btn btn-primary btn-sm">
                            <i class="bi bi-save"></i> Simpan Komentar
                        </button>
                        <button type="button" id="btnKirim" class="btn btn-success btn-sm ms-2">
                            <i class="bi bi-send"></i> Kirim ke Admin
                        </button>
                    </div>
                </div>

                <table id="data-table" class="saytable border-top-bottom">
                    <thead>
                        <tr>
                            <th show width="5%">No</th>
                            <th show width="20%">Layanan</th>
                            <th show width="8%">Jumlah</th>
                            <th show width="25%">Keterangan</th>
                            <th show width="10%">Status</th>
                            <th show width="20%">Berikan keterangan</th>
                            <th show width="12%" class="text-center">Aksi</th>
                        </tr>
                    </thead>
                    <tbody id="table-body"></tbody>
                </table>
            </div>
        </div>
    </div>
</div>

<script>
table = createTable({
    apiUrl: '<?php echo site_url("kajiulang/detaillist/".$idenc) ?>',
    onLoaded: function(data) {
        // Store encrypted layanan ID for use in buttons
        window.encLnId = data.encLn;
    }
});

// Save komentar functionality
$('#btnSaveKomentar').on('click', function() {
    if (!window.encLnId) {
        alert('Data belum dimuat dengan lengkap');
        return;
    }

    let items = [];
    $('.komentar-input').each(function() {
        let ujiKode = $(this).data('uji');
        let komentar = $(this).val();
        items.push({
            ujiKode: ujiKode,
            komentar: komentar
        });
    });

    if (items.length === 0) {
        alert('Tidak ada data untuk disimpan');
        return;
    }

    $.ajax({
        url: '<?php echo site_url("kajiulang/savekomentar") ?>',
        type: 'POST',
        data: {
            lnId: window.encLnId,
            items: items,
            '<?php echo csrf_token() ?>': '<?php echo csrf_hash() ?>'
        },
        success: function(response) {
            if (response.res) {
                alert('Komentar berhasil disimpan');
                table.ajax.reload();
            } else {
                alert('Gagal menyimpan komentar: ' + response.msg);
            }
        },
        error: function() {
            alert('Terjadi kesalahan saat menyimpan komentar');
        }
    });
});

// Kirim ke admin functionality
$('#btnKirim').on('click', function() {
    if (!window.encLnId) {
        alert('Data belum dimuat dengan lengkap');
        return;
    }

    if (!confirm('Apakah Anda yakin ingin mengirim layanan ini ke admin?')) {
        return;
    }

    $.ajax({
        url: '<?php echo site_url("kajiulang/kirim") ?>',
        type: 'POST',
        data: {
            ln: window.encLnId,
            '<?php echo csrf_token() ?>': '<?php echo csrf_hash() ?>'
        },
        success: function(response) {
            if (response.res) {
                alert(response.msg);
                if (response.parent_updated) {
                    loadContent('kajiulang');
                } else {
                    table.ajax.reload();
                }
            } else {
                alert('Gagal mengirim: ' + response.msg);
            }
        },
        error: function() {
            alert('Terjadi kesalahan saat mengirim layanan');
        }
    });
});

// Approve functionality
$(document).on('click', '.btn-accept-manager', function() {
    let lnId = $(this).data('ln');
    let ujiKode = $(this).data('uji');

    $.ajax({
        url: '<?php echo site_url("kajiulang/approvedetail") ?>',
        type: 'POST',
        data: {
            ln: lnId,
            uji: ujiKode,
            '<?php echo csrf_token() ?>': '<?php echo csrf_hash() ?>'
        },
        success: function(response) {
            if (response.res) {
                alert('Berhasil disetujui');
                table.ajax.reload();
            } else {
                alert('Gagal menyetujui: ' + response.msg);
            }
        },
        error: function() {
            alert('Terjadi kesalahan saat menyetujui');
        }
    });
});

// Reject functionality
$(document).on('click', '.btn-reject-manager', function() {
    let lnId = $(this).data('ln');
    let ujiKode = $(this).data('uji');

    $.ajax({
        url: '<?php echo site_url("kajiulang/rejectdetail") ?>',
        type: 'POST',
        data: {
            ln: lnId,
            uji: ujiKode,
            '<?php echo csrf_token() ?>': '<?php echo csrf_hash() ?>'
        },
        success: function(response) {
            if (response.res) {
                alert('Berhasil ditolak');
                table.ajax.reload();
            } else {
                alert('Gagal menolak: ' + response.msg);
            }
        },
        error: function() {
            alert('Terjadi kesalahan saat menolak');
        }
    });
});
</script>