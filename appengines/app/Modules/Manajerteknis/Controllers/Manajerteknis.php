<?php

namespace Modules\Manajerteknis\Controllers;

use App\Controllers\BaseController;
use App\Models\MyModel;

class Manajerteknis extends BaseController
{
    private $table = 'simlab_account';
    private $id = 'username';

    public function index()
    {
        $data = [
            'title' => 'Data Manajer Teknis',
            'csrf_name' => csrf_token(),
            'csrf_hash' => csrf_hash()
        ];
        return view('Modules\Manajerteknis\Views\v_manajerteknis', $data);
    }

    public function edit($id)
    {
        $idenc = $id;
        try {
            $id = $this->encrypter->decrypt(hex2bin($id));
        } catch (\Exception $e) {
            return $this->response->setJSON([
                'res' => 'fail',
                'msg' => 'ID tidak valid',
                'xname' => csrf_token(),
                'xhash' => csrf_hash()
            ]);
        }

        $model = new MyModel($this->table);
        $get = $model->getDataById($this->id, $id);

        $data[csrf_token()] = csrf_hash();
        $data['id'] = $idenc;
        $data['username'] = $get->username;
        $data['nama'] = $get->nama;
        $data['status'] = $get->status_user;

        return $this->response->setJSON($data);
    }

    public function delete($id)
    {
        try {
            $id = $this->encrypter->decrypt(hex2bin($id));
        } catch (\Exception $e) {
            return $this->response->setJSON([
                'res' => 'fail',
                'msg' => 'ID tidak valid',
                'xname' => csrf_token(),
                'xhash' => csrf_hash()
            ]);
        }

        $db = \Config\Database::connect();

        // Hapus akun Manajer Teknis dari simlab_account
        $model = new MyModel($this->table);
        // Karena di database FK simlab_r_layanan_pengujian.ujiManajerTeknis
        // sudah ON DELETE SET NULL, maka kolom ujiManajerTeknis otomatis jadi NULL
        $res = $model->deleteData('user_id', $id); 

        return $this->response->setJSON([
            'res'   => $res ? 'ok' : 'fail',
            'xname' => csrf_token(),
            'xhash' => csrf_hash()
        ]);
    }

    public function submit()
    {
        $idenc = $this->request->getPost('id');
        $username = $this->request->getPost('username');

        $data = [
            'username'    => $username,
            'nama'        => $this->request->getPost('nama'),
            'status_user' => $this->request->getPost('status'),
        ];

        $password = $this->request->getPost('password');
        if ($password != "") {
            $data['password'] = password_hash($password, PASSWORD_DEFAULT);
        }

        $model = new MyModel($this->table);
        $check = $model->getDataById('username', $username);

        if ($idenc == "") {
            $data['role_id'] = 4; // default Manajer Teknis

            if ($check) {
                $res = 'check';
                $link = 'Username sudah ada!';
            } else {
                $res = $model->insertData($data);
            }
        } else {
            $id = $this->encrypter->decrypt(hex2bin($idenc));
            $current = $model->getDataById($this->id, $id);

            if ($check && $current->username != $username) {
                $res = 'check';
                $link = 'Username sudah ada!';
            } else {
                $res = $model->updateData($data, $this->id, $id);
            }
        }

        return $this->response->setJSON([
            'res' => $res,
            'link' => $link ?? '',
            'xname' => csrf_token(),
            'xhash' => csrf_hash()
        ]);
    }

    public function dataList()
    {
        $model = new MyModel($this->table);
        $list = $model->getAllData();
        $db   = \Config\Database::connect();
        $data = [];

        foreach ($list as $row) {
            if ($row->role_id != 4) continue; // hanya role Manajer Teknis

            $id = bin2hex($this->encrypter->encrypt($row->user_id));

            $count = $db->table('simlab_r_layanan_pengujian')
                        ->where('ujiManajerTeknis', $row->user_id)
                        ->countAllResults();

            $aktif = $row->status_user == 1
                ? '<small><i class="bi bi-check-circle text-primary"></i> Aktif</small>'
                : '<small class="text-danger"><i class="bi bi-x-circle"></i> Tidak Aktif</small>';

            $data[] = [
                $row->username,
                '<button class="btn btn-sm btn-info" onclick="lihatLayanan(\''.$id.'\')">
                    <i class="bi bi-eye"></i> '.$count.' layanan
                 </button>',
                $row->nama,
                $aktif,
                $this->aksi($id)
            ];
        }

        return $this->response->setJSON(['items' => $data]);
    }

    private function aksi($id)
    {
        return '<div id="' . $id . '" class="float-end">
            <span class="text-secondary btn-action" title="Ubah" onclick="editItem(event)">
                <i class="bi bi-pencil-square"></i>
            </span> 
            <label class="divider">|</label>
            <span class="text-danger btn-action" title="Hapus" onclick="deleteManajerteknis(event)">
                <i class="bi bi-trash"></i>
            </span>
        </div>';
    }

    public function deleteLayanan($id)
    {
        try {
            $ujiKode = $this->encrypter->decrypt(hex2bin($id));
        } catch (\Exception $e) {
            return $this->response->setJSON([
                'res' => 'fail',
                'msg' => 'ID tidak valid atau rusak!',
                'xname' => csrf_token(),
                'xhash' => csrf_hash()
            ]);
        }

        $db = \Config\Database::connect();
        $res = $db->table('simlab_r_layanan_pengujian')
                  ->where('ujiKode', $ujiKode)
                  ->update(['ujiManajerTeknis' => null]);

        return $this->response->setJSON([
            'res' => $res ? 'ok' : 'fail',
            'msg' => $res ? 'Layanan berhasil dihapus dari Manajer Teknis!' : 'Gagal menghapus layanan dari Manajer Teknis.',
            'xname' => csrf_token(),
            'xhash' => csrf_hash()
        ]);
    }

    public function layanan($id)
    {
        try {
            $user_id = $this->encrypter->decrypt(hex2bin($id));
        } catch (\Exception $e) {
            return $this->response->setJSON([
                'res' => 'fail',
                'msg' => 'ID Manajer Teknis tidak valid!',
                'xname' => csrf_token(),
                'xhash' => csrf_hash()
            ]);
        }

        $db = \Config\Database::connect();
        $account = $db->table('simlab_account')->where('user_id', $user_id)->get()->getRow();
        if (!$account) {
            return $this->response->setJSON([
                'res' => 'notfound',
                'msg' => 'Manajer Teknis tidak ditemukan!',
                'items' => [],
                'xname' => csrf_token(),
                'xhash' => csrf_hash()
            ]);
        }

        $search = $this->request->getGet('search') ?? '';

        $builder = $db->table('simlab_r_layanan_pengujian')
                      ->select('ujiKode, ujiLayanan, ujiManajerTeknis')
                      ->orderBy('ujiLayanan', 'ASC');

        if ($search !== '') {
            $builder->like('ujiLayanan', $search);
        }

        $layanan = $builder->get()->getResult();

        $items = [];
        foreach ($layanan as $l) {
            if ($l->ujiManajerTeknis !== null && $l->ujiManajerTeknis != $account->user_id) continue;

            $idEnc = bin2hex($this->encrypter->encrypt($l->ujiKode));

            if ($l->ujiManajerTeknis == $account->user_id) {
                $items[] = [
                    'nama'   => $l->ujiLayanan,
                    'status' => '<span class="text-primary"><i class="bi bi-check-circle"></i> Sudah Dikelola</span>',
                    'aksi'   => '<button class="btn btn-sm btn-danger" data-id="'.$idEnc.'" onclick="deleteItem(event)">
                                    <i class="bi bi-trash"></i> Hapus
                                </button>'
                ];
            } else { 
                $items[] = [
                    'nama'   => $l->ujiLayanan,
                    'status' => '<span class="text-muted"><i class="bi bi-dash-circle"></i> Belum Ditambahkan</span>',
                    'aksi'   => '<button class="btn btn-sm btn-success" onclick="pilihLayanan(\''.$idEnc.'\', \''.$id.'\')">
                                    <i class="bi bi-plus-circle"></i> Tambah
                                </button>'
                ];
            }
        }

        usort($items, function($a, $b) {
            $a_flag = strpos($a['status'], 'Sudah Dikelola') !== false ? 1 : 0;
            $b_flag = strpos($b['status'], 'Sudah Dikelola') !== false ? 1 : 0;
            return $b_flag - $a_flag;
        });

        return $this->response->setJSON([
            'res' => 'ok',
            'items' => $items, 
            'xname' => csrf_token(),
            'xhash' => csrf_hash()
        ]);
    }

    public function tambahLayananManajerteknis()
    {
        $data = $this->request->getJSON(true);
        $idLayananEnc = $data['layanan'] ?? null;
        $idManajerEnc = $data['ManajerTeknis'] ?? null;

        if (!$idLayananEnc || !$idManajerEnc) {
            return $this->response->setJSON([
                'res' => 'fail',
                'msg' => 'Data layanan atau Manajer Teknis tidak valid!',
                'xname' => csrf_token(),
                'xhash' => csrf_hash()
            ]);
        }

        try {
            $idLayanan = $this->encrypter->decrypt(hex2bin($idLayananEnc));
            $idManajer = $this->encrypter->decrypt(hex2bin($idManajerEnc));
        } catch (\Exception $e) {
            return $this->response->setJSON([
                'res' => 'fail',
                'msg' => 'ID tidak valid!',
                'xname' => csrf_token(),
                'xhash' => csrf_hash()
            ]);
        }

        $model = new MyModel('simlab_r_layanan_pengujian');
        $exists = $model->getDataById('ujiKode', $idLayanan);
        if (!$exists) {
            return $this->response->setJSON([
                'res' => 'fail',
                'msg' => 'Layanan tidak ditemukan!',
                'xname' => csrf_token(),
                'xhash' => csrf_hash()
            ]);
        }

        if ($exists->ujiManajerTeknis == $idManajer) {
            return $this->response->setJSON([
                'res' => 'fail',
                'msg' => 'Layanan sudah ditambahkan ke Manajer Teknis ini!',
                'xname' => csrf_token(),
                'xhash' => csrf_hash()
            ]);
        }

        $res = $model->updateData(['ujiManajerTeknis' => $idManajer], 'ujiKode', $idLayanan);

        return $this->response->setJSON([
            'res' => $res ? 'ok' : 'fail',
            'msg' => $res ? 'Layanan berhasil ditambahkan!' : 'Gagal menambahkan layanan.',
            'xname' => csrf_token(),
            'xhash' => csrf_hash()
        ]);
    }
}
