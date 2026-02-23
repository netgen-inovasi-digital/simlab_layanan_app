<!DOCTYPE html>
<html lang="en">

<head>
  <meta charset="UTF-8">
  <meta name="viewport" content="width=device-width, initial-scale=1.0">
  <title><?= $title ?> | Netgen </title>
  <link rel="icon" type="image/x-icon" href="<?= base_url('favicon.ico') ?>">
  <link rel="stylesheet" href="<?= asset_url('assets/css/bootstrap.min.css') ?>">
  <link rel="stylesheet" href="<?= asset_url('assets/css/bootstrap-icons.min.css') ?>">
  <!-- CDN Swiper -->
  <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/swiper@10/swiper-bundle.min.css" />
  <link rel="stylesheet" href="<?= asset_url('assets/css/website.css') ?>">
  <link rel="stylesheet" href="<?= asset_url('assets/css/captcha.css') ?>">

  <link rel="preconnect" href="https://fonts.googleapis.com">
  <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
  <link
    href="https://fonts.googleapis.com/css2?family=Inter:ital,opsz,wght@0,14..32,100..900;1,14..32,100..900&family=Quicksand:wght@300..700&display=swap"
    rel="stylesheet">
</head>

<body>

  <style>
    .social-icon-link {
      display: flex;
      align-items: center;
      justify-content: center;
      width: 42px;
      height: 42px;
      border-radius: 50%;
      background-color: #f1f1f1;
      color: #333;
      font-size: 20px;
      text-decoration: none;
      transition: all 0.3s ease;
    }

    .social-icon-link:hover {
      /* Bootstrap Primary */
      transform: translateY(-2px) scale(1.1);
      box-shadow: 0 4px 10px rgba(0, 0, 0, 0.1);
    }

    .nav-link.active {
      color: #c78a3b !important;
      font-weight: bold;
    }

    .dropdown-item.active {
      background-color: #fff;
      font-weight: bold;
    }

    .modal-body img,
    .modal-body iframe {
      max-width: 100%;
      border-radius: 12px;
    }

    .modal-body h4 {
      font-size: 1.5rem;
    }

    .modal-body {
      max-height: 80vh;
      overflow-y: auto;
    }
  </style>

  <!-- Navbar -->
  <nav class="navbar navbar-expand-lg navbar-light bg-white fixed-top">
    <div class="container">
      <a class="navbar-brand fw-bold" href="<?php echo base_url('') ?>">
        <img src="assets/img/logosimlab.png" alt="Logo SimLab" style="width: 180px; object-fit: contain;"
          class="img-fluid" />
      </a>
      <button class="navbar-toggler" type="button" data-bs-toggle="collapse" data-bs-target="#navbarNavAltMarkup"
        aria-controls="navbarNavAltMarkup" aria-expanded="false" aria-label="Toggle navigation">
        <span class="navbar-toggler-icon"></span>
      </button>
      <div class="collapse navbar-collapse" id="navbarNavAltMarkup">
        <ul class="navbar-nav ms-auto">
          <?php foreach ($getNavbar as $menu): ?>
            <?php if ($menu['nama'] == 'Layanan' || $menu['nama'] == 'Pengumuman'): ?>

              <?php if ($menu['nama'] == 'Pengumuman'): ?>
                <li class="nav-item">
                  <a class="nav-link" href="#" data-bs-toggle="modal" data-bs-target="#pengumumanModal">
                    Pengumuman
                  </a>
                </li>
              <?php elseif (empty($menu['children'])): ?>
                <li class="nav-item">
                  <?php
                  $menuUrl = rtrim($menu['link'], '/');
                  $currentUrl = rtrim(current_url(), '/');
                  $isActive = $currentUrl === $menuUrl;
                  ?>
                  <a class="nav-link <?= $isActive ? 'active fw-semibold text-primary' : '' ?>" href="<?= $menu['link'] ?>">
                    <?= esc($menu['nama']) ?>
                  </a>
                </li>
              <?php else: ?>
              <?php endif; ?>

            <?php endif; ?>
          <?php endforeach; ?>
        </ul>

        <?php if (session()->get('logged_in')): ?>
          <!-- Tombol untuk user yang sudah login -->
          <a class="btn btn-primary" href="<?= base_url('home') ?>">DASHBOARD</a>
        <?php else: ?>
          <!-- Tombol untuk user yang belum login -->
          <button type="button" class="btn btn-outline-primary me-2" data-bs-toggle="modal" data-bs-target="#authModal">
            MASUK

          </button>
        <?php endif; ?>
      </div>
    </div>
  </nav>

  <!-- Konten -->
  <?php
  // INI ADALAH PERBAIKANNYA
// Kita secara manual meneruskan variabel yang BENAR dari Controller
// ke dalam view $content (v_landing.php)
  

  echo view($content, [
    // Data ini diambil dari Landing.php dan diteruskan ke v_landing.php
    'getJenisLayanan' => $getJenisLayanan ?? [], // Data untuk dropdown
    'getLayanan' => $getLayanan ?? [],      // Data untuk tabel
    'getPengumuman' => $getPengumuman ?? [],   // Data untuk modal
  
    // Data lain yang mungkin dibutuhkan oleh v_landing.php
    // (Jika Anda memuatnya di Landing.php)
    'getHero' => $getHero ?? [],
    'getTeam' => $getTeam ?? [],
    'getBerita' => $getBerita ?? [],
    'getMitra' => $getMitra ?? [],
  ]);
  ?>

  <!-- Footer 
    <footer class="footer">
        <div class="footer-container">
            <div class="footer-col">
                <img src="<?= base_url('uploads/' . $getInformasi->logo) ?>" alt="Logo Ecomel" class="footer-logo">
                <p>
                    <?= substr(strip_tags($getInformasi->deskripsi), 0, 180) . '...' ?>
                </p>
                <?= $getInformasi->link != 'tidak ada' ?
                  '<a href="' . base_url('hal/' . $getInformasi->link) . '" class="btn btn-outline">SELENGKAPNYA</a>'
                  : '' ?>
            </div>
            <div class="footer-col">
                <h4>Alamat</h4>
                <p><?= $getInformasi->alamat ?></p>
                <button type="button" class="btn btn-outline" data-bs-toggle="modal" data-bs-target="#petaModal">
                    LIHAT PETA
                </button>
                <h4 class="highlight mt-3">Informasi Kontak</h4>
                <p>Email: <strong> <a style="color: inherit; text-decoration: none;" href="mailto:<?= $getInformasi->email ?>" "><?= $getInformasi->email ?></a></strong></p>
                <?php
                $nomorWA = $getInformasi->telepon;
                // Hapus karakter non-digit
                $nomorWA = preg_replace('/[^0-9]/', '', $nomorWA);
                // Jika dimulai dengan 08, ganti dengan 628
                if (substr($nomorWA, 0, 2) == '08') {
                  $nomorWA = '628' . substr($nomorWA, 2);
                }
                ?>
                <h4 class=" highlight">Telepon/ WA</h4>
                            <p>
                                <a href="https://wa.me/<?= $nomorWA ?>" target="_blank" style="color: inherit; text-decoration: none;"><?= $getInformasi->telepon ?></a>
                            </p>
            </div>
            <div class="footer-col">
                <h4>Statistik Pengunjung</h4>
                <p><span class="stat-label">Hari Ini</span><br /><?= $viewsToday ?? 0 ?> Orang</p>
                <p><span class="stat-label">Bulan Ini</span><br /><?= $viewsThisMonth ?? 0 ?> Orang</p>
                <p><span class="stat-label">Total</span><br /><?= $viewsAllTime ?? 0 ?> Orang</p>
                <div class="social-icons mt-4">
                    <?php foreach ($getSosmed as $sosmed): ?>
                        <a href="<?= $sosmed->link ?>" class="social-icon-link btn btn-outline"><i class="bi <?= $sosmed->icon ?>"></i></a>
                    <?php endforeach; ?>
                </div>
            </div>
        </div>
        <div class="footer-bottom">
            <p>Netx Template &copy; 2025. All Rights Reserved.</p>
        </div>
    </footer> -->

  <!-- modal peta -->
  <div class="modal fade" id="petaModal" tabindex="-1" aria-labelledby="petaModalLabel" aria-hidden="true">
    <div class="modal-dialog modal-lg modal-dialog-centered">
      <div class="modal-content">
        <div class="modal-header">
          <h5 class="modal-title" id="petaModalLabel">Lokasi Peta</h5>
          <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Tutup"></button>
        </div>
        <div class="modal-body">
          <div class="ratio ratio-16x9">
            <?= $getInformasi->peta ?>
          </div>
        </div>
      </div>
    </div>
  </div>


  <script src="<?= asset_url('assets/js/bootstrap.bundle.min.js') ?>"></script>
  <link rel="preconnect" href="https://fonts.googleapis.com">
  <script src="<?= asset_url('assets/js/rupiahFormatter.js') ?>"></script>
  <?php if ($title !== 'Lab Terpadu ULM'): ?>
    <script src="<?= asset_url('assets/js/sayJS.js') ?>"></script>
    <script src="<?= asset_url('assets/js/sayTable.js') ?>"></script>
  <?php endif; ?>

  <!-- Cart Badge Script -->
  <script>
    function updateCartBadge() {
      const cart = JSON.parse(localStorage.getItem('shopping_cart') || '[]');
      const totalItems = cart.reduce((sum, item) => sum + item.quantity, 0);

      const cartBadge = document.querySelector('.cart-badge');
      if (cartBadge) {
        cartBadge.textContent = totalItems;
        cartBadge.style.display = totalItems > 0 ? 'inline' : 'none';
      }
    }

    // Update cart badge when page loads
    document.addEventListener('DOMContentLoaded', () => {
      updateCartBadge();
    });

    // Update cart badge when storage changes (when user adds items from another tab)
    window.addEventListener('storage', (e) => {
      if (e.key === 'shopping_cart') {
        updateCartBadge();
      }
    });
  </script>
  <div class="modal fade" id="authModal" tabindex="-1" aria-labelledby="authModalLabel" aria-hidden="true">
    <div class="modal-dialog modal-dialog-centered">
      <div class="modal-content rounded-4 shadow p-4">
        <div class="modal-header border-bottom-0">
          <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
        </div>
        <div class="modal-body">

          <!--login form masuk-->
          <div id="login-view">
            <h5 class="fw-bold mb-4 text-center">Silakan Masuk</h5>
            <?php if (session()->getFlashdata('login_error')): ?>
              <div
                class="alert alert-danger small rounded-pill mx-auto d-flex justify-content-center align-items-center p-0"
                role="alert">
                <?= session()->getFlashdata('login_error') ?>
              </div>
            <?php endif; ?>

            <?= form_open('login/auth', ['id' => 'modal-login-form']) ?>
            <div class="mb-3">
              <input name="email" type="text" class="form-control rounded-pill mx-auto bg-light-gray"
                placeholder="Email" value="<?= old('email') ?>" required />
            </div>
            <div class="mb-3">
              <div class="input-group rounded-pill mx-auto bg-light-gray"
                style="border-radius: 50px; overflow: hidden;">
                <input name="pwd" id="modal-password-input" type="password" class="form-control border-0 bg-light-gray"
                  placeholder="Password" required />
                <button class="btn border-0 pe-3" type="button" id="toggleModalPassword"
                  style="background-color: transparent;">
                  <i class="bi bi-eye-slash"></i>
                </button>
              </div>
            </div>
            <!-- Captcha Widget -->
            <?php use App\Libraries\SimpleCaptcha; ?>
            <?= SimpleCaptcha::render('modal-login-form') ?>

            <div class="d-grid">
              <button type="submit" class="btn btn-primary rounded-pill mx-auto">MASUK</button>
            </div>
            </form>

            <div class="mt-4 text-muted small">
              <p class="text-center">
                Lupa Sandi? Klik <a href="#" id="show-forgot-view" class="text-decoration-none">Disini</a>.
              </p>

            </div>
          </div>
          <div id="forgot-view" style="display: none;">
            <h5 class="fw-bold mb-4 text-center">Lupa Password</h5>
            <p class="text-muted small mb-4 text-center">Masukkan email Anda yang terdaftar. Kami akan
              mengirimkan link untuk mereset password.</p>

            <?= form_open('forgot/auth', ['id' => 'modal-forgot-form']) ?>
            <div class="mb-3">
              <input name="email" type="email" class="form-control rounded-pill mx-auto bg-light-gray"
                placeholder="Masukkan email Anda" value="<?= old('email') ?>" required />
            </div>
            <div class="d-grid">
              <button type="submit" class="btn btn-primary rounded-pill mx-auto">SUBMIT</button>
            </div>
            </form>

            <div class="mt-4 text-muted small">
              <p class="text-center">
                Kembali untuk Login? Klik <a href="#" id="show-login-view" class="text-decoration-none">Disini</a>.
              </p>
            </div>
          </div>
        </div>
      </div>
    </div>
  </div>
  <div class="modal fade" id="pengumumanModal" tabindex="-1" aria-labelledby="pengumumanModalLabel" aria-hidden="true">
    <div class="modal-dialog modal-xl modal-dialog-centered">
      <div class="modal-content">

        <div class="modal-header border-0 bg-primary text-white">
          <h4 class="modal-title fw-semibold" id="pengumumanModalLabel">
            <i class="bi bi-megaphone-fill me-2"></i> Pengumuman
          </h4>
          <button type="button" class="btn-close btn-close-white" data-bs-dismiss="modal" aria-label="Close"></button>
        </div>

        <div class="modal-body p-4" style="overflow-y: auto;">
          <?php if (!empty($getPengumuman)): ?>
            <?php foreach ($getPengumuman as $index => $pengumuman): ?>

              <div>
                <h4 class="fw-bold mb-3"><?= esc($pengumuman->judul ?? 'Pengumuman') ?></h4>

                <?php
                if (!empty($pengumuman->file)) {
                  $fileUrl = base_url('uploads/pengumuman/' . $pengumuman->file);
                  $ext = strtolower(pathinfo($pengumuman->file, PATHINFO_EXTENSION));

                  if (in_array($ext, ['jpg', 'jpeg', 'png', 'gif', 'webp'])) {
                    echo '<img src="' . $fileUrl . '" class="img-fluid w-100" style="border-radius: 12px;" alt="' . esc($pengumuman->judul ?? 'Gambar Pengumuman') . '">';
                  } elseif ($ext === 'pdf') {
                    echo '<iframe src="' . $fileUrl . '" class="w-100" style="height: 75vh; border: none; border-radius: 12px;"></iframe>';
                  } else {
                    echo '<div class="p-4 text-center"><a href="' . $fileUrl . '" class="btn btn-outline-primary" target="_blank"><i class="bi bi-file-earmark-fill me-1"></i> Lihat File</a></div>';
                  }
                }
                ?>
              </div>

              <?php if ($index < count($getPengumuman) - 1): ?>
                <hr class="my-4">
              <?php endif; ?>

            <?php endforeach; ?>
          <?php else: ?>
            <p class="text-center text-muted my-4">Tidak ada pengumuman untuk ditampilkan.</p>
          <?php endif; ?>
        </div>
      </div>
    </div>
  </div>

  <script>
    document.addEventListener('DOMContentLoaded', function () {
      const authModal = new bootstrap.Modal(document.getElementById('authModal'));

      function setupPasswordToggle(inputId, toggleId) {
        const toggleBtn = document.getElementById(toggleId);
        const inputField = document.getElementById(inputId);
        const icon = toggleBtn.querySelector('i');

        if (toggleBtn && inputField) {
          toggleBtn.addEventListener('click', (e) => {
            e.preventDefault();
            const isPassword = inputField.type === 'password';

            // Toggle input type
            inputField.type = isPassword ? 'text' : 'password';

            // Toggle icon: eye-slash (tertutup) <-> eye (terbuka)
            icon.classList.toggle('bi-eye-slash');
            icon.classList.toggle('bi-eye');
          });
        }
      }

      setupPasswordToggle('modal-password-input', 'toggleModalPassword');

      let adaAksiFormulir = false;

      <?php if (session()->getFlashdata('login_error')): ?>
        authModal.show();
        adaAksiFormulir = true;
      <?php endif; ?>

      <?php if (
        session()->getFlashdata('error') ||
        session()->getFlashdata('success')
      ): ?>
        adaAksiFormulir = true;
      <?php endif; ?>


      const loginView = document.getElementById('login-view');
      const forgotView = document.getElementById('forgot-view');
      const showForgotLink = document.getElementById('show-forgot-view');
      const showLoginLink = document.getElementById('show-login-view');

      showForgotLink.addEventListener('click', function (e) {
        e.preventDefault();
        loginView.style.display = 'none';
        forgotView.style.display = 'block';
      });

      showLoginLink.addEventListener('click', function (e) {
        e.preventDefault();
        forgotView.style.display = 'none';
        loginView.style.display = 'block';
      });

      const authModalElement = document.getElementById('authModal');
      authModalElement.addEventListener('hidden.bs.modal', function (event) {
        forgotView.style.display = 'none';
        loginView.style.display = 'block';
      });

      const pengumumanModalElement = document.getElementById('pengumumanModal');
      if (pengumumanModalElement) {
        const pengumumanModal = new bootstrap.Modal(pengumumanModalElement);

        <?php if (!empty($getPengumuman)): ?>
          if (adaAksiFormulir == false) {
            pengumumanModal.show();
          }
        <?php endif; ?>
      }
    });
  </script>
</body>

</html>