<?php

namespace App\Database\Seeds;

use CodeIgniter\Database\Seeder;

class RolesSeeder extends Seeder
{
    public function run()
    {
        $data = [
            ['id_role' => 1, 'nama_role' => 'Admin', 'grup' => 'admin', 'status_role' => 1],
            ['id_role' => 2, 'nama_role' => 'User', 'grup' => 'author', 'status_role' => 1],
            ['id_role' => 4, 'nama_role' => 'Manajer Teknis', 'grup' => null, 'status_role' => 1],
            ['id_role' => 6, 'nama_role' => 'Penyelia', 'grup' => null, 'status_role' => 1],
            ['id_role' => 8, 'nama_role' => 'Super Admin', 'grup' => null, 'status_role' => 1],
        ];

        $this->db->table('roles')->insertBatch($data);
    }
}
