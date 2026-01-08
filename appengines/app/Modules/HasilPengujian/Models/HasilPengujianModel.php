<?php

namespace Modules\HasilPengujian\Models;

use CodeIgniter\Model;

class HasilPengujianModel extends Model
{
  protected $db;

  public function __construct()
  {
    parent::__construct();
    $this->db = \Config\Database::connect();
  }

  /**
   * Get layanan codes related to user through r_tim -> t_layanan_detil
   * 
   * @param int $userId User ID
   * @return array Array of kode_layanan
   */
  public function getLayananKodesByUserId(int $userId): array
  {
    $builder = $this->db->table('t_layanan_detil as d');
    $builder->select('DISTINCT d.kode_layanan AS kode_layanan', false);
    $builder->join('r_tim rt', 'rt.uji_kode = d.uji_kode', 'inner');
    $builder->where('rt.user_id', $userId);
    $builder->where('d.status_layanan', 1);
    $rows = $builder->get()->getResult();

    $lnKodeList = [];
    foreach ($rows as $item) {
      if (is_object($item) && isset($item->kode_layanan)) {
        $lnKodeList[] = $item->kode_layanan;
      } elseif (is_array($item) && isset($item['kode_layanan'])) {
        $lnKodeList[] = $item['kode_layanan'];
      }
    }

    return array_values(array_unique(array_filter($lnKodeList)));
  }

  /**
   * Get aggregated status for user based on r_tim concept
   * 
   * @param int $userId User ID
   * @return string Compiled SQL for subquery
   */
  public function getUserStatusAggregationSubquery(int $userId): string
  {
    $aggSql = $this->db->table('t_layanan_detil d')
      ->select("
                d.kode_layanan,
                MAX(CASE WHEN d.status_layanan=1 AND EXISTS(
                    SELECT 1 FROM r_tim rt2
                    WHERE rt2.uji_kode = d.uji_kode
                      AND rt2.user_id = {$userId}
                ) AND d.files = 2 THEN 1 ELSE 0 END) AS has_reject_for_user,
                SUM(CASE WHEN d.status_layanan=1 AND EXISTS(
                    SELECT 1 FROM r_tim rt2
                    WHERE rt2.uji_kode = d.uji_kode
                      AND rt2.user_id = {$userId}
                ) THEN 1 ELSE 0 END) AS user_active_total,
                SUM(CASE WHEN d.status_layanan=1 AND EXISTS(
                    SELECT 1 FROM r_tim rt2
                    WHERE rt2.uji_kode = d.uji_kode
                      AND rt2.user_id = {$userId}
                ) AND d.files = 1 THEN 1 ELSE 0 END) AS user_accepted_total,
                SUM(CASE WHEN d.status_layanan=1 AND EXISTS(
                    SELECT 1 FROM r_tim rt2
                    WHERE rt2.uji_kode = d.uji_kode
                      AND rt2.user_id = {$userId}
                ) AND d.files = 0 THEN 1 ELSE 0 END) AS user_sent_total,
                SUM(CASE WHEN d.status_layanan=1 AND EXISTS(
                    SELECT 1 FROM r_tim rt2
                    WHERE rt2.uji_kode = d.uji_kode
                      AND rt2.user_id = {$userId}
                ) AND d.files = 3 THEN 1 ELSE 0 END) AS user_uploaded_total,
                SUM(CASE WHEN d.status_layanan=1 AND EXISTS(
                    SELECT 1 FROM r_tim rt2
                    WHERE rt2.uji_kode = d.uji_kode
                      AND rt2.user_id = {$userId}
                ) AND (d.files IS NULL) THEN 1 ELSE 0 END) AS pending_for_user
            ", false)
      ->groupBy('d.kode_layanan')
      ->getCompiledSelect(false);

    return $aggSql;
  }

  /**
   * Get layanan list with status filtering for Penyelia
   * 
   * @param array $lnKodeList List of layanan codes
   * @param int $userId User ID
   * @param array $lnStatusFilter Numeric status filter array
   * @param bool $wantReject Filter for rejected items
   * @param bool $wantUploaded Filter for uploaded items
   * @return array List of layanan
   */
  public function getLayananListForPenyelia(
    array $lnKodeList,
    int $userId,
    array $lnStatusFilter = [],
    bool $wantReject = false,
    bool $wantUploaded = false
  ): array {
    $aggSql = $this->getUserStatusAggregationSubquery($userId);

    $builder = $this->db->table('t_layanan as l');
    $builder->select('l.*');
    $builder->join("({$aggSql}) agg", 'agg.kode_layanan = l.kode_layanan', 'left');

    // Info pemesan
    $builder->select('u.user_name as pemesan_name, u.user_email as pemesan_email, u.user_identity as pemesan_identity');
    $builder->join('account_users as u', 'u.user_id = l.user_id', 'left');

    $builder->whereIn('l.kode_layanan', $lnKodeList);
    $builder->where('l.status_layanan !=', 2); // exclude Ditolak awal
    $builder->orderBy('l.tanggal_checkout', 'DESC');

    // Apply filters if any
    if ($wantReject || $wantUploaded || !empty($lnStatusFilter)) {
      $builder->groupStart();

      if ($wantReject) {
        $builder->orGroupStart()
          ->where('COALESCE(agg.has_reject_for_user,0) =', 1)
          ->groupEnd();
      }

      if ($wantUploaded) {
        $builder->orGroupStart()
          ->where('COALESCE(agg.has_reject_for_user,0) =', 0)
          ->where('COALESCE(agg.user_uploaded_total,0) >', 0)
          ->groupEnd();
      }

      foreach ($lnStatusFilter as $s) {
        switch ((int) $s) {
          case 4: // "Sedang dalam pengujian"
            $builder->orGroupStart()
              ->where('COALESCE(agg.has_reject_for_user,0) =', 0)
              ->where('COALESCE(agg.pending_for_user,0) >', 0)
              ->where('l.status_layanan', 4)
              ->groupEnd();
            break;

          case 5: // "LHUS sedang diverifikasi manajer (terkirim)"
            $builder->orGroupStart()
              ->where('COALESCE(agg.has_reject_for_user,0) =', 0)
              ->where('COALESCE(agg.user_sent_total,0) >', 0)
              ->groupStart()
              ->where('COALESCE(agg.user_accepted_total,0) < COALESCE(agg.user_active_total,0)', null, false)
              ->orWhere('COALESCE(agg.user_active_total,0) =', 0)
              ->groupEnd()
              ->groupEnd();
            break;

          case 6: // "LHUS diterima"
            $builder->orGroupStart()
              ->where('COALESCE(agg.has_reject_for_user,0) =', 0)
              ->where('COALESCE(agg.pending_for_user,0) =', 0)
              ->where('COALESCE(agg.user_active_total,0) >', 0)
              ->where('COALESCE(agg.user_accepted_total,0) = COALESCE(agg.user_active_total,0)', null, false)
              ->groupEnd();
            break;

          default:
            $builder->orGroupStart()
              ->where('l.status_layanan', (int) $s)
              ->where('COALESCE(agg.has_reject_for_user,0) =', 0)
              ->groupStart()
              ->where('COALESCE(agg.pending_for_user,0) >', 0)
              ->orWhere('COALESCE(agg.user_active_total,0) =', 0)
              ->groupEnd()
              ->groupEnd();
            break;
        }
      }

      $builder->groupEnd();
    }

    return $builder->get()->getResult();
  }

  /**
   * Check if user is authorized for layanan via r_tim
   * 
   * @param int|string $kode_layanan Layanan code
   * @param int $userId User ID
   * @return bool
   */
  public function isUserAuthorizedForLayanan($kode_layanan, int $userId): bool
  {
    $count = (int) $this->db->table('t_layanan_detil as d')
      ->join('r_tim as rt', 'rt.uji_kode = d.uji_kode', 'inner')
      ->where('d.kode_layanan', $kode_layanan)
      ->where('rt.user_id', $userId)
      ->where('d.status_layanan', 1)
      ->limit(1)
      ->countAllResults(false);

    return $count > 0;
  }

  /**
   * Get detail list with file information for Penyelia
   * 
   * @param int|string $kode Layanan code
   * @param int $userId User ID
   * @return array
   */
  public function getDetailListForPenyelia($kode, int $userId): array
  {
    $builder = $this->db->table('t_layanan_detil as d');

    $builder->select("
            d.kode,
            ANY_VALUE(d.uji_kode) AS uji_kode,
            ANY_VALUE(d.kode_layanan) AS kode_layanan,
            ANY_VALUE(d.nama_layanan) AS nama_layanan,
            ANY_VALUE(d.kode_jenis) AS kode_jenis,
            GROUP_CONCAT(DISTINCT COALESCE(lhus.catatan, '') SEPARATOR ' | ') AS detKet,
            GROUP_CONCAT(DISTINCT COALESCE(lhus.catatan, '') SEPARATOR ' | ') AS detKetManajer,
            GROUP_CONCAT(DISTINCT COALESCE(lhus.catatan, '') SEPARATOR ' | ') AS catatan_lhus,
            GROUP_CONCAT(DISTINCT d.files SEPARATOR ',') AS detFilesList,
            MAX(d.files) AS detFilesMax,
            SUM(d.jumlah) AS jumlah,
            SUM(d.biaya) AS detBiaya,
            MAX(d.status_layanan) AS status_group,
            ANY_VALUE(d.terima_layanan_by) AS terima_layanan_by,
            ANY_VALUE(lhus.validasi_by) AS validasi_by,
            ANY_VALUE(acc.nama) AS acc_by,
            (SELECT nama FROM r_metode WHERE metode_kode = d.metode_pengujian LIMIT 1) AS metode_nama,
            ANY_VALUE(lhus.file_lhus) AS file_lhus,
            ANY_VALUE(lhus.status) AS lhus_status
        ");

    // "Acc Manajer" pada Hasil Pengujian mengacu pada validator file LHUS terbaru (t_files_lhus.validasi_by)
    $builder->join('r_tim as rt', 'rt.uji_kode = d.uji_kode', 'inner');

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

    $builder->join('account acc', 'acc.user_id = lhus.validasi_by', 'left');

    $builder->where('d.kode_layanan', $kode);
    $builder->where('rt.user_id', $userId);
    $builder->where('d.status_layanan', 1);
    $builder->groupBy('d.kode');

    return $builder->get()->getResult();
  }

  /**
   * Get file information from t_files_lhus table
   * 
   * @param int $detailKode Detail code
   * @return object|null
   */
  public function getFileLhus(int $detailKode): ?object
  {
    return $this->db->table('t_files_lhus')
      ->where('kode', $detailKode)
      ->limit(1)
      ->get()
      ->getRow();
  }

  /**
   * Get layanan status by kode_layanan
   * 
   * @param int|string $kode_layanan Layanan code
   * @return object|null
   */
  public function getLayananStatus($kode_layanan): ?object
  {
    return $this->db->table('t_layanan')
      ->select('status_layanan')
      ->where('kode_layanan', $kode_layanan)
      ->get()
      ->getRow();
  }

  /**
   * Check if user has missing files (files that need to be uploaded before sending)
   * 
   * @param int|string $kode_layanan Layanan code
   * @param int $userId User ID
   * @return array ['missingCount' => int, 'missingItems' => array]
   */
  public function checkMissingFilesForUser($kode_layanan, int $userId): array
  {
    $builder = $this->db->table('t_layanan_detil as d');
    $builder->select("d.kode, d.files");
    $builder->join('r_tim as t', 't.uji_kode = d.uji_kode', 'inner');
    $builder->where('d.kode_layanan', $kode_layanan);
    $builder->where('t.user_id', $userId);
    $builder->where('d.status_layanan', 1);
    $userDetRows = $builder->get()->getResult();

    $missingCount = 0;
    $missingItems = [];

    foreach ($userDetRows as $dr) {
      $filesVal = isset($dr->files) ? (int) $dr->files : null;

      // Status files:
      // NULL = belum upload sama sekali
      // 2 = ditolak (perlu upload ulang)
      // 3 = terunggah (siap dikirim) - OK
      // 0 = sudah terkirim - OK
      // 1 = sudah diterima - OK
      // 
      // Yang dianggap 'missing' hanya NULL dan 2 (ditolak)
      if ($filesVal === null || $filesVal === 2) {
        $missingCount++;
        $missingItems[] = $dr->kode ?? null;
      }
    }

    return [
      'missingCount' => $missingCount,
      'missingItems' => $missingItems
    ];
  }

  /**
   * Update files status for user's layanan detail items
   * 
   * @param int|string $kode_layanan Layanan code
   * @param int $userId User ID
   * @param int|null $fromStatus From status (null = update all regardless of current status)
   * @param int $toStatus To status
   * @return bool
   */
  public function updateFilesStatusForUser($kode_layanan, int $userId, ?int $fromStatus, int $toStatus): bool
  {
    if ($fromStatus !== null) {
      $sql = "
                UPDATE t_layanan_detil d
                INNER JOIN r_tim rt ON rt.uji_kode = d.uji_kode
                SET d.files = ?
                WHERE d.kode_layanan = ?
                  AND rt.user_id = ?
                  AND d.status_layanan = 1
                  AND d.files = ?
            ";
      return $this->db->query($sql, [$toStatus, $kode_layanan, $userId, $fromStatus]);
    } else {
      $sql = "
                UPDATE t_layanan_detil d
                INNER JOIN r_tim rt ON rt.uji_kode = d.uji_kode
                SET d.files = ?
                WHERE d.kode_layanan = ?
                  AND rt.user_id = ?
                  AND d.status_layanan = 1
            ";
      return $this->db->query($sql, [$toStatus, $kode_layanan, $userId]);
    }
  }

  /**
   * Update files status in t_files_lhus table
   * 
   * @param int|string $kode_layanan Layanan code
   * @param int $userId User ID
   * @param int $fromStatus From status
   * @param int $toStatus To status
   * @return bool
   */
  public function updateFilesLhusStatusForUser($kode_layanan, int $userId, int $fromStatus, int $toStatus): bool
  {
    $sql = "
            UPDATE t_files_lhus lhus
            INNER JOIN t_layanan_detil d ON d.kode = lhus.kode
            INNER JOIN r_tim rt ON rt.uji_kode = d.uji_kode
            SET lhus.status = ?
            WHERE lhus.kode_layanan = ?
              AND rt.user_id = ?
              AND d.status_layanan = 1
              AND lhus.status = ?
        ";

    return $this->db->query($sql, [$toStatus, $kode_layanan, $userId, $fromStatus]);
  }

  /**
   * Update terima_layanan_by for user's items
   * 
   * @param int|string $kode_layanan Layanan code
   * @param int $userId User ID
   * @param int $acceptedBy User who accepted
   * @return bool
   */
  public function updateTerimaLayananBy($kode_layanan, int $userId, int $acceptedBy): bool
  {
    $sql = "
            UPDATE t_layanan_detil d
            INNER JOIN r_tim rt2 ON rt2.uji_kode = d.uji_kode
            SET d.terima_layanan_by = ?
            WHERE d.kode_layanan = ?
              AND rt2.user_id = ?
              AND d.status_layanan = 1
              AND d.files = 0
        ";

    return $this->db->query($sql, [$acceptedBy, $kode_layanan, $userId]);
  }

  /**
   * Check if all files are uploaded for layanan (across all users)
   * 
   * @param int|string $kode_layanan Layanan code
   * @return int Count of items without files
   */
  public function countMissingFilesForLayanan($kode_layanan): int
  {
    // Status files:
    // NULL = belum upload sama sekali
    // 2 = ditolak (perlu upload ulang)
    // 3 = terunggah (siap dikirim) - OK
    // 0 = sudah terkirim - OK  
    // 1 = sudah diterima - OK
    //
    // Yang dianggap 'missing' hanya NULL dan 2 (ditolak)
    $sql = "
            SELECT COUNT(*) as total_belum_upload
            FROM t_layanan_detil d
            WHERE d.kode_layanan = ?
              AND d.status_layanan = 1
              AND (d.files IS NULL OR d.files = 2)
        ";

    $result = $this->db->query($sql, [$kode_layanan])->getRow();
    return $result ? (int) $result->total_belum_upload : 0;
  }

  /**
   * Update layanan status
   * 
   * @param int|string $kode_layanan Layanan code
   * @param int $status New status
   * @return bool
   */
  public function updateLayananStatus($kode_layanan, int $status): bool
  {
    return $this->db->table('t_layanan')
      ->where('kode_layanan', $kode_layanan)
      ->update(['status_layanan' => $status]);
  }

  /**
   * Update log sampel verifikasi_hasil_uji timestamp
   * 
   * @param int|string $kode_layanan Layanan code
   * @return bool
   */
  public function updateLogSampelVerifikasiHasilUji($kode_layanan): bool
  {
    try {
      $logUpdate = [
        'verifikasi_hasil_uji' => date('Y-m-d H:i:s')
      ];

      return $this->db->table('t_log_sampel')
        ->where('kode_layanan', $kode_layanan)
        ->update($logUpdate);
    } catch (\Exception $e) {
      log_message('error', 'Error update log sampel verifikasi_hasil_uji: ' . $e->getMessage());
      return false;
    }
  }

  /**
   * Update files status for specific detail
   * 
   * @param int $detailKode Detail code
   * @param int $status New status
   * @return bool
   */
  public function updateDetailFilesStatus(int $detailKode, int $status): bool
  {
    return $this->db->table('t_layanan_detil')
      ->where('kode', $detailKode)
      ->update(['files' => $status]);
  }

  /**
   * Insert or update file in t_files_lhus
   * 
   * @param int $detailKode Detail code
   * @param int|string $kode_layanan Layanan code
   * @param string $filename Filename
   * @param int $userId User ID who uploaded
   * @param int $status File status
   * @return bool
   */
  public function saveFileLhus(int $detailKode, $kode_layanan, string $filename, int $userId, int $status = 3): bool
  {
    $existingFile = $this->getFileLhus($detailKode);

    if ($existingFile) {
      // Update existing record
      $result = $this->db->table('t_files_lhus')
        ->where('kode', $detailKode)
        ->update([
          'file_lhus' => $filename,
          'upload_by' => $userId,
          'status' => $status,
          'kode_layanan' => $kode_layanan
        ]);

      // Delete old file
      if (!empty($existingFile->file_lhus) && $existingFile->file_lhus !== $filename) {
        $oldFilePath = FCPATH . 'uploads/lhus/' . $existingFile->file_lhus;
        if (is_file($oldFilePath)) {
          @unlink($oldFilePath);
        }
      }

      return $result;
    } else {
      // Insert new record
      return $this->db->table('t_files_lhus')->insert([
        'kode' => $detailKode,
        'kode_layanan' => $kode_layanan,
        'file_lhus' => $filename,
        'upload_by' => $userId,
        'status' => $status
      ]);
    }
  }

  /**
   * Get detail rows for bulk file upload
   * 
   * @param int|string $kode_layanan Layanan code
   * @param int $userId User ID
   * @return array
   */
  public function getDetailRowsForBulkUpload($kode_layanan, int $userId): array
  {
    return $this->db->table('t_layanan_detil as d2')
      ->join('r_tim as t2', 't2.uji_kode = d2.uji_kode', 'inner')
      ->where('d2.kode_layanan', $kode_layanan)
      ->where('t2.user_id', $userId)
      ->where('d2.status_layanan', 1)
      ->get()->getResult();
  }

  /**
   * Check if user has rejected LHUS
   * 
   * @param int|string $kode_layanan Layanan code
   * @param int $userId User ID
   * @return bool
   */
  public function hasRejectedLhus($kode_layanan, int $userId): bool
  {
    $count = (int) $this->db->table('t_layanan_detil')
      ->select('1')
      ->join('r_tim rt', 'rt.uji_kode = t_layanan_detil.uji_kode', 'inner')
      ->where('t_layanan_detil.kode_layanan', $kode_layanan)
      ->where('t_layanan_detil.status_layanan', 1)
      ->where('rt.user_id', $userId)
      ->where('t_layanan_detil.files', 2)
      ->limit(1)
      ->countAllResults(false);

    return $count > 0;
  }

  /**
   * Check if user has uploaded LHUS (not sent yet)
   * 
   * @param int|string $kode_layanan Layanan code
   * @param int $userId User ID
   * @return bool
   */
  public function hasUploadedLhus($kode_layanan, int $userId): bool
  {
    $count = (int) $this->db->table('t_layanan_detil as d')
      ->select('1')
      ->join('r_tim rt', 'rt.uji_kode = d.uji_kode', 'inner')
      ->where('d.kode_layanan', $kode_layanan)
      ->where('d.status_layanan', 1)
      ->where('rt.user_id', $userId)
      ->where('d.files', 3)
      ->limit(1)
      ->countAllResults(false);

    return $count > 0;
  }

  /**
   * Check if all user's LHUS are accepted
   * 
   * @param int|string $kode_layanan Layanan code
   * @param int $userId User ID
   * @return bool
   */
  public function allUserLhusAccepted($kode_layanan, int $userId): bool
  {
    $totalUserActive = (int) $this->db->table('t_layanan_detil as d')
      ->join('r_tim rt', 'rt.uji_kode = d.uji_kode', 'inner')
      ->where('d.kode_layanan', $kode_layanan)
      ->where('d.status_layanan', 1)
      ->where('rt.user_id', $userId)
      ->countAllResults(false);

    if ($totalUserActive <= 0) {
      return false;
    }

    $acceptedCount = (int) $this->db->table('t_layanan_detil as d')
      ->join('r_tim rt', 'rt.uji_kode = d.uji_kode', 'inner')
      ->where('d.kode_layanan', $kode_layanan)
      ->where('d.status_layanan', 1)
      ->where('rt.user_id', $userId)
      ->where('d.files', 1)
      ->countAllResults(false);

    return $acceptedCount === $totalUserActive;
  }

  /**
   * Check if user has sent LHUS to manager
   * 
   * @param int|string $kode_layanan Layanan code
   * @param int $userId User ID
   * @return bool
   */
  public function hasSentLhus($kode_layanan, int $userId): bool
  {
    $count = (int) $this->db->table('t_layanan_detil as d')
      ->select('1')
      ->join('r_tim rt', 'rt.uji_kode = d.uji_kode', 'inner')
      ->where('d.kode_layanan', $kode_layanan)
      ->where('d.status_layanan', 1)
      ->where('rt.user_id', $userId)
      ->where('d.files', 0)
      ->limit(1)
      ->countAllResults(false);

    return $count > 0;
  }

  /**
   * Start database transaction
   */
  public function transStart(): void
  {
    $this->db->transStart();
  }

  /**
   * Begin database transaction
   */
  public function transBegin(): void
  {
    $this->db->transBegin();
  }

  /**
   * Complete database transaction
   */
  public function transComplete(): void
  {
    $this->db->transComplete();
  }

  /**
   * Commit database transaction
   */
  public function transCommit(): void
  {
    $this->db->transCommit();
  }

  /**
   * Rollback database transaction
   */
  public function transRollback(): void
  {
    $this->db->transRollback();
  }

  /**
   * Get database transaction status
   * 
   * @return bool
   */
  public function transStatus(): bool
  {
    return $this->db->transStatus();
  }

  /**
   * Get database error
   * 
   * @return array
   */
  public function getError(): array
  {
    return $this->db->error();
  }

  /**
   * Get affected rows count
   * 
   * @return int
   */
  public function affectedRows(): int
  {
    return $this->db->affectedRows();
  }
}