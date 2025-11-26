<?php

namespace App\Database\Seeds;

use CodeIgniter\Database\Seeder;

class FileUmumSeeder extends Seeder
{
    public function run()
    {
        $this->db->table('file_umum')->truncate();

        $this->db->table('file_umum')->insertBatch([
            [
                'user_id' => 1, // Assuming user_id 1 exists
                'judul' => 'Surat pernyataan ULM',
                'file_path' => 'none',
                'deskripsi' => 'File terletak di profil user yang bisa diunduh',
                'status' => 'active',
                'tanggal' => date('Y-m-d'),
                'created_at' => date('Y-m-d H:i:s'),
                'updated_at' => date('Y-m-d H:i:s')
            ]
        ]);
    }
}