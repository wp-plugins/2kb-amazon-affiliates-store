<?php
!defined('ABSPATH') and exit;

/**
 * Menus
 */

add_action('admin_menu','kb_amz_admin_menu');
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
                unset($products[$key]);
            } else {
                getKbAmz()->addException('Cron Import Product (AMZ Item)', $result[0]['error']);
            }
            $i++;
        } catch (Exception $e) {
            getKbAmz()->addException('Cron Import Product', $e->getMessage());
        }
     }
    getKbAmz()->setOption('ProductsToDownload', $products);
    getKbAmz()->setOption('LastCronRun', date('Y-m-d H:i:s'));
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
    
    $numberOfProductsToUpdate = getKbAmz()->getOption(
        'updateProductsPriceCronNumberToProcess',
        KbAmazonImporter::CRON_NUMBER_OF_PRODUCTS_PRICE_TO_UPDATE
    );
    
    $asins = getKbAmz()->getProductsAsinsToUpdate();
    $importer = new KbAmazonImporter;
    $i = 0;
    foreach ($asins as $key => $asin) {
        if ($i >= $numberOfProductsToUpdate) {
            break;
        }
        try {
            $importer->import($asin);
            $i++;
        } catch (Exception $e) {
            getKbAmz()->addException('Cron Update Product', $e->getMessage());
        }
    }
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
        wp_die('Kb Amz Cron Done. ' . date('Y-m-d H:i:s'));
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
        wp_die('Kb Amz Insert Cron Done. ' . date('Y-m-d H:i:s'));
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
        wp_die('Kb Amz Update Cron Done. ' . date('Y-m-d H:i:s'));
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