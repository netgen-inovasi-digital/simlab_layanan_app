<!-- v_hasilPengujian.php -->
<div class="row">
    <div class="col-md-12">
        <div class="card">
            <div class="card-header d-flex justify-content-between align-items-center">
                <label class="card-title mb-0"><?php echo $title ?></label>
            </div>
            <div class="card-body">
                <table id="data-table" class="saytable border-top-bottom">
                    <thead>
                        <tr>
                            <th show width="5%">No.</th>
                            <th show width="25%">Pemesan</th> <!-- DITAMBAHKAN -->
                            <th show width="25%">Status Layanan</th>
                            <th show width="15%">Aksi layanan</th> 
                        </tr>
                    </thead>
                    <tbody id="table-body"></tbody>
                </table>
            </div>
        </div>
    </div>
</div>

<!--  Modal Detail (tetap ada) -->
<div class="modal fade" id="modalDetail" data-bs-backdrop="static" data-bs-keyboard="false" tabindex="-1">
  <div class="modal-dialog modal-xl modal-dialog-centered" role="document" style="max-width:1200px; margin: 1.5% auto;">
    <div class="modal-content">
      <div class="modal-header">
        <h5 class="modal-title">Detail Item Layanan</h5>
        <button id="btnSaveKomentar" type="button" class="btn-close" data-bs-dismiss="modal"></button>
      </div>
      <div class="modal-body">
        <div class="table-responsive">
          <table class="table table-bordered align-middle">
            <thead>
             <tr>
                  <th style="min-width:40px; width:5%;">No</th>
                  <th style="min-width:300px; width:15%;">Layanan</th>
                  <th style="min-width:60px; width:5%;">Jumlah</th>
                  <th style="min-width:200px; width:25%;" class="text-center">Keterangan</th>
                  <th style="min-width:200px; width:5%;" class="text-center">Status File</th>
                  <th style="min-width:110px; width:5%;" class="text-center">LHUS</th>
                  <th style="min-width:300px; width:20%;" class="text-center">keterangan Manajer</th>
              </tr>
            </thead>
            <tbody id="detail-body">
              <tr><td colspan="8" class="text-center">Loading...</td></tr>
            </tbody>
          </table>
        </div>
      </div>

      <div class="modal-footer">
         <button id="btnKirimDetail" class="btn btn-success" type="button" title="Kirim semua item (approve)" disabled
                 data-enc="">
          <i class="bi bi-send"></i> Kirim
        </button>
      </div>

    </div>
  </div>
</div>

<!-- NOTE: modalUploadLhus tetap ada (tidak dipakai oleh default flow langsung-upload), disimpan untuk fallback -->
<div class="modal fade" id="modalUploadLhus" data-bs-backdrop="static" data-bs-keyboard="false" tabindex="-1">
  <div class="modal-dialog modal-md" role="document" style="margin: 4% auto">
    <div class="modal-content">
      <div class="modal-header">
        <h5 class="modal-title">Unggah File LHUS</h5>
        <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
      </div>
      <div style="border-bottom:1px solid #e9ecef"></div>
      <div class="modal-body">
        <form id="formUploadLhus" action="<?php echo site_url('hasilpengujian/upload') ?>" method="post" enctype="multipart/form-data" novalidate>
          <input type="hidden" name="<?= csrf_token() ?>" value="<?= csrf_hash() ?>">
          <input type="hidden" name="id" id="upload_lhus_id" value="">
          <input type="hidden" name="detKode" id="upload_detKode" value="">

          <div class="mb-3">
            <label for="lhus_file" class="form-label">Pilih File (jpg, png, pdf, docx, xlsx)</label>
            <div class="d-flex align-items-center gap-2">
              <input type="file" name="lhus_file" id="lhus_file" class="form-control" accept=".jpg,.jpeg,.png,.pdf,.doc,.docx,.xls,.xlsx" style="max-width:360px">
              <button type="button" id="btnViewExistingLhus" class="btn btn-outline-primary btn-sm" title="Lihat Bukti" disabled>
                <span aria-hidden="true"></span> <span class="d-none d-sm-inline">Lihat Bukti</span>
              </button>
            </div>
            <div id="lhus-selection" class="form-text mt-2">Anda bisa unggah file baru untuk mengganti.</div>
            <div class="form-text text-muted">Ukuran maksimal 5MB.</div>
          </div>
        </form>
      </div>
      <div class="modal-footer justify-content-between">
        <div class="text-start">
          <button class="btn btn-light" type="button" id="btnCancelUpload" data-bs-dismiss="modal">Batal</button>
        </div>
        <div>
          <button class="btn btn-primary" id="btnUploadLhus" type="button">Unggah</button>
        </div>
      </div>
    </div>
  </div>
</div>

<script>
    table = createTable({
        apiUrl: '<?php echo site_url("hasilpengujian/datalist") ?>',
        dataSrc: 'items'
    });
    addAction();

    document.querySelector('#btnSimpan')?.addEventListener('click', function(e) {
        e.preventDefault();
        const form = document.querySelector('#myform');
        const formData = new FormData(form);
        const actionUrl = form.getAttribute('action');
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

    /* ---------------- existing helper functions left unchanged (saveData, confirmApprove, deleteItem, loadDetail) ---------------- */
    function saveData({ url, formData, onSuccess, onError }) {
        showLoading();
        const csrfInput = document.querySelector('[name="<?= csrf_token() ?>"]');
        const csrfToken = csrfInput ? csrfInput.value : '';
        fetch(url, { method: 'POST', body: formData, headers: { 'X-CSRF-TOKEN': csrfToken } })
        .then(response => response.json())
        .then(data => {
            if (data.xname && data.xhash) {
                document.querySelectorAll('[name="' + data.xname + '"]').forEach(input => {
                    input.value = data.xhash;
                });
            }
            if (typeof onSuccess === 'function') { onSuccess(data); return; }
            if ($('#modalForm').hasClass('show')) $('#modalForm').modal('hide');
            if (data.res === true) {
                if (typeof table !== 'undefined') table.fetchData({ reload: true });
                sayAlert('successModal', 'Success', 'Data berhasil disimpan.', 'success');
            } else if (data.res === 'reload' || data.res === 'refresh') {
                sayAlert('errorModal', 'Error', data.link, 'warning');
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
            if (typeof onError === 'function') { onError(error); }
            else sayAlert('errorModal', 'Error', 'Terjadi kesalahan pada sistem.', 'warning');
        })
        .finally(() => { hideLoading(); });
    }

    //  function confirmApprove(e) {
    //     e.preventDefault();
    //     let id = e.currentTarget.closest('div')?.id;
    //     if (!id) return;
    //     if (confirm('Yakin ingin approve data ini?')) {
    //         const csrfInput = document.querySelector('[name="<?= csrf_token() ?>"]');
    //         const csrfToken = csrfInput ? csrfInput.value : '';
    //         fetch('<?php echo site_url("hasilpengujian/approve/") ?>' + id, {
    //             method: 'POST',
    //             headers: {
    //                 'X-Requested-With': 'XMLHttpRequest',
    //                 'X-CSRF-TOKEN': csrfToken
    //             }
    //         })
    //         .then(res => res.json())
    //         .then(data => {
    //             if (data.res) {
    //                 if (typeof table !== 'undefined') table.fetchData({ reload: true });
    //                 sayAlert('successModal', 'Berhasil', 'Data berhasil diapprove', 'success');
    //             } else {
    //                 sayAlert('errorModal', 'Gagal', data.msg || 'Approve gagal dilakukan', 'warning');
    //             }
    //             if (data.xname && data.xhash) {
    //                 document.querySelectorAll('[name="' + data.xname + '"]').forEach(input => input.value = data.xhash);
    //             }
    //         })
    //         .catch(err => sayAlert('errorModal', 'Error', 'Terjadi kesalahan sistem', 'warning'));
    //     }
    // }

    function loadDetail(id) {
    const url = '<?php echo site_url("hasilpengujian/detaillist/") ?>' + id;
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

            const btnKirim = document.getElementById('btnKirimDetail');
            if (btnKirim) {
                const encLn = data.encLn || '';
                btnKirim.setAttribute('data-enc', encLn);
                if (data.allFilesUploaded) btnKirim.removeAttribute('disabled');
                else btnKirim.setAttribute('disabled', 'disabled');

                const newBtn = btnKirim.cloneNode(true);
                btnKirim.parentNode.replaceChild(newBtn, btnKirim);

                newBtn.addEventListener('click', function(ev) {
                    ev.preventDefault();
                    const enc = this.getAttribute('data-enc') || '';
                    if (!enc) { 
                        sayAlert('errorModal','Error','ID tidak ditemukan.','warning'); 
                        return; 
                    }
                    sayConfirm(
                        'Konfirmasi',
                        'Yakin ingin mengirim file LHUS untuk semua item ini?',
                        () => { doSendLhus(enc); },
                        'success',
                        'Kirim',
                        'Batal'
                    );
                });

            }

            // show modal - only show if it's not already visible to avoid stacking backdrops
            const modalEl = document.getElementById('modalDetail');
            if (modalEl) {
                // If Bootstrap 5 available, check class 'show' OR use getInstance
                try {
                    // prefer to check existing instance first
                    const existing = bootstrap.Modal && bootstrap.Modal.getInstance ? bootstrap.Modal.getInstance(modalEl) : null;
                    const isShown = modalEl.classList.contains('show') || (existing && typeof existing._isShown !== 'undefined' && existing._isShown);

                    if (!isShown) {
                        // create instance if not exists
                        const modal = existing || new bootstrap.Modal(modalEl);
                        modal.show();
                    } else {
                        // already shown — do nothing (content already updated)
                    }
                } catch (e) {
                    // fallback to jQuery if bootstrap object not available
                    try {
                        if (typeof $ !== 'undefined') {
                            if (!$('#modalDetail').hasClass('show')) $('#modalDetail').modal('show');
                        } else {
                            // last resort: attempt to show (but this branch unlikely)
                            const modal = new bootstrap.Modal(modalEl);
                            modal.show();
                        }
                    } catch (ee) {
                        console.warn('modal show fallback error', ee);
                    }
                }
            } else {
                // fallback for older jQuery modal
                if (typeof $ !== 'undefined' && !$('#modalDetail').hasClass('show')) $('#modalDetail').modal('show');
            }
        })
        .catch(error => {
            console.error('loadDetail error:', error);
            tbody.innerHTML = '<tr><td colspan="8" class="text-center text-danger">Error load data</td></tr>';
            // show modal only if not already shown
            const modalEl = document.getElementById('modalDetail');
            if (modalEl) {
                try {
                    const existing = bootstrap.Modal && bootstrap.Modal.getInstance ? bootstrap.Modal.getInstance(modalEl) : null;
                    const isShown = modalEl.classList.contains('show') || (existing && typeof existing._isShown !== 'undefined' && existing._isShown);
                    if (!isShown) {
                        const modal = existing || new bootstrap.Modal(modalEl);
                        modal.show();
                    }
                } catch (e) {
                    if (typeof $ !== 'undefined' && !$('#modalDetail').hasClass('show')) $('#modalDetail').modal('show');
                }
            }
        });
}

</script>

<script>
/**
 * autoUploadFile(input)
 * - Input element must have attributes:
 *    data-ln = encrypted ln (hex)
 *    data-detlist = comma separated detKode(s) OR empty
 * Behavior: upload file via fetch to hasilpengujian/upload and update UI inline.
 */
async function autoUploadFile(input) {
    if (!input || !input.files || input.files.length === 0) return;
    const file = input.files[0];
    const name = file.name || '';
    const ext = name.split('.').pop().toLowerCase();
    const allowedExt = ['jpg','jpeg','png','pdf','doc','docx','xls','xlsx'];
    const maxSize = 5 * 1024 * 1024;
    if (!allowedExt.includes(ext)) { alert('Format file tidak diperbolehkan.'); input.value = ''; return; }
    if (file.size > maxSize) { alert('Ukuran file maksimal 5MB.'); input.value = ''; return; }

    const encLn = input.getAttribute('data-ln') || '';
    const detlist = input.getAttribute('data-detlist') || '';
    const detCodes = detlist.split(',').map(s => s.trim()).filter(Boolean);
    let detKodeToSend = '';
    if (detCodes.length === 1) detKodeToSend = detCodes[0];

    const fd = new FormData();
    fd.append('lhus_file', file, file.name);
    fd.append('id', encLn);
    if (detKodeToSend) fd.append('detKode', detKodeToSend);

    // attach CSRF if present in DOM as hidden input (common CI pattern)
    const csrfInput = document.querySelector('[name="<?= csrf_token() ?>"]');
    if (csrfInput) fd.append(csrfInput.name, csrfInput.value);

    // UI: set uploading state on nearest button (if any)
    const parent = input.parentElement;
    const btn = parent ? parent.querySelector('button') : null;
    const originalHtml = btn ? btn.innerHTML : null;
    if (btn) { btn.disabled = true; btn.innerHTML = '<span class="spinner-border spinner-border-sm" role="status" aria-hidden="true"></span> Mengunggah...'; }

    try {
        const res = await fetch('<?php echo site_url("hasilpengujian/upload") ?>', {
            method: 'POST',
            body: fd,
            credentials: 'same-origin'
        });
        const json = await res.json();
        if (json.xname && json.xhash) {
            document.querySelectorAll('[name="' + json.xname + '"]').forEach(i => i.value = json.xhash);
        }

        if (json && (json.res === true || json.res === 'true')) {
            const fileUrl = json.url || null;
            // Replace uploader area with "Lihat File" button
            if (fileUrl) {
                if (parent) {
                    parent.innerHTML = '<div class="mb-2">'
                    + '<button type="button" class="btn btn-sm btn-outline-primary w-100 text-start" onclick="window.open(' + JSON.stringify(fileUrl) + ', \'_blank\')">'
                    + '<i class="bi bi-eye me-1"></i> Lihat File</button>'
                    + '</div>';
                }
            } else {
                if (btn) {
                    btn.innerHTML = 'Terunggah';
                    btn.classList.remove('btn-outline-secondary');
                    btn.classList.add('btn-outline-success');
                }
            }

            // Enable send button in same row if exists
            const tr = input.closest('tr');
            if (tr) {
                const sendElem = tr.querySelector('.btn-action[title="Tidak ada file LHUS"], .btn-action[title="Kirim LHUS"]');
                if (sendElem) {
                    // ganti menjadi aktif send
                    const enc = encLn || '';
                    const wrapper = document.createElement('span');
                    wrapper.className = 'text-success btn-action';
                    wrapper.title = 'Kirim LHUS';
                    wrapper.innerHTML = '<i class="bi bi-check-circle"></i>';
                    wrapper.setAttribute('onclick', 'confirmApprove(event, \'' + enc + '\')');
                    sendElem.parentNode.replaceChild(wrapper, sendElem);
                }
            }

            if (typeof sayAlert === 'function') {
                    sayAlert('successModal','Berhasil', json.msg || 'File berhasil diunggah.','success');
                    loadDetail(encLn); 
                } else {
                    alert(json.msg || 'File berhasil diunggah.');
                }
            } else {
                const message = (json && json.msg) ? json.msg : 'Gagal mengunggah file.';
                if (typeof sayAlert === 'function') sayAlert('errorModal','Gagal', message, 'warning'); else alert(message);
                if (btn) { btn.disabled = false; btn.innerHTML = originalHtml; }
                input.value = '';
            }
    } catch (err) {
        console.error(err);
        if (typeof sayAlert === 'function') sayAlert('errorModal','Error','Terjadi kesalahan saat mengunggah file.','warning'); else alert('Terjadi kesalahan saat mengunggah file.');
        if (btn) { btn.disabled = false; btn.innerHTML = originalHtml; }
        input.value = '';
    }
}

/* Utility: attach click-to-open file input for rows created by server data.
   Server will render input.lhus-uploader-input inside LHUS column - see controller detailList() output.
   If table is re-rendered, ensure addAction() (existing) runs again or call attachUploaderTriggers().
*/
function attachUploaderTriggers() {
    document.querySelectorAll('input.lhus-uploader-input').forEach(function(inp) {
        // ensure event only once
        if (!inp.dataset._hasAutoUpload) {
            inp.dataset._hasAutoUpload = '1';
            inp.addEventListener('change', function(){ autoUploadFile(inp); });
        }
    });
}

// initial attach (if table loads elements on render)
attachUploaderTriggers();

// Re-attach after table fetches new data (if your createTable calls addAction or trigger event, ensure attachUploaderTriggers runs)
if (typeof table !== 'undefined' && table.on) {
    table.on('draw', attachUploaderTriggers); // if createTable exposes events
}
</script>

<script>
document.addEventListener('click', function(ev) {
    const target = ev.target;
    const btn = target.closest ? target.closest('#btnViewExistingLhus') : null;
    if (!btn) return;
    const url = btn.getAttribute('data-url') || btn.dataset.url || null;
    if (url && url !== '#' && url !== '') {
        const w = window.open('', '_blank');
        if (w) {
            try { w.opener = null; w.location = url; } catch (err) { window.open(url, '_blank'); }
        } else { window.open(url, '_blank'); }
    } else {
        sayAlert('errorModal','Info','Tidak ada file bukti.','warning');
    }
});

(function() {
    const fi = document.getElementById('lhus_file');
    const sel = document.getElementById('lhus-selection');
    if (!fi) return;
    fi.addEventListener('change', function(e) {
        const f = e.target.files && e.target.files[0];
        if (f) {
            if (sel) sel.textContent = 'Anda memilih: ' + f.name;
            const viewBtn = document.getElementById('btnViewExistingLhus');
            if (viewBtn) viewBtn.setAttribute('disabled', 'disabled');
        } else {
            if (sel) sel.textContent = 'Anda bisa unggah file baru untuk mengganti.';
        }
    });
})();

document.getElementById('btnUploadLhus')?.addEventListener('click', function(e) {
    e.preventDefault();
    const form = document.getElementById('formUploadLhus');
    const formData = new FormData(form);
    const url = form.getAttribute('action');
    const csrfInput = document.querySelector('[name="<?= csrf_token() ?>"]');
    const csrfToken = csrfInput ? csrfInput.value : '';

    if (!formData.get('lhus_file') || formData.get('lhus_file').size === 0) {
        sayAlert('errorModal','Error','Pilih file terlebih dahulu.','warning');
        return;
    }

    showLoading();

    fetch(url, {
        method: 'POST',
        body: formData,
        headers: { 'X-CSRF-TOKEN': csrfToken }
    })
    .then(res => res.json())
    .then(data => {
        if (data.xname && data.xhash) {
            document.querySelectorAll('[name="' + data.xname + '"]').forEach(input => input.value = data.xhash);
        }
        if (data.res === true) {
            sayAlert('successModal','Berhasil', data.msg || 'File berhasil diunggah.','success');
            $('#modalUploadLhus').modal('hide');
            if (typeof table !== 'undefined') table.fetchData({ reload: true });
        } else {
            sayAlert('errorModal','Gagal', data.msg || 'Upload gagal.','warning');
        }
    })
    .catch(err => { console.error(err); sayAlert('errorModal','Error','Terjadi kesalahan saat upload.','warning'); })
    .finally(() => { hideLoading(); });
});

function doSendLhus(encId) {
    const csrfInput = document.querySelector('[name="<?= csrf_token() ?>"]');
    const csrfToken = csrfInput ? csrfInput.value : '';
    // disable send button segera
    const btnKirim = document.getElementById('btnKirimDetail');
    if (btnKirim) {
        btnKirim.setAttribute('disabled', 'disabled');
    }
    showLoading();

    if (!encId || encId === '') {
        hideLoading();
        if (btnKirim) btnKirim.removeAttribute('disabled');
        sayAlert('errorModal','Error','ID tidak ditemukan.','warning');
        return;
    }

    const body = new URLSearchParams();
    body.append('id', encId);

    fetch('<?php echo site_url("hasilpengujian/submit") ?>', {
        method: 'POST',
        headers: {
            'Content-Type': 'application/x-www-form-urlencoded; charset=UTF-8',
            'X-Requested-With': 'XMLHttpRequest',
            'X-CSRF-TOKEN': csrfToken
        },
        body: body.toString()
    })
    .then(res => {
        if (!res.ok) return res.text().then(t => { throw new Error('HTTP ' + res.status + ': ' + t); });
        return res.json();
    })
    .then(data => {
        if (data.xname && data.xhash) {
            document.querySelectorAll('[name="' + data.xname + '"]').forEach(input => input.value = data.xhash);
        }

        // 1) TUTUP modal detail segera (sebelum notifikasi)
        try {
            const modalEl = document.getElementById('modalDetail');
            if (modalEl) {
                // Bootstrap 5 preferred
                if (typeof bootstrap !== 'undefined' && bootstrap.Modal) {
                    const inst = bootstrap.Modal.getInstance(modalEl) || new bootstrap.Modal(modalEl);
                    try { inst.hide(); } catch (e) { /* ignore */ }
                } else if (typeof $ !== 'undefined') {
                    // jQuery fallback
                    try { $('#modalDetail').modal('hide'); } catch (e) {}
                }
            }
        } catch (e) {
            console.warn('hide modal error', e);
        }

        // 2) Tampilkan notifikasi
        if (data.res === true) {
            if (typeof sayAlert === 'function') {
                sayAlert('successModal','Berhasil', data.msg || 'File berhasil dikirim.','success');
            } else {
                alert(data.msg || 'File berhasil dikirim.');
            }

            // 3) refresh tabel sedikit setelah notifikasi ditampilkan
            const REFRESH_DELAY = 300; // ms - sesuaikan jika perlu
            setTimeout(function() {
                try {
                    if (typeof table !== 'undefined' && typeof table.fetchData === 'function') {
                        table.fetchData({ reload: true });
                    }
                } catch (e) { console.warn('table.fetchData error', e); }
            }, REFRESH_DELAY);

        } else {
            if (typeof sayAlert === 'function') sayAlert('errorModal','Gagal', data.msg || 'Kirim gagal.','warning');
            else alert(data.msg || 'Kirim gagal.');
            // jika gagal, kita bisa buka kembali modal (opsional) — di sini biarkan tertutup
        }
    })
    .catch(err => {
        console.error('doSendLhus error:', err);
        if (typeof sayAlert === 'function') sayAlert('errorModal','Error','Terjadi kesalahan saat mengirim LHUS.','warning');
        else alert('Terjadi kesalahan saat mengirim LHUS.');
    })
    .finally(() => {
        hideLoading();
        if (btnKirim) btnKirim.removeAttribute('disabled');
    });
}

// override confirmApprove to support confirmApprove(event, encId)
if (typeof window.confirmApprove === 'function') {
    window._orig_confirmApprove = window.confirmApprove;
}
window.confirmApprove = function(e, encId) {
    if (typeof encId !== 'undefined' && encId) {
        e.preventDefault();
        return sayConfirm(
            'Konfirmasi',
            'Yakin ingin mengirim file LHUS untuk data ini?',
            () => { doSendLhus(encId); },
            'success',
            'Kirim',
            'Batal'
        );
    }
    if (typeof window._orig_confirmApprove === 'function') {
        return window._orig_confirmApprove(e);
    }
    try {
        e.preventDefault();
        let id = e.currentTarget && e.currentTarget.closest ? e.currentTarget.closest('div').id : null;
        if (!id) return;
            return sayConfirm(
                'Konfirmasi',
                'Yakin ingin mengirim file LHUS untuk data ini?',
                () => { doSendLhus(id); },
                'success',
                'Kirim',
                'Batal'
            );
    } catch (err) { console.warn('confirmApprove fallback error:', err); }
    return;
};
</script>
