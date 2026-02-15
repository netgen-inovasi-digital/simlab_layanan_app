<?= $this->extend('email/layouts/master') ?>
<?= $this->section('content') ?>

<div style="padding: 20px 0;">
  <h2 style="color: #333; margin: 0 0 20px 0; font-size: 22px;">
    LHUS Telah Diverifikasi
  </h2>

  <p style="font-size: 15px; color: #333; margin-bottom: 15px;">
    Halo <strong>Admin</strong>,
  </p>

  <p style="font-size: 14px; color: #555; line-height: 1.6; margin-bottom: 20px;">
    Seluruh LHUS (Laporan Hasil Uji Sementara) untuk layanan berikut telah
    <strong>diterima</strong> oleh Manajer Teknis.
    Layanan ini siap untuk dibuatkan LHU (Laporan Hasil Uji).
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
      <td style="padding: 6px 0; color: #777; width: 40%; font-size: 14px;">Pelanggan</td>
      <td style="padding: 6px 0; color: #333; font-size: 14px;"><?= esc($nama_pelanggan) ?></td>
    </tr>
    <tr>
      <td style="padding: 6px 0; color: #777; font-size: 14px;">Total Layanan</td>
      <td style="padding: 6px 0; color: #333; font-size: 14px; font-weight: bold;"><?= esc($total_detail) ?> item
        diterima</td>
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
              <span style="font-weight: bold;">Diterima</span>
            </td>
          </tr>
        <?php endforeach; ?>
      </tbody>
    </table>
  <?php endif; ?>

  <hr style="border: none; border-top: 1px solid #eee; margin: 25px 0;">

  <p style="font-size: 14px; color: #555; line-height: 1.6;">
    Silakan buat dan unggah LHU melalui menu <em>Pelaksanaan</em> di SIMLAB.
  </p>

  <p style="font-size: 13px; color: #777; margin-top: 15px;">
    Status layanan telah diperbarui secara otomatis menjadi <strong>LHUS Disetujui</strong>.
  </p>
</div>

<?= $this->endSection() ?>