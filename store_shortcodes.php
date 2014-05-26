<?php
!defined('ABSPATH') and exit;

// [kb_amz_product_attributes]
function kb_amz_product_attributes_func($atts) {
	$atts = shortcode_atts( array(
            
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
        
        
        $meta = getKbAmz()->getProductMeta(get_the_ID());
        $attributes = getKbAmz()->getShortCodeAttributes();
        // var_dump($attributes, $meta);die;
        $markup = '';
        foreach ($attributes as $attr => $label) {
            if (isset($meta[$attr]) && !empty($meta[$attr])) {
                
                
                // kb-amz-item-price
                
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
        $imagesHtml = '';
        if (count($images) > 1) {
            foreach ($images as $img) {
                $maxImage = wp_get_attachment_image_src($img->ID, $atts['size']);
                $imagesHtml .= wp_get_attachment_image($img->ID, $atts['size'], null, array('data-image' => $maxImage[0]));
            }
        }

        $post_thumbnail_id = get_post_thumbnail_id( get_the_ID() );
        $thumb = wp_get_attachment_image( $post_thumbnail_id, $atts['size']);
                
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
            'show-all-reviews' => 'Yes',
            'show-title' => 'Yes',
            'strip-tags' => 'No'
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
        
        $meta = getKbAmz()->getProductMetaArray(get_the_ID());
        $tabs = '';
        if (isset($meta['KbAmzEditorialReviews']['EditorialReview'])) {
            $reviews = isset($meta['KbAmzEditorialReviews']['EditorialReview'][0])
                     ? $meta['KbAmzEditorialReviews']['EditorialReview']
                     : array($meta['KbAmzEditorialReviews']['EditorialReview']);
            ksort($reviews);
            $headers = array();
            $contents = array();
            foreach ($reviews as $review) {
                if (kbAmzShortCodeBool($atts['show-title'])) {
                    $headers[]  = $review['Source'];
                }
                
                $contents[] = kbAmzShortCodeBool($atts['strip-tags'])
                            ? strip_tags($review['Content'])
                            : $review['Content'];
                if (!kbAmzShortCodeBool($atts['show-all-reviews'])) {
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


// [kb_amz_product_content]
function kb_amz_product_actions_func($atts) {
    $atts = shortcode_atts( array(

    ), $atts);

    $shortCodes = getKbAmz()->getShortCodes();
    if (isset($shortCodes['actions']['active'])
    && !$shortCodes['actions']['active']) {
        return;
    }

    $addToCart = getKbAmz()->getCartButtonHtml(get_the_ID());
    return sprintf(
        '<div class="kb-product-actions">%s</div>',
        $addToCart
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
    $atts = shortcode_atts( array(
        'posts_per_page'    => get_option('posts_per_page', 10),
        'category'          => null,
        'post_status'       => 'any',
        'attributeKey'      => null,
        'attributeValue'    => null,
        'attributeCompare'  => '=',
        'title'             => null,
        'pagination'        => 'Yes'
    ), $atts);
    
    $shortCodes = getKbAmz()->getShortCodes();
    if (isset($shortCodes['listProduct']['active'])
    && !$shortCodes['listProduct']['active']) {
        return;
    }
    
    $atts['pagination'] = kbAmzShortCodeBool($atts['pagination']);
    
    $category = null;
    if (!empty($atts['category'])) {
        $id = is_numeric($atts['category'])
            ? $atts['category']
            : get_cat_ID($atts['category']);
        $category = get_category($id);
    }
    
    $atts['category']       = is_object($category) ? $category->ID : null;
    $atts['post_type']      = 'post';
    $atts['meta_key']       = 'KbAmzASIN';
    $atts['post_status']    = 'any';
    $atts['paged']          = getKbAmzPaged();

    if (isset($atts['attributeKey']) && isset($atts['attributeValue'])) {
        $atts['meta_query'] = array(
            'key' => $atts['attributeKey'],
            'value' => $atts['attributeValue'],
            'compare' => $atts['attributeCompare']
        );
    }
    kbAmzIsMainQuery(true);    
    $posts = query_posts($atts);
    $atts['maxNumPages'] = $GLOBALS['wp_query']->max_num_pages;
    $atts['posts']       = $posts;
    $view = getKbAmzTemplate()->getProductsView($atts)->getContent();
    wp_reset_query();
    kbAmzIsMainQuery(false);
    
    return $view;
}

add_shortcode('kb_amz_list_products', 'kb_amz_list_products');



function kbAmzShortCodeBool($str)
{
    return strtolower($str) == 'yes' ? true : false;
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