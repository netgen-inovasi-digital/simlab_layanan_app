<?php

namespace Modules\Notifications\Controllers;

use App\Services\EmailServices;
use Modules\Notifications\Models\NotificationRecipientModel;
use Modules\Notifications\Models\PaymentDataModel;

/**
 * Controller untuk mengirim notifikasi email terkait pembayaran.
 * 
 * Digunakan oleh:
 * - PembayaranAdmin (upload & kirim invoice) → kirim ke pelanggan
 * - PembayaranAdmin (terima/tolak verifikasi) → kirim ke pelanggan
 * - PembayaranUser (upload bukti bayar) → kirim ke admin
 * 
 * @package Modules\Notifications\Controllers
 */
class PaymentNotificationController
{
  /** @var EmailServices */
  protected $emailService;

  /** @var NotificationRecipientModel */
  protected $recipientModel;

  /** @var PaymentDataModel */
  protected $paymentModel;

  public function __construct()
  {
    $this->emailService = new EmailServices();
    $this->recipientModel = new NotificationRecipientModel();
    $this->paymentModel = new PaymentDataModel();
  }

  /**
   * 1) Kirim notifikasi invoice masuk ke pelanggan
   * Trigger: Admin upload & kirim invoice
   * 
   * @param int|string $kodeBayar Kode bayar (primary key t_pembayaran)
   */
  public function sendInvoiceNotification($kodeBayar): void
  {
    try {
      $paymentData = $this->paymentModel->getPaymentWithLayanan($kodeBayar);
      if (!$paymentData) {
        log_message('warning', 'PaymentNotification: data pembayaran tidak ditemukan - ' . $kodeBayar);
        return;
      }

      // Cari user pelanggan
      $user = $this->paymentModel->findUser($paymentData->user_id ?? null, $paymentData->user_email ?? null);
      if (!$user || empty($user->user_email)) {
        log_message('warning', 'PaymentNotification: email pelanggan tidak ditemukan - kodeBayar: ' . $kodeBayar);
        return;
      }

      // Hitung total biaya
      $totalBiaya = $this->paymentModel->calculateTotal($paymentData->kode_layanan);

      $emailData = [
        'no_invoice' => $paymentData->no_invoice ?? '-',
        'kode_layanan' => $paymentData->kode_layanan ?? '-',
        'nama_pelanggan' => $user->user_name ?? 'Pelanggan',
        'total_biaya' => 'Rp ' . number_format($totalBiaya, 0, ',', '.'),
        'tanggal_invoice' => date('d-m-Y H:i'),
      ];

      $this->emailService->sendInvoiceNotification($user->user_email, $emailData);
      $this->emailService->clearEmail();

      log_message('info', 'Invoice notification sent to: ' . $user->user_email);
    } catch (\Exception $e) {
      log_message('error', 'PaymentNotificationController::sendInvoiceNotification error: ' . $e->getMessage());
    }
  }

  /**
   * 2) Kirim notifikasi bukti bayar baru ke admin
   * Trigger: Pelanggan upload bukti bayar
   * 
   * @param int|string $kodeBayar Kode bayar (primary key t_pembayaran)
   */
  public function sendPaymentProofNotification($kodeBayar): void
  {
    try {
      $paymentData = $this->paymentModel->getPaymentWithLayanan($kodeBayar);
      if (!$paymentData) {
        log_message('warning', 'PaymentNotification: data pembayaran tidak ditemukan - ' . $kodeBayar);
        return;
      }

      // Cari user pelanggan
      $user = $this->paymentModel->findUser($paymentData->user_id ?? null, $paymentData->user_email ?? null);
      $namaPelanggan = $user->user_name ?? ($paymentData->user_email ?? 'Pelanggan');
      $emailPelanggan = $user->user_email ?? ($paymentData->user_email ?? '-');

      // Hitung total biaya
      $totalBiaya = $this->paymentModel->calculateTotal($paymentData->kode_layanan);

      $emailData = [
        'no_invoice' => $paymentData->no_invoice ?? '-',
        'kode_layanan' => $paymentData->kode_layanan ?? '-',
        'nama_pelanggan' => $namaPelanggan,
        'email_pelanggan' => $emailPelanggan,
        'total_biaya' => 'Rp ' . number_format($totalBiaya, 0, ',', '.'),
        'tanggal_upload' => date('d-m-Y H:i'),
      ];

      // Kirim ke semua admin
      $adminEmails = $this->recipientModel->getEmailsByRole(NotificationRecipientModel::ROLE_ADMIN);

      foreach ($adminEmails as $email) {
        if (!empty($email)) {
          $this->emailService->sendPaymentProofNotification($email, $emailData);
          $this->emailService->clearEmail();
        }
      }

      log_message('info', 'Payment proof notification sent to ' . count($adminEmails) . ' admin(s)');
    } catch (\Exception $e) {
      log_message('error', 'PaymentNotificationController::sendPaymentProofNotification error: ' . $e->getMessage());
    }
  }

  /**
   * 3) Kirim notifikasi hasil verifikasi bukti bayar ke pelanggan
   * Trigger: Admin memverifikasi (terima/tolak) bukti bayar
   * 
   * @param int|string $kodeBayar Kode bayar (primary key t_pembayaran)
   * @param bool $accepted True = diterima, False = ditolak
   * @param string|null $catatan Catatan penolakan (jika ditolak)
   */
  public function sendVerificationResultNotification($kodeBayar, bool $accepted, ?string $catatan = null): void
  {
    try {
      $paymentData = $this->paymentModel->getPaymentWithLayanan($kodeBayar);
      if (!$paymentData) {
        log_message('warning', 'PaymentNotification: data pembayaran tidak ditemukan - ' . $kodeBayar);
        return;
      }

      // Cari user pelanggan
      $user = $this->paymentModel->findUser($paymentData->user_id ?? null, $paymentData->user_email ?? null);
      if (!$user || empty($user->user_email)) {
        log_message('warning', 'PaymentNotification: email pelanggan tidak ditemukan - kodeBayar: ' . $kodeBayar);
        return;
      }

      $emailData = [
        'no_invoice' => $paymentData->no_invoice ?? '-',
        'kode_layanan' => $paymentData->kode_layanan ?? '-',
        'nama_pelanggan' => $user->user_name ?? 'Pelanggan',
        'accepted' => $accepted,
        'catatan' => $catatan,
      ];

      $this->emailService->sendPaymentVerificationResult($user->user_email, $emailData);
      $this->emailService->clearEmail();

      $status = $accepted ? 'diterima' : 'ditolak';
      log_message('info', "Payment verification result ({$status}) sent to: " . $user->user_email);
    } catch (\Exception $e) {
      log_message('error', 'PaymentNotificationController::sendVerificationResultNotification error: ' . $e->getMessage());
    }
  }

}
