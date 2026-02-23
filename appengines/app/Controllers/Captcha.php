<?php

namespace App\Controllers;

use CodeIgniter\Controller;
use App\Libraries\SimpleCaptcha;

class Captcha extends Controller
{
    /**
     * Endpoint AJAX untuk verifikasi captcha checkbox
     */
    public function verify()
    {
        // Pastikan request AJAX
        if (!$this->request->isAJAX()) {
            return $this->response->setStatusCode(403)->setJSON([
                'status' => 'error',
                'message' => 'Akses tidak diizinkan.'
            ]);
        }

        $token = $this->request->getPost('captcha_token');

        if (empty($token)) {
            return $this->response->setJSON([
                'status' => 'error',
                'message' => 'Token captcha tidak ditemukan.'
            ]);
        }

        $result = SimpleCaptcha::markVerified($token);

        if ($result) {
            // Return new CSRF token for subsequent form submission
            $csrfName = csrf_token();
            $csrfHash = csrf_hash();

            return $this->response->setJSON([
                'status' => 'success',
                'message' => 'Captcha berhasil diverifikasi.',
                'csrf_name' => $csrfName,
                'csrf_token' => $csrfHash,
            ]);
        }

        return $this->response->setJSON([
            'status' => 'error',
            'message' => 'Verifikasi captcha gagal. Silakan muat ulang halaman.'
        ]);
    }
}
