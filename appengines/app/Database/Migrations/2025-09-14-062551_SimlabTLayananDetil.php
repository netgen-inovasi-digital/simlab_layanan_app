<?php

namespace App\Database\Migrations;

use CodeIgniter\Database\Migration;

class CreateSimlabTLayananDetil extends Migration
{
    public function up()
    {
        $this->forge->addField([
            'detKode' => [
                'type'           => 'INT',
                'constraint'     => 11,
                'unsigned'       => true,
                'auto_increment' => true
            ],
            'detUjiKode' => [
                'type'       => 'INT',
                'constraint' => 11,
                'null'       => true
            ],
            'detBiaya' => [
                'type'       => 'DOUBLE',
                'null'       => true
            ],
            'detKeterangan' => [
                'type'       => 'VARCHAR',
                'constraint' => 255,
                'null'       => true
            ],
            'detStatus' => [
                'type'       => 'INT',
                'constraint' => 11,
                'null'       => true
            ],
            'detLnKode' => [
                'type'       => 'INT',
                'constraint' => 11,
                'null'       => true
            ],
            'detFileHasil' => [
                'type'       => 'VARCHAR',
                'constraint' => 255,
                'null'       => true
            ],
            'detJenKode' => [
                'type'       => 'VARCHAR',
                'constraint' => 2,
                'null'       => true
            ],
            'detLayanan' => [
                'type'       => 'TEXT',
                'null'       => true
            ]
        ]);

        $this->forge->addKey('detKode', true);
        $this->forge->createTable('simlab_t_layanan_detil');
    }

    public function down()
    {
        $this->forge->dropTable('simlab_t_layanan_detil');
    }
}
