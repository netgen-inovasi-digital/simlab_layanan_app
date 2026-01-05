<?= $this->extend('auth/auth_layout') ?>


<?= $this->section('title') ?>
Reset Password
<?= $this->endSection() ?>

<?= $this->section('content') ?>
<div class="row g-0">

  <!-- Form Section -->
  <div class="col-md-6 bg-white p-5">
    <h5 class="fw-bold mb-4 text-center">Reset Password</h5>
    <?php foreach (['success', 'error', 'msg'] as $type): ?>
      <?php if (session()->getFlashdata($type)): ?>
        <blockquote
          class="blockquote custom-blockquote bg-light mb-3 text-center text-<?= $type == 'error' ? 'danger' : 'success' ?> small rounded">
          <span><?= session()->getFlashdata($type) ?></span>
          <span class="ms-3">
            <i class="bi <?= $type == 'error' ? 'bi-x-circle' : 'bi-check-circle' ?>"></i>
          </span>
        </blockquote>
      <?php endif; ?>
    <?php endforeach; ?>
    <!--LOGIN_PAGE_MARKER-->
    <?php if (session()->getFlashdata('success')): ?>
      <div id="success-message" class="alert alert-success text-center rounded-pill">
        <?= session()->getFlashdata('success') ?>
      </div>

      <div class="text-center mt-3">
        <a href="<?= base_url('login') ?>" class="btn btn-outline-success rounded-pill">
          <i class="bi bi-box-arrow-in-right me-1"></i> Kembali ke Login
        </a>
      </div>
    <?php endif; ?>

    <div id="reset-form-wrapper">
      <?= form_open('reset/auth', ['id' => 'reset-form']) ?>
      <input type="hidden" name="token" value="<?= esc($token) ?>">
      <div class="mb-3 position-relative">
        <input type="password" id="password-field" class="form-control rounded-pill mx-auto bg-light-gray pe-5"
          name="pass" placeholder="Password baru" minlength="6" required style="padding-right: 45px;">
        <i id="togglePassword" class="bi bi-eye position-absolute fs-5 toggle-eye"></i>
        <!-- <small class="text-muted d-block mt-1 text-center">Minimal 6 karakter</small> -->
      </div>
      <div class="mb-3 position-relative">
        <input type="password" id="repassword-field" class="form-control rounded-pill mx-auto bg-light-gray pe-5"
          name="reppass" placeholder="Ulangi password baru" minlength="6" required style="padding-right: 45px;">
        <i id="toggleRePassword" class="bi bi-eye position-absolute fs-5 toggle-eye"></i>
      </div>
      <div class="d-grid">
        <button type="submit" class="btn btn-primary rounded-pill mx-auto">RESET PASSWORD</button>
      </div>
      </form>
    </div>

  </div>

  <!-- Logo Section -->
  <div class="col-md-6 bg-light-gray d-flex flex-column justify-content-center align-items-center p-5">
    <img src="<?= base_url('assets/img/logosimlab_nobg.png') ?>" alt="Logo Template" style="width: 250px;"
      class="img-fluid mb-4" />
    <h3 class="fw-bold text-center mb-2">Selamat Datang</h3>
    <p class="text-center text-muted" style="max-width: 300px;">
      Akses sistem layanan lab terpadu untuk mengelola data dan layanan dengan lebih mudah.
    </p>
  </div>

</div>

<script>
  const togglePassword = document.getElementById('togglePassword');
  const passwordField = document.getElementById('password-field');
  const toggleRePassword = document.getElementById('toggleRePassword');
  const repasswordField = document.getElementById('repassword-field');

  togglePassword.addEventListener('click', () => {
    const isPassword = passwordField.type === 'password';
    passwordField.type = isPassword ? 'text' : 'password';
    togglePassword.classList.toggle('bi-eye');
    togglePassword.classList.toggle('bi-eye-slash');
    togglePassword.style.opacity = isPassword ? '1' : '0.7';
  });

  toggleRePassword.addEventListener('click', () => {
    const isPassword = repasswordField.type === 'password';
    repasswordField.type = isPassword ? 'text' : 'password';
    toggleRePassword.classList.toggle('bi-eye');
    toggleRePassword.classList.toggle('bi-eye-slash');
    toggleRePassword.style.opacity = isPassword ? '1' : '0.7';
  });
</script>

<?= $this->endSection() ?>