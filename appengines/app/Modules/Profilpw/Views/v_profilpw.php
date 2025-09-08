<div class="row">
    <div class="col-md-12">
        <div class="card">
            <div class="card-header">
                <label class="card-title mb-0"><?php echo $title ?></label>
            </div>
            <div class="card-body">
                <?php echo form_open('profilpw/submit', array('id'=>'myform', 'novalidate'=>'')) ?>
                <div class="row">
                    <div class="col-md-4 border-end">
                        <div class="text-center py-4">
                            <?php 
                                $foto = "https://placehold.co/150";
                                if($get && isset($get->foto) && $get->foto != "")
                                    $foto = base_url('uploads/'.$get->foto);
                            ?>
                            <img src="<?= esc($foto) ?>" width="150" class="img-thumbnail mb-3" id="previewImg" alt="Logo">
                            <h5 class="fw-bold">
                                <i class="bi bi-person-check text-secondary"></i> 
                                <?= ($get && isset($get->user_name)) ? esc($get->user_name) : '' ?>
                            </h5>
                            <input type="file" class="form-control mt-3" name="foto" id="uploadFoto" accept="image/*">
                        </div>
                    </div>
                    <div class="col-md-8 p-4">

                        <div class="row mb-3">
                            <div class="col-md-12">
                                <label class="form-label">Email</label>
                                <input type="email" class="form-control" name="email" placeholder="Email" 
                                    value="<?= ($get && isset($get->user_email)) ? esc($get->user_email) : '' ?>" readonly>
                            </div>
                        </div>

                        <div class="mb-3">
                            <label class="form-label">Password Lama</label>
                            <input type="password" class="form-control" name="old_password" placeholder="Masukkan Password Lama">
                        </div>

                        <div class="mb-3">
                            <label class="form-label">Password Baru</label>
                            <input type="password" class="form-control" name="new_password" placeholder="Masukkan Password Baru">
                        </div>

                        <div class="mb-3">
                            <label class="form-label">Ulangi Password Baru</label>
                            <input type="password" class="form-control" name="confirm_password" placeholder="Ulangi Password Baru">
                        </div>

                        <div class="text-end">
                            <button type="submit" class="btn btn-success px-4">
                                <i class="bi bi-check2-circle"></i> Simpan
                            </button>
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
