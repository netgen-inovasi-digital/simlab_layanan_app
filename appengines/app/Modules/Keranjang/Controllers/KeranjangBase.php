<?php

namespace Modules\Keranjang\Controllers;

use App\Controllers\BaseController;
use Modules\Keranjang\Models\KeranjangModel;

/**
 * Base Controller untuk semua jenis keranjang
 * Berisi logic umum yang di-share antar jenis layanan
 * Juga menjadi penyimpan konfigurasi untuk semua jenis layanan
 */
abstract class KeranjangBase extends BaseController
{
  protected $jenisLayanan;      // 'pengujian', 'sewa', dll
  protected $config;
  protected $encrypter;
  protected $sessionKey;

  /** @var KeranjangModel */
  protected $keranjangModel;

  // Table names (dari config)
  protected $tableLayanan;
  protected $tableLayananDetail;
  protected $tablePengujian;
  protected $tablePembayaran;

  public function __construct()
  {
    $this->encrypter = \Config\Services::encrypter();

    // Harus di-set oleh child class
    if (!$this->jenisLayanan) {
      throw new \RuntimeException('Property $jenisLayanan harus di-set di child class');
    }

    // Load config berdasarkan jenis layanan
    $this->loadConfig();

    // Siapkan model utama untuk seluruh akses database
    $this->keranjangModel = new KeranjangModel($this->config);
  }

  /**
   * Load konfigurasi dari getLayananConfig()
   */
  protected function loadConfig(): void
  {
    $this->config = static::getLayananConfig($this->jenisLayanan);

    // Set table names dari config
    $this->tableLayanan = $this->config['table_layanan'];
    $this->tableLayananDetail = $this->config['table_detail'];
    $this->tablePengujian = $this->config['table_pengujian'];
    $this->tablePembayaran = $this->config['table_pembayaran'];
    $this->sessionKey = $this->config['session_key'];
  }

  /**
   * Get konfigurasi untuk jenis layanan tertentu
   * CENTRAL CONFIG - Semua konfigurasi jenis layanan ada di sini
   * 
   * @param string $jenisLayanan 'pengujian', 'sewa', 'konsultasi'
   * @return array Konfigurasi lengkap untuk jenis layanan
   */
  protected static function getLayananConfig(string $jenisLayanan): array
  {
    $configs = [
      // ========================================
      // PENGUJIAN (Testing Services)
      // ========================================
      'pengujian' => [
        'title' => 'Keranjang Layanan Pengujian',
        'session_key' => 'keranjang_pengujian',

        // Table names
        'table_layanan' => 'simlab_t_layanan',
        'table_detail' => 't_layanan_detil',
        'table_pengujian' => 'r_layanan_pengujian',
        'table_pembayaran' => 't_pembayaran',

        // Column mappings
        'columns' => [
          'kode' => 'kode',
          'nama' => 'nama_layanan',
          'alat' => 'kode_alat',
          'parameter' => 'kode_parameter',
          'jenis' => 'kode_jenis',
          'satuan' => 'satuan',
          'biaya' => 'biaya',
          'diskon' => 'diskon',
        ],

        // Joins untuk query
        'joins' => [
          [
            'table' => 'simlab_r_parameter',
            'alias' => 'p',
            'on' => 'p.paraKode = lp.kode_parameter',
            'type' => 'left'
          ],
          [
            'table' => 'simlab_r_alat',
            'alias' => 'a',
            'on' => 'a.alatKode = lp.kode_alat',
            'type' => 'left'
          ],
          [
            'table' => 'simlab_r_jenis',
            'alias' => 'j',
            'on' => 'j.jenKode = lp.kode_jenis',
            'type' => 'left'
          ],
        ],

        // Fields untuk modal form
        'modal_fields' => [
          ['label' => 'Parameter', 'name' => 'detParameter', 'type' => 'text', 'readonly' => true],
          ['label' => 'Instrumen/Alat', 'name' => 'detAlat', 'type' => 'text', 'readonly' => true],
          ['label' => 'Biaya', 'name' => 'detBiaya', 'type' => 'number', 'readonly' => true],
          ['label' => 'Jumlah', 'name' => 'detJumlah', 'type' => 'number', 'min' => 1, 'max' => 100],
          ['label' => 'Metode Uji', 'name' => 'detMetode', 'type' => 'select', 'required' => true],
        ],

        // Validation rules
        'validation' => [
          'min_jumlah' => 1,
          'max_jumlah' => 100,
          'require_keterangan' => false,
        ]
      ],

      // ========================================
      // SEWA ALAT (Equipment Rental)
      // ========================================
      'alat' => [
        'title' => 'Keranjang Sewa Alat',
        'session_key' => 'keranjang_alat',

        // Table names - MENGGUNAKAN TABEL YANG SAMA DENGAN PENGUJIAN
        'table_layanan' => 'simlab_t_layanan',
        'table_detail' => 't_layanan_detil',
        'table_pengujian' => 'r_layanan_pengujian',
        'table_pembayaran' => 't_pembayaran',

        // Column mappings
        'columns' => [
          'kode' => 'kode',
          'nama' => 'nama_layanan',
          'alat' => 'kode_alat',
          'parameter' => 'kode_parameter',
          'jenis' => 'kode_jenis',
          'satuan' => 'satuan',
          'biaya' => 'biaya',
          'diskon' => 'diskon',
        ],

        // Joins untuk query - filter untuk kode_jenis = 'B' (alat)
        'joins' => [
          [
            'table' => 'simlab_r_parameter',
            'alias' => 'p',
            'on' => 'p.paraKode = lp.kode_parameter',
            'type' => 'left'
          ],
          [
            'table' => 'simlab_r_alat',
            'alias' => 'a',
            'on' => 'a.alatKode = lp.kode_alat',
            'type' => 'left'
          ],
          [
            'table' => 'simlab_r_jenis',
            'alias' => 'j',
            'on' => 'j.jenKode = lp.kode_jenis',
            'type' => 'left'
          ],
        ],

        // Fields untuk modal form
        'modal_fields' => [
          ['label' => 'Nama Alat', 'name' => 'detParameter', 'type' => 'text', 'readonly' => true],
          ['label' => 'Instrumen', 'name' => 'detAlat', 'type' => 'text', 'readonly' => true],
          ['label' => 'Biaya', 'name' => 'detBiaya', 'type' => 'number', 'readonly' => true],
          ['label' => 'Jumlah', 'name' => 'detJumlah', 'type' => 'number', 'min' => 1, 'max' => 100],
          ['label' => 'Keterangan', 'name' => 'detKeterangan', 'type' => 'textarea'],
        ],

        // Validation rules
        'validation' => [
          'min_jumlah' => 1,
          'max_jumlah' => 100,
          'require_keterangan' => false,
        ]
      ],

      // ========================================
      // RAPAT JAS (Meeting Room Services)
      // ========================================
      'rapat_jas' => [
        'title' => 'Keranjang Layanan Rapat JAS',
        'session_key' => 'keranjang_rapat_jas',

        // Table names
        'table_layanan' => 'simlab_t_layanan',
        'table_detail' => 't_layanan_detil',
        'table_pengujian' => 'r_layanan_pengujian',
        'table_pembayaran' => 't_pembayaran',

        // Column mappings
        'columns' => [
          'kode' => 'kode',
          'nama' => 'nama_layanan',
          'alat' => 'kode_alat',
          'parameter' => 'kode_parameter',
          'jenis' => 'kode_jenis',
          'satuan' => 'satuan',
          'biaya' => 'biaya',
          'diskon' => 'diskon',
        ],

        // Joins untuk query
        'joins' => [
          [
            'table' => 'simlab_r_parameter',
            'alias' => 'p',
            'on' => 'p.paraKode = lp.kode_parameter',
            'type' => 'left'
          ],
          [
            'table' => 'simlab_r_alat',
            'alias' => 'a',
            'on' => 'a.alatKode = lp.kode_alat',
            'type' => 'left'
          ],
          [
            'table' => 'simlab_r_jenis',
            'alias' => 'j',
            'on' => 'j.jenKode = lp.kode_jenis',
            'type' => 'left'
          ],
        ],

        // Fields untuk modal form
        'modal_fields' => [
          ['label' => 'Parameter', 'name' => 'detParameter', 'type' => 'text', 'readonly' => true],
          ['label' => 'Instrumen/Alat', 'name' => 'detAlat', 'type' => 'text', 'readonly' => true],
          ['label' => 'Biaya', 'name' => 'detBiaya', 'type' => 'number', 'readonly' => true],
          ['label' => 'Jumlah', 'name' => 'detJumlah', 'type' => 'number', 'min' => 1, 'max' => 100],
          ['label' => 'Metode Uji', 'name' => 'detMetode', 'type' => 'select', 'required' => true],
        ],

        // Validation rules
        'validation' => [
          'min_jumlah' => 1,
          'max_jumlah' => 100,
          'require_metode' => true,
        ],

        // Filter khusus untuk Rapat JAS
        'filter' => [
          'kode_jenis' => 'D'
        ]
      ],

      // ========================================
      // SEWA RUANGAN LAB (Lab Room Rental)
      // ========================================
      'lab' => [
        'title' => 'Keranjang Sewa Ruangan Lab',
        'session_key' => 'keranjang_lab',

        // Table names - MENGGUNAKAN TABEL YANG SAMA
        'table_layanan' => 'simlab_t_layanan',
        'table_detail' => 't_layanan_detil',
        'table_pengujian' => 'r_layanan_pengujian',
        'table_pembayaran' => 't_pembayaran',

        // Column mappings
        'columns' => [
          'kode' => 'uji_kode',
          'nama' => 'nama_layanan',
          'ruangan' => 'kode_ruangan',
          'parameter' => 'kode_parameter',
          'jenis' => 'kode_jenis',
          'satuan' => 'satuan',
          'biaya' => 'biaya',
          'diskon' => 'diskon',
        ],

        // Joins untuk query - filter untuk kode_jenis = 'C' (ruangan lab)
        'joins' => [
          [
            'table' => 'simlab_r_parameter',
            'alias' => 'p',
            'on' => 'p.paraKode = lp.kode_parameter',
            'type' => 'left'
          ],
          [
            'table' => 'simlab_r_ruangan',
            'alias' => 'r',
            'on' => 'r.ruanganKode = lp.kode_ruangan',
            'type' => 'left'
          ],
          [
            'table' => 'simlab_r_jenis',
            'alias' => 'j',
            'on' => 'j.jenKode = lp.kode_jenis',
            'type' => 'left'
          ],
        ],

        // Fields untuk modal form
        'modal_fields' => [
          ['label' => 'Parameter', 'name' => 'detParameter', 'type' => 'text', 'readonly' => true],
          ['label' => 'Nama Ruangan', 'name' => 'detRuangan', 'type' => 'text', 'readonly' => true],
          ['label' => 'Biaya/Hari', 'name' => 'detBiaya', 'type' => 'number', 'readonly' => true],
          ['label' => 'Jumlah Hari', 'name' => 'detJumlah', 'type' => 'number', 'min' => 1, 'max' => 365],
          ['label' => 'Keterangan', 'name' => 'detKeterangan', 'type' => 'textarea'],
        ],

        // Validation rules
        'validation' => [
          'min_jumlah' => 1,
          'max_jumlah' => 365,
          'require_keterangan' => true,
        ]
      ],

    ];

    // Return config untuk jenis layanan yang diminta, default ke pengujian
    return $configs[$jenisLayanan] ?? $configs['pengujian'];
  }

  /**
   * Get list semua jenis layanan yang tersedia
   * 
   * @return array
   */
  protected static function getAvailableTypes(): array
  {
    return [
      'pengujian' => 'Layanan Pengujian',
      'alat' => 'Sewa Alat',
      'rapat_jas' => 'Layanan Rapat JAS',
      'lab' => 'Sewa Ruangan Lab',
    ];
  }

  /**
   * Validate apakah jenis layanan valid
   * 
   * @param string $jenisLayanan
   * @return bool
   */
  protected static function isValidType(string $jenisLayanan): bool
  {
    return array_key_exists($jenisLayanan, static::getAvailableTypes());
  }

  /**
   * Halaman index - bisa di-override oleh child class
   */
  public function index()
  {
    $session = session();
    $user_id = $session->get('id_user');
    $user = $this->keranjangModel->getUserById((int) $user_id);
    $categories = $this->getCategories();

    $data = [
      'title' => $this->config['title'],
      'user' => $user,
      'categories' => $categories,
      'jenisLayanan' => $this->jenisLayanan,
    ];

    return view('Modules\Keranjang\Views\v_keranjang', $data);
  }

  /**
   * Get categories - bisa di-override jika logic berbeda
   */
  protected function getCategories(): array
  {
    return $this->keranjangModel->getCategories();
  }

  /**
   * Check user verification status
   */
  public function checkVerified()
  {
    $session = session();
    $user_id = $session->get('id_user');
    $user = $this->keranjangModel->getUserById((int) $user_id);

    if (!$user) {
      return $this->response->setJSON(['verified' => false, 'msg' => 'User tidak ditemukan.']);
    }

    if ((int) $user->verifikasi === 1) {
      return $this->response->setJSON(['verified' => true, 'msg' => 'Akun sudah terverifikasi.']);
    } else {
      return $this->response->setJSON([
        'verified' => false,
        'msg' => 'Akun belum diverifikasi. Silakan unggah bukti atau tunggu verifikasi.'
      ]);
    }
  }

  /**
   * Get keranjang data list - bisa di-override untuk custom display
   */
  public function keranjangDataList()
  {
    $session = session();
    $keranjang = $session->get($this->sessionKey) ?? [];
    $data = [];

    // Clean duplicates
    $cleanedKeranjang = $this->cleanDuplicates($keranjang);
    $session->set($this->sessionKey, $cleanedKeranjang);

    // Build data untuk output
    foreach ($cleanedKeranjang as $idx => $row) {
      $response = $this->buildRowData($row, $idx);
      $data[] = $response;
    }

    return $this->response->setJSON(['items' => $data]);
  }

  /**
   * Clean duplicate items dari keranjang
   */
  protected function cleanDuplicates(array $keranjang): array
  {
    $unique = [];
    $cleanedKeranjang = [];

    foreach ($keranjang as $row) {
      $key = $this->generateItemKey($row);

      if (!isset($unique[$key])) {
        $unique[$key] = true;
        $cleanedKeranjang[] = $row;
      }
    }

    return $cleanedKeranjang;
  }

  /**
   * Generate unique key untuk item - bisa di-override
   */
  protected function generateItemKey(array $row): string
  {
    $kode = isset($row['kode']) ? trim((string) $row['kode']) : '';
    $alat = isset($row['alat']) ? trim((string) $row['alat']) : '';
    $ket = isset($row['keterangan']) ? trim((string) $row['keterangan']) : '';

    return md5($kode . '|' . $alat . '|' . $ket);
  }

  /**
   * Build row data untuk display - WAJIB di-override oleh child class
   */
  abstract protected function buildRowData(array $row, int $idx): array;

  /**
   * Submit item ke keranjang
   */
  public function keranjangSubmit()
  {
    $session = session();
    $post = $this->request->getPost();

    // Validate input menggunakan config
    $validationResult = $this->validateSubmitData($post);
    if (!$validationResult['valid']) {
      return $this->response->setJSON([
        'res' => false,
        'msg' => $validationResult['message'],
        'xname' => csrf_token(),
        'xhash' => csrf_hash()
      ]);
    }

    // Process item data
    $itemData = $this->processItemData($post);

    // Ambil keranjang lama
    $keranjang = $session->get($this->sessionKey) ?? [];

    // Cari item yang sama atau tambah baru
    $found = $this->findAndUpdateExistingItem($keranjang, $itemData);

    if (!$found) {
      $keranjang[] = $itemData;
    }

    // Simpan kembali ke session
    $session->set($this->sessionKey, $keranjang);

    return $this->response->setJSON([
      'res' => true,
      'msg' => $found ? 'Jumlah item berhasil diperbarui.' : 'Item baru berhasil ditambahkan ke keranjang.',
      'xname' => csrf_token(),
      'xhash' => csrf_hash(),
      'items_count' => count($keranjang)
    ]);
  }

  /**
   * Validate submit data - bisa di-override
   */
  protected function validateSubmitData(array $post): array
  {
    $validation = $this->config['validation'];

    $kode = $post['detUjiKode'] ?? null;
    $jumlah = isset($post['detJumlah']) ? (int) $post['detJumlah'] : 1;

    if (empty($kode)) {
      return ['valid' => false, 'message' => 'Kode tidak valid.'];
    }

    if ($jumlah < $validation['min_jumlah'] || $jumlah > $validation['max_jumlah']) {
      return ['valid' => false, 'message' => "Jumlah harus antara {$validation['min_jumlah']} - {$validation['max_jumlah']}."];
    }

    if ($validation['require_keterangan'] && empty($post['detKeterangan'])) {
      return ['valid' => false, 'message' => 'Keterangan wajib diisi.'];
    }

    return ['valid' => true];
  }

  /**
   * Process item data - bisa di-override untuk custom processing
   */
  abstract protected function processItemData(array $post): array;

  /**
   * Find and update existing item - bisa di-override
   */
  abstract protected function findAndUpdateExistingItem(array &$keranjang, array $itemData): bool;

  /**
   * Checkout - bisa di-override untuk custom checkout process
   */
  public function keranjangCheckout()
  {
    $session = session();
    $user_id = $session->get('id_user');
    $userRow = $this->keranjangModel->getUserById((int) $user_id);

    $keranjang = $session->get($this->sessionKey) ?? [];
    if (empty($keranjang)) {
      return $this->response->setJSON([
        'res' => false,
        'msg' => 'Keranjang kosong',
        'xname' => csrf_token(),
        'xhash' => csrf_hash()
      ]);
    }

    $totalBiaya = array_sum(array_column($keranjang, 'biaya'));

    $db = \Config\Database::connect();

    $db->transStart();

    try {
      // Simpan data utama layanan
      $lnKode = $this->saveMainLayanan($userRow, $totalBiaya);

      if (!$lnKode) {
        throw new \RuntimeException('Gagal menyimpan data layanan utama');
      }

      // Simpan data pembayaran
      $this->savePembayaran($lnKode, $totalBiaya);

      // Simpan detail layanan
      $this->saveDetailLayanan($lnKode, $keranjang);

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

      return $this->response->setJSON([
        'res' => false,
        'msg' => 'Checkout gagal: ' . $e->getMessage(),
        'xname' => csrf_token(),
        'xhash' => csrf_hash()
      ]);
    }
  }

  /**
   * Save main layanan data - bisa di-override
   */
  protected function saveMainLayanan($userRow, float $totalBiaya): int
  {
    $insertId = $this->keranjangModel->insertLayanan([
      'user_id' => session()->get('id_user'),
      'lnAccEmail' => $userRow->user_email ?? '',
      'lnTgl' => date('Y-m-d H:i:s'),
      'lnStatus' => 1,
      'kuisioner' => 0
    ]);

    return (int) $insertId;
  }

  /**
   * Save pembayaran data
   */
  protected function savePembayaran(int $lnKode, float $totalBiaya): void
  {
    $today = date('Y-m-d');

    $this->keranjangModel->insertPembayaran([
      'bayarLnKode' => $lnKode,
      'bayarTotalBiaya' => $totalBiaya,
      'bayarStatus' => 0,
      'bayarInvoiceTgl' => $today,
      'bayarInvoiceFile' => null,
      'bayarBuktiFile' => null,
      'bayarCatatan' => null,
      'bayarInvoiceNo' => null,
    ]);
  }

  /**
   * Save detail layanan - WAJIB di-override oleh child class
   */
  abstract protected function saveDetailLayanan(int $lnKode, array $keranjang): void;

  /**
   * Delete item dari keranjang
   */
  public function keranjangDelete($id)
  {
    $session = session();
    $keranjang = $session->get($this->sessionKey) ?? [];

    if (isset($keranjang[$id])) {
      unset($keranjang[$id]);
      $keranjang = array_values($keranjang);
      $session->set($this->sessionKey, $keranjang);

      return $this->response->setJSON([
        'res' => true,
        'msg' => 'Item berhasil dihapus dari keranjang.',
        'xname' => csrf_token(),
        'xhash' => csrf_hash()
      ]);
    }

    return $this->response->setJSON([
      'res' => true,
      'msg' => 'Item tidak ditemukan di keranjang.',
      'xname' => csrf_token(),
      'xhash' => csrf_hash()
    ]);
  }

  /**
   * Get data list layanan - WAJIB di-override oleh child class
   */
  abstract public function keranjangDataListLayanan();

  /**
   * Get kategori list
   */
  public function kategoriList()
  {
    $categories = $this->getCategories();

    return $this->response->setJSON([
      'success' => true,
      'categories' => $categories
    ]);
  }

  /**
   * Get list metode pengujian untuk dropdown
   */
  public function getMetodeList()
  {
    try {
      $metodeList = $this->keranjangModel->getMetodeList();

      return $this->response->setJSON([
        'success' => true,
        'data' => $metodeList
      ]);
    } catch (\Exception $e) {
      log_message('error', 'Error fetching metode list: ' . $e->getMessage());
      return $this->response->setJSON([
        'success' => false,
        'message' => 'Gagal memuat data metode pengujian'
      ]);
    }
  }

  /**
   * Helper untuk generate aksi button
   */
  protected function aksiKeranjang($id, $isPreview = false): string
  {
    $functionName = $isPreview ? 'deleteItemFromPreview' : 'deleteItem';

    return '<div id="item-' . $id . '" class="text-center">
            <span data-index="' . $id . '" 
                class="text-danger btn-action btn-delete-item" 
                style="cursor: pointer;"
                title="Hapus" 
                onclick="' . $functionName . '(event)">
                <i class="bi bi-trash"></i>
            </span>
        </div>';
  }

  /**
   * Get user discount berdasarkan identity
   */
  protected function getUserDiscount(float $originalDiskon): float
  {
    $user_id = (int) session()->get('id_user');
    $userIdentity = $this->keranjangModel->getUserIdentity($user_id);

    return ($userIdentity === 'ULM') ? max(0, $originalDiskon) : 0;
  }
}
