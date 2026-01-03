<?php

namespace Modules\TinjauLHUS\Models;

use CodeIgniter\Model;

class TinjauLhusModel extends Model
{
  protected $table = 't_layanan';
  protected $primaryKey = 'kode_layanan';
  protected $allowedFields = ['status_layanan', 'tanggal_checkout', 'user_id', 'lnOrangNama', 'lnOrangJenis', 'lnOrangTipe'];

  /**
   * Get active layanan codes (kode_layanan) yang relevan dengan user
   * 
   * @param int $userId
   * @return array Array of kode_layanan integers
   */
  public function getActiveLayananCodesByUser($userId)
  {
    $db = $this->db;

    $detRows = $db->table('t_layanan_detil as d')
      ->distinct()
      ->select('d.kode_layanan')
      ->join('r_tim rt', 'rt.uji_kode = d.uji_kode', 'inner')
      ->where('rt.user_id', $userId)
      ->where('d.status_layanan', 1)
      ->get()
      ->getResult();

    $lnKodeList = [];
    foreach ($detRows as $r) {
      $val = is_object($r) ? $r->kode_layanan : $r['kode_layanan'];
      if ($val) {
        $lnKodeList[] = (int) $val;
      }
    }

    return array_values(array_unique(array_filter($lnKodeList)));
  }

  /**
   * Get parent layanan list dengan join ke account_users
   * 
   * @param array $lnKodeList
   * @return array
   */
  public function getLayananListByKodes($lnKodeList)
  {
    if (empty($lnKodeList)) {
      return [];
    }

    $db = $this->db;

    return $db->table('t_layanan as l')
      ->select('l.*, u.user_name as pemesan_name, u.user_email as pemesan_email, u.user_identity as pemesan_identity')
      ->join('simlab_account_users as u', 'u.user_id = l.user_id', 'left')
      ->whereIn('l.kode_layanan', $lnKodeList)
      ->where('l.status_layanan >=', 5)
      ->orderBy('l.tanggal_checkout', 'DESC')
      ->get()
      ->getResult();
  }

  /**
   * Get global status summary untuk semua layanan aktif
   * 
   * @param array $lnKodeList
   * @return array Associative array dengan key = kode_layanan, value = summary data
   */
  public function getGlobalStatusSummary($lnKodeList)
  {
    if (empty($lnKodeList)) {
      return [];
    }

    $db = $this->db;

    $rowsG = $db->table('t_layanan_detil')
      ->select("
                kode_layanan,
                COUNT(*) AS total,
                SUM(CASE WHEN files = 1 THEN 1 ELSE 0 END) AS cnt1,
                SUM(CASE WHEN files = 2 THEN 1 ELSE 0 END) AS cnt2,
                SUM(CASE WHEN files = 0 OR files = 3 THEN 1 ELSE 0 END) AS cnt0
            ", false)
      ->whereIn('kode_layanan', $lnKodeList)
      ->where('status_layanan', 1)
      ->groupBy('kode_layanan')
      ->get()
      ->getResultArray();

    $statusSummary = [];
    foreach ($rowsG as $sr) {
      $statusSummary[(int) $sr['kode_layanan']] = [
        'total' => (int) $sr['total'],
        'cnt1' => (int) $sr['cnt1'],
        'cnt2' => (int) $sr['cnt2'],
        'cnt0' => (int) $sr['cnt0'],
      ];
    }

    return $statusSummary;
  }

  /**
   * Get user-specific status summary
   * 
   * @param array $lnKodeList
   * @param int $userId
   * @return array Associative array dengan key = kode_layanan, value = summary data
   */
  public function getUserStatusSummary($lnKodeList, $userId)
  {
    if (empty($lnKodeList)) {
      return [];
    }

    $db = $this->db;

    $rowsU = $db->table('t_layanan_detil as d')
      ->select("
                d.kode_layanan,
                COUNT(*) AS total,
                SUM(CASE WHEN d.files = 1 THEN 1 ELSE 0 END) AS cnt1,
                SUM(CASE WHEN d.files = 2 THEN 1 ELSE 0 END) AS cnt2,
                SUM(CASE WHEN d.files = 0 OR d.files = 3 THEN 1 ELSE 0 END) AS cnt0
            ", false)
      ->join('r_tim rt', 'rt.uji_kode = d.uji_kode', 'inner')
      ->whereIn('d.kode_layanan', $lnKodeList)
      ->where('d.status_layanan', 1)
      ->where('rt.user_id', $userId)
      ->groupBy('d.kode_layanan')
      ->get()
      ->getResultArray();

    $userSummary = [];
    foreach ($rowsU as $sr) {
      $userSummary[(int) $sr['kode_layanan']] = [
        'total' => (int) $sr['total'],
        'cnt1' => (int) $sr['cnt1'],
        'cnt2' => (int) $sr['cnt2'],
        'cnt0' => (int) $sr['cnt0'],
      ];
    }

    return $userSummary;
  }

  /**
   * Get detail list dengan join ke t_files_lhus dan r_tim
   * 
   * @param int $kode_layanan
   * @param int $userId
   * @return array
   */
  public function getDetailListByLayananAndUser($kode_layanan, $userId)
  {
    $db = $this->db;

    $builder = $db->table('t_layanan_detil as d');
    $builder->select("
            d.kode,
            d.uji_kode,
            d.kode_layanan,
            d.nama_layanan,
            d.jumlah,
            d.biaya,
            d.catatan_manajer,
            d.files,
            d.status_layanan,
            lhus.file_lhus,
            lhus.catatan as ket_lhus,
            lhus.validasi_by
        ", false);

    $builder->join('r_tim rt', 'rt.uji_kode = d.uji_kode', 'inner');

    // Subquery untuk ambil hanya 1 file terbaru per kode
    $builder->join(
      '(SELECT lhus1.* FROM t_files_lhus lhus1 
              INNER JOIN (
                SELECT kode, MAX(file_id) as max_file_id 
                FROM t_files_lhus 
                GROUP BY kode
              ) lhus2 ON lhus1.kode = lhus2.kode AND lhus1.file_id = lhus2.max_file_id
            ) lhus',
      'lhus.kode = d.kode',
      'left'
    );

    $builder->where('d.kode_layanan', $kode_layanan);
    $builder->where('d.status_layanan', 1);
    $builder->where('rt.user_id', $userId);
    $builder->orderBy('d.kode', 'ASC');

    return $builder->get()->getResult();
  }

  /**
   * Simpan catatan ke t_files_lhus (selalu diarahkan ke tabel file, tidak lagi di t_layanan_detil).
   * Ketika record file belum tersedia (edge-case), method akan membuat placeholder agar catatan tetap tercatat.
   */
  public function updateFileLhusCatatan(int $detKode, ?string $catatan, ?int $actorId = null): bool
  {
    $db = $this->db;

    // Ambil file LHUS terbaru untuk detil ini
    $latest = $db->table('t_files_lhus')
      ->select('file_id')
      ->where('kode', $detKode)
      ->orderBy('file_id', 'DESC')
      ->limit(1)
      ->get()
      ->getRow();

    if ($latest) {
      $res = $db->table('t_files_lhus')
        ->where('file_id', $latest->file_id)
        ->update(['catatan' => $catatan]);

      return $db->affectedRows() > 0 || $res === true;
    }

    // Jika belum ada record di t_files_lhus (kasus jarang), buat placeholder baru
    $layanan = $this->getLayananKodeByDetailKode($detKode);
    $insertData = [
      'kode' => $detKode,
      'kode_layanan' => $layanan->kode_layanan ?? null,
      'catatan' => $catatan,
      'status' => null,
    ];

    if ($actorId) {
      $insertData['validasi_by'] = $actorId;
    }

    return $db->table('t_files_lhus')->insert($insertData);
  }

  /**
   * Get kode_layanan dari detail kode
   * 
   * @param int $detKode
   * @return object|null
   */
  public function getLayananKodeByDetailKode($detKode)
  {
    $db = $this->db;

    return $db->table('t_layanan_detil')
      ->select('kode_layanan')
      ->where('kode', $detKode)
      ->get()
      ->getRow();
  }

  /**
   * Update status files di t_layanan_detil
   * 
   * @param int $detKode
   * @param int $filesStatus
   * @return bool
   */
  public function updateDetailFilesStatus($detKode, $filesStatus)
  {
    $db = $this->db;

    return $db->table('t_layanan_detil')
      ->where('kode', $detKode)
      ->update(['files' => $filesStatus]);
  }

  /**
   * Update validasi_by dan status di t_files_lhus
   * 
   * @param int $detKode
   * @param int $status
   * @param int $validasiBy
   * @return bool
   */
  public function updateFileLhusValidation($detKode, $status, $validasiBy)
  {
    $db = $this->db;

    return $db->table('t_files_lhus')
      ->where('kode', $detKode)
      ->update([
        'status' => $status,
        'validasi_by' => $validasiBy
      ]);
  }

  /**
   * Get status summary untuk satu layanan (untuk auto-update logic)
   * 
   * @param int $kode_layanan
   * @return array|null
   */
  public function getLayananStatusSummary($kode_layanan)
  {
    $db = $this->db;

    return $db->query("
            SELECT 
                COUNT(*) AS total,
                SUM(CASE WHEN files = 1 THEN 1 ELSE 0 END) AS cnt1
            FROM t_layanan_detil
            WHERE kode_layanan = ? AND status_layanan = 1
        ", [$kode_layanan])->getRowArray();
  }

  /**
   * Update status_layanan di t_layanan
   * 
   * @param int $kode_layanan
   * @param int $status
   * @return bool
   */
  public function updateLayananStatus($kode_layanan, $status)
  {
    return $this->update($kode_layanan, ['status_layanan' => $status]);
  }

  /**
   * Update log sampel dengan penerbitan_lhus dan verifikasi_lhu
   * 
   * @param int $kode_layanan
   * @param string $currentTime
   * @return bool
   */
  public function updateLogSampelLhus($kode_layanan, $currentTime)
  {
    $db = $this->db;

    try {
      $logUpdate = [
        'penerbitan_lhus' => $currentTime,
        'verifikasi_lhu' => $currentTime
      ];

      return $db->table('t_log_sampel')
        ->where('kode_layanan', $kode_layanan)
        ->update($logUpdate);
    } catch (\Exception $e) {
      log_message('error', 'Error update log sampel penerbitan_lhus & verifikasi_lhu: ' . $e->getMessage());
      return false;
    }
  }

  /**
   * Process detail LHUS dengan transaction
   * 
   * @param int $detKode
   * @param int $newStatus 1=terima, 2=tolak
   * @param int $accUserId
   * @return array ['success' => bool, 'kode_layanan' => int|null, 'error' => string|null]
   */
  public function processDetailLhusTransaction($detKode, $newStatus, $accUserId)
  {
    $db = $this->db;
    $db->transStart();

    try {
      // Ambil LN parent dari detil ini
      $detRow = $this->getLayananKodeByDetailKode($detKode);

      if (!$detRow) {
        $db->transRollback();
        return ['success' => false, 'kode_layanan' => null, 'error' => 'Detil tidak ditemukan'];
      }

      $kode_layanan = (int) $detRow->kode_layanan;

      // UPDATE status files di t_layanan_detil
      $this->updateDetailFilesStatus($detKode, $newStatus);

      // Update validasi_by di t_files_lhus
      $this->updateFileLhusValidation($detKode, $newStatus, $accUserId);

      // Auto set status_layanan = 6 jika semua det aktif sudah diterima
      $rowG = $this->getLayananStatusSummary($kode_layanan);

      $gTotal = (int) ($rowG['total'] ?? 0);
      $gCnt1 = (int) ($rowG['cnt1'] ?? 0);

      if ($gTotal > 0 && $gCnt1 === $gTotal) {
        $this->updateLayananStatus($kode_layanan, 6);

        // Update log sampel
        $currentTime = date('Y-m-d H:i:s');
        $this->updateLogSampelLhus($kode_layanan, $currentTime);
      }

      $db->transComplete();

      return [
        'success' => $db->transStatus(),
        'kode_layanan' => $kode_layanan,
        'error' => null
      ];

    } catch (\Throwable $e) {
      $db->transRollback();
      return ['success' => false, 'kode_layanan' => null, 'error' => $e->getMessage()];
    }
  }

  /**
   * Get user status summary untuk satu layanan
   * 
   * @param int $kode_layanan
   * @param int $userId
   * @return array|null
   */
  public function getUserStatusSummaryForLayanan($kode_layanan, $userId)
  {
    $db = $this->db;

    return $db->table('t_layanan_detil as d')
      ->select("
                COUNT(*) AS total,
                SUM(CASE WHEN d.files = 1 THEN 1 ELSE 0 END) AS cnt1,
                SUM(CASE WHEN d.files = 0 OR d.files = 3 THEN 1 ELSE 0 END) AS cnt0
            ", false)
      ->join('r_tim rt', 'rt.uji_kode = d.uji_kode', 'inner')
      ->where('d.kode_layanan', $kode_layanan)
      ->where('d.status_layanan', 1)
      ->where('rt.user_id', $userId)
      ->get()
      ->getRowArray();
  }

  /**
   * Get global status summary untuk satu layanan
   * 
   * @param int $kode_layanan
   * @return array|null
   */
  public function getGlobalStatusSummaryForLayanan($kode_layanan)
  {
    $db = $this->db;

    return $db->query("
            SELECT 
                COUNT(*) AS total,
                SUM(CASE WHEN files = 1 THEN 1 ELSE 0 END) AS cnt1,
                SUM(CASE WHEN files = 0 OR files = 3 THEN 1 ELSE 0 END) AS cnt0
            FROM t_layanan_detil
            WHERE kode_layanan = ? AND status_layanan = 1
        ", [$kode_layanan])->getRowArray();
  }

  /**
   * Check if user has access to layanan
   * 
   * @param int $kode_layanan
   * @param int $userId
   * @return bool
   */
  public function checkUserAccessToLayanan($kode_layanan, $userId)
  {
    $db = $this->db;

    $checkBuilder = $db->table('t_layanan_detil as d');
    $checkBuilder->select('1');
    $checkBuilder->join('r_tim as rt', 'rt.uji_kode = d.uji_kode', 'inner');
    $checkBuilder->where('d.kode_layanan', $kode_layanan);
    $checkBuilder->where('rt.user_id', $userId);
    $exists = $checkBuilder->limit(1)->get()->getRow();

    return !empty($exists);
  }

  /**
   * Get sample identity data by kode_layanan
   * 
   * @param int $kode_layanan
   * @return object|null
   */
  public function getSampleIdentityByLayanan($kode_layanan)
  {
    $db = $this->db;

    return $db->table('t_identitas_sampel')
      ->where('kode_layanan', $kode_layanan)
      ->get()
      ->getRow();
  }
}
