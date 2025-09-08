<link rel="stylesheet" href="https://cdn.datatables.net/1.13.6/css/dataTables.bootstrap5.min.css">
<style>
    .notice-swiper .swiper-wrapper {
        padding-bottom: 30px;
    }

    .notice-swiper .swiper-slide {
        height: auto;
    }
</style>

<?php foreach ($getLayout as $layout): ?>
    <?php
    $kode = $layout->kode;
    $konten = json_decode($layout->konten_dinamis ?? '{}');
    ?>

    <?php if ($kode == 'hero'): ?>
        <section id="hero" class="hero-section">
            <div class="swiper hero-slider">
                <div class="swiper-wrapper">
                    <?php foreach ($getHero as $hero): ?>
                        <div class="swiper-slide hero-slide"
                            style="background-image: url('<?= base_url('uploads/' . $hero->foto) ?>');">
                            <div class="hero-overlay">
                                <h2><?= esc($hero->judul) ?></h2>
                                <p><?= esc($hero->deskripsi) ?></p>
                            </div>
                        </div>
                    <?php endforeach; ?>
                </div>
                <div class="swiper-pagination hero-pagination"></div>
            </div>

            <div class="form-overlay-container">
                <div class="card shadow-lg border-0 p-4">
                    <?= $this->include('auth/v_register') ?>
                </div>
            </div>

        </section>

    <?php elseif ($kode == 'layanan'): ?>
        <section id="services" class="services-section py-5 bg-light">
            <div class="container">
                <div class="text-center mb-5">
                    <h2 class="section-title fw-bold mb-3"><?= esc($konten->judul ?? 'Layanan Kami') ?></h2>
                    <h6 class="section-desc"><?= esc($konten->deskripsi ?? 'Berikut daftar layanan yang tersedia') ?></h6>
                </div>
                <div class="row mb-4">
                    <div class="col-md-4 ms-auto">
                        <label for="pencarian" class="form-label fw-bold">Pencarian Layanan</label>
                        <input type="text" class="form-control" id="pencarian" placeholder="Ketik untuk mencari...">
                    </div>
                </div>
                <div class="table-responsive shadow-sm p-3 mb-5 bg-body rounded">
                    <table id="layananTable" class="table table-striped" style="width:100%">
                        <thead class="table-light">
                            <tr>
                                <th>Instrumen / Alat / Tempat</th>
                                <th>Biaya</th>
                                <th>Satuan</th>
                            </tr>
                        </thead>
                        <tbody>
    <?php foreach ($getLayanan as $layanan): ?>
        <?php if ($layanan->status == 'Y'): ?>
            <tr>
                <td>
                    <?php // Coba tampilkan properti 'judul'. Jika tidak ada, tampilkan 'Data Tidak Tersedia'. ?>
                    <?= esc($layanan->judul ?? 'Data Tidak Tersedia') ?>
                </td>
                <td>
                    <?php // Coba format properti 'biaya'. Jika tidak ada, gunakan angka 0. ?>
                    <?= 'Rp ' . number_format($layanan->biaya ?? 0, 0, ',', '.') ?>
                </td>
                <td>
                    <?php // Coba tampilkan properti 'satuan'. Jika tidak ada, tampilkan '-'. ?>
                    <?= esc($layanan->satuan ?? '-') ?>
                </td>
            </tr>
        <?php endif; ?>
    <?php endforeach; ?>
</tbody>
                    </table>
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
                                <a href="<?= $team->link != '' ? base_url('/hal/' . $team->link) : '#' ?>"
                                    class="text-decoration-none">
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
                            <img src="<?= $utama->thumbnail ? base_url('uploads/' . $utama->thumbnail) : 'https://placehold.co/500?text=No\nImage' ?>"
                                class="img-fluid w-100" alt="<?= esc($utama->title) ?>">
                            <div class="main-news-overlay p-4">
                                <a href="<?= base_url('berita?&kategori=' . $utama->category_slug) ?>"><span
                                        class="btn badge btn-warning text-dark mb-2"><?= esc($utama->nama ?? 'Berita') ?></span></a>
                                <a href="<?= base_url('berita/' . $utama->post_slug) ?>" class="text-decoration-none">
                                    <h3 class="text-white fw-bold">
                                        <?= esc(strlen($utama->title) > 100 ? substr($utama->title, 0, 97) . '...' : $utama->title) ?>
                                    </h3>
                                </a>
                                <div class="text-white small mt-2">
                                    Diterbitkan oleh <span
                                        class="text-white fw-semibold"><?= esc($utama->author ?? 'Admin') ?></span>
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
                                <img src="<?= base_url('uploads/' . $berita->thumbnail) ?>" class="thumb"
                                    alt="<?= esc($berita->title) ?>">
                                <div class="p-3">
                                    <a href="<?= base_url('berita?&kategori=' . $berita->category_slug) ?>"><span
                                            class="btn badge btn-primary mb-2"><?= esc($berita->nama ?? 'Berita') ?></span></a>
                                    <a href="<?= base_url('berita/' . $berita->post_slug) ?>"
                                        class="text-decoration-none text-dark">
                                        <h6 class="fw-bold mb-1">
                                            <?= esc(strlen($berita->title) > 80 ? substr($berita->title, 0, 77) . '...' : $berita->title) ?>
                                        </h6>
                                    </a>
                                    <div class="text-muted small">
                                        Diterbitkan oleh <span
                                            class="text-dark fw-semibold"><?= esc($berita->author ?? 'Admin') ?></span><br>
                                        pada <?= date('d M Y', strtotime($berita->updated_at)) ?> | Dilihat
                                        <?= $berita->views ?? 0 ?> kali
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
                <h6 class="mb-4 section-desc">
                    <?= esc($konten->deskripsi ?? 'Kami bekerja sama dengan berbagai institusi terpercaya') ?></h6>
                <div class="swiper partner-slider">
                    <div class="swiper-wrapper align-items-center">
                        <?php foreach ($getMitra as $mitra): ?>
                            <div class="swiper-slide">
                                <img src="<?= base_url('uploads/' . $mitra->foto) ?>" alt="<?= esc($mitra->nama) ?>"
                                    class="img-fluid" style="max-height: 80px;" />
                            </div>
                        <?php endforeach; ?>
                    </div>
                </div>
            </div>
        </section>
    <?php endif; ?>
<?php endforeach; ?>

<script src="https://cdn.jsdelivr.net/npm/swiper@10/swiper-bundle.min.js"></script>
<script>
    // SEMUA SCRIPT SWIPER ASLI ANDA
    var filterButtons = document.querySelectorAll('.filter-btn');
    var produkItems = document.querySelectorAll('.produk-item');
    filterButtons.forEach(btn => { /* ... (kode filter Anda) ... */ });

    var slider = document.querySelector('.notice-swiper');
    if (slider) { /* ... (kode panah swiper Anda) ... */ }

    var swiper = new Swiper(".team-slider", { /* ... (konfigurasi swiper Anda) ... */ });
    var heroSwiper = new Swiper(".hero-slider", {
        loop: true,
        autoplay: { delay: 4000, disableOnInteraction: false },
        pagination: { el: ".hero-pagination", clickable: true }
    });
    var partnerSlider = new Swiper(".partner-slider", { /* ... (konfigurasi swiper Anda) ... */ });
    var noticeSwiper = new Swiper(".notice-swiper", { /* ... (konfigurasi swiper Anda) ... */ });
</script>

<script src="https://code.jquery.com/jquery-3.7.0.js"></script>
<script src="https://cdn.datatables.net/1.13.6/js/jquery.dataTables.min.js"></script>
<script src="https://cdn.datatables.net/1.13.6/js/dataTables.bootstrap5.min.js"></script>
<script>
    $(document).ready(function () {
        var table = new DataTable('#layananTable', {
            "language": {
                "url": "https://cdn.datatables.net/plug-ins/1.13.6/i18n/id.json"
            },
            "dom": 'lrtip'
        });
        $('#pencarian').on('keyup', function () {
            table.search(this.value).draw();
        });
    });
</script>
<script>
    // SCRIPT UNTUK FORM REGISTRASI ULM/NON-ULM
    document.addEventListener('DOMContentLoaded', function () {
        const radioUlm = document.getElementById('radioUlm');
        const radioNonUlm = document.getElementById('radioNonUlm');
        const ktmUploadField = document.getElementById('ktm-upload-field');
        const ktmImageInput = document.getElementById('ktm_image');

        function toggleKtmField() {
            if (radioUlm.checked) {
                // Jika "ULM" dipilih, tampilkan field dan buat wajib diisi
                ktmUploadField.style.display = 'block';
                ktmImageInput.setAttribute('required', 'required');
            } else {
                // Jika "Non-ULM" dipilih
                ktmUploadField.style.display = 'none';
                ktmImageInput.removeAttribute('required');
            }
        }

        radioUlm.addEventListener('change', toggleKtmField);
        radioNonUlm.addEventListener('change', toggleKtmField);

        toggleKtmField();
    });
</script>