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
        helper('form');
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

    public function datalist()
{
    $session = session();
    $user_id = $session->get('id_user');

    $model = new MyModel($this->table);
    $modelDet = new MyModel('simlab_t_layanan_detil');

    $data  = [];

    // dapatkan daftar detil untuk manajer teknis ini
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

    // ambil parent list, langsung order by tanggal terbaru
    $db = \Config\Database::connect();
    $builder = $db->table('simlab_t_layanan as l');

    $builder->select('l.*, u.user_name as pemesan_name, u.user_email as pemesan_email, u.user_identity as pemesan_identity');
    $builder->join('simlab_account_users as u', 'u.user_id = l.user_id', 'left');
    $builder->whereIn('l.lnKode', $lnKodeList);

    // exclude lnStatus = 2 (Ditolak) seperti semula
    $builder->where('l.lnStatus !=', 2);

    // urutkan berdasarkan tanggal terbaru
    $builder->orderBy('l.lnTgl', 'DESC');
    $list = $builder->get()->getResult();

    // langsung iterasi list sesuai urutan tanggal (baru -> lama)
    foreach ($list as $row) {
        // lewati status 0 sesuai kode awal
        if ((int)$row->lnStatus === 0) {
            continue;
        }

        $id = bin2hex($this->encrypter->encrypt($row->lnKode));
        $response = [];

        $pemesanNama = !empty($row->pemesan_name) ? $row->pemesan_name : '-';
        $tipe = !empty($row->pemesan_identity) ? $row->pemesan_identity : '-';
        $tanggal = !empty($row->lnTgl) ? date('d-m-Y H:i', strtotime($row->lnTgl)) : '-';

        $combined = '
            <div style="line-height:1.3;">
                <span style="font-size:1rem; font-weight:600;">' . esc($pemesanNama) . '</span><br>
                <span style="font-size:0.9rem; color:#555;">' . esc($tanggal) . ' | ' . esc($tipe) . '</span>
            </div>';
        $response[] = $combined;

        $response[] = $this->formatStatus($row->lnStatus);

        $response[] = '<a href="javascript:void(0)" onclick="loadDetail(\'' . $id . '\')" 
                        class="btn btn-sm btn-info">
                        <i class="bi bi-gear"></i> Review Layanan
                    </a>';

        $data[] = $response;
    }

    $output = ["items" => $data];
    return $this->response->setJSON($output);
}


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

    // cek user sebagai manajer teknis untuk Ln ini
    $db = \Config\Database::connect();
    $checkBuilder = $db->table('simlab_t_layanan_detil as d');
    $checkBuilder->select('1');
    $checkBuilder->where('d.detLnKode', $kode);
    $checkBuilder->where('d.detManajerTeknis', $user_id);
    $exists = $checkBuilder->limit(1)->get()->getRow();

    if (!$exists) {
        return $this->response->setJSON(['items' => []]);
    }

    // Encrypted ln dipakai di tombol & save
    $encLnId = bin2hex($this->encrypter->encrypt($kode));

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
    $builder->where('d.detManajerTeknis', $user_id);
    $builder->groupBy('d.detUjiKode, d.detLnKode, d.detLayanan, d.detJenKode');
    $rows = $builder->get()->getResult();

    $data = [];
    $no = 1;

    foreach ($rows as $row) {
        $response = [];
        $response[] = $no++;
        $response[] = $row->detLayanan ?? '-';
        $response[] = isset($row->jumlah) ? (int)$row->jumlah : 0;
        $response[] = '<div 
                    style="display:block; max-width:240px; min-width:160px; width:100%;
                        max-height:120px; min-height:48px; overflow-y:auto; overflow-x:hidden;
                        padding:4px 6px; border:1px solid #ddd; border-radius:4px; background:#f9f9f9;
                        white-space:pre-wrap; word-break:break-word; font-size:0.9rem;">'
                    . htmlspecialchars($row->detKet ?? '', ENT_QUOTES, 'UTF-8') .
                    '</div>';

        // status grouping
        $statusGroup = isset($row->detStatusGroup) ? (int)$row->detStatusGroup : null;
        if ($statusGroup === 2) {
            $statusHtml = '<span class="badge bg-danger">Ditolak</span>';
        } elseif ($statusGroup === 1) {
            $statusHtml = '<span class="badge bg-success">Diterima</span>';
        } elseif ($statusGroup === 0) {
            $statusHtml = '<span class="badge bg-secondary">Pending</span>';
        } else {
            $statusHtml = '<span class="badge bg-secondary">Belum Diproses</span>';
        }
        $response[] = $statusHtml;

        // aksi approve/reject 
        $ujiKodeInt = (int)$row->detUjiKode;
        $encLnForBtn = $encLnId;

        
        // komentar per-layanan: textarea multiline, data-uji untuk identifikasi
        $komentarVal = $row->detKetLn !== null ? esc($row->detKetLn) : '';
        $textarea = '<textarea class="form-control komentar-input" data-uji="' . $ujiKodeInt . '" rows="2" placeholder="Keterangan/manajer..."'
        . ' style="max-width:240px; min-width:160px; max-height:120px; min-height:48px; overflow-y:auto; overflow-x:hidden; resize:vertical; white-space:pre-wrap; word-break:break-word;">'
        . $komentarVal .
        '</textarea>';
        
        $response[] = $textarea;
        
        $aksiHtml = '<div class="d-flex justify-content-center gap-2 align-items-center">';
        $aksiHtml .= '<span class="text-success btn-action" title="Setujui" data-ln="' . $encLnForBtn . '" data-uji="' . $ujiKodeInt . '" onclick="confirmApproveDetail(event)"><i class="bi bi-check-circle"></i></span> ';
        $aksiHtml .= '<span class="text-warning btn-action" title="Tolak" data-ln="' . $encLnForBtn . '" data-uji="' . $ujiKodeInt . '" onclick="confirmRejectDetail(event)"><i class="bi bi-x-circle"></i></span>';
        $aksiHtml .= '</div>';
        $response[] = $aksiHtml;

        $data[] = $response;
    }

    return $this->response->setJSON(['items' => $data, 'encLn' => $encLnId]);
}

public function saveKomentar()
{
    $raw = file_get_contents('php://input');
    $input = json_decode($raw, true);

    if (!$input || !isset($input['lnId']) || !isset($input['items']) || !is_array($input['items'])) {
        return $this->response->setJSON([
            'res' => false,
            'msg' => 'Payload tidak lengkap',
            'xname' => csrf_token(),
            'xhash' => csrf_hash()
        ]);
    }

    try {
        $lnKode = $this->encrypter->decrypt(hex2bin($input['lnId']));
        $lnKode = (int)$lnKode;
    } catch (\Exception $e) {
        return $this->response->setJSON([
            'res' => false,
            'msg' => 'LN ID tidak valid',
            'xname' => csrf_token(),
            'xhash' => csrf_hash()
        ]);
    }

    $session = session();
    $user_id = $session->get('id_user');

    $db = \Config\Database::connect();
    $builder = $db->table('simlab_t_layanan_detil');

    // Otorisasi singkat, user adalah manager teknis untuk minimal 1 baris LN ini
    $check = (int)$db->table('simlab_t_layanan_detil')
        ->where('detLnKode', $lnKode)
        ->where('detManajerTeknis', $user_id)
        ->limit(1)
        ->countAllResults(false);

    if ($check === 0) {
        return $this->response->setJSON([
            'res' => false,
            'msg' => 'Anda tidak berwenang mengubah komentar pada layanan ini',
            'xname' => csrf_token(),
            'xhash' => csrf_hash()
        ]);
    }

    $db->transStart();
    foreach ($input['items'] as $it) {
        $uji = isset($it['ujiKode']) ? (int)$it['ujiKode'] : null;
        $kom = isset($it['komentar']) ? $it['komentar'] : null;
        if ($uji === null) continue;

        // update semua baris yang sesuai detLnKode + detUjiKode
        $builder->where('detLnKode', $lnKode)
                ->where('detUjiKode', $uji)
                ->update(['detKetLn' => $kom]);
    }
    $db->transComplete();

    $ok = $db->transStatus();

    return $this->response->setJSON([
        'res' => $ok,
        'msg' => $ok ? 'Komentar berhasil disimpan' : 'Gagal menyimpan komentar',
        'xname' => csrf_token(),
        'xhash' => csrf_hash()
    ]);
}



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

        // brpa banyak detStatus = 1 (sudah diterima)
        $table->where('detLnKode', $lnId);
        $table->where('detUjiKode', $uji);
        $table->where('detStatus', 1);
        $already = (int) $table->countAllResults(false);

        if ($already === $total) {
            // semua sudah disetujui: success, affected = 0
            // juga cek apakah seluruh layanan pada ln ini sudah diproses (tidak ada detStatus = 0/NULL)
            $pendingBuilder = $db->table('simlab_t_layanan_detil');
            $pendingBuilder->where('detLnKode', $lnId);
            $pendingBuilder->groupStart()
                            ->where('detStatus', 0)
                            ->orWhere('detStatus IS NULL', null, false)
                         ->groupEnd();
            $pendingRemaining = (int) $pendingBuilder->countAllResults(false);

            $parentUpdated = false;
            if ($pendingRemaining === 0) {
                // update parent
                $model = new MyModel($this->table);
                $resParent = $model->updateData(['lnStatus' => 3], $this->id, $lnId);
                $parentUpdated = ($resParent === true || $resParent === 1);
            }

            return $this->response->setJSON([
                'res' => true,
                'affected' => 0,
                'msg' => 'Sudah disetujui',
                'parent_updated' => $parentUpdated,
                'xname' => csrf_token(),
                'xhash' => csrf_hash()
            ]);
        }

        // update hanya row bkn 1
        $res = $db->table('simlab_t_layanan_detil')
                ->where('detLnKode', $lnId)
                ->where('detUjiKode', $uji)
                ->where('(detStatus IS NULL OR detStatus != 1)')
                ->update(['detStatus' => 1]);

        $affected = $db->affectedRows();

        // Setelah update: cek apakah seluruh layanan pada LN ini sudah tidak pending (detStatus != 0/null)
        $parentUpdated = false;
        if ($affected > 0) {
            // hitung sisa pending pada ln
            $pendingBuilder = $db->table('simlab_t_layanan_detil');
            $pendingBuilder->where('detLnKode', $lnId);
            $pendingBuilder->groupStart()
                            ->where('detStatus', 0)
                            ->orWhere('detStatus IS NULL', null, false)
                         ->groupEnd();
            $pendingRemaining = (int) $pendingBuilder->countAllResults(false);

            if ($pendingRemaining === 0) {
                // semua layanan pada ln ini sudah diproses (baik disetujui atau ditolak) -> update parent lnStatus = 3
                $model = new MyModel($this->table);
                $resParent = $model->updateData(['lnStatus' => 3], $this->id, $lnId);
                $parentUpdated = ($resParent === true || $resParent === 1);
            }
        }

        return $this->response->setJSON([
            'res'      => (bool)$res && $affected > 0,
            'affected' => $affected,
            'msg'      => $affected > 0 ? 'OK' : 'No rows updated',
            'parent_updated' => $parentUpdated,
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

        // brpa banyak detStatus = 2 (sudah ditolak)
        $table->where('detLnKode', $lnId);
        $table->where('detUjiKode', $uji);
        $table->where('detStatus', 2);
        $already = (int) $table->countAllResults(false);

        if ($already === $total) {
            // semua sudah ditolak: success, affected = 0
            // cek apakah seluruh layanan pada ln ini sudah diproses (tidak ada detStatus = 0/NULL)
            $pendingBuilder = $db->table('simlab_t_layanan_detil');
            $pendingBuilder->where('detLnKode', $lnId);
            $pendingBuilder->groupStart()
                            ->where('detStatus', 0)
                            ->orWhere('detStatus IS NULL', null, false)
                         ->groupEnd();
            $pendingRemaining = (int) $pendingBuilder->countAllResults(false);

            $parentUpdated = false;
            if ($pendingRemaining === 0) {
                // update parent
                $model = new MyModel($this->table);
                $resParent = $model->updateData(['lnStatus' => 3], $this->id, $lnId);
                $parentUpdated = ($resParent === true || $resParent === 1);
            }

            return $this->response->setJSON([
                'res' => true,
                'affected' => 0,
                'msg' => 'Sudah ditolak',
                'parent_updated' => $parentUpdated,
                'xname' => csrf_token(),
                'xhash' => csrf_hash()
            ]);
        }

        // update hanya row bkn 2
        $res = $db->table('simlab_t_layanan_detil')
                ->where('detLnKode', $lnId)
                ->where('detUjiKode', $uji)
                ->where('(detStatus IS NULL OR detStatus != 2)')
                ->update(['detStatus' => 2]);

        $affected = $db->affectedRows();

        // Setelah update: cek apakah seluruh layanan pada LN ini sudah tidak pending (detStatus != 0/null)
        $parentUpdated = false;
        if ($affected > 0) {
            $pendingBuilder = $db->table('simlab_t_layanan_detil');
            $pendingBuilder->where('detLnKode', $lnId);
            $pendingBuilder->groupStart()
                            ->where('detStatus', 0)
                            ->orWhere('detStatus IS NULL', null, false)
                         ->groupEnd();
            $pendingRemaining = (int) $pendingBuilder->countAllResults(false);

            if ($pendingRemaining === 0) {
                // update parent lnStatus = 3
                $model = new MyModel($this->table);
                $resParent = $model->updateData(['lnStatus' => 3], $this->id, $lnId);
                $parentUpdated = ($resParent === true || $resParent === 1);
            }
        }

        return $this->response->setJSON([
            'res'      => (bool)$res && $affected > 0,
            'affected' => $affected,
            'msg'      => $affected > 0 ? 'OK' : 'No rows updated',
            'parent_updated' => $parentUpdated,
            'xname'    => csrf_token(),
            'xhash'    => csrf_hash()
        ]);
    }

    public function kirim()
{
    $lnEnc = $this->request->getPost('ln');

    if (empty($lnEnc)) {
        return $this->response->setJSON([
            'res' => false,
            'msg' => 'Parameter ln tidak ditemukan',
            'xname' => csrf_token(),
            'xhash' => csrf_hash()
        ]);
    }

    try {
        $lnId = $this->encrypter->decrypt(hex2bin($lnEnc));
    } catch (\Exception $e) {
        return $this->response->setJSON([
            'res' => false,
            'msg' => 'ID layanan tidak valid',
            'xname' => csrf_token(),
            'xhash' => csrf_hash()
        ]);
    }

    $session = session();
    $user_id = $session->get('id_user');

    $db = \Config\Database::connect();

    // Otorisasi: user harus menjadi detManajerTeknis di minimal 1 baris LN ini
    $check = (int) $db->table('simlab_t_layanan_detil')
                ->where('detLnKode', $lnId)
                ->where('detManajerTeknis', $user_id)
                ->limit(1)
                ->countAllResults(false);

    if ($check === 0) {
        return $this->response->setJSON([
            'res' => false,
            'msg' => 'Anda tidak memiliki otorisasi untuk mengirim layanan ini',
            'xname' => csrf_token(),
            'xhash' => csrf_hash()
        ]);
    }

    // 1) manajer saat ini sudah tidak memiliki item pending
    $pendingManagerCount = (int) $db->table('simlab_t_layanan_detil')
        ->where('detLnKode', $lnId)
        ->where('detManajerTeknis', $user_id)
        ->groupStart()
            ->where('detStatus', 0)
            ->orWhere('detStatus IS NULL', null, false)
        ->groupEnd()
        ->countAllResults(false);

    if ($pendingManagerCount > 0) {
        return $this->response->setJSON([
            'res' => false,
            'msg' => 'Layanan yang belum anda proses : ' . $pendingManagerCount,
            'pending' => $pendingManagerCount,
            'xname' => csrf_token(),
            'xhash' => csrf_hash()
        ]);
    }

    // 2) Hitung total pending untuk seluruh LN (semua manajer)
    $pendingTotal = (int) $db->table('simlab_t_layanan_detil')
        ->where('detLnKode', $lnId)
        ->groupStart()
            ->where('detStatus', 0)
            ->orWhere('detStatus IS NULL', null, false)
        ->groupEnd()
        ->countAllResults(false);

    // Jika masih ada pending dari pengelola lain -> jangan ubah lnStatus, 
    // tapi anggap pengiriman manajer ini berhasil dan beri tahu berapa pending tersisa.
    if ($pendingTotal > 0) {
        return $this->response->setJSON([
            'res' => true,
            'msg' => 'Berhasil dikirim — sedang menunggu verifikasi pengelola lain : ' . $pendingTotal . ' layanan ',
            'waiting_others' => true,
            'pending_total' => $pendingTotal,
            'parent_updated' => false,
            'xname' => csrf_token(),
            'xhash' => csrf_hash()
        ]);
    }

    // Jika sampai sini berarti tidak ada pending (untuk semua manajer) -> update parent lnStatus = 3
    // Untuk safety: lakukan re-check di dalam transaksi sebelum update
    $db->transStart();

    $pendingTotalCheck = (int) $db->table('simlab_t_layanan_detil')
        ->where('detLnKode', $lnId)
        ->groupStart()
            ->where('detStatus', 0)
            ->orWhere('detStatus IS NULL', null, false)
        ->groupEnd()
        ->countAllResults(false);

    if ($pendingTotalCheck > 0) {
        // ditemukan pending saat verifikasi akhir -> tidak update parent
        $db->transComplete();
        return $this->response->setJSON([
            'res' => true,
            'msg' => 'Berhasil dikirim — namun ditemukan item pending saat verifikasi akhir ' . $pendingTotalCheck,
            'waiting_others' => true,
            'pending_total' => $pendingTotalCheck,
            'parent_updated' => false,
            'xname' => csrf_token(),
            'xhash' => csrf_hash()
        ]);
    }

    // Semua layanan sdh ter-acc > ubah lnStatus
    $model = new MyModel($this->table);
    $resParent = $model->updateData(['lnStatus' => 3], $this->id, $lnId);
    $parentUpdated = ($resParent === true || $resParent === 1);

    $db->transComplete();

    if ($db->transStatus() === false) {
        return $this->response->setJSON([
            'res' => false,
            'msg' => 'Gagal memperbarui status layanan',
            'xname' => csrf_token(),
            'xhash' => csrf_hash()
        ]);
    }

    return $this->response->setJSON([
        'res' => $parentUpdated,
        'msg' => $parentUpdated ? 'Layanan berhasil dikirim ke admin' : 'Berhasil dikirim ',
        'waiting_others' => false,
        'pending_total' => 0,
        'parent_updated' => $parentUpdated,
        'xname' => csrf_token(),
        'xhash' => csrf_hash()
    ]);
}

  private function formatStatus($status)
    {
        switch ($status) {
            case 0: return '<span class="badge bg-secondary">Draft</span>';
            case 1: return '<span class="badge bg-warning">Layanan belum direview</span>';
            case 2: return '<span class="badge bg-danger">Ditolak</span>';
            case 3: return '<span class="badge bg-info">Layanan terkirim ke admin</span>';
            case 4: return '<span class="badge bg-primary">Pengujian sedang dilakukan</span>';
            case 5: return '<span class="badge bg-primary">LHUS sedang diproses</span>';
            case 6: return '<span class="badge bg-success">LHUS telah disetujui</span>';
            case 7: return '<span class="badge bg-primary">LHU sedang diproses</span>';
            case 8: return '<span class="badge bg-success">LHU telah disetujui</span>';
            case 9: return '<span class="badge bg-dark">Pengujian telah selesai</span>';
            default: return '<span class="badge bg-dark">Unknown</span>';
        }
    }


}
