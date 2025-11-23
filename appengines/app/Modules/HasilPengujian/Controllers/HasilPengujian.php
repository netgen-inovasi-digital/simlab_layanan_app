<?php

namespace Modules\HasilPengujian\Controllers;

use App\Controllers\BaseController;
use App\Models\MyModel;
use Modules\HasilPengujian\Models\HasilPengujianModel;

/**
 * HasilPengujian Controller
 * 
 * Controller untuk mengelola hasil pengujian laboratorium
 * Menangani upload, submit, dan verifikasi LHUS (Laporan Hasil Uji Sampel)
 */
class HasilPengujian extends BaseController
{
    private $table = 'simlab_t_layanan';
    private $id    = 'lnKode';
    protected $encrypter;
    protected $hasilPengujianModel;

    public function __construct()
    {
        $this->encrypter = \Config\Services::encrypter();
        $this->hasilPengujianModel = new HasilPengujianModel();
        helper('form');
    }

    /**
     * Halaman utama hasil pengujian
     */
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

    /**
     * Get data list untuk tabel hasil pengujian
     * Mendukung filter berdasarkan status layanan
     */
    public function dataList()
    {
        $session = session();
        $user_id = (int) ($session->get('id_user') ?? 0);

        // Parse lnStatus filter: "4", "4,5,6", atau token khusus "tolak" dan "terunggah"
        $lnStatusParam = (string) ($this->request->getGet('lnStatus') ?? '');
        list($lnStatusFilter, $wantReject, $wantUploaded) = $this->parseStatusFilter($lnStatusParam);

        // Get layanan codes related to user via model
        $lnKodeList = $this->hasilPengujianModel->getLayananKodesByUserId($user_id);

        if (empty($lnKodeList)) {
            return $this->response->setJSON(["items" => []]);
        }

        // Get layanan list with filters via model
        $list = $this->hasilPengujianModel->getLayananListForPenyelia(
            $lnKodeList,
            $user_id,
            $lnStatusFilter,
            $wantReject,
            $wantUploaded
        );

        // Format response data
        $data = $this->formatDataListResponse($list, $user_id);

        return $this->response->setJSON(["items" => $data]);
    }

    /**
     * Get detail list untuk modal
     */
    public function detailList($id = null)
    {
        if (!$id) {
            return $this->response->setJSON(['items' => []]);
        }

        $session = session();
        $user_id = (int) ($session->get('id_user') ?? 0);

        // Decrypt kode layanan
        try {
            $kode = $this->decryptId($id);
        } catch (\Exception $e) {
            return $this->response->setJSON(['items' => []]);
        }

        // Check user authorization via model
        if (!$this->hasilPengujianModel->isUserAuthorizedForLayanan($kode, $user_id)) {
            return $this->response->setJSON(['items' => []]);
        }

        // Encrypted ln
        $encLnId = bin2hex($this->encrypter->encrypt($kode));

        // Get detail list via model
        $rows = $this->hasilPengujianModel->getDetailListForPenyelia($kode, $user_id);

        // Get layanan status via model
        $layananRow = $this->hasilPengujianModel->getLayananStatus($kode);
        $lnStatus = $layananRow->lnStatus ?? null;

        // Format response
        $data = [];
        $no = 1;
        $allUploaded = true;

        foreach ($rows as $row) {
            $response = $this->formatDetailRow($row, $no++, $encLnId, $lnStatus, $allUploaded);
            $data[] = $response;
        }

        return $this->response->setJSON([
            'items' => $data,
            'encLn' => $encLnId,
            'allFilesUploaded' => $allUploaded,
            'lnKode' => $kode
        ]);
    }

    /**
     * Submit LHUS ke manajer
     */
    public function submit($idParam = null)
    {
        $encId = $this->request->getPost('id') ?? $idParam ?? service('uri')->getSegment(3);

        if (empty($encId)) {
            return $this->jsonResponse('error', 'ID missing');
        }

        try {
            $lnKode = $this->decryptId($encId);
        } catch (\Throwable $e) {
            return $this->jsonResponse('error', 'Invalid ID');
        }

        $session = session();
        $user_id = (int) ($session->get('id_user') ?? 0);

        // Check if record exists
        $model = new MyModel($this->table);
        $row = $model->getDataById($this->id, $lnKode);
        if (!$row) {
            return $this->jsonResponse('error', 'Record not found');
        }

        // Check for missing files
        $missingData = $this->hasilPengujianModel->checkMissingFilesForUser($lnKode, $user_id);
        
        if ($missingData['missingCount'] > 0) {
            return $this->jsonResponse(true, 
                'Berhasil dikirim, sisa ' . $missingData['missingCount'] . ' layanan yang perlu diaccc', 
                [
                    'waiting_others' => true,
                    'pending_total' => $missingData['missingCount'],
                    'missing_detKode' => $missingData['missingItems'],
                    'parent_updated' => false
                ]
            );
        }

        // Process submission with transaction
        return $this->processSubmission($lnKode, $user_id);
    }

    /**
     * Upload LHUS file
     */
    public function upload()
    {
        $file = $this->request->getFile('lhus_file');
        $encId = $this->request->getPost('id');
        $detKode = $this->request->getPost('detKode');

        $session = session();
        $user_id = $session->get('id_user');

        if (empty($encId)) {
            return $this->jsonResponse('error', 'ID tidak ditemukan');
        }

        try {
            $lnKode = $this->decryptId($encId);
        } catch (\Throwable $e) {
            return $this->jsonResponse('error', 'ID tidak valid');
        }

        if (!($file && $file->isValid() && !$file->hasMoved())) {
            return $this->jsonResponse('error', 'File tidak valid atau tidak dipilih');
        }

        // Upload file
        $uploadResult = $this->doUpload($file);
        if (!$uploadResult['status']) {
            return $this->jsonResponse('error', $uploadResult['msg']);
        }

        $filename = $uploadResult['filename'];

        // Save file info to database via model
        try {
            if (!empty($detKode)) {
                return $this->handleSingleFileUpload($detKode, $lnKode, $filename, $user_id);
            } else {
                return $this->handleBulkFileUpload($lnKode, $filename, $user_id);
            }
        } catch (\Throwable $e) {
            $this->deleteUploadedFile($filename);
            return $this->jsonResponse('error', 'Error saat menyimpan ke detil: ' . $e->getMessage());
        }
    }

    /**
     * Get sample identity information
     */
    public function getSampleIdentity($lnKode = null)
    {
        if (!$lnKode) {
            return $this->response->setJSON([
                'success' => false,
                'message' => 'Kode layanan tidak ditemukan'
            ]);
        }

        $modelIdentitasSampel = new MyModel('t_identitas_sampel');
        $sampleData = $modelIdentitasSampel->getDataById('kode_layanan', $lnKode);

        if (!$sampleData) {
            return $this->response->setJSON([
                'success' => false,
                'message' => 'Data identitas sampel tidak ditemukan'
            ]);
        }

        return $this->response->setJSON([
            'success' => true,
            'data' => [
                'jenis' => $sampleData->jenis ?? '-',
                'kemasan' => $sampleData->kemasan ?? '-',
                'sifat' => $sampleData->sifat ?? '-',
                'sisa' => $sampleData->sisa ?? '-',
                'deskripsi' => $sampleData->deskripsi ?? '-',
                'keterangan_khusus' => $sampleData->keterangan_khusus ?? '-'
            ]
        ]);
    }

    // ==================== PRIVATE HELPER METHODS ====================

    /**
     * Parse status filter dari parameter GET
     */
    private function parseStatusFilter(string $lnStatusParam): array
    {
        $lnStatusFilter = [];
        $wantReject = false;
        $wantUploaded = false;
        
        if ($lnStatusParam !== '') {
            foreach (preg_split('/[,\s]+/', $lnStatusParam, -1, PREG_SPLIT_NO_EMPTY) as $p) {
                $tp = strtolower(trim($p));
                if (in_array($tp, ['tolak','reject','ditolak'], true)) {
                    $wantReject = true;
                    continue;
                }
                if (in_array($tp, ['terunggah','uploaded'], true)) {
                    $wantUploaded = true;
                    continue;
                }
                if ($tp !== '' && is_numeric($tp)) {
                    $lnStatusFilter[] = (int) $tp;
                }
            }
            $lnStatusFilter = array_values(array_unique($lnStatusFilter));
        }

        return [$lnStatusFilter, $wantReject, $wantUploaded];
    }

    /**
     * Format data list response untuk tabel
     */
    private function formatDataListResponse(array $list, int $user_id): array
    {
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

        return $data;
    }

    /**
     * Format detail row untuk modal
     */
    private function formatDetailRow(object $row, int $no, string $encLnId, ?int $lnStatus, bool &$allUploaded): array
    {
        $response = [];
        $response[] = $no;
        $response[] = $row->nama_layanan ?? '-';
        $response[] = isset($row->jumlah) ? (int)$row->jumlah : 0;

        // Format keterangan
        $keteranganHtml = $this->formatKeteranganHtml($row->detKet ?? '');
        $response[] = $keteranganHtml;

        // Check file status
        $detFilesMax = isset($row->detFilesMax) ? (int)$row->detFilesMax : null;
        $fileRow = $this->hasilPengujianModel->getFileLhus($row->kode);
        
        $rowHasFile = false;
        $fileUrl = null;

        if ($fileRow && !empty($fileRow->file_lhus)) {
            $rowHasFile = true;
            $fileUrl = base_url('uploads/lhus/' . ltrim($fileRow->file_lhus, '/'));
        }

        if (!$rowHasFile) {
            $allUploaded = false;
        }

        // Format action buttons
        $combinedHtml = $this->formatActionButtons($rowHasFile, $fileUrl, $row->kode, $encLnId);
        $response[] = $this->formatStatusBadge($detFilesMax, $lnStatus, $rowHasFile);
        $response[] = $combinedHtml;

        // Format keterangan manajer
        $keteranganManajerHtml = $this->formatKeteranganManajerHtml($row->detKetManajer ?? '');
        $response[] = $keteranganManajerHtml;

        // Username approver
        $accBy = !empty($row->acc_by) ? esc($row->acc_by) : '-';
        $response[] = '<div class="text-center">'.$accBy.'</div>';

        return $response;
    }

    /**
     * Format keterangan HTML
     */
    private function formatKeteranganHtml(string $text): string
    {
        return '<div style="display:block; max-width:260px; min-width:160px; width:100%;'
            . 'max-height:120px; min-height:48px; overflow-y:auto; overflow-x:hidden;'
            . 'padding:4px 6px; border:1px solid #ddd; border-radius:4px; background:#f9f9f9;'
            . 'white-space:pre-wrap; word-break:break-word; font-size:0.9rem;">'
            . htmlspecialchars($text, ENT_QUOTES, 'UTF-8') .
            '</div>';
    }

    /**
     * Format keterangan manajer HTML
     */
    private function formatKeteranganManajerHtml(string $text): string
    {
        return '<div style="display:block; max-width:260px; min-width:160px; width:100%;'
            . 'max-height:120px; min-height:48px; overflow-y:auto; overflow-x:hidden;'
            . 'padding:4px 6px; border:1px solid #e6e6ff; border-radius:4px; background:#fbfbff;'
            . 'white-space:pre-wrap; word-break:break-word; font-size:0.9rem; color:#333;">'
            . htmlspecialchars($text, ENT_QUOTES, 'UTF-8') .
            '</div>';
    }

    /**
     * Format action buttons untuk upload dan view file
     */
    private function formatActionButtons(bool $hasFile, ?string $fileUrl, $detKode, string $encLnId): string
    {
        $combinedHtml = '<div class="d-flex justify-content-center gap-2 align-items-center">';

        // Eye button
        if ($hasFile && $fileUrl) {
            $eyeButton = '<span class="text-primary btn-action" title="Lihat File" onclick="window.open(\'' . esc($fileUrl) . '\', \'_blank\')"><i class="bi bi-eye"></i></span>';
        } else {
            $eyeButton = '<span class="text-secondary btn-action" title="Belum ada file"><i class="bi bi-eye"></i></span>';
        }

        // Upload button
        $detKodeAttr = htmlspecialchars($detKode ?? '', ENT_QUOTES, 'UTF-8');
        $uploadInput = '<label class="mb-0 position-relative" style="cursor:pointer;">'
                    . '<input type="file" accept=".jpg,.jpeg,.png,.pdf,.doc,.docx,.xls,.xlsx" '
                    . 'data-detlist="' . $detKodeAttr . '" data-detkode="' . $detKodeAttr . '" data-ln="' . $encLnId . '" '
                    . 'class="d-none lhus-uploader-input" onchange="autoUploadFile(this)" />'
                    . '<span class="text-primary btn-action" title="Unggah / Ubah File LHUS"><i class="bi bi-upload"></i></span>'
                    . '</label>';

        $combinedHtml .= $eyeButton . $uploadInput . '</div>';

        return $combinedHtml;
    }

    /**
     * Format status badge
     */
    private function formatStatusBadge(?int $detStatusLHUS, ?int $lnStatusInt, bool $rowHasFile): string
    {
        if ($detStatusLHUS === 2) {
            return '<div class="text-center"><span class="badge bg-danger">lhus ditolak</span></div>';
        } elseif ($detStatusLHUS === 3 && $rowHasFile) {
            return '<div class="text-center"><span class="badge bg-info">lhus ter-unggah</span></div>';
        } elseif ($detStatusLHUS === 1) {
            return '<div class="text-center"><span class="badge bg-success">lhus diterima</span></div>';
        } elseif ($lnStatusInt === 5) {
            return '<div class="text-center"><span class="badge bg-primary">terkirim</span></div>';
        } elseif ($rowHasFile) {
            return '<div class="text-center"><span class="badge bg-success">ter-unggah</span></div>';
        } else {
            return '<div class="text-center"><span class="badge bg-warning text-dark">belum upload</span></div>';
        }
    }

    /**
     * Process submission dengan transaction
     */
    private function processSubmission($lnKode, int $user_id): object
    {
        try {
            $this->hasilPengujianModel->transBegin();

            // Update files status dari 3 (terunggah) ke 0 (terkirim)
            $this->hasilPengujianModel->updateFilesStatusForUser($lnKode, $user_id, 3, 0);

            // Update status di t_files_lhus
            $this->hasilPengujianModel->updateFilesLhusStatusForUser($lnKode, $user_id, 3, 0);

            // Update terima_layanan_by
            $this->hasilPengujianModel->updateTerimaLayananBy($lnKode, $user_id, $user_id);

            // Check if all files uploaded
            $totalBelumUpload = $this->hasilPengujianModel->countMissingFilesForLayanan($lnKode);

            $parentUpdated = false;
            if ($totalBelumUpload === 0) {
                // Update layanan status ke 5
                $this->hasilPengujianModel->updateLayananStatus($lnKode, 5);
                $parentUpdated = true;
                
                // Update log sampel
                $this->hasilPengujianModel->updateLogSampelVerifikasiHasilUji($lnKode);
            }

            // Commit transaction
            if ($this->hasilPengujianModel->transStatus() === false) {
                $this->hasilPengujianModel->transRollback();
                $dberr = $this->hasilPengujianModel->getError();
                return $this->jsonResponse('error', 
                    'Gagal menyimpan status pada detil/parent (transaksi gagal). DB Error: ' . ($dberr['message'] ?? 'Unknown'),
                    ['db' => $dberr]
                );
            } else {
                $this->hasilPengujianModel->transCommit();
            }

            // Prepare response
            if ($totalBelumUpload > 0) {
                return $this->jsonResponse(true, 
                    'LHUS Anda berhasil dikirim ke manajer. Masih ada ' . $totalBelumUpload . ' layanan lain yang belum terupload.',
                    [
                        'waiting_others' => true,
                        'pending_total' => $totalBelumUpload,
                        'parent_updated' => false
                    ]
                );
            }

            return $this->jsonResponse(true, 
                'Semua LHUS dalam invoice ini sudah lengkap dan terkirim ke manajer teknis!',
                [
                    'waiting_others' => false,
                    'parent_updated' => $parentUpdated
                ]
            );
            
        } catch (\Throwable $e) {
            if ($this->hasilPengujianModel->transStatus() !== false) {
                $this->hasilPengujianModel->transRollback();
            }
            $dberr = $this->hasilPengujianModel->getError();
            return $this->jsonResponse('error', 'Error saat update: ' . $e->getMessage(), ['db' => $dberr]);
        }
    }

    /**
     * Handle single file upload
     */
    private function handleSingleFileUpload(int $detKode, $lnKode, string $filename, int $user_id): object
    {
        // Update status files ke 3 (terunggah, belum dikirim)
        $ok = $this->hasilPengujianModel->updateDetailFilesStatus($detKode, 3);

        if ($ok) {
            // Save file info
            $this->hasilPengujianModel->saveFileLhus($detKode, $lnKode, $filename, $user_id, 3);

            return $this->jsonResponse(true, 
                'File LHUS berhasil diunggah (status: terunggah)',
                [
                    'url' => base_url('uploads/lhus/' . $filename),
                    'detKode' => $detKode
                ]
            );
        } else {
            $this->deleteUploadedFile($filename);
            $dberr = $this->hasilPengujianModel->getError();
            return $this->jsonResponse('error', 
                'Gagal menyimpan ke detail (detKode). DB Error: ' . ($dberr['message'] ?? 'Unknown'),
                ['db' => $dberr]
            );
        }
    }

    /**
     * Handle bulk file upload
     */
    private function handleBulkFileUpload($lnKode, string $filename, int $user_id): object
    {
        // Update status files ke 3 untuk semua detil yang relevan dengan user
        $ok = $this->hasilPengujianModel->updateFilesStatusForUser($lnKode, $user_id, null, 3);

        if ($ok) {
            // Get detail rows
            $detRows = $this->hasilPengujianModel->getDetailRowsForBulkUpload($lnKode, $user_id);

            foreach ($detRows as $dr) {
                $this->hasilPengujianModel->saveFileLhus($dr->kode, $lnKode, $filename, $user_id, 3);
            }

            return $this->jsonResponse(true, 
                'File LHUS berhasil diunggah untuk layanan terkait Anda (status: terunggah)',
                ['url' => base_url('uploads/lhus/' . $filename)]
            );
        } else {
            $this->deleteUploadedFile($filename);
            $dberr = $this->hasilPengujianModel->getError();
            return $this->jsonResponse('error', 
                'Gagal menyimpan ke detil. DB Error: ' . ($dberr['message'] ?? 'Unknown'),
                ['db' => $dberr]
            );
        }
    }

    /**
     * Upload file fisik ke server
     */
    private function doUpload(\CodeIgniter\HTTP\Files\UploadedFile $file): array
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

    /**
     * Delete uploaded file
     */
    private function deleteUploadedFile(string $filename): void
    {
        $savedPath = FCPATH . 'uploads/lhus/' . $filename;
        if (is_file($savedPath)) {
            @unlink($savedPath);
        }
    }

    /**
     * Decrypt ID dari encrypted string
     */
    private function decryptId(string $encId): string
    {
        if (preg_match('/^[0-9a-f]+$/i', $encId)) {
            return $this->encrypter->decrypt(hex2bin($encId));
        } else {
            return $this->encrypter->decrypt($encId);
        }
    }

    /**
     * Format JSON response helper
     */
    private function jsonResponse($res, string $msg, array $extra = []): object
    {
        $response = array_merge([
            'res' => $res,
            'msg' => $msg,
            'xname' => csrf_token(),
            'xhash' => csrf_hash()
        ], $extra);

        return $this->response->setJSON($response);
    }

    /**
     * Format status badge untuk layanan umum
     */
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

    /**
     * Format status untuk penyelia (dengan logic khusus berdasarkan files status)
     */
    private function formatStatusForPenyelia($lnStatus, $lnKode, $userId)
    {
        // PRIORITAS 1: ada detail milik user yang ditolak (files = 2)
        if ($this->hasilPengujianModel->hasRejectedLhus($lnKode, $userId)) {
            return '<span class="badge bg-danger">LHUS ditolak</span>';
        }

        // PRIORITAS 2: Cek apakah ada yang statusnya 3 (terunggah tapi belum dikirim)
        if ($this->hasilPengujianModel->hasUploadedLhus($lnKode, $userId)) {
            return '<span class="badge bg-info">Terunggah (belum dikirim)</span>';
        }

        // PRIORITAS 3: Cek apakah sudah diterima semua (files = 1)
        if ($this->hasilPengujianModel->allUserLhusAccepted($lnKode, $userId)) {
            return '<span class="badge bg-success">LHUS diterima</span>';
        }

        // PRIORITAS 4: Cek apakah sudah terkirim ke manajer (files = 0)
        if ($this->hasilPengujianModel->hasSentLhus($lnKode, $userId)) {
            return '<span class="badge bg-primary">Terkirim ke manajer teknis</span>';
        }

        // Fallback ke mapping lnStatus
        return $this->formatStatus((int)$lnStatus);
    }
}
