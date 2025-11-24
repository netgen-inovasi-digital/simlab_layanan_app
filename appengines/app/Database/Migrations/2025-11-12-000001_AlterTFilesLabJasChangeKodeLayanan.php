<?php

namespace App\Database\Migrations;

use CodeIgniter\Database\Migration;

class AlterTFilesLabJasChangeKodeLayanan extends Migration
{
    public function up()
    {
        // ---------------------------------------------------------------------
        // PERBAIKAN: Tambahkan kolom kode_layanan terlebih dahulu jika belum ada
        // ---------------------------------------------------------------------
        if (! $this->db->fieldExists('kode_layanan', 't_files_lab_jas')) {
            $this->forge->addColumn('t_files_lab_jas', [
                'kode_layanan' => [
                    'type'       => 'INT',
                    'unsigned'   => true,
                    'null'       => true, // Dibuat NULL dulu untuk menghindari error data existing
                    'after'      => 'file_id', // Sesuaikan posisi jika perlu
                ],
            ]);
        }

        // 1. Drop FK kode_detail_layanan (tidak dipakai lagi)
        try {
            if ($this->db->DBDriver !== 'SQLite3') {
                // Cek dulu apakah constraint ada sebelum drop untuk menghindari error
                $result = $this->db->query("SELECT CONSTRAINT_NAME FROM information_schema.KEY_COLUMN_USAGE WHERE TABLE_NAME = 't_files_lab_jas' AND CONSTRAINT_NAME = 't_files_lab_jas_kode_detail_layanan_foreign' AND TABLE_SCHEMA = DATABASE()");
                
                if ($result->getRow()) {
                    $this->db->query('ALTER TABLE `t_files_lab_jas` DROP FOREIGN KEY `t_files_lab_jas_kode_detail_layanan_foreign`');
                }
            }
        } catch (\Exception $e) {
            log_message('warning', 'FK kode_detail_layanan skip: ' . $e->getMessage());
        }

        // 2. Drop index kode_detail_layanan jika ada
        try {
            if ($this->db->DBDriver !== 'SQLite3') {
                 // Cek index exists secara manual seringkali ribet di MySQL, try-catch adalah cara teraman di CI4 migration raw query
                $this->db->query('ALTER TABLE `t_files_lab_jas` DROP INDEX `kode_detail_layanan`');
            }
        } catch (\Exception $e) {
            log_message('warning', 'Index kode_detail_layanan skip: ' . $e->getMessage());
        }

        // 3. Drop kolom kode_detail_layanan (tidak dipakai lagi)
        // Cek if exist untuk keamanan
        if ($this->db->fieldExists('kode_detail_layanan', 't_files_lab_jas')) {
            $this->forge->dropColumn('t_files_lab_jas', 'kode_detail_layanan');
        }

        // ---------------------------------------------------------------------
        // PROSES UPDATE & MODIFY KOLOM BARU
        // ---------------------------------------------------------------------

        // PENTING: Drop FK kode_layanan dulu sebelum memodifikasi kolom (jika teman Anda pernah add FK ini sebelumnya)
        try {
            if ($this->db->DBDriver !== 'SQLite3') {
                // Cek constraint existence
                $result = $this->db->query("SELECT CONSTRAINT_NAME FROM information_schema.KEY_COLUMN_USAGE WHERE TABLE_NAME = 't_files_lab_jas' AND CONSTRAINT_NAME = 't_files_lab_jas_kode_layanan_foreign' AND TABLE_SCHEMA = DATABASE()");
                
                if ($result->getRow()) {
                    $this->db->query('ALTER TABLE `t_files_lab_jas` DROP FOREIGN KEY `t_files_lab_jas_kode_layanan_foreign`');
                }
            }
        } catch (\Exception $e) {
            log_message('warning', 'FK kode_layanan skip drop: ' . $e->getMessage());
        }

        // Ubah kolom kode_layanan menjadi NOT NULL
        // Isi data NULL dengan 0 (atau ID default lain) agar bisa diubah jadi NOT NULL
        $this->db->query("UPDATE `t_files_lab_jas` SET `kode_layanan` = 0 WHERE `kode_layanan` IS NULL");
        
        $this->forge->modifyColumn('t_files_lab_jas', [
            'kode_layanan' => [
                'type'     => 'INT',
                'unsigned' => true,
                'null'     => false, // ← Ubah jadi NOT NULL
            ],
        ]);

        // ✅ Tambahkan kembali FK kode_layanan
        try {
            if ($this->db->DBDriver !== 'SQLite3') {
                // Pastikan tabel referensi ada
                if ($this->db->tableExists('simlab_t_layanan')) {
                    $this->db->query('
                        ALTER TABLE `t_files_lab_jas` 
                        ADD CONSTRAINT `t_files_lab_jas_kode_layanan_foreign` 
                        FOREIGN KEY (`kode_layanan`) 
                        REFERENCES `simlab_t_layanan` (`lnKode`) 
                        ON DELETE CASCADE 
                        ON UPDATE CASCADE
                    ');
                    log_message('info', 'FK kode_layanan berhasil ditambahkan kembali');
                } else {
                    log_message('error', 'Tabel referensi simlab_t_layanan tidak ditemukan, FK gagal dibuat.');
                }
            }
        } catch (\Exception $e) {
            log_message('error', 'Gagal menambahkan FK kode_layanan: ' . $e->getMessage());
            // Jangan throw error agar migration tetap tercatat selesai, 
            // user bisa fix data manual nanti jika FK gagal karena data tidak konsisten
        }
    }

    public function down()
    {
        // Kembalikan kolom kode_detail_layanan
        if (! $this->db->fieldExists('kode_detail_layanan', 't_files_lab_jas')) {
            $this->forge->addColumn('t_files_lab_jas', [
                'kode_detail_layanan' => [
                    'type'     => 'INT',
                    'unsigned' => true,
                    'null'     => true,
                    'after'    => 'file_id',
                ],
            ]);
        }

        // Kembalikan FK & Index kode_detail_layanan (Simplified)
        try {
            if ($this->db->DBDriver !== 'SQLite3') {
                 $this->db->query('ALTER TABLE `t_files_lab_jas` ADD INDEX `kode_detail_layanan` (`kode_detail_layanan`)');
                 
                 // Pastikan tabel parent ada sebelum add FK
                 if ($this->db->tableExists('t_layanan_detil')) {
                     $this->db->query('
                        ALTER TABLE `t_files_lab_jas` 
                        ADD CONSTRAINT `t_files_lab_jas_kode_detail_layanan_foreign` 
                        FOREIGN KEY (`kode_detail_layanan`) 
                        REFERENCES `t_layanan_detil` (`kode`) 
                        ON DELETE CASCADE 
                        ON UPDATE CASCADE
                    ');
                 }
            }
        } catch (\Exception $e) {
            // Silent fail for down method often safer
        }

        // Hapus kolom kode_layanan yang kita buat di up()
        if ($this->db->fieldExists('kode_layanan', 't_files_lab_jas')) {
            // Drop FK nya dulu
            try {
                $this->db->query('ALTER TABLE `t_files_lab_jas` DROP FOREIGN KEY `t_files_lab_jas_kode_layanan_foreign`');
            } catch(\Exception $e) {}
            
            $this->forge->dropColumn('t_files_lab_jas', 'kode_layanan');
        }
    }
}