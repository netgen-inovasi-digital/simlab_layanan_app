<?php

namespace App\Database\Migrations;

use CodeIgniter\Database\Migration;

class AlterRLayananPengujianRemoveOldColumns extends Migration
{
    public function up()
    {
        $db = \Config\Database::connect();
        
        // Check if old columns exist in the table and drop them
        if ($db->fieldExists('ujiPenyelia', 'r_layanan_pengujian')) {
            $this->forge->dropColumn('r_layanan_pengujian', 'ujiPenyelia');
        }
        
        if ($db->fieldExists('ujiManajerTeknis', 'r_layanan_pengujian')) {
            $this->forge->dropColumn('r_layanan_pengujian', 'ujiManajerTeknis');
        }
        
        // Also check if old table name exists (simlab_r_layanan_pengujian)
        // and migrate data to new table if needed
        if ($db->tableExists('simlab_r_layanan_pengujian') && !$db->tableExists('r_layanan_pengujian')) {
            // Rename table
            $db->query('RENAME TABLE `simlab_r_layanan_pengujian` TO `r_layanan_pengujian`');
            
            // Rename columns if they still use old naming convention
            $fields = $db->getFieldNames('r_layanan_pengujian');
            
            if (in_array('ujiKode', $fields)) {
                $db->query('ALTER TABLE `r_layanan_pengujian` CHANGE `ujiKode` `kode` INT(10) UNSIGNED NOT NULL AUTO_INCREMENT');
            }
            if (in_array('ujiLayanan', $fields)) {
                $db->query('ALTER TABLE `r_layanan_pengujian` CHANGE `ujiLayanan` `nama_layanan` VARCHAR(255) NULL');
            }
            if (in_array('ujiAlatKode', $fields)) {
                $db->query('ALTER TABLE `r_layanan_pengujian` CHANGE `ujiAlatKode` `kode_alat` VARCHAR(50) NULL');
            }
            if (in_array('ujiParaKode', $fields)) {
                $db->query('ALTER TABLE `r_layanan_pengujian` CHANGE `ujiParaKode` `kode_parameter` VARCHAR(20) NULL');
            }
            if (in_array('ujiJenKode', $fields)) {
                $db->query('ALTER TABLE `r_layanan_pengujian` CHANGE `ujiJenKode` `kode_jenis` VARCHAR(2) NULL');
            }
            if (in_array('ujiSatuan', $fields)) {
                $db->query('ALTER TABLE `r_layanan_pengujian` CHANGE `ujiSatuan` `satuan` VARCHAR(15) NULL');
            }
            if (in_array('ujiBiaya', $fields)) {
                $db->query('ALTER TABLE `r_layanan_pengujian` CHANGE `ujiBiaya` `biaya` DOUBLE NULL');
            }
            if (in_array('ujiDiskon', $fields)) {
                $db->query('ALTER TABLE `r_layanan_pengujian` CHANGE `ujiDiskon` `diskon` DOUBLE NULL');
            }
            
            // Drop old columns if they exist
            if (in_array('ujiPenyelia', $fields)) {
                $db->query('ALTER TABLE `r_layanan_pengujian` DROP COLUMN `ujiPenyelia`');
            }
            if (in_array('ujiManajerTeknis', $fields)) {
                $db->query('ALTER TABLE `r_layanan_pengujian` DROP COLUMN `ujiManajerTeknis`');
            }
        }
    }

    public function down()
    {
        // Optionally add columns back
        $db = \Config\Database::connect();
        
        if (!$db->fieldExists('ujiPenyelia', 'r_layanan_pengujian')) {
            $this->forge->addColumn('r_layanan_pengujian', [
                'ujiPenyelia' => [
                    'type' => 'INT',
                    'unsigned' => true,
                    'null' => true,
                    'after' => 'diskon'
                ]
            ]);
        }
        
        if (!$db->fieldExists('ujiManajerTeknis', 'r_layanan_pengujian')) {
            $this->forge->addColumn('r_layanan_pengujian', [
                'ujiManajerTeknis' => [
                    'type' => 'INT',
                    'unsigned' => true,
                    'null' => true,
                    'after' => 'ujiPenyelia'
                ]
            ]);
        }
    }
}
