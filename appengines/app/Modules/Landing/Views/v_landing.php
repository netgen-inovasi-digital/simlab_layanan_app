<style>
    .notice-swiper .swiper-wrapper {
        padding-bottom: 30px;
        /* agar pagination tidak ketutup */
    }

    .notice-swiper .swiper-slide {
        height: auto;
    }

    /* .notice-swiper .swiper-pagination {
        position: static;
    } */

    
</style>

<?php foreach ($getLayout as $layout): ?>
    <?php
    $kode = $layout->kode; // misalnya: 'hero', 'layanan', dll
    $konten = json_decode($layout->konten_dinamis ?? '{}');
    ?>

    <?php if ($kode == 'hero'): ?>
        <!-- HERO SECTION -->
        <section id="hero" class="hero-section">
            <div class="swiper hero-slider">
                <div class="swiper-wrapper">
                    <?php foreach ($getHero as $hero): ?>
                        <div class="swiper-slide hero-slide" style="background-image: url('<?= base_url('uploads/' . $hero->foto) ?>');">
                            <div class="hero-overlay">
                                <h2><?= esc($hero->judul) ?></h2>
                                <p><?= esc($hero->deskripsi) ?></p>
                            </div>
                        </div>
                    <?php endforeach; ?>
                </div>
                <div class="swiper-pagination hero-pagination"></div>
            </div>
        </section>

    <?php elseif ($kode == 'layanan'): ?>
        <!-- LAYANAN SECTION -->
        <section id="services" class="services-section py-5 bg-light">
            <div class="container text-center">
                <h2 class="section-title fw-bold mb-3"><?= esc($konten->judul ?? 'Layanan') ?></h2>
                <h6 class="section-desc mb-5"><?= esc($konten->deskripsi ?? 'Deskripsi layanan...') ?></h6>

                <div class="row justify-content-center g-4">
                    <?php foreach ($getLayanan as $layanan): ?>
                        <div class="col-sm-8 col-md-8 col-lg-4">
                            <a href="<?= $layanan->link != '' ?  base_url('/hal/' . $layanan->link . '') : '#' ?>" class="text-decoration-none">
                                <div class="service-card bg-white p-3 rounded shadow-sm h-100">
                                    <img src="<?= base_url('uploads/' . $layanan->foto) ?>" alt="<?= esc($layanan->judul) ?>" class="img-fluid mb-3" style="height: 80px; object-fit: contain;">
                                    <h5 class="fw-bold"><?= esc($layanan->judul) ?></h5>
                                    <p class="text-muted small"><?= esc($layanan->deskripsi) ?></p>
                                </div>
                            </a>
                        </div>
                    <?php endforeach ?>
                </div>
            </div>
        </section>

    <?php elseif ($kode == 'team'): ?>
        <!-- TEAM SECTION -->
        <section id="team" class="team-section py-5">
            <div class="container text-center">
                <h2 class="section-title fw-bold mb-4"><?= esc($konten->judul ?? 'Dokter dan Tenaga Kesehatan') ?></h2>
                <h6 class="section-desc mb-4"><?= esc($konten->deskripsi ?? 'Tim medis kami yang profesional') ?></h6>
                <div class="swiper team-slider">
                    <div class="swiper-wrapper">
                        <?php foreach ($getTeam as $team): ?>
                            <div class="swiper-slide">
                                <a href="<?= $team->link != '' ? base_url('/hal/' . $team->link) : '#' ?>" class="text-decoration-none">
                                    <div class="team-card p-3">
                                        <img src="<?= base_url('uploads/' . $team->foto) ?>" alt="<?= esc($team->nama) ?>" />
                                        <h5><?= esc($team->nama) ?></h5>
                                        <p><?= esc($team->spesialis) ?></p>
                                    </div>
                                </a>
                            </div>
                        <?php endforeach; ?>
                    </div>
                    <div class="swiper-button-next"></div>
                    <div class="swiper-button-prev"></div>
                </div>
            </div>
        </section>

    <?php elseif ($kode == 'berita'): ?>
        <!-- BERITA SECTION -->
        <section id="news" class="news-section py-5">
            <div class="container">
                <div class="d-flex justify-content-between align-items-center mb-4">
                    <div class="col">
                        <h2 class="section-title fw-bold"><?= esc($konten->judul ?? 'Berita Klinik') ?></h2>
                        <h6 class="section-desc"><?= esc($konten->deskripsi ?? '   ') ?></h6>
                    </div>
                    <a href="<?= base_url('berita') ?>" class="btn btn-outline-primary">SELENGKAPNYA</a>
                </div>
                <div class="row g-4">
                    <!-- Berita Utama -->
                    <div class="col-lg-7">
                        <?php $utama = $getBerita[0]; ?>
                        <div class="main-news position-relative rounded overflow-hidden shadow-sm">
                            <img src="<?= $utama->thumbnail ? base_url('uploads/' . $utama->thumbnail) : 'https://placehold.co/500?text=No\nImage' ?>" class="img-fluid w-100" alt="<?= esc($utama->title) ?>">
                            <div class="main-news-overlay p-4">
                                <a href="<?= base_url('berita?&kategori=' . $utama->category_slug) ?>"><span class="btn badge btn-warning text-dark mb-2"><?= esc($utama->nama ?? 'Berita') ?></span></a>
                                <a href="<?= base_url('berita/' . $utama->post_slug) ?>" class="text-decoration-none">
                                    <h3 class="text-white fw-bold"><?= esc(strlen($utama->title) > 100 ? substr($utama->title, 0, 97) . '...' : $utama->title) ?></h3>
                                </a>
                                <div class="text-white small mt-2">
                                    Diterbitkan oleh <span class="text-white fw-semibold"><?= esc($utama->author ?? 'Admin') ?></span>
                                    pada <?= date('d M Y', strtotime($utama->updated_at)) ?> |
                                    Dilihat <?= $utama->views ?? 0 ?> kali
                                </div>
                            </div>
                        </div>
                    </div>
                    <!-- Berita Lainnya -->
                    <div class="col-lg-5 d-flex flex-column gap-3">
                        <?php foreach (array_slice($getBerita, 1) as $berita): ?>
                            <div class="news-card d-flex shadow-sm rounded overflow-hidden">
                                <img src="<?= base_url('uploads/' . $berita->thumbnail) ?>" class="thumb" alt="<?= esc($berita->title) ?>">
                                <div class="p-3">
                                    <a href="<?= base_url('berita?&kategori=' . $berita->category_slug) ?>"><span class="btn badge btn-primary mb-2"><?= esc($berita->nama ?? 'Berita') ?></span></a>
                                    <a href="<?= base_url('berita/' . $berita->post_slug) ?>" class="text-decoration-none text-dark">
                                        <h6 class="fw-bold mb-1"><?= esc(strlen($berita->title) > 80 ? substr($berita->title, 0, 77) . '...' : $berita->title) ?></h6>
                                    </a>
                                    <div class="text-muted small">
                                        Diterbitkan oleh <span class="text-dark fw-semibold"><?= esc($berita->author ?? 'Admin') ?></span><br>
                                        pada <?= date('d M Y', strtotime($berita->updated_at)) ?> | Dilihat <?= $berita->views ?? 0 ?> kali
                                    </div>
                                </div>
                            </div>
                        <?php endforeach; ?>
                    </div>
                </div>
            </div>
        </section>

    <?php elseif ($kode == 'pengumuman'): ?>
        <!-- PENGUMUMAN SECTION -->
        <section id="notice">
            <div class="container">
                <h2 class="section-title fw-bold mb-4 text-center"><?= esc($konten->judul ?? 'Pengumuman') ?></h2>
                <div class="swiper notice-swiper">
                    <div class="swiper-wrapper">
                        <?php foreach ($getPengumuman as $pengumuman): ?>
                            <div class="swiper-slide">
                                <div class="card notice-card h-100 shadow-sm border-0">
                                    <div class="card-body d-flex flex-column text-center px-4">
                                        <h5 class="card-title section-title"><?= esc($pengumuman->judul) ?></h5>
                                        <p class="card-text flex-grow-1"><?= esc($pengumuman->deskripsi) ?></p>
                                        <p class="text-muted small">Diumumkan: <?= formatTanggalIndo($pengumuman->tanggal) ?></p>
                                    </div>
                                </div>
                            </div>
                        <?php endforeach; ?>
                    </div>
                    <!-- Optional: Pagination & Nav -->
                    <div class="swiper-button-prev"></div>
                    <div class="swiper-button-next"></div>
                </div>
            </div>
        </section>


    <?php elseif ($kode == 'mitra'): ?>
        <!-- MITRA SECTION -->
        <section id="partner" class="py-5 bg-light">
            <div class="container text-center">
                <h2 class="fw-bold section-title mb-4"><?= esc($konten->judul ?? 'Mitra dan Partner Kami') ?></h2>
                <h6 class="mb-4 section-desc"><?= esc($konten->deskripsi ?? 'Kami bekerja sama dengan berbagai institusi terpercaya') ?></h6>
                <div class="swiper partner-slider">
                    <div class="swiper-wrapper align-items-center">
                        <?php foreach ($getMitra as $mitra): ?>
                            <div class="swiper-slide">
                                <img src="<?= base_url('uploads/' . $mitra->foto) ?>" alt="<?= esc($mitra->nama) ?>" class="img-fluid"
                                    style="max-height: 80px;" />
                            </div>
                        <?php endforeach; ?>
                    </div>
                </div>
            </div>
        </section>
    <?php endif; ?>
<?php endforeach; ?>


<!-- Swiper JS -->
<script src="https://cdn.jsdelivr.net/npm/swiper@10/swiper-bundle.min.js"></script>

<script>
    var filterButtons = document.querySelectorAll('.filter-btn');
    var produkItems = document.querySelectorAll('.produk-item');

    filterButtons.forEach(btn => {
        btn.addEventListener('click', () => {
            // Remove active class dari semua tombol
            filterButtons.forEach(b => b.classList.remove('active'));
            btn.classList.add('active');

            var filter = btn.getAttribute('data-filter');

            produkItems.forEach(item => {
                var kategori = item.getAttribute('data-kategori');
                if (filter === 'all' || kategori === filter) {
                    item.style.display = 'block';
                } else {
                    item.style.display = 'none';
                }
            });
        });
    });
</script>

<script>
    // ===== muncul/sembunyikan panah di section notice ===== //
    var slider = document.querySelector('.notice-swiper');
    var nextBtn = slider.querySelector('.swiper-button-next');
    var prevBtn = slider.querySelector('.swiper-button-prev');

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

<script>
    var swiper = new Swiper(".team-slider", {
        loop: true,
        autoplay: {
            delay: 3000,
            disableOnInteraction: false,
        },
        slidesPerView: 1,
        spaceBetween: 20,
        pagination: {
            el: ".team-pagination",
            clickable: true,
        },
        navigation: {
            nextEl: ".swiper-button-next",
            prevEl: ".swiper-button-prev",
        },
        breakpoints: {
            768: {
                slidesPerView: 2
            },
            992: {
                slidesPerView: 3
            },
        },
    });
</script>


<script>
    var heroSwiper = new Swiper(".hero-slider", {
        loop: true,
        autoplay: {
            delay: 4000,
            disableOnInteraction: false,
        },
        pagination: {
             el: ".hero-pagination",
            clickable: true,
        }
    });
</script>

<script>
    var partnerSlider = new Swiper(".partner-slider", {
        slidesPerView: 5,
        spaceBetween: 30,
        loop: true,
        autoplay: {
            delay: 2000,
            disableOnInteraction: false,
        },
        breakpoints: {
            320: {
                slidesPerView: 2
            },
            576: {
                slidesPerView: 3
            },
            768: {
                slidesPerView: 4
            },
            992: {
                slidesPerView: 5
            }
        }
    });
</script>

<script>
    var noticeSwiper = new Swiper(".notice-swiper", {
        loop: true,
        autoplay: {
            delay: 3000,
            disableOnInteraction: false,
        },
        slidesPerView: 1,
        spaceBetween: 20,
        pagination: {
            el: ".notice-pagination",
            clickable: true,
        },
        navigation: {
            nextEl: ".swiper-button-next",
            prevEl: ".swiper-button-prev",
        },
        breakpoints: {
            768: {
                slidesPerView: 2
            },
            992: {
                slidesPerView: 3
            },
        },
    });
</script>