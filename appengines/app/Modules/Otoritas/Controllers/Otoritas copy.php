<?php

namespace Modules\Otoritas\Controllers;

use App\Controllers\BaseController;
use App\Models\MyModel;

class Otoritas extends BaseController
{
	private $table = 'zOtoritas';
	private $id = 'idRole';

    public function index()
    {
		$model = new MyModel('zMenu');
        $get = $model->getAllData();
		$menu = array(
            'menus' => array(),
            'parent_menus' => array(),
        );
        foreach($get as $row) {
            $menu['menus'][$row->kodeMenu] = $row;
            $menu['parent_menus'][$row->kodeInduk][] = $row->kodeMenu;
        }

		$model = new MyModel('mPreferensi');
		$get = $model->getAllData();
		$pref = [];
		foreach($get as $row) {
			if($row->kodeMenu != "")
				$pref[] = $row->kodeMenu;
		}

		$model = new MyModel('zRole');
		$role = $model->getAllData();
        $data = [
            'title' => 'Otoritas Menu',
			'menu' => $menu,
			'role' => $role,
			'prefArray' => $pref
        ];
		return view('Modules\Otoritas\Views\v_otoritas', $data);
    }

    function edit()
	{
		$role = $this->request->getGet('s');
        $model = new MyModel('zOtoritas');
		$get = $model->getAllDataById(array('idRole'=> $role,'statusOtoritas'=>'2'));
        $data = array();
		foreach($get as $row) {
		    $data['menu'][] = $row->kodeMenu;
		}
		return $this->response->setJSON($data);
	}

    function set()
	{
		$role = $this->request->getGet('rl');
		$id = $this->request->getGet('id');
        $model = new MyModel('zMenu');
		$get = $model->getDataById('kodeMenu', $id);
		$model = new MyModel('zOtoritas');
		$get1 = $model->getDataByArray(array('idRole'=>$role, 'kodeMenu'=> $id));

		$data = array();
		if (!empty($get->label1)) 
			$data[] = ["id" => "value1", "label" => $get->label1, "value" => $get1->value1];
		if (!empty($get->label2))
			$data[] = ["id" => "value2", "label" => $get->label2, "value" => $get1->value2];
		if (!empty($get->label3))
			$data[] = ["id" => "value3", "label" => $get->label3, "value" => $get1->value3];
		
		return $this->response->setJSON($data);
	}

	function value()
	{
		$role = $this->request->getGet('rl');
		$kode = $this->request->getGet('id');
		$field = $this->request->getGet('f');
		$value = $this->request->getGet('val');

		$model = new MyModel('zOtoritas');
		$res = $model->updateDataByArray(array($field=>$value), 
				array('idRole'=>$role, 'kodeMenu'=>$kode)
			);
	}

    public function submit()
    {
        $role = $this->request->getPost('role');
		$model = new MyModel('zOtoritas');
		$model->updateData(array('statusOtoritas'=>'-2'), 'idRole', $role);

		$res = true;
		$menu = $this->request->getPost('menu');
		if($menu && $role != "") {
			foreach($menu as $key => $val) {
				$data = array(
					'kodeMenu' => $val,
					'idRole' => $role,
				);
				$get = $model->getDataByArray($data);

				$data['statusOtoritas'] = '2';
				if($get) $res = $model->updateData($data, 'idOtoritas', $get->idOtoritas);
				else $res = $model->insertData($data);
			}
		}
		return $this->response->setJSON(array('res'=> $res, 'xname'=>csrf_token(), 'xhash'=>csrf_hash()));
    }

    public function dataList()
    {
        $model = new MyModel($this->table);
		$data = array();
		$list = $model->getAllData();
        foreach ($list as $row) 
        {
			$id = bin2hex($this->encrypter->encrypt($row->kodeOtoritasBarang));
			$response = array();
			$response[] = $row->kodeOtoritasBarang;
			$response[] = $row->namaOtoritasBarang;
			$response[] = $this->aksi($id);
			$data[] = $response;
		}
		$output = array("items" => $data);
		return $this->response->setJSON($output);
    }

	function aksi($id)
	{
		return '<div id="'.$id.'" class="float-end">
			<span class="text-secondary btn-action" title="Ubah" onclick="editItem(event)">
				<i class="fa-solid fa-pen-to-square"></i></span> 
			<label class="divider">|</label>
			<span class="text-danger btn-action" title="Hapus" onclick="deleteItem(event)">
				<i class="fa-regular fa-trash-can"></i></span>
		</div>';
	}
}