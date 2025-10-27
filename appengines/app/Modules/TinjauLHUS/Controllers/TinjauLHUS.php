<?php

namespace Modules\TinjauLHUS\Controllers;

use App\Controllers\BaseController;
use App\Models\MyModel;

class TinjauLHUS extends BaseController
{
    private $table = 'simlab_t_layanan';
    private $id    = 'lnKode';

    public function __construct()
    {
        $this->encrypter = \Config\Services::encrypter();
        helper('form');
    }

    public function index()
    {
        $session = session();
        $user_id = $session->get('id_user');

        $modelUser = new MyModel('simlab_account_users');

        $data = [
            'title' => 'Tinjau LHUS',
            'user'  => $modelUser->getDataById('user_id', $user_id),
        ];

        return view('Modules\TinjauLHUS\Views\v_tinjauLhus', $data);
    }

    /**
     * dataList: load daftar LN yang berstatus >= 5 (memproses LHUS dan seterusnya)
     */

    // --------------------- dataList() ---------------------
public function dataList()
{
    $session = session();
    $user_id = $session->get('id_user');

    $model = new MyModel($this->table);
    $data  = [];

    $db = \Config\Database::connect();

    // 1) cari lnKode yang relevan untuk user ini (manajer teknis atau penyelia)
    //    HANYA detil yang detStatus = 1 (aktif) yang dihitung
    $detBuilder = $db->table('simlab_t_layanan_detil as d');
    $detBuilder->select('d.detLnKode');
    $detBuilder->groupStart();
        $detBuilder->where('d.detManajerTeknis', $user_id);
        $detBuilder->orWhere('d.detPenyelia', $user_id);
    $detBuilder->groupEnd();
    // FILTER PENTING: hanya detStatus = 1
    $detBuilder->where('d.detStatus', 1);
    $detRows = $detBuilder->get()->getResult();

    $lnKodeList = [];
    foreach ($detRows as $r) {
        if (is_object($r) && isset($r->detLnKode)) $lnKodeList[] = (int)$r->detLnKode;
        if (is_array($r) && isset($r['detLnKode'])) $lnKodeList[] = (int)$r['detLnKode'];
    }

    // unique & filter invalid
    $lnKodeList = array_values(array_unique(array_filter($lnKodeList, function ($v) {
        return $v !== null && $v !== '' && $v !== 0;
    })));

    if (empty($lnKodeList)) {
        return $this->response->setJSON(['items' => []]);
    }

    // 2) ambil parent LN yang ada di daftar dan status >= 5
    $builder = $db->table('simlab_t_layanan as l');
    $builder->select('l.*, u.user_name as pemesan_name, u.user_email as pemesan_email, u.user_identity as pemesan_identity');
    $builder->join('simlab_account_users as u', 'u.user_id = l.user_id', 'left');
    $builder->whereIn('l.lnKode', $lnKodeList);
    $builder->where('l.lnStatus >=', 5); // hanya LHUS ke atas
    $builder->orderBy('l.lnTgl', 'DESC');

    $list = $builder->get()->getResult();

    // 3) ambil summary detStatusLHUS untuk semua lnKode yang ada (satu query, grouped)
    //    HANYA hitung detil dengan detStatus = 1
    $statusSummary = [];
    $detSummaryRows = $db->table('simlab_t_layanan_detil')
        ->select("detLnKode,
                  COUNT(*) AS total,
                  SUM(CASE WHEN detStatusLHUS = 1 THEN 1 ELSE 0 END) AS cnt1,
                  SUM(CASE WHEN detStatusLHUS = 2 THEN 1 ELSE 0 END) AS cnt2,
                  SUM(CASE WHEN detStatusLHUS = 0 OR detStatusLHUS IS NULL THEN 1 ELSE 0 END) AS cnt0", false)
        ->whereIn('detLnKode', $lnKodeList)
        ->where('detStatus', 1) // filter detStatus aktif
        ->groupBy('detLnKode')
        ->get()
        ->getResultArray();

    foreach ($detSummaryRows as $sr) {
        $detLn = (int) $sr['detLnKode'];
        $statusSummary[$detLn] = [
            'total' => (int) ($sr['total'] ?? 0),
            'cnt1'  => (int) ($sr['cnt1'] ?? 0),
            'cnt2'  => (int) ($sr['cnt2'] ?? 0),
            'cnt0'  => (int) ($sr['cnt0'] ?? 0),
        ];
    }

    foreach ($list as $row) {
        // derive status based on det summary (prioritaskan summary, fallback ke lnStatus dari DB)
        $lnKode = (int)$row->lnKode;
        $derivedStatus = (int)$row->lnStatus; // default fallback

        if (isset($statusSummary[$lnKode])) {
            $s = $statusSummary[$lnKode];
            // Jika masih ada yang belum diproses -> tetap "Memproses LHUS" (5)
            if ($s['cnt0'] > 0) {
                $derivedStatus = 5;
            } else {
                // semua sudah diproses: cek apakah ada yg ditolak
                if ($s['cnt2'] > 0) {
                    $derivedStatus = 4; // ada yang ditolak
                } elseif ($s['total'] > 0 && $s['cnt1'] === $s['total']) {
                    $derivedStatus = 6; // semua diterima -> LHUS Disetujui
                } else {
                    // safety fallback
                    $derivedStatus = 5;
                }
            }
        } else {
            // jika tidak ada baris detil aktif sama sekali, fallback ke lnStatus
            $derivedStatus = (int)$row->lnStatus;
        }

        $response = [];

        $pemesanNama = '-';
        if (!empty($row->pemesan_name)) {
            $pemesanNama = $row->pemesan_name;
        } elseif (!empty($row->lnOrangNama)) {
            $pemesanNama = $row->lnOrangNama;
        }

        $tipe = '-';
        if (!empty($row->pemesan_identity)) {
            $tipe = $row->pemesan_identity;
        } elseif (!empty($row->lnOrangJenis)) {
            $tipe = $row->lnOrangJenis;
        } elseif (!empty($row->lnOrangTipe)) {
            $tipe = $row->lnOrangTipe;
        }

        $tanggal = !empty($row->lnTgl) ? date('d-m-Y H:i', strtotime($row->lnTgl)) : '-';

        $combined = '
            <div style="line-height:1.3;">
                <span style="font-size:1rem; font-weight:600;">' . esc($pemesanNama) . '</span><br>
                <span style="font-size:0.9rem; color:#555;">' . esc($tanggal) . ' | ' . esc($tipe) . '</span>
            </div>';
        $response[] = $combined;

        // gunakan derivedStatus untuk tampilan status utama
        $response[] = $this->formatStatus((int)$derivedStatus);

        $lihatDetailBtn = '<a href="javascript:void(0)" onclick="loadDetail(\'' . bin2hex($this->encrypter->encrypt($row->lnKode)) . '\')" 
                class="btn btn-sm btn-info" title="Tinjau LHUS">
                <i class="bi bi-gear"></i> Tinjau LHUS
              </a>';
        $response[] = $lihatDetailBtn;

        $data[] = $response;
    }

    return $this->response->setJSON(["items" => $data]);
}



    /**
     * detailList: menampilkan detail per LN (digunakan modal)
     * Menyertakan detKode, detStatusLHUS, detKetLhus, dan textarea + aksi
     */
   // --------------------- detailList() ---------------------
public function detailList($id = null)
{
    if (empty($id)) {
        return $this->response->setJSON(['items' => []]);
    }

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

    $session = session();
    $user_id = $session->get('id_user');

    // Ambil detil layanan sesuai detLnKode, batasi baris relevan untuk manajer/penyelia
    $model = new MyModel('simlab_t_layanan_detil d');
    $joins = [
        'simlab_r_layanan_pengujian lp' => 'lp.ujiKode = d.detUjiKode',
        'simlab_r_parameter p'          => 'p.paraKode = lp.ujiParaKode',
        'simlab_r_alat a'               => 'a.alatKode = lp.ujiAlatKode',
    ];
    $where = ['d.detLnKode' => $lnKode];

    $select = "
        d.detKode,
        d.detUjiKode,
        lp.ujiLayanan,
        p.paraNama,
        a.alatNama,
        d.detJumlah,
        d.detBiaya,
        d.detKeterangan,
        d.detil_LHUS,
        d.detStatusLHUS,
        d.detKetLhus,
        d.detManajerTeknis,
        d.detPenyelia,
        d.detStatus
    ";

    try {
        // Ambil semua, lalu kita filter relevansi & hanya detStatus = 1
        $list = $model->getAllDataWithJoinWhereOrder($joins, $where, ['d.detUjiKode' => 'ASC'], $select);
    } catch (\Throwable $e) {
        return $this->response->setJSON(['items' => []]);
    }

    $data = [];
    $no = 1;

    foreach ($list as $row) {
        // SKIP jika detStatus tidak aktif (hanya detStatus = 1 yang dianggap bagian layanan)
        if (isset($row->detStatus) && (int)$row->detStatus !== 1) {
            continue;
        }

        // Filter relevansi (manajer / penyelia) - jika kolom tersedia, cek
        if (isset($row->detManajerTeknis) || isset($row->detPenyelia)) {
            $isRelevant = false;
            if (isset($row->detManajerTeknis) && (int)$row->detManajerTeknis === (int)$user_id) $isRelevant = true;
            if (isset($row->detPenyelia) && (int)$row->detPenyelia === (int)$user_id) $isRelevant = true;
            if (!$isRelevant) continue;
        }

        $layanan = $row->ujiLayanan ?? '-';
        if (!empty($row->paraNama)) {
            $layanan .= ' (' . $row->paraNama . ')';
        }

        $jumlah = isset($row->detJumlah) ? (int)$row->detJumlah : 0;
        $ket    = !empty($row->detKeterangan) ? $row->detKeterangan : '-';

        // Cek file LHUS (tetap boleh ada tombol Lihat)
        $hasFile = false;
        $fileUrl = null;
        $candidates = ['detil_LHUS', 'detil_LHU', 'detFile', 'detLhus', 'detFileLhus', 'det_file_lhus', 'detil_lhus'];

        foreach ($candidates as $cf) {
            if (!empty($row->{$cf})) {
                $raw = trim((string)$row->{$cf});
                if (preg_match('/^https?:\/\//i', $raw)) {
                    $hasFile = true;
                    $fileUrl = $raw;
                    break;
                }
                $possiblePath = FCPATH . 'uploads/lhus/' . ltrim($raw, '/');
                if (is_file($possiblePath)) {
                    $hasFile = true;
                    $fileUrl = base_url('uploads/lhus/' . ltrim($raw, '/'));
                    break;
                }
            }
        }

        $latusHtml = $hasFile && !empty($fileUrl)
            ? '<button type="button" class="btn btn-sm btn-outline-primary" onclick="window.open(\'' . esc($fileUrl) . '\', \'_blank\')"><i class="bi bi-eye"></i> Lihat</button>'
            : '<span class="text-muted">-</span>';

        // prepare LHUS fields
        $detKode = isset($row->detKode) ? (int)$row->detKode : 0;
        $statusLhus = isset($row->detStatusLHUS) ? (int)$row->detStatusLHUS : null;
        $ketLhusVal = isset($row->detKetLhus) ? $row->detKetLhus : '';

        $textareaLhus = '<textarea id="detketlhus_' . $detKode . '" class="form-control detketlhus-input" data-det="' . $detKode . '" rows="2" placeholder="Keterangan LHUS..."'
                      . ' style="max-width:320px; min-width:220px; max-height:140px; resize:vertical; overflow:auto;">'
                      . htmlspecialchars($ketLhusVal, ENT_QUOTES, 'UTF-8') .
                      '</textarea>';

        $saveBtn = '<div class="mt-1"><button type="button" class="btn btn-sm btn-primary btn-save-detketlhus" data-det="' . $detKode . '">'
             . '<i class="bi bi-save"></i> Simpan</button></div>';

        if ($statusLhus === 1) {
            $statusBadge = '<span class="badge bg-success">LHUS Diterima</span>';
        } elseif ($statusLhus === 2) {
            $statusBadge = '<span class="badge bg-danger">LHUS Ditolak</span>';
        } else {
            $statusBadge = '<span class="badge bg-secondary">Belum Diproses</span>';
        }

        $canAccept = ($statusLhus !== 1);
        $canReject = ($statusLhus !== 2);

        $aksiHtml = '<div class="d-flex justify-content-center gap-2 align-items-center">';
        $aksiHtml .= '<span class="text-success btn-action btn-accept-lhus" title="Terima LHUS" data-det="' . $detKode . '"'
                . ($canAccept ? '' : ' style="opacity:0.5;pointer-events:none;"') . '>'
                . '<i class="bi bi-check-circle"></i>'
                . '</span>';
        $aksiHtml .= '<span class="text-warning btn-action btn-reject-lhus" title="Tolak LHUS" data-det="' . $detKode . '"'
                . ($canReject ? '' : ' style="opacity:0.5;pointer-events:none;"') . '>'
                . '<i class="bi bi-x-circle"></i>'
                . '</span>';
        $aksiHtml .= '</div>';

        // Susun kolom: No, Layanan, Jumlah, Keterangan(LN), Status LHUS, LHUS File, Keterangan LHUS, Aksi
        $rowArr = [
            $no++,
            $layanan,
            $jumlah,
            $ket,
            $statusBadge,
            $latusHtml,
            $textareaLhus,
            $aksiHtml
        ];

        $data[] = $rowArr;
    }

    return $this->response->setJSON(['items' => $data, 'encLn' => bin2hex($this->encrypter->encrypt($lnKode))]);
}

    /**
     * saveDetKetLhus: simpan detKetLhus (JSON POST)
     */
    public function saveDetKetLhus()
    {
        $raw = file_get_contents('php://input');
        $input = json_decode($raw, true);

        if (!$input || !isset($input['detKode'])) {
            return $this->response->setJSON([
                'res' => false,
                'msg' => 'Parameter tidak lengkap',
                'xname' => csrf_token(),
                'xhash' => csrf_hash()
            ]);
        }

        $detKode = (int)$input['detKode'];
        $ket = isset($input['ket']) ? $input['ket'] : null;

        try {
            $db = \Config\Database::connect();
            $res = $db->table('simlab_t_layanan_detil')
                      ->where('detKode', $detKode)
                      ->update(['detKetLhus' => $ket]);

            $ok = $db->affectedRows() > 0 || $res === true;
            return $this->response->setJSON([
                'res' => $ok,
                'msg' => $ok ? 'Keterangan LHUS disimpan' : 'Tidak ada perubahan',
                'xname' => csrf_token(),
                'xhash' => csrf_hash()
            ]);
        } catch (\Throwable $e) {
            return $this->response->setJSON([
                'res' => false,
                'msg' => 'Error: ' . $e->getMessage(),
                'xname' => csrf_token(),
                'xhash' => csrf_hash()
            ]);
        }
    }

    /**
     * prosesDetailLhus: update detStatusLHUS per detKode (POST form-data)
     * aksi: 'terima' -> 1 ; 'tolak' -> 2
     */
    public function prosesDetailLhus()
    {
        $detKode = $this->request->getPost('detKode');
        $aksi = $this->request->getPost('aksi');

        if (empty($detKode) || empty($aksi)) {
            return $this->response->setJSON(['res' => false, 'msg' => 'Parameter tidak lengkap', 'xname' => csrf_token(), 'xhash' => csrf_hash()]);
        }

        $detKode = (int)$detKode;
        $map = ['terima' => 1, 'tolak' => 2];
        if (!isset($map[$aksi])) {
            return $this->response->setJSON(['res' => false, 'msg' => 'Aksi tidak valid', 'xname' => csrf_token(), 'xhash' => csrf_hash()]);
        }
        $new = $map[$aksi];

        try {
            $db = \Config\Database::connect();
            $res = $db->table('simlab_t_layanan_detil')
                      ->where('detKode', $detKode)
                      ->update(['detStatusLHUS' => $new]);

            $ok = $db->affectedRows() > 0 || $res === true;
            return $this->response->setJSON(['res' => $ok, 'msg' => $ok ? 'Status LHUS diperbarui' : 'Tidak ada perubahan', 'xname' => csrf_token(), 'xhash' => csrf_hash()]);
        } catch (\Throwable $e) {
            return $this->response->setJSON(['res' => false, 'msg' => 'Error: ' . $e->getMessage(), 'xname' => csrf_token(), 'xhash' => csrf_hash()]);
        }
    }

    /**
     * proses: update lnStatus level parent (terima/tolak seluruh LN)
     * route: tinjaulhus/proses/{encId}/{aksi}
     */
    /**
 * proses: update lnStatus level parent (terima/tolak seluruh LN)
 * route: tinjaulhus/proses/{encId}/{aksi}
 */
    // --------------------- proses() ---------------------
public function proses($idEnc = null, $aksi = null)
{
    $default = [
        'success' => false,
        'msg'     => 'Invalid request',
        'xname'   => csrf_token(),
        'xhash'   => csrf_hash()
    ];

    if (empty($idEnc) || empty($aksi)) {
        $default['msg'] = 'ID atau aksi tidak ditemukan';
        return $this->response->setJSON($default);
    }

    try {
        $lnKode = $this->encrypter->decrypt(hex2bin($idEnc));
    } catch (\Throwable $e) {
        try {
            $lnKode = $this->encrypter->decrypt($idEnc);
        } catch (\Throwable $e2) {
            $default['msg'] = 'ID tidak valid';
            return $this->response->setJSON($default);
        }
    }

    $map = [
        'terima' => 5, // fallback aman: tetap di "Memproses LHUS"
        'tolak'  => 2  // langsung Ditolak (jika dipakai)
    ];

    if (!isset($map[$aksi])) {
        $default['msg'] = 'Aksi tidak dikenali';
        return $this->response->setJSON($default);
    }

    try {
        $db = \Config\Database::connect();

        if ($aksi === 'terima') {
            // Hitung hanya detil AKTIF (detStatus = 1)
            $sql = "SELECT 
                      COUNT(*) AS total,
                      SUM(CASE WHEN detStatusLHUS = 1 THEN 1 ELSE 0 END) AS cnt1,
                      SUM(CASE WHEN detStatusLHUS = 2 THEN 1 ELSE 0 END) AS cnt2,
                      SUM(CASE WHEN detStatusLHUS = 0 OR detStatusLHUS IS NULL THEN 1 ELSE 0 END) AS cnt0
                    FROM simlab_t_layanan_detil
                    WHERE detLnKode = ? AND detStatus = 1";
            $row = $db->query($sql, [$lnKode])->getRowArray();

            $total = isset($row['total']) ? (int)$row['total'] : 0;
            $countStatus1 = isset($row['cnt1']) ? (int)$row['cnt1'] : 0;
            $countStatus2 = isset($row['cnt2']) ? (int)$row['cnt2'] : 0;
            $countStatus0 = isset($row['cnt0']) ? (int)$row['cnt0'] : 0;

            // Jika masih ada item belum diproses -> jangan ubah lnStatus
            if ($countStatus0 > 0) {
                return $this->response->setJSON([
                    'success' => false,
                    'msg'     => 'Masih ada item LHUS yang belum diproses (hanya menghitung item aktif). Harap terima atau tolak semua item aktif terlebih dahulu.',
                    'xname'   => csrf_token(),
                    'xhash'   => csrf_hash()
                ]);
            }

            if ($total > 0 && $countStatus2 > 0) {
                $newStatus = 4; // ada yang ditolak -> LnStatus 4
            } elseif ($total > 0 && $countStatus1 === $total) {
                $newStatus = 6; // semua diterima -> LnStatus 6
            } else {
                // fallback aman ke 5 (memproses LHUS)
                $newStatus = $map['terima'];
            }
        } else {
            // aksi selain 'terima' (mis. 'tolak') -> mapping langsung
            $newStatus = $map[$aksi];
        }

        // Update parent dalam transaction
        $db->transStart();
        $model = new MyModel($this->table);
        $res = $model->updateData(['lnStatus' => $newStatus], $this->id, $lnKode);
        $db->transComplete();

        if ($db->transStatus() === false || !$res) {
            return $this->response->setJSON([
                'success' => false,
                'msg'     => 'Gagal mengupdate status (database)',
                'xname'   => csrf_token(),
                'xhash'   => csrf_hash()
            ]);
        }

        return $this->response->setJSON([
            'success'  => true,
            'msg'      => 'Status berhasil diubah',
            'newStatus'=> $newStatus,
            'xname'    => csrf_token(),
            'xhash'    => csrf_hash()
        ]);
    } catch (\Throwable $e) {
        return $this->response->setJSON([
            'success' => false,
            'msg'     => 'Terjadi error: ' . $e->getMessage(),
            'xname'   => csrf_token(),
            'xhash'   => csrf_hash()
        ]);
    }
}


    /**
     * aksi: helper membuat tombol aksi pada datalist
     */
    private function aksi($id, $status)
    {
        $btn = '<div id="' . $id . '" class="float-end d-flex align-items-center gap-2">';

        // if ($status == 5) {
        //     $btn .= '<span class="text-success btn-action" title="Terima"
        //                 onclick="prosesLhus(\'' . $id . '\', \'terima\')">
        //                 <i class="bi bi-check-circle"></i>
        //             </span>';
        //     $btn .= '<span class="text-warning btn-action" title="Tolak"
        //                 onclick="prosesLhus(\'' . $id . '\', \'tolak\')">
        //                 <i class="bi bi-x-circle"></i>
        //             </span>';
        //     $btn .= '<label class="divider">|</label>';
        // }

        $btn .= '<span class="text-danger btn-action" title="Hapus"
                    onclick="deleteItem(event)">
                    <i class="bi bi-trash"></i>
                </span>';

        $btn .= '</div>';

        return $btn;
    }

    private function formatStatus($status)
    {
        switch ($status) {
            case 5: return '<span class="badge bg-warning">Memproses LHUS</span>';
            case 6: return '<span class="badge bg-success">LHUS Disetujui</span>';
            case 7: return '<span class="badge bg-primary">Memproses LHU</span>';
            case 8: return '<span class="badge bg-info">LHU Disetujui</span>';
            case 9: return '<span class="badge bg-dark">Pengujian Selesai</span>';
            case 2: return '<span class="badge bg-danger">Ditolak</span>';
            default: return '<span class="badge bg-secondary">Unknown</span>';
        }
    }
}
