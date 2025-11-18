<?php

namespace Modules\PembayaranAdmin\Controllers;

use App\Controllers\BaseController;
use App\Models\MyModel;

class PembayaranAdmin extends BaseController
{
    private $table = 't_pembayaran';
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

            // Ambil parameter filter dari request
            $tanggalAwal = $this->request->getGet('tanggal_awal');
            $tanggalAkhir = $this->request->getGet('tanggal_akhir');
            $filterStatus = $this->request->getGet('status'); // 0=Menunggu, 1=Terkirim, 2=Belum Diverifikasi, 3=Terverifikasi, 4=Ditolak

            // Debug log
            log_message('info', 'Filter parameters - Tanggal Awal: ' . ($tanggalAwal ?? 'null') . ', Tanggal Akhir: ' . ($tanggalAkhir ?? 'null') . ', Status: ' . ($filterStatus ?? 'null'));

            // Query SEMUA pembayaran dengan lnStatus > 3 (admin melihat semua data)
            $builder = $db->table('t_pembayaran');
            $builder->select('t_pembayaran.*, simlab_t_layanan.lnKode, simlab_t_layanan.lnAccEmail, simlab_t_layanan.lnNoTransaksi, simlab_t_layanan.lnTgl, simlab_t_layanan.lnStatus, simlab_t_layanan.user_id');
            $builder->join('simlab_t_layanan', 't_pembayaran.bayarLnKode = simlab_t_layanan.lnKode', 'inner');
            $builder->where('simlab_t_layanan.lnStatus >', 2);

            // Aplikasikan filter tanggal jika ada
            if (!empty($tanggalAwal) && !empty($tanggalAkhir)) {
                $builder->where('DATE(simlab_t_layanan.lnTgl) >=', $tanggalAwal);
                $builder->where('DATE(simlab_t_layanan.lnTgl) <=', $tanggalAkhir);
            } elseif (!empty($tanggalAwal)) {
                $builder->where('DATE(simlab_t_layanan.lnTgl) >=', $tanggalAwal);
            } elseif (!empty($tanggalAkhir)) {
                $builder->where('DATE(simlab_t_layanan.lnTgl) <=', $tanggalAkhir);
            }

            $builder->orderBy('t_pembayaran.bayarKode', 'DESC');

            $list = $builder->get()->getResult();

            $userModel = new MyModel('simlab_account_users');

            foreach ($list as $row) {
                $encrypted_id = bin2hex(service('encrypter')->encrypt($row->bayarKode));

                // Cek file invoice dari session (file baru yang belum dikirim)
                $sessionKey = 'temp_invoice_' . $row->bayarKode;
                $tempInvoiceFile = session()->get($sessionKey);

                // Status berdasarkan lnNoTransaksi dan bayarBuktiFile
                // 0 = Menunggu Proses (lnNoTransaksi kosong)
                // 1 = Terkirim (lnNoTransaksi terisi, bukti bayar belum ada)
                // 2 = Belum Diverifikasi (lnNoTransaksi terisi, bukti bayar ada, bayarStatus = 0)
                // 3 = Terverifikasi (bayarStatus = 1)
                // 4 = Ditolak (bayarStatus = 2)
                $invoiceStatus = 0;
                if (!empty($row->lnNoTransaksi)) {
                    // Invoice sudah terkirim
                    if (!empty($row->bayarBuktiFile)) {
                        // Bukti bayar sudah ada, cek status verifikasi
                        if ($row->bayarStatus == 1) {
                            $invoiceStatus = 3; // Terverifikasi
                        } elseif ($row->bayarStatus == 2) {
                            $invoiceStatus = 4; // Ditolak
                        } else {
                            $invoiceStatus = 2; // Belum Diverifikasi
                        }
                    } else {
                        $invoiceStatus = 1; // Terkirim (belum ada bukti bayar)
                    }
                } else {
                    $invoiceStatus = 0; // Menunggu Proses (invoice belum terkirim)
                }

                // Filter berdasarkan status jika dipilih
                if ($filterStatus !== null && $filterStatus !== '' && $filterStatus !== 'all') {
                    // Konversi filterStatus ke integer untuk perbandingan
                    $filterStatusInt = (int)$filterStatus;

                    // Debug log untuk troubleshooting
                    log_message('debug', 'Comparing status - filterStatus: ' . $filterStatusInt . ' (type: ' . gettype($filterStatusInt) . '), invoiceStatus: ' . $invoiceStatus . ' (type: ' . gettype($invoiceStatus) . ')');

                    if ($filterStatusInt !== $invoiceStatus) {
                        continue; // Skip row ini jika tidak sesuai filter
                    }
                }

                $status = $this->formatInvoiceStatus($invoiceStatus);

                // Status pembayaran untuk logic button (tetap gunakan bayarStatus)
                $paymentStatus = $this->getPaymentStatus($row->bayarBuktiFile, $row->bayarStatus);

                // Tombol aksi
                $aksi = $this->aksiButton($encrypted_id, $paymentStatus, $row->bayarBuktiFile, $row->bayarInvoiceFile, $row->lnNoTransaksi, $tempInvoiceFile);

                // Ambil data user (sama seperti di Tagihan)
                $personName = null;
                $userIdentity = '-';
                $instansi = '-';
                $u = null;

                // Cek langsung dari user_id (FK)
                if (!empty($row->user_id)) {
                    $u = $userModel->getDataById('user_id', $row->user_id);
                }

                // Jika belum ada, cek berdasarkan email (lnAccEmail)
                if (!$u && !empty($row->lnAccEmail)) {
                    $users = $userModel->getAllDataById(['user_email' => $row->lnAccEmail]);
                    if (!empty($users)) $u = is_array($users) ? $users[0] : $users;
                }

                // Jika user ditemukan, ambil info
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

                // Kolom File Invoice - dengan logic seperti di Tagihan
                $fileInvoiceDisplay = '';
                $currentFile = !empty($tempInvoiceFile) ? $tempInvoiceFile : ($row->bayarInvoiceFile ?? '');

                if (!empty($currentFile)) {
                    if (!empty($tempInvoiceFile)) {
                        // File baru dari session (belum dikirim) - Status: Menunggu Proses
                        $fileInvoiceDisplay = '<span class="badge bg-info"><i class="bi bi-clock-history"></i> File Terupload</span>';
                    } else {
                        // File dari database (sudah dikirim) - Status: Terkirim
                        $fileInvoiceDisplay = '<a href="' . base_url('uploads/invoice/' . $currentFile) . '" target="_blank" class="btn btn-sm btn-info"><i class="bi bi-file-pdf"></i> Lihat</a>';
                    }
                } else {
                    // Belum ada file sama sekali - Status: Menunggu Proses
                    $fileInvoiceDisplay = '<span class="text-muted">-</span>';
                }

                // Kolom bukti bayar
                // Hanya tampilkan "-" jika status = Menunggu Proses (invoiceStatus = 0)
                // Untuk status lain (Terkirim, Belum Diverifikasi, Terverifikasi, Ditolak), tampilkan file jika ada
                $buktiBayar = '-';
                if ($invoiceStatus == 0) {
                    // Status Menunggu Proses - selalu tampilkan "-"
                    $buktiBayar = '<span class="text-muted">-</span>';
                } else {
                    // Status Terkirim/Belum Diverifikasi/Terverifikasi/Ditolak - tampilkan bukti bayar jika ada
                    if (!empty($row->bayarBuktiFile)) {
                        $buktiBayar = '<a href="' . base_url('uploads/bukti/' . $row->bayarBuktiFile) . '" target="_blank" class="btn btn-sm btn-success"><i class="bi bi-file-earmark-check"></i> Lihat</a>';
                    } else {
                        $buktiBayar = '<span class="text-muted">-</span>';
                    }
                }

                // Response array: 8 kolom
                $data[] = [
                    !empty($row->bayarInvoiceNo) ? esc($row->bayarInvoiceNo) : '<span class="text-muted">-</span>', // No. Invoice
                    $combined, // Pemesan (Nama + Tanggal + Tipe)
                    'Rp ' . number_format($row->bayarTotalBiaya, 0, ',', '.'), // Total Biaya
                    $fileInvoiceDisplay, // File Invoice (dengan badge jika baru upload)
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
     * Format status badge untuk INVOICE STATUS (dengan status verifikasi)
     * Berdasarkan lnNoTransaksi dan bayarStatus
     * 0 = Menunggu Proses (lnNoTransaksi kosong)
     * 1 = Terkirim (lnNoTransaksi terisi, belum ada bukti bayar)
     * 2 = Belum Diverifikasi (ada bukti bayar, bayarStatus = 0)
     * 3 = Terverifikasi (bayarStatus = 1)
     * 4 = Ditolak (bayarStatus = 2)
     */
    private function formatInvoiceStatus($status)
    {
        switch ($status) {
            case 0:
                return '<span class="badge bg-warning">Menunggu Proses</span>';
            case 1:
                return '<span class="badge bg-success">Terkirim</span>';
            case 2:
                return '<span class="badge bg-info">Belum Diverifikasi</span>';
            case 3:
                return '<span class="badge bg-primary">Terverifikasi</span>';
            case 4:
                return '<span class="badge bg-danger">Ditolak</span>';
            default:
                return '<span class="badge bg-secondary">Unknown</span>';
        }
    }

    /**
     * Format status badge untuk PAYMENT VERIFICATION (tidak digunakan di kolom, hanya untuk logic)
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
     * Tombol aksi admin - Upload & Kirim Invoice (GABUNGAN), Upload Bukti, Terima, Tolak
     */
    private function aksiButton($id, $status, $file, $invoiceFile, $invoiceNo, $tempInvoiceFile = null)
    {
        $fileUrl = !empty($file) ? base_url('uploads/bukti/' . $file) : '';
        $invoiceUrl = !empty($invoiceFile) ? base_url('uploads/invoice/' . $invoiceFile) : '';

        $html = '<div id="' . $id . '" class="float-end">';

        // Dropdown Button
        $html .= '<div class="dropdown">';
        $html .= '<button class="btn btn-sm btn-secondary dropdown-toggle" type="button" data-bs-toggle="dropdown" aria-expanded="false">';
        $html .= '<i class="bi bi-three-dots-vertical"></i> Aksi';
        $html .= '</button>';
        $html .= '<ul class="dropdown-menu dropdown-menu-end">';

        // Menu 1: Upload & Kirim Invoice (GABUNGAN - aktif jika invoice belum dikirim)
        $uploadKirimDisabled = (!empty($invoiceNo)) ? 'disabled' : '';
        $uploadKirimTitle = (!empty($invoiceNo)) ? 'Invoice sudah terkirim' : 'Upload & Kirim Invoice';
        $iconClass = (!empty($invoiceNo)) ? 'text-secondary' : 'text-info';

        $html .= '<li><a class="dropdown-item btn-action ' . $uploadKirimDisabled . '" href="javascript:void(0)" title="' . $uploadKirimTitle . '" data-invoiceurl="' . esc($invoiceUrl) . '" onclick="uploadKirimInvoice(event)">';
        $html .= '<i class="bi bi-send-check ' . $iconClass . '"></i> Upload & Kirim Invoice</a></li>';

        $html .= '<li><hr class="dropdown-divider"></li>';

        // Menu 2: Upload Bukti Bayar (admin bisa upload bukti untuk user)
        // DISABLED jika invoice belum TERKIRIM (invoiceNo kosong) ATAU sudah terverifikasi
        $uploadBuktiDisabled = (empty($invoiceNo) || $status == 2) ? 'disabled' : '';

        if (empty($invoiceNo)) {
            $uploadBuktiTitle = 'Kirim invoice terlebih dahulu';
        } else if ($status == 2) {
            $uploadBuktiTitle = 'Sudah terverifikasi';
        } else {
            $uploadBuktiTitle = 'Upload Bukti Bayar';
        }

        $iconClassBukti = (empty($invoiceNo) || $status == 2) ? 'text-secondary' : 'text-warning';
        $html .= '<li><a class="dropdown-item btn-action ' . $uploadBuktiDisabled . '" href="javascript:void(0)" title="' . $uploadBuktiTitle . '" data-fileurl="' . esc($fileUrl) . '" onclick="uploadBukti(event)">';
        $html .= '<i class="bi bi-upload ' . $iconClassBukti . '"></i> Upload Bukti Bayar</a></li>';

        $html .= '<li><hr class="dropdown-divider"></li>';

        // Menu 3: Terima (hanya aktif jika Belum Diverifikasi atau Tidak Terverifikasi)
        if ($status == 1 || $status == 3) {
            $html .= '<li><a class="dropdown-item btn-action" href="javascript:void(0)" title="Terima & Verifikasi" onclick="terimaVerifikasi(event)">';
            $html .= '<i class="bi bi-check-circle text-success"></i> Terima & Verifikasi</a></li>';
        } else {
            $html .= '<li><a class="dropdown-item btn-action disabled" href="javascript:void(0)" title="Tidak perlu verifikasi">';
            $html .= '<i class="bi bi-check-circle text-secondary"></i> Terima & Verifikasi</a></li>';
        }

        // Menu 4: Tolak (hanya aktif jika Belum Diverifikasi)
        if ($status == 1) {
            $html .= '<li><a class="dropdown-item btn-action" href="javascript:void(0)" title="Tolak Verifikasi" onclick="tolakVerifikasi(event)">';
            $html .= '<i class="bi bi-x-circle text-danger"></i> Tolak Verifikasi</a></li>';
        } else {
            $html .= '<li><a class="dropdown-item btn-action disabled" href="javascript:void(0)" title="Tidak bisa ditolak">';
            $html .= '<i class="bi bi-x-circle text-secondary"></i> Tolak Verifikasi</a></li>';
        }

        $html .= '</ul>';
        $html .= '</div>';
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
     * Upload & Kirim Invoice (GABUNGAN) - Upload file invoice + set nomor invoice sekaligus
     */
    public function uploadKirimInvoice()
    {
        try {
            $file = $this->request->getFile('file_invoice');
            $invoiceNo = trim($this->request->getPost('no_invoice')); // FIX: sesuaikan dengan name di form
            $encId = $this->request->getPost('id');

            // Validasi ID
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
            $currentData = $model->getDataById($this->id, $id);

            if (!$currentData) {
                return $this->response->setJSON([
                    'res' => false,
                    'msg' => 'Data pembayaran tidak ditemukan',
                    'xname' => csrf_token(),
                    'xhash' => csrf_hash()
                ]);
            }

            // Cek apakah invoice sudah terkirim
            if (!empty($currentData->bayarInvoiceNo)) {
                return $this->response->setJSON([
                    'res' => false,
                    'msg' => 'Invoice sudah terkirim sebelumnya',
                    'xname' => csrf_token(),
                    'xhash' => csrf_hash()
                ]);
            }

            // Validasi nomor invoice (wajib diisi)
            if (empty($invoiceNo)) {
                return $this->response->setJSON([
                    'res' => false,
                    'msg' => 'Nomor invoice harus diisi',
                    'xname' => csrf_token(),
                    'xhash' => csrf_hash()
                ]);
            }

            // Validasi file invoice (wajib diupload)
            if (!($file && $file->isValid() && !$file->hasMoved())) {
                return $this->response->setJSON([
                    'res' => false,
                    'msg' => 'File invoice harus diupload',
                    'xname' => csrf_token(),
                    'xhash' => csrf_hash()
                ]);
            }

            // Upload file invoice
            $uploadResult = $this->doUpload($file, 'invoice');

            if (!$uploadResult['status']) {
                return $this->response->setJSON([
                    'res' => false,
                    'msg' => $uploadResult['msg'],
                    'xname' => csrf_token(),
                    'xhash' => csrf_hash()
                ]);
            }

            $filename = $uploadResult['filename'];

            // Hapus file invoice lama jika ada
            if (!empty($currentData->bayarInvoiceFile)) {
                $oldFile = FCPATH . 'uploads/invoice/' . $currentData->bayarInvoiceFile;
                if (file_exists($oldFile)) {
                    @unlink($oldFile);
                }
            }

            // Update data pembayaran: simpan file invoice + nomor invoice + tanggal invoice
            $dataPembayaran = [
                'bayarInvoiceFile' => $filename,
                'bayarInvoiceNo' => $invoiceNo,
                'bayarInvoiceTgl' => date('Y-m-d H:i:s')
            ];

            $update = $model->updateData($dataPembayaran, $this->id, $id);

            if (!$update) {
                // Jika gagal update, hapus file yang sudah diupload
                $uploadedFile = FCPATH . 'uploads/invoice/' . $filename;
                if (file_exists($uploadedFile)) {
                    @unlink($uploadedFile);
                }

                return $this->response->setJSON([
                    'res' => false,
                    'msg' => 'Gagal menyimpan data invoice',
                    'xname' => csrf_token(),
                    'xhash' => csrf_hash()
                ]);
            }

            // Update nomor transaksi di tabel layanan (sync dengan invoice)
            $modelLayanan = new MyModel('simlab_t_layanan');
            $modelLayanan->updateData(
                ['lnNoTransaksi' => $invoiceNo],
                'lnKode',
                $currentData->bayarLnKode
            );

            return $this->response->setJSON([
                'res' => 'success',
                'msg' => 'Invoice berhasil diupload dan dikirim ke pelanggan',
                'xname' => csrf_token(),
                'xhash' => csrf_hash()
            ]);
        } catch (\Exception $e) {
            log_message('error', 'UploadKirimInvoice exception: ' . $e->getMessage());
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

            // Update bayarStatus menjadi 1 (Terverifikasi) dan kosongkan bayarCatatan
            $data = [
                'bayarStatus' => 1,
                'bayarCatatan' => null  // Hapus catatan penolakan lama
            ];
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

    /**
     * Upload file invoice (PDF) - Admin upload invoice untuk pelanggan
     */
    public function uploadInvoice()
    {
        try {
            $file = $this->request->getFile('file_invoice');
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

            // Upload file ke folder invoice
            $uploadResult = $this->doUpload($file, 'invoice');

            if (!$uploadResult['status']) {
                return $this->response->setJSON([
                    'res' => false,
                    'msg' => $uploadResult['msg'],
                    'xname' => csrf_token(),
                    'xhash' => csrf_hash()
                ]);
            }

            $filename = $uploadResult['filename'];

            // Update file invoice di database (tanpa nomor invoice)
            $model = new MyModel($this->table);
            $currentData = $model->getDataById($this->id, $id);

            // Hapus file lama jika ada
            if (!empty($currentData->bayarInvoiceFile)) {
                $oldFile = FCPATH . 'uploads/invoice/' . $currentData->bayarInvoiceFile;
                if (file_exists($oldFile)) {
                    @unlink($oldFile);
                }
            }

            // Update file invoice di database
            $dataPembayaran = [
                'bayarInvoiceFile' => $filename
            ];

            $update = $model->updateData($dataPembayaran, $this->id, $id);

            if (!$update) {
                return $this->response->setJSON([
                    'res' => false,
                    'msg' => 'Gagal menyimpan invoice',
                    'xname' => csrf_token(),
                    'xhash' => csrf_hash()
                ]);
            }

            // Simpan ke session juga (sebagai penanda file baru diupload)
            session()->set('temp_invoice_' . $id, $filename);

            return $this->response->setJSON([
                'res' => 'success',
                'msg' => 'Invoice berhasil diupload. Silakan kirim ke pelanggan.',
                'xname' => csrf_token(),
                'xhash' => csrf_hash()
            ]);
        } catch (\Exception $e) {
            log_message('error', 'UploadInvoice exception: ' . $e->getMessage());
            return $this->response->setJSON([
                'res' => false,
                'msg' => 'Terjadi kesalahan: ' . $e->getMessage(),
                'xname' => csrf_token(),
                'xhash' => csrf_hash()
            ]);
        }
    }

    /**
     * Kirim invoice ke pelanggan - Update nomor invoice dan file ke database
     */
    public function kirimInvoice()
    {
        try {
            $encId = $this->request->getPost('id');
            $noInvoice = $this->request->getPost('no_invoice');

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
            $currentData = $model->getDataById($this->id, $id);

            // Cek file dari session (file yang baru diupload)
            $sessionKey = 'temp_invoice_' . $id;
            $tempFilename = session()->get($sessionKey);

            // Jika tidak ada file di session, cek di database (file lama)
            if (empty($tempFilename) && (empty($currentData) || empty($currentData->bayarInvoiceFile))) {
                return $this->response->setJSON([
                    'res' => false,
                    'msg' => 'Upload file invoice terlebih dahulu',
                    'xname' => csrf_token(),
                    'xhash' => csrf_hash()
                ]);
            }

            // Gunakan file dari session jika ada, jika tidak gunakan file lama
            $filename = !empty($tempFilename) ? $tempFilename : $currentData->bayarInvoiceFile;

            // Validasi nomor invoice
            if (empty($noInvoice)) {
                return $this->response->setJSON([
                    'res' => false,
                    'msg' => 'Nomor invoice harus diisi',
                    'xname' => csrf_token(),
                    'xhash' => csrf_hash()
                ]);
            }

            // Cek apakah nomor invoice sudah ada
            $cekInvoice = $model->getDataByArray(['bayarInvoiceNo' => $noInvoice]);
            if ($cekInvoice) {
                return $this->response->setJSON([
                    'res' => false,
                    'msg' => 'Nomor invoice sudah digunakan',
                    'xname' => csrf_token(),
                    'xhash' => csrf_hash()
                ]);
            }

            // Ambil lnKode dari pembayaran
            $lnKode = $currentData->bayarLnKode;
            if (empty($lnKode)) {
                return $this->response->setJSON([
                    'res' => false,
                    'msg' => 'Data layanan tidak ditemukan',
                    'xname' => csrf_token(),
                    'xhash' => csrf_hash()
                ]);
            }

            // Hapus file lama jika ada dan berbeda dengan file baru
            if (!empty($currentData->bayarInvoiceFile) && !empty($tempFilename) && $currentData->bayarInvoiceFile !== $tempFilename) {
                $oldFilePath = FCPATH . 'uploads/invoice/' . $currentData->bayarInvoiceFile;
                if (file_exists($oldFilePath)) {
                    @unlink($oldFilePath);
                }
            }

            // Update nomor invoice dan filename di tabel pembayaran
            $dataPembayaran = [
                'bayarInvoiceNo' => $noInvoice,
                'bayarInvoiceFile' => $filename
            ];

            $updatePembayaran = $model->updateData($dataPembayaran, $this->id, $id);

            if (!$updatePembayaran) {
                return $this->response->setJSON([
                    'res' => false,
                    'msg' => 'Gagal menyimpan invoice',
                    'xname' => csrf_token(),
                    'xhash' => csrf_hash()
                ]);
            }

            // Hapus dari session setelah berhasil save
            if (!empty($tempFilename)) {
                session()->remove($sessionKey);
            }

            // Update nomor invoice ke tabel simlab_t_layanan kolom lnNoTransaksi
            $db = \Config\Database::connect();
            $layananBuilder = $db->table('simlab_t_layanan');
            $layananBuilder->where('lnKode', $lnKode);
            $layananBuilder->set('lnNoTransaksi', $noInvoice);
            $layananBuilder->update();

            return $this->response->setJSON([
                'res' => true,
                'msg' => 'Invoice berhasil dikirim ke pelanggan',
                'xname' => csrf_token(),
                'xhash' => csrf_hash()
            ]);
        } catch (\Exception $e) {
            log_message('error', 'KirimInvoice exception: ' . $e->getMessage());
            return $this->response->setJSON([
                'res' => false,
                'msg' => 'Terjadi kesalahan: ' . $e->getMessage(),
                'xname' => csrf_token(),
                'xhash' => csrf_hash()
            ]);
        }
    }
}
