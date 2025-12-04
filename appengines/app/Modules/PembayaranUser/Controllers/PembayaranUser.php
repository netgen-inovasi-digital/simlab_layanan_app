<?php

namespace Modules\PembayaranUser\Controllers;

use App\Controllers\BaseController;
use Modules\PembayaranUser\Models\PembayaranUserModel;

class PembayaranUser extends BaseController
{
  private $table = 't_pembayaran';
  private $id = 'bayarKode';

  /** @var PembayaranUserModel */
  protected $pembayaranModel;

  public function __construct()
  {
    $this->pembayaranModel = new PembayaranUserModel();
  }

  public function index()
  {
    $data = [
      'title' => 'Pembayaran',
    ];
    return view('Modules\PembayaranUser\Views\v_pembayaran_user', $data);
  }

  /**
   * Get data list untuk tabel - hanya menampilkan tagihan yang sudah diproses (ada nomor invoice)
   */
  public function dataList()
  {
    try {
      $session = session();
      $user_id = $session->get('id_user'); // ✅ Sama seperti Pelayanan.php

      // Validasi: User harus login
      if (empty($user_id)) {
        return $this->response->setJSON([
          "items" => [],
          "error" => "User belum login atau session expired. Silakan login kembali."
        ]);
      }

      // Ambil user dari tabel simlab_account_users (sama seperti Pelayanan.php)
      $user = $this->pembayaranModel->getUserById((int) $user_id);

      // Jika user tidak ditemukan, kembalikan data kosong
      if (!$user) {
        return $this->response->setJSON([
          "items" => [],
          "error" => "Data user tidak ditemukan."
        ]);
      }


      $data = [];

      $list = $this->pembayaranModel->getPaymentListByEmail($user->user_email);

      foreach ($list as $row) {
        $encrypted_id = bin2hex(service('encrypter')->encrypt($row->bayarKode));

        // Status pembayaran berdasarkan bayarBuktiFile dan bayarStatus
        $paymentStatus = $this->getPaymentStatus($row->bayarBuktiFile, $row->bayarStatus);
        $status = $this->formatStatus($paymentStatus);

        // Cek apakah ada file bukti di session (file temporary yang belum disave)
        $sessionKey = 'temp_bukti_' . $row->bayarKode;
        $tempBukti = session()->get($sessionKey);
        $currentFile = !empty($tempBukti) ? $tempBukti : ($row->bayarBuktiFile ?? '');

        // Tombol aksi
        $aksi = $this->aksiButton($encrypted_id, $paymentStatus, $currentFile);

        // Ambil data user
        $personName = null;
        $userIdentity = '-';
        $instansi = '-';
        $userData = $this->pembayaranModel->findPemesanUser($row->user_id ?? null, $row->lnAccEmail ?? null);

        // Jika user ditemukan, ambil info
        if ($userData) {
          $personName = $userData->user_name ?? $userData->user_email ?? '-';
          $instansi = $userData->user_instansi ?? '-';
          $userIdentity = $userData->user_identity ?? '-';
        } else {
          $personName = $row->lnAccEmail ?? '-';
        }

        $pemesanNama = !empty($personName) ? $personName : '-';
        $tipe = !empty($userIdentity) ? $userIdentity : '-';
        $tanggal = !empty($row->lnTgl) ? date('d-m-Y H:i', strtotime($row->lnTgl)) : '-';

        // Format gabungan seperti di Tagihan (Nama + Tanggal + Tipe)
        $combined = '
                    <div style="line-height:1.3;">
                        <span style="font-size:1rem; font-weight:600;">' . esc($pemesanNama) . '</span><br>
                        <span style="font-size:0.9rem; color:#555;">' . esc($tanggal) . ' | ' . esc($tipe) . '</span>
                    </div>';

        // Kolom bukti bayar dengan logika: null = "-", ada file = tombol lihat
        $buktiBayar = '-';
        if (!empty($currentFile)) {
          if (!empty($tempBukti)) {
            // File baru dari session (belum disave)
            $buktiBayar = '<span class="badge bg-info"><i class="bi bi-clock-history"></i> File Terupload</span>';
          } else {
            // File dari database (sudah disave)
            $buktiBayar = '<a href="' . base_url('uploads/bukti/' . $currentFile) . '" target="_blank" class="btn btn-sm btn-success"><i class="bi bi-file-earmark-check"></i> Lihat</a>';
          }
        }

        // FIX: Status badge - jika ditolak, tampilkan sebagai BADGE yang bisa diklik (seperti badge lainnya)
        if ($paymentStatus == 3 && !empty($row->bayarCatatan)) {
          // Status DITOLAK - tampilkan sebagai badge danger yang bisa diklik (cursor pointer)
          $status = '<span class="badge bg-danger" style="cursor: pointer;" onclick="lihatCatatan(\'' . $encrypted_id . '\', \'' . esc($row->bayarCatatan, 'js') . '\')">
                        Ditolak - Lihat Catatan
                    </span>';
        } else {
          // Status lainnya - tampilkan badge biasa
          $status = $this->formatStatus($paymentStatus);
        }

        // Response array
        $data[] = [
          !empty($row->bayarInvoiceNo) ? esc($row->bayarInvoiceNo) : '<span class="text-muted">-</span>', // No. Invoice
          $combined, // Pemesan (Nama + Tanggal + Tipe)
          'Rp ' . number_format($row->bayarTotalBiaya, 0, ',', '.'), // Total Biaya
          !empty($row->bayarInvoiceFile)
          ? '<a href="' . base_url('uploads/invoice/' . $row->bayarInvoiceFile) . '" target="_blank" class="btn btn-sm btn-info"><i class="bi bi-file-pdf"></i> Lihat</a>'
          : '<span class="text-muted">-</span>', // File Invoice
          $buktiBayar, // Bukti Bayar
          $status, // Status (badge biasa atau button jika ditolak)
          $aksi // Aksi
        ];
      }

      return $this->response->setJSON(["items" => $data]);
    } catch (\Exception $e) {
      return $this->response->setJSON([
        "items" => [],
        "error" => $e->getMessage()
      ]);
    }
  }

  /**
   * Tentukan status pembayaran
   * @return int 0 = Belum Diunggah, 1 = Menunggu Verifikasi, 2 = Terverifikasi
   */
  private function getPaymentStatus($buktiBayar, $bayarStatus)
  {
    // Jika bayarBuktiFile == null & bayarStatus == 0 → Belum Diunggah
    if (empty($buktiBayar) && $bayarStatus == 0) {
      return 0;
    }

    // Jika bayarBuktiFile != null & bayarStatus == 1 → Terverifikasi
    if (!empty($buktiBayar) && $bayarStatus == 1) {
      return 2;
    }

    // Jika bayarBuktiFile != null & bayarStatus == 2 → Verifikasi Gagal
    if (!empty($buktiBayar) && $bayarStatus == 2) {
      return 3;
    }

    // Jika bayarBuktiFile != null & bayarStatus == 0 → Menunggu Verifikasi
    if (!empty($buktiBayar) && $bayarStatus == 0) {
      return 1;
    }

    return 0; // Default
  }

  /**
   * Format status badge
   */
  private function formatStatus($status)
  {
    switch ($status) {
      case 0:
        return '<span class="badge bg-secondary">Belum Bayar</span>';
      case 1:
        return '<span class="badge bg-info">Menunggu Verifikasi</span>';
      case 2:
        return '<span class="badge bg-success">Terverifikasi</span>';
      case 3:
        return '<span class="badge bg-danger">Verifikasi Gagal</span>';
      default:
        return '<span class="badge bg-secondary">Unknown</span>';
    }
  }

  /**
   * Tombol aksi - Hanya 1 button untuk Upload & Kirim (gabungan)
   */
  private function aksiButton($id, $status, $file)
  {
    // URL file bukti bayar (jika ada)
    $fileUrl = !empty($file) ? base_url('uploads/bukti/' . $file) : '';

    // Button "Upload & Kirim Bukti":
    // - Disabled jika status = Menunggu Verifikasi (1) atau Terverifikasi (2)
    // - Aktif jika status = Belum Bayar (0) atau Verifikasi Gagal (3)
    $uploadDisabled = ($status == 1 || $status == 2) ? 'disabled' : '';
    $uploadClass = ($status == 1 || $status == 2) ? 'text-secondary' : 'text-primary';
    $uploadTitle = ($status == 2) ? 'Sudah terverifikasi'
      : (($status == 1) ? 'Menunggu verifikasi admin'
        : (($status == 3) ? 'Upload ulang bukti bayar'
          : 'Upload & Kirim Bukti Bayar'));

    return '<div id="' . $id . '" class="float-end">
        <span class="' . $uploadClass . ' btn-action" ' . $uploadDisabled . ' title="' . $uploadTitle . '" data-fileurl="' . esc($fileUrl) . '" onclick="uploadBukti(event)">
            <i class="bi bi-send"></i> Kirim Bukti</span>
    </div>';
  }

  /**
   * Upload & Kirim Bukti Bayar (GABUNGAN) - Upload file + langsung kirim ke admin untuk verifikasi
   */
  public function uploadKirimBukti()
  {
    try {
      $file = $this->request->getFile('file_bukti');
      $encId = $this->request->getPost('id');

      if (empty($encId)) {
        return $this->response->setJSON([
          'res' => false,
          'msg' => 'ID tidak ditemukan',
          'xname' => csrf_token(),
          'xhash' => csrf_hash()
        ]);
      }

      try {
        $id = service('encrypter')->decrypt(hex2bin($encId));
      } catch (\Throwable $e) {
        return $this->response->setJSON([
          'res' => false,
          'msg' => 'ID tidak valid',
          'xname' => csrf_token(),
          'xhash' => csrf_hash()
        ]);
      }

      if (!($file && $file->isValid() && !$file->hasMoved())) {
        return $this->response->setJSON([
          'res' => false,
          'msg' => 'File tidak valid atau belum dipilih',
          'xname' => csrf_token(),
          'xhash' => csrf_hash()
        ]);
      }

      // Upload file ke folder uploads/bukti/
      $uploadResult = $this->doUpload($file, 'bukti');

      if (!$uploadResult['status']) {
        return $this->response->setJSON([
          'res' => false,
          'msg' => $uploadResult['msg'],
          'xname' => csrf_token(),
          'xhash' => csrf_hash()
        ]);
      }

      $filename = $uploadResult['filename'];

      // Langsung simpan ke database (tidak pakai session)
      $currentData = $this->pembayaranModel->getPembayaranById($id);

      // Hapus file lama jika ada
      if (!empty($currentData->bayarBuktiFile)) {
        $oldFile = FCPATH . 'uploads/bukti/' . $currentData->bayarBuktiFile;
        if (file_exists($oldFile)) {
          @unlink($oldFile);
        }
      }

      // Update bukti bayar dan set bayarStatus = 0 (menunggu verifikasi)
      $dataPembayaran = [
        'bayarBuktiFile' => $filename,
        'bayarStatus' => 0  // Menunggu verifikasi admin
      ];

      $updatePembayaran = $this->pembayaranModel->updatePembayaran($id, $dataPembayaran);

      if (!$updatePembayaran) {
        // Jika gagal update, hapus file yang sudah diupload
        $uploadedFile = FCPATH . 'uploads/bukti/' . $filename;
        if (file_exists($uploadedFile)) {
          @unlink($uploadedFile);
        }

        return $this->response->setJSON([
          'res' => false,
          'msg' => 'Gagal menyimpan bukti bayar',
          'xname' => csrf_token(),
          'xhash' => csrf_hash()
        ]);
      }

      return $this->response->setJSON([
        'res' => 'success',
        'msg' => 'Bukti pembayaran berhasil dikirim ke admin. Menunggu verifikasi.',
        'xname' => csrf_token(),
        'xhash' => csrf_hash()
      ]);
    } catch (\Exception $e) {
      log_message('error', 'UploadKirimBukti exception: ' . $e->getMessage());
      return $this->response->setJSON([
        'res' => false,
        'msg' => 'Terjadi kesalahan: ' . $e->getMessage(),
        'xname' => csrf_token(),
        'xhash' => csrf_hash()
      ]);
    }
  }

  /**
   * Upload bukti bayar (PNG/JPG/PDF/image format) - OLD METHOD, kept for compatibility
   */
  public function uploadBukti()
  {
    try {
      $file = $this->request->getFile('file_bukti');
      $encId = $this->request->getPost('id');

      if (empty($encId)) {
        return $this->response->setJSON([
          'res' => false,
          'msg' => 'ID tidak ditemukan',
          'xname' => csrf_token(),
          'xhash' => csrf_hash()
        ]);
      }

      try {
        $id = service('encrypter')->decrypt(hex2bin($encId));
      } catch (\Throwable $e) {
        return $this->response->setJSON([
          'res' => false,
          'msg' => 'ID tidak valid',
          'xname' => csrf_token(),
          'xhash' => csrf_hash()
        ]);
      }

      if (!($file && $file->isValid() && !$file->hasMoved())) {
        return $this->response->setJSON([
          'res' => false,
          'msg' => 'File tidak valid atau belum dipilih',
          'xname' => csrf_token(),
          'xhash' => csrf_hash()
        ]);
      }

      // Upload file ke folder uploads/bukti/
      $uploadResult = $this->doUpload($file, 'bukti');

      if (!$uploadResult['status']) {
        return $this->response->setJSON([
          'res' => false,
          'msg' => $uploadResult['msg'],
          'xname' => csrf_token(),
          'xhash' => csrf_hash()
        ]);
      }

      $filename = $uploadResult['filename'];

      // Simpan filename untuk sementara di session
      session()->set('temp_bukti_' . $id, $filename);

      return $this->response->setJSON([
        'res' => 'success',
        'msg' => 'Bukti bayar berhasil diupload',
        'xname' => csrf_token(),
        'xhash' => csrf_hash()
      ]);
    } catch (\Exception $e) {
      return $this->response->setJSON([
        'res' => false,
        'msg' => 'Terjadi kesalahan: ' . $e->getMessage(),
        'xname' => csrf_token(),
        'xhash' => csrf_hash()
      ]);
    }
  }

  /**
   * Helper untuk upload file (PNG/JPG/PDF/image)
   */
  private function doUpload($file, $folder = 'bukti')
  {
    if (!($file && $file->isValid() && !$file->hasMoved())) {
      return ['status' => false, 'msg' => 'File tidak valid'];
    }

    // Validasi tipe file (image dan PDF)
    $allowedExt = ['pdf', 'png', 'jpg', 'jpeg', 'gif', 'bmp', 'webp'];
    $allowedMime = [
      'application/pdf',
      'image/png',
      'image/jpg',
      'image/jpeg',
      'image/gif',
      'image/bmp',
      'image/webp'
    ];

    $ext = strtolower($file->getClientExtension());
    $tmpName = $file->getTempName();

    if (!is_file($tmpName)) {
      return ['status' => false, 'msg' => 'File sementara tidak ditemukan'];
    }

    // Deteksi MIME type
    $detectedMime = null;
    if (function_exists('finfo_open')) {
      $finfo = finfo_open(FILEINFO_MIME_TYPE);
      $detectedMime = finfo_file($finfo, $tmpName);
      finfo_close($finfo);
    } else {
      $detectedMime = $file->getClientMimeType();
    }

    // Validasi ekstensi dan MIME type
    if (!in_array($ext, $allowedExt) || !in_array($detectedMime, $allowedMime)) {
      return ['status' => false, 'msg' => 'Format file harus gambar (PNG/JPG/etc) atau PDF. Detected: ' . $detectedMime];
    }

    // Validasi ukuran file (max 5MB)
    if ($file->getSize() > 5 * 1024 * 1024) {
      return ['status' => false, 'msg' => 'Ukuran file maksimal 5MB'];
    }

    // Generate filename
    try {
      $rand = bin2hex(random_bytes(8));
    } catch (\Exception $e) {
      $rand = bin2hex(openssl_random_pseudo_bytes(8));
    }
    $filename = time() . '_' . $rand . '.' . $ext;

    $path = FCPATH . 'uploads/' . $folder;

    if (!is_dir($path)) {
      @mkdir($path, 0755, true);
    }

    try {
      $file->move($path, $filename, true);
      $fullPath = $path . DIRECTORY_SEPARATOR . $filename;

      if (is_file($fullPath)) {
        return ['status' => true, 'filename' => $filename];
      } else {
        return ['status' => false, 'msg' => 'File gagal dipindahkan'];
      }
    } catch (\Exception $e) {
      return ['status' => false, 'msg' => 'Gagal memindahkan file: ' . $e->getMessage()];
    }
  }

  /**
   * Kirim bukti bayar - update database dan ubah status
   */
  public function kirimBukti()
  {
    $id = $this->request->getPost('id');

    try {
      $id = service('encrypter')->decrypt(hex2bin($id));
    } catch (\Exception $e) {
      return $this->response->setJSON([
        'res' => false,
        'msg' => 'ID tidak valid: ' . $e->getMessage(),
        'xname' => csrf_token(),
        'xhash' => csrf_hash()
      ]);
    }

    $currentData = $this->pembayaranModel->getPembayaranById($id);

    // Cek file dari session (file yang baru diupload)
    $sessionKey = 'temp_bukti_' . $id;
    $tempFilename = session()->get($sessionKey);

    // Jika tidak ada file di session, cek di database (file lama)
    if (empty($tempFilename) && (empty($currentData) || empty($currentData->bayarBuktiFile))) {
      return $this->response->setJSON([
        'res' => false,
        'msg' => 'Bukti bayar belum diupload. Upload terlebih dahulu sebelum mengirim.',
        'xname' => csrf_token(),
        'xhash' => csrf_hash()
      ]);
    }

    // Gunakan file dari session jika ada, jika tidak gunakan file lama dari database
    $filename = !empty($tempFilename) ? $tempFilename : $currentData->bayarBuktiFile;


    // Hapus file lama jika ada dan berbeda dengan file baru
    if (!empty($currentData->bayarBuktiFile) && !empty($tempFilename) && $currentData->bayarBuktiFile !== $tempFilename) {
      $oldFilePath = FCPATH . 'uploads/bukti/' . $currentData->bayarBuktiFile;
      if (file_exists($oldFilePath)) {
        @unlink($oldFilePath);
      }
    }

    // Update bukti bayar dan set bayarStatus = 0 (menunggu verifikasi)
    $dataPembayaran = [
      'bayarBuktiFile' => $filename,
      'bayarStatus' => 0  // Menunggu verifikasi admin
    ];

    $updatePembayaran = $this->pembayaranModel->updatePembayaran($id, $dataPembayaran);

    if (!$updatePembayaran) {
      return $this->response->setJSON([
        'res' => false,
        'msg' => 'Gagal menyimpan bukti bayar',
        'xname' => csrf_token(),
        'xhash' => csrf_hash()
      ]);
    }

    // Hapus dari session setelah berhasil save
    if (!empty($tempFilename)) {
      session()->remove($sessionKey);
    }

    return $this->response->setJSON([
      'res' => true,
      'msg' => 'Bukti pembayaran berhasil dikirim. Menunggu verifikasi petugas lab.',
      'xname' => csrf_token(),
      'xhash' => csrf_hash()
    ]);
  }
}
