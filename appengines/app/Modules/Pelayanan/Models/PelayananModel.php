<?php

namespace Modules\Pelayanan\Models;

use App\Models\MyModel;

/**
 * PelayananModel
 *
 * Menyediakan helper database khusus untuk modul Pelayanan agar controller
 * fokus pada alur presentasi dan validasi input.
 */
class PelayananModel extends MyModel
{
  /** @var string */
  protected $layananTable = 't_layanan';

  /** @var \CodeIgniter\Database\BaseConnection */
  protected $db;

  /** @var MyModel */
  protected $userModel;

  /** @var MyModel */
  protected $kuesionerModel;

  /** @var MyModel */
  protected $jawabanModel;

  /** @var MyModel */
  protected $sampleModel;

  public function __construct()
  {
    parent::__construct($this->layananTable);
    $this->db = \Config\Database::connect();
    $this->userModel = new MyModel('account_users');
    $this->kuesionerModel = new MyModel('t_kuesioner');
    $this->jawabanModel = new MyModel('t_kuesioner_jawaban');
    $this->sampleModel = new MyModel('t_identitas_sampel');
  }

  public function getUserById(int $userId)
  {
    return $this->userModel->getDataById('user_id', $userId);
  }

  public function getCategories(): array
  {
    return $this->db->table('r_layanan_pengujian as lp')
      ->select('DISTINCT TRIM(LEFT(lp.kode_jenis, 2)) as kode, j.nama')
      ->join('r_jenis j', 'j.kode = TRIM(LEFT(lp.kode_jenis, 2))', 'left')
      ->where('lp.kode_jenis IS NOT NULL')
      ->where('lp.kode_jenis !=', '')
      ->orderBy('j.nama', 'ASC')
      ->get()->getResult();
  }

  public function getTrackingDetails($kode_layanan): array
  {
    return $this->db->table('t_layanan_detil as d')
      ->select('d.uji_kode, d.kode_layanan, d.nama_layanan as detParameter, d.kode_jenis, SUM(d.jumlah) as jumlah, SUM(d.biaya) as biaya, MAX(d.status_layanan) as status_layanan')
      ->where('d.kode_layanan', $kode_layanan)
      ->groupBy('d.uji_kode, d.kode_layanan, d.nama_layanan, d.kode_jenis')
      ->get()->getResult();
  }

  public function getLogSampel($kode_layanan)
  {
    return $this->db->table('t_log_sampel')->where('kode_layanan', $kode_layanan)->get()->getRow();
  }

  public function getUserLayananList(int $userId): array
  {
    return $this->db->table($this->layananTable . ' as t')
      ->select('t.kode_layanan, t.user_id, t.user_email, t.no_invoice, t.tanggal_checkout, t.status_layanan, t.kuisioner')
      ->join('t_layanan_detil d', 'd.kode_layanan = t.kode_layanan', 'inner')
      ->where('t.user_id', $userId)
      ->where('d.kode_jenis', 'A')
      ->groupBy('t.kode_layanan, t.user_id, t.user_email, t.no_invoice, t.tanggal_checkout, t.status_layanan, t.kuisioner')
      ->orderBy('t.tanggal_checkout', 'DESC')
      ->get()->getResult();
  }

  public function getLatestPaymentMap(array $lnKodes): array
  {
    if (empty($lnKodes)) {
      return [];
    }

    $rows = $this->db->table('t_pembayaran')
      ->select('kode_layanan, status_bayar, no_invoice, MAX(kode_bayar) AS lastKode')
      ->whereIn('kode_layanan', $lnKodes)
      ->groupBy('kode_layanan, status_bayar, no_invoice')
      ->orderBy('lastKode', 'DESC')
      ->get()->getResult();

    $map = [];
    foreach ($rows as $row) {
      $ln = (int) $row->kode_layanan;
      if (!isset($map[$ln])) {
        $map[$ln] = [
          'status' => (int) $row->status_bayar,
          'inv' => $row->no_invoice ?? null,
        ];
      }
    }

    return $map;
  }

  public function getDetailItems($kode_layanan): array
  {
    return $this->db->table('t_layanan_detil as d')
      ->select('d.uji_kode, d.kode_layanan, d.nama_layanan, d.kode_jenis, d.metode_pengujian, m.nama AS metode_nama, SUM(d.jumlah) AS jumlah, SUM(d.biaya) AS detBiaya, MAX(d.status_layanan) AS detStatusGroup')
      ->join('r_metode m', 'm.metode_kode = d.metode_pengujian', 'left')
      ->where('d.kode_layanan', $kode_layanan)
      ->groupBy('d.uji_kode, d.kode_layanan, d.nama_layanan, d.kode_jenis, d.metode_pengujian, m.nama')
      ->get()->getResult();
  }

  public function getSampleIdentityRow($kode_layanan)
  {
    return $this->sampleModel->getWhere(['kode_layanan' => $kode_layanan])->getRow();
  }

  public function getLhuFiles($kode_layanan): array
  {
    return $this->db->table('t_files_lhu AS lhu')
      ->select('lhu.file_id, lhu.file, lhu.tanggal_terbit, lhu.upload_by, '
        . 'acc.Telepon AS admin_phone, acc.nama AS admin_full_name, acc.username AS admin_username, '
        . 'au.user_telpon AS admin_phone_alt, au.user_name AS admin_name_alt')
      ->join('account acc', 'acc.user_id = lhu.upload_by', 'left')
      ->join('account_users au', 'au.user_id = lhu.upload_by', 'left')
      ->where('lhu.kode', $kode_layanan)
      ->orderBy('CASE WHEN lhu.tanggal_terbit IS NULL THEN 1 ELSE 0 END', 'ASC', false)
      ->orderBy('lhu.tanggal_terbit', 'ASC')
      ->orderBy('lhu.file_id', 'ASC')
      ->get()->getResult();
  }

  public function getLatestLhuFile($kode_layanan)
  {
    return $this->db->table('t_files_lhu as lhu')
      ->select('lhu.file_id, lhu.kode, lhu.file, lhu.upload_by, acc.user_name as uploader_name')
      ->join('account_users as acc', 'acc.user_id = lhu.upload_by', 'left')
      ->where('lhu.kode', $kode_layanan)
      ->orderBy('lhu.file_id', 'DESC')
      ->limit(1)
      ->get()->getRow();
  }

  public function getKuesionerPertanyaan(): array
  {
    return $this->kuesionerModel->getAllDataWithJoinWhereOrder([], [], ['kuesioner_id' => 'ASC']);
  }

  public function getExistingJawaban($pertanyaanId, $userId, $kode_layanan)
  {
    return $this->jawabanModel
      ->getWhere([
        'id_pertanyaan' => $pertanyaanId,
        'user_id' => $userId,
        'kode_layanan' => $kode_layanan,
      ])->getRow();
  }

  public function saveJawaban(array $data, $existingId = null)
  {
    if ($existingId) {
      return $this->jawabanModel->updateData($data, 'id_jawaban', $existingId);
    }

    return $this->jawabanModel->insertData($data);
  }

  public function updateKuisionerFlag($kode_layanan)
  {
    return $this->updateData(['kuisioner' => 1], 'kode_layanan', $kode_layanan);
  }

  public function getLayananByKode($kode_layanan)
  {
    return $this->getDataById('kode_layanan', $kode_layanan);
  }

  /**
   * Update kolom surat_pertanyaan di t_layanan.
   */
  public function updateSuratPengantar(int $kode_layanan, string $fileName): bool
  {
    return (bool) $this->db->table($this->layananTable)
      ->where('kode_layanan', $kode_layanan)
      ->update(['surat_pertanyaan' => $fileName]);
  }
}
