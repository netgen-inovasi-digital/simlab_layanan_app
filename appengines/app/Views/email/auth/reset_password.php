<?= $this->extend('email/layouts/master') ?>
<?= $this->section('content') ?>

<div style="padding: 20px 0;">
  <h2 style="color: #333; margin: 0 0 20px 0; font-size: 22px;">
    Permintaan Reset Password
  </h2>

  <p style="font-size: 15px; color: #333; margin-bottom: 15px;">
    Halo, <strong><?= esc($user_name) ?></strong>
  </p>

  <p style="font-size: 14px; color: #555; line-height: 1.6; margin-bottom: 25px;">
    Kami menerima permintaan untuk mereset password akun Anda di sistem <strong>Simlab</strong>.
    Klik tombol di bawah untuk melanjutkan:
  </p>

  <div style="text-align: center; margin: 30px 0;">
    <a href="<?= esc($reset_link) ?>"
      style="background-color: #333; color: #ffffff; padding: 12px 32px; text-decoration: none; border-radius: 5px; display: inline-block; font-size: 15px;">
      Reset Password
    </a>
  </div>

  <p style="font-size: 13px; color: #777; margin-bottom: 10px;">Atau salin link berikut:</p>
  <p
    style="background-color: #f5f5f5; padding: 10px; border: 1px solid #ddd; border-radius: 4px; word-break: break-all; font-size: 13px; color: #555;">
    <?= esc($reset_link) ?>
  </p>

  <hr style="border: none; border-top: 1px solid #eee; margin: 25px 0;">

  <p style="font-size: 13px; color: #777; line-height: 1.6;">
    <strong>Perhatian:</strong>
  </p>
  <ul style="font-size: 13px; color: #777; line-height: 1.8; margin: 5px 0 0 20px; padding: 0;">
    <li>Link berlaku selama <strong>1 jam</strong></li>
    <li>Hanya dapat digunakan <strong>satu kali</strong></li>
    <li>Jika Anda tidak meminta reset password, abaikan email ini</li>
  </ul>
</div>

<?= $this->endSection() ?>