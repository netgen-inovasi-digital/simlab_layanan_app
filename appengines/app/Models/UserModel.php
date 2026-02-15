<?php

namespace App\Models;

/**
 * UserModel
 * Handle operasi user/account spesifik
 * Global model - bisa dipakai di semua modul
 */
class UserModel extends MyModel
{
  protected $table = 'account_users';
  protected $primaryKey = 'user_id';

  public function __construct()
  {
    parent::__construct($this->table);
  }

  /**
   * Mengambil data user berdasarkan user_id
   * 
   * @param int $userId
   * @return object|null
   */
  public function getUserById($userId)
  {
    return $this->getDataById($this->primaryKey, $userId);
  }

  /**
   * Cek apakah email sudah digunakan oleh user lain
   * 
   * @param string $email Email yang akan dicek
   * @param int $excludeUserId User ID yang dikecualikan dari pengecekan
   * @return bool True jika email sudah digunakan
   */
  public function isEmailExists($email, $excludeUserId = null)
  {
    $email = strtolower(trim($email));

    $this->builder->where('LOWER(user_email)', $email);

    if ($excludeUserId !== null) {
      $this->builder->where($this->primaryKey . ' !=', (int) $excludeUserId);
    }

    $count = $this->builder->countAllResults();

    return $count > 0;
  }

  /**
   * Update data user
   * 
   * @param array $data Data yang akan diupdate
   * @param int $userId User ID
   * @return bool
   */
  public function updateUser($data, $userId)
  {
    return $this->updateData($data, $this->primaryKey, $userId);
  }

  /**
   * Mengambil profil user lengkap dengan data tambahan
   * 
   * @param int $userId
   * @return object|null
   */
  public function getUserProfile($userId)
  {
    $user = $this->getUserById($userId);

    if (!$user) {
      return null;
    }

    return (object) [
      'user_name' => $user->user_name,
      'user_email' => $user->user_email,
      'user_telpon' => $user->user_telpon ?? '',
      'user_instansi' => $user->user_instansi ?? '',
      'user_identity' => $user->user_identity,
      'verifikasi' => $user->verifikasi ?? 0,
      'status_user' => $user->status_user,
      'role_id' => $user->role_id,
      'bukti' => $user->bukti ?? '',
      'user_password' => $user->user_password ?? ''
    ];
  }

  /**
   * Validasi password user
   * 
   * @param int $userId
   * @param string $password
   * @return bool
   */
  public function verifyPassword($userId, $password)
  {
    $user = $this->getUserById($userId);

    if (!$user || empty($user->user_password)) {
      return false;
    }

    return password_verify($password, $user->user_password);
  }

  /**
   * Update status verifikasi user
   * 
   * @param int $userId
   * @param int $status 0 = menunggu verifikasi, 1 = terverifikasi, 2 = ditolak
   * @return bool
   */
  public function updateVerificationStatus($userId, $status)
  {
    return $this->updateUser(['verifikasi' => (int) $status], $userId);
  }

  /**
   * Update bukti user (untuk identitas ULM)
   * 
   * @param int $userId
   * @param string|null $buktiFilename
   * @return bool
   */
  public function updateBukti($userId, $buktiFilename)
  {
    return $this->updateUser(['bukti' => $buktiFilename], $userId);
  }

  /**
   * Get user by email
   * 
   * @param string $email
   * @return object|null
   */
  public function getUserByEmail($email)
  {
    $email = strtolower(trim($email));
    return $this->getDataById('LOWER(user_email)', $email);
  }
}
