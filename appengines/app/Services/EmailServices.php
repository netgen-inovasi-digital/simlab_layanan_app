<?php

namespace App\Services;

class EmailServices
{
  protected $email;

  public function __construct()
  {
    $this->email = \Config\Services::email();
  }

  /**
   * Clear email data untuk reset connection SMTP
   * Gunakan ini setelah setiap send untuk menghindari connection reuse issues
   * 
   * @return void
   */
  public function clearEmail(): void
  {
    $this->email->clear();
  }

  // notifikasi sederhana (lupa password)
  public function send(array $params): bool
  {
    $this->email->setFrom(
      $params['from_email'] ?? config('Email')->fromEmail,
      $params['from_name'] ?? config('Email')->fromName
    );
    $this->email->setTo($params['to']);
    $this->email->setSubject($params['subject']);
    $this->email->setMessage($params['message']);

    return $this->email->send();
  }

  // notifikasi email
  public function sendOrderStatus($toEmail, $subject, $templateView, $data)
  {
    $message = view("email/orders/{$templateView}", $data); // render dengan layout
    $this->email->setTo($toEmail);
    $this->email->setSubject($subject);
    $this->email->setMessage($message);
    $this->email->setMailType('html');
    return $this->email->send();
  }

  /**
   * Kirim notifikasi pesanan baru ke admin atau manajer teknis
   * 
   * @param string $toEmail Email penerima (admin atau manajer)
   * @param array $orderData Data pesanan [kode_layanan, no_invoice, nama_pelanggan, email_pelanggan, total_biaya, tanggal_checkout, detail_items]
   * @param string $recipientType 'admin' atau 'manajer' untuk menyesuaikan greeting
   * @return bool Success status
   */
  public function sendNewOrderNotification(string $toEmail, array $orderData, string $recipientType = 'admin'): bool
  {
    $model = new \App\Models\MyModel('konfigurasi');
    $config = $model->getDataById('id_konfigurasi', 1);

    // Buat subject berdasarkan recipient type
    if ($recipientType === 'manajer') {
      $subject = 'Pengecekan Diperlukan - Pesanan Baru';
    } else {
      $subject = 'Pesanan Baru dari Pelanggan';
    }

    // Render template view dengan data
    $viewData = [
      'subject' => $subject,
      'recipient_type' => $recipientType,
      'kode_layanan' => $orderData['kode_layanan'],
      'no_invoice' => $orderData['no_invoice'] ?? null,
      'nama_pelanggan' => $orderData['nama_pelanggan'],
      'email_pelanggan' => $orderData['email_pelanggan'],
      'telpon_pelanggan' => $orderData['telpon_pelanggan'] ?? '-',
      'total_biaya' => $orderData['total_biaya'],
      'tanggal_checkout' => $orderData['tanggal_checkout'],
      'detail_items' => $orderData['detail_items'] ?? [],
      'link_dashboard' => base_url('admin/pelaksanaan'),
    ];

    // Gunakan template berbeda untuk manajer dan admin
    $templatePath = $recipientType === 'manajer'
      ? 'email/orders/new_order_notification_manager'
      : 'email/orders/new_order_notification';

    $message = view($templatePath, $viewData);

    $fromEmail = $config->email ?? config('Email')->fromEmail;

    $this->email->setFrom($fromEmail, 'SIMLAB ULM');
    $this->email->setTo($toEmail);
    $this->email->setSubject($subject);
    $this->email->setMessage($message);
    $this->email->setMailType('html');


    return $this->email->send();
  }

  /**
   * Kirim notifikasi permintaan verifikasi profil ULM ke admin
   * 
   * @param string $toEmail Email penerima (admin)
   * @param array $profileData Data profil [nama_pelanggan, email_pelanggan, telpon_pelanggan]
   * @return bool Success status
   */
  public function sendProfileVerificationNotification(string $toEmail, array $profileData): bool
  {
    $model = new \App\Models\MyModel('konfigurasi');
    $config = $model->getDataById('id_konfigurasi', 1);

    $subject = 'Permintaan Verifikasi ULM - ' . ($profileData['nama_pelanggan'] ?? 'Pelanggan');

    $viewData = [
      'subject' => $subject,
      'nama_pelanggan' => $profileData['nama_pelanggan'],
      'email_pelanggan' => $profileData['email_pelanggan'],
      'telpon_pelanggan' => $profileData['telpon_pelanggan'] ?? '-',
    ];

    $message = view('email/profile/ulm_verification_request', $viewData);

    $fromEmail = $config->email ?? config('Email')->fromEmail;

    $this->email->setFrom($fromEmail, 'SIMLAB ULM');
    $this->email->setTo($toEmail);
    $this->email->setSubject($subject);
    $this->email->setMessage($message);
    $this->email->setMailType('html');

    $result = $this->email->send();

    return $result;
  }

  /**
   * Kirim notifikasi hasil verifikasi ULM ke pelanggan
   * 
   * @param string $toEmail Email pelanggan
   * @param array $profileData Data profil [nama_pelanggan, email_pelanggan, telpon_pelanggan]
   * @param bool $accepted True jika diterima, false jika ditolak
   * @return bool Success status
   */
  public function sendVerificationResultNotification(string $toEmail, array $profileData, bool $accepted): bool
  {
    $model = new \App\Models\MyModel('konfigurasi');
    $config = $model->getDataById('id_konfigurasi', 1);

    $subject = $accepted
      ? 'Verifikasi ULM Diterima'
      : 'Verifikasi ULM Ditolak';

    $viewData = [
      'subject' => $subject,
      'nama_pelanggan' => $profileData['nama_pelanggan'],
    ];

    $templatePath = $accepted
      ? 'email/profile/verification_accepted'
      : 'email/profile/verification_rejected';

    $message = view($templatePath, $viewData);

    $fromEmail = $config->email ?? config('Email')->fromEmail;

    $this->email->setFrom($fromEmail, 'SIMLAB ULM');
    $this->email->setTo($toEmail);
    $this->email->setSubject($subject);
    $this->email->setMessage($message);
    $this->email->setMailType('html');

    return $this->email->send();
  }

  /**
   * Kirim notifikasi review layanan selesai ke admin
   * 
   * @param string $toEmail Email penerima (admin)
   * @param array $reviewData Data review [kode_layanan, no_invoice, nama_pelanggan, email_pelanggan, total_detail, jumlah_diterima, jumlah_ditolak, detail_items]
   * @return bool Success status
   */
  public function sendReviewCompleteNotification(string $toEmail, array $reviewData): bool
  {
    $model = new \App\Models\MyModel('konfigurasi');
    $config = $model->getDataById('id_konfigurasi', 1);

    $subject = 'Review Layanan Selesai - ' . ($reviewData['kode_layanan'] ?? '');

    $viewData = [
      'subject' => $subject,
      'kode_layanan' => $reviewData['kode_layanan'],
      'no_invoice' => $reviewData['no_invoice'] ?? '',
      'nama_pelanggan' => $reviewData['nama_pelanggan'],
      'email_pelanggan' => $reviewData['email_pelanggan'],
      'total_detail' => $reviewData['total_detail'],
      'jumlah_diterima' => $reviewData['jumlah_diterima'],
      'jumlah_ditolak' => $reviewData['jumlah_ditolak'],
      'detail_items' => $reviewData['detail_items'] ?? [],
    ];

    $message = view('email/review/review_complete_notification', $viewData);

    $fromEmail = $config->email ?? config('Email')->fromEmail;

    $this->email->setFrom($fromEmail, 'SIMLAB ULM');
    $this->email->setTo($toEmail);
    $this->email->setSubject($subject);
    $this->email->setMessage($message);
    $this->email->setMailType('html');

    return $this->email->send();
  }

  /**
   * Kirim notifikasi invoice masuk ke pelanggan
   * 
   * @param string $toEmail Email pelanggan
   * @param array $data [no_invoice, kode_layanan, nama_pelanggan, total_biaya, tanggal_invoice]
   * @return bool Success status
   */
  public function sendInvoiceNotification(string $toEmail, array $data): bool
  {
    $model = new \App\Models\MyModel('konfigurasi');
    $config = $model->getDataById('id_konfigurasi', 1);

    $subject = 'Invoice Pembayaran - ' . ($data['no_invoice'] ?? '');

    $viewData = [
      'subject' => $subject,
      'no_invoice' => $data['no_invoice'] ?? '-',
      'kode_layanan' => $data['kode_layanan'] ?? '-',
      'nama_pelanggan' => $data['nama_pelanggan'] ?? 'Pelanggan',
      'total_biaya' => $data['total_biaya'] ?? 'Rp 0',
      'tanggal_invoice' => $data['tanggal_invoice'] ?? date('d-m-Y H:i'),
    ];

    $message = view('email/payment/invoice_sent', $viewData);

    $fromEmail = $config->email ?? config('Email')->fromEmail;

    $this->email->setFrom($fromEmail, 'SIMLAB ULM');
    $this->email->setTo($toEmail);
    $this->email->setSubject($subject);
    $this->email->setMessage($message);
    $this->email->setMailType('html');

    return $this->email->send();
  }

  /**
   * Kirim notifikasi bukti bayar baru ke admin
   * 
   * @param string $toEmail Email admin
   * @param array $data [no_invoice, kode_layanan, nama_pelanggan, email_pelanggan, total_biaya, tanggal_upload]
   * @return bool Success status
   */
  public function sendPaymentProofNotification(string $toEmail, array $data): bool
  {
    $model = new \App\Models\MyModel('konfigurasi');
    $config = $model->getDataById('id_konfigurasi', 1);

    $subject = 'Bukti Pembayaran Baru - ' . ($data['nama_pelanggan'] ?? 'Pelanggan');

    $viewData = [
      'subject' => $subject,
      'no_invoice' => $data['no_invoice'] ?? '-',
      'kode_layanan' => $data['kode_layanan'] ?? '-',
      'nama_pelanggan' => $data['nama_pelanggan'] ?? 'Pelanggan',
      'email_pelanggan' => $data['email_pelanggan'] ?? '-',
      'total_biaya' => $data['total_biaya'] ?? 'Rp 0',
      'tanggal_upload' => $data['tanggal_upload'] ?? date('d-m-Y H:i'),
    ];

    $message = view('email/payment/payment_proof_uploaded', $viewData);

    $fromEmail = $config->email ?? config('Email')->fromEmail;

    $this->email->setFrom($fromEmail, 'SIMLAB ULM');
    $this->email->setTo($toEmail);
    $this->email->setSubject($subject);
    $this->email->setMessage($message);
    $this->email->setMailType('html');

    return $this->email->send();
  }

  /**
   * Kirim notifikasi hasil verifikasi bukti bayar ke pelanggan
   * 
   * @param string $toEmail Email pelanggan
   * @param array $data [no_invoice, kode_layanan, nama_pelanggan, accepted, catatan]
   * @return bool Success status
   */
  public function sendPaymentVerificationResult(string $toEmail, array $data): bool
  {
    $model = new \App\Models\MyModel('konfigurasi');
    $config = $model->getDataById('id_konfigurasi', 1);

    $accepted = $data['accepted'] ?? false;
    $subject = $accepted
      ? 'Pembayaran Diterima - ' . ($data['no_invoice'] ?? '')
      : 'Pembayaran Ditolak - ' . ($data['no_invoice'] ?? '');

    $viewData = [
      'subject' => $subject,
      'no_invoice' => $data['no_invoice'] ?? '-',
      'kode_layanan' => $data['kode_layanan'] ?? '-',
      'nama_pelanggan' => $data['nama_pelanggan'] ?? 'Pelanggan',
      'accepted' => $accepted,
      'catatan' => $data['catatan'] ?? null,
    ];

    $message = view('email/payment/payment_verification_result', $viewData);

    $fromEmail = $config->email ?? config('Email')->fromEmail;

    $this->email->setFrom($fromEmail, 'SIMLAB ULM');
    $this->email->setTo($toEmail);
    $this->email->setSubject($subject);
    $this->email->setMessage($message);
    $this->email->setMailType('html');

    return $this->email->send();
  }
}
