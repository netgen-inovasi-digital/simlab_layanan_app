<?php

namespace Modules\Team\Controllers;

use App\Controllers\BaseController;
use App\Models\MyModel;

class Team extends BaseController
{
	private $table = 'team';
	private $id = 'id_team';

    public function index()
    {
		// ===== model team ===== //
		$model = new MyModel('team');

		// ===== model pages ===== //
		$modelHalaman = new MyModel('pages');

        $data = [
            'title' => 'Data Team',
			'getTeam' => $model->getAllData('urutan', 'asc'),
			'getHalaman' => $modelHalaman->getAllDataById(['status' => 'publish']),
        ];
		return view('Modules\Team\Views\v_team', $data);
    }

    function edit($id)
	{
		$idenc = $id;
		$id = $this->encrypter->decrypt(hex2bin($id));
        $model = new MyModel($this->table);
		$get = $model->getDataById($this->id, $id);

		$data[csrf_token()] = csrf_hash();
		$data['id'] = $idenc;
		$data['nama_team'] = $get->nama;
		$data['spesialis'] = $get->spesialis;
		$data['link'] = $get->link;

		$where = [
			'id_team' => $id,
		];
		$get = $model->getAllDataById($where);

		return $this->response->setJSON($data);
	}

    function delete($id)
	{
		$id = $this->encrypter->decrypt(hex2bin($id));
        $model = new MyModel($this->table);
		$res = $model->deleteData($this->id, $id);
		if($res) {
			$res = 'refresh'; 
			$link = 'team';
		}
		return $this->response->setJSON(array('res'=> $res, 'link'=>$link ?? '', 
			'xname'=>csrf_token(), 'xhash'=>csrf_hash()));
	}
    
    public function submit()
    {
        $idenc = $this->request->getPost('id');
        $data = array(
			'nama' => $this->request->getPost('nama_team'),
			'spesialis' => $this->request->getPost('spesialis'),
			'link' => $this->request->getPost('link'),
		);
		$foto = $this->request->getFile('foto');
		if($foto!="") {
			$filename = $this->doUpload($foto);
			if($filename!="") $data['foto'] = $filename;
		}

		$model = new MyModel($this->table);
		if($idenc == "") {
			$code = $this->request->getPost('code');
			$data['urutan'] = (int)$code + 1;
			$res = $model->insertData($data);
		}else{
			$id = $this->encrypter->decrypt(hex2bin($idenc));
			$res = $model->updateData($data, $this->id, $id);
		}

		if($res) {
			$res = 'refresh'; 
			$link = 'team';
		}
		return $this->response->setJSON(array('res'=> $res, 'link'=>$link ?? '', 'xname'=>csrf_token(), 'xhash'=>csrf_hash()));
    }

	function updated()
	{
		$data = [];
		$items = $this->request->getPost('items'); 
		foreach ($items as $item) {
			$data[] = [
				'id_team'     => $this->encrypter->decrypt(hex2bin($item['id'])),
				'urutan'   => $item['code'],
			];
		}

		$model = new MyModel('team');
		$res = $model->updateDataBatch($data, 'id_team');
		return $this->response->setJSON(array('res'=> $res, 'xhash'=>csrf_hash()));
	}

	function doUpload($file)
	{
		$filename = "";
		if($file) {
			if ($file->isValid() && ! $file->hasMoved())
			{
				$ext = $file->getClientExtension();
				$filename = time() . bin2hex(random_bytes(5)) . '.' . $ext;
				$path = FCPATH . 'uploads';
				$file->move($path, $filename, true);
			}
		} 
		return $filename;
	}

    public function dataList()
    {
        $model = new TeamModel;
		$data = array();
		$list = $model->getAllData();
        foreach ($list as $row) 
        {
			$id = bin2hex($this->encrypter->encrypt($row->id_team));
			$response = array();
			$response[] = ($row->foto!= NULL) ? '<img class="img-thumbnail" width="40" src="'.esc(base_url('uploads/'.$row->foto)).'">' : '';
			$response[] = $row->nama;
			$response[] = $row->spesialis;
			$response[] = $this->aksi($id);
			$data[] = $response;
		}
		$output = array("items" => $data);
		return $this->response->setJSON($output);
    }

	function aksi($id)
	{
		return '<div id="'.$id.'" class="float-end">
			<span class="text-secondary btn-action" title="Ubah" onclick="editItem(event)">
				<i class="bi bi-pencil-square"></i></span> 
			<label class="divider">|</label>
			<span class="text-danger btn-action" title="Hapus" onclick="deleteItem(event)">
				<i class="bi bi-trash"></i></span>
		</div>';
	}

	function toggle() {
		$idenc = $this->request->getPost('id');
		$id = $this->encrypter->decrypt(hex2bin($idenc));
		$status = $this->request->getPost('status');
		$data = [
			'status' => $status,
		];

		$model = new MyModel('team');
		$res = $model->updateData($data, $this->id, $id);
		return $this->response->setJSON(array('res' => $res, 'xhash' => csrf_hash()));
	}
}