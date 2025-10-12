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

            // Kolom 1: No Invoice + Tanggal
            $response[] = '<div>'
                        . ($row->lnNoTransaksi ?? '-') . '<br>'
                        . (!empty($row->lnTgl) ? date('d-m-Y', strtotime($row->lnTgl)) : '-')
                        . '</div>';

            //Kolom 3 : Nama orang
            $response[] = $row->lnOrangNama ?? '-';

            // Kolom 2: Nama layanan
            $lihatDetailBtn = '<button type="button" class="btn btn-sm btn-info" title="Lihat Detail Item Layanan" onclick="loadDetail(\'' . $id . '\')">'
                        . '<i class="bi bi-eye"></i> Lihat Detail Layanan</button>';
            $response[] = $lihatDetailBtn; 

            // ------------------ Kolom LHUS (lihat) ------------------
            // (ambil dari detil_LHUS atau kemungkinan field pada row utama)
            $lhusInfo = $this->detectLhusFile($row);
            if ($lhusInfo['has']) {
                // buka di tab baru 
                $response[] = '<button class="btn btn-sm btn-outline-primary" onclick="window.open(\'' . esc($lhusInfo['url']) . '\', \'_blank\')">'
                            . '<i class="bi bi-eye"></i> Lihat</button>';
            } else {
                $response[] = '<button class="btn btn-sm btn-secondary" disabled>'
                            . '<i class="bi bi-file-earmark-text"></i> Lihat</button>';
            }

           
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
            // openUploadModal(encId, fileUrl, detKode) — fileUrl sekarang adalah URL LHU existing
            $uploadOnclick = "openUploadModal('{$id}', '" . ($lhuInfo['has'] ? esc($lhuInfo['url']) : '#') . "')";
            $btnUpload = '<button class="btn btn-sm btn-outline-primary" onclick="' . $uploadOnclick . '">'
                       . '<i class="bi bi-upload"></i> Upload</button>';

        
            $response[] = '<div class="d-flex align-items-center">' . $btnUpload . '</div>';

            // Kolom Status
            $response[] = $this->formatStatus($row->lnStatus);

            // Kolom Aksi
            $response[] = $this->aksiButton($id, $row->lnStatus);

            $data[] = $response;
        }

        return $this->response->setJSON(["items" => $data]);
    }

    public function detailList($id)
{
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

    // Ambil detil layanan sesuai detLnKode
    $model = new MyModel('simlab_t_layanan_detil d');
    $joins = [
        'simlab_r_layanan_pengujian lp' => 'lp.ujiKode = d.detUjiKode',
        'simlab_r_parameter p'          => 'p.paraKode = lp.ujiParaKode',
        'simlab_r_alat a'               => 'a.alatKode = lp.ujiAlatKode',
    ];
    $where = ['d.detLnKode' => $lnKode];

    $select = "
        d.detUjiKode,
        lp.ujiLayanan,
        p.paraNama,
        a.alatNama,
        d.detBiaya,
        d.detKeterangan
    ";

    try {
        $list = $model->getAllDataWithJoinWhereOrder($joins, $where, ['d.detUjiKode' => 'ASC'], $select);
    } catch (\Throwable $e) {
        // jika query error, kembalikan array kosong
        return $this->response->setJSON(['items' => []]);
    }

    $data = [];
    $no = 1;
    foreach ($list as $row) {
        $layanan = isset($row->ujiLayanan) ? $row->ujiLayanan : '-';
        if (isset($row->paraNama) && !empty($row->paraNama)) {
            $layanan .= ' (' . $row->paraNama . ')';
        }

        $biaya = isset($row->detBiaya) ? 'Rp ' . number_format($row->detBiaya, 0, ',', '.') : '-';
        $ket   = isset($row->detKeterangan) && !empty($row->detKeterangan) ? $row->detKeterangan : '-';

        $response = [];
        $response[] = $no++;
        $response[] = $layanan;
        $response[] = $biaya;
        $response[] = $ket;

        $data[] = $response;
    }

    return $this->response->setJSON(['items' => $data]);
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

    private function aksiButton($id, $status)
    {
        $btn = '<div id="' . $id . '" class="float-end">';

        // tombol proses hanya muncul jika status = 6
        if ($status == 6) {
            $btn .= '<span class="text-success btn-action" title="Proses" onclick="prosesItem(event)">'
                 . '<i class="bi bi-check2-circle"></i>'
                 . '</span> ';
            $btn .= '<label class="divider">|</label> ';
        }

        // tombol hapus selalu ada
        $btn .= '<span class="text-danger btn-action" title="Hapus" onclick="deleteItem(event)">'
             . '<i class="bi bi-trash"></i>'
             . '</span>';

        $btn .= '</div>';
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

        $model = new MyModel($this->table);
        $res   = $model->updateData(['lnStatus' => 7], $this->id, $kode);

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
