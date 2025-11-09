<?php

namespace App\Database\Migrations;

use CodeIgniter\Database\Migration;

class CreateRTim extends Migration
{
    public function up()
    {
        $this->forge->addField([
            'id' => [
                'type' => 'INT',
                'unsigned' => true,
                'auto_increment' => true,
            ],
            'uji_kode' => [
                'type' => 'INT',
                'null' => true,
            ],
            'user_id' => [
                'type' => 'INT',
                'null' => true,
            ],
        ]);
        $this->forge->addKey('id', true);
        $this->forge->createTable('r_tim');
    }

    public function down()
    {
        $this->forge->dropTable('r_tim');
    }
}
