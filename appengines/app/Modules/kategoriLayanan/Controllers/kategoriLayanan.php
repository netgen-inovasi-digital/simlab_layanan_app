<?php

namespace Modules\kategoriLayanan\Controllers;

use App\Controllers\BaseController;
use App\Models\MyModel;

class kategoriLayanan extends BaseController
{
    private $table = 'simlab_r_jenis';
    private $id    = 'jenKode';
    protected $encrypter;

    public function initController(\CodeIgniter\HTTP\RequestInterface $request, \CodeIgniter\HTTP\ResponseInterface $response, \Psr\Log\LoggerInterface $logger)
    {
        parent::initController($request, $response, $logger);
        $this->encrypter = \Config\Services::encrypter();
    }

    public function index()
    {
        $session  = session(); 
        $user_id  = $session->get('id_user');
        $modelUser = new MyModel('users');

        $data = [
            'title' => 'Data Kategori Layanan',
            'user'  => $modelUser->getDataById('id_user', $user_id),
        ];

        return view('Modules\kategoriLayanan\Views\v_kategoriLayanan', $data);
    }

    public function edit($id)
    {
        $idenc = $id;
        $id    = $this->encrypter->decrypt(hex2bin($id));
        $model = new MyModel($this->table);
        $get   = $model->getDataById($this->id, $id);

        $data[csrf_token()] = csrf_hash();
        $data['id']      = $idenc;
        $data['jenKode'] = $get->jenKode;
        $data['jenNama'] = $get->jenNama;

        return $this->response->setJSON($data);
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
        $idenc = $this->request->getPost('id');
        $jenKode = $this->request->getPost('jenKode');
        $jenNama = $this->request->getPost('jenNama');

        $model = new MyModel($this->table);

        if ($idenc == "") {
            // Mode Insert
            $data = [
                'jenKode' => $jenKode,
                'jenNama' => $jenNama,
            ];
            
            // Cek apakah kode kategori sudah ada untuk insert
            $cekKode = $model->getDataById($this->id, $jenKode);
            if ($cekKode) {
                return $this->response->setJSON([
                    'res'   => 'check',
                    'msg'   => "Kode kategori layanan <strong>{$jenKode}</strong> sudah ada. Silakan gunakan kode yang berbeda.",
                    'xname' => csrf_token(),
                    'xhash' => csrf_hash()
                ]);
            }
            
            $res = $model->insertData($data);
        } else {
            // Mode Update
            $idLama  = $this->encrypter->decrypt(hex2bin($idenc));
            
            // Cek apakah kode diubah
            if ($idLama != $jenKode) {
                // Kode diubah - cek duplikat dan update termasuk kode
                $cekKode = $model->getDataById($this->id, $jenKode);
                if ($cekKode) {
                    return $this->response->setJSON([
                        'res'   => 'check',
                        'msg'   => "Kode kategori layanan <strong>{$jenKode}</strong> sudah digunakan oleh data lain. Silakan gunakan kode yang berbeda.",
                        'xname' => csrf_token(),
                        'xhash' => csrf_hash()
                    ]);
                }
                
                // Update dengan kode baru (CASCADE akan handle relasi)
                $data = [
                    'jenKode' => $jenKode,
                    'jenNama' => $jenNama,
                ];
            } else {
                // Kode tidak diubah - hanya update nama
                $data = [
                    'jenNama' => $jenNama,
                ];
            }
            
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
            $id = bin2hex($this->encrypter->encrypt($row->jenKode));
            
            // Cek jumlah relasi di layanan pengujian
            $jumlahRelasi = $modelLayanan->where('kode_jenis', $row->jenKode)->countAllResults();
            $msgRelasi = $jumlahRelasi > 0 ? "Terdapat {$jumlahRelasi} layanan pengujian yang terhubung dan akan ikut terhapus." : "";
            
            $response = [];
            $response[] = '<span class="badge bg-info">' . esc($row->jenKode) . '</span>';
            $response[] = esc($row->jenNama);
            $response[] = $this->aksi($id, $msgRelasi);
            $data[]     = $response;
        }
        $output = ["items" => $data];
        return $this->response->setJSON($output);
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
