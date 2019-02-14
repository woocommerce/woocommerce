=== WooCommerce Blocks ===
Contributors: automattic, claudiulodro, tiagonoronha, jameskoster, ryelle, levinmedia
Tags: gutenberg, woocommerce, woo commerce, products
Requires at least: 4.9
Tested up to: 5.0
Requires PHP: 5.2
Stable tag: 1.4.0
License: GPLv3
License URI: https://www.gnu.org/licenses/gpl-3.0.html

== Description ==

WooCommerce Blocks are the easiest, most flexible way to display your products on posts and pages!

**NEW: Products by Attribute Block**
Display a grid of products from your selected attributes.

**Featured Product Block**
Select and display a single product in a new, high impact fashion. Control text alignment, hide or show the price and description, add a color overlay, change the button call to action, and override the product photo. 

**Hand-Picked products Block**
Display a grid of hand picked products. Products can be ordered in various ways.

**Best Selling Products Block**
Display a grid of your best selling products, filterable by category.

**Top Rated Products Block**
Display a grid of your top rated products, filterable by category.

**Newest Products Block**
Display a grid of your newest products, filterable by category.

**On Sale Products Block**
Display a grid of on sale products, filterable by category.

**Products by Category Block**
Display a grid of products from your selected category, or categories. Products can be ordered in various ways.

We've also improved the category selection filter. If you select two or more categories, you can now chose to show products that include ANY or ALL selected categories. 

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

Release and roadmap notes available on the [WooCommerce Developers Blog](https://woocommerce.wordpress.com/2019/01/15/woocommerce-blocks-1-3-0-release-notes/)

== Screenshots ==

1. The Featured Product Block
2. Selecting a product for the Featured Product Block
3. Selecting Products for the Hand-Picked Products Block
4. Selecting categories in the Products By Category block
5. WooCommerce Product Blocks in the block inserter menu

== Changelog ==

= 1.4.0 - 2019-02-12 =

- Feature: Added new block: "Products by Attribute"
- Enhancement: Added the ability to resize the Featured Product block (a default and minimum height can be set by your theme)
- Enhancement: Updated button on Featured Product block to match core button block behavior
- Components: Added new control component `GridLayoutControl` to manage row/column layout values
- Components: Updated `ProductAttributeControl` to list only attribute types, then once selected, list terms in that attribute
- UX: Remove ability to change selected product in Featured Product block, to prevent "sticky" product links.
- Fix: A product without an image will now use the placeholder image in grid-layout block previews
- Fix: Previously, there was a PHP notice when a Featured Product has no background image
- Fix: There is now an enforced limit on column and row counts (which can be set by your theme)
- API: Added `attr_operator` support to products endpoint to compare product attribute terms
- Build: Update packages
- Build: Remove unnecessary internationalization build step

= 1.3.1 - 2019-01-17 =

- Fix: A CSS conflict was causing the core columns style to reset, this has been fixed and columns will display as expected now.
- Fix: A version conflict with a JS package was causing the blocks to be broken in non-English locales. The package was updated.
- Fix: Translations were not being loaded correctly for the JS files. We now bundle the Danish, Spanish, and French translations so that these can be used.

= 1.3.0 - 2019-01-15 =

- Feature: Added new blocks: "Featured Product", "Hand-picked Products", "Best Selling Products", "Newest Products", "On Sale Products", "Top Rated Products"
- Enhancement: Create new "WooCommerce" block category, all blocks are found there now
- Enhancement: Added a control to "Products by Category" block to control whether products need to match any selected categories or all selected categories
- Fix: A "Products by Category" block with no category selected will no longer show all products
- Legacy block: Remove legacy "Products" block from being shown in the block inserter (still loading the block for an existing uses)
- Legacy block: Fix an issue with imageless products in the legacy "Products" block.
- Components: Add new Control components `ProductControl`, `ProductsControl`, `ProductOrderbyControl`
- Components: Update `SearchListControl` to allow selecting a single item
- API: Add `cat_operator` support to products endpoint
- API: Add product `description` & `short_description` to each product
- API: Add attribute group names to each attribute
- Build: Update packages
- Build: Add cssnano to minify CSS
- Build: Split out node_modules code into separate vendors files

= 1.2.0 - 2018-12-04 =

* Feature - Stand-alone product category block with improved category selection interface.
* Fix - All users who can edit posts can now use these blocks thanks to a new set of API endpoints allowing view access to products, product categories, and product attributes.
* Fix - Compatibility with WP 5.0, fixed error “Cannot read property Toolbar of undefined”.
* Fix - Only published products are shown in previews.
* Enhancement - Translations should now load into the block (for WP 5.0+).
* Enhancement - Modernized build process and developer tools, and added tests for faster future development.

= 1.1.2 - 2018-09-07 =

* Fix - Refactor to remove withAPIData usage, as the class was removed in Gutenberg 3.7. 

= 1.1.1 - 2018-08-22 =

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
