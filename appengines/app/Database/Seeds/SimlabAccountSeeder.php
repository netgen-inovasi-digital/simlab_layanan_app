<?php

namespace App\Database\Seeds;

use CodeIgniter\Database\Seeder;

class SimlabAccountSeeder extends Seeder
{
    public function run()
    {
        $data = [
            // mengikuti dump SQL yang diberikan (user_id explicit)
            ['user_id' => 1,  'username' => 'admin',      'nama' => null,           'role_id' => 1, 'password' => '$2y$10$Iq/xB7OEi7Y8e7U4Dbu9r.CHyFMFGkhvH8NBtOB0Fdn5ETmmaGIma', 'status_user' => 1, 'Telepon' => null],
            ['user_id' => 8,  'username' => 'manajerA',  'nama' => 'abang mamat',  'role_id' => 4, 'password' => '$2y$10$Iq/xB7OEi7Y8e7U4Dbu9r.CHyFMFGkhvH8NBtOB0Fdn5ETmmaGIma', 'status_user' => 1, 'Telepon' => null],
            ['user_id' => 9,  'username' => 'penyeliaA', 'nama' => 'faisal',       'role_id' => 6, 'password' => '$2y$10$Iq/xB7OEi7Y8e7U4Dbu9r.CHyFMFGkhvH8NBtOB0Fdn5ETmmaGIma', 'status_user' => 1, 'Telepon' => null],
            ['user_id' => 10, 'username' => 'superadmin','nama' => null,           'role_id' => 8, 'password' => '$2y$10$Iq/xB7OEi7Y8e7U4Dbu9r.CHyFMFGkhvH8NBtOB0Fdn5ETmmaGIma', 'status_user' => 1, 'Telepon' => null],
            ['user_id' => 11, 'username' => 'penyeliaB', 'nama' => 'mawar',        'role_id' => 6, 'password' => '$2y$10$z9kf80Uuh5FdWlVh4PNgDeUTg/8w/y5sZHIkue.smg..z2aL0eI2.', 'status_user' => 1, 'Telepon' => null],
            ['user_id' => 12, 'username' => 'manajerB',  'nama' => 'faisal',       'role_id' => 4, 'password' => '$2y$10$/nxgm/0dpm8RrtOyCnd5KeSUHIkDT0V0trbNU07tPgtvAyOjpD.R.', 'status_user' => 1, 'Telepon' => null],
            ['user_id' => 23, 'username' => 'manajerC',  'nama' => 'ahmad sahroni','role_id' => 4, 'password' => '$2y$10$/nxgm/0dpm8RrtOyCnd5KeSUHIkDT0V0trbNU07tPgtvAyOjpD.R.', 'status_user' => 1, 'Telepon' => null],
            ['user_id' => 30, 'username' => 'faisal',    'nama' => 'penyeliad',    'role_id' => 6, 'password' => '$2y$10$FqP6y/ZdrwP9PZiCfkPJCeqwQNMEcFZ1Q1zEO9BGPHOGbnRybWTYW', 'status_user' => 1, 'Telepon' => null],
            ['user_id' => 33, 'username' => 'akbar',     'nama' => 'akbar',        'role_id' => 6, 'password' => '$2y$10$al6NeG3A6svR7TfQkmZHne3Aws5SMAzcf6S/9VERBD2nCOT7x3mP2', 'status_user' => 1, 'Telepon' => null],
            ['user_id' => 34, 'username' => 'faisalc',   'nama' => 'faisal',       'role_id' => 6, 'password' => '$2y$10$IKqvAhm8Mi.W6U2lr2aW0O/Ve4fOl7/M4oIWN5pCG85Hbw1foV.D2', 'status_user' => 1, 'Telepon' => null],
            ['user_id' => 35, 'username' => 'faisalp',   'nama' => 'p',            'role_id' => 6, 'password' => '$2y$10$QAkQYScjoN8/279OpG2qkeInC5yQvUsGES6zh9VzFG5vCLpM7C.hm', 'status_user' => 1, 'Telepon' => null],
            ['user_id' => 36, 'username' => 'faisalk',   'nama' => '1',            'role_id' => 6, 'password' => '$2y$10$qz2B.B5qGLRvW9VV7Pn75OvA51m4Jk8bJkghmL5I6a3PXsZ1U/hAG', 'status_user' => 1, 'Telepon' => null],
            ['user_id' => 37, 'username' => 'faisalj',   'nama' => 'faisal',       'role_id' => 6, 'password' => '$2y$10$EpQ0pB/RkLWZewq27EHEaugunxAVpVHxK2cYas30Xu7.itbklMCre', 'status_user' => 1, 'Telepon' => null],
            ['user_id' => 38, 'username' => 'faisall',   'nama' => 'cek',          'role_id' => 6, 'password' => '$2y$10$DIfnjawJxfkFEBzLpBRC6uQLFgbehuIgzMNzGXfRWN4FjgUVXVxX.', 'status_user' => 1, 'Telepon' => null],
            ['user_id' => 39, 'username' => 'faisaly',   'nama' => 'cek',          'role_id' => 6, 'password' => '$2y$10$xzNzDAWdbTW8t1jzipMSvuvvEX06J1MJUKxxUumFOAOUucksvMsA6', 'status_user' => 1, 'Telepon' => null],
            ['user_id' => 40, 'username' => 'faisalll',  'nama' => '1',            'role_id' => 6, 'password' => '$2y$10$EC1Efq8qWWdBtPe/67549OTx.nSc9HHJ5H9hGhPChFjXAXxLr9hqy', 'status_user' => 1, 'Telepon' => null],
            ['user_id' => 41, 'username' => 'penyeliaC', 'nama' => null,           'role_id' => 6, 'password' => '$2y$10$/nxgm/0dpm8RrtOyCnd5KeSUHIkDT0V0trbNU07tPgtvAyOjpD.R.', 'status_user' => 1, 'Telepon' => null],
            ['user_id' => 42, 'username' => 'manajerD',  'nama' => null,           'role_id' => 4, 'password' => '$2y$10$Yr6Oq/xf2Zs4o9Smo11hau4UkHv7iZDHN64BSHjYT/vvhxJ1hl5vO', 'status_user' => 1, 'Telepon' => null],
        ];

        $this->db->table('simlab_account')->insertBatch($data);
    }
}
