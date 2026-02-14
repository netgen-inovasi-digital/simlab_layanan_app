<?php

namespace Modules\Notifications\Models;

use CodeIgniter\Database\BaseConnection;

/**
 * LhusDataModel
 * 
 * Mengelola query database terkait data LHUS
 * untuk keperluan notifikasi email.
 * 
 * @package Modules\Notifications\Models
 */
class LhusDataModel
{
  /** @var BaseConnection */
  protected $db;

  public function __construct()
  {
    $this->db = \Config\Database::connect();
  }

  /**
   * Ambil data parent layanan beserta info pelanggan
   * 
   * @param int|string $kodeLayanan Kode layanan
   * @return object|null
   */
  public function getLayananWithPelanggan($kodeLayanan): ?object
  {
    return $this->db->table('t_layanan as l')
      ->select("
        l.kode_layanan,
        l.no_invoice,
        l.user_id,
        l.status_layanan,
        COALESCE(au.user_name, a.nama, l.user_email, '-') AS nama_pelanggan,
        COALESCE(au.user_email, l.user_email, '') AS email_pelanggan
      ", false)
      ->join('account_users as au', 'au.user_id = l.user_id', 'left')
      ->join('account as a', 'a.user_id = l.user_id', 'left')
      ->where('l.kode_layanan', $kodeLayanan)
      ->get()
      ->getRow();
  }

  /**
   * Ambil detail layanan LHUS beserta status files dan catatan
   * 
   * @param int|string $kodeLayanan Kode layanan
   * @return array Array of detail rows
   */
  public function getLhusDetailsByKodeLayanan($kodeLayanan): array
  {
    return $this->db->table('t_layanan_detil as d')
      ->select('d.kode, d.nama_layanan, d.files, d.metode_pengujian, m.nama AS metode_nama, lhus.catatan, lhus.status AS lhus_status')
      ->join('r_metode m', 'm.metode_kode = d.metode_pengujian', 'left')
      ->join('(SELECT kode, catatan, status FROM t_files_lhus WHERE file_id IN (SELECT MAX(file_id) FROM t_files_lhus GROUP BY kode)) lhus', 'lhus.kode = d.kode', 'left')
      ->where('d.kode_layanan', $kodeLayanan)
      ->where('d.status_layanan', 1)
      ->orderBy('d.kode', 'ASC')
      ->get()
      ->getResult();
  }

  /**
   * Ambil detail layanan LHUS yang relevan dengan user tertentu (via r_tim)
   * 
   * @param int|string $kodeLayanan Kode layanan
   * @param int $userId User ID (penyelia)
   * @return array Array of detail rows
   */
  public function getLhusDetailsByKodeLayananAndUser($kodeLayanan, int $userId): array
  {
    return $this->db->table('t_layanan_detil as d')
      ->select('d.kode, d.nama_layanan, d.files, d.metode_pengujian, m.nama AS metode_nama')
      ->join('r_metode m', 'm.metode_kode = d.metode_pengujian', 'left')
      ->join('r_tim rt', 'rt.uji_kode = d.uji_kode', 'inner')
      ->where('d.kode_layanan', $kodeLayanan)
      ->where('d.status_layanan', 1)
      ->where('rt.user_id', $userId)
      ->orderBy('d.kode', 'ASC')
      ->get()
      ->getResult();
  }

  /**
   * Ambil nama penyelia berdasarkan user_id
   * 
   * @param int $userId
   * @return string
   */
  public function getPenyeliaName(int $userId): string
  {
    $row = $this->db->table('account')
      ->select('nama')
      ->where('user_id', $userId)
      ->get()
      ->getRow();

    return $row->nama ?? 'Penyelia';
  }
}
