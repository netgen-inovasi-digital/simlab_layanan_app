<?php

namespace Modules\Keranjang\Controllers;

/**
 * Keranjang Controller untuk Layanan PENGUJIAN
 * Extends KeranjangBase untuk reuse common logic
 */
class Keranjang extends KeranjangBase
{
  /**
   * Cache nama metode agar tidak query berulang kali.
   *
   * @var array<int,string>
   */
  protected $metodeCache = [];
  public function __construct()
  {
    // Set jenis layanan untuk pengujian
    $this->jenisLayanan = 'pengujian';

    // Call parent constructor untuk load config
    parent::__construct();

    // 🔍 DEBUG: Log config yang ter-load
    log_message('debug', 'Keranjang Config Loaded: ' . json_encode([
      'jenis_layanan' => $this->jenisLayanan,
      'table_pengujian' => $this->tablePengujian,
      'table_detail' => $this->tableLayananDetail,
      'columns' => $this->config['columns'] ?? null
    ]));
  }

  /**
   * Build row data untuk display di keranjang
   * Override dari KeranjangBase
   */
  protected function buildRowData(array $row, int $idx): array
  {
    $response = [];

    $parameter = $row['layanan'] ?? '-';
    $alat = $row['alat'] ?? '-';
    $jumlah = (int) ($row['jumlah'] ?? 0);
    $metodeKode = $row['metode_kode'] ?? null;
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

    // Metode Uji
    $metodeNama = '-';
    if ($metodeKode) {
      $metodeNama = esc($this->getMetodeName((int) $metodeKode));
    }
    $response[] = $metodeNama;

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
    $detMetode = isset($post['detMetode']) ? (int) $post['detMetode'] : null;
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
      'metode_kode' => $detMetode,
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
      $sameMetode = (isset($item['metode_kode']) ? (int) $item['metode_kode'] : null) === (isset($itemData['metode_kode']) ? (int) $itemData['metode_kode'] : null);

      // Item dianggap sama hanya jika kode, alat, DAN metode sama
      if ($sameKode && $sameAlat && $sameMetode) {
        // Tambah jumlah
        $keranjang[$idx]['jumlah'] = (int) ($item['jumlah'] ?? 0) + (int) $itemData['jumlah'];

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
    foreach ($keranjang as $item) {
      $jenKodeValue = null;

      if (isset($item['kode'])) {
        $jenKodeValue = trim((string) $item['kode']);
        if ($jenKodeValue === '') {
          $jenKodeValue = null;
        }
      }

      if ($jenKodeValue === null && !empty($item['kode'])) {
        $jenKodeValue = $this->keranjangModel->resolveJenisKode((string) $item['kode']);
      }

      $detil = [
        'kode_layanan' => $kode_layanan,
        'uji_kode' => $item['kode'] ?? null,
        'biaya' => $item['biaya'] ?? 0,
        'jumlah' => $item['jumlah'] ?? 1,
        'metode_pengujian' => isset($item['metode_kode']) ? (int) $item['metode_kode'] : null,
        'nama_layanan' => $item['nama_layanan'] ?? null,
        'status_layanan' => 0,
        'kode_jenis' => $jenKodeValue,
        'catatan_manajer' => null,
        'terima_layanan_by' => null,
        'files' => null,
      ];

      log_message('debug', 'Inserting detail layanan: ' . json_encode($detil));

      if (!$this->keranjangModel->insertDetailLayanan($detil)) {
        throw new \RuntimeException('Gagal simpan detail layanan.');
      }
    }
  }

  /**
   * Get data list layanan (override untuk custom query pengujian)
   */
  public function keranjangDataListLayanan()
  {
    $session = session();
    $user_id = (int) $session->GET('id_user');
    $userIdentity = $this->keranjangModel->getUserIdentity($user_id);

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
        $jenKodeFilter = $parts[0] === '' ? $parts[1] : $parts[0];
      }
      $jenKodeFilter = preg_replace('/[^A-Za-z0-9]/', '', $jenKodeFilter);
      $jenKodeFilter = substr($jenKodeFilter, 0, 2);
      $jenKodeFilter = trim($jenKodeFilter);
    }

    try {
      $listUji = $this->keranjangModel->fetchPengujianList($jenKodeFilter ?: null);

      if ($q !== '') {
        $listUji = $this->keranjangModel->filterPengujianBySearch($listUji, $q);
      }

      $data = [];
      $metodeList = $this->keranjangModel->getMetodeList();

      foreach ($listUji as $row) {
        $response = [];

        // Parameter
        $response[] = esc($row->parameter_nama ?? '-');
        // Instrumen/Alat
        $response[] = esc($row->alat_nama ?? '-');

        $allowedDiskon = 0;
        if (!empty($row->diskon) && $row->diskon > 0 && $userIdentity === 'ULM') {
          $allowedDiskon = (float) $row->diskon;
        }

        $biaya = 'Rp ' . number_format($row->biaya, 0, ',', '.');
        if ($allowedDiskon > 0) {
          $biaya .= ' <span class="badge bg-danger ms-1">-' . $allowedDiskon . '%</span>';
        }
        $response[] = $biaya;

        $inputJumlah = '<input type="number" class="form-control form-control-sm text-center jumlah" value="1" min="1" style="width:80px;">';
        $response[] = $inputJumlah;

        $selectMetode = '<select class="form-select form-select-sm metode-select" required>';
        $selectMetode .= '<option value="">-- Pilih Metode --</option>';
        foreach ($metodeList as $metode) {
          $selectMetode .= '<option value="' . esc($metode->metode_kode) . '">' . esc($metode->nama) . '</option>';
        }
        $selectMetode .= '</select>';
        $response[] = $selectMetode;

        $jenKodeClean = isset($row->kode_jenis) ? trim(substr($row->kode_jenis, 0, 2)) : '';

        $btnMasukkan = '<button type="button" class="btn btn-success btn-sm btnMasukkan" '
          . 'data-kode="' . esc($row->kode) . '" '
          . 'data-alat="' . esc($row->nama ?? '') . '" '
          . 'data-biaya="' . $row->biaya . '" '
          . 'data-parameter="' . esc($row->nama ?? '') . '" '
          . 'data-nama-layanan="' . esc($row->nama_layanan ?? '') . '" '
          . 'data-diskon="' . $allowedDiskon . '" '
          . 'data-kode="' . esc($jenKodeClean) . '" '
          . 'data-nama="' . esc($row->nama ?? '') . '" '
          . 'title="Masukkan ke keranjang">'
          . '<i class="bi bi-cart-plus"></i>'
          . '</button>';
        $response[] = $btnMasukkan;

        $data[] = $response;
      }

      return $this->response->setJSON([
        'items' => $data,
        'total' => count($data),
        'debug' => [
          'filter_jenKode' => $jenKodeFilter,
          'search_query' => $q,
          'user_identity' => $userIdentity
        ]
      ]);
    } catch (\Exception $e) {
      log_message('error', 'Error in keranjangDataListLayanan: ' . $e->getMessage());
      log_message('error', 'Stack trace: ' . $e->getTraceAsString());

      return $this->response->setStatusCode(500)->setJSON([
        'error' => true,
        'message' => 'Terjadi kesalahan saat memuat data layanan',
        'detail' => ENVIRONMENT === 'development' ? $e->getMessage() : null,
        'items' => []
      ]);
    }
  }

  /**
   * Override keranjangCheckout untuk menyimpan identitas sampel
   */
  public function keranjangCheckout()
  {
    $session = session();
    $user_id = (int) $session->GET('id_user');
    $userRow = $this->keranjangModel->getUserById($user_id);

    $keranjang = $session->get($this->sessionKey) ?? [];
    if (empty($keranjang)) {
      return $this->response->setJSON([
        'res' => false,
        'msg' => 'Keranjang kosong',
        'xname' => csrf_token(),
        'xhash' => csrf_hash()
      ]);
    }

    // Ambil data identitas sampel dari POST
    $jenisSampel = $this->request->getPost('jenisSampel');
    $kemasanSampel = $this->request->getPost('kemasanSampel');
    $sifatSampel = $this->request->getPost('sifatSampel');
    $sisaSampel = $this->request->getPost('sisaSampel');
    $deskripsiSampel = $this->request->getPost('deskripsiSampel');
    $keteranganKhusus = $this->request->getPost('keteranganKhusus');

    // Validasi data identitas sampel
    if (empty($jenisSampel)) {
      return $this->response->setJSON([
        'res' => false,
        'msg' => 'Jenis Sampel harus diisi',
        'xname' => csrf_token(),
        'xhash' => csrf_hash()
      ]);
    }

    if (empty($kemasanSampel)) {
      return $this->response->setJSON([
        'res' => false,
        'msg' => 'Kemasan Sampel harus diisi',
        'xname' => csrf_token(),
        'xhash' => csrf_hash()
      ]);
    }

    if (empty($sifatSampel)) {
      return $this->response->setJSON([
        'res' => false,
        'msg' => 'Sifat Sampel harus dipilih',
        'xname' => csrf_token(),
        'xhash' => csrf_hash()
      ]);
    }

    if (empty($sisaSampel)) {
      return $this->response->setJSON([
        'res' => false,
        'msg' => 'Status Sisa Sampel harus dipilih',
        'xname' => csrf_token(),
        'xhash' => csrf_hash()
      ]);
    }

    $totalBiaya = array_sum(array_column($keranjang, 'biaya'));

    $db = \Config\Database::connect();

    $db->transStart();

    try {
      // Simpan data utama layanan
      $kode_layanan = $this->saveMainLayanan($userRow, $totalBiaya);

      if (!$kode_layanan) {
        throw new \RuntimeException('Gagal menyimpan data layanan utama');
      }

      // Simpan data pembayaran
      $this->savePembayaran($kode_layanan, $totalBiaya);

      // Simpan detail layanan
      $this->saveDetailLayanan($kode_layanan, $keranjang);

      // Simpan identitas sampel
      $identitasSampelData = [
        'kode_layanan' => $kode_layanan,
        'jenis' => trim($jenisSampel),
        'kemasan' => trim($kemasanSampel),
        'sifat' => $sifatSampel,
        'sisa' => $sisaSampel,
        'deskripsi' => !empty($deskripsiSampel) ? trim($deskripsiSampel) : null,
        'keterangan_khusus' => !empty($keteranganKhusus) ? trim($keteranganKhusus) : null,
      ];

      if (!$this->keranjangModel->saveIdentitasSampel($identitasSampelData)) {
        throw new \RuntimeException('Gagal menyimpan identitas sampel');
      }

      // Seed log sampel agar tahapan lain bisa langsung melakukan update timestamp
      if (!$this->keranjangModel->seedLogSampel($kode_layanan)) {
        throw new \RuntimeException('Gagal menyimpan log sampel');
      }

      // Commit dan bersihkan keranjang
      $db->transComplete();
      $session->remove($this->sessionKey);

      return $this->response->setJSON([
        'res' => true,
        'msg' => 'Checkout berhasil! Data Anda sedang diproses.',
        'xname' => csrf_token(),
        'xhash' => csrf_hash()
      ]);
    } catch (\Exception $e) {
      if ($db->transStatus() === FALSE) {
        $db->transRollback();
      }

      log_message('error', 'Error in keranjangCheckout: ' . $e->getMessage());

      return $this->response->setJSON([
        'res' => false,
        'msg' => 'Checkout gagal: ' . $e->getMessage(),
        'xname' => csrf_token(),
        'xhash' => csrf_hash()
      ]);
    }
  }

  /**
   * Generate unique key untuk item - override untuk include metode_kode
   */
  protected function generateItemKey(array $row): string
  {
    $kode = isset($row['kode']) ? trim((string) $row['kode']) : '';
    $alat = isset($row['alat']) ? trim((string) $row['alat']) : '';
    $metode = isset($row['metode_kode']) ? (int) $row['metode_kode'] : 0;

    return md5($kode . '|' . $alat . '|' . $metode);
  }

  /**
   * Ambil nama metode dengan cache sederhana.
   */
  protected function getMetodeName(int $metodeKode): string
  {
    if (isset($this->metodeCache[$metodeKode])) {
      return $this->metodeCache[$metodeKode];
    }

    $metode = $this->keranjangModel->getMetodeByKode($metodeKode);
    $nama = ($metode && isset($metode->nama) && trim((string) $metode->nama) !== '')
      ? trim((string) $metode->nama)
      : '-';

    $this->metodeCache[$metodeKode] = $nama;

    return $nama;
  }
}
