<?php

namespace Modules\Notifications\Controllers;

use App\Services\EmailServices;
use Modules\Notifications\Models\NotificationRecipientModel;
use Modules\Notifications\Models\LhusDataModel;

/**
 * Controller untuk mengirim notifikasi email terkait LHU (Laporan Hasil Uji).
 * 
 * Mengelola notifikasi:
 * 1. LHU diterbitkan → dikirim ke Pelanggan (trigger: Admin klik "Upload & Kirim")
 * 
 * @package Modules\Notifications\Controllers
 */
class LhuNotificationController
{
  /** @var EmailServices */
  protected $emailService;

  /** @var NotificationRecipientModel */
  protected $recipientModel;

  /** @var LhusDataModel */
  protected $lhusDataModel;

  public function __construct()
  {
    $this->emailService = new EmailServices();
    $this->recipientModel = new NotificationRecipientModel();
    $this->lhusDataModel = new LhusDataModel();
  }

  /**
   * Kirim email ke Pelanggan bahwa LHU telah diterbitkan
   * 
   * Trigger: Admin klik "Upload & Kirim" di Pelaksanaan
   * Target: Pelanggan (email dari account_users)
   * 
   * @param int|string $kodeLayanan Kode layanan
   * @param string $tanggalTerbit Tanggal terbit LHU (formatted)
   */
  public function sendLhuPublishedToCustomer($kodeLayanan, string $tanggalTerbit): void
  {
    try {
      $data = $this->prepareLhuPublishedData($kodeLayanan, $tanggalTerbit);

      if (empty($data)) {
        log_message('warning', 'LhuNotificationController::sendLhuPublishedToCustomer - Data kosong untuk kode_layanan: ' . $kodeLayanan);
        return;
      }

      $customerEmail = $data['email_pelanggan'] ?? '';

      if (empty($customerEmail)) {
        log_message('warning', 'LhuNotificationController::sendLhuPublishedToCustomer - Tidak ada email pelanggan untuk kode_layanan: ' . $kodeLayanan);
        return;
      }

      $this->emailService->sendLhuPublishedNotification($customerEmail, $data);
      $this->emailService->clearEmail();

      log_message('info', 'LhuNotificationController: Notifikasi LHU published terkirim ke pelanggan (' . $customerEmail . ') untuk kode_layanan: ' . $kodeLayanan);
    } catch (\Throwable $e) {
      log_message('error', 'LhuNotificationController::sendLhuPublishedToCustomer error: ' . $e->getMessage());
    }
  }

  /**
   * Siapkan data untuk notifikasi LHU published ke Pelanggan
   * 
   * @param int|string $kodeLayanan
   * @param string $tanggalTerbit
   * @return array
   */
  protected function prepareLhuPublishedData($kodeLayanan, string $tanggalTerbit): array
  {
    $layanan = $this->lhusDataModel->getLayananWithPelanggan($kodeLayanan);
    if (!$layanan) {
      return [];
    }

    $details = $this->lhusDataModel->getLhusDetailsByKodeLayanan($kodeLayanan);

    $detailItems = [];
    foreach ($details as $d) {
      $detailItems[] = [
        'nama_layanan' => $d->nama_layanan ?? '-',
        'metode_nama' => $d->metode_nama ?? '',
      ];
    }

    return [
      'kode_layanan' => $layanan->kode_layanan,
      'no_invoice' => $layanan->no_invoice ?? '',
      'nama_pelanggan' => $layanan->nama_pelanggan,
      'email_pelanggan' => $layanan->email_pelanggan ?? '',
      'tanggal_terbit' => $tanggalTerbit,
      'detail_items' => $detailItems,
    ];
  }
}
