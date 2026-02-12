<?= $this->extend('email/layouts/master') ?>
<?= $this->section('content') ?>

<div style="padding: 20px 0;">
  <h2 style="color: #333; margin: 0 0 20px 0; font-size: 22px;">
    Pesanan Baru Masuk
  </h2>

  <p style="font-size: 15px; color: #333; margin-bottom: 15px;">
    Halo <strong>Admin</strong>,
  </p>

  <p style="font-size: 14px; color: #555; line-height: 1.6; margin-bottom: 20px;">
    Ada pesanan baru dari pelanggan <strong><?= esc($nama_pelanggan) ?></strong>.
  </p>

  <!-- Informasi Pelanggan -->
  <table width="100%" cellpadding="0" cellspacing="0" border="0" style="margin-bottom: 25px;">
    <tr>
      <td style="padding: 6px 0; color: #777; width: 40%; font-size: 14px;">Nama</td>
      <td style="padding: 6px 0; color: #333; font-size: 14px;"><?= esc($nama_pelanggan) ?></td>
    </tr>
    <tr>
      <td style="padding: 6px 0; color: #777; font-size: 14px;">Email</td>
      <td style="padding: 6px 0; color: #333; font-size: 14px;"><?= esc($email_pelanggan) ?></td>
    </tr>
    <tr>
      <td style="padding: 6px 0; color: #777; font-size: 14px;">Telepon</td>
      <td style="padding: 6px 0; color: #333; font-size: 14px;"><?= esc($telpon_pelanggan) ?></td>
    </tr>
    <tr>
      <td style="padding: 6px 0; color: #777; font-size: 14px;">Waktu Pemesanan</td>
      <td style="padding: 6px 0; color: #333; font-size: 14px;">
        <?= esc(date('d F Y, H:i', strtotime($tanggal_checkout))) ?> WIB</td>
    </tr>
  </table>

  <hr style="border: none; border-top: 1px solid #eee; margin: 20px 0;">

  <!-- Detail Layanan -->
  <?php if (!empty($detail_items)): ?>
    <h3 style="color: #333; margin: 0 0 15px 0; font-size: 16px;">Detail Layanan</h3>

    <table width="100%" cellpadding="8" cellspacing="0" border="0" style="border-collapse: collapse; font-size: 14px;">
      <thead>
        <tr style="background-color: #f5f5f5;">
          <th style="text-align: left; padding: 10px; border-bottom: 2px solid #ddd;">No</th>
          <th style="text-align: left; padding: 10px; border-bottom: 2px solid #ddd;">Layanan</th>
          <th style="text-align: center; padding: 10px; border-bottom: 2px solid #ddd;">Jumlah</th>
          <th style="text-align: right; padding: 10px; border-bottom: 2px solid #ddd;">Biaya</th>
        </tr>
      </thead>
      <tbody>
        <?php $no = 1;
        foreach ($detail_items as $item): ?>
          <tr>
            <td style="padding: 8px 10px; border-bottom: 1px solid #eee;"><?= $no++ ?></td>
            <td style="padding: 8px 10px; border-bottom: 1px solid #eee;">
              <?= esc($item['nama_layanan']) ?>
              <?php if (!empty($item['metode_nama'])): ?>
                <br><small style="color: #999;">Metode: <?= esc($item['metode_nama']) ?></small>
              <?php endif; ?>
            </td>
            <td style="padding: 8px 10px; text-align: center; border-bottom: 1px solid #eee;"><?= esc($item['jumlah']) ?>
            </td>
            <td style="padding: 8px 10px; text-align: right; border-bottom: 1px solid #eee;">Rp
              <?= esc(number_format($item['biaya'], 0, ',', '.')) ?></td>
          </tr>
        <?php endforeach; ?>
        <tr>
          <td colspan="3" style="padding: 10px; text-align: right; font-weight: bold;">Total:</td>
          <td style="padding: 10px; text-align: right; font-weight: bold;">Rp
            <?= esc(number_format($total_biaya, 0, ',', '.')) ?></td>
        </tr>
      </tbody>
    </table>
  <?php else: ?>
    <p style="color: #999; font-size: 14px;">Detail layanan tidak tersedia.</p>
  <?php endif; ?>

  <hr style="border: none; border-top: 1px solid #eee; margin: 25px 0;">

  <p style="font-size: 13px; color: #777;">
    Harap segera review dan proses pesanan jika sudah ditinjau oleh Manajer Teknis!.
  </p>
</div>

<?= $this->endSection() ?>