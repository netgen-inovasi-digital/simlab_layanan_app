<?php

namespace App\Database\Seeds;

use CodeIgniter\Database\Seeder;

class AlatSeeder extends Seeder
{
    public function run()
    {
        $data = [
            ['kode' => 'AASFLAME', 'nama' => 'Instrumen AAS Flame-'],
            ['kode' => 'ASSGRAFUR', 'nama' => 'Instrumen AAS Graphite Furnace'],
            ['kode' => 'ASSVAPOUR', 'nama' => 'Instrumen AAS Vapour'],
            ['kode' => 'COBA', 'nama' => 'coba lagi, php'],
            ['kode' => 'COLONYCOUNTER', 'nama' => 'Colony Counter Digital'],
            ['kode' => 'DISTILASI', 'nama' => 'Parameter Distilasi'],
            ['kode' => 'EKSTRAKSI', 'nama' => "Parameter Ekstraksi\r\n"],
            ['kode' => 'ELISA', 'nama' => 'ELISA READER'],
            ['kode' => 'Fasilitas Magang (Umum)', 'nama' => 'Fasilitas Magang (Umum)'],
            ['kode' => 'Fasilitas Pelatihan (Umum)', 'nama' => 'Fasilitas Pelatihan (Umum)'],
            ['kode' => 'FRIABILITY', 'nama' => 'Friability Tester '],
            ['kode' => 'FTIR', 'nama' => 'Instrumen FTIR'],
            ['kode' => 'GCMS', 'nama' => 'Instrumen GCMS'],
            ['kode' => 'GRAVIMETRI', 'nama' => 'Metode Gravimetri'],
            ['kode' => 'HPLC', 'nama' => 'Instrumen HPLC'],
            ['kode' => 'jidan-', 'nama' => 'alat jidan'],
            ['kode' => 'KJELDAHL', 'nama' => 'Metode Kjeldahl'],
            ['kode' => 'KROMATOLAPIS', 'nama' => 'Instrumen Kromatografi Lapis Tipis'],
            ['kode' => 'Lidan', 'nama' => 'Lidan ae sdh-'],
            ['kode' => 'MIKROBIOLOGI', 'nama' => 'Pemeriksaan Mikrobiologi'],
            ['kode' => 'MIKROSKOP', 'nama' => 'Instrumen Mikroskop'],
            ['kode' => 'MOISTURE', 'nama' => 'Moisture Analyzer'],
            ['kode' => 'PH', 'nama' => 'pH meter'],
            ['kode' => 'PROKSIMAT', 'nama' => 'PARAMETER PROKSIMAT'],
            ['kode' => 'ROTATINGVISCO', 'nama' => 'Rotating Viscometer Digital'],
            ['kode' => 'RR', 'nama' => 'ruangan rapat'],
            ['kode' => 'RTPCR', 'nama' => 'Instrumen RT PCR'],
            ['kode' => 'ruangan', 'nama' => 'testing p'],
            ['kode' => 'SAA', 'nama' => 'Instrumen SAA'],
            ['kode' => 'SEM', 'nama' => 'SEM MORFOLOGI'],
            ['kode' => 'SEM 1', 'nama' => 'SEM 1'],
            ['kode' => 'SEM-EDX', 'nama' => 'SEM-EDX'],
            ['kode' => 'Sewa Alat', 'nama' => 'Sewa Alat'],
            ['kode' => 'Sewa Alat (Autoclave)', 'nama' => 'Sewa Alat (Autoclave)'],
            ['kode' => 'Sewa Alat (Freeze Dryer)', 'nama' => 'Sewa Alat (Freeze Dryer)'],
            ['kode' => 'Sewa Alat (Freezers -20°C  to -40°C )', 'nama' => 'Sewa Alat (Freezers -20 °C to -40°C )'],
            ['kode' => 'Sewa Alat (Furnace muffle up to 1400°C )', 'nama' => 'Sewa Alat (Furnace muffle up to 1400°C )'],
            ['kode' => 'Sewa Alat (Oven)', 'nama' => 'Sewa Alat (Oven)'],
            ['kode' => 'Sewa Alat (Refrigeratore Suhu 1-12 °C)', 'nama' => 'Sewa Alat (Refrigeratore Suhu 1-12 °C)'],
            ['kode' => 'Sewa Alat (Thermostate Incubator)', 'nama' => 'Sewa Alat (Thermostate Incubator)'],
            ['kode' => 'Sewa Alat Refrigerated Mikrocentrifuge', 'nama' => 'Sewa Alat Refrigerated Mikrocentrifuge'],
            ['kode' => 'Sewa Ruang Lab', 'nama' => 'Sewa Ruang Lab'],
            ['kode' => 'Sewa Ruangan Fullday', 'nama' => 'Sewa Ruangan Fullday'],
            ['kode' => 'Sewa Ruangan Halfday', 'nama' => 'Sewa Ruangan Halfday'],
            ['kode' => 'Sewa Ruangan Testing', 'nama' => 'Testing aja sih'],
            ['kode' => 'SPEKTRO UV-VIS', 'nama' => 'SPEKTRO UV-VIS'],
            ['kode' => 'SPEKTROF', 'nama' => 'Instrumen Spektrofotometri-Uv'],
            ['kode' => 'Test', 'nama' => 'Test aja sih'],
            ['kode' => 'TEXTURE ANALYZER-1', 'nama' => 'TEXTURE ANALYZER'],
            ['kode' => 'UV VIS', 'nama' => 'SPEKTRO UV-VIS'],
            ['kode' => 'VISKOMETER ', 'nama' => 'ROTATING VISKOMETER DIGITAL'],
        ];

        $this->db->table('r_alat')->insertBatch($data);
    }
}
