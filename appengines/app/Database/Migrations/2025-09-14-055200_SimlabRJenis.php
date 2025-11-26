<?php

namespace App\Database\Migrations;

use CodeIgniter\Database\Migration;

class CreateSimlabRJenis extends Migration
{
    public function up()
    {
        $this->forge->addField([
            'jenKode' => [
                'type' => 'VARCHAR',
                'constraint' => 2,
            ],
            'jenNama' => [
                'type' => 'VARCHAR',
                'constraint' => 100,
                'null' => true,
            ],
        ]);

        $this->forge->addKey('jenKode', true);
        $this->forge->createTable('simlab_r_jenis', true);
    }

    public function down()
    {
        $this->forge->dropTable('simlab_r_jenis', true);
    }
}
