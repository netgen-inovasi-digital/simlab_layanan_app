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
        $model = new MyModel($this->table);
        $data  = [];

        // Ambil semua data dari tabel utama dan urutkan berdasarkan tanggal DESC
        $list = $model->getAllDataWithOrder(['lnTgl' => 'DESC']);

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
            $modelDet = new MyModel('simlab_t_layanan_detil');
            $detil = $modelDet->getAllDataById(['detLnKode' => $row->lnKode]);

            $items = [];
            foreach ($detil as $d) {
                // ambil nama layanan jika ada, fallback ke kode/jenis
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

            $lhusInfo = $this->detectLhusFile($row);
            if ($lhusInfo['has']) {
                // jika ada file, tampilkan tombol lihat file
                $btn = '<a href="' . esc($lhusInfo['url']) . '" target="_blank" class="btn btn-sm btn-outline-primary" title="Lihat LHUS">'
                     . '<i class="bi bi-file-earmark-text"></i> Lihat File</a>';
            } else {
                // jika tidak ada file, tampilkan tombol unggah
                $btn = '<button class="btn btn-sm btn-outline-secondary" title="Unggah LHUS" onclick="openUploadModal(\'' . $id . '\')">'
                     . '<i class="bi bi-upload"></i> Unggah File</button>';
            }
            $response[] = $btn;

            // Kolom 5: Status
            $response[] = $this->formatStatus($row->lnStatus);

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
                    return ['has' => true, 'url' => $raw];
                }

                $possibleUrl = base_url('uploads/lhus/' . ltrim($raw, '/'));
                return ['has' => true, 'url' => $possibleUrl];
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
                            $possibleUrl = base_url('uploads/lhus/' . ltrim($raw, '/'));
                            return ['has' => true, 'url' => $possibleUrl];
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
     * submit() = aksi "kirim LHUS"
     * - Nama method sesuai permintaan: submit
     * - Memeriksa bahwa file LHUS sudah ada di tabel detil dan di storage sebelum menandai sebagai 'dikirim'
     * - Tidak memindahkan atau mengunggah file pada langkah ini
     */
    public function submit()
    {
        if (!$this->request->isAJAX() || $this->request->getMethod() !== 'post') {
            return $this->response->setStatusCode(400)->setJSON([
                'res' => 'error',
                'msg' => 'Invalid request',
                'xname' => csrf_token(),
                'xhash' => csrf_hash()
            ]);
        }

        $encId = $this->request->getPost('id'); // id terenkripsi (hex)
        if (empty($encId)) {
            return $this->response->setJSON([
                'res' => 'error',
                'msg' => 'ID missing',
                'xname' => csrf_token(),
                'xhash' => csrf_hash()
            ]);
        }

        try {
            $lnKode = $this->encrypter->decrypt(hex2bin($encId));
        } catch (\Throwable $e) {
            return $this->response->setJSON([
                'res' => 'error',
                'msg' => 'Invalid ID',
                'xname' => csrf_token(),
                'xhash' => csrf_hash()
            ]);
        }

        $model = new MyModel($this->table);
        $row = $model->getDataById($this->id, $lnKode);

        if (!$row) {
            return $this->response->setJSON([
                'res' => 'error',
                'msg' => 'Record tidak ditemukan',
                'xname' => csrf_token(),
                'xhash' => csrf_hash()
            ]);
        }

        // Periksa apakah ada minimal satu detil yang memiliki detil_LHUS terisi dan file fisik ada
        try {
            $modelDet = new MyModel('simlab_t_layanan_detil');
            $detils = $modelDet->getAllDataById(['detLnKode' => $lnKode]);

            $foundFile = false;
            foreach ($detils as $d) {
                if (!empty($d->detil_LHUS)) {
                    $filePath = FCPATH . 'uploads/lhus/' . $d->detil_LHUS;
                    if (is_file($filePath)) {
                        $foundFile = true;
                        break;
                    }
                }
            }

            if (!$foundFile) {
                return $this->response->setJSON([
                    'res' => 'error',
                    'msg' => 'Tidak ada file LHUS yang diupload pada detail. Silakan unggah terlebih dahulu.',
                    'xname' => csrf_token(),
                    'xhash' => csrf_hash()
                ]);
            }
        } catch (\Throwable $e) {
            return $this->response->setJSON([
                'res' => 'error',
                'msg' => 'Error saat memeriksa detil LHUS: ' . $e->getMessage(),
                'xname' => csrf_token(),
                'xhash' => csrf_hash()
            ]);
        }

        // Tandai record sebagai telah dikirim (lnKirimLhus = 1) dan simpan timestamp lnKirimLhusAt (jika kolom ada)
        $dataUpdate = [
            'lnKirimLhus'   => 1,
            'lnKirimLhusAt' => date('Y-m-d H:i:s')
        ];

        try {
            $res = $model->updateData($dataUpdate, $this->id, $lnKode);
            if ($res) {
                return $this->response->setJSON([
                    'res' => true,
                    'msg' => 'File LHUS berhasil dikirim (ditandai di database).',
                    'xname' => csrf_token(),
                    'xhash' => csrf_hash()
                ]);
            } else {
                return $this->response->setJSON([
                    'res' => 'error',
                    'msg' => 'Gagal memperbarui status kirim di database.',
                    'xname' => csrf_token(),
                    'xhash' => csrf_hash()
                ]);
            }
        } catch (\Throwable $e) {
            return $this->response->setJSON([
                'res' => 'error',
                'msg' => 'Error: ' . $e->getMessage(),
                'xname' => csrf_token(),
                'xhash' => csrf_hash()
            ]);
        }
    }

    /**
     * doUpload() helper mirip Profilpw
     * - Validasi ekstensi/mime/ukuran
     * - Menyimpan file di uploads/lhus
     */
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
        $mime = $file->getMimeType();

        if (!in_array($ext, $allowedExt) || !in_array($mime, $allowedMime)) {
            return ['status' => false, 'msg' => 'Format file tidak diperbolehkan'];
        }

        // Jika file gambar, periksa gambar asli
        if (in_array($ext, ['jpg', 'jpeg', 'png'])) {
            if (@getimagesize($file->getTempName()) === false) {
                return ['status' => false, 'msg' => 'File bukan gambar asli'];
            }
        }

        // Validasi ukuran file (max 5MB default)
        if ($file->getSize() > 5 * 1024 * 1024) {
            return ['status' => false, 'msg' => 'Ukuran file maksimal 5MB'];
        }

        // Simpan file ke folder uploads/lhus 
        try {
            $filename = time() . bin2hex(random_bytes(6)) . '.' . $ext;
        } catch (\Exception $e) {
            $filename = time() . '_'. bin2hex(openssl_random_pseudo_bytes(6)) . '.' . $ext;
        }

        $path = FCPATH . 'uploads/lhus';
        if (!is_dir($path)) {
            @mkdir($path, 0755, true);
        }

        $file->move($path, $filename, true);

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
