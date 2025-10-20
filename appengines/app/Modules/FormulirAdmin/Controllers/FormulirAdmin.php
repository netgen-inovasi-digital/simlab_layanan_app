<?php

namespace Modules\FormulirAdmin\Controllers;

use App\Controllers\BaseController;
use App\Models\MyModel;

class FormulirAdmin extends BaseController
{
    private $table = 'simlab_t_layanan';
    private $id    = 'lnKode';

    public function index()
    {
        $session = session();
        $user_id = $session->get('id_user');

        $modelUser = new MyModel('simlab_account_users');

        $data = [
            'title' => 'Data Formulir Admin',
            'user'  => $modelUser->getDataById('user_id', $user_id),
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
    // contoh: ?kategoriLayanan=all  atau ?kategoriLayanan=3  atau ?kategoriLayanan=3,4,5
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

        // jika perlu gunakan $row->detUjiKode dan $encLnId untuk tombol/aksi
        $ujiKodeInt = (int)$row->detUjiKode;
        $encLnForBtn = $encLnId;

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

}
