<?php

namespace Modules\Keranjang\Controllers;

use App\Models\MyModel;

/**
 * Keranjang Controller untuk Layanan SEWA RUANGAN LAB
 * Extends KeranjangBase untuk reuse common logic
 */
class KeranjangLab extends KeranjangBase
{
    /**
     * Array untuk menyimpan ID detail layanan yang baru disimpan
     * Digunakan untuk menyimpan file pendukung
     */
    protected $detailIds = [];

    public function __construct()
    {
        // Set jenis layanan untuk sewa ruangan lab
        $this->jenisLayanan = 'lab';

        // Call parent constructor untuk load config
        parent::__construct();
    }

    /**
     * Build row data untuk display di keranjang lab
     * Override dari KeranjangBase
     */
    protected function buildRowData(array $row, int $idx): array
    {
        $response = [];

        $parameter = $row['layanan'] ?? '-';
        $ruangan = $row['ruangan'] ?? '-';
        $jumlah = (int) ($row['jumlah'] ?? 0);
        $keterangan = $row['keterangan'] ?? '';
        $diskon = (float) ($row['diskon'] ?? 0);
        $biayaAsli = (float) ($row['biaya_asli'] ?? 0);
        $biayaTotal = (float) ($row['biaya'] ?? 0);

        // Parameter / Nama Layanan
        $response[] = esc($parameter);

        // Nama Ruangan Lab
        $response[] = esc($ruangan);

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

        // Jumlah hari
        $response[] = $jumlah . ' hari';

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
        $detRuangan = $post['detRuangan'] ?? null;
        $detBiaya = isset($post['detBiaya']) ? (float) $post['detBiaya'] : 0;
        $detParameter = $post['detParameter'] ?? null;
        $detDiskon = isset($post['detDiskon']) ? (float) $post['detDiskon'] : 0;
        $detJumlah = isset($post['detJumlah']) ? (int) $post['detJumlah'] : 1;
        $detKeterangan = trim($post['detKeterangan'] ?? '');
        $jenKode = $post['jenKode'] ?? 'C'; // Default kode jenis untuk ruangan lab

        // Dapatkan diskon yang sebenarnya diterapkan
        $appliedDiskon = $this->getUserDiscount($detDiskon);

        // Hitung biaya
        $jumlah = max(1, $detJumlah);
        $biayaPerHari = max(0, $detBiaya);
        $biayaSetelahDiskon = $biayaPerHari * (1 - ($appliedDiskon / 100));
        $biayaTotalBaru = $biayaSetelahDiskon * $jumlah;

        return [
            'kode' => $detUjiKode,
            'layanan' => $detParameter ?? 'Layanan',
            'ruangan' => $detRuangan ?? '',
            'biaya_asli' => $biayaPerHari,
            'diskon' => $appliedDiskon,
            'jumlah' => $jumlah,
            'keterangan' => $detKeterangan,
            'biaya' => $biayaTotalBaru,
            'jenKode' => $jenKode, // Simpan jenKode untuk digunakan saat save
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
            $sameRuangan = (isset($item['ruangan']) ? trim((string) $item['ruangan']) : '') === trim((string) $itemData['ruangan']);

            if ($sameKode && $sameRuangan) {
                // Item sudah ada, update jumlah dan biaya
                $jumlahLama = (int) ($item['jumlah'] ?? 0);
                $jumlahBaru = (int) $itemData['jumlah'];
                $jumlahTotal = $jumlahLama + $jumlahBaru;

                $keranjang[$idx]['jumlah'] = $jumlahTotal;

                // Recalculate biaya total
                $biayaPerHari = (float) ($item['biaya_asli'] ?? 0);
                $diskon = (float) ($item['diskon'] ?? 0);
                $biayaSetelahDiskon = $biayaPerHari * (1 - ($diskon / 100));
                $keranjang[$idx]['biaya'] = $biayaSetelahDiskon * $jumlahTotal;

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
    protected function saveDetailLayanan(int $lnKode, array $keranjang): void
    {
        $modelDetil = new MyModel($this->tableLayananDetail);
        $db = \Config\Database::connect();

        foreach ($keranjang as $i => $item) {
            // Ambil jenKode dari keranjang atau fallback ke tabel pengujian supaya sesuai FK
            $jenKodeValue = null;

            if (isset($item['jenKode'])) {
                $jenKodeValue = trim((string) $item['jenKode']);
                if ($jenKodeValue === '') {
                    $jenKodeValue = null;
                }
            }

            if ($jenKodeValue === null && isset($item['kode']) && $item['kode'] !== '') {
                $builderJen = $db->table($this->tablePengujian);
                $builderJen->select('kode_jenis');
                $builderJen->where('uji_kode', $item['kode']);
                $builderJen->limit(1);
                $rowJen = $builderJen->get()->getRow();

                if ($rowJen && isset($rowJen->kode_jenis)) {
                    $jenKodeValue = trim((string) $rowJen->kode_jenis);
                    if ($jenKodeValue === '') {
                        $jenKodeValue = null;
                    }
                }
            }

            // Build data detil - sesuai struktur tabel t_layanan_detil
            $detil = [
                'kode_layanan' => $lnKode,
                'uji_kode' => $item['kode'] ?? null,
                'nama_layanan' => $item['ruangan'] ?? '',
                'kode_jenis' => $jenKodeValue ?? 'C',
                'jumlah' => (int) ($item['jumlah'] ?? 0),
                'biaya' => (float) ($item['biaya'] ?? 0),
                'status_layanan' => 0,
            ];

            // Insert ke tabel detil dan dapatkan ID
            $insertedId = $modelDetil->insertData($detil, true);
            if (!$insertedId) {
                log_message('error', 'Failed to insert detail layanan lab: ' . json_encode($detil));
                throw new \RuntimeException('Gagal menyimpan detail layanan ruangan lab.');
            }

            // Simpan ID detail untuk digunakan saat upload file
            $this->detailIds[] = (int) $insertedId;
        }
    }

    /**
     * Override checkout untuk handle file upload
     */
    public function keranjangCheckout()
    {
        $session = session();
        $user_id = $session->get('id_user');

        $modelUser = new MyModel('simlab_account_users');
        $userRow = $modelUser->getDataById('user_id', $user_id);

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

        // Reset array untuk menyimpan ID detail
        $this->detailIds = [];

        try {
            // Simpan data utama layanan
            $lnKode = $this->saveMainLayanan($userRow, $totalBiaya);

            if (!$lnKode) {
                throw new \RuntimeException('Gagal menyimpan data layanan utama');
            }

            // Simpan data pembayaran
            $this->savePembayaran($lnKode, $totalBiaya);

            // Simpan detail layanan (akan mengisi $this->detailIds)
            $this->saveDetailLayanan($lnKode, $keranjang);

            // Handle upload file pendukung jika ada
            $uploadedFilePath = null;
            $file = $this->request->getFile('filePendukung');

            if ($file && $file->isValid() && !$file->hasMoved()) {
                // Validasi ukuran file (max 2MB)
                if ($file->getSize() > 2 * 1024 * 1024) {
                    throw new \RuntimeException('Ukuran file terlalu besar. Maksimal 2MB.');
                }

                // Validasi tipe file
                $allowedTypes = [
                    'application/pdf',
                    'application/msword',
                    'application/vnd.openxmlformats-officedocument.wordprocessingml.document',
                    'application/vnd.ms-excel',
                    'application/vnd.openxmlformats-officedocument.spreadsheetml.sheet',
                    'image/jpeg',
                    'image/jpg',
                    'image/png'
                ];

                if (!in_array($file->getMimeType(), $allowedTypes)) {
                    throw new \RuntimeException('Tipe file tidak diizinkan. Hanya PDF, Word, Excel, dan Gambar yang diperbolehkan.');
                }

                // Generate nama file unik
                $newName = 'file_ruangan_' . $lnKode . '_' . time() . '.' . $file->getExtension();

                // Pindahkan file ke folder uploads
                $uploadPath = WRITEPATH . '../uploads/files_ruangan/';
                if (!is_dir($uploadPath)) {
                    mkdir($uploadPath, 0755, true);
                }

                if ($file->move($uploadPath, $newName)) {
                    $uploadedFilePath = 'uploads/files_ruangan/' . $newName;
                } else {
                    throw new \RuntimeException('Gagal mengupload file pendukung.');
                }
            }

            // Simpan data file ke t_files_ruangan untuk setiap detail layanan
            if ($uploadedFilePath && !empty($this->detailIds)) {
                $modelFiles = new MyModel('t_files_ruangan');

                foreach ($this->detailIds as $detailId) {
                    $fileData = [
                        'kode_detail_layanan' => $detailId,
                        'file_pendukung' => $uploadedFilePath,
                        'status_file' => 0,
                        'kirim_by' => $user_id,
                    ];

                    $insertFileResult = $modelFiles->insertData($fileData);
                    if (!$insertFileResult) {
                        throw new \RuntimeException('Gagal menyimpan data file pendukung.');
                    }
                }
            }

            // Commit dan bersihkan keranjang
            $db->transComplete();

            if ($db->transStatus() === false) {
                throw new \RuntimeException('Transaksi database gagal.');
            }

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

            // Hapus file yang sudah diupload jika transaksi gagal
            if (isset($uploadedFilePath) && $uploadedFilePath && file_exists(WRITEPATH . '../' . $uploadedFilePath)) {
                @unlink(WRITEPATH . '../' . $uploadedFilePath);
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
     * Override saveMainLayanan untuk include tgl_pelaksanaan dan jenis_layanan = 'lab'
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
            'lnTgl' => date('Y-m-d H:i:s'),
            'lnStatus' => 1,
            'kuisioner' => 0,
            'tgl_pelaksanaan' => $tglPelaksanaan
        ];

        try {
            $insertLayananId = $modelLayanan->insertData($dataToInsert, true);

            if (!$insertLayananId) {
                log_message('error', 'Failed to insert main layanan lab');
                throw new \RuntimeException('Gagal menyimpan data layanan ruangan lab.');
            }

            return (int) $insertLayananId;

        } catch (\Exception $e) {
            throw $e;
        }
    }

    /**
     * Get data list layanan ruangan lab (override untuk custom query lab)
     */
    public function keranjangDataListLayanan()
    {
        $session = session();
        $user_id = $session->get('id_user');

        // Ambil info user untuk cek user_identity
        $modelUser = new MyModel('simlab_account_users');
        $user = $modelUser->getDataById('user_id', $user_id);
        $userIdentity = '';
        if ($user && isset($user->user_identity)) {
            $userIdentity = strtoupper(trim($user->user_identity));
        }

        // Baca parameter filter dari GET request
        $qRaw = trim((string) ($this->request->getGet('q') ?? $this->request->getGet('search') ?? ''));
        $q = $qRaw !== '' ? mb_strtolower($qRaw, 'UTF-8') : '';

        // Baca jenKode sebagai filter kategori
        $jenKodeFilter = trim((string) ($this->request->getGet('jenKode') ?? ''));

        // SANITASI: bersihkan jenKode dari query parameters yang salah
        if ($jenKodeFilter !== '') {
            $jenKodeFilter = rawurldecode($jenKodeFilter);
            $jenKodeFilter = preg_replace('/[?&].*$/', '', $jenKodeFilter);
            if (strpos($jenKodeFilter, '=') !== false) {
                $parts = explode('=', $jenKodeFilter);
                $jenKodeFilter = trim($parts[0]);
                if (in_array(strtolower($jenKodeFilter), ['jenkode', 'jenis', 'kode'])) {
                    $jenKodeFilter = isset($parts[1]) ? trim($parts[1]) : '';
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
                lp.{$cols['alat']} as kode_ruangan,
                p.paraNama,
                j.jenNama
            ");

            // Joins dari config - TIDAK perlu JOIN ke simlab_r_ruangan (tabel tidak ada)
            // Nama ruangan sudah ada di kolom nama_layanan
            $builder->join('simlab_r_parameter p', 'p.paraKode = lp.' . $cols['parameter'], 'left');
            $builder->join('simlab_r_jenis j', 'j.jenKode = lp.' . $cols['jenis'], 'left');

            // ⚠️ FILTER PENTING: Hanya tampilkan ruangan lab (kode_jenis = 'C')
            $builder->where('lp.' . $cols['jenis'], 'C');

            // Filter kategori tambahan (optional)
            if ($jenKodeFilter !== '' && $jenKodeFilter !== 'C') {
                $builder->where("TRIM(LEFT(lp.{$cols['jenis']}, 2))", $jenKodeFilter);
            }

            $builder->orderBy("lp.{$cols['kode']}", 'ASC');

            $listUji = $builder->get()->getResult();

            // Debug: log query yang dijalankan
            log_message('debug', 'Lab Query SQL: ' . $db->getLastQuery());
            log_message('debug', 'Lab Query Result Count: ' . count($listUji));

            // Filter search query
            if ($q !== '') {
                $filtered = [];
                foreach ($listUji as $row) {
                    $fields = [
                        isset($row->paraNama) ? mb_strtolower($row->paraNama, 'UTF-8') : '',
                        isset($row->nama_layanan) ? mb_strtolower($row->nama_layanan, 'UTF-8') : '',
                        isset($row->jenNama) ? mb_strtolower($row->jenNama, 'UTF-8') : '',
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

            $data = [];
            if (!empty($listUji)) {
                foreach ($listUji as $row) {
                    $response = [];

                    // Parameter
                    $response[] = esc($row->paraNama ?? '-');

                    // Nama Ruangan (dari kolom nama_layanan)
                    $response[] = esc($row->nama_layanan ?? '-');

                    // Biaya per hari
                    $biaya = isset($row->biaya) ? (float) $row->biaya : 0;
                    $diskonDb = isset($row->diskon) ? (float) $row->diskon : 0;
                    $diskonApplied = $this->getUserDiscount($diskonDb);

                    $biayaTampil = 'Rp ' . number_format($biaya, 0, ',', '.');
                    if ($diskonApplied > 0) {
                        $biayaTampil .= ' <span class="badge bg-danger ms-1">-' . $diskonApplied . '%</span>';
                    }
                    $response[] = $biayaTampil . '<small class="text-muted"> / hari</small>';

                    // KOLOM JUMLAH HARI (inline input)
                    $response[] = '<input type="number" class="form-control form-control-sm text-center jumlah" min="1" value="1" style="width: 80px;">';

                    // KOLOM KETERANGAN (inline input)
                    $response[] = '<input type="text" class="form-control form-control-sm keterangan" placeholder="Keterangan..." style="min-width: 150px;">';

                    // Tombol masukkan dengan data attributes
                    $jenKodeClean = isset($row->kode_jenis) ? trim(substr($row->kode_jenis, 0, 2)) : '';

                    $response[] = '<button type="button" class="btn btn-sm btn-primary btnMasukkanLab" '
                        . 'data-kode="' . esc($row->kode ?? '', 'attr') . '" '
                        . 'data-parameter="' . esc($row->paraNama ?? '', 'attr') . '" '
                        . 'data-ruangan="' . esc($row->nama_layanan ?? '', 'attr') . '" '
                        . 'data-biaya="' . $biaya . '" '
                        . 'data-diskon="' . $diskonDb . '" '
                        . 'data-jenKode="' . esc($jenKodeClean, 'attr') . '" '
                        . 'data-jenNama="' . esc($row->jenNama ?? '', 'attr') . '" '
                        . 'title="Masukkan ke keranjang sewa ruangan lab">'
                        . '<i class="bi bi-plus-circle"></i> Masukkan'
                        . '</button>';

                    $data[] = $response;
                }
            }

            return $this->response->setJSON([
                'items' => $data,
                'total' => count($data),
                'debug' => [
                    'filter_jenKode' => $jenKodeFilter,
                    'search_query' => $q,
                    'user_identity' => $userIdentity,
                    'total_before_filter' => count($listUji)
                ]
            ]);
        } catch (\Exception $e) {
            log_message('error', 'Error in keranjangDataListLayanan (Lab): ' . $e->getMessage());
            return $this->response->setStatusCode(500)->setJSON([
                'error' => true,
                'message' => 'Terjadi kesalahan saat memuat data layanan',
                'detail' => ENVIRONMENT === 'development' ? $e->getMessage() : null,
                'items' => []
            ]);
        }
    }

    /**
     * Get kategori list untuk filter (override untuk hanya menampilkan ruangan lab)
     */
    public function kategoriList()
    {
        try {
            $categories = $this->getCategories();
            return $this->response->setJSON([
                'items' => $categories
            ]);
        } catch (\Exception $e) {
            return $this->response->setJSON([
                'items' => []
            ]);
        }
    }
}
