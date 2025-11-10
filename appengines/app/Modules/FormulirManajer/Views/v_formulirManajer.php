<div class="row"> 
    <div class="col-md-12">
        <div class="card">
            <div class="card-header d-flex justify-content-between align-items-center">
                <label class="card-title mb-0"><?php echo $title ?></label>
                <!-- Kategori Status (LnStatus) -->
                <div class="d-flex align-items-center" style="gap:8px;">
                    <label class="mb-0 small text-muted">Status:</label>
                    <select id="statusFilter" class="form-select form-select-sm" style="width:240px;">
                        <option value="">— Semua status —</option>
                        <option value="1">Layanan belum direview</option>
                        <option value="3">Layanan terkirim ke admin</option>
                    </select>
                </div>
            </div>
            <div class="card-body">
                <input type="hidden" name="<?= csrf_token() ?>" value="<?= csrf_hash() ?>">
                <table id="data-table" class="saytable border-top-bottom">
                    <thead>
                        <tr>
                            <th show width="8%">No.</th>
                            <th show width="35%">Pemesan</th>
                            <th show width="25%">Status layanan</th>
                            <th show width="15%">Aksi</th>
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
        <button id="btnSaveKomentar" type="button" class="btn-close" data-bs-dismiss="modal"></button>
      </div>
      <div class="modal-body">
        <!-- responsive wrapper: jika tabel lebar maka muncul scroll -->
        <div class="table-responsive">
          <table class="table table-bordered align-middle">
            <thead>
             <tr>
                  <th style="min-width:40px; width:5%;">No</th>
                  <th style="min-width:300px; width:15%;">Layanan</th>
                  <th style="min-width:60px; width:5%;">Jumlah</th>
                  <th style="min-width:200px; width:25%;">Keterangan</th>
                  <th style="min-width:120px; width:5%;">Status</th>
                  <th style="min-width:300px; width:20%;">Berikan keterangan</th>
                  <th style="min-width:110px; width:5%;" class="text-center">Aksi</th>
              </tr>
            </thead>
            <tbody id="detail-body">
              <tr><td colspan="7" class="text-center">Loading...</td></tr>
            </tbody>
          </table>
        </div>
      </div>
      <!-- Modal footer DIHAPUS (tombol Kirim dihapus sesuai permintaan) -->
    </div>
  </div>
</div>

<script>
    // ============================================================
    // HELPERS URL
    // ============================================================
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

    function normalizeDoubleQuestion(url) {
        if (typeof url !== 'string') return url;
        url = url.replace(/\?([^?]*)\?/, '?$1&');
        url = url.replace(/&{2,}/g, '&');
        url = url.replace(/\?&/, '?');
        if (url.endsWith('&')) url = url.slice(0, -1);
        return url;
    }

    // ============================================================
    // CSRF UTILITY
    // ============================================================
    function _getCsrf() {
        const csrfInput = document.querySelector('[name="<?= csrf_token() ?>"]');
        return csrfInput ? csrfInput.value : '';
    }

    // ============================================================
    // TABEL UTAMA
    // ============================================================
    table = createTable({
        apiUrl: '<?php echo site_url("formulirmanajer/datalist") ?>',
        dataSrc: 'items'
    });
    addAction();

    // Patch normalize apiUrl saat reload
    if (typeof table !== 'undefined' && table && typeof table.getConfig === 'function' && typeof table.fetchData === 'function' && !table.__fetchPatched) {
        const _origFetch = table.fetchData.bind(table);
        let _currentAbort = null;
        table.fetchData = function(opts = {}) {
            try {
                const cfg = table.getConfig();
                if (cfg && typeof cfg.apiUrl === 'string') {
                    const u = new URL(cfg.apiUrl, window.location.origin);
                    u.searchParams.set('_ts', Date.now().toString()); // cache-buster
                    cfg.apiUrl = normalizeDoubleQuestion(u.pathname + (u.search ? u.search : ''));
                }
            } catch (err) {}
            try { if (_currentAbort) _currentAbort.abort(); } catch(e){}
            try {
                _currentAbort = new AbortController();
                opts.signal = _currentAbort.signal;
            } catch(e){}
            return _origFetch(opts);
        };
        table.__fetchPatched = true;
    }

    // ============================================================
    // FILTER STATUS DROPDOWN
    // ============================================================
    (function attachStatusFilter(){
        const sel = document.getElementById('statusFilter');
        if (!sel || sel.dataset.bound === '1') return;
        sel.addEventListener('change', function(){
            const val = (this.value || '').toString().trim();
            if (table?.getConfig) {
                const cfg = table.getConfig();
                cfg.apiUrl = normalizeDoubleQuestion(
                    buildApiUrlWithOptionalParam('<?= site_url("formulirmanajer/datalist") ?>', 'lnStatus', (val === '' ? null : val))
                );
                table.fetchData({ reload: true, page: 1 });
            }
        });
        sel.dataset.bound = '1';
    })();

    // ============================================================
    // BOOTSTRAP MODAL INSTANCE
    // ============================================================
    window._modalDetailEl = window._modalDetailEl || document.getElementById('modalDetail');
    if (typeof window._modalDetailInstance === 'undefined' || window._modalDetailInstance === null) {
        try {
            window._modalDetailInstance = window._modalDetailEl ? new bootstrap.Modal(window._modalDetailEl) : null;
        } catch (err) {
            window._modalDetailInstance = null;
        }
    }

    // ============================================================
    // SAVE KOMENTAR ASYNC
    // ============================================================
    async function saveKomentarAsync() {
        const modalEl = document.getElementById('modalDetail');
        if (!modalEl) return { ok: false, msg: 'Modal tidak ditemukan' };

        // Ambil encLn dari modal dataset
        let encLn = modalEl.dataset.encLn || null;
        if (!encLn) {
            console.warn('LN tidak ditemukan untuk menyimpan komentar');
            return { ok: false, msg: 'LN tidak ditemukan' };
        }

        // Kumpulkan textarea/input komentar dalam modal (kelas .komentar-input)
        const inputs = modalEl.querySelectorAll('.komentar-input');
        const items = [];
        inputs.forEach(function(inp) {
            const uji = inp.getAttribute('data-uji');
            const val = inp.value;
            if (uji !== null && uji !== '') {
                items.push({ ujiKode: parseInt(uji, 10), komentar: val });
            }
        });

        if (items.length === 0) {
            // Tidak ada yang disimpan
            return { ok: true, skipped: true };
        }

        const csrfToken = _getCsrf();
        const btn = document.getElementById('btnSaveKomentar');
        if (btn) btn.disabled = true;

        try {
            const res = await fetch('<?php echo site_url("formulirmanajer/savekomentar") ?>', {
                method: 'POST',
                headers: {
                    'Content-Type': 'application/json',
                    'X-CSRF-TOKEN': csrfToken,
                    'X-Requested-With': 'XMLHttpRequest'
                },
                body: JSON.stringify({
                    lnId: encLn,
                    items: items
                })
            });

            const data = await res.json();

            // Update token bila dikembalikan
            if (data.xname && data.xhash) {
                document.querySelectorAll('[name="' + data.xname + '"]').forEach(input => input.value = data.xhash);
            }

            if (data.res) {
                // Reload data silent jika perlu
                if (typeof table !== 'undefined') table.fetchData({ reload: true });
                return { ok: true, data: data };
            } else {
                console.warn('Gagal menyimpan komentar:', data.msg || null);
                return { ok: false, data: data };
            }
        } catch (err) {
            console.error('Error saat menyimpan komentar:', err);
            return { ok: false, error: err };
        } finally {
            if (btn) btn.disabled = false;
        }
    }

    // Event listener untuk tombol close (auto-save komentar)
    document.addEventListener('click', function(e) {
        if (!e.target.matches('#btnSaveKomentar') && !e.target.closest('#btnSaveKomentar')) return;
        e.preventDefault();
        // Panggil fungsi async (silent)
        saveKomentarAsync().then(() => {});
    });

    // ============================================================
    // LOAD DETAIL LAYANAN
    // ============================================================
    function loadDetail(id) {
        const url = '<?php echo site_url("formulirmanajer/detailList/") ?>' + id;
        const tbody = document.querySelector('#detail-body');
        tbody.innerHTML = '<tr><td colspan="7" class="text-center">Loading...</td></tr>';

        fetch(url)
            .then(response => response.json())
            .then(data => {
                console.log('detailList response:', data);
                tbody.innerHTML = '';

                if (data.items && data.items.length > 0) {
                    data.items.forEach(function(row) {
                        let tr = '<tr>';
                        row.forEach(function(col) {
                            tr += '<td>' + col + '</td>';
                        });
                        tr += '</tr>';
                        tbody.innerHTML += tr;
                    });
                } else {
                    tbody.innerHTML = '<tr><td colspan="7" class="text-center">Tidak ada data</td></tr>';
                }

                // Simpan encLn ke modal dataset
                const modalEl = document.getElementById('modalDetail');
                if (modalEl) {
                    if (data.encLn) modalEl.dataset.encLn = data.encLn;
                    else modalEl.dataset.encLn = id;
                }

                // Show modal
                try {
                    if (_modalDetailInstance) _modalDetailInstance.show();
                    else if (typeof $ === 'function') $('#modalDetail').modal('show');
                } catch (err) {
                    if (typeof $ === 'function' && $('#modalDetail').modal) $('#modalDetail').modal('show');
                }
            })
            .catch(error => {
                console.error(error);
                tbody.innerHTML = '<tr><td colspan="7" class="text-center text-danger">Error load data</td></tr>';
                try {
                    if (_modalDetailInstance) _modalDetailInstance.show();
                    else if (typeof $ === 'function') $('#modalDetail').modal('show');
                } catch (e) {}
            });
    }

    // ============================================================
    // HANDLE APPROVE/REJECT (UNIFIED)
    // ============================================================
    async function handleApproveReject(el, isAccept) {
        if (!el) return;
        
        const ln = el.dataset.ln;
        const uji = el.dataset.uji;
        
        if (!ln || (uji === undefined || uji === null)) {
            console.warn('handleApproveReject: missing ln or uji', ln, uji);
            return;
        }

        if (el.dataset.sending === '1') return;
        el.dataset.sending = '1';
        el.style.pointerEvents = 'none';

        try {
            // 1. Save komentar terlebih dahulu
            try {
                const komentarResult = await saveKomentarAsync();
                if (!komentarResult.ok && !komentarResult.skipped) {
                    console.warn('Penyimpanan komentar bermasalah (melanjutkan):', komentarResult);
                }
            } catch (err) {
                console.error('saveKomentarAsync error (ignored):', err);
            }

            // 2. Approve/Reject
            const csrfToken = _getCsrf();
            const formData = new FormData();
            formData.append('ln', ln);
            formData.append('uji', uji);

            const url = isAccept
                ? '<?php echo site_url("formulirmanajer/approveDetail") ?>'
                : '<?php echo site_url("formulirmanajer/rejectDetail") ?>';

            const res = await fetch(url, {
                method: 'POST',
                body: formData,
                headers: {
                    'X-Requested-With': 'XMLHttpRequest',
                    'X-CSRF-TOKEN': csrfToken
                }
            });

            const data = await res.json();

            // Update CSRF token
            if (data.xname && data.xhash) {
                document.querySelectorAll('[name="' + data.xname + '"]').forEach(input => input.value = data.xhash);
            }

            if (data.res) {
                // Reload detail dan table
                try { loadDetail(ln); } catch (err) { console.error('loadDetail error', err); }
                if (typeof table !== 'undefined') table.fetchData({ reload: true });
            } else {
                console.warn((isAccept ? 'Gagal menyetujui' : 'Gagal menolak'), data.msg || null);
            }
        } catch (err) {
            console.error('Error saat handleApproveReject:', err);
        } finally {
            el.dataset.sending = '0';
            el.style.pointerEvents = 'auto';
        }
    }

    // ============================================================
    // EVENT DELEGATION UNTUK APPROVE/REJECT BUTTONS
    // ============================================================
    document.addEventListener('click', function(e) {
        // ACCEPT BUTTON
        const acceptEl = e.target.closest ? e.target.closest('.btn-accept-manager') : null;
        if (acceptEl) {
            e.preventDefault();
            handleApproveReject(acceptEl, true);
            return;
        }

        // REJECT BUTTON
        const rejectEl = e.target.closest ? e.target.closest('.btn-reject-manager') : null;
        if (rejectEl) {
            e.preventDefault();
            handleApproveReject(rejectEl, false);
            return;
        }
    });

    // ============================================================
    // SAVE DATA GENERIC (UNTUK FORM LAIN JIKA ADA)
    // ============================================================
    function saveData({ url, formData, onSuccess, onError }) {
        showLoading();
        const csrfToken = _getCsrf();

        fetch(url, {
                method: 'POST',
                body: formData,
                headers: {
                    'X-CSRF-TOKEN': csrfToken
                }
            })
            .then(response => response.json())
            .then(data => {
                // Update token
                if (data.xname && data.xhash) {
                    document.querySelectorAll('[name="' + data.xname + '"]').forEach(input => {
                        input.value = data.xhash;
                    });
                }

                if (typeof onSuccess === 'function') {
                    onSuccess(data);
                    return;
                }

                if ($('#modalForm').hasClass('show')) $('#modalForm').modal('hide');

                if (data.res === true) {
                    if (typeof table !== 'undefined') table.fetchData({ reload: true });
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
                    sayAlert('errorModal', 'Error', 'Terjadi kesalahan pada sistem.', 'warning');
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
</script>