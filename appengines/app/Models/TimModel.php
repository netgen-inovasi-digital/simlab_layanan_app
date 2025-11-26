<?php

namespace App\Models;

/**
 * TimModel
 * Handle operasi tim penanggung jawab
 * Global model - bisa dipakai di semua modul
 */
class TimModel extends MyModel
{
    protected $table = 'r_tim';

    public function __construct()
    {
        parent::__construct($this->table);
    }

    // Ambil tim dengan detail user (join account)
    public function getTimWithUserDetails($ujiKode)
    {
        $join = [
            'simlab_account a' => 'a.user_id = ' . $this->table . '.user_id'
        ];

        $select = $this->table . '.*, a.username, a.nama, a.role_id';
        $where = [$this->table . '.uji_kode' => $ujiKode];

        return $this->getAllDataWithJoinWhereOrder($join, $where, [], $select, 'left');
    }

    // Ambil tim dengan nama role
    public function getTimWithRoles($ujiKode, $roleMap = [])
    {
        $timList = $this->getTimWithUserDetails($ujiKode);

        foreach ($timList as &$member) {
            $member->role_name = $roleMap[$member->role_id] ?? 'Unknown';
        }

        return $timList;
    }

    // Hitung jumlah anggota tim
    public function countTimMembers($ujiKode)
    {
        return $this->getWhere(['uji_kode' => $ujiKode])->getNumRows();
    }

    // Hitung tim untuk banyak layanan sekaligus
    public function getTimCountsForLayanan($ujiKodes)
    {
        $counts = [];
        foreach ($ujiKodes as $kode) {
            $counts[$kode] = $this->countTimMembers($kode);
        }
        return $counts;
    }

    // Hapus semua tim dari layanan
    public function deleteTimByLayanan($ujiKode)
    {
        return $this->deleteData('uji_kode', $ujiKode);
    }

    // Insert banyak anggota tim sekaligus
    public function insertTimMembers($ujiKode, $userIds)
    {
        $successCount = 0;
        $errors = [];

        if (!empty($userIds) && is_array($userIds)) {
            foreach ($userIds as $userId) {
                if (!empty($userId)) {
                    try {
                        $timData = [
                            'uji_kode' => $ujiKode,
                            'user_id' => (int)$userId
                        ];
                        
                        $result = $this->insertData($timData);
                        if ($result) {
                            $successCount++;
                        }
                    } catch (\Exception $e) {
                        $errors[] = "Error inserting user_id {$userId}: " . $e->getMessage();
                    }
                }
            }
        }

        return [
            'success' => $successCount,
            'errors' => $errors
        ];
    }

    // Ganti semua anggota tim (hapus lama, insert baru)
    public function replaceTimMembers($ujiKode, $userIds)
    {
        // Delete existing members
        $this->deleteTimByLayanan($ujiKode);
        
        // Insert new members
        return $this->insertTimMembers($ujiKode, $userIds);
    }
}
