<?php

namespace Modules\KeranjangAdmin\Models;

use CodeIgniter\Model;

/**
 * KeranjangAdminModel
 * Model untuk operasi database khusus KeranjangAdmin module
 */
class KeranjangAdminModel extends Model
{
  protected $db;

  public function __construct()
  {
    parent::__construct();
    $this->db = \Config\Database::connect();
  }

  /**
   * Get database instance untuk transaction handling
   * @return \CodeIgniter\Database\BaseConnection
   */
  public function getDb()
  {
    return $this->db;
  }

  /**
   * Ambil daftar kategori untuk keranjang berdasarkan tabel dan kolom jenis
   * @param string $tablePengujian Nama tabel pengujian
   * @param string $jenisCol Nama kolom jenis
   * @return array
   */
  public function getKategoriByTable(string $tablePengujian, string $jenisCol): array
  {
    $builder = $this->db->table($tablePengujian . ' as lp');

    $builder->select("
            DISTINCT TRIM(LEFT(lp.{$jenisCol}, 2)) as kode,
            j.nama
        ");
    $builder->join('r_jenis j', "j.kode = TRIM(LEFT(lp.{$jenisCol}, 2))", 'left');
    $builder->where("lp.{$jenisCol} IS NOT NULL");
    $builder->where("lp.{$jenisCol} !=", '');
    $builder->orderBy('j.nama', 'ASC');

    return $builder->get()->getResult();
  }

  /**
   * Ambil kode_jenis dari tabel pengujian berdasarkan kode
   * @param string $tablePengujian Nama tabel pengujian
   * @param string $kode Kode item
   * @return string|null
   */
  public function getKodeJenisByKode(string $tablePengujian, string $kode): ?string
  {
    $result = $this->db->table($tablePengujian)
      ->select('kode_jenis')
      ->where('kode', $kode)
      ->get()
      ->getRow();

    if ($result && $result->kode_jenis !== null) {
      $kode = trim((string) $result->kode_jenis);
      return $kode !== '' ? $kode : null;
    }

    return null;
  }

  /**
   * Ambil diskon dari r_layanan_pengujian berdasarkan kode
   * @param string $kode Kode item
   * @return float
   */
  public function getDiskonByKode(string $kode): float
  {
    $row = $this->db->table('r_layanan_pengujian')
      ->select('diskon')
      ->where('kode', $kode)
      ->get()
      ->getRow();

    return ($row && isset($row->diskon)) ? (float) $row->diskon : 0.0;
  }

  /**
   * Ambil data list layanan pengujian untuk keranjang
   * @param string $tablePengujian Nama tabel pengujian
   * @param array $cols Column mappings
   * @param string $jenKodeFilter Filter kategori (opsional)
   * @return array
   */
  public function getLayananPengujianList(string $tablePengujian, array $cols, string $jenKodeFilter = ''): array
  {
    $builder = $this->db->table($tablePengujian . ' as lp');

    $builder->select("
            lp.{$cols['kode']} as kode,
            lp.{$cols['biaya']} as biaya,
            lp.{$cols['diskon']} as diskon,
            lp.{$cols['nama']} as nama_layanan,
            lp.{$cols['jenis']} as kode_jenis,
            lp.{$cols['satuan']} as satuan,
            p.nama as parameter_nama,
            a.nama as alat_nama,
            j.nama as jenis_nama
        ");

    // Joins
    $builder->join('r_parameter p', 'p.kode = lp.' . $cols['parameter'], 'left');
    $builder->join('r_alat a', 'a.kode = lp.' . $cols['alat'], 'left');
    $builder->join('r_jenis j', 'j.kode = lp.' . $cols['jenis'], 'left');

    // Filter kategori
    if ($jenKodeFilter !== '') {
      $builder->where("TRIM(LEFT(lp.{$cols['jenis']}, 2))", $jenKodeFilter);
    }

    $builder->orderBy("lp.{$cols['kode']}", 'ASC');

    return $builder->get()->getResult();
  }
}
