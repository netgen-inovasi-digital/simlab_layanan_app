<?= $this->extend('email/layouts/master') ?>
<?= $this->section('content') ?>
  <p>Halo <strong><?= esc($nama_pelanggan) ?></strong>,</p>
  <p>Pesanan Anda dengan ID <strong>#<?= esc($order_id) ?></strong> telah <strong>dibatalkan</strong>.</p>
  <p>Alasan pembatalan: <em><?= esc($alasan ?? 'Tidak disebutkan') ?></em></p>
  <p>Jika Anda merasa ini adalah kesalahan, silakan hubungi customer service kami.</p>
<?= $this->endSection() ?>
