<?php

namespace Modules\Notifications\Controllers;

use App\Services\EmailServices;
use Modules\Notifications\Models\NotificationRecipientModel;

/**
 * Controller untuk mengirim notifikasi email terkait verifikasi profil ULM.
 * 
 * Digunakan oleh:
 * - ProfilUser (pelanggan submit profil dengan identitas ULM) → kirim ke admin
 * 
 * @package Modules\Notifications\Controllers
 */
class ProfileNotificationController
{
  /** @var EmailServices */
  protected $emailService;

  /** @var NotificationRecipientModel */
  protected $recipientModel;

  public function __construct()
  {
    $this->emailService = new EmailServices();
    $this->recipientModel = new NotificationRecipientModel();
  }

  /**
   * Kirim notifikasi ke admin bahwa ada pelanggan yang mengajukan verifikasi ULM
   * 
   * @param object $userRow Data pelanggan (user_name, user_email, user_telpon)
   */
  public function sendUlmVerificationNotification(object $userRow): void
  {
    $profileData = $this->prepareProfileData($userRow);

    $adminEmails = $this->recipientModel->getEmailsByRole(NotificationRecipientModel::ROLE_ADMIN);

    foreach ($adminEmails as $email) {
      if (!empty($email)) {
        $this->emailService->sendProfileVerificationNotification($email, $profileData);
        $this->emailService->clearEmail();
      }
    }
  }

  /**
   * Siapkan data profil untuk template email
   */
  protected function prepareProfileData(object $userRow): array
  {
    return [
      'nama_pelanggan' => $userRow->user_name ?? 'Pelanggan',
      'email_pelanggan' => $userRow->user_email ?? '',
      'telpon_pelanggan' => $userRow->user_telpon ?? '-',
    ];
  }
}
