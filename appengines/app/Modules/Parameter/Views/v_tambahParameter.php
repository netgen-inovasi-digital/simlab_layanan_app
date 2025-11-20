<div class="modal fade" id="modalForm" data-bs-backdrop="static" data-bs-keyboard="false" tabindex="-1" aria-labelledby="staticBackdropLabel" aria-hidden="true">
    <div class="modal-dialog modal-lg" role="document">
        <div class="modal-content">
            <div class="modal-header">
                <h5 class="modal-title">Tambah Data Parameter Baru</h5>
                <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
            </div>
            
            <?php echo form_open('parameter/submit', array('id' => 'myform', 'novalidate' => '')) ?>
            <div class="modal-body p-4">
                <input type="hidden" value="" name="id" />

                <div class="mb-3">
                    <label for="paraKodeInput" class="form-label fw-bold">Kode Parameter</label>
                    <input name="paraKode" type="text" class="form-control" id="paraKodeInput" required placeholder="Contoh : AS">
                    <div class="form-text"></div>
                </div>

                <div class="mb-3">
                    <label for="paraNamaInput" class="form-label fw-bold">Nama Parameter</label>
                    <input name="paraNama" type="text" class="form-control" id="paraNamaInput" required placeholder="Contoh : As (Arsen)">
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