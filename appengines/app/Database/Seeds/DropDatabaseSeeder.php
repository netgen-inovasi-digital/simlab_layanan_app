<?php

namespace App\Database\Seeds;

use CodeIgniter\Database\Seeder;

class DropDatabaseSeeder extends Seeder
{
    public function run()
    {
        // Menghapus database simlab_terpadu jika ada
        $this->db->query('DROP DATABASE IF EXISTS simlab_terpadu');
    }
}