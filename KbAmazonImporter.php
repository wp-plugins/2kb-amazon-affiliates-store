<?php
!defined('ABSPATH') and exit;

/*
 * @SEE http://codex.wordpress.org/Function_Reference/wp_generate_attachment_metadata
 */
require_once ABSPATH . 'wp-admin/includes/image.php';

class KbAmazonImporter
{
    const SIMILAR_ITEM_LOAD_IMMEDIATELY = 1;
    const SIMILAR_ITEM_LOAD_CRON = 2;
    const SIMILAR_ITEM_LOAD_NO = 3;
    const CRON_NUMBER_OF_PRODUCTS_TO_PROCESS = 10;
    const CRON_NUMBER_OF_PRODUCTS_PRICE_TO_UPDATE = 50;
    const SECONDS_BEFORE_UPDATE = 86400;
    
    protected $requiredParams = array(
        // AMZ ARRAY WP ATTR
        'ASIN' => 'ASIN'
        
    );
    
    protected $importCategories = array();
    
    protected $amazonCategoryGroups = array(
        'com'       => 'Worldwide', // ok
        'de'        => 'Germany', // ok
        'co.uk'     => 'United Kingdom', // ok
        'ca'        => 'Canada', // ok
        'co.jp'     => 'Japan', // ok
        'it'        => 'Italy', // ok
        'cn'        => 'China',
        'fr'        => 'France', //ok
        'es'        => 'Spain', // ok
        'in'        => 'India', // ok
    );
    
    protected $amazonCategories = array(
        'com' => array(
            '1036682' => 'Apparel',
            '2619526011' => 'Appliances',
            '2617942011' => 'ArtsAndCrafts',
            '15690151' => 'Automotive',
            '165797011' => 'Baby',
            '11055981' => 'Beauty',
            '1000' => 'Books',
            '541966' => 'PCHardware',
            '2625374011' => 'DVD',
            '493964' => 'Electronics',
            '2255571011' => 'GourmetFood',
            '16310211' => 'Grocery',
            '3760931' => 'HealthPersonalCare',
            '1063498' => 'HomeImprovement',
            '16310161' => 'Industrial',
            '3880591' => 'Jewelry',
            '358606011' => 'KindleStore',
            '284507' => 'Kitchen',
            '599872' => 'Magazines',
            '2350150011' => 'MobileApps',
            '624868011' => 'MP3Downloads',
            '301668' => 'Music',
            '11965861' => 'MusicalInstruments',
            '1084128' => 'OfficeProducts',
            '286168' => 'OutdoorLiving',
            '11846801' => 'VideoGames',
            '2619534011' => 'PetSupplies',
            '502394' => 'Photo',
            '672124011' => 'Shoes',
            '491286' => 'Software',
            '3375301' => 'SportingGoods',
            '468240' => 'Tools',
            '165795011' => 'Toys',
            '2625374011' => 'UnboxVideo',
            '378516011' => 'Watches',
            '2335753011' => 'Wireless',
            '2407755011' => 'WirelessAccessories',
         ),
        'ca' => array(
            '927726' => 'Books',
            '952768' => 'DVD',
            '677211011' => 'Electronics',
            '2224068011' => 'Kitchen',
            '962454' => 'Music',
            '3234221' => 'VideoGames',
            '3234171' => 'Software',
            '2242990011' => 'SportingGoods',
            '962072' => 'VHS',
        ),
        'cn' => array(
            '658391051' => 'Books',
            '836313051' => 'SportingGoods',
            '2127222051' => 'OfficeProducts',
            '852804051' => 'HealthPersonalCare',
            '42693071' => 'Baby',
            '80208071' => 'Appliances',
            '1952921051' => 'HomeImprovement',
            '1953165051' => 'Watches',
            '2016157051' => 'Apparel',
            '2127216051' => 'Grocery',
            '1947900051' => 'Automotive',
            '755653051' => 'Photo',
            '647071051' => 'Toys',
            '816483051' => 'Jewelry',
            '2016117051' => 'Electronics',
            '746777051' => 'Beauty',
            '2016137051' => 'Video',
            '897416051' => 'VideoGames',
            '863873051' => 'Software',
            '2029190051' => 'Shoes',
            '754387051' => 'Music',
            '2016127051' => 'Home',
        ),
        'de' => array(
            '79899031' => 'Automotive',
            '357577011' => 'Baby',
            '541686' => 'Books',
            '192417031' => 'OfficeProducts',
            '78689031' => 'Apparel',
            '213084031' => 'Lighting',
            '368179031' => 'PCHardware',
            '547664' => 'DVD',
            '569604' => 'Electronics',
            '54071011' => 'ForeignBooks',
            '541708' => 'VideoGames',
            '64263031' => 'HealthPersonalCare',
            '10925241' => 'HomeGarden',
            '10925051' => 'Tools',
            '571860' => 'Photo',
            '3169011' => 'Kitchen',
            '530485031' => 'KindleStore',
            '340847031' => 'Grocery',
            '180529031' => 'MP3Downloads',
            '542676' => 'Music',
            '340850031' => 'MusicalInstruments',
            '11048231' => 'OutdoorLiving',
            '84231031' => 'Beauty',
            '327473011' => 'Jewelry',
            '355006011' => 'Shoes',
            '542064' => 'Software',
            '12950661' => 'Toys',
            '16435121' => 'SportingGoods',
            '193708031' => 'Watches',
            '547082' => 'VHS',
            '1161660' => 'Magazines',
        ),
        'es' => array(
            '599392031' => 'Kitchen',
            '599380031' => 'DVD',
            '667050031' => 'Electronics',
            '599383031' => 'VideoGames',
            '599386031' => 'Toys',
            '599365031' => 'Books',
            '599368031' => 'ForeignBooks',
            '599374031' => 'Music',
            '599389031' => 'Watches',
            '599377031' => 'Software',
        ),
        'fr' => array(
            '206618031' => 'Baby',
            '197859031' => 'Beauty',
            '193711031' => 'Jewelry',
            '248815031' => 'Shoes',
            '57686031' => 'Kitchen',
            '235554011' => 'DVD',
            '69633011' => 'ForeignBooks',
            '192420031' => 'OfficeProducts',
            '340859031' => 'PCHardware',
            '340862031' => 'MusicalInstruments',
            '322088011' => 'Toys',
            '235571011' => 'VideoGames',
            '235560011' => 'Electronics',
            '235564011' => 'Books',
            '235570011' => 'Software',
            '213081031' => 'Lighting',
            '60937031' => 'Watches',
            '235565011' => 'Music',
            '197862031' => 'HealthPersonalCare',
            '325615031' => 'SportingGoods',
            '206442031' => 'MP3Downloads',
            '340856031' => 'Apparel',
            '235555011' => 'VHS',
        ),
        'it' => array(
            '524016031' => 'Kitchen',
            '412607031' => 'DVD',
            '412610031' => 'Electronics',
            '433843031' => 'ForeignBooks',
            '635017031' => 'HomeGarden',
            '523998031' => 'Toys',
            '411664031' => 'Books',
            '412601031' => 'Music',
            '524010031' => 'Watches',
            '524007031' => 'Shoes',
            '412613031' => 'Software',
            '412604031' => 'VideoGames',
        ),
        'co.jp' => array(
            '202762011' => 'Electronics',
            '202769011' => 'SportingGoods',
            '202764011' => 'Software',
            '202763011' => 'VideoGames',
            '202761011' => 'HomeImprovement',
            '202770011' => 'HealthPersonalCare',
            '202761011' => 'Kitchen',
            '333336011' => 'Watches',
            '202188011' => 'Books',
            '86422051' => 'Jewelry',
            '202768011' => 'Toys',
            '202770011' => 'Beauty',
            '361298011' => 'Apparel',
            '2016927051' => 'Shoes',
            '202765011' => 'Music',
            '57240051' => 'Grocery',
            '202766011' => 'DVD',
            '2129334051' => 'MP3Downloads',
            '89680051' => 'OfficeProducts',
            '202767011' => 'VHS',
        ),
        'co.uk' => array(
            '83451031' => 'Apparel',
            '248878031' => 'Automotive',
            '60032031' => 'Baby',
            '117333031' => 'Beauty',
            '1025612' => 'Books',
            '573406' => 'DVD',
            '560800' => 'Electronics',
            '344155031' => 'Grocery',
            '66280031' => 'HealthPersonalCare',
            '10709121' => 'HomeImprovement',
            '3147411' => 'HomeGarden',
            '193717031' => 'Jewelry',
            '341678031' => 'KindleStore',
            '3147411' => 'Kitchen',
            '213078031' => 'Lighting',
            '77198031' => 'MP3Downloads',
            '520920' => 'Music',
            '192414031' => 'OfficeProducts',
            '10709021' => 'OutdoorLiving',
            '1025616' => 'VideoGames',
            '362350011' => 'Shoes',
            '1025614' => 'Software',
            '319530011' => 'SportingGoods',
            '84124031' => 'Tools',
            '595314' => 'Toys',
            '573400' => 'VHS',
            '328229011' => 'Watches',
        ),
        'in' => array(
            '976389031' => 'Books',
            '976416031' => 'DVD',
            '976419031' => 'Electronics',
            '976442031' => 'Home & Kitchen',
            '1951048031' => 'Jewelry',
            '976392031' => 'PCHardware',
            '1350380031' => 'Toys',
            '1350387031' => 'Watches'
            
        )
    );

    public function __construnct()
    {
        
    }
    
    public function getAmazonCategoryGroups()
    {
        return $this->amazonCategoryGroups;
    }

    public function getAmazonCategories()
    {
        return $this->amazonCategories;
    }
    
    public function getAmazonCategory($id)
    {
        foreach ($this->getAmazonCategories() as $subCats) {
            foreach ($subCats as $catId => $catName) {
                if ($catId == $id) {
                    return $catName;
                }
            }
        }
    }

    public function countAmazonRequest()
    {
        getKbAmz()->setOption(
            'AmazonRequests',
            getKbAmz()->getOption('AmazonRequests', 0) + 1
        );
    }
    /**
     * 
     * @param type $arr
     * @return \KbAmazonImporter
     */
    public function setImportCategories($arr)
    {
        $this->importCategories = !empty($arr) ? $arr : array();
        return $this;
    }
    
    public function getImportCategories()
    {
        $cats = array();
        foreach ($this->importCategories as $cat) {
            $cat = get_category($cat);
            if ($cat) {
                $cats[] = $cat->name;
            } else if (!is_numeric($cat)) {
                $cats[] = $cat;
            }
        }
        return $cats;
    }
    
    public static function getCacheItemKey($asin)
    {
        return 'kbAmzStoreFind' . $asin;
    }
    
    public static function cacheItem(KbAmazonItem $data)
    {
        getKbAmz()->setCache(self::getCacheItemKey($data->getAsin()), $data);
    }

    /**
     * 
     * @param str $asin
     * @return array|null
     */
    public function find($asin, $responseGroup = 'Large')
    {
        $key = $this->getCacheItemKey($asin);
        if (!$data = getKbAmz()->getCache($key)) {
            $this->countAmazonRequest();
            $time = microtime(true);
            $result = getKbAmazonApi()
                      ->responseGroup($responseGroup)
                      ->optionalParameters(array('MerchantId' => 'All'))
                      ->lookup($asin);
            /**
             * STATS
             */
            $ttf = microtime(true) - $time;
            $ttfOpt = getKbAmz()->getOption('averageTimeToFetch', 1);
            getKbAmz()->setOption('averageTimeToFetch', round(($ttfOpt + $ttf) / 2, 3));
            $timeSpent = getKbAmz()->getOption('timeSpentFetchingAmzProducts', 0);
            getKbAmz()->setOption('timeSpentFetchingAmzProducts', round($timeSpent + $time, 3));
            
            /**
             * STATS END
             */
            $data = new KbAmazonItem($result);
            self::cacheItem($data);
        }
        return $data;
    }
    
    /**
     * 
     * @param type $search
     * @param type $category
     * @param type $nodeId
     * @return \KbAmazonItems
     */
    public function search($search, $category, $nodeId = null, $page = null)
    {
        $result = getKbAmazonApi()
                  ->responseGroup('Large')
                  ->optionalParameters(array('MerchantId' => 'All', 'ItemPage' => $page))
                  ->category($category)
                  ->search($search, $nodeId);
        
        $this->countAmazonRequest();
        return new KbAmazonItems($result, true);
    }

    public function import($asins, $isSimilar = false)
    {
        $ids = array();
        if (!is_array($asins)) {
            $asins = array($asins);
        }
        foreach ($asins as $asin) {
            $item = $this->find($asin);
            if ($item->isValid()) {
                $ids[] = $this->saveProduct($item, $isSimilar);
            } else {
                $ids[] = array(
                    'post_id' => null,
                    'updated' => null,
                    'error' => $item->getError()
                );
            }
        }
        return $ids;
    }
    
    /**
     * 
     * @param type $asins
     */
    public function updatePrice($asins)
    {
        if (!is_array($asins)) {
            $asins = array($asins);
        }
        foreach ($asins as $asin) {
            $item = $this->find($asin, 'OfferSummary');
            if (!$item->getError()) {
                $this->updateProductPrice($item);
            }
        }
    }

    public function itemExists(KbAmazonItem $item)
    {
        $post = getKbAmz()->getProductByAsin($item->getAsin());
        return $post ? $post->ID : null;
    }
    
    /**
     * 
     * @param KbAmazonItem $item
     */
    public function updateProductPrice(KbAmazonItem $item)
    {
        $postId = $this->itemExists($item);
        
        if ($postId) {
            $meta = $item->getFlattenArray();
            $this->priceToMeta($meta);
            $this->updateProductPostMeta($meta, $postId);
            wp_update_post(array('ID' => $postId, 'post_modified' => date('Y-m-d H:i:s')));
            $this->checkAvailableAction($postId);
        }
    }

    protected function priceToMeta(&$meta)
    {
        $meta['PriceAmount'] = 0;
        $meta['PriceAmountFormatted'] = 0;
        if (isset($meta['OfferSummary.LowestNewPrice.FormattedPrice'])) {
            $meta['PriceAmountFormatted'] = $meta['OfferSummary.LowestNewPrice.FormattedPrice'];
            $meta['PriceAmount'] = $meta['OfferSummary.LowestNewPrice.Amount'];
        } else if(isset($meta['ItemAttributes.ListPrice.FormattedPrice'])) {
            $meta['PriceAmountFormatted'] = $meta['ItemAttributes.ListPrice.FormattedPrice'];
            $meta['PriceAmount'] = $meta['ItemAttributes.ListPrice.Amount'];
        } else if (isset($meta['OfferSummary.LowestRefurbishedPrice.FormattedPrice'])) {
            $meta['PriceAmountFormatted'] = $meta['OfferSummary.LowestRefurbishedPrice.FormattedPrice'];
            $meta['PriceAmount'] = $meta['OfferSummary.LowestRefurbishedPrice.Amount'];
        } else if (isset($meta['OfferSummary.LowestUsedPrice.FormattedPrice'])) {
            $meta['PriceAmountFormatted'] = $meta['OfferSummary.LowestUsedPrice.FormattedPrice'];
            $meta['PriceAmount'] = $meta['OfferSummary.LowestUsedPrice.Amount'];
        } else if (isset($meta['OfferSummary.LowestCollectiblePrice.FormattedPrice'])) {
            $meta['PriceAmountFormatted'] = $meta['OfferSummary.LowestCollectiblePrice.FormattedPrice'];
            $meta['PriceAmount'] = $meta['OfferSummary.LowestCollectiblePrice.Amount'];
        }
        
        $meta['PriceAmount'] = self::paddedNumberToDecial($meta['PriceAmount']);
    }
    
    /**
     * returns inserted meta key => val
     */
    protected function updateProductPostMeta($meta, $postId)
    {
        $metaToInsert = array();
        foreach ($meta as $key => $val) {
           $metaToInsert['KbAmz' . $key] = $val;
        }
        kbAmzFilterAttributes($metaToInsert);
        foreach ($metaToInsert as $key => $val) {
            update_post_meta($postId, $key, $val);
        }
        
        return $metaToInsert;
    }


    protected function saveProduct(KbAmazonItem $item, $isSimilar = false)
    {
        $postExists = $this->itemExists($item);
        $meta = $item->getFlattenArray();
        $meta['SimilarProducts'] = $item->getSimilarProducts();
        $this->priceToMeta($meta);
        
        $canImportFreeItems = getKbAmz()->getOption('canImportFreeItems', true);
        if (!$canImportFreeItems && empty($meta['PriceAmountFormatted']) && !$postExists) {
            return array(
                'post_id' => null,
                'updated' => null,
                'error' => __('Can not upload free items. Check in General Settings. ' . $item->getAsin())
            );
        }
        
        if (!$this->canUploadInCategory($item) && !$postExists) {
            return array(
                'post_id' => null,
                'updated' => null,
                'error' => __('Can not upload in this item top category. ' . $item->getAsin())
            );
        }
        
        if (!$postExists) {
            if (getKbAmz()->isMaxProductsCountReached()) {
                throw new Exception(
                    sprintf(
                        'Max number products of %s reached. Please update from the premium menu. Thank you.',
                        getKbAmz()->getOption('maxProductsCount')
                    )
                );
            }

            $admin = getKbAdminUser();
            $postArgs = array(
                'post_title' 	=> $item->getTitle(),
                'post_status' 	=> getKbAmz()->getOption('defaultPostStatus', 'pending'),
                'post_content' 	=> $this->getPreparedContent($item),
                'post_type' 	=> 'post',
                'menu_order' 	=> 0,
                'post_author' 	=> isset($admin->ID) ? $admin->ID : 1
            ); 
            $postId = wp_insert_post($postArgs);
            do_action('wp_insert_post', 'wp_insert_post');
            update_post_meta($postId, 'KbAmzASIN', $item->getAsin());
            getKbAmz()->addProductCount(1);
        } else {
            $postId = $postExists;
            wp_update_post(array('ID' => $postId, 'post_modified' => date('Y-m-d H:i:s')));
        }
             
        update_post_meta($postId, 'KbAmzLastUpdateTime', time());
        
        // Images
        $uploadedProductImages = getKbAmz()->getProductImages($postId);
        if (!$postExists || empty($uploadedProductImages)) {
            $images = $item->getImages();
            $postImages = array();
            if (!empty($images)) {
                $i = 0;
                foreach ($images as $imageUrl) {
                    $imageId = $this->downloadAndSaveImage($imageUrl, $postId, $i++, $item);
                    if ($imageId) {
                        $postImages[] = $imageId;
                    }
                }
            }

            if (!empty($postImages)) {
                update_post_meta($postId, "_thumbnail_id", $postImages[0]);
            }
        } 
        
        $metaToInsert = $this->updateProductPostMeta($meta, $postId);
        
        $specialMeta = array();
        $specialMeta['KbAmzNewProduct'] = 'no';
        $specialMeta['KbAmzOnSaleProduct'] = 'no';
        $specialMeta['KbAmzFreeProduct'] = 'no';
        
        if (getKbAmz()->isProductNew($metaToInsert, true)) {
            $specialMeta['KbAmzNewProduct'] = 'yes';
        } 
        if (getKbAmz()->isProductSale($metaToInsert, true)) {
            $specialMeta['KbAmzOnSaleProduct'] = 'yes';
        }
        if (getKbAmz()->isProductFree($metaToInsert, true)) {
            $specialMeta['KbAmzFreeProduct'] = 'yes';
        }
        
        foreach ($specialMeta as $key => $val) {
            update_post_meta($postId, $key, $val);
        }
        
        // no autoupdate for categories
        if (!$postExists) {
            $categories = !empty($this->importCategories)
                          ? $this->getImportCategories()
                          : $this->saveCategories($item->getNodes());

            wp_set_object_terms($postId, $categories, 'category', true);
        }

        if (!$postExists) {
            $loadSimilar = getKbAmz()->getOption('loadSimilarItems', KbAmazonImporter::SIMILAR_ITEM_LOAD_NO);
            if (!empty($meta['SimilarProducts']) && !$isSimilar && $loadSimilar !== KbAmazonImporter::SIMILAR_ITEM_LOAD_NO) {

                if ($loadSimilar == KbAmazonImporter::SIMILAR_ITEM_LOAD_IMMEDIATELY) {
                    $this->import($meta['SimilarProducts'], true);
                } else if ($loadSimilar == KbAmazonImporter::SIMILAR_ITEM_LOAD_CRON) {
                    $similarProducts = kbMergeUnique(getKbAmz()->getOption('ProductsToDownload', array()), $meta['SimilarProducts']);
                    getKbAmz()->setOption(
                        'ProductsToDownload',
                        $similarProducts
                    );
                }
            }
        }
        
        $this->updateProductContent($postId);
        
        $this->checkAvailableAction($postId);
        return array(
            'post_id' => $postId,
            'updated' => (bool) $postExists,
            'error' => null
        );
    }
    
    public function checkAvailableAction($postId)
    {
        // update status
        if (!getKbAmz()->isProductAvailable($postId)) {
            wp_update_post(array('ID' => $postId, 'post_status' => 'pending'));
            if (getKbAmz()->getOption('deleteProductOnNoQuantity')) {
                getKbAmz()->clearProduct($postId);
            }
        }
    }
    
    /**
     * @param KbAmazonItem $item
     */
    protected function getPreparedContent(KbAmazonItem $item)
    {
        return getKbAmz()->getShortCodePostContent();
    }
    
    public function updateProductContent($postId)
    {
        $pattern = get_shortcode_regex();
        $post = get_post($postId);
        $matches = array();
        $contentShortCode = getKbAmz()->getShortCode('content', true);
        preg_match_all('/'. $pattern .'/s', $post->post_content, $matches);
        if (isset($matches[0]) && is_array($matches[0])) {
            foreach ($matches[0] as $shortCode) {
                if (strpos($shortCode, $contentShortCode) !== false) {
                    $codeStr = str_replace(array('[', ']', $contentShortCode), '', $shortCode);
                    $atts = shortcode_parse_atts($codeStr);
                    if (isset($atts['replace']) && kbAmzShortCodeBool($atts['replace'])) {
                        $atts['id'] = $postId;
                        $doShortCodeStr = kbAmzShortCodeAttrToStr($contentShortCode, $atts);
                        $shortCodeContent = do_shortcode($doShortCodeStr);
                        $post->post_content = str_replace(
                            $shortCode,
                            "\n" . $shortCodeContent . "\n",
                            $post->post_content
                        );
                        wp_update_post($post);
                    }
                }
            }
        }
    }

    public function downloadAndSaveImage($imageUrl, $postId, $num, KbAmazonItem $item)
    {
        if (!empty($imageUrl)) {
            $canDowloadImages = getKbAmz()->getOption('downloadImages');
            
            $asinNum = $item->getAsin() . 'KBAMZIMG' . $num;
            $attachmentExists = getKbAmz()->getAttachmentsFromMeta('KbAmzAttachmentASIN', $asinNum);
            if (!empty($attachmentExists)) {
                return $attachmentExists[0]->ID;
            }
            
            $attachmentTitle = sprintf(
                '%s | %s',
                $item->getTitle(),
                $num
            );

            if ($canDowloadImages) {
                $uploads = wp_upload_dir();
                $uploads_path = $uploads['path'];
                // check if folder exist, if not create it
                if (!is_dir( $uploads_path )) {
                    mkdir( $uploads_path );
                }

                $fileExt = end(explode(".", $imageUrl));

                $filename = $asinNum . '-' . $item->getTitle();
                $filename = sanitize_title($filename);
                $filename = sanitize_file_name($filename);
                $filename = substr($filename, 0, 120);
                $filename = $filename . "." . $fileExt;
                // Save image in uploads folder
                $image = file_get_contents($imageUrl);
                file_put_contents( $uploads_path . '/' . $filename, $image );
                $image_path = $uploads_path . '/' . $filename; // Path of the image on the disk
            } else {
                $image_path = $imageUrl; // Path of the image on the disk
            }

            
            $wp_filetype = wp_check_filetype(basename( $image_path ), null);
            $attachment = array(
               'post_mime_type' => $wp_filetype['type'],
               'post_title'     => $attachmentTitle,
               'post_content'   => $imageUrl,
               'post_status'    => 'inherit'
            );
            if ($canDowloadImages) {
                $attach_id = wp_insert_attachment( $attachment, $image_path, $postId);
                $attach_data = wp_generate_attachment_metadata( $attach_id, $image_path );
                wp_update_attachment_metadata($attach_id, $attach_data);
            } else {
                $attach_id = wp_insert_attachment( $attachment, false, $postId);
                update_post_meta($attach_id, '_wp_attached_file', $image_path );
                update_post_meta($postId, '_thumbnail_id', $attach_id );
                /* METADATA */
                $imagesize = getimagesize( $image_path );
		$metadata['width'] = $imagesize[0];
		$metadata['height'] = $imagesize[1];
		$metadata['file'] = $image_path;
                $metadata['sizes'] = array();
                wp_update_attachment_metadata($attach_id, $metadata);
            }
            update_post_meta($attach_id, 'KbAmzAttachmentASIN', $asinNum);
            return $attach_id;
        }
    }
    
    
    public function getCategoriesFromNodes($nodes)
    {
        $categories = array();
        foreach ($nodes as $key => $node) {
            $categories[$key] = array();
            $cats = array();
            $this->getCategoriesFromResult($node, $cats);
            $categories[$key] = array_reverse($cats);
        }
        return $categories;
    }


    public function saveCategories($nodes) {
        $postCategories = array();
        $categories = $this->getCategoriesFromNodes($nodes);

        foreach ($categories as $nodeCategories) {
            $parent = 0;
            foreach ($nodeCategories as $catName) {
                if (empty($catName) || is_numeric($catName)) {
                    continue;
                }
                $termId = term_exists($catName, 'category', $parent);
                if ($termId > 0) {
                    if (isset($termId['term_id'])) {
                        $parent = $termId['term_id'];
                    } else {
                        $parent = $termId;
                    }
                } else {
                    $termId = wp_insert_term($catName, 'category', array('parent' => $parent, 'description' => getKbAmz()->getCategoryDescriptionMarkup()));
                    if (is_object($termId)) {
                        if (isset($termId->error_data['term_exists'])) {
                            $parent = (int) $termId->error_data['term_exists'];
                        } else {
                            continue;
                        }
                    } else {
                        if (isset($termId['term_id'])) {
                            $parent = (int) $termId['term_id'];
                        } else {
                            $parent = (int) $termId;
                        }
                    }
                }
                $postCategories[] = (int) $parent;
            }
        }
        return $postCategories;
    }
    
    function getCategoriesFromResult($node, &$categories)
    {
        if (isset($node['Name'])) {
           $categories[] = $node['Name'];
        }
        if(isset($node['Ancestors']) && isset($node['Ancestors']['BrowseNode'])) {
                $this->getCategoriesFromResult($node['Ancestors']['BrowseNode'], $categories);
        }
    }
    
    public function canUploadInCategory(KbAmazonItem $item)
    {
        $cats = $this->getCategoriesFromNodes($item->getNodes());
        
        if (empty($cats)) {
            return false;
        }

        $allCats = array();
        foreach ($cats as $subCats) {
            foreach ($subCats as $cat) {
                $allCats[] = $cat;
            }
        }
        
        $enabled = getKbAmz()->getAmazonTopEnabledCategories();
        $disabled = getKbAmz()->getAmazonTopDisabledCategories();
        
        if (!empty($enabled)) {
            $isEnabled = false;
            foreach ($allCats as $cat) {
                if (!$isEnabled && in_array($cat, $enabled)) {
                    $isEnabled = true;
                    break;
                }
            }
            return $isEnabled;
        }

        if (!empty($disabled)) {
            $isDisabled = true;
            foreach ($allCats as $cat) {
                if (!$isDisabled && in_array($cat, $disabled)) {
                    $isDisabled = false;
                    break;
                }
            }
            return $isDisabled;
        }
        // all categories go
        return true;
    }


    public function getUrlAsins($url)
    {
        $key = sha1($url);
        if (!$data = wp_cache_get($key)) {
            $data = $this->getUrlResponse(
                sprintf(
                    '%s?check=%s&q=%s',
                    get_site_url(),
                    getKbAmz()->getSecret(),
                    urlencode($url)
                )  
            );
            $data = empty($data) ? array() : unserialize($data);
            if (!empty($data)) {
                wp_cache_set($key, $data);
            }
        }
        return $data;
    }
    
    public function getUrlItems($url)
    {
        set_time_limit(90);
        $asins = $this->getUrlAsins($url);
        $items = array();
        foreach ($asins as $asin) {
            try {
                $item = $this->find($asin['asin']);
            } catch (Exception $e) {
                $item = new KbAmazonItem($asin);
                getKbAmz()->addException('Url Items', $e->getMessage());
            }
            if ($item->isValid()) {
                $items[] = $item;
            }
        }
        return $items;
    }

    /**
     * 
     * @param type $url
     * @return type
     */
    public function getUrlResponse($url)
    {
        static $cache;
        if (!isset($cache[$url])) {
            $response = wp_remote_get($url);
            if (is_array($response) && isset($response['body']) && !empty($response['body'])) {
                $cache[$url] = $response['body'];
            } else {
                $cache[$url] = null;
            }
        }
        return $cache[$url];
    }
    
    function getBetween($content,$start,$end){
        $r = explode($start, $content);
        if (isset($r[1])){
            $r = explode($end, $r[1]);
            return $r[0];
        }
        return '';
    }
    
    public static function paddedNumberToDecial($num)
    {
        if ($num > 0) {
            $last = substr($num, -2);
            $num = substr($num, 0, strlen($num) - 2) . '.' . $last;
            return round($num, 2);
        }
        return 0;
    }
}

