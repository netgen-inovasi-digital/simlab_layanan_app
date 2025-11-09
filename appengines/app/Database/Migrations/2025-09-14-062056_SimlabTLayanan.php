<?php

namespace App\Database\Migrations;

use CodeIgniter\Database\Migration;

class CreateSimlabTLayanan extends Migration
{
    public function up()
    {
        $this->forge->addField([
            'lnKode' => [
                'type' => 'INT',
                'unsigned' => true,
                'auto_increment' => true,
            ],
            'user_id' => [
                'type' => 'INT',
                'unsigned' => true, // samakan dengan simlab_account_users.user_id
                'null' => true,
            ],
            'lnAccEmail' => [
                'type' => 'VARCHAR',
                'constraint' => 100,
                'null' => true,
                'comment' => 'Email akun yang menginput layanan (bisa admin/user lain)',
            ],
            'lnNoTransaksi' => [
                'type' => 'VARCHAR',
                'constraint' => 25,
                'null' => true,
            ],
            'lnTgl' => [
                'type' => 'DATETIME',
                'null' => true,
            ],
            'lnStatus' => [
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
            'lhu_id' => [
                'type' => 'INT',
                'unsigned' => true, // jika nanti mau jadikan FK ke t_files_lhu.file_id
                'null' => true,
            ],
        ]);

        $this->forge->addKey('lnKode', true);
        $this->forge->addKey('user_id');
        $this->forge->addKey('lhu_id');

        // create table first
        $this->forge->createTable('simlab_t_layanan', true);

        // add FK via Forge (could also be done before createTable; doing before sometimes fails if ref table not exists)
        // but since simlab_account_users should exist earlier, we add via DB query or Forge:
        $this->forge->addForeignKey('user_id', 'simlab_account_users', 'user_id', 'SET NULL', 'CASCADE');

        // If lhu_id should reference t_files_lhu.file_id, make sure t_files_lhu migration runs before this or add later.
        // Note: Some DB engines require FK additions before/after; adapt order of migrations accordingly.
    }

    public function down()
    {
        $this->forge->dropTable('simlab_t_layanan', true);
    }
}
