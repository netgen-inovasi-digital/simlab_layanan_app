<?php

namespace App\Services;

class EmailServices
{
  protected $email;

  public function __construct()
  {
    $this->email = \Config\Services::email();
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
    try {
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

      if (empty($message)) {
        log_message('error', "Email view rendering failed for {$toEmail}");
        return false;
      }

      $fromEmail = $config->email ?? config('Email')->fromEmail;

      $this->email->setFrom($fromEmail, 'Simlab System');
      $this->email->setTo($toEmail);
      $this->email->setSubject($subject);
      $this->email->setMessage($message);
      $this->email->setMailType('html');


      $result = $this->email->send();

      if (!$result) {
        log_message('error', "Email send FAILED!");
        log_message('error', "Email debugger: " . $this->email->printDebugger(['headers', 'subject', 'body']));
      } else {
        log_message('info', "Email sent SUCCESSFULLY to {$toEmail}");
      }

      log_message('info', "=== END EMAIL NOTIFICATION ===");

      return $result;

    } catch (\Exception $e) {
      log_message('error', "Email exception to {$toEmail}: " . $e->getMessage());
      return false;
    }
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
}
