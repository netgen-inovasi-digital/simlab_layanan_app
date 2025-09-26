<!-- Modal Tambah -->
<div class="modal fade" id="modalForm" data-bs-backdrop="static" data-bs-keyboard="false" tabindex="-1"
    aria-labelledby="staticBackdropLabel" aria-hidden="true">
    <div class="modal-dialog modal-xl" role="document" style="margin: 2% auto">
        <div class="modal-content">
            <div class="modal-header">
                <h5 class="modal-title">Pilih Layanan</h5>
                <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
            </div>
            <div class="modal-body">
                <table class="table table-bordered table-hover">
                    <thead class="table-light">
                        <tr>
                            <th style="width:5%">No</th>
                            <th style="width:10%">Kode Uji</th>
                            <th style="width:15%">Parameter</th>
                            <th style="width:15%">Instrumen/Alat/Tempat</th>
                            <th style="width:12%">Biaya</th>
                            <th style="width:12%">Jumlah</th>
                            <th style="width:18%">Keterangan</th>
                            <th style="width:5%" class="text-center">Aksi</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php if(isset($listUji)): $no=1; foreach($listUji as $row): ?>
                        <tr>
                            <td><?= $no++ ?></td>
                            <td><?= $row->ujiKode ?></td>
                            <td><?= $row->paraNama ?></td>
                            <td><?= $row->alatNama ?></td>
                            <td>
                                Rp <?= number_format($row->ujiBiaya,0,',','.') ?>
                                <?php if (!empty($row->ujiDiskon) && $row->ujiDiskon > 0): ?>
                                    <span class="text-danger fw-bold"> - <?= $row->ujiDiskon ?>%</span>
                                <?php endif; ?>
                            </td>
                            <td>
                                <div class="input-group input-group-sm">
                                    <button type="button" class="btn btn-outline-secondary btnKurang">-</button>
                                    <input type="number" class="form-control text-center jumlah" value="0" min="1">
                                    <button type="button" class="btn btn-outline-secondary btnTambah">+</button>
                                </div>
                            </td>
                            <td>
                                <input type="text" class="form-control form-control-sm keterangan" placeholder="Keterangan...">
                            </td>
                            <td class="text-center">
                                <button type="button" 
                                        class="btn btn-success btn-sm btnMasukkan" 
                                        data-kode="<?= $row->ujiKode ?>" 
                                        data-alat="<?= $row->alatNama ?>" 
                                        data-biaya="<?= $row->ujiBiaya ?>" 
                                        data-parameter="<?= $row->paraNama ?>"
                                        data-diskon="<?= $row->ujiDiskon ?? 0 ?>" 
                                        data-instansi="<?= $row->ujiInstansi ?>" 
                                        title="Masukkan ke keranjang">
                                    <i class="bi bi-cart-plus"></i>
                                </button>
                            </td>
                        </tr>
                        <?php endforeach; else: ?>
                        <tr>
                            <td colspan="8" class="text-center text-muted">Data layanan pengujian belum tersedia.</td>
                        </tr>
                        <?php endif; ?>
                    </tbody>
                </table>
            </div>
            <div class="modal-footer">
                <input type="hidden" name="<?= csrf_token() ?>" value="<?= csrf_hash() ?>">
                <button class="btn btn-light" type="button" data-bs-dismiss="modal">
                    <i class="bi bi-x-circle"></i> Tutup
                </button>
            </div>
        </div>
    </div>
</div>

<script>
document.addEventListener('click', function(e) {
    // tombol tambah jumlah
    if (e.target.classList.contains('btnTambah')) {
        let input = e.target.closest('.input-group').querySelector('.jumlah');
        input.value = parseInt(input.value) + 1;
    }

    // tombol kurang jumlah
    if (e.target.classList.contains('btnKurang')) {
        let input = e.target.closest('.input-group').querySelector('.jumlah');
        if (parseInt(input.value) > 1) input.value = parseInt(input.value) - 1;
    }

    // tombol masukkan
    if (e.target.closest('.btnMasukkan')) {
        let btn = e.target.closest('.btnMasukkan');
        let tr = btn.closest('tr');

        // --- Ambil data dari baris yang diklik ---
        let biaya   = parseFloat(btn.dataset.biaya) || 0;
        let diskon  = parseFloat(btn.dataset.diskon) || 0;
        let jumlah  = parseInt(tr.querySelector('.jumlah').value) || 1;

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
            detTotal: total   // 👈 tambahan
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
});
</script>
