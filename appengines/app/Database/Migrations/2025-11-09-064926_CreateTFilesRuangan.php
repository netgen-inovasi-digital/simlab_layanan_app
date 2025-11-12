<?php

namespace App\Database\Migrations;

use CodeIgniter\Database\Migration;

class CreateTFilesRuangan extends Migration
{
    public function up()
    {
        $this->forge->addField([
            'file_id' => [
                'type' => 'INT',
                'unsigned' => true,
                'auto_increment' => true,
            ],
            'kode_detail_layanan' => [
                'type' => 'INT',
                'unsigned' => true, // <-- penting: samakan dengan t_layanan_detil.kode (unsigned)
                'null' => true,
            ],
            'file_ruangan' => [
                'type' => 'VARCHAR',
                'constraint' => 255,
                'null' => true,
            ],
            'status_file' => [
                'type' => 'INT',
                'default' => 0,
            ],
            'kirim_by' => [
                'type' => 'INT',
                'unsigned' => true, // <-- samakan dengan simlab_account_users.user_id
                'null' => true,
            ],
            'file_pendukung' => [
                'type' => 'VARCHAR',
                'constraint' => 255,
                'null' => true,
            ],
        ]);

        $this->forge->addKey('file_id', true);
        $this->forge->addKey('kode_detail_layanan');
        $this->forge->addKey('kirim_by');

        // foreign keys using Forge (safer)
        $this->forge->addForeignKey('kode_detail_layanan', 't_layanan_detil', 'kode', 'CASCADE', 'CASCADE');
        $this->forge->addForeignKey('kirim_by', 'simlab_account_users', 'user_id', 'RESTRICT', 'RESTRICT');

        $this->forge->createTable('t_files_ruangan', true);
    }

    public function down()
    {
        $this->forge->dropTable('t_files_ruangan', true);
    }
}
