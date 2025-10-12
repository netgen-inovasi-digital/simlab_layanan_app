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
    $data  = [];

    // Ambil detil berdasarkan detPenyelia = user_id (prioritas)
    $detilList = $modelDet->getAllDataById(['detPenyelia' => $user_id]);

    // Jika kosong ambil semua baris yang user terkait (gabungan kedua kolom)
    if (empty($detilList)) {
        $db = \Config\Database::connect();
        $builder = $db->table('simlab_t_layanan_detil as d');
        $builder->select('d.detLnKode');
        $builder->groupStart();
        $builder->where('d.detPenyelia', $user_id);
        $builder->orWhere('d.detManajerTeknis', $user_id);
        $builder->groupEnd();
        $rows = $builder->get()->getResult();
        $detilList = $rows;
    }

    // Ambil lnKode yang sesuai dari tabel detil — robust untuk array objek/array
    $lnKodeList = [];
    foreach ($detilList as $item) {
        if (is_array($item) && isset($item['detLnKode'])) {
            $lnKodeList[] = $item['detLnKode'];
        } elseif (is_object($item) && isset($item->detLnKode)) {
            $lnKodeList[] = $item->detLnKode;
        }
    }

    $lnKodeList = array_values(array_unique(array_filter($lnKodeList, function ($v) {
        return $v !== null && $v !== '' && $v !== 0;
    })));

    if (empty($lnKodeList)) {
        return $this->response->setJSON(["items" => []]);
    }

    $db = \Config\Database::connect();
    $builder = $db->table('simlab_t_layanan as l');
    $builder->select('l.*');
    $builder->whereIn('l.lnKode', $lnKodeList);

    // Tambahan: jangan ambil baris dengan lnStatus
    $builder->where('l.lnStatus !=', 2);

    $builder->orderBy('l.lnTgl', 'DESC');
    $list = $builder->get()->getResult();

    // Kelompokkan berdasarkan status 0-9
    $grouped = [];
    for ($i = 0; $i <= 9; $i++) {
        $grouped[$i] = [];
    }

    foreach ($list as $row) {
        $status = (int) $row->lnStatus;
        if (!isset($grouped[$status])) {
            $grouped[$status] = [];
        }
        $grouped[$status][] = $row;
    }

    // Urutkan data berdasarkan status 0 → 9
    $finalList = [];
    for ($i = 0; $i <= 9; $i++) {
        $finalList = array_merge($finalList, $grouped[$i]);
    }

    $no = 1; // nomor urut
    foreach ($finalList as $row) {

        if ((int) $row->lnStatus < 4) {
            continue;
        }

        $id = bin2hex($this->encrypter->encrypt($row->lnKode));
        $response = [];

        // Ambil item layanan dari tabel detail
        $detil = $modelDet->getAllDataById(['detLnKode' => $row->lnKode]);

        $items = [];
        foreach ($detil as $d) {
            if (isset($d->detLayanan) && !empty($d->detLayanan)) {
                $items[] = $d->detLayanan;
            } elseif (isset($d->detJenKode) && !empty($d->detJenKode)) {
                $items[] = $d->detJenKode;
            } else {
                $items[] = '-';
            }
        }
        $itemList = !empty($items) ? implode(', ', $items) : '-';

        $noInvoiceTgl = '<div>'
                      . ($row->lnNoTransaksi ?? '-') . '<br>'
                      . (!empty($row->lnTgl) ? date('d-m-Y', strtotime($row->lnTgl)) : '-') 
                      . '</div>';
        $response[] = $noInvoiceTgl;

        $response[] = '<div>' . $itemList . '</div>';

        // Deteksi apakah LHUS file ada
        $lhusInfo = $this->detectLhusFile($row);

        // Default onclick handler
        $fileUrlEscaped = $lhusInfo['has'] ? esc($lhusInfo['url']) : '#';
        $uploadOnclick  = 'openUploadModal(\'' . $id . '\', \'' . $fileUrlEscaped . '\')';

        // Tombol tunggal: berubah teks sesuai kondisi
        if ($lhusInfo['has']) {
            $btnUpload = '<button class="btn btn-sm btn-outline-primary me-1" title="Lihat File" onclick="' . $uploadOnclick . '">'
                       . '<i class="bi bi-eye"></i> Lihat</button>';
        } else {
            $btnUpload = '<button class="btn btn-sm btn-outline-secondary me-1" title="Unggah File" onclick="' . $uploadOnclick . '">'
                       . '<i class="bi bi-upload"></i> Unggah</button>';
        }

        // Kolom LHUS (Tinjau) → hanya 1 tombol ini
        $response[] = $btnUpload;

        // Kolom Status
        $response[] = $this->formatStatus($row->lnStatus);

        // Kolom Aksi
        $lihatDetailBtn = '
            <a href="javascript:void(0)" onclick="loadDetail(\'' . $id . '\')" 
            class="btn btn-sm btn-info me-1" title="Lihat Detail">
                <i class="bi bi-eye"></i>
            </a>
        ';

        if ($lhusInfo['has']) {
            $sendBtn = '<span class="text-success btn-action" title="Kirim LHUS" onclick="confirmApprove(event, \'' . $id . '\')">'
                     . '<i class="bi bi-check-circle"></i></span>';
        } else {
            $sendBtn = '<span class="text-muted btn-action" title="Tidak ada file LHUS">'
                     . '<i class="bi bi-check-circle"></i></span>';
        }

        $response[] = $lihatDetailBtn . ' ' . $sendBtn;

        $data[] = $response;
        $no++;
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




    private function detectLhusFile($row)
    {
        $possibleFields = [
            'lnLhus', 'lnLHUS', 'lnFileLhus', 'ln_file_lhus', 'lhus_file', 'ln_lhus', 'ln_lhus_file', 'ln_file_lhus_path'
        ];

        // cek field di tabel utama dulu (meskipun kita sekarang menyimpan di detil)
        foreach ($possibleFields as $f) {
            if (isset($row->{$f}) && !empty($row->{$f})) {
                $raw = $row->{$f};

                if (preg_match('/^https?:\/\//i', $raw)) {
                    // jika URL absolute, anggap valid (tidak bisa cek via filesystem)
                    return ['has' => true, 'url' => $raw];
                }

                // Buat path fisik dan cek file ada
                $possiblePath = FCPATH . 'uploads/lhus/' . ltrim($raw, '/');
                if (is_file($possiblePath)) {
                    $possibleUrl = base_url('uploads/lhus/' . ltrim($raw, '/'));
                    return ['has' => true, 'url' => $possibleUrl];
                }

                // jika tidak ada di filesystem, jangan klaim ada
                return ['has' => false, 'url' => '#'];
            }
        }

        // Cek di tabel detil (kolom detil_LHUS)
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
                // ignore, kembalikan tidak ada
            }
        }

        return ['has' => false, 'url' => '#'];
    }

 public function submit($idParam = null)
{
    // terima POST atau URL segment
    $encId = $this->request->getPost('id') ?? $idParam ?? $this->request->uri->getSegment(3);

    if (empty($encId)) {
        return $this->response->setJSON([
            'res' => 'error',
            'msg' => 'ID missing',
            'xname' => csrf_token(),
            'xhash' => csrf_hash()
        ]);
    }

    // coba dekripsi tolerant (hex or direct)
    $lnKode = null;
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
        $res = $model->updateData(['lnStatus' => 5], $this->id, $lnKode);
        return $this->response->setJSON([
            'res' => $res ? true : false,
            'msg' => $res ? 'Lhus terkirim' : 'Gagal terkirim',
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

    function doUpload($file)
    {
        // Pastikan file valid dan belum dipindahkan
        if (!($file && $file->isValid() && !$file->hasMoved())) {
            return ['status' => false, 'msg' => 'File tidak valid atau sudah dipindahkan'];
        }

        // Validasi tipe file (ekstensi & MIME)
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

        // Deteksi MIME yang lebih andal
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

        // Validasi ukuran file (max 5MB default)
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

        $path = FCPATH . 'uploads/lhus';
        if (!is_dir($path)) {
            @mkdir($path, 0755, true);
        }

        try {
            $file->move($path, $filename, true);
            $fullPath = $path . DIRECTORY_SEPARATOR . $filename;

            // set permission file lebih ketat
            if (is_file($fullPath)) {
                @chmod($fullPath, 0644);
            }

        } catch (\Exception $e) {
            return ['status' => false, 'msg' => 'Gagal memindahkan file: ' . $e->getMessage()];
        }

        return ['status' => true, 'filename' => $filename];
    }

    /**
     * upload() = endpoint untuk mengunggah file LHUS
     * - Behavior: upload file -> simpan nama file ke kolom detil_LHUS pada tabel simlab_t_layanan_detil
     * - Parameter POST:
     *    - id (wajib): lnKode terenkripsi (hex)
     *    - detKode (opsional): jika diberikan, update hanya baris detKode tersebut; jika tidak, update semua detil dengan detLnKode = lnKode
     */
    public function upload()
    {
        // menerima file input name 'lhus_file' dan post 'id' (terenkripsi hex)
        $file = $this->request->getFile('lhus_file');
        $encId = $this->request->getPost('id');
        $detKode = $this->request->getPost('detKode'); // optional

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

        // Upload file ke storage
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

        // Simpan ke tabel detil: kolom detil_LHUS
        $modelDet = new MyModel('simlab_t_layanan_detil');
        $db = \Config\Database::connect();

        try {
            if (!empty($detKode)) {
                // update single detKode
                $ok = $db->table('simlab_t_layanan_detil')->where('detKode', $detKode)->update(['detil_LHUS' => $filename]);

                if ($ok) {
                    return $this->response->setJSON([
                        'res' => true,
                        'msg' => 'File LHUS berhasil diunggah ke detail (detKode).',
                        'url' => base_url('uploads/lhus/' . $filename),
                        'detKode' => $detKode,
                        'xname' => csrf_token(),
                        'xhash' => csrf_hash()
                    ]);
                } else {
                    // rollback file
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
                // update semua detil yang berkaitan dengan lnKode
                $ok = $db->table('simlab_t_layanan_detil')->where('detLnKode', $lnKode)->update(['detil_LHUS' => $filename]);

                if ($ok) {
                    return $this->response->setJSON([
                        'res' => true,
                        'msg' => 'File LHUS berhasil diunggah ke semua detil terkait.',
                        'url' => base_url('uploads/lhus/' . $filename),
                        'xname' => csrf_token(),
                        'xhash' => csrf_hash()
                    ]);
                } else {
                    // rollback file
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
            // rollback file
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

    private function formatStatus($status)
    {
        switch ($status) {
            case 0: return '<span class="badge bg-secondary">Draft</span>';
            case 1: return '<span class="badge bg-warning">In Review (Manajer)</span>';
            case 2: return '<span class="badge bg-danger">Ditolak</span>';
            case 3: return '<span class="badge bg-info">In Review (Admin)</span>';
            case 4: return '<span class="badge bg-primary">Menunggu Hasil Uji</span>';
            case 5: return '<span class="badge bg-primary">Menunggu Verifikasi Manajer Teknis</span>';
            case 6: return '<span class="badge bg-success">LHUS Disetujui</span>';
            case 7: return '<span class="badge bg-primary">Memproses LHU</span>';
            case 8: return '<span class="badge bg-success">LHU Disetujui</span>';
            case 9: return '<span class="badge bg-dark">Pengujian Selesai</span>';
            default: return '<span class="badge bg-dark">Unknown</span>';
        }
    }
}
