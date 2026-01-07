<?php

namespace Modules\KuisionerResponse\Controllers;

use App\Controllers\BaseController;
use Modules\KuisionerResponse\Models\KuisionerResponseModel;

class KuisionerResponse extends BaseController
{
  protected $encrypter;
  protected $responseModel;

  public function __construct()
  {
    $this->encrypter = service('encrypter');
    $this->responseModel = new KuisionerResponseModel();
  }

  public function index()
  {
    $data = [
      'title' => 'Hasil Kuisioner Pelanggan'
    ];

    return view('Modules\\KuisionerResponse\\Views\\v_response_kuisioner', $data);
  }

  public function dataList()
  {
    try {
      // Get pagination parameters
      $page = $this->request->getGet('page') ?? 1;
      $limit = $this->request->getGet('limit') ?? 10;
      $search = $this->request->getGet('search') ?? '';
      
      $rows = $this->responseModel->getResponseList();

      if (empty($rows)) {
        return $this->response->setJSON([
          'items' => [],
          'total' => 0,
          'page' => (int)$page,
          'limit' => (int)$limit
        ]);
      }

      $data = [];
      foreach ($rows as $row) {
        $encId = bin2hex($this->encrypter->encrypt($row->kode_layanan));

        $namaLayanan = $row->layanan_nama ?: '-';
        $noTransaksi = $row->no_invoice ?: '-';
        $colLayanan = '<div class="fw-semibold">' . esc($namaLayanan) . '</div>' .
          '<small class="text-muted">No. Invoice: ' . esc($noTransaksi) . '</small>';

        $data[] = [
          $colLayanan,
          $this->buildPemesanColumn($row),
          $this->buildActionButton($encId)
        ];
      }

      $total = count($data);

      return $this->response->setJSON([
        'items' => $data,
        'total' => $total,
        'page' => (int)$page,
        'limit' => (int)$limit
      ]);
    } catch (\Exception $e) {
      log_message('error', 'Error in KuisionerResponse::dataList - ' . $e->getMessage());
      return $this->response->setStatusCode(500)->setJSON([
        'items' => [],
        'total' => 0,
        'error' => 'Terjadi kesalahan saat mengambil data. Silakan coba lagi.'
      ]);
    }
  }

  public function detail($id = null)
  {
    try {
      if (!$id) {
        return $this->response->setStatusCode(400)->setJSON([
          'success' => false, 
          'msg' => 'ID tidak valid'
        ]);
      }

      try {
        $kode_layanan = $this->encrypter->decrypt(hex2bin($id));
      } catch (\Throwable $e) {
        try {
          $kode_layanan = $this->encrypter->decrypt($id);
        } catch (\Throwable $e2) {
          return $this->response->setStatusCode(400)->setJSON([
            'success' => false, 
            'msg' => 'ID tidak valid'
          ]);
        }
      }

      $header = $this->responseModel->getResponseHeader($kode_layanan);

      if (!$header) {
        return $this->response->setStatusCode(404)->setJSON([
          'success' => false, 
          'msg' => 'Data layanan tidak ditemukan'
        ]);
      }

      $rows = $this->responseModel->getQuestionResponses($kode_layanan);

      $items = [];
      foreach ($rows as $index => $row) {
        $items[] = [
          'no' => $index + 1,
          'pertanyaan' => $row->pertanyaan_teks,
          'tipe' => $row->pertanyaan_tipe,
          'jawaban' => $row->jawaban ?? '-',
          'created_at' => $row->created_at
        ];
      }

      return $this->response->setJSON([
        'success' => true,
        'meta' => [
          'no_transaksi' => $header->no_invoice ?? '-',
          'pemesan' => $header->user_name ?? '-'
        ],
        'items' => $items
      ]);
    } catch (\Exception $e) {
      log_message('error', 'Error in KuisionerResponse::detail - ' . $e->getMessage());
      return $this->response->setStatusCode(500)->setJSON([
        'success' => false,
        'msg' => 'Terjadi kesalahan saat mengambil data. Silakan coba lagi.'
      ]);
    }
  }

  private function buildPemesanColumn($row): string
  {
    $pemesanNama = $row->user_name ?: ($row->user_email ?? $row->user_email ?? '-');
    $tanggal = !empty($row->tanggal_checkout) ? date('d-m-Y H:i', strtotime($row->tanggal_checkout)) : '-';
    $identity = strtoupper($row->user_identity ?? 'NON ULM');

    return '<div style="line-height:1.3;">'
      . '<span class="fw-semibold">' . esc($pemesanNama) . '</span><br>'
      . '<small class="text-muted">' . esc($tanggal) . ' | ' . esc($identity) . '</small>'
      . '</div>';
  }

  private function buildActionButton(string $encId): string
  {
    return '<button type="button" class="btn btn-sm btn-outline-primary" '
      . 'onclick="showResponseModal(\'' . $encId . '\')">'
      . '<i class="bi bi-eye"></i> Lihat</button>';
  }
}
