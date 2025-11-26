<?php

namespace App\Database\Migrations;

use CodeIgniter\Database\Migration;

class CreateSimlabRAlat extends Migration
{
    public function up()
    {
        $this->forge->addField([
            'alatKode' => [
                'type' => 'VARCHAR',
                'constraint' => 50,
            ],
            'alatNama' => [
                'type' => 'VARCHAR',
                'constraint' => 255,
                'null' => true,
            ],
        ]);

        $this->forge->addKey('alatKode', true); // primary key
        $this->forge->createTable('simlab_r_alat', true);
    }

    public function down()
    {
        $this->forge->dropTable('simlab_r_alat', true);
    }
}
