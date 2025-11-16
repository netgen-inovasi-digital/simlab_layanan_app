<?php

namespace Modules\Rekap\Controllers;

use App\Controllers\BaseController;
use App\Models\MyModel;

require_once APPPATH . 'Libraries/SimpleXLSXGen/src/SimpleXLSXGen.php';

class Rekap extends BaseController
{
    private $table_pembayaran = 't_pembayaran';
    private $table_layanan = 'simlab_t_layanan';
    private $table_detil = 't_layanan_detil';
    private $table_jenis = 'simlab_r_jenis';
    private $table_r_layanan = 'r_layanan_pengujian';
    private $table_kolom_keuangan = 'simlab_r_kolom_keuangan_detail';


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
            'error' => $session->getFlashdata('error')
        ];

        return view('Modules\Rekap\Views\v_rekap', $data);
    }

    public function dataList()
    {
        $jenis_layanan_filter = $this->request->getGet('jenis_layanan');
        $tanggal_awal = $this->request->getGet('tanggal_awal');
        $tanggal_akhir = $this->request->getGet('tanggal_akhir');

        $db = \Config\Database::connect();

        // 1. Ambil daftar Jenis Layanan yang akan ditampilkan
        $model_jenis = new MyModel($this->table_jenis);
        if (!empty($jenis_layanan_filter) && $jenis_layanan_filter !== 'semua') {
            $model_jenis->where('jenKode', $jenis_layanan_filter);
        }
        $daftar_jenis = $model_jenis->getAllData('jenKode', 'ASC');

        // 2. Ambil semua total, digabungkan berdasarkan kode_jenis
        $totals_lookup = $this->getTotalsLookup($tanggal_awal, $tanggal_akhir);

        // 3. Ambil semua template kolom keuangan, digabungkan berdasarkan kdJenKode
        $kolom_lookup = $this->getKolomKeuanganLookup();

        // 4. Bangun Array Respon
        $rekap_data = [];
        
        if (empty($daftar_jenis)) {
             return $this->response->setJSON(['success' => false, 'message' => 'Jenis layanan tidak ditemukan.']);
        }

        foreach ($daftar_jenis as $jenis) {
            $jenKode = $jenis->jenKode;

            // Ambil total untuk jenis ini (atau 0 jika tidak ada)
            $total_ulm = $totals_lookup[$jenKode]['total_ulm'] ?? 0;
            $total_non_ulm = $totals_lookup[$jenKode]['total_non_ulm'] ?? 0;

            // Ambil template kolom untuk jenis ini (atau [] jika tidak ada)
            $kolom_template = $kolom_lookup[$jenKode] ?? [];

            // Buat objek data untuk tabel ini
            $rekap_data[] = [
                'title' => $jenis->jenKode . '. ' . $jenis->jenNama,
                'kolom_header' => $kolom_template,
                'total_ulm' => (float) $total_ulm,
                'total_non_ulm' => (float) $total_non_ulm,
                'ulm_detail' => $this->hitungPembagianDinamis($total_ulm, $kolom_template),
                'non_ulm_detail' => $this->hitungPembagianDinamis($total_non_ulm, $kolom_template),
            ];
        }

        $response_data = [
            'success' => true,
            'data' => $rekap_data, 
            'xname' => csrf_token(),
            'xhash' => csrf_hash(),
        ];

        return $this->response->setJSON($response_data);
    }

    private function getTotalsLookup($tanggal_awal, $tanggal_akhir)
    {
        $db = \Config\Database::connect();
        $builder = $db->table($this->table_detil . ' d');
        $builder->select("
            d.kode_jenis,
            SUM(CASE WHEN u.user_identity = 'ULM' THEN (d.biaya * d.jumlah) ELSE 0 END) AS total_ulm,
            SUM(CASE WHEN u.user_identity != 'ULM' THEN (d.biaya * d.jumlah) ELSE 0 END) AS total_non_ulm
        ");
        $builder->join($this->table_layanan . ' l', 'd.kode_layanan = l.lnKode', 'inner');
        $builder->join('simlab_account_users u', 'l.user_id = u.user_id', 'inner');
        $builder->join($this->table_pembayaran . ' p', 'l.lnKode = p.bayarLnKode', 'inner');

        if (!empty($tanggal_awal) && !empty($tanggal_akhir)) {
            $builder->where('p.bayarInvoiceTgl >=', $tanggal_awal);
            $builder->where('p.bayarInvoiceTgl <=', $tanggal_akhir);
        }
        
        $builder->groupBy('d.kode_jenis');
        $results = $builder->get()->getResult();

        $lookup = [];
        foreach ($results as $row) {
            $lookup[$row->kode_jenis] = [
                'total_ulm' => $row->total_ulm,
                'total_non_ulm' => $row->total_non_ulm
            ];
        }
        return $lookup;
    }

    private function getKolomKeuanganLookup()
    {
        $model = new MyModel($this->table_kolom_keuangan);
        $all_kolom = $model->getAllData('kdJenKode, kdKode', 'ASC');
        
        $lookup = [];
        foreach ($all_kolom as $kolom) {
            if (!isset($lookup[$kolom->kdJenKode])) {
                $lookup[$kolom->kdJenKode] = [];
            }
            $lookup[$kolom->kdJenKode][] = $kolom;
        }
        return $lookup;
    }

    private function hitungPembagianDinamis($total, $kolom_template)
    {
        $total = (float) $total;
        $pembagian = [];

        if (empty($kolom_template)) {
            return $pembagian;
        }
        
        foreach ($kolom_template as $kolom) {
            $percentage = (float) $kolom->kdPersenNONULM / 100;
            $pembagian[] = [
                'label' => $kolom->kdKolomLabel,
                'percent' => $kolom->kdPersenNONULM,
                'value' => $total * $percentage
            ];
        }
        return $pembagian;
    }


    public function download()
    {
        // 1. Ambil Filter Tanggal & Jenis
        $jenis_layanan = $this->request->getGet('jenis_layanan');
        $tanggal_awal = $this->request->getGet('tanggal_awal');
        $tanggal_akhir = $this->request->getGet('tanggal_akhir');

        // 2. Validasi Tanggal
        if (empty($tanggal_awal) || empty($tanggal_akhir)) {
            session()->setFlashdata('error', 'Tanggal awal dan akhir harus diisi untuk mengunduh laporan pendapatan.');
            return redirect()->to('rekap');
        }

        // 3. Ambil Data Kategori (sesuai filter)
        $model_jenis = new MyModel($this->table_jenis); 
        $builder_jenis = $model_jenis->builder();
        if (!empty($jenis_layanan) && $jenis_layanan !== 'semua') {
            $builder_jenis->where('jenKode', $jenis_layanan);
        }
        $daftar_jenis = $builder_jenis->orderBy('jenKode', 'ASC')->get()->getResult();
        
        // 4. Ambil Data PENDAPATAN berdasarkan MASTER LAYANAN
        $db = \Config\Database::connect();
        
        $dateFilter = "p.bayarInvoiceTgl >= '{$tanggal_awal}' AND p.bayarInvoiceTgl <= '{$tanggal_akhir}'";
        
        $builder_data = $db->table($this->table_r_layanan . ' r'); 
        $builder_data->select("
            r.nama_layanan, 
            r.kode_jenis, 
            r.kode,
            COALESCE(SUM(CASE WHEN p.bayarKode IS NOT NULL AND u.user_identity = 'ULM' THEN (d.biaya * d.jumlah) ELSE 0 END), 0) AS total_ulm, 
            COALESCE(SUM(CASE WHEN p.bayarKode IS NOT NULL AND u.user_identity != 'ULM' THEN (d.biaya * d.jumlah) ELSE 0 END), 0) AS total_non_ulm
        "); 
        
        $builder_data->join($this->table_detil . ' d', 'r.kode = d.uji_kode', 'left'); 
        $builder_data->join($this->table_layanan . ' l', 'd.kode_layanan = l.lnKode', 'left'); 
        $builder_data->join('simlab_account_users u', 'l.user_id = u.user_id', 'left');
        
        $builder_data->join($this->table_pembayaran . ' p', "l.lnKode = p.bayarLnKode AND {$dateFilter}", 'left');
        
        if (!empty($jenis_layanan) && $jenis_layanan !== 'semua') {
            $builder_data->where('r.kode_jenis', $jenis_layanan); 
        }

        $builder_data->groupBy('r.kode, r.nama_layanan, r.kode_jenis'); 
        $builder_data->orderBy('r.kode_jenis', 'ASC'); 
        $builder_data->orderBy('r.nama_layanan', 'ASC'); 
        $revenue_data_all = $builder_data->get()->getResult();

        // 5. Kelompokkan data PENDAPATAN berdasarkan kode_jenis
        $layanan_grouped = [];
        foreach ($revenue_data_all as $layanan) {
            if (!isset($layanan_grouped[$layanan->kode_jenis])) { 
                $layanan_grouped[$layanan->kode_jenis] = []; 
            }
            $layanan_grouped[$layanan->kode_jenis][] = $layanan; 
        }

        // 6. Siapkan Data Excel
        try {
            $headerStyle = '<style bgcolor="#f2f2f2" border="thin" font-style="bold"><b><center>_TEXT_</center></b></style>';
            $categoryStyle = '<style bgcolor="#ffff00" border="thin" font-style="bold"><b>_TEXT_</b></style>'; 
            $categoryStyleEmpty = '<style bgcolor="#ffff00" border="thin"></style>'; 
            $noStyle = '<style border="thin" align="center">_TEXT_</style>';
            $uraianStyle = '<style border="thin">_TEXT_</style>';
            $hargaStyle = '<style border="thin" align="right">_TEXT_</style>'; 
            $subtotalLabelStyle = '<style border="thin" font-style="bold" align="right"><b>_TEXT_</b></style>';
            $emptySubtotalStyle = '<style border="thin"></style>';
            $subtotalHargaStyle = '<style border="thin" font-style="bold" align="right"><b>_TEXT_</b></style>';

            $formatCurrency = function ($number) {
                return 'Rp ' . number_format((float)$number, 2, ',', '.');
            };

            $excelData = [];

            // Baris Judul
            $jenisInfo = ($jenis_layanan !== 'semua' && !empty($daftar_jenis)) ? $daftar_jenis[0]->jenNama : 'Semua Jenis Layanan';
            $titleText = "REKAP PENDAPATAN PER LAYANAN - " . strtoupper($jenisInfo);
            $periodText = "PERIODE: " . date('d M Y', strtotime($tanggal_awal)) . " s/d " . date('d M Y', strtotime($tanggal_akhir));
            
            $excelData[] = ['<style font-size="14"><b>' . $titleText . '</b></style>', '', '', ''];
            $excelData[] = ['<style font-size="12"><b>' . $periodText . '</b></style>', '', '', ''];
            $excelData[] = []; 

            // Header Utama (4 Kolom)
            $excelData[] = [
                str_replace('_TEXT_', 'No.', $headerStyle),
                str_replace('_TEXT_', 'Uraian Kegiatan', $headerStyle),
                str_replace('_TEXT_', 'ULM', $headerStyle),
                str_replace('_TEXT_', 'NON-ULM', $headerStyle),
            ];

            // Inisialisasi Grand Total
            $grand_total_ulm = 0;
            $grand_total_non_ulm = 0;

            // --- Loop Data KATEGORI MASTER ---
            foreach ($daftar_jenis as $jenis) {
                
                // Baris Kategori (Kuning)
                $excelData[] = [
                    str_replace('_TEXT_', $jenis->jenNama, $categoryStyle),
                    $categoryStyleEmpty, $categoryStyleEmpty, $categoryStyleEmpty
                ];

                $sub_total_ulm = 0;
                $sub_total_non_ulm = 0;

                // Ambil data PENDAPATAN yang sudah dikelompokkan
                $layanan_di_kategori = $layanan_grouped[$jenis->jenKode] ?? [];
                
                $no = 1;
                if (!empty($layanan_di_kategori)) {
                    // Loop isi layanan
                    foreach ($layanan_di_kategori as $layanan) {
                        $excelData[] = [
                            str_replace('_TEXT_', $no, $noStyle), 
                            str_replace('_TEXT_', $layanan->nama_layanan, $uraianStyle), 
                            str_replace('_TEXT_', $formatCurrency($layanan->total_ulm), $hargaStyle), 
                            str_replace('_TEXT_', $formatCurrency($layanan->total_non_ulm), $hargaStyle)
                        ];

                        // Akumulasi total
                        $sub_total_ulm += (float)$layanan->total_ulm;
                        $sub_total_non_ulm += (float)$layanan->total_non_ulm;
                        $grand_total_ulm += (float)$layanan->total_ulm;
                        $grand_total_non_ulm += (float)$layanan->total_non_ulm;

                        $no++;
                    }
                } else {
                    // Jika tidak ada layanan master untuk kategori ini
                    $excelData[] = [
                        str_replace('_TEXT_', '-', $noStyle),
                        str_replace('_TEXT_', '(Tidak ada layanan master)', $uraianStyle),
                        str_replace('_TEXT_', $formatCurrency(0), $hargaStyle), 
                        str_replace('_TEXT_', $formatCurrency(0), $hargaStyle), 
                    ];
                }

                // Baris SUB TOTAL (4 Kolom)
                $excelData[] = [
                    $emptySubtotalStyle, 
                    str_replace('_TEXT_', 'SUB TOTAL', $subtotalLabelStyle), 
                    str_replace('_TEXT_', $formatCurrency($sub_total_ulm), $subtotalHargaStyle), 
                    str_replace('_TEXT_', $formatCurrency($sub_total_non_ulm), $subtotalHargaStyle)
                ];
            }

            // Baris TOTAL (4 Kolom)
            $excelData[] = [
                $emptySubtotalStyle, 
                str_replace('_TEXT_', 'TOTAL', $subtotalLabelStyle), 
                str_replace('_TEXT_', $formatCurrency($grand_total_ulm), $subtotalHargaStyle), 
                str_replace('_TEXT_', $formatCurrency($grand_total_non_ulm), $subtotalHargaStyle)
            ];
            
            // 7. Generate & Download Excel
            $filename = 'Rekap_Pendapatan_Layanan_' . str_replace(' ', '_', $jenisInfo) . '_' . $tanggal_awal . '_sd_' . $tanggal_akhir . '.xlsx';

            $xlsx = \Shuchkin\SimpleXLSXGen::fromArray($excelData);
            
            $xlsx->setDefaultFont('Arial');
            $xlsx->setDefaultFontSize(10);

            // Merge Judul
            $xlsx->mergeCells('A1:D1');
            $xlsx->mergeCells('A2:D2');

            // Atur lebar kolom
            $xlsx->setColWidth(1, 6); // No.
            $xlsx->setColWidth(2, 45); // Uraian
            $xlsx->setColWidth(3, 25); // ULM 
            $xlsx->setColWidth(4, 25); // NON-ULM

            $rowIndex = 5; 
            foreach ($daftar_jenis as $jenis) {
                $xlsx->mergeCells('A'.$rowIndex.':D'.$rowIndex); 
                $rowIndex++; 
                
                $layanan_count = 0;
                if (isset($layanan_grouped[$jenis->jenKode]) && !empty($layanan_grouped[$jenis->jenKode])) {
                    $layanan_count = count($layanan_grouped[$jenis->jenKode]);
                } else {
                    $layanan_count = 1; 
                }

                $rowIndex += $layanan_count; 
                $rowIndex++; 
            }
            
            ob_clean();
            return $xlsx->downloadAs($filename);

        } catch (\Exception $e) {
            log_message('error', 'Export Excel Rekap Pendapatan Error: ' . $e->getMessage());
            session()->setFlashdata('error', 'Terjadi kesalahan saat membuat file Excel: ' . $e->getMessage());
            return redirect()->to('rekap');
        }
    }
}