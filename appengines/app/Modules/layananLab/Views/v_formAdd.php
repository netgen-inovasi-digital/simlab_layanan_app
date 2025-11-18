<!-- Modal Form -->
<div class="modal fade" id="modalForm" data-bs-backdrop="static" data-bs-keyboard="false" tabindex="-1" aria-labelledby="staticBackdropLabel" aria-hidden="true">
    <div class="modal-dialog modal-xl" role="document">
        <div class="modal-content">
            <div class="modal-header">
                <h5 class="modal-title">TAMBAH DATA</h5>
                <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
            </div>
            <?php echo form_open('layananLab/submit', array('id' => 'myform', 'novalidate' => '')) ?>
            <div class="modal-body p-4">
                <input type="hidden" value="" name="id" />
                <div class="row">
                    <div class="col-md-6 border-end">
                        <p class="text-muted small fw-bold">KLASIFIKASI LAYANAN</p>
                        <div class="mb-3">
                            <label class="form-label">Jenis Layanan</label>
                            <select name="kode_jenis" class="form-select" required>
                                <option value="">-- Pilih Jenis --</option>
                            </select>
                        </div>
                        <div class="mb-3">
                            <label class="form-label">Alat</label>
                            <select name="kode_alat" class="form-select" required>
                                <option value="">-- Pilih Alat --</option>
                            </select>
                        </div>
                        <div class="mb-3">
                            <label class="form-label">Parameter</label>
                            <select name="kode_parameter" class="form-select" required>
                                <option value="">-- Pilih Parameter --</option>
                            </select>
                        </div>
                    </div>
                    <div class="col-md-6">
                        <p class="text-muted small fw-bold">DETAIL LAYANAN</p>
                        <div class="mb-3">
                            <label class="form-label">Nama Layanan</label>
                            <input name="nama_layanan" type="text" class="form-control" required placeholder="Masukkan nama layanan">
                        </div>
                        <div class="row">
                            <div class="col-sm-7">
                                <div class="mb-3">
                                    <label class="form-label">Biaya</label>
                                    <div class="input-group">
                                        <span class="input-group-text">Rp</span>
                                        <input name="biaya" type="number" class="form-control" required placeholder="Masukkan biaya">
                                    </div>
                                </div>
                            </div>
                            <div class="col-sm-5">
                                <div class="mb-3">
                                    <label class="form-label">Satuan</label>
                                    <input name="satuan" type="text" class="form-control" required placeholder="Sampel/Jam/Ruangan">
                                </div>
                            </div>
                        </div>
                        <div class="mb-3">
                            <label class="form-label">Diskon (%)</label>
                            <input name="diskon" type="number" class="form-control" min="0" max="100" placeholder="Masukkan diskon">
                        </div>
                    </div>
                </div>
                <hr class="my-4">
                <div class="row">
                    <div class="col-12">
                        <p class="text-muted small fw-bold mb-3">TIM PENANGGUNG JAWAB</p>
                    </div>
                </div>

                <!-- Penyelia Section -->
                <div class="row mb-4">
                    <div class="col-md-6">
                        <label class="form-label fw-bold">PENYELIA </label>
                        <select id="selectPenyelia" class="form-select">
                            <option value="">[ Pilih Penyelia ... ]</option>
                        </select>
                        <div class="table-responsive border rounded mt-3">
                            <table class="table table-sm table-hover mb-0">
                                <thead class="table-light">
                                    <tr>
                                        <th width="10%">No.</th>
                                        <th width="70%">Username</th>
                                        <th width="20%" class="text-center">Aksi</th>
                                    </tr>
                                </thead>
                                <tbody id="tablePenyelia">
                                    <tr class="text-muted text-center">
                                        <td colspan="3"><em>Belum ada penyelia dipilih</em></td>
                                    </tr>
                                </tbody>
                            </table>
                        </div>
                        <p class="small text-muted mt-1 mb-0">(Pilih satu atau lebih penyelia)</p>
                    </div>

                    <!-- Manajer Teknis Section -->
                    <div class="col-md-6">
                        <label class="form-label fw-bold">MANAJER TEKNIS</label>
                        <select id="selectManajer" class="form-select">
                            <option value="">[Pilih Manajer Teknis ... ]</option>
                        </select>
                        <div class="table-responsive border rounded mt-3">
                            <table class="table table-sm table-hover mb-0">
                                <thead class="table-light">
                                    <tr>
                                        <th width="10%">No.</th>
                                        <th width="70%">Username</th>
                                        <th width="20%" class="text-center">Aksi</th>
                                    </tr>
                                </thead>
                                <tbody id="tableManajer">
                                    <tr class="text-muted text-center">
                                        <td colspan="3"><em>Belum ada manajer teknis dipilih</em></td>
                                    </tr>
                                </tbody>
                            </table>
                        </div>
                        <p class="small text-muted mt-1 mb-0">(Pilih satu atau lebih manajer teknis)</p>
                    </div>
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