<?php

namespace Modules\PembayaranAdmin\Controllers;

use App\Controllers\BaseController;
use App\Models\MyModel;

class PembayaranAdmin extends BaseController
{
    private $table = 'simlab_t_pembayaran';
    private $id = 'bayarKode';

    public function index()
    {
        $data = [
            'title' => 'Verifikasi Pembayaran',
        ];
        return view('Modules\PembayaranAdmin\Views\v_pembayaran_admin', $data);
    }

    /**
     * Get data list untuk tabel admin - menampilkan SEMUA tagihan yang sudah ada invoice (tidak ada filter user)
     */
    public function dataList()
    {
        try {
            $db = \Config\Database::connect();
            $data = [];

            // Query SEMUA pembayaran yang sudah ada invoice (admin melihat semua data)
            $builder = $db->table('simlab_t_pembayaran');
            $builder->select('simlab_t_pembayaran.*, simlab_t_layanan.lnKode, simlab_t_layanan.lnAccEmail, simlab_t_layanan.lnNoTransaksi, simlab_t_layanan.lnTgl, simlab_t_layanan.user_id');
            $builder->join('simlab_t_layanan', 'simlab_t_pembayaran.bayarLnKode = simlab_t_layanan.lnKode', 'inner');
            $builder->where('simlab_t_pembayaran.bayarInvoiceNo IS NOT NULL');
            $builder->orderBy('simlab_t_pembayaran.bayarKode', 'DESC');

            $list = $builder->get()->getResult();

            $userModel = new MyModel('simlab_account_users');

            foreach ($list as $row) {
                $encrypted_id = bin2hex(service('encrypter')->encrypt($row->bayarKode));

                // Status pembayaran berdasarkan bayarBuktiFile dan bayarStatus
                $paymentStatus = $this->getPaymentStatus($row->bayarBuktiFile, $row->bayarStatus);
                $status = $this->formatStatus($paymentStatus);

                // Tombol aksi
                $aksi = $this->aksiButton($encrypted_id, $paymentStatus, $row->bayarBuktiFile);

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

                // Kolom bukti bayar
                $buktiBayar = '-';
                if (!empty($row->bayarBuktiFile)) {
                    $buktiBayar = '<a href="' . base_url('uploads/bukti/' . $row->bayarBuktiFile) . '" target="_blank" class="btn btn-sm btn-success"><i class="bi bi-file-earmark-check"></i> Lihat</a>';
                }

                // Response array: 8 kolom
                $data[] = [
                    !empty($row->bayarInvoiceNo) ? esc($row->bayarInvoiceNo) : '<span class="text-muted">-</span>', // No. Invoice
                    $combined, // Pemesan (Nama + Tanggal + Tipe)
                    'Rp ' . number_format($row->bayarTotalBiaya, 0, ',', '.'), // Total Biaya
                    !empty($row->bayarInvoiceFile)
                        ? '<a href="' . base_url('uploads/invoice/' . $row->bayarInvoiceFile) . '" target="_blank" class="btn btn-sm btn-info"><i class="bi bi-file-pdf"></i> Lihat</a>'
                        : '<span class="text-muted">-</span>', // File Invoice
                    $buktiBayar, // Bukti Bayar
                    $status, // Status
                    $aksi // Aksi
                ];
            }

            return $this->response->setJSON(["items" => $data]);
        } catch (\Exception $e) {
            log_message('error', 'PembayaranAdmin dataList error: ' . $e->getMessage());
            return $this->response->setJSON([
                "items" => [],
                "error" => $e->getMessage()
            ]);
        }
    }

    /**
     * Tentukan status pembayaran untuk ADMIN VIEW
     * @return int 0 = Belum Diunggah, 1 = Belum Diverifikasi, 2 = Terverifikasi, 3 = Tidak Terverifikasi
     */
    private function getPaymentStatus($buktiBayar, $bayarStatus)
    {
        // Jika bayarBuktiFile == null → Belum Diunggah
        if (empty($buktiBayar)) {
            return 0;
        }

        // Jika bayarBuktiFile != null & bayarStatus == 1 → Terverifikasi
        if (!empty($buktiBayar) && $bayarStatus == 1) {
            return 2;
        }

        // Jika bayarBuktiFile != null & bayarStatus == 2 → Tidak Terverifikasi (Ditolak)
        if (!empty($buktiBayar) && $bayarStatus == 2) {
            return 3;
        }

        // Jika bayarBuktiFile != null & bayarStatus == 0 → Belum Diverifikasi (Menunggu)
        if (!empty($buktiBayar) && $bayarStatus == 0) {
            return 1;
        }

        return 0; // Default
    }

    /**
     * Format status badge untuk ADMIN VIEW
     */
    private function formatStatus($status)
    {
        switch ($status) {
            case 0:
                return '<span class="badge bg-secondary">Belum Diunggah</span>';
            case 1:
                return '<span class="badge bg-warning text-dark">Belum Diverifikasi</span>';
            case 2:
                return '<span class="badge bg-success">Terverifikasi</span>';
            case 3:
                return '<span class="badge bg-danger">Tidak Terverifikasi</span>';
            default:
                return '<span class="badge bg-secondary">Unknown</span>';
        }
    }

    /**
     * Tombol aksi admin - Upload, Terima, Tolak
     */
    private function aksiButton($id, $status, $file)
    {
        $fileUrl = !empty($file) ? base_url('uploads/bukti/' . $file) : '';

        $html = '<div id="' . $id . '" class="float-end">';

        // Button 1: Upload (admin bisa upload bukti untuk user)
        $uploadDisabled = ($status == 2) ? 'disabled' : '';
        $uploadClass = ($status == 2) ? 'text-secondary' : 'text-primary';
        $uploadTitle = ($status == 2) ? 'Sudah terverifikasi' : 'Upload Bukti Bayar';

        $html .= '<span class="' . $uploadClass . ' btn-action" ' . $uploadDisabled . ' title="' . $uploadTitle . '" data-fileurl="' . esc($fileUrl) . '" onclick="uploadBukti(event)">';
        $html .= '<i class="bi bi-upload"></i></span>';

        // Button 2: Terima (hanya aktif jika Belum Diverifikasi atau Tidak Terverifikasi)
        $html .= ' <label class="divider">|</label>';
        if ($status == 1 || $status == 3) {
            $html .= '<span class="text-success btn-action" title="Terima & Verifikasi" onclick="terimaVerifikasi(event)">';
            $html .= '<i class="bi bi-check-circle"></i></span>';
        } else {
            $html .= '<span class="text-secondary btn-action" disabled title="Tidak perlu verifikasi">';
            $html .= '<i class="bi bi-check-circle"></i></span>';
        }

        // Button 3: Tolak (hanya aktif jika Belum Diverifikasi)
        $html .= ' <label class="divider">|</label>';
        if ($status == 1) {
            $html .= '<span class="text-danger btn-action" title="Tolak Verifikasi" onclick="tolakVerifikasi(event)">';
            $html .= '<i class="bi bi-x-circle"></i></span>';
        } else {
            $html .= '<span class="text-secondary btn-action" disabled title="Tidak bisa ditolak">';
            $html .= '<i class="bi bi-x-circle"></i></span>';
        }

        $html .= '</div>';
        return $html;
    }

    /**
     * Upload bukti bayar oleh admin (untuk user yang tidak bisa upload sendiri)
     */
    public function uploadBukti()
    {
        try {
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

            if (!($file && $file->isValid() && !$file->hasMoved())) {
                return $this->response->setJSON([
                    'res' => false,
                    'msg' => 'File tidak valid atau belum dipilih',
                    'xname' => csrf_token(),
                    'xhash' => csrf_hash()
                ]);
            }

            // Upload file
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

            // Update langsung ke database (admin langsung save)
            $model = new MyModel($this->table);
            $currentData = $model->getDataById($this->id, $id);

            // Hapus file lama jika ada
            if (!empty($currentData->bayarBuktiFile)) {
                $oldFile = FCPATH . 'uploads/bukti/' . $currentData->bayarBuktiFile;
                if (file_exists($oldFile)) {
                    @unlink($oldFile);
                }
            }

            $dataPembayaran = [
                'bayarBuktiFile' => $filename,
                'bayarStatus' => 0  // Set status Belum Diverifikasi
            ];

            $update = $model->updateData($dataPembayaran, $this->id, $id);

            if (!$update) {
                return $this->response->setJSON([
                    'res' => false,
                    'msg' => 'Gagal menyimpan bukti bayar',
                    'xname' => csrf_token(),
                    'xhash' => csrf_hash()
                ]);
            }

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
     * Helper untuk upload file
     */
    private function doUpload($file, $folder = 'bukti')
    {
        if (!($file && $file->isValid() && !$file->hasMoved())) {
            return ['status' => false, 'msg' => 'File tidak valid'];
        }

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

        $detectedMime = null;
        if (function_exists('finfo_open')) {
            $finfo = finfo_open(FILEINFO_MIME_TYPE);
            $detectedMime = finfo_file($finfo, $tmpName);
            finfo_close($finfo);
        } else {
            $detectedMime = $file->getClientMimeType();
        }

        if (!in_array($ext, $allowedExt) || !in_array($detectedMime, $allowedMime)) {
            return ['status' => false, 'msg' => 'Format file harus gambar atau PDF'];
        }

        if ($file->getSize() > 5 * 1024 * 1024) {
            return ['status' => false, 'msg' => 'Ukuran file maksimal 5MB'];
        }

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
                return ['status' => true, 'filename' => $filename];
            } else {
                return ['status' => false, 'msg' => 'File gagal dipindahkan'];
            }
        } catch (\Exception $e) {
            return ['status' => false, 'msg' => 'Gagal memindahkan file: ' . $e->getMessage()];
        }
    }

    /**
     * Terima & Verifikasi pembayaran
     */
    public function terimaVerifikasi()
    {
        try {
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

            $model = new MyModel($this->table);

            // Update bayarStatus menjadi 1 (Terverifikasi)
            $data = ['bayarStatus' => 1];
            $update = $model->updateData($data, $this->id, $id);

            if (!$update) {
                return $this->response->setJSON([
                    'res' => false,
                    'msg' => 'Gagal memverifikasi pembayaran',
                    'xname' => csrf_token(),
                    'xhash' => csrf_hash()
                ]);
            }

            return $this->response->setJSON([
                'res' => true,
                'msg' => 'Pembayaran berhasil diverifikasi',
                'xname' => csrf_token(),
                'xhash' => csrf_hash()
            ]);
        } catch (\Exception $e) {
            log_message('error', 'TerimaVerifikasi exception: ' . $e->getMessage());
            return $this->response->setJSON([
                'res' => false,
                'msg' => 'Terjadi kesalahan: ' . $e->getMessage(),
                'xname' => csrf_token(),
                'xhash' => csrf_hash()
            ]);
        }
    }

    /**
     * Tolak verifikasi pembayaran
     */
    public function tolakVerifikasi()
    {
        try {
            $encId = $this->request->getPost('id');
            $alasan = $this->request->getPost('alasan');

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

            $model = new MyModel($this->table);

            // Update bayarStatus menjadi 2 (Tidak Terverifikasi)
            $data = ['bayarStatus' => 2];

            // Jika ada alasan, simpan (perlu kolom bayarCatatan di database)
            if (!empty($alasan)) {
                $data['bayarCatatan'] = $alasan;
            }

            $update = $model->updateData($data, $this->id, $id);

            if (!$update) {
                return $this->response->setJSON([
                    'res' => false,
                    'msg' => 'Gagal menolak pembayaran',
                    'xname' => csrf_token(),
                    'xhash' => csrf_hash()
                ]);
            }

            return $this->response->setJSON([
                'res' => true,
                'msg' => 'Pembayaran ditolak. User dapat mengupload ulang bukti bayar.',
                'xname' => csrf_token(),
                'xhash' => csrf_hash()
            ]);
        } catch (\Exception $e) {
            log_message('error', 'TolakVerifikasi exception: ' . $e->getMessage());
            return $this->response->setJSON([
                'res' => false,
                'msg' => 'Terjadi kesalahan: ' . $e->getMessage(),
                'xname' => csrf_token(),
                'xhash' => csrf_hash()
            ]);
        }
    }
}
