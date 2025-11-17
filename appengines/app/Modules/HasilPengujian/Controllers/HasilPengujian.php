<?php

namespace Modules\HasilPengujian\Controllers;

use App\Controllers\BaseController;
use App\Models\MyModel;

class HasilPengujian extends BaseController
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
            'title' => 'Data Hasil Pengujian',
            'user'  => $modelUser->getDataById('user_id', $user_id),
        ];

        return view('Modules\HasilPengujian\Views\v_hasilPengujian', $data);
    }

    public function dataList()
    {
        $session = session();
        $user_id = (int) ($session->get('id_user') ?? 0);

        $model = new MyModel($this->table);

        // --- Parse lnStatus filter: "4", "4,5,6", atau token khusus "tolak" dan "terunggah"
        $lnStatusParam = (string) ($this->request->getGet('lnStatus') ?? '');
        $lnStatusFilter = [];
        $wantReject = false;
        $wantUploaded = false;
        if ($lnStatusParam !== '') {
            foreach (preg_split('/[,\s]+/', $lnStatusParam, -1, PREG_SPLIT_NO_EMPTY) as $p) {
                $tp = strtolower(trim($p));
                if (in_array($tp, ['tolak','reject','ditolak'], true)) {
                    $wantReject = true;
                    continue;
                }
                if (in_array($tp, ['terunggah','uploaded'], true)) {
                    $wantUploaded = true;
                    continue;
                }
                if ($tp !== '' && is_numeric($tp)) {
                    $lnStatusFilter[] = (int) $tp;
                }
            }
            $lnStatusFilter = array_values(array_unique($lnStatusFilter));
        }

        $db = \Config\Database::connect();

        // --- DAPATKAN lnKode yang berkaitan dengan user melalui r_tim (mengikuti konsep yang diberikan)
        $builderLn = $db->table('t_layanan_detil as d');
        $builderLn->select('DISTINCT d.kode_layanan AS kode_layanan', false);
        $builderLn->join('r_tim rt', 'rt.uji_kode = d.uji_kode', 'inner');
        $builderLn->where('rt.user_id', $user_id);
        // hanya ambil detil aktif (status_layanan = 1) supaya relevan (sejalan implementasi sebelumnya)
        $builderLn->where('d.status_layanan', 1);
        $detilList = $builderLn->get()->getResult();

        $lnKodeList = [];
        foreach ($detilList as $item) {
            if (is_object($item) && isset($item->kode_layanan)) $lnKodeList[] = $item->kode_layanan;
            elseif (is_array($item) && isset($item['kode_layanan'])) $lnKodeList[] = $item['kode_layanan'];
        }
        $lnKodeList = array_values(array_unique(array_filter($lnKodeList)));

        if (empty($lnKodeList)) {
            return $this->response->setJSON(["items" => []]);
        }

        // --- Agregasi status user (sinkron dgn formatStatusForPenyelia) menggunakan r_tim concept
        $aggSql = $db->table('t_layanan_detil d')
            ->select("
                d.kode_layanan,
                MAX(CASE WHEN d.status_layanan=1 AND EXISTS(
                    SELECT 1 FROM r_tim rt2
                    WHERE rt2.uji_kode = d.uji_kode
                      AND rt2.user_id = {$user_id}
                ) AND d.files = 2 THEN 1 ELSE 0 END) AS has_reject_for_user,
                SUM(CASE WHEN d.status_layanan=1 AND EXISTS(
                    SELECT 1 FROM r_tim rt2
                    WHERE rt2.uji_kode = d.uji_kode
                      AND rt2.user_id = {$user_id}
                ) THEN 1 ELSE 0 END) AS user_active_total,
                SUM(CASE WHEN d.status_layanan=1 AND EXISTS(
                    SELECT 1 FROM r_tim rt2
                    WHERE rt2.uji_kode = d.uji_kode
                      AND rt2.user_id = {$user_id}
                ) AND d.files = 1 THEN 1 ELSE 0 END) AS user_accepted_total,
                SUM(CASE WHEN d.status_layanan=1 AND EXISTS(
                    SELECT 1 FROM r_tim rt2
                    WHERE rt2.uji_kode = d.uji_kode
                      AND rt2.user_id = {$user_id}
                ) AND d.files = 0 THEN 1 ELSE 0 END) AS user_sent_total,
                SUM(CASE WHEN d.status_layanan=1 AND EXISTS(
                    SELECT 1 FROM r_tim rt2
                    WHERE rt2.uji_kode = d.uji_kode
                      AND rt2.user_id = {$user_id}
                ) AND d.files = 3 THEN 1 ELSE 0 END) AS user_uploaded_total,
                SUM(CASE WHEN d.status_layanan=1 AND EXISTS(
                    SELECT 1 FROM r_tim rt2
                    WHERE rt2.uji_kode = d.uji_kode
                      AND rt2.user_id = {$user_id}
                ) AND (d.files IS NULL) THEN 1 ELSE 0 END) AS pending_for_user
            ", false)
            ->groupBy('d.kode_layanan')
            ->getCompiledSelect(false);

        $b  = $db->table('simlab_t_layanan as l');
        $b->select('l.*');
        $b->join("({$aggSql}) agg", 'agg.kode_layanan = l.lnKode', 'left');

        // Info pemesan (prefer simlab_account_users)
        $b->select('u.user_name as pemesan_name, u.user_email as pemesan_email, u.user_identity as pemesan_identity');
        $b->join('simlab_account_users as u', 'u.user_id = l.user_id', 'left');

        $b->whereIn('l.lnKode', $lnKodeList);
        $b->where('l.lnStatus !=', 2);                // exclude Ditolak awal
        $b->orderBy('l.lnTgl', 'DESC');

        // =======================
        // FILTER “VIEW STATUS” = algoritma formatStatusForPenyelia (+ 'tolak')
        // =======================
        if ($wantReject || $wantUploaded || !empty($lnStatusFilter)) {
            $b->groupStart();

            if ($wantReject) {
                $b->orGroupStart()
                    ->where('COALESCE(agg.has_reject_for_user,0) =', 1)
                ->groupEnd();
            }

            if ($wantUploaded) {
                // Kondisi: ada item yang files=3 (terunggah belum dikirim)
                $b->orGroupStart()
                    ->where('COALESCE(agg.has_reject_for_user,0) =', 0)
                    ->where('COALESCE(agg.user_uploaded_total,0) >', 0)
                ->groupEnd();
            }

            foreach ($lnStatusFilter as $s) {
                switch ((int)$s) {
                    case 4: // "Sedang dalam pengujian"
                        $b->orGroupStart()
                            ->where('COALESCE(agg.has_reject_for_user,0) =', 0)
                            ->where('COALESCE(agg.pending_for_user,0) >', 0)
                            ->where('l.lnStatus', 4)
                          ->groupEnd();
                        break;

                    case 5: // "LHUS sedang diverifikasi manajer (terkirim)"
                        // Kondisi: ada item yang files=0 (terkirim) dan tidak ada yang ditolak
                        // dan tidak semua sudah diterima (accepted)
                        $b->orGroupStart()
                            ->where('COALESCE(agg.has_reject_for_user,0) =', 0)
                            ->where('COALESCE(agg.user_sent_total,0) >', 0)
                            ->groupStart()
                                ->where('COALESCE(agg.user_accepted_total,0) < COALESCE(agg.user_active_total,0)', null, false)
                                ->orWhere('COALESCE(agg.user_active_total,0) =', 0)
                            ->groupEnd()
                          ->groupEnd();
                        break;

                    case 6: // "LHUS diterima"
                        $b->orGroupStart()
                            ->where('COALESCE(agg.has_reject_for_user,0) =', 0)
                            ->where('COALESCE(agg.pending_for_user,0) =', 0)
                            ->where('COALESCE(agg.user_active_total,0) >', 0)
                            ->where('COALESCE(agg.user_accepted_total,0) = COALESCE(agg.user_active_total,0)', null, false)
                          ->groupEnd();
                        break;

                    default:
                        $b->orGroupStart()
                            ->where('l.lnStatus', (int)$s)
                            ->where('COALESCE(agg.has_reject_for_user,0) =', 0)
                            ->groupStart()
                                ->where('COALESCE(agg.pending_for_user,0) >', 0)
                                ->orWhere('COALESCE(agg.user_active_total,0) =', 0)
                            ->groupEnd()
                          ->groupEnd();
                        break;
                }
            }

            $b->groupEnd();
        }

        // Eksekusi & bentuk response (kolom tetap)
        $list = $b->get()->getResult();

        $data = [];
        foreach ($list as $row) {
            if ((int)$row->lnStatus < 4) continue;

            $id = bin2hex(service('encrypter')->encrypt($row->lnKode));
            $pemesanNama = !empty($row->pemesan_name) ? $row->pemesan_name : '-';
            $tipe        = !empty($row->pemesan_identity) ? $row->pemesan_identity : '-';
            $tanggal     = !empty($row->lnTgl) ? date('d-m-Y H:i', strtotime($row->lnTgl)) : '-';

            $colA = '
                <div style="line-height:1.3;">
                    <span style="font-size:1rem; font-weight:600;">' . esc($pemesanNama) . '</span><br>
                    <span style="font-size:0.9rem; color:#555;">' . esc($tanggal) . ' | ' . esc($tipe) . '</span>
                </div>';

            $colB = $this->formatStatusForPenyelia($row->lnStatus, $row->lnKode, $user_id);

            $btn  = '<button type="button" class="btn btn-sm btn-info" title="Lihat Detail Item Layanan" onclick="loadDetail(\'' . $id . '\')">'
                  . '<i class="bi bi-upload"></i>Unggah LHUS</button>';

            $data[] = [$colA, $colB, $btn];
        }

        return $this->response->setJSON(["items" => $data]);
    }

    public function detailList($id = null)
    {
        if (!$id) {
            return $this->response->setJSON(['items' => []]);
        }

        $session = session();
        $user_id = (int) ($session->get('id_user') ?? 0);

        try {
            if (preg_match('/^[0-9a-f]+$/i', $id)) {
                $kode = service('encrypter')->decrypt(hex2bin($id));
            } else {
                $kode = service('encrypter')->decrypt($id);
            }
        } catch (\Exception $e) {
            return $this->response->setJSON(['items' => []]);
        }

        // cek akses user sebagai anggota tim untuk Ln ini via r_tim
        $db = \Config\Database::connect();
        $checkBuilder = $db->table('t_layanan_detil as d');
        $checkBuilder->select('1');
        $checkBuilder->join('r_tim as rt', 'rt.uji_kode = d.uji_kode', 'inner');
        $checkBuilder->where('d.kode_layanan', $kode);
        $checkBuilder->where('rt.user_id', $user_id);
        $checkBuilder->where('d.status_layanan', 1);
        $exists = $checkBuilder->limit(1)->get()->getRow();

        if (!$exists) {
            return $this->response->setJSON(['items' => []]);
        }

        // Encrypted ln
        $encLnId = bin2hex(service('encrypter')->encrypt($kode));

        $builder = $db->table('t_layanan_detil as d');

        $builder->select("
            d.kode,
            d.uji_kode,
            d.kode_layanan,
            d.nama_layanan,
            d.kode_jenis,
            GROUP_CONCAT(DISTINCT d.catatan_pelanggan SEPARATOR ' | ') AS detKet,
            GROUP_CONCAT(DISTINCT d.catatan_manajer SEPARATOR ' | ') AS detKetManajar,
            GROUP_CONCAT(DISTINCT d.catatan_manajer SEPARATOR ' | ') AS detKetLhus, -- fallback reuse column
            GROUP_CONCAT(DISTINCT d.files SEPARATOR ',') AS detFilesList,
            MAX(d.files) AS detFilesMax,
            SUM(d.jumlah) AS jumlah,
            SUM(d.biaya) AS detBiaya,
            MAX(d.status_layanan) AS status_group,
            d.terima_layanan_by,
            u.user_name AS acc_by
        ");
        // >>> join untuk ambil username approver (terima_layanan_by)
        $builder->join('simlab_account_users u', 'u.user_id = d.terima_layanan_by', 'left');

        // join r_tim to ensure items are related to this user (we already checked access)
        $builder->join('r_tim as rt', 'rt.uji_kode = d.uji_kode', 'inner');

        $builder->where('d.kode_layanan', $kode);
        $builder->where('rt.user_id', $user_id);
        $builder->where('d.status_layanan', 1);
        $builder->groupBy('d.kode');

        $rows = $builder->get()->getResult();

        $data = [];
        $no = 1;

        $allUploaded = true;
        $layananRow = $db->table('simlab_t_layanan')->select('lnStatus')->where('lnKode', $kode)->get()->getRow();
        $lnStatus = $layananRow->lnStatus ?? null;

        foreach ($rows as $row) {
            $response = [];

            $response[] = $no++;
            $response[] = $row->nama_layanan ?? '-';
            $response[] = isset($row->jumlah) ? (int)$row->jumlah : 0;

            $keteranganHtml = '<div style="display:block; max-width:260px; min-width:160px; width:100%;'
                . 'max-height:120px; min-height:48px; overflow-y:auto; overflow-x:hidden;'
                . 'padding:4px 6px; border:1px solid #ddd; border-radius:4px; background:#f9f9f9;'
                . 'white-space:pre-wrap; word-break:break-word; font-size:0.9rem;">'
                . htmlspecialchars($row->detKet ?? '', ENT_QUOTES, 'UTF-8') .
                '</div>';
            $response[] = $keteranganHtml;

            // --- FILE LHUS per row detection: pakai files kolom dan t_files_lhus
            $filesListRaw = $row->detFilesList ?? '';
            $detFilesMax = isset($row->detFilesMax) ? (int)$row->detFilesMax : null;

            $files = [];
            $rowHasFile = false;
            $fileUrl = null;

            // Selalu cek tabel t_files_lhus untuk mendapatkan file yang sudah diupload
            try {
                $fileRow = $db->table('t_files_lhus')->where('kode', $row->kode)->limit(1)->get()->getRow();
                if ($fileRow && !empty($fileRow->file_lhus)) {
                    $rowHasFile = true;
                    $fileUrl = base_url('uploads/lhus/' . ltrim($fileRow->file_lhus, '/'));
                    $files[] = ['label' => $fileRow->file_lhus, 'url' => $fileUrl, 'exists' => true];
                }
            } catch (\Throwable $e) {
                // ignore
            }

            // Jika tidak ada di t_files_lhus, cek status files
            if (!$rowHasFile && $detFilesMax !== null && $detFilesMax > 0) {
                // Ada indikasi file tapi tidak ditemukan di t_files_lhus
                $rowHasFile = false; // tetap false karena tidak ada file nyata
            }

            if (!$rowHasFile) {
                $allUploaded = false;
            }

            $combinedHtml = '<div class="d-flex justify-content-center gap-2 align-items-center">';

            // Tombol lihat file
            if ($rowHasFile && $fileUrl) {
                $eyeButton = '<span class="text-primary btn-action" title="Lihat File" onclick="window.open(\'' . esc($fileUrl) . '\', \'_blank\')"><i class="bi bi-eye"></i></span>';
            } else {
                $eyeButton = '<span class="text-secondary btn-action" title="Belum ada file"><i class="bi bi-eye"></i></span>';
            }

            $detKodeAttr = htmlspecialchars($row->kode ?? '', ENT_QUOTES, 'UTF-8');
            $uploadInput = '<label class="mb-0 position-relative" style="cursor:pointer;">'
                        . '<input type="file" accept=".jpg,.jpeg,.png,.pdf,.doc,.docx,.xls,.xlsx" '
                        . 'data-detlist="' . $detKodeAttr . '" data-detkode="' . $detKodeAttr . '" data-ln="' . $encLnId . '" '
                        . 'class="d-none lhus-uploader-input" onchange="autoUploadFile(this)" />'
                        . '<span class="text-primary btn-action" title="Unggah / Ubah File LHUS"><i class="bi bi-upload"></i></span>'
                        . '</label>';

            $combinedHtml .= $eyeButton . $uploadInput . '</div>';

            // Status baris (LHUS)
            $detStatusLHUS = null;
            if ($detFilesMax !== null) {
                $detStatusLHUS = $detFilesMax;
            } else {
                $detStatusLHUS = null;
            }

            $lnStatusInt = isset($lnStatus) ? (int)$lnStatus : null;

            if ($detStatusLHUS === 2) {
                $statusHtml = '<div class="text-center"><span class="badge bg-danger">lhus ditolak</span></div>';
            } elseif ($detStatusLHUS === 3 && $rowHasFile) {
                $statusHtml = '<div class="text-center"><span class="badge bg-info">lhus ter-unggah</span></div>';
            } elseif ($detStatusLHUS === 1) {
                $statusHtml = '<div class="text-center"><span class="badge bg-success">lhus diterima</span></div>';
            } elseif ($lnStatusInt === 5) {
                $statusHtml = '<div class="text-center"><span class="badge bg-primary">terkirim</span></div>';
            } elseif ($rowHasFile) {
                $statusHtml = '<div class="text-center"><span class="badge bg-success">ter-unggah</span></div>';
            } else {
                $statusHtml = '<div class="text-center"><span class="badge bg-warning text-dark">belum upload</span></div>';
            }

            $response[] = $statusHtml;
            $response[] = $combinedHtml;

            $keteranganManajerHtml = '<div style="display:block; max-width:260px; min-width:160px; width:100%;'
                . 'max-height:120px; min-height:48px; overflow-y:auto; overflow-x:hidden;'
                . 'padding:4px 6px; border:1px solid #e6e6ff; border-radius:4px; background:#fbfbff;'
                . 'white-space:pre-wrap; word-break:break-word; font-size:0.9rem; color:#333;">'
                . htmlspecialchars($row->detKetManajar ?? '', ENT_QUOTES, 'UTF-8') .
                '</div>';
            $response[] = $keteranganManajerHtml;

            // >>> kolom tambahan: username approver det.terima_layanan_by
            $accBy = !empty($row->acc_by) ? esc($row->acc_by) : '-';
            $response[] = '<div class="text-center">'.$accBy.'</div>';

            $data[] = $response;
        }

        return $this->response->setJSON(['items' => $data, 'encLn' => $encLnId, 'allFilesUploaded' => $allUploaded, 'lnKode' => $kode]);
    }

    // submit, upload, doUpload, formatStatus, formatStatusForPenyelia remain identical to previous implementation
    // untuk ringkas, saya sertakan fungsi-fungsi berikut persis seperti sebelumnya (tanpa perubahan selain penyesuaian nama tabel/kolom bila diperlukan).

    public function submit($idParam = null)
    {
        $encId = $this->request->getPost('id') ?? $idParam ?? service('uri')->getSegment(3);

        if (empty($encId)) {
            return $this->response->setJSON([
                'res' => 'error',
                'msg' => 'ID missing',
                'xname' => csrf_token(),
                'xhash' => csrf_hash()
            ]);
        }

        try {
            if (preg_match('/^[0-9a-f]+$/i', $encId)) {
                $lnKode = service('encrypter')->decrypt(hex2bin($encId));
            } else {
                $lnKode = service('encrypter')->decrypt($encId);
            }
        } catch (\Throwable $e) {
            return $this->response->setJSON([
                'res' => 'error',
                'msg' => 'Invalid ID',
                'xname' => csrf_token(),
                'xhash' => csrf_hash()
            ]);
        }

        $session = session();
        $user_id = (int) ($session->get('id_user') ?? 0);

        $model = new MyModel($this->table);
        $row = $model->getDataById($this->id, $lnKode);
        if (!$row) {
            return $this->response->setJSON([
                'res' => 'error',
                'msg' => 'Record not found',
                'xname' => csrf_token(),
                'xhash' => csrf_hash()
            ]);
        }

        // --- cek kelengkapan file milik user (tetap) ---
        try {
            $db = \Config\Database::connect();
            $detBuilder = $db->table('t_layanan_detil as d');

            $detBuilder->select("d.kode, d.files");
            $detBuilder->join('r_tim as t', 't.uji_kode = d.uji_kode', 'inner');
            $detBuilder->where('d.kode_layanan', $lnKode);
            $detBuilder->where('t.user_id', $user_id);
            $detBuilder->where('d.status_layanan', 1);
            $userDetRows = $detBuilder->get()->getResult();

            $missingCount = 0;
            $missingItems = [];

            foreach ($userDetRows as $dr) {
                $hasFile = false;

                $filesVal = $dr->files ?? 0;
                if ($filesVal && (int)$filesVal > 0) {
                    $hasFile = true;
                } else {
                    $fileRow = $db->table('t_files_lhus')->where('kode', $dr->kode)->limit(1)->get()->getRow();
                    if ($fileRow && !empty($fileRow->file_lhus)) $hasFile = true;
                }

                if (!$hasFile) {
                    $missingCount++;
                    $missingItems[] = $dr->kode ?? null;
                }
            }

            if ($missingCount > 0) {
                $msg = 'Berhasil dikirim, sisa ' . $missingCount . ' layanan yang perlu diaccc';
                return $this->response->setJSON([
                    'res' => true,
                    'msg' => $msg,
                    'waiting_others' => true,
                    'pending_total' => $missingCount,
                    'missing_detKode' => $missingItems,
                    'parent_updated' => false,
                    'xname' => csrf_token(),
                    'xhash' => csrf_hash()
                ]);
            }
        } catch (\Throwable $e) {
            return $this->response->setJSON([
                'res' => 'error',
                'msg' => 'Error saat memeriksa file detil milik user: ' . $e->getMessage(),
                'xname' => csrf_token(),
                'xhash' => csrf_hash()
            ]);
        }

        // --- kirim & catat upload_by di t_files_lhus / files flag ---
try {
    $db = \Config\Database::connect();
    // mulai transaksi eksplisit
    $db->transBegin();

    // 1) Ubah status files dari 3 (terunggah) menjadi 0 (terkirim ke manajer) untuk detil milik user ini
    $sql1 = "
        UPDATE t_layanan_detil d
        INNER JOIN r_tim rt ON rt.uji_kode = d.uji_kode
        SET d.files = 0
        WHERE d.kode_layanan = ?
          AND rt.user_id = ?
          AND d.status_layanan = 1
          AND d.files = 3
    ";
    $db->query($sql1, [$lnKode, $user_id]);

    // 2) Update status di t_files_lhus menjadi 0 (terkirim)
    $sql1b = "
        UPDATE t_files_lhus lhus
        INNER JOIN t_layanan_detil d ON d.kode = lhus.kode
        INNER JOIN r_tim rt ON rt.uji_kode = d.uji_kode
        SET lhus.status = 0
        WHERE lhus.kode_layanan = ?
          AND rt.user_id = ?
          AND d.status_layanan = 1
          AND lhus.status = 3
    ";
    $db->query($sql1b, [$lnKode, $user_id]);

    // 3) Catat terima_layanan_by untuk baris yang relevan (hanya user ini)
    $sql2 = "
        UPDATE t_layanan_detil d
        INNER JOIN r_tim rt2 ON rt2.uji_kode = d.uji_kode
        SET d.terima_layanan_by = ?
        WHERE d.kode_layanan = ?
          AND rt2.user_id = ?
          AND d.status_layanan = 1
          AND d.files = 0
    ";
    $db->query($sql2, [$user_id, $lnKode, $user_id]);

    // 4) Cek apakah SEMUA layanan detil dalam invoice ini sudah terupload (tidak peduli user siapa)
    // Menggunakan t_files_lhus dengan kode_layanan untuk mempermudah pengecekan
    $sqlCheckAllUploaded = "
        SELECT COUNT(*) as total_belum_upload
        FROM t_layanan_detil d
        LEFT JOIN t_files_lhus lhus ON lhus.kode = d.kode
        WHERE d.kode_layanan = ?
          AND d.status_layanan = 1
          AND (lhus.file_id IS NULL OR lhus.file_lhus IS NULL OR lhus.file_lhus = '')
    ";
    $resultCheck = $db->query($sqlCheckAllUploaded, [$lnKode])->getRow();
    $totalBelumUpload = $resultCheck ? (int)$resultCheck->total_belum_upload : 0;

    $parentUpdated = false;
    if ($totalBelumUpload === 0) {
        // Semua layanan dalam invoice ini sudah terupload, update lnStatus ke 5
        $db->table('simlab_t_layanan')->where('lnKode', $lnKode)->update(['lnStatus' => 5]);
        $parentUpdated = true;
        
        // Update log sampel: set kolom verifikasi_hasil_uji dengan waktu saat ini
        try {
            $logUpdate = [
                'verifikasi_hasil_uji' => date('Y-m-d H:i:s')
            ];
            
            $db->table('t_log_sampel')->where('kode_layanan', $lnKode)->update($logUpdate);
        } catch (\Exception $logEx) {
            // Log error tapi jangan gagalkan proses kirim
            log_message('error', 'Error update log sampel verifikasi_hasil_uji: ' . $logEx->getMessage());
        }
    }

    // 5) Cek apakah masih ada detil milik user lain yang belum upload
    $sqlRemainingForOthers = "
        SELECT COUNT(*) as remaining
        FROM t_layanan_detil d
        LEFT JOIN t_files_lhus lhus ON lhus.kode = d.kode
        WHERE d.kode_layanan = ?
          AND d.status_layanan = 1
          AND (lhus.file_id IS NULL OR lhus.file_lhus IS NULL OR lhus.file_lhus = '')
    ";
    $resultRemaining = $db->query($sqlRemainingForOthers, [$lnKode])->getRow();
    $remainingCount = $resultRemaining ? (int)$resultRemaining->remaining : 0;

    // commit / complete
    if ($db->transStatus() === false) {
        $db->transRollback();
        $dberr = $db->error();
        return $this->response->setJSON([
            'res' => 'error',
            'msg' => 'Gagal menyimpan status pada detil/parent (transaksi gagal). DB Error: ' . ($dberr['message'] ?? 'Unknown'),
            'db' => $dberr,
            'xname' => csrf_token(),
            'xhash' => csrf_hash()
        ]);
    } else {
        $db->transCommit();
    }

    if ($remainingCount > 0) {
        return $this->response->setJSON([
            'res' => true,
            'msg' => 'LHUS Anda berhasil dikirim ke manajer. Masih ada ' . $remainingCount . ' layanan lain yang belum terupload.',
            'waiting_others' => true,
            'pending_total' => $remainingCount,
            'parent_updated' => false,
            'xname' => csrf_token(),
            'xhash' => csrf_hash()
        ]);
    }

    return $this->response->setJSON([
        'res' => true,
        'msg' => 'Semua LHUS dalam invoice ini sudah lengkap dan terkirim ke manajer teknis!',
        'waiting_others' => false,
        'parent_updated' => $parentUpdated,
        'xname' => csrf_token(),
        'xhash' => csrf_hash()
    ]);
} catch (\Throwable $e) {
    if (isset($db) && $db->transStatus() !== false) {
        $db->transRollback();
    }
    $dberr = isset($db) ? $db->error() : [];
    return $this->response->setJSON([
        'res' => 'error',
        'msg' => 'Error saat update: ' . $e->getMessage(),
        'db' => $dberr,
        'xname' => csrf_token(),
        'xhash' => csrf_hash()
    ]);
}

    }

    private function doUpload(\CodeIgniter\HTTP\Files\UploadedFile $file)
    {
        $allowed = ['jpg','jpeg','png','pdf','doc','docx','xls','xlsx'];
        $max = 5 * 1024 * 1024;

        if (!$file->isValid() || $file->hasMoved()) {
            return ['status' => false, 'msg' => 'File tidak valid atau sudah dipindah'];
        }

        if ($file->getSize() > $max) {
            return ['status' => false, 'msg' => 'Ukuran file melebihi 5MB'];
        }

        $ext = strtolower($file->getClientExtension() ?: pathinfo($file->getClientName(), PATHINFO_EXTENSION));
        if (!in_array($ext, $allowed)) {
            return ['status' => false, 'msg' => 'Ekstensi file tidak diperbolehkan'];
        }

        $targetFolder = FCPATH . 'uploads/lhus/';
        if (!is_dir($targetFolder)) {
            if (!mkdir($targetFolder, 0755, true)) {
                return ['status' => false, 'msg' => 'Gagal membuat folder upload'];
            }
        }

        $newName = uniqid('lhus_', true) . '.' . $ext;
        try {
            $moved = $file->move($targetFolder, $newName);
            if ($moved) {
                return ['status' => true, 'filename' => $newName];
            } else {
                return ['status' => false, 'msg' => 'Gagal memindahkan file'];
            }
        } catch (\Throwable $e) {
            return ['status' => false, 'msg' => 'Exception saat upload: ' . $e->getMessage()];
        }
    }

    public function upload()
    {
        $file = $this->request->getFile('lhus_file');
        $encId = $this->request->getPost('id');
        $detKode = $this->request->getPost('detKode');

        $session = session();
        $user_id = $session->get('id_user');

        if (empty($encId)) {
            return $this->response->setJSON([
                'res' => 'error',
                'msg' => 'ID tidak ditemukan',
                'xname' => csrf_token(),
                'xhash' => csrf_hash()
            ]);
        }

        try {
            $lnKode = service('encrypter')->decrypt(hex2bin($encId));
        } catch (\Throwable $e) {
            return $this->response->setJSON([
                'res' => 'error',
                'msg' => 'ID tidak valid',
                'xname' => csrf_token(),
                'xhash' => csrf_hash()
            ]);
        }

        if (!($file && $file->isValid() && !$file->hasMoved())) {
            return $this->response->setJSON([
                'res' => 'error',
                'msg' => 'File tidak valid atau tidak dipilih',
                'xname' => csrf_token(),
                'xhash' => csrf_hash()
            ]);
        }

        $uploadResult = $this->doUpload($file);
        if (!$uploadResult['status']) {
            return $this->response->setJSON([
                'res' => 'error',
                'msg' => $uploadResult['msg'],
                'xname' => csrf_token(),
                'xhash' => csrf_hash()
            ]);
        }

        $filename = $uploadResult['filename'];

        $db = \Config\Database::connect();

        try {
            if (!empty($detKode)) {
                // Update status files ke 3 (terunggah, belum dikirim)
                $ok = $db->table('t_layanan_detil')
                    ->where('kode', $detKode)
                    ->update(['files' => 3]);

                if ($ok) {
                    // Cek apakah sudah ada record di t_files_lhus untuk kode ini
                    $existingFile = $db->table('t_files_lhus')
                        ->where('kode', $detKode)
                        ->get()->getRow();

                    if ($existingFile) {
                        // Update record yang sudah ada
                        $db->table('t_files_lhus')
                            ->where('kode', $detKode)
                            ->update([
                                'file_lhus' => $filename,
                                'upload_by' => $user_id,
                                'status' => 3,  // status 3 = terunggah (belum dikirim)
                                'kode_layanan' => $lnKode
                            ]);
                        
                        // Hapus file lama jika ada
                        if (!empty($existingFile->file_lhus) && $existingFile->file_lhus !== $filename) {
                            $oldFilePath = FCPATH . 'uploads/lhus/' . $existingFile->file_lhus;
                            if (is_file($oldFilePath)) {
                                @unlink($oldFilePath);
                            }
                        }
                    } else {
                        // Insert record baru jika belum ada
                        $db->table('t_files_lhus')->insert([
                            'kode' => $detKode,
                            'kode_layanan' => $lnKode,
                            'file_lhus' => $filename,
                            'upload_by' => $user_id,
                            'status' => 3  // status 3 = terunggah (belum dikirim)
                        ]);
                    }

                    return $this->response->setJSON([
                        'res' => true,
                        'msg' => 'File LHUS berhasil diunggah (status: terunggah)',
                        'url' => base_url('uploads/lhus/' . $filename),
                        'detKode' => $detKode,
                        'xname' => csrf_token(),
                        'xhash' => csrf_hash()
                    ]);
                } else {
                    $savedPath = FCPATH . 'uploads/lhus/' . $filename;
                    if (is_file($savedPath)) @unlink($savedPath);
                    $dberr = $db->error();
                    return $this->response->setJSON([
                        'res' => 'error',
                        'msg' => 'Gagal menyimpan ke detail (detKode). DB Error: ' . ($dberr['message'] ?? 'Unknown'),
                        'db' => $dberr,
                        'xname' => csrf_token(),
                        'xhash' => csrf_hash()
                    ]);
                }
            } else {
                // Update status files ke 3 untuk semua detil yang relevan dengan user
                $updateBuilder = $db->table('t_layanan_detil as d');
                $updateBuilder->join('r_tim as t', 't.uji_kode = d.uji_kode', 'inner');
                $updateBuilder->where('d.kode_layanan', $lnKode);
                $updateBuilder->where('t.user_id', $user_id);
                $updateBuilder->where('d.status_layanan', 1);
                $ok = $updateBuilder->update(['d.files' => 3]);

                if ($ok) {
                    // Simpan t_files_lhus untuk setiap detil yang relevan
                    $detRows = $db->table('t_layanan_detil as d2')
                        ->join('r_tim as t2', 't2.uji_kode = d2.uji_kode', 'inner')
                        ->where('d2.kode_layanan', $lnKode)
                        ->where('t2.user_id', $user_id)
                        ->where('d2.status_layanan', 1)
                        ->get()->getResult();

                    foreach ($detRows as $dr) {
                        $db->table('t_files_lhus')->insert([
                            'kode' => $dr->kode,
                            'kode_layanan' => $lnKode,
                            'file_lhus' => $filename,
                            'upload_by' => $user_id,
                            'status' => 3  // status 3 = terunggah (belum dikirim)
                        ]);
                    }

                    return $this->response->setJSON([
                        'res' => true,
                        'msg' => 'File LHUS berhasil diunggah untuk layanan terkait Anda (status: terunggah)',
                        'url' => base_url('uploads/lhus/' . $filename),
                        'xname' => csrf_token(),
                        'xhash' => csrf_hash()
                    ]);
                } else {
                    $savedPath = FCPATH . 'uploads/lhus/' . $filename;
                    if (is_file($savedPath)) @unlink($savedPath);
                    $dberr = $db->error();
                    return $this->response->setJSON([
                        'res' => 'error',
                        'msg' => 'Gagal menyimpan ke detil. DB Error: ' . ($dberr['message'] ?? 'Unknown'),
                        'db' => $dberr,
                        'xname' => csrf_token(),
                        'xhash' => csrf_hash()
                    ]);
                }
            }
        } catch (\Throwable $e) {
            $savedPath = FCPATH . 'uploads/lhus/' . $filename;
            if (is_file($savedPath)) @unlink($savedPath);
            return $this->response->setJSON([
                'res' => 'error',
                'msg' => 'Error saat menyimpan ke detil: ' . $e->getMessage(),
                'xname' => csrf_token(),
                'xhash' => csrf_hash()
            ]);
        }
    }

    private function formatStatus($lnStatus)
    {
        switch ((int)$lnStatus) {
            case 0: return '<span class="badge bg-secondary">Draft</span>';
            case 1: return '<span class="badge bg-warning">In Review (Manajer)</span>';
            case 2: return '<span class="badge bg-danger">Ditolak</span>';
            case 3: return '<span class="badge bg-info">In Review (Admin)</span>';
            case 4: return '<span class="badge bg-primary">Sedang dalam pengujian</span>';
            case 5: return '<span class="badge bg-primary">LHUS sedang diverifikasi manajer</span>';
            case 6: return '<span class="badge bg-success">LHUS Disetujui</span>';
            case 7: return '<span class="badge bg-primary">Memproses LHU</span>';
            case 8: return '<span class="badge bg-success">LHU sedang diproses</span>';
            case 9: return '<span class="badge bg-dark">Pengujian Selesai</span>';
            default: return '<span class="badge bg-dark">Unknown</span>';
        }
    }

    private function formatStatusForPenyelia($lnStatus, $lnKode, $userId)
    {
        $db = \Config\Database::connect();

        // PRIORITAS 1: ada detail milik user yang ditolak (files = 2)
        try {
            $checkReject = $db->table('t_layanan_detil')
                ->select('1')
                ->join('r_tim rt', 'rt.uji_kode = t_layanan_detil.uji_kode', 'inner')
                ->where('t_layanan_detil.kode_layanan', $lnKode)
                ->where('t_layanan_detil.status_layanan', 1)
                ->where('rt.user_id', $userId)
                ->where('t_layanan_detil.files', 2)
                ->limit(1)
                ->get()->getRow();

            if ($checkReject) {
                return '<span class="badge bg-danger">LHUS ditolak</span>';
            }
        } catch (\Throwable $e) {}

        // PRIORITAS 2: Cek apakah ada yang statusnya 3 (terunggah tapi belum dikirim)
        try {
            $checkUploaded = $db->table('t_layanan_detil as d')
                ->select('1')
                ->join('r_tim rt', 'rt.uji_kode = d.uji_kode', 'inner')
                ->where('d.kode_layanan', $lnKode)
                ->where('d.status_layanan', 1)
                ->where('rt.user_id', $userId)
                ->where('d.files', 3)
                ->limit(1)
                ->get()->getRow();

            if ($checkUploaded) {
                return '<span class="badge bg-info">Terunggah (belum dikirim)</span>';
            }
        } catch (\Throwable $e) {}

        // PRIORITAS 3: Cek apakah sudah diterima semua (files = 1)
        try {
            $totalUserActive = (int) $db->table('t_layanan_detil as d')
                ->join('r_tim rt', 'rt.uji_kode = d.uji_kode', 'inner')
                ->where('d.kode_layanan', $lnKode)
                ->where('d.status_layanan', 1)
                ->where('rt.user_id', $userId)
                ->countAllResults();

            if ($totalUserActive > 0) {
                $acceptedCount = (int) $db->table('t_layanan_detil as d')
                    ->join('r_tim rt', 'rt.uji_kode = d.uji_kode', 'inner')
                    ->where('d.kode_layanan', $lnKode)
                    ->where('d.status_layanan', 1)
                    ->where('rt.user_id', $userId)
                    ->where('d.files', 1)
                    ->countAllResults();

                if ($acceptedCount === $totalUserActive) {
                    return '<span class="badge bg-success">LHUS diterima</span>';
                }
            }
        } catch (\Throwable $e) {}

        // PRIORITAS 4: Cek apakah sudah terkirim ke manajer (files = 0)
        try {
            $checkSent = $db->table('t_layanan_detil as d')
                ->select('1')
                ->join('r_tim rt', 'rt.uji_kode = d.uji_kode', 'inner')
                ->where('d.kode_layanan', $lnKode)
                ->where('d.status_layanan', 1)
                ->where('rt.user_id', $userId)
                ->where('d.files', 0)
                ->limit(1)
                ->get()->getRow();

            if ($checkSent) {
                return '<span class="badge bg-primary">Terkirim ke manajer teknis</span>';
            }
        } catch (\Throwable $e) {}

        // Fallback ke mapping lnStatus
        return $this->formatStatus((int)$lnStatus);
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
