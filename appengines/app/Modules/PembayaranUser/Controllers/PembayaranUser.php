<?php

namespace Modules\PembayaranUser\Controllers;

use App\Controllers\BaseController;
use App\Models\MyModel;

class PembayaranUser extends BaseController
{
    private $table = 'simlab_t_pembayaran';
    private $id = 'bayarKode';

    public function index()
    {
        $data = [
            'title' => 'Pembayaran',
        ];
        return view('Modules\PembayaranUser\Views\v_pembayaran_user', $data);
    }

    /**
     * Get data list untuk tabel - hanya menampilkan tagihan yang sudah diproses (ada nomor invoice)
     */
    public function dataList()
    {
        try {
            $session = session();
            $user_id = $session->get('id_user'); // ✅ Sama seperti Pelayanan.php

            log_message('debug', '=== PembayaranUser dataList START ===');
            log_message('debug', 'Session id_user: ' . ($user_id ?? 'NULL'));

            // Validasi: User harus login
            if (empty($user_id)) {
                log_message('error', '⚠️ USER NOT LOGGED IN! Session id_user is NULL');
                return $this->response->setJSON([
                    "items" => [],
                    "error" => "User belum login atau session expired. Silakan login kembali."
                ]);
            }

            // Ambil user dari tabel simlab_account_users (sama seperti Pelayanan.php)
            $modelUser = new MyModel('simlab_account_users');
            $user = $modelUser->getDataById('user_id', $user_id);

            // Jika user tidak ditemukan, kembalikan data kosong
            if (!$user) {
                log_message('error', '⚠️ USER NOT FOUND! user_id=' . $user_id);
                return $this->response->setJSON([
                    "items" => [],
                    "error" => "Data user tidak ditemukan."
                ]);
            }

            log_message('debug', 'User found: ' . $user->user_email);

            $db = \Config\Database::connect();
            $data = [];

            // Query dengan JOIN - filter berdasarkan EMAIL seperti di Pelayanan.php
            $builder = $db->table('simlab_t_pembayaran');
            $builder->select('simlab_t_pembayaran.*, simlab_t_layanan.lnKode, simlab_t_layanan.lnAccEmail, simlab_t_layanan.lnNoTransaksi, simlab_t_layanan.lnTgl, simlab_t_layanan.user_id');
            $builder->join('simlab_t_layanan', 'simlab_t_pembayaran.bayarLnKode = simlab_t_layanan.lnKode', 'inner');
            $builder->where('simlab_t_pembayaran.bayarInvoiceNo IS NOT NULL');
            $builder->where('simlab_t_layanan.lnAccEmail', $user->user_email); // ✅ Filter by EMAIL
            $builder->orderBy('simlab_t_pembayaran.bayarKode', 'DESC');

            // Log SQL query
            $sql = $builder->getCompiledSelect(false);
            log_message('debug', 'SQL Query: ' . $sql);

            $list = $builder->get()->getResult();

            log_message('debug', 'Total records found: ' . count($list));
            log_message('debug', '=== PembayaranUser dataList END ===');

            $userModel = new MyModel('simlab_account_users');

            foreach ($list as $row) {
                $encrypted_id = bin2hex(service('encrypter')->encrypt($row->bayarKode));

                // Status pembayaran berdasarkan bayarBuktiFile dan bayarStatus
                $paymentStatus = $this->getPaymentStatus($row->bayarBuktiFile, $row->bayarStatus);
                $status = $this->formatStatus($paymentStatus);

                // Cek apakah ada file bukti di session (file temporary yang belum disave)
                $sessionKey = 'temp_bukti_' . $row->bayarKode;
                $tempBukti = session()->get($sessionKey);
                $currentFile = !empty($tempBukti) ? $tempBukti : ($row->bayarBuktiFile ?? '');

                // Tombol aksi
                $aksi = $this->aksiButton($encrypted_id, $paymentStatus, $currentFile);

                // Ambil data user (sama seperti di Tagihan)
                $personName = null;
                $userIdentity = '-';
                $instansi = '-';
                $u = null;

                // 1️⃣ Cek langsung dari user_id (FK)
                if (!empty($row->user_id)) {
                    $u = $userModel->getDataById('user_id', $row->user_id);
                }

                // 2️⃣ Jika belum ada, cek berdasarkan email (lnAccEmail)
                if (!$u && !empty($row->lnAccEmail)) {
                    $users = $userModel->getAllDataById(['user_email' => $row->lnAccEmail]);
                    if (!empty($users)) $u = is_array($users) ? $users[0] : $users;
                }

                // 3️⃣ Jika user ditemukan, ambil info
                if ($u) {
                    $personName = $u->user_name ?? $u->user_email ?? '-';
                    $instansi = $u->user_instansi ?? '-';
                    $userIdentity = $u->user_identity ?? '-';
                } else {
                    $personName = $row->lnAccEmail ?? '-';
                }

                $pemesanNama = !empty($personName) ? $personName : '-';
                $tipe = !empty($userIdentity) ? $userIdentity : '-';
                $tanggal = !empty($row->lnTgl) ? date('d-m-Y H:i', strtotime($row->lnTgl)) : '-';

                // Format gabungan seperti di Tagihan (Nama + Tanggal + Tipe)
                $combined = '
                    <div style="line-height:1.3;">
                        <span style="font-size:1rem; font-weight:600;">' . esc($pemesanNama) . '</span><br>
                        <span style="font-size:0.9rem; color:#555;">' . esc($tanggal) . ' | ' . esc($tipe) . '</span>
                    </div>';

                // Kolom bukti bayar dengan logika: null = "-", ada file = tombol lihat
                $buktiBayar = '-';
                if (!empty($currentFile)) {
                    if (!empty($tempBukti)) {
                        // File baru dari session (belum disave)
                        $buktiBayar = '<span class="badge bg-info"><i class="bi bi-clock-history"></i> File Terupload</span>';
                    } else {
                        // File dari database (sudah disave)
                        $buktiBayar = '<a href="' . base_url('uploads/bukti/' . $currentFile) . '" target="_blank" class="btn btn-sm btn-success"><i class="bi bi-file-earmark-check"></i> Lihat</a>';
                    }
                }

                // Tombol lihat catatan jika verifikasi ditolak
                $catatanButton = '';
                if ($paymentStatus == 3 && !empty($row->bayarCatatan)) {
                    $catatanButton = '<button class="btn btn-sm btn-warning mt-1" onclick="lihatCatatan(\'' . $encrypted_id . '\', \'' . esc($row->bayarCatatan, 'js') . '\')">
                        <i class="bi bi-file-text"></i> Lihat Catatan
                    </button>';
                }

                // Response array
                $data[] = [
                    !empty($row->bayarInvoiceNo) ? esc($row->bayarInvoiceNo) : '<span class="text-muted">-</span>', // No. Invoice
                    $combined, // Pemesan (Nama + Tanggal + Tipe)
                    'Rp ' . number_format($row->bayarTotalBiaya, 0, ',', '.'), // Total Biaya
                    !empty($row->bayarInvoiceFile)
                        ? '<a href="' . base_url('uploads/invoice/' . $row->bayarInvoiceFile) . '" target="_blank" class="btn btn-sm btn-info"><i class="bi bi-file-pdf"></i> Lihat</a>'
                        : '<span class="text-muted">-</span>', // File Invoice
                    $buktiBayar, // Bukti Bayar
                    $status . $catatanButton, // Status + Tombol Catatan (jika ditolak)
                    $aksi // Aksi
                ];
            }

            return $this->response->setJSON(["items" => $data]);
        } catch (\Exception $e) {
            log_message('error', 'PembayaranUser dataList error: ' . $e->getMessage());
            return $this->response->setJSON([
                "items" => [],
                "error" => $e->getMessage()
            ]);
        }
    }

    /**
     * Tentukan status pembayaran
     * @return int 0 = Belum Diunggah, 1 = Menunggu Verifikasi, 2 = Terverifikasi
     */
    private function getPaymentStatus($buktiBayar, $bayarStatus)
    {
        // Jika bayarBuktiFile == null & bayarStatus == 0 → Belum Diunggah
        if (empty($buktiBayar) && $bayarStatus == 0) {
            return 0;
        }

        // Jika bayarBuktiFile != null & bayarStatus == 1 → Terverifikasi
        if (!empty($buktiBayar) && $bayarStatus == 1) {
            return 2;
        }

        // Jika bayarBuktiFile != null & bayarStatus == 2 → Verifikasi Gagal
        if (!empty($buktiBayar) && $bayarStatus == 2) {
            return 3;
        }

        // Jika bayarBuktiFile != null & bayarStatus == 0 → Menunggu Verifikasi
        if (!empty($buktiBayar) && $bayarStatus == 0) {
            return 1;
        }

        return 0; // Default
    }

    /**
     * Format status badge
     */
    private function formatStatus($status)
    {
        switch ($status) {
            case 0:
                return '<span class="badge bg-secondary">Belum Bayar</span>';
            case 1:
                return '<span class="badge bg-info">Menunggu Verifikasi</span>';
            case 2:
                return '<span class="badge bg-success">Terverifikasi</span>';
            case 3:
                return '<span class="badge bg-danger">Verifikasi Gagal</span>';
            default:
                return '<span class="badge bg-secondary">Unknown</span>';
        }
    }

    /**
     * Tombol aksi - update logic untuk status verifikasi gagal
     */
    private function aksiButton($id, $status, $file)
    {
        // URL file bukti bayar (jika ada)
        $fileUrl = !empty($file) ? base_url('uploads/bukti/' . $file) : '';

        // Button "Upload Bukti":
        // - Disabled jika status = Menunggu Verifikasi (1) atau Terverifikasi (2)
        // - Aktif jika status = Belum Terkirim (0) atau Verifikasi Gagal (3)
        $uploadDisabled = ($status == 1 || $status == 2) ? 'disabled' : '';
        $uploadClass = ($status == 1 || $status == 2) ? 'text-secondary' : 'text-primary';
        $uploadTitle = ($status == 2) ? 'Sudah terverifikasi'
            : (($status == 1) ? 'Menunggu verifikasi admin'
                : (($status == 3) ? 'Upload ulang bukti bayar'
                    : 'Upload Bukti Bayar'));

        // Button "Kirim":
        // - Aktif jika sudah upload file DAN (status = Belum Terkirim atau Verifikasi Gagal)
        // - Disabled jika belum upload, Menunggu Verifikasi, atau Terverifikasi
        $prosesDisabled = (empty($file) || $status == 1 || $status == 2) ? 'disabled' : '';
        $prosesClass = (empty($file) || $status == 1 || $status == 2) ? 'text-secondary' : 'text-success';
        $prosesTitle = empty($file)
            ? 'Upload bukti bayar terlebih dahulu'
            : (($status == 2) ? 'Sudah terverifikasi'
                : (($status == 1) ? 'Menunggu verifikasi admin'
                    : (($status == 3) ? 'Kirim ulang bukti pembayaran'
                        : 'Kirim Bukti Pembayaran')));

        return '<div id="' . $id . '" class="float-end">
        <span class="' . $uploadClass . ' btn-action" ' . $uploadDisabled . ' title="' . $uploadTitle . '" data-fileurl="' . esc($fileUrl) . '" onclick="uploadBukti(event)">
            <i class="bi bi-upload"></i></span> 
        <label class="divider">|</label>
        <span class="' . $prosesClass . ' btn-action" ' . $prosesDisabled . ' title="' . $prosesTitle . '" onclick="kirimBukti(event)">
            <i class="bi bi-send"></i></span>
    </div>';
    }

    /**
     * Upload bukti bayar (PNG/JPG/PDF/image format)
     */
    public function uploadBukti()
    {
        try {
            log_message('debug', 'UploadBukti request received');

            $file = $this->request->getFile('file_bukti');
            $encId = $this->request->getPost('id');

            if (empty($encId)) {
                return $this->response->setJSON([
                    'res' => false,
                    'msg' => 'ID tidak ditemukan',
                    'xname' => csrf_token(),
                    'xhash' => csrf_hash()
                ]);
            }

            try {
                $id = service('encrypter')->decrypt(hex2bin($encId));
            } catch (\Throwable $e) {
                return $this->response->setJSON([
                    'res' => false,
                    'msg' => 'ID tidak valid',
                    'xname' => csrf_token(),
                    'xhash' => csrf_hash()
                ]);
            }

            log_message('debug', 'Decrypted ID: ' . $id);

            if (!($file && $file->isValid() && !$file->hasMoved())) {
                return $this->response->setJSON([
                    'res' => false,
                    'msg' => 'File tidak valid atau belum dipilih',
                    'xname' => csrf_token(),
                    'xhash' => csrf_hash()
                ]);
            }

            // Upload file ke folder uploads/bukti/
            $uploadResult = $this->doUpload($file, 'bukti');

            if (!$uploadResult['status']) {
                return $this->response->setJSON([
                    'res' => false,
                    'msg' => $uploadResult['msg'],
                    'xname' => csrf_token(),
                    'xhash' => csrf_hash()
                ]);
            }

            $filename = $uploadResult['filename'];
            log_message('debug', 'Bukti uploaded successfully: ' . $filename);

            // Simpan filename untuk sementara di session
            session()->set('temp_bukti_' . $id, $filename);

            log_message('debug', 'Bukti saved to session: temp_bukti_' . $id . ' = ' . $filename);

            return $this->response->setJSON([
                'res' => 'success',
                'msg' => 'Bukti bayar berhasil diupload',
                'xname' => csrf_token(),
                'xhash' => csrf_hash()
            ]);
        } catch (\Exception $e) {
            log_message('error', 'UploadBukti exception: ' . $e->getMessage());
            return $this->response->setJSON([
                'res' => false,
                'msg' => 'Terjadi kesalahan: ' . $e->getMessage(),
                'xname' => csrf_token(),
                'xhash' => csrf_hash()
            ]);
        }
    }

    /**
     * Helper untuk upload file (PNG/JPG/PDF/image)
     */
    private function doUpload($file, $folder = 'bukti')
    {
        if (!($file && $file->isValid() && !$file->hasMoved())) {
            return ['status' => false, 'msg' => 'File tidak valid'];
        }

        // Validasi tipe file (image dan PDF)
        $allowedExt  = ['pdf', 'png', 'jpg', 'jpeg', 'gif', 'bmp', 'webp'];
        $allowedMime = [
            'application/pdf',
            'image/png',
            'image/jpg',
            'image/jpeg',
            'image/gif',
            'image/bmp',
            'image/webp'
        ];

        $ext  = strtolower($file->getClientExtension());
        $tmpName = $file->getTempName();

        if (!is_file($tmpName)) {
            return ['status' => false, 'msg' => 'File sementara tidak ditemukan'];
        }

        // Deteksi MIME type
        $detectedMime = null;
        if (function_exists('finfo_open')) {
            $finfo = finfo_open(FILEINFO_MIME_TYPE);
            $detectedMime = finfo_file($finfo, $tmpName);
            finfo_close($finfo);
        } else {
            $detectedMime = $file->getClientMimeType();
        }

        // Validasi ekstensi dan MIME type
        if (!in_array($ext, $allowedExt) || !in_array($detectedMime, $allowedMime)) {
            return ['status' => false, 'msg' => 'Format file harus gambar (PNG/JPG/etc) atau PDF. Detected: ' . $detectedMime];
        }

        // Validasi ukuran file (max 5MB)
        if ($file->getSize() > 5 * 1024 * 1024) {
            return ['status' => false, 'msg' => 'Ukuran file maksimal 5MB'];
        }

        // Generate filename
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
                log_message('debug', 'File moved successfully to: ' . $fullPath);
                return ['status' => true, 'filename' => $filename];
            } else {
                return ['status' => false, 'msg' => 'File gagal dipindahkan'];
            }
        } catch (\Exception $e) {
            return ['status' => false, 'msg' => 'Gagal memindahkan file: ' . $e->getMessage()];
        }
    }

    /**
     * Kirim bukti bayar - update database dan ubah status
     */
    public function kirimBukti()
    {
        log_message('debug', '=== KIRIM BUKTI REQUEST START ===');
        log_message('debug', 'POST data: ' . json_encode($this->request->getPost()));

        $id = $this->request->getPost('id');

        log_message('debug', 'ID received: ' . ($id ?? 'NULL'));

        try {
            $id = service('encrypter')->decrypt(hex2bin($id));
            log_message('debug', 'Decrypted ID: ' . $id);
        } catch (\Exception $e) {
            log_message('error', 'Decrypt error: ' . $e->getMessage());
            return $this->response->setJSON([
                'res' => false,
                'msg' => 'ID tidak valid: ' . $e->getMessage(),
                'xname' => csrf_token(),
                'xhash' => csrf_hash()
            ]);
        }

        $model = new MyModel($this->table);
        $currentData = $model->getDataById($this->id, $id);

        // Cek file dari session (file yang baru diupload)
        $sessionKey = 'temp_bukti_' . $id;
        $tempFilename = session()->get($sessionKey);

        // Jika tidak ada file di session, cek di database (file lama)
        if (empty($tempFilename) && (empty($currentData) || empty($currentData->bayarBuktiFile))) {
            return $this->response->setJSON([
                'res' => false,
                'msg' => 'Bukti bayar belum diupload. Upload terlebih dahulu sebelum mengirim.',
                'xname' => csrf_token(),
                'xhash' => csrf_hash()
            ]);
        }

        // Gunakan file dari session jika ada, jika tidak gunakan file lama dari database
        $filename = !empty($tempFilename) ? $tempFilename : $currentData->bayarBuktiFile;

        log_message('debug', 'Processing with bukti filename: ' . $filename);

        // Hapus file lama jika ada dan berbeda dengan file baru
        if (!empty($currentData->bayarBuktiFile) && !empty($tempFilename) && $currentData->bayarBuktiFile !== $tempFilename) {
            $oldFilePath = FCPATH . 'uploads/bukti/' . $currentData->bayarBuktiFile;
            if (file_exists($oldFilePath)) {
                @unlink($oldFilePath);
                log_message('debug', 'Old bukti file deleted: ' . $currentData->bayarBuktiFile);
            }
        }

        // Update bukti bayar dan set bayarStatus = 0 (menunggu verifikasi)
        $dataPembayaran = [
            'bayarBuktiFile' => $filename,
            'bayarStatus' => 0  // Menunggu verifikasi admin
        ];

        $updatePembayaran = $model->updateData($dataPembayaran, $this->id, $id);

        if (!$updatePembayaran) {
            return $this->response->setJSON([
                'res' => false,
                'msg' => 'Gagal menyimpan bukti bayar',
                'xname' => csrf_token(),
                'xhash' => csrf_hash()
            ]);
        }

        // Hapus dari session setelah berhasil save
        if (!empty($tempFilename)) {
            session()->remove($sessionKey);
            log_message('debug', 'Session key removed: ' . $sessionKey);
        }

        log_message('debug', 'Bukti bayar sent successfully. Status: Menunggu Verifikasi');

        return $this->response->setJSON([
            'res' => true,
            'msg' => 'Bukti pembayaran berhasil dikirim. Menunggu verifikasi petugas lab.',
            'xname' => csrf_token(),
            'xhash' => csrf_hash()
        ]);
    }
}
