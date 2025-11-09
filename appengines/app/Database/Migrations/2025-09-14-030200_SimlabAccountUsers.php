<?php

namespace App\Database\Migrations;

use CodeIgniter\Database\Migration;

class CreateSimlabAccountUsers extends Migration
{
    public function up()
    {
        $this->forge->addField([
            'user_id' => [
                'type' => 'INT',
                'unsigned' => true,
                'auto_increment' => true,
            ],
            'role_id' => [
                'type' => 'INT',
                'default' => 0,
            ],
            'user_name' => [
                'type' => 'VARCHAR',
                'constraint' => 200,
                'null' => false,
            ],
            'user_email' => [
                'type' => 'VARCHAR',
                'constraint' => 100,
                'null' => false,
            ],
            'user_password' => [
                'type' => 'VARCHAR',
                'constraint' => 200,
                'null' => false,
            ],
            'user_telpon' => [
                'type' => 'VARCHAR',
                'constraint' => 20,
                'null' => true,
            ],
            'user_instansi' => [
                'type' => 'VARCHAR',
                'constraint' => 200,
                'null' => true,
            ],
            'status_user' => [
                'type' => 'TINYINT',
                'constraint' => 1,
                'default' => 0,
            ],
            'user_identity' => [
                'type' => "ENUM('NON ULM','ULM')",
                'default' => 'NON ULM',
            ],
            'verifikasi' => [
                'type' => 'TINYINT',
                'constraint' => 1,
                'default' => 0,
            ],
            'bukti' => [
                'type' => 'VARCHAR',
                'constraint' => 255,
                'null' => true,
            ],
        ]);
        $this->forge->addKey('user_id', true);
        $this->forge->addKey('user_email', false, true); // unique

        $this->forge->createTable('simlab_account_users', true);
    }

    public function down()
    {
        $this->forge->dropTable('simlab_account_users', true);
    }
}
