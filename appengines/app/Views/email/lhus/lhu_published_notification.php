<?= $this->extend('email/layouts/master') ?>
<?= $this->section('content') ?>

<div style="padding: 20px 0;">
  <h2 style="color: #333; margin: 0 0 20px 0; font-size: 22px;">
    LHU Telah Diterbitkan
  </h2>

  <p style="font-size: 15px; color: #333; margin-bottom: 15px;">
    Halo <strong><?= esc($nama_pelanggan) ?></strong>,
  </p>

  <p style="font-size: 14px; color: #555; line-height: 1.6; margin-bottom: 20px;">
    Laporan Hasil Uji (LHU) untuk layanan Anda telah diterbitkan.
    LHU sudah dapat diambil di aplikasi SIMLAB.
  </p>

  <hr style="border: none; border-top: 1px solid #eee; margin: 20px 0;">

  <!-- Informasi Layanan -->
  <h3 style="color: #333; margin: 0 0 15px 0; font-size: 16px;">Informasi Layanan</h3>

  <table width="100%" cellpadding="0" cellspacing="0" border="0" style="margin-bottom: 25px;">
    <?php if (!empty($no_invoice)): ?>
      <tr>
        <td style="padding: 6px 0; color: #777; width: 40%; font-size: 14px;">No. Invoice</td>
        <td style="padding: 6px 0; color: #333; font-size: 14px;"><?= esc($no_invoice) ?></td>
      </tr>
    <?php endif; ?>
    <tr>
      <td style="padding: 6px 0; color: #777; width: 40%; font-size: 14px;">Tanggal Terbit</td>
      <td style="padding: 6px 0; color: #333; font-size: 14px; font-weight: bold;"><?= esc($tanggal_terbit) ?></td>
    </tr>
  </table>

  <!-- Detail Layanan -->
  <?php if (!empty($detail_items)): ?>
    <hr style="border: none; border-top: 1px solid #eee; margin: 20px 0;">

    <h3 style="color: #333; margin: 0 0 15px 0; font-size: 16px;">
      Detail Pengujian (<?= count($detail_items) ?> item)
    </h3>

    <table width="100%" cellpadding="8" cellspacing="0" border="0" style="border-collapse: collapse; font-size: 14px;">
      <thead>
        <tr style="background-color: #f5f5f5;">
          <th style="text-align: left; padding: 10px; border-bottom: 2px solid #ddd;">No</th>
          <th style="text-align: left; padding: 10px; border-bottom: 2px solid #ddd;">Layanan</th>
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
          </tr>
        <?php endforeach; ?>
      </tbody>
    </table>
  <?php endif; ?>

  <hr style="border: none; border-top: 1px solid #eee; margin: 25px 0;">

  <!-- Call to Action -->
  <div
    style="background-color: #f8f9fa; border-left: 4px solid #ddd; padding: 15px 20px; margin-bottom: 20px; border-radius: 4px;">
    <p style="font-size: 14px; color: #333; margin: 0 0 10px 0; font-weight: bold;">
      Catatan:
    </p>
    <ol style="font-size: 14px; color: #555; margin: 0; padding-left: 20px; line-height: 1.8;">
      <li>Mohon mengisi <strong>kuesioner kepuasan pelanggan</strong> melalui SIMLAB untuk membantu kami meningkatkan
        kualitas layanan.</li>
      <li>Anda bisa mengajukan pengujian ulang dengan cara menghubungi Admin dalam waktu 7 hari setelah LHU diterbitkan.
      </li>
    </ol>
  </div>

  <p style="font-size: 13px; color: #777;">
    Terima kasih telah menggunakan layanan pengujian kami.
  </p>
</div>

<?= $this->endSection() ?>