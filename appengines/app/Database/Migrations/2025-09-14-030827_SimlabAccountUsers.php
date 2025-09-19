<?php

namespace App\Database\Migrations;

use CodeIgniter\Database\Migration;

class CreateSimlabAccountUsers extends Migration
{
    public function up()
    {
        $this->forge->addField([
            'user_id' => [
                'type'           => 'INT',
                'unsigned'       => true,
                'auto_increment' => true,
            ],
            'role_id' => [
                'type'       => 'INT',
                'default'    => 0,
            ],
            'user_name' => [
                'type'       => 'VARCHAR',
                'constraint' => 200,
            ],
            'user_email' => [
                'type'       => 'VARCHAR',
                'constraint' => 100,
            ],
            'user_password' => [
                'type'       => 'VARCHAR',
                'constraint' => 200,
            ],
            'status_user' => [
                'type'       => 'TINYINT',
                'constraint' => 1,
                'default'    => 0,
            ],
            'user_identity' => [
                'type'       => 'ENUM',
                'constraint' => ['NON ULM', 'ULM'],
            ],
        ]);

        $this->forge->addKey('user_id', true); // Primary Key
        $this->forge->addUniqueKey('user_email'); // Unique
        $this->forge->createTable('simlab_account_users');
    }

    public function down()
    {
        $this->forge->dropTable('simlab_account_users');
    }
}
