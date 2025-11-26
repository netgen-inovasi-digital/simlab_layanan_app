<?php

namespace App\Database\Migrations;

use CodeIgniter\Database\Migration;

class AlterTablePengumuman extends Migration
{
    public function up()
    {
        $fields = [

            'file' => [
                'type' => 'VARCHAR',
                'constraint' => 255,
                'null' => true,
                'after' => 'judul',
            ],
        ];

        $this->forge->addColumn('pengumuman', $fields);
    }

    public function down()
    {
        $this->forge->dropColumn('pengumuman',  'file');
    }
}