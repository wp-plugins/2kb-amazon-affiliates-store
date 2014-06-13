<?php

!defined('ABSPATH') and exit;

class KbAmazonImage
{
    protected $image;
    
    public function __construct($image)
    {
        $this->image = $image;
    }
    
    public function getImageSrc($size = null)
    {
        if ($this->isDownloaded()) {
            return wp_get_attachment_image_src($this->image->ID, $size);
        } else {
            return isset($this->image->amzMeta['_wp_attached_file']) ? $this->image->amzMeta['_wp_attached_file'] : null;
        }
    }
    
    public function getMeta()
    {
        return isset($this->image->amzMeta) ? $this->image->amzMeta : array();
    }

    public function getObject()
    {
        return $this->image;
    }

    public function isDownloaded()
    {
        return isset($this->image->amzMeta['_wp_attached_file'])
               && strpos($this->image->amzMeta['_wp_attached_file'], 'http://') === false;
    }
}

