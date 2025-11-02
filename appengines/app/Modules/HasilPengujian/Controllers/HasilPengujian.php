<?php

namespace Modules\HasilPengujian\Controllers;

use App\Controllers\BaseController;
use App\Models\MyModel;

class HasilPengujian extends BaseController
{
    private $table = 'simlab_t_layanan';
    private $id    = 'lnKode';

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
    $user_id = $session->get('id_user');

    $model = new MyModel($this->table);
    $modelDet = new MyModel('simlab_t_layanan_detil');

    // --- Parse lnStatus filter: "4", "4,5,6", atau token khusus "tolak"
    $lnStatusParam = (string) ($this->request->getGet('lnStatus') ?? '');
    $lnStatusFilter = [];
    $wantReject = false; // [NEW]
    if ($lnStatusParam !== '') {
        foreach (preg_split('/[,\s]+/', $lnStatusParam, -1, PREG_SPLIT_NO_EMPTY) as $p) {
            $tp = strtolower(trim($p));
            if (in_array($tp, ['tolak','reject','ditolak'], true)) { // [NEW]
                $wantReject = true;
                continue;
            }
            if ($tp !== '' && is_numeric($tp)) {
                $lnStatusFilter[] = (int) $tp;
            }
        }
        $lnStatusFilter = array_values(array_unique($lnStatusFilter));
    }

    // Ambil lnKode terkait user (penyelia prioritas), hanya detStatus=1
    $detilList = $modelDet->getAllDataById(['detPenyelia' => $user_id, 'detStatus' => 1]);

    if (empty($detilList)) {
        $db = \Config\Database::connect();
        $builder = $db->table('simlab_t_layanan_detil as d');
        $builder->select('d.detLnKode');
        $builder->groupStart()
                    ->where('d.detPenyelia', $user_id)
                    ->orWhere('d.detManajerTeknis', $user_id)
                ->groupEnd();
        $builder->where('d.detStatus', 1);
        $detilList = $builder->get()->getResult();
    }

    $lnKodeList = [];
    foreach ($detilList as $item) {
        if (is_array($item) && isset($item['detLnKode'])) $lnKodeList[] = $item['detLnKode'];
        elseif (is_object($item) && isset($item->detLnKode)) $lnKodeList[] = $item->detLnKode;
    }
    $lnKodeList = array_values(array_unique(array_filter($lnKodeList)));

    if (empty($lnKodeList)) {
        return $this->response->setJSON(["items" => []]);
    }

    $db = \Config\Database::connect();
    $b  = $db->table('simlab_t_layanan as l');

    // --- Agregasi status user (sinkron dgn formatStatusForPenyelia)
    $aggSql = $db->table('simlab_t_layanan_detil d')
        ->select("
            d.detLnKode,
            MAX(CASE WHEN d.detStatus=1 AND d.detPenyelia={$user_id} AND d.detStatusLHUS=2 THEN 1 ELSE 0 END) AS has_reject_for_user,
            SUM(CASE WHEN d.detStatus=1 AND d.detPenyelia={$user_id} THEN 1 ELSE 0 END) AS user_active_total,
            SUM(CASE WHEN d.detStatus=1 AND d.detPenyelia={$user_id} AND d.detStatusLHUS=1 THEN 1 ELSE 0 END) AS user_accepted_total,
            SUM(CASE WHEN d.detStatus=1 AND d.detPenyelia={$user_id} AND (d.detil_LHUS IS NULL OR d.detil_LHUS='') THEN 1 ELSE 0 END) AS pending_for_user
        ", false)
        ->groupBy('d.detLnKode')
        ->getCompiledSelect(false);

    $b->select('l.*');
    $b->join("({$aggSql}) agg", 'agg.detLnKode = l.lnKode', 'left');

    // Info pemesan
    $b->select('u.user_name as pemesan_name, u.user_email as pemesan_email, u.user_identity as pemesan_identity');
    $b->join('simlab_account_users as u', 'u.user_id = l.user_id', 'left');

    $b->whereIn('l.lnKode', $lnKodeList);
    $b->where('l.lnStatus !=', 2);                // exclude Ditolak awal (tahap awal proses)
    $b->orderBy('l.lnTgl', 'DESC');

    // =======================
    // FILTER “VIEW STATUS” = algoritma formatStatusForPenyelia (+ 'tolak')
    // =======================
    if ($wantReject || !empty($lnStatusFilter)) {
        $b->groupStart();

        // [NEW] Filter khusus "LHUS ditolak" → ada minimal satu detil aktif milik penyelia dengan detStatusLHUS=2
        if ($wantReject) {
            $b->orGroupStart()
                ->where('COALESCE(agg.has_reject_for_user,0) =', 1)
            ->groupEnd();
        }

        // Filter numerik lain (4,5,6,dst)
        foreach ($lnStatusFilter as $s) {
            switch ((int)$s) {
                case 4: // "Sedang dalam pengujian"
                    $b->orGroupStart()
                        ->where('COALESCE(agg.has_reject_for_user,0) =', 0)
                        ->where('COALESCE(agg.pending_for_user,0) >', 0)
                        ->where('l.lnStatus', 4)
                      ->groupEnd();
                    break;

                case 5: // "LHUS sedang diverifikasi manajer"
                    $b->orGroupStart()
                        ->where('COALESCE(agg.has_reject_for_user,0) =', 0)
                        ->where('COALESCE(agg.pending_for_user,0) =', 0)
                        ->groupStart()
                            ->where('COALESCE(agg.user_active_total,0) =', 0)
                            ->orWhere('COALESCE(agg.user_accepted_total,0) < COALESCE(agg.user_active_total,0)', null, false)
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
                    // Fallback: mapping langsung l.lnStatus, tapi tetap hormati prioritas
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

        $id = bin2hex($this->encrypter->encrypt($row->lnKode));
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
        $user_id = $session->get('id_user');

        try {
            $kode = $this->encrypter->decrypt(hex2bin($id));
        } catch (\Exception $e) {
            return $this->response->setJSON(['items' => []]);
        }

        // cek user sebagai manajer teknis/penyelia untuk Ln ini
        $db = \Config\Database::connect();
        $checkBuilder = $db->table('simlab_t_layanan_detil as d');
        $checkBuilder->select('1');
        $checkBuilder->where('d.detLnKode', $kode);
        $checkBuilder->groupStart();
        $checkBuilder->where('d.detPenyelia', $user_id);
        $checkBuilder->orWhere('d.detManajerTeknis', $user_id);
        $checkBuilder->groupEnd();
        $checkBuilder->where('d.detStatus', 1);
        $exists = $checkBuilder->limit(1)->get()->getRow();

        if (!$exists) {
            return $this->response->setJSON(['items' => []]);
        }

        // Encrypted ln
        $encLnId = bin2hex($this->encrypter->encrypt($kode));

        $builder = $db->table('simlab_t_layanan_detil as d');

        $builder->select("
            d.detKode,
            d.detUjiKode,
            d.detLnKode,
            d.detLayanan,
            d.detJenKode,
            GROUP_CONCAT(DISTINCT d.detKeterangan SEPARATOR ' | ') AS detKet,
            GROUP_CONCAT(DISTINCT d.detKetLn SEPARATOR ' | ') AS detKetLn,
            GROUP_CONCAT(DISTINCT d.detKetLhus SEPARATOR ' | ') AS detKetLhus,
            GROUP_CONCAT(DISTINCT d.detil_LHUS SEPARATOR ';;') AS detLHUS,
            GROUP_CONCAT(DISTINCT d.detStatusLHUS SEPARATOR ',') AS detStatusLHUSList,
            MAX(d.detStatusLHUS) AS detStatusLHUSMax,
            SUM(CASE WHEN d.detStatusLHUS = 2 THEN 1 ELSE 0 END) AS cnt_rejected,
            SUM(CASE WHEN d.detStatusLHUS = 1 THEN 1 ELSE 0 END) AS cnt_accepted,
            SUM(d.detJumlah) AS jumlah,
            SUM(d.detBiaya) AS detBiaya,
            MAX(d.detStatus) AS detStatusGroup
        ");
        $builder->where('d.detLnKode', $kode);
        $builder->groupStart();
        $builder->where('d.detPenyelia', $user_id);
        $builder->orWhere('d.detManajerTeknis', $user_id);
        $builder->groupEnd();
        $builder->where('d.detStatus', 1);
        $builder->groupBy('d.detKode');
        $rows = $builder->get()->getResult();

        $data = [];
        $no = 1;

        $allUploaded = true;
        $layananRow = $db->table('simlab_t_layanan')->select('lnStatus')->where('lnKode', $kode)->get()->getRow();
        $lnStatus = $layananRow->lnStatus ?? null;

        foreach ($rows as $row) {
            $response = [];

            $response[] = $no++;
            $response[] = $row->detLayanan ?? '-';
            $response[] = isset($row->jumlah) ? (int)$row->jumlah : 0;

            $keteranganHtml = '<div style="display:block; max-width:260px; min-width:160px; width:100%;'
                . 'max-height:120px; min-height:48px; overflow-y:auto; overflow-x:hidden;'
                . 'padding:4px 6px; border:1px solid #ddd; border-radius:4px; background:#f9f9f9;'
                . 'white-space:pre-wrap; word-break:break-word; font-size:0.9rem;">'
                . htmlspecialchars($row->detKet ?? '', ENT_QUOTES, 'UTF-8') .
                '</div>';
            $response[] = $keteranganHtml;

            // --- FILE LHUS per row ---
            $detLHUSraw = $row->detLHUS ?? '';
            $detKodesRaw = $row->detKode ?? '';
            $detKodesAttr = htmlspecialchars($detKodesRaw, ENT_QUOTES, 'UTF-8');

            $files = [];
            if (!empty($detLHUSraw)) {
                $split = array_filter(array_map('trim', explode(';;', $detLHUSraw)));
                foreach ($split as $f) {
                    if (empty($f)) continue;
                    if (preg_match('/^https?:\/\//i', $f)) {
                        $files[] = ['label' => $f, 'url' => $f, 'exists' => true];
                    } else {
                        $possiblePath = FCPATH . 'uploads/lhus/' . ltrim($f, '/');
                        if (is_file($possiblePath)) {
                            $url = base_url('uploads/lhus/' . ltrim($f, '/'));
                            $files[] = ['label' => $f, 'url' => $url, 'exists' => true];
                        } else {
                            $files[] = ['label' => $f, 'url' => null, 'exists' => false];
                        }
                    }
                }
            }

            // Cek file fisik / kolom lain sebagai fallback
            $rowHasFile = false;
            if (!empty($files)) {
                foreach ($files as $fi) {
                    if ($fi['exists']) { $rowHasFile = true; break; }
                }
            }

            if (!$rowHasFile) {
                $detFields = ['detil_LHUS', 'detil_LHU', 'detFile', 'detLhus', 'detFileLhus', 'det_file_lhus'];
                foreach ($detFields as $df) {
                    if (isset($row->{$df}) && !empty($row->{$df})) {
                        $raw = $row->{$df};
                        if (preg_match('/^https?:\/\//i', $raw)) { $rowHasFile = true; break; }
                        $possiblePath = FCPATH . 'uploads/lhus/' . ltrim($raw, '/');
                        if (is_file($possiblePath)) { $rowHasFile = true; break; }
                    }
                }
            }

            if (!$rowHasFile) {
                try {
                    $modelDet = new MyModel('simlab_t_layanan_detil');
                    $detRows = $modelDet->getAllDataById(['detKode' => $row->detKode ?? null, 'detStatus' => 1]);
                    foreach ($detRows as $dr) {
                        foreach (['detil_LHUS','detil_LHU','detFile','detLhus','detFileLhus','det_file_lhus'] as $df) {
                            if (isset($dr->{$df}) && !empty($dr->{$df})) {
                                $raw = $dr->{$df};
                                if (preg_match('/^https?:\/\//i', $raw) || is_file(FCPATH . 'uploads/lhus/' . ltrim($raw, '/'))) {
                                    $rowHasFile = true;
                                    break 3;
                                }
                            }
                        }
                    }
                } catch (\Throwable $e) {
                    // ignore
                }
            }

            if (!$rowHasFile) {
                $allUploaded = false;
            }

            $combinedHtml = '<div class="d-flex justify-content-center gap-2 align-items-center">';

            if (!empty($files)) {
                $firstViewUrl = null;
                foreach ($files as $fi) {
                    if ($fi['exists']) { $firstViewUrl = $fi['url']; break; }
                }
                if ($firstViewUrl) {
                    $eyeButton = '<span class="text-primary btn-action" title="Lihat File" onclick="window.open(\'' . esc($firstViewUrl) . '\', \'_blank\')"><i class="bi bi-eye"></i></span>';
                } else {
                    $eyeButton = '<span class="text-secondary btn-action" title="File tidak ditemukan"><i class="bi bi-eye"></i></span>';
                }
            } else {
                $eyeButton = '<span class="text-secondary btn-action" title="Belum ada file"><i class="bi bi-eye"></i></span>';
            }

            $uploadInput = '<label class="mb-0 position-relative" style="cursor:pointer;">'
                        . '<input type="file" accept=".jpg,.jpeg,.png,.pdf,.doc,.docx,.xls,.xlsx" '
                        . 'data-detlist="' . $detKodesAttr . '" data-detkode="' . htmlspecialchars($row->detKode ?? '', ENT_QUOTES, 'UTF-8') . '" data-ln="' . $encLnId . '" '
                        . 'class="d-none lhus-uploader-input" onchange="autoUploadFile(this)" />'
                        . '<span class="text-primary btn-action" title="Unggah / Ubah File LHUS"><i class="bi bi-upload"></i></span>'
                        . '</label>';

            $combinedHtml .= $eyeButton . $uploadInput . '</div>';

            // Status baris (LHUS)
            $detStatusLHUS = null;
            if (isset($row->detStatusLHUSMax) && $row->detStatusLHUSMax !== null) {
                $detStatusLHUS = (int)$row->detStatusLHUSMax;
            } elseif (isset($row->detStatusLHUSList) && $row->detStatusLHUSList !== '') {
                $parts = array_filter(array_map('trim', explode(',', $row->detStatusLHUSList)));
                if (in_array('2', $parts, true) || in_array(2, array_map('intval', $parts), true)) {
                    $detStatusLHUS = 2;
                } elseif (in_array('1', $parts, true) || in_array(1, array_map('intval', $parts), true)) {
                    $detStatusLHUS = 1;
                } else {
                    $detStatusLHUS = 0;
                }
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
                . htmlspecialchars($row->detKetLhus ?? '', ENT_QUOTES, 'UTF-8') .
                '</div>';
            $response[] = $keteranganManajerHtml;

            $data[] = $response;
        }

        return $this->response->setJSON(['items' => $data, 'encLn' => $encLnId, 'allFilesUploaded' => $allUploaded]);
    }

    private function detectLhusFile($row)
    {
        $possibleFields = [
            'lnLhus', 'lnLHUS', 'lnFileLhus', 'ln_file_lhus', 'lhus_file', 'ln_lhus', 'ln_lhus_file', 'ln_file_lhus_path'
        ];

        foreach ($possibleFields as $f) {
            if (isset($row->{$f}) && !empty($row->{$f})) {
                $raw = $row->{$f};

                if (preg_match('/^https?:\/\//i', $raw)) {
                    return ['has' => true, 'url' => $raw];
                }

                $possiblePath = FCPATH . 'uploads/lhus/' . ltrim($raw, '/');
                if (is_file($possiblePath)) {
                    $possibleUrl = base_url('uploads/lhus/' . ltrim($raw, '/'));
                    return ['has' => true, 'url' => $possibleUrl];
                }

                return ['has' => false, 'url' => '#'];
            }
        }

        if (isset($row->lnKode) && !empty($row->lnKode)) {
            try {
                $modelDet = new MyModel('simlab_t_layanan_detil');
                $detils = $modelDet->getAllDataById(['detLnKode' => $row->lnKode]);
                foreach ($detils as $d) {
                    $detFields = ['detil_LHUS', 'detil_LHU', 'detFile', 'detLhus', 'detFileLhus', 'det_file_lhus'];
                    foreach ($detFields as $df) {
                        if (isset($d->{$df}) && !empty($d->{$df})) {
                            $raw = $d->{$df};
                            if (preg_match('/^https?:\/\//i', $raw)) {
                                return ['has' => true, 'url' => $raw];
                            }
                            $possiblePath = FCPATH . 'uploads/lhus/' . ltrim($raw, '/');
                            if (is_file($possiblePath)) {
                                $possibleUrl = base_url('uploads/lhus/' . ltrim($raw, '/'));
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

    public function submit($idParam = null)
    {
        $encId = $this->request->getPost('id') ?? $idParam ?? $this->request->uri->getSegment(3);

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
                $lnKode = $this->encrypter->decrypt(hex2bin($encId));
            } else {
                $lnKode = $this->encrypter->decrypt($encId);
            }
        } catch (\Throwable $e) {
            return $this->response->setJSON([
                'res' => 'error',
                'msg' => 'Invalid ID',
                'debug' => $e->getMessage(),
                'xname' => csrf_token(),
                'xhash' => csrf_hash()
            ]);
        }

        $session = session();
        $user_id = $session->get('id_user');

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

        try {
            $db = \Config\Database::connect();
            $detBuilder = $db->table('simlab_t_layanan_detil as d');

            $detBuilder->select("d.detKode, d.detil_LHUS, d.detil_LHU, d.detKetLhus, d.detKetLn, d.detStatusLHUS, d.detPenyelia, d.detManajerTeknis");
            $detBuilder->where('d.detLnKode', $lnKode);
            $detBuilder->groupStart();
                $detBuilder->where('d.detPenyelia', $user_id);
                $detBuilder->orWhere('d.detManajerTeknis', $user_id);
            $detBuilder->groupEnd();
            $detBuilder->where('d.detStatus', 1);
            $userDetRows = $detBuilder->get()->getResult();

            $missingCount = 0;
            $missingItems = [];

            foreach ($userDetRows as $dr) {
                $hasFile = false;

                $candidates = ['detil_LHUS', 'detil_LHU', 'detKetLhus', 'detKetLn'];
                foreach ($candidates as $f) {
                    if (isset($dr->{$f}) && !empty(trim((string)$dr->{$f}))) {
                        $val = trim((string)$dr->{$f});
                        if (preg_match('/^https?:\/\//i', $val)) {
                            $hasFile = true;
                            break;
                        }
                        if (strpos($val, ';;') !== false) {
                            $parts = array_filter(array_map('trim', explode(';;', $val)));
                            foreach ($parts as $p) {
                                if (preg_match('/^https?:\/\//i', $p) || is_file(FCPATH . 'uploads/lhus/' . ltrim($p, '/'))) {
                                    $hasFile = true;
                                    break 2;
                                }
                            }
                        }
                        $possiblePath = FCPATH . 'uploads/lhus/' . ltrim($val, '/');
                        if (is_file($possiblePath)) {
                            $hasFile = true;
                            break;
                        }
                    }
                }

                if (!$hasFile) {
                    $missingCount++;
                    $missingItems[] = $dr->detKode ?? null;
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

        try {
            $db = \Config\Database::connect();
            $db->transStart();

            $db->table('simlab_t_layanan_detil')
                ->where('detLnKode', $lnKode)
                ->groupStart()
                    ->where('detPenyelia', $user_id)
                    ->orWhere('detManajerTeknis', $user_id)
                ->groupEnd()
                ->where('detStatus', 1)
                ->groupStart()
                    ->where('detStatusLHUS IS NULL', null, false)
                    ->orWhereIn('detStatusLHUS', [0, 3])
                ->groupEnd()
                ->update(['detStatusLHUS' => 0]);

            $otherBuilder = $db->table('simlab_t_layanan_detil as d2');
            $otherBuilder->select('d2.detKode');
            $otherBuilder->where('d2.detLnKode', $lnKode);
            $otherBuilder->where('d2.detStatus', 1);
            $otherBuilder->groupStart();
                $otherBuilder->where('d2.detil_LHUS IS NULL', null, false);
                $otherBuilder->orWhere('d2.detil_LHUS', '');
            $otherBuilder->groupEnd();

            $remainingRows = $otherBuilder->get()->getResult();
            $remainingCount = is_array($remainingRows) ? count($remainingRows) : 0;
            $remainingCodes = [];
            foreach ($remainingRows as $r) {
                if (isset($r->detKode)) $remainingCodes[] = $r->detKode;
            }

            if ($remainingCount === 0) {
                $model->updateData(['lnStatus' => 5], $this->id, $lnKode);
                $parentUpdated = true;
            } else {
                $parentUpdated = false;
            }

            $db->transComplete();

            if ($db->transStatus() === false) {
                return $this->response->setJSON([
                    'res' => 'error',
                    'msg' => 'Gagal menyimpan status pada detil/parent (transaksi gagal)',
                    'xname' => csrf_token(),
                    'xhash' => csrf_hash()
                ]);
            }

            if ($remainingCount > 0) {
                return $this->response->setJSON([
                    'res' => true,
                    'msg' => 'Sebagian layanan sudah dikirim, masih ada ' . $remainingCount . ' layanan aktif yang perlu diaccc.',
                    'waiting_others' => true,
                    'pending_total' => $remainingCount,
                    'missing_detKode' => $remainingCodes,
                    'parent_updated' => false,
                    'xname' => csrf_token(),
                    'xhash' => csrf_hash()
                ]);
            }

            return $this->response->setJSON([
                'res' => true,
                'msg' => 'Lhus terkirim ke manajer',
                'waiting_others' => false,
                'parent_updated' => true,
                'xname' => csrf_token(),
                'xhash' => csrf_hash()
            ]);
        } catch (\Throwable $e) {
            return $this->response->setJSON([
                'res' => 'error',
                'msg' => 'Error saat update: ' . $e->getMessage(),
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
            $lnKode = $this->encrypter->decrypt(hex2bin($encId));
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

        $modelDet = new MyModel('simlab_t_layanan_detil');
        $db = \Config\Database::connect();

        try {
            if (!empty($detKode)) {
                $ok = $db->table('simlab_t_layanan_detil')
                    ->where('detKode', $detKode)
                    ->update(['detil_LHUS' => $filename, 'detStatusLHUS' => 3]);

                if ($ok) {
                    return $this->response->setJSON([
                        'res' => true,
                        'msg' => 'File LHUS berhasil diunggah ',
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
                $builder = $db->table('simlab_t_layanan_detil');
                $builder->where('detLnKode', $lnKode);
                $builder->groupStart();
                $builder->where('detPenyelia', $user_id);
                $builder->orWhere('detManajerTeknis', $user_id);
                $builder->groupEnd();
                $ok = $builder->update(['detil_LHUS' => $filename, 'detStatusLHUS' => 3]);

                if ($ok) {
                    return $this->response->setJSON([
                        'res' => true,
                        'msg' => 'File LHUS berhasil diunggah untuk layanan terkait Anda',
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

        // PRIORITAS: ada detail milik penyelia yang ditolak
        try {
            $checkReject = $db->table('simlab_t_layanan_detil')
                ->select('1')
                ->where('detLnKode', $lnKode)
                ->where('detStatus', 1)
                ->where('detPenyelia', $userId)
                ->where('detStatusLHUS', 2)
                ->limit(1)
                ->get()->getRow();

            if ($checkReject) {
                return '<span class="badge bg-danger">LHUS ditolak</span>';
            }
        } catch (\Throwable $e) {}

        // CEK PENDING: semua detil milik penyelia sudah punya file?
        $pendingCount = 0;
        try {
            $rows = $db->table('simlab_t_layanan_detil')
                ->select('detKode, detil_LHUS, detil_LHU, detKetLhus, detKetLn')
                ->where('detLnKode', $lnKode)
                ->where('detStatus', 1)
                ->where('detPenyelia', $userId)
                ->get()->getResult();

            foreach ($rows as $dr) {
                $hasFile = false;
                foreach (['detil_LHUS', 'detil_LHU', 'detKetLhus', 'detKetLn'] as $f) {
                    if (!isset($dr->{$f}) || trim((string)$dr->{$f}) === '') continue;

                    $val = trim((string)$dr->{$f});
                    if (preg_match('/^https?:\/\//i', $val)) { $hasFile = true; break; }
                    if (strpos($val, ';;') !== false) {
                        foreach (array_filter(array_map('trim', explode(';;', $val))) as $p) {
                            if (preg_match('/^https?:\/\//i', $p) || is_file(FCPATH.'uploads/lhus/'.ltrim($p,'/'))) {
                                $hasFile = true; break 2;
                            }
                        }
                    }
                    if (is_file(FCPATH.'uploads/lhus/'.ltrim($val,'/'))) { $hasFile = true; break; }
                }
                if (!$hasFile) $pendingCount++;
            }
        } catch (\Throwable $e) {
            $pendingCount = -1;
        }

        if ($pendingCount === 0) {
            try {
                $totalUserActive = (int) $db->table('simlab_t_layanan_detil')
                    ->where('detLnKode', $lnKode)
                    ->where('detStatus', 1)
                    ->where('detPenyelia', $userId)
                    ->countAllResults();

                if ($totalUserActive > 0) {
                    $acceptedCount = (int) $db->table('simlab_t_layanan_detil')
                        ->where('detLnKode', $lnKode)
                        ->where('detStatus', 1)
                        ->where('detPenyelia', $userId)
                        ->where('detStatusLHUS', 1)
                        ->countAllResults();

                    if ($acceptedCount === $totalUserActive) {
                        return '<span class="badge bg-success">LHUS diterima</span>';
                    }
                }

                return '<span class="badge bg-primary">LHUS sedang diverifikasi manajer</span>';

            } catch (\Throwable $e) {
                return '<span class="badge bg-primary">LHUS sedang diverifikasi manajer</span>';
            }
        }

        // Fallback ke mapping lnStatus
        return $this->formatStatus((int)$lnStatus);
    }
}
