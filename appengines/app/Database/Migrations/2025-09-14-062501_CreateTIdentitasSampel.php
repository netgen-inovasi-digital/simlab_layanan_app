<?php

namespace App\Database\Migrations;

use CodeIgniter\Database\Migration;

class CreateTIdentitasSampel extends Migration
{
    public function up()
    {
        $this->forge->addField([
            'id_sampel' => [
                'type' => 'INT',
                'unsigned' => false,
                'auto_increment' => true,
            ],
            'kode_layanan' => [
                'type' => 'INT',
                'unsigned' => true,
            ],
            'jenis' => [
                'type' => 'VARCHAR',
                'constraint' => 255,
                'null' => true,
                'default' => '',
            ],
            'kemasan' => [
                'type' => 'VARCHAR',
                'constraint' => 255,
                'null' => true,
                'default' => '',
            ],
            'sifat' => [
                'type' => 'ENUM',
                'constraint' => ['Korosif', 'Beracun', 'Mudah menguap', 'Higroskopis', 'Cair', 'Tidak mudah menguap', 'Padat kering', 'Cairan kental'],
                'default' => 'Cair',
            ],
            'sisa' => [
                'type' => 'ENUM',
                'constraint' => ['Tidak diambil', 'Diambil'],
                'default' => 'Tidak diambil',
            ],
            'deskripsi' => [
                'type' => 'TEXT',
                'null' => true,
            ],
            'keterangan_khusus' => [
                'type' => 'TEXT',
                'null' => true,
            ],
        ]);

        $this->forge->addKey('id_sampel', true);
        $this->forge->addKey('kode_layanan');

        // Foreign key
        $this->forge->addForeignKey('kode_layanan', 'simlab_t_layanan', 'lnKode', 'CASCADE', 'CASCADE');

        $this->forge->createTable('t_identitas_sampel', true);
    }

    public function down()
    {
        $this->forge->dropTable('t_identitas_sampel', true);
    }
}
