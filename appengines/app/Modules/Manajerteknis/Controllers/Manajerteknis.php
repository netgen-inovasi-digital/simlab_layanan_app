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

        // Hapus semua relasi manajer teknis-layanan di tabel r_tim
        $db->table('r_tim')->where('user_id', $id)->delete();

        // Hapus akun Manajer Teknis dari simlab_account
        $model = new MyModel($this->table);
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

            // Hitung jumlah layanan dari tabel r_tim
            $count = $db->table('r_tim')
                        ->where('user_id', $row->user_id)
                        ->countAllResults();

            $aktif = $row->status_user == 1
                ? '<small><i class="bi bi-check-circle text-primary"></i> Aktif</small>'
                : '<small class="text-danger"><i class="bi bi-x-circle"></i> Tidak Aktif</small>';

            $data[] = [
                $row->username,
                $row->nama,
                $aktif,
                '<button class="btn btn-sm btn-info" onclick="lihatLayanan(\''.$id.'\')">
                    <i class="bi bi-eye"></i> '.$count.' layanan
                 </button>'
            ];
        }

        return $this->response->setJSON(['items' => $data]);
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
        
        // Hapus relasi dari tabel r_tim
        $res = $db->table('r_tim')
                  ->where('uji_kode', $ujiKode)
                  ->delete();

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

        $builder = $db->table('r_layanan_pengujian')
                      ->select('kode, nama_layanan')
                      ->orderBy('nama_layanan', 'ASC');

        if ($search !== '') {
            $builder->like('nama_layanan', $search);
        }

        $layanan = $builder->get()->getResult();

        // Ambil semua layanan yang sudah dikelola manajer teknis ini
        $assignedLayanan = $db->table('r_tim')
                              ->select('uji_kode')
                              ->where('user_id', $user_id)
                              ->get()
                              ->getResultArray();
        $assignedKodes = array_column($assignedLayanan, 'uji_kode');

        $items = [];
        foreach ($layanan as $l) {
            $idEnc = bin2hex($this->encrypter->encrypt($l->kode));
            $isAssigned = in_array($l->kode, $assignedKodes);

            if ($isAssigned) {
                $items[] = [
                    'nama'   => $l->nama_layanan,
                    'status' => '<span class="text-primary"><i class="bi bi-check-circle"></i> Sudah Dikelola</span>',
                    'aksi'   => '<button class="btn btn-sm btn-danger" data-id="'.$idEnc.'" onclick="deleteItem(event)">
                                    <i class="bi bi-trash"></i> Hapus
                                </button>'
                ];
            } else { 
                $items[] = [
                    'nama'   => $l->nama_layanan,
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

        // Cek apakah layanan ada di r_layanan_pengujian
        $model = new MyModel('r_layanan_pengujian');
        $exists = $model->getDataById('kode', $idLayanan);
        if (!$exists) {
            return $this->response->setJSON([
                'res' => 'fail',
                'msg' => 'Layanan tidak ditemukan!',
                'xname' => csrf_token(),
                'xhash' => csrf_hash()
            ]);
        }

        // Cek apakah relasi sudah ada di r_tim
        $db = \Config\Database::connect();
        $existing = $db->table('r_tim')
                       ->where('uji_kode', $idLayanan)
                       ->where('user_id', $idManajer)
                       ->get()
                       ->getRow();

        if ($existing) {
            return $this->response->setJSON([
                'res' => 'fail',
                'msg' => 'Layanan sudah ditambahkan ke Manajer Teknis ini!',
                'xname' => csrf_token(),
                'xhash' => csrf_hash()
            ]);
        }

        // Insert relasi baru ke tabel r_tim
        $timModel = new MyModel('r_tim');
        $res = $timModel->insertData([
            'uji_kode' => $idLayanan,
            'user_id'  => $idManajer
        ]);

        return $this->response->setJSON([
            'res' => $res ? 'ok' : 'fail',
            'msg' => $res ? 'Layanan berhasil ditambahkan!' : 'Gagal menambahkan layanan.',
            'xname' => csrf_token(),
            'xhash' => csrf_hash()
        ]);
    }
}
