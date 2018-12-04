=== WooCommerce Blocks ===
Contributors: automattic, claudiulodro, tiagonoronha, jameskoster, ryelle, levinmedia
Tags: gutenberg, woocommerce, woo commerce, products
Requires at least: 4.9
Tested up to: 5.0
Requires PHP: 5.2
Stable tag: 1.2.0
License: GPLv3
License URI: https://www.gnu.org/licenses/gpl-3.0.html

== Description ==

WooCommerce Blocks are the easiest, most flexible way to display your products on posts and pages! Using the original "Products Block", your displayed products can be filtered by category, sale status, or a variety of other fields. You can even make a custom list of hand-picked products to display.

New for 1.2.0: We've added a stand-alone Product Category block to simplify the experience and improve the category search and selection UI. Be on the lookout for additional stand-alone blocks in future releases. 

== Getting Started ==

= Minimum Requirements =

* WordPress 4.9.x
* Gutenberg plugin 4.6 or greater
* WooCommerce 3.3.1 or greater
* PHP version 5.2.4 or greater (PHP 7.2 or greater is recommended)
* MySQL version 5.0 or greater (MySQL 5.6 or greater is recommended)

OR

* WordPress 5.0.x
* WooCommerce 3.5.1 or greater
* PHP version 5.2.4 or greater (PHP 7.2 or greater is recommended)
* MySQL version 5.0 or greater (MySQL 5.6 or greater is recommended)

Visit the [WooCommerce server requirements documentation](https://docs.woocommerce.com/document/server-requirements/) for a detailed list of server requirements.

= Automatic installation =

Automatic installation is the easiest option as WordPress handles the file transfers itself and you don’t need to leave your web browser. To do an automatic install of this plugin, log in to your WordPress dashboard, navigate to the Plugins menu and click Add New.

In the search field type “WooCommerce Gutenberg Products Block” and click Search Plugins. Once you’ve found this plugin you can view details about it such as the point release, rating and description. Most importantly of course, you can install it by simply clicking “Install Now”.

= Manual installation =

The manual installation method involves downloading the plugin and uploading it to your webserver via your favourite FTP application. The WordPress codex contains [instructions on how to do this here](https://codex.wordpress.org/Managing_Plugins#Manual_Plugin_Installation).

= Sample data =

WooCommerce comes with some sample data you can use to populate the products and get started building Products blocks quickly and easily. You can use the core [CSV importer](https://docs.woocommerce.com/document/product-csv-importer-exporter/) or our [CSV Import Suite plugin](https://woocommerce.com/products/product-csv-import-suite/) to import sample_products.csv.

= Where can I report bugs or contribute to the project? =

Bugs should be reported in the [WooCommerce Gutenberg Products Block GitHub repository](https://github.com/woocommerce/woocommerce-gutenberg-products-block/).

= This is awesome! Can I contribute? =

Yes you can! Join in on our [GitHub repository](https://github.com/woocommerce/woocommerce-gutenberg-products-block/) :)

== Screenshots ==

1. The initial Products block.
2. Selecting hand-picked products for the Products block.
3. The completed Products block when inserted into a post.
4. New: Stand-alone Product Category block

== Changelog ==

== 1.2.0 - 2018-12-04 =
* Feature - Stand-alone product category block with improved category selection interface.
* Fix - All users who can edit posts can now use these blocks thanks to a new set of API endpoints allowing view access to products, product categories, and product attributes.
* Fix - Compatibility with WP 5.0, fixed error “Cannot read property Toolbar of undefined”.
* Fix - Only published products are shown in previews.
* Enhancement - Translations should now load into the block (for WP 5.0+).
* Enhancement - Modernized build process and developer tools, and added tests for faster future development.

== 1.1.2 - 2018-09-07 =
* Fix - Refactor to remove withAPIData usage, as the class was removed in Gutenberg 3.7. 

== 1.1.1 - 2018-08-22 =
* Fix - Make Newness ordering order correctly on frontend.
* Fix - Don't cause fatal errors if WooCommerce is not active.

= 1.1.0 - 2018-06-06 =
* Feature - Add "Best Selling" and "Top Rated" product scopes.
* Fix - Only enqueue scripts and styles in the site backend.
* Fix - Remove focus checks deprecated in latest Gutenberg version.
* Fix - Added keys to some elements to prevent React warnings.
* Enhancement - Added custom API endpoint for more accurate block previews with orderby.
* Performance - Refactored the way the attribute select works to prevent many concurrent API requests on sites with many attributes.

= 1.0.0 - 2018-04-24 =
* Initial implementation of the Gutenberg Products block.

== Upgrade Notice ==

= 1.0 =
