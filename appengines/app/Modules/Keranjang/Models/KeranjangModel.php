<?php

namespace Modules\Keranjang\Models;

use CodeIgniter\Database\BaseConnection;
use App\Models\MyModel;

/**
 * KeranjangModel mengonsolidasikan seluruh interaksi database
 * untuk modul Keranjang sehingga controller cukup fokus pada flow.
 */
class KeranjangModel
{
  /** @var BaseConnection */
  protected $db;

  /** @var array */
  protected $config;

  /** @var string */
  protected $tableLayanan;

  /** @var string */
  protected $tableDetail;

  /** @var string */
  protected $tablePengujian;

  /** @var string */
  protected $tablePembayaran;

  /** @var array */
  protected $columns;

  /** @var string */
  protected $tableIdentitasSampel = 't_identitas_sampel';

  /** @var string */
  protected $tableLogSampel = 't_log_sampel';

  public function __construct(array $config)
  {
    $this->db = \Config\Database::connect();
    $this->config = $config;
    $this->tableLayanan = $config['table_layanan'] ?? 't_layanan';
    $this->tableDetail = $config['table_detail'] ?? 't_layanan_detil';
    $this->tablePengujian = $config['table_pengujian'] ?? 'r_layanan_pengujian';
    $this->tablePembayaran = $config['table_pembayaran'] ?? 't_pembayaran';
    $this->columns = $config['columns'] ?? [];
  }

  /**
   * Ambil data user berdasarkan ID.
   */
  public function getUserById(int $userId)
  {
    if ($userId <= 0) {
      return null;
    }

    return $this->db
      ->table('account_users')
      ->where('user_id', $userId)
      ->get()
      ->getRow();
  }

  /**
   * Ambil kategori layanan (distinct kode jenis).
   */
  public function getCategories(): array
  {
    $jenisCol = $this->columns['jenis'] ?? 'kode_jenis';

    $builder = $this->db->table($this->tablePengujian . ' as lp');
    $builder->select('DISTINCT TRIM(LEFT(lp.' . $jenisCol . ', 2)) as kode, j.nama');
    $builder->join('r_jenis j', 'j.kode = TRIM(LEFT(lp.' . $jenisCol . ', 2))', 'left');
    $builder->where('lp.' . $jenisCol . ' IS NOT NULL');
    $builder->where('lp.' . $jenisCol . ' !=', '');
    $builder->orderBy('j.nama', 'ASC');

    $result = $builder->get()->getResult();
    $normalized = [];

    foreach ($result as $row) {
      $kode = isset($row->kode) ? trim((string) $row->kode) : '';
      if ($kode === '') {
        continue;
      }

      $normalized[] = (object) [
        'kode' => $kode,
        'nama' => (isset($row->nama) && trim((string) $row->nama) !== '')
          ? trim((string) $row->nama)
          : $kode
      ];
    }

    return $normalized;
  }

  /**
   * Ambil daftar metode uji.
   */
  public function getMetodeList(): array
  {
    return $this->db->table('r_metode')->get()->getResult();
  }

  /**
   * Ambil detail metode berdasarkan kode.
   */
  public function getMetodeByKode($metodeKode)
  {
    if ($metodeKode === null || $metodeKode === '') {
      return null;
    }

    return $this->db
      ->table('r_metode')
      ->where('metode_kode', $metodeKode)
      ->get()
      ->getRow();
  }

  /**
   * Ambil identity user (ULM, dll) dalam bentuk uppercase string.
   */
  public function getUserIdentity(int $userId): string
  {
    $user = $this->getUserById($userId);
    return $user && isset($user->user_identity)
      ? strtoupper(trim((string) $user->user_identity))
      : '';
  }

  /**
   * Insert data layanan utama dan kembalikan ID auto increment.
   */
  public function insertLayanan(array $data): int
  {
    $builder = $this->db->table($this->tableLayanan);
    $builder->insert($data);
    return (int) $this->db->insertID();
  }

  /**
   * Insert data pembayaran.
   */
  public function insertPembayaran(array $data): bool
  {
    return (bool) $this->db->table($this->tablePembayaran)->insert($data);
  }

  /**
   * Insert detail layanan.
   */
  public function insertDetailLayanan(array $data): bool
  {
    return (bool) $this->db->table($this->tableDetail)->insert($data);
  }

  /**
   * Dapatkan kode jenis dari tabel pengujian berdasarkan kode layanan.
   */
  public function resolveJenisKode(?string $layananKode): ?string
  {
    if (!$layananKode) {
      return null;
    }

    $kodeColumn = $this->columns['kode'] ?? 'kode';

    $row = $this->db
      ->table($this->tablePengujian)
      ->select('kode_jenis')
      ->where($kodeColumn, $layananKode)
      ->get()
      ->getRow();

    if (!$row || !isset($row->kode_jenis)) {
      return null;
    }

    $trimmed = trim((string) $row->kode_jenis);
    return $trimmed === '' ? null : $trimmed;
  }

  /**
   * Ambil daftar layanan pengujian sesuai filter kategori.
   */
  public function fetchPengujianList(?string $jenKodeFilter = null): array
  {
    $cols = $this->columns;
    $builder = $this->db->table($this->tablePengujian . ' as lp');

    $builder->select(
      'lp.' . ($cols['kode'] ?? 'kode') . ' as kode,' .
      ' lp.' . ($cols['biaya'] ?? 'biaya') . ' as biaya,' .
      ' lp.' . ($cols['diskon'] ?? 'diskon') . ' as diskon,' .
      ' lp.' . ($cols['nama'] ?? 'nama_layanan') . ' as nama_layanan,' .
      ' lp.' . ($cols['jenis'] ?? 'kode_jenis') . ' as kode_jenis,' .
      ' lp.' . ($cols['satuan'] ?? 'satuan') . ' as satuan,' .
      ' p.nama as parameter_nama,' .
      ' a.nama as alat_nama,' .
      ' j.nama as jenis_nama'
    );

    $builder->join('r_parameter p', 'p.kode = lp.' . ($cols['parameter'] ?? 'kode_parameter'), 'left');
    $builder->join('r_alat a', 'a.kode = lp.' . ($cols['alat'] ?? 'kode_alat'), 'left');
    $builder->join('r_jenis j', 'j.kode = lp.' . ($cols['jenis'] ?? 'kode_jenis'), 'left');

    if ($jenKodeFilter !== null && $jenKodeFilter !== '') {
      $builder->where('TRIM(LEFT(lp.' . ($cols['jenis'] ?? 'kode_jenis') . ', 2))', $jenKodeFilter);
    }

    $builder->orderBy('lp.' . ($cols['kode'] ?? 'kode'), 'ASC');

    return $builder->get()->getResult();
  }

  /**
   * Helper pencarian berbasis string pada hasil fetchPengujianList.
   */
  public function filterPengujianBySearch(array $rows, string $searchTerm): array
  {
    if ($searchTerm === '') {
      return $rows;
    }

    $lowerSearch = mb_strtolower($searchTerm, 'UTF-8');
    $filtered = [];

    foreach ($rows as $row) {
      $fields = [
        isset($row->nama) ? mb_strtolower($row->nama, 'UTF-8') : '',
        isset($row->nama) ? mb_strtolower($row->nama, 'UTF-8') : '',
        isset($row->nama_layanan) ? mb_strtolower($row->nama_layanan, 'UTF-8') : '',
        isset($row->nama) ? mb_strtolower($row->nama, 'UTF-8') : '',
        isset($row->kode) ? mb_strtolower((string) $row->kode, 'UTF-8') : ''
      ];

      foreach ($fields as $field) {
        if ($field !== '' && mb_strpos($field, $lowerSearch, 0, 'UTF-8') !== false) {
          $filtered[] = $row;
          break;
        }
      }
    }

    return $filtered;
  }

  /**
   * Simpan identitas sampel.
   */
  public function saveIdentitasSampel(array $data): bool
  {
    return (bool) $this->db->table($this->tableIdentitasSampel)->insert($data);
  }

  /**
   * Seed log sampel awal.
   */
  public function seedLogSampel(int $kode_layanan, ?string $timestamp = null): bool
  {
    $payload = [
      'kode_layanan' => $kode_layanan,
      'pengecekan' => $timestamp ?? date('Y-m-d H:i:s'),
    ];

    return (bool) $this->db->table($this->tableLogSampel)->insert($payload);
  }

  /**
   * Update kolom surat_pertanyaan di t_layanan.
   */
  public function updateSuratPengantar(int $kode_layanan, string $fileName): bool
  {
    return (bool) $this->db->table($this->tableLayanan)
      ->where('kode_layanan', $kode_layanan)
      ->update(['surat_pertanyaan' => $fileName]);
  }
}
