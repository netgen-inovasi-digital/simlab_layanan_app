<?php

namespace Modules\Tagihan\Controllers;

use App\Controllers\BaseController;
use App\Models\MyModel;

class Tagihan extends BaseController
{
    private $table = 'simlab_t_pembayaran';
    private $id = 'bayarKode';

    public function index()
    {
        $data = [
            'title' => 'Tagihan',
        ];
        return view('Modules\Tagihan\Views\v_tagihan', $data);
    }

    /**
     * Get data list untuk tabel - menampilkan semua tagihan
     */
    public function dataList()
    {
        $model = new MyModel($this->table);
        $data = [];

        // JOIN dengan simlab_t_layanan untuk mendapatkan data layanan dan nama pemesan
        $joins = [
            'simlab_t_layanan' => 'simlab_t_pembayaran.bayarLnKode = simlab_t_layanan.lnKode'
        ];

        $select = 'simlab_t_pembayaran.*, simlab_t_layanan.lnKode, simlab_t_layanan.lnOrangNama';

        $orderBy = ['simlab_t_pembayaran.bayarKode' => 'DESC'];

        $list = $model->getAllDataByJoinWithOrder($joins, [], $orderBy, $select, 'inner');

        $no = 1;
        foreach ($list as $row) {
            $encrypted_id = bin2hex(service('encrypter')->encrypt($row->bayarKode));

            // Status badge
            $status = $this->formatStatus($row->bayarStatus);

            // Tombol aksi
            $aksi = $this->aksiButton($encrypted_id, $row->bayarStatus, $row->bayarInvoiceFile ?? '');

            // Gunakan indexed array, bukan associative array
            $response = [];
            $response[] = $row->bayarInvoiceNo ?? '<span class="badge bg-warning">Belum Ada</span>';
            $response[] = $row->lnOrangNama ?? '-';
            $response[] = 'Rp ' . number_format($row->bayarTotalBiaya ?? 0, 0, ',', '.');
            $response[] = !empty($row->bayarInvoiceFile) ? '<a href="' . base_url('uploads/' . $row->bayarInvoiceFile) . '" target="_blank" class="btn btn-sm btn-info"><i class="bi bi-file-pdf"></i> Lihat</a>' : '<span class="text-muted">Belum upload</span>';
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
                return '<span class="badge bg-warning">Belum Diproses</span>';
            case 1:
                return '<span class="badge bg-success">Terkirim</span>';
            default:
                return '<span class="badge bg-secondary">Unknown</span>';
        }
    }

    /**
     * Tombol aksi
     */
    private function aksiButton($id, $status, $file)
    {
        $html = '<div class="btn-group" role="group">';

        // Tombol Upload File Invoice (jika belum ada file)
        if (empty($file)) {
            $html .= '<button type="button" class="btn btn-sm btn-primary" onclick="uploadFile(\'' . $id . '\')" title="Upload Invoice">
						<i class="bi bi-upload"></i>
					  </button>';
        }

        // Tombol Proses (tambah nomor invoice) - hanya jika belum diproses
        if ($status == 0) {
            $html .= '<button type="button" class="btn btn-sm btn-success" onclick="prosesItem(\'' . $id . '\')" title="Proses & Kirim">
						<i class="bi bi-check-circle"></i>
					  </button>';
        }

        // Tombol Hapus
        $html .= '<button type="button" class="btn btn-sm btn-danger" onclick="deleteItem(\'' . $id . '\')" title="Hapus">
					<i class="bi bi-trash"></i>
				  </button>';

        $html .= '</div>';

        return $html;
    }

    /**
     * Upload file invoice (PDF)
     */
    public function upload()
    {
        $id = $this->request->getPost('id');
        $id = service('encrypter')->decrypt(hex2bin($id));

        $model = new MyModel($this->table);

        // Validasi file
        $file = $this->request->getFile('file_invoice');
        if (!$file || !$file->isValid()) {
            return $this->response->setJSON([
                'status' => false,
                'message' => 'File tidak valid',
                csrf_token() => csrf_hash()
            ]);
        }

        // Validasi tipe file (hanya PDF)
        $allowedTypes = ['application/pdf'];
        if (!in_array($file->getMimeType(), $allowedTypes)) {
            return $this->response->setJSON([
                'status' => false,
                'message' => 'File harus berformat PDF',
                csrf_token() => csrf_hash()
            ]);
        }

        // Validasi ukuran file (max 5MB)
        if ($file->getSize() > 5242880) {
            return $this->response->setJSON([
                'status' => false,
                'message' => 'Ukuran file maksimal 5MB',
                csrf_token() => csrf_hash()
            ]);
        }

        // Hapus file lama jika ada
        $oldData = $model->getDataById($this->id, $id);
        if ($oldData && !empty($oldData->bayarInvoiceFile)) {
            $oldFilePath = ROOTPATH . 'uploads/' . $oldData->bayarInvoiceFile;
            if (file_exists($oldFilePath)) {
                unlink($oldFilePath);
            }
        }

        // Generate nama file unik
        $newName = $file->getRandomName();

        // Upload file ke folder uploads
        $file->move(ROOTPATH . 'uploads', $newName);

        // Update database
        $data = [
            'bayarInvoiceFile' => $newName
        ];

        $res = $model->updateData($data, $this->id, $id);

        return $this->response->setJSON([
            'status' => $res,
            'message' => $res ? 'File invoice berhasil diupload' : 'Gagal upload file',
            csrf_token() => csrf_hash()
        ]);
    }

    /**
     * Proses tagihan - tambah nomor invoice dan ubah status jadi terkirim
     */
    public function proses()
    {
        $id = $this->request->getPost('id');
        $noInvoice = $this->request->getPost('no_invoice');

        $id = service('encrypter')->decrypt(hex2bin($id));

        $model = new MyModel($this->table);

        // Validasi nomor invoice
        if (empty($noInvoice)) {
            return $this->response->setJSON([
                'status' => false,
                'message' => 'Nomor invoice harus diisi',
                csrf_token() => csrf_hash()
            ]);
        }

        // Cek apakah nomor invoice sudah ada
        $cekInvoice = $model->getDataByArray(['bayarInvoiceNo' => $noInvoice]);
        if ($cekInvoice) {
            return $this->response->setJSON([
                'status' => false,
                'message' => 'Nomor invoice sudah digunakan',
                csrf_token() => csrf_hash()
            ]);
        }

        // Update data
        $data = [
            'bayarInvoiceNo' => $noInvoice,
            'bayarStatus' => 1 // Status: Terkirim
        ];

        $res = $model->updateData($data, $this->id, $id);

        return $this->response->setJSON([
            'status' => $res,
            'message' => $res ? 'Tagihan berhasil diproses dan dikirim' : 'Gagal memproses tagihan',
            csrf_token() => csrf_hash()
        ]);
    }

    /**
     * Hapus tagihan beserta file invoice
     */
    public function delete()
    {
        $id = $this->request->getPost('id');
        $id = service('encrypter')->decrypt(hex2bin($id));

        $model = new MyModel($this->table);

        // Ambil data untuk hapus file invoice jika ada
        $data = $model->getDataById($this->id, $id);
        if ($data && !empty($data->bayarInvoiceFile)) {
            $filePath = ROOTPATH . 'uploads/' . $data->bayarInvoiceFile;
            if (file_exists($filePath)) {
                unlink($filePath);
            }
        }

        $res = $model->deleteData($this->id, $id);

        return $this->response->setJSON([
            'status' => $res,
            'message' => $res ? 'Tagihan berhasil dihapus' : 'Gagal menghapus tagihan',
            csrf_token() => csrf_hash()
        ]);
    }

    function submit()
    {
        // Method submit sudah tidak dipakai untuk modul tagihan
        // Silakan hapus jika tidak diperlukan
        return $this->response->setJSON([
            'status' => false,
            'message' => 'Method ini tidak tersedia',
            csrf_token() => csrf_hash()
        ]);
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
