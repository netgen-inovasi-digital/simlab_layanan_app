<?php

namespace Modules\Notifications\Models;

use CodeIgniter\Database\BaseConnection;

/**
 * NotificationRecipientModel
 * 
 * Mengelola query untuk mencari penerima notifikasi (email).
 * Digunakan oleh semua service notifikasi di module ini.
 * 
 * @package Modules\Notifications\Models
 */
class NotificationRecipientModel
{
  // Role ID Constants
  const ROLE_ADMIN = 1;
  const ROLE_MANAJER_TEKNIS = 4;
  const ROLE_PENYELIA = 6;

  /** @var BaseConnection */
  protected $db;

  public function __construct()
  {
    $this->db = \Config\Database::connect();
  }

  /**
   * Ambil daftar email unik berdasarkan role_id dari tabel account
   * Hanya user aktif (status_user=1) dengan email valid
   * 
   * @param int $roleId Role ID (1=Admin, 4=Manajer Teknis, dst)
   * @return array Array email unik
   */
  public function getEmailsByRole(int $roleId): array
  {
    try {
      $result = $this->db->table('account')
        ->select('email')
        ->where('role_id', $roleId)
        ->where('status_user', 1)
        ->where('email IS NOT NULL')
        ->where('email !=', '')
        ->get()
        ->getResult();

      return $this->extractUniqueEmails($result);
    } catch (\Exception $e) {
      log_message('error', 'NotificationRecipientModel::getEmailsByRole error: ' . $e->getMessage());
      return [];
    }
  }

  /**
   * Ambil email anggota tim berdasarkan item keranjang
   * Query r_tim JOIN account untuk mendapatkan email user
   * yang ditugaskan mengelola layanan yang dipesan
   * 
   * @param array $keranjang Item-item di keranjang (harus punya key 'kode')
   * @param int|null $roleId Filter role (4=Manajer Teknis). Null = semua role
   * @return array Array email unik
   */
  public function getEmailsByKeranjang(array $keranjang, ?int $roleId = null): array
  {
    try {
      $ujiKodes = [];
      foreach ($keranjang as $item) {
        if (isset($item['kode']) && !empty($item['kode'])) {
          $ujiKodes[] = $item['kode'];
        }
      }

      if (empty($ujiKodes)) {
        log_message('warning', 'NotificationRecipientModel::getEmailsByKeranjang - No uji_kode found');
        return [];
      }

      $builder = $this->db->table('r_tim as tim')
        ->select('acc.email')
        ->join('account as acc', 'acc.user_id = tim.user_id', 'inner')
        ->whereIn('tim.uji_kode', array_unique($ujiKodes))
        ->where('acc.email IS NOT NULL')
        ->where('acc.email !=', '')
        ->where('acc.status_user', 1)
        ->distinct();

      if ($roleId !== null) {
        $builder->where('acc.role_id', $roleId);
      }

      $result = $builder->get()->getResult();

      return $this->extractUniqueEmails($result);
    } catch (\Exception $e) {
      log_message('error', 'NotificationRecipientModel::getEmailsByKeranjang error: ' . $e->getMessage());
      return [];
    }
  }

  /**
   * Extract email unik dari result set
   * 
   * @param array $rows Database result rows
   * @return array Array email unik
   */
  protected function extractUniqueEmails(array $rows): array
  {
    $emails = [];
    foreach ($rows as $row) {
      if (!empty($row->email) && !in_array($row->email, $emails)) {
        $emails[] = $row->email;
      }
    }
    return $emails;
  }
}
