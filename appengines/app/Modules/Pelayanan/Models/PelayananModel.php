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
  protected $layananTable = 'simlab_t_layanan';

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
    $this->userModel = new MyModel('simlab_account_users');
    $this->kuesionerModel = new MyModel('simlab_t_kuesioner');
    $this->jawabanModel = new MyModel('simlab_t_kuesioner_jawaban');
    $this->sampleModel = new MyModel('t_identitas_sampel');
  }

  public function getUserById(int $userId)
  {
    return $this->userModel->getDataById('user_id', $userId);
  }

  public function getCategories(): array
  {
    return $this->db->table('r_layanan_pengujian as lp')
      ->select('DISTINCT TRIM(LEFT(lp.kode_jenis, 2)) as jenKode, j.jenNama')
      ->join('simlab_r_jenis j', 'j.jenKode = TRIM(LEFT(lp.kode_jenis, 2))', 'left')
      ->where('lp.kode_jenis IS NOT NULL')
      ->where('lp.kode_jenis !=', '')
      ->orderBy('j.jenNama', 'ASC')
      ->get()->getResult();
  }

  public function getTrackingDetails($lnKode): array
  {
    return $this->db->table('t_layanan_detil as d')
      ->select('d.uji_kode, d.kode_layanan, d.nama_layanan as detParameter, d.kode_jenis, SUM(d.jumlah) as jumlah, SUM(d.biaya) as biaya, MAX(d.status_layanan) as status_layanan')
      ->where('d.kode_layanan', $lnKode)
      ->groupBy('d.uji_kode, d.kode_layanan, d.nama_layanan, d.kode_jenis')
      ->get()->getResult();
  }

  public function getLogSampel($lnKode)
  {
    return $this->db->table('t_log_sampel')->where('kode_layanan', $lnKode)->get()->getRow();
  }

  public function getUserLayananList(int $userId): array
  {
    return $this->db->table($this->layananTable . ' as t')
      ->select('t.lnKode, t.user_id, t.lnAccEmail, t.lnNoTransaksi, t.lnTgl, t.lnStatus, t.kuisioner')
      ->join('t_layanan_detil d', 'd.kode_layanan = t.lnKode', 'inner')
      ->where('t.user_id', $userId)
      ->where('d.kode_jenis', 'A')
      ->groupBy('t.lnKode, t.user_id, t.lnAccEmail, t.lnNoTransaksi, t.lnTgl, t.lnStatus, t.kuisioner')
      ->orderBy('t.lnTgl', 'DESC')
      ->get()->getResult();
  }

  public function getLatestPaymentMap(array $lnKodes): array
  {
    if (empty($lnKodes)) {
      return [];
    }

    $rows = $this->db->table('t_pembayaran')
      ->select('bayarLnKode, bayarStatus, bayarInvoiceNo, MAX(bayarKode) AS lastKode')
      ->whereIn('bayarLnKode', $lnKodes)
      ->groupBy('bayarLnKode, bayarStatus, bayarInvoiceNo')
      ->orderBy('lastKode', 'DESC')
      ->get()->getResult();

    $map = [];
    foreach ($rows as $row) {
      $ln = (int) $row->bayarLnKode;
      if (!isset($map[$ln])) {
        $map[$ln] = [
          'status' => (int) $row->bayarStatus,
          'inv' => $row->bayarInvoiceNo ?? null,
        ];
      }
    }

    return $map;
  }

  public function getDetailItems($lnKode): array
  {
    return $this->db->table('t_layanan_detil as d')
      ->select('d.uji_kode, d.kode_layanan, d.nama_layanan, d.kode_jenis, d.metode_pengujian, m.nama AS metode_nama, SUM(d.jumlah) AS jumlah, SUM(d.biaya) AS detBiaya, MAX(d.status_layanan) AS detStatusGroup')
      ->join('r_metode m', 'm.metode_kode = d.metode_pengujian', 'left')
      ->where('d.kode_layanan', $lnKode)
      ->groupBy('d.uji_kode, d.kode_layanan, d.nama_layanan, d.kode_jenis, d.metode_pengujian, m.nama')
      ->get()->getResult();
  }

  public function getSampleIdentityRow($lnKode)
  {
    return $this->sampleModel->getWhere(['kode_layanan' => $lnKode])->getRow();
  }

  public function getLhuFiles($lnKode): array
  {
    return $this->db->table('t_files_lhu AS lhu')
      ->select('lhu.file_id, lhu.file, lhu.tanggal_terbit, lhu.upload_by, '
        . 'acc.Telepon AS admin_phone, acc.nama AS admin_full_name, acc.username AS admin_username, '
        . 'au.user_telpon AS admin_phone_alt, au.user_name AS admin_name_alt')
      ->join('simlab_account acc', 'acc.user_id = lhu.upload_by', 'left')
      ->join('simlab_account_users au', 'au.user_id = lhu.upload_by', 'left')
      ->where('lhu.kode', $lnKode)
      ->orderBy('CASE WHEN lhu.tanggal_terbit IS NULL THEN 1 ELSE 0 END', 'ASC', false)
      ->orderBy('lhu.tanggal_terbit', 'ASC')
      ->orderBy('lhu.file_id', 'ASC')
      ->get()->getResult();
  }

  public function getLatestLhuFile($lnKode)
  {
    return $this->db->table('t_files_lhu as lhu')
      ->select('lhu.file_id, lhu.kode, lhu.file, lhu.upload_by, acc.user_name as uploader_name')
      ->join('simlab_account_users as acc', 'acc.user_id = lhu.upload_by', 'left')
      ->where('lhu.kode', $lnKode)
      ->orderBy('lhu.file_id', 'DESC')
      ->limit(1)
      ->get()->getRow();
  }

  public function getKuesionerPertanyaan(): array
  {
    return $this->kuesionerModel->getAllDataWithJoinWhereOrder([], [], ['kuesioner_id' => 'ASC']);
  }

  public function getExistingJawaban($pertanyaanId, $userId, $lnKode)
  {
    return $this->jawabanModel
      ->getWhere([
        'id_pertanyaan' => $pertanyaanId,
        'user_id' => $userId,
        'kode_layanan' => $lnKode,
      ])->getRow();
  }

  public function saveJawaban(array $data, $existingId = null)
  {
    if ($existingId) {
      return $this->jawabanModel->updateData($data, 'id_jawaban', $existingId);
    }

    return $this->jawabanModel->insertData($data);
  }

  public function updateKuisionerFlag($lnKode)
  {
    return $this->updateData(['kuisioner' => 1], 'lnKode', $lnKode);
  }

  public function getLayananByKode($lnKode)
  {
    return $this->getDataById('lnKode', $lnKode);
  }
}
