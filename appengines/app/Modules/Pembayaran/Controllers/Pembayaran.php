<?php

namespace Modules\Pembayaran\Controllers;

use App\Controllers\BaseController;
use App\Models\MyModel;

class Pembayaran extends BaseController
{
    private $table = 'simlab_t_pembayaran';
    private $id = 'bayarKode';

    public function index()
    {
        $data = [
            'title' => 'Pembayaran',
        ];
        return view('Modules\Pembayaran\Views\v_pembayaran', $data);
    }

    /**
     * Get data list untuk tabel - menampilkan pembayaran yang sudah upload bukti bayar
     */
    public function dataList()
    {
        $model = new MyModel($this->table);
        $data = [];

        // JOIN dengan simlab_t_layanan untuk mendapatkan data layanan dan nama pemesan
        $joins = [
            'simlab_t_layanan' => 'simlab_t_pembayaran.bayarLnKode = simlab_t_layanan.lnKode'
        ];

        $select = 'simlab_t_pembayaran.*, simlab_t_layanan.lnKode, simlab_t_layanan.lnOrangNama, simlab_t_layanan.lnNoTransaksi';

        // Filter: hanya yang sudah upload bukti bayar (bayarBuktiFile not null)
        $where = ['simlab_t_pembayaran.bayarBuktiFile IS NOT NULL' => null];

        $orderBy = ['simlab_t_pembayaran.bayarKode' => 'DESC'];

        $list = $model->getAllDataByJoinWithOrder($joins, $where, $orderBy, $select, 'inner');

        $no = 1;
        foreach ($list as $row) {
            $encrypted_id = bin2hex(service('encrypter')->encrypt($row->bayarKode));

            // Status badge
            $status = $this->formatStatus($row->bayarStatus);

            // Tombol aksi verifikasi
            $aksi = $this->aksiButton($encrypted_id, $row->bayarStatus);

            // Bukti bayar button
            $buktiBayar = $this->buktiBayarButton($row->bayarBuktiFile);

            // Gunakan indexed array, bukan associative array
            $response = [];
            $response[] = $row->bayarInvoiceNo ?? '<span class="badge bg-warning">Belum Ada</span>';
            $response[] = $row->lnOrangNama ?? '-';
            $response[] = 'Rp ' . number_format($row->bayarTotalBiaya ?? 0, 0, ',', '.');
            $response[] = !empty($row->bayarInvoiceFile) ? '<a href="' . base_url('uploads/' . $row->bayarInvoiceFile) . '" target="_blank" class="btn btn-sm btn-info"><i class="bi bi-file-pdf"></i> Invoice</a>' : '<span class="text-muted">-</span>';
            $response[] = $buktiBayar;
            $response[] = $status;
            $response[] = $aksi;

            $data[] = $response;
        }

        return $this->response->setJSON(["items" => $data]);
    }

    /**
     * Format status badge
     */
    private function formatStatus($status)
    {
        switch ($status) {
            case 0:
                return '<span class="badge bg-warning">Menunggu Verifikasi</span>';
            case 1:
                return '<span class="badge bg-success">Lunas</span>';
            case 2:
                return '<span class="badge bg-info">Pending</span>';
            default:
                return '<span class="badge bg-secondary">Unknown</span>';
        }
    }

    /**
     * Tombol untuk melihat bukti bayar
     */
    private function buktiBayarButton($file)
    {
        if (!empty($file) && $file !== 'by_admin') {
            return '<a href="' . base_url('uploads/' . $file) . '" target="_blank" class="btn btn-sm btn-success">
                        <i class="bi bi-file-image"></i> Lihat Bukti
                    </a>';
        }
        return '<span class="text-muted">Tidak ada</span>';
    }

    /**
     * Tombol aksi verifikasi
     */
    private function aksiButton($id, $status)
    {
        $html = '<div class="btn-group" role="group">';

        // Tombol Verifikasi - hanya muncul jika status bukan lunas (1)
        if ($status != 1) {
            $html .= '<button type="button" class="btn btn-sm btn-primary" onclick="verifikasiItem(\'' . $id . '\')" title="Verifikasi Pembayaran">
						<i class="bi bi-check-circle"></i> Verifikasi
					  </button>';
        } else {
            $html .= '<span class="badge bg-secondary"><i class="bi bi-check-all"></i> Terverifikasi</span>';
        }

        $html .= '</div>';

        return $html;
    }

    /**
     * Verifikasi pembayaran - ubah status jadi lunas
     */
    public function verifikasi()
    {
        $id = $this->request->getPost('id');
        $id = service('encrypter')->decrypt(hex2bin($id));

        $model = new MyModel($this->table);

        // Update status pembayaran jadi lunas
        $data = [
            'bayarStatus' => 1 // Status: Lunas
        ];

        $res = $model->updateData($data, $this->id, $id);

        return $this->response->setJSON([
            'status' => $res,
            'message' => $res ? 'Pembayaran berhasil diverifikasi dan status diubah menjadi Lunas' : 'Gagal memverifikasi pembayaran',
            csrf_token() => csrf_hash()
        ]);
    }
}
