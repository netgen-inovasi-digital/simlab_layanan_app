<?php

namespace Modules\KuisionerResponse\Controllers;

use App\Controllers\BaseController;

class KuisionerResponse extends BaseController
{
    protected $encrypter;

    public function __construct()
    {
        $this->encrypter = service('encrypter');
    }

    public function index()
    {
        $data = [
            'title' => 'Hasil Kuisioner Pelanggan'
        ];

        return view('Modules\\KuisionerResponse\\Views\\v_response_kuisioner', $data);
    }

    public function dataList()
    {
        $db = \Config\Database::connect();

        $builder = $db->table('simlab_t_layanan as l');
        $builder->select('l.lnKode, l.lnNoTransaksi, l.lnTgl, l.user_id, l.lnAccEmail, l.jumlah_kaji_ulang');
        $builder->select('GROUP_CONCAT(DISTINCT d.nama_layanan ORDER BY d.nama_layanan SEPARATOR ", ") as layanan_nama');
        $builder->select('u.user_name, u.user_email, u.user_identity, u.user_instansi');
        $builder->select('COUNT(DISTINCT jawab.id_jawaban) as total_jawaban');
        $builder->join('simlab_t_kuesioner_jawaban as jawab', 'jawab.kode_layanan = l.lnKode', 'inner');
        $builder->join('t_layanan_detil as d', 'd.kode_layanan = l.lnKode', 'left');
        $builder->join('simlab_account_users as u', 'u.user_id = l.user_id', 'left');
        $builder->where('l.kuisioner', 1);
        $builder->groupBy('l.lnKode, l.lnNoTransaksi, l.lnTgl, l.user_id, l.lnAccEmail, l.jumlah_kaji_ulang, u.user_name, u.user_email, u.user_identity, u.user_instansi');
        $builder->orderBy('l.lnTgl', 'DESC');
        $rows = $builder->get()->getResult();

        if (empty($rows)) {
            return $this->response->setJSON(['items' => []]);
        }

        $data = [];
        foreach ($rows as $row) {
            $encId = bin2hex($this->encrypter->encrypt($row->lnKode));

            $namaLayanan = $row->layanan_nama ?: '-';
            $noTransaksi = $row->lnNoTransaksi ?: '-';
            $colLayanan = '<div class="fw-semibold">' . esc($namaLayanan) . '</div>' .
                '<small class="text-muted">No. Invoice: ' . esc($noTransaksi) . '</small>';

            $data[] = [
                $colLayanan,
                $this->buildPemesanColumn($row),
                $this->buildActionButton($encId)
            ];
        }

        return $this->response->setJSON(['items' => $data]);
    }

    public function detail($id = null)
    {
        if (!$id) {
            return $this->response->setJSON(['success' => false, 'msg' => 'ID tidak valid']);
        }

        try {
            $lnKode = $this->encrypter->decrypt(hex2bin($id));
        } catch (\Throwable $e) {
            try {
                $lnKode = $this->encrypter->decrypt($id);
            } catch (\Throwable $e2) {
                return $this->response->setJSON(['success' => false, 'msg' => 'ID tidak valid']);
            }
        }

        $db = \Config\Database::connect();

        $header = $db->table('simlab_t_layanan as l')
            ->select('l.lnKode, l.lnNoTransaksi, l.lnTgl, u.user_name, u.user_identity, u.user_instansi')
            ->join('simlab_account_users as u', 'u.user_id = l.user_id', 'left')
            ->where('l.lnKode', $lnKode)
            ->get()->getRow();

        if (!$header) {
            return $this->response->setJSON(['success' => false, 'msg' => 'Data layanan tidak ditemukan']);
        }

        $builder = $db->table('simlab_t_kuesioner as q');
        $builder->select('q.kuesioner_id, q.pertanyaan_teks, q.pertanyaan_tipe, q.pertanyaan_wajib, jawab.jawaban, jawab.created_at');
        $builder->join('simlab_t_kuesioner_jawaban as jawab', 'jawab.id_pertanyaan = q.kuesioner_id AND jawab.kode_layanan = ' . (int) $lnKode, 'left');
        $builder->orderBy('q.kuesioner_id', 'ASC');
        $rows = $builder->get()->getResult();

        $items = [];
        foreach ($rows as $index => $row) {
            $items[] = [
                'no' => $index + 1,
                'pertanyaan' => $row->pertanyaan_teks,
                'tipe' => $row->pertanyaan_tipe,
                'jawaban' => $row->jawaban ?? '-',
                'created_at' => $row->created_at
            ];
        }

        return $this->response->setJSON([
            'success' => true,
            'meta' => [
                'no_transaksi' => $header->lnNoTransaksi ?? '-',
                'pemesan' => $header->user_name ?? '-'
            ],
            'items' => $items
        ]);
    }

    private function buildPemesanColumn($row): string
    {
        $pemesanNama = $row->user_name ?: ($row->user_email ?? $row->lnAccEmail ?? '-');
        $tanggal = !empty($row->lnTgl) ? date('d-m-Y H:i', strtotime($row->lnTgl)) : '-';
        $identity = strtoupper($row->user_identity ?? 'NON ULM');

        return '<div style="line-height:1.3;">'
            . '<span class="fw-semibold">' . esc($pemesanNama) . '</span><br>'
            . '<small class="text-muted">' . esc($tanggal) . ' | ' . esc($identity) . '</small>'
            . '</div>';
    }

    private function buildActionButton(string $encId): string
    {
        return '<button type="button" class="btn btn-sm btn-outline-primary" '
            . 'onclick="showResponseModal(\'' . $encId . '\')">'
            . '<i class="bi bi-eye"></i> Lihat</button>';
    }
}
