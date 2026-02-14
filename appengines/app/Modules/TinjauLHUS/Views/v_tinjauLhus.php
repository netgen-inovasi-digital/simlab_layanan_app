<?php // view: Modules/TinjauLHUS/Views/v_tinjauLhus.php ?>
<div class="row">
  <div class="col-md-12">
    <div class="card">
      <div class="card-header d-flex justify-content-between align-items-center">
        <label class="card-title mb-0"><?php echo $title ?></label>

        <!-- [ADDED] Filter Status -->
        <div class="d-flex align-items-center" style="gap:8px;">
          <label class="mb-0 small text-muted">Status:</label>
          <select id="statusFilter" class="form-select form-select-sm" style="width:280px;">
            <option value="">— Semua status —</option>
            <option value="tolak">LHUS ditolak</option>
            <option value="5">LHUS belum ditinjau</option>
            <option value="diproses">LHUS diproses kembali</option>
            <option value="6">LHUS disetujui</option>
          </select>
        </div>
        <!-- [ADDED] end -->
      </div>
      <div class="card-body">

        <!--  Hidden CSRF untuk Ajax -->
        <input type="hidden" name="<?= csrf_token() ?>" value="<?= csrf_hash() ?>">

        <table id="data-table" class="saytable border-top-bottom">
          <thead>
            <tr>
              <th show width="5%">No.</th>
              <th show width="40%">Pemesan</th>
              <th show width="25%">Status</th>
              <th show width="15%">LHUS</th>
            </tr>
          </thead>
          <tbody id="table-body"></tbody>
        </table>
      </div>
    </div>
  </div>
</div>

<!--  Modal Detail -->
<div class="modal fade" id="modalDetail" data-bs-backdrop="static" data-bs-keyboard="false" tabindex="-1">
  <div class="modal-dialog modal-xl modal-dialog-centered" role="document" style="max-width:1200px; margin: 1.5% auto;">
    <div class="modal-content">
      <div class="modal-header">
        <h5 class="modal-title">Detail Item Layanan</h5>
        <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
      </div>

      <div class="modal-body">
        <div class="table-responsive">
          <table id="tableDetail" class="table table-bordered align-middle">
            <thead>
              <tr>
                <th style="min-width:40px; width:5%;">No</th>
                <th style="min-width:300px; width:15%;">Layanan</th>
                <th style="min-width:120px; width:10%;">Metode</th>
                <th style="min-width:60px; width:5%;">Jumlah</th>
                <th style="min-width:120px; width:10%;">Status LHUS</th>
                <th style="min-width:120px; width:10%;">LHUS</th>
                <th style="min-width:250px; width:20%;">Keterangan LHUS</th>
                <th style="min-width:110px; width:5%;" class="text-center">Aksi</th>
              </tr>
            </thead>
            <tbody id="detail-body">
              <tr>
                <td colspan="8" class="text-center">Loading...</td>
              </tr>
            </tbody>
          </table>
        </div>

        <!-- Sample Identity Details Section -->
        <div class="detail-table mt-4" id="sampleIdentitySection" style="display: none;">
          <h6 class="mb-3">Identitas Sampel:</h6>
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
                  <div class="border rounded p-2"
                    style="max-height: 160px; overflow-y: auto; background-color: #f8f9fa;">
                    <p class="mb-0 text-wrap small" id="sampleDeskripsi">-</p>
                  </div>
                </div>
                <div class="col-12">
                  <label class="fw-bold text-muted small">Keterangan Khusus:</label>
                  <div class="border rounded p-2"
                    style="max-height: 160px; overflow-y: auto; background-color: #f8f9fa;">
                    <p class="mb-0 text-wrap small" id="sampleKeteranganKhusus">-</p>
                  </div>
                </div>
              </div>
            </div>
          </div>
        </div>
      </div>

      <div class="modal-footer">
        <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">
          <i class="bi bi-x-circle"></i> Tutup
        </button>
        <button type="button" class="btn btn-primary" id="btnSelesaiReview">
          <i class="bi bi-check-circle"></i> Selesai Ulasan
        </button>
      </div>
    </div>
  </div>
</div>

<!-- Modal Catatan Kaji Ulang -->
<div class="modal fade" id="modalCatatanKajiUlang" tabindex="-1" aria-labelledby="modalCatatanKajiUlangLabel"
  aria-hidden="true">
  <div class="modal-dialog modal-dialog-centered">
    <div class="modal-content">
      <div class="modal-header bg-danger text-white">
        <h5 class="modal-title" id="modalCatatanKajiUlangLabel">
          <i class="bi bi-exclamation-triangle-fill me-2"></i>Catatan Kaji Ulang
        </h5>
        <button type="button" class="btn-close btn-close-white" data-bs-dismiss="modal" aria-label="Close"></button>
      </div>
      <div class="modal-body">
        <div class="mb-3">
          <label class="form-label fw-bold text-muted small">Jumlah Kaji Ulang:</label>
          <p class="mb-0" id="jumlahKajiUlang">-</p>
        </div>
        <div>
          <label class="form-label fw-bold text-muted small">Catatan:</label>
          <div class="border rounded p-3" style="max-height: 300px; overflow-y: auto; background-color: #f8f9fa;">
            <p class="mb-0 text-wrap" id="catatanKajiUlangContent" style="white-space: pre-wrap;">-</p>
          </div>
        </div>
      </div>
      <div class="modal-footer">
        <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Tutup</button>
      </div>
    </div>
  </div>
</div>

<script>
  // Init table utama
  table = createTable({
    apiUrl: '<?php echo site_url("tinjaulhus/datalist") ?>',
    dataSrc: 'items'
  });
  addAction();

  // [ADDED] Helpers untuk filter & cache-buster
  if (typeof window.buildApiUrlWithOptionalParam !== 'function') {
    function buildApiUrlWithOptionalParam(path, key, value) {
      try {
        const u = new URL(path, window.location.origin);
        const params = new URLSearchParams(u.search);
        if (key && String(key) !== '') {
          if (typeof value !== 'undefined' && value !== null && String(value) !== '') {
            params.set(key, String(value));
          } else {
            params.delete(key);
          }
        }
        const s = params.toString();
        return u.pathname + (s ? '?' + s : '');
      } catch (e) {
        if (key && String(key) !== '' && value !== null && String(value) !== '') {
          return path + (path.includes('?') ? '&' : '?') + encodeURIComponent(key) + '=' + encodeURIComponent(String(value));
        }
        return path;
      }
    }
  }
  if (typeof window.normalizeDoubleQuestion !== 'function') {
    function normalizeDoubleQuestion(url) {
      if (typeof url !== 'string') return url;
      url = url.replace(/\?([^?]*)\?/, '?$1&');
      url = url.replace(/&{2,}/g, '&');
      url = url.replace(/\?&/, '?');
      if (url.endsWith('&')) url = url.slice(0, -1);
      return url;
    }
  }
  (function patchFetchData() {
    if (typeof table !== 'undefined' && table && typeof table.getConfig === 'function' && typeof table.fetchData === 'function' && !table.__fetchPatchedTL) {
      const _origFetch = table.fetchData.bind(table);
      var _currentAbort = null;
      table.fetchData = function (opts = {}) {
        try {
          const cfg = table.getConfig();
          if (cfg && typeof cfg.apiUrl === 'string') {
            const u = new URL(cfg.apiUrl, window.location.origin);
            u.searchParams.set('_ts', Date.now().toString());
            cfg.apiUrl = normalizeDoubleQuestion(u.pathname + (u.search ? u.search : ''));
          }
        } catch (err) { }
        try { if (_currentAbort) _currentAbort.abort(); } catch (e) { }
        try { _currentAbort = new AbortController(); opts.signal = _currentAbort.signal; } catch (e) { }
        return _origFetch(opts);
      };
      table.__fetchPatchedTL = true;
    }
  })();
  (function attachStatusFilter() {
    const sel = document.getElementById('statusFilter');
    if (!sel || sel.dataset.bound === '1') return;
    sel.addEventListener('change', function () {
      const val = (this.value || '').toString().trim();
      if (table?.getConfig) {
        const cfg = table.getConfig();
        cfg.apiUrl = normalizeDoubleQuestion(
          buildApiUrlWithOptionalParam('<?php echo site_url("tinjaulhus/datalist") ?>', 'status_layanan', (val === '' ? null : val))
        );
        table.fetchData({ reload: true, page: 1 });
      }
    });
    sel.dataset.bound = '1';
  })();
  // [ADDED] end

  // Utility ambil CSRF token
  function _getCsrf() {
    const csrfInput = document.querySelector('[name="<?= csrf_token() ?>"]');
    return csrfInput ? csrfInput.value : '';
  }

  // Proses LN parent (tetap tersedia bila diperlukan dari kode lain)
  function prosesLhus(id, aksi) {
    if (!id || !aksi) return;

    const csrfInput = document.querySelector('[name="<?= csrf_token() ?>"]');
    const csrfName = csrfInput ? csrfInput.getAttribute("name") : "";
    const csrfToken = csrfInput ? csrfInput.value : "";

    var formData = new FormData();
    if (csrfName) formData.append(csrfName, csrfToken);

    fetch('<?php echo site_url("tinjaulhus/proses/") ?>' + id + '/' + aksi, {
      method: 'POST',
      headers: { 'X-Requested-With': 'XMLHttpRequest' },
      body: formData
    })
      .then(res => res.json())
      .then(data => {
        if (data.success) {
          if (typeof table !== 'undefined') table.fetchData({ reload: true });
        } else {
          sayAlert('errorModal', 'Gagal', data.msg || 'Proses LHUS gagal dilakukan', 'warning');
        }
        if (data.xname && data.xhash) {
          document.querySelectorAll('[name="' + data.xname + '"]').forEach(input => input.value = data.xhash);
        }
      })
      .catch(err => {
        console.error(err);
        sayAlert('errorModal', 'Error', 'Terjadi kesalahan sistem', 'warning');
      });
  }

  // Cache untuk data identitas sampel
  var cachedSampleData = {};

  function loadSampleIdentity(kode_layanan) {
    const sampleSection = document.getElementById('sampleIdentitySection');

    if (!kode_layanan) {
      if (sampleSection) sampleSection.style.display = 'none';
      return;
    }

    // Cek apakah data sudah di-cache
    if (cachedSampleData[kode_layanan]) {
      // Gunakan data dari cache
      const data = cachedSampleData[kode_layanan];
      document.getElementById('sampleJenis').textContent = data.jenis || '-';
      document.getElementById('sampleKemasan').textContent = data.kemasan || '-';
      document.getElementById('sampleSifat').textContent = data.sifat || '-';
      document.getElementById('sampleSisa').textContent = data.sisa || '-';
      document.getElementById('sampleDeskripsi').textContent = data.deskripsi || '-';
      document.getElementById('sampleKeteranganKhusus').textContent = data.keterangan_khusus || '-';
      sampleSection.style.display = 'block';
    } else {
      // Fetch data baru dari server
      fetch(`<?php echo site_url("tinjaulhus/getSampleIdentity/") ?>${kode_layanan}`)
        .then(response => response.json())
        .then(data => {
          if (data.success && data.data) {
            // Simpan ke cache
            cachedSampleData[kode_layanan] = data.data;

            // Populate sample identity fields
            document.getElementById('sampleJenis').textContent = data.data.jenis || '-';
            document.getElementById('sampleKemasan').textContent = data.data.kemasan || '-';
            document.getElementById('sampleSifat').textContent = data.data.sifat || '-';
            document.getElementById('sampleSisa').textContent = data.data.sisa || '-';
            document.getElementById('sampleDeskripsi').textContent = data.data.deskripsi || '-';
            document.getElementById('sampleKeteranganKhusus').textContent = data.data.keterangan_khusus || '-';
            sampleSection.style.display = 'block';
          } else {
            // Jika tidak ada data, sembunyikan section
            sampleSection.style.display = 'none';
          }
        })
        .catch(error => {
          console.error('Error loading sample identity:', error);
          sampleSection.style.display = 'none';
        });
    }
  }

  // Load detail LN -> tampilkan modal
  var trackingDetailTable = null;
  var pendingChanges = {};
  function loadDetail(id) {
    pendingChanges = {};
    const modalEl = document.getElementById('modalDetail');
    if (modalEl) {
      modalEl.dataset.encLn = id;
    }

    // Clear existing table body and pagination before creating new modal table
    const tableDetail = document.getElementById('tableDetail');
    if (tableDetail) {
      const tbody = tableDetail.querySelector('tbody');
      if (tbody) tbody.innerHTML = '';
    }

    // Remove existing filter and pagination for tableDetail
    const existingFilter = document.getElementById('filter-container-tableDetail');
    if (existingFilter) existingFilter.remove();
    const existingPagination = document.getElementById('pagination-tableDetail');
    if (existingPagination) existingPagination.remove();

    // Selalu buat ulang modal table dengan URL baru (untuk handle reload)
    trackingDetailTable = createModal({
      tableId: 'tableDetail',
      apiUrl: `<?php echo site_url("tinjaulhus/detaillist/") ?>${id}`,
      itemsPerPage: 10,
      showFilter: false,
      treeview: false,
      numbering: false,
      dataSrc: 'items',
      sortable: false
    });

    // Load identitas sampel dan data tambahan setelah modal dibuat
    setTimeout(async function () {
      try {
        const response = await fetch(`<?php echo site_url("tinjaulhus/detaillist/") ?>${id}`);
        const data = await response.json();

        // Load identitas sampel
        if (data.kode_layanan) {
          loadSampleIdentity(data.kode_layanan);
        }

        // Update encLn dari response
        if (data.encLn && modalEl) {
          modalEl.dataset.encLn = data.encLn;
        }
      } catch (error) {
        console.error('Error loading additional data:', error);
      }
    }, 300);

    // Show modal
    try {
      if (typeof bootstrap !== 'undefined') {
        var modalInstance = bootstrap.Modal.getInstance(modalEl);
        if (!modalInstance) modalInstance = new bootstrap.Modal(modalEl);
        if (!modalEl.classList.contains('show')) modalInstance.show();
      } else if (typeof $ === 'function') {
        if (!$('#modalDetail').hasClass('show')) $('#modalDetail').modal('show');
      }
    } catch (err) {
      console.warn('Modal show error', err);
    }
  }

  // ============================================================
  // LOGIKA REVIEW LHUS: LOCAL STATE + BATCH SAVE
  // ============================================================

  // Handler: Terima / Tolak per item (toggle lokal, belum simpan ke DB)
  document.addEventListener('click', function (e) {
    const btnAccept = e.target.closest('.btn-accept-lhus');
    const btnReject = e.target.closest('.btn-reject-lhus');
    if (!btnAccept && !btnReject) return;
    e.preventDefault();

    const isAccept = !!btnAccept;
    const el = isAccept ? btnAccept : btnReject;
    const det = el.dataset.det;
    if (!det) return;

    const row = el.closest('tr');
    if (!row) return;

    // Update local pending state
    pendingChanges[det] = isAccept ? 'terima' : 'tolak';

    // Visual feedback: update status badge
    const statusCell = row.cells[4];
    const actionDiv = el.closest('.d-flex');
    const acceptBtn = actionDiv.querySelector('.btn-accept-lhus');
    const rejectBtn = actionDiv.querySelector('.btn-reject-lhus');

    if (isAccept) {
      statusCell.innerHTML = '<span class="badge bg-success">Diterima</span>';
      acceptBtn.style.opacity = '0.5';
      acceptBtn.style.pointerEvents = 'none';
      rejectBtn.style.opacity = '1';
      rejectBtn.style.pointerEvents = 'auto';
    } else {
      statusCell.innerHTML = '<span class="badge bg-danger">Ditolak</span>';
      rejectBtn.style.opacity = '0.5';
      rejectBtn.style.pointerEvents = 'none';
      acceptBtn.style.opacity = '1';
      acceptBtn.style.pointerEvents = 'auto';
    }
  });

  // Handler: Tombol Selesai — batch submit semua perubahan ke server
  document.getElementById('btnSelesaiReview').addEventListener('click', function () {
    const modalEl = document.getElementById('modalDetail');
    const encLn = modalEl ? modalEl.dataset.encLn : null;

    if (!encLn) {
      sayAlert('errorModal', 'Error', 'Data layanan tidak valid.', 'error');
      return;
    }

    // Collect semua perubahan
    const items = [];
    for (const [det, aksi] of Object.entries(pendingChanges)) {
      const textarea = document.getElementById('detketlhus_' + det);
      items.push({
        detKode: parseInt(det, 10),
        aksi: aksi,
        ket: textarea ? textarea.value.trim() : ''
      });
    }

    if (items.length === 0) {
      sayAlert('errorModal', 'Info', 'Belum ada perubahan. Silakan terima atau tolak item LHUS terlebih dahulu.', 'warning');
      return;
    }

    showLoading();

    fetch('<?= site_url("tinjaulhus/submitreview") ?>', {
      method: 'POST',
      headers: {
        'Content-Type': 'application/json',
        'X-CSRF-TOKEN': _getCsrf(),
        'X-Requested-With': 'XMLHttpRequest'
      },
      body: JSON.stringify({ encLn: encLn, items: items })
    })
      .then(res => res.json())
      .then(data => {
        if (data.xname && data.xhash) {
          document.querySelectorAll('[name="' + data.xname + '"]').forEach(input => input.value = data.xhash);
        }
        if (data.res) {
          pendingChanges = {};
          sayAlert('successModal', 'Berhasil', data.msg || 'Review berhasil disimpan', 'success');

          // Tutup modal
          try {
            const modalEl = document.getElementById('modalDetail');
            if (typeof bootstrap !== 'undefined') {
              const modalInstance = bootstrap.Modal.getInstance(modalEl);
              if (modalInstance) modalInstance.hide();
            } else if (typeof $ === 'function') {
              $('#modalDetail').modal('hide');
            }
          } catch (err) {
            console.warn('Modal close error', err);
          }

          if (typeof table !== 'undefined') table.fetchData({ reload: true });
        } else {
          sayAlert('errorModal', 'Gagal', data.msg || 'Gagal menyimpan review', 'error');
        }
      })
      .catch(err => {
        console.error(err);
        sayAlert('errorModal', 'Error', 'Terjadi kesalahan sistem', 'error');
      })
      .finally(() => { hideLoading(); });
  });

  // Refresh table utama saat modal ditutup
  (function attachModalCloseRefresh() {
    const modalEl = document.getElementById('modalDetail');
    if (!modalEl) return;
    if (typeof bootstrap !== 'undefined') {
      modalEl.addEventListener('hidden.bs.modal', function () {
        pendingChanges = {};
        try { if (typeof table !== 'undefined') table.fetchData({ reload: true }); } catch (e) { }
      });
    }
  })();

  // ============================================================
  // EVENT DELEGATION UNTUK BADGE UJI ULANG
  // ============================================================
  document.addEventListener('click', function (e) {
    // BADGE UJI ULANG - Klik untuk melihat catatan kaji ulang
    const badgeUjiUlang = e.target.closest ? e.target.closest('.badge-uji-ulang') : null;
    if (badgeUjiUlang) {
      e.preventDefault();
      e.stopPropagation();
      const encId = badgeUjiUlang.dataset.id;
      if (encId) {
        showCatatanKajiUlang(encId);
      }
      return;
    }
  });

  // ============================================================
  // FUNGSI UNTUK MENAMPILKAN MODAL CATATAN KAJI ULANG
  // ============================================================
  function showCatatanKajiUlang(encId) {
    const url = '<?php echo site_url("tinjaulhus/getCatatanKajiUlang/") ?>' + encId;

    // Set loading state
    document.getElementById('jumlahKajiUlang').textContent = 'Memuat...';
    document.getElementById('catatanKajiUlangContent').textContent = 'Memuat...';

    // Show modal
    const modal = new bootstrap.Modal(document.getElementById('modalCatatanKajiUlang'));
    modal.show();

    // Fetch data
    fetch(url)
      .then(response => response.json())
      .then(data => {
        if (data.success) {
          document.getElementById('jumlahKajiUlang').textContent = data.data.jumlah_kaji_ulang + ' kali';
          document.getElementById('catatanKajiUlangContent').textContent = data.data.catatan_kaji_ulang || '-';
        } else {
          document.getElementById('jumlahKajiUlang').textContent = '-';
          document.getElementById('catatanKajiUlangContent').textContent = data.message || 'Gagal memuat data';
        }
      })
      .catch(error => {
        console.error('Error:', error);
        document.getElementById('jumlahKajiUlang').textContent = '-';
        document.getElementById('catatanKajiUlangContent').textContent = 'Terjadi kesalahan saat memuat data';
      });
  }
</script>