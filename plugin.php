<?php
/**
 * Plugin Name: 2kb Amazon Affiliates Store
 * Plugin URI: http://www.2kblater.com/?p=8318
 * Description: Amazon Affiliate Store Plugin With Cart, Checkout, Custom Themes. Easy to manage and setup. Sell wide range of physical and digital products imported from Amazon Affiliate API using 90 days cookie reference. This plugin is released with GPL2 license.
 * Version: 1.0.7
 * Author: 2kblater.com
 * Author URI: http://www.2kblater.com
 * License: GPL2
 */

!defined('ABSPATH') and exit;

if (!session_id()) {
  session_start();
}

define('KbAmazonVersion', '1.0.7');
define('KbAmazonVersionNumber', 107);
define('KbAmazonStoreFolderName',  pathinfo(dirname(__FILE__), PATHINFO_FILENAME));
define('KbAmazonStorePluginPath',  dirname(__FILE__) . '/');

require_once KbAmazonStorePluginPath . 'store_functions.php';
require_once KbAmazonStorePluginPath . 'KbAmazonImporter.php';
require_once KbAmazonStorePluginPath . 'KbAmzOptions.php';
require_once KbAmazonStorePluginPath . 'KbAmazonStore.php';
require_once KbAmazonStorePluginPath . 'KbAmazonController.php';
require_once KbAmazonStorePluginPath . 'KbTemplate.php';
require_once KbAmazonStorePluginPath . 'lib/KbAmazonApi.php';
require_once KbAmazonStorePluginPath . 'lib/KbAmazonItem.php';
require_once KbAmazonStorePluginPath . 'lib/KbAmazonItems.php';
require_once KbAmazonStorePluginPath . 'lib/KbAmazonImage.php';
require_once KbAmazonStorePluginPath . 'lib/KbAmazonImages.php';
require_once KbAmazonStorePluginPath . 'lib/KbView.php';
require_once KbAmazonStorePluginPath . 'lib/kbAmzApi.php';
// @IMPORTED only for the files they use it.
// require_once KbAmazonStorePluginPath . 'lib/phpQuery-onefile.php';
require_once KbAmazonStorePluginPath . 'store_widgets.php';
require_once KbAmazonStorePluginPath . 'store_shortcodes.php';
require_once KbAmazonStorePluginPath . 'store_init.php';
require_once KbAmazonStorePluginPath . 'query.php';
