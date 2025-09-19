<?php

namespace App\Database\Migrations;

use CodeIgniter\Database\Migration;

class CreateSimlabTPembayaran extends Migration
{
    public function up()
    {
        $this->forge->addField([
            'bayarKode' => [
                'type'           => 'INT',
                'constraint'     => 11,
                'unsigned'       => true,
                'auto_increment' => true
            ],
            'bayarLnKode' => [
                'type'       => 'INT',
                'constraint' => 11,
                'null'       => true
            ],
            'bayarTotalBiaya' => [
                'type'       => 'DOUBLE',
                'null'       => true
            ],
            'bayarInvoiceFile' => [
                'type'       => 'VARCHAR',
                'constraint' => 255,
                'null'       => true
            ],
            'bayarStatus' => [
                'type'       => 'INT',
                'constraint' => 11,
                'default'    => 0,
                'comment'    => '0=belum bayar; 1=sudah bayar'
            ],
            'bayarBuktiFile' => [
                'type'       => 'VARCHAR',
                'constraint' => 255,
                'null'       => true
            ],
            'bayarInvoiceNo' => [
                'type'       => 'VARCHAR',
                'constraint' => 50,
                'null'       => true
            ],
            'bayarInvoiceTgl' => [
                'type'       => 'DATE',
                'null'       => true
            ],
        ]);

        $this->forge->addKey('bayarKode', true);
        $this->forge->createTable('simlab_t_pembayaran');
    }

    public function down()
    {
        $this->forge->dropTable('simlab_t_pembayaran');
    }
}
