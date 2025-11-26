<div class="register-form-container">
    <h5 class="fw-bold mb-4 text-center">Daftar Pengguna</h5>

    <?php $validation = service('validation'); ?>
    <?php if ($validation->getErrors()) : ?>
        <div class="alert alert-danger" role="alert">
            <strong>Ditemukan Kesalahan Validasi :</strong>
            <ul>
            <?php foreach ($validation->getErrors() as $error) : ?>
                <li><?= esc($error) ?></li>
            <?php endforeach ?>
            </ul>
        </div>
    <?php endif ?>

     <!-- Flash message -->
    <?php if(session()->getFlashdata('error')): ?>
        <div class="alert alert-danger">
            <?= session()->getFlashdata('error') ?>
        </div>
    <?php endif; ?>

    <?php if(session()->getFlashdata('success')): ?>
        <div class="alert alert-success">
            <?= session()->getFlashdata('success') ?>
        </div>
    <?php endif; ?>
    
      <?= form_open_multipart('register/auth', ['id' => 'register-form', 'method' => 'post'   ]) ?>
      
      <?= csrf_field() ?>
    
        <div class="mb-3">
            <input name="nama" type="text" class="form-control rounded-pill mx-auto" placeholder="Nama Lengkap" value="<?= old('nama') ?>" required />
        </div>
        <div class="mb-3">
            <input name="email" type="email" class="form-control rounded-pill mx-auto" placeholder="Email" value="<?= old('email') ?>" required />
        </div>
        <div class="mb-3 position-relative">
            <input name="pwd" id="reg-password" type="password" 
                   class="form-control rounded-pill mx-auto pe-5" 
                   placeholder="Password" required style="padding-right: 45px;" />
            <i id="toggleRegPassword" class="bi bi-eye position-absolute fs-5 toggle-eye"></i>
        </div>

        <div class="mb-3 position-relative">
            <input name="repwd" id="reg-repassword" type="password" 
                   class="form-control rounded-pill mx-auto pe-5" 
                   placeholder="Ulangi Password" required style="padding-right: 45px;" />
            <i id="toggleRegRePassword" class="bi bi-eye position-absolute fs-5 toggle-eye"></i>
        </div>

    
    <!-- <div class="mb-3 text-center">
        <div class="form-check form-check-inline">
            <input class="form-check-input" type="radio" name="status_mahasiswa" id="radioNonUlm" value="Non-ULM" <?= old('status_mahasiswa', 'Non-ULM') == 'Non-ULM' ? 'checked' : '' ?>>
            <label class="form-check-label" for="radioNonUlm">Nonsdf-ULM</label>
        </div>
        <div class="form-check form-check-inline">
            <input class="form-check-input" type="radio" name="status_mahasiswa" id="radioUlm" value="ULM" <?= old('status_mahasiswa') == 'ULM' ? 'checked' : '' ?>>
            <label class="form-check-label" for="radioUlm">ULM</label>
        </div>
    </div> -->

    <!-- <div class="mb-3" id="ktm-upload-field" style="display: <?= old('status_mahasiswa') == 'ULM' ? 'block' : 'none' ?>;">
        <label for="ktm_image" class="form-label small d-block text-center">Upload Foto KTM/Screenshot SIMARI</label>
        <input name="ktm_image" class="form-control" type="file" id="ktm_image" accept="image/png, image/jpeg, image/jpg">
    </div> -->

    <div class="d-grid">
        <button type="submit" class="btn btn-primary rounded-pill mx-auto">DAFTAR</button>
    </div>
    <?= form_close(); ?>

</div>
<script>
    document.addEventListener('DOMContentLoaded', function() {
        function setupPasswordToggle(inputId, toggleId) {
            const toggleBtn = document.getElementById(toggleId);
            const inputField = document.getElementById(inputId);

            if (toggleBtn && inputField) {
                toggleBtn.addEventListener('click', () => {
                    const isPassword = inputField.type === 'password';
                    inputField.type = isPassword ? 'text' : 'password';
                    
                    toggleBtn.classList.toggle('bi-eye');
                    toggleBtn.classList.toggle('bi-eye-slash');
                    toggleBtn.style.opacity = isPassword ? '1' : '0.7';
                });
            }
        }

        setupPasswordToggle('reg-password', 'toggleRegPassword');

        setupPasswordToggle('reg-repassword', 'toggleRegRePassword');
    });
</script>