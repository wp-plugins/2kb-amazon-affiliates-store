<?php
!defined('ABSPATH') and exit;

/**
 * 
 * @staticvar $KbTemplate KbTemplate
 * @return \KbTemplate
 */
function getKbAmzTemplate()
{
    static $KbTemplate;
    if (!$KbTemplate) {
        $KbTemplate = new KbAmzTemplate;
    }
    return $KbTemplate;
}

class KbAmzTemplate
{
    protected $template = '';
    
    public function __construct()
    {
       
        add_filter('body_class', array($this, 'bodyClass'));
        add_action('init', array($this, 'initDefaultScripts'));
        add_action('admin_init', array($this, 'initDefaultAdminScripts'));
        
        add_action('init', array($this, 'initTemplate'));
    }
    
    public function getProductsView($data = array())
    {
        return new KbView($data, KbAmazonStorePluginPath . 'template/view/products');
    }
    
    public function initTemplate()
    {
        
    }

    public function bodyClass($classes)
    {
        $theme = wp_get_theme();
        $classes [] = 'kb-amz-' . strtolower(str_replace(' ', '-', $theme->name));
        if (hasKbPostShortCode('listProduct')) {
            $classes[] = 'kb-amz-shortcode-listProduct';
        }
        $post = getKbAmzCurrentPost();
        if ($post) {
            if (getKbAmz()->isPostProduct($post->ID)) {
                $classes[] = 'kb-amz-post-product';
            } else {
                $classes[] = 'kb-amz-post-not-product';
            }
        } else {
            $classes[] = 'kb-amz-post-not-product';
        }
        
        if (getKbAmz()->getOption('replaceThumbnailWithGallery')) {
            $classes[] = 'kb-amz-replace-thumb-with-gallery';
        } else {
            $classes[] = 'kb-amz-not-replace-thumb-with-gallery';
        }
        
        if (getKbAmz()->isShortCodeActive('gallery')) {
            $classes[] = 'kb-amz-shortcode-gallry-active';
        } else {
            $classes[] = 'kb-amz-shortcode-gallry-not-active';
        }
        
        return $classes;
    }

    public function initDefaultScripts()
    { 
        if (is_admin()) {
            return;
        }
        wp_enqueue_style(
            'KbAmzStoreDefaultStyle',
            getKbPluginUrl() . '/template/default/css/style.css'
        );

        wp_enqueue_script(
            'KbAmzStoreStyle',
            getKbPluginUrl() . '/template/js/default.js',
            array('jquery')
        );
    }
    
    public function initDefaultAdminScripts()
    {
        wp_enqueue_style(
            'KbAmzAdminDefaultCss',
            getKbPluginUrl() . '/template/admin/css/default.css'
        );

        wp_enqueue_script(
            'KbAmzAdminJqueryUI',
            getKbPluginUrl() . '/template/admin/js/jquery-ui-1.10.4.custom.min.js',
            array('jquery')
        );

        wp_enqueue_script(
            'KbAmzAdminDefault',
            getKbPluginUrl() . '/template/admin/js/default.js',
            array('jquery')
        );
        
        $this->loadBootstrapScripts();
    }
    
    public function loadBootstrapScripts()
    {
        if (is_admin() && (!isset($_GET['page']) || $_GET['page'] != 'kbAmz')) {
            return;
        }
        
        wp_enqueue_script(
            'KbAmzBootstrapJs',
            getKbPluginUrl() . '/template/vendors/bootstrap/js/bootstrap.min.js',
            array('jquery')
        ); 
        
        wp_enqueue_style(
            'KbAmzBootstrapCss',
            getKbPluginUrl() . '/template/vendors/bootstrap/css/bootstrap.min.css'
        );
    }
}

$KbTemplate = getKbAmzTemplate();