<?php

namespace App\Database\Seeds;

use CodeIgniter\Database\Seeder;

class JenisSeeder extends Seeder
{
    public function run()
    {
        $data = [
            ['kode' => 'A', 'nama' => 'Layanan Pengujian Sampel '],
            ['kode' => 'B', 'nama' => 'Layanan Sewa Alat Laboratorium'],
            ['kode' => 'C', 'nama' => 'Layanan Sewa Ruangan Lab'],
            ['kode' => 'D', 'nama' => 'Layanan Sewa Ruangan Rapat dan Jas'],
            ['kode' => 'E', 'nama' => 'Layanan Aquades'],
        ];

        $this->db->table('r_jenis')->insertBatch($data);
    }
}
