<?php

namespace Modules\Akun\Controllers;

use App\Controllers\BaseController;
use App\Models\MyModel;
use Modules\Notifications\Controllers\ProfileNotificationController;

class Akun extends BaseController
{
  private $table = 'account_users';
  private $id = 'user_id';

  public function index()
  {
    $data = [
      'title' => 'Data Akun Pengguna'
    ];
    return view('Modules\Akun\Views\v_akun', $data);
  }

  public function edit($id)
  {
    $idenc = $id;
    $id = $this->encrypter->decrypt(hex2bin($id));

    $model = new MyModel($this->table);
    $get = $model->getDataById($this->id, $id);

    $data[csrf_token()] = csrf_hash();
    $data['id'] = $idenc;
    $data['user_name'] = $get->user_name;
    $data['user_email'] = $get->user_email;
    $data['user_telpon'] = $get->user_telpon;
    $data['status_user'] = $get->status_user;
    $data['user_identity'] = $get->user_identity;
    $data['user_instansi'] = $get->user_instansi;
    $data['bukti'] = $get->bukti ?? null;
    $data['verifikasi'] = $get->verifikasi ?? 0;

    // generate URL preview kalau ada file
    if (!empty($get->bukti)) {
      $data['bukti_url'] = base_url('uploads/bukti/' . $get->bukti);
    } else {
      $data['bukti_url'] = null;
    }

    return $this->response->setJSON($data);
  }

  public function delete($id)
  {
    $id = $this->encrypter->decrypt(hex2bin($id));

    $model = new MyModel($this->table);
    $data = $model->getDataById($this->id, $id);

    // hapus file bukti jika ada
    if (!empty($data->bukti)) {
      $oldPath = FCPATH . 'uploads/bukti/' . $data->bukti;
      if (is_file($oldPath) && strpos(realpath($oldPath), realpath(FCPATH . 'uploads/bukti')) === 0) {
        unlink($oldPath);
      }
    }

    $res = $model->deleteData($this->id, $id);

    return $this->response->setJSON([
      'res' => $res,
      'xname' => csrf_token(),
      'xhash' => csrf_hash()
    ]);
  }

  public function submit()
  {
    $idenc = $this->request->getPost('id');
    $username = $this->request->getPost('user_name');
    $email = $this->request->getPost('user_email');
    $telpon = $this->request->getPost('user_telpon');
    $newIdentity = $this->request->getPost('user_identity');

    $model = new MyModel($this->table);

    // Ambil data lama jika ini UPDATE
    $oldData = null;
    if (!empty($idenc)) {
      $id = $this->encrypter->decrypt(hex2bin($idenc));
      $oldData = $model->getDataById($this->id, $id);
    }

    $data = [
      'user_name' => $username,
      'user_email' => $email,
      'user_telpon' => $telpon,
      'status_user' => $this->request->getPost('status_user'),
      'user_identity' => $newIdentity,
      'role_id' => 2,
    ];

    // Cek perubahan identity dari ULM ke NON ULM → hapus file bukti lama
    if ($oldData && $oldData->user_identity === 'ULM' && $newIdentity === 'NON ULM') {
      if (!empty($oldData->bukti)) {
        $oldPath = FCPATH . 'uploads/bukti/' . $oldData->bukti;
        if (is_file($oldPath) && strpos(realpath($oldPath), realpath(FCPATH . 'uploads/bukti')) === 0) {
          @unlink($oldPath);
        }
      }
      $data['bukti'] = null;
    }

    // simpan instansi hanya jika NON ULM
    if ($newIdentity === 'NON ULM') {
      $data['user_instansi'] = $this->request->getPost('user_instansi');
    } else {
      $data['user_instansi'] = null;
    }

    // password
    $password = $this->request->getPost('user_password');
    if (!empty($password)) {
      $data['user_password'] = password_hash($password, PASSWORD_DEFAULT);
    }

    // Upload Bukti File - hanya untuk ULM
    $file = $this->request->getFile('bukti_file');
    if ($newIdentity === 'ULM' && $file && $file->isValid() && !$file->hasMoved()) {
      // Jika ini UPDATE, hapus file lama sebelum upload baru
      if (!empty($idenc)) {
        $id = $this->encrypter->decrypt(hex2bin($idenc));
        $current = $model->getDataById($this->id, $id);
        if (!empty($current->bukti)) {
          $oldPath = FCPATH . 'uploads/bukti/' . $current->bukti;
          if (is_file($oldPath) && strpos(realpath($oldPath), realpath(FCPATH . 'uploads/bukti')) === 0) {
            @unlink($oldPath);
          }
        }
      }

      // Upload file baru menggunakan doUpload yang disesuaikan
      $uploadResult = $this->doUpload($file);
      if (!$uploadResult['status']) {
        return $this->response->setJSON([
          'res' => 'error',
          'msg' => $uploadResult['msg'],
          'xname' => csrf_token(),
          'xhash' => csrf_hash()
        ]);
      } else {
        $data['bukti'] = $uploadResult['filename'];
      }
    }

    // Verifikasi
    $verifikasi = $this->request->getPost('verifikasi');
    $verifikasiExplicit = ($verifikasi !== null && $verifikasi !== '');
    if ($verifikasiExplicit) {
      $data['verifikasi'] = ($verifikasi === '1') ? 1 : 0;
    }

    $check = $model->getDataById('user_email', $email);

    if ($idenc == "") {
      if ($check) {
        $res = 'check';
        $link = 'Email sudah ada!';
      } else {
        $res = $model->insertData($data);
      }
    } else {
      $id = $this->encrypter->decrypt(hex2bin($idenc));
      $current = $model->getDataById($this->id, $id);

      if ($check && $current->user_email != $email) {
        $res = 'check';
        $link = 'Email sudah ada!';
      } else {
        $res = $model->updateData($data, $this->id, $id);
        // Kirim notifikasi ke pelanggan
        if ($res && $verifikasiExplicit && $oldData && $oldData->user_identity === 'ULM') {
          $newVerifikasi = (int) $data['verifikasi'];
          $this->sendVerificationResultNotification($oldData, $newVerifikasi === 1);
        }
      }
    }

    return $this->response->setJSON([
      'res' => $res,
      'link' => $link ?? '',
      'xname' => csrf_token(),
      'xhash' => csrf_hash()
    ]);
  }

  public function dataList()
  {
    $model = new MyModel($this->table);
    $data = [];

    $list = $model->getAllData('user_id', 'DESC');
    foreach ($list as $row) {
      $id = bin2hex($this->encrypter->encrypt($row->user_id));
      $response = [];

      $response[] = $row->user_name;

      $kontak = "Email : " . $row->user_email;
      $kontak .= "<br>No. Telepon : " . ($row->user_telpon ?? '-');
      $response[] = $kontak;

      $identity = $row->user_identity;

      if ($row->verifikasi == 1) {
        $verify = '<span class="badge bg-success">Terverifikasi</span>';
      } else {
        $verify = '<span class="badge bg-danger">Belum Terverifikasi</span>';
      }

      $response[] = $identity . '<br>' . $verify;

      if ($row->user_identity === 'NON ULM') {
        $response[] = $row->user_instansi ?? '-';
      } else {
        $response[] = '-';
      }

      $aktif = '<small><i class="bi bi-check-circle text-primary"></i> Aktif</small>';
      if ($row->status_user == 0) {
        $aktif = '<small class="text-danger"><i class="bi bi-x-circle"></i> Tidak Aktif</small>';
      }

      $response[] = $aktif;
      $response[] = $this->aksi($id);

      $data[] = $response;
    }

    $output = ["items" => $data];
    return $this->response->setJSON($output);
  }

  private function aksi($id)
  {
    return '<div id="' . $id . '" class="float-end">
            <span class="text-secondary btn-action" title="Ubah" onclick="editItem(event)">
                <i class="bi bi-pencil-square"></i></span>
            <label class="divider">|</label>
            <span class="text-danger btn-action" title="Hapus" onclick="deleteItem(event)">
                <i class="bi bi-trash"></i></span>
        </div>';
  }


  private function doUpload($file)
  {
    $result = ['status' => false, 'msg' => 'File tidak valid atau sudah dipindahkan', 'filename' => ''];

    if (!($file && $file->isValid() && !$file->hasMoved())) {
      return $result;
    }

    $allowedExt = ['jpg', 'jpeg', 'png', 'pdf'];
    $allowedMime = ['image/jpeg', 'image/png', 'application/pdf'];

    $ext = strtolower($file->getClientExtension());

    $tmpName = $file->getTempName();
    $detectedMime = null;
    if (is_file($tmpName)) {
      if (function_exists('finfo_open')) {
        $finfo = finfo_open(FILEINFO_MIME_TYPE);
        $detectedMime = finfo_file($finfo, $tmpName);
        finfo_close($finfo);
      } else {
        $detectedMime = $file->getMimeType();
      }
    } else {
      return ['status' => false, 'msg' => 'File sementara tidak ditemukan', 'filename' => ''];
    }
    if (!in_array($ext, $allowedExt) || !in_array($detectedMime, $allowedMime)) {
      return ['status' => false, 'msg' => 'Format file tidak diperbolehkan (hanya JPG, PNG, PDF)', 'filename' => ''];
    }

    // Validasi image jika bukan PDF
    if ($ext !== 'pdf' && @getimagesize($tmpName) === false) {
      return ['status' => false, 'msg' => 'File bukan gambar asli', 'filename' => ''];
    }

    // Validasi ukuran file (contoh: max 2MB)
    if ($file->getSize() > 2 * 1024 * 1024) {
      return ['status' => false, 'msg' => 'Ukuran file maksimal 2MB', 'filename' => ''];
    }

    try {
      $filename = time() . bin2hex(random_bytes(5)) . '.' . $ext;
    } catch (\Exception $e) {
      $filename = time() . '_' . bin2hex(openssl_random_pseudo_bytes(5)) . '.' . $ext;
    }

    $path = FCPATH . 'uploads/bukti';
    if (!is_dir($path)) {
      @mkdir($path, 0755, true);
    }

    try {
      $file->move($path, $filename, true);
    } catch (\Exception $e) {
      return ['status' => false, 'msg' => 'Gagal memindahkan file: ' . $e->getMessage(), 'filename' => ''];
    }

    return ['status' => true, 'msg' => 'OK', 'filename' => $filename];
  }

  /**
   * Kirim notifikasi hasil verifikasi ke pelanggan
   */
  protected function sendVerificationResultNotification(object $userData, bool $accepted): void
  {
    try {
      $notificationController = new ProfileNotificationController();
      $notificationController->sendVerificationResultNotification($userData, $accepted);
    } catch (\Exception $e) {
      log_message('error', 'Failed to send verification result notification: ' . $e->getMessage());
    }
  }
}
