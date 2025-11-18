<?php 

namespace App\Database\Migrations;

use CodeIgniter\Database\Migration;

class CreateRLayananPengujian extends Migration
{
    public function up()
    {
        $this->forge->addField([
            'kode' => [
                'type' => 'INT',
                'unsigned' => true,
                'auto_increment' => true,
            ],
            'nama_layanan' => [
                'type' => 'VARCHAR',
                'constraint' => 255,
                'null' => true,
            ],
            'kode_alat' => [
                'type' => 'VARCHAR',
                'constraint' => 50,
                'null' => true,
            ],
            'kode_parameter' => [
                'type' => 'VARCHAR',
                'constraint' => 20,
                'null' => true,
            ],
            'kode_jenis' => [
                'type' => 'VARCHAR',
                'constraint' => 2,
                'null' => true,
            ],
            'satuan' => [
                'type' => 'VARCHAR',
                'constraint' => 15,
                'null' => true,
            ],
            'biaya' => [
                'type' => 'DOUBLE',
                'null' => true,
            ],
            'diskon' => [
                'type' => 'DOUBLE',
                'null' => true,
            ],
        ]);

        $this->forge->addKey('kode', true);
        $this->forge->addKey('kode_alat');
        $this->forge->addKey('kode_parameter');
        $this->forge->addKey('kode_jenis');

        $this->forge->createTable('r_layanan_pengujian');

        // Jika FK lama mungkin sudah ada, drop dulu (agar migration idempotent pada DB yang sudah ada FK)
        $db = \Config\Database::connect();
        // Hapus FK lama jika ada (tidak error jika tidak ada) - silakan jalankan ini sebelum menambah FK baru
        // NOTE: beberapa versi MySQL/MariaDB tidak support IF EXISTS untuk DROP FOREIGN KEY,
        // tapi mengeksekusi DROP pada nama yang tidak ada akan error — jika yakin nama, gunakan; 
        // di sini kita coba perlakuan aman: cek information_schema sebelum drop.
        $db->query("
            DELETE FROM information_schema.REFERENTIAL_CONSTRAINTS
            WHERE CONSTRAINT_SCHEMA = DATABASE()
              AND CONSTRAINT_NAME IN (
                'fk_rlaypeng_paraKode',
                'fk_rlaypeng_alatKode',
                'fk_rlaypeng_jenKode'
              );
        ");

        // Tambah FK baru semua dengan CASCADE
        $db->query("
            ALTER TABLE `r_layanan_pengujian`
                ADD CONSTRAINT `fk_rlaypeng_paraKode` 
                    FOREIGN KEY (`kode_parameter`) REFERENCES `simlab_r_parameter` (`paraKode`) 
                    ON DELETE CASCADE ON UPDATE CASCADE,
                ADD CONSTRAINT `fk_rlaypeng_alatKode` 
                    FOREIGN KEY (`kode_alat`) REFERENCES `simlab_r_alat` (`alatKode`) 
                    ON DELETE CASCADE ON UPDATE CASCADE,
                ADD CONSTRAINT `fk_rlaypeng_jenKode` 
                    FOREIGN KEY (`kode_jenis`) REFERENCES `simlab_r_jenis` (`jenKode`) 
                    ON DELETE CASCADE ON UPDATE CASCADE;
        ");
    }

    public function down()
    {
        // drop table (akan otomatis menghapus FK juga)
        $this->forge->dropTable('r_layanan_pengujian', true);
    }
}
