<?php

namespace Modules\FileUmum\Controllers;

use App\Controllers\BaseController;
use App\Models\MyModel;

class FileUmum extends BaseController
{
    private $table = 'file_umum';
    private $id = 'file_id';

    public function index()
    {
        $data = [
            'title' => 'Data File Umum'
        ];
        return view('Modules\FileUmum\Views\v_fileumum', $data);
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
            'file_path' => $get->file_path,
            'deskripsi' => $get->deskripsi,
            'status' => $get->status,
        ];

        return $this->response->setJSON($data);
    }

    public function submit()
    {
        $idenc = $this->request->getPost('id');
        $judul = $this->request->getPost('judul');
        $deskripsi = $this->request->getPost('deskripsi');

        $statusPost = $this->request->getPost('status');
        $status = ($statusPost == 'on' || $statusPost == '1' || $statusPost == 'true') ? 'aktif' : 'non-aktif';

        $data = [
            'judul' => $judul,
            'deskripsi' => $deskripsi,
            'status' => $status,
            'tanggal' => date('Y-m-d H:i:s')
        ];

        $validationRule = [
            'judul' => 'required',
            'file' => 'max_size[file,5120]|ext_in[file,pdf,doc,docx]',
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
            $data['file_path'] = $filename;

            // Hapus file lama jika edit
            if ($idenc) {
                $id = $this->encrypter->decrypt(hex2bin($idenc));
                $existing = $model->getDataById($this->id, $id);
                if ($existing && !empty($existing->file_path) && file_exists(FCPATH . 'uploads/fileumum/' . $existing->file_path)) {
                    unlink(FCPATH . 'uploads/fileumum/' . $existing->file_path);
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

        $no = 1;
        foreach ($list as $row) {
            $id = bin2hex($this->encrypter->encrypt($row->{$this->id}));
            $response = array();

            $response[] = $row->judul;
            $response[] = $row->deskripsi;

            $filePreview = '-';
            if (!empty($row->file_path)) {
                $fileUrl = site_url('fileumum/download/' . $id);
                $fileExt = pathinfo($row->file_path, PATHINFO_EXTENSION);
                $filePreview = '<a href="' . $fileUrl . '" target="_blank" class="btn btn-sm btn-outline-primary">
                    <i class="bi bi-file-earmark-' . ($fileExt == 'pdf' ? 'pdf' : 'text') . '"></i> Lihat
                </a>';
            }
            $response[] = $filePreview;

            if ($row->status == 'non-aktif') {
                $status = '<small class="text-danger"><i class="bi bi-x-circle"></i> Non-Aktif</small>';
            } else {
                $status = '<small><i class="bi bi-check-circle text-primary"></i> Aktif</small>';
            }
            $response[] = $status;

            $response[] = $this->aksi($id);
            $data[] = $response;
        }

        $output = array("items" => $data);
        return $this->response->setJSON($output);
    }

    public function download($idenc)
    {
        $id = $this->encrypter->decrypt(hex2bin($idenc));
        $model = new MyModel($this->table);
        $get = $model->getDataById($this->id, $id);

        if (!$get || empty($get->file_path)) {
            throw \CodeIgniter\Exceptions\PageNotFoundException::forPageNotFound();
        }

        $filePath = FCPATH . 'uploads/fileumum/' . $get->file_path;
        if (!file_exists($filePath)) {
            throw \CodeIgniter\Exceptions\PageNotFoundException::forPageNotFound();
        }

        $ext = pathinfo($get->file_path, PATHINFO_EXTENSION);
        $filename = $get->judul . '.' . $ext;

        return $this->response->download($filePath, null)->setFileName($filename);
    }

    /**
     * Download manual penggunaan berdasarkan role_id user yang sedang login.
     * Mapping: role_id 2 (pelanggan) => file_id 2, role_id 1 (admin) => file_id 3,
     *          role_id 4 (manajer) => file_id 4, role_id 6 (penyelia) => file_id 5
     */
    public function downloadManual()
    {
        $session = session();
        if (!$session->get('logged_in')) {
            return redirect()->to('/login');
        }

        $roleId = $session->get('role_id');

        // Mapping role_id ke file_id manual
        $manualMap = [
            2 => 2, // pelanggan
            1 => 3, // admin
            4 => 4, // manajer
            6 => 5, // penyelia
        ];

        if (!isset($manualMap[$roleId])) {
            return redirect()->back()->with('error', 'Manual tidak tersedia untuk role Anda.');
        }

        $fileId = $manualMap[$roleId];
        $model = new MyModel($this->table);
        $get = $model->getDataById($this->id, $fileId);

        if (!$get || empty($get->file_path)) {
            return redirect()->back()->with('error', 'File manual belum diupload. Silakan hubungi administrator.');
        }

        $filePath = FCPATH . 'uploads/fileumum/' . $get->file_path;
        if (!file_exists($filePath)) {
            return redirect()->back()->with('error', 'File manual tidak ditemukan di server.');
        }

        $ext = pathinfo($get->file_path, PATHINFO_EXTENSION);
        $filename = $get->judul . '.' . $ext;

        return $this->response->download($filePath, null)->setFileName($filename);
    }

    private function aksi($id)
    {
        return '<div id="' . $id . '" class="float-end">
            <span class="text-secondary btn-action" title="Ubah" onclick="editItem(event)">
                <i class="bi bi-pencil-square"></i>
            </span>
        </div>';
    }

    private function doUpload($file)
    {
        $filename = '';
        if ($file && $file->isValid() && !$file->hasMoved()) {
            $ext = $file->getClientExtension();
            $filename = time() . bin2hex(random_bytes(5)) . '.' . $ext;
            $path = FCPATH . 'uploads/fileumum';
            if (!is_dir($path)) {
                mkdir($path, 0755, true);
            }
            $file->move($path, $filename, true);
        }
        return $filename;
    }
}
