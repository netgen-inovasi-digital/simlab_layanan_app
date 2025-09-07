<?php

namespace App\Models;

use CodeIgniter\Model;

class AuthModelAdmin extends Model
{
    protected $table = 'simlab_account_admin';
    protected $primaryKey = 'admin_username';

    protected $allowedFields = [
        'admin_username',
        'id_role',
        'admin_password',
        'lab_kode'
    ];

    // Ambil data admin berdasarkan username
    public function getAdminByUsername($username)
    {
        return $this->where('admin_username', $username)->first();
    }

    // === Tambahkan ini untuk ambil menu berdasarkan role admin ===
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
