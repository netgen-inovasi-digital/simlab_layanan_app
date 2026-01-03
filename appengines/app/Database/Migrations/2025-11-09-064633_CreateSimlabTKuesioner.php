<?php

namespace App\Database\Migrations;

use CodeIgniter\Database\Migration;

class CreateSimlabTKuesioner extends Migration
{
    public function up()
    {
        $this->forge->addField([
            'kuesioner_id' => [
                'type' => 'INT',
                'unsigned' => true,
                'auto_increment' => true,
            ],
            'pertanyaan_teks' => [
                'type' => 'TEXT',
                'null' => false,
            ],
            'pertanyaan_tipe' => [
                'type' => "ENUM('isian','pilihan','rating')",
                'default' => 'isian',
            ],
            'pertanyaan_opsi' => [
                'type' => 'TEXT',
                'null' => true,
                'comment' => 'Simpan opsi pilihan (pisah dgn newline)',
            ],
            'pertanyaan_wajib' => [
                'type' => 'TINYINT',
                'constraint' => 1,
                'default' => 1,
            ],
        ]);
        $this->forge->addKey('kuesioner_id', true);
        $this->forge->createTable('t_kuisioner');
    }

    public function down()
    {
        $this->forge->dropTable('t_kuisioner');
    }
}
