<?php

namespace App\Database\Migrations;

use CodeIgniter\Database\Migration;

class CreateSimlabRAlat extends Migration
{
    public function up()
    {
        $this->forge->addField([
            'kode' => [
                'type' => 'VARCHAR',
                'constraint' => 50,
            ],
            'nama' => [
                'type' => 'VARCHAR',
                'constraint' => 255,
                'null' => true,
            ],
        ]);

        $this->forge->addKey('kode', true); // primary key
        $this->forge->createTable('r_alat', true);
    }

    public function down()
    {
        $this->forge->dropTable('r_alat', true);
    }
}
