<?php

namespace Modules\TinjauLHUS\Controllers;

use App\Controllers\BaseController;
use App\Models\MyModel;
use Modules\TinjauLHUS\Models\TinjauLhusModel;
use Modules\Notifications\Controllers\LhusNotificationController;

class TinjauLHUS extends BaseController
{
  private $table = 't_layanan';
  private $id = 'kode_layanan';
  private $tinjauLhusModel;
  protected $encrypter;

  public function __construct()
  {
    $this->encrypter = \Config\Services::encrypter();
    $this->tinjauLhusModel = new TinjauLhusModel();
    helper('form');
  }

  public function index()
  {
    $session = session();
    $user_id = $session->get('id_user');

    $modelUser = new MyModel('account_users');

    $data = [
      'title' => 'Tinjau LHUS',
      'user' => $modelUser->getDataById('user_id', $user_id),
    ];

    return view('Modules\TinjauLHUS\Views\v_tinjauLhus', $data);
  }

  // --------------------- dataList() ---------------------
  public function dataList()
  {
    $session = session();
    $user_id = (int) $session->get('id_user');

    // [ADDED] Parse status_layanan filter (?status_layanan=tolak,5,6,diproses)
    $lnStatusParam = (string) ($this->request->getGet('status_layanan') ?? '');
    $wantReject = false;          // token: tolak/reject/ditolak
    $wantReprocess = false;          // token: diproses/reprocess
    $statusNums = [];             // angka: 5/6/...
    if ($lnStatusParam !== '') {
      foreach (preg_split('/[,\s]+/', $lnStatusParam, -1, PREG_SPLIT_NO_EMPTY) as $p) {
        $tp = strtolower(trim($p));
        if (in_array($tp, ['tolak', 'reject', 'ditolak'], true)) {
          $wantReject = true;
          continue;
        }
        if (in_array($tp, ['diproses', 'reprocess', 'proseskembali'], true)) {
          $wantReprocess = true;
          continue;
        }
        if ($tp !== '' && is_numeric($tp)) {
          $statusNums[] = (int) $tp;
        }
      }
      $statusNums = array_values(array_unique($statusNums));
    }
    // [ADDED] end

    $data = [];

    // 1) Get active layanan codes for user
    $lnKodeList = $this->tinjauLhusModel->getActiveLayananCodesByUser($user_id);

    if (empty($lnKodeList)) {
      return $this->response->setJSON(['items' => []]);
    }

    // 2) Get parent layanan list
    $list = $this->tinjauLhusModel->getLayananListByKodes($lnKodeList);

    // 3) Get global and user status summaries
    $statusSummary = $this->tinjauLhusModel->getGlobalStatusSummary($lnKodeList);
    $userSummary = $this->tinjauLhusModel->getUserStatusSummary($lnKodeList, $user_id);

    foreach ($list as $row) {
      $kode_layanan = (int) $row->kode_layanan;

      // Hitung derivedStatus (tampil) — konsisten dengan view
      $derivedStatus = (int) $row->status_layanan;
      if (isset($userSummary[$kode_layanan]) && $userSummary[$kode_layanan]['total'] > 0) {
        $su = $userSummary[$kode_layanan];
        if ($su['cnt0'] === 0) {
          if ($su['cnt1'] === $su['total'])
            $derivedStatus = 6; // semua accept
          elseif ($su['cnt2'] > 0)
            $derivedStatus = 2; // ada tolak
          else
            $derivedStatus = 5; // selesai subset user tapi global belum tentu
        } else
          $derivedStatus = 5; // masih pending
      } elseif (isset($statusSummary[$kode_layanan])) {
        $s = $statusSummary[$kode_layanan];
        if ($s['cnt0'] > 0)
          $derivedStatus = 5;
        elseif ($s['total'] > 0 && $s['cnt1'] === $s['total'])
          $derivedStatus = 6;
        elseif ($s['cnt2'] > 0)
          $derivedStatus = 2;
        else
          $derivedStatus = 5;
      }

      // [ADDED] Terapkan FILTER (?status_layanan=...)
      if ($wantReject || $wantReprocess || !empty($statusNums)) {
        $hasRejectForUser = (isset($userSummary[$kode_layanan]) && $userSummary[$kode_layanan]['cnt2'] > 0);
        $match = false;

        if ($wantReject && $hasRejectForUser)
          $match = true;
        if ($wantReprocess && (int) $derivedStatus === 2)
          $match = true;
        if (!$match && !empty($statusNums) && in_array((int) $derivedStatus, $statusNums, true))
          $match = true;

        if (!$match)
          continue; // tidak match filter → skip
      }
      // [ADDED] end

      // ===== JSON row (3 kolom) — cocok dengan header Pemesan, Status, LHUS =====
      $pemesanNama = $row->pemesan_name ?: ($row->lnOrangNama ?? '-');
      $tipe = $row->pemesan_identity ?: ($row->lnOrangJenis ?? ($row->lnOrangTipe ?? '-'));
      $tanggal = !empty($row->tanggal_checkout) ? date('d-m-Y H:i', strtotime($row->tanggal_checkout)) : '-';

      // Badge Uji Ulang (clickable)
      $badge = '';
      if ((int) ($row->jumlah_kaji_ulang ?? 0) > 0) {
        $encId = bin2hex($this->encrypter->encrypt($row->kode_layanan));
        $badge = '<span class="badge bg-danger text-white ms-1 badge-uji-ulang" style="cursor:pointer;" data-id="' . $encId . '" title="Klik untuk melihat catatan kaji ulang">Uji Ulang</span>';
      }

      $combined = '
                <div style="line-height:1.3;">
                    <span style="font-size:1rem; font-weight:600;">' . esc($pemesanNama) . '</span><br>
                    <span style="font-size:0.9rem; color:#555;">' . esc($tanggal) . ' | ' . esc($tipe) . '</span>' . $badge . '
                </div>';

      $actionBtn = '<a href="javascript:void(0)" onclick="loadDetail(\''
        . bin2hex($this->encrypter->encrypt($row->kode_layanan))
        . '\')" class="btn btn-sm btn-info"><i class="bi bi-gear"></i> Tinjau LHUS</a>';

      $data[] = [
        $combined,
        $this->formatStatus((int) $derivedStatus),
        $actionBtn,
      ];
    }

    return $this->response->setJSON(["items" => $data]);
  }

  // --------------------- detailList() ---------------------
  public function detailList($id = null)
  {
    if (empty($id))
      return $this->response->setJSON(['items' => []]);

    // tolerant decrypt (hex/raw)
    try {
      $kode_layanan = $this->encrypter->decrypt(hex2bin($id));
    } catch (\Throwable $e) {
      try {
        $kode_layanan = $this->encrypter->decrypt($id);
      } catch (\Throwable $e2) {
        return $this->response->setJSON(['items' => [], 'error' => 'Invalid ID']);
      }
    }

    $session = session();
    $user_id = (int) $session->get('id_user');

    try {
      $list = $this->tinjauLhusModel->getDetailListByLayananAndUser($kode_layanan, $user_id);
    } catch (\Throwable $e) {
      return $this->response->setJSON(['items' => [], 'error' => $e->getMessage()]);
    }

    $data = [];
    $no = 1;

    foreach ($list as $row) {
      $layanan = $row->nama_layanan ?? '-';
      $jumlah = (int) ($row->jumlah ?? 0);

      // Format metode
      $metodeHtml = $this->formatKeteranganHtml($row->metode_nama ?? '');

      // Deteksi file LHUS dari t_files_lhus
      $hasFile = false;
      $fileUrl = null;
      if (!empty($row->file_lhus)) {
        $raw = trim((string) $row->file_lhus);
        if (preg_match('/^https?:\/\//i', $raw)) {
          $hasFile = true;
          $fileUrl = $raw;
        } else {
          $possiblePath = FCPATH . 'uploads/lhus/' . ltrim($raw, '/');
          if (is_file($possiblePath)) {
            $hasFile = true;
            $fileUrl = base_url('uploads/lhus/' . ltrim($raw, '/'));
          }
        }
      }

      $lhusHtml = $hasFile && $fileUrl
        ? '<button type="button" class="btn btn-sm btn-outline-primary" onclick="window.open(\'' . esc($fileUrl) . '\', \'_blank\')"><i class="bi bi-eye"></i> Lihat</button>'
        : '<span class="text-muted">-</span>';

      $detKode = (int) ($row->kode ?? 0);
      // Status LHUS: 0=terkirim (pending review), 1=diterima, 2=ditolak, 3=terunggah (belum kirim)
      $statusLhus = isset($row->files) ? (int) $row->files : 0;
      $ketLhusVal = $row->ket_lhus ?? '';

      $textareaLhus =
        '<textarea id="detketlhus_' . $detKode . '" class="form-control detketlhus-input" ' .
        'data-det="' . $detKode . '" data-statuslhus="' . $statusLhus . '" rows="2" placeholder="Keterangan LHUS..." ' .
        'style="max-width:320px; min-width:220px; max-height:140px; resize:vertical; overflow:auto;">' .
        htmlspecialchars($ketLhusVal, ENT_QUOTES, 'UTF-8') .
        '</textarea>';

      // Status badge: 0/3=pending, 1=diterima, 2=ditolak
      if ($statusLhus === 1)
        $statusBadge = '<span class="badge bg-success">Diterima</span>';
      elseif ($statusLhus === 2)
        $statusBadge = '<span class="badge bg-danger">Ditolak</span>';
      elseif ($statusLhus === 3)
        $statusBadge = '<span class="badge bg-info">Terunggah (Belum Kirim)</span>';
      elseif ($statusLhus === 0)
        $statusBadge = '<span class="badge bg-warning">Belum diverifikasi</span>';
      else
        $statusBadge = '<span class="badge bg-secondary">Belum Diproses</span>';

      $parentStatus = (int) ($row->layanan_status ?? 0);
      $lockActions = ($parentStatus === 9);

      $canAccept = ($statusLhus !== 1) && !$lockActions;
      $canReject = ($statusLhus !== 2) && !$lockActions;

      $acceptTitle = $lockActions ? 'Status layanan = 9 (Pengujian Selesai), aksi dikunci' : 'Terima LHUS';
      $rejectTitle = $lockActions ? 'Status layanan = 9 (Pengujian Selesai), aksi dikunci' : 'Tolak LHUS';

      $aksiHtml = '<div class="d-flex justify-content-center gap-2 align-items-center">';
      $aksiHtml .= '<span class="text-success btn-action btn-accept-lhus" title="' . $acceptTitle . '" data-det="' . $detKode . '"' . ($canAccept ? '' : ' style="opacity:.5;pointer-events:none;"') . '><i class="bi bi-check-circle"></i></span>';
      $aksiHtml .= '<span class="text-warning btn-action btn-reject-lhus" title="' . $rejectTitle . '"  data-det="' . $detKode . '"' . ($canReject ? '' : ' style="opacity:.5;pointer-events:none;"') . '><i class="bi bi-x-circle"></i></span>';
      $aksiHtml .= '</div>';

      // ===== JSON row (8 kolom) — match header modal =====
      $data[] = [
        $no++,
        $layanan,
        $metodeHtml,
        $jumlah,
        $statusBadge,
        $lhusHtml,
        $textareaLhus,
        $aksiHtml
      ];
    }

    return $this->response->setJSON([
      'items' => $data,
      'encLn' => bin2hex($this->encrypter->encrypt($kode_layanan)),
      'kode_layanan' => $kode_layanan
    ]);
  }

  /**
   * Batch submit review LHUS (terima/tolak + keterangan).
   * Menerima JSON: { encLn: "...", items: [{detKode, aksi, ket}, ...] }
   */
  public function submitReview()
  {
    $raw = file_get_contents('php://input');
    $input = json_decode($raw, true);

    if (!$input || !isset($input['items']) || !is_array($input['items']) || empty($input['items'])) {
      return $this->response->setJSON([
        'res' => false,
        'msg' => 'Tidak ada data yang diproses',
        'xname' => csrf_token(),
        'xhash' => csrf_hash()
      ]);
    }

    $session = session();
    $accUserId = (int) ($session->get('id_user') ?? 0);
    if ($accUserId <= 0) {
      return $this->response->setJSON([
        'res' => false,
        'msg' => 'User tidak terautentikasi',
        'xname' => csrf_token(),
        'xhash' => csrf_hash()
      ]);
    }

    // Decrypt encLn → kode_layanan
    $encLn = $input['encLn'] ?? '';
    try {
      $kode_layanan = (int) $this->encrypter->decrypt(hex2bin($encLn));
    } catch (\Throwable $e) {
      try {
        $kode_layanan = (int) $this->encrypter->decrypt($encLn);
      } catch (\Throwable $e2) {
        return $this->response->setJSON([
          'res' => false,
          'msg' => 'ID tidak valid',
          'xname' => csrf_token(),
          'xhash' => csrf_hash()
        ]);
      }
    }

    try {
      $result = $this->tinjauLhusModel->processBatchReview($input['items'], $accUserId, $kode_layanan);

      if (!$result['success']) {
        return $this->response->setJSON([
          'res' => false,
          'msg' => $result['error'] ?? 'Gagal menyimpan review',
          'xname' => csrf_token(),
          'xhash' => csrf_hash()
        ]);
      }

      // Kirim notifikasi email ke Penyelia bahwa review LHUS selesai
      try {
        $lhusNotif = new LhusNotificationController();
        $lhusNotif->sendLhusReviewCompleteNotification($kode_layanan, $input['items']);
      } catch (\Throwable $e) {
        log_message('error', 'TinjauLHUS::submitReview - Notifikasi review LHUS gagal: ' . $e->getMessage());
      }

      $msg = 'Review LHUS berhasil disimpan.';
      if (!empty($result['allAccepted'])) {
        $msg = 'Semua LHUS telah diterima. Status layanan diperbarui.';
      }

      return $this->response->setJSON([
        'res' => true,
        'msg' => $msg,
        'xname' => csrf_token(),
        'xhash' => csrf_hash()
      ]);
    } catch (\Throwable $e) {
      return $this->response->setJSON([
        'res' => false,
        'msg' => 'Error: ' . $e->getMessage(),
        'xname' => csrf_token(),
        'xhash' => csrf_hash()
      ]);
    }
  }

  private function formatStatus($status)
  {
    switch ($status) {
      case 5:
        return '<span class="badge bg-warning">LHUS belum ditinjau</span>';
      case 6:
        return '<span class="badge bg-success">LHUS Disetujui</span>';
      case 7:
        return '<span class="badge bg-primary">Memproses LHU</span>';
      case 8:
        return '<span class="badge bg-success">LHU Disetujui</span>';
      case 9:
        return '<span class="badge bg-dark">Pengujian Selesai</span>';
      case 2:
        return '<span class="badge bg-info">LHUS diproses kembali</span>';
      default:
        return '<span class="badge bg-secondary">Unknown</span>';
    }
  }

  public function getSampleIdentity($kode_layanan = null)
  {
    if (!$kode_layanan) {
      return $this->response->setJSON([
        'success' => false,
        'message' => 'Kode layanan tidak ditemukan'
      ]);
    }

    $session = session();
    $user_id = (int) ($session->get('id_user') ?? 0);

    // Check user access via model
    $hasAccess = $this->tinjauLhusModel->checkUserAccessToLayanan($kode_layanan, $user_id);

    if (!$hasAccess) {
      return $this->response->setJSON([
        'success' => false,
        'message' => 'Anda tidak berwenang melihat data ini'
      ]);
    }

    try {
      $sampleData = $this->tinjauLhusModel->getSampleIdentityByLayanan($kode_layanan);

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
    } catch (\Exception $e) {
      log_message('error', 'Error fetching sample identity: ' . $e->getMessage());
      return $this->response->setJSON([
        'success' => false,
        'message' => 'Terjadi kesalahan saat memuat data identitas sampel'
      ]);
    }
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
      $kode_layanan = $this->encrypter->decrypt(hex2bin($encId));
    } catch (\Exception $e) {
      return $this->response->setJSON([
        'success' => false,
        'message' => 'ID tidak valid'
      ]);
    }

    $model = new MyModel($this->table);
    $row = $model->getDataById($this->id, $kode_layanan);

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

  /**
   * Format metode HTML
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
}
