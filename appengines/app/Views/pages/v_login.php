<<<<<<< HEAD
<?= $this->extend('auth/auth_layout') ?>

<?= $this->section('title') ?>
Login
<?= $this->endSection() ?>

<?= $this->section('content') ?>
<div class="row g-0">
    <div class="col-md-6 bg-light-gray d-flex flex-column justify-content-center align-items-center p-5">
        <img src="https://placehold.co/250x100?text=Logo+Template" alt="Logo Template" style="width: 250px;" class="img-fluid" />
    </div>

    <div class="col-md-6 bg-white p-5 d-flex flex-column justify-content-center">
        <h5 class="fw-bold mb-4 text-center">Silakan Masuk</h5>
        
        <?php if (session()->getFlashdata('success')): ?>
            <div class="alert alert-success small text-center" role="alert">
                <?= session()->getFlashdata('success') ?>
            </div>
        <?php endif; ?>
        <?php if (session()->getFlashdata('login_error')): ?>
            <div class="alert alert-danger small text-center" role="alert">
                <?= session()->getFlashdata('login_error') ?>
            </div>
        <?php endif; ?>

        <?= form_open('login/auth', ['id' => 'login-form']) ?>
            <div class="mb-3">
                <input name="usr" type="text" class="form-control rounded-pill mx-auto bg-light-gray" placeholder="Username" value="<?= old('usr') ?>" required />
            </div>
            <div class="mb-3">
                <input name="pwd" type="password" class="form-control rounded-pill mx-auto bg-light-gray" placeholder="Password" required />
            </div>
            <div class="d-grid">
                <button type="submit" class="btn btn-primary rounded-pill mx-auto">MASUK</button>
            </div>
        </form>
        <div class="mt-4 text-muted small">
            <p class="text-center">Lupa Sandi? Klik <a href="<?= base_url('forgot') ?>" class="text-decoration-none">Disini</a>.</p>
            <p class="text-center">Belum Punya Akun? Daftar <a href="<?= base_url('register') ?>" class="text-decoration-none">Disini</a>.</p>
        </div>
    </div>
</div>
<?= $this->endSection() ?>
=======
<!DOCTYPE html>
<html lang="id">

<head>
  <meta charset="UTF-8" />
  <meta name="viewport" content="width=device-width, initial-scale=1.0" />
  <title>Login | Klinik MediKidz</title>
  <link rel="stylesheet" href="<?php echo base_url('assets/css/bootstrap.min.css') ?>">
  <link rel="stylesheet" href="<?php echo base_url('assets/css/bootstrap-icons.min.css') ?>">
  <link rel="stylesheet" href="<?php echo base_url('assets/css/login.css?v=0.14') ?>">

  <link rel="preconnect" href="https://fonts.googleapis.com">
  <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
  <link href="https://fonts.googleapis.com/css2?family=Quicksand:wght@300..700&display=swap" rel="stylesheet">
</head>

<body class="bg-custom-green">

  <div class="container">
    <div class="row justify-content-center align-items-center min-vh-100">
      <div class="col-12 col-md-10 col-lg-8">
        <div class="card shadow-lg rounded-4 overflow-hidden">
          <div class="row g-0">

            <!-- Logo Section -->
            <div class="col-md-6 bg-light-gray d-flex flex-column justify-content-center align-items-center p-5">
              <img src="<?php echo base_url('assets/img/logo_medikidz.png') ?>" alt="Logo Klinik MediKidz" class="img-fluid" />
            </div>

            <!-- Form Section -->
            <div class="col-md-6 bg-white p-5">
              <h5 class="fw-bold mb-4 text-center">Silakan Masuk</h5>
              <?php if (session()->getFlashdata('success')): ?>
                <blockquote class="blockquote custom-blockquote bg-light mb-3 text-center text-success small rounded">
                  <span><?= session()->getFlashdata('success') ?></span>
                  <span class="ms-3"><i class="bi bi-check-circle"></i></span>
                </blockquote>
              <?php endif; ?>

              <?php if (session()->getFlashdata('msg')): ?>
                <blockquote class="blockquote custom-blockquote bg-light mb-3 text-center text-danger small rounded">
                  <span><?= session()->getFlashdata('msg') ?></span>
                  <span class="ms-3"><i class="bi bi-exclamation-octagon"></i></span>
                </blockquote>
              <?php endif; ?>
              <!--LOGIN_PAGE_MARKER-->
              <?php echo form_open('login/auth', array('id' => 'login-form')) ?>
              <div class="mb-3">
                <input name="usr" type="text" class="form-control rounded-pill mx-auto bg-light-gray" placeholder="username" />
              </div>
              <div class="mb-3">
                <input name="pwd" type="password" class="form-control rounded-pill mx-auto bg-light-gray" placeholder="password" />
              </div>
              <div class="d-grid">
                <button type="submit" class="btn btn-success rounded-pill mx-auto">MASUK</button>
              </div>
              </form>
              <div class="mt-4 text-muted small">
                <p class="text-center">Lupa Sandi? Klik <a href="forgot" class="text-decoration-none">Disini</a>.</p>
              </div>
            </div>

          </div>
        </div>
        <!-- Footer -->
        <footer class="mt-3 small footer">
          <div class="d-flex flex-column flex-md-row justify-content-between align-items-center">
            <div>&copy; 2025. Klinik MediKidz Banjarbaru.</div>
            <div class="mt-2 mt-md-0">
              <a href="<?php echo base_url() ?>" class=" mx-2">Ke Beranda</a>
              <a href="#" class="mx-2">Tentang Aplikasi</a>
              <a href="#" class="mx-2">Tim Pengembang</a>
              <a href="#" class="mx-2">Kontak</a>
            </div>
          </div>
        </footer>
      </div>
    </div>
  </div>
</body>

</html>
>>>>>>> 8d1b9db1b83d6499aa3f004b06a0e13eb69d8dc5
