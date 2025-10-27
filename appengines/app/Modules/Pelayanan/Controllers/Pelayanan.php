<?php

namespace Modules\Pelayanan\Controllers;

use App\Controllers\BaseController;
use App\Models\MyModel;

class Pelayanan extends BaseController
{
    private $table = 'simlab_t_layanan';
    private $id    = 'lnKode';
    protected $encrypter;
    private $sessionKey = 'keranjang';

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

        // Jika lnNoTransaksi kosong/null/trim = tampilkan "Belum tersedia"
        $noTransaksi = (isset($row->lnNoTransaksi) && trim((string)$row->lnNoTransaksi) !== '')
                        ? $row->lnNoTransaksi
                        : 'Belum tersedia';

        $tanggal     = !empty($row->lnTgl) ? date('d-m-Y', strtotime($row->lnTgl)) : '-';
        $response[]  = '<div>' . esc($noTransaksi) . '<br><small>' . esc($tanggal) . '</small></div>';

        // Status transaksi layanan
        $response[] = $this->statusBadge($row->lnStatus);

        // cek kuisioner 
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

        // ambil status pembayaran terakhir untuk lnKode ini
        $bayarStatusVal = 0;
        $bayarInvoiceNo = null;
        try {
            if (!empty($row->lnKode)) {
                $db = \Config\Database::connect();
                $pay = $db->table('simlab_t_pembayaran')
                          ->select('bayarStatus, bayarInvoiceNo')
                          ->where('bayarLnKode', $row->lnKode)
                          ->orderBy('bayarKode', 'DESC')
                          ->limit(1)
                          ->get()
                          ->getRow();

                if ($pay && isset($pay->bayarStatus)) {
                    $bayarStatusVal = (int) $pay->bayarStatus;
                }
                if ($pay && isset($pay->bayarInvoiceNo)) {
                    $bayarInvoiceNo = $pay->bayarInvoiceNo;
                }
            }
        } catch (\Throwable $e) {
            $bayarStatusVal = 0;
        }

        // statusBayar: 1 = sudah bayar, 0 = lakukan pembayaran
        $statusBayar = ($bayarStatusVal === 1) ? 1 : 0;

        // status pembayaran
       if ($statusBayar === 1) {
            $response[] = '<button class="btn btn-sm btn-success" >'
                        . '<i class="bi bi-credit-card"></i> Sudah Bayar</button>';
        } else {
            $response[] = '<button class="btn btn-sm btn-danger" onclick="lokasiPembayaran(' . (int)$row->lnKode . ')">'
                        . '<i class="bi bi-credit-card"></i> Belum Bayar</button>';
        }


        // logic file LHU 
        $lnStatusVal = isset($row->lnStatus) ? (int) $row->lnStatus : 0;
        $canViewLhu = ($kuisionerVal === 1 && $bayarStatusVal === 1 && in_array($lnStatusVal, [7, 8], true));
        $lhuInfo = $this->detectLhuFile($row);

        if ($lhuInfo['has'] && $canViewLhu) {
            $response[] = '<button class="btn btn-sm btn-outline-primary" onclick="window.open(\'' . esc($lhuInfo['url']) . '\', \'_blank\')" title="Buka LHU">'
                        . '<i class="bi bi-eye"></i> Lihat File LHU</button>';
        } else {
            $reason = 'File LHU tidak dapat diakses.';
            if ($lhuInfo['has'] && !$canViewLhu) {
                if ($kuisionerVal !== 1) {
                    $reason = 'Isi kuisioner ';
                } elseif ($bayarStatusVal !== 1) {
                    $reason = 'belum bayar';
                } elseif (!in_array($lnStatusVal, [7,8], true)) {
                    $reason = 'LHU diproses';
                }
            } elseif (!$lhuInfo['has']) {
                $reason = 'LHU diproses';
            }

            $response[] = '<button class="btn btn-sm btn-secondary" disabled>'
                        . '<i class="bi bi-eye-slash"></i> ' . esc($reason) . '</button>';
        }


        $response[] = '<a href="javascript:void(0)" onclick="loadDetail(\'' . $id . '\')" class="btn btn-sm btn-info">Lihat pesanan</a>';


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
            4 => 'Pengujian sedang dilakukan',
            5 => 'File LHUS sedang diproses',
            6 => 'LHUS telah disetujui petugas',
            7 => 'LHU disetujui oleh petugas',
        ];
        $class = [
            0 => 'secondary',
            1 => 'info',
            2 => 'danger',
            3 => 'info',
            4 => 'primary',
            5 => 'info',
            6 => 'warning',
            7 => 'warning',
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
        $response[] = '<div 
                    style="display:block; max-width:240px; min-width:160px; width:100%;
                        max-height:120px; min-height:48px; overflow-y:auto; overflow-x:hidden;
                        padding:4px 6px; border:1px solid #ddd; border-radius:4px; background:#f9f9f9;
                        white-space:pre-wrap; word-break:break-word; font-size:0.9rem;">'
                    . htmlspecialchars($row->detKet ?? '', ENT_QUOTES, 'UTF-8') .
                    '</div>';

        // Status grouping (0 = pending, 1 = diterima, 2 = ditolak)
        $statusGroup = isset($row->detStatusGroup) ? (int)$row->detStatusGroup : null;

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

    public function keranjang()
    {
        $session = session();
        $user_id = $session->get('id_user');

        $modelUser = new MyModel('simlab_account_users');
        $model     = new MyModel('simlab_r_layanan_pengujian');

        $joins = [
            'simlab_r_parameter p' => 'p.paraKode = simlab_r_layanan_pengujian.ujiParaKode',
            'simlab_r_alat a'      => 'a.alatKode = simlab_r_layanan_pengujian.ujiAlatKode'
        ];

        $select = '
            simlab_r_layanan_pengujian.ujiKode,
            simlab_r_layanan_pengujian.ujiBiaya,
            simlab_r_layanan_pengujian.ujiInstansi,
            simlab_r_layanan_pengujian.ujiDiskon,
            simlab_r_layanan_pengujian.ujiLayanan,
            p.paraNama,
            a.alatNama
        ';

        $listUji = $model->getAllDataWithJoinWhereOrder($joins, [], ['ujiKode' => 'ASC'], $select, 'left');

        $data = [
            'title'   => 'Keranjang Layanan',
            'user'    => $modelUser->getDataById('user_id', $user_id),
            'listUji' => $listUji
        ];

        return view('Modules\Pelayanan\Views\v_keranjang', $data);
    }

   public function keranjangDataList()
{
    $session   = session();
    $keranjang = $session->get($this->sessionKey) ?? [];
    $data      = array();

    foreach ($keranjang as $idx => $row) {
        $response   = array();

        // ambil fields
        $parameter  = isset($row['layanan']) ? $row['layanan'] : '-';
        $alat       = isset($row['alat']) ? $row['alat'] : '-';
        $jumlah     = isset($row['jumlah']) ? (int)$row['jumlah'] : 0;
        $keterangan = isset($row['keterangan']) ? $row['keterangan'] : '';
        $diskon     = isset($row['diskon']) ? (float)$row['diskon'] : 0;

        $biayaAsli  = isset($row['biaya_asli']) ? (float)$row['biaya_asli'] : 0;
        $biayaTotal = isset($row['biaya']) ? (float)$row['biaya'] : 0; // total after discount * jumlah

        // 1) Parameter
        $response[] = esc($parameter);

        // 2) Instrumen / Alat / Tempat
        $response[] = esc($alat);

        // 3) Diskon %
        $response[] = $diskon > 0 ? $diskon . '%' : '-';

        // 4) Biaya satuan (tampilkan original + harga setelah diskon jika ada)
        if ($diskon > 0) {
            $hargaDiskon = $biayaAsli - ($biayaAsli * ($diskon / 100));
            $biayaTampil = '<span style="color:red;text-decoration:line-through;">Rp ' . number_format($biayaAsli, 0, ',', '.') . '</span><br>';
            $biayaTampil .= 'Rp ' . number_format($hargaDiskon, 0, ',', '.');
        } else {
            $biayaTampil = 'Rp ' . number_format($biayaAsli, 0, ',', '.');
        }
        $response[] = $biayaTampil;

        // 5) Jumlah
        $response[] = $jumlah;

        // 6) Keterangan
        $response[] = esc($keterangan);

        // 7) Aksi (hapus) + sisipkan hidden total agar JS bisa hitung grand total
        $hiddenTotal = '<span class="d-none row-total">Rp ' . number_format($biayaTotal, 0, ',', '.') . '</span>';
        $response[] = $this->aksiKeranjang($idx, true) . $hiddenTotal;

        $data[] = $response;
    }

    $output = array("items" => $data);
    return $this->response->setJSON($output);
}



   public function keranjangSubmit()
{
    $session = session();
    $post = $this->request->getPost();

    // Ambil input yang dikirim dari JS
    $detUjiKode   = $post['detUjiKode']   ?? null;
    $detAlat      = $post['detAlat']      ?? null; // <- ambil alat/instrumen
    $detBiaya     = isset($post['detBiaya']) ? (float)$post['detBiaya'] : 0;
    $detParameter = $post['detParameter'] ?? null;
    $detDiskon    = isset($post['detDiskon']) ? (float)$post['detDiskon'] : 0;
    $detInstansi  = $post['detInstansi']  ?? null;
    $detJumlah    = isset($post['detJumlah']) ? (int)$post['detJumlah'] : 1;
    $detKeterangan= $post['detKeterangan'] ?? '';

    if (empty($detUjiKode)) {
        return $this->response->setJSON([
            'res' => false,
            'msg' => 'Kode uji tidak valid.',
            'xname' => csrf_token(),
            'xhash' => csrf_hash()
        ]);
    }

    // Ambil user identity dari session
    $user_id = session()->get('id_user');
    $modelUser = new MyModel('simlab_account_users');
    $user = $modelUser->getDataById('user_id', $user_id);
    $userIdentity = '';
    if ($user && isset($user->user_identity)) {
        $userIdentity = strtoupper(trim($user->user_identity));
    }

    // Tentukan diskon yang boleh diterapkan: hanya jika user_identity === 'ULM'
    $appliedDiskon = 0;
    if ($userIdentity === 'ULM') {
        $appliedDiskon = max(0, $detDiskon);
    }

    // Hitung ulang di server
    $jumlah = max(1, (int)$detJumlah);
    $biayaPerItem = max(0, (float)$detBiaya);
    $biayaSetelahDiskon = $biayaPerItem * (1 - ($appliedDiskon / 100));
    $biayaTotal = $biayaSetelahDiskon * $jumlah;

    // Siapkan item — tambahkan 'alat' field agar tampil di preview
    $item = [
        'kode'        => $detUjiKode,
        'layanan'     => $detParameter ?? 'Layanan', // parameter / nama layanan
        'alat'        => $detAlat ?? '',             // <-- baru: instrumen/alat/tempat
        'biaya_asli'  => $detBiaya,
        'diskon'      => $appliedDiskon,
        'jumlah'      => $jumlah,
        'keterangan'  => $detKeterangan,
        'biaya'       => $biayaTotal,
        'ujiPenyelia' => $post['ujiPenyelia'] ?? null,
        'ujiManajerTeknis' => $post['ujiManajerTeknis'] ?? null,
    ];

    $keranjang = $session->get($this->sessionKey) ?? [];
    $keranjang[] = $item;
    $session->set($this->sessionKey, $keranjang);

    return $this->response->setJSON([
        'res' => true,
        'msg' => 'Item berhasil ditambahkan ke keranjang.',
        'xname' => csrf_token(),
        'xhash' => csrf_hash(),
        'items_count' => count($keranjang)
    ]);
}


   

    public function keranjangCheckout()
    {
        $session = session();
        $user_id = $session->get('id_user');

        $modelUser = new MyModel('simlab_account_users');
        $userRow   = $modelUser->getDataById('user_id', $user_id);

        $emailFromDB = $userRow->user_email ?? $session->get('username');
        $nameFromDB  = $userRow->user_name ?? $session->get('nama');
        $identity    = $userRow->user_identity ?? null;

        $keranjang = $session->get($this->sessionKey) ?? [];
        if (empty($keranjang)) {
            return $this->response->setJSON([
                'res' => false,
                'msg' => 'Keranjang masih kosong.',
                'xname' => csrf_token(),
                'xhash' => csrf_hash()
            ]);
        }

        $totalBiaya = array_sum(array_column($keranjang, 'biaya'));

        $modelPembayaran = new MyModel('simlab_t_pembayaran');
        $modelLayanan    = new MyModel('simlab_t_layanan');
        $modelDetil      = new MyModel('simlab_t_layanan_detil');

        $db = \Config\Database::connect();

        // Gunakan transaction untuk atomicity
        $db->transStart();

        try {
            // 1) Insert ke simlab_t_layanan dulu (tanpa lnNoTransaksi)
            $insertLayananId = $modelLayanan->insertData([
                'user_id'       => $user_id,
                'lnAccEmail'    => $emailFromDB,
                'lnNoTransaksi' => null,
                'lnTgl'         => date('Y-m-d H:i:s'),
                'lnStatus'      => 1,
                'kuisioner'     => 0
            ], true);

            // Pastikan kita punya lnKode (primary key) yang valid
            if (!$insertLayananId) {
                $insertLayananId = $db->insertID();
            }

            if (!$insertLayananId) {
                $db->transRollback();
                return $this->response->setJSON([
                    'res' => false,
                    'msg' => 'Gagal membuat record layanan (lnKode tidak tersedia).',
                    'xname' => csrf_token(),
                    'xhash' => csrf_hash()
                ]);
            }

            $lnKode = (int)$insertLayananId;

            // 2) Sekarang insert pembayaran yang merujuk ke lnKode yang sudah ada
            $today = date('Y-m-d');
            $insertPembayaranId = $modelPembayaran->insertData([
                'bayarLnKode'     => $lnKode,
                'bayarTotalBiaya' => $totalBiaya,
                'bayarStatus'     => 0,
                'bayarInvoiceNo'  => null,
                'bayarInvoiceTgl' => $today,
                'bayarBuktiFile'  => null
            ], true);

            if (!$insertPembayaranId) {
                $db->transRollback();
                return $this->response->setJSON([
                    'res' => false,
                    'msg' => 'Gagal simpan pembayaran.',
                    'xname' => csrf_token(),
                    'xhash' => csrf_hash()
                ]);
            }

            // 3) Simpan detail layanan (detLnKode -> lnKode)
            foreach ($keranjang as $item) {
                $detil = [
                    'detLnKode'         => $lnKode,
                    'detUjiKode'        => $item['kode'] ?? null,
                    'detBiaya'          => $item['biaya'] ?? null,
                    'detJumlah'         => $item['jumlah'] ?? 1,
                    'detKeterangan'     => $item['keterangan'] ?? null,
                    'detLayanan'        => $item['layanan'] ?? null,
                    'detStatus'         => 0,
                    'detJenKode'        => null,
                    'detPenyelia'       => $item['ujiPenyelia'] ?? null,
                    'detManajerTeknis'  => $item['ujiManajerTeknis'] ?? null,
                ];

                $res = $modelDetil->insertData($detil);
                if (!$res) {
                    $error = $modelDetil->db->error();
                    log_message('error', ' Insert gagal ke simlab_t_layanan_detil. Data: ' . json_encode($detil));
                    log_message('error', ' DB Error: ' . json_encode($error));

                    $db->transRollback();

                    return $this->response->setJSON([
                        'res' => false,
                        'msg' => 'Checkout gagal saat simpan detail: ' . ($error['message'] ?? 'Unknown error'),
                        'xname' => csrf_token(),
                        'xhash' => csrf_hash()
                    ]);
                }
            }

            // jika semua sukses, commit
            $db->transComplete();

            // kosongkan keranjang
            $session->remove($this->sessionKey);

            return $this->response->setJSON([
                'res' => true,
                'msg' => 'Checkout berhasil! ', 
                'lnKode' => $lnKode,
                'xname' => csrf_token(),
                'xhash' => csrf_hash()
            ]);
        } catch (\Exception $e) {
            if ($db->transStatus() === FALSE) {
                $db->transRollback();
            }

            log_message('error', 'Checkout exception: ' . $e->getMessage() . ' Trace: ' . $e->getTraceAsString());

            return $this->response->setJSON([
                'res' => false,
                'msg' => 'Checkout gagal: ' . $e->getMessage(),
                'xname' => csrf_token(),
                'xhash' => csrf_hash()
            ]);
        }
    }

        public function keranjangDelete($id)
    {
        $session   = session();
        $keranjang = $session->get($this->sessionKey) ?? [];

        // Jika index valid dan ada, hapus. Jika tidak ada, tetap dianggap sukses (tanpa validasi).
        if (isset($keranjang[$id])) {
            unset($keranjang[$id]);
            // reset index agar berurutan kembali
            $keranjang = array_values($keranjang);
            $session->set($this->sessionKey, $keranjang);

            return $this->response->setJSON([
                'res'   => true,
                'msg'   => 'Item berhasil dihapus.',
                'xname' => csrf_token(),
                'xhash' => csrf_hash()
            ]);
        }

        // Kalau index tidak ditemukan, jangan error — kembalikan sukses juga.
        // (Opsional: bisa tetap set session jika keranjang kosong)
        if (empty($keranjang)) {
            $session->remove($this->sessionKey);
        } else {
            // tidak ditemukan, tetap simpan keranjang apa adanya (no-op)
            $session->set($this->sessionKey, $keranjang);
        }

        return $this->response->setJSON([
            'res'   => true,
            'msg'   => 'Item tidak ditemukan di keranjang, namun operasi hapus dianggap berhasil.',
            'xname' => csrf_token(),
            'xhash' => csrf_hash()
        ]);
    }


   

   public function keranjangDataListLayanan()
{
    $session = session();
    $user_id = $session->get('id_user');

    // ambil info user (jika ada) untuk cek user_identity
    $modelUser = new MyModel('simlab_account_users');
    $user = $modelUser->getDataById('user_id', $user_id);
    $userIdentity = '';
    if ($user && isset($user->user_identity)) {
        $userIdentity = strtoupper(trim($user->user_identity));
    }

    $model = new MyModel('simlab_r_layanan_pengujian');

    $joins = [
        'simlab_r_parameter p' => 'p.paraKode = simlab_r_layanan_pengujian.ujiParaKode',
        'simlab_r_alat a'      => 'a.alatKode = simlab_r_layanan_pengujian.ujiAlatKode'
    ];

    $select = '
        simlab_r_layanan_pengujian.ujiKode,
        simlab_r_layanan_pengujian.ujiBiaya,
        simlab_r_layanan_pengujian.ujiInstansi,
        simlab_r_layanan_pengujian.ujiDiskon,
        simlab_r_layanan_pengujian.ujiLayanan,
        p.paraNama,
        a.alatNama
    ';

    $listUji = $model->getAllDataWithJoinWhereOrder(
        $joins, 
        [], 
        ['ujiKode' => 'ASC'], 
        $select, 
        'left'
    );

    // Baca query pencarian (compatibel q atau search)
    $qRaw = trim((string) ($this->request->getGet('q') ?? $this->request->getGet('search') ?? ''));
    $q = $qRaw !== '' ? mb_strtolower($qRaw, 'UTF-8') : '';

    // Jika ada query, lakukan filter pada array $listUji
    if ($q !== '') {
        $filtered = [];
        foreach ($listUji as $row) {
            $fields = [
                isset($row->paraNama) ? mb_strtolower($row->paraNama, 'UTF-8') : '',
                isset($row->alatNama) ? mb_strtolower($row->alatNama, 'UTF-8') : '',
                isset($row->ujiLayanan) ? mb_strtolower($row->ujiLayanan, 'UTF-8') : '',
                isset($row->ujiKode) ? (string)$row->ujiKode : ''
            ];

            foreach ($fields as $f) {
                if ($f !== '' && mb_stripos($f, $q, 0, 'UTF-8') !== false) {
                    $filtered[] = $row;
                    break;
                }
            }
        }
        $listUji = $filtered;
    }

    $data = array();
    $no = 1;

    foreach ($listUji as $row) {
        $response = array();

        // Parameter
        $response[] = esc($row->paraNama);

        // Instrumen/Alat
        $response[] = esc($row->alatNama);

        // Tentukan apakah diskon boleh diterapkan untuk user saat ini
        $allowedDiskon = 0;
        if (!empty($row->ujiDiskon) && $row->ujiDiskon > 0 && $userIdentity === 'ULM') {
            $allowedDiskon = (float)$row->ujiDiskon;
        }

        // Biaya (tampilkan badge diskon hanya jika allowedDiskon > 0)
        $biaya = 'Rp ' . number_format($row->ujiBiaya, 0, ',', '.');
        if ($allowedDiskon > 0) {
            $biaya .= ' <span class="text-danger fw-bold">- ' . $allowedDiskon . '%</span>';
        }
        $response[] = $biaya;

        // Input Jumlah (tanpa tombol + / -)
        $inputJumlah = '
            <input type="number" class="form-control form-control-sm text-center jumlah" value="1" min="1" style="width:100px;">
        ';
        $response[] = $inputJumlah;

        // Input Keterangan
        $response[] = '<input type="text" class="form-control form-control-sm keterangan" placeholder="Keterangan...">';

        // Tombol Aksi (data-diskon = allowedDiskon, bukan nilai DB langsung)
        $btnMasukkan = '
            <button type="button" 
                    class="btn btn-success btn-sm btnMasukkan" 
                    data-kode="' . esc($row->ujiKode) . '" 
                    data-alat="' . esc($row->alatNama) . '" 
                    data-biaya="' . $row->ujiBiaya . '" 
                    data-parameter="' . esc($row->paraNama) . '"
                    data-diskon="' . $allowedDiskon . '" 
                    data-instansi="' . esc($row->ujiInstansi) . '" 
                    title="Masukkan ke keranjang">
                <i class="bi bi-cart-plus"></i>
            </button>
        ';
        $response[] = $btnMasukkan;

        $data[] = $response;
    }

    $output = array("items" => $data);
    return $this->response->setJSON($output);
}



    private function aksiKeranjang($id, $isPreview = false)
{
    $functionName = $isPreview ? 'deleteItemFromPreview' : 'deleteItem';
    
    return '<div id="item-' . $id . '" class="text-center">
        <span data-index="' . $id . '" 
            class="text-danger btn-action btn-delete-item" 
            style="cursor: pointer;"
            title="Hapus" 
            onclick="' . $functionName . '(event)">
            <i class="bi bi-trash"></i>
        </span>
    </div>';
}


}