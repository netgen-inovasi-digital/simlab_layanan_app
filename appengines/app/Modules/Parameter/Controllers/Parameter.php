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
		$paraKode = $this->request->getPost('paraKode');
		
		$data = array(
			'paraKode' => $paraKode,
			'paraNama' => $this->request->getPost('paraNama'),
		);

		$model = new MyModel($this->table);
		
		if ($idenc == "") {
			// Cek apakah kode parameter sudah ada untuk insert
			$cekKode = $model->getDataById($this->id, $paraKode);
			if ($cekKode) {
				return $this->response->setJSON([
					'res'   => 'check',
					'msg'   => "Kode parameter <strong>{$paraKode}</strong> sudah ada. Silakan gunakan kode yang berbeda.",
					'xname' => csrf_token(),
					'xhash' => csrf_hash()
				]);
			}
			
			$res = $model->insertData($data);
		} else {
			$idLama = $this->encrypter->decrypt(hex2bin($idenc));
			
			// Hanya cek duplikat jika kode diubah
			if ($idLama != $paraKode) {
				// Cek apakah kode parameter baru sudah digunakan oleh data lain
				$cekKode = $model->getDataById($this->id, $paraKode);
				if ($cekKode) {
					return $this->response->setJSON([
						'res'   => 'check',
						'msg'   => "Kode parameter <strong>{$paraKode}</strong> sudah digunakan oleh data lain. Silakan gunakan kode yang berbeda.",
						'xname' => csrf_token(),
						'xhash' => csrf_hash()
					]);
				}
			}
			
			$res = $model->updateData($data, $this->id, $idLama);
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
		$modelLayanan = new MyModel('r_layanan_pengujian');
		$data = array();

		$list = $model->getAllData();
		foreach ($list as $row) {
			$id = bin2hex($this->encrypter->encrypt($row->paraKode));
			
			// Cek jumlah relasi di layanan pengujian
			$jumlahRelasi = $modelLayanan->where('kode_parameter', $row->paraKode)->countAllResults();
			$msgRelasi = $jumlahRelasi > 0 ? "Anda akan menghapus {$jumlahRelasi} layanan lab jika menghapus parameter ini" : "";
			
			$response = array();
			$response[] = '<span class="badge bg-info">' . esc($row->paraKode) . '</span>';
			$response[] = esc($row->paraNama);
			$response[] = $this->aksi($id, $msgRelasi);
			$data[] = $response;
		}
		$output = array("items" => $data);
		return $this->response->setJSON($output);
	}

	function aksi($id, $msgRelasi = '')
	{
		$deleteOnclick = $msgRelasi ? "deleteItem(event, '{$msgRelasi}')" : "deleteItem(event)";
		
		return '<div id="' . $id . '" class="float-end">
			<span class="text-secondary btn-action" title="Ubah" onclick="editItem(event)">
				<i class="bi bi-pencil-square"></i></span> 
			<label class="divider">|</label>
			<span class="text-danger btn-action" title="Hapus" onclick="' . $deleteOnclick . '">
				<i class="bi bi-trash"></i></span>
		</div>';
	}
}


