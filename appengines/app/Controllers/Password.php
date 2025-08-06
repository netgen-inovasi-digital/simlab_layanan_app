<?php namespace App\Controllers;

use App\Models\MyModel; 

class Password extends BaseController
{
    public function index()
    {
		$data = [
            'title' => 'Ganti Password',
        ];
		return view('pages/v_password', $data);
    }

	public function submit()
	{
		$passwordlama = $this->request->getPost('oldpass');
		$passwordbaru = $this->request->getPost('newpass');
		$model = new MyModel('users');
		$cek = $model->checkPassword($passwordlama);
		$link = "";
		if($cek){
			$data = array(
				'password' => password_hash($passwordbaru, PASSWORD_DEFAULT)
			);
			$res = $model->updateData($data, 'username', session()->get('username'));
			$res = 'refresh';
			$link = 'password';
		} 
		else {
			$res = 'check';
			$link = "Password Lama SALAH!";
		}
	
        return $this->response->setJSON(array('res'=>$res, 'link'=>$link, 'xname'=>csrf_token(), 'xhash'=>csrf_hash()));
	}
}