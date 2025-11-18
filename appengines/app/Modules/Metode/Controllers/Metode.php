<?php

namespace Modules\Metode\Controllers;

use App\Controllers\BaseController;
use App\Models\MyModel;

class Metode extends BaseController
{
	private $table = 'r_metode';
	private $id = 'metode_kode';

	public function index()
	{
		$session = session(); 
		$user_id = $session->get('id_user');

		$modelUser = new MyModel('users');

		$data = [
			'title' => 'Data Metode',
			'user' => $modelUser->getDataById('id_user', $user_id),
		];

		return view('Modules\Metode\Views\v_metode', $data);
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
		$nama = $this->request->getPost('nama');
		
		$data = array(
			'nama' => $nama,
		);

		$model = new MyModel($this->table);
		
		// Cek duplikat nama metode (case-insensitive)
		$excludeWhere = [];
		if ($idenc != "") {
			// Jika update, exclude ID yang sedang diedit
			$id = $this->encrypter->decrypt(hex2bin($idenc));
			$excludeWhere = [$this->id => $id];
		}
		
		$isDuplicate = $model->checkDuplicateByField('nama', $nama, $excludeWhere, true);
		
		if ($isDuplicate) {
			// Return response untuk notifikasi duplikat
			return $this->response->setJSON(array(
				'res' => 'duplicate', 
				'message' => 'Nama metode sudah ada. Silakan gunakan nama yang berbeda.',
				'xname' => csrf_token(), 
				'xhash' => csrf_hash()
			));
		}
		
		// Jika tidak duplikat, lanjutkan insert/update
		if ($idenc == "") {
			$res = $model->insertData($data);
		} else {
			$id = $this->encrypter->decrypt(hex2bin($idenc));
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
			$id = bin2hex($this->encrypter->encrypt($row->metode_kode));
			$response = array();
			$response[] = esc($row->nama);
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
