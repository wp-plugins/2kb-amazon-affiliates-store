<?php
!defined('ABSPATH') and exit;

/**
 * 
 * @staticvar KbAmazonStore $kbAmazonStore
 * @return \KbAmazonStore
 */
function getKbAmz()
{
    static $kbAmazonStore;
    if (!$kbAmazonStore) {
        $kbAmazonStore = new KbAmazonStore;
    }
    return $kbAmazonStore;
}

class KbAmazonStore
{
    const PRICE_ATTRIBUTE = 'KbAmzFormattedPrice';
    const SUPPORT_EMAIL = 'support@2kblater.com';

    public static $container = array();
    
    protected $secret = 'INLINE';
    
    protected $productMeta = array();

    protected $shortCodes = array(
        'gallery' => array(
            'code' => 'kb_amz_product_gallery',
            'params' => array(
                
            ),
            'active' => false,
        ),
        'attributes' => array(
            'code' => 'kb_amz_product_attributes',
            'params' => array(
                
            ),
            'active' => true,
        ),
        'content' => array(
            'code' => 'kb_amz_product_content',
            'params' => array(
                'show_all_reviews' => '<b>Yes</b>/No',
                'show_title' => '<b>Yes</b>/No',
                'strip_tags' => '<b>No</b>/Yes',
                'replace' => '<b>No</b>/Yes - replace the short code result directly in the post'
            ),
            'active' => true,
        ),
        'similar' => array(
            'code' => 'kb_amz_product_similar',
            'params' => array(
                'count' => '1-6'
            ),
            'active' => true,
        ),
        'actions' => array(
            'code' => 'kb_amz_product_actions',
            'params' => array(
                
            ),
            'active' => true,
        ),
        'checkout' => array(
            'code' => 'kb_amz_checkout',
            'params' => array(
                
            ),
            'active' => true,
        ),
        'listProduct' => array(
            'code' => 'kb_amz_list_products',
            'params' => array(
                'featured' => '<b>No</b>/Yes',
                'featured_content_length' => '<b>300</b> / any number',
                'items_per_row' => '2,3,4,6',
                'posts_per_page' => 'Number',
                'pagination' => 'Yes/No',
                'category' => 'ID/Name, FUNCTION(), example: getKbAmzProductBottomCategory(), getKbAmzProductTopCategory(), the_category_ID(true)',
                'post_status' => 'Always - any',
                'title' => 'String',
                'attribute_key' => '(See Product Attributes)',
                'attribute_value' => '(Explore Attributes Value)',
                'attribute_compare' => "'=', '!=', '>', '>=', '<', '<=', 'LIKE', 'NOT LIKE', 'IN', 'NOT IN', 'BETWEEN'",
                'post_status' => '<b>publish</b> / any'
            ),
            'active' => true,
        ),
        'reviews' => array(
            'code' => 'kb_amz_product_reviews',
            'params' => array(
                'Version'  => '<b>Beta* Use to Test and Feedback Only</b>',
                'ID' => 'post ID, leave empty for current post',
                'title' => '<b>Emtpy</b> / String',
                'title_tag' => '<b>h3</b> / Any html tag',
                'width' => '<b>100%</b> / any valid css value',
                'height' => '<b>300px</b> / any valid css value',
            ),
            'active' => false,
        ) 
    );

    /**
     * Added in widgets itself
     * @var type
     */
    protected $widgetsTypes = array();

    protected $options = null;
    
    protected $productsCount;
    
    protected $publishedProductsCount;

    protected $isCronRunning = false;

    public function __construct() {
        
    }
    
    /**
     * 
     * @param type $bool
     * @return \KbAmazonStore
     */
    public function setIsCronRunnig($bool)
    {
        $this->isCronRunning = $bool;
        return $this;
    }
    
    public function isCronRunning()
    {
        return $this->isCronRunning;
    }

    public function getExceptions()
    {
        $errors = get_option('KbAmzExceptions', '');
        if (!empty($errors)) {
            $errors = unserialize($errors);
        } else {
            $errors = array();
        }
        return $errors;
    }


    public function addException($type, $exceptionStr)
    {
        $exceptions = get_option('KbAmzExceptions', '');
        if (!empty($exceptions)) {
            $exceptions = unserialize($exceptions);
        } else {
            $exceptions = array();
        }
        
        $exceptions = array_merge(array(array(
            'type' => $type,
            'date' => date('Y-m-d H:i:s'),
            'msg' => (string) $exceptionStr,
        )), $exceptions);
       
        $exceptions = array_slice($exceptions, 0, 1000);
        $value = serialize($exceptions);
        add_option('KbAmzExceptions', $value);
        update_option('KbAmzExceptions', $value);
        return $this;
    }

    public function getStoreId()
    {
//        $host = get_site_url();
//        $host = str_replace(
//            array('http://', 'www.'),
//            '',
//            $host
//        );
//        if (substr($host, -1) == '/') {
//            $host = substr($host, 0, -1);
//        }
        $parts = parse_url(get_site_url());
        $host = str_replace('www.', '', $parts['host']);
        return base64_encode($host);
    }

    public function getSecret()
    {
        return md5($this->getStoreId());
    }
    
    public function isPostProduct($id)
    {
        $meta = $this->getProductMeta($id);
        return isset($meta['KbAmzASIN']) && !empty($meta['KbAmzASIN']);
    }
    
    /**
     * 
     * @param type $asin
     * @return \KbAmazonStore
     */
    public function addProductForDownload($asin)
    {
        $productsToDownload = $this->getOption('ProductsToDownload', array());
        if (!in_array($asin, $productsToDownload)) {
            $productsToDownload[] = $asin;
        }
        $this->setOption('ProductsToDownload', $productsToDownload);
        return $this;
    }

    public function getCheckoutPage()
    {
        $pageIdOption = $this->getOption('CheckoutPage');
       
        if ($pageIdOption) {
            $page = get_page($pageIdOption);
            if ($page && is_object($page) && $page->post_status == 'publish') {
                return $page;
            }
        }
        $args = array(
            'meta_key' => 'KbAmzCheckoutPage',
            'meta_value' => '1',
            'post_type' => 'page'
        );
        $posts = get_posts($args);
        wp_reset_query();
        $page = isset($posts[0]) ? $posts[0] : null;
        if (!$page) {
            $pageId =
            wp_insert_post(array(
                'post_status' => 'publish',
                'post_type' => 'page',
                'post_title' => 'Checkout',
                'post_content' => $this->getShortCode('checkout')
            ));
            add_post_meta($pageId, 'KbAmzCheckoutPage', '1');
            $this->setOption('CheckoutPage', $pageId);
        }
        if (!$pageIdOption) {
            $this->setOption('CheckoutPage', $page->ID);
        }
        return $page;
    }
    
    public function getAmazonTopDisabledCategories()
    {
        $cats = array();
        $disabled = explode(',', $this->getOption('disableImportInTopCategories', ''));
        foreach ($disabled as $name) {
            $name = trim($name);
            if (!empty($name)) {
                $cats[] = $name;
            }
        }
        return $cats;
    }
    
    public function getAmazonTopEnabledCategories()
    {
        $cats = array();
        $disabled = explode(',', $this->getOption('enableImportInTopCategories', ''));
        foreach ($disabled as $name) {
            $name = trim($name);
            if (!empty($name)) {
                $cats[] = $name;
            }
        }
        return $cats;
    }
    
    public function getExcludeDisabledAttributes()
    {
        return array(
            'KbAmzFormattedPrice',
            'KbAmzSimilarProducts',
            'KbAmzASIN',
            'KbAmzItemAttributes.Title',
            'KbAmzLastUpdateTime',
            'KbAmzPriceAmount',
            'KbAmzItemAttributes.ListPrice.Amount',
            'KbAmzItemAttributes.NumberOfItems',
            'KbAmzOnSaleProduct',
            'KbAmzNewProduct',
            'KbAmzFreeProduct',
            'KbAmzItemAttributes.ReleaseDate',
            'KbAmzItemAttributes.PublicationDate',
            
        );
    }

        public function getWidgetsTypes()
    {
        return $this->widgetsTypes;
    }
    
    public function addWidgetsType($type, $params)
    {
        return $this->widgetsTypes[$type] = $params;
    }
    
    public function getOptions($reload = false)
    {
        if (null == $this->options || $reload) {
            $opt = get_option('kbAmzStore', array());
            if (!empty($opt)) {
                $opt = json_decode(base64_decode($opt), true);
            }
            $this->options = $opt;
        }
        return $this->options;
    }

    public function getOption($name, $default = '')
    {
        $options = $this->getOptions();
        $result = null;
        if (strpos($name, '.') === false) {
            $result = isset($options[$name])
                   && $options[$name] != '' && $options[$name] != null
                   ? $options[$name] : null;
        } else {
            $paths = explode('.', $name);
            $value = null;
            $values = $options;
            foreach ($paths as $key => $path) {
                if (isset($values[$path])) {
                    $value = $values[$path];
                    if (is_array($value)) {
                        $values = $values[$path];
                    }
                    unset($paths[$key]);
                }
            }
            if (count($paths) == 0) {
                $result = $value;
            }
        }
        
        if ($result === null) {
            $defaults = getKbAmzDefaultOptions();
            $result = isset($defaults[$name]) && $default === ''
                      ? $defaults[$name] : $default;
        }
        return $result;
    }
    
    public function setOption($key, $val)
    {
        // load the options
        $this->getOptions(true);
        $this->options[$key] = $val;
        $opts = base64_encode(json_encode($this->options));
        add_option('kbAmzStore', $opts);
        update_option('kbAmzStore', $opts);
        return $this;
    }
    
    public function setOptions(array $data)
    {
        foreach ($data as $name => $val) {
            $this->setOption($name, $val);
        }
    }

    public function getDefaultAttributes()
    {
        return
        array(
            'KbAmzFormattedPrice' => __('Price')
        );
    }

    public function getShortCodeAttributes()
    {
        $attributes = $this->getOption('productAttributes', array());
        return empty($attributes) ? $this->getDefaultAttributes() : $attributes;
    }

    public function getShortCode($code, $mixed = false)
    {
        if (isset($this->shortCodes[$code])) {
            $codeName = $this->shortCodes[$code]['code'];
            if ($mixed === true) {
                return $codeName;
            } else if (is_array($mixed)) {
                return kbAmzShortCodeAttrToStr($codeName, $mixed);
            } else {
                return '[' . $codeName . ']';
            }
        }
    }
    
    public function isShortCodeActive($code)
    {
         $codes = $this->getOption('productShortCodes');
         return isset($codes[$code]['active']) && $codes[$code]['active'];
    }

    public function getShortCodes()
    {
        $codes = $this->getOption('productShortCodes');
        $updated = $this->shortCodes;
        foreach ($updated as $name => $values) {
            if (isset($codes[$name])) {
                $updated[$name] = array_merge($values, $codes[$name]);
            }
        }
        return $updated;
    }
    
    public function getShortCodePostContent()
    {
        $content = $this->getOption('shortCodePostContent');
        if (!empty($content)) {
            return $content;
        }
        $content = <<<HTML
%s %s
%s
%s
%s
%s
HTML;

        return sprintf(
            $content,
            getKbAmz()->getShortCode('gallery'),
            getKbAmz()->getShortCode('attributes'),
            getKbAmz()->getShortCode('actions'),
            getKbAmz()->getShortCode('content', array('replace' => 'Yes')),
            getKbAmz()->getShortCode('reviews'),
            getKbAmz()->getShortCode('similar')
        );
    }

    public function getProductMeta($id, $refresh = false)
    {
        if (!isset($this->productMeta[$id]) || $refresh) {
            $meta = get_post_meta($id);
            $meta = $meta ? $meta : array();
            foreach ($meta as $key => $val) {
                if (is_array($val)) {
                    $meta[$key] = $val[0];
                }
            }
            
            if (isset($meta['KbAmzSimilarProducts'])) {
                $meta['KbAmzSimilarProducts'] = unserialize($meta['KbAmzSimilarProducts']);
            }
            
            $this->productMeta[$id] = $meta;
            $this->productMeta[$id]['KbAmzFormattedPrice'] = $this->getProductPriceHtml($id);
        }
        return $this->productMeta[$id];
    }

    public function getSimilarProductsHtml($id) {
        $posts = getKbAmz()->getSimilarProducts($id);
        $data = array();
        $data['posts'] = $posts;
        return new KbView($data, KbAmazonStorePluginPath . 'template/view/similar-products');
    }

    public function getProductMetaArray($id)
    {
       $meta = $this->getProductMeta($id);
       $attr = array();
       foreach ($meta as $key => $value) {
           if (strpos($key, '.') !== false) {
               $this->set_val($attr, $key, $value);
           } else {
               $attr[$key] = $value;
           }
       }
       return $attr;
    }
    
    protected function set_val(array &$arr, $path, $val)
    {
       $loc = &$arr;
       foreach(explode('.', $path) as $step)
       {
         $loc = &$loc[$step];
       }
       return $loc = $val;
    }


    public function getProductImages($id)
    {
        static $cache;
        if (!isset($cache[$id])) {
            $args = array(
              'post_type' => 'attachment',
              'numberposts' => -1,
              'post_parent' => $id,
              'order' => 'asc'
             );
             $images = get_posts($args);
             reset($images);
             wp_reset_query();
             foreach ($images as $key => $img) {
                 $meta = get_post_meta($img->ID);
                  foreach ($meta as $k => $val) {
                      if (isset($val[0])) {
                          $meta[$k] = $val[0];
                      }
                  }
                  if (isset($meta['_wp_attachment_metadata'])) {
                      $meta['_wp_attachment_metadata'] = unserialize($meta['_wp_attachment_metadata']);
                  }
                 $img->amzMeta = $meta;
                 $images[$key] = new KbAmazonImage($img);
             }

            $cache[$id] = new KbAmazonImages($images, $id);
        }
        return $cache[$id];
    }
    
    
    public function getSimilarProducts($id)
    {
        $meta = $this->getProductMeta($id);
        $products = array();
        if (isset($meta['KbAmzSimilarProducts']) && !empty($meta['KbAmzSimilarProducts'])) {
            $productsToDownload = $this->getOption('ProductsToDownload', array());
            foreach ($meta['KbAmzSimilarProducts'] as $asin) {
                $product = $this->getProductByAsin($asin);
                if ($product) {
                    $products[] = $product;
                } else {
                    if (!in_array($asin, $productsToDownload)) {
                        $productsToDownload[] = $asin;
                    }
                }
            }
            $loadSimilar = getKbAmz()->getOption('loadSimilarItems', KbAmazonImporter::SIMILAR_ITEM_LOAD_NO);
            if ($loadSimilar != KbAmazonImporter::SIMILAR_ITEM_LOAD_NO) {
                $this->setOption('ProductsToDownload', $productsToDownload);
            }
        }
        return $products;
    }
    
    public function getProductsFromMeta($metaKey, $metaValue)
    {
        static $cache;
        $args = array(
            'post_status' => 'any',
            'meta_query' => array(
                array(
                    'key' => $metaKey,
                    'value' => $metaValue
                )
            ),
        );
        $key = serialize($args);
        if (!isset($cache[$key])) {
            $cache[$key] = get_posts($args);
            wp_reset_query();
        }
        return $cache[$key];
    }
    
    public function getAttachmentsFromMeta($metaKey, $metaValue)
    {
        $args = array(
            'post_type' => 'attachment',
            'meta_query' => array(
                array(
                    'key' => $metaKey,
                    'value' => $metaValue
                )
            ),
        );
        $posts = get_posts($args);
        wp_reset_query();
        return $posts;
    }
    
    public function isProductAvailable($id)
    {
        
        /*else if (isset($meta['KbAmzOfferSummary.TotalUsed'])
        && $meta['KbAmzOfferSummary.TotalUsed'] > 0) {
            return true;
        } else if (isset($meta['KbAmzOfferSummary.TotalCollectible'])
        && $meta['KbAmzOfferSummary.TotalCollectible'] > 0) {
            return true;
        } else if (isset($meta['KbAmzOfferSummary.TotalRefurbished'])
        && $meta['KbAmzOfferSummary.TotalRefurbished'] > 0) {
            return true;
        } */
        
        $meta = $this->getProductMeta($id);
        if (isset($meta['KbAmzOfferSummary.TotalNew'])
        && $meta['KbAmzOfferSummary.TotalNew'] > 0) {
            return true;
        } else {
            return $this->isProductFree($id);
        }
        return false;
    }
    
    public function getProductByAsin($asin)
    {
        global $wpdb;
        $sql = "
           SELECT post_id
           FROM $wpdb->postmeta
           WHERE meta_key = 'KbAmzASIN' AND meta_value = '$asin'
        ";
        
        $result = $this->getSqlResult($sql);
        return isset($result[0]) ? get_post($result[0]->post_id) : null;
    }
    
    function getCurrentCategoryId()
    {
        $cat = $this->getCurrentCategory();
        return $cat ? $cat->term_id : null;
    }
    
    function getCurrentCategory()
    {
        $cat = get_query_var('cat');
        // var_dump($cat);die;
        return empty($cat) ? null : get_category($cat);
    }
    
    function isCurrentCategory($id)
    {
        $curr = $this->getCurrentCategory();
        if ($curr && $curr->term_id == $id) {
            return true;
        }
    }
    
    public function getProductContent($id)
    {
        $meta = $this->getProductMeta($id);
        return isset($meta['KbAmzEditorialReviews.EditorialReview.0.Content'])
               ? $meta['KbAmzEditorialReviews.EditorialReview.0.Content'] : '';
    }

    public function getCartButtonHtml($id)
    {
        $id = $id ? $id : get_the_ID();

        if (getKbAmz()->inCart($id)) {
            return getKbAmz()->getCheckoutButtonHtml();
        }
        return sprintf(
            '<button title="%s" class="kb-add-to-cart button kb-cart-action not-loading animate%s" data-url="%s/wp-admin/admin-ajax.php" data-id="%s">
                <span class="glyphicon glyphicon-shopping-cart"></span>
                <span class="button-text">%s</span>
                <img src="%s" alt="loading" class="loading" />
             </button>',
            __("Add To Cart"),
            (!$this->isProductAvailable($id) ? ' disabled' : ''),
            get_bloginfo('url'),
            $id,
            __('Add to cart'),
            getKbPluginUrl('template/images/loader.gif')
        );
    }
    
    public function getCheckoutButtonHtml()
    {
        return sprintf(
            '<button title="%s" class="kb-checkout button kb-cart-action" onclick="document.location.href=\'%s\'">
                <span class="glyphicon glyphicon glyphicon-check"></span>
                <span class="button-text">%s</span>
             </button>',
            __("Checkkout"),
            get_permalink($this->getCheckoutPage()->ID),
            __('Checkout')
        );
    }
    
    public function inCart($id)
    {
        $meta = $this->getProductMeta($id);
        
        $cart = $this->getSessionCart();
       if (isset($cart['CartItems']['CartItem']) && !empty($cart['CartItems']['CartItem'])) {
           foreach ($cart['CartItems']['CartItem'] as $item) {
               if ($item['ASIN'] == $meta['KbAmzASIN']) {
                   return true;
               }
           }
       }
       return false;
    }
            
    function getProductPriceHtml($id)
    {
        if (!$this->isProductAvailable($id)) {
            return '<span class="kb-amz-out-of-stock">'.__('Out of stock').'</span>';
        }
        $meta = $this->getProductMeta($id);
        $price = $meta['KbAmzPriceAmountFormatted'];
        $lowest = isset($meta['KbAmzOfferSummary.LowestNewPrice.FormattedPrice'])
                ? $meta['KbAmzOfferSummary.LowestNewPrice.FormattedPrice'] : 0;
        
        $listPrice = 0;
        if (isset($meta['KbAmzItemAttributes.ListPrice.FormattedPrice'])
        && !empty($meta['KbAmzItemAttributes.ListPrice.FormattedPrice'])) {
            $listPrice = $meta['KbAmzItemAttributes.ListPrice.FormattedPrice'];
        }
        
        if (getKbAmz()->getOption('enableSalePrice', 1)
        && $lowest && $lowest != $price) {
            return sprintf(
                '<del>%s</del>&nbsp;<ins>%s</ins>',
                $price,
                $lowest
            );
        } else if ($price) {
            
            if (getKbAmz()->getOption('enableSalePrice', 1)
            && $listPrice
            && $listPrice != $price) {
                return sprintf(
                    '<del>%s</del>&nbsp;<ins>%s</ins>',
                    $listPrice,
                    $price
                );
            } else {
                return sprintf(
                    '<ins>%s</ins>',
                    $price
                );
            }
        } else {
            return sprintf(
                '<ins>%s</ins>',
                __('Free')
            ); 
        }
    }
    
    function getProductSticker($id)
    {
        $meta = $this->getProductMeta($id);
        $label = '';
        if ($this->isProductNew($meta)) {
            $label = '<span class="kb-amz-item-sticker-new kb-amz-item-sticker">'.__('New').'</span>';
        } else if ($this->isProductSale($meta)) {
            $label =  '<span class="kb-amz-item-sticker-sale kb-amz-item-sticker">'.__('Sale').'</span>';
        }
        return $label;
    }
    
    public function isProductNew($mixed, $extract = false)
    {
        $meta = is_array($mixed) ? $mixed : $this->getProductMeta($mixed);
        if ($extract) {
            if (isset($meta['KbAmzItemAttributes.PublicationDate'])
            && strtotime($meta['KbAmzItemAttributes.PublicationDate']) > strtotime('-1month')){
                return true;
            }
            return false;
        }
        return isset( $meta['KbAmzNewProduct']) &&  $meta['KbAmzNewProduct'] == 'yes';
    }
    
    public function isProductSale($mixed, $extract = false)
    {
        $meta = is_array($mixed) ? $mixed : $this->getProductMeta($mixed);
        if ($extract) {
            $price = $meta['KbAmzPriceAmountFormatted'];
            $lowest = isset($meta['KbAmzOfferSummary.LowestNewPrice.FormattedPrice'])
                    ? $meta['KbAmzOfferSummary.LowestNewPrice.FormattedPrice'] : 0;
            if ($lowest && $price != $lowest) {
                return true;
            }
            return false;  
        }
        return $meta['KbAmzOnSaleProduct'] == 'yes';
    }
    
    public function isProductFree($mixed, $extract = false)
    {
        $meta = is_array($mixed) ? $mixed : $this->getProductMeta($mixed);
        if ($extract) {
            return empty($meta['KbAmzPriceAmount']);
        }
        return isset($meta['KbAmzFreeProduct']) && $meta['KbAmzFreeProduct'] == 'yes';
    }

    public function getAttributes()
    {
        global $wpdb;
        
        $sql = "
           SELECT meta_key, COUNT(meta_key) AS count
           FROM $wpdb->postmeta
           WHERE meta_key LIKE 'KbAmz%'
           GROUP BY meta_key
           ORDER BY count DESC
        ";
        
        $rows = $this->getSqlCachedResult($sql);
        
        $attributes = array();
        foreach ($rows as $row) {
           $attributes[] =  $row->meta_key;
        }
        kbAmzFilterAttributes($attributes);
        $data = array();
        foreach ($rows as $row) {
            if (in_array($row->meta_key, $attributes)) {
                $data[$row->meta_key] = $row->meta_key . ' <span class="badge"><span class="kbbr">(</span>'.$row->count.'<span class="kbbr">)</span></span>';
            }
        }
        if (isset($data['KbAmzSimilarProducts'])) {
            unset($data['KbAmzSimilarProducts']);
        }
        return $data;
    }
    
    public function getMinMaxMetaWidgetMethod($metaKey)
    {
        global $wpdb;
        $join = array(
            "JOIN $wpdb->posts AS p ON p.ID = t.post_id"
        );
        
        $where = array(
            "t.meta_key = '$metaKey'",
            "t.meta_value IS NOT NULL",
            "t.meta_value != ''"
        );
        
        $catId = getKbAmz()->getCurrentCategoryId();
        if ($catId) {
            $join[] = "JOIN $wpdb->term_relationships AS tr ON t.post_id = tr.object_id";
            $where[] = "tr.term_taxonomy_id = '$catId'"; 
        }

        $this->mergeWidgetFilters(KbAmzWidget::getWidgetsFilters(), $join, $where);
        
        $sql = "
           SELECT MIN(CAST(t.meta_value AS DECIMAL)) AS min, MAX(CAST(t.meta_value AS DECIMAL)) AS max
           FROM $wpdb->postmeta AS t
           ".  implode(' ', $join)."
           WHERE " . implode(' AND ', $where) . "
           LIMIT 1
        ";
        //echo $sql;die;
        $rows = $this->getSqlCachedResult($sql);
        if (!empty($rows)) {
            $min = $rows[0]->min ? $rows[0]->min : 0;
            $max = $rows[0]->max ? $rows[0]->max : 0;
            return array(
                $min,
                $max
            );
        }
        return array(0, 0);
    }
    
    /**
     * 
     * @global type $wpdb
     * @param type $metaKey
     * @param type $count
     * @param type $order = count / name
     * @return type
     */
    public function getMetaCountListWidgetMethod($metaKey, $count = 10, $order = 'count')
    {
        global $wpdb;
        $join = array(
            "JOIN $wpdb->posts AS p ON p.ID = t.post_id"
        );
        $where = array(
            "t.meta_key = '$metaKey'",
            "t.meta_value IS NOT NULL",
            "t.meta_value != ''"
        );
        
        $catId = getKbAmz()->getCurrentCategoryId();
        if ($catId) {
            $join[] = "JOIN $wpdb->term_relationships AS tr ON t.post_id = tr.object_id";
            $where[] = "tr.term_taxonomy_id = '$catId'"; 
        }
        
        $order = $order == 'count' ? 'count' : 't.meta_value';
        $orderType = $order == 'count' ? 'DESC' : 'ASC';
        $count = empty($count) ? 10 : $count;
        
        $sql = "
           SELECT t.meta_id, t.meta_key, t.meta_value, count(t.meta_value) as count
           FROM $wpdb->postmeta AS t
           " . implode(' ', $join) . "
           WHERE " . implode(' AND ', $where) . "
           GROUP BY t.meta_value
           ORDER BY $order $orderType
           LIMIT $count
        ";
        
        $defaultValues = $this->getSqlCachedResult($sql);

        $this->mergeWidgetFilters(KbAmzWidget::getWidgetsFilters(), $join, $where);
        
        $sql = "
           SELECT t.meta_id, t.meta_key, t.meta_value, count(t.meta_value) as count
           FROM $wpdb->postmeta AS t
           " . implode(' ', $join) . "
           WHERE " . implode(' AND ', $where) . "
           GROUP BY t.meta_value
           ORDER BY $order $orderType
           LIMIT $count
        ";
        
        $filteredValues = $this->getSqlCachedResult($sql);
        foreach ($defaultValues as &$row) {
            $inFiltered = false;
            foreach ($filteredValues as $srow) {
                if ($srow->meta_id == $row->meta_id) {
                    $inFiltered = true;
                    break;
                }
            }
            if (!$inFiltered){
                $row->count = 0;
            }
        }
        return $defaultValues;
    }
    
    public function deleteAttributesByKey(array $attributes)
    {
        global $wpdb;
        $wpdb->query("DELETE FROM $wpdb->postmeta WHERE meta_key IN('".  implode("','", $attributes)."')");
    }
    
    public function updateProductsStatus($status)
    {
        global $wpdb;
        $sql = "
           UPDATE
           $wpdb->posts AS p
           INNER JOIN $wpdb->postmeta AS t ON p.ID = t.post_id AND t.meta_key = 'KbAmzASIN'
           SET p.post_status = '$status'
        ";
        $wpdb->query($sql);
    }

    public function updateAllProductsContent($content)
    {
        global $wpdb;
        $content = $wpdb->escape($content);
        
        $sql = "
           UPDATE
           $wpdb->posts AS p
           INNER JOIN $wpdb->postmeta AS t ON p.ID = t.post_id AND t.meta_key = 'KbAmzASIN'
           SET p.post_content = '$content'
        ";
        $wpdb->query($sql);
    }
    
    public function getProductsWithNoQuantity()
    {
        global $wpdb;
        
        $sql = "
           SELECT t.ID
           FROM $wpdb->posts AS t
           JOIN $wpdb->postmeta AS t1 ON t.ID = t1.post_id AND t1.meta_key = 'KbAmzOfferSummary.TotalNew'
           WHERE t.post_status ='pending' AND t1.meta_value <= 0
           ORDER BY t.post_modified ASC
        ";
        
        return $this->getSqlResult($sql);
    }

    public function getProductsCount($addUp = null)
    {
        if (null === $this->productsCount) {
            global $wpdb;
            $sql = "
               SELECT COUNT(DISTINCT t.post_id) AS count
               FROM $wpdb->postmeta AS t
               JOIN $wpdb->posts AS p ON p.ID = t.post_id
               WHERE t.meta_key = 'KbAmzASIN'
            ";
            $result = $this->getSqlResult($sql);
            $this->productsCount = isset($result[0]) ? $result[0]->count : 0;
        }
        
        return $this->productsCount;
    }
    
    public function getPublishedProductsCount()
    {
        if (null === $this->publishedProductsCount) {
            global $wpdb;
            $sql = "
               SELECT COUNT(DISTINCT t.post_id) AS count
               FROM $wpdb->postmeta AS t
               JOIN $wpdb->posts AS p ON p.ID = t.post_id
               WHERE t.meta_key = 'KbAmzASIN' AND p.post_status = 'publish'
            ";
            $result = $this->getSqlResult($sql);
            $this->productsCount = isset($result[0]) ? $result[0]->count : 0;
        }
        
        return $this->productsCount;
    }
    
    public function getProductsToDownloadCount()
    {
        $pr =  getKbAmz()->getOption('ProductsToDownload', array());
        return count($pr);
    }
    
    public function getProductsToUpdateCount()
    {
        static $count;
        if (null === $count){
            $pr =  getKbAmz()->getProductsAsinsToUpdate();
            $count = count($pr);
        }
        return $count;
    }

    public function addProductCount($addup)
    {
        $this->productsCount += $addup;
    }

    public function isMaxProductsCountReached()
    {
        $maxProductsCount = getKbAmz()->getOption('maxProductsCount');
        $productsCount = getKbAmz()->getProductsCount();
        if ($productsCount >= $maxProductsCount) {
            return true;
        }
        return false;
    }



    public function getMetaDataById($metaId)
    {
        global $wpdb;
        $sql = "
           SELECT *
           FROM $wpdb->postmeta AS t
           WHERE meta_id = $metaId
           LIMIT 1
        ";

        $rows = $this->getSqlResult($sql);
        return isset($rows[0]) ? $rows[0] : null;
    }

    public function clearAllProducts()
    {
        set_time_limit(360);
        global $wpdb;
        $sql = "
           SELECT DISTINCT post_id
           FROM $wpdb->postmeta
           WHERE meta_key = 'KbAmzASIN' 
           OR meta_key = 'KbAmzAttachmentASIN'
        ";
        
        $result = $this->getSqlResult($sql);
        foreach ($result as $row) {
            $this->clearProduct($row->post_id);
        }
    }
    
    public function clearProduct($postId)
    {
        wp_delete_post($postId, true);
        wp_delete_attachment($postId, true);
    }


    public function getProductsAsinsToUpdate()
    {
        global $wpdb;
        $add = getKbAmz()->getOption('uproductsUpdateOlderThanHours', KbAmazonImporter::SECONDS_BEFORE_UPDATE);
        $time = time() - $add;
        
        $sql = "
           SELECT t1.meta_value AS asin
           FROM $wpdb->posts AS t
           JOIN $wpdb->postmeta AS t1 ON t.ID = t1.post_id AND t1.meta_key = 'KbAmzASIN'
           WHERE t.post_modified < '".date('Y-m-d H:i:s', $time)."'
           ORDER BY t.post_modified ASC
        ";
        //echo $sql;die;
        $result = $this->getSqlResult($sql);

        $asins = array();
        foreach ($result as $row) {
           $asins[] = $row->asin;
        }
        return $asins;
    }
     
    protected function getSqlCachedResult($sql)
    {
        global $wpdb;
        if (!$rows = $this->getCache($sql)) {
            $rows = $wpdb->get_results($sql);
            $this->setCache($sql, $rows);
        }
        return $rows;
    }
    
    protected function getSqlResult($sql)
    {
        global $wpdb;
        return $wpdb->get_results($sql);
    }
    
    protected function mergeWidgetFilters($filters, &$join, &$where)
    {
        if (!empty($filters)) {
            foreach ($filters as $filter) {
                if (isset($filter['join'])) {
                    $join = array_merge($join, $filter['join']);
                }
                if (isset($filter['where'])) {
                    $where = array_merge($where, $filter['where']);
                }
            }
        }
    }

    public function getCache($key)
    {
        if (isset($_SESSION['2kb-amazon-affiliates-store']['cache'])) {
            if (!is_array($_SESSION['2kb-amazon-affiliates-store']['cache'])) {
                $_SESSION['2kb-amazon-affiliates-store']['cache']
                = unserialize($_SESSION['2kb-amazon-affiliates-store']['cache']);
            }
            if (isset($_SESSION['2kb-amazon-affiliates-store']['cache'][$key])) {
                return $_SESSION['2kb-amazon-affiliates-store']['cache'][$key];
            }
        }
        
        $key = sha1($key);
        return wp_cache_get($key);
    }
    
    public function setCache($key, $data)
    {
        $key = sha1($key);
        $data = empty($data) ? null : $data;
        
        if (isset($_SESSION['2kb-amazon-affiliates-store']['cache'])) {
            if (!is_array($_SESSION['2kb-amazon-affiliates-store']['cache'])) {
                $_SESSION['2kb-amazon-affiliates-store']['cache']
                = unserialize($_SESSION['2kb-amazon-affiliates-store']['cache']);
            }
             $_SESSION['2kb-amazon-affiliates-store']['cache'][$key] = $data;
        }
        wp_cache_set($key, $data);
    }
    
    public function removeFromAjaxCart()
    {
        $response = array(
            'success' => false,
        );
        if (isset($_POST['id'])) {
            $post = get_post((int) $_POST['id']);
            wp_reset_query();
            if ($post) {
		if (!session_id()) {
                    session_start();
                }
                $cart = $this->getSessionCart();
                $meta = $this->getProductMeta($post->ID);

                if (isset($cart['CartItems']['CartItem'])) {
                    foreach ($cart['CartItems']['CartItem'] as $key => $item) {
                        if ($item['ASIN'] == $meta['KbAmzASIN']) {
                            getKbAmazonApi()->responseGroup('Cart')->cartUpdate($item['CartItemId'], 0);
                            unset($cart['CartItems']['CartItem'][$key]);
                        }
                    }
                }
                
                if (!isset($cart['CartItems']['CartItem']) || empty($cart['CartItems']['CartItem'])) {
                    $cart = null;
                    getKbAmazonApi()->cartKill();
                }
                $_SESSION['KbAmzCart'] = $cart;
                $response['success'] = true;
                $response['cart'] = $this->getCartCheckoutButtonHtml(true);
            }
        }
        if (!$response['success']) {
            $response['msg'] = 'Something went wrong. Please try again.';
        }
        
        echo json_encode($response);
        exit;
    }

    public function addToAjaxCart()
    {
        $response = array(
            'success' => false,
        );
        if (isset($_POST['id'])) {
            $post = get_post((int) $_POST['id']);
            wp_reset_query();
            if ($post) {
		if (!session_id()) {
                    session_start();
                }
                $meta = $this->getProductMeta($post->ID);
                $params = array();
                $params[] = array(
                    'Quantity' => 1,
                    'ASIN' => $meta['KbAmzASIN']
                );
                $cart = getKbAmazonApi()->responseGroup('Cart')->cartThem($params);
                $cart = isset($cart['Cart']) ? $cart['Cart'] : $cart;
                if (isset($cart['Request']['Errors'])) {
                    $response['msg'] = isset($cart['Request']['Errors']['Error']['Message'])
                        ? $cart['Request']['Errors']['Error']['Message']
                        : 'Unable to add this product to the cart. Please contact the shop administrator.';
                    $this->addProductForDownload($meta['KbAmzASIN']);
                    $response['title'] = __('Info');
                    $info = '';
                    if (isset($meta['KbAmzDetailPageURL'])) {
                        $info .= sprintf(
                            '<br/><b><a href="%s" target="_blank">%s</a></b>',
                            $meta['KbAmzDetailPageURL'],
                            __('Checkout on Amazon')
                        );
                    }
                    $response['content'] = sprintf(
                        '<small>%s</small>%s',
                        $response['msg'],
                        $info
                    );
                } else {
                    $_SESSION['KbAmzCart'] = $cart;
                    $response['success'] = true;
                    $response['cart'] = $this->getCartCheckoutButtonHtml(true);
                    $response['button'] = $this->getCheckoutButtonHtml();
                    
                }
            }
        }
        if (!$response['success'] && !isset($response['msg'])) {
            $response['msg'] = 'Something went wrong. Please try again.';
        }
        
        echo json_encode($response);
        exit;
    }
    
    public function getSessionCart()
    {
        if (!session_id()) {
            session_start();
        }
        return isset($_SESSION['KbAmzCart']) ? $_SESSION['KbAmzCart'] : null;
    }

    public function getCartCheckoutButtonHtml($setActive = false)
{
        $cart = $this->getSessionCart();
  
        $itemsCount = 0;
        $totalPrice = '0.00';
        
        $items = array();
        $itemHtml = <<<HTML
<div class="row kb-mini-cart-item mg-5">
    <div class="col-md-2">
        %s
    </div>
    <div class="col-md-10 kb-title">
        <a href="%s">%s x1 <b>%s</b></a>
        <a href="#kb-remove-item" class="kb-mini-cart-item-remove" data-url="%s/wp-admin/admin-ajax.php" data-id="%s">
            <span class="glyphicon glyphicon-remove"></span>
            %s
        </a>
    </div>
</div>
HTML;
        
        
        if (isset($cart['CartItems']['CartItem'])) {
            $itemsCount = count($cart['CartItems']['CartItem']);
            
            foreach ($cart['CartItems']['CartItem'] as $item) {
                $post = $this->getProductByAsin($item['ASIN']);
                $items[] = sprintf(
                    $itemHtml,
                    get_the_post_thumbnail($post->ID, 'post-thumbnail'),
                    get_permalink($post->ID),
                    $post->post_title,
                    $item['ItemTotal']['FormattedPrice'],
                    get_site_url(),
                    $post->ID,
                    __('remove')
                );
            }
        }
        
        if (isset($cart['SubTotal']['FormattedPrice'])) {
            $totalPrice = $cart['SubTotal']['FormattedPrice'];
        }
        
        $html = <<<HTML
        <div id="cart" class="%s kb-amz-cart %s">
            <div class="cart-heading">
                <span class="glyphicon glyphicon-shopping-cart"></span>
                <b>%s:</b>
                <span id="cart-total">%s item(s) %s</span>
                <span class="caret"></span>
            </div>
            <div class="cart-content">
                <div class="content-scroll">
                    <div class="full">
                        %s
                        <div class="kb-mini-cart-total"><b>%s:</b> %s</div>
                        <a href="%s" class="button kb-button animate"><span class="animate">%s</span></a>
                    </div>
                    <div class="empty">%s</div>
                </div>
            </div>
        </div>
HTML;
        
        return sprintf(
            $html,
            !empty($items) ? 'has-item' : 'no-items',
            $setActive ? 'active' : '',
            __('Cart'),
            $itemsCount,
            $totalPrice,
            implode('', $items),
            __('Total'),
            $totalPrice,
            get_permalink($this->getCheckoutPage()->ID),
            __('Checkout'),
            __('Your shopping cart is empty!')
        );
    }

    public function getImageSizes()
    {
        $sizes = array();
        foreach (get_intermediate_image_sizes() as $size) {
            $sizes[$size] = $size;
        }
        return $sizes;
    }

    public function getCategoryDescriptionMarkup()
    {
        return '';// '###KbAmz_DELETE_MARKER_FOR_UNINSTALL_WILL_NOT_SHOW_IN_FRONTEND###';
    }




    public function uninstall()
    {
        set_time_limit(360);
        
        foreach (array('kbAmzStore', 'KbAmzExceptions') as $option_name) {
            if (!is_multisite()) {
                delete_option($option_name);
            } else {
                global $wpdb;
                $blog_ids = $wpdb->get_col("SELECT blog_id FROM $wpdb->blogs");
                $original_blog_id = get_current_blog_id();
                foreach ($blog_ids as $blog_id) {
                    switch_to_blog($blog_id);
                    delete_option($option_name);
                }
                switch_to_blog($original_blog_id);
                delete_site_option($option_name);
            }
        }
        wp_delete_post($this->getCheckoutPage()->ID, true);
        $this->clearAllProducts();
    }
}

