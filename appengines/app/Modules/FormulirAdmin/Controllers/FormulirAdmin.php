<?php 

namespace Modules\FormulirAdmin\Controllers;

use App\Controllers\BaseController;
use App\Models\MyModel;

class FormulirAdmin extends BaseController
{
    private $table = 'simlab_t_layanan';
    private $id    = 'lnKode';
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
            $users = $modelUser->getAllDataWithOrder([], ['user_name' => 'ASC']);
        } else {
            // fallback ke query builder jika MyModel tidak punya helper
            $db = \Config\Database::connect();
            $users = $db->table('simlab_account_users')->select('user_id, user_name, user_email, user_identity, user_instansi')->orderBy('user_name', 'ASC')->get()->getResult();
        }

        // Ambil daftar kategori yang benar-benar ada di data pengujian
        $db = \Config\Database::connect();
        $builder = $db->table('simlab_r_layanan_pengujian as lp');

        $builder->select(' DISTINCT TRIM(LEFT(lp.ujiJenKode, 2)) as jenKode, j.jenNama ');
        $builder->join('simlab_r_jenis j', 'j.jenKode = TRIM(LEFT(lp.ujiJenKode, 2))', 'left');
        $builder->where('lp.ujiJenKode IS NOT NULL');
        $builder->where('lp.ujiJenKode !=', '');
        $builder->orderBy('j.jenNama', 'ASC');

        $categoriesRaw = $builder->get()->getResult();

        // NORMALISASI kategori
        $categories = [];
        if (!empty($categoriesRaw)) {
            foreach ($categoriesRaw as $c) {
                $kode = isset($c->jenKode) ? trim((string)$c->jenKode) : '';
                $nama = (isset($c->jenNama) && trim((string)$c->jenNama) !== '') ? trim((string)$c->jenNama) : $kode;
                if ($kode !== '') {
                    $categories[] = (object)[ 'jenKode' => $kode, 'jenNama' => $nama ];
                }
            }
        }

        $data = [
            'title' => 'Data Formulir Admin',
            'user'  => (new MyModel('simlab_account_users'))->getDataById('user_id', $user_id),
            'users' => $users,
            'categories' => $categories
        ];

        return view('Modules\FormulirAdmin\Views\v_formulirAdmin', $data);
    }

    public function delete($id)
    {
        $id    = $this->encrypter->decrypt(hex2bin($id));
        $model = new MyModel($this->table);
        $res   = $model->deleteData($this->id, $id);

        return $this->response->setJSON([
            'res'   => $res,
            'xname' => csrf_token(),
            'xhash' => csrf_hash()
        ]);
    }

    public function submit()
    {
        $idenc = $this->request->getPost('id');
        $data  = [
            'lnOrangNama' => $this->request->getPost('lnOrangNama'),
            'lnInstansi'  => $this->request->getPost('lnInstansi'),
        ];

        $model = new MyModel($this->table);

        if ($idenc == "") {
            $res = $model->insertData($data);
        } else {
            $id  = $this->encrypter->decrypt(hex2bin($idenc));
            $res = $model->updateData($data, $this->id, $id);
        }

        return $this->response->setJSON([
            'res'   => $res,
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
        $data  = [];

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

        $userModel  = new MyModel('simlab_account_users');
        $layananDet = new MyModel('simlab_t_layanan_detil');
        $db = \Config\Database::connect();

        foreach ($list as $row) {
            $lnStatusInt = (int)$row->lnStatus;

            // Jika ada filterStatuses, hanya proses baris yang cocok
            if (is_array($filterStatuses)) {
                if (!in_array($lnStatusInt, $filterStatuses, true)) continue;
            } else {
                // Perilaku default lama: lewati status tertentu (0,2)
                if (in_array($lnStatusInt, [0, 2], true)) continue;
            }

            // jika ada jenKodeParam, kita cek di detil apakah baris ini memiliki layanan yang termasuk jenKodeParam
            if ($jenKodeParam !== '') {
                try {
                    $dets = $layananDet->getAllDataById(['detLnKode' => $row->lnKode]);
                    $hasMatch = false;
                    foreach ($dets as $dd) {
                        $detJen = isset($dd->detJenKode) ? trim(substr($dd->detJenKode, 0, 2)) : '';
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
            $detil = $layananDet->getAllDataById(['detLnKode' => $row->lnKode]);
            $items = [];
            foreach ($detil as $d) {
                $items[] = $d->detLayanan ?? $d->detJenKode;
            }

            // ambil data user (logika tetap dipertahankan)
            $personName   = null;
            $userIdentity = '-';
            $instansi     = '-';
            $u            = null;

            // 1) Cek langsung dari user_id (FK)
            if (!empty($row->user_id)) {
                $u = $userModel->getDataById('user_id', $row->user_id);
            }

            // 2) Jika belum ada, cek berdasarkan email (lnAccEmail)
            if (!$u && !empty($row->lnAccEmail)) {
                $users = $userModel->getAllDataById(['user_email' => $row->lnAccEmail]);
                if (!empty($users)) $u = is_array($users) ? $users[0] : $users;
            }

            // 3) Jika masih belum ketemu, cari user_id dari invoice (lnNoTransaksi)
            if (!$u && !empty($row->lnNoTransaksi)) {
                $qb = $db->table($this->table);
                $qb->select('user_id')
                   ->where('lnNoTransaksi', $row->lnNoTransaksi)
                   ->where('user_id IS NOT NULL', null, false);
                $res = $qb->get()->getResult();
                if (!empty($res)) {
                    $foundUserId = (int)$res[0]->user_id;
                    $u = $userModel->getDataById('user_id', $foundUserId);
                }
            }

            // 4) Jika user ditemukan, ambil info
            if ($u) {
                $personName   = $u->user_name ?? $u->user_email ?? '-';
                $instansi     = $u->user_instansi ?? '-';
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

            $lihatDetailBtn = '<button type="button" class="btn btn-sm btn-info" 
                                title="Lihat Detail Item Layanan" 
                                onclick="loadDetail(\'' . $id . '\')">
                                <i class="bi bi-eye"></i> Lihat Layanan</button>';
            $response[] = $lihatDetailBtn;

            $response[] = $this->aksi($id, $row->lnStatus);

            $data[] = $response;
        }

        return $this->response->setJSON(['items' => $data]);
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

        // Encrypted hex parent
        $encLnId = bin2hex($this->encrypter->encrypt($kode));

        $db = \Config\Database::connect();
        $builder = $db->table('simlab_t_layanan_detil as d');

        $builder->select("
            d.detUjiKode,
            d.detLnKode,
            d.detLayanan,
            d.detJenKode,
            GROUP_CONCAT(DISTINCT d.detKeterangan SEPARATOR ' | ') AS detKet,
            GROUP_CONCAT(DISTINCT d.detKetLn SEPARATOR ' | ') AS detKetLn,
            SUM(d.detJumlah) AS jumlah,
            SUM(d.detBiaya) AS detBiaya,
            MAX(d.detStatus) AS detStatusGroup
        ");
        $builder->where('d.detLnKode', $kode);
        $builder->groupBy('d.detUjiKode, d.detLnKode, d.detLayanan, d.detJenKode');
        $rows = $builder->get()->getResult();

        $data = [];
        $no = 1;

        foreach ($rows as $row) {
            $response = [];
            $response[] = $no++;
            $response[] = $row->detLayanan ?? '-';
            $response[] = isset($row->detBiaya) ? number_format($row->detBiaya, 0, ',', '.') : '-';
            $response[] = isset($row->jumlah) ? (int)$row->jumlah : 0;
            $response[] = '<div 
                        style="display:block; max-width:240px; min-width:160px; width:100%;
                            max-height:120px; min-height:48px; overflow-y:auto; overflow-x:hidden;
                            padding:4px 6px; border:1px solid #ddd; border-radius:4px; background:#f9f9f9;
                            white-space:pre-wrap; word-break:break-word; font-size:0.9rem;">'
                        . htmlspecialchars($row->detKet ?? '', ENT_QUOTES, 'UTF-8') .
                        '</div>';

            // Ambil status grouping (1 = diterima, 2 = ditolak, lainnya = belum diproses)
            $statusGroup = isset($row->detStatusGroup) ? (int)$row->detStatusGroup : null;

            if ($statusGroup === 1) {
                $statusHtml = '<span class="badge bg-success">Diterima</span>';
            } elseif ($statusGroup === 2) {
                $statusHtml = '<span class="badge bg-danger">Ditolak</span>';
            } else {
                $statusHtml = '<span class="badge bg-secondary">Pending</span>';
            }
            $response[] = $statusHtml;

            // tambahkan detKetLn dari detail
            $response[] = '<div 
                        style="display:block; max-width:240px; min-width:160px; width:100%;
                            max-height:120px; min-height:48px; overflow-y:auto; overflow-x:hidden;
                            padding:4px 6px; border:1px solid #ddd; border-radius:4px; background:#f9f9f9;
                            white-space:pre-wrap; word-break:break-word; font-size:0.9rem;">'
                        . htmlspecialchars($row->detKetLn ?? '', ENT_QUOTES, 'UTF-8') .
                        '</div>';

            $data[] = $response;
        }

        return $this->response->setJSON(['items' => $data]);
    }

    private function aksi($id, $status)
    {
        $btn = '<div id="' . $id . '" class="float-end d-flex align-items-center" style="gap:6px;">';

        // tombol Approve (sesuai kondisi Anda) — tampil seperti sekarang
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
                $userObj  = null;

                if ($row) {
                    $modelUser = new MyModel('simlab_account_users');

                    // 1) coba dari user_id FK
                    if (!empty($row->user_id)) {
                        $userObj = $modelUser->getDataById('user_id', $row->user_id);
                    }

                    // 2) jika belum ada, coba cari berdasarkan lnAccEmail
                    if (!$userObj && !empty($row->lnAccEmail)) {
                        $users = $modelUser->getAllDataById(['user_email' => $row->lnAccEmail]);
                        if (!empty($users)) $userObj = is_array($users) ? $users[0] : $users;
                    }

                    // Ambil field telepon dari objek user (cek beberapa nama kolom umum)
                    if ($userObj) {
                        if (isset($userObj->user_phone) && !empty($userObj->user_phone)) $phoneRaw = $userObj->user_phone;
                        elseif (isset($userObj->user_telpon) && !empty($userObj->user_telpon)) $phoneRaw = $userObj->user_telpon;
                        elseif (isset($userObj->user_telp) && !empty($userObj->user_telp)) $phoneRaw = $userObj->user_telp;
                        elseif (isset($userObj->phone) && !empty($userObj->phone)) $phoneRaw = $userObj->phone;
                        // tambahkan nama kolom lain jika DB anda memakai nama berbeda
                    }
                }

                // normalisasi nomor
                if (!empty($phoneRaw)) {
                    $waDigits = $this->normalize_phone_for_whatsapp($phoneRaw);
                    if ($waDigits !== '') {
                        $displayName = $userObj->user_name ?? null;

                        // buat pesan pembuka (encoded)
                        $message = $displayName ? "Halo " . $displayName . ", saya ingin bertanya tentang layanan." : "Halo, saya ingin bertanya tentang layanan.";
                        $msgEncoded = rawurlencode($message);

                        $waUrl = "https://wa.me/" . $waDigits . "?text=" . $msgEncoded;

                        // Tombol WA bergaya kotak, mirip accept — gunakan onclick membuka tab baru
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
            case 0: return '<span class="badge bg-secondary">Draft</span>';
            case 1: return '<span class="badge bg-warning">In Review Manajer</span>';
            case 2: return '<span class="badge bg-danger">Ditolak</span>';
            case 3: return '<span class="badge bg-info">Belum direview</span>';
            case 4: return '<span class="badge bg-primary">Dalam pengujian</span>';
            case 5: return '<span class="badge bg-primary">LHUS diproses</span>';
            case 6: return '<span class="badge bg-success">LHUS disetujui</span>';
            case 7: return '<span class="badge bg-primary">LHU diproses</span>';
            case 8: return '<span class="badge bg-success">LHU disetujui</span>';
            case 9: return '<span class="badge bg-dark">Pengujian selesai</span>';
            default: return '<span class="badge bg-dark">Unknown</span>';
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
            } catch (\Throwable $e) {}
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
    public function normalize_phone_for_whatsapp($rawPhone) {
        if (empty($rawPhone)) return '';
        // keep digits only
        $digits = preg_replace('/\D+/', '', (string)$rawPhone);
        if ($digits === '') return '';
        // jika mulai dengan 0 -> ganti 0 dengan 62 (Indonesia)
        if (strpos($digits, '0') === 0) {
            $digits = '62' . substr($digits, 1);
        }
        // jika mulai dengan 62 sudah ok; jika mulai dengan +62 (already removed +), ok
        // jika panjang terlalu pendek, bail out
        if (strlen($digits) < 8) return '';
        return $digits;
    }

    /**
     * Buat tombol HTML untuk membuka WhatsApp (wa.me). Aman untuk output view.
     * $name digunakan untuk pesan pembuka (opsional).
     */
    public function whatsapp_button_html($phoneDigits, $name = null) {
        if (empty($phoneDigits)) return '';
        $text = $name ? "Halo%20" . rawurlencode($name) . "%2C%20saya%20ingin%20bertanya%20tentang%20layanan." : "Halo%2C%20saya%20ingin%20bertanya%20tentang%20layanan.";
        $url = "https://wa.me/" . $phoneDigits . "?text=" . $text;
        // tombol kecil dengan icon bootstrap (bi bi-whatsapp)
        return '<a href="' . esc($url) . '" target="_blank" rel="noopener noreferrer" class="btn btn-sm btn-success ms-1" title="Chat via WhatsApp">'
            . '<i class="bi bi-whatsapp"></i>'
            . '</a>';
    }

  



   //keranjang layanan

    public function keranjang()
    {
        $session = session();
        $user_id = $session->get('id_user');
        $modelUser = new MyModel('simlab_account_users');

        // Ambil daftar kategori yang BENAR-BENAR ada di data pengujian
        $db = \Config\Database::connect();
        $builder = $db->table('simlab_r_layanan_pengujian as lp');
        
        // Query untuk mendapatkan kategori yang benar-benar digunakan
        $builder->select('
            DISTINCT TRIM(LEFT(lp.ujiJenKode, 2)) as jenKode,
            j.jenNama
        ');
        $builder->join('simlab_r_jenis j', 'j.jenKode = TRIM(LEFT(lp.ujiJenKode, 2))', 'left');
        $builder->where('lp.ujiJenKode IS NOT NULL');
        $builder->where('lp.ujiJenKode !=', '');
        $builder->orderBy('j.jenNama', 'ASC');
        
        $categories = $builder->get()->getResult();
        
        // NORMALISASI: pastikan jenNama selalu ada (fallback ke jenKode)
        $normalized = [];
        if (!empty($categories)) {
            foreach ($categories as $c) {
                $kode = isset($c->jenKode) ? trim((string)$c->jenKode) : '';
                $nama = (isset($c->jenNama) && trim((string)$c->jenNama) !== '') ? trim((string)$c->jenNama) : $kode;

                if ($kode !== '') {
                    // buat object seragam supaya view bisa mengakses ->jenKode / ->jenNama
                    $normalized[] = (object)[
                        'jenKode' => $kode,
                        'jenNama' => $nama
                    ];
                }
            }
        }

        // Re-index array; jika tetap kosong, kirim array kosong (view akan fallback via AJAX)
        $categories = array_values($normalized);

        $data = [
            'title'      => 'Keranjang Layanan',
            'user'       => $modelUser->getDataById('user_id', $user_id),
            // NOTE: supaya persis sama seperti modul Pelayanan, view berikut arahkan ke v_keranjang Pelayanan
            'categories' => $categories,
        ];

        // jika Anda ingin view khusus FormulirAdmin, ganti path view di sini
        return view('Modules\Pelayanan\Views\v_keranjang', $data);
    }

    /**
     * keranjangDatalist
     * - membersihkan duplikat berdasarkan kombinasi kode+alat+keterangan (server-side)
     * - menyimpan hasil pembersihan kembali ke session
     * - mengembalikan info pelanggan jika ada di session
     * Note: sekarang juga mengirimkan kolom 'alat' sehingga preview tabel menampilkan Instrumen/Alat/Tempat
     */
    public function keranjangDatalist()
    {
        $session = session();
        $keranjang = $session->get($this->sessionKey) ?? [];

        // HAPUS DUPLIKAT: gunakan key = md5(kode|alat|keterangan)
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

        $data = [];
        foreach ($cleaned as $idx => $row) {
            $response = [];

            $layanan    = isset($row['layanan']) ? esc($row['layanan']) : '-';
            $alat       = isset($row['alat']) ? esc($row['alat']) : '-';
            $jumlah     = isset($row['jumlah']) ? (int)$row['jumlah'] : 0;
            $keterangan = isset($row['keterangan']) ? esc($row['keterangan']) : '';
            $diskon     = isset($row['diskon']) ? (float)$row['diskon'] : 0;

            $biayaAsli  = isset($row['biaya_asli']) ? (float)$row['biaya_asli'] : 0;
            $biayaTotal = isset($row['biaya']) ? (float)$row['biaya'] : 0;

            // 1️ Parameter (tanpa warna / badge)
            $response[] = $layanan;

            // 2️ Instrumen / Alat / Tempat
            $response[] = $alat;

            // 3️ Biaya satuan
            if ($diskon > 0) {
                $hargaDiskon = $biayaAsli - ($biayaAsli * ($diskon / 100));
                $biayaTampil = '<span style="text-decoration:line-through;">Rp ' . number_format($biayaAsli, 0, ',', '.') . '</span><br>';
                $biayaTampil .= 'Rp ' . number_format($hargaDiskon, 0, ',', '.');
            } else {
                $biayaTampil = 'Rp ' . number_format($biayaAsli, 0, ',', '.');
            }
            $response[] = $biayaTampil;

            // 4️ Jumlah
            $response[] = $jumlah;

            // 5️ Diskon
            $response[] = $diskon > 0 ? $diskon . '%' : '-';

            // 6️ Total
            $response[] = 'Rp ' . number_format($biayaTotal, 0, ',', '.');

            // 7️ Keterangan
            $response[] = $keterangan;

            // 8️ Aksi
            $response[] = $this->aksiKeranjang($idx);

            $data[] = $response;
        }

        // kembalikan info pelanggan di session
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

        return $this->response->setJSON(['items' => $data, 'pelanggan' => $pelanggan]);
    }

    /**
     * keranjangSubmit
     * - jika pelanggan belum dipilih => tolak (frontend sudah memeriksa, ini lapis server)
     * - jika item dengan kode+alat sama ada: tambahkan jumlah & ganti keterangan (bukan gabung),
     *   hitung ulang biaya berdasarkan biaya_asli & diskon yang tersimpan atau diterima
     * - jika item baru: tambahkan sesuai struktur (simpan biaya_asli, diskon, dll)
     * - untuk pelanggan dengan status 'ULM' terapkan diskon yang dikirim (jika ada), selain itu diskon = 0
     */
    
    public function keranjangSubmit()
{
    $session = session();

    // cek pelanggan di session
    $pelanggan = $session->get($this->sessionKey . '_pelanggan') ?? null;

    // ===== Perbaikan minimal: terima selectedUserId langsung dari POST jika session kosong =====
    $selectedUserIdFromPost = (int)($this->request->getPost('selectedUserId') ?? 0);
    if ((empty($pelanggan) || empty($pelanggan['user_id'])) && $selectedUserIdFromPost) {
        try {
            $modelUser = new MyModel('simlab_account_users');
            $u = $modelUser->getDataById('user_id', $selectedUserIdFromPost);
            if ($u) {
                $pelanggan = [
                    'user_id' => (int)$u->user_id,
                    'name'    => $u->user_name ?? null,
                    'email'   => $u->user_email ?? null,
                    'status'  => isset($u->user_identity) ? strtoupper(trim($u->user_identity)) : null
                ];
                // simpan ke session supaya konsisten dengan flow lainnya
                $session->set($this->sessionKey . '_pelanggan', $pelanggan);
            }
        } catch (\Throwable $e) {
            // jika gagal ambil user, lanjutkan tanpa crash (pelanggan tetap null)
            log_message('warning', 'keranjangSubmit: gagal ambil selectedUserId dari POST: ' . $e->getMessage());
        }
    }

    // jika masih belum ada pelanggan, tolak request (lapis server)
    if (empty($pelanggan) || empty($pelanggan['user_id'])) {
        return $this->response->setJSON([
            'res' => false,
            'msg' => 'Pelanggan belum dipilih. Silakan pilih pelanggan di modal sebelum menambahkan layanan.',
            'xname' => csrf_token(),
            'xhash' => csrf_hash()
        ]);
    }

    // ambil input
    $detUjiKode   = $this->request->getPost('detUjiKode');
    $detAlat      = $this->request->getPost('detAlat');
    // gunakan getPost konsisten (hindari akses langsung ke $_POST)
    $detBiaya     = (float) ($this->request->getPost('detBiaya') ?? 0);
    $detParameter = $this->request->getPost('detParameter');
    $detDiskonCli = (float) ($this->request->getPost('detDiskon') ?? 0);
    $detInstansi  = $this->request->getPost('detInstansi');
    $detJumlah    = max(1, (int) ($this->request->getPost('detJumlah') ?? 1));
    $detKeterangan= trim($this->request->getPost('detKeterangan') ?? '');

    if (empty($detUjiKode) || $detJumlah < 1) {
        return $this->response->setJSON([
            'res' => false,
            'msg' => 'Data tidak valid.',
            'xname' => csrf_token(),
            'xhash' => csrf_hash()
        ]);
    }

    // tentukan appliedDiskon dari status pelanggan (ULM dapat diskon)
    $appliedDiskon = 0;
    if (isset($pelanggan['status']) && strtoupper(trim((string)$pelanggan['status'])) === 'ULM') {
        $appliedDiskon = max(0, $detDiskonCli);
    } else {
        $appliedDiskon = 0;
    }

    $jumlah = max(1, $detJumlah);
    $biayaPerItem = max(0, $detBiaya);
    $biayaSetelahDiskon = $biayaPerItem * (1 - ($appliedDiskon / 100));
    $biayaTotalBaru = $biayaSetelahDiskon * $jumlah;

    $keranjang = $session->get($this->sessionKey) ?? [];

    // Cari item dengan kode & alat yang sama (merge behavior)
    $found = false;
    foreach ($keranjang as $idx => $item) {
        $sameKode = isset($item['kode']) && (string)$item['kode'] === (string)$detUjiKode;
        $sameAlat = (isset($item['alat']) ? trim((string)$item['alat']) : '') === trim((string)$detAlat);

        if ($sameKode && $sameAlat) {
            // Tambah jumlah
            $existingJumlah = isset($item['jumlah']) ? (int)$item['jumlah'] : 0;
            $newJumlah = $existingJumlah + $jumlah;
            $keranjang[$idx]['jumlah'] = $newJumlah;

            // Ganti keterangan (replace, jangan gabung)
            $keranjang[$idx]['keterangan'] = $detKeterangan;

            // Pastikan biaya_asli tetap (jika tidak ada, gunakan detBiaya)
            $biayaAsli = isset($item['biaya_asli']) ? (float)$item['biaya_asli'] : $biayaPerItem;

            // diskon: pertahankan diskon yang ada di item jika ada, else gunakan appliedDiskon
            $disk = isset($item['diskon']) ? (float)$item['diskon'] : $appliedDiskon;

            // Hitung ulang total biaya item berdasarkan biaya asli & disk
            $keranjang[$idx]['biaya'] = ($biayaAsli * $newJumlah) * (1 - ($disk / 100));
            $keranjang[$idx]['biaya_asli'] = $biayaAsli;
            $keranjang[$idx]['diskon'] = $disk;

            $found = true;
            break;
        }
    }

    if (!$found) {
        // tambahkan sebagai item baru
        $keranjang[] = [
            'kode' => $detUjiKode,
            'alat' => $detAlat ?? '',
            'layanan' => $detParameter ?? 'Layanan',
            'biaya_asli' => $biayaPerItem,
            'biaya' => $biayaTotalBaru,
            'diskon' => $appliedDiskon,
            'jumlah' => $jumlah,
            'keterangan' => $detKeterangan,
            'ujiPenyelia' => null,
            'ujiManajerTeknis' => null
        ];
    }

    // simpan kembali
    $session->set($this->sessionKey, $keranjang);

    return $this->response->setJSON([
        'res' => true,
        'msg' => $found ? 'Item berhasil ditambahkan di keranjang.' : 'Item baru berhasil ditambahkan ke keranjang.',
        'xname' => csrf_token(),
        'xhash' => csrf_hash(),
        'items_count' => count($keranjang)
    ]);
}

public function keranjangDelete($id) { $session = session(); $keranjang = $session->get($this->sessionKey) ?? []; 
    // Jika index valid dan ada, hapus. Jika tidak ada, tetap dianggap sukses (tanpa validasi). 
    if (isset($keranjang[$id])) { unset($keranjang[$id]); 
        // reset index agar berurutan kembali 
        $keranjang = array_values($keranjang); $session->set($this->sessionKey, $keranjang); 
        return $this->response->setJSON([ 'res' => true, 'msg' => 'Item berhasil dihapus.', 'xname' => csrf_token(), 'xhash' => csrf_hash() ]); } 
        // Kalau index tidak ditemukan, jangan error — kembalikan sukses juga. // (Opsional: bisa tetap set session jika keranjang kosong) 
        if (empty($keranjang)) { $session->remove($this->sessionKey); } else { 
            // tidak ditemukan, tetap simpan keranjang apa adanya (no-op) 
            $session->set($this->sessionKey, $keranjang); } 
            
            
            return $this->response->setJSON([ 'res' => true, 'msg' => 'Item tidak ditemukan di keranjang, namun operasi hapus dianggap berhasil.', 'xname' => csrf_token(), 'xhash' => csrf_hash() ]);
         }


    public function keranjangDataListLayanan()
{
    $session = session();
    $user_id = $session->get('id_user');
    
    // Ambil info user untuk cek user_identity
    $modelUser = new MyModel('simlab_account_users');
    $session = session();

    // dapatkan pelanggan terpilih dari session (jika ada)
    $pelangganSession = $session->get($this->sessionKey . '_pelanggan') ?? null;
    $effectiveIdentity = '';

    // jika ada pelanggan di session gunakan itu
    if ($pelangganSession && !empty($pelangganSession['status'])) {
        $effectiveIdentity = strtoupper(trim((string)$pelangganSession['status']));
    } else {
        // fallback: gunakan user yang sedang login (admin/operator)
        $user = $modelUser->getDataById('user_id', $user_id);
        if ($user && isset($user->user_identity)) {
            $effectiveIdentity = strtoupper(trim((string)$user->user_identity));
        }
    }

    // Baca parameter filter dari GET request
    $qRaw = trim((string) ($this->request->getGet('q') ?? $this->request->getGet('search') ?? ''));
    $q = $qRaw !== '' ? mb_strtolower($qRaw, 'UTF-8') : '';
    
    // PERBAIKAN: Baca jenKode sebagai filter kategori
    $jenKodeFilter = trim((string) ($this->request->getGet('jenKode') ?? ''));

    // SANITASI tambahan: jika client keliru mengirim sesuatu seperti "A?page=1" atau "A&page=1",
    // kita buang sisa setelah '?' atau '&' dan ambil hanya token jenKode yang relevan.
    if ($jenKodeFilter !== '') {
        // decode dulu untuk menghindari encoding oddities
        $jenKodeFilter = rawurldecode($jenKodeFilter);
        // hapus bagian query yang tersisa jika ada
        $jenKodeFilter = preg_replace('/[?&].*$/', '', $jenKodeFilter);
        // jika ada tanda sama dengan (mis-sent key=value), ambil bagian nilai/atau kunci tergantung struktur.
        if (strpos($jenKodeFilter, '=') !== false) {
            // bisa jadi bentuk "jenKode=A" atau "A=page=1"; ambil bagian sebelum '=' kecuali kosong
            $parts = explode('=', $jenKodeFilter, 2);
            if ($parts[0] === '') {
                $jenKodeFilter = $parts[1];
            } else {
                $jenKodeFilter = $parts[0];
            }
        }
        // batasi ke karakter alfanumerik dan ambil maksimal 2 char (format jenKode di DB)
        $jenKodeFilter = preg_replace('/[^A-Za-z0-9]/', '', $jenKodeFilter);
        $jenKodeFilter = substr($jenKodeFilter, 0, 2);
        $jenKodeFilter = trim($jenKodeFilter);
    }
    
    // Query menggunakan Query Builder untuk lebih fleksibel
    $db = \Config\Database::connect();
    $builder = $db->table('simlab_r_layanan_pengujian as lp');
    
    $builder->select('
        lp.ujiKode,
        lp.ujiBiaya,
        lp.ujiInstansi,
        lp.ujiDiskon,
        lp.ujiLayanan,
        lp.ujiJenKode,
        p.paraNama,
        a.alatNama,
        j.jenNama
    ');
    
    $builder->join('simlab_r_parameter p', 'p.paraKode = lp.ujiParaKode', 'left');
    $builder->join('simlab_r_alat a', 'a.alatKode = lp.ujiAlatKode', 'left');
    $builder->join('simlab_r_jenis j', 'j.jenKode = lp.ujiJenKode', 'left');
    
    // Filter kategori: EXACT MATCH atau LEFT() untuk VARCHAR yang lebih panjang
    if ($jenKodeFilter !== '') {
        $builder->where("TRIM(LEFT(lp.ujiJenKode, 2))", $jenKodeFilter);
    }
    
    $builder->orderBy('lp.ujiKode', 'ASC');
    
    $listUji = $builder->get()->getResult();

    // Filter tambahan untuk search query (free text)
    if ($q !== '') {
        $filtered = [];
        foreach ($listUji as $row) {
            $fields = [
                isset($row->paraNama) ? mb_strtolower($row->paraNama, 'UTF-8') : '',
                isset($row->alatNama) ? mb_strtolower($row->alatNama, 'UTF-8') : '',
                isset($row->ujiLayanan) ? mb_strtolower($row->ujiLayanan, 'UTF-8') : '',
                isset($row->jenNama) ? mb_strtolower($row->jenNama, 'UTF-8') : '',
                isset($row->ujiKode) ? (string)$row->ujiKode : ''
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
    $data = array();
    $no = 1;
    
    foreach ($listUji as $row) {
        $response = array();
         
        // Parameter
        $response[] = esc($row->paraNama ?? '-');
        
        // Instrumen/Alat
        $response[] = esc($row->alatNama ?? '-');
        
        // Tentukan diskon yang diperbolehkan
       $allowedDiskon = 0;
        if (!empty($row->ujiDiskon) && $row->ujiDiskon > 0 && $effectiveIdentity === 'ULM') {
            $allowedDiskon = (float)$row->ujiDiskon;
        }
        
        // Biaya
        $biaya = 'Rp ' . number_format($row->ujiBiaya, 0, ',', '.');
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
        $jenKodeClean = isset($row->ujiJenKode) ? trim(substr($row->ujiJenKode, 0, 2)) : '';
        
        $btnMasukkan = '
            <button type="button" 
                    class="btn btn-success btn-sm btnMasukkan" 
                    data-kode="' . esc($row->ujiKode) . '" 
                    data-alat="' . esc($row->alatNama ?? '') . '" 
                    data-biaya="' . $row->ujiBiaya . '" 
                    data-parameter="' . esc($row->paraNama ?? '') . '"
                    data-diskon="' . $allowedDiskon . '" 
                    data-instansi="' . esc($row->ujiInstansi ?? '') . '"
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
        "debug" => [
            "total_items" => count($data),
            "filter_jenKode" => $jenKodeFilter,
            "search_query" => $q
        ]
    ]);
}


    /**
     * keranjangCheckout - sekarang menerima selectedUserId dari frontend.
     * Jika selectedUserId valid, simpan user_id dan lnAccEmail dari tabel simlab_account_users.
     * Jika tidak ada atau tidak valid: fallback ke admin@lab.local (seperti sebelumnya).
     */
    public function keranjangCheckout()
{
    $session   = session();
    $keranjang = $session->get($this->sessionKey) ?? [];

    if (empty($keranjang)) {
        return $this->response->setJSON([
            'res'   => false,
            'msg'   => 'Keranjang masih kosong.',
            'xname' => csrf_token(),
            'xhash' => csrf_hash()
        ]);
    }

    $totalBiaya     = array_sum(array_column($keranjang, 'biaya'));
    $modelPembayaran= new MyModel('simlab_t_pembayaran');
    $modelLayanan   = new MyModel('simlab_t_layanan');
    $modelDetil     = new MyModel('simlab_t_layanan_detil');
    $modelAccount   = new MyModel('simlab_account'); // untuk validasi FK user_id
    $db             = \Config\Database::connect();

    $db->transStart();

    try {
        // ========= 1) Tentukan pemilik pesanan (pelanggan) =========
        $adminEmail      = 'admin@lab.local';
        $selectedUserId  = (int)($this->request->getPost('selectedUserId') ?? 0);

        // fallback: jika POST kosong, ambil dari session pelanggan
        if (!$selectedUserId) {
            $pelangganSession = $session->get($this->sessionKey . '_pelanggan') ?? null;
            if ($pelangganSession && !empty($pelangganSession['user_id'])) {
                $selectedUserId = (int)$pelangganSession['user_id'];
            }
        }

        $lnAccEmailToSave = $adminEmail;
        $userIdToSave     = null;

        if ($selectedUserId) {
            $userModel = new MyModel('simlab_account_users');
            $userData  = $userModel->getDataById('user_id', $selectedUserId);
            if ($userData) {
                $lnAccEmailToSave = $userData->user_email ?? $adminEmail;
                $userIdToSave     = $selectedUserId;
            }
        }

        // ========= 2) Insert header layanan =========
        $insertLayananId = $modelLayanan->insertData([
            'user_id'       => $userIdToSave,
            'lnAccEmail'    => 'by_admin',
            'lnNoTransaksi' => null,
            'lnTgl'         => date('Y-m-d H:i:s'),
            'lnStatus'      => 1,
            'kuisioner'     => 0
        ], true);

        if (!$insertLayananId) $insertLayananId = $db->insertID();
        if (!$insertLayananId) {
            $db->transRollback();
            return $this->response->setJSON([
                'res'   => false,
                'msg'   => 'Gagal membuat record layanan (lnKode tidak tersedia).',
                'xname' => csrf_token(),
                'xhash' => csrf_hash()
            ]);
        }
        $lnKode = (int)$insertLayananId;

        // ========= 3) Insert pembayaran =========
        $insertPembayaranId = $modelPembayaran->insertData([
            'bayarLnKode'     => $lnKode,
            'bayarTotalBiaya' => $totalBiaya,
            'bayarStatus'     => 0,
            'bayarInvoiceNo'  => null,
            'bayarInvoiceTgl' => date('Y-m-d'),
            'bayarBuktiFile'  => null
        ], true);

        if (!$insertPembayaranId) {
            $db->transRollback();
            return $this->response->setJSON([
                'res'   => false,
                'msg'   => 'Gagal simpan pembayaran.',
                'xname' => csrf_token(),
                'xhash' => csrf_hash()
            ]);
        }

        // ========= 4) Insert detail: validasi penyelia/manajer & isi detJenKode =========
        foreach ($keranjang as $i => $item) {
            // Kandidat dari item (jika suatu saat frontend mengirimkan)
            $penyeliaCand = isset($item['ujiPenyelia']) && is_numeric($item['ujiPenyelia']) ? (int)$item['ujiPenyelia'] : null;
            $manajerCand  = isset($item['ujiManajerTeknis']) && is_numeric($item['ujiManajerTeknis']) ? (int)$item['ujiManajerTeknis'] : null;
            $jenKodeCand  = isset($item['jenKode']) ? trim((string)$item['jenKode']) : null;

            $validPenyelia = null;
            $validManajer  = null;
            $validJenKode  = null;

            // Cek user_id penyelia & manajer di simlab_account (hindari FK error)
            try {
                if ($penyeliaCand) {
                    $acc = $modelAccount->getDataById('user_id', $penyeliaCand);
                    $validPenyelia = $acc ? $penyeliaCand : null;
                }
                if ($manajerCand) {
                    $acc2 = $modelAccount->getDataById('user_id', $manajerCand);
                    $validManajer = $acc2 ? $manajerCand : null;
                }
            } catch (\Throwable $e) {
                $validPenyelia = null;
                $validManajer  = null;
            }

            // Fallback: ambil dari master pengujian jika perlu (berdasarkan ujiKode)
            if (($validPenyelia === null || $validManajer === null || $validJenKode === null) && !empty($item['kode'])) {
                try {
                    $row = $db->table('simlab_r_layanan_pengujian')
                              ->select('ujiPenyelia, ujiManajerTeknis, ujiJenKode')
                              ->where('ujiKode', $item['kode'])
                              ->get()->getRow();

                    if ($row) {
                        if ($validPenyelia === null && isset($row->ujiPenyelia) && is_numeric($row->ujiPenyelia)) {
                            $acc3 = $modelAccount->getDataById('user_id', (int)$row->ujiPenyelia);
                            if ($acc3) $validPenyelia = (int)$row->ujiPenyelia;
                        }
                        if ($validManajer === null && isset($row->ujiManajerTeknis) && is_numeric($row->ujiManajerTeknis)) {
                            $acc4 = $modelAccount->getDataById('user_id', (int)$row->ujiManajerTeknis);
                            if ($acc4) $validManajer = (int)$row->ujiManajerTeknis;
                        }
                        if ($validJenKode === null && !empty($row->ujiJenKode)) {
                            $validJenKode = substr(preg_replace('/[^A-Za-z0-9]/', '', (string)$row->ujiJenKode), 0, 2);
                        }
                    }
                } catch (\Throwable $e) {
                    // silent fallback
                }
            }

            // Sanitasi jenKode dari item jika masih null
            if ($validJenKode === null && $jenKodeCand !== null) {
                $validJenKode = substr(preg_replace('/[^A-Za-z0-9]/', '', $jenKodeCand), 0, 2);
            }

            // Logging jika id tidak valid (tidak hentikan proses, set NULL)
            if ($penyeliaCand && $validPenyelia === null) {
                log_message('warning', "FormulirAdmin Checkout: penyelia {$penyeliaCand} tidak valid (item {$i}), set NULL.");
            }
            if ($manajerCand && $validManajer === null) {
                log_message('warning', "FormulirAdmin Checkout: manajerTeknis {$manajerCand} tidak valid (item {$i}), set NULL.");
            }
            if ($jenKodeCand && $validJenKode === null) {
                log_message('warning', "FormulirAdmin Checkout: jenKode '{$jenKodeCand}' tidak valid (item {$i}), set NULL.");
            }

            $detil = [
                'detLnKode'         => $lnKode,
                'detUjiKode'        => $item['kode'] ?? null,
                'detBiaya'          => $item['biaya'] ?? null,
                'detJumlah'         => $item['jumlah'] ?? 1,
                'detKeterangan'     => $item['keterangan'] ?? null,
                'detLayanan'        => $item['layanan'] ?? null,
                'detStatus'         => 0,
                'detJenKode'        => $validJenKode,     // <<=== penting
                'detPenyelia'       => $validPenyelia,    // <<=== penting
                'detManajerTeknis'  => $validManajer      // <<=== penting
            ];

            $res = $modelDetil->insertData($detil);
            if (!$res) {
                $error = $modelDetil->db->error();
                log_message('error', 'Insert gagal simlab_t_layanan_detil. Data: ' . json_encode($detil));
                log_message('error', 'DB Error: ' . json_encode($error));
                $db->transRollback();
                return $this->response->setJSON([
                    'res'   => false,
                    'msg'   => 'Checkout gagal saat simpan detail: ' . ($error['message'] ?? 'Unknown error'),
                    'xname' => csrf_token(),
                    'xhash' => csrf_hash()
                ]);
            }
        }

        // ========= 5) Commit & bersihkan session =========
        $db->transComplete();
        $session->remove($this->sessionKey);
        $session->remove($this->sessionKey . '_pelanggan');

        return $this->response->setJSON([
            'res'   => true,
            'msg'   => 'Checkout berhasil!',
            'lnKode'=> $lnKode,
            'xname' => csrf_token(),
            'xhash' => csrf_hash()
        ]);
    } catch (\Exception $e) {
        if ($db->transStatus() === FALSE) {
            $db->transRollback();
        }
        log_message('error', 'Checkout exception (FormulirAdmin): ' . $e->getMessage() . ' Trace: ' . $e->getTraceAsString());
        return $this->response->setJSON([
            'res'   => false,
            'msg'   => 'Checkout gagal: ' . $e->getMessage(),
            'xname' => csrf_token(),
            'xhash' => csrf_hash()
        ]);
    }
}


    /**
 * Endpoint: set pelanggan ke session lalu recalc diskon & biaya di keranjang
 * POST params: selectedUserId, name, email, status
 */
public function keranjangSetPelanggan()
{
    $session = session();
    $selectedUserId = $this->request->getPost('selectedUserId');
    $name = $this->request->getPost('name');
    $email = $this->request->getPost('email');
    $status = $this->request->getPost('status');

    if (!$selectedUserId) {
        return $this->response->setJSON([
            'res' => false,
            'msg' => 'selectedUserId tidak ditemukan.',
            'xname' => csrf_token(),
            'xhash' => csrf_hash()
        ]);
    }

    // simpan pelanggan ke session
    $pelanggan = [
        'user_id' => (int)$selectedUserId,
        'name'    => $name ?? null,
        'email'   => $email ?? null,
        'status'  => $status ?? null
    ];
    $session->set($this->sessionKey . '_pelanggan', $pelanggan);

    // ambil keranjang sekarang
    $keranjang = $session->get($this->sessionKey) ?? [];

    // apakah pelanggan berhak diskon? cek status ULM atau lookup di DB bila status kosong
    $isUlm = false;
    if (!empty($pelanggan['status']) && strtoupper(trim((string)$pelanggan['status'])) === 'ULM') {
        $isUlm = true;
    } else {
        // fallback: cek di DB user_identity bila perlu
        try {
            $modelUser = new MyModel('simlab_account_users');
            $u = $modelUser->getDataById('user_id', (int)$selectedUserId);
            if ($u && isset($u->user_identity) && strtoupper(trim((string)$u->user_identity)) === 'ULM') {
                $isUlm = true;
            }
        } catch (\Throwable $e) {
            // silent: treat as non-ULM
        }
    }

    // Jika keranjang tidak kosong, recalc diskon & biaya masing-masing item
    $db = \Config\Database::connect();
    $diskonCache = []; // cache ujiDiskon per ujiKode

    foreach ($keranjang as $i => $item) {
        $kode = isset($item['kode']) ? trim((string)$item['kode']) : '';
        $biayaAsli = isset($item['biaya_asli']) ? (float)$item['biaya_asli'] : (float)($item['biaya'] ?? 0);
        $jumlah = isset($item['jumlah']) ? max(1, (int)$item['jumlah']) : 1;

        $appliedDiskon = 0.0;
        if ($isUlm && $kode !== '') {
            if (!array_key_exists($kode, $diskonCache)) {
                try {
                    $row = $db->table('simlab_r_layanan_pengujian')->select('ujiDiskon')->where('ujiKode', $kode)->get()->getRow();
                    $diskonCache[$kode] = ($row && isset($row->ujiDiskon)) ? (float)$row->ujiDiskon : 0.0;
                } catch (\Throwable $e) {
                    $diskonCache[$kode] = 0.0;
                }
            }
            $appliedDiskon = max(0, (float)$diskonCache[$kode]);
        } else {
            $appliedDiskon = 0.0;
        }

        // Hitung biaya baru berdasarkan biaya_asli & jumlah & diskon
        $newBiaya = ($biayaAsli * $jumlah) * (1 - ($appliedDiskon / 100));

        // update item di keranjang (pertahankan fields lain)
        $keranjang[$i]['diskon'] = $appliedDiskon;
        $keranjang[$i]['biaya'] = $newBiaya;
        if (!isset($keranjang[$i]['biaya_asli']) || empty($keranjang[$i]['biaya_asli'])) {
            $keranjang[$i]['biaya_asli'] = $biayaAsli;
        }
    }

    // simpan kembali keranjang ke session
    $session->set($this->sessionKey, $keranjang);

    // hitung grand total
    $grandTotal = 0;
    foreach ($keranjang as $it) {
        $grandTotal += isset($it['biaya']) ? (float)$it['biaya'] : 0;
    }

    return $this->response->setJSON([
        'res' => true,
        'msg' => 'Pelanggan disimpan dan diskon keranjang diperbarui.',
        'pelanggan' => $pelanggan,
        'items_count' => count($keranjang),
        'grand_total' => $grandTotal,
        'items' => $keranjang,
        'xname' => csrf_token(),
        'xhash' => csrf_hash()
    ]);
}


private function aksiKeranjang($id, $isPreview = false)
{
        // selalu panggil deleteItemFromPreview agar front-end konsisten
        $functionName = 'deleteItemFromPreview';
        return '<div id="item-' . $id . '" class="text-center">'
        . '<span data-index="' . $id . '" '
        . 'class="text-danger btn-action btn-delete-item" '
        . 'style="cursor: pointer;" '
        . 'title="Hapus" '
        . 'onclick="' . $functionName . '(event)">'
        . '<i class="bi bi-trash"></i>'
        . '</span>'
        . '</div>';
}

    
public function kategoriList()
{
    $db = \Config\Database::connect();
    $builder = $db->table('simlab_r_layanan_pengujian as lp');
    
    // PERBAIKAN: gunakan LEFT() untuk matching
    $builder->select('
        DISTINCT TRIM(LEFT(lp.ujiJenKode, 2)) as jenKode,
        j.jenNama
    ');
    $builder->join('simlab_r_jenis j', 'j.jenKode = TRIM(LEFT(lp.ujiJenKode, 2))', 'left');
    $builder->where('lp.ujiJenKode IS NOT NULL');
    $builder->where('lp.ujiJenKode !=', '');
    $builder->orderBy('j.jenNama', 'ASC');

    $categories = $builder->get()->getResult();
    
    // Filter out null/empty
    $categories = array_filter($categories, function($cat) {
        return !empty($cat->jenKode) && !empty($cat->jenNama);
    });

    return $this->response->setJSON([
        'success' => true,
        'categories' => array_values($categories)
    ]);
}



}
