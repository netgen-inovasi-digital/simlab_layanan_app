<?php

namespace Modules\Persentase\Controllers;

use App\Controllers\BaseController;
use App\Models\MyModel;

class Persentase extends BaseController
{
    private $table = 'simlab_r_kolom_keuangan_detail';
    private $id = 'kdKode';

    public function index()
    {
        $session = session(); 
        $user_id = $session->get('id_user');

        $modelUser = new MyModel('users');
        $modelJenis = new MyModel('simlab_r_jenis');

        $data = [
            'title' => 'Data Persentase',
            'user' => $modelUser->getDataById('id_user', $user_id),
            'jenis' => $modelJenis->getAllData(),
        ];

        return view('Modules\Persentase\Views\v_persentase', $data);
    }

    function edit($id)
    {
        $idenc = $id;
        $id = $this->encrypter->decrypt(hex2bin($id));
        $model = new MyModel($this->table);
        $get = $model->getDataById($this->id, $id);

        $data[csrf_token()] = csrf_hash();
        $data['id'] = $idenc;
        $data['kdJenKode'] = $get->kdJenKode;
        $data['kdKolomLabel'] = $get->kdKolomLabel;
        $data['kdPersenNONULM'] = $get->kdPersenNONULM;

        return $this->response->setJSON($data);
    }

    function delete($id)
    {
        $id = $this->encrypter->decrypt(hex2bin($id));
        $model = new MyModel($this->table);
        $res = $model->deleteData($this->id, $id);
        return $this->response->setJSON(array(
            'res' => $res, 
            'xname' => csrf_token(), 
            'xhash' => csrf_hash()
        ));
    }

    public function submit()
    {
        $idenc = $this->request->getPost('id');
        $data = array(
            'kdJenKode' => $this->request->getPost('kdJenKode'),
            'kdKolomLabel' => $this->request->getPost('kdKolomLabel'),
            'kdPersenNONULM' => $this->request->getPost('kdPersenNONULM'),
        );

        $model = new MyModel($this->table);
        
        if ($idenc == "") {
            $res = $model->insertData($data);
        } else {
            $id = $this->encrypter->decrypt(hex2bin($idenc));
            $res = $model->updateData($data, $this->id, $id);
        }

        return $this->response->setJSON(array(
            'res' => $res, 
            'xname' => csrf_token(), 
            'xhash' => csrf_hash()
        ));
    }

    public function dataList()
    {
        
        $model = new MyModel($this->table . ' d');

        $jenKode = $this->request->getGet('kdJenKode');

        $joins = ['simlab_r_jenis j' => 'j.jenKode = d.kdJenKode'];
        $where = [];
        if (!empty($jenKode)) {
            $where['d.kdJenKode'] = $jenKode;
        }

        $list = $model->getAllDataWithJoinWhereOrder(
            $joins,
            $where,
            ['d.kdJenKode' => 'ASC'],
            'd.kdKode, d.kdJenKode, d.kdKolomLabel, d.kdPersenNONULM, j.jenNama'
        );

        $data = [];
        foreach ($list as $row) {
            $id = bin2hex($this->encrypter->encrypt($row->kdKode));
            $response = [
                'kodeLayanan' => '<span class="badge bg-secondary">' . esc($row->kdJenKode) . ' - ' . esc($row->jenNama) . '</span>',
                'jenisBiaya'  => $row->kdKolomLabel,
                'persentase'  => $row->kdPersenNONULM . ' %',
                'aksi'        => $this->aksi($id),
            ];

            $data[] = $response;
        }

        $output = ["items" => $data];
        return $this->response->setJSON($output);
    }

    function aksi($id)
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
