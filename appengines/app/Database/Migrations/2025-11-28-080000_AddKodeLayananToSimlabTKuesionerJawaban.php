<?php

namespace App\Database\Migrations;

use CodeIgniter\Database\Migration;

class AddKodeLayananToSimlabTKuesionerJawaban extends Migration
{
  public function up()
  {
    $fieldExists = false;
    try {
      $fieldExists = $this->db->query("SHOW COLUMNS FROM `t_kuisioner_jawaban` LIKE 'kode_layanan'")->getNumRows() > 0;
    } catch (\Throwable $e) {
      $fieldExists = false;
    }
    if (!$fieldExists) {
      $fields = [
        'kode_layanan' => [
          'type' => 'INT',
          'unsigned' => true,
          'null' => true,
          'after' => 'user_id',
        ],
      ];
      $this->forge->addColumn('t_kuisioner_jawaban', $fields);

      // Tambahkan index agar pencarian lebih cepat
      $this->db->query('ALTER TABLE `t_kuisioner_jawaban` ADD INDEX `idx_kode_layanan` (`kode_layanan`)');

      // Tambahkan foreign key ke t_layanan.kode_layanan
      $this->db->query('ALTER TABLE `t_kuisioner_jawaban`
                ADD CONSTRAINT `fk_kuesioner_jawaban_layanan`
                FOREIGN KEY (`kode_layanan`) REFERENCES `t_layanan` (`kode_layanan`)
                ON DELETE CASCADE ON UPDATE CASCADE');
    }
  }

  public function down()
  {
    $fieldExists = false;
    try {
      $fieldExists = $this->db->query("SHOW COLUMNS FROM `t_kuisioner_jawaban` LIKE 'kode_layanan'")->getNumRows() > 0;
    } catch (\Throwable $e) {
      $fieldExists = false;
    }

    if ($fieldExists) {
      try {
        $this->db->query('ALTER TABLE `t_kuisioner_jawaban` DROP FOREIGN KEY `fk_kuesioner_jawaban_layanan`');
      } catch (\Throwable $e) {
        // ignore if FK already removed
      }

      try {
        $this->db->query('ALTER TABLE `t_kuisioner_jawaban` DROP INDEX `idx_kode_layanan`');
      } catch (\Throwable $e) {
        // ignore if index already removed
      }

      $this->forge->dropColumn('t_kuisioner_jawaban', 'kode_layanan');
    }
  }
}
