<?php

namespace App\Database\Migrations;

use CodeIgniter\Database\Migration;

class CreateTLayananDetil extends Migration
{
    public function up()
    {
        $this->forge->addField([
            'kode' => [
                'type' => 'INT',
                'unsigned' => true,
                'auto_increment' => true,
            ],
            'uji_kode' => [
                'type' => 'INT',
                'unsigned' => true,
                'null' => true,
            ],
            'biaya' => [
                'type' => 'DOUBLE',
                'null' => true,
            ],
            'jumlah' => [
                'type' => 'INT',
                'default' => 1,
            ],
            'status_layanan' => [
                'type' => 'INT',
                'default' => 0,
            ],
            'kode_layanan' => [
                'type' => 'INT',
                'unsigned' => true,
                'null' => true,
            ],
            'kode_jenis' => [
                'type' => 'VARCHAR',
                'constraint' => 2,
                'null' => true,
            ],
            'nama_layanan' => [
                'type' => 'TEXT',
                'null' => true,
            ],
            'catatan_pelanggan' => [
                'type' => 'VARCHAR',
                'constraint' => 255,
                'null' => true,
            ],
            'catatan_manajar' => [
                'type' => 'VARCHAR',
                'constraint' => 255,
                'null' => true,
            ],
            'terima_layanan_by' => [
                'type' => 'INT',
                'unsigned' => true,
                'null' => true,
            ],
            'files' => [
                'type' => 'INT',
                'null' => true,
            ],
        ]);

        $this->forge->addKey('kode', true);
        $this->forge->addKey('uji_kode');
        $this->forge->addKey('kode_layanan');
        $this->forge->addKey('kode_jenis');
        $this->forge->addKey('terima_layanan_by');

        // FK via Forge
        $this->forge->addForeignKey('terima_layanan_by', 'simlab_account', 'user_id', 'RESTRICT', 'RESTRICT');
        $this->forge->addForeignKey('kode_jenis', 'simlab_r_jenis', 'jenKode', 'RESTRICT', 'RESTRICT');
        $this->forge->addForeignKey('kode_layanan', 'simlab_t_layanan', 'lnKode', 'RESTRICT', 'RESTRICT');
        $this->forge->addForeignKey('uji_kode', 'r_layanan_pengujian', 'kode', 'SET NULL', 'SET NULL');

        $this->forge->createTable('t_layanan_detil', true);
    }

    public function down()
    {
        $this->forge->dropTable('t_layanan_detil', true);
    }
}
