<!-- Modal Tambah dengan Preview di Bawah -->
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
                                <th style="width:5%">No</th>
                                <th style="width:15%">Parameter</th>
                                <th style="width:15%">Instrumen/Alat/Tempat</th>
                                <th style="width:12%">Biaya</th>
                                <th style="width:12%">Jumlah</th>
                                <th style="width:18%">Keterangan</th>
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
                        <button type="button" id="btnRefreshKeranjang" class="btn btn-sm btn-outline-secondary">
                            <i class="bi bi-arrow-clockwise"></i> Refresh
                        </button>
                    </div>

                    <div id="keranjangKosong" class="alert alert-warning text-center" style="display:none;">
                        <i class="bi bi-cart-x"></i> Keranjang masih kosong. Silakan pilih layanan di atas.
                    </div>
                    
                    <table id="preview-keranjang-table" class="saytable border-top-bottom">
                        <thead>
                            <tr>
                                <th style="width:5%">No</th>
                                <th style="width:20%">Layanan</th>
                                <th style="width:12%">Biaya Satuan</th>
                                <th style="width:8%">Jumlah</th>
                                <th style="width:10%">Diskon (%)</th>
                                <th style="width:15%">Total</th>
                                <th style="width:20%">Keterangan</th>
                                <th style="width:10%" class="text-center">Aksi</th>
                            </tr>
                        </thead>
                        <tbody id="preview-keranjang-table-body"></tbody>
                        <tfoot>
                            <tr class="table-active">
                                <td colspan="5" class="text-end fw-bold">TOTAL KESELURUHAN:</td>
                                <td id="grandTotal" class="fw-bold text-primary fs-5">Rp 0</td>
                                <td colspan="2"></td>
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

<!-- (HTML modal tidak perlu banyak diubah; hanya pastikan kolom "Jumlah" tetap ada) -->
<!-- ... gunakan modal HTML yang sudah ada ... -->

<script>
// Variabel untuk table layanan di modal
let layananTable;
let previewKeranjangTable;

// Inisialisasi table layanan saat modal dibuka
document.getElementById('modalForm').addEventListener('shown.bs.modal', function () {
    if (!layananTable) {
        layananTable = createModal({
            apiUrl: '<?= site_url("keranjang/dataListLayanan") ?>',
            tableId: 'layanan-table',
            showFilter: true,
            treeview: false,
            numbering: true,
            itemsPerPage: 10
        });
    } else {
        layananTable.fetchData({ reload: true });
    }

    if (!previewKeranjangTable) {
        previewKeranjangTable = createModal({
            apiUrl: '<?= site_url("keranjang/datalist") ?>',
            tableId: 'preview-keranjang-table',
            showFilter: false,
            treeview: false,
            numbering: true,
            itemsPerPage: 100
        });
    } else {
        previewKeranjangTable.fetchData({ reload: true });
    }
    
    // Update counter dan grand total
    setTimeout(function() {
        updateKeranjangCounter();
        calculateGrandTotal();
    }, 500);
});

// Fungsi untuk update jumlah item di badge
function updateKeranjangCounter() {
    fetch('<?= site_url("keranjang/datalist") ?>')
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
    fetch('<?= site_url("keranjang/datalist") ?>')
        .then(res => res.json())
        .then(data => {
            if (data.items && data.items.length > 0) {
                let grandTotal = 0;
                data.items.forEach(item => {
                    const totalStr = item[5]; // Kolom Total (index 5)
                    if (totalStr) {
                        const totalNum = parseFloat(totalStr.replace(/[^0-9,-]/g, '').replace(',', '.'));
                        if (!isNaN(totalNum)) {
                            grandTotal += totalNum;
                        }
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
    // Tombol masukkan (tetap ada)
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
            url: "<?= site_url('keranjang/submit') ?>",
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
    
    // Tombol Refresh Keranjang
    if (e.target.closest('#btnRefreshKeranjang')) {
        e.preventDefault();
        if (previewKeranjangTable) {
            previewKeranjangTable.fetchData({ reload: true });
            setTimeout(function() {
                updateKeranjangCounter();
                calculateGrandTotal();
            }, 500);
        }
    }
    
    // Tombol Checkout dari Modal
    if (e.target.closest('#btnCheckoutFromModal')) {
        e.preventDefault();
        
        if (confirm('Apakah Anda yakin ingin melakukan checkout?')) {
            doCheckout();
        }
    }
});

// Fungsi untuk hapus item dari preview keranjang (tetap sama)
function deleteItemFromPreview(e) {
    e.preventDefault();
    let idx = e.currentTarget.getAttribute("data-index");

    if (!confirm("Apakah Anda yakin ingin menghapus item ini dari keranjang?")) return;

    fetch("<?= site_url('keranjang/delete/') ?>" + idx)
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

// Helper function untuk checkout (tetap sama)
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
