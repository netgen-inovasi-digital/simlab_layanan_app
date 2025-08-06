<?= $this->extend('email/layouts/master') ?>
<?= $this->section('content') ?>
  <p>Halo <strong><?= esc($nama_pelanggan) ?></strong>,</p>
  <p>Pesanan Anda dengan ID <strong>#<?= esc($order_id) ?></strong> telah <strong>selesai</strong> dan diterima dengan baik.</p>
  <p>Terima kasih telah berbelanja di <strong><?= esc($nama_toko ?? 'Ecomel Sasirangan') ?></strong>! Kami harap Anda puas dengan layanan kami.</p>
<?= $this->endSection() ?>
