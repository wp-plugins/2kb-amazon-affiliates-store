<?php
!defined('ABSPATH') and exit;

/**
 * Menus
 */

add_action('admin_menu','kb_amz_admin_menu', 100);
function kb_amz_admin_menu()
{
    add_menu_page(
        __( '2kb Amazon Store'),
        __( '2kb Amazon Store'),
        'manage_options',
        'kbAmz',
        array(getKbAmzAdminController(), 'indexAction'),
        getKbPluginUrl() . '/template/admin/img/amazon-icon16x16.gif',
        84
    );
}

/**
 * CRONS
 */
if (!wp_next_scheduled('kbAmzSyncNetwork') ) {
    wp_schedule_event(
        time(),
        86400,
        'kbAmzSyncNetwork'
    );
}
add_action('kbAmzSyncNetwork', 'kbAmzSyncNetworkFunction');

function kbAmzSyncNetworkFunction()
{
    $data = getKbAmz()->getOption('siteNetwork');
    if (!empty($data) && $data['siteActive']) {
        $api = new KbAmzApi(getKbAmz()->getStoreId());
        $data['siteHealth'] = getKbAmzStoreHealth();
        $api->setUser($data);
    }
}

if (!wp_next_scheduled('kbAmzDownloadProductsCron') ) {
    wp_schedule_event(
        time(),
        getKbAmz()->getOption('downloadProductsCronInterval', getKbCronFirstInterval()),
        'kbAmzDownloadProductsCron'
    );
}
add_action('kbAmzDownloadProductsCron', 'kbAmzDownloadProductsCronFunction');

function kbAmzDownloadProductsCronFunction($execute = false)
{
    if (!$execute && !getKbAmz()->getOption('isCronEnabled', 1)) {
        getKbAmz()->setOption('LastCronRun', date('Y-m-d H:i:s') . ' ' . __('Cron Disabled From Settings.'));
        return;
    }
    getKbAmz()->setIsCronRunnig(true);
    $importer = new KbAmazonImporter;
    
    $products = getKbAmz()->getOption('ProductsToDownload', array());
    $numberToProcess = (int) getKbAmz()->getOption('downloadProductsCronNumberToProcess', KbAmazonImporter::CRON_NUMBER_OF_PRODUCTS_TO_PROCESS);
    $i = 0;

    foreach ($products as $key => $asin) {
        if ($i >= $numberToProcess) {
            break;
        }
        try {
           $result = $importer->import($asin);
           if (empty($result[0]['error'])) {
           } else {
               getKbAmz()->addException('Cron Import Product (AMZ Item)', $result[0]['error']);
           }
           unset($products[$key]);
           getKbAmz()->setOption('ProductsToDownload', $products);
          
       } catch (Exception $e) {
           getKbAmz()->addException('Cron Import Product', $e->getMessage());
       }
        $i++;
    }
    getKbAmz()->setOption('LastCronRun', date('Y-m-d H:i:s'));
    getKbAmz()->setIsCronRunnig(false);
}

if (!wp_next_scheduled('kbAmzProductsUpdateCron') ) {
    wp_schedule_event(
        time(),
        getKbAmz()->getOption('updateProductsPriceCronInterval', getKbCronFirstInterval()),
        'kbAmzProductsUpdateCron'
    );
}
add_action('kbAmzProductsUpdateCron', 'kbAmzkbAmzProductsUpdateCronFunction');

function kbAmzkbAmzProductsUpdateCronFunction($execute = false)
{
    if (!$execute && !getKbAmz()->getOption('isCronEnabled', 1)) {
        getKbAmz()->setOption('LastCronRun', date('Y-m-d H:i:s') . ' ' . __('Cron Disabled From Settings.'));
        return;
    }
    
    getKbAmz()->setIsCronRunnig(true);
    $numberOfProductsToUpdate = getKbAmz()->getOption(
        'updateProductsPriceCronNumberToProcess',
        KbAmazonImporter::CRON_NUMBER_OF_PRODUCTS_PRICE_TO_UPDATE
    );
    
    $importer = new KbAmazonImporter;

    $asins = getKbAmz()->getProductsAsinsToUpdate($numberOfProductsToUpdate, false);
    foreach ($asins as $asin) {
        try {
            $importer->import($asin);
        } catch (Exception $e) {
            getKbAmz()->addException('Cron Update Product', $e->getMessage());
        }
    }
    
    getKbAmz()->setIsCronRunnig(false);
    getKbAmz()->setOption('LastCronRun', date('Y-m-d H:i:s'));
}

if (isset($_GET['kbAction'])
&& $_GET['kbAction'] == 'KbAmzCronAction'
&& isset($_GET['secret'])
&& $_GET['secret'] == getKbAmz()->getSecret()) {
    
    add_action('init', 'kbAmzTriggerManualCronJobs');
    function kbAmzTriggerManualCronJobs()
    {
        kbAmzDownloadProductsCronFunction(true);
        kbAmzkbAmzProductsUpdateCronFunction(true);
        http_response_code(200);
        die('Kb Amz Cron Done. ' . date('Y-m-d H:i:s'));
    }
}

if (isset($_GET['kbAction'])
&& $_GET['kbAction'] == 'KbAmzCronImportProductsAction'
&& isset($_GET['secret'])
&& $_GET['secret'] == getKbAmz()->getSecret()) {
    
    add_action('init', 'kbAmzTriggerManualInsertCronJobs');
    function kbAmzTriggerManualInsertCronJobs()
    {
        kbAmzDownloadProductsCronFunction(true);
        http_response_code(200);
        die('Kb Amz Insert Cron Done. ' . date('Y-m-d H:i:s'));
    }
}

if (isset($_GET['kbAction'])
&& $_GET['kbAction'] == 'KbAmzCronUpdateProductsAction'
&& isset($_GET['secret'])
&& $_GET['secret'] == getKbAmz()->getSecret()) {
    
    add_action('init', 'kbAmzTriggerManualUpdateCronJobs');
    function kbAmzTriggerManualUpdateCronJobs()
    {
        kbAmzkbAmzProductsUpdateCronFunction(true);
        http_response_code(200);
        die('Kb Amz Update Cron Done. ' . date('Y-m-d H:i:s'));
    }
}


/**
 * Add to cart
 */
add_action('wp_ajax_kbAddToCartAction', 'kbAddToCartActionAjax');
add_action('wp_ajax_nopriv_kbAddToCartAction', 'kbAddToCartActionAjax');
function kbAddToCartActionAjax()
{
    getKbAmz()->addToAjaxCart();
}

/**
 * Add to cart
 */
add_action('wp_ajax_kbRemoveFromCartAction', 'kbRemoveFromCartActionAjax');
add_action('wp_ajax_nopriv_kbRemoveFromCartAction', 'kbRemoveFromCartActionAjax');
function kbRemoveFromCartActionAjax()
{
    getKbAmz()->removeFromAjaxCart();
}


function kbAmzRemoveCategoryDeleteMarker($desc) {
    $markUp = getKbAmz()->getCategoryDescriptionMarkup();
    return str_replace($markUp, '', $desc);
}
add_filter( 'category_description', 'kbAmzRemoveCategoryDeleteMarker' );

/**
 * External Url Option fix
 */
add_filter('wp_get_attachment_url', 'kbAmzFixImageExternalUrl');
function kbAmzFixImageExternalUrl($url) {
    if (strpos($url, 'images-amazon.com') !== false
    || strpos($url, 'amazon.com') !== false) {
        $parts = explode('/http', $url);
        if (isset($parts[1])) {
            return 'http' . $parts[1];
        }
    }
    return $url;
}


/**
 * Serialize session
 */
add_action('shutdown', 'kbAmzSerializeSession', 99999);

function kbAmzSerializeSession()
{
    if (isset($_SESSION['2kb-amazon-affiliates-store']['cache'])
    && is_array($_SESSION['2kb-amazon-affiliates-store']['cache'])) {
        if (count($_SESSION['2kb-amazon-affiliates-store']['cache']) > 100) {
            $_SESSION['2kb-amazon-affiliates-store']['cache']
            = array_slice($_SESSION['2kb-amazon-affiliates-store']['cache'], -100);
        }
        $_SESSION['2kb-amazon-affiliates-store']['cache']
        = serialize($_SESSION['2kb-amazon-affiliates-store']['cache']);
    } else {
        $_SESSION['2kb-amazon-affiliates-store']['cache'] = array();
    }
}

/**
 * Plugin recheck
 */
register_activation_hook(KbAmazonStorePluginPath . '/plugin.php', 'kbAmzStorePluginActivated');
function kbAmzStorePluginActivated()
{
    $api = new KbAmzApi(getKbAmz()->getStoreId());
    $result = $api->getProductsCount();
    getKbAmz()->setOption('maxProductsCount', $result);
}

add_filter( 'wp_revisions_to_keep', 'kbAmzProductRevisions', 99999, 2 );
function kbAmzProductRevisions($num, $post)
{
    if (getKbAmz()->isPostProduct($post->ID)) {
        return 0;
    } else {
        return $num;
    }
}

add_action('restrict_manage_posts', 'kbAmzPostsPageFiltersSelect');
function kbAmzPostsPageFiltersSelect()
{
    $type = 'post';
    if (isset($_GET['post_type'])) {
        $type = $_GET['post_type'];
    }
    
    if ('post' == $type){
        $values = array(
            false => __('Don`t Show Amz Products'),
            true  => __('Show Amz Products'),
        );
        $filter = isset($_GET['kbAmzShowProductsFilter'])
                ? $_GET['kbAmzShowProductsFilter']
                : getKbAmz()->getOption('showProductsInAdminPosts');
        ?>
        <select name="kbAmzShowProductsFilter" style="color:<?php echo !$filter ? '#d54e21!important;' : 'green!important;'?>" onchange="this.style = ''">
        <?php
            foreach ($values as $value => $label) {
                printf
                    (
                        '<option value="%s"%s style="color:%s!important;">%s</option>',
                        $value,
                        $value == $filter? ' selected="selected"':'',
                        (!$value ? '#d54e21' : 'black'),
                        $label
                    );
                }
        ?>
        </select>
        <?php
    }
}


add_filter('parse_query', 'kbAmzPostsPageFilters');
function kbAmzPostsPageFilters($query)
{
    global $pagenow;
    $type = 'post';
    if (isset($_GET['post_type'])) {
        $type = $_GET['post_type'];
    }
    
    if (!is_admin() || $type != 'post' || $pagenow != 'edit.php') {
        return $query;
    }
   

    if (!isset($query->query_vars['meta_query'])
    || !is_array($query->meta_query)) {
        $query->query_vars['meta_query'] = array();
    }
    
    if (isset($_GET['kbAmzShowProductsFilter'])) {
        getKbAmz()->setOption('showProductsInAdminPosts', boolval($_GET['kbAmzShowProductsFilter']));
    }
    
    if ((isset($_GET['kbAmzShowProductsFilter']) && $_GET['kbAmzShowProductsFilter'])
    || getKbAmz()->getOption('showProductsInAdminPosts')) {
        $query->query_vars['meta_query'][] = array(
            'key'       => 'KbAmzASIN',
            'compare'   => 'EXISTS',
            //'value'     => ''
        );
    } else {
        $query->query_vars['meta_query'][] = array(
            'key'       => 'KbAmzASIN',
            'compare'   => 'NOT EXISTS',
            //'value'     => ''
        );
    }
    
    return $query;
}


if (isset($_GET['kbAction'])
&& $_GET['kbAction'] == 'KbAmzNetworkGetProduct'
&& isset($_GET['asin'])) {
    
    add_action('init', 'kbAmzTriggerManualUpdateCronJobs');
    function kbAmzTriggerManualUpdateCronJobs()
    {
        $response = array();
        $asin     = $_GET['asin'];
        $responseGroup = array(
            'Offers',
            'OfferFull',
            'OfferSummary',
            'OfferListings',
        );
        try {
           
            $data = getKbAmz()->getOption('siteNetwork');
            if (empty($data) || !$data['siteActive']) {
                throw new Exception('Not Joined');
            }
             
            $requests = getKbAmz()->getOption('amazonApiRequests', array());
            $key      = date('YmdH');
            $limit    = 1800;
            if (!empty($requests)) {
                $r = $requests[key($requests)];
                if (isset($r['limit']) && $r['limit'] / 2 > $limit) {
                    $limit = $r['limit'] / 2;
                }
            }
            
            $requests = getKbAmz()->getOption('networkRequests', array());
            if (empty($requests) || !isset($requests[$key])) {
                $requests = array(
                    $key => array(
                        'count' => 0
                    )
                );
            }
           
            if ($requests[$key]['count'] > $limit) {
                throw new Exception('Limit');
            }
            
            $requests[$key]['count']++;
            getKbAmz()->setOption('networkRequests', $requests);
            
            $importer            = new KbAmazonImporter;
            $item                = $importer->find($asin, $responseGroup);
            $response['item']    = $item->isValid() ? base64_encode(serialize($item)) : null;
            $response['success'] = $item->isValid();
        } catch (Exception $e) {
            $response['success'] = false;
            $response['error']   = $e->getMessage();
            getKbAmz()->addException('Newtwork Product Fetch', $e->getMessage());
        }
        
        echo sprintf(
            ';%s%s(%s);',
            PHP_EOL,
            $_GET['callback'],
            json_encode($response)
        );
        http_response_code(200);
        die;
    }
}