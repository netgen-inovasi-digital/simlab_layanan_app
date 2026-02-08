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

		$modelLayananDetil = new MyModel('t_layanan_detil');
		// ---------- Layanan Masuk (Kode Jenis A) ----------
		$totalLayananMasuk = $modelLayananDetil->getCountAll('kode_jenis', 'A');
		// ---------- LHU yang Telah Diterbitkan ----------
		$modelFilesLHU = new MyModel('t_files_lhu');
		$totalLHUDiterbitkan = $modelFilesLHU->getCountAllbyManyWhere([]);
		// ---------- Invoice yang Dikeluarkan ----------
		$modelPembayaran = new MyModel('t_pembayaran');
		$totalInvoice = $modelPembayaran->getCountAllbyManyWhere([]);


		// ---------- Jumlah Layanan Pengujian Sampel ----------
		$modelLayananPengujian = new MyModel('r_layanan_pengujian');
		$totalLayananPengujian = $modelLayananPengujian->getCountAllbyManyWhere([]);
		
		// ---------- Jumlah Pelanggan Terdaftar ----------
		$modelAccountUsers = new MyModel('account_users');
		$totalPelanggan = $modelAccountUsers->getCountAllbyManyWhere([]);
		
		// ---------- Jumlah Pengelola (Manajer Teknis & Penyelia) ----------
		$modelAccount = new MyModel('account');
		$countRole4 = $modelAccount->getCountAllbyManyWhere(['role_id' => 4]);
		$countRole6 = $modelAccount->getCountAllbyManyWhere(['role_id' => 6]);
		$totalPengelola = $countRole4 + $countRole6;

		return [
			// ===== Layanan Masuk (Kode Jenis A) ===== //
			'totalLayananMasuk' => formatAngkaSingkat($totalLayananMasuk),
			// ===== LHU yang Telah Diterbitkan ===== //
			'totalLHUDiterbitkan' => formatAngkaSingkat($totalLHUDiterbitkan),
			// ===== Invoice yang Dikeluarkan ===== //
			'totalInvoice' => formatAngkaSingkat($totalInvoice),
			'totalPengelola' => $totalPengelola,
			'totalLayananPengujian' => $totalLayananPengujian,
			'totalPelanggan' => $totalPelanggan,
			'greeting' => $greeting,
			'nama_user' => $nama,
			'role_id' => $role_id,
		];
	}
}
