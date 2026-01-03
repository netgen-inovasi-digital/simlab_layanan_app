<?php 

namespace App\Database\Migrations;

use CodeIgniter\Database\Migration;
use Config\Database;

class CreateRLayananPengujian extends Migration
{
    public function up()
    {
        $this->forge->addField([
            'kode' => [
                'type'           => 'INT',
                'unsigned'       => true,
                'auto_increment' => true,
            ],
            'nama_layanan' => [
                'type'       => 'VARCHAR',
                'constraint' => 255,
                'null'       => true,
            ],
            'kode_alat' => [
                'type'       => 'VARCHAR',
                'constraint' => 50,
                'null'       => true,
            ],
            'kode_parameter' => [
                'type'       => 'VARCHAR',
                'constraint' => 20,
                'null'       => true,
            ],
            'kode_jenis' => [
                'type'       => 'VARCHAR',
                'constraint' => 2,
                'null'       => true,
            ],
            'satuan' => [
                'type'       => 'VARCHAR',
                'constraint' => 15,
                'null'       => true,
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

        // Create table first
        $this->forge->createTable('r_layanan_pengujian', true);

        // Tambah FK dengan mekanisme forge (lebih aman & portable)
        $this->forge->addForeignKey('kode_parameter', 'r_parameter', 'kode', 'CASCADE', 'CASCADE');
        $this->forge->addForeignKey('kode_alat', 'r_alat', 'kode', 'CASCADE', 'CASCADE');
        $this->forge->addForeignKey('kode_jenis', 'r_jenis', 'kode', 'CASCADE', 'CASCADE');

        // Apply alter table for foreign keys
        $this->forge->processIndexes('r_layanan_pengujian');
    }

    public function down()
    {
        $this->forge->dropTable('r_layanan_pengujian', true);
    }
}
