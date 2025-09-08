<?php

namespace Modules\Profilpw\Controllers;

use App\Controllers\BaseController;
use App\Models\MyModel;

class Profilpw extends BaseController
{
	private $table = 'simlab_account_users';
	private $id = 'user_id';

    public function index()
    {
        $data = [
            'title' => 'Profil',
			'get' => $this->getProfil()
        ];
		return view('Modules\Profilpw\Views\v_profilpw', $data);
    }

	function getProfil()
	{
		$idUser = session()->get('id_user'); // disesuaikan dengan session dari auth
		$model = new MyModel($this->table);
		$get = $model->getDataById($this->id, $idUser);

		$data = [
			'user_name'   => $get->user_name,
			'user_email'  => $get->user_email,
			'user_identity' => $get->user_identity,
			'status_user' => $get->status_user,
			'role_id'     => $get->role_id
		];

		return json_decode(json_encode($data));
	}
    
    public function submit()
    {
		$passwordLama   = $this->request->getPost('old_password');
		$passwordBaru   = $this->request->getPost('new_password');
		$ulangiPassword = $this->request->getPost('confirm_password');

		$model = new MyModel($this->table);
		$user  = $model->getDataById($this->id, session()->get('id_user'));

		$data = [
			'user_email' => $this->request->getPost('email'),
		];

		// Validasi password lama
		if (!empty($passwordLama) && !empty($passwordBaru)) {
			if (!password_verify($passwordLama, $user->user_password)) {
				return $this->response->setJSON([
					'res' => 'error',
					'msg' => 'Password lama salah',
					'xname' => csrf_token(),
					'xhash' => csrf_hash()
				]);
			}

			// Cek ulangi password
			if ($passwordBaru !== $ulangiPassword) {
				return $this->response->setJSON([
					'res' => 'error',
					'msg' => 'Password baru dan konfirmasi tidak sama',
					'xname' => csrf_token(),
					'xhash' => csrf_hash()
				]);
			}

			// Update password baru
			$data['user_password'] = password_hash($passwordBaru, PASSWORD_DEFAULT);
			$data['user_password_default'] = 0;
		}

		$res = $model->updateData($data, $this->id, session()->get('id_user'));
		
		if($res) {
			$res = 'refresh';
			$link = 'profilpw';
		}
		return $this->response->setJSON([
			'res'=> $res, 
			'link'=>$link ?? '', 
			'xname'=>csrf_token(), 
			'xhash'=>csrf_hash()
		]);
    }

	function doUpload($file)
	{
		$filename = "";
		if($file) {
			if ($file->isValid() && ! $file->hasMoved())
			{
				$ext = $file->getClientExtension();
				$filename = 'logo.' . $ext;
				$path = FCPATH . 'uploads';
				$file->move($path, $filename, true);
			}
		} 
		return $filename;
	}

	// $file = $this->request->getFile('image');
	// if ($file->isValid() && in_array($file->getMimeType(), ['image/jpeg', 'image/png', 'application/pdf'])) {
	// 	// simpan
	// }
}
