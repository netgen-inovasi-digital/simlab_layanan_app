<?php

namespace Modules\Landing\Controllers;

use App\Controllers\BaseController;
use App\Models\MyModel;

class Landing extends BaseController
{

    public function index()
    {
        $modelPengumuman = new MyModel('pengumuman');
        $modelJenisLayanan = new MyModel('simlab_r_jenis');
        $modelLayanan = new MyModel('r_layanan_pengujian');

        // Get Pengumuman
        $getPengumuman = $modelPengumuman->builder()
            ->where('status', 'tampil')
            ->orderBy('id_pengumuman', 'DESC') 
            ->get()
            ->getResult(); 

        // Get Jenis Layanan (for the dropdown)
        $getJenisLayanan = $modelJenisLayanan->builder()
            ->orderBy('kode', 'ASC')
            ->get()
            ->getResult();

        // Get Layanan (for the table)
        $getLayanan = $modelLayanan->builder()
            ->select('nama_layanan as judul, 
                      biaya as biaya, 
                      satuan, 
                      kode_jenis, 
                      status')
            ->where('status', 'Y')
            ->orderBy('nama_layanan', 'ASC')
            ->get()
            ->getResult();
            
        $data = [
            'title'           => 'Lab Terpadu ULM',
            'content'         => 'Modules\Landing\Views\v_landing',
            'getPengumuman'   => $getPengumuman,
            'getJenisLayanan' => $getJenisLayanan,
            'getLayanan'      => $getLayanan,     
        ];


        return view('website', $data);
    }
}