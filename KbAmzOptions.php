<?php
!defined('ABSPATH') and exit;

/**
 * 
 * @staticvar $KbTemplate KbTemplate
 * @return \KbTemplate
 */
function getKbAmzDefaultOptions()
{
   return array(
       'enableSalePrice' => 1,
       'productListImageSize' => 1,
       'replaceThumbnailWithGallery' => 0,
       'downloadImages' => 1,
       'numberImagesToDownload' => 3,
       'canImportFreeItems' => 0,
       'loadSimilarItems' => 3, // NO
       'enableImportInTopCategories' => '',
       'disableImportInTopCategories' => '',
       'isCronEnabled' => 1,
       'maxProductsCount' => 250,
       'productsLimitReached' => false,
       'imageHoverSwitch' => 0,
       'defaultPostStatus' => 'pending'
   );
}
