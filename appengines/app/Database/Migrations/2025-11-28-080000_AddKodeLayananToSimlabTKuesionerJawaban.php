<?php

namespace App\Database\Migrations;

use CodeIgniter\Database\Migration;

class AddKodeLayananToSimlabTKuesionerJawaban extends Migration
{
    public function up()
    {
        $fieldExists = false;
        try {
            $fieldExists = $this->db->query("SHOW COLUMNS FROM `simlab_t_kuesioner_jawaban` LIKE 'kode_layanan'")->getNumRows() > 0;
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
            $this->forge->addColumn('simlab_t_kuesioner_jawaban', $fields);

            // Tambahkan index agar pencarian lebih cepat
            $this->db->query('ALTER TABLE `simlab_t_kuesioner_jawaban` ADD INDEX `idx_kode_layanan` (`kode_layanan`)');

            // Tambahkan foreign key ke simlab_t_layanan.lnKode
            $this->db->query('ALTER TABLE `simlab_t_kuesioner_jawaban`
                ADD CONSTRAINT `fk_kuesioner_jawaban_layanan`
                FOREIGN KEY (`kode_layanan`) REFERENCES `simlab_t_layanan` (`lnKode`)
                ON DELETE CASCADE ON UPDATE CASCADE');
        }
    }

    public function down()
    {
        $fieldExists = false;
        try {
            $fieldExists = $this->db->query("SHOW COLUMNS FROM `simlab_t_kuesioner_jawaban` LIKE 'kode_layanan'")->getNumRows() > 0;
        } catch (\Throwable $e) {
            $fieldExists = false;
        }

        if ($fieldExists) {
            try {
                $this->db->query('ALTER TABLE `simlab_t_kuesioner_jawaban` DROP FOREIGN KEY `fk_kuesioner_jawaban_layanan`');
            } catch (\Throwable $e) {
                // ignore if FK already removed
            }

            try {
                $this->db->query('ALTER TABLE `simlab_t_kuesioner_jawaban` DROP INDEX `idx_kode_layanan`');
            } catch (\Throwable $e) {
                // ignore if index already removed
            }

            $this->forge->dropColumn('simlab_t_kuesioner_jawaban', 'kode_layanan');
        }
    }
}
