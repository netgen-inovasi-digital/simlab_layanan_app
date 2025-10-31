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
            1 => 'Review Manajer',
            2 => 'Ditolak',
            3 => 'Review Admin',
            4 => 'Pengujian',
            5 => 'Proses LHUS',
            6 => 'LHUS Disetujui',
            7 => 'Proses LHU',
            8 => 'LHU Disetujui',
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

            // Get detail items
            $db = \Config\Database::connect();
            $details = $db->table('simlab_t_detail_layanan')
                ->where('detLnKode', $realId)
                ->get()
                ->getResult();

            return $this->response->setJSON([
                'success' => true,
                'data' => [
                    'kode' => $data->lnKode,
                    'status' => (int)$data->lnStatus,
                    'statusText' => $this->getStatusText((int)$data->lnStatus),
                    'tanggal' => date('d-m-Y', strtotime($data->lnTgl)),
                    'noTransaksi' => $data->lnNoTransaksi ?? 'Belum tersedia',
                    'details' => array_map(function ($detail) {
                        return [
                            'parameter' => $detail->detParameter,
                            'biaya' => number_format($detail->detBiaya, 0, ',', '.'),
                            'jumlah' => $detail->detJumlah,
                            'keterangan' => $detail->detKeterangan ?? '-',
                            'status' => $detail->detStatus ?? '-'
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
        $user_id   = (int)$session->get('id_user');

        // Ambil user login (opsional buat cek kuisioner fallback)
        $modelUser = new MyModel('simlab_account_users');
        $user      = $modelUser->getDataById('user_id', $user_id);

        if (!$user || !$user_id) {
            return $this->response->setJSON(["items" => []]);
        }

        $model = new MyModel($this->table);
        $data  = [];

        //  Filter utama: milik user yang sedang login
        $where = ['user_id' => $user_id];
        $list  = $model->getAllDataById($where, ['lnTgl' => 'DESC']);

        if (empty($list)) {
            return $this->response->setJSON(["items" => []]);
        }

        // --- Siapkan map pembayaran terakhir per lnKode (satu query) ---
        $db = \Config\Database::connect();
        $lnKodes = array_map(fn($r) => (int)$r->lnKode, $list);

        $payRows = $db->table('simlab_t_pembayaran')
            ->select('bayarLnKode, bayarStatus, bayarInvoiceNo, MAX(bayarKode) AS lastKode')
            ->whereIn('bayarLnKode', $lnKodes)
            ->groupBy('bayarLnKode, bayarStatus, bayarInvoiceNo')
            ->orderBy('lastKode', 'DESC')
            ->get()->getResult();

        // Simpan yang terbaru per lnKode
        $payMap = [];
        foreach ($payRows as $p) {
            $ln = (int)$p->bayarLnKode;
            if (!isset($payMap[$ln])) {
                $payMap[$ln] = [
                    'status' => (int)$p->bayarStatus,
                    'inv'    => $p->bayarInvoiceNo ?? null,
                ];
            }
        }

        foreach ($list as $row) {
            $id = bin2hex($this->encrypter->encrypt($row->lnKode));
            $response = [];

            // No Transaksi + Tanggal
            $noTransaksi = (isset($row->lnNoTransaksi) && trim((string)$row->lnNoTransaksi) !== '')
                ? $row->lnNoTransaksi
                : 'Belum tersedia';

            $tanggal     = !empty($row->lnTgl) ? date('d-m-Y', strtotime($row->lnTgl)) : '-';
            $response[]  = '<div>' . esc($noTransaksi) . '<br><small>' . esc($tanggal) . '</small></div>';

            // Status layanan dengan tracking
            $statusClass = $this->getStatusClass((int)($row->lnStatus ?? 0));
            $statusText = $this->getStatusText((int)($row->lnStatus ?? 0));
            $response[] = '<div class="d-flex gap-2 align-items-center">' .
                '<span class="badge bg-' . $statusClass . ' px-2 py-1">' . $statusText . '</span>' .
                '<button class="btn btn-sm btn-outline-primary" onclick="showTrackingModal(\'' . $id . '\', \'' . $row->lnKode . '\', ' . (int)($row->lnStatus ?? 0) . ')">' .
                '<i class="bi bi-activity"></i> Track</button>' .
                '</div>';

            // Kuisioner (ambil dari row dulu, kalau kosong fallback dari user)
            $kuisionerVal = 0;
            if (isset($row->kuisioner) && $row->kuisioner !== '') {
                $kuisionerVal = (int)$row->kuisioner;
            } else {
                $kuFields = ['kuisioner', 'user_kuisioner', 'lnKuisioner', 'ln_kuisioner', 'kuisioner_user'];
                foreach ($kuFields as $kf) {
                    if (isset($user->{$kf}) && $user->{$kf} !== '') {
                        $kuisionerVal = (int)$user->{$kf};
                        break;
                    }
                }
            }

            // Pembayaran terakhir untuk lnKode ini (pakai map hasil query)
            $lnKodeInt = (int)$row->lnKode;
            $bayarStatusVal = isset($payMap[$lnKodeInt]) ? $payMap[$lnKodeInt]['status'] : 0;
            $bayarInvoiceNo = isset($payMap[$lnKodeInt]) ? $payMap[$lnKodeInt]['inv']    : null;

            // statusBayar: 1 = sudah bayar, 0 = belum
            if ($bayarStatusVal === 1) {
                $response[] = '<button class="btn btn-sm btn-success"><i class="bi bi-credit-card"></i> Sudah Bayar</button>';
            } else {
                $response[] = '<button class="btn btn-sm btn-danger" onclick="lokasiPembayaran(' . $lnKodeInt . ')"><i class="bi bi-credit-card"></i> Belum Bayar</button>';
            }

            // Akses LHU
            $lnStatusVal = (int)($row->lnStatus ?? 0);
            $canViewLhu  = ($kuisionerVal === 1 && $bayarStatusVal === 1 && in_array($lnStatusVal, [7, 8], true));
            $lhuInfo     = $this->detectLhuFile($row);

            if ($lhuInfo['has'] && $canViewLhu) {
                $response[] = '<button class="btn btn-sm btn-outline-primary" onclick="window.open(\'' . esc($lhuInfo['url']) . '\', \'_blank\')" title="Buka LHU"><i class="bi bi-eye"></i> Lihat File LHU</button>';
            } else {
                $reason = 'File LHU tidak dapat diakses.';
                if ($lhuInfo['has'] && !$canViewLhu) {
                    if ($kuisionerVal !== 1) {
                        $reason = 'Isi kuisioner';
                    } elseif ($bayarStatusVal !== 1) {
                        $reason = 'Belum bayar';
                    } elseif (!in_array($lnStatusVal, [7, 8], true)) {
                        $reason = 'LHU diproses';
                    }
                } elseif (!$lhuInfo['has']) {
                    $reason = 'LHU diproses';
                }
                $response[] = '<button class="btn btn-sm btn-secondary" disabled><i class="bi bi-eye-slash"></i> ' . esc($reason) . '</button>';
            }

            // Aksi detail (masking lnKode via enkripsi)
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
            return $this->response->setJSON([
                'verified' => false,
                'msg' => 'Akun belum diverifikasi. Silakan unggah bukti atau tunggu verifikasi.
             '
            ]);
        }
    }

    private function detectLhuFile($row)
    {
        $possibleFields = [
            'lnLhu',
            'lnLHU',
            'lnFileLhu',
            'ln_file_lhu',
            'lhu_file',
            'ln_lhu',
            'ln_lhu_file',
            'ln_file_lhu_path'
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
        $idenc   = $this->request->getPost('idenc');

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
            $existingAnswer = $modelJawaban->getWhere(['id_pertanyaan' => $id_pertanyaan, 'user_id' => $user_id])->getRow();
            if (!$existingAnswer) {
                $dataJawaban = [
                    'id_pertanyaan' => $id_pertanyaan,
                    'user_id' => $user_id,
                    'jawaban' => $jawaban,
                    'created_at' => date('Y-m-d H:i:s')
                ];
                $modelJawaban->insertData($dataJawaban);
            }
        }

        $modelLayanan->updateData(['kuisioner' => 1, 'lnStatus' => 8], $this->id, $lnKode);

        $db->transComplete();

        if ($db->transStatus() === false) {
            return $this->response->setJSON(['res' => false, 'msg' => 'Terjadi kegagalan saat menyimpan data.', 'xname' => csrf_token(), 'xhash' => csrf_hash()]);
        }

        return $this->response->setJSON([
            'res'   => 'refresh',
            'link'  => site_url('pelayanan'),
            'xname' => csrf_token(),
            'xhash' => csrf_hash()
        ]);
    }

    public function keranjang()
    {
        $session = session();
        $user_id = $session->get('id_user');
        $modelUser = new MyModel('simlab_account_users');

        // Ambil daftar kategori yang BENAR-BENAR ada di data pengujian
        $db = \Config\Database::connect();
        $builder = $db->table('simlab_r_layanan_pengujian as lp');

        // Query untuk mendapatkan kategori yang benar-benar digunakan
        $builder->select('
        DISTINCT TRIM(LEFT(lp.ujiJenKode, 2)) as jenKode,
        j.jenNama
    ');
        $builder->join('simlab_r_jenis j', 'j.jenKode = TRIM(LEFT(lp.ujiJenKode, 2))', 'left');
        $builder->where('lp.ujiJenKode IS NOT NULL');
        $builder->where('lp.ujiJenKode !=', '');
        $builder->orderBy('j.jenNama', 'ASC');

        $categories = $builder->get()->getResult();

        // NORMALISASI: pastikan jenNama selalu ada (fallback ke jenKode)
        $normalized = [];
        if (!empty($categories)) {
            foreach ($categories as $c) {
                $kode = isset($c->jenKode) ? trim((string)$c->jenKode) : '';
                $nama = (isset($c->jenNama) && trim((string)$c->jenNama) !== '') ? trim((string)$c->jenNama) : $kode;

                if ($kode !== '') {
                    // buat object seragam supaya view bisa mengakses ->jenKode / ->jenNama
                    $normalized[] = (object)[
                        'jenKode' => $kode,
                        'jenNama' => $nama
                    ];
                }
            }
        }

        // Re-index array; jika tetap kosong, kirim array kosong (view akan fallback via AJAX)
        $categories = array_values($normalized);

        $data = [
            'title'      => 'Keranjang Layanan',
            'user'       => $modelUser->getDataById('user_id', $user_id),
            'categories' => $categories,
        ];

        return view('Modules\Pelayanan\Views\v_keranjang', $data);
    }


    public function keranjangDataList()
    {
        $session   = session();
        $keranjang = $session->get($this->sessionKey) ?? [];
        $data      = array();

        // 🧹 Hapus duplikat berdasarkan kombinasi kode + alat + keterangan
        $unique = [];
        $cleanedKeranjang = [];

        foreach ($keranjang as $row) {
            $kode = isset($row['kode']) ? trim((string)$row['kode']) : '';
            $alat = isset($row['alat']) ? trim((string)$row['alat']) : '';
            $ket  = isset($row['keterangan']) ? trim((string)$row['keterangan']) : '';
            $key  = md5($kode . '|' . $alat . '|' . $ket);

            if (!isset($unique[$key])) {
                $unique[$key] = true;
                $cleanedKeranjang[] = $row;
            }
        }

        // 🔁 Simpan hasil pembersihan ke session lagi
        $session->set($this->sessionKey, $cleanedKeranjang);

        // 📦 Build data untuk output
        foreach ($cleanedKeranjang as $idx => $row) {
            $response   = array();

            $parameter  = $row['layanan'] ?? '-';
            $alat       = $row['alat'] ?? '-';
            $jumlah     = (int)($row['jumlah'] ?? 0);
            $keterangan = $row['keterangan'] ?? '';
            $diskon     = (float)($row['diskon'] ?? 0);
            $biayaAsli  = (float)($row['biaya_asli'] ?? 0);
            $biayaTotal = (float)($row['biaya'] ?? 0);

            // 1️⃣ Parameter
            $response[] = esc($parameter);

            // 2️⃣ Instrumen / Alat / Tempat
            $response[] = esc($alat);

            // 3️⃣ Diskon
            $response[] = $diskon > 0 ? $diskon . '%' : '-';

            // 4️⃣ Biaya satuan
            if ($diskon > 0) {
                $hargaDiskon = $biayaAsli - ($biayaAsli * ($diskon / 100));
                $biayaTampil  = '<span style="color:red;text-decoration:line-through;">Rp '
                    . number_format($biayaAsli, 0, ',', '.') . '</span><br>';
                $biayaTampil .= 'Rp ' . number_format($hargaDiskon, 0, ',', '.');
            } else {
                $biayaTampil = 'Rp ' . number_format($biayaAsli, 0, ',', '.');
            }
            $response[] = $biayaTampil;

            // 5️⃣ Jumlah
            $response[] = $jumlah;

            // 6️⃣ Keterangan
            $response[] = esc($keterangan);

            // 7️⃣ Aksi + total hidden
            $hiddenTotal = '<span class="d-none row-total">Rp ' . number_format($biayaTotal, 0, ',', '.') . '</span>';
            $response[]  = $this->aksiKeranjang($idx, true) . $hiddenTotal;

            $data[] = $response;
        }

        return $this->response->setJSON(['items' => $data]);
    }


    public function keranjangSubmit()
    {
        $session = session();
        $post = $this->request->getPost();

        // Ambil input
        $detUjiKode    = $post['detUjiKode']   ?? null;
        $detAlat       = $post['detAlat']      ?? null;
        $detBiaya      = isset($post['detBiaya']) ? (float)$post['detBiaya'] : 0;
        $detParameter  = $post['detParameter'] ?? null;
        $detDiskon     = isset($post['detDiskon']) ? (float)$post['detDiskon'] : 0;
        $detJumlah     = isset($post['detJumlah']) ? (int)$post['detJumlah'] : 1;
        $detKeterangan = trim($post['detKeterangan'] ?? '');

        if (empty($detUjiKode)) {
            return $this->response->setJSON([
                'res'   => false,
                'msg'   => 'Kode uji tidak valid.',
                'xname' => csrf_token(),
                'xhash' => csrf_hash()
            ]);
        }

        // Ambil user identity (untuk diskon)
        $user_id = session()->get('id_user');
        $modelUser = new MyModel('simlab_account_users');
        $user = $modelUser->getDataById('user_id', $user_id);
        $userIdentity = $user && isset($user->user_identity) ? strtoupper(trim($user->user_identity)) : '';

        $appliedDiskon = ($userIdentity === 'ULM') ? max(0, $detDiskon) : 0;

        // Hitung biaya item baru
        $jumlah = max(1, $detJumlah);
        $biayaPerItem = max(0, $detBiaya);
        $biayaSetelahDiskon = $biayaPerItem * (1 - ($appliedDiskon / 100));
        $biayaTotalBaru = $biayaSetelahDiskon * $jumlah;

        // Ambil keranjang lama
        $keranjang = $session->get($this->sessionKey) ?? [];

        // Cari item dengan kode & alat yang sama
        $found = false;
        foreach ($keranjang as $idx => $item) {
            $sameKode = isset($item['kode']) && (string)$item['kode'] === (string)$detUjiKode;
            $sameAlat = (isset($item['alat']) ? trim((string)$item['alat']) : '') === trim((string)$detAlat);

            if ($sameKode && $sameAlat) {
                // 🔹 Tambah jumlah
                $keranjang[$idx]['jumlah'] = (int)($item['jumlah'] ?? 0) + $jumlah;

                // 🔹 Ganti keterangan (bukan gabung)
                $keranjang[$idx]['keterangan'] = $detKeterangan;

                // 🔹 Pastikan biaya asli & diskon tetap
                $biayaAsli = isset($item['biaya_asli']) ? (float)$item['biaya_asli'] : $biayaPerItem;
                $disk = isset($item['diskon']) ? (float)$item['diskon'] : $appliedDiskon;

                // 🔹 Hitung ulang total biaya baru
                $jumlahBaru = (int)$keranjang[$idx]['jumlah'];
                $keranjang[$idx]['biaya'] = ($biayaAsli * $jumlahBaru) * (1 - ($disk / 100));
                $keranjang[$idx]['biaya_asli'] = $biayaAsli;
                $keranjang[$idx]['diskon'] = $disk;

                $found = true;
                break;
            }
        }

        $penyelia = isset($post['ujiPenyelia']) && is_numeric($post['ujiPenyelia']) ? (int)$post['ujiPenyelia'] : null;
        $manajer  = isset($post['ujiManajerTeknis']) && is_numeric($post['ujiManajerTeknis']) ? (int)$post['ujiManajerTeknis'] : null;

        // Jika belum ada item sejenis, tambahkan item baru
        if (!$found) {
            $keranjang[] = [
                'kode'        => $detUjiKode,
                'layanan'     => $detParameter ?? 'Layanan',
                'alat'        => $detAlat ?? '',
                'biaya_asli'  => $biayaPerItem,
                'diskon'      => $appliedDiskon,
                'jumlah'      => $jumlah,
                'keterangan'  => $detKeterangan,
                'biaya'       => $biayaTotalBaru,
                'ujiPenyelia' => $post['ujiPenyelia'] ?? null,
                'ujiManajerTeknis' => $post['ujiManajerTeknis'] ?? null,
            ];
        }

        // Simpan kembali ke session
        $session->set($this->sessionKey, $keranjang);

        return $this->response->setJSON([
            'res' => true,
            'msg' => $found
                ? 'Item berhasil ditambahkan di keranjang.'
                : 'Item baru berhasil ditambahkan ke keranjang.',
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
        $modelAccount    = new MyModel('simlab_account');
        $db = \Config\Database::connect();

        $db->transStart();

        try {
            // === 1) Simpan data utama layanan ===
            $insertLayananId = $modelLayanan->insertData([
                'user_id'       => $user_id,
                'lnAccEmail'    => $emailFromDB,
                'lnNoTransaksi' => null,
                'lnTgl'         => date('Y-m-d H:i:s'),
                'lnStatus'      => 1,
                'kuisioner'     => 0
            ], true);

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

            // === 2) Simpan data pembayaran ===
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

            // === 3) Simpan detail layanan ===
            foreach ($keranjang as $i => $item) {
                $penyeliaCand = isset($item['ujiPenyelia']) && is_numeric($item['ujiPenyelia']) ? (int)$item['ujiPenyelia'] : null;
                $manajerCand  = isset($item['ujiManajerTeknis']) && is_numeric($item['ujiManajerTeknis']) ? (int)$item['ujiManajerTeknis'] : null;
                $jenKodeCand  = isset($item['jenKode']) && trim($item['jenKode']) !== '' ? trim($item['jenKode']) : null;

                $validPenyelia = null;
                $validManajer  = null;
                $validJenKode  = null;

                // --- validasi user id ---
                try {
                    if ($penyeliaCand) {
                        $acc = $modelAccount->getDataById('user_id', $penyeliaCand);
                        $validPenyelia = $acc ? $penyeliaCand : null;
                    }
                    if ($manajerCand) {
                        $acc2 = $modelAccount->getDataById('user_id', $manajerCand);
                        $validManajer = $acc2 ? $manajerCand : null;
                    }
                } catch (\Throwable $e) {
                    $validPenyelia = null;
                    $validManajer  = null;
                }

                // --- ambil dari tabel pengujian jika belum valid ---
                if (($validPenyelia === null || $validManajer === null || $validJenKode === null) && !empty($item['kode'])) {
                    try {
                        $row = $db->table('simlab_r_layanan_pengujian')
                            ->select('ujiPenyelia, ujiManajerTeknis, ujiJenKode')
                            ->where('ujiKode', $item['kode'])
                            ->get()
                            ->getRow();

                        if ($row) {
                            if ($validPenyelia === null && isset($row->ujiPenyelia) && is_numeric($row->ujiPenyelia)) {
                                $acc3 = $modelAccount->getDataById('user_id', (int)$row->ujiPenyelia);
                                if ($acc3) $validPenyelia = (int)$row->ujiPenyelia;
                            }

                            if ($validManajer === null && isset($row->ujiManajerTeknis) && is_numeric($row->ujiManajerTeknis)) {
                                $acc4 = $modelAccount->getDataById('user_id', (int)$row->ujiManajerTeknis);
                                if ($acc4) $validManajer = (int)$row->ujiManajerTeknis;
                            }

                            if ($validJenKode === null && !empty($row->ujiJenKode)) {
                                $validJenKode = substr(trim($row->ujiJenKode), 0, 2);
                            }
                        }
                    } catch (\Throwable $e) {
                        // fallback silent
                    }
                }

                // --- sanitasi jenKode ---
                if ($validJenKode === null && $jenKodeCand !== null) {
                    $validJenKode = substr(preg_replace('/[^A-Za-z0-9]/', '', $jenKodeCand), 0, 2);
                }

                // --- logging bila invalid ---
                if ($penyeliaCand && $validPenyelia === null) {
                    log_message('warning', "Checkout Warning: Penyelia ID {$penyeliaCand} tidak valid, set NULL (item ke-{$i}).");
                }
                if ($manajerCand && $validManajer === null) {
                    log_message('warning', "Checkout Warning: ManajerTeknis ID {$manajerCand} tidak valid, set NULL (item ke-{$i}).");
                }
                if ($jenKodeCand && $validJenKode === null) {
                    log_message('warning', "Checkout Warning: JenKode '{$jenKodeCand}' tidak valid atau tidak ditemukan, set NULL (item ke-{$i}).");
                }

                // --- build data detil ---
                $detil = [
                    'detLnKode'         => $lnKode,
                    'detUjiKode'        => $item['kode'] ?? null,
                    'detBiaya'          => $item['biaya'] ?? null,
                    'detJumlah'         => $item['jumlah'] ?? 1,
                    'detKeterangan'     => $item['keterangan'] ?? null,
                    'detLayanan'        => $item['layanan'] ?? null,
                    'detStatus'         => 0,
                    'detJenKode'        => $validJenKode,
                    'detPenyelia'       => $validPenyelia,
                    'detManajerTeknis'  => $validManajer,
                ];

                // --- insert ke tabel detil ---
                $res = $modelDetil->insertData($detil);
                if (!$res) {
                    $error = $modelDetil->db->error();
                    log_message('error', 'Insert gagal ke simlab_t_layanan_detil. Data: ' . json_encode($detil));
                    log_message('error', 'DB Error: ' . json_encode($error));

                    $db->transRollback();

                    return $this->response->setJSON([
                        'res' => false,
                        'msg' => 'Checkout gagal saat simpan detail: ' . ($error['message'] ?? 'Unknown error'),
                        'xname' => csrf_token(),
                        'xhash' => csrf_hash()
                    ]);
                }
            }

            // === 4) Commit dan bersihkan keranjang ===
            $db->transComplete();
            $session->remove($this->sessionKey);

            return $this->response->setJSON([
                'res' => true,
                'msg' => 'Checkout berhasil!',
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

        // Ambil info user untuk cek user_identity
        $modelUser = new MyModel('simlab_account_users');
        $user = $modelUser->getDataById('user_id', $user_id);
        $userIdentity = '';
        if ($user && isset($user->user_identity)) {
            $userIdentity = strtoupper(trim($user->user_identity));
        }

        // Baca parameter filter dari GET request
        $qRaw = trim((string) ($this->request->getGet('q') ?? $this->request->getGet('search') ?? ''));
        $q = $qRaw !== '' ? mb_strtolower($qRaw, 'UTF-8') : '';

        // PERBAIKAN: Baca jenKode sebagai filter kategori
        $jenKodeFilter = trim((string) ($this->request->getGet('jenKode') ?? ''));

        // SANITASI tambahan: jika client keliru mengirim sesuatu seperti "A?page=1" atau "A&page=1",
        // kita buang sisa setelah '?' atau '&' dan ambil hanya token jenKode yang relevan.
        if ($jenKodeFilter !== '') {
            // decode dulu untuk menghindari encoding oddities
            $jenKodeFilter = rawurldecode($jenKodeFilter);
            // hapus bagian query yang tersisa jika ada
            $jenKodeFilter = preg_replace('/[?&].*$/', '', $jenKodeFilter);
            // jika ada tanda sama dengan (mis-sent key=value), ambil bagian nilai/atau kunci tergantung struktur.
            if (strpos($jenKodeFilter, '=') !== false) {
                // bisa jadi bentuk "jenKode=A" atau "A=page=1"; ambil bagian sebelum '=' kecuali kosong
                $parts = explode('=', $jenKodeFilter, 2);
                if ($parts[0] === '') {
                    $jenKodeFilter = $parts[1];
                } else {
                    $jenKodeFilter = $parts[0];
                }
            }
            // batasi ke karakter alfanumerik dan ambil maksimal 2 char (format jenKode di DB)
            $jenKodeFilter = preg_replace('/[^A-Za-z0-9]/', '', $jenKodeFilter);
            $jenKodeFilter = substr($jenKodeFilter, 0, 2);
            $jenKodeFilter = trim($jenKodeFilter);
        }

        // Query menggunakan Query Builder untuk lebih fleksibel
        $db = \Config\Database::connect();
        $builder = $db->table('simlab_r_layanan_pengujian as lp');

        $builder->select('
        lp.ujiKode,
        lp.ujiBiaya,
        lp.ujiInstansi,
        lp.ujiDiskon,
        lp.ujiLayanan,
        lp.ujiJenKode,
        p.paraNama,
        a.alatNama,
        j.jenNama
    ');

        $builder->join('simlab_r_parameter p', 'p.paraKode = lp.ujiParaKode', 'left');
        $builder->join('simlab_r_alat a', 'a.alatKode = lp.ujiAlatKode', 'left');
        $builder->join('simlab_r_jenis j', 'j.jenKode = lp.ujiJenKode', 'left');

        // Filter kategori: EXACT MATCH atau LEFT() untuk VARCHAR yang lebih panjang
        if ($jenKodeFilter !== '') {
            $builder->where("TRIM(LEFT(lp.ujiJenKode, 2))", $jenKodeFilter);
        }

        $builder->orderBy('lp.ujiKode', 'ASC');

        $listUji = $builder->get()->getResult();

        // Filter tambahan untuk search query (free text)
        if ($q !== '') {
            $filtered = [];
            foreach ($listUji as $row) {
                $fields = [
                    isset($row->paraNama) ? mb_strtolower($row->paraNama, 'UTF-8') : '',
                    isset($row->alatNama) ? mb_strtolower($row->alatNama, 'UTF-8') : '',
                    isset($row->ujiLayanan) ? mb_strtolower($row->ujiLayanan, 'UTF-8') : '',
                    isset($row->jenNama) ? mb_strtolower($row->jenNama, 'UTF-8') : '',
                    isset($row->ujiKode) ? (string)$row->ujiKode : ''
                ];

                $matchQ = false;
                foreach ($fields as $f) {
                    if ($f !== '' && mb_stripos($f, $q, 0, 'UTF-8') !== false) {
                        $matchQ = true;
                        break;
                    }
                }

                if ($matchQ) {
                    $filtered[] = $row;
                }
            }
            $listUji = $filtered;
        }

        // Build response data
        $data = array();
        $no = 1;

        foreach ($listUji as $row) {
            $response = array();


            // Parameter
            $response[] = esc($row->paraNama ?? '-');

            // Instrumen/Alat
            $response[] = esc($row->alatNama ?? '-');

            // Tentukan diskon yang diperbolehkan
            $allowedDiskon = 0;
            if (!empty($row->ujiDiskon) && $row->ujiDiskon > 0 && $userIdentity === 'ULM') {
                $allowedDiskon = (float)$row->ujiDiskon;
            }

            // Biaya
            $biaya = 'Rp ' . number_format($row->ujiBiaya, 0, ',', '.');
            if ($allowedDiskon > 0) {
                $biaya .= ' <span class="badge bg-danger ms-1">-' . $allowedDiskon . '%</span>';
            }
            $response[] = $biaya;

            // Input Jumlah
            $inputJumlah = '<input type="number" class="form-control form-control-sm text-center jumlah" value="1" min="1" style="width:80px;">';
            $response[] = $inputJumlah;

            // Input Keterangan
            $response[] = '<input type="text" class="form-control form-control-sm keterangan" placeholder="Keterangan...">';

            // Tombol Aksi
            $jenKodeClean = isset($row->ujiJenKode) ? trim(substr($row->ujiJenKode, 0, 2)) : '';

            $btnMasukkan = '
            <button type="button" 
                    class="btn btn-success btn-sm btnMasukkan" 
                    data-kode="' . esc($row->ujiKode) . '" 
                    data-alat="' . esc($row->alatNama ?? '') . '" 
                    data-biaya="' . $row->ujiBiaya . '" 
                    data-parameter="' . esc($row->paraNama ?? '') . '"
                    data-diskon="' . $allowedDiskon . '" 
                    data-instansi="' . esc($row->ujiInstansi ?? '') . '"
                    data-jenKode="' . esc($jenKodeClean) . '"
                    data-jenNama="' . esc($row->jenNama ?? '') . '"
                    data-ujiPenyelia="' . (isset($row->ujiPenyelia) ? (int)$row->ujiPenyelia : '') . '"
                    data-ujiManajerTeknis="' . (isset($row->ujiManajerTeknis) ? (int)$row->ujiManajerTeknis : '') . '"
                    title="Masukkan ke keranjang">
                <i class="bi bi-cart-plus"></i>
            </button>
        ';
            $response[] = $btnMasukkan;

            $data[] = $response;
        }

        return $this->response->setJSON([
            "items" => $data,
            "debug" => [
                "total_items" => count($data),
                "filter_jenKode" => $jenKodeFilter,
                "search_query" => $q
            ]
        ]);
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

    public function kategoriList()
    {
        $db = \Config\Database::connect();
        $builder = $db->table('simlab_r_layanan_pengujian as lp');

        // PERBAIKAN: gunakan LEFT() untuk matching
        $builder->select('
        DISTINCT TRIM(LEFT(lp.ujiJenKode, 2)) as jenKode,
        j.jenNama
    ');
        $builder->join('simlab_r_jenis j', 'j.jenKode = TRIM(LEFT(lp.ujiJenKode, 2))', 'left');
        $builder->where('lp.ujiJenKode IS NOT NULL');
        $builder->where('lp.ujiJenKode !=', '');
        $builder->orderBy('j.jenNama', 'ASC');

        $categories = $builder->get()->getResult();

        // Filter out null/empty
        $categories = array_filter($categories, function ($cat) {
            return !empty($cat->jenKode) && !empty($cat->jenNama);
        });

        return $this->response->setJSON([
            'success' => true,
            'categories' => array_values($categories)
        ]);
    }
}
