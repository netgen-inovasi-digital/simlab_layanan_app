<?php

namespace Modules\layananLab\Models;

use App\Models\MyModel;

/**
 * LayananLabModel
 * Handle operasi tabel r_layanan_pengujian
 * Module model - khusus untuk modul layananLab
 */
class LayananLabModel extends MyModel
{
    protected $table = 'r_layanan_pengujian';
    protected $primaryKey = 'kode';

    public function __construct()
    {
        parent::__construct($this->table);
    }

    // Ambil layanan dengan join jenis, alat, parameter
    public function getLayananWithDetails($where = [])
    {
        $join = [
            'r_jenis j'     => 'j.kode = ' . $this->table . '.kode_jenis',
            'r_alat a'      => 'a.kode = ' . $this->table . '.kode_alat',
            'r_parameter p' => 'p.kode = ' . $this->table . '.kode_parameter',
        ];

        $select = $this->table . '.*, 
            j.kode, j.nama, 
            a.nama, 
            p.nama';

        return $this->getAllDataWithJoinWhereOrder($join, $where, [], $select, 'left');
    }

    // Cek duplikat kombinasi alat & parameter
    public function isDuplicateCombination($kodeAlat, $kodeParameter, $excludeId = null)
    {
        $where = [
            'kode_alat' => $kodeAlat,
            'kode_parameter' => $kodeParameter
        ];

        if ($excludeId !== null) {
            $where[$this->primaryKey . ' !='] = $excludeId;
        }

        $result = $this->getWhere($where)->getRow();
        return $result !== null;
    }

    // Insert layanan, return ID
    public function insertLayanan($data)
    {
        return $this->insertData($data, true);
    }

    // Update layanan
    public function updateLayanan($data, $id)
    {
        return $this->updateData($data, $this->primaryKey, $id);
    }

    // Hapus layanan
    public function deleteLayanan($id)
    {
        return $this->deleteData($this->primaryKey, $id);
    }
}
