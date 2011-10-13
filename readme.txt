=== WooCommerce - eCommerce plugin for WordPress ===
Contributors: woothemes
Tags: ecommerce, e-commerce, commerce, woothemes, wordpress ecommerce, store, shop, shopping, cart, checkout, widgets, reports, shipping, tax, paypal, inventory
Requires at least: 3.1
Tested up to: 3.3
Stable tag: 1.1

An e-commerce toolkit that helps you sell anything. Beautifully.

== Description ==

Transform your WordPress website into a thorough-bred online store. Delivering enterprise-level quality & features whilst backed by a name you can trust. Say hello to WooCommerce.

[vimeo http://vimeo.com/29198966]

= STRENGTH & FLEXIBILITY =
Built upon core WordPress functionality for stability, with enough hooks and filters to satisfy the most avid theme developer.

= CUSTOMIZABLE =
Your business is unique, as should your online store. Choose one of our themes or build your own and give it a personal touch using the built in shortcodes and widgets.

= SMART DASHBOARD WIDGETS =
Keep a birds-eye view of incoming sales and reviews, stock levels and general store performance and statistics all from the WordPress dashboard.

= FEATURES =
Seriously, WooCommerce has got more features than you can shake a stick at. But don’t just take out word for it, try it for yourself.

Read more about the features and benefits of WooCommerce at http://www.woothemes.com/woocommerce/
Checkout and contribute to the source on GitHub at http://github.com/woothemes/woocommerce/

== Installation ==

= Minimum Requirements =

* WordPress 3.1+
* PHP version 5.2.4 or greater
* MySQL version 5.0 or greater
* Some payment gateways require fsockopen support (for IPN access)

= Automatic installation =

Automatic installation is the easiest option as WordPress handles the file transfers itself and you don’t even need to leave your web browser. To do an automatic install of WooCommerce log in to your WordPress admin panel, navigate to the Plugins menu and click Add New. 

In the search field type “WooCommerce” and click Search Plugins. Once you’ve found the plugin you can view details about it such as the the point release, rating and description. Most importantly of course, you can install it by simply clicking Install Now. After clicking that link you will be asked if you’re sure you want to install the plugin. Click yes and WordPress will automatically complete the installation. 

= Manual installation =

The manual installation involves downloading the plugin and uploading it to your webserver via your favourite FTP application.

1. Download the plugin file to your computer and unzip it
2. Using an FTP program, or your hosting control panel, upload the unzipped plugin folder to your WordPress installation’s wp-content/plugins/ directory.
3. Activate the plugin from the Plugins menu within the WordPress admin.

== FAQ ==

For further documentation on using WooCommerce, please sign up for free at http://www.woothemes.com/. This will provide access to extensive WooCommerce Codex, documentation and tips.

== Changelog ==

= 1.1 - 12/10/2011 =
* Tested and working with WordPress 3.3 beta-1
* Added a hook for payment complete order status
* Added woocommerce term meta api
* Added ability to upload category thumbnails
* Added option to show sub-categories when browing categories/the shop page
* Zero tax rate fix
* Filters for tax rates
* Fixes to find rate function
* Product dimension fields
* Fixed archives being displayed by woocommerce_show_product_images
* Added 'configure terms' button to attributes page to make it clearer
* Fix for variations when an attribute has been removed
* Fixed some localisation strings
* Hard crop option for images (use regenerate thumbnails after toggling to redo all images)
* Password field type for gateways API
* Front page shop improvements/correct title tags
* Added option for controlling product permalinks
* Shop page title option
* Load admin css only where needed
* Admin JS cleanup
* Removed error message when clicking buttons to view variations/grouped
* Drag and drop term ordering (so variation options can be sorted)
* Pay page after invoicing fix

= 1.0.3 - 06/10/2011 =
* Several minor fixes/tweaks
* Conditionals check for existing function names
* Made image size settings clearer
* Cleaned up coupon code settings/write panel and added a few more hooks
* Fixed 'product ids' setting in coupons
* Fixed notices on shop pages due to WP_QUERY
* Cleaned up discount types and made some helper functions for getting them
* woocommerce_coupon_is_valid hook
* Fixed order tracking completed time
* Sale price affects variable product 'from:' price
* Variation options (frontend) no longer lose your selections when changing an option
* Gallery image field fix
* Image 'insert into' fix
* variable products store min and max variation price, so if they match the 'from' text is not displayed
* Email items list fix
* Reports chart fix
* Fixed category ordering in widgets
* Labels to taxonomies updated
* Query tweak to fix tags/cats
* Order tracking localisation

= 1.0.2 - 02/10/2011 =
* Fix in woocommerce_templates for when a shop is the front-page
* Added esc_html/esc_attribute in review-order.php
* Tweaked localised strings in shortcode-thankyou.php
* Filter added to get_downloadable_products so more advanced rules can be applied. 
* Fixed required fields in edit shipping address
* Fixed login link 'jump' on checkout
* Added Turkish lira currency
* Active menu state fix
* Few minor typos/case changes
* Tweaked install script so it only redirects after activation
* Removing attributes fix
* only style mails of WooCommerce - not all mails. This is to prevent conflicts with other plugins.
* unique sku check only checks products
* More security audit tweaks thanks to Mark Jaquith
* cart totals update when adding cross-sells to cart
* Removed the 'resave permalinks message' due to it being done automatically
* Added support to exclude images from product page

= 1.0.1 - 29/09/2011 = 
* Fixed notices on product page
* Variation formatting uses item_meta when showing order items
* Javascript fixes in admin + correct enqueuing of scripts
* Added en_GB po file
* Used dbDelta for installation of tables for better table upgrades/installs
* Fix for reviews form when fancybox is turned off

= 1.0 - 27/09/2011 = 
* Initial Release. Woo!
