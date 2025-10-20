<div class="row">
    <div class="col-md-12">
        <div class="card">
            <div class="card-header d-flex justify-content-between align-items-center">
                <label class="card-title mb-0"><?php echo $title ?></label>
                <div>
                    <button id="checkout" class="btn btn-success">
                        <i class="bi bi-cart-check"></i> Checkout
                    </button>
                    <button id="add" class="btn btn-primary" data-bs-toggle="modal" data-bs-target="#modalForm">
                        <i class="bi bi-plus-circle-dotted"></i> Tambah
                    </button>
                </div>
            </div>
            <div class="card-body">
                <!-- CSRF Token -->
                <input type="hidden" name="<?= csrf_token() ?>" value="<?= csrf_hash() ?>" />

                <table id="data-table" class="saytable border-top-bottom">
                    <thead>
                        <tr>
                            <th width="5%">No.</th>
                            <th>Layanan</th>
                            <th>Biaya</th>
                            <th>Jumlah</th>
                            <th>Diskon (%)</th>
                            <th>Total</th>
                            <th>Keterangan</th>
                            <th class="text-end">Aksi</th>
                        </tr>
                    </thead>
                    <tbody id="table-body"></tbody>
                </table>
            </div>
        </div>
    </div>
</div>

<!-- Load Form Tambah (modal) -->
<?php echo $this->include('Modules\Keranjang\Views\v_keranjang_form'); ?>

<script>
    // Buat table ajax
    table = createTable({
        apiUrl: '<?php echo site_url("keranjang/datalist") ?>',
    });
    addAction();

    // === Simpan data ke session === //
    const btnSimpan = document.querySelector('#btnSimpan');
    if (btnSimpan) {
        btnSimpan.addEventListener('click', function(e) {
            e.preventDefault();
            const form = document.querySelector('#myform');
            const formData = new FormData(form);
            const actionUrl = form.getAttribute('action');

            // Ambil nilai input
            let biaya   = parseFloat(document.querySelector('#detBiaya').value) || 0;
            let jumlah  = parseInt(document.querySelector('#detJumlah').value) || 1;
            let diskon  = parseFloat(document.querySelector('#detDiskon').value) || 0; // ambil dari input form
            if(diskon > 100) diskon = 100; // batas maksimal diskon

            // Hitung total setelah diskon
            let subtotal = biaya * jumlah;
            let total = subtotal - (subtotal * (diskon / 100));

            formData.append('detBiaya', biaya);
            formData.append('detJumlah', jumlah);
            formData.append('detDiskon', diskon);
            formData.append('detTotal', total);

            // Sertakan CSRF token ke FormData
            const csrfInput = document.querySelector('input[name="<?= csrf_token() ?>"]');
            if (csrfInput) {
                formData.append('<?= csrf_token() ?>', csrfInput.value);
            }

            saveData({
                url: actionUrl,
                formData: formData,
                onSuccess: function(data) {
                    if (data.xname && data.xhash) {
                        document.querySelectorAll('[name="' + data.xname + '"]').forEach(input => {
                            input.value = data.xhash;
                        });
                    }

                    if (data.res === true) {
                        if (typeof table !== 'undefined') table.fetchData({ reload: true });
                        sayAlert('successModal', 'Berhasil', 'Layanan berhasil ditambahkan ke keranjang.', 'success');

                        if ($('#modalForm').hasClass('show')) $('#modalForm').modal('hide');
                    } else {
                        sayAlert('errorModal', 'Gagal', data.msg ?? 'Terjadi kesalahan.', 'error');
                    }
                },
                onError: function(error) {
                    sayAlert('errorModal', 'Error', 'Terjadi kesalahan pada sistem.', 'warning');
                }
            });
        });
    }

    // === Checkout === //
    document.addEventListener('click', function(e) {
        if (e.target.closest('#checkout')) {
            e.preventDefault();
            doCheckout();
        }
    });

    function doCheckout() {
        const formData = new FormData();
        const csrfInput = document.querySelector('input[name="<?= csrf_token() ?>"]');
        if (csrfInput) {
            formData.append('<?= csrf_token() ?>', csrfInput.value);
        }

        fetch('<?= site_url("keranjang/checkout") ?>', {
            method: 'POST',
            body: formData
        })
        .then(res => res.json())
        .then(data => {
            if (data.xname && data.xhash) {
                document.querySelectorAll('[name="' + data.xname + '"]').forEach(input => {
                    input.value = data.xhash;
                });
            }

            if (data.res === true) {
                if (typeof table !== 'undefined') table.fetchData({ reload: true });
                sayAlert('successModal', 'Sukses', data.msg, 'success');
            } else {
                sayAlert('errorModal', 'Gagal', data.msg ?? 'Checkout gagal.', 'error');
            }
        })
        .catch(error => {
            sayAlert('errorModal', 'Error', 'Terjadi kesalahan koneksi ke server.', 'error');
        });
    }

    // === Save Ajax helper === //
    function saveData({ url, formData, onSuccess, onError }) {
        showLoading();
        fetch(url, {
            method: 'POST',
            body: formData
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

    // === Ubah biaya otomatis saat pilih parameter === //
    document.addEventListener('change', function(e){
        if(e.target && e.target.id === 'detUjiKode'){
            let biaya = e.target.options[e.target.selectedIndex].dataset.biaya || 0;
            let diskon = e.target.options[e.target.selectedIndex].dataset.diskon || 0;
            document.querySelector('#detBiaya').value = biaya;
            document.querySelector('#detDiskon').value = diskon;
            let alatNama = e.target.options[e.target.selectedIndex].dataset.alat;
            document.querySelector('#detAlat').value = alatNama;
        }
    });

    // === Tombol tambah/kurang jumlah === //
    document.addEventListener('click', function(e){
        if(e.target.id === 'btnTambah'){
            let input = document.querySelector('#detJumlah');
            input.value = parseInt(input.value) + 1;
        }
        if(e.target.id === 'btnKurang'){
            let input = document.querySelector('#detJumlah');
            if(parseInt(input.value) > 1) input.value = parseInt(input.value) - 1;
        }
    });

    // === Hapus item dari keranjang dengan konfirmasi === //
    function deleteItem(e) {
        e.preventDefault();
        let idx = e.currentTarget.getAttribute("data-index");

        if (!confirm("Apakah Anda yakin ingin menghapus item ini?")) return;

        fetch("<?php echo site_url('keranjang/delete/') ?>" + idx)
            .then(res => res.json())
            .then(data => {
                if (data.xname && data.xhash) {
                    document.querySelectorAll('[name="' + data.xname + '"]').forEach(input => {
                        input.value = data.xhash;
                    });
                }

                if (data.res === true) {
                    if (typeof table !== 'undefined') table.fetchData({ reload: true });
                    sayAlert('successModal', 'Sukses', data.msg, 'success');
                } else {
                    sayAlert('errorModal', 'Gagal', data.msg ?? 'Hapus item gagal.', 'error');
                }
            })
            .catch(err => {
                sayAlert('errorModal', 'Error', 'Terjadi kesalahan koneksi ke server.', 'error');
            });
    }

    console.log("Script keranjang.js loaded");
</script>
