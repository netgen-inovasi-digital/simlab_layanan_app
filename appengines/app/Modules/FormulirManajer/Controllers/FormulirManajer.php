<?php  

namespace Modules\FormulirManajer\Controllers;

use App\Controllers\BaseController;
use App\Models\MyModel;

class FormulirManajer extends BaseController
{
    private $table = 'simlab_t_layanan';
    private $id    = 'lnKode';

    public function index()
    {
        $session = session();
        $user_id = $session->get('id_user');
        
        $modelUser = new MyModel('simlab_account_users');

        $data = [
            'title' => 'Data Formulir Manajer',
            'user'  => $modelUser->getDataById('user_id', $user_id),
        ];

        return view('Modules\FormulirManajer\Views\v_formulirManajer', $data);
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
        $session = session();
        $user_id = $session->get('id_user');

        $idenc = $this->request->getPost('id');
        $data  = [
            'user_id'    => $user_id ?? null,
            'lnAccEmail' => $this->request->getPost('lnOrangEmail') ?? $this->request->getPost('lnAccEmail') ?? null,
            'lnTgl'      => $this->request->getPost('lnTgl') ?? date('Y-m-d H:i:s'),
        ];

        $lnOrangNama  = $this->request->getPost('lnOrangNama');
        $lnInstansi   = $this->request->getPost('lnInstansi');
        $lnOrangTelp  = $this->request->getPost('lnOrangTelp');
        $lnOrangEmail = $this->request->getPost('lnOrangEmail');
        $lnTipe       = $this->request->getPost('lnTipe');

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
        $session = session();
        $user_id = $session->get('id_user');

        $model = new MyModel($this->table);
        $modelDet = new MyModel('simlab_t_layanan_detil');

        $data  = [];

        $detilList = $modelDet->getAllDataById(['detManajerTeknis' => $user_id]);

        if (empty($detilList)) {
            $db = \Config\Database::connect();
            $builder = $db->table('simlab_t_layanan_detil as d');
            $builder->select('d.detLnKode');
            $builder->groupStart();
            $builder->where('d.detPenyelia', $user_id);
            $builder->orWhere('d.detManajerTeknis', $user_id);
            $builder->groupEnd();
            $rows = $builder->get()->getResult();
            $detilList = $rows;
        }

        $lnKodeList = [];
        foreach ($detilList as $item) {
            if (is_array($item) && isset($item['detLnKode'])) {
                $lnKodeList[] = $item['detLnKode'];
            } elseif (is_object($item) && isset($item->detLnKode)) {
                $lnKodeList[] = $item->detLnKode;
            }
        }

        $lnKodeList = array_values(array_unique(array_filter($lnKodeList, function ($v) {
            return $v !== null && $v !== '' && $v !== 0;
        })));

        if (empty($lnKodeList)) {
            return $this->response->setJSON(['items' => []]);
        }

        $db = \Config\Database::connect();
        $builder = $db->table('simlab_t_layanan as l');

        $builder->select('l.*, u.user_name as pemesan_name, u.user_email as pemesan_email, u.user_identity as pemesan_identity');
        $builder->join('simlab_account_users as u', 'u.user_id = l.user_id', 'left');
        $builder->whereIn('l.lnKode', $lnKodeList);

        $builder->where('l.lnStatus !=', 2);

        $builder->orderBy('l.lnTgl', 'DESC');
        $list = $builder->get()->getResult();

        $grouped = [];
        for ($i = 0; $i <= 9; $i++) {
            $grouped[$i] = [];
        }

        foreach ($list as $row) {
            $status = (int) $row->lnStatus;
            if (!isset($grouped[$status])) {
                $grouped[$status] = [];
            }
            $grouped[$status][] = $row;
        }

        $finalList = [];
        for ($i = 0; $i <= 9; $i++) {
            $finalList = array_merge($finalList, $grouped[$i]);
        }

        foreach ($finalList as $row) {
            if ((int)$row->lnStatus === 0) {
                continue;
            }
            $id = bin2hex($this->encrypter->encrypt($row->lnKode));
            $response = [];

            $response[] = !empty($row->lnTgl) ? date('d-m-Y H:i', strtotime($row->lnTgl)) : '-';

            $pemesanNama = !empty($row->pemesan_name) ? $row->pemesan_name : '-';
            $response[] = $pemesanNama;

            $tipe = !empty($row->pemesan_identity) ? $row->pemesan_identity : '-';
            $response[] = $tipe;

            $response[] = $this->formatStatus($row->lnStatus);

            $response[] = '<a href="javascript:void(0)" onclick="loadDetail(\'' . $id . '\')" 
                            class="btn btn-sm btn-info">
                            <i class="bi bi-eye"></i> Lihat Detail
                        </a>';

            $response[] = $this->aksi($id, $row->lnStatus);

            $data[] = $response;
        }

        $output = ["items" => $data];
        return $this->response->setJSON($output);
    }

    /**
     * detailList - diperbaiki:
     * - jangan pakai addslashes untuk detUjiKode; tanamkan sebagai integer ke atribut data-uji
     * - pastikan respon JSON berisi elemen aksi yang memanggil JS yang mengirim ln (hex) dan uji (int)
     */
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

            // Aksi: gunakan span dengan atribut data-ln (hex) dan data-uji (integer, tidak di-escape)
            $ujiKodeInt = (int)$row->detUjiKode;
            $encLnForBtn = $encLnId;

            $aksiHtml = '<div class="d-flex justify-content-center gap-2 align-items-center">';
            // ikon & kelas sama seperti tabel utama; onclick langsung memanggil JS (tanpa konfirmasi)
            $aksiHtml .= '<span class="text-success btn-action" title="Setujui" data-ln="' . $encLnForBtn . '" data-uji="' . $ujiKodeInt . '" onclick="confirmApproveDetail(event)"><i class="bi bi-check-circle"></i></span> ';
            $aksiHtml .= '<span class="text-warning btn-action" title="Tolak" data-ln="' . $encLnForBtn . '" data-uji="' . $ujiKodeInt . '" onclick="confirmRejectDetail(event)"><i class="bi bi-x-circle"></i></span>';
            $aksiHtml .= '</div>';

            $response[] = $aksiHtml;

            $data[] = $response;
        }

        return $this->response->setJSON(['items' => $data]);
    }

    private function aksi($id, $status)
    {
        $btn = '<div id="' . $id . '" class="float-end">';
        if ($status == 1) { // saat In Review
            $btn .= '<span class="text-success btn-action" title="Setujui" onclick="confirmApprove(event)">
                        <i class="bi bi-check-circle"></i></span> ';
            $btn .= '<span class="text-warning btn-action" title="Tolak" onclick="confirmReject(event)">
                        <i class="bi bi-x-circle"></i></span> ';
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
            case 3: return '<span class="badge bg-info">Sedang diulas (admin)</span>';
            case 4: return '<span class="badge bg-primary">Proses Pengujian</span>';
            case 5: return '<span class="badge bg-primary">Memproses LHUS</span>';
            case 6: return '<span class="badge bg-success">LHUS Disetujui</span>';
            case 7: return '<span class="badge bg-primary">Memproses LHU</span>';
            case 8: return '<span class="badge bg-success">LHU Disetujui</span>';
            case 9: return '<span class="badge bg-dark">Pengujian Selesai</span>';
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
        $res = $model->updateData(['lnStatus' => 3], $this->id, $id);

        return $this->response->setJSON([
            'res'   => $res,
            'xname' => csrf_token(),
            'xhash' => csrf_hash()
        ]);
    }

    public function reject($id)
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
        $res = $model->updateData(['lnStatus' => 2], $this->id, $id);

        return $this->response->setJSON([
            'res'   => $res,
            'xname' => csrf_token(),
            'xhash' => csrf_hash()
        ]);
    }

    /**
     * approveDetail: VALIDATED and return affected rows
     */
    public function approveDetail()
{
    $lnEnc = $this->request->getPost('ln');
    $ujiRaw = $this->request->getPost('uji');

    if (empty($lnEnc) || $ujiRaw === null) {
        return $this->response->setJSON([
            'res' => false,
            'affected' => 0,
            'msg' => 'Parameter tidak lengkap',
            'xname' => csrf_token(),
            'xhash' => csrf_hash()
        ]);
    }

    $uji = (int)$ujiRaw;

    try {
        $lnId = $this->encrypter->decrypt(hex2bin($lnEnc));
    } catch (\Exception $e) {
        return $this->response->setJSON([
            'res' => false,
            'affected' => 0,
            'msg' => 'ID layanan tidak valid',
            'xname' => csrf_token(),
            'xhash' => csrf_hash()
        ]);
    }

    $db = \Config\Database::connect();
    $builder = $db->table('simlab_t_layanan_detil');

    // cek dulu ada berapa baris yang match WHERE
    $builder->where('detLnKode', $lnId);
    $builder->where('detUjiKode', $uji);
    $count = $builder->countAllResults(false); // false: jangan reset query (CI4 quirk)

    if ($count == 0) {
        return $this->response->setJSON([
            'res' => false,
            'affected' => 0,
            'msg' => 'No matching detail rows found',
            'xname' => csrf_token(),
            'xhash' => csrf_hash()
        ]);
    }

    // lakukan update (buat query baru)
    $res = $db->table('simlab_t_layanan_detil')
             ->where('detLnKode', $lnId)
             ->where('detUjiKode', $uji)
             ->update(['detStatus' => 1]);

    $affected = $db->affectedRows();

    return $this->response->setJSON([
        'res'      => (bool)$res && $affected > 0,
        'affected' => $affected,
        'msg'      => $affected > 0 ? 'OK' : 'No rows updated',
        'xname'    => csrf_token(),
        'xhash'    => csrf_hash()
    ]);
}

public function rejectDetail()
{
    $lnEnc = $this->request->getPost('ln');
    $ujiRaw = $this->request->getPost('uji');

    if (empty($lnEnc) || $ujiRaw === null) {
        return $this->response->setJSON([
            'res' => false,
            'affected' => 0,
            'msg' => 'Parameter tidak lengkap',
            'xname' => csrf_token(),
            'xhash' => csrf_hash()
        ]);
    }

    $uji = (int)$ujiRaw;

    try {
        $lnId = $this->encrypter->decrypt(hex2bin($lnEnc));
    } catch (\Exception $e) {
        return $this->response->setJSON([
            'res' => false,
            'affected' => 0,
            'msg' => 'ID layanan tidak valid',
            'xname' => csrf_token(),
            'xhash' => csrf_hash()
        ]);
    }

    $db = \Config\Database::connect();
    $builder = $db->table('simlab_t_layanan_detil');

    // cek dulu ada berapa baris yang match WHERE
    $builder->where('detLnKode', $lnId);
    $builder->where('detUjiKode', $uji);
    $count = $builder->countAllResults(false);

    if ($count == 0) {
        return $this->response->setJSON([
            'res' => false,
            'affected' => 0,
            'msg' => 'No matching detail rows found',
            'xname' => csrf_token(),
            'xhash' => csrf_hash()
        ]);
    }

    $res = $db->table('simlab_t_layanan_detil')
             ->where('detLnKode', $lnId)
             ->where('detUjiKode', $uji)
             ->update(['detStatus' => 0]);

    $affected = $db->affectedRows();

    return $this->response->setJSON([
        'res'      => (bool)$res && $affected > 0,
        'affected' => $affected,
        'msg'      => $affected > 0 ? 'OK' : 'No rows updated',
        'xname'    => csrf_token(),
        'xhash'    => csrf_hash()
    ]);
}

}
