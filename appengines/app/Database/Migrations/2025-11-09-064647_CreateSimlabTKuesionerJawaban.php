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
        $this->forge->createTable('simlab_t_kuesioner_jawaban');

        // add fk id_pertanyaan -> simlab_t_kuesioner.kuesioner_id
        $db = \Config\Database::connect();
        $db->query('ALTER TABLE `simlab_t_kuesioner_jawaban`
            ADD CONSTRAINT `fk_kuesioner_jawaban_pertanyaan` FOREIGN KEY (`id_pertanyaan`) REFERENCES `simlab_t_kuesioner` (`kuesioner_id`) ON DELETE CASCADE ON UPDATE CASCADE;
        ');
    }

    public function down()
    {
        $this->forge->dropTable('simlab_t_kuesioner_jawaban', true);
    }
}
