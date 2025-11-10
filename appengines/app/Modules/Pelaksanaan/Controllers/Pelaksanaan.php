<?php  
namespace Modules\Pelaksanaan\Controllers;

use App\Controllers\BaseController;
use App\Models\MyModel;

class Pelaksanaan extends BaseController
{
    private $table = 'simlab_t_layanan';
    private $id    = 'lnKode';

    
    public function index()
    {
        $session = session();
        $user_id = $session->get('id_user');

        $modelUser = new MyModel('simlab_account_users');

        $data = [
            'title' => 'Data Pelaksanaan',
            'user'  => $modelUser->getDataById('user_id', $user_id),
        ];

        return view('Modules\Pelaksanaan\Views\v_pelaksanaan', $data);
    }

    public function dataList()
    {
        $model = new MyModel($this->table);
        $data  = [];

        $list = $model->getAllDataWithOrder(['lnTgl' => 'DESC']);

        if (empty($list)) {
            return $this->response->setJSON(["items" => []]);
        }

        // [FILTER] ?lnStatus=7,6,uploaded,pending
        $lnStatusParam = (string) ($this->request->getGet('lnStatus') ?? '');
        $wantUploaded  = false;   // status 6 + sudah upload
        $wantPending   = false;   // status 6 + belum upload
        $statusNums    = [];

        if ($lnStatusParam !== '') {
            $parts = preg_split('/[,\s]+/', $lnStatusParam, -1, PREG_SPLIT_NO_EMPTY);
            foreach ($parts as $p) {
                $tp = strtolower(trim($p));
                if (in_array($tp, ['uploaded','terunggah'], true)) { $wantUploaded = true; continue; }
                if (in_array($tp, ['pending','belumupload','belum_upload'], true)) { $wantPending = true; continue; }
                if (is_numeric($tp)) { $statusNums[] = (int)$tp; }
            }
            $statusNums = array_values(array_unique($statusNums));
        }

        // prefetch user info
        $userIds = [];
        foreach ($list as $r) { if (!empty($r->user_id)) $userIds[] = $r->user_id; }
        $userMap = [];
        if (!empty($userIds)) {
            $db = \Config\Database::connect();
            $users = $db->table('simlab_account_users')
                        ->select('user_id, user_name, user_identity, user_email')
                        ->whereIn('user_id', array_values(array_unique($userIds)))
                        ->get()->getResult();
            foreach ($users as $u) $userMap[$u->user_id] = $u;
        }

        foreach ($list as $row) {
            if ((int)$row->lnStatus < 6) continue;

            $id = bin2hex(service('encrypter')->encrypt($row->lnKode));
                // $encrypted_id = bin2hex(service('encrypter')->encrypt($row->bayarKode));
            $response = [];

            // kolom pemesan
            $pemesanNama = '-'; $tipe='-'; $tanggal='-';
            if (isset($row->user_id, $userMap[$row->user_id])) {
                $u = $userMap[$row->user_id];
                $pemesanNama = !empty($u->user_name) ? $u->user_name : ($row->lnOrangNama ?? '-');
                $tipe = !empty($u->user_identity) ? $u->user_identity : '-';
            } else {
                $pemesanNama = !empty($row->lnOrangNama) ? $row->lnOrangNama : ($row->lnPemesanNama ?? '-');
                $tipe = !empty($row->lnPemesanIdentity) ? $row->lnPemesanIdentity : ($row->lnJenisPemesan ?? '-');
            }
            if (!empty($row->lnTgl)) $tanggal = date('d-m-Y H:i', strtotime($row->lnTgl));

            $response[] =
                '<div style="line-height:1.3;">
                    <span style="font-size:1rem; font-weight:600;">'.esc($pemesanNama).'</span><br>
                    <span style="font-size:0.9rem; color:#555;">'.esc($tanggal).' | '.esc($tipe).'</span>
                </div>';

            // kolom LHUS (lihat)
            $response[] = '<button type="button" class="btn btn-sm btn-info" title="Lihat Detail Item Layanan" onclick="loadDetail(\''.$id.'\')">
                                <i class="bi bi-eye"></i> Lihat File
                           </button>';

            // deteksi file LHU
            $lhuInfo = $this->detectLhuFile($row);

            // kolom status (single badge)
            $response[] = '<div id="status-cell-'.$id.'">'.$this->formatStatus($row->lnStatus, $lhuInfo['has']).'</div>';

            // ❗️RULE BARU: boleh accept jika status=6 DAN file LHU sudah ada
            $allowAccept = ((int)$row->lnStatus === 6 && $lhuInfo['has'] === true);

            // kolom aksi
            $response[] = $this->aksiButton($id, $row->lnStatus, $allowAccept, $lhuInfo);

            // terapkan filter
            if ($wantUploaded || $wantPending || !empty($statusNums)) {
                $match = false;
                if (!empty($statusNums) && in_array((int)$row->lnStatus, $statusNums, true)) $match = true;
                if ((int)$row->lnStatus === 6) {
                    if ($wantUploaded && $lhuInfo['has']) $match = true;
                    if ($wantPending  && !$lhuInfo['has']) $match = true;
                }
                if (!$match) continue;
            }

            $data[] = $response;
        }

        return $this->response->setJSON(["items" => $data]);
    }

   
    public function detailList($id = null)
{
    if (!$id) return $this->response->setJSON(['items' => []]);

    // decrypt tolerant (hex → raw)
    try { $lnKode = service('encrypter')->decrypt(hex2bin($id)); }
    catch (\Throwable $e) {
        try { $lnKode = service('encrypter')->decrypt($id); }
        catch (\Throwable $e2) { return $this->response->setJSON(['items' => []]); }
    }

    $db = \Config\Database::connect();

    // ambil detil + JOIN username uploader/approver
    $rows = $db->table('simlab_t_layanan_detil as d')
        ->select('
            d.detKode, d.detUjiKode, d.detLayanan, d.detJumlah, d.detKeterangan,
            d.detil_LHUS, d.detil_LHU, d.detKetLn, d.detKetLhus, d.detStatus,
            up.username  AS upload_by,
            acc.username AS acc_by
        ')
        ->join('simlab_account up',  'up.user_id  = d.detUploadLHUS', 'left')
        ->join('simlab_account acc', 'acc.user_id = d.detAccLHUS',    'left')
        ->where('d.detLnKode', $lnKode)
        ->where('d.detStatus', 1)
        ->orderBy('d.detKode', 'ASC')
        ->get()->getResult();

    if (empty($rows)) return $this->response->setJSON(['items' => []]);

    // prefetch nama layanan uji (kalau detLayanan kosong)
    $ujiMap = []; $ujiKodeList = [];
    foreach ($rows as $r) if (!empty($r->detUjiKode)) $ujiKodeList[] = (int)$r->detUjiKode;
    $ujiKodeList = array_values(array_unique($ujiKodeList));
    if (!empty($ujiKodeList)) {
        $ujis = $db->table('simlab_r_layanan_pengujian')
                   ->select('ujiKode, ujiLayanan')
                   ->whereIn('ujiKode', $ujiKodeList)
                   ->get()->getResult();
        foreach ($ujis as $u) $ujiMap[$u->ujiKode] = $u->ujiLayanan;
    }

    $items = []; $no = 1;
    foreach ($rows as $row) {
        if ((int)($row->detStatus ?? 0) !== 1) continue;

        $layanan = !empty($row->detLayanan) ? $row->detLayanan
                  : ((!empty($row->detUjiKode) && isset($ujiMap[$row->detUjiKode])) ? $ujiMap[$row->detUjiKode] : '-');

        $jumlah = (int)($row->detJumlah ?? 0);
        $ket    = trim((string)($row->detKeterangan ?? ''));
        $ket    = $ket === '' ? '-' : esc($ket);

        // cari file untuk tombol "Lihat"
        $fileUrl = null;
        foreach (['detil_LHUS','detil_LHU','detKetLhus','detKetLn'] as $cf) {
            if (!isset($row->{$cf}) || trim((string)$row->{$cf}) === '') continue;
            $val = trim((string)$row->{$cf});
            if (strpos($val, ';;') !== false) {
                foreach (array_filter(array_map('trim', explode(';;', $val))) as $p) {
                    if (preg_match('/^https?:\/\//i', $p)) 
                        { $fileUrl = $p; break 2; }
                    $p1 = FCPATH.'uploads/lhus/'.ltrim($p,'/');
                    $p2 = FCPATH.'uploads/lhu/'.ltrim($p,'/');
                    if (is_file($p1))
                         { $fileUrl = base_url('uploads/lhus/'.ltrim($p,'/')); break 2; }
                    if (is_file($p2)) 
                        { $fileUrl = base_url('uploads/lhu/'.ltrim($p,'/'));  break 2; }
                }
            } else {
                if (preg_match('/^https?:\/\//i', $val)) { $fileUrl = $val; break; }
                $p1 = FCPATH.'uploads/lhus/'.ltrim($val,'/');
                $p2 = FCPATH.'uploads/lhu/'.ltrim($val,'/');
                if (is_file($p1)) { $fileUrl = base_url('uploads/lhus/'.ltrim($val,'/')); break; }
                if (is_file($p2)) { $fileUrl = base_url('uploads/lhu/'.ltrim($val,'/'));  break; }
            }
        }

        $viewHtml = $fileUrl
            ? '<button class="btn btn-sm btn-outline-primary" onclick="window.open(\'' . esc($fileUrl) . '\', \'_blank\')"><i class="bi bi-eye"></i></button>'
            : '<button class="btn btn-sm btn-secondary" disabled><i class="bi bi-file-earmark-text"></i> Lihat</button>';

        // username (nickname) dari simlab_account
        $uploadBy = !empty($row->upload_by) ? esc($row->upload_by) : '-';
        $accBy    = !empty($row->acc_by)    ? esc($row->acc_by)    : '-';

        // urutan kolom dikembalikan seperti semula + 2 kolom tambahan di akhir
        $items[] = [
            $no++,
            $layanan,
            $jumlah,
            $ket,
            $viewHtml,
            $uploadBy,   // Upload LHUS (username)
            $accBy       // Acc LHUS (username)
        ];
    }

    return $this->response->setJSON(['items' => $items]);
}



    public function upload()
    {
        $file    = $this->request->getFile('lhu_file');
        $encId   = $this->request->getPost('id');
        $detKode = $this->request->getPost('detKode');

        if (empty($encId)) {
            return $this->response->setJSON([
                'res' => 'error','msg' => 'ID tidak ditemukan',
                'xname' => csrf_token(),'xhash' => csrf_hash()
            ]);
        }

        try { $lnKode = service('encrypter')->decrypt(hex2bin($encId)); }
        catch (\Throwable $e) {
            try { $lnKode = service('encrypter')->decrypt($encId); }
            catch (\Throwable $e2) {
                return $this->response->setJSON([
                    'res'=>'error','msg'=>'ID tidak valid',
                    'xname'=>csrf_token(),'xhash'=>csrf_hash()
                ]);
            }
        }

        if (!($file && $file->isValid() && !$file->hasMoved())) {
            return $this->response->setJSON([
                'res'=>'error','msg'=>'File tidak valid atau tidak dipilih',
                'xname'=>csrf_token(),'xhash'=>csrf_hash()
            ]);
        }

        // upload fisik
        $uploadResult = $this->doUpload($file, 'lhu');
        if (!$uploadResult['status']) {
            return $this->response->setJSON([
                'res'=>'error','msg'=>$uploadResult['msg'],
                'xname'=>csrf_token(),'xhash'=>csrf_hash()
            ]);
        }
        $filename = $uploadResult['filename'];

        $db = \Config\Database::connect();

        try {
            if (!empty($detKode)) {
                // hapus lama (single det)
                try {
                    $oldRow = $db->table('simlab_t_layanan_detil')->select('detil_LHU')->where('detKode', $detKode)->get()->getRow();
                    if ($oldRow && !empty($oldRow->detil_LHU)) {
                        $oldPath = FCPATH.'uploads/lhu/'.ltrim($oldRow->detil_LHU,'/');
                        if (is_file($oldPath)) @unlink($oldPath);
                    }
                } catch (\Throwable $e) {}

                $ok = $db->table('simlab_t_layanan_detil')->where('detKode', $detKode)->update(['detil_LHU' => $filename]);

                if ($ok) {
                    return $this->response->setJSON([
                        'res'=>true,
                        'msg'=>'File LHU berhasil diunggah ke detail.',
                        'url'=>base_url('uploads/lhu/'.$filename),
                        'detKode'=>$detKode,
                        'xname'=>csrf_token(),'xhash'=>csrf_hash()
                    ]);
                } else {
                    $savedPath = FCPATH.'uploads/lhu/'.$filename;
                    if (is_file($savedPath)) @unlink($savedPath);
                    $dberr = $db->error();
                    return $this->response->setJSON([
                        'res'=>'error','msg'=>'Gagal menyimpan ke detail. DB: '.($dberr['message']??'Unknown'),
                        'db'=>$dberr,'xname'=>csrf_token(),'xhash'=>csrf_hash()
                    ]);
                }
            } else {
                // hapus semua lama (untuk lnKode ini)
                try {
                    $oldRows = $db->table('simlab_t_layanan_detil')->select('detil_LHU')->where('detLnKode', $lnKode)->get()->getResult();
                    if ($oldRows) {
                        foreach ($oldRows as $r) {
                            if (!empty($r->detil_LHU)) {
                                $oldPath = FCPATH.'uploads/lhu/'.ltrim($r->detil_LHU,'/');
                                if (is_file($oldPath)) @unlink($oldPath);
                            }
                        }
                    }
                } catch (\Throwable $e) {}

                $ok = $db->table('simlab_t_layanan_detil')->where('detLnKode', $lnKode)->update(['detil_LHU' => $filename]);

                if ($ok) {
                    return $this->response->setJSON([
                        'res'=>true,
                        'msg'=>'File LHU berhasil diunggah.',
                        'url'=>base_url('uploads/lhu/'.$filename),
                        'xname'=>csrf_token(),'xhash'=>csrf_hash()
                    ]);
                } else {
                    $savedPath = FCPATH.'uploads/lhu/'.$filename;
                    if (is_file($savedPath)) @unlink($savedPath);
                    $dberr = $db->error();
                    return $this->response->setJSON([
                        'res'=>'error','msg'=>'Gagal menyimpan ke detil. DB: '.($dberr['message']??'Unknown'),
                        'db'=>$dberr,'xname'=>csrf_token(),'xhash'=>csrf_hash()
                    ]);
                }
            }
        } catch (\Throwable $e) {
            $savedPath = FCPATH.'uploads/lhu/'.$filename;
            if (is_file($savedPath)) @unlink($savedPath);
            return $this->response->setJSON([
                'res'=>'error','msg'=>'Error saat menyimpan: '.$e->getMessage(),
                'xname'=>csrf_token(),'xhash'=>csrf_hash()
            ]);
        }
    }

    // upload helper
    private function doUpload($file, $folder = 'lhu')
    {
        if (!($file && $file->isValid() && !$file->hasMoved())) {
            return ['status'=>false,'msg'=>'File tidak valid atau sudah dipindahkan'];
        }

        $allowedExt  = ['jpg','jpeg','png','pdf','doc','docx','xls','xlsx'];
        $allowedMime = [
            'image/jpeg','image/png',
            'application/pdf',
            'application/msword',
            'application/vnd.openxmlformats-officedocument.wordprocessingml.document',
            'application/vnd.ms-excel',
            'application/vnd.openxmlformats-officedocument.spreadsheetml.sheet'
        ];

        $ext = strtolower($file->getClientExtension());
        $tmp = $file->getTempName();
        if (!is_file($tmp)) return ['status'=>false,'msg'=>'File sementara tidak ditemukan'];

        $detectedMime = function_exists('finfo_open')
            ? (function($tmp){ $f=finfo_open(FILEINFO_MIME_TYPE); $m=finfo_file($f,$tmp); finfo_close($f); return $m;})($tmp)
            : $file->getClientMimeType();

        if (!in_array($ext,$allowedExt) || !in_array($detectedMime,$allowedMime)) {
            return ['status'=>false,'msg'=>'Format file tidak diperbolehkan'];
        }

        if (in_array($ext,['jpg','jpeg','png']) && @getimagesize($tmp) === false) {
            return ['status'=>false,'msg'=>'File bukan gambar asli'];
        }

        if ($file->getSize() > 5*1024*1024) return ['status'=>false,'msg'=>'Ukuran file maksimal 5MB'];

        try { $rand = bin2hex(random_bytes(8)); } catch (\Exception $e) { $rand = bin2hex(openssl_random_pseudo_bytes(8)); }
        $filename = time().'_'.$rand.'.'.$ext;

        $path = FCPATH.'uploads/'.$folder;
        if (!is_dir($path)) @mkdir($path,0755,true);

        try {
            $file->move($path,$filename,true);
            $full = $path.DIRECTORY_SEPARATOR.$filename;
            if (is_file($full)) @chmod($full,0644);
        } catch (\Exception $e) {
            return ['status'=>false,'msg'=>'Gagal memindahkan file: '.$e->getMessage()];
        }

        return ['status'=>true,'filename'=>$filename];
    }

    // detect LHUS
    private function detectLhusFile($row)
    {
        $fields = ['lnLhus','lnLHUS','lnFileLhus','ln_file_lhus','lhus_file','ln_lhus','ln_lhus_file','ln_file_lhus_path'];
        foreach ($fields as $f) {
            if (isset($row->{$f}) && !empty($row->{$f})) {
                $raw = $row->{$f};
                if (preg_match('/^https?:\/\//i',$raw)) return ['has'=>true,'url'=>$raw];
                $p = FCPATH.'uploads/lhus/'.ltrim($raw,'/');
                if (is_file($p)) return ['has'=>true,'url'=>base_url('uploads/lhus/'.ltrim($raw,'/'))];
                return ['has'=>false,'url'=>'#'];
            }
        }
        if (!empty($row->lnKode)) {
            try {
                $detils = (new MyModel('simlab_t_layanan_detil'))->getAllDataById(['detLnKode'=>$row->lnKode]);
                foreach ($detils as $d) {
                    foreach (['detil_LHUS','detLhus','detFileLhus','det_file_lhus'] as $df) {
                        if (isset($d->{$df}) && !empty($d->{$df})) {
                            $raw = $d->{$df};
                            if (preg_match('/^https?:\/\//i',$raw)) return ['has'=>true,'url'=>$raw];
                            $p = FCPATH.'uploads/lhus/'.ltrim($raw,'/');
                            if (is_file($p)) return ['has'=>true,'url'=>base_url('uploads/lhus/'.ltrim($raw,'/'))];
                        }
                    }
                }
            } catch (\Throwable $e) {}
        }
        return ['has'=>false,'url'=>'#'];
    }

    // detect LHU
    private function detectLhuFile($row)
    {
        $fields = ['lnLhu','lnLHU','lnFileLhu','ln_file_lhu','lhu_file','ln_lhu','ln_lhu_file','ln_file_lhu_path'];
        foreach ($fields as $f) {
            if (isset($row->{$f}) && !empty($row->{$f})) {
                $raw = $row->{$f};
                if (preg_match('/^https?:\/\//i',$raw)) return ['has'=>true,'url'=>$raw];
                $p = FCPATH.'uploads/lhu/'.ltrim($raw,'/');
                if (is_file($p)) return ['has'=>true,'url'=>base_url('uploads/lhu/'.ltrim($raw,'/'))];
                return ['has'=>false,'url'=>'#'];
            }
        }
        if (!empty($row->lnKode)) {
            try {
                $detils = (new MyModel('simlab_t_layanan_detil'))->getAllDataById(['detLnKode'=>$row->lnKode]);
                foreach ($detils as $d) {
                    foreach (['detil_LHU','detFile','detFilelhu','det_file_lhu'] as $df) {
                        if (isset($d->{$df}) && !empty($d->{$df})) {
                            $raw = $d->{$df};
                            if (preg_match('/^https?:\/\//i',$raw)) return ['has'=>true,'url'=>$raw];
                            $p = FCPATH.'uploads/lhu/'.ltrim($raw,'/');
                            if (is_file($p)) return ['has'=>true,'url'=>base_url('uploads/lhu/'.ltrim($raw,'/'))];
                        }
                    }
                }
            } catch (\Throwable $e) {}
        }
        return ['has'=>false,'url'=>'#'];
    }

    private function formatStatus($status, $uploaded = null)
    {
        switch ((int)$status) {
            case 7: return '<span class="badge bg-success">LHU Disetujui</span>';
            case 6:
                return $uploaded === true
                    ? '<span class="badge bg-success">LHU terunggah</span>'
                    : '<span class="badge bg-primary">LHU belum diproses</span>';
            default: return '<span class="badge bg-dark">Unknown</span>';
        }
    }

    private function aksiButton($id, $status, $allowAccept = true, $lhuInfo = ['has' => false, 'url' => '#'])
    {
        $btn = '<div id="'.$id.'" class="float-end d-flex align-items-center justify-content-end" style="gap:10px;">';

        if ((int)$status >= 6) {
            $safeUrl = ($lhuInfo['has'] && !empty($lhuInfo['url'])) ? esc($lhuInfo['url']) : '#';
            $btn .= '<span class="text-primary btn-action" title="Upload LHU" onclick="openUploadModal(\''.$id.'\', \''.$safeUrl.'\')" style="cursor:pointer;">
                        <i class="bi bi-cloud-upload"></i>
                     </span>';
        }

        if ((int)$status === 6) {
            if ($allowAccept) {
                $btn .= '<span class="text-success btn-action" title="Proses (Setujui LHU)" onclick="prosesItem(event)" style="cursor:pointer;">
                            <i class="bi bi-check2-circle"></i>
                         </span>';
            } else {
                $btn .= '<span class="text-muted btn-action" title="Unggah LHU terlebih dahulu baru bisa di-accept" style="cursor:not-allowed;opacity:0.5;">
                            <i class="bi bi-check2-circle"></i>
                         </span>';
            }
        }

        $btn .= '</div>';
        return $btn;
    }

    // proses: kini hanya butuh LHU sudah terunggah
    public function proses($id)
    {
        try { $kode = service('encrypter')->decrypt(hex2bin($id)); }
        catch (\Exception $e) {
            return $this->response->setJSON([
                'res'=>false,'msg'=>'ID tidak valid',
                'xname'=>csrf_token(),'xhash'=>csrf_hash()
            ]);
        }

        $model = new MyModel($this->table);
        $row   = $model->getDataById($this->id, $kode);

        if (!$row) {
            return $this->response->setJSON([
                'res'=>false,'msg'=>'Data layanan tidak ditemukan',
                'xname'=>csrf_token(),'xhash'=>csrf_hash()
            ]);
        }

        // HARUS ada file LHU
        $lhuInfo = $this->detectLhuFile($row);
        if (!($lhuInfo['has'] ?? false)) {
            return $this->response->setJSON([
                'res'=>false,
                'msg'=>'Tidak dapat memproses: file LHU belum terunggah.',
                'xname'=>csrf_token(),'xhash'=>csrf_hash()
            ]);
        }

        // (opsional) pastikan status minimal 6
        if ((int)$row->lnStatus < 6) {
            return $this->response->setJSON([
                'res'=>false,
                'msg'=>'Status belum pada tahap Memproses LHU.',
                'xname'=>csrf_token(),'xhash'=>csrf_hash()
            ]);
        }

        // set ke 7
        $res = $model->updateData(['lnStatus'=>7], $this->id, $kode);

        return $this->response->setJSON([
            'res'=>$res,
            'xname'=>csrf_token(),'xhash'=>csrf_hash()
        ]);
    }

    public function delete($id)
    {
        try { $kode = service('encrypter')->decrypt(hex2bin($id)); }
        catch (\Exception $e) {
            return $this->response->setJSON([
                'res'=>false,'msg'=>'ID tidak valid',
                'xname'=>csrf_token(),'xhash'=>csrf_hash()
            ]);
        }

        $model = new MyModel($this->table);
        $res   = $model->deleteData($this->id, $kode);

        return $this->response->setJSON([
            'res'=>$res,'xname'=>csrf_token(),'xhash'=>csrf_hash()
        ]);
    }
}
