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
                <table id="data-table" class="saytable border-top-bottom table table-hover table-sm">
                    <thead>
                        <tr>
                            <th show width="5%">No.</th>
                            <th show width="15%">No. transaksi</th>
                            <th show width="15%">Status pesanan</th>
                            <th show width="15%">Status pembayaran</th>
                            <th show width="15%">File LHU</th>
                            <th show class="action text-center">Detail pesanan</th>
                        </tr>
                    </thead>
                    <tbody id="table-body">
                    </tbody>
                </table>
            </div>
        </div>
    </div>
</div>


<script>
    // Buat table dengan helper createTable (helper ini diasumsikan sudah ada di project)
    table = createTable({
        apiUrl: '<?php echo site_url("pelayanan/datalist") ?>',
        onData: function(items) {
            // render manual ke tbody agar fleksibel
            const tbody = document.querySelector('#table-body');
            tbody.innerHTML = '';
            if (!items || !items.length) {
                tbody.innerHTML = '<tr><td colspan="4" class="text-center">Tidak ada data</td></tr>';
                return;
            }

            items.forEach(function(row) {
                const tr = document.createElement('tr');
                // row diharapkan berupa array kolom sesuai controller
                row.forEach(function(colHtml) {
                    const td = document.createElement('td');
                    td.innerHTML = colHtml;
                    tr.appendChild(td);
                });

                tbody.appendChild(tr);
            });
        }
    });

    addAction();

    document.querySelector('#add').addEventListener('click', function () {
        fetch('<?php echo site_url("pelayanan/checkVerified") ?>', {
            method: 'GET',
            headers: { 'Accept': 'application/json' }
        })
        .then(r => r.json())
        .then(data => {
            if (data.verified) {
                loadContent('<?php echo site_url("keranjang") ?>');
            } else {
                sayAlert('warningModal', 'Verifikasi Diperlukan', 'Akun anda belum diverifikasi. Silakan lengkapi data di halaman profil.', 'warning');
                setTimeout(() => {
                    loadContent('<?php echo site_url("profilpw") ?>');
                }, 1500);
            }
        })
        .catch(err => {
            console.error(err);
            sayAlert('errorModal', 'Error', 'Gagal memeriksa status verifikasi.', 'warning');
        });
    });


    function reloadTable() {
        const tbody = document.querySelector('#table-body');
        tbody.innerHTML = '';
        if (typeof table !== 'undefined') {
            table.fetchData({ reload: true });
        }
    }
        
    
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
                    reloadTable();
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
                    reloadTable();
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

    function loadDetail(id) {
    const url = '<?php echo site_url("pelayanan/detailList/") ?>' + id;
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
          $('#modalForm').modal('show');
        })
        .catch(error => {
            console.error(error);
            tbody.innerHTML = '<tr><td colspan="7" class="text-center text-danger">Error load data</td></tr>';
           $('#modalForm').modal('show');
        });
}

</script>

<div class="modal fade" id="modalForm" data-bs-backdrop="static" data-bs-keyboard="false" tabindex="-1" aria-labelledby="staticBackdropLabel" aria-hidden="true">
    <div class="modal-dialog modal-xl" role="document" style="margin: 2% auto">
        <div class="modal-content">
            <div class="modal-header">
                <h5 class="modal-title">Detail Layanan</h5>
                <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close">
                </button>
            </div>
            <div class="modal-body">
                <table class="table table-bordered">
                    <thead>
                        <tr>
                            <th width="5%">No</th>
                            <th width="30%">Layanan</th>
                            <th width="20%">Biaya</th>
                            <th width="15%">Jumlah</th>
                            <th width="15%">Keterangan</th>
                            <th width="15%">Status</th>
                        </tr>
                    </thead>
                    <tbody id="detail-body">
                        <tr><td colspan="6" class="text-center">Loading...</td></tr>
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