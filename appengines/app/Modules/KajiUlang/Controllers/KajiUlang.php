<?php

namespace Modules\KajiUlang\Controllers;

use App\Controllers\BaseController;
use App\Models\MyModel;
use Modules\KajiUlang\Models\KajiUlangModel;

class KajiUlang extends BaseController
{
    private $table = 'simlab_t_layanan';
    private $id = 'lnKode';
    protected $encrypter;
    protected $kajiUlangModel;

    public function __construct()
    {
        $this->encrypter = \Config\Services::encrypter();
        $this->kajiUlangModel = new KajiUlangModel();
        helper('form');
    }

    public function index()
    {
        $session = session();
        $user_id = $session->get('id_user');

        $modelUser = new MyModel('simlab_account_users');
        $data = [
            'title' => 'Data Kaji Ulang',
            'user'  => $modelUser->getDataById('user_id', $user_id),
        ];

        return view('Modules\KajiUlang\Views\v_kajiUlang', $data);
    }

    public function datalist()
    {
        $session = session();
        $user_id = $session->get('id_user');
        $data    = [];

        $lnStatusRaw = (string) ($this->request->getGet('lnStatus') ?? '');
        $statusArr = [];
        if ($lnStatusRaw !== '') {
            if (preg_match_all('/\d+/', $lnStatusRaw, $m) && !empty($m[0])) {
                $statusArr = array_map('intval', $m[0]);
                $statusArr = array_values(array_unique($statusArr));
            }
        }

        // Get layanan codes related to user
        $lnKodeList = $this->kajiUlangModel->getLayananKodesByUserId($user_id);

        if (empty($lnKodeList)) {
            return $this->response->setJSON(['items' => []]);
        }

        // Get layanan list with status filtering
        $list = $this->kajiUlangModel->getLayananListForManager($lnKodeList, $user_id, $statusArr);

        foreach ($list as $row) {
            // HANYA skip status=0 jika TIDAK sedang mem-filter
            if ((int) $row->lnStatus === 0 && empty($statusArr)) {
                continue;
            }

            $id = bin2hex($this->encrypter->encrypt($row->lnKode));
            $response = [];

            $pemesanNama = !empty($row->pemesan_name) ? $row->pemesan_name : '-';
            $tipe = !empty($row->pemesan_identity) ? $row->pemesan_identity : '-';
            $tanggal = !empty($row->lnTgl) ? date('d-m-Y H:i', strtotime($row->lnTgl)) : '-';

            // Badge Uji Ulang (clickable)
            $badge = '';
            if ((int) ($row->jumlah_kaji_ulang ?? 0) > 0) {
                $badge = '<span class="badge bg-danger text-white ms-1 badge-uji-ulang" style="cursor:pointer;" data-id="' . $id . '" title="Klik untuk melihat catatan kaji ulang">Uji Ulang</span>';
            }

            $combined = '
                <div style="line-height:1.3;">
                    <span style="font-size:1rem; font-weight:600;">' . esc($pemesanNama) . '</span><br>
                    <span style="font-size:0.9rem; color:#555;">' . esc($tanggal) . ' | ' . esc($tipe) . '</span>' . $badge . '
                </div>';

            $response[] = $combined;

            // Tampilkan status perspektif manajer
            $response[] = $this->formatStatusForManager($row->lnStatus, $row->lnKode, $user_id);

            $response[] = '<a href="javascript:void(0)" onclick="loadDetail(\'' . $id . '\', \'' . $row->lnKode . '\')" 
                            class="btn btn-sm btn-info">
                            <i class="bi bi-gear"></i> Review Layanan
                        </a>';

            $data[] = $response;
        }

        $output = ["items" => $data];
        return $this->response->setJSON($output);
    }

    public function detailList($id = null)
    {
        if (!$id) {
            return $this->response->setJSON(['items' => []]);
        }

        $session = session();
        $user_id = $session->get('id_user');

        try {
            $kode = $this->encrypter->decrypt(hex2bin($id));
        } catch (\Exception $e) {
            return $this->response->setJSON(['items' => []]);
        }

        // Check user authorization for this layanan
        if (!$this->kajiUlangModel->isUserAuthorizedForLayanan($kode, $user_id)) {
            return $this->response->setJSON(['items' => []]);
        }

        // Encrypted ln dipakai di tombol & save
        $encLnId = bin2hex($this->encrypter->encrypt($kode));

        // Get detail list for review
        $rows = $this->kajiUlangModel->getDetailListForReview($kode, $user_id);

        $data = [];
        $no = 1;

        foreach ($rows as $row) {
            $response = [];
            $response[] = (string) $no++;
            $response[] = $row->nama_layanan ?? '-';
            $response[] = isset($row->jumlah) ? (string) $row->jumlah : '0';

            $response[] = isset($row->metode_nama) && !empty($row->metode_nama) ? esc($row->metode_nama) : '-';

            // Status per record
            $status = isset($row->status_layanan) ? (int)$row->status_layanan : null;
            if ($status === 2) {
                $statusHtml = '<span class="badge bg-danger">Ditolak</span>';
            } elseif ($status === 1) {
                $statusHtml = '<span class="badge bg-success">Diterima</span>';
            } elseif ($status === 0) {
                $statusHtml = '<span class="badge bg-secondary">Pending</span>';
            } else {
                $statusHtml = '<span class="badge bg-secondary">Belum Diproses</span>';
            }
            $response[] = $statusHtml;

            // Aksi approve/reject
            $detailKode = (int)$row->kode; // Primary key dari t_layanan_detil
            $encLnForBtn = $encLnId;
            $komentarVal = $row->catatan_manajer !== null ? esc($row->catatan_manajer) : '';

            $textarea = '<textarea class="form-control komentar-input" data-detail="' . $detailKode . '" rows="2" placeholder="Keterangan/manajer..."'
                . ' style="max-width:240px; min-width:160px; max-height:120px; min-height:48px; overflow-y:auto; overflow-x:hidden; resize:vertical; white-space:pre-wrap; word-break:break-word;">'
                . $komentarVal .
                '</textarea>';

            $response[] = $textarea;

            $aksiHtml = '<div class="d-flex justify-content-center gap-2 align-items-center">';
            $aksiHtml .= '<span class="text-success btn-action btn-accept-manager" title="Setujui" data-ln="' . $encLnForBtn . '" data-detail="' . $detailKode . '"><i class="bi bi-check-circle"></i></span> ';
            $aksiHtml .= '<span class="text-warning btn-action btn-reject-manager" title="Tolak" data-ln="' . $encLnForBtn . '" data-detail="' . $detailKode . '"><i class="bi bi-x-circle"></i></span>';
            $aksiHtml .= '</div>';

            $response[] = $aksiHtml;

            $data[] = $response;
        }

        return $this->response->setJSON([
            'res' => 'ok',
            'items' => $data,
            'encLn' => $encLnId,
            'xname' => csrf_token(),
            'xhash' => csrf_hash()
        ]);
    }

    public function saveKomentar()
    {
        $raw = file_get_contents('php://input');
        $input = json_decode($raw, true);

        if (!$input || !isset($input['lnId']) || !isset($input['items']) || !is_array($input['items'])) {
            return $this->response->setJSON([
                'res' => false,
                'msg' => 'Payload tidak lengkap',
                'xname' => csrf_token(),
                'xhash' => csrf_hash()
            ]);
        }

        try {
            $lnKode = $this->encrypter->decrypt(hex2bin($input['lnId']));
            $lnKode = (int) $lnKode;
        } catch (\Exception $e) {
            return $this->response->setJSON([
                'res' => false,
                'msg' => 'LN ID tidak valid',
                'xname' => csrf_token(),
                'xhash' => csrf_hash()
            ]);
        }

        $session = session();
        $user_id = $session->get('id_user');

        // Check authorization
        if (!$this->kajiUlangModel->isUserAuthorizedForLayanan($lnKode, $user_id)) {
            return $this->response->setJSON([
                'res' => false,
                'msg' => 'Anda tidak berwenang mengubah komentar pada layanan ini',
                'xname' => csrf_token(),
                'xhash' => csrf_hash()
            ]);
        }

        // Update komentar batch
        $ok = $this->kajiUlangModel->updateKomentarBatch($lnKode, $input['items']);

        return $this->response->setJSON([
            'res' => $ok,
            'msg' => $ok ? 'Komentar berhasil disimpan' : 'Gagal menyimpan komentar',
            'xname' => csrf_token(),
            'xhash' => csrf_hash()
        ]);
    }

    public function approveDetail()
    {
        $lnEnc = $this->request->getPost('ln');
        $detailRaw = $this->request->getPost('detail');

        if (empty($lnEnc) || $detailRaw === null) {
            return $this->response->setJSON([
                'res' => false,
                'affected' => 0,
                'msg' => 'Parameter tidak lengkap',
                'xname' => csrf_token(),
                'xhash' => csrf_hash()
            ]);
        }

        $detailKode = (int)$detailRaw;

        try {
            $lnId = $this->encrypter->decrypt(hex2bin($lnEnc));
        } catch (\Exception $e) {
            return $this->response->setJSON([
                'res' => false,
                'affected' => 0,
                'msg' => 'ID layanan tidak valid',
                'xname' => csrf_token(),
                'xhash' => csrf_hash()
            ]);
        }

        $session = session();
        $managerId = (int) ($session->get('id_user') ?? 0);

        if ($managerId <= 0) {
            return $this->response->setJSON([
                'res' => false,
                'affected' => 0,
                'msg' => 'User login tidak ditemukan.',
                'xname' => csrf_token(),
                'xhash' => csrf_hash()
            ]);
        }

        // Validate manager user
        if (!$this->kajiUlangModel->isValidUser($managerId)) {
            return $this->response->setJSON([
                'res' => false,
                'affected' => 0,
                'msg' => 'User login tidak valid di simlab_account.',
                'xname' => csrf_token(),
                'xhash' => csrf_hash()
            ]);
        }

        // Check authorization for this detail record
        if (!$this->kajiUlangModel->isUserAuthorizedForDetail($detailKode, $managerId)) {
            return $this->response->setJSON([
                'res' => false,
                'affected' => 0,
                'msg' => 'Anda tidak berwenang memproses detail ini.',
                'xname' => csrf_token(),
                'xhash' => csrf_hash()
            ]);
        }

        // Count total detail rows (should be 1 for this specific record)
        $total = $this->kajiUlangModel->countDetailByKode($detailKode);

        if ($total === 0) {
            return $this->response->setJSON([
                'res' => false,
                'affected' => 0,
                'msg' => 'Detail record tidak ditemukan',
                'xname' => csrf_token(),
                'xhash' => csrf_hash()
            ]);
        }

        // Check if already approved
        $already = $this->kajiUlangModel->countDetailByKode($detailKode, 1);

        if ($already === $total) {
            // Already approved, check if parent needs update
            $pendingRemaining = $this->kajiUlangModel->countPendingForLayanan($lnId);

            $parentUpdated = false;
            if ($pendingRemaining === 0) {
                $model = new MyModel($this->table);
                $resParent = $model->updateData(['lnStatus' => 3], $this->id, $lnId);
                $parentUpdated = ($resParent === true || $resParent === 1);
            }

            return $this->response->setJSON([
                'res' => true,
                'affected' => 0,
                'msg' => 'Sudah disetujui',
                'parent_updated' => $parentUpdated,
                'xname' => csrf_token(),
                'xhash' => csrf_hash()
            ]);
        }

        // Start transaction
        $this->kajiUlangModel->transStart();

        // Approve detail
        $affected = $this->kajiUlangModel->approveLayananDetailByKode($detailKode, $managerId);
        $parentUpdated = false;

        if ($affected > 0) {
            // Check remaining pending
            $pendingRemaining = $this->kajiUlangModel->countPendingForLayanan($lnId);

            if ($pendingRemaining === 0) {
                // Update parent status to 3
                $model = new MyModel($this->table);
                $resParent = $model->updateData(['lnStatus' => 3], $this->id, $lnId);
                $parentUpdated = ($resParent === true || $resParent === 1);
            }
        }

        $this->kajiUlangModel->transComplete();
        $transOk = $this->kajiUlangModel->transStatus();

        return $this->response->setJSON([
            'res' => $transOk && $affected > 0,
            'affected' => $affected,
            'msg' => $affected > 0 ? 'Berhasil disetujui' : 'No rows updated',
            'parent_updated' => $parentUpdated,
            'xname' => csrf_token(),
            'xhash' => csrf_hash()
        ]);
    }


    public function rejectDetail()
    {
        $lnEnc = $this->request->getPost('ln');
        $detailRaw = $this->request->getPost('detail');

        if (empty($lnEnc) || $detailRaw === null) {
            return $this->response->setJSON([
                'res' => false,
                'affected' => 0,
                'msg' => 'Parameter tidak lengkap',
                'xname' => csrf_token(),
                'xhash' => csrf_hash()
            ]);
        }

        $detailKode = (int)$detailRaw;

        try {
            $lnId = $this->encrypter->decrypt(hex2bin($lnEnc));
        } catch (\Exception $e) {
            return $this->response->setJSON([
                'res' => false,
                'affected' => 0,
                'msg' => 'ID layanan tidak valid',
                'xname' => csrf_token(),
                'xhash' => csrf_hash()
            ]);
        }

        $session = session();
        $managerId = (int) ($session->get('id_user') ?? 0);

        if ($managerId <= 0) {
            return $this->response->setJSON([
                'res' => false,
                'affected' => 0,
                'msg' => 'User login tidak ditemukan.',
                'xname' => csrf_token(),
                'xhash' => csrf_hash()
            ]);
        }

        // Validate manager user
        if (!$this->kajiUlangModel->isValidUser($managerId)) {
            return $this->response->setJSON([
                'res' => false,
                'affected' => 0,
                'msg' => 'User login tidak valid di simlab_account.',
                'xname' => csrf_token(),
                'xhash' => csrf_hash()
            ]);
        }

        // Check authorization for this detail record
        if (!$this->kajiUlangModel->isUserAuthorizedForDetail($detailKode, $managerId)) {
            return $this->response->setJSON([
                'res' => false,
                'affected' => 0,
                'msg' => 'Anda tidak berwenang memproses detail ini.',
                'xname' => csrf_token(),
                'xhash' => csrf_hash()
            ]);
        }

        // Count total detail rows (should be 1 for this specific record)
        $total = $this->kajiUlangModel->countDetailByKode($detailKode);

        if ($total === 0) {
            return $this->response->setJSON([
                'res' => false,
                'affected' => 0,
                'msg' => 'Detail record tidak ditemukan',
                'xname' => csrf_token(),
                'xhash' => csrf_hash()
            ]);
        }

        // Check if already rejected
        $already = $this->kajiUlangModel->countDetailByKode($detailKode, 2);

        if ($already === $total) {
            // Already rejected, check if parent needs update
            $pendingRemaining = $this->kajiUlangModel->countPendingForLayanan($lnId);

            $parentUpdated = false;
            if ($pendingRemaining === 0) {
                $model = new MyModel($this->table);
                $resParent = $model->updateData(['lnStatus' => 3], $this->id, $lnId);
                $parentUpdated = ($resParent === true || $resParent === 1);
            }

            return $this->response->setJSON([
                'res' => true,
                'affected' => 0,
                'msg' => 'Sudah ditolak',
                'parent_updated' => $parentUpdated,
                'xname' => csrf_token(),
                'xhash' => csrf_hash()
            ]);
        }

        // Start transaction
        $this->kajiUlangModel->transStart();

        // Reject detail
        $affected = $this->kajiUlangModel->rejectLayananDetailByKode($detailKode, $managerId);
        $parentUpdated = false;

        if ($affected > 0) {
            // Check remaining pending
            $pendingRemaining = $this->kajiUlangModel->countPendingForLayanan($lnId);

            if ($pendingRemaining === 0) {
                // Update parent status to 3
                $model = new MyModel($this->table);
                $resParent = $model->updateData(['lnStatus' => 3], $this->id, $lnId);
                $parentUpdated = ($resParent === true || $resParent === 1);
            }
        }

        $this->kajiUlangModel->transComplete();
        $transOk = $this->kajiUlangModel->transStatus();

        return $this->response->setJSON([
            'res' => $transOk && $affected > 0,
            'affected' => $affected,
            'msg' => $affected > 0 ? 'OK' : 'No rows updated',
            'parent_updated' => $parentUpdated,
            'xname' => csrf_token(),
            'xhash' => csrf_hash()
        ]);
    }

    public function kirim()
    {
        $lnEnc = $this->request->getPost('ln');

        if (empty($lnEnc)) {
            return $this->response->setJSON([
                'res' => false,
                'msg' => 'Parameter ln tidak ditemukan',
                'xname' => csrf_token(),
                'xhash' => csrf_hash()
            ]);
        }

        try {
            $lnId = $this->encrypter->decrypt(hex2bin($lnEnc));
        } catch (\Exception $e) {
            return $this->response->setJSON([
                'res' => false,
                'msg' => 'ID layanan tidak valid',
                'xname' => csrf_token(),
                'xhash' => csrf_hash()
            ]);
        }

        $session = session();
        $user_id = $session->get('id_user');

        // Check authorization
        if (!$this->kajiUlangModel->isUserAuthorizedForLayanan($lnId, $user_id)) {
            return $this->response->setJSON([
                'res' => false,
                'msg' => 'Anda tidak memiliki otorisasi untuk mengirim layanan ini',
                'xname' => csrf_token(),
                'xhash' => csrf_hash()
            ]);
        }

        // Check pending for this manager
        $pendingManagerCount = $this->kajiUlangModel->countPendingForManager($lnId, $user_id);

        if ($pendingManagerCount > 0) {
            return $this->response->setJSON([
                'res' => false,
                'msg' => 'Layanan yang belum anda proses : ' . $pendingManagerCount,
                'pending' => $pendingManagerCount,
                'xname' => csrf_token(),
                'xhash' => csrf_hash()
            ]);
        }

        // Check total pending for layanan
        $pendingTotal = $this->kajiUlangModel->countPendingForLayanan($lnId);

        if ($pendingTotal > 0) {
            return $this->response->setJSON([
                'res' => true,
                'msg' => 'Berhasil dikirim — sedang menunggu verifikasi pengelola lain : ' . $pendingTotal . ' layanan ',
                'waiting_others' => true,
                'pending_total' => $pendingTotal,
                'parent_updated' => false,
                'xname' => csrf_token(),
                'xhash' => csrf_hash()
            ]);
        }

        // Update parent status in transaction
        $this->kajiUlangModel->transStart();

        $pendingTotalCheck = $this->kajiUlangModel->countPendingForLayanan($lnId);

        if ($pendingTotalCheck > 0) {
            $this->kajiUlangModel->transComplete();
            return $this->response->setJSON([
                'res' => true,
                'msg' => 'Berhasil dikirim — namun ditemukan item pending saat verifikasi akhir ' . $pendingTotalCheck,
                'waiting_others' => true,
                'pending_total' => $pendingTotalCheck,
                'parent_updated' => false,
                'xname' => csrf_token(),
                'xhash' => csrf_hash()
            ]);
        }

        $model = new MyModel($this->table);
        $resParent = $model->updateData(['lnStatus' => 3], $this->id, $lnId);
        $parentUpdated = ($resParent === true || $resParent === 1);

        $this->kajiUlangModel->transComplete();

        if ($this->kajiUlangModel->transStatus() === false) {
            return $this->response->setJSON([
                'res' => false,
                'msg' => 'Gagal memperbarui status layanan',
                'xname' => csrf_token(),
                'xhash' => csrf_hash()
            ]);
        }

        return $this->response->setJSON([
            'res' => $parentUpdated,
            'msg' => $parentUpdated ? 'Layanan berhasil dikirim ke admin' : 'Berhasil dikirim ',
            'waiting_others' => false,
            'pending_total' => 0,
            'parent_updated' => $parentUpdated,
            'xname' => csrf_token(),
            'xhash' => csrf_hash()
        ]);
    }

    private function formatStatus($status)
    {
        switch ($status) {
            case 0:
                return '<span class="badge bg-secondary">Draft</span>';
            case 1:
                return '<span class="badge bg-warning">Layanan belum direview</span>';
            case 2:
                return '<span class="badge bg-danger">Ditolak</span>';
            case 3:
                return '<span class="badge bg-info">Layanan terkirim ke admin</span>';
            case 4:
                return '<span class="badge bg-primary">Pengujian sedang dilakukan</span>';
            case 5:
                return '<span class="badge bg-primary">LHUS sedang diproses</span>';
            case 6:
                return '<span class="badge bg-success">LHUS telah disetujui</span>';
            case 7:
                return '<span class="badge bg-primary">LHU sedang diproses</span>';
            case 8:
                return '<span class="badge bg-success">LHU telah disetujui</span>';
            case 9:
                return '<span class="badge bg-dark">Pengujian telah selesai</span>';
            default:
                return '<span class="badge bg-dark">Unknown</span>';
        }
    }

    private function formatStatusForManager($lnStatus, $lnKode, $userId)
    {
        // Check pending via r_tim
        $pendingCount = $this->kajiUlangModel->countPendingForManager($lnKode, $userId);

        // If manager has no pending, show "Layanan terkirim ke admin"
        if ($pendingCount === 0) {
            return '<span class="badge bg-info">Layanan terkirim ke admin</span>';
        }

        // Otherwise, show parent status
        return $this->formatStatus((int)$lnStatus);
    }

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

    /**
     * Get catatan kaji ulang for modal
     */
    public function getCatatanKajiUlang($encId = null)
    {
        if (!$encId) {
            return $this->response->setJSON([
                'success' => false,
                'message' => 'ID tidak ditemukan'
            ]);
        }

        try {
            $lnKode = $this->encrypter->decrypt(hex2bin($encId));
        } catch (\Exception $e) {
            return $this->response->setJSON([
                'success' => false,
                'message' => 'ID tidak valid'
            ]);
        }

        $model = new MyModel($this->table);
        $row = $model->getDataById($this->id, $lnKode);

        if (!$row) {
            return $this->response->setJSON([
                'success' => false,
                'message' => 'Data tidak ditemukan'
            ]);
        }

        return $this->response->setJSON([
            'success' => true,
            'data' => [
                'catatan_kaji_ulang' => $row->catatan_kaji_ulang ?? '-',
                'jumlah_kaji_ulang' => (int) ($row->jumlah_kaji_ulang ?? 0)
            ]
        ]);
    }
}
