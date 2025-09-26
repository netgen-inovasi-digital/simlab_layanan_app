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

            // Kolom 3: Tombol file LHUS (sementara di-comment)
            $response[] = '<span class="text-muted">-</span>';

            // Kolom 4: Status
            $response[] = $this->formatStatus($status);

            // Kolom 5: Aksi
            $response[] = $this->aksi($id, $status);

            $data[] = $response;
        }

        return $this->response->setJSON(["items" => $data]);
    }

    public function detailList($id)
    {
        try {
            $kode = $this->encrypter->decrypt(hex2bin($id));
        } catch (\Exception $e) {
            return $this->response->setJSON(['items' => []]);
        }

        $modelDet = new MyModel('simlab_t_layanan_detil');
        $detil = $modelDet->getAllDataById(['detLnKode' => $kode]);

        $data = [];
        $no = 1;
        foreach ($detil as $d) {
            $row = [
                $no++,
                $d->detJenKode,
                $d->detLayanan,
                number_format($d->detBiaya, 0, ',', '.'),
                $d->detKet
            ];
            $data[] = $row;
        }

        return $this->response->setJSON(['items' => $data]);
    }

    public function proses($id = null, $aksi = null)
    {
        if (!$id || !$aksi) {
            return $this->response->setJSON([
                'success' => false,
                'msg'     => 'Data tidak valid'
            ]);
        }

        try {
            $kode = $this->encrypter->decrypt(hex2bin($id));
        } catch (\Exception $e) {
            return $this->response->setJSON([
                'success' => false,
                'msg'     => 'ID tidak valid'
            ]);
        }

        $model = new MyModel($this->table);

        // mapping aksi -> status baru
        $statusBaru = null;
        if ($aksi === 'terima') {
            $statusBaru = 6; // LHUS Disetujui
        } elseif ($aksi === 'tolak') {
            $statusBaru = 2; // Ditolak
        }

        if ($statusBaru === null) {
            return $this->response->setJSON([
                'success' => false,
                'msg'     => 'Aksi tidak dikenal'
            ]);
        }

        // ✅ gunakan updateDataByArray (custom di MyModel)
        $res = $model->updateDataByArray(
            ['lnStatus' => $statusBaru],
            [$this->id => $kode]
        );

        // log untuk debugging
        log_message('debug', 'Update status LHUS | kode: '.$kode.' | aksi: '.$aksi.' | status: '.$statusBaru.' | result: '.($res ? 'OK' : 'FAIL'));

        // response + update CSRF hash
        $security = \Config\Services::security();
        return $this->response->setJSON([
            'success' => $res,
            'msg'     => $res ? 'Proses berhasil' : 'Gagal update data',
            'xname'   => $security->getCSRFTokenName(),
            'xhash'   => $security->getCSRFHash()
        ]);
    }

    private function aksi($id, $status)
    {
        $btn = '<div id="' . $id . '" class="float-end">';
        if ($status == 5) { // LHUS sedang ditinjau
            $btn .= '<span class="text-success btn-action" title="Terima" onclick="prosesLhus(\''.$id.'\', \'terima\')">
                        <i class="bi bi-check-circle"></i></span> ';
            $btn .= '<span class="text-warning btn-action" title="Tolak" onclick="prosesLhus(\''.$id.'\', \'tolak\')">
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
