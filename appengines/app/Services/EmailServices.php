<?php

namespace App\Services;

class EmailServices
{
    protected $email;

    public function __construct()
    {
        $this->email = \Config\Services::email();
    }

    // notifikasi sederhana (lupa password)
    public function send(array $params): bool
    {
        $this->email->setFrom(
            $params['from_email'] ?? config('Email')->fromEmail,
            $params['from_name'] ?? config('Email')->fromName
        );
        $this->email->setTo($params['to']);
        $this->email->setSubject($params['subject']);
        $this->email->setMessage($params['message']);

        return $this->email->send();
    }

    // notifikasi email
    public function sendOrderStatus($toEmail, $subject, $templateView, $data)
    {
        $message = view("email/orders/{$templateView}", $data); // render dengan layout
        $this->email->setTo($toEmail);
        $this->email->setSubject($subject);
        $this->email->setMessage($message);
        $this->email->setMailType('html');
        return $this->email->send();

    }
}
