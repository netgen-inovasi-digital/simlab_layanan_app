<?php

namespace App\Database\Seeds;

use CodeIgniter\Database\Seeder;

class RTimSeeder extends Seeder
{
    public function run()
    {
        // mapping uji_kode baru berdasarkan urutan di RLayananPengujianSeeder (1..21)
        $data = [
            // sebelumnya ada beberapa entri ganda untuk satu uji; saya pertahankan pola itu
            ['uji_kode' => 1,  'user_id' => 9],
            ['uji_kode' => 1,  'user_id' => 8],
            ['uji_kode' => 2,  'user_id' => 11],
            ['uji_kode' => 3,  'user_id' => 41],
            ['uji_kode' => 4,  'user_id' => 41],
            ['uji_kode' => 5,  'user_id' => 30],
            ['uji_kode' => 7,  'user_id' => 33],
            ['uji_kode' => 8,  'user_id' => 30],
            ['uji_kode' => 9,  'user_id' => 11],
            ['uji_kode' => 12, 'user_id' => 11],
            ['uji_kode' => 14, 'user_id' => 9],
            ['uji_kode' => 17, 'user_id' => 9],
            ['uji_kode' => 20, 'user_id' => 9],
            ['uji_kode' => 21, 'user_id' => 11],
        ];

        $this->db->table('r_tim')->insertBatch($data);
    }
}
