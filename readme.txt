=== Plugin Name ===
Tags: amazon, affiliate, associate, online store, selling products
Requires at least: 4.0
Tested up to: 4.1
Stable tag: 1.2.0
License: GPLv2 or later
License URI: http://www.gnu.org/licenses/gpl-2.0.html

Amazon Affiliate Store Plugin With Variants, Cart, Checkout, Custom Themes. Easy to manage and setup. Sell wide range of physical and digital products.

== Description ==

Features

*   Product Variants and Versions
*   Sell wide range of physical and digital products in Worldwide, Germany, United Kingdom, Canada, Japan, Italy, China, France, Spain, India.
*   Amazon Affiliate API integration with 90 days cookie duration
*   Product as post
*   Product attributes smart management system
*   Import by ASIN, Category Search and Amazon.com link
*   Supports of similar products
*   Supports of most wordpress themes
*   Filter Widgets - Categories, Attribute slider, Attributes count with link (very cool stuff)
*   Completely removable

== Installation ==

This section describes how to install the plugin and get it working.

1. Upload `2kb-amazon-affiliates-store` folder to the `/wp-content/plugins/` directory
1. Activate 2kb Amazon Affiliates Store plugin through the 'Plugins' menu in WordPress
1. Open 2kb Amazon Store from admin menu or go to link YOUR_BLOG.COM/wp-admin/admin.php?page=kbAmz and follow the instructions.

== Frequently Asked Questions ==

== Screenshots ==

== Changelog ==
= 2.0.0 BETA 2=
New Import loaded, custom categories are now working.
Added option to limit Product Versions import on product create. Some books have 100+ versions and it could take much time to import. Now the cron job will finish the import.
= 2.0.0 BETA 1=
php < v5.3 syntax error fix
= 2.0.0 BETA =
Product Variants Added
Product Versions Added
Import Products Improvement
Optimizations
Some bug fixes
= 1.2.0 =
Usability changes:
Delay between requests option added to reduce Amazon API warnings
Last cron run label on dashboard added

Programming changes:
Many hooks added
Default no item image accepts attributes
Similar Products warning fixed
= 1.1.8 =
Bug fix - Mobile layout for listing
= 1.1.6, 1.1.7 =
Bugs fixing
= 1.1.5 =
New Option - delete product(post) on quantity = 0
New Action - Delete All Products That Have post_status = pending And KbAmzOfferSummary.TotalNew <= 0'
Added Default Product Image in the listings.
Added Popover on checkout button: if product is not sellable with the affiliate program, direct product link will be provided.
Added Plugin Experience Program (Optional)
= 1.1.4 =
Fixed bug when listing products with no quantity left. kb_amz_list_products now lists only products (posts) with post_status = publish be default, which can be changed.
= 1.1.3 =
Fixed bug for creating multiple checkout pages.
Fixed bug with listing price option. Now showing listing price and discounted price.
Admin widgets css update.
Documentation added.
= 1.1.2 =
Listing price add when 'Show the original price of the product.' options is enabled.
Disabled store widgets filters on product post page.
= 1.1.1 =
Amazon Iframe Reviews added. You can test and provide feedback to complete this functionality.
Fixed bug when using product images directly from Amazon and not displaying outside the product page. Thanks to alamandeh for reporting it.
Fixed bug when pagination is disabled for one listing on multiple product listings.
Fixed admin import search form same parameters after submit bug.
Fixed bug for custom themes when having thumbnail size (class) on the listing page.
= 1.1.0 =
Import timeout increased from default 30sec. to 90 sec.
Added pagination on the search page.
= 1.0.9 =
kb_amz_list_products shortcode accept short code parameters with php code. Ex. [kb_amz_list_products attribute_value="<? date('Y-m-d', time() - 3600); ?>"].
= 1.0.8 =
India is added to the import categories list thanks to Mr.Parmar.
= 1.0.7 =
Short Codes bug fixed - all shortcodes use '_' instead of '-'.
Added option for featured content in [kb_amz_list_products featured="Yes" featured_content_length="150"]. Content is loaded from the_excerpt or the product description.
Bug fixed when using [kb_amz_list_products] in product shortcode content. (product is excluded from the query).
= 1.0.6 =
Category accept functions in [kb_amz_list_products] and items_per_row added.
Dashboard published products  message added.
= 1.0.5 =
Category fix in [kb_amz_list_products]
= 1.0.4 =
Some bugs got fixed. Thank you for your support.
= 1.0.3 =
New Option = Download Images. This option allows you to store only the link of product`s images. This will save you space and time to import.
Maintenance fixes
lib/KbAmazonImage
lib/KbAmazonImages
= 1.0.2 =
Dashboard info update - products counts, products to download, products to sync, time to sync.
= 1.0.1 =
Day 1 update
Products -> Short Codes, restore default content shortcodes. Option to replace content shortcode with the product content insuring better SEO and editability.
= 1.0.0 =
Initial realise version.
