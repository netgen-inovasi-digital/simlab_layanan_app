<?php

namespace Modules\Notifications\Models;

use CodeIgniter\Database\BaseConnection;

/**
 * ReviewDataModel
 * 
 * Mengelola query database terkait data review/kaji ulang layanan
 * untuk keperluan notifikasi email.
 * 
 * @package Modules\Notifications\Models
 */
class ReviewDataModel
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
   * @return object|null Row data atau null jika tidak ditemukan
   */
  public function getLayananWithPelanggan($kodeLayanan): ?object
  {
    return $this->db->table('t_layanan as l')
      ->select("
        l.kode_layanan,
        l.no_invoice,
        l.user_id,
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
   * Ambil semua detail layanan beserta status review dan nama metode
   * 
   * @param int|string $kodeLayanan Kode layanan
   * @return array Array of detail rows
   */
  public function getDetailsByKodeLayanan($kodeLayanan): array
  {
    return $this->db->table('t_layanan_detil as d')
      ->select('d.nama_layanan, d.status_layanan, d.metode_pengujian, m.nama AS metode_nama')
      ->join('r_metode m', 'm.metode_kode = d.metode_pengujian', 'left')
      ->where('d.kode_layanan', $kodeLayanan)
      ->orderBy('d.uji_kode', 'ASC')
      ->get()
      ->getResult();
  }
}
