<?php

namespace App\Database\Migrations;

use CodeIgniter\Database\Migration;

class CreateSimlabTKuesionerJawaban extends Migration
{
    public function up()
    {
        $this->forge->addField([
            'id_jawaban' => [
                'type' => 'INT',
                'unsigned' => true,
                'auto_increment' => true,
            ],
            'id_pertanyaan' => [
                'type' => 'INT',
                'unsigned' => true,
            ],
            'user_id' => [
                'type' => 'INT',
                'unsigned' => true,
            ],
            'jawaban' => [
                'type' => 'TEXT',
                'null' => true,
            ],
            'created_at' => [
                'type' => 'DATETIME',
                'null' => true,
            ],
        ]);
        $this->forge->addKey('id_jawaban', true);
        $this->forge->addKey('id_pertanyaan');
        $this->forge->createTable('t_kuisioner_jawaban');

        // add fk id_pertanyaan -> t_kuisioner.kuesioner_id
        $db = \Config\Database::connect();
        $db->query('ALTER TABLE `t_kuisioner_jawaban`
            ADD CONSTRAINT `fk_kuesioner_jawaban_pertanyaan` FOREIGN KEY (`id_pertanyaan`) REFERENCES `t_kuisioner` (`kuesioner_id`) ON DELETE CASCADE ON UPDATE CASCADE;
        ');
    }

    public function down()
    {
        $this->forge->dropTable('t_kuisioner_jawaban', true);
    }
}
