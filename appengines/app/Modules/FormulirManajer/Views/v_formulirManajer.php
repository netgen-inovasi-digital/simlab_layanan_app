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
                            <th show>Tanggal</th>
                            <th show>Pemesan</th>
                            <th show>Asal</th>
                            <th show>Status</th>
                            <th show>Item Layanan</th>
                            <th show class="action text-end">Aksi<i class="bi bi-code sort-icon"></i></th>
                        </tr>
                    </thead>
                    <tbody id="table-body"></tbody>
                </table>
            </div>
        </div>
    </div>
</div>


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
                <th width="35%">Layanan</th>
                <th width="15%">Total</th>
                <th width="5%">Jumlah</th>
                <th width="40%">Keterangan</th>
                <th width="20%">Status</th>
                <th width="15%" class="text-center">Aksi</th>
            </tr>
          </thead>
          <tbody id="detail-body">
            <tr><td colspan="7" class="text-center">Loading...</td></tr>
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

   // Tombol Approve pada baris utama (tetap ada konfirmasi untuk baris utama)
function confirmApprove(e) {
    e.preventDefault();
    let id = e.currentTarget.closest('div').id;
    if (!id) return;

    if (!confirm('Yakin ingin approve data ini?')) return;

    // ambil CSRF token input (nilai name token berubah setelah request)
    const csrfInput = document.querySelector('[name="<?= csrf_token() ?>"]');
    const csrfToken = csrfInput ? csrfInput.value : '';

    // disable sementara tombol supaya tidak double click
    const container = document.getElementById(id);
    if (container) container.querySelectorAll('.btn-action').forEach(el => el.style.pointerEvents = 'none');

    fetch('<?php echo site_url("formulirmanajer/approve/") ?>' + id, {
        method: 'POST',
        headers: {
            'X-Requested-With': 'XMLHttpRequest',
            'X-CSRF-TOKEN': csrfToken
        }
    })
    .then(res => res.json())
    .then(data => {
        // Update CSRF token jika dikembalikan
        if (data.xname && data.xhash) {
            document.querySelectorAll('[name="' + data.xname + '"]').forEach(input => input.value = data.xhash);
        }

        if (data.res) {
            // tampilkan pesan spesifik berdasarkan det_affected
            const affected = parseInt(data.det_affected || data.affected || 0, 10);
            if (affected > 0) {
                sayAlert('successModal', 'Berhasil', `Data berhasil diapprove — ${affected} detail di-acc.`, 'success');
            } else {
                // Parent sudah diupdate tetapi tidak ada detail yang perlu di-acc (mungkin sudah di-acc)
                sayAlert('successModal', 'Berhasil', data.msg || 'Parent berhasil diapprove (tidak ada detail baru yang diubah).', 'success');
            }

            // refresh table utama jika ada
            if (typeof table !== 'undefined' && typeof table.fetchData === 'function') {
                table.fetchData({ reload: true });
            }

            // jika ada modal/detail view terbuka, coba reload detail (fungsi loadDetail harus ada)
            try {
                if (typeof loadDetail === 'function') {
                    loadDetail(id);
                }
            } catch (err) {
                // ignore
            }
        } else {
            sayAlert('errorModal', 'Gagal', data.msg || 'Approve gagal dilakukan', 'warning');
        }
    })
    .catch(err => {
        console.error(err);
        sayAlert('errorModal', 'Error', 'Terjadi kesalahan sistem', 'warning');
    })
    .finally(() => {
        if (container) container.querySelectorAll('.btn-action').forEach(el => el.style.pointerEvents = 'auto');
    });
}


    // Tombol Hapus pada baris utama
    function deleteItem(e) {
        e.preventDefault();
        let id = e.currentTarget.closest('div').id;
        if (!id) return;

        if (!confirm('Yakin ingin menghapus data ini?')) return;

        const csrfInput = document.querySelector('[name="<?= csrf_token() ?>"]');
        const csrfToken = csrfInput ? csrfInput.value : '';

        const container = document.getElementById(id);
        if (container) container.querySelectorAll('.btn-action').forEach(el => el.style.pointerEvents = 'none');

        fetch('<?php echo site_url("formulirmanajer/delete/") ?>' + id, {
            method: 'POST',
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
                if (typeof table !== 'undefined') table.fetchData({ reload: true });
                sayAlert('successModal', 'Berhasil', 'Data berhasil dihapus', 'success');
            } else {
                sayAlert('errorModal', 'Gagal', data.msg || 'Hapus gagal dilakukan', 'warning');
            }
        })
        .catch(err => {
            console.error(err);
            sayAlert('errorModal', 'Error', 'Terjadi kesalahan sistem', 'warning');
        })
        .finally(() => {
            if (container) container.querySelectorAll('.btn-action').forEach(el => el.style.pointerEvents = 'auto');
        });
    }

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
            $('#modalDetail').modal('show');
        })
        .catch(error => {
            console.error(error);
            tbody.innerHTML = '<tr><td colspan="7" class="text-center text-danger">Error load data</td></tr>';
            $('#modalDetail').modal('show');
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

    el.style.pointerEvents = 'none';

    const csrfInput = document.querySelector('[name="<?= csrf_token() ?>"]');
    const csrfToken = csrfInput ? csrfInput.value : '';

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

    el.style.pointerEvents = 'none';

    const csrfInput = document.querySelector('[name="<?= csrf_token() ?>"]');
    const csrfToken = csrfInput ? csrfInput.value : '';

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
        el.style.pointerEvents = 'auto';
    });
}

</script>
