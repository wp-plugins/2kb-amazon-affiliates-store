<?php

!defined('ABSPATH') and exit;

/**
 * 
 * @staticvar KbAmazonStore $kbAmazonStore
 * @return \KbAmzAdminController
 */
function getKbAmzAdminController() {
    static $KbAmzAdminController;
    if (!$KbAmzAdminController) {
        $KbAmzAdminController = new KbAmzAdminController;
    }
    return $KbAmzAdminController;
}
// do it
getKbAmzAdminController();

class KbAmzAdminController {

    protected $messages = array();

    
    public function __construct()
    {
        add_action('wp_ajax_kbAmzPremiumAction', array($this, 'premiumAction'));
        add_action('wp_ajax_kbAmzPremiumActivateAction', array($this, 'premiumActivate'));
        add_action('wp_ajax_kbAmzSetOption', array($this, 'kbAmzSetOption'));
        add_action('wp_ajax_kbAmzLoadItemPreview', array($this, 'kbAmzLoadItemPreviewAction'));
        add_action('wp_ajax_kbAmzImportItem', array($this, 'kbAmzImportItemAjaxAction'));
        add_action('wp_ajax_kbAmzNetworkMembers', array($this, 'networkMembersAction'));
        add_action('wp_ajax_kbAmzNetworkGetProductsToSync', array($this, 'kbAmzNetworkGetProductsToSyncAction'));
        add_action('wp_ajax_kbAmzNetworkImportProduct', array($this, 'kbAmzNetworkImportProductAction'));
        
        
    }
    
    function kbAmzImportItemAjaxAction()
    {
        $asin     = $_POST['asin'];
        $importer = new KbAmazonImporter;
        if (isset($_POST['post_category']) && !empty($_POST['post_category'])) {
            $importer->setImportCategories($_POST['post_category']);
        }
        $item     = $importer->find($asin);
        $result   = $importer->saveProduct($item);
        $view = new KbView(array('item' => $item), $this->getTemplatePath('kbAmzLoadItemPreview'));
        echo json_encode(
            array(
                'html'      => $item->isValid() ?  $view->getContent() : null,
                'result'    => $result
            )
        );
        exit;
    }
    
    public function kbAmzLoadItemPreviewAction()
    {
        $asin     = $_POST['asin'];
        $importer = new KbAmazonImporter;
        $item     = $importer->find($asin);
        
        $view = new KbView(array('item' => $item), $this->getTemplatePath('kbAmzLoadItemPreview'));
        
        echo json_encode(
            array(
                'html' => $item->isValid() ?  $view->getContent() : null
            )
        );
        exit;
    }

    public function premiumActivate()
    {
        $data = array();
        $data['success'] = false;
        if (isset($_POST['purchaseId'])) {
            $api = new KbAmzApi(getKbAmz()->getStoreId());
            $purchaseId = $_POST['purchaseId'];
            $result = $api->getOrderActivationData($purchaseId);
            if ($result) {
                if (isset($result->error)) {
                    $data['msg'] = $result->error;
                } else if ($result->addNumberOfProducts) {
                    $maxProductsCount = getKbAmz()->getOption('maxProductsCount');
                    $productsToAdd = intval($result->addNumberOfProducts);
                    getKbAmz()->setOption('maxProductsCount', $maxProductsCount + $productsToAdd);
                    $data['success'] = true;
                    $api->activatePurchase($purchaseId);
                } else {
                    $data['msg'] = __('Already activated.');
                    $data['success'] = true;
                }
            } else {
                $data['msg'] = __('Server is unreachable.');
            }
        } else {
            $data['msg'] = __('Invalid parameter.');
        }
        echo json_encode($data);
        exit;
    }

    public function indexAction() {
        $action = isset($_GET['kbAction']) ? $_GET['kbAction'] . 'Action' : null;
        if (method_exists($this, $action)) {
            $view = call_user_func_array(array($this, $action), array());
            if (!$view->hasTemplate()) {
                $view->setTemplate($this->getTemplatePath($_GET['kbAction']));
            }
        } else {
            $view = new KbView(array(), $this->getTemplatePath('index'));
        }
        $view->setLayout(KbAmazonStorePluginPath . '/template/admin/layout');
        $view->actions = $this->getActions();
        $view->messages = $this->messages;
        $view->bodyClass = $action;
        echo $view;
    }
    
    public function improveAction()
    {
        if (isset($_GET['improvePluginExperience'])) {
            getKbAmz()->setOption(
                'sendStatsData',
                $_GET['improvePluginExperience']
            );
            getKbAmz()->setOption(
                'showStatsDataJoinModal',
                '0'
            );
            unset($_GET['improvePluginExperience']);
        }
        
        $view = new KbView(array());
        return $view;
    }

    public function importByAsinAction() {
        $data = array();
        if (isset($_POST['asin']) && !empty($_POST['asin'])) {
            $importer = new KbAmazonImporter;
            if (isset($_POST['post_category']) && !empty($_POST['post_category'])) {
                $importer->setImportCategories($_POST['post_category']);
            }
            $statuses = $importer->import($_POST['asin']);
            $status = $statuses[0];
            if ($status['error']) {
                $this->messages[] = array($status['error'], 'alert-warning');
            } else if ($status['updated']) {
                $this->messages[] = array(
                    sprintf(__('Product with id %s is updated.'), $status['post_id']),
                    'alert-success'
                );
            } else {
                $this->messages[] = array(
                    sprintf(__('Product with id %s is inserted.'), $status['post_id']),
                    'alert-success'
                );
            }
        }

        $view = new KbView($data);
        return $view;
    }

    public function importByUrlAction() {
        $this->messages[] = array(__("Large amount of items will require more time to load. To import variants, they must be allowed from the General Settings"), 'alert-warning');
        $this->messages[] = array(__("You can proceed to Import at the bottom, without waiting for all items to load."), 'alert-info');
        $data = array();
        if (isset($_POST['url']) && !empty($_POST['url']) && isset($_POST['load'])) {
            $data['url'] = $_POST['url'];
            $importer = new KbAmazonImporter;
            // $items = $importer->getUrlItems($data['url']);
            $items = $importer->getUrlAsinItems($data['url']);
            if (!empty($items)) {
                $data['items'] = $items;
                $data['addItemsTemplate'] = $this->getTemplatePath('importItemsWithGallery');
            } else {
                $this->messages[] = array(__("Unable to load the link."), 'alert-warning');
            }
        }
        $this->importItemsWithGallery();
        $view = new KbView($data);
        $view->useAjax = true;
        return $view;
    }

    public function importBySearchAction()
    {
        $data = array();
        $importer = new KbAmazonImporter;
        if (isset($_GET['search']) && (!empty($_GET['category']) || !empty($_GET['categoryName']))) {
            $category = !empty($_GET['categoryName'])
                        ? $_GET['categoryName']
                        : $importer->getAmazonCategory($_GET['category']);
            
            $resultSet = $importer->search(
                $_GET['search'],
                $category,
                null,
                (isset($_GET['kbpage']) ? $_GET['kbpage'] : null)
            );
  
            if ($resultSet->isValid()) {
                $data['resultSet'] = $resultSet;
                $data['items'] = $resultSet->getItems();
                $data['addItemsTemplate'] = $this->getTemplatePath('importItemsWithGallery');
            } else {
                $this->messages[] = array($resultSet->getError(), 'alert-warning');
            }
        }
        $data['categoriesGroups'] = $importer->getAmazonCategoryGroups();
        $data['categories'] = $importer->getAmazonCategories();
        foreach ($data['categories'] as $key => $val) {
            $data['categories'][$data['categoriesGroups'][$key]] = $val;
            unset($data['categories'][$key]);
        }
        $this->importItemsWithGallery();
        $view = new KbView($data);
        $view->useAjax = true;
        $view->areLoaded = true;
        return $view;
    }

    protected function importItemsWithGallery()
    {
        set_time_limit(300);
        if (isset($_POST['import']) && isset($_POST['asin']) && !empty($_POST['asin'])) {
            $importer = new KbAmazonImporter;
            if (isset($_POST['post_category']) && !empty($_POST['post_category'])) {
                $importer->setImportCategories($_POST['post_category']);
            }
            
            $statuses = array();
            $download = getKbAmz()->getOption('ProductsToDownload', array());
            $cron = 0;
            foreach ($_POST['asin'] as $asin) {
                try {
                    $status = $importer->import($asin, true);
                    $statuses = array_merge($statuses, $status);
                } catch (Exception $e) {
                    getKbAmz()->addException('Import From Search', $e->getMessage());
                    if (!in_array($asin, $download)) {
                        $download[] = $asin;
                        $cron++;
                    }
                }
            }
            getKbAmz()->setOption('ProductsToDownload', $download);
            
            $inserted = 0;
            $updated = 0;
            $errors = 0;
           
            foreach ($statuses as $s) {
                if ($s['updated']) {
                    $updated++;
                } else if ($s['error']) {
                    $errors++;
                } else {
                    $inserted++;
                }
            }
            $this->messages[] = array(
                sprintf(
                        __('Inserted = %s, Updated = %s, Errors = %s, Added to Cron %s'), $inserted, $updated, $errors, $cron
                ),
                'alert-success'
            );
        }
    }

    public function settingsAmazonApiAction()
    {
        $data = array();
        if (isset($_POST['amazon']) && !empty($_POST['amazon'])) {
            getKbAmz()->setOptions(
                array(
                    'amazonApiRequestDelay' => $_POST['amazonApiRequestDelay'],
                    'amazon'                => $_POST['amazon']
                )
            );
//            try {
//                getKbAmz()->setOption('amazon', $_POST['amazon']);
//                $amazonApi = getKbAmazonApi();
//                $amazonApi->category('Phone');
//                $result = $amazonApi->search('iphone');
//                $this->messages[] = array(__('Credentials Updated'), 'alert-success');
//            } catch (Exception $e) {
//                getKbAmz()->setOption('amazon', array());
//                $this->messages[] = array(sprintf(__('Invalid Credentials: %s'), $e->getMessage()), 'alert-danger');
//                getKbAmz()->addException('Amazon API', sprintf(__('Invalid Credentials: %s'), $e->getMessage()));
//            }
        }
        $importer = new KbAmazonImporter;
        $data['groups'] = $importer->getAmazonCategoryGroups();
        $view = new KbView($data);
        return $view;
    }
    
    public function settingsErrorLogAction()
    {
        $data = array();
        $data['errors'] = getKbAmz()->getExceptions();
        if (empty($data['errors'])) {
            $this->messages[] = array(__('The log is empty.'), 'alert-success');
        } else {
            $this->messages[] = array(__('Note that it is normal to have Amazon API exceptions.'), 'alert-warning');
        }
        $view = new KbView($data);
        return $view;  
    }


    public function productsAttributesAction()
    {
        if (!empty($_POST)) {
            $productAttributes = array();
            foreach ($_POST['attr'] as $key => $attr) {
                $productAttributes[$attr] = $_POST['label'][$key];
            }
            getKbAmz()->setOption('productAttributes', $productAttributes);
            $this->messages[] = array(__('Product Attributes Updated'), 'alert-success');
        }

        $data = array();
        $data['title'] = __('Drag and Drop to Reorder Your Product Attributes. Note that if the product does not have given attribute it will not show it.');
        $data['titleLeft'] = __('Product Attributes');
        $data['attributes'] = getKbAmz()->getAttributes();
        $data['activeAttributes'] = getKbAmz()->getShortCodeAttributes();
        $data['defaultAttributes'] = getKbAmz()->getDefaultAttributes();
        
        foreach ($data['activeAttributes'] as $key => $val) {
            if (isset($data['attributes'][$key])) {
                unset($data['attributes'][$key]);
            }
        }
        
        $view = new KbView($data);
        return $view;
    }

    public function productsExplodeAttributesAction()
    {
        $data = array();
        $data['attributes'] = getKbAmz()->getAttributes();
        
        if (isset($_GET['attr'])) {
            $data['values'] = getKbAmz()->getMetaCountListWidgetMethod($_GET['attr'], 999999);
        }
        
        $view = new KbView($data);
        return $view;
    }

    public function productsExcludeAttributesAction()
    {
        if (!empty($_POST)) {
            $attrs = isset($_POST['excludedAttributes']) && !empty($_POST['excludedAttributes'])
                   ? $_POST['excludedAttributes'] : array();
            getKbAmz()->setOption('excludedAttributes', $attrs);
            
            if ($_POST['deleteExcluded'] && !empty($attrs)) {
                getKbAmz()->deleteAttributesByKey($attrs);
            }
            
            $this->messages[] = array(__('Attributes are excluded successfully.'), 'alert-success');
        }
        
        $data = array();
        $data['attributes'] = getKbAmz()->getAttributes();
        $data['excludedAttributes'] = getKbAmz()->getOption('excludedAttributes', array());
        $data['excludedAttributesDisabled'] = getKbAmz()->getExcludeDisabledAttributes();
        $view = new KbView($data);
        return $view;
    }


    public function productsShortCodesAction()
    {
        $data = array();
        if (!empty($_POST)) {
            $_POST['shortCodePostContent'] = stripslashes($_POST['shortCodePostContent']);
            $action = $_POST['submit'];
            unset($_POST['submit']);
            if ($action == 'defaults') {
                getKbAmz()->setOption('shortCodePostContent', null);
                $this->messages[] = array(__('Defaults restored.'), 'alert-success'); 
            } else if ($action == 'update') {
                $codes = getKbAmz()->getShortCodes();
                $updated = array();
                foreach ($codes as $name => $code) {
                    if (isset($_POST[$name]['active']) && $_POST[$name]['active']) {
                        $updated[$name]['active'] = true;
                    } else {
                        $updated[$name]['active'] = false;
                    }
                }
                getKbAmz()->setOption('productShortCodes', $updated);

                $shortCodeContent = $_POST['shortCodePostContent'];
                getKbAmz()->setOption('shortCodePostContent', $shortCodeContent);
                if ($_POST['updateForAllPosts']) {
                    getKbAmz()->updateAllProductsContent($shortCodeContent);
                }

                $this->messages[] = array(__('Product Short Codes Updated'), 'alert-success');
                $this->messages[] = array(__('If the content shortcode has param replace="Yes", the content will be updated on the next product update via the cron job.'), 'alert-success');
            }

        }
        
        $data['shortCodes'] = getKbAmz()->getShortCodes();
        $view = new KbView($data);
        return $view;
    }

    public function productsVisibilityAction()
    {
        $data = array();
        //$this->messages[] = array(__('Product Short Codes Updated'), 'alert-warning');
        if (isset($_POST['defaultPostStatus'])) {
            getKbAmz()->setOption('defaultPostStatus', $_POST['defaultPostStatus']);
            $this->messages[] = array(__('Status Changed.'), 'alert-success');
            if (isset($_POST['update'])) {
                getKbAmz()->updateProductsStatus(getKbAmz()->getOption('defaultPostStatus', 'pending'));
                $this->messages[] = array(__('All Products Status Changed.'), 'alert-success');
            }
        }
        
        $view = new KbView($data);
        return $view;
    }

    public function widgetsAction()
    {
         $this->messages[] = array(__('All widgets are available with configuration in Wordpres widgets area. Check the one with Kb Shop prefix.'), 'alert-success');
         
        $data = array();
        $data['widgetsTypes'] = getKbAmz()->getWidgetsTypes();
        $view = new KbView($data);
        return $view;
    }
    
    public function settingsGeneralAction()
    {
        $data = array();
        if (!empty($_POST)) {
            $data = $_POST;
            unset($data['update']);
            getKbAmz()->setOptions($data);
        }
        $data['cronTime'] = array();
        foreach (wp_get_schedules() as $timeStr => $opts) {
            $data['cronTime'][$timeStr] = $timeStr;
        }
        $data['hoursOld'] = array();
        for ($i = 1; $i<= 24 * 30; $i++) {
            $data['hoursOld'][$i * 3600] = $i .' ' .($i > 1 ? __('hours') : __('Hour'));
        }
        $view = new KbView($data);
        return $view;
    }
    
    function importCronPendingAction()
    {
        $this->messages[] = array(sprintf(__('Last cron run at %s.'), getKbAmz()->getOption('LastCronRun', '0000-00-00 00:00:00')), 'alert-success');
        
        if (isset($_POST['clear'])) {
            getKbAmz()->setOption('ProductsToDownload', array());
            $this->messages[] = array(__('Cron Cleared.'), 'alert-success');
        }
        
        $data = array();
        $data['asins'] = getKbAmz()->getOption('ProductsToDownload', array());
        if (empty($data['asins'])) {
            $this->messages[] = array(__('No items in the cron.'), 'alert-warning');
        }
        $view = new KbView($data);
        return $view; 
    }
    
    function actionsAction()
    {
        $data = array();
        if (isset($_POST['clearAllProducts'])) {
            getKbAmz()->clearAllProducts();
            getKbAmz()->setOption('ProductsToDownload', array());
            $this->messages[] = array(__('All products are deleted and cron queue is cleared.'), 'alert-success');
        } else if (isset($_POST['clearAllProductsNoQuantity'])) {
            $productsNoQuantity = getKbAmz()->getProductsWithNoQuantity();
            foreach ($productsNoQuantity as $row) {
                getKbAmz()->clearProduct($row->ID);
            }
            $this->messages[] = array(__('Products with no quantity are deleted.'), 'alert-success');
        } else {
            $this->messages[] = array(__('Full post data delete may be slow. Expect to delete about 1000-2000 products (depending on the images settings) per request.'), 'alert-success');
        }
        $productsNoQuantity = getKbAmz()->getProductsWithNoQuantity();
        $data['productsNoQuantityCount'] = count($productsNoQuantity);
        
        $view = new KbView($data);
        return $view;
    }
    
    public function versionAction()
    {
        return new KbView(array());
    }
    
    public function supportAction()
    {
        $data = array();
        $data['user'] = wp_get_current_user();
        $data['isSent'] = null;
        if (!empty($_POST)) {
            if (!empty($_POST['comment'])
            && !empty($_POST['replyto'])
            && !empty($_POST['from'])) {
                $title = 'WpKbAmzStore '. $_POST['type'] . ' '. (empty($_POST['title']) ? 'No Title' : $_POST['title']);
                $headers = array();
                $headers[] = 'From: '.$_POST['from'].' <'.$_POST['replyto'].'>';
                
                $message = "Support Id: " . getKbAmz()->getStoreId() . "\n";
                $message .= "I want to: " . $_POST['type'] . "\n";
                $message .= $_POST['comment'];
                
                $data['isSent'] = wp_mail(
                    KbAmazonStore::SUPPORT_EMAIL,
                    $title,
                    $message,
                    $headers
                );
                if ($data['isSent']) {
                    $this->messages[] = array(__('Message sent!.'), 'alert-success');
                } else {
                    $this->messages[] = array(
                        sprintf(__('Message is not sent. If this problem continues, you can use %s and your Support Id(%s) to contact us.'),  KbAmazonStore::SUPPORT_EMAIL, getKbAmz()->getStoreId()),
                        'alert-success'
                    );
                }
            } else {
                $this->messages[] = array(__('Please fill the form and try again.'), 'alert-warning');
            }
        } else {
            $this->messages[] = array(
                sprintf(__('You can contact us at <b>%s</b> using your Support Id(%s).'), KbAmazonStore::SUPPORT_EMAIL, getKbAmz()->getStoreId()),
                'alert-success'
            );
        }
        
        
        return new KbView($data);
    }
    
    public function premiumAction()
    {

        $data = array();
        $data['isAjax'] = isset($_POST['action']);
        if ($data['isAjax']) {
            $api = new KbAmzApi(getKbAmz()->getStoreId());
            $result = $api->getProductsListHtml();
            $data['premium'] = isset($result->content) ? $result->content : '';
            $view =  new KbView($data, $this->getTemplatePath('premium'));
            echo $view;
            exit;
        }
        $data['postData'] = json_encode(array('action' => 'premiumAction'));
        return new KbView($data);
    }
    
    public function infoAction()
    {
        $data = array();
        
        return new KbView($data);
    }
    
    public function kbAmzSetOption()
    {
        if (!empty($_POST) && isset($_POST['option'])) {
            getKbAmz()->setOption(
                $_POST['option'],
                (isset($_POST['option-value']) ? $_POST['option-value'] : true)
            );
        }
    }
    
    public function createStorePageAction()
    {
        $view = new KbView(array());
        
        if (!getKbAmz()->getProductsCount()) {
            $this->messages[] = array(
                'Add product/s before you can start creating store page. Import at least 10 for best results.',
                'alert-warning'
            );
            $view->setTemplate($this->getTemplatePath('createStorePageError'));
            return $view;
        }
        
        
        return $view;
    }
    
    public function kbAmzNetworkImportProductAction()
    {
        $response = array();
        try {
            $imported = array();
            foreach ($_POST['items'] as $itemData) {
                $item = unserialize(base64_decode($itemData['item']));
                $importer = new KbAmazonImporter;
                $importer->updateProductPrice($item);
                $imported[] = array(
                    'asin' => $itemData['asin']
                );
            }
            
            $response['items']   = $imported;
            $response['success'] = true;
        } catch (Exception $e) {
            $response['success'] = false;
            $response['error']   = $e->getMessage();
            getKbAmz()->addException('Newtwork Product Fetch', $e->getMessage());
            
        }
        echo json_encode($response);
        die;
    }

    
    public function kbAmzNetworkGetProductsToSyncAction()
    {
        $asins      = getKbAmz()->getProductsAsinsToUpdate(50);
        $products   = array();
        foreach ($asins as $asin) {
            $products[$asin] = getKbAmz()->getProductByAsin($asin);
        }
        $sizes = get_intermediate_image_sizes();
        $size = 'thumbnail';
        if (in_array('small', $sizes)) {
            $size = 'small';
        } else if (in_array('medium', $sizes)) {
            $size = 'medium';
        }
        
        $view = new KbView(array());
        $view->setTemplate($this->getTemplatePath('kbAmzNetworkGetProductsToSync'));
        $view->products = $products;
        $view->size     = $size;
        echo $view;
        die;
    }

    public function networkMembersAction()
    {
        
        $api = new KbAmzApi(getKbAmz()->getStoreId());
        $result =  $api->getNetworkListHtml();
        if ($result->error) {
            $data             = array();
            $data['page']     = 'kbAmz';
            $data['kbAction'] = 'network';
            echo '<div role="alert" class="alert alert-danger">Unable to connect to the service server. <a href="?'.  http_build_query($data).'">Please reload the page</a>.</div>';
        } else {
            echo $result->content;
        }
        die;
    }

    public function networkAction()
    {
        $data         = array();
        $user         = wp_get_current_user();
        $data['user'] = wp_get_current_user();
        $data['siteOwnerName'] = $user->data->display_name;
        if (isset($user->data->first_name)
        && !empty($user->data->first_name)
        && isset($user->data->last_name)
        && !empty($user->data->last_name)) {
            $data['siteOwnerName'] = sprintf(
                '%s %s',
                $user->data->first_name,
                $user->data->last_name
            );
        }
        
        $data['siteOwnerEmail']  = $user->data->user_email;
        $data['siteName']        = get_bloginfo('name');
        $data['siteUrl']         = get_bloginfo('url');
        $data['siteInfo']        = get_bloginfo('description');

        if (isset($_POST['submit'])) {
            
            $data  =
            array(
                'siteOwnerName'     => empty($_POST['siteOwnerName'])   ? $data['siteOwnerName']    : $_POST['siteOwnerName'],
                'siteOwnerEmail'    => empty($_POST['siteOwnerEmail'])  ? $data['siteOwnerEmail']   : $_POST['siteOwnerEmail'],
                'siteName'          => empty($_POST['siteName'])        ? $data['siteName']         : $_POST['siteName'],
                'siteUrl'           => empty($_POST['siteUrl'])         ? $data['siteUrl']          : $_POST['siteUrl'],
                'siteInfo'          => substr(empty($_POST['siteInfo'])        ? $data['siteInfo']         : $_POST['siteInfo'], 0, 250),
                'siteActive'        => $_POST['submit'] == 'join',
                'siteHealth'        => getKbAmzStoreHealth()
            );
            
            getKbAmz()->setOption('siteNetwork', $data);
            
            $api = new KbAmzApi(getKbAmz()->getStoreId());
            $result =  $api->setUser($data);
            
            if (isset($result->error) && $result->error) {
                $this->messages[] = array('Unable to join the 2kb Amazon Network at this time. Error: ' .$result->error, 'alert-warning');
            } else {
                if ($_POST['submit'] == 'join') {
                    $this->messages[] = array('You successfully <b>joined</b> 2kb Amazon Network', 'alert-success');
                } else {
                    $this->messages[] = array('You successfully <b>left</b> 2kb Amazon Network', 'alert-danger');
                }
            }
        } else {
            $siteNetwork = getKbAmz()->getOption('siteNetwork');
            if (!empty($siteNetwork)) {
                $data = $siteNetwork;
            }
        }
        
         
        $data['canLeave'] = isset($data['siteActive']) && $data['siteActive'];
        
        $view = new KbView($data);
        return $view;
    }

    public function shortCodeProductAction($atts)
    {
        $post = null;
        if ($atts['asin']) {
            $post = getKbAmz()->getProductByAsin($atts['asin']);
            if (!$post) {
                $_POST['asin'] = $atts['asin'];
                $this->importByAsinAction();
            }
            $post = getKbAmz()->getProductByAsin($atts['asin']);
        } else {
            $post = get_post($atts['postId']);
        }
        
        if (!$post) {
            throw new Exception('Invalid product, check postId or asin');
        }
        $atts['post'] = $post;
        $atts['meta'] = getKbAmz()->getProductMeta($post->ID);
        $view = new KbView($atts);
        $view->setTemplate($this->getTemplatePath('shortCodeProduct'));
        return $view;
    }

    protected function getActions() {
        return array(
            array(
                'action' => 'home',
                'icon' => 'glyphicon-th',
                'label' => __('Dashboard')
            ),
            array(
                'action' => 'premium',
                'icon' => 'glyphicon-plus',
                'label' => __('Premium')
            ),
            array(
                'action' => 'products',
                'icon' => 'glyphicon-usd',
                'label' => __('Products'),
                'pages' => array(
                    array('action' => 'productsAttributes', 'label' => __('Attributes')),
                    array('action' => 'productsExplodeAttributes', 'label' => __('Explore Attributes')),
                    array('action' => 'productsExcludeAttributes', 'label' => __('Exclude Attributes')),
                    array('action' => 'productsShortCodes', 'label' => __('Short Codes')),
                    array('action' => 'productsVisibility', 'label' => __('Visibility')),
                    //array('action' => 'createStorePage', 'label' => __('Create Store Page')),
                )
            ),
            array(
                'action' => 'widgets',
                'icon' => 'glyphicon-list-alt',
                'label' => __('Widgets')
            ),
            array(
                'action' => 'import',
                'icon' => 'glyphicon-import',
                'label' => __('Import'),
                'pages' => array(
                    array('action' => 'importByAsin', 'label' => __('By ASIN')),
                    array('action' => 'importBySearch', 'label' => __('By Search')),
                    array('action' => 'importByUrl', 'label' => __('By Url')),
                    array('action' => 'importCronPending', 'label' => __('Cron Pending'))
                )
            ),
            array(
                'action' => 'settings',
                'icon' => 'glyphicon-wrench',
                'label' => __('Settings'),
                'pages' => array(
                    array('action' => 'actions', 'label' => __('Actions')),
                    array('action' => 'settingsGeneral', 'label' => __('General')),
                    array('action' => 'settingsAmazonApi', 'label' => __('Amazon API')),
                    array('action' => 'settingsErrorLog', 'label' => __('Error Log')),
                )
            ),
            array(
                'action' => 'support',
                'icon' => 'glyphicon-question-sign',
                'label' => __('Support')
            ),
            array(
                'action' => 'info',
                'icon' => 'glyphicon glyphicon-folder-open',
                'label' => __('Docs & FAQ')
            ),
            array(
                'action' => 'version',
                'label' => 'v'.KbAmazonVersion
            ),
            
        );
    }

    protected function getTemplatePath($addup) {
        return KbAmazonStorePluginPath . '/template/admin/' . $addup;
    }

}
