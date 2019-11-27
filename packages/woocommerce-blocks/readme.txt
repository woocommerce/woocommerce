=== WooCommerce Blocks ===
Contributors: automattic, claudiulodro, tiagonoronha, jameskoster, ryelle, levinmedia, aljullu, mikejolley, nerrad, joshuawold
Tags: gutenberg, woocommerce, woo commerce, products, blocks, woocommerce blocks
Requires at least: 5.0
Tested up to: 5.3
Requires PHP: 5.6
Stable tag: 2.4.5
License: GPLv3
License URI: https://www.gnu.org/licenses/gpl-3.0.html

== Description ==

WooCommerce Blocks are the easiest, most flexible way to display your products on posts and pages!

**Featured Product Block**
Select and display a single product in a new, high impact fashion. Control text alignment, hide or show the price and description, add a color overlay, change the button call to action, and override the product photo.

**Featured Category Block**
Visually highlight a product category to increase traffic to that specific section on your shop.

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

**Products by Tag Block**
Show a grid of products based on a specific tag that allows you to highlight products based on finer classification options.

**Products by Attribute Block**
Display a grid of products from your selected attributes.

**Product Categories List Block**
Display all your product categories as a list or dropdown and help shoppers to find a specific category.

**Reviews by Product**
Display reviews of a specific product to build trust in your product and brand.

**Reviews by Category**
Highlight reviews from specific categories and help merchants making an informed purchasing decision.

**All Reviews**
Show a list of all product reviews on a landing page, blog post or any other page in your site.

**Product Search**
Help shoppers find your products by placing a search box in specific locations.

We've also improved the category selection filter. If you select two or more categories, you can now chose to show products that include ANY or ALL selected categories.

== Getting Started ==

= Minimum Requirements =

* WordPress 5.0
* WooCommerce 3.6 or greater
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
= 2.4.5 - 2019-11-2 =

- Fix bug with html entities in product name being shown in review blocks.

= 2.4.4 - 2019-10-15 =

- Fix product search widget due to missing home url on the form action.
- Fix fatal on WP_Error due to missing root namespace for class invocation.

= 2.4.3 - 2019-10-14 =

-  Refactor permission checks for authors.

= 2.4.2 - 2019-09-23 =

- Fix product grids overflowing in some themes
- Fix regression that prevented selecting product variations in the Featured Product block.

= 2.4.1 - 2019-08-30 =

- Fix conflict with WooCommerce Admin.

= 2.4.0 - 2019-08-29 =
- Feature: A new block named 'All Reviews' was added in order to display a list of reviews from all products and categories of your store. #902
- Feature: Added Reviews by Product block.
- Feature: Added Reviews by Category block.
- Feature: Added a new product search block to insert a product search field on a page.
- Enhancement: Add error handling for API requests to the featured product block.
- Enhancement: Allow hidden products in handpicked products block.
- Fix: Prevented block settings being output on every route.  Now they are only needed when the route has blocks requiring them.
- Dev: Introduced higher order components, global data handlers, and refactored some blocks.
- Dev: Created new HOCs for retrieving data: `withProduct`, `withComponentId`, `withCategory`.
- Dev: Export block settings to an external global `wc.blockSettings` that can be reliably used by extensions by enqueuing their script with the `wc-block-settings` as the handle. #903
- Dev: Added new generic base components: `<OrderSelect />` and `<Label />` so they can be shared between different blocks. #905
