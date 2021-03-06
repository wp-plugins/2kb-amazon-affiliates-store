<?php
!defined('ABSPATH') and exit;

// [kb_amz_product]
function kb_amz_product_func($atts)
{
	$atts = shortcode_atts( array(
        'ID'            => null,
        'asin'          => null,
        'variations'    => true,
        'image_width'   => null,
        'image_height'  => null,
	), $atts);
    $atts['postId']         = $atts['ID'];
    $atts['variations']     = kbAmzShortCodeBool($atts['variations']);
    
    if (!$atts['postId'] && !$atts['asin']) {
        return new KbAmzErrorString('[kb_amz_product] - ID or asin parameter is required.');
    }
    
    return getKbAmzAdminController()->shortCodeProductAction($atts);
}

add_shortcode('kb_amz_product', 'kb_amz_product_func');

// [kb_amz_product_attributes]
function kb_amz_product_attributes_func($atts) {
	$atts = shortcode_atts( array(
        'postId' => get_the_ID()
	), $atts);
        
        $shortCodes = getKbAmz()->getShortCodes();
        if (isset($shortCodes['attributes']['active'])
        && !$shortCodes['attributes']['active']) {
            return;
        }
        
        $html = <<<HTML
<div class="kb-product-attributes">
%s
</div>
HTML;
        
        $htmlList = <<<HTML
<div class="%s">
    <span class="kb-product-attribute-label">%s</span> : <span class="kb-product-attribute-value%s">%s</span>
</div>
HTML;
        
        
        $meta = getKbAmz()->getProductMeta($atts['postId']);
        $attributes = getKbAmz()->getShortCodeAttributes();
        $markup = '';
        foreach ($attributes as $attr => $label) {
            if (isset($meta[$attr]) && !empty($meta[$attr])) {
                $markup .= sprintf(
                    $htmlList,
                    strtolower(str_replace('.', '-', $attr)),
                    $label,
                    ($attr == KbAmazonStore::PRICE_ATTRIBUTE ? ' kb-amz-item-price' : ''),
                    $meta[$attr]
                );
            }
        }
        
        if (empty($markup)) {
            return;
        }
        return sprintf(
            $html,
            $markup    
        );
}
add_shortcode( 'kb_amz_product_attributes', 'kb_amz_product_attributes_func' );



function getKbProductGalleryContent($atts)
{
        $html = <<<HTML
<div class="kb-amz-shortcode-product-images kb-amz-shortcode-product-gallery %s %s">
    %s
    %s
</div>
HTML;
        $images = getKbAmz()->getProductImages(get_the_ID());
        if (count ($images) > 1) {
            $imagesHtml = $images->getImagesHtml($atts['size']);
        } else {
            $imagesHtml = $images->getImagesHtmlSkipFirst($atts['size']);
        }
        
        $thumb = $images->getFirst($atts['size']);
        if (empty($thumb)) {
            return;
        }
        return sprintf(
            $html,
            empty($images) ? 'has-no-thumbs' : 'has-thumbs',
            (isset($atts['class']) ? $atts['class'] : ''),
            $thumb ? '<div class="kb-product-image">' . $thumb . '</div>' : '',
            $imagesHtml ? '<div class="kb-product-thumbs">' . $imagesHtml . '</div>' : ''
                
        );
}

// [kb_amz_product_gallery]
function kb_amz_product_gallery_func($atts) {
	$atts = shortcode_atts( array(
		'size' => 'original'
	), $atts);

        $shortCodes = getKbAmz()->getShortCodes();
        if (isset($shortCodes['gallery']['active'])
        && !$shortCodes['gallery']['active']) {
            return;
        }
        
        return getKbProductGalleryContent($atts);
}
add_shortcode( 'kb_amz_product_gallery', 'kb_amz_product_gallery_func' );


// [kb_amz_product_content]
function kb_amz_product_content_func($atts) {
    
	$atts = shortcode_atts( array(
            'show_all_reviews' => 'Yes',
            'show_title' => 'Yes',
            'strip_tags' => 'No',
            'id' => null
	), $atts);

        $shortCodes = getKbAmz()->getShortCodes();
        if (isset($shortCodes['content']['active'])
        && !$shortCodes['content']['active']) {
            return;
        }
        
        $html = <<<HTML
<div class="kb-product-tabs%s">
    <div class="kb-product-tabs-headers">
        <h4 class="kb-product-tabs-header">%s</h4>
        <hr/>
    </div>  
    <div class="kb-product-tabs-contents">
        <div class="kb-product-tabs-content">%s</div>
    </div>
</div>
HTML;
        
        $postId = $atts['id'] ? $atts['id'] : get_the_ID();
        $meta = getKbAmz()->getProductMetaArray($postId);
        $tabs = '';
        if (isset($meta['KbAmzEditorialReviews']['EditorialReview'])) {
            $reviews = isset($meta['KbAmzEditorialReviews']['EditorialReview'][0])
                     ? $meta['KbAmzEditorialReviews']['EditorialReview']
                     : array($meta['KbAmzEditorialReviews']['EditorialReview']);
            ksort($reviews);
            $headers = array();
            $contents = array();
            foreach ($reviews as $review) {
                if (kbAmzShortCodeBool($atts['show_title'])) {
                    $headers[]  = $review['Source'];
                }
                
                $contents[] = kbAmzShortCodeBool($atts['strip_tags'])
                            ? strip_tags($review['Content'])
                            : $review['Content'];
                if (!kbAmzShortCodeBool($atts['show_all_reviews'])) {
                    break;
                }
            }
            $tabs .= sprintf(
                $html,
                count($headers) > 1 ? ' kb-event' : '',
                implode('</h4><h4 class="kb-product-tabs-header">', $headers),
                implode('</div><div class="kb-product-tabs-content">', $contents)
            );
        }
        return $tabs;
}
add_shortcode( 'kb_amz_product_content', 'kb_amz_product_content_func' );


// [kb_amz_product_actions]
function kb_amz_product_actions_func($atts) {
    $atts = shortcode_atts( array(
        'postId'        => get_the_ID(),
        'variations'    => true
    ), $atts);

    $shortCodes = getKbAmz()->getShortCodes();
    if (isset($shortCodes['actions']['active'])
    && !$shortCodes['actions']['active']) {
        return;
    }
    
    $atts['variations'] = kbAmzShortCodeBool($atts['variations']);
    
    $actions = array();
    $actions[] = array(
        'html'  => getKbAmz()->getCartButtonHtml($atts['postId']),
        'order' => 100
    );
    $std = new stdClass;
    $std->actions = $actions;
    $std->postId  = $atts['postId'];
    $std->post    = get_post($atts['postId']);
    $std->atts    = $atts;
    
    apply_filters('kb_amz_product_add_actions', $std);
    
    $actions = $std->actions;
    usort($actions, 'kbAmzSortOrder');
    
    $html = array();
    foreach ($actions as $action) {
        $html[] = $action['html'];
    }
    
    if (empty($html)) {
        $html[] = getKbAmz()->getCartButtonHtml($atts['postId']);
    }
    
    return sprintf(
        '<div class="kb-product-actions">%s</div>',
        implode(PHP_EOL . '<br class="kb-product-actions-break"/>', $html)
    );
}
add_shortcode( 'kb_amz_product_actions', 'kb_amz_product_actions_func' );

// [kb_amz_product_similar]
function kb_amz_product_similar_func($atts) {
    $atts = shortcode_atts( array(
        'count' => getKbAmz()->getOption('ListItemsPerRow', 3)
    ), $atts);
    
    $shortCodes = getKbAmz()->getShortCodes();
    if (isset($shortCodes['similar']['active'])
    && !$shortCodes['similar']['active']
    || getKbAmz()->getCurrentCategory()) {
        return;
    }

    kbAmzIsSimilarQuery(true);
    $similarProducts = getKbAmz()->getSimilarProductsHtml(get_the_ID());
    $similarProducts->count = $atts['count'];
    $str = '';
    if (count($similarProducts->posts)) {
        $str = sprintf(
            '<div class="kb-amz-similar-products-wrapper"><h4>%s</h4>%s</div>',
            __('Similar Products'),
            $similarProducts
        );
    }
    kbAmzIsSimilarQuery(false);
    return $str;
}

add_shortcode( 'kb_amz_product_similar', 'kb_amz_product_similar_func' );


// [kb_amz_checkout]
function kb_amz_checkout($atts) {
    $atts = shortcode_atts( array(
        'count' => getKbAmz()->getOption('ListItemsPerRow', 3)
    ), $atts);
    
    $shortCodes = getKbAmz()->getShortCodes();
    if (isset($shortCodes['checkout']['active'])
    && !$shortCodes['checkout']['active']) {
        return;
    }
    
    $html = <<<HTML
<li>
    <div class="row">
        <div class="kb-amz-cart-thumb col-md-2">%s</div>
        <div class="kb-amz-cart-title col-md-9">
            <div>
                <h5 class="kb-cart-head"><a href="%s">%s</a></h5>
            </div>
            <b>%s</b>
        </div>
    </div>
</li>
HTML;
    
    
    
    $cart = getKbAmz()->getSessionCart();
    $output = '';
    if (isset($cart['CartItems']['CartItem']) && !empty($cart['CartItems']['CartItem'])) {
        $output .= '<div class="kb-amz-cart-wrapper">';
        $output .= '<ul>';
        foreach ($cart['CartItems']['CartItem'] as $item) {
            $post = getKbAmz()->getProductByAsin($item['ASIN']);
            // $meta = getKbAmz()->getProductMeta($post->ID);
            $output .= sprintf(
                $html,
                get_the_post_thumbnail($post->ID),
                get_permalink($post->ID),
                $post->post_title,
                $item['ItemTotal']['FormattedPrice']
            );
        }
        $output .= '</ul>';
        $output .= '<div class="kb-amz-checkout-actions">';
        $output .= sprintf(
        '<a href="%s" title="%s" target="_blank" class="kb-amz-amazon-checkout-link"><img src="%s" alt="%s" height="50"/></a>',
            $cart['PurchaseURL'],
            __('Checkout With Amazon'),
            getKbPluginUrl() . '/template/admin/img/amazonCheckout.png',
            __('Checkout With Amazon')
        );
        $output .= sprintf(
        '<a href="%s" target="_blank" class="kb-amz-continue-shopping"><button class="button kb-button">%s</button></a>',
            get_site_url(),
            __('Continue Shopping')
        );
        $output .= '</div>';
        $output .= '</div>';
    } else {
        $output = __('Your shopping cart is empty!');
    }
    return $output;
}

add_shortcode( 'kb_amz_checkout', 'kb_amz_checkout' );


// [kb_amz_list_products]
function kb_amz_list_products($atts) {
    $atts = kbAmzShortCodeAtts( array(
        'featured'          => null,
        'featured_content_length' => 300,
        'posts_per_page'    => get_option('posts_per_page', 10),
        'category'          => null,
        'post_status'       => 'any',
        'attribute_key'      => null,
        'attribute_value'    => null,
        'attribute_compare'  => '=',
        'title'             => null,
        'pagination'        => 'Yes',
        'items_per_row'     => null,
        'post_status'       => 'publish'
    ), $atts, 'kb_amz_list_products');
    
    $shortCodes = getKbAmz()->getShortCodes();
    if (isset($shortCodes['listProduct']['active'])
    && !$shortCodes['listProduct']['active']) {
        return;
    }

    if ($atts['items_per_row'] && !in_array(intval($atts['items_per_row']), array(2, 3, 4, 6))) {
        $atts['items_per_row'] = null;
    }
    
    $atts['pagination'] = kbAmzShortCodeBool($atts['pagination']);
    
    $category = kbAmzExtractShortCodeFunctionParam($atts['category']);
    
    if (!$category instanceof KbAmzErrorString && !$category && !empty($atts['category'])) {
        $category = is_numeric($atts['category'])
            ? $atts['category']
            : get_cat_ID($atts['category']);
    } else if ($category instanceof KbAmzErrorString) {
        echo $category;
    }
    
    $atts['cat']            = $category;
    $atts['post_type']      = 'post';
    //$atts['meta_key']       = 'KbAmzASIN';
    //$atts['post_status']    = 'publish';
    $atts['paged']          = $atts['pagination'] ? getKbAmzPaged() : null;
    $atts['meta_query']     = array();
    $atts['meta_query'][]   = array(
        'key' => 'KbAmzASIN'
    );
    
    if ($atts['attribute_key'] && $atts['attribute_value']) {
        $atts['meta_query'][] = array(
            'key' => $atts['attribute_key'],
            'value' => $atts['attribute_value'],
            'compare' => $atts['attribute_compare']
        );
    }
    
    if (get_the_ID() && getKbAmz()->isPostProduct(get_the_ID())) {
        $atts['post__not_in'] = array(get_the_ID());
    }
    
    $currentPost = null;
    if (get_the_ID()) {
        global $post;
        $currentPost = $post;
    }
    
    kbAmzIsMainQuery(true);
    
    $std = new stdClass;
    $std->queryAttributes = $atts;
    $std->atts            = $atts;
    
    apply_filters('kb_amz_list_products_query_attributes', $std);
    
    if ($std instanceof stdClass
    && isset($std->queryAttributes)
    && !empty($std->queryAttributes)) {
        $atts = $std->queryAttributes;
    }
    
    $posts = query_posts($atts);
    $atts['maxNumPages'] = $GLOBALS['wp_query']->max_num_pages;
    $atts['posts']       = $posts;
    $atts['featured'] = kbAmzShortCodeBool($atts['featured']);
    $view = getKbAmzTemplate()->getProductsView($atts)->getContent();
    wp_reset_query();
    kbAmzIsMainQuery(false);
    if (get_the_ID()) {
        setup_postdata($currentPost);
    }
    return $view;
}

add_shortcode('kb_amz_list_products', 'kb_amz_list_products');


// [kb_amz_product_reviews]
function kb_amz_product_reviews_func($atts) {
    $atts = shortcode_atts( array(
        'ID' => null,
        'title' => null,
        'title_tag' => 'h3',
        'width' => '100%',
        'height' => '300px'
    ), $atts);

    $shortCodes = getKbAmz()->getShortCodes();
    if (isset($shortCodes['reviews']['active'])
    && !$shortCodes['reviews']['active']) {
        return;
    }
    $meta = getKbAmz()->getProductMeta(($atts['ID'] ? $atts['ID'] : get_the_ID()));
    if (isset($meta['KbAmzCustomerReviews.HasReviews'])
    && $meta['KbAmzCustomerReviews.HasReviews']
    && isset($meta['KbAmzCustomerReviews.IFrameURL'])
    && $meta['KbAmzCustomerReviews.IFrameURL']) {
        return sprintf(
            '<div class="kb-amz-iframe-reviews">%s<iframe src="%s" style="width:%s;height:%s;border:none;padding:0;margin:0;"/>%s</iframe></div>',
            ($atts['title'] ? '<' . $atts['title_tag'] . '>' . $atts['title'] . '</' . $atts['title_tag'] . '>' : ''),
            $meta['KbAmzCustomerReviews.IFrameURL'],
            $atts['width'],
            $atts['height'],
            __('Your browser does not support Iframes.')
        );
    }
}
add_shortcode( 'kb_amz_product_reviews', 'kb_amz_product_reviews_func' );


function kbAmzShortCodeBool($str)
{
    $test = strtolower($str);
    if ($test == 'yes') {
        return true;
    } else if ($test == 'no') {
        return false;
    }
    return $str;
}


function kbAmzShortCodeAttrToStr($code, $atts)
{
    $code = str_replace(array('[', ']', ' '), '', $code);
    $params = array();
    foreach ($atts as $key => $val) {
        $params[] = $key.'="'.$val.'"';
    }
    return sprintf(
        '[%s%s]',
        $code,
        (empty($params) ? null : ' '.implode(' ', $params))
    );
}


add_filter('post_thumbnail_html', 'filterKbAmzThumbnailGallery', 99999);
function filterKbAmzThumbnailGallery($html)
{
    if (!getKbAmz()->getOption('replaceThumbnailWithGallery')
    || !getKbAmz()->isPostProduct(get_the_ID())
    || get_the_ID() != getKbAmzCurrentPostId()
    || kbAmzIsMainQuery()
    || kbAmzIsSimilarQuery()) {
        return $html;
    }
    return getKbProductGalleryContent(array('size' => getKbAmz()->getOption('productListImageSize'), 'class' => 'kb-amz-thumb-replace'));
    
}

function kbAmzShortCodeAtts($pairs, $atts, $shortcode = '' ) {
    $atts = (array)$atts;
    foreach ($atts as $name => $val) {
        $pairs[$name] = kbAmzExtractShortCodeFunctionParam($val);
    }

    if ($shortcode) {
        $pairs = apply_filters( "shortcode_atts_{$shortcode}", $pairs, $pairs, $atts );
    }
    return $pairs;
}

function kbAmzExtractShortCodeFunctionParam($str)
{
    if (is_numeric($str) || is_object($str) || is_array($str)) {
        return $str;
    }
    
    $str = str_replace(array('&gt;', '&lt;'), array('>', '<'), $str);
    
    if (strpos($str, '<?') !== false || strpos($str, '<?php') !== false) {
        $str = str_replace(array('<?', '<?php', '?>', 'return', "\n"), null, $str);
        $str = "return " . $str . ";";
        try {
            $result = eval($str);
            return kbAmzTestShortCodeValueResult($result);
        } catch (Exception $e) {
            return new KbAmzErrorString('Shortcode value error: ' . $e->getMessage());
        }
    } else if (strpos($str, '(') !== false && strpos($str, ')') !== false) {
        $parts = explode('(', trim(strip_tags($str)));
        $function = $parts[0];
        if (function_exists($function)) {
            $params = explode(')', $parts[1]);
            $params = explode(',', $params[0]);
            $fixedParams = array();
            foreach ($params as $param) {
                if (strtolower($param) == 'true') {
                    $fixedParams[] = true;
                } else if(strtolower($param) == 'false') {
                    $fixedParams[] = false;
                } else {
                    $fixedParams[] = $param;
                }
            }
            $results = call_user_func_array($function, $fixedParams);
            if (!is_array($results)) {
                $results = array($results);
            }
            foreach ($results as $result) {
                return kbAmzTestShortCodeValueResult($result);
            }
        } else {
            return new KbAmzErrorString('ShortCode given function: "' .$function . '"" is not a valid function.');
        }
    }
    return $str;
}

function kbAmzTestShortCodeValueResult($result)
{
    if (is_numeric($result)) {
        return $result;
    } else if (is_string($result)) {
        return $result;
    } else if (is_object($result) && isset($result->ID)) {
        return $result->ID;
    } else if (is_object($result) && isset($result->term_id)) {
        return $result->term_id;
    } else if (is_object($result) && isset($result->cat_ID)) {
        return $result->cat_ID;
    }
    return null;
}

class KbAmzErrorString
{
    private $error;
    
    public function __construct($error)
    {
        $this->error = $error;
    }
    
    public function __toString()
    {
        return '<div><strong style="color:red;">Error: '.$this->error.'</strong></div>';
    }
}
