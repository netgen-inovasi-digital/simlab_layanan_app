<?php

namespace App\Database\Seeds;

use CodeIgniter\Database\Seeder;

class SimlabSeeder extends Seeder
{
    public function run()
    {
        // categories
        $this->db->table('categories')->insertBatch([
            ['id_categories' => 46, 'nama' => 'Umum', 'slug' => 'umum', 'created_at' => '2025-07-16 07:13:19'],
            ['id_categories' => 49, 'nama' => 'dokumen A', 'slug' => 'dokumen-a', 'created_at' => '2025-08-11 22:00:40'],
        ]);

        // hero
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

        // konfigurasi
        $this->db->table('konfigurasi')->insert([
            'id_konfigurasi' => 1,
            'nama_profil' => 'Netx Template',
            'deskripsi' => 'NetX Template adalah sebuah starter template engine berbasis CodeIgniter 4 (CI4) yang dirancang untuk memudahkan pengembangan website dengan struktur yang rapi, modular, dan siap pakai. ',
            'alamat' => 'Kota Banjarbaru, Kalimantan Selatan',
            'telepon' => '083159236448',
            'email' => 'netgen.id@gmail.com',
            'kota' => 'Banjarbaru',
            'provinsi' => 'Kalimantan Selatan',
            'logo' => '1754489500f9b1f5b62d.jpg',
            'peta' => '<iframe src="https://www.google.com/maps/embed?pb=!1m18!1m12!1m3!1d3982.668147624301!2d114.8010200744995!3d-3.4307116417292853!2m3!1f0!2f0!3f0!3m2!1i1024!2i768!4f13.1!3m3!1m2!1s0x2de683004aea87fd%3A0x908679b896616ec2!2sKlinik%20dan%20Apotek%20Medikidz!5e0!3m2!1sen!2sid!4v1751200717274!5m2!1sen!2sid" width="600" height="450" style="border:0;" allowfullscreen="" loading="lazy" referrerpolicy="no-referrer-when-downgrade"></iframe>',
            'rajaongkir_api_key' => 'Cs6xweDrd1a1384d96a3d754VgCErom8',
            'rajaongkir_origin_subdistrict_id' => '3079',
            'rajaongkir_origin_name' => 'BANGKAL, BANJARBARU, KALIMANTAN SELATAN',
            'rajaongkir_couriers' => 'jne,sicepat,jnt,pos,tiki',
            'link' => 'profil'
        ]);

        // landing_views (minimal row as in partial)
        $this->db->table('landing_views')->insert([
            'id_landing_views' => 1,
            'viewed_at' => '2025-08-22 14:16:54',
        ]);

        // layanan
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

        // layout
        $this->db->table('layout')->insertBatch([
            [
                'id_layout' => 1,
                'kode' => 'hero',
                'html_section' => "layout html\r\n",
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

        // mitra
        $this->db->table('mitra')->insertBatch([
            ['id_mitra' => 14, 'nama' => 'BIMA', 'foto' => '175293305338af992638.png', 'urutan' => 3, 'status' => 'Y'],
            ['id_mitra' => 16, 'nama' => 'Tut Wuri Handayani', 'foto' => '17529330137b4677912f.png', 'urutan' => 1, 'status' => 'Y'],
            ['id_mitra' => 19, 'nama' => 'LPPM', 'foto' => '1752933028429a38c250.png', 'urutan' => 2, 'status' => 'Y'],
            ['id_mitra' => 20, 'nama' => 'DIKTISAINTEK BERDAMPAK', 'foto' => '1752933097403cb1c616.png', 'urutan' => 4, 'status' => 'Y'],
            ['id_mitra' => 23, 'nama' => 'Tut Wuri Handayani', 'foto' => '175293315576c3452791.png', 'urutan' => 5, 'status' => 'Y'],
            ['id_mitra' => 24, 'nama' => 'LPPM', 'foto' => '1752933169766c556b5d.png', 'urutan' => 6, 'status' => 'Y'],
            ['id_mitra' => 25, 'nama' => 'BIMA', 'foto' => '1752933187684626179c.png', 'urutan' => 7, 'status' => 'Y'],
            ['id_mitra' => 26, 'nama' => 'DIKTISAINTEK BERDAMPAK', 'foto' => '17529332156624c13061.png', 'urutan' => 8, 'status' => 'Y'],
            ['id_mitra' => 27, 'nama' => 'Tut Wuri Handayani', 'foto' => '1752933992112ee8c1dc.png', 'urutan' => 9, 'status' => 'Y'],
            ['id_mitra' => 28, 'nama' => 'LPPM', 'foto' => '1752934005f52689f1b2.png', 'urutan' => 10, 'status' => 'Y'],
        ]);

        // motifs
        $this->db->table('motifs')->insertBatch([
            ['id' => 1, 'name' => 'Dragon', 'deskripsi' => 'Dragon adalah naga', 'foto' => '1754487655f4c01333af.jpg'],
            ['id' => 4, 'name' => 'coba', 'deskripsi' => 'iya', 'foto' => '175963978578397eab6a.jpg'],
            ['id' => 5, 'name' => 'coba lagi', 'deskripsi' => 'apa coba', 'foto' => '175963990908c3f485b1.jpg'],
            ['id' => 6, 'name' => 'lagi', 'deskripsi' => 'lagi dong', 'foto' => '175964004215fcc46ddb.png'],
        ]);

        // navbar
        $this->db->table('navbar')->insertBatch([
            ['id_navbar' => 11, 'kode_navbar' => '1', 'kode_induk' => '0', 'nama' => 'News', 'url' => 'berita', 'status' => 'N', 'sort_order' => 1],
            ['id_navbar' => 31, 'kode_navbar' => '2', 'kode_induk' => '0', 'nama' => 'Youtube', 'url' => 'https://youtube.com/', 'status' => 'N', 'sort_order' => 2],
            ['id_navbar' => 33, 'kode_navbar' => '4', 'kode_induk' => '0', 'nama' => 'Layanan', 'url' => '#services', 'status' => 'Y', 'sort_order' => 4],
            ['id_navbar' => 34, 'kode_navbar' => '5', 'kode_induk' => '0', 'nama' => 'Team', 'url' => '#team', 'status' => 'N', 'sort_order' => 5],
            ['id_navbar' => 35, 'kode_navbar' => '6', 'kode_induk' => '0', 'nama' => 'Pengumuman', 'url' => '#notice', 'status' => 'Y', 'sort_order' => 6],
            ['id_navbar' => 36, 'kode_navbar' => '8', 'kode_induk' => '0', 'nama' => 'Mitra', 'url' => '#partner', 'status' => 'Y', 'sort_order' => 8],
            ['id_navbar' => 38, 'kode_navbar' => '7', 'kode_induk' => '0', 'nama' => 'Berita', 'url' => '#news', 'status' => 'Y', 'sort_order' => 7],
            ['id_navbar' => 40, 'kode_navbar' => '3', 'kode_induk' => '0', 'nama' => 'Profil', 'url' => 'hal/profil', 'status' => 'Y', 'sort_order' => 3],
        ]);

        // pages
        $this->db->table('pages')->insert([
            'id_pages' => 60,
            'user_id' => 1,
            'title' => 'Profil',
            'slug' => 'profil',
            'konten' => '<p><strong>Ecomel</strong> adalah platform e-commerce yang hadir untuk menghadirkan pengalaman belanja digital yang mudah, aman, dan memberdayakan. Dibangun dengan semangat lokal dan inovasi teknologi, Ecomel menghubungkan pelanggan dengan berbagai produk berkualitas dari seluruh Indonesia, sekaligus menjadi rumah digital bagi pelaku UMKM untuk tumbuh bersama.</p><h3>💡 Visi</h3><p><strong>Menjadi platform e-commerce terpercaya yang menghubungkan masyarakat Indonesia dengan produk berkualitas melalui teknologi yang sederhana dan inklusif.</strong></p><h3>🎯 Misi</h3><ol><li data-list="ordered"><span class="ql-ui" contenteditable="false"></span>Memberikan pengalaman belanja online yang praktis, cepat, dan menyenangkan.</li><li data-list="ordered"><span class="ql-ui" contenteditable="false"></span>Mendukung pertumbuhan UMKM dan produk lokal melalui teknologi digital.</li><li data-list="ordered"><span class="ql-ui" contenteditable="false"></span>Menyediakan sistem pembayaran dan pengiriman yang aman, transparan, dan efisien.</li><li data-list="ordered"><span class="ql-ui" contenteditable="false"></span>Menjadi mitra strategis bagi pengguna, mitra usaha, dan komunitas digital.</li></ol><h3>🌱 Nilai-Nilai Kami</h3><ol><li data-list="bullet"><span class="ql-ui" contenteditable="false"></span><strong>Integritas</strong> – Kami menjaga kepercayaan pelanggan dan mitra dengan transparansi dan tanggung jawab.</li><li data-list="bullet"><span class="ql-ui" contenteditable="false"></span><strong>Inovasi</strong> – Kami terus berkembang dan berinovasi untuk menciptakan solusi belanja yang lebih baik.</li><li data-list="bullet"><span class="ql-ui" contenteditable="false"></span><strong>Kebermanfaatan</strong> – Kami percaya bahwa teknologi harus memberi dampak positif bagi masyarakat.</li><li data-list="bullet"><span class="ql-ui" contenteditable="false"></span><strong>Kebersamaan</strong> – Kami tumbuh bersama pelanggan dan pelaku usaha dalam semangat kolaborasi.</li></ol><h3>🔍 Apa yang Membuat Ecomel Berbeda?</h3><ol><li data-list="bullet"><span class="ql-ui" contenteditable="false"></span><strong>Fokus pada Produk Lokal:</strong> Kami memprioritaskan brand dan usaha lokal untuk menjangkau pasar lebih luas.</li><li data-list="bullet"><span class="ql-ui" contenteditable="false"></span><strong>UI/UX Sederhana &amp; Ringan:</strong> Desain aplikasi kami dibuat untuk semua kalangan, bahkan yang baru pertama kali belanja online.</li><li data-list="bullet"><span class="ql-ui" contenteditable="false"></span><strong>Layanan Pelanggan Responsif:</strong> Tim kami siap membantu melalui berbagai kanal dengan cepat dan ramah.</li><li data-list="bullet"><span class="ql-ui" contenteditable="false"></span><strong>Promo dan Program Loyalitas:</strong> Kami menghadirkan promo menarik setiap hari dan sistem poin belanja yang menguntungkan.</li></ol><h3>📍 Lokasi Kantor</h3><p>Jl. Bhayangkara, Kel. Sungai Besar, Banjarbaru Selatan,</p><p> Kota Banjarbaru, Kalimantan Selatan 70714</p><p> 📧 Email: info@ecomel.id</p><p> 📞 Telepon: 08xx-xxxx-xxxx</p>',
            'status' => 'publish',
            'created_at' => '2025-06-29 00:00:00',
            'updated_at' => '2025-06-29 00:00:00',
            'published_at' => '2025-06-29 00:00:00',
            'views' => 27
        ]);

        // page_views (as in dump)
        $this->db->table('page_views')->insertBatch([
            ['id_page_views' => 1, 'page_id' => 60, 'viewed_at' => '2025-06-30 03:23:28'],
            ['id_page_views' => 2, 'page_id' => 60, 'viewed_at' => '2025-06-30 14:18:32'],
            ['id_page_views' => 3, 'page_id' => 60, 'viewed_at' => '2025-07-04 06:13:29'],
            ['id_page_views' => 4, 'page_id' => 60, 'viewed_at' => '2025-07-12 12:29:55'],
            ['id_page_views' => 5, 'page_id' => 60, 'viewed_at' => '2025-07-16 07:49:23'],
            ['id_page_views' => 6, 'page_id' => 60, 'viewed_at' => '2025-07-16 09:49:08'],
            ['id_page_views' => 7, 'page_id' => 60, 'viewed_at' => '2025-07-18 07:52:40'],
            ['id_page_views' => 8, 'page_id' => 60, 'viewed_at' => '2025-07-18 07:57:11'],
            ['id_page_views' => 9, 'page_id' => 60, 'viewed_at' => '2025-07-18 07:58:07'],
            ['id_page_views' => 10, 'page_id' => 60, 'viewed_at' => '2025-07-18 08:31:49'],
            ['id_page_views' => 11, 'page_id' => 60, 'viewed_at' => '2025-07-19 13:42:04'],
            ['id_page_views' => 12, 'page_id' => 60, 'viewed_at' => '2025-07-21 18:13:33'],
            ['id_page_views' => 13, 'page_id' => 60, 'viewed_at' => '2025-07-23 11:38:13'],
            ['id_page_views' => 14, 'page_id' => 60, 'viewed_at' => '2025-07-27 02:11:17'],
            ['id_page_views' => 15, 'page_id' => 60, 'viewed_at' => '2025-07-30 07:36:49'],
            ['id_page_views' => 16, 'page_id' => 60, 'viewed_at' => '2025-07-30 07:43:23'],
            ['id_page_views' => 17, 'page_id' => 60, 'viewed_at' => '2025-07-30 07:56:16'],
            ['id_page_views' => 18, 'page_id' => 60, 'viewed_at' => '2025-07-30 11:24:49'],
            ['id_page_views' => 19, 'page_id' => 60, 'viewed_at' => '2025-07-30 15:18:53'],
            ['id_page_views' => 20, 'page_id' => 60, 'viewed_at' => '2025-07-31 21:09:04'],
            ['id_page_views' => 21, 'page_id' => 60, 'viewed_at' => '2025-08-06 22:13:05'],
            ['id_page_views' => 22, 'page_id' => 60, 'viewed_at' => '2025-08-14 13:50:58'],
            ['id_page_views' => 23, 'page_id' => 60, 'viewed_at' => '2025-08-19 18:09:15'],
            ['id_page_views' => 24, 'page_id' => 60, 'viewed_at' => '2025-08-19 21:44:52'],
            ['id_page_views' => 25, 'page_id' => 60, 'viewed_at' => '2025-08-19 21:46:09'],
            ['id_page_views' => 26, 'page_id' => 60, 'viewed_at' => '2025-09-03 13:26:18'],
            ['id_page_views' => 27, 'page_id' => 60, 'viewed_at' => '2025-09-09 20:35:09'],
        ]);

        // pengumuman
        $this->db->table('pengumuman')->insertBatch([
            [
                'id_pengumuman' => 2,
                'user_id' => 1,
                'judul' => 'Pengumuman Maintenance Sistem Ecomel',
                'deskripsi' => "Halo, Sahabat Ecomel!\r\nKami akan melakukan maintenance sistem untuk peningkatan layanan pada:\r\n\r\n🗓️ Tanggal: Kamis, 18 Juli 2025\r\n⏰ Waktu: Pukul 23.00 – 03.00 WITA",
                'status' => 'tampil',
                'tanggal' => '2025-06-25'
            ],
            [
                'id_pengumuman' => 3,
                'user_id' => 1,
                'judul' => 'Pemberitahuan Keterlambatan Pengiriman',
                'deskripsi' => 'Kami informasikan bahwa terjadi gangguan distribusi akibat cuaca ekstrem di beberapa wilayah Kalimantan dan Sulawesi. Hal ini dapat menyebabkan keterlambatan pengiriman 1–3 hari dari estimasi awal.',
                'status' => 'tampil',
                'tanggal' => '2025-06-25'
            ],
            [
                'id_pengumuman' => 4,
                'user_id' => 1,
                'judul' => 'Promo Khusus Member Baru: Dapatkan Voucher Belanja!',
                'deskripsi' => "Kabar baik untuk kamu yang baru bergabung!\r\nDapatkan Voucher Belanja Rp25.000 tanpa minimum belanja, khusus untuk pengguna baru yang mendaftar akun Ecomel mulai 15–31 Juli 2025.",
                'status' => 'tampil',
                'tanggal' => '2025-08-01'
            ],
        ]);

        // posts
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
                'id_posts' => 55,
                'categories_id' => 35,
                'user_id' => 1,
                'title' => 'Peran Gizi Seimbang dalam Menjaga Daya Tahan Tubuh',
                'slug' => 'peran-gizi-seimbang-dalam-menjaga-daya-tahan-tubuh',
                'konten' => '<p>Tubuh memerlukan nutrisi lengkap untuk berfungsi optimal. Gizi seimbang mencakup karbohidrat, protein, lemak sehat, vitamin, dan mineral. Sayur dan buah memberikan serat serta antioksidan, sementara protein membantu membangun dan memperbaiki jaringan. Mengonsumsi makanan olahan secara berlebihan dapat menurunkan imunitas. Cobalah makan dengan porsi seimbang dan utamakan bahan makanan segar.</p>',
                'excerpt' => 'Tubuh memerlukan nutrisi lengkap untuk berfungsi optima...',
                'thumbnail' => '17508684608c4aef997b.jpg',
                'status' => 'publish',
                'created_at' => '2025-06-25 00:00:00',
                'updated_at' => '2025-06-23 00:00:00',
                'published_at' => '2025-06-23 00:00:00',
                'views' => 2
            ],
            [
                'id_posts' => 56,
                'categories_id' => 46,
                'user_id' => 1,
                'title' => 'Ecomel Day: Diskon Gede-Gedean Spesial 1 Bulan Peluncuran!',
                'slug' => 'mengelola-stres-untuk-kesehatan-mental-yang-lebih-baik',
                'konten' => '<p>Dalam rangka memperingati <strong>1 bulan peluncuran Ecomel</strong>, kami menghadirkan program <strong>Ecomel Day</strong>, yaitu promo besar-besaran selama 3 hari berturut-turut. Nikmati diskon hingga <strong>70%</strong> untuk semua kategori, <strong>flash sale setiap jam</strong>, dan <strong>gratis ongkir tanpa minimum belanja</strong>.</p><p>Program ini berlangsung mulai <strong>15 Agustus 2025</strong>. Jangan lewatkan kejutan tambahan berupa <strong>voucher cashback</strong> dan hadiah menarik untuk pelanggan aktif. Yuk, rayakan Ecomel Day dan jadikan belanja lebih hemat &amp; seru!</p>',
                'excerpt' => 'Dalam rangka memperingati 1 bulan peluncuran Ecomel, ka...',
                'thumbnail' => '17526500674de4f6c3b6.jpg',
                'status' => 'publish',
                'created_at' => '2025-06-25 00:00:00',
                'updated_at' => '2025-06-24 00:00:00',
                'published_at' => '2025-06-24 00:00:00',
                'views' => 12
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

        // post_views
        $this->db->table('post_views')->insertBatch([
            ['id_post_views' => 1, 'post_id' => 56, 'viewed_at' => '2025-06-30 03:21:43'],
            ['id_post_views' => 2, 'post_id' => 63, 'viewed_at' => '2025-06-30 03:54:24'],
            ['id_post_views' => 3, 'post_id' => 56, 'viewed_at' => '2025-06-30 14:17:57'],
            ['id_post_views' => 4, 'post_id' => 55, 'viewed_at' => '2025-06-30 14:18:11'],
            ['id_post_views' => 5, 'post_id' => 54, 'viewed_at' => '2025-07-01 03:20:27'],
            ['id_post_views' => 6, 'post_id' => 56, 'viewed_at' => '2025-07-01 03:20:53'],
            ['id_post_views' => 7, 'post_id' => 54, 'viewed_at' => '2025-07-01 04:15:46'],
            ['id_post_views' => 8, 'post_id' => 56, 'viewed_at' => '2025-07-03 05:29:34'],
            ['id_post_views' => 9, 'post_id' => 56, 'viewed_at' => '2025-07-03 13:02:01'],
            ['id_post_views' => 10, 'post_id' => 63, 'viewed_at' => '2025-07-03 13:02:18'],
            ['id_post_views' => 11, 'post_id' => 56, 'viewed_at' => '2025-07-04 06:13:02'],
            ['id_post_views' => 12, 'post_id' => 56, 'viewed_at' => '2025-07-04 10:29:00'],
            ['id_post_views' => 13, 'post_id' => 55, 'viewed_at' => '2025-07-04 10:29:16'],
            ['id_post_views' => 14, 'post_id' => 56, 'viewed_at' => '2025-07-04 13:37:38'],
            ['id_post_views' => 15, 'post_id' => 56, 'viewed_at' => '2025-07-12 13:35:30'],
            ['id_post_views' => 16, 'post_id' => 54, 'viewed_at' => '2025-07-17 06:31:12'],
            ['id_post_views' => 17, 'post_id' => 54, 'viewed_at' => '2025-07-17 07:56:20'],
            ['id_post_views' => 18, 'post_id' => 54, 'viewed_at' => '2025-07-18 02:17:03'],
            ['id_post_views' => 19, 'post_id' => 56, 'viewed_at' => '2025-07-23 11:47:36'],
            ['id_post_views' => 20, 'post_id' => 56, 'viewed_at' => '2025-07-23 14:14:25'],
            ['id_post_views' => 21, 'post_id' => 54, 'viewed_at' => '2025-07-26 05:50:20'],
            ['id_post_views' => 22, 'post_id' => 56, 'viewed_at' => '2025-07-27 02:19:54'],
        ]);

        // sosmed
        $this->db->table('sosmed')->insertBatch([
            ['id_sosmed' => 1, 'nama' => 'Facebook', 'link' => 'https://www.facebook.com/klinik', 'icon' => 'bi-facebook', 'status' => 'Y', 'urutan' => 0],
            ['id_sosmed' => 2, 'nama' => 'Instagram', 'link' => 'https://www.instagram.com/klinik', 'icon' => 'bi-instagram', 'status' => 'Y', 'urutan' => 0],
        ]);

        // team
        $this->db->table('team')->insert([
            'id_team' => 17,
            'nama' => 'dr. Iskandar, M.Kes., Sp.A',
            'spesialis' => 'Dokter Cinta',
            'foto' => '175325369259f09b9399.png',
            'urutan' => 1,
            'status' => 'Y',
            'link' => ''
        ]);

        // migrations
        $this->db->table('migrations')->insertBatch([
            ['id' => 1, 'version' => '2025_08_28_000001', 'class' => 'App\\Database\\Migrations\\CreateCompleteNetxTemplateDatabase', 'group' => 'default', 'namespace' => 'App', 'time' => 1762671377, 'batch' => 1],
            ['id' => 2, 'version' => '2025-09-14-030200', 'class' => 'App\\Database\\Migrations\\CreateSimlabAccountUsers', 'group' => 'default', 'namespace' => 'App', 'time' => 1762693021, 'batch' => 2],
            ['id' => 3, 'version' => '2025-09-14-030300', 'class' => 'App\\Database\\Migrations\\CreateSimlabAccount', 'group' => 'default', 'namespace' => 'App', 'time' => 1762693021, 'batch' => 2],
            ['id' => 4, 'version' => '2025-09-14-055100', 'class' => 'App\\Database\\Migrations\\CreateSimlabRAlat', 'group' => 'default', 'namespace' => 'App', 'time' => 1762693021, 'batch' => 2],
            ['id' => 5, 'version' => '2025-09-14-055200', 'class' => 'App\\Database\\Migrations\\CreateSimlabRJenis', 'group' => 'default', 'namespace' => 'App', 'time' => 1762693021, 'batch' => 2],
        ]);
    }
}
