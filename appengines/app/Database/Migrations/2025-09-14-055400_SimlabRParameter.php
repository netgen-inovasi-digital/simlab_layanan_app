<?php

namespace App\Database\Migrations;

use CodeIgniter\Database\Migration;

class CreateSimlabRParameter extends Migration
{
    public function up()
    {
        $this->forge->addField([
            'kode' => [
                'type'       => 'VARCHAR',
                'constraint' => 20,
            ],
            'nama' => [
                'type'       => 'VARCHAR',
                'constraint' => 255,
                'null'       => true,
            ],
        ]);

        $this->forge->addKey('kode', true); // Primary key
        $this->forge->createTable('r_parameter', true);
    }

    public function down()
    {
        $this->forge->dropTable('r_parameter', true);
    }
}
