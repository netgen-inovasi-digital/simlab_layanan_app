<?php 

namespace Modules\Akun\Controllers;

use App\Controllers\BaseController;
use App\Models\MyModel;

class Akun extends BaseController
{
    private $table = 'simlab_account_users'; 
    private $id    = 'user_id';

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
        $id    = $this->encrypter->decrypt(hex2bin($id));

        $model = new MyModel($this->table);
        $get   = $model->getDataById($this->id, $id);

        $data[csrf_token()]   = csrf_hash();
        $data['id']           = $idenc;
        $data['user_name']    = $get->user_name;
        $data['user_email']   = $get->user_email;
        $data['user_telpon']  = $get->user_telpon; 
        $data['status_user']  = $get->status_user;
        $data['user_identity']= $get->user_identity;
        $data['user_instansi']= $get->user_instansi;

        return $this->response->setJSON($data);
    }

    public function delete($id)
    {
        $id = $this->encrypter->decrypt(hex2bin($id));

        $model = new MyModel($this->table);
        $res   = $model->deleteData($this->id, $id);

        return $this->response->setJSON([
            'res'   => $res,
            'xname' => csrf_token(),
            'xhash' => csrf_hash()
        ]);
    }

    public function submit()
    {
        $idenc    = $this->request->getPost('id');
        $username = $this->request->getPost('user_name');
        $email    = $this->request->getPost('user_email');
        $telpon   = $this->request->getPost('user_telpon');

        $data = [
            'user_name'     => $username,
            'user_email'    => $email,
            'user_telpon'   => $telpon, 
            'status_user'   => $this->request->getPost('status_user'),
            'user_identity' => $this->request->getPost('user_identity'),
            'role_id'       => 2,
        ];

        // simpan instansi hanya jika NON ULM
        if ($this->request->getPost('user_identity') === 'NON ULM') {
            $data['user_instansi'] = $this->request->getPost('user_instansi');
        } else {
            $data['user_instansi'] = null;
        }

        // password
        $password = $this->request->getPost('user_password');
        if (!empty($password)) {
            $data['user_password'] = password_hash($password, PASSWORD_DEFAULT);
        }

        $model = new MyModel($this->table);

        // cek apakah email sudah ada
        $check = $model->getDataById('user_email', $email);

        if ($idenc == "") {
            // INSERT
            if ($check) {
                $res  = 'check';
                $link = 'Email sudah ada!';
            } else {
                $res = $model->insertData($data);
            }
        } else {
            // UPDATE
            $id      = $this->encrypter->decrypt(hex2bin($idenc));
            $current = $model->getDataById($this->id, $id);

            if ($check && $current->user_email != $email) {
                $res  = 'check';
                $link = 'Email sudah ada!';
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
        $data  = [];

        $list = $model->getAllData();
        foreach ($list as $row) {
            $id       = bin2hex($this->encrypter->encrypt($row->user_id));
            $response = [];

            $response[] = $row->user_name;

            // kolom kontak → email + telepon
            $kontak  = "Email : " . $row->user_email;
            $kontak .= "<br>No. Telepon : " . ($row->user_telpon ?? '-'); 
            $response[] = $kontak;

            // === Status identitas + verifikasi ===
            $identity = $row->user_identity;

            if ($row->verifikasi == 1) {
                $verify = '<span class="badge bg-success">Terverifikasi</span>';
            } else {
                $verify = '<span class="badge bg-danger">Belum Terverifikasi</span>';
            }

            $response[] = $identity . '<br>' . $verify;



            // tampilkan instansi kalau NON ULM
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
}
