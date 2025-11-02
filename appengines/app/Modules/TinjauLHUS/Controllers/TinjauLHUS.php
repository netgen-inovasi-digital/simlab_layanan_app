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

    // --------------------- dataList() ---------------------
    public function dataList()
    {
        $session = session();
        $user_id = (int)$session->get('id_user');

        // [ADDED] Parse lnStatus filter (?lnStatus=tolak,5,6)
        $lnStatusParam  = (string) ($this->request->getGet('lnStatus') ?? '');
        $wantReject     = false;          // token: tolak/reject/ditolak
        $statusNums     = [];             // angka: 5/6/...
        if ($lnStatusParam !== '') {
            foreach (preg_split('/[,\s]+/', $lnStatusParam, -1, PREG_SPLIT_NO_EMPTY) as $p) {
                $tp = strtolower(trim($p));
                if (in_array($tp, ['tolak','reject','ditolak'], true)) {
                    $wantReject = true;
                    continue;
                }
                if ($tp !== '' && is_numeric($tp)) {
                    $statusNums[] = (int)$tp;
                }
            }
            $statusNums = array_values(array_unique($statusNums));
        }
        // [ADDED] end

        $db   = \Config\Database::connect();
        $data = [];

        // 1) det aktif relevan dengan user
        $detRows = $db->table('simlab_t_layanan_detil as d')
            ->select('d.detLnKode')
            ->groupStart()
                ->where('d.detManajerTeknis', $user_id)
                ->orWhere('d.detPenyelia', $user_id)
            ->groupEnd()
            ->where('d.detStatus', 1)
            ->get()->getResult();

        $lnKodeList = [];
        foreach ($detRows as $r) $lnKodeList[] = (int)(is_object($r) ? $r->detLnKode : $r['detLnKode']);
        $lnKodeList = array_values(array_unique(array_filter($lnKodeList)));

        if (empty($lnKodeList)) {
            return $this->response->setJSON(['items' => []]);
        }

        // 2) Parent LN (>=5)
        $list = $db->table('simlab_t_layanan as l')
            ->select('l.*, u.user_name as pemesan_name, u.user_email as pemesan_email, u.user_identity as pemesan_identity')
            ->join('simlab_account_users as u', 'u.user_id = l.user_id', 'left')
            ->whereIn('l.lnKode', $lnKodeList)
            ->where('l.lnStatus >=', 5)
            ->orderBy('l.lnTgl', 'DESC')
            ->get()->getResult();

        // 3) Ringkasan global dan subset user (det aktif saja)
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

        foreach ($list as $row) {
            $lnKode = (int)$row->lnKode;

            // Hitung derivedStatus (tampil) — konsisten dengan view
            $derivedStatus = (int)$row->lnStatus;
            if (isset($userSummary[$lnKode]) && $userSummary[$lnKode]['total'] > 0) {
                $su = $userSummary[$lnKode];
                if ($su['cnt0'] === 0) {
                    if ($su['cnt1'] === $su['total'])      $derivedStatus = 6; // semua accept
                    elseif ($su['cnt2'] > 0)               $derivedStatus = 2; // ada tolak
                    else                                    $derivedStatus = 5; // selesai subset user tapi global belum tentu
                } else                                      $derivedStatus = 5; // masih pending
            } elseif (isset($statusSummary[$lnKode])) {
                $s = $statusSummary[$lnKode];
                if     ($s['cnt0'] > 0)                                     $derivedStatus = 5;
                elseif ($s['total'] > 0 && $s['cnt1'] === $s['total'])      $derivedStatus = 6;
                elseif ($s['cnt2'] > 0)                                     $derivedStatus = 2;
                else                                                         $derivedStatus = 5;
            }

            // [ADDED] Terapkan FILTER (?lnStatus=...)
            if ($wantReject || !empty($statusNums)) {
                $hasRejectForUser = (isset($userSummary[$lnKode]) && $userSummary[$lnKode]['cnt2'] > 0);
                $match = false;

                if ($wantReject && $hasRejectForUser) $match = true;
                if (!$match && !empty($statusNums) && in_array((int)$derivedStatus, $statusNums, true)) $match = true;

                if (!$match) continue; // tidak match filter → skip
            }
            // [ADDED] end

            // ===== JSON row (3 kolom) — cocok dengan header Pemesan, Status, LHUS =====
            $pemesanNama = $row->pemesan_name ?: ($row->lnOrangNama ?? '-');
            $tipe        = $row->pemesan_identity ?: ($row->lnOrangJenis ?? ($row->lnOrangTipe ?? '-'));
            $tanggal     = !empty($row->lnTgl) ? date('d-m-Y H:i', strtotime($row->lnTgl)) : '-';

            $combined = '
                <div style="line-height:1.3;">
                    <span style="font-size:1rem; font-weight:600;">' . esc($pemesanNama) . '</span><br>
                    <span style="font-size:0.9rem; color:#555;">' . esc($tanggal) . ' | ' . esc($tipe) . '</span>
                </div>';

            $actionBtn = '<a href="javascript:void(0)" onclick="loadDetail(\'' 
                       . bin2hex($this->encrypter->encrypt($row->lnKode)) 
                       . '\')" class="btn btn-sm btn-info"><i class="bi bi-gear"></i> Tinjau LHUS</a>';

            $data[] = [
                $combined,
                $this->formatStatus((int)$derivedStatus),
                $actionBtn,
            ];
        }

        return $this->response->setJSON(["items" => $data]);
    }

    // --------------------- detailList() ---------------------
    public function detailList($id = null)
    {
        if (empty($id)) return $this->response->setJSON(['items' => []]);

        // tolerant decrypt (hex/raw)
        try {
            $lnKode = $this->encrypter->decrypt(hex2bin($id));
        } catch (\Throwable $e) {
            try { $lnKode = $this->encrypter->decrypt($id); }
            catch (\Throwable $e2) {
                return $this->response->setJSON(['items' => [], 'error' => 'Invalid ID']);
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

            $isRelevant = false;
            if ((int)$row->detManajerTeknis === $user_id) $isRelevant = true;
            if ((int)$row->detPenyelia      === $user_id) $isRelevant = true;
            if (!$isRelevant) continue;

            $layanan = $row->ujiLayanan ?? '-';
            if (!empty($row->paraNama)) $layanan .= ' (' . $row->paraNama . ')';
            $jumlah = (int)($row->detJumlah ?? 0);
            $ket    = $row->detKeterangan ?: '-';

            // Deteksi file LHUS
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
            $lhusHtml = $hasFile && $fileUrl
                ? '<button type="button" class="btn btn-sm btn-outline-primary" onclick="window.open(\'' . esc($fileUrl) . '\', \'_blank\')"><i class="bi bi-eye"></i> Lihat</button>'
                : '<span class="text-muted">-</span>';

            $detKode    = (int)($row->detKode ?? 0);
            $statusLhus = isset($row->detStatusLHUS) ? (int)$row->detStatusLHUS : 0; // 0 pending, 1 diterima, 2 ditolak
            $ketLhusVal = $row->detKetLhus ?? '';

            $textareaLhus =
                '<textarea id="detketlhus_' . $detKode . '" class="form-control detketlhus-input" '.
                'data-det="' . $detKode . '" data-statuslhus="' . $statusLhus . '" rows="2" placeholder="Keterangan LHUS..." '.
                'style="max-width:320px; min-width:220px; max-height:140px; resize:vertical; overflow:auto;">' .
                htmlspecialchars($ketLhusVal, ENT_QUOTES, 'UTF-8') .
                '</textarea>';

            if     ($statusLhus === 1) $statusBadge = '<span class="badge bg-success">LHUS Diterima</span>';
            elseif ($statusLhus === 2) $statusBadge = '<span class="badge bg-danger">LHUS Ditolak</span>';
            else                       $statusBadge = '<span class="badge bg-secondary">Belum Diproses</span>';

            $canAccept = ($statusLhus !== 1);
            $canReject = ($statusLhus !== 2);

            $aksiHtml  = '<div class="d-flex justify-content-center gap-2 align-items-center">';
            $aksiHtml .= '<span class="text-success btn-action btn-accept-lhus" title="Terima LHUS" data-det="' . $detKode . '"' . ($canAccept ? '' : ' style="opacity:.5;pointer-events:none;"') . '><i class="bi bi-check-circle"></i></span>';
            $aksiHtml .= '<span class="text-warning btn-action btn-reject-lhus" title="Tolak LHUS"  data-det="' . $detKode . '"' . ($canReject ? '' : ' style="opacity:.5;pointer-events:none;"') . '><i class="bi bi-x-circle"></i></span>';
            $aksiHtml .= '</div>';

            // ===== JSON row (8 kolom) — match header modal =====
            $data[] = [
                $no++,
                $layanan,
                $jumlah,
                $ket,
                $statusBadge,
                $lhusHtml,
                $textareaLhus,
                $aksiHtml
            ];
        }

        return $this->response->setJSON([
            'items' => $data,
            'encLn' => bin2hex($this->encrypter->encrypt($lnKode))
        ]);
    }

    // Simpan detKetLhus (JSON)
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

    // Proses detil (POST form-data): 'terima'->1, 'tolak'->2
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
            $db = \Config\Database::connect();
            $db->transStart();

            // Ambil LN parent dari detil ini
            $detRow = $db->table('simlab_t_layanan_detil')
                ->select('detLnKode')
                ->where('detKode', $detKode)
                ->get()->getRow();

            if (!$detRow) {
                $db->transComplete();
                return $this->response->setJSON([
                    'res'   => false,
                    'msg'   => 'Detil tidak ditemukan',
                    'xname' => csrf_token(),
                    'xhash' => csrf_hash()
                ]);
            }

            $lnKode = (int)$detRow->detLnKode;

            // Update status detil
            $db->table('simlab_t_layanan_detil')
                ->where('detKode', $detKode)
                ->update(['detStatusLHUS' => $new]);

            // Auto set lnStatus = 6 jika SEMUA det aktif sudah detStatusLHUS = 1
            $rowG = $db->query("
                SELECT 
                    COUNT(*) AS total,
                    SUM(CASE WHEN detStatusLHUS = 1 THEN 1 ELSE 0 END) AS cnt1
                FROM simlab_t_layanan_detil
                WHERE detLnKode = ? AND detStatus = 1
            ", [$lnKode])->getRowArray();

            $gTotal = (int)($rowG['total'] ?? 0);
            $gCnt1  = (int)($rowG['cnt1']  ?? 0);

            if ($gTotal > 0 && $gCnt1 === $gTotal) {
                $db->table('simlab_t_layanan')
                   ->where('lnKode', $lnKode)
                   ->update(['lnStatus' => 6]);
            }

            $db->transComplete();

            $ok = $db->transStatus();

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

    // Proses parent LN (terima/tolak)
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

        // decrypt tolerant
        try {
            $lnKode = $this->encrypter->decrypt(hex2bin($idEnc));
        } catch (\Throwable $e) {
            try { $lnKode = $this->encrypter->decrypt($idEnc); }
            catch (\Throwable $e2) { $default['msg'] = 'ID tidak valid'; return $this->response->setJSON($default); }
        }

        $map = ['terima' => 5, 'tolak' => 2];
        if (!isset($map[$aksi])) {
            $default['msg'] = 'Aksi tidak dikenali';
            return $this->response->setJSON($default);
        }

        try {
            $db = \Config\Database::connect();

            if ($aksi === 'terima') {
                $session = session();
                $user_id = (int) $session->get('id_user');

                // Ringkasan global (det aktif)
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

                // Ringkasan subset user (det aktif)
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

                // Global semua diterima -> lnStatus = 6
                if ($gTotal > 0 && $gCnt1 === $gTotal) {
                    $db->table('simlab_t_layanan')->where('lnKode', $lnKode)->update(['lnStatus' => 6]);
                    return $this->response->setJSON([
                        'success' => true,
                        'msg'     => 'Berhasil. Semua layanan aktif telah diterima. LHUS disetujui (lnStatus = 6) dan dikirim ke admin.',
                        'xname'   => csrf_token(),
                        'xhash'   => csrf_hash()
                    ]);
                }

                // Parsial selesai pada subset user (tidak ubah lnStatus)
                if ($uTotal > 0 && $uCnt0 === 0 && $uCnt1 === $uTotal) {
                    return $this->response->setJSON([
                        'success' => true,
                        'msg'     => 'Masih ada beberapa item LHUS yang belum diproses.',
                        'xname'   => csrf_token(),
                        'xhash'   => csrf_hash()
                    ]);
                }

                return $this->response->setJSON([
                    'success' => false,
                    'msg'     => 'Masih ada item LHUS yang belum diproses. Harap terima atau tolak semua item aktif terlebih dahulu.',
                    'xname'   => csrf_token(),
                    'xhash'   => csrf_hash()
                ]);
            }

            // Tolak parent
            if ($aksi === 'tolak') {
                $db->table('simlab_t_layanan')->where('lnKode', $lnKode)->update(['lnStatus' => 2]);
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

    private function formatStatus($status)
    {
        switch ($status) {
            case 5: return '<span class="badge bg-warning">LHUS belum ditinjau</span>';
            case 6: return '<span class="badge bg-success">LHUS Disetujui</span>';
            case 7: return '<span class="badge bg-primary">Memproses LHU</span>';
            case 8: return '<span class="badge bg-success">LHU Disetujui</span>';
            case 9: return '<span class="badge bg-dark">Pengujian Selesai</span>';
            case 2: return '<span class="badge bg-info">LHUS diproses kembali</span>';
            default: return '<span class="badge bg-secondary">Unknown</span>';
        }
    }
}
