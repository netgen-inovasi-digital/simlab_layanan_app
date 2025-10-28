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
        $user_id = (int)$session->get('id_user');

        $db   = \Config\Database::connect();
        $data = [];

        // 1) Cari lnKode relevan untuk user (det aktif saja)
        $detRows = $db->table('simlab_t_layanan_detil as d')
            ->select('d.detLnKode')
            ->groupStart()
                ->where('d.detManajerTeknis', $user_id)
                ->orWhere('d.detPenyelia', $user_id)
            ->groupEnd()
            ->where('d.detStatus', 1)
            ->get()->getResult();

        $lnKodeList = [];
        foreach ($detRows as $r) {
            $lnKodeList[] = (int)(is_object($r) ? $r->detLnKode : $r['detLnKode']);
        }
        $lnKodeList = array_values(array_unique(array_filter($lnKodeList)));

        if (empty($lnKodeList)) {
            return $this->response->setJSON(['items' => []]);
        }

        // 2) Ambil parent LN (>=5)
        $list = $db->table('simlab_t_layanan as l')
            ->select('l.*, u.user_name as pemesan_name, u.user_email as pemesan_email, u.user_identity as pemesan_identity')
            ->join('simlab_account_users as u', 'u.user_id = l.user_id', 'left')
            ->whereIn('l.lnKode', $lnKodeList)
            ->where('l.lnStatus >=', 5)
            ->orderBy('l.lnTgl', 'DESC')
            ->get()->getResult();

        // 3) Ringkasan GLOBAL (det aktif)
        $statusSummary = [];
        $rowsG = $db->table('simlab_t_layanan_detil')
            ->select("
                detLnKode,
                COUNT(*) AS total,
                SUM(CASE WHEN detStatusLHUS = 1 THEN 1 ELSE 0 END) AS cnt1,
                SUM(CASE WHEN detStatusLHUS = 2 THEN 1 ELSE 0 END) AS cnt2,
                SUM(CASE WHEN detStatusLHUS = 0 OR detStatusLHUS IS NULL THEN 1 ELSE 0 END) AS cnt0
            ", false)
            ->whereIn('detLnKode', $lnKodeList)
            ->where('detStatus', 1)
            ->groupBy('detLnKode')
            ->get()->getResultArray();

        foreach ($rowsG as $sr) {
            $statusSummary[(int)$sr['detLnKode']] = [
                'total' => (int)$sr['total'],
                'cnt1'  => (int)$sr['cnt1'],
                'cnt2'  => (int)$sr['cnt2'],
                'cnt0'  => (int)$sr['cnt0'],
            ];
        }

        // 4) Ringkasan SUBSET USER (det aktif ditangani user login)
        $userSummary = [];
        $rowsU = $db->table('simlab_t_layanan_detil')
            ->select("
                detLnKode,
                COUNT(*) AS total,
                SUM(CASE WHEN detStatusLHUS = 1 THEN 1 ELSE 0 END) AS cnt1,
                SUM(CASE WHEN detStatusLHUS = 2 THEN 1 ELSE 0 END) AS cnt2,
                SUM(CASE WHEN detStatusLHUS = 0 OR detStatusLHUS IS NULL THEN 1 ELSE 0 END) AS cnt0
            ", false)
            ->whereIn('detLnKode', $lnKodeList)
            ->where('detStatus', 1)
            ->groupStart()
                ->where('detManajerTeknis', $user_id)
                ->orWhere('detPenyelia', $user_id)
            ->groupEnd()
            ->groupBy('detLnKode')
            ->get()->getResultArray();

        foreach ($rowsU as $sr) {
            $userSummary[(int)$sr['detLnKode']] = [
                'total' => (int)$sr['total'],
                'cnt1'  => (int)$sr['cnt1'],
                'cnt2'  => (int)$sr['cnt2'],
                'cnt0'  => (int)$sr['cnt0'],
            ];
        }

        // 5) Build items
        $no = 1;
        foreach ($list as $row) {
            $lnKode = (int)$row->lnKode;
            $derivedStatus = (int)$row->lnStatus; // fallback

            // TAMPILAN prioritas untuk user login:
            if (isset($userSummary[$lnKode]) && $userSummary[$lnKode]['total'] > 0) {
                $su = $userSummary[$lnKode];
                if ($su['cnt0'] === 0) {
                    if ($su['cnt1'] === $su['total']) {
                        // Semua item yang DITANGANI user ini sudah diterima -> tampil sebagai Disetujui
                        $derivedStatus = 6;
                    } elseif ($su['cnt2'] > 0) {
                        // ada yang ditolak di subset user -> boleh tampilkan 2 (Ditolak) jika ingin
                        $derivedStatus = 2;
                    } else {
                        $derivedStatus = 5;
                    }
                } else {
                    $derivedStatus = 5;
                }
            } elseif (isset($statusSummary[$lnKode])) {
                // fallback ke global
                $s = $statusSummary[$lnKode];
                if ($s['cnt0'] > 0) {
                    $derivedStatus = 5;
                } elseif ($s['total'] > 0 && $s['cnt1'] === $s['total']) {
                    $derivedStatus = 6;
                } elseif ($s['cnt2'] > 0) {
                    $derivedStatus = 2; // tampilkan ditolak
                } else {
                    $derivedStatus = 5;
                }
            }

            $pemesanNama = $row->pemesan_name ?: ($row->lnOrangNama ?? '-');
            $tipe        = $row->pemesan_identity ?: ($row->lnOrangJenis ?? ($row->lnOrangTipe ?? '-'));
            $tanggal     = !empty($row->lnTgl) ? date('d-m-Y H:i', strtotime($row->lnTgl)) : '-';

            $combined = '
                <div style="line-height:1.3;">
                    <span style="font-size:1rem; font-weight:600;">' . esc($pemesanNama) . '</span><br>
                    <span style="font-size:0.9rem; color:#555;">' . esc($tanggal) . ' | ' . esc($tipe) . '</span>
                </div>';

            $data[] = [
                $combined,
                $this->formatStatus((int)$derivedStatus),
                '<a href="javascript:void(0)" onclick="loadDetail(\'' . bin2hex($this->encrypter->encrypt($row->lnKode)) . '\')" class="btn btn-sm btn-info"><i class="bi bi-gear"></i> Tinjau LHUS</a>',
            ];
        }

        return $this->response->setJSON(["items" => $data]);
    }

    /**
     * detailList: menampilkan detail per LN (digunakan modal)
     */
    // --------------------- detailList() ---------------------
    public function detailList($id = null)
    {
        if (empty($id)) {
            return $this->response->setJSON(['items' => []]);
        }

        // tolerant decrypt (hex/raw)
        try {
            $lnKode = $this->encrypter->decrypt(hex2bin($id));
        } catch (\Throwable $e) {
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
        $user_id = (int)$session->get('id_user');

        $model = new MyModel('simlab_t_layanan_detil d');
        $joins = [
            'simlab_r_layanan_pengujian lp' => 'lp.ujiKode = d.detUjiKode',
            'simlab_r_parameter p'          => 'p.paraKode = lp.ujiParaKode',
            'simlab_r_alat a'               => 'a.alatKode = lp.ujiAlatKode',
        ];
        $where   = ['d.detLnKode' => $lnKode];
        $select  = "
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
            $list = $model->getAllDataWithJoinWhereOrder($joins, $where, ['d.detUjiKode' => 'ASC'], $select);
        } catch (\Throwable $e) {
            return $this->response->setJSON(['items' => []]);
        }

        $data = [];
        $no   = 1;

        foreach ($list as $row) {
            if ((int)($row->detStatus ?? 0) !== 1) continue; // hanya detil aktif

            // relevansi user
            $isRelevant = false;
            if (isset($row->detManajerTeknis) && (int)$row->detManajerTeknis === $user_id) $isRelevant = true;
            if (isset($row->detPenyelia)      && (int)$row->detPenyelia      === $user_id) $isRelevant = true;
            if (!$isRelevant) continue;

            $layanan = $row->ujiLayanan ?? '-';
            if (!empty($row->paraNama)) $layanan .= ' (' . $row->paraNama . ')';
            $jumlah = (int)($row->detJumlah ?? 0);
            $ket    = $row->detKeterangan ?: '-';

            // Cek file LHUS
            $hasFile = false; $fileUrl = null;
            $candidates = ['detil_LHUS','detil_LHU','detFile','detLhus','detFileLhus','det_file_lhus','detil_lhus'];
            foreach ($candidates as $cf) {
                if (!empty($row->{$cf})) {
                    $raw = trim((string)$row->{$cf});
                    if (preg_match('/^https?:\/\//i', $raw)) { $hasFile = true; $fileUrl = $raw; break; }
                    $possiblePath = FCPATH . 'uploads/lhus/' . ltrim($raw, '/');
                    if (is_file($possiblePath)) { $hasFile = true; $fileUrl = base_url('uploads/lhus/' . ltrim($raw, '/')); break; }
                }
            }
            $latusHtml = $hasFile && $fileUrl
                ? '<button type="button" class="btn btn-sm btn-outline-primary" onclick="window.open(\'' . esc($fileUrl) . '\', \'_blank\')"><i class="bi bi-eye"></i> Lihat</button>'
                : '<span class="text-muted">-</span>';

            $detKode    = (int)($row->detKode ?? 0);
            $statusLhus = isset($row->detStatusLHUS) ? (int)$row->detStatusLHUS : null;
            $ketLhusVal = $row->detKetLhus ?? '';

            $textareaLhus =
                '<textarea id="detketlhus_' . $detKode . '" class="form-control detketlhus-input" data-det="' . $detKode . '" rows="2" placeholder="Keterangan LHUS..."' .
                ' style="max-width:320px; min-width:220px; max-height:140px; resize:vertical; overflow:auto;">' .
                htmlspecialchars($ketLhusVal, ENT_QUOTES, 'UTF-8') .
                '</textarea>'.
                '<div class="mt-1"><button type="button" class="btn btn-sm btn-primary btn-save-detketlhus" data-det="' . $detKode . '"><i class="bi bi-save"></i> Simpan</button></div>';

            if     ($statusLhus === 1) 
                $statusBadge = '<span class="badge bg-success">LHUS Diterima</span>';
            elseif ($statusLhus === 2) 
                $statusBadge = '<span class="badge bg-danger">LHUS Ditolak</span>';
            else       
                $statusBadge = '<span class="badge bg-secondary">Belum Diproses</span>';

            $canAccept = ($statusLhus !== 1);
            $canReject = ($statusLhus !== 2);

            $aksiHtml  = '<div class="d-flex justify-content-center gap-2 align-items-center">';
            $aksiHtml .= '<span class="text-success btn-action btn-accept-lhus" title="Terima LHUS" data-det="' . $detKode . '"' . ($canAccept ? '' : ' style="opacity:.5;pointer-events:none;"') . '><i class="bi bi-check-circle"></i></span>';
            $aksiHtml .= '<span class="text-warning btn-action btn-reject-lhus" title="Tolak LHUS"  data-det="' . $detKode . '"' . ($canReject ? '' : ' style="opacity:.5;pointer-events:none;"') . '><i class="bi bi-x-circle"></i></span>';
            $aksiHtml .= '</div>';

            $data[] = [
                $no++,
                $layanan,
                $jumlah,
                $ket,
                $statusBadge,
                $latusHtml,
                $textareaLhus,
                $aksiHtml
            ];
        }

        return $this->response->setJSON([
            'items' => $data,
            'encLn' => bin2hex($this->encrypter->encrypt($lnKode))
        ]);
    }

    /**
     * saveDetKetLhus: simpan detKetLhus (JSON POST)
     */
    public function saveDetKetLhus()
    {
        $raw   = file_get_contents('php://input');
        $input = json_decode($raw, true);

        if (!$input || !isset($input['detKode'])) {
            return $this->response->setJSON([
                'res'   => false,
                'msg'   => 'Parameter tidak lengkap',
                'xname' => csrf_token(),
                'xhash' => csrf_hash()
            ]);
        }

        $detKode = (int)$input['detKode'];
        $ket     = $input['ket'] ?? null;

        try {
            $db  = \Config\Database::connect();
            $res = $db->table('simlab_t_layanan_detil')
                ->where('detKode', $detKode)
                ->update(['detKetLhus' => $ket]);

            $ok = $db->affectedRows() > 0 || $res === true;
            return $this->response->setJSON([
                'res'   => $ok,
                'msg'   => $ok ? 'Keterangan LHUS disimpan' : 'Tidak ada perubahan',
                'xname' => csrf_token(),
                'xhash' => csrf_hash()
            ]);
        } catch (\Throwable $e) {
            return $this->response->setJSON([
                'res'   => false,
                'msg'   => 'Error: ' . $e->getMessage(),
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
        $aksi    = $this->request->getPost('aksi');

        if (empty($detKode) || empty($aksi)) {
            return $this->response->setJSON([
                'res'   => false,
                'msg'   => 'Parameter tidak lengkap',
                'xname' => csrf_token(),
                'xhash' => csrf_hash()
            ]);
        }

        $detKode = (int)$detKode;
        $map = ['terima' => 1, 'tolak' => 2];
        if (!isset($map[$aksi])) {
            return $this->response->setJSON([
                'res'   => false,
                'msg'   => 'Aksi tidak valid',
                'xname' => csrf_token(),
                'xhash' => csrf_hash()
            ]);
        }
        $new = $map[$aksi];

        try {
            $db  = \Config\Database::connect();
            $res = $db->table('simlab_t_layanan_detil')
                ->where('detKode', $detKode)
                ->update(['detStatusLHUS' => $new]);

            $ok = $db->affectedRows() > 0 || $res === true;
            return $this->response->setJSON([
                'res'   => $ok,
                'msg'   => $ok ? 'Status LHUS diperbarui' : 'Tidak ada perubahan',
                'xname' => csrf_token(),
                'xhash' => csrf_hash()
            ]);
        } catch (\Throwable $e) {
            return $this->response->setJSON([
                'res'   => false,
                'msg'   => 'Error: ' . $e->getMessage(),
                'xname' => csrf_token(),
                'xhash' => csrf_hash()
            ]);
        }
    }

    /**
     * proses: update lnStatus level parent (terima/tolak seluruh LN)
     * route: tinjaulhus/proses/{encId}/{aksi}
     */
    // --------------------- proses() ---------------------
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

    // tolerant decrypt
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
        'terima' => 5, // guard/fallback
        'tolak'  => 2
    ];
    if (!isset($map[$aksi])) {
        $default['msg'] = 'Aksi tidak dikenali';
        return $this->response->setJSON($default);
    }

    try {
        $db = \Config\Database::connect();

        if ($aksi === 'terima') {
            $session = session();
            $user_id = (int) $session->get('id_user');

            // --- RINGKASAN GLOBAL: hanya detil AKTIF (detStatus = 1) ---
            $rowG = $db->query("
                SELECT 
                    COUNT(*) AS total,
                    SUM(CASE WHEN detStatusLHUS = 1 THEN 1 ELSE 0 END) AS cnt1,
                    SUM(CASE WHEN detStatusLHUS = 0 OR detStatusLHUS IS NULL THEN 1 ELSE 0 END) AS cnt0
                FROM simlab_t_layanan_detil
                WHERE detLnKode = ? AND detStatus = 1
            ", [$lnKode])->getRowArray();

            $gTotal = (int)($rowG['total'] ?? 0);
            $gCnt1  = (int)($rowG['cnt1']  ?? 0);
            $gCnt0  = (int)($rowG['cnt0']  ?? 0);

            // --- RINGKASAN SUBSET USER (manajer teknis / penyelia yang login), det aktif saja ---
            $rowU = $db->table('simlab_t_layanan_detil')
                ->select("
                    COUNT(*) AS total,
                    SUM(CASE WHEN detStatusLHUS = 1 THEN 1 ELSE 0 END) AS cnt1,
                    SUM(CASE WHEN detStatusLHUS = 0 OR detStatusLHUS IS NULL THEN 1 ELSE 0 END) AS cnt0
                ", false)
                ->where(['detLnKode' => $lnKode, 'detStatus' => 1])
                ->groupStart()
                    ->where('detManajerTeknis', $user_id)
                    ->orWhere('detPenyelia', $user_id)
                ->groupEnd()
                ->get()->getRowArray();

            $uTotal = (int)($rowU['total'] ?? 0);
            $uCnt1  = (int)($rowU['cnt1']  ?? 0);
            $uCnt0  = (int)($rowU['cnt0']  ?? 0);

            // === RULE 1: Global semua diterima -> set lnStatus = 6 (kirim ke admin) ===
            if ($gTotal > 0 && $gCnt1 === $gTotal) {
                $db->table('simlab_t_layanan')
                    ->where('lnKode', $lnKode)
                    ->update(['lnStatus' => 6]);

                return $this->response->setJSON([
                    'success' => true,
                    'msg'     => 'Berhasil. Semua layanan aktif telah diterima. LHUS disetujui (lnStatus = 6) dan dikirim ke admin.',
                    'xname'   => csrf_token(),
                    'xhash'   => csrf_hash()
                ]);
            }

          // === RULE 2: Global belum tuntas, tetapi subset user tuntas -> BERHASIL (parsial), TANPA ubah lnStatus ===
            if ($uTotal > 0 && $uCnt0 === 0 && $uCnt1 === $uTotal) {
                $sisa = max(0, $gCnt0); // item aktif global yang masih pending
                return $this->response->setJSON([
                    'success' => true,
                    'msg'     => 'Masih ada beberapa item LHUS yang belum diproses.',
                    'xname'   => csrf_token(),
                    'xhash'   => csrf_hash()
                ]);
            }

            // === RULE 3: Subset user masih pending -> GAGAL ===
            return $this->response->setJSON([
                'success' => false,
                'msg'     => 'Masih ada item LHUS yang belum diproses. Harap terima atau tolak semua item aktif terlebih dahulu.',
                'xname'   => csrf_token(),
                'xhash'   => csrf_hash()
            ]);

        }

        // --- aksi 'tolak' (opsional) ---
        if ($aksi === 'tolak') {
            $db->table('simlab_t_layanan')
                ->where('lnKode', $lnKode)
                ->update(['lnStatus' => 2]);

            return $this->response->setJSON([
                'success' => true,
                'msg'     => 'LN ditolak (lnStatus = 2).',
                'xname'   => csrf_token(),
                'xhash'   => csrf_hash()
            ]);
        }

        return $this->response->setJSON([
            'success' => false,
            'msg'     => 'Aksi tidak dikenali.',
            'xname'   => csrf_token(),
            'xhash'   => csrf_hash()
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

        // (opsional) tombol proses parent kalau diperlukan saat status 5
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
