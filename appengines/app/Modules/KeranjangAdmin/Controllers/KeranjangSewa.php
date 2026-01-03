<?php

namespace Modules\KeranjangAdmin\Controllers;

use App\Models\MyModel;

/**
 * Keranjang Controller untuk Layanan SEWA ALAT (ADMIN VERSION)
 * Extends KeranjangBase untuk reuse common logic
 */
class KeranjangSewa extends KeranjangBase
{
  public function __construct()
  {
    // Set jenis layanan untuk sewa
    $this->jenisLayanan = 'sewa';

    // Call parent constructor untuk load config
    parent::__construct();
  }

  /**
   * Build row data untuk display di keranjang (sewa)
   * Override dari KeranjangBase
   */
  protected function buildRowData(array $row, int $idx): array
  {
    $response = [];

    $namaAlat = $row['nama_alat'] ?? '-';
    $kategori = $row['kategori'] ?? '-';
    $jumlah = (int) ($row['jumlah'] ?? 0);
    $durasi = (int) ($row['durasi'] ?? 1);
    $keterangan = $row['keterangan'] ?? '';
    $diskon = (float) ($row['diskon'] ?? 0);
    $biayaPerHari = (float) ($row['biaya_per_hari'] ?? 0);
    $biayaTotal = (float) ($row['biaya'] ?? 0);
    $tanggalMulai = $row['tanggal_mulai'] ?? '-';

    // Nama Alat
    $response[] = esc($namaAlat);

    // Kategori
    $response[] = esc($kategori);

    // Durasi (hari)
    $response[] = $durasi . ' hari';

    // Tanggal Mulai
    $response[] = esc($tanggalMulai);

    // Diskon
    $response[] = $diskon > 0 ? $diskon . '%' : '-';

    // Biaya per hari
    if ($diskon > 0) {
      $hargaDiskon = $biayaPerHari - ($biayaPerHari * ($diskon / 100));
      $biayaTampil = '<span style="color:red;text-decoration:line-through;">Rp '
        . number_format($biayaPerHari, 0, ',', '.') . '</span><br>';
      $biayaTampil .= 'Rp ' . number_format($hargaDiskon, 0, ',', '.');
    } else {
      $biayaTampil = 'Rp ' . number_format($biayaPerHari, 0, ',', '.');
    }
    $response[] = $biayaTampil;

    // Jumlah Unit
    $response[] = $jumlah;

    // Keterangan
    $response[] = esc($keterangan);

    // Aksi + total hidden
    $hiddenTotal = '<span class="d-none row-total">Rp ' . number_format($biayaTotal, 0, ',', '.') . '</span>';
    $response[] = $this->aksiKeranjang($idx, true) . $hiddenTotal;

    return $response;
  }

  /**
   * Process item data dari POST submit (sewa)
   * Override dari KeranjangBase
   */
  protected function processItemData(array $post): array
  {
    $kodeAlat = $post['kode_alat'] ?? null;
    $namaAlat = $post['nama_alat'] ?? '';
    $kategori = $post['kategori'] ?? '';
    $biayaPerHari = isset($post['biaya_per_hari']) ? (float) $post['biaya_per_hari'] : 0;
    $diskon = isset($post['diskon']) ? (float) $post['diskon'] : 0;
    $jumlah = isset($post['jumlah']) ? (int) $post['jumlah'] : 1;
    $durasi = isset($post['durasi']) ? (int) $post['durasi'] : 1;
    $tanggalMulai = $post['tanggal_mulai'] ?? date('Y-m-d');
    $keterangan = trim($post['keterangan'] ?? '');

    // Dapatkan diskon yang sebenarnya diterapkan
    $appliedDiskon = $this->getUserDiscount($diskon);

    // Hitung biaya
    $jumlah = max(1, $jumlah);
    $durasi = max(1, $durasi);
    $biayaPerHari = max(0, $biayaPerHari);

    // Total = (biaya per hari * durasi * jumlah) dengan diskon
    $biayaSetelahDiskon = $biayaPerHari * (1 - ($appliedDiskon / 100));
    $biayaTotal = $biayaSetelahDiskon * $durasi * $jumlah;

    return [
      'kode_alat' => $kodeAlat,
      'nama_alat' => $namaAlat,
      'kategori' => $kategori,
      'biaya_per_hari' => $biayaPerHari,
      'diskon' => $appliedDiskon,
      'jumlah' => $jumlah,
      'durasi' => $durasi,
      'tanggal_mulai' => $tanggalMulai,
      'keterangan' => $keterangan,
      'biaya' => $biayaTotal,
    ];
  }

  /**
   * Validate submit data untuk sewa
   * Override dari parent
   */
  protected function validateSubmitData(array $post): array
  {
    $validation = $this->config['validation'];

    $kodeAlat = $post['kode_alat'] ?? null;
    $jumlah = isset($post['jumlah']) ? (int) $post['jumlah'] : 1;
    $durasi = isset($post['durasi']) ? (int) $post['durasi'] : 1;

    if (empty($kodeAlat)) {
      return ['valid' => false, 'message' => 'Kode alat tidak valid.'];
    }

    if ($jumlah < $validation['min_jumlah'] || $jumlah > $validation['max_jumlah']) {
      return ['valid' => false, 'message' => "Jumlah unit harus antara {$validation['min_jumlah']} - {$validation['max_jumlah']}."];
    }

    if ($durasi < 1 || $durasi > 365) {
      return ['valid' => false, 'message' => 'Durasi sewa harus antara 1-365 hari.'];
    }

    // Validasi tanggal mulai (opsional - bisa ditambahkan)
    $tanggalMulai = $post['tanggal_mulai'] ?? '';
    if (empty($tanggalMulai)) {
      return ['valid' => false, 'message' => 'Tanggal mulai sewa wajib diisi.'];
    }

    return ['valid' => true];
  }

  /**
   * Find and update existing item di keranjang (sewa)
   * Override dari KeranjangBase
   */
  protected function findAndUpdateExistingItem(array &$keranjang, array $itemData): bool
  {
    $found = false;

    foreach ($keranjang as $idx => $item) {
      $sameKode = isset($item['kode_alat']) && (string) $item['kode_alat'] === (string) $itemData['kode_alat'];
      $sameTanggal = (isset($item['tanggal_mulai']) ? $item['tanggal_mulai'] : '') === $itemData['tanggal_mulai'];

      // Untuk sewa, item sama jika kode alat DAN tanggal mulai sama
      if ($sameKode && $sameTanggal) {
        // Tambah jumlah dan durasi
        $keranjang[$idx]['jumlah'] = (int) ($item['jumlah'] ?? 0) + (int) $itemData['jumlah'];
        $keranjang[$idx]['durasi'] = (int) ($item['durasi'] ?? 0) + (int) $itemData['durasi'];

        // Update keterangan
        $keranjang[$idx]['keterangan'] = $itemData['keterangan'];

        // Hitung ulang biaya
        $biayaPerHari = isset($item['biaya_per_hari']) ? (float) $item['biaya_per_hari'] : 0;
        $disk = isset($item['diskon']) ? (float) $item['diskon'] : 0;

        $jumlahBaru = (int) $keranjang[$idx]['jumlah'];
        $durasiBaru = (int) $keranjang[$idx]['durasi'];

        $biayaSetelahDiskon = $biayaPerHari * (1 - ($disk / 100));
        $keranjang[$idx]['biaya'] = $biayaSetelahDiskon * $durasiBaru * $jumlahBaru;

        $found = true;
        break;
      }
    }

    return $found;
  }

  /**
   * Save detail layanan ke database (sewa)
   * Override dari KeranjangBase
   */
  protected function saveDetailLayanan(int $kode_layanan, array $keranjang): void
  {
    $modelDetil = new MyModel($this->tableLayananDetail);

    foreach ($keranjang as $i => $item) {
      // Hitung tanggal selesai berdasarkan tanggal mulai + durasi
      $tanggalMulai = $item['tanggal_mulai'] ?? date('Y-m-d');
      $durasi = (int) ($item['durasi'] ?? 1);
      $tanggalSelesai = date('Y-m-d', strtotime($tanggalMulai . " + {$durasi} days"));

      // Build data detil untuk sewa
      $detil = [
        'kode_layanan' => $kode_layanan,
        'kode_alat' => $item['kode_alat'] ?? null,
        'nama_alat' => $item['nama_alat'] ?? null,
        'kategori' => $item['kategori'] ?? null,
        'biaya_per_hari' => $item['biaya_per_hari'] ?? null,
        'diskon' => $item['diskon'] ?? 0,
        'jumlah' => $item['jumlah'] ?? 1,
        'durasi' => $durasi,
        'tanggal_mulai' => $tanggalMulai,
        'tanggal_selesai' => $tanggalSelesai,
        'biaya' => $item['biaya'] ?? null,
        'status_layanan' => 0,
      ];

      // Insert ke tabel detil
      $res = $modelDetil->insertData($detil);
      if (!$res) {
        $error = $modelDetil->db->error();
        log_message('error', 'Insert gagal ke ' . $this->tableLayananDetail . '. Data: ' . json_encode($detil));
        log_message('error', 'DB Error: ' . json_encode($error));

        throw new \RuntimeException('Gagal simpan detail sewa: ' . ($error['message'] ?? 'Unknown error'));
      }
    }
  }

  /**
   * Get data list layanan untuk sewa
   * Override dari parent
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

    // Baca parameter filter
    $qRaw = trim((string) ($this->request->getGet('q') ?? ''));
    $q = $qRaw !== '' ? mb_strtolower($qRaw, 'UTF-8') : '';

    $kategoriFilter = trim((string) ($this->request->getGet('kategori') ?? ''));

    // Query menggunakan Query Builder dengan config
    $db = \Config\Database::connect();
    $builder = $db->table($this->tablePengujian . ' as ls');

    $cols = $this->config['columns'];

    $builder->select("
            ls.{$cols['kode']},
            ls.{$cols['nama_alat']},
            ls.{$cols['kategori']},
            ls.{$cols['biaya_per_hari']},
            ls.{$cols['diskon']},
            ls.{$cols['stok_tersedia']},
            k.kategoriNama
        ");

    // Joins dari config
    foreach ($this->config['joins'] as $join) {
      $builder->join($join['table'], $join['on'], $join['type']);
    }

    // Filter kategori
    if ($kategoriFilter !== '') {
      $builder->where("ls.{$cols['kategori']}", $kategoriFilter);
    }

    // Filter stok tersedia
    $builder->where("ls.{$cols['stok_tersedia']} >", 0);

    $builder->orderBy("ls.{$cols['kode']}", 'ASC');

    $listSewa = $builder->get()->getResult();

    // Filter search query
    if ($q !== '') {
      $filtered = [];
      foreach ($listSewa as $row) {
        $fields = [
          isset($row->nama_alat) ? mb_strtolower($row->nama_alat, 'UTF-8') : '',
          isset($row->kategoriNama) ? mb_strtolower($row->kategoriNama, 'UTF-8') : '',
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
      $listSewa = $filtered;
    }

    // Build response data
    $data = [];

    foreach ($listSewa as $row) {
      $response = [];

      // Nama Alat
      $response[] = esc($row->nama_alat ?? '-');

      // Kategori
      $response[] = esc($row->kategoriNama ?? '-');

      // Stok Tersedia
      $response[] = (int) ($row->stok_tersedia ?? 0);

      // Tentukan diskon
      $allowedDiskon = 0;
      if (!empty($row->diskon) && $row->diskon > 0 && $userIdentity === 'ULM') {
        $allowedDiskon = (float) $row->diskon;
      }

      // Biaya per hari
      $biaya = 'Rp ' . number_format($row->biaya_per_hari, 0, ',', '.') . '/hari';
      if ($allowedDiskon > 0) {
        $biaya .= ' <span class="badge bg-danger ms-1">-' . $allowedDiskon . '%</span>';
      }
      $response[] = $biaya;

      // Input Jumlah Unit
      $inputJumlah = '<input type="number" class="form-control form-control-sm text-center jumlah" value="1" min="1" max="' . $row->stok_tersedia . '" style="width:80px;">';
      $response[] = $inputJumlah;

      // Input Durasi (hari)
      $inputDurasi = '<input type="number" class="form-control form-control-sm text-center durasi" value="1" min="1" style="width:80px;">';
      $response[] = $inputDurasi;

      // Input Tanggal Mulai
      $inputTanggal = '<input type="date" class="form-control form-control-sm tanggal-mulai" value="' . date('Y-m-d') . '" min="' . date('Y-m-d') . '">';
      $response[] = $inputTanggal;

      // Input Keterangan
      $response[] = '<input type="text" class="form-control form-control-sm keterangan" placeholder="Keterangan...">';

      // Tombol Aksi
      $btnMasukkan = '
            <button type="button" 
                    class="btn btn-success btn-sm btnMasukkanSewa" 
                    data-kode="' . esc($row->kode) . '" 
                    data-nama="' . esc($row->nama_alat ?? '') . '" 
                    data-kategori="' . esc($row->kategoriNama ?? '') . '" 
                    data-biaya="' . $row->biaya_per_hari . '" 
                    data-diskon="' . $allowedDiskon . '" 
                    data-stok="' . $row->stok_tersedia . '"
                    title="Masukkan ke keranjang">
                <i class="bi bi-cart-plus"></i>
            </button>
        ';
      $response[] = $btnMasukkan;

      $data[] = $response;
    }

    return $this->response->setJSON([
      "items" => $data,
      "debug" => [
        "total_items" => count($data),
        "filter_kategori" => $kategoriFilter,
        "search_query" => $q
      ]
    ]);
  }
}
