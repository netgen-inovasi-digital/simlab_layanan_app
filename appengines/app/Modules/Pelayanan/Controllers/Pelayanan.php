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

    // Ambil user dari tabel simlab_account_users
    $modelUser = new MyModel('simlab_account_users');
    $user      = $modelUser->getDataById('user_id', $user_id);

    // Jika user tidak ditemukan, kembalikan data kosong
    if (!$user) {
        return $this->response->setJSON(["items" => []]);
    }

    $model = new MyModel($this->table);
    $data  = [];

    // Filter data pelayanan berdasarkan email login
    $where = ['lnAccEmail' => $user->user_email];
    $list  = $model->getAllDataById($where, ['lnTgl' => 'DESC']);

    foreach ($list as $index => $row) {
        // Encrypt ID untuk keamanan
        $id = bin2hex($this->encrypter->encrypt($row->lnKode));

        $response   = [];

        // Format No. Transaksi dan Tanggal ke dalam dua baris
        $noTransaksi = $row->lnNoTransaksi ?? '-';
        $tanggal     = !empty($row->lnTgl) ? date('d-m-Y', strtotime($row->lnTgl)) : '-';
        $response[]  = '<div>' . $noTransaksi . '<br><small>' . $tanggal . '</small></div>';

        // Kolom "Pengujian Untuk" dihapus, tapi tetap disimpan sebagai komentar
        // $response[] = $row->lnTipe ?? '-'; // Pengujian Untuk (Dihilangkan sesuai permintaan)

        // Status
        $response[] = $this->statusBadge($row->lnStatus);

        // Tombol lihat detail
        $response[] = '<a href="javascript:void(0)" 
                        onclick="loadDetail(\'' . $id . '\')" 
                        class="btn btn-sm btn-info">Lihat detail</a>';

        $data[] = $response;
    }

    // Kembalikan hasil dalam format JSON
    return $this->response->setJSON(["items" => $data]);
}

     

    private function statusBadge($status)
{
    $labels = [
        0 => 'Draft',
        1 => 'Sedang diverifikasi',
        2 => 'Ditolak',
        3 => 'Sedang diverifikasi',
        4 => 'Pengujian Sedang Dilakukan',
        5 => 'Memproses LHUS',
        6 => 'LHUS Disetujui',
        7 => 'Memproses LHU',
        8 => 'LHU Disetujui',
        9 => 'Pengujian Selesai',
    ];
    $class = [
        0 => 'secondary',
        1 => 'warning',
        2 => 'danger',
        3 => 'info',
        4 => 'primary',
        5 => 'dark',
        6 => 'success',
        7 => 'warning',
        8 => 'success',
        9 => 'secondary',
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
