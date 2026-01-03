<?php

namespace Modules\Rekap\Models;

use CodeIgniter\Model;

class RekapModel extends Model
{
  protected $db;
  protected $tablePembayaran = 't_pembayaran';
  protected $tableLayanan = 't_layanan';
  protected $tableDetil = 't_layanan_detil';
  protected $tableJenis = 'simlab_r_jenis';
  protected $tableRLayanan = 'r_layanan_pengujian';
  protected $tableKolomKeuangan = 'simlab_r_kolom_keuangan_detail';

  public function __construct()
  {
    parent::__construct();
    $this->db = \Config\Database::connect();
  }

  public function getJenisLayanan(?string $kode = null): array
  {
    $builder = $this->db->table($this->tableJenis)->orderBy('jenKode', 'ASC');

    if (!empty($kode) && $kode !== 'semua') {
      $builder->where('jenKode', $kode);
    }

    return $builder->get()->getResult();
  }

  public function getJenisLayananOptions(): array
  {
    return $this->getJenisLayanan();
  }

  public function getTotalsLookup(?string $tanggalAwal, ?string $tanggalAkhir): array
  {
    $builder = $this->db->table($this->tableDetil . ' d');
    $builder->select("
            d.kode_jenis,
            SUM(CASE WHEN u.user_identity = 'ULM' THEN (d.biaya * d.jumlah) ELSE 0 END) AS total_ulm,
            SUM(CASE WHEN u.user_identity != 'ULM' THEN (d.biaya * d.jumlah) ELSE 0 END) AS total_non_ulm
        ");
    $builder->join($this->tableLayanan . ' l', 'd.kode_layanan = l.kode_layanan', 'inner');
    $builder->join('simlab_account_users u', 'l.user_id = u.user_id', 'inner');
    $builder->join($this->tablePembayaran . ' p', 'l.kode_layanan = p.kode_layanan', 'inner');

    if (!empty($tanggalAwal) && !empty($tanggalAkhir)) {
      $builder->where('p.tanggal_invoice >=', $tanggalAwal);
      $builder->where('p.tanggal_invoice <=', $tanggalAkhir);
    }

    $builder->groupBy('d.kode_jenis');
    $results = $builder->get()->getResult();

    $lookup = [];
    foreach ($results as $row) {
      $lookup[$row->kode_jenis] = [
        'total_ulm' => $row->total_ulm,
        'total_non_ulm' => $row->total_non_ulm,
      ];
    }

    return $lookup;
  }

  public function getKolomKeuanganLookup(): array
  {
    $builder = $this->db->table($this->tableKolomKeuangan)
      ->orderBy('kdJenKode', 'ASC')
      ->orderBy('kdKode', 'ASC');

    $allKolom = $builder->get()->getResult();

    $lookup = [];
    foreach ($allKolom as $kolom) {
      if (!isset($lookup[$kolom->kdJenKode])) {
        $lookup[$kolom->kdJenKode] = [];
      }
      $lookup[$kolom->kdJenKode][] = $kolom;
    }

    return $lookup;
  }

  public function getRevenueData(?string $jenisLayanan, string $tanggalAwal, string $tanggalAkhir): array
  {
    $dateCondition = 'l.kode_layanan = p.kode_layanan';
    if (!empty($tanggalAwal) && !empty($tanggalAkhir)) {
      $dateCondition .= ' AND p.tanggal_invoice >= ' . $this->db->escape($tanggalAwal);
      $dateCondition .= ' AND p.tanggal_invoice <= ' . $this->db->escape($tanggalAkhir);
    }

    $builder = $this->db->table($this->tableRLayanan . ' r');
    $builder->select("
            r.nama_layanan,
            r.kode_jenis,
            r.kode,
            COALESCE(SUM(CASE WHEN p.kode_bayar IS NOT NULL AND u.user_identity = 'ULM' THEN (d.biaya * d.jumlah) ELSE 0 END), 0) AS total_ulm,
            COALESCE(SUM(CASE WHEN p.kode_bayar IS NOT NULL AND u.user_identity != 'ULM' THEN (d.biaya * d.jumlah) ELSE 0 END), 0) AS total_non_ulm
        ");
    $builder->join($this->tableDetil . ' d', 'r.kode = d.uji_kode', 'left');
    $builder->join($this->tableLayanan . ' l', 'd.kode_layanan = l.kode_layanan', 'left');
    $builder->join('simlab_account_users u', 'l.user_id = u.user_id', 'left');
    $builder->join($this->tablePembayaran . ' p', $dateCondition, 'left');

    if (!empty($jenisLayanan) && $jenisLayanan !== 'semua') {
      $builder->where('r.kode_jenis', $jenisLayanan);
    }

    $builder->groupBy('r.kode, r.nama_layanan, r.kode_jenis');
    $builder->orderBy('r.kode_jenis', 'ASC');
    $builder->orderBy('r.nama_layanan', 'ASC');

    return $builder->get()->getResult();
  }
}
