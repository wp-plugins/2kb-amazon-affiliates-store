<?php

!defined('ABSPATH') and exit;

class KbView
{
    protected $data = array();
    
    protected $template;
    
    protected $layout;
    
    protected $content;

    public function __construct(array $data, $template = null, $layout = null)
    {
        $this->data = $data;
        $this->template = $template;
        $this->layout = $layout;
    }
    
    public function __get($name)
    {
        return isset($this->data[$name]) ? $this->data[$name] : null;
    }
    
    public function __toString()
    {
        ob_start();
        if ($this->getLayout()) {
            include $this->getLayout();
        } else {
            echo $this->getContent();
        }
        return ob_get_clean();
    }

    /**
     * 
     * @param type $tmpl
     * @return \KbView
     */
    public function setTemplate($tmpl)
    {
        $this->template = $tmpl;
        return $this;
    }
    
    /**
     * 
     * @param type $tmpl
     * @return \KbView
     */
    public function setLayout($tmpl)
    {
        $this->layout = $tmpl;
        return $this;
    }
    
    public function getContent()
    {
        if (!$this->content) {
            ob_start();
            include $this->getTemplate();
            $this->content = ob_get_clean();
        }
        return $this->content;
    }

    public function hasTemplate()
    {
        return $this->template ? true : false;
    }

    protected function getLayout()
    {
        return $this->layout ? $this->layout . '.phtml' : null;
    }

    protected function getTemplate()
    {
        if (strpos($this->template, KbAmazonStoreFolderName) !== false) {
            return $this->template.'.phtml';
        } else {
            return KbAmazonStorePluginPath . 'template/view/' . $this->template.'.phtml';
        }
    }
}
