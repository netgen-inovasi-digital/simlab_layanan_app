<?= $this->extend('email/layouts/master') ?>
<?= $this->section('content') ?>
<p>Halo <strong><?= esc($nama_pelanggan) ?></strong>,</p>
<p>Pesanan Anda dengan ID <strong>#<?= esc($order_id) ?></strong> telah <strong>dikirim</strong> ke alamat tujuan.</p>
Ekspedisi: <strong><?= esc($shipping_service ?? '-') ?></strong></p>
<p>Silakan lacak status pengiriman melalui website resmi ekspedisi.</p>
<?= $this->endSection() ?>