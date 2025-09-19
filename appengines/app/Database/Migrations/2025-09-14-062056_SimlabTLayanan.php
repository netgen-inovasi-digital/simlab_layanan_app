<?php

namespace App\Database\Migrations;

use CodeIgniter\Database\Migration;

class CreateSimlabTLayanan extends Migration
{
    public function up()
    {
        $this->forge->addField([
            'lnKode' => [
                'type'           => 'INT',
                'constraint'     => 11,
                'unsigned'       => true,
                'auto_increment' => true
            ],
            'lnAccEmail' => [
                'type'       => 'VARCHAR',
                'constraint' => 100,
                'null'       => true
            ],
            'lnNoTransaksi' => [
                'type'       => 'VARCHAR',
                'constraint' => 25,
                'null'       => true
            ],
            'lnTgl' => [
                'type' => 'DATETIME',
                'null' => true
            ],
            'lnTipe' => [
                'type'       => 'VARCHAR',
                'constraint' => 15,
                'null'       => true
            ],
            'lnOrangNama' => [
                'type'       => 'VARCHAR',
                'constraint' => 255,
                'null'       => true
            ],
            'lnOrangIdentitas' => [
                'type'       => 'VARCHAR',
                'constraint' => 255,
                'null'       => true
            ],
            'lnOrangTelp' => [
                'type'       => 'VARCHAR',
                'constraint' => 50,
                'null'       => true
            ],
            'lnOrangEmail' => [
                'type'       => 'VARCHAR',
                'constraint' => 255,
                'null'       => true
            ],
            'lnInstansi' => [
                'type'       => 'VARCHAR',
                'constraint' => 255,
                'null'       => true
            ],
            'lnStatus' => [
                'type'       => 'TINYINT',
                'constraint' => 1,
                'default'    => 0,
                'comment'    => '0=draft;1=onreview;2=sudahreview;3=pelaksanaan;4=selesai'
            ],
            'lnNoUrut' => [
                'type'       => 'INT',
                'null'       => true
            ],
            'lnPosting' => [
                'type'       => 'INT',
                'null'       => true
            ]
        ]);

        $this->forge->addKey('lnKode', true);
        $this->forge->createTable('simlab_t_layanan');
    }

    public function down()
    {
        $this->forge->dropTable('simlab_t_layanan');
    }
}
