<?php

namespace Modules\FormulirManajer\Controllers;

use App\Controllers\BaseController;
use App\Models\MyModel;

class FormulirManajer extends BaseController
{
    private $table = 'simlab_t_layanan';
    private $id    = 'lnKode';

    public function index()
    {
        $session = session();
        $user_id = $session->get('id_user');
        
        echo '<pre>';
        var_dump($user_id);
        echo '</pre>';
        exit;

        $modelUser = new MyModel('simlab_account');

        $data = [
            'title' => 'Data Formulir Manajer',
            'user'  => $modelUser->getDataById('user_id', $user_id),
        ];

        return view('Modules\FormulirManajer\Views\v_formulirManajer', $data);
    }

    public function formulirManajerDelete($id)
    {
        $id    = $this->encrypter->decrypt(hex2bin($id));
        $model = new MyModel($this->table);
        $res   = $model->deleteData($this->id, $id);

        return $this->response->setJSON([
            'res'   => $res,
            'xname' => csrf_token(),
            'xhash' => csrf_hash()
        ]);
    }

    public function formulirManajerSubmit()
    {
        $idenc = $this->request->getPost('id');
        $data  = [
            'lnOrangNama'   => $this->request->getPost('lnOrangNama'),
            'lnInstansi'    => $this->request->getPost('lnInstansi'),
            'lnOrangTelp'   => $this->request->getPost('lnOrangTelp'),
            'lnOrangEmail'  => $this->request->getPost('lnOrangEmail'),
            'lnTipe'        => $this->request->getPost('lnTipe'),
            'lnTgl'         => $this->request->getPost('lnTgl') ?? date('Y-m-d H:i:s'),
        ];

        $model = new MyModel($this->table);

        if ($idenc == "") {
            $res = $model->insertData($data);
        } else {
            $id  = $this->encrypter->decrypt(hex2bin($idenc));
            $res = $model->updateData($data, $this->id, $id);
        }

        return $this->response->setJSON([
            'res'   => $res,
            'xname' => csrf_token(),
            'xhash' => csrf_hash()
        ]);
    }

    public function formulirManajerDataList()
    {
        $session = session();
        $user_id = $session->get('id_user');

        $model = new MyModel($this->table);
        $modelDet = new MyModel('simlab_t_layanan_detil');

        $data  = [];

        // Ambil semua detil yang penyelianya = user login
        $detilList = $modelDet->getAllDataById(['detPenyelia' => $user_id]);

        // Ambil lnKode yang sesuai dari tabel detil
        $lnKodeList = array_unique(array_column($detilList, 'detLnKode'));

        if (empty($lnKodeList)) {
            return $this->response->setJSON(['items' => []]);
        }

        // Ambil semua data layanan dengan lnKode tersebut
        $list = $model->getAllDataWithJoinWhereOrder(
            [], // join kosong
            ['lnKode' => $lnKodeList], 
            ['lnTgl' => 'DESC']        
        );


        // Kelompokkan berdasarkan status
        $grouped = [];
        for ($i = 0; $i <= 9; $i++) {
            $grouped[$i] = [];
        }

        foreach ($list as $row) {
            $status = (int) $row->lnStatus;
            if (!isset($grouped[$status])) {
                $grouped[$status] = [];
            }
            $grouped[$status][] = $row;
        }

        // Urutkan dari 0 → 9
        $finalList = [];
        for ($i = 0; $i <= 9; $i++) {
            $finalList = array_merge($finalList, $grouped[$i]);
        }

        foreach ($finalList as $row) {
            if ((int)$row->lnStatus === 0) {
                continue;
            }
            $id = bin2hex($this->encrypter->encrypt($row->lnKode));
            $response = [];

            $response[] = !empty($row->lnTgl) ? date('d-m-Y H:i', strtotime($row->lnTgl)) : '-';
            $response[] = $row->lnOrangNama ?? '-';
            $response[] = $row->lnTipe ?? '-';
            $response[] = $this->formulirManajerFormatStatus($row->lnStatus);

            $response[] = '<a href="javascript:void(0)" onclick="loadDetail(\'' . $id . '\')" 
                            class="btn btn-sm btn-info">
                            <i class="bi bi-eye"></i> Lihat Detail
                        </a>';

            $response[] = $this->formulirManajerAksi($id, $row->lnStatus);

            $data[] = $response;
        }

        $output = ["items" => $data];
        return $this->response->setJSON($output);
    }


    public function formulirManajerDetailList($id = null)
    {
        if (!$id) {
            return $this->response->setJSON(['items' => []]);
        }

        try {
            $kode = $this->encrypter->decrypt(hex2bin($id));
        } catch (\Exception $e) {
            return $this->response->setJSON(['items' => []]);
        }

        $modelDet = new MyModel('simlab_t_layanan_detil');
        $detil = $modelDet->getAllDataById(['detLnKode' => $kode]);

        $data = [];
        $no = 1;
        foreach ($detil as $row) {
            $response = [];
            $response[] = $no++;
            $response[] = $row->detJenKode ?? '-';
            $response[] = $row->detLayanan ?? '-';
            $response[] = isset($row->detBiaya) ? number_format($row->detBiaya, 0, ',', '.') : '-';
            $response[] = $row->detKet ?? '-';

            $data[] = $response;
        }

        return $this->response->setJSON(['items' => $data]);
    }


    private function formulirManajerAksi($id, $status)
    {
        $btn = '<div id="' . $id . '" class="float-end">';
        if ($status == 1) { // saat In Review
            $btn .= '<span class="text-success btn-action" title="Setujui" onclick="confirmApprove(event)">
                        <i class="bi bi-check-circle"></i></span> ';
            $btn .= '<span class="text-warning btn-action" title="Tolak" onclick="confirmReject(event)">
                        <i class="bi bi-x-circle"></i></span> ';
            $btn .= '<label class="divider">|</label> ';
        }
        $btn .= '<span class="text-danger btn-action" title="Hapus" onclick="deleteItem(event)">
                    <i class="bi bi-trash"></i></span>
                 </div>';
        return $btn;
    }

    private function formulirManajerFormatStatus($status)
    {
        switch ($status) {
            case 0: return '<span class="badge bg-secondary">Draft</span>';
            case 1: return '<span class="badge bg-warning">In Review (Manajer)</span>';
            case 2: return '<span class="badge bg-danger">Ditolak</span>';
            case 3: return '<span class="badge bg-info">In Review (Admin)</span>';
            case 4: return '<span class="badge bg-primary">Pengujian</span>';
            case 5: return '<span class="badge bg-primary">Memproses LHUS</span>';
            case 6: return '<span class="badge bg-success">LHUS Disetujui</span>';
            case 7: return '<span class="badge bg-primary">Memproses LHU</span>';
            case 8: return '<span class="badge bg-success">LHU Disetujui</span>';
            case 9: return '<span class="badge bg-dark">Pengujian Selesai</span>';
            default: return '<span class="badge bg-dark">Unknown</span>';
        }
    }

	public function formulirManajerApprove($id)
	{
		try {
			$id = $this->encrypter->decrypt(hex2bin($id));
		} catch (\Exception $e) {
			return $this->response->setJSON([
				'res'   => false,
				'msg'   => 'ID tidak valid',
				'xname' => csrf_token(),
				'xhash' => csrf_hash()
			]);
		}

		$model = new MyModel($this->table);
		$res = $model->updateData(['lnStatus' => 3], $this->id, $id);

		return $this->response->setJSON([
			'res'   => $res,
			'xname' => csrf_token(),
			'xhash' => csrf_hash()
		]);
	}

    public function formulirManajerReject($id)
    {
        try {
            $id = $this->encrypter->decrypt(hex2bin($id));
        } catch (\Exception $e) {
            return $this->response->setJSON([
                'res'   => false,
                'msg'   => 'ID tidak valid',
                'xname' => csrf_token(),
                'xhash' => csrf_hash()
            ]);
        }

        $model = new MyModel($this->table);
        $res = $model->updateData(['lnStatus' => 2], $this->id, $id);

        return $this->response->setJSON([
            'res'   => $res,
            'xname' => csrf_token(),
            'xhash' => csrf_hash()
        ]);
    }
}
