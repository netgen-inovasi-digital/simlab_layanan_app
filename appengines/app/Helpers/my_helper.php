<?php

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
