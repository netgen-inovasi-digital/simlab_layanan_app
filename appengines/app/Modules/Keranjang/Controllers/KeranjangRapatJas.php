<?php

namespace Modules\Keranjang\Controllers;

use App\Models\MyModel;

/**
 * Keranjang Controller untuk Layanan RAPAT JAS
 * Extends KeranjangBase untuk reuse common logic
 * Filter khusus: kode_jenis = 'D'
 */
class KeranjangRapatJas extends KeranjangBase
{
  public function __construct()
  {
    // Set jenis layanan untuk rapat jas
    $this->jenisLayanan = 'rapat_jas';

    // Call parent constructor untuk load config
    parent::__construct();

    // 🔍 DEBUG: Log config yang ter-load
    log_message('debug', 'KeranjangRapatJas Config Loaded: ' . json_encode([
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
          log_message('warning', "Failed to fetch kode from pengujian for kode: {$item['kode']}");
        }
      }

      // Build data detil - sesuai struktur tabel t_layanan_detil
      $detil = [
        'kode_layanan' => $kode_layanan,
        'uji_kode' => $item['kode'] ?? null,
        'biaya' => $item['biaya'] ?? 0,
        'jumlah' => $item['jumlah'] ?? 1,
        'nama_layanan' => $item['nama_layanan'] ?? null, // nama_layanan dari r_layanan_pengujian
        'status_layanan' => 0,
        'kode_jenis' => $jenKodeValue,
        'catatan_manajer' => null,
        'terima_layanan_by' => null,
        'files' => null,
      ];

      // Log data yang akan di-insert untuk debugging
      log_message('debug', 'Inserting detail layanan (Rapat JAS): ' . json_encode($detil));

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
   * Override checkout untuk handle upload file surat
   */
  public function keranjangCheckout()
  {
    $session = session();
    $user_id = $session->get('id_user');

    $modelUser = new MyModel('account_users');
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

    $modelPembayaran = new MyModel($this->tablePembayaran);
    $modelLayanan = new MyModel($this->tableLayanan);
    $modelDetil = new MyModel($this->tableLayananDetail);
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

      // 🔥 Upload file surat jika ada
      $uploadedFilePath = null;
      $file = $this->request->getFile('file_surat_rapat_jas');

      if ($file && $file->isValid() && !$file->hasMoved()) {
        // Validasi tipe file
        $allowedMimes = ['application/pdf', 'application/msword', 'application/vnd.openxmlformats-officedocument.wordprocessingml.document', 'image/jpeg', 'image/jpg', 'image/png'];
        if (!in_array($file->getMimeType(), $allowedMimes)) {
          throw new \RuntimeException('Tipe file tidak diizinkan. Hanya PDF, DOC, DOCX, JPG, PNG yang diperbolehkan.');
        }

        // Validasi ukuran file (max 5MB)
        if ($file->getSize() > 5 * 1024 * 1024) {
          throw new \RuntimeException('Ukuran file terlalu besar. Maksimal 5MB.');
        }

        // Generate nama file unik
        $newFileName = 'surat_rapat_jas_' . $kode_layanan . '_' . time() . '.' . $file->getExtension();

        // Upload ke folder rapatJas
        $uploadPath = FCPATH . 'uploads/rapatJas/';

        // Buat folder jika belum ada
        if (!is_dir($uploadPath)) {
          mkdir($uploadPath, 0755, true);
        }

        // Pindahkan file
        $file->move($uploadPath, $newFileName);
        $uploadedFilePath = 'rapatJas/' . $newFileName;

        // 🔥 Ambil kode_detail_layanan (kode dari t_layanan_detil) yang baru saja di-insert
        $kodeDetailLayanan = null;
        $detailLayanan = $db->table($this->tableLayananDetail)
          ->select('kode')
          ->where('kode_layanan', $kode_layanan)
          ->orderBy('kode', 'DESC')
          ->limit(1)
          ->get()
          ->getRow();

        if ($detailLayanan) {
          $kodeDetailLayanan = $detailLayanan->kode;
        }

        // 🔥 Cek apakah sudah ada file untuk kode_layanan ini (1 kode_layanan = 1 file)
        $modelFilesLabJas = new MyModel('t_files_lab_jas');
        $existingFile = $modelFilesLabJas->getWhere(['kode_layanan' => $kode_layanan])->getRow();

        if ($existingFile) {
          // Hapus file lama jika ada
          $oldFilePath = FCPATH . 'uploads/' . $existingFile->file_lab_jas;
          if (file_exists($oldFilePath)) {
            @unlink($oldFilePath);
          }

          // Update record
          $modelFilesLabJas->updateData([
            'kode_detail_layanan' => $kodeDetailLayanan,
            'file_lab_jas' => $uploadedFilePath,
            'status_file' => 0,
            'kirim_by' => $user_id,
          ], 'file_id', $existingFile->file_id);
        } else {
          // Insert record baru
          $modelFilesLabJas->insertData([
            'kode_detail_layanan' => $kodeDetailLayanan,
            'kode_layanan' => $kode_layanan,
            'file_lab_jas' => $uploadedFilePath,
            'status_file' => 0,
            'kirim_by' => $user_id,
          ]);
        }

        log_message('info', 'File surat rapat JAS uploaded successfully: ' . $uploadedFilePath . ' for layanan: ' . $kode_layanan . ' with detail: ' . $kodeDetailLayanan);
      }

      // Commit dan bersihkan keranjang
      $db->transComplete();

      if ($db->transStatus() === false) {
        throw new \RuntimeException('Transaksi database gagal.');
      }

      $session->remove($this->sessionKey);

      $successMsg = 'Checkout berhasil! Data Anda sedang diproses.';
      if ($uploadedFilePath) {
        $successMsg .= ' File surat berhasil diunggah.';
      }

      return $this->response->setJSON([
        'res' => true,
        'msg' => $successMsg,
        'xname' => csrf_token(),
        'xhash' => csrf_hash()
      ]);
    } catch (\Exception $e) {
      if ($db->transStatus() === FALSE) {
        $db->transRollback();
      }

      log_message('error', 'Checkout exception (Rapat JAS): ' . $e->getMessage());
      log_message('error', 'Stack trace: ' . $e->getTraceAsString());

      return $this->response->setJSON([
        'res' => false,
        'msg' => 'Checkout gagal: ' . $e->getMessage(),
        'xname' => csrf_token(),
        'xhash' => csrf_hash()
      ]);
    }
  }

  /**
   * Get data list layanan (override untuk filter kode_jenis = 'D')
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

      // 🔥 FILTER UTAMA: kode_jenis = 'D' (Rapat JAS)
      $builder->where("lp.{$cols['jenis']}", 'D');

      // Filter kategori tambahan (jika ada)
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
                        class="btn btn-success btn-sm btnMasukkan" 
                        data-kode="' . esc($row->kode) . '" 
                        data-alat="' . esc($row->nama ?? '') . '" 
                        data-biaya="' . $row->biaya . '" 
                        data-parameter="' . esc($row->nama ?? '') . '"
                        data-nama-layanan="' . esc($row->nama_layanan ?? '') . '"
                        data-diskon="' . $allowedDiskon . '" 
                        data-kode="' . esc($jenKodeClean) . '"
                        data-nama="' . esc($row->nama ?? '') . '"
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
          "filter_kode_jenis" => "D (Rapat JAS)",
          "filter_jenKode" => $jenKodeFilter,
          "search_query" => $q,
          "user_identity" => $userIdentity
        ]
      ]);
    } catch (\Exception $e) {
      log_message('error', 'Error in keranjangDataListLayanan (Rapat JAS): ' . $e->getMessage());
      log_message('error', 'Stack trace: ' . $e->getTraceAsString());

      return $this->response->setStatusCode(500)->setJSON([
        'error' => true,
        'message' => 'Terjadi kesalahan saat memuat data layanan',
        'detail' => ENVIRONMENT === 'development' ? $e->getMessage() : null,
        'items' => []
      ]);
    }
  }
}
