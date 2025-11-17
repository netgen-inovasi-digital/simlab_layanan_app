<?php

namespace App\Database\Migrations;

use CodeIgniter\Database\Migration;

class CreateRMetode extends Migration
{
    public function up()
    {
        $this->forge->addField([
            'metode_kode' => [
                'type' => 'INT',
                'unsigned' => true,
                'auto_increment' => true,
            ],
            'nama' => [
                'type' => 'VARCHAR',
                'constraint' => 100,
                'null' => true,
            ],
        ]);

        $this->forge->addKey('metode_kode', true);
        $this->forge->createTable('r_metode', true);
    }

    public function down()
    {
        $this->forge->dropTable('r_metode', true);
    }
}
