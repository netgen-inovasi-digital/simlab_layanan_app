<?php

namespace App\Database\Migrations;

use CodeIgniter\Database\Migration;

class CreateSimlabRLayananPengujian extends Migration
{
    public function up()
    {
        $this->forge->addField([
            'ujiKode' => [
                'type'           => 'INT',
                'unsigned'       => true,
                'auto_increment' => true,
            ],
            'ujiLayanan' => [
                'type'       => 'VARCHAR',
                'constraint' => 255,
                'null'       => true,
            ],
            'ujiAlatKode' => [
                'type'       => 'VARCHAR',
                'constraint' => 50,
                'null'       => true,
            ],
            'ujiParaKode' => [
                'type'       => 'VARCHAR',
                'constraint' => 20,
                'null'       => true,
            ],
            'ujiInstansi' => [
                'type'       => 'VARCHAR',
                'constraint' => 10,
                'null'       => true,
                'comment'    => 'ULM; NON ULM',
            ],
            'ujiJenKode' => [
                'type'       => 'VARCHAR',
                'constraint' => 20,
                'null'       => true,
            ],
            'ujiSatuan' => [
                'type'       => 'VARCHAR',
                'constraint' => 15,
                'null'       => true,
            ],
            'ujiBiaya' => [
                'type'       => 'DOUBLE',
                'null'       => true,
            ],
            'ujiBiaya1' => [
                'type'       => 'DOUBLE',
                'null'       => true,
            ],
            'ujiBiaya2' => [
                'type'       => 'DOUBLE',
                'null'       => true,
            ],
            'ujiBiaya3' => [
                'type'       => 'DOUBLE',
                'null'       => true,
            ],
            'ujiBiaya4' => [
                'type'       => 'DOUBLE',
                'null'       => true,
            ],
            'ujiBiaya5' => [
                'type'       => 'DOUBLE',
                'null'       => true,
            ],
        ]);

        $this->forge->addKey('ujiKode', true);
        $this->forge->createTable('simlab_r_layanan_pengujian', true);

        // Set auto increment sesuai SQL dump
        $this->db->query("ALTER TABLE `simlab_r_layanan_pengujian` AUTO_INCREMENT = 124;");
    }

    public function down()
    {
        $this->forge->dropTable('simlab_r_layanan_pengujian', true);
    }
}
