<?php

namespace Modules\Mitra\Controllers;

use App\Controllers\BaseController;
use App\Models\MyModel;

class Mitra extends BaseController
{
	private $table = 'mitra';
	private $id = 'id_mitra';

    public function index()
    {
		$model = new MyModel('mitra');
        $data = [
            'title' => 'Data Mitra',
			'getMitra' => $model->getAllData('urutan', 'asc')
        ];
		return view('Modules\Mitra\Views\v_mitra', $data);
    }

    function edit($id)
	{
		$idenc = $id;
		$id = $this->encrypter->decrypt(hex2bin($id));
        $model = new MyModel($this->table);
		$get = $model->getDataById($this->id, $id);

		$data[csrf_token()] = csrf_hash();
		$data['id'] = $idenc;
		$data['nama'] = $get->nama;

		$where = [
			'id_mitra' => $id,
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
			$link = 'mitra';
		}
		return $this->response->setJSON(array('res'=> $res, 'link'=>$link ?? '', 
			'xname'=>csrf_token(), 'xhash'=>csrf_hash()));
	}
    
    public function submit()
    {
        $idenc = $this->request->getPost('id');
        $data = array(
			'nama' => $this->request->getPost('nama'),
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
			$link = 'mitra';
		}
		return $this->response->setJSON(array('res'=> $res, 'link'=>$link ?? '', 'xname'=>csrf_token(), 'xhash'=>csrf_hash()));
    }

	function updated()
	{
		$data = [];
		$items = $this->request->getPost('items'); 
		foreach ($items as $item) {
			$data[] = [
				'id_mitra'     => $this->encrypter->decrypt(hex2bin($item['id'])),
				'urutan'   => $item['code'],
			];
		}

		$model = new MyModel('mitra');
		$res = $model->updateDataBatch($data, 'id_mitra');
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
        $model = new MitraModel;
		$data = array();
		$list = $model->getAllData();
        foreach ($list as $row) 
        {
			$id = bin2hex($this->encrypter->encrypt($row->id_mitra));
			$response = array();
			$response[] = ($row->foto!= NULL) ? '<img class="img-thumbnail" width="40" src="'.esc(base_url('uploads/'.$row->foto)).'">' : '';
			$response[] = $row->nama;
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

		$model = new MyModel('mitra');
		$res = $model->updateData($data, $this->id, $id);
		return $this->response->setJSON(array('res' => $res, 'xhash' => csrf_hash()));
	}
}