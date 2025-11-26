<?php

namespace App\Database\Migrations;

use CodeIgniter\Database\Migration;

class CreateSimlabRParameter extends Migration
{
    public function up()
    {
        $this->forge->addField([
            'paraKode' => [
                'type'       => 'VARCHAR',
                'constraint' => 20,
            ],
            'paraNama' => [
                'type'       => 'VARCHAR',
                'constraint' => 255,
                'null'       => true,
            ],
        ]);

        $this->forge->addKey('paraKode', true); // Primary key
        $this->forge->createTable('simlab_r_parameter', true);
    }

    public function down()
    {
        $this->forge->dropTable('simlab_r_parameter', true);
    }
}
