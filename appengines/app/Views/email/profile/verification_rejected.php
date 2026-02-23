<?= $this->extend('email/layouts/master') ?>
<?= $this->section('content') ?>

<div style="padding: 20px 0;">
  <h2 style="color: #333; margin: 0 0 20px 0; font-size: 22px;">
    Verifikasi ULM Ditolak
  </h2>

  <p style="font-size: 15px; color: #333; margin-bottom: 15px;">
    Halo <strong><?= esc($nama_pelanggan) ?></strong>,
  </p>

  <p style="font-size: 14px; color: #555; line-height: 1.6; margin-bottom: 20px;">
    Kami informasikan bahwa pengajuan verifikasi identitas <strong>ULM</strong> Anda <strong>belum dapat
      diterima</strong> oleh admin.
  </p>

  <hr style="border: none; border-top: 1px solid #eee; margin: 20px 0;">

  <div style="background-color: #f5f5f5; padding: 15px; border-radius: 4px; margin-bottom: 20px;">
    <p style="font-size: 14px; color: #333; margin: 0 0 5px 0;"><strong>Status Verifikasi:</strong></p>
    <p style="font-size: 14px; color: #c62828; margin: 0;">Belum Terverifikasi</p>
  </div>

  <p style="font-size: 14px; color: #333; margin-bottom: 10px;"><strong>Langkah yang dapat dilakukan:</strong></p>
  <ul style="font-size: 13px; color: #555; line-height: 1.8; margin: 5px 0 0 20px; padding: 0;">
    <li>Pastikan bukti yang diunggah jelas dan sesuai</li>
    <li>Unggah ulang bukti melalui halaman profil</li>
    <li>Hubungi admin jika memerlukan informasi lebih lanjut</li>
  </ul>

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