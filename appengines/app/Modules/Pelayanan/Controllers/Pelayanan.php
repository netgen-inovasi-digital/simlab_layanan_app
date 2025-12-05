<?php

namespace Modules\Pelayanan\Controllers;

use App\Controllers\BaseController;
use Modules\Pelayanan\Models\PelayananModel;

class Pelayanan extends BaseController
{
  private $table = 'simlab_t_layanan';
  private $id = 'lnKode';
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
      6 => 'Penerbitan LHUS',
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
          'kode' => $data->lnKode,
          'statusText' => $this->getStatusText((int) $data->lnStatus),
          'tanggal' => date('d-m-Y', strtotime($data->lnTgl)),
          'noTransaksi' => $data->lnNoTransaksi ?? 'Belum tersedia',
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
        $kode = isset($c->jenKode) ? trim((string) $c->jenKode) : '';
        $nama = (isset($c->jenNama) && trim((string) $c->jenNama) !== '') ? trim((string) $c->jenNama) : $kode;
        if ($kode !== '') {
          $normalized[] = (object) [
            'jenKode' => $kode,
            'jenNama' => $nama
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

    // --- Siapkan map pembayaran terakhir per lnKode (satu query) ---
    $lnKodes = array_map(fn($r) => (int) $r->lnKode, $list);

    $payMap = $this->pelayananModel->getLatestPaymentMap($lnKodes);

    foreach ($list as $row) {
      $id = bin2hex($this->encrypter->encrypt($row->lnKode));
      $response = [];

      // No Transaksi + Tanggal
      $noTransaksi = (isset($row->lnNoTransaksi) && trim((string) $row->lnNoTransaksi) !== '')
        ? $row->lnNoTransaksi
        : 'Belum tersedia';

      $tanggal = !empty($row->lnTgl) ? date('d-m-Y', strtotime($row->lnTgl)) : '-';
      $response[] = '<div>' . esc($noTransaksi) . '<br><small>' . esc($tanggal) . '</small></div>';

      // Status layanan dengan tracking
      $statusText = $this->getStatusText((int) ($row->lnStatus ?? 0));
      $response[] = '<div class="d-flex gap-2 align-items-center">' .
        '<button class="btn btn-sm btn-outline-primary" onclick="showTrackingModal(\'' . $id . '\', \'' . $row->lnKode . '\', ' . (int) ($row->lnStatus ?? 0) . ')">' .
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

      // Pembayaran terakhir untuk lnKode ini (pakai map hasil query)
      $lnKodeInt = (int) $row->lnKode;
      $bayarStatusVal = isset($payMap[$lnKodeInt]) ? $payMap[$lnKodeInt]['status'] : 0;
      $bayarInvoiceNo = isset($payMap[$lnKodeInt]) ? $payMap[$lnKodeInt]['inv'] : null;

      // statusBayar: 1 = sudah bayar, 0 = belum
      if ($bayarStatusVal === 1) {
        $response[] = '<button class="btn btn-sm btn-success"><i class="bi bi-credit-card"></i> Sudah Bayar</button>';
      } else {
        $response[] = '<button class="btn btn-sm btn-info"><i class="bi bi-credit-card"></i> Belum Bayar</button>';
      }

      // Akses LHU & kuisioner
      $lnStatusVal = (int) ($row->lnStatus ?? 0);
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

      // Aksi detail (masking lnKode via enkripsi)
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

  public function getSampleIdentity($lnKode = null)
  {
    if (!$lnKode) {
      return $this->response->setJSON([
        'success' => false,
        'message' => 'Kode layanan tidak ditemukan'
      ]);
    }

    try {
      $sampleData = $this->pelayananModel->getSampleIdentityRow($lnKode);

      if (!$sampleData) {
        return $this->response->setJSON([
          'success' => false,
          'message' => 'Data identitas sampel tidak ditemukan'
        ]);
      }

      return $this->response->setJSON([
        'success' => true,
        'data' => [
          'jenis' => $sampleData->jenis ?? '-',
          'kemasan' => $sampleData->kemasan ?? '-',
          'sifat' => $sampleData->sifat ?? '-',
          'sisa' => $sampleData->sisa ?? '-',
          'deskripsi' => $sampleData->deskripsi ?? '-',
          'keterangan_khusus' => $sampleData->keterangan_khusus ?? '-'
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
      $lnKode = $this->encrypter->decrypt(hex2bin($id));
    } catch (\Throwable $e) {
      try {
        $lnKode = $this->encrypter->decrypt($id);
      } catch (\Throwable $e2) {
        return $this->response->setJSON(['items' => []]);
      }
    }

    $layanan = $this->pelayananModel->getLayananByKode($lnKode);

    if (!$layanan || (int) ($layanan->user_id ?? 0) !== $userId) {
      return $this->response->setJSON(['items' => []]);
    }

    try {
      $rows = $this->pelayananModel->getLhuFiles($lnKode);
    } catch (\Throwable $e) {
      log_message('error', 'Pelayanan::lhuList error: ' . $e->getMessage());
      return $this->response->setJSON(['items' => []]);
    }

    if (empty($rows)) {
      return $this->response->setJSON(['items' => []]);
    }

    $items = [];
    $transLabel = !empty($layanan->lnNoTransaksi)
      ? 'No. Transaksi ' . $layanan->lnNoTransaksi
      : 'kode layanan ' . $lnKode;
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
    $lnKode = $row->lnKode ?? null;
    if (!$lnKode) {
      return ['has' => false, 'url' => '#'];
    }

    try {
      $lhuFile = $this->pelayananModel->getLatestLhuFile($lnKode);

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
      log_message('warning', "LHU file not found in storage: {$filePath} for lnKode: {$lnKode}");
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
      $lnKode = $this->encrypter->decrypt(hex2bin($idenc));
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
      $lnKode = $this->encrypter->decrypt(hex2bin($idenc));
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

      $existingAnswer = $this->pelayananModel->getExistingJawaban($id_pertanyaan, $user_id, $lnKode);

      $dataJawaban = [
        'id_pertanyaan' => $id_pertanyaan,
        'user_id' => $user_id,
        'kode_layanan' => $lnKode,
        'jawaban' => $jawabanText,
        'created_at' => date('Y-m-d H:i:s')
      ];

      if ($existingAnswer) {
        $this->pelayananModel->saveJawaban($dataJawaban, $existingAnswer->id_jawaban);
      } else {
        $this->pelayananModel->saveJawaban($dataJawaban);
      }
    }

    $this->pelayananModel->updateKuisionerFlag($lnKode);

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
}
