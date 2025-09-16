<?php

namespace Modules\Pelayanan\Controllers;

use App\Controllers\BaseController;
use Modules\Pelayanan\Models\PelayananModel;
use App\Models\MyModel;

class Pelayanan extends BaseController
{
    private $table = 'simlab_t_layanan';
    private $id    = 'lnKode';
    protected $encrypter;

    public function __construct()
    {
        $this->encrypter = \Config\Services::encrypter();
    }

    public function index()
    {
        $session  = session();
        $user_id  = $session->get('id_user');

        $modelUser = new MyModel('simlab_account_users');

        $data = [
            'title' => 'Data Pelayanan',
            'user'  => $modelUser->getDataById('user_id', $user_id),
        ];

        return view('Modules\Pelayanan\Views\v_pelayanan', $data);
    }

    public function dataList()
    {
        $session   = session();
        $user_id   = $session->get('id_user');

        // ambil user dari tabel simlab_account_users
        $modelUser = new MyModel('simlab_account_users');
        $user      = $modelUser->getDataById('user_id', $user_id);

        if (!$user) {
            return $this->response->setJSON(["items" => []]);
        }

        $model = new MyModel($this->table);
        $data  = [];

        // filter data pelayanan berdasarkan email login
        $where = ['lnAccEmail' => $user->user_email];
        $list  = $model->getAllDataById($where, ['lnTgl' => 'DESC']);

        foreach ($list as $index => $row) {
            $id = bin2hex($this->encrypter->encrypt($row->lnKode));

            $response   = [];
            $response[] = $row->lnNoTransaksi ?? '-'; // No. Transaksi
            $response[] = !empty($row->lnTgl) ? date('d-m-Y', strtotime($row->lnTgl)) : '-'; // Tanggal
            $response[] = $row->lnTipe ?? '-'; // Pengujian Untuk
            $response[] = $this->statusBadge($row->lnStatus); // Status
            $response[] = '<a href="javascript:void(0)" 
                            onclick="loadDetail(\'' . $id . '\')" 
                            class="btn btn-sm btn-info">Lihat lebih detail</a>';

            $data[] = $response;
        }

        return $this->response->setJSON(["items" => $data]);
    }

    private function statusBadge($status)
    {
        $labels = [
            0 => 'Draft',
            1 => 'On Review',
            2 => 'Sudah Review',
            3 => 'Pelaksanaan',
            4 => 'Selesai',
            5 => 'Posting',
        ];
        $class = [
            0 => 'secondary',
            1 => 'warning',
            2 => 'primary',
            3 => 'info',
            4 => 'success',
            5 => 'dark',
        ];

        return isset($labels[$status])
            ? '<span class="badge bg-' . $class[$status] . '">' . $labels[$status] . '</span>'
            : '<span class="badge bg-secondary">Unknown</span>';
    }

    public function detail($id)
    {
        $id    = $this->encrypter->decrypt(hex2bin($id));
        $model = new MyModel($this->table);
        $get   = $model->getDataById($this->id, $id);

        return view('Modules\Pelayanan\Views\v_detail', ['data' => $get]);
    }

    // === tambahan ajax untuk detail layanan ===
    public function detailList($id)
    {
        $id = $this->encrypter->decrypt(hex2bin($id));

        $model = new MyModel('simlab_t_layanan_detil d');
        $joins = [
            'simlab_r_layanan_pengujian lp' => 'lp.ujiKode = d.detUjiKode',
            'simlab_r_parameter p'          => 'p.paraKode = lp.ujiParaKode',
            'simlab_r_alat a'               => 'a.alatKode = lp.ujiAlatKode',
        ];
        $where = ['d.detLnKode' => $id];

        $select = "
            d.detUjiKode,
            lp.ujiLayanan,
            p.paraNama,
            a.alatNama,
            d.detBiaya,
            d.detKeterangan
        ";

        $list = $model->getAllDataWithJoinWhereOrder(
            $joins,
            $where,
            ['d.detUjiKode' => 'ASC'],
            $select
        );

        $data = [];
        $no = 1;
        foreach ($list as $row) {
            $response   = [];
            $response[] = $no++; // No
            $response[] = $row->detUjiKode; 
            $response[] = $row->ujiLayanan . ' (' . $row->paraNama . ')'; // Layanan + Parameter
            $response[] = $row->alatNama;
            $response[] = 'Rp ' . number_format($row->detBiaya, 0, ',', '.');
            $response[] = $row->detKeterangan ?? '-';

            $data[] = $response;
        }

        return $this->response->setJSON(["items" => $data]);
    }
}
