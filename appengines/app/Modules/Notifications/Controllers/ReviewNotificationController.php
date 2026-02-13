<?php

namespace Modules\Notifications\Controllers;

use App\Services\EmailServices;
use Modules\Notifications\Models\NotificationRecipientModel;
use Modules\Notifications\Models\ReviewDataModel;

/**
 * Controller untuk mengirim notifikasi email terkait review/kaji ulang layanan.
 * 
 * Digunakan oleh:
 * - KajiUlang (Manajer Teknis selesai review semua layanan dalam 1 transaksi) → kirim ke admin
 * 
 * @package Modules\Notifications\Controllers
 */
class ReviewNotificationController
{
  /** @var EmailServices */
  protected $emailService;

  /** @var NotificationRecipientModel */
  protected $recipientModel;

  /** @var ReviewDataModel */
  protected $reviewDataModel;

  public function __construct()
  {
    $this->emailService = new EmailServices();
    $this->recipientModel = new NotificationRecipientModel();
    $this->reviewDataModel = new ReviewDataModel();
  }

  /**
   * Kirim notifikasi ke admin bahwa semua layanan dalam transaksi telah direview
   * 
   * Trigger: pendingRemaining === 0 di approveDetail() atau rejectDetail()
   * 
   * @param int|string $kodeLayanan Kode layanan (parent)
   */
  public function sendReviewCompleteNotification($kodeLayanan): void
  {
    $reviewData = $this->prepareReviewData($kodeLayanan);

    if (empty($reviewData)) {
      return;
    }

    $adminEmails = $this->recipientModel->getEmailsByRole(NotificationRecipientModel::ROLE_ADMIN);

    foreach ($adminEmails as $email) {
      if (!empty($email)) {
        $this->emailService->sendReviewCompleteNotification($email, $reviewData);
        $this->emailService->clearEmail();
      }
    }
  }

  /**
   * Menggunakan ReviewDataModel untuk query t_layanan + t_layanan_detil + account
   * 
   * @param int|string $kodeLayanan Kode layanan
   * @return array Data review, kosong jika data tidak ditemukan
   */
  protected function prepareReviewData($kodeLayanan): array
  {
    // Ambil data parent layanan + info pelanggan
    $layanan = $this->reviewDataModel->getLayananWithPelanggan($kodeLayanan);

    if (!$layanan) {
      return [];
    }

    // Ambil semua detail layanan beserta status review
    $details = $this->reviewDataModel->getDetailsByKodeLayanan($kodeLayanan);

    // Hitung ringkasan
    $totalDetail = count($details);
    $jumlahDiterima = 0;
    $jumlahDitolak = 0;
    $detailItems = [];

    foreach ($details as $d) {
      $status = (int) ($d->status_layanan ?? 0);
      if ($status === 1) {
        $jumlahDiterima++;
      } elseif ($status === 2) {
        $jumlahDitolak++;
      }

      $detailItems[] = [
        'nama_layanan' => $d->nama_layanan ?? '-',
        'metode_nama' => $d->metode_nama ?? '',
        'status_layanan' => $status,
      ];
    }

    return [
      'kode_layanan' => $layanan->kode_layanan,
      'no_invoice' => $layanan->no_invoice ?? '',
      'nama_pelanggan' => $layanan->nama_pelanggan,
      'email_pelanggan' => $layanan->email_pelanggan,
      'total_detail' => $totalDetail,
      'jumlah_diterima' => $jumlahDiterima,
      'jumlah_ditolak' => $jumlahDitolak,
      'detail_items' => $detailItems,
    ];
  }
}
