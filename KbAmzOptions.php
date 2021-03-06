<?php
!defined('ABSPATH') and exit;

/**
 * 
 * @staticvar $KbTemplate KbTemplate
 * @return \KbTemplate
 */
function getKbAmzDefaultOptions()
{
   $options = array(
       'enableSalePrice'                    => 1,
       'productListImageSize'               => 1,
       'replaceThumbnailWithGallery'        => 0,
       'downloadImages'                     => 1,
       'numberImagesToDownload'             => 3,
       'canImportFreeItems'                 => 0,
       'loadSimilarItems'                   => 3, // NO
       'enableImportInTopCategories'        => '',
       'disableImportInTopCategories'       => '',
       'isCronEnabled'                      => 1,
       'maxProductsCount'                   => 250,
       'productsLimitReached'               => false,
       'imageHoverSwitch'                   => 0,
       'defaultPostStatus'                  => 'pending',
       'deleteProductOnNoQuantity'          => false,
       'sendStatsData'                      => false,
       'showStatsDataJoinModal'             => true,
       'amazonApiRequestPerSec'             => 1,
       'allowVariants'                      => false,
       'showVariantsInListing'              => true,
       'cacheTtl'                           => 1800,
       'maxVersionsNumberOnImport'          => 5,
       'showProductsInAdminPosts'           => false,
       'defaultProductQuantity'             => 10,
       /**
        * DISMISABLE
        */
       'KbAmzV2VariantsMessageSeen'         => false,
       'AttributesCountMessageSeen'         => false,
       'KbAmzV2RateUs'                      => false,
   );
   
   $filtered = apply_filters('getKbAmzDefaultOptions', $options);
   if (empty($filtered)) {
       $filtered = $options;
   }
   return $filtered;
}
