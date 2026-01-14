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
    $this->userModel = new MyModel('account_users');
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
    $builder->select('p.*, l.kode_layanan, l.user_email, l.no_invoice, l.tanggal_checkout, l.user_id');
    $builder->join('t_layanan as l', 'p.kode_layanan = l.kode_layanan', 'inner');
    $builder->where('p.no_invoice IS NOT NULL');
    $builder->where('l.user_email', $email);
    $builder->orderBy('p.kode_bayar', 'DESC');

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
    return $this->getDataById('kode_bayar', $bayarId);
  }

  /**
   * Update data pembayaran berdasarkan primary key.
   */
  public function updatePembayaran($bayarId, array $data): bool
  {
    return (bool) $this->updateData($data, 'kode_bayar', $bayarId);
  }

  /**
   * Map total biaya detail layanan yang ditampilkan.
   * Menggunakan logic:
   * - status_layanan = 1 (sedang diterima, mungkin belum lunas)
   * - OR status_lunas IS NOT NULL (sudah pernah dibayar/lunas)
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
      ->groupStart()
      ->where('status_layanan', 1)
      ->orWhere('status_lunas IS NOT NULL', null, false)
      ->groupEnd()
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
}
