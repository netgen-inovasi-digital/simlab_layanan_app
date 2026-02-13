<?= $this->extend('email/layouts/master') ?>
<?= $this->section('content') ?>

<div style="padding: 20px 0;">
  <h2 style="color: #333; margin: 0 0 20px 0; font-size: 22px;">
    Verifikasi ULM Diterima
  </h2>

  <p style="font-size: 15px; color: #333; margin-bottom: 15px;">
    Halo <strong><?= esc($nama_pelanggan) ?></strong>,
  </p>

  <p style="font-size: 14px; color: #555; line-height: 1.6; margin-bottom: 20px;">
    Kami informasikan bahwa pengajuan verifikasi identitas <strong>ULM</strong> Anda telah <strong>diterima</strong>
    oleh admin.
  </p>

  <hr style="border: none; border-top: 1px solid #eee; margin: 20px 0;">

  <div style="background-color: #f5f5f5; padding: 15px; border-radius: 4px; margin-bottom: 20px;">
    <p style="font-size: 14px; color: #333; margin: 0 0 5px 0;"><strong>Status Verifikasi:</strong></p>
    <p style="font-size: 14px; color: #2e7d32; margin: 0;">Terverifikasi</p>
  </div>

  <p style="font-size: 14px; color: #555; line-height: 1.6;">
    Akun Anda kini sudah terverifikasi sebagai anggota ULM. Anda dapat melanjutkan untuk membuat pesanan layanan.
  </p>

  <hr style="border: none; border-top: 1px solid #eee; margin: 25px 0;">

  <p style="font-size: 13px; color: #777; text-align: center;">
    Email ini dikirim secara otomatis oleh sistem. Mohon tidak membalas email ini.
  </p>
</div>

<?= $this->endSection() ?>