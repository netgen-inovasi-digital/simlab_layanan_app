<?php
namespace Modules\Pelaksanaan\Controllers;

use App\Controllers\BaseController;
use App\Models\MyModel;

class Pelaksanaan extends BaseController
{
    private $table = 'simlab_t_layanan';
    private $id = 'lnKode';


    public function index()
    {
        $session = session();
        $user_id = $session->get('id_user');

        $modelUser = new MyModel('simlab_account_users');

        $data = [
            'title' => 'Data Pelaksanaan',
            'user' => $modelUser->getDataById('user_id', $user_id),
        ];

        return view('Modules\Pelaksanaan\Views\v_pelaksanaan', $data);
    }

    public function dataList()
    {
        $model = new MyModel($this->table);
        $data = [];

        $list = $model->getAllDataWithOrder(['lnTgl' => 'DESC']);

        if (empty($list)) {
            return $this->response->setJSON(["items" => []]);
        }

        // [FILTER] ?lnStatus=7,6,uploaded,pending
        $lnStatusParam = (string) ($this->request->getGet('lnStatus') ?? '');
        $wantUploaded = false;   // status 6 + sudah upload
        $wantPending = false;   // status 6 + belum upload
        $statusNums = [];

        if ($lnStatusParam !== '') {
            $parts = preg_split('/[,\s]+/', $lnStatusParam, -1, PREG_SPLIT_NO_EMPTY);
            foreach ($parts as $p) {
                $tp = strtolower(trim($p));
                if (in_array($tp, ['uploaded', 'terunggah'], true)) {
                    $wantUploaded = true;
                    continue;
                }
                if (in_array($tp, ['pending', 'belumupload', 'belum_upload'], true)) {
                    $wantPending = true;
                    continue;
                }
                if (is_numeric($tp)) {
                    $statusNums[] = (int) $tp;
                }
            }
            $statusNums = array_values(array_unique($statusNums));
        }

        // prefetch user info
        $userIds = [];
        foreach ($list as $r) {
            if (!empty($r->user_id))
                $userIds[] = $r->user_id;
        }
        $userMap = [];
        if (!empty($userIds)) {
            $db = \Config\Database::connect();
            $users = $db->table('simlab_account_users')
                ->select('user_id, user_name, user_identity, user_email')
                ->whereIn('user_id', array_values(array_unique($userIds)))
                ->get()->getResult();
            foreach ($users as $u)
                $userMap[$u->user_id] = $u;
        }

        foreach ($list as $row) {
            if ((int) $row->lnStatus < 6)
                continue;

            $id = bin2hex(service('encrypter')->encrypt($row->lnKode));
            // $encrypted_id = bin2hex(service('encrypter')->encrypt($row->bayarKode));
            $response = [];

            // kolom pemesan
            $pemesanNama = '-';
            $tipe = '-';
            $tanggal = '-';
            if (isset($row->user_id, $userMap[$row->user_id])) {
                $u = $userMap[$row->user_id];
                $pemesanNama = !empty($u->user_name) ? $u->user_name : ($row->lnOrangNama ?? '-');
                $tipe = !empty($u->user_identity) ? $u->user_identity : '-';
            } else {
                $pemesanNama = !empty($row->lnOrangNama) ? $row->lnOrangNama : ($row->lnPemesanNama ?? '-');
                $tipe = !empty($row->lnPemesanIdentity) ? $row->lnPemesanIdentity : ($row->lnJenisPemesan ?? '-');
            }
            if (!empty($row->lnTgl))
                $tanggal = date('d-m-Y H:i', strtotime($row->lnTgl));

            $response[] =
                '<div style="line-height:1.3;">
                    <span style="font-size:1rem; font-weight:600;">' . esc($pemesanNama) . '</span><br>
                    <span style="font-size:0.9rem; color:#555;">' . esc($tanggal) . ' | ' . esc($tipe) . '</span>
                </div>';

            // kolom LHUS (lihat)
            $response[] = '<button type="button" class="btn btn-sm btn-info" title="Lihat Detail Item Layanan" onclick="loadDetail(\'' . $id . '\')">
                                <i class="bi bi-eye"></i> Lihat File
                           </button>';

            // deteksi file LHU
            $lhuInfo = $this->detectLhuFile($row);

            // kolom status (single badge)
            $response[] = '<div id="status-cell-' . $id . '">' . $this->formatStatus($row->lnStatus, $lhuInfo['has']) . '</div>';

            // ❗️RULE BARU: boleh accept jika status=6 DAN file LHU sudah ada
            $allowAccept = ((int) $row->lnStatus === 6 && $lhuInfo['has'] === true);

            // kolom aksi
            $response[] = $this->aksiButton($id, $row->lnStatus, $allowAccept, $lhuInfo);

            // terapkan filter
            if ($wantUploaded || $wantPending || !empty($statusNums)) {
                $match = false;
                if (!empty($statusNums) && in_array((int) $row->lnStatus, $statusNums, true))
                    $match = true;
                if ((int) $row->lnStatus === 6) {
                    if ($wantUploaded && $lhuInfo['has'])
                        $match = true;
                    if ($wantPending && !$lhuInfo['has'])
                        $match = true;
                }
                if (!$match)
                    continue;
            }

            $data[] = $response;
        }

        return $this->response->setJSON(["items" => $data]);
    }


    public function detailList($id = null)
    {
        if (!$id)
            return $this->response->setJSON(['items' => []]);

        // decrypt tolerant (hex → raw)
        try {
            $lnKode = service('encrypter')->decrypt(hex2bin($id));
        } catch (\Throwable $e) {
            try {
                $lnKode = service('encrypter')->decrypt($id);
            } catch (\Throwable $e2) {
                return $this->response->setJSON(['items' => []]);
            }
        }

        $db = \Config\Database::connect();

        // ambil detil + JOIN dengan t_files_lhus dan t_files_lhu (database baru) dan username uploader/approver
        $rows = $db->table('t_layanan_detil as d')
            ->select('
            d.kode, d.uji_kode, d.nama_layanan, d.jumlah,
            d.files, d.status_layanan,
            lhus.file_lhus,
            lhus.catatan as ket_lhus,
            up_lhus.username AS upload_lhus_by,
            acc_lhus.username AS acc_lhus_by,
            lhu.file AS file_lhu,
            up_lhu.user_name AS upload_lhu_by
        ')
            // JOIN untuk LHUS (dari t_files_lhus) - ambil file terbaru
            ->join(
                '(SELECT lhus1.* FROM t_files_lhus lhus1 
              INNER JOIN (
                SELECT kode, MAX(file_id) as max_file_id 
                FROM t_files_lhus 
                GROUP BY kode
              ) lhus2 ON lhus1.kode = lhus2.kode AND lhus1.file_id = lhus2.max_file_id
            ) lhus',
                'lhus.kode = d.kode',
                'left'
            )
            ->join('simlab_account up_lhus', 'up_lhus.user_id = lhus.upload_by', 'left')
            ->join('simlab_account acc_lhus', 'acc_lhus.user_id = lhus.validasi_by', 'left')
            // JOIN untuk LHU (dari t_files_lhu) - ambil file terbaru
            ->join('t_files_lhu lhu', 'lhu.kode = d.kode_layanan', 'left')
            ->join('simlab_account_users up_lhu', 'up_lhu.user_id = lhu.upload_by', 'left')
            ->where('d.kode_layanan', $lnKode)
            ->where('d.status_layanan', 1)
            ->orderBy('d.kode', 'ASC')
            ->get()->getResult();

        if (empty($rows))
            return $this->response->setJSON(['items' => []]);

        $items = [];
        $no = 1;
        foreach ($rows as $row) {
            if ((int) ($row->status_layanan ?? 0) !== 1)
                continue;

            // Nama layanan dari database baru (nama_layanan)
            $layanan = !empty($row->nama_layanan) ? $row->nama_layanan : '-';

            $jumlah = (int) ($row->jumlah ?? 0);

            // Prioritas file: 1) LHUS dari t_files_lhus, 2) LHU dari file_lhu
            $fileUrl = null;

            // Cek file LHUS dulu (dari t_files_lhus)
            if (!empty($row->file_lhus)) {
                $val = trim((string) $row->file_lhus);
                if (preg_match('/^https?:\/\//i', $val)) {
                    $fileUrl = $val;
                } else {
                    $p = FCPATH . 'uploads/lhus/' . ltrim($val, '/');
                    if (is_file($p)) {
                        $fileUrl = base_url('uploads/lhus/' . ltrim($val, '/'));
                    }
                }
            }

            // Kalau LHUS tidak ada, cek file LHU
            if (!$fileUrl && !empty($row->file_lhu)) {
                $val = trim((string) $row->file_lhu);
                if (preg_match('/^https?:\/\//i', $val)) {
                    $fileUrl = $val;
                } else {
                    $p = FCPATH . 'uploads/lhu/' . ltrim($val, '/');
                    if (is_file($p)) {
                        $fileUrl = base_url('uploads/lhu/' . ltrim($val, '/'));
                    }
                }
            }

            $viewHtml = $fileUrl
                ? '<button class="btn btn-sm btn-outline-primary" onclick="window.open(\'' . esc($fileUrl) . '\', \'_blank\')"><i class="bi bi-eye"></i></button>'
                : '<button class="btn btn-sm btn-secondary" disabled><i class="bi bi-file-earmark-text"></i> Lihat</button>';

            // Username dari simlab_account (LHUS uploader/approver)
            $uploadLhusBy = !empty($row->upload_lhus_by) ? esc($row->upload_lhus_by) : '-';
            $accLhusBy = !empty($row->acc_lhus_by) ? esc($row->acc_lhus_by) : '-';

            // urutan kolom dikembalikan tanpa keterangan
            $items[] = [
                $no++,
                $layanan,
                $jumlah,
                $viewHtml,
                $uploadLhusBy,   // Upload LHUS (username)
                $accLhusBy       // Acc LHUS (username)
            ];
        }

        return $this->response->setJSON(['items' => $items]);
    }



    public function upload()
    {
        $file = $this->request->getFile('lhu_file');
        $encId = $this->request->getPost('id');
        $detKode = $this->request->getPost('detKode');
        $tanggalTerbit = $this->request->getPost('tanggal_terbit_lhu');

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
            try {
                $lnKode = service('encrypter')->decrypt($encId);
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

        // Validasi tanggal terbit LHU
        if (empty($tanggalTerbit)) {
            return $this->response->setJSON([
                'res' => 'error',
                'msg' => 'Tanggal terbit LHU harus diisi',
                'xname' => csrf_token(),
                'xhash' => csrf_hash()
            ]);
        }

        // upload fisik
        $uploadResult = $this->doUpload($file, 'lhu');
        if (!$uploadResult['status']) {
            return $this->response->setJSON([
                'res' => 'error',
                'msg' => $uploadResult['msg'],
                'xname' => csrf_token(),
                'xhash' => csrf_hash()
            ]);
        }
        $filename = $uploadResult['filename'];

        $session = session();
        $user_id = (int) $session->get('id_user');

        $db = \Config\Database::connect();

        try {
            // Upload LHU ke t_files_lhu (database baru)
            // Cek apakah sudah ada record untuk lnKode ini
            $existingFile = $db->table('t_files_lhu')
                ->where('kode', $lnKode)
                ->get()->getRow();

            if ($existingFile) {
                // Update record yang sudah ada
                $db->table('t_files_lhu')
                    ->where('kode', $lnKode)
                    ->update([
                        'file' => $filename,
                        'upload_by' => $user_id
                    ]);

                // Hapus file lama jika ada
                if (!empty($existingFile->file) && $existingFile->file !== $filename) {
                    $oldFilePath = FCPATH . 'uploads/lhu/' . ltrim($existingFile->file, '/');
                    if (is_file($oldFilePath)) {
                        @unlink($oldFilePath);
                    }
                }

                $msg = 'File LHU berhasil diperbarui.';
            } else {
                // Insert record baru jika belum ada
                $db->table('t_files_lhu')->insert([
                    'kode' => $lnKode,
                    'file' => $filename,
                    'upload_by' => $user_id
                ]);

                // Ambil file_id yang baru saja dibuat
                $newFileId = $db->insertID();

                // Update lhu_id di simlab_t_layanan
                $db->table('simlab_t_layanan')
                    ->where('lnKode', $lnKode)
                    ->update(['lhu_id' => $newFileId]);

                $msg = 'File LHU berhasil diunggah.';
            }

            // Update/Insert tanggal terbit LHU ke t_log_sampel
            $logSampel = $db->table('t_log_sampel')
                ->where('kode_layanan', $lnKode)
                ->get()->getRow();

            $tanggalTerbitFormatted = date('Y-m-d H:i:s', strtotime($tanggalTerbit));

            if ($logSampel) {
                // Update existing record
                $db->table('t_log_sampel')
                    ->where('kode_layanan', $lnKode)
                    ->update(['penerbitan_lhu' => $tanggalTerbitFormatted]);
            } else {
                // Insert new record
                $db->table('t_log_sampel')->insert([
                    'kode_layanan' => $lnKode,
                    'penerbitan_lhu' => $tanggalTerbitFormatted
                ]);
            }

            // Update status menjadi 8 (LHU Disetujui) setelah upload berhasil
            $model = new MyModel($this->table);
            $model->updateData(['lnStatus' => 8], $this->id, $lnKode);

            return $this->response->setJSON([
                'res' => true,
                'msg' => $msg . ' LHU berhasil dikirim.',
                'url' => base_url('uploads/lhu/' . $filename),
                'xname' => csrf_token(),
                'xhash' => csrf_hash()
            ]);

        } catch (\Throwable $e) {
            $savedPath = FCPATH . 'uploads/lhu/' . $filename;
            if (is_file($savedPath))
                @unlink($savedPath);
            return $this->response->setJSON([
                'res' => 'error',
                'msg' => 'Error saat menyimpan: ' . $e->getMessage(),
                'xname' => csrf_token(),
                'xhash' => csrf_hash()
            ]);
        }
    }

    // upload helper
    private function doUpload($file, $folder = 'lhu')
    {
        if (!($file && $file->isValid() && !$file->hasMoved())) {
            return ['status' => false, 'msg' => 'File tidak valid atau sudah dipindahkan'];
        }

        $allowedExt = ['jpg', 'jpeg', 'png', 'pdf', 'doc', 'docx', 'xls', 'xlsx'];
        $allowedMime = [
            'image/jpeg',
            'image/png',
            'application/pdf',
            'application/msword',
            'application/vnd.openxmlformats-officedocument.wordprocessingml.document',
            'application/vnd.ms-excel',
            'application/vnd.openxmlformats-officedocument.spreadsheetml.sheet'
        ];

        $ext = strtolower($file->getClientExtension());
        $tmp = $file->getTempName();
        if (!is_file($tmp))
            return ['status' => false, 'msg' => 'File sementara tidak ditemukan'];

        $detectedMime = function_exists('finfo_open')
            ? (function ($tmp) {
                $f = finfo_open(FILEINFO_MIME_TYPE);
                $m = finfo_file($f, $tmp);
                finfo_close($f);
                return $m; })($tmp)
            : $file->getClientMimeType();

        if (!in_array($ext, $allowedExt) || !in_array($detectedMime, $allowedMime)) {
            return ['status' => false, 'msg' => 'Format file tidak diperbolehkan'];
        }

        if (in_array($ext, ['jpg', 'jpeg', 'png']) && @getimagesize($tmp) === false) {
            return ['status' => false, 'msg' => 'File bukan gambar asli'];
        }

        if ($file->getSize() > 5 * 1024 * 1024)
            return ['status' => false, 'msg' => 'Ukuran file maksimal 5MB'];

        try {
            $rand = bin2hex(random_bytes(8));
        } catch (\Exception $e) {
            $rand = bin2hex(openssl_random_pseudo_bytes(8));
        }
        $filename = time() . '_' . $rand . '.' . $ext;

        $path = FCPATH . 'uploads/' . $folder;
        if (!is_dir($path))
            @mkdir($path, 0755, true);

        try {
            $file->move($path, $filename, true);
            $full = $path . DIRECTORY_SEPARATOR . $filename;
            if (is_file($full))
                @chmod($full, 0644);
        } catch (\Exception $e) {
            return ['status' => false, 'msg' => 'Gagal memindahkan file: ' . $e->getMessage()];
        }

        return ['status' => true, 'filename' => $filename];
    }

    // detect LHUS - UPDATED: gunakan t_files_lhus dengan kode_layanan
    private function detectLhusFile($row)
    {
        // Cek langsung ke t_files_lhus berdasarkan kode_layanan (lnKode)
        if (!empty($row->lnKode)) {
            try {
                $db = \Config\Database::connect();

                // Cek apakah ada file LHUS yang sudah dikirim (status=0) atau diterima (status=1)
                $lhusFile = $db->table('t_files_lhus')
                    ->select('file_lhus')
                    ->where('kode_layanan', $row->lnKode)
                    ->whereIn('status', [0, 1]) // 0=terkirim, 1=diterima
                    ->orderBy('file_id', 'DESC')
                    ->limit(1)
                    ->get()->getRow();

                if ($lhusFile && !empty($lhusFile->file_lhus)) {
                    $raw = $lhusFile->file_lhus;
                    if (preg_match('/^https?:\/\//i', $raw)) {
                        return ['has' => true, 'url' => $raw];
                    }
                    $p = FCPATH . 'uploads/lhus/' . ltrim($raw, '/');
                    if (is_file($p)) {
                        return ['has' => true, 'url' => base_url('uploads/lhus/' . ltrim($raw, '/'))];
                    }
                }
            } catch (\Throwable $e) {
                // Silent catch
            }
        }

        return ['has' => false, 'url' => '#'];
    }

    // detect LHU - UPDATED: gunakan t_files_lhu (database baru)
    private function detectLhuFile($row)
    {
        // Cek langsung ke t_files_lhu berdasarkan kode (lnKode)
        if (!empty($row->lnKode)) {
            try {
                $db = \Config\Database::connect();

                $lhuFile = $db->table('t_files_lhu')
                    ->select('file')
                    ->where('kode', $row->lnKode)
                    ->orderBy('file_id', 'DESC')
                    ->limit(1)
                    ->get()->getRow();

                if ($lhuFile && !empty($lhuFile->file)) {
                    $raw = $lhuFile->file;
                    if (preg_match('/^https?:\/\//i', $raw)) {
                        return ['has' => true, 'url' => $raw];
                    }
                    $p = FCPATH . 'uploads/lhu/' . ltrim($raw, '/');
                    if (is_file($p)) {
                        return ['has' => true, 'url' => base_url('uploads/lhu/' . ltrim($raw, '/'))];
                    }
                }
            } catch (\Throwable $e) {
                // Silent catch
            }
        }

        return ['has' => false, 'url' => '#'];
    }

    private function formatStatus($status, $uploaded = null)
    {
        switch ((int) $status) {
            case 7:
            case 8:
                return '<span class="badge bg-success">LHU Disetujui</span>';
            case 6:
                return $uploaded === true
                    ? '<span class="badge bg-success">LHU terunggah</span>'
                    : '<span class="badge bg-primary">LHU belum diproses</span>';
            default:
                return '<span class="badge bg-dark">Unknown</span>';
        }
    }

    private function aksiButton($id, $status, $allowAccept = true, $lhuInfo = ['has' => false, 'url' => '#'])
    {
        $btn = '<div id="' . $id . '" class="float-end d-flex align-items-center justify-content-end" style="gap:10px;">';

        // Button Proses - hanya muncul jika status = 6 (Memproses LHU)
        if ((int) $status === 6) {
            $safeUrl = ($lhuInfo['has'] && !empty($lhuInfo['url'])) ? esc($lhuInfo['url']) : '#';
            $btn .= '<button type="button" class="btn btn-sm btn-success" title="Upload & Kirim LHU" onclick="openUploadModal(\'' . $id . '\', \'' . $safeUrl . '\')"><i class="bi bi-send-check"></i> Proses</button>';
        }

        $btn .= '</div>';
        return $btn;
    }

    // proses: kini hanya butuh LHU sudah terunggah
    public function proses($id)
    {
        try {
            $kode = service('encrypter')->decrypt(hex2bin($id));
        } catch (\Exception $e) {
            return $this->response->setJSON([
                'res' => false,
                'msg' => 'ID tidak valid',
                'xname' => csrf_token(),
                'xhash' => csrf_hash()
            ]);
        }

        $model = new MyModel($this->table);
        $row = $model->getDataById($this->id, $kode);

        if (!$row) {
            return $this->response->setJSON([
                'res' => false,
                'msg' => 'Data layanan tidak ditemukan',
                'xname' => csrf_token(),
                'xhash' => csrf_hash()
            ]);
        }

        // HARUS ada file LHU
        $lhuInfo = $this->detectLhuFile($row);
        if (!($lhuInfo['has'] ?? false)) {
            return $this->response->setJSON([
                'res' => false,
                'msg' => 'Tidak dapat memproses: file LHU belum terunggah.',
                'xname' => csrf_token(),
                'xhash' => csrf_hash()
            ]);
        }

        // (opsional) pastikan status minimal 6
        if ((int) $row->lnStatus < 6) {
            return $this->response->setJSON([
                'res' => false,
                'msg' => 'Status belum pada tahap Memproses LHU.',
                'xname' => csrf_token(),
                'xhash' => csrf_hash()
            ]);
        }

        // set ke 8
        $res = $model->updateData(['lnStatus' => 8], $this->id, $kode);

        return $this->response->setJSON([
            'res' => $res,
            'xname' => csrf_token(),
            'xhash' => csrf_hash()
        ]);
    }

    public function delete($id)
    {
        try {
            $kode = service('encrypter')->decrypt(hex2bin($id));
        } catch (\Exception $e) {
            return $this->response->setJSON([
                'res' => false,
                'msg' => 'ID tidak valid',
                'xname' => csrf_token(),
                'xhash' => csrf_hash()
            ]);
        }

        $model = new MyModel($this->table);
        $res = $model->deleteData($this->id, $kode);

        return $this->response->setJSON([
            'res' => $res,
            'xname' => csrf_token(),
            'xhash' => csrf_hash()
        ]);
    }
}
