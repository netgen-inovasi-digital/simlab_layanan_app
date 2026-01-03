<?php

namespace App\Controllers;

use CodeIgniter\Controller;
use App\Models\MyModel;
use App\Models\AuthModel;
use App\Models\AuthModelAdmin;
use App\Services\EmailServices;

class Auth extends Controller
{
  public function index()
  {
    $session = session();
    if ($session->get('logged_in') == FALSE) {
      helper('form');
      $redirect = $this->request->getGet('redirect');
      $data = ['redirect' => $redirect];
      return view('auth/v_login', $data);
    } else {
      return redirect()->route('home');
    }
  }

  public function act()
  {
    helper('form');
    $session = session();
    $model = new AuthModel();

    $email = $this->request->getPost('email');
    $password = $this->request->getPost('pwd');
    $data = $model->where('user_email', $email)->first();

    if ($data) {
      $hash = $data['user_password'];
      $verify_pass = password_verify($password, $hash);
      if ($verify_pass) {
        if ($data['status_user'] == 1) {
          date_default_timezone_set('Asia/Singapore');
          $datenow = date('Y-m-d H:i:s');

          $modelRoles = new MyModel('roles');
          $role = $modelRoles->where('id_role', $data['role_id'])->first();
          $nama_role = $role ? $role['nama_role'] : 'Role';

          $ses_data = [
            'id_user' => $data['user_id'],
            'nama' => $data['user_name'],
            'email' => $data['user_email'],
            'role_id' => $data['role_id'],
            'nama_role' => $nama_role,
            'logged_in' => TRUE
          ];

          $getMenu = $model->getMenu($data['role_id']);
          $menu = array(
            'menus' => array(),
            'parent_menus' => array(),
          );

          foreach ($getMenu as $row) {
            $menu['menus'][$row->kode_menu] = $row;
            $menu['parent_menus'][$row->kode_induk][] = $row->kode_menu;
          }

          $ses_data['menu'] = $menu;
          $session->set($ses_data);

          // ambil parameter redirect dari POST
          $redirect = $this->request->getPost('redirect');
          if ($redirect && !empty($redirect)) {
            $redirectUrl = urldecode($redirect);
            if (strpos($redirectUrl, base_url()) === 0 || strpos($redirectUrl, '/') === 0) {
              return redirect()->to($redirectUrl);
            }
          }

          return redirect()->route('home');
        } else {
          $session->setFlashdata('login_error', '* Akun anda dinonaktifkan!');
          $session->setFlashdata('login_email', $email);
          return redirect()->back();
        }
      } else {
        $session->setFlashdata('login_error', '* Password Salah!');
        $session->setFlashdata('login_email', $email);
        return redirect()->back();
      }
    } else {
      $session->setFlashdata('login_error', '* Email salah atau belum terdaftar!');
      $session->setFlashdata('login_email', $email);
      return redirect()->back();
    }
  }

  public function actAdmin()
  {
    helper('form');
    $session = session();
    $adminModel = new AuthModelAdmin();

    $username = $this->request->getPost('username');
    $password = $this->request->getPost('password');

    // Ambil data admin dari tabel
    $admin = $adminModel->getAdminByUsername($username);

    if ($admin) {
      $hash = $admin['password']; // cocokkan dengan field database

      if (password_verify($password, $hash)) {
        if ($admin['status_user'] == 1) {
          $modelRoles = new MyModel('roles');
          $role = $modelRoles->where('id_role', $admin['role_id'])->first();
          $nama_role = $role ? $role['nama_role'] : 'Admin Role';

          $ses_data = [
            'id_user' => $admin['user_id'],
            'nama' => $admin['nama'] ?? $admin['username'],
            'role_id' => $admin['role_id'],
            'nama_role' => $nama_role,
            'logged_in' => TRUE
          ];

          // Ambil menu sesuai role
          $getMenu = $adminModel->getMenu($admin['role_id']);
          $menu = [
            'menus' => [],
            'parent_menus' => [],
          ];

          foreach ($getMenu as $row) {
            $menu['menus'][$row->kode_menu] = $row;
            $menu['parent_menus'][$row->kode_induk][] = $row->kode_menu;
          }

          $ses_data['menu'] = $menu;
          $session->set($ses_data);

          return redirect()->to('/dashboard');
        } else {
          $session->setFlashdata('login_error', '* Akun anda dinonaktifkan!');
          $session->setFlashdata('login_username', $username);
        }
      } else {
        $session->setFlashdata('login_error', '* Password salah!');
        $session->setFlashdata('login_username', $username);
      }
    } else {
      $session->setFlashdata('login_error', '* Username tidak ditemukan!');
      $session->setFlashdata('login_username', $username);
    }

    return redirect()->back();
  }



  public function register()
  {
    $session = session();
    if ($session->get('logged_in') == FALSE) {
      helper('form');
      return view('auth/v_register');
    } else {
      return redirect()->route('home');
    }
  }

  // public function logout()
  // {
  // 	$session = session();
  // 	$session->destroy();
  // 	return redirect()->to(base_url('/'));
  // }

  public function logout()
  {
    $session = session();

    // Simpan role sebelum session dihancurkan
    $roleId = $session->get('role_id');

    // Hapus semua session
    $session->destroy();

    // Tentukan redirect berdasarkan role
    if ($roleId != 2) {
      return redirect()->to(base_url('login'));
    } else {
      // Jika tidak ada role (default)
      return redirect()->to(base_url('/'));
    }
  }


  public function actRegister()
  {
    $session = session();
    $model = new AuthModel();

    // Ambil data dari form
    $username = $this->request->getPost('nama');
    $email = $this->request->getPost('email');
    $password = $this->request->getPost('pwd');
    $repassword = $this->request->getPost('repwd');

    // Validasi field kosong
    if (empty($username) || empty($email) || empty($password) || empty($repassword)) {
      $session->setFlashdata('error', 'Semua field wajib diisi!');
      return redirect()->back()->withInput();
    }

    // Validasi panjang password minimal 6 karakter
    if (strlen($password) < 6) {
      $session->setFlashdata('error', 'Password minimal 6 karakter!');
      return redirect()->back()->withInput();
    }

    // Cek username sudah ada
    // if ($model->checkUsername($username) > 0) {
    //     $session->setFlashdata('error', 'Username sudah terdaftar!');
    //     return redirect()->back()->withInput();
    // }

    // Cek email sudah ada
    if ($model->checkEmail($email) > 0) {
      $session->setFlashdata('error', 'Email sudah terdaftar!');
      return redirect()->back()->withInput();
    }

    // Cek password 2 kali
    if ($password !== $repassword) {
      $session->setFlashdata('error', 'Password tidak sama!');
      return redirect()->back()->withInput();
    }

    // Hash password
    $hash = password_hash($password, PASSWORD_DEFAULT);

    // Tentukan identitas otomatis
    $identity = (strpos($email, '@ulm.ac.id') !== false) ? 'ULM' : 'NON ULM';

    $data = [
      'user_name' => $username,
      'user_email' => $email,
      'user_password' => $hash,
      'user_identity' => $identity,
      'status_user' => 1,
      'role_id' => 2, //pengaturan user role id
    ];

    // Simpan ke database
    try {
      $model->registerUser($data);
      $session->setFlashdata('success', 'Registrasi berhasil! Silakan login.');
      return redirect()->to('/');
    } catch (\Exception $e) {
      $session->setFlashdata('error', 'Registrasi gagal: ' . $e->getMessage());
      return redirect()->back()->withInput();
    }

  }


  // untuk fitur lupa password
  public function forgot()
  {
    $data = [
      'title' => 'Lupa Password',
    ];
    helper('form');
    return view('auth/v_lupa_password', $data);
  }

  public function sendReset()
  {
    $session = session();
    $email = $this->request->getPost('email');

    // Validasi email tidak boleh kosong
    if (empty($email)) {
      $session->setFlashdata('error', 'Email tidak boleh kosong!');
      return redirect()->back()->withInput();
    }

    $model = new AuthModel();
    $user = $model->where('user_email', $email)->first();

    if (!$user) {
      $session->setFlashdata('error', 'Email tidak terdaftar!');
      return redirect()->back()->withInput();
    }

    helper('text');
    $token = random_string('alnum', 64);

    $modelResetPassword = new MyModel('password_resets');
    $data = [
      'user_id' => $user['user_id'],
      'token' => $token,
      'used' => 0,
      'expired_at' => date('Y-m-d H:i:s', strtotime('+1 hour')),
    ];
    $modelResetPassword->insertData($data);

    $resetLink = base_url('reset/' . $token);

    // Gunakan EmailService
    $emailService = new EmailServices();
    $userName = !empty($user['user_name']) ? $user['user_name'] : 'Pengguna';
    $message = "
        <div style='font-family: Arial, sans-serif; max-width: 600px; margin: 0 auto;'>
            <h2 style='color: #333;'>Reset Password</h2>
            <p>Halo <strong>{$userName}</strong>,</p>
            <p>Kami menerima permintaan untuk mereset password akun Anda.</p>
            <p>Klik tombol di bawah ini untuk mereset password Anda:</p>
            <p style='text-align: center; margin: 30px 0;'>
                <a href='{$resetLink}' style='background-color: #007bff; color: white; padding: 12px 30px; text-decoration: none; border-radius: 5px; display: inline-block;'>Reset Password</a>
            </p>
            <p>Atau salin dan tempel link berikut di browser Anda:</p>
            <p style='background-color: #f5f5f5; padding: 10px; word-break: break-all;'>{$resetLink}</p>
            <p style='color: #666; font-size: 14px;'><strong>Catatan:</strong> Link ini hanya berlaku selama 1 jam.</p>
            <p style='color: #666; font-size: 14px;'>Jika Anda tidak meminta reset password, abaikan email ini dan password Anda akan tetap aman.</p>
            <hr style='border: none; border-top: 1px solid #ddd; margin: 30px 0;'>
            <p style='color: #999; font-size: 12px;'>Email ini dikirim otomatis, mohon tidak membalas email ini.</p>
        </div>
    ";

    // email user
    $model = new MyModel('konfigurasi');
    $get = $model->getDataById('id_konfigurasi', 1);

    $emailSent = $emailService->send([
      'from_email' => $get->email,
      'from_name' => 'Notification',
      'to' => $email,
      'subject' => 'Reset Password Anda',
      'message' => $message
    ]);

    if (!$emailSent) {
      log_message('error', 'Failed to send password reset email to: ' . $email);
      return redirect()->back()->with('error', 'Gagal mengirim email. Silakan cek konfigurasi SMTP atau hubungi administrator.');
    }

    return redirect()->back()->with('success', 'Link reset password telah dikirim ke email Anda. Silakan cek inbox atau folder spam Anda.');
  }

  public function resetPassword($token)
  {
    if (empty($token)) {
      return redirect()->to('/login')->with('error', 'Token tidak valid.');
    }

    $model = new MyModel('password_resets');
    $where = [
      'token' => $token,
      'used' => 0,
      'expired_at >=' => date('Y-m-d H:i:s')
    ];
    $reset = $model->getDataByWhere($where);

    if (!$reset) {
      return redirect()->to('/login')->with('error', 'Link reset password tidak valid atau telah kadaluarsa. Silakan ajukan reset password kembali.');
    }
    helper('form');
    return view('auth/v_reset_password', ['token' => $token]);
  }

  public function sendPassword()
  {
    $token = $this->request->getPost('token');
    $password = $this->request->getPost('pass');
    $repass = $this->request->getPost('reppass');

    // Validasi field tidak boleh kosong
    if (empty($password) || empty($repass)) {
      return redirect()->back()->with('error', 'Password tidak boleh kosong.');
    }

    // Validasi minimal 6 karakter
    if (strlen($password) < 6) {
      return redirect()->back()->with('error', 'Password minimal 6 karakter.');
    }

    // Validasi password sama
    if ($password !== $repass) {
      return redirect()->back()->with('error', 'Password dan konfirmasi password tidak sama.');
    }

    $modelReset = new MyModel('password_resets');
    $where = [
      'token' => $token,
      'used' => 0,
      'expired_at >=' => date('Y-m-d H:i:s')
    ];
    $reset = $modelReset->getDataByWhere($where);

    if (!$reset) {
      return redirect()->to('/login')->with('error', 'Token tidak valid atau telah kadaluarsa. Silakan ajukan reset password kembali.');
    }

    // Update password user
    $data = [
      'user_password' => password_hash($password, PASSWORD_DEFAULT),
    ];
    $modelUser = new MyModel('account_users');
    $modelUser->updateData($data, 'user_id', $reset->user_id);

    // Tandai token sebagai sudah digunakan
    $dataToken = [
      'used' => 1
    ];
    $modelReset->updateData($dataToken, 'token', $token);

    return redirect()->to('login')->with('success', 'Password berhasil diubah! Silakan login dengan password baru Anda.');
  }
}