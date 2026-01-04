<?php

namespace Modules\FormulirAdmin\Controllers;

use App\Controllers\BaseController;
use App\Models\MyModel;

class FormulirAdminRapatJas extends BaseController
{
  private $table = 't_layanan';
  private $id = 'kode_layanan';
  protected $encrypter;
  private $sessionKey = 'keranjang_formadmin_rapatjas';

  public function __construct()
  {
    $this->encrypter = \Config\Services::encrypter();
  }

  /**
   * Index - kirim juga daftar user untuk pemilih pelanggan
   * dan daftar kategori (kode/nama) agar view bisa render opsi kategori awal
   * KHUSUS RAPAT JAS: Filter hanya kode_jenis = 'D'
   */
  public function index()
  {
    $session = session();
    $user_id = $session->get('id_user');

    $modelUser = new MyModel('account_users');

    // Ambil user list untuk dropdown pemilih pelanggan
    $users = [];
    if (method_exists($modelUser, 'getAllData')) {
      $users = $modelUser->getAllData();
    } elseif (method_exists($modelUser, 'getAllDataWithOrder')) {
      $users = $modelUser->getAllDataWithOrder(['user_name' => 'ASC']);
    } else {
      // fallback ke query builder jika MyModel tidak punya helper
      $db = \Config\Database::connect();
      $users = $db->table('account_users')->select('user_id, user_name, user_email, user_identity, user_instansi')->orderBy('user_name', 'ASC')->get()->getResult();
    }

    // Ambil daftar kategori yang benar-benar ada di data pengujian
    // KHUSUS RAPAT JAS: Filter hanya yang kode_jenis dimulai dengan 'D'
    $db = \Config\Database::connect();
    $builder = $db->table('r_layanan_pengujian as lp');

    $builder->select(' DISTINCT TRIM(LEFT(lp.kode_jenis, 2)) as kode, j.nama ');
    $builder->join('r_jenis j', 'j.kode = TRIM(LEFT(lp.kode_jenis, 2))', 'left');
    $builder->where('lp.kode_jenis IS NOT NULL');
    $builder->where('lp.kode_jenis !=', '');
    // FILTER KHUSUS: Hanya kode_jenis yang dimulai dengan 'D'
    $builder->like('lp.kode_jenis', 'D', 'after');
    $builder->orderBy('j.nama', 'ASC');

    $categoriesRaw = $builder->get()->getResult();

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
      'title' => 'Data Formulir Admin - Rapat JAS',
      'user' => (new MyModel('account_users'))->getDataById('user_id', $user_id),
      'users' => $users,
      'categories' => $categories
    ];

    return view('Modules\FormulirAdmin\Views\v_formulirAdminRapatJas', $data);
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
   * KHUSUS RAPAT JAS: Hanya tampilkan data dengan kode_jenis dimulai 'D' di t_layanan_detil
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
    if (method_exists($model, 'getAllDataWithOrder')) {
      $list = $model->getAllDataWithOrder(['tanggal_checkout' => 'DESC']);
    } else {
      $list = $model->getAllDataByWhere([], ['tanggal_checkout' => 'DESC']);
    }

    $userModel = new MyModel('account_users');
    $layananDet = new MyModel('t_layanan_detil');
    $db = \Config\Database::connect();

    foreach ($list as $row) {
      $lnStatusInt = (int) $row->status_layanan;

      // Filter by status
      if (is_array($filterStatuses)) {
        if (!in_array($lnStatusInt, $filterStatuses, true))
          continue;
      } else {
        // Perilaku default lama: lewati status tertentu (0,2)
        if (in_array($lnStatusInt, [0, 2], true))
          continue;
      }

      // FILTER KHUSUS RAPAT JAS: Hanya tampilkan jika ada detil dengan kode_jenis dimulai 'D'
      try {
        $dets = $layananDet->getAllDataById(['kode_layanan' => $row->kode_layanan]);
        $hasRapatJas = false;
        foreach ($dets as $dd) {
          $kodeJenis = isset($dd->kode_jenis) ? trim($dd->kode_jenis) : '';
          // Cek jika kode_jenis dimulai dengan 'D'
          if ($kodeJenis !== '' && strpos($kodeJenis, 'D') === 0) {
            $hasRapatJas = true;
            break;
          }
        }
        if (!$hasRapatJas) {
          // skip this row if none of its detil have kode_jenis starting with 'D'
          continue;
        }
      } catch (\Throwable $ex) {
        // jika error saat cek, skip untuk keamanan
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
            continue;
          }
        } catch (\Throwable $ex) {
          continue;
        }
      }

      // pakai kode_layanan (yang sudah ada di $row) sebagai sumber id
      $id = bin2hex($this->encrypter->encrypt($row->kode_layanan));
      $response = [];

      // Ambil detail item layanan
      $detil = $layananDet->getAllDataById(['kode_layanan' => $row->kode_layanan]);
      $items = [];
      foreach ($detil as $d) {
        $items[] = $d->nama_layanan ?? $d->kode_jenis;
      }

      // ambil data user
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
        $qb = $db->table($this->table);
        $qb->select('user_id')
          ->where('no_invoice', $row->no_invoice)
          ->where('user_id IS NOT NULL', null, false);
        $res = $qb->get()->getResult();
        if (!empty($res)) {
          $foundUserId = (int) $res[0]->user_id;
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

      // Gunakan no_invoicehanya untuk ditampilkan
      $invoiceNo = !empty($row->no_invoice) ? $row->no_invoice : 'Belum tersedia';

      $pemesanNama = !empty($personName) ? $personName : '-';
      $tipe = !empty($userIdentity) ? $userIdentity : '-';
      $tanggal = !empty($row->tanggal_checkout) ? date('d-m-Y H:i', strtotime($row->tanggal_checkout)) : '-';

      $combined = '
                <div style="line-height:1.3;">
                    <span style="font-size:1rem; font-weight:600;">' . esc($pemesanNama) . '</span><br>
                    <span style="font-size:0.9rem; color:#555;">' . esc($tanggal) . ' | ' . esc($tipe) . '</span>
                </div>';

      $response[] = $combined;
      $response[] = esc($invoiceNo);
      $response[] = $this->formatStatus($row->status_layanan);

      $lihatDetailBtn = '<button type="button" class="btn btn-sm btn-info" 
                                title="Lihat Detail Item Layanan" 
                                onclick="loadDetail(\'' . $id . '\')">
                                <i class="bi bi-eye"></i> Lihat Layanan</button>';
      $response[] = $lihatDetailBtn;

      $response[] = $this->aksi($id, $row->status_layanan);

      $data[] = $response;
    }

    return $this->response->setJSON([
      'items' => $data,
      'total' => count($data)
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

    $encLnId = bin2hex($this->encrypter->encrypt($kode));

    $db = \Config\Database::connect();
    $builder = $db->table('t_layanan_detil as d');

    $builder->select("
            d.uji_kode,
            d.kode_layanan,
            d.nama_layanan,
            d.kode_jenis,
            GROUP_CONCAT(DISTINCT d.catatan_manajer SEPARATOR ' | ') AS detKetLn,
            SUM(d.jumlah) AS jumlah,
            SUM(d.biaya) AS detBiaya,
            MAX(d.status_layanan) AS detStatusGroup,
            GROUP_CONCAT(DISTINCT acc.nama SEPARATOR ' | ') AS accUsernames
        ");

    $builder->join('account acc', 'acc.user_id = d.terima_layanan_by', 'left');
    $builder->where('d.kode_layanan', $kode);
    // FILTER KHUSUS: Hanya detil dengan kode_jenis dimulai 'D'
    $builder->like('d.kode_jenis', 'D', 'after');
    $builder->groupBy('d.uji_kode, d.kode_layanan, d.nama_layanan, d.kode_jenis');

    $rows = $builder->get()->getResult();

    $data = [];
    $no = 1;

    foreach ($rows as $row) {
      $response = [];
      $response[] = $no++;
      $response[] = $row->nama_layanan ?? '-';
      $response[] = isset($row->detBiaya) ? number_format($row->detBiaya, 0, ',', '.') : '-';
      $response[] = isset($row->jumlah) ? (int) $row->jumlah : 0;

      // detKet (keterangan item)
      $response[] = '<div 
                    style="display:block; max-width:240px; min-width:160px; width:100%;
                        max-height:120px; min-height:48px; overflow-y:auto; overflow-x:hidden;
                        padding:4px 6px; border:1px solid #ddd; border-radius:4px; background:#f9f9f9;
                        white-space:pre-wrap; word-break:break-word; font-size:0.9rem;">'
        . htmlspecialchars($row->detKet ?? '', ENT_QUOTES, 'UTF-8') .
        '</div>';

      // status hasil grouping
      $statusGroup = isset($row->detStatusGroup) ? (int) $row->detStatusGroup : null;
      if ($statusGroup === 1) {
        $statusHtml = '<span class="badge bg-success">Diterima</span>';
      } elseif ($statusGroup === 2) {
        $statusHtml = '<span class="badge bg-danger">Ditolak</span>';
      } else {
        $statusHtml = '<span class="badge bg-secondary">Pending</span>';
      }
      $response[] = $statusHtml;

      // detKetLn (keterangan level layanan)
      $response[] = '<div 
            style="display:block; max-width:240px; min-width:160px; width:100%;
            max-height:120px; min-height:48px; overflow-y:auto; overflow-x:hidden;
            padding:4px 6px; border:1px solid #ddd; border-radius:4px; background:#f9f9f9;
            white-space:pre-wrap; word-break:break-word; font-size:0.9rem;">'
        . htmlspecialchars($row->detKetLn ?? '', ENT_QUOTES, 'UTF-8') .
        '</div>';

      // Username yang melakukan accept layanan
      $accUsernames = trim((string) ($row->accUsernames ?? ''));
      $response[] = $accUsernames !== '' ? htmlspecialchars($accUsernames, ENT_QUOTES, 'UTF-8') : '-';
      $data[] = $response;
    }

    return $this->response->setJSON(['items' => $data]);
  }

  private function aksi($id, $status)
  {
    $btn = '<div id="' . $id . '" class="float-end d-flex align-items-center" style="gap:6px;">';

    // tombol Approve
    if ($status == 3) {
      $btn .= '<span class="text-success btn-action" title="Setujui" onclick="confirmApprove(event)" style="display:inline-flex;align-items:center;justify-content:center;width:25px;height:25px;border-radius:6px;">'
        . '<i class="bi bi-check-circle"></i></span>';
      $btn .= '<span class="text-muted" style="margin-left:4px;margin-right:4px;">|</span>';
    }

    // tombol WhatsApp
    try {
      $kode_layanan = null;
      try {
        $kode_layanan = $this->encrypter->decrypt(hex2bin($id));
      } catch (\Exception $e) {
        $kode_layanan = null;
      }

      if ($kode_layanan !== null) {
        $db = \Config\Database::connect();
        $row = $db->table($this->table)
          ->select('user_id, user_email')
          ->where($this->id, $kode_layanan)
          ->get()
          ->getRow();

        $phoneRaw = '';
        $userObj = null;

        if ($row) {
          $modelUser = new MyModel('account_users');

          if (!empty($row->user_id)) {
            $userObj = $modelUser->getDataById('user_id', $row->user_id);
          }

          if (!$userObj && !empty($row->user_email)) {
            $users = $modelUser->getAllDataById(['user_email' => $row->user_email]);
            if (!empty($users))
              $userObj = is_array($users) ? $users[0] : $users;
          }

          if ($userObj) {
            if (isset($userObj->user_phone) && !empty($userObj->user_phone))
              $phoneRaw = $userObj->user_phone;
            elseif (isset($userObj->user_telpon) && !empty($userObj->user_telpon))
              $phoneRaw = $userObj->user_telpon;
            elseif (isset($userObj->user_telp) && !empty($userObj->user_telp))
              $phoneRaw = $userObj->user_telp;
            elseif (isset($userObj->phone) && !empty($userObj->phone))
              $phoneRaw = $userObj->phone;
          }
        }

        if (!empty($phoneRaw)) {
          $waDigits = $this->normalize_phone_for_whatsapp($phoneRaw);
          if ($waDigits !== '') {
            $displayName = $userObj->user_name ?? null;
            $message = $displayName ? "Assalamualaikum Kak " . $displayName . ", saya ingin bertanya terkait layanan Rapat JAS bapak/ibu" : "Halo, saya ingin bertanya tentang layanan Rapat JAS.";
            $msgEncoded = rawurlencode($message);
            $waUrl = "https://wa.me/" . $waDigits . "?text=" . $msgEncoded;

            $btn .= '<span class="text-success btn-action" title="Chat via WhatsApp" '
              . 'style="display:inline-flex;align-items:center;justify-content:center;width:25px;height:25px;border:1px solid #28a745;border-radius:6px;cursor:pointer;background:#ffffff;" '
              . 'onclick="window.open(\'' . esc($waUrl) . '\', \'_blank\', \'noopener\')">'
              . '<i class="bi bi-whatsapp"></i>'
              . '</span>';
            $btn .= '<span class="text-muted" style="margin-left:4px;margin-right:2px;">|</span>';
          }
        }
      }
    } catch (\Throwable $e) {
      // log error if needed
    }

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
        return '<span class="badge bg-info">Belum direview</span>';
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
        return '<span class="badge bg-dark">Pengujian selesai</span>';
      default:
        return '<span class="badge bg-dark">Unknown</span>';
    }
  }

  /**
   * Approve endpoint (dipanggil via AJAX POST)
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
      $kode_layanan = $this->encrypter->decrypt(hex2bin($encId));

      if (empty($kode_layanan)) {
        $response['msg'] = 'ID tidak valid.';
        return $this->response->setJSON($response);
      }

      $model = new MyModel($this->table);

      $update = [
        'status_layanan' => 4,
      ];

      $res = $model->updateData($update, $this->id, $kode_layanan);

      if ($res) {
        $response['res'] = true;
        $response['msg'] = 'Data berhasil diapprove.';
        $response['newStatus'] = $update['status_layanan'];
      } else {
        $response['msg'] = 'Gagal update database (tidak ada perubahan atau error).';
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

    $response['xname'] = csrf_token();
    $response['xhash'] = csrf_hash();

    return $this->response->setJSON($response);
  }

  /**
   * Normalisasi nomor telepon untuk WhatsApp
   */
  public function normalize_phone_for_whatsapp($rawPhone)
  {
    if (empty($rawPhone))
      return '';
    $digits = preg_replace('/\D+/', '', (string) $rawPhone);
    if ($digits === '')
      return '';
    if (strpos($digits, '0') === 0) {
      $digits = '62' . substr($digits, 1);
    }
    if (strlen($digits) < 8)
      return '';
    return $digits;
  }

  /**
   * Buat tombol HTML untuk WhatsApp
   */
  public function whatsapp_button_html($phoneDigits, $name = null)
  {
    if (empty($phoneDigits))
      return '';
    $text = $name ? "Halo%20" . rawurlencode($name) . "%2C%20saya%20ingin%20bertanya%20tentang%20layanan%20Rapat%20JAS." : "Halo%2C%20saya%20ingin%20bertanya%20tentang%20layanan%20Rapat%20JAS.";
    $url = "https://wa.me/" . $phoneDigits . "?text=" . $text;
    return '<a href="' . esc($url) . '" target="_blank" rel="noopener noreferrer" class="btn btn-sm btn-success ms-1" title="Chat via WhatsApp">'
      . '<i class="bi bi-whatsapp"></i>'
      . '</a>';
  }

  // ==========================================
  // KERANJANG FUNCTIONS
  // ==========================================
  // Untuk Rapat JAS, akan menggunakan KeranjangAdmin dengan filter kode_jenis = 'D'
  // atau bisa menggunakan KeranjangAdminRapatJas jika dibuat terpisah
  // ==========================================
}
