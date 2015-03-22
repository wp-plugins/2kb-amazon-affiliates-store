<?php

class KbAmzApi
{
    const API_URL           = 'http://www.2kblater.com';
    const PRODUCTS_CATEGORY = 86;
    
    protected $apiKey = null;
    
    public function __construct($apiKey)
    {
        $this->apiKey = $apiKey;
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
        $params['cat'] = self::PRODUCTS_CATEGORY;
        $params['type'] = 'json';
        $params['apiType'] = 'kbAmz';
        $params['apiKey'] = $this->apiKey;
        
        $requestUrl = sprintf(
            self::API_URL . '?%s',
            http_build_query($params)
        );
        //echo $requestUrl;die;
        $content = file_get_contents($requestUrl);
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