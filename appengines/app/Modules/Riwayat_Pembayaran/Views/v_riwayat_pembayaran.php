<div class="row">
    <div class="col-md-12">
        <div class="card">
            <div class="card-header d-flex justify-content-between align-items-center">
                <label class="card-title mb-0"><?php echo $title ?></label>
            </div>

            <div class="card-body">
                <div class="row mb-3">
                    <div class="col-12">
                        <div class="card border-0 bg-light">
                            <div class="card-body py-3">
                                <label class="form-label fw-bold mb-3">Filter Tanggal Layanan</label>
                                <div class="row g-2 align-items-end">
                                    <div class="col-md-3">
                                        <label for="awal" class="form-label small">Tanggal Awal</label>
                                        <input type="date" id="awal" class="form-control">
                                    </div>
                                    <div class="col-md-3">
                                        <label for="akhir" class="form-label small">Tanggal Akhir</label>
                                        <input type="date" id="akhir" class="form-control">
                                    </div>
                                    <div class="col-md-2">
                                        <button type="button" class="btn btn-primary w-100 tampil">
                                            <i class="bi bi-search me-1"></i>Tampilkan
                                        </button>
                                    </div>
                                    <div class="col-md-2">
                                        <button type="button" class="btn btn-outline-secondary w-90 tampilSemua">
                                            <i class="bi bi-arrow-clockwise me-1"></i>Reset
                                        </button>
                                    </div>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>

                <div class="row">
                    <div class="col-12">
                        <table id="data-table" class="table table-hover">
                            <thead>
                                <tr>
                                    <th show width="4%">No.</th>
                                    <th show width="15%">No. Invoice</th>
                                    <th show width="25%">Pemesan</th>
                                    <th show width="12%">Tagihan</th>
                                    <th show width="12%">Invoice</th>
                                    <th show width="12%">Bukti Bayar</th>
                                    <th show width="10%">Status</th>
                                    <th show class="action text-end">Aksi<i class="bi bi-code sort-icon"></i></th>
                                </tr>
                            </thead>
                            <tbody id="table-body">
                            </tbody>
                        </table>
                    </div>
                </div>
            </div>
        </div>
    </div>


    <script>
        table = createTable({
            apiUrl: '<?php echo site_url("riwayat_pembayaran/datalist") ?>',
        });
        addAction();

        // Handle filter tanggal
        document.querySelector('.tampil').addEventListener('click', function(e) {
            e.preventDefault();
            
            const tanggalAwal = document.getElementById('awal').value;
            const tanggalAkhir = document.getElementById('akhir').value;
            
            if (!tanggalAwal || !tanggalAkhir) {
                sayAlert('errorModal', 'Error', 'Mohon pilih tanggal awal dan akhir', 'warning');
                return;
            }
            
            if (tanggalAwal > tanggalAkhir) {
                sayAlert('errorModal', 'Error', 'Tanggal awal tidak boleh lebih besar dari tanggal akhir', 'warning');
                return;
            }
            
            // Update table dengan filter
            if (typeof table !== 'undefined') {
                table.ajax.url('<?php echo site_url("riwayat_pembayaran/datalist") ?>?tanggal_awal=' + tanggalAwal + '&tanggal_akhir=' + tanggalAkhir).load();
            }
        });

        // Handle reset filter
        document.querySelector('.tampilSemua').addEventListener('click', function(e) {
            e.preventDefault();
            
            // Clear input fields
            document.getElementById('awal').value = '';
            document.getElementById('akhir').value = '';
            
            // Reset table to original data
            if (typeof table !== 'undefined') {
                table.ajax.url('<?php echo site_url("riwayat_pembayaran/datalist") ?>').load();
            }
        });

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
                        // Refresh table dengan menggunakan ajax reload
                        if (typeof table !== 'undefined') {
                            table.ajax.reload();
                        }
                        sayAlert('successModal', 'Berhasil', 'Data berhasil disimpan.', 'success');
                        if ($('#modalForm').hasClass('show')) $('#modalForm').modal('hide');
                    }
                }
            });
        });

        function saveData({
            url,
            formData,
            onSuccess,
            onError
        }) {
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
                        if (typeof table !== 'undefined') table.fetchData({
                            reload: true
                        });
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
    </script>
</div>