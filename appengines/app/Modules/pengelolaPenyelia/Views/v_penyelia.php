<div class="row">
    <div class="col-md-12">
        <div class="card">
            <div class="card-header d-flex justify-content-between align-items-center">
                <label class="card-title mb-0"><?= $title ?></label>
                <!-- <button id="add" class="btn btn-primary">
                    <i class="bi bi-plus-circle-dotted"></i> Tambah
                </button> -->
            </div>
            <div class="card-body">
                <div class="table-wrapper">
                    <table id="data-table" class="saytable border-top-bottom">
                        <thead>
                            <tr>
                                <th width="8%">No.</th>
                                <th width="15%">Username</th>
                                <th width="15%">Nama</th>
                                <th width="15%"> Status</th>
                                <th width="5%">Aksi</th>
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
var csrfHash = '<?= csrf_hash() ?>';

// Inisialisasi tabel utama
table = createTable({
    apiUrl: '<?= site_url("pengelolaPenyelia/datalist") ?>',
    showFilter: true
});

var modalLayananInstance = null;
var layananTable = null;

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
           apiUrl: '<?= site_url("pengelolaPenyelia/layanan") ?>/' + id,
            tableId: 'layanan-table-modal',
    });


    if (modalLayananInstance) {
        modalLayananInstance.hide();
        modalLayananInstance = null;
    }
    modalLayananInstance = new bootstrap.Modal(modalEl);
    modalLayananInstance.show();
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
        fetch("<?= site_url('pengelolaPenyelia/deleteLayanan') ?>/" + id, {
            method: 'GET',
            headers: { 'X-Requested-With': 'XMLHttpRequest' }
        })
        .then(res => res.json())
        .then(data => {
            if (data.xhash) csrfHash = data.xhash;
            if (data.res === 'ok') {
                sayAlert('successModal', 'Berhasil', data.msg ?? 'Layanan berhasil dihapus.', 'success');
                
                // Refresh tabel utama
                if (typeof table !== 'undefined') table.fetchData({ reload: true });
                
                // Refresh data modal tanpa recreate tabel
                if (layananTable && typeof layananTable.fetchData === 'function') {
                    layananTable.fetchData({ reload: true });
                }
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
    // Buat modal konfirmasi custom untuk tambah layanan
    const modalId = 'confirmTambahModal';
    let modalElement = document.getElementById(modalId);
    
    if (!modalElement) {
        const modalHtml = `
          <div class="modal fade" id="${modalId}" tabindex="-1" aria-labelledby="${modalId}Label" aria-hidden="true">
            <div class="modal-dialog" style="margin: 5% auto">
              <div class="modal-content">
                <div class="modal-header bg-primary text-white">
                  <h5 class="modal-title" id="${modalId}Label">Konfirmasi</h5>
                  <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
                </div>
                <div class="modal-body">
                    <div class="sayalert d-flex align-items-stretch">
                        <div class="me-3 d-flex align-items-center text-primary">
                            <i class="bi bi-patch-question icon"></i>
                        </div>
                        <div class="flex-grow-1 align-self-center">
                            <div class="alert-title">Konfirmasi</div>
                            <div class="alert-subtitle">Yakin ingin menambahkan layanan ini?</div>
                        </div>
                        <div class="button-container justify-content-center">
                            <button type="button" class="btn btn-sm btn-primary ms-4 me-2" id="${modalId}ConfirmButton">Tambah</button>
                            <div class="button-divider"></div>
                            <button type="button" class="btn btn-sm btn-light ms-4 me-2" data-bs-dismiss="modal">Batal</button>
                        </div>
                    </div>
                </div>
              </div>
            </div>
          </div>
        `;
        document.body.insertAdjacentHTML('beforeend', modalHtml);
        modalElement = document.getElementById(modalId);
    }
    
    const modalInstance = bootstrap.Modal.getOrCreateInstance(modalElement);
    modalInstance.show();
    
    const confirmButton = document.getElementById(`${modalId}ConfirmButton`);
    confirmButton.onclick = () => {
        modalInstance.hide();
        
        let idPenyelia = document.getElementById('modallayanan').getAttribute('data-penyelia');
        showLoading();
        fetch("<?= site_url('pengelolaPenyelia/tambahLayananPenyelia') ?>", {
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
                sayAlert('successModal', 'Berhasil', data.msg ?? 'Layanan berhasil ditambahkan.', 'success');
                
                // Refresh tabel utama
                if (typeof table !== 'undefined') {
                    table.fetchData({ reload: true });
                }
                
                // Refresh data modal tanpa recreate tabel
                if (layananTable && typeof layananTable.fetchData === 'function') {
                    layananTable.fetchData({ reload: true });
                }
            } else {
                sayAlert('errorModal', 'Gagal', data.msg ?? 'Gagal menambahkan layanan.', 'warning');
            }
        })
        .catch(err => {
            sayAlert('errorModal', 'Error', 'Terjadi kesalahan: ' + err.message, 'warning');
        })
        .finally(() => hideLoading());
    };
}


function pilihLayanan(idLayanan) {
    tambahlayanan(idLayanan);
}
</script>

<?= view('Modules\pengelolaPenyelia\Views\v_modalManajemenLayanan') ?>
