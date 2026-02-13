<?php

namespace App\Database\Migrations;

use CodeIgniter\Database\Migration;

class CreateSimlabTLayanan extends Migration
{
    public function up()
    {
        $this->forge->addField([
            'kode_layanan' => [
                'type' => 'INT',
                'unsigned' => true,
                'auto_increment' => true,
            ],
            'user_id' => [
                'type' => 'INT',
                'unsigned' => true,
                'null' => true,
            ],
            'user_email' => [
                'type' => 'VARCHAR',
                'constraint' => 100,
                'null' => true,
                'comment' => 'Email akun yang menginput layanan (bisa admin/user lain)',
            ],
            'no_invoice' => [
                'type' => 'VARCHAR',
                'constraint' => 25,
                'null' => true,
            ],
            'tanggal_checkout' => [
                'type' => 'DATETIME',
                'null' => true,
            ],
            'status_layanan' => [
                'type' => 'TINYINT',
                'default' => 0,
                'comment' => '0=Draft; 1=InReviewManajer; 2=Ditolak; 3=InReviewAdmin; 4=Pengujian; 5=Memproses LHUS; 6=LHUS disetujui; 7=Memproses LHU; 8=LHU Disetujui; 9=Selesai',
            ],
            'kuisioner' => [
                'type' => 'TINYINT',
                'constraint' => 1,
                'null' => true,
                'comment' => '0=Belum isi; 1=Sudah isi',
            ],
            'tgl_pelaksanaan' => [
                'type' => 'DATETIME',
                'null' => true,
            ],
            'catatan_kaji_ulang' => [
                'type' => 'TEXT',
                'null' => true,
            ],
            'jumlah_kaji_ulang' => [
                'type' => 'INT',
                'default' => 0,
                'null' => true,
            ],
            'surat_pernyataan' => [
                'type' => 'VARCHAR',
                'constraint' => 255,
                'null' => true,
            ],
        ]);

        $this->forge->addKey('kode_layanan', true);
        $this->forge->addKey('user_id');

        $this->forge->createTable('t_layanan', true);

        $this->forge->addForeignKey(
            'user_id',
            'account_users',
            'user_id',
            'SET NULL',
            'CASCADE'
        );
    }

    public function down()
    {
        $this->forge->dropTable('t_layanan', true);
    }
}
