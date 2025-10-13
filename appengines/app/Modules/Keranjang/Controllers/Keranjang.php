<?php

namespace Modules\Keranjang\Controllers;

use App\Controllers\BaseController;
use App\Models\MyModel;

class Keranjang extends BaseController
{
    private $sessionKey = 'keranjang';

    public function index()
    {
        $session = session();
        $user_id = $session->get('id_user');

        $modelUser = new MyModel('users');
        $model     = new MyModel('simlab_r_layanan_pengujian');

        $joins = [
            'simlab_r_parameter p' => 'p.paraKode = simlab_r_layanan_pengujian.ujiParaKode',
            'simlab_r_alat a'      => 'a.alatKode = simlab_r_layanan_pengujian.ujiAlatKode'
        ];

        $select = '
            simlab_r_layanan_pengujian.ujiKode,
            simlab_r_layanan_pengujian.ujiBiaya,
            simlab_r_layanan_pengujian.ujiInstansi,
            simlab_r_layanan_pengujian.ujiDiskon,
            simlab_r_layanan_pengujian.ujiLayanan,
            p.paraNama,
            a.alatNama
        ';

        $listUji = $model->getAllDataWithJoinWhereOrder($joins, [], ['ujiKode' => 'ASC'], $select, 'left');

        $data = [
            'title'   => 'Keranjang Layanan',
            'user'    => $modelUser->getDataById('id_user', $user_id),
            'listUji' => $listUji
        ];

        return view('Modules\Keranjang\Views\v_keranjang', $data);
    }

    public function dataList()
    {
        $session   = session();
        $keranjang = $session->get($this->sessionKey) ?? [];
        $data      = array();

        foreach ($keranjang as $idx => $row) {
            $response   = array();

            $layanan    = isset($row['layanan']) ? esc($row['layanan']) : '-';
            $jumlah     = isset($row['jumlah']) ? (int)$row['jumlah'] : 0;
            $keterangan = isset($row['keterangan']) ? esc($row['keterangan']) : '';
            $diskon     = isset($row['diskon']) ? (float)$row['diskon'] : 0;

            $biayaAsli  = isset($row['biaya_asli']) ? (float)$row['biaya_asli'] : 0;
            $biayaTotal = isset($row['biaya']) ? (float)$row['biaya'] : 0;

            $response[] = '<span class="badge bg-primary">' . $layanan . '</span>';

            if ($diskon > 0) {
                $hargaDiskon = $biayaAsli - ($biayaAsli * ($diskon / 100));
                $biayaTampil = '<span style="color:red;text-decoration:line-through;">Rp ' . number_format($biayaAsli, 0, ',', '.') . '</span><br>';
                $biayaTampil .= 'Rp ' . number_format($hargaDiskon, 0, ',', '.');
            } else {
                $biayaTampil = 'Rp ' . number_format($biayaAsli, 0, ',', '.');
            }
            $response[] = $biayaTampil;

            $response[] = $jumlah;
            $response[] = $diskon > 0 ? $diskon . '%' : '-';
            $response[] = 'Rp ' . number_format($biayaTotal, 0, ',', '.');
            $response[] = $keterangan;
            $response[] = $this->aksi($idx);

            $data[] = $response;
        }

        $output = array("items" => $data);
        return $this->response->setJSON($output);
    }

    public function formTambah()
    {
        $model = new MyModel('simlab_r_layanan_pengujian');

        $joins = [
            'simlab_r_parameter p' => 'p.paraKode = simlab_r_layanan_pengujian.ujiParaKode',
            'simlab_r_alat a'      => 'a.alatKode = simlab_r_layanan_pengujian.ujiAlatKode'
        ];

        $select = '
            simlab_r_layanan_pengujian.ujiKode,
            simlab_r_layanan_pengujian.ujiBiaya,
            simlab_r_layanan_pengujian.ujiInstansi,
            simlab_r_layanan_pengujian.ujiDiskon,
            simlab_r_layanan_pengujian.ujiLayanan,
            p.paraNama,
            a.alatNama
        ';

        $listUji = $model->getAllDataWithJoinWhereOrder(
            $joins,
            [],
            ['ujiKode' => 'ASC'],
            $select,
            'left'
        );

        return view('Modules\Keranjang\Views\v_keranjang_form', [
            'listUji' => $listUji
        ]);
    }

    public function submit()
    {
        $session   = session();
        $keranjang = $session->get($this->sessionKey) ?? [];

        $ujiKode       = trim($this->request->getPost('detUjiKode'));
        $jumlahRaw     = $this->request->getPost('detJumlah');
        $keterangan    = trim($this->request->getPost('detKeterangan'));

        if (empty($ujiKode)) {
            return $this->response->setJSON([
                'res' => false,
                'msg'  => 'Kode uji tidak dikirim.',
                'xname' => csrf_token(),
                'xhash' => csrf_hash()
            ]);
        }

        $jumlah = (int)$jumlahRaw;
        if ($jumlah <= 0) {
            return $this->response->setJSON([
                'res' => false,
                'msg'  => 'Jumlah tidak valid.',
                'xname' => csrf_token(),
                'xhash' => csrf_hash()
            ]);
        }

        $model = new MyModel('simlab_r_layanan_pengujian');

        $joins = [
            'simlab_r_parameter p' => 'p.paraKode = simlab_r_layanan_pengujian.ujiParaKode',
            'simlab_r_alat a'      => 'a.alatKode = simlab_r_layanan_pengujian.ujiAlatKode'
        ];

        // menambah dua kolom baru agar ikut tersimpan ke session
        $where  = ['ujiKode' => $ujiKode];
        $select = 'simlab_r_layanan_pengujian.ujiKode, 
                   simlab_r_layanan_pengujian.ujiBiaya,
                   simlab_r_layanan_pengujian.ujiLayanan,
                   simlab_r_layanan_pengujian.ujiInstansi,
                   simlab_r_layanan_pengujian.ujiDiskon,
                   simlab_r_layanan_pengujian.ujiPenyelia,
                   simlab_r_layanan_pengujian.ujiManajerTeknis,
                   p.paraNama, 
                   a.alatNama';

        $row = $model->getOneByJoin($joins, $where, $select);

        if (!$row) {
            return $this->response->setJSON([
                'res' => false,
                'msg'  => 'Data layanan tidak ditemukan (kode uji tidak valid).',
                'xname' => csrf_token(),
                'xhash' => csrf_hash()
            ]);
        }

        $biayaAsli   = isset($row->ujiBiaya) ? (float)$row->ujiBiaya : 0;
        $diskon      = isset($row->ujiDiskon) ? (float)$row->ujiDiskon : 0;
        $biayaSatuan = $biayaAsli;

        if (isset($row->ujiInstansi) && strtoupper($row->ujiInstansi) === 'ULM' && $diskon > 0) {
            $biayaSatuan = $biayaAsli - ($biayaAsli * ($diskon / 100));
        }

        $totalBiaya = ($biayaSatuan * $jumlah) * (1 - ($diskon / 100));

        //  menambah dua field baru ke data session
        $data = [
            'lnKode'            => $row->ujiInstansi,
            'kode'              => $row->ujiKode,
            'layanan'           => $row->ujiLayanan ?? '',
            'biaya'             => $totalBiaya,
            'biaya_asli'        => $biayaAsli,
            'jumlah'            => $jumlah,
            'keterangan'        => $keterangan,
            'diskon'            => $diskon,
            'ujiPenyelia'       => $row->ujiPenyelia ?? null,
            'ujiManajerTeknis'  => $row->ujiManajerTeknis ?? null
        ];

        $keranjang[] = $data;
        $session->set($this->sessionKey, $keranjang);

        return $this->response->setJSON([
            'res'   => true,
            'msg'   => 'Layanan berhasil ditambahkan ke keranjang.',
            'xname' => csrf_token(),
            'xhash' => csrf_hash()
        ]);
    }

    public function checkout()
    {
        $session = session();
        $user_id = $session->get('id_user');

        $modelUser = new \App\Models\MyModel('simlab_account_users');
        $userRow   = $modelUser->getDataById('user_id', $user_id);

        $emailFromDB = $userRow->user_email ?? $session->get('username');
        $nameFromDB  = $userRow->user_name ?? $session->get('nama');
        $identity    = $userRow->user_identity ?? null;

        $keranjang = $session->get($this->sessionKey) ?? [];
        if (empty($keranjang)) {
            return $this->response->setJSON([
                'res' => false,
                'msg' => 'Keranjang masih kosong.',
                'xname' => csrf_token(),
                'xhash' => csrf_hash()
            ]);
        }

        $totalBiaya = array_sum(array_column($keranjang, 'biaya'));

        $modelPembayaran = new \App\Models\MyModel('simlab_t_pembayaran');
        $modelLayanan    = new \App\Models\MyModel('simlab_t_layanan');
        $modelDetil      = new \App\Models\MyModel('simlab_t_layanan_detil');

        $lastKode = $modelLayanan->getMax('lnKode', 'simlab_t_layanan');
        $nextKode = $lastKode ? ($lastKode + 1) : 1;

        $today = date('Y-m-d');
        $countToday = $modelPembayaran->getCountAllbyManyWhere(['DATE(bayarInvoiceTgl)' => $today]);
        $nextNumber = str_pad($countToday + 1, 4, '0', STR_PAD_LEFT);
        $invoiceNo  = 'ULM' . date('Y') . date('m') . $nextNumber;

        $insertId = $modelPembayaran->insertData([
            'bayarLnKode'     => $nextKode,
            'bayarTotalBiaya' => $totalBiaya,
            'bayarStatus'     => 0,
            'bayarInvoiceNo'  => $invoiceNo,
            'bayarInvoiceTgl' => $today,
            'bayarBuktiFile'  => 'by_admin'
        ], true);

        if (!$insertId) {
            return $this->response->setJSON([
                'res' => false,
                'msg' => 'Gagal simpan pembayaran.',
                'xname' => csrf_token(),
                'xhash' => csrf_hash()
            ]);
        }

        $email    = $emailFromDB;
        $tipe     = $this->request->getPost('InTipe') ?: ($identity === 'ULM' ? 'ULM' : 'NONULM');
        $nama     = $this->request->getPost('InOrangNama') ?: $nameFromDB;
        $telp     = $this->request->getPost('InOrangTelp') ?: '-';
        $instansi = $this->request->getPost('InInstansi') ?: 'Tidak diisi';

        // Disesuaikan dengan struktur tabel simlab_t_layanan terbaru (hanya kolom yang ada)
        $modelLayanan->insertData([
            'lnKode'        => $nextKode,
            'user_id'       => $user_id,
            'lnAccEmail'    => $email,
            'lnNoTransaksi' => $invoiceNo,
            'lnTgl'         => date('Y-m-d H:i:s'),
            'lnStatus'      => 1,
            'kuisioner'     => 0
        ]);

        // --- ⬇ Tambahan kolom detPenyelia & detManajerTeknis ---
        foreach ($keranjang as $item) {
            $detil = [
                'detLnKode'         => $nextKode,
                'detUjiKode'        => $item['kode'] ?? null,
                'detBiaya'          => $item['biaya'] ?? null,
                'detKeterangan'     => $item['keterangan'] ?? null,
                'detLayanan'        => $item['layanan'] ?? null,
                'detStatus'         => 1,
                'detFileHasil'      => null,
                'detJenKode'        => null,
                'detPenyelia'       => $item['ujiPenyelia'] ?? null,
                'detManajerTeknis'  => $item['ujiManajerTeknis'] ?? null
            ];

            $res = $modelDetil->insertData($detil);
            if (!$res) {
                $error = $modelDetil->db->error();
                log_message('error', ' Insert gagal ke simlab_t_layanan_detil. Data: ' . json_encode($detil));
                log_message('error', ' DB Error: ' . json_encode($error));

                return $this->response->setJSON([
                    'res' => false,
                    'msg' => 'Checkout gagal saat simpan detail: ' . ($error['message'] ?? 'Unknown error'),
                    'xname' => csrf_token(),
                    'xhash' => csrf_hash()
                ]);
            }
        }

        $session->remove($this->sessionKey);

        return $this->response->setJSON([
            'res' => true,
            'msg' => 'Checkout berhasil! Nomor Invoice: ' . $invoiceNo,
            'invoiceNo' => $invoiceNo,
            'xname' => csrf_token(),
            'xhash' => csrf_hash()
        ]);
    }

    public function delete($id)
    {
        $session   = session();
        $keranjang = $session->get($this->sessionKey) ?? [];

        if (!isset($keranjang[$id])) {
            return $this->response->setJSON([
                'res'   => false,
                'msg'   => 'Item tidak ditemukan.',
                'xname' => csrf_token(),
                'xhash' => csrf_hash()
            ]);
        }

        unset($keranjang[$id]);
        $keranjang = array_values($keranjang);
        $session->set($this->sessionKey, $keranjang);

        return $this->response->setJSON([
            'res'   => true,
            'msg'   => 'Item berhasil dihapus.',
            'xname' => csrf_token(),
            'xhash' => csrf_hash()
        ]);
    }

    private function aksi($id)
    {
        return '<div id="item-' . $id . '" class="float-end">
            <span data-index="' . $id . '" 
                class="text-danger btn-action btn-delete-item" 
                title="Hapus" 
                onclick="deleteItem(event)">
                <i class="bi bi-trash"></i>
            </span>
        </div>';
    }
}
