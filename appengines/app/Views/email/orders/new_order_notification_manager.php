<?= $this->extend('email/layouts/master') ?>
<?= $this->section('content') ?>

<div style="padding: 20px 0;">
  <h2 style="color: #333; margin: 0 0 20px 0; font-size: 22px;">
    Pesanan Baru Memerlukan Pengecekan
  </h2>

  <p style="font-size: 15px; color: #333; margin-bottom: 15px;">
    Halo <strong>Manajer Teknis</strong>,
  </p>

  <p style="font-size: 14px; color: #555; line-height: 1.6; margin-bottom: 20px;">
    Ada pesanan baru yang memerlukan pengecekan dan tindak lanjut dari Anda.
  </p>

  <hr style="border: none; border-top: 1px solid #eee; margin: 20px 0;">

  <!-- Detail Layanan -->
  <?php if (!empty($detail_items)): ?>
    <h3 style="color: #333; margin: 0 0 15px 0; font-size: 16px;">Detail Layanan</h3>

    <table width="100%" cellpadding="8" cellspacing="0" border="0" style="border-collapse: collapse; font-size: 14px;">
      <thead>
        <tr style="background-color: #f5f5f5;">
          <th style="text-align: left; padding: 10px; border-bottom: 2px solid #ddd;">No</th>
          <th style="text-align: left; padding: 10px; border-bottom: 2px solid #ddd;">Layanan</th>
          <th style="text-align: center; padding: 10px; border-bottom: 2px solid #ddd;">Jumlah</th>
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
            <td style="padding: 8px 10px; text-align: center; border-bottom: 1px solid #eee;"><?= esc($item['jumlah']) ?>
            </td>
          </tr>
        <?php endforeach; ?>
      </tbody>
    </table>
  <?php else: ?>
    <p style="color: #999; font-size: 14px;">Detail layanan tidak tersedia.</p>
  <?php endif; ?>

  <hr style="border: none; border-top: 1px solid #eee; margin: 25px 0;">

  <!-- Tindakan -->
  <p style="font-size: 14px; color: #333; margin-bottom: 10px;"><strong>Tindakan Diperlukan:</strong></p>
  <ul style="font-size: 13px; color: #555; line-height: 1.8; margin: 5px 0 0 20px; padding: 0;">
    <li>Cek ketersediaan alat dan SDM</li>
    <li>Verifikasi spesifikasi layanan</li>
    <li>Update status di sistem setelah pengecekan</li>
  </ul>

  <hr style="border: none; border-top: 1px solid #eee; margin: 25px 0;">

  <p style="font-size: 13px; color: #777; text-align: center;">
    Waktu Pemesanan: <?= esc(date('d F Y, H:i', strtotime($tanggal_checkout))) ?> WITA
  </p>
</div>

<?= $this->endSection() ?>