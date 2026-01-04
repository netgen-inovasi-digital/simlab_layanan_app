<?php

namespace Modules\FormulirAdmin\Models;

use CodeIgniter\Model;

/**
 * FormulirAdminModel
 * Model untuk operasi database khusus FormulirAdmin module
 */
class FormulirAdminModel extends Model
{
  protected $db;

  public function __construct()
  {
    parent::__construct();
    $this->db = \Config\Database::connect();
  }

  /**
   * Ambil daftar kategori dari r_layanan_pengujian dengan join ke r_jenis
   * @return array
   */
  public function getKategoriLayanan(): array
  {
    $builder = $this->db->table('r_layanan_pengujian as lp');
    $builder->select('DISTINCT TRIM(LEFT(lp.kode_jenis, 2)) as kode, j.nama');
    $builder->join('r_jenis j', 'j.kode = TRIM(LEFT(lp.kode_jenis, 2))', 'left');
    $builder->where('lp.kode_jenis IS NOT NULL');
    $builder->where('lp.kode_jenis !=', '');
    $builder->orderBy('j.nama', 'ASC');

    return $builder->get()->getResult();
  }

  /**
   * Ambil pembayaran terakhir per kode_layanan
   * @param array $lnKodes Array of kode_layanan
   * @return array Map [kode_layanan => ['status' => int, 'inv' => string, 'invoiceFile' => string]]
   */
  public function getPembayaranMapByLnKodes(array $lnKodes): array
  {
    if (empty($lnKodes)) {
      return [];
    }

    $payRows = $this->db->table('t_pembayaran')
      ->select('kode_layanan, status_bayar, no_invoice, invoice_file, MAX(kode_bayar) AS lastKode')
      ->whereIn('kode_layanan', $lnKodes)
      ->groupBy('kode_layanan, status_bayar, no_invoice, invoice_file')
      ->orderBy('lastKode', 'DESC')
      ->get()->getResult();

    $payMap = [];
    foreach ($payRows as $p) {
      $ln = (int) $p->kode_layanan;
      if (!isset($payMap[$ln])) {
        $payMap[$ln] = [
          'status' => (int) $p->status_bayar,
          'inv' => $p->no_invoice ?? null,
          'invoiceFile' => $p->invoice_file ?? null,
        ];
      }
    }

    return $payMap;
  }

  /**
   * Ambil log sampel per kode_layanan
   * @param array $lnKodes Array of kode_layanan
   * @return array Map [kode_layanan => pengecekan]
   */
  public function getLogSampelMapByLnKodes(array $lnKodes): array
  {
    if (empty($lnKodes)) {
      return [];
    }

    $logRows = $this->db->table('t_log_sampel')
      ->select('kode_layanan, pengecekan')
      ->whereIn('kode_layanan', $lnKodes)
      ->get()->getResult();

    $logMap = [];
    foreach ($logRows as $log) {
      $logMap[(int) $log->kode_layanan] = $log->pengecekan;
    }

    return $logMap;
  }

  /**
   * Cari user_id dari layanan berdasarkan no_invoice
   * @param string $table Nama tabel layanan
   * @param string $no_invoiceNomor transaksi
   * @return int|null
   */
  public function findUserIdByNoTransaksi(string $table, string $no_invoice): ?int
  {
    $result = $this->db->table($table)
      ->select('user_id')
      ->where('no_invoice', $no_invoice)
      ->where('user_id IS NOT NULL', null, false)
      ->get()->getResult();

    if (!empty($result)) {
      return (int) $result[0]->user_id;
    }

    return null;
  }

  /**
   * Ambil detail layanan dengan group untuk detail list
   * @param int $kode kode_layanan
   * @return array
   */
  public function getDetailLayananGrouped(int $kode): array
  {
    $builder = $this->db->table('t_layanan_detil as d');

    $builder->select("
            d.uji_kode,
            d.kode_layanan,
            d.nama_layanan,
            d.kode_jenis,
            d.metode_pengujian,
            m.nama AS metode_nama,
            GROUP_CONCAT(DISTINCT d.catatan_manajer SEPARATOR ' | ') AS detKetLn,
            SUM(d.jumlah) AS jumlah,
            SUM(d.biaya) AS detBiaya,
            MAX(d.status_layanan) AS detStatusGroup,
            GROUP_CONCAT(DISTINCT acc.nama SEPARATOR ' | ') AS accUsernames
        ");

    $builder->join('account acc', 'acc.user_id = d.terima_layanan_by', 'left');
    $builder->join('r_metode m', 'm.metode_kode = d.metode_pengujian', 'left');
    $builder->where('d.kode_layanan', $kode);
    $builder->groupBy('d.uji_kode, d.kode_layanan, d.nama_layanan, d.kode_jenis, d.metode_pengujian, m.nama');

    return $builder->get()->getResult();
  }

  /**
   * Ambil data layanan header berdasarkan kode_layanan untuk WhatsApp button
   * @param string $table Nama tabel
   * @param string $idField Nama field ID
   * @param int $kode_layanan
   * @return object|null
   */
  public function getLayananHeaderById(string $table, string $idField, int $kode_layanan): ?object
  {
    return $this->db->table($table)
      ->select('user_id, user_email')
      ->where($idField, $kode_layanan)
      ->get()
      ->getRow();
  }

  /**
   * Cek status pembayaran terakhir
   * @param int $kode_layanan
   * @return object|null
   */
  public function getLastPaymentStatus(int $kode_layanan): ?object
  {
    return $this->db->table('t_pembayaran')
      ->select('status_bayar')
      ->where('kode_layanan', $kode_layanan)
      ->orderBy('kode_bayar', 'DESC')
      ->limit(1)
      ->get()->getRow();
  }
}
