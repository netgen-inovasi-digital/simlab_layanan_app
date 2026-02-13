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

  .track .step .step-date {
    position: absolute;
    top: -28px;
    left: 50%;
    transform: translateX(-50%);
    font-size: 12px;
    color: #6c757d;
    white-space: nowrap;
    min-height: 14px;
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

  .sample-text-scroll {
    border: 1px solid #dee2e6;
    border-radius: 0.375rem;
    padding: 0.5rem 0.75rem;
    background-color: #f8f9fa;
    max-height: 160px;
    overflow-y: auto;
  }

  .sample-text-scroll p {
    margin-bottom: 0;
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

<!-- Modal Tracking -->
<div class="modal fade" id="modalTracking" data-bs-backdrop="static" data-bs-keyboard="false" tabindex="-1"
  aria-labelledby="modalTrackingLabel" aria-hidden="true">
  <div class="modal-dialog modal-xl">
    <div class="modal-content">
      <div class="modal-header">
        <h5 class="modal-title" id="modalTrackingLabel">Progress & Detail Layanan</h5>
        <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
      </div>
      <div class="modal-body">
        <!-- Status Tracking Section -->
        <div class="statusbox">
          <div class="track">
            <?php
            $steps = [
              1 => ['icon' => 'bi-person-check', 'text' => 'In Review Petugas'],
              2 => ['icon' => 'bi-gear', 'text' => 'Pengujian Dilakukan'],
              3 => ['icon' => 'bi-file-text', 'text' => 'Verifikasi Hasil Uji'],
              4 => ['icon' => 'bi-check-circle', 'text' => 'Penerbitan LHUS'],
              5 => ['icon' => 'bi-file-earmark-text', 'text' => 'Verifikasi LHU'],
              6 => ['icon' => 'bi-check-circle', 'text' => 'LHU Diterbitkan'],
              7 => ['icon' => 'bi-flag', 'text' => 'Selesai']
            ];

            // map step -> t_log_sampel column
            $stepDateField = [
              1 => 'pengecekan',
              2 => 'pengujian',
              3 => 'verifikasi_hasil_uji',
              4 => 'penerbitan_lhus',
              5 => 'verifikasi_lhu',
              6 => 'penerbitan_lhu',
              7 => 'penerbitan_lhu'
            ];

            foreach ($steps as $step => $info):
              ?>
              <div class="step" data-step="<?= $step ?>" data-field="<?= $stepDateField[$step] ?? '' ?>">
                <div class="step-date">&nbsp;</div>
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
                  <th>Metode Uji</th>
                  <th>Status</th>
                </tr>
              </thead>
              <tbody></tbody>
            </table>
          </div>
        </div>

        <!-- Sample Identity Details Section -->
        <div class="detail-table mt-4" id="sampleIdentitySection" style="display: none;">
          <h6 class="mb-3">
            Identitas Sampel:
          </h6>
          <div class="card">
            <div class="card-body">
              <div class="row">
                <div class="col-md-6 mb-3">
                  <label class="fw-bold text-muted small">Jenis Sampel:</label>
                  <p class="mb-0" id="sampleJenis">-</p>
                </div>
                <div class="col-md-6 mb-3">
                  <label class="fw-bold text-muted small">Kemasan Sampel:</label>
                  <p class="mb-0" id="sampleKemasan">-</p>
                </div>
                <div class="col-md-6 mb-3">
                  <label class="fw-bold text-muted small">Sifat Sampel:</label>
                  <p class="mb-0" id="sampleSifat">-</p>
                </div>
                <div class="col-md-6 mb-3">
                  <label class="fw-bold text-muted small">Sisa Sampel:</label>
                  <p class="mb-0" id="sampleSisa">-</p>
                </div>
                <div class="col-12 mb-3">
                  <label class="fw-bold text-muted small">Deskripsi:</label>
                  <div class="sample-text-scroll">
                    <p class="mb-0 text-wrap" id="sampleDeskripsi">-</p>
                  </div>
                </div>
                <div class="col-12">
                  <label class="fw-bold text-muted small">Keterangan Khusus:</label>
                  <div class="sample-text-scroll">
                    <p class="mb-0 text-wrap" id="sampleKeteranganKhusus">-</p>
                  </div>
                </div>
              </div>
            </div>
          </div>
        </div>

        <!-- Bukti Surat Pengantar Section (untuk ULM atau jika sudah ada file) -->
        <div class="detail-table mt-4" id="suratPengantarTrackSection" style="display: none;">
          <h6 class="mb-3">
            <i class="bi bi-file-earmark-arrow-up"></i> Bukti Surat Pengantar
          </h6>

          <!-- State: Sudah diunggah -->
          <div id="suratTrackSudahUpload" style="display: none;">
            <table class="table table-bordered mb-0">
              <tbody>
                <tr>
                  <td class="fw-semibold" style="width:35%">File Surat Pengantar <span class="text-danger">*</span></td>
                  <td>
                    <span id="suratPengantarTrackName">-</span>
                  </td>
                </tr>
                <tr>
                  <td class="fw-semibold">Format</td>
                  <td>PDF / JPG / PNG</td>
                </tr>
                <tr>
                  <td class="fw-semibold">Ukuran Maks</td>
                  <td>5 MB</td>
                </tr>
                <tr>
                  <td class="fw-semibold">Status</td>
                  <td><span class="text-success"><i class="bi bi-check-circle-fill"></i> Sudah diunggah</span></td>
                </tr>
                <tr>
                  <td class="fw-semibold">Aksi</td>
                  <td>
                    <a href="#" id="btnLihatSuratTrack" class="btn btn-sm btn-outline-primary" target="_blank"
                      rel="noopener">
                      <i class="bi bi-eye"></i> Lihat
                    </a>
                    <button type="button" class="btn btn-sm btn-outline-warning ms-1" id="btnReuploadSuratToggle">
                      <i class="bi bi-arrow-repeat"></i> Upload Ulang
                    </button>
                    <!-- Hidden file input for reupload -->
                    <input type="file" class="d-none" id="fileSuratPengantarTrack" name="fileSuratPengantarTrack"
                      accept=".pdf,.jpg,.jpeg,.png">
                  </td>
                </tr>
              </tbody>
            </table>
          </div>

          <!-- State: Belum diunggah -->
          <div id="suratTrackBelumUpload" style="display: none;">
            <table class="table table-bordered mb-0">
              <tbody>
                <tr>
                  <td class="fw-semibold" style="width:35%">File Surat Pengantar <span class="text-danger">*</span></td>
                  <td>
                    <input type="file" class="form-control" id="fileSuratPengantarTrackNew"
                      name="fileSuratPengantarTrackNew" accept=".pdf,.jpg,.jpeg,.png">
                  </td>
                </tr>
                <tr>
                  <td class="fw-semibold">Format</td>
                  <td>PDF / JPG / PNG</td>
                </tr>
                <tr>
                  <td class="fw-semibold">Ukuran Maks</td>
                  <td>5 MB</td>
                </tr>
                <tr>
                  <td class="fw-semibold">Status</td>
                  <td><span class="text-muted"><i class="bi bi-x-circle"></i> Belum diunggah</span></td>
                </tr>
              </tbody>
            </table>
            <div class="mt-2 text-end">
              <button type="button" class="btn btn-sm btn-secondary ms-1" id="btnBatalReupload" style="display: none;">
                <i class="bi bi-x-circle"></i> Batal
              </button>
            </div>
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
  var trackingDetailTable;

  // Fungsi ini akan dipanggil dari v_pelayanan.php
  function showFullTrackingModal(id, kode_layanan, status_layanan) {
    // Reset semua step
    document.querySelectorAll('.track .step').forEach(step => {
      step.classList.remove('active', 'reject');
    });

    // mapping status_layanan -> step index
    const statusToStep = {
      1: 1,
      2: 1,
      3: 1,
      4: 2,
      5: 3,
      6: 5,
      7: 5,
      8: 6,
      9: 7
    };

    // mapping untuk status "reject"
    const rejectMap = {
      2: 1
    };

    // tentukan target step berdasarkan mapping (fallback ke status_layanan jika tidak ada)
    const targetStep = (statusToStep.hasOwnProperty(status_layanan) ? statusToStep[status_layanan] : status_layanan);

    // jika status termasuk reject, tandai step yang sesuai dengan kelas 'reject'
    if (rejectMap.hasOwnProperty(status_layanan)) {
      const r = document.querySelector(`.step[data-step="${rejectMap[status_layanan]}"]`);
      if (r) {
        r.classList.remove('active');
        r.classList.add('reject');
      }
    } else {
      // aktifkan semua step sampai targetStep (ini mencegah "bergerak" jika Anda set target lebih kecil)
      document.querySelectorAll('.track .step').forEach(step => {
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
      4: 'Pengujian Dilakukan',
      5: 'Proses LHUS',
      6: 'LHUS Disetujui',
      7: 'Proses LHU',
      8: 'LHU Disetujui',
      9: 'Selesai',
    };

    const statusBadge = document.getElementById('trackStatus');
    statusBadge.textContent = statusText[status_layanan] || 'Status Tidak Diketahui';
    statusBadge.className = 'badge p-2 ' + (status_layanan === 2 ? 'bg-danger' : 'bg-success');

    if (!trackingDetailTable) {
      trackingDetailTable = createModal({
        tableId: 'tableDetail',
        apiUrl: `<?= site_url('pelayanan/detailList/') ?>${id}`,
        itemsPerPage: 5,
        showFilter: false,
        treeview: false,
        numbering: false
      });
    } else {
      trackingDetailTable.refresh({
        apiUrl: `<?= site_url('pelayanan/detailList/') ?>${id}`
      });
    }

    // Simpan kode_layanan di variabel global agar bisa diakses fungsi upload
    window._currentKodeLayan = kode_layanan;

    // Fetch sample identity data
    fetch(`<?= site_url('pelayanan/getSampleIdentity/') ?>${kode_layanan}`)
      .then(response => response.json())
      .then(data => {
        const sampleSection = document.getElementById('sampleIdentitySection');
        const suratSection = document.getElementById('suratPengantarTrackSection');

        if (data.success && data.data) {
          // Populate sample identity fields
          document.getElementById('sampleJenis').textContent = data.data.jenis || '-';
          document.getElementById('sampleKemasan').textContent = data.data.kemasan || '-';
          document.getElementById('sampleSifat').textContent = data.data.sifat || '-';
          document.getElementById('sampleSisa').textContent = data.data.sisa || '-';
          document.getElementById('sampleDeskripsi').textContent = data.data.deskripsi || '-';
          document.getElementById('sampleKeteranganKhusus').textContent = data.data.keterangan_khusus || '-';
          sampleSection.style.display = 'block';

          // Surat Pengantar - section terpisah
          const isUlm = data.data.user_identity === 'ULM';
          const hasSuratFile = data.data.surat_pengantar && data.data.surat_pengantar_url;
          
          // Tampilkan section jika: (1) user ULM, atau (2) ada file yang sudah terupload
          if (isUlm || hasSuratFile) {
            resetSuratTrackUI();
            
            if (hasSuratFile) {
              // Ada file yang sudah terupload
              document.getElementById('suratPengantarTrackName').textContent = data.data.surat_pengantar;
              document.getElementById('btnLihatSuratTrack').href = data.data.surat_pengantar_url;
              document.getElementById('suratTrackSudahUpload').style.display = 'block';
              document.getElementById('suratTrackBelumUpload').style.display = 'none';
              
              // Jika NON-ULM, sembunyikan tombol "Upload Ulang" (read-only mode)
              const btnReupload = document.getElementById('btnReuploadSuratToggle');
              if (!isUlm && btnReupload) {
                btnReupload.style.display = 'none';
              } else if (isUlm && btnReupload) {
                btnReupload.style.display = 'inline-block';
              }
            } else {
              // Belum ada file (hanya mungkin jika isUlm = true)
              document.getElementById('suratTrackSudahUpload').style.display = 'none';
              document.getElementById('suratTrackBelumUpload').style.display = 'block';
            }
            
            if (suratSection) suratSection.style.display = 'block';
          } else {
            // NON-ULM dan tidak ada file → sembunyikan section
            if (suratSection) suratSection.style.display = 'none';
          }
        } else {
          sampleSection.style.display = 'none';
          if (suratSection) suratSection.style.display = 'none';
        }
      })
      .catch(error => {
        console.error('Error fetching sample identity:', error);
        document.getElementById('sampleIdentitySection').style.display = 'none';
        document.getElementById('suratPengantarTrackSection').style.display = 'none';
      });

    // Fetch tracking data (including t_log_sampel timestamps) and populate date labels above steps
    fetch(`<?= site_url('pelayanan/getTrackingData/') ?>${id}`)
      .then(res => res.json())
      .then(payload => {
        if (payload && payload.success && payload.data && payload.data.log) {
          const log = payload.data.log;
          document.querySelectorAll('.track .step').forEach(step => {
            const field = step.dataset.field;
            const dateEl = step.querySelector('.step-date');
            if (!field || !dateEl) return;
            const val = log[field];
            dateEl.textContent = val ? val : '-';
          });
        } else {
          // clear dates if not available
          document.querySelectorAll('.track .step .step-date').forEach(el => el.textContent = '');
        }
      })
      .catch(err => {
        console.error('Error fetching tracking data:', err);
        document.querySelectorAll('.track .step .step-date').forEach(el => el.textContent = '');
      });

    // Tampilkan modal
    const trackingModal = new bootstrap.Modal(document.getElementById('modalTracking'));
    trackingModal.show();
  }

  /* === Surat Pengantar Track: Reset UI === */
  function resetSuratTrackUI() {
    const fileInput = document.getElementById('fileSuratPengantarTrack');
    const fileInputNew = document.getElementById('fileSuratPengantarTrackNew');
    if (fileInput) fileInput.value = '';
    if (fileInputNew) fileInputNew.value = '';
    document.getElementById('btnBatalReupload').style.display = 'none';
  }

  /* === Surat Pengantar Track: upload, reupload === */
  (function initSuratPengantarTrack() {
    const fileInputReupload = document.getElementById('fileSuratPengantarTrack');
    const fileInputNew = document.getElementById('fileSuratPengantarTrackNew');
    const belumUpload = document.getElementById('suratTrackBelumUpload');
    const sudahUpload = document.getElementById('suratTrackSudahUpload');
    const btnReuploadToggle = document.getElementById('btnReuploadSuratToggle');
    const btnBatalReupload = document.getElementById('btnBatalReupload');

    // Klik "Upload Ulang" -> langsung buka file picker
    if (btnReuploadToggle && fileInputReupload) {
      btnReuploadToggle.addEventListener('click', function () {
        fileInputReupload.click();
      });
    }

    // Setelah pilih file dari reupload -> langsung upload
    if (fileInputReupload) {
      fileInputReupload.addEventListener('change', function () {
        if (this.files.length > 0) {
          const file = this.files[0];
          if (!validateSuratFile(file)) {
            this.value = '';
            return;
          }
          doUploadSuratTrack(file);
        }
      });
    }

    // File input untuk state "Belum diunggah" -> langsung upload setelah pilih file
    if (fileInputNew) {
      fileInputNew.addEventListener('change', function () {
        if (this.files.length > 0) {
          const file = this.files[0];
          if (!validateSuratFile(file)) {
            this.value = '';
            return;
          }
          doUploadSuratTrack(file);
        }
      });
    }

    // Batal reupload -> kembali ke state "Sudah diunggah"
    if (btnBatalReupload) {
      btnBatalReupload.addEventListener('click', function () {
        if (belumUpload) belumUpload.style.display = 'none';
        if (sudahUpload) sudahUpload.style.display = 'block';
        if (fileInputReupload) fileInputReupload.value = '';
        if (fileInputNew) fileInputNew.value = '';
        this.style.display = 'none';
      });
    }

    function validateSuratFile(file) {
      const allowedTypes = ['application/pdf', 'image/jpeg', 'image/jpg', 'image/png'];
      if (!allowedTypes.includes(file.type)) {
        sayAlert('errorModal', 'Validasi', 'Format file tidak diizinkan. Hanya PDF, JPG, dan PNG.', 'warning');
        return false;
      }
      if (file.size > 5 * 1024 * 1024) {
        sayAlert('errorModal', 'Validasi', 'Ukuran file terlalu besar. Maksimal 5 MB.', 'warning');
        return false;
      }
      return true;
    }

    function doUploadSuratTrack(file) {
      const kodeLayan = window._currentKodeLayan;
      if (!kodeLayan) {
        sayAlert('errorModal', 'Error', 'Kode layanan tidak ditemukan.', 'error');
        return;
      }

      const formData = new FormData();
      formData.append('surat_pengantar', file);
      formData.append('kode_layanan', kodeLayan);

      // Ambil CSRF token dari input hidden yang ada di halaman (selalu up-to-date)
      const csrfInput = document.querySelector('input[name="<?= csrf_token() ?>"]');
      if (csrfInput) {
        formData.append('<?= csrf_token() ?>', csrfInput.value);
      } else {
        formData.append('<?= csrf_token() ?>', '<?= csrf_hash() ?>');
      }

      // Disable button
      if (btnReuploadToggle) btnReuploadToggle.disabled = true;

      fetch('<?= site_url('pelayanan/uploadSuratPengantar') ?>', {
        method: 'POST',
        body: formData,
        headers: {
          'X-Requested-With': 'XMLHttpRequest'
        }
      })
        .then(res => res.json())
        .then(result => {
          // Update CSRF token di semua input hidden
          if (result.xname && result.xhash) {
            document.querySelectorAll('[name="' + result.xname + '"]').forEach(input => input.value = result.xhash);
          }

          if (result.res) {
            sayAlert('errorModal', 'Berhasil', result.msg || 'Surat pengantar berhasil diunggah!', 'success');

            // Update UI ke state "Sudah diunggah"
            document.getElementById('suratPengantarTrackName').textContent = result.fileName || file.name;
            if (result.fileUrl) {
              document.getElementById('btnLihatSuratTrack').href = result.fileUrl;
            }
            if (sudahUpload) sudahUpload.style.display = 'block';
            if (belumUpload) belumUpload.style.display = 'none';
            if (btnBatalReupload) btnBatalReupload.style.display = 'none';
            if (fileInputReupload) fileInputReupload.value = '';
            if (fileInputNew) fileInputNew.value = '';
          } else {
            sayAlert('errorModal', 'Gagal', result.msg || 'Gagal mengunggah surat pengantar.', 'error');
          }
        })
        .catch(err => {
          console.error('Upload error:', err);
          sayAlert('errorModal', 'Error', 'Terjadi kesalahan koneksi ke server.', 'error');
        })
        .finally(() => {
          if (btnReuploadToggle) btnReuploadToggle.disabled = false;
        });
    }
  })();
</script>