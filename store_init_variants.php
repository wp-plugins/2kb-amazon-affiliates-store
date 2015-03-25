<?php
!defined('ABSPATH') and exit;

/**
 * Setup new parameters
 */
getKbAmz()->setUnserializeMetaKey('KbAmzVariations');
getKbAmz()->setUnserializeMetaKey('KbAmzVersions');

/**
 * Set the first variant
 */
add_action('loop_start', 'kbAmzReplaceVariantParent', -99999);
function kbAmzReplaceVariantParent($query)
{
    if (!getKbAmz()->getOption('allowVariants')) {
        return $query;
    }
    
    if (!empty($query->posts)
    && count($query->posts) == 1) {
        $post = $query->posts[0];
        if (getKbAmz()->isPostProduct($post->ID)) {
            $variant = getKbAmz()->getProductFirstVariant($post->ID);
            if ($variant) {
                $query->posts[0] = $variant;
            }
        }
    }
    
    return $query;
}

/**
[AlternateVersions] => Array
    (
        [AlternateVersion] => Array
            (
                [0] => Array
                    (
                        [ASIN] => 0008135126
                        [Title] => The Queen's Orang-Utan
                        [Binding] => Hardcover
                    )
                [1] => Array
                    (
                        [ASIN] => 0008135134
                        [Title] => The Queen's Orang-Utan (Comic Relief)
                        [Binding] => Paperback
                    )
                [2] => Array
                    (
                        [ASIN] => B00S4DWGIO
                        [Title] => The Queen's Orang-Utan
                        [Binding] => Kindle Edition
                    )
            )
    )
)
 */

add_action('KbAmazonImporter::saveProduct', 'kbAmzVariantsProductpreSaveAlternateVersions');
function kbAmzVariantsProductpreSaveAlternateVersions($std)
{
    if (!getKbAmz()->getOption('allowVariants')) {
        return $std;
    }
    
    $item       = $std->item;
    $postId     = $std->postId;
    $post       = $std->post;
    $postExists = $std->postExists;
    $importer   = $std->importer;
     
    if (/*$postExists ||*/ !$postId || !$item->isValid() || $post->post_parent) {
        return $std;
    }
    
    $result = $item->getResult();
    if (!isset($result['Items']['Item']['AlternateVersions']['AlternateVersion'])) {
        return $std;
    }
    
    $versions         = $result['Items']['Item']['AlternateVersions']['AlternateVersion'];
    $meta             = array();
    $meta['Versions'] = $versions;
    
    foreach ($versions as $version) {
        if (!isset($version['ASIN'])) {
            continue;
        }
        $asin = $version['ASIN'];
        if (getKbAmz()->getProductByAsin($asin)) {
            continue;
        }
        $versionItem = $importer->find($asin);
        if (!$versionItem->isValid()) {
            continue;
        }
        $itemData = $versionItem->getItem();
        if (isset($version['Binding'])) {
            $itemData['ItemAttributes']['Title'] .= ' ('.$version['Binding'].')';
        }
        
        $versionItemData = array(
            'Items' => array(
                'Item' => array_merge($item->getItem(), $itemData)
            )
        );
        
        $versionItem = new KbAmazonItem($versionItemData);
        $versionItem->setPostParent($postId);
        $saveResult = $importer->saveProduct($versionItem);
        $importer->updateProductPostMeta($meta, $saveResult['post_id']);
    }
    
    $importer->updateProductPostMeta($meta, $postId);
    
    return $std;
}


/**
 * Standard Product Variants
 */
add_action('KbAmazonImporter::saveProduct', 'kbAmzVariantsProductSave');
function kbAmzVariantsProductSave($std)
{
    if (!getKbAmz()->getOption('allowVariants')) {
        return $std;
    }
    /**
     * @TODO disable reupdating, becase variants are products and will get imported
     */
    $importer = $std->importer;
    $postId   = $std->postId;
    $post     = get_post($postId);
    $item     = $std->item;
    $result   = $item->getResult();
    
    $parentItem = $result['Items']['Item'];
    if (!isset($parentItem['Variations'])
    || empty($parentItem['Variations'])) {
        return;
    }
    
    $variations = $parentItem['Variations'];
    $items      = $variations['Item'];
    unset($parentItem['Variations'], $variations['Item']);
    
    foreach ($items as $item) {
        if (!isset($item['ASIN'])) {
            continue;
        }
        $variations['Items'][] = array(
            'ASIN' => $item['ASIN']
        );
        
        $variantItem = $importer->find($item['ASIN']);
        if (!$variantItem->isValid()) {
            continue;
        }
        $item = array_merge($item, $variantItem->getItem());
        $item['IsVariant'] = true;
        if (!isset($item['VariationAttributes']['VariationAttribute'][0])) {
            $item['VariationAttributes']['VariationAttribute'] = array($item['VariationAttributes']['VariationAttribute']);
        }
        
        if ($item['ItemAttributes']['Title'] == $post->post_title) {
            $addup = array();
            foreach ($item['VariationAttributes']['VariationAttribute'] as $pair) {
                $addup[] = $pair['Name'] . ' ' . $pair['Value'];
            }
            $item['ItemAttributes']['Title'] .= ' (' . implode(' ', $addup) . ')';
        }
        
        $variantItemData = array(
            'Items' => array(
                'Item' => array_merge($parentItem, $item)
            )
        );
        $variantItem = new KbAmazonItem($variantItemData);
        $saveResult = $importer->saveProduct($variantItem);
        if (isset($saveResult['post_id'])
        && $saveResult['post_id']) {
            wp_update_post(
                array(
                    'ID'            => $saveResult['post_id'],
                    'post_parent'   => $postId
                )
            );
        }
    }
    
    if (is_string($variations['VariationDimensions']['VariationDimension'])) {
        $variations['VariationDimensions']['VariationDimension'] = array($variations['VariationDimensions']['VariationDimension']);
    }
        
    $meta = array();
    $meta['Variations'] = $variations;
    /**
     * Addinig varits data in the main product
     */
    $importer->updateProductPostMeta($meta, $postId);
    wp_update_post(array('ID' => $postId, 'post_status' => 'publish'));
    
    return $std;
}





add_action('kb_amz_product_add_actions', 'kbAmzProductVariansActions');
function kbAmzProductVariansActions($std)
{
    if (!getKbAmz()->getOption('allowVariants')) {
        return $std;
    }
    
    $post        = $std->post;
    $productId   = $post->post_parent;
    $currentMeta = getKbAmz()->getProductMeta($post->ID);
    $html        = array();
    if (getKbAmz()->hasProductVariants($productId)) {
        $variants = getKbAmz()->getProductVariants($productId);
        if (empty($variants)) {
            return;
        }
        $productMeta     = getKbAmz()->getProductMeta($productId);
        $types           = $productMeta['KbAmzVariations']['VariationDimensions']['VariationDimension'];
        $types           = array_merge(array(), $types);
        
        foreach ($types as $type) {
            $funct  = 'kbAmzVariantType' . $type;
            if (!function_exists($funct)) {
                continue;
            }
            
            $typeVariants = array();
            foreach ($variants as $variant) {
                $meta = getKbAmz()->getProductMeta($variant->ID);
                if (!isset($meta['KbAmzVariationAttributes']['VariationAttribute'])) {
                    continue;               
                }
                $canUserVariant = true;
                foreach ($meta['KbAmzVariationAttributes']['VariationAttribute'] as $pair) {
                    if (!isset($pair['Name'])) {
                        continue;
                    }
                    
                    $key = 'KbAmzItemAttributes.' . $pair['Name'];
                    if ($pair['Name'] != $type) {
                        if (isset($currentMeta[$key])
                        && $currentMeta[$key] != $pair['Value']) {
                            $canUserVariant = false;
                        }
                    }
                }
                
                if (!$canUserVariant) {
                    continue;
                }
                $typeVariants[] = $variant;
            }
            if (count($typeVariants) > 1) {
                $html[] = $funct($typeVariants, array('type' => $type, 'label' => $productMeta['KbAmzItemAttributes.' . $type]), $post);
            }
        }
        
        $std->actions[] = array(
            'html'  => implode(PHP_EOL, $html),
            'order' => 90
        );
    }
    
    return $std;
}

function kbAmzVariantTypeColor($variants, $type, $active)
{
    $html = <<<HTML
<div class="kb-amz-variant-attribute kb-amz-type-%s">
    <div class="kb-amz-variant-attribute-label kb-product-attributes">%s: %s</div>
    <div class="kb-amz-variant-attribute-value">%s</div>      
</div>
HTML;
    
    $imagesStr = array();
    foreach ($variants as $variant) {
        $isActive = $active && $active->ID == $variant->ID;
        
        $images = getKbAmz()->getProductImages($variant->ID);
        $str    = $images->getFirst(null, null, array('class' => 'kb-amz-variant-attribute-image'));
        $imagesStr[] = sprintf(
            '<a href="%s" title="%s" class="kb-amz-attribute-image-link%s">%s</a>',
            get_permalink($variant->ID),
            esc_attr($variant->post_title),
            ($isActive ? ' kb-amz-active' : ''),
            $str
        );
    }
    
    return sprintf(
        $html,
        strtolower($type['type']),
        __($type['type']),
        __($type['value']),
        implode('', $imagesStr)
    );
}

function kbAmzVariantTypeSize($variants, $type, $active)
{
    if (empty($variants)) {
        return;
    }
    
    $html = <<<HTML
<div class="kb-amz-variant-attribute kb-amz-type-%s">
    <div class="kb-amz-variant-attribute-label kb-product-attributes">%s: %s</div>
    <div class="kb-amz-variant-attribute-value">%s</div>      
</div>
HTML;
    
    $maxLenghth = 5;
    $options    = '';
    foreach ($variants as $variant) {
        $meta = getKbAmz()->getProductMeta($variant->ID);
        $value = $meta['KbAmzItemAttributes.' . $type['type']];
        $length = strlen($value);
        if ($maxLenghth < $length) {
            $maxLenghth = $length;
        }
        $options .= sprintf(
            '<option class="kb-amz-variant-select%s" value="%s"%s>%s</option>',
            (getKbAmz()->isProductAvailable($variant->ID) ? ' kb-amz-available' : ' kb-amz-not-available'),
            get_permalink($variant->ID),
            ($active->ID == $variant->ID ? ' selected="selected"' : ''),
            $value
        );
    }
    
    $select = '<select name="product_variant_'.  strtolower($type['type']).'" style="width: '.$maxLenghth.'em;" onchange="this.options[this.selectedIndex].value && (window.location = this.options[this.selectedIndex].value);">';
    $select .= $options;
    $select .= '</select>';
    $select .= sprintf(
        '&nbsp;&nbsp;&nbsp;<a href="%s" title="%s" target="_blank" rel="nofollow">%s</a>',
        getKbAmz()->getProductSizeChartUrl($active->ID),
        __('Size Chart'),
        __('Size Chart')
    );
    return sprintf(
        $html,
        strtolower($type['type']),
        __($type['type']),
        __($type['value']),
        $select
    );
    
}
/**
 * Versions
 */
add_action('kb_amz_product_add_actions', 'kbAmzProductVersionsActions');
function kbAmzProductVersionsActions($std)
{
    if (!getKbAmz()->getOption('allowVariants')) {
        return $std;
    }
    
    $current        = $std->post;
    $meta           = getKbAmz()->getProductMeta($current->ID);

    if (isset($meta['KbAmzVersions'])
    && !empty($meta['KbAmzVersions'])) {
        $html       = array();
        $versions   = array();
        $bindings   = array();
        if ($current->post_parent) {
            $postParent = get_post($current->post_parent);
        } else {
            $postParent = $current;
        }
        
        $parentMeta     = getKbAmz()->getProductMeta($postParent->ID);
        $parentAsin     = $parentMeta['KbAmzASIN'];
        
        if (!isset($parentAsin[$parentAsin])) {
            $binding = isset($parentMeta['KbAmzItemAttributes.Binding'])
                     ? $parentMeta['KbAmzItemAttributes.Binding'] : $postParent->post_title;
            $versions[$parentAsin] = array(
                'ASIN' => $parentAsin,
                'Title' => $postParent->post_title,
                'Binding' => $binding
            );
            $bindings[] = $binding;
        }

        foreach ($meta['KbAmzVersions'] as $v) {
            if (isset($versions[$v['ASIN']])) {
                continue;
            }
            
            $binding = isset($v['Binding']) ? $v['Binding'] : $v['Title'];
            if (in_array($binding, $bindings)) {
                $post = getKbAmz()->getProductByAsin($v['ASIN']);
                $binding .= ', ' . date('d M Y', getKbAmz()->getProductDate($post->ID, true));
            }
            $v['Binding'] = $binding;
            $versions[$v['ASIN']] = $v;
            $bindings[] = $binding;
        }
        
        $i = 0;
        foreach ($versions as $key => $version) {
            $i++;
            $post = getKbAmz()->getProductByAsin($version['ASIN']);
            if (!$post) {
                continue;
            }
            
            $title = (isset($version['Binding']) ? $version['Binding'] : $post->post_title);
            
            $html[$title] =  sprintf(
                '<div class="%s%s%s"><div class="kb-amz-version"><a href="%s" title="%s">%s</a> <div class="kb-amz-item-price">%s</div></div></div>',
                'kb-amz-version-attribute col-md-3',
                (count($meta['KbAmzVersions']) > 4 ? ' kb-amz-version-offset' : ''),
                ($current->ID == $post->ID ? ' kb-amz-version-active' : ''),
                get_permalink($post->ID),
                esc_attr($post->post_title),
                $title,
                getKbAmz()->getProductPriceHtml($post->ID)
            );
            unset($versions[$key]);
            if ($i >= 8) {
                break;
            }
        }
        
        
        if (!empty($html)) {
            $part1 = '';
            if (!empty($versions)) {
                $grouped = array();
                $allVersions = $versions;
                foreach ($versions as $version) {
                    $post = getKbAmz()->getProductByAsin($version['ASIN']);
                    if (!$post) {
                        continue;
                    }
                    $meta = getKbAmz()->getProductMeta($post->ID);
                    if (!isset($meta['KbAmzItemAttributes.Binding'])) {
                        continue;
                    }
                    $binding = $meta['KbAmzItemAttributes.Binding'];
                    if (!isset($grouped[$binding])) {
                        $grouped[$binding] = array();
                    }
                    $grouped[$binding][] = array(
                        'version' => $version,
                        'meta'    => $meta,
                        'post'    => $post
                    );
                }
                ksort($grouped);
                
                $hasActiveInAll = false;
                $t = '';
                foreach ($grouped as $type => $versions) {
                    $hasActive = false;
                    $rows = '';
                    foreach ($versions as $verison) {
                        $isActive = $current->ID == $verison['post']->ID;
                        if ($isActive) {
                            $hasActive = true;
                        }
                        $rows .= sprintf(
                            '<tr><td><a%s href="%s" title="%s">%s</a></td><td class="kb-amz-format-price"><div class="kb-amz-item-price">%s</div></td></tr>',
                            ($isActive ? ' class="kb-amz-format-active"' : ''),
                            get_permalink($verison['post']->ID),
                            esc_attr($verison['post']->post_title),
                            $verison['meta']['KbAmzItemAttributes.Binding'] . ' ' . date('d M Y', getKbAmz()->getProductDate($verison['post']->ID, true)),
                            getKbAmz()->getProductPriceHtml($verison['post']->ID)
                        );
                    }
                    
                    if ($hasActive) {
                        $hasActiveInAll = true;
                    }
                    
                    $t .= sprintf(
                        '<div class="kb-amz-format-type%s">%s</div>',
                        $hasActive ? ' active' : '',
                        $type
                    );
                    
                    $t .= '<table class="kb-amz-format-type-table table"'.($hasActive ? ' style="display:table;"' : '').'>';
                    $t .= '<tr><th>'.__('Format').'</th><th class="kb-amz-format-price">'.__('Price').'</th></tr>';
                    $t .= $rows;
                    $t .= '</table>';
                }
                $text = sprintf(
                    'See all %s %s and editions',
                    count($allVersions),
                    count($allVersions) > 1 ? 'formats' : 'format'
                );
                $textActive = __('Hide other formats and editions');
                
                $part1 .= sprintf(
                    '<a href="#" class="kb-amz-actions-see-all-formats%s" data-text="%s" data-text-original="%s">%s</a>',
                    ($hasActiveInAll ? ' active' : ''),
                    ($hasActiveInAll ? $textActive : $text),
                    ($hasActiveInAll ? $text : $textActive),
                    ($hasActiveInAll ? $textActive : $text)
                );
                $part1 .= '<div class="kb-amz-actions-all-formats"'.($hasActiveInAll ? ' style="display:block;"' : '').'>';
                $part1 .= $t;
                $part1 .= '</div>';
            }
            
            ksort($html);
            $part2 = '<div class="kb-amz-version-attributes row kb-amz-same-child-height">' . implode(PHP_EOL, $html) . '</div>';
            $std->actions[] = array(
                'html'  => $part1 . $part2,
                'order' => 100
            );
        }
    }
    
    return $std;
}

/**
 * Query atributes in listing
 */
add_action('kb_amz_list_products_query_attributes', 'kbAmzDisableVarinatFromListing');
function kbAmzDisableVarinatFromListing($std)
{
    if (!getKbAmz()->getOption('showVariantsInListing')) {
        $std->queryAttributes['post_parent'] = 0;
    }
    
    return $std;
}
