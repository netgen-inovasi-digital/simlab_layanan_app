<?= $this->extend('email/layouts/master') ?>
<?= $this->section('content') ?>

<div style="padding: 20px 0;">
  <h2 style="color: #333; margin: 0 0 20px 0; font-size: 22px;">
    Hasil Review LHUS
  </h2>

  <p style="font-size: 15px; color: #333; margin-bottom: 15px;">
    Halo <strong>Analis</strong>,
  </p>

  <p style="font-size: 14px; color: #555; line-height: 1.6; margin-bottom: 20px;">
    <?php if (!empty($all_accepted)): ?>
      Manajer Teknis telah meninjau dan <strong style="color: #28a745;">menerima semua</strong> LHUS untuk layanan
      berikut.
    <?php else: ?>
      Manajer Teknis telah menyelesaikan peninjauan LHUS untuk layanan berikut. Silakan periksa detail di bawah ini.
    <?php endif; ?>
  </p>

  <hr style="border: none; border-top: 1px solid #eee; margin: 20px 0;">

  <!-- Ringkasan Review -->
  <h3 style="color: #333; margin: 0 0 15px 0; font-size: 16px;">Ringkasan Review</h3>

  <table width="100%" cellpadding="0" cellspacing="0" border="0" style="margin-bottom: 25px;">
    <tr>
      <td style="padding: 6px 0; color: #777; width: 40%; font-size: 14px;">Total LHUS</td>
      <td style="padding: 6px 0; color: #333; font-size: 14px;"><?= esc($total_detail) ?> item</td>
    </tr>
    <tr>
      <td style="padding: 6px 0; color: #777; font-size: 14px;">Diterima</td>
      <td style="padding: 6px 0; font-size: 14px;">
        <span style="color: #28a745; font-weight: bold;"><?= esc($jumlah_diterima) ?> item</span>
      </td>
    </tr>
    <?php if ($jumlah_ditolak > 0): ?>
      <tr>
        <td style="padding: 6px 0; color: #777; font-size: 14px;">Ditolak</td>
        <td style="padding: 6px 0; font-size: 14px;">
          <span style="color: #dc3545; font-weight: bold;"><?= esc($jumlah_ditolak) ?> item</span>
        </td>
      </tr>
    <?php endif; ?>
  </table>

  <!-- Detail Layanan -->
  <?php if (!empty($detail_items)): ?>
    <hr style="border: none; border-top: 1px solid #eee; margin: 20px 0;">

    <h3 style="color: #333; margin: 0 0 15px 0; font-size: 16px;">Detail Review</h3>

    <table width="100%" cellpadding="8" cellspacing="0" border="0" style="border-collapse: collapse; font-size: 14px;">
      <thead>
        <tr style="background-color: #f5f5f5;">
          <th style="text-align: left; padding: 10px; border-bottom: 2px solid #ddd;">No</th>
          <th style="text-align: left; padding: 10px; border-bottom: 2px solid #ddd;">Layanan</th>
          <th style="text-align: center; padding: 10px; border-bottom: 2px solid #ddd;">Status</th>
          <th style="text-align: left; padding: 10px; border-bottom: 2px solid #ddd;">Keterangan</th>
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
              <?php if ((int) ($item['aksi'] ?? 0) === 1): ?>
                <span style="color: #28a745; font-weight: bold;">Diterima</span>
              <?php elseif ((int) ($item['aksi'] ?? 0) === 2): ?>
                <span style="color: #dc3545; font-weight: bold;">Ditolak</span>
              <?php else: ?>
                <span style="color: #999;">-</span>
              <?php endif; ?>
            </td>
            <td style="padding: 8px 10px; border-bottom: 1px solid #eee; color: #555;">
              <?= !empty($item['keterangan']) ? esc($item['keterangan']) : '-' ?>
            </td>
          </tr>
        <?php endforeach; ?>
      </tbody>
    </table>
  <?php endif; ?>

  <hr style="border: none; border-top: 1px solid #eee; margin: 25px 0;">

  <p style="font-size: 13px; color: #777;">
    <?php if (!empty($all_accepted)): ?>
      Semua LHUS telah diterima. Status layanan telah diperbarui secara otomatis.
    <?php elseif ($jumlah_ditolak > 0): ?>
      Terdapat LHUS yang ditolak. Silakan periksa keterangan dan lakukan perbaikan yang diperlukan melalui menu <em>Hasil
        Pengujian</em>.
    <?php else: ?>
      Silakan periksa hasil review melalui SIMLAB.
    <?php endif; ?>
  </p>
</div>

<?= $this->endSection() ?>