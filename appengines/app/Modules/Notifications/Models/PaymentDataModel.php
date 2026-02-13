<?php

namespace Modules\Notifications\Models;

use CodeIgniter\Database\BaseConnection;

/**
 * PaymentDataModel
 * 
 * Mengelola query database terkait data pembayaran
 * untuk keperluan notifikasi email.
 * 
 * @package Modules\Notifications\Models
 */
class PaymentDataModel
{
  /** @var BaseConnection */
  protected $db;

  public function __construct()
  {
    $this->db = \Config\Database::connect();
  }

  /**
   * Ambil data pembayaran beserta relasi layanan
   * 
   * @param int|string $kodeBayar Kode bayar (PK t_pembayaran)
   * @return object|null Row data atau null jika tidak ditemukan
   */
  public function getPaymentWithLayanan($kodeBayar): ?object
  {
    return $this->db->table('t_pembayaran as p')
      ->select('p.*, l.kode_layanan, l.user_email, l.user_id, l.no_invoice, l.tanggal_checkout')
      ->join('t_layanan as l', 'p.kode_layanan = l.kode_layanan', 'inner')
      ->where('p.kode_bayar', $kodeBayar)
      ->get()
      ->getFirstRow();
  }

  /**
   * Cari user berdasarkan user_id atau email dari tabel account_users
   * 
   * @param int|null $userId ID user
   * @param string|null $email Email user (fallback jika userId kosong)
   * @return object|null Row data user atau null
   */
  public function findUser(?int $userId, ?string $email): ?object
  {
    if (!empty($userId)) {
      $user = $this->db->table('account_users')
        ->where('user_id', $userId)
        ->get()
        ->getFirstRow();
      if ($user) {
        return $user;
      }
    }

    if (!empty($email)) {
      return $this->db->table('account_users')
        ->where('user_email', $email)
        ->get()
        ->getFirstRow();
    }

    return null;
  }

  /**
   * Hitung total biaya dari detail layanan yang diterima/sudah lunas
   * 
   * @param int|string $kodeLayanan Kode layanan
   * @return float Total biaya
   */
  public function calculateTotal($kodeLayanan): float
  {
    $result = $this->db->table('t_layanan_detil')
      ->selectSum('biaya')
      ->where('kode_layanan', $kodeLayanan)
      ->groupStart()
      ->where('status_lunas IS NOT NULL', null, false)
      ->orWhere('status_layanan', 1)
      ->groupEnd()
      ->get()
      ->getFirstRow();

    return (float) ($result->biaya ?? 0);
  }
}
