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
    $builder = $this->db->table('simlab_t_layanan as l');
    $builder->select('l.lnKode, l.lnNoTransaksi, l.lnTgl, l.user_id, l.lnAccEmail, l.jumlah_kaji_ulang');
    $builder->select('GROUP_CONCAT(DISTINCT d.nama_layanan ORDER BY d.nama_layanan SEPARATOR ", ") as layanan_nama');
    $builder->select('u.user_name, u.user_email, u.user_identity, u.user_instansi');
    $builder->select('COUNT(DISTINCT jawab.id_jawaban) as total_jawaban');
    $builder->join('simlab_t_kuesioner_jawaban as jawab', 'jawab.kode_layanan = l.lnKode', 'inner');
    $builder->join('t_layanan_detil as d', 'd.kode_layanan = l.lnKode', 'left');
    $builder->join('simlab_account_users as u', 'u.user_id = l.user_id', 'left');
    $builder->where('l.kuisioner', 1);
    $builder->groupBy('l.lnKode, l.lnNoTransaksi, l.lnTgl, l.user_id, l.lnAccEmail, l.jumlah_kaji_ulang, u.user_name, u.user_email, u.user_identity, u.user_instansi');
    $builder->orderBy('l.lnTgl', 'DESC');

    return $builder->get()->getResult();
  }

  /**
   * Ambil header layanan untuk detail jawaban.
   */
  public function getResponseHeader($lnKode)
  {
    return $this->db->table('simlab_t_layanan as l')
      ->select('l.lnKode, l.lnNoTransaksi, l.lnTgl, u.user_name, u.user_identity, u.user_instansi')
      ->join('simlab_account_users as u', 'u.user_id = l.user_id', 'left')
      ->where('l.lnKode', $lnKode)
      ->get()
      ->getRow();
  }

  /**
   * Ambil daftar pertanyaan dan jawaban pada satu layanan.
   */
  public function getQuestionResponses($lnKode): array
  {
    $builder = $this->db->table('simlab_t_kuesioner as q');
    $builder->select('q.kuesioner_id, q.pertanyaan_teks, q.pertanyaan_tipe, q.pertanyaan_wajib, jawab.jawaban, jawab.created_at');
    $builder->join(
      'simlab_t_kuesioner_jawaban as jawab',
      'jawab.id_pertanyaan = q.kuesioner_id AND jawab.kode_layanan = ' . $this->db->escape($lnKode),
      'left'
    );
    $builder->orderBy('q.kuesioner_id', 'ASC');

    return $builder->get()->getResult();
  }
}
