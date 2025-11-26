<?php

namespace App\Database\Seeds;

use CodeIgniter\Database\Seeder;

class MenuSeeder extends Seeder
{
    public function run()
    {
        $this->db->table('menus')->truncate();

        $this->db->table('menus')->insertBatch([
            // Pelayanan (sort_order: 1)
            ['id_menu' => 48, 'kode_menu' => '1',    'kode_induk' => '0',  'nama' => 'Pelayanan',                      'link' => 'pelayanan',              'icon' => 'bi-layout-text-window',  'sort_order' => 1],
            
            // Pembayaran User (sort_order: 2)
            ['id_menu' => 44, 'kode_menu' => '2',    'kode_induk' => '0',  'nama' => 'Pembayaran User',                'link' => 'pembayaran_user',        'icon' => 'bi-layout-text-window',  'sort_order' => 2],
            
            // Formulir Masuk (sort_order: 3)
            ['id_menu' => 47, 'kode_menu' => '3',    'kode_induk' => '0',  'nama' => 'Formulir Masuk',                 'link' => 'formuliradmin',          'icon' => 'bi-layout-text-window',  'sort_order' => 3],
            
            // Pembayaran Admin (sort_order: 4)
            ['id_menu' => 49, 'kode_menu' => '4',    'kode_induk' => '0',  'nama' => 'Pembayaran Admin',               'link' => 'pembayaran_admin',       'icon' => 'bi-layout-text-window',  'sort_order' => 4],
            
            // Penerbitan LHU (sort_order: 5)
            ['id_menu' => 63, 'kode_menu' => '10',   'kode_induk' => '0',  'nama' => 'Penerbitan LHU',                 'link' => 'pelaksanaan',            'icon' => 'bi-layout-text-window',  'sort_order' => 5],
            
            // Pelanggan (sort_order: 6)
            ['id_menu' => 52, 'kode_menu' => '6',    'kode_induk' => '0',  'nama' => 'Pelanggan',                      'link' => 'akun',                   'icon' => 'bi-person',              'sort_order' => 6],
            
            // Rekap (sort_order: 7)
            ['id_menu' => 46, 'kode_menu' => '5',    'kode_induk' => '0',  'nama' => 'Rekap',                          'link' => 'rekap',                  'icon' => 'bi-layout-text-window',  'sort_order' => 7],
            
            // Kuisioner (sort_order: 8)
            ['id_menu' => 64, 'kode_menu' => '7',    'kode_induk' => '0',  'nama' => 'Kuisioner',                      'link' => 'kuesioner',              'icon' => 'bi-layout-text-window',  'sort_order' => 8],
            
            // Pengumuman (sort_order: 9)
            ['id_menu' => 65, 'kode_menu' => '8',    'kode_induk' => '0',  'nama' => 'Pengumuman',                     'link' => 'pengumuman',             'icon' => 'bi-megaphone',           'sort_order' => 9],
            
            // File Umum (sort_order: 10)
            ['id_menu' => 84, 'kode_menu' => '25',   'kode_induk' => '0',  'nama' => 'File Umum',                      'link' => 'fileumum',               'icon' => 'bi-file-earmark',        'sort_order' => 10],
            
            // Pengelola (sort_order: 11)
            ['id_menu' => 60, 'kode_menu' => '11',   'kode_induk' => '0',  'nama' => 'Pengelola',                      'link' => '#',                      'icon' => 'bi-layout-text-window',  'sort_order' => 11],
            ['id_menu' => 58, 'kode_menu' => '11.1', 'kode_induk' => '11', 'nama' => 'Penyelia',                       'link' => 'pengelolaPenyelia',      'icon' => 'bi-person',              'sort_order' => 12],
            ['id_menu' => 59, 'kode_menu' => '11.2', 'kode_induk' => '11', 'nama' => 'Manajer Teknis',                 'link' => 'pengelolaManajer',       'icon' => 'bi-person',              'sort_order' => 13],
            
            // Data Master (sort_order: 14)
            ['id_menu' => 34, 'kode_menu' => '12',   'kode_induk' => '0',  'nama' => 'Data Master',                    'link' => '#',                      'icon' => 'bi-layout-text-window',  'sort_order' => 14],
            ['id_menu' => 36, 'kode_menu' => '12.1', 'kode_induk' => '12', 'nama' => 'Kategori Layanan',               'link' => 'kategoriLayanan',        'icon' => 'bi-three-dots-vertical', 'sort_order' => 15],
            ['id_menu' => 37, 'kode_menu' => '12.2', 'kode_induk' => '12', 'nama' => 'Alat',                           'link' => 'alat',                   'icon' => 'bi-three-dots-vertical', 'sort_order' => 16],
            ['id_menu' => 38, 'kode_menu' => '12.3', 'kode_induk' => '12', 'nama' => 'Parameter',                      'link' => 'parameter',              'icon' => 'bi-three-dots-vertical', 'sort_order' => 17],
            ['id_menu' => 39, 'kode_menu' => '12.4', 'kode_induk' => '12', 'nama' => 'Kolom Persentase',               'link' => 'persentase',             'icon' => 'bi-three-dots-vertical', 'sort_order' => 18],
            ['id_menu' => 82, 'kode_menu' => '12.5', 'kode_induk' => '12', 'nama' => 'Metode',                         'link' => 'metode',                 'icon' => 'bi-three-dots-vertical', 'sort_order' => 19],
            ['id_menu' => 40, 'kode_menu' => '12.6', 'kode_induk' => '12', 'nama' => 'Layanan Lab',                    'link' => 'layananLab',             'icon' => 'bi-three-dots-vertical', 'sort_order' => 20],
            
            // Pengaturan (sort_order: 21)
            ['id_menu' => 18, 'kode_menu' => '17',   'kode_induk' => '0',  'nama' => 'Pengaturan',                     'link' => '#',                      'icon' => 'bi-gear',                'sort_order' => 21],
            ['id_menu' => 19, 'kode_menu' => '17.1', 'kode_induk' => '17', 'nama' => 'Personalia',                     'link' => 'admin',                  'icon' => 'bi-person',              'sort_order' => 22],
            ['id_menu' => 21, 'kode_menu' => '17.2', 'kode_induk' => '17', 'nama' => 'Otoritas',                       'link' => 'otoritas',               'icon' => 'bi-shield-check',        'sort_order' => 23],
            ['id_menu' => 23, 'kode_menu' => '17.3', 'kode_induk' => '17', 'nama' => 'Menu',                           'link' => 'menu',                   'icon' => 'bi-people',              'sort_order' => 24],
            
            // Kaji Ulang (sort_order: 25)
            ['id_menu' => 55, 'kode_menu' => '13',   'kode_induk' => '0',  'nama' => 'Kaji Ulang',                     'link' => 'kajiulang',              'icon' => 'bi-layout-text-window',  'sort_order' => 25],
            
            // Tinjau LHUS (sort_order: 26)
            ['id_menu' => 57, 'kode_menu' => '14',   'kode_induk' => '0',  'nama' => 'Tinjau LHUS',                    'link' => 'tinjaulhus',             'icon' => 'bi-layout-text-window',  'sort_order' => 26],
            
            // Pengujian (sort_order: 27)
            ['id_menu' => 56, 'kode_menu' => '15',   'kode_induk' => '0',  'nama' => 'Pengujian',                      'link' => 'hasilpengujian',         'icon' => 'bi-layout-text-window',  'sort_order' => 27],
            
            // Urutan Tampilan (sort_order: 29)
            ['id_menu' => 17, 'kode_menu' => '18',   'kode_induk' => '0',  'nama' => 'Urutan Tampilan',                'link' => 'layout',                 'icon' => 'bi-layout-text-window',  'sort_order' => 29],
            
            // Halaman (sort_order: 30)
            ['id_menu' => 10, 'kode_menu' => '19',   'kode_induk' => '0',  'nama' => 'Halaman',                        'link' => 'pages',                  'icon' => 'bi-file-text',           'sort_order' => 30],
            
            // Tampilan (sort_order: 31)
            ['id_menu' => 11, 'kode_menu' => '20',   'kode_induk' => '0',  'nama' => 'Tampilan',                       'link' => '#',                      'icon' => 'bi-display',             'sort_order' => 31],
            ['id_menu' => 14, 'kode_menu' => '20.1', 'kode_induk' => '20', 'nama' => 'Layanan',                        'link' => 'mitra',                  'icon' => 'bi-three-dots-vertical', 'sort_order' => 32],
            ['id_menu' => 28, 'kode_menu' => '20.2', 'kode_induk' => '20', 'nama' => 'Team',                           'link' => 'team',                   'icon' => 'bi-three-dots-vertical', 'sort_order' => 33],
            ['id_menu' => 13, 'kode_menu' => '20.3', 'kode_induk' => '20', 'nama' => 'Slider',                         'link' => 'hero',                   'icon' => 'bi-three-dots-vertical', 'sort_order' => 34],
            ['id_menu' => 16, 'kode_menu' => '20.4', 'kode_induk' => '20', 'nama' => 'Sosial',                         'link' => 'sosmed',                 'icon' => 'bi-three-dots-vertical', 'sort_order' => 35],
            ['id_menu' => 15, 'kode_menu' => '20.5', 'kode_induk' => '20', 'nama' => 'Informasi',                      'link' => 'konfigurasi',            'icon' => 'bi-three-dots-vertical', 'sort_order' => 36],
            
            // Berita (sort_order: 37)
            ['id_menu' => 24, 'kode_menu' => '21',   'kode_induk' => '0',  'nama' => 'Berita',                         'link' => 'posts',                  'icon' => 'bi-newspaper',           'sort_order' => 37],
            
            // Dashboard (sort_order: 38)
            ['id_menu' => 6,  'kode_menu' => '22',   'kode_induk' => '0',  'nama' => 'Dashboard',                      'link' => 'dashboard/load',         'icon' => 'bi-house',               'sort_order' => 38],
        ]);
    }
}
