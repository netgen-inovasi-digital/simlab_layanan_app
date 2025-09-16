<?php

namespace Modules\FormulirAdmin\Controllers;

use App\Controllers\BaseController;
use App\Models\MyModel;

class FormulirAdmin extends BaseController
{
    private $table = 'simlab_t_layanan';
    private $id    = 'lnKode';

    public function index()
    {
        $session = session();
        $user_id = $session->get('id_user');

        $modelUser = new MyModel('simlab_account_users');

        $data = [
            'title' => 'Data Formulir Admin',
            'user'  => $modelUser->getDataById('user_id', $user_id),
        ];

        return view('Modules\FormulirAdmin\Views\v_formulirAdmin', $data);
    }

    public function delete($id)
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

    public function submit()
    {
        $idenc = $this->request->getPost('id');
        $data  = [
            'lnOrangNama' => $this->request->getPost('lnOrangNama'),
            'lnInstansi'  => $this->request->getPost('lnInstansi'),
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

	public function dataList()
{
    $model = new MyModel($this->table);
    $data  = [];

    // ambil semua data, urutkan tanggal DESC dulu
    $list = $model->getAllDataWithOrder(['lnTgl' => 'DESC']);

    // lalu kelompokkan berdasarkan status supaya 1 -> 2 -> 3 -> 4
    $grouped = [1 => [], 2 => [], 3 => [], 4 => [], 0 => [], 99 => []]; 

    foreach ($list as $row) {
        $status = (int) $row->lnStatus;
        if (!isset($grouped[$status])) {
            $grouped[$status] = [];
        }
        $grouped[$status][] = $row;
    }

    $finalList = array_merge(
        $grouped[1],
        $grouped[2],
        $grouped[3],
        $grouped[4],
        $grouped[0],
        $grouped[99]
    );

    foreach ($finalList as $row) {
        $id = bin2hex($this->encrypter->encrypt($row->lnKode));
        $response = [];

        // ambil item layanan dari tabel detil
        $modelDet = new MyModel('simlab_t_layanan_detil');
        $detil = $modelDet->getAllDataById(['detLnKode' => $row->lnKode]);

        $items = [];
        foreach ($detil as $d) {
            $items[] = $d->detLayanan ?? $d->detJenKode;
        }
        $itemList = !empty($items) ? implode(', ', $items) : '-';

        // susun kolom
        $response[] = !empty($row->lnTgl) ? date('d-m-Y H:i', strtotime($row->lnTgl)) : '-';
        $response[] = $row->lnOrangNama ?? '-';
        $response[] = $row->lnTipe ?? '-';
        $response[] = $this->formatStatus($row->lnStatus);
		$response[] = '<a href="javascript:void(0)" onclick="loadDetail(\'' . $id . '\')" 
					class="btn btn-sm btn-info">
					<i class="bi bi-eye"></i> Lihat Detail
				</a>';
        $response[] = $this->aksi($id, $row->lnStatus);

        $data[] = $response;
    }

    $output = ["items" => $data];
    return $this->response->setJSON($output);
}



    private function aksi($id, $status)
    {
        $btn = '<div id="' . $id . '" class="float-end">';
        if ($status == 1) {
            $btn .= '<span class="text-success btn-action" title="Proses" onclick="confirmApprove(event)">
                        <i class="bi bi-check-circle"></i></span> ';
            $btn .= '<label class="divider">|</label> ';
        }
        $btn .= '<span class="text-danger btn-action" title="Hapus" onclick="deleteItem(event)">
                    <i class="bi bi-trash"></i></span>
                 </div>';
        return $btn;
    }

    private function formatStatus($status)
    {
        switch ($status) {
            case 0: return '<span class="badge bg-secondary">Draft</span>';
            case 1: return '<span class="badge bg-warning">On Review</span>';
            case 2: return '<span class="badge bg-primary">Sudah Review</span>';
            case 3: return '<span class="badge bg-info">Pelaksanaan</span>';
            case 4: return '<span class="badge bg-success">Selesai</span>';
            default: return '<span class="badge bg-dark">Unknown</span>';
        }
    }

    // untuk aksi ubah status dari 1 -> 2
    // untuk aksi ubah status dari 1 -> 2
public function approve($id)
{
    // jika tombol approve mengirimkan ID terenkripsi, coba decrypt
    try {
        $id = $this->encrypter->decrypt(hex2bin($id));
    } catch (\Exception $e) {
        // jika dekripsi gagal, kembalikan false
        return $this->response->setJSON([
            'res'   => false,
            'msg'   => 'ID tidak valid',
            'xname' => csrf_token(),
            'xhash' => csrf_hash()
        ]);
    }

    // update status ke 2
    $model = new MyModel($this->table);
    $res = $model->updateData(['lnStatus' => 2], $this->id, $id);

    return $this->response->setJSON([
        'res'   => $res,
        'xname' => csrf_token(),
        'xhash' => csrf_hash()
    ]);
}

}
