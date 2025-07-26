<div class="row">
    <div class="col-md-12">
        <div class="card">
            <div class="card-header">
                <label class="card-title mb-0"><?php echo $title ?></label>
            </div>
            <div class="card-body">
                <?php echo form_open('konfigurasi/submit', array('id' => 'myform', 'novalidate' => '')) ?>
                <div class="row">
                    <div class="col-md-4 border-end">
                        <div class="text-center py-4">
                            <?php
                            $logo = "https://placehold.co/150";
                            if ($get && $get->logo != "")
                                $logo = base_url('uploads/' . $get->logo);
                            ?>
                            <img src="<?= esc($logo) ?>" width="150" class="img-thumbnail mb-3" id="previewImg" alt="Logo">
                            <h5 class="fw-bold"><?= ($get) ? esc($get->nama_profil) : '' ?></h5>
                            <p><?= ($get) ? esc($get->email) : '' ?></p>
                            <input type="file" class="form-control mt-3" name="logo" id="uploadFoto" accept="image/*">
                        </div>
                        <div class="col mb-3">
                            <label class="form-label">Nama Profil</label>
                            <input type="text" class="form-control" name="nama" placeholder="Nama Profil" value="<?= ($get) ? esc($get->nama_profil) : '' ?>">
                        </div>
                        <div class="col mb-3">
                            <label class="form-label">No. Telepon</label>
                            <input type="text" class="form-control" name="telepon" placeholder="0812xxxxxxx" value="<?= ($get) ? esc($get->telepon) : '' ?>">
                        </div>
                        <div class="col">
                            <label class="form-label">Email</label>
                            <input type="email" class="form-control" name="email" placeholder="Email" value="<?= ($get) ? esc($get->email) : '' ?>">
                        </div>
                    </div>
                    <div class="col-md-8 p-4">
                        <div class="row mb-3">
                            <div class="col-md-6">
                                <label class="form-label">Deskripsi Singkat</label>
                                <textarea class="form-control" name="deskripsi" rows="3" placeholder="Tuliskan deskripsi singkat tentang profil"><?= ($get) ? esc($get->deskripsi) : '' ?></textarea>
                            </div>
                            <div class="col-md-6">
                                <label class="form-label">Halaman Selengkapnya</label>
                                <div class="col">
                                    <select name="link" class="form-select" required>
                                        <option value="tidak ada">-- pilih data --</option>
                                        <?php foreach ($getHalaman as $halaman): ?>
                                            <option
                                                value="<?= esc($halaman->slug) ?>"
                                                data-nama="<?= esc($halaman->title) ?>"
                                                <?= (isset($get->link) && $get->link == $halaman->slug) ? 'selected' : '' ?>>
                                                <?= esc($halaman->title) ?>
                                            </option>
                                        <?php endforeach; ?>
                                    </select>

                                </div>
                            </div>
                        </div>
                        <div class="mb-3">
                            <label class="form-label">Alamat</label>
                            <textarea class="form-control" name="alamat" rows="2" placeholder="Masukkan alamat"><?= ($get) ? esc($get->alamat) : '' ?></textarea>
                        </div>
                        <div class="row mb-3">
                            <div class="col-md-6">
                                <label class="form-label">Kota</label>
                                <input type="text" class="form-control" name="kota" placeholder="Kota" value="<?= ($get) ? esc($get->kota) : '' ?>">
                            </div>
                            <div class="col-md-6">
                                <label class="form-label">Provinsi</label>
                                <input type="text" class="form-control" name="provinsi" placeholder="Provinsi" value="<?= ($get) ? esc($get->provinsi) : '' ?>">
                            </div>
                        </div>
                        <div class="mb-4">
                            <label class="form-label">Embed Peta Lokasi</label>
                            <textarea class="form-control" name="peta" rows="4" placeholder="Masukkan embed kode Google Maps (iframe HTML)"><?= ($get) ? esc($get->peta) : '' ?></textarea>
                            <small class="text-muted">Contoh: &lt;iframe src="..." width="100%" height="250" style="border:0;" allowfullscreen="" loading="lazy"&gt;&lt;/iframe&gt;</small>
                        </div>

                        <div>
                            <button type="submit" class="btn btn-success px-4"><i class="bi bi-check2-circle"></i> Simpan</button>
                        </div>
                    </div>
                </div>
                <?php echo form_close() ?>
            </div>

        </div>
    </div>
</div>

<script>
    $('#myform').submit();
    document.getElementById("uploadFoto").addEventListener("change", function(event) {
        const file = event.target.files[0];
        const preview = document.getElementById("previewImg");
        if (file) preview.src = URL.createObjectURL(file);
    });
</script>