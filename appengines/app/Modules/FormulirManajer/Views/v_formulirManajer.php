<div class="row">
    <div class="col-md-12">
        <div class="card">
            <div class="card-header d-flex justify-content-between align-items-center">
                <label class="card-title mb-0"><?php echo $title ?></label>
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
                            <!-- <th show class="action text-end">Aksi<i class="bi bi-code sort-icon"></i></th> -->
                        </tr>
                    </thead>
                    <tbody id="table-body"></tbody>
                </table>
            </div>
        </div>
    </div>
</div>


<!--  Modal Detail -->
<!--  Modal Detail (LEBIH BESAR & RESPONSIVE) -->
<div class="modal fade" id="modalDetail" data-bs-backdrop="static" data-bs-keyboard="false" tabindex="-1">
  <!-- gunakan modal-xl dan atur max-width supaya tidak terlalu melebar -->
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
                  <th style="min-width:300px; width:35%;">Layanan</th>
                  <th style="min-width:60px; width:5%;">Jumlah</th>
                  <th style="min-width:200px; width:20%;">Keterangan</th>
                  <th style="min-width:120px; width:7%;">Status</th>
                  <th style="min-width:110px; width:10%;" class="text-center">Aksi</th>
                  <th style="min-width:300px; width:25%;">Keterangan Manajer</th>
              </tr>
            </thead>
            <tbody id="detail-body">
              <tr><td colspan="8" class="text-center">Loading...</td></tr>
            </tbody>
          </table>
        </div>
      </div>

      <div class="modal-footer">
        <button id="btnSaveKomentar" class="btn btn-light" type="button" data-bs-dismiss="modal">
          <i class="bi bi-x-circle"></i> Tutup
        </button>

         <button id="btnKirimDetail" class="btn btn-success" type="button" title="Kirim semua item (approve)">
          <i class="bi bi-send"></i> Kirim
        </button>
      </div>
    </div>
  </div>
</div>


<script>
    // init table dengan fitur search, show entries, dll
    table = createTable({
        apiUrl: '<?php echo site_url("formulirmanajer/datalist") ?>',
        dataSrc: 'items'
    });
    addAction();

    // simpan data (tetap seperti semula)
    document.querySelector('#btnSimpan')?.addEventListener('click', function(e) {
        e.preventDefault(); // Hindari submit default

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

    // --- setelah loadDetail() definisi ---

   // Simpan komentar (per-layanan) — silent (tanpa notif / tanpa showLoading)
document.addEventListener('click', function(e) {
    if (!e.target.matches('#btnSaveKomentar') && !e.target.closest('#btnSaveKomentar')) return;
    e.preventDefault();

    const btn = document.getElementById('btnSaveKomentar');
    if (!btn) return;

    // ambil encLn dari response yang disimpan di modal (set saat loadDetail)
    const modalEl = document.getElementById('modalDetail');
    let encLn = modalEl ? modalEl.dataset.encLn : null;
    if (!encLn) {
        // fallback: gunakan data-ln di tombol Kirim jika tersedia
        const btnKirim = document.getElementById('btnKirimDetail');
        if (btnKirim && btnKirim.dataset.ln) {
            encLn = btnKirim.dataset.ln;
        }
    }
    if (!encLn) {
        console.warn('LN tidak ditemukan untuk menyimpan komentar');
        return;
    }

    // kumpulkan textarea komentar
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
        // tidak ada yang disimpan (silent)
        return;
    }

    const csrfToken = _getCsrf();

    btn.disabled = true;

    fetch('<?php echo site_url("formulirmanajer/savekomentar") ?>', {
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
    })
    .then(res => res.json())
    .then(data => {
        if (data.xname && data.xhash) {
            document.querySelectorAll('[name="' + data.xname + '"]').forEach(input => input.value = data.xhash);
        }
        if (data.res) {
            // reload detail dan tabel utama (silent)
            if (typeof table !== 'undefined') table.fetchData({ reload: true });
            // loadDetail(encLn);
        } else {
            // gagal: hanya log ke console (silent)
            console.warn('Gagal menyimpan komentar:', data.msg || null);
        }
    })
    .catch(err => {
        console.error('Error saat menyimpan komentar:', err);
    })
    .finally(() => {
        btn.disabled = false;
    });
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

    // Buat satu instance modal (Bootstrap 5) jika tersedia, agar tidak membuat banyak backdrop
    const _modalDetailEl = document.getElementById('modalDetail');
    let _modalDetailInstance = null;
    try {
        if (_modalDetailEl) {
            _modalDetailInstance = new bootstrap.Modal(_modalDetailEl);
        }
    } catch (err) {
        // jika bootstrap belum tersedia, akan fallback ke jQuery modal show/hide seperti sebelumnya
        _modalDetailInstance = null;
    }

    // Utility: ambil token CSRF saat ini
    function _getCsrf() {
        const csrfInput = document.querySelector('[name="<?= csrf_token() ?>"]');
        return csrfInput ? csrfInput.value : '';
    }

    // Tombol Kirim: gunakan flag sending dan disabled (tidak hanya pointerEvents)
    document.querySelector('#btnKirimDetail')?.addEventListener('click', async function(e) {
        e.preventDefault();
        const btn = e.currentTarget;
        const ln = btn.dataset.ln;
        if (!ln) {
            sayAlert('errorModal', 'Gagal', 'LN tidak ditemukan untuk dikirim', 'warning');
            return;
        }

        if (btn.dataset.sending === '1') return; // sudah dalam proses
        if (!confirm('Kirim semua item pada layanan ini? Pastikan semua item sudah disetujui/ditolak.')) return;

        btn.dataset.sending = '1';
        btn.disabled = true;

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

            // update token jika dikembalikan
            if (data.xname && data.xhash) {
                document.querySelectorAll('[name="' + data.xname + '"]').forEach(input => input.value = data.xhash);
            }

            if (data.res) {
                // sukses
                sayAlert('successModal', 'Berhasil', data.msg || 'Layanan berhasil dikirim', 'success');
                // reload detail & tabel utama
                loadDetail(ln);
                if (typeof table !== 'undefined') table.fetchData({ reload: true });
                // tutup modal jika mau: gunakan instance agar backdrop tidak menumpuk
                try {
                    if (_modalDetailInstance) _modalDetailInstance.hide();
                    else if (typeof $ === 'function') $('#modalDetail').modal('hide');
                } catch (err) { /* ignore */ }
            } else {
                // gagal (mungkin ada pending)
                const msg = data.msg || 'Gagal mengirim layanan';
                sayAlert('errorModal', 'Gagal', msg, 'warning');
            }
        } catch (err) {
            console.error(err);
            sayAlert('errorModal', 'Error', 'Terjadi kesalahan sistem saat mengirim', 'warning');
        } finally {
            btn.dataset.sending = '0';
            btn.disabled = false;
        }
    });


  function loadDetail(id) {
    const url = '<?php echo site_url("formulirmanajer/detailList/") ?>' + id;
    const tbody = document.querySelector('#detail-body');
    // sesuaikan colspan kalau header punya 8 kolom
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

            // SIMPAN encLn ke modal agar handler lain (saveKomentar) bisa pakai
            const modalEl = document.getElementById('modalDetail');
            if (modalEl) {
                if (data.encLn) modalEl.dataset.encLn = data.encLn;
                else modalEl.dataset.encLn = id; // fallback kalau server tidak mengembalikan encLn
            }

            // set LN pada tombol Kirim di modal agar handler tahu LN yang sedang ditampilkan
            const btn = document.getElementById('btnKirimDetail');
            if (btn) btn.dataset.ln = id;

            // show modal menggunakan instance jika ada (mencegah backdrop ganda)
            try {
                if (_modalDetailInstance) _modalDetailInstance.show();
                else if (typeof $ === 'function') $('#modalDetail').modal('show');
            } catch (err) {
                // fallback: tetap coba tampilkan dengan jQuery atau biarkan HTML default
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


// approve detail: langsung panggil endpoint dan reload detail dan tabel utama
function confirmApproveDetail(e) {
    e.preventDefault();

    // cari elemen span.btn-action paling dekat (tahan kasus klik pada <i>)
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

    // prevent double click by disabling pointer & using dataset.sending
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
        console.log('approveDetail response:', data);
        if (data.xname && data.xhash) {
            document.querySelectorAll('[name="' + data.xname + '"]').forEach(input => input.value = data.xhash);
        }
        if (data.res) {
            loadDetail(ln);
            if (typeof table !== 'undefined') table.fetchData({ reload: true });
            sayAlert('successModal', 'Berhasil', 'Item berhasil disetujui', 'success');
        } else {
            sayAlert('errorModal', 'Gagal', data.msg || 'Gagal menyetujui item', 'warning');
        }
    })
    .catch(err => {
        console.error(err);
        sayAlert('errorModal', 'Error', 'Terjadi kesalahan sistem', 'warning');
    })
    .finally(() => {
        el.dataset.sending = '0';
        el.style.pointerEvents = 'auto';
    });
}

function confirmRejectDetail(e) {
    e.preventDefault();

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

    // prevent double click
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
        console.log('rejectDetail response:', data);
        if (data.xname && data.xhash) {
            document.querySelectorAll('[name="' + data.xname + '"]').forEach(input => input.value = data.xhash);
        }
        if (data.res) {
            loadDetail(ln);
            if (typeof table !== 'undefined') table.fetchData({ reload: true });
            sayAlert('successModal', 'Berhasil', 'Item berhasil ditolak', 'success');
        } else {
            sayAlert('errorModal', 'Gagal', data.msg || 'Gagal menolak item', 'warning');
        }
    })
    .catch(err => {
        console.error(err);
        sayAlert('errorModal', 'Error', 'Terjadi kesalahan sistem', 'warning');
    })
    .finally(() => {
        el.dataset.sending = '0';
        el.style.pointerEvents = 'auto';
    });
}

</script>
