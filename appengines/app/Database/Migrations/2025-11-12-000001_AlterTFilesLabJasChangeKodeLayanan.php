<?php

namespace App\Database\Migrations;

use CodeIgniter\Database\Migration;

class AlterTFilesLabJasChangeKodeLayanan extends Migration
{
    public function up()
    {
        // 🔥 Karena tabel sudah punya kolom kode_layanan dan kode_detail_layanan
        // Kita hanya perlu:
        // 1. Drop FK kode_detail_layanan (tidak dipakai lagi)
        // 2. Drop kolom kode_detail_layanan
        // 3. Ubah kode_layanan menjadi NOT NULL (karena ini yang akan dipakai)
        
        try {
            // Drop foreign key kode_detail_layanan jika ada
            if ($this->db->DBDriver !== 'SQLite3') {
                $this->db->query('ALTER TABLE `t_files_lab_jas` DROP FOREIGN KEY `t_files_lab_jas_kode_detail_layanan_foreign`');
            }
        } catch (\Exception $e) {
            log_message('warning', 'FK kode_detail_layanan mungkin tidak ada: ' . $e->getMessage());
        }

        try {
            // Drop index kode_detail_layanan jika ada
            if ($this->db->DBDriver !== 'SQLite3') {
                $this->db->query('ALTER TABLE `t_files_lab_jas` DROP INDEX `kode_detail_layanan`');
            }
        } catch (\Exception $e) {
            log_message('warning', 'Index kode_detail_layanan mungkin tidak ada: ' . $e->getMessage());
        }

        // Drop kolom kode_detail_layanan (tidak dipakai lagi)
        $this->forge->dropColumn('t_files_lab_jas', 'kode_detail_layanan');

        // Ubah kolom kode_layanan menjadi NOT NULL (sebelumnya NULL)
        // Pastikan tidak ada data NULL dulu
        $this->db->query('UPDATE `t_files_lab_jas` SET `kode_layanan` = 0 WHERE `kode_layanan` IS NULL');
        
        $this->forge->modifyColumn('t_files_lab_jas', [
            'kode_layanan' => [
                'type' => 'INT',
                'unsigned' => true,
                'null' => false, // ← Ubah jadi NOT NULL
            ],
        ]);

        // FK kode_layanan sudah ada dari migration awal, jadi tidak perlu tambahkan lagi
        // Tapi kita pastikan dengan query manual jika belum ada
        try {
            if ($this->db->DBDriver !== 'SQLite3') {
                // Cek apakah FK sudah ada
                $query = $this->db->query("
                    SELECT CONSTRAINT_NAME 
                    FROM INFORMATION_SCHEMA.KEY_COLUMN_USAGE 
                    WHERE TABLE_NAME = 't_files_lab_jas' 
                    AND CONSTRAINT_NAME = 't_files_lab_jas_kode_layanan_foreign'
                ");
                
                if ($query->getNumRows() == 0) {
                    // FK belum ada, tambahkan
                    $this->db->query('
                        ALTER TABLE `t_files_lab_jas` 
                        ADD CONSTRAINT `t_files_lab_jas_kode_layanan_foreign` 
                        FOREIGN KEY (`kode_layanan`) 
                        REFERENCES `simlab_t_layanan` (`lnKode`) 
                        ON DELETE CASCADE 
                        ON UPDATE CASCADE
                    ');
                }
            }
        } catch (\Exception $e) {
            log_message('error', 'Gagal menambahkan/cek FK kode_layanan: ' . $e->getMessage());
        }
    }

    public function down()
    {
        // Kembalikan kolom kode_detail_layanan
        $this->forge->addColumn('t_files_lab_jas', [
            'kode_detail_layanan' => [
                'type' => 'INT',
                'unsigned' => true,
                'null' => true,
                'after' => 'file_id',
            ],
        ]);

        // Kembalikan index kode_detail_layanan
        if ($this->db->DBDriver !== 'SQLite3') {
            $this->db->query('ALTER TABLE `t_files_lab_jas` ADD INDEX `kode_detail_layanan` (`kode_detail_layanan`)');
        }

        // Kembalikan FK kode_detail_layanan
        if ($this->db->DBDriver !== 'SQLite3') {
            $this->db->query('
                ALTER TABLE `t_files_lab_jas` 
                ADD CONSTRAINT `t_files_lab_jas_kode_detail_layanan_foreign` 
                FOREIGN KEY (`kode_detail_layanan`) 
                REFERENCES `t_layanan_detil` (`kode`) 
                ON DELETE CASCADE 
                ON UPDATE CASCADE
            ');
        }

        // Ubah kode_layanan kembali jadi NULL
        $this->forge->modifyColumn('t_files_lab_jas', [
            'kode_layanan' => [
                'type' => 'INT',
                'unsigned' => true,
                'null' => true, // ← Kembalikan jadi NULL
            ],
        ]);
    }
}
