<div class="row">
    <div class="col-md-12">
        <div class="card">
            <div class="card-header d-flex justify-content-between align-items-center">
                <label class="card-title mb-0"><?= $title ?></label>
                <button id="add" class="btn btn-primary">
                    <i class="bi bi-plus-circle-dotted"></i> Tambah
                </button>
            </div>
            <div class="card-body">
                <div class="table-wrapper">
                    <table id="data-table" class="saytable border-top-bottom">
                        <thead>
                            <tr>
                                <th width="8%">No.</th>
                                <th>Username</th>
                                <th>Layanan yang Dikelola</th>
                                <th>Nama</th>
                                <th>Status</th>
                                <th class="action text-end">Aksi</th>
                            </tr>
                        </thead>
                        <tbody id="table-body"></tbody>
                    </table>
                </div>
            </div>
        </div>
    </div>
</div>

<script>
let csrfHash = '<?= csrf_hash() ?>';

// Inisialisasi tabel utama
table = createTable({
    apiUrl: '<?= site_url("penyelia/datalist") ?>',
    showFilter: true
});
addAction();

const modal = document.getElementById('modalForm');
modal.addEventListener('shown.bs.modal', function () {
    const pwd = document.querySelector('[name="password"]');
    pwd.value = "";
    const id = document.querySelector('[name="id"]').value;
    if (id === "") pwd.setAttribute('required', true);
    else pwd.removeAttribute('required');
});

let modalLayananInstance = null;
let layananTable = null;

function lihatLayanan(id) {
    const modalEl = document.getElementById('modallayanan');
    modalEl.setAttribute('data-penyelia', id);

    // Reset isi modal
    const modalBody = modalEl.querySelector('.modal-body');
    modalBody.innerHTML = `
        <div class="table-wrapper">
            <table id="layanan-table-modal" class="saytable border-top-bottom">
                <thead>
                    <tr>
                        <th width="8%">No.</th>
                        <th>Nama Layanan</th>
                        <th>Status</th>
                        <th class="action text-end">Aksi</th>
                    </tr>
                </thead>
                <tbody></tbody>
            </table>
        </div>
    `;

    layananTable = createModal({
           apiUrl: '<?= site_url("penyelia/layanan") ?>/' + id,
            tableId: 'layanan-table-modal',
    });


    if (modalLayananInstance) {
        modalLayananInstance.hide();
        modalLayananInstance = null;
    }
    modalLayananInstance = new bootstrap.Modal(modalEl);
    modalLayananInstance.show();
}

// Hapus data penyelia (konsisten dengan deleteItem)
function deletePenyelia(event) {
    let el = event.currentTarget.closest('div');
    let id = el?.id || event.currentTarget.getAttribute('data-id');
    if (!id) return sayAlert('errorModal', 'Error', 'ID penyelia tidak ditemukan!', 'warning');

    sayAlert('confirmModal', 'Konfirmasi', 'Yakin ingin menghapus penyelia ini?', 'danger', true, () => {
        showLoading();
        fetch("<?= site_url('penyelia/delete') ?>/" + id, {
            method: 'GET',
            headers: { 'X-Requested-With': 'XMLHttpRequest' }
        })
        .then(res => res.json())
        .then(data => {
            if (data.xhash) csrfHash = data.xhash;
            if (data.res === 'ok') {
                if (typeof table !== 'undefined') table.fetchData({ reload: true });
                sayAlert('successModal', 'Berhasil', data.msg ?? 'Penyelia berhasil dihapus.', 'success');
            } else {
                sayAlert('errorModal', 'Gagal', data.msg ?? 'Gagal menghapus penyelia.', 'warning');
            }
        })
        .catch(err => {
            sayAlert('errorModal', 'Error', 'Terjadi kesalahan: ' + err.message, 'warning');
        })
        .finally(() => hideLoading());
    });
}


function deleteItem(event) {
    let el = event.currentTarget.closest('div');
    let id = el?.id || event.currentTarget.getAttribute('data-id');
    if (!id) {
        sayAlert('errorModal', 'Error', 'ID layanan tidak ditemukan!', 'warning');
        return;
    }

    sayAlert('confirmModal', 'Konfirmasi', 'Yakin ingin menghapus layanan ini dari penyelia?', 'danger', true, () => {
        showLoading();
        fetch("<?= site_url('penyelia/deleteLayanan') ?>/" + id, {
            method: 'GET',
            headers: { 'X-Requested-With': 'XMLHttpRequest' }
        })
        .then(res => res.json())
        .then(data => {
            if (data.xhash) csrfHash = data.xhash;
            if (data.res === 'ok') {
                if (typeof table !== 'undefined') table.fetchData({ reload: true });
                sayAlert('successModal', 'Berhasil', data.msg ?? 'Layanan berhasil dihapus.', 'success');
                let idPenyelia = document.getElementById('modallayanan').getAttribute('data-penyelia');
                if (idPenyelia) lihatLayanan(idPenyelia); // refresh modal
                table.refresh(); // refresh tabel utama
            } else {
                sayAlert('errorModal', 'Gagal', data.msg ?? 'Gagal menghapus layanan.', 'warning');
            }
        })
        .catch(err => {
            sayAlert('errorModal', 'Error', 'Terjadi kesalahan: ' + err.message, 'warning');
        })
        .finally(() => hideLoading());
    });
}

function tambahlayanan(idLayanan) {
    sayAlert('confirmModal', 'Konfirmasi', 'Yakin ingin menambahkan layanan ini?', 'primary', true, () => {
        let idPenyelia = document.getElementById('modallayanan').getAttribute('data-penyelia');
        showLoading();
        fetch("<?= site_url('penyelia/tambahLayananPenyelia') ?>", {
            method: 'POST',
            headers: {
                'Content-Type': 'application/json',
                'X-Requested-With': 'XMLHttpRequest'
            },
            body: JSON.stringify({
                layanan: idLayanan,
                penyelia: idPenyelia,
                '<?= csrf_token() ?>': csrfHash
            })
        })
        .then(res => res.json())
        .then(data => {
            if (data.xhash) csrfHash = data.xhash;
            if (data.res === 'ok') {
                lihatLayanan(idPenyelia);
                if (typeof table !== 'undefined') {
                    table.fetchData({ reload: true });
                }
                sayAlert('successModal', 'Berhasil', data.msg ?? 'Layanan berhasil ditambahkan.', 'success');
            } else {
                sayAlert('errorModal', 'Gagal', data.msg ?? 'Gagal menambahkan layanan.', 'warning');
            }
        })
        .catch(err => {
            sayAlert('errorModal', 'Error', 'Terjadi kesalahan: ' + err.message, 'warning');
        })
        .finally(() => hideLoading());
    });
}


function pilihLayanan(idLayanan) {
    tambahlayanan(idLayanan);
}
</script>

<!-- Modal Form Penyelia -->
<div class="modal fade" id="modalForm" data-bs-backdrop="static" data-bs-keyboard="false" tabindex="-1">
    <div class="modal-dialog" role="document" style="margin: 2% auto">
        <div class="modal-content">
            <div class="modal-header">
                <h5 class="modal-title">Data Penyelia</h5>
                <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
            </div>
            
            <?= form_open('penyelia/submit', ['id' => 'myform', 'novalidate' => '']) ?>
                <div class="modal-body">
                    <input type="hidden" value="" name="id"/>
                    <div class="row mb-2">
                        <label class="col-md-4 col-form-label">Username</label>
                        <div class="col">
                            <input name="username" type="text" class="form-control" required>
                        </div>
                    </div>
                    <div class="row mb-2">
                        <label class="col-md-4 col-form-label">Nama</label>
                        <div class="col">
                            <input name="nama" type="text" class="form-control" required>
                        </div>
                    </div>
                    <div class="row mb-2">
                        <label class="col-md-4 col-form-label">Password</label>
                        <div class="col">
                            <input name="password" type="password" class="form-control">
                        </div>
                    </div>
                    <div class="row mb-2">
                        <label class="col-4 col-form-label">Status</label>
                        <div class="col">
                            <div class="form-check mt-2 form-check-inline">
                                <input class="form-check-input" type="radio" name="status" value="1" checked>
                                <label class="form-check-label">Aktif</label>
                            </div>
                            <div class="form-check mt-2 form-check-inline">
                                <input class="form-check-input" type="radio" name="status" value="0">
                                <label class="form-check-label text-danger">Tidak Aktif</label>
                            </div>
                        </div>
                    </div>
                </div>
                <div class="modal-footer">
                    <button class="btn btn-light" type="button" data-bs-dismiss="modal">
                        <i class="bi bi-x-circle"></i> Batal
                    </button>
                    <button class="btn btn-success" type="submit">
                        <i class="bi bi-check2-circle"></i> Simpan
                    </button>
                </div>
            </form>
        </div>
    </div>
</div>

<!-- Modal Manajemen Layanan Penyelia -->
<div class="modal fade" id="modallayanan" data-bs-backdrop="static" data-bs-keyboard="false" tabindex="-1">
  <div class="modal-dialog modal-xl" role="document" style="margin: 2% auto">
    <div class="modal-content">
      <div class="modal-header">
        <h5 class="modal-title">Manajemen Layanan Penyelia</h5>
        <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
      </div>
      <div class="modal-body">
         <div class="table-wrapper">
            <table id="layanan-table" class="saytable border-top-bottom">
              <thead>
                <tr>
                  <th width="8%">No.</th>
                  <th>Nama Layanan</th>
                  <th>Status</th>
                  <th class="action text-end">Aksi</th>
                </tr>
              </thead>
              <tbody id="layanan-table-body"></tbody>
            </table>
        </div>
      </div>
      <div class="modal-footer">
        <button class="btn btn-secondary" data-bs-dismiss="modal">
          <i class="bi bi-x-circle"></i> Tutup
        </button>
      </div>
    </div>
  </div>
</div>
