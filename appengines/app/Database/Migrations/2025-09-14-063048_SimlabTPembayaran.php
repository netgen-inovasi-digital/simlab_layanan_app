<?php

namespace App\Database\Migrations;

use CodeIgniter\Database\Migration;

class CreateTPembayaran extends Migration
{
  public function up()
  {
    $this->forge->addField([
      'kode_bayar' => [
        'type' => 'INT',
        'unsigned' => true,
        'auto_increment' => true,
      ],
      'kode_layanan' => [
        'type' => 'INT',
        'unsigned' => true, // <-- pastikan unsigned sesuai t_layanan.kode_layanan
        'null' => true,
      ],
      'total_biaya' => [
        'type' => 'DOUBLE',
        'null' => true,
      ],
      'invoice_file' => [
        'type' => 'VARCHAR',
        'constraint' => 255,
        'null' => true,
      ],
      'status_bayar' => [
        'type' => 'INT',
        'default' => 0,
      ],
      'bukti_bayar' => [
        'type' => 'VARCHAR',
        'constraint' => 255,
        'null' => true,
      ],
      'no_invoice' => [
        'type' => 'VARCHAR',
        'constraint' => 50,
        'null' => true,
      ],
      'tanggal_invoice' => [
        'type' => 'DATE',
        'null' => true,
      ],
      'catatan_pembayaran' => [
        'type' => 'VARCHAR',
        'constraint' => 255,
        'null' => true,
      ],
    ]);

    $this->forge->addKey('kode_bayar', true);
    $this->forge->addKey('kode_layanan');

    // foreign key using Forge (safer)
    $this->forge->addForeignKey('kode_layanan', 't_layanan', 'kode_layanan', 'RESTRICT', 'RESTRICT');

    $this->forge->createTable('t_pembayaran', true);
  }

  public function down()
  {
    $this->forge->dropTable('t_pembayaran', true);
  }
}
