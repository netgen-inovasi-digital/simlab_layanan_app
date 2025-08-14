<?php

namespace Modules\Menu\Controllers;

use App\Controllers\BaseController;
use App\Models\MyModel;
<<<<<<< HEAD
use App\Modules\Menu\Models\MenuModel;
=======
>>>>>>> df8c327176c0d2352c9b643155da517b0816f615

class Menu extends BaseController
{
	private $table = 'menus';
	private $id = 'id_menu';

<<<<<<< HEAD
	public function index()
	{
		$model = new MyModel('menus');
		$data = [
			'title' => 'Data Menu',
			'getMenu' => $model->getAllData('sort_order', 'asc')
		];
		// dd($data['getMenu']);
		return view('Modules\Menu\Views\v_menu', $data);
	}

	function edit($id)
	{
		$idenc = $id;
		$id = $this->encrypter->decrypt(hex2bin($id));
		$model = new MyModel($this->table);
=======
    public function index()
    {
		$model = new MyModel('menus');
        $data = [
            'title' => 'Data Menu',
			'getMenu' => $model->getAllData('kode_menu', 'asc')
        ];
		return view('Modules\Menu\Views\v_menu', $data);
    }

    function edit($id)
	{
		$idenc = $id;
		$id = $this->encrypter->decrypt(hex2bin($id));
        $model = new MyModel($this->table);
>>>>>>> df8c327176c0d2352c9b643155da517b0816f615
		$get = $model->getDataById($this->id, $id);

		$data[csrf_token()] = csrf_hash();
		$data['id'] = $idenc;
		$data['nama'] = $get->nama;
		$data['url'] = $get->link;
		$data['icon'] = $get->icon;
		return $this->response->setJSON($data);
	}

<<<<<<< HEAD
	function delete($id)
	{
		$id = $this->encrypter->decrypt(hex2bin($id));
		$model = new MyModel($this->table);
		$res = $model->deleteData($this->id, $id);
		if ($res) {
			$res = 'refresh';
			$link = 'menu';
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
		$data = [
			'nama' => $this->request->getPost('nama'),
			'link' => $this->request->getPost('url'),
			'icon' => $this->request->getPost('icon'),
		];
		$model = new MyModel($this->table);
		if ($idenc == "") {
			// ambil kode & parent dari input hidden
			$code = $this->request->getPost('code');  // contoh: "2" atau "2.1"
			$parent = 0;
			$sort_order = 0;

			if (strpos($code, '.') !== false) {
				// kalau ada titik berarti child
				[$parentKode, $childNo] = explode('.', $code);
				$parent = $parentKode;
				$sort_order = $childNo;
			} else {
				// main menu
				$sort_order = (int)$code + 1;
			}

			$data['kode_menu'] = $sort_order;
			$data['kode_induk'] = $parent;
			$data['sort_order'] = $sort_order;

			$res = $model->insertData($data);
		} else {
=======
    function delete($id)
	{
		$id = $this->encrypter->decrypt(hex2bin($id));
        $model = new MyModel($this->table);
		$res = $model->deleteData($this->id, $id);
		if($res) {
			$res = 'refresh'; 
			$link = 'menu';
		}
		return $this->response->setJSON(array('res'=> $res, 'link'=>$link ?? '', 
			'xname'=>csrf_token(), 'xhash'=>csrf_hash()));
	}
    
    public function submit()
    {
        $idenc = $this->request->getPost('id');
        $data = array(
			'nama' => $this->request->getPost('nama'),
			'link' => $this->request->getPost('url'),
			'icon' => $this->request->getPost('icon'),
		);
		
		$model = new MyModel($this->table);
		if($idenc == "") {
			$code = $this->request->getPost('code');
			$data['kode_menu'] = (int)$code  + 1;
			$data['kode_induk'] = 0;
			$res = $model->insertData($data);
		}else{
>>>>>>> df8c327176c0d2352c9b643155da517b0816f615
			$id = $this->encrypter->decrypt(hex2bin($idenc));
			$res = $model->updateData($data, $this->id, $id);
		}

<<<<<<< HEAD
		if ($res) {
			$res = 'refresh';
			$link = 'menu';
		}

		return $this->response->setJSON([
			'res' => $res,
			'link' => $link ?? '',
			'xname' => csrf_token(),
			'xhash' => csrf_hash()
		]);
	}
=======
		if($res) {
			$res = 'refresh'; 
			$link = 'menu';
		}
		return $this->response->setJSON(array('res'=> $res, 'link'=>$link ?? '', 'xname'=>csrf_token(), 'xhash'=>csrf_hash()));
    }
>>>>>>> df8c327176c0d2352c9b643155da517b0816f615

	function updated()
	{
		$data = [];
<<<<<<< HEAD
		$items = $this->request->getPost('items');
=======
		$items = $this->request->getPost('items'); 
>>>>>>> df8c327176c0d2352c9b643155da517b0816f615
		foreach ($items as $item) {
			$data[] = [
				'id_menu'     => $this->encrypter->decrypt(hex2bin($item['id'])),
				'kode_menu'   => $item['code'],
<<<<<<< HEAD
				'kode_induk'  => $item['parent'],
				'sort_order'  => $item['sort_order'] // tambahkan ini
=======
				'kode_induk'  => $item['parent']
>>>>>>> df8c327176c0d2352c9b643155da517b0816f615
			];
		}

		$model = new MyModel('menus');
		$res = $model->updateDataBatch($data, 'id_menu');
<<<<<<< HEAD
		return $this->response->setJSON(array('res' => $res, 'xhash' => csrf_hash()));
	}
}
=======
		return $this->response->setJSON(array('res'=> $res, 'xhash'=>csrf_hash()));
	}
}
>>>>>>> df8c327176c0d2352c9b643155da517b0816f615
