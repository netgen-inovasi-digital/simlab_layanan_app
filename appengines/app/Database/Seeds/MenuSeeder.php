<?php

namespace App\Database\Seeds;

use CodeIgniter\Database\Seeder;

class MenuSeeder extends Seeder
{
    public function run()
    {
        // Kosongkan tabel terlebih dahulu agar state identik
        $this->db->table('menus')->truncate();

        $this->db->table('menus')->insertBatch([
            ['id_menu' => 6,  'kode_menu' => '21',   'kode_induk' => '0',  'nama' => 'Dashboard',                        'link' => 'dashboard/load',           'icon' => 'bi-house',               'sort_order' => 51],
            ['id_menu' => 9,  'kode_menu' => '22',   'kode_induk' => '0',  'nama' => 'Pengumuman',                       'link' => 'pengumuman',               'icon' => 'bi-megaphone',           'sort_order' => 52],
            ['id_menu' => 10, 'kode_menu' => '18',   'kode_induk' => '0',  'nama' => 'Halaman',                          'link' => 'pages',                    'icon' => 'bi-file-text',           'sort_order' => 43],
            ['id_menu' => 11, 'kode_menu' => '19',   'kode_induk' => '0',  'nama' => 'Tampilan',                         'link' => '#',                        'icon' => 'bi-display',             'sort_order' => 44],
            ['id_menu' => 13, 'kode_menu' => '19.3', 'kode_induk' => '19', 'nama' => 'Slider',                           'link' => 'hero',                     'icon' => 'bi-three-dots-vertical', 'sort_order' => 47],
            ['id_menu' => 14, 'kode_menu' => '19.1', 'kode_induk' => '19', 'nama' => 'Layanan',                          'link' => 'mitra',                    'icon' => 'bi-three-dots-vertical', 'sort_order' => 45],
            ['id_menu' => 15, 'kode_menu' => '19.5', 'kode_induk' => '19', 'nama' => 'Informasi',                        'link' => 'konfigurasi',              'icon' => 'bi-three-dots-vertical', 'sort_order' => 49],
            ['id_menu' => 16, 'kode_menu' => '19.4', 'kode_induk' => '19', 'nama' => 'Sosial',                           'link' => 'sosmed',                   'icon' => 'bi-three-dots-vertical', 'sort_order' => 48],
            ['id_menu' => 17, 'kode_menu' => '17',   'kode_induk' => '0',  'nama' => 'Urutan Tampilan',                  'link' => 'layout',                   'icon' => 'bi-layout-text-window',  'sort_order' => 42],
            ['id_menu' => 18, 'kode_menu' => '16',   'kode_induk' => '0',  'nama' => 'Pengaturan',                       'link' => '#',                        'icon' => 'bi-gear',                'sort_order' => 38],
            ['id_menu' => 19, 'kode_menu' => '16.1', 'kode_induk' => '16', 'nama' => 'Personalia',                       'link' => 'admin',                    'icon' => 'bi-person',              'sort_order' => 39],
            ['id_menu' => 21, 'kode_menu' => '16.2', 'kode_induk' => '16', 'nama' => 'Otoritas',                         'link' => 'otoritas',                 'icon' => 'bi-shield-check',        'sort_order' => 40],
            ['id_menu' => 23, 'kode_menu' => '16.3', 'kode_induk' => '16', 'nama' => 'Menu ',                            'link' => 'menu',                     'icon' => 'bi-people',              'sort_order' => 41],
            ['id_menu' => 24, 'kode_menu' => '20',   'kode_induk' => '0',  'nama' => 'Berita',                           'link' => 'posts',                    'icon' => 'bi-newspaper',           'sort_order' => 50],
            ['id_menu' => 28, 'kode_menu' => '19.2', 'kode_induk' => '19', 'nama' => 'Team',                             'link' => 'team',                     'icon' => 'bi-three-dots-vertical', 'sort_order' => 46],
            ['id_menu' => 34, 'kode_menu' => '11',   'kode_induk' => '0',  'nama' => 'Data Master',                      'link' => '#',                        'icon' => 'bi-layout-text-window',  'sort_order' => 28],
            ['id_menu' => 36, 'kode_menu' => '11.1', 'kode_induk' => '11', 'nama' => 'Kategori Layanan',                 'link' => 'jenis',                    'icon' => 'bi-three-dots-vertical', 'sort_order' => 29],
            ['id_menu' => 37, 'kode_menu' => '11.2', 'kode_induk' => '11', 'nama' => 'Alat',                             'link' => 'alat',                     'icon' => 'bi-three-dots-vertical', 'sort_order' => 30],
            ['id_menu' => 38, 'kode_menu' => '11.3', 'kode_induk' => '11', 'nama' => 'Parameter',                        'link' => 'parameter',                'icon' => 'bi-three-dots-vertical', 'sort_order' => 31],
            ['id_menu' => 39, 'kode_menu' => '11.4', 'kode_induk' => '11', 'nama' => 'Kolom Persentase',                 'link' => 'persentase',               'icon' => 'bi-three-dots-vertical', 'sort_order' => 32],
            ['id_menu' => 40, 'kode_menu' => '11.5', 'kode_induk' => '11', 'nama' => 'Layanan lab',                      'link' => 'lab',                      'icon' => 'bi-three-dots-vertical', 'sort_order' => 33],
            ['id_menu' => 44, 'kode_menu' => '2',    'kode_induk' => '0',  'nama' => 'Pembayaran User',                  'link' => 'pembayaran_user',          'icon' => 'bi-layout-text-window',  'sort_order' => 7],
            ['id_menu' => 46, 'kode_menu' => '5',    'kode_induk' => '0',  'nama' => 'Rekap',                            'link' => 'rekap',                    'icon' => 'bi-layout-text-window',  'sort_order' => 15],
            ['id_menu' => 47, 'kode_menu' => '3',    'kode_induk' => '0',  'nama' => 'Formulir Masuk',                   'link' => '#',                        'icon' => 'bi-layout-text-window',  'sort_order' => 8],
            ['id_menu' => 48, 'kode_menu' => '1',    'kode_induk' => '0',  'nama' => 'Layanan',                          'link' => '#',                        'icon' => 'bi-layout-text-window',  'sort_order' => 1],
            ['id_menu' => 49, 'kode_menu' => '4',    'kode_induk' => '0',  'nama' => 'Pembayaran Admin',                 'link' => 'pembayaran_admin',         'icon' => 'bi-layout-text-window',  'sort_order' => 14],
            ['id_menu' => 52, 'kode_menu' => '6',    'kode_induk' => '0',  'nama' => 'Pelanggan',                        'link' => 'akun',                     'icon' => 'bi-person',              'sort_order' => 16],
            ['id_menu' => 55, 'kode_menu' => '12',   'kode_induk' => '0',  'nama' => 'Formulir Masuk,',                  'link' => 'formulirmanajer',          'icon' => 'bi-layout-text-window',  'sort_order' => 34],
            ['id_menu' => 56, 'kode_menu' => '14',   'kode_induk' => '0',  'nama' => 'Hasil Pengujian',                  'link' => 'hasilpengujian',           'icon' => 'bi-layout-text-window',  'sort_order' => 36],
            ['id_menu' => 57, 'kode_menu' => '13',   'kode_induk' => '0',  'nama' => 'Tinjau LHUS',                      'link' => 'tinjaulhus',               'icon' => 'bi-layout-text-window',  'sort_order' => 35],
            ['id_menu' => 58, 'kode_menu' => '10.1', 'kode_induk' => '10', 'nama' => 'Penyelia',                         'link' => 'penyelia',                 'icon' => 'bi-person',              'sort_order' => 26],
            ['id_menu' => 59, 'kode_menu' => '10.2', 'kode_induk' => '10', 'nama' => 'Manajer Teknis',                   'link' => 'manajerteknis',            'icon' => 'bi-person',              'sort_order' => 27],
            ['id_menu' => 60, 'kode_menu' => '10',   'kode_induk' => '0',  'nama' => 'Pengelola',                        'link' => '#',                        'icon' => 'bi-layout-text-window',  'sort_order' => 25],
            ['id_menu' => 63, 'kode_menu' => '9',    'kode_induk' => '0',  'nama' => 'Pelaksanaan',                      'link' => '#',                        'icon' => 'bi-layout-text-window',  'sort_order' => 19],
            ['id_menu' => 64, 'kode_menu' => '7',    'kode_induk' => '0',  'nama' => 'kuisioner',                        'link' => 'kuesioner',                'icon' => 'bi-layout-text-window',  'sort_order' => 17],
            ['id_menu' => 65, 'kode_menu' => '8',    'kode_induk' => '0',  'nama' => 'Pengumuman',                       'link' => 'pengumuman',               'icon' => 'bi-layout-text-window',  'sort_order' => 18],
            ['id_menu' => 66, 'kode_menu' => '1.1',  'kode_induk' => '1',  'nama' => 'Uji Sampel',                       'link' => 'pelayanan',                'icon' => 'bi-three-dots-vertical', 'sort_order' => 2],
            ['id_menu' => 67, 'kode_menu' => '1.2',  'kode_induk' => '1',  'nama' => 'Sewa Alat',                        'link' => 'layanan',                  'icon' => 'bi-three-dots-vertical', 'sort_order' => 3],
            ['id_menu' => 68, 'kode_menu' => '1.3',  'kode_induk' => '1',  'nama' => 'Sewa Ruangan Lab',                 'link' => 'layanan',                  'icon' => 'bi-three-dots-vertical', 'sort_order' => 4],
            ['id_menu' => 69, 'kode_menu' => '1.4',  'kode_induk' => '1',  'nama' => 'Sewa Ruangan Rapat dan Jas Lab',  'link' => 'layanan',                  'icon' => 'bi-three-dots-vertical', 'sort_order' => 5],
            ['id_menu' => 70, 'kode_menu' => '1.5',  'kode_induk' => '1',  'nama' => 'Beli Aquades',                     'link' => 'layanan',                  'icon' => 'bi-three-dots-vertical', 'sort_order' => 6],
            ['id_menu' => 71, 'kode_menu' => '15',   'kode_induk' => '0',  'nama' => 'Surat Masuk',                      'link' => 'surat_masuk',              'icon' => 'bi-layout-text-window',  'sort_order' => 37],
            ['id_menu' => 72, 'kode_menu' => '9.1',  'kode_induk' => '9',  'nama' => 'Uji Sampel',                       'link' => 'pelaksanaan',              'icon' => 'bi-three-dots-vertical', 'sort_order' => 20],
            ['id_menu' => 73, 'kode_menu' => '9.2',  'kode_induk' => '9',  'nama' => 'Sewa Alat',                        'link' => 'pelaksanaan_alat',         'icon' => 'bi-three-dots-vertical', 'sort_order' => 21],
            ['id_menu' => 74, 'kode_menu' => '3.1',  'kode_induk' => '3',  'nama' => 'Uji Sampel',                       'link' => 'formuliradmin',            'icon' => 'bi-three-dots-vertical', 'sort_order' => 9],
            ['id_menu' => 75, 'kode_menu' => '3.2',  'kode_induk' => '3',  'nama' => 'Sewa Alat',                        'link' => 'formuliradmin_alat',       'icon' => 'bi-three-dots-vertical', 'sort_order' => 10],
            ['id_menu' => 76, 'kode_menu' => '3.3',  'kode_induk' => '3',  'nama' => 'Sewa Ruangan Lab',                 'link' => 'formuliradmin_lab',        'icon' => 'bi-three-dots-vertical', 'sort_order' => 11],
            ['id_menu' => 77, 'kode_menu' => '3.4',  'kode_induk' => '3',  'nama' => 'Sewa Ruangan Rapat dan Jas Lab',  'link' => 'formuliradmin_rapat_jas',  'icon' => 'bi-three-dots-vertical', 'sort_order' => 12],
            ['id_menu' => 78, 'kode_menu' => '3.5',  'kode_induk' => '3',  'nama' => 'Beli Aquades',                     'link' => 'formuliradmin_aquades',    'icon' => 'bi-three-dots-vertical', 'sort_order' => 13],
            ['id_menu' => 79, 'kode_menu' => '9.3',  'kode_induk' => '9',  'nama' => 'Sewa Ruangan Lab',                 'link' => 'pelaksanaan_lab',          'icon' => 'bi-three-dots-vertical', 'sort_order' => 22],
            ['id_menu' => 80, 'kode_menu' => '9.4',  'kode_induk' => '9',  'nama' => 'Sewa Ruangan Rapat dan Jas Lab',  'link' => 'pelaksanaan_rapat_jas',    'icon' => 'bi-three-dots-vertical', 'sort_order' => 23],
            ['id_menu' => 81, 'kode_menu' => '9.5',  'kode_induk' => '9',  'nama' => 'Beli Aquades',                     'link' => 'pelaksanaan_aquades',      'icon' => 'bi-three-dots-vertical', 'sort_order' => 24],
        ]);
    }
}
