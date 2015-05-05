<?php

!defined('ABSPATH') and exit;

class KbAmazonItems
{
    protected $result;
    protected $items = array();
    
    public function __construct($result)
    {
        $this->result = $result;
        if ($this->isValid() && isset($result['Items']['Item'])) {
            if (isset($result['Items']['Item']['ASIN'])) {
                $this->items[] = new KbAmazonItem(array('Items' => array('Item' => $result['Items']['Item'])));
            } else {
                foreach ($result['Items']['Item'] as $item) {
                    $item = new KbAmazonItem(array('Items' => array('Item' => $item)));
                    $this->items[] = $item;
                }
            }

        }
    }
    
    public function isValid()
    {
        return isset($this->result['Items']['Request']['IsValid'])
               && $this->result['Items']['Request']['IsValid'] == 'True';
    }
    
    public function getError()
    {
        if (isset($this->result['Items']['Request']['Errors']['Error']['Message'])) {
            return $this->result['Items']['Request']['Errors']['Error']['Message'];
        }
    }
    
    public function getTotalResults()
    {
        return isset($this->result['Items']['TotalResults']) ? $this->result['Items']['TotalResults'] : 0;
    }
    
    public function getTotalPages()
    {
        return isset($this->result['Items']['TotalPages']) ? $this->result['Items']['TotalPages'] : 0;
    }
    
    public function getItems()
    {
        return $this->items;
    }
}

