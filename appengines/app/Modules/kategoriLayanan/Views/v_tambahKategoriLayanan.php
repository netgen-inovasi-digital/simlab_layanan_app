<div class="modal fade" id="modalForm" data-bs-backdrop="static" data-bs-keyboard="false" tabindex="-1" aria-labelledby="staticBackdropLabel" aria-hidden="true">
    <div class="modal-dialog modal-lg" role="document">
        <div class="modal-content">
            <div class="modal-header">
                <h5 class="modal-title">Tambah Data Kategori Layanan Baru</h5>
                <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
            </div>
            
            <?php echo form_open('kategoriLayanan/submit', array('id' => 'myform', 'novalidate' => '')) ?>
            <div class="modal-body p-4">
                <input type="hidden" value="" name="id" />

                <div class="mb-3">
                    <label for="jenKodeInput" class="form-label fw-bold">Kode Kategori</label>
                    <input name="jenKode" type="text" class="form-control" id="jenKodeInput" required placeholder="Contoh : MKB">
                    <div class="form-text"></div>
                </div>

                <div class="mb-3">
                    <label for="jenNamaInput" class="form-label fw-bold">Nama Kategori</label>
                    <input name="jenNama" type="text" class="form-control" id="jenNamaInput" required placeholder="Contoh : Mikrobiologi">
                    <div class="form-text"></div>
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