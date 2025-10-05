<?php 

namespace Modules\Akun\Controllers;

use App\Controllers\BaseController;
use App\Models\MyModel;

class Akun extends BaseController
{
    private $table = 'simlab_account_users'; 
    private $id    = 'user_id';

    public function index()
    {
        $data = [
            'title' => 'Data Akun Pengguna'
        ];
        return view('Modules\Akun\Views\v_akun', $data);
    }

    public function edit($id)
    {
        $idenc = $id;
        $id    = $this->encrypter->decrypt(hex2bin($id));

        $model = new MyModel($this->table);
        $get   = $model->getDataById($this->id, $id);

        $data[csrf_token()]    = csrf_hash();
        $data['id']            = $idenc;
        $data['user_name']     = $get->user_name;
        $data['user_email']    = $get->user_email;
        $data['user_telpon']   = $get->user_telpon; 
        $data['status_user']   = $get->status_user;
        $data['user_identity'] = $get->user_identity;
        $data['user_instansi'] = $get->user_instansi;
        $data['bukti']         = $get->bukti ?? null;
        $data['verifikasi']    = $get->verifikasi ?? 0;

        // generate URL preview kalau ada file
        if (!empty($get->bukti)) {
            $data['bukti_url'] = base_url('uploads/bukti/' . $get->bukti);
        } else {
            $data['bukti_url'] = null;
        }

        return $this->response->setJSON($data);
    }

    public function delete($id)
    {
        $id = $this->encrypter->decrypt(hex2bin($id));

        $model = new MyModel($this->table);
        $data  = $model->getDataById($this->id, $id);

        // hapus file bukti jika ada
        if (!empty($data->bukti)) {
            $oldPath = FCPATH . 'uploads/bukti/' . $data->bukti;
            if (is_file($oldPath) && strpos(realpath($oldPath), realpath(FCPATH . 'uploads/bukti')) === 0) {
                unlink($oldPath);
            }
        }

        $res = $model->deleteData($this->id, $id);

        return $this->response->setJSON([
            'res'   => $res,
            'xname' => csrf_token(),
            'xhash' => csrf_hash()
        ]);
    }

    public function submit()
    {
        $idenc    = $this->request->getPost('id');
        $username = $this->request->getPost('user_name');
        $email    = $this->request->getPost('user_email');
        $telpon   = $this->request->getPost('user_telpon');

        $model = new MyModel($this->table);

        $data = [
            'user_name'     => $username,
            'user_email'    => $email,
            'user_telpon'   => $telpon, 
            'status_user'   => $this->request->getPost('status_user'),
            'user_identity' => $this->request->getPost('user_identity'),
            'role_id'       => 2,
        ];

        // simpan instansi hanya jika NON ULM
        if ($this->request->getPost('user_identity') === 'NON ULM') {
            $data['user_instansi'] = $this->request->getPost('user_instansi');
        } else {
            $data['user_instansi'] = null;
        }

        // password
        $password = $this->request->getPost('user_password');
        if (!empty($password)) {
            $data['user_password'] = password_hash($password, PASSWORD_DEFAULT);
        }
        
        // Upload Bukti File
        $file = $this->request->getFile('bukti_file');
        if ($file && $file->isValid() && !$file->hasMoved()) {
            // Jika ini UPDATE, hapus file lama sebelum upload baru
            if (!empty($idenc)) {
                $id      = $this->encrypter->decrypt(hex2bin($idenc));
                $current = $model->getDataById($this->id, $id);
                if (!empty($current->bukti)) {
                    $oldPath = FCPATH . 'uploads/bukti/' . $current->bukti;
                    if (is_file($oldPath) && strpos(realpath($oldPath), realpath(FCPATH . 'uploads/bukti')) === 0) {
                        unlink($oldPath);
                    }
                }
            }

            // Upload file baru
            $filename = $this->doUpload($file);
            if ($filename != "") {
                $data['bukti'] = $filename;
            }
        }

        // Verifikasi (radio button: 1 = Terverifikasi, 0 = Belum)
        $verifikasi = $this->request->getPost('verifikasi');
        $data['verifikasi'] = ($verifikasi === '1') ? 1 : 0;

        // cek apakah email sudah ada
        $check = $model->getDataById('user_email', $email);

        if ($idenc == "") {
            // INSERT
            if ($check) {
                $res  = 'check';
                $link = 'Email sudah ada!';
            } else {
                $res = $model->insertData($data);
            }
        } else {
            // UPDATE
            $id      = $this->encrypter->decrypt(hex2bin($idenc));
            $current = $model->getDataById($this->id, $id);

            if ($check && $current->user_email != $email) {
                $res  = 'check';
                $link = 'Email sudah ada!';
            } else {
                $res = $model->updateData($data, $this->id, $id);
            }
        }

        return $this->response->setJSON([
            'res'   => $res,
            'link'  => $link ?? '',
            'xname' => csrf_token(),
            'xhash' => csrf_hash()
        ]);
    }

    public function dataList()
    {
        $model = new MyModel($this->table);
        $data  = [];

        $list = $model->getAllData('user_id', 'DESC');
        foreach ($list as $row) {
            $id       = bin2hex($this->encrypter->encrypt($row->user_id));
            $response = [];

            $response[] = $row->user_name;

            // kolom kontak
            $kontak  = "Email : " . $row->user_email;
            $kontak .= "<br>No. Telepon : " . ($row->user_telpon ?? '-'); 
            $response[] = $kontak;

            // identitas + verifikasi
            $identity = $row->user_identity;

            if ($row->verifikasi == 1) {
                $verify = '<span class="badge bg-success">Terverifikasi</span>';
            } else {
                $verify = '<span class="badge bg-danger">Belum Terverifikasi</span>';
            }

            $response[] = $identity . '<br>' . $verify;

            // instansi kalau NON ULM
            if ($row->user_identity === 'NON ULM') {
                $response[] = $row->user_instansi ?? '-';
            } else {
                $response[] = '-';
            }

            // status aktif
            $aktif = '<small><i class="bi bi-check-circle text-primary"></i> Aktif</small>';
            if ($row->status_user == 0) {
                $aktif = '<small class="text-danger"><i class="bi bi-x-circle"></i> Tidak Aktif</small>';
            }

            $response[] = $aktif;
            $response[] = $this->aksi($id);

            $data[] = $response;
        }

        $output = ["items" => $data];
        return $this->response->setJSON($output);
    }

    private function aksi($id)
    {
        return '<div id="' . $id . '" class="float-end">
            <span class="text-secondary btn-action" title="Ubah" onclick="editItem(event)">
                <i class="bi bi-pencil-square"></i></span>
            <label class="divider">|</label>
            <span class="text-danger btn-action" title="Hapus" onclick="deleteItem(event)">
                <i class="bi bi-trash"></i></span>
        </div>';
    }

    private function doUpload($file)
    {
        $filename = "";
        if ($file) {
            if ($file->isValid() && !$file->hasMoved()) {
                $ext      = $file->getClientExtension();
                $filename = time() . bin2hex(random_bytes(5)) . '.' . $ext;
                $file->move(FCPATH . 'uploads/bukti', $filename, true);
            }
        }
        return $filename;
    }
}
