<!-- Include Modal Tracking Rapat JAS -->
<?php require_once(__DIR__ . '/v_track_modal_rapat_jas.php'); ?>

<!-- Include Modal Keranjang Rapat JAS -->
<?php echo view('Modules\Keranjang\Views\v_keranjang_rapat_jas', ['categories' => $categories ?? []]); ?>

<!-- modal tabel utama -->
<div class="row">
  <div class="col-md-12">
    <div class="card">
      <div class="card-header d-flex justify-content-between align-items-center">
        <label class="card-title mb-0"><?php echo $title ?></label>
        <button id="add" class="btn btn-primary">
          <i class="bi bi-plus-circle-dotted"></i> Pesan Layanan Baru
        </button>
      </div>
      <div class="card-body">
        <table id="data-table" class="saytable border-top-bottom">
          <thead>
            <tr>
              <th show width="5%">No.</th>
              <th show width="20%">No. transaksi</th>
              <th show width="25%">Status & Detail Pesanan</th>
              <th show width="20%">Status Pembayaran</th>
              <!-- <th show >Detail pesanan</th> -->
            </tr>
          </thead>
          <tbody id="table-body">
          </tbody>
        </table>
      </div>
    </div>
  </div>
</div>

<script>
    /**
     * buildApiUrlWithOptionalParam
     * - path: path ke endpoint, mis. '<?= site_url("pelayanan/datalist") ?>'
    * - key / value: jika diberikan, tambahkan sebagai query string(tanpa page / limit)
      *
     * Output: path atau path + '?key=value'
    */
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
      // gunakan pathname (tanpa origin) agar konsisten dengan helper createTable yang mungkin
      return u.pathname + (s ? '?' + s : '');
    } catch (e) {
      if (typeof key === 'string' && key !== '' && typeof value !== 'undefined' && value !== null && String(value) !== '') {
        return path + '?' + encodeURIComponent(key) + '=' + encodeURIComponent(String(value));
      }
      return path;
    }
  }

  /**
   * normalizeDoubleQuestion
   * - ubah pola like "...?a=b?c=d" menjadi "...?a=b&c=d"
   * - collapse accidental "&&"
   */
  function normalizeDoubleQuestion(url) {
    if (typeof url !== 'string') return url;
    // ubah pertama kali "?...?" -> "?...&"
    let n = url.replace(/\?([^?]*)\?/, '?$1&');
    // collapse duplicate ampersand
    n = n.replace(/&{2,}/g, '&');
    return n;
  }

  //    Ambil kategori dari query string (jika ada)
  var urlParams = new URLSearchParams(window.location.search);
  var kategoriLayananFromUrl = urlParams.get('kategoriLayanan');
  var jenKodeFromUrl = urlParams.get('jenKode');

  // Inisialisasi tabel utama menggunakan createTable

  var baseMainPath = '<?= site_url("pelayananrapatjas/datalist") ?>';

  // Prioritas: kategoriLayanan > jenKode
  let initialParamKey = null;
  let initialParamValue = null;
  if (kategoriLayananFromUrl && kategoriLayananFromUrl !== '') {
    initialParamKey = 'kategoriLayanan';
    initialParamValue = kategoriLayananFromUrl;
  } else if (jenKodeFromUrl && jenKodeFromUrl !== '') {
    initialParamKey = 'jenKode';
    initialParamValue = jenKodeFromUrl;
  }

  var initialApiUrl = (initialParamKey) ?
    buildApiUrlWithOptionalParam(baseMainPath, initialParamKey, initialParamValue) :
    buildApiUrlWithOptionalParam(baseMainPath, '', '');

  // Inisialisasi createTable (helper existing)
  table = createTable({
    apiUrl: initialApiUrl,
    numbering: true,
    dataSrc: 'items',
    onData: function (items) {
      // default render
    }
  });

  // patch fetchData main table agar normalisasi jika helper menghasilkan '?ganda'
  if (table && typeof table.fetchData === 'function' && typeof table.getConfig === 'function') {
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

  /* =========================
     reloadTable helper
     ========================= */
  function reloadTable() {
    if (typeof table !== 'undefined' && typeof table.fetchData === 'function') {
      table.fetchData({
        reload: true
      });
    }
  }

  /* =========================
     saveData, loadDetail, keranjang actions, etc.
     (gunakan persis implementasi yang sudah Anda punya)
     ========================= */

  /* saveData */
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
      .then(res => res.json())
      .then(data => {
        if (data.xname && data.xhash) {
          document.querySelectorAll('[name="' + data.xname + '"]').forEach(i => i.value = data.xhash);
        }
        if (typeof onSuccess === 'function') {
          onSuccess(data);
          return;
        }

        if (data.res === true) {
          reloadTable();
          sayAlert('successModal', 'Success', 'Data berhasil disimpan.', 'success');
        } else if (data.res === 'refresh') {
          loadContent(data.link);
        } else if (data.res === 'redirect') {
          window.location.href = data.link;
        } else {
          sayAlert('errorModal', 'Error', data.msg ?? 'Data gagal disimpan.', 'warning');
        }
      })
      .catch(err => {
        if (typeof onError === 'function') onError(err);
        else sayAlert('errorModal', 'Error', 'Terjadi kesalahan pada sistem.', 'warning');
      })
      .finally(() => hideLoading());
  }

  // Fungsi untuk menampilkan tracking modal Rapat JAS
  function showTrackingModal(id, kode_layanan, status_layanan) {
    // Panggil fungsi dari v_track_modal_rapat_jas.php
    showFullTrackingModalRapatJas(id, kode_layanan, status_layanan);
  }

  function loadDetail(id) {
    const url = '<?php echo site_url("pelayananrapatjas/detailList/") ?>' + id;
    const tbody = document.querySelector('#detail-body');
    tbody.innerHTML = '<tr><td colspan="7" class="text-center">Loading...</td></tr>';

    fetch(url)
      .then(response => response.json())
      .then(data => {
        tbody.innerHTML = '';
        if (data.items && data.items.length > 0) {
          data.items.forEach(function (row) {
            let tr = '<tr>';
            row.forEach(function (col) {
              tr += '<td>' + col + '</td>';
            });
            tr += '</tr>';
            tbody.innerHTML += tr;
          });
        } else {
          tbody.innerHTML = '<tr><td colspan="7" class="text-center">Tidak ada data</td></tr>';
        }
        $('#modalDetail').modal('show');
      })
      .catch(error => {
        console.error(error);
        tbody.innerHTML = '<tr><td colspan="7" class="text-center text-danger">Error load data</td></tr>';
        $('#modalDetail').modal('show');
      });
  }
</script>