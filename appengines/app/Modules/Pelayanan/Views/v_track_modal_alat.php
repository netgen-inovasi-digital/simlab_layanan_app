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
        width: 25%;
        margin-top: -18px;
        text-align: center;
        position: relative
    }

    .track .step.active:before {
        background: var(--bs-primary);
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
        background: var(--bs-primary);
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

    .progress-track::before {
        content: '';
        position: absolute;
        top: 50%;
        left: 0;
        right: 0;
        height: 2px;
        background: #dee2e6;
        transform: translateY(-50%);
        z-index: 1;
    }

    .step-box {
        flex: 1;
        min-width: 120px;
        text-align: center;
        position: relative;
        z-index: 2;
        opacity: 0.5;
        padding: 0 1rem;
    }

    .step-box::before {
        content: '';
        position: absolute;
        top: 50%;
        left: 0;
        right: 0;
        height: 2px;
        background: #dee2e6;
        transform: translateY(-50%);
        z-index: -1;
        transition: background-color 0.3s ease;
    }

    .step-box.active {
        opacity: 1;
    }

    .step-box.active::before {
        background: #0d6efd;
    }

    .step-box.reject {
        opacity: 1;
    }

    .step-box.reject::before {
        background: #dc3545;
    }

    .step-box .step-icon {
        width: 45px;
        height: 45px;
        margin: 0 auto 0.75rem;
        background: #fff;
        border: 2px solid #dee2e6;
        border-radius: 50%;
        display: flex;
        align-items: center;
        justify-content: center;
        font-size: 1.2rem;
        position: relative;
        z-index: 3;
        transition: all 0.3s ease;
        font-size: 1.2rem;
        color: #6c757d;
    }

    .step-box.active .step-icon {
        background: #0d6efd;
        border-color: #0d6efd;
        color: #fff;
    }

    .step-box.reject .step-icon {
        background: #dc3545;
        border-color: #dc3545;
        color: #fff;
    }

    .step-box .step-text {
        font-size: 0.875rem;
        color: #6c757d;
    }

    .step-box.active .step-text {
        color: #0d6efd;
        font-weight: 500;
    }

    .step-box.reject .step-text {
        color: #dc3545;
        font-weight: 500;
    }

    .tracking-info {
        background: #f8f9fa;
        padding: 1rem;
        border-radius: 0.5rem;
    }

    .reject-step {
        position: absolute;
        top: 50%;
        left: 50%;
        transform: translate(-50%, -50%);
        display: none;
    }

    .reject-step.show {
        display: block;
    }
</style>

<!-- Modal Tracking Alat -->
<div class="modal fade" id="modalTrackingAlat" data-bs-backdrop="static" data-bs-keyboard="false" tabindex="-1"
    aria-labelledby="modalTrackingAlatLabel" aria-hidden="true">
    <div class="modal-dialog modal-xl">
        <div class="modal-content">
            <div class="modal-header">
                <h5 class="modal-title" id="modalTrackingAlatLabel">Progress & Detail Penyewaan Alat</h5>
                <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
            </div>
            <div class="modal-body">
                <!-- Info Tanggal Pelaksanaan -->
                <div class="tracking-info mb-3">
                    <div class="row">
                        <div class="col-md-6">
                            <strong>No. Transaksi:</strong>
                            <span id="trackNoTransaksiAlat">-</span>
                        </div>
                        <div class="col-md-6">
                            <strong>Tanggal Pelaksanaan:</strong>
                            <span id="trackTglPelaksanaanAlat" class="text-primary fw-bold">-</span>
                        </div>
                    </div>
                </div>

                <!-- Status Tracking Section -->
                <div class="statusbox">
                    <div class="track">
                        <?php
                        // Step untuk penyewaan alat
                        $stepsAlat = [
                            1 => ['icon' => 'bi-person-check', 'text' => 'In Review Petugas'],
                            2 => ['icon' => 'bi-check-circle', 'text' => 'Penyewaan Diterima'],
                            3 => ['icon' => 'bi-clipboard-data', 'text' => 'Rekapitulasi Pemakaian'],
                            4 => ['icon' => 'bi-flag', 'text' => 'Selesai']
                        ];

                        foreach ($stepsAlat as $step => $info):
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
                        <div id="trackStatusAlat" class="badge bg-secondary p-2">
                            Memuat...
                        </div>
                    </div>
                </div>

                <!-- Detail Table Section -->
                <div class="detail-table">
                    <h6 class="mb-3">Detail Alat yang Disewa:</h6>
                    <div class="table-responsive">
                        <table class="table table-bordered table-hover" id="tableDetailAlat">
                            <thead class="table-light">
                                <tr>
                                    <th>No</th>
                                    <th>Nama Alat</th>
                                    <th>Biaya</th>
                                    <th>Jumlah</th>
                                    <th>Keterangan</th>
                                    <th>Status</th>
                                </tr>
                            </thead>
                            <tbody></tbody>
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
    var trackingDetailTableAlat;

    // Fungsi ini akan dipanggil dari v_pelayanan_alat.php
    function showTrackingModal(id, lnKode, lnStatus) {
        // Reset semua step
        document.querySelectorAll('#modalTrackingAlat .track .step').forEach(step => {
            step.classList.remove('active', 'reject');
        });

        // Mapping lnStatus -> step index untuk penyewaan alat
        const statusToStep = {
            1: 1,  // Review Petugas
            2: 1,  // Ditolak (reject di step 1)
            6: 2,  // Penyewaan Diterima
            7: 3,  // Rekapitulasi Pemakaian
            8: 4,  // Selesai
            9: 4   // Selesai
        };

        // Mapping untuk status "reject"
        const rejectMap = {
            2: 1  // Ditolak di step 1
        };

        // Tentukan target step berdasarkan mapping (fallback ke lnStatus jika tidak ada)
        const targetStep = (statusToStep.hasOwnProperty(lnStatus) ? statusToStep[lnStatus] : 1);

        // Jika status termasuk reject, tandai step yang sesuai dengan kelas 'reject'
        if (rejectMap.hasOwnProperty(lnStatus)) {
            const r = document.querySelector(`#modalTrackingAlat .step[data-step="${rejectMap[lnStatus]}"]`);
            if (r) {
                r.classList.remove('active');
                r.classList.add('reject');
            }
        } else {
            // Aktifkan semua step sampai targetStep
            document.querySelectorAll('#modalTrackingAlat .track .step').forEach(step => {
                const stepNum = parseInt(step.dataset.step, 10);
                if (stepNum <= targetStep) {
                    step.classList.add('active');
                }
            });
        }

        // Update badge status
        const statusText = {
            1: 'In Review Petugas',
            2: 'Ditolak',
            3: 'In Review Petugas',
            6: 'Penyewaan Diterima',
            7: 'Rekapitulasi Pemakaian',
            8: 'Selesai',
            9: 'Selesai'
        };

        const statusBadge = document.getElementById('trackStatusAlat');
        statusBadge.textContent = statusText[lnStatus] || 'Status Tidak Diketahui';
        statusBadge.className = 'badge p-2 ' + (lnStatus === 2 ? 'bg-danger' : 'bg-success');

        // Fetch data tracking untuk mendapatkan tanggal pelaksanaan
        fetch(`<?= site_url('pelayanan_alat/getTrackingData/') ?>${id}`)
            .then(response => response.json())
            .then(data => {
                if (data.success) {
                    // Update No Transaksi
                    document.getElementById('trackNoTransaksiAlat').textContent = data.data.noTransaksi || '-';

                    // Update Tanggal Pelaksanaan
                    document.getElementById('trackTglPelaksanaanAlat').textContent = data.data.tglPelaksanaan || '-';
                }
            })
            .catch(error => {
                console.error('Error fetching tracking data:', error);
            });

        // Setup table detail menggunakan createModal (sama seperti v_track_modal.php)
        if (!trackingDetailTableAlat) {
            trackingDetailTableAlat = createModal({
                tableId: 'tableDetailAlat',
                apiUrl: `<?= site_url('pelayanan_alat/detailList/') ?>${id}`,
                itemsPerPage: 5,
                showFilter: false,
                treeview: false,
                numbering: false
            });
        } else {
            trackingDetailTableAlat.refresh({
                apiUrl: `<?= site_url('pelayanan_alat/detailList/') ?>${id}`
            });
        }

        // Tampilkan modal
        const trackingModalAlat = new bootstrap.Modal(document.getElementById('modalTrackingAlat'));
        trackingModalAlat.show();
    }
</script>