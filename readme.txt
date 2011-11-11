=== WooCommerce - eCommerce plugin for WordPress ===
Contributors: woothemes
Tags: ecommerce, e-commerce, commerce, woothemes, wordpress ecommerce, store, shop, shopping, cart, checkout, widgets, reports, shipping, tax, paypal, inventory
Donate link: https://www.paypal.com/cgi-bin/webscr?cmd=_xclick&business=paypal@woothemes.com&item_name=Donation+for+WooCommerce
Requires at least: 3.1
Tested up to: 3.3
Stable tag: 1.2.1

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

* WordPress 3.1 or greater
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

== Frequently Asked Questions == 

= Where can I find WooCommerce documentation and user guides =

For further documentation on using WooCommerce, please sign up for free at http://www.woothemes.com/. This will provide access to extensive WooCommerce Codex, documentation and tips. 

The codex for WooCommerce can be found here: http://www.woothemes.com/woocommerce-codex/

Clients and beginners will appreciate the user guide here: http://www.woothemes.com/woocommerce-codex/woocommerce-user-guide/

= Where can I request new features and extensions? =

You can vote on and request new features and extensions in our WooIdeas board - http://ideas.woothemes.com/forums/133476-woocommerce

= Where can I report bugs or contribute to the project? =

Bugs can be reported either in our support forum or preferably at the WooCommerce GitHub repository (https://github.com/woothemes/woocommerce/issues)

= WooCommerce is awesome! Can I contribute? =

Yes you can! Join in on our GitHub repository :) https://github.com/woothemes/woocommerce/

== Screenshots ==

1. The slick WooCommerce settings panel
2. WooCommerce products admin
3. WooCommerce sales reports

== Changelog ==

= 1.2.2 =
* Tweaked 'pay' emails
* Check at top of email templates to make sure they are not accessed directly

= 1.2.1 - 10/11/2011 =
* Reworked downloadable and virtual products - now variations can be downloadable/virtual too making it more flexible
* My account/login widget
* Added shortcode insertion button to the post editor
* Shortcode for products by category (slug)
* Option to enable/disable ajax add to cart buttons
* Widget for showing onsale products
* Signup/login can be turned off for checkout
* Paypal remote post now has 'sslverify' => false to prevent errors with CURL
* Minor admin settings tidyup
* Dutch translation has been updated
* Cart is now more robust and supports custom data being stored, such as addons
* Fix for 0 quantity
* add_to_cart shortcode
* Improved order search
* Option to unforce SSL checkout
* Support for X-Accel-Redirect / X-Sendfile for downloads
* Customer new account email when signing up from the checkout
* Attributes can be added to nav bar via filter
* External/Affiliate product type
* Added Spanish translation by lluis masachs
* Support for informal/formal localisations
* Directory changed for uploading file downloads - uploads/woocommerce_files
* Download directory created on install as well as htaccess for denying access
* Formal and informal German translations - thanks to stefahn, jessor, Ramoonus , owcv and deckerweb 
* Hook for checking cart contents during cart/checkout - used for plugins too

= 1.2 - 03/11/2011 =
* Added quick status change buttons (processing/complete) to orders panel
* Ability to preview email templates
* Option to add logout link to my account menu
* Added ability to show meta (custom fields) on order emails - useful for plugins
* Added order details to thankyou page
* Added basic rss feeds for products and product categories
* Added functions which show tax/vat conditionally
* Made use of transients to store average ratings and improve performance
* Added page installer on installation to make it optional (you may want to import your pages)
* BACS and Cheque gateways now contain payment instructions
* Custom field for product total_sales when sold
* Best sellers widget based on new total_sales field
* Ability to exclude product ids
* Option for the recipient of order/stock emails
* Options to define default attribute selections in variations
* Edit category - image fix
* Order Complete email heading fix
* 100% discount when price excludes tax logic fix
* Download urls use site_url instead of home_url so installs in subdirectories are handled correctly
* Fixed variations - Incorrectly used instead $product_custom_fields of $parent_custom_fields
* Adding cart item resets shipping - so free shipping etc is selected when going over a threshold
* Changes to shipping calc - if no rates are found, but the user did not enter a state/postcode, it asks them to do so. 
* Fix for adding sites in multisite
* Dashboard chart now ignores 'pending' orders
* Fixed dashboard report range
* Added hooks to gateway icons
* Added Dutch translation by [Ramoonus](http://www.ramoonus.nl/)

= 1.1.3 - 27/10/2011 =
* Improved Force SSL Setting - now forces https urls for enqueued scripts and styles 
* Updated some localisation strings in email subject lines
* Fixed variation coupons
* Fixed edit address via my account
* Support for localisations in wp-content/languages/woocommerce
* Added ability to change email template colors and from name/address from the settings panel
* Added italian translation by Roberto Lioniello
* Added swedish translation by Stefan Johansson
* Made cart page hide the 'no shipping methods found' message unless the user has calculated shipping.
* Given shop_manager role capabilities of an editor
* Fixed menu order when logged in as a shop manager
* Added SKU column to order data
* Removed output buffering from loop
* Add product % coupons
* Filtering hides subcategories
* Lots of other minor fixes

= 1.1.2 - 23/10/2011 =
* Coupons can be applied to variations (by ID)
* Fixed up/cross-sell removal
* Fixed image (zoom) URL after variation selection
* Fixed category filter in admin
* Fixed billing/shipping address on my account page
* Tax fix for prices excluding tax - issue was in the 1.1.1 update
* Option to hide products when sub-cats are shown, and empty cats are shown
* Renamed $columns global for compatibility with certain themes
* Made variation slugs display as term name
* Added a helper function to get the return url (for gateways) - fixes https return url

= 1.1.1 - 16/10/2011 =
* Products need a base, regardless of category settings - added base to prevent pages breaking
* Fixed hard cropping
* Translation fixes
* Moved discount above shipping calc
* Fixed enter key on product edit page
* Typos
* Made custom attributes display 'nicely' in cart widgets, instead of santized

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

== Upgrade Notice ==

= 1.2.1 =
This version has improved product types - ensure you de/re-activate the theme to ensure existing products get converted correctly.

= 1.2 =
New features, bug fixes and improvements to keep WooCommerce running smoothly.