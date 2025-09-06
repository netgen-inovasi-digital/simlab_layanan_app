<?php

namespace Modules\Alat\Controllers;

use App\Controllers\BaseController;
use App\Models\MyModel;

class Alat extends BaseController
{
    private $table = 'simlab_r_alat';
    private $id    = 'alatKode';

    public function index()
    {
        $session   = session(); 
        $user_id   = $session->get('id_user');
        $modelUser = new MyModel('users');

        $data = [
            'title' => 'Data Alat',
            'user'  => $modelUser->getDataById('id_user', $user_id),
        ];

        return view('Modules\Alat\Views\v_alat', $data);
    }

    public function edit($id)
    {
        $idenc = $id;
        $id    = $this->encrypter->decrypt(hex2bin($id));
        $model = new MyModel($this->table);
        $get   = $model->getDataById($this->id, $id);

        $data[csrf_token()] = csrf_hash();
        $data['id']        = $idenc;
        $data['alatKode']  = $get->alatKode;
        $data['alatNama']  = $get->alatNama;

        return $this->response->setJSON($data);
    }

    public function delete($id)
    {
        $id    = $this->encrypter->decrypt(hex2bin($id));
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
        $idenc = $this->request->getPost('id');
        $data = [
            'alatKode' => $this->request->getPost('alatKode'),
            'alatNama' => $this->request->getPost('alatNama'),
        ];

        $model = new MyModel($this->table);

        if ($idenc == "") {
            $res = $model->insertData($data);
        } else {
            $id  = $this->encrypter->decrypt(hex2bin($idenc));
            $res = $model->updateData($data, $this->id, $id);
        }

        return $this->response->setJSON([
            'res'   => $res,
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
            $id = bin2hex($this->encrypter->encrypt($row->alatKode));
            $response   = [];
            $response[] = '<span class="badge bg-secondary">' . esc($row->alatKode) . '</span>';
            $response[] = esc($row->alatNama);
            $response[] = $this->aksi($id);
            $data[]     = $response;
        }

        return $this->response->setJSON(["items" => $data]);
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
