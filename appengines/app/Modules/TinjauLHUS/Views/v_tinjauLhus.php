<?php // view: Modules/TinjauLHUS/Views/v_tinjauLhus.php ?>
<div class="row">
    <div class="col-md-12">
        <div class="card">
            <div class="card-header d-flex justify-content-between align-items-center">
                <label class="card-title mb-0"><?php echo $title ?></label>

                <!-- [ADDED] Filter Status -->
                <div class="d-flex align-items-center" style="gap:8px;">
                    <label class="mb-0 small text-muted">Status:</label>
                    <select id="statusFilter" class="form-select form-select-sm" style="width:260px;">
                        <option value="">— Semua status —</option>
                        <option value="tolak">LHUS ditolak</option>
                        <option value="5">LHUS belum ditinjau</option>
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
          <table class="table table-bordered align-middle">
            <thead>
              <tr>
                <th style="min-width:40px; width:5%;">No</th>
                <th style="min-width:300px; width:15%;">Layanan</th>
                <th style="min-width:60px; width:5%;">Jumlah</th>
                <th style="min-width:200px; width:25%;">Keterangan</th>
                <th style="min-width:120px; width:10%;">Status LHUS</th>
                <th style="min-width:120px; width:10%;">LHUS</th>
                <th style="min-width:250px; width:20%;">Keterangan LHUS</th>
                <th style="min-width:110px; width:5%;" class="text-center">Aksi</th>
              </tr>
            </thead>
            <tbody id="detail-body">
              <tr><td colspan="8" class="text-center">Loading...</td></tr>
            </tbody>
          </table>
        </div>
      </div>

      <div class="modal-footer">
        <!-- Tidak ada tombol “Kirim” sesuai permintaan -->
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
          } catch(e) {
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
    (function patchFetchData(){
      if (typeof table !== 'undefined' && table && typeof table.getConfig === 'function' && typeof table.fetchData === 'function' && !table.__fetchPatchedTL) {
          const _origFetch = table.fetchData.bind(table);
          let _currentAbort = null;
          table.fetchData = function(opts = {}) {
              try {
                  const cfg = table.getConfig();
                  if (cfg && typeof cfg.apiUrl === 'string') {
                      const u = new URL(cfg.apiUrl, window.location.origin);
                      u.searchParams.set('_ts', Date.now().toString());
                      cfg.apiUrl = normalizeDoubleQuestion(u.pathname + (u.search ? u.search : ''));
                  }
              } catch (err) {}
              try { if (_currentAbort) _currentAbort.abort(); } catch(e){}
              try { _currentAbort = new AbortController(); opts.signal = _currentAbort.signal; } catch(e){}
              return _origFetch(opts);
          };
          table.__fetchPatchedTL = true;
      }
    })();
    (function attachStatusFilter(){
        const sel = document.getElementById('statusFilter');
        if (!sel || sel.dataset.bound === '1') return;
        sel.addEventListener('change', function(){
            const val = (this.value || '').toString().trim();
            if (table?.getConfig) {
                const cfg = table.getConfig();
                cfg.apiUrl = normalizeDoubleQuestion(
                    buildApiUrlWithOptionalParam('<?php echo site_url("tinjaulhus/datalist") ?>', 'lnStatus', (val === '' ? null : val))
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
        const csrfName  = csrfInput ? csrfInput.getAttribute("name") : "";
        const csrfToken = csrfInput ? csrfInput.value : "";

        let formData = new FormData();
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

    // Load detail LN -> tampilkan modal
    function loadDetail(id) {
        const url = '<?php echo site_url("tinjaulhus/detaillist/") ?>' + id;
        const tbody = document.querySelector('#detail-body');
        tbody.innerHTML = '<tr><td colspan="8" class="text-center">Loading...</td></tr>';

        fetch(url, { headers: { 'X-Requested-With': 'XMLHttpRequest' } })
            .then(response => {
                if (!response.ok) {
                    return response.text().then(t => { throw new Error('HTTP ' + response.status + ': ' + t); });
                }
                return response.json();
            })
            .then(data => {
                tbody.innerHTML = '';
                if (data.items && data.items.length > 0) {
                    data.items.forEach(function(row) {
                        let tr = '<tr>';
                        row.forEach(function(col) { tr += '<td>' + col + '</td>'; });
                        tr += '</tr>';
                        tbody.innerHTML += tr;
                    });
                } else {
                    tbody.innerHTML = '<tr><td colspan="8" class="text-center">Tidak ada data</td></tr>';
                }

                const modalEl = document.getElementById('modalDetail');
                if (modalEl) modalEl.dataset.encLn = data.encLn || id;

                try {
                    if (typeof bootstrap !== 'undefined') {
                        let modalInstance = bootstrap.Modal.getInstance(modalEl);
                        if (!modalInstance) modalInstance = new bootstrap.Modal(modalEl);
                        if (!modalEl.classList.contains('show')) modalInstance.show();
                    } else if (typeof $ === 'function') {
                        if (!$('#modalDetail').hasClass('show')) $('#modalDetail').modal('show');
                    }
                } catch (err) {
                    console.warn('Modal show error', err);
                }
            })
            .catch(error => {
                console.error('loadDetail error:', error);
                tbody.innerHTML = '<tr><td colspan="8" class="text-center text-danger">Error load data</td></tr>';
                try {
                    const modalEl = document.getElementById('modalDetail');
                    if (typeof bootstrap !== 'undefined') {
                        let modalInstance = bootstrap.Modal.getInstance(modalEl);
                        if (!modalInstance) modalInstance = new bootstrap.Modal(modalEl);
                        if (!modalEl.classList.contains('show')) modalInstance.show();
                    } else if (typeof $ === 'function') {
                        if (!$('#modalDetail').hasClass('show')) $('#modalDetail').modal('show');
                    }
                } catch (e) {}
            });
    }

    // Handler: Simpan keterangan LHUS per baris (manual)
    document.addEventListener('click', function(e) {
        const btnSave = e.target.closest('.btn-save-detketlhus');
        if (!btnSave) return;

        e.preventDefault();
        const det = btnSave.dataset.det;
        if (!det) return;

        const textarea = document.getElementById('detketlhus_' + det);
        const ket = textarea ? textarea.value : '';
        const st  = textarea ? parseInt(textarea.getAttribute('data-statuslhus') || '0', 10) : 0;

        // Simpan hanya jika status != 0 (sudah diproses: diterima/ditolak)
        if (st === 0) {
            sayAlert('errorModal', 'Tidak Bisa Disimpan', 'Komentar hanya disimpan untuk item yang sudah diproses (Diterima/Ditolak).', 'warning');
            return;
        }

        btnSave.disabled = true;
        const csrfToken = _getCsrf();

        fetch('<?php echo site_url("tinjaulhus/savedetketlhus") ?>', {
            method: 'POST',
            headers: {
                'Content-Type': 'application/json',
                'X-CSRF-TOKEN': csrfToken,
                'X-Requested-With': 'XMLHttpRequest'
            },
            body: JSON.stringify({ detKode: parseInt(det, 10), ket: ket })
        })
        .then(res => res.json())
        .then(data => {
            if (data.xname && data.xhash) {
                document.querySelectorAll('[name="' + data.xname + '"]').forEach(input => input.value = data.xhash);
            }
            if (data.res) {
                sayAlert('successModal', 'Berhasil', data.msg || 'Tersimpan', 'success');
            } else {
                sayAlert('errorModal', 'Gagal', data.msg || 'Gagal menyimpan', 'warning');
            }
        })
        .catch(err => {
            console.error(err);
            sayAlert('errorModal', 'Error', 'Terjadi kesalahan sistem', 'warning');
        })
        .finally(() => { btnSave.disabled = false; });
    });

    // Handler: Terima / Tolak per det (langsung tanpa konfirmasi)
    document.addEventListener('click', function(e) {
        const btnAccept = e.target.closest('.btn-accept-lhus');
        const btnReject = e.target.closest('.btn-reject-lhus');
        if (!btnAccept && !btnReject) return;

        e.preventDefault();
        const isAccept = !!btnAccept;
        theEl = isAccept ? btnAccept : btnReject;
        const det = theEl.dataset.det;
        if (!det) return;

        theEl.disabled = true;
        const formData = new FormData();
        formData.append('detKode', det);
        formData.append('aksi', isAccept ? 'terima' : 'tolak');

        const csrfInput = document.querySelector('[name="<?= csrf_token() ?>"]');
        if (csrfInput) formData.append(csrfInput.getAttribute('name'), csrfInput.value);

        fetch('<?php echo site_url("tinjaulhus/prosesdetaillhus") ?>', {
            method: 'POST',
            headers: { 'X-Requested-With': 'XMLHttpRequest' },
            body: formData
        })
        .then(res => res.json())
        .then(data => {
            if (data.xname && data.xhash) {
                document.querySelectorAll('[name="' + data.xname + '"]').forEach(input => input.value = data.xhash);
            }
            if (data.res) {
                const modalEl = document.getElementById('modalDetail');
                const encLn = modalEl ? modalEl.dataset.encLn : null;
                if (encLn) {
                    try { loadDetail(encLn); } catch (err) { if (typeof table !== 'undefined') table.fetchData({ reload: true }); }
                } else {
                    if (typeof table !== 'undefined') table.fetchData({ reload: true });
                }
            } else {
                sayAlert('errorModal', 'Gagal', data.msg || 'Gagal memproses', 'warning');
            }
        })
        .catch(err => {
            console.error(err);
            sayAlert('errorModal', 'Error', 'Terjadi kesalahan sistem', 'warning');
        })
        .finally(() => { theEl.disabled = false; });
    });

    (function(){
    const SAVE_URL = '<?php echo site_url("tinjaulhus/savedetketlhus") ?>';

    // Debounce helper
    function debounce(fn, wait) {
        let t;
        return function(...args) {
            clearTimeout(t);
            t = setTimeout(() => fn.apply(this, args), wait);
        };
    }

    // Ambil token CSRF sekarang
    function _getCsrfLocal() {
        const csrfInput = document.querySelector('[name="<?= csrf_token() ?>"]');
        return csrfInput ? csrfInput.value : '';
    }

    // Simpan satu det (promise). HANYA dipanggil jika status != 0
    function saveSingleDet(detKode, ket) {
        const csrfToken = _getCsrfLocal();
        return fetch(SAVE_URL, {
            method: 'POST',
            headers: {
                'Content-Type': 'application/json',
                'X-CSRF-TOKEN': csrfToken,
                'X-Requested-With': 'XMLHttpRequest'
            },
            body: JSON.stringify({ detKode: parseInt(detKode, 10), ket: ket })
        })
        .then(res => res.json())
        .then(data => {
            if (data && data.xname && data.xhash) {
                document.querySelectorAll('[name="' + data.xname + '"]').forEach(input => input.value = data.xhash);
            }
            return data;
        })
        .catch(err => {
            console.error('saveSingleDet error det=' + detKode, err);
            return { res: false, error: err };
        });
    }

    // Simpan semua textarea yang statusnya != 0
    function saveAllDetKetLhus() {
        const modalEl = document.getElementById('modalDetail');
        if (!modalEl) return Promise.resolve({ ok: false, msg: 'Modal tidak ditemukan' });

        const inputs = modalEl.querySelectorAll('.detketlhus-input');
        const promises = [];
        inputs.forEach(input => {
            const det = input.getAttribute('data-det');
            const st  = parseInt(input.getAttribute('data-statuslhus') || '0', 10);
            if (det && st !== 0) {
                promises.push(saveSingleDet(det, input.value));
            }
        });

        if (promises.length === 0) return Promise.resolve({ ok: true, skipped: true });

        return Promise.all(promises).then(results => {
            const successCount = results.filter(r => r && r.res).length;
            return { ok: successCount === results.length, results: results, saved: successCount };
        });
    }

    // Debounced saver on typing — hanya untuk status != 0
    const debouncedSave = debounce(function(input) {
        const det = input.getAttribute('data-det');
        const st  = parseInt(input.getAttribute('data-statuslhus') || '0', 10);
        if (!det || st === 0) return;
        const saveBtn = input.closest('td, tr')?.querySelector('.btn-save-detketlhus');
        if (saveBtn) saveBtn.disabled = true;
        saveSingleDet(det, input.value).then(() => { if (saveBtn) saveBtn.disabled = false; });
    }, 800);

    // Autosave saat input (hanya status != 0)
    document.addEventListener('input', function(e) {
        const t = e.target;
        if (!t || !t.classList) return;
        if (t.classList.contains('detketlhus-input')) {
            const st = parseInt(t.getAttribute('data-statuslhus') || '0', 10);
            if (st !== 0) debouncedSave(t);
        }
    });

    // Simpan on blur (lebih agresif, hanya status != 0)
    document.addEventListener('blur', function(e) {
        const t = e.target;
        if (!t || !t.classList) return;
        if (t.classList.contains('detketlhus-input')) {
            const det = t.getAttribute('data-det');
            const st  = parseInt(t.getAttribute('data-statuslhus') || '0', 10);
            if (det && st !== 0) {
                const saveBtn = t.closest('td, tr')?.querySelector('.btn-save-detketlhus');
                if (saveBtn) saveBtn.disabled = true;
                saveSingleDet(det, t.value).finally(() => { if (saveBtn) saveBtn.disabled = false; });
            }
        }
    }, true);

    // Saat modal akan / selesai ditutup -> simpan komentar yang eligible dan refresh table utama
    (function attachModalCloseHandlers() {
        const modalEl = document.getElementById('modalDetail');
        if (!modalEl) return;

        function refreshMainTable() {
            try { if (typeof table !== 'undefined') table.fetchData({ reload: true }); } catch(e) {}
        }

        try {
            if (typeof bootstrap !== 'undefined') {
                modalEl.addEventListener('hide.bs.modal', function () {
                    saveAllDetKetLhus().catch(err => console.error(err));
                });
                modalEl.addEventListener('hidden.bs.modal', function () {
                    // Pastikan refresh setelah benar-benar tertutup
                    refreshMainTable();
                });
            } else if (typeof $ === 'function') {
                $(modalEl).on('hide.bs.modal', function () {
                    saveAllDetKetLhus().catch(err => console.error(err));
                });
                $(modalEl).on('hidden.bs.modal', function () {
                    refreshMainTable();
                });
            } else {
                // fallback click dismiss
                modalEl.addEventListener('click', function(ev) {
                    const btn = ev.target.closest('[data-bs-dismiss="modal"]');
                    if (btn) {
                        saveAllDetKetLhus().catch(err => console.error(err));
                        setTimeout(refreshMainTable, 300);
                    }
                });
            }
        } catch (err) {
            console.warn('attachModalCloseHandlers error', err);
        }
    })();

    // Expose helper (opsional)
    window.saveAllDetKetLhus = saveAllDetKetLhus;
})();
</script>
