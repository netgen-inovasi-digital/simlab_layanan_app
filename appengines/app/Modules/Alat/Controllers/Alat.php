<?php

namespace Modules\Alat\Controllers;

use App\Controllers\BaseController;
use App\Models\MyModel;

class Alat extends BaseController
{
    private $table = 'simlab_r_alat';
    private $id    = 'alatKode';

    public function index()
    {
        $session   = session(); 
        $user_id   = $session->get('id_user');
        $modelUser = new MyModel('users');

        $data = [
            'title' => 'Data Alat',
            'user'  => $modelUser->getDataById('id_user', $user_id),
        ];

        return view('Modules\Alat\Views\v_alat', $data);
    }

    public function edit($id)
    {
        $idenc = $id;
        $id    = $this->encrypter->decrypt(hex2bin($id));
        $model = new MyModel($this->table);
        $get   = $model->getDataById($this->id, $id);

        $data[csrf_token()] = csrf_hash();
        $data['id']        = $idenc;
        $data['alatKode']  = $get->alatKode;
        $data['alatNama']  = $get->alatNama;

        return $this->response->setJSON($data);
    }

    public function delete($id)
    {
        $id    = $this->encrypter->decrypt(hex2bin($id));
        $model = new MyModel($this->table);
        
        // Cek apakah ada layanan pengujian yang menggunakan alat ini
        $modelLayanan = new MyModel('r_layanan_pengujian');
        $countLayanan = $modelLayanan->getCountAll('kode_alat', $id);
        
        if ($countLayanan > 0) {
            // Ada layanan pengujian yang terhubung - hapus dengan cascade
            $res = $model->deleteDataWithCascade($this->id, $id, [
                'r_layanan_pengujian' => 'kode_alat'
            ]);
            
            return $this->response->setJSON([
                'res'     => $res,
                'message' => $res ? "Data alat dan {$countLayanan} layanan pengujian terkait berhasil dihapus." : "Gagal menghapus data.",
                'cascade' => true,
                'xname'   => csrf_token(),
                'xhash'   => csrf_hash()
            ]);
        }
        
        // Tidak ada layanan terhubung, hapus biasa
        $res = $model->deleteData($this->id, $id);

        return $this->response->setJSON([
            'res'   => $res,
            'xname' => csrf_token(),
            'xhash' => csrf_hash()
        ]);
    }

    public function submit()
    {
        $idenc = $this->request->getPost('id');
        $data = [
            'alatKode' => $this->request->getPost('alatKode'),
            'alatNama' => $this->request->getPost('alatNama'),
        ];

        $model = new MyModel($this->table);

        if ($idenc == "") {
            $res = $model->insertData($data);
        } else {
            $id  = $this->encrypter->decrypt(hex2bin($idenc));
            $oldData = $model->getDataById($this->id, $id);
            
            // Jika kode alat berubah, update dengan cascade ke layanan pengujian
            if ($oldData && $oldData->alatKode !== $data['alatKode']) {
                $res = $model->updateDataWithCascade(
                    $data,
                    $this->id,
                    $id,
                    $oldData->alatKode,
                    [
                        'r_layanan_pengujian' => [
                            'where' => 'kode_alat',
                            'field' => 'kode_alat'
                        ]
                    ]
                );
            } else {
                $res = $model->updateData($data, $this->id, $id);
            }
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
        $modelLayanan = new MyModel('r_layanan_pengujian');
        $data  = [];

        $list = $model->getAllData();
        foreach ($list as $row) {
            $id = bin2hex($this->encrypter->encrypt($row->alatKode));
            
            // Hitung jumlah layanan pengujian yang terhubung
            $countLayanan = $modelLayanan->getCountAll('kode_alat', $row->alatKode);
            
            $response   = [];
            $response[] = '<span class="badge bg-info">' . esc($row->alatKode) . '</span>';
            $response[] = esc($row->alatNama);
            $response[] = $this->aksi($id, $countLayanan);
            $data[]     = $response;
        }

        return $this->response->setJSON(["items" => $data]);
    }

    private function aksi($id, $countLayanan = 0)
    {
        $deleteMsg = '';
        if ($countLayanan > 0) {
            $deleteMsg = "Terdapat {$countLayanan} layanan yang akan ikut terhapus jika anda menghapus alat ini";
        }
        
        return '<div id="' . $id . '" class="float-end">
            <span class="text-secondary btn-action" title="Ubah" onclick="editItem(event)">
                <i class="bi bi-pencil-square"></i></span> 
            <label class="divider">|</label>
            <span class="text-danger btn-action" title="Hapus" onclick="deleteItem(event, \'' . $deleteMsg . '\')">
                <i class="bi bi-trash"></i></span>
        </div>';
    }
}
