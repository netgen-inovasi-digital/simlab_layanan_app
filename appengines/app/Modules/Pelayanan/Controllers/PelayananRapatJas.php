<?php

namespace Modules\Pelayanan\Controllers;

use App\Controllers\BaseController;
use App\Models\MyModel;

/**
 * Controller Pelayanan untuk Rapat JAS
 * Duplikat dari Pelayanan.php dengan filter kode_jenis = 'D'
 */
class PelayananRapatJas extends BaseController
{
    private $table = 'simlab_t_layanan';
    private $id = 'lnKode';
    protected $encrypter;
    private $sessionKey = 'keranjang_rapat_jas';

    // Filter khusus untuk Rapat JAS
    private $kodeJenisFilter = 'D';

    private function getStatusClass($status)
    {
        // Status class khusus untuk Rapat JAS
        return match ($status) {
            0 => 'secondary',  // Pendaftaran
            1 => 'warning',    // Review Manajer
            2 => 'danger',     // Ditolak
            3 => 'info',       // Review Admin
            4 => 'primary',    // Pelaksanaan
            9 => 'success',    // Selesai
            default => 'secondary'
        };
    }

    private function getStatusText($status)
    {
        // Status khusus untuk Rapat JAS
        return match ($status) {
            0 => 'Pendaftaran',
            1 => 'Review Petugas',
            2 => 'Ditolak',
            3 => 'Review Admin',
            4 => 'Pelaksanaan',
            9 => 'Selesai',
            default => 'Tidak Diketahui'
        };
    }

    public function __construct()
    {
        $this->encrypter = \Config\Services::encrypter();
    }

    public function getTrackingData($id)
    {
        try {
            $realId = $this->encrypter->decrypt(hex2bin($id));
            $model = new MyModel($this->table);
            $data = $model->getDataById($this->id, $realId);

            if (!$data) {
                return $this->response->setJSON([
                    'success' => false,
                    'message' => 'Data tidak ditemukan'
                ]);
            }

            // Get detail items dengan filter kode_jenis = 'D'
            $db = \Config\Database::connect();
            $builder = $db->table('t_layanan_detil as d');
            $builder->select("
                d.uji_kode,
                d.kode_layanan,
                d.nama_layanan as detParameter,
                d.kode_jenis,
                GROUP_CONCAT(DISTINCT d.catatan_pelanggan SEPARATOR ' | ') as catatan_pelanggan,
                SUM(d.jumlah) as jumlah,
                SUM(d.biaya) as biaya,
                MAX(d.status_layanan) as status_layanan
            ");
            $builder->where('d.kode_layanan', $realId);
            $builder->where('d.kode_jenis', $this->kodeJenisFilter); // Filter Rapat JAS
            $builder->groupBy('d.uji_kode, d.kode_layanan, d.nama_layanan, d.kode_jenis');
            $details = $builder->get()->getResult();

            return $this->response->setJSON([
                'success' => true,
                'data' => [
                    'kode' => $data->lnKode,
                    'statusText' => $this->getStatusText((int) $data->lnStatus),
                    'tanggal' => date('d-m-Y', strtotime($data->lnTgl)),
                    'noTransaksi' => $data->lnNoTransaksi ?? 'Belum tersedia',
                    'details' => array_map(function ($detail) {
                        $statusGroup = isset($detail->detStatus) ? (int) $detail->detStatus : null;

                        if ($statusGroup === 0) {
                            $statusHtml = '<span class="badge bg-warning">Pending</span>';
                        } elseif ($statusGroup === 1) {
                            $statusHtml = '<span class="badge bg-success">Diterima</span>';
                        } elseif ($statusGroup === 2) {
                            $statusHtml = '<span class="badge bg-danger">Ditolak</span>';
                        } else {
                            $statusHtml = '<span class="badge bg-secondary">Belum Diproses</span>';
                        }
                        return [
                            'parameter' => $detail->detParameter ?? '-',
                            'biaya' => number_format((float) ($detail->detBiaya ?? 0), 0, ',', '.'),
                            'jumlah' => (int) ($detail->detJumlah ?? 0),
                            'keterangan' => $detail->detKeterangan ?? '-',
                            'status' => $statusHtml
                        ];
                    }, $details)
                ]
            ]);
        } catch (\Exception $e) {
            return $this->response->setJSON([
                'success' => false,
                'message' => 'Terjadi kesalahan saat memuat data'
            ]);
        }
    }

    public function index()
    {
        $session = session();
        $user_id = $session->get('id_user');

        $modelUser = new MyModel('simlab_account_users');

        // Ambil kategori dengan filter kode_jenis = 'D' (Rapat JAS)
        $db = \Config\Database::connect();
        $builder = $db->table('r_layanan_pengujian as lp');
        $builder->select('
            DISTINCT TRIM(LEFT(lp.kode_jenis, 2)) as jenKode,
            j.jenNama
        ');
        $builder->join('simlab_r_jenis j', 'j.jenKode = TRIM(LEFT(lp.kode_jenis, 2))', 'left');
        $builder->where('lp.kode_jenis', $this->kodeJenisFilter); // Filter Rapat JAS
        $builder->orderBy('j.jenNama', 'ASC');
        $categories = $builder->get()->getResult();

        // Normalisasi categories
        $normalized = [];
        if (!empty($categories)) {
            foreach ($categories as $c) {
                $kode = isset($c->jenKode) ? trim((string) $c->jenKode) : '';
                $nama = (isset($c->jenNama) && trim((string) $c->jenNama) !== '') ? trim((string) $c->jenNama) : $kode;
                if ($kode !== '') {
                    $normalized[] = (object) [
                        'jenKode' => $kode,
                        'jenNama' => $nama
                    ];
                }
            }
        }

        $data = [
            'title' => 'Data Pelayanan Rapat JAS',
            'user' => $modelUser->getDataById('user_id', $user_id),
            'categories' => array_values($normalized),
        ];

        return view('Modules\Pelayanan\Views\v_pelayanan_rapat_jas', $data);
    }

    public function dataList()
    {
        $session = session();
        $user_id = (int) $session->get('id_user');

        $modelUser = new MyModel('simlab_account_users');
        $user = $modelUser->getDataById('user_id', $user_id);

        if (!$user || !$user_id) {
            return $this->response->setJSON(["items" => []]);
        }

        $model = new MyModel($this->table);
        $data = [];

        // 🔥 Filter: Ambil layanan yang memiliki detail dengan kode_jenis = 'D'
        $db = \Config\Database::connect();

        // Subquery untuk mendapatkan lnKode yang memiliki detail dengan kode_jenis = 'D'
        $lnKodesWithRapatJas = $db->table('t_layanan_detil')
            ->select('kode_layanan')
            ->distinct()
            ->where('kode_jenis', $this->kodeJenisFilter)
            ->get()
            ->getResultArray();

        $validLnKodes = array_column($lnKodesWithRapatJas, 'kode_layanan');

        // Jika tidak ada layanan dengan kode_jenis = 'D', return empty
        if (empty($validLnKodes)) {
            return $this->response->setJSON(["items" => []]);
        }

        // Query layanan dengan filter user_id dan lnKode yang valid
        $builder = $db->table($this->table);
        $builder->where('user_id', $user_id);
        $builder->whereIn('lnKode', $validLnKodes);
        $builder->orderBy('lnTgl', 'DESC');
        $list = $builder->get()->getResult();

        if (empty($list)) {
            return $this->response->setJSON(["items" => []]);
        }

        $lnKodes = array_map(fn($r) => (int) $r->lnKode, $list);

        $payRows = $db->table('t_pembayaran')
            ->select('bayarLnKode, bayarStatus, bayarInvoiceNo, MAX(bayarKode) AS lastKode')
            ->whereIn('bayarLnKode', $lnKodes)
            ->groupBy('bayarLnKode, bayarStatus, bayarInvoiceNo')
            ->orderBy('lastKode', 'DESC')
            ->get()->getResult();

        $payMap = [];
        foreach ($payRows as $p) {
            $ln = (int) $p->bayarLnKode;
            if (!isset($payMap[$ln])) {
                $payMap[$ln] = [
                    'status' => (int) $p->bayarStatus,
                    'inv' => $p->bayarInvoiceNo ?? null,
                ];
            }
        }

        foreach ($list as $row) {
            $id = bin2hex($this->encrypter->encrypt($row->lnKode));
            $response = [];

            $noTransaksi = (isset($row->lnNoTransaksi) && trim((string) $row->lnNoTransaksi) !== '')
                ? $row->lnNoTransaksi
                : 'Belum tersedia';

            $tanggal = !empty($row->lnTgl) ? date('d-m-Y', strtotime($row->lnTgl)) : '-';
            $response[] = '<div>' . esc($noTransaksi) . '<br><small>' . esc($tanggal) . '</small></div>';

            $statusText = $this->getStatusText((int) ($row->lnStatus ?? 0));
            $response[] = '<div class="d-flex gap-2 align-items-center">' .
                '<button class="btn btn-sm btn-outline-primary" onclick="showTrackingModal(\'' . $id . '\', \'' . $row->lnKode . '\', ' . (int) ($row->lnStatus ?? 0) . ')">' .
                '<i class="bi bi-activity"></i> ' . $statusText . '</button>' .
                '</div>';

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

            $lnKodeInt = (int) $row->lnKode;
            $bayarStatusVal = isset($payMap[$lnKodeInt]) ? $payMap[$lnKodeInt]['status'] : 0;
            $bayarInvoiceNo = isset($payMap[$lnKodeInt]) ? $payMap[$lnKodeInt]['inv'] : null;

            if ($bayarStatusVal === 1) {
                $response[] = '<button class="btn btn-sm btn-success"><i class="bi bi-credit-card"></i> Sudah Bayar</button>';
            } else {
                $response[] = '<button class="btn btn-sm btn-info" onclick="lokasiPembayaran(' . $lnKodeInt . ')"><i class="bi bi-credit-card"></i> Belum Bayar</button>';
            }

            $data[] = $response;
        }

        return $this->response->setJSON(["items" => $data]);
    }

    public function detail($id)
    {
        $id = $this->encrypter->decrypt(hex2bin($id));
        $model = new MyModel($this->table);
        $get = $model->getDataById($this->id, $id);

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

        $encLnId = bin2hex($this->encrypter->encrypt($kode));

        $db = \Config\Database::connect();
        $builder = $db->table('t_layanan_detil as d');

        $builder->select("
            d.uji_kode,
            d.kode_layanan,
            d.nama_layanan,
            d.kode_jenis,
            GROUP_CONCAT(DISTINCT d.catatan_pelanggan SEPARATOR ' | ') AS detKet,
            SUM(d.jumlah) AS jumlah,
            SUM(d.biaya) AS detBiaya,
            MAX(d.status_layanan) AS detStatusGroup
        ");
        $builder->where('d.kode_layanan', $kode);
        $builder->where('d.kode_jenis', $this->kodeJenisFilter); // Filter Rapat JAS
        $builder->groupBy('d.uji_kode, d.kode_layanan, d.nama_layanan, d.kode_jenis');
        $rows = $builder->get()->getResult();

        $data = [];
        $no = 1;

        foreach ($rows as $row) {
            $response = [];
            $response[] = $no++;
            $response[] = $row->nama_layanan ?? '-';
            $response[] = isset($row->detBiaya) ? number_format($row->detBiaya, 0, ',', '.') : '-';
            $response[] = isset($row->jumlah) ? (int) $row->jumlah : 0;
            $response[] = '<div 
                    style="display:block; max-width:240px; min-width:160px; width:100%;
                        max-height:120px; min-height:48px; overflow-y:auto; overflow-x:hidden;
                        padding:4px 6px; border:1px solid #ddd; border-radius:4px; background:#f9f9f9;
                        white-space:pre-wrap; word-break:break-word; font-size:0.9rem;">'
                . htmlspecialchars($row->detKet ?? '', ENT_QUOTES, 'UTF-8') .
                '</div>';

            $statusGroup = isset($row->detStatusGroup) ? (int) $row->detStatusGroup : null;

            if ($statusGroup === 0) {
                $statusHtml = '<span class="badge bg-warning ">Pending</span>';
            } elseif ($statusGroup === 1) {
                $statusHtml = '<span class="badge bg-success">Diterima</span>';
            } elseif ($statusGroup === 2) {
                $statusHtml = '<span class="badge bg-danger">Ditolak</span>';
            } else {
                $statusHtml = '<span class="badge bg-secondary">Belum Diproses</span>';
            }

            $response[] = $statusHtml;

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

        if ((int) $user->verifikasi === 1) {
            return $this->response->setJSON(['verified' => true, 'msg' => 'Akun sudah terverifikasi.']);
        } else {
            return $this->response->setJSON([
                'verified' => false,
                'msg' => 'Akun belum diverifikasi. Silakan unggah bukti atau tunggu verifikasi.'
            ]);
        }
    }



    public function kuesioner($id)
    {
        $idenc = $id;
        try {
            $lnKode = $this->encrypter->decrypt(hex2bin($idenc));
        } catch (\Exception $e) {
            throw \CodeIgniter\Exceptions\PageNotFoundException::forPageNotFound();
        }

        $modelPertanyaan = new MyModel('simlab_t_kuesioner');
        $pertanyaan = $modelPertanyaan->getAllDataWithJoinWhereOrder([], [], ['kuesioner_id' => 'ASC']);

        $data = [
            'title' => 'Kuesioner Kepuasan Pelanggan',
            'pertanyaan' => $pertanyaan,
            'idenc' => $idenc,
        ];
        return view('Modules\Pelayanan\Views\v_kuesioner_form_user', $data);
    }

    public function submit_kuesioner()
    {
        $session = session();
        $user_id = $session->get('id_user');
        $idenc = $this->request->getPost('idenc');

        try {
            $lnKode = $this->encrypter->decrypt(hex2bin($idenc));
        } catch (\Exception $e) {
            return $this->response->setJSON(['res' => false, 'msg' => 'ID Layanan tidak valid.', 'xname' => csrf_token(), 'xhash' => csrf_hash()]);
        }

        $jawaban_array = $this->request->getPost('jawaban');
        if (empty($jawaban_array)) {
            return $this->response->setJSON(['res' => false, 'msg' => 'Tidak ada jawaban yang dikirim.', 'xname' => csrf_token(), 'xhash' => csrf_hash()]);
        }

        $modelJawaban = new MyModel('simlab_t_kuesioner_jawaban');
        $modelLayanan = new MyModel($this->table);
        $db = \Config\Database::connect();

        $db->transStart();

        foreach ($jawaban_array as $id_pertanyaan => $jawaban) {
            $jawabanText = is_array($jawaban) ? json_encode($jawaban) : (string) $jawaban;

            $existingAnswer = $modelJawaban
                ->getWhere([
                    'id_pertanyaan' => $id_pertanyaan,
                    'user_id' => $user_id,
                    'kode_layanan' => $lnKode,
                ])->getRow();

            $dataJawaban = [
                'id_pertanyaan' => $id_pertanyaan,
                'user_id' => $user_id,
                'kode_layanan' => $lnKode,
                'jawaban' => $jawabanText,
                'created_at' => date('Y-m-d H:i:s')
            ];

            if ($existingAnswer) {
                $modelJawaban->updateData($dataJawaban, 'id_jawaban', $existingAnswer->id_jawaban);
            } else {
                $modelJawaban->insertData($dataJawaban);
            }
        }

        $modelLayanan->updateData(['kuisioner' => 1, 'lnStatus' => 8], $this->id, $lnKode);

        $db->transComplete();

        if ($db->transStatus() === false) {
            return $this->response->setJSON(['res' => false, 'msg' => 'Terjadi kegagalan saat menyimpan data.', 'xname' => csrf_token(), 'xhash' => csrf_hash()]);
        }

        return $this->response->setJSON([
            'res' => 'refresh',
            'link' => site_url('pelayananrapatjas'),
            'xname' => csrf_token(),
            'xhash' => csrf_hash()
        ]);
    }
}
