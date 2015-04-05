<?php

class KbAmzApi
{
    const API_URL           = 'http://www.2kblater.com';
    const PRODUCTS_CATEGORY = 86;
    
    protected $apiKey   = null;
    protected $apiKey2  = null;


    public function __construct()
    {
        $this->apiKey   = getKbAmz()->getStoreId();

    }
    
    public function setUser($data)
    {
        return $this->getRequest('setUser', $data);
    }

    public function getProductsCount()
    {
        $data =  $this->getRequest('getProductsCount');
        return (int) (isset($data->count) ? $data->count : 250);
    }
    
    public function getProductsListHtml()
    {
        return $this->getRequest('getProductsListHtml');
    }
    
    public function getNetworkListHtml()
    {
        return $this->getRequest('getNetworkListHtml');
    }
    
    public function getOrderActivationData($purchaseId)
    {
        return $this->getRequest('getOrderActivationData', array('order' => $purchaseId));
    }

    public function activatePurchase($purchaseId)
    {
        return $this->getRequest('activateAmzOrder', array('order' => $purchaseId));
    }

    protected function getRequest($action, $params = array())
    {
        $params['2kbProductsApiAction'] = $action;
        $params['cat']                  = self::PRODUCTS_CATEGORY;
        $params['type']                 = 'json';
        $params['apiType']              = 'kbAmz';
        $params['apiKey']               = $this->apiKey;
        $params['requestTime']          = date('Y-m-d H:i:s');
        
        $requestUrl = sprintf(
            self::API_URL . '?%s',
            http_build_query($params)
        );
        
        $response = wp_remote_get($requestUrl);
        $content  = '';
        if (isset($response['body'])) {
            $content = $response['body'];
        }
        $data = array();
        if (!empty($content)) {
            $data = json_decode($content);
        } else {
            $data['success'] = false;
            $data['error'] = 'Server connection error.';
        }
        return $data;
    }

}