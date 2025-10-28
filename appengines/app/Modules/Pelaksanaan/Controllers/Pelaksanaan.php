<?php

namespace Modules\Pelaksanaan\Controllers;

use App\Controllers\BaseController;
use App\Models\MyModel;

class Pelaksanaan extends BaseController
{
    private $table = 'simlab_t_layanan';
    private $id    = 'lnKode';

    public function index()
    {
        $session = session();
        $user_id = $session->get('id_user');

        $modelUser = new MyModel('simlab_account_users');

        $data = [
            'title' => 'Data Pelaksanaan',
            'user'  => $modelUser->getDataById('user_id', $user_id),
        ];

        return view('Modules\Pelaksanaan\Views\v_pelaksanaan', $data);
    }

    
        public function dataList()
{
    $model = new MyModel($this->table);
    $data  = [];

    $list = $model->getAllDataWithOrder(['lnTgl' => 'DESC']);

    if (empty($list)) {
        return $this->response->setJSON(["items" => []]);
    }

    // kumpulkan user_id untuk fetch nama & identity sekaligus
    $userIds = [];
    foreach ($list as $r) {
        if (isset($r->user_id) && $r->user_id) {
            $userIds[] = $r->user_id;
        }
    }
    $userMap = [];
    if (!empty($userIds)) {
        $db = \Config\Database::connect();
        $users = $db->table('simlab_account_users')
                    ->select('user_id, user_name, user_identity, user_email')
                    ->whereIn('user_id', array_values(array_unique($userIds)))
                    ->get()
                    ->getResult();

        foreach ($users as $u) {
            $userMap[$u->user_id] = $u;
        }
    }

    foreach ($list as $row) {
        // hanya tampilkan status >= 6
        if ((int) $row->lnStatus < 6) {
            continue;
        }

        $id = bin2hex($this->encrypter->encrypt($row->lnKode));
        $response = [];

        // detail layanan
        $modelDet = new MyModel('simlab_t_layanan_detil');
        $detil = $modelDet->getAllDataById(['detLnKode' => $row->lnKode]);

        $items = [];
        foreach ($detil as $d) {
            $items[] = $d->detLayanan ?? $d->detJenKode ?? '-';
        }
        $itemList = !empty($items) ? implode(', ', $items) : '-';

        // --- Kolom 1: Nama pemesan (besar) + tanggal | tipe (identity) ---
        $pemesanNama = '-';
        $tipe = '-';
        $tanggal = '-';

        // coba ambil dari userMap dulu
        if (isset($row->user_id) && isset($userMap[$row->user_id])) {
            $u = $userMap[$row->user_id];
            $pemesanNama = !empty($u->user_name) ? $u->user_name : ($row->lnOrangNama ?? '-');
            $tipe = !empty($u->user_identity) ? $u->user_identity : '-';
        } else {
            // fallback ke lnOrangNama atau field lain
            $pemesanNama = !empty($row->lnOrangNama) ? $row->lnOrangNama : ($row->lnPemesanNama ?? '-');
            // jika ada field pemesan identity di row
            $tipe = !empty($row->lnPemesanIdentity) ? $row->lnPemesanIdentity : ($row->lnJenisPemesan ?? '-');
        }

        if (!empty($row->lnTgl)) {
            $tanggal = date('d-m-Y H:i', strtotime($row->lnTgl));
        }

        $col1 = '
            <div style="line-height:1.3;">
                <span style="font-size:1rem; font-weight:600;">' . esc($pemesanNama) . '</span><br>
                <span style="font-size:0.9rem; color:#555;">' . esc($tanggal) . ' | ' . esc($tipe) . '</span>
            </div>';
        $response[] = $col1;

        // --- Kolom 2: Nama layanan (kamu sebelumnya menempatkan ini di kolom 2/3; aku simpan sebagai kolom layanan) ---
        $lihatDetailBtn = '<button type="button" class="btn btn-sm btn-info" title="Lihat Detail Item Layanan" onclick="loadDetail(\'' . $id . '\')">'
                    . '<i class="bi bi-eye"></i> Lihat </button>';
        // jika ingin menampilkan ringkasan nama layanan di samping tombol, bisa tambahkan $itemList
        $response[] = $lihatDetailBtn;

        // --- Kolom 3 : Nama orang (tetap disediakan jika kamu butuh) ---


        // ------------------ Kolom LHUS (lihat) ------------------
        // $lhusInfo = $this->detectLhusFile($row);
        // if ($lhusInfo['has']) {
        //     $response[] = '<button class="btn btn-sm btn-outline-primary" onclick="window.open(\'' . esc($lhusInfo['url']) . '\', \'_blank\')">'
        //                 . '<i class="bi bi-eye"></i> Lihat</button>';
        // } else {
        //     $response[] = '<button class="btn btn-sm btn-secondary" disabled>'
        //                 . '<i class="bi bi-file-earmark-text"></i> Lihat</button>';
        // }

        // Deteksi file LHU (detil_LHU)
        $lhuInfo = $this->detectLhuFile($row);

        // Tombol lihat LHU (jika ada)
        if ($lhuInfo['has']) {
            $btnViewLhu = '<button class="btn btn-sm btn-outline-primary me-1" onclick="window.open(\'' . esc($lhuInfo['url']) . '\', \'_blank\')">'
                        . '<i class="bi bi-eye"></i> Lihat</button>';
        } else {
            $btnViewLhu = '<button class="btn btn-sm btn-secondary me-1" disabled><i class="bi bi-eye"></i> Lihat</button>';
        }

        // Tombol upload: pastikan modal mendapatkan URL LHU (bukan LHUS)
        $uploadOnclick = "openUploadModal('{$id}', '" . ($lhuInfo['has'] ? esc($lhuInfo['url']) : '#') . "')";
        $btnUpload = '<button class="btn btn-sm btn-outline-primary" onclick="' . $uploadOnclick . '">'
                   . '<i class="bi bi-upload"></i> Upload</button>';

        $response[] = '<div class="d-flex align-items-center">' . $btnUpload . '</div>';

        // Kolom Status
        $response[] = $this->formatStatus($row->lnStatus);

        // ====== NEW: determine if accept/proses button should be allowed ======
        $kuisionerVal = null;
        if (isset($row->kuisioner)) {
            $kuisionerVal = (int) $row->kuisioner;
        } else {
            $kuFields = ['kuisioner', 'lnKuisioner', 'ln_kuisioner'];
            foreach ($kuFields as $kf) {
                if (isset($row->{$kf})) {
                    $kuisionerVal = (int) $row->{$kf};
                    break;
                }
            }
        }

        // Ambil status pembayaran terbaru dari tabel simlab_t_pembayaran untuk lnKode ini
        $bayarStatusVal = 0; // default not paid
        try {
            if (!empty($row->lnKode)) {
                $db = \Config\Database::connect();
                $pay = $db->table('simlab_t_pembayaran')
                          ->select('bayarStatus')
                          ->where('bayarLnKode', $row->lnKode)
                          ->orderBy('bayarKode', 'DESC')
                          ->limit(1)
                          ->get()
                          ->getRow();

                if ($pay && isset($pay->bayarStatus)) {
                    $bayarStatusVal = (int) $pay->bayarStatus;
                }
            }
        } catch (\Throwable $e) {
            $bayarStatusVal = 0;
        }

        $allowAccept = ($kuisionerVal === 1 && $bayarStatusVal === 1);

        // Kolom Aksi (kirim flag $allowAccept)
        $response[] = $this->aksiButton($id, $row->lnStatus, $allowAccept);

        $data[] = $response;
    }

    return $this->response->setJSON(["items" => $data]);
}



   public function detailList($id = null)
{
    if (!$id) {
        return $this->response->setJSON(['items' => []]);
    }

    // decrypt tolerant (hex or raw)
    try {
        $lnKode = $this->encrypter->decrypt(hex2bin($id));
    } catch (\Throwable $e) {
        try {
            $lnKode = $this->encrypter->decrypt($id);
        } catch (\Throwable $e2) {
            return $this->response->setJSON(['items' => []]);
        }
    }

    $db = \Config\Database::connect();

    // Ambil semua detil untuk detLnKode ini — gunakan hanya kolom yang ada
    $builder = $db->table('simlab_t_layanan_detil as d')
                  ->select('d.detKode, d.detUjiKode, d.detLayanan, d.detJumlah, d.detKeterangan, d.detil_LHUS, d.detil_LHU, d.detKetLn, d.detKetLhus')
                  ->where('d.detLnKode', $lnKode)
                  ->orderBy('d.detKode', 'ASC');

    $rows = $builder->get()->getResult();

    if (empty($rows)) {
        return $this->response->setJSON(['items' => []]);
    }

    // Prefetch ujiLayanan untuk detUjiKode yang ada (opsional)
    $ujiMap = [];
    $ujiKodeList = [];
    foreach ($rows as $r) {
        if (!empty($r->detUjiKode)) $ujiKodeList[] = $r->detUjiKode;
    }
    $ujiKodeList = array_values(array_unique($ujiKodeList));
    if (!empty($ujiKodeList)) {
        $ujis = $db->table('simlab_r_layanan_pengujian')
                   ->select('ujiKode, ujiLayanan')
                   ->whereIn('ujiKode', $ujiKodeList)
                   ->get()
                   ->getResult();
        foreach ($ujis as $u) $ujiMap[$u->ujiKode] = $u->ujiLayanan;
    }

    $items = [];
    $no = 1;
    foreach ($rows as $row) {
        // Layanan: prefer detLayanan, fallback to uji map
        $layanan = '-';
        if (!empty($row->detLayanan)) {
            $layanan = $row->detLayanan;
        } elseif (!empty($row->detUjiKode) && isset($ujiMap[$row->detUjiKode])) {
            $layanan = $ujiMap[$row->detUjiKode];
        }

        $jumlah = isset($row->detJumlah) ? (int)$row->detJumlah : 0;
        $ket = !empty($row->detKeterangan) ? esc($row->detKeterangan) : '-';

        // Deteksi file: hanya untuk tombol "Lihat" (jika ada)
        $fileUrl = null;
        $candidates = ['detil_LHUS', 'detil_LHU', 'detKetLhus', 'detKetLn'];
        foreach ($candidates as $cf) {
            if (isset($row->{$cf}) && trim((string)$row->{$cf}) !== '') {
                $val = trim((string)$row->{$cf});
                // multiple parts separated by ';;' -> check each
                if (strpos($val, ';;') !== false) {
                    $parts = array_filter(array_map('trim', explode(';;', $val)));
                    foreach ($parts as $p) {
                        if (preg_match('/^https?:\/\//i', $p)) { $fileUrl = $p; break 3; }
                        $p1 = FCPATH . 'uploads/lhus/' . ltrim($p, '/');
                        $p2 = FCPATH . 'uploads/lhu/' . ltrim($p, '/');
                        if (is_file($p1)) { $fileUrl = base_url('uploads/lhus/' . ltrim($p, '/')); break 3; }
                        if (is_file($p2)) { $fileUrl = base_url('uploads/lhu/' . ltrim($p, '/')); break 3; }
                    }
                } else {
                    if (preg_match('/^https?:\/\//i', $val)) { $fileUrl = $val; break; }
                    $p1 = FCPATH . 'uploads/lhus/' . ltrim($val, '/');
                    $p2 = FCPATH . 'uploads/lhu/' . ltrim($val, '/');
                    if (is_file($p1)) { $fileUrl = base_url('uploads/lhus/' . ltrim($val, '/')); break; }
                    if (is_file($p2)) { $fileUrl = base_url('uploads/lhu/' . ltrim($val, '/')); break; }
                }
            }
        }

        // Build "Lihat" button only (no status badge)
        if ($fileUrl) {
            $viewHtml = '<button class="btn btn-sm btn-outline-primary" onclick="window.open(\'' . esc($fileUrl) . '\', \'_blank\')"><i class="bi bi-eye"></i></button>';
        } else {
            $viewHtml = '<button class="btn btn-sm btn-secondary" disabled><i class="bi bi-file-earmark-text"></i> Lihat</button>';
        }

        // Items: No, Layanan, Jumlah, Keterangan, Lihat
        $items[] = [
            $no++,
            $layanan,
            $jumlah,
            $ket,
            $viewHtml
        ];
    }

    return $this->response->setJSON(['items' => $items]);
}



    public function upload()
    {
        $file = $this->request->getFile('lhu_file');
        $encId = $this->request->getPost('id');
        $detKode = $this->request->getPost('detKode');

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
            try {
                $lnKode = $this->encrypter->decrypt($encId);
            } catch (\Throwable $e2) {
                return $this->response->setJSON([
                    'res' => 'error',
                    'msg' => 'ID tidak valid',
                    'xname' => csrf_token(),
                    'xhash' => csrf_hash()
                ]);
            }
        }

        if (!($file && $file->isValid() && !$file->hasMoved())) {
            return $this->response->setJSON([
                'res' => 'error',
                'msg' => 'File tidak valid atau tidak dipilih',
                'xname' => csrf_token(),
                'xhash' => csrf_hash()
            ]);
        }

        // Upload file
        $uploadResult = $this->doUpload($file, 'lhu'); // simpan ke folder uploads/lhu
        if (!$uploadResult['status']) {
            return $this->response->setJSON([
                'res' => 'error',
                'msg' => $uploadResult['msg'],
                'xname' => csrf_token(),
                'xhash' => csrf_hash()
            ]);
        }

        $filename = $uploadResult['filename'];

        // Simpan ke tabel detil: kolom detil_LHU
        $db = \Config\Database::connect();

        try {
            if (!empty($detKode)) {
                // <-- remove old file for single detKode (if exists)
                try {
                    $oldRow = $db->table('simlab_t_layanan_detil')->select('detil_LHU')->where('detKode', $detKode)->get()->getRow();
                    if ($oldRow && !empty($oldRow->detil_LHU)) {
                        $oldPath = FCPATH . 'uploads/lhu/' . ltrim($oldRow->detil_LHU, '/');
                        if (is_file($oldPath)) {
                            @unlink($oldPath);
                        }
                    }
                } catch (\Throwable $e) {
                    // jika gagal membaca/hapus, lanjutkan (tidak fatal)
                }

                // update single detKode
                $ok = $db->table('simlab_t_layanan_detil')->where('detKode', $detKode)->update(['detil_LHU' => $filename]);

                if ($ok) {
                    return $this->response->setJSON([
                        'res' => true,
                        'msg' => 'File LHU berhasil diunggah ke detail (detKode).',
                        'url' => base_url('uploads/lhu/' . $filename),
                        'detKode' => $detKode,
                        'xname' => csrf_token(),
                        'xhash' => csrf_hash()
                    ]);
                } else {
                    // rollback (hapus file baru)
                    $savedPath = FCPATH . 'uploads/lhu/' . $filename;
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
                // <-- remove old files for all detils related to lnKode (if exist)
                try {
                    $oldRows = $db->table('simlab_t_layanan_detil')->select('detil_LHU')->where('detLnKode', $lnKode)->get()->getResult();
                    if ($oldRows) {
                        foreach ($oldRows as $r) {
                            if (!empty($r->detil_LHU)) {
                                $oldPath = FCPATH . 'uploads/lhu/' . ltrim($r->detil_LHU, '/');
                                if (is_file($oldPath)) {
                                    @unlink($oldPath);
                                }
                            }
                        }
                    }
                } catch (\Throwable $e) {
                    // ignore deletion errors and continue
                }

                // update semua detil yang berkaitan dengan lnKode
                $ok = $db->table('simlab_t_layanan_detil')->where('detLnKode', $lnKode)->update(['detil_LHU' => $filename]);

                if ($ok) {
                    return $this->response->setJSON([
                        'res' => true,
                        'msg' => 'File LHU berhasil diunggah ke semua detil terkait.',
                        'url' => base_url('uploads/lhu/' . $filename),
                        'xname' => csrf_token(),
                        'xhash' => csrf_hash()
                    ]);
                } else {
                    // rollback (hapus file baru)
                    $savedPath = FCPATH . 'uploads/lhu/' . $filename;
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
            // rollback file
            $savedPath = FCPATH . 'uploads/lhu/' . $filename;
            if (is_file($savedPath)) @unlink($savedPath);

            return $this->response->setJSON([
                'res' => 'error',
                'msg' => 'Error saat menyimpan ke detil: ' . $e->getMessage(),
                'xname' => csrf_token(),
                'xhash' => csrf_hash()
            ]);
        }
    }

    /**
     * doUpload: validasi & pindahkan file ke uploads/{folder}
     * default folder: 'lhu' ; untuk LHUS gunakan 'lhus' (dipakai di detect saja)
     */
    function doUpload($file, $folder = 'lhu')
    {
        if (!($file && $file->isValid() && !$file->hasMoved())) {
            return ['status' => false, 'msg' => 'File tidak valid atau sudah dipindahkan'];
        }

        $allowedExt  = ['jpg', 'jpeg', 'png', 'pdf', 'doc', 'docx', 'xls', 'xlsx'];
        $allowedMime = [
            'image/jpeg', 'image/png',
            'application/pdf',
            'application/msword',
            'application/vnd.openxmlformats-officedocument.wordprocessingml.document',
            'application/vnd.ms-excel',
            'application/vnd.openxmlformats-officedocument.spreadsheetml.sheet'
        ];

        $ext  = strtolower($file->getClientExtension());
        $tmpName = $file->getTempName();

        if (!is_file($tmpName)) {
            return ['status' => false, 'msg' => 'File sementara tidak ditemukan'];
        }

        $detectedMime = null;
        if (function_exists('finfo_open')) {
            $finfo = finfo_open(FILEINFO_MIME_TYPE);
            $detectedMime = finfo_file($finfo, $tmpName);
            finfo_close($finfo);
        } else {
            $detectedMime = $file->getClientMimeType();
        }

        if (!in_array($ext, $allowedExt) || !in_array($detectedMime, $allowedMime)) {
            return ['status' => false, 'msg' => 'Format file tidak diperbolehkan'];
        }

        // Jika file gambar, periksa gambar asli
        if (in_array($ext, ['jpg', 'jpeg', 'png'])) {
            if (@getimagesize($tmpName) === false) {
                return ['status' => false, 'msg' => 'File bukan gambar asli'];
            }
        }

        // Validasi ukuran file (max 5MB)
        if ($file->getSize() > 5 * 1024 * 1024) {
            return ['status' => false, 'msg' => 'Ukuran file maksimal 5MB'];
        }

        // Generate filename aman
        try {
            $rand = bin2hex(random_bytes(8));
        } catch (\Exception $e) {
            $rand = bin2hex(openssl_random_pseudo_bytes(8));
        }
        $filename = time() . '_' . $rand . '.' . $ext;

        $path = FCPATH . 'uploads/' . $folder;
        if (!is_dir($path)) {
            @mkdir($path, 0755, true);
        }

        try {
            $file->move($path, $filename, true);
            $fullPath = $path . DIRECTORY_SEPARATOR . $filename;

            if (is_file($fullPath)) {
                @chmod($fullPath, 0644);
            }

        } catch (\Exception $e) {
            return ['status' => false, 'msg' => 'Gagal memindahkan file: ' . $e->getMessage()];
        }

        return ['status' => true, 'filename' => $filename];
    }

    /**
     * Detect LHUS file pada row atau pada tabel detil (kolom detil_LHUS)
     * mengembalikan ['has' => bool, 'url' => string]
     */
    private function detectLhusFile($row)
    {
        // cek field di row utama dulu (jika ada)
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

        // cek di tabel detil (khusus LHUS: hanya cek kolom detil_LHUS dan varian terkait)
        if (isset($row->lnKode) && !empty($row->lnKode)) {
            try {
                $modelDet = new MyModel('simlab_t_layanan_detil');
                $detils = $modelDet->getAllDataById(['detLnKode' => $row->lnKode]);
                foreach ($detils as $d) {
                    // HANYA kolom yang relevan untuk LHUS
                    $detFields = ['detil_LHUS', 'detLhus', 'detFileLhus', 'det_file_lhus'];
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

    /**
     * Detect LHU file pada row atau pada tabel detil (kolom detil_LHU)
     * mengembalikan ['has' => bool, 'url' => string]
     */
    private function detectLhuFile($row)
    {
        // cek kemungkinan field di row utama
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
                    // HANYA kolom yang relevan untuk LHU
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

    private function formatStatus($status)
    {
        switch ($status) {
            case 6: return '<span class="badge bg-primary">Memproses LHU</span>';
            case 7: return '<span class="badge bg-success">LHU Disetujui</span>';
            default: return '<span class="badge bg-dark">Unknown</span>';
        }
    }

      private function aksiButton($id, $status, $allowAccept = true)
{
    $btn = '<div id="' . $id . '" class="float-end">';

    // tombol proses hanya muncul jika status = 6
    if ($status == 6) {
        if ($allowAccept) {
            // tombol proses aktif (seperti semula)
            $btn .= '<span class="text-success btn-action" title="Proses" onclick="prosesItem(event)">'
                 . '<i class="bi bi-check2-circle"></i>'
                 . '</span> ';
        } else {
            // tampilkan tombol proses TETAP dengan icon yang sama tapi nonaktif
            $btn .= '<span class="text-muted btn-action" title="Tidak dapat di-accept: Pastikan kuisioner sudah diisi dan pembayaran telah dikonfirmasi" style="cursor:not-allowed;opacity:0.5;">'
                 . '<i class="bi bi-check2-circle"></i>'
                 . '</span> ';
        }

        $btn .= '<label class="divider">|</label> ';
    }

    // tombol hapus selalu ada
    // $btn .= '<span class="text-danger btn-action" title="Hapus" onclick="deleteItem(event)">'
    //      . '<i class="bi bi-trash"></i>'
    //      . '</span>';

    // $btn .= '</div>';
    return $btn;
}



    // proses ubah status ke 7
    public function proses($id)
    {
        try {
            $kode = $this->encrypter->decrypt(hex2bin($id));
        } catch (\Exception $e) {
            return $this->response->setJSON([
                'res'   => false,
                'msg'   => 'ID tidak valid',
                'xname' => csrf_token(),
                'xhash' => csrf_hash()
            ]);
        }

        // Ambil row layanan untuk verifikasi kuisioner
        $model = new MyModel($this->table);
        $row = $model->getDataById($this->id, $kode);

        if (!$row) {
            return $this->response->setJSON([
                'res'   => false,
                'msg'   => 'Data layanan tidak ditemukan',
                'xname' => csrf_token(),
                'xhash' => csrf_hash()
            ]);
        }

        $kuisionerVal = isset($row->kuisioner) ? (int) $row->kuisioner : 0;

        // cek bayarStatus di tabel pembayaran (ambil yang terbaru)
        $bayarStatusVal = 0;
        try {
            $db = \Config\Database::connect();
            $pay = $db->table('simlab_t_pembayaran')
                      ->select('bayarStatus')
                      ->where('bayarLnKode', $kode)
                      ->orderBy('bayarKode', 'DESC')
                      ->limit(1)
                      ->get()
                      ->getRow();

            if ($pay && isset($pay->bayarStatus)) {
                $bayarStatusVal = (int) $pay->bayarStatus;
            }
        } catch (\Throwable $e) {
            $bayarStatusVal = 0;
        }

        // Validasi: harus kuisioner == 1 dan bayarStatus == 1
        if (!($kuisionerVal === 1 && $bayarStatusVal === 1)) {
            return $this->response->setJSON([
                'res'   => false,
                'msg'   => 'Tidak dapat memproses: pastikan kuisioner telah diisi dan pembayaran sudah konfirmasi.',
                'kuisioner' => $kuisionerVal,
                'bayarStatus' => $bayarStatusVal,
                'xname' => csrf_token(),
                'xhash' => csrf_hash()
            ]);
        }

        // Semua ok, update status menjadi 7 (LHU Disetujui)
        $res = $model->updateData(['lnStatus' => 7], $this->id, $kode);

        return $this->response->setJSON([
            'res'   => $res,
            'xname' => csrf_token(),
            'xhash' => csrf_hash()
        ]);
    }

    // hapus data
    public function delete($id)
    {
        try {
            $kode = $this->encrypter->decrypt(hex2bin($id));
        } catch (\Exception $e) {
            return $this->response->setJSON([
                'res'   => false,
                'msg'   => 'ID tidak valid',
                'xname' => csrf_token(),
                'xhash' => csrf_hash()
            ]);
        }

        $model = new MyModel($this->table);
        $res   = $model->deleteData($this->id, $kode);

        return $this->response->setJSON([
            'res'   => $res,
            'xname' => csrf_token(),
            'xhash' => csrf_hash()
        ]);
    }
}
