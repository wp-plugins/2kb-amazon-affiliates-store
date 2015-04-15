<?php

!defined('ABSPATH') and exit;

class KbAmazonImages
{

    protected $images;
    protected $post;

    protected $sizes = array();


    public function __construct($images, $post)
    {
        $images = empty($images) ? array() : $images;
        reset($images);
        $this->images = $images;
        $this->post = $post;
    }
    
    public function getImagesHtml($size = 'thumbnail', $icon = null, $attr = array())
    {
        $html = null;
        foreach ($this->images as $key => $image) {
            $html .= $this->getIndex($key, $size, $icon, $attr);
        }
        return $html;
    }
    
   public function getImagesHtmlSkipFirst($size = 'thumbnail', $icon = null, $attr = array())
    {
        $html = null;
        foreach ($this->images as $key => $image) {
            if ($key > 0) {
                $html .= $this->getIndex($key, $size, $icon, $attr);
            }
        }
        return $html;
    }

    public function getPost()
    {
        return is_numeric($this->post) ? get_post($this->post) : $this->post;
    }

    public function getFirst($size = 'thumbnail', $icon = null, $attr = array())
    {
        return $this->getIndex(0, $size, $icon, $attr);
    }
    
    public function getSecond($size = 'thumbnail', $icon = null, $attr = array())
    {
        return $this->getIndex(1, $size, $icon, $attr);
    }

    public function getIndex($index, $size = 'thumbnail', $icon = null, $attr = array())
    {
        if (isset($this->images[$index])) {
            $image = $this->images[$index];
            if ($image->isDownloaded()) {
                $maxImage = $image->getImageSrc($size);
                $attr['data-image'] = $maxImage;
                return wp_get_attachment_image($image->getObject()->ID, $size, $icon, $attr);
            } else {
                
                $meta = $image->getMeta();
                if (isset($meta['_wp_attachment_metadata'])) {
                    if (!array_key_exists('width', $attr)) {
                        $attr['width']  = $meta['_wp_attachment_metadata']['width'];
                    }
                    if (!array_key_exists('height', $attr)) {
                        $attr['height'] = $meta['_wp_attachment_metadata']['height'];
                    }
                }
                $attrs = array();
                foreach ($attr as $key => $val) {
                    $attrs[] = $key . '="' . $val . '"';
                }
                
                $styleData = array();
                if (isset($attr['width']) && $attr['width']) {
                    $styleData[] = 'width:' . intval($attr['width']) . 'px;';
                }
                if (isset($attr['height']) && $attr['height']) {
                    $styleData[] = 'height:' . intval($attr['height']) . 'px;';
                }
                if (!empty($styleData)) {
                    $attrs[] = 'style="' . implode('', $styleData) . '"';
                }
                
                $attrs[] = 'alt="'. htmlspecialchars($this->getPost()->post_title) .'"';
                $attrs[] = 'data-image="' .$image->getImageSrc() .'"';
       
                return sprintf(
                    '<img src="%s" %s/>',
                    $image->getImageSrc(),
                    implode(' ', $attrs)
                );
            }
        } else if ($index == 0) {
            $attrs = array();
            foreach ($attr as $key => $val) {
                $attrs[] = $key . '="' . $val . '"';
            }
            return sprintf(
                '<img src="%s" alt="%s" %s/>',
                getKbPluginUrl('template/images/default.jpg'),
                __('No Image'),
                implode(' ', $attrs)
            );
        }
    }
}
