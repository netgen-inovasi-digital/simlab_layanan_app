<?php

namespace Modules\Persentase\Controllers;

use App\Controllers\BaseController;
use App\Models\MyModel;

class Persentase extends BaseController
{
    private $table = 'r_kolom_keuangan_detail';
    private $id = 'kode';

    public function index()
    {
        $session = session(); 
        $user_id = $session->get('id_user');

        $modelUser = new MyModel('users');
        $modelJenis = new MyModel('r_jenis');

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
        $data['jenis_kode'] = $get->jenis_kode;
        $data['label'] = $get->label;
        $data['non_ulm'] = $get->non_ulm;

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
            'jenis_kode' => $this->request->getPost('jenis_kode'),
            'label' => $this->request->getPost('label'),
            'non_ulm' => $this->request->getPost('non_ulm'),
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

        $kode = $this->request->getGet('jenis_kode');
        $page    = $this->request->getGet('page');
        $limit   = $this->request->getGet('limit');

        $joins = ['r_jenis j' => 'j.kode = d.jenis_kode'];
        $where = [];
        if (!empty($kode)) {
            $where['d.jenis_kode'] = $kode;
        }

        $list = $model->getAllDataWithJoinWhereOrder(
            $joins,
            $where,
            ['d.jenis_kode' => 'ASC'],
            'd.kode, d.jenis_kode, d.label, d.non_ulm, j.nama'
        );

        $data = [];
        foreach ($list as $row) {
            $id = bin2hex($this->encrypter->encrypt($row->kode));
            $response = [
                'kodeLayanan' => '<span class="badge bg-info">' . esc($row->jenis_kode) . ' - ' . esc($row->nama) . '</span>',
                'jenisBiaya'  => $row->label,
                'persentase'  => $row->non_ulm . ' %',
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
