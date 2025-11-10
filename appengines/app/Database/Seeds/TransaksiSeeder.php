<?php

namespace App\Database\Seeds;

use CodeIgniter\Database\Seeder;

class TransaksiSeeder extends Seeder
{
    public function run()
    {
        /**
         * 1Data Transaksi Utama (simlab_t_layanan)
         */
        $layanan = [
            [
                'lnKode' => 100,
                'user_id' => 1,
                'lnAccEmail' => 'admin@example.com',
                'lnNoTransaksi' => 'TRX-20251109-001',
                'lnTgl' => '2025-11-09 09:15:00',
                'lnStatus' => 3,
                'kuisioner' => 0,
                'lhu_id' => null,
            ],
            [
                'lnKode' => 101,
                'user_id' => 1,
                'lnAccEmail' => 'manajerA@example.com',
                'lnNoTransaksi' => 'TRX-20251109-002',
                'lnTgl' => '2025-11-09 10:30:00',
                'lnStatus' => 3,
                'kuisioner' => 1,
                'lhu_id' => null,
            ],
            [
                'lnKode' => 102,
                'user_id' => 1,
                'lnAccEmail' => 'penyeliaB@example.com',
                'lnNoTransaksi' => 'TRX-20251109-003',
                'lnTgl' => '2025-11-09 11:45:00',
                'lnStatus' => 3,
                'kuisioner' => 0,
                'lhu_id' => null,
            ],
        ];

        $this->db->table('simlab_t_layanan')->insertBatch($layanan);

        /**
         * 2️ Data Detail Layanan (t_layanan_detil)
         */
        $detil = [
            // Untuk lnKode = 100
            [
                'uji_kode' => 1,
                'biaya' => 100000,
                'jumlah' => 1,
                'status_layanan' => 0,
                'kode_layanan' => 100,
                'kode_jenis' => 'A',
                'nama_layanan' => 'Instrumen FTIR - Al (Aluminium) - Tanah, air',
                'catatan_pelanggan' => 'Sample 1 dari klien X',
                'catatan_manajer' => null,
                'terima_layanan_by' => null,
                'files' => null,
            ],
            [
                'uji_kode' => 2,
                'biaya' => 1000001,
                'jumlah' => 2,
                'status_layanan' => 0,
                'kode_layanan' => 100,
                'kode_jenis' => 'A',
                'nama_layanan' => 'Instrumen AAS Flame : Cd (tanah,air,pangan, non pangan)',
                'catatan_pelanggan' => 'Butuh hasil cepat',
                'catatan_manajer' => null,
                'terima_layanan_by' => null,
                'files' => null,
            ],

            // Untuk lnKode = 101
            [
                'uji_kode' => 3,
                'biaya' => 598000,
                'jumlah' => 1,
                'status_layanan' => 1,
                'kode_layanan' => 101,
                'kode_jenis' => 'A',
                'nama_layanan' => 'PARAMETER PROKSIMAT : PROKSIMAT(Air, Abu, Protein kasar, Lemak, Karbohidrat, & Serat kasar)',
                'catatan_pelanggan' => 'Paket lengkap untuk bahan makanan',
                'catatan_manajer' => null,
                'terima_layanan_by' => null,
                'files' => null,
            ],
            [
                'uji_kode' => 1,
                'biaya' => 25000,
                'jumlah' => 3,
                'status_layanan' => 0,
                'kode_layanan' => 101,
                'kode_jenis' => 'A',
                'nama_layanan' => 'Instrumen Mikroskop : FOTO PREPARAT',
                'catatan_pelanggan' => null,
                'catatan_manajer' => null,
                'terima_layanan_by' => null,
                'files' => null,
            ],

            // Untuk lnKode = 102
            [
                'uji_kode' => 1,
                'biaya' => 450000,
                'jumlah' => 1,
                'status_layanan' => 2,
                'kode_layanan' => 102,
                'kode_jenis' => 'A',
                'nama_layanan' => 'SEM MORFOLOGI : SEM MORFOLOGI',
                'catatan_pelanggan' => 'Gunakan preparat B',
                'catatan_manajer' => null,
                'terima_layanan_by' => null,
                'files' => null,
            ],
            [
                'uji_kode' => 2,
                'biaya' => 100000,
                'jumlah' => 2,
                'status_layanan' => 0,
                'kode_layanan' => 102,
                'kode_jenis' => 'A',
                'nama_layanan' => 'Instrumen AAS Flame- - ASAM GALAT',
                'catatan_pelanggan' => null,
                'catatan_manajer' => null,
                'terima_layanan_by' => null,
                'files' => null,
            ],
        ];

        $this->db->table('t_layanan_detil')->insertBatch($detil);

        /**
         * 3️ Data Pembayaran (t_pembayaran)
         */
        $pembayaran = [
            [
                'bayarKode' => 1,
                'bayarLnKode' => 100,
                'bayarTotalBiaya' => 100000 + (1000001 * 2),
                'bayarInvoiceFile' => null,
                'bayarStatus' => 0,
                'bayarBuktiFile' => null,
                'bayarInvoiceNo' => 'INV-20251109-100',
                'bayarInvoiceTgl' => '2025-11-09',
                'bayarCatatan' => 'Invoice dibuat; menunggu pembayaran klien.',
            ],
            [
                'bayarKode' => 2 ,
                'bayarLnKode' => 101,
                'bayarTotalBiaya' => 598000 + (25000 * 3),
                'bayarInvoiceFile' => null,
                'bayarStatus' => 1,
                'bayarBuktiFile' => 'bukti_501.jpg',
                'bayarInvoiceNo' => 'INV-20251109-101',
                'bayarInvoiceTgl' => '2025-11-09',
                'bayarCatatan' => 'Terima bukti transfer, status dibayar.',
            ],
            [
                'bayarKode' => 3,
                'bayarLnKode' => 102,
                'bayarTotalBiaya' => 450000 + (100000 * 2),
                'bayarInvoiceFile' => null,
                'bayarStatus' => 0,
                'bayarBuktiFile' => null,
                'bayarInvoiceNo' => 'INV-20251109-102',
                'bayarInvoiceTgl' => '2025-11-09',
                'bayarCatatan' => 'Dikirim ke bagian penagihan.',
            ],
        ];

        $this->db->table('t_pembayaran')->insertBatch($pembayaran);
    }
}
