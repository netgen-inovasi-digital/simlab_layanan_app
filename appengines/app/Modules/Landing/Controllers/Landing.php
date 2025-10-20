<?php

namespace Modules\Landing\Controllers;

use App\Controllers\BaseController;

use App\Models\MyModel;

class Landing extends BaseController
{

	public function index()
    {
        $modelPengumuman = new MyModel('pengumuman');
        
        $getPengumuman = $modelPengumuman->builder()
            ->where('status', 'tampil')
            ->orderBy('id_pengumuman', 'DESC') 
            ->get()
            ->getResult(); 

        $data = [
            'title'         => 'Lab Terpadu ULM',
            'content'       => 'Modules\Landing\Views\landing',
            'getPengumuman' => $getPengumuman,
        ];

        return view('website', $data);
    }
}