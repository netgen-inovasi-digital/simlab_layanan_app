<div class="modal fade" id="modalForm" data-bs-backdrop="static" data-bs-keyboard="false" tabindex="-1"
    aria-hidden="true">
    <div class="modal-dialog modal-lg" role="document" style="margin: 2% auto">
        <div class="modal-content">
            <div class="modal-header">
                <h5 class="modal-title">Form Alat</h5>
                <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close">
                </button>
            </div>
            <?php echo form_open('alat/submit', array('id' => 'myform', 'novalidate' => '')) ?>
            <div class="modal-body p-4">
                <input type="hidden" value="" name="id" />

                <div class="mb-3">
                    <label for="alatKodeInput" class="form-label fw-bold">Kode Alat</label>
                    <input name="kode" type="text" class="form-control" id="alatKodeInput" required
                        placeholder="Contoh : DISTILASI">
                    <div class="form-text"></div>
                </div>

                <div class="mb-3">
                    <label for="alatNamaInput" class="form-label fw-bold">Nama Alat</label>
                    <input name="nama" type="text" class="form-control" id="alatNamaInput" required
                        placeholder="Contoh : Parameter Distalasi">
                    <div class="form-text"></div>
                </div>
            </div>
            <div class="modal-footer">
                <button class="btn btn-light" type="button" data-bs-dismiss="modal"><i class="bi bi-x-circle"></i>
                    Batal</button>
                <button class="btn btn-success" id="btnSimpan" type="submit"><i class="bi bi-check2-circle"></i>
                    Simpan</button>
            </div>
            </form>
        </div>
    </div>
</div>