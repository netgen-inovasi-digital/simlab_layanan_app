<?= $this->extend('email/layouts/master') ?>
<?= $this->section('content') ?>
<p>Halo <strong><?= esc($nama_pelanggan) ?></strong>,</p>

<p>Pesanan Anda dengan ID <strong>#<?= esc($order_id) ?></strong> saat ini dalam status: <strong><?= esc($status) ?></strong>.</p>

<p>Anda dapat melacak pesanan Anda di sini:</p>
<p><a href="<?= esc($link_tracking) ?>"><?= esc($link_tracking) ?></a></p>

<p>Terima kasih telah berbelanja bersama kami!</p>
<?= $this->endSection() ?>
