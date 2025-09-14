<?php

namespace App\Database\Migrations;

use CodeIgniter\Database\Migration;

class CreateSimlabAccount extends Migration
{
    public function up()
    {
        $this->forge->addField([
            'username' => [
                'type'       => 'VARCHAR',
                'constraint' => 50,
            ],
            'role_id' => [
                'type'       => 'INT',
                'constraint' => 11,
                'null'       => false,
            ],
            'password' => [
                'type'       => 'VARCHAR',
                'constraint' => 100,
            ],
            'lab_kode' => [
                'type'       => 'VARCHAR',
                'constraint' => 15,
                'null'       => true,
            ],
            'status_user' => [
                'type'       => 'TINYINT',
                'constraint' => 1,
                'default'    => 1,
            ],
        ]);

        // Primary Key
        $this->forge->addKey('username', true);

        // Foreign Key (role_id → roles.id_role)
        $this->forge->addForeignKey('role_id', 'roles', 'id_role', 'CASCADE', 'CASCADE');

        // Buat tabel
        $this->forge->createTable('simlab_account');
    }

    public function down()
    {
        $this->forge->dropTable('simlab_account');
    }
}
