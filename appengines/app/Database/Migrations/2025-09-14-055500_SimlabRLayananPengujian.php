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
        // index keys
        $this->forge->addKey('kode_alat');
        $this->forge->addKey('kode_parameter');
        $this->forge->addKey('kode_jenis');

        $this->forge->createTable('r_layanan_pengujian');

        // foreign keys (pastikan tabel referensi sudah ada saat migration dijalankan)
        $db = \Config\Database::connect();
        $db->query('ALTER TABLE `r_layanan_pengujian`
            ADD CONSTRAINT `fk_rlaypeng_paraKode` FOREIGN KEY (`kode_parameter`) REFERENCES `simlab_r_parameter` (`paraKode`) ON DELETE RESTRICT ON UPDATE RESTRICT,
            ADD CONSTRAINT `fk_rlaypeng_alatKode` FOREIGN KEY (`kode_alat`) REFERENCES `simlab_r_alat` (`alatKode`) ON DELETE RESTRICT ON UPDATE RESTRICT,
            ADD CONSTRAINT `fk_rlaypeng_jenKode` FOREIGN KEY (`kode_jenis`) REFERENCES `simlab_r_jenis` (`jenKode`) ON DELETE RESTRICT ON UPDATE RESTRICT
        ;');
    }

    public function down()
    {
        $this->forge->dropTable('r_layanan_pengujian', true);
    }
}
