<div class="row">
    <div class="col-md-12">
        <div class="card">
            <div class="card-header d-flex justify-content-between align-items-center">
                <label class="card-title mb-0"><?php echo $title ?></label>
                <div class="d-flex gap-2">
                    <select id="filterKategori" class="form-select">
                        <option value="">-- Semua Kategori --</option>
                        <?php foreach ($kategori as $row): ?>
                            <option value="<?= $row->jenisKode ?>"><?= $row->jenisNama ?></option>
                        <?php endforeach; ?>
                    </select>
                    <input type="text" id="searchParam" class="form-control" placeholder="Cari parameter / instrumen...">
                </div>
            </div>
            <div class="card-body">
                <table id="data-table" class="saytable border-top-bottom">
                    <thead>
                        <tr>
                            <th show width="5%">No.</th>
                            <th show>Parameter</th>
                            <th show>Instrumen / Alat</th>
                            <th show width="15%">Biaya</th>
                            <th show width="10%">Jumlah</th>
                            <th show width="20%">Keterangan</th>
                            <th show class="action text-end">Aksi</th>
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
    // === init datatable ===
    table = createTable({
        apiUrl: '<?php echo site_url("pelayanan/list") ?>',
    });
    addAction();

    // === filter kategori ===
    document.querySelector('#filterKategori').addEventListener('change', function () {
        const kategori = this.value;
        table.fetchData({ kategori: kategori });
    });

    // === search parameter / instrumen ===
    document.querySelector('#searchParam').addEventListener('keyup', function () {
        const keyword = this.value;
        table.fetchData({ search: keyword });
    });

    // === aksi masukkan ke keranjang ===
    function addToCart(ujiKode) {
        const jumlah = document.querySelector('#jumlah_' + ujiKode).value;
        const keterangan = document.querySelector('#ket_' + ujiKode).value;

        const formData = new FormData();
        formData.append('ujiKode', ujiKode);
        formData.append('jumlah', jumlah);
        formData.append('keterangan', keterangan);

        saveData({
            url: '<?php echo site_url("keranjang/add") ?>',
            formData: formData,
            onSuccess: function (data) {
                if (data.res === true) {
                    sayAlert('successModal', 'Berhasil', 'Layanan berhasil dimasukkan ke keranjang.', 'success');
                }
            }
        });
    }

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
</script>
