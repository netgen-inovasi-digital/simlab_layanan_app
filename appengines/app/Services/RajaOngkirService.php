<?php

namespace App\Services;

use App\Models\MyModel;
use Config\Services;

class RajaOngkirService
{
    private $apiKey;
    private $originSubdistrictId;
    private $couriers;
    
    public function __construct()
    {
        // Load configuration
        $configModel = new MyModel('konfigurasi');
        $config = $configModel->getDataById('id_konfigurasi', 1);
        
        if ($config) {
            $this->apiKey = $config->rajaongkir_api_key ?? null;
            $this->originSubdistrictId = $config->rajaongkir_origin_subdistrict_id ?? null;
            $this->couriers = $config->rajaongkir_couriers ?? 'jne,sicepat,jnt,pos,tiki';
        }
    }
    
    public function isConfigured()
    {
        return !empty($this->apiKey) && !empty($this->originSubdistrictId);
    }
    
    public function getConfigurationStatus()
    {
        return [
            'api_key' => !empty($this->apiKey) ? 'Configured' : 'Not configured',
            'origin_subdistrict_id' => !empty($this->originSubdistrictId) ? 'Configured' : 'Not configured',
            'couriers' => !empty($this->couriers) ? $this->couriers : 'Not configured',
            'api_key_length' => $this->apiKey ? strlen($this->apiKey) : 0,
            'origin_id' => $this->originSubdistrictId
        ];
    }
    
    public function searchDestination($keyword, $limit = 10, $offset = 0)
    {
        if (!$this->apiKey) {
            return ['success' => false, 'message' => 'RajaOngkir API key not configured'];
        }
        
        $client = Services::curlrequest();
        
        try {
            $response = $client->get('https://rajaongkir.komerce.id/api/v1/destination/domestic-destination', [
                'query' => [
                    'search' => $keyword,
                    'limit' => $limit,
                    'offset' => $offset
                ],
                'headers' => [
                    'key' => $this->apiKey,
                    'Accept' => 'application/json'
                ],
                'timeout' => 30
            ]);

            $result = json_decode($response->getBody(), true);
            
            if ($response->getStatusCode() === 200) {
                return ['success' => true, 'data' => $result];
            } else {
                return ['success' => false, 'message' => 'API request failed', 'error' => $result];
            }
        } catch (\Exception $e) {
            return ['success' => false, 'message' => 'API request error: ' . $e->getMessage()];
        }
    }
    
    public function calculateShippingCost($destinationSubdistrictId, $weight, $selectedCouriers = null)
    {
        if (!$this->apiKey || !$this->originSubdistrictId) {
            return ['success' => false, 'message' => 'RajaOngkir configuration incomplete'];
        }
        
        $couriers = $selectedCouriers ?: $this->couriers;
        $couriersString = is_array($couriers) ? implode(':', $couriers) : str_replace(',', ':', $couriers);
        
        $client = Services::curlrequest();
        
        try {
            $response = $client->post('https://rajaongkir.komerce.id/api/v1/calculate/domestic-cost', [
                'headers' => [
                    'key' => $this->apiKey,
                    'Content-Type' => 'application/x-www-form-urlencoded',
                    'Accept' => 'application/json'
                ],
                'form_params' => [
                    'origin' => $this->originSubdistrictId,
                    'destination' => $destinationSubdistrictId,
                    'weight' => $weight,
                    'courier' => $couriersString,
                    'price' => 'lowest'
                ],
                'timeout' => 30
            ]);

            $result = json_decode($response->getBody(), true);
            
            if ($response->getStatusCode() === 200) {
                return ['success' => true, 'data' => $result];
            } else {
                return ['success' => false, 'message' => 'Shipping calculation failed', 'error' => $result];
            }
        } catch (\Exception $e) {
            return ['success' => false, 'message' => 'Shipping calculation error: ' . $e->getMessage()];
        }
    }
    
    public function formatShippingOptions($calculationResult)
    {
        if (!$calculationResult['success'] || !isset($calculationResult['data']['data'])) {
            return [];
        }
        
        $options = [];
        $data = $calculationResult['data']['data'];
        
        foreach ($data as $service) {
            // Skip services with no cost or service not found
            if (!isset($service['cost']) || $service['cost'] == 0 || $service['service'] == 'NOT FOUND') {
                continue;
            }
            
            // Clean ETD value - handle empty string, null, or whitespace
            $etdValue = 'Estimasi tidak tersedia';
            if (isset($service['etd']) && is_string($service['etd'])) {
                $cleanEtd = trim($service['etd']);
                if (!empty($cleanEtd)) {
                    $etdValue = $cleanEtd;
                }
            }
            
            $options[] = [
                'courier_code' => $service['code'],
                'courier_name' => $service['name'],
                'service_code' => $service['service'],
                'service_name' => $service['description'],
                'cost' => $service['cost'],
                'cost_formatted' => number_format($service['cost'], 0, ',', '.'),
                'etd' => $etdValue,
                'note' => ''
            ];
        }
        
        // Sort by cost (lowest first)
        usort($options, function($a, $b) {
            return $a['cost'] <=> $b['cost'];
        });
        
        return $options;
    }
}
