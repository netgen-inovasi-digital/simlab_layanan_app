<?php

namespace Modules\Notifications\Controllers;

use App\Services\EmailServices;
use Modules\Notifications\Models\NotificationRecipientModel;

/**
 * Controller untuk mengirim notifikasi email terkait pesanan baru.
 * 
 * Digunakan oleh:
 * - KeranjangBase (pelanggan checkout) → kirim ke admin + manajer teknis
 * - KeranjangAdmin (admin checkout) → kirim ke manajer teknis saja
 * 
 * @package Modules\Notifications\Controllers
 */
class OrderNotificationController
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
   * Kirim notifikasi pesanan baru berdasarkan siapa yang trigger checkout
   * 
   * @param int $kode_layanan Kode layanan yang baru dibuat
   * @param object $userRow Data user (pelanggan) yang terkait pesanan
   * @param float $totalBiaya Total biaya pesanan
   * @param array $keranjang Item-item yang dipesan
   * @param string $triggeredBy 'pelanggan' atau 'admin'
   */
  public function sendNewOrderNotification(
    int $kode_layanan,
    object $userRow,
    float $totalBiaya,
    array $keranjang,
    string $triggeredBy = 'pelanggan'
  ): void {
    try {
      $orderData = $this->prepareOrderData($kode_layanan, $userRow, $totalBiaya, $keranjang);

      if ($triggeredBy === 'pelanggan') {
        // Pelanggan checkout → kirim ke admin DAN manajer teknis
        $this->sendToAdmins($orderData);
        $this->sendToManagers($orderData, $keranjang);
      } elseif ($triggeredBy === 'admin') {
        // Admin checkout → hanya kirim ke manajer teknis
        $this->sendToManagers($orderData, $keranjang);
      }

      log_message('info', "Order notification completed. Triggered by: {$triggeredBy}");
    } catch (\Exception $e) {
      log_message('error', 'OrderNotificationController error: ' . $e->getMessage());
    }
  }

  /**
   * Kirim email ke semua Admin (role_id=1)
   */
  protected function sendToAdmins(array $orderData): void
  {
    $adminEmails = $this->recipientModel->getEmailsByRole(NotificationRecipientModel::ROLE_ADMIN);
    log_message('info', 'Sending to ' . count($adminEmails) . ' admin(s)');

    foreach ($adminEmails as $email) {
      if (!empty($email)) {
        $this->emailService->sendNewOrderNotification($email, $orderData, 'admin');
        $this->emailService->clearEmail();
      }
    }
  }

  /**
   * Kirim email ke Manajer Teknis (role_id=4) yang mengelola layanan di keranjang
   */
  protected function sendToManagers(array $orderData, array $keranjang): void
  {
    $managerEmails = $this->recipientModel->getEmailsByKeranjang($keranjang, NotificationRecipientModel::ROLE_MANAJER_TEKNIS);
    log_message('info', 'Sending to ' . count($managerEmails) . ' manager(s)');

    foreach ($managerEmails as $email) {
      if (!empty($email)) {
        $this->emailService->sendNewOrderNotification($email, $orderData, 'manajer');
        $this->emailService->clearEmail();
      }
    }
  }

  /**
   * Siapkan data pesanan untuk template email
   */
  protected function prepareOrderData(int $kode_layanan, object $userRow, float $totalBiaya, array $keranjang): array
  {
    $detailItems = [];
    foreach ($keranjang as $item) {
      $detailItems[] = [
        'nama_layanan' => $item['nama_layanan'] ?? $item['nama'] ?? 'Layanan',
        'jumlah' => $item['jumlah'] ?? 1,
        'biaya' => $item['biaya'] ?? 0,
        'metode_nama' => $item['metode_nama'] ?? null,
      ];
    }

    return [
      'kode_layanan' => $kode_layanan,
      'nama_pelanggan' => $userRow->user_name ?? 'Pelanggan',
      'email_pelanggan' => $userRow->user_email ?? '',
      'telpon_pelanggan' => $userRow->user_telpon ?? '-',
      'total_biaya' => $totalBiaya,
      'tanggal_checkout' => date('Y-m-d H:i:s'),
      'detail_items' => $detailItems,
    ];
  }
}
