<?php

namespace Modules\PembayaranUser\Models;

use App\Models\MyModel;

/**
 * PembayaranUserModel
 *
 * Membungkus seluruh interaksi database untuk modul Pembayaran User agar
 * controller hanya fokus pada alur bisnis.
 */
class PembayaranUserModel extends MyModel
{
  /** @var \CodeIgniter\Database\BaseConnection */
  protected $db;

  /** @var MyModel */
  protected $userModel;

  /**
   * Inisialisasi model utama dan relasi yang dibutuhkan.
   */
  public function __construct()
  {
    parent::__construct('t_pembayaran');
    $this->db = \Config\Database::connect();
    $this->userModel = new MyModel('simlab_account_users');
  }

  /**
   * Ambil data user berdasarkan user_id.
   */
  public function getUserById(int $userId)
  {
    return $this->userModel->getDataById('user_id', $userId);
  }

  /**
   * Ambil daftar pembayaran user berdasarkan email akun layanan.
   */
  public function getPaymentListByEmail(string $email): array
  {
    $builder = $this->db->table('t_pembayaran as p');
    $builder->select('p.*, l.lnKode, l.lnAccEmail, l.lnNoTransaksi, l.lnTgl, l.user_id');
    $builder->join('simlab_t_layanan as l', 'p.bayarLnKode = l.lnKode', 'inner');
    $builder->where('p.bayarInvoiceNo IS NOT NULL');
    $builder->where('l.lnAccEmail', $email);
    $builder->orderBy('p.bayarKode', 'DESC');

    return $builder->get()->getResult();
  }

  /**
   * Cari pemesan berdasarkan user_id atau email layanan.
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
   * Ambil data pembayaran berdasarkan primary key.
   */
  public function getPembayaranById($bayarId)
  {
    return $this->getDataById('bayarKode', $bayarId);
  }

  /**
   * Update data pembayaran berdasarkan primary key.
   */
  public function updatePembayaran($bayarId, array $data): bool
  {
    return (bool) $this->updateData($data, 'bayarKode', $bayarId);
  }
}
