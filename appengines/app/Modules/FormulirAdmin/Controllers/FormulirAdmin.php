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

        // kelompokkan berdasarkan status (0–9)
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

        // urutkan 0 → 9
        $finalList = [];
        for ($i = 0; $i <= 9; $i++) {
            $finalList = array_merge($finalList, $grouped[$i]);
        }

        foreach ($finalList as $row) {
            if ((int)$row->lnStatus === 0 || (int)$row->lnStatus === 1 || (int)$row->lnStatus === 2) {
                continue;
            }

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

            // kolom tabel
            $response[] = !empty($row->lnTgl) ? date('d-m-Y H:i', strtotime($row->lnTgl)) : '-';
            $response[] = $row->lnOrangNama ?? '-';
            $response[] = $row->lnTipe ?? '-';
            $response[] = $this->formatStatus($row->lnStatus);

            $lihatDetailBtn = '<button type="button" class="btn btn-sm btn-info" title="Lihat Detail Item Layanan" onclick="loadDetail(\'' . $id . '\')">'
                        . '<i class="bi bi-eye"></i> Lihat Detail Layanan</button>';
            $response[] = $lihatDetailBtn; 

            $response[] = $this->aksi($id, $row->lnStatus);

            $data[] = $response;
        }

        $output = ["items" => $data];
        return $this->response->setJSON($output);
    }

    public function detailList($id)
{
    // tolerant decrypt (id dikirim sebagai hex dari client)
    try {
        $lnKode = $this->encrypter->decrypt(hex2bin($id));
    } catch (\Throwable $e) {
        // coba decrypt langsung (jika tidak hex)
        try {
            $lnKode = $this->encrypter->decrypt($id);
        } catch (\Throwable $e2) {
            return $this->response->setJSON([
                'items' => [],
                'error' => 'Invalid ID'
            ]);
        }
    }

    // Ambil detil layanan sesuai detLnKode
    $model = new MyModel('simlab_t_layanan_detil d');
    $joins = [
        'simlab_r_layanan_pengujian lp' => 'lp.ujiKode = d.detUjiKode',
        'simlab_r_parameter p'          => 'p.paraKode = lp.ujiParaKode',
        'simlab_r_alat a'               => 'a.alatKode = lp.ujiAlatKode',
    ];
    $where = ['d.detLnKode' => $lnKode];

    $select = "
        d.detUjiKode,
        lp.ujiLayanan,
        p.paraNama,
        a.alatNama,
        d.detBiaya,
        d.detKeterangan
    ";

    try {
        $list = $model->getAllDataWithJoinWhereOrder($joins, $where, ['d.detUjiKode' => 'ASC'], $select);
    } catch (\Throwable $e) {
        // jika query error, kembalikan array kosong
        return $this->response->setJSON(['items' => []]);
    }

    $data = [];
    $no = 1;
    foreach ($list as $row) {
        $layanan = isset($row->ujiLayanan) ? $row->ujiLayanan : '-';
        if (isset($row->paraNama) && !empty($row->paraNama)) {
            $layanan .= ' (' . $row->paraNama . ')';
        }

        $biaya = isset($row->detBiaya) ? 'Rp ' . number_format($row->detBiaya, 0, ',', '.') : '-';
        $ket   = isset($row->detKeterangan) && !empty($row->detKeterangan) ? $row->detKeterangan : '-';

        $response = [];
        $response[] = $no++;
        $response[] = $layanan;
        $response[] = $biaya;
        $response[] = $ket;

        $data[] = $response;
    }

    return $this->response->setJSON(['items' => $data]);
}

    private function aksi($id, $status)
    {
        $btn = '<div id="' . $id . '" class="float-end">';
        if ($status == 3) { // Admin proses dari status "In Review (Admin)"
            $btn .= '<span class="text-success btn-action" title="Setujui" onclick="confirmApprove(event)">
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

    // untuk aksi approve Admin (dari 3 -> 4)
    public function approve($id)
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
        $res = $model->updateData(['lnStatus' => 4], $this->id, $id);

        return $this->response->setJSON([
            'res'   => $res,
            'xname' => csrf_token(),
            'xhash' => csrf_hash()
        ]);
    }
}
