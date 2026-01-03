<?php

namespace Modules\Pelayanan\Controllers;

use App\Controllers\BaseController;
use App\Models\MyModel;

class PelayananAlat extends BaseController
{
  private $table = 't_layanan';
  private $id = 'kode_layanan';
  protected $encrypter;
  private $sessionKey = 'keranjang_alat';

  private function getStatusClass($status)
  {
    return match ($status) {
      0 => 'secondary',  // Pendaftaran
      1 => 'info',       // Review Manajer
      2 => 'danger',     // Ditolak
      3 => 'info',       // Review Admin
      4 => 'primary',    // Dalam Proses
      5 => 'success',    // Selesai
      default => 'secondary'
    };
  }

  private function getStatusText($status)
  {
    return match ($status) {
      0 => 'Pendaftaran',
      1 => 'Review Petugas',
      2 => 'Ditolak',
      3 => 'Review Petugas',
      4 => 'Dalam Proses',
      5 => 'Selesai',
      default => 'Tidak Diketahui'
    };
  }

  public function __construct()
  {
    $this->encrypter = \Config\Services::encrypter();
  }

  public function index()
  {
    $session = session();
    $user_id = $session->get('id_user');

    $modelUser = new MyModel('account_users');

    // Ambil daftar kategori untuk alat (kode_jenis = 'B') dari r_layanan_pengujian
    $db = \Config\Database::connect();
    $builder = $db->table('r_layanan_pengujian as lp');
    $builder->select('lp.kode_jenis as kode, j.nama');
    $builder->join('r_jenis j', 'j.kode = lp.kode_jenis', 'left');
    $builder->where('lp.kode_jenis', 'B');  // Filter untuk alat
    $builder->groupBy('lp.kode_jenis, j.nama');  // Group by untuk DISTINCT
    $builder->orderBy('j.nama', 'ASC');
    $categories = $builder->get()->getResult();

    $data = [
      'title' => 'Data Pelayanan Sewa Alat',
      'user' => $modelUser->getDataById('user_id', $user_id),
      'categories' => $categories ?? [],
    ];

    return view('Modules\Pelayanan\Views\v_pelayanan_alat', $data);
  }

  public function dataList()
  {
    $session = session();
    $user_id = (int) $session->get('id_user');

    $modelUser = new MyModel('account_users');
    $user = $modelUser->getDataById('user_id', $user_id);

    if (!$user || !$user_id) {
      return $this->response->setJSON(["items" => []]);
    }

    $db = \Config\Database::connect();

    // Query dengan JOIN ke t_layanan_detil untuk filter kode_jenis = 'B' (alat)
    // Gunakan GROUP BY untuk menghindari duplikasi row jika ada multiple detail items
    $builder = $db->table($this->table . ' as t');
    $builder->select('t.kode_layanan, t.user_id, t.lnAccEmail, t.lnNoTransaksi, t.tanggal_checkout, t.status_layanan, t.kuisioner, t.tgl_pelaksanaan, t.lhu_id');
    $builder->join('t_layanan_detil d', 'd.kode_layanan = t.kode_layanan', 'inner');
    $builder->where('t.user_id', $user_id);
    $builder->where('d.kode_jenis', 'B');  // Filter hanya alat (kode_jenis = 'B')
    $builder->groupBy('t.kode_layanan, t.user_id, t.lnAccEmail, t.lnNoTransaksi, t.tanggal_checkout, t.status_layanan, t.kuisioner, t.tgl_pelaksanaan, t.lhu_id');
    $builder->orderBy('t.tanggal_checkout', 'DESC');

    $list = $builder->get()->getResult();

    if (empty($list)) {
      return $this->response->setJSON(["items" => []]);
    }

    // Siapkan map pembayaran terakhir per kode_layanan
    $lnKodes = array_map(fn($r) => (int) $r->kode_layanan, $list);

    $payRows = $db->table('t_pembayaran')
      ->select('kode_layanan, status_bayar, no_invoice, MAX(kode_bayar) AS lastKode')
      ->whereIn('kode_layanan', $lnKodes)
      ->groupBy('kode_layanan, status_bayar, no_invoice')
      ->orderBy('lastKode', 'DESC')
      ->get()->getResult();

    $payMap = [];
    foreach ($payRows as $p) {
      $ln = (int) $p->kode_layanan;
      if (!isset($payMap[$ln])) {
        $payMap[$ln] = [
          'status' => (int) $p->status_bayar,
          'inv' => $p->no_invoice ?? null,
        ];
      }
    }

    $data = [];
    foreach ($list as $row) {
      $id = bin2hex($this->encrypter->encrypt($row->kode_layanan));
      $response = [];

      // Kolom 1: No. Transaksi + Tanggal
      $noTransaksi = (isset($row->lnNoTransaksi) && trim((string) $row->lnNoTransaksi) !== '')
        ? $row->lnNoTransaksi
        : 'Belum tersedia';

      $tanggal = !empty($row->tanggal_checkout) ? date('d-m-Y', strtotime($row->tanggal_checkout)) : '-';
      $response[] = '<div>' . esc($noTransaksi) . '<br><small>' . esc($tanggal) . '</small></div>';

      // Kolom 2: Status & Detail Pesanan
      $statusText = $this->getStatusText((int) ($row->status_layanan ?? 0));
      $response[] = '<div class="d-flex gap-2 align-items-center">' .
        '<button class="btn btn-sm btn-outline-primary" onclick="showTrackingModal(\'' . $id . '\', \'' . $row->kode_layanan . '\', ' . (int) ($row->status_layanan ?? 0) . ')">' .
        '<i class="bi bi-activity"></i> ' . $statusText . '</button>' .
        '</div>';

      // Kolom 3: Status Pembayaran
      $lnKodeInt = (int) $row->kode_layanan;
      $bayarStatusVal = isset($payMap[$lnKodeInt]) ? $payMap[$lnKodeInt]['status'] : 0;

      if ($bayarStatusVal === 3) {
        $response[] = '<span class="badge bg-success">Lunas</span>';
      } elseif ($bayarStatusVal === 1) {
        $response[] = '<span class="badge bg-warning">Menunggu Verifikasi</span>';
      } elseif ($bayarStatusVal === 2) {
        $response[] = '<span class="badge bg-danger">Ditolak</span>';
      } else {
        $response[] = '<button class="btn btn-sm btn-info" onclick="lokasiPembayaran(' . $lnKodeInt . ')"><i class="bi bi-credit-card"></i> Belum Bayar</button>';
      }

      // Kolom 4: Tanggal Pelaksanaan
      $tglPelaksanaan = '-';
      if (!empty($row->tgl_pelaksanaan)) {
        $tglPelaksanaan = date('d-m-Y', strtotime($row->tgl_pelaksanaan));
      }
      $response[] = '<div>' . esc($tglPelaksanaan) . '</div>';

      $data[] = $response;
    }

    return $this->response->setJSON(["items" => $data]);
  }

  public function getTrackingData($id)
  {
    try {
      $realId = $this->encrypter->decrypt(hex2bin($id));
      $model = new MyModel($this->table);
      $data = $model->getDataById($this->id, $realId);

      if (!$data) {
        return $this->response->setJSON([
          'success' => false,
          'message' => 'Data tidak ditemukan'
        ]);
      }

      // Get detail items dengan grouped data
      $db = \Config\Database::connect();
      $builder = $db->table('t_layanan_detil as d');
      $builder->select("
                d.uji_kode,
                d.kode_layanan,
                d.nama_layanan,
                d.kode_jenis,
                GROUP_CONCAT(DISTINCT d.catatan_pelanggan SEPARATOR ' | ') as catatan_pelanggan,
                SUM(d.jumlah) as jumlah,
                SUM(d.biaya) as biaya,
                MAX(d.status_layanan) as status_layanan
            ");
      $builder->where('d.kode_layanan', $realId);
      $builder->groupBy('d.uji_kode, d.kode_layanan, d.nama_layanan, d.kode_jenis');
      $details = $builder->get()->getResult();

      return $this->response->setJSON([
        'success' => true,
        'data' => [
          'kode' => $data->kode_layanan,
          'statusText' => $this->getStatusText((int) $data->status_layanan),
          'tanggal' => date('d-m-Y', strtotime($data->tanggal_checkout)),
          'noTransaksi' => $data->no_invoice ?? 'Belum tersedia',
          'tglPelaksanaan' => !empty($data->tgl_pelaksanaan) ? date('d-m-Y', strtotime($data->tgl_pelaksanaan)) : '-',
          'details' => array_map(function ($detailItem) {
            $statusLayanan = isset($detailItem->status_layanan) ? (int) $detailItem->status_layanan : null;

            if ($statusLayanan === 0) {
              $statusHtml = '<span class="badge bg-warning">Pending</span>';
            } elseif ($statusLayanan === 1) {
              $statusHtml = '<span class="badge bg-success">Diterima</span>';
            } elseif ($statusLayanan === 2) {
              $statusHtml = '<span class="badge bg-danger">Ditolak</span>';
            } else {
              $statusHtml = '<span class="badge bg-secondary">Belum Diproses</span>';
            }

            return [
              'nama_alat' => $detailItem->nama_layanan ?? '-',
              'biaya' => number_format((float) ($detailItem->biaya ?? 0), 0, ',', '.'),
              'jumlah' => (int) ($detailItem->jumlah ?? 0),
              'keterangan' => $detailItem->catatan_pelanggan ?? '-',
              'status' => $statusHtml
            ];
          }, $details)
        ]
      ]);
    } catch (\Exception $e) {
      return $this->response->setJSON([
        'success' => false,
        'message' => 'Terjadi kesalahan saat memuat data'
      ]);
    }
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

    $db = \Config\Database::connect();
    $builder = $db->table('t_layanan_detil as d');

    $builder->select("
            d.uji_kode,
            d.kode_layanan,
            d.nama_layanan,
            d.kode_jenis,
            GROUP_CONCAT(DISTINCT d.catatan_pelanggan SEPARATOR ' | ') AS detKet,
            SUM(d.jumlah) AS jumlah,
            SUM(d.biaya) AS detBiaya,
            MAX(d.status_layanan) AS detStatusGroup
        ");
    $builder->where('d.kode_layanan', $kode);
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
      $response[] = '<div 
                    style="display:block; max-width:240px; min-width:160px; width:100%;
                        max-height:120px; min-height:48px; overflow-y:auto; overflow-x:hidden;
                        padding:4px 6px; border:1px solid #ddd; border-radius:4px; background:#f9f9f9;
                        white-space:pre-wrap; word-break:break-word; font-size:0.9rem;">' .
        htmlspecialchars($row->detKet ?? '', ENT_QUOTES, 'UTF-8') .
        '</div>';

      $statusGroup = isset($row->detStatusGroup) ? (int) $row->detStatusGroup : null;

      if ($statusGroup === 0) {
        $statusHtml = '<span class="badge bg-warning">Pending</span>';
      } elseif ($statusGroup === 1) {
        $statusHtml = '<span class="badge bg-success">Diterima</span>';
      } elseif ($statusGroup === 2) {
        $statusHtml = '<span class="badge bg-danger">Ditolak</span>';
      } else {
        $statusHtml = '<span class="badge bg-secondary">Belum Diproses</span>';
      }

      $response[] = $statusHtml;

      $data[] = $response;
    }

    return $this->response->setJSON(['items' => $data]);
  }

  public function checkVerified()
  {
    $session = session();
    $user_id = $session->get('id_user');

    $modelUser = new MyModel('account_users');
    $user = $modelUser->getDataById('user_id', $user_id);

    if (!$user) {
      return $this->response->setJSON(['verified' => false, 'msg' => 'User tidak ditemukan.']);
    }

    if ((int) $user->verifikasi === 1) {
      return $this->response->setJSON(['verified' => true, 'msg' => 'Akun sudah terverifikasi.']);
    } else {
      return $this->response->setJSON([
        'verified' => false,
        'msg' => 'Akun Anda belum terverifikasi. Harap hubungi admin untuk verifikasi.',
        'userVerified' => (int) $user->verifikasi
      ]);
    }
  }
}
