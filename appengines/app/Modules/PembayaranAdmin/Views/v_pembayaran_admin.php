<div class="row page-pembayaran">
  <div class="col-md-12">
    <div class="card">
      <div class="card-header d-flex justify-content-between align-items-center">
        <label class="card-title mb-0"><i class="bi bi-shield-check"></i>
          <?php echo "Invoice dan Verifikasi Pembayaran" ?></label>
      </div>
      <div class="card-body">
        <!-- Filter Section -->
        <div class="row mb-3">
          <div class="col-12">
            <div class="card border-0 bg-light">
              <div class="card-body py-3">
                <label class="form-label fw-bold mb-3"><i class="bi bi-funnel"></i> Filter Data</label>
                <div class="row g-2 align-items-end">
                  <div class="col-md-3">
                    <label for="tanggal_awal" class="form-label small">Tanggal Awal</label>
                    <input type="date" id="tanggal_awal" class="form-control">
                  </div>
                  <div class="col-md-3">
                    <label for="tanggal_akhir" class="form-label small">Tanggal Akhir</label>
                    <input type="date" id="tanggal_akhir" class="form-control">
                  </div>
                  <div class="col-md-3">
                    <label for="filter_status" class="form-label small">Status</label>
                    <select id="filter_status" class="form-select">
                      <option value="all">Semua Status</option>
                      <option value="0">Menunggu Proses</option>
                      <option value="1">Terkirim</option>
                      <option value="2">Belum Diverifikasi</option>
                      <option value="3">Terverifikasi</option>
                      <option value="4">Ditolak</option>
                    </select>
                  </div>
                  <div class="col-md-2">
                    <button type="button" class="btn btn-primary w-100 btnFilterData">
                      <i class="bi bi-search me-1"></i>Tampilkan
                    </button>
                  </div>
                  <div class="col-md-1">
                    <button type="button" class="btn btn-outline-secondary w-100 btnResetFilter">
                      <i class="bi bi-arrow-clockwise"></i>
                    </button>
                  </div>
                </div>
              </div>
            </div>
          </div>
        </div>

        <table id="data-table" class="saytable border-top-bottom">
          <thead>
            <tr>
              <th show width="5%">No</th>
              <th show width="15%">No. Invoice</th>
              <th show width="20%">Pemesan</th>
              <th show width="12%">Detail Layanan</th>
              <th show width="14%">File Invoice</th>
              <th show width="14%">Bukti Bayar</th>
              <th show width="18%">Status</th>
              <th show width="10%" class="text-end">Aksi</th>
            </tr>
          </thead>
          <tbody id="table-body"></tbody>
        </table>
      </div>
    </div>
  </div>
</div>

<style>
  .page-pembayaran {
    margin-top: -23px;
  }

  .btn-action[disabled] {
    cursor: not-allowed;
    opacity: 0.5;
    pointer-events: none;
  }
</style>

<!-- Modal Upload Bukti Bayar (Admin) -->
<div class="modal fade" id="modalUploadBukti" data-bs-backdrop="static" data-bs-keyboard="false" tabindex="-1">
  <div class="modal-dialog modal-dialog-centered">
    <div class="modal-content">
      <div class="modal-header bg-primary text-white">
        <h5 class="modal-title">
          <i class="bi bi-upload"></i> Upload Bukti Bayar (Admin)
        </h5>
        <button type="button" class="btn-close btn-close-white" data-bs-dismiss="modal"></button>
      </div>
      <form id="formUploadBukti" enctype="multipart/form-data" method="post" onsubmit="return false;">
        <input type="hidden" name="<?= csrf_token() ?>" value="<?= csrf_hash() ?>" class="txt_csrfname">
        <input type="hidden" name="id" id="upload_bukti_id">

        <div class="modal-body">
          <!-- Tombol Lihat Bukti yang sudah ada -->
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

          <div class="alert alert-info">
            <i class="bi bi-info-circle"></i>
            Sebagai admin, Anda dapat mengupload bukti bayar untuk user yang tidak dapat mengupload sendiri.
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
</div>

<!-- Modal Tolak Verifikasi -->
<div class="modal fade" id="modalTolak" data-bs-backdrop="static" data-bs-keyboard="false" tabindex="-1">
  <div class="modal-dialog modal-dialog-centered">
    <div class="modal-content">
      <div class="modal-header bg-danger text-white">
        <h5 class="modal-title">
          <i class="bi bi-x-circle"></i> Tolak Verifikasi
        </h5>
        <button type="button" class="btn-close btn-close-white" data-bs-dismiss="modal"></button>
      </div>
      <form id="formTolak" method="post" onsubmit="return false;">
        <input type="hidden" name="<?= csrf_token() ?>" value="<?= csrf_hash() ?>" class="txt_csrfname">
        <input type="hidden" name="id" id="tolak_id">

        <div class="modal-body">
          <div class="mb-3">
            <label for="alasan" class="form-label">
              Alasan Penolakan <span class="text-danger">*</span>
            </label>
            <textarea class="form-control" id="alasan" name="alasan" rows="4" placeholder="Masukkan alasan penolakan..."
              required></textarea>
            <small class="text-muted">User akan melihat alasan ini</small>
          </div>

          <div class="alert alert-warning">
            <i class="bi bi-exclamation-triangle"></i> <strong>Perhatian:</strong>
            <br>Setelah ditolak, user dapat mengupload ulang bukti bayar yang benar.
          </div>
        </div>

        <div class="modal-footer">
          <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">
            <i class="bi bi-x-circle"></i> Batal
          </button>
          <button type="button" class="btn btn-danger" id="btnTolakSubmit" onclick="handleTolakVerifikasi()">
            <i class="bi bi-x-circle"></i> Tolak Verifikasi
          </button>
        </div>
      </form>
    </div>
  </div>
</div>

<!-- Modal Upload & Kirim Invoice (Gabungan) -->
<div class="modal fade" id="modalUploadKirimInvoice" data-bs-backdrop="static" data-bs-keyboard="false" tabindex="-1">
  <div class="modal-dialog modal-dialog-centered">
    <div class="modal-content">
      <div class="modal-header bg-info text-white">
        <h5 class="modal-title">
          <i class="bi bi-file-pdf"></i> Upload & Kirim Invoice
        </h5>
        <button type="button" class="btn-close btn-close-white" data-bs-dismiss="modal"></button>
      </div>
      <form id="formUploadKirimInvoice" enctype="multipart/form-data" method="post" onsubmit="return false;">
        <input type="hidden" name="<?= csrf_token() ?>" value="<?= csrf_hash() ?>" class="txt_csrfname">
        <input type="hidden" name="id" id="upload_kirim_invoice_id">

        <div class="modal-body">
          <!-- Tombol Lihat Invoice yang sudah ada -->
          <div class="mb-3">
            <button type="button" class="btn btn-sm btn-outline-info w-100" id="btnViewExistingInvoiceGabungan"
              disabled>
              <i class="bi bi-eye"></i> Lihat Invoice yang Sudah Ada
            </button>
          </div>

          <!-- Upload File Invoice -->
          <div class="mb-3">
            <label for="file_invoice_gabungan" class="form-label">
              Pilih File Invoice (PDF) <span class="text-danger">*</span>
            </label>
            <input type="file" class="form-control" id="file_invoice_gabungan" name="file_invoice" accept=".pdf"
              required>
            <div class="form-text" id="invoice-selection-gabungan">Format: PDF, Maksimal 5MB</div>
          </div>

          <!-- Nomor Invoice -->
          <div class="mb-3">
            <label for="no_invoice_gabungan" class="form-label">
              Nomor Invoice <span class="text-danger">*</span>
            </label>
            <input type="text" class="form-control" id="no_invoice_gabungan" name="no_invoice"
              placeholder="Contoh: INV/2025/001" required>
            <small class="text-muted">Masukkan nomor invoice yang unik</small>
          </div>

          <div class="alert alert-info">
            <i class="bi bi-info-circle"></i>
            Invoice akan dikirim langsung ke pelanggan setelah upload selesai.
          </div>
        </div>

        <div class="modal-footer">
          <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">
            <i class="bi bi-x-circle"></i> Batal
          </button>
          <button type="button" class="btn btn-info text-white" id="btnUploadKirimInvoice"
            onclick="handleUploadKirimInvoice()">
            <i class="bi bi-cloud-upload"></i> Upload & Kirim
          </button>
        </div>
      </form>
    </div>
  </div>
</div>

<!-- Modal Detail Layanan -->
<div class="modal fade" id="modalDetailLayananAdmin" data-bs-backdrop="static" data-bs-keyboard="false" tabindex="-1">
  <div class="modal-dialog modal-xl modal-dialog-scrollable">
    <div class="modal-content">
      <div class="modal-header bg-white text-black">
        <h5 class="modal-title"><i class="bi bi-card-list"></i> Detail Layanan</h5>
        <button type="button" class="btn-close btn-close-white" data-bs-dismiss="modal" aria-label="Close"></button>
      </div>
      <div class="modal-body">
        <div class="row g-3 mb-3">
          <div class="col-md-6">
            <small class="text-muted">Pemesan</small>
            <div class="fw-semibold" id="detailLayananPemesan">-</div>
          </div>
          <div class="col-md-6 text-md-end">
            <small class="text-muted">No. Invoice</small>
            <div class="fw-semibold" id="detailLayananInvoice">-</div>
          </div>
        </div>

        <div class="table-responsive">
          <table id="detailLayananTable" class="saytable border-top-bottom align-middle">
            <thead>
              <tr>
                <th width="5%">No</th>
                <th width="30%">Layanan</th>
                <th width="15%">Metode</th>
                <th width="12%">Diskon</th>
                <th width="15%">Biaya Satuan</th>
                <th width="8%">Jumlah</th>
                <th width="15%">Sub Total</th>
              </tr>
            </thead>
            <tbody></tbody>
            <tfoot>
              <tr class="table-active align-middle">
                <td colspan="7">
                  <div class="d-flex justify-content-end">
                    <div class="fw-bold fs-5">
                      TOTAL KESELURUHAN:
                      <span id="detailLayananTotal" class="text-primary">Rp 0</span>
                    </div>
                  </div>
                </td>
              </tr>
            </tfoot>
          </table>
        </div>
      </div>
      <div class="modal-footer">
        <button type="button" class="btn btn-light" data-bs-dismiss="modal">
          <i class="bi bi-x-circle"></i> Tutup
        </button>
      </div>
    </div>
  </div>
</div>

<script>
  // Inisialisasi tabel dengan sistem sayTable
  table = createTable({
    apiUrl: '<?php echo site_url("pembayaran_admin/dataList") ?>',
    dataSrc: 'items'
  });
  addAction();

  // Handle Filter Data
  document.querySelector('.btnFilterData').addEventListener('click', function () {
    const tanggalAwal = document.getElementById('tanggal_awal').value;
    const tanggalAkhir = document.getElementById('tanggal_akhir').value;
    const filterStatus = document.getElementById('filter_status').value;

    // Build base URL with filter parameters
    let baseUrl = '<?php echo site_url("pembayaran_admin/dataList") ?>';
    let params = [];

    if (tanggalAwal) {
      params.push('tanggal_awal=' + encodeURIComponent(tanggalAwal));
    }
    if (tanggalAkhir) {
      params.push('tanggal_akhir=' + encodeURIComponent(tanggalAkhir));
    }
    if (filterStatus && filterStatus !== 'all') {
      params.push('status=' + encodeURIComponent(filterStatus));
    }

    if (params.length > 0) {
      baseUrl += '?' + params.join('&');
    }

    // Update global config and clear cache
    config.apiUrl = baseUrl;
    allItems = [];
    allItemsSorted = [];
    isDataLoaded = false;

    // Force reload with new URL
    table.fetchData({
      page: 1,
      reload: true
    });
  });

  // Handle Reset Filter
  document.querySelector('.btnResetFilter').addEventListener('click', function () {
    document.getElementById('tanggal_awal').value = '';
    document.getElementById('tanggal_akhir').value = '';
    document.getElementById('filter_status').value = 'all';

    // Reset to original URL and clear cache
    config.apiUrl = '<?php echo site_url("pembayaran_admin/dataList") ?>';
    allItems = [];
    allItemsSorted = [];
    isDataLoaded = false;

    // Force reload
    table.fetchData({
      page: 1,
      reload: true
    });
  });

  /**
   * Upload Bukti (Admin)
   */
  function uploadBukti(event) {
    const id = event.target.closest('.dropdown').parentElement.id;
    document.getElementById('upload_bukti_id').value = id;

    const fileInput = document.getElementById('file_bukti');
    if (fileInput) fileInput.value = '';

    const selText = document.getElementById('bukti-selection');
    if (selText) selText.textContent = 'Format: PNG, JPG, PDF, dll. Maksimal 5MB';

    const btnElement = event.target.closest('.btn-action');
    const fileUrl = btnElement.getAttribute('data-fileurl') || '';

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

    const modalElement = document.getElementById('modalUploadBukti');
    const modal = new bootstrap.Modal(modalElement);
    modal.show();
  }

  // Handler untuk tombol Lihat Bukti
  document.addEventListener('click', function (ev) {
    const btn = ev.target.closest('#btnViewExistingBukti');
    if (!btn) return;

    const url = btn.getAttribute('data-url') || '';
    if (url && url !== '#' && url !== '') {
      window.open(url, '_blank');
    } else {
      sayAlert('errorModal', 'Info', 'Tidak ada file bukti.', 'warning');
    }
  });

  // Show filename when user selects a file
  (function () {
    const fi = document.getElementById('file_bukti');
    const sel = document.getElementById('bukti-selection');
    const viewBtn = document.getElementById('btnViewExistingBukti');

    if (!fi) return;

    fi.addEventListener('change', function (e) {
      const f = e.target.files && e.target.files[0];
      if (f) {
        sel.textContent = 'File dipilih: ' + f.name + ' (' + (f.size / 1024).toFixed(2) + ' KB)';
        if (viewBtn) viewBtn.setAttribute('disabled', 'disabled');
      } else {
        sel.textContent = 'Format: PNG, JPG, PDF, dll. Maksimal 5MB';
      }
    });
  })();

  /**
   * Handle Upload Bukti (Admin)
   */
  function handleUploadBukti() {
    const form = document.getElementById('formUploadBukti');
    if (!form.checkValidity()) {
      form.reportValidity();
      return;
    }

    const formData = new FormData(form);
    const btnUpload = document.getElementById('btnUploadBukti');

    // Ambil CSRF token terbaru dari hidden input
    const csrfInput = document.querySelector('input.txt_csrfname');
    const csrfName = csrfInput ? csrfInput.getAttribute('name') : '<?= csrf_token() ?>';
    const csrfHash = csrfInput ? csrfInput.value : '<?= csrf_hash() ?>';
    formData.set(csrfName, csrfHash);

    btnUpload.disabled = true;
    btnUpload.innerHTML = '<i class="bi bi-hourglass-split"></i> Uploading...';

    fetch('<?php echo site_url("pembayaran_admin/uploadBukti") ?>', {
      method: 'POST',
      body: formData
    })
      .then(response => response.json())
      .then(data => {
        // Update CSRF token untuk request berikutnya
        if (data.xname && data.xhash) {
          const allCsrfInputs = document.querySelectorAll('input.txt_csrfname');
          allCsrfInputs.forEach(input => {
            input.setAttribute('name', data.xname);
            input.value = data.xhash;
          });
        }

        btnUpload.disabled = false;
        btnUpload.innerHTML = '<i class="bi bi-cloud-upload"></i> Upload';

        if (data.res === 'success') {
          sayAlert('successModal', 'Berhasil', data.msg, 'success');

          const modalElement = document.getElementById('modalUploadBukti');
          const modal = bootstrap.Modal.getInstance(modalElement);
          if (modal) modal.hide();

          if (typeof table !== 'undefined') table.fetchData({
            reload: true
          });
        } else {
          sayAlert('errorModal', 'Gagal', data.msg, 'error');
        }
      })
      .catch(error => {
        btnUpload.disabled = false;
        btnUpload.innerHTML = '<i class="bi bi-cloud-upload"></i> Upload';
        sayAlert('errorModal', 'Error', 'Terjadi kesalahan: ' + error.message, 'error');
      });
  }

  /**
   * Terima Verifikasi
   */
  function terimaVerifikasi(event) {
    const btnElement = event.target.closest('.btn-action');
    if (btnElement.hasAttribute('disabled')) {
      sayAlert('warningModal', 'Perhatian', 'Tidak ada yang perlu diverifikasi', 'warning');
      return;
    }

    const id = event.target.closest('.dropdown').parentElement.id;

    // Ambil CSRF token terbaru dari hidden input
    const csrfInput = document.querySelector('input.txt_csrfname');
    const csrfName = csrfInput ? csrfInput.getAttribute('name') : '<?= csrf_token() ?>';
    const csrfHash = csrfInput ? csrfInput.value : '<?= csrf_hash() ?>';

    const formData = new FormData();
    formData.append('id', id);
    formData.append(csrfName, csrfHash);

    showLoading(); // Tampilkan loading overlay

    fetch('<?php echo site_url("pembayaran_admin/terimaVerifikasi") ?>', {
      method: 'POST',
      body: formData
    })
      .then(response => response.json())
      .then(data => {
        hideLoading(); // Sembunyikan loading overlay

        // Update CSRF token untuk request berikutnya
        if (data.xname && data.xhash) {
          const allCsrfInputs = document.querySelectorAll('input.txt_csrfname');
          allCsrfInputs.forEach(input => {
            input.setAttribute('name', data.xname);
            input.value = data.xhash;
          });
        }

        if (data.res === true) {
          sayAlert('successModal', 'Berhasil', data.msg, 'success');
          if (typeof table !== 'undefined') table.fetchData({
            reload: true
          });
        } else {
          sayAlert('errorModal', 'Gagal', data.msg, 'error');
        }
      })
      .catch(error => {
        hideLoading(); // Sembunyikan loading overlay
        sayAlert('errorModal', 'Error', 'Terjadi kesalahan: ' + error.message, 'error');
      });
  }

  /**
   * Tolak Verifikasi (buka modal)
   */
  function tolakVerifikasi(event) {
    const btnElement = event.target.closest('.btn-action');
    if (btnElement.hasAttribute('disabled')) {
      sayAlert('warningModal', 'Perhatian', 'Tidak bisa ditolak', 'warning');
      return;
    }

    const id = event.target.closest('.dropdown').parentElement.id;
    document.getElementById('tolak_id').value = id;

    const modalElement = document.getElementById('modalTolak');
    const modal = new bootstrap.Modal(modalElement);
    modal.show();
  }

  /**
   * Handle Tolak Verifikasi
   */
  function handleTolakVerifikasi() {
    const form = document.getElementById('formTolak');
    if (!form.checkValidity()) {
      form.reportValidity();
      return;
    }

    const alasan = document.getElementById('alasan').value.trim();
    if (!alasan) {
      sayAlert('warningModal', 'Perhatian', 'Alasan penolakan harus diisi!', 'warning');
      return;
    }

    const formData = new FormData(form);
    const btnTolak = document.getElementById('btnTolakSubmit');

    // Ambil CSRF token terbaru dari hidden input
    const csrfInput = document.querySelector('input.txt_csrfname');
    const csrfName = csrfInput ? csrfInput.getAttribute('name') : '<?= csrf_token() ?>';
    const csrfHash = csrfInput ? csrfInput.value : '<?= csrf_hash() ?>';
    formData.set(csrfName, csrfHash);

    btnTolak.disabled = true;
    btnTolak.innerHTML = '<i class="bi bi-hourglass-split"></i> Memproses...';

    showLoading(); // Tampilkan loading overlay

    fetch('<?php echo site_url("pembayaran_admin/tolakVerifikasi") ?>', {
      method: 'POST',
      body: formData
    })
      .then(response => response.json())
      .then(data => {
        hideLoading(); // Sembunyikan loading overlay

        // Update CSRF token untuk request berikutnya
        if (data.xname && data.xhash) {
          const allCsrfInputs = document.querySelectorAll('input.txt_csrfname');
          allCsrfInputs.forEach(input => {
            input.setAttribute('name', data.xname);
            input.value = data.xhash;
          });
        }

        btnTolak.disabled = false;
        btnTolak.innerHTML = '<i class="bi bi-x-circle"></i> Tolak Verifikasi';

        if (data.res === true) {
          sayAlert('successModal', 'Berhasil', data.msg, 'success');

          const modalElement = document.getElementById('modalTolak');
          const modal = bootstrap.Modal.getInstance(modalElement);
          if (modal) modal.hide();

          if (typeof table !== 'undefined') table.fetchData({
            reload: true
          });
        } else {
          sayAlert('errorModal', 'Gagal', data.msg, 'error');
        }
      })
      .catch(error => {
        hideLoading(); // Sembunyikan loading overlay
        btnTolak.disabled = false;
        btnTolak.innerHTML = '<i class="bi bi-x-circle"></i> Tolak Verifikasi';
        sayAlert('errorModal', 'Error', 'Terjadi kesalahan: ' + error.message, 'error');
      });
  }

  /**
   * Upload & Kirim Invoice (Gabungan)
   */
  function uploadKirimInvoice(event) {
    const id = event.target.closest('.dropdown').parentElement.id;
    document.getElementById('upload_kirim_invoice_id').value = id;

    const fileInput = document.getElementById('file_invoice_gabungan');
    const noInvoiceInput = document.getElementById('no_invoice_gabungan');

    if (fileInput) fileInput.value = '';
    if (noInvoiceInput) noInvoiceInput.value = '';

    const selText = document.getElementById('invoice-selection-gabungan');
    if (selText) selText.textContent = 'Format: PDF, Maksimal 5MB';

    const btnElement = event.target.closest('.btn-action');
    const invoiceUrl = btnElement.getAttribute('data-invoiceurl') || '';

    const viewBtn = document.getElementById('btnViewExistingInvoiceGabungan');
    if (viewBtn) {
      if (invoiceUrl && invoiceUrl !== '#' && invoiceUrl !== '') {
        viewBtn.removeAttribute('disabled');
        viewBtn.classList.remove('btn-outline-info');
        viewBtn.classList.add('btn-info');
        viewBtn.setAttribute('data-url', invoiceUrl);
      } else {
        viewBtn.setAttribute('disabled', 'disabled');
        viewBtn.classList.remove('btn-info');
        viewBtn.classList.add('btn-outline-info');
        viewBtn.removeAttribute('data-url');
      }
    }

    const modalElement = document.getElementById('modalUploadKirimInvoice');
    const modal = new bootstrap.Modal(modalElement);
    modal.show();
  }

  // Handler untuk tombol Lihat Invoice (Gabungan)
  document.addEventListener('click', function (ev) {
    const btn = ev.target.closest('#btnViewExistingInvoiceGabungan');
    if (!btn) return;

    const url = btn.getAttribute('data-url') || '';
    if (url && url !== '#' && url !== '') {
      window.open(url, '_blank');
    } else {
      sayAlert('errorModal', 'Info', 'Tidak ada file invoice.', 'warning');
    }
  });

  // Show filename when user selects invoice file (Gabungan)
  (function () {
    const fi = document.getElementById('file_invoice_gabungan');
    const sel = document.getElementById('invoice-selection-gabungan');
    const viewBtn = document.getElementById('btnViewExistingInvoiceGabungan');

    if (!fi) return;

    fi.addEventListener('change', function (e) {
      const f = e.target.files && e.target.files[0];
      if (f) {
        sel.textContent = 'File dipilih: ' + f.name + ' (' + (f.size / 1024).toFixed(2) + ' KB)';
        if (viewBtn) viewBtn.setAttribute('disabled', 'disabled');
      } else {
        sel.textContent = 'Format: PDF, Maksimal 5MB';
      }
    });
  })();

  /**
   * Handle Upload & Kirim Invoice (Gabungan)
   */
  function handleUploadKirimInvoice() {
    const form = document.getElementById('formUploadKirimInvoice');
    if (!form.checkValidity()) {
      form.reportValidity();
      return;
    }

    const noInvoice = document.getElementById('no_invoice_gabungan').value.trim();
    if (!noInvoice) {
      sayAlert('warningModal', 'Perhatian', 'Nomor invoice harus diisi!', 'warning');
      return;
    }

    const formData = new FormData(form);
    const btnUpload = document.getElementById('btnUploadKirimInvoice');

    // Ambil CSRF token terbaru dari hidden input
    const csrfInput = document.querySelector('input.txt_csrfname');
    const csrfName = csrfInput ? csrfInput.getAttribute('name') : '<?= csrf_token() ?>';
    const csrfHash = csrfInput ? csrfInput.value : '<?= csrf_hash() ?>';
    formData.set(csrfName, csrfHash);

    btnUpload.disabled = true;
    btnUpload.innerHTML = '<i class="bi bi-hourglass-split"></i> Memproses...';

    fetch('<?php echo site_url("pembayaran_admin/uploadKirimInvoice") ?>', {
      method: 'POST',
      body: formData
    })
      .then(response => response.json())
      .then(data => {
        // Update CSRF token untuk request berikutnya
        if (data.xname && data.xhash) {
          const allCsrfInputs = document.querySelectorAll('input.txt_csrfname');
          allCsrfInputs.forEach(input => {
            input.setAttribute('name', data.xname);
            input.value = data.xhash;
          });
        }

        btnUpload.disabled = false;
        btnUpload.innerHTML = '<i class="bi bi-cloud-upload"></i> Upload & Kirim';

        if (data.res === 'success' || data.res === true) {
          sayAlert('successModal', 'Berhasil', data.msg, 'success');

          const modalElement = document.getElementById('modalUploadKirimInvoice');
          const modal = bootstrap.Modal.getInstance(modalElement);
          if (modal) modal.hide();

          if (typeof table !== 'undefined') table.fetchData({
            reload: true
          });
        } else {
          sayAlert('errorModal', 'Gagal', data.msg, 'error');
        }
      })
      .catch(error => {
        btnUpload.disabled = false;
        btnUpload.innerHTML = '<i class="bi bi-cloud-upload"></i> Upload & Kirim';
        sayAlert('errorModal', 'Error', 'Terjadi kesalahan: ' + error.message, 'error');
      });
  }

  var detailModalTable = null;

  // Handler detail layanan - gunakan sayTable modal
  document.addEventListener('click', function (event) {
    const btn = event.target.closest('.btn-detail-layanan');
    if (!btn) return;

    const detailId = btn.getAttribute('data-detail-id');
    if (!detailId) {
      sayAlert('warningModal', 'Data Tidak Ditemukan', 'Detail layanan belum tersedia untuk baris ini.', 'warning');
      return;
    }

    const pemesan = btn.getAttribute('data-pemesan') || '-';
    const invoice = btn.getAttribute('data-invoice') || '-';
    const totalValue = parseFloat(btn.getAttribute('data-total') || '0');

    const pemesanEl = document.getElementById('detailLayananPemesan');
    const invoiceEl = document.getElementById('detailLayananInvoice');
    const totalEl = document.getElementById('detailLayananTotal');

    if (pemesanEl) pemesanEl.textContent = pemesan;
    if (invoiceEl) invoiceEl.textContent = invoice || '-';
    if (totalEl) totalEl.textContent = formatCurrencyIDR(totalValue);

    const targetUrl = '<?php echo site_url("pembayaran_admin/detailLayanan/") ?>' + detailId;

    if (!detailModalTable) {
      detailModalTable = createModal({
        tableId: 'detailLayananTable',
        apiUrl: targetUrl,
        showFilter: false,
        numbering: false,
        treeview: false,
        itemsPerPage: 10
      });
    } else {
      detailModalTable.refresh({
        apiUrl: targetUrl,
        currentPage: 1
      });
    }

    const modalElement = document.getElementById('modalDetailLayananAdmin');
    if (modalElement) {
      const modal = bootstrap.Modal.getOrCreateInstance(modalElement);
      modal.show();
    }
  });

  function formatCurrencyIDR(value) {
    const number = Number(value) || 0;
    if (typeof window.formatRupiahIntl === 'function') {
      return window.formatRupiahIntl(number);
    }

    return new Intl.NumberFormat('id-ID', {
      style: 'currency',
      currency: 'IDR',
      maximumFractionDigits: 0
    }).format(number);
  }
</script>