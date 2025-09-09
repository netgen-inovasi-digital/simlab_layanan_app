<?php

namespace Modules\Laboran\Controllers;

use App\Controllers\BaseController;
use App\Models\MyModel;

class Laboran extends BaseController
{
    private $table = 'simlab_account';
    private $id = 'username'; // Primary key adalah username

    public function index()
    {
        $data = [
            'title' => 'Data Laboran'
        ];
        return view('Modules\Laboran\Views\v_laboran', $data);
    }

    public function edit($id)
    {
        $idenc = $id;
        $id = $this->encrypter->decrypt(hex2bin($id));
        $model = new MyModel($this->table);
        $get = $model->getDataById($this->id, $id);

        $data[csrf_token()] = csrf_hash();
        $data['id'] = $idenc;
        $data['username'] = $get->username;
        $data['lab_kode'] = $get->lab_kode;
        $data['status'] = $get->status_user;

        return $this->response->setJSON($data);
    }

    public function delete($id)
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
        $idenc = $this->request->getPost('id');
        $username = $this->request->getPost('username');

        $data = [
            'username'    => $username,
            'lab_kode'    => $this->request->getPost('lab_kode'),
            'status_user' => $this->request->getPost('status'),
        ];

        // Password hanya diisi jika user memasukkan password
        $password = $this->request->getPost('password');
        if ($password != "") {
            $data['password'] = password_hash($password, PASSWORD_DEFAULT);
        }

        $model = new MyModel($this->table);
        $check = $model->getDataById('username', $username);

        if ($idenc == "") {
            // Jika tambah data baru, role_id default = 5
            $data['role_id'] = 5;

            if ($check) {
                $res = 'check';
                $link = 'Username sudah ada!';
            } else {
                $res = $model->insertData($data);
            }
        } else {
            // Jika update data
            $id = $this->encrypter->decrypt(hex2bin($idenc));
            $current = $model->getDataById($this->id, $id);

            if ($check && $current->username != $username) {
                $res = 'check';
                $link = 'Username sudah ada!';
            } else {
                $res = $model->updateData($data, $this->id, $id);
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
        $data = array();

        // Ambil semua data
        $list = $model->getAllData();

        foreach ($list as $row) 
        {
            // Hanya tampilkan data yang lab_kode tidak NULL atau kosong
            if ($row->lab_kode === null || $row->lab_kode === '') {
                continue; 
            }

            $id = bin2hex($this->encrypter->encrypt($row->username)); 
            $response = array();

            $response[] = $row->username;           
            $response[] = $row->lab_kode;          

            $aktif = '<small><i class="bi bi-check-circle text-primary"></i> Aktif</small>';
            if ($row->status_user == 0) {
                $aktif = '<small class="text-danger"><i class="bi bi-x-circle"></i> Tidak Aktif</small>';
            }
            $response[] = $aktif;

            $response[] = $this->aksi($id);

            $data[] = $response;
        }

        $output = array("items" => $data);
        return $this->response->setJSON($output);
    }

    private function aksi($id)
    {
        return '<div id="' . $id . '" class="float-end">
            <span class="text-secondary btn-action" title="Ubah" onclick="editItem(event)">
                <i class="bi bi-pencil-square"></i>
            </span> 
            <label class="divider">|</label>
            <span class="text-danger btn-action" title="Hapus" onclick="deleteItem(event)">
                <i class="bi bi-trash"></i>
            </span>
        </div>';
    }
}
