<?php

namespace Modules\Parameter\Controllers;

use App\Controllers\BaseController;
use App\Models\MyModel;

class Parameter extends BaseController
{
	private $table = 'simlab_r_parameter';
	private $id = 'paraKode';

	public function index()
	{
		$session = session(); 
		$user_id = $session->get('id_user');

		$modelUser = new MyModel('users');

		$data = [
			'title' => 'Data Parameter',
			'user' => $modelUser->getDataById('id_user', $user_id),
		];

		return view('Modules\Parameter\Views\v_parameter', $data);
	}

	function edit($id)
	{
		$idenc = $id;
		$id = $this->encrypter->decrypt(hex2bin($id));
		$model = new MyModel($this->table);
		$get = $model->getDataById($this->id, $id);

		$data[csrf_token()] = csrf_hash();
		$data['id'] = $idenc;
		$data['paraKode'] = $get->paraKode;
		$data['paraNama'] = $get->paraNama;

		return $this->response->setJSON($data);
	}

	function delete($id)
	{
		$id = $this->encrypter->decrypt(hex2bin($id));
		$model = new MyModel($this->table);
		$res = $model->deleteData($this->id, $id);
		return $this->response->setJSON(array('res' => $res, 'xname' => csrf_token(), 'xhash' => csrf_hash()));
	}

	public function submit()
	{
		$idenc = $this->request->getPost('id');
		$data = array(
			'paraKode' => $this->request->getPost('paraKode'),
			'paraNama' => $this->request->getPost('paraNama'),
		);

		$model = new MyModel($this->table);
		
		if ($idenc == "") {
			$res = $model->insertData($data);
		} else {
			$id = $this->encrypter->decrypt(hex2bin($idenc));
			$res = $model->updateData($data, $this->id, $id);
		}
		return $this->response->setJSON(array('res' => $res, 'xname' => csrf_token(), 'xhash' => csrf_hash()));
	}

	// public function upload()
	// {
	// 	return $this->response->setJSON([
	// 		'uploaded' => false,
	// 		'error'    => ['message' => 'Upload tidak digunakan untuk Parameter.'],
	// 		'xname'    => csrf_token(),
	// 		'xhash'    => csrf_hash()
	// 	]);
	// }

	public function dataList()
	{
		$model = new MyModel($this->table);
		$data = array();

		$list = $model->getAllData();
		foreach ($list as $row) {
			$id = bin2hex($this->encrypter->encrypt($row->paraKode));
			$response = array();
			$response[] = '<span class="badge bg-secondary">' . esc($row->paraKode) . '</span>';
			$response[] = esc($row->paraNama);
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


