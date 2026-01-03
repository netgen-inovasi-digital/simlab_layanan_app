<?php

namespace App\Database\Migrations;

use CodeIgniter\Database\Migration;

class CreateSimlabRKolomKeuanganDetail extends Migration
{
    public function up()
    {
        $this->forge->addField([
            'kode' => [
                'type' => 'INT',
                'unsigned' => true,
                'auto_increment' => true,
            ],
            'jenis_kode' => [
                'type' => 'VARCHAR',
                'constraint' => 2,
                'null' => true,
            ],
            'label' => [
                'type' => 'VARCHAR',
                'constraint' => 50,
                'null' => true,
            ],
            'non_ulm' => [
                'type' => 'DOUBLE',
                'null' => true,
            ],
        ]);
        $this->forge->addKey('kode', true);
        $this->forge->createTable('r_kolom_keuangan_detail');
    }

    public function down()
    {
        $this->forge->dropTable('r_kolom_keuangan_detail');
    }
}