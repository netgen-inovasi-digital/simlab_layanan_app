<?php

namespace Modules\Keranjang\Controllers;

use App\Models\MyModel;

/**
 * Keranjang Controller untuk Layanan SEWA ALAT
 * Extends KeranjangBase untuk reuse common logic
 */
class KeranjangAlat extends KeranjangBase
{
  public function __construct()
  {
    // Set jenis layanan untuk sewa alat
    $this->jenisLayanan = 'alat';

    // Call parent constructor untuk load config
    parent::__construct();
  }

  /**
   * Build row data untuk display di keranjang alat
   * Override dari KeranjangBase
   */
  protected function buildRowData(array $row, int $idx): array
  {
    $response = [];

    $parameter = $row['layanan'] ?? '-';
    $alat = $row['alat'] ?? '-';
    $jumlah = (int) ($row['jumlah'] ?? 0);
    $keterangan = $row['keterangan'] ?? '';
    $diskon = (float) ($row['diskon'] ?? 0);
    $biayaAsli = (float) ($row['biaya_asli'] ?? 0);
    $biayaTotal = (float) ($row['biaya'] ?? 0);

    // Parameter
    $response[] = esc($parameter);

    // Instrumen / Alat / Tempat
    $response[] = esc($alat);

    // Diskon
    $response[] = $diskon > 0 ? $diskon . '%' : '-';

    // Biaya satuan
    if ($diskon > 0) {
      $hargaDiskon = $biayaAsli - ($biayaAsli * ($diskon / 100));
      $biayaTampil = '<span style="color:red;text-decoration:line-through;">Rp '
        . number_format($biayaAsli, 0, ',', '.') . '</span><br>';
      $biayaTampil .= 'Rp ' . number_format($hargaDiskon, 0, ',', '.');
    } else {
      $biayaTampil = 'Rp ' . number_format($biayaAsli, 0, ',', '.');
    }
    $response[] = $biayaTampil;

    // Jumlah
    $response[] = $jumlah;

    // Keterangan
    $response[] = esc($keterangan);

    // Aksi + total hidden
    $hiddenTotal = '<span class="d-none row-total">Rp ' . number_format($biayaTotal, 0, ',', '.') . '</span>';
    $response[] = $this->aksiKeranjang($idx, true) . $hiddenTotal;

    return $response;
  }

  /**
   * Process item data dari POST submit
   * Override dari KeranjangBase
   */
  protected function processItemData(array $post): array
  {
    $detUjiKode = $post['detUjiKode'] ?? null;
    $detAlat = $post['detAlat'] ?? null;
    $detBiaya = isset($post['detBiaya']) ? (float) $post['detBiaya'] : 0;
    $detParameter = $post['detParameter'] ?? null;
    $detDiskon = isset($post['detDiskon']) ? (float) $post['detDiskon'] : 0;
    $detJumlah = isset($post['detJumlah']) ? (int) $post['detJumlah'] : 1;
    $detKeterangan = trim($post['detKeterangan'] ?? '');
    $detNamaLayanan = $post['detNamaLayanan'] ?? null; // nama_layanan dari r_layanan_pengujian

    // Dapatkan diskon yang sebenarnya diterapkan
    $appliedDiskon = $this->getUserDiscount($detDiskon);

    // Hitung biaya
    $jumlah = max(1, $detJumlah);
    $biayaPerItem = max(0, $detBiaya);
    $biayaSetelahDiskon = $biayaPerItem * (1 - ($appliedDiskon / 100));
    $biayaTotalBaru = $biayaSetelahDiskon * $jumlah;

    return [
      'kode' => $detUjiKode,
      'layanan' => $detParameter ?? 'Layanan',
      'alat' => $detAlat ?? '',
      'biaya_asli' => $biayaPerItem,
      'diskon' => $appliedDiskon,
      'jumlah' => $jumlah,
      'keterangan' => $detKeterangan,
      'biaya' => $biayaTotalBaru,
      'nama_layanan' => $detNamaLayanan, // nama_layanan dari r_layanan_pengujian
    ];
  }

  /**
   * Find and update existing item di keranjang
   * Override dari KeranjangBase
   */
  protected function findAndUpdateExistingItem(array &$keranjang, array $itemData): bool
  {
    $found = false;

    foreach ($keranjang as $idx => $item) {
      $sameKode = isset($item['kode']) && (string) $item['kode'] === (string) $itemData['kode'];
      $sameAlat = (isset($item['alat']) ? trim((string) $item['alat']) : '') === trim((string) $itemData['alat']);

      if ($sameKode && $sameAlat) {
        // Tambah jumlah
        $keranjang[$idx]['jumlah'] = (int) ($item['jumlah'] ?? 0) + (int) $itemData['jumlah'];

        // Ganti keterangan (bukan gabung)
        $keranjang[$idx]['keterangan'] = $itemData['keterangan'];

        // Pastikan biaya asli & diskon tetap
        $biayaAsli = isset($item['biaya_asli']) ? (float) $item['biaya_asli'] : (float) $itemData['biaya_asli'];
        $disk = isset($item['diskon']) ? (float) $item['diskon'] : (float) $itemData['diskon'];

        // Hitung ulang total biaya baru
        $jumlahBaru = (int) $keranjang[$idx]['jumlah'];
        $keranjang[$idx]['biaya'] = ($biayaAsli * $jumlahBaru) * (1 - ($disk / 100));
        $keranjang[$idx]['biaya_asli'] = $biayaAsli;
        $keranjang[$idx]['diskon'] = $disk;

        $found = true;
        break;
      }
    }

    return $found;
  }

  /**
   * Save detail layanan ke database
   * Override dari KeranjangBase
   */
  protected function saveDetailLayanan(int $kode_layanan, array $keranjang): void
  {
    $modelDetil = new MyModel($this->tableLayananDetail);
    $db = \Config\Database::connect();

    foreach ($keranjang as $i => $item) {
      // Ambil kode dari keranjang atau fallback ke tabel pengujian supaya sesuai FK
      $jenKodeValue = null;

      if (isset($item['kode'])) {
        $jenKodeValue = trim((string) $item['kode']);
        if ($jenKodeValue === '') {
          $jenKodeValue = null;
        }
      }

      if ($jenKodeValue === null && isset($item['kode']) && $item['kode'] !== '') {
        try {
          $pengujian = $db->table($this->tablePengujian)
            ->select('kode_jenis')
            ->where('kode', $item['kode'])
            ->get()
            ->getRow();

          if ($pengujian && $pengujian->kode_jenis !== null) {
            $jenKodeValue = trim((string) $pengujian->kode_jenis);
            if ($jenKodeValue === '') {
              $jenKodeValue = null;
            }
          }
        } catch (\Throwable $e) {
          // Ignore error
        }
      }

      // Build data detil - sesuai struktur tabel t_layanan_detil
      $detil = [
        'kode_layanan' => $kode_layanan,                    // FK ke t_layanan
        'uji_kode' => $item['kode'] ?? null,      // FK ke r_layanan_pengujian
        'biaya' => $item['biaya'] ?? 0,        // Total biaya item ini
        'jumlah' => $item['jumlah'] ?? 1,       // Jumlah item
        'nama_layanan' => $item['nama_layanan'] ?? null,   // Nama layanan dari r_layanan_pengujian
        'status_layanan' => 0,                          // Status default: 0
        'kode_jenis' => $jenKodeValue,              // Kode jenis (2 char)
        'catatan_manajer' => null,                       // Default null
        'terima_layanan_by' => null,                       // Default null
        'files' => null,                       // Default null
      ];

      // Insert ke tabel detil
      $res = $modelDetil->insertData($detil);
      if (!$res) {
        $error = $db->error();
        throw new \RuntimeException('Gagal simpan detail: ' . ($error['message'] ?? 'Unknown error'));
      }
    }
  }

  /**
   * Override saveMainLayanan untuk include tgl_pelaksanaan dan jenis_layanan = 'alat'
   */
  protected function saveMainLayanan($userRow, float $totalBiaya): int
  {
    $modelLayanan = new MyModel($this->tableLayanan);

    // Ambil tanggal pelaksanaan dari POST
    $tglPelaksanaan = $this->request->getPost('tglPelaksanaan');

    if (empty($tglPelaksanaan)) {
      throw new \RuntimeException('Tanggal pelaksanaan harus diisi!');
    }

    // Validasi format tanggal
    $date = \DateTime::createFromFormat('Y-m-d', $tglPelaksanaan);
    if (!$date || $date->format('Y-m-d') !== $tglPelaksanaan) {
      throw new \RuntimeException('Format tanggal pelaksanaan tidak valid!');
    }

    $dataToInsert = [
      'user_id' => session()->get('id_user'),
      'lnAccEmail' => $userRow->user_email ?? '',
      'tanggal_checkout' => date('Y-m-d H:i:s'),
      'status_layanan' => 1,
      'kuisioner' => 0,
      'tgl_pelaksanaan' => $tglPelaksanaan
    ];

    try {
      $insertLayananId = $modelLayanan->insertData($dataToInsert, true);

      if (!$insertLayananId) {
        $db = \Config\Database::connect();
        $error = $db->error();
        throw new \RuntimeException('Gagal insert ke database: ' . ($error['message'] ?? 'Unknown error'));
      }

      return (int) $insertLayananId;

    } catch (\Exception $e) {
      throw $e;
    }
  }

  /**
   * Get data list layanan alat (override untuk custom query alat)
   */
  public function keranjangDataListLayanan()
  {
    $session = session();
    $user_id = $session->get('id_user');

    // Ambil info user untuk cek user_identity
    $modelUser = new MyModel('account_users');
    $user = $modelUser->getDataById('user_id', $user_id);
    $userIdentity = '';
    if ($user && isset($user->user_identity)) {
      $userIdentity = strtoupper(trim($user->user_identity));
    }

    // Baca parameter filter dari GET request
    $qRaw = trim((string) ($this->request->getGet('q') ?? $this->request->getGet('search') ?? ''));
    $q = $qRaw !== '' ? mb_strtolower($qRaw, 'UTF-8') : '';

    // Baca kode sebagai filter kategori
    $jenKodeFilter = trim((string) ($this->request->getGet('kode') ?? ''));

    // SANITASI: bersihkan kode dari query parameters yang salah
    if ($jenKodeFilter !== '') {
      $jenKodeFilter = rawurldecode($jenKodeFilter);
      $jenKodeFilter = preg_replace('/[?&].*$/', '', $jenKodeFilter);
      if (strpos($jenKodeFilter, '=') !== false) {
        $parts = explode('=', $jenKodeFilter, 2);
        if ($parts[0] === '') {
          $jenKodeFilter = $parts[1];
        } else {
          $jenKodeFilter = $parts[0];
        }
      }
      $jenKodeFilter = preg_replace('/[^A-Za-z0-9]/', '', $jenKodeFilter);
      $jenKodeFilter = substr($jenKodeFilter, 0, 2);
      $jenKodeFilter = trim($jenKodeFilter);
    }

    try {
      // Query menggunakan Query Builder
      $db = \Config\Database::connect();
      $builder = $db->table($this->tablePengujian . ' as lp');

      // Gunakan config columns mapping
      $cols = $this->config['columns'];

      $builder->select("
                lp.{$cols['kode']} as kode,
                lp.{$cols['biaya']} as biaya,
                lp.{$cols['diskon']} as diskon,
                lp.{$cols['nama']} as nama_layanan,
                lp.{$cols['jenis']} as kode_jenis,
                lp.{$cols['satuan']} as satuan,
                p.nama,
                a.nama,
                j.nama
            ");

      // Joins dari config
      $builder->join('r_parameter p', 'p.kode = lp.' . $cols['parameter'], 'left');
      $builder->join('r_alat a', 'a.kode = lp.' . $cols['alat'], 'left');
      $builder->join('r_jenis j', 'j.kode = lp.' . $cols['jenis'], 'left');

      // ⚠️ FILTER PENTING: Hanya tampilkan alat (kode_jenis = 'B')
      $builder->where('lp.' . $cols['jenis'], 'B');

      // Filter kategori tambahan (optional)
      if ($jenKodeFilter !== '' && $jenKodeFilter !== 'B') {
        $builder->where("TRIM(LEFT(lp.{$cols['jenis']}, 2))", $jenKodeFilter);
      }

      $builder->orderBy("lp.{$cols['kode']}", 'ASC');

      $listUji = $builder->get()->getResult();

      // Filter search query
      if ($q !== '') {
        $filtered = [];
        foreach ($listUji as $row) {
          $fields = [
            isset($row->nama) ? mb_strtolower($row->nama, 'UTF-8') : '',
            isset($row->nama) ? mb_strtolower($row->nama, 'UTF-8') : '',
            isset($row->nama_layanan) ? mb_strtolower($row->nama_layanan, 'UTF-8') : '',
            isset($row->nama) ? mb_strtolower($row->nama, 'UTF-8') : '',
            isset($row->kode) ? (string) $row->kode : ''
          ];

          $matchQ = false;
          foreach ($fields as $f) {
            if ($f !== '' && mb_stripos($f, $q, 0, 'UTF-8') !== false) {
              $matchQ = true;
              break;
            }
          }

          if ($matchQ) {
            $filtered[] = $row;
          }
        }
        $listUji = $filtered;
      }

      // Build response data
      $data = [];

      foreach ($listUji as $row) {
        $response = [];

        // Parameter
        $response[] = esc($row->nama ?? '-');

        // Instrumen/Alat
        $response[] = esc($row->nama ?? '-');

        // Tentukan diskon yang diperbolehkan
        $allowedDiskon = 0;
        if (!empty($row->diskon) && $row->diskon > 0 && $userIdentity === 'ULM') {
          $allowedDiskon = (float) $row->diskon;
        }

        // Biaya
        $biaya = 'Rp ' . number_format($row->biaya, 0, ',', '.');
        if ($allowedDiskon > 0) {
          $biaya .= ' <span class="badge bg-danger ms-1">-' . $allowedDiskon . '%</span>';
        }
        $response[] = $biaya;

        // Input Jumlah
        $inputJumlah = '<input type="number" class="form-control form-control-sm text-center jumlah" value="1" min="1" style="width:80px;">';
        $response[] = $inputJumlah;

        // Input Keterangan
        $response[] = '<input type="text" class="form-control form-control-sm keterangan" placeholder="Keterangan...">';

        // Tombol Aksi
        $jenKodeClean = isset($row->kode_jenis) ? trim(substr($row->kode_jenis, 0, 2)) : '';

        $btnMasukkan = '
                <button type="button" 
                        class="btn btn-success btn-sm btnMasukkanAlat" 
                        data-kode="' . esc($row->kode) . '" 
                        data-alat="' . esc($row->nama ?? '') . '" 
                        data-biaya="' . $row->biaya . '" 
                        data-parameter="' . esc($row->nama ?? '') . '"
                        data-nama-layanan="' . esc($row->nama_layanan ?? '') . '"
                        data-diskon="' . $allowedDiskon . '" 
                        data-kode="' . esc($jenKodeClean) . '"
                        data-nama="' . esc($row->nama ?? '') . '"
                        title="Masukkan ke keranjang sewa alat">
                    <i class="bi bi-cart-plus"></i>
                </button>
            ';
        $response[] = $btnMasukkan;

        $data[] = $response;
      }

      return $this->response->setJSON([
        "items" => $data,
        "total" => count($data),
        "debug" => [
          "filter_jenKode" => $jenKodeFilter,
          "search_query" => $q,
          "user_identity" => $userIdentity
        ]
      ]);
    } catch (\Exception $e) {
      return $this->response->setStatusCode(500)->setJSON([
        'error' => true,
        'message' => 'Terjadi kesalahan saat memuat data layanan',
        'detail' => ENVIRONMENT === 'development' ? $e->getMessage() : null,
        'items' => []
      ]);
    }
  }

  /**
   * Get kategori list untuk filter (override untuk hanya menampilkan alat)
   */
  public function kategoriList()
  {
    try {
      $db = \Config\Database::connect();
      $builder = $db->table($this->tablePengujian . ' as lp');

      $builder->select('lp.kode_jenis as kode, j.nama');
      $builder->join('r_jenis j', 'j.kode = lp.kode_jenis', 'left');
      $builder->where('lp.kode_jenis', 'B');  // Filter untuk alat
      $builder->groupBy('lp.kode_jenis, j.nama');
      $builder->orderBy('j.nama', 'ASC');

      $categories = $builder->get()->getResult();

      return $this->response->setJSON([
        'categories' => $categories ?? []
      ]);
    } catch (\Exception $e) {
      return $this->response->setJSON([
        'categories' => []
      ]);
    }
  }
}
