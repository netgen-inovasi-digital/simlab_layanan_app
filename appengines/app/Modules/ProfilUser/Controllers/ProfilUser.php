<?php

namespace Modules\ProfilUser\Controllers;

use App\Controllers\BaseController;
use App\Models\MyModel;
use App\Models\UserModel;
use Modules\Notifications\Controllers\ProfileNotificationController;

class ProfilUser extends BaseController
{
  private $table = 'account_users';
  private $id = 'user_id';

  public function index()
  {
    $data = [
      'title' => 'Profil',
      'get' => $this->getProfil(),
      'fileUlm' => $this->getFileUlm()
    ];
    return view('Modules\ProfilUser\Views\v_profiluser', $data);
  }

  function getFileUlm()
  {
    $model = new MyModel('file_umum');
    $file = $model->getDataById('file_id', 1);

    if ($file && $file->status === 'aktif' && !empty($file->file_path)) {
      return [
        'judul' => $file->judul,
        'file_path' => $file->file_path,
        'file_url' => base_url('uploads/fileumum/' . $file->file_path),
        'deskripsi' => $file->deskripsi ?? ''
      ];
    }

    return null;
  }

  function getProfil()
  {
    $idUser = session()->get('id_user');
    $model = new UserModel();
    $user = $model->getUserById($idUser);

    if (!$user) {
      return null;
    }

    $data = [
      'user_name' => $user->user_name,
      'user_email' => $user->user_email,
      'user_telpon' => $user->user_telpon ?? '',
      'user_instansi' => $user->user_instansi ?? '',
      'user_identity' => $user->user_identity,
      'verifikasi' => $user->verifikasi ?? 0,
      'status_user' => $user->status_user,
      'role_id' => $user->role_id,
      'bukti' => $user->bukti ?? '',
      'bukti_url' => !empty($user->bukti) ? base_url('uploads/bukti/' . $user->bukti) : null,
      'showInstansi' => ($user->user_identity ?? '') === 'NON ULM',
      'showBukti' => ($user->user_identity ?? '') === 'ULM',
    ];

    // Status verifikasi
    $identityFilled = !empty($user->user_identity);
    $telpFilled = !empty($user->user_telpon);
    $buktiOrInstansiFilled = ($user->user_identity === 'ULM' && !empty($user->bukti)) ||
      ($user->user_identity === 'NON ULM' && !empty($user->user_instansi));

    if (!$identityFilled || !$telpFilled || !$buktiOrInstansiFilled) {
      $data['statusVerifikasi'] = ['text' => 'Silahkan lengkapi identitas Anda', 'class' => 'bg-danger'];
    } elseif ((int) $user->verifikasi === 2) {
      $data['statusVerifikasi'] = ['text' => 'Tertolak — Silakan unggah ulang bukti ULM Anda', 'class' => 'bg-danger', 'icon' => 'bi bi-x-circle'];
    } elseif ((int) $user->verifikasi === 0) {
      $data['statusVerifikasi'] = ['text' => 'Harap tunggu verifikasi', 'class' => 'bg-success'];
    } else {
      $data['statusVerifikasi'] = ['text' => 'Terverifikasi', 'class' => 'bg-primary', 'icon' => 'bi bi-check-circle'];
    }

    return (object) $data;
  }

  public function submit()
  {
    $passwordLama = (string) $this->request->getPost('old_password');      // opsional (hanya jika ganti password)
    $passwordBaru = (string) $this->request->getPost('new_password');      // opsional
    $ulangiPassword = (string) $this->request->getPost('confirm_password');  // opsional
    $emailPost = strtolower(trim((string) $this->request->getPost('email')));

    $userModel = new UserModel();
    $user = $userModel->getUserById(session()->get('id_user'));

    // Siapkan data awal
    $data = [
      'user_telpon' => trim((string) $this->request->getPost('user_telpon')),
      'user_instansi' => trim((string) $this->request->getPost('user_instansi')),
      'user_identity' => (string) $this->request->getPost('user_identity'),
    ];

    // ========== VALIDASI WAJIB LENGKAPI IDENTITAS ==========
    if ($data['user_identity'] === '') {
      return $this->response->setJSON([
        'res' => 'error',
        'msg' => 'Silahkan pilih status identitas (ULM / NON ULM)',
        'xname' => csrf_token(),
        'xhash' => csrf_hash()
      ]);
    }

    if ($data['user_telpon'] === '') {
      return $this->response->setJSON([
        'res' => 'error',
        'msg' => 'Nomor telepon wajib diisi',
        'xname' => csrf_token(),
        'xhash' => csrf_hash()
      ]);
    }

    // Ambil file bukti (jika ada di request)
    $fileBukti = $this->request->getFile('bukti_file');
    $hasNewBukti = $fileBukti && $fileBukti->isValid() && !$fileBukti->hasMoved();

    // Jika ULM → wajib ada bukti (baru atau sudah tersimpan sebelumnya)
    if ($data['user_identity'] === 'ULM') {
      $alreadyHasBukti = !empty($user->bukti);
      if (!$alreadyHasBukti && !$hasNewBukti) {
        return $this->response->setJSON([
          'res' => 'error',
          'msg' => 'Silahkan upload bukti untuk identitas ULM',
          'xname' => csrf_token(),
          'xhash' => csrf_hash()
        ]);
      }
    }

    // Jika NON ULM → instansi wajib diisi
    if ($data['user_identity'] === 'NON ULM' && $data['user_instansi'] === '') {
      return $this->response->setJSON([
        'res' => 'error',
        'msg' => 'Alamat Instansi wajib diisi untuk NON ULM',
        'xname' => csrf_token(),
        'xhash' => csrf_hash()
      ]);
    }

    // ========== STATUS VERIFIKASI & PENGELOLAAN FIELD TERKAIT ==========
    if ($data['user_identity'] === 'NON ULM') {
      // NON ULM → otomatis verifikasi dan hapus bukti lama jika ada
      if (!empty($user->bukti)) {
        $oldPath = FCPATH . 'uploads' . DIRECTORY_SEPARATOR . 'bukti' . DIRECTORY_SEPARATOR . $user->bukti;
        if (is_file($oldPath) && strpos(realpath($oldPath), realpath(FCPATH . 'uploads/bukti')) === 0) {
          @unlink($oldPath);
        }
      }
      $data['bukti'] = null;
      $data['verifikasi'] = 1;
      // user_instansi dipertahankan (memang diperlukan untuk NON ULM)
    } else { // ULM
      // ULM → menunggu verifikasi admin
      $data['verifikasi'] = 0;
      // ★ Penting: bersihkan jejak instansi kalau sebelumnya pernah NON ULM
      $data['user_instansi'] = null;
    }

    // ========== PERUBAHAN EMAIL (TANPA PERLU PASSWORD) ==========
    $emailLama = strtolower((string) $user->user_email);
    $emailBerubah = ($emailPost !== '' && $emailPost !== $emailLama);

    if ($emailBerubah) {
      if (!filter_var($emailPost, FILTER_VALIDATE_EMAIL)) {
        return $this->response->setJSON([
          'res' => 'error',
          'msg' => 'Format email tidak valid.',
          'xname' => csrf_token(),
          'xhash' => csrf_hash()
        ]);
      }
      if (strlen($emailPost) > 191) {
        return $this->response->setJSON([
          'res' => 'error',
          'msg' => 'Email terlalu panjang (maks 191 karakter).',
          'xname' => csrf_token(),
          'xhash' => csrf_hash()
        ]);
      }
      // Cek unik
      $emailExists = $userModel->isEmailExists($emailPost, (int) session()->get('id_user'));

      if ($emailExists) {
        return $this->response->setJSON([
          'res' => 'error',
          'msg' => 'Email sudah terpakai. Gunakan email lain.',
          'xname' => csrf_token(),
          'xhash' => csrf_hash()
        ]);
      }

      $data['user_email'] = $emailPost;
    }

    // ubah password
    if ($passwordLama !== '' || $passwordBaru !== '' || $ulangiPassword !== '') {
      if ($passwordLama === '' || $passwordBaru === '' || $ulangiPassword === '') {
        return $this->response->setJSON([
          'res' => 'error',
          'msg' => 'Lengkapi Password Lama, Password Baru, dan Konfirmasi Password.',
          'xname' => csrf_token(),
          'xhash' => csrf_hash()
        ]);
      }
      if (!$userModel->verifyPassword(session()->get('id_user'), $passwordLama)) {
        return $this->response->setJSON([
          'res' => 'error',
          'msg' => 'Password lama salah',
          'xname' => csrf_token(),
          'xhash' => csrf_hash()
        ]);
      }
      if ($passwordBaru !== $ulangiPassword) {
        return $this->response->setJSON([
          'res' => 'error',
          'msg' => 'Password baru dan konfirmasi tidak sama',
          'xname' => csrf_token(),
          'xhash' => csrf_hash()
        ]);
      }
      $data['user_password'] = password_hash($ulangiPassword, PASSWORD_DEFAULT);
    }

    // bukti
    if ($data['user_identity'] === 'ULM' && $hasNewBukti) {
      if (!empty($user->bukti)) {
        $oldPath = FCPATH . 'uploads' . DIRECTORY_SEPARATOR . 'bukti' . DIRECTORY_SEPARATOR . $user->bukti;
        if (is_file($oldPath) && strpos(realpath($oldPath), realpath(FCPATH . 'uploads/bukti')) === 0) {
          @unlink($oldPath);
        }
      }

      $uploadResult = $this->doUpload($fileBukti);
      if (!$uploadResult['status']) {
        return $this->response->setJSON([
          'res' => 'error',
          'msg' => $uploadResult['msg'],
          'xname' => csrf_token(),
          'xhash' => csrf_hash()
        ]);
      }
      $data['bukti'] = $uploadResult['filename'];
    }

    $res = $userModel->updateUser($data, session()->get('id_user'));

    if ($res) {
      // Kirim notifikasi ke admin jika pelanggan mengajukan verifikasi ULM
      if ($data['user_identity'] === 'ULM') {
        $updatedUser = $userModel->getUserById(session()->get('id_user'));
        $this->sendUlmVerificationNotification($updatedUser);
      }

      return $this->response->setJSON([
        'res' => 'refresh',
        'link' => site_url('profiluser'),
        'xname' => csrf_token(),
        'xhash' => csrf_hash()
      ]);
    }

    return $this->response->setJSON([
      'res' => 'error',
      'msg' => 'Data gagal disimpan.',
      'xname' => csrf_token(),
      'xhash' => csrf_hash()
    ]);
  }



  function doUpload($file)
  {
    if (!($file && $file->isValid() && !$file->hasMoved())) {
      return ['status' => false, 'msg' => 'File tidak valid atau sudah dipindahkan'];
    }

    // Format yang diperbolehkan: JPG, PNG, dan PDF
    $allowedExt = ['jpg', 'jpeg', 'png', 'pdf'];
    $allowedMime = ['image/jpeg', 'image/jpg', 'image/png', 'application/pdf'];

    $ext = strtolower($file->getClientExtension());
    $tmpName = $file->getTempName();

    // Deteksi MIME type yang sebenarnya menggunakan finfo
    if (!is_file($tmpName)) {
      return ['status' => false, 'msg' => 'File sementara tidak ditemukan'];
    }

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
      return ['status' => false, 'msg' => 'Format file harus JPG, PNG, atau PDF'];
    }

    // Validasi khusus untuk gambar (JPG/PNG)
    if (in_array($ext, ['jpg', 'jpeg', 'png'])) {
      if (@getimagesize($tmpName) === false) {
        return ['status' => false, 'msg' => 'File bukan gambar yang valid'];
      }
    }

    // Validasi ukuran file maksimal 5MB
    if ($file->getSize() > 5 * 1024 * 1024) {
      return ['status' => false, 'msg' => 'Ukuran file maksimal 5 MB'];
    }

    try {
      $filename = time() . '_' . bin2hex(random_bytes(8)) . '.' . $ext;
    } catch (\Exception $e) {
      $filename = time() . '_' . bin2hex(openssl_random_pseudo_bytes(8)) . '.' . $ext;
    }

    $path = FCPATH . 'uploads' . DIRECTORY_SEPARATOR . 'bukti';
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

  public function upload()
  {
    $file = $this->request->getFile('upload');
    $uploadResult = $this->doUpload($file);

    if ($uploadResult['status'] === true) {
      return $this->response->setJSON([
        'uploaded' => true,
        'url' => base_url('uploads/bukti/' . $uploadResult['filename']),
        'xname' => csrf_token(),
        'xhash' => csrf_hash()
      ]);
    } else {
      return $this->response->setJSON([
        'uploaded' => false,
        'error' => ['message' => $uploadResult['msg'] ?? 'Upload gagal.'],
        'xname' => csrf_token(),
        'xhash' => csrf_hash()
      ]);
    }
  }

  /**
   * Kirim notifikasi ke admin bahwa pelanggan mengajukan verifikasi ULM
   */
  protected function sendUlmVerificationNotification(object $user): void
  {
    $notificationController = new ProfileNotificationController();
    $notificationController->sendUlmVerificationNotification($user);
  }
}
