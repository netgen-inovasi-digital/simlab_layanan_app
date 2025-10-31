<style>
    .track {
        position: relative;
        background-color: #ddd;
        height: 7px;
        display: -webkit-box;
        display: -ms-flexbox;
        display: flex;
        margin-bottom: 60px;
        margin-top: 50px
    }

    .track .step {
        -webkit-box-flex: 1;
        -ms-flex-positive: 1;
        flex-grow: 1;
        width: 11%;
        margin-top: -18px;
        text-align: center;
        position: relative
    }

    .track .step.active:before {
        background: #3bb077
    }

    .track .step.reject:before {
        background: #dc3545
    }

    .track .step::before {
        height: 7px;
        position: absolute;
        content: "";
        width: 100%;
        left: 0;
        top: 18px
    }

    .track .step.active .icon {
        background: #3bb077;
        color: #fff
    }

    .track .step.reject .icon {
        background: #dc3545;
        color: #fff
    }

    .track .icon {
        display: inline-block;
        width: 40px;
        height: 40px;
        line-height: 40px;
        position: relative;
        border-radius: 100%;
        background: #ddd;
    }

    .track .step.active .text {
        font-weight: 400;
        color: #000
    }

    .track .text {
        display: block;
        margin-top: 7px;
        font-size: 13px;
        color: #666
    }

    .statusbox {
        padding: 20px;
        background-color: #fff;
        border-radius: 8px;
        margin-bottom: 20px;
    }

    .detail-table {
        margin-top: 20px;
        background: #fff;
        border-radius: 8px;
        padding: 20px;
    }
</style>

<div class="modal fade" id="modalTrack" data-bs-backdrop="static" data-bs-keyboard="false" tabindex="-1" aria-labelledby="modalTrackLabel" aria-hidden="true">
    <div class="modal-dialog modal-xl">
        <div class="modal-content">
            <div class="modal-header">
                <h5 class="modal-title" id="modalTrackLabel">Progress & Detail Layanan</h5>
                <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
            </div>
            <div class="modal-body">
                <!-- Status Tracking Section -->
                <div class="statusbox">
                    <div class="mb-3">
                        <p class="mb-0">No. Layanan: <strong id="trackLnKode"></strong></p>
                    </div>

                    <div class="track">
                        <?php
                        $steps = [
                            1 => ['icon' => 'bi-person-check', 'text' => 'Review Manajer'],
                            2 => ['icon' => 'bi-x-circle', 'text' => 'Ditolak'],
                            3 => ['icon' => 'bi-person-check', 'text' => 'Review Admin'],
                            4 => ['icon' => 'bi-gear', 'text' => 'Pengujian'],
                            5 => ['icon' => 'bi-file-text', 'text' => 'Proses LHUS'],
                            6 => ['icon' => 'bi-check-circle', 'text' => 'LHUS Disetujui'],
                            7 => ['icon' => 'bi-file-earmark-text', 'text' => 'Proses LHU'],
                            8 => ['icon' => 'bi-check-circle', 'text' => 'LHU Disetujui'],
                            9 => ['icon' => 'bi-flag', 'text' => 'Selesai']
                        ];

                        foreach ($steps as $step => $info):
                        ?>
                            <div class="step" data-step="<?= $step ?>">
                                <span class="icon">
                                    <i class="bi <?= $info['icon'] ?>"></i>
                                </span>
                                <span class="text"><?= $info['text'] ?></span>
                            </div>
                        <?php endforeach; ?>
                    </div>

                    <div class="text-center">
                        <h6>Status Saat Ini:</h6>
                        <div id="trackStatus" class="badge bg-secondary p-2">
                            Memuat...
                        </div>
                    </div>
                </div>

                <!-- Detail Table Section -->
                <div class="detail-table">
                    <h6 class="mb-3">Detail Layanan:</h6>
                    <div class="table-responsive">
                        <table class="table table-bordered table-hover" id="tableDetail">
                            <thead class="table-light">
                                <tr>
                                    <th>No</th>
                                    <th>Parameter</th>
                                    <th>Biaya</th>
                                    <th>Jumlah</th>
                                    <th>Keterangan</th>
                                    <th>Status</th>
                                </tr>
                            </thead>
                            <tbody>
                                <tr>
                                    <td colspan="6" class="text-center">Memuat data...</td>
                                </tr>
                            </tbody>
                        </table>
                    </div>
                </div>
            </div>
            <div class="modal-footer">
                <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">
                    <i class="bi bi-x-circle"></i> Tutup
                </button>
            </div>
        </div>
    </div>
</div>

<script>
    function showFullTrackingModal(id, lnKode, lnStatus) {
        // Update nomor layanan
        document.getElementById('trackLnKode').textContent = lnKode;

        // Reset semua step
        document.querySelectorAll('.track .step').forEach(step => {
            step.classList.remove('active', 'reject');
        });

        // Update status steps
        if (lnStatus == 2) {
            // Jika ditolak
            document.querySelector('.step[data-step="2"]').classList.add('reject');
        } else {
            // Update progress sampai status terkini
            document.querySelectorAll('.track .step').forEach(step => {
                if (parseInt(step.dataset.step) <= lnStatus && lnStatus != 2) {
                    step.classList.add('active');
                }
            });
        }

        // Update badge status
        const statusText = {
            1: 'Dalam Review Manajer',
            2: 'Ditolak',
            3: 'Dalam Review Admin',
            4: 'Sedang Dalam Pengujian',
            5: 'Sedang Memproses LHUS',
            6: 'LHUS Telah Disetujui',
            7: 'Sedang Memproses LHU',
            8: 'LHU Telah Disetujui',
            9: 'Selesai'
        };

        const statusBadge = document.getElementById('trackStatus');
        statusBadge.textContent = statusText[lnStatus] || 'Status Tidak Diketahui';
        statusBadge.className = 'badge p-2 ' + (lnStatus == 2 ? 'bg-danger' : 'bg-success');

        // Load detail table data
        loadDetailData(id);

        // Tampilkan modal
        const modal = new bootstrap.Modal(document.getElementById('modalTrack'));
        modal.show();
    }

    function loadDetailData(id) {
        const tbody = document.querySelector('#tableDetail tbody');
        tbody.innerHTML = '<tr><td colspan="6" class="text-center">Memuat data...</td></tr>';

        fetch(`<?= site_url('pelayanan/detailList/') ?>${id}`)
            .then(response => response.json())
            .then(data => {
                if (data.items && data.items.length > 0) {
                    tbody.innerHTML = data.items.map(item => `
                        <tr>
                            <td>${item.no}</td>
                            <td>${item.detParameter}</td>
                            <td>Rp ${item.detBiaya}</td>
                            <td>${item.detJumlah}</td>
                            <td>${item.detKeterangan}</td>
                            <td>${item.detStatus}</td>
                        </tr>
                    `).join('');
                } else {
                    tbody.innerHTML = '<tr><td colspan="6" class="text-center">Tidak ada data detail</td></tr>';
                }
            })
            .catch(error => {
                console.error('Error:', error);
                tbody.innerHTML = '<tr><td colspan="6" class="text-center text-danger">Gagal memuat data</td></tr>';
            });
    }
</script>