<?= $this->extend('email/layouts/master') ?>
<?= $this->section('content') ?>

<div style="padding: 20px 0;">
  <h2 style="color: #333; margin: 0 0 20px 0; font-size: 22px;">
    Bukti Pembayaran Baru
  </h2>

  <p style="font-size: 15px; color: #333; margin-bottom: 15px;">
    Halo <strong>Admin</strong>,
  </p>

  <p style="font-size: 14px; color: #555; line-height: 1.6; margin-bottom: 20px;">
    Pelanggan telah mengupload bukti pembayaran yang perlu diverifikasi. Berikut detailnya:
  </p>

  <!-- Informasi Pembayaran -->
  <table width="100%" cellpadding="0" cellspacing="0" border="0" style="margin-bottom: 25px;">
    <tr>
      <td style="padding: 6px 0; color: #777; width: 40%; font-size: 14px;">No. Invoice</td>
      <td style="padding: 6px 0; color: #333; font-size: 14px; font-weight: bold;"><?= esc($no_invoice) ?></td>
    </tr>
    <tr>
      <td style="padding: 6px 0; color: #777; font-size: 14px;">Pelanggan</td>
      <td style="padding: 6px 0; color: #333; font-size: 14px;"><?= esc($nama_pelanggan) ?></td>
    </tr>
    <tr>
      <td style="padding: 6px 0; color: #777; font-size: 14px;">Email Pelanggan</td>
      <td style="padding: 6px 0; color: #333; font-size: 14px;"><?= esc($email_pelanggan) ?></td>
    </tr>
    <tr>
      <td style="padding: 6px 0; color: #777; font-size: 14px;">Total Biaya</td>
      <td style="padding: 6px 0; color: #333; font-size: 14px; font-weight: bold;"><?= esc($total_biaya) ?></td>
    </tr>
    <tr>
      <td style="padding: 6px 0; color: #777; font-size: 14px;">Tanggal Upload</td>
      <td style="padding: 6px 0; color: #333; font-size: 14px;"><?= esc($tanggal_upload) ?></td>
    </tr>
  </table>

  <hr style="border: none; border-top: 1px solid #eee; margin: 20px 0;">

  <div style="padding: 15px; border-radius: 4px; border: 1px solid #ddd; margin-bottom: 20px;">
    <p style="font-size: 14px; color: #333; margin: 0 0 5px 0;"><strong>Tindakan Diperlukan:</strong></p>
    <p style="font-size: 14px; color: #555; margin: 0;">
      Silakan verifikasi bukti pembayaran melalui menu <strong>Verifikasi Pembayaran</strong> di dashboard admin.
    </p>
  </div>

  <hr style="border: none; border-top: 1px solid #eee; margin: 25px 0;">

  <p style="font-size: 13px; color: #777; text-align: center;">
    Email ini dikirim secara otomatis oleh sistem. Mohon tidak membalas email ini.
  </p>

  <!-- CTA Button -->
  <div style="text-align: center; margin: 30px 0 20px 0;">
    <a href="https://simlab.ulm.ac.id/login"
      style="display: inline-block; background-color: #0056b3; color: #ffffff; text-decoration: none; padding: 12px 30px; border-radius: 5px; font-size: 15px; font-weight: bold;">
      Login ke SIMLAB
    </a>
  </div>
</div>

<?= $this->endSection() ?>