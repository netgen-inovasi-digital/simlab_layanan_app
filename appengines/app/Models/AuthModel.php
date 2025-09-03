<?php namespace App\Models;

use CodeIgniter\Model;

class AuthModel extends Model
{
	protected $table = 'simlab_account_users';
    protected $primaryKey = 'user_id';
     protected $allowedFields = [
        'user_name',
        'user_email',
        'user_password',
        'user_identity',
        'role_id',
        'status_user',
    ];

	public function checkUsername($username)
	{
		$db = \Config\Database::connect();
        $builder = $db->table('simlab_account_users');
        $builder->where('user_name', $username);
		return $builder->get()->getNumRows();
	}

    public function checkEmail($email)
	{
		$db = \Config\Database::connect();
        $builder = $db->table('simlab_account_users');
        $builder->where('user_email', $email);
		return $builder->get()->getNumRows();
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

    public function registerUser($data)
    {
        return $this->insert($data);
    }
}