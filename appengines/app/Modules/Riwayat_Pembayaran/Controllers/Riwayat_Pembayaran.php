<?php

namespace Modules\Riwayat_Pembayaran\Controllers;

use App\Controllers\BaseController;
use App\Models\MyModel;

class Riwayat_Pembayaran extends BaseController
{
    private $table = 'simlab_t_pembayaran';
    private $id = 'bayarKode';

    public function index()
    {
        $session = session();
        $user_id = $session->get('id_user');

        $modelUser = new MyModel('users');

        $data = [
            'title' => 'Riwayat Pembayaran',
            'user' => $modelUser->getDataById('id_user', $user_id),
        ];

        return view('Modules\Riwayat_Pembayaran\Views\v_riwayat_pembayaran', $data);
    }

    function edit($id)
    {
        $idenc = $id;
        $id = service('encrypter')->decrypt(hex2bin($id));
        $model = new MyModel($this->table);
        $get = $model->getDataById($this->id, $id);

        $data[csrf_token()] = csrf_hash();
        $data['id'] = $idenc;
        $data['bayarLnKode'] = $get->bayarLnKode ?? '';
        $data['bayarJumlah'] = $get->bayarJumlah ?? '';
        $data['bayarTgl'] = $get->bayarTgl ?? '';
        $data['bayarStatus'] = $get->bayarStatus ?? '';

        return $this->response->setJSON($data);
    }

    function delete($id)
    {
        $id = service('encrypter')->decrypt(hex2bin($id));
        $model = new MyModel($this->table);
        $res = $model->deleteData($this->id, $id);
        return $this->response->setJSON(array('res' => $res, 'xname' => csrf_token(), 'xhash' => csrf_hash()));
    }

    public function submit()
    {
        $idenc = $this->request->getPost('id');
        $data = array(
            'bayarLnKode' => $this->request->getPost('bayarLnKode'),
            'bayarJumlah' => $this->request->getPost('bayarJumlah'),
            'bayarTgl' => $this->request->getPost('bayarTgl'),
            'bayarStatus' => $this->request->getPost('bayarStatus'),
        );

        $model = new MyModel($this->table);

        if ($idenc == "") {
            $res = $model->insertData($data);
        } else {
            $id = service('encrypter')->decrypt(hex2bin($idenc));
            $res = $model->updateData($data, $this->id, $id);
        }
        return $this->response->setJSON(array('res' => $res, 'xname' => csrf_token(), 'xhash' => csrf_hash()));
    }

    public function dataList()
    {
        $db = \Config\Database::connect();
        $data = array();

        // Query dengan JOIN untuk mendapatkan nama customer
        $builder = $db->table($this->table . ' a');
        $builder->select('a.bayarKode, a.bayarInvoiceNo, a.bayarLnKode, b.user_name as customerName, a.bayarTotalBiaya, a.bayarInvoiceFile, a.bayarBuktiFile, a.bayarStatus');
        $builder->join('simlab_account_users b', 'b.user_id = a.bayarLnKode', 'left');
        $list = $builder->get()->getResult();

        $no = 1;
        foreach ($list as $row) {
            $id = bin2hex(service('encrypter')->encrypt($row->bayarKode));
            $response = array();

            // No. Invoice
            $response[] = '<span class="badge bg-info">' . esc($row->bayarInvoiceNo ?? '-') . '</span>';

            // Pemesan (nama customer dari join atau tampilkan bayarLnKode jika tidak ada)
            $response[] = esc($row->customerName ?? $row->bayarLnKode ?? '-');

            // Tagihan
            $response[] = 'Rp' . number_format($row->bayarTotalBiaya ?? 0, 0, ',', '.');

            // Invoice file button
            $invoiceFile = $row->bayarInvoiceFile ?? '';
            if (!empty($invoiceFile) && $invoiceFile !== null) {
                $response[] = '<a href="' . base_url('uploads/' . $invoiceFile) . '" target="_blank" class="btn btn-sm btn-outline-primary">
                    <i class="bi bi-file-earmark-pdf"></i> Lihat Invoice
                </a>';
            } else {
                $response[] = '<span class="text-muted">-</span>';
            }

            // Bukti bayar file button
            $buktiFile = $row->bayarBuktiFile ?? '';
            if (!empty($buktiFile) && $buktiFile !== 'by_admin' && $buktiFile !== null) {
                $response[] = '<a href="' . base_url('uploads/' . $buktiFile) . '" target="_blank" class="btn btn-sm btn-outline-success">
                    <i class="bi bi-file-earmark-image"></i> Lihat Bukti
                </a>';
            } else {
                $response[] = '<span class="text-muted">-</span>';
            }

            // Status
            $status = $row->bayarStatus ?? '0';
            $statusText = '';
            $statusClass = '';

            switch ($status) {
                case '1':
                    $statusText = 'Lunas';
                    $statusClass = 'success';
                    break;
                case '2':
                    $statusText = 'Pending';
                    $statusClass = 'warning';
                    break;
                default:
                    $statusText = 'Belum Bayar';
                    $statusClass = 'danger';
                    break;
            }

            $response[] = '<span class="badge bg-' . $statusClass . '">' . $statusText . '</span>';

            // Aksi
            $response[] = $this->aksi($id);

            $data[] = $response;
        }

        $output = array("items" => $data);
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
