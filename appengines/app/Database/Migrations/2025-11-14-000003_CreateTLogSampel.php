<?php

namespace App\Database\Migrations;

use CodeIgniter\Database\Migration;

class CreateTLogSampel extends Migration
{
    public function up()
    {
        $this->forge->addField([
            'log_id' => [
                'type' => 'INT',
                'unsigned' => false,
                'auto_increment' => true,
            ],
            'kode_layanan' => [
                'type' => 'INT',
                'unsigned' => true,
            ],
            'pengecekan' => [
                'type' => 'DATETIME',
                'null' => true,
            ],
            'pengujian' => [
                'type' => 'DATETIME',
                'null' => true,
            ],
            'verifikasi_hasil_uji' => [
                'type' => 'DATETIME',
                'null' => true,
            ],
            'penerbitan_lhus' => [
                'type' => 'DATETIME',
                'null' => true,
            ],
            'verifikasi_lhu' => [
                'type' => 'DATETIME',
                'null' => true,
            ],
            'penerbitan_lhu' => [
                'type' => 'DATETIME',
                'null' => true,
            ],
        ]);

        $this->forge->addKey('log_id', true);
        $this->forge->addKey('kode_layanan');

        // Foreign key
        $this->forge->addForeignKey('kode_layanan', 'simlab_t_layanan', 'lnKode', 'CASCADE', 'CASCADE');

        $this->forge->createTable('t_log_sampel', true);
    }

    public function down()
    {
        $this->forge->dropTable('t_log_sampel', true);
    }
}
