<?php

namespace Modules\Keranjang\Config;

/**
 * Konfigurasi untuk berbagai jenis layanan
 * Setiap jenis layanan memiliki mapping tabel dan kolom yang berbeda
 */
class LayananConfig
{
    /**
     * Get konfigurasi berdasarkan jenis layanan
     * 
     * @param string $jenisLayanan 'pengujian', 'sewa', 'konsultasi', dll
     * @return array Konfigurasi tabel dan kolom
     */
    public static function getConfig(string $jenisLayanan): array
    {
        $configs = [
            // ========== LAYANAN PENGUJIAN (EXISTING) ==========
            'pengujian' => [
                'title' => 'Keranjang Layanan Pengujian',
                'session_key' => 'keranjang_pengujian',

                // Tabel database
                'table_layanan' => 'simlab_t_layanan',
                'table_detail' => 't_layanan_detil',
                'table_pengujian' => 'r_layanan_pengujian',
                'table_pembayaran' => 't_pembayaran',

                // Kolom untuk tabel pengujian (r_layanan_pengujian)
                'columns' => [
                    'kode' => 'kode',
                    'nama' => 'nama_layanan',
                    'biaya' => 'biaya',
                    'diskon' => 'diskon',
                    'alat' => 'kode_alat',
                    'parameter' => 'kode_parameter',
                    'jenis' => 'kode_jenis',
                    'satuan' => 'satuan',
                ],

                // Kolom untuk tabel detail (t_layanan_detil)
                'detail_columns' => [
                    'kode_layanan' => 'kode_layanan',
                    'uji_kode' => 'uji_kode',
                    'nama_layanan' => 'nama_layanan',
                    'biaya' => 'biaya',
                    'jumlah' => 'jumlah',
                    'catatan' => 'catatan_pelanggan',
                    'status' => 'status_layanan',
                    'kode_jenis' => 'kode_jenis',
                ],

                // JOIN tables
                'joins' => [
                    'parameter' => [
                        'table' => 'simlab_r_parameter',
                        'alias' => 'p',
                        'on' => 'p.paraKode = lp.kode_parameter',
                        'type' => 'left',
                        'display_column' => 'paraNama'
                    ],
                    'alat' => [
                        'table' => 'simlab_r_alat',
                        'alias' => 'a',
                        'on' => 'a.alatKode = lp.kode_alat',
                        'type' => 'left',
                        'display_column' => 'alatNama'
                    ],
                    'jenis' => [
                        'table' => 'simlab_r_jenis',
                        'alias' => 'j',
                        'on' => 'j.jenKode = lp.kode_jenis',
                        'type' => 'left',
                        'display_column' => 'jenNama'
                    ]
                ],

                // Field khusus yang ditampilkan di modal
                'modal_fields' => [
                    'Parameter',
                    'Instrumen/Alat/Tempat',
                    'Biaya',
                    'Jumlah',
                    'Keterangan',
                    'Aksi'
                ],

                // Validation rules
                'validation' => [
                    'min_jumlah' => 1,
                    'max_jumlah' => 999,
                    'require_keterangan' => false,
                ]
            ],

            // ========== LAYANAN SEWA ALAT (CONTOH BARU) ==========
            'sewa' => [
                'title' => 'Keranjang Layanan Sewa Alat',
                'session_key' => 'keranjang_sewa',

                // Tabel database
                'table_layanan' => 'simlab_t_layanan',
                'table_detail' => 't_sewa_detil',
                'table_pengujian' => 'r_layanan_sewa',
                'table_pembayaran' => 't_pembayaran',

                // Kolom untuk tabel sewa (r_layanan_sewa)
                'columns' => [
                    'kode' => 'id_sewa',
                    'nama' => 'nama_alat',
                    'biaya' => 'harga_sewa_perhari',
                    'diskon' => 'diskon',
                    'alat' => 'kode_alat',
                    'kategori' => 'kategori_alat',
                    'satuan' => 'satuan_waktu', // hari, minggu, bulan
                    'stok' => 'stok_tersedia',
                ],

                // Kolom untuk tabel detail sewa (t_sewa_detil)
                'detail_columns' => [
                    'kode_layanan' => 'kode_layanan',
                    'sewa_kode' => 'id_sewa',
                    'nama_layanan' => 'nama_alat',
                    'biaya' => 'total_biaya',
                    'jumlah' => 'qty',
                    'catatan' => 'catatan',
                    'status' => 'status_sewa',
                    'durasi' => 'durasi_hari',
                    'tanggal_mulai' => 'tanggal_mulai_sewa',
                    'tanggal_selesai' => 'tanggal_selesai_sewa',
                ],

                // JOIN tables
                'joins' => [
                    'alat' => [
                        'table' => 'simlab_r_alat',
                        'alias' => 'a',
                        'on' => 'a.alatKode = lp.kode_alat',
                        'type' => 'left',
                        'display_column' => 'alatNama'
                    ],
                    'kategori' => [
                        'table' => 'simlab_r_kategori_alat',
                        'alias' => 'k',
                        'on' => 'k.katKode = lp.kategori_alat',
                        'type' => 'left',
                        'display_column' => 'katNama'
                    ]
                ],

                // Field khusus yang ditampilkan di modal
                'modal_fields' => [
                    'Nama Alat',
                    'Kategori',
                    'Stok',
                    'Harga/Hari',
                    'Durasi (Hari)',
                    'Tanggal Mulai',
                    'Keterangan',
                    'Aksi'
                ],

                // Validation rules
                'validation' => [
                    'min_jumlah' => 1,
                    'max_jumlah' => 50,
                    'require_keterangan' => true,
                    'require_tanggal' => true,
                    'min_durasi' => 1,
                    'max_durasi' => 365,
                ]
            ],

        ];

        return $configs[$jenisLayanan] ?? $configs['pengujian']; // Default ke pengujian
    }

    /**
     * Get list semua jenis layanan yang tersedia
     * 
     * @return array
     */
    public static function getAvailableTypes(): array
    {
        return [
            'pengujian' => 'Layanan Pengujian',
            'sewa' => 'Sewa Alat',
        ];
    }

    /**
     * Validate apakah jenis layanan valid
     * 
     * @param string $jenisLayanan
     * @return bool
     */
    public static function isValidType(string $jenisLayanan): bool
    {
        return array_key_exists($jenisLayanan, self::getAvailableTypes());
    }
}
