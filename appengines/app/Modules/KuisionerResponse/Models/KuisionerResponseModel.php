<?php

namespace Modules\KuisionerResponse\Models;

use CodeIgniter\Model;

class KuisionerResponseModel extends Model
{
  protected $db;

  public function __construct()
  {
    parent::__construct();
    $this->db = \Config\Database::connect();
  }

  /**
   * Ambil daftar layanan yang sudah mengisi kuisioner beserta ringkasan metadata-nya.
   */
  public function getResponseList(): array
  {
    $builder = $this->db->table('t_layanan as l');
    $builder->select('l.kode_layanan, l.no_invoice, l.tanggal_checkout, l.user_id, l.user_email, l.jumlah_kaji_ulang');
    $builder->select('GROUP_CONCAT(DISTINCT d.nama_layanan ORDER BY d.nama_layanan SEPARATOR ", ") as layanan_nama');
    $builder->select('u.user_name, u.user_email, u.user_identity, u.user_instansi');
    $builder->select('COUNT(DISTINCT jawab.id_jawaban) as total_jawaban');
    $builder->join('t_kuesioner_jawaban as jawab', 'jawab.kode_layanan = l.kode_layanan', 'inner');
    $builder->join('t_layanan_detil as d', 'd.kode_layanan = l.kode_layanan', 'left');
    $builder->join('account_users as u', 'u.user_id = l.user_id', 'left');
    $builder->where('l.kuisioner', 1);
    $builder->groupBy('l.kode_layanan, l.no_invoice, l.tanggal_checkout, l.user_id, l.user_email, l.jumlah_kaji_ulang, u.user_name, u.user_email, u.user_identity, u.user_instansi');
    $builder->orderBy('l.tanggal_checkout', 'DESC');

    return $builder->get()->getResult();
  }

  /**
   * Ambil header layanan untuk detail jawaban.
   */
  public function getResponseHeader($kode_layanan)
  {
    return $this->db->table('t_layanan as l')
      ->select('l.kode_layanan, l.no_invoice, l.tanggal_checkout, u.user_name, u.user_identity, u.user_instansi')
      ->join('account_users as u', 'u.user_id = l.user_id', 'left')
      ->where('l.kode_layanan', $kode_layanan)
      ->get()
      ->getRow();
  }

  /**
   * Ambil daftar pertanyaan dan jawaban pada satu layanan.
   */
  public function getQuestionResponses($kode_layanan): array
  {
    $builder = $this->db->table('t_kuesioner as q');
    $builder->select('q.kuesioner_id, q.pertanyaan_teks, q.pertanyaan_tipe, q.pertanyaan_wajib, jawab.jawaban, jawab.created_at');
    $builder->join(
      't_kuesioner_jawaban as jawab',
      'jawab.id_pertanyaan = q.kuesioner_id AND jawab.user_id IN (
        SELECT user_id FROM t_layanan WHERE kode_layanan = ' . $this->db->escape($kode_layanan) . '
      )',
      'left'
    );
    $builder->orderBy('q.kuesioner_id', 'ASC');

    return $builder->get()->getResult();
  }
}
