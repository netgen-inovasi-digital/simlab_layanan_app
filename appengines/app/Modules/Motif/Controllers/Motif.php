<?php

namespace Modules\Motif\Controllers;

use App\Controllers\BaseController;
use App\Models\MyModel;

class Motif extends BaseController
{
	private $table = 'motifs';
	private $id = 'id';

	public function index()
	{
		$session = session(); // aktifkan session
		$user_id = $session->get('id_user');

		$modelUser = new MyModel('users');

		$data = [
			'title' => 'Data Motif',
			'user' => $modelUser->getDataById('id_user', $user_id),
		];

		return view('Modules\Motif\Views\v_motif', $data);
	}

	function edit($id)
	{
		$idenc = $id;
		$id = $this->encrypter->decrypt(hex2bin($id));
		$model = new MyModel($this->table);
		$get = $model->getDataById($this->id, $id);

		$data[csrf_token()] = csrf_hash();
		$data['id'] = $idenc;
		$data['name'] = $get->name;
		$data['deskripsi'] = $get->deskripsi;

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
			'name' => $this->request->getPost('name'),
			'deskripsi' => $this->request->getPost('deskripsi'),
		);

		$foto = $this->request->getFile('foto');
		if ($foto && $foto->isValid() && !$foto->hasMoved()) {
			$filename = $this->doUpload($foto);
			if ($filename != "") $data['foto'] = $filename;
		}

		$model = new MyModel($this->table);
		
		if ($idenc == "") {
			$res = $model->insertData($data);
		} else {
			$id = $this->encrypter->decrypt(hex2bin($idenc));
			$res = $model->updateData($data, $this->id, $id);
		}
		return $this->response->setJSON(array('res' => $res, 'xname' => csrf_token(), 'xhash' => csrf_hash()));
	}

	function doUpload($file)
	{
		$filename = "";
		if ($file) {
			if ($file->isValid() && ! $file->hasMoved()) {
				$ext = $file->getClientExtension();
				$filename = time() . bin2hex(random_bytes(5)) . '.' . $ext;
				$path = FCPATH . 'uploads';
				$file->move($path, $filename, true);
			}
		}
		return $filename;
	}

	public function upload()
	{
		$file = $this->request->getFile('upload');
		$filename = $this->doUpload($file);

		if ($filename !== "") {
			return $this->response->setJSON([
				'uploaded' => true,
				'url'      => base_url('uploads/' . $filename),
				'xname'    => csrf_token(),
				'xhash'    => csrf_hash()
			]);
		} else {
			return $this->response->setJSON([
				'uploaded' => false,
				'error'    => ['message' => 'Upload gagal.'],
				'xname'    => csrf_token(),
				'xhash'    => csrf_hash()
			]);
		}
	}

	public function dataList()
	{
		$model = new MyModel($this->table);
		$data = array();

		$list = $model->getAllData();
		foreach ($list as $row) {
			$id = bin2hex($this->encrypter->encrypt($row->id));
			$response = array();
			$response[] = '<span class="badge bg-secondary">' . esc($row->name) . '</span>';
			$response[] = $row->deskripsi;
			$response[] = ($row->foto != NULL) ? '<img class="img-thumbnail" width="80" src="' . esc(base_url('uploads/' . $row->foto)) . '">' : 'Tidak ada';
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
