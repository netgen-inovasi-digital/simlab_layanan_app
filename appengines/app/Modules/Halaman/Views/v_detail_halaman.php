<div class="container py-5">
    <div class="mx-auto" style="max-width: 800px;">

        <!-- Judul Post -->
        <h1 class="fw-bold display-5 mb-3"><?= esc($halaman->title) ?></h1>

        <!-- Info Penulis -->
        <div class="d-flex align-items-center text-muted mb-4" style="font-size: 0.95rem;">
            <div class="me-4 d-flex align-items-center">
                <i class="bi bi-person-circle me-2"></i> <?= esc($halaman->nama) ?>
            </div>
            <div class="me-4 d-flex align-items-center">
                <i class="bi bi-calendar-event me-2"></i> <?= date('d M Y', strtotime($halaman->published_at)) ?>
            </div>
            <div class="d-flex align-items-center"><i class="bi bi-eye me-1"></i> Dilihat <?= esc($halaman->views) ?? 0 ?> kali</div>
        </div>

        <!-- Konten Artikel -->
        <article class="mb-5" style="line-height: 1.8; font-size: 1.05rem;">
            <?= $halaman->konten ?>
        </article>

        <!-- Garis Pemisah -->
        <hr class="my-4">

        <!-- Bagian Share (Opsional) -->
        <div class="d-flex justify-content-between align-items-center">
            <div class="text-muted small">
                Dibagikan oleh <strong><?= esc($halaman->nama) ?></strong>
            </div>
            <div class="d-flex gap-2">
                <?php foreach ($getSosmed as $sosmed) : ?>
                    <a href="<?= $sosmed->link ?>" class="btn btn-outline-primary btn-sm"><i class="bi <?= $sosmed->icon ?>"></i></a>
                <?php endforeach; ?>
            </div>
        </div>
    </div>
</div>