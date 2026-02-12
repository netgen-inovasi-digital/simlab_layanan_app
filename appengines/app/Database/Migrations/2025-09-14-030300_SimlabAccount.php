<?php

namespace App\Database\Migrations;

use CodeIgniter\Database\Migration;

class CreateSimlabAccount extends Migration
{
    public function up()
    {
        $this->forge->addField([
            'user_id' => [
                'type'           => 'INT',
                'constraint'     => 11,
                'unsigned'       => true,
                'auto_increment' => true,
            ],
            'username' => [
                'type'       => 'VARCHAR',
                'constraint' => 50,
                'null'       => false,
            ],
            'nama' => [
                'type'       => 'VARCHAR',
                'constraint' => 100,
                'null'       => true,
                'default'    => null,
            ],
            'role_id' => [
                'type'       => 'INT',
                'constraint' => 11,
                'unsigned'   => true,
                'null'       => false,
            ],
            'password' => [
                'type'       => 'VARCHAR',
                'constraint' => 100,
                'null'       => false,
            ],
            'status_user' => [
                'type'       => 'TINYINT',
                'constraint' => 1,
                'null'       => false,
                'default'    => 1,
            ],
            'Telepon' => [
                'type'       => 'VARCHAR',
                'constraint' => 255,
                'null'       => true,
                'default'    => null,
            ],
            'email' => [
                'type'       => 'VARCHAR',
                'constraint' => 255,
                'null'       => true,
                'default'    => null,
            ],
        ]);

        // primary key
        $this->forge->addKey('user_id', true);

        // unique key username (like uk_username)
        $this->forge->addKey('username', false, true);

        // index for role_id
        $this->forge->addKey('role_id');

        // create table with attributes to match dump
        $attributes = [
            'ENGINE'  => 'InnoDB',
            'CHARSET' => 'utf8mb4',
            'COLLATE' => 'utf8mb4_unicode_ci',
        ];
        $this->forge->createTable('account', true, $attributes);

        // set AUTO_INCREMENT starting value to 43 (as in dump)
        try {
            $db = \Config\Database::connect();
            $db->query("ALTER TABLE `
            
            account` AUTO_INCREMENT = 43");
        } catch (\Throwable $e) {
            // ignore if not permitted
        }

        // add foreign key role_id -> roles(id_role) using ALTER TABLE (wrapped in try/catch)
        try {
            $db = \Config\Database::connect();
            if ($db->tableExists('roles')) {
                $db->query("ALTER TABLE `account`
                    ADD CONSTRAINT `fk_account_role` FOREIGN KEY (`role_id`) REFERENCES `roles`(`id_role`)
                    ON DELETE NO ACTION ON UPDATE NO ACTION");
            }
        } catch (\Throwable $e) {
            // ignore FK addition errors to avoid migration failure if roles missing
        }
    }

    public function down()
    {
        // drop table (FK will be removed automatically)
        $this->forge->dropTable('account', true);
    }
}
