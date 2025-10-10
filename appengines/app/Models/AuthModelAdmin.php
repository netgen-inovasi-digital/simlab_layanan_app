<?php

namespace App\Models;

use CodeIgniter\Model;

class AuthModelAdmin extends Model
{
    protected $table = 'simlab_account';
    protected $primaryKey = 'user_id';

    protected $allowedFields = [
        'username',
        'role_id',
        'password',
        'status_user'
    ];
    
    public function getAdminByUsername($username)
    {
        return $this->where('username', $username)->first();
    }

    public function getMenu($role)
    {
        $db = \Config\Database::connect();
        $builder = $db->table('menus a');
        $builder->select('a.*, b.role_id, b.status_otoritas');
        $builder->join('otoritas b', 'a.kode_menu=b.kode_menu');
        $builder->where('role_id', $role);
        $builder->where('status_otoritas', 1);
        $builder->orderBy('a.sort_order', 'asc');
        return $builder->get()->getResult();
    }
}
