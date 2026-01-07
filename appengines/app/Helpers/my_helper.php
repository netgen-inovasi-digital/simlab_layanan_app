<?php

use App\Services\EmailServices;

if (!function_exists('buildQueryUrl')) {
    function buildQueryUrl(array $params = [], string $baseUrl = ''): string
    {
        $current = $_GET;

        // Gabungkan dengan parameter baru (akan menimpa yang lama)
        $query = array_merge($current, $params);

        // Buang parameter kosong (optional)
        $query = array_filter($query, fn($v) => $v !== '');

        // Gunakan base url sekarang jika tidak diset
        $uri = service('uri');
        $base = $baseUrl ?: $uri->getSegment(1);

        return base_url($base) . '?' . http_build_query($query);
    }
}

function formatTanggalIndo($tanggal)
{
    $bulanIndo = [
        1 => 'Januari',
        'Februari',
        'Maret',
        'April',
        'Mei',
        'Juni',
        'Juli',
        'Agustus',
        'September',
        'Oktober',
        'November',
        'Desember'
    ];

    $tanggal = date('Y-m-d', strtotime($tanggal));
    list($tahun, $bulan, $hari) = explode('-', $tanggal);

    return (int)$hari . ' ' . $bulanIndo[(int)$bulan] . ' ' . $tahun;
}

function formatAngkaSingkat($num)
{
    if ($num >= 1000000) {
        return round($num / 1000000, 1) . 'M';
    } elseif ($num >= 1000) {
        return round($num / 1000, 1) . 'k';
    }
    return $num;
}

if (!function_exists('formatRupiah')) {
    function formatRupiah($angka, $prefix = true) {
        $formatted = number_format($angka, 0, ',', '.');
        return $prefix ? 'Rp ' . $formatted : $formatted;
    }
}

if (!function_exists('parseSatuan')) {
    function parseSatuan($angka) {
        // Hapus format rupiah dan karakter non-numerik kecuali koma untuk desimal
        $angka = str_replace(['Rp.', 'Rp', ' '], '', $angka);
        // Ganti tanda titik (.) ribuan dengan string kosong
        $angka = str_replace('.', '', $angka);
        // Ganti tanda koma (,) desimal menjadi titik (.)
        $angka = str_replace(',', '.', $angka);
        // Ubah string menjadi float
        return (float)$angka;
    }
}

if (!function_exists('formatRupiahJS')) {
    function formatRupiahJS($angka) {
        // Format using Indonesian locale for JavaScript
        return 'new Intl.NumberFormat("id", {
            style: "currency",
            currency: "IDR",
            minimumFractionDigits: 0,
            maximumFractionDigits: 0
        }).format(' . $angka . ')';
    }
}

// mengirim notifikasi email
if (!function_exists('send_order_status_email')) {
    function send_order_status_email($email, $status, $dataOrder, $data = [])
    {
        $emailService = new EmailServices();
        $template     = '';
        $subject      = '';
        $templateData = [];

        switch ($status) {
            case 'diproses':
                $template = 'status_diproses';
                $subject  = 'Pesanan Anda Sedang Diproses';
                $templateData = [
                    'subject'   => $subject,
                    'nama'      => $dataOrder->nama_penerima,
                    'order_id'  => $dataOrder->order_number,
                ];
                break;

            case 'dikirim':
                $template = 'status_dikirim';
                $subject  = 'Pesanan Anda Telah Dikirim';
                $templateData = [
                    'subject'   => $subject,
                    'nama'      => $dataOrder->nama_penerima,
                    'order_id'  => $dataOrder->order_number,
                    'ekspedisi'     => $dataOrder->nama_ekspedisi,
                    'no_resi'   => $dataOrder->resi ?? '000000',
                ];
                break;

            case 'siap_diambil':
                $template = 'status_siap_diambil';
                $subject  = 'Pesanan Anda Siap Diambil';
                $templateData = [
                    'subject'   => $subject,
                    'nama'      => $dataOrder->nama_penerima,
                    'order_id'  => $dataOrder->order_number,
                    'alamat_toko'    => $data['alamat'],
                ];
                break;

            case 'selesai':
                $template = 'status_selesai';
                $subject  = 'Pesanan Anda Telah Selesai';
                $templateData = [
                    'subject'   => $subject,
                    'nama'      => $dataOrder->nama_penerima,
                    'order_id'  => $dataOrder->order_number,
                ];
                break;

            case 'dibatalkan':
                $template = 'status_dibatalkan';
                $subject  = 'Pesanan Anda Dibatalkan';
                $templateData = [
                    'subject'   => $subject,
                    'nama'      => $dataOrder->nama_penerima,
                    'order_id'  => $dataOrder->order_number,
                    'alasan'    => $data['catatan'] ?? 'Tidak ada alasan khusus.',
                ];
                break;

            default:
                return false;
        }

        return $emailService->sendOrderStatus($email, $subject, $template, $templateData);
    }
}

/**
 * Generate asset URL with automatic cache-busting based on file modification time.
 * When the file is modified, the timestamp changes, forcing browsers to download the new version.
 * 
 * @param string $path Relative path to asset from base_url (e.g., 'assets/js/sayTable.js')
 * @param string|null $version Optional manual version string (takes precedence if provided)
 * @return string Full URL with cache-busting query parameter
 */
if (!function_exists('asset_url')) {
    function asset_url(string $path, ?string $version = null): string
    {
        // Get the full filesystem path to the asset
        $fullPath = FCPATH . $path;
        
        // Determine cache-busting value
        if ($version !== null) {
            // Use manual version if provided
            $cacheBuster = $version;
        } elseif (file_exists($fullPath)) {
            // Use file modification time as cache buster
            $cacheBuster = filemtime($fullPath);
        } else {
            // Fallback to current timestamp if file doesn't exist
            $cacheBuster = time();
        }
        
        return base_url($path) . '?v=' . $cacheBuster;
    }
}

