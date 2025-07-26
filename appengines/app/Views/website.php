<!DOCTYPE html>
<html lang="en">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Klinik Medikidz Banjarbaru</title>
    <link rel="icon" type="image/x-icon" href="<?php echo base_url('assets/img/favicon.ico') ?>">
    <link rel="stylesheet" href="<?php echo base_url('assets/css/bootstrap.min.css') ?>">
    <link rel="stylesheet" href="<?php echo base_url('assets/css/bootstrap-icons.min.css') ?>">
    <!-- CDN Swiper -->
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/swiper@10/swiper-bundle.min.css" />
    <link rel="stylesheet" href="<?php echo base_url('assets/css/landing.css?v=0.1') ?>">

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
    </style>

    <!-- Navbar -->
    <nav class="navbar navbar-expand-lg navbar-light bg-white fixed-top">
        <div class="container">
            <a class="navbar-brand fw-bold" href="<?php echo base_url('') ?>">
                <img src="<?php echo base_url('assets/img/logo_landscape.png?v=0.4') ?>" alt="MediKidz Logo" height="50" />
            </a>
            <button class="navbar-toggler" type="button" data-bs-toggle="collapse" data-bs-target="#navbarNavAltMarkup"
                aria-controls="navbarNavAltMarkup" aria-expanded="false" aria-label="Toggle navigation">
                <span class="navbar-toggler-icon"></span>
            </button>
            <div class="collapse navbar-collapse" id="navbarNavAltMarkup">
                <ul class="navbar-nav ms-auto">
                    <?php foreach ($getNavbar as $menu): ?>
                        <?php if (empty($menu['children'])): ?>
                            <li class="nav-item">
                                <a class="nav-link" href="<?= $menu['link'] ?>" <?= str_starts_with($menu['link'], 'http') ? 'target="_blank"' : '' ?>>
                                    <?= esc($menu['nama']) ?>
                                </a>
                            </li>
                        <?php else: ?>
                            <li class="nav-item dropdown">
                                <a class="nav-link dropdown-toggle" href="#" role="button" data-bs-toggle="dropdown">
                                    <?= esc($menu['nama']) ?>
                                </a>
                                <ul class="dropdown-menu">
                                    <?php foreach ($menu['children'] as $child): ?>
                                        <li>
                                            <a class="dropdown-item" href="<?= $child['link'] ?>" <?= str_starts_with($child['link'], 'http') ? 'target="_blank"' : '' ?>>
                                                <?= esc($child['nama']) ?>
                                            </a>
                                        </li>
                                    <?php endforeach; ?>
                                </ul>
                            </li>
                        <?php endif; ?>
                    <?php endforeach; ?>
                </ul>


                <!-- <a class="btn btn-outline-primary me-2" href="#">MASUK</a>
                <a class="btn btn-primary" href="#">DAFTAR</a> -->
            </div>
        </div>
    </nav>

    <!-- Konten -->
    <?php echo view($content) ?>

    <!-- Footer -->
    <footer class="footer">
        <div class="footer-container">
            <div class="footer-col">
                <img src="<?= base_url('uploads/' . $getInformasi->logo) ?>" alt="Logo Medikidz" class="footer-logo">
                <p>
                    <?= $getInformasi->deskripsi ?>
                </p>
                <?= $getInformasi->link != 'tidak ada' ? 
                '<a href="'. base_url('hal/'.$getInformasi->link) . '" class="btn btn-outline">SELENGKAPNYA</a>'
                : '' ?>
            </div>
            <div class="footer-col">
                <h4>Alamat</h4>
                <p><?= $getInformasi->alamat ?></p>
                <button type="button" class="btn btn-outline" data-bs-toggle="modal" data-bs-target="#petaModal">
                    LIHAT PETA
                </button>
                <h4 class="highlight mt-3">Informasi Kontak</h4>
                <p>Email: <strong><?= $getInformasi->email ?></strong></p>
                <h4 class="highlight">Telepon/ WA</h4>
                <p><?= $getInformasi->telepon ?></p>
            </div>
            <div class="footer-col">
                <h4>Statistik Pengunjung</h4>
                <p><span class="stat-label">Hari Ini</span><br /><?= $viewsToday ?? 0 ?> Orang</p>
                <p><span class="stat-label">Bulan Ini</span><br /><?= $viewsThisMonth ?? 0 ?> Orang</p>
                <p><span class="stat-label">Total</span><br /><?= $viewsAllTime ?? 0 ?> Orang</p>
                <div class="social-icons mt-4">
                    <?php foreach ($getSosmed as $sosmed) : ?>
                        <a href="<?= $sosmed->link ?>" class="social-icon-link btn btn-outline"><i class="bi <?= $sosmed->icon ?>"></i></a>
                    <?php endforeach; ?>
                </div>
            </div>
        </div>
        <div class="footer-bottom">
            <p>Klinik MediKidz &copy; 2025. All Rights Reserved.</p>
        </div>
    </footer>

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


    <script src="<?php echo base_url('assets/js/bootstrap.bundle.min.js') ?>"></script>
    <link rel="preconnect" href="https://fonts.googleapis.com">

    <script src="<?php echo base_url('assets/js/sayJS.js?v=0.02') ?>"></script>
    <script src="<?php echo base_url('assets/js/sayTable.js?v=0.11') ?>"></script>
    <script>
        // ===== muncul/sembunyikan panah di section team ===== //
        const slider = document.querySelector('.team-slider');
        const nextBtn = slider.querySelector('.swiper-button-next');
        const prevBtn = slider.querySelector('.swiper-button-prev');

        // Sembunyikan tombol saat awal
        nextBtn.style.opacity = '0';
        prevBtn.style.opacity = '0';
        nextBtn.style.transition = 'opacity 0.3s ease';
        prevBtn.style.transition = 'opacity 0.3s ease';

        // Saat mouse masuk ke slider
        slider.addEventListener('mouseenter', () => {
            nextBtn.style.opacity = '1';
            prevBtn.style.opacity = '1';
        });

        // Saat mouse keluar dari slider
        slider.addEventListener('mouseleave', () => {
            nextBtn.style.opacity = '0';
            prevBtn.style.opacity = '0';
        });
    </script>
</body>

</html>