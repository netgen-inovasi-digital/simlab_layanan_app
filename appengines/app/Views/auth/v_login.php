<?= $this->extend('auth/auth_layout') ?>

<?= $this->section('title') ?>
Login
<?= $this->endSection() ?>

<?= $this->section('content') ?>
<div class="row g-0">

  <div class="col-md-6 bg-light-gray d-flex flex-column justify-content-center align-items-center p-5">
    <img src="assets/img/logosimlab_nobg.png" alt="Logo Template" style="width: 250px;" class="img-fluid mb-4" />
    <h3 class="fw-bold text-center mb-2">Selamat Datang</h3>
    <p class="text-center text-muted" style="max-width: 300px;">
      Akses sistem layanan lab terpadu untuk mengelola data dan layanan dengan lebih mudah.
    </p>
  </div>

  <div class="col-md-6 bg-white p-5">
    <h5 class="fw-bold mb-4 text-center">Log In</h5>
    
    <?php foreach (['success', 'error', 'msg'] as $type): ?>
      <?php if (session()->getFlashdata($type)): ?>
        <blockquote class="blockquote custom-blockquote bg-light mb-3 text-center text-<?= $type == 'error' || $type == 'msg' ? 'danger' : 'success' ?> small rounded">
          <span><?= session()->getFlashdata($type) ?></span>
          <span class="ms-3">
            <i class="bi <?= $type == 'error' || $type == 'msg' ? 'bi-x-circle' : 'bi-check-circle' ?>"></i>
          </span>
        </blockquote>
      <?php endif; ?>
    <?php endforeach; ?>

    <?php if (session()->getFlashdata('login_error')): ?>
      <div class="alert alert-danger alert-dismissible fade show" role="alert">
        <?= session()->getFlashdata('login_error'); ?>
        <button type="button" class="btn-close" data-bs-dismiss="alert" aria-label="Close"></button>
      </div>
    <?php endif; ?>

    <?= form_open('login/admin', ['id' => 'login-form']) ?>
    <?php if (isset($redirect) && !empty($redirect)): ?>
      <input type="hidden" name="redirect" value="<?= esc($redirect) ?>" />
    <?php endif; ?>
    
    <div class="mb-3">
      <input name="username" type="text" class="form-control rounded-pill mx-auto bg-light-gray" placeholder="Username" required />
    </div>

    <div class="mb-3 position-relative">
      <input name="password" id="password-field" type="password" 
             class="form-control rounded-pill mx-auto bg-light-gray pe-5" 
             placeholder="Password" required 
             style="padding-right: 45px;" />
             
      <i id="togglePassword" class="bi bi-eye position-absolute fs-5 toggle-eye"></i>
    </div>

    <div class="d-grid">
      <button type="submit" class="btn btn-primary rounded-pill mx-auto">MASUK</button>
    </div>
    </form>

    </div>
</div>

<script>
  const togglePassword = document.getElementById('togglePassword');
  const passwordField = document.getElementById('password-field');

  togglePassword.addEventListener('click', () => {
    const isPassword = passwordField.type === 'password';
    passwordField.type = isPassword ? 'text' : 'password';
    togglePassword.classList.toggle('bi-eye');
    togglePassword.classList.toggle('bi-eye-slash');
    togglePassword.style.opacity = isPassword ? '1' : '0.7';
  });
</script>

<?= $this->endSection() ?>