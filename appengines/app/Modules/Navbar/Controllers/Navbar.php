<?php

namespace Modules\Navbar\Controllers;

use App\Controllers\BaseController;
use App\Models\MyModel;

class Navbar extends BaseController
{
	private $table = 'navbar';
	private $id = 'id_navbar';

	public function index()
	{
		$modelNavbar = new MyModel('navbar');
		$modelPosts = new MyModel('posts');
		$modelPages = new MyModel('pages');
		$data = [
			'title' => 'Data Menu',
<<<<<<< HEAD
			'getNavbar' => $modelNavbar->getAllData('sort_order', 'asc'),
=======
			'getNavbar' => $modelNavbar->getAllData('kode_navbar', 'asc'),
>>>>>>> df8c327176c0d2352c9b643155da517b0816f615
			'getPosts' => $modelPosts->getAllData('published_at', 'asc'),
			'getPages' => $modelPages->getAllData('published_at', 'asc'),
		];
		return view('Modules\Navbar\Views\v_navbar', $data);
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
		$data['url'] = $get->url;
		return $this->response->setJSON($data);
	}

	function delete($id)
	{
		$id = $this->encrypter->decrypt(hex2bin($id));
		$model = new MyModel($this->table);
		$res = $model->deleteData($this->id, $id);
		if ($res) {
			$res = 'refresh';
			$link = 'navbar';
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
		$sumber = $this->request->getPost('sumber_menu'); // halaman | berita | url
		$slug   = $this->request->getPost("url_$sumber");

		$url = match ($sumber) {
			'halaman' => "hal/$slug",
			'berita'  => "berita/$slug",
			'manual'   => $slug,
		};

		$nama_menu = ($sumber === 'manual')
			? $this->request->getPost('nama_menu_url')
			: $this->request->getPost('nama');

		$data = [
			'nama' => $nama_menu,
			'url'  => $url,
		];


		$model = new MyModel($this->table);
		if ($idenc == "") {
			$code = $this->request->getPost('code');
			$data['kode_navbar'] = (int)$code  + 1;
			$data['kode_induk'] = 0;
<<<<<<< HEAD
			$data['sort_order'] = 0;
=======
>>>>>>> df8c327176c0d2352c9b643155da517b0816f615
			$res = $model->insertData($data);
		} else {
			$id = $this->encrypter->decrypt(hex2bin($idenc));
			$res = $model->updateData($data, $this->id, $id);
		}

		if ($res) {
			$res = 'refresh';
			$link = 'navbar';
		}
		return $this->response->setJSON(array('res' => $res, 'link' => $link ?? '', 'xname' => csrf_token(), 'xhash' => csrf_hash()));
	}

	function updated()
	{
		$data = [];
		$items = $this->request->getPost('items');
		foreach ($items as $item) {
			$data[] = [
				'id_navbar'     => $this->encrypter->decrypt(hex2bin($item['id'])),
				'kode_navbar'   => $item['code'],
<<<<<<< HEAD
				'kode_induk'  => $item['parent'],
				'sort_order'   => $item['sort_order'],
=======
				'kode_induk'  => $item['parent']
>>>>>>> df8c327176c0d2352c9b643155da517b0816f615
			];
		}

		$model = new MyModel('navbar');
		$res = $model->updateDataBatch($data, 'id_navbar');
		return $this->response->setJSON(array('res' => $res, 'xhash' => csrf_hash()));
	}

	function toggle() {
		$idenc = $this->request->getPost('id');
		$id = $this->encrypter->decrypt(hex2bin($idenc));
		$status = $this->request->getPost('status');
		$data = [
			'status' => $status,
		];

		$model = new MyModel('navbar');
		$res = $model->updateData($data, $this->id, $id);
		return $this->response->setJSON(array('res' => $res, 'xhash' => csrf_hash()));
	}


}
