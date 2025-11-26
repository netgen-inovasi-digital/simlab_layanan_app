<div class="modal fade" id="modalForm" data-bs-backdrop="static" data-bs-keyboard="false" tabindex="-1" aria-labelledby="staticBackdropLabel" aria-hidden="true">
    <div class="modal-dialog modal-lg" role="document">
        <div class="modal-content">
            <div class="modal-header">
                <h5 class="modal-title">Tambah Data Metode Baru</h5>
                <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
            </div>
            
            <?php echo form_open('metode/submit', array('id' => 'myform', 'novalidate' => '')) ?>
            <div class="modal-body p-4">
                <input type="hidden" value="" name="id" />

                <div class="mb-3">
                    <label for="namaInput" class="form-label fw-bold">Nama Metode</label>
                    <input name="nama" type="text" class="form-control" id="namaInput" required placeholder="Contoh : SNI 06-6989.11-2004">
                    <div class="form-text">Masukkan nama metode pengujian</div>
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