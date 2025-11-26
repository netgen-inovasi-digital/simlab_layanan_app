<?php

namespace App\Database\Seeds;

use CodeIgniter\Database\Seeder;

class RMetodeSeeder extends Seeder
{
    public function run()
    {
        $data = [
            ['nama' => 'Gravimetri'],
            ['nama' => 'Spektrofotometri UV-Vis'],
            ['nama' => 'Kromatografi Cair Kinerja Tinggi (HPLC)'],
            ['nama' => 'Titrasi Asam-Basa'],
            ['nama' => 'Spektroskopi Serapan Atom (AAS)'],
            ['nama' => 'Mikrobiologi: Total Plate Count (TPC)'],
            ['nama' => 'Gas Chromatography (GC)'],
        ];

        $this->db->table('r_metode')->insertBatch($data);
    }
}
