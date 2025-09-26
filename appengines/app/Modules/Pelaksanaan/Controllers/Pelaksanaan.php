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

    foreach ($list as $row) {
        // ✅ hanya tampilkan status >= 6
        if ((int) $row->lnStatus < 6) {
            continue;
        }

        $id = bin2hex($this->encrypter->encrypt($row->lnKode));
        $response = [];

        // detail layanan
        $modelDet = new MyModel('simlab_t_layanan_detil');
        $detil = $modelDet->getAllDataById(['detLnKode' => $row->lnKode]);

        $items = [];
        foreach ($detil as $d) {
            $items[] = $d->detLayanan ?? $d->detJenKode;
        }
        $itemList = !empty($items) ? implode(', ', $items) : '-';

        // Kolom 1: No Invoice + Tanggal
        $response[] = '<div>'
                    . ($row->lnNoTransaksi ?? '-') . '<br>'
                    . (!empty($row->lnTgl) ? date('d-m-Y', strtotime($row->lnTgl)) : '-') 
                    . '</div>';

        // Kolom 2: Nama layanan
        $response[] = '<div>' . $itemList . '</div>';

        // Kolom 3: LHUS (lihat file)
        $response[] = '<button class="btn btn-sm btn-secondary">
                           <i class="bi bi-file-earmark-text"></i> Lihat
                       </button>';

        // Kolom 4: LHU (upload file)
        $response[] = '
            <div id="' . $id . '">
                <label class="btn btn-sm btn-outline-primary mb-0">
                    <i class="bi bi-upload"></i> Upload
                    <input type="file" class="d-none" onchange="uploadLhu(event)">
                </label>
            </div>';

        // Kolom 5: Status
        $response[] = $this->formatStatus($row->lnStatus);

        // Kolom 6: Aksi (hapus / proses)
        $response[] = $this->aksiButton($id, $row->lnStatus);

        $data[] = $response;
    }

    return $this->response->setJSON(["items" => $data]);
}

    private function formatStatus($status)
    {
        switch ($status) {
            case 6: return '<span class="badge bg-primary">Memproses LHU</span>';
            case 7: return '<span class="badge bg-success">LHU Disetujui</span>';
            default: return '<span class="badge bg-dark">Unknown</span>';
        }
    }

    private function aksiButton($id, $status)
    {
        $btn = '<div id="' . $id . '" class="float-end">';

        // tombol proses hanya muncul jika status = 6
        if ($status == 6) {
            $btn .= '<span class="text-success btn-action" title="Proses" onclick="prosesItem(event)">
                        <i class="bi bi-check2-circle"></i>
                     </span> ';
            $btn .= '<label class="divider">|</label> ';
        }

        // tombol hapus selalu ada
        $btn .= '<span class="text-danger btn-action" title="Hapus" onclick="deleteItem(event)">
                    <i class="bi bi-trash"></i>
                 </span>';

        $btn .= '</div>';
        return $btn;
    }

    // ✅ Tambahan: proses ubah status ke 7
    public function proses($id)
    {
        try {
            $kode = $this->encrypter->decrypt(hex2bin($id));
        } catch (\Exception $e) {
            return $this->response->setJSON([
                'res'   => false,
                'msg'   => 'ID tidak valid',
                'xname' => csrf_token(),
                'xhash' => csrf_hash()
            ]);
        }

        $model = new MyModel($this->table);
        $res   = $model->updateData(['lnStatus' => 7], $this->id, $kode);

        return $this->response->setJSON([
            'res'   => $res,
            'xname' => csrf_token(),
            'xhash' => csrf_hash()
        ]);
    }

    // ✅ Tambahan: hapus data
    public function delete($id)
    {
        try {
            $kode = $this->encrypter->decrypt(hex2bin($id));
        } catch (\Exception $e) {
            return $this->response->setJSON([
                'res'   => false,
                'msg'   => 'ID tidak valid',
                'xname' => csrf_token(),
                'xhash' => csrf_hash()
            ]);
        }

        $model = new MyModel($this->table);
        $res   = $model->deleteData($this->id, $kode);

        return $this->response->setJSON([
            'res'   => $res,
            'xname' => csrf_token(),
            'xhash' => csrf_hash()
        ]);
    }
}
