<?php

namespace App\Database\Seeds;

use CodeIgniter\Database\Seeder;

class KolomKeuanganDetailSeeder extends Seeder
{
    public function run()
    {
        $data = [
            ['kdKode'=>1,  'kdJenKode'=>'H', 'kdKolomField'=>'ujiBiaya1', 'kdKolomLabel'=>'Bahan Kimia-',                     'kdPersenNONULM'=>34,        'kdPersenULM'=>null],
            ['kdKode'=>2,  'kdJenKode'=>'A', 'kdKolomField'=>'ujiBiaya2', 'kdKolomLabel'=>'Operasional',                       'kdPersenNONULM'=>13,        'kdPersenULM'=>null],
            ['kdKode'=>4,  'kdJenKode'=>'A', 'kdKolomField'=>'ujiBiaya4', 'kdKolomLabel'=>'Jasa Profesi',                      'kdPersenNONULM'=>46,        'kdPersenULM'=>null],
            ['kdKode'=>5,  'kdJenKode'=>'A', 'kdKolomField'=>'ujiBiaya5', 'kdKolomLabel'=>'Pendapatan Instansi',              'kdPersenNONULM'=>10,        'kdPersenULM'=>null],
            ['kdKode'=>6,  'kdJenKode'=>'B', 'kdKolomField'=>null,        'kdKolomLabel'=>'Jasa Profesi',                      'kdPersenNONULM'=>40,        'kdPersenULM'=>null],
            ['kdKode'=>8,  'kdJenKode'=>'B', 'kdKolomField'=>null,        'kdKolomLabel'=>'Operasional',                       'kdPersenNONULM'=>25,        'kdPersenULM'=>null],
            ['kdKode'=>9,  'kdJenKode'=>'B', 'kdKolomField'=>null,        'kdKolomLabel'=>'Pendapatan Instansi',              'kdPersenNONULM'=>10,        'kdPersenULM'=>null],
            ['kdKode'=>10, 'kdJenKode'=>'C', 'kdKolomField'=>null,        'kdKolomLabel'=>'Jasa Profesi',                      'kdPersenNONULM'=>50,        'kdPersenULM'=>null],
            ['kdKode'=>11, 'kdJenKode'=>'C', 'kdKolomField'=>null,        'kdKolomLabel'=>'Operasional',                       'kdPersenNONULM'=>10,        'kdPersenULM'=>null],
            ['kdKode'=>13, 'kdJenKode'=>'C', 'kdKolomField'=>null,        'kdKolomLabel'=>'Pendapatan Instansi',              'kdPersenNONULM'=>40,        'kdPersenULM'=>null],
            ['kdKode'=>14, 'kdJenKode'=>'D', 'kdKolomField'=>null,        'kdKolomLabel'=>'Jasa Profesi',                      'kdPersenNONULM'=>40,        'kdPersenULM'=>null],
            ['kdKode'=>15, 'kdJenKode'=>'D', 'kdKolomField'=>null,        'kdKolomLabel'=>'Operasional dan Seminar Kit',       'kdPersenNONULM'=>40,        'kdPersenULM'=>null],
            ['kdKode'=>17, 'kdJenKode'=>'D', 'kdKolomField'=>'ujiBiaya2', 'kdKolomLabel'=>'Konsumsi',                         'kdPersenNONULM'=>10,        'kdPersenULM'=>null],
            ['kdKode'=>18, 'kdJenKode'=>'D', 'kdKolomField'=>'ujiBiaya4', 'kdKolomLabel'=>'Pendapatan Instansi',              'kdPersenNONULM'=>10,        'kdPersenULM'=>null],
            ['kdKode'=>20, 'kdJenKode'=>'G', 'kdKolomField'=>null,        'kdKolomLabel'=>'jidan',                             'kdPersenNONULM'=>21,        'kdPersenULM'=>null],
            ['kdKode'=>21, 'kdJenKode'=>'J', 'kdKolomField'=>null,        'kdKolomLabel'=>'jdan',                              'kdPersenNONULM'=>11,        'kdPersenULM'=>null],
            ['kdKode'=>23, 'kdJenKode'=>'L', 'kdKolomField'=>null,        'kdKolomLabel'=>'Jasa Lidan',                        'kdPersenNONULM'=>50,        'kdPersenULM'=>null],
            ['kdKode'=>24, 'kdJenKode'=>'L', 'kdKolomField'=>null,        'kdKolomLabel'=>'jidan',                             'kdPersenNONULM'=>12,        'kdPersenULM'=>null],
            ['kdKode'=>25, 'kdJenKode'=>'A', 'kdKolomField'=>null,        'kdKolomLabel'=>'jidan',                             'kdPersenNONULM'=>11,        'kdPersenULM'=>null],
            ['kdKode'=>26, 'kdJenKode'=>'L', 'kdKolomField'=>null,        'kdKolomLabel'=>'Jasa Lidan',                        'kdPersenNONULM'=>12,        'kdPersenULM'=>null],
            ['kdKode'=>27, 'kdJenKode'=>'B', 'kdKolomField'=>null,        'kdKolomLabel'=>'Jasa Lidan',                        'kdPersenNONULM'=>129309011, 'kdPersenULM'=>null],
        ];

        $this->db->table('simlab_r_kolom_keuangan_detail')->insertBatch($data);
    }
}
