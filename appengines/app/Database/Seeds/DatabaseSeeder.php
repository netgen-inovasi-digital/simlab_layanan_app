<?php

namespace App\Database\Seeds;

use CodeIgniter\Database\Seeder;

class DatabaseSeeder extends Seeder
{
    public function run()
    {
        // Seeder untuk template lengkap
        $this->call('SimlabSeeder');

        // Seeder khusus aplikasi SIMLAB
        $this->call('RolesSeeder');
        $this->call('SimlabAccountSeeder');
        $this->call('SimlabAccountUsersSeeder');
        $this->call('AlatSeeder');
        $this->call('DiskonSeeder');
        $this->call('JenisSeeder');
        $this->call('KolomKeuanganDetailSeeder');
        $this->call('LayananPengujianSeeder');
        $this->call('ParameterSeeder');
        $this->call('SimlabTLayananSeeder');       // panggil layanan utama dulu
        $this->call('SimlabTLayananDetilSeeder');  // baru detilnya
        $this->call('SimlabTPembayaranSeeder');

        // Seeder tambahan jika ada:
        // $this->call('UsersSeeder');
        // $this->call('MenusSeeder');
        // $this->call('OtoritasSeeder');
        // dst...
    }
}
