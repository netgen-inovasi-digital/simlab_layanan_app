<?php

namespace Modules\Notifications\Controllers;

use App\Services\EmailServices;
use Modules\Notifications\Models\NotificationRecipientModel;
use Modules\Notifications\Models\LhusDataModel;

/**
 * Controller untuk mengirim notifikasi email terkait LHUS.
 * 
 * Mengelola 3 jenis notifikasi:
 * 1. LHUS siap ditinjau → dikirim ke Manajer Teknis (trigger: Penyelia klik "Kirim")
 * 2. Review LHUS selesai → dikirim ke Penyelia (trigger: MT klik "Selesai")
 * 3. LHUS semua diterima → dikirim ke Admin (trigger: MT menerima semua item secara global)
 * 
 * @package Modules\Notifications\Controllers
 */
class LhusNotificationController
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
   * Notifikasi #1: Kirim email ke Manajer Teknis bahwa LHUS siap ditinjau
   * 
   * Trigger: Penyelia klik "Kirim" di modal HasilPengujian
   * Target: Manajer Teknis yang terkait layanan ini (via r_tim)
   * 
   * @param int|string $kodeLayanan Kode layanan
   * @param int $penyeliaUserId User ID penyelia yang mengirim
   */
  public function sendLhusReadyForReviewNotification($kodeLayanan, int $penyeliaUserId): void
  {
    try {
      $data = $this->prepareLhusReadyData($kodeLayanan, $penyeliaUserId);

      if (empty($data)) {
        log_message('warning', 'LhusNotificationController::sendLhusReadyForReview - Data kosong untuk kode_layanan: ' . $kodeLayanan);
        return;
      }

      // Ambil email Manajer Teknis yang terkait layanan ini
      $mtEmails = $this->recipientModel->getEmailsByLayanan(
        $kodeLayanan,
        NotificationRecipientModel::ROLE_MANAJER_TEKNIS
      );

      if (empty($mtEmails)) {
        log_message('warning', 'LhusNotificationController::sendLhusReadyForReview - Tidak ada email MT untuk kode_layanan: ' . $kodeLayanan);
        return;
      }

      foreach ($mtEmails as $email) {
        if (!empty($email)) {
          $this->emailService->sendLhusReadyNotification($email, $data);
          $this->emailService->clearEmail();
        }
      }

      log_message('info', 'LhusNotificationController: Notifikasi LHUS ready terkirim ke ' . count($mtEmails) . ' MT untuk kode_layanan: ' . $kodeLayanan);
    } catch (\Throwable $e) {
      log_message('error', 'LhusNotificationController::sendLhusReadyForReview error: ' . $e->getMessage());
    }
  }

  /**
   * Notifikasi #2: Kirim email ke Penyelia bahwa review LHUS selesai
   * 
   * Trigger: Manajer Teknis klik "Selesai" di modal TinjauLHUS
   * Target: Penyelia yang terkait layanan ini (via r_tim)
   * 
   * @param int|string $kodeLayanan Kode layanan
   * @param array $reviewItems Array item review [{detKode, aksi, ket}, ...]
   */
  public function sendLhusReviewCompleteNotification($kodeLayanan, array $reviewItems = []): void
  {
    try {
      $data = $this->prepareLhusReviewCompleteData($kodeLayanan, $reviewItems);

      if (empty($data)) {
        log_message('warning', 'LhusNotificationController::sendLhusReviewComplete - Data kosong untuk kode_layanan: ' . $kodeLayanan);
        return;
      }

      // Ambil email Penyelia yang terkait layanan ini
      $penyeliaEmails = $this->recipientModel->getEmailsByLayanan(
        $kodeLayanan,
        NotificationRecipientModel::ROLE_PENYELIA
      );

      if (empty($penyeliaEmails)) {
        log_message('warning', 'LhusNotificationController::sendLhusReviewComplete - Tidak ada email Penyelia untuk kode_layanan: ' . $kodeLayanan);
        return;
      }

      foreach ($penyeliaEmails as $email) {
        if (!empty($email)) {
          $this->emailService->sendLhusReviewResultNotification($email, $data);
          $this->emailService->clearEmail();
        }
      }

      log_message('info', 'LhusNotificationController: Notifikasi review LHUS terkirim ke ' . count($penyeliaEmails) . ' Penyelia untuk kode_layanan: ' . $kodeLayanan);
    } catch (\Throwable $e) {
      log_message('error', 'LhusNotificationController::sendLhusReviewComplete error: ' . $e->getMessage());
    }
  }

  /**
   * Notifikasi #3: Kirim email ke Admin bahwa seluruh LHUS telah diterima (all accepted globally)
   * 
   * Trigger: MT klik "Selesai" dan SEMUA item layanan (dari semua MT) sudah diterima
   * Target: Admin (role_id = 1)
   * 
   * @param int|string $kodeLayanan Kode layanan
   */
  public function sendLhusAllAcceptedToAdmin($kodeLayanan): void
  {
    try {
      $data = $this->prepareLhusAllAcceptedData($kodeLayanan);

      if (empty($data)) {
        log_message('warning', 'LhusNotificationController::sendLhusAllAcceptedToAdmin - Data kosong untuk kode_layanan: ' . $kodeLayanan);
        return;
      }

      // Ambil email Admin
      $adminEmails = $this->recipientModel->getEmailsByRole(
        NotificationRecipientModel::ROLE_ADMIN
      );

      if (empty($adminEmails)) {
        log_message('warning', 'LhusNotificationController::sendLhusAllAcceptedToAdmin - Tidak ada email Admin');
        return;
      }

      foreach ($adminEmails as $email) {
        if (!empty($email)) {
          $this->emailService->sendLhusAllAcceptedNotification($email, $data);
          $this->emailService->clearEmail();
        }
      }

      log_message('info', 'LhusNotificationController: Notifikasi LHUS all accepted terkirim ke ' . count($adminEmails) . ' Admin untuk kode_layanan: ' . $kodeLayanan);
    } catch (\Throwable $e) {
      log_message('error', 'LhusNotificationController::sendLhusAllAcceptedToAdmin error: ' . $e->getMessage());
    }
  }

  /**
   * Siapkan data untuk notifikasi LHUS siap ditinjau
   * 
   * @param int|string $kodeLayanan
   * @param int $penyeliaUserId
   * @return array
   */
  protected function prepareLhusReadyData($kodeLayanan, int $penyeliaUserId): array
  {
    $layanan = $this->lhusDataModel->getLayananWithPelanggan($kodeLayanan);
    if (!$layanan) {
      return [];
    }

    $details = $this->lhusDataModel->getLhusDetailsByKodeLayananAndUser($kodeLayanan, $penyeliaUserId);
    $penyeliaName = $this->lhusDataModel->getPenyeliaName($penyeliaUserId);

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
      'nama_penyelia' => $penyeliaName,
      'total_detail' => count($detailItems),
      'detail_items' => $detailItems,
    ];
  }

  /**
   * Siapkan data untuk notifikasi review LHUS selesai
   * 
   * @param int|string $kodeLayanan
   * @param array $reviewItems
   * @return array
   */
  protected function prepareLhusReviewCompleteData($kodeLayanan, array $reviewItems): array
  {
    $layanan = $this->lhusDataModel->getLayananWithPelanggan($kodeLayanan);
    if (!$layanan) {
      return [];
    }

    $details = $this->lhusDataModel->getLhusDetailsByKodeLayanan($kodeLayanan);

    // Map review items by detKode for quick lookup
    $reviewMap = [];
    foreach ($reviewItems as $item) {
      $detKode = $item['detKode'] ?? '';
      if (!empty($detKode)) {
        $aksiRaw = $item['aksi'] ?? 0;
        if ($aksiRaw === 'terima') {
          $aksiInt = 1;
        } elseif ($aksiRaw === 'tolak') {
          $aksiInt = 2;
        } else {
          $aksiInt = (int) $aksiRaw;
        }

        $reviewMap[$detKode] = [
          'aksi' => $aksiInt,
          'ket' => $item['ket'] ?? '',
        ];
      }
    }

    $jumlahDiterima = 0;
    $jumlahDitolak = 0;
    $detailItems = [];

    foreach ($details as $d) {
      $review = $reviewMap[$d->kode] ?? null;
      $aksi = $review ? $review['aksi'] : ((int) ($d->files ?? 0));
      $ket = $review ? $review['ket'] : ($d->catatan ?? '');

      if ($aksi === 1) {
        $jumlahDiterima++;
      } elseif ($aksi === 2) {
        $jumlahDitolak++;
      }

      $detailItems[] = [
        'nama_layanan' => $d->nama_layanan ?? '-',
        'metode_nama' => $d->metode_nama ?? '',
        'aksi' => $aksi,
        'keterangan' => $ket,
      ];
    }

    return [
      'kode_layanan' => $layanan->kode_layanan,
      'no_invoice' => $layanan->no_invoice ?? '',
      'nama_pelanggan' => $layanan->nama_pelanggan,
      'total_detail' => count($detailItems),
      'jumlah_diterima' => $jumlahDiterima,
      'jumlah_ditolak' => $jumlahDitolak,
      'all_accepted' => ($jumlahDitolak === 0 && $jumlahDiterima > 0),
      'detail_items' => $detailItems,
    ];
  }

  /**
   * Siapkan data untuk notifikasi LHUS all accepted ke Admin
   * 
   * @param int|string $kodeLayanan
   * @return array
   */
  protected function prepareLhusAllAcceptedData($kodeLayanan): array
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
      'total_detail' => count($detailItems),
      'detail_items' => $detailItems,
    ];
  }

}
