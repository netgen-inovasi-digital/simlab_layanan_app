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
                            <th show width="20%">No. Invoice & Tanggal</th>
                            <th show width="40%">Nama Layanan</th>
                            <th show width="12%">LHUS </th>
                            <th show width="13%">Status</th>
                            <th show width="20%" class="action text-end">Aksi</th>
                        </tr>
                    </thead>
                    <tbody id="table-body"></tbody>
                </table>
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

    document.querySelector('#btnSimpan').addEventListener('click', function(e) {
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
                sayAlert('successModal', 'Success', 'Data berhasil disimpan.', 'success');
                if (data.res === 'refresh' && data.link) loadContent(data.link);
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

     function confirmApprove(e) {
        e.preventDefault();
        let id = e.currentTarget.closest('div').id;
        if (!id) return;

        if (confirm('Yakin ingin approve data ini?')) {
            const csrfInput = document.querySelector('[name="<?= csrf_token() ?>"]');
            const csrfToken = csrfInput ? csrfInput.value : '';

            fetch('<?php echo site_url("hasilpengujian/approve/") ?>' + id, {
                method: 'POST',
                headers: {
                    'X-Requested-With': 'XMLHttpRequest',
                    'X-CSRF-TOKEN': csrfToken
                }
            })
            .then(res => res.json())
            .then(data => {
                if (data.res) {
                    if (typeof table !== 'undefined') table.fetchData({ reload: true });
                    sayAlert('successModal', 'Berhasil', 'Data berhasil diapprove', 'success');
                } else {
                    sayAlert('errorModal', 'Gagal', data.msg || 'Approve gagal dilakukan', 'warning');
                }

                if (data.xname && data.xhash) {
                    document.querySelectorAll('[name="' + data.xname + '"]').forEach(input => input.value = data.xhash);
                }
            })
            .catch(err => sayAlert('errorModal', 'Error', 'Terjadi kesalahan sistem', 'warning'));
        }
    }


    function loadDetail(id) {
        const url = '<?php echo site_url("hasilpengujian/detaillist/") ?>' + id;
        const tbody = document.querySelector('#detail-body');

        // tampilkan loading
        tbody.innerHTML = '<tr><td colspan="4" class="text-center">Loading...</td></tr>';

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
                    tbody.innerHTML = '<tr><td colspan="4" class="text-center">Tidak ada data</td></tr>';
                }
                // tampilkan modal
                if (typeof bootstrap !== 'undefined') {
                    const modalEl = document.getElementById('modalDetail');
                    const modal = new bootstrap.Modal(modalEl);
                    modal.show();
                } else {
                    $('#modalDetail').modal('show');
                }
            })
            .catch(error => {
                console.error('loadDetail error:', error);
                tbody.innerHTML = '<tr><td colspan="4" class="text-center text-danger">Error load data</td></tr>';
                if (typeof bootstrap !== 'undefined') {
                    const modalEl = document.getElementById('modalDetail');
                    const modal = new bootstrap.Modal(modalEl);
                    modal.show();
                } else {
                    $('#modalDetail').modal('show');
                }
            });
    }
</script>

<!-- 🔹 Modal Detail -->
<div class="modal fade" id="modalDetail" data-bs-backdrop="static" data-bs-keyboard="false" tabindex="-1">
  <div class="modal-dialog modal-lg" role="document" style="margin: 2% auto">
    <div class="modal-content">
      <div class="modal-header">
        <h5 class="modal-title">Detail Item Layanan</h5>
        <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
      </div>
      <div class="modal-body">
        <table class="table table-bordered">
          <thead>
            <tr>
              <th width="5%">No</th>
              <th width="40%">Layanan</th>
              <th width="20%">Biaya</th>
              <th width="20%">Keterangan</th>
            </tr>
          </thead>
          <tbody id="detail-body">
            <tr><td colspan="5" class="text-center">Loading...</td></tr>
          </tbody>
        </table>
      </div>
      <div class="modal-footer">
        <button class="btn btn-light" type="button" data-bs-dismiss="modal">
          <i class="bi bi-x-circle"></i> Tutup
        </button>
      </div>
    </div>
  </div>
</div>

<!-- Modal Upload LHUS -->
<div class="modal fade" id="modalUploadLhus" data-bs-backdrop="static" data-bs-keyboard="false" tabindex="-1">
  <div class="modal-dialog modal-md" role="document" style="margin: 4% auto">
    <div class="modal-content">
      <div class="modal-header">
        <h5 class="modal-title">Unggah File LHUS</h5>
        <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
      </div>

      <!-- Garis pemisah -->
      <div style="border-bottom:1px solid #e9ecef"></div>

      <div class="modal-body">
        <form id="formUploadLhus" action="<?php echo site_url('hasilpengujian/upload') ?>" method="post" enctype="multipart/form-data" novalidate>
          <!-- CSRF input (server-side) -->
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
          <button class="btn btn-light" type="button" id="btnCancelUpload" data-bs-dismiss="modal">
            <span aria-hidden="true"></span> Batal
          </button>
        </div>
        <div>
          <button class="btn btn-primary" id="btnUploadLhus" type="button">
            <span aria-hidden="true"></span> Unggah
          </button>
        </div>
      </div>
    </div>
  </div>
</div>

<script>
function openUploadModal(encId, fileUrl = '#', detKode = '') {
    // set id terenkripsi (hex)
    const inputId = document.getElementById('upload_lhus_id');
    if (inputId) inputId.value = encId || '';

    const inputDet = document.getElementById('upload_detKode');
    if (inputDet) inputDet.value = detKode || '';

    // reset file input
    const f = document.getElementById('lhus_file');
    if (f) f.value = '';

    // set teks instruksi
    const sel = document.getElementById('lhus-selection');
    if (sel) sel.textContent = 'Anda bisa unggah file baru untuk mengganti.';

    // set tombol lihat bukti di modal: simpan url di data-url dan aktifkan/disable tombol
    const viewBtn = document.getElementById('btnViewExistingLhus');
    if (viewBtn) {
        if (fileUrl && fileUrl !== '#' && fileUrl !== '') {
            viewBtn.removeAttribute('disabled');
            // simpan url secara eksplisit ke attribute data-url
            viewBtn.setAttribute('data-url', fileUrl);
        } else {
            viewBtn.setAttribute('disabled', 'disabled');
            viewBtn.removeAttribute('data-url');
        }
    }

    // show modal (Bootstrap 5) — gunakan bootstrap modal API jika tersedia
    if (typeof bootstrap !== 'undefined') {
        const modalEl = document.getElementById('modalUploadLhus');
        const modal = new bootstrap.Modal(modalEl);
        modal.show();
    } else {
        $('#modalUploadLhus').modal('show');
    }
}

/* ---------- Tambahan: handler untuk tombol Lihat Bukti di modal ---------- */
/* buka data-url pada tombol #btnViewExistingLhus di tab baru */
document.addEventListener('click', function(ev) {
    const target = ev.target;
    // gunakan closest agar klik icon/span di dalam button juga bekerja
    const btn = target.closest ? target.closest('#btnViewExistingLhus') : null;
    if (!btn) return;
    const url = btn.getAttribute('data-url') || btn.dataset.url || null;
    if (url && url !== '#' && url !== '') {
        // buka di tab baru (tambahkan noopener noreferrer)
        const w = window.open('', '_blank');
        if (w) {
            try {
                w.opener = null;
                w.location = url;
            } catch (err) {
                // fallback
                window.open(url, '_blank');
            }
        } else {
            window.open(url, '_blank');
        }
    } else {
        // tampilkan info jika tidak ada file
        if (typeof sayAlert === 'function') {
            sayAlert('errorModal', 'Info', 'Tidak ada file bukti.', 'warning');
        } else {
            alert('Tidak ada file bukti.');
        }
    }
});

/* ---- show filename when user selects a file ---- */
(function() {
    const fi = document.getElementById('lhus_file');
    const sel = document.getElementById('lhus-selection');
    if (!fi) return;
    fi.addEventListener('change', function(e) {
        const f = e.target.files && e.target.files[0];
        if (f) {
            if (sel) sel.textContent = 'Anda memilih: ' + f.name;
            // disable lihat bukti because user is replacing; keep previous URL in data-url if present
            const viewBtn = document.getElementById('btnViewExistingLhus');
            if (viewBtn) viewBtn.setAttribute('disabled', 'disabled');
        } else {
            if (sel) sel.textContent = 'Anda bisa unggah file baru untuk mengganti.';
        }
    });
})();

document.getElementById('btnUploadLhus').addEventListener('click', function(e) {
    e.preventDefault();
    const form = document.getElementById('formUploadLhus');
    const formData = new FormData(form);
    const url = form.getAttribute('action');
    const csrfInput = document.querySelector('[name="<?= csrf_token() ?>"]');
    const csrfToken = csrfInput ? csrfInput.value : '';

    if (!formData.get('lhus_file') || formData.get('lhus_file').size === 0) {
        if (typeof sayAlert === 'function') sayAlert('errorModal', 'Error', 'Pilih file terlebih dahulu.', 'warning');
        else alert('Pilih file terlebih dahulu.');
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
            if (typeof sayAlert === 'function') sayAlert('successModal', 'Berhasil', data.msg || 'File berhasil diunggah.', 'success');
            $('#modalUploadLhus').modal('hide');
            if (typeof table !== 'undefined') table.fetchData({ reload: true });
        } else {
            if (typeof sayAlert === 'function') sayAlert('errorModal', 'Gagal', data.msg || 'Upload gagal.', 'warning');
            else alert(data.msg || 'Upload gagal.');
        }
    })
    .catch(err => {
        console.error(err);
        if (typeof sayAlert === 'function') sayAlert('errorModal', 'Error', 'Terjadi kesalahan saat upload.', 'warning');
        else alert('Terjadi kesalahan saat upload.');
    })
    .finally(() => { hideLoading(); });
});

/**
 * doSendLhus(encId)
 * - mengirim id sebagai application/x-www-form-urlencoded untuk kompatibilitas CSRF/CI
 * - menerima encId (hex) yang dihasilkan oleh server (bin2hex(encrypt(...)))
 */
function doSendLhus(encId) {
    const csrfInput = document.querySelector('[name="<?= csrf_token() ?>"]');
    const csrfToken = csrfInput ? csrfInput.value : '';
    showLoading();

    // jika tidak diberikan encId, beri peringatan dan return
    if (!encId || encId === '') {
        hideLoading();
        if (typeof sayAlert === 'function') sayAlert('errorModal', 'Error', 'ID tidak ditemukan.', 'warning');
        else alert('ID tidak ditemukan.');
        return;
    }

    // Gunakan URLSearchParams (x-www-form-urlencoded) karena hanya mengirim satu field 'id'
    const body = new URLSearchParams();
    body.append('id', encId);
    // beberapa setup CSRF memerlukan token juga sebagai field POST — tambahkan jika perlu:
    // body.append('<?= csrf_token() ?>', csrfToken);

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
        if (!res.ok) {
            return res.text().then(t => { throw new Error('HTTP ' + res.status + ': ' + t); });
        }
        return res.json();
    })
    .then(data => {
        if (data.xname && data.xhash) {
            document.querySelectorAll('[name="' + data.xname + '"]').forEach(input => input.value = data.xhash);
        }
        if (data.res === true) {
            sayAlert('successModal', 'Berhasil', data.msg || 'File berhasil dikirim.', 'success');
            if (typeof table !== 'undefined') table.fetchData({ reload: true });
        } else {
            sayAlert('errorModal', 'Gagal', data.msg || 'Kirim gagal.', 'warning');
            console.warn('submit response:', data);
        }
    })
    .catch(err => {
        console.error('doSendLhus error:', err);
        sayAlert('errorModal', 'Error', 'Terjadi kesalahan saat mengirim LHUS. ' + (err.message || ''), 'warning');
    })
    .finally(() => { hideLoading(); });
}


// override global confirmApprove agar kompatibel:
// 1) Jika dipanggil confirmApprove(event, encId) -> gunakan encId langsung
// 2) Jika dipanggil confirmApprove(event) -> fallback ke _orig_confirmApprove jika ada (sebelumnya)
if (typeof window.confirmApprove === 'function') {
    window._orig_confirmApprove = window.confirmApprove;
}

window.confirmApprove = function(e, encId) {
    // jika encId diberikan (dipanggil per-row dengan ID), pakai doSendLhus
    if (typeof encId !== 'undefined' && encId) {
        e.preventDefault();
        if (!confirm('Yakin ingin mengirim file LHUS untuk data ini?')) return;
        doSendLhus(encId);
        return;
    }

    // fallback: jika ada implementasi confirmApprove lama, panggil
    if (typeof window._orig_confirmApprove === 'function') {
        return window._orig_confirmApprove(e);
    }

    // jika tidak ada sama sekali, coba ambil id dari DOM (div parent)
    try {
        e.preventDefault();
        let id = e.currentTarget && e.currentTarget.closest ? e.currentTarget.closest('div').id : null;
        if (!id) return;
        if (!confirm('Yakin ingin mengirim file LHUS untuk data ini?')) return;
        doSendLhus(id);
    } catch (err) {
        console.warn('confirmApprove fallback error:', err);
    }
    return;
};
</script>
