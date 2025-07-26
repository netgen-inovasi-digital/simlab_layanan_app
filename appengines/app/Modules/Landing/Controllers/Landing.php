<?php

namespace Modules\Landing\Controllers;

use App\Controllers\BaseController;

use App\Models\MyModel;

class Landing extends BaseController
{

	public function index()
	{

		$data = [
			'title' => 'Klinik Medikidz',
		];
		return view('website', $data);
	}
}
