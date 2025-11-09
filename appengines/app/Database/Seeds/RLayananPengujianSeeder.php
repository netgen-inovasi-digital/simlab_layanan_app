<?php

namespace App\Database\Seeds;

use CodeIgniter\Database\Seeder;

class RLayananPengujianSeeder extends Seeder
{
    public function run()
    {
        // urut: kode dimulai dari 1 (sesuai permintaan)
        $data = [
            ['kode' => 1,  'nama_layanan' => 'Instrumen FTIR - Al (Aluminium) - Tanah, air', 'kode_alat' => 'FTIR', 'kode_parameter' => 'Al1', 'kode_jenis' => 'A', 'satuan' => 'Sample', 'biaya' => 100000,  'diskon' => 0],
            ['kode' => 2,  'nama_layanan' => 'Instrumen AAS Flame : Fe (Besi) - Tanah, air', 'kode_alat' => 'AASFLAME', 'kode_parameter' => 'Fe1', 'kode_jenis' => 'A', 'satuan' => 'sampel', 'biaya' => 100000,  'diskon' => 0],
            ['kode' => 3,  'nama_layanan' => 'Instrumen AAS Flame : Cd (tanah,air,pangan, non pangan)', 'kode_alat' => 'AASFLAME', 'kode_parameter' => 'Cd', 'kode_jenis' => 'A', 'satuan' => 'sampel1', 'biaya' => 1000001, 'diskon' => 0],
            ['kode' => 4,  'nama_layanan' => 'SPEKTRO UV-VIS : KURKUMIN', 'kode_alat' => 'UV VIS', 'kode_parameter' => 'UV-VIS-5', 'kode_jenis' => 'A', 'satuan' => 'sampel', 'biaya' => 370000,  'diskon' => 0],
            ['kode' => 5,  'nama_layanan' => 'PARAMETER PROKSIMAT : KADAR PROTEIN', 'kode_alat' => 'PROKSIMAT', 'kode_parameter' => 'PROKSIMAT-2', 'kode_jenis' => 'A', 'satuan' => 'sampel', 'biaya' => 150000,  'diskon' => 0],
            ['kode' => 6,  'nama_layanan' => 'PARAMETER PROKSIMAT : KADAR KARBOHIDRAT', 'kode_alat' => 'PROKSIMAT', 'kode_parameter' => 'PROKSIMAT-5', 'kode_jenis' => 'A', 'satuan' => 'sampel', 'biaya' => 150000,  'diskon' => 0],
            ['kode' => 7,  'nama_layanan' => 'PARAMETER PROKSIMAT : PROKSIMAT(Air, Abu, Protein kasar, Lemak, Karbohidrat, & Serat kasar)', 'kode_alat' => 'PROKSIMAT', 'kode_parameter' => 'PROKSIMAT', 'kode_jenis' => 'A', 'satuan' => 'Paket', 'biaya' => 598000,  'diskon' => 0],
            ['kode' => 8,  'nama_layanan' => 'Parameter Ekstraksi : MASERASI', 'kode_alat' => 'EKSTRAKSI', 'kode_parameter' => 'EKSTRAKSI', 'kode_jenis' => 'A', 'satuan' => 'sampel', 'biaya' => 320000,  'diskon' => 0],
            ['kode' => 9,  'nama_layanan' => 'Instrumen Mikroskop : FOTO PREPARAT', 'kode_alat' => 'MIKROSKOP', 'kode_parameter' => 'MIKROSKOP-0', 'kode_jenis' => 'A', 'satuan' => 'sampel', 'biaya' => 25000,   'diskon' => 0],
            ['kode' => 10, 'nama_layanan' => 'pH meter : pH (air, tanah, dll)', 'kode_alat' => 'PH', 'kode_parameter' => 'pH', 'kode_jenis' => 'A', 'satuan' => 'sampel', 'biaya' => 40000,   'diskon' => 0],
            ['kode' => 11, 'nama_layanan' => 'Instrumen FTIR : Preparasi Sampel', 'kode_alat' => 'FTIR', 'kode_parameter' => 'FTIR-1', 'kode_jenis' => 'A', 'satuan' => 'sampel', 'biaya' => 25000,   'diskon' => 0],
            ['kode' => 12, 'nama_layanan' => 'Instrumen FTIR : Preparasi dan Pengujian', 'kode_alat' => 'FTIR', 'kode_parameter' => 'FTIR-2', 'kode_jenis' => 'A', 'satuan' => 'sampel', 'biaya' => 140000,  'diskon' => 0],
            ['kode' => 13, 'nama_layanan' => 'ROTATING VISKOMETER DIGITAL : NILAI VISKOSITAS', 'kode_alat' => 'VISKOMETER', 'kode_parameter' => 'VISKOSITAS', 'kode_jenis' => 'A', 'satuan' => 'sampel', 'biaya' => 80000,   'diskon' => 0],
            ['kode' => 14, 'nama_layanan' => 'SEM MORFOLOGI : SEM MORFOLOGI', 'kode_alat' => 'SEM', 'kode_parameter' => 'SEM', 'kode_jenis' => 'A', 'satuan' => 'sampel', 'biaya' => 450000,  'diskon' => 0],
            ['kode' => 15, 'nama_layanan' => 'SPEKTRO UV-VIS : ASAM HUMAT', 'kode_alat' => 'UV VIS', 'kode_parameter' => 'spektro uv-vis 10', 'kode_jenis' => 'A', 'satuan' => 'sampel', 'biaya' => 259000,  'diskon' => 0],
            ['kode' => 16, 'nama_layanan' => 'Fasilitas Pelatihan (Umum) - Biosafety Cabinet', 'kode_alat' => 'Fasilitas Pelatihan (Umum)', 'kode_parameter' => 'Biosafety Cabinet', 'kode_jenis' => 'B', 'satuan' => 'orang', 'biaya' => 2000001, 'diskon' => 0],
            ['kode' => 17, 'nama_layanan' => 'faisal', 'kode_alat' => 'GCMS', 'kode_parameter' => 'EKSTRAKSI', 'kode_jenis' => 'A', 'satuan' => 'sampel', 'biaya' => 100000, 'diskon' => 12],
            ['kode' => 18, 'nama_layanan' => 'Test aja sih - Waterbath Shaker', 'kode_alat' => 'Test', 'kode_parameter' => 'Waterbath Shaker', 'kode_jenis' => 'A', 'satuan' => 'sampel', 'biaya' => 100000, 'diskon' => 15],
            ['kode' => 19, 'nama_layanan' => 'coba lagi, php - Ba (Barium) - Tanah, air', 'kode_alat' => 'COBA', 'kode_parameter' => 'Ba1', 'kode_jenis' => 'E', 'satuan' => 'sampel', 'biaya' => 5000,   'diskon' => 5],
            ['kode' => 20, 'nama_layanan' => 'Instrumen AAS Flame- - ASAM GALAT', 'kode_alat' => 'AASFLAME', 'kode_parameter' => 'HPLC-3', 'kode_jenis' => 'A', 'satuan' => 'sampel', 'biaya' => 100000, 'diskon' => 10],
            ['kode' => 21, 'nama_layanan' => 'Instrumen AAS Graphite Furnace - Autoclave', 'kode_alat' => 'ASSGRAFUR', 'kode_parameter' => 'Autoclave', 'kode_jenis' => 'A', 'satuan' => 'sampel', 'biaya' => 100000, 'diskon' => 5],
        ];

        $this->db->table('r_layanan_pengujian')->insertBatch($data);
    }
}
