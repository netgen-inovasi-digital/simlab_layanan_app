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
    $this->userModel = new MyModel('simlab_account_users');
    $this->layananModel = new MyModel('simlab_t_layanan');
  }

  /**
   * Ambil daftar pembayaran untuk admin dengan relasi layanan.
   */
  public function getAdminPaymentList(?string $tanggalAwal = null, ?string $tanggalAkhir = null): array
  {
    $builder = $this->db->table('t_pembayaran as p');
    $builder->select('p.*, l.lnKode, l.lnAccEmail, l.lnNoTransaksi, l.lnTgl, l.lnStatus, l.user_id, l.jumlah_kaji_ulang');
    $builder->join('simlab_t_layanan as l', 'p.bayarLnKode = l.lnKode', 'inner');
    $builder->where('l.lnStatus >', 2);

    if (!empty($tanggalAwal) && !empty($tanggalAkhir)) {
      $builder->where('DATE(l.lnTgl) >=', $tanggalAwal);
      $builder->where('DATE(l.lnTgl) <=', $tanggalAkhir);
    } elseif (!empty($tanggalAwal)) {
      $builder->where('DATE(l.lnTgl) >=', $tanggalAwal);
    } elseif (!empty($tanggalAkhir)) {
      $builder->where('DATE(l.lnTgl) <=', $tanggalAkhir);
    }

    $builder->orderBy('p.bayarKode', 'DESC');
    return $builder->get()->getResult();
  }

  /**
   * Ambil user pemesan menggunakan user_id atau email lnAccEmail.
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
  public function getDetailLayananItems($lnKode): array
  {
    return $this->db->table('t_layanan_detil d')
      ->select('d.nama_layanan, d.jumlah, d.biaya, rl.nama_layanan AS ref_nama, rl.kode_alat, rl.diskon AS ref_diskon, alat.alatNama, metode.nama AS metode_nama')
      ->join('r_layanan_pengujian rl', 'rl.kode = d.uji_kode', 'left')
      ->join('simlab_r_alat alat', 'alat.alatKode = rl.kode_alat', 'left')
      ->join('r_metode metode', 'metode.metode_kode = d.metode_pengujian', 'left')
      ->where('d.kode_layanan', $lnKode)
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
    $builder->select('bayarKode');
    $builder->where('bayarInvoiceNo', $invoiceNo);

    if ($excludeId !== null && $excludeId !== '') {
      $builder->where('bayarKode !=', $excludeId);
    }

    return (bool) $builder->get()->getFirstRow();
  }

  /**
   * Update nomor transaksi di tabel layanan.
   */
  public function updateLayananNoTransaksi($lnKode, string $noInvoice): bool
  {
    if (empty($lnKode)) {
      return false;
    }

    return $this->db->table('simlab_t_layanan')
      ->where('lnKode', $lnKode)
      ->update(['lnNoTransaksi' => $noInvoice]);
  }

  /**
   * Ambil data layanan berdasarkan kode.
   */
  public function getLayananByKode($lnKode)
  {
    return $this->layananModel->getDataById('lnKode', $lnKode);
  }
}
