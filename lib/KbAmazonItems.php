<?php

!defined('ABSPATH') and exit;

class KbAmazonItems
{
    protected $result;
    protected $items = array();
    
    public function __construct($result, $cache = false)
    {
        $this->result = $result;
        if ($this->isValid() && isset($result['Items']['Item'])) {
            foreach ($result['Items']['Item'] as $item) {
                $item = new KbAmazonItem(array('Items' => array('Item' => $item)));
                $this->items[] = $item;
                if ($cache) {
                    KbAmazonImporter::cacheItem($item);
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
    
    public function getItems()
    {
        return $this->items;
    }
}

