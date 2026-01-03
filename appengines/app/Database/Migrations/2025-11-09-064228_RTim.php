<?php

namespace App\Database\Migrations;

use CodeIgniter\Database\Migration;

class CreateRTim extends Migration
{
    public function up()
    {
        $this->forge->addField([
            'id' => [
                'type'           => 'INT',
                'unsigned'       => true,
                'auto_increment' => true,
            ],
            'uji_kode' => [
                'type'     => 'INT',
                'unsigned' => true,
                'null'     => true,
            ],
            'user_id' => [
                'type'     => 'INT',
                'unsigned' => true,
                'null'     => true,
            ],
        ]);

        $this->forge->addKey('id', true);
        $this->forge->addKey('uji_kode');
        $this->forge->addKey('user_id');

        // Tambahkan foreign key langsung
        $this->forge->addForeignKey('uji_kode', 'r_layanan_pengujian', 'kode', 'CASCADE', 'CASCADE');
        $this->forge->addForeignKey('user_id', 'account', 'user_id', 'CASCADE', 'CASCADE');

        $this->forge->createTable('r_tim');
    }

    public function down()
    {
        $this->forge->dropTable('r_tim', true);
    }
}
