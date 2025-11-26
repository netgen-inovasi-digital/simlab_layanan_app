<?php

namespace App\Database\Migrations;

use CodeIgniter\Database\Migration;

class CreateSimlabRKolomKeuanganDetail extends Migration
{
    public function up()
    {
        $this->forge->addField([
            'kdKode' => [
                'type' => 'INT',
                'unsigned' => true,
                'auto_increment' => true,
            ],
            'kdJenKode' => [
                'type' => 'VARCHAR',
                'constraint' => 2,
                'null' => true,
            ],
            'kdKolomLabel' => [
                'type' => 'VARCHAR',
                'constraint' => 50,
                'null' => true,
            ],
            'kdPersenNONULM' => [
                'type' => 'DOUBLE',
                'null' => true,
            ],
        ]);
        $this->forge->addKey('kdKode', true);
        $this->forge->createTable('simlab_r_kolom_keuangan_detail');
    }

    public function down()
    {
        $this->forge->dropTable('simlab_r_kolom_keuangan_detail');
    }
}
