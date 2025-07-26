<?php

namespace App\Controllers;

use App\Models\MyModel;
use App\Modules\Berita\Models\BeritaModel;
use App\Libraries\VisitorLogger;


class Website extends BaseController
{

  public function __construct()
{
	VisitorLogger::logVisit();
}

  public function index()
  {
    // ===== model untuk hero ===== //
    $modelHero = new MyModel('hero');
    $where = [
      'status' => 'Y',
    ];

    $order = [
      'urutan' => 'ASC'
    ];

    $dataHero = $modelHero->getAllDataById($where, $order);

    // ===== model untuk team ===== //
    $modelTeam = new MyModel('team');

    $where = [
      'status' => 'Y',
    ];

    $order = [
      'urutan' => 'ASC'
    ];

    $dataTeam = $modelTeam->getAllDataById($where, $order);


    // ===== model untuk layanan ===== //
    $modelLayanan = new MyModel('layanan');

    $where = [
      'status' => 'Y',
    ];

    $order = [
      'urutan' => 'ASC'
    ];

    $dataLayanan = $modelLayanan->getAllDataById($where, $order);

    // ===== model untuk berita ===== //
    $modelBerita = new BeritaModel('posts p');

    $select = 'p.title, p.slug as post_slug, p.konten, p.thumbnail, p.status, p.updated_at, u.nama as nama_user, c.nama, c.slug as category_slug, p.views';

    $join = [
      'users u' => 'u.id_user = p.user_id',
      'categories c' => 'c.id_categories = p.categories_id',
    ];
    $where = [
      'status' => 'publish',
    ];

    $orderBy = ['p.published_at' => 'DESC'];
    $dataBerita = $modelBerita->getAllDataByJoinWithOrderLimit($join, $where, $orderBy, $select, 4);

    // ===== model untuk pengumuman ===== //
    $modelPengumuman = new MyModel('pengumuman');
    $dataPengumuman = $modelPengumuman->getAllDataWhereLimit(['status' => 'tampil'], 'tanggal', 'desc', 3, 0);

    // ===== model untuk mitra ===== //
    $modelMitra = new MyModel('mitra');
    $where = [
      'status' => 'Y',
    ];

    $order = [
      'urutan' => 'ASC'
    ];

    $dataMitra = $modelMitra->getAllDataById($where, $order);

    // --- Hitung view (tanpa refresh berulang dihitung) ---
    $modelLanding = new MyModel('landing_views');
    $where = [
      'id_landing_views' => 1
    ];

    $order = [
    ];

    $dataLanding = $modelLanding->getDataById($where, $order);

    // ===== model Layout ===== //
    $modelLayout = new MyModel('layout');
    $dataLayout = $modelLayout->getAllDataById(['status' => 'Y'], ['urutan' => 'ASC']);

		$session = session();
		$viewedKey = 'viewed_landing_' . $dataLanding->id_landing_views;

		if (!$session->has($viewedKey)) {
			// Simpan juga ke tabel landing_views (untuk statistik harian/bulanan)
			$logModel = new MyModel('landing_views');
			$logModel->insertData([
				'viewed_at' => date('Y-m-d H:i:s')
			]);
			// Simpan session supaya tidak dihitung ulang
			$session->set($viewedKey, true);
		}

    $data = [
      'getHero' => $dataHero,
      'getTeam' => $dataTeam,
      'getLayanan' => $dataLayanan,
      'getBerita' => $dataBerita,
      'getPengumuman' => $dataPengumuman,
      'getMitra' => $dataMitra,
      'getLayout' => $dataLayout,
      'title' => 'Klinik Medikidz',
      'content' => 'Modules\Landing\Views\v_landing'
    ];


    // ===== load data header dan footer ===== //
    $this->layoutHeaderFooter($data);

    return view('website', $data);
  }
}
