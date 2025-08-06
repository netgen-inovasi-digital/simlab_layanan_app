<?php

namespace Modules\Dashboard\Controllers;

use App\Controllers\BaseController;
use App\Models\MyModel;

class Dashboard extends BaseController
{
	public function index()
	{
		$data = $this->getDashboardData();
		$data['title'] = 'Dashboard';
		$data['content'] = 'Modules\Dashboard\Views\v_dashboard';
		return view('template', $data);
	}

	public function load()
	{
		$data = $this->getDashboardData();
		$data['title'] = 'Dashboard';
		return view('Modules\Dashboard\Views\v_dashboard', $data);
	}


	private function getDashboardData()
	{
		$session = session();
		$nama = $session->get('nama');
		$role_id = $session->get('role_id');

		date_default_timezone_set('Asia/Makassar');
		$hour = date('H');
		$greeting = 'Selamat pagi';

		if ($hour >= 12 && $hour < 17) {
			$greeting = 'Selamat siang';
		} elseif ($hour >= 17 && $hour < 21) {
			$greeting = 'Selamat sore';
		} elseif ($hour >= 21 || $hour < 4) {
			$greeting = 'Selamat malam';
		}

		$modelVisitor = new MyModel('visitor');
		// ---------- Hari Ini ----------
		$today = date('Y-m-d');
		$whereToday = ['DATE(visitDate)' => $today];
		$totalViewsToday = $modelVisitor->getCountAllbyManyWhere($whereToday);
		// ---------- Bulan Ini ----------
		$thisMonth = date('m');
		$thisYear = date('Y');
		$whereMonth = ['MONTH(visitDate)' => $thisMonth, 'YEAR(visitDate)' => $thisYear];
		$totalViewsThisMonth = $modelVisitor->getCountAllbyManyWhere($whereMonth);
		// ---------- Sepanjang Masa ----------
		$totalViewsAllTime = $modelVisitor->getCountAllbyManyWhere([]);


		$modelPengumuman = new MyModel('pengumuman');
		$dataPengumuman = $modelPengumuman->getCountAll('status', 'tampil');
		$modelPosts = new MyModel('posts');
		$dataPosts = $modelPosts->getCountAll('status', 'publish');
		$modelPages = new MyModel('pages');
		$dataPages = $modelPages->getCountAll('status', 'publish');

		return [
			// ===== statistik hari ini ini ===== //
			'viewsToday' => formatAngkaSingkat($totalViewsToday),
			// ===== statistik bulan ini ===== //
			'viewsThisMonth' => formatAngkaSingkat($totalViewsThisMonth),
			// ===== statistik sepanjang masa ===== //
			'viewsAllTime' => formatAngkaSingkat($totalViewsAllTime),
			'totalPengumuman' => $dataPengumuman,
			'totalPosts' => $dataPosts,
			'totalPages' => $dataPages,
			'greeting' => $greeting,
			'nama_user' => $nama,
			'role_id' => $role_id,
		];
	}
}
