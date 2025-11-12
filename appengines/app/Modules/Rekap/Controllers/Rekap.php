<?php

namespace Modules\Rekap\Controllers;

use App\Controllers\BaseController;
use App\Models\MyModel;

require_once APPPATH . 'Libraries/SimpleXLSXGen/src/SimpleXLSXGen.php';

class Rekap extends BaseController
{
    private $table_pembayaran = 't_pembayaran';
    private $table_layanan = 'simlab_t_layanan';
    private $table_detil = 't_layanan_detil'; // BERUBAH
    private $table_jenis = 'simlab_r_jenis'; // ASUMSI TIDAK BERUBAH
    private $table_r_layanan_pengujian = 'r_layanan_pengujian'; // BERUBAH
    // ============================
    private $table_kolom_keuangan = 'simlab_r_kolom_keuangan_detail'; // BARU


    public function index()
    {
        $session = session();
        $user_id = $session->get('id_user');

        $model_user = new MyModel('users');
        $model_jenis = new MyModel($this->table_jenis);

        $data = [
            'title' => 'Rekap Pembayaran',
            'user' => $model_user->getDataById('id_user', $user_id),
            'jenis_layanan_options' => $model_jenis->getAllData('jenKode', 'ASC'), // Asumsi 'jenKode' masih dipakai di simlab_r_jenis
            'error' => $session->getFlashdata('error')
        ];

        return view('Modules\Rekap\Views\v_rekap', $data);
    }

    /**
     * Endpoint untuk AJAX men-generate ringkasan (dipakai oleh tampilan)
     * TELAH DIMODIFIKASI UNTUK PEMBAGIAN DINAMIS
     */
    public function dataList()
    {
        $jenis_layanan = $this->request->getGet('jenis_layanan');
        $tanggal_awal = $this->request->getGet('tanggal_awal');
        $tanggal_akhir = $this->request->getGet('tanggal_akhir');

        $db = \Config\Database::connect();
        $builder = $db->table($this->table_pembayaran . ' p');
        $builder->select("
            SUM(CASE WHEN u.user_identity = 'ULM' THEN p.bayarTotalBiaya ELSE 0 END) AS total_ulm,
            SUM(CASE WHEN u.user_identity != 'ULM' THEN p.bayarTotalBiaya ELSE 0 END) AS total_non_ulm
        ");
        $builder->join($this->table_layanan . ' l', 'l.lnKode = p.bayarLnKode', 'inner');
        $builder->join('simlab_account_users u', 'u.user_id = l.user_id', 'inner');
        $builder->join($this->table_detil . ' d', 'd.kode_layanan = l.lnKode', 'left');

        if (!empty($jenis_layanan) && $jenis_layanan !== 'semua') {
            $builder->where('d.kode_jenis', $jenis_layanan);
        }

        if (!empty($tanggal_awal) && !empty($tanggal_akhir)) {
            $builder->where('p.bayarInvoiceTgl >=', $tanggal_awal);
            $builder->where('p.bayarInvoiceTgl <=', $tanggal_akhir);
        }

        $result = $builder->get()->getRow();

        // --- PERUBAHAN LOGIKA DI SINI ---

        // 1. Ambil template kolom dinamis dari DB
        $kolom_template = $this->getKolomKeuangan();

        // 2. Siapkan total (pastikan 0 jika null)
        $total_ulm = $result->total_ulm ?? 0;
        $total_non_ulm = $result->total_non_ulm ?? 0;

        // 3. Kita tidak lagi mengembalikan 'success: false' di sini.
        // Biarkan JavaScript yang menggambar tabel kosong.

        // 4. Buat data rekap dinamis
        $rekap = [
            'kolom_header' => $kolom_template, // Akan jadi array kosong jika DB kosong
            'total_ulm' => (float) $total_ulm,
            'total_non_ulm' => (float) $total_non_ulm,
            'ulm_detail' => $this->hitungPembagianDinamis($total_ulm, $kolom_template), // Akan jadi array kosong
            'non_ulm_detail' => $this->hitungPembagianDinamis($total_non_ulm, $kolom_template), // Akan jadi array kosong
        ];

        $response_data = [
            'success' => true, // Selalu 'true' agar JS mau menggambar tabel
            'data' => $rekap,
            'xname' => csrf_token(),
            'xhash' => csrf_hash(),
        ];

        return $this->response->setJSON($response_data);
    }

    /**
     * Hitung pembagian biaya (LAMA - DIPAKAI UNTUK EXCEL DOWNLOAD)
     */
    private function hitungPembagian($total)
    {
        $total = (float) $total;
        return [
            'total_pembayaran' => $total,
            'bahan_kimia' => $total * 0.35,
            'operasional' => $total * 0.10,
            'jasa_profesi' => $total * 0.45,
            'pendapatan_instansi' => $total * 0.10,
        ];
    }
    
    /**
     * [BARU] Ambil template kolom keuangan dari DB
     * Mengambil daftar kolom dan persentasenya.
     */
    private function getKolomKeuangan()
    {
        $model = new MyModel($this->table_kolom_keuangan);
        // Urutkan berdasarkan kdKode agar urutannya konsisten
        return $model->getAllData('kdKode', 'ASC'); 
    }

    /**
     * [BARU] Hitung pembagian biaya dinamis berdasarkan template DB
     * Digunakan oleh dataList() untuk tampilan AJAX.
     */
    private function hitungPembagianDinamis($total, $kolom_template)
    {
        $total = (float) $total;
        $pembagian = [];

        // Jika tidak ada template, kembalikan array kosong
        if (empty($kolom_template)) {
            return $pembagian;
        }
        
        // Loop setiap kolom dari database
        foreach ($kolom_template as $kolom) {
            // Ambil persentase dari kolom kdPersenNONULM
            $percentage = (float) $kolom->kdPersenNONULM / 100;
            
            // Simpan hasil kalkulasi
            $pembagian[] = [
                'label' => $kolom->kdKolomLabel,
                'percent' => $kolom->kdPersenNONULM,
                'value' => $total * $percentage
            ];
        }
        return $pembagian;
    }


    /**
     * FUNGSI DOWNLOAD YANG TELAH DIPERBARUI
     * (Tidak diubah, tetap memakai hitungPembagian() lama)
     */
    public function download()
    {
        // 1. Ambil Filter
        $jenis_layanan = $this->request->getGet('jenis_layanan');
        $tanggal_awal = $this->request->getGet('tanggal_awal');
        $tanggal_akhir = $this->request->getGet('tanggal_akhir');

        if (empty($tanggal_awal) || empty($tanggal_akhir)) {
            session()->setFlashdata('error', 'Tanggal awal dan akhir harus diisi untuk mengunduh laporan.');
            return redirect()->to('rekap');
        }

        $db = \Config\Database::connect();

        // 2. [QUERY 1] Ambil Data Rekap Total (Bagian A)
        $builder_rekap = $db->table($this->table_pembayaran . ' p');
        $builder_rekap->select("
            SUM(CASE WHEN u.user_identity = 'ULM' THEN p.bayarTotalBiaya ELSE 0 END) AS total_ulm,
            SUM(CASE WHEN u.user_identity != 'ULM' THEN p.bayarTotalBiaya ELSE 0 END) AS total_non_ulm
        ");
        $builder_rekap->join($this->table_layanan . ' l', 'l.lnKode = p.bayarLnKode', 'inner');
        $builder_rekap->join('simlab_account_users u', 'u.user_id = l.user_id', 'inner');
        
        // === PERUBAHAN KOLOM JOIN & WHERE ===
        $builder_rekap->join($this->table_detil . ' d', 'd.kode_layanan = l.lnKode', 'left'); // (was detLnKode)
        if (!empty($jenis_layanan) && $jenis_layanan !== 'semua') {
            $builder_rekap->where('d.kode_jenis', $jenis_layanan); // (was detJenKode)
        }
        // ===================================
        
        $builder_rekap->where('p.bayarInvoiceTgl >=', $tanggal_awal);
        $builder_rekap->where('p.bayarInvoiceTgl <=', $tanggal_akhir);
        $result_rekap = $builder_rekap->get()->getRow();


        // 3. [QUERY 2] Ambil Data Jumlah per Layanan (Untuk tabel rincian)
        $builder_jumlah = $db->table($this->table_pembayaran . ' p');
        
        // === PERUBAHAN KOLOM SELECT, JOIN, WHERE, GROUPBY ===
        $builder_jumlah->select('d.uji_kode, SUM(d.jumlah) as total_jumlah'); // (was detUjiKode, detJumlah)
        $builder_jumlah->join($this->table_layanan . ' l', 'l.lnKode = p.bayarLnKode', 'inner');
        $builder_jumlah->join($this->table_detil . ' d', 'd.kode_layanan = l.lnKode', 'inner'); // (was detLnKode)
        $builder_jumlah->where('p.bayarInvoiceTgl >=', $tanggal_awal);
        $builder_jumlah->where('p.bayarInvoiceTgl <=', $tanggal_akhir);
        if (!empty($jenis_layanan) && $jenis_layanan !== 'semua') {
            $builder_jumlah->where('d.kode_jenis', $jenis_layanan); // (was detJenKode)
        }
        $builder_jumlah->groupBy('d.uji_kode'); // (was detUjiKode)
        // ================================================
        
        $result_jumlah_raw = $builder_jumlah->get()->getResult();

        $data_jumlah_lookup = [];
        foreach ($result_jumlah_raw as $row) {
            $data_jumlah_lookup[$row->uji_kode] = $row->total_jumlah; // (was detUjiKode)
        }


        // 4. [QUERY 3] Ambil Template Layanan (Master Data)
        $builder_layanan = $db->table($this->table_jenis . ' j');
        
        // === PERUBAHAN KOLOM SELECT, JOIN, ORDERBY (ALIASING) ===
        // Menggunakan ALIAS (AS) agar sisa kode tidak perlu diubah
        $builder_layanan->select(
            'j.jenKode, j.jenNama, 
            u.kode as ujiKode, 
            u.nama_layanan as ujiLayanan, 
            u.satuan as ujiSatuan, 
            u.biaya as ujiTarifUlm, 
            u.biaya as ujiTarifNonUlm' // Asumsi 'biaya' dipakai untuk keduanya
        );
        $builder_layanan->join($this->table_r_layanan_pengujian . ' u', 'u.kode_jenis = j.jenKode', 'inner'); // (was ujiJenKode)
        
        if (!empty($jenis_layanan) && $jenis_layanan !== 'semua') {
            $builder_layanan->where('j.jenKode', $jenis_layanan); // Asumsi tabel jenis tidak berubah
        }
        $builder_layanan->orderBy('j.jenNama', 'ASC');
        $builder_layanan->orderBy('u.nama_layanan', 'ASC'); // (was ujiLayanan)
        // ====================================================

        $data_layanan_template = $builder_layanan->get()->getResult();

        // Validasi data kosong 
        if ((!$result_rekap || ($result_rekap->total_ulm == 0 && $result_rekap->total_non_ulm == 0)) && empty($data_layanan_template)) {
            session()->setFlashdata('error', 'Tidak ada data untuk diekspor pada periode yang dipilih.');
            return redirect()->to('rekap');
        }

        // 5. Hitung Pembagian (MEMAKAI FUNGSI LAMA)
        $total_ulm = $result_rekap->total_ulm ?? 0;
        $total_non_ulm = $result_rekap->total_non_ulm ?? 0;

        $rekap = [
            'ulm' => $this->hitungPembagian($total_ulm),
            'non_ulm' => $this->hitungPembagian($total_non_ulm),
        ];

        // 6. Siapkan Data Excel
        try {
            // --- STYLES (Tidak ada perubahan) ---
            $headerStyle = '<style bgcolor="4A9AE0" border="thin" font-size="11" color="#FFFFFF"><b><center>_TEXT_</center></b></style>'; // Biru
            $labelStyle = '<style border="thin" font-size="11"><b>_TEXT_</b></style>';
            $dataStyle = '<style border="thin" font-size="11" align="right">_TEXT_</style>';
            $totalLabelStyle = '<style border="thin" font-size="11" font-style="bold"><b>_TEXT_</b></style>';
            $totalDataStyle = '<style border="thin" font-size="11" align="right" font-style="bold"><b>_TEXT_</b></style>';
            $yellowHeaderStyle = '<style bgcolor="FFFF00" border="thin" font-size="11"><b>_TEXT_</b></style>'; // Kuning
            $subHeaderStyle = '<style bgcolor="D9EAD3" border="thin" font-size="11"><b><center>_TEXT_</center></b></style>'; // Hijau muda
            $subDataStyle = '<style border="thin" font-size="11">_TEXT_</style>';
            $subDataNumStyle = '<style border="thin" font-size="11" align="right">_TEXT_</style>';
            $subDataCenterStyle = '<style border="thin" font-size="11" align="center">_TEXT_</style>';

            // Helper format
            $formatCurrency = function ($number) {
                return number_format($number, 2, ',', '.');
            };
            $formatInt = function ($number) {
                return number_format($number, 0, ',', '.');
            };

            // Ambil info nama jenis
            $model_jenis = new MyModel($this->table_jenis);
            $jenisInfo = ($jenis_layanan !== 'semua') ? $model_jenis->getDataById('jenKode', $jenis_layanan) : null;
            $jenisNama = ($jenisInfo) ? $jenisInfo->jenNama : 'Semua Jenis Layanan';

            // Buat Judul
            $titleText = "REKAP PEMBAYARAN - " . strtoupper($jenisNama);
            $periodText = "PERIODE: " . date('d M Y', strtotime($tanggal_awal)) . " s/d " . date('d M Y', strtotime($tanggal_akhir));

            $excelData = [];
            $merges = []; 
            $currentRow = 1;

            // Baris Judul
            $excelData[] = ['<style font-size="14"><b>' . $titleText . '</b></style>', '', '', '', '', ''];
            $merges[] = 'A' . $currentRow . ':F' . $currentRow;
            $currentRow++;
            
            $excelData[] = ['<style font-size="12"><b>' . $periodText . '</b></style>', '', '', '', '', ''];
            $merges[] = 'A' . $currentRow . ':F' . $currentRow;
            $currentRow++;
            
            $excelData[] = []; // Baris kosong
            $currentRow++;

            // --- BAGIAN A: REKAP TOTAL (Tidak ada perubahan logika) ---
            $excelData[] = ['<style font-size="12"><b>A. Layanan Pengujian Sampel</b></style>', '', '', '', '', ''];
            $merges[] = 'A' . $currentRow . ':F' . $currentRow;
            $currentRow++;

            $excelData[] = [
                str_replace('_TEXT_', 'Pendapatan', $headerStyle),
                str_replace('_TEXT_', 'Total Pembayaran', $headerStyle),
                str_replace('_TEXT_', 'Bahan Kimia (35%)', $headerStyle),
                str_replace('_TEXT_', 'Operasional (10%)', $headerStyle),
                str_replace('_TEXT_', 'Jasa Profesi (45%)', $headerStyle),
                str_replace('_TEXT_', 'Pendapatan Instansi (10%)', $headerStyle),
            ];
            $currentRow++;

            $ulm_data = $rekap['ulm'];
            $excelData[] = [
                str_replace('_TEXT_', 'ULM', $labelStyle),
                str_replace('_TEXT_', $formatCurrency($ulm_data['total_pembayaran']), $dataStyle),
                str_replace('_TEXT_', $formatCurrency($ulm_data['bahan_kimia']), $dataStyle),
                str_replace('_TEXT_', $formatCurrency($ulm_data['operasional']), $dataStyle),
                str_replace('_TEXT_', $formatCurrency($ulm_data['jasa_profesi']), $dataStyle),
                str_replace('_TEXT_', $formatCurrency($ulm_data['pendapatan_instansi']), $dataStyle),
            ];
            $currentRow++;

            $non_ulm_data = $rekap['non_ulm'];
            $excelData[] = [
                str_replace('_TEXT_', 'Non-ULM', $labelStyle),
                str_replace('_TEXT_', $formatCurrency($non_ulm_data['total_pembayaran']), $dataStyle),
                str_replace('_TEXT_', $formatCurrency($non_ulm_data['bahan_kimia']), $dataStyle),
                str_replace('_TEXT_', $formatCurrency($non_ulm_data['operasional']), $dataStyle),
                str_replace('_TEXT_', $formatCurrency($non_ulm_data['jasa_profesi']), $dataStyle),
                str_replace('_TEXT_', $formatCurrency($non_ulm_data['pendapatan_instansi']), $dataStyle),
            ];
            $currentRow++;

            $total_bayar = $ulm_data['total_pembayaran'] + $non_ulm_data['total_pembayaran'];
            $total_kimia = $ulm_data['bahan_kimia'] + $non_ulm_data['bahan_kimia'];
            $total_ops = $ulm_data['operasional'] + $non_ulm_data['operasional'];
            $total_jasa = $ulm_data['jasa_profesi'] + $non_ulm_data['jasa_profesi'];
            $total_inst = $ulm_data['pendapatan_instansi'] + $non_ulm_data['pendapatan_instansi'];

            $excelData[] = [
                str_replace('_TEXT_', 'TOTAL', $totalLabelStyle),
                str_replace('_TEXT_', $formatCurrency($total_bayar), $totalDataStyle),
                str_replace('_TEXT_', $formatCurrency($total_kimia), $totalDataStyle),
                str_replace('_TEXT_', $formatCurrency($total_ops), $totalDataStyle),
                str_replace('_TEXT_', $formatCurrency($total_jasa), $totalDataStyle),
                str_replace('_TEXT_', $formatCurrency($total_inst), $totalDataStyle),
            ];
            $currentRow++;

            // --- BAGIAN B: RINCIAN LAYANAN ---
            $excelData[] = []; // Baris kosong
            $currentRow++;
            $excelData[] = []; // Baris kosong
            $currentRow++;

            $currentJenKode = null;
            $nomor = 1;

            // (Tidak ada perubahan logika di sini karena kita pakai ALIAS di QUERY 3)
            foreach ($data_layanan_template as $layanan) {
                if ($layanan->jenKode !== $currentJenKode) {
                    if ($currentJenKode !== null) {
                        $excelData[] = [];
                        $currentRow++;
                    }

                    $excelData[] = [
                        str_replace('_TEXT_', strtoupper($layanan->jenKode . '. ' . $layanan->jenNama), $yellowHeaderStyle),
                        '', '', '', '', ''
                    ];
                    $merges[] = 'A' . $currentRow . ':F' . $currentRow;
                    $currentRow++;

                    $excelData[] = [
                        str_replace('_TEXT_', 'No', $subHeaderStyle),
                        str_replace('_TEXT_', 'Nama Layanan Pengujian', $subHeaderStyle),
                        str_replace('_TEXT_', 'Satuan', $subHeaderStyle),
                        str_replace('_TEXT_', 'Tarif ULM', $subHeaderStyle),
                        str_replace('_TEXT_', 'Tarif NON ULM', $subHeaderStyle),
                        str_replace('_TEXT_', 'Jumlah', $subHeaderStyle),
                    ];
                    $currentRow++;

                    $currentJenKode = $layanan->jenKode;
                    $nomor = 1;
                }

                // Ambil jumlah dari data lookup
                // $layanan->ujiKode di sini SEBENARNYA adalah kolom 'kode' dari r_layanan_pengujian,
                // tapi karena ALIAS 'u.kode as ujiKode', ini tetap berfungsi
                $jumlah = $data_jumlah_lookup[$layanan->ujiKode] ?? 0;

                // Baris Data Rincian
                $excelData[] = [
                    str_replace('_TEXT_', $nomor, $subDataCenterStyle),
                    str_replace('_TEXT_', $layanan->ujiLayanan, $subDataStyle), // (Alias dari nama_layanan)
                    str_replace('_TEXT_', $layanan->ujiSatuan, $subDataCenterStyle), // (Alias dari satuan)
                    str_replace('_TEXT_', $formatCurrency($layanan->ujiTarifUlm), $subDataNumStyle), // (Alias dari biaya)
                    str_replace('_TEXT_', $formatCurrency($layanan->ujiTarifNonUlm), $subDataNumStyle), // (Alias dari biaya)
                    str_replace('_TEXT_', $formatInt($jumlah), $subDataNumStyle),
                ];
                $currentRow++;
                $nomor++;
            }

            // 7. Generate & Download Excel (Tidak ada perubahan)
            $filename = 'Rekap_Pembayaran_' . str_replace(' ', '_', $jenisNama) . '_' . $tanggal_awal . '_sd_' . $tanggal_akhir . '.xlsx';

            $xlsx = \Shuchkin\SimpleXLSXGen::fromArray($excelData);

            $xlsx->setDefaultFont('Times New Roman');
            $xlsx->setDefaultFontSize(11);

            foreach ($merges as $merge) {
                $xlsx->mergeCells($merge);
            }

            $xlsx->setColWidth(1, 10);  
            $xlsx->setColWidth(2, 40);  
            $xlsx->setColWidth(3, 20);  
            $xlsx->setColWidth(4, 20);  
            $xlsx->setColWidth(5, 20);  
            $xlsx->setColWidth(6, 20);  

            $tempFile = WRITEPATH . 'uploads/' . $filename;
            $xlsx->saveAs($tempFile);

            ob_clean();

            $this->response->setHeader('Content-Type', 'application/vnd.openxmlformats-officedocument.spreadsheetml.sheet');
            $this->response->setHeader('Content-Disposition', 'attachment; filename="' . $filename . '"');
            $this->response->setHeader('Cache-Control', 'max-age=0');
            $this->response->setHeader('Pragma', 'public');

            return $this->response->download($tempFile, null)->setFileName($filename);

        } catch (\Exception $e) {
            log_message('error', 'Export Excel Rekap Error: ' . $e->getMessage());
            session()->setFlashdata('error', 'Terjadi kesalahan saat membuat file Excel: ' . $e->getMessage());
            return redirect()->to('rekap');
        }
    }
}