<?php

namespace Modules\Pengumuman\Controllers;

use App\Controllers\BaseController;
use App\Models\MyModel;

class Pengumuman extends BaseController
{
    private $table = 'pengumuman';
    private $id = 'id_pengumuman';

    public function index()
    {
        $data = [
            'title' => 'Data Pengumuman'
        ];
        return view('Modules\Pengumuman\Views\v_pengumuman', $data);
    }

    public function edit()
    {
        $idenc = $this->request->getPost('id');
        $id = $this->encrypter->decrypt(hex2bin($idenc));
        $model = new MyModel($this->table);
        $get = $model->getDataById($this->id, $id);

        if (!$get) {
            return $this->response->setJSON(['res' => 'error', 'msg' => 'Data tidak ditemukan.']);
        }

        $data = [
            csrf_token() => csrf_hash(),
            'id' => $idenc,
            'judul' => $get->judul,
            'file' => $get->file,
            'status' => $get->status,
        ];

        return $this->response->setJSON($data);
    }


    public function delete($id)
    {
        $id = $this->encrypter->decrypt(hex2bin($id));
        $model = new MyModel($this->table);

        // Hapus file fisik sebelum hapus data
        $existing = $model->getDataById($this->id, $id);
        if ($existing && !empty($existing->file) && file_exists(FCPATH . 'uploads/pengumuman/' . $existing->file)) {
            unlink(FCPATH . 'uploads/pengumuman/' . $existing->file);
        }

        $res = $model->deleteData($this->id, $id);

        return $this->response->setJSON([
            'res' => $res,
            'xname' => csrf_token(),
            'xhash' => csrf_hash()
        ]);
    }

    public function submit()
    {
        $idenc = $this->request->getPost('id');
        $judul = $this->request->getPost('judul');

        $statusPost = $this->request->getPost('status');
        $status = ($statusPost == 'on' || $statusPost == '1' || $statusPost == 'true') ? 'tampil' : 'tersembunyi';


        $data = [
            'judul' => $judul,
            'status' => $status
        ];

        $validationRule = [
            'judul' => 'required',
            'file' => 'max_size[file,2048]|ext_in[file,png,jpg,jpeg]',
        ];

        if (!$this->validate($validationRule)) {
            return $this->response->setJSON([
                'res' => 'check',
                'link' => $this->validator->getErrors(),
                'xname' => csrf_token(),
                'xhash' => csrf_hash()
            ]);
        }

        $model = new MyModel($this->table);
        $file = $this->request->getFile('file');

        // Upload file baru jika ada
        if ($file && $file->isValid() && !$file->hasMoved()) {
            $filename = $this->doUpload($file);
            $data['file'] = $filename;

            // Hapus file lama jika edit
            if ($idenc) {
                $id = $this->encrypter->decrypt(hex2bin($idenc));
                $existing = $model->getDataById($this->id, $id);
                if ($existing && !empty($existing->file) && file_exists(FCPATH . 'uploads/pengumuman/' . $existing->file)) {
                    unlink(FCPATH . 'uploads/pengumuman/' . $existing->file);
                }
            }
        }

        if ($idenc == "") {
            $res = $model->insertData($data);
        } else {
            $id = $this->encrypter->decrypt(hex2bin($idenc));
            $res = $model->updateData($data, $this->id, $id);
        }

        return $this->response->setJSON([
            'res' => $res,
            'xname' => csrf_token(),
            'xhash' => csrf_hash()
        ]);
    }

    public function dataList()
    {
        $model = new MyModel($this->table);
        $data = array();
        $list = $model->getAllData();

        foreach ($list as $row) {
            $id = bin2hex($this->encrypter->encrypt($row->{$this->id}));
            $response = array();

            $response[] = $row->judul;

            $filePreview = '-';
            if (!empty($row->file)) {
                $fileUrl = base_url('uploads/pengumuman/' . $row->file);
                $filePreview = '<a href="' . $fileUrl . '" target="_blank" class="btn btn-sm btn-outline-primary">
                    <i class="bi bi-eye"></i> Lihat
                </a>';
            }
            $response[] = $filePreview;

            $status = '<small><i class="bi bi-check-circle text-primary"></i> Active</small>';
            if ($row->status == 'tersembunyi') {
                $status = '<small class="text-danger"><i class="bi bi-x-circle"></i> Non-Active</small>';
            }
            $response[] = $status;

            $response[] = $this->aksi($id);
            $data[] = $response;
        }

        $output = array("items" => $data);
        return $this->response->setJSON($output);
    }

    private function aksi($id)
    {
        return '<div id="' . $id . '" class="float-end">
            <span class="text-secondary btn-action" title="Ubah" onclick="editItem(event)">
                <i class="bi bi-pencil-square"></i>
            </span> 
            <label class="divider">|</label>
            <span class="text-danger btn-action" title="Hapus" onclick="deleteItem(event)">
                <i class="bi bi-trash"></i>
            </span>
        </div>';
    }

    private function doUpload($file)
    {
        $filename = '';
        if ($file && $file->isValid() && !$file->hasMoved()) {
            $ext = $file->getClientExtension();
            $filename = time() . bin2hex(random_bytes(5)) . '.' . $ext;
            $path = FCPATH . 'uploads/pengumuman';
            if (!is_dir($path)) {
                mkdir($path, 0755, true);
            }
            $file->move($path, $filename, true);
        }
        return $filename;
    }
}
