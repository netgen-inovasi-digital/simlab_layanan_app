<?php

namespace Modules\HasilPengujian\Controllers;

use App\Controllers\BaseController;
use App\Models\MyModel;

class HasilPengujian extends BaseController
{
    private $table = 'simlab_t_layanan';
    private $id    = 'lnKode';

    /**
     * Halaman utama modul Hasil Pengujian
     */
    public function index()
    {
        $session = session();
        $user_id = $session->get('id_user');

        $modelUser = new MyModel('simlab_account_users');

        $data = [
            'title' => 'Data Hasil Pengujian',
            'user'  => $modelUser->getDataById('user_id', $user_id),
        ];

        return view('Modules\HasilPengujian\Views\v_hasilPengujian', $data);
    }

    /**
     * Mengambil data list hasil pengujian dalam format JSON untuk DataTable
     */
    public function dataList()
    {
        $model = new MyModel($this->table);
        $data  = [];

        // Ambil semua data dari tabel utama dan urutkan berdasarkan tanggal DESC
        $list = $model->getAllDataWithOrder(['lnTgl' => 'DESC']);

        // Kelompokkan berdasarkan status 0-9
        $grouped = [];
        for ($i = 0; $i <= 9; $i++) {
            $grouped[$i] = [];
        }

        foreach ($list as $row) {
            $status = (int) $row->lnStatus;
            if (!isset($grouped[$status])) {
                $grouped[$status] = [];
            }
            $grouped[$status][] = $row;
        }

        // Urutkan data berdasarkan status 0 → 9
        $finalList = [];
        for ($i = 0; $i <= 9; $i++) {
            $finalList = array_merge($finalList, $grouped[$i]);
        }

        $no = 1; // nomor urut
        foreach ($finalList as $row) {

            // ✅ hanya tampilkan jika status >= 4
            if ((int) $row->lnStatus < 4) {
                continue;
            }

            $id = bin2hex($this->encrypter->encrypt($row->lnKode));
            $response = [];

            // Ambil item layanan dari tabel detail
            $modelDet = new MyModel('simlab_t_layanan_detil');
            $detil = $modelDet->getAllDataById(['detLnKode' => $row->lnKode]);

            $items = [];
            foreach ($detil as $d) {
                $items[] = $d->detLayanan ?? $d->detJenKode;
            }
            $itemList = !empty($items) ? implode(', ', $items) : '-';

            // ============================
            // ====== Kolom Tabel =========
            // ============================


            // Kolom 2: No. Invoice & Tanggal
            $noInvoiceTgl = '<div>'
                          . ($row->lnNoTransaksi ?? '-') . '<br>'
                          . (!empty($row->lnTgl) ? date('d-m-Y', strtotime($row->lnTgl)) : '-') 
                          . '</div>';
            $response[] = $noInvoiceTgl;

            // Kolom 3: Nama Layanan
            $response[] = '<div>' . $itemList . '</div>';

            // Kolom 4: LHUS (Tinjau) → tombol lihat file (placeholder)
            $lhusBtn = '<button class="btn btn-sm btn-secondary" disabled>Lihat File</button>';
            $response[] = $lhusBtn;

            // Kolom 5: Status
            $response[] = $this->formatStatus($row->lnStatus);

		// Kolom 6: Aksi (Unggah LHUS + Lihat Detail)
		$unggahBtn = '
			<button class="btn btn-sm btn-primary me-1" title="Unggah LHUS" disabled>
				<i class="bi bi-upload"></i>
			</button>
		';

		$lihatDetailBtn = '
			<a href="javascript:void(0)" onclick="loadDetail(\'' . $id . '\')" 
			class="btn btn-sm btn-info" title="Lihat Detail">
				<i class="bi bi-eye"></i>
			</a>
		';

		$response[] = $unggahBtn . $lihatDetailBtn;



            // Masukkan data ke array utama
            $data[] = $response;
        }

        return $this->response->setJSON(["items" => $data]);
    }

    /**
     * Format status dengan badge warna
     */
    private function formatStatus($status)
    {
        switch ($status) {
            case 0: return '<span class="badge bg-secondary">Draft</span>';
            case 1: return '<span class="badge bg-warning">In Review (Manajer)</span>';
            case 2: return '<span class="badge bg-danger">Ditolak</span>';
            case 3: return '<span class="badge bg-info">In Review (Admin)</span>';
            case 4: return '<span class="badge bg-primary">Menunggu Hasil Uji</span>';
            case 5: return '<span class="badge bg-primary">Menunggu Verifikasi Manajer Teknis</span>';
            case 6: return '<span class="badge bg-success">LHUS Disetujui</span>';
            case 7: return '<span class="badge bg-primary">Memproses LHU</span>';
            case 8: return '<span class="badge bg-success">LHU Disetujui</span>';
            case 9: return '<span class="badge bg-dark">Pengujian Selesai</span>';
            default: return '<span class="badge bg-dark">Unknown</span>';
        }
    }
}
