<?php

namespace Modules\Pelayanan\Controllers;

use App\Controllers\BaseController;
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
        $id = bin2hex($this->encrypter->encrypt($row->lnKode));

        $response = [];

        $noTransaksi = $row->lnNoTransaksi ?? '-';
        $tanggal     = !empty($row->lnTgl) ? date('d-m-Y', strtotime($row->lnTgl)) : '-';
        $response[]  = '<div>' . esc($noTransaksi) . '<br><small>' . esc($tanggal) . '</small></div>';

        $response[] = $this->statusBadge($row->lnStatus);

        $kuisionerVal = 0;
        if (isset($row->kuisioner) && $row->kuisioner !== '') {
            $kuisionerVal = (int) $row->kuisioner;
        } else {
            $kuFields = ['kuisioner', 'user_kuisioner', 'lnKuisioner', 'ln_kuisioner', 'kuisioner_user'];
            foreach ($kuFields as $kf) {
                if (isset($user->{$kf}) && $user->{$kf} !== '') {
                    $kuisionerVal = (int) $user->{$kf};
                    break;
                }
            }
        }

        $bayarStatusVal = 0;
        try {
            if (!empty($row->lnKode)) {
                $db = \Config\Database::connect();
                $pay = $db->table('simlab_t_pembayaran')
                          ->select('bayarStatus')
                          ->where('bayarLnKode', $row->lnKode)
                          ->orderBy('bayarKode', 'DESC')
                          ->limit(1)
                          ->get()
                          ->getRow();

                if ($pay && isset($pay->bayarStatus)) {
                    $bayarStatusVal = (int) $pay->bayarStatus;
                }
            }
        } catch (\Throwable $e) {
            $bayarStatusVal = 0;
        }

        $lnStatusVal = isset($row->lnStatus) ? (int) $row->lnStatus : 0;

        $canViewLhu = ($kuisionerVal === 1 && $bayarStatusVal === 1 && in_array($lnStatusVal, [7, 8], true));

        $lhuInfo = $this->detectLhuFile($row);

        if ($lhuInfo['has'] && $canViewLhu) {
            $response[] = '<button class="btn btn-sm btn-outline-primary" onclick="window.open(\'' . esc($lhuInfo['url']) . '\', \'_blank\')" title="Buka LHU">'
                        . '<i class="bi bi-eye"></i> Lihat File</button>';
        } else {
            $reason = 'File LHU tidak dapat diakses.';
            if ($lhuInfo['has'] && !$canViewLhu) {
                if ($kuisionerVal !== 1) {
                    $reason = 'Isi kuisioner terlebih dahulu untuk melihat LHU.';
                } elseif ($bayarStatusVal !== 1) {
                    $reason = 'LHU hanya bisa dilihat setelah pembayaran dikonfirmasi.';
                } elseif (!in_array($lnStatusVal, [7,8], true)) {
                    $reason = 'LHU belum disetujui (status harus 7 atau 8).';
                }
            } elseif (!$lhuInfo['has']) {
                $reason = 'File LHU belum tersedia.';
            }

            $response[] = '<button class="btn btn-sm btn-secondary" disabled title="' . esc($reason) . '">'
                        . '<i class="bi bi-eye-slash"></i> Isi Kuisioner </button>';
        }
        $response[] = '<a href="javascript:void(0)" onclick="loadDetail(\'' . $id . '\')" class="btn btn-sm btn-info">Lihat detail Layanan</a>';

        $data[] = $response;
    }

    return $this->response->setJSON(["items" => $data]);
}



    private function statusBadge($status)
    {
        $labels = [
            0 => 'Draft',
            1 => 'Sedang diverifikasi petugas',
            2 => 'Ditolak',
            3 => 'Sedang diverifikasi petugas',
            4 => 'Pengujian Sedang Dilakukan',
            5 => 'Memproses LHUS',
            6 => 'LHUS Disetujui',
            7 => 'LHU Disetujui Petugas',
            // 8 => 'LHU Disetujui',
            // 9 => 'Pengujian Selesai',
        ];
        $class = [
            0 => 'secondary',
            1 => 'info',
            2 => 'danger',
            3 => 'info',
            4 => 'primary',
            5 => 'dark',
            6 => 'success',
            7 => 'warning',
            // 8 => 'success',
            // 9 => 'secondary',
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

    public function detailList($id = null)
    {
        if (!$id) {
            return $this->response->setJSON(['items' => []]);
        }

        try {
            $kode = $this->encrypter->decrypt(hex2bin($id));
        } catch (\Exception $e) {
            return $this->response->setJSON(['items' => []]);
        }

        // Encrypted hex parent
        $encLnId = bin2hex($this->encrypter->encrypt($kode));

        $db = \Config\Database::connect();
        $builder = $db->table('simlab_t_layanan_detil as d');

        $builder->select("
            d.detUjiKode,
            d.detLnKode,
            d.detLayanan,
            d.detJenKode,
            GROUP_CONCAT(DISTINCT d.detKeterangan SEPARATOR ' | ') AS detKet,
            SUM(d.detJumlah) AS jumlah,
            SUM(d.detBiaya) AS detBiaya,
            MAX(d.detStatus) AS detStatusGroup
        ");
        $builder->where('d.detLnKode', $kode);
        $builder->groupBy('d.detUjiKode, d.detLnKode, d.detLayanan, d.detJenKode');
        $rows = $builder->get()->getResult();

        $data = [];
        $no = 1;

        foreach ($rows as $row) {
            $response = [];
            $response[] = $no++;
            $response[] = $row->detLayanan ?? '-';
            $response[] = isset($row->detBiaya) ? number_format($row->detBiaya, 0, ',', '.') : '-';
            $response[] = isset($row->jumlah) ? (int)$row->jumlah : 0;
            $response[] = $row->detKet ?? '';

            // Ambil status grouping (0/1/atau lainnya)
            $statusGroup = isset($row->detStatusGroup) ? (int)$row->detStatusGroup : null;

            if ($statusGroup === 0) {
                $statusHtml = '<span class="badge bg-danger">Ditolak</span>';
            } elseif ($statusGroup === 1) {
                $statusHtml = '<span class="badge bg-success">Diterima</span>';
            } else {
                $statusHtml = '<span class="badge bg-secondary">Belum Diproses</span>';
            }
            $response[] = $statusHtml;

            $ujiKodeInt = (int)$row->detUjiKode;
            $encLnForBtn = $encLnId;


            $data[] = $response;
        }

        return $this->response->setJSON(['items' => $data]);
    }

    public function checkVerified()
    {
        $session = session();
        $user_id = $session->get('id_user');

        $modelUser = new MyModel('simlab_account_users');
        $user = $modelUser->getDataById('user_id', $user_id);

        if (!$user) {
            return $this->response->setJSON(['verified' => false, 'msg' => 'User tidak ditemukan.']);
        }

        if ((int)$user->verifikasi === 1) {
            return $this->response->setJSON(['verified' => true, 'msg' => 'Akun sudah terverifikasi.']);
        } else {
            return $this->response->setJSON(['verified' => false,
             'msg' => 'Akun belum diverifikasi. Silakan unggah bukti atau tunggu verifikasi.
             ']);
        }
    }

    private function detectLhuFile($row)
    {
        $possibleFields = [
            'lnLhu', 'lnLHU', 'lnFileLhu', 'ln_file_lhu', 'lhu_file', 'ln_lhu', 'ln_lhu_file', 'ln_file_lhu_path'
        ];

        foreach ($possibleFields as $f) {
            if (isset($row->{$f}) && !empty($row->{$f})) {
                $raw = $row->{$f};
                if (preg_match('/^https?:\/\//i', $raw)) {
                    return ['has' => true, 'url' => $raw];
                }
                $possiblePath = FCPATH . 'uploads/lhu/' . ltrim($raw, '/');
                if (is_file($possiblePath)) {
                    $possibleUrl = base_url('uploads/lhu/' . ltrim($raw, '/'));
                    return ['has' => true, 'url' => $possibleUrl];
                }
                return ['has' => false, 'url' => '#'];
            }
        }

        // cek di tabel detil (khusus LHU: hanya cek kolom detil_LHU dan varian terkait)
        if (isset($row->lnKode) && !empty($row->lnKode)) {
            try {
                $modelDet = new MyModel('simlab_t_layanan_detil');
                $detils = $modelDet->getAllDataById(['detLnKode' => $row->lnKode]);
                foreach ($detils as $d) {
                    $detFields = ['detil_LHU', 'detFile', 'detFilelhu', 'det_file_lhu'];
                    foreach ($detFields as $df) {
                        if (isset($d->{$df}) && !empty($d->{$df})) {
                            $raw = $d->{$df};
                            if (preg_match('/^https?:\/\//i', $raw)) {
                                return ['has' => true, 'url' => $raw];
                            }
                            $possiblePath = FCPATH . 'uploads/lhu/' . ltrim($raw, '/');
                            if (is_file($possiblePath)) {
                                $possibleUrl = base_url('uploads/lhu/' . ltrim($raw, '/'));
                                return ['has' => true, 'url' => $possibleUrl];
                            }
                        }
                    }
                }
            } catch (\Throwable $e) {
                // ignore
            }
        }

        return ['has' => false, 'url' => '#'];
    }

}
