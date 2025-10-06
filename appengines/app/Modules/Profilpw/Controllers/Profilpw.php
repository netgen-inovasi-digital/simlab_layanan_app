<?php

namespace Modules\Profilpw\Controllers;

use App\Controllers\BaseController;
use App\Models\MyModel;

class Profilpw extends BaseController
{
    private $table = 'simlab_account_users';
    private $id    = 'user_id';

    public function index()
    {
        $data = [
            'title' => 'Profil',
            'get'   => $this->getProfil()
        ];
        return view('Modules\Profilpw\Views\v_profilpw', $data);
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
        $identityFilled       = !empty($user->user_identity);
        $telpFilled           = !empty($user->user_telpon);
        $buktiOrInstansiFilled = ($user->user_identity === 'ULM' && !empty($user->bukti)) ||
                                 ($user->user_identity === 'NON ULM' && !empty($user->user_instansi));

        if (!$identityFilled || !$telpFilled || !$buktiOrInstansiFilled) {
            $data['statusVerifikasi'] = ['text' => 'Silahkan lengkapi identitas Anda', 'class' => 'bg-danger'];
        } elseif ($user->verifikasi == 0) {
            $data['statusVerifikasi'] = ['text' => 'Harap tunggu konfirmasi admin', 'class' => 'bg-success'];
        } else {
            $data['statusVerifikasi'] = ['text' => 'Terverifikasi', 'class' => 'bg-primary', 'icon' => 'bi bi-check-circle'];
        }

        return (object) $data;
    }

    public function submit()
    {
        $passwordLama   = $this->request->getPost('old_password');
        $passwordBaru   = $this->request->getPost('new_password');
        $ulangiPassword = $this->request->getPost('confirm_password');

        $model = new MyModel($this->table);
        $user  = $model->getDataById($this->id, session()->get('id_user'));

        $data = [
            'user_email'    => $this->request->getPost('email'),
            'user_telpon'   => $this->request->getPost('user_telpon'),
            'user_instansi' => $this->request->getPost('user_instansi'),
            'user_identity' => $this->request->getPost('user_identity'),
        ];

        if ($data['user_identity'] === 'NON ULM' && !empty($user->bukti)) {
            $oldPath = FCPATH . 'uploads/bukti/' . $user->bukti;
            if (is_file($oldPath) && strpos(realpath($oldPath), realpath(FCPATH . 'uploads/bukti')) === 0) {
                unlink($oldPath);
            }
            $data['bukti'] = null;
        }

        if (!empty($passwordLama) && !empty($passwordBaru)) {
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

            $data['user_password'] = password_hash($passwordBaru, PASSWORD_DEFAULT);
        }

        if ($data['user_identity'] === 'ULM') {
            $file = $this->request->getFile('bukti_file');
            if ($file && $file->isValid() && !$file->hasMoved()) {
                if (!empty($user->bukti)) {
                    $oldPath = FCPATH . 'uploads/bukti/' . $user->bukti;
                    if (is_file($oldPath) && strpos(realpath($oldPath), realpath(FCPATH . 'uploads/bukti')) === 0) {
                        unlink($oldPath);
                    }
                }

                $filename = $this->doUpload($file);
                if ($filename != "") $data['bukti'] = $filename;
            }
        }

        $res = $model->updateData($data, $this->id, session()->get('id_user'));

        if ($res) {
            // Response sukses AJAX, **tidak ada redirect atau refresh**
            return $this->response->setJSON([
                'res'   => true,
                'msg'   => 'Data berhasil disimpan.',
                'xname' => csrf_token(),
                'xhash' => csrf_hash()
            ]);
        } else {
            return $this->response->setJSON([
                'res'   => 'error',
                'msg'   => 'Data gagal disimpan.',
                'xname' => csrf_token(),
                'xhash' => csrf_hash()
            ]);
        }
    }

    private function doUpload($file)
    {
        $filename = "";
        if ($file && $file->isValid() && !$file->hasMoved()) {
            $ext      = $file->getClientExtension();
            $filename = time() . bin2hex(random_bytes(5)) . '.' . $ext;
            $file->move(FCPATH . 'uploads/bukti', $filename, true);
        }
        return $filename;
    }
}
