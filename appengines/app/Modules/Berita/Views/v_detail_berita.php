<div class="container py-5">
    <!-- Breadcrumb -->
    <nav aria-label="breadcrumb" class="mb-4">
        <ol class="breadcrumb bg-white p-2 px-3 rounded shadow-sm">
            <li><a href="<?= base_url('/') ?>" class="text-primary text-decoration-none">Home</a></li>
            <li><i class="bi bi-chevron-right mx-1 text-muted"></i><a href="<?= base_url('berita') ?>" class="text-primary text-decoration-none">Berita</a></li>
            <li class="breadcrumb-item active" aria-current="page"><i class="bi bi-chevron-right mx-1 text-muted"></i><?= esc($post->title) ?></li>
        </ol>
    </nav>

    <!-- Wrapper Card -->
<<<<<<< HEAD
    <div class="card border-0 shadow rounded-4 p-4">
=======
    <div class="card border-0 shadow-lg rounded-4 p-4">
>>>>>>> df8c327176c0d2352c9b643155da517b0816f615
        <!-- Judul -->
        <h1 class="fw-bold display-5 mb-3"><?= esc($post->title) ?></h1>

        <!-- Info Penulis & Tanggal -->
        <div class="d-flex flex-wrap align-items-center text-muted mb-4 gap-3 small">
            <div><i class="bi bi-person-circle me-1"></i> <?= esc($post->nama_user) ?></div>
            <div><i class="bi bi-calendar-event me-1"></i> <?= date('d M Y', strtotime($post->published_at)) ?></div>
            <div><i class="bi bi-eye me-1"></i> Dilihat <?= esc($post->views) ?? 0 ?> kali</div>
        </div>

        <!-- Thumbnail -->
<<<<<<< HEAD
        <div class="mb-4 text-center">
            <img
                src="<?= !empty($post->thumbnail)
                            ? base_url('uploads/' . $post->thumbnail)
                            : 'https://placehold.co/800x500?text=No+Image&font=roboto' ?>"
                class="img-fluid rounded-4 shadow-sm"
                style="max-height: 500px; object-fit: cover;"
                alt="<?= esc($post->title) ?>">
        </div>
=======
        <?php if (!empty($post->thumbnail)): ?>
        <div class="mb-4 text-center">
            <img src="<?= base_url('uploads/' . $post->thumbnail) ?>" class="img-fluid rounded-4 shadow-sm" style="max-height: 400px; object-fit: cover;" alt="<?= esc($post->title) ?>">
        </div>
        <?php endif; ?>
>>>>>>> df8c327176c0d2352c9b643155da517b0816f615

        <!-- Konten -->
        <article class="mb-5" style="line-height: 1.9; font-size: 1.1rem;">
            <?= $post->konten ?>
        </article>

        <!-- Kategori & Tags -->
        <div class="mb-4">
<<<<<<< HEAD
            <a href="<?= base_url('berita?kategori=' . $post->category_slug) ?>"><span class="badge bg-primary me-2">#<?= esc($post->nama) ?></span></a>
=======
            <span class="badge bg-primary me-2">#<?= esc($post->nama) ?></span>
>>>>>>> df8c327176c0d2352c9b643155da517b0816f615
            <?php if (!empty($post->tags)): ?>
                <?php foreach (explode(',', $post->tags) as $tag): ?>
                    <span class="badge rounded-pill bg-light border text-secondary me-1">#<?= esc(trim($tag)) ?></span>
                <?php endforeach; ?>
            <?php endif; ?>
        </div>

        <!-- Share -->
        <div class="mb-5">
            <span class="me-2 fw-semibold">Bagikan:</span>
<<<<<<< HEAD
            <?php foreach ($getSosmed as $sosmed): ?>
                <a href="<?= $sosmed->link ?>" class="btn btn-outline-primary btn-sm rounded-pill me-1"><i class="bi <?= $sosmed->icon ?>"></i></a>
=======
            <?php foreach($getSosmed as $sosmed): ?>
            <a href="<?= $sosmed->link ?>" class="btn btn-outline-primary btn-sm rounded-pill me-1"><i class="bi <?= $sosmed->icon ?>"></i></a>
>>>>>>> df8c327176c0d2352c9b643155da517b0816f615
            <?php endforeach; ?>
        </div>
    </div>

    <!-- Related Posts -->
    <?php if (!empty($relatedPosts)): ?>
<<<<<<< HEAD
        <div class="mt-5">
            <h4 class="fw-bold mb-4">Baca Juga</h4>
            <div class="row row-cols-1 row-cols-md-3 g-4">
                <?php foreach ($relatedPosts as $item): ?>
                    <div class="col">
                        <a href="<?= base_url('berita/' . $item->slug) ?>" class="text-decoration-none text-dark card-hover-link">
                            <div class="card h-100 border-0 shadow-sm transition-hover">
                                <img
                                    src="<?= !empty($item->thumbnail)
                                                ? base_url('uploads/' . $item->thumbnail)
                                                : 'https://placehold.co/300x180?text=No+Image&font=roboto' ?>"
                                    class="card-img-top"
                                    style="height: 180px; object-fit: cover;"
                                    alt="<?= esc($item->title) ?>">

                                <div class="card-body">
                                    <!-- Username -->
                                    <small class="text-muted d-block mb-2">
                                        <i class="bi bi-person me-1"></i>
                                        <?= $item->nama_user ?>
                                    </small>
                                    <!-- Baris info: tanggal dan views -->
                                    <div class="d-flex justify-content-between align-items-center mb-2">
                                        <!-- Kolom tanggal -->
                                        <div class="d-flex align-items-center">
                                            <i class="bi bi-calendar me-2"></i>
                                            <small class="text-muted"><?= date('d M Y', strtotime($item->published_at)) ?></small>
                                        </div>
                                        <!-- Kolom views -->
                                        <div class="d-flex align-items-center">
                                            <i class="bi bi-eye me-1"></i>
                                            <small class="text-muted"><?= $item->views ?? 0 ?></small>
                                        </div>
                                    </div>
                                    <!-- Kategori -->
                                    <small class="badge bg-secondary mb-2"><?= $item->nama ?></small>

                                    <!-- Judul dan Konten -->
                                    <h6 class="fw-bold"><?= $item->title ?></h6>
                                    <p class="text-muted small">
                                        <?= substr(strip_tags($item->konten), 0, 100) . '...' ?>
                                    </p>
                                </div>
                            </div>
                        </a>
                    </div>
                <?php endforeach ?>
            </div>
        </div>
    <?php endif ?>
</div>
=======
    <div class="mt-5">
        <h4 class="fw-bold mb-4">Baca Juga</h4>
        <div class="row row-cols-1 row-cols-md-3 g-4">
            <?php foreach ($relatedPosts as $item): ?>
            <div class="col">
                <a href="<?= base_url('berita/' . $item->slug) ?>" class="text-decoration-none text-dark">
                    <div class="card h-100 border-0 shadow-sm rounded-4">
                        <img src="<?= base_url('uploads/' . $item->thumbnail) ?>" class="card-img-top rounded-top-4" style="height: 160px; object-fit: cover;">
                        <div class="card-body">
                            <h6 class="fw-semibold"><?= esc($item->title) ?></h6>
                            <p class="text-muted small mb-0"><?= substr(strip_tags($item->konten), 0, 90) . '...' ?></p>
                        </div>
                    </div>
                </a>
            </div>
            <?php endforeach ?>
        </div>
    </div>
    <?php endif ?>
</div>
>>>>>>> df8c327176c0d2352c9b643155da517b0816f615
