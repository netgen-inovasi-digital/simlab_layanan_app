<?php

namespace App\Database\Migrations;

use CodeIgniter\Database\Migration;

class AlterTFilesLhusAddKodeLayanan extends Migration
{
    public function up()
    {
        // Cek apakah kolom kode_layanan sudah ada
        if (!$this->db->fieldExists('kode_layanan', 't_files_lhus')) {
            // Tambahkan kolom kode_layanan
            $fields = [
                'kode_layanan' => [
                    'type' => 'INT',
                    'unsigned' => true,
                    'null' => true,
                    'comment' => 'Relasi ke simlab_t_layanan.lnKode untuk memudahkan pengecekan kelengkapan upload per invoice',
                    'after' => 'kode'
                ]
            ];
            $this->forge->addColumn('t_files_lhus', $fields);

            // Tambahkan index untuk kolom kode_layanan
            $this->forge->addKey('kode_layanan');
            $this->db->query('ALTER TABLE t_files_lhus ADD INDEX idx_kode_layanan (kode_layanan)');

            // Tambahkan foreign key
            $this->db->query('ALTER TABLE t_files_lhus ADD CONSTRAINT fk_lhus_kode_layanan FOREIGN KEY (kode_layanan) REFERENCES simlab_t_layanan(lnKode) ON DELETE CASCADE ON UPDATE CASCADE');
        }

        // Update existing records: set kode_layanan dari t_layanan_detil
        $sql = "
            UPDATE t_files_lhus lhus
            INNER JOIN t_layanan_detil d ON d.kode = lhus.kode
            SET lhus.kode_layanan = d.kode_layanan
            WHERE lhus.kode_layanan IS NULL
        ";
        $this->db->query($sql);
    }

    public function down()
    {
        // Drop foreign key dan kolom
        if ($this->db->fieldExists('kode_layanan', 't_files_lhus')) {
            // Drop foreign key terlebih dahulu
            try {
                $this->db->query('ALTER TABLE t_files_lhus DROP FOREIGN KEY fk_lhus_kode_layanan');
            } catch (\Exception $e) {
                // Ignore jika FK tidak ada
            }

            // Drop kolom
            $this->forge->dropColumn('t_files_lhus', 'kode_layanan');
        }
    }
}
