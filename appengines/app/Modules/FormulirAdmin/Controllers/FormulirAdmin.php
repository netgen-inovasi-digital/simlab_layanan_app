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

        $data = [
            'title' => 'Data Formulir Admin',
            'user'  => (new MyModel('simlab_account_users'))->getDataById('user_id', $user_id),
            'users' => $users
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

    public function datalist()
    {
        $model = new MyModel($this->table);
        $data  = [];

        // Ambil parameter kategoriLayanan dari query string.
        $kategoriParam = $this->request->getGet('kategoriLayanan');
        $filterStatuses = null;
        if ($kategoriParam !== null && $kategoriParam !== '' && $kategoriParam !== 'all') {
            $parts = array_filter(array_map('trim', explode(',', $kategoriParam)));
            $filterStatuses = array_map('intval', $parts);
        }

        // Ambil semua data, urutkan tanggal DESC (terbaru di atas)
        $list = $model->getAllDataWithOrder(['lnTgl' => 'DESC']);

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
        $btn = '<div id="' . $id . '" class="float-end">';
        if ($status == 3) {
            $btn .= '<span class="text-success btn-action" title="Setujui" onclick="confirmApprove(event)">
                        <i class="bi bi-check-circle"></i></span> ';
            $btn .= '<label class="divider">|</label> ';
        }
        $btn .= '<span class="text-danger btn-action" title="Hapus" onclick="deleteItem(event)">
                    <i class="bi bi-trash"></i></span>
                 </div>';
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

    public function approve($id)
    {
        try {
            $id = $this->encrypter->decrypt(hex2bin($id));
        } catch (\Exception $e) {
            return $this->response->setJSON([
                'res'   => false,
                'msg'   => 'ID tidak valid',
                'xname' => csrf_token(),
                'xhash' => csrf_hash()
            ]);
        }

        $model = new MyModel($this->table);
        $detModel = new MyModel('simlab_t_layanan_detil');

        // Ambil semua detail yang berkaitan dengan lnKode ini
        $details = $detModel->getAllDataById(['detLnKode' => $id]);

        // Jika tidak ada detail, approve lama (set ke 4)
        if (empty($details)) {
            $newLnStatus = 4;
            $res = $model->updateData(['lnStatus' => $newLnStatus], $this->id, $id);

            return $this->response->setJSON([
                'res'   => $res,
                'lnStatusApplied' => $newLnStatus,
                'msg'   => $res ? 'Approve berhasil (tanpa detail).' : 'Gagal mengupdate status.',
                'xname' => csrf_token(),
                'xhash' => csrf_hash()
            ]);
        }

        // Kumpulkan semua nilai detStatus
        $statuses = [];
        foreach ($details as $d) {
            $statuses[] = isset($d->detStatus) ? (int)$d->detStatus : null;
        }
        $unique = array_values(array_unique($statuses, SORT_REGULAR));

        // Default new status: approve (4)
        $newLnStatus = 4;
        $msg = 'LnStatus di-set ke 4 (approved)';

        // Jika semua detStatus sama dan sama dengan 2 -> LnStatus = 2
        // Juga menangani permintaan: jika semua detStatus = 0 -> LnStatus = 2
        if (count($unique) === 1) {
            $only = $unique[0];
            if ($only === 2 || $only === 0) {
                $newLnStatus = 2;
                $msg = 'Semua detStatus = ' . $only . ' => LnStatus di-set ke 2 (ditolak).';
            }
        }

        $res = $model->updateData(['lnStatus' => $newLnStatus], $this->id, $id);

        return $this->response->setJSON([
            'res'   => $res,
            'lnStatusApplied' => $newLnStatus,
            'msg'   => $res ? $msg : 'Gagal mengupdate status.',
            'xname' => csrf_token(),
            'xhash' => csrf_hash()
        ]);
    }

    // ============================
    // ========== KERANJANG METHODS ==========
    // ============================

    public function keranjangDataListLayanan()
    {
        $model = new MyModel('simlab_r_layanan_pengujian');

        $joins = [
            'simlab_r_parameter p' => 'p.paraKode = simlab_r_layanan_pengujian.ujiParaKode',
            'simlab_r_alat a'      => 'a.alatKode = simlab_r_layanan_pengujian.ujiAlatKode'
        ];

        $select = '
            simlab_r_layanan_pengujian.ujiKode,
            simlab_r_layanan_pengujian.ujiBiaya,
            simlab_r_layanan_pengujian.ujiInstansi,
            simlab_r_layanan_pengujian.ujiDiskon,
            simlab_r_layanan_pengujian.ujiLayanan,
            p.paraNama,
            a.alatNama
        ';

        $listUji = $model->getAllDataWithJoinWhereOrder(
            $joins,
            [],
            ['ujiKode' => 'ASC'],
            $select,
            'left'
        );

        $data = array();
        foreach ($listUji as $row) {
            $response = array();

            $response[] = esc($row->paraNama);
            $response[] = esc($row->alatNama);

            $biaya = 'Rp ' . number_format($row->ujiBiaya, 0, ',', '.');
            if (!empty($row->ujiDiskon) && $row->ujiDiskon > 0) {
                $biaya .= ' <span class="text-danger fw-bold">- ' . $row->ujiDiskon . '%</span>';
            }
            $response[] = $biaya;

            $inputJumlah = '
                <input type="number" class="form-control form-control-sm text-center jumlah" value="1" min="1" style="width:100px;">
            ';
            $response[] = $inputJumlah;

            $response[] = '<input type="text" class="form-control form-control-sm keterangan" placeholder="Keterangan...">';

            $btnMasukkan = '
                <button type="button"
                        class="btn btn-success btn-sm btnMasukkan"
                        data-kode="' . esc($row->ujiKode) . '"
                        data-alat="' . esc($row->alatNama) . '"
                        data-biaya="' . $row->ujiBiaya . '"
                        data-parameter="' . esc($row->paraNama) . '"
                        data-diskon="' . ($row->ujiDiskon ?? 0) . '"
                        data-instansi="' . esc($row->ujiInstansi) . '"
                        title="Masukkan ke keranjang">
                    <i class="bi bi-cart-plus"></i>
                </button>
            ';
            $response[] = $btnMasukkan;

            $data[] = $response;
        }

        return $this->response->setJSON(['items' => $data]);
    }

    public function keranjangDatalist()
    {
        $session = session();
        $keranjang = $session->get($this->sessionKey) ?? [];
        $data = [];

        foreach ($keranjang as $idx => $row) {
            $response = [];

            $layanan    = isset($row['layanan']) ? esc($row['layanan']) : '-';
            $jumlah     = isset($row['jumlah']) ? (int)$row['jumlah'] : 0;
            $keterangan = isset($row['keterangan']) ? esc($row['keterangan']) : '';
            $diskon     = isset($row['diskon']) ? (float)$row['diskon'] : 0;

            $biayaAsli  = isset($row['biaya_asli']) ? (float)$row['biaya_asli'] : 0;
            $biayaTotal = isset($row['biaya']) ? (float)$row['biaya'] : 0;

            $response[] = '<span class="badge bg-primary">' . $layanan . '</span>';

            if ($diskon > 0) {
                $hargaDiskon = $biayaAsli - ($biayaAsli * ($diskon / 100));
                $biayaTampil = '<span style="color:red;text-decoration:line-through;">Rp ' . number_format($biayaAsli, 0, ',', '.') . '</span><br>';
                $biayaTampil .= 'Rp ' . number_format($hargaDiskon, 0, ',', '.');
            } else {
                $biayaTampil = 'Rp ' . number_format($biayaAsli, 0, ',', '.');
            }
            $response[] = $biayaTampil;

            $response[] = $jumlah;
            $response[] = $diskon > 0 ? $diskon . '%' : '-';
            $response[] = 'Rp ' . number_format($biayaTotal, 0, ',', '.');
            $response[] = $keterangan;
            $response[] = $this->aksiKeranjang($idx);

            $data[] = $response;
        }

        // MODIF: kembalikan info pelanggan yang tersimpan di session (jika ada)
        $pelanggan = $session->get($this->sessionKey . '_pelanggan') ?? null;
        if ($pelanggan && is_array($pelanggan)) {
            $pelanggan = [
                'user_id' => isset($pelanggan['user_id']) ? $pelanggan['user_id'] : null,
                'name'    => isset($pelanggan['name']) ? $pelanggan['name'] : null,
                'email'   => isset($pelanggan['email']) ? $pelanggan['email'] : null,
                'status'  => isset($pelanggan['status']) ? $pelanggan['status'] : null
            ];
        } else {
            $pelanggan = null;
        }

        return $this->response->setJSON(['items' => $data, 'pelanggan' => $pelanggan]);
    }

    public function keranjangSubmit()
    {
        $session = session();

        // MODIF: cek apakah pelanggan sudah dipilih di session
        $pelanggan = $session->get($this->sessionKey . '_pelanggan') ?? null;
        if (empty($pelanggan) || empty($pelanggan['user_id'])) {
            return $this->response->setJSON([
                'res' => false,
                'msg' => 'Pelanggan belum dipilih. Silakan pilih pelanggan di modal sebelum menambahkan layanan.',
                'xname' => csrf_token(),
                'xhash' => csrf_hash()
            ]);
        }

        $kode       = $this->request->getPost('detUjiKode');
        $alat       = $this->request->getPost('detAlat');
        $biaya      = (float)$this->request->getPost('detBiaya');
        $parameter  = $this->request->getPost('detParameter');
        $diskon     = (float)$this->request->getPost('detDiskon');
        $instansi   = $this->request->getPost('detInstansi');
        $jumlah     = (int)$this->request->getPost('detJumlah');
        $keterangan = $this->request->getPost('detKeterangan');

        if (empty($kode) || $jumlah < 1) {
            return $this->response->setJSON([
                'res' => false,
                'msg' => 'Data tidak valid.',
                'xname' => csrf_token(),
                'xhash' => csrf_hash()
            ]);
        }

        $biayaAsli = $biaya;
        $biayaTotal = ($biaya * $jumlah) * (1 - ($diskon / 100));

        $item = [
            'kode' => $kode,
            'alat' => $alat,
            'layanan' => $parameter,
            'biaya_asli' => $biayaAsli,
            'biaya' => $biayaTotal,
            'diskon' => $diskon,
            'jumlah' => $jumlah,
            'keterangan' => $keterangan,
            'ujiPenyelia' => null,
            'ujiManajerTeknis' => null
        ];

        $keranjang = $session->get($this->sessionKey) ?? [];
        $keranjang[] = $item;
        $session->set($this->sessionKey, $keranjang);

        return $this->response->setJSON([
            'res' => true,
            'msg' => 'Item berhasil ditambahkan ke keranjang.',
            'xname' => csrf_token(),
            'xhash' => csrf_hash()
        ]);
    }

    public function keranjangDelete($index = null)
    {
        $session = session();
        $keranjang = $session->get($this->sessionKey) ?? [];

        if ($index === null || !isset($keranjang[$index])) {
            return $this->response->setJSON([
                'res' => false,
                'msg' => 'Item tidak ditemukan.',
                'xname' => csrf_token(),
                'xhash' => csrf_hash()
            ]);
        }

        unset($keranjang[$index]);
        $keranjang = array_values($keranjang);
        $session->set($this->sessionKey, $keranjang);

        return $this->response->setJSON([
            'res' => true,
            'msg' => 'Item berhasil dihapus.',
            'xname' => csrf_token(),
            'xhash' => csrf_hash()
        ]);
    }

    /**
     * keranjangCheckout - sekarang menerima selectedUserId dari frontend.
     * Jika selectedUserId valid, simpan user_id dan lnAccEmail dari tabel simlab_account_users.
     * Jika tidak ada atau tidak valid: fallback ke admin@lab.local (seperti sebelumnya).
     */
    public function keranjangCheckout()
    {
        $session = session();
        $keranjang = $session->get($this->sessionKey) ?? [];

        if (empty($keranjang)) {
            return $this->response->setJSON([
                'res' => false,
                'msg' => 'Keranjang masih kosong.',
                'xname' => csrf_token(),
                'xhash' => csrf_hash()
            ]);
        }

        $totalBiaya = array_sum(array_column($keranjang, 'biaya'));

        $modelPembayaran = new MyModel('simlab_t_pembayaran');
        $modelLayanan    = new MyModel('simlab_t_layanan');
        $modelDetil      = new MyModel('simlab_t_layanan_detil');

        $db = \Config\Database::connect();
        $db->transStart();

        try {
            // email admin (fallback)
            $adminEmail = 'admin@lab.local';

            // ambil selectedUserId dari POST jika ada
            $selectedUserId = $this->request->getPost('selectedUserId');
            $selectedUserId = $selectedUserId ? (int)$selectedUserId : null;

            // MODIF: jika tidak diberikan, fallback ke session pelanggan jika ada
            if (!$selectedUserId) {
                $pelangganSession = $session->get($this->sessionKey . '_pelanggan') ?? null;
                if ($pelangganSession && !empty($pelangganSession['user_id'])) {
                    $selectedUserId = (int)$pelangganSession['user_id'];
                }
            }

            $lnAccEmailToSave = $adminEmail;
            $userIdToSave = null;

            if ($selectedUserId) {
                // coba ambil data user
                $userModel = new MyModel('simlab_account_users');
                $userData = $userModel->getDataById('user_id', $selectedUserId);
                if ($userData) {
                    $lnAccEmailToSave = $userData->user_email ?? $adminEmail;
                    $userIdToSave = $selectedUserId;
                } else {
                    // jika tidak ditemukan, tetap fallback ke admin
                    $lnAccEmailToSave = $adminEmail;
                    $userIdToSave = null;
                }
            }

            $insertLayananId = $modelLayanan->insertData([
                'user_id'       => $userIdToSave,
                'lnAccEmail'    => $lnAccEmailToSave,
                'lnNoTransaksi' => null,
                'lnTgl'         => date('Y-m-d H:i:s'),
                'lnStatus'      => 1,
                'kuisioner'     => 0
            ], true);

            if (!$insertLayananId) {
                $insertLayananId = $db->insertID();
            }

            if (!$insertLayananId) {
                $db->transRollback();
                return $this->response->setJSON([
                    'res' => false,
                    'msg' => 'Gagal membuat record layanan (lnKode tidak tersedia).',
                    'xname' => csrf_token(),
                    'xhash' => csrf_hash()
                ]);
            }

            $lnKode = (int)$insertLayananId;

            $today = date('Y-m-d');
            $insertPembayaranId = $modelPembayaran->insertData([
                'bayarLnKode'     => $lnKode,
                'bayarTotalBiaya' => $totalBiaya,
                'bayarStatus'     => 0,
                'bayarInvoiceNo'  => null,
                'bayarInvoiceTgl' => $today,
                'bayarBuktiFile'  => 'by_admin'
            ], true);

            if (!$insertPembayaranId) {
                $db->transRollback();
                return $this->response->setJSON([
                    'res' => false,
                    'msg' => 'Gagal simpan pembayaran.',
                    'xname' => csrf_token(),
                    'xhash' => csrf_hash()
                ]);
            }

            // Simpan detail
            foreach ($keranjang as $item) {
                $detil = [
                    'detLnKode'         => $lnKode,
                    'detUjiKode'        => $item['kode'] ?? null,
                    'detBiaya'          => $item['biaya'] ?? null,
                    'detJumlah'         => $item['jumlah'] ?? 1,
                    'detKeterangan'     => $item['keterangan'] ?? null,
                    'detLayanan'        => $item['layanan'] ?? null,
                    'detStatus'         => 0,
                    'detJenKode'        => null,
                    'detPenyelia'       => $item['ujiPenyelia'] ?? null,
                    'detManajerTeknis'  => $item['ujiManajerTeknis'] ?? null,
                ];

                $res = $modelDetil->insertData($detil);
                if (!$res) {
                    $error = $modelDetil->db->error();
                    log_message('error', ' Insert gagal ke simlab_t_layanan_detil. Data: ' . json_encode($detil));
                    log_message('error', ' DB Error: ' . json_encode($error));

                    $db->transRollback();

                    return $this->response->setJSON([
                        'res' => false,
                        'msg' => 'Checkout gagal saat simpan detail: ' . ($error['message'] ?? 'Unknown error'),
                        'xname' => csrf_token(),
                        'xhash' => csrf_hash()
                    ]);
                }
            }

            $db->transComplete();

            // kosongkan keranjang session
            $session->remove($this->sessionKey);
            // MODIF: hapus juga session pelanggan agar tidak tersisa setelah checkout
            $session->remove($this->sessionKey . '_pelanggan');

            return $this->response->setJSON([
                'res' => true,
                'msg' => 'Checkout berhasil!',
                'lnKode' => $lnKode,
                'xname' => csrf_token(),
                'xhash' => csrf_hash()
            ]);
        } catch (\Exception $e) {
            if ($db->transStatus() === FALSE) {
                $db->transRollback();
            }

            log_message('error', 'Checkout exception: ' . $e->getMessage() . ' Trace: ' . $e->getTraceAsString());

            return $this->response->setJSON([
                'res' => false,
                'msg' => 'Checkout gagal: ' . $e->getMessage(),
                'xname' => csrf_token(),
                'xhash' => csrf_hash()
            ]);
        }
    }

    /**
     * Endpoint baru: set pelanggan ke session (dipanggil dari modal)
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

        $session->set($this->sessionKey . '_pelanggan', [
            'user_id' => (int)$selectedUserId,
            'name' => $name ?? null,
            'email' => $email ?? null,
            'status' => $status ?? null
        ]);

        return $this->response->setJSON([
            'res' => true,
            'msg' => 'Pelanggan disimpan ke session.',
            'pelanggan' => $session->get($this->sessionKey . '_pelanggan'),
            'xname' => csrf_token(),
            'xhash' => csrf_hash()
        ]);
    }

    private function aksiKeranjang($id)
    {
        return '<div id="item-' . $id . '" class="text-center">
            <span data-index="' . $id . '" 
                class="text-danger btn-action btn-delete-item" 
                style="cursor: pointer;"
                title="Hapus" 
                onclick="keranjangDeleteItem(event)">
                <i class="bi bi-trash"></i>
            </span>
        </div>';
    }

    // ============================
    // END KERANJANG METHODS
    // ============================
}
