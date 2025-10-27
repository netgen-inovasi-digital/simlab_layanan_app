<?php // view: Modules/TinjauLHUS/Views/v_tinjauLhus.php ?>
<div class="row">
    <div class="col-md-12">
        <div class="card">
            <div class="card-header d-flex justify-content-between align-items-center">
                <label class="card-title mb-0"><?php echo $title ?></label>
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
                            <!-- <th show width="20%" class="action text-end">Aksi</th> -->
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
        <!-- responsive wrapper: jika tabel lebar maka muncul scroll -->
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
        <button id="btnKirimDetail" class="btn btn-success" type="button" title="Kirim semua item (approve)">
          <i class="bi bi-send"></i> Kirim
        </button>
        <!-- <button class="btn btn-light" type="button" data-bs-dismiss="modal">
          <i class="bi bi-x-circle"></i> Tutup
        </button> -->
      </div>
    </div>
  </div>
</div>


<script>
    // Init table
    table = createTable({
        apiUrl: '<?php echo site_url("tinjaulhus/datalist") ?>',
        dataSrc: 'items'
    });
    addAction();

    // Utility ambil CSRF token
    function _getCsrf() {
        const csrfInput = document.querySelector('[name="<?= csrf_token() ?>"]');
        return csrfInput ? csrfInput.value : '';
    }

    /**
     * Proses LHUS (terima / tolak) untuk LN (parent)
     * NOTE: sudah diubah -> langsung eksekusi tanpa confirm
     */
    function prosesLhus(id, aksi) {
        if (!id || !aksi) return;

        const csrfInput = document.querySelector('[name="<?= csrf_token() ?>"]');
        const csrfName  = csrfInput ? csrfInput.getAttribute("name") : "";
        const csrfToken = csrfInput ? csrfInput.value : "";

        let formData = new FormData();
        if (csrfName) formData.append(csrfName, csrfToken);

        fetch('<?php echo site_url("tinjaulhus/proses/") ?>' + id + '/' + aksi, {
            method: 'POST',
            headers: {
                'X-Requested-With': 'XMLHttpRequest'
            },
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

function toggleKirimButton(modalEl) {
    const btn = document.getElementById('btnKirimDetail');
    if (!btn || !modalEl) return;

    // Cari badge "Belum Diproses" -> server meng-output '<span class="badge bg-secondary">Belum Diproses</span>'
    const pending = modalEl.querySelectorAll('.badge.bg-secondary').length > 0;

    if (pending) {
        // non-aktifkan tombol (greyed-out, tidak bisa diklik) — gaya konsisten dengan ikon action
        btn.style.opacity = '0.5';
        btn.style.pointerEvents = 'none';
        btn.classList.add('disabled');
        btn.setAttribute('aria-disabled', 'true');
        btn.title = 'Masih ada item yang belum diproses';
    } else {
        // aktifkan kembali
        btn.style.opacity = '';
        btn.style.pointerEvents = '';
        btn.classList.remove('disabled');
        btn.removeAttribute('aria-disabled');
        btn.title = 'Kirim semua item (approve)';
    }
}

/* Load detail LN => tampilkan modal (versi yang memanggil toggleKirimButton) */
function loadDetail(id) {
    const url = '<?php echo site_url("tinjaulhus/detaillist/") ?>' + id;
    const tbody = document.querySelector('#detail-body');
    // table punya 8 kolom, jadi colspan 8
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
            if (modalEl) {
                if (data.encLn) modalEl.dataset.encLn = data.encLn;
                else modalEl.dataset.encLn = id;

                // PENTING: evaluasi apakah tombol Kirim harus disembunyikan
                try {
                    toggleKirimButton(modalEl);
                } catch (err) { console.warn('toggleKirimButton error', err); }
            }

            try {
                if (typeof bootstrap !== 'undefined') {
                    // Reuse modal instance jika sudah ada agar backdrop tidak menumpuk
                    let modalInstance = bootstrap.Modal.getInstance(modalEl);
                    if (!modalInstance) {
                        modalInstance = new bootstrap.Modal(modalEl);
                    }
                    // Tampilkan modal hanya jika belum tampil
                    if (!modalEl.classList.contains('show')) {
                        modalInstance.show();
                    }
                } else if (typeof $ === 'function') {
                    // jQuery/Bootstrap v4 fallback: cek apakah sudah terbuka
                    if (!$('#modalDetail').hasClass('show')) {
                        $('#modalDetail').modal('show');
                    }
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
                if (modalEl) {
                    // pastikan tombol kirim tersembunyi kalau gagal load (aman)
                    toggleKirimButton(modalEl);
                }
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

    // --- Handler tombol Kirim (approve seluruh LN) ---
document.addEventListener('click', function (e) {
    const btn = e.target.closest('#btnKirimDetail');
    if (!btn) return;

    e.preventDefault();
    const modalEl = document.getElementById('modalDetail');
    if (!modalEl) {
        console.warn('ModalDetail tidak ditemukan');
        return;
    }

    // ambil encLn (diset saat loadDetail)
    const encLn = modalEl.dataset.encLn || null;
    if (!encLn) {
        sayAlert('errorModal', 'Gagal', 'ID LN tidak ditemukan. Muat ulang dan coba lagi.', 'warning');
        return;
    }

    // disable tombol sementara
    btn.disabled = true;
    btn.classList.add('disabled');

    // langsung pakai helper prosesLhus jika tersedia
    try {
        // jika Anda ingin tampilkan notifikasi/konfirmasi lokal, bisa di sini
        prosesLhus(encLn, 'terima');
        // setelah prosesLhus selesai, fungsi itu sendiri akan me-refresh table.
        // kita re-enable tombol setelah sedikit delay untuk keamanan (atau bergantung pada response CSRF update)
        setTimeout(() => { btn.disabled = false; btn.classList.remove('disabled'); }, 1200);
        // tutup modal (opsional) — hanya jika proses sukses, prosesLhus akan mereload table
        try {
            if (typeof bootstrap !== 'undefined') {
                const instance = bootstrap.Modal.getInstance(modalEl) || new bootstrap.Modal(modalEl);
                instance.hide();
            } else if (typeof $ === 'function') {
                $('#modalDetail').modal('hide');
            }
        } catch (err) { /* ignore modal hide error */ }
    } catch (err) {
        console.error('Kirim error', err);
        sayAlert('errorModal', 'Error', 'Gagal melakukan proses kirim', 'warning');
        btn.disabled = false;
        btn.classList.remove('disabled');
    }
});



    // Handler: Simpan keterangan LHUS (per baris)
    document.addEventListener('click', function(e) {
        const btnSave = e.target.closest('.btn-save-detketlhus');
        if (!btnSave) return;

        e.preventDefault();
        const det = btnSave.dataset.det;
        if (!det) return;
        const textarea = document.getElementById('detketlhus_' + det);
        const ket = textarea ? textarea.value : '';

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

    // Handler: Accept / Reject per det (per baris)
    // NOTE: diubah -> langsung proses tanpa konfirmasi
    document.addEventListener('click', function(e) {
        const btnAccept = e.target.closest('.btn-accept-lhus');
        const btnReject = e.target.closest('.btn-reject-lhus');
        if (!btnAccept && !btnReject) return;

        e.preventDefault();
        const isAccept = !!btnAccept;
        const el = isAccept ? btnAccept : btnReject;
        const det = el.dataset.det;
        if (!det) return;

        // langsung eksekusi tanpa konfirmasi pengguna
        el.disabled = true;
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
        .finally(() => {
            el.disabled = false;
        });
    });

    (function(){
    const SAVE_URL = '<?php echo site_url("tinjaulhus/savedetketlhus") ?>';
    const CSRF_NAME = '<?= csrf_token() ?>';

    // Debounce helper
    function debounce(fn, wait) {
        let t;
        return function(...args) {
            clearTimeout(t);
            t = setTimeout(() => fn.apply(this, args), wait);
        };
    }

    // Ambil token CSRF sekarang
    function _getCsrf() {
        const csrfInput = document.querySelector('[name="<?= csrf_token() ?>"]');
        return csrfInput ? csrfInput.value : '';
    }

    // Simpan satu det (mengembalikan promise)
    function saveSingleDet(detKode, ket) {
        const csrfToken = _getCsrf();
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
            // update token jika server mengembalikan
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

    // Simpan semua textarea di modal (mengembalikan promise all)
    function saveAllDetKetLhus() {
        const modalEl = document.getElementById('modalDetail');
        if (!modalEl) return Promise.resolve({ ok: false, msg: 'Modal tidak ditemukan' });

        const inputs = modalEl.querySelectorAll('.detketlhus-input');
        const promises = [];
        inputs.forEach(input => {
            const det = input.getAttribute('data-det');
            const val = input.value;
            if (det) {
                promises.push(saveSingleDet(det, val));
            }
        });

        if (promises.length === 0) return Promise.resolve({ ok: true, skipped: true });

        return Promise.all(promises).then(results => {
            // tentukan apakah mayoritas sukses
            const successCount = results.filter(r => r && r.res).length;
            return { ok: successCount === results.length, results: results, saved: successCount };
        });
    }

    // Debounced single-input saver (dipakai saat user mengetik)
    const debouncedSave = debounce(function(input) {
        const det = input.getAttribute('data-det');
        if (!det) return;
        // visual: disable tombol simpan pada baris terkait bila ada (opsional)
        const saveBtn = input.closest('td, tr')?.querySelector('.btn-save-detketlhus');
        if (saveBtn) saveBtn.disabled = true;
        saveSingleDet(det, input.value).then(() => { if (saveBtn) saveBtn.disabled = false; });
    }, 800);

    // Hook: autosave saat textarea berubah / blur
    document.addEventListener('input', function(e) {
        const t = e.target;
        if (!t || !t.classList) return;
        if (t.classList.contains('detketlhus-input')) {
            // autosave debounced
            debouncedSave(t);
        }
    });

    // Optional: juga simpan on blur (lebih agresif)
    document.addEventListener('blur', function(e) {
        const t = e.target;
        if (!t || !t.classList) return;
        if (t.classList.contains('detketlhus-input')) {
            // segera simpan
            const det = t.getAttribute('data-det');
            if (det) {
                const saveBtn = t.closest('td, tr')?.querySelector('.btn-save-detketlhus');
                if (saveBtn) saveBtn.disabled = true;
                saveSingleDet(det, t.value).finally(() => { if (saveBtn) saveBtn.disabled = false; });
            }
        }
    }, true); // useCapture true untuk tangkap blur yang tidak bubble

    // Saat tombol "Simpan" baris ditekan — tetap panggil saveSingleDet agar konsisten
    document.addEventListener('click', function(e) {
        const btnSave = e.target.closest('.btn-save-detketlhus');
        if (!btnSave) return;
        e.preventDefault();
        const det = btnSave.dataset.det;
        const textarea = document.getElementById('detketlhus_' + det);
        const val = textarea ? textarea.value : '';
        btnSave.disabled = true;
        saveSingleDet(det, val).then(data => {
            if (data && data.res) {
                sayAlert('successModal', 'Tersimpan', data.msg || 'Keterangan disimpan', 'success');
            } else {
                sayAlert('errorModal', 'Gagal', data.msg || 'Gagal menyimpan', 'warning');
            }
        }).finally(() => btnSave.disabled = false);
    });

    // Saat modal akan ditutup -> simpan semua (Bootstrap 5 event)
    (function attachModalHide() {
        const modalEl = document.getElementById('modalDetail');
        if (!modalEl) return;

        // Bootstrap 5: 'hide.bs.modal'
        try {
            if (typeof bootstrap !== 'undefined') {
                modalEl.addEventListener('hide.bs.modal', function (evt) {
                    // blokir close sementara: tidak menutup (tidak mengubah UI) — kita simpan async tapi tidak mencegah close
                    // simpan secara silent sebelum modal benar-benar hilang
                    saveAllDetKetLhus().then(result => {
                        if (result && result.ok) {
                            // silent success
                        } else {
                            // jika gagal sebagian, tulis ke console — tetap izinkan modal tutup
                            console.warn('saveAllDetKetLhus result', result);
                        }
                    }).catch(err => console.error(err));
                });
            } else if (typeof $ === 'function') {
                // jQuery/Bootstrap v4: 'hide.bs.modal'
                $(modalEl).on('hide.bs.modal', function () {
                    saveAllDetKetLhus().catch(err => console.error(err));
                });
            } else {
                // fallback: simpan saat tombol Tutup diklik (data-bs-dismiss)
                modalEl.addEventListener('click', function(ev) {
                    const btn = ev.target.closest('[data-bs-dismiss="modal"]');
                    if (btn) {
                        saveAllDetKetLhus().catch(err => console.error(err));
                    }
                });
            }
        } catch (err) {
            console.warn('attachModalHide error', err);
        }
    })();

    // Expose helper (opsional) untuk panggil manual jika mau
    window.saveAllDetKetLhus = saveAllDetKetLhus;
})();
</script>
