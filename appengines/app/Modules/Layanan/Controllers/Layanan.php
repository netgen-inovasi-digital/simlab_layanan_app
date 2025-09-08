<?php

namespace Modules\Layanan\Controllers;

use App\Controllers\BaseController;
use App\Models\MyModel;

class Layanan extends BaseController
{
	private $table = 'layanan';
	private $id = 'id_layanan';

    public function index()
    {
		// ===== model Layanan ===== //
		$modelLayanan = new MyModel('layanan');

		// ===== model pages ===== //
		$modelHalaman = new MyModel('pages');

        $data = [
            'title' => 'Data Layanan',
			'getLayanan' => $modelLayanan->getAllData('urutan', 'asc'),
			'getHalaman' => $modelHalaman->getAllDataById(['status' => 'publish']),
        ];
		return view('Modules\Layanan\Views\v_layanan', $data);
    }

    function edit($id)
	{
		$idenc = $id;
		$id = $this->encrypter->decrypt(hex2bin($id));
        $model = new MyModel($this->table);
		$get = $model->getDataById($this->id, $id);
		

		$data[csrf_token()] = csrf_hash();
		$data['id'] = $idenc;
		$data['judul'] = $get->judul;
		$data['deskripsi'] = $get->deskripsi;
		$data['link'] = $get->link;
		$data['instrumen'] = $get->instrumen;
    $data['biaya'] = $get->biaya;
    $data['satuan'] = $get->satuan;
		return $this->response->setJSON($data);
	}

    function delete($id)
	{
		$id = $this->encrypter->decrypt(hex2bin($id));
        $model = new MyModel($this->table);
		$res = $model->deleteData($this->id, $id);
		if($res) {
			$res = 'refresh'; 
			$link = 'layanan';
		}
		return $this->response->setJSON(array('res'=> $res, 'link'=>$link ?? '', 
			'xname'=>csrf_token(), 'xhash'=>csrf_hash()));
	}
    
    public function submit()
    {
        $idenc = $this->request->getPost('id');
        $data = array(
			'judul' => $this->request->getPost('judul'),
			'deskripsi' => $this->request->getPost('deskripsi'),
			'link' => $this->request->getPost('link'),
			'instrumen' => $this->request->getPost('instrumen'),
        'biaya' => $this->request->getPost('biaya'),
        'satuan' => $this->request->getPost('satuan'),
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
			$link = 'layanan';
		}
		return $this->response->setJSON(array('res'=> $res, 'link'=>$link ?? '', 'xname'=>csrf_token(), 'xhash'=>csrf_hash()));
    }

	function updated()
	{
		$data = [];
		$items = $this->request->getPost('items'); 
		foreach ($items as $item) {
			$data[] = [
				'id_layanan'     => $this->encrypter->decrypt(hex2bin($item['id'])),
				'urutan'   => $item['code'],
			];
		}

		$model = new MyModel('layanan');
		$res = $model->updateDataBatch($data, 'id_layanan');
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

    // public function dataList()
    // {
    //     $model = new MyModel('layanan');
	// 	$data = array();
	// 	$list = $model->getAllData();
    //     foreach ($list as $row) 
    //     {
	// 		$id = bin2hex($this->encrypter->encrypt($row->id));
	// 		$response = array();
	// 		$response[] = ($row->foto!= NULL) ? '<img class="img-thumbnail" width="40" src="'.esc(base_url('uploads/'.$row->foto)).'">' : '';
	// 		$response[] = $row->judul;
	// 		$response[] = $row->deskripsi;
	// 		$response[] = $this->aksi($id);
	// 		$data[] = $response;
	// 	}
	// 	$output = array("items" => $data);
	// 	return $this->response->setJSON($output);
    // }

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

		$model = new MyModel('layanan');
		$res = $model->updateData($data, $this->id, $id);
		return $this->response->setJSON(array('res' => $res, 'xhash' => csrf_hash()));
	}
}