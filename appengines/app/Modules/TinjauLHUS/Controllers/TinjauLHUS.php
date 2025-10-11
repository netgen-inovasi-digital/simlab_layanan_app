<?php

namespace Modules\TinjauLHUS\Controllers;

use App\Controllers\BaseController;
use App\Models\MyModel;

class TinjauLHUS extends BaseController
{
    private $table = 'simlab_t_layanan';
    private $id    = 'lnKode';

    public function index()
    {
        $session = session();
        $user_id = $session->get('id_user');

        $modelUser = new MyModel('simlab_account_users');

        $data = [
            'title' => 'Tinjau LHUS',
            'user'  => $modelUser->getDataById('user_id', $user_id),
        ];

        return view('Modules\TinjauLHUS\Views\v_tinjauLhus', $data);
    }

    public function dataList()
    {
        $model = new MyModel($this->table);
        $data  = [];

        // ambil semua data, urutkan tanggal DESC
        $list = $model->getAllDataWithOrder(['lnTgl' => 'DESC']);

        foreach ($list as $row) {
            $status = (int) $row->lnStatus;

            // hanya ambil yg sudah masuk tahap LHUS
            if ($status < 5) {
                continue;
            }

            $id = bin2hex($this->encrypter->encrypt($row->lnKode));
            $response = [];

            // Kolom 1: No Invoice & Tanggal
            $response[] = '<div>'
                . ($row->lnNoTransaksi ?? '-') . '<br>'
                . (!empty($row->lnTgl) ? date('d-m-Y', strtotime($row->lnTgl)) : '-')
                . '</div>';

            // Kolom 2: Nama Layanan (join detil)
            $modelDet = new MyModel('simlab_t_layanan_detil');
            $detil = $modelDet->getAllDataById(['detLnKode' => $row->lnKode]);
            $items = [];
            foreach ($detil as $d) {
                $items[] = $d->detLayanan ?? $d->detJenKode;
            }
            $response[] = !empty($items) ? implode(', ', $items) : '-';

            // Tombol Lihat Detail (panggil loadDetail dengan id terenkripsi)
            // $lihatDetailBtn = '
            //     <span class="text-info btn-action" title="Lihat Detail"
            //         onclick="loadDetail(\'' . $id . '\')">
            //         <i class="bi bi-eye"></i>
            //     </span>
            // ';


            // Kolom 3: Tombol file LHUS (klik untuk lihat)
            $lhusInfo = $this->detectLhusFile($row);
            if ($lhusInfo['has'] && !empty($lhusInfo['url'])) {
                // gunakan anchor supaya bisa dibuka di tab baru; esc() untuk keamanan output
                $safeUrl = esc($lhusInfo['url']);
                $response[] = '<a href="' . $safeUrl . '" class="btn btn-sm btn-outline-primary" target="_blank" rel="noopener noreferrer" title="Lihat LHUS">'
                            . '<i class="bi bi-eye"></i> Lihat</a>';
            } else {
                $response[] = '<span class="text-muted">Belum ada file</span>';
            }

            // Kolom 4: Status
            $response[] = $this->formatStatus($status);

            // Kolom 5: Aksi (gabungkan tombol lihat detail + aksi lain)
            $response[] =  $this->aksi($id, $status);

            $data[] = $response;
        }

        return $this->response->setJSON(["items" => $data]);
    }


    private function detectLhusFile($row)
    {
        $possibleFields = [
            'lnLhus', 'lnLHUS', 'lnFileLhus', 'ln_file_lhus', 'lhus_file', 'ln_lhus', 'ln_lhus_file', 'ln_file_lhus_path'
        ];

        // cek field di tabel utama dulu (jika ada)
        foreach ($possibleFields as $f) {
            if (isset($row->{$f}) && !empty($row->{$f})) {
                $raw = trim($row->{$f});

                if (preg_match('/^https?:\/\//i', $raw)) {
                    return ['has' => true, 'url' => $raw];
                }

                // cek di folder uploads/lhus
                $possiblePath = FCPATH . 'uploads/lhus/' . ltrim($raw, '/');
                if (is_file($possiblePath)) {
                    $possibleUrl = base_url('uploads/lhus/' . ltrim($raw, '/'));
                    return ['has' => true, 'url' => $possibleUrl];
                }

                // jika field ada tetapi file tidak ditemukan, lanjut cek field lain / detil
                continue;
            }
        }

        // Cek di tabel detil (kolom detil_LHUS / detFile / detLhus / dsb)
        if (isset($row->lnKode) && !empty($row->lnKode)) {
            try {
                $modelDet = new MyModel('simlab_t_layanan_detil');
                $detils = $modelDet->getAllDataById(['detLnKode' => $row->lnKode]);
                $detFields = ['detil_LHUS', 'detil_LHU', 'detFile', 'detLhus', 'detFileLhus', 'det_file_lhus', 'detil_lhus'];

                foreach ($detils as $d) {
                    foreach ($detFields as $df) {
                        if (isset($d->{$df}) && !empty($d->{$df})) {
                            $raw = trim($d->{$df});
                            if (preg_match('/^https?:\/\//i', $raw)) {
                                return ['has' => true, 'url' => $raw];
                            }
                            $possiblePath = FCPATH . 'uploads/lhus/' . ltrim($raw, '/');
                            if (is_file($possiblePath)) {
                                $possibleUrl = base_url('uploads/lhus/' . ltrim($raw, '/'));
                                return ['has' => true, 'url' => $possibleUrl];
                            }
                        }
                    }
                }
            } catch (\Throwable $e) {
                // jika error baca detail, anggap tidak ada file
            }
        }

        return ['has' => false, 'url' => '#'];
    }


    public function detailList($id)
    {
        // tolerant decrypt (id dikirim sebagai hex dari client)
        try {
            $lnKode = $this->encrypter->decrypt(hex2bin($id));
        } catch (\Throwable $e) {
            // coba decrypt langsung (jika tidak hex)
            try {
                $lnKode = $this->encrypter->decrypt($id);
            } catch (\Throwable $e2) {
                return $this->response->setJSON([
                    'items' => [],
                    'error' => 'Invalid ID'
                ]);
            }
        }

        // Ambil detil layanan sesuai detLnKode
        $model = new MyModel('simlab_t_layanan_detil d');
        $joins = [
            'simlab_r_layanan_pengujian lp' => 'lp.ujiKode = d.detUjiKode',
            'simlab_r_parameter p'          => 'p.paraKode = lp.ujiParaKode',
            'simlab_r_alat a'               => 'a.alatKode = lp.ujiAlatKode',
        ];
        $where = ['d.detLnKode' => $lnKode];

        $select = "
            d.detUjiKode,
            lp.ujiLayanan,
            p.paraNama,
            a.alatNama,
            d.detBiaya,
            d.detKeterangan
        ";

        try {
            $list = $model->getAllDataWithJoinWhereOrder($joins, $where, ['d.detUjiKode' => 'ASC'], $select);
        } catch (\Throwable $e) {
            // jika query error, kembalikan array kosong
            return $this->response->setJSON(['items' => []]);
        }

        $data = [];
        $no = 1;
        foreach ($list as $row) {
            // tambahkan kolom kode (detUjiKode) supaya total kolom = 5 sesuai modal
            $kodeUji = isset($row->detUjiKode) ? $row->detUjiKode : '-';

            $layanan = isset($row->ujiLayanan) ? $row->ujiLayanan : '-';
            if (isset($row->paraNama) && !empty($row->paraNama)) {
                $layanan .= ' (' . $row->paraNama . ')';
            }

            $biaya = isset($row->detBiaya) ? 'Rp ' . number_format($row->detBiaya, 0, ',', '.') : '-';
            $ket   = isset($row->detKeterangan) && !empty($row->detKeterangan) ? $row->detKeterangan : '-';

            $response = [];
            $response[] = $no++;
            $response[] = $layanan;         // Layanan
            $response[] = $biaya;           // Biaya
            $response[] = $ket;             // Keterangan

            $data[] = $response;
        }

        return $this->response->setJSON(['items' => $data]);
    }

    /**
     * Untuk memproses aksi terima / tolak LHUS.
     * - aksi 'terima' -> lnStatus = 6 (LHUS Disetujui)
     * - aksi 'tolak'  -> lnStatus = 2 (Ditolak)
     *
     * Endpoint: tinjaulhus/proses/{id}/{aksi}  (POST)
     */
    public function proses($idEnc = null, $aksi = null)
    {
        // default response (sertakan CSRF token agar frontend bisa memperbarui)
        $default = [
            'success' => false,
            'msg'     => 'Invalid request',
            'xname'   => csrf_token(),
            'xhash'   => csrf_hash()
        ];

        if (empty($idEnc) || empty($aksi)) {
            $default['msg'] = 'ID atau aksi tidak ditemukan';
            return $this->response->setJSON($default);
        }

        // coba decrypt (tolerant menerima hex atau plain encrpyted string)
        try {
            $lnKode = $this->encrypter->decrypt(hex2bin($idEnc));
        } catch (\Throwable $e) {
            try {
                $lnKode = $this->encrypter->decrypt($idEnc);
            } catch (\Throwable $e2) {
                $default['msg'] = 'ID tidak valid';
                return $this->response->setJSON($default);
            }
        }

        $map = [
            'terima' => 6, // LHUS Disetujui
            'tolak'  => 2  // Ditolak
        ];

        if (!isset($map[$aksi])) {
            $default['msg'] = 'Aksi tidak dikenali';
            return $this->response->setJSON($default);
        }

        $newStatus = $map[$aksi];

        try {
            $model = new MyModel($this->table);
            $res = $model->updateData(['lnStatus' => $newStatus], $this->id, $lnKode);

            if ($res) {
                return $this->response->setJSON([
                    'success' => true,
                    'msg'     => 'Status berhasil diubah',
                    'xname'   => csrf_token(),
                    'xhash'   => csrf_hash()
                ]);
            } else {
                return $this->response->setJSON([
                    'success' => false,
                    'msg'     => 'Gagal mengupdate status (database)',
                    'xname'   => csrf_token(),
                    'xhash'   => csrf_hash()
                ]);
            }
        } catch (\Throwable $e) {
            return $this->response->setJSON([
                'success' => false,
                'msg'     => 'Terjadi error: ' . $e->getMessage(),
                'xname'   => csrf_token(),
                'xhash'   => csrf_hash()
            ]);
        }
    }

    private function aksi($id, $status)
    {
        // gunakan flex agar tombol sejajar dan rapi, tetap float-end
        $btn = '<div id="' . $id . '" class="float-end d-flex align-items-center gap-2">';

        // tombol lihat detail (disamakan gaya dengan tombol aksi lain)
        $btn .= '<span class="text-info btn-action" title="Lihat Detail"
                    onclick="loadDetail(\'' . $id . '\')">
                    <i class="bi bi-eye"></i>
                </span>';

        // jika status = 5, tambahkan tombol terima & tolak
        if ($status == 5) {
            $btn .= '<span class="text-success btn-action" title="Terima"
                        onclick="prosesLhus(\'' . $id . '\', \'terima\')">
                        <i class="bi bi-check-circle"></i>
                    </span>';
            $btn .= '<span class="text-warning btn-action" title="Tolak"
                        onclick="prosesLhus(\'' . $id . '\', \'tolak\')">
                        <i class="bi bi-x-circle"></i>
                    </span>';
            $btn .= '<label class="divider">|</label>';
        }

        // tombol hapus
        $btn .= '<span class="text-danger btn-action" title="Hapus"
                    onclick="deleteItem(event)">
                    <i class="bi bi-trash"></i>
                </span>';

        $btn .= '</div>';

        return $btn;
    }


    private function formatStatus($status)
    {
        switch ($status) {
            case 5: return '<span class="badge bg-warning">Memproses LHUS</span>';
            case 6: return '<span class="badge bg-success">LHUS Disetujui</span>';
            case 7: return '<span class="badge bg-primary">Memproses LHU</span>';
            case 8: return '<span class="badge bg-info">LHU Disetujui</span>';
            case 9: return '<span class="badge bg-dark">Pengujian Selesai</span>';
            case 2: return '<span class="badge bg-danger">Ditolak</span>';
            default: return '<span class="badge bg-secondary">Unknown</span>';
        }
    }
}
