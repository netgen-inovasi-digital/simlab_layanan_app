<?php

namespace Modules\Sosmed\Controllers;

use App\Controllers\BaseController;
use App\Models\MyModel;

class Sosmed extends BaseController
{
	private $table = 'sosmed';
	private $id = 'id_sosmed';

	public function index()
	{
		$model = new MyModel('sosmed');
		$data = [
			'title' => 'Data Sosmed',
			'getSosmed' => $model->getAllData('urutan', 'asc')
		];
		return view('Modules\Sosmed\Views\v_sosmed', $data);
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
		$data['link'] = $get->link;
		$data['icon'] = $get->icon;
		return $this->response->setJSON($data);
	}

	function delete($id)
	{
		$id = $this->encrypter->decrypt(hex2bin($id));
		$model = new MyModel($this->table);
		$res = $model->deleteData($this->id, $id);
		if ($res) {
			$res = 'refresh';
			$link = 'sosmed';
		}
		return $this->response->setJSON(array(
			'res' => $res,
			'link' => $link ?? '',
			'xname' => csrf_token(),
			'xhash' => csrf_hash()
		));
	}

	public function submit()
	{
		$idenc = $this->request->getPost('id');
		$icon = $this->request->getPost('icon');
		$nama = ''; // default

		// ambil nama dari label option berdasarkan value icon
		$sosmedMap = [
			'bi-facebook'    => 'Facebook',
			'bi-twitter-x'   => 'X (Twitter)',
			'bi-instagram'   => 'Instagram',
			'bi-linkedin'    => 'LinkedIn',
			'bi-youtube'     => 'YouTube',
			'bi-music-note'  => 'TikTok',
			'bi-whatsapp'    => 'WhatsApp',
			'bi-telegram'    => 'Telegram',
		];

		if (array_key_exists($icon, $sosmedMap)) {
			$nama = $sosmedMap[$icon];
		}

		$data = [
			'nama' => $nama,
			'icon' => $icon,
			'link' => $this->request->getPost('link'),
		];

		$model = new MyModel($this->table);
		if ($idenc == "") {
			$code = $this->request->getPost('code');
			$data['urutan'] = (int)$code + 1;
			$res = $model->insertData($data);
		} else {
			$id = $this->encrypter->decrypt(hex2bin($idenc));
			$res = $model->updateData($data, $this->id, $id);
		}

		if ($res) {
			$res = 'refresh';
			$link = 'sosmed';
		}
		return $this->response->setJSON(array('res' => $res, 'link' => $link ?? '', 'xname' => csrf_token(), 'xhash' => csrf_hash()));
	}

	function updated()
	{
		$data = [];
		$items = $this->request->getPost('items');
		foreach ($items as $item) {
			$data[] = [
				'id_sosmed'     => $this->encrypter->decrypt(hex2bin($item['id'])),
				'urutan'   => $item['code'],
			];
		}

		$model = new MyModel('sosmed');
		$res = $model->updateDataBatch($data, 'id_sosmed');
		return $this->response->setJSON(array('res' => $res, 'xhash' => csrf_hash()));
	}

	function toggle()
	{
		$idenc = $this->request->getPost('id');
		$id = $this->encrypter->decrypt(hex2bin($idenc));
		$status = $this->request->getPost('status');
		$data = [
			'status' => $status,
		];

		$model = new MyModel('sosmed');
		$res = $model->updateData($data, $this->id, $id);
		return $this->response->setJSON(array('res' => $res, 'xhash' => csrf_hash()));
	}
}
