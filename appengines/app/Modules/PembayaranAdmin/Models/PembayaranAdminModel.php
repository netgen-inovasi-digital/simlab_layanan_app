<?php

namespace Modules\PembayaranAdmin\Models;

use App\Models\MyModel;

/**
 * PembayaranAdminModel
 *
 * Wrapper untuk table utama yang digunakan modul PembayaranAdmin.
 * Menggunakan MyModel sebagai base agar builder tetap konsisten.
 */
class PembayaranAdminModel extends MyModel
{
  /** @var \CodeIgniter\Database\BaseConnection */
  protected $db;

  /** @var MyModel */
  protected $userModel;

  /** @var MyModel */
  protected $layananModel;

  public function __construct()
  {
    parent::__construct('t_pembayaran');
    $this->db = \Config\Database::connect();
    $this->userModel = new MyModel('account_users');
    $this->layananModel = new MyModel('t_layanan');
  }

  /**
   * Ambil daftar pembayaran untuk admin dengan relasi layanan.
   */
  public function getAdminPaymentList(?string $tanggalAwal = null, ?string $tanggalAkhir = null): array
  {
    $builder = $this->db->table('t_pembayaran as p');
    $builder->select('p.*, l.kode_layanan, l.user_email, l.no_invoice, l.tanggal_checkout, l.status_layanan, l.user_id, l.jumlah_kaji_ulang');
    $builder->join('t_layanan as l', 'p.kode_layanan = l.kode_layanan', 'inner');
    $builder->where('l.status_layanan >', 2);

    if (!empty($tanggalAwal) && !empty($tanggalAkhir)) {
      $builder->where('DATE(l.tanggal_checkout) >=', $tanggalAwal);
      $builder->where('DATE(l.tanggal_checkout) <=', $tanggalAkhir);
    } elseif (!empty($tanggalAwal)) {
      $builder->where('DATE(l.tanggal_checkout) >=', $tanggalAwal);
    } elseif (!empty($tanggalAkhir)) {
      $builder->where('DATE(l.tanggal_checkout) <=', $tanggalAkhir);
    }

    $builder->orderBy('p.kode_bayar', 'DESC');
    return $builder->get()->getResult();
  }

  /**
   * Ambil user pemesan menggunakan user_id atau email user_email.
   */
  public function findPemesanUser(?int $userId, ?string $email)
  {
    if (!empty($userId)) {
      $user = $this->userModel->getDataById('user_id', $userId);
      if ($user) {
        return $user;
      }
    }

    if (!empty($email)) {
      $users = $this->userModel->getAllDataById(['user_email' => $email]);
      if (!empty($users)) {
        return is_array($users) ? ($users[0] ?? null) : $users;
      }
    }

    return null;
  }

  /**
   * Ambil detail layanan untuk modal detail.
   */
  public function getDetailLayananItems($kode_layanan): array
  {
    return $this->db->table('t_layanan_detil d')
      ->select('d.nama_layanan, d.jumlah, d.biaya, rl.nama_layanan AS ref_nama, rl.kode_alat, rl.diskon AS ref_diskon, alat.nama, metode.nama AS metode_nama')
      ->join('r_layanan_pengujian rl', 'rl.kode = d.uji_kode', 'left')
      ->join('r_alat alat', 'alat.kode = rl.kode_alat', 'left')
      ->join('r_metode metode', 'metode.metode_kode = d.metode_pengujian', 'left')
      ->where('d.kode_layanan', $kode_layanan)
      ->where('d.status_layanan', 1)
      ->get()->getResult();
  }

  /**
   * Map total biaya detail layanan yang sudah diterima (status_layanan=1).
   *
   * @return array<string,float>
   */
  public function getAcceptedDetailTotalMap(array $lnKodes): array
  {
    $lnKodes = array_values(array_unique(array_filter($lnKodes, static fn($v) => $v !== null && $v !== '')));
    if (empty($lnKodes)) {
      return [];
    }

    $rows = $this->db->table('t_layanan_detil')
      ->select('kode_layanan, SUM(biaya) AS total_biaya')
      ->whereIn('kode_layanan', $lnKodes)
      ->where('status_layanan', 1)
      ->groupBy('kode_layanan')
      ->get()->getResult();

    $map = [];
    foreach ($rows as $row) {
      $key = (string) ($row->kode_layanan ?? '');
      if ($key === '') {
        continue;
      }
      $map[$key] = (float) ($row->total_biaya ?? 0);
    }

    return $map;
  }

  /**
   * Cek ketersediaan nomor invoice.
   */
  public function invoiceNumberExists(string $invoiceNo, $excludeId = null): bool
  {
    $invoiceNo = trim($invoiceNo);
    if ($invoiceNo === '') {
      return false;
    }

    $builder = $this->db->table('t_pembayaran');
    $builder->select('kode_bayar');
    $builder->where('no_invoice', $invoiceNo);

    if ($excludeId !== null && $excludeId !== '') {
      $builder->where('kode_bayar !=', $excludeId);
    }

    return (bool) $builder->get()->getFirstRow();
  }

  /**
   * Update nomor transaksi di tabel layanan.
   */
  public function updateLayananNoTransaksi($kode_layanan, string $noInvoice): bool
  {
    if (empty($kode_layanan)) {
      return false;
    }

    return $this->db->table('t_layanan')
      ->where('kode_layanan', $kode_layanan)
      ->update(['no_invoice' => $noInvoice]);
  }

  /**
   * Ambil data layanan berdasarkan kode.
   */
  public function getLayananByKode($kode_layanan)
  {
    return $this->layananModel->getDataById('kode_layanan', $kode_layanan);
  }

  /**
   * Hitung total biaya dari detail layanan yang sudah diterima (status_layanan=1)
   * Layanan yang ditolak (status_layanan=2) tidak dihitung
   *
   * @param int $kode_layanan
   * @return float
   */
  public function calculateAcceptedTotal(int $kode_layanan): float
  {
    $result = $this->db->table('t_layanan_detil')
      ->selectSum('biaya')
      ->where('kode_layanan', $kode_layanan)
      ->where('status_layanan', 1)
      ->get()
      ->getRow();

    return (float) ($result->biaya ?? 0);
  }

  /**
   * Update total_biaya di t_pembayaran berdasarkan detail yang diterima
   *
   * @param int $kode_layanan
   * @return bool
   */
  public function updateTotalBiayaByAccepted(int $kode_layanan): bool
  {
    $totalBiaya = $this->calculateAcceptedTotal($kode_layanan);

    return $this->db->table('t_pembayaran')
      ->where('kode_layanan', $kode_layanan)
      ->update(['total_biaya' => $totalBiaya]);
  }
}
