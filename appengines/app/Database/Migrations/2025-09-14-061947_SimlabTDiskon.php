<?php

namespace App\Database\Migrations;

use CodeIgniter\Database\Migration;

class CreateSimlabTDiskon extends Migration
{
    public function up()
    {
        $this->forge->addField([
            'kolom' => [
                'type'       => 'VARCHAR',
                'constraint' => 5,
            ],
            'diskon' => [
                'type'       => 'DOUBLE',
                'null'       => true,
            ],
        ]);

        // Primary key
        $this->forge->addKey('kolom', true);

        $this->forge->createTable('simlab_t_diskon', true);
    }

    public function down()
    {
        $this->forge->dropTable('simlab_t_diskon', true);
    }
}
