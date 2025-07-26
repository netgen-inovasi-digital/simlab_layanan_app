<?php

namespace Modules\Konfigurasi\Controllers;

use App\Controllers\BaseController;
use App\Models\MyModel;

class Konfigurasi extends BaseController
{
	private $table = 'konfigurasi';
	private $id = 'id_konfigurasi';

	public function index()
	{
		// ===== model konfigurasi ===== //
		$model = new MyModel('konfigurasi');
		$get = $model->getDataById('id_konfigurasi', 1);

		// ===== model halaman ===== //
		$modelHalaman = new MyModel('pages');
		$data = [
			'title' => 'Data Informasi',
			'get' => $get,
			'getHalaman' => $modelHalaman->getAllDataById(['status' => 'publish']),
		];
		return view('Modules\Konfigurasi\Views\v_konfigurasi', $data);
	}

	public function submit()
	{
		$data = array(
			'nama_profil' => $this->request->getPost('nama'),
			'alamat' => $this->request->getPost('alamat'),
			'telepon' => $this->request->getPost('telepon'),
			'email' => $this->request->getPost('email'),
			'kota' => $this->request->getPost('kota'),
			'provinsi' => $this->request->getPost('provinsi'),
			'deskripsi' => $this->request->getPost('deskripsi'),
			'peta' => $this->request->getPost('peta'),
			'link' => $this->request->getPost('link'),
		);

		$logo = $this->request->getFile('logo');
		if ($logo != "") {
			$filename = $this->doUpload($logo);
			if ($filename != "") $data['logo'] = $filename;
		}

		$model = new MyModel($this->table);
		$check = $model->getDataById('id_konfigurasi', 1);
		if (! $check) $res = $model->insertData($data);
		else $res = $model->updateData($data, $this->id, 1);

		if ($res) {
			$res = 'refresh';
			$link = 'konfigurasi';
		}
		return $this->response->setJSON(array('res' => $res, 'link' => $link ?? '', 'xname' => csrf_token(), 'xhash' => csrf_hash()));
	}

	function doUpload($file)
	{
		$filename = "";
		if ($file) {
			if (
				$file->isValid() && ! $file->hasMoved() &&
				in_array($file->getMimeType(), ['image/jpeg', 'image/png', 'application/pdf'])
			) {
				$ext = $file->getClientExtension();
				$filename = time() . bin2hex(random_bytes(5)) . '.' . $ext;
				$path = FCPATH . 'uploads';
				$file->move($path, $filename, true);
			}
		}
		return $filename;
	}
}
