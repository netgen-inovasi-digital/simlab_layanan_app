<?php
namespace Modules\Pelaksanaan\Controllers;

use App\Controllers\BaseController;
use Modules\Pelaksanaan\Models\PelaksanaanModel;

class Pelaksanaan extends BaseController
{
  private $table = 't_layanan';
  private $id = 'kode_layanan';

  /** @var PelaksanaanModel */
  protected $pelaksanaanModel;

  public function __construct()
  {
    $this->pelaksanaanModel = new PelaksanaanModel();
  }


  public function index()
  {
    $session = session();
    $user_id = $session->get('id_user');

    $data = [
      'title' => 'Data Pelaksanaan',
      'user' => $this->pelaksanaanModel->getUserById((int) $user_id),
    ];

    return view('Modules\Pelaksanaan\Views\v_pelaksanaan', $data);
  }

  public function dataList()
  {
    $data = [];

    $list = $this->pelaksanaanModel->getAllLayananOrdered();

    if (empty($list)) {
      return $this->response->setJSON(["items" => []]);
    }

    // [FILTER] ?status_layanan=7,6
    $lnStatusParam = (string) ($this->request->getGet('status_layanan') ?? '');
    $statusNums = [];

    if ($lnStatusParam !== '') {
      $parts = preg_split('/[,\s]+/', $lnStatusParam, -1, PREG_SPLIT_NO_EMPTY);
      foreach ($parts as $p) {
        $tp = strtolower(trim($p));
        if (is_numeric($tp)) {
          $statusNums[] = (int) $tp;
        }
      }
      $statusNums = array_values(array_unique($statusNums));
    }

    // prefetch user info
    $userIds = [];
    foreach ($list as $r) {
      if (!empty($r->user_id))
        $userIds[] = $r->user_id;
    }
    $userMap = $this->pelaksanaanModel->getUserMapByIds($userIds);

    foreach ($list as $row) {
      $isKajiUlang = (int) ($row->jumlah_kaji_ulang ?? 0) > 0;

      if ((int) $row->status_layanan < 6 && !$isKajiUlang)
        continue;

      $id = bin2hex(service('encrypter')->encrypt($row->kode_layanan));
      // $encrypted_id = bin2hex(service('encrypter')->encrypt($row->kode_bayar));
      $response = [];

      // kolom pemesan
      $pemesanNama = '-';
      $tipe = '-';
      $tanggal = '-';
      if (isset($row->user_id, $userMap[$row->user_id])) {
        $u = $userMap[$row->user_id];
        $pemesanNama = !empty($u->user_name) ? $u->user_name : ($row->lnOrangNama ?? '-');
        $tipe = !empty($u->user_identity) ? $u->user_identity : '-';
      } else {
        $pemesanNama = !empty($row->lnOrangNama) ? $row->lnOrangNama : ($row->lnPemesanNama ?? '-');
        $tipe = !empty($row->lnPemesanIdentity) ? $row->lnPemesanIdentity : ($row->lnJenisPemesan ?? '-');
      }
      if (!empty($row->tanggal_checkout))
        $tanggal = date('d-m-Y H:i', strtotime($row->tanggal_checkout));

      $response[] =
        '<div style="line-height:1.3;">
                    <span style="font-size:1rem; font-weight:600;">' . esc($pemesanNama) . '</span><br>
                    <span style="font-size:0.9rem; color:#555;">' . esc($tanggal) . ' | ' . esc($tipe) . '</span>
                </div>';

      // kolom LHUS (lihat)
      $response[] = '<button type="button" class="btn btn-sm btn-info" title="Lihat Detail Item Layanan" onclick="loadDetail(\'' . $id . '\')">
                              <i class="bi bi-eye"></i> Lihat LHUS
                          </button>';

      // kolom LHU history
      $response[] = '<button type="button" class="btn btn-sm btn-outline-primary" title="Riwayat LHU" onclick="showLhuHistory(\'' . $id . '\')">
                              <i class="bi bi-files"></i> Lihat LHU
                          </button>';

      // deteksi file LHU
      $lhuInfo = $this->detectLhuFile($row);

      // kolom status (single badge)
      $response[] = '<div id="status-cell-' . $id . '">' . $this->formatStatus($row->status_layanan, $isKajiUlang) . '</div>';

      // kolom aksi
      $response[] = $this->aksiButton($id, $row->status_layanan, $lhuInfo);

      // terapkan filter
      if (!empty($statusNums) && !in_array((int) $row->status_layanan, $statusNums, true)) {
        continue;
      }

      $data[] = $response;
    }

    return $this->response->setJSON(["items" => $data]);
  }


  public function detailList($id = null)
  {
    if (!$id)
      return $this->response->setJSON(['items' => []]);

    // decrypt tolerant (hex → raw)
    try {
      $kode_layanan = service('encrypter')->decrypt(hex2bin($id));
    } catch (\Throwable $e) {
      try {
        $kode_layanan = service('encrypter')->decrypt($id);
      } catch (\Throwable $e2) {
        return $this->response->setJSON(['items' => []]);
      }
    }

    $rows = $this->pelaksanaanModel->getAcceptedDetailRowsForLayanan($kode_layanan);

    if (empty($rows))
      return $this->response->setJSON(['items' => []]);

    $items = [];
    $no = 1;
    foreach ($rows as $row) {
      if ((int) ($row->status_layanan ?? 0) !== 1)
        continue;

      // Nama layanan dari database baru (nama_layanan)
      $layanan = !empty($row->nama_layanan) ? $row->nama_layanan : '-';

      $jumlah = (int) ($row->jumlah ?? 0);

      // Prioritas file: 1) LHUS dari t_files_lhus, 2) LHU dari file_lhu
      $fileUrl = null;

      // Cek file LHUS dulu (dari t_files_lhus)
      if (!empty($row->file_lhus)) {
        $val = trim((string) $row->file_lhus);
        if (preg_match('/^https?:\/\//i', $val)) {
          $fileUrl = $val;
        } else {
          $p = FCPATH . 'uploads/lhus/' . ltrim($val, '/');
          if (is_file($p)) {
            $fileUrl = base_url('uploads/lhus/' . ltrim($val, '/'));
          }
        }
      }

      // Kalau LHUS tidak ada, cek file LHU
      if (!$fileUrl && !empty($row->file_lhu)) {
        $val = trim((string) $row->file_lhu);
        if (preg_match('/^https?:\/\//i', $val)) {
          $fileUrl = $val;
        } else {
          $p = FCPATH . 'uploads/lhu/' . ltrim($val, '/');
          if (is_file($p)) {
            $fileUrl = base_url('uploads/lhu/' . ltrim($val, '/'));
          }
        }
      }

      $viewHtml = $fileUrl
        ? '<button class="btn btn-sm btn-outline-primary" onclick="window.open(\'' . esc($fileUrl) . '\', \'_blank\')"><i class="bi bi-eye"></i></button>'
        : '<button class="btn btn-sm btn-secondary" disabled><i class="bi bi-file-earmark-text"></i> Lihat</button>';

      // Username dari account (LHUS uploader/approver)
      $uploadLhusBy = !empty($row->upload_lhus_by) ? esc($row->upload_lhus_by) : '-';
      $accLhusBy = !empty($row->acc_lhus_by) ? esc($row->acc_lhus_by) : '-';

      // urutan kolom dikembalikan tanpa keterangan
      $items[] = [
        $no++,
        $layanan,
        $jumlah,
        $viewHtml,
        $uploadLhusBy,   // Upload LHUS (username)
        $accLhusBy       // Acc LHUS (username)
      ];
    }

    return $this->response->setJSON(['items' => $items]);
  }

  public function lhuList($id = null)
  {
    if (!$id) {
      return $this->response->setJSON(['items' => []]);
    }

    try {
      $kode_layanan = service('encrypter')->decrypt(hex2bin($id));
    } catch (\Throwable $e) {
      try {
        $kode_layanan = service('encrypter')->decrypt($id);
      } catch (\Throwable $e2) {
        return $this->response->setJSON(['items' => []]);
      }
    }

    try {
      $rows = $this->pelaksanaanModel->getLhuHistoryRows($kode_layanan);
    } catch (\Throwable $e) {
      log_message('error', 'Pelaksanaan::lhuList error: ' . $e->getMessage());
      return $this->response->setJSON(['items' => []]);
    }

    if (empty($rows)) {
      return $this->response->setJSON(['items' => []]);
    }

    $items = [];
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

      $items[] = [
        'no' => $index + 1,
        'tanggal' => $tanggal,
        'uploader' => $row->uploader_name ?? '-',
        'url' => $fileUrl
      ];
    }

    return $this->response->setJSON(['items' => $items]);
  }



  public function upload()
  {
    $file = $this->request->getFile('lhu_file');
    $encId = $this->request->getPost('id');
    $detKode = $this->request->getPost('detKode');
    $tanggalTerbit = $this->request->getPost('tanggal_terbit_lhu');

    if (empty($encId)) {
      return $this->response->setJSON([
        'res' => 'error',
        'msg' => 'ID tidak ditemukan',
        'xname' => csrf_token(),
        'xhash' => csrf_hash()
      ]);
    }

    try {
      $kode_layanan = service('encrypter')->decrypt(hex2bin($encId));
    } catch (\Throwable $e) {
      try {
        $kode_layanan = service('encrypter')->decrypt($encId);
      } catch (\Throwable $e2) {
        return $this->response->setJSON([
          'res' => 'error',
          'msg' => 'ID tidak valid',
          'xname' => csrf_token(),
          'xhash' => csrf_hash()
        ]);
      }
    }

    $layananRow = $this->pelaksanaanModel->getLayananByKode($kode_layanan);

    if (!$layananRow) {
      return $this->response->setJSON([
        'res' => 'error',
        'msg' => 'Data layanan tidak ditemukan',
        'xname' => csrf_token(),
        'xhash' => csrf_hash()
      ]);
    }

    $isUjiUlang = (int) ($layananRow->jumlah_kaji_ulang ?? 0) > 0;

    if (!($file && $file->isValid() && !$file->hasMoved())) {
      return $this->response->setJSON([
        'res' => 'error',
        'msg' => 'File tidak valid atau tidak dipilih',
        'xname' => csrf_token(),
        'xhash' => csrf_hash()
      ]);
    }

    // Validasi tanggal terbit LHU
    if (empty($tanggalTerbit)) {
      return $this->response->setJSON([
        'res' => 'error',
        'msg' => 'Tanggal terbit LHU harus diisi',
        'xname' => csrf_token(),
        'xhash' => csrf_hash()
      ]);
    }

    try {
      $tanggalTerbitFormatted = (new \DateTime($tanggalTerbit))->format('Y-m-d H:i:s');
    } catch (\Exception $e) {
      return $this->response->setJSON([
        'res' => 'error',
        'msg' => 'Format tanggal terbit tidak valid',
        'xname' => csrf_token(),
        'xhash' => csrf_hash()
      ]);
    }

    // upload fisik
    $uploadResult = $this->doUpload($file, 'lhu');
    if (!$uploadResult['status']) {
      return $this->response->setJSON([
        'res' => 'error',
        'msg' => $uploadResult['msg'],
        'xname' => csrf_token(),
        'xhash' => csrf_hash()
      ]);
    }
    $filename = $uploadResult['filename'];

    $session = session();
    $user_id = (int) $session->get('id_user');

    try {
      $saveResult = $this->pelaksanaanModel->saveUploadedLhuFile(
        (string) $kode_layanan,
        (string) $filename,
        (int) $user_id,
        (string) $tanggalTerbitFormatted,
        (bool) $isUjiUlang
      );

      $msg = $saveResult['msg'] ?? 'File LHU berhasil diunggah.';
      $oldFile = $saveResult['oldFile'] ?? null;

      // Hapus file lama jika ada (hanya untuk unggahan non uji ulang)
      if (!$isUjiUlang && !empty($oldFile) && $oldFile !== $filename) {
        $oldFilePath = FCPATH . 'uploads/lhu/' . ltrim((string) $oldFile, '/');
        if (is_file($oldFilePath)) {
          @unlink($oldFilePath);
        }
      }

      // Update/Insert tanggal terbit LHU ke t_log_sampel
      $this->pelaksanaanModel->upsertLogPenerbitanLhu((string) $kode_layanan, (string) $tanggalTerbitFormatted);

      // Update status menjadi 9 (LHU Disetujui) setelah upload berhasil
      $this->pelaksanaanModel->setLayananStatus((string) $kode_layanan, 9);

      return $this->response->setJSON([
        'res' => true,
        'msg' => $msg . ' LHU berhasil dikirim.',
        'url' => base_url('uploads/lhu/' . $filename),
        'xname' => csrf_token(),
        'xhash' => csrf_hash()
      ]);

    } catch (\Throwable $e) {
      $savedPath = FCPATH . 'uploads/lhu/' . $filename;
      if (is_file($savedPath))
        @unlink($savedPath);
      return $this->response->setJSON([
        'res' => 'error',
        'msg' => 'Error saat menyimpan: ' . $e->getMessage(),
        'xname' => csrf_token(),
        'xhash' => csrf_hash()
      ]);
    }
  }

  public function pengujianUlang()
  {
    $response = [
      'res' => false,
      'msg' => 'Pengujian ulang gagal diproses.',
      'xname' => csrf_token(),
      'xhash' => csrf_hash()
    ];

    if (strtolower($this->request->getMethod()) !== 'post') {
      return $this->response->setJSON($response);
    }

    $encId = $this->request->getPost('id');
    $catatan = trim((string) $this->request->getPost('catatan'));

    if (empty($encId)) {
      $response['msg'] = 'ID layanan tidak ditemukan.';
      return $this->response->setJSON($response);
    }

    if ($catatan === '') {
      $response['msg'] = 'Catatan pengujian ulang wajib diisi.';
      return $this->response->setJSON($response);
    }

    try {
      $kode_layanan = service('encrypter')->decrypt(hex2bin($encId));
    } catch (\Throwable $e) {
      try {
        $kode_layanan = service('encrypter')->decrypt($encId);
      } catch (\Throwable $e2) {
        $response['msg'] = 'ID tidak valid.';
        return $this->response->setJSON($response);
      }
    }

    $row = $this->pelaksanaanModel->getLayananByKode($kode_layanan);

    if (!$row) {
      $response['msg'] = 'Data layanan tidak ditemukan.';
      return $this->response->setJSON($response);
    }

    if ((int) $row->status_layanan !== 9) {
      $response['msg'] = 'Pengujian ulang hanya bisa dilakukan pada layanan dengan status selesai.';
      return $this->response->setJSON($response);
    }

    $ok = $this->pelaksanaanModel->createPengujianUlang((string) $kode_layanan, (string) $catatan);
    if ($ok === false) {
      $response['msg'] = 'Terjadi kesalahan saat menyimpan data.';
      return $this->response->setJSON($response);
    }

    $response['res'] = true;
    $response['msg'] = 'Pengujian ulang berhasil dibuat. Status layanan kembali ke Review Petugas.';
    return $this->response->setJSON($response);
  }

  // upload helper
  private function doUpload($file, $folder = 'lhu')
  {
    if (!($file && $file->isValid() && !$file->hasMoved())) {
      return ['status' => false, 'msg' => 'File tidak valid atau sudah dipindahkan'];
    }

    $allowedExt = ['jpg', 'jpeg', 'png', 'pdf', 'doc', 'docx', 'xls', 'xlsx'];
    $allowedMime = [
      'image/jpeg',
      'image/png',
      'application/pdf',
      'application/msword',
      'application/vnd.openxmlformats-officedocument.wordprocessingml.document',
      'application/vnd.ms-excel',
      'application/vnd.openxmlformats-officedocument.spreadsheetml.sheet'
    ];

    $ext = strtolower($file->getClientExtension());
    $tmp = $file->getTempName();
    if (!is_file($tmp))
      return ['status' => false, 'msg' => 'File sementara tidak ditemukan'];

    $detectedMime = function_exists('finfo_open')
      ? (function ($tmp) {
        $f = finfo_open(FILEINFO_MIME_TYPE);
        $m = finfo_file($f, $tmp);
        finfo_close($f);
        return $m; })($tmp)
      : $file->getClientMimeType();

    if (!in_array($ext, $allowedExt) || !in_array($detectedMime, $allowedMime)) {
      return ['status' => false, 'msg' => 'Format file tidak diperbolehkan'];
    }

    if (in_array($ext, ['jpg', 'jpeg', 'png']) && @getimagesize($tmp) === false) {
      return ['status' => false, 'msg' => 'File bukan gambar asli'];
    }

    if ($file->getSize() > 5 * 1024 * 1024)
      return ['status' => false, 'msg' => 'Ukuran file maksimal 5MB'];

    try {
      $rand = bin2hex(random_bytes(8));
    } catch (\Exception $e) {
      $rand = bin2hex(openssl_random_pseudo_bytes(8));
    }
    $filename = time() . '_' . $rand . '.' . $ext;

    $path = FCPATH . 'uploads/' . $folder;
    if (!is_dir($path))
      @mkdir($path, 0755, true);

    try {
      $file->move($path, $filename, true);
      $full = $path . DIRECTORY_SEPARATOR . $filename;
      if (is_file($full))
        @chmod($full, 0644);
    } catch (\Exception $e) {
      return ['status' => false, 'msg' => 'Gagal memindahkan file: ' . $e->getMessage()];
    }

    return ['status' => true, 'filename' => $filename];
  }

  // detect LHUS - UPDATED: gunakan t_files_lhus dengan kode_layanan
  private function detectLhusFile($row)
  {
    // Cek langsung ke t_files_lhus berdasarkan kode_layanan (kode_layanan)
    if (!empty($row->kode_layanan)) {
      try {
        $db = \Config\Database::connect();

        // Cek apakah ada file LHUS yang sudah dikirim (status=0) atau diterima (status=1)
        $lhusFile = $db->table('t_files_lhus')
          ->select('file_lhus')
          ->where('kode_layanan', $row->kode_layanan)
          ->whereIn('status', [0, 1]) // 0=terkirim, 1=diterima
          ->orderBy('file_id', 'DESC')
          ->limit(1)
          ->get()->getRow();

        if ($lhusFile && !empty($lhusFile->file_lhus)) {
          $raw = $lhusFile->file_lhus;
          if (preg_match('/^https?:\/\//i', $raw)) {
            return ['has' => true, 'url' => $raw];
          }
          $p = FCPATH . 'uploads/lhus/' . ltrim($raw, '/');
          if (is_file($p)) {
            return ['has' => true, 'url' => base_url('uploads/lhus/' . ltrim($raw, '/'))];
          }
        }
      } catch (\Throwable $e) {
        // Silent catch
      }
    }

    return ['has' => false, 'url' => '#'];
  }

  // detect LHU - UPDATED: gunakan t_files_lhu (database baru)
  private function detectLhuFile($row)
  {
    // Cek langsung ke t_files_lhu berdasarkan kode (kode_layanan)
    if (!empty($row->kode_layanan)) {
      try {
        $db = \Config\Database::connect();

        $lhuFile = $db->table('t_files_lhu')
          ->select('file')
          ->where('kode', $row->kode_layanan)
          ->orderBy('file_id', 'DESC')
          ->limit(1)
          ->get()->getRow();

        if ($lhuFile && !empty($lhuFile->file)) {
          $raw = $lhuFile->file;
          if (preg_match('/^https?:\/\//i', $raw)) {
            return ['has' => true, 'url' => $raw];
          }
          $p = FCPATH . 'uploads/lhu/' . ltrim($raw, '/');
          if (is_file($p)) {
            return ['has' => true, 'url' => base_url('uploads/lhu/' . ltrim($raw, '/'))];
          }
        }
      } catch (\Throwable $e) {
        // Silent catch
      }
    }

    return ['has' => false, 'url' => '#'];
  }

  private function formatStatus($status, $isKajiUlang = false)
  {
    if ($isKajiUlang && (int) $status <= 5) {
      return '<span class="badge bg-warning text-dark">Pengujian Ulang</span>';
    }

    switch ((int) $status) {
      case 7:
      case 8:
      case 9:
        return '<span class="badge bg-success">LHU Disetujui</span>';
      case 6:
        return '<span class="badge bg-info">LHU belum diproses</span>';
      default:
        return '<span class="badge bg-dark">Unknown</span>';
    }
  }

  private function aksiButton($id, $status, $lhuInfo = ['has' => false, 'url' => '#'])
  {
    $btn = '<div id="' . $id . '" class="float-end d-flex align-items-center justify-content-end" style="gap:6px;">';

    $rawUrl = '#';
    if (is_array($lhuInfo) && ($lhuInfo['has'] ?? false) && !empty($lhuInfo['url'])) {
      $rawUrl = (string) $lhuInfo['url'];
    }
    $safeUrl = addslashes($rawUrl);

    if ((int) $status === 6) {
      $btn .= sprintf(
        '<span class="btn-action text-success" role="button" title="Upload & Kirim LHU" onclick="openUploadModal(\'%s\', \'%s\')"><i class="bi bi-send-check"></i></span>',
        $id,
        $safeUrl
      );
    } else {
      $btn .= '<span class="btn-action text-muted" style="cursor:not-allowed;opacity:.45;" title="Menunggu status Memproses LHU"><i class="bi bi-send-check"></i></span>';
    }

    $btn .= '<label class="divider">|</label>';

    $canReTest = ((int) $status === 9);
    if ($canReTest) {
      $btn .= '<span class="btn-action text-warning" role="button" title="Pengujian Ulang" onclick="openPengujianUlangModal(\'' . $id . '\')"><i class="bi bi-arrow-counterclockwise"></i></span>';
    } else {
      $btn .= '<span class="btn-action text-muted" style="cursor:not-allowed;opacity:.45;" title="Pengujian Ulang belum tersedia"><i class="bi bi-arrow-counterclockwise"></i></span>';
    }

    $btn .= '</div>';
    return $btn;
  }

  // proses: kini hanya butuh LHU sudah terunggah
  public function proses($id)
  {
    try {
      $kode = service('encrypter')->decrypt(hex2bin($id));
    } catch (\Exception $e) {
      return $this->response->setJSON([
        'res' => false,
        'msg' => 'ID tidak valid',
        'xname' => csrf_token(),
        'xhash' => csrf_hash()
      ]);
    }

    $row = $this->pelaksanaanModel->getLayananByKode($kode);

    if (!$row) {
      return $this->response->setJSON([
        'res' => false,
        'msg' => 'Data layanan tidak ditemukan',
        'xname' => csrf_token(),
        'xhash' => csrf_hash()
      ]);
    }

    // HARUS ada file LHU
    $lhuInfo = $this->detectLhuFile($row);
    if (!($lhuInfo['has'] ?? false)) {
      return $this->response->setJSON([
        'res' => false,
        'msg' => 'Tidak dapat memproses: file LHU belum terunggah.',
        'xname' => csrf_token(),
        'xhash' => csrf_hash()
      ]);
    }

    // (opsional) pastikan status minimal 6
    if ((int) $row->status_layanan < 6) {
      return $this->response->setJSON([
        'res' => false,
        'msg' => 'Status belum pada tahap Memproses LHU.',
        'xname' => csrf_token(),
        'xhash' => csrf_hash()
      ]);
    }

    // set ke 8
    $res = $this->pelaksanaanModel->setLayananStatus((string) $kode, 9);

    return $this->response->setJSON([
      'res' => $res,
      'xname' => csrf_token(),
      'xhash' => csrf_hash()
    ]);
  }

  public function delete($id)
  {
    try {
      $kode = service('encrypter')->decrypt(hex2bin($id));
    } catch (\Exception $e) {
      return $this->response->setJSON([
        'res' => false,
        'msg' => 'ID tidak valid',
        'xname' => csrf_token(),
        'xhash' => csrf_hash()
      ]);
    }

    $res = $this->pelaksanaanModel->deleteLayanan((string) $kode);

    return $this->response->setJSON([
      'res' => $res,
      'xname' => csrf_token(),
      'xhash' => csrf_hash()
    ]);
  }
}
