<?php

namespace Modules\Lab\Controllers;

use App\Controllers\BaseController;
use App\Models\MyModel;

class Lab extends BaseController
{
	private $table = 'simlab_r_layanan_pengujian';
	private $id = 'ujiKode';   

	public function index()
	{
		$session = session();
		$user_id = $session->get('id_user');

		$modelUser = new MyModel('users');

		$modelDiskon  = new MyModel('simlab_t_diskon');

		$data = [
			'title' => 'Data Layanan Lab',
			'user'  => $modelUser->getDataById('id_user', $user_id),
			'diskon_ulm' => $modelDiskon->getDataById('kolom', 'ulm')->diskon ?? 0,
		];

		return view('Modules\Lab\Views\v_lab', $data);
	}

	function edit($id)
	{
		$idenc = $id;
		$id = $this->encrypter->decrypt(hex2bin($id));
		$model = new MyModel($this->table);
		$get = $model->getDataById($this->id, $id);

		$jenisModel = new MyModel('simlab_r_jenis');
		$alatModel  = new MyModel('simlab_r_alat');
		$paraModel  = new MyModel('simlab_r_parameter');

		$data[csrf_token()] = csrf_hash();
		$data['id']          = $idenc;
		$data['ujiJenKode']  = $get->ujiJenKode;
		$data['ujiAlatKode'] = $get->ujiAlatKode;
		$data['ujiParaKode'] = $get->ujiParaKode;
		$data['ujiLayanan']  = $get->ujiLayanan;
		$data['ujiSatuan']   = $get->ujiSatuan;
		$data['ujiBiaya']    = $get->ujiBiaya;

		$data['options'] = [
			'jenis'     => $jenisModel->getAllData(),
			'alat'      => $alatModel->getAllData(),
			'parameter' => $paraModel->getAllData(),
		];

		return $this->response->setJSON($data);
	}

	function delete($id)
	{
		$id = $this->encrypter->decrypt(hex2bin($id));
		$model = new MyModel($this->table);
		$res = $model->deleteData($this->id, $id);
		return $this->response->setJSON(['res' => $res, 'xname' => csrf_token(), 'xhash' => csrf_hash()]);
	}

	public function submit()
	{
		$idenc = $this->request->getPost('id');
		$data = [
			'ujiJenKode'   => $this->request->getPost('ujiJenKode'),
			'ujiAlatKode'  => $this->request->getPost('ujiAlatKode'),
			'ujiParaKode'  => $this->request->getPost('ujiParaKode'),
			'ujiLayanan'   => $this->request->getPost('ujiLayanan'),
			'ujiSatuan'    => $this->request->getPost('ujiSatuan'),
			'ujiBiaya'     => $this->request->getPost('ujiBiaya'),
		];

		$model = new MyModel($this->table);

		if ($idenc == "") {
			$res = $model->insertData($data);
		} else {
			$id = $this->encrypter->decrypt(hex2bin($idenc));
			$res = $model->updateData($data, $this->id, $id);
		}
		return $this->response->setJSON(['res' => $res, 'xname' => csrf_token(), 'xhash' => csrf_hash()]);
	}

	public function dataList()
	{
		$model = new MyModel($this->table);
		$data = [];

		$join = [
			'simlab_r_jenis j'     => 'j.jenKode = ' . $this->table . '.ujiJenKode',
			'simlab_r_alat a'      => 'a.alatKode = ' . $this->table . '.ujiAlatKode',
			'simlab_r_parameter p' => 'p.paraKode = ' . $this->table . '.ujiParaKode',
		];

		$select = $this->table . '.*, j.jenKode, j.jenNama, a.alatNama, p.paraNama';

		$where = [];
		$ujiJenKode = $this->request->getGet('ujiJenKode');
		if (!empty($ujiJenKode)) {
			$where[$this->table . '.ujiJenKode'] = $ujiJenKode;
		}

		$list = $model->getAllDataByJoinWithOrder($join, $where, [], $select);

		foreach ($list as $row) {
			$id = bin2hex($this->encrypter->encrypt($row->ujiKode));
			$response = [];
			$response[] = '<span class="badge bg-secondary">' . esc($row->jenKode) . '</span>';
			$response[] = $row->ujiLayanan . '<br>'
				. '<strong> Alat : </strong>' . $row->alatNama . '<br>'
				. '<strong> Parameter : </strong> ' . $row->paraNama;
			$response[] = $row->ujiBiaya . ' / ' . $row->ujiSatuan;
			$response[] = $this->aksi($id);
			$data[] = $response;
		}
		$output = ["items" => $data];
		return $this->response->setJSON($output);
	}

	public function getoptions()
	{
		$jenisModel = new MyModel('simlab_r_jenis');
		$alatModel  = new MyModel('simlab_r_alat');
		$paraModel  = new MyModel('simlab_r_parameter');

		$data = [
			'jenis'     => $jenisModel->getAllData(),
			'alat'      => $alatModel->getAllData(),
			'parameter' => $paraModel->getAllData(),
		];

		return $this->response->setJSON($data);
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

	public function update_diskon()
	{
		$diskon = $this->request->getPost('diskon');
		$res = false;

		if ($diskon !== null) {
			$model = new MyModel('simlab_t_diskon');
			$res = $model->updateData(['diskon' => $diskon], 'kolom', 'ulm');
		}

		return $this->response->setJSON([
			'res' => $res,
			'xname' => csrf_token(),
			'xhash' => csrf_hash()
		]);
	}

}
