<?php

namespace App\Models;

/**
 * AccountModel
 * Handle operasi user/account
 * Global model - bisa dipakai di semua modul
 */
class AccountModel extends MyModel
{
    protected $table = 'simlab_account';

    // Role ID Constants
    const ROLE_MANAJER_TEKNIS = 4;
    const ROLE_PENYELIA = 6;

    public function __construct()
    {
        parent::__construct($this->table);
    }

    // Ambil user berdasarkan role
    public function getUsersByRole($roleId)
    {
        return $this->getWhere(['role_id' => $roleId])->getResult();
    }

    // Ambil user penyelia
    public function getPenyelia()
    {
        return $this->getUsersByRole(self::ROLE_PENYELIA);
    }

    // Ambil user manajer teknis
    public function getManajerTeknis()
    {
        return $this->getUsersByRole(self::ROLE_MANAJER_TEKNIS);
    }

    // Ambil user by ID
    public function getUserById($userId)
    {
        return $this->getDataById('user_id', $userId);
    }

    // Validasi tim: minimal 1 penyelia dan 1 manajer teknis
    public function validateTimMembers($userIds)
    {
        if (empty($userIds) || !is_array($userIds)) {
            return [
                'valid' => false,
                'penyelia_count' => 0,
                'manajer_count' => 0,
                'message' => 'Tim penanggung jawab harus diisi minimal 1 Penyelia dan 1 Manajer Teknis.'
            ];
        }

        $penyeliaCount = 0;
        $manajerCount = 0;

        foreach ($userIds as $userId) {
            if (!empty($userId)) {
                $user = $this->getUserById($userId);
                if ($user) {
                    if ($user->role_id == self::ROLE_PENYELIA) {
                        $penyeliaCount++;
                    } elseif ($user->role_id == self::ROLE_MANAJER_TEKNIS) {
                        $manajerCount++;
                    }
                }
            }
        }

        $valid = ($penyeliaCount >= 1 && $manajerCount >= 1);
        $message = $valid 
            ? 'Validasi tim berhasil' 
            : 'Tim penanggung jawab harus memiliki minimal 1 Penyelia dan 1 Manajer Teknis.';

        return [
            'valid' => $valid,
            'penyelia_count' => $penyeliaCount,
            'manajer_count' => $manajerCount,
            'message' => $message
        ];
    }
}
