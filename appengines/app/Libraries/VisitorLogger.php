<?php

namespace App\Libraries;
use App\Models\MyModel;

class VisitorLogger
{
    public static function logVisit()
    {
        $request = service('request');
        $model = new MyModel('visitor');

        $ip = $request->getIPAddress();
        $agent = $request->getUserAgent()->getAgentString();
        $page = current_url();
        $date = date('Y-m-d');
        $time = date('H:i:s');
		
		$data = array(
			'ipAddress' => $ip,
			'userAgent' => $agent,
			'visitPage' => $page,
			'visitDate' => $date,
			'visitTime' => $time,
		);
		
		$where = ['ipAddress' => $ip, 'visitDate' => $date];
		$cek = $model->getCountAllByArray($where);

        if ($cek == 0) {
            $model->insertData($data);
        }
    }
}