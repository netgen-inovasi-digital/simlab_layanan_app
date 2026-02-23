<?= $this->extend('email/layouts/master') ?>
<?= $this->section('content') ?>

<div style="padding: 20px 0;">
  <h2 style="color: #333; margin: 0 0 20px 0; font-size: 22px;">
    Hasil Verifikasi Pembayaran
  </h2>

  <p style="font-size: 15px; color: #333; margin-bottom: 15px;">
    Halo <strong><?= esc($nama_pelanggan) ?></strong>,
  </p>

  <p style="font-size: 14px; color: #555; line-height: 1.6; margin-bottom: 20px;">
    Bukti pembayaran yang Anda kirim telah diverifikasi oleh admin. Berikut adalah hasilnya:
  </p>

  <!-- Informasi Invoice -->
  <table width="100%" cellpadding="0" cellspacing="0" border="0" style="margin-bottom: 25px;">
    <tr>
      <td style="padding: 6px 0; color: #777; width: 40%; font-size: 14px;">No. Invoice</td>
      <td style="padding: 6px 0; color: #333; font-size: 14px; font-weight: bold;"><?= esc($no_invoice) ?></td>
    </tr>
  </table>

  <hr style="border: none; border-top: 1px solid #eee; margin: 20px 0;">

  <!-- Status Verifikasi -->
  <?php if ($accepted): ?>
    <div style="padding: 15px; border-radius: 4px; border: 1px solid #ddd; margin-bottom: 20px;">
      <p style="font-size: 14px; color: #333; margin: 0 0 5px 0;"><strong>Status: DITERIMA</strong></p>
      <p style="font-size: 14px; color: #555; margin: 0;">
        Pembayaran Anda telah diverifikasi dan diterima. Proses pengujian sampel akan segera dilanjutkan.
      </p>
    </div>
  <?php else: ?>
    <div style="padding: 15px; border-radius: 4px; border: 1px solid #ddd; margin-bottom: 20px;">
      <p style="font-size: 14px; color: #333; margin: 0 0 5px 0;"><strong>Status: DITOLAK</strong></p>
      <p style="font-size: 14px; color: #555; margin: 0;">
        Bukti pembayaran Anda tidak dapat diverifikasi.
        <?php if (!empty($catatan)): ?>
          <br><br><strong>Catatan dari Admin:</strong><br><?= esc($catatan) ?>
        <?php endif; ?>
      </p>
    </div>

    <p style="font-size: 14px; color: #555; line-height: 1.6;">
      Silakan upload ulang bukti pembayaran yang valid melalui menu <strong>Pembayaran</strong> di dashboard Anda.
    </p>
  <?php endif; ?>

  <hr style="border: none; border-top: 1px solid #eee; margin: 25px 0;">

  <p style="font-size: 13px; color: #777; text-align: center;">
    Email ini dikirim secara otomatis oleh sistem. Mohon tidak membalas email ini.
  </p>

  <!-- CTA Button -->
  <div style="text-align: center; margin: 30px 0 20px 0;">
    <a href="https://simlab.ulm.ac.id/"
      style="display: inline-block; background-color: #0056b3; color: #ffffff; text-decoration: none; padding: 12px 30px; border-radius: 5px; font-size: 15px; font-weight: bold;">
      Buka Aplikasi SIMLAB
    </a>
  </div>
</div>

<?= $this->endSection() ?>