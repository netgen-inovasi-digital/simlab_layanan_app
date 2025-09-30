<?php

namespace Modules\ManajerTeknis\Controllers;

use App\Controllers\BaseController;
use App\Models\MyModel;

class ManajerTeknis extends BaseController
{
    private $table = 'simlab_account';
    private $id = 'username';

    public function index()
    {
        $data = [
            'title' => 'Data Manajer Teknis'
        ];
        return view('Modules\ManajerTeknis\Views\v_manajerteknis', $data);
    }

    public function edit($id)
    {
        $idenc = $id;
        $id = $this->encrypter->decrypt(hex2bin($id));
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
        $id = $this->encrypter->decrypt(hex2bin($id));
        $model = new MyModel($this->table);
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
        $data = [];

        $list = $model->getAllData();
        $db   = \Config\Database::connect();

        foreach ($list as $row) {
            if ($row->role_id != 4) continue; // hanya role Manajer Teknis

            $id = bin2hex($this->encrypter->encrypt($row->username));
            $response = [];

            $response[] = $row->username;

            // Menampilkan jumlah layanan yang dimiliki manajer teknis
            $count = $db->table('simlab_r_layanan_pengujian')
                        ->where('ujiManajerTeknis', $row->user_id)
                        ->countAllResults();

            $response[] = '<button class="btn btn-sm btn-info" onclick="lihatLayanan(\''.$id.'\')">
                <i class="bi bi-eye"></i> '.$count.' layanan
            </button>';

            $response[] = $row->nama;

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

    // === Fungsi aksi edit dan hapus untuk tabel utama Manajer Teknis ===
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

    public function layanan($id)
    {
        $username = $this->encrypter->decrypt(hex2bin($id));

        $db = \Config\Database::connect();
        $account = $db->table('simlab_account')->where('username', $username)->get()->getRow();

        if (!$account) {
            return $this->response->setJSON([
                'res' => 'notfound',
                'items' => []
            ]);
        }

        // Ambil parameter page dan limit dari query string
        $page  = (int) ($this->request->getGet('page') ?? 1);
        $limit = (int) ($this->request->getGet('limit') ?? 10);
        $offset = ($page - 1) * $limit;

        // Total data
        $total = $db->table('simlab_r_layanan_pengujian')
                    ->where('ujiManajerTeknis', $account->user_id)
                    ->countAllResults();

        // Ambil data dengan limit dan offset
        $layanan = $db->table('simlab_r_layanan_pengujian')
                      ->select('ujiKode, ujiLayanan')
                      ->where('ujiManajerTeknis', $account->user_id)
                      ->limit($limit, $offset)
                      ->get()
                      ->getResult();

        $items = [];
        $no = $offset + 1;
        foreach ($layanan as $l) {
            $idEnc = bin2hex($this->encrypter->encrypt($l->ujiKode));
            $items[] = [
                'no' => $no++,
                'nama' => $l->ujiLayanan,
                'aksi' => '<button class="btn btn-sm btn-danger" onclick="hapusLayanan(\''.$idEnc.'\')">
                                <i class="bi bi-trash"></i> Hapus
                           </button>'
            ];
        }

        // Hitung total halaman
        $totalPages = ceil($total / $limit);

        return $this->response->setJSON([
            'res' => 'ok',
            'items' => $items,
            'pagination' => [
                'page' => $page,
                'total_pages' => $totalPages,
                'total_items' => $total,
                'limit' => $limit
            ],
            'xname' => csrf_token(),
            'xhash' => csrf_hash()
        ]);
    }

    // === Menghapus layanan berdasarkan ujiKode ===
    public function deleteLayanan($id)
    {
        $ujiKode = $this->encrypter->decrypt(hex2bin($id));

        $db = \Config\Database::connect();
        $res = $db->table('simlab_r_layanan_pengujian')->delete(['ujiKode' => $ujiKode]);

        return $this->response->setJSON([
            'res' => $res ? 'ok' : 'fail',
            'xname' => csrf_token(),
            'xhash' => csrf_hash()
        ]);
    }

    public function layananKosong()
    {
        $db = \Config\Database::connect();

        $page   = (int) ($this->request->getGet('page') ?? 1);
        $limit  = (int) ($this->request->getGet('limit') ?? 10);
        $offset = ($page - 1) * $limit;
        $search = $this->request->getGet('search') ?? '';

        $builder = $db->table('simlab_r_layanan_pengujian')
                    ->select('ujiKode, ujiLayanan')
                    ->where('ujiManajerTeknis', null, true);

        if ($search !== '') {
            $builder->like('ujiLayanan', $search);
        }

        // Hitung total data
        $total = (clone $builder)->countAllResults();

        // Ambil data sesuai page & limit
        $layanan = $builder->limit($limit, $offset)->get()->getResult();

        $items = [];
        $no = $offset + 1;
        foreach ($layanan as $l) {
            $idEnc = bin2hex($this->encrypter->encrypt($l->ujiKode));
            $items[] = [
                'no'   => $no++,
                'nama' => $l->ujiLayanan,
                'aksi' => '<button class="btn btn-sm btn-success" onclick="pilihLayanan(\''.$idEnc.'\')">
                              <i class="bi bi-plus-circle"></i> Pilih
                           </button>'
            ];
        }

        $totalPages = ceil($total / $limit);

        return $this->response->setJSON([
            'res' => 'ok',
            'items' => $items,
            'pagination' => [
                'page'        => $page,
                'total_pages' => $totalPages,
                'total_items' => $total,
                'limit'       => $limit
            ],
            'xname' => csrf_token(),
            'xhash' => csrf_hash()
        ]);
    }
}