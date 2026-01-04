<?php

namespace App\Database\Seeds;

use CodeIgniter\Database\Seeder;

class TransaksiSeeder extends Seeder
{
  public function run()
  {
    /**
     * 1Data Transaksi Utama (t_layanan)
     */
    $layanan = [
      [
        'kode_layanan' => 100,
        'user_id' => 1,
        'user_email' => 'admin@example.com',
        'no_invoice' => 'TRX-20251109-001',
        'tanggal_checkout' => '2025-11-09 09:15:00',
        'status_layanan' => 3,
        'kuisioner' => 0,
        'tgl_pelaksanaan' => null,
        'catatan_kaji_ulang' => null,
        'jumlah_kaji_ulang' => 0,
      ],
      [
        'kode_layanan' => 101,
        'user_id' => 1,
        'user_email' => 'manajerA@example.com',
        'no_invoice' => 'TRX-20251109-002',
        'tanggal_checkout' => '2025-11-09 10:30:00',
        'status_layanan' => 3,
        'kuisioner' => 1,
        'tgl_pelaksanaan' => null,
        'catatan_kaji_ulang' => null,
        'jumlah_kaji_ulang' => 0,
      ],
      [
        'kode_layanan' => 102,
        'user_id' => 1,
        'user_email' => 'penyeliaB@example.com',
        'no_invoice' => 'TRX-20251109-003',
        'tanggal_checkout' => '2025-11-09 11:45:00',
        'status_layanan' => 3,
        'kuisioner' => 0,
        'tgl_pelaksanaan' => null,
        'catatan_kaji_ulang' => null,
        'jumlah_kaji_ulang' => 0,
      ],
    ];

    $this->db->table('t_layanan')->insertBatch($layanan);

    /**
     * 2️ Data Detail Layanan (t_layanan_detil)
     */
    $detil = [
      // Untuk kode_layanan = 100
      [
        'uji_kode' => 1,
        'biaya' => 100000,
        'jumlah' => 1,
        'status_layanan' => 0,
        'kode_layanan' => 100,
        'kode_jenis' => 'A',
        'nama_layanan' => 'Instrumen FTIR - Al (Aluminium) - Tanah, air',
        'metode_pengujian' => null,
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
        'metode_pengujian' => null,
        'catatan_manajer' => null,
        'terima_layanan_by' => null,
        'files' => null,
      ],

      // Untuk kode_layanan = 101
      [
        'uji_kode' => 3,
        'biaya' => 598000,
        'jumlah' => 1,
        'status_layanan' => 1,
        'kode_layanan' => 101,
        'kode_jenis' => 'A',
        'nama_layanan' => 'PARAMETER PROKSIMAT : PROKSIMAT(Air, Abu, Protein kasar, Lemak, Karbohidrat, & Serat kasar)',
        'metode_pengujian' => null,
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
        'metode_pengujian' => null,
        'catatan_manajer' => null,
        'terima_layanan_by' => null,
        'files' => null,
      ],

      // Untuk kode_layanan = 102
      [
        'uji_kode' => 1,
        'biaya' => 450000,
        'jumlah' => 1,
        'status_layanan' => 2,
        'kode_layanan' => 102,
        'kode_jenis' => 'A',
        'nama_layanan' => 'SEM MORFOLOGI : SEM MORFOLOGI',
        'metode_pengujian' => null,
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
        'metode_pengujian' => null,
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
        'kode_bayar' => 1,
        'kode_layanan' => 100,
        'total_biaya' => 100000 + (1000001 * 2),
        'invoice_file' => null,
        'status_bayar' => 0,
        'bukti_bayar' => null,
        'no_invoice' => 'INV-20251109-100',
        'tanggal_invoice' => '2025-11-09',
        'catatan_pembayaran' => 'Invoice dibuat; menunggu pembayaran klien.',
      ],
      [
        'kode_bayar' => 2,
        'kode_layanan' => 101,
        'total_biaya' => 598000 + (25000 * 3),
        'invoice_file' => null,
        'status_bayar' => 1,
        'bukti_bayar' => 'bukti_501.jpg',
        'no_invoice' => 'INV-20251109-101',
        'tanggal_invoice' => '2025-11-09',
        'catatan_pembayaran' => 'Terima bukti transfer, status dibayar.',
      ],
      [
        'kode_bayar' => 3,
        'kode_layanan' => 102,
        'total_biaya' => 450000 + (100000 * 2),
        'invoice_file' => null,
        'status_bayar' => 0,
        'bukti_bayar' => null,
        'no_invoice' => 'INV-20251109-102',
        'tanggal_invoice' => '2025-11-09',
        'catatan_pembayaran' => 'Dikirim ke bagian penagihan.',
      ],
    ];

    $this->db->table('t_pembayaran')->insertBatch($pembayaran);
  }
}
