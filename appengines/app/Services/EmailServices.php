<?php

namespace App\Services;

class EmailServices
{
    protected $email;

    public function __construct()
    {
        $this->email = \Config\Services::email();
    }

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
}
