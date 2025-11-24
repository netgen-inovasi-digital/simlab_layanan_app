<?php  

namespace Modules\ProfilUser\Controllers;

use App\Controllers\BaseController;
use App\Models\MyModel;

class ProfilUser extends BaseController
{
    private $table = 'simlab_account_users';
    private $id    = 'user_id';

    public function index()
    {
        $data = [
            'title' => 'Profil',
            'get'   => $this->getProfil(),
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
        $model  = new MyModel($this->table);
        $user   = $model->getDataById($this->id, $idUser);

        $data = [
            'user_name'     => $user->user_name,
            'user_email'    => $user->user_email,
            'user_telpon'   => $user->user_telpon ?? '',
            'user_instansi' => $user->user_instansi ?? '',
            'user_identity' => $user->user_identity,
            'verifikasi'    => $user->verifikasi ?? 0,
            'status_user'   => $user->status_user,
            'role_id'       => $user->role_id,
            'bukti'         => $user->bukti ?? '',
            'bukti_url'     => !empty($user->bukti) ? base_url('uploads/bukti/' . $user->bukti) : null,
            'showInstansi'  => ($user->user_identity ?? '') === 'NON ULM',
            'showBukti'     => ($user->user_identity ?? '') === 'ULM',
        ];

        // Status verifikasi
        $identityFilled        = !empty($user->user_identity);
        $telpFilled            = !empty($user->user_telpon);
        $buktiOrInstansiFilled = ($user->user_identity === 'ULM' && !empty($user->bukti)) ||
                                 ($user->user_identity === 'NON ULM' && !empty($user->user_instansi));

        if (!$identityFilled || !$telpFilled || !$buktiOrInstansiFilled) {
            $data['statusVerifikasi'] = ['text' => 'Silahkan lengkapi identitas Anda', 'class' => 'bg-danger'];
        } elseif ((int)$user->verifikasi === 0) {
            $data['statusVerifikasi'] = ['text' => 'Harap tunggu konfirmasi admin', 'class' => 'bg-success'];
        } else {
            $data['statusVerifikasi'] = ['text' => 'Terverifikasi', 'class' => 'bg-primary', 'icon' => 'bi bi-check-circle'];
        }

        return (object) $data;
    }

   public function submit()
{
    $passwordLama   = (string)$this->request->getPost('old_password');      // opsional (hanya jika ganti password)
    $passwordBaru   = (string)$this->request->getPost('new_password');      // opsional
    $ulangiPassword = (string)$this->request->getPost('confirm_password');  // opsional
    $emailPost      = strtolower(trim((string)$this->request->getPost('email')));

    $model = new MyModel($this->table);
    $user  = $model->getDataById($this->id, session()->get('id_user'));

    // Siapkan data awal
    $data = [
        'user_telpon'   => trim((string)$this->request->getPost('user_telpon')),
        'user_instansi' => trim((string)$this->request->getPost('user_instansi')),
        'user_identity' => (string)$this->request->getPost('user_identity'),
    ];

    // ========== VALIDASI WAJIB LENGKAPI IDENTITAS ==========
    if ($data['user_identity'] === '') {
        return $this->response->setJSON([
            'res'   => 'error',
            'msg'   => 'Silahkan pilih status identitas (ULM / NON ULM)',
            'xname' => csrf_token(),
            'xhash' => csrf_hash()
        ]);
    }

    if ($data['user_telpon'] === '') {
        return $this->response->setJSON([
            'res'   => 'error',
            'msg'   => 'Nomor telepon wajib diisi',
            'xname' => csrf_token(),
            'xhash' => csrf_hash()
        ]);
    }

    // Ambil file bukti (jika ada di request)
    $fileBukti   = $this->request->getFile('bukti_file');
    $hasNewBukti = $fileBukti && $fileBukti->isValid() && !$fileBukti->hasMoved();

    // Jika ULM → wajib ada bukti (baru atau sudah tersimpan sebelumnya)
    if ($data['user_identity'] === 'ULM') {
        $alreadyHasBukti = !empty($user->bukti);
        if (!$alreadyHasBukti && !$hasNewBukti) {
            return $this->response->setJSON([
                'res'   => 'error',
                'msg'   => 'Silahkan upload bukti untuk identitas ULM',
                'xname' => csrf_token(),
                'xhash' => csrf_hash()
            ]);
        }
    }

    // Jika NON ULM → instansi wajib diisi
    if ($data['user_identity'] === 'NON ULM' && $data['user_instansi'] === '') {
        return $this->response->setJSON([
            'res'   => 'error',
            'msg'   => 'Alamat Instansi wajib diisi untuk NON ULM',
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
    $emailLama    = strtolower((string)$user->user_email);
    $emailBerubah = ($emailPost !== '' && $emailPost !== $emailLama);

    if ($emailBerubah) {
        if (!filter_var($emailPost, FILTER_VALIDATE_EMAIL)) {
            return $this->response->setJSON([
                'res'   => 'error',
                'msg'   => 'Format email tidak valid.',
                'xname' => csrf_token(),
                'xhash' => csrf_hash()
            ]);
        }
        if (strlen($emailPost) > 191) {
            return $this->response->setJSON([
                'res'   => 'error',
                'msg'   => 'Email terlalu panjang (maks 191 karakter).',
                'xname' => csrf_token(),
                'xhash' => csrf_hash()
            ]);
        }
        // Cek unik
        $db = \Config\Database::connect();
        $exists = $db->table($this->table)
            ->where('LOWER(user_email)', $emailPost)
            ->where($this->id.' !=', (int)session()->get('id_user'))
            ->countAllResults();

        if ($exists > 0) {
            return $this->response->setJSON([
                'res'   => 'error',
                'msg'   => 'Email sudah terpakai. Gunakan email lain.',
                'xname' => csrf_token(),
                'xhash' => csrf_hash()
            ]);
        }

        $data['user_email'] = $emailPost;
    }

    // ========== UBAH PASSWORD (OPSIONAL — HANYA JIKA DIISI) ==========
    if ($passwordLama !== '' || $passwordBaru !== '' || $ulangiPassword !== '') {
        if ($passwordLama === '' || $passwordBaru === '' || $ulangiPassword === '') {
            return $this->response->setJSON([
                'res'   => 'error',
                'msg'   => 'Lengkapi Password Lama, Password Baru, dan Konfirmasi Password.',
                'xname' => csrf_token(),
                'xhash' => csrf_hash()
            ]);
        }
        if (!password_verify($passwordLama, $user->user_password)) {
            return $this->response->setJSON([
                'res'   => 'error',
                'msg'   => 'Password lama salah',
                'xname' => csrf_token(),
                'xhash' => csrf_hash()
            ]);
        }
        if ($passwordBaru !== $ulangiPassword) {
            return $this->response->setJSON([
                'res'   => 'error',
                'msg'   => 'Password baru dan konfirmasi tidak sama',
                'xname' => csrf_token(),
                'xhash' => csrf_hash()
            ]);
        }
        $data['user_password'] = password_hash($ulangiPassword, PASSWORD_DEFAULT);
    }

    // ========== UPLOAD BUKTI (KHUSUS ULM) ==========
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
                'res'   => 'error',
                'msg'   => $uploadResult['msg'],
                'xname' => csrf_token(),
                'xhash' => csrf_hash()
            ]);
        }
        $data['bukti'] = $uploadResult['filename'];
    }

    $res = $model->updateData($data, $this->id, session()->get('id_user'));

    if ($res) {
        return $this->response->setJSON([
            'res'   => 'refresh',
            'link'  => site_url('profiluser'),
            'xname' => csrf_token(),
            'xhash' => csrf_hash()
        ]);
    }

    return $this->response->setJSON([
        'res'   => 'error',
        'msg'   => 'Data gagal disimpan.',
        'xname' => csrf_token(),
        'xhash' => csrf_hash()
    ]);
}



    function doUpload($file)
    {
        if (!($file && $file->isValid() && !$file->hasMoved())) {
            return ['status' => false, 'msg' => 'File tidak valid atau sudah dipindahkan'];
        }

        $allowedExt  = ['jpg', 'jpeg', 'png'];
        $allowedMime = ['image/jpeg', 'image/png'];

        $ext  = strtolower($file->getClientExtension());
        $mime = $file->getMimeType();

        if (!in_array($ext, $allowedExt) || !in_array($mime, $allowedMime)) {
            return ['status' => false, 'msg' => 'Format gambar tidak diperbolehkan'];
        }

        if (@getimagesize($file->getTempName()) === false) {
            return ['status' => false, 'msg' => 'File bukan gambar asli'];
        }

        if ($file->getSize() > 2 * 1024 * 1024) {
            return ['status' => false, 'msg' => 'Ukuran file maksimal 2MB'];
        }

        try {
            $filename = time() . bin2hex(random_bytes(5)) . '.' . $ext;
        } catch (\Exception $e) {
            $filename = time() . '_' . bin2hex(openssl_random_pseudo_bytes(5)) . '.' . $ext;
        }

        $path = FCPATH . 'uploads' . DIRECTORY_SEPARATOR . 'bukti';
        if (!is_dir($path)) {
            @mkdir($path, 0755, true);
        }

        $file->move($path, $filename, true);

        return ['status' => true, 'filename' => $filename];
    }

    public function upload()
    {
        $file = $this->request->getFile('upload');
        $uploadResult = $this->doUpload($file);

        if ($uploadResult['status'] === true) {
            return $this->response->setJSON([
                'uploaded' => true,
                'url'      => base_url('uploads/bukti/' . $uploadResult['filename']),
                'xname'    => csrf_token(),
                'xhash'    => csrf_hash()
            ]);
        } else {
            return $this->response->setJSON([
                'uploaded' => false,
                'error'    => ['message' => $uploadResult['msg'] ?? 'Upload gagal.'],
                'xname'    => csrf_token(),
                'xhash'    => csrf_hash()
            ]);
        }
    }
}
