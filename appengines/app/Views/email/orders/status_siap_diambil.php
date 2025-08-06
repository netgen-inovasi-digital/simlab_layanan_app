<?= $this->extend('email/layouts/master') ?>
<?= $this->section('content') ?>
  <p>Halo <strong><?= esc($nama_pelanggan) ?></strong>,</p>
  <p>Pesanan Anda dengan ID <strong>#<?= esc($order_id) ?></strong> sudah <strong>siap untuk diambil</strong> di toko kami.</p>
  <p>Silakan datang ke toko dengan menunjukkan bukti pesanan.</p>
  <p>Alamat Toko:<br><?= nl2br(esc($alamat_toko ?? '')) ?></p>
<?= $this->endSection() ?>
