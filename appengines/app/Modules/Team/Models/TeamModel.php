<?php namespace App\Modules\Team\Models;

use CodeIgniter\Model;

class TeamModel extends Model
{
    function getAllData() 
    {
        $db = \Config\Database::connect();
		$builder = $db->table('Team');
        return $builder->get()->getResult();
    }

}