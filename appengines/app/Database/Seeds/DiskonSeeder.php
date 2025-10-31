<?php

namespace App\Database\Seeds;

use CodeIgniter\Database\Seeder;

class DiskonSeeder extends Seeder
{
    public function run()
    {
        $data = [
            [
                'kolom'  => 'ulm',
                'diskon' => 48
            ],
        ];

        $this->db->table('simlab_t_diskon')->insertBatch($data);
    }
}
