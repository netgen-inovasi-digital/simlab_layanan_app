<!-- Include Modal Keranjang -->
<?php echo view('Modules\KeranjangAdmin\Views\v_keranjang', ['categories' => $categories ?? [], 'users' => $users ?? []]); ?>

<style>
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
</style>

<!-- modal tabel utama -->
<div class="row">
  <div class="col-md-12">
    <div class="card">

      <div class="card-header d-flex justify-content-between align-items-center">
        <!-- Tetap kiri: Judul -->
        <div class="d-flex align-items-center" style="gap:12px;">
          <label class="card-title mb-0"><?= $title ?></label>
        </div>

        <!-- Kanan: Filter + Tombol, urutan sesuai request -->
        <div class="d-flex align-items-center" style="gap:10px;">
          <select id="statusFilter" class="form-select form-select-sm" style="width:180px;">
            <option value="all">Semua Kategori</option>
            <option value="1">In Review Manajer</option>
            <option value="3">Belum direview</option>
            <option value="4">Dalam pengujian</option>
            <option value="5">LHUS diproses</option>
            <option value="6">LHUS disetujui</option>
            <option value="7">LHU proses</option>
          </select>

          <button id="add" class="btn btn-primary">
            <i class="bi bi-plus-circle-dotted"></i> Pesan
          </button>
        </div>
      </div>

      <div class="card-body">
        <table id="data-table" class="saytable border-top-bottom">
          <thead>
            <tr>
              <th show width="5%">No.</th>
              <th show width="30%">Pemesan</th>
              <th show width="20%">Status</th>
              <th show width="15%">Status Pembayaran</th>
              <th show width="15%">Detail Layanan</th>
              <th show width="15%" class="action text-end">Aksi<i class="bi bi-code sort-icon"></i></th>
            </tr>
          </thead>
          <tbody id="table-body"></tbody>
        </table>
      </div>
    </div>
  </div>
</div>

<!-- Modal detail Pesanan -->
<div class="modal fade" id="modalDetail" data-bs-backdrop="static" data-bs-keyboard="false" tabindex="-1"
  aria-labelledby="staticBackdropLabel" aria-hidden="true">
  <div class="modal-dialog modal-xl modal-dialog-scrollable" role="document" style="margin: 2% auto">
    <div class="modal-content">
      <div class="modal-header">
        <h5 class="modal-title"><i class="bi bi-card-list"></i> Detail Layanan</h5>
        <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close">
        </button>
      </div>
      <div class="modal-body">
        <table id="detailLayananTable" class="saytable border-top-bottom">
          <thead>
            <tr>
              <th width="5%">No</th>
              <th width="25%">Layanan</th>
              <th width="20%">Metode</th>
              <th width="10%">Biaya</th>
              <th width="8%">Jumlah</th>
              <th width="10%">Status</th>
              <th width="12%">Keterangan Manajer</th>
              <th width="10%">Disetujui oleh</th>
            </tr>
          </thead>
          <tbody></tbody>
        </table>

        <!-- Sample Identity Details Section -->
        <div class="detail-table mt-4" id="sampleIdentitySection">
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
      </div>
      <div class="modal-footer">
        <button class="btn btn-light" type="button" data-bs-dismiss="modal">
          <i class="bi bi-x-circle"></i> Tutup
        </button>
      </div>
    </div>
  </div>
</div>



<script>
  //   Utility: buildApiUrlWithOptionalParam & normalizeDoubleQuestion
  //   - mencegah pembentukan URL seperti "...?A?page=1"
  function buildApiUrlWithOptionalParam(path, key, value) {
    try {
      const u = new URL(path, window.location.origin);
      const params = new URLSearchParams(u.search);

      if (typeof key === 'string' && key !== '' && typeof value !== 'undefined' && value !== null && String(value) !== '') {
        params.set(key, String(value));
      } else {
        if (typeof key === 'string' && key !== '') params.delete(key);
      }

      const s = params.toString();
      // gunakan pathname agar sesuai helper createTable yang biasanya memakai path relatif
      return u.pathname + (s ? '?' + s : '');
    } catch (e) {
      if (typeof key === 'string' && key !== '' && typeof value !== 'undefined' && value !== null && String(value) !== '') {
        return path + '?' + encodeURIComponent(key) + '=' + encodeURIComponent(String(value));
      }
      return path;
    }
  }

  function normalizeDoubleQuestion(url) {
    if (typeof url !== 'string') return url;
    // Replace occurrences of '?...?' to '?...&' and collapse duplicate &.
    // First, replace any '?...?' pattern
    url = url.replace(/\?([^?]*)\?/, '?$1&');
    // remove duplicate ampersands
    url = url.replace(/&{2,}/g, '&');
    // fix any trailing & or ? if needed
    url = url.replace(/\?&/, '?');
    if (url.endsWith('&')) url = url.slice(0, -1);
    return url;
  }

  // ambil parameter URL
  var urlParams = new URLSearchParams(window.location.search);
  var kategoriFromUrl = urlParams.get('kategoriLayanan');

  // Base API URL (normalisasi)
  var baseApiUrl = '<?php echo site_url("formuliradmin/datalist") ?>';
  if (kategoriFromUrl) {
    baseApiUrl = buildApiUrlWithOptionalParam(baseApiUrl, 'kategoriLayanan', kategoriFromUrl);
    baseApiUrl = normalizeDoubleQuestion(baseApiUrl);
  }

  // Inisialisasi table (fungsi createTable diasumsikan sudah ada di project)
  table = createTable({
    apiUrl: baseApiUrl,
    dataSrc: 'items'
  });

  // Patch table.fetchData untuk menormalisasi apiUrl bila helper createTable menambahkan '?ganda'
  if (table && typeof table.getConfig === 'function' && typeof table.fetchData === 'function') {
    const origFetch = table.fetchData.bind(table);
    table.fetchData = function (opts = {}) {
      try {
        const cfg = table.getConfig();
        if (cfg && cfg.apiUrl && typeof cfg.apiUrl === 'string') {
          cfg.apiUrl = normalizeDoubleQuestion(cfg.apiUrl);
        }
      } catch (err) {
        console.warn('Normalization (table) failed:', err);
      }
      return origFetch(opts);
    };
  }

  // Set dropdown sesuai parameter URL
  if (kategoriFromUrl) {
    var sf = document.getElementById('statusFilter');
    if (sf) sf.value = kategoriFromUrl;
  }

  // panggil fungsi tambahan (asumsi addAction tersedia)
  if (typeof addAction === 'function') addAction();

  // Event listener untuk filter dropdown
  var statusFilterEl = document.getElementById('statusFilter');
  if (statusFilterEl) {
    statusFilterEl.addEventListener('change', function () {
      const selectedValue = this.value;
      const tableConfig = table.getConfig();

      if (selectedValue === 'all') {
        tableConfig.apiUrl = '<?php echo site_url("formuliradmin/datalist") ?>';
      } else {
        tableConfig.apiUrl = buildApiUrlWithOptionalParam('<?php echo site_url("formuliradmin/datalist") ?>', 'kategoriLayanan', selectedValue);
      }
      tableConfig.apiUrl = normalizeDoubleQuestion(tableConfig.apiUrl);

      tableConfig.currentPage = 1;
      table.fetchData({
        page: 1,
        reload: true
      });
    });
  }

  // ======= saveData tetap tersedia (dipakai oleh fitur lain) =======
  function saveData({
    url,
    formData,
    onSuccess,
    onError
  }) {
    showLoading();

    const csrfInput = document.querySelector('[name="<?= csrf_token() ?>"]');
    const csrfToken = csrfInput ? csrfInput.value : '';

    fetch(url, {
      method: 'POST',
      body: formData,
      headers: {
        'X-CSRF-TOKEN': csrfToken
      }
    })
      .then(response => response.json())
      .then(data => {
        if (data.xname && data.xhash) {
          document.querySelectorAll('[name="' + data.xname + '"]').forEach(input => {
            input.value = data.xhash;
          });
        }

        if (typeof onSuccess === 'function') {
          onSuccess(data);
          return;
        }

        // Default handling untuk responses lainnya
        if (data.res === true) {
          if (typeof table !== 'undefined') table.fetchData({
            reload: true
          });
          sayAlert('successModal', 'Success', 'Data berhasil disimpan.', 'success');
        } else if (data.res === 'reload') {
          sayAlert('successModal', 'Success', 'Data berhasil disimpan.', 'success');
        } else if (data.res === 'refresh') {
          loadContent(data.link);
          sayAlert('successModal', 'Success', 'Data berhasil disimpan.', 'success');
        } else if (data.res === 'redirect') {
          window.location.href = data.link;
        } else if (data.res === 'check') {
          sayAlert('errorModal', 'Error', data.link, 'warning');
        } else if (data.res === 'refresh-print') {
          loadContent(data.link);
          window.open(data.print, "_blank");
        } else {
          sayAlert('errorModal', 'Error', 'Data gagal disimpan.', 'warning');
        }
      })
      .catch(error => {
        if (typeof onError === 'function') {
          onError(error);
        } else {
          sayAlert('errorModal', 'Error', 'Terjadi kesalahan pada sistem.', 'warning');
        }
      })
      .finally(() => {
        hideLoading();
      });
  }

  function confirmApprove(e) {
    try {
      if (e && typeof e.preventDefault === 'function') e.preventDefault();
      // dapatkan id dari elemen pembungkus (div#<id>)
      const el = (e && e.currentTarget) ? e.currentTarget : (e && e.target) ? e.target : null;
      let id = null;
      if (el && typeof el.closest === 'function') {
        const div = el.closest('div[id]');
        if (div) id = div.id;
      }
      // fallback: cari terdekat dengan attribute id pada parent
      if (!id && e && e.target) {
        const maybe = e.target.closest && e.target.closest('div') ? e.target.closest('div').id : null;
        if (maybe) id = maybe;
      }
      if (!id) return;

      // gunakan sayConfirm jika tersedia (konsisten dengan UI)
      const doApprove = function () {
        const csrf = getCsrfTokenFromPage();
        const headers = {
          'X-Requested-With': 'XMLHttpRequest'
        };

        // set header menggunakan nama token dinamis jika tersedia
        if (csrf && csrf.name && csrf.value) {
          headers[csrf.name] = csrf.value;
        }

        // jika server mengharuskan token di body, gunakan FormData
        const body = new FormData();
        // tambahkan token juga ke body agar kompatibel
        if (csrf && csrf.name && csrf.value) body.append(csrf.name, csrf.value);

        fetch('<?= site_url("formuliradmin/approve/") ?>' + id, {
          method: 'POST',
          headers: headers,
          body: body
        })

          .then(res => res.json())
          .then(data => {
            if (data.res) {
              if (typeof table !== 'undefined') table.fetchData({
                reload: true
              });
              sayAlert('successModal', 'Berhasil', 'Data berhasil diapprove', 'success');
            } else {
              sayAlert('errorModal', 'Gagal', data.msg || 'Approve gagal dilakukan', 'warning');
            }

            if (data.xname && data.xhash) {
              document.querySelectorAll('[name="' + data.xname + '"]').forEach(input => input.value = data.xhash);
            }
          })
          .catch(err => sayAlert('errorModal', 'Error', 'Terjadi kesalahan sistem', 'warning'));
      };

      if (typeof sayConfirm === 'function') {
        sayConfirm('Konfirmasi ', 'Setujui transaksi ini?', function () {
          doApprove();
        }, 'primary', 'Setujui');

      } else {
        // fallback ke native confirm jika sayConfirm tidak tersedia
        if (confirm('Yakin ingin approve data ini?')) {
          doApprove();
        }
      }
    } catch (err) {
      console.error('confirmApprove error', err);
    }
  }

  /**
   * Ambil CSRF token dari input hidden di halaman (nama token dinamis)
   * Return { name: string, value: string } or null
   */
  function getCsrfTokenFromPage() {
    try {
      // Cari input hidden yang berisi token (nama dinamis)
      const inputs = document.querySelectorAll('input[type="hidden"]');
      for (let i = 0; i < inputs.length; i++) {
        const inp = inputs[i];
        // heuristik: nama token di CI biasanya panjang & acak, tapi value mengandung hash -> gunakan token config
        // Kita cek apakah nama input bukan "_method" dan value panjang > 8
        if (!inp.name) continue;
        if (inp.name.toLowerCase() === '_method') continue;
        if ((inp.value || '').toString().length > 8) {
          return {
            name: inp.name,
            value: inp.value
          };
        }
      }
    } catch (err) {
      console.warn('getCsrfTokenFromPage error', err);
    }
    return null;
  }

  // ============================================================
  // DETAIL LAYANAN MODAL - Menggunakan createModal dari sayTable.js
  // ============================================================
  var detailModalTable = null;
  var cachedSampleData = {}; // Cache untuk identitas sampel

  // Helper: Reset tabel detail sepenuhnya
  function resetDetailTable() {
    const paging = document.getElementById('pagination-detailLayananTable');
    if (paging) paging.remove();

    const filter = document.getElementById('filter-container-detailLayananTable');
    if (filter) filter.remove();

    const tbody = document.querySelector('#detailLayananTable tbody');
    if (tbody) tbody.innerHTML = '';

    detailModalTable = null;
  }

  // Event listener untuk modal hidden (reset state)
  document.addEventListener('hidden.bs.modal', function (e) {
    if (e.target.id === 'modalDetail') {
      resetDetailTable();
    }
  });

  function loadDetail(id, kode_layanan) {
    // Reset identitas sampel terlebih dahulu
    document.getElementById('sampleJenis').textContent = '-';
    document.getElementById('sampleKemasan').textContent = '-';
    document.getElementById('sampleSifat').textContent = '-';
    document.getElementById('sampleSisa').textContent = '-';
    document.getElementById('sampleDeskripsi').textContent = '-';
    document.getElementById('sampleKeteranganKhusus').textContent = '-';

    // Hapus pagination sebelum load data baru
    const paging = document.getElementById('pagination-detailLayananTable');
    if (paging) paging.remove();

    // Kosongkan tbody untuk mencegah data lama terlihat
    const tbody = document.querySelector('#detailLayananTable tbody');
    if (tbody) tbody.innerHTML = '';

    const targetUrl = '<?php echo site_url("formuliradmin/detaillist/") ?>' + id;

    // Gunakan createModal dari sayTable.js
    if (!detailModalTable) {
      detailModalTable = createModal({
        tableId: 'detailLayananTable',
        apiUrl: targetUrl,
        showFilter: false,
        numbering: false,
        treeview: false,
        itemsPerPage: 10,
        dataSrc: 'items'
      });
    } else {
      detailModalTable.refresh({
        apiUrl: targetUrl,
        currentPage: 1
      });
    }

    // Fetch identitas sampel secara terpisah
    fetch(targetUrl)
      .then(response => response.json())
      .then(data => {
        // Populate identitas sampel
        if (data && data.sampleData) {
          document.getElementById('sampleJenis').textContent = data.sampleData.jenis || '-';
          document.getElementById('sampleKemasan').textContent = data.sampleData.kemasan || '-';
          document.getElementById('sampleSifat').textContent = data.sampleData.sifat || '-';
          document.getElementById('sampleSisa').textContent = data.sampleData.sisa || '-';
          document.getElementById('sampleDeskripsi').textContent = data.sampleData.deskripsi || '-';
          document.getElementById('sampleKeteranganKhusus').textContent = data.sampleData.keterangan_khusus || '-';
        }
      })
      .catch(err => {
        console.warn('Gagal memuat identitas sampel:', err);
      });

    // Tampilkan modal
    const modalElement = document.getElementById('modalDetail');
    if (modalElement) {
      const modal = bootstrap.Modal.getOrCreateInstance(modalElement);
      modal.show();
    }
  }

  // Fungsi untuk pembayaran (sama seperti di Pelayanan)
  function lokasiPembayaran(kode_layanan) {
    window.location.href = '<?= site_url('pembayaran?kode=') ?>' + kode_layanan;
  }

  // ======= Pastiin #add membuka modalForm (dari KeranjangAdmin) =======
  (function () {
    var addBtn = document.querySelector('#add');
    if (!addBtn) return;

    addBtn.addEventListener('click', function () {
      try {
        var modalEl = document.getElementById('modalForm');
        if (!modalEl) {
          console.warn('modalForm tidak ditemukan di DOM.');
          return;
        }
        if (typeof bootstrap !== 'undefined') {
          var modalInstance = new bootstrap.Modal(modalEl);
          modalInstance.show();
        } else if (typeof $ !== 'undefined' && typeof $('#modalForm').modal === 'function') {
          $('#modalForm').modal('show');
        } else {
          console.warn('Bootstrap modal tidak tersedia — pastikan Bootstrap JS dimuat.');
        }
      } catch (err) {
        console.error('Gagal membuka modalForm:', err);
      }
    });
  })();
</script>