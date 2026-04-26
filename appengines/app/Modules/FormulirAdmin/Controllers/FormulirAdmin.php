<?php

namespace Modules\FormulirAdmin\Controllers;

use App\Controllers\BaseController;
use App\Models\MyModel;
use Modules\FormulirAdmin\Models\FormulirAdminModel;

class FormulirAdmin extends BaseController
{
  private $table = 't_layanan';
  private $id = 'kode_layanan';
  protected $encrypter;
  private $sessionKey = 'keranjang_formadmin';
  protected $formulirAdminModel;

  public function __construct()
  {
    $this->encrypter = \Config\Services::encrypter();
    $this->formulirAdminModel = new FormulirAdminModel();
  }

  /**
   * Index - kirim juga daftar user untuk pemilih pelanggan
   * dan daftar kategori (kode/nama) agar view bisa render opsi kategori awal
   */
  public function index()
  {
    $session = session();
    $user_id = $session->get('id_user');

    $modelUser = new MyModel('account_users');

    // Ambil user list untuk dropdown pemilih pelanggan (hanya yang sudah diverifikasi)
    $users = $modelUser->getAllDataById(['verifikasi' => 1], ['user_name' => 'ASC']);

    // Ambil daftar kategori menggunakan model
    $categoriesRaw = $this->formulirAdminModel->getKategoriLayanan();

    // NORMALISASI kategori
    $categories = [];
    if (!empty($categoriesRaw)) {
      foreach ($categoriesRaw as $c) {
        $kode = isset($c->kode) ? trim((string) $c->kode) : '';
        $nama = (isset($c->nama) && trim((string) $c->nama) !== '') ? trim((string) $c->nama) : $kode;
        if ($kode !== '') {
          $categories[] = (object) ['kode' => $kode, 'nama' => $nama];
        }
      }
    }

    $data = [
      'title' => 'Data Formulir Admin',
      'user' => (new MyModel('account_users'))->getDataById('user_id', $user_id),
      'users' => $users,
      'categories' => $categories
    ];

    return view('Modules\FormulirAdmin\Views\v_formulirAdmin', $data);
  }

  public function delete($id)
  {
    $id = $this->encrypter->decrypt(hex2bin($id));
    $model = new MyModel($this->table);
    $res = $model->deleteData($this->id, $id);

    return $this->response->setJSON([
      'res' => $res,
      'xname' => csrf_token(),
      'xhash' => csrf_hash()
    ]);
  }

  public function submit()
  {
    $idenc = $this->request->getPost('id');
    $data = [
      'lnOrangNama' => $this->request->getPost('lnOrangNama'),
      'lnInstansi' => $this->request->getPost('lnInstansi'),
    ];

    $model = new MyModel($this->table);

    if ($idenc == "") {
      $res = $model->insertData($data);
    } else {
      $id = $this->encrypter->decrypt(hex2bin($idenc));
      $res = $model->updateData($data, $this->id, $id);
    }

    return $this->response->setJSON([
      'res' => $res,
      'xname' => csrf_token(),
      'xhash' => csrf_hash()
    ]);
  }

  /**
   * datalist
   * - menerima parameter kategoriLayanan (status-array) & kode (kategori layanan)
   * - jika kode diberikan, hanya sertakan baris layanan yang memiliki detil dengan kode yang cocok
   */
  public function datalist()
  {
    $model = new MyModel($this->table);
    $data = [];

    // Ambil parameter kategoriLayanan dari query string (status filtering)
    $kategoriParam = $this->request->getGet('kategoriLayanan');
    $filterStatuses = null;
    if ($kategoriParam !== null && $kategoriParam !== '' && $kategoriParam !== 'all') {
      $parts = array_filter(array_map('trim', explode(',', $kategoriParam)));
      $filterStatuses = array_map('intval', $parts);
    }

    // Ambil parameter kode (kategori layanan yang ingin difilter)
    $jenKodeParam = trim((string) ($this->request->getGet('kode') ?? ''));

    // Ambil semua data, urutkan tanggal DESC (terbaru di atas)
    // Tetap gunakan getAllDataWithOrder jika model mendukungnya
    if (method_exists($model, 'getAllDataWithOrder')) {
      $list = $model->getAllDataWithOrder(['tanggal_checkout' => 'DESC']);
    } else {
      $list = $model->getAllDataByWhere([], ['tanggal_checkout' => 'DESC']);
    }

    $userModel = new MyModel('account_users');
    $layananDet = new MyModel('t_layanan_detil');

    // Siapkan map pembayaran terakhir per kode_layanan menggunakan model
    $lnKodes = array_map(fn($r) => (int) $r->kode_layanan, $list);

    $payMap = $this->formulirAdminModel->getPembayaranMapByLnKodes($lnKodes);
    $logMap = $this->formulirAdminModel->getLogSampelMapByLnKodes($lnKodes);

    foreach ($list as $row) {
      $lnStatusInt = (int) $row->status_layanan;

      // Jika ada filterStatuses, hanya proses baris yang cocok
      if (is_array($filterStatuses)) {
        if (!in_array($lnStatusInt, $filterStatuses, true))
          continue;
      } else {
        // Perilaku default lama: lewati status tertentu (0,2)
        if (in_array($lnStatusInt, [0, 2], true))
          continue;
      }

      // jika ada jenKodeParam, kita cek di detil apakah baris ini memiliki layanan yang termasuk jenKodeParam
      if ($jenKodeParam !== '') {
        try {
          $dets = $layananDet->getAllDataById(['kode_layanan' => $row->kode_layanan]);
          $hasMatch = false;
          foreach ($dets as $dd) {
            $detJen = isset($dd->kode_jenis) ? trim(substr($dd->kode_jenis, 0, 2)) : '';
            if ($detJen !== '' && $detJen === $jenKodeParam) {
              $hasMatch = true;
              break;
            }
          }
          if (!$hasMatch) {
            // skip this row if none of its detil match kode filter
            continue;
          }
        } catch (\Throwable $ex) {
          // jika error saat cek, lanjutkan tanpa mem-filter (defensive)
        }
      }

      // pakai kode_layanan (yang sudah ada di $row) sebagai sumber id
      $id = bin2hex($this->encrypter->encrypt($row->kode_layanan));
      $lnKodeInt = (int) $row->kode_layanan;
      $response = [];

      // Ambil detail item layanan (tetap ada, seperti semula)
      $detil = $layananDet->getAllDataById(['kode_layanan' => $row->kode_layanan]);
      $items = [];
      foreach ($detil as $d) {
        $items[] = $d->nama_layanan ?? $d->kode_jenis;
      }

      // ambil data user (logika tetap dipertahankan)
      $personName = null;
      $userIdentity = '-';
      $instansi = '-';
      $u = null;

      // 1) Cek langsung dari user_id (FK)
      if (!empty($row->user_id)) {
        $u = $userModel->getDataById('user_id', $row->user_id);
      }

      // 2) Jika belum ada, cek berdasarkan email (user_email)
      if (!$u && !empty($row->user_email)) {
        $users = $userModel->getAllDataById(['user_email' => $row->user_email]);
        if (!empty($users))
          $u = is_array($users) ? $users[0] : $users;
      }

      // 3) Jika masih belum ketemu, cari user_id dari invoice (no_invoice)
      if (!$u && !empty($row->no_invoice)) {
        $foundUserId = $this->formulirAdminModel->findUserIdByNoTransaksi($this->table, $row->no_invoice);
        if ($foundUserId !== null) {
          $u = $userModel->getDataById('user_id', $foundUserId);
        }
      }

      // 4) Jika user ditemukan, ambil info
      if ($u) {
        $personName = $u->user_name ?? $u->user_email ?? '-';
        $instansi = $u->user_instansi ?? '-';
        $userIdentity = $u->user_identity ?? '-';
      } else {
        $personName = $row->user_email ?? '-';
      }

      $pemesanNama = !empty($personName) ? $personName : '-';
      $tipe = !empty($userIdentity) ? strtoupper($userIdentity) : '-';

      $logDateRaw = $logMap[$lnKodeInt] ?? $row->tanggal_checkout ?? null;
      $tanggal = '-';
      if (!empty($logDateRaw)) {
        $ts = strtotime($logDateRaw);
        if ($ts !== false) {
          $tanggal = date('d-m-Y H:i', $ts);
        }
      }

      $metaParts = [];
      if ($tanggal !== '-') {
        $metaParts[] = $tanggal;
      }
      if ($tipe !== '-') {
        $metaParts[] = $tipe;
      }
      $metaLine = !empty($metaParts) ? implode(' | ', $metaParts) : '-';

      $badge = '';
      if ((int) ($row->jumlah_kaji_ulang ?? 0) > 0) {
        $badge = '<span class="badge bg-danger text-white ms-1" title="Data pengujian ulang">Uji Ulang</span>';
      }

      $combined = '
                <div style="line-height:1.3;">
                    <span style="font-size:1rem; font-weight:600; display:block;">' . esc($pemesanNama) . '</span>
                    <span style="font-size:0.9rem; color:#555;">' . esc($metaLine) . '</span>' . $badge . '
                </div>';

      $response[] = $combined;
      $response[] = $this->formatStatus($row->status_layanan);

      // Status Pembayaran (sama seperti di Pelayanan)
      $bayarStatusVal = isset($payMap[$lnKodeInt]) ? $payMap[$lnKodeInt]['status'] : 0;
      $invoiceFile = isset($payMap[$lnKodeInt]) ? $payMap[$lnKodeInt]['invoiceFile'] : null;
      $buktiBayar = isset($payMap[$lnKodeInt]) ? $payMap[$lnKodeInt]['buktiBayar'] : null;

      if ($bayarStatusVal === 0) {
        // Cek apakah ada bukti bayar (Belum Diverifikasi)
        if (!empty($buktiBayar) && !empty($invoiceFile)) {
          $response[] = '<span class="badge bg-warning">Belum Diverifikasi</span>';
        }
        // Cek apakah invoice sudah dikirim (Menunggu pembayaran)
        elseif (!empty($invoiceFile)) {
          $response[] = '<span class="badge bg-info">Menunggu pembayaran</span>';
        }
        // Belum ada invoice (Belum diproses)
        else {
          $response[] = '<span class="badge bg-secondary">Belum diproses</span>';
        }
      } elseif ($bayarStatusVal === 1) {
        $response[] = '<span class="badge bg-success">Lunas</span>';
      } elseif ($bayarStatusVal === 2) {
        $response[] = '<span class="badge bg-danger">Ditolak</span>';
      } else {
        $response[] = '<button class="btn btn-sm btn-info" onclick="lokasiPembayaran(' . $lnKodeInt . ')"><i class="bi bi-credit-card"></i> Belum Bayar</button>';
      }

      $lihatDetailBtn = '<button type="button" class="btn btn-sm btn-info" 
                                title="Lihat Detail Item Layanan" 
                                onclick="loadDetail(\'' . $id . '\')">
                                <i class="bi bi-eye"></i> Lihat </button>';
      $response[] = $lihatDetailBtn;

      $response[] = $this->aksi($id, $row->status_layanan, $bayarStatusVal);

      $data[] = $response;
    }

    return $this->response->setJSON([
      'items' => $data
    ]);
  }

  public function detaillist($id = null)
  {
    if (!$id) {
      return $this->response->setJSON(['items' => []]);
    }

    try {
      $kode = $this->encrypter->decrypt(hex2bin($id));
    } catch (\Exception $e) {
      return $this->response->setJSON(['items' => []]);
    }

    // Encrypted hex parent (disimpan jika perlu dipakai di tempat lain)
    $encLnId = bin2hex($this->encrypter->encrypt($kode));

    $canEditJumlah = !$this->formulirAdminModel->isInvoiceSent((int) $kode);

    // Ambil detail layanan menggunakan model
    $rows = $this->formulirAdminModel->getDetailLayananGrouped((int) $kode);

    $data = [];
    $no = 1;

    foreach ($rows as $row) {
      $response = [];
      // No
      $response[] = $no++;
      // Layanan
      $response[] = $row->nama_layanan ?? '-';
      // Metode
      $response[] = isset($row->metode_nama) && !empty($row->metode_nama) ? esc($row->metode_nama) : '-';
      // Biaya
      $response[] = isset($row->detBiaya) ? number_format($row->detBiaya, 0, ',', '.') : '-';
      // Jumlah (hanya bisa diubah jika invoice belum dikirim)
      $jumlahVal = isset($row->jumlah) ? (int) $row->jumlah : 0;
      if ($canEditJumlah) {
        $detailKodeList = htmlspecialchars((string) ($row->detailKodeList ?? ''), ENT_QUOTES, 'UTF-8');
        $response[] = '<input type="number" min="1" class="form-control form-control-sm text-center fa-jumlah-input" '
          . 'style="max-width:90px; margin:auto;" '
          . 'value="' . $jumlahVal . '" '
          . 'data-kode-layanan="' . (int) $kode . '" '
          . 'data-detail-kode-list="' . $detailKodeList . '">';
      } else {
        $response[] = $jumlahVal;
      }

      // Status hasil grouping (1 = diterima, 2 = ditolak, lainnya = pending)
      $statusGroup = isset($row->detStatusGroup) ? (int) $row->detStatusGroup : null;
      if ($statusGroup === 1) {
        $statusHtml = '<span class="badge bg-success">Diterima</span>';
      } elseif ($statusGroup === 2) {
        $statusHtml = '<span class="badge bg-danger">Ditolak</span>';
      } else {
        $statusHtml = '<span class="badge bg-secondary">Pending</span>';
      }
      $response[] = $statusHtml;

      // Keterangan Manajer (detKetLn)
      $response[] = '<div 
                style="display:block; max-width:240px; min-width:160px; width:100%;
                max-height:120px; min-height:48px; overflow-y:auto; overflow-x:hidden;
                padding:4px 6px; border:1px solid #ddd; border-radius:4px; background:#f9f9f9;
                white-space:pre-wrap; word-break:break-word; font-size:0.9rem;">'
        . htmlspecialchars($row->detKetLn ?? '', ENT_QUOTES, 'UTF-8') .
        '</div>';

      // ACC - Username yang melakukan accept layanan (bisa >1 username bila multi-row dalam satu grup)
      $accUsernames = trim((string) ($row->accUsernames ?? ''));
      $response[] = $accUsernames !== '' ? htmlspecialchars($accUsernames, ENT_QUOTES, 'UTF-8') : '-';

      $data[] = $response;
    }

    // Ambil data identitas sampel
    $modelIdentitasSampel = new MyModel('t_identitas_sampel');
    $sampleRow = $modelIdentitasSampel->getDataById('kode_layanan', $kode);

    $sampleData = [
      'jenis' => $sampleRow->jenis ?? '-',
      'kemasan' => $sampleRow->kemasan ?? '-',
      'sifat' => $sampleRow->sifat ?? '-',
      'sisa' => $sampleRow->sisa ?? '-',
      'deskripsi' => $sampleRow->deskripsi ?? '-',
      'keterangan_khusus' => $sampleRow->keterangan_khusus ?? '-'
    ];

    // Ambil surat pengantar dari t_layanan (kolom: surat_pernyataan)
    $modelLayanan = new MyModel($this->table);
    $layanan = $modelLayanan->getDataById('kode_layanan', $kode);
    $suratPengantar = $layanan->surat_pernyataan ?? null;

    $sampleData['surat_pengantar'] = $suratPengantar;
    $sampleData['surat_pengantar_url'] = $suratPengantar ? base_url('uploads/surat_pengantar/' . $suratPengantar) : null;

    return $this->response->setJSON([
      'items' => $data,
      'sampleData' => $sampleData,
      'kode_layanan' => (int) $kode
    ]);
  }

  public function updateJumlahDetail()
  {
    $kodeLayanan = (int) ($this->request->getPost('kode_layanan') ?? 0);
    $jumlahBaru = (int) ($this->request->getPost('jumlah') ?? 0);
    $detailKodeListRaw = trim((string) ($this->request->getPost('detail_kode_list') ?? ''));

    if ($kodeLayanan <= 0 || $jumlahBaru < 1 || $detailKodeListRaw === '') {
      return $this->response->setJSON([
        'res' => false,
        'msg' => 'Data update jumlah tidak valid.',
        'xname' => csrf_token(),
        'xhash' => csrf_hash()
      ]);
    }

    if ($this->formulirAdminModel->isInvoiceSent($kodeLayanan)) {
      return $this->response->setJSON([
        'res' => false,
        'msg' => 'Jumlah tidak dapat diubah karena invoice sudah dikirim.',
        'xname' => csrf_token(),
        'xhash' => csrf_hash()
      ]);
    }

    $detailKodeList = array_values(array_filter(array_map('intval', explode(',', $detailKodeListRaw)), static fn($v) => $v > 0));
    if (empty($detailKodeList)) {
      return $this->response->setJSON([
        'res' => false,
        'msg' => 'Detail layanan tidak ditemukan.',
        'xname' => csrf_token(),
        'xhash' => csrf_hash()
      ]);
    }

    $details = $this->formulirAdminModel->getDetailRowsForJumlahUpdate($kodeLayanan, $detailKodeList);

    if (empty($details)) {
      return $this->response->setJSON([
        'res' => false,
        'msg' => 'Data detail layanan tidak valid.',
        'xname' => csrf_token(),
        'xhash' => csrf_hash()
      ]);
    }

    $updated = $this->formulirAdminModel->updateDetailJumlahGrouped($kodeLayanan, $details, $jumlahBaru);
    if (!$updated) {
      return $this->response->setJSON([
        'res' => false,
        'msg' => 'Gagal memperbarui jumlah layanan.',
        'xname' => csrf_token(),
        'xhash' => csrf_hash()
      ]);
    }

    return $this->response->setJSON([
      'res' => true,
      'msg' => 'Jumlah berhasil diperbarui.',
      'xname' => csrf_token(),
      'xhash' => csrf_hash()
    ]);
  }

  public function uploadSuratPengantar()
  {
    $kode_layanan = (int) $this->request->getPost('kode_layanan');
    if ($kode_layanan <= 0) {
      return $this->response->setJSON([
        'res' => false,
        'msg' => 'Kode layanan tidak valid.',
        'xname' => csrf_token(),
        'xhash' => csrf_hash()
      ]);
    }

    // Ambil data layanan
    $modelLayanan = new MyModel($this->table);
    $layanan = $modelLayanan->getDataById('kode_layanan', $kode_layanan);
    if (!$layanan) {
      return $this->response->setJSON([
        'res' => false,
        'msg' => 'Data layanan tidak ditemukan.',
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
      $oldFile = $layanan->surat_pernyataan ?? null;
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

      // Update kolom surat_pernyataan di t_layanan
      $modelLayanan->updateData(['surat_pernyataan' => $newFileName], 'kode_layanan', $kode_layanan);

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

  private function aksi($id, $status, $status_bayar = 0)
  {
    $btn = '<div id="' . $id . '" class="float-end d-flex align-items-center" style="gap:6px;">';

    // tombol Approve (sesuai kondisi Anda) – tampil seperti sekarang
    if ($status == 3) {
      // Cek status_bayar: hanya bisa approve jika status_bayar = 1 (Lunas)
      if ($status_bayar === 1) {
        $btn .= '<span class="text-success btn-action" title="Setujui" onclick="confirmApprove(event)" style="display:inline-flex;align-items:center;justify-content:center;width:25px;height:25px;border-radius:6px;">'
          . '<i class="bi bi-check-circle"></i></span>';
      } else {
        $btn .= '<span class="text-muted" title="Tidak dapat disetujui - Pembayaran belum lunas" style="display:inline-flex;align-items:center;justify-content:center;width:25px;height:25px;border-radius:6px;cursor:not-allowed;">'
          . '<i class="bi bi-check-circle"></i></span>';
      }
      // divider kecil (opsional)
      $btn .= '<span class="text-muted" style="margin-left:4px;margin-right:4px;">|</span>';
    }

    // --- tambahkan tombol WhatsApp di samping approve jika nomor tersedia (berbentuk kotak) ---
    try {
      // decrypt id (kode_layanan)
      $kode_layanan = null;
      try {
        $kode_layanan = $this->encrypter->decrypt(hex2bin($id));
      } catch (\Exception $e) {
        $kode_layanan = null;
      }

      if ($kode_layanan !== null) {
        // ambil header layanan untuk menemukan user_id / user_emailmenggunakan model
        $row = $this->formulirAdminModel->getLayananHeaderById($this->table, $this->id, (int) $kode_layanan);

        $phoneRaw = '';
        $userObj = null;

        if ($row) {
          $modelUser = new MyModel('account_users');

          // 1) coba dari user_id FK
          if (!empty($row->user_id)) {
            $userObj = $modelUser->getDataById('user_id', $row->user_id);
          }

          // 2) jika belum ada, coba cari berdasarkan user_email
          if (!$userObj && !empty($row->user_email)) {
            $users = $modelUser->getAllDataById(['user_email' => $row->user_email]);
            if (!empty($users))
              $userObj = is_array($users) ? $users[0] : $users;
          }

          // Ambil field telepon dari objek user (cek beberapa nama kolom umum)
          if ($userObj) {
            if (isset($userObj->user_phone) && !empty($userObj->user_phone))
              $phoneRaw = $userObj->user_phone;
            elseif (isset($userObj->user_telpon) && !empty($userObj->user_telpon))
              $phoneRaw = $userObj->user_telpon;
            elseif (isset($userObj->user_telp) && !empty($userObj->user_telp))
              $phoneRaw = $userObj->user_telp;
            elseif (isset($userObj->phone) && !empty($userObj->phone))
              $phoneRaw = $userObj->phone;
            // tambahkan nama kolom lain jika DB anda memakai nama berbeda
          }
        }

        // normalisasi nomor
        if (!empty($phoneRaw)) {
          $waDigits = $this->normalize_phone_for_whatsapp($phoneRaw);
          if ($waDigits !== '') {
            $displayName = $userObj->user_name ?? null;

            // buat pesan pembuka (encoded)
            $message = $displayName ? "Permisi saudara/i " . $displayName . ", kami dari pihak SIMLAB Terpadu ULM, terkait layanan pengujian sampel yang Anda pesan mendapatkan beberapa penolakan. Kami ingin mengonfirmasi apakah pengujian ingin tetap dilanjutkan atau tidak?" : "Halo, saya ingin bertanya tentang layanan.";
            $msgEncoded = rawurlencode($message);

            $waUrl = "https://wa.me/" . $waDigits . "?text=" . $msgEncoded;

            // Tombol WA bergaya kotak, mirip accept â€” gunakan onclick membuka tab baru
            $btn .= '<span class="text-success btn-action" title="Chat via WhatsApp" '
              . 'style="display:inline-flex;align-items:center;justify-content:center;width:25px;height:25px;border:1px solid #28a745;border-radius:6px;cursor:pointer;background:#ffffff;" '
              . 'onclick="window.open(\'' . esc($waUrl) . '\', \'_blank\', \'noopener\')">'
              . '<i class="bi bi-whatsapp"></i>'
              . '</span>';
            // optional small divider after WA
            $btn .= '<span class="text-muted" style="margin-left:4px;margin-right:2px;">|</span>';
          }
        }
      }
    } catch (\Throwable $e) {
      // jangan ganggu rendering tabel bila terjadi error; bisa di-log bila perlu
      // log_message('warning', 'aksi() WA button error: ' . $e->getMessage());
    }

    // tutup wrapper
    $btn .= '</div>';
    return $btn;
  }

  private function formatStatus($status)
  {
    switch ($status) {
      case 0:
        return '<span class="badge bg-secondary">Draft</span>';
      case 1:
        return '<span class="badge bg-warning">In Review Manajer</span>';
      case 2:
        return '<span class="badge bg-danger">Ditolak</span>';
      case 3:
        return '<span class="badge bg-info">Belum diproses</span>';
      case 4:
        return '<span class="badge bg-primary">Dalam pengujian</span>';
      case 5:
        return '<span class="badge bg-primary">LHUS diproses</span>';
      case 6:
        return '<span class="badge bg-success">LHUS disetujui</span>';
      case 7:
        return '<span class="badge bg-primary">LHU diproses</span>';
      case 8:
        return '<span class="badge bg-success">LHU disetujui</span>';
      case 9:
        return '<span class="badge bg-secondary">Pengujian selesai</span>';
      default:
        return '<span class="badge bg-dark">Unknown</span>';
    }
  }

  /**
   * Approve endpoint (dipanggil via AJAX POST)
   * URL: /formuliradmin/approve/{encId}
   */
  public function approve($encId = null)
  {
    $response = [
      'res' => false,
      'msg' => 'Approve gagal dilakukan.',
      'xname' => csrf_token(),
      'xhash' => csrf_hash()
    ];

    if (!$encId) {
      $response['msg'] = 'ID tidak diberikan.';
      return $this->response->setJSON($response);
    }

    try {
      // decrypt id (sama cara yg Anda pakai di tempat lain)
      $kode_layanan = $this->encrypter->decrypt(hex2bin($encId));

      // simple validation
      if (empty($kode_layanan)) {
        $response['msg'] = 'ID tidak valid.';
        return $this->response->setJSON($response);
      }

      // Cek status_bayar terlebih dahulu menggunakan model
      $paymentCheck = $this->formulirAdminModel->getLastPaymentStatus((int) $kode_layanan);

      if (!$paymentCheck || (int) $paymentCheck->status_bayar !== 1) {
        $response['msg'] = 'Tidak dapat menyetujui - Pembayaran belum lunas.';
        return $this->response->setJSON($response);
      }

      $model = new MyModel($this->table);

      // Anda bisa men-set status apa yg "approve" maksudnya.
      // Contoh ini set status_layanan => 1 (In Review Manajer).
      // Ubah menjadi status lain jika bisnis logic menghendaki.
      $update = [
        'status_layanan' => 4,
        // 'lnTglUpdate' => date('Y-m-d H:i:s') .
      ];

      $res = $model->updateData($update, $this->id, $kode_layanan);

      if ($res) {
        // Update log sampel: set kolom pengujian dengan waktu saat ini
        try {
          $modelLogSampel = new MyModel('t_log_sampel');
          $logUpdate = [
            'pengujian' => date('Y-m-d H:i:s')
          ];

          // Update berdasarkan kode_layanan
          $logUpdateResult = $modelLogSampel->updateData($logUpdate, 'kode_layanan', $kode_layanan);

          if (!$logUpdateResult) {
            log_message('warning', 'Gagal update log sampel untuk kode_layanan: ' . $kode_layanan);
          }
        } catch (\Exception $logEx) {
          // Log error tapi jangan gagalkan approve
          log_message('error', 'Error update log sampel: ' . $logEx->getMessage());
        }

        $response['res'] = true;
        $response['msg'] = 'Data berhasil diapprove.';
        // bila perlu kirim status baru juga
        $response['newStatus'] = $update['status_layanan'];
      } else {
        $response['msg'] = 'Gagal update database (tidak ada perubahan atau error).';
        // log db error bila model expose
        try {
          $err = $model->db->error();
          log_message('error', 'Approve gagal: ' . json_encode($err));
        } catch (\Throwable $e) {
        }
      }
    } catch (\Exception $e) {
      log_message('error', 'Approve exception: ' . $e->getMessage());
      $response['msg'] = 'Approve exception: ' . $e->getMessage();
    }

    // kirim token CSRF baru juga supaya frontend bisa mengupdate
    $response['xname'] = csrf_token();
    $response['xhash'] = csrf_hash();

    return $this->response->setJSON($response);
  }


  // --- MULAI: WhatsApp button helper (paste sebelum pembuatan $combined) ---
  /**
   * Ambil nomor telepon user dari object $u (bisa user_phone, user_telpon, user_telp, dsb.)
   * Normalisasi: hapus semua selain digit, ubah leading 0 -> 62 (Indonesia) jika perlu.
   * Jika kosong / tidak valid -> return empty string.
   */
  public function normalize_phone_for_whatsapp($rawPhone)
  {
    if (empty($rawPhone))
      return '';
    // keep digits only
    $digits = preg_replace('/\D+/', '', (string) $rawPhone);
    if ($digits === '')
      return '';
    // jika mulai dengan 0 -> ganti 0 dengan 62 (Indonesia)
    if (strpos($digits, '0') === 0) {
      $digits = '62' . substr($digits, 1);
    }
    // jika mulai dengan 62 sudah ok; jika mulai dengan +62 (already removed +), ok
    // jika panjang terlalu pendek, bail out
    if (strlen($digits) < 8)
      return '';
    return $digits;
  }

  /**
   * Buat tombol HTML untuk membuka WhatsApp (wa.me). Aman untuk output view.
   * $name digunakan untuk pesan pembuka (opsional).
   */
  public function whatsapp_button_html($phoneDigits, $name = null)
  {
    if (empty($phoneDigits))
      return '';
    $text = $name ? "Halo%20" . rawurlencode($name) . "%2C%20saya%20ingin%20bertanya%20tentang%20layanan." : "Halo%2C%20saya%20ingin%20bertanya%20tentang%20layanan.";
    $url = "https://wa.me/" . $phoneDigits . "?text=" . $text;
    // tombol kecil dengan icon bootstrap (bi bi-whatsapp)
    return '<a href="' . esc($url) . '" target="_blank" rel="noopener noreferrer" class="btn btn-sm btn-success ms-1" title="Chat via WhatsApp">'
      . '<i class="bi bi-whatsapp"></i>'
      . '</a>';
  }
}
