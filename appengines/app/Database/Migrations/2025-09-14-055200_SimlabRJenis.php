<?php

namespace App\Database\Migrations;

use CodeIgniter\Database\Migration;

class CreateSimlabRJenis extends Migration
{
    public function up()
    {
        $this->forge->addField([
            'kode' => [
                'type' => 'VARCHAR',
                'constraint' => 2,
            ],
            'nama' => [
                'type' => 'VARCHAR',
                'constraint' => 100,
                'null' => true,
            ],
        ]);

        $this->forge->addKey('kode', true);
        $this->forge->createTable('r_jenis', true);
    }

    public function down()
    {
        $this->forge->dropTable('r_jenis', true);
    }
}
