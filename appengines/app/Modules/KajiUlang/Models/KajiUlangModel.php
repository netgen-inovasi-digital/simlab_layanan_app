<?php

namespace Modules\KajiUlang\Models;

use CodeIgniter\Model;

class KajiUlangModel extends Model
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
    $rows = $builder->get()->getResult();

    $lnKodeList = [];
    foreach ($rows as $item) {
      if (is_array($item) && isset($item['kode_layanan'])) {
        $lnKodeList[] = $item['kode_layanan'];
      } elseif (is_object($item) && isset($item->kode_layanan)) {
        $lnKodeList[] = $item->kode_layanan;
      }
    }

    return array_values(array_unique(array_filter($lnKodeList, function ($v) {
      return $v !== null && $v !== '' && $v !== 0;
    })));
  }

  /**
   * Get pending count per manager for layanan
   * 
   * @param int $userId User ID
   * @return \CodeIgniter\Database\BaseBuilder Builder with subquery
   */
  public function getPendingPerManagerSubquery(int $userId)
  {
    $pendingSub = $this->db->table('t_layanan_detil as det')
      ->select('det.kode_layanan AS kode_layanan, SUM(CASE WHEN (det.status_layanan = 0 OR det.status_layanan IS NULL) THEN 1 ELSE 0 END) AS pending_for_manager', false)
      ->join('r_tim rt', 'rt.uji_kode = det.uji_kode', 'inner')
      ->where('rt.user_id', $userId)
      ->groupBy('det.kode_layanan');

    return $pendingSub;
  }

  /**
   * Get layanan list for manager with status filtering
   * 
   * @param array $lnKodeList List of layanan codes
   * @param int $userId User ID
   * @param array $statusArr Status filter array
   * @return array List of layanan
   */
  public function getLayananListForManager(array $lnKodeList, int $userId, array $statusArr = []): array
  {
    $builder = $this->db->table('t_layanan as l');
    $builder->join('account_users as au', 'au.user_id = l.user_id', 'left');
    $builder->join('account as a', 'a.user_id = l.user_id', 'left');

    $builder->select("
            l.*,
            COALESCE(au.user_name, a.nama, l.user_email, '-') AS pemesan_name,
            COALESCE(au.user_email, l.user_email, '') AS pemesan_email,
            COALESCE(au.user_identity, '-') AS pemesan_identity,
            COALESCE(pm.pending_for_manager, 0) as pending_for_manager
        ", false);

    // Join with pending subquery
    $pendingSub = $this->getPendingPerManagerSubquery($userId);
    $builder->join('(' . $pendingSub->getCompiledSelect(false) . ') pm', 'pm.kode_layanan = l.kode_layanan', 'left');

    $builder->whereIn('l.kode_layanan', $lnKodeList);
    $builder->where('l.status_layanan !=', 2); // Exclude rejected

    // Filter by status
    if (!empty($statusArr)) {
      $builder->groupStart();
      foreach ($statusArr as $st) {
        $st = (int) $st;
        if ($st === 1) {
          // Belum direview: status_layanan = 1 AND pending_for_manager > 0
          $builder->orGroupStart()
            ->where('l.status_layanan', 1)
            ->where('COALESCE(pm.pending_for_manager,0) >', 0, false)
            ->groupEnd();
        } elseif ($st === 3) {
          // Terkirim ke admin: pending_for_manager = 0 and not draft
          $builder->orGroupStart()
            ->where('COALESCE(pm.pending_for_manager,0) =', 0, false)
            ->where('l.status_layanan !=', 0)
            ->groupEnd();
        } elseif ($st === 4) {
          // Pengujian
          $builder->orGroupStart()
            ->where('l.status_layanan', 4)
            ->groupEnd();
        } else {
          // Fallback to raw status_layanan
          $builder->orGroupStart()
            ->where('l.status_layanan', $st)
            ->groupEnd();
        }
      }
      $builder->groupEnd();
    }

    $builder->orderBy('l.tanggal_checkout', 'DESC');
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
      ->join('r_tim rt', 'rt.uji_kode = d.uji_kode', 'inner')
      ->where('d.kode_layanan', $kode_layanan)
      ->where('rt.user_id', $userId)
      ->limit(1)
      ->countAllResults(false);

    return $count > 0;
  }

  /**
   * Get layanan detail list for manager review - shows all records with same kode_layanan without quantity calculation
   *
   * @param int|string $kode Layanan code
   * @param int $userId User ID
   * @return array
   */
  public function getDetailListForReview($kode, int $userId): array
  {
    $builder = $this->db->table('t_layanan_detil as d');
    $builder->select("
            d.kode,
            d.uji_kode,
            d.kode_layanan,
            d.nama_layanan,
            d.kode_jenis,
            d.catatan_manajer,
            d.jumlah,
            d.biaya,
            d.status_layanan,
            d.metode_pengujian,
            m.nama AS metode_nama,
            l.no_invoice,
            l.status_layanan AS layanan_status,
            l.jumlah_kaji_ulang AS layanan_jumlah_kaji_ulang
        ");
    $builder->join('r_tim rt', 'rt.uji_kode = d.uji_kode', 'inner');
    $builder->join('r_metode m', 'm.metode_kode = d.metode_pengujian', 'left');
    $builder->join('t_layanan l', 'l.kode_layanan = d.kode_layanan', 'left');
    $builder->where('d.kode_layanan', $kode);
    $builder->where('rt.user_id', $userId);
    $builder->orderBy('d.uji_kode', 'ASC');

    return $builder->get()->getResult();
  }

  /**
   * Update komentar/catatan manajer for multiple detail records
   *
   * @param int|string $kode_layanan Layanan code
   * @param array $items Array of items with detailKode and komentar
   * @return bool
   */
  public function updateKomentarBatch($kode_layanan, array $items): bool
  {
    $this->db->transStart();

    $builder = $this->db->table('t_layanan_detil');
    foreach ($items as $it) {
      $detailKode = isset($it['detailKode']) ? (int) $it['detailKode'] : null;
      $kom = isset($it['komentar']) ? $it['komentar'] : null;

      if ($detailKode === null)
        continue;

      $builder->where('kode', $detailKode)
        ->where('kode_layanan', $kode_layanan)
        ->update(['catatan_manajer' => $kom]);
    }

    $this->db->transComplete();
    return $this->db->transStatus();
  }

  /**
   * Check if user exists in account
   * 
   * @param int $userId User ID
   * @return bool
   */
  public function isValidUser(int $userId): bool
  {
    $acc = $this->db->table('account')
      ->select('user_id')
      ->where('user_id', $userId)
      ->get()
      ->getRow();

    return $acc !== null;
  }

  /**
   * Check if user is authorized for specific uji via r_tim
   * 
   * @param int $ujiKode Uji code
   * @param int $userId User ID
   * @return bool
   */
  public function isUserAuthorizedForUji(int $ujiKode, int $userId): bool
  {
    $count = (int) $this->db->table('r_tim')
      ->where('uji_kode', $ujiKode)
      ->where('user_id', $userId)
      ->countAllResults(false);

    return $count > 0;
  }

  /**
   * Count layanan detail rows by conditions
   * 
   * @param int|string $kode_layanan Layanan code
   * @param int $ujiKode Uji code
   * @param int|null $statusLayanan Optional status filter
   * @return int
   */
  public function countLayananDetail($kode_layanan, int $ujiKode, ?int $statusLayanan = null): int
  {
    $builder = $this->db->table('t_layanan_detil');
    $builder->where('kode_layanan', $kode_layanan);
    $builder->where('uji_kode', $ujiKode);

    if ($statusLayanan !== null) {
      $builder->where('status_layanan', $statusLayanan);
    }

    return (int) $builder->countAllResults(false);
  }

  /**
   * Count pending layanan detail for entire layanan
   * 
   * @param int|string $kode_layanan Layanan code
   * @return int
   */
  public function countPendingForLayanan($kode_layanan): int
  {
    $builder = $this->db->table('t_layanan_detil');
    $builder->where('kode_layanan', $kode_layanan);
    $builder->groupStart()
      ->where('status_layanan', 0)
      ->orWhere('status_layanan IS NULL', null, false)
      ->groupEnd();

    return (int) $builder->countAllResults(false);
  }

  /**
   * Update layanan detail status (approve)
   * 
   * @param int|string $kode_layanan Layanan code
   * @param int $ujiKode Uji code
   * @param int $managerId Manager user ID
   * @return int Affected rows
   */
  public function approveLayananDetail($kode_layanan, int $ujiKode, int $managerId): int
  {
    $result = $this->db->table('t_layanan_detil')
      ->where('kode_layanan', $kode_layanan)
      ->where('uji_kode', $ujiKode)
      ->where('(status_layanan IS NULL OR status_layanan != 1)')
      ->update([
        'status_layanan' => 1,
        'terima_layanan_by' => $managerId
      ]);

    return $this->db->affectedRows();
  }

  /**
   * Update layanan detail status (reject)
   * 
   * @param int|string $kode_layanan Layanan code
   * @param int $ujiKode Uji code
   * @param int $managerId Manager user ID
   * @return int Affected rows
   */
  public function rejectLayananDetail($kode_layanan, int $ujiKode, int $managerId): int
  {
    $result = $this->db->table('t_layanan_detil')
      ->where('kode_layanan', $kode_layanan)
      ->where('uji_kode', $ujiKode)
      ->where('(status_layanan IS NULL OR status_layanan != 2)')
      ->update([
        'status_layanan' => 2,
        'terima_layanan_by' => $managerId
      ]);

    return $this->db->affectedRows();
  }

  /**
   * Check if user is authorized for specific detail record
   *
   * @param int $detailKode Detail kode (primary key)
   * @param int $userId User ID
   * @return bool
   */
  public function isUserAuthorizedForDetail(int $detailKode, int $userId): bool
  {
    $builder = $this->db->table('t_layanan_detil as d');
    $builder->join('r_tim rt', 'rt.uji_kode = d.uji_kode', 'inner');
    $builder->where('d.kode', $detailKode);
    $builder->where('rt.user_id', $userId);

    return $builder->countAllResults() > 0;
  }

  /**
   * Count detail records by kode with optional status filter
   *
   * @param int $detailKode Detail kode (primary key)
   * @param int|null $status Status filter (optional)
   * @return int
   */
  public function countDetailByKode(int $detailKode, ?int $status = null): int
  {
    $builder = $this->db->table('t_layanan_detil');
    $builder->where('kode', $detailKode);

    if ($status !== null) {
      $builder->where('status_layanan', $status);
    }

    return $builder->countAllResults();
  }

  /**
   * Approve layanan detail by kode (primary key)
   *
   * @param int $detailKode Detail kode (primary key)
   * @param int $managerId Manager user ID
   * @return int Affected rows
   */
  public function approveLayananDetailByKode(int $detailKode, int $managerId): int
  {
    $result = $this->db->table('t_layanan_detil')
      ->where('kode', $detailKode)
      ->where('(status_layanan IS NULL OR status_layanan != 1)')
      ->update([
        'status_layanan' => 1,
        'terima_layanan_by' => $managerId
      ]);

    return $this->db->affectedRows();
  }

  /**
   * Reject layanan detail by kode (primary key)
   *
   * @param int $detailKode Detail kode (primary key)
   * @param int $managerId Manager user ID
   * @return int Affected rows
   */
  public function rejectLayananDetailByKode(int $detailKode, int $managerId): int
  {
    $result = $this->db->table('t_layanan_detil')
      ->where('kode', $detailKode)
      ->where('(status_layanan IS NULL OR status_layanan != 2)')
      ->update([
        'status_layanan' => 2,
        'terima_layanan_by' => $managerId
      ]);

    return $this->db->affectedRows();
  }

  /**
   * Count pending for manager (via r_tim)
   * 
   * @param int|string $kode_layanan Layanan code
   * @param int $userId User ID
   * @return int
   */
  public function countPendingForManager($kode_layanan, int $userId): int
  {
    $count = (int) $this->db->table('t_layanan_detil as d')
      ->join('r_tim rt', 'rt.uji_kode = d.uji_kode', 'inner')
      ->where('d.kode_layanan', $kode_layanan)
      ->where('rt.user_id', $userId)
      ->groupStart()
      ->where('d.status_layanan', 0)
      ->orWhere('d.status_layanan IS NULL', null, false)
      ->groupEnd()
      ->countAllResults(false);

    return $count;
  }

  /**
   * Start database transaction
   */
  public function transStart(): void
  {
    $this->db->transStart();
  }

  /**
   * Complete database transaction
   */
  public function transComplete(): void
  {
    $this->db->transComplete();
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
   * Get affected rows count
   * 
   * @return int
   */
  public function affectedRows(): int
  {
    return $this->db->affectedRows();
  }
}
