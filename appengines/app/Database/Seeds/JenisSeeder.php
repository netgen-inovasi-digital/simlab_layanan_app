<?php

namespace App\Database\Seeds;

use CodeIgniter\Database\Seeder;

class JenisSeeder extends Seeder
{
    public function run()
    {
        $data = [
            ['jenKode' => 'A', 'jenNama' => 'Layanan Pengujian Sampel '],
            ['jenKode' => 'B', 'jenNama' => 'Layanan Sewa Alat Laboratorium'],
            ['jenKode' => 'C', 'jenNama' => 'Layanan Sewa Ruangan'],
            ['jenKode' => 'D', 'jenNama' => 'Layanan Pelatihan/Magang'],
            ['jenKode' => 'E', 'jenNama' => 'Layanan Cihuy'],
            ['jenKode' => 'F', 'jenNama' => 'Layanan yyy'],
            ['jenKode' => 'G', 'jenNama' => 'Layanan gagal'],
            ['jenKode' => 'H', 'jenNama' => 'layanaan apa ini'],
            ['jenKode' => 'I', 'jenNama' => 'Nah inii'],
            ['jenKode' => 'J', 'jenNama' => 'apah iya'],
            ['jenKode' => 'K', 'jenNama' => 'layananan'],
            ['jenKode' => 'L', 'jenNama' => 'Layanan Lidan'],
        ];

        $this->db->table('simlab_r_jenis')->insertBatch($data);
    }
}
