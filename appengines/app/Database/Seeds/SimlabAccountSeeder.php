<?php

namespace App\Database\Seeds;

use CodeIgniter\Database\Seeder;

class SimlabAccountSeeder extends Seeder
{
    public function run()
    {
        $data = [
            [
                'username'     => 'admin',
                'role_id'     => 1,
                'password'    => '$2y$10$Iq/xB7OEi7Y8e7U4Dbu9r.CHyFMFGkhvH8NBtOB0Fdn5ETmmaGIma',
                'lab_kode'    => null,
                'status_user' => 1,
            ],
            [
                'username'     => 'aku raja',
                'role_id'     => 8,
                'password'    => '$2y$10$1I5a03VBHVXuIOjQvYw//eA.kCoqXoge5w.6nfhrgq0lWSC5LUw5q',
                'lab_kode'    => null,
                'status_user' => 1,
            ],
            [
                'username'     => 'laboranA',
                'role_id'     => 5,
                'password'    => '$2y$10$UOP.KHsS/b3ShkPKjzV83uOSC2QEU04Bmtymk4fiRBfpIXY62Eq3.',
                'lab_kode'    => 'AB',
                'status_user' => 0,
            ],
            [
                'username'     => 'laboranB',
                'role_id'     => 5,
                'password'    => '$2y$10$CEIyegAPveVa9S9YHSM4M.R5kUBJt3adAyqFVh7wI.WmhTJPo9Api',
                'lab_kode'    => 'BB',
                'status_user' => 1,
            ],
            [
                'username'     => 'laboranC',
                'role_id'     => 5,
                'password'    => '$2y$10$Qp7wV3czs196aLgGz/chSemWOqYdTpuwgkqpmSD0xRuDZulOw.4W2',
                'lab_kode'    => 'CC',
                'status_user' => 1,
            ],
            [
                'username'     => 'laboranD',
                'role_id'     => 5,
                'password'    => '$2y$10$o73hgS3BO/IXWSG5NLpMD.WkBZliUhMu4eEFLkygxkmrUt79VXXF2',
                'lab_kode'    => 'DD',
                'status_user' => 0,
            ],
            [
                'username'     => 'laboranE',
                'role_id'     => 5,
                'password'    => '$2y$10$mocE1kRwZJ4B4sNKXzoLHeZ2NzGh5zoG2jk0EZq..I2FpO6Hqqs72',
                'lab_kode'    => 'EE',
                'status_user' => 1,
            ],
            [
                'username'     => 'manajerteknis',
                'role_id'     => 4,
                'password'    => '$2y$10$Iq/xB7OEi7Y8e7U4Dbu9r.CHyFMFGkhvH8NBtOB0Fdn5ETmmaGIma',
                'lab_kode'    => '',
                'status_user' => 1,
            ],
            [
                'username'     => 'penyelia',
                'role_id'     => 6,
                'password'    => '$2y$10$Iq/xB7OEi7Y8e7U4Dbu9r.CHyFMFGkhvH8NBtOB0Fdn5ETmmaGIma',
                'lab_kode'    => '',
                'status_user' => 1,
            ],
            [
                'username'     => 'superadmin',
                'role_id'     => 8,
                'password'    => '$2y$10$Iq/xB7OEi7Y8e7U4Dbu9r.CHyFMFGkhvH8NBtOB0Fdn5ETmmaGIma',
                'lab_kode'    => null,
                'status_user' => 1,
            ],
        ];

        // Masukkan data ke tabel simlab_account
        $this->db->table('simlab_account')->insertBatch($data);
    }
}
