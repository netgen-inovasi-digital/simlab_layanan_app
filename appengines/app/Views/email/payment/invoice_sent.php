<?= $this->extend('email/layouts/master') ?>
<?= $this->section('content') ?>

<div style="padding: 20px 0;">
  <h2 style="color: #333; margin: 0 0 20px 0; font-size: 22px;">
    Invoice Pembayaran
  </h2>

  <p style="font-size: 15px; color: #333; margin-bottom: 15px;">
    Halo <strong><?= esc($nama_pelanggan) ?></strong>,
  </p>

  <p style="font-size: 14px; color: #555; line-height: 1.6; margin-bottom: 20px;">
    Invoice untuk pengujian sampel Anda telah diterbitkan. Rincian invoice dapat Anda lihat pada menu <strong>Pembayaran</strong> di SIMLAB
  </p>

  <!-- Informasi Invoice -->
  <table width="100%" cellpadding="0" cellspacing="0" border="0" style="margin-bottom: 25px;">
    <tr>
      <td style="padding: 6px 0; color: #777; width: 40%; font-size: 14px;">No. Invoice</td>
      <td style="padding: 6px 0; color: #333; font-size: 14px; font-weight: bold;"><?= esc($no_invoice) ?></td>
    </tr>
    <tr>
      <td style="padding: 6px 0; color: #777; font-size: 14px;">Kode Layanan</td>
      <td style="padding: 6px 0; color: #333; font-size: 14px;"><?= esc($kode_layanan) ?></td>
    </tr>
    <tr>
      <td style="padding: 6px 0; color: #777; font-size: 14px;">Total Biaya</td>
      <td style="padding: 6px 0; color: #333; font-size: 14px; font-weight: bold;"><?= esc($total_biaya) ?></td>
    </tr>
    <tr>
      <td style="padding: 6px 0; color: #777; font-size: 14px;">Tanggal Invoice</td>
      <td style="padding: 6px 0; color: #333; font-size: 14px;"><?= esc($tanggal_invoice) ?></td>
    </tr>
  </table>

  <hr style="border: none; border-top: 1px solid #eee; margin: 20px 0;">

  <div
    style="background-color: #eee; padding: 15px; border-radius: 4px; border-left: 4px solid #ffffff; margin-bottom: 20px;">
    <p style="font-size: 14px; color: #777; margin: 0 0 5px 0;"><strong>Informasi Penting:</strong></p>
    <p style="font-size: 14px; color: #777; margin: 0;">
      Silakan lakukan pembayaran dan upload bukti bayar melalui menu <strong>Pembayaran</strong> di dashboard Anda.
      File invoice dapat diunduh pada halaman tersebut.
    </p>
  </div>

  <hr style="border: none; border-top: 1px solid #eee; margin: 25px 0;">

  <p style="font-size: 13px; color: #777; text-align: center;">
    Email ini dikirim secara otomatis oleh sistem. Mohon tidak membalas email ini.
  </p>
</div>

<?= $this->endSection() ?>