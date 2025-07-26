<?php

namespace Modules\Halaman\Controllers;

use App\Controllers\BaseController;

use App\Models\MyModel;
use App\Modules\Halaman\Models\HalamanModel;

class Halaman extends BaseController
{

	public function index()
	{

		return redirect()->to('/');
	}

	public function detail($slug)
	{

		// ===== model posts ===== //
		// Ambil detail posting berdasarkan slug
		$modelHalaman = new HalamanModel('pages p');

		$select = 'p.title, p.id_pages, p.slug, p.konten, u.nama, p.published_at, p.views';

		$join = [
			'users u' => 'u.id_user = p.user_id',
		];
		$where = ['p.slug' => $slug];

		$halaman = $modelHalaman->getOneByJoin($join, $where, $select);

		// --- Hitung view (tanpa refresh berulang dihitung) ---
		$session = session();
		$viewedKey = 'viewed_halaman_' . $halaman->id_pages;

		if (!$session->has($viewedKey)) {
			$model = new MyModel('pages');

			// Tambah 1 ke kolom views
			$model->updateData([
				'views' => $halaman->views + 1
			], 'id_pages', $halaman->id_pages);

			// Simpan juga ke tabel landing_views (untuk statistik harian/bulanan)
			$logModel = new MyModel('page_views');
			$logModel->insertData([
				'page_id' => $halaman->id_pages,
				'viewed_at' => date('Y-m-d H:i:s')
			]);

			// Simpan session supaya tidak dihitung ulang
			$session->set($viewedKey, true);
		}

		$data = [
			'halaman' => $halaman,
			'title' => $halaman->title,
			'content' => 'Modules\Halaman\Views\v_detail_halaman',
		];

		// ===== load data header dan footer ===== //
		$this->layoutHeaderFooter($data);

		return view('website', $data);
	}
}
