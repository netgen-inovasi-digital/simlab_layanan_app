<?php

namespace Modules\KajiUlang\Controllers;

use App\Controllers\BaseController;
use App\Models\MyModel;

class KajiUlang extends BaseController
{
    private $table = 'simlab_t_layanan';
    private $id    = 'lnKode';
    protected $encrypter;

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
            'title' => 'Data Kaji Ulang',
            'user'  => $modelUser->getDataById('user_id', $user_id),
        ];

        return view('Modules\KajiUlang\Views\v_kajiUlang', $data);
    }

    public function datalist()
    {
        $session = session();
        $user_id = $session->get('id_user');
        $model   = new MyModel($this->table);
        $data    = [];

        $lnStatusRaw = (string) ($this->request->getGet('lnStatus') ?? '');
        $statusArr = [];
        if ($lnStatusRaw !== '') {
            if (preg_match_all('/\d+/', $lnStatusRaw, $m) && !empty($m[0])) {
                $statusArr = array_map('intval', $m[0]);
                $statusArr = array_values(array_unique($statusArr));
            }
        }

        $db = \Config\Database::connect();

        // Dapatkan daftar kode_layanan yang berkaitan dengan user lewat r_tim -> t_layanan_detil
        $builderLn = $db->table('t_layanan_detil as d');
        // tambahkan alias supaya hasil selalu berproperty kode_layanan
        $builderLn->select('DISTINCT d.kode_layanan AS kode_layanan', false);
        $builderLn->join('r_tim rt', 'rt.uji_kode = d.uji_kode', 'inner');
        $builderLn->where('rt.user_id', $user_id);
        $rowsLn = $builderLn->get()->getResult();

        $lnKodeList = [];
        foreach ($rowsLn as $item) {
            if (is_array($item) && isset($item['kode_layanan'])) {
                $lnKodeList[] = $item['kode_layanan'];
            } elseif (is_object($item) && isset($item->kode_layanan)) {
                $lnKodeList[] = $item->kode_layanan;
            }
        }

        $lnKodeList = array_values(array_unique(array_filter($lnKodeList, function ($v) {
            return $v !== null && $v !== '' && $v !== 0;
        })));

        if (empty($lnKodeList)) {
            return $this->response->setJSON(['items' => []]);
        }

        // Build main query on simlab_t_layanan
        $builder = $db->table('simlab_t_layanan as l');
        // join both account_users (preferred) and account (fallback)
        $builder->join('simlab_account_users as au', 'au.user_id = l.user_id', 'left');
        $builder->join('simlab_account as a', 'a.user_id = l.user_id', 'left');

        // use COALESCE so pemesan_name favors account_users.user_name, then account.nama, then lnAccEmail
        $builder->select("
            l.*,
            COALESCE(au.user_name, a.nama, l.lnAccEmail, '-') AS pemesan_name,
            COALESCE(au.user_email, l.lnAccEmail, '') AS pemesan_email,
            COALESCE(au.user_identity, '-') AS pemesan_identity,
            COALESCE(pm.pending_for_manager, 0) as pending_for_manager
        ", false);


        // SUBQUERY pending per manager
        $pendingSub = $db->table('t_layanan_detil as det')
            ->select('det.kode_layanan AS kode_layanan, SUM(CASE WHEN (det.status_layanan = 0 OR det.status_layanan IS NULL) THEN 1 ELSE 0 END) AS pending_for_manager', false)
            ->join('r_tim rt', 'rt.uji_kode = det.uji_kode', 'inner')
            ->where('rt.user_id', $user_id)
            ->groupBy('det.kode_layanan');

        // join subquery
        $builder->join('(' . $pendingSub->getCompiledSelect(false) . ') pm', 'pm.kode_layanan = l.lnKode', 'left');

        $builder->whereIn('l.lnKode', $lnKodeList);

        // exclude lnStatus = 2 (Ditolak)
        $builder->where('l.lnStatus !=', 2);

        // === FILTER BERDASARKAN LOGIC TABEL UTAMA ===
        if (!empty($statusArr)) {
            $builder->groupStart();
            foreach ($statusArr as $st) {
                $st = (int)$st;
                if ($st === 1) {
                    // 1 (Belum direview): lnStatus = 1 AND pending_for_manager > 0
                    $builder->orGroupStart()
                        ->where('l.lnStatus', 1)
                        ->where('COALESCE(pm.pending_for_manager,0) >', 0, false)
                        ->groupEnd();
                } elseif ($st === 3) {
                    // 3 (Terkirim ke admin): pending_for_manager = 0 and not draft
                    $builder->orGroupStart()
                        ->where('COALESCE(pm.pending_for_manager,0) =', 0, false)
                        ->where('l.lnStatus !=', 0)
                        ->groupEnd();
                } elseif ($st === 4) {
                    // 4 (Pengujian)
                    $builder->orGroupStart()
                        ->where('l.lnStatus', 4)
                        ->groupEnd();
                } else {
                    // fallback ke lnStatus murni
                    $builder->orGroupStart()
                        ->where('l.lnStatus', $st)
                        ->groupEnd();
                }
            }
            $builder->groupEnd();
        }

        $builder->orderBy('l.lnTgl', 'DESC');
        $list = $builder->get()->getResult();

        foreach ($list as $row) {
            // HANYA skip status=0 jika TIDAK sedang mem-filter
            if ((int)$row->lnStatus === 0 && empty($statusArr)) {
                continue;
            }

            $id = bin2hex($this->encrypter->encrypt($row->lnKode));
            $response = [];

            $pemesanNama = !empty($row->pemesan_name) ? $row->pemesan_name : '-';
            $tipe        = !empty($row->pemesan_identity) ? $row->pemesan_identity : '-';
            $tanggal     = !empty($row->lnTgl) ? date('d-m-Y H:i', strtotime($row->lnTgl)) : '-';

            $combined = '
                <div style="line-height:1.3;">
                    <span style="font-size:1rem; font-weight:600;">' . esc($pemesanNama) . '</span><br>
                    <span style="font-size:0.9rem; color:#555;">' . esc($tanggal) . ' | ' . esc($tipe) . '</span>
                </div>';

            $response[] = $combined;

            // Tampilkan status perspektif manajer
            $response[] = $this->formatStatusForManager($row->lnStatus, $row->lnKode, $user_id);

            $response[] = '<a href="javascript:void(0)" onclick="loadDetail(\'' . $id . '\', \'' . esc($row->lnKode) . '\')" 
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

        $db = \Config\Database::connect();

        // Cek user sebagai anggota tim untuk layanan ini
        $checkBuilder = $db->table('t_layanan_detil as d');
        $checkBuilder->select('1');
        $checkBuilder->join('r_tim rt', 'rt.uji_kode = d.uji_kode', 'inner');
        $checkBuilder->where('d.kode_layanan', $kode);
        $checkBuilder->where('rt.user_id', $user_id);
        $exists = $checkBuilder->limit(1)->get()->getRow();

        if (!$exists) {
            return $this->response->setJSON(['items' => []]);
        }

        // Encrypted ln dipakai di tombol & save
        $encLnId = bin2hex($this->encrypter->encrypt($kode));

        $builder = $db->table('t_layanan_detil as d');
        $builder->select("
            d.uji_kode,
            d.kode_layanan,
            d.nama_layanan,
            d.kode_jenis,
            GROUP_CONCAT(DISTINCT d.catatan_pelanggan SEPARATOR ' | ') AS catatan_pelanggan,
            GROUP_CONCAT(DISTINCT d.catatan_manajer SEPARATOR ' | ') AS catatan_manajer,
            SUM(d.jumlah) AS jumlah,
            SUM(d.biaya) AS total_biaya,
            MAX(d.status_layanan) AS status_group
        ");
        $builder->join('r_tim rt', 'rt.uji_kode = d.uji_kode', 'inner');
        $builder->where('d.kode_layanan', $kode);
        $builder->where('rt.user_id', $user_id);
        $builder->groupBy('d.uji_kode, d.kode_layanan, d.nama_layanan, d.kode_jenis');

        $rows = $builder->get()->getResult();

        $data = [];
        $no = 1;

        foreach ($rows as $row) {
            $response = [];
            $response[] = $no++;
            $response[] = $row->nama_layanan ?? '-';
            $response[] = isset($row->jumlah) ? (int)$row->jumlah : 0;

            $response[] = '<div 
                        style="display:block; max-width:240px; min-width:160px; width:100%;
                            max-height:120px; min-height:48px; overflow-y:auto; overflow-x:hidden;
                            padding:4px 6px; border:1px solid #ddd; border-radius:4px; background:#f9f9f9;
                            white-space:pre-wrap; word-break:break-word; font-size:0.9rem;">'
                . htmlspecialchars($row->catatan_pelanggan ?? '', ENT_QUOTES, 'UTF-8') .
                '</div>';

            // Status grouping
            $statusGroup = isset($row->status_group) ? (int)$row->status_group : null;
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

            // Aksi approve/reject 
            $ujiKodeInt = (int)$row->uji_kode;
            $encLnForBtn = $encLnId;
            $komentarVal = $row->catatan_manajer !== null ? esc($row->catatan_manajer) : '';

            $textarea = '<textarea class="form-control komentar-input" data-uji="' . $ujiKodeInt . '" rows="2" placeholder="Keterangan/manajer..."'
                . ' style="max-width:240px; min-width:160px; max-height:120px; min-height:48px; overflow-y:auto; overflow-x:hidden; resize:vertical; white-space:pre-wrap; word-break:break-word;">'
                . $komentarVal .
                '</textarea>';

            $response[] = $textarea;

            $aksiHtml = '<div class="d-flex justify-content-center gap-2 align-items-center">';
            $aksiHtml .= '<span class="text-success btn-action btn-accept-manager" title="Setujui" data-ln="' . $encLnForBtn . '" data-uji="' . $ujiKodeInt . '"><i class="bi bi-check-circle"></i></span> ';
            $aksiHtml .= '<span class="text-warning btn-action btn-reject-manager" title="Tolak" data-ln="' . $encLnForBtn . '" data-uji="' . $ujiKodeInt . '"><i class="bi bi-x-circle"></i></span>';
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

        // Cek otorisasi via r_tim
        $check = (int)$db->table('t_layanan_detil as d')
            ->join('r_tim rt', 'rt.uji_kode = d.uji_kode', 'inner')
            ->where('d.kode_layanan', $lnKode)
            ->where('rt.user_id', $user_id)
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

        $builder = $db->table('t_layanan_detil');
        foreach ($input['items'] as $it) {
            $uji = isset($it['ujiKode']) ? (int)$it['ujiKode'] : null;
            $kom = isset($it['komentar']) ? $it['komentar'] : null;

            if ($uji === null) continue;

            $builder->where('kode_layanan', $lnKode)
                ->where('uji_kode', $uji)
                ->update(['catatan_manajer' => $kom]);
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

        $session   = session();
        $managerId = (int) ($session->get('id_user') ?? 0);

        if ($managerId <= 0) {
            return $this->response->setJSON([
                'res' => false,
                'affected' => 0,
                'msg' => 'User login tidak ditemukan.',
                'xname' => csrf_token(),
                'xhash' => csrf_hash()
            ]);
        }

        $db = \Config\Database::connect();

        // Pastikan managerId valid
        $acc = $db->table('simlab_account')->select('user_id')->where('user_id', $managerId)->get()->getRow();
        if (!$acc) {
            return $this->response->setJSON([
                'res' => false,
                'affected' => 0,
                'msg' => 'User login tidak valid di simlab_account.',
                'xname' => csrf_token(),
                'xhash' => csrf_hash()
            ]);
        }

        // PASTIKAN manager adalah anggota tim untuk uji ini (otorisasi)
        $auth = (int) $db->table('r_tim')
            ->where('uji_kode', $uji)
            ->where('user_id', $managerId)
            ->countAllResults(false);

        if ($auth === 0) {
            return $this->response->setJSON([
                'res' => false,
                'affected' => 0,
                'msg' => 'Anda tidak berwenang memproses uji ini.',
                'xname' => csrf_token(),
                'xhash' => csrf_hash()
            ]);
        }

        $table = $db->table('t_layanan_detil');

        // Total baris matching (khusus uji + ln)
        $table->where('kode_layanan', $lnId);
        $table->where('uji_kode', $uji);
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

        // Sudah berapa yang status_layanan=1
        $table->where('kode_layanan', $lnId);
        $table->where('uji_kode', $uji);
        $table->where('status_layanan', 1);
        $already = (int) $table->countAllResults(false);

        if ($already === $total) {
            // Jika semua sudah approved untuk uji ini, cek apakah masih ada pending di seluruh LN
            $pendingBuilder = $db->table('t_layanan_detil');
            $pendingBuilder->where('kode_layanan', $lnId);
            $pendingBuilder->groupStart()
                ->where('status_layanan', 0)
                ->orWhere('status_layanan IS NULL', null, false)
                ->groupEnd();
            $pendingRemaining = (int) $pendingBuilder->countAllResults(false);

            $parentUpdated = false;
            if ($pendingRemaining === 0) {
                // Jika tidak ada pending sama sekali, update parent menjadi status 3 (Layanan terkirim ke admin)
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

        // Mulai TRANSAKSI untuk menghindari race condition antara update + pengecekan pending + update parent
        $db->transStart();

        // Update hanya baris yang belum status_layanan=1 untuk uji yang bersangkutan
        // (otorisasi sudah dipastikan via r_tim untuk uji ini)
        $resUpdate = $db->table('t_layanan_detil')
            ->where('kode_layanan', $lnId)
            ->where('uji_kode', $uji)
            ->where('(status_layanan IS NULL OR status_layanan != 1)')
            ->update([
                'status_layanan'    => 1,
                'terima_layanan_by' => $managerId
            ]);

        $affected = $db->affectedRows();
        $parentUpdated = false;

        if ($affected > 0) {
            // Setelah update, cek apakah masih ada pending di seluruh LN
            $pendingBuilder = $db->table('t_layanan_detil');
            $pendingBuilder->where('kode_layanan', $lnId);
            $pendingBuilder->groupStart()
                ->where('status_layanan', 0)
                ->orWhere('status_layanan IS NULL', null, false)
                ->groupEnd();
            $pendingRemaining = (int) $pendingBuilder->countAllResults(false);

            if ($pendingRemaining === 0) {
                // Update ke 3 (Layanan terkirim ke admin) bila sudah tidak ada pending
                $model = new MyModel($this->table);
                $resParent = $model->updateData(['lnStatus' => 3], $this->id, $lnId);
                $parentUpdated = ($resParent === true || $resParent === 1);
            }
        }

        $db->transComplete();
        $transOk = $db->transStatus();

        return $this->response->setJSON([
            'res'      => $transOk && $affected > 0,
            'affected' => $affected,
            'msg'      => $affected > 0 ? 'Berhasil disetujui' : 'No rows updated',
            'parent_updated' => $parentUpdated,
            'xname'    => csrf_token(),
            'xhash'    => csrf_hash()
        ]);
    }


    public function rejectDetail()
    {

        $session   = session();
        $managerId = (int) ($session->get('id_user') ?? 0);


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

        $session   = session();
        $managerId = (int) ($session->get('id_user') ?? 0);

        if ($managerId <= 0) {
            return $this->response->setJSON([
                'res' => false,
                'affected' => 0,
                'msg' => 'User login tidak ditemukan.',
                'xname' => csrf_token(),
                'xhash' => csrf_hash()
            ]);
        }

        $db = \Config\Database::connect();

        // Pastikan managerId valid
        $acc = $db->table('simlab_account')->select('user_id')->where('user_id', $managerId)->get()->getRow();
        if (!$acc) {
            return $this->response->setJSON([
                'res' => false,
                'affected' => 0,
                'msg' => 'User login tidak valid di simlab_account.',
                'xname' => csrf_token(),
                'xhash' => csrf_hash()
            ]);
        }

        // PASTIKAN manager adalah anggota tim untuk uji ini (otorisasi)
        $auth = (int) $db->table('r_tim')
            ->where('uji_kode', $uji)
            ->where('user_id', $managerId)
            ->countAllResults(false);

        if ($auth === 0) {
            return $this->response->setJSON([
                'res' => false,
                'affected' => 0,
                'msg' => 'Anda tidak berwenang memproses uji ini.',
                'xname' => csrf_token(),
                'xhash' => csrf_hash()
            ]);
        }

        $table = $db->table('t_layanan_detil');

        // Total baris matching
        $table->where('kode_layanan', $lnId);
        $table->where('uji_kode', $uji);
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

        // Sudah berapa yang status_layanan=2
        $table->where('kode_layanan', $lnId);
        $table->where('uji_kode', $uji);
        $table->where('status_layanan', 2);
        $already = (int) $table->countAllResults(false);

        if ($already === $total) {
            $pendingBuilder = $db->table('t_layanan_detil');
            $pendingBuilder->where('kode_layanan', $lnId);
            $pendingBuilder->groupStart()
                ->where('status_layanan', 0)
                ->orWhere('status_layanan IS NULL', null, false)
                ->groupEnd();
            $pendingRemaining = (int) $pendingBuilder->countAllResults(false);

            $parentUpdated = false;
            if ($pendingRemaining === 0) {
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

        // Mulai TRANSAKSI untuk menghindari race condition
        $db->transStart();

        // Update hanya baris yang belum status_layanan=2
        $resUpdate = $db->table('t_layanan_detil')
            ->where('kode_layanan', $lnId)
            ->where('uji_kode', $uji)
            ->where('(status_layanan IS NULL OR status_layanan != 2)')
            ->update([
                'status_layanan'     => 2,
                'terima_layanan_by'  => $managerId
            ]);

        $affected = $db->affectedRows();
        $parentUpdated = false;

        if ($affected > 0) {
            $pendingBuilder = $db->table('t_layanan_detil');
            $pendingBuilder->where('kode_layanan', $lnId);
            $pendingBuilder->groupStart()
                ->where('status_layanan', 0)
                ->orWhere('status_layanan IS NULL', null, false)
                ->groupEnd();
            $pendingRemaining = (int) $pendingBuilder->countAllResults(false);

            if ($pendingRemaining === 0) {
                $model = new MyModel($this->table);
                $resParent = $model->updateData(['lnStatus' => 3], $this->id, $lnId);
                $parentUpdated = ($resParent === true || $resParent === 1);
            }
        }

        $db->transComplete();
        $transOk = $db->transStatus();

        return $this->response->setJSON([
            'res'      => $transOk && $affected > 0,
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

        // Otorisasi via r_tim
        $check = (int) $db->table('t_layanan_detil as d')
            ->join('r_tim rt', 'rt.uji_kode = d.uji_kode', 'inner')
            ->where('d.kode_layanan', $lnId)
            ->where('rt.user_id', $user_id)
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

        // Pending per-manajer (via r_tim)
        $pendingManagerCount = (int) $db->table('t_layanan_detil as d')
            ->join('r_tim rt', 'rt.uji_kode = d.uji_kode', 'inner')
            ->where('d.kode_layanan', $lnId)
            ->where('rt.user_id', $user_id)
            ->groupStart()
            ->where('d.status_layanan', 0)
            ->orWhere('d.status_layanan IS NULL', null, false)
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

        // Pending total LN
        $pendingTotal = (int) $db->table('t_layanan_detil')
            ->where('kode_layanan', $lnId)
            ->groupStart()
            ->where('status_layanan', 0)
            ->orWhere('status_layanan IS NULL', null, false)
            ->groupEnd()
            ->countAllResults(false);

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

        // Update parent lnStatus = 3
        $db->transStart();

        $pendingTotalCheck = (int) $db->table('t_layanan_detil')
            ->where('kode_layanan', $lnId)
            ->groupStart()
            ->where('status_layanan', 0)
            ->orWhere('status_layanan IS NULL', null, false)
            ->groupEnd()
            ->countAllResults(false);

        if ($pendingTotalCheck > 0) {
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
            case 0:
                return '<span class="badge bg-secondary">Draft</span>';
            case 1:
                return '<span class="badge bg-warning">Layanan belum direview</span>';
            case 2:
                return '<span class="badge bg-danger">Ditolak</span>';
            case 3:
                return '<span class="badge bg-info">Layanan terkirim ke admin</span>';
            case 4:
                return '<span class="badge bg-primary">Pengujian sedang dilakukan</span>';
            case 5:
                return '<span class="badge bg-primary">LHUS sedang diproses</span>';
            case 6:
                return '<span class="badge bg-success">LHUS telah disetujui</span>';
            case 7:
                return '<span class="badge bg-primary">LHU sedang diproses</span>';
            case 8:
                return '<span class="badge bg-success">LHU telah disetujui</span>';
            case 9:
                return '<span class="badge bg-dark">Pengujian telah selesai</span>';
            default:
                return '<span class="badge bg-dark">Unknown</span>';
        }
    }

    private function formatStatusForManager($lnStatus, $lnKode, $userId)
    {
        $db = \Config\Database::connect();

        // Cek pending via r_tim
        $pendingCount = (int) $db->table('t_layanan_detil as d')
            ->join('r_tim rt', 'rt.uji_kode = d.uji_kode', 'inner')
            ->where('d.kode_layanan', $lnKode)
            ->where('rt.user_id', $userId)
            ->groupStart()
            ->where('d.status_layanan', 0)
            ->orWhere('d.status_layanan IS NULL', null, false)
            ->groupEnd()
            ->countAllResults(false);

        // Jika manajer tidak punya pending lagi -> badge "Layanan terkirim ke admin"
        if ($pendingCount === 0) {
            return '<span class="badge bg-info">Layanan terkirim ke admin</span>';
        }

        // Selain itu, tampilkan status parent sebagaimana biasa
        return $this->formatStatus((int)$lnStatus);
    }

    public function getSampleIdentity($lnKode = null)
    {
        if (!$lnKode) {
            return $this->response->setJSON([
                'success' => false,
                'message' => 'Kode layanan tidak ditemukan'
            ]);
        }

        $modelIdentitasSampel = new MyModel('t_identitas_sampel');
        $sampleData = $modelIdentitasSampel->getDataById('kode_layanan', $lnKode);

        if (!$sampleData) {
            return $this->response->setJSON([
                'success' => false,
                'message' => 'Data identitas sampel tidak ditemukan'
            ]);
        }

        return $this->response->setJSON([
            'success' => true,
            'data' => [
                'jenis' => $sampleData->jenis ?? '-',
                'kemasan' => $sampleData->kemasan ?? '-',
                'sifat' => $sampleData->sifat ?? '-',
                'sisa' => $sampleData->sisa ?? '-',
                'deskripsi' => $sampleData->deskripsi ?? '-',
                'keterangan_khusus' => $sampleData->keterangan_khusus ?? '-'
            ]
        ]);
    }
}
