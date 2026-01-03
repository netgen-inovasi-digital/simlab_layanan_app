<?php

namespace App\Database\Migrations;

use CodeIgniter\Database\Migration;

class CreateTFilesLabJas extends Migration
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
        'unsigned' => true, // <-- samakan dengan t_layanan_detil.kode
        'null' => true,
      ],
      'kode_layanan' => [
        'type' => 'INT',
        'unsigned' => true, // <-- relasi ke t_layanan.kode_layanan
        'null' => true,
      ],
      'file_lab_jas' => [
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
        'unsigned' => true, // <-- samakan dengan account_users.user_id
        'null' => true,
      ],
    ]);

    $this->forge->addKey('file_id', true);
    $this->forge->addKey('kode_detail_layanan');
    $this->forge->addKey('kode_layanan');
    $this->forge->addKey('kirim_by');

    // foreign keys using Forge
    $this->forge->addForeignKey('kode_detail_layanan', 't_layanan_detil', 'kode', 'CASCADE', 'CASCADE');
    $this->forge->addForeignKey('kode_layanan', 't_layanan', 'kode_layanan', 'CASCADE', 'CASCADE');
    $this->forge->addForeignKey('kirim_by', 'account_users', 'user_id', 'RESTRICT', 'RESTRICT');

    $this->forge->createTable('t_files_lab_jas', true);
  }

  public function down()
  {
    // If you want to be explicit, you can drop foreign keys first. Many setups allow dropping table directly.
    // Example (uncomment if needed and supported by DB engine / CodeIgniter version):
    // $this->forge->dropForeignKey('t_files_lab_jas', 't_files_lab_jas_kode_detail_layanan_foreign');
    // $this->forge->dropForeignKey('t_files_lab_jas', 't_files_lab_jas_kode_layanan_foreign');
    // $this->forge->dropForeignKey('t_files_lab_jas', 't_files_lab_jas_kirim_by_foreign');

    $this->forge->dropTable('t_files_lab_jas', true);
  }
}
