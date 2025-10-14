<?php  

namespace Modules\FormulirManajer\Controllers;

use App\Controllers\BaseController;
use App\Models\MyModel;

class FormulirManajer extends BaseController
{
    private $table = 'simlab_t_layanan';
    private $id    = 'lnKode';

    public function __construct()
    {
        $this->encrypter = \Config\Services::encrypter();
    }

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

    $session = session();
    $user_id = $session->get('id_user');

    try {
        $kode = $this->encrypter->decrypt(hex2bin($id));
    } catch (\Exception $e) {
        return $this->response->setJSON(['items' => []]);
    }

    // pastikan user session adalah detManajerTeknis untuk ln ini
    $db = \Config\Database::connect();
    $checkBuilder = $db->table('simlab_t_layanan_detil as d');
    $checkBuilder->select('1');
    $checkBuilder->where('d.detLnKode', $kode);
    $checkBuilder->where('d.detManajerTeknis', $user_id);
    $exists = $checkBuilder->limit(1)->get()->getRow();

    if (!$exists) {
        // tidak diizinkan / tidak ada data untuk manajer teknis ini
        return $this->response->setJSON(['items' => []]);
    }

    // Encrypted hex parent (dipakai untuk tombol aksi)
    $encLnId = bin2hex($this->encrypter->encrypt($kode));

    $builder = $db->table('simlab_t_layanan_detil as d');

    // Ambil hanya baris yang milik detManajerTeknis = session user dan untuk ln yang diminta
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
    $builder->where('d.detManajerTeknis', $user_id);
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

    $session = session();
    $user_id = $session->get('id_user');

    $db = \Config\Database::connect();
    $model = new MyModel($this->table);

    // mulai transaksi agar konsisten
    $db->transStart();

    // 1) Update ONLY the detail rows that are shown in detailList for this session user
    // That is: detLnKode = $id AND detManajerTeknis = $user_id
    // Set detAccLn = 1 (only change if not already 1 to reduce churn)
    $detBuilder = $db->table('simlab_t_layanan_detil');
    $detBuilder->where('detLnKode', $id);
    $detBuilder->where('detManajerTeknis', $user_id);
    $detBuilder->where('(detAccLn IS NULL OR detAccLn != 1)');
    $resDetUpdate = $detBuilder->update(['detAccLn' => 1]);

    // ambil jumlah baris yang benar-benar berubah oleh update di atas
    $detAffected = $db->affectedRows();

    // 2) Hitung keseluruhan detail untuk ln ini, dan hitung berapa yang sudah detAccLn = 1
    $totalDetails = (int) $db->table('simlab_t_layanan_detil')
        ->where('detLnKode', $id)
        ->countAllResults();

    $accCount = (int) $db->table('simlab_t_layanan_detil')
        ->where('detLnKode', $id)
        ->where('detAccLn', 1)
        ->countAllResults();

    // 3) Jika semua detail sudah di-acc (total > 0 dan accCount == total), maka update parent lnStatus = 3
    $parentUpdated = false;
    if ($totalDetails > 0 && $accCount === $totalDetails) {
        $resParent = $model->updateData(['lnStatus' => 3], $this->id, $id);
        // treat successful update if model returns true or 1
        $parentUpdated = ($resParent === true || $resParent === 1);
    }

    $db->transComplete();

    if ($db->transStatus() === false) {
        return $this->response->setJSON([
            'res'   => false,
            'msg'   => 'Transaksi gagal saat proses approve',
            'xname' => csrf_token(),
            'xhash' => csrf_hash()
        ]);
    }

    return $this->response->setJSON([
        'res'            => true,
        'det_affected'   => $detAffected,
        'total_details'  => $totalDetails,
        'acc_count'      => $accCount,
        'parent_updated' => $parentUpdated,
        'msg'            => $parentUpdated ? 'Parent diapprove karena semua detail telah di-acc' : 'Detail di-acc (parent tidak diubah, belum semua detail acc)',
        'xname'          => csrf_token(),
        'xhash'          => csrf_hash()
    ]);
}


   /**
 * approveDetail: set detStatus = 1 (diterima)
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
    $table = $db->table('simlab_t_layanan_detil');

    // total rows matching ln + uji
    $table->where('detLnKode', $lnId);
    $table->where('detUjiKode', $uji);
    $total = (int) $table->countAllResults(false);

    if ($total === 0) {
        return $this->response->setJSON([
            'res' => false,
            'affected' => 0,
            'msg' => 'No matching detail rows found',
            'xname' => csrf_token(),
            'xhash' => csrf_hash()
        ]);
    }

    // count how many already have detStatus = 1
    $table->where('detLnKode', $lnId);
    $table->where('detUjiKode', $uji);
    $table->where('detStatus', 1);
    $already = (int) $table->countAllResults(false);

    if ($already === $total) {
        // semua sudah disetujui: treat as success but affected = 0
        return $this->response->setJSON([
            'res' => true,
            'affected' => 0,
            'msg' => 'Sudah disetujui',
            'xname' => csrf_token(),
            'xhash' => csrf_hash()
        ]);
    }

    // update only rows that are not yet 1 to avoid no-op updates
    $res = $db->table('simlab_t_layanan_detil')
             ->where('detLnKode', $lnId)
             ->where('detUjiKode', $uji)
             ->where('(detStatus IS NULL OR detStatus != 1)')
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

/**
 * rejectDetail: set detStatus = 0 (ditolak)
 */
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
    $table = $db->table('simlab_t_layanan_detil');

    // total rows matching ln + uji
    $table->where('detLnKode', $lnId);
    $table->where('detUjiKode', $uji);
    $total = (int) $table->countAllResults(false);

    if ($total === 0) {
        return $this->response->setJSON([
            'res' => false,
            'affected' => 0,
            'msg' => 'No matching detail rows found',
            'xname' => csrf_token(),
            'xhash' => csrf_hash()
        ]);
    }

    // count how many already have detStatus = 0
    $table->where('detLnKode', $lnId);
    $table->where('detUjiKode', $uji);
    $table->where('detStatus', 0);
    $already = (int) $table->countAllResults(false);

    if ($already === $total) {
        // semua sudah ditolak: treat as success but affected = 0
        return $this->response->setJSON([
            'res' => true,
            'affected' => 0,
            'msg' => 'Sudah ditolak',
            'xname' => csrf_token(),
            'xhash' => csrf_hash()
        ]);
    }

    // update only rows that are not yet 0
    $res = $db->table('simlab_t_layanan_detil')
             ->where('detLnKode', $lnId)
             ->where('detUjiKode', $uji)
             ->where('(detStatus IS NULL OR detStatus != 0)')
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
