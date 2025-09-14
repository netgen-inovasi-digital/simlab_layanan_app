<?php

namespace App\Database\Seeds;

use CodeIgniter\Database\Seeder;

class DatabaseSeeder extends Seeder
{
    public function run()
    {
        // Urutan penting: roles dulu, baru account & users
        $this->call('RolesSeeder');
        $this->call('SimlabAccountSeeder');
        $this->call('SimlabAccountUsersSeeder');
        $this->call('AlatSeeder');
        $this->call('DiskonSeeder');
        $this->call('JenisSeeder');
        $this->call('KolomKeuanganDetailSeeder');
        $this->call('LayananPengujianSeeder');
        $this->call('ParameterSeeder');
        $this->call('SimlabTLayananDetilSeeder');
        $this->call('SimlabTLayananSeeder');
        $this->call('SimlabTPembayaranSeeder');

        // Kalau punya seeder lain, panggil di sini juga:
        // $this->call('UsersSeeder');
        // $this->call('MenusSeeder');
        // $this->call('OtoritasSeeder');
        // $this->call('CategoriesSeeder');
        // dst...
    }
}
