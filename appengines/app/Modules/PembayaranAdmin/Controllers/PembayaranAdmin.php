<?php

namespace Modules\PembayaranAdmin\Controllers;

use App\Controllers\BaseController;
use Modules\PembayaranAdmin\Models\PembayaranAdminModel;

class PembayaranAdmin extends BaseController
{
  private $table = 't_pembayaran';
  private $id = 'kode_bayar';
  protected $pembayaranModel;

  public function __construct()
  {
    $this->pembayaranModel = new PembayaranAdminModel();
  }

  public function index()
  {
    $data = [
      'title' => 'Verifikasi Pembayaran',
    ];
    return view('Modules\PembayaranAdmin\Views\v_pembayaran_admin', $data);
  }

  /**
   * Get data list untuk tabel admin - menampilkan SEMUA tagihan yang sudah ada invoice (tidak ada filter user)
   */
  public function dataList()
  {
    try {
      $data = [];

      // Ambil parameter filter dari request
      $tanggalAwal = $this->request->getGet('tanggal_awal');
      $tanggalAkhir = $this->request->getGet('tanggal_akhir');
      $filterStatus = $this->request->getGet('status'); // 0=Menunggu, 1=Terkirim, 2=Belum Diverifikasi, 3=Terverifikasi, 4=Ditolak

      // Debug log
      log_message('info', 'Filter parameters - Tanggal Awal: ' . ($tanggalAwal ?? 'null') . ', Tanggal Akhir: ' . ($tanggalAkhir ?? 'null') . ', Status: ' . ($filterStatus ?? 'null'));

      $list = $this->pembayaranModel->getAdminPaymentList($tanggalAwal, $tanggalAkhir);

      // Total modal detail layanan harus mengikuti item yang sudah diterima (status_layanan=1)
      $lnKodeList = [];
      foreach ($list as $item) {
        if (!empty($item->kode_layanan)) {
          $lnKodeList[] = $item->kode_layanan;
        }
      }
      $acceptedTotalMap = $this->pembayaranModel->getAcceptedDetailTotalMap($lnKodeList);

      foreach ($list as $row) {
        $encrypted_id = bin2hex(service('encrypter')->encrypt($row->kode_bayar));

        // Cek file invoice dari session (file baru yang belum dikirim)
        $sessionKey = 'temp_invoice_' . $row->kode_bayar;
        $tempInvoiceFile = session()->get($sessionKey);

        // Status berdasarkan no_invoicedan bukti_bayar
        // 0 = Menunggu Proses (no_invoicekosong)
        // 1 = Terkirim (no_invoiceterisi, bukti bayar belum ada)
        // 2 = Belum Diverifikasi (no_invoiceterisi, bukti bayar ada, status_bayar = 0)
        // 3 = Terverifikasi (status_bayar = 1)
        // 4 = Ditolak (status_bayar = 2)
        $invoiceStatus = 0;
        if (!empty($row->no_invoice)) {
          // Invoice sudah terkirim
          if (!empty($row->bukti_bayar)) {
            // Bukti bayar sudah ada, cek status verifikasi
            if ($row->status_bayar == 1) {
              $invoiceStatus = 3; // Terverifikasi
            } elseif ($row->status_bayar == 2) {
              $invoiceStatus = 4; // Ditolak
            } else {
              $invoiceStatus = 2; // Belum Diverifikasi
            }
          } else {
            $invoiceStatus = 1; // Terkirim (belum ada bukti bayar)
          }
        } else {
          $invoiceStatus = 0; // Menunggu Proses (invoice belum terkirim)
        }

        // Filter berdasarkan status jika dipilih
        if ($filterStatus !== null && $filterStatus !== '' && $filterStatus !== 'all') {
          // Konversi filterStatus ke integer untuk perbandingan
          $filterStatusInt = (int) $filterStatus;

          // Debug log untuk troubleshooting
          log_message('debug', 'Comparing status - filterStatus: ' . $filterStatusInt . ' (type: ' . gettype($filterStatusInt) . '), invoiceStatus: ' . $invoiceStatus . ' (type: ' . gettype($invoiceStatus) . ')');

          if ($filterStatusInt !== $invoiceStatus) {
            continue; // Skip row ini jika tidak sesuai filter
          }
        }

        $status = $this->formatInvoiceStatus($invoiceStatus);

        // Status pembayaran untuk logic button (tetap gunakan status_bayar)
        $paymentStatus = $this->getPaymentStatus($row->bukti_bayar, $row->status_bayar);

        // Ambil data user (sama seperti di Tagihan)
        $personName = null;
        $userIdentity = '-';
        $instansi = '-';
        $u = null;

        $u = $this->pembayaranModel->findPemesanUser($row->user_id ?? null, $row->user_email ?? null);

        // Jika user ditemukan, ambil info
        if ($u) {
          $personName = $u->user_name ?? $u->user_email ?? '-';
          $instansi = $u->user_instansi ?? '-';
          $userIdentity = $u->user_identity ?? '-';
        } else {
          $personName = $row->user_email ?? '-';
        }

        // Tombol aksi
        $aksi = $this->aksiButton($encrypted_id, $paymentStatus, $row->bukti_bayar, $row->invoice_file, $row->no_invoice, $tempInvoiceFile, $u);

        $pemesanNama = !empty($personName) ? $personName : '-';
        $tipe = !empty($userIdentity) ? strtoupper($userIdentity) : '-';
        $tanggal = !empty($row->tanggal_checkout) ? date('d-m-Y H:i', strtotime($row->tanggal_checkout)) : '-';

        $badge = '';
        if ((int) ($row->jumlah_kaji_ulang ?? 0) > 0) {
          $badge = '<span class="badge bg-danger text-white ms-1" title="Data uji ulang">Uji Ulang</span>';
        }

        $combined = '
                    <div style="line-height:1.3;">
                        <span style="font-size:1rem; font-weight:600;">' . esc($pemesanNama) . '</span>
                        <div style="font-size:0.9rem; color:#555; display:flex; align-items:center; gap:6px;">
                            <span>' . esc($tipe) . '</span>' . $badge . '
                        </div>
                    </div>';

        $invoiceNumber = !empty($row->no_invoice)
          ? esc($row->no_invoice)
          : '<span class="text-muted">-</span>';

        $invoiceDisplay = '
                    <div style="line-height:1.3;">
                        <span class="fw-semibold">' . $invoiceNumber . '</span><br>
                        <span class="text-muted" style="font-size:0.85rem;">' . esc($tanggal) . '</span>
                    </div>';

        $detailTotal = (float) ($acceptedTotalMap[(string) $row->kode_layanan] ?? 0);
        $encodedLn = bin2hex(service('encrypter')->encrypt($row->kode_layanan));
        $detailButton = '<button type="button" class="btn btn-sm btn-outline-primary btn-detail-layanan"' .
          ' data-detail-id="' . esc($encodedLn, 'attr') . '"' .
          ' data-pemesan="' . esc($pemesanNama, 'attr') . '"' .
          ' data-invoice="' . esc($row->no_invoice ?? '-', 'attr') . '"' .
          ' data-total="' . $detailTotal . '">' .
          '<i class="bi bi-card-list"></i> Detail</button>';

        // Kolom File Invoice - dengan logic seperti di Tagihan
        $fileInvoiceDisplay = '';
        $currentFile = !empty($tempInvoiceFile) ? $tempInvoiceFile : ($row->invoice_file ?? '');

        if (!empty($currentFile)) {
          if (!empty($tempInvoiceFile)) {
            // File baru dari session (belum dikirim) - Status: Menunggu Proses
            $fileInvoiceDisplay = '<span class="badge bg-info"><i class="bi bi-clock-history"></i> File Terupload</span>';
          } else {
            // File dari database (sudah dikirim) - Status: Terkirim
            $fileInvoiceDisplay = '<a href="' . base_url('uploads/invoice/' . $currentFile) . '" target="_blank" class="btn btn-sm btn-info"><i class="bi bi-file-pdf"></i> Lihat</a>';
          }
        } else {
          // Belum ada file sama sekali - Status: Menunggu Proses
          $fileInvoiceDisplay = '<span class="text-muted">-</span>';
        }

        // Kolom bukti bayar
        // Hanya tampilkan "-" jika status = Menunggu Proses (invoiceStatus = 0)
        // Untuk status lain (Terkirim, Belum Diverifikasi, Terverifikasi, Ditolak), tampilkan file jika ada
        $buktiBayar = '-';
        if ($invoiceStatus == 0) {
          // Status Menunggu Proses - selalu tampilkan "-"
          $buktiBayar = '<span class="text-muted">-</span>';
        } else {
          // Status Terkirim/Belum Diverifikasi/Terverifikasi/Ditolak - tampilkan bukti bayar jika ada
          if (!empty($row->bukti_bayar)) {
            $buktiBayar = '<a href="' . base_url('uploads/bukti/' . $row->bukti_bayar) . '" target="_blank" class="btn btn-sm btn-success"><i class="bi bi-file-earmark-check"></i> Lihat</a>';
          } else {
            $buktiBayar = '<span class="text-muted">-</span>';
          }
        }

        // Response array: 8 kolom
        $data[] = [
          $invoiceDisplay,
          $combined,
          $detailButton,
          $fileInvoiceDisplay,
          $buktiBayar,
          $status,
          $aksi
        ];
      }

      return $this->response->setJSON(["items" => $data]);
    } catch (\Exception $e) {
      log_message('error', 'PembayaranAdmin dataList error: ' . $e->getMessage());
      return $this->response->setJSON([
        "items" => [],
        "error" => $e->getMessage()
      ]);
    }
  }

  /**
   * Detail layanan untuk modal (digunakan oleh sayTable di view)
   */
  public function detailLayanan($encLnKode = null)
  {
    if (empty($encLnKode)) {
      return $this->response->setJSON(['items' => [], 'total' => 0]);
    }

    try {
      $kode_layanan = service('encrypter')->decrypt(hex2bin($encLnKode));
    } catch (\Throwable $e) {
      return $this->response->setJSON(['items' => [], 'total' => 0]);
    }

    $rows = $this->pembayaranModel->getDetailLayananItems($kode_layanan);

    $items = [];
    foreach ($rows as $idx => $det) {
      $qty = max(1, (int) ($det->jumlah ?? 0));
      $subtotal = (float) ($det->biaya ?? 0);
      $unit = $qty > 0 ? $subtotal / $qty : $subtotal;

      // Gabungkan parameter dan instrumen/alat menjadi satu kolom "Layanan"
      $parameter = esc($det->nama_layanan ?? $det->ref_nama ?? '-', 'html');
      $instrumen = esc($det->nama ?? $det->kode_alat ?? '-', 'html');
      $layanan = $parameter;
      if (!empty($instrumen) && $instrumen !== '-') {
        $layanan .= ' (' . $instrumen . ')';
      }

      $items[] = [
        $idx + 1,
        $layanan,
        esc($det->metode_nama ?? '-', 'html'),
        $this->formatDiskonValue($det->ref_diskon ?? 0),
        $this->formatCurrencyIDR($unit),
        $qty,
        $this->formatCurrencyIDR($subtotal),
      ];
    }

    return $this->response->setJSON([
      'items' => $items,
      'total' => count($items)
    ]);
  }

  /**
   * Tentukan status pembayaran untuk ADMIN VIEW
   * @return int 0 = Belum Diunggah, 1 = Belum Diverifikasi, 2 = Terverifikasi, 3 = Tidak Terverifikasi
   */
  private function getPaymentStatus($buktiBayar, $status_bayar)
  {
    // Jika bukti_bayar == null → Belum Diunggah
    if (empty($buktiBayar)) {
      return 0;
    }

    // Jika bukti_bayar != null & status_bayar == 1 → Terverifikasi
    if (!empty($buktiBayar) && $status_bayar == 1) {
      return 2;
    }

    // Jika bukti_bayar != null & status_bayar == 2 → Tidak Terverifikasi (Ditolak)
    if (!empty($buktiBayar) && $status_bayar == 2) {
      return 3;
    }

    // Jika bukti_bayar != null & status_bayar == 0 → Belum Diverifikasi (Menunggu)
    if (!empty($buktiBayar) && $status_bayar == 0) {
      return 1;
    }

    return 0; // Default
  }

  /**
   * Format status badge untuk INVOICE STATUS (dengan status verifikasi)
   * Berdasarkan no_invoicedan status_bayar
   * 0 = Menunggu Proses (no_invoicekosong)
   * 1 = Terkirim (no_invoiceterisi, belum ada bukti bayar)
   * 2 = Belum Diverifikasi (ada bukti bayar, status_bayar = 0)
   * 3 = Terverifikasi (status_bayar = 1)
   * 4 = Ditolak (status_bayar = 2)
   */
  private function formatInvoiceStatus($status)
  {
    switch ($status) {
      case 0:
        return '<span class="badge bg-warning">Menunggu Proses</span>';
      case 1:
        return '<span class="badge bg-success">Terkirim</span>';
      case 2:
        return '<span class="badge bg-info">Belum Diverifikasi</span>';
      case 3:
        return '<span class="badge bg-primary">Terverifikasi</span>';
      case 4:
        return '<span class="badge bg-danger">Ditolak</span>';
      default:
        return '<span class="badge bg-secondary">Unknown</span>';
    }
  }

  /**
   * Format status badge untuk PAYMENT VERIFICATION (tidak digunakan di kolom, hanya untuk logic)
   */
  private function formatStatus($status)
  {
    switch ($status) {
      case 0:
        return '<span class="badge bg-secondary">Belum Diunggah</span>';
      case 1:
        return '<span class="badge bg-warning text-dark">Belum Diverifikasi</span>';
      case 2:
        return '<span class="badge bg-success">Terverifikasi</span>';
      case 3:
        return '<span class="badge bg-danger">Tidak Terverifikasi</span>';
      default:
        return '<span class="badge bg-secondary">Unknown</span>';
    }
  }

  private function formatCurrencyIDR($value)
  {
    return 'Rp ' . number_format((float) $value, 0, ',', '.');
  }

  private function formatDiskonValue($value)
  {
    $number = (float) $value;
    if (!is_finite($number) || $number === 0.0) {
      return '-';
    }

    if (abs($number) <= 100) {
      if (fmod($number, 1.0) === 0.0) {
        return number_format($number, 0, ',', '.') . '%';
      }
      return rtrim(rtrim(number_format($number, 2, ',', '.'), '0'), ',') . '%';
    }

    return $this->formatCurrencyIDR($number);
  }

  /**
   * Tombol aksi admin - Upload & Kirim Invoice (GABUNGAN), Upload Bukti, Terima, Tolak
   */
  private function aksiButton($id, $status, $file, $invoiceFile, $invoiceNo, $tempInvoiceFile = null, $userObj = null)
  {
    $fileUrl = !empty($file) ? base_url('uploads/bukti/' . $file) : '';
    $invoiceUrl = !empty($invoiceFile) ? base_url('uploads/invoice/' . $invoiceFile) : '';

    // Persiapan data WhatsApp
    $waUrl = '';
    $hasWhatsApp = false;
    if ($userObj) {
      $phoneRaw = '';

      // Ambil field telepon dari objek user
      if (isset($userObj->user_phone) && !empty($userObj->user_phone))
        $phoneRaw = $userObj->user_phone;
      elseif (isset($userObj->user_telpon) && !empty($userObj->user_telpon))
        $phoneRaw = $userObj->user_telpon;
      elseif (isset($userObj->user_telp) && !empty($userObj->user_telp))
        $phoneRaw = $userObj->user_telp;
      elseif (isset($userObj->phone) && !empty($userObj->phone))
        $phoneRaw = $userObj->phone;

      // Normalisasi nomor
      if (!empty($phoneRaw)) {
        $waDigits = $this->normalize_phone_for_whatsapp($phoneRaw);
        if ($waDigits !== '') {
          $displayName = $userObj->user_name ?? null;

          // Buat pesan pembuka (encoded)
          $message = $displayName
            ? "Assalamualaikum Kak " . $displayName . ", saya ingin menginformasikan terkait pembayaran layanan pengujian."
            : "Halo, saya ingin menginformasikan terkait pembayaran layanan pengujian.";
          $msgEncoded = rawurlencode($message);

          $waUrl = "https://wa.me/" . $waDigits . "?text=" . $msgEncoded;
          $hasWhatsApp = true;
        }
      }
    }

    $html = '<div id="' . $id . '" class="float-end">';

    // Dropdown Button
    $html .= '<div class="dropdown">';
    $html .= '<button class="btn btn-sm btn-secondary dropdown-toggle" type="button" data-bs-toggle="dropdown" aria-expanded="false">';
    $html .= '<i class="bi bi-three-dots-vertical"></i> Aksi';
    $html .= '</button>';
    $html .= '<ul class="dropdown-menu dropdown-menu-end">';

    // Menu 1: Upload & Kirim Invoice (GABUNGAN - aktif jika invoice belum dikirim)
    $uploadKirimDisabled = (!empty($invoiceNo)) ? 'disabled' : '';
    $uploadKirimTitle = (!empty($invoiceNo)) ? 'Invoice sudah terkirim' : 'Upload & Kirim Invoice';
    $iconClass = (!empty($invoiceNo)) ? 'text-secondary' : 'text-info';

    $html .= '<li><a class="dropdown-item btn-action ' . $uploadKirimDisabled . '" href="javascript:void(0)" title="' . $uploadKirimTitle . '" data-invoiceurl="' . esc($invoiceUrl) . '" onclick="uploadKirimInvoice(event)">';
    $html .= '<i class="bi bi-send-check ' . $iconClass . '"></i> Upload & Kirim Invoice</a></li>';

    $html .= '<li><hr class="dropdown-divider"></li>';

    // Menu 2: Upload Bukti Bayar (admin bisa upload bukti untuk user)
    // DISABLED jika invoice belum TERKIRIM (invoiceNo kosong) ATAU sudah terverifikasi
    $uploadBuktiDisabled = (empty($invoiceNo) || $status == 2) ? 'disabled' : '';

    if (empty($invoiceNo)) {
      $uploadBuktiTitle = 'Kirim invoice terlebih dahulu';
    } else if ($status == 2) {
      $uploadBuktiTitle = 'Sudah terverifikasi';
    } else {
      $uploadBuktiTitle = 'Upload Bukti Bayar';
    }

    $iconClassBukti = (empty($invoiceNo) || $status == 2) ? 'text-secondary' : 'text-warning';
    $html .= '<li><a class="dropdown-item btn-action ' . $uploadBuktiDisabled . '" href="javascript:void(0)" title="' . $uploadBuktiTitle . '" data-fileurl="' . esc($fileUrl) . '" onclick="uploadBukti(event)">';
    $html .= '<i class="bi bi-upload ' . $iconClassBukti . '"></i> Upload Bukti Bayar</a></li>';

    $html .= '<li><hr class="dropdown-divider"></li>';

    // Menu 3: Terima (hanya aktif jika Belum Diverifikasi atau Tidak Terverifikasi)
    if ($status == 1 || $status == 3) {
      $html .= '<li><a class="dropdown-item btn-action" href="javascript:void(0)" title="Terima & Verifikasi" onclick="terimaVerifikasi(event)">';
      $html .= '<i class="bi bi-check-circle text-success"></i> Terima & Verifikasi</a></li>';
    } else {
      $html .= '<li><a class="dropdown-item btn-action disabled" href="javascript:void(0)" title="Tidak perlu verifikasi">';
      $html .= '<i class="bi bi-check-circle text-secondary"></i> Terima & Verifikasi</a></li>';
    }

    // Menu 4: Tolak (hanya aktif jika Belum Diverifikasi)
    if ($status == 1) {
      $html .= '<li><a class="dropdown-item btn-action" href="javascript:void(0)" title="Tolak Verifikasi" onclick="tolakVerifikasi(event)">';
      $html .= '<i class="bi bi-x-circle text-danger"></i> Tolak Verifikasi</a></li>';
    } else {
      $html .= '<li><a class="dropdown-item btn-action disabled" href="javascript:void(0)" title="Tidak bisa ditolak">';
      $html .= '<i class="bi bi-x-circle text-secondary"></i> Tolak Verifikasi</a></li>';
    }

    // Menu 5: Chat WhatsApp (disabled jika invoice belum terkirim atau nomor tidak tersedia)
    if ($hasWhatsApp) {
      $waDisabled = empty($invoiceNo) ? 'disabled' : '';
      $waTitle = empty($invoiceNo) ? 'Kirim invoice terlebih dahulu' : 'Chat via WhatsApp';
      $waIconClass = empty($invoiceNo) ? 'text-secondary' : 'text-success';

      if (empty($invoiceNo)) {
        $html .= '<li><hr class="dropdown-divider"></li>';
        $html .= '<li><a class="dropdown-item btn-action ' . $waDisabled . '" href="javascript:void(0)" title="' . $waTitle . '">';
        $html .= '<i class="bi bi-whatsapp ' . $waIconClass . '"></i> Chat WhatsApp</a></li>';
      } else {
        $html .= '<li><hr class="dropdown-divider"></li>';
        $html .= '<li><a class="dropdown-item btn-action" href="javascript:void(0)" title="' . $waTitle . '" onclick="window.open(\'' . esc($waUrl) . '\', \'_blank\', \'noopener\')">';
        $html .= '<i class="bi bi-whatsapp ' . $waIconClass . '"></i> Chat WhatsApp</a></li>';
      }
    }

    $html .= '</ul>';
    $html .= '</div>';
    $html .= '</div>';
    return $html;
  }

  /**
   * Upload bukti bayar oleh admin (untuk user yang tidak bisa upload sendiri)
   */
  public function uploadBukti()
  {
    try {
      $file = $this->request->getFile('file_bukti');
      $encId = $this->request->getPost('id');

      if (empty($encId)) {
        return $this->response->setJSON([
          'res' => false,
          'msg' => 'ID tidak ditemukan',
          'xname' => csrf_token(),
          'xhash' => csrf_hash()
        ]);
      }

      try {
        $id = service('encrypter')->decrypt(hex2bin($encId));
      } catch (\Throwable $e) {
        return $this->response->setJSON([
          'res' => false,
          'msg' => 'ID tidak valid',
          'xname' => csrf_token(),
          'xhash' => csrf_hash()
        ]);
      }

      if (!($file && $file->isValid() && !$file->hasMoved())) {
        return $this->response->setJSON([
          'res' => false,
          'msg' => 'File tidak valid atau belum dipilih',
          'xname' => csrf_token(),
          'xhash' => csrf_hash()
        ]);
      }

      // Upload file
      $uploadResult = $this->doUpload($file, 'bukti');

      if (!$uploadResult['status']) {
        return $this->response->setJSON([
          'res' => false,
          'msg' => $uploadResult['msg'],
          'xname' => csrf_token(),
          'xhash' => csrf_hash()
        ]);
      }

      $filename = $uploadResult['filename'];

      // Update langsung ke database (admin langsung save)
      $model = $this->pembayaranModel;
      $currentData = $model->getDataById($this->id, $id);

      // Hapus file lama jika ada
      if (!empty($currentData->bukti_bayar)) {
        $oldFile = FCPATH . 'uploads/bukti/' . $currentData->bukti_bayar;
        if (file_exists($oldFile)) {
          @unlink($oldFile);
        }
      }

      $dataPembayaran = [
        'bukti_bayar' => $filename,
        'status_bayar' => 0  // Set status Belum Diverifikasi
      ];

      $update = $model->updateData($dataPembayaran, $this->id, $id);

      if (!$update) {
        return $this->response->setJSON([
          'res' => false,
          'msg' => 'Gagal menyimpan bukti bayar',
          'xname' => csrf_token(),
          'xhash' => csrf_hash()
        ]);
      }

      return $this->response->setJSON([
        'res' => 'success',
        'msg' => 'Bukti bayar berhasil diupload',
        'xname' => csrf_token(),
        'xhash' => csrf_hash()
      ]);
    } catch (\Exception $e) {
      log_message('error', 'UploadBukti exception: ' . $e->getMessage());
      return $this->response->setJSON([
        'res' => false,
        'msg' => 'Terjadi kesalahan: ' . $e->getMessage(),
        'xname' => csrf_token(),
        'xhash' => csrf_hash()
      ]);
    }
  }

  /**
   * Upload & Kirim Invoice (GABUNGAN) - Upload file invoice + set nomor invoice sekaligus
   */
  public function uploadKirimInvoice()
  {
    try {
      $file = $this->request->getFile('file_invoice');
      $invoiceNo = trim($this->request->getPost('no_invoice')); // FIX: sesuaikan dengan name di form
      $encId = $this->request->getPost('id');

      // Validasi ID
      if (empty($encId)) {
        return $this->response->setJSON([
          'res' => false,
          'msg' => 'ID tidak ditemukan',
          'xname' => csrf_token(),
          'xhash' => csrf_hash()
        ]);
      }

      try {
        $id = service('encrypter')->decrypt(hex2bin($encId));
      } catch (\Throwable $e) {
        return $this->response->setJSON([
          'res' => false,
          'msg' => 'ID tidak valid',
          'xname' => csrf_token(),
          'xhash' => csrf_hash()
        ]);
      }

      $model = $this->pembayaranModel;
      $currentData = $model->getDataById($this->id, $id);

      if (!$currentData) {
        return $this->response->setJSON([
          'res' => false,
          'msg' => 'Data pembayaran tidak ditemukan',
          'xname' => csrf_token(),
          'xhash' => csrf_hash()
        ]);
      }

      // Cek apakah invoice sudah terkirim
      if (!empty($currentData->no_invoice)) {
        return $this->response->setJSON([
          'res' => false,
          'msg' => 'Invoice sudah terkirim sebelumnya',
          'xname' => csrf_token(),
          'xhash' => csrf_hash()
        ]);
      }

      // Validasi nomor invoice (wajib diisi)
      if (empty($invoiceNo)) {
        return $this->response->setJSON([
          'res' => false,
          'msg' => 'Nomor invoice harus diisi',
          'xname' => csrf_token(),
          'xhash' => csrf_hash()
        ]);
      }

      // Nomor invoice harus unik di seluruh pembayaran
      if ($this->pembayaranModel->invoiceNumberExists($invoiceNo)) {
        return $this->response->setJSON([
          'res' => false,
          'msg' => 'Nomor invoice sudah digunakan',
          'xname' => csrf_token(),
          'xhash' => csrf_hash()
        ]);
      }

      // Validasi file invoice (wajib diupload)
      if (!($file && $file->isValid() && !$file->hasMoved())) {
        return $this->response->setJSON([
          'res' => false,
          'msg' => 'File invoice harus diupload',
          'xname' => csrf_token(),
          'xhash' => csrf_hash()
        ]);
      }

      // Upload file invoice
      $uploadResult = $this->doUpload($file, 'invoice');

      if (!$uploadResult['status']) {
        return $this->response->setJSON([
          'res' => false,
          'msg' => $uploadResult['msg'],
          'xname' => csrf_token(),
          'xhash' => csrf_hash()
        ]);
      }

      $filename = $uploadResult['filename'];

      // Hapus file invoice lama jika ada
      if (!empty($currentData->invoice_file)) {
        $oldFile = FCPATH . 'uploads/invoice/' . $currentData->invoice_file;
        if (file_exists($oldFile)) {
          @unlink($oldFile);
        }
      }

      // Update data pembayaran: simpan file invoice + nomor invoice + tanggal invoice
      $dataPembayaran = [
        'invoice_file' => $filename,
        'no_invoice' => $invoiceNo,
        'tanggal_invoice' => date('Y-m-d H:i:s')
      ];

      $update = $model->updateData($dataPembayaran, $this->id, $id);

      if (!$update) {
        // Jika gagal update, hapus file yang sudah diupload
        $uploadedFile = FCPATH . 'uploads/invoice/' . $filename;
        if (file_exists($uploadedFile)) {
          @unlink($uploadedFile);
        }

        return $this->response->setJSON([
          'res' => false,
          'msg' => 'Gagal menyimpan data invoice',
          'xname' => csrf_token(),
          'xhash' => csrf_hash()
        ]);
      }

      // Update nomor transaksi di tabel layanan (sync dengan invoice)
      $this->pembayaranModel->updateLayananNoTransaksi($currentData->kode_layanan, $invoiceNo);

      return $this->response->setJSON([
        'res' => 'success',
        'msg' => 'Invoice berhasil diupload dan dikirim ke pelanggan',
        'xname' => csrf_token(),
        'xhash' => csrf_hash()
      ]);
    } catch (\Exception $e) {
      log_message('error', 'UploadKirimInvoice exception: ' . $e->getMessage());
      return $this->response->setJSON([
        'res' => false,
        'msg' => 'Terjadi kesalahan: ' . $e->getMessage(),
        'xname' => csrf_token(),
        'xhash' => csrf_hash()
      ]);
    }
  }

  /**
   * Helper untuk upload file
   */
  private function doUpload($file, $folder = 'bukti')
  {
    if (!($file && $file->isValid() && !$file->hasMoved())) {
      return ['status' => false, 'msg' => 'File tidak valid'];
    }

    $allowedExt = ['pdf', 'png', 'jpg', 'jpeg', 'gif', 'bmp', 'webp'];
    $allowedMime = [
      'application/pdf',
      'image/png',
      'image/jpg',
      'image/jpeg',
      'image/gif',
      'image/bmp',
      'image/webp'
    ];

    $ext = strtolower($file->getClientExtension());
    $tmpName = $file->getTempName();

    if (!is_file($tmpName)) {
      return ['status' => false, 'msg' => 'File sementara tidak ditemukan'];
    }

    $detectedMime = null;
    if (function_exists('finfo_open')) {
      $finfo = finfo_open(FILEINFO_MIME_TYPE);
      $detectedMime = finfo_file($finfo, $tmpName);
      finfo_close($finfo);
    } else {
      $detectedMime = $file->getClientMimeType();
    }

    if (!in_array($ext, $allowedExt) || !in_array($detectedMime, $allowedMime)) {
      return ['status' => false, 'msg' => 'Format file harus gambar atau PDF'];
    }

    if ($file->getSize() > 5 * 1024 * 1024) {
      return ['status' => false, 'msg' => 'Ukuran file maksimal 5MB'];
    }

    try {
      $rand = bin2hex(random_bytes(8));
    } catch (\Exception $e) {
      $rand = bin2hex(openssl_random_pseudo_bytes(8));
    }
    $filename = time() . '_' . $rand . '.' . $ext;

    $path = FCPATH . 'uploads/' . $folder;

    if (!is_dir($path)) {
      @mkdir($path, 0755, true);
    }

    try {
      $file->move($path, $filename, true);
      $fullPath = $path . DIRECTORY_SEPARATOR . $filename;

      if (is_file($fullPath)) {
        return ['status' => true, 'filename' => $filename];
      } else {
        return ['status' => false, 'msg' => 'File gagal dipindahkan'];
      }
    } catch (\Exception $e) {
      return ['status' => false, 'msg' => 'Gagal memindahkan file: ' . $e->getMessage()];
    }
  }

  /**
   * Terima & Verifikasi pembayaran
   */
  public function terimaVerifikasi()
  {
    try {
      $encId = $this->request->getPost('id');

      if (empty($encId)) {
        return $this->response->setJSON([
          'res' => false,
          'msg' => 'ID tidak ditemukan',
          'xname' => csrf_token(),
          'xhash' => csrf_hash()
        ]);
      }

      try {
        $id = service('encrypter')->decrypt(hex2bin($encId));
      } catch (\Throwable $e) {
        return $this->response->setJSON([
          'res' => false,
          'msg' => 'ID tidak valid',
          'xname' => csrf_token(),
          'xhash' => csrf_hash()
        ]);
      }

      $model = $this->pembayaranModel;
      
      // Ambil data pembayaran untuk mendapatkan kode_layanan
      $currentData = $model->getDataById($this->id, $id);
      
      if (!$currentData) {
        return $this->response->setJSON([
          'res' => false,
          'msg' => 'Data pembayaran tidak ditemukan',
          'xname' => csrf_token(),
          'xhash' => csrf_hash()
        ]);
      }

      // Hitung ulang total_biaya berdasarkan layanan yang sudah diterima
      // Layanan yang ditolak (status_layanan=2) tidak masuk perhitungan
      $updateTotalResult = $model->updateTotalBiayaByAccepted($currentData->kode_layanan);
      
      if (!$updateTotalResult) {
        log_message('warning', 'Gagal update total_biaya untuk kode_layanan: ' . $currentData->kode_layanan);
      }

      // Update status_bayar menjadi 1 (Terverifikasi) dan kosongkan catatan_pembayaran
      $data = [
        'status_bayar' => 1,
        'catatan_pembayaran' => null  // Hapus catatan penolakan lama
      ];
      $update = $model->updateData($data, $this->id, $id);

      if ($update) {
        // Update status_lunas di t_layanan_detil untuk menandakan detail sudah lunas
        $updateLunasResult = $model->updateStatusLunasDetil($currentData->kode_layanan, $id);
        
        if (!$updateLunasResult) {
          log_message('warning', 'Gagal update status_lunas untuk kode_layanan: ' . $currentData->kode_layanan);
        }
      }

      if (!$update) {
        return $this->response->setJSON([
          'res' => false,
          'msg' => 'Gagal memverifikasi pembayaran',
          'xname' => csrf_token(),
          'xhash' => csrf_hash()
        ]);
      }

      return $this->response->setJSON([
        'res' => true,
        'msg' => 'Pembayaran berhasil diverifikasi',
        'xname' => csrf_token(),
        'xhash' => csrf_hash()
      ]);
    } catch (\Exception $e) {
      log_message('error', 'TerimaVerifikasi exception: ' . $e->getMessage());
      return $this->response->setJSON([
        'res' => false,
        'msg' => 'Terjadi kesalahan: ' . $e->getMessage(),
        'xname' => csrf_token(),
        'xhash' => csrf_hash()
      ]);
    }
  }

  /**
   * Tolak verifikasi pembayaran
   */
  public function tolakVerifikasi()
  {
    try {
      $encId = $this->request->getPost('id');
      $alasan = $this->request->getPost('alasan');

      if (empty($encId)) {
        return $this->response->setJSON([
          'res' => false,
          'msg' => 'ID tidak ditemukan',
          'xname' => csrf_token(),
          'xhash' => csrf_hash()
        ]);
      }

      try {
        $id = service('encrypter')->decrypt(hex2bin($encId));
      } catch (\Throwable $e) {
        return $this->response->setJSON([
          'res' => false,
          'msg' => 'ID tidak valid',
          'xname' => csrf_token(),
          'xhash' => csrf_hash()
        ]);
      }

      $model = $this->pembayaranModel;
      
      // Ambil data pembayaran untuk mendapatkan kode_layanan
      $currentData = $model->getDataById($this->id, $id);
      
      if (!$currentData) {
        return $this->response->setJSON([
          'res' => false,
          'msg' => 'Data pembayaran tidak ditemukan',
          'xname' => csrf_token(),
          'xhash' => csrf_hash()
        ]);
      }

      // Update status_bayar menjadi 2 (Tidak Terverifikasi)
      $data = ['status_bayar' => 2];

      // Jika ada alasan, simpan (perlu kolom catatan_pembayaran di database)
      if (!empty($alasan)) {
        $data['catatan_pembayaran'] = $alasan;
      }

      $update = $model->updateData($data, $this->id, $id);

      if ($update) {
        // Unset status_lunas di t_layanan_detil (set null) karena pembayaran ditolak
        $updateLunasResult = $model->updateStatusLunasDetil($currentData->kode_layanan, null);
        
        if (!$updateLunasResult) {
          log_message('warning', 'Gagal unset status_lunas untuk kode_layanan: ' . $currentData->kode_layanan);
        }
      }

      if (!$update) {
        return $this->response->setJSON([
          'res' => false,
          'msg' => 'Gagal menolak pembayaran',
          'xname' => csrf_token(),
          'xhash' => csrf_hash()
        ]);
      }

      return $this->response->setJSON([
        'res' => true,
        'msg' => 'Pembayaran ditolak. User dapat mengupload ulang bukti bayar.',
        'xname' => csrf_token(),
        'xhash' => csrf_hash()
      ]);
    } catch (\Exception $e) {
      log_message('error', 'TolakVerifikasi exception: ' . $e->getMessage());
      return $this->response->setJSON([
        'res' => false,
        'msg' => 'Terjadi kesalahan: ' . $e->getMessage(),
        'xname' => csrf_token(),
        'xhash' => csrf_hash()
      ]);
    }
  }

  /**
   * Upload file invoice (PDF) - Admin upload invoice untuk pelanggan
   */
  public function uploadInvoice()
  {
    try {
      $file = $this->request->getFile('file_invoice');
      $encId = $this->request->getPost('id');

      if (empty($encId)) {
        return $this->response->setJSON([
          'res' => false,
          'msg' => 'ID tidak ditemukan',
          'xname' => csrf_token(),
          'xhash' => csrf_hash()
        ]);
      }

      try {
        $id = service('encrypter')->decrypt(hex2bin($encId));
      } catch (\Throwable $e) {
        return $this->response->setJSON([
          'res' => false,
          'msg' => 'ID tidak valid',
          'xname' => csrf_token(),
          'xhash' => csrf_hash()
        ]);
      }

      if (!($file && $file->isValid() && !$file->hasMoved())) {
        return $this->response->setJSON([
          'res' => false,
          'msg' => 'File tidak valid atau belum dipilih',
          'xname' => csrf_token(),
          'xhash' => csrf_hash()
        ]);
      }

      // Upload file ke folder invoice
      $uploadResult = $this->doUpload($file, 'invoice');

      if (!$uploadResult['status']) {
        return $this->response->setJSON([
          'res' => false,
          'msg' => $uploadResult['msg'],
          'xname' => csrf_token(),
          'xhash' => csrf_hash()
        ]);
      }

      $filename = $uploadResult['filename'];

      // Update file invoice di database (tanpa nomor invoice)
      $model = $this->pembayaranModel;
      $currentData = $model->getDataById($this->id, $id);

      // Hapus file lama jika ada
      if (!empty($currentData->invoice_file)) {
        $oldFile = FCPATH . 'uploads/invoice/' . $currentData->invoice_file;
        if (file_exists($oldFile)) {
          @unlink($oldFile);
        }
      }

      // Update file invoice di database
      $dataPembayaran = [
        'invoice_file' => $filename
      ];

      $update = $model->updateData($dataPembayaran, $this->id, $id);

      if (!$update) {
        return $this->response->setJSON([
          'res' => false,
          'msg' => 'Gagal menyimpan invoice',
          'xname' => csrf_token(),
          'xhash' => csrf_hash()
        ]);
      }

      // Simpan ke session juga (sebagai penanda file baru diupload)
      session()->set('temp_invoice_' . $id, $filename);

      return $this->response->setJSON([
        'res' => 'success',
        'msg' => 'Invoice berhasil diupload. Silakan kirim ke pelanggan.',
        'xname' => csrf_token(),
        'xhash' => csrf_hash()
      ]);
    } catch (\Exception $e) {
      log_message('error', 'UploadInvoice exception: ' . $e->getMessage());
      return $this->response->setJSON([
        'res' => false,
        'msg' => 'Terjadi kesalahan: ' . $e->getMessage(),
        'xname' => csrf_token(),
        'xhash' => csrf_hash()
      ]);
    }
  }

  /**
   * Kirim invoice ke pelanggan - Update nomor invoice dan file ke database
   */
  public function kirimInvoice()
  {
    try {
      $encId = $this->request->getPost('id');
      $noInvoice = $this->request->getPost('no_invoice');

      if (empty($encId)) {
        return $this->response->setJSON([
          'res' => false,
          'msg' => 'ID tidak ditemukan',
          'xname' => csrf_token(),
          'xhash' => csrf_hash()
        ]);
      }

      try {
        $id = service('encrypter')->decrypt(hex2bin($encId));
      } catch (\Throwable $e) {
        return $this->response->setJSON([
          'res' => false,
          'msg' => 'ID tidak valid',
          'xname' => csrf_token(),
          'xhash' => csrf_hash()
        ]);
      }

      $model = $this->pembayaranModel;
      $currentData = $model->getDataById($this->id, $id);

      // Cek file dari session (file yang baru diupload)
      $sessionKey = 'temp_invoice_' . $id;
      $tempFilename = session()->get($sessionKey);

      // Jika tidak ada file di session, cek di database (file lama)
      if (empty($tempFilename) && (empty($currentData) || empty($currentData->invoice_file))) {
        return $this->response->setJSON([
          'res' => false,
          'msg' => 'Upload file invoice terlebih dahulu',
          'xname' => csrf_token(),
          'xhash' => csrf_hash()
        ]);
      }

      // Gunakan file dari session jika ada, jika tidak gunakan file lama
      $filename = !empty($tempFilename) ? $tempFilename : $currentData->invoice_file;

      // Validasi nomor invoice
      if (empty($noInvoice)) {
        return $this->response->setJSON([
          'res' => false,
          'msg' => 'Nomor invoice harus diisi',
          'xname' => csrf_token(),
          'xhash' => csrf_hash()
        ]);
      }

      // Cek apakah nomor invoice sudah ada di pembayaran lain
      if ($this->pembayaranModel->invoiceNumberExists($noInvoice, $id)) {
        return $this->response->setJSON([
          'res' => false,
          'msg' => 'Nomor invoice sudah digunakan',
          'xname' => csrf_token(),
          'xhash' => csrf_hash()
        ]);
      }

      // Ambil kode_layanan dari pembayaran
      $kode_layanan = $currentData->kode_layanan;
      if (empty($kode_layanan)) {
        return $this->response->setJSON([
          'res' => false,
          'msg' => 'Data layanan tidak ditemukan',
          'xname' => csrf_token(),
          'xhash' => csrf_hash()
        ]);
      }

      // Hapus file lama jika ada dan berbeda dengan file baru
      if (!empty($currentData->invoice_file) && !empty($tempFilename) && $currentData->invoice_file !== $tempFilename) {
        $oldFilePath = FCPATH . 'uploads/invoice/' . $currentData->invoice_file;
        if (file_exists($oldFilePath)) {
          @unlink($oldFilePath);
        }
      }

      // Update nomor invoice dan filename di tabel pembayaran
      $dataPembayaran = [
        'no_invoice' => $noInvoice,
        'invoice_file' => $filename
      ];

      $updatePembayaran = $model->updateData($dataPembayaran, $this->id, $id);

      if (!$updatePembayaran) {
        return $this->response->setJSON([
          'res' => false,
          'msg' => 'Gagal menyimpan invoice',
          'xname' => csrf_token(),
          'xhash' => csrf_hash()
        ]);
      }

      // Hapus dari session setelah berhasil save
      if (!empty($tempFilename)) {
        session()->remove($sessionKey);
      }

      $this->pembayaranModel->updateLayananNoTransaksi($kode_layanan, $noInvoice);

      return $this->response->setJSON([
        'res' => true,
        'msg' => 'Invoice berhasil dikirim ke pelanggan',
        'xname' => csrf_token(),
        'xhash' => csrf_hash()
      ]);
    } catch (\Exception $e) {
      log_message('error', 'KirimInvoice exception: ' . $e->getMessage());
      return $this->response->setJSON([
        'res' => false,
        'msg' => 'Terjadi kesalahan: ' . $e->getMessage(),
        'xname' => csrf_token(),
        'xhash' => csrf_hash()
      ]);
    }
  }

  /**
   * Normalisasi nomor telepon untuk WhatsApp
   * Hapus semua selain digit, ubah leading 0 -> 62 (Indonesia) jika perlu
   */
  private function normalize_phone_for_whatsapp($rawPhone)
  {
    if (empty($rawPhone))
      return '';

    // Keep digits only
    $digits = preg_replace('/\D+/', '', (string) $rawPhone);
    if ($digits === '')
      return '';

    // Jika mulai dengan 0 -> ganti 0 dengan 62 (Indonesia)
    if (strpos($digits, '0') === 0) {
      $digits = '62' . substr($digits, 1);
    }

    // Jika panjang terlalu pendek, bail out
    if (strlen($digits) < 8)
      return '';

    return $digits;
  }
}
