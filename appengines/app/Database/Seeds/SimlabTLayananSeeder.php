<?php

namespace App\Database\Seeds;

use CodeIgniter\Database\Seeder;

class SimlabTLayananSeeder extends Seeder
{
    public function run()
    {
        $data = [
            [
                'lnKode'          => 68,
                'lnAccEmail'      => '1911012310007@mhs.ulm.ac.id',
                'lnNoTransaksi'   => null,
                'lnTgl'           => '2024-07-29 09:25:24',
                'lnTipe'          => 'ULM',
                'lnOrangNama'     => 'Muhammad Fauzan Arya',
                'lnOrangIdentitas'=> null,
                'lnOrangTelp'     => '081257881630',
                'lnOrangEmail'    => '1911012310007@mhs.ulm.ac.id',
                'lnInstansi'      => null,
                'lnStatus'        => 0,
                'lnNoUrut'        => null,
                'lnPosting'       => null,
            ],
            [
                'lnKode'          => 70,
                'lnAccEmail'      => '1911012310007@mhs.ulm.ac.id',
                'lnNoTransaksi'   => 'ULM2024070001',
                'lnTgl'           => '2024-03-07 00:00:00',
                'lnTipe'          => 'ULM',
                'lnOrangNama'     => 'Muhammad Fauzan Arya',
                'lnOrangIdentitas'=> null,
                'lnOrangTelp'     => '081257881630',
                'lnOrangEmail'    => '1911012310007@mhs.ulm.ac.id',
                'lnInstansi'      => null,
                'lnStatus'        => 5,
                'lnNoUrut'        => null,
                'lnPosting'       => 1,
            ],
            [
                'lnKode'          => 73,
                'lnAccEmail'      => 'wiraraza2021@gmail.com',
                'lnNoTransaksi'   => 'ULM2024070003',
                'lnTgl'           => '2024-03-07 00:00:00',
                'lnTipe'          => 'NON ULM',
                'lnOrangNama'     => 'Ikhwan Wirahadikusuma',
                'lnOrangIdentitas'=> null,
                'lnOrangTelp'     => '081251163819',
                'lnOrangEmail'    => 'wiraraza2021@gmail.com',
                'lnInstansi'      => 'Instalasi Farmasi RSUD Ratu Zaleha',
                'lnStatus'        => 4,
                'lnNoUrut'        => null,
                'lnPosting'       => null,
            ],
            // Tambahkan data lainnya sesuai file layanan.txt
        ];

        $this->db->table('simlab_t_layanan')->insertBatch($data);
    }
}
