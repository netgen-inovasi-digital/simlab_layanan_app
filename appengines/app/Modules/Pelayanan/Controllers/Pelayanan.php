<?php

namespace Modules\Pelayanan\Controllers;

use App\Controllers\BaseController;
use Modules\Pelayanan\Models\PelayananModel;

class Pelayanan extends BaseController
{
  private $table = 't_layanan';
  private $id = 'kode_layanan';
  protected $encrypter;
  private $sessionKey = 'keranjang';
  /** @var PelayananModel */
  protected $pelayananModel;

  private function getStatusClass($status)
  {
    return match ($status) {
      0 => 'secondary',  // Pendaftaran
      1 => 'info',       // Review Manajer
      2 => 'danger',     // Ditolak
      3 => 'info',       // Review Admin
      4 => 'primary',    // Pengujian
      5 => 'primary',    // Proses LHUS
      6 => 'success',    // LHUS Disetujui
      7 => 'primary',    // Proses LHU
      8 => 'success',    // LHU Disetujui
      9 => 'success',    // Selesai
      default => 'secondary'
    };
  }

  private function getStatusText($status)
  {
    return match ($status) {
      0 => 'Pendaftaran',
      1 => 'Review Petugas',
      2 => 'Ditolak',
      3 => 'In Review Petugas',
      4 => 'Pengujian Dilakukan',
      5 => 'Verifikasi Hasil Uji',
      6 => 'Verifikasi LHU',
      7 => 'Verifikasi LHU',
      8 => 'Penerbitan LHU',
      9 => 'Selesai',
      default => 'Tidak Diketahui'
    };
  }

  public function __construct()
  {
    $this->encrypter = \Config\Services::encrypter();
    $this->pelayananModel = new PelayananModel();
  }

  public function getTrackingData($id)
  {
    try {
      $realId = $this->encrypter->decrypt(hex2bin($id));
      $data = $this->pelayananModel->getLayananByKode($realId);

      if (!$data) {
        return $this->response->setJSON([
          'success' => false,
          'message' => 'Data tidak ditemukan'
        ]);
      }

      // Get detail items with grouped data
      $details = $this->pelayananModel->getTrackingDetails($realId);

      // Get log sampel (timestamps) if available
      $logRow = $this->pelayananModel->getLogSampel($realId);
      $log = null;
      if ($logRow) {
        $log = [
          'pengecekan' => $logRow->pengecekan ? date('d-m-Y H:i', strtotime($logRow->pengecekan)) : null,
          'pengujian' => $logRow->pengujian ? date('d-m-Y H:i', strtotime($logRow->pengujian)) : null,
          'verifikasi_hasil_uji' => $logRow->verifikasi_hasil_uji ? date('d-m-Y H:i', strtotime($logRow->verifikasi_hasil_uji)) : null,
          'penerbitan_lhus' => $logRow->penerbitan_lhus ? date('d-m-Y H:i', strtotime($logRow->penerbitan_lhus)) : null,
          'verifikasi_lhu' => $logRow->verifikasi_lhu ? date('d-m-Y H:i', strtotime($logRow->verifikasi_lhu)) : null,
          'penerbitan_lhu' => $logRow->penerbitan_lhu ? date('d-m-Y H:i', strtotime($logRow->penerbitan_lhu)) : null,
        ];
      }

      return $this->response->setJSON([
        'success' => true,
        'data' => [
          'kode' => $data->kode_layanan,
          'statusText' => $this->getStatusText((int) $data->status_layanan),
          'tanggal' => date('d-m-Y', strtotime($data->tanggal_checkout)),
          'noTransaksi' => $data->no_invoice ?? 'Belum tersedia',
          'details' => array_map(function ($detail) {
            $statusGroup = isset($detail->status_layanan) ? (int) $detail->status_layanan : null;

            if ($statusGroup === 0) {
              $statusHtml = '<span class="badge bg-warning">Pending</span>';
            } elseif ($statusGroup === 1) {
              $statusHtml = '<span class="badge bg-success">Diterima</span>';
            } elseif ($statusGroup === 2) {
              $statusHtml = '<span class="badge bg-danger">Ditolak</span>';
            } else {
              $statusHtml = '<span class="badge bg-secondary">Belum Diproses</span>';
            }
            return [
              'parameter' => $detail->detParameter ?? '-',
              'biaya' => number_format((float) ($detail->biaya ?? 0), 0, ',', '.'),
              'jumlah' => (int) ($detail->jumlah ?? 0),
              'status' => $statusHtml
            ];
          }, $details),
          'log' => $log
        ]
      ]);
    } catch (\Exception $e) {
      return $this->response->setJSON([
        'success' => false,
        'message' => 'Terjadi kesalahan saat memuat data'
      ]);
    }
  }

  public function index()
  {
    $session = session();
    $user_id = $session->get('id_user');

    $categories = $this->pelayananModel->getCategories();

    // Normalisasi categories
    $normalized = [];
    if (!empty($categories)) {
      foreach ($categories as $c) {
        $kode = isset($c->kode) ? trim((string) $c->kode) : '';
        $nama = (isset($c->nama) && trim((string) $c->nama) !== '') ? trim((string) $c->nama) : $kode;
        if ($kode !== '') {
          $normalized[] = (object) [
            'kode' => $kode,
            'nama' => $nama
          ];
        }
      }
    }

    $data = [
      'title' => 'Data Pelayanan',
      'user' => $this->pelayananModel->getUserById((int) $user_id),
      'categories' => array_values($normalized),
    ];

    return view('Modules\Pelayanan\Views\v_pelayanan', $data);
  }

  public function dataList()
  {
    $session = session();
    $user_id = (int) $session->get('id_user');

    // Ambil user login (opsional buat cek kuisioner fallback)
    $user = $this->pelayananModel->getUserById($user_id);

    if (!$user || !$user_id) {
      return $this->response->setJSON(["items" => []]);
    }

    $data = [];
    $list = $this->pelayananModel->getUserLayananList($user_id);

    if (empty($list)) {
      return $this->response->setJSON(["items" => []]);
    }

    // --- Siapkan map pembayaran terakhir per kode_layanan (satu query) ---
    $lnKodes = array_map(fn($r) => (int) $r->kode_layanan, $list);

    $payMap = $this->pelayananModel->getLatestPaymentMap($lnKodes);

    foreach ($list as $row) {
      $id = bin2hex($this->encrypter->encrypt($row->kode_layanan));
      $response = [];

      // No Transaksi + Tanggal
      $noTransaksi = (isset($row->no_invoice) && trim((string) $row->no_invoice) !== '')
        ? $row->no_invoice
        : 'Belum tersedia';

      $tanggal = !empty($row->tanggal_checkout) ? date('d-m-Y', strtotime($row->tanggal_checkout)) : '-';
      $response[] = '<div>' . esc($noTransaksi) . '<br><small>' . esc($tanggal) . '</small></div>';

      // Status layanan dengan tracking
      $statusText = $this->getStatusText((int) ($row->status_layanan ?? 0));
      $response[] = '<div class="d-flex gap-2 align-items-center">' .
        '<button class="btn btn-sm btn-outline-primary" onclick="showTrackingModal(\'' . $id . '\', \'' . $row->kode_layanan . '\', ' . (int) ($row->status_layanan ?? 0) . ')">' .
        '<i class="bi bi-activity"></i> ' . $statusText . '</button>' .
        '</div>';

      // Kuisioner (ambil dari row dulu, kalau kosong fallback dari user)
      $kuisionerVal = 0;
      if (isset($row->kuisioner) && $row->kuisioner !== '') {
        $kuisionerVal = (int) $row->kuisioner;
      } else {
        $kuFields = ['kuisioner', 'user_kuisioner', 'lnKuisioner', 'ln_kuisioner', 'kuisioner_user'];
        foreach ($kuFields as $kf) {
          if (isset($user->{$kf}) && $user->{$kf} !== '') {
            $kuisionerVal = (int) $user->{$kf};
            break;
          }
        }
      }

      // Pembayaran terakhir untuk kode_layanan ini (pakai map hasil query)
      $lnKodeInt = (int) $row->kode_layanan;
      $bayarStatusVal = isset($payMap[$lnKodeInt]) ? $payMap[$lnKodeInt]['status'] : 0;
      $no_invoice = isset($payMap[$lnKodeInt]) ? $payMap[$lnKodeInt]['inv'] : null;

      // statusBayar: 1 = sudah bayar, 0 = belum
      if ($bayarStatusVal === 1) {
        $response[] = '<button class="btn btn-sm btn-success"><i class="bi bi-credit-card"></i> Sudah Bayar</button>';
      } else {
        $response[] = '<button class="btn btn-sm btn-info"><i class="bi bi-credit-card"></i> Belum Bayar</button>';
      }

      // Akses LHU & kuisioner
      $lnStatusVal = (int) ($row->status_layanan ?? 0);
      $canFillKuesioner = ($kuisionerVal !== 1 && $lnStatusVal === 9);
      $canViewLhu = ($kuisionerVal === 1 && $bayarStatusVal === 1 && $lnStatusVal === 9);
      $lhuInfo = $this->detectLhuFile($row);

      $lhuButtons = [];

      if ($canFillKuesioner) {
        $lhuButtons[] = '<button class="btn btn-sm btn-warning" onclick="loadContent(\'pelayanan/kuesioner/' . $id . '\')"><i class="bi bi-chat-square-text"></i> Isi Kuisioner</button>';
      }

      if ($lhuInfo['has'] && $canViewLhu) {
        $lhuButtons[] = '<button class="btn btn-sm btn-outline-primary" onclick="showPelayananLhuHistory(\'' . $id . '\')"><i class="bi bi-eye"></i> LHU</button>';
      } elseif (!$canFillKuesioner) {
        $reason = 'File LHU tidak dapat diakses.';
        if ($lhuInfo['has'] && !$canViewLhu) {
          if ($kuisionerVal !== 1) {
            $reason = 'Isi kuisioner';
          } elseif ($bayarStatusVal !== 1) {
            $reason = 'Belum bayar';
          } elseif ($lnStatusVal !== 9) {
            $reason = 'LHU diproses';
          }
        } elseif (!$lhuInfo['has']) {
          $reason = 'LHU diproses';
        }
        $lhuButtons[] = '<button class="btn btn-sm btn-secondary" disabled><i class="bi bi-eye-slash"></i> ' . esc($reason) . '</button>';
      }

      $buttonHtml = '';
      foreach ($lhuButtons as $btnHtml) {
        $buttonHtml .= '<div>' . $btnHtml . '</div>';
      }

      $response[] = '<div class="d-flex flex-column gap-2 align-items-start">' . $buttonHtml . '</div>';

      // Aksi detail (masking kode_layanan via enkripsi)
      // $response[] = '<a href="javascript:void(0)" onclick="loadDetail(\'' . $id . '\')" class="btn btn-sm btn-info">Lihat pesanan</a>';

      $data[] = $response;
    }

    return $this->response->setJSON(["items" => $data]);
  }

  public function detail($id)
  {
    $id = $this->encrypter->decrypt(hex2bin($id));
    $get = $this->pelayananModel->getLayananByKode($id);

    return view('Modules\Pelayanan\Views\v_detail', ['data' => $get]);
  }


  public function detailList($id = null)
  {
    if (!$id) {
      return $this->response->setJSON(['items' => []]);
    }

    try {
      $kode = $this->encrypter->decrypt(hex2bin($id));
    } catch (\Exception $e) {
      return $this->response->setJSON(['items' => []]);
    }

    // Encrypted hex parent
    $encLnId = bin2hex($this->encrypter->encrypt($kode));

    $rows = $this->pelayananModel->getDetailItems($kode);

    $data = [];
    $no = 1;

    foreach ($rows as $row) {
      $response = [];
      $response[] = $no++;
      $response[] = $row->nama_layanan ?? '-';
      $response[] = isset($row->detBiaya) ? number_format($row->detBiaya, 0, ',', '.') : '-';
      $response[] = isset($row->jumlah) ? (int) $row->jumlah : 0;
      $response[] = isset($row->metode_nama) && !empty($row->metode_nama) ? esc($row->metode_nama) : '-';

      // Status grouping (0 = pending, 1 = diterima, 2 = ditolak)
      $statusGroup = isset($row->detStatusGroup) ? (int) $row->detStatusGroup : null;

      if ($statusGroup === 0) {
        $statusHtml = '<span class="badge bg-warning ">Pending</span>';
      } elseif ($statusGroup === 1) {
        $statusHtml = '<span class="badge bg-success">Diterima</span>';
      } elseif ($statusGroup === 2) {
        $statusHtml = '<span class="badge bg-danger">Ditolak</span>';
      } else {
        $statusHtml = '<span class="badge bg-secondary">Belum Diproses</span>';
      }

      $response[] = $statusHtml;

      $ujiKodeInt = (int) $row->uji_kode;
      $encLnForBtn = $encLnId;

      $data[] = $response;
    }

    return $this->response->setJSON(['items' => $data]);
  }


  public function checkVerified()
  {
    $session = session();
    $user_id = $session->get('id_user');
    $user = $this->pelayananModel->getUserById((int) $user_id);

    if (!$user) {
      return $this->response->setJSON(['verified' => false, 'msg' => 'User tidak ditemukan.']);
    }

    if ((int) $user->verifikasi === 1) {
      return $this->response->setJSON(['verified' => true, 'msg' => 'Akun sudah terverifikasi.']);
    } else {
      return $this->response->setJSON([
        'verified' => false,
        'msg' => 'Akun belum diverifikasi. Silakan unggah bukti atau tunggu verifikasi.
             '
      ]);
    }
  }

  public function getSampleIdentity($kode_layanan = null)
  {
    if (!$kode_layanan) {
      return $this->response->setJSON([
        'success' => false,
        'message' => 'Kode layanan tidak ditemukan'
      ]);
    }

    try {
      $sampleData = $this->pelayananModel->getSampleIdentityRow($kode_layanan);

      if (!$sampleData) {
        return $this->response->setJSON([
          'success' => false,
          'message' => 'Data identitas sampel tidak ditemukan'
        ]);
      }

      // Ambil surat pengantar dari t_layanan (kolom: surat_pertanyaan)
      $layanan = $this->pelayananModel->getLayananByKode($kode_layanan);
      $suratPengantar = $layanan->surat_pertanyaan ?? null;

      return $this->response->setJSON([
        'success' => true,
        'data' => [
          'jenis' => $sampleData->jenis ?? '-',
          'kemasan' => $sampleData->kemasan ?? '-',
          'sifat' => $sampleData->sifat ?? '-',
          'sisa' => $sampleData->sisa ?? '-',
          'deskripsi' => $sampleData->deskripsi ?? '-',
          'keterangan_khusus' => $sampleData->keterangan_khusus ?? '-',
          'surat_pengantar' => $suratPengantar,
          'surat_pengantar_url' => $suratPengantar ? base_url('uploads/surat_pengantar/' . $suratPengantar) : null,
        ]
      ]);
    } catch (\Exception $e) {
      log_message('error', 'Error fetching sample identity: ' . $e->getMessage());
      return $this->response->setJSON([
        'success' => false,
        'message' => 'Terjadi kesalahan saat memuat data identitas sampel'
      ]);
    }
  }

  public function lhuList($id = null)
  {
    if (!$id) {
      return $this->response->setJSON(['items' => []]);
    }

    $session = session();
    $userId = (int) ($session->get('id_user') ?? 0);
    if ($userId <= 0) {
      return $this->response->setJSON(['items' => []]);
    }

    try {
      $kode_layanan = $this->encrypter->decrypt(hex2bin($id));
    } catch (\Throwable $e) {
      try {
        $kode_layanan = $this->encrypter->decrypt($id);
      } catch (\Throwable $e2) {
        return $this->response->setJSON(['items' => []]);
      }
    }

    $layanan = $this->pelayananModel->getLayananByKode($kode_layanan);

    if (!$layanan || (int) ($layanan->user_id ?? 0) !== $userId) {
      return $this->response->setJSON(['items' => []]);
    }

    try {
      $rows = $this->pelayananModel->getLhuFiles($kode_layanan);
    } catch (\Throwable $e) {
      log_message('error', 'Pelayanan::lhuList error: ' . $e->getMessage());
      return $this->response->setJSON(['items' => []]);
    }

    if (empty($rows)) {
      return $this->response->setJSON(['items' => []]);
    }

    $items = [];
    $transLabel = !empty($layanan->no_invoice)
      ? 'No. Transaksi ' . $layanan->no_invoice
      : 'kode layanan ' . $kode_layanan;
    foreach ($rows as $index => $row) {
      $tanggal = '-';
      if (!empty($row->tanggal_terbit)) {
        try {
          $tanggal = date('d/m/Y H:i', strtotime($row->tanggal_terbit));
        } catch (\Throwable $e) {
          $tanggal = $row->tanggal_terbit;
        }
      }

      $fileUrl = null;
      if (!empty($row->file)) {
        if (preg_match('/^https?:\/\//i', $row->file)) {
          $fileUrl = $row->file;
        } else {
          $fileUrl = base_url('uploads/lhu/' . ltrim($row->file, '/'));
        }
      }

      $phoneRaw = $row->admin_phone ?? $row->admin_phone_alt ?? null;
      $waUrl = null;
      $adminName = $row->admin_full_name
        ?? $row->admin_username
        ?? $row->admin_name_alt
        ?? 'Admin';

      if (!empty($phoneRaw)) {
        $waDigits = $this->normalizePhoneForWhatsapp($phoneRaw);
        if ($waDigits !== '') {
          $message = 'Halo ' . $adminName . ', saya ingin melakukan pengujian ulang untuk LHU ' . $transLabel . '.';
          $waUrl = 'https://wa.me/' . $waDigits . '?text=' . rawurlencode($message);
        }
      }

      $items[] = [
        'no' => $index + 1,
        'tanggal' => $tanggal,
        'url' => $fileUrl,
        'wa_url' => $waUrl,
        'admin_name' => $adminName,
      ];
    }

    return $this->response->setJSON(['items' => $items]);
  }

  private function detectLhuFile($row)
  {
    $kode_layanan = $row->kode_layanan ?? null;
    if (!$kode_layanan) {
      return ['has' => false, 'url' => '#'];
    }

    try {
      $lhuFile = $this->pelayananModel->getLatestLhuFile($kode_layanan);

      if (!$lhuFile) {
        return ['has' => false, 'url' => '#'];
      }

      // Gunakan kolom file
      $filePath = $lhuFile->file ?? null;

      if (empty($filePath)) {
        return ['has' => false, 'url' => '#'];
      }

      // Jika sudah berupa URL lengkap
      if (preg_match('/^https?:\/\//i', $filePath)) {
        return [
          'has' => true,
          'url' => $filePath,
          'uploader' => $lhuFile->uploader_name ?? 'Unknown'
        ];
      }

      // Jika berupa path file relatif
      $possiblePath = FCPATH . 'uploads/lhu/' . ltrim($filePath, '/');
      if (is_file($possiblePath)) {
        $possibleUrl = base_url('uploads/lhu/' . ltrim($filePath, '/'));
        return [
          'has' => true,
          'url' => $possibleUrl,
          'uploader' => $lhuFile->uploader_name ?? 'Unknown'
        ];
      }

      // File tercatat di database tapi tidak ditemukan di storage
      log_message('warning', "LHU file not found in storage: {$filePath} for kode_layanan: {$kode_layanan}");
      return ['has' => false, 'url' => '#'];
    } catch (\Throwable $e) {
      log_message('error', 'Error detecting LHU file: ' . $e->getMessage());
      return ['has' => false, 'url' => '#'];
    }
  }

  private function normalizePhoneForWhatsapp(?string $rawPhone): string
  {
    if (!$rawPhone) {
      return '';
    }

    $digits = preg_replace('/\D/', '', trim($rawPhone));
    if ($digits === '') {
      return '';
    }

    if (substr($digits, 0, 1) === '0') {
      $digits = '62' . substr($digits, 1);
    } elseif (substr($digits, 0, 2) !== '62') {
      $digits = '62' . $digits;
    }

    return $digits;
  }

  public function kuesioner($id)
  {
    $idenc = $id;
    try {
      $kode_layanan = $this->encrypter->decrypt(hex2bin($idenc));
    } catch (\Exception $e) {
      throw \CodeIgniter\Exceptions\PageNotFoundException::forPageNotFound();
    }

    $pertanyaan = $this->pelayananModel->getKuesionerPertanyaan();

    $data = [
      'title' => 'Kuesioner Kepuasan Pelanggan',
      'pertanyaan' => $pertanyaan,
      'idenc' => $idenc,
    ];
    return view('Modules\Pelayanan\Views\v_kuesioner_form_user', $data);
  }

  public function submit_kuesioner()
  {
    $session = session();
    $user_id = $session->get('id_user');
    $idenc = $this->request->getPost('idenc');

    try {
      $kode_layanan = $this->encrypter->decrypt(hex2bin($idenc));
    } catch (\Exception $e) {
      return $this->response->setJSON(['res' => false, 'msg' => 'ID Layanan tidak valid.', 'xname' => csrf_token(), 'xhash' => csrf_hash()]);
    }

    $jawaban_array = $this->request->getPost('jawaban');
    if (empty($jawaban_array)) {
      return $this->response->setJSON(['res' => false, 'msg' => 'Tidak ada jawaban yang dikirim.', 'xname' => csrf_token(), 'xhash' => csrf_hash()]);
    }

    $db = \Config\Database::connect();

    $db->transStart();

    foreach ($jawaban_array as $id_pertanyaan => $jawaban) {
      $jawabanText = is_array($jawaban) ? json_encode($jawaban) : (string) $jawaban;

      $existingAnswer = $this->pelayananModel->getExistingJawaban($id_pertanyaan, $user_id, $kode_layanan);

      $dataJawaban = [
        'id_pertanyaan' => $id_pertanyaan,
        'user_id' => $user_id,
        'kode_layanan' => $kode_layanan,
        'jawaban' => $jawabanText,
        'created_at' => date('Y-m-d H:i:s')
      ];

      if ($existingAnswer) {
        $this->pelayananModel->saveJawaban($dataJawaban, $existingAnswer->id_jawaban);
      } else {
        $this->pelayananModel->saveJawaban($dataJawaban);
      }
    }

    $this->pelayananModel->updateKuisionerFlag($kode_layanan);

    $db->transComplete();

    if ($db->transStatus() === false) {
      return $this->response->setJSON(['res' => false, 'msg' => 'Terjadi kegagalan saat menyimpan data.', 'xname' => csrf_token(), 'xhash' => csrf_hash()]);
    }

    return $this->response->setJSON([
      'res' => 'refresh',
      'link' => site_url('pelayanan'),
      'xname' => csrf_token(),
      'xhash' => csrf_hash()
    ]);
  }

  /**
   * Upload / Re-upload surat pengantar dari halaman progress & detail layanan.
   */
  public function uploadSuratPengantar()
  {
    $session = session();
    $userId = (int) ($session->get('id_user') ?? 0);

    if ($userId <= 0) {
      return $this->response->setJSON([
        'res' => false,
        'msg' => 'Sesi Anda telah habis. Silakan login kembali.',
        'xname' => csrf_token(),
        'xhash' => csrf_hash()
      ]);
    }

    $kode_layanan = (int) $this->request->getPost('kode_layanan');
    if ($kode_layanan <= 0) {
      return $this->response->setJSON([
        'res' => false,
        'msg' => 'Kode layanan tidak valid.',
        'xname' => csrf_token(),
        'xhash' => csrf_hash()
      ]);
    }

    // Pastikan layanan milik user ini
    $layanan = $this->pelayananModel->getLayananByKode($kode_layanan);
    if (!$layanan || (int) ($layanan->user_id ?? 0) !== $userId) {
      return $this->response->setJSON([
        'res' => false,
        'msg' => 'Anda tidak memiliki akses ke layanan ini.',
        'xname' => csrf_token(),
        'xhash' => csrf_hash()
      ]);
    }

    try {
      $fileSurat = $this->request->getFile('surat_pengantar');
      if (!$fileSurat || !$fileSurat->isValid() || $fileSurat->hasMoved()) {
        return $this->response->setJSON([
          'res' => false,
          'msg' => 'File surat pengantar tidak valid atau belum dipilih.',
          'xname' => csrf_token(),
          'xhash' => csrf_hash()
        ]);
      }

      // Validasi tipe file
      $allowedMimes = ['application/pdf', 'image/jpeg', 'image/jpg', 'image/png'];
      if (!in_array($fileSurat->getMimeType(), $allowedMimes)) {
        return $this->response->setJSON([
          'res' => false,
          'msg' => 'Tipe file tidak diizinkan. Hanya PDF, JPG, PNG.',
          'xname' => csrf_token(),
          'xhash' => csrf_hash()
        ]);
      }

      // Validasi ukuran (max 5MB)
      if ($fileSurat->getSize() > 5 * 1024 * 1024) {
        return $this->response->setJSON([
          'res' => false,
          'msg' => 'Ukuran file terlalu besar. Maksimal 5MB.',
          'xname' => csrf_token(),
          'xhash' => csrf_hash()
        ]);
      }

      // Hapus file lama jika ada
      $oldFile = $layanan->surat_pertanyaan ?? null;
      if ($oldFile) {
        $oldPath = FCPATH . 'uploads/surat_pengantar/' . $oldFile;
        if (file_exists($oldPath)) {
          @unlink($oldPath);
        }
      }

      // Generate nama file unik
      $ext = strtolower($fileSurat->getClientExtension());
      try {
        $newFileName = time() . bin2hex(random_bytes(5)) . '.' . $ext;
      } catch (\Exception $e) {
        $newFileName = time() . bin2hex(openssl_random_pseudo_bytes(5)) . '.' . $ext;
      }

      // Upload path
      $uploadPath = FCPATH . 'uploads/surat_pengantar/';
      if (!is_dir($uploadPath)) {
        mkdir($uploadPath, 0755, true);
      }

      // Pindahkan file
      $tmpPath = $fileSurat->getTempName();
      $destPath = $uploadPath . $newFileName;
      if (!rename($tmpPath, $destPath)) {
        if (!copy($tmpPath, $destPath)) {
          return $this->response->setJSON([
            'res' => false,
            'msg' => 'Gagal memindahkan file surat pengantar.',
            'xname' => csrf_token(),
            'xhash' => csrf_hash()
          ]);
        }
        @unlink($tmpPath);
      }

      // Update kolom surat_pertanyaan di t_layanan
      if (!$this->pelayananModel->updateSuratPengantar($kode_layanan, $newFileName)) {
        return $this->response->setJSON([
          'res' => false,
          'msg' => 'Gagal menyimpan data surat pengantar.',
          'xname' => csrf_token(),
          'xhash' => csrf_hash()
        ]);
      }

      return $this->response->setJSON([
        'res' => true,
        'msg' => 'Surat pengantar berhasil diunggah!',
        'fileName' => $newFileName,
        'fileUrl' => base_url('uploads/surat_pengantar/' . $newFileName),
        'xname' => csrf_token(),
        'xhash' => csrf_hash()
      ]);
    } catch (\Exception $e) {
      log_message('error', 'Upload surat pengantar error: ' . $e->getMessage());
      return $this->response->setJSON([
        'res' => false,
        'msg' => 'Terjadi kesalahan saat mengunggah file.',
        'xname' => csrf_token(),
        'xhash' => csrf_hash()
      ]);
    }
  }
}
