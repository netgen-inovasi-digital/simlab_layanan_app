<?php

namespace Modules\layananLab\Controllers;

use App\Controllers\BaseController;
use App\Models\MyModel;
use App\Models\AccountModel;
use App\Models\TimModel;
use Modules\layananLab\Models\LayananLabModel;

class layananLab extends BaseController
{
	private $table = 'r_layanan_pengujian';
	private $id = 'kode';
	protected $encrypter;

	public function __construct()
	{
		$this->encrypter = \Config\Services::encrypter();
	}

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

		return view('Modules\layananLab\Views\v_layananLab', $data);
	}

    // Ambil data layanan untuk edit
    function edit($id)
    {
        $idenc = $id;
        $id = $this->encrypter->decrypt(hex2bin($id));
        
        // Ambil data layanan
        $layananModel = new LayananLabModel();
        $layanan = $layananModel->getDataById('kode', $id);
        
        if (!$layanan) {
            return $this->response->setJSON([
                'res' => false,
                'msg' => 'Data tidak ditemukan'
            ]);
        }

        // Ambil tim members
        $timModel = new TimModel();
        $tim = $timModel->getTimWithUserDetails($id);

        // Ambil options untuk dropdown
        $jenisModel = new MyModel('simlab_r_jenis');
        $alatModel  = new MyModel('simlab_r_alat');
        $paraModel  = new MyModel('simlab_r_parameter');
        $accountModel = new AccountModel();

        $data = [
            csrf_token() => csrf_hash(),
            'id' => $idenc,
            'kode_jenis' => $layanan->kode_jenis,
            'kode_alat' => $layanan->kode_alat,
            'kode_parameter' => $layanan->kode_parameter,
            'nama_layanan' => $layanan->nama_layanan,
            'satuan' => $layanan->satuan,
            'biaya' => $layanan->biaya,
            'diskon' => $layanan->diskon,
            'tim' => $tim,
            'options' => [
                'jenis'     => $jenisModel->getAllData(),
                'alat'      => $alatModel->getAllData(),
                'parameter' => $paraModel->getAllData(),
                'penyelia'  => $accountModel->getPenyelia(),
                'manajer'   => $accountModel->getManajerTeknis(),
            ]
        ];

        return $this->response->setJSON($data);
    }

    // Simpan data layanan (insert/update)
    public function submit()
    {
        $idenc = $this->request->getPost('id');
        $timMembers = $this->request->getPost('tim');

        // Data layanan
        $data = [
            'kode_jenis'       => $this->request->getPost('kode_jenis'),
            'kode_alat'        => $this->request->getPost('kode_alat'),
            'kode_parameter'   => $this->request->getPost('kode_parameter'),
            'nama_layanan'     => $this->request->getPost('nama_layanan'),
            'satuan'           => $this->request->getPost('satuan'),
            'biaya'            => $this->request->getPost('biaya'),
            'diskon'           => $this->request->getPost('diskon'),
        ];

        // Validasi tim: minimal 1 penyelia dan 1 manajer teknis
        $accountModel = new AccountModel();
        $validation = $accountModel->validateTimMembers($timMembers);
        
        if (!$validation['valid']) {
            return $this->response->setJSON([
                'res' => false,
                'msg' => $validation['message'],
                'xname' => csrf_token(),
                'xhash' => csrf_hash()
            ]);
        }

        $layananModel = new LayananLabModel();
        $timModel = new TimModel();
        
        if (empty($idenc)) {
            // INSERT - Cek duplikat kombinasi alat & parameter
            if ($layananModel->isDuplicateCombination($data['kode_alat'], $data['kode_parameter'])) {
                return $this->response->setJSON([
                    'res' => false,
                    'msg' => 'Data kombinasi Alat & Parameter ini sudah ada',
                    'xname' => csrf_token(),
                    'xhash' => csrf_hash()
                ]);
            }

            // Insert layanan
            $insertedId = $layananModel->insertLayanan($data);
            
            if ($insertedId) {
                // Insert tim members
                $timResult = $timModel->insertTimMembers($insertedId, $timMembers);
                $res = true;
                $msg = 'Data berhasil disimpan';
            } else {
                $res = false;
                $msg = 'Gagal menyimpan data';
            }
        } else {
            // UPDATE
            $id = $this->encrypter->decrypt(hex2bin($idenc));
            
            // Cek duplikat kombinasi alat & parameter (exclude current id)
            if ($layananModel->isDuplicateCombination($data['kode_alat'], $data['kode_parameter'], $id)) {
                return $this->response->setJSON([
                    'res' => false,
                    'msg' => 'Data kombinasi Alat & Parameter ini sudah ada',
                    'xname' => csrf_token(),
                    'xhash' => csrf_hash()
                ]);
            }

            // Update layanan
            $res = $layananModel->updateLayanan($data, $id);
            
            if ($res) {
                // Replace tim members (delete old, insert new)
                $timResult = $timModel->replaceTimMembers($id, $timMembers);
                $msg = 'Data berhasil disimpan';
            } else {
                $msg = 'Gagal menyimpan data';
            }
        }

        return $this->response->setJSON([
            'res' => $res,
            'msg' => $msg,
            'xname' => csrf_token(),
            'xhash' => csrf_hash()
        ]);
    }




    // Ambil dropdown options
    public function getoptions()
    {
        $jenisModel = new MyModel('simlab_r_jenis');
        $alatModel  = new MyModel('simlab_r_alat');
        $paraModel  = new MyModel('simlab_r_parameter');
        $accountModel = new AccountModel();

        $options = [
            'jenis'     => $jenisModel->getAllData(),
            'alat'      => $alatModel->getAllData(),
            'parameter' => $paraModel->getAllData(),
            'penyelia'  => $accountModel->getPenyelia(),
            'manajer'   => $accountModel->getManajerTeknis(),
        ];

        return $this->response->setJSON($options);
    }

    // Ambil data tim dengan role
    public function getTim($id)
    {
        try {
            $id = $this->encrypter->decrypt(hex2bin($id));
            
            // Ambil tim dengan user details
            $timModel = new TimModel();
            $timList = $timModel->getTimWithUserDetails($id);
            
            // Ambil mapping role
            $roleModel = new MyModel('roles');
            $roles = $roleModel->getAllData();
            $roleMap = [];
            foreach ($roles as $role) {
                $roleMap[$role->id_role] = $role->nama_role ?? 'Unknown';
            }
            
            // Tambahkan nama role ke setiap member
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



	// Hapus layanan
	function delete($id)
	{
		$id = $this->encrypter->decrypt(hex2bin($id));
		
		// Hapus tim dulu
		$timModel = new TimModel();
		$timModel->deleteTimByLayanan($id);
		
		// Hapus layanan
		$layananModel = new LayananLabModel();
		$res = $layananModel->deleteLayanan($id);
		
		return $this->response->setJSON([
			'res' => $res, 
			'xname' => csrf_token(), 
			'xhash' => csrf_hash()
		]);
	}

	

	// Ambil list data untuk tabel
	public function dataList()
	{
		$data = [];
		
		// Filter
		$where = [];
		$kode_jenis = $this->request->getGet('kode_jenis');
		if (!empty($kode_jenis)) {
			$where['r_layanan_pengujian.kode_jenis'] = $kode_jenis;
		}

		// Ambil data layanan dengan join
		$layananModel = new LayananLabModel();
		$list = $layananModel->getLayananWithDetails($where);

		// Hitung tim untuk setiap layanan
		$timModel = new TimModel();
		$layananCodes = array_column($list, 'kode');
		$timCounts = $timModel->getTimCountsForLayanan($layananCodes);

		// Format data untuk response
		foreach ($list as $row) {
			$id = bin2hex($this->encrypter->encrypt($row->kode));
			$response = [];

			// kolom kategori
			$response[] = '<span class="badge bg-info">' . esc($row->jenKode) . '</span>';

			// kolom nama layanan
			$response[] = $row->nama_layanan . '<br>'
				. '<strong>Alat : </strong>' . $row->alatNama . '<br>'
				. '<strong>Parameter : </strong>' . $row->paraNama;

			// kolom penanggung jawab
			$timCount = $timCounts[$row->kode] ?? 0;
			$btnLihatTim = '<button class="btn btn-sm btn-outline-primary btn-lihat-tim" data-id="' . $id . '" title="Lihat Tim Penanggung Jawab">
				<i class="bi bi-eye"></i> Lihat (' . $timCount . ')
			</button>';
			$response[] = $btnLihatTim;

			// kolom biaya
			$biayaFormatted = number_format($row->biaya, 0, ',', '.');
			$response[] = $biayaFormatted . ' / ' . $row->satuan;

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

	// Update diskon ULM
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
