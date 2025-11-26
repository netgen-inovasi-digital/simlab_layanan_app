<?php

namespace App\Modules\FileUmum\Models;

use CodeIgniter\Model;

class FileUmumModel extends Model
{
    protected $table = 'file_umum';
    protected $primaryKey = 'file_id';
    protected $useAutoIncrement = true;
    protected $returnType = 'object';
    protected $useSoftDeletes = false;
    protected $protectFields = true;
    protected $allowedFields = [
        'user_id',
        'judul',
        'file_path',
        'deskripsi',
        'status',
        'tanggal',
        'created_at',
        'updated_at'
    ];

    // Dates
    protected $useTimestamps = true;
    protected $dateFormat = 'datetime';
    protected $createdField = 'created_at';
    protected $updatedField = 'updated_at';

    function getAllData()
    {
        return $this->orderBy('file_id', 'DESC')->findAll();
    }

    public function getAllDataByJoinWithOrderLimit($joins = [], $where = [], $orderBy = [], $select = '*', $limit = 10, $offset = 0)
    {
        $this->select($select);

        if (!empty($joins)) {
            foreach ($joins as $table => $condition) {
                $this->join($table, $condition);
            }
        }

        if (!empty($where)) {
            foreach ($where as $key => $value) {
                $this->where($key, $value);
            }
        }

        if (!empty($orderBy)) {
            foreach ($orderBy as $column => $direction) {
                $this->orderBy($column, $direction);
            }
        }

        return $this->findAll($limit, $offset);
    }

    public function getTotalRowsWithJoin($joins = [], $where = [])
    {
        if (!empty($joins)) {
            foreach ($joins as $table => $condition) {
                $this->join($table, $condition);
            }
        }

        if (!empty($where)) {
            foreach ($where as $key => $value) {
                $this->where($key, $value);
            }
        }

        return $this->countAllResults();
    }
}
