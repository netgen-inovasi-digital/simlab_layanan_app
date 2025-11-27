<?php

namespace Modules\Pelayanan\Controllers;

use App\Controllers\BaseController;
use App\Models\MyModel;

class Pelayanan extends BaseController
{
    private $table = 'simlab_t_layanan';
    private $id = 'lnKode';
    protected $encrypter;
    private $sessionKey = 'keranjang';

    private function getStatusClass($status)
    {
        return match ($status) {
            0 => 'secondary',  // Pendaftaran
            1 => 'info',       // Review Manajer
            2 => 'danger',     // Ditolak
            3 => 'info',       // Review Admin
            4 => 'primary',    // Pengujian
            5 => 'primary',    // Proses LHUS
            6 => 'success',    // LHUS Disetujui
            7 => 'primary',    // Proses LHU
            8 => 'success',    // LHU Disetujui
            9 => 'success',    // Selesai
            default => 'secondary'
        };
    }

    private function getStatusText($status)
    {
        return match ($status) {
            0 => 'Pendaftaran',
            1 => 'Review Petugas',
            2 => 'Ditolak',
            3 => 'In Review Petugas',
            4 => 'Pengujian Dilakukan',
            5 => 'Verifikasi Hasil Uji',
            6 => 'Penerbitan LHUS',
            7 => 'Verifikasi LHU',
            8 => 'Penerbitan LHU',
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

            // Get detail items with grouped data
            $db = \Config\Database::connect();
            $builder = $db->table('t_layanan_detil as d');
            $builder->select("
                d.uji_kode,
                d.kode_layanan,
                d.nama_layanan as detParameter,
                d.kode_jenis,
                SUM(d.jumlah) as jumlah,
                SUM(d.biaya) as biaya,
                MAX(d.status_layanan) as status_layanan
            ");
            $builder->where('d.kode_layanan', $realId);
            $builder->groupBy('d.uji_kode, d.kode_layanan, d.nama_layanan, d.kode_jenis');
            $details = $builder->get()->getResult();

            // Get log sampel (timestamps) if available
            $logRow = $db->table('t_log_sampel')->where('kode_layanan', $realId)->get()->getRow();
            $log = null;
            if ($logRow) {
                $log = [
                    'pengecekan' => $logRow->pengecekan ? date('d-m-Y H:i', strtotime($logRow->pengecekan)) : null,
                    'pengujian' => $logRow->pengujian ? date('d-m-Y H:i', strtotime($logRow->pengujian)) : null,
                    'verifikasi_hasil_uji' => $logRow->verifikasi_hasil_uji ? date('d-m-Y H:i', strtotime($logRow->verifikasi_hasil_uji)) : null,
                    'penerbitan_lhus' => $logRow->penerbitan_lhus ? date('d-m-Y H:i', strtotime($logRow->penerbitan_lhus)) : null,
                    'verifikasi_lhu' => $logRow->verifikasi_lhu ? date('d-m-Y H:i', strtotime($logRow->verifikasi_lhu)) : null,
                    'penerbitan_lhu' => $logRow->penerbitan_lhu ? date('d-m-Y H:i', strtotime($logRow->penerbitan_lhu)) : null,
                ];
            }

            return $this->response->setJSON([
                'success' => true,
                'data' => [
                    'kode' => $data->lnKode,
                    'statusText' => $this->getStatusText((int) $data->lnStatus),
                    'tanggal' => date('d-m-Y', strtotime($data->lnTgl)),
                    'noTransaksi' => $data->lnNoTransaksi ?? 'Belum tersedia',
                    'details' => array_map(function ($detail) {
                        $statusGroup = isset($detail->status_layanan) ? (int) $detail->status_layanan : null;

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
                            'biaya' => number_format((float) ($detail->biaya ?? 0), 0, ',', '.'),
                            'jumlah' => (int) ($detail->jumlah ?? 0),
                            'status' => $statusHtml
                        ];
                    }, $details),
                    'log' => $log
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

        // Ambil daftar kategori untuk modal keranjang
        $db = \Config\Database::connect();
        $builder = $db->table('r_layanan_pengujian as lp');
        $builder->select('
            DISTINCT TRIM(LEFT(lp.kode_jenis, 2)) as jenKode,
            j.jenNama
        ');
        $builder->join('simlab_r_jenis j', 'j.jenKode = TRIM(LEFT(lp.kode_jenis, 2))', 'left');
        $builder->where('lp.kode_jenis IS NOT NULL');
        $builder->where('lp.kode_jenis !=', '');
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
            'title' => 'Data Pelayanan',
            'user' => $modelUser->getDataById('user_id', $user_id),
            'categories' => array_values($normalized),
        ];

        return view('Modules\Pelayanan\Views\v_pelayanan', $data);
    }

    public function dataList()
    {
        $session = session();
        $user_id = (int) $session->get('id_user');

        // Ambil user login (opsional buat cek kuisioner fallback)
        $modelUser = new MyModel('simlab_account_users');
        $user = $modelUser->getDataById('user_id', $user_id);

        if (!$user || !$user_id) {
            return $this->response->setJSON(["items" => []]);
        }

        $db = \Config\Database::connect();
        $data = [];

        // Query dengan JOIN ke t_layanan_detil untuk filter kode_jenis = 'A' (sampel)
        // Gunakan GROUP BY untuk menghindari duplikasi row jika ada multiple detail items
        $builder = $db->table($this->table . ' as t');
        $builder->select('t.lnKode, t.user_id, t.lnAccEmail, t.lnNoTransaksi, t.lnTgl, t.lnStatus, t.kuisioner');
        $builder->join('t_layanan_detil d', 'd.kode_layanan = t.lnKode', 'inner');
        $builder->where('t.user_id', $user_id);
        $builder->where('d.kode_jenis', 'A');  // Filter hanya sampel (kode_jenis = 'A')
        $builder->groupBy('t.lnKode, t.user_id, t.lnAccEmail, t.lnNoTransaksi, t.lnTgl, t.lnStatus, t.kuisioner');
        $builder->orderBy('t.lnTgl', 'DESC');

        $list = $builder->get()->getResult();

        if (empty($list)) {
            return $this->response->setJSON(["items" => []]);
        }

        // --- Siapkan map pembayaran terakhir per lnKode (satu query) ---
        $lnKodes = array_map(fn($r) => (int) $r->lnKode, $list);

        $payRows = $db->table('t_pembayaran')
            ->select('bayarLnKode, bayarStatus, bayarInvoiceNo, MAX(bayarKode) AS lastKode')
            ->whereIn('bayarLnKode', $lnKodes)
            ->groupBy('bayarLnKode, bayarStatus, bayarInvoiceNo')
            ->orderBy('lastKode', 'DESC')
            ->get()->getResult();

        // Simpan yang terbaru per lnKode
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

            // No Transaksi + Tanggal
            $noTransaksi = (isset($row->lnNoTransaksi) && trim((string) $row->lnNoTransaksi) !== '')
                ? $row->lnNoTransaksi
                : 'Belum tersedia';

            $tanggal = !empty($row->lnTgl) ? date('d-m-Y', strtotime($row->lnTgl)) : '-';
            $response[] = '<div>' . esc($noTransaksi) . '<br><small>' . esc($tanggal) . '</small></div>';

            // Status layanan dengan tracking
            $statusText = $this->getStatusText((int) ($row->lnStatus ?? 0));
            $response[] = '<div class="d-flex gap-2 align-items-center">' .
                '<button class="btn btn-sm btn-outline-primary" onclick="showTrackingModal(\'' . $id . '\', \'' . $row->lnKode . '\', ' . (int) ($row->lnStatus ?? 0) . ')">' .
                '<i class="bi bi-activity"></i> ' . $statusText . '</button>' .
                '</div>';

            // Kuisioner (ambil dari row dulu, kalau kosong fallback dari user)
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

            // Pembayaran terakhir untuk lnKode ini (pakai map hasil query)
            $lnKodeInt = (int) $row->lnKode;
            $bayarStatusVal = isset($payMap[$lnKodeInt]) ? $payMap[$lnKodeInt]['status'] : 0;
            $bayarInvoiceNo = isset($payMap[$lnKodeInt]) ? $payMap[$lnKodeInt]['inv'] : null;

            // statusBayar: 1 = sudah bayar, 0 = belum
            if ($bayarStatusVal === 1) {
                $response[] = '<button class="btn btn-sm btn-success"><i class="bi bi-credit-card"></i> Sudah Bayar</button>';
            } else {
                $response[] = '<button class="btn btn-sm btn-info" onclick="lokasiPembayaran(' . $lnKodeInt . ')"><i class="bi bi-credit-card"></i> Belum Bayar</button>';
            }

            // Akses LHU & kuisioner
            $lnStatusVal = (int) ($row->lnStatus ?? 0);
            $canFillKuesioner = ($kuisionerVal !== 1 && $lnStatusVal === 9);
            $canViewLhu = ($kuisionerVal === 1 && $bayarStatusVal === 1 && $lnStatusVal === 9);
            $lhuInfo = $this->detectLhuFile($row);

            $lhuButtons = [];

            if ($canFillKuesioner) {
                $lhuButtons[] = '<button class="btn btn-sm btn-warning" onclick="loadContent(\'pelayanan/kuesioner/' . $id . '\')"><i class="bi bi-chat-square-text"></i> Isi Kuisioner</button>';
            }

            if ($lhuInfo['has'] && $canViewLhu) {
                $lhuButtons[] = '<button class="btn btn-sm btn-outline-primary" onclick="showPelayananLhuHistory(\'' . $id . '\')"><i class="bi bi-eye"></i> LHU</button>';
            } elseif (!$canFillKuesioner) {
                $reason = 'File LHU tidak dapat diakses.';
                if ($lhuInfo['has'] && !$canViewLhu) {
                    if ($kuisionerVal !== 1) {
                        $reason = 'Isi kuisioner';
                    } elseif ($bayarStatusVal !== 1) {
                        $reason = 'Belum bayar';
                    } elseif ($lnStatusVal !== 9) {
                        $reason = 'LHU diproses';
                    }
                } elseif (!$lhuInfo['has']) {
                    $reason = 'LHU diproses';
                }
                $lhuButtons[] = '<button class="btn btn-sm btn-secondary" disabled><i class="bi bi-eye-slash"></i> ' . esc($reason) . '</button>';
            }

            $buttonHtml = '';
            foreach ($lhuButtons as $btnHtml) {
                $buttonHtml .= '<div>' . $btnHtml . '</div>';
            }

            $response[] = '<div class="d-flex flex-column gap-2 align-items-start">' . $buttonHtml . '</div>';

            // Aksi detail (masking lnKode via enkripsi)
            // $response[] = '<a href="javascript:void(0)" onclick="loadDetail(\'' . $id . '\')" class="btn btn-sm btn-info">Lihat pesanan</a>';

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

        // Encrypted hex parent
        $encLnId = bin2hex($this->encrypter->encrypt($kode));

        $db = \Config\Database::connect();
        $builder = $db->table('t_layanan_detil as d');

        $builder->select("
        d.uji_kode,
        d.kode_layanan,
        d.nama_layanan,
        d.kode_jenis,
        d.metode_pengujian,
        m.nama AS metode_nama,
        SUM(d.jumlah) AS jumlah,
        SUM(d.biaya) AS detBiaya,
        MAX(d.status_layanan) AS detStatusGroup
    ");
        $builder->join('r_metode m', 'm.metode_kode = d.metode_pengujian', 'left');
        $builder->where('d.kode_layanan', $kode);
        $builder->groupBy('d.uji_kode, d.kode_layanan, d.nama_layanan, d.kode_jenis, d.metode_pengujian, m.nama');
        $rows = $builder->get()->getResult();

        $data = [];
        $no = 1;

        foreach ($rows as $row) {
            $response = [];
            $response[] = $no++;
            $response[] = $row->nama_layanan ?? '-';
            $response[] = isset($row->detBiaya) ? number_format($row->detBiaya, 0, ',', '.') : '-';
            $response[] = isset($row->jumlah) ? (int) $row->jumlah : 0;
            $response[] = isset($row->metode_nama) && !empty($row->metode_nama) ? esc($row->metode_nama) : '-';

            // Status grouping (0 = pending, 1 = diterima, 2 = ditolak)
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

            $ujiKodeInt = (int) $row->uji_kode;
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

        if ((int) $user->verifikasi === 1) {
            return $this->response->setJSON(['verified' => true, 'msg' => 'Akun sudah terverifikasi.']);
        } else {
            return $this->response->setJSON([
                'verified' => false,
                'msg' => 'Akun belum diverifikasi. Silakan unggah bukti atau tunggu verifikasi.
             '
            ]);
        }
    }

    public function getSampleIdentity($lnKode = null)
    {
        if (!$lnKode) {
            return $this->response->setJSON([
                'success' => false,
                'message' => 'Kode layanan tidak ditemukan'
            ]);
        }

        try {
            $modelSample = new MyModel('t_identitas_sampel');
            $sampleData = $modelSample->getWhere(['kode_layanan' => $lnKode])->getRow();

            if (!$sampleData) {
                return $this->response->setJSON([
                    'success' => false,
                    'message' => 'Data identitas sampel tidak ditemukan'
                ]);
            }

            return $this->response->setJSON([
                'success' => true,
                'data' => [
                    'jenis' => $sampleData->jenis ?? '-',
                    'kemasan' => $sampleData->kemasan ?? '-',
                    'sifat' => $sampleData->sifat ?? '-',
                    'sisa' => $sampleData->sisa ?? '-',
                    'deskripsi' => $sampleData->deskripsi ?? '-',
                    'keterangan_khusus' => $sampleData->keterangan_khusus ?? '-'
                ]
            ]);
        } catch (\Exception $e) {
            log_message('error', 'Error fetching sample identity: ' . $e->getMessage());
            return $this->response->setJSON([
                'success' => false,
                'message' => 'Terjadi kesalahan saat memuat data identitas sampel'
            ]);
        }
    }

    public function lhuList($id = null)
    {
        if (!$id) {
            return $this->response->setJSON(['items' => []]);
        }

        $session = session();
        $userId = (int) ($session->get('id_user') ?? 0);
        if ($userId <= 0) {
            return $this->response->setJSON(['items' => []]);
        }

        try {
            $lnKode = $this->encrypter->decrypt(hex2bin($id));
        } catch (\Throwable $e) {
            try {
                $lnKode = $this->encrypter->decrypt($id);
            } catch (\Throwable $e2) {
                return $this->response->setJSON(['items' => []]);
            }
        }

        $model = new MyModel($this->table);
        $layanan = $model->getDataById($this->id, $lnKode);

        if (!$layanan || (int) ($layanan->user_id ?? 0) !== $userId) {
            return $this->response->setJSON(['items' => []]);
        }

        $db = \Config\Database::connect();

        try {
            $rows = $db->table('t_files_lhu AS lhu')
                ->select('lhu.file_id, lhu.file, lhu.tanggal_terbit')
                ->where('lhu.kode', $lnKode)
                ->orderBy('CASE WHEN lhu.tanggal_terbit IS NULL THEN 1 ELSE 0 END', 'ASC', false)
                ->orderBy('lhu.tanggal_terbit', 'ASC')
                ->orderBy('lhu.file_id', 'ASC')
                ->get()->getResult();
        } catch (\Throwable $e) {
            log_message('error', 'Pelayanan::lhuList error: ' . $e->getMessage());
            return $this->response->setJSON(['items' => []]);
        }

        if (empty($rows)) {
            return $this->response->setJSON(['items' => []]);
        }

        $items = [];
        foreach ($rows as $index => $row) {
            $tanggal = '-';
            if (!empty($row->tanggal_terbit)) {
                try {
                    $tanggal = date('d/m/Y H:i', strtotime($row->tanggal_terbit));
                } catch (\Throwable $e) {
                    $tanggal = $row->tanggal_terbit;
                }
            }

            $fileUrl = null;
            if (!empty($row->file)) {
                if (preg_match('/^https?:\/\//i', $row->file)) {
                    $fileUrl = $row->file;
                } else {
                    $fileUrl = base_url('uploads/lhu/' . ltrim($row->file, '/'));
                }
            }

            $items[] = [
                'no' => $index + 1,
                'tanggal' => $tanggal,
                'url' => $fileUrl,
            ];
        }

        return $this->response->setJSON(['items' => $items]);
    }

    private function detectLhuFile($row)
    {
        $lnKode = $row->lnKode ?? null;
        if (!$lnKode) {
            return ['has' => false, 'url' => '#'];
        }

        try {
            $db = \Config\Database::connect();
            $builder = $db->table('t_files_lhu as lhu');

            // Join dengan simlab_account_users untuk mendapatkan info uploader
            $builder->select('lhu.file_id, lhu.kode, lhu.file, lhu.upload_by, acc.user_name as uploader_name');
            $builder->join('simlab_account_users as acc', 'acc.user_id = lhu.upload_by', 'left');
            $builder->where('lhu.kode', $lnKode);
            $builder->orderBy('lhu.file_id', 'DESC');
            $builder->limit(1);

            $lhuFile = $builder->get()->getRow();

            if (!$lhuFile) {
                return ['has' => false, 'url' => '#'];
            }

            // Gunakan kolom file
            $filePath = $lhuFile->file ?? null;

            if (empty($filePath)) {
                return ['has' => false, 'url' => '#'];
            }

            // Jika sudah berupa URL lengkap
            if (preg_match('/^https?:\/\//i', $filePath)) {
                return [
                    'has' => true,
                    'url' => $filePath,
                    'uploader' => $lhuFile->uploader_name ?? 'Unknown'
                ];
            }

            // Jika berupa path file relatif
            $possiblePath = FCPATH . 'uploads/lhu/' . ltrim($filePath, '/');
            if (is_file($possiblePath)) {
                $possibleUrl = base_url('uploads/lhu/' . ltrim($filePath, '/'));
                return [
                    'has' => true,
                    'url' => $possibleUrl,
                    'uploader' => $lhuFile->uploader_name ?? 'Unknown'
                ];
            }

            // File tercatat di database tapi tidak ditemukan di storage
            log_message('warning', "LHU file not found in storage: {$filePath} for lnKode: {$lnKode}");
            return ['has' => false, 'url' => '#'];
        } catch (\Throwable $e) {
            log_message('error', 'Error detecting LHU file: ' . $e->getMessage());
            return ['has' => false, 'url' => '#'];
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

        $modelLayanan->updateData(['kuisioner' => 1], $this->id, $lnKode);

        $db->transComplete();

        if ($db->transStatus() === false) {
            return $this->response->setJSON(['res' => false, 'msg' => 'Terjadi kegagalan saat menyimpan data.', 'xname' => csrf_token(), 'xhash' => csrf_hash()]);
        }

        return $this->response->setJSON([
            'res' => 'refresh',
            'link' => site_url('pelayanan'),
            'xname' => csrf_token(),
            'xhash' => csrf_hash()
        ]);
    }
}
