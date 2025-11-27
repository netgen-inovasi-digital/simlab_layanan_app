<?php

namespace Modules\KeranjangAdmin\Controllers;

use App\Models\MyModel;

/**
 * Keranjang Controller untuk Layanan PENGUJIAN (ADMIN VERSION)
 * Extends KeranjangBase untuk reuse common logic
 */
class Keranjang extends KeranjangBase
{
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

        $parameter   = $row['layanan'] ?? '-';
        $alat        = $row['alat'] ?? '-';
        $jumlah      = (int)($row['jumlah'] ?? 0);
        $metodeKode  = $row['metode_kode'] ?? null;
        $diskon      = (float)($row['diskon'] ?? 0);
        $biayaAsli   = (float)($row['biaya_asli'] ?? 0);
        $biayaTotal  = (float)($row['biaya'] ?? 0);

        // Parameter
        $response[] = esc($parameter);

        // Instrumen / Alat / Tempat
        $response[] = esc($alat);

        // Diskon
        $response[] = $diskon > 0 ? $diskon . '%' : '-';

        // Biaya satuan
        if ($diskon > 0) {
            $hargaDiskon = $biayaAsli - ($biayaAsli * ($diskon / 100));
            $biayaTampil  = '<span style="color:red;text-decoration:line-through;">Rp '
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
            $modelMetode = new MyModel('r_metode');
            $metode = $modelMetode->getDataById('metode_kode', $metodeKode);
            if ($metode && isset($metode->nama)) {
                $metodeNama = esc($metode->nama);
            }
        }
        $response[] = $metodeNama;

        // Aksi + total hidden
        $hiddenTotal = '<span class="d-none row-total">Rp ' . number_format($biayaTotal, 0, ',', '.') . '</span>';
        $response[]  = $this->aksiKeranjang($idx, true) . $hiddenTotal;

        return $response;
    }

    /**
     * Process item data dari POST submit
     * Override dari KeranjangBase
     */
    protected function processItemData(array $post): array
    {
        $detUjiKode    = $post['detUjiKode']   ?? null;
        $detAlat       = $post['detAlat']      ?? null;
        $detBiaya      = isset($post['detBiaya']) ? (float)$post['detBiaya'] : 0;
        $detParameter  = $post['detParameter'] ?? null;
        $detDiskon     = isset($post['detDiskon']) ? (float)$post['detDiskon'] : 0;
        $detJumlah     = isset($post['detJumlah']) ? (int)$post['detJumlah'] : 1;
        $detMetode     = $post['detMetode']    ?? null;
        $detNamaLayanan = $post['detNamaLayanan'] ?? null; // nama_layanan dari r_layanan_pengujian

        // DEBUG: Log diskon dari database
        log_message('debug', 'ProcessItem - Diskon dari database: ' . $detDiskon);
        
        // Dapatkan diskon yang sebenarnya diterapkan
        $appliedDiskon = $this->getUserDiscount($detDiskon);
        
        // DEBUG: Log diskon yang diterapkan
        log_message('debug', 'ProcessItem - Diskon diterapkan: ' . $appliedDiskon);

        // Hitung biaya
        $jumlah = max(1, $detJumlah);
        $biayaPerItem = max(0, $detBiaya);
        $biayaSetelahDiskon = $biayaPerItem * (1 - ($appliedDiskon / 100));
        $biayaTotalBaru = $biayaSetelahDiskon * $jumlah;

        return [
            'kode'        => $detUjiKode,
            'layanan'     => $detParameter ?? 'Layanan',
            'alat'        => $detAlat ?? '',
            'biaya_asli'  => $biayaPerItem,
            'diskon'      => $appliedDiskon,
            'jumlah'      => $jumlah,
            'metode_kode' => $detMetode,
            'biaya'       => $biayaTotalBaru,
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
            $sameKode = isset($item['kode']) && (string)$item['kode'] === (string)$itemData['kode'];
            $sameAlat = (isset($item['alat']) ? trim((string)$item['alat']) : '') === trim((string)$itemData['alat']);
            $sameMetode = (isset($item['metode_kode']) ? (int)$item['metode_kode'] : null) === (isset($itemData['metode_kode']) ? (int)$itemData['metode_kode'] : null);

            if ($sameKode && $sameAlat && $sameMetode) {
                // Tambah jumlah
                $keranjang[$idx]['jumlah'] = (int)($item['jumlah'] ?? 0) + (int)$itemData['jumlah'];

                // Ganti metode_kode (update)
                $keranjang[$idx]['metode_kode'] = $itemData['metode_kode'];

                // Pastikan biaya asli & diskon tetap
                $biayaAsli = isset($item['biaya_asli']) ? (float)$item['biaya_asli'] : (float)$itemData['biaya_asli'];
                $disk = isset($item['diskon']) ? (float)$item['diskon'] : (float)$itemData['diskon'];

                // Hitung ulang total biaya baru
                $jumlahBaru = (int)$keranjang[$idx]['jumlah'];
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
                    log_message('warning', "Failed to fetch jenKode from pengujian for kode: {$item['kode']}");
                }
            }

            // Build data detil - sesuai struktur tabel t_layanan_detil
            $detil = [
                'kode_layanan'      => $lnKode,                           // FK ke simlab_t_layanan
                'uji_kode'          => $item['kode'] ?? null,             // FK ke r_layanan_pengujian
                'biaya'             => $item['biaya'] ?? 0,               // Total biaya item ini
                'jumlah'            => $item['jumlah'] ?? 1,              // Jumlah item
                'nama_layanan'      => $item['nama_layanan'] ?? null,     // Nama layanan dari r_layanan_pengujian
                'status_layanan'    => 0,                                 // Status default: 0
                'kode_jenis'        => $jenKodeValue,                     // Kode jenis (2 char)
                'catatan_manajer'   => null,                              // Default null
                'terima_layanan_by' => null,                              // Default null
                'files'             => null,                              // Default null
                'metode_pengujian'  => isset($item['metode_kode']) ? (int)$item['metode_kode'] : null, // FK ke r_metode
            ];

            // Log data yang akan di-insert untuk debugging
            log_message('debug', 'Inserting detail layanan: ' . json_encode($detil));

            // Insert ke tabel detil
            $res = $modelDetil->insertData($detil);
            if (!$res) {
                $error = $db->error();
                log_message('error', 'Insert gagal ke ' . $this->tableLayananDetail . '. Data: ' . json_encode($detil));
                log_message('error', 'DB Error Code: ' . $error['code']);
                log_message('error', 'DB Error Message: ' . $error['message']);

                throw new \RuntimeException('Gagal simpan detail: ' . ($error['message'] ?? 'Unknown error'));
            }
        }
    }

    /**
     * Get data list layanan (override untuk custom query pengujian)
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
                p.paraNama,
                a.alatNama,
                j.jenNama
            ");

            // Joins dari config
            $builder->join('simlab_r_parameter p', 'p.paraKode = lp.' . $cols['parameter'], 'left');
            $builder->join('simlab_r_alat a', 'a.alatKode = lp.' . $cols['alat'], 'left');
            $builder->join('simlab_r_jenis j', 'j.jenKode = lp.' . $cols['jenis'], 'left');

            // Filter kategori
            if ($jenKodeFilter !== '') {
                $builder->where("TRIM(LEFT(lp.{$cols['jenis']}, 2))", $jenKodeFilter);
            }

            $builder->orderBy("lp.{$cols['kode']}", 'ASC');

            $listUji = $builder->get()->getResult();

            // Filter search query
            if ($q !== '') {
                $filtered = [];
                foreach ($listUji as $row) {
                    $fields = [
                        isset($row->paraNama) ? mb_strtolower($row->paraNama, 'UTF-8') : '',
                        isset($row->alatNama) ? mb_strtolower($row->alatNama, 'UTF-8') : '',
                        isset($row->nama_layanan) ? mb_strtolower($row->nama_layanan, 'UTF-8') : '',
                        isset($row->jenNama) ? mb_strtolower($row->jenNama, 'UTF-8') : '',
                        isset($row->kode) ? (string)$row->kode : ''
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
            
            // Get metode list untuk dropdown
            $modelMetode = new MyModel('r_metode');
            $metodeList = $modelMetode->getAllData();
            
            // ADMIN: Cek pelanggan yang dipilih, bukan user yang login
            $pelanggan = $session->get($this->sessionKey . '_pelanggan');
            $pelangganStatus = '';
            if ($pelanggan && is_array($pelanggan) && isset($pelanggan['status'])) {
                $pelangganStatus = strtoupper(trim($pelanggan['status']));
            }

            foreach ($listUji as $row) {
                $response = [];

                // Parameter
                $response[] = esc($row->paraNama ?? '-');

                // Instrumen/Alat
                $response[] = esc($row->alatNama ?? '-');

                // Tentukan diskon yang diperbolehkan berdasarkan status PELANGGAN yang dipilih
                $allowedDiskon = 0;
                if (!empty($row->diskon) && $row->diskon > 0 && $pelangganStatus === 'ULM') {
                    $allowedDiskon = (float)$row->diskon;
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

                // Dropdown Metode Uji
                $selectMetode = '<select class="form-select form-select-sm metode-select" required>';
                $selectMetode .= '<option value="">-- Pilih Metode --</option>';
                foreach ($metodeList as $metode) {
                    $selectMetode .= '<option value="' . esc($metode->metode_kode) . '">' . esc($metode->nama) . '</option>';
                }
                $selectMetode .= '</select>';
                $response[] = $selectMetode;

                // Tombol Aksi
                $jenKodeClean = isset($row->kode_jenis) ? trim(substr($row->kode_jenis, 0, 2)) : '';

                $btnMasukkan = '
                <button type="button" 
                        class="btn btn-success btn-sm btnMasukkan" 
                        data-kode="' . esc($row->kode) . '" 
                        data-alat="' . esc($row->alatNama ?? '') . '" 
                        data-biaya="' . $row->biaya . '" 
                        data-parameter="' . esc($row->paraNama ?? '') . '"
                        data-nama-layanan="' . esc($row->nama_layanan ?? '') . '"
                        data-diskon="' . $allowedDiskon . '" 
                        data-jenKode="' . esc($jenKodeClean) . '"
                        data-jenNama="' . esc($row->jenNama ?? '') . '"
                        title="Masukkan ke keranjang">
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
     * Override keranjangDataList untuk include info pelanggan (admin feature)
     */
    public function keranjangDataList()
    {
        $session = session();
        $keranjang = $session->get($this->sessionKey) ?? [];

        // HAPUS DUPLIKAT berdasarkan kombinasi kode+alat+keterangan
        $unique = [];
        $cleaned = [];
        foreach ($keranjang as $row) {
            $kode = isset($row['kode']) ? trim((string)$row['kode']) : '';
            $alat = isset($row['alat']) ? trim((string)$row['alat']) : '';
            $ket  = isset($row['keterangan']) ? trim((string)$row['keterangan']) : '';
            $key  = md5($kode . '|' . $alat . '|' . $ket);
            if (!isset($unique[$key])) {
                $unique[$key] = true;
                $cleaned[] = $row;
            }
        }
        $session->set($this->sessionKey, $cleaned);

        // Build data untuk response
        $data = [];
        foreach ($cleaned as $idx => $row) {
            $data[] = $this->buildRowData($row, $idx);
        }

        // Get pelanggan info dari session (admin feature)
        $pelanggan = $session->get($this->sessionKey . '_pelanggan') ?? null;
        if ($pelanggan && is_array($pelanggan)) {
            $pelanggan = [
                'user_id' => $pelanggan['user_id'] ?? null,
                'name'    => $pelanggan['name'] ?? null,
                'email'   => $pelanggan['email'] ?? null,
                'status'  => $pelanggan['status'] ?? null
            ];
        } else {
            $pelanggan = null;
        }

        return $this->response->setJSON([
            'items' => $data,
            'pelanggan' => $pelanggan  // Include pelanggan info untuk admin
        ]);
    }
}
