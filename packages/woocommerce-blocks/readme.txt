=== WooCommerce Blocks ===
Contributors: automattic, claudiulodro, tiagonoronha, jameskoster, ryelle, levinmedia, aljullu, mikejolley, nerrad, joshuawold, assassinateur, haszari
Tags: gutenberg, woocommerce, woo commerce, products, blocks, woocommerce blocks
Requires at least: 5.0
Tested up to: 5.3
Requires PHP: 5.6
Stable tag: 2.5.14
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

**All Products**
Display all products from your store as a grid with pagination and sorting options. Requires WordPress 5.3.

**Filter Products by Price**
Display a slider to filter products in your store by price. Works in combination with the _All Products_ block. Requires WordPress 5.3.

**Filter Products by Attribute**
Display a list of filters based on a chosen product attribute. Works in combination with the _All Products_ block. Requires WordPress 5.3.

**Active Product Filters**
Display a list of active product filters. Works in combination with the _Filter Products by Price_ and _Filter Products by Attribute_ block. Requires WordPress 5.3.

We've also improved the category selection filter. If you select two or more categories, you can now chose to show products that include ANY or ALL selected categories.

== Getting Started ==

= Minimum Requirements =

* WordPress 5.0
* WooCommerce 3.7 or greater
* PHP version 5.6 or greater (PHP 7.2 or greater is recommended)
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

= 2.5.14 - 2020-03-03 =
- Added screen reader text to product counts in the product category list block #1828
- Added screenreader alternative text to the sale badge. #1826
- Product Search block is now compatible with WordPress 5.4 and the last versions of Gutenberg. #1841
- Security: Improved escaping of attributes on blocks. #1854

= 2.5.13 - 2020-02-18 =
- Respect hidden products in All Products block. #1753

= 2.5.12 - 2020-02-05 =
- Fix ratings appearing as text in the editor instead. #1650
- Fix error with the All Products block and Internet Explorer 11 when adding products to the cart. #1642
- bug: Check for instance of WooCommerce and WP_Error before initializing session and cart in `rest_authentication_errors` callback. #1698
- Fix display of price slider when using RTL languages. #1651
- Renamed the "all products" align option so it's clear the final element gets alignment, not just buttons. #1659

= 2.5.11 - 2020-01-20 =
- bug: Fix a javascript error when editing All Products inner blocks "Link to Product Page" option #1593
- bug: Fix an issue in All Products when ordering by newness was reversed #1598
- bug: Fix a javascript error in editor when user re-selects same attribute in Filter Products by Attribute block #1596
- bug: Fix a render issue for product attribute values with ampersand (&) or other special characters #1608
- bug: Fix bug in Safari and other Webkit browsers that was causing the All Products block to show 0 results when resetting the sort value. #1611

= 2.5.10 - 2020-01-09 =
- All Products block: fix wrong price format for variable products with certain currency settings. #1518

= 2.5.9 - 2020-01-07 =
- Fix issue in All Products block that was causing Variable products price to exclude taxes in some cases. #1503

= 2.5.8 - 2020-01-02 =
- Fixed a bug where Filter by Price didn't show up. #1450
- Price filter now allows entering any number in the input fields, even if it's out of constrains. #1457
- Make price slider accurately represent the selected price #1453

= 2.5.7 - 2019-12-20 =
- Add translation comments and use correct functions #1412, #1415
- bug: Fix Price Filter constraints when price is decimal #1419

= 2.5.6 - 2019-12-17 =
- Fix broken build resulting in blocks not working.

= 2.5.5 - 2019-12-17 =
- bug: Fix broken atomic blocks in the All Products Block #1402
- bug: Only allow one instance of the All Products block per page/post. #1383
- bug: All Products Block: Fix default sort order changes not updating block in editor. #1385
- bug: Normalize set minPrice and maxPrice values by step #1379
- bug: Fix messaging when there are no attributes #1382
- Price Filter: fix NaN values shown in some occasions while loading . #1386
- bug: Fix incorrect property name for price format #1397
- Remove double colon on active filter block price label. #1399
- Fix: Attribute filters were not updating based on changes in the Price filter when query type was set to OR. #1390

= 2.5.4 - 2019-12-11 =
- bug: Fix increase in some bundle sizes #1363

= 2.5.3 - 2019-12-09 =
- Prevent Filter Products by Attribute block hiding non-matching options when Querty Type is set to OR. #1339
- bug: Fix price slider layout in narrow columns #1231

= 2.5.2 - 2019-12-02 =
- Fixed a PHP Notice in Featured Category Block when the category is invalid. #1291 👏 @strategio
- Filter Products by Attribute block now uses the attribute label instead of the slug to set the default title. #1271
- Fix Filter Products by Price slider being reset to 0-0 when filters were cleared from the Active Filters block. #1278
- Don't enqueue wcSettings unless the route requires it. #1292
- Add `getAdminLink()` utility method. #1244

= 2.5.1 - 2019-11-26 =
- Fix Products by Tag, Products by Attribute and Handpicked products blocks showing an invalid attributes error. #1254
- Fix the price slider updating instantly even when filter button was enabled. #1228
- Fix price slider layout in narrow columns. #1231
- Honor CSS classes in the editor for blocks added in 2.5. #1227
- Fix variable products price format in All Products block. #1210
- Allow the feature plugin to use WooCommerce Core translated strings. #1242
- Reduce number of queries ran by multiple filter blocks with All Products block. #1233
- Fix heading level setting for the All Products Title Block. #1230
- Fix editor styles (background color) for titles of "Filter by…" blocks. #1256
- Fix bug with cart not updating. #1258
- Fix issue in the Filter by Attribute selector that was preventing to reselect the currently selected attribute. #1264

= 2.5.0 - 2019-11-19 =

- Feature: Introduce an All Products block, a new block listing products using client side rendering. Requires WordPress 5.3.
- Feature: Introduce a Filter Products by Price block. Allow customers to filter the All Products block by price. Requires WordPress 5.3.
- Feature: Introduce a Filter Products by Attribute block which works alongside the new "All products" block. Requires WordPress 5.3.
- Feature: Introduce an Active Filters block that lists all currently used filters. Requires WordPress 5.3.
- Show a friendly error message in the frontend if blocks throw a JS error.
- Show a message in the editor if no products are found rather than show nothing.
- Show previews for all included blocks in the block inserter. Requires WordPress 5.3.
- Products on Sale, Products Tag and Product Search blocks have new icons.
- Officialy deprecate NPM package `@woocommerce/block-library`.
- Use Server Side Rendering for Product Category List block to remove the need to pass large amounts of data around when not needed.
- RTL fixes to several blocks.
- All block icons are displayed gray in the editor shortcuts inserter.
- Make it easier for themes to style the Product Categories List block: new class names allow writing simpler selectors and it's now possible to remove the parentheses around the count number.

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

= 2.3.1 - 2019-08-27 =

- Fix: Fix deprecation notices with PHP 7.4.
- Fix: Removed unused screen-reader-text css styles for buttons which caused some theme conflicts.
- Fix: Left align stars to fix alignment in Storefront.
- Fix: Best-sellers block query results #917
- Fix: Fix duplicated translatable string #843

= 2.3.0 - 2019-08-12 =

- Feature: Added a new Featured Category Block; feature a category and show a link to it's archive.
- Feature: Added a new Products by Tag(s) block.
- Feature: Allow individual variations to be selected in the Featured Product block.
- Feature: Added a button alignment option to product grid blocks to align buttons horizontally across the row.
- Feature: Added a cancel button to the product category block editor to discard unsaved changes.
- Enhancement: Change the toggle for list type in Product Category List block to a button toggle component for clarity.
- Build: Updated build process and plugin structure to follow modern best practices. Minimum PHP version bumped to 5.6.
- Fix: Correctly hide products from grids when visibility is hidden.
- Fix: Fix Featured Category block using radio buttons instead of checkboxes.
- Fix: Use externals for frontend dependencies so they are shared between extensions and blocks. That saves 2.57MB on page weight.
- Fix: Load frontend scripts dynamically only when the page contains a block that requires them.
- Fix: Reduce dependencies of JavaScript powered frontend blocks.
- Fix: Disable HTML editing on dynamic blocks which have no content.
- Fix: Hide background opacity control in Featured Product settings if there is no background image.
- Fix: Reduce CSS specificity to make styling easier.
- Fix: Fix author access to API for handpicked products block.

= 2.2.1 - 2019-07-04 =

- Fix: Allow custom CSS classes on grid blocks.
- Fix: Allow custom CSS classes on featured product block.
- Fix: Allow custom CSS classes on product categories list.

= 2.2.0 - 2019-06-26 =

- Feature: Add Product Categories List navigation block for showing a list of categories on your site.
- Enhancement: All grid blocks are now rendered directly by the blocks code, not using the shortcode.
- Enhancement: Brand the WooCommerce Blocks for better discoverability in the block inserter.
- Build: Update build process to dynamically generate required WordPress dependencies.
- Build: Update packages.

= 2.1.0 - 2019-05-14 =

- Feature: Add focal point picker to the Featured Product block, so you can adjust the background image position (only available on WP 5.2+ or with Gutenberg plugin).
- Fix: Improved fetching products from API, so searching for products in Featured Product & Hand-picked Products is faster for stores with over 200 products.
- Fix: It might be possible to request over 100 products for the editor preview, but this would cause an API error - we now limit the preview request to 100 products.
- Build: Update build script to show visual progress indicator.
- Build: Update packages.

= 2.0.1 - 2019-04-22 =

- Fix: Fix warnings about blocks already being registered.
- Fix: Fix a conflict with WooCommerce 3.6 and WooCommerce Blocks 1.4 (this change only applies to the version of blocks bundled with WooCommerce core).

= 2.0.0 - 2019-04-18 =

- **BREAKING:** Requires WordPress 5.0+, WooCommerce 3.6+
- **BREAKING:** Remove the legacy block entirely
- **BREAKING:** Remove the `wc-pb/v3/*` endpoints in favor of new core `wc-blocks/v1/*` endpoints
- Feature: Add content visibility settings to show/hide title, price, rating, button
- Feature: Add transforms between basic product grid blocks
- Fix: Add product rating display to preview, to better match front end
- Fix: Product titles render HTML correctly in preview
- Fix: Icons are now aligned correctly in placeholders
- Fix: Grid block preview column width now matches the front-end
- Fix: Webpack now builds using a custom jsonp callback, fixing possible collisions with other projects
- API: Change namespace, endpoints now accessed at `/wc/blocks/*`
- API: Add `catalog_visibility` parameter for fetching products
- API: Update structure of attribute term endpoint to return `attribute.slug`, `attribute.name` etc
- API: Update parameters to use full names, `category_operator`, `attribute_operator`
- Components: Move SearchListControl to `@woocommerce/components` library
- Components: Added new control component GridContentControl to manage content visibility
- Build: Reorganize CSS into one file for editor preview, and one file for front-end styles
- Build: Move registration code to a new class
- Build: Update packages

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
