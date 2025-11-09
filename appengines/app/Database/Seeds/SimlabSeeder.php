<?php

namespace App\Database\Seeds;

use CodeIgniter\Database\Seeder;

class SimlabSeeder extends Seeder
{
    public function run()
    {
        // 1. Categories
        $this->db->table('categories')->insertBatch([
            ['id_categories' => 46, 'nama' => 'Umum', 'slug' => 'umum', 'created_at' => '2025-07-16 07:13:19'],
            ['id_categories' => 49, 'nama' => 'dokumen A', 'slug' => 'dokumen-a', 'created_at' => '2025-08-11 22:00:40'],
        ]);

        // 2. Hero
        $this->db->table('hero')->insertBatch([
            [
                'id_hero' => 3,
                'judul' => 'Selamat Datang di Ecomel',
                'deskripsi' => 'Temukan kemudahan berbelanja online dengan pilihan produk terbaik dan harga bersahabat hanya di Ecomel.',
                'foto' => '1752649616757b72ad46.jpg',
                'urutan' => 2,
                'status' => 'Y'
            ],
            [
                'id_hero' => 4,
                'judul' => 'Promo Spesial Setiap Hari!',
                'deskripsi' => 'Nikmati potongan harga menarik untuk berbagai kebutuhan—dari fashion hingga kebutuhan rumah tangga.',
                'foto' => '1752649638665a712824.jpg',
                'urutan' => 1,
                'status' => 'Y'
            ],
            [
                'id_hero' => 5,
                'judul' => 'Dukung Produk Lokal',
                'deskripsi' => 'Belanja sambil berdampak! Temukan dan dukung UMKM lokal lewat produk-produk berkualitas pilihan.',
                'foto' => '1752649663f5ccb58ac3.jpg',
                'urutan' => 3,
                'status' => 'Y'
            ],
            [
                'id_hero' => 6,
                'judul' => 'Kirim Cepat, Sampai Tepat',
                'deskripsi' => 'Kami pastikan pesananmu dikirim dengan aman dan cepat ke seluruh Indonesia. Belanja tanpa khawatir.',
                'foto' => '17526496951cafae734c.jpg',
                'urutan' => 4,
                'status' => 'Y'
            ],
        ]);

        // 3. Konfigurasi
        $this->db->table('konfigurasi')->insert([
            'id_konfigurasi' => 1,
            'nama_profil' => 'Netx Template',
            'deskripsi' => 'NetX Template adalah sebuah starter template engine berbasis CodeIgniter 4 (CI4) yang dirancang untuk memudahkan pengembangan website dengan struktur yang rapi, modular, dan siap pakai.',
            'alamat' => 'Kota Banjarbaru, Kalimantan Selatan',
            'telepon' => '083159236448',
            'email' => 'netgen.id@gmail.com',
            'kota' => 'Banjarbaru',
            'provinsi' => 'Kalimantan Selatan',
            'logo' => '1754489500f9b1f5b62d.jpg',
            'peta' => '<iframe src="https://www.google.com/maps/embed?pb=!1m18!1m12!1m3!1d3982.668147624301!2d114.8010200744995!3d-3.4307116417292853!2m3!1f0!2f0!3f0!3m2!1i1024!2i768!4f13.1!3m3!1m2!1s0x2de683004aea87fd%3A0x908679b896616ec2!2sKlinik%20dan%20Apotek%20Medikidz!5e0!3m2!1sen!2sid!4v1751200717274!5m2!1sen!2sid" width="600" height="450" style="border:0;" allowfullscreen="" loading="lazy" referrerpolicy="no-referrer-when-downgrade"></iframe>',
            'link' => 'profil'
        ]);

        // 4. Layanan
        $this->db->table('layanan')->insertBatch([
            [
                'id_layanan' => 22,
                'judul' => 'Produk Ramah Lingkungan',
                'deskripsi' => 'Semua produk di Ecomel telah dikurasi untuk mendukung gaya hidup berkelanjutan, bebas dari bahan berbahaya dan lebih aman untuk bumi.',
                'foto' => '1753239440dbd259a32f.png',
                'urutan' => 1,
                'status' => 'Y',
                'link' => ''
            ],
            [
                'id_layanan' => 23,
                'judul' => 'Pilihan Produk Berkualitas',
                'deskripsi' => 'Ecomel menghadirkan produk dari brand terpercaya yang mengutamakan kualitas, keamanan, dan etika produksi.',
                'foto' => '175323955772941de000.png',
                'urutan' => 2,
                'status' => 'Y',
                'link' => ''
            ],
            [
                'id_layanan' => 24,
                'judul' => 'Pengiriman Cepat & Aman',
                'deskripsi' => 'Didukung oleh sistem logistik terpercaya, Ecomel memastikan barang sampai tepat waktu dalam kondisi terbaik.',
                'foto' => '175323964063bf5b939e.png',
                'urutan' => 3,
                'status' => 'Y',
                'link' => ''
            ],
            [
                'id_layanan' => 25,
                'judul' => 'Dukungan untuk UMKM Lokal',
                'deskripsi' => 'Dengan berbelanja di Ecomel, kamu turut mendukung para pelaku UMKM lokal yang bergerak di bidang produk ramah lingkungan.',
                'foto' => '1753239750a1ff7e9ba5.png',
                'urutan' => 4,
                'status' => 'Y',
                'link' => ''
            ],
            [
                'id_layanan' => 26,
                'judul' => 'Beragam Metode Pembayaran',
                'deskripsi' => 'Nikmati transaksi mudah dan aman dengan berbagai metode pembayaran, termasuk e-wallet dan transfer bank.',
                'foto' => '1753239818ddf5aa910f.png',
                'urutan' => 5,
                'status' => 'Y',
                'link' => ''
            ],
        ]);

        // 10. Landing views (optional in your snippet; included as per provided seeder example)
        $this->db->table('landing_views')->insert([
            'id_landing_views' => 1,
            'viewed_at' => '2025-08-22 14:16:54',
        ]);

        // 11. Pengumuman
        $this->db->table('pengumuman')->insertBatch([
            [
                'id_pengumuman' => 2,
                'user_id' => 1,
                'judul' => 'Pengumuman Maintenance Sistem Ecomel',
                'status' => 'tampil',
                'tanggal' => '2025-06-25'
            ],
            [
                'id_pengumuman' => 3,
                'user_id' => 1,
                'judul' => 'Pemberitahuan Keterlambatan Pengiriman',
                'status' => 'tampil',
                'tanggal' => '2025-06-25'
            ],
            [
                'id_pengumuman' => 4,
                'user_id' => 1,
                'judul' => 'Promo Khusus Member Baru: Dapatkan Voucher Belanja!',
                'status' => 'tampil',
                'tanggal' => '2025-08-01'
            ],
        ]);

        // 12. Posts
        $this->db->table('posts')->insertBatch([
            [
                'id_posts' => 54,
                'categories_id' => 46,
                'user_id' => 1,
                'title' => 'Ecomel Resmi Diluncurkan: Platform Belanja Digital Baru untuk Generasi Cerdas dan Hemat',
                'slug' => 'manfaat-berjalan-kaki-30-menit-setiap-hari',
                'konten' => '<p><strong>Banjarbaru, 15 Juli 2025</strong> – Sebuah platform e-commerce terbaru bernama <strong>Ecomel</strong> resmi diluncurkan dan siap menjadi solusi belanja digital yang cepat, mudah, dan ramah pengguna. Dengan mengusung slogan <em>"Belanja Mudah, Hidup Cerah"</em>, Ecomel menawarkan pengalaman berbelanja yang efisien dengan harga terjangkau dan dukungan pada produk-produk lokal unggulan.</p><p>Peluncuran Ecomel dilangsungkan secara daring melalui siaran langsung di kanal media sosial resmi dan disambut antusias oleh para pengguna awal, pelaku UMKM, serta komunitas digital di Kalimantan Selatan. Dalam sambutannya, CEO Ecomel, Muhammad Nazar Gunawan menyampaikan:</p><blockquote>"Kami membangun Ecomel dengan semangat untuk menghadirkan e-commerce yang bukan hanya praktis, tapi juga memberdayakan. Kami percaya bahwa teknologi bisa menjadi jembatan antara kualitas, aksesibilitas, dan pemberdayaan lokal."</blockquote><p>Ecomel hadir dengan fitur-fitur unggulan seperti:</p><ol><li data-list="bullet"><span class="ql-ui" contenteditable="false"></span>Navigasi super ringan &amp; mobile friendly</li><li data-list="bullet"><span class="ql-ui" contenteditable="false"></span>Promo harian dan sistem cashback</li><li data-list="bullet"><span class="ql-ui" contenteditable="false"></span>Kategori khusus produk lokal dan UMKM</li><li data-list="bullet"><span class="ql-ui" contenteditable="false"></span>Pembayaran digital aman dan pengiriman cepat</li></ol><p><br></p>',
                'excerpt' => 'Banjarbaru, 15 Juli 2025 – Sebuah platform e-commerce...',
                'thumbnail' => '1755143835d1c4284c77.jpg',
                'status' => 'publish',
                'created_at' => '2025-06-25 16:11:29',
                'updated_at' => '2025-08-14 11:57:15',
                'published_at' => '2025-06-25 00:00:00',
                'views' => 6
            ],
            [
                'id_posts' => 63,
                'categories_id' => 49,
                'user_id' => 1,
                'title' => 'Pentingnya Tidur Cukup untuk Kesehatan Tubuh dan Mental',
                'slug' => 'pentingnya-tidur-cukup-untuk-kesehatan-tubuh-dan-mental',
                'konten' => '<p>Tidur bukan sekadar istirahat â€" ini adalah kebutuhan dasar tubuh untuk memperbaiki dan memulihkan fungsi fisik serta mental. Kurang tidur dapat menyebabkan penurunan daya konsentrasi, gangguan suasana hati, dan penurunan sistem imun. Orang dewasa disarankan tidur 7â€"9 jam per malam. Untuk meningkatkan kualitas tidur, hindari layar sebelum tidur, jaga jadwal tidur yang konsisten, dan ciptakan lingkungan tidur yang nyaman dan gelap.</p>',
                'excerpt' => 'Tidur bukan sekadar istirahat â€" ini adalah kebutuhan ...',
                'thumbnail' => '17508752556585a56e62.jpg',
                'status' => 'publish',
                'created_at' => '2025-06-25 00:00:00',
                'updated_at' => '2025-06-22 00:00:00',
                'published_at' => '2025-06-22 00:00:00',
                'views' => 2
            ],
        ]);

        // 13. Sosmed
        $this->db->table('sosmed')->insertBatch([
            ['id_sosmed' => 1, 'nama' => 'Facebook', 'link' => 'https://www.facebook.com/klinik', 'icon' => 'bi-facebook', 'status' => 'Y', 'urutan' => 0],
            ['id_sosmed' => 2, 'nama' => 'Instagram', 'link' => 'https://www.instagram.com/klinik', 'icon' => 'bi-instagram', 'status' => 'Y', 'urutan' => 0],
        ]);

        // 14. Team
        $this->db->table('team')->insert([
            'id_team' => 17,
            'nama' => 'dr. Iskandar, M.Kes., Sp.A',
            'spesialis' => 'Dokter Cinta',
            'foto' => '175325369259f09b9399.png',
            'urutan' => 1,
            'status' => 'Y',
            'link' => ''
        ]);

        // 15. Layout
        $this->db->table('layout')->insertBatch([
            [
                'id_layout' => 1,
                'kode' => 'hero',
                'html_section' => 'layout html' . "\r\n",
                'konten_dinamis' => '{"judul": "Slider", "deskripsi": null}',
                'urutan' => 1,
                'status' => 'Y',
                'created_at' => '2025-08-22 14:16:54',
                'updated_at' => '2025-08-22 16:22:13'
            ],
            [
                'id_layout' => 2,
                'kode' => 'layanan',
                'html_section' => 'layout html',
                'konten_dinamis' => '{"judul":"Layanan","deskripsi":"Kami memiliki keunggulan dalam pelayanan untuk memenuhi kebutuhan Anda dan keluarga."}',
                'urutan' => 3,
                'status' => 'Y',
                'created_at' => '2025-08-22 14:16:54',
                'updated_at' => '2025-08-22 14:16:54'
            ],
            [
                'id_layout' => 3,
                'kode' => 'team',
                'html_section' => 'layout html',
                'konten_dinamis' => '{"judul":"Team","deskripsi":"Berikut adalah daftar tim Ecomel"}',
                'urutan' => 4,
                'status' => 'N',
                'created_at' => '2025-08-22 14:16:54',
                'updated_at' => '2025-08-22 14:16:54'
            ],
            [
                'id_layout' => 4,
                'kode' => 'mitra',
                'html_section' => 'layout html',
                'konten_dinamis' => '{"judul":"Mitra dan Partner Kami","deskripsi":"Kami bekerja sama dengan berbagai institusi terpercaya untuk mendukung layanan terbaik."}',
                'urutan' => 7,
                'status' => 'Y',
                'created_at' => '2025-08-22 14:16:54',
                'updated_at' => '2025-08-22 14:16:54'
            ],
            [
                'id_layout' => 5,
                'kode' => 'berita',
                'html_section' => 'layout html',
                'konten_dinamis' => '{"judul":"Berita \/ Event","deskripsi":"Kami menyediakan berita terbaru tentang Ecomel"}',
                'urutan' => 5,
                'status' => 'Y',
                'created_at' => '2025-08-22 14:16:54',
                'updated_at' => '2025-08-22 14:16:54'
            ],
            [
                'id_layout' => 6,
                'kode' => 'pengumuman',
                'html_section' => 'layout html',
                'konten_dinamis' => '{"judul": "Pengumuman", "deskripsi": null}',
                'urutan' => 6,
                'status' => 'Y',
                'created_at' => '2025-08-22 14:16:54',
                'updated_at' => '2025-08-22 14:16:54'
            ],
        ]);
    }
}
