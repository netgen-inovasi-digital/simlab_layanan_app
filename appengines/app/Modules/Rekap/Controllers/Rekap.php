<?php

namespace Modules\Rekap\Controllers;

use App\Controllers\BaseController;
use App\Models\MyModel;

class Rekap extends BaseController
{
    private $table_pembayaran = 'simlab_t_pembayaran';
    private $table_layanan = 'simlab_t_layanan';
    private $table_detil = 'simlab_t_layanan_detil';
    private $table_jenis = 'simlab_r_jenis';

    public function index()
    {
        $session = session();
        $user_id = $session->get('id_user');

        $model_user = new MyModel('users');
        $model_jenis = new MyModel($this->table_jenis);

        $data = [
            'title' => 'Rekap Pembayaran',
            'user' => $model_user->getDataById('id_user', $user_id),
            'jenis_layanan_options' => $model_jenis->getAllData('jenKode', 'ASC'),
        ];

        return view('Modules\Rekap\Views\v_rekap', $data);
    }

    /**
     * Endpoint untuk AJAX men-generate ringkasan (dipakai oleh tampilan)
     * Mengembalikan JSON { success: bool, data: { ulm, non_ulm } }
     */
    public function dataList()
    {
        $jenis_layanan = $this->request->getGet('jenis_layanan');
        $tanggal_awal = $this->request->getGet('tanggal_awal');
        $tanggal_akhir = $this->request->getGet('tanggal_akhir');

        $db = \Config\Database::connect();
        $builder = $db->table($this->table_pembayaran . ' p');
        $builder->select("
            SUM(CASE WHEN l.lnTipe = 'ULM' THEN p.bayarTotalBiaya ELSE 0 END) AS total_ulm,
            SUM(CASE WHEN l.lnTipe != 'ULM' THEN p.bayarTotalBiaya ELSE 0 END) AS total_non_ulm
        ");
        $builder->join($this->table_layanan . ' l', 'l.lnKode = p.bayarLnKode', 'inner');
        $builder->join($this->table_detil . ' d', 'd.detLnKode = l.lnKode', 'left');

        if (!empty($jenis_layanan) && $jenis_layanan !== 'semua') {
            $builder->where('d.detJenKode', $jenis_layanan);
        }

        if (!empty($tanggal_awal) && !empty($tanggal_akhir)) {
            $builder->where('p.bayarInvoiceTgl >=', $tanggal_awal);
            $builder->where('p.bayarInvoiceTgl <=', $tanggal_akhir);
        }

        $result = $builder->get()->getRow();

        if (!$result || ($result->total_ulm == 0 && $result->total_non_ulm == 0)) {
            return $this->response->setJSON(['success' => false, 'message' => 'Data tidak ditemukan']);
        }

        $rekap = [
            'ulm' => $this->hitungPembagian($result->total_ulm),
            'non_ulm' => $this->hitungPembagian($result->total_non_ulm),
        ];

        // Menyesuaikan gaya respons JSON seperti di template
        $response_data = [
            'success' => true,
            'data' => $rekap,
            'xname' => csrf_token(),
            'xhash' => csrf_hash(),
        ];

        return $this->response->setJSON($response_data);
    }

    /**
     * Hitung pembagian biaya (persist dengan proporsi yang sudah ditetapkan)
     */
    private function hitungPembagian($total)
    {
        $total = (float)$total;
        return [
            'total_pembayaran' => $total,
            'bahan_kimia' => $total * 0.35,
            'operasional' => $total * 0.10,
            'jasa_profesi' => $total * 0.45,
            'pendapatan_instansi' => $total * 0.10,
        ];
    }

    /**
     * Download Excel (dummy function)
     */
    public function download()
    {
        // Logika untuk download Excel seharusnya ada di sini
        return view('Modules\\Rekap\\Views\\v_download_rekap');
    }
}