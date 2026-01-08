<?php

namespace App\Database\Migrations;

use CodeIgniter\Database\Migration;

class AlterTLayananDetilAddStatusLunas extends Migration
{
  public function up()
  {
    $fields = [
      'status_lunas' => [
        'type' => 'INT',
        'unsigned' => true,
        'null' => true,
        'after' => 'files',
      ],
    ];

    $this->forge->addColumn('t_layanan_detil', $fields);

    // Add foreign key constraint
    $this->forge->addForeignKey(
      'status_lunas',
      't_pembayaran',
      'kode_bayar',
      'SET NULL',
      'SET NULL',
      't_layanan_detil_status_lunas_foreign'
    );

    $this->db->query('ALTER TABLE t_layanan_detil ADD CONSTRAINT t_layanan_detil_status_lunas_foreign 
      FOREIGN KEY (status_lunas) REFERENCES t_pembayaran(kode_bayar) ON DELETE SET NULL ON UPDATE SET NULL');
  }

  public function down()
  {
    // Drop foreign key first
    $this->forge->dropForeignKey('t_layanan_detil', 't_layanan_detil_status_lunas_foreign');

    // Drop column
    $this->forge->dropColumn('t_layanan_detil', 'status_lunas');
  }
}
