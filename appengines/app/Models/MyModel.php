<?php

namespace App\Models;

use CodeIgniter\Model;

class MyModel extends Model
{
  public function __construct($table)
  {
    parent::__construct();
    $db = \Config\Database::connect();
    $this->builder = $db->table($table);
  }

  // ===== mengambil semua data ===== //
  public function getAllData($order = "", $asc = "")
  {
    if ($order != "")
      $this->builder->orderBy($order, $asc);
    return $this->builder->get()->getResult();
  }

  // ===== mengambil semua data tapi ada limit ===== //
  public function getAllDataLimit($order = "", $asc = "", $limit = null, $offset = 0)
  {
    if ($order != "") {
      $this->builder->orderBy($order, $asc);
    }

    if ($limit !== null) {
      $this->builder->limit($limit, $offset);
    }

    return $this->builder->get()->getResult();
  }

  // ===== mengambil semua data dengan filter dan limit ===== //
  public function getAllDataWhereLimit($where = [], $order = "", $asc = "", $limit = null, $offset = 0)
  {
    if (!empty($where)) {
      foreach ($where as $key => $val) {
        $this->builder->where($key, $val);
      }
    }

    if ($order != "") {
      $this->builder->orderBy($order, $asc);
    }

    if ($limit !== null) {
      $this->builder->limit($limit, $offset);
    }

    return $this->builder->get()->getResult();
  }


  // ===== mengambil semua data dengan urutan ===== //
  public function getAllDataWithOrder($orders = [])
  {
    if (!empty($orders)) {
      foreach ($orders as $order => $sort) {
        $this->builder->orderBy($order, $sort);
      }
    }
    return $this->builder->get()->getResult();
  }

  // ===== mengambil data dengan where yang banyak
  public function getDataByWhere(array $where)
  {
    foreach ($where as $key => $value) {
      if (strpos($key, ' ') !== false) {
        // Jika ada operator (seperti expired_at >=)
        [$field, $operator] = explode(' ', $key, 2);
        $this->builder->where($field . ' ' . $operator, $value);
      } else {
        $this->builder->where($key, $value);
      }
    }

    return $this->builder->get()->getRow();
  }

  // ===== mengambil semua data sesuai filter where dan juga urutan ===== //
  public function getAllDataById($where, $orders = [])
  {
    if ($where) {
      foreach ($where as $key => $value) {
        $this->builder->where($key, $value);
      }
    }
    if (!empty($orders)) {
      foreach ($orders as $order => $sort) {
        $this->builder->orderBy($order, $sort);
      }
    }
    return $this->builder->get()->getResult();
  }

  // ===== mengambil semua data dengan filter where dan urutan (alias untuk compatibility) ===== //
  public function getAllDataByWhereWithOrder($where = [], $orders = [], $select = '*')
  {
    $this->builder->select($select);

    if (!empty($where)) {
      foreach ($where as $key => $value) {
        $this->builder->where($key, $value);
      }
    }

    if (!empty($orders)) {
      foreach ($orders as $order => $sort) {
        $this->builder->orderBy($order, $sort);
      }
    }

    return $this->builder->get()->getResult();
  }

  // ===== Ambil data lengkap dengan JOIN, WHERE (support array), ORDER BY, dan SELECT custom ===== //
  public function getAllDataWithJoinWhereOrder(
    array $joins = [],
    array $where = [],
    array $orders = [],
    string $select = '*',
    string $joinType = 'inner'
  ): array {
    $this->builder->select($select);

    // Handle JOIN
    if (!empty($joins)) {
      foreach ($joins as $table => $condition) {
        $this->builder->join($table, $condition, $joinType);
      }
    }

    // Handle WHERE dan whereIn
    if (!empty($where)) {
      foreach ($where as $key => $value) {
        if (is_array($value)) {
          $this->builder->whereIn($key, $value);
        } else {
          $this->builder->where($key, $value);
        }
      }
    }

    // Handle ORDER BY
    if (!empty($orders)) {
      foreach ($orders as $order => $sort) {
        $this->builder->orderBy($order, $sort);
      }
    }

    return $this->builder->get()->getResult();
  }



  // ===== mencari kolom yang berbeda ===== //
  public function getDistinct($col)
  {
    $this->builder->select($col)->distinct();
    return $this->builder->get()->getResult();
  }

  // ===== mengambil data sesuai filter ===== //
  public function getDataById($where, $id)
  {
    $this->builder->where($where, $id);
    return $this->builder->get()->getRow();
  }

  // ===== mengambil data array sesuai filter ===== //
  public function getDataByArray($where)
  {
    if ($where) {
      foreach ($where as $key => $value) {
        if (is_array($value)) {
          $this->builder->whereIn($key, $value);
        } else {
          $this->builder->where($key, $value);
        }
      }
    }
    return $this->builder->get()->getRow();
  }

  // ===== mengambil semua data dengan join tabel dan sesuai filter where ==== //
  public function getAllDataByJoin($joins = [], $where = [])
  {
    if (!empty($joins)) {
      foreach ($joins as $table => $condition) {
        $this->builder->join($table, $condition);
      }
    }

    if (!empty($where)) {
      foreach ($where as $key => $value) {
        $this->builder->where($key, $value);
      }
    }

    return $this->builder->get()->getResult();
  }

  // ===== mengambil semua data dengan join tabel dan urutan dan juga like ===== //
  public function getAllDataByJoinWithOrder($joins = [], $where = [], $orderBy = [], $select = '*', $joinType = 'inner', $like = [])
  {
    $this->builder->select($select);

    if (!empty($joins)) {
      foreach ($joins as $table => $condition) {
        $this->builder->join($table, $condition, $joinType);
      }
    }

    if (!empty($where)) {
      foreach ($where as $key => $value) {
        $this->builder->where($key, $value);
      }
    }

    if (!empty($like)) {
      foreach ($like as $key => $value) {
        // LIKE AFTER: cocokkan nilai yang diawali dengan $value
        $this->builder->like($key, $value, 'after');
      }
    }

    if (!empty($orderBy)) {
      foreach ($orderBy as $column => $direction) {
        $this->builder->orderBy($column, $direction);
      }
    }

    return $this->builder->get()->getResult();
  }


  // ===== mengambil semua data dengan join tabel, urutan, dan limit ===== //
  public function getAllDataByJoinWithOrderLimit($joins = [], $where = [], $orderBy = [], $select = '*', $limit = 10, $offset = 0, $joinType = 'inner')
  {
    $this->builder->select($select);

    if (!empty($joins)) {
      foreach ($joins as $table => $condition) {
        $this->builder->join($table, $condition, $joinType);
      }
    }

    if (!empty($where)) {
      foreach ($where as $key => $value) {
        if (strpos($key, 'LIKE') !== false) {
          $this->builder->like(str_replace(' LIKE', '', $key), $value);
        } elseif (strpos($key, ' IN') !== false) {
          $this->builder->whereIn(str_replace(' IN', '', $key), $value);
        } else {
          $this->builder->where($key, $value);
        }
      }
    }

    if (!empty($orderBy)) {
      foreach ($orderBy as $column => $direction) {
        $this->builder->orderBy($column, $direction);
      }
    }

    $this->builder->limit($limit, $offset);
    return $this->builder->get()->getResult();
  }

  // ===== mengambil satu data dengan join ===== //
  public function getOneByJoin($joins = [], $where = [], $select = '*', $joinType = 'inner')
  {
    $this->builder->select($select);

    if (!empty($joins)) {
      foreach ($joins as $table => $condition) {
        $this->builder->join($table, $condition, $joinType);
      }
    }

    if (!empty($where)) {
      foreach ($where as $key => $value) {
        $this->builder->where($key, $value);
      }
    }

    return $this->builder->get()->getRow();
  }

  // ===== menghitung total rows dengan join ===== //
  public function getTotalRowsWithJoin($joins = [], $where = [])
  {
    if (!empty($joins)) {
      foreach ($joins as $table => $condition) {
        $this->builder->join($table, $condition);
      }
    }

    if (!empty($where)) {
      foreach ($where as $key => $value) {
        if (strpos($key, 'LIKE') !== false) {
          $this->builder->like(str_replace(' LIKE', '', $key), $value);
        } elseif (strpos($key, ' IN') !== false) {
          $this->builder->whereIn(str_replace(' IN', '', $key), $value);
        } else {
          $this->builder->where($key, $value);
        }
      }
    }

    return $this->builder->countAllResults();
  }

  // ===== mengambil data ID yang paling besar ===== //
  public function getMaxId($select, $where = "", $id = "")
  {
    $this->builder->selectMax($select, 'idMax');
    if ($where !== "")
      $this->builder->where($where, $id);
    return $this->builder->get()->getRow();
  }

  // ===== mengambil data ID yang paling besar dengan bentuk array ===== //
  public function getMaxIdByArray($select, $where)
  {
    $this->builder->selectMax($select, 'idMax');
    if ($where) {
      foreach ($where as $key => $value) {
        $this->builder->where($key, $value);
      }
    }
    return $this->builder->get()->getRow();
  }

  // ===== menghitung jumlah baris sesuai filter 1 where ===== //
  public function getCountAll($where, $id)
  {
    $this->builder->where($where, $id);
    return $this->builder->countAllResults();
  }

  // ===== menghitung jumlah baris tetapi dengan where yang banyak ===== //
  public function getCountAllbyManyWhere($where = [])
  {
    foreach ($where as $key => $value) {
      $this->builder->where($key, $value);
    }

    return $this->builder->countAllResults();
  }


  // ===== menghitung jumlah baris sesuai filter dalam bentuk array ===== // 
  public function getCountAllByArray($where)
  {
    if ($where) {
      foreach ($where as $key => $value) {
        $this->builder->where($key, $value);
      }
    }
    return $this->builder->countAllResults();
  }


  public function checkPassword($password)
  {
    $username = session()->get('username');

    $this->builder->select('password');
    $this->builder->where('username', $username);
    $get = $this->builder->get()->getRow();
    $hash = $get->password;
    $verify_pass = password_verify($password, $hash);
    return $verify_pass;
  }

  public function insertData($data, $lastID = false)
  {
    $this->db->transBegin();
    $this->builder->insert($data);
    if ($this->db->transStatus() === FALSE) {
      $this->db->transRollback();
      return false;
    } else {
      if ($lastID == false)
        $return = true;
      else
        $return = $this->db->insertID();

      $this->db->transCommit();
      return $return;
    }
  }

  public function insertDataBatch($data)
  {
    $this->db->transBegin();
    $this->builder->insertBatch($data);
    if ($this->db->transStatus() === FALSE) {
      $this->db->transRollback();
      return false;
    } else {
      $this->db->transCommit();
      return true;
    }
  }

  function updateData($data, $where = "", $id = "")
  {
    $this->db->transBegin();
    if ($where != "")
      $this->builder->where($where, $id);

    $this->builder->update($data);
    if ($this->db->transStatus() === FALSE) {
      $this->db->transRollback();
      return false;
    } else {
      $this->db->transCommit();
      return true;
    }
  }

  public function updateDataBatch($data, $key)
  {
    $this->db->transBegin();
    $this->builder->updateBatch($data, $key);
    if ($this->db->transStatus() === FALSE) {
      $this->db->transRollback();
      return false;
    } else {
      $this->db->transCommit();
      return true;
    }
  }

  function updateDataByArray($data, $where)
  {
    $this->db->transBegin();
    if ($where) {
      foreach ($where as $key => $value) {
        $this->builder->where($key, $value);
      }
    }

    $this->builder->update($data);
    if ($this->db->transStatus() === FALSE) {
      $this->db->transRollback();
      return false;
    } else {
      $this->db->transCommit();
      return true;
    }
  }

  function deleteData($where, $id)
  {
    $this->db->transBegin();
    $this->builder->where($where, $id);
    $this->builder->delete();
    if ($this->db->transStatus() === FALSE) {
      $this->db->transRollback();
      return false;
    } else {
      $this->db->transCommit();
      return true;
    }
  }

  public function getMax($field, $table = null)
  {
    $tableName = $table ?? $this->table;

    if (empty($tableName)) {
      throw new \Exception("Table name is required for getMax()");
    }

    $row = $this->db->table($tableName)
      ->selectMax($field)
      ->get()
      ->getRow();

    return $row ? $row->$field : null;
  }

  // ===== delete data dengan cascade ke table lain ===== //
  public function deleteDataWithCascade($where, $id, $cascadeTables = [])
  {
    $this->db->transBegin();

    // Hapus data dari tabel cascade terlebih dahulu
    foreach ($cascadeTables as $cascadeTable => $cascadeWhere) {
      $this->db->table($cascadeTable)
        ->where($cascadeWhere, $id)
        ->delete();
    }

    // Hapus data utama
    $this->builder->where($where, $id);
    $this->builder->delete();

    if ($this->db->transStatus() === FALSE) {
      $this->db->transRollback();
      return false;
    } else {
      $this->db->transCommit();
      return true;
    }
  }

  // ===== update data dengan cascade ke table lain ===== //
  public function updateDataWithCascade($data, $where, $id, $oldValue, $cascadeUpdates = [])
  {
    $this->db->transBegin();

    // Update data di tabel cascade terlebih dahulu
    foreach ($cascadeUpdates as $cascadeTable => $cascadeConfig) {
      $this->db->table($cascadeTable)
        ->where($cascadeConfig['where'], $oldValue)
        ->update([$cascadeConfig['field'] => $data[$where]]);
    }

    // Update data utama
    $this->builder->where($where, $id);
    $this->builder->update($data);

    if ($this->db->transStatus() === FALSE) {
      $this->db->transRollback();
      return false;
    } else {
      $this->db->transCommit();
      return true;
    }
  }

  // ===== cek duplikat data berdasarkan field (case-insensitive) ===== //
  public function checkDuplicateByField($field, $value, $excludeWhere = [], $caseInsensitive = true)
  {
    if ($caseInsensitive) {
      // Menggunakan LOWER() untuk perbandingan case-insensitive
      $this->builder->where("LOWER($field)", strtolower($value));
    } else {
      $this->builder->where($field, $value);
    }

    // Exclude record tertentu (misalnya saat update, exclude ID yang sedang diedit)
    if (!empty($excludeWhere)) {
      foreach ($excludeWhere as $key => $val) {
        $this->builder->where("$key !=", $val);
      }
    }

    $count = $this->builder->countAllResults();
    return $count > 0; // Return true jika ada duplikat
  }

  // ===== cek duplikat data dengan multiple fields (case-insensitive) ===== //
  public function checkDuplicateByFields($fields = [], $excludeWhere = [], $caseInsensitive = true)
  {
    if (!empty($fields)) {
      foreach ($fields as $field => $value) {
        if ($caseInsensitive) {
          $this->builder->where("LOWER($field)", strtolower($value));
        } else {
          $this->builder->where($field, $value);
        }
      }
    }

    // Exclude record tertentu
    if (!empty($excludeWhere)) {
      foreach ($excludeWhere as $key => $val) {
        $this->builder->where("$key !=", $val);
      }
    }

    $count = $this->builder->countAllResults();
    return $count > 0;
  }

  // ===== mengambil data menggunakan getWhere (untuk compatibility dengan kode lama) ===== //
  public function getWhere($where = [])
  {
    if (!empty($where)) {
      foreach ($where as $key => $value) {
        // Support operator seperti 'id !=' => 5
        if (strpos($key, ' ') !== false) {
          // Jika ada operator (seperti 'id !=')
          $this->builder->where($key, $value);
        } elseif (is_array($value)) {
          // Jika value adalah array, gunakan whereIn
          $this->builder->whereIn($key, $value);
        } else {
          $this->builder->where($key, $value);
        }
      }
    }
    return $this->builder->get();
  }

  // ===== count data by multiple where conditions ===== //
  public function countByWhere($where = [])
  {
    if (!empty($where)) {
      foreach ($where as $key => $value) {
        if (is_array($value)) {
          $this->builder->whereIn($key, $value);
        } else {
          $this->builder->where($key, $value);
        }
      }
    }
    return $this->builder->countAllResults();
  }


}
