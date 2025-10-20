<?php

namespace Modules\Tagihan\Controllers;

use App\Controllers\BaseController;
use App\Models\MyModel;

class Tagihan extends BaseController
{
    private $table = 'simlab_t_pembayaran';
    private $id = 'bayarKode';

    public function index()
    {
        $data = [
            'title' => 'Tagihan',
        ];
        return view('Modules\Tagihan\Views\v_tagihan', $data);
    }

    /**
     * Get data list untuk tabel - menampilkan semua tagihan
     */
    public function dataList()
    {
        try {
            $model = new MyModel($this->table);
            $data = [];

            // JOIN dengan simlab_t_layanan untuk mendapatkan data layanan dan email pemesan
            $joins = [
                'simlab_t_layanan' => 'simlab_t_pembayaran.bayarLnKode = simlab_t_layanan.lnKode'
            ];

            $select = 'simlab_t_pembayaran.*, simlab_t_layanan.lnKode, simlab_t_layanan.lnAccEmail';

            $orderBy = ['simlab_t_pembayaran.bayarKode' => 'DESC'];

            $list = $model->getAllDataByJoinWithOrder($joins, [], $orderBy, $select, 'inner');

            // Log untuk debugging
            log_message('debug', 'Tagihan dataList - Total records: ' . count($list));

            $no = 1;
            foreach ($list as $row) {
                $encrypted_id = bin2hex(service('encrypter')->encrypt($row->bayarKode));

                // Cek status dari simlab_t_layanan_detil
                $detilStatus = $this->getDetilStatus($row->bayarLnKode);

                // Status badge
                $status = $this->formatStatus($detilStatus);

                // Cek apakah ada file di session (file temporary yang belum disave)
                $sessionKey = 'temp_invoice_' . $row->bayarKode;
                $tempFile = session()->get($sessionKey);
                $currentFile = !empty($tempFile) ? $tempFile : ($row->bayarInvoiceFile ?? '');

                // Tombol aksi
                $aksi = $this->aksiButton($encrypted_id, $detilStatus, $currentFile);

                // Gunakan indexed array, bukan associative array
                $response = [];
                $response[] = $row->bayarInvoiceNo ?? '<span class="badge bg-warning">Belum Ditambahkan</span>';
                $response[] = $row->lnAccEmail ?? '-';
                $response[] = 'Rp ' . number_format($row->bayarTotalBiaya ?? 0, 0, ',', '.');

                // Tampilkan info file (dari session atau database)
                if (!empty($currentFile)) {
                    if (!empty($tempFile)) {
                        // File baru dari session (belum disave)
                        $response[] = '<span class="badge bg-info"><i class="bi bi-clock-history"></i> File Terupload</span>';
                    } else {
                        // File dari database (sudah disave)
                        $response[] = '<a href="' . base_url('uploads/invoice/' . $currentFile) . '" target="_blank" class="btn btn-sm btn-info"><i class="bi bi-file-pdf"></i> Lihat</a>';
                    }
                } else {
                    $response[] = '<span class="text-muted">Belum upload</span>';
                }

                $response[] = $status;
                $response[] = $aksi;

                $data[] = $response;
            }

            return $this->response->setJSON(["items" => $data]);
        } catch (\Exception $e) {
            log_message('error', 'Tagihan dataList error: ' . $e->getMessage());
            return $this->response->setJSON([
                "items" => [],
                "error" => $e->getMessage()
            ]);
        }
    }

    /**
     * Get status dari tabel simlab_t_layanan_detil
     * Cek apakah semua detil sudah status = 1 (terkirim)
     */
    private function getDetilStatus($lnKode)
    {
        if (empty($lnKode)) {
            return 0;
        }

        $db = \Config\Database::connect();
        $builder = $db->table('simlab_t_layanan_detil');

        // Hitung total detil
        $totalDetil = $builder->where('detLnKode', $lnKode)->countAllResults(false);

        // Hitung detil yang sudah status = 1
        $detilSelesai = $builder->where('detLnKode', $lnKode)
            ->where('detKirim', 1)
            ->countAllResults();

        // Jika semua detil sudah selesai, return 1, jika tidak return 0
        return ($totalDetil > 0 && $totalDetil == $detilSelesai) ? 1 : 0;
    }

    /**
     * Format status badge
     */
    private function formatStatus($status)
    {
        switch ($status) {
            case 0:
                return '<span class="badge bg-warning">Belum Diproses</span>';
            case 1:
                return '<span class="badge bg-success">Terkirim</span>';
            default:
                return '<span class="badge bg-secondary">Unknown</span>';
        }
    }

    /**
     * Tombol aksi (menggunakan format yang sama dengan Riwayat_Pembayaran)
     */
    private function aksiButton($id, $status, $file)
    {
        // Button "Proses & Kirim" hanya aktif jika:
        // 1. Ada file invoice (bayarInvoiceFile tidak kosong)
        // 2. Status masih 0 (Belum Diproses)
        $prosesDisabled = (empty($file) || $status != 0) ? 'disabled' : '';
        $prosesClass = (empty($file) || $status != 0) ? 'text-secondary' : 'text-success';
        $prosesTitle = empty($file)
            ? 'Upload invoice terlebih dahulu'
            : ($status != 0 ? 'Sudah diproses' : 'Proses & Kirim');

        return '<div id="' . $id . '" class="float-end">
            <span class="text-primary btn-action" title="Upload Invoice" onclick="uploadFile(event)">
                <i class="bi bi-upload"></i></span> 
            <label class="divider">|</label>
            <span class="' . $prosesClass . ' btn-action" ' . $prosesDisabled . ' title="' . $prosesTitle . '" onclick="prosesItem(event)">
                <i class="bi bi-check-circle"></i></span>
        </div>';
    }

    /**
     * Upload file invoice (PDF)
     */
    public function upload()
    {
        try {
            log_message('debug', 'Upload request received');

            $file = $this->request->getFile('file_invoice');
            $encId = $this->request->getPost('id');

            if (empty($encId)) {
                log_message('error', 'ID tidak ditemukan dalam request');
                return $this->response->setJSON([
                    'res' => 'error',
                    'msg' => 'ID tidak ditemukan',
                    'xname' => csrf_token(),
                    'xhash' => csrf_hash()
                ]);
            }

            try {
                $id = service('encrypter')->decrypt(hex2bin($encId));
            } catch (\Throwable $e) {
                log_message('error', 'Decrypt failed: ' . $e->getMessage());
                return $this->response->setJSON([
                    'res' => 'error',
                    'msg' => 'ID tidak valid',
                    'xname' => csrf_token(),
                    'xhash' => csrf_hash()
                ]);
            }

            log_message('debug', 'Decrypted ID: ' . $id);

            if (!($file && $file->isValid() && !$file->hasMoved())) {
                log_message('error', 'File tidak valid atau tidak ditemukan');
                return $this->response->setJSON([
                    'res' => 'error',
                    'msg' => 'File tidak valid atau tidak ditemukan',
                    'xname' => csrf_token(),
                    'xhash' => csrf_hash()
                ]);
            }

            log_message('debug', 'File size: ' . $file->getSize() . ' bytes');
            log_message('debug', 'File type: ' . $file->getMimeType());

            // Upload file ke folder uploads/invoice/
            $uploadResult = $this->doUpload($file, 'invoice');

            if (!$uploadResult['status']) {
                log_message('error', 'Upload failed: ' . $uploadResult['msg']);
                return $this->response->setJSON([
                    'res' => 'error',
                    'msg' => $uploadResult['msg'],
                    'xname' => csrf_token(),
                    'xhash' => csrf_hash()
                ]);
            }

            $filename = $uploadResult['filename'];
            log_message('debug', 'File uploaded successfully: ' . $filename);

            // TIDAK update database, hanya simpan filename untuk sementara
            // File akan tersimpan di session untuk digunakan saat proses
            session()->set('temp_invoice_' . $id, $filename);

            log_message('debug', 'File saved to session: temp_invoice_' . $id . ' = ' . $filename);

            return $this->response->setJSON([
                'res' => 'success',
                'msg' => 'File invoice berhasil diupload. Silakan klik "Proses & Kirim" untuk mengirimkan invoice ke pelanggan.',
                'filename' => $filename,
                'xname' => csrf_token(),
                'xhash' => csrf_hash()
            ]);
        } catch (\Exception $e) {
            log_message('error', 'Upload exception: ' . $e->getMessage());
            return $this->response->setJSON([
                'res' => 'error',
                'msg' => 'Terjadi kesalahan: ' . $e->getMessage(),
                'xname' => csrf_token(),
                'xhash' => csrf_hash()
            ]);
        }
    }

    /**
     * Helper untuk upload file PDF
     * @param $file - uploaded file object
     * @param $folder - subfolder dalam uploads/ (default: 'invoice')
     */
    private function doUpload($file, $folder = 'invoice')
    {
        // Pastikan file valid dan belum dipindahkan
        if (!($file && $file->isValid() && !$file->hasMoved())) {
            return ['status' => false, 'msg' => 'File tidak valid atau sudah dipindahkan'];
        }

        // Validasi tipe file (hanya PDF)
        $allowedExt  = ['pdf'];
        $allowedMime = ['application/pdf'];

        $ext  = strtolower($file->getClientExtension());
        $tmpName = $file->getTempName();

        // Pastikan file temporary ada
        if (!is_file($tmpName)) {
            return ['status' => false, 'msg' => 'File sementara tidak ditemukan'];
        }

        // Deteksi MIME type dengan finfo (lebih akurat)
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
            return ['status' => false, 'msg' => 'Format file harus PDF. Detected: ' . $detectedMime];
        }

        // Validasi ukuran file (max 5MB)
        if ($file->getSize() > 5 * 1024 * 1024) {
            return ['status' => false, 'msg' => 'Ukuran file maksimal 5MB'];
        }

        // Generate filename aman dengan random bytes
        try {
            $rand = bin2hex(random_bytes(8));
        } catch (\Exception $e) {
            $rand = bin2hex(openssl_random_pseudo_bytes(8));
        }
        $filename = time() . '_' . $rand . '.' . $ext;

        // Path ke folder uploads/{folder}/
        $path = FCPATH . 'uploads/' . $folder;

        // Pastikan folder ada
        if (!is_dir($path)) {
            @mkdir($path, 0755, true);
        }

        // Pindahkan file
        try {
            $file->move($path, $filename, true);
            $fullPath = $path . DIRECTORY_SEPARATOR . $filename;

            // Verifikasi file berhasil dipindahkan
            if (is_file($fullPath)) {
                @chmod($fullPath, 0644);
                return ['status' => true, 'filename' => $filename];
            } else {
                return ['status' => false, 'msg' => 'File gagal dipindahkan ke folder tujuan'];
            }
        } catch (\Exception $e) {
            return ['status' => false, 'msg' => 'Gagal memindahkan file: ' . $e->getMessage()];
        }
    }

    /**
     * Proses tagihan - tambah nomor invoice dan ubah status detil layanan
     */
    public function proses()
    {
        // Debug: log semua POST data
        log_message('debug', '=== PROSES REQUEST START ===');
        log_message('debug', 'POST data: ' . json_encode($this->request->getPost()));
        log_message('debug', 'Headers: ' . json_encode($this->request->headers()));

        $id = $this->request->getPost('id');
        $noInvoice = $this->request->getPost('no_invoice');

        log_message('debug', 'ID received: ' . ($id ?? 'NULL'));
        log_message('debug', 'No Invoice received: ' . ($noInvoice ?? 'NULL'));

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

        // Cek apakah file invoice sudah diupload (di session)
        $currentData = $model->getDataById($this->id, $id);

        // Cek file dari session (file yang baru diupload)
        $sessionKey = 'temp_invoice_' . $id;
        $tempFilename = session()->get($sessionKey);

        // Jika tidak ada file di session, cek di database (file lama)
        if (empty($tempFilename) && (empty($currentData) || empty($currentData->bayarInvoiceFile))) {
            return $this->response->setJSON([
                'res' => false,
                'msg' => 'File invoice belum diupload. Upload terlebih dahulu sebelum memproses.',
                'xname' => csrf_token(),
                'xhash' => csrf_hash()
            ]);
        }

        // Gunakan file dari session jika ada, jika tidak gunakan file lama dari database
        $filename = !empty($tempFilename) ? $tempFilename : $currentData->bayarInvoiceFile;

        log_message('debug', 'Processing with filename: ' . $filename);
        log_message('debug', 'Temp filename from session: ' . ($tempFilename ?? 'null'));
        log_message('debug', 'Old filename from DB: ' . ($currentData->bayarInvoiceFile ?? 'null'));

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
                log_message('debug', 'Old file deleted: ' . $currentData->bayarInvoiceFile);
            }
        }

        // Update nomor invoice dan filename di tabel pembayaran
        $dataPembayaran = [
            'bayarInvoiceNo' => $noInvoice,
            'bayarInvoiceFile' => $filename  // Simpan filename ke database
        ];

        $updatePembayaran = $model->updateData($dataPembayaran, $this->id, $id);

        if (!$updatePembayaran) {
            return $this->response->setJSON([
                'res' => false,
                'msg' => 'Gagal menyimpan nomor invoice',
                'xname' => csrf_token(),
                'xhash' => csrf_hash()
            ]);
        }

        // Hapus dari session setelah berhasil save
        if (!empty($tempFilename)) {
            session()->remove($sessionKey);
            log_message('debug', 'Session key removed: ' . $sessionKey);
        }

        // Update nomor invoice ke tabel simlab_t_layanan kolom lnNoTransaksi
        $db = \Config\Database::connect();
        $layananBuilder = $db->table('simlab_t_layanan');
        $layananBuilder->where('lnKode', $lnKode);
        $layananBuilder->set('lnNoTransaksi', $noInvoice);
        $updateLayanan = $layananBuilder->update();

        log_message('debug', 'Update lnNoTransaksi for lnKode ' . $lnKode . ': ' . ($updateLayanan ? 'success' : 'failed'));
        log_message('debug', 'Affected rows (layanan): ' . $db->affectedRows());

        if (!$updateLayanan) {
            log_message('warning', 'Failed to update lnNoTransaksi in simlab_t_layanan for lnKode: ' . $lnKode);
        }

        // Update status di tabel simlab_t_layanan_detil
        $builder = $db->table('simlab_t_layanan_detil');

        // Cek apakah ada detil dengan lnKode ini
        $countDetil = $builder->where('detLnKode', $lnKode)->countAllResults();
        log_message('debug', 'Total detil found with lnKode ' . $lnKode . ': ' . $countDetil);

        $msg = 'Tagihan berhasil diproses';

        // Jika ada detil, update statusnya
        if ($countDetil > 0) {
            log_message('debug', 'Updating detKirim for lnKode: ' . $lnKode);

            // Update semua detil yang terkait dengan lnKode ini
            $builder = $db->table('simlab_t_layanan_detil');
            $builder->where('detLnKode', $lnKode);
            $builder->set('detKirim', 1);
            $updateDetil = $builder->update();

            log_message('debug', 'Update detil result: ' . ($updateDetil ? 'success' : 'failed'));
            log_message('debug', 'Affected rows: ' . $db->affectedRows());
            log_message('debug', 'Last query: ' . $db->getLastQuery());

            $msg = 'Tagihan berhasil diproses dan status layanan diperbarui';
        } else {
            log_message('debug', 'No detil records found for lnKode ' . $lnKode . ', skipping detil status update');
        }

        return $this->response->setJSON([
            'res' => true,
            'msg' => $msg,
            'xname' => csrf_token(),
            'xhash' => csrf_hash()
        ]);
    }

    function aksi($id)
    {
        return '<div id="' . $id . '" class="float-end">
			<span class="text-secondary btn-action" title="Ubah" onclick="editItem(event)">
				<i class="bi bi-pencil-square"></i></span> 
			<label class="divider">|</label>
		</div>';
    }
}
