<?php

namespace Modules\TinjauLHUS\Controllers;

use App\Controllers\BaseController;
use App\Models\MyModel;
use Modules\TinjauLHUS\Models\TinjauLhusModel;

class TinjauLHUS extends BaseController
{
    private $table = 'simlab_t_layanan';
    private $id = 'lnKode';
    private $tinjauLhusModel;

    public function __construct()
    {
        $this->encrypter = \Config\Services::encrypter();
        $this->tinjauLhusModel = new TinjauLhusModel();
        helper('form');
    }

    public function index()
    {
        $session = session();
        $user_id = $session->get('id_user');

        $modelUser = new MyModel('simlab_account_users');

        $data = [
            'title' => 'Tinjau LHUS',
            'user' => $modelUser->getDataById('user_id', $user_id),
        ];

        return view('Modules\TinjauLHUS\Views\v_tinjauLhus', $data);
    }

    // --------------------- dataList() ---------------------
    public function dataList()
    {
        $session = session();
        $user_id = (int) $session->get('id_user');

        // [ADDED] Parse lnStatus filter (?lnStatus=tolak,5,6,diproses)
        $lnStatusParam = (string) ($this->request->getGet('lnStatus') ?? '');
        $wantReject = false;          // token: tolak/reject/ditolak
        $wantReprocess = false;          // token: diproses/reprocess
        $statusNums = [];             // angka: 5/6/...
        if ($lnStatusParam !== '') {
            foreach (preg_split('/[,\s]+/', $lnStatusParam, -1, PREG_SPLIT_NO_EMPTY) as $p) {
                $tp = strtolower(trim($p));
                if (in_array($tp, ['tolak', 'reject', 'ditolak'], true)) {
                    $wantReject = true;
                    continue;
                }
                if (in_array($tp, ['diproses', 'reprocess', 'proseskembali'], true)) {
                    $wantReprocess = true;
                    continue;
                }
                if ($tp !== '' && is_numeric($tp)) {
                    $statusNums[] = (int) $tp;
                }
            }
            $statusNums = array_values(array_unique($statusNums));
        }
        // [ADDED] end

        $data = [];

        // 1) Get active layanan codes for user
        $lnKodeList = $this->tinjauLhusModel->getActiveLayananCodesByUser($user_id);

        if (empty($lnKodeList)) {
            return $this->response->setJSON(['items' => []]);
        }

        // 2) Get parent layanan list
        $list = $this->tinjauLhusModel->getLayananListByKodes($lnKodeList);

        // 3) Get global and user status summaries
        $statusSummary = $this->tinjauLhusModel->getGlobalStatusSummary($lnKodeList);
        $userSummary = $this->tinjauLhusModel->getUserStatusSummary($lnKodeList, $user_id);

        foreach ($list as $row) {
            $lnKode = (int) $row->lnKode;

            // Hitung derivedStatus (tampil) — konsisten dengan view
            $derivedStatus = (int) $row->lnStatus;
            if (isset($userSummary[$lnKode]) && $userSummary[$lnKode]['total'] > 0) {
                $su = $userSummary[$lnKode];
                if ($su['cnt0'] === 0) {
                    if ($su['cnt1'] === $su['total'])
                        $derivedStatus = 6; // semua accept
                    elseif ($su['cnt2'] > 0)
                        $derivedStatus = 2; // ada tolak
                    else
                        $derivedStatus = 5; // selesai subset user tapi global belum tentu
                } else
                    $derivedStatus = 5; // masih pending
            } elseif (isset($statusSummary[$lnKode])) {
                $s = $statusSummary[$lnKode];
                if ($s['cnt0'] > 0)
                    $derivedStatus = 5;
                elseif ($s['total'] > 0 && $s['cnt1'] === $s['total'])
                    $derivedStatus = 6;
                elseif ($s['cnt2'] > 0)
                    $derivedStatus = 2;
                else
                    $derivedStatus = 5;
            }

            // [ADDED] Terapkan FILTER (?lnStatus=...)
            if ($wantReject || $wantReprocess || !empty($statusNums)) {
                $hasRejectForUser = (isset($userSummary[$lnKode]) && $userSummary[$lnKode]['cnt2'] > 0);
                $match = false;

                if ($wantReject && $hasRejectForUser)
                    $match = true;
                if ($wantReprocess && (int) $derivedStatus === 2)
                    $match = true;
                if (!$match && !empty($statusNums) && in_array((int) $derivedStatus, $statusNums, true))
                    $match = true;

                if (!$match)
                    continue; // tidak match filter → skip
            }
            // [ADDED] end

            // ===== JSON row (3 kolom) — cocok dengan header Pemesan, Status, LHUS =====
            $pemesanNama = $row->pemesan_name ?: ($row->lnOrangNama ?? '-');
            $tipe = $row->pemesan_identity ?: ($row->lnOrangJenis ?? ($row->lnOrangTipe ?? '-'));
            $tanggal = !empty($row->lnTgl) ? date('d-m-Y H:i', strtotime($row->lnTgl)) : '-';

            // Badge Uji Ulang
            $badge = '';
            if ((int) ($row->jumlah_kaji_ulang ?? 0) > 0) {
                $badge = '<span class="badge bg-danger text-white ms-1" title="Data pengujian ulang">Uji Ulang</span>';
            }

            $combined = '
                <div style="line-height:1.3;">
                    <span style="font-size:1rem; font-weight:600;">' . esc($pemesanNama) . '</span><br>
                    <span style="font-size:0.9rem; color:#555;">' . esc($tanggal) . ' | ' . esc($tipe) . '</span>' . $badge . '
                </div>';

            $actionBtn = '<a href="javascript:void(0)" onclick="loadDetail(\''
                . bin2hex($this->encrypter->encrypt($row->lnKode))
                . '\')" class="btn btn-sm btn-info"><i class="bi bi-gear"></i> Tinjau LHUS</a>';

            $data[] = [
                $combined,
                $this->formatStatus((int) $derivedStatus),
                $actionBtn,
            ];
        }

        return $this->response->setJSON(["items" => $data]);
    }

    // --------------------- detailList() ---------------------
    public function detailList($id = null)
    {
        if (empty($id))
            return $this->response->setJSON(['items' => []]);

        // tolerant decrypt (hex/raw)
        try {
            $lnKode = $this->encrypter->decrypt(hex2bin($id));
        } catch (\Throwable $e) {
            try {
                $lnKode = $this->encrypter->decrypt($id);
            } catch (\Throwable $e2) {
                return $this->response->setJSON(['items' => [], 'error' => 'Invalid ID']);
            }
        }

        $session = session();
        $user_id = (int) $session->get('id_user');

        try {
            $list = $this->tinjauLhusModel->getDetailListByLayananAndUser($lnKode, $user_id);
        } catch (\Throwable $e) {
            return $this->response->setJSON(['items' => [], 'error' => $e->getMessage()]);
        }

        $data = [];
        $no = 1;

        foreach ($list as $row) {
            $layanan = $row->nama_layanan ?? '-';
            $jumlah = (int) ($row->jumlah ?? 0);

            // Deteksi file LHUS dari t_files_lhus
            $hasFile = false;
            $fileUrl = null;
            if (!empty($row->file_lhus)) {
                $raw = trim((string) $row->file_lhus);
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

            $detKode = (int) ($row->kode ?? 0);
            // Status LHUS: 0=terkirim (pending review), 1=diterima, 2=ditolak, 3=terunggah (belum kirim)
            $statusLhus = isset($row->files) ? (int) $row->files : 0;
            $ketLhusVal = $row->ket_lhus ?? '';

            $textareaLhus =
                '<textarea id="detketlhus_' . $detKode . '" class="form-control detketlhus-input" ' .
                'data-det="' . $detKode . '" data-statuslhus="' . $statusLhus . '" rows="2" placeholder="Keterangan LHUS..." ' .
                'style="max-width:320px; min-width:220px; max-height:140px; resize:vertical; overflow:auto;">' .
                htmlspecialchars($ketLhusVal, ENT_QUOTES, 'UTF-8') .
                '</textarea>';

            // Status badge: 0/3=pending, 1=diterima, 2=ditolak
            if ($statusLhus === 1)
                $statusBadge = '<span class="badge bg-success">LHUS Diterima</span>';
            elseif ($statusLhus === 2)
                $statusBadge = '<span class="badge bg-danger">LHUS Ditolak</span>';
            elseif ($statusLhus === 3)
                $statusBadge = '<span class="badge bg-info">Terunggah (Belum Kirim)</span>';
            elseif ($statusLhus === 0)
                $statusBadge = '<span class="badge bg-warning">Terkirim (Menunggu Review)</span>';
            else
                $statusBadge = '<span class="badge bg-secondary">Belum Diproses</span>';

            $canAccept = ($statusLhus !== 1);
            $canReject = ($statusLhus !== 2);

            $aksiHtml = '<div class="d-flex justify-content-center gap-2 align-items-center">';
            $aksiHtml .= '<span class="text-success btn-action btn-accept-lhus" title="Terima LHUS" data-det="' . $detKode . '"' . ($canAccept ? '' : ' style="opacity:.5;pointer-events:none;"') . '><i class="bi bi-check-circle"></i></span>';
            $aksiHtml .= '<span class="text-warning btn-action btn-reject-lhus" title="Tolak LHUS"  data-det="' . $detKode . '"' . ($canReject ? '' : ' style="opacity:.5;pointer-events:none;"') . '><i class="bi bi-x-circle"></i></span>';
            $aksiHtml .= '</div>';

            // ===== JSON row (8 kolom) — match header modal =====
            $data[] = [
                $no++,
                $layanan,
                $jumlah,
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

        $detKode = (int) $input['detKode'];
        $ket = $input['ket'] ?? null;

        try {
            $ok = $this->tinjauLhusModel->updateFileLhusCatatan($detKode, $ket);
            
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

    // Proses detil (POST form-data): 'terima'->1, 'tolak'->2
    public function prosesDetailLhus()
    {
        $detKode = $this->request->getPost('detKode');
        $aksi = $this->request->getPost('aksi');

        if (empty($detKode) || empty($aksi)) {
            return $this->response->setJSON([
                'res' => false,
                'msg' => 'Parameter tidak lengkap',
                'xname' => csrf_token(),
                'xhash' => csrf_hash()
            ]);
        }

        $detKode = (int) $detKode;
        $map = ['terima' => 1, 'tolak' => 2];
        if (!isset($map[$aksi])) {
            return $this->response->setJSON([
                'res' => false,
                'msg' => 'Aksi tidak valid',
                'xname' => csrf_token(),
                'xhash' => csrf_hash()
            ]);
        }
        $new = $map[$aksi];

        // Ambil user yang sedang login untuk dicatat sebagai validasi_by
        $session = session();
        $accUserId = (int) ($session->get('id_user') ?? 0);
        if ($accUserId <= 0) {
            return $this->response->setJSON([
                'res' => false,
                'msg' => 'User login tidak ditemukan',
                'xname' => csrf_token(),
                'xhash' => csrf_hash()
            ]);
        }

        try {
            $result = $this->tinjauLhusModel->processDetailLhusTransaction($detKode, $new, $accUserId);

            if (!$result['success']) {
                return $this->response->setJSON([
                    'res' => false,
                    'msg' => $result['error'] ?? 'Tidak ada perubahan',
                    'xname' => csrf_token(),
                    'xhash' => csrf_hash()
                ]);
            }

            return $this->response->setJSON([
                'res' => true,
                'msg' => 'Status LHUS diperbarui',
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


    // Proses parent LN (terima/tolak)
    public function proses($idEnc = null, $aksi = null)
    {
        $default = [
            'success' => false,
            'msg' => 'Invalid request',
            'xname' => csrf_token(),
            'xhash' => csrf_hash()
        ];

        if (empty($idEnc) || empty($aksi)) {
            $default['msg'] = 'ID atau aksi tidak ditemukan';
            return $this->response->setJSON($default);
        }

        // decrypt tolerant
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

        $map = ['terima' => 5, 'tolak' => 2];
        if (!isset($map[$aksi])) {
            $default['msg'] = 'Aksi tidak dikenali';
            return $this->response->setJSON($default);
        }

        try {
            if ($aksi === 'terima') {
                $session = session();
                $user_id = (int) $session->get('id_user');

                // Get global and user status summaries
                $rowG = $this->tinjauLhusModel->getGlobalStatusSummaryForLayanan($lnKode);
                $rowU = $this->tinjauLhusModel->getUserStatusSummaryForLayanan($lnKode, $user_id);

                $gTotal = (int) ($rowG['total'] ?? 0);
                $gCnt1 = (int) ($rowG['cnt1'] ?? 0);
                $gCnt0 = (int) ($rowG['cnt0'] ?? 0);

                $uTotal = (int) ($rowU['total'] ?? 0);
                $uCnt1 = (int) ($rowU['cnt1'] ?? 0);
                $uCnt0 = (int) ($rowU['cnt0'] ?? 0);

                // Global semua diterima -> lnStatus = 6
                if ($gTotal > 0 && $gCnt1 === $gTotal) {
                    $this->tinjauLhusModel->updateLayananStatus($lnKode, 6);

                    // Update log sampel
                    $currentTime = date('Y-m-d H:i:s');
                    $this->tinjauLhusModel->updateLogSampelLhus($lnKode, $currentTime);

                    return $this->response->setJSON([
                        'success' => true,
                        'msg' => 'Berhasil. Semua layanan aktif telah diterima. LHUS disetujui (lnStatus = 6) dan dikirim ke admin.',
                        'xname' => csrf_token(),
                        'xhash' => csrf_hash()
                    ]);
                }

                // Parsial selesai pada subset user (tidak ubah lnStatus)
                if ($uTotal > 0 && $uCnt0 === 0 && $uCnt1 === $uTotal) {
                    return $this->response->setJSON([
                        'success' => true,
                        'msg' => 'Masih ada beberapa item LHUS yang belum diproses.',
                        'xname' => csrf_token(),
                        'xhash' => csrf_hash()
                    ]);
                }

                return $this->response->setJSON([
                    'success' => false,
                    'msg' => 'Masih ada item LHUS yang belum diproses. Harap terima atau tolak semua item aktif terlebih dahulu.',
                    'xname' => csrf_token(),
                    'xhash' => csrf_hash()
                ]);
            }

            // Tolak parent
            if ($aksi === 'tolak') {
                $this->tinjauLhusModel->updateLayananStatus($lnKode, 2);
                return $this->response->setJSON([
                    'success' => true,
                    'msg' => 'LN ditolak (lnStatus = 2).',
                    'xname' => csrf_token(),
                    'xhash' => csrf_hash()
                ]);
            }

            return $this->response->setJSON([
                'success' => false,
                'msg' => 'Aksi tidak dikenali.',
                'xname' => csrf_token(),
                'xhash' => csrf_hash()
            ]);
        } catch (\Throwable $e) {
            return $this->response->setJSON([
                'success' => false,
                'msg' => 'Terjadi error: ' . $e->getMessage(),
                'xname' => csrf_token(),
                'xhash' => csrf_hash()
            ]);
        }
    }

    private function formatStatus($status)
    {
        switch ($status) {
            case 5:
                return '<span class="badge bg-warning">LHUS belum ditinjau</span>';
            case 6:
                return '<span class="badge bg-success">LHUS Disetujui</span>';
            case 7:
                return '<span class="badge bg-primary">Memproses LHU</span>';
            case 8:
                return '<span class="badge bg-success">LHU Disetujui</span>';
            case 9:
                return '<span class="badge bg-dark">Pengujian Selesai</span>';
            case 2:
                return '<span class="badge bg-info">LHUS diproses kembali</span>';
            default:
                return '<span class="badge bg-secondary">Unknown</span>';
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

        // Check user access via model
        $hasAccess = $this->tinjauLhusModel->checkUserAccessToLayanan($lnKode, $user_id);

        if (!$hasAccess) {
            return $this->response->setJSON([
                'success' => false,
                'message' => 'Anda tidak berwenang melihat data ini'
            ]);
        }

        try {
            $sampleData = $this->tinjauLhusModel->getSampleIdentityByLayanan($lnKode);

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
