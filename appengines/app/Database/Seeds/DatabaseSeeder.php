<?php

namespace App\Database\Seeds;

use CodeIgniter\Database\Seeder;

class DatabaseSeeder extends Seeder
{
    public function run()
    {
        // Seeder untuk template lengkap
        // $this->call('SimlabSeeder');
        $this->call('CompleteNetxTemplateSeeder');


        // Seeder khusus aplikasi SIMLAB
        $this->call('RolesSeeder');
        $this->call('SimlabAccountSeeder');
        $this->call('AlatSeeder');
        $this->call('JenisSeeder');
        $this->call('ParameterSeeder'); 
        $this->call('RLayananPengujianSeeder');
        $this->call('RTimSeeder');
        $this->call('MenuSeeder');
        $this->call('OtoritasSeeder');
        $this->call('RMetodeSeeder');
        
        //ngasih transaksi
        // $this->call('TransaksiSeeder');
        // Seeder tambahan jika ada:
        // $this->call('UsersSeeder');
        // $this->call('MenusSeeder');
        // $this->call('OtoritasSeeder');
        // dst...
    }
}
