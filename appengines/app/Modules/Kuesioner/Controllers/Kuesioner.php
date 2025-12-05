<?php

namespace Modules\Kuesioner\Controllers;

use App\Controllers\BaseController;
use App\Models\MyModel;

class Kuesioner extends BaseController
{
    private $table = 'simlab_t_kuesioner';
    private $id = 'kuesioner_id';
    protected $encrypter;

    public function __construct()
    {
        $this->encrypter = \Config\Services::encrypter();
    }

    public function index()
    {
        $data = [
            'title' => 'Data Master Kuesioner',
        ];
        return view('Modules\Kuesioner\Views\v_kuesioner', $data);
    }

    public function submit()
    {
        $idenc = $this->request->getPost('id');
        $tipe  = $this->request->getPost('pertanyaan_tipe');
        $opsi_string = null;

        if ($tipe == 'pilihan') {
            $opsi_array = $this->request->getPost('opsi');

            if (is_array($opsi_array)) {
                $filtered_opsi = array_filter($opsi_array, function($value) { 
                    return $value !== null && trim($value) !== ''; 
                });
                $opsi_string = implode("\n", $filtered_opsi);
            }
        }

        $data = [
            'pertanyaan_teks'  => $this->request->getPost('pertanyaan_teks'),
            'pertanyaan_tipe'  => $tipe,
            'pertanyaan_wajib' => $this->request->getPost('pertanyaan_wajib'),
            'pertanyaan_opsi'  => $opsi_string,
        ];

        $model = new MyModel($this->table);
        $res = false;

        if ($idenc == "") {
            $res = $model->insertData($data);
        } else {
            $id = $this->encrypter->decrypt(hex2bin($idenc));
            $res = $model->updateData($data, $this->id, $id);
        }

        return $this->response->setJSON([
            'res'   => $res,
            'xname' => csrf_token(),
            'xhash' => csrf_hash()
        ]);
    }

    function edit($id)
    {
        $idenc = $id;
        $id = $this->encrypter->decrypt(hex2bin($id));
        $model = new MyModel($this->table);
        $get = $model->getDataById($this->id, $id);

        $data = [];
        if ($get) {
            $data[csrf_token()] = csrf_hash();
            $data['id'] = $idenc;
            $data['pertanyaan_teks'] = $get->pertanyaan_teks;
            $data['pertanyaan_tipe'] = $get->pertanyaan_tipe;
            $data['pertanyaan_wajib'] = $get->pertanyaan_wajib;

            if ($get->pertanyaan_tipe == 'pilihan') {
                $data['opsi_list'] = explode("\n", $get->pertanyaan_opsi ?? '');
            } else {
                $data['opsi_list'] = []; 
            }
            
        }

        return $this->response->setJSON($data);
    }

    function delete($id)
    {
        $id = $this->encrypter->decrypt(hex2bin($id));
        $model = new MyModel($this->table);
        $res = $model->deleteData($this->id, $id);

        return $this->response->setJSON([
            'res'   => $res,
            'xname' => csrf_token(),
            'xhash' => csrf_hash()
        ]);
    }

    public function dataList()
    {
        $model = new MyModel($this->table);
        $data = [];
        $select = '*';
        $where = [];
        // Order by ID (primary key)
        $list = $model->getAllDataWithJoinWhereOrder([], $where, [$this->table . '.' . $this->id => 'ASC'], $select);

        foreach ($list as $row) {
            $id = bin2hex($this->encrypter->encrypt($row->{$this->id}));
            $response = [];

            $response[] = esc($row->pertanyaan_teks);

            $tipe = '';
            switch ($row->pertanyaan_tipe) {
                case 'pilihan':
                    $tipe = '<span class="badge bg-primary">Pilihan Ganda</span>';
                    break;
                case 'rating':
                    $tipe = '<span class="badge bg-primary">Rating</span>'; 
                    break;
                default:
                    $tipe = '<span class="badge bg-primary">Isian Teks</span>';
                    break;
            }
            $response[] = $tipe;
            
            $response[] = ($row->pertanyaan_wajib == 1)
                ? '<span class="badge bg-success">Ya</span>'
                : '<span class="badge bg-danger">Tidak</span>'; 

            $response[] = $this->aksi($id);

            $data[] = $response;
        }

        $output = ["items" => $data];
        return $this->response->setJSON($output);
    }

    function aksi($id)
    {
        return '<div id="' . $id . '" class="float-end">
            <span class="text-secondary btn-action" title="Ubah" onclick="editItem(event)">
                <i class="bi bi-pencil-square"></i></span> 
            <label class="divider">|</label>
            <span class="text-danger btn-action" title="Hapus" onclick="deleteItem(event)">
                <i class="bi bi-trash"></i></span>
        </div>';
    }
}