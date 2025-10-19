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

    public function dataList()
{
    $model = new MyModel($this->table);
    $data  = [];

    // Ambil semua data, urutkan tanggal DESC
    $list = $model->getAllDataWithOrder(['lnTgl' => 'DESC']);

    // Kelompokkan berdasarkan status
    $grouped = [];
    for ($i = 0; $i <= 9; $i++) {
        $grouped[$i] = [];
    }
    foreach ($list as $row) {
        $status = (int) $row->lnStatus;
        $grouped[$status][] = $row;
    }

    // Gabungkan urut 0 → 9
    $finalList = [];
    for ($i = 0; $i <= 9; $i++) {
        $finalList = array_merge($finalList, $grouped[$i]);
    }

    $userModel  = new MyModel('simlab_account_users');
    $layananDet = new MyModel('simlab_t_layanan_detil');

    foreach ($finalList as $row) {
        // Lewati status tertentu
        if (in_array((int)$row->lnStatus, [0, 1, 2])) continue;

        $id = bin2hex($this->encrypter->encrypt($row->lnKode));
        $response = [];

        // Ambil detail item layanan
        $detil = $layananDet->getAllDataById(['detLnKode' => $row->lnKode]);
        $items = [];
        foreach ($detil as $d) {
            $items[] = $d->detLayanan ?? $d->detJenKode;
        }

        // ================== Ambil Data User ==================
        $personName   = null;
        $userIdentity = '-';
        $instansi     = '-';
        $u            = null;

        // 1️ Cek langsung dari user_id (FK)
        if (!empty($row->user_id)) {
            $u = $userModel->getDataById('user_id', $row->user_id);
        }

        // 2️ Jika belum ada, cek berdasarkan email (lnAccEmail)
        if (!$u && !empty($row->lnAccEmail)) {
            $users = $userModel->getAllDataById(['user_email' => $row->lnAccEmail]);
            if (!empty($users)) $u = is_array($users) ? $users[0] : $users;
        }

        // 3️ Jika masih belum ketemu, cari user_id dari invoice (lnNoTransaksi)
        if (!$u && !empty($row->lnNoTransaksi)) {
            $db = \Config\Database::connect();
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

        // 4️ Jika user ditemukan, ambil info
        if ($u) {
            $personName   = $u->user_name ?? $u->user_email ?? '-';
            $instansi     = $u->user_instansi ?? '-';
            $userIdentity = $u->user_identity ?? '-';
        } else {
            // fallback kalau gak ada user
            $personName = $row->lnAccEmail ?? '-';
        }
        // =====================================================

        // Kolom tabel
        $response[] = !empty($row->lnTgl) ? date('d-m-Y H:i', strtotime($row->lnTgl)) : '-'; // Tanggal
        $response[] = $personName;       // Nama
        $response[] = $userIdentity;     // Identitas (ULM / NON ULM)
        $response[] = $this->formatStatus($row->lnStatus); // Status

        // Tombol lihat detail
        $lihatDetailBtn = '<button type="button" class="btn btn-sm btn-info" 
                            title="Lihat Detail Item Layanan" 
                            onclick="loadDetail(\'' . $id . '\')">
                            <i class="bi bi-eye"></i> Lihat Detail Layanan</button>';
        $response[] = $lihatDetailBtn;

        // Tombol aksi (approve / hapus)
        $response[] = $this->aksi($id, $row->lnStatus);

        $data[] = $response;
    }

    return $this->response->setJSON(['items' => $data]);
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

        $db = \Config\Database::connect();
        $builder = $db->table('simlab_t_layanan_detil as d');

        $builder->select("
            d.detUjiKode,
            d.detLnKode,
            d.detLayanan,
            d.detJenKode,
            GROUP_CONCAT(DISTINCT d.detKeterangan SEPARATOR ' | ') AS detKet,
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
            $response[] = $row->detKet ?? '';

            // Ambil status grouping (0/1/atau lainnya)
            $statusGroup = isset($row->detStatusGroup) ? (int)$row->detStatusGroup : null;

            if ($statusGroup === 0) {
                $statusHtml = '<span class="badge bg-danger">Ditolak</span>';
            } elseif ($statusGroup === 1) {
                $statusHtml = '<span class="badge bg-success">Diterima</span>';
            } else {
                $statusHtml = '<span class="badge bg-secondary">Belum Diproses</span>';
            }
            $response[] = $statusHtml;

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
            case 1: return '<span class="badge bg-warning">Sedang diulas</span>';
            case 2: return '<span class="badge bg-danger">Ditolak</span>';
            case 3: return '<span class="badge bg-info">Sedang diulas</span>';
            case 4: return '<span class="badge bg-primary">Dalam Pengujian</span>';
            case 5: return '<span class="badge bg-primary">Memproses LHUS</span>';
            case 6: return '<span class="badge bg-success">LHUS Disetujui</span>';
            case 7: return '<span class="badge bg-primary">Memproses LHU</span>';
            case 8: return '<span class="badge bg-success">LHU Disetujui</span>';
            case 9: return '<span class="badge bg-dark">Pengujian Selesai</span>';
            default: return '<span class="badge bg-dark">Unknown</span>';
        }
    }

    // untuk aksi approve Admin (dari 3 -> 4)
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
        $res = $model->updateData(['lnStatus' => 4], $this->id, $id);

        return $this->response->setJSON([
            'res'   => $res,
            'xname' => csrf_token(),
            'xhash' => csrf_hash()
        ]);
    }
}
