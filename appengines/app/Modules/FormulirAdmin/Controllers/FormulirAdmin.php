<?php

namespace Modules\FormulirAdmin\Controllers;

use App\Controllers\BaseController;
use App\Models\MyModel;

class FormulirAdmin extends BaseController
{
    private $table = 'simlab_t_layanan';
    private $id = 'lnKode';
    protected $encrypter;
    private $sessionKey = 'keranjang_formadmin';

    public function __construct()
    {
        $this->encrypter = \Config\Services::encrypter();
    }

    /**
     * Index - kirim juga daftar user untuk pemilih pelanggan
     * dan daftar kategori (jenKode/jenNama) agar view bisa render opsi kategori awal
     */
    public function index()
    {
        $session = session();
        $user_id = $session->get('id_user');

        $modelUser = new MyModel('simlab_account_users');

        // Ambil user list untuk dropdown pemilih pelanggan
        // Coba beberapa method model yang mungkin tersedia
        $users = [];
        if (method_exists($modelUser, 'getAllData')) {
            $users = $modelUser->getAllData();
        } elseif (method_exists($modelUser, 'getAllDataWithOrder')) {
            $users = $modelUser->getAllDataWithOrder(['user_name' => 'ASC']);
        } else {
            // fallback ke query builder jika MyModel tidak punya helper
            $db = \Config\Database::connect();
            $users = $db->table('simlab_account_users')->select('user_id, user_name, user_email, user_identity, user_instansi')->orderBy('user_name', 'ASC')->get()->getResult();
        }

        // Ambil daftar kategori yang benar-benar ada di data pengujian
        $db = \Config\Database::connect();
        $builder = $db->table('r_layanan_pengujian as lp');

        $builder->select(' DISTINCT TRIM(LEFT(lp.kode_jenis, 2)) as jenKode, j.jenNama ');
        $builder->join('simlab_r_jenis j', 'j.jenKode = TRIM(LEFT(lp.kode_jenis, 2))', 'left');
        $builder->where('lp.kode_jenis IS NOT NULL');
        $builder->where('lp.kode_jenis !=', '');
        $builder->orderBy('j.jenNama', 'ASC');

        $categoriesRaw = $builder->get()->getResult();

        // NORMALISASI kategori
        $categories = [];
        if (!empty($categoriesRaw)) {
            foreach ($categoriesRaw as $c) {
                $kode = isset($c->jenKode) ? trim((string) $c->jenKode) : '';
                $nama = (isset($c->jenNama) && trim((string) $c->jenNama) !== '') ? trim((string) $c->jenNama) : $kode;
                if ($kode !== '') {
                    $categories[] = (object) ['jenKode' => $kode, 'jenNama' => $nama];
                }
            }
        }

        $data = [
            'title' => 'Data Formulir Admin',
            'user' => (new MyModel('simlab_account_users'))->getDataById('user_id', $user_id),
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
     * - menerima parameter kategoriLayanan (status-array) & jenKode (kategori layanan)
     * - jika jenKode diberikan, hanya sertakan baris layanan yang memiliki detil dengan jenKode yang cocok
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

        // Ambil parameter jenKode (kategori layanan yang ingin difilter)
        $jenKodeParam = trim((string) ($this->request->getGet('jenKode') ?? ''));

        // Ambil semua data, urutkan tanggal DESC (terbaru di atas)
        // Tetap gunakan getAllDataWithOrder jika model mendukungnya
        if (method_exists($model, 'getAllDataWithOrder')) {
            $list = $model->getAllDataWithOrder(['lnTgl' => 'DESC']);
        } else {
            $list = $model->getAllDataByWhere([], ['lnTgl' => 'DESC']);
        }

        $userModel = new MyModel('simlab_account_users');
        $layananDet = new MyModel('t_layanan_detil');
        $db = \Config\Database::connect();

        // Siapkan map pembayaran terakhir per lnKode (sama seperti di Pelayanan)
        $lnKodes = array_map(fn($r) => (int) $r->lnKode, $list);

        $payRows = $db->table('t_pembayaran')
            ->select('bayarLnKode, bayarStatus, bayarInvoiceNo, MAX(bayarKode) AS lastKode')
            ->whereIn('bayarLnKode', $lnKodes)
            ->groupBy('bayarLnKode, bayarStatus, bayarInvoiceNo')
            ->orderBy('lastKode', 'DESC')
            ->get()->getResult();

        $payMap = [];
        foreach ($payRows as $p) {
            $ln = (int) $p->bayarLnKode;
            if (!isset($payMap[$ln])) {
                $payMap[$ln] = [
                    'status' => (int) $p->bayarStatus,
                    'inv' => $p->bayarInvoiceNo ?? null,
                ];
            }
        }

        foreach ($list as $row) {
            $lnStatusInt = (int) $row->lnStatus;

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
                    $dets = $layananDet->getAllDataById(['kode_layanan' => $row->lnKode]);
                    $hasMatch = false;
                    foreach ($dets as $dd) {
                        $detJen = isset($dd->kode_jenis) ? trim(substr($dd->kode_jenis, 0, 2)) : '';
                        if ($detJen !== '' && $detJen === $jenKodeParam) {
                            $hasMatch = true;
                            break;
                        }
                    }
                    if (!$hasMatch) {
                        // skip this row if none of its detil match jenKode filter
                        continue;
                    }
                } catch (\Throwable $ex) {
                    // jika error saat cek, lanjutkan tanpa mem-filter (defensive)
                }
            }

            // pakai lnKode (yang sudah ada di $row) sebagai sumber id
            $id = bin2hex($this->encrypter->encrypt($row->lnKode));
            $response = [];

            // Ambil detail item layanan (tetap ada, seperti semula)
            $detil = $layananDet->getAllDataById(['kode_layanan' => $row->lnKode]);
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

            // 2) Jika belum ada, cek berdasarkan email (lnAccEmail)
            if (!$u && !empty($row->lnAccEmail)) {
                $users = $userModel->getAllDataById(['user_email' => $row->lnAccEmail]);
                if (!empty($users))
                    $u = is_array($users) ? $users[0] : $users;
            }

            // 3) Jika masih belum ketemu, cari user_id dari invoice (lnNoTransaksi)
            if (!$u && !empty($row->lnNoTransaksi)) {
                $qb = $db->table($this->table);
                $qb->select('user_id')
                    ->where('lnNoTransaksi', $row->lnNoTransaksi)
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
                $personName = $row->lnAccEmail ?? '-';
            }



            // Gunakan lnNoTransaksi hanya untuk ditampilkan, bukan dasar sorting
            $invoiceNo = !empty($row->lnNoTransaksi) ? $row->lnNoTransaksi : 'Belum tersedia';

            $pemesanNama = !empty($personName) ? $personName : '-';
            $tipe = !empty($userIdentity) ? $userIdentity : '-';
            $tanggal = !empty($row->lnTgl) ? date('d-m-Y H:i', strtotime($row->lnTgl)) : '-';

            $combined = '
                <div style="line-height:1.3;">
                    <span style="font-size:1rem; font-weight:600;">' . esc($pemesanNama) . '</span><br>
                    <span style="font-size:0.9rem; color:#555;">' . esc($tanggal) . ' | ' . esc($tipe) . '</span>
                </div>';

            $response[] = $combined;
            $response[] = esc($invoiceNo);
            $response[] = $this->formatStatus($row->lnStatus);

            // Status Pembayaran (sama seperti di Pelayanan)
            $lnKodeInt = (int) $row->lnKode;
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

            $lihatDetailBtn = '<button type="button" class="btn btn-sm btn-info" 
                                title="Lihat Detail Item Layanan" 
                                onclick="loadDetail(\'' . $id . '\')">
                                <i class="bi bi-eye"></i> Lihat </button>';
            $response[] = $lihatDetailBtn;

            $response[] = $this->aksi($id, $row->lnStatus);

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

        // Encrypted hex parent (disimpan jika perlu dipakai di tempat lain)
        $encLnId = bin2hex($this->encrypter->encrypt($kode));

        $db = \Config\Database::connect();
        $builder = $db->table('t_layanan_detil as d');

        $builder->select("
        d.uji_kode,
        d.kode_layanan,
        d.nama_layanan,
        d.kode_jenis,
        GROUP_CONCAT(DISTINCT d.catatan_pelanggan SEPARATOR ' | ') AS detKet,
        GROUP_CONCAT(DISTINCT d.catatan_manajer SEPARATOR ' | ') AS detKetLn,
        SUM(d.jumlah) AS jumlah,
        SUM(d.biaya) AS detBiaya,
        MAX(d.status_layanan) AS detStatusGroup,
        GROUP_CONCAT(DISTINCT acc.username SEPARATOR ' | ') AS accUsernames
    ");

        // join untuk ambil username dari terima_layanan_by
        $builder->join('simlab_account acc', 'acc.user_id = d.terima_layanan_by', 'left');

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

            // detKet (keterangan item)
            $response[] = '<div 
                style="display:block; max-width:240px; min-width:160px; width:100%;
                    max-height:120px; min-height:48px; overflow-y:auto; overflow-x:hidden;
                    padding:4px 6px; border:1px solid #ddd; border-radius:4px; background:#f9f9f9;
                    white-space:pre-wrap; word-break:break-word; font-size:0.9rem;">'
                . htmlspecialchars($row->detKet ?? '', ENT_QUOTES, 'UTF-8') .
                '</div>';

            // status hasil grouping (1 = diterima, 2 = ditolak, lainnya = pending)
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


            // Username yang melakukan accept layanan (bisa >1 username bila multi-row dalam satu grup)
            $accUsernames = trim((string) ($row->accUsernames ?? ''));
            $response[] = $accUsernames !== '' ? htmlspecialchars($accUsernames, ENT_QUOTES, 'UTF-8') : '-';
            $data[] = $response;
        }

        return $this->response->setJSON(['items' => $data]);
    }



    private function aksi($id, $status)
    {
        $btn = '<div id="' . $id . '" class="float-end d-flex align-items-center" style="gap:6px;">';

        // tombol Approve (sesuai kondisi Anda) â€” tampil seperti sekarang
        if ($status == 3) {
            $btn .= '<span class="text-success btn-action" title="Setujui" onclick="confirmApprove(event)" style="display:inline-flex;align-items:center;justify-content:center;width:25px;height:25px;border-radius:6px;">'
                . '<i class="bi bi-check-circle"></i></span>';
            // divider kecil (opsional)
            $btn .= '<span class="text-muted" style="margin-left:4px;margin-right:4px;">|</span>';
        }

        // --- tambahkan tombol WhatsApp di samping approve jika nomor tersedia (berbentuk kotak) ---
        try {
            // decrypt id (lnKode)
            $lnKode = null;
            try {
                $lnKode = $this->encrypter->decrypt(hex2bin($id));
            } catch (\Exception $e) {
                $lnKode = null;
            }

            if ($lnKode !== null) {
                // ambil header layanan untuk menemukan user_id / lnAccEmail
                $db = \Config\Database::connect();
                $row = $db->table($this->table)
                    ->select('user_id, lnAccEmail')
                    ->where($this->id, $lnKode)
                    ->get()
                    ->getRow();

                $phoneRaw = '';
                $userObj = null;

                if ($row) {
                    $modelUser = new MyModel('simlab_account_users');

                    // 1) coba dari user_id FK
                    if (!empty($row->user_id)) {
                        $userObj = $modelUser->getDataById('user_id', $row->user_id);
                    }

                    // 2) jika belum ada, coba cari berdasarkan lnAccEmail
                    if (!$userObj && !empty($row->lnAccEmail)) {
                        $users = $modelUser->getAllDataById(['user_email' => $row->lnAccEmail]);
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
                        $message = $displayName ? "Assalamualaikum Kak " . $displayName . ", saya ingin bertanya terkait layanan bapak yang beberapa ditolak, apakah kakak ingin melanjutkan layanan tersebut" : "Halo, saya ingin bertanya tentang layanan.";
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
            $lnKode = $this->encrypter->decrypt(hex2bin($encId));

            // simple validation
            if (empty($lnKode)) {
                $response['msg'] = 'ID tidak valid.';
                return $this->response->setJSON($response);
            }

            $model = new MyModel($this->table);

            // Anda bisa men-set status apa yg "approve" maksudnya.
            // Contoh ini set lnStatus => 1 (In Review Manajer).
            // Ubah menjadi status lain jika bisnis logic menghendaki.
            $update = [
                'lnStatus' => 4,
                // 'lnTglUpdate' => date('Y-m-d H:i:s') .
            ];

            $res = $model->updateData($update, $this->id, $lnKode);

            if ($res) {
                // Update log sampel: set kolom pengujian dengan waktu saat ini
                try {
                    $modelLogSampel = new MyModel('t_log_sampel');
                    $logUpdate = [
                        'pengujian' => date('Y-m-d H:i:s')
                    ];
                    
                    // Update berdasarkan kode_layanan
                    $logUpdateResult = $modelLogSampel->updateData($logUpdate, 'kode_layanan', $lnKode);
                    
                    if (!$logUpdateResult) {
                        log_message('warning', 'Gagal update log sampel untuk kode_layanan: ' . $lnKode);
                    }
                } catch (\Exception $logEx) {
                    // Log error tapi jangan gagalkan approve
                    log_message('error', 'Error update log sampel: ' . $logEx->getMessage());
                }

                $response['res'] = true;
                $response['msg'] = 'Data berhasil diapprove.';
                // bila perlu kirim status baru juga
                $response['newStatus'] = $update['lnStatus'];
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

    // ==========================================
    // KERANJANG FUNCTIONS REMOVED
    // ==========================================
    // All keranjang-related functions have been moved to Modules\KeranjangAdmin
    // This module now uses KeranjangAdmin for all cart operations:
    // - keranjangadmin/datalist
    // - keranjangadmin/dataListLayanan
    // - keranjangadmin/submit
    // - keranjangadmin/delete/:id
    // - keranjangadmin/checkout
    // - keranjangadmin/setPelanggan
    // - keranjangadmin/kategoriList
    // ==========================================

}
