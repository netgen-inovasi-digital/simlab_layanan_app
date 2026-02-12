<?= $this->extend('email/layouts/master') ?>
<?= $this->section('content') ?>

<div style="padding: 20px 0;">
  <h2 style="color: #333; margin: 0 0 20px 0; font-size: 22px;">
    Permintaan Verifikasi ULM
  </h2>

  <p style="font-size: 15px; color: #333; margin-bottom: 15px;">
    Halo <strong>Admin</strong>,
  </p>

  <p style="font-size: 14px; color: #555; line-height: 1.6; margin-bottom: 20px;">
    Pelanggan berikut telah mengajukan verifikasi identitas sebagai <strong>ULM</strong> dan mengunggah bukti pendukung.
    Silakan lakukan pengecekan.
  </p>

  <hr style="border: none; border-top: 1px solid #eee; margin: 20px 0;">

  <!-- Informasi Pelanggan -->
  <h3 style="color: #333; margin: 0 0 15px 0; font-size: 16px;">Informasi Pelanggan</h3>

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
      <td style="padding: 6px 0; color: #777; font-size: 14px;">Status Identitas</td>
      <td style="padding: 6px 0; color: #333; font-size: 14px;"><strong>ULM</strong></td>
    </tr>
  </table>

  <hr style="border: none; border-top: 1px solid #eee; margin: 20px 0;">

  <!-- Tindakan -->
  <p style="font-size: 14px; color: #333; margin-bottom: 10px;"><strong>Tindakan Diperlukan:</strong></p>
  <ul style="font-size: 13px; color: #555; line-height: 1.8; margin: 5px 0 0 20px; padding: 0;">
    <li>Periksa bukti yang diunggah pelanggan</li>
    <li>Verifikasi keabsahan dokumen</li>
    <li>Setujui atau tolak permintaan verifikasi di sistem</li>
  </ul>

  <hr style="border: none; border-top: 1px solid #eee; margin: 25px 0;">

  <p style="font-size: 13px; color: #777; text-align: center;">
    Waktu Pengajuan: <?= esc(date('d F Y, H:i')) ?> WITA
  </p>
</div>

<?= $this->endSection() ?>