<div class="modal fade" id="modalForm" data-bs-backdrop="static" data-bs-keyboard="false" tabindex="-1" aria-labelledby="staticBackdropLabel" aria-hidden="true">
    <div class="modal-dialog modal-lg" role="document">
        <div class="modal-content">
            <div class="modal-header">
                <h5 class="modal-title">Tambah Data Persentase</h5>
                <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
            </div>
            
            <?php echo form_open('persentase/submit', array('id' => 'myform', 'novalidate' => '')) ?>
            <div class="modal-body p-4">
                <input type="hidden" value="" name="id" />

                <div class="mb-3">
                    <label class="form-label fw-bold">Jenis Layanan</label>
                    <select name="jenis_kode" class="form-select" required>
                        <option value="">-- Pilih Jenis Layanan --</option>
                        <?php if (isset($jenis) && !empty($jenis)) : ?>
                            <?php foreach ($jenis as $j) : ?>
                                <option value="<?php echo $j->kode; ?>">
                                    <?php echo $j->kode . ' - ' . $j->nama; ?>
                                </option>
                            <?php endforeach; ?>
                        <?php endif; ?>
                    </select>
                    <div class="form-text">Pilih kategori utama untuk persentase biaya ini.</div>
                </div>

                <div class="mb-3">
                    <label class="form-label fw-bold">Nama Jenis Biaya</label>
                    <input name="label" type="text" class="form-control" required placeholder="Contoh: Jasa Sarana atau Operasional">
                    <div class="form-text">Masukkan nama komponen biaya yang akan dihitung.</div>
                </div>

                <div class="mb-3">
                    <label class="form-label fw-bold">Persentase</label>
                    <div class="input-group">
                        <input name="non_ulm" type="number" class="form-control" required placeholder="Contoh : 40">
                        <span class="input-group-text">%</span>
                    </div>
                    <div class="form-text">Masukkan angkanya saja, tanpa simbol persen (%).</div>
                </div>

            </div>
            <div class="modal-footer">
                <button class="btn btn-light" type="button" data-bs-dismiss="modal"><i class="bi bi-x-circle"></i> Batal</button>
                <button class="btn btn-primary" id="btnSimpan" type="submit"><i class="bi bi-check2-circle"></i> Simpan</button>
            </div>
            </form>
        </div>
    </div>
</div>