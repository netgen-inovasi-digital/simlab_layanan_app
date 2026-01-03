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
    width: 20%;
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

<!-- Modal Tracking Rapat JAS -->
<div class="modal fade" id="modalTrackingRapatJas" data-bs-backdrop="static" data-bs-keyboard="false" tabindex="-1"
  aria-labelledby="modalTrackingRapatJasLabel" aria-hidden="true">
  <div class="modal-dialog modal-xl">
    <div class="modal-content">
      <div class="modal-header bg-primary text-white">
        <h5 class="modal-title" id="modalTrackingRapatJasLabel">
          <i class="bi bi-calendar-event"></i> Progress & Detail Layanan Rapat JAS
        </h5>
        <button type="button" class="btn-close btn-close-white" data-bs-dismiss="modal" aria-label="Close"></button>
      </div>
      <div class="modal-body">
        <!-- Status Tracking Section -->
        <div class="statusbox">
          <div class="track">
            <?php
            // 🔥 Tracking khusus untuk Rapat JAS (3 step sederhana)
            $stepsRapatJas = [
              3 => ['icon' => 'bi-person-check', 'text' => 'Direview Petugas'],
              4 => ['icon' => 'bi-calendar-check', 'text' => 'Pelaksanaan'],
              5 => ['icon' => 'bi-check-circle-fill', 'text' => 'Pelaksanaan Telah Selesai']
            ];

            foreach ($stepsRapatJas as $step => $info):
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
            <div id="trackStatusRapatJas" class="badge bg-secondary p-2">
              Memuat...
            </div>
          </div>

          <!-- Info Pembayaran (hanya muncul di status Pelaksanaan) -->
          <div id="paymentInfoBox" class="alert alert-info mt-3" style="display: none;">
            <i class="bi bi-info-circle"></i>
            <strong>Informasi:</strong> Anda sudah dapat melakukan pembayaran untuk layanan ini.
          </div>
        </div>

        <!-- Detail Table Section -->
        <div class="detail-table">
          <h6 class="mb-3">Detail Layanan Rapat JAS:</h6>
          <div class="table-responsive">
            <table class="table table-bordered table-hover" id="tableDetailRapatJas">
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
  var trackingDetailTableRapatJas;

  // Fungsi ini akan dipanggil dari v_pelayanan_rapat_jas.php
  function showFullTrackingModalRapatJas(id, kode_layanan, status_layanan) {
    // Reset semua step
    document.querySelectorAll('#modalTrackingRapatJas .track .step').forEach(step => {
      step.classList.remove('active', 'reject');
    });

    // 🔥 Mapping status untuk Rapat JAS (3 step sederhana):
    // 3 = Direview Petugas
    // 4 = Pelaksanaan (bisa pembayaran)
    // 5 = Pelaksanaan Telah Selesai

    const statusToStep = {
      0: 3,  // Pendaftaran → Direview Petugas
      1: 3,  // Review Manajer → Direview Petugas
      2: 3,  // Ditolak → Direview Petugas (tetap di review)
      3: 3,  // Review Admin → Direview Petugas
      4: 4,  // Pelaksanaan → Pelaksanaan
      5: 5,  // Selesai → Pelaksanaan Telah Selesai
      9: 5   // Selesai → Pelaksanaan Telah Selesai
    };

    const targetStep = (statusToStep.hasOwnProperty(status_layanan) ? statusToStep[status_layanan] : 3);

    // Aktifkan semua step sampai targetStep
    document.querySelectorAll('#modalTrackingRapatJas .track .step').forEach(step => {
      const stepNum = parseInt(step.dataset.step, 10);
      if (stepNum <= targetStep) {
        step.classList.add('active');
      }
    });

    // Update badge status
    const statusText = {
      0: 'Pendaftaran',
      1: 'Review Manajer',
      2: 'Ditolak',
      3: 'Direview Petugas',
      4: 'Pelaksanaan',
      5: 'Pelaksanaan Telah Selesai',
      9: 'Pelaksanaan Telah Selesai',
    };

    const statusBadge = document.getElementById('trackStatusRapatJas');
    statusBadge.textContent = statusText[status_layanan] || 'Status Tidak Diketahui';

    // Set warna badge
    if (status_layanan === 2) {
      statusBadge.className = 'badge p-2 bg-danger';
    } else if (status_layanan === 5 || status_layanan === 9) {
      statusBadge.className = 'badge p-2 bg-success';
    } else if (status_layanan === 4) {
      statusBadge.className = 'badge p-2 bg-info';
    } else {
      statusBadge.className = 'badge p-2 bg-warning';
    }

    // Tampilkan info pembayaran jika status = 4 (Pelaksanaan)
    const paymentInfoBox = document.getElementById('paymentInfoBox');
    if (status_layanan === 4) {
      paymentInfoBox.style.display = 'block';
    } else {
      paymentInfoBox.style.display = 'none';
    }

    // Load detail table
    if (!trackingDetailTableRapatJas) {
      trackingDetailTableRapatJas = createModal({
        tableId: 'tableDetailRapatJas',
        apiUrl: `<?= site_url('pelayananrapatjas/detailList/') ?>${id}`,
        itemsPerPage: 10,
        showFilter: false,
        treeview: false,
        numbering: false
      });
    } else {
      trackingDetailTableRapatJas.refresh({
        apiUrl: `<?= site_url('pelayananrapatjas/detailList/') ?>${id}`
      });
    }

    // Tampilkan modal
    const trackingModal = new bootstrap.Modal(document.getElementById('modalTrackingRapatJas'));
    trackingModal.show();
  }
</script>