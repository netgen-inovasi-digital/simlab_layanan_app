<?php

namespace App\Database\Seeds;

use CodeIgniter\Database\Seeder;

class AlatSeeder extends Seeder
{
    public function run()
    {
        $data = [
            ['alatKode' => 'AASFLAME', 'alatNama' => 'Instrumen AAS Flame-'],
            ['alatKode' => 'ASSGRAFUR', 'alatNama' => 'Instrumen AAS Graphite Furnace'],
            ['alatKode' => 'ASSVAPOUR', 'alatNama' => 'Instrumen AAS Vapour'],
            ['alatKode' => 'COBA', 'alatNama' => 'coba lagi, php'],
            ['alatKode' => 'COLONYCOUNTER', 'alatNama' => 'Colony Counter Digital'],
            ['alatKode' => 'DISTILASI', 'alatNama' => 'Parameter Distilasi'],
            ['alatKode' => 'EKSTRAKSI', 'alatNama' => "Parameter Ekstraksi\r\n"],
            ['alatKode' => 'ELISA', 'alatNama' => 'ELISA READER'],
            ['alatKode' => 'Fasilitas Magang (Umum)', 'alatNama' => 'Fasilitas Magang (Umum)'],
            ['alatKode' => 'Fasilitas Pelatihan (Umum)', 'alatNama' => 'Fasilitas Pelatihan (Umum)'],
            ['alatKode' => 'FRIABILITY', 'alatNama' => 'Friability Tester '],
            ['alatKode' => 'FTIR', 'alatNama' => 'Instrumen FTIR'],
            ['alatKode' => 'GCMS', 'alatNama' => 'Instrumen GCMS'],
            ['alatKode' => 'GRAVIMETRI', 'alatNama' => 'Metode Gravimetri'],
            ['alatKode' => 'HPLC', 'alatNama' => 'Instrumen HPLC'],
            ['alatKode' => 'jidan-', 'alatNama' => 'alat jidan'],
            ['alatKode' => 'KJELDAHL', 'alatNama' => 'Metode Kjeldahl'],
            ['alatKode' => 'KROMATOLAPIS', 'alatNama' => 'Instrumen Kromatografi Lapis Tipis'],
            ['alatKode' => 'Lidan', 'alatNama' => 'Lidan ae sdh-'],
            ['alatKode' => 'MIKROBIOLOGI', 'alatNama' => 'Pemeriksaan Mikrobiologi'],
            ['alatKode' => 'MIKROSKOP', 'alatNama' => 'Instrumen Mikroskop'],
            ['alatKode' => 'MOISTURE', 'alatNama' => 'Moisture Analyzer'],
            ['alatKode' => 'PH', 'alatNama' => 'pH meter'],
            ['alatKode' => 'PROKSIMAT', 'alatNama' => 'PARAMETER PROKSIMAT'],
            ['alatKode' => 'ROTATINGVISCO', 'alatNama' => 'Rotating Viscometer Digital'],
            ['alatKode' => 'RTPCR', 'alatNama' => 'Instrumen RT PCR'],
            ['alatKode' => 'ruangan', 'alatNama' => 'testing p'],
            ['alatKode' => 'SAA', 'alatNama' => 'Instrumen SAA'],
            ['alatKode' => 'SEM', 'alatNama' => 'SEM MORFOLOGI'],
            ['alatKode' => 'SEM 1', 'alatNama' => 'SEM 1'],
            ['alatKode' => 'SEM-EDX', 'alatNama' => 'SEM-EDX'],
            ['alatKode' => 'Sewa Alat', 'alatNama' => 'Sewa Alat'],
            ['alatKode' => 'Sewa Alat (Autoclave)', 'alatNama' => 'Sewa Alat (Autoclave)'],
            ['alatKode' => 'Sewa Alat (Freeze Dryer)', 'alatNama' => 'Sewa Alat (Freeze Dryer)'],
            ['alatKode' => 'Sewa Alat (Freezers -20°C  to -40°C )', 'alatNama' => 'Sewa Alat (Freezers -20 °C to -40°C )'],
            ['alatKode' => 'Sewa Alat (Furnace muffle up to 1400°C )', 'alatNama' => 'Sewa Alat (Furnace muffle up to 1400°C )'],
            ['alatKode' => 'Sewa Alat (Oven)', 'alatNama' => 'Sewa Alat (Oven)'],
            ['alatKode' => 'Sewa Alat (Refrigeratore Suhu 1-12 °C)', 'alatNama' => 'Sewa Alat (Refrigeratore Suhu 1-12 °C)'],
            ['alatKode' => 'Sewa Alat (Thermostate Incubator)', 'alatNama' => 'Sewa Alat (Thermostate Incubator)'],
            ['alatKode' => 'Sewa Alat Refrigerated Mikrocentrifuge', 'alatNama' => 'Sewa Alat Refrigerated Mikrocentrifuge'],
            ['alatKode' => 'Sewa Ruang Lab', 'alatNama' => 'Sewa Ruang Lab'],
            ['alatKode' => 'Sewa Ruangan Fullday', 'alatNama' => 'Sewa Ruangan Fullday'],
            ['alatKode' => 'Sewa Ruangan Halfday', 'alatNama' => 'Sewa Ruangan Halfday'],
            ['alatKode' => 'Sewa Ruangan Testing', 'alatNama' => 'Testing aja sih'],
            ['alatKode' => 'SPEKTRO UV-VIS', 'alatNama' => 'SPEKTRO UV-VIS'],
            ['alatKode' => 'SPEKTROF', 'alatNama' => 'Instrumen Spektrofotometri-Uv'],
            ['alatKode' => 'Test', 'alatNama' => 'Test aja sih'],
            ['alatKode' => 'TEXTURE ANALYZER-1', 'alatNama' => 'TEXTURE ANALYZER'],
            ['alatKode' => 'UV VIS', 'alatNama' => 'SPEKTRO UV-VIS'],
            ['alatKode' => 'VISKOMETER ', 'alatNama' => 'ROTATING VISKOMETER DIGITAL'],
        ];

        // Insert batch (skips duplicates depending on DB constraints)
        $builder = $this->db->table('simlab_r_alat');
        $builder->insertBatch($data);
    }
}
