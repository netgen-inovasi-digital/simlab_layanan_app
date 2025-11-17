<?php

namespace Modules\KeranjangAdmin\Controllers;

use App\Controllers\BaseController;
use App\Models\MyModel;

/**
 * Base Controller untuk semua jenis keranjang ADMIN
 * Berisi logic umum yang di-share antar jenis layanan
 * Juga menjadi penyimpan konfigurasi untuk semua jenis layanan
 */
abstract class KeranjangBase extends BaseController
{
    protected $jenisLayanan;      // 'pengujian', 'sewa', dll
    protected $config;
    protected $encrypter;
    protected $sessionKey;

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
                'title' => 'Keranjang Layanan Pengujian (Admin)',
                'session_key' => 'keranjang_formadmin',

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
            // SEWA ALAT (Equipment Rental)
            // ========================================
            'sewa' => [
                'title' => 'Keranjang Sewa Alat (Admin)',
                'session_key' => 'keranjang_sewa_admin',

                // Table names
                'table_layanan' => 'simlab_t_layanan',
                'table_detail' => 't_sewa_detil',
                'table_pengujian' => 'r_layanan_sewa_alat',
                'table_pembayaran' => 't_pembayaran',

                // Column mappings
                'columns' => [
                    'kode' => 'kode',
                    'nama_alat' => 'nama_alat',
                    'kategori' => 'kode_kategori',
                    'biaya_per_hari' => 'biaya_per_hari',
                    'diskon' => 'diskon',
                    'durasi' => 'durasi_hari',
                    'tanggal_mulai' => 'tanggal_mulai',
                    'tanggal_selesai' => 'tanggal_selesai',
                    'stok_tersedia' => 'stok_tersedia',
                ],

                // Joins untuk query
                'joins' => [
                    [
                        'table' => 'simlab_r_alat',
                        'alias' => 'a',
                        'on' => 'a.alatKode = ls.kode',
                        'type' => 'left'
                    ],
                    [
                        'table' => 'simlab_r_kategori_alat',
                        'alias' => 'k',
                        'on' => 'k.kategoriKode = ls.kode_kategori',
                        'type' => 'left'
                    ],
                ],

                // Fields untuk modal form
                'modal_fields' => [
                    ['label' => 'Nama Alat', 'name' => 'nama_alat', 'type' => 'text', 'readonly' => true],
                    ['label' => 'Kategori', 'name' => 'kategori', 'type' => 'text', 'readonly' => true],
                    ['label' => 'Biaya per Hari', 'name' => 'biaya_per_hari', 'type' => 'number', 'readonly' => true],
                    ['label' => 'Jumlah Unit', 'name' => 'jumlah', 'type' => 'number', 'min' => 1, 'max' => 50],
                    ['label' => 'Durasi (Hari)', 'name' => 'durasi', 'type' => 'number', 'min' => 1, 'max' => 365],
                    ['label' => 'Tanggal Mulai', 'name' => 'tanggal_mulai', 'type' => 'date'],
                    ['label' => 'Keterangan', 'name' => 'keterangan', 'type' => 'textarea'],
                ],

                // Validation rules
                'validation' => [
                    'min_jumlah' => 1,
                    'max_jumlah' => 50,
                    'min_durasi' => 1,
                    'max_durasi' => 365,
                    'require_keterangan' => false,
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
            'sewa' => 'Sewa Alat',
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
        $session  = session();
        $user_id  = $session->get('id_user');

        $modelUser = new MyModel('simlab_account_users');

        // Get categories menggunakan config dinamis
        $categories = $this->getCategories();

        $data = [
            'title'      => $this->config['title'],
            'user'       => $modelUser->getDataById('user_id', $user_id),
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
        $db = \Config\Database::connect();
        $builder = $db->table($this->tablePengujian . ' as lp');

        $jenisCol = $this->config['columns']['jenis'] ?? 'kode_jenis';

        $builder->select("
            DISTINCT TRIM(LEFT(lp.{$jenisCol}, 2)) as jenKode,
            j.jenNama
        ");
        $builder->join('simlab_r_jenis j', "j.jenKode = TRIM(LEFT(lp.{$jenisCol}, 2))", 'left');
        $builder->where("lp.{$jenisCol} IS NOT NULL");
        $builder->where("lp.{$jenisCol} !=", '');
        $builder->orderBy('j.jenNama', 'ASC');

        $categories = $builder->get()->getResult();

        // Normalisasi
        $normalized = [];
        if (!empty($categories)) {
            foreach ($categories as $c) {
                $kode = isset($c->jenKode) ? trim((string)$c->jenKode) : '';
                $nama = (isset($c->jenNama) && trim((string)$c->jenNama) !== '') ? trim((string)$c->jenNama) : $kode;

                if ($kode !== '') {
                    $normalized[] = (object)[
                        'jenKode' => $kode,
                        'jenNama' => $nama
                    ];
                }
            }
        }

        return array_values($normalized);
    }

    /**
     * Check user verification status
     */
    public function checkVerified()
    {
        $session = session();
        $user_id = $session->get('id_user');

        $modelUser = new MyModel('simlab_account_users');
        $user = $modelUser->getDataById('user_id', $user_id);

        if (!$user) {
            return $this->response->setJSON(['verified' => false, 'msg' => 'User tidak ditemukan.']);
        }

        if ((int)$user->verifikasi === 1) {
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
        $session   = session();
        $keranjang = $session->get($this->sessionKey) ?? [];
        $data      = [];

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
        $kode = isset($row['kode']) ? trim((string)$row['kode']) : '';
        $alat = isset($row['alat']) ? trim((string)$row['alat']) : '';
        $ket  = isset($row['keterangan']) ? trim((string)$row['keterangan']) : '';

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

        // ADMIN MUST SELECT PELANGGAN FIRST
        $pelanggan = $session->get($this->sessionKey . '_pelanggan');
        if (!$pelanggan || !is_array($pelanggan) || empty($pelanggan['user_id'])) {
            return $this->response->setJSON([
                'res'   => false,
                'msg'   => 'Silakan pilih pelanggan terlebih dahulu sebelum menambahkan layanan.',
                'xname' => csrf_token(),
                'xhash' => csrf_hash()
            ]);
        }

        // Validate input menggunakan config
        $validationResult = $this->validateSubmitData($post);
        if (!$validationResult['valid']) {
            return $this->response->setJSON([
                'res'   => false,
                'msg'   => $validationResult['message'],
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
        $jumlah = isset($post['detJumlah']) ? (int)$post['detJumlah'] : 1;

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

        // ADMIN MUST SELECT PELANGGAN FIRST
        $pelanggan = $session->get($this->sessionKey . '_pelanggan');
        if (!$pelanggan || !is_array($pelanggan) || empty($pelanggan['user_id'])) {
            return $this->response->setJSON([
                'res'   => false,
                'msg'   => 'Silakan pilih pelanggan terlebih dahulu sebelum checkout.',
                'xname' => csrf_token(),
                'xhash' => csrf_hash()
            ]);
        }

        $user_id = $session->get('id_user');

        $modelUser = new MyModel('simlab_account_users');
        $userRow   = $modelUser->getDataById('user_id', $user_id);

        $keranjang = $session->get($this->sessionKey) ?? [];
        if (empty($keranjang)) {
            return $this->response->setJSON([
                'res'   => false,
                'msg'   => 'Keranjang kosong',
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

        $modelPembayaran = new MyModel($this->tablePembayaran);
        $modelLayanan    = new MyModel($this->tableLayanan);
        $modelDetil      = new MyModel($this->tableLayananDetail);
        $modelIdentitasSampel = new MyModel('t_identitas_sampel');
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

            // Simpan identitas sampel
            $identitasSampelData = [
                'kode_layanan' => $lnKode,
                'jenis' => trim($jenisSampel),
                'kemasan' => trim($kemasanSampel),
                'sifat' => $sifatSampel,
                'sisa' => $sisaSampel,
                'deskripsi' => !empty($deskripsiSampel) ? trim($deskripsiSampel) : null,
                'keterangan_khusus' => !empty($keteranganKhusus) ? trim($keteranganKhusus) : null,
            ];

            $insertIdentitasResult = $modelIdentitasSampel->insertData($identitasSampelData);
            if (!$insertIdentitasResult) {
                throw new \RuntimeException('Gagal menyimpan identitas sampel');
            }

            // Simpan log sampel dengan tanggal dan waktu checkout pada kolom pengecekan
            $modelLogSampel = new MyModel('t_log_sampel');
            $logSampelData = [
                'kode_layanan' => $lnKode,
                'pengecekan' => date('Y-m-d H:i:s'),
            ];

            $insertLogResult = $modelLogSampel->insertData($logSampelData);
            if (!$insertLogResult) {
                throw new \RuntimeException('Gagal menyimpan log sampel');
            }

            // Commit dan bersihkan keranjang + pelanggan
            $db->transComplete();
            $session->remove($this->sessionKey);
            $session->remove($this->sessionKey . '_pelanggan');  // Clear pelanggan info also

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

            log_message('error', 'Checkout exception: ' . $e->getMessage());

            return $this->response->setJSON([
                'res'   => false,
                'msg'   => 'Checkout gagal: ' . $e->getMessage(),
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
        $modelLayanan = new MyModel($this->tableLayanan);
        $session = session();

        // Untuk admin: cek apakah ada pelanggan yang dipilih
        $pelanggan = $session->get($this->sessionKey . '_pelanggan');
        $userIdToSave = null;
        $emailToSave = '';

        if ($pelanggan && !empty($pelanggan['user_id'])) {
            // Admin memilih pelanggan
            $userIdToSave = (int)$pelanggan['user_id'];
            $emailToSave = $pelanggan['email'] ?? 'by_admin';
        } else {
            // User biasa atau admin tanpa pilih pelanggan
            $userIdToSave = session()->get('id_user');
            $emailToSave = $userRow->user_email ?? '';
        }

        $insertLayananId = $modelLayanan->insertData([
            'user_id'       => $userIdToSave,
            'lnAccEmail'    => $emailToSave,
            'lnTgl'         => date('Y-m-d H:i:s'),
            'lnStatus'      => 1,
            'kuisioner'     => 0
        ], true);

        return (int)$insertLayananId;
    }

    /**
     * Save pembayaran data
     */
    protected function savePembayaran(int $lnKode, float $totalBiaya): void
    {
        $modelPembayaran = new MyModel($this->tablePembayaran);
        $today = date('Y-m-d');

        $modelPembayaran->insertData([
            'bayarLnKode'      => $lnKode,
            'bayarTotalBiaya'  => $totalBiaya,
            'bayarStatus'      => 0,
            'bayarInvoiceTgl'  => $today,
            'bayarInvoiceFile' => null,
            'bayarBuktiFile'   => null,
            'bayarCatatan'     => null,
            'bayarInvoiceNo'   => null,
        ], true);
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
        $session   = session();

        // ADMIN MUST SELECT PELANGGAN FIRST
        $pelanggan = $session->get($this->sessionKey . '_pelanggan');
        if (!$pelanggan || !is_array($pelanggan) || empty($pelanggan['user_id'])) {
            return $this->response->setJSON([
                'success' => false,
                'message' => 'Silakan pilih pelanggan terlebih dahulu.',
                'xname'   => csrf_token(),
                'xhash'   => csrf_hash()
            ]);
        }

        $keranjang = $session->get($this->sessionKey) ?? [];

        if (isset($keranjang[$id])) {
            unset($keranjang[$id]);
            $keranjang = array_values($keranjang);
            $session->set($this->sessionKey, $keranjang);

            return $this->response->setJSON([
                'success' => true,
                'message' => 'Item berhasil dihapus dari keranjang.',
                'xname'   => csrf_token(),
                'xhash'   => csrf_hash()
            ]);
        }

        return $this->response->setJSON([
            'success' => false,
            'message' => 'Item tidak ditemukan di keranjang.',
            'xname'   => csrf_token(),
            'xhash'   => csrf_hash()
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
     * Get user discount berdasarkan pelanggan yang dipilih admin
     * Berbeda dengan Keranjang reguler yang cek user login,
     * di KeranjangAdmin kita cek pelanggan yang dipilih dari session
     * 
     * @param float $originalDiskon Diskon asli dari database
     * @return float Diskon yang diterapkan (0 jika bukan ULM)
     */
    protected function getUserDiscount(float $originalDiskon): float
    {
        $session = session();
        $pelanggan = $session->get($this->sessionKey . '_pelanggan');

        // DEBUG: Log pelanggan data
        log_message('debug', 'getUserDiscount - Session key: ' . $this->sessionKey . '_pelanggan');
        log_message('debug', 'getUserDiscount - Pelanggan data: ' . json_encode($pelanggan));
        log_message('debug', 'getUserDiscount - Original diskon: ' . $originalDiskon);

        // Jika pelanggan belum dipilih atau tidak ada status, return 0
        if (!$pelanggan || !is_array($pelanggan)) {
            log_message('debug', 'getUserDiscount - RETURN 0: Pelanggan tidak valid');
            return 0;
        }

        // Ambil status pelanggan (ULM atau NON ULM)
        $status = isset($pelanggan['status']) ? strtoupper(trim($pelanggan['status'])) : '';
        log_message('debug', 'getUserDiscount - Status pelanggan: ' . $status);

        // Jika status adalah "ULM", terapkan diskon dari database
        // Jika bukan ULM, diskon = 0
        $result = ($status === 'ULM') ? max(0, $originalDiskon) : 0;
        log_message('debug', 'getUserDiscount - RETURN: ' . $result);

        return $result;
    }

    /**
     * Get pelanggan yang dipilih dari session (admin feature)
     * Endpoint: GET /keranjangadmin/getPelanggan
     */
    public function keranjangGetPelanggan()
    {
        $session = session();
        $pelanggan = $session->get($this->sessionKey . '_pelanggan') ?? null;
        $keranjang = $session->get($this->sessionKey) ?? [];
        $itemsCount = is_array($keranjang) ? count($keranjang) : 0;

        if ($pelanggan && is_array($pelanggan)) {
            return $this->response->setJSON([
                'res' => true,
                'pelanggan' => [
                    'user_id' => $pelanggan['user_id'] ?? null,
                    'name'    => $pelanggan['name'] ?? null,
                    'email'   => $pelanggan['email'] ?? null,
                    'status'  => $pelanggan['status'] ?? null
                ],
                'items_count' => $itemsCount,
                'xname' => csrf_token(),
                'xhash' => csrf_hash()
            ]);
        }

        return $this->response->setJSON([
            'res' => false,
            'pelanggan' => null,
            'items_count' => $itemsCount,
            'msg' => 'Belum ada pelanggan yang dipilih',
            'xname' => csrf_token(),
            'xhash' => csrf_hash()
        ]);
    }

    /**
     * Set pelanggan untuk keranjang admin
     * Endpoint: POST /keranjangadmin/setPelanggan
     */
    public function keranjangSetPelanggan()
    {
        $session = session();
        $selectedUserId = $this->request->getPost('selectedUserId');
        $name = $this->request->getPost('name');
        $email = $this->request->getPost('email');
        $status = $this->request->getPost('status');

        if (!$selectedUserId) {
            return $this->response->setJSON([
                'res' => false,
                'msg' => 'selectedUserId tidak ditemukan.',
                'xname' => csrf_token(),
                'xhash' => csrf_hash()
            ]);
        }

        // simpan pelanggan ke session
        $pelanggan = [
            'user_id' => (int)$selectedUserId,
            'name'    => $name ?? null,
            'email'   => $email ?? null,
            'status'  => $status ?? null
        ];
        $session->set($this->sessionKey . '_pelanggan', $pelanggan);

        // ambil keranjang sekarang
        $keranjang = $session->get($this->sessionKey) ?? [];

        // apakah pelanggan berhak diskon? cek status ULM atau lookup di DB bila status kosong
        $isUlm = false;
        if (!empty($pelanggan['status']) && strtoupper(trim((string)$pelanggan['status'])) === 'ULM') {
            $isUlm = true;
        } else {
            // fallback: cek di DB user_identity bila perlu
            try {
                $modelUser = new MyModel('simlab_account_users');
                $u = $modelUser->getDataById('user_id', (int)$selectedUserId);
                if ($u && isset($u->user_identity) && strtoupper(trim((string)$u->user_identity)) === 'ULM') {
                    $isUlm = true;
                }
            } catch (\Throwable $e) {
                // silent: treat as non-ULM
            }
        }

        // Jika keranjang tidak kosong, recalc diskon & biaya masing-masing item
        $db = \Config\Database::connect();
        $diskonCache = []; // cache ujiDiskon per kode

        foreach ($keranjang as $i => $item) {
            $kode = isset($item['kode']) ? trim((string)$item['kode']) : '';
            $biayaAsli = isset($item['biaya_asli']) ? (float)$item['biaya_asli'] : (float)($item['biaya'] ?? 0);
            $jumlah = isset($item['jumlah']) ? max(1, (int)$item['jumlah']) : 1;

            $appliedDiskon = 0.0;
            if ($isUlm && $kode !== '') {
                if (!array_key_exists($kode, $diskonCache)) {
                    try {
                        $row = $db->table('r_layanan_pengujian')->select('diskon')->where('kode', $kode)->get()->getRow();
                        $diskonCache[$kode] = ($row && isset($row->diskon)) ? (float)$row->diskon : 0.0;
                    } catch (\Throwable $e) {
                        $diskonCache[$kode] = 0.0;
                    }
                }
                $appliedDiskon = max(0, (float)$diskonCache[$kode]);
            } else {
                $appliedDiskon = 0.0;
            }

            // Hitung biaya baru berdasarkan biaya_asli & jumlah & diskon
            $newBiaya = ($biayaAsli * $jumlah) * (1 - ($appliedDiskon / 100));

            // update item di keranjang (pertahankan fields lain)
            $keranjang[$i]['diskon'] = $appliedDiskon;
            $keranjang[$i]['biaya'] = $newBiaya;
            if (!isset($keranjang[$i]['biaya_asli']) || empty($keranjang[$i]['biaya_asli'])) {
                $keranjang[$i]['biaya_asli'] = $biayaAsli;
            }
        }

        // simpan kembali keranjang ke session
        $session->set($this->sessionKey, $keranjang);

        // hitung grand total
        $grandTotal = 0;
        foreach ($keranjang as $it) {
            $grandTotal += isset($it['biaya']) ? (float)$it['biaya'] : 0;
        }

        return $this->response->setJSON([
            'res' => true,
            'msg' => 'Pelanggan disimpan dan diskon keranjang diperbarui.',
            'pelanggan' => $pelanggan,
            'items_count' => count($keranjang),
            'grand_total' => $grandTotal,
            'items' => $keranjang,
            'xname' => csrf_token(),
            'xhash' => csrf_hash()
        ]);
    }
}
