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
    $rows = $this->responseModel->getResponseList();

    if (empty($rows)) {
      return $this->response->setJSON(['items' => []]);
    }

    $data = [];
    foreach ($rows as $row) {
      $encId = bin2hex($this->encrypter->encrypt($row->lnKode));

      $namaLayanan = $row->layanan_nama ?: '-';
      $noTransaksi = $row->lnNoTransaksi ?: '-';
      $colLayanan = '<div class="fw-semibold">' . esc($namaLayanan) . '</div>' .
        '<small class="text-muted">No. Invoice: ' . esc($noTransaksi) . '</small>';

      $data[] = [
        $colLayanan,
        $this->buildPemesanColumn($row),
        $this->buildActionButton($encId)
      ];
    }

    return $this->response->setJSON(['items' => $data]);
  }

  public function detail($id = null)
  {
    if (!$id) {
      return $this->response->setJSON(['success' => false, 'msg' => 'ID tidak valid']);
    }

    try {
      $lnKode = $this->encrypter->decrypt(hex2bin($id));
    } catch (\Throwable $e) {
      try {
        $lnKode = $this->encrypter->decrypt($id);
      } catch (\Throwable $e2) {
        return $this->response->setJSON(['success' => false, 'msg' => 'ID tidak valid']);
      }
    }

    $header = $this->responseModel->getResponseHeader($lnKode);

    if (!$header) {
      return $this->response->setJSON(['success' => false, 'msg' => 'Data layanan tidak ditemukan']);
    }

    $rows = $this->responseModel->getQuestionResponses($lnKode);

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
        'no_transaksi' => $header->lnNoTransaksi ?? '-',
        'pemesan' => $header->user_name ?? '-'
      ],
      'items' => $items
    ]);
  }

  private function buildPemesanColumn($row): string
  {
    $pemesanNama = $row->user_name ?: ($row->user_email ?? $row->lnAccEmail ?? '-');
    $tanggal = !empty($row->lnTgl) ? date('d-m-Y H:i', strtotime($row->lnTgl)) : '-';
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
