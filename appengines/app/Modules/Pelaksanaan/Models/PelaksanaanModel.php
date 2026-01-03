<?php

namespace Modules\Pelaksanaan\Models;

use App\Models\MyModel;

class PelaksanaanModel extends MyModel
{
  /** @var string */
  protected $layananTable = 't_layanan';

  /** @var \CodeIgniter\Database\BaseConnection */
  protected $db;

  /** @var MyModel */
  protected $userModel;

  /** @var MyModel */
  protected $logSampelModel;

  public function __construct()
  {
    parent::__construct($this->layananTable);
    $this->db = \Config\Database::connect();
    $this->userModel = new MyModel('account_users');
    $this->logSampelModel = new MyModel('t_log_sampel');
  }

  public function getUserById(int $userId)
  {
    return $this->userModel->getDataById('user_id', $userId);
  }

  public function getAllLayananOrdered(): array
  {
    return $this->getAllDataWithOrder(['tanggal_checkout' => 'DESC']);
  }

  /**
   * @return array<string,object>
   */
  public function getUserMapByIds(array $userIds): array
  {
    $userIds = array_values(array_unique(array_filter($userIds, static fn($v) => $v !== null && $v !== '')));
    if (empty($userIds)) {
      return [];
    }

    $rows = $this->userModel->getAllDataWithJoinWhereOrder(
      [],
      ['user_id' => $userIds],
      [],
      'user_id, user_name, user_identity, user_email',
      'left'
    );

    $map = [];
    foreach ($rows as $row) {
      $map[$row->user_id] = $row;
    }

    return $map;
  }

  public function getAcceptedDetailRowsForLayanan($kode_layanan): array
  {
    $detailModel = new MyModel('t_layanan_detil as d');

    $select = '
                        d.kode, d.uji_kode, d.nama_layanan, d.jumlah,
                        d.files, d.status_layanan,
                        lhus.file_lhus,
                        lhus.catatan as ket_lhus,
                        up_lhus.username AS upload_lhus_by,
                        acc_lhus.username AS acc_lhus_by,
                        lhu.file AS file_lhu,
                        up_lhu.user_name AS upload_lhu_by
                ';

    $joins = [
      '(SELECT lhus1.* FROM t_files_lhus lhus1 
                            INNER JOIN (
                                SELECT kode, MAX(file_id) as max_file_id 
                                FROM t_files_lhus 
                                GROUP BY kode
                            ) lhus2 ON lhus1.kode = lhus2.kode AND lhus1.file_id = lhus2.max_file_id
                        ) lhus' => 'lhus.kode = d.kode',
      'account up_lhus' => 'up_lhus.user_id = lhus.upload_by',
      'account acc_lhus' => 'acc_lhus.user_id = lhus.validasi_by',
      't_files_lhu lhu' => 'lhu.kode = d.kode_layanan',
      'account_users up_lhu' => 'up_lhu.user_id = lhu.upload_by',
    ];

    return $detailModel->getAllDataWithJoinWhereOrder(
      $joins,
      ['d.kode_layanan' => $kode_layanan, 'd.status_layanan' => 1],
      ['d.kode' => 'ASC'],
      $select,
      'left'
    );
  }

  public function getLhuHistoryRows($kode_layanan): array
  {
    return $this->db->table('t_files_lhu AS lhu')
      ->select('lhu.file_id, lhu.file, lhu.tanggal_terbit, lhu.upload_by, users.user_name AS uploader_name')
      ->join('account_users AS users', 'users.user_id = lhu.upload_by', 'left')
      ->where('lhu.kode', $kode_layanan)
      ->orderBy('CASE WHEN lhu.tanggal_terbit IS NULL THEN 1 ELSE 0 END', 'ASC', false)
      ->orderBy('lhu.tanggal_terbit', 'ASC')
      ->orderBy('lhu.file_id', 'ASC')
      ->get()->getResult();
  }

  public function getLayananByKode($kode_layanan)
  {
    return $this->getDataById('kode_layanan', $kode_layanan);
  }

  /**
   * @return array{msg:string,oldFile:?string}
   */
  public function saveUploadedLhuFile(string $kode_layanan, string $filename, int $userId, string $tanggalTerbitFormatted, bool $isUjiUlang): array
  {
    $msg = 'File LHU berhasil diunggah.';
    $oldFile = null;

    $fileModel = new MyModel('t_files_lhu');

    if ($isUjiUlang) {
      $fileModel->insertData([
        'kode' => $kode_layanan,
        'file' => $filename,
        'upload_by' => $userId,
        'tanggal_terbit' => $tanggalTerbitFormatted,
      ]);
      return ['msg' => 'File LHU uji ulang berhasil diunggah.', 'oldFile' => null];
    }

    $existingFile = $this->db->table('t_files_lhu')
      ->where('kode', $kode_layanan)
      ->orderBy('file_id', 'DESC')
      ->limit(1)
      ->get()->getRow();

    if ($existingFile) {
      $oldFile = $existingFile->file ?? null;

      $fileModel->updateData(
        [
          'file' => $filename,
          'upload_by' => $userId,
          'tanggal_terbit' => $tanggalTerbitFormatted,
        ],
        'file_id',
        $existingFile->file_id
      );

      $msg = 'File LHU berhasil diperbarui.';
    } else {
      $fileModel->insertData([
        'kode' => $kode_layanan,
        'file' => $filename,
        'upload_by' => $userId,
        'tanggal_terbit' => $tanggalTerbitFormatted,
      ]);
    }

    return ['msg' => $msg, 'oldFile' => $oldFile];
  }

  public function upsertLogPenerbitanLhu(string $kode_layanan, string $tanggalTerbitFormatted): void
  {
    $logSampel = $this->logSampelModel->getDataById('kode_layanan', $kode_layanan);
    if ($logSampel) {
      $this->logSampelModel->updateData(['penerbitan_lhu' => $tanggalTerbitFormatted], 'kode_layanan', $kode_layanan);
      return;
    }

    $this->logSampelModel->insertData([
      'kode_layanan' => $kode_layanan,
      'penerbitan_lhu' => $tanggalTerbitFormatted,
    ]);
  }

  public function setLayananStatus(string $kode_layanan, int $status): bool
  {
    return (bool) $this->updateData(['status_layanan' => $status], 'kode_layanan', $kode_layanan);
  }

  public function createPengujianUlang(string $kode_layanan, string $catatan): bool
  {
    $this->db->transStart();

    $this->db->table('t_layanan')
      ->where('kode_layanan', $kode_layanan)
      ->set('status_layanan', 1)
      ->set('jumlah_kaji_ulang', 'COALESCE(jumlah_kaji_ulang,0)+1', false)
      ->set('catatan_kaji_ulang', $catatan)
      ->set('kuisioner', 0)
      ->update();

    $this->db->table('t_layanan_detil')
      ->where('kode_layanan', $kode_layanan)
      ->set([
        'status_layanan' => 0,
        'terima_layanan_by' => null,
        'catatan_manajer' => null,
        'files' => null,
      ])
      ->update();

    $this->db->table('t_files_lhus')
      ->where('kode_layanan', $kode_layanan)
      ->set([
        'file_lhus' => null,
        'validasi_by' => null,
        'upload_by' => null,
        'status' => 0,
        'catatan' => null,
      ])
      ->update();

    $logData = [
      'pengecekan' => date('Y-m-d H:i:s'),
      'pengujian' => null,
      'verifikasi_hasil_uji' => null,
      'penerbitan_lhus' => null,
      'verifikasi_lhu' => null,
      'penerbitan_lhu' => null,
    ];

    $logBuilder = $this->db->table('t_log_sampel');
    $logExists = $logBuilder->where('kode_layanan', $kode_layanan)->get()->getRow();

    if ($logExists) {
      $logBuilder
        ->where('kode_layanan', $kode_layanan)
        ->set($logData)
        ->update();
    } else {
      $logBuilder->insert(array_merge(['kode_layanan' => $kode_layanan], $logData));
    }

    $this->db->transComplete();

    return $this->db->transStatus() !== false;
  }

  public function deleteLayanan(string $kode_layanan): bool
  {
    return (bool) $this->deleteData('kode_layanan', $kode_layanan);
  }
}
