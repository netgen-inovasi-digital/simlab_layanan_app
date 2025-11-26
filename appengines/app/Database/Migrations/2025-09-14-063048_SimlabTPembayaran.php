<?php

namespace App\Database\Migrations;

use CodeIgniter\Database\Migration;

class CreateTPembayaran extends Migration
{
    public function up()
    {
        $this->forge->addField([
            'bayarKode' => [
                'type' => 'INT',
                'unsigned' => true,
                'auto_increment' => true,
            ],
            'bayarLnKode' => [
                'type' => 'INT',
                'unsigned' => true, // <-- pastikan unsigned sesuai simlab_t_layanan.lnKode
                'null' => true,
            ],
            'bayarTotalBiaya' => [
                'type' => 'DOUBLE',
                'null' => true,
            ],
            'bayarInvoiceFile' => [
                'type' => 'VARCHAR',
                'constraint' => 255,
                'null' => true,
            ],
            'bayarStatus' => [
                'type' => 'INT',
                'default' => 0,
            ],
            'bayarBuktiFile' => [
                'type' => 'VARCHAR',
                'constraint' => 255,
                'null' => true,
            ],
            'bayarInvoiceNo' => [
                'type' => 'VARCHAR',
                'constraint' => 50,
                'null' => true,
            ],
            'bayarInvoiceTgl' => [
                'type' => 'DATE',
                'null' => true,
            ],
            'bayarCatatan' => [
                'type' => 'VARCHAR',
                'constraint' => 255,
                'null' => true,
            ],
        ]);

        $this->forge->addKey('bayarKode', true);
        $this->forge->addKey('bayarLnKode');

        // foreign key using Forge (safer)
        $this->forge->addForeignKey('bayarLnKode', 'simlab_t_layanan', 'lnKode', 'RESTRICT', 'RESTRICT');

        $this->forge->createTable('t_pembayaran', true);
    }

    public function down()
    {
        $this->forge->dropTable('t_pembayaran', true);
    }
}
