<?php

namespace Modules\Lab\Controllers;

use App\Controllers\BaseController;
use App\Models\MyModel;

class Lab extends BaseController
{
	private $table = 'r_layanan_pengujian';
	private $id = 'kode';   

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
    $timModel   = new MyModel('r_tim');

    // Get tim members for this layanan
    $timMembers = $timModel->getWhere(['uji_kode' => $id])->getResult();
    $selectedTeam = array_map(function($t) { return $t->user_id; }, $timMembers);

    $data[csrf_token()] = csrf_hash();
    $data['id']           = $idenc;
    $data['kode_jenis']   = $get->kode_jenis;
    $data['kode_alat']    = $get->kode_alat;
    $data['kode_parameter'] = $get->kode_parameter;
    $data['nama_layanan'] = $get->nama_layanan;
    $data['satuan']       = $get->satuan;
    $data['biaya']        = $get->biaya;
    $data['diskon']       = $get->diskon;
    $data['tim']          = $selectedTeam;

    $data['options'] = [
        'jenis'     => $jenisModel->getAllData(),
        'alat'      => $alatModel->getAllData(),
        'parameter' => $paraModel->getAllData(),
        'users'     => $accModel->getWhere(['role_id' => 6])->getResult(), // role_id=6 penyelia
    ];

    return $this->response->setJSON($data);
}

public function submit()
{
    $idenc = $this->request->getPost('id');
    $kode_jenis  = $this->request->getPost('kode_jenis');
    $kode_alat = $this->request->getPost('kode_alat');
    $kode_parameter = $this->request->getPost('kode_parameter');
    $timMembers = $this->request->getPost('tim'); // array of user_id

    $data = [
        'kode_jenis'       => $kode_jenis,
        'kode_alat'        => $kode_alat,
        'kode_parameter'   => $kode_parameter,
        'nama_layanan'     => $this->request->getPost('nama_layanan'),
        'satuan'           => $this->request->getPost('satuan'),
        'biaya'            => $this->request->getPost('biaya'),
        'diskon'           => $this->request->getPost('diskon'),
    ];

    $model = new MyModel($this->table);
    $timModel = new MyModel('r_tim');
    $db = \Config\Database::connect();

    if ($idenc == "") {
        //cek duplikat insert
        $cek = $model->getWhere([
            'kode_alat' => $kode_alat,
            'kode_parameter' => $kode_parameter
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
        
        if ($res && !empty($timMembers)) {
            $insertedId = $db->insertID();
            foreach ($timMembers as $userId) {
                $timModel->insertData([
                    'uji_kode' => $insertedId,
                    'user_id' => $userId
                ]);
            }
        }
    } else {
        //cek duplikat update
        $id = $this->encrypter->decrypt(hex2bin($idenc));

        $cek = $model->getWhere([
            'kode_alat' => $kode_alat,
            'kode_parameter' => $kode_parameter,
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
        
        // Update tim members
        if ($res) {
            // Delete existing tim members
            $timModel->deleteData('uji_kode', $id);
            
            // Insert new tim members
            if (!empty($timMembers)) {
                foreach ($timMembers as $userId) {
                    $timModel->insertData([
                        'uji_kode' => $id,
                        'user_id' => $userId
                    ]);
                }
            }
        }
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
        'users'     => $accModel->getWhere(['role_id' => 6])->getResult(), // role_id=6 penyelia
    ];

    return $this->response->setJSON($data);
}

public function getTim($id)
{
    try {
        $id = $this->encrypter->decrypt(hex2bin($id));
        $timModel = new MyModel('r_tim');
        
        $join = [
            'simlab_account a' => 'a.user_id = r_tim.user_id'
        ];
        
        $select = 'r_tim.*, a.username, a.nama, a.role_id';
        $where = ['r_tim.uji_kode' => $id];
        
        $timList = $timModel->getAllDataWithJoinWhereOrder($join, $where, [], $select, 'left');
        
        // Get role names
        $roleModel = new MyModel('roles');
        $roles = $roleModel->getAllData();
        $roleMap = [];
        foreach ($roles as $role) {
            $roleMap[$role->id_role] = $role->nama_role ?? 'Unknown';
        }
        
        foreach ($timList as &$member) {
            $member->role_name = $roleMap[$member->role_id] ?? 'Unknown';
        }
        
        return $this->response->setJSON([
            'res' => true,
            'data' => $timList
        ]);
    } catch (\Exception $e) {
        return $this->response->setJSON([
            'res' => false,
            'msg' => 'Gagal memuat data tim: ' . $e->getMessage()
        ]);
    }
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
        'simlab_r_jenis j'     => 'j.jenKode = ' . $this->table . '.kode_jenis',
        'simlab_r_alat a'      => 'a.alatKode = ' . $this->table . '.kode_alat',
        'simlab_r_parameter p' => 'p.paraKode = ' . $this->table . '.kode_parameter',
    ];

    // kolom yang di-select
    $select = $this->table . '.*, 
        j.jenKode, j.jenNama, 
        a.alatNama, 
        p.paraNama';

    // filter
    $where = [];
    $kode_jenis = $this->request->getGet('kode_jenis');
    if (!empty($kode_jenis)) {
        $where[$this->table . '.kode_jenis'] = $kode_jenis;
    }

    // ambil data dengan LEFT JOIN
    $list = $model->getAllDataWithJoinWhereOrder($join, $where, [], $select, 'left');

    // Get tim counts for each layanan
    $timModel = new MyModel('r_tim');
    $timCounts = [];
    foreach ($list as $row) {
        $count = $timModel->getWhere(['uji_kode' => $row->kode])->getNumRows();
        $timCounts[$row->kode] = $count;
    }

    foreach ($list as $row) {
        $id = bin2hex($this->encrypter->encrypt($row->kode));
        $response = [];

        // kolom kategori
        $response[] = '<span class="badge bg-info">' . esc($row->jenKode) . '</span>';

        // kolom nama layanan
        $response[] = $row->nama_layanan . '<br>'
            . '<strong>Alat : </strong>' . $row->alatNama . '<br>'
            . '<strong>Parameter : </strong>' . $row->paraNama;

        // kolom penanggung jawab dengan button lihat
        $timCount = $timCounts[$row->kode] ?? 0;
        $btnLihatTim = '<button class="btn btn-sm btn-outline-primary btn-lihat-tim" data-id="' . $id . '" title="Lihat Tim Penanggung Jawab">
            <i class="bi bi-eye"></i> Lihat (' . $timCount . ')
        </button>';
        $response[] = $btnLihatTim;

        // kolom biaya
        $response[] = $row->biaya . ' / ' . $row->satuan;

        // kolom diskon
        $response[] = ($row->diskon ?? 0) . '%';

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
