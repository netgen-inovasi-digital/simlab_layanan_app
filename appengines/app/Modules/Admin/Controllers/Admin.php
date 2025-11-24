<?php 

namespace Modules\Admin\Controllers;

use App\Controllers\BaseController;
use App\Models\MyModel;

class Admin extends BaseController
{
    private $table = 'simlab_account';
    private $id = 'username';

    public function index()
    {
        $model = new MyModel('roles');
        $data = [
            'title' => 'Data Admin',
            'role'  => $model->getAllData()
        ];
        return view('Modules\Admin\Views\v_admin', $data);
    }

    function edit($id)
    {
        $idenc = $id;
        $id = $this->encrypter->decrypt(hex2bin($id));
        $model = new MyModel($this->table);
        $get = $model->getDataById($this->id, $id);

        $data[csrf_token()] = csrf_hash();
        $data['id'] = $idenc;
        $data['username'] = $get->username;
        $data['nama'] = $get->nama ?? '';
        $data['telepon'] = $get->Telepon ?? '';
        $data['role'] = $get->role_id;
        $data['status'] = $get->status_user;
        return $this->response->setJSON($data);
    }

    function delete($id)
    {
        $id = $this->encrypter->decrypt(hex2bin($id));
        $model = new MyModel($this->table);
        $res = $model->deleteData($this->id, $id);
        return $this->response->setJSON([
            'res' => $res,
            'xname' => csrf_token(),
            'xhash' => csrf_hash()
        ]);
    }

    public function submit()
    {
        $idenc    = $this->request->getPost('id');
        $username = $this->request->getPost('username');

        $data = [
            'username'    => $username,
            'nama'        => $this->request->getPost('nama'),
            'Telepon'     => $this->request->getPost('telepon'),
            'role_id'     => $this->request->getPost('role'),
            'status_user' => $this->request->getPost('status'),
        ];

        $password = $this->request->getPost('password');
        if ($password != "") {
            $data['password'] = password_hash($password, PASSWORD_DEFAULT);
        }

        $model = new MyModel($this->table);
        $check = $model->getDataById('username', $username);

        if ($idenc == "") {
            if ($check) {
                $res = 'check';
                $link = 'Username Sudah Ada!';
            } else {
                $res = $model->insertData($data);
            }
        } else {
            $id = $this->encrypter->decrypt(hex2bin($idenc));
            $current = $model->getDataById($this->id, $id);

            if ($check && $current->username != $username) {
                $res = 'check';
                $link = 'Username Sudah Ada!';
            } else {
                $res = $model->updateData($data, $this->id, $id);
            }
        }

        return $this->response->setJSON([
            'res'   => $res,
            'link'  => $link ?? '',
            'xname' => csrf_token(),
            'xhash' => csrf_hash()
        ]);
    }

		public function dataList()
		{
		$model = new MyModel($this->table);
		$data = [];

		$join = [
			'roles' => 'roles.id_role = simlab_account.role_id'
			];	

		$list = $model->getAllDataByJoin($join);

		foreach ($list as $row) {
			if ($row->role_id == 5) {
				continue;
			}

			$id = bin2hex($this->encrypter->encrypt($row->username));
			$response = [];

			$response[] = $row->username;
			$response[] = $row->nama ?? '-';
			$response[] = '<small class="badge bg-light text-muted">'.$row->nama_role.'</small>';

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


    /**
     * Normalisasi nomor telepon untuk WhatsApp (hapus karakter non-digit, tambahkan 62)
     */
    private function normalize_phone_for_whatsapp($rawPhone)
    {
        if (empty($rawPhone)) return '';
        // hapus semua karakter kecuali digit
        $digits = preg_replace('/\D/', '', trim($rawPhone));
        if (empty($digits)) return '';

        // jika diawali 0, ganti dengan 62
        if (substr($digits, 0, 1) === '0') {
            $digits = '62' . substr($digits, 1);
        }
        // jika belum diawali 62, tambahkan 62
        elseif (substr($digits, 0, 2) !== '62') {
            $digits = '62' . $digits;
        }

        return $digits;
    }

    /**
     * Membuat tombol WhatsApp untuk user tertentu
     */
    private function whatsapp_button($user)
    {
        if (!$user || empty($user->Telepon)) {
            return '';
        }

        $waDigits = $this->normalize_phone_for_whatsapp($user->Telepon);
        if ($waDigits === '') {
            return '';
        }

        $displayName = $user->nama ?? $user->username ?? 'Admin';
        $message = "Halo " . $displayName . ", saya ingin bertanya terkait layanan.";
        $msgEncoded = rawurlencode($message);
        $waUrl = "https://wa.me/" . $waDigits . "?text=" . $msgEncoded;

        return '<span class="text-success btn-action" title="Chat via WhatsApp" '
            . 'style="display:inline-flex;align-items:center;justify-content:center;cursor:pointer;" '
            . 'onclick="window.open(\'' . esc($waUrl) . '\', \'_blank\', \'noopener\')">'
            . '<i class="bi bi-whatsapp"></i>'
            . '</span>';
    }

    function aksi($id)
    {
        $btn = '<div id="' . $id . '" class="float-end d-flex align-items-center" style="gap:6px;">';

        // Tombol WhatsApp (jika ada nomor telepon)
        try {
            $username = $this->encrypter->decrypt(hex2bin($id));
            $model = new MyModel($this->table);
            $user = $model->getDataById($this->id, $username);
            $waBtn = $this->whatsapp_button($user);
            if (!empty($waBtn)) {
                $btn .= $waBtn;
                $btn .= '<span class="text-muted" style="margin:0 4px;">|</span>';
            }
        } catch (\Throwable $e) {
            // Jika error, lanjutkan tanpa tombol WA
        }

        // Tombol Edit
        $btn .= '<span class="text-secondary btn-action" title="Ubah" onclick="editItem(event)" style="display:inline-flex;align-items:center;justify-content:center;">';
        $btn .= '<i class="bi bi-pencil-square"></i>';
        $btn .= '</span>';

        // Divider
        $btn .= '<span class="text-muted" style="margin:0 4px;">|</span>';

        // Tombol Delete
        $btn .= '<span class="text-danger btn-action" title="Hapus" onclick="deleteItem(event)" style="display:inline-flex;align-items:center;justify-content:center;">';
        $btn .= '<i class="bi bi-trash"></i>';
        $btn .= '</span>';

        $btn .= '</div>';
        return $btn;
    }
}
