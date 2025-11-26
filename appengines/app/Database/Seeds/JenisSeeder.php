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
            ['jenKode' => 'C', 'jenNama' => 'Layanan Sewa Ruangan Lab'],
            ['jenKode' => 'D', 'jenNama' => 'Layanan Sewa Ruangan Rapat dan Jas'],
            ['jenKode' => 'E', 'jenNama' => 'Layanan Aquades'],
        ];

        $this->db->table('simlab_r_jenis')->insertBatch($data);
    }
}
