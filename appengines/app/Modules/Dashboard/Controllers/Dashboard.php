<?php

namespace Modules\Dashboard\Controllers;

use App\Controllers\BaseController;
use App\Models\MyModel;

class Dashboard extends BaseController
{
	public function index()
	{
<<<<<<< HEAD
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
=======

		// ====== user ===== //
		$session = session(); // aktifkan session
		$nama = $session->get('nama');
>>>>>>> df8c327176c0d2352c9b643155da517b0816f615

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

<<<<<<< HEAD
		$modelVisitor = new MyModel('visitor');
=======
		// ===== model Visitor ===== //

		$modelVisitor = new MyModel('visitor');

>>>>>>> df8c327176c0d2352c9b643155da517b0816f615
		// ---------- Hari Ini ----------
		$today = date('Y-m-d');
		$whereToday = ['DATE(visitDate)' => $today];
		$totalViewsToday = $modelVisitor->getCountAllbyManyWhere($whereToday);
		// ---------- Bulan Ini ----------
		$thisMonth = date('m');
		$thisYear = date('Y');
<<<<<<< HEAD
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
=======
		$whereMonth = [
			'MONTH(visitDate)' => $thisMonth,
			'YEAR(visitDate)' => $thisYear
		];
		$totalViewsThisMonth = $modelVisitor->getCountAllbyManyWhere($whereMonth);

		// ---------- Sepanjang Masa ----------
		$whereAllTime = [];
		$totalViewsAllTime = $modelVisitor->getCountAllbyManyWhere($whereAllTime);

		// ---------- data --------- 
		$modelPengumuman = new MyModel('pengumuman');
		$dataPengumuman = $modelPengumuman->getCountAll('status', 'tampil');
		$modelPosts   = new MyModel('posts');
		$dataPosts = $modelPosts->getCountAll('status', 'publish');
		$modelPages   = new MyModel('pages');
		$dataPages = $modelPages->getCountAll('status', 'publish');

		$data = [
			'title' => 'Dashboard',
			// ===== statistik hari ini ===== //
			'viewsToday' => formatAngkaSingkat($totalViewsToday),
			// ===== statistik bulan ini ===== //
			'viewsThisMonth' => formatAngkaSingkat($totalViewsThisMonth),
			// ===== statistik hari ini ===== //
>>>>>>> df8c327176c0d2352c9b643155da517b0816f615
			'viewsAllTime' => formatAngkaSingkat($totalViewsAllTime),
			'totalPengumuman' => $dataPengumuman,
			'totalPosts' => $dataPosts,
			'totalPages' => $dataPages,
			'greeting' => $greeting,
<<<<<<< HEAD
			'nama_user' => $nama,
			'role_id' => $role_id,
		];
=======
			'nama_user' => $nama,  
			'content' => 'Modules\Dashboard\Views\v_dashboard'
		];
		return view('template', $data);
	}

	public function load()
	{
		// ====== user ===== //
		$session = session(); // aktifkan session
		$nama = $session->get('nama');

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
		// ===== model visitor ===== //
		$modelVisitor = new MyModel('visitor');

		// ---------- Hari Ini ----------
		$today = date('Y-m-d');
		$whereToday = ['DATE(visitDate)' => $today];
		$totalViewsToday =
			$modelVisitor->getCountAllbyManyWhere($whereToday);
		// ---------- Bulan Ini ----------
		$thisMonth = date('m');
		$thisYear = date('Y');
		$whereMonth = [
			'MONTH(visitDate)' => $thisMonth,
			'YEAR(visitDate)' => $thisYear
		];
		$totalViewsThisMonth =
			$modelVisitor->getCountAllbyManyWhere($whereMonth);

		// ---------- Sepanjang Masa ----------
		$whereAllTime = [];
		$totalViewsAllTime =
			$modelVisitor->getCountAllbyManyWhere($whereAllTime);

		$modelPengumuman = new MyModel('pengumuman');
		$dataPengumuman = $modelPengumuman->getCountAll('status', 'tampil');
		$modelPosts   = new MyModel('posts');
		$dataPosts = $modelPosts->getCountAll('status', 'publish');
		$modelPages   = new MyModel('pages');
		$dataPages = $modelPages->getCountAll('status', 'publish');

		$data = [
			'title' => 'Dashboard',
			// ===== statistik hari ini ===== //
			'viewsToday' => formatAngkaSingkat($totalViewsToday),
			// ===== statistik bulan ini ===== //
			'viewsThisMonth' => formatAngkaSingkat($totalViewsThisMonth),
			// ===== statistik hari ini ===== //
			'viewsAllTime' => formatAngkaSingkat($totalViewsAllTime),
			'totalPengumuman' => $dataPengumuman,
			'totalPosts' => $dataPosts,
			'totalPages' => $dataPages,
			'greeting' => $greeting,
			'nama_user' => $nama, 
		];
		return view('Modules\Dashboard\Views\v_dashboard', $data);
>>>>>>> df8c327176c0d2352c9b643155da517b0816f615
	}
}
