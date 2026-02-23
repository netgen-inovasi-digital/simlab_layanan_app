<?= $this->extend('email/layouts/master') ?>
<?= $this->section('content') ?>

<div style="padding: 20px 0;">
  <h2 style="color: #333; margin: 0 0 20px 0; font-size: 22px;">
    Review Layanan Selesai
  </h2>

  <p style="font-size: 15px; color: #333; margin-bottom: 15px;">
    Halo <strong>Admin</strong>,
  </p>

  <p style="font-size: 14px; color: #555; line-height: 1.6; margin-bottom: 20px;">
    Seluruh layanan dalam transaksi berikut telah selesai direview oleh Manajer Teknis dan siap untuk diproses lebih
    lanjut.
  </p>

  <!-- Informasi Transaksi -->
  <table width="100%" cellpadding="0" cellspacing="0" border="0" style="margin-bottom: 25px;">
    <?php if (!empty($no_invoice)): ?>
      <tr>
        <td style="padding: 6px 0; color: #777; font-size: 14px;">No. Invoice</td>
        <td style="padding: 6px 0; color: #333; font-size: 14px;"><?= esc($no_invoice) ?></td>
      </tr>
    <?php endif; ?>
    <tr>
      <td style="padding: 6px 0; color: #777; font-size: 14px;">Pelanggan</td>
      <td style="padding: 6px 0; color: #333; font-size: 14px;"><?= esc($nama_pelanggan) ?></td>
    </tr>
    <tr>
      <td style="padding: 6px 0; color: #777; font-size: 14px;">Email Pelanggan</td>
      <td style="padding: 6px 0; color: #333; font-size: 14px;"><?= esc($email_pelanggan) ?></td>
    </tr>
  </table>

  <hr style="border: none; border-top: 1px solid #eee; margin: 20px 0;">

  <!-- Ringkasan Review -->
  <h3 style="color: #333; margin: 0 0 15px 0; font-size: 16px;">Ringkasan Review</h3>

  <table width="100%" cellpadding="0" cellspacing="0" border="0" style="margin-bottom: 25px;">
    <tr>
      <td style="padding: 6px 0; color: #777; width: 40%; font-size: 14px;">Total Layanan</td>
      <td style="padding: 6px 0; color: #333; font-size: 14px;"><?= esc($total_detail) ?> item</td>
    </tr>
    <tr>
      <td style="padding: 6px 0; color: #777; font-size: 14px;">Diterima</td>
      <td style="padding: 6px 0; color: #333; font-size: 14px;"><?= esc($jumlah_diterima) ?> item</td>
    </tr>
    <tr>
      <td style="padding: 6px 0; color: #777; font-size: 14px;">Ditolak</td>
      <td style="padding: 6px 0; color: #333; font-size: 14px;"><?= esc($jumlah_ditolak) ?> item</td>
    </tr>
  </table>

  <!-- Detail Layanan -->
  <?php if (!empty($detail_items)): ?>
    <hr style="border: none; border-top: 1px solid #eee; margin: 20px 0;">

    <h3 style="color: #333; margin: 0 0 15px 0; font-size: 16px;">Detail Layanan</h3>

    <table width="100%" cellpadding="8" cellspacing="0" border="0" style="border-collapse: collapse; font-size: 14px;">
      <thead>
        <tr style="background-color: #f5f5f5;">
          <th style="text-align: left; padding: 10px; border-bottom: 2px solid #ddd;">No</th>
          <th style="text-align: left; padding: 10px; border-bottom: 2px solid #ddd;">Layanan</th>
          <th style="text-align: center; padding: 10px; border-bottom: 2px solid #ddd;">Status</th>
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
            <td style="padding: 8px 10px; text-align: center; border-bottom: 1px solid #eee;">
              <?php if ((int) $item['status_layanan'] === 1): ?>
                <span style="color: #333; font-weight: bold;">Diterima</span>
              <?php elseif ((int) $item['status_layanan'] === 2): ?>
                <span style="color: #333; font-weight: bold;">Ditolak</span>
              <?php else: ?>
                <span style="color: #999;">-</span>
              <?php endif; ?>
            </td>
          </tr>
        <?php endforeach; ?>
      </tbody>
    </table>
  <?php endif; ?>

  <hr style="border: none; border-top: 1px solid #eee; margin: 25px 0;">

  <p style="font-size: 13px; color: #777;">
    Seluruh layanan telah direview. Silakan lanjutkan proses pelaksanaan pengujian.
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