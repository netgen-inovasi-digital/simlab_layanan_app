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

        // [ADDED] Parse lnStatus filter (?lnStatus=tolak,5,6,diproses)
        $lnStatusParam  = (string) ($this->request->getGet('lnStatus') ?? '');
        $wantReject     = false;          // token: tolak/reject/ditolak
        $wantReprocess  = false;          // token: diproses/reprocess
        $statusNums     = [];             // angka: 5/6/...
        if ($lnStatusParam !== '') {
            foreach (preg_split('/[,\s]+/', $lnStatusParam, -1, PREG_SPLIT_NO_EMPTY) as $p) {
                $tp = strtolower(trim($p));
                if (in_array($tp, ['tolak','reject','ditolak'], true)) {
                    $wantReject = true;
                    continue;
                }
                if (in_array($tp, ['diproses','reprocess','proseskembali'], true)) {
                    $wantReprocess = true;
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

        // 1) det aktif relevan dengan user (menggunakan tabel baru t_layanan_detil dan r_tim)
        $detRows = $db->table('t_layanan_detil as d')
            ->distinct()
            ->select('d.kode_layanan')
            ->join('r_tim rt', 'rt.uji_kode = d.uji_kode', 'inner')
            ->where('rt.user_id', $user_id)
            ->where('d.status_layanan', 1)
            ->get()->getResult();

        $lnKodeList = [];
        foreach ($detRows as $r) {
            $val = is_object($r) ? $r->kode_layanan : $r['kode_layanan'];
            if ($val) $lnKodeList[] = (int)$val;
        }
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
        // Menggunakan files status dari t_layanan_detil: 0=terkirim, 1=diterima, 2=ditolak, 3=terunggah
        $statusSummary = [];
        $rowsG = $db->table('t_layanan_detil')
            ->select("
                kode_layanan,
                COUNT(*) AS total,
                SUM(CASE WHEN files = 1 THEN 1 ELSE 0 END) AS cnt1,
                SUM(CASE WHEN files = 2 THEN 1 ELSE 0 END) AS cnt2,
                SUM(CASE WHEN files = 0 OR files = 3 THEN 1 ELSE 0 END) AS cnt0
            ", false)
            ->whereIn('kode_layanan', $lnKodeList)
            ->where('status_layanan', 1)
            ->groupBy('kode_layanan')
            ->get()->getResultArray();
        foreach ($rowsG as $sr) {
            $statusSummary[(int)$sr['kode_layanan']] = [
                'total' => (int)$sr['total'],
                'cnt1'  => (int)$sr['cnt1'],
                'cnt2'  => (int)$sr['cnt2'],
                'cnt0'  => (int)$sr['cnt0'],
            ];
        }

        $userSummary = [];
        $rowsU = $db->table('t_layanan_detil as d')
            ->select("
                d.kode_layanan,
                COUNT(*) AS total,
                SUM(CASE WHEN d.files = 1 THEN 1 ELSE 0 END) AS cnt1,
                SUM(CASE WHEN d.files = 2 THEN 1 ELSE 0 END) AS cnt2,
                SUM(CASE WHEN d.files = 0 OR d.files = 3 THEN 1 ELSE 0 END) AS cnt0
            ", false)
            ->join('r_tim rt', 'rt.uji_kode = d.uji_kode', 'inner')
            ->whereIn('d.kode_layanan', $lnKodeList)
            ->where('d.status_layanan', 1)
            ->where('rt.user_id', $user_id)
            ->groupBy('d.kode_layanan')
            ->get()->getResultArray();
        foreach ($rowsU as $sr) {
            $userSummary[(int)$sr['kode_layanan']] = [
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
            if ($wantReject || $wantReprocess || !empty($statusNums)) {
                $hasRejectForUser = (isset($userSummary[$lnKode]) && $userSummary[$lnKode]['cnt2'] > 0);
                $match = false;

                if ($wantReject && $hasRejectForUser) $match = true;
                if ($wantReprocess && (int)$derivedStatus === 2) $match = true;
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

        $db = \Config\Database::connect();
        
        // Query dengan struktur baru: t_layanan_detil + r_tim + t_files_lhus
        // Gunakan subquery untuk mengambil hanya 1 file terbaru per kode (yang terakhir diupload)
        $builder = $db->table('t_layanan_detil as d');
        $builder->select("
            d.kode,
            d.uji_kode,
            d.kode_layanan,
            d.nama_layanan,
            d.jumlah,
            d.biaya,
            d.catatan_pelanggan,
            d.catatan_manajer,
            d.files,
            d.status_layanan,
            lhus.file_lhus,
            lhus.catatan as ket_lhus,
            lhus.validasi_by
        ", false);
        
        $builder->join('r_tim rt', 'rt.uji_kode = d.uji_kode', 'inner');
        
        // Subquery untuk ambil hanya 1 file terbaru per kode
        // Jika ada multiple files untuk 1 kode, ambil yang file_id paling besar (terakhir diupload)
        $builder->join(
            '(SELECT lhus1.* FROM t_files_lhus lhus1 
              INNER JOIN (
                SELECT kode, MAX(file_id) as max_file_id 
                FROM t_files_lhus 
                GROUP BY kode
              ) lhus2 ON lhus1.kode = lhus2.kode AND lhus1.file_id = lhus2.max_file_id
            ) lhus', 
            'lhus.kode = d.kode', 
            'left'
        );
        
        $builder->where('d.kode_layanan', $lnKode);
        $builder->where('d.status_layanan', 1);
        $builder->where('rt.user_id', $user_id);
        $builder->orderBy('d.kode', 'ASC');

        try {
            $list = $builder->get()->getResult();
        } catch (\Throwable $e) {
            return $this->response->setJSON(['items' => [], 'error' => $e->getMessage()]);
        }

        $data = [];
        $no   = 1;

        foreach ($list as $row) {
            $layanan = $row->nama_layanan ?? '-';
            $jumlah = (int)($row->jumlah ?? 0);
            $ket    = $row->catatan_pelanggan ?: '-';

            // Deteksi file LHUS dari t_files_lhus
            $hasFile = false; $fileUrl = null;
            if (!empty($row->file_lhus)) {
                $raw = trim((string)$row->file_lhus);
                if (preg_match('/^https?:\/\//i', $raw)) {
                    $hasFile = true; 
                    $fileUrl = $raw;
                } else {
                    $possiblePath = FCPATH . 'uploads/lhus/' . ltrim($raw, '/');
                    if (is_file($possiblePath)) {
                        $hasFile = true;
                        $fileUrl = base_url('uploads/lhus/' . ltrim($raw, '/'));
                    }
                }
            }
            
            $lhusHtml = $hasFile && $fileUrl
                ? '<button type="button" class="btn btn-sm btn-outline-primary" onclick="window.open(\'' . esc($fileUrl) . '\', \'_blank\')"><i class="bi bi-eye"></i> Lihat</button>'
                : '<span class="text-muted">-</span>';

            $detKode    = (int)($row->kode ?? 0);
            // Status LHUS: 0=terkirim (pending review), 1=diterima, 2=ditolak, 3=terunggah (belum kirim)
            $statusLhus = isset($row->files) ? (int)$row->files : 0;
            $ketLhusVal = $row->ket_lhus ?? '';

            $textareaLhus =
                '<textarea id="detketlhus_' . $detKode . '" class="form-control detketlhus-input" '.
                'data-det="' . $detKode . '" data-statuslhus="' . $statusLhus . '" rows="2" placeholder="Keterangan LHUS..." '.
                'style="max-width:320px; min-width:220px; max-height:140px; resize:vertical; overflow:auto;">' .
                htmlspecialchars($ketLhusVal, ENT_QUOTES, 'UTF-8') .
                '</textarea>';

            // Status badge: 0/3=pending, 1=diterima, 2=ditolak
            if     ($statusLhus === 1) $statusBadge = '<span class="badge bg-success">LHUS Diterima</span>';
            elseif ($statusLhus === 2) $statusBadge = '<span class="badge bg-danger">LHUS Ditolak</span>';
            elseif ($statusLhus === 3) $statusBadge = '<span class="badge bg-info">Terunggah (Belum Kirim)</span>';
            elseif ($statusLhus === 0) $statusBadge = '<span class="badge bg-warning">Terkirim (Menunggu Review)</span>';
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
            'encLn' => bin2hex($this->encrypter->encrypt($lnKode)),
            'lnKode' => $lnKode
        ]);
    }

    // Simpan detKetLhus (JSON) - Update ke t_files_lhus.catatan
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
            
            // Update catatan di t_files_lhus (bukan di t_layanan_detil)
            $res = $db->table('t_files_lhus')
                ->where('kode', $detKode)
                ->update(['catatan' => $ket]);

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

    // Ambil user yang sedang login untuk dicatat sebagai validasi_by
    $session   = session();
    $accUserId = (int) ($session->get('id_user') ?? 0);
    if ($accUserId <= 0) {
        return $this->response->setJSON([
            'res'   => false,
            'msg'   => 'User login tidak ditemukan',
            'xname' => csrf_token(),
            'xhash' => csrf_hash()
        ]);
    }

    try {
        $db = \Config\Database::connect();
        $db->transStart();

        // Ambil LN parent dari detil ini (menggunakan t_layanan_detil baru)
        $detRow = $db->table('t_layanan_detil')
            ->select('kode_layanan')
            ->where('kode', $detKode)
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

        $lnKode = (int)$detRow->kode_layanan;

        // UPDATE status files di t_layanan_detil + catat siapa yang menilai di t_files_lhus
        $db->table('t_layanan_detil')
            ->where('kode', $detKode)
            ->update(['files' => $new]);

        // Update validasi_by di t_files_lhus
        $db->table('t_files_lhus')
            ->where('kode', $detKode)
            ->update([
                'status' => $new,
                'validasi_by' => $accUserId
            ]);

        // Auto set lnStatus = 6 jika semua det aktif sudah diterima (files = 1)
        $rowG = $db->query("
            SELECT 
                COUNT(*) AS total,
                SUM(CASE WHEN files = 1 THEN 1 ELSE 0 END) AS cnt1
            FROM t_layanan_detil
            WHERE kode_layanan = ? AND status_layanan = 1
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

                // Ringkasan global (det aktif) - menggunakan t_layanan_detil.files
                $rowG = $db->query("
                    SELECT 
                        COUNT(*) AS total,
                        SUM(CASE WHEN files = 1 THEN 1 ELSE 0 END) AS cnt1,
                        SUM(CASE WHEN files = 0 OR files = 3 THEN 1 ELSE 0 END) AS cnt0
                    FROM t_layanan_detil
                    WHERE kode_layanan = ? AND status_layanan = 1
                ", [$lnKode])->getRowArray();

                $gTotal = (int)($rowG['total'] ?? 0);
                $gCnt1  = (int)($rowG['cnt1']  ?? 0);
                $gCnt0  = (int)($rowG['cnt0']  ?? 0);

                // Ringkasan subset user (det aktif)
                $rowU = $db->table('t_layanan_detil as d')
                    ->select("
                        COUNT(*) AS total,
                        SUM(CASE WHEN d.files = 1 THEN 1 ELSE 0 END) AS cnt1,
                        SUM(CASE WHEN d.files = 0 OR d.files = 3 THEN 1 ELSE 0 END) AS cnt0
                    ", false)
                    ->join('r_tim rt', 'rt.uji_kode = d.uji_kode', 'inner')
                    ->where('d.kode_layanan', $lnKode)
                    ->where('d.status_layanan', 1)
                    ->where('rt.user_id', $user_id)
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

    public function getSampleIdentity($lnKode = null)
    {
        if (!$lnKode) {
            return $this->response->setJSON([
                'success' => false,
                'message' => 'Kode layanan tidak ditemukan'
            ]);
        }

        $session = session();
        $user_id = (int) ($session->get('id_user') ?? 0);

        // Cek akses user sebagai anggota tim untuk Ln ini via r_tim
        $db = \Config\Database::connect();
        $checkBuilder = $db->table('t_layanan_detil as d');
        $checkBuilder->select('1');
        $checkBuilder->join('r_tim as rt', 'rt.uji_kode = d.uji_kode', 'inner');
        $checkBuilder->where('d.kode_layanan', $lnKode);
        $checkBuilder->where('rt.user_id', $user_id);
        $exists = $checkBuilder->limit(1)->get()->getRow();

        if (!$exists) {
            return $this->response->setJSON([
                'success' => false,
                'message' => 'Anda tidak berwenang melihat data ini'
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
}
