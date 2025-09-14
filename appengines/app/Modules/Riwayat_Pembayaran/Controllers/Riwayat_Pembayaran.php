<?php

namespace Modules\Riwayat_Pembayaran\Controllers;

use App\Controllers\BaseController;
use App\Models\MyModel;

class Riwayat_Pembayaran extends BaseController
{
    private $table = 'simlab_t_pembayaran';
    private $id = 'bayarKode';

    public function index()
    {
        $session = session();
        $user_id = $session->get('id_user');

        $modelUser = new MyModel('users');

        $data = [
            'title' => 'Riwayat Pembayaran',
            'user' => $modelUser->getDataById('id_user', $user_id),
        ];

        return view('Modules\Riwayat_Pembayaran\Views\v_riwayat_pembayaran', $data);
    }

    function edit($id)
    {
        $idenc = $id;
        $id = service('encrypter')->decrypt(hex2bin($id));
        $model = new MyModel($this->table);
        $get = $model->getDataById($this->id, $id);

        $data[csrf_token()] = csrf_hash();
        $data['id'] = $idenc;
        $data['bayarLnKode'] = $get->bayarLnKode ?? '';
        $data['bayarJumlah'] = $get->bayarJumlah ?? '';
        $data['bayarTgl'] = $get->bayarTgl ?? '';
        $data['bayarStatus'] = $get->bayarStatus ?? '';

        return $this->response->setJSON($data);
    }

    function delete($id)
    {
        $id = service('encrypter')->decrypt(hex2bin($id));
        $model = new MyModel($this->table);
        $res = $model->deleteData($this->id, $id);
        return $this->response->setJSON(array('res' => $res, 'xname' => csrf_token(), 'xhash' => csrf_hash()));
    }

    public function submit()
    {
        $idenc = $this->request->getPost('id');
        $data = array(
            'bayarLnKode' => $this->request->getPost('bayarLnKode'),
            'bayarJumlah' => $this->request->getPost('bayarJumlah'),
            'bayarTgl' => $this->request->getPost('bayarTgl'),
            'bayarStatus' => $this->request->getPost('bayarStatus'),
        );

        $model = new MyModel($this->table);

        if ($idenc == "") {
            $res = $model->insertData($data);
        } else {
            $id = service('encrypter')->decrypt(hex2bin($idenc));
            $res = $model->updateData($data, $this->id, $id);
        }
        return $this->response->setJSON(array('res' => $res, 'xname' => csrf_token(), 'xhash' => csrf_hash()));
    }

    public function dataList()
    {
        $model = new MyModel($this->table);
        $data = array();

        $list = $model->getAllData();
        foreach ($list as $row) {
            $id = bin2hex(service('encrypter')->encrypt($row->{$this->id}));
            $response = array();
            $response[] = '<span class="badge bg-info">' . esc($row->bayarKode ?? '') . '</span>';
            $response[] = esc($row->bayarLnKode ?? '');
            $response[] = 'Rp ' . number_format($row->bayarJumlah ?? 0, 0, ',', '.');
            $response[] = esc($row->bayarTgl ?? '');
            $response[] = '<span class="badge bg-' . ($row->bayarStatus == 'lunas' ? 'success' : ($row->bayarStatus == 'pending' ? 'warning' : 'danger')) . '">' . esc($row->bayarStatus ?? '') . '</span>';
            $response[] = $this->aksi($id);
            $data[] = $response;
        }
        $output = array("items" => $data);
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
