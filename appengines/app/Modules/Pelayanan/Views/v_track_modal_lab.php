<!-- Modal Tracking Ruangan Lab -->
<style>
  /* Progress Bar Styles for Lab Room Rental */
  .track {
    position: relative;
    background-color: #ddd;
    height: 7px;
    display: flex;
    margin-bottom: 60px;
    margin-top: 50px;
  }

  .track .step {
    flex-grow: 1;
    width: 25%;
    margin-top: -18px;
    text-align: center;
    position: relative;
  }

  .track .step.active:before {
    background: #28a745;
  }

  .track .step.rejected:before {
    background: #dc3545;
  }

  .track .step:before {
    height: 7px;
    position: absolute;
    content: "";
    width: 100%;
    left: 0;
    top: 18px;
  }

  .track .step.active .icon {
    background: #28a745;
    color: #fff;
  }

  .track .step.rejected .icon {
    background: #dc3545;
    color: #fff;
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
    font-weight: 600;
    color: #000;
  }

  .track .text {
    display: block;
    margin-top: 7px;
  }

  .itemside {
    position: relative;
    display: flex;
    width: 100%;
  }

  .itemside .aside {
    position: relative;
    flex-shrink: 0;
  }

  .img-sm {
    width: 80px;
    height: 80px;
    padding: 7px;
  }

  ul.row,
  ul.row-sm {
    list-style: none;
    padding: 0;
  }

  .itemside .info {
    padding-left: 15px;
    padding-right: 7px;
  }

  .itemside .title {
    display: block;
    margin-bottom: 5px;
    color: #212529;
  }

  p {
    margin-top: 0;
    margin-bottom: 1rem;
  }

  .btn-warning {
    color: #ffffff;
    background-color: #ffc107;
    border-color: #ffc107;
    border-radius: 1px;
  }

  .btn-warning:hover {
    color: #ffffff;
    background-color: #e0a800;
    border-color: #d39e00;
  }

  #tableDetailLab {
    width: 100% !important;
  }

  #tableDetailLab th,
  #tableDetailLab td {
    padding: 8px;
    text-align: left;
    vertical-align: middle;
  }

  #tableDetailLab th {
    background-color: #f8f9fa;
    font-weight: 600;
  }
</style>

<div class="modal fade" id="modalTrackingLab" tabindex="-1" aria-labelledby="modalTrackingLabLabel" aria-hidden="true">
  <div class="modal-dialog modal-xl">
    <div class="modal-content">
      <div class="modal-header">
        <h5 class="modal-title" id="modalTrackingLabLabel">Tracking Sewa Ruangan Lab</h5>
        <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
      </div>
      <div class="modal-body">
        <article class="card">
          <header class="card-header">Info Transaksi</header>
          <div class="card-body">
            <div class="row mb-3">
              <div class="col-md-6">
                <strong>No. Transaksi:</strong> <span id="trackNoTransaksi">-</span><br>
                <strong>Tanggal:</strong> <span id="trackTanggal">-</span><br>
                <strong>Tanggal Pelaksanaan:</strong> <span id="trackTglPelaksanaan">-</span>
              </div>
              <div class="col-md-6 text-end">
                <strong>Status:</strong> <span id="trackStatus" class="badge bg-secondary">-</span>
              </div>
            </div>

            <div class="track">
              <div class="step" id="stepLab1">
                <span class="icon"><i class="fa fa-check"></i></span>
                <span class="text">In Review Petugas</span>
              </div>
              <div class="step" id="stepLab2">
                <span class="icon"><i class="fa fa-user"></i></span>
                <span class="text">Penyewaan Diterima</span>
              </div>
              <div class="step" id="stepLab3">
                <span class="icon"><i class="fa fa-clipboard-list"></i></span>
                <span class="text">Rekapitulasi Pemakaian</span>
              </div>
              <div class="step" id="stepLab4">
                <span class="icon"><i class="fa fa-flag-checkered"></i></span>
                <span class="text">Selesai</span>
              </div>
            </div>

            <hr>

            <div class="table-responsive">
              <table id="tableDetailLab" class="table table-bordered table-striped" style="width:100%">
                <thead>
                  <tr>
                    <th style="width:5%">No</th>
                    <th style="width:30%">Nama Ruangan</th>
                    <th style="width:15%">Biaya</th>
                    <th style="width:10%">Jumlah Hari</th>
                    <th style="width:25%">Keterangan</th>
                    <th style="width:15%">Status</th>
                  </tr>
                </thead>
                <tbody id="trackDetailListLab">
                  <tr>
                    <td colspan="6" class="text-center">Memuat data...</td>
                  </tr>
                </tbody>
              </table>
            </div>
          </div>
        </article>
      </div>
      <div class="modal-footer">
        <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Tutup</button>
      </div>
    </div>
  </div>
</div>

<script>
  function showFullTrackingModalLab(id, kode_layanan, status_layanan) {
    // Reset progress bar
    for (let i = 1; i <= 4; i++) {
      const step = document.getElementById('stepLab' + i);
      if (step) {
        step.classList.remove('active', 'rejected');
      }
    }

    // Status mapping untuk ruangan lab (4 steps)
    // Status dari database:
    // 1 = In Review
    // 2 = Ditolak (rejected - indicator merah)
    // 6 = Penyewaan Diterima
    // 7 = Rekapitulasi Pemakaian
    // 8 = Selesai
    // 9 = Selesai
    const statusToStep = {
      1: 1,  // In Review Petugas
      2: 1,  // Ditolak (show red on step 1)
      6: 2,  // Penyewaan Diterima
      7: 3,  // Rekapitulasi Pemakaian
      8: 4,  // Selesai
      9: 4   // Selesai
    };

    const currentStep = statusToStep[status_layanan] || 0;

    // Set active steps
    for (let i = 1; i <= currentStep; i++) {
      const step = document.getElementById('stepLab' + i);
      if (step) {
        if (status_layanan == 2 && i == 1) {
          step.classList.add('rejected');
        } else {
          step.classList.add('active');
        }
      }
    }

    // Fetch data dari controller
    fetch('<?= site_url('pelayanan-lab/getTrackingData/') ?>' + id)
      .then(response => response.json())
      .then(result => {
        if (result.success && result.data) {
          const data = result.data;

          document.getElementById('trackNoTransaksi').textContent = data.noTransaksi || '-';
          document.getElementById('trackTanggal').textContent = data.tanggal || '-';
          document.getElementById('trackTglPelaksanaan').textContent = data.tglPelaksanaan || '-';
          document.getElementById('trackStatus').textContent = data.statusText || '-';

          // Populate detail items
          const tbody = document.getElementById('trackDetailListLab');
          tbody.innerHTML = '';

          if (data.details && data.details.length > 0) {
            data.details.forEach((item, index) => {
              const tr = document.createElement('tr');
              tr.innerHTML = `
                                <td>${index + 1}</td>
                                <td>${item.nama_ruangan || '-'}</td>
                                <td>Rp ${item.biaya || '0'}</td>
                                <td>${item.jumlah || '0'} hari</td>
                                <td><div style="max-width:240px; max-height:120px; overflow-y:auto; white-space:pre-wrap; word-break:break-word;">${item.keterangan || '-'}</div></td>
                                <td>${item.status || '-'}</td>
                            `;
              tbody.appendChild(tr);
            });
          } else {
            tbody.innerHTML = '<tr><td colspan="6" class="text-center">Tidak ada data detail</td></tr>';
          }

          // Show modal
          const modal = new bootstrap.Modal(document.getElementById('modalTrackingLab'));
          modal.show();
        } else {
          Swal.fire({
            icon: 'error',
            title: 'Gagal',
            text: result.message || 'Gagal memuat data tracking'
          });
        }
      })
      .catch(error => {
        console.error('Error fetching tracking data:', error);
        Swal.fire({
          icon: 'error',
          title: 'Error',
          text: 'Terjadi kesalahan saat memuat data'
        });
      });
  }
</script>