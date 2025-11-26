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
        
        // Hapus data - cascade akan ditangani otomatis oleh database FK
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
        $alatKode = $this->request->getPost('alatKode');
        
        $data = [
            'alatKode' => $alatKode,
            'alatNama' => $this->request->getPost('alatNama'),
        ];

        $model = new MyModel($this->table);

        if ($idenc == "") {
            // Cek apakah kode alat sudah ada untuk insert
            $cekKode = $model->getDataById($this->id, $alatKode);
            if ($cekKode) {
                return $this->response->setJSON([
                    'res'   => 'check',
                    'msg'   => "Kode alat <strong>{$alatKode}</strong> sudah ada. Silakan gunakan kode yang berbeda.",
                    'xname' => csrf_token(),
                    'xhash' => csrf_hash()
                ]);
            }
            
            $res = $model->insertData($data);
        } else {
            $idLama  = $this->encrypter->decrypt(hex2bin($idenc));
            
            // Hanya cek duplikat jika kode diubah
            if ($idLama != $alatKode) {
                // Cek apakah kode alat baru sudah digunakan oleh data lain
                $cekKode = $model->getDataById($this->id, $alatKode);
                if ($cekKode) {
                    return $this->response->setJSON([
                        'res'   => 'check',
                        'msg'   => "Kode alat <strong>{$alatKode}</strong> sudah digunakan oleh data lain. Silakan gunakan kode yang berbeda.",
                        'xname' => csrf_token(),
                        'xhash' => csrf_hash()
                    ]);
                }
            }
            
            // Update data - cascade akan ditangani otomatis oleh database FK
            $res = $model->updateData($data, $this->id, $idLama);
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
            
            // Cek jumlah relasi di layanan pengujian
            $jumlahRelasi = $modelLayanan->where('kode_alat', $row->alatKode)->countAllResults();
            $msgRelasi = $jumlahRelasi > 0 ? "Anda akan menghapus {$jumlahRelasi} layanan lab jika menghapus alat ini" : "";
            
            $response   = [];
            $response[] = '<span class="badge bg-info">' . esc($row->alatKode) . '</span>';
            $response[] = esc($row->alatNama);
            $response[] = $this->aksi($id, $msgRelasi);
            $data[]     = $response;
        }

        return $this->response->setJSON(["items" => $data]);
    }

    private function aksi($id, $msgRelasi = '')
    {
        $deleteOnclick = $msgRelasi ? "deleteItem(event, '{$msgRelasi}')" : "deleteItem(event)";
        
        return '<div id="' . $id . '" class="float-end">
            <span class="text-secondary btn-action" title="Ubah" onclick="editItem(event)">
                <i class="bi bi-pencil-square"></i></span> 
            <label class="divider">|</label>
            <span class="text-danger btn-action" title="Hapus" onclick="' . $deleteOnclick . '">
                <i class="bi bi-trash"></i></span>
        </div>';
    }
}
