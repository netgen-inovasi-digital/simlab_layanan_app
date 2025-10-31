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
    $accModel   = new MyModel('simlab_account');

    $data[csrf_token()] = csrf_hash();
    $data['id']           = $idenc;
    $data['ujiJenKode']   = $get->ujiJenKode;
    $data['ujiAlatKode']  = $get->ujiAlatKode;
    $data['ujiParaKode']  = $get->ujiParaKode;
    $data['ujiLayanan']   = $get->ujiLayanan;
    $data['ujiSatuan']    = $get->ujiSatuan;
    $data['ujiBiaya']     = $get->ujiBiaya;
    $data['ujiDiskon']    = $get->ujiDiskon;
    $data['ujiPenyelia']  = $get->ujiPenyelia;
    $data['ujiManajerTeknis'] = $get->ujiManajerTeknis;

    $data['options'] = [
        'jenis'     => $jenisModel->getAllData(),
        'alat'      => $alatModel->getAllData(),
        'parameter' => $paraModel->getAllData(),
        'penyelia'  => $accModel->getWhere(['role_id' => 6])->getResult(), // role_id=6 penyelia
        'manajer'   => $accModel->getWhere(['role_id' => 4])->getResult(), // role_id=4 manajer teknis
    ];

    return $this->response->setJSON($data);
}

public function submit()
{
    $idenc = $this->request->getPost('id');
    $ujiJenKode  = $this->request->getPost('ujiJenKode');
    $ujiAlatKode = $this->request->getPost('ujiAlatKode');
    $ujiParaKode = $this->request->getPost('ujiParaKode');

    $data = [
        'ujiJenKode'       => $ujiJenKode,
        'ujiAlatKode'      => $ujiAlatKode,
        'ujiParaKode'      => $ujiParaKode,
        'ujiLayanan'       => $this->request->getPost('ujiLayanan'),
        'ujiSatuan'        => $this->request->getPost('ujiSatuan'),
        'ujiBiaya'         => $this->request->getPost('ujiBiaya'),
        'ujiDiskon'        => $this->request->getPost('ujiDiskon'),
        'ujiPenyelia'      => $this->request->getPost('ujiPenyelia'),
        'ujiManajerTeknis' => $this->request->getPost('ujiManajerTeknis'),
    ];

    $model = new MyModel($this->table);

    if ($idenc == "") {
        //cek duplikat insert
        $cek = $model->getWhere([
            'ujiAlatKode' => $ujiAlatKode,
            'ujiParaKode' => $ujiParaKode
        ])->getRow();

        if ($cek) {
            return $this->response->setJSON([
                'res' => false,
                'msg' => 'Data kombinasi Alat & Parameter ini sudah ada',
                'xname' => csrf_token(),
                'xhash' => csrf_hash()
            ]);
        }

        $res = $model->insertData($data);
    } else {
        //cek duplikat update
        $id = $this->encrypter->decrypt(hex2bin($idenc));

        $cek = $model->getWhere([
            'ujiAlatKode' => $ujiAlatKode,
            'ujiParaKode' => $ujiParaKode,
            $this->id.' !=' => $id
        ])->getRow();

        if ($cek) {
            return $this->response->setJSON([
                'res' => false,
                'msg' => 'Data kombinasi Alat & Parameter ini sudah ada',
                'xname' => csrf_token(),
                'xhash' => csrf_hash()
            ]);
        }

        $res = $model->updateData($data, $this->id, $id);
    }

    return $this->response->setJSON([
        'res' => $res,
        'xname' => csrf_token(),
        'xhash' => csrf_hash()
    ]);
}




public function getoptions()
{
    $jenisModel = new MyModel('simlab_r_jenis');
    $alatModel  = new MyModel('simlab_r_alat');
    $paraModel  = new MyModel('simlab_r_parameter');
    $accModel   = new MyModel('simlab_account');

    $data = [
        'jenis'     => $jenisModel->getAllData(),
        'alat'      => $alatModel->getAllData(),
        'parameter' => $paraModel->getAllData(),
        'penyelia'  => $accModel->getWhere(['role_id' => 6])->getResult(),
        'manajer'   => $accModel->getWhere(['role_id' => 4])->getResult(),
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

	

		public function dataList()
{
    $model = new MyModel($this->table);
    $data = [];

    // daftar JOIN
    $join = [
        'simlab_r_jenis j'     => 'j.jenKode = ' . $this->table . '.ujiJenKode',
        'simlab_r_alat a'      => 'a.alatKode = ' . $this->table . '.ujiAlatKode',
        'simlab_r_parameter p' => 'p.paraKode = ' . $this->table . '.ujiParaKode',
        'simlab_account sp'    => 'sp.user_id = ' . $this->table . '.ujiPenyelia',
        'simlab_account sm'    => 'sm.user_id = ' . $this->table . '.ujiManajerTeknis',
    ];

    // kolom yang di-select
    $select = $this->table . '.*, 
        j.jenKode, j.jenNama, 
        a.alatNama, 
        p.paraNama, 
        sp.username AS nama_penyelia, 
        sm.username AS nama_manajer';

    // filter
    $where = [];
    $ujiJenKode = $this->request->getGet('ujiJenKode');
    if (!empty($ujiJenKode)) {
        $where[$this->table . '.ujiJenKode'] = $ujiJenKode;
    }

    // ambil data dengan LEFT JOIN
    $list = $model->getAllDataWithJoinWhereOrder($join, $where, [], $select, 'left');

    foreach ($list as $row) {
        $id = bin2hex($this->encrypter->encrypt($row->ujiKode));
        $response = [];

        // kolom kategori
        $response[] = '<span class="badge bg-info">' . esc($row->jenKode) . '</span>';

        // kolom nama layanan
        $response[] = $row->ujiLayanan . '<br>'
            . '<strong>Alat : </strong>' . $row->alatNama . '<br>'
            . '<strong>Parameter : </strong>' . $row->paraNama;

        // kolom penanggung jawab
        $response[] = 
             'P : ' . ($row->nama_penyelia ?? '-') . '<br>'
            . 'MT : ' . ($row->nama_manajer ?? '-');

        // kolom biaya
        $response[] = $row->ujiBiaya . ' / ' . $row->ujiSatuan;

        // kolom diskon
        $response[] = ($row->ujiDiskon ?? 0) . '%';

        // kolom aksi
        $response[] = $this->aksi($id);

        $data[] = $response;
    }

    $output = ["items" => $data];
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
