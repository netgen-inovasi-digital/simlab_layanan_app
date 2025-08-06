<?= $this->extend('email/layouts/master') ?>
<?= $this->section('content') ?>

  <p>Halo Admin,</p>

  <p>Pelanggan <strong><?= esc($nama_pelanggan) ?></strong> dengan ID pesanan <strong>#<?= esc($order_id) ?></strong> telah mengunggah bukti transfer untuk pesanan mereka.</p>

  <table width="100%" cellpadding="8" cellspacing="0" border="0" style="border-collapse: collapse; background-color: #f9f9f9; border-radius: 6px;">
    <tr>
      <td><strong>Nama Pelanggan</strong></td>
      <td><?= esc($nama_pelanggan) ?></td>
    </tr>
    <tr>
      <td><strong>Email</strong></td>
      <td><?= esc($email_pelanggan) ?></td>
    </tr>
    <tr>
      <td><strong>Waktu Upload</strong></td>
      <td><?= esc($waktu_upload) ?></td>
    </tr>
    <tr>
      <td><strong>Total Pembayaran</strong></td>
      <td><?= esc(number_format($total_harga, 0, ',', '.')) ?> IDR</td>
    </tr>
    <tr>
      <td><strong>Metode Pembayaran</strong></td>
      <td><?= esc($metode_pembayaran) ?></td>
    </tr>
  </table>

  <p>Silakan segera verifikasi bukti transfer melalui sistem.</p>

  <p>
    <a href="<?= esc($link_verifikasi_admin) ?>" style="display:inline-block; background-color:#28a745; color:#fff; padding:10px 16px; border-radius:6px; text-decoration:none;" target="_blank">
      Verifikasi Pembayaran
    </a>
  </p>

<?= $this->endSection() ?>
