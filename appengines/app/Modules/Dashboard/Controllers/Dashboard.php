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
		$user_id = $session->get('id_user');

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

		// ---------- Progress Status Layanan dari t_layanan ----------
		$modelLayanan = new MyModel('t_layanan');
		$statusInReviewManajer = $modelLayanan->getCountAll('status_layanan', 1);
		$statusInReviewAdmin = $modelLayanan->getCountAll('status_layanan', 3);
		$statusPengujian = $modelLayanan->getCountAll('status_layanan', 4);
		$statusMemprosesLHUS = $modelLayanan->getCountAll('status_layanan', 5);
		$statusMemprosesLHU = $modelLayanan->getCountAll('status_layanan', 6);
		$statusSelesai = $modelLayanan->getCountAll('status_layanan', 9);
		
		// ---------- Penerbitan Invoice (no_invoice kosong/null) ----------
		$statusPenerbitanInvoice = $modelPembayaran->getCountAllbyManyWhere(['no_invoice' => null]);
		
		// ---------- Verifikasi Pembayaran (bukti_bayar ada, status_bayar = 0) ----------
		$statusVerifikasiPembayaran = $modelPembayaran->getCountAllbyManyWhere(['bukti_bayar IS NOT NULL' => null, 'status_bayar' => 0]);
		
		// ---------- Jumlah Kaji Ulang ----------
		$totalKajiUlang = $modelLayanan->getCountAllbyManyWhere(['jumlah_kaji_ulang >' => 0]);

		// ---------- Progress Status Manajer Teknis (role_id = 4) ----------
		$mtBelumReview = 0;
		$mtTerkirimKeAdmin = 0;
		$mtLhusDitolak = 0;
		$mtLhusBelumTinjau = 0;
		$mtLhusDiprosesKembali = 0;
		$mtLhusDisetujui = 0;

		if ($role_id == 4) {
			// Card 1: Layanan belum direview (detail pending, layanan in review)
			$modelDetil1 = new MyModel('t_layanan_detil');
			$belumReviewRows = $modelDetil1->getAllDataWithJoinWhereOrder(
				[
					'r_tim' => 'r_tim.uji_kode = t_layanan_detil.uji_kode',
					't_layanan' => 't_layanan.kode_layanan = t_layanan_detil.kode_layanan'
				],
				[
					'r_tim.user_id' => $user_id,
					't_layanan_detil.status_layanan' => 0,
					't_layanan.status_layanan' => 1
				],
				[],
				't_layanan_detil.kode_layanan'
			);
			$belumReviewKodes = array_unique(array_map(fn($r) => $r->kode_layanan, $belumReviewRows));
			$mtBelumReview = count($belumReviewKodes);

			// Card 2: Layanan terkirim ke admin (semua item sudah direview)
			$modelDetil2 = new MyModel('t_layanan_detil');
			$allAssignedRows = $modelDetil2->getAllDataWithJoinWhereOrder(
				[
					'r_tim' => 'r_tim.uji_kode = t_layanan_detil.uji_kode',
					't_layanan' => 't_layanan.kode_layanan = t_layanan_detil.kode_layanan'
				],
				[
					'r_tim.user_id' => $user_id,
					't_layanan.status_layanan >=' => 1,
					't_layanan.status_layanan !=' => 2,
				],
				[],
				't_layanan_detil.kode_layanan'
			);
			$allKodes = array_unique(array_map(fn($r) => $r->kode_layanan, $allAssignedRows));
			$mtTerkirimKeAdmin = count(array_diff($allKodes, $belumReviewKodes));

			// Cards 3-6: TinjauLHUS logic — derivedStatus per layanan
			// Fetch all detail records for this user where detail accepted (status_layanan=1) and parent status >= 5
			$modelDetilLhus = new MyModel('t_layanan_detil');
			$lhusRows = $modelDetilLhus->getAllDataWithJoinWhereOrder(
				[
					'r_tim' => 'r_tim.uji_kode = t_layanan_detil.uji_kode',
					't_layanan' => 't_layanan.kode_layanan = t_layanan_detil.kode_layanan'
				],
				[
					'r_tim.user_id' => $user_id,
					't_layanan_detil.status_layanan' => 1,
					't_layanan.status_layanan >=' => 5,
				],
				[],
				't_layanan_detil.kode_layanan, t_layanan_detil.files'
			);

			// Group by kode_layanan and compute summary (same as TinjauLHUS getUserStatusSummary)
			$lhusSummary = [];
			foreach ($lhusRows as $r) {
				$kode = $r->kode_layanan;
				if (!isset($lhusSummary[$kode])) {
					$lhusSummary[$kode] = ['total' => 0, 'cnt0' => 0, 'cnt1' => 0, 'cnt2' => 0];
				}
				$files = (int) ($r->files ?? 0);
				$lhusSummary[$kode]['total']++;
				if ($files === 1) $lhusSummary[$kode]['cnt1']++;
				elseif ($files === 2) $lhusSummary[$kode]['cnt2']++;
				else $lhusSummary[$kode]['cnt0']++; // files=0 or files=3 → pending
			}

			// Derive status per layanan (same logic as TinjauLHUS controller derivedStatus)
			foreach ($lhusSummary as $s) {
				if ($s['total'] === 0) continue;

				if ($s['cnt0'] > 0) {
					$mtLhusBelumTinjau++;       // derivedStatus = 5: masih ada pending
				} elseif ($s['cnt1'] === $s['total']) {
					$mtLhusDisetujui++;         // derivedStatus = 6: semua diterima
				} elseif ($s['cnt2'] > 0) {
					$mtLhusDiprosesKembali++;   // derivedStatus = 2: semua ditinjau, ada ditolak
				}

				// LHUS ditolak: jumlah item detail yang ditolak (files=2)
				$mtLhusDitolak += $s['cnt2'];
			}
		}

		// ---------- Progress Status Penyelia (role_id = 6) ----------
		$pySedangPengujian = 0;
		$pyLhusTerunggah = 0;
		$pyLhusVerifikasi = 0;
		$pyLhusDisetujui = 0;
		$pyLhusDitolak = 0;

		if ($role_id == 6) {
			// Fetch all detail records for this penyelia (status_layanan=1, parent >= 4)
			$modelDetilPy = new MyModel('t_layanan_detil');
			$pyRows = $modelDetilPy->getAllDataWithJoinWhereOrder(
				[
					'r_tim' => 'r_tim.uji_kode = t_layanan_detil.uji_kode',
					't_layanan' => 't_layanan.kode_layanan = t_layanan_detil.kode_layanan'
				],
				[
					'r_tim.user_id' => $user_id,
					't_layanan_detil.status_layanan' => 1,
					't_layanan.status_layanan >=' => 4,
					't_layanan.status_layanan !=' => 2,
				],
				[],
				't_layanan_detil.kode_layanan, t_layanan_detil.files, t_layanan.status_layanan AS parent_status'
			);

			// Group by kode_layanan
			$pySummary = [];
			foreach ($pyRows as $r) {
				$kode = $r->kode_layanan;
				if (!isset($pySummary[$kode])) {
					$pySummary[$kode] = [
						'total' => 0,
						'files_null' => 0,  // NULL = belum upload
						'files_0' => 0,     // 0 = terkirim ke manajer
						'files_1' => 0,     // 1 = diterima/disetujui
						'files_2' => 0,     // 2 = ditolak
						'files_3' => 0,     // 3 = terunggah belum kirim
						'parent_status' => (int) ($r->parent_status ?? 4),
					];
				}
				$pySummary[$kode]['total']++;
				$files = $r->files;
				if ($files === null) $pySummary[$kode]['files_null']++;
				elseif ((int)$files === 0) $pySummary[$kode]['files_0']++;
				elseif ((int)$files === 1) $pySummary[$kode]['files_1']++;
				elseif ((int)$files === 2) $pySummary[$kode]['files_2']++;
				elseif ((int)$files === 3) $pySummary[$kode]['files_3']++;
			}

			// Derive status per layanan using same priority as formatStatusForPenyelia
			foreach ($pySummary as $s) {
				if ($s['total'] === 0) continue;

				// Priority 1: ada files=2 → LHUS ditolak
				if ($s['files_2'] > 0) {
					$pyLhusDitolak++;
					continue;
				}
				// Priority 2: ada files=3 → LHUS terunggah
				if ($s['files_3'] > 0) {
					$pyLhusTerunggah++;
					continue;
				}
				// Priority 3: semua files=1 → LHUS disetujui
				if ($s['files_1'] === $s['total']) {
					$pyLhusDisetujui++;
					continue;
				}
				// Priority 4: ada files=0 → LHUS diverifikasi manajer (terkirim)
				if ($s['files_0'] > 0) {
					$pyLhusVerifikasi++;
					continue;
				}
				// Fallback: masih ada files NULL → sedang dalam pengujian
				if ($s['files_null'] > 0 && $s['parent_status'] == 4) {
					$pySedangPengujian++;
				}
			}
		}

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
			// ===== Progress Status Layanan ===== //
			'statusInReviewManajer' => $statusInReviewManajer,
			'statusInReviewAdmin' => $statusInReviewAdmin,
			'statusPenerbitanInvoice' => $statusPenerbitanInvoice,
			'statusVerifikasiPembayaran' => $statusVerifikasiPembayaran,
			'statusPengujian' => $statusPengujian,
			'statusMemprosesLHUS' => $statusMemprosesLHUS,
			'statusMemprosesLHU' => $statusMemprosesLHU,
			'statusSelesai' => $statusSelesai,
			'totalKajiUlang' => $totalKajiUlang,
			'greeting' => $greeting,
			'nama_user' => $nama,
			'role_id' => $role_id,
			// ===== Manajer Teknis Dashboard (role_id = 4) ===== //
			'mtBelumReview' => $mtBelumReview,
			'mtTerkirimKeAdmin' => $mtTerkirimKeAdmin,
			'mtLhusDitolak' => $mtLhusDitolak,
			'mtLhusBelumTinjau' => $mtLhusBelumTinjau,
			'mtLhusDiprosesKembali' => $mtLhusDiprosesKembali,
			'mtLhusDisetujui' => $mtLhusDisetujui,
			// ===== Penyelia Dashboard (role_id = 6) ===== //
			'pySedangPengujian' => $pySedangPengujian,
			'pyLhusTerunggah' => $pyLhusTerunggah,
			'pyLhusVerifikasi' => $pyLhusVerifikasi,
			'pyLhusDisetujui' => $pyLhusDisetujui,
			'pyLhusDitolak' => $pyLhusDitolak,
		];
	}
}
