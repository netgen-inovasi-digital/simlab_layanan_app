<?php

namespace Modules\Menu\Controllers;

use App\Controllers\BaseController;
use App\Models\MyModel;

class Menu extends BaseController
{
	private $table = 'menus';
	private $id = 'id_menu';

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
		$get = $model->getDataById($this->id, $id);

		$data[csrf_token()] = csrf_hash();
		$data['id'] = $idenc;
		$data['nama'] = $get->nama;
		$data['url'] = $get->link;
		$data['icon'] = $get->icon;
		return $this->response->setJSON($data);
	}

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
			$id = $this->encrypter->decrypt(hex2bin($idenc));
			$res = $model->updateData($data, $this->id, $id);
		}

		if($res) {
			$res = 'refresh'; 
			$link = 'menu';
		}
		return $this->response->setJSON(array('res'=> $res, 'link'=>$link ?? '', 'xname'=>csrf_token(), 'xhash'=>csrf_hash()));
    }

	function updated()
	{
		$data = [];
		$items = $this->request->getPost('items'); 
		foreach ($items as $item) {
			$data[] = [
				'id_menu'     => $this->encrypter->decrypt(hex2bin($item['id'])),
				'kode_menu'   => $item['code'],
				'kode_induk'  => $item['parent']
			];
		}

		$model = new MyModel('menus');
		$res = $model->updateDataBatch($data, 'id_menu');
		return $this->response->setJSON(array('res'=> $res, 'xhash'=>csrf_hash()));
	}
}