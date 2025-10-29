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
              <tr><td colspan="8" class="text-center">Loading...</td></tr>
            </tbody>
          </table>
        </div>
      </div>

      <div class="modal-footer">
         <button id="btnKirimDetail" class="btn btn-success" type="button" title="Kirim semua item (approve)">
          <i class="bi bi-send"></i> Kirim
        </button>
      </div>
    </div>
  </div>
</div>


<script>
    // Helpers URL
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

    // Tabel utama
    table = createTable({
        apiUrl: '<?php echo site_url("formulirmanajer/datalist") ?>',
        dataSrc: 'items'
    });
    addAction();

    // Patch normalize apiUrl saat reload
    if (typeof table !== 'undefined' && table && typeof table.getConfig === 'function' && typeof table.fetchData === 'function') {
        const _origFetch = table.fetchData.bind(table);
        table.fetchData = function(opts = {}) {
            try {
                const cfg = table.getConfig();
                if (cfg && cfg.apiUrl && typeof cfg.apiUrl === 'string') {
                    cfg.apiUrl = normalizeDoubleQuestion(cfg.apiUrl);
                }
            } catch (err) {}
            return _origFetch(opts);
        };
    }

    // Wiring dropdown Status -> filter lnStatus
    (function attachStatusFilter(){
        const sel = document.getElementById('statusFilter');
        if (!sel) return;

        sel.addEventListener('change', function(){
            const val = (this.value || '').toString().trim();
            if (typeof table !== 'undefined' && table && typeof table.getConfig === 'function') {
                const cfg = table.getConfig();
                cfg.apiUrl = buildApiUrlWithOptionalParam('<?= site_url("formulirmanajer/datalist") ?>', (val !== '' ? 'lnStatus' : ''), val);
                cfg.apiUrl = normalizeDoubleQuestion(cfg.apiUrl);
                if (typeof table.fetchData === 'function') table.fetchData({ reload: true, page: 1 });
            }
        });
    })();

    document.querySelector('#btnSimpan')?.addEventListener('click', function(e) {
        e.preventDefault(); 

        const form = document.querySelector('#myform');
        if (!form) return;

        const formData = new FormData(form);
        const actionUrl = form.getAttribute('action') || '<?php echo site_url("formulirmanajer/submit") ?>';

        saveData({
            url: actionUrl,
            formData: formData,
            onSuccess: function(data) {
                if (data.res === true) {
                    if (typeof table !== 'undefined') table.fetchData({ reload: true });
                    sayAlert('successModal', 'Berhasil', 'Data berhasil disimpan.', 'success');
                    if ($('#modalForm').hasClass('show')) $('#modalForm').modal('hide');
                }
            }
        });
    });


async function saveKomentarAsync() {
    const btn = document.getElementById('btnSaveKomentar');
    const modalEl = document.getElementById('modalDetail');
    if (!modalEl) return { ok: false, msg: 'Modal tidak ditemukan' };

    // ambil encLn dari modal atau fallback dari tombol kirim
    let encLn = modalEl.dataset.encLn || null;
    if (!encLn) {
        const btnKirim = document.getElementById('btnKirimDetail');
        if (btnKirim && btnKirim.dataset.ln) encLn = btnKirim.dataset.ln;
    }
    if (!encLn) {
        console.warn('LN tidak ditemukan untuk menyimpan komentar');
        return { ok: false, msg: 'LN tidak ditemukan' };
    }

    // kumpulkan textarea/input komentar dalam modal (kelas .komentar-input)
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
        // tidak ada yang disimpan
        return { ok: true, skipped: true };
    }

    const csrfToken = _getCsrf();

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

        // update token bila dikembalikan
        if (data.xname && data.xhash) {
            document.querySelectorAll('[name="' + data.xname + '"]').forEach(input => input.value = data.xhash);
        }

        if (data.res) {
            // reload data silent jika perlu
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


document.addEventListener('click', function(e) {
    if (!e.target.matches('#btnSaveKomentar') && !e.target.closest('#btnSaveKomentar')) return;
    e.preventDefault();

    // panggil fungsi async (silent)
    saveKomentarAsync().then(() => {});
});



    // Bootstrap Modal instance
    const _modalDetailEl = document.getElementById('modalDetail');
    let _modalDetailInstance = null;
    try {
        if (_modalDetailEl) {
            _modalDetailInstance = new bootstrap.Modal(_modalDetailEl);
        }
    } catch (err) {
        _modalDetailInstance = null;
    }

    // CSRF util
    function _getCsrf() {
        const csrfInput = document.querySelector('[name="<?= csrf_token() ?>"]');
        return csrfInput ? csrfInput.value : '';
    }

  
   // Tombol Kirim
    document.querySelector('#btnKirimDetail')?.addEventListener('click', async function(e) {
        e.preventDefault();

        const btn = e.currentTarget;
        const ln = btn.dataset.ln;
        if (!ln) {
            sayAlert('errorModal', 'Gagal', 'LN tidak ditemukan untuk dikirim', 'warning');
            return;
        }

        if (btn.dataset.sending === '1') return;

        sayConfirm(
            'Konfirmasi',
            'Setujui layanan ini?<br>Pastikan untuk cek kembali ketersediaan barang.',
            async () => {

                btn.dataset.sending = '1';
                btn.disabled = true;

                // Simpan komentar dulu (silent)
                try {
                    const komentarResult = await saveKomentarAsync();
                    if (!komentarResult.ok && !komentarResult.skipped) {
                        console.warn('Komentar bermasalah (ignored):', komentarResult);
                    }
                } catch (err) {
                    console.error('saveKomentarAsync error (ignored):', err);
                }

                const csrfToken = _getCsrf();
                const formData = new FormData();
                formData.append('ln', ln);

                try {
                    const res = await fetch('<?php echo site_url("formulirmanajer/kirim") ?>', {
                        method: 'POST',
                        body: formData,
                        headers: {
                            'X-Requested-With': 'XMLHttpRequest',
                            'X-CSRF-TOKEN': csrfToken
                        }
                    });

                    const data = await res.json();

                    if (data.xname && data.xhash) {
                        document
                            .querySelectorAll('[name="' + data.xname + '"]')
                            .forEach(input => input.value = data.xhash);
                    }

                    if (data.res) {
                        sayAlert('successModal', 'Berhasil', data.msg || 'Layanan berhasil dikirim', 'success');

                        if (typeof table !== 'undefined') table.fetchData({ reload: true });

                        try {
                            if (_modalDetailInstance) _modalDetailInstance.hide();
                            else if (typeof $ === 'function') $('#modalDetail').modal('hide');
                        } catch (_) {}
                    } else {
                        sayAlert('errorModal', 'Gagal', data.msg || 'Gagal mengirim layanan', 'warning');
                    }

                } catch (err) {
                    console.error(err);
                    sayAlert('errorModal', 'Error', 'Terjadi kesalahan sistem saat mengirim', 'warning');

                } finally {
                    btn.dataset.sending = '0';
                    btn.disabled = false;
                }
            }, 
            'success',
            'Kirim',
            'Batal'
        );
    });
    

    function saveData({ url, formData, onSuccess, onError }) {
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


  function loadDetail(id) {
    const url = '<?php echo site_url("formulirmanajer/detailList/") ?>' + id;
    const tbody = document.querySelector('#detail-body');
    tbody.innerHTML = '<tr><td colspan="8" class="text-center">Loading...</td></tr>';

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
                tbody.innerHTML = '<tr><td colspan="8" class="text-center">Tidak ada data</td></tr>';
            }

            // simpan encLn
            const modalEl = document.getElementById('modalDetail');
            if (modalEl) {
                if (data.encLn) modalEl.dataset.encLn = data.encLn;
                else modalEl.dataset.encLn = id;
            }

            // set LN pada tombol Kirim
            const btn = document.getElementById('btnKirimDetail');
            if (btn) btn.dataset.ln = id;

            try {
                if (_modalDetailInstance) _modalDetailInstance.show();
                else if (typeof $ === 'function') $('#modalDetail').modal('show');
            } catch (err) {
                if (typeof $ === 'function' && $('#modalDetail').modal) $('#modalDetail').modal('show');
            }
        })
        .catch(error => {
            console.error(error);
            tbody.innerHTML = '<tr><td colspan="8" class="text-center text-danger">Error load data</td></tr>';
            try {
                if (_modalDetailInstance) _modalDetailInstance.show();
                else if (typeof $ === 'function') $('#modalDetail').modal('show');
            } catch (e) {}
        });
}

// approve detail: silent
function confirmApproveDetail(e) {
    e.preventDefault();

    document.getElementById('btnSaveKomentar')?.click();

    const el = (e.currentTarget && e.currentTarget.dataset && e.currentTarget.dataset.ln) 
                ? e.currentTarget 
                : (e.target && e.target.closest ? e.target.closest('[data-ln]') : null);

    if (!el) return;

    const ln = el.dataset.ln;
    const uji = el.dataset.uji;
    if (!ln || (uji === undefined || uji === null)) {
        console.warn('approveDetail: missing ln or uji', ln, uji);
        return;
    }

    if (el.dataset.sending === '1') return;
    el.dataset.sending = '1';
    el.style.pointerEvents = 'none';

    const csrfToken = _getCsrf();

    const formData = new FormData();
    formData.append('ln', ln);
    formData.append('uji', uji);

    fetch('<?php echo site_url("formulirmanajer/approveDetail") ?>', {
        method: 'POST',
        body: formData,
        headers: {
            'X-Requested-With': 'XMLHttpRequest',
            'X-CSRF-TOKEN': csrfToken
        }
    })
    .then(res => res.json())
    .then(data => {
        if (data.xname && data.xhash) {
            document.querySelectorAll('[name="' + data.xname + '"]').forEach(input => input.value = data.xhash);
        }

        if (data.res) {
            try { loadDetail(ln); } catch (err) { console.error('loadDetail error', err); }
            if (typeof table !== 'undefined') table.fetchData({ reload: true });
        } else {
            console.warn('Gagal menyetujui item:', data.msg || null);
        }
    })
    .catch(err => {
        console.error('Error saat approveDetail:', err);
    })
    .finally(() => {
        el.dataset.sending = '0';
        el.style.pointerEvents = 'auto';
    });
}

// reject detail: silent
function confirmRejectDetail(e) {
    e.preventDefault();

    document.getElementById('btnSaveKomentar')?.click();
    const el = (e.currentTarget && e.currentTarget.dataset && e.currentTarget.dataset.ln) 
                ? e.currentTarget 
                : (e.target && e.target.closest ? e.target.closest('[data-ln]') : null);

    if (!el) return;

    const ln = el.dataset.ln;
    const uji = el.dataset.uji;
    if (!ln || (uji === undefined || uji === null)) {
        console.warn('rejectDetail: missing ln or uji', ln, uji);
        return;
    }

    if (el.dataset.sending === '1') return;
    el.dataset.sending = '1';
    el.style.pointerEvents = 'none';

    const csrfToken = _getCsrf();

    const formData = new FormData();
    formData.append('ln', ln);
    formData.append('uji', uji);

    fetch('<?php echo site_url("formulirmanajer/rejectDetail") ?>', {
        method: 'POST',
        body: formData,
        headers: {
            'X-Requested-With': 'XMLHttpRequest',
            'X-CSRF-TOKEN': csrfToken
        }
    })
    .then(res => res.json())
    .then(data => {
        if (data.xname && data.xhash) {
            document.querySelectorAll('[name="' + data.xname + '"]').forEach(input => input.value = data.xhash);
        }

        if (data.res) {
            try { loadDetail(ln); } catch (err) { console.error('loadDetail error', err); }
            if (typeof table !== 'undefined') table.fetchData({ reload: true });
        } else {
            console.warn('Gagal menolak item:', data.msg || null);
        }
    })
    .catch(err => {
        console.error('Error saat rejectDetail:', err);
    })
    .finally(() => {
        el.dataset.sending = '0';
        el.style.pointerEvents = 'auto';
    });
}


document.addEventListener('click', function(e) {
    // ACCEPT
    const acceptEl = e.target.closest ? e.target.closest('.btn-accept-manager') : null;
    if (acceptEl) {
        e.preventDefault();
        handleApproveReject(acceptEl, true);
        return;
    }

    // REJECT
    const rejectEl = e.target.closest ? e.target.closest('.btn-reject-manager') : null;
    if (rejectEl) {
        e.preventDefault();
        handleApproveReject(rejectEl, false);
        return;
    }
});

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
        try {
            const komentarResult = await saveKomentarAsync();
            if (!komentarResult.ok && !komentarResult.skipped) {
                console.warn('Penyimpanan komentar bermasalah (melanjutkan):', komentarResult);
            }
        } catch (err) {
            console.error('saveKomentarAsync error (ignored):', err);
        }

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

        if (data.xname && data.xhash) {
            document.querySelectorAll('[name="' + data.xname + '"]').forEach(input => input.value = data.xhash);
        }

        if (data.res) {
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

</script>
