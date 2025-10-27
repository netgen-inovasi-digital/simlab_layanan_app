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

<!-- Modal Detail Pesanan -->
<div class="modal fade" id="modalDetail" data-bs-backdrop="static" data-bs-keyboard="false" tabindex="-1" aria-labelledby="staticBackdropLabel" aria-hidden="true">
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
                            <th width="30%">Parameter</th>
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

<!-- Modal Keranjang (Pesan Layanan Baru) -->
<div class="modal fade" id="modalForm" data-bs-backdrop="static" data-bs-keyboard="false" tabindex="-1"
    aria-labelledby="staticBackdropLabel" aria-hidden="true">
    <div class="modal-dialog modal-xl" role="document" style="margin: 2% auto">
        <div class="modal-content">
            <div class="modal-header">
                <h5 class="modal-title">Pilih Layanan</h5>
                <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
            </div>
            <div class="modal-body">
                <!-- CSRF Token -->
                <input type="hidden" name="<?= csrf_token() ?>" value="<?= csrf_hash() ?>" />
                
                <!-- Tabel Pilih Layanan -->
                <div class="mb-4">
                    <h6 class="fw-bold text-primary mb-3">
                        <i class="bi bi-list-check"></i> Daftar Layanan Tersedia
                    </h6>
                    <table id="layanan-table" class="saytable border-top-bottom">
                        <thead>
                            <tr>
                                <th width="5%">No</th>
                                <th width="25%">Parameter</th>
                                <th width="25%">Instrumen/Alat/Tempat</th>
                                <th width="15%">Biaya</th>
                                <th width="5%">Jumlah</th>
                                <th width="20%">Keterangan</th>
                                <th style="width:5%" class="text-center">Aksi</th>
                            </tr>
                        </thead>
                        <tbody id="layanan-table-body"></tbody>
                    </table>
                </div>

                <hr class="my-4">

                <!-- Tabel Preview Keranjang -->
                <div>
                    <div class="d-flex justify-content-between align-items-center mb-3">
                        <h6 class="fw-bold text-success mb-0">
                            <i class="bi bi-cart3"></i> Keranjang Anda 
                            (<span id="jumlahItemKeranjang">0</span> Item)
                        </h6>
                        <!-- <button type="button" id="btnRefreshKeranjang" class="btn btn-sm btn-outline-secondary">
                            <i class="bi bi-arrow-clockwise"></i> Refresh
                        </button> -->
                    </div>

                    <div id="keranjangKosong" class="alert alert-warning text-center" style="display:none;">
                        <i class="bi bi-cart-x"></i> Keranjang masih kosong. Silakan pilih layanan di atas.
                    </div>
                    
                    <table id="preview-keranjang-table" class="saytable border-top-bottom">
                        <thead>
                            <tr>
                                <th width="5%">No</th>
                                <th width="22%">Parameter</th>
                                <th width="22%">Instrumen/Alat/Tempat</th>
                                <th width="10%">Diskon</th>
                                <th width="15%">Biaya</th>
                                <th width="5%">Jumlah</th>
                                <th width="25%">Keterangan</th>
                                <th style="width:10%" class="text-center">Aksi</th>
                            </tr>
                        </thead>
                        <tbody id="preview-keranjang-table-body"></tbody>
                    <tfoot>
                        <tr class="table-active align-middle">
                            <td colspan="8">
                                <div class="d-flex justify-content-end">
                                    <div class="fw-bold fs-5">
                                        TOTAL KESELURUHAN:
                                        <span id="grandTotal" class="text-primary">Rp 0</span>
                                    </div>
                                </div>
                            </td>
                        </tr>
                    </tfoot>
    
                    </table>
                </div>
            </div>
            <div class="modal-footer">
                <button class="btn btn-light" type="button" data-bs-dismiss="modal">
                    <i class="bi bi-x-circle"></i> Tutup
                </button>
                <button id="btnCheckoutFromModal" class="btn btn-success" disabled>
                    <i class="bi bi-cart-check"></i> Checkout Sekarang
                </button>
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

    // Event handler untuk tombol "Pesan Layanan Baru"
    document.querySelector('#add').addEventListener('click', function () {
        fetch('<?php echo site_url("pelayanan/checkVerified") ?>', {
            method: 'GET',
            headers: { 'Accept': 'application/json' }
        })
        .then(r => r.json())
        .then(data => {
            if (data.verified) {
                // Buka modal keranjang langsung
                const modalForm = new bootstrap.Modal(document.getElementById('modalForm'));
                modalForm.show();
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
                $('#modalDetail').modal('show');
            })
            .catch(error => {
                console.error(error);
                tbody.innerHTML = '<tr><td colspan="7" class="text-center text-danger">Error load data</td></tr>';
                $('#modalDetail').modal('show');
            });
    }

    // ========== KODE KERANJANG FORM ==========
    
    // Variabel untuk table layanan di modal
    let layananTable;
    let previewKeranjangTable;

    // Inisialisasi table layanan saat modal dibuka
    document.getElementById('modalForm').addEventListener('shown.bs.modal', function () {
        if (!layananTable) {
            layananTable = createModal({
                apiUrl: '<?= site_url("pelayanan/keranjang/dataListLayanan") ?>',
                tableId: 'layanan-table',
            });
        } else {
            layananTable.fetchData({ reload: true });
        }

       if (!previewKeranjangTable) {
            previewKeranjangTable = createModal({
                apiUrl: '<?= site_url("pelayanan/keranjang/datalist") ?>',
                tableId: 'preview-keranjang-table',
                showFilter: false,
                treeview: true,
                numbering: true,
                itemsPerPage: 10
            });
        } else {
            previewKeranjangTable.fetchData({ reload: true });
        }

        
        setTimeout(function() {
            updateKeranjangCounter();
            calculateGrandTotal();
        }, 500);
    });

    // Fungsi untuk update jumlah item di badge
    function updateKeranjangCounter() {
        fetch('<?= site_url("pelayanan/keranjang/datalist") ?>')
            .then(res => res.json())
            .then(data => {
                const jumlahItem = data.items ? data.items.length : 0;
                document.getElementById('jumlahItemKeranjang').textContent = jumlahItem;
                
                const keranjangKosong = document.getElementById('keranjangKosong');
                const previewTable = document.getElementById('preview-keranjang-table');
                const btnCheckout = document.getElementById('btnCheckoutFromModal');
                
                if (jumlahItem === 0) {
                    keranjangKosong.style.display = 'block';
                    previewTable.style.display = 'none';
                    btnCheckout.disabled = true;
                } else {
                    keranjangKosong.style.display = 'none';
                    previewTable.style.display = 'table';
                    btnCheckout.disabled = false;
                }
            })
            .catch(err => {
                console.error('Error updating counter:', err);
            });
    }

    // Fungsi untuk menghitung grand total
    function calculateGrandTotal() {
    fetch('<?= site_url("pelayanan/keranjang/datalist") ?>')
        .then(res => res.json())
        .then(data => {
            if (data.items && data.items.length > 0) {
                let grandTotal = 0;

                data.items.forEach(item => {
                    // item adalah array kolom; kolom aksi berisi hidden span .row-total
                    // jadi kita cari elemen yang mengandung "row-total" dengan cara parsing HTML
                    let totalStr = null;
                    for (let i = 0; i < item.length; i++) {
                        if (typeof item[i] === 'string' && item[i].indexOf('row-total') !== -1) {
                            const tmp = document.createElement('div');
                            tmp.innerHTML = item[i];
                            const rt = tmp.querySelector('.row-total');
                            if (rt) {
                                totalStr = rt.textContent || rt.innerText || null;
                                break;
                            }
                        }
                    }

                    // fallback: kalau tidak ketemu row-total, cari kolom terakhir yang memiliki 'Rp'
                    if (!totalStr) {
                        for (let i = item.length - 1; i >= 0; i--) {
                            if (typeof item[i] === 'string' && item[i].indexOf('Rp') !== -1) {
                                const tmp2 = document.createElement('div');
                                tmp2.innerHTML = item[i];
                                totalStr = (tmp2.textContent || tmp2.innerText || '').trim();
                                break;
                            }
                        }
                    }

                    if (totalStr) {
                        let cleaned = totalStr.replace(/[^0-9,.-]/g, '');
                        cleaned = cleaned.replace(/\./g, '').replace(/,/g, '.');
                        const totalNum = parseFloat(cleaned);
                        if (!isNaN(totalNum)) grandTotal += totalNum;
                    }
                });

                document.getElementById('grandTotal').textContent =
                    'Rp ' + new Intl.NumberFormat('id-ID').format(grandTotal);
            } else {
                document.getElementById('grandTotal').textContent = 'Rp 0';
            }
        })
        .catch(err => {
            console.error('Error calculating grand total:', err);
        });
}


    // Event delegation untuk tombol-tombol di dalam modal
    document.addEventListener('click', function(e) {
        // Tombol masukkan
        if (e.target.closest('.btnMasukkan')) {
            let btn = e.target.closest('.btnMasukkan');
            let tr = btn.closest('tr');

            // Ambil data dari baris yang diklik
            let biaya   = parseFloat(btn.dataset.biaya) || 0;
            let diskon  = parseFloat(btn.dataset.diskon) || 0;

            // Ambil nilai jumlah dari input number (default 1)
            let jumlahInput = tr.querySelector('.jumlah');
            let jumlah  = parseInt(jumlahInput ? jumlahInput.value : 1) || 1;
            if (jumlah < 1) jumlah = 1;

            // Hitung total harga setelah diskon
            let total = (biaya * jumlah) * (1 - (diskon / 100));

            let data = {
                detUjiKode: btn.dataset.kode,
                detAlat: btn.dataset.alat,
                detBiaya: biaya,
                detParameter: btn.dataset.parameter,
                detDiskon: diskon,
                detInstansi: btn.dataset.instansi || '',
                detJumlah: jumlah,
                detKeterangan: tr.querySelector('.keterangan').value,
                detTotal: total
            };

            // Validasi data sebelum kirim
            if (!data.detUjiKode) {
                sayAlert('errorModal', 'Gagal', 'Kode Uji tidak ditemukan.', 'error');
                return;
            }
            if (parseInt(data.detJumlah) < 1) {
                sayAlert('errorModal', 'Gagal', 'Jumlah minimal 1.', 'error');
                return;
            }

            // Siapkan FormData
            let formData = new FormData();
            for (const key in data) {
                formData.append(key, data[key]);
            }

            // Sertakan CSRF Token
            let csrfInput = document.querySelector('input[name="<?= csrf_token() ?>"]');
            if (csrfInput) {
                formData.append('<?= csrf_token() ?>', csrfInput.value);
            }

            // AJAX untuk simpan ke keranjang
            saveData({
                   url: "<?= site_url('pelayanan/keranjang/submit') ?>",
                formData: formData,
                onSuccess: function(res) {
                    if (res.xname && res.xhash) {
                        let csrfField = document.querySelector('input[name="' + res.xname + '"]');
                        if (csrfField) {
                            csrfField.value = res.xhash;
                        }
                    }

                    if (res.res === true) {
                        if (typeof table !== 'undefined') {
                            table.fetchData({ reload: true });
                        }
                        
                        if (previewKeranjangTable) {
                            previewKeranjangTable.fetchData({ reload: true });
                            setTimeout(function() {
                                updateKeranjangCounter();
                                calculateGrandTotal();
                            }, 500);
                        }
                        
                        // Reset form di baris yang bersangkutan -> jumlah kembali ke 1
                        if (jumlahInput) jumlahInput.value = 1;
                        tr.querySelector('.keterangan').value = '';
                        
                        sayAlert('successModal', 'Berhasil', res.msg ?? 'Layanan berhasil ditambahkan ke keranjang.', 'success');
                    } else {
                        sayAlert('errorModal', 'Gagal', res.msg ?? 'Terjadi kesalahan saat menambahkan ke keranjang.', 'error');
                    }
                },
                onError: function() {
                    sayAlert('errorModal', 'Gagal', 'Terjadi kesalahan koneksi ke server.', 'error');
                }
            });
        }
        
      
        
        // Tombol Checkout dari Modal
        if (e.target.closest('#btnCheckoutFromModal')) {
            e.preventDefault();
            
            if (confirm('Apakah Anda yakin ingin melakukan checkout?')) {
                doCheckout();
            }
        }
    });

   // Fungsi untuk hapus item dari preview keranjang — robust terhadap event atau element
function deleteItemFromPreview(eOrEl) {
    // jika pemanggilan lewat onclick="deleteItemFromPreview(this)" maka eOrEl adalah elemen
    // jika lewat onclick="deleteItemFromPreview(event)" atau addEventListener, maka eOrEl instanceof Event
    let el;
    if (eOrEl instanceof Event) {
        eOrEl.preventDefault();
        // prefer currentTarget, fallback ke target
        el = eOrEl.currentTarget || eOrEl.target;
    } else {
        // dianggap elemen DOM
        el = eOrEl;
    }

    // jika elemen adalah ikon <i> atau anak, cari parent dengan data-index
    if (el && !el.hasAttribute('data-index')) {
        el = el.closest('[data-index]');
    }

    if (!el) {
        console.warn('Element untuk delete tidak ditemukan.');
        return;
    }

    const idx = el.getAttribute('data-index');
    if (!idx) {
        console.warn('Data-index tidak ditemukan pada element hapus.');
        return;
    }

    fetch("<?= site_url('pelayanan/keranjang/delete/') ?>" + idx)
        .then(res => res.json())
        .then(data => {
            if (data.xname && data.xhash) {
                document.querySelectorAll('[name="' + data.xname + '"]').forEach(input => {
                    input.value = data.xhash;
                });
            }

            if (data.res === true) {
                if (typeof table !== 'undefined') {
                    table.fetchData({ reload: true });
                }

                if (previewKeranjangTable) {
                    previewKeranjangTable.fetchData({ reload: true });
                    setTimeout(function() {
                        updateKeranjangCounter();
                        calculateGrandTotal();
                    }, 500);
                }

                sayAlert('successModal', 'Sukses', data.msg, 'success');
            } else {
                sayAlert('errorModal', 'Gagal', data.msg ?? 'Hapus item gagal.', 'error');
            }
        })
        .catch(err => {
            sayAlert('errorModal', 'Error', 'Terjadi kesalahan koneksi ke server.', 'error');
        });
}


    // Helper function untuk checkout
    function doCheckout() {
        const formData = new FormData();
        const csrfInput = document.querySelector('input[name="<?= csrf_token() ?>"]');
        if (csrfInput) {
            formData.append('<?= csrf_token() ?>', csrfInput.value);
        }

        fetch('<?= site_url("pelayanan/keranjang/checkout") ?>',{
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
                
                if (previewKeranjangTable) {
                    previewKeranjangTable.fetchData({ reload: true });
                    setTimeout(function() {
                        updateKeranjangCounter();
                        calculateGrandTotal();
                    }, 500);
                }
                
                const modalForm = bootstrap.Modal.getInstance(document.getElementById('modalForm'));
                if (modalForm) modalForm.hide();
                
                sayAlert('successModal', 'Sukses', data.msg, 'success');
            } else {
                sayAlert('errorModal', 'Gagal', data.msg ?? 'Checkout gagal.', 'error');
            }
        })
        .catch(error => {
            sayAlert('errorModal', 'Error', 'Terjadi kesalahan koneksi ke server.', 'error');
        });
    }
</script>