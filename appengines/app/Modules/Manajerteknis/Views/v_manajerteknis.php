<div class="row">
    <div class="col-md-12">
        <div class="card">
            <div class="card-header d-flex justify-content-between align-items-center">
                <label class="card-title mb-0"><?php echo $title ?></label>
                <button id="add" class="btn btn-primary">
                    <i class="bi bi-plus-circle-dotted"></i> Tambah
                </button>
            </div>
            <div class="card-body">
                <table id="data-table" class="saytable border-top-bottom">
                    <thead>
                        <tr>
                            <th show width="8%">No.</th>
                            <th show>Username</th>
                            <th show>Layanan yang dikelola</th>
                            <th show>Nama</th>
                            <th>Status</th>
                            <th show class="action text-end">Aksi</th>
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
        apiUrl: '<?php echo site_url("manajerteknis/datalist") ?>',
    });
    addAction();

    var modal = document.getElementById('modalForm');
    modal.addEventListener('shown.bs.modal', function (e) {
        const pwd = document.querySelector('[name="password"]');
        pwd.value = "";
        const id = document.querySelector('[name="id"]').value;
        if (id == "") pwd.setAttribute('required', true);
        else pwd.removeAttribute('required');
    });

    // === Menampilkan layanan yang dikelola Manajer Teknis + Pagination ===
    let modalLayananInstance = null;

    function lihatLayanan(id, page = 1) {
        fetch("<?php echo site_url('manajerteknis/layanan') ?>/" + id + "?page=" + page)
            .then(res => res.json())
            .then(data => {
                let body = document.getElementById('layanan-body');
                let paginationContainer = document.getElementById('layanan-pagination');
                body.innerHTML = "";
                paginationContainer.innerHTML = "";

                if (data.res === 'ok' && data.items.length > 0) {
                    data.items.forEach(item => {
                        let idLayanan = item.aksi.match(/hapusLayanan\('(.+?)'\)/)[1];
                        body.innerHTML += `
                            <tr>
                                <td>${item.no}</td>
                                <td>${item.nama}</td>
                                <td>
                                    <div class="float-end">
                                        <span class="text-danger btn-action" title="Hapus" onclick="hapusLayanan('${idLayanan}')">
                                            <i class="bi bi-trash"></i> Hapus
                                        </span>
                                    </div>
                                </td>
                            </tr>`;
                    });

                    let currentPage = data.pagination.page;
                    let totalPages = data.pagination.total_pages;

                    paginationContainer.innerHTML += `
                        <li class="page-item ${currentPage === 1 ? 'disabled' : ''}">
                            <a class="page-link" href="javascript:void(0)" onclick="lihatLayanan('${id}', ${currentPage - 1})">
                                <i class="bi bi-chevron-left"></i>
                            </a>
                        </li>`;

                    for (let i = 1; i <= totalPages; i++) {
                        paginationContainer.innerHTML += `
                            <li class="page-item ${i === currentPage ? 'active' : ''}">
                                <a class="page-link" href="javascript:void(0)" onclick="lihatLayanan('${id}', ${i})">${i}</a>
                            </li>`;
                    }

                    paginationContainer.innerHTML += `
                        <li class="page-item ${currentPage === totalPages ? 'disabled' : ''}">
                            <a class="page-link" href="javascript:void(0)" onclick="lihatLayanan('${id}', ${currentPage + 1})">
                                <i class="bi bi-chevron-right"></i>
                            </a>
                        </li>`;
                } else {
                    body.innerHTML = `<tr><td colspan="3" class="text-center"><i>Tidak ada layanan</i></td></tr>`;
                }

                if (!modalLayananInstance) {
                    modalLayananInstance = new bootstrap.Modal(document.getElementById('modalLayanan'));
                }
                document.getElementById('modalLayanan').setAttribute('data-manajerteknis', id);
                modalLayananInstance.show();
            })
            .catch(error => console.error('Error fetch layanan:', error));
    }

    function hapusLayanan(id) {
        if (!id) return;
        if (!confirm("Yakin ingin menghapus layanan ini?")) return;

        fetch("<?php echo site_url('manajerteknis/deleteLayanan') ?>/" + id)
            .then(res => res.json())
            .then(data => {
                if (data.res === 'ok') {
                    alert("Layanan berhasil dihapus!");
                    document.querySelector('.modal.show .btn-close').click();
                    table.refresh();
                } else {
                    alert("Gagal menghapus layanan.");
                }
            })
            .catch(error => console.error('Error hapus layanan:', error));
    }

    // === Modal Tambah Layanan dengan Search ===
    let modalTambahLayananInstance = new bootstrap.Modal(document.getElementById('modalTambahLayanan'));
    let searchLayananTerm = "";

    function tambahLayanan(page = 1) {
        fetch("<?= site_url('manajerteknis/layananKosong') ?>?page=" + page + "&limit=10&search=" + encodeURIComponent(searchLayananTerm))
            .then(res => res.json())
            .then(data => {
                let body = document.getElementById('layanan-kosong-body');
                let paginationContainer = document.getElementById('layanan-kosong-pagination');
                body.innerHTML = "";
                paginationContainer.innerHTML = "";

                if (data.res === 'ok' && data.items.length > 0) {
                    data.items.forEach(item => {
                        body.innerHTML += `
                            <tr>
                                <td>${item.no}</td>
                                <td>${item.nama}</td>
                                <td>${item.aksi}</td>
                            </tr>`;
                    });

                    let currentPage = data.pagination.page;
                    let totalPages = data.pagination.total_pages;

                    paginationContainer.innerHTML += `
                        <li class="page-item ${currentPage === 1 ? 'disabled' : ''}">
                            <a class="page-link" href="javascript:void(0)" onclick="tambahLayanan(${currentPage - 1})">
                                <i class="bi bi-chevron-left"></i>
                            </a>
                        </li>`;

                    for (let i = 1; i <= totalPages; i++) {
                        paginationContainer.innerHTML += `
                            <li class="page-item ${i === currentPage ? 'active' : ''}">
                                <a class="page-link" href="javascript:void(0)" onclick="tambahLayanan(${i})">${i}</a>
                            </li>`;
                    }

                    paginationContainer.innerHTML += `
                        <li class="page-item ${currentPage === totalPages ? 'disabled' : ''}">
                            <a class="page-link" href="javascript:void(0)" onclick="tambahLayanan(${currentPage + 1})">
                                <i class="bi bi-chevron-right"></i>
                            </a>
                        </li>`;
                } else {
                    body.innerHTML = `<tr><td colspan="3" class="text-center"><i>Tidak ada layanan kosong</i></td></tr>`;
                }

                modalTambahLayananInstance.show();
            })
            .catch(err => console.error(err));
    }

    // === Event Search Tambah Layanan ===
    document.addEventListener('DOMContentLoaded', function() {
        const searchInput = document.getElementById('search-layanan-input');
        if (searchInput) {
            searchInput.addEventListener('input', function(e) {
                searchLayananTerm = e.target.value; // langsung passing ke backend
                tambahLayanan(1);
            });
        }
    });

    function pilihLayanan(idLayanan) {
        if (!confirm("Yakin ingin menambahkan layanan ini ke manajer teknis?")) return;

        let idManajerTeknisEnc = document.getElementById('modalLayanan').getAttribute('data-manajerteknis');
        if (!idManajerTeknisEnc) return alert("Manajer Teknis belum diketahui.");

        fetch("<?= site_url('manajerteknis/tambahLayananManajer') ?>", {
            method: 'POST',
            headers: {'Content-Type': 'application/json', 'X-Requested-With':'XMLHttpRequest'},
            body: JSON.stringify({layanan: idLayanan, manajerteknis: idManajerTeknisEnc, '<?= csrf_token() ?>':'<?= csrf_hash() ?>'})
        })
        .then(res => res.json())
        .then(data => {
            if (data.res === 'ok') {
                alert("Layanan berhasil ditambahkan!");
                document.getElementById('modalTambahLayanan').querySelector('.btn-close').click();
                lihatLayanan(idManajerTeknisEnc);
            } else {
                alert("Gagal menambahkan layanan.");
            }
        });
    }
</script>

<!-- Modal Form Manajer Teknis -->
<div class="modal fade" id="modalForm" data-bs-backdrop="static" data-bs-keyboard="false" tabindex="-1">
    <div class="modal-dialog" role="document" style="margin: 2% auto">
        <div class="modal-content">
            <div class="modal-header">
                <h5 class="modal-title">Data Manajer Teknis</h5>
                <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
            </div>
            
            <?php echo form_open('manajerteknis/submit', ['id' => 'myform', 'novalidate' => '']) ?>
                <div class="modal-body">
                    <input type="hidden" value="" name="id"/>

                    <!-- Username -->
                    <div class="row mb-2">
                        <label class="col-md-4 col-form-label">Username</label>
                        <div class="col">
                            <input name="username" type="text" class="form-control" required>
                        </div>
                    </div>

                    <!-- Nama -->
                    <div class="row mb-2">
                        <label class="col-md-4 col-form-label">Nama</label>
                        <div class="col">
                            <input name="nama" type="text" class="form-control" required>
                        </div>
                    </div>

                    <!-- Password -->
                    <div class="row mb-2">
                        <label class="col-md-4 col-form-label">Password</label>
                        <div class="col">
                            <input name="password" type="password" class="form-control">
                        </div>
                    </div>

                    <!-- Status -->
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

<!-- Modal Detail Layanan -->
<div class="modal fade" id="modalLayanan" tabindex="-1">
  <div class="modal-dialog modal-lg">
    <div class="modal-content">
      <div class="modal-header">
        <h5 class="modal-title">Layanan yang Dikelola</h5>
        <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
      </div>
      <div class="modal-body">
        <div class="d-flex justify-content-end mb-2">
            <button class="btn btn-success btn-sm" onclick="tambahLayanan()">
                <i class="bi bi-plus-circle"></i> Tambah Layanan
            </button>
        </div>
        <table class="table table-sm table-bordered mb-2">
          <thead>
            <tr>
             <th width="8%">No.</th>
             <th width="80%">Nama Layanan</th>
             <th width="12%">Aksi</th>
            </tr>
          </thead>
          <tbody id="layanan-body"></tbody>
        </table>
      </div>
      <div class="modal-footer flex-column">
        <nav class="w-100 mb-2">
            <ul id="layanan-pagination" class="pagination pagination-sm justify-content-center mb-0"></ul>
        </nav>
        <div class="w-100 d-flex justify-content-end">
            <button class="btn btn-secondary" data-bs-dismiss="modal">Tutup</button>
        </div>
      </div>
    </div>
  </div>
</div>

<!-- Modal Tambah Layanan -->
<div class="modal fade" id="modalTambahLayanan" tabindex="-1">
  <div class="modal-dialog modal-lg">
    <div class="modal-content">
      <div class="modal-header">
        <h5 class="modal-title">Pilih Layanan</h5>
        <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
      </div>
      <div class="modal-body">
        <div class="d-flex justify-content-end mb-2">
            <input type="text" id="search-layanan-input" class="form-control form-control-sm w-auto" placeholder="Cari layanan...">
        </div>
        <table class="table table-sm table-bordered">
          <thead>
            <tr>
              <th width="8%">No.</th>
              <th width="80%">Nama Layanan</th>
              <th width="12%">Aksi</th>
            </tr>
          </thead>
          <tbody id="layanan-kosong-body"></tbody>
        </table>
      </div>
      <div class="modal-footer flex-column">
        <nav class="w-100 mb-2">
            <ul id="layanan-kosong-pagination" class="pagination pagination-sm justify-content-center mb-0"></ul>
        </nav>
        <div class="w-100 d-flex justify-content-end">
            <button class="btn btn-secondary" data-bs-dismiss="modal">Tutup</button>
        </div>
      </div>
    </div>
  </div>
</div>

<!-- Script lama dipertahankan untuk fallback -->
<script>
function lihatLayananSimple(id) {
    fetch("<?= site_url('manajerteknis/layanan') ?>/" + id)
        .then(res => res.json())
        .then(data => {
            let body = document.getElementById('layanan-body');
            body.innerHTML = "";

            if (data.res === 'ok' && data.items.length > 0) {
            data.items.forEach(item => {
                body.innerHTML += `
                    <tr>
                        <td>${item.no}</td>
                        <td>${item.nama}</td>
                        <td>${item.aksi}</td>
                    </tr>
                `;
            });

            } else {
                body.innerHTML = `<tr><td colspan="3" class="text-center"><i>Tidak ada layanan</i></td></tr>`;
            }

            var modal = new bootstrap.Modal(document.getElementById('modalLayanan'));
            modal.show();
        });
}
</script>
