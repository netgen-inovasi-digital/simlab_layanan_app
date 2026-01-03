<?php

namespace App\Database\Migrations;

use CodeIgniter\Database\Migration;

class CreateTFilesLhus extends Migration
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
        'unsigned' => true, // refer ke t_layanan_detil.kode (unsigned)
        'null' => true,
      ],
      'kode_layanan' => [
        'type' => 'INT',
        'unsigned' => true, // refer ke t_layanan.kode_layanan
        'null' => true,
        'comment' => 'Relasi ke t_layanan.kode_layanan untuk memudahkan pengecekan kelengkapan upload per invoice',
      ],
      'file_lhus' => [
        'type' => 'VARCHAR',
        'constraint' => 255,
        'null' => true,
      ],
      'validasi_by' => [
        'type' => 'INT',
        'unsigned' => true, // refer ke simlab_account_users.user_id
        'null' => true,
      ],
      'upload_by' => [
        'type' => 'INT',
        'unsigned' => true, // refer ke simlab_account_users.user_id
        'null' => true,
      ],
      'catatan' => [
        'type' => 'VARCHAR',
        'constraint' => 255,
        'null' => true,
      ],
      'status' => [
        'type' => 'INT',
        'null' => true,
        'comment' => '0=pending, 1=accepted, 2=rejected, 3=uploaded (belum dikirim)',
      ],
    ]);

    $this->forge->addKey('file_id', true);
    $this->forge->addKey('kode');
    $this->forge->addKey('kode_layanan');
    $this->forge->addKey('validasi_by');
    $this->forge->addKey('upload_by');

    // FK via Forge
    $this->forge->addForeignKey('kode', 't_layanan_detil', 'kode', 'CASCADE', 'CASCADE');
    $this->forge->addForeignKey('kode_layanan', 't_layanan', 'kode_layanan', 'CASCADE', 'CASCADE');
    $this->forge->addForeignKey('upload_by', 'simlab_account', 'user_id', 'RESTRICT', 'RESTRICT');
    $this->forge->addForeignKey('validasi_by', 'simlab_account', 'user_id', 'RESTRICT', 'RESTRICT');

    $this->forge->createTable('t_files_lhus', true);
  }

  public function down()
  {
    $this->forge->dropTable('t_files_lhus', true);
  }
}
