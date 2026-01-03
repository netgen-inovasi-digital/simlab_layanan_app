<?php

namespace App\Database\Migrations;

use CodeIgniter\Database\Migration;

class CreateTFilesLhu extends Migration
{
  public function up()
  {
    $this->forge->addField([
      'file_id' => [
        'type' => 'INT',
        'unsigned' => true,
        'auto_increment' => true,
      ],
      'kode' => [
        'type' => 'INT',
        'unsigned' => true, // <-- SAMAKAN dengan t_layanan.kode_layanan (unsigned)
        'null' => true,
      ],
      'file' => [
        'type' => 'VARCHAR',
        'constraint' => 255,
        'null' => true,
      ],
      'upload_by' => [
        'type' => 'INT',
        'unsigned' => true, // <-- SAMAKAN dengan simlab_account_users.user_id
        'null' => true,
      ],
      'tanggal_terbit' => [
        'type' => 'DATETIME',
        'null' => true,
      ],
    ]);

    $this->forge->addKey('file_id', true);
    $this->forge->addKey('kode');
    $this->forge->addKey('upload_by');

    // FK via Forge (lebih aman)
    $this->forge->addForeignKey('kode', 't_layanan', 'kode_layanan', 'CASCADE', 'CASCADE');
    $this->forge->addForeignKey('upload_by', 'simlab_account_users', 'user_id', 'RESTRICT', 'RESTRICT');

    $this->forge->createTable('t_files_lhu', true);
  }

  public function down()
  {
    $this->forge->dropTable('t_files_lhu', true);
  }
}
