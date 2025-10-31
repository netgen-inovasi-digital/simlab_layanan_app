<?php

namespace App\Database\Seeds;

use CodeIgniter\Database\Seeder;

class LayananPengujianSeeder extends Seeder
{
    public function run()
    {
        $data = [
            [
                'ujiKode' => 26,
                'ujiLayanan' => 'Instrumen AAS Flame : Al (Aluminium) - Tanah, air,',
                'ujiAlatKode' => '',
                'ujiParaKode' => '',
                'ujiInstansi' => 'NON ULM',
                'ujiJenKode' => '',
                'ujiSatuan' => 'Sample',
                'ujiBiaya' => 100000,
                'ujiBiaya1' => null,
                'ujiBiaya2' => null,
                'ujiBiaya3' => null,
                'ujiBiaya4' => null,
                'ujiBiaya5' => null,
            ],
            [
                'ujiKode' => 27,
                'ujiLayanan' => 'Instrumen1 AAS Flame : Mn (Mangan) - Tanah, air, pupuk cair',
                'ujiAlatKode' => 'AASFLAME',
                'ujiParaKode' => 'Colony Counter Digit',
                'ujiInstansi' => 'NON ULM',
                'ujiJenKode' => 'B',
                'ujiSatuan' => 'sampel1',
                'ujiBiaya' => 100001,
                'ujiBiaya1' => null,
                'ujiBiaya2' => null,
                'ujiBiaya3' => null,
                'ujiBiaya4' => null,
                'ujiBiaya5' => null,
            ],
            [
                'ujiKode' => 28,
                'ujiLayanan' => 'Instrumen AAS Flame : Fe (Besi) - Tanah, air',
                'ujiAlatKode' => 'AASFLAME',
                'ujiParaKode' => 'Fe1',
                'ujiInstansi' => 'NON ULM',
                'ujiJenKode' => 'A',
                'ujiSatuan' => 'sampel ',
                'ujiBiaya' => 100000,
                'ujiBiaya1' => null,
                'ujiBiaya2' => null,
                'ujiBiaya3' => null,
                'ujiBiaya4' => null,
                'ujiBiaya5' => null,
            ],
            // ...
            // MASUKKAN SEMUA DATA HINGGA ujiKode 123
            // ...
            [
                'ujiKode' => 123,
                'ujiLayanan' => 'faisal',
                'ujiAlatKode' => 'AASFLAME',
                'ujiParaKode' => 'Ag (Perak) -',
                'ujiInstansi' => null,
                'ujiJenKode' => 'A',
                'ujiSatuan' => 'sampel ',
                'ujiBiaya' => 120000,
                'ujiBiaya1' => null,
                'ujiBiaya2' => null,
                'ujiBiaya3' => null,
                'ujiBiaya4' => null,
                'ujiBiaya5' => null,
            ],
        ];

        // Insert semua data ke tabel
        $this->db->table('simlab_r_layanan_pengujian')->insertBatch($data);
    }
}
