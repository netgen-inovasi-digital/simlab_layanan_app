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
                            <th show width="8%">No.</th>
                            <th show>Tanggal</th>
                            <th show>Pemesan</th>
                            <th show>Tipe</th>
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

<script>
    // init table dengan fitur search, show entries, dll
    table = createTable({
        apiUrl: '<?php echo site_url("formuliradmin/datalist") ?>',
        dataSrc: 'items'
    });
    addAction();

    // simpan data
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

    // Tombol Approve
    function confirmApprove(e) {
        e.preventDefault();
        let id = e.currentTarget.closest('div').id;
        if (!id) return;

        if (confirm('Yakin ingin approve data ini?')) {
            const csrfInput = document.querySelector('[name="<?= csrf_token() ?>"]');
            const csrfToken = csrfInput ? csrfInput.value : '';

            fetch('<?php echo site_url("formuliradmin/approve/") ?>' + id, {
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

    

    // Tombol Hapus
    function deleteItem(e) {
        e.preventDefault();
        let id = e.currentTarget.closest('div').id;
        if (!id) return;

        if (confirm('Yakin ingin menghapus data ini?')) {
            const csrfInput = document.querySelector('[name="<?= csrf_token() ?>"]');
            const csrfToken = csrfInput ? csrfInput.value : '';

            fetch('<?php echo site_url("formuliradmin/delete/") ?>' + id, {
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
                    sayAlert('successModal', 'Berhasil', 'Data berhasil dihapus', 'success');
                } else {
                    sayAlert('errorModal', 'Gagal', data.msg || 'Hapus gagal dilakukan', 'warning');
                }

                if (data.xname && data.xhash) {
                    document.querySelectorAll('[name="' + data.xname + '"]').forEach(input => input.value = data.xhash);
                }
            })
            .catch(err => sayAlert('errorModal', 'Error', 'Terjadi kesalahan sistem', 'warning'));
        }
    }

    // 🔹 Tombol Lihat Detail
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

<div class="modal fade" id="modalForm" data-bs-backdrop="static" data-bs-keyboard="false" tabindex="-1" aria-labelledby="staticBackdropLabel" aria-hidden="true">
    <div class="modal-dialog modal-lg" role="document" style="margin: 2% auto">
        <div class="modal-content">
            <div class="modal-header">
                <h5 class="modal-title">Formulir Admin</h5>
                <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close">
                </button>
            </div>
            <?php echo form_open('formuliradmin/submit', array('id' => 'myform', 'novalidate' => '')) ?>
            <div class="modal-body">
                <input type="hidden" value="" name="id" />
                <div class="row mb-2">
                    <div class="col">
                        <label class="col-md-3 col-form-label">Pemesan</label>
                        <input name="lnOrangNama" type="text" class="form-control" required placeholder="Masukkan nama pemesan">
                    </div>
                    <div class="col">
                        <label class="col-md-3 col-form-label">Instansi</label>
                        <input name="lnInstansi" type="text" class="form-control" required placeholder="Masukkan instansi">
                    </div>
                </div>
            </div>
            <div class="modal-footer">
                <button class="btn btn-light" type="button" data-bs-dismiss="modal"><i class="bi bi-x-circle"></i> Batal</button>
                <button class="btn btn-success" id="btnSimpan" type="submit"><i class="bi bi-check2-circle"></i> Simpan</button>
            </div>
            </form>
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
              <!-- <th width="15%">Kode</th> -->
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
