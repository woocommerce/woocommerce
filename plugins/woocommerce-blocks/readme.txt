=== WooCommerce Blocks ===
Contributors: automattic, woocommerce, claudiulodro, tiagonoronha, jameskoster, ryelle, levinmedia, aljullu, mikejolley, nerrad, joshuawold, assassinateur, haszari, mppfeiffer, nielslange, opr18, ralucastn, tjcafferkey, danielwrobert, patriciahillebrandt, albarin, dinhtungdu, royho, imanish003, karolmanijak, sunyatasattva, alexandrelara, gigitux, danieldudzic, samueljseay, alexflorisca, opr18, tarunvijwani, pauloarromba, saadtarhi
Tags: gutenberg, woocommerce, woo commerce, products, blocks, woocommerce blocks
Requires at least: 6.4
Tested up to: 6.4
Requires PHP: 7.4
Stable tag: 11.6.2
License: GPLv3
License URI: https://www.gnu.org/licenses/gpl-3.0.html

== Description ==

WooCommerce Blocks are the easiest, most flexible way to display your products on posts and pages!

For more information on what blocks are available, and how to use them, check out <a href="https://woocommerce.com/document/woocommerce-blocks/
">the official documentation</a>.

**Note: Feature plugin for WooCommerce + Gutenberg. This plugin serves as a space to iterate and explore new Blocks and updates to existing blocks for WooCommerce, and how WooCommerce might work with the block editor.**

Use this plugin if you want access to the bleeding edge of available blocks for WooCommerce. However, stable blocks are bundled into WooCommerce, and can be added from the "WooCommerce" section in the block inserter.

- **Active Filters**
- **All Products**
- **All Reviews**
- **Best Selling Products**
- **Cart including Cross-Sells**
- **Checkout**
- **Featured Category**
- **Featured Product**
- **Filter by Attribute**
- **Filter by Price**
- **Filter by Rating**
- **Filter by Stock**
- **Hand-picked Products**
- **Mini-Cart**
- **Newest Products**
- **On Sale Products**
- **Product Categories List**
- **Product Search**
- **Products by Attribute**
- **Products by Category**
- **Products by Tag**
- **Reviews by Category**
- **Reviews by Product**
- **Top Rated Products**
- **Customer Account**

== Getting Started ==

= Minimum Requirements =

* Latest release versions of WordPress and WooCommerce ([read more here](https://developer.woocommerce.com/2021/07/27/announcing-a-change-to-the-woocommerce-blocks-version-support-policy/))
* PHP version 7.3 or greater (PHP 7.4 or greater is recommended)
* MySQL version 5.6 or greater

Visit the [WooCommerce server requirements documentation](https://woocommerce.com/document/server-requirements/) for a detailed list of server requirements.

= Automatic installation =

Automatic installation is the easiest option as WordPress handles the file transfers itself and you don’t need to leave your web browser. To do an automatic install of this plugin, log in to your WordPress dashboard, navigate to the Plugins menu and click Add New.

In the search field type “WooCommerce Blocks” and click Search Plugins. Once you’ve found this plugin you can view details about it such as the point release, rating and description. Most importantly of course, you can install it by simply clicking “Install Now”.

= Manual installation =

The manual installation method involves downloading the plugin and uploading it to your webserver via your favourite FTP application. The WordPress codex contains [instructions on how to do this here](https://codex.wordpress.org/Managing_Plugins#Manual_Plugin_Installation).

= Sample data =

WooCommerce comes with some sample data you can use to populate the products and get started building Products blocks quickly and easily. You can use the core [CSV importer](https://woocommerce.com/document/product-csv-importer-exporter/) or our [CSV Import Suite plugin](https://woocommerce.com/products/product-csv-import-suite/) to import sample_products.csv.

= Where can I report bugs or contribute to the project? =

Bugs should be reported in the [WooCommerce Blocks GitHub repository](https://github.com/woocommerce/woocommerce-gutenberg-products-block/).

= This is awesome! Can I contribute? =

Yes you can! Join in on our [GitHub repository](https://github.com/woocommerce/woocommerce-gutenberg-products-block/) :)

Release and roadmap notes available on the [WooCommerce Developers Blog](https://woocommerce.wordpress.com/2019/01/15/woocommerce-blocks-1-3-0-release-notes/)

== Changelog ==

= 11.7.0 - 2023-12-05 =

#### Enhancements

- The Block Hooks API is implemented to auto-inject the Mini-Cart block in header patterns and template parts when the "Twenty Twenty-Four" theme is active. The Mini-Cart block also now defaults to not show the total for the items in the cart when inserted into content. ([11745](https://github.com/woocommerce/woocommerce-blocks/pull/11745))
- Decrease modal width. ([12003](https://github.com/woocommerce/woocommerce-blocks/pull/12003))
- [Store Customization] Update the default content in patterns. ([11997](https://github.com/woocommerce/woocommerce-blocks/pull/11997))
- [Store Customization] Update the "Footer with 3 Menus" pattern to remove the last 2 menus. ([11980](https://github.com/woocommerce/woocommerce-blocks/pull/11980))
- Limit number of visible incompatible extensions in sidebar notice. ([11972](https://github.com/woocommerce/woocommerce-blocks/pull/11972))
- Improve readability of order note. ([11944](https://github.com/woocommerce/woocommerce-blocks/pull/11944))
- Reorganise Columns controls and fix undefined problem in Product Collection settings. ([11937](https://github.com/woocommerce/woocommerce-blocks/pull/11937))
- Switch to NoticeBanner component inside Store Notices Block placeholder. ([11920](https://github.com/woocommerce/woocommerce-blocks/pull/11920))
- Preserve shrinkColumns value when switching the layout type of Product Collection. ([11905](https://github.com/woocommerce/woocommerce-blocks/pull/11905))
- Tweak the product prompt. ([11903](https://github.com/woocommerce/woocommerce-blocks/pull/11903))
- Add DELETE `private/ai/pattern` endpoint. ([11890](https://github.com/woocommerce/woocommerce-blocks/pull/11890))
- Update notice for default cart and checkout. ([11861](https://github.com/woocommerce/woocommerce-blocks/pull/11861))
- Enable shrink columns option in Product Collection by default. ([11821](https://github.com/woocommerce/woocommerce-blocks/pull/11821))
- Move `Combobox` to components package. ([11353](https://github.com/woocommerce/woocommerce-blocks/pull/11353))
- Interactivity API: Implement the new `store()` API. ([11071](https://github.com/woocommerce/woocommerce-blocks/pull/11071))

#### Bug Fixes

- [CYS] Fix regression and ensure AI-generated content is assigned to products after the third attempt. ([12016](https://github.com/woocommerce/woocommerce-blocks/pull/12016))
- [Product Collection] Fix: HTML Entity Decoding in Taxonomy Controls. ([11982](https://github.com/woocommerce/woocommerce-blocks/pull/11982))
- Product Gallery: Add a Product Image fallback. ([11978](https://github.com/woocommerce/woocommerce-blocks/pull/11978))
- Reviews by Product: Fix reviews count not appearing in product selector. ([11976](https://github.com/woocommerce/woocommerce-blocks/pull/11976))
- Hook `woocommerce_single_product_summary` action to `core/post-excerpt` block. ([11953](https://github.com/woocommerce/woocommerce-blocks/pull/11953))
- fix: Store notices always shows as an error type #11768. ([11932](https://github.com/woocommerce/woocommerce-blocks/pull/11932))
- [Product Collection] Fix: HTML entity decoding for product names in Hand-Picked Products. ([11927](https://github.com/woocommerce/woocommerce-blocks/pull/11927))
- Validate coupon usage against customer id AND emails. ([11860](https://github.com/woocommerce/woocommerce-blocks/pull/11860))
- Pass order ID to woocommerce_before_thankyou hook. ([11852](https://github.com/woocommerce/woocommerce-blocks/pull/11852))
- Translate the prefixes passed to post-terms in product-meta. ([11811](https://github.com/woocommerce/woocommerce-blocks/pull/11811))
- Prevent fatal errors when using Cart Tokens and creating new accounts on checkout. ([11785](https://github.com/woocommerce/woocommerce-blocks/pull/11785))
- Product Gallery Thumbnails: Add support for cropping. ([11718](https://github.com/woocommerce/woocommerce-blocks/pull/11718))
- Fix: Product Collection show products with correct stock statuses. ([11708](https://github.com/woocommerce/woocommerce-blocks/pull/11708))
- Product Gallery Thumbnails: Fix overflow issues and improve responsiveness. ([11665](https://github.com/woocommerce/woocommerce-blocks/pull/11665))

#### Various

- Update extensibility doc. ([11924](https://github.com/woocommerce/woocommerce-blocks/pull/11924))
- Move `CheckboxControl` to components package and leave alias in checkout package. ([11662](https://github.com/woocommerce/woocommerce-blocks/pull/11662))

= 11.6.2 - 2023-12-04 =

#### Enhancements

- Update the "Give us your feedback" link to point to the WooCommerce repo discussions. [#11999](https://github.com/woocommerce/woocommerce-blocks/pull/11999) [#12006](https://github.com/woocommerce/woocommerce-blocks/pull/12006)

= 11.6.1 - 2023-11-23 =

#### Bug Fixes

- Add missing woocommerce classname to Classic Cart/Checkout Blocks container so UI updates when the cart is emptied. [#11919](https://github.com/woocommerce/woocommerce-blocks/pull/11919)
- Fix an issue that caused the Order by select in Reviews blocks to always be disabled. [#11918](https://github.com/woocommerce/woocommerce-blocks/pull/11918)
- Fix All Reviews, Reviews by Product and Reviews by Category blocks not being rendered. [#11913](https://github.com/woocommerce/woocommerce-blocks/pull/11913)

= 11.6.0 - 2023-11-22 =

#### Enhancements

- Product Collection - New 'No Results' block with default UI. [#11783](https://github.com/woocommerce/woocommerce-blocks/pull/11783)
- We have moved the TotalsItem, TotalsFees, Subtotal, Banner, StoreNotice, StoreNotices, Panel, TextInput, ValidatedTextInput and ValidationInputError components to the @woocommerce/blocks-components package. Previously these were available in @woocommerce/blocks-checkout . Your code will continue to work as we have added aliases to the new location. Despite this, it is recommended that you change your code to import this component from @woocommerce/blocks-components as the import from the checkout package will be deprecated in the future. [#11766](https://github.com/woocommerce/woocommerce-blocks/pull/11766) [#11698](https://github.com/woocommerce/woocommerce-blocks/pull/11698) [#11654](https://github.com/woocommerce/woocommerce-blocks/pull/11654) [#11773](https://github.com/woocommerce/woocommerce-blocks/pull/11773)
- Improve performance in patterns registration. [#11733](https://github.com/woocommerce/woocommerce-blocks/pull/11733)
- Patterns: remove unused author, sticky, and parents attributes from the Product Collection block in patterns. [#11673](https://github.com/woocommerce/woocommerce-blocks/pull/11673)
- Semantic enhancement to the position of a phone field in Checkout. [#11651](https://github.com/woocommerce/woocommerce-blocks/pull/11651)
- Migrate the Product Button to the new store() API of the Interactivity API.. [#11558](https://github.com/woocommerce/woocommerce-blocks/pull/11558)
- Product Collection: Add support for filtering products by featured status. [#11522](https://github.com/woocommerce/woocommerce-blocks/pull/11522)

#### Bug Fixes

- Make "Use same address for billing" visible by default. [#11804](https://github.com/woocommerce/woocommerce-blocks/pull/11804)
- Fix the order endpoint tax line items format. [#11796](https://github.com/woocommerce/woocommerce-blocks/pull/11796)
- Store API/Blocks Extensibility: Fix recursive extension schema validation. [#11792](https://github.com/woocommerce/woocommerce-blocks/pull/11792)
- Fix: Left align local pickup address. [#11772](https://github.com/woocommerce/woocommerce-blocks/pull/11772)
- Fix typo in classic checkout modal. [#11771](https://github.com/woocommerce/woocommerce-blocks/pull/11771)
- Fix hardcoded shop link in "Hero Product 3 Split" pattern. [#11767](https://github.com/woocommerce/woocommerce-blocks/pull/11767)
- Fix billing address condensed address state in the editor and in Firefox. [#11765](https://github.com/woocommerce/woocommerce-blocks/pull/11765)
- Related Products: Make the heading translated when in blockified Single Product template. [#11693](https://github.com/woocommerce/woocommerce-blocks/pull/11693)
- Add to Cart with Options block: fix inconsistency between editor and frontend styles. [#11614](https://github.com/woocommerce/woocommerce-blocks/pull/11614)
- Product Button: Improve the width and padding. [#11537](https://github.com/woocommerce/woocommerce-blocks/pull/11537)
- Fix the Layout for Shipping and Billing Address Forms in the Checkout Block [#11486](https://github.com/woocommerce/woocommerce-blocks/pull/11486)
- Minor fixes for PHP 8. [#11473](https://github.com/woocommerce/woocommerce-blocks/pull/11473)
- Product Button: always enqueue the store. [#11858](https://github.com/woocommerce/woocommerce-blocks/pull/11858)
- Fixed params passed to woocommerce_before_thankyou for block checkout. This should be an order ID, not an order object. [#11862](https://github.com/woocommerce/woocommerce-blocks/pull/11862)
- Enhanced validation for limited use coupons and guest users. [#11860](https://github.com/woocommerce/woocommerce-blocks/pull/11860)

#### Documentation

- Updated documentation for the onProcessingSetup observer. [#11751](https://github.com/woocommerce/woocommerce-blocks/pull/11751)

= 11.5.4 - 2023-11-13 =

#### Bug Fixes

- Prevent PHP warnings when using Jetpack WooCommerce Analytics module. [#11707](https://github.com/woocommerce/woocommerce-blocks/pull/11707)
- Fixed address components in Firefox, and editing of address form in the editor. [#11714](https://github.com/woocommerce/woocommerce-blocks/pull/11714)
- Fix Classic Cart/Checkout styling on non-cart and checkout pages. [#11694](https://github.com/woocommerce/woocommerce-blocks/pull/11694)
- Fix double border in cart and notes field width on mobile. [11742](https://github.com/woocommerce/woocommerce-blocks/pull/11742)
- Ensure that incompatible notices are displayed in Safari. [#11736](https://github.com/woocommerce/woocommerce-blocks/pull/11736)
- Enabled the new blockified Order Confirmation by default for block-based themes. [#11615](https://github.com/woocommerce/woocommerce-blocks/pull/11615)

= 11.5.3 - 2023-11-08 =

#### Bug Fixes

- Use wp_json_file_decode instead of json_decode [11681](https://github.com/woocommerce/woocommerce-blocks/pull/11681)

= 11.5.2 - 2023-11-08 =

#### Bug Fixes

- Rename the Centered Header Menu with Search to Centered Header Menu. [11637](https://github.com/woocommerce/woocommerce-blocks/pull/11637)
- Fix decoding issue and pattern. [11681](https://github.com/woocommerce/woocommerce-blocks/pull/11681)

= 11.5.1 - 2023-11-08 =

#### Bug Fixes

- WordPress 6.4: fixed a bug which would break sites using the Classic Template block for the Single Product template. ([11455](https://github.com/woocommerce/woocommerce-blocks/pull/11455))
- Fix an error that might appear when pre_get_block_template filter was called with wrong params. ([11690](https://github.com/woocommerce/woocommerce-blocks/pull/11690))

= 11.5.0 - 2023-11-06 =

#### Enhancements

- Add margin bottom to the `Hero Product 3 Split` pattern. ([11573](https://github.com/woocommerce/woocommerce-blocks/pull/11573))
- Set explicit margins in the search bar group on the `Large Header` pattern. ([11571](https://github.com/woocommerce/woocommerce-blocks/pull/11571))
- Add aspect ratio to the `Featured Products 5 Columns` pattern. ([11570](https://github.com/woocommerce/woocommerce-blocks/pull/11570))
- [CYS] Fix `Product Collection 4 Columns` pattern button height. ([11553](https://github.com/woocommerce/woocommerce-blocks/pull/11553))
- Change the "chessboard" pattern structure to improve mobile view. ([11545](https://github.com/woocommerce/woocommerce-blocks/pull/11545))
- `Footer with 3 menus` pattern: Update the spacing in the columns to improve the mobile view. ([11544](https://github.com/woocommerce/woocommerce-blocks/pull/11544))
- Add checkout-header template to the correct area in site editor. ([11528](https://github.com/woocommerce/woocommerce-blocks/pull/11528))
- Product gallery/add crop images. ([11482](https://github.com/woocommerce/woocommerce-blocks/pull/11482))
- Remove authors filter from Product Collection block. ([11427](https://github.com/woocommerce/woocommerce-blocks/pull/11427))
- Move `SortSelect` to components package. ([11411](https://github.com/woocommerce/woocommerce-blocks/pull/11411))
- Move `Textarea` to components package. ([11384](https://github.com/woocommerce/woocommerce-blocks/pull/11384))
- Move `Title` to components package. ([11383](https://github.com/woocommerce/woocommerce-blocks/pull/11383))
- Use the <header> element for the checkout header. ([11222](https://github.com/woocommerce/woocommerce-blocks/pull/11222))

#### Bug Fixes

- Fix `Hero Product 3 Split` pattern text content. ([11612](https://github.com/woocommerce/woocommerce-blocks/pull/11612))
- Prevent theme button border appearing in opinionated patterns. ([11564](https://github.com/woocommerce/woocommerce-blocks/pull/11564))
- Prevent Sale badge overflowing the Product Image in some product grid blocks. ([11556](https://github.com/woocommerce/woocommerce-blocks/pull/11556))
- Block Checkout: Add back missing render-checkout-form hook. ([11554](https://github.com/woocommerce/woocommerce-blocks/pull/11554))
- Ensure that the "Remove Item" link on Cart block has a hover state. ([11526](https://github.com/woocommerce/woocommerce-blocks/pull/11526))
- All Products: Add cursor pointer when hovering over pagination items. ([11502](https://github.com/woocommerce/woocommerce-blocks/pull/11502))
- Product Collection: Fix the PHP Warning after migrating from Products (Beta). ([11494](https://github.com/woocommerce/woocommerce-blocks/pull/11494))
- Footer with 2 Menus Dark pattern: Fix the Site Title color contrast in TT4. ([11484](https://github.com/woocommerce/woocommerce-blocks/pull/11484))
- Essential Header Dark pattern: Fix color contrast issues in TT4. ([11480](https://github.com/woocommerce/woocommerce-blocks/pull/11480))
- Display shipping calculator link for guests shopper. ([11442](https://github.com/woocommerce/woocommerce-blocks/pull/11442))
- Comboboxes should match against values before looking at labels. ([11410](https://github.com/woocommerce/woocommerce-blocks/pull/11410))
- Fix products incorrectly marked as discounted. ([11386](https://github.com/woocommerce/woocommerce-blocks/pull/11386))
- Ensure input is validated when autofilled in Firefox. ([11062](https://github.com/woocommerce/woocommerce-blocks/pull/11062))

#### compatibility

- Remove bullet points and unnecessary padding from `SearchListControl`. ([11444](https://github.com/woocommerce/woocommerce-blocks/pull/11444))

= 11.4.9 - 2023-11-21 =

#### Bug fixes

- Fixed params passed to woocommerce_before_thankyou for block checkout. This should be an order ID, not an order object. ([#11862](https://github.com/woocommerce/woocommerce-blocks/pull/11862))
- Enhance validation for limited use coupons and guest users. ([#11860](https://github.com/woocommerce/woocommerce-blocks/pull/11860))

= 11.4.8 - 2023-11-10 =

#### Bug Fixes

- Ensure that incompatible notices are displayed in Safari. [11736](https://github.com/woocommerce/woocommerce-blocks/pull/11736)

= 11.4.7 - 2023-11-09 =

#### Bug Fixes

- Fixed address components in Firefox, and editing of address form in the editor. [#11714](https://github.com/woocommerce/woocommerce-blocks/pull/11714)

= 11.4.6 - 2023-11-09 =

#### Bug Fixes

- Prevent PHP warnings when using Jetpack WooCommerce Analytics module. [#11707](https://github.com/woocommerce/woocommerce-blocks/pull/11707)

= 11.4.5 - 2023-11-07 =

#### Bug Fixes

- WordPress 6.4: fixed a bug which would break sites using the Classic Template block for the Single Product template. https://github.com/woocommerce/woocommerce-blocks/pull/11455

= 11.4.4 - 2023-11-06 =

#### Enhancements

- Enabled the new blockified Order Confirmation by default for block-based themes. [11615](https://github.com/woocommerce/woocommerce-blocks/pull/11615)

= 11.4.3 - 2023-10-30 =

#### Enhancements

- Improve Hero Product Chessboard pattern. [11423](https://github.com/woocommerce/woocommerce-blocks/pull/11423)
- Fix spacing on the "Minimal header" pattern. [11477](https://github.com/woocommerce/woocommerce-blocks/pull/11477)
- "Product Gallery" improvements: remove product summary and update margins. [11464](https://github.com/woocommerce/woocommerce-blocks/pull/11464)
- Patterns with Search Bar: improve style. [11478](https://github.com/woocommerce/woocommerce-blocks/pull/11478)
- "Product Collection X Columns" patterns: align "no reviews" text with the star. [11468](https://github.com/woocommerce/woocommerce-blocks/pull/11468)
- Rename the Footer with Simple Menu and Cart pattern. [11487](https://github.com/woocommerce/woocommerce-blocks/pull/11487)
- Enhance the Hero Product Split pattern. [11505](https://github.com/woocommerce/woocommerce-blocks/pull/11505)
- Simplify the Hero Product 3 Split pattern. [11495](https://github.com/woocommerce/woocommerce-blocks/pull/11495)
- Improve the Centered Header Menu with Search pattern. [11304](https://github.com/woocommerce/woocommerce-blocks/pull/11304)
- Large Header pattern: improve the layout on mobile view. [11490](https://github.com/woocommerce/woocommerce-blocks/pull/11490)
- Improve the "Large footer" spacing. [11520](https://github.com/woocommerce/woocommerce-blocks/pull/11520)

= 11.4.2 - 2023-10-25 =

#### Enhancements

- Improve the button size on the "Product Collection 4 Column" pattern. [11433](https://github.com/woocommerce/woocommerce-blocks/pull/11433)
- Update the patterns content after a plugin update. [11210](https://github.com/woocommerce/woocommerce-blocks/pull/11210)
- Improve the "Minimal header" pattern spacing and title. [11434](https://github.com/woocommerce/woocommerce-blocks/pull/11434)
- Store Customization > Ensure dummy products can have AI-generated content. [11155](https://github.com/woocommerce/woocommerce-blocks/pull/11155)
- Improve the Testimonials 3 columns pattern. [11430](https://github.com/woocommerce/woocommerce-blocks/pull/11430)
- Add overlay to the "Featured Category Triple" pattern. [11428](https://github.com/woocommerce/woocommerce-blocks/pull/11428)
- Improve the "Footer with 3 Menus" pattern. [11379](https://github.com/woocommerce/woocommerce-blocks/pull/11379)
- Update the "Large footer" pattern. [11413](https://github.com/woocommerce/woocommerce-blocks/pull/11413)
- Improve the "Essential Header". [11449](https://github.com/woocommerce/woocommerce-blocks/pull/11449)
- Improve the mobile view for the Minimal header pattern. [11463](https://github.com/woocommerce/woocommerce-blocks/pull/11463)
- Update the Footer with simple menu and cart pattern. [11409](https://github.com/woocommerce/woocommerce-blocks/pull/11409)
- Update the image provider for the Customize Your Store experience, migrating from Verticals to Pexels. [11280](https://github.com/woocommerce/woocommerce-blocks/pull/11280)

= 11.4.1 - 2023-10-25 =

#### Bug Fixes

- Regenerate package.

= 11.4.0 - 2023-10-24 =

#### Enhancements

- Store Customization: Update the "Large Footer" pattern. (https://github.com/woocommerce/woocommerce-blocks/pull/11381)
- Store Customization: Update the `Footer with 3 Menus` pattern. (https://github.com/woocommerce/woocommerce-blocks/pull/11356)
- Store Customization: Update the Social pattern. (https://github.com/woocommerce/woocommerce-blocks/pull/11355)
- Store Customization: Re-enable the `Product Hero 2 Column 2 Row` pattern. (https://github.com/woocommerce/woocommerce-blocks/pull/11346)
- Product Collection: Shrink columns to fit. (https://github.com/woocommerce/woocommerce-blocks/pull/11320)
- Store Customization: Adjust margins and paddings for the `Featured Category Triple`. (https://github.com/woocommerce/woocommerce-blocks/pull/11319)
- Move `Spinner` to components package. (https://github.com/woocommerce/woocommerce-blocks/pull/11317)
- Store Customization: Fix "Discount Banner" pattern. (https://github.com/woocommerce/woocommerce-blocks/pull/11315)
- Move `RadioControl`and `RadioControlAccordion` components to components package. (https://github.com/woocommerce/woocommerce-blocks/pull/11312)
- Store Customization: Fix the "Testimonials 3 Columns" title alignment. (https://github.com/woocommerce/woocommerce-blocks/pull/11281)
- Store Customization: Fix pattern spacing in homepage template 3. (https://github.com/woocommerce/woocommerce-blocks/pull/11279)
- Store Customization: Fix pattern spacing in homepage template 2. (https://github.com/woocommerce/woocommerce-blocks/pull/11278)
- Store Customization: Fix pattern spacing in homepage template 1. (https://github.com/woocommerce/woocommerce-blocks/pull/11268)
- Store Customization: Remove placeholder text from the image on the Hero Product Chessboard pattern. (https://github.com/woocommerce/woocommerce-blocks/pull/11250)
- Move `FormStep` to the components package. (https://github.com/woocommerce/woocommerce-blocks/pull/11246)
- Temporarily disable the `woocommerce-blocks/product-hero-2-col-2-row` pattern. (https://github.com/woocommerce/woocommerce-blocks/pull/11234)
- Add: `menu-order` orderby option for Product Attribute Terms route. (https://github.com/woocommerce/woocommerce-blocks/pull/11232)
- Move `FormattedMonetaryAmount` to the components package. (https://github.com/woocommerce/woocommerce-blocks/pull/11230)
- Cart and Checkout block transforms for classic shortcodes. (https://github.com/woocommerce/woocommerce-blocks/pull/11228)
- Use the <main> tag in Cart and Checkout templates. (https://github.com/woocommerce/woocommerce-blocks/pull/11224)
- Move `Chip` and `RemovableChip` to components package. (https://github.com/woocommerce/woocommerce-blocks/pull/11223)
- Move `CheckboxList` and create new `blocksComponents` package. (https://github.com/woocommerce/woocommerce-blocks/pull/11214)
- Store Customization MVP: Add fallbacks to all the pattern titles, descriptions and buttons. (https://github.com/woocommerce/woocommerce-blocks/pull/11208)
- Checkout Field Padding and Spacing. (https://github.com/woocommerce/woocommerce-blocks/pull/11207)
- Make patterns full width and fix margin. (https://github.com/woocommerce/woocommerce-blocks/pull/11206)
- Product Collection 5 Columns pattern: Update title and price to be rows instead of columns. (https://github.com/woocommerce/woocommerce-blocks/pull/11205)
- Product Collection: Featured Products 5 Columns: Remove no results block. (https://github.com/woocommerce/woocommerce-blocks/pull/11198)
- Add universal border radius for form elements and components. (https://github.com/woocommerce/woocommerce-blocks/pull/11193)
- Update wording on checkout error message to try to prevent retries. (https://github.com/woocommerce/woocommerce-blocks/pull/10888)

#### Bug Fixes

- Remove hardcoded queryIds from patterns that include Product Collection. (https://github.com/woocommerce/woocommerce-blocks/pull/11290)
- Fix: Mini-Cart block shows wrong total if theres multiple installs on the same domain. (https://github.com/woocommerce/woocommerce-blocks/pull/11257)
- Fix inconsistent border focus styles. (https://github.com/woocommerce/woocommerce-blocks/pull/11203)
- Product Gallery pattern: Use the Product Collection block. (https://github.com/woocommerce/woocommerce-blocks/pull/11194)
- Increase number of visible products when cart is empty. (https://github.com/woocommerce/woocommerce-blocks/pull/11182)
- Fix a bug in which shipping phone field was not being synced to the billing field. (https://github.com/woocommerce/woocommerce-blocks/pull/10603)
- Always show the `Enable the shipping calculator on the cart page` option. (https://github.com/woocommerce/woocommerce-blocks/pull/11421)

#### Documentation

- Add order and checkout order endpoint documentation. (https://github.com/woocommerce/woocommerce-blocks/pull/11157)

#### Various

- Add Local Pickup event and Cart/Checkout page views events. (https://github.com/woocommerce/woocommerce-blocks/pull/11225)

= 11.3.1 - 2023-10-16 =

#### Bug Fixes

- All Products: fix error during rendering on the frontend. ([11263](https://github.com/woocommerce/woocommerce-blocks/pull/11263))

= 11.3.0 - 2023-10-09 =

#### Enhancements

- Introduced condensed address components on checkout for customers with an existing address. ([11167](https://github.com/woocommerce/woocommerce-blocks/pull/11167))
- Update aspect ratio, icons and default text for the Social: Follow us on social media pattern. ([11161](https://github.com/woocommerce/woocommerce-blocks/pull/11161))
- Add horizontal padding to the Featured Category Triple pattern. ([11160](https://github.com/woocommerce/woocommerce-blocks/pull/11160))
- Remove the "no results" placeholder and pagination and set aspect ratio on the Product Collection 3 Columns, Product Collection 4 Columns, Product Collection 5 Columns, and Product Gallery patterns. ([11145](https://github.com/woocommerce/woocommerce-blocks/pull/11145))
- Add group and padding to the Product Collection 3 Columns, Featured Category Triple and the Social: Follow us on social media patterns. ([11144](https://github.com/woocommerce/woocommerce-blocks/pull/11144))
- New Product Collection Patterns: add the new Product Collection 3 Columns, Product Collection 4 Columns, Product Collection 5 Columns and Product Collection: Featured Products 5 Columns patterns. ([11134](https://github.com/woocommerce/woocommerce-blocks/pull/11134))
- Add titles and padding to the Product Collection: Featured Products 5 Columns and the Product Gallery patterns. ([11131](https://github.com/woocommerce/woocommerce-blocks/pull/11131))
- Add default image and fixed height to the Just Arrived Full Hero pattern. ([11130](https://github.com/woocommerce/woocommerce-blocks/pull/11130))
- Add titles and padding to the Product Collection 4 Columns, Product Collection 5 Columns, and Testimonials 3 Columns patterns. ([11129](https://github.com/woocommerce/woocommerce-blocks/pull/11129))
- Add Add to Cart button's product price data attribute. ([11117](https://github.com/woocommerce/woocommerce-blocks/pull/11117))
- Just Arrived Full Hero pattern: wireframe the content and adjust the pattern width. ([11115](https://github.com/woocommerce/woocommerce-blocks/pull/11115))
- Remove opinionated styles from the Hero Product 3 Split pattern. ([11110](https://github.com/woocommerce/woocommerce-blocks/pull/11110))
- Add the Featured Category Cover Image pattern. ([11109](https://github.com/woocommerce/woocommerce-blocks/pull/11109))
- Add fee ID to parent cart and checkout block. ([11054](https://github.com/woocommerce/woocommerce-blocks/pull/11054))
- Update: Adjust text of incompatibility sidebar notice and show extensions, that explicitly declared incompatibility with the Cart and Checkout blocks. ([10877](https://github.com/woocommerce/woocommerce-blocks/pull/10877))

#### Bug Fixes

- Fix Store Notices block breaks page editors. ([11165](https://github.com/woocommerce/woocommerce-blocks/pull/11165))
- Ensure the Just Arrived Full Hero pattern can have an AI-selected images assigned to it and add a background dim. ([11159](https://github.com/woocommerce/woocommerce-blocks/pull/11159))
- Testimonials 3 Columns pattern > Update the width and fix the PHP warnings that could be triggered if the content saved within the wc_blocks_patterns_content option didn't match the updated patterns dictionary. ([11158](https://github.com/woocommerce/woocommerce-blocks/pull/11158))
- Pattern: Fetch product ID with JS to prevent unnecessary queries on every page load. ([11138](https://github.com/woocommerce/woocommerce-blocks/pull/11138))
- Fix checkout state/country field width in the site editor. ([11133](https://github.com/woocommerce/woocommerce-blocks/pull/11133))
- Fixed PHP notice that would appear if an API endpoint failed to load. ([11128](https://github.com/woocommerce/woocommerce-blocks/pull/11128))
- Made error icon on checkout match text color. ([11127](https://github.com/woocommerce/woocommerce-blocks/pull/11127))
- Fix a PHP error that was occurring when the WooCommerce Product Add-ons or the WooCommerce Product Bundles plugins were enabled. ([11082](https://github.com/woocommerce/woocommerce-blocks/pull/11082))
- Resolved an issue where the Single Product block did not respect the WooCommerce setting for redirecting to the cart page after successful addition. ([11151](https://github.com/woocommerce/woocommerce-blocks/pull/11151))

= 11.2.0 - 2023-09-25 =

#### Enhancements

- Remove order and checkout order endpoints experimental flag. ([11022](https://github.com/woocommerce/woocommerce-blocks/pull/11022))
- Ensure the content of the patterns is also AI-generated. ([10997](https://github.com/woocommerce/woocommerce-blocks/pull/10997))
- Product Collection: Transfer layout options from Toolbar to Inspector controls. ([10922](https://github.com/woocommerce/woocommerce-blocks/pull/10922))
- Add pattern chooser in Product Collection. ([10876](https://github.com/woocommerce/woocommerce-blocks/pull/10876))
- Product Gallery: Lock the Sale Badge and the Next/Prev Buttons. ([10869](https://github.com/woocommerce/woocommerce-blocks/pull/10869))
- Refactor Cart and Checkout Page Templates. ([10773](https://github.com/woocommerce/woocommerce-blocks/pull/10773))
-  Blockified Order Confirmation. ([10056](https://github.com/woocommerce/woocommerce-blocks/pull/10056))

#### Bug Fixes

- Product Gallery: Fix the Product Gallery Thumbnails on click. ([11032](https://github.com/woocommerce/woocommerce-blocks/pull/11032))
- WooExpress: Fix Checkout and Cart Blocks Editor Crash. ([11024](https://github.com/woocommerce/woocommerce-blocks/pull/11024))
- Product Gallery: Fix zoom animation on large Image. ([11023](https://github.com/woocommerce/woocommerce-blocks/pull/11023))
- Fix: Password Protection not respected on single product template. ([10999](https://github.com/woocommerce/woocommerce-blocks/pull/10999))
- Product Gallery Pager: Remove the Pager markup if there's only one image. ([10998](https://github.com/woocommerce/woocommerce-blocks/pull/10998))
- Related Products: Hide the block outside of Single Product Template and Single Product block. ([10978](https://github.com/woocommerce/woocommerce-blocks/pull/10978))
- Single Product: Fix the Align setting. ([10977](https://github.com/woocommerce/woocommerce-blocks/pull/10977))
- Hide unexpected bullet point in Product Collection on Storefront. ([10945](https://github.com/woocommerce/woocommerce-blocks/pull/10945))
- Add custom regex for validating Nicaraguan postal codes. ([10928](https://github.com/woocommerce/woocommerce-blocks/pull/10928))
- Update `postcode-validator` to 3.8.15 to validate "new" Taiwanese postcodes. ([10924](https://github.com/woocommerce/woocommerce-blocks/pull/10924))
- BlockTemplatesController: Check that $attributes['theme'] value isset before operating on it. ([10879](https://github.com/woocommerce/woocommerce-blocks/pull/10879))
- Product Gallery: CSS styling tightening up. ([10867](https://github.com/woocommerce/woocommerce-blocks/pull/10867))
- Checkout Block: Prevent changes in the selected shipping method when new rates are added or removed. ([10457](https://github.com/woocommerce/woocommerce-blocks/pull/10457))

= 11.1.3 - 2023-11-08 =

#### Bug Fixes

- WordPress 6.4: fixed a bug which would break sites using the Classic Template block for the Single Product template. [11455](https://github.com/woocommerce/woocommerce-blocks/pull/11455)

= 11.1.2 - 2023-09-27 =

#### Bug Fixes

- Improved escaping [565cd04](https://github.com/woocommerce/woocommerce-blocks/commit/565cd0461dbcc21fa04f77a736da443fc56a054d)

= 11.1.1 - 2023-09-20 =

#### Bug Fixes

- Improved escaping around attributes [bb37d05](https://github.com/woocommerce/woocommerce-blocks/commit/bb37d056bccccd714fa6617c5da6f50d54837f41)

= 11.1.0 - 2023-09-11 =

#### Enhancements

- Add Product Collection Rows pattern. ([10857](https://github.com/woocommerce/woocommerce-blocks/pull/10857))
- Add Simple Grid Product Collection pattern. ([10835](https://github.com/woocommerce/woocommerce-blocks/pull/10835))
- Product Hero Pattern > Adjust the content width. ([10832](https://github.com/woocommerce/woocommerce-blocks/pull/10832))
- Fix: Load blocks style separately for classic themes. ([10758](https://github.com/woocommerce/woocommerce-blocks/pull/10758))
- Adjust `Follow us on social media` pattern icon alignment. ([10709](https://github.com/woocommerce/woocommerce-blocks/pull/10709))
- Add Product Collection Full Grid pattern. ([10665](https://github.com/woocommerce/woocommerce-blocks/pull/10665))

#### Bug Fixes

- Change default number of columns in Related Products. ([10875](https://github.com/woocommerce/woocommerce-blocks/pull/10875))
- Product Image: Fix bug that prevented block styles being applied within the editor. ([10859](https://github.com/woocommerce/woocommerce-blocks/pull/10859))
- Fix bug with wpautop for the empty cart. ([10768](https://github.com/woocommerce/woocommerce-blocks/pull/10768))
- Prevent the Cart block reloading when adding an item to it from the Empty Cart block. ([10624](https://github.com/woocommerce/woocommerce-blocks/pull/10624))
- Product button: Sync temporary number of items on instantiation. ([10604](https://github.com/woocommerce/woocommerce-blocks/pull/10604))
- Customer Account Block: Fixed an issue that would make the account icon scale incorrectly in certain environments (prominently in the TT1 theme). ([10533](https://github.com/woocommerce/woocommerce-blocks/pull/10533))
- Customer Account Block: Fixed an issue that caused padding to be inconsistent between the editor and the front-end. ([10476](https://github.com/woocommerce/woocommerce-blocks/pull/10476))
- Fixed notice displaying on Cart and Checkout template. ([10151](https://github.com/woocommerce/woocommerce-blocks/pull/10151))

= 11.0.0 - 2023-08-30 =

#### Enhancements

- Enable Product Collection block. ([10524](https://github.com/woocommerce/woocommerce-blocks/pull/10524))
- Add Product Collection Grid pattern. ([10660](https://github.com/woocommerce/woocommerce-blocks/pull/10660))
- Enable manual migration of Products to Product Collection. ([10655](https://github.com/woocommerce/woocommerce-blocks/pull/10655))
- Footer Patterns > Ensure the “My Account” text has the same font size as the other texts nearby. ([10614](https://github.com/woocommerce/woocommerce-blocks/pull/10614))
- Updated the image format and content width for the Testimonials Single Pattern. ([10600](https://github.com/woocommerce/woocommerce-blocks/pull/10600))
- Product Gallery block: Add Pager block. ([10320](https://github.com/woocommerce/woocommerce-blocks/pull/10320))

#### Bug Fixes

- Fix UI misalignment of Filter by Price in TT1 theme. ([10732](https://github.com/woocommerce/woocommerce-blocks/pull/10732))
- Refactor block variation registration in product-collection. ([10701](https://github.com/woocommerce/woocommerce-blocks/pull/10701))
- Make View Cart link displayed below the Add to Cart button. ([10675](https://github.com/woocommerce/woocommerce-blocks/pull/10675))
- Store Breadcrumbs block: Fix icon color when block is selected in List View. ([10652](https://github.com/woocommerce/woocommerce-blocks/pull/10652))
- Fix "On sale" badge class. ([10634](https://github.com/woocommerce/woocommerce-blocks/pull/10634))
- Ensure the event dispatched when rendering the empty cart block is done only when it actually renders. ([10619](https://github.com/woocommerce/woocommerce-blocks/pull/10619))

#### Various

- Remove client side navigation from Products beta block. ([10636](https://github.com/woocommerce/woocommerce-blocks/pull/10636))

= 10.9.3 - 2023-08-24 =

#### Bug Fixes

- Add to Cart: fix the problem that variable products couldn't be added to cart

= 10.9.2 - 2023-08-21 =

#### Bug Fixes

- Load Interactivity API in `bootstrap.php`. ([10657](https://github.com/woocommerce/woocommerce-blocks/pull/10657))
- Add to Cart button: fix the layout of View Cart link showing after adding product to cart. ([10675](https://github.com/woocommerce/woocommerce-blocks/pull/10675))
- Fixed checkout and cart page migration routine for users of block themes so that block templates are also migrated. ([10641](https://github.com/woocommerce/woocommerce-blocks/pull/10641))
- Fixed a bug causing Cart and Checkout Blocks to be used by default instead of shortcode versions on WC updates. ([10608](https://github.com/woocommerce/woocommerce-blocks/pull/10608))

= 10.9.1 - 2023-08-17 =

#### Enhancements

- Update WooCommerce Blocks brand assets. ([10622](https://github.com/woocommerce/woocommerce-blocks/pull/10622))

#### Bug Fixes

- Fix "On sale" badge class. ([10634](https://github.com/woocommerce/woocommerce-blocks/pull/10634))
- Remove client side navigation from Products beta block. ([10636](https://github.com/woocommerce/woocommerce-blocks/pull/10636))

= 10.9.0 - 2023-08-14 =

#### Enhancements

- Add the `wc-blocks-footer-pattern` class identifier to all footer patterns. ([10542](https://github.com/woocommerce/woocommerce-blocks/pull/10542))
- Add the `wc-blocks-header-pattern` class identifier to all header patterns. ([10541](https://github.com/woocommerce/woocommerce-blocks/pull/10541))
- Featured Products: Fresh & Tasty pattern: Enhance mobile view and optimize images. ([10521](https://github.com/woocommerce/woocommerce-blocks/pull/10521))
- Add placeholder images and update text styles for the Alternating Image and Text pattern. ([10479](https://github.com/woocommerce/woocommerce-blocks/pull/10479))
- Performance: Selecting shipping rates during checkout, and API calls in general, are now faster. Shipping selection improved by at least 1 second. ([10472](https://github.com/woocommerce/woocommerce-blocks/pull/10472))
- Fixed wcSettings inconsistent property naming style by converting all properties to camelCase. ([10439](https://github.com/woocommerce/woocommerce-blocks/pull/10439))
- Prevent a conflict with 3rd party plugins caused by using the session class too early. ([10373](https://github.com/woocommerce/woocommerce-blocks/pull/10373))
- Product Collection: Use Interactivity API for pagination. ([10361](https://github.com/woocommerce/woocommerce-blocks/pull/10361))
- Products: Fix the before/after shop item hooks. ([10332](https://github.com/woocommerce/woocommerce-blocks/pull/10332))
- Checkout: Prevent postcode validation error notice appearing after pushing changes for other fields. ([10315](https://github.com/woocommerce/woocommerce-blocks/pull/10315))
- Product Button block: Integrate Interactivity API to make the block dynamically. ([10006](https://github.com/woocommerce/woocommerce-blocks/pull/10006))

#### Bug Fixes

- Fix Express Payments buttons display in the Cart block. ([10534](https://github.com/woocommerce/woocommerce-blocks/pull/10534))
- Fixed a bug which caused theme border colors to not correctly show on the blocks on the editor side. ([10468](https://github.com/woocommerce/woocommerce-blocks/pull/10468))
- Fix: Compatibility layer issue when the Products (Beta) block is the top level block. ([10455](https://github.com/woocommerce/woocommerce-blocks/pull/10455))
- Products (Beta): Hooks are no longer fired around the empty content. ([10453](https://github.com/woocommerce/woocommerce-blocks/pull/10453))
- Fix an issue where inner Cart blocks were incorrectly nested. ([10447](https://github.com/woocommerce/woocommerce-blocks/pull/10447))

= 10.8.3 - 2023-08-11 =

#### Bug Fixes

- Create wc-all-block-styles chunk with all blocks stylesheet for classic themes. ([10543](https://github.com/woocommerce/woocommerce-blocks/pull/10543))
- Fix the "On Sale" badge position. ([10550](https://github.com/woocommerce/woocommerce-blocks/pull/10550))

= 10.8.2 - 2023-08-09 =

#### Bug Fixes

- Ensure cached script data is refreshed following a site URL change, version change, or when switching between the WooCommerce Blocks feature plugin and WooCommerce Core.

= 10.8.1 - 2023-08-07 =

#### Bug Fixes

- Classic Template block registration: Add defensive type handling. ([10475](https://github.com/woocommerce/woocommerce-blocks/pull/10475))
- Fixed an issue where modifications to the Cart/Checkout templates made in the site editor would not be visible on the front end. [#10462](https://github.com/woocommerce/woocommerce-blocks/pull/10462)

= 10.8.0 - 2023-07-31 =

#### Enhancements

- Update the Featured Products 5-Item Grid pattern to become wireframed. ([10401](https://github.com/woocommerce/woocommerce-blocks/pull/10401))
- Add selection button to featured items block when id not found. ([10387](https://github.com/woocommerce/woocommerce-blocks/pull/10387))
- Unify 'Powered by' and 'Built with' texts in all patterns. ([10380](https://github.com/woocommerce/woocommerce-blocks/pull/10380))
- `Product Details Product Listing`: Remove the styles on product title and button. ([10362](https://github.com/woocommerce/woocommerce-blocks/pull/10362))
- Update the Featured Category Triple pattern to become wireframed. ([10343](https://github.com/woocommerce/woocommerce-blocks/pull/10343))
- Update the Large Footer and Large Footer Dark patterns to become wireframed. ([10323](https://github.com/woocommerce/woocommerce-blocks/pull/10323))
- Update the `Footer with Simple Menu and Cart` pattern to have no opinionated styles. ([10306](https://github.com/woocommerce/woocommerce-blocks/pull/10306))
- Relocate sale badge to be consistent for products. ([10297](https://github.com/woocommerce/woocommerce-blocks/pull/10297))
- Update the `Footer with 3 menus` pattern to have no opinionated styles. ([10294](https://github.com/woocommerce/woocommerce-blocks/pull/10294))
- Updated Product Hero pattern to have no opinionated styles. ([10292](https://github.com/woocommerce/woocommerce-blocks/pull/10292))
- Remove border controls from Filter by Attribute block. ([10277](https://github.com/woocommerce/woocommerce-blocks/pull/10277))
- Updated "Testimonial Single" pattern to have no opinionated styles. ([10275](https://github.com/woocommerce/woocommerce-blocks/pull/10275))
- Remove opinionated styles from the Hero Product Split pattern. ([10270](https://github.com/woocommerce/woocommerce-blocks/pull/10270))
- Updated Product Hero pattern to have no opinionated styles. ([10260](https://github.com/woocommerce/woocommerce-blocks/pull/10260))
- Updated Testimonial 3 columns pattern to have no opinionated styles. ([10258](https://github.com/woocommerce/woocommerce-blocks/pull/10258))
- Updated Product Collections Newest Arrivals patterns to have no opinionated styles. ([10256](https://github.com/woocommerce/woocommerce-blocks/pull/10256))
- Made checkout event hooks reflect the current state of the cart. ([10254](https://github.com/woocommerce/woocommerce-blocks/pull/10254))
- Remove Featured Category Cover Image pattern in favor of the Feature Category block. ([10252](https://github.com/woocommerce/woocommerce-blocks/pull/10252))
- Small adjustments to the title, description and icon of the Express Checkout block. ([10237](https://github.com/woocommerce/woocommerce-blocks/pull/10237))
- Remove opinionated styles from the Minimal Header pattern. ([10227](https://github.com/woocommerce/woocommerce-blocks/pull/10227))
- Remove opinionated styles from the Centered Header Menu with Search pattern. ([10226](https://github.com/woocommerce/woocommerce-blocks/pull/10226))
- Register a Mini-Cart template part area, so in WP 6.3, the Mini-Cart template part is listed separately from the other template parts. ([10203](https://github.com/woocommerce/woocommerce-blocks/pull/10203))
- Added a new ColorPanel component and implemented in the Mini-Cart block for custom color controls. ([10062](https://github.com/woocommerce/woocommerce-blocks/pull/10062))
- Start using the "Single Product" block for the "Product Details Product Listing" pattern. ([9978](https://github.com/woocommerce/woocommerce-blocks/pull/9978))

#### Bug Fixes

- Fix Classic Template block not rendering in Single Product custom templates. ([10342](https://github.com/woocommerce/woocommerce-blocks/pull/10342))
- Products: Improve performance by preventing running the Product Query logic twice in Editor. ([10340](https://github.com/woocommerce/woocommerce-blocks/pull/10340))
- Fix some styling issues in the product or category selector of some blocks. ([10308](https://github.com/woocommerce/woocommerce-blocks/pull/10308))
- Fix: Incorrect default number of products in editor when inheriting query. ([10303](https://github.com/woocommerce/woocommerce-blocks/pull/10303))
- Fix PHP warnings when doing a search with product collection block. ([10300](https://github.com/woocommerce/woocommerce-blocks/pull/10300))
- Fix styles for the Add to Cart Form block when used together with the Single Product block. ([10282](https://github.com/woocommerce/woocommerce-blocks/pull/10282))
- Fix: Add missing page titles to the Cart and the Checkout templates. ([10281](https://github.com/woocommerce/woocommerce-blocks/pull/10281))
- Fix deprecated warning for featured category and product block. ([10246](https://github.com/woocommerce/woocommerce-blocks/pull/10246))
- Fix indent issue for the search list control in certain themes. ([10245](https://github.com/woocommerce/woocommerce-blocks/pull/10245))
- Fixed classic template appearance in the editor with some of the default themes. ([10240](https://github.com/woocommerce/woocommerce-blocks/pull/10240))
- Newest Products: Fix the stock status filter that would crash the block when opened. ([10201](https://github.com/woocommerce/woocommerce-blocks/pull/10201))
- Fix an issue where the radio buttons for shipping methods were incorrectly aligned on the Cart block in the post and site editor. ([10150](https://github.com/woocommerce/woocommerce-blocks/pull/10150))

#### Various

- Introduce the new **Social: Follow us in social media** pattern. ([10368](https://github.com/woocommerce/woocommerce-blocks/pull/10368))
- Add the new Featured Products: Fresh & Tasty pattern. ([10339](https://github.com/woocommerce/woocommerce-blocks/pull/10339))
- Add an endpoint for processing pay-for-order orders. ([10287](https://github.com/woocommerce/woocommerce-blocks/pull/10287))
- Add an endpoint for getting pay for order orders. ([10199](https://github.com/woocommerce/woocommerce-blocks/pull/10199))

= 10.7.1 - 2023-07-25 =

#### Bug Fixes

- Fix an error occurring due to missing parameters in the `woocommerce_add_to_cart_redirect` filter. ([10316](https://github.com/woocommerce/woocommerce-blocks/pull/10316))

= 10.7.0 - 2023-07-17 =

#### Enhancements

- Updated Essential Header patterns to have no opinionated font sizes. ([10219](https://github.com/woocommerce/woocommerce-blocks/pull/10219))
- Modernize the block registration for the Mini Cart block. ([10168](https://github.com/woocommerce/woocommerce-blocks/pull/10168))
- Improve Banner pattern for mobile devices. ([10107](https://github.com/woocommerce/woocommerce-blocks/pull/10107))
- Bring back user to 1st page when filters are changed. ([9952](https://github.com/woocommerce/woocommerce-blocks/pull/9952))
- Only load styles required by the blocks rendered on the page. ([9831](https://github.com/woocommerce/woocommerce-blocks/pull/9831))
- Enable WooCommerce custom template part support for classic themes that support block template parts. ([9780](https://github.com/woocommerce/woocommerce-blocks/pull/9780))

#### Bug Fixes

- Prevent Mini-Cart template part preview in Site Editor being too high. ([10204](https://github.com/woocommerce/woocommerce-blocks/pull/10204))
- Fix some styling issues in the editor placeholder of some blocks. ([10192](https://github.com/woocommerce/woocommerce-blocks/pull/10192))
- Mini-Cart: Fix the empty cart drawer button styling. ([10169](https://github.com/woocommerce/woocommerce-blocks/pull/10169))
- Single Product Template > Ensure extensions can't trigger fatal errors on customized single product templates without any post content blocks. ([10128](https://github.com/woocommerce/woocommerce-blocks/pull/10128))
- Fix image overlapping in mobile in the Editor for the `Discount banner with image`. ([10108](https://github.com/woocommerce/woocommerce-blocks/pull/10108))
- Fix: Return null or object for ImageAttachmentSchema response. ([9962](https://github.com/woocommerce/woocommerce-blocks/pull/9962))

= 10.6.6. - 2023-08-22 =

#### Bug Fixes

- Fix: Made migration migrate block templates in the current theme. (https://github.com/woocommerce/woocommerce-blocks/pull/10641)
- Fixed a bug causing  Cart and Checkout Blocks to be used by default instead of shortcode versions on WC updates. (https://github.com/woocommerce/woocommerce-blocks/pull/10608)

= 10.6.5 - 2023-08-09 =

#### Bug Fixes

- Avoid to cache script data in a transient. ([10509](https://github.com/woocommerce/woocommerce-blocks/pull/10509))

= 10.6.4 - 2023-08-04 =

#### Bug Fixes

- Classic Template block registration: add defensive type handling. ([10475](https://github.com/woocommerce/woocommerce-blocks/pull/10475))
- Fixed an issue where modifications to the Cart/Checkout templates made in the site editor would not be visible on the front end. [#10462](https://github.com/woocommerce/woocommerce-blocks/pull/10462)

= 10.6.3 - 2023-08-02 =

#### Bug Fixes

- Fix styles for the Add to Cart Form block when used together with the Single Product block. ([10282](https://github.com/woocommerce/woocommerce-blocks/pull/10282))
- Blockified templates: Improve migration logic. [#10415](https://github.com/woocommerce/woocommerce-blocks/pull/10415)

= 10.6.2 - 2023-07-31 =

#### Enhancements

- Updated Product Hero pattern to have no opinionated styles. ([10255](https://github.com/woocommerce/woocommerce-blocks/pull/10255))
- Only load styles required by the blocks rendered on the page. ([9831](https://github.com/woocommerce/woocommerce-blocks/pull/9831))

#### Bug Fixes

- Fix: Resolve the conflict preventing the navigation block from being correctly previewed in the editor while having WooCommerce enabled. ([10388](https://github.com/woocommerce/woocommerce-blocks/pull/10388))
- Products: Fix the incorrect layout with WordPress 6.3+ without Gutenberg. ([10360](https://github.com/woocommerce/woocommerce-blocks/pull/10360))
- Fixed a rendering bug on content containing Cart and Checkout blocks/shortcode. ([10359](https://github.com/woocommerce/woocommerce-blocks/pull/10359))
- Fix an error occurring due to missing parameters in the `woocommerce_add_to_cart_redirect` filter. ([10316](https://github.com/woocommerce/woocommerce-blocks/pull/10316))
- Single Product Template > Ensure extensions can't trigger fatal errors on customized single product templates without any post content blocks. ([10128](https://github.com/woocommerce/woocommerce-blocks/pull/10128))
- Cart and Checkout Page Migration: Inherit Page template and fix rendering. ([10375](https://github.com/woocommerce/woocommerce-blocks/pull/10375))

= 10.6.1 - 2023-07-17 =

#### Bug Fixes

- Improve performance and fix memory exhaustion errors that could be triggered for stores with a high volume of products using the Products (Beta) or Products Collection blocks. ([#10198](https://github.com/woocommerce/woocommerce-blocks/pull/10198))
- Fix a visual bug with margins around the Proceed to Checkout button on the Cart block. ([10182](https://github.com/woocommerce/woocommerce-blocks/pull/10182))
- Fixed formatting for addresses sent to the Store API. ([#10242](https://github.com/woocommerce/woocommerce-blocks/pull/10242))

#### Security
- Update CORS handling in Store API

= 10.6.0 - 2023-07-07 =

#### Enhancements

- Remove attributes data from saving in HTML for Active Filters block. ([10047](https://github.com/woocommerce/woocommerce-blocks/pull/10047))
- Remove attributes data from saving in HTML for Filter by Price block. ([10039](https://github.com/woocommerce/woocommerce-blocks/pull/10039))
- Product Image block: Add size settings to change the width, height, and scale. ([10034](https://github.com/woocommerce/woocommerce-blocks/pull/10034))
- Remove the word ‘beta’ from the compatibility notice. ([10019](https://github.com/woocommerce/woocommerce-blocks/pull/10019))
- Add new `Discount banner with image` pattern. ([9957](https://github.com/woocommerce/woocommerce-blocks/pull/9957))
- Add new `Product collection banner` pattern. ([9953](https://github.com/woocommerce/woocommerce-blocks/pull/9953))
- Add `Small discount banner with image` pattern. ([9947](https://github.com/woocommerce/woocommerce-blocks/pull/9947))
- Add the new `Banner discount` pattern. ([9936](https://github.com/woocommerce/woocommerce-blocks/pull/9936))
- Start using the Single Product block for the Hero Product pattern. ([9935](https://github.com/woocommerce/woocommerce-blocks/pull/9935))
- Shop by price pattern: Update titles and links to filter by price and rating. ([9915](https://github.com/woocommerce/woocommerce-blocks/pull/9915))
- Update `Hero Product 3 Split` pattern with opinionated button styling and margin adjustments. ([9893](https://github.com/woocommerce/woocommerce-blocks/pull/9893))
- Update header patterns to use the product search block instead of the WP core search block. ([9890](https://github.com/woocommerce/woocommerce-blocks/pull/9890))
- Hero Product – Split pattern: Add link to the shop page to the button. ([9879](https://github.com/woocommerce/woocommerce-blocks/pull/9879))
- Banner pattern: Update colors and improve spacing and centering in small screens. ([9877](https://github.com/woocommerce/woocommerce-blocks/pull/9877))
- Update `Display product count` option to default to `false` in Filter by Attributes, Filter by Stock and Filter by Rating blocks. ([9833](https://github.com/woocommerce/woocommerce-blocks/pull/9833))
- Product Rating: Removes unnecessary Product Rating markup in case there are no real or mocked ratings present. ([9822](https://github.com/woocommerce/woocommerce-blocks/pull/9822))
- Consistent Pattern Titles. ([9814](https://github.com/woocommerce/woocommerce-blocks/pull/9814))
- Mini Cart: Add color settings for the Price, Icon, and Product Count. ([9647](https://github.com/woocommerce/woocommerce-blocks/pull/9647))
- Added new Cart, Checkout, Order Confirmation templates and Checkout Header template part to the Site Editor. ([9301](https://github.com/woocommerce/woocommerce-blocks/pull/9301))

#### Bug Fixes

- Prevent prefetched Cart payload causing wrong values with cached Mini-Cart block. ([10029](https://github.com/woocommerce/woocommerce-blocks/pull/10029))
- Make filter by rating preview consistent with default settings. ([10007](https://github.com/woocommerce/woocommerce-blocks/pull/10007))
- Fix notice banner links for Legacy notices. ([9996](https://github.com/woocommerce/woocommerce-blocks/pull/9996))
- Add to Cart with Options block > Remove opinionated styles for the price list on grouped products. ([9988](https://github.com/woocommerce/woocommerce-blocks/pull/9988))
- Add loading and responsive image attribute to image element of Product Image block. ([9984](https://github.com/woocommerce/woocommerce-blocks/pull/9984))
- Single Product Template Compatibility Layer: Fix some blocks that don't show on the frontend side in a specific case. ([9982](https://github.com/woocommerce/woocommerce-blocks/pull/9982))
- Fix: Single Product Block - Ensure the Product Summary and Title blocks are always rendered with the correct context independent of their position in the editor. ([9968](https://github.com/woocommerce/woocommerce-blocks/pull/9968))
- Ensure saved payment methods do not show if the payment gateway they belong to cannot be used to make payments for the current cart configuration. ([9917](https://github.com/woocommerce/woocommerce-blocks/pull/9917))
- Featured Products 5-item grid: Fix extra whitespace in small viewports. ([9913](https://github.com/woocommerce/woocommerce-blocks/pull/9913))
- Local Pickup - Only show collection address when set. ([9808](https://github.com/woocommerce/woocommerce-blocks/pull/9808))
- Remove unnecessary white space from Testimonials 3 Columns pattern. ([9786](https://github.com/woocommerce/woocommerce-blocks/pull/9786))
- Fix customer account block not able to center align. ([9750](https://github.com/woocommerce/woocommerce-blocks/pull/9750))

= 10.5.0 - 2023-06-19 =

#### Enhancements

- Allow Customer Account Block to be found with keyword "My Account". ([9815](https://github.com/woocommerce/woocommerce-blocks/pull/9815))
- Mini Cart: Add an alternative icon setting. ([9570](https://github.com/woocommerce/woocommerce-blocks/pull/9570))
- Improved the performance of loading frontend assets through the use of caching. ([9120](https://github.com/woocommerce/woocommerce-blocks/pull/9120))
- Add additional optimization to the Pattern images. ([9803](https://github.com/woocommerce/woocommerce-blocks/pull/9803))


#### Bug Fixes

- Fix error when adding the Reviews by Category block to a store without any product reviews. ([9868](https://github.com/woocommerce/woocommerce-blocks/pull/9868))
- Mini Cart block: Fix icon render in the widget selector. ([9828](https://github.com/woocommerce/woocommerce-blocks/pull/9828))
- WooCommerce Product Filters pattern: Improve the preview of the Filter by Attribute block. ([9812](https://github.com/woocommerce/woocommerce-blocks/pull/9812))
- Ensure the "Change Address" button is visible in the cart even if local pickup is selected. ([9809](https://github.com/woocommerce/woocommerce-blocks/pull/9809))
- Fix capitalization in Product Collections: Newest Arrivals pattern. ([9777](https://github.com/woocommerce/woocommerce-blocks/pull/9777))
- Add RTL preview support for mini-cart. ([9756](https://github.com/woocommerce/woocommerce-blocks/pull/9756))
- Fix mini cart icon not displaying correctly in RTL sites. ([9707](https://github.com/woocommerce/woocommerce-blocks/pull/9707))
- Prevent Mini-Cart drawer to show horizontal scrollbar if the system has visible scrollbars. ([9676](https://github.com/woocommerce/woocommerce-blocks/pull/9676))
- All Reviews block: Fix error when adding the block to a store without reviews. ([9869](https://github.com/woocommerce/woocommerce-blocks/pull/9869))
- Products block: fix compatibility with Gutenberg 16. ([9886](https://github.com/woocommerce/woocommerce-blocks/pull/9886))

#### Dependencies

- Peer Dependency: Request 2.88.2. ([9827](https://github.com/woocommerce/woocommerce-blocks/pull/9827))
- Bump fast-xml-parser from 4.0.7 to 4.2.4. ([9818](https://github.com/woocommerce/woocommerce-blocks/pull/9818))

= 10.4.6 - 2023-7-17 =

#### Bug Fixes

- Improve performance and fix memory exhaustion errors that could be triggered for stores with a high volume of products using the Products (Beta) or Products Collection blocks. ([#10198](https://github.com/woocommerce/woocommerce-blocks/pull/10198))
- Fixed formatting for addresses sent to the Store API. ([#10242](https://github.com/woocommerce/woocommerce-blocks/pull/10242))

#### Security
- Update CORS handling in Store API

= 10.4.5 - 2023-06-29 =

#### Bug Fixes

- Product Rating block > Ensure the customer reviews text links to the relevant user reviews within the Single Product block and Single Product template. ([9998](https://github.com/woocommerce/woocommerce-blocks/pull/9998))
- Fix reviews count not showing for the Product Rating block when inside the Single Product page. ([9995](https://github.com/woocommerce/woocommerce-blocks/pull/9995))
- Single Product template: Fix variation SKU switching in the blockified template. ([9990](https://github.com/woocommerce/woocommerce-blocks/pull/9990))
- Single Product template: Fix variation gallery image switching in the blockified template. ([9986](https://github.com/woocommerce/woocommerce-blocks/pull/9986))
- Single Product Block: avoid to register incompatibility blocks with the Single Product Block on the post/page editor. ([10044](https://github.com/woocommerce/woocommerce-blocks/pull/10044))

= 10.4.4 - 2023-06-22 =

#### Bug Fixes

- Fix filter blocks using the old markup not rendering and fix missing translations in those blocks. ([9954](https://github.com/woocommerce/woocommerce-blocks/pull/9954))

= 10.4.3 - 2023-06-20 =

#### Bug Fixes

- Products block: fix compatibility with Gutenberg 16. ([9878](https://github.com/woocommerce/woocommerce-blocks/pull/9878))

= 10.4.2 - 2023-06-12 =

#### Bug Fixes

- Update the Product Collections: Featured Collections to replace the Unsplash image with a CCO licensed image from Pxhere. ([9766](https://github.com/woocommerce/woocommerce-blocks/pull/9766))
- Update the Featured Category patterns to replace the Unsplash image with a CCO licensed image from Pxhere. ([9765](https://github.com/woocommerce/woocommerce-blocks/pull/9765))
- Update the Product Details patterns to replace the Unsplash images with CCO licensed images from Pxhere. ([9764](https://github.com/woocommerce/woocommerce-blocks/pull/9764))
- Update the Hero Product Split pattern to replace the Unsplash image with a CCO licensed image from Pxhere. ([9762](https://github.com/woocommerce/woocommerce-blocks/pull/9762))
- Update the Hero Product Chessboard pattern to replace the Unsplash image with a CCO licensed image from Pxhere. ([9761](https://github.com/woocommerce/woocommerce-blocks/pull/9761))
- Update the banner pattern to replace the Unsplash image with a CCO licensed image from Pxhere. ([9760](https://github.com/woocommerce/woocommerce-blocks/pull/9760))
- Fix error on clearing customizations on Woo Templates that prevented WooCommerce Classic Template block from being displayed. ([9759](https://github.com/woocommerce/woocommerce-blocks/pull/9759))

= 10.4.0 - 2023-06-05 =

#### Enhancements

- Enable Single Product Block. ([9714](https://github.com/woocommerce/woocommerce-blocks/pull/9714))
- Product Price: Remove underline on sale price. ([9702](https://github.com/woocommerce/woocommerce-blocks/pull/9702))
- Blockified Single Product Template: Use h1 for the Product Title. ([9698](https://github.com/woocommerce/woocommerce-blocks/pull/9698))
- Blockified Single Product Template: Add product-classes. ([9697](https://github.com/woocommerce/woocommerce-blocks/pull/9697))
- Blockified Single Product Template: Show upsells. ([9636](https://github.com/woocommerce/woocommerce-blocks/pull/9636))
- Template Placeholder Design for the Order Received Template. ([9602](https://github.com/woocommerce/woocommerce-blocks/pull/9602))
- Mini-Cart: Update cart error notices to be non-dismissible (to match the behavior of the regular Cart). ([9578](https://github.com/woocommerce/woocommerce-blocks/pull/9578))
- Classic Product Template block visible in the inserter. ([9573](https://github.com/woocommerce/woocommerce-blocks/pull/9573))
- Hide the Product Rating when the product has no reviews. Previously, a link to the Reviews section was displayed. ([9556](https://github.com/woocommerce/woocommerce-blocks/pull/9556))
- Reduced the amount of settings data consumed by the cart and checkout blocks. ([9552](https://github.com/woocommerce/woocommerce-blocks/pull/9552))
- Enable blockified templates. ([9551](https://github.com/woocommerce/woocommerce-blocks/pull/9551))
- Product Categories List: Add "Show child categories only" toggle. ([9550](https://github.com/woocommerce/woocommerce-blocks/pull/9550))
- Add Product Rating block to the default Single Product template. ([9510](https://github.com/woocommerce/woocommerce-blocks/pull/9510))
- Add support for the Product Rating block within the Single Product template. ([9499](https://github.com/woocommerce/woocommerce-blocks/pull/9499))
- The Mini-Cart block is now compatible with caching plugins. ([9493](https://github.com/woocommerce/woocommerce-blocks/pull/9493))
- On-Sale Badge: Use background and text color of the button element if not defined in theme.json. ([9068](https://github.com/woocommerce/woocommerce-blocks/pull/9068))

#### Bug Fixes

- Fix aria-label displaying wrong information on mini-cart. ([9672](https://github.com/woocommerce/woocommerce-blocks/pull/9672))
- Allow plugin based template parts in templates. ([9667](https://github.com/woocommerce/woocommerce-blocks/pull/9667))
- Prevent horizontal shift when opening the Mini-Cart drawer if scrollbars are visible. ([9648](https://github.com/woocommerce/woocommerce-blocks/pull/9648))
- Fix text alignment issue on various widths for Just arrived full hero pattern. ([9640](https://github.com/woocommerce/woocommerce-blocks/pull/9640))
- Product Image Gallery Block: Reintroduce filters and override-restore the global product variable. ([9630](https://github.com/woocommerce/woocommerce-blocks/pull/9630))
- Remove propTypes definitions from Product List container. ([9616](https://github.com/woocommerce/woocommerce-blocks/pull/9616))
- Remove propTypes definitions from Add to Cart. ([9615](https://github.com/woocommerce/woocommerce-blocks/pull/9615))
- Revalidate the postcode when changing country in the Checkout form. ([9611](https://github.com/woocommerce/woocommerce-blocks/pull/9611))
- Fix an issue where changing country could cause shipping rates to constantly reload. ([9606](https://github.com/woocommerce/woocommerce-blocks/pull/9606))
- Fix non visible cart button on mini cart in certain themes. ([9603](https://github.com/woocommerce/woocommerce-blocks/pull/9603))
- Fix Mini-Cart badge not visible in themes without a `<body>` background color. ([9601](https://github.com/woocommerce/woocommerce-blocks/pull/9601))
- Fix Cambodian postcode validation. ([9597](https://github.com/woocommerce/woocommerce-blocks/pull/9597))
- Product Image Gallery block: Fix magnifying glass appearing outside the block content area. ([9594](https://github.com/woocommerce/woocommerce-blocks/pull/9594))
- Exclude already-enqueued scripts from scripts to lazy load in the Mini Cart block. ([9587](https://github.com/woocommerce/woocommerce-blocks/pull/9587))
- Fix: Ensure the global product variable is restored to its original value after being overridden within the Add to Cart with Options block. ([9581](https://github.com/woocommerce/woocommerce-blocks/pull/9581))
- Fix alignment of the close button in the Mini-Cart drawer when it has borders. ([9507](https://github.com/woocommerce/woocommerce-blocks/pull/9507))
- Add to Cart with Options Block: Remove global variable overwrite. ([9457](https://github.com/woocommerce/woocommerce-blocks/pull/9457))
- Fix total shipping display info when no shipping method is available. ([8819](https://github.com/woocommerce/woocommerce-blocks/pull/8819))
- Blockified Single Product Template: use h1 for the Product Title when transforming from Classic template. ([9748]((https://github.com/woocommerce/woocommerce-blocks/pull/9748)))

= 10.2.4 - 2023-06-22 =

#### Bug Fixes

- Fix filter blocks using the old markup not rendering and fix missing translations in those blocks. ([9954](https://github.com/woocommerce/woocommerce-blocks/pull/9954))

= 10.2.3 - 2023-06-09 =

#### Bug Fixes

- Update the Featured Category patterns to replace the Unsplash image with a CCO licensed image from Pxhere. ([9765](https://github.com/woocommerce/woocommerce-blocks/pull/9765))
- Update the Hero Product Split pattern to replace the Unsplash image with a CCO licensed image from Pxhere. ([9762](https://github.com/woocommerce/woocommerce-blocks/pull/9762))

= 10.2.2 - 2023-05-31 =

#### Bug Fixes

- Fix some scripts needed by the Mini-Cart block not loading. ([9649](https://github.com/woocommerce/woocommerce-blocks/pull/9649))

= 10.0.6 - 2023-05-30 =

#### Bug Fixes

- Fix some scripts needed by the Mini-Cart block not loading. ([9649](https://github.com/woocommerce/woocommerce-blocks/pull/9649))

= 10.2.1 - 2023-05-25 =

#### Bug Fixes

- Fix a conflict between the Mini-Cart block and the Page Optimize and Product Bundles extensions. ([9586](https://github.com/woocommerce/woocommerce-blocks/pull/9586))

= 10.0.5 - 2023-05-24 =

#### Bug Fixes

- Fix a conflict between the Mini-Cart block and the Page Optimize and Product Bundles extensions. ([9586](https://github.com/woocommerce/woocommerce-blocks/pull/9586))

= 10.3.0 - 2023-05-24 =

#### Enhancements

- Add Banner pattern. ([9434](https://github.com/woocommerce/woocommerce-blocks/pull/9434))
- Update Mini-Cart block editor sidebar with a clearer design and texts. ([9420](https://github.com/woocommerce/woocommerce-blocks/pull/9420))
- Add product details pattern. ([9408](https://github.com/woocommerce/woocommerce-blocks/pull/9408))
- Modernize the block registration for the Add to Cart button. ([9404](https://github.com/woocommerce/woocommerce-blocks/pull/9404))
- Fix alignment issue with 'Add Coupon Code' button in WooCommerce Blocks. ([9392](https://github.com/woocommerce/woocommerce-blocks/pull/9392))
- Add new `Product collections: Featured collections` pattern. ([9388](https://github.com/woocommerce/woocommerce-blocks/pull/9388))
- Classic Product Block: Improve layout and user experience to migrate to blockified templates. ([9386](https://github.com/woocommerce/woocommerce-blocks/pull/9386))
- Add the new Product Details: Product listing pattern. ([9383](https://github.com/woocommerce/woocommerce-blocks/pull/9383))
- Mini cart: Add color and typography styles on the mini cart title block. ([9382](https://github.com/woocommerce/woocommerce-blocks/pull/9382))
- Add new `Product listing with gallery` pattern. ([9347](https://github.com/woocommerce/woocommerce-blocks/pull/9347))
- New Mini Cart count badge which is only visible if there is at least one item in the cart. ([9259](https://github.com/woocommerce/woocommerce-blocks/pull/9259))
- [Pattern] Add new `Hero product chessboard` pattern. ([9180](https://github.com/woocommerce/woocommerce-blocks/pull/9180))
- Trigger the set-billing-address from the shipping step. ([8841](https://github.com/woocommerce/woocommerce-blocks/pull/8841))

#### Bug Fixes

- Fix an issue that caused the color of the Product Meta block icon to not change when the block was selected in the Site Editor's List View. ([9506](https://github.com/woocommerce/woocommerce-blocks/pull/9506))
- Fix an issue that caused the color of the Product Details block icon to not change when the block was selected in the Site Editor's List View. ([9503](https://github.com/woocommerce/woocommerce-blocks/pull/9503))
- Fix Checkout block stepped sections numbers overlapping. ([9497](https://github.com/woocommerce/woocommerce-blocks/pull/9497))
- Fix compatibility between the checkout block and the template editor. ([9495](https://github.com/woocommerce/woocommerce-blocks/pull/9495))
- Product Search Results Template: Fix compatibility layer when the Product Catalog isn't blockified. ([9492](https://github.com/woocommerce/woocommerce-blocks/pull/9492))
- Product Details block: Show the block when used along the Post Content block. ([9479](https://github.com/woocommerce/woocommerce-blocks/pull/9479))
- Fix: Remove the global variable overwrite for the Product Gallery block while still keeping support for the Single Product block. ([9475](https://github.com/woocommerce/woocommerce-blocks/pull/9475))
- Archive Templates - Compatibility Layer: Avoid woocommerce_before_shop_loop` and `woocommerce_after_shop_loop are injected when any products is rendered. ([9464](https://github.com/woocommerce/woocommerce-blocks/pull/9464))
- Allow Shipping method prices to inherit global styles text color. ([9456](https://github.com/woocommerce/woocommerce-blocks/pull/9456))
- Product Archive Template: Fix compatibility Layer with Product Recommendations extension. ([9452](https://github.com/woocommerce/woocommerce-blocks/pull/9452))
- Fixed appearance of checkout form step headings in the editor. ([9437](https://github.com/woocommerce/woocommerce-blocks/pull/9437))
- Global Styles: Fix radio control overlap when font-size is small. ([9436](https://github.com/woocommerce/woocommerce-blocks/pull/9436))
- Fix COD availability on checkout. ([9375](https://github.com/woocommerce/woocommerce-blocks/pull/9375))
- Addressed an issue where a fatal error could sometimes be thrown if a plugin modified a shipping package's rates incorrectly. ([9351](https://github.com/woocommerce/woocommerce-blocks/pull/9351))
- Mini Cart block no longer uses the Modal component from `@wordpress/components`. ([9345](https://github.com/woocommerce/woocommerce-blocks/pull/9345))
- Fix: ensure the global product variable is restored to its original value after being overridden within the Add to Cart with Options block. ([9581](https://github.com/woocommerce/woocommerce-blocks/pull/9581))

#### Performance

- Removed the block-editor dependency on the frontend. ([9251](https://github.com/woocommerce/woocommerce-blocks/pull/9251))

= 10.2.0 - 2023-05-08 =

#### Enhancements

- Add Shop by Price pattern. ([9373](https://github.com/woocommerce/woocommerce-blocks/pull/9373))
- Convert featured category triple pattern to use WP core blocks. ([9340](https://github.com/woocommerce/woocommerce-blocks/pull/9340))
- Add Featured Category pattern. ([9322](https://github.com/woocommerce/woocommerce-blocks/pull/9322))
- [Pattern] Add new `Hero product 3 split` pattern. ([9319](https://github.com/woocommerce/woocommerce-blocks/pull/9319))
- Add featured category cover image pattern. ([9284](https://github.com/woocommerce/woocommerce-blocks/pull/9284))
- Add featured category triple pattern. ([9269](https://github.com/woocommerce/woocommerce-blocks/pull/9269))
- Add new `Product Collections: Featured Collection` pattern. ([9246](https://github.com/woocommerce/woocommerce-blocks/pull/9246))
- Make Mini Cart footer border follow the current text color. ([9245](https://github.com/woocommerce/woocommerce-blocks/pull/9245))
- [Pattern] Add new `Product Collections: Newest Arrivals` pattern. ([9240](https://github.com/woocommerce/woocommerce-blocks/pull/9240))
- Add the new `WooCommerce Hero Product – Split` pattern. ([9191](https://github.com/woocommerce/woocommerce-blocks/pull/9191))
- Add product hero pattern. ([9181](https://github.com/woocommerce/woocommerce-blocks/pull/9181))
- Related Products: Make Product Title a link by default. ([9179](https://github.com/woocommerce/woocommerce-blocks/pull/9179))
- [Patterns] Add new `Footer with 3 menus` menu. ([9175](https://github.com/woocommerce/woocommerce-blocks/pull/9175))
- [Patterns]: Add new `Header Centered Search` pattern. ([9081](https://github.com/woocommerce/woocommerce-blocks/pull/9081))
- Add the new `WooCommerce Featured Products 5-item grid` pattern. ([9006](https://github.com/woocommerce/woocommerce-blocks/pull/9006))
- Products block: Using nested selectors to add consistent spacing between Product elements. ([8993](https://github.com/woocommerce/woocommerce-blocks/pull/8993))

#### Bug Fixes

- Fix: Ensure the product title within the Single Product Block matches the selected product instead of the title of the post/page where it was inserted. ([9335](https://github.com/woocommerce/woocommerce-blocks/pull/9335))
- Fix a regression which caused the Mini Cart drawer not to open until its contents have completely loaded. ([9329](https://github.com/woocommerce/woocommerce-blocks/pull/9329))
- Prevent email being cleared when changing shipping method or when first entering shipping information. ([9328](https://github.com/woocommerce/woocommerce-blocks/pull/9328))
- Product Image Gallery: Fix resize width when the window resizes. ([9299](https://github.com/woocommerce/woocommerce-blocks/pull/9299))
- Product Image Gallery: Fix the inline displayed issue. ([9297](https://github.com/woocommerce/woocommerce-blocks/pull/9297))
- Single Product Template - Related Products: Fix items per page option. ([9286](https://github.com/woocommerce/woocommerce-blocks/pull/9286))
- Fix shipping notices when cart contains multiple packages. ([9285](https://github.com/woocommerce/woocommerce-blocks/pull/9285))
- Prevent Product Price global styles leaking into the Mini Cart, Cart and Checkout blocks. ([9276](https://github.com/woocommerce/woocommerce-blocks/pull/9276))
- Fix consistency between editor and frontend button styling. ([9260](https://github.com/woocommerce/woocommerce-blocks/pull/9260))
- Fix Mini Cart block not respecting Add-to-Cart behaviour attribute when adding the first product. ([9257](https://github.com/woocommerce/woocommerce-blocks/pull/9257))
- Make it so wide widths are correctly applied to the Mini Cart Contents block in the editor. ([9201](https://github.com/woocommerce/woocommerce-blocks/pull/9201))
- Performance: Removed lodash dependency on the frontend. ([9161](https://github.com/woocommerce/woocommerce-blocks/pull/9161))

#### Various

- Update the icon and description for the Product Meta block. ([9243](https://github.com/woocommerce/woocommerce-blocks/pull/9243))
- Update the icon and description for the Product Details block. ([9241](https://github.com/woocommerce/woocommerce-blocks/pull/9241))
- Rename Add To Cart Form to Add To Cart Options. ([9238](https://github.com/woocommerce/woocommerce-blocks/pull/9238))


= 10.1.0 - 2023-04-24 =

#### Enhancements

- Add spacing around the Customer Account block to make it more easily clickable on the Editor page ([9009](https://github.com/woocommerce/woocommerce-blocks/pull/9009))
- Mini-cart: make the title customizable. ([8905](https://github.com/woocommerce/woocommerce-blocks/pull/8905))
- Mini-cart: allow customizing the drawer width. ([8930](https://github.com/woocommerce/woocommerce-blocks/pull/8930))
- StoreAPI: add new "/wc/store/v1/products/:slug" endpoint, allow searching products by slug with "/wc/store/v1/products?slug=product-slug" and return new field slug from the product endpoints. ([9017](https://github.com/woocommerce/woocommerce-blocks/pull/9017))
- Display Mini Cart overlay in template part editor to better reflect the frontend experience. ([9014](https://github.com/woocommerce/woocommerce-blocks/pull/9014))
- Product Stock indicator: Add support for background color, margin, padding & various typography controls ([8954](https://github.com/woocommerce/woocommerce-blocks/pull/8954))
- Products: Optimise loading in the Editor ([9021](https://github.com/woocommerce/woocommerce-blocks/pull/9021))
- useStoreProducts: Improve the logic of choosing the product from the outcome ([9079](https://github.com/woocommerce/woocommerce-blocks/pull/9079))
- Mini cart settings: Replace ToggleGroupControl with ToggleControl. ([9098](https://github.com/woocommerce/woocommerce-blocks/pull/9098))
- Checkout: Display special characters when they are part of a city name. ([9117](https://github.com/woocommerce/woocommerce-blocks/pull/9117))
- Cart: Use body background color as the Cart block sticky footer background color. ([9103](https://github.com/woocommerce/woocommerce-blocks/pull/9103))
- Checkout page: Add: pointer cursor to checkbox and radio input fields. ([9102](https://github.com/woocommerce/woocommerce-blocks/pull/9102))
- Minimal Header pattern: Update design ([9126](https://github.com/woocommerce/woocommerce-blocks/pull/9126))
- Add theme-level global styles to Cart and Checkout block. ([8809](https://github.com/woocommerce/woocommerce-blocks/pull/8809))
- Add new Testimonials 3 columns pattern. ([9159](https://github.com/woocommerce/woocommerce-blocks/pull/9159))
- Add new Featured Products 2 columns pattern. ([9072](https://github.com/woocommerce/woocommerce-blocks/pull/9072))


#### Bug Fixes

- Fix the product counts for the filter by attribute block now match the displayed products with different filter combinations, including filter by price, by rating, by stock, and by other attributes as well. ([8599](https://github.com/woocommerce/woocommerce-blocks/pull/8599))
- Give user customized Mini Cart template part priority over the template part from the theme. ([9005](https://github.com/woocommerce/woocommerce-blocks/pull/9005))
- Product Meta block: Block is not being displayed on the frontend. ([9032](https://github.com/woocommerce/woocommerce-blocks/pull/9032))
- Fix JS error visible in the editor caused by Mini Cart Contents buttons. ([9039](https://github.com/woocommerce/woocommerce-blocks/pull/9039))
- Remove excessive margin between cart items in the Mini Cart block. ([9051](https://github.com/woocommerce/woocommerce-blocks/pull/9051))
- Fix "Order received" page does not display the payment method information. ([9092](https://github.com/woocommerce/woocommerce-blocks/pull/9092))
- Match editor "Place Order" button UI with frontend. ([9094](https://github.com/woocommerce/woocommerce-blocks/pull/9094))
- Filter by Price block: Fix slider thumb position on wide screens. ([9118](https://github.com/woocommerce/woocommerce-blocks/pull/9118))
- Filter by Stock Status block: Fix dropdown ordering. ([9127](https://github.com/woocommerce/woocommerce-blocks/pull/9127))
- Disable "Hide shipping costs until an address is entered" option when Local Pickup is in use. ([8964](https://github.com/woocommerce/woocommerce-blocks/pull/8964))
- Mini Cart block: fix slide in animation. ([9195](https://github.com/woocommerce/woocommerce-blocks/pull/9195))
- Set minimum width for the Mini Cart Contents block. ([9196](https://github.com/woocommerce/woocommerce-blocks/pull/9196))

= 10.0.4 - 2023-05-04 =

#### Bug Fixes

- Fix "Edit Mini Cart template part" URL for the Mini Cart block. ([9348](https://github.com/woocommerce/woocommerce-blocks/pull/9348))
- Fix duplicate taxonomy templates being created on every save. ([9330](https://github.com/woocommerce/woocommerce-blocks/pull/9330))

= 10.0.3 - 2023-04-20 =

#### Bug Fixes

- Fix image editor in Featured Product/Category blocks on WP 6.2. ([9142](https://github.com/woocommerce/woocommerce-blocks/pull/9142))

= 10.0.2 - 2023-04-19 =

#### Bug Fixes

- Fix infinite loop with the registration of Single Product Block (and inner blocks) breaking WP Post and Page editors in WP 6.1. ([9090](https://github.com/woocommerce/woocommerce-blocks/pull/9090))

= 10.0.1 - 2023-04-18 =

#### Bug Fixes

- Product Price Block: Remove ProductSelector support. ([8980](https://github.com/woocommerce/woocommerce-blocks/pull/8980))
- Single Product Compatibility Layer: add support for custom HTML Blocks. ([9075](https://github.com/woocommerce/woocommerce-blocks/pull/9075))
- Fix: tax_query should be calculated only if __woocommerceAttributes is valid. ([9049](https://github.com/woocommerce/woocommerce-blocks/pull/9049))

= 10.0.0 - 2023-04-11 =

#### Enhancements

- Filter by Price: Simplify the interface by removing the decimals. ([8975](https://github.com/woocommerce/woocommerce-blocks/pull/8975))
- Moved editor-only placeholders to the block inspector to improve the appearance of the checkout block in the post editor. ([8957](https://github.com/woocommerce/woocommerce-blocks/pull/8957))
- Enhance Product SKU block styling capabilities with additional support for color, typography & spacing. ([8913](https://github.com/woocommerce/woocommerce-blocks/pull/8913))
- Enhance Product Price block styling capabilities with additional support for background color, line height, font family, letter spacing, and padding. ([8906](https://github.com/woocommerce/woocommerce-blocks/pull/8906))
- Enable users to migrate to the blockified Single Product template. ([8902](https://github.com/woocommerce/woocommerce-blocks/pull/8902))
- Set Cart and Checkout blocks to have the wide alignment by default when first added to a page. ([8899](https://github.com/woocommerce/woocommerce-blocks/pull/8899))
- Add padding support for `Product Image` block. ([8895](https://github.com/woocommerce/woocommerce-blocks/pull/8895))
- Added the “Products Review” block. ([8857](https://github.com/woocommerce/woocommerce-blocks/pull/8857))
- New styles for error, info, and success notices across notices created by blocks. Additionally, existing notices in core receive new styles when Cart and Checkout Blocks are in use. ([8659](https://github.com/woocommerce/woocommerce-blocks/pull/8659))
- Add `additionalCartCheckoutInnerBlockTypes` checkout filter to allow additional block types to be inserted into the Cart and Checkout blocks in the editor. ([8650](https://github.com/woocommerce/woocommerce-blocks/pull/8650))
- Product Rating: Normalize the height of rating icons and the add review link. ([8399](https://github.com/woocommerce/woocommerce-blocks/pull/8399))
- Show the collection address in the shipping section of the Checkout sidebar when using a Local Pickup method. ([8305](https://github.com/woocommerce/woocommerce-blocks/pull/8305))

#### Bug Fixes

- Prevent the shipping/billing address forms from breaking when entering postcodes for specific countries. ([8987](https://github.com/woocommerce/woocommerce-blocks/pull/8987))
- Product Archive Compatibility Layer: Fix duplicated content. ([8915](https://github.com/woocommerce/woocommerce-blocks/pull/8915))
- Product Archive compatibility layer: Fix the `woocommerce_after_shop_loop_item_title` hook positioning. ([8911](https://github.com/woocommerce/woocommerce-blocks/pull/8911))
- Fix image placeholder for the Product Image Gallery block that was not fitting inside its wrapper component. ([8901](https://github.com/woocommerce/woocommerce-blocks/pull/8901))
- Add Cart and Checkout blocks to the Style Book. ([8888](https://github.com/woocommerce/woocommerce-blocks/pull/8888))
- Fix duplicated "Shipping Method" Block on the front-end. ([8861](https://github.com/woocommerce/woocommerce-blocks/pull/8861))
- Replace the Notice component with the Skeleton component for the Add to Cart Form block. ([8842](https://github.com/woocommerce/woocommerce-blocks/pull/8842))

= 9.9.0 - 2023-03-30 =

#### Enhancements

- Move Related Products's notice component to the Inspector Control section. ([8843](https://github.com/woocommerce/woocommerce-blocks/pull/8843))
- Product SKU Block: Don't render the prefix when the SKU isn't defined. ([8837](https://github.com/woocommerce/woocommerce-blocks/pull/8837))
- Mini-cart: Add the option to change the style between 'Outline' and 'Fill' in all the buttons. ([8835](https://github.com/woocommerce/woocommerce-blocks/pull/8835))
- Product SKU: Make the block focusable in editor. ([8804](https://github.com/woocommerce/woocommerce-blocks/pull/8804))
- Add Fill & Outline styles, width settings & new typography controls for Product(Add to cart) button block. ([8781](https://github.com/woocommerce/woocommerce-blocks/pull/8781))
- Allow themes to remove the Mini Cart title on overridden template parts. ([8779](https://github.com/woocommerce/woocommerce-blocks/pull/8779))
- Add style to the `Mini Cart` buttons. ([8776](https://github.com/woocommerce/woocommerce-blocks/pull/8776))
- Add the ability to change the background and text colors of the Mini Cart block "Start shopping" button. ([8766](https://github.com/woocommerce/woocommerce-blocks/pull/8766))
- Mini-cart: Add setting to not render the block on the cart & checkout pages. ([8700](https://github.com/woocommerce/woocommerce-blocks/pull/8700))
- Ensure shipping rates do not show in the Checkout block if the "Hide shipping costs until an address is entered option is selected". ([8682](https://github.com/woocommerce/woocommerce-blocks/pull/8682))
- Move option to hide shipping costs until an address is entered to the Checkout block. ([8680](https://github.com/woocommerce/woocommerce-blocks/pull/8680))
- Remove certain Shipping settings from WooCommerce -> Settings -> Shipping -> Shipping Options when using the Cart or Checkout blocks, these have been moved to setting on the blocks. ([8679](https://github.com/woocommerce/woocommerce-blocks/pull/8679))
- Add spacing between Mini Cart title and products list when scrolled. ([8676](https://github.com/woocommerce/woocommerce-blocks/pull/8676))
- Add new ExperimentalOrderLocalPickupPackages Slot/Fill. ([8636](https://github.com/woocommerce/woocommerce-blocks/pull/8636))
- Add default Single Product HTML template. ([8515](https://github.com/woocommerce/woocommerce-blocks/pull/8515))
- Validate postcodes client-site instead of server-side. ([8503](https://github.com/woocommerce/woocommerce-blocks/pull/8503))
- Added support for `woocommerce_available_payment_gateways` to the Store API. ([8441](https://github.com/woocommerce/woocommerce-blocks/pull/8441))

#### Bug Fixes

- Fix border styles not visible in the editor in Featured Product and Featured Category blocks. ([8838](https://github.com/woocommerce/woocommerce-blocks/pull/8838))
- Add decoding to product names titles that are in HTML entities. ([8824](https://github.com/woocommerce/woocommerce-blocks/pull/8824))
- Fix react 18 error in the editor when using cart/checkout blocks. ([8820](https://github.com/woocommerce/woocommerce-blocks/pull/8820))
- Filter by Stock and Filter by Rating: Fix the potential endless redirection loop when used on a search results page. ([8784](https://github.com/woocommerce/woocommerce-blocks/pull/8784))
- Fix "Save changes" default behavior bug in the Firefox browser. ([8754](https://github.com/woocommerce/woocommerce-blocks/pull/8754))
- Store API - Apply `woocommerce_cart_item_permalink` filter to cart item permalinks. ([8726](https://github.com/woocommerce/woocommerce-blocks/pull/8726))
- Add validation error to prevent checkout when there is no shipping method available. ([8384](https://github.com/woocommerce/woocommerce-blocks/pull/8384))
- Fix Markdown table in payment-method-integration.md  > external contribution #8158. ([8258](https://github.com/woocommerce/woocommerce-blocks/pull/8258))

#### Various

- Display pickup location details in order confirmations. ([8727](https://github.com/woocommerce/woocommerce-blocks/pull/8727))
- Local Pickup: Merge country and state into same field in location modal. ([8408](https://github.com/woocommerce/woocommerce-blocks/pull/8408))
- Enable users to migrate to the blockified Single Product template. ([8324](https://github.com/woocommerce/woocommerce-blocks/pull/8324))

= 9.8.5 - 2023-04-21 =

#### Bug Fixes

- Fix image editor in Featured Product/Category blocks on WP 6.2. ([9142](https://github.com/woocommerce/woocommerce-blocks/pull/9142))

= 9.8.4 - 2023-03-29 =

#### Bug Fixes

- Fix unlinked border widths not being applied correctly in the frontend in WP 6.2 for some blocks. ([8893](https://github.com/woocommerce/woocommerce-blocks/pull/8893))

= 9.8.3 - 2023-03-28 =

#### Bug Fixes

- Fixed an issue where extensions were unable to programmatically set the shipping address during payment processing. ([8878](https://github.com/woocommerce/woocommerce-blocks/pull/8878))
- Fix border styles not visible in the editor in Featured Product and Featured Category blocks. ([8838](https://github.com/woocommerce/woocommerce-blocks/pull/8838))
- Fix Local Pickup "Save changes" default behavior bug in the Firefox browser. ([8754](https://github.com/woocommerce/woocommerce-blocks/pull/8754))

= 9.8.2 - 2023-03-22 =

#### Enhancements

- Display the link to add the shipping address when the shipping address is not available. ([8141](https://github.com/woocommerce/woocommerce-blocks/pull/8141))

#### Bug Fixes

- Fix Customer Account block doing a 404 request in the frontend. ([8798](https://github.com/woocommerce/woocommerce-blocks/pull/8798))
- Fix issue that prevented spaces being added to Mini Cart, Cart and Checkout buttons in Firefox. ([8777](https://github.com/woocommerce/woocommerce-blocks/pull/8777))

= 9.9.0 - 2023-03-27 =

#### Enhancements

- Move Related Products's notice component to the Inspector Control section. ([8843](https://github.com/woocommerce/woocommerce-blocks/pull/8843))
- Product SKU Block: Don't render the prefix when the SKU isn't defined. ([8837](https://github.com/woocommerce/woocommerce-blocks/pull/8837))
- Mini-cart: Add the option to change the style between 'Outline' and 'Fill' in all the buttons. ([8835](https://github.com/woocommerce/woocommerce-blocks/pull/8835))
- Product SKU: Make the block focusable in editor. ([8804](https://github.com/woocommerce/woocommerce-blocks/pull/8804))
- Add Fill & Outline styles, width settings & new typography controls for Product(Add to cart) button block. ([8781](https://github.com/woocommerce/woocommerce-blocks/pull/8781))
- Allow themes to remove the Mini Cart title on overridden template parts. ([8779](https://github.com/woocommerce/woocommerce-blocks/pull/8779))
- Add style to the `Mini Cart` buttons. ([8776](https://github.com/woocommerce/woocommerce-blocks/pull/8776))
- Add the ability to change the background and text colors of the Mini Cart block "Start shopping" button. ([8766](https://github.com/woocommerce/woocommerce-blocks/pull/8766))
- Mini-cart: Add setting to not render the block on the cart & checkout pages. ([8700](https://github.com/woocommerce/woocommerce-blocks/pull/8700))
- Ensure shipping rates do not show in the Checkout block if the "Hide shipping costs until an address is entered option is selected". ([8682](https://github.com/woocommerce/woocommerce-blocks/pull/8682))
- Move option to hide shipping costs until an address is entered to the Checkout block. ([8680](https://github.com/woocommerce/woocommerce-blocks/pull/8680))
- Remove certain Shipping settings from WooCommerce -> Settings -> Shipping -> Shipping Options when using the Cart or Checkout blocks, these have been moved to setting on the blocks. ([8679](https://github.com/woocommerce/woocommerce-blocks/pull/8679))
- Add spacing between Mini Cart title and products list when scrolled. ([8676](https://github.com/woocommerce/woocommerce-blocks/pull/8676))
- Add new ExperimentalOrderLocalPickupPackages Slot/Fill. ([8636](https://github.com/woocommerce/woocommerce-blocks/pull/8636))
- Add default Single Product HTML template. ([8515](https://github.com/woocommerce/woocommerce-blocks/pull/8515))
- Validate postcodes client-site instead of server-side. ([8503](https://github.com/woocommerce/woocommerce-blocks/pull/8503))
- Added support for `woocommerce_available_payment_gateways` to the Store API. ([8441](https://github.com/woocommerce/woocommerce-blocks/pull/8441))
- Display pickup location details in order confirmations. ([8727](https://github.com/woocommerce/woocommerce-blocks/pull/8727))
- Local Pickup: Merge country and state into same field in location modal. ([8408](https://github.com/woocommerce/woocommerce-blocks/pull/8408))
- Enable users to migrate to the blockified Single Product template. ([8324](https://github.com/woocommerce/woocommerce-blocks/pull/8324))

#### Bug Fixes

- Fix border styles not visible in the editor in Featured Product and Featured Category blocks. ([8838](https://github.com/woocommerce/woocommerce-blocks/pull/8838))
- Add decoding to product names titles that are in HTML entities. ([8824](https://github.com/woocommerce/woocommerce-blocks/pull/8824))
- Fix react 18 error in the editor when using cart/checkout blocks. ([8820](https://github.com/woocommerce/woocommerce-blocks/pull/8820))
- Filter by Stock and Filter by Rating: Fix the potential endless redirection loop when used on a search results page. ([8784](https://github.com/woocommerce/woocommerce-blocks/pull/8784))
- Fix "Save changes" default behavior bug in the Firefox browser. ([8754](https://github.com/woocommerce/woocommerce-blocks/pull/8754))
- Store API - Apply `woocommerce_cart_item_permalink` filter to cart item permalinks. ([8726](https://github.com/woocommerce/woocommerce-blocks/pull/8726))
- Add validation error to prevent checkout when there is no shipping method available. ([8384](https://github.com/woocommerce/woocommerce-blocks/pull/8384))

= 9.8.1 - 2023-03-15 =

#### Bug Fixes

- Fix Single Product page not visible in block themes that provided a custom template. ([8758](https://github.com/woocommerce/woocommerce-blocks/pull/8758))
- Products by Attributes: Fix the block rendered empty in the Editor. ([8759](https://github.com/woocommerce/woocommerce-blocks/pull/8759))
- Fix the local pickup price in the shipping type selector and pickup options. ([8623](https://github.com/woocommerce/woocommerce-blocks/pull/8623))
- Enable users to migrate to the blockified Single Product template.([8902](https://github.com/woocommerce/woocommerce-blocks/pull/8902))
- Fixed an issue where extensions were unable to programmatically set the shipping address during payment processing. ([8878](https://github.com/woocommerce/woocommerce-blocks/pull/8878))
- Fix unlinked border widths not being applied correctly in the frontend in WP 6.2 for some blocks. ([8893](https://github.com/woocommerce/woocommerce-blocks/pull/8893))

= 9.8.0 - 2023-03-14 =

#### Enhancements

- Add `Product Meta` block. [8484](https://github.com/woocommerce/woocommerce-blocks/pull/8484)
- Add `proceedToCheckoutButtonLabel` and `proceedToCheckoutButtonLink` filters and delete cached filters when registering new ones. [8613](https://github.com/woocommerce/woocommerce-blocks/pull/8613)
- Add border style controls to the Mini Cart block. [8654](https://github.com/woocommerce/woocommerce-blocks/pull/8654)
- Add support to non-AJAX add to cart in the Products block. [8532](https://github.com/woocommerce/woocommerce-blocks/pull/8532)
- Fix mini-cart `Start shopping` button to follow the current theme styles. [8567](https://github.com/woocommerce/woocommerce-blocks/pull/8567)
- Fix: change the "Add-to-Cart behaviour" mini-cart select to a toggle button. [8558](https://github.com/woocommerce/woocommerce-blocks/pull/8558)
- Fix: enable global styles for the `Product Result Count` block. [8517](https://github.com/woocommerce/woocommerce-blocks/pull/8517)
- Fix: increase the "Customer account" size icon. [8594](https://github.com/woocommerce/woocommerce-blocks/pull/8594)
- Improve Mini Cart drawer close button alignment and make it inherit the text color [8605](https://github.com/woocommerce/woocommerce-blocks/pull/8605)
- Made the cart and checkout view switcher compatible with List View in the editor. [8429](https://github.com/woocommerce/woocommerce-blocks/pull/8429)
- Preload Mini Cart inner blocks frontend scripts to improve performance [8653](https://github.com/woocommerce/woocommerce-blocks/pull/8653)
- Products block: improved UX and UI for the Product Attributes advanced filter. [8583](https://github.com/woocommerce/woocommerce-blocks/pull/8583)
- Products: Improve spacing consistency of product elements in patterns. [8401](https://github.com/woocommerce/woocommerce-blocks/pull/8401)
- Reduce the number of scripts needed to render a page containing the Mini Cart block [8657](https://github.com/woocommerce/woocommerce-blocks/pull/8657) [8703](https://github.com/woocommerce/woocommerce-blocks/pull/8703)

#### Bug Fixes

- Fix 'Edit Mini Cart template part' link in WP 6.2 [8574](https://github.com/woocommerce/woocommerce-blocks/pull/8574)
- Fix Mini Cart block having some translations missing in the editor. [8591](https://github.com/woocommerce/woocommerce-blocks/pull/8591)
- Fix Mini Cart block inserter preview not showing the cart amount. [8516](https://github.com/woocommerce/woocommerce-blocks/pull/8516)
- Fix: remove unnecessary class from the Mini-cart badge. [8557](https://github.com/woocommerce/woocommerce-blocks/pull/8557)
- Fixed a bug where quickly clicking through shipping methods would cause UI to update multiple times after the final selection is made. [8498](https://github.com/woocommerce/woocommerce-blocks/pull/8498)
- Products block: Fix attributes filters that do not update the editor preview correctly [8611](https://github.com/woocommerce/woocommerce-blocks/pull/8611)
- Revert "Show Cart and Checkout blocks in Style Book" [8602](https://github.com/woocommerce/woocommerce-blocks/pull/8602)

#### Technical debt

- Change the required minimum version from 6.1.1 to 6.1.0. [8649](https://github.com/woocommerce/woocommerce-blocks/pull/8649)
- Only call wp.apiFetch.createPreloadingMiddleware() when necessary. [8647](https://github.com/woocommerce/woocommerce-blocks/pull/8647)

= 9.7.1 - 2023-03-03 =

#### Bug Fixes

- Fix: Show up to three Express Payments buttons next to each other. ([8601](https://github.com/woocommerce/woocommerce-blocks/pull/8601))

= 9.7.0 - 2023-02-28 =

#### Enhancements

- Add Related Products block. ([8522](https://github.com/woocommerce/woocommerce-blocks/pull/8522))
- Products block: Set the Product Title as a link by default. ([8519](https://github.com/woocommerce/woocommerce-blocks/pull/8519))
- Add support for the `woocommerce_loop_add_to_cart_args` filter in the Products block. ([8422](https://github.com/woocommerce/woocommerce-blocks/pull/8422))
- Enable the `Inherit query from template` setting by default for the _Products_ block in archive products templates. ([8375](https://github.com/woocommerce/woocommerce-blocks/pull/8375))
- Update the blockified archive templates to use the Products block. ([8308](https://github.com/woocommerce/woocommerce-blocks/pull/8308))
- Improve the dismissal behavior of the incompatible gateways notice. ([8299](https://github.com/woocommerce/woocommerce-blocks/pull/8299))
- Add the _Add to Cart Form_ block, allowing merchants to display a button so the customer can add a product to their cart. Options will also be displayed depending on product type. e.g. quantity, variation. ([8284](https://github.com/woocommerce/woocommerce-blocks/pull/8284))
- All Products: Add alignment settings for the Product Rating and Product Price blocks. ([8264](https://github.com/woocommerce/woocommerce-blocks/pull/8264))
- Enable users to migrate to blockified Product Archive templates. ([8248](https://github.com/woocommerce/woocommerce-blocks/pull/8248))
- Add Product Image Gallery block. ([8235](https://github.com/woocommerce/woocommerce-blocks/pull/8235))
- Add Single Product Details block that displays the product description, information, and reviews. ([8225](https://github.com/woocommerce/woocommerce-blocks/pull/8225))

#### Bug Fixes

- Add the `woocommerce_disable_compatibility_layer` filter to disable the compatibility layer. Disable the compatibility layer when Archive Product and Single Product templates contain the WooCommerce Product Grid Block. ([8550](https://github.com/woocommerce/woocommerce-blocks/pull/8550))
- Ensure custom shipping methods supporting local pickup show up in the shipping options when no WC Blocks pickup locations are enabled. ([8542](https://github.com/woocommerce/woocommerce-blocks/pull/8542))
- Focus the coupon code input when the form is revealed in the cart. ([8525](https://github.com/woocommerce/woocommerce-blocks/pull/8525))
- Fix: Make `Price product` margin work in the `All products` block. ([8518](https://github.com/woocommerce/woocommerce-blocks/pull/8518))
- Fix an issue in which setting the city/state would not persist in the first time for certain customers. ([8497](https://github.com/woocommerce/woocommerce-blocks/pull/8497))
- Fix noticeContext declaration in the Shipping calculator. ([8495](https://github.com/woocommerce/woocommerce-blocks/pull/8495))
- Update Product Details block so it inherits more styles from the theme. ([8494](https://github.com/woocommerce/woocommerce-blocks/pull/8494))
- Add Cart and Checkout blocks to the Style Book. ([8489](https://github.com/woocommerce/woocommerce-blocks/pull/8489))
- Fix: Adjust `Catalog Sorting` colors in dark themes. ([8483](https://github.com/woocommerce/woocommerce-blocks/pull/8483))
- Remove opinionated styles from Button component on block themes that define button styles. ([8478](https://github.com/woocommerce/woocommerce-blocks/pull/8478))
- Fix individual border controls not showing in the editor for Featured Product and Featured Category blocks. ([8472](https://github.com/woocommerce/woocommerce-blocks/pull/8472))
- Fix potential console warnings when certain Checkout Blocks are disabled. ([8471](https://github.com/woocommerce/woocommerce-blocks/pull/8471))
- Prevent saved cards from appearing that belong to gateways that are not enabled. ([8461](https://github.com/woocommerce/woocommerce-blocks/pull/8461))
- Fix error: "Undefined property $area" error on the BlockTemplatesController. ([8443](https://github.com/woocommerce/woocommerce-blocks/pull/8443))
- Fixed an issue where warnings relating to payment method script dependencies were shown when editing pages with Elementor. ([8428](https://github.com/woocommerce/woocommerce-blocks/pull/8428))
- Performance - Prevent extra API hydration in the editor when using All Products block. ([8413](https://github.com/woocommerce/woocommerce-blocks/pull/8413))
- Product Price: Fix typography styles in the editor. ([8398](https://github.com/woocommerce/woocommerce-blocks/pull/8398))
- Fix spacing and display issues for Store Breadcrumbs, Catalog Sorting and Product Result Counts blocks. ([8391](https://github.com/woocommerce/woocommerce-blocks/pull/8391))
- Fix Product categories, Product Tags & Keyword filter not working in Products block. ([8377](https://github.com/woocommerce/woocommerce-blocks/pull/8377))

#### Technical debt

- Rename the checkout events. ([8381](https://github.com/woocommerce/woocommerce-blocks/pull/8381))

= 9.6.6 - 2023-03-17 =

#### Bug Fixes

- Product Image Gallery: fix 404 error. ([8445](https://github.com/woocommerce/woocommerce-blocks/pull/8445))

= 9.6.5 - 2023-03-06 =

#### Bug Fixes

- Checkout: Fix state validation after changing shipping country. ([8633](https://github.com/woocommerce/woocommerce-blocks/pull/8633)

= 9.6.4 - 2023-03-03 =

#### Bug Fixes

- Fix: Show up to three Express Payments buttons next to each other. ([8601](https://github.com/woocommerce/woocommerce-blocks/pull/8601))

= 9.6.3 - 2023-02-27 =

#### Bug Fixes

- Fix: Ensure that Express Payment buttons are visible next to each other. ([8548](https://github.com/woocommerce/woocommerce-blocks/pull/8548))
- Check if session is set before returning updated customer address. ([8537](https://github.com/woocommerce/woocommerce-blocks/pull/8537))
- Fix the Checkout Blocks "Payment Options" settings crash in the editor. ([8535](https://github.com/woocommerce/woocommerce-blocks/pull/8535))

= 9.6.2 - 2023-02-22 =

#### Bug Fixes

- Disable compatibility layer ([8507](https://github.com/woocommerce/woocommerce-blocks/pull/8507))

= 9.6.1 - 2023-02-17 =

#### Bug Fixes

- Make Mini Cart Contents block visible in the Style Book. ([8458](https://github.com/woocommerce/woocommerce-blocks/pull/8458))
- Fixed an issue where cart item data could cause fatal errors if it was an array. ([8440](https://github.com/woocommerce/woocommerce-blocks/pull/8440))
- Fix Customer account sidebar link incorrect margin in WP 6.2. ([8437](https://github.com/woocommerce/woocommerce-blocks/pull/8437))
- Fix cases in which Checkout would validate customer country against the wrong state. ([8460](https://github.com/woocommerce/woocommerce-blocks/pull/8460))

= 9.6.0 - 2023-02-14 =

#### Enhancements

- Improved default headings and styling of the cart block and fixed the display of cart and checkout block editable fields when using dark themes. ([8380](https://github.com/woocommerce/woocommerce-blocks/pull/8380))
- Add a reset button for the Filter blocks. ([8366](https://github.com/woocommerce/woocommerce-blocks/pull/8366))
- Update the incompatible gateways notice design. ([8365](https://github.com/woocommerce/woocommerce-blocks/pull/8365))
- Product Rating: Add support for the Padding setting. ([8347](https://github.com/woocommerce/woocommerce-blocks/pull/8347))
- Update apply button description to be more clear for filter blocks. ([8339](https://github.com/woocommerce/woocommerce-blocks/pull/8339))
- Allow third party shipping methods to declare compatibility with WC Blocks local pickup. ([8256](https://github.com/woocommerce/woocommerce-blocks/pull/8256))

#### Bug Fixes

- Fix a bug where certain checkout fields were being reset when changing the shipping option. ([8400](https://github.com/woocommerce/woocommerce-blocks/pull/8400))
- Fix bug in which errors would be shown twice in Checkout block. ([8390](https://github.com/woocommerce/woocommerce-blocks/pull/8390))
- Filter by Rating: Fix functionality to for resetting filters using the Reset button. ([8374](https://github.com/woocommerce/woocommerce-blocks/pull/8374))
- Fix a bug in WordPress 5.9 in which changing quantity doesn't work inside Cart and Mini Cart blocks. ([8356](https://github.com/woocommerce/woocommerce-blocks/pull/8356))
- Fix potential conflict between newsletter extensions on the checkout page. ([8354](https://github.com/woocommerce/woocommerce-blocks/pull/8354))
- Mini Cart block: Fix the drawer content height to allow the checkout button to be visible. ([8351](https://github.com/woocommerce/woocommerce-blocks/pull/8351))
- Prevent errors relating to the coupon input disappearing when focusing/blurring the coupon input and the value of the input field remains unchanged. ([8349](https://github.com/woocommerce/woocommerce-blocks/pull/8349))
- Fix: The experimental typography styles for the Store Breadcrumbs block are now restricted to the feature plugin. ([8345](https://github.com/woocommerce/woocommerce-blocks/pull/8345))
- Fix console error of `isLoading` for Price filter block. ([8340](https://github.com/woocommerce/woocommerce-blocks/pull/8340))
- Checkout - Allow partial pushes of address data to work before a country is provided ([8425](https://github.com/woocommerce/woocommerce-blocks/pull/8425))

= 9.5.0 - 2023-01-30 =

#### Enhancements

- Enhancement: Add _Store Breadcrumbs_ block, allowing merchants to keep track of their locations within the store and navigate back to parent pages. ([8222](https://github.com/woocommerce/woocommerce-blocks/pull/8222))
- Enhancement: Add _Catalog Sorting_ block. ([8122](https://github.com/woocommerce/woocommerce-blocks/pull/8122))
- Enhancement: Add _Product Results Count_ block. ([8078](https://github.com/woocommerce/woocommerce-blocks/pull/8078))
- Enhancement: Add a `reset` button for the _Filter by Attributes_ block. ([8285](https://github.com/woocommerce/woocommerce-blocks/pull/8285))
- Enhancement: Add a compatibility layer to keep extensions continue working with Blockified Archive Templates. ([8172](https://github.com/woocommerce/woocommerce-blocks/pull/8172))
- Enhancement: Add border style previews in the editor for featured items. ([8304](https://github.com/woocommerce/woocommerce-blocks/pull/8304))
- Enhancement: Graduate margin styling for _Product Price block_ to WooCommerce core. ([8269](https://github.com/woocommerce/woocommerce-blocks/pull/8269))
- Enhancement: Improve free local pickup display during checkout. ([8241](https://github.com/woocommerce/woocommerce-blocks/pull/8241))
- Enhancement: Improve how checkout pushes data to the server when entering address data. ([8030](https://github.com/woocommerce/woocommerce-blocks/pull/8030))
- Enhancement: Move margin for _Product Rating_ from CSS to Global Styles. ([8202](https://github.com/woocommerce/woocommerce-blocks/pull/8202))
- Enhancement: Prevent an edge case where adding the _Product_ blocks above the Classic Template block would cause its ratings to change the markup. ([8247](https://github.com/woocommerce/woocommerce-blocks/pull/8247))
- Enhancement: Refresh the cart after using the back button to return to checkout. ([8236](https://github.com/woocommerce/woocommerce-blocks/pull/8236))
- Enhancement: Replace the collapsed section for the coupon code with a link. ([7993](https://github.com/woocommerce/woocommerce-blocks/pull/7993))
- Enhancement: Set `inherit` setting to true when is inserted in the archive product template. ([8251](https://github.com/woocommerce/woocommerce-blocks/pull/8251))
- Enhancement: Transition _Product Button_ from using CSS margin to Global Styles. ([8239](https://github.com/woocommerce/woocommerce-blocks/pull/8239))

#### Bug Fixes

- Fix: Adjust _Featured Product_ and _Featured Category_ blocks preview for Style Book. ([8313](https://github.com/woocommerce/woocommerce-blocks/pull/8313))
- Fix: Adjust _Store Notices_ text color in dark themes. ([8278](https://github.com/woocommerce/woocommerce-blocks/pull/8278))
- Fix: Adjust _Catalog Sorting_ colors in dark themes. ([8270](https://github.com/woocommerce/woocommerce-blocks/pull/8270))
- Fix: Adjust color and direction of the arrow of the return to cart button on the checkout page. ([8289](https://github.com/woocommerce/woocommerce-blocks/pull/8289))
- Fix: Hide filter blocks and _Product Search_ block for Style Book. ([8309](https://github.com/woocommerce/woocommerce-blocks/pull/8309))
- Fix: Resolve a bug that would display Billing Address for Shipping Address on checkout rest endpoint. ([8291](https://github.com/woocommerce/woocommerce-blocks/pull/8291))
- Fix: Resolve an issue where the WooCommerce tab of the style book would crash and certain blocks would not load correctly. ([8243](https://github.com/woocommerce/woocommerce-blocks/pull/8243))

= 9.4.4 - 2023-02-27 =

#### Bug Fixes

- Check if session is set before returning updated customer address. ([8537](https://github.com/woocommerce/woocommerce-blocks/pull/8537))

= 9.4.3 - 2023-02-01 =

#### Bug Fixes

- Fix a bug in WordPress 5.9 in which changing quantity doesn't work inside Cart and Mini Cart blocks. ([8297](https://github.com/woocommerce/woocommerce-blocks/pull/8356))
- Mini Cart block: Fix the drawer content height to allow the checkout button to be visible. ([8297](https://github.com/woocommerce/woocommerce-blocks/pull/8351))

= 9.4.2 - 2023-01-26 =

#### Bug Fixes

Product Elements: remove feature plugin flag from Product Title, Product Summary and Product Template block. ([8297](https://github.com/woocommerce/woocommerce-blocks/pull/8297))

= 9.4.1 - 2023-01-23 =

#### Bug Fixes

Prevent Cart and Checkout notices from disappearing immediately after adding. ([8253](https://github.com/woocommerce/woocommerce-blocks/pull/8253))

= 9.4.0 - 2023-01-16 =

#### Enhancements

- Product Elements: Change the color of product elements (variations of core blocks) icon blocks to match the color of the core blocks. ([8155](https://github.com/woocommerce/woocommerce-blocks/pull/8155))
- Added context for aria-label on cart quantity controls. ([8099](https://github.com/woocommerce/woocommerce-blocks/pull/8099))
- Add Local Pickup shipping method and its blocks. ([7433](https://github.com/woocommerce/woocommerce-blocks/pull/7433))

#### Bug Fixes

- Prevent undefined variable notice on checkout. ([8197](https://github.com/woocommerce/woocommerce-blocks/pull/8197))
- Allow Slot/Fills in the Cart and Checkout blocks to correctly render in the Block Editor. ([8111](https://github.com/woocommerce/woocommerce-blocks/pull/8111))
- Attribute, Rating and Stock filters: Dropdown indicator icon display fix. ([8080](https://github.com/woocommerce/woocommerce-blocks/pull/8080))
- Ensure the filter controls are consistently displayed in the editor. ([8079](https://github.com/woocommerce/woocommerce-blocks/pull/8079))
- Ensure the Checkout Totals and Checkout Order Summary blocks cannot be removed from the Checkout block. ([7873](https://github.com/woocommerce/woocommerce-blocks/pull/7873))
- Correctly detect compatible express payment methods. ([8201](https://github.com/woocommerce/woocommerce-blocks/pull/8201))

#### Documentation

- Add Customer Account block name to readme.txt. ([8114](https://github.com/woocommerce/woocommerce-blocks/pull/8114))

#### Technical debt

- Update validation messages to reference the name of the invalid field. ([8143](https://github.com/woocommerce/woocommerce-blocks/pull/8143))

#### Compatibility

- Update minimum PHP required version to 7.2. ([8154](https://github.com/woocommerce/woocommerce-blocks/pull/8154))

= 9.3.0 - 2023-01-02 =

#### Enhancements

- The filter by attribute, price, rating, and stock blocks are not reloaded when selected in the editor anymore. ([8002](https://github.com/woocommerce/woocommerce-blocks/pull/8002))
- Products and All Products: Display "Add review" link when there's no product rating. ([7929](https://github.com/woocommerce/woocommerce-blocks/pull/7929))
- Product Query: Create variation of `core/post-template` as a Product Query inner block. ([7838](https://github.com/woocommerce/woocommerce-blocks/pull/7838))
- Mini Cart block: Added notice support. ([7234](https://github.com/woocommerce/woocommerce-blocks/pull/7234))

#### Bug Fixes

- Don't check for validation on pushChange. ([8029](https://github.com/woocommerce/woocommerce-blocks/pull/8029))
- Fix: Ensure that the Checkout Order Summary block is showing of WooCommerce Blocks instead the WooCommerce core translations. ([7995](https://github.com/woocommerce/woocommerce-blocks/pull/7995))
- Update Mini Cart, Cart and Checkout button styles so they follow theme styles in Twenty Twenty Three and Zaino themes. ([7992](https://github.com/woocommerce/woocommerce-blocks/pull/7992))
- Disable Rate Limiting when editing Blocks in admin. ([7934](https://github.com/woocommerce/woocommerce-blocks/pull/7934))

#### Various

- Show cart view switcher when inner blocks are selected. ([8006](https://github.com/woocommerce/woocommerce-blocks/pull/8006))
- Cart Block: Fixed the console error displayed when an invalid coupon was added to the cart. ([7969](https://github.com/woocommerce/woocommerce-blocks/pull/7969))
- Add "Customer Account" block to header and footer patterns. ([7944](https://github.com/woocommerce/woocommerce-blocks/pull/7944))
- Add the new `Customer Account` block. ([7876](https://github.com/woocommerce/woocommerce-blocks/pull/7876))
- Highlight incompatible payment gateways in the Cart & Checkout Blocks. ([7412](https://github.com/woocommerce/woocommerce-blocks/pull/7412))

= 9.2.0 - 2022-12-19 =

#### Enhancements

- Product Query: Add Product Visibility support. ([7951](https://github.com/woocommerce/woocommerce-blocks/pull/7951))
- Remove account creation setting from Checkout block. ([7941](https://github.com/woocommerce/woocommerce-blocks/pull/7941))
- Enable merchants to adjust the label of the `Place Order` button according to their needs. ([7843](https://github.com/woocommerce/woocommerce-blocks/pull/7843))
- Enable merchants to edit the button labels within the Mini Cart block. ([7817](https://github.com/woocommerce/woocommerce-blocks/pull/7817))
- Fix Mini Cart icon color in Global Styles blocks customizer. ([7762](https://github.com/woocommerce/woocommerce-blocks/pull/7762))
- Enable merchants to adjust the label of the Place Order button according to their needs. ([7733](https://github.com/woocommerce/woocommerce-blocks/pull/7733))

#### Bug Fixes

- Fix bug that was overriding the  `archive-product` when saving a fallback template. ([7975](https://github.com/woocommerce/woocommerce-blocks/pull/7975))
- Fix: Add non-ASCII terms support to Filter by Attribute block. ([7906](https://github.com/woocommerce/woocommerce-blocks/pull/7906))
- Fix: Ensure that the Checkout block respects the WooCommerce core settings for guest checkout and account creation. ([7883](https://github.com/woocommerce/woocommerce-blocks/pull/7883))
- A notice is now displayed in the editor whenever the Filter by Rating block is used in a store that has no products with ratings. Additionally, users can now preview/update the content, settings, and color for the filter even when the store doesn't have any ratings yet. ([7763](https://github.com/woocommerce/woocommerce-blocks/pull/7763))
- Prevent invalid data being pushed to the server when validating fields on the checkout. ([7755](https://github.com/woocommerce/woocommerce-blocks/pull/7755))

#### Documentation

- Added documentation for the selectors of the cart data store. ([7708](https://github.com/woocommerce/woocommerce-blocks/pull/7708))

#### Technical debt

- Show notices to the shopper if an item in the cart's quantity is updated automatically. ([7938](https://github.com/woocommerce/woocommerce-blocks/pull/7938))

#### Various

- Remove hidden autocomplete fields in checkout. ([7953](https://github.com/woocommerce/woocommerce-blocks/pull/7953))

= 9.1.3 - 2022-12-21 =

#### Enhancements

- Enable Product SKU and Product Stock Indicator in Core ([8009](https://github.com/woocommerce/woocommerce-blocks/pull/8009))

= 9.1.2 - 2022-12-21 =

#### Enhancements

- Enable Products block into WC core. ([8001](https://github.com/woocommerce/woocommerce-blocks/pull/8001))

= 9.1.1 - 2022-12-14 =

#### Enhancements

- Products block: Add patterns. ([7857](https://github.com/woocommerce/woocommerce-blocks/pull/7857))
- Filter by Stock: Add dropdown version.
- Filter by Stock: Add option to choose multiple or single option. ([7831](https://github.com/woocommerce/woocommerce-blocks/pull/7831))
- Filter by Rating: Add dropdown version.
- Filter by Rating: Add option to choose multiple or single option.
- Filters: Fix the little gaps on the border corners in the filters Dropdown version. ([7771](https://github.com/woocommerce/woocommerce-blocks/pull/7771))
- Product Query - Enable "Inherit Query from template" option. ([7641](https://github.com/woocommerce/woocommerce-blocks/pull/7641))

#### Bug Fixes

- Classic Products Template and Products: Improve the layout of the Rating. ([7932](https://github.com/woocommerce/woocommerce-blocks/pull/7932))
- Product Elements: Fix misc block settings for Product Button, Price and Rating. ([7914](https://github.com/woocommerce/woocommerce-blocks/pull/7914))
- Fix Padding, Margin, Border Width, and Radius settings for all relevant blocks. ([7909](https://github.com/woocommerce/woocommerce-blocks/pull/7909))
- Fix wrong Mini Cart amount displayed when displaying prices including taxes. ([7832](https://github.com/woocommerce/woocommerce-blocks/pull/7832))
- Product Query: Add Sorted by title preset to Popular Filters. ([7949])(https://github.com/woocommerce/woocommerce-blocks/pull/7949))

= 9.1.0 - 2022-12-06 =

#### Enhancements

- The "WooCommerce Alternating Image and Text" block pattern has been added. This design can be used by adding two attention-grabbing images, customizing the text to match your needs and customizing the button block to be linked to any page. ([7827](https://github.com/woocommerce/woocommerce-blocks/pull/7827))
- Add to Cart Button: Add support for the alignment setting. ([7816](https://github.com/woocommerce/woocommerce-blocks/pull/7816))
- Products block: Make the block available to the users of the feature plugin. ([7815](https://github.com/woocommerce/woocommerce-blocks/pull/7815))
- The "WooCommerce Product Hero 2 Column 2 Row" block pattern has been added. It is a straightforward hero design that can be used by adding two attention-grabbing images, some custom product description text and customizing the button block to be linked to any page. ([7814](https://github.com/woocommerce/woocommerce-blocks/pull/7814))
- The "Just Arrived Full Hero" block pattern has been added. It is a straightforward hero design that can be used with an attention-grabber image some simple callout text, and a button block that can be linked to any page. ([7812](https://github.com/woocommerce/woocommerce-blocks/pull/7812))
- Product Rating: Add support for the alignment setting. ([7790](https://github.com/woocommerce/woocommerce-blocks/pull/7790))
- Rename Active Product Filters block to Active Filters. ([7753](https://github.com/woocommerce/woocommerce-blocks/pull/7753))
- Product Query: Add support for filtering by attributes within the block. ([7743](https://github.com/woocommerce/woocommerce-blocks/pull/7743))
- Align the font-sizes in filters. ([7707](https://github.com/woocommerce/woocommerce-blocks/pull/7707))

#### Bug Fixes

- Mini Cart block: Load `wc-blocks-registry` package at the page's load instead of lazy load it. ([7813](https://github.com/woocommerce/woocommerce-blocks/pull/7813))
- Hide the shipping address form from Checkout Block in Editor and rename the Billing Address label when "Force shipping to the customer billing address" is enabled. ([7800](https://github.com/woocommerce/woocommerce-blocks/pull/7800))
- Product Price: Fix the alignment setting. ([7795](https://github.com/woocommerce/woocommerce-blocks/pull/7795))
- Fix: Show tax label in Cart and Checkout block. ([7785](https://github.com/woocommerce/woocommerce-blocks/pull/7785))
- Make Footer and Header patterns available in pattern chooser. ([7699](https://github.com/woocommerce/woocommerce-blocks/pull/7699))
- Fix: Product Query editor preview with Stock Status setting. ([7682](https://github.com/woocommerce/woocommerce-blocks/pull/7682))

#### technical debt

- Clean up unused CSS code. ([7751](https://github.com/woocommerce/woocommerce-blocks/pull/7751))

= 9.0.0 - 2022-11-21 =

#### Bug Fixes

- Fix skewed placeholder of a Product Image block. ([7651](https://github.com/woocommerce/woocommerce-blocks/pull/7651))
- Fix missing translations in the inspector (editor mode) for the Cart Cross-Sells Blocks. ([7616](https://github.com/woocommerce/woocommerce-blocks/pull/7616))

#### Enhancements

- Move paymentResult to the payment store. ([7692](https://github.com/woocommerce/woocommerce-blocks/pull/7692))
- Add the `Products by Attribute` template. ([7660](https://github.com/woocommerce/woocommerce-blocks/pull/7660))
- Make loading placeholder colors match the current font color for the theme. ([7658](https://github.com/woocommerce/woocommerce-blocks/pull/7658))
- Remove cart fragments support to improve performance in product blocks. ([7644](https://github.com/woocommerce/woocommerce-blocks/pull/7644))
- Add a `clearValidationErrors` action to the `wc/store/validation` data store. ([7601](https://github.com/woocommerce/woocommerce-blocks/pull/7601))
- Add `ValidatedTextInput` and `ValidationInputError` to the `@woocommerce/blocks-checkout` package. ([7583](https://github.com/woocommerce/woocommerce-blocks/pull/7583))
- React Based Local Pickup Settings Screen. ([7581](https://github.com/woocommerce/woocommerce-blocks/pull/7581))
- Convert product-elements/image to TypeScript. ([7572](https://github.com/woocommerce/woocommerce-blocks/pull/7572))
- Add `StoreNoticesContainer` to the `@woocommerce/blocks-checkout` package. ([7558](https://github.com/woocommerce/woocommerce-blocks/pull/7558))
- Convert product-elements/price to TypeScript. ([7534](https://github.com/woocommerce/woocommerce-blocks/pull/7534))
- Adds the option of providing a custom class for the product details on the Cart Block. ([7328](https://github.com/woocommerce/woocommerce-blocks/pull/7328))

#### Various

- Change action type name for use shipping as billing option. ([7695](https://github.com/woocommerce/woocommerce-blocks/pull/7695))
- Block Checkout: Apply selected Local Pickup rate to entire order (all packages). ([7484](https://github.com/woocommerce/woocommerce-blocks/pull/7484))

= 8.9.4 - 2023-01-04 =

#### Bug fixes

- Fix hangs in the block editor with WordPress 5.8. [#8095](https://github.com/woocommerce/woocommerce-blocks/pull/8095)
- Fix Filter by Attribute block crashing in the editor of WordPress 5.8. [#8101](https://github.com/woocommerce/woocommerce-blocks/pull/8101)

= 8.9.3 - 2023-01-03 =

#### Bug fixes

- Fix fatal error in WordPress 5.8 when creating a post or page. [#7496](https://github.com/woocommerce/woocommerce-blocks/pull/7496)

= 8.9.2 - 2022-12-01 =

#### Bug Fixes

- Mini Cart block: fix compatibility with Page Optimize and Product Bundles plugins [#7794](https://github.com/woocommerce/woocommerce-blocks/pull/7794)
- Mini Cart block: Load wc-blocks-registry package at the page's load instead of lazy load it [#7813](https://github.com/woocommerce/woocommerce-blocks/pull/7813)

= 8.9.1 - 2022-11-14 =

#### Bug fixes

- Display correct block template when filtering by attribute. ([7640](https://github.com/woocommerce/woocommerce-blocks/pull/7640))

= 8.9.0 - 2022-11-07 =

#### Enhancements

- Make the Filter by Price block range color dependent of the theme color. ([7525](https://github.com/woocommerce/woocommerce-blocks/pull/7525))
- Update the Mini Cart block drawer to honor the theme's background. ([7510](https://github.com/woocommerce/woocommerce-blocks/pull/7510))
- Improve the appearance of the Express Payment Block. ([7465](https://github.com/woocommerce/woocommerce-blocks/pull/7465))
- Enhancement/pickup location editor improvements. ([7446](https://github.com/woocommerce/woocommerce-blocks/pull/7446))
- Add Stock Status setting to Product Query Block. ([7397](https://github.com/woocommerce/woocommerce-blocks/pull/7397))
- Improve performance for `get_store_pages` method. ([6898](https://github.com/woocommerce/woocommerce-blocks/pull/6898))
- Adding rate limiting functionality to Store API endpoints. ([5962](https://github.com/woocommerce/woocommerce-blocks/pull/5962))

#### Bug Fixes

- Fix margins from Product Selector description on Filter Products by Attribute block. ([7552](https://github.com/woocommerce/woocommerce-blocks/pull/7552))
- Filter by Price block: Fix price slider visibility on dark themes. ([7527](https://github.com/woocommerce/woocommerce-blocks/pull/7527))
- Fix the text color for the header and footer WooCommerce patterns. ([7524](https://github.com/woocommerce/woocommerce-blocks/pull/7524))
- Fix inconsistent button styling with TT3. ([7516](https://github.com/woocommerce/woocommerce-blocks/pull/7516))
- Fix Mini Cart Global Styles. ([7515](https://github.com/woocommerce/woocommerce-blocks/pull/7515))
- Add white background to Filter by Attribute block dropdown so text is legible in dark backgrounds. ([7506](https://github.com/woocommerce/woocommerce-blocks/pull/7506))
- Product button: Fix 'In cart' button localization. ([7504](https://github.com/woocommerce/woocommerce-blocks/pull/7504))
- Fix: Restore transform functionality for filter widgets. ([7401](https://github.com/woocommerce/woocommerce-blocks/pull/7401))
- Developers: Fixed an issue where the project would not build on Windows machines. ([6798](https://github.com/woocommerce/woocommerce-blocks/pull/6798))
- Active Product Filters: Fix active filter grouping for Product Ratings and Stock Statuses ([7577]https://github.com/woocommerce/woocommerce-blocks/pull/7577)

= 8.8.2 - 2022-10-31 =

#### Bug fixes

- Fix Mini Cart Global Styles. [7515](https://github.com/woocommerce/woocommerce-blocks/pull/7515)
- Fix inconsistent button styling with TT3. ([7516](https://github.com/woocommerce/woocommerce-blocks/pull/7516))
- Make the Filter by Price block range color dependent of the theme color. [7525](https://github.com/woocommerce/woocommerce-blocks/pull/7525)
- Filter by Price block: fix price slider visibility on dark themes. [7527](https://github.com/woocommerce/woocommerce-blocks/pull/7527)
- Update the Mini Cart block drawer to honor the theme's background. [7510](https://github.com/woocommerce/woocommerce-blocks/pull/7510)
- Add white background to Filter by Attribute block dropdown so text is legible in dark backgrounds. [7506](https://github.com/woocommerce/woocommerce-blocks/pull/7506)

= 8.8.1 - 2022-10-28 =

#### Bug fixes

- Fix a bug in which cart totals aren't recalculated after running CartExtensions ([#7490](https://github.com/woocommerce/woocommerce-blocks/pull/7490))

= 8.8.0 - 2022-10-24 =

#### Enhancements

- Filter by Ratings: Add the Filter by Rating block to the feature plugin build. ([7384](https://github.com/woocommerce/woocommerce-blocks/pull/7384))
- Filter by Ratings: Improve accessibility by announcing the rating filter change. ([7370](https://github.com/woocommerce/woocommerce-blocks/pull/7370))
- Prevent resource hinting when cart/checkout blocks are not in use. ([7364](https://github.com/woocommerce/woocommerce-blocks/pull/7364))
- Separate filter title and filter controls by converting the Rating filter block to use Inner Blocks. ([7362](https://github.com/woocommerce/woocommerce-blocks/pull/7362))
- Products by Rating: Add support for Display Options. ([7311](https://github.com/woocommerce/woocommerce-blocks/pull/7311))
- Store API: Introduced `woocommerce_store_api_add_to_cart_data` hook. ([7252](https://github.com/woocommerce/woocommerce-blocks/pull/7252))

#### Bug Fixes

- Fix performance issue with the sidebar notices. ([7435](https://github.com/woocommerce/woocommerce-blocks/pull/7435))
- Fix mini cart items alignment issues in Editor. ([7387](https://github.com/woocommerce/woocommerce-blocks/pull/7387))
- Fix: Product Query: Update the Editor preview when custom attributes are changed. ([7366](https://github.com/woocommerce/woocommerce-blocks/pull/7366))
- Store API: Replaced `wc()->api->get_endpoint_data` usage in `/cart/extensions` to fix inconsistencies via filter hooks. ([7361](https://github.com/woocommerce/woocommerce-blocks/pull/7361))
- Fixes a fatal error with Cart Block usage in specific site configurations with multiple shipping countries. ([6896](https://github.com/woocommerce/woocommerce-blocks/pull/6896))

= 8.7.6 - 2022-12-01 =

#### Bug Fixes

- Mini Cart block: fix compatibility with Page Optimize and Product Bundles plugins [#7794](https://github.com/woocommerce/woocommerce-blocks/pull/7794)
- Mini Cart block: Load wc-blocks-registry package at the page's load instead of lazy load it [#7813](https://github.com/woocommerce/woocommerce-blocks/pull/7813)

= 8.7.5 - 2022-10-31 =

#### Enhancements

- Fix Mini Cart Global Styles. [7515](https://github.com/woocommerce/woocommerce-blocks/pull/7515)
- Fix inconsistent button styling with TT3. ([7516](https://github.com/woocommerce/woocommerce-blocks/pull/7516))
- Make the Filter by Price block range color dependent of the theme color. [7525](https://github.com/woocommerce/woocommerce-blocks/pull/7525)
- Filter by Price block: fix price slider visibility on dark themes. [7527](https://github.com/woocommerce/woocommerce-blocks/pull/7527)
- Update the Mini Cart block drawer to honor the theme's background. [7510](https://github.com/woocommerce/woocommerce-blocks/pull/7510)
- Add white background to Filter by Attribute block dropdown so text is legible in dark backgrounds. [7506](https://github.com/woocommerce/woocommerce-blocks/pull/7506)

= 8.7.4 - 2022-10-21 =

#### Bug fixes
- Compatibility fix for Cart and Checkout inner blocks for WordPress 6.1. ([7447](https://github.com/woocommerce/woocommerce-blocks/pull/7447))

= 8.7.3 - 2022-10-20 =

#### Bug fixes
- Fixed an issue where the argument passed to `canMakePayment` contained the incorrect keys. Also fixed the current user's customer data appearing in the editor when editing the Checkout block. ([7434](https://github.com/woocommerce/woocommerce-blocks/pull/7434))

= 8.7.2 - 2022-10-13 =

#### Bug Fixes

- Fixed a problem where Custom Order Tables compatibility declaration could fail due to the unpredictable plugin order load. ([7395](https://github.com/woocommerce/woocommerce-blocks/pull/7395))
- Refactor useCheckoutAddress hook to enable "Use same address for billing" option in Editor ([7393](https://github.com/woocommerce/woocommerce-blocks/pull/7393))

= 8.7.1 - 2022-10-12 =

#### Bug Fixes

- Fixed an issue where JavaScript errors would occur when more than one extension tried to filter specific payment methods in the Cart and Checkout blocks. ([7377](https://github.com/woocommerce/woocommerce-blocks/pull/7377))

= 8.7.0 - 2022-10-10 =

#### Enhancements

- Improve visual consistency between block links. ([7340](https://github.com/woocommerce/woocommerce-blocks/pull/7340))
- Update the titles of some inner blocks of the Cart block and remove the lock of the Cross-Sells parent block. ([7232](https://github.com/woocommerce/woocommerce-blocks/pull/7232))
- Add filter for place order button label. ([7154](https://github.com/woocommerce/woocommerce-blocks/pull/7154))
- Exposed data related to the checkout through wordpress/data stores. ([6612](https://github.com/woocommerce/woocommerce-blocks/pull/6612))
- Add simple, large & two menus footer patterns. ([7306](https://github.com/woocommerce/woocommerce-blocks/pull/7306))
- Add minimal, large, and essential header patterns. ([7292](https://github.com/woocommerce/woocommerce-blocks/pull/7292))
- Add `showRemoveItemLink` as a new checkout filter to allow extensions to toggle the visibility of the `Remove item` button under each cart line item. ([7242](https://github.com/woocommerce/woocommerce-blocks/pull/7242))
- Add support for a GT tracking ID for Google Analytics. ([7213](https://github.com/woocommerce/woocommerce-blocks/pull/7213))
- Separate filter titles and filter controls by converting filter blocks to use Inner Blocks. ([6978](https://github.com/woocommerce/woocommerce-blocks/pull/6978))
- StoreApi requests will return a `Cart-Token` header that can be used to retrieve the cart from the corresponding session via **GET** `/wc/store/v1/cart`. ([5953](https://github.com/woocommerce/woocommerce-blocks/pull/5953))

#### Bug Fixes

- Fixed HTML rendering in description of active payment integrations. ([7313](https://github.com/woocommerce/woocommerce-blocks/pull/7313))
- Hide the shipping address form from the Checkout when the "Force shipping to the customer billing address" is enabled. ([7268](https://github.com/woocommerce/woocommerce-blocks/pull/7268))
- Fixed an error where adding new pages would cause an infinite loop and large amounts of memory use in redux. ([7256](https://github.com/woocommerce/woocommerce-blocks/pull/7256))
- Ensure error messages containing HTML are shown correctly in the Cart and Checkout blocks. ([7231](https://github.com/woocommerce/woocommerce-blocks/pull/7231))
- Prevent locked inner blocks from sometimes displaying twice. ([6676](https://github.com/woocommerce/woocommerce-blocks/pull/6676))
- Improve visual consistency between block links. ([7357](https://github.com/woocommerce/woocommerce-blocks/pull/7357))
- StoreApi `/checkout` endpoint now returns HTTP 402 instead of HTTP 400 when payment fails. ([7273](https://github.com/woocommerce/woocommerce-blocks/pull/7273))
- Fix a problem that causes an infinite loop when inserting Cart block in wordpress.com. ([7367](https://github.com/woocommerce/woocommerce-blocks/pull/7367))

= 8.6.0 - 2022-09-26 =

#### Enhancements

- Create Cross-Sells product list for showing the Cross-Sells products on the Cart block. ([6645](https://github.com/woocommerce/woocommerce-blocks/pull/6645))

#### Bug Fixes

- Fix a bug with the product details block. ([7191](https://github.com/woocommerce/woocommerce-blocks/pull/7191))
- Fix: Ensure that the Express Payment block is not cut off when selecting the Checkout block in the editor. ([7152](https://github.com/woocommerce/woocommerce-blocks/pull/7152))
- Make chevron clickable in Filter by Product block input. ([7139](https://github.com/woocommerce/woocommerce-blocks/pull/7139))
- Fix: Inner blocks control position for Cart Block. ([6973](https://github.com/woocommerce/woocommerce-blocks/pull/6973))
- Enabled HTML rendering within notices for checkout. ([6800](https://github.com/woocommerce/woocommerce-blocks/pull/6800))
- Fix: Render HTML elements on Cart and Checkout blocks when using the woocommerce_shipping_package_name filter. ([7147](https://github.com/woocommerce/woocommerce-blocks/pull/7147))

#### Technical debt

- Remove unused CSS selectors from Filter blocks. ([7150](https://github.com/woocommerce/woocommerce-blocks/pull/7150))

#### Compatibility

- WooPay: fixed a compatibility issue with some error messages shown by WooPay. ([7145](https://github.com/woocommerce/woocommerce-blocks/pull/7145))
- WooPay: Fixed an issue with WooPay which would display tax totals on multiple lines even when configured otherwise. ([7084](https://github.com/woocommerce/woocommerce-blocks/pull/7084))

= 8.5.2 - 2022-10-31 =

#### Enhancements

- Fix Mini Cart Global Styles. [7515](https://github.com/woocommerce/woocommerce-blocks/pull/7515)
- Fix inconsistent button styling with TT3. ([7516](https://github.com/woocommerce/woocommerce-blocks/pull/7516))
- Make the Filter by Price block range color dependent of the theme color. [7525](https://github.com/woocommerce/woocommerce-blocks/pull/7525)
- Filter by Price block: fix price slider visibility on dark themes. [7527](https://github.com/woocommerce/woocommerce-blocks/pull/7527)
- Update the Mini Cart block drawer to honor the theme's background. [7510](https://github.com/woocommerce/woocommerce-blocks/pull/7510)
- Add white background to Filter by Attribute block dropdown so text is legible in dark backgrounds. [7506](https://github.com/woocommerce/woocommerce-blocks/pull/7506)

= 8.5.1 - 2022-09-23 =

#### Bug Fixes

- Ensure that scripts are loaded using absolute URLs to prevent loading issues with subfolder installs. ([7211](https://github.com/woocommerce/woocommerce-blocks/pull/7211))

= 8.5.0 - 2022-09-12 =

#### Enhancements

- Improve the alignment of the Remove button in the Filter by Attribute block. ([7088](https://github.com/woocommerce/woocommerce-blocks/pull/7088))
- Enhance the display of the Active filters block changing the sizes of the text. ([7087](https://github.com/woocommerce/woocommerce-blocks/pull/7087))
- Add loading placeholders to Active Filters block. ([7083](https://github.com/woocommerce/woocommerce-blocks/pull/7083))
- Improved many of the labels to be less technical and more user-friendly. ([7045](https://github.com/woocommerce/woocommerce-blocks/pull/7045))
- Featured Item Blocks: Remove inline default color so that custom colors from Global Styles are applied correctly. ([7036](https://github.com/woocommerce/woocommerce-blocks/pull/7036))
- Update "remove filter" icon on the Active Filters block to use Icon component in both layouts. ([7035](https://github.com/woocommerce/woocommerce-blocks/pull/7035))
- Update `filter by price` skeleton design. ([6997](https://github.com/woocommerce/woocommerce-blocks/pull/6997))
- Update `filter by attribute` skeleton design. ([6990](https://github.com/woocommerce/woocommerce-blocks/pull/6990))

#### Bug Fixes

- Fix checkbox label when count is zero. ([7073](https://github.com/woocommerce/woocommerce-blocks/pull/7073))
- Fix incompatible Classic Template block notice in the Editor for Woo specific templates. ([7033](https://github.com/woocommerce/woocommerce-blocks/pull/7033))
- Update - remove __experimentalDuotone from Featured Product and Featured Category blocks. ([7000](https://github.com/woocommerce/woocommerce-blocks/pull/7000))

#### Documentation

- Add steps to retrieve products variations in Store API documentation. ([7076](https://github.com/woocommerce/woocommerce-blocks/pull/7076))

= 8.4.0 - 2022-08-29 =

#### Enhancements

- Update the filter `Apply` buttons to match the new designs. ([6958](https://github.com/woocommerce/woocommerce-blocks/pull/6958))
- Update the design of the Filter Products by Attribute block. ([6920](https://github.com/woocommerce/woocommerce-blocks/pull/6920))
- Update the design of the Filter by Attribute block settings panel. ([6912](https://github.com/woocommerce/woocommerce-blocks/pull/6912))
- Terms and conditions, and Privacy policy links open in a new tab by default. ([6908](https://github.com/woocommerce/woocommerce-blocks/pull/6908))
- Layout updates to the Active Filters block. ([6905](https://github.com/woocommerce/woocommerce-blocks/pull/6905))
- Update the design of the Filter Products by Stock block. ([6883](https://github.com/woocommerce/woocommerce-blocks/pull/6883))
- Update the design of the Filter Products by Price block. ([6877](https://github.com/woocommerce/woocommerce-blocks/pull/6877))
- Allow making the Cart/Checkout block page the default one from within the editor. ([6867](https://github.com/woocommerce/woocommerce-blocks/pull/6867))
- Register product search as a core/search variation when available. ([6191](https://github.com/woocommerce/woocommerce-blocks/pull/6191))

#### Bug Fixes

- Fixed a bug with a class name deriving from a translatable string. ([6914](https://github.com/woocommerce/woocommerce-blocks/pull/6914))

= 8.3.1 - 2022-08-17 =
#### Bug Fixes


- Prevent unnecessarily showing the item names in a shipping package if it's the only package. ([6899](https://github.com/woocommerce/woocommerce-blocks/pull/6899))

= 8.3.0 - 2022-08-15 =

#### Enhancements

- Add feedback box to the Cart & Checkout Inner Blocks in the inspector. ([6881](https://github.com/woocommerce/woocommerce-blocks/pull/6881))
- Refactor style-attributes hooks to add as global custom imports and remove relative import paths. ([6870](https://github.com/woocommerce/woocommerce-blocks/pull/6870))
- Add notice to Cart and Checkout blocks' inspector controls which links to the list of compatible plugins. ([6869](https://github.com/woocommerce/woocommerce-blocks/pull/6869))
- Add the ability to register patterns by adding them under the "patterns" folder and add the new "WooCommerce Filters" pattern. ([6861](https://github.com/woocommerce/woocommerce-blocks/pull/6861))
- Enable the Cart and Checkout blocks when WooCommerce Blocks is bundled in WooCommerce Core. ([6805](https://github.com/woocommerce/woocommerce-blocks/pull/6805))

#### Bug Fixes

- Refactor Product Categories block to use block.json. ([6875](https://github.com/woocommerce/woocommerce-blocks/pull/6875))
- Fix: Update billing address when shipping address gets change in shipping calculator at Cart block. ([6823](https://github.com/woocommerce/woocommerce-blocks/pull/6823))
- Fix: Add font-weight controls to the Mini Cart block text. ([6760](https://github.com/woocommerce/woocommerce-blocks/pull/6760))

= 8.2.1 - 2022-08-03 =

#### Bug Fixes

- Fixed an issue where shoppers could not switch between different saved payment methods. ([6825](https://github.com/woocommerce/woocommerce-blocks/pull/6825))

= 8.2.0 - 2022-08-02 =

#### Enhancements

- Add update_customer_from_request action to Checkout flow. ([6792](https://github.com/woocommerce/woocommerce-blocks/pull/6792))
- Update: New block icon for the Mini Cart block. ([6784](https://github.com/woocommerce/woocommerce-blocks/pull/6784))
- Introduce `productNameFormat` filter for cart items in Cart and Checkout blocks. ([4993](https://github.com/woocommerce/woocommerce-blocks/pull/4993))

#### Bug Fixes

- Fix proceed to checkout button not working for custom links. ([6804](https://github.com/woocommerce/woocommerce-blocks/pull/6804))
- Mini Cart block: Remove the compatibility notice. ([6803](https://github.com/woocommerce/woocommerce-blocks/pull/6803))
- Fix: Render the product attribute archive page using the `archive-product` template. ([6776](https://github.com/woocommerce/woocommerce-blocks/pull/6776))
- Ensure using the "Use shipping as billing" checkbox in the Checkout Block correctly syncs the addresses when making the order. ([6773](https://github.com/woocommerce/woocommerce-blocks/pull/6773))
- Ensure shipping package names are shown correctly in the Checkout Block when a cart contains multiple packages. ([6753](https://github.com/woocommerce/woocommerce-blocks/pull/6753))
- Select the correct inner button for the "Featured Item" button to update its URL. ([6741](https://github.com/woocommerce/woocommerce-blocks/pull/6741))
- Fix the spacing between separate shipping packages in the Checkout Block. ([6740](https://github.com/woocommerce/woocommerce-blocks/pull/6740))
- Fix missing translations in the inspector (editor mode). ([6737](https://github.com/woocommerce/woocommerce-blocks/pull/6737))
- Fix: Navigate through Mini Cart contents with keyboard. ([6731](https://github.com/woocommerce/woocommerce-blocks/pull/6731))
- Fix: Ensure add to cart notices are displayed on pages containing the Mini Cart block. ([6728](https://github.com/woocommerce/woocommerce-blocks/pull/6728))
- Fix Cart an d Checkout blocks compatibility issue with wordpress.com in which blocks wouldn't load in the editor. ([6718](https://github.com/woocommerce/woocommerce-blocks/pull/6718))
- Fixes an issue where search lists would not preserve the case of the original item. ([6551](https://github.com/woocommerce/woocommerce-blocks/pull/6551))

= 8.1.0 - 2022-07-18 =

#### Enhancements

- Update WooCommerce block template descriptions. ([6667](https://github.com/woocommerce/woocommerce-blocks/pull/6667))
- Add filter URL support to filter blocks when filtering for All Products block. ([6642](https://github.com/woocommerce/woocommerce-blocks/pull/6642))
- Add: Allow choosing between single and multiple sections. ([6620](https://github.com/woocommerce/woocommerce-blocks/pull/6620))
- Cart endpoint for Store API (/wc/store/cart) now features cross-sell items based on cart contents. ([6635](https://github.com/woocommerce/woocommerce-blocks/pull/6635))

#### Bug Fixes

- Prevent Featured Product block from breaking when product is out of stock + hidden from catalog. ([6640](https://github.com/woocommerce/woocommerce-blocks/pull/6640))
- Contrast improvement for checkout error messages when displayed over a theme's dark mode. ([6292](https://github.com/woocommerce/woocommerce-blocks/pull/6292))

= 8.0.0 - 2022-07-04 =

#### Enhancements

- Make form components require onChange and have a default value. ([6636](https://github.com/woocommerce/woocommerce-blocks/pull/6636))
- Footer Template Parts use now `<footer>` instead of `<div>` and Header uses `<header>` instead of `<div>`. ([6596](https://github.com/woocommerce/woocommerce-blocks/pull/6596))
- Replace the ProductTag tax_query field to be the term_id instead of the id. ([6585](https://github.com/woocommerce/woocommerce-blocks/pull/6585))

#### Bug Fixes

- Fix: Correctly calculate taxes for local pickups. ([6631](https://github.com/woocommerce/woocommerce-blocks/pull/6631))
- Fix: Ensure WooCommerce templates show correct titles. ([6452](https://github.com/woocommerce/woocommerce-blocks/pull/6452))

= 7.9.0 - 2022-06-20 =

#### Enhancements

- Disable page scroll when Mini Cart drawer is open. ([6532](https://github.com/woocommerce/woocommerce-blocks/pull/6532))
- Register filter blocks using block metadata. ([6505](https://github.com/woocommerce/woocommerce-blocks/pull/6505))

#### Bug Fixes

- Fix images hidden by default in Product grid blocks after WC 6.6 update. ([6599](https://github.com/woocommerce/woocommerce-blocks/pull/6599))
- Fix: Scrolling issue of the Filled Mini Cart view block. ([6565](https://github.com/woocommerce/woocommerce-blocks/pull/6565))
- Fix an endless loop when using product grid blocks inside product descriptions. ([6471](https://github.com/woocommerce/woocommerce-blocks/pull/6471))

#### Various

- Prevent warnings appearing when using some plugins for managing shipping packages. ([6470](https://github.com/woocommerce/woocommerce-blocks/pull/6470))
- Add template descriptions. ([6345](https://github.com/woocommerce/woocommerce-blocks/pull/6345))

= 7.8.3 - 2022-06-20 =

#### Bug Fixes
- Fix images hidden by default in Product grid blocks after WC 6.6 update. ([6599](https://github.com/woocommerce/woocommerce-blocks/pull/6599))

= 7.8.2 - 2022-06-20 =

#### Bug Fixes
- Replace instances of wp_is_block_theme() with wc_current_theme_is_fse_theme(). ([6590](https://github.com/woocommerce/woocommerce-blocks/pull/6590))


= 7.8.1 - 2022-06-13 =

#### Bug Fixes

- Fix PHP notice in Mini Cart when prices included taxes. ([6537](https://github.com/woocommerce/woocommerce-blocks/pull/6537))

= 7.8.0 - 2022-06-06 =

#### Enhancements

- Filter Products by Price: Decrease price slider step sequence from 10 to 1. ([6486](https://github.com/woocommerce/woocommerce-blocks/pull/6486))
- Add the `Fixed image` and `Repeated image` media controls to the Featured Category block. ([6440](https://github.com/woocommerce/woocommerce-blocks/pull/6440))

#### Bug Fixes

- Featured Item Blocks: Fix an issue where the default color could be overridden by a theme, and where custom colors were not applied correctly. ([6492](https://github.com/woocommerce/woocommerce-blocks/pull/6492))
- Fix: Only enqueue the relevant translations script. ([6478](https://github.com/woocommerce/woocommerce-blocks/pull/6478))
- Fix: All Products block: New product is missing in the Cart block if `Redirect to the cart page after successful addition` is enabled. ([6466](https://github.com/woocommerce/woocommerce-blocks/pull/6466))
- Respect low stock visibility setting in Cart/Checkout. ([6444](https://github.com/woocommerce/woocommerce-blocks/pull/6444))
- Decouple Store API payment handling from Checkout block loading code. ([6519](https://github.com/woocommerce/woocommerce-blocks/pull/6519))

#### Various

- Add support for `Font size` and `Font family` for the `Mini Cart` block. ([6396](https://github.com/woocommerce/woocommerce-blocks/pull/6396))

= 7.7.0 - 2022-05-24 =

#### Enhancements

- Add the `Fixed image` and `Repeated image` media controls to the Featured Product block. ([6344](https://github.com/woocommerce/woocommerce-blocks/pull/6344))

#### Bug Fixes

- Remove bold styles from All Products block. ([6436](https://github.com/woocommerce/woocommerce-blocks/pull/6436))
- Fix an issue where the Cart & Checkout could have some of the locked inner blocks removed. ([6419](https://github.com/woocommerce/woocommerce-blocks/pull/6419))
- Fix broken translation in Cart/Checkout blocks. ([6420](https://github.com/woocommerce/woocommerce-blocks/pull/6420))

= 7.6.2 - 2022-06-20 =

####  Bug Fixes

- Fix images hidden by default in Product grid blocks after WC 6.6 update. ([6599](https://github.com/woocommerce/woocommerce-blocks/pull/6599))

= 7.6.1 - 2022-06-17 =

### Bug Fixes

- Fix PHP notice in Mini Cart when prices included taxes. ([6537](https://github.com/woocommerce/woocommerce-blocks/pull/6537))
- Fix error Uncaught Error: Call to undefined function Automattic\WooCommerce\Blocks\Templates\wp_is_block_theme() in WP 5.8. ([6590](https://github.com/woocommerce/woocommerce-blocks/pull/6590))

= 7.6.0 - 2022-05-09 =

#### Enhancements

- Featured Category: Add background color option. ([6368](https://github.com/woocommerce/woocommerce-gutenberg-products-block/pull/6368))
- Featured Product: Add background color option. ([6367](https://github.com/woocommerce/woocommerce-gutenberg-products-block/pull/6367))
- Added media controls allowing the user to edit images within the editor on a Featured Category block. ([6360](https://github.com/woocommerce/woocommerce-gutenberg-products-block/pull/6360))
- Added media controls allowing the user to edit images within the editor on a Featured Product block. ([6348](https://github.com/woocommerce/woocommerce-gutenberg-products-block/pull/6348))
- Add the alt text control to the Featured Category block media settings. ([6341](https://github.com/woocommerce/woocommerce-gutenberg-products-block/pull/6341))
- Hide the Product Tag Cloud from the Widgets screen in classic themes. ([6327](https://github.com/woocommerce/woocommerce-gutenberg-products-block/pull/6327))
- Add the alt text control to the Featured Product block media settings. ([6308](https://github.com/woocommerce/woocommerce-gutenberg-products-block/pull/6308))
- GridContentControl: Add product image control. ([6302](https://github.com/woocommerce/woocommerce-gutenberg-products-block/pull/6302))

#### Bug Fixes

- Fix: Align Empty Mini Cart view. block center in the Site Editor. ([6379](https://github.com/woocommerce/woocommerce-gutenberg-products-block/pull/6379))
- Remove the Template panel from the Setting Sidebar for Shop page. ([6366](https://github.com/woocommerce/woocommerce-gutenberg-products-block/pull/6366))
- Parse categories coming from the back-end as a json array. ([6358](https://github.com/woocommerce/woocommerce-gutenberg-products-block/pull/6358))
- Update the default width of Classic Template to Wide width. ([6356](https://github.com/woocommerce/woocommerce-gutenberg-products-block/pull/6356))
- Fix: Mini Cart block is not available from the Edit template screen. ([6351](https://github.com/woocommerce/woocommerce-gutenberg-products-block/pull/6351))
- Fix Filter Products by Attribute block not working on PHP templates when Filter button was enabled. ([6332](https://github.com/woocommerce/woocommerce-gutenberg-products-block/pull/6332))


= 7.5.0 - 2022-04-25 =

#### Enhancements

- Add PHP templates support to the Active Product Filters block. ([6295](https://github.com/woocommerce/woocommerce-gutenberg-products-block/pull/6295))
- Enable Draft orders in WooCommerce Core. ([6288](https://github.com/woocommerce/woocommerce-gutenberg-products-block/pull/6288))
- Enhanced the *Featured Category block*:
	- Implemented support for duotone.
	- Implemented support for gradients on the overlay.
	- Implemented support for custom inner padding.
	- Implemented image fit options: users can now decide how should the image behave on the resizing of the component; it can either scale to always cover the entire container, or remain its original size.
	- Fixed an inconsistency where the overlay color was controlled by the background color control. It is now moved to the correct section.
	- Fixed the focal point picker, it now works on both axis as long as the image fit (above) is set to `none`.
	- Fixed an issue with the visibility of the resizing handle.
	- Fixed an issue which would keep the resizing handle always active regardless of block selection status.
	- Changed the behavior of the resizing: The block can't be resized below a minimum height determined by its content plus the padding. ([6276](https://github.com/woocommerce/woocommerce-gutenberg-products-block/pull/6276))
- Allow adding the Filter Products by Stock block to Product Catalog templates to filter products. ([6261](https://github.com/woocommerce/woocommerce-gutenberg-products-block/pull/6261))
- Enhanced the *Featured Product block*:
	- Implemented support for duotone.
	- Implemented support for gradients on the overlay.
	- Implemented support for custom inner padding.
	- Implemented image fit options: users can now decide how should the image behave on the resizing of the component; it can either scale to always cover the entire container, or remain its original size.
	- Fixed an inconsistency where the overlay color was controlled by the background color control. It is now moved to the correct section.
	- Fixed the focal point picker, it now works on both axis as long as the image fit (above) is set to `none`.
	- Fixed an issue with the visibility of the resizing handle.
	- Fixed an issue which would keep the resizing handle always active regardless of block selection status.
	- Changed the behavior of the resizing: The block can't be resized below a minimum height determined by its content plus the padding. ([6181](https://github.com/woocommerce/woocommerce-gutenberg-products-block/pull/6181))
- Allow saved payment methods labels other than card/eCheck to display brand & last 4 digits if present. ([6177](https://github.com/woocommerce/woocommerce-gutenberg-products-block/pull/6177))

#### Bug Fixes

- Filter Products by Attribute: Fix the page reload which happens when clicking the filter button on Woo templates using the Classic Template block. ([6287](https://github.com/woocommerce/woocommerce-gutenberg-products-block/pull/6287))
- Store API: Show visible attributes in simple products, and hidden attributes in variable products. ([6274](https://github.com/woocommerce/woocommerce-gutenberg-products-block/pull/6274))
- Add RTL support for the Mini Cart icon. ([6264](https://github.com/woocommerce/woocommerce-gutenberg-products-block/pull/6264))
- Fix page load problem due to incorrect URL to certain assets. ([6260](https://github.com/woocommerce/woocommerce-gutenberg-products-block/pull/6260))
- Fix: Make Filters Products by Price work with Active Filters block for the PHP rendered Classic Template. ([6245](https://github.com/woocommerce/woocommerce-gutenberg-products-block/pull/6245))

#### Various

- Product Query: Pass any product taxonomies existing in the URL parameters. ([6152](https://github.com/woocommerce/woocommerce-gutenberg-products-block/pull/6152))


= 7.4.2 - 2022-04-15 =

#### Bug Fixes

- Ensure errors during cart/checkout API requests are shown on the front-end. ([6268](https://github.com/woocommerce/woocommerce-gutenberg-products-block/pull/6268))

= 7.4.1 - 2022-04-14 =

#### Bug Fixes

- Fix page load problem due to incorrect URL to certain assets. ([6260](https://github.com/woocommerce/woocommerce-gutenberg-products-block/pull/6260))

= 7.4.0 - 2022-04-14 =

#### Enhancements

- Allow adding the Filter Products by Price block to Product Catalog templates to filter products. ([6146](https://github.com/woocommerce/woocommerce-gutenberg-products-block/pull/6146))
- The order summary area for the Cart and Checkout Blocks is now powered by Inner Blocks allowing for more customizations and extensibility. ([6065](https://github.com/woocommerce/woocommerce-gutenberg-products-block/pull/6065))

#### Bug Fixes

- Increase Cart product quantity limit. ([6202](https://github.com/woocommerce/woocommerce-gutenberg-products-block/pull/6202))
- Mini Cart block: Fix translations loading. ([6158](https://github.com/woocommerce/woocommerce-gutenberg-products-block/pull/6158))
- Fix Featured Product and Featured Category buttons misalignment in Twenty Twenty Two theme. ([6156](https://github.com/woocommerce/woocommerce-gutenberg-products-block/pull/6156))
- Remove the ToggleButtonControl in favor of ToggleGroupControl. ([5967](https://github.com/woocommerce/woocommerce-gutenberg-products-block/pull/5967))
- Decode HTML entities when formatting Store API error messages. ([5870](https://github.com/woocommerce/woocommerce-gutenberg-products-block/pull/5870))

= 7.3.0 - 2022-03-28 =

#### Enhancements

- Product Ratings: Add Global Styles font size and spacing support. ([5927](https://github.com/woocommerce/woocommerce-gutenberg-products-block/pull/5927))
- Add resource hinting for cart and checkout blocks to improve first time performance. ([5553](https://github.com/woocommerce/woocommerce-gutenberg-products-block/pull/5553))
- Add Mini Cart block to feature plugin ([6127](https://github.com/woocommerce/woocommerce-gutenberg-products-block/pull/6127))
- Allow memoized checkout filters to re-run if the default value changes between runs. ([6102](https://github.com/woocommerce/woocommerce-gutenberg-products-block/pull/6102))

#### Bug Fixes

- Filter Products by Attribute: Make dropdown search case sensitive. ([6096](https://github.com/woocommerce/woocommerce-gutenberg-products-block/pull/6096))
- Stop showing the price slider skeleton when moving the slider handles. ([6078](https://github.com/woocommerce/woocommerce-gutenberg-products-block/pull/6078))

#### Various

- Rename Legacy Template block to Classic Template block. ([6021](https://github.com/woocommerce/woocommerce-gutenberg-products-block/pull/6021))

= 7.2.2 - 2022-04-15 =

#### Bug fixes

- Fix page load problem due to incorrect URL to certain assets. [#6260](https://github.com/woocommerce/woocommerce-gutenberg-products-block/pull/6260)

= 7.2.1 - 2022-03-23 =

#### Bug fixes

- Don't trigger class deprecations if headers are already sent [#6074](https://github.com/woocommerce/woocommerce-gutenberg-products-block/pull/6074)

= 7.2.0 - 2022-03-14 =

#### Bug Fixes

- StoreAPI: Clear all wc notice types in the cart validation context [#5983](https://github.com/woocommerce/woocommerce-gutenberg-products-block/pull/5983)
- Fix loading more WC core translations in locales where WC Blocks is not localized for some strings.
- Ensure shipping address is set for virtual orders to prevent missing country errors. [#6050](https://github.com/woocommerce/woocommerce-gutenberg-products-block/pull/6050)

#### Enhancements

- Memoize/cache filter results so that we don't call third party filters too often [#5143](https://github.com/woocommerce/woocommerce-gutenberg-products-block/pull/5143)

#### Various

- Remove v1 string from Store Keys. ([5987](https://github.com/woocommerce/woocommerce-gutenberg-products-block/pull/5987))
- Introduce the `InvalidCartException` for handling cart validation. ([5904](https://github.com/woocommerce/woocommerce-gutenberg-products-block/pull/5904))
- Renamed Store API custom headers to remove `X-WC-Store-API` prefixes. [#5983](https://github.com/woocommerce/woocommerce-gutenberg-products-block/pull/5983)
- Normalized Store API error codes [#5992](https://github.com/woocommerce/woocommerce-gutenberg-products-block/pull/5992)
- Deprecated `woocommerce_blocks_checkout_order_processed` in favour of `woocommerce_store_api_checkout_order_processed`
- Deprecated `woocommerce_blocks_checkout_update_order_meta` in favour of `woocommerce_store_api_checkout_update_order_meta`
- Deprecated `woocommerce_blocks_checkout_update_order_from_request` in favour of `woocommerce_store_api_checkout_update_order_from_request`

= 7.1.0 - 2022-02-28 =

#### Enhancements

- Add Global Styles support to the Product Price block. ([5950](https://github.com/woocommerce/woocommerce-gutenberg-products-block/pull/5950))
- Add Global Styles support to the Add To Cart Button block. ([5816](https://github.com/woocommerce/woocommerce-gutenberg-products-block/pull/5816))
- Store API - Introduced `wc/store/v1` namespace. ([5911](https://github.com/woocommerce/woocommerce-gutenberg-products-block/pull/5911))
- Renamed WooCommerce block templates to more e-commerce related names. ([5935](https://github.com/woocommerce/woocommerce-gutenberg-products-block/pull/5935))
- Featured Product block: Add the ability to reset to a previously set custom background image. ([5886](https://github.com/woocommerce/woocommerce-gutenberg-products-block/pull/5886))

#### Bug Fixes

- Fixed typo in `wooocommerce_store_api_validate_add_to_cart` and `wooocommerce_store_api_validate_cart_item` hook names. ([5926](https://github.com/woocommerce/woocommerce-gutenberg-products-block/pull/5926))
- Fix loading WC core translations in locales where WC Blocks is not localized for some strings. ([5910](https://github.com/woocommerce/woocommerce-gutenberg-products-block/pull/5910))

= 7.0.0 - 2022-02-14 =

#### Enhancements

- Add a remove image button to the WooCommerce Feature Category block. ([5719](https://github.com/woocommerce/woocommerce-gutenberg-products-block/pull/5719))
- Add support for the global style for the On-Sale Badge block. ([5565](https://github.com/woocommerce/woocommerce-gutenberg-products-block/pull/5565))
- Add support for the global style for the Attribute Filter block. ([5557](https://github.com/woocommerce/woocommerce-gutenberg-products-block/pull/5557))
- Category List block: Add support for global style. ([5516](https://github.com/woocommerce/woocommerce-gutenberg-products-block/pull/5516))

#### Bug Fixes

- Fix wide appender buttons overlap in Cart & Checkout blocks in the Editor. ([5801](https://github.com/woocommerce/woocommerce-gutenberg-products-block/pull/5801))
- Fixed an issue where clear customizations functionality was not working for WooCommerce templates. ([5746](https://github.com/woocommerce/woocommerce-gutenberg-products-block/pull/5746))
- Fixed an issue where default block attributes were not being passed to the Checkout block correctly. ([5732](https://github.com/woocommerce/woocommerce-gutenberg-products-block/pull/5732))
- Fixed an issue where orders would break if they did not require a payment. ([5720](https://github.com/woocommerce/woocommerce-gutenberg-products-block/pull/5720))
- Fixed hover and focus states for button components. ([5712](https://github.com/woocommerce/woocommerce-gutenberg-products-block/pull/5712))
- Add to Cart button on Products listing blocks will respect the "Redirect to the cart page after successful addition" setting. ([5708](https://github.com/woocommerce/woocommerce-gutenberg-products-block/pull/5708))
- Fixes Twenty Twenty Two issues with sales price and added to cart "View Cart" call out styling in the "Products by Category" block. ([5684](https://github.com/woocommerce/woocommerce-gutenberg-products-block/pull/5684))

= 6.9.0 - 2022-01-31 =

#### Enhancements

- Add support for the global style for the Featured Category block. ([5542](https://github.com/woocommerce/woocommerce-gutenberg-products-block/pull/5542))

#### Bug Fixes


- Enable Mini Cart template-parts only for experimental builds. ([5606](https://github.com/woocommerce/woocommerce-gutenberg-products-block/pull/5606))
- Show express payment button in full width if only one express payment method is available. ([5601](https://github.com/woocommerce/woocommerce-gutenberg-products-block/pull/5601))
- Wrapped cart item product contents in inner div. ([5240](https://github.com/woocommerce/woocommerce-gutenberg-products-block/pull/5240))
- Fix alignment issue with the "create account" section on the checkout block in the editor ([5633](https://github.com/woocommerce/woocommerce-gutenberg-products-block/pull/5633))

#### blocker

- Revert "Allow LegacyTemplate block to be reinserted, only on WooCommerce block templates.". ([5643](https://github.com/woocommerce/woocommerce-gutenberg-products-block/pull/5643))


= 6.8.0 - 2022-01-17 =

#### Enhancements

- Add support for the global style for the Price Filter block. ([5559](https://github.com/woocommerce/woocommerce-gutenberg-products-block/pull/5559))
- Hold stock for 60 mins if the order is pending payment. ([5546](https://github.com/woocommerce/woocommerce-gutenberg-products-block/pull/5546))
- Allow users to reinsert the WooCommerce Legacy Template block. ([5545](https://github.com/woocommerce/woocommerce-gutenberg-products-block/pull/5545))
- Add support for the global style for the Stock Indicator block. ([5525](https://github.com/woocommerce/woocommerce-gutenberg-products-block/pull/5525))
- Add support for the global style for the Summary Product block. ([5524](https://github.com/woocommerce/woocommerce-gutenberg-products-block/pull/5524))
- Add support for the global style for the Product Title block. ([5515](https://github.com/woocommerce/woocommerce-gutenberg-products-block/pull/5515))
- Fix duplicated checkout error notices. ([5476](https://github.com/woocommerce/woocommerce-gutenberg-products-block/pull/5476))
- Store API: Throw errors when attempting to pay with a non-available payment method. ([5440](https://github.com/woocommerce/woocommerce-gutenberg-products-block/pull/5440))
- Add support for the wide and full alignment for the legacy template block. ([5433](https://github.com/woocommerce/woocommerce-gutenberg-products-block/pull/5433))
- Store API and Cart block now support defining a quantity stepper and a minimum quantity. ([5406](https://github.com/woocommerce/woocommerce-gutenberg-products-block/pull/5406))
- Added controls to product grid blocks for filtering by stock levels. ([4943](https://github.com/woocommerce/woocommerce-gutenberg-products-block/pull/4943))

#### Bug Fixes

- Use consistent HTML code for all rating sections, so that screen readers pronounce the rating correctly. ([5552](https://github.com/woocommerce/woocommerce-gutenberg-products-block/pull/5552))
- All Products block displays thumbnails. ([5551](https://github.com/woocommerce/woocommerce-gutenberg-products-block/pull/5551))
- Fixed a styling issue in the Checkout block when an order has multiple shipping packages. ([5529](https://github.com/woocommerce/woocommerce-gutenberg-products-block/pull/5529))
- Fixed a visual bug (#5152) with the points and rewards plugin. ([5430](https://github.com/woocommerce/woocommerce-gutenberg-products-block/pull/5430))
- Filter Products By Price block: Don't allow to insert negative values on inputs. ([5123](https://github.com/woocommerce/woocommerce-gutenberg-products-block/pull/5123))

#### technical debt

- Remove invalid `$wpdb->prepare()` statement in Featured Category Block. ([5471](https://github.com/woocommerce/woocommerce-gutenberg-products-block/pull/5471))
- Remove Stripe Payment Method Integration (which is now part of the Stripe Payment Method extension itself). ([5449](https://github.com/woocommerce/woocommerce-gutenberg-products-block/pull/5449))

#### Various

- Update the block theme folders to latest Gutenberg convention (i.e. `templates` and `parts`). ([5464](https://github.com/woocommerce/woocommerce-gutenberg-products-block/pull/5464))

= 6.7.3 - 2022-01-24 =

#### Bug Fixes

- Enable Mini Cart template parts only for experimental builds. ([#5606](https://github.com/woocommerce/woocommerce-gutenberg-products-block/pull/5606))

= 6.7.2 - 2022-01-17 =

#### Bug Fixes

- Update WooCommerce plugin slug for Block Templates. ([#5519](https://github.com/woocommerce/woocommerce-gutenberg-products-block/pull/5519))

= 6.7.1 - 2022-01-07 =

#### Bug Fixes

- Convert token to string when setting the active payment method. ([5535](https://github.com/woocommerce/woocommerce-gutenberg-products-block/pull/5535))

= 6.7.0 - 2022-01-03 =

#### Enhancements

- Added global styles (text color) to the Active Filters block. ([5465](https://github.com/woocommerce/woocommerce-gutenberg-products-block/pull/5465))
- Prevent a 0 value shipping price being shown in the Checkout if no shipping methods are available. ([5444](https://github.com/woocommerce/woocommerce-gutenberg-products-block/pull/5444))

#### Bug Fixes

- Fixed an issue where the checkout address fields would be blank for logged in customers. ([5473](https://github.com/woocommerce/woocommerce-gutenberg-products-block/pull/5473))
- Account for products without variations in the On Sale Products block. ([5470](https://github.com/woocommerce/woocommerce-gutenberg-products-block/pull/5470))
- Update the template retrieving logic to allow for older Gutenberg convention and newer one (`block-templates`/`block-template-parts` vs. `templates`/`parts`). ([5455](https://github.com/woocommerce/woocommerce-gutenberg-products-block/pull/5455))
- Ensure that the translation of the "Proceed to Checkout" button is working. ([5453](https://github.com/woocommerce/woocommerce-gutenberg-products-block/pull/5453))
- Fix custom templates with fallback to archive being incorrectly attributed to the user in the editor instead of the parent theme. ([5447](https://github.com/woocommerce/woocommerce-gutenberg-products-block/pull/5447))
- Remove text decorations from product filtering blocks items. ([5384](https://github.com/woocommerce/woocommerce-gutenberg-products-block/pull/5384))

= 6.6.0 - 2021-12-20 =

#### Bug Fixes

- "Added By" template column value is user friendly for modified WooCommerce block templates. ([5420](https://github.com/woocommerce/woocommerce-gutenberg-products-block/pull/5420))
- Fixed a performance issue with the cart by preventing an extra network request on mount. ([5394](https://github.com/woocommerce/woocommerce-gutenberg-products-block/pull/5394))
- Use the themes product archive block template for product category & product tag pages if the theme does not have more specific templates for those. ([5380](https://github.com/woocommerce/woocommerce-gutenberg-products-block/pull/5380))
- Cart block: Switch to correct view if inner block is selected. ([5358](https://github.com/woocommerce/woocommerce-gutenberg-products-block/pull/5358))
- Respect implicit quantity updates coming from server or directly from data stores. ([5352](https://github.com/woocommerce/woocommerce-gutenberg-products-block/pull/5352))
- Fixed a case where payments could fail after validation errors when using saved cards. ([5350](https://github.com/woocommerce/woocommerce-gutenberg-products-block/pull/5350))
- Add error handling for network errors during checkout. ([5341](https://github.com/woocommerce/woocommerce-gutenberg-products-block/pull/5341))
- Fix cart and checkout margin problem by removing the full-width option. ([5315](https://github.com/woocommerce/woocommerce-gutenberg-products-block/pull/5315))
- Fix saving WooCommerce templates in WP 5.9 beta 3 ([5408](https://github.com/woocommerce/woocommerce-gutenberg-products-block/pull/5408))
- Fix You attempted to edit an item that doesn't exist error on WordPress 5.8 ([5425](https://github.com/woocommerce/woocommerce-gutenberg-products-block/pull/5425))
- Fix required scripts not loading for WC block templates. ([5346](https://github.com/woocommerce/woocommerce-gutenberg-products-block/pull/5346))
- Fix reverting WC templates. ([5342](https://github.com/woocommerce/woocommerce-gutenberg-products-block/pull/5342))
- Fix WC templates loading for WP 5.9 without Gutenberg plugin. ([5335](https://github.com/woocommerce/woocommerce-gutenberg-products-block/pull/5335))

#### Various

- Sync draft orders whenever cart data changes. [5379](https://github.com/woocommerce/woocommerce-gutenberg-products-block/pull/5379)
- Removed legacy handling for shipping_phone in Store API. ([5326](https://github.com/woocommerce/woocommerce-gutenberg-products-block/pull/5326))
- Site Editor template list: Fix wrong icon displayed on WooCommerce templates after they have been edited. ([5375](https://github.com/woocommerce/woocommerce-gutenberg-products-block/pull/5375))
- Fix validation error handling after using browser autofill. ([5373](https://github.com/woocommerce/woocommerce-gutenberg-products-block/pull/5373))
- Update loading skeleton animations. ([5362](https://github.com/woocommerce/woocommerce-gutenberg-products-block/pull/5362))
- Add error handling to `get_routes_from_namespace` method. ([5319](https://github.com/woocommerce/woocommerce-gutenberg-products-block/pull/5319))
- Make it so WooCommerce template names are not editable ([5385](https://github.com/woocommerce/woocommerce-gutenberg-products-block/pull/5385))


= 6.5.0 - 2021-12-06 =

#### Enhancements

- Added global styles (text color, link color, line height, and font size) to the Product Title block. ([5133](https://github.com/woocommerce/woocommerce-gutenberg-products-block/pull/5133))

#### Bug Fixes

- Fixed Featured Product Block search not working for large stores. ([5156](https://github.com/woocommerce/woocommerce-gutenberg-products-block/pull/5156))

= 6.4.0 - 2021-11-22 =

#### Enhancements

- Pass to payment methods a wrapper component that handles the loading state. ([5135](https://github.com/woocommerce/woocommerce-gutenberg-products-block/pull/5135))

#### Bug Fixes

- Gate WC template editing (FSE) to versions of WC 6.0 or higher. ([5210](https://github.com/woocommerce/woocommerce-gutenberg-products-block/pull/5210))
- Fix manual entry within Quantity Inputs in Cart block. ([5197](https://github.com/woocommerce/woocommerce-gutenberg-products-block/pull/5197))
- Correctly align Terms and Conditions block checkbox in Checkout block. ([5191](https://github.com/woocommerce/woocommerce-gutenberg-products-block/pull/5191))
- Add support for decimal and thousand separators in the `formatPrice` function. ([5188](https://github.com/woocommerce/woocommerce-gutenberg-products-block/pull/5188))
- Reduce the size of the checkbox component label to prevent accidental input. ([5164](https://github.com/woocommerce/woocommerce-gutenberg-products-block/pull/5164))
- Lazy load missing translation files on frontend to ensure that all visible texts are translatable. ([5112](https://github.com/woocommerce/woocommerce-gutenberg-products-block/pull/5112))

= 6.3.3 - 2021-11-25 =

#### Bug Fixes

- Fix fatal error in certain WP 5.9 pre-release versions. ([5183](https://github.com/woocommerce/woocommerce-gutenberg-products-block/pull/5183))

= 6.3.2 - 2021-11-17 =

#### Enhancements

- Legacy Template Block: allow users to delete the block. ([5176](https://github.com/woocommerce/woocommerce-gutenberg-products-block/pull/5176))

#### Bug Fixes

- Removed WooCommerce block templates from appearing in the template dropdown for a page or post. ([5167](https://github.com/woocommerce/woocommerce-gutenberg-products-block/pull/5167))

= 6.3.1 - 2021-11-17 =

#### Bug Fixes

- Fix 'Country is required' error on the Cart block when updating shipping address ([5129](https://github.com/woocommerce/woocommerce-gutenberg-products-block/pull/5129))
- Fix state validation to compare state codes, and only validate if a country is given ([5132](https://github.com/woocommerce/woocommerce-gutenberg-products-block/pull/5132))
- Make order note block removable ([5139](https://github.com/woocommerce/woocommerce-gutenberg-products-block/pull/5139))

= 6.3.0 - 2021-11-16 =

#### Enhancements

- Add placeholder text when modifying product search input in the editor. ([5122](https://github.com/woocommerce/woocommerce-gutenberg-products-block/pull/5122))
- FSE: Add basic product archive block template. ([5049](https://github.com/woocommerce/woocommerce-gutenberg-products-block/pull/5049))
- FSE: Add basic taxonomy block templates. ([5063](https://github.com/woocommerce/woocommerce-gutenberg-products-block/pull/5063))
- FSE: Add single product block template. ([5054](https://github.com/woocommerce/woocommerce-gutenberg-products-block/pull/5054))
- FSE: Remove the `do_action( 'woocommerce_sidebar' );` action from the `LegacyTemplate.php` block. ([5097](https://github.com/woocommerce/woocommerce-gutenberg-products-block/pull/5097))
- Fix duplicate queries in product grids #4695. ([5002](https://github.com/woocommerce/woocommerce-gutenberg-products-block/pull/5002))
- FSE: Add abstract block legacy template for core PHP templates. ([4991](https://github.com/woocommerce/woocommerce-gutenberg-products-block/pull/4991))
- FSE: Add render logic to BlockTemplateController. ([4984](https://github.com/woocommerce/woocommerce-gutenberg-products-block/pull/4984))
- Improve accessibility by using self-explaining edit button titles. ([5113](https://github.com/woocommerce/woocommerce-gutenberg-products-block/pull/5113))
- Improve readability of terms and condition text by not displaying the text justified. ([5120](https://github.com/woocommerce/woocommerce-gutenberg-products-block/pull/5120))
- Improve rendering performance for Single Product block. ([5107](https://github.com/woocommerce/woocommerce-gutenberg-products-block/pull/5107))
- Improve the product images placeholder display by adding a light gray border to it. ([4950](https://github.com/woocommerce/woocommerce-gutenberg-products-block/pull/4950))
- Deprecate the __experimental_woocommerce_blocks_checkout_update_order_from_request action in favour of woocommerce_blocks_checkout_update_order_from_request. ([5015](https://github.com/woocommerce/woocommerce-gutenberg-products-block/pull/5015))
- Deprecate the __experimental_woocommerce_blocks_checkout_update_order_meta action in favour of woocommerce_blocks_checkout_update_order_meta. ([5017](https://github.com/woocommerce/woocommerce-gutenberg-products-block/pull/5017))
- Deprecate the __experimental_woocommerce_blocks_checkout_order_processed action in favour of woocommerce_blocks_checkout_order_processed. ([5014](https://github.com/woocommerce/woocommerce-gutenberg-products-block/pull/5014))

#### Bug Fixes

- Fix label alignment of the product search in the editor. ([5072](https://github.com/woocommerce/woocommerce-gutenberg-products-block/pull/5072))
- Fix sale badge alignment on smaller screen. ([5061](https://github.com/woocommerce/woocommerce-gutenberg-products-block/pull/5061))
- FSE: Fix missing `is_custom` property for WooCommerce block template objects. ([5067](https://github.com/woocommerce/woocommerce-gutenberg-products-block/pull/5067))
- Replace incorrect with correct text domain. ([5020](https://github.com/woocommerce/woocommerce-gutenberg-products-block/pull/5020))
- Scripts using `wc-settings` or script that depend on it would be enqueued in the footer if they're enqueued in the header. ([5059](https://github.com/woocommerce/woocommerce-gutenberg-products-block/pull/5059))

= 6.2.0 - 2021-10-26 =

#### Enhancements

- Cart v2: The cart block, like checkout block, now supports inner blocks that allow for greater customizability. ([4973](https://github.com/woocommerce/woocommerce-gutenberg-products-block/pull/4973))
- BlockTemplateController: Adds the ability to load and manage block template files. ([4981](https://github.com/woocommerce/woocommerce-gutenberg-products-block/pull/4981))
- Improve accessibility for the editor view of the Product search block. ([4905](https://github.com/woocommerce/woocommerce-gutenberg-products-block/pull/4905))

#### Bug Fixes

- Fix custom classname support for inner checkout blocks. ([4978](https://github.com/woocommerce/woocommerce-gutenberg-products-block/pull/4978))
- Fix a bug in free orders and trial subscription products. ([4955](https://github.com/woocommerce/woocommerce-gutenberg-products-block/pull/4955))
- Remove duplicate attributes in saved block HTML. ([4941](https://github.com/woocommerce/woocommerce-gutenberg-products-block/pull/4941))
- Fix render error of Filter by Attribute block when no attribute is selected. ([4847](https://github.com/woocommerce/woocommerce-gutenberg-products-block/pull/4847))
- Store API - Ensure returned customer address state is valid. ([4844](https://github.com/woocommerce/woocommerce-gutenberg-products-block/pull/4844))

= 6.1.0 - 2021-10-12 =

#### Bug Fixes

- Fix the dropdown list in Product Category List Block for nested categories ([4920](https://github.com/woocommerce/woocommerce-gutenberg-products-block/pull/4920))
- Fixed string translations within the All Products Block. ([4897](https://github.com/woocommerce/woocommerce-gutenberg-products-block/pull/4897))
- Filter By Price: Update aria values to be more representative of the actual values presented. ([4839](https://github.com/woocommerce/woocommerce-gutenberg-products-block/pull/4839))
- Fixed: Filter button from Filter Products by Attribute block is not aligned with the input field. ([4814](https://github.com/woocommerce/woocommerce-gutenberg-products-block/pull/4814))
- Remove IntersectionObserver shim in favor of dropping IE11 support. ([4808](https://github.com/woocommerce/woocommerce-gutenberg-products-block/pull/4808))

= 6.0.0 - 2021-09-28 =

#### Enhancements

- Checkout v2: The checkout now supports inner blocks that allow for greater customizability. This update also includes an optional Terms and Conditions field. ([4745](https://github.com/woocommerce/woocommerce-gutenberg-products-block/pull/4745))
- Added global styles to All Reviews, Reviews by Category and Reviews by Product blocks. Now it's possible to change the text color and font size of those blocks. ([4323](https://github.com/woocommerce/woocommerce-gutenberg-products-block/pull/4323))
- Improve the Checkout Order Summary block accessibility by making more info available to screen readers. ([4810](https://github.com/woocommerce/woocommerce-gutenberg-products-block/pull/4810))
- Update canMakePayment to receive cart as argument and make it react to changes in billingData.  Improve the performance of calculating canMakePayment after changes in the Checkout block. ([4776](https://github.com/woocommerce/woocommerce-gutenberg-products-block/pull/4776))
- Add support for extensions to filter express payment methods. ([4774](https://github.com/woocommerce/woocommerce-gutenberg-products-block/pull/4774))

#### Bug Fixes

- Checkout: Throw an exception if there is a shipping method required and one isn't selected at the time of placing an order. ([4784](https://github.com/woocommerce/woocommerce-gutenberg-products-block/pull/4784))
- Fix infinite recursion when removing an attribute filter from the Active filters block. ([4816](https://github.com/woocommerce/woocommerce-gutenberg-products-block/pull/4816))
- Show placeholder message in the shipping section when there are no rates. ([4765](https://github.com/woocommerce/woocommerce-gutenberg-products-block/pull/4765))
- Update All Reviews block so it honors 'ratings enabled' and 'show avatars' preferences. ([4764](https://github.com/woocommerce/woocommerce-gutenberg-products-block/pull/4764))
- Fix state validation if base location has a state, and the address has an optional state. ([4761](https://github.com/woocommerce/woocommerce-gutenberg-products-block/pull/4761))
- Products by Category: Moved renderEmptyResponsePlaceholder to separate method to prevent unnecessary rerender. ([4751](https://github.com/woocommerce/woocommerce-gutenberg-products-block/pull/4751))
- Fix validation message styling so they never overlap other elements. ([4734](https://github.com/woocommerce/woocommerce-gutenberg-products-block/pull/4734))
- Removed `receiveCart` method that was exposed in a couple of SlotFills by mistake. ([4730](https://github.com/woocommerce/woocommerce-gutenberg-products-block/pull/4730))
- Fix calculation of number of reviews in the Reviews by Category block. ([4729](https://github.com/woocommerce/woocommerce-gutenberg-products-block/pull/4729))

#### Documentation

- Add documentation for registerPaymentMethodExtensionCallbacks. ([4834](https://github.com/woocommerce/woocommerce-gutenberg-products-block/pull/4834))

#### Performance

- Removed `wp-blocks` dependency from several frontend scripts. ([4767](https://github.com/woocommerce/woocommerce-gutenberg-products-block/pull/4767))


= 5.9.1 - 2021-09-23 =

#### Bug fixes

- Fix infinite recursion when removing an attribute filter from the Active filters block. ([4816](https://github.com/woocommerce/woocommerce-gutenberg-products-block/pull/4816))

= 5.9.0 - 2021-09-14 =

#### Enhancements

- Add extensibility point for extensions to filter payment methods. ([4668](https://github.com/woocommerce/woocommerce-gutenberg-products-block/pull/4668))

#### Bug Fixes

- Fix Product Search block displaying incorrectly ([4740](https://github.com/woocommerce/woocommerce-gutenberg-products-block/pull/4740))


= 5.8.0 - 2021-08-31 =

#### Enhancements

- Introduced the `__experimental_woocommerce_blocks_checkout_update_order_from_request` hook to the Checkout Store API. ([4610](https://github.com/woocommerce/woocommerce-gutenberg-products-block/pull/4610))
- Add "Filter Products by Stock" block. ([4145](https://github.com/woocommerce/woocommerce-gutenberg-products-block/pull/4145))

#### Bug Fixes

- Prevent Product Category List from displaying incorrectly when used on the shop page. ([4587](https://github.com/woocommerce/woocommerce-gutenberg-products-block/pull/4587))
- Add label element to `<BlockTitle>` component. ([4585](https://github.com/woocommerce/woocommerce-gutenberg-products-block/pull/4585))

#### Documentation

- Add Extensibility info to Store API readme. ([4605](https://github.com/woocommerce/woocommerce-gutenberg-products-block/pull/4605))
- Update documentation for the snackbarNoticeVisibility filter. ([4508](https://github.com/woocommerce/woocommerce-gutenberg-products-block/pull/4508))
- Add documentation for `extensionCartUpdate` method - this allows extensions to update the client-side cart after it has been modified on the server. ([4377](https://github.com/woocommerce/woocommerce-gutenberg-products-block/pull/4377))

= 5.7.2 - 2021-09-23 =

#### Bug Fixes

- Fix infinite recursion when removing an attribute filter from the Active filters block. #4816
- Fix Product Search block displaying incorrectly. #4740

= 5.7.1 - 2021-08-30 =

#### Bug Fixes

- Disable Cart, Checkout, All Products & filters blocks from the widgets screen

= 5.7.0 - 2021-08-16 =

#### Enhancements

- Featured Category Block:  Allow user to re-select categories using the edit icon. ([4559](https://github.com/woocommerce/woocommerce-gutenberg-products-block/pull/4559))
- Checkout: Switch from select element to combobox for country and state inputs so contents are searchable. ([4369](https://github.com/woocommerce/woocommerce-gutenberg-products-block/pull/4369))

#### Bug Fixes

- Adjusted store notice class names so that error notices show the correct icons. ([4568](https://github.com/woocommerce/woocommerce-gutenberg-products-block/pull/4568))
- Fix autofill triggering validation errors for valid values in Checkout block. ([4561](https://github.com/woocommerce/woocommerce-gutenberg-products-block/pull/4561))
- Reviews by Category: Show review count instead of product count. ([4552](https://github.com/woocommerce/woocommerce-gutenberg-products-block/pull/4552))
- Add server side rendering to search block so the block can be used by non-admins. ([4551](https://github.com/woocommerce/woocommerce-gutenberg-products-block/pull/4551))
- Twenty Twenty: Fix broken sale badge left alignment. ([4549](https://github.com/woocommerce/woocommerce-gutenberg-products-block/pull/4549))
- Twenty Twenty-One: Adjust removable chip background color. ([4547](https://github.com/woocommerce/woocommerce-gutenberg-products-block/pull/4547))
- Fix handpicked product selections when a store has over 100 products. ([4534](https://github.com/woocommerce/woocommerce-gutenberg-products-block/pull/4534))
- Replace .screen-reader-text with .hidden for elements that are not relevant to screen readers. ([4530](https://github.com/woocommerce/woocommerce-gutenberg-products-block/pull/4530))

#### Various

- Performance improvements in the Cart and Checkout block extensibility points. ([4570](https://github.com/woocommerce/woocommerce-gutenberg-products-block/pull/4570))

= 5.6.0 - 2021-08-01 =

#### Enhancements

- Ensure payment method icons are constrained to a reasonable size in the Cart and Checkout blocks. ([4427](https://github.com/woocommerce/woocommerce-gutenberg-products-block/pull/4427))
- Update pagination arrows to match core. ([4364](https://github.com/woocommerce/woocommerce-gutenberg-products-block/pull/4364))

#### Bug Fixes

- Remove unnecessary margin from Cart block loading skeleton to avoid content jump. ([4498](https://github.com/woocommerce/woocommerce-gutenberg-products-block/pull/4498))
- Fixed the SKU search on the /wc/store/products endpoint. ([4469](https://github.com/woocommerce/woocommerce-gutenberg-products-block/pull/4469))
- Ensure cart totals displayed within a Panel component are aligned well and do not have extra padding. ([4435](https://github.com/woocommerce/woocommerce-gutenberg-products-block/pull/4435))
- Fix memory leak when previewing transform options for the All reviews block. ([4428](https://github.com/woocommerce/woocommerce-gutenberg-products-block/pull/4428))

#### Various

- Deprecate snackbarNotices filter in favour of snackbarNoticeVisibility to allow extensions to hide snackbar notices in the Cart and Checkout blocks. ([4417](https://github.com/woocommerce/woocommerce-gutenberg-products-block/pull/4417))

= 5.5.1, 5.4.1, 5.3.2, 5.2.1, 5.1.1, 5.0.1, 4.9.2, 4.8.1, 4.7.1, 4.6.1, 4.5.3, 4.4.3, 4.3.1, 4.2.1, 4.1.1, 4.0.1, 3.9.1, 3.8.1, 3.7.2, 3.6.1, 3.5.1, 3.4.1, 3.3.1, 3.2.1, 3.1.1, 3.0.1, 2.9.1, 2.8.1, 2.7.2, 2.6.2, 2.5.16 - 2021-07-14 =

#### Security fix

- This release fixes a critical vulnerability. More information about this can be found here: https://woocommerce.com/posts/critical-vulnerability-detected-july-2021/

= 5.5.0 - 2021-07-21 =

#### Enhancements

- Add screen reader text to price ranges. ([4367](https://github.com/woocommerce/woocommerce-gutenberg-products-block/pull/4367))
- Allow HTML in All Products Block Product Titles. ([4363](https://github.com/woocommerce/woocommerce-gutenberg-products-block/pull/4363))

#### Bug Fixes

- Ensure product grids display as intended in the editor. ([4424](https://github.com/woocommerce/woocommerce-gutenberg-products-block/pull/4424))
- Wrap components in the Cart and Checkout sidebar in a TotalsWrapper. This will ensure consistent spacing and borders are applied to items in the sidebar. ([4415](https://github.com/woocommerce/woocommerce-gutenberg-products-block/pull/4415))
- Remove `couponName` filter and replace it with `coupons` filter. ([4312](https://github.com/woocommerce/woocommerce-gutenberg-products-block/pull/4312))
- Fix filtering by product type on Store API. ([4422](https://github.com/woocommerce/woocommerce-gutenberg-products-block/pull/4422))

#### Documentation

- Add documentation for the IntegrationInterface which extension developers can use to register scripts, styles, and data with WooCommerce Blocks. ([4394](https://github.com/woocommerce/woocommerce-gutenberg-products-block/pull/4394))

= 5.4.0 - 2021-06-22 =

#### Enhancements

- Made script and style handles consistent. ([4324](https://github.com/woocommerce/woocommerce-gutenberg-products-block/pull/4324))
- Show loading state in the express payments area whilst payment is processing or the page is redirecting. ([4228](https://github.com/woocommerce/woocommerce-gutenberg-products-block/pull/4228))

#### Bug Fixes

- Fix a warning shown when fees are included in the order. ([4360](https://github.com/woocommerce/woocommerce-gutenberg-products-block/pull/4360))
- Prevent PHP notice for variable products without enabled variations. ([4317](https://github.com/woocommerce/woocommerce-gutenberg-products-block/pull/4317))

#### Various

- Allow products to be added by SKU in the Hand-picked Products block. ([4366](https://github.com/woocommerce/woocommerce-gutenberg-products-block/pull/4366))
- Add Slot in the Discounts section of the Checkout sidebar to allow third party extensions to render their own components there. ([4310](https://github.com/woocommerce/woocommerce-gutenberg-products-block/pull/4310))

= 5.3.2 - 2021-06-28 =
- Remove the ability to filter snackbar notices ([#4398](https://github.com/woocommerce/woocommerce-gutenberg-products-block/pull/4398)).

= 5.3.1 - 2021-06-15 =

- Fix Product Categories List block display in Site Editor ([#4335](https://github.com/woocommerce/woocommerce-gutenberg-products-block/pull/4335)).
- Make links in the Product Categories List block unclickable in the editor ([#4339](https://github.com/woocommerce/woocommerce-gutenberg-products-block/pull/4339)).
- Fix rating stars not being shown in the Site Editor ([#4345](https://github.com/woocommerce/woocommerce-gutenberg-products-block/pull/4345)).

= 5.3.0 - 2021-06-08 =

#### Enhancements

- Hide the Cart and Checkout blocks from the new block-based widget editor. ([4303](https://github.com/woocommerce/woocommerce-gutenberg-products-block/pull/4303))
- Provide block transforms for legacy widgets with a feature-complete block equivalent. ([4292](https://github.com/woocommerce/woocommerce-gutenberg-products-block/pull/4292))

#### Bug Fixes

- Fix some missing translations from the Cart and Checkout blocks. ([4295](https://github.com/woocommerce/woocommerce-gutenberg-products-block/pull/4295))
- Fix the flickering of the Proceed to Checkout button on quantity update in the Cart Block. ([4293](https://github.com/woocommerce/woocommerce-gutenberg-products-block/pull/4293))
- Fix a bug in which Cart Widget didn't update when adding items from the All Products block. ([4291](https://github.com/woocommerce/woocommerce-gutenberg-products-block/pull/4291))
- Fix a display issue when itemized taxes are enabled, but no products in the cart are taxable. ([4284](https://github.com/woocommerce/woocommerce-gutenberg-products-block/pull/4284))
- Fix an issue where an attempt to add an out-of-stock product to the cart was made when clicking the "Read more" button. ([4265](https://github.com/woocommerce/woocommerce-gutenberg-products-block/pull/4265))

#### Compatibility

- Add the ability for extensions to register callbacks to be executed by Blocks when the cart/extensions endpoint is hit. Extensions can now tell Blocks they need to do some server-side processing which will update the cart. ([4298](https://github.com/woocommerce/woocommerce-gutenberg-products-block/pull/4298))

#### Various

- Add Slot in the Discounts section of the cart sidebar to allow third party extensions to render their own components there. ([4248](https://github.com/woocommerce/woocommerce-gutenberg-products-block/pull/4248))
- Move `ValidatedTextInput` to the `@woocommerce/blocks-checkout` package. ([4238](https://github.com/woocommerce/woocommerce-gutenberg-products-block/pull/4238))

= 5.2.0 - 2021-05-25 =

#### Enhancements

- Added a key prop to each `CartTotalItem` within `usePaymentMethodInterface `. ([4240](https://github.com/woocommerce/woocommerce-gutenberg-products-block/pull/4240))
- Hide legacy widgets with a feature-complete block equivalent from the widget area block inserter. ([4237](https://github.com/woocommerce/woocommerce-gutenberg-products-block/pull/4237))
- Hide the All Products Block from the Customizer Widget Areas until full support is achieved. ([4225](https://github.com/woocommerce/woocommerce-gutenberg-products-block/pull/4225))
- Sync customer data during checkout with draft orders. ([4197](https://github.com/woocommerce/woocommerce-gutenberg-products-block/pull/4197))
- Update the display of the sidebar/order summary in the Cart and Checkout blocks. ([4180](https://github.com/woocommerce/woocommerce-gutenberg-products-block/pull/4180))
- Improved accessibility and styling of the controls of several of ours blocks. ([4100](https://github.com/woocommerce/woocommerce-gutenberg-products-block/pull/4100))

#### Bug Fixes

- Hide tax breakdown if the total amount of tax to be paid is 0. ([4262](https://github.com/woocommerce/woocommerce-gutenberg-products-block/pull/4262))
- Prevent Coupon code panel from appearing in stores were coupons are disabled. ([4202](https://github.com/woocommerce/woocommerce-gutenberg-products-block/pull/4202))
- For payment methods, only use `canMakePayment` in the frontend (not the editor) context. ([4188](https://github.com/woocommerce/woocommerce-gutenberg-products-block/pull/4188))
- Fix duplicate react keys in ProductDetails component. ([4187](https://github.com/woocommerce/woocommerce-gutenberg-products-block/pull/4187))
- Fix sending of confirmation emails for orders when no payment is needed. ([4186](https://github.com/woocommerce/woocommerce-gutenberg-products-block/pull/4186))
- Stopped a warning being shown when using WooCommerce Force Sells and adding a product with a Synced Force Sell to the cart. ([4182](https://github.com/woocommerce/woocommerce-gutenberg-products-block/pull/4182))

#### Various

- Move Button and Label components to `@woocommerce/blocks-checkout` package. ([4222](https://github.com/woocommerce/woocommerce-gutenberg-products-block/pull/4222))
- Add couponName filter to allow extensions to modify how coupons are displayed in the Cart and Checkout summary. ([4166](https://github.com/woocommerce/woocommerce-gutenberg-products-block/pull/4166))

= 5.1.0 - 2021-05-10 =

#### Enhancements

- Improve error message displayed when a payment method didn't have all its dependencies registered. ([4176](https://github.com/woocommerce/woocommerce-gutenberg-products-block/pull/4176))
- Improvements to `emitEventWithAbort`. ([4158](https://github.com/woocommerce/woocommerce-gutenberg-products-block/pull/4158))

#### Bug Fixes

- Fix issue in which email and phone fields are cleared when using a separate billing address. ([4162](https://github.com/woocommerce/woocommerce-gutenberg-products-block/pull/4162))

= 5.0.0 - 2021-04-28 =

#### Enhancements

- Added support to the Store API for batching requests. This allows multiple POST requests to be made at once to reduce the number of separate requests being made to the API. ([4075](https://github.com/woocommerce/woocommerce-gutenberg-products-block/pull/4075))

#### Bug Fixes

- Prevent parts of old addresses being displayed in the shipping calculator when changing countries. ([4038](https://github.com/woocommerce/woocommerce-gutenberg-products-block/pull/4038))

#### Refactor

- Rename onCheckoutBeforeProcessing to onCheckoutValidationBeforeProcessing.
- Switched to `rest_preload_api_request` for API hydration in cart and checkout blocks. ([4090](https://github.com/woocommerce/woocommerce-gutenberg-products-block/pull/4090))
- Introduced AssetsController and BlockTypesController classes (which replace Assets.php and Library.php). ([4094](https://github.com/woocommerce/woocommerce-gutenberg-products-block/pull/4094))
- Replaced usage of the `woocommerce_shared_settings` hook. This will be deprecated. ([4092](https://github.com/woocommerce/woocommerce-gutenberg-products-block/pull/4092))

 = 4.9.1 - 2021-04-13 =

 #### Bug Fixes

 - Check if Cart and Checkout are registered before removing payment methods. ([4056](https://github.com/woocommerce/woocommerce-gutenberg-products-block/pull/4056))

= 4.9.0 - 2021-04-12 =
#### Enhancements

- Added compatibility with the Google Analytics Integration. Block events, including cart and checkout, can now be tracked.

##### Dev note

Blocks are now compatible with the Google Analytics Integration: https://woocommerce.com/products/woocommerce-google-analytics/ If using Google Analytics with GTAG support (and a `G-` prefixed site ID), block events will also be tracked. This includes:

- Product searches in the Product Search Block
- Product views in the product grid blocks and All Products Block
- Add to cart events
- Cart item changes
- Checkout progress events. ([4020](https://github.com/woocommerce/woocommerce-gutenberg-products-block/pull/4020))

#### Bug Fixes

- Use font color in payment methods border. ([4051](https://github.com/woocommerce/woocommerce-gutenberg-products-block/pull/4051))
- Load translation file for JS files that has translatable strings. ([4050](https://github.com/woocommerce/woocommerce-gutenberg-products-block/pull/4050))
- Stop shipping package titles line-breaks occurring in the middle of a word. ([4049](https://github.com/woocommerce/woocommerce-gutenberg-products-block/pull/4049))
- Fixed styling issues on the cart and checkout page in Twenty(X) themes. ([4046](https://github.com/woocommerce/woocommerce-gutenberg-products-block/pull/4046))
- Fix headline alignment in the empty state of the cart block. ([4044](https://github.com/woocommerce/woocommerce-gutenberg-products-block/pull/4044))
- Fix button alignment in Featured Product and Featured Category blocks. ([4028](https://github.com/woocommerce/woocommerce-gutenberg-products-block/pull/4028))

#### Technical debt

- Removed legacy handling for SSR blocks that rendered shortcodes. ([4010](https://github.com/woocommerce/woocommerce-gutenberg-products-block/pull/4010))


= 4.8.0 - 2021-04-01 =

#### Enhancements

- Registered payment methods now have access to the `shouldSavePayment` prop in their components (which indicates whether the shopper has checked the save payment method checkbox). ([3990](https://github.com/woocommerce/woocommerce-gutenberg-products-block/pull/3990))
- Payment methods implementing the `savedTokenComponent` configuration property will now have the `onPaymentProcessing` event available to the registered component. ([3982](https://github.com/woocommerce/woocommerce-gutenberg-products-block/pull/3982))

#### Bug Fixes

- Fix customer address country saving to orders in certain circumstances. ([4013](https://github.com/woocommerce/woocommerce-gutenberg-products-block/pull/4013))
- Prevent error messages returned by the API from displaying raw HTML. ([4005](https://github.com/woocommerce/woocommerce-gutenberg-products-block/pull/4005))
- Fix the Proceed to checkout button click bug happening when the Coupon error is visible in the Cart block. ([3996](https://github.com/woocommerce/woocommerce-gutenberg-products-block/pull/3996))

= 4.7.0 - 2021-03-16 =

#### Enhancements

- A new configuration property is available to registered payment methods for additional logic handling of saved payment method tokens. ([3961](https://github.com/woocommerce/woocommerce-gutenberg-products-block/pull/3961))
- Provided billing data to payment method extensions so they can decide if payment is possible. ([3922](https://github.com/woocommerce/woocommerce-gutenberg-products-block/pull/3922))
- Prevent errant payment methods from keeping Cart and Checkout blocks from loading. ([3920](https://github.com/woocommerce/woocommerce-gutenberg-products-block/pull/3920))
- Fix block elements that don't play well with dark backgrounds. ([3887](https://github.com/woocommerce/woocommerce-gutenberg-products-block/pull/3887))

#### Bug Fixes

- Remove extra padding from payment methods with no description. ([3952](https://github.com/woocommerce/woocommerce-gutenberg-products-block/pull/3952))
- Fix "save payment" checkbox not showing for payment methods. ([3950](https://github.com/woocommerce/woocommerce-gutenberg-products-block/pull/3950))
- Fix cart preview when shipping rates are set to be hidden until an address is entered. ([3946](https://github.com/woocommerce/woocommerce-gutenberg-products-block/pull/3946))
- Sync cart item quantity if its Implicitly changed. ([3907](https://github.com/woocommerce/woocommerce-gutenberg-products-block/pull/3907))
- Fix FSE not being visible when WC Blocks was enabled. ([3898](https://github.com/woocommerce/woocommerce-gutenberg-products-block/pull/3898))
- Ensure sale badges have a uniform height in the Cart block. ([3897](https://github.com/woocommerce/woocommerce-gutenberg-products-block/pull/3897))


= 4.6.0 - 2021-03-01 =

#### Bug Fixes

- Handle out-of-stock product visibility setting in All Products block. ([3859](https://github.com/woocommerce/woocommerce-gutenberg-products-block/pull/3859))
- Show cart item subtotal instead of total in Cart and Checkout blocks ([#3905](https://github.com/woocommerce/woocommerce-gutenberg-products-block/pull/3905))
- Fix button styles in Twenty Nineteen theme. ([3862](https://github.com/woocommerce/woocommerce-gutenberg-products-block/pull/3862))
- Return correct sale/regular prices for variable products in the Store API. ([3854](https://github.com/woocommerce/woocommerce-gutenberg-products-block/pull/3854))
- Remove shadows from text buttons and gradient background from selects in some themes. ([3846](https://github.com/woocommerce/woocommerce-gutenberg-products-block/pull/3846))
- Hide Browse Shop link in cart block empty state when there is no shop page. ([3845](https://github.com/woocommerce/woocommerce-gutenberg-products-block/pull/3845))

#### Various

- StoreAPI: Inject Order and Cart Controllers into Routes. ([3871](https://github.com/woocommerce/woocommerce-gutenberg-products-block/pull/3871))
- Update Panel component class names to follow guidelines. More info can be found in our theming docs: https://github.com/woocommerce/woocommerce-gutenberg-products-block/blob/18dd54f07262b4d1dcf15561624617f824fcdc22/docs/theming/class-names-update-460.md. ([3860](https://github.com/woocommerce/woocommerce-gutenberg-products-block/pull/3860))
- Refactor block type registration to support 3rd party integrations.

##### Dev note:

An important note that internally, this release has modified how `AbstractBlock` (the base class for all of our blocks) functions, and how it loads assets. `AbstractBlock` is internal to this project and does not seem like something that would ever need to be extended by 3rd parties, but note if you are doing so for whatever reason, your implementation would need to be updated to match. ([3829](https://github.com/woocommerce/woocommerce-gutenberg-products-block/pull/3829))


= 4.5.2 - 2021-02-23 =

#### Bug Fixes

- Fix cart items showing a price of 0 when currency format didn't have decimals. ([3876](https://github.com/woocommerce/woocommerce-gutenberg-products-block/pull/3876))
- Ensure the sale badge is displayed correctly below short prices in the Cart block. ([3879](https://github.com/woocommerce/woocommerce-gutenberg-products-block/pull/3879))

= 4.5.1 - 2021-02-16 =

This release fixes an error that some users experienced when their site automatically updated to a temporarily broken version of the 4.5.0 release.

= 4.5.0 - 2021-02-16 =

#### Enhancements

- Login links on the checkout should use the account page. ([3844](https://github.com/woocommerce/woocommerce-gutenberg-products-block/pull/3844))
- Prevent checkout linking to trashed terms and policy pages. ([3843](https://github.com/woocommerce/woocommerce-gutenberg-products-block/pull/3843))
- Improved nonce logic by moving nonces to cart routes only. ([3812](https://github.com/woocommerce/woocommerce-gutenberg-products-block/pull/3812))
- If coupons become invalid between applying to a cart and checking out, show the user a notice when the order is placed. ([3810](https://github.com/woocommerce/woocommerce-gutenberg-products-block/pull/3810))
- Improve design of cart and checkout sidebars. ([3797](https://github.com/woocommerce/woocommerce-gutenberg-products-block/pull/3797))
- Improve error displayed to customers when an item's stock status changes during checkout. ([3703](https://github.com/woocommerce/woocommerce-gutenberg-products-block/pull/3703))
- Dev - Block Checkout will now respect custom address locales and custom country states via core filter hooks. ([3662](https://github.com/woocommerce/woocommerce-gutenberg-products-block/pull/3662))
- Update checkout block payment methods UI. ([3439](https://github.com/woocommerce/woocommerce-gutenberg-products-block/pull/3439))

#### Bug Fixes

- Fix JS warning if two cart products share the same name. ([3814](https://github.com/woocommerce/woocommerce-gutenberg-products-block/pull/3814))
- Align place order button to the right of the block. ([3803](https://github.com/woocommerce/woocommerce-gutenberg-products-block/pull/3803))
- Ensure special characters are displayed properly in the Cart sidebar. ([3721](https://github.com/woocommerce/woocommerce-gutenberg-products-block/pull/3721))
- Fix a bug where the total price of items did not include tax in the cart and checkout blocks. ([3851](https://github.com/woocommerce/woocommerce-gutenberg-products-block/pull/3851))

= 4.4.2 - 2021-02-05 =

### Bug Fixes

- Fix - Conflicts with 3rd Party payment method integrations. ([3796](https://github.com/woocommerce/woocommerce-gutenberg-products-block/pull/3796))

= 4.4.0 - 2021-02-02 =

#### Enhancements

- Design tweaks to the cart page which move the quantity picker below each cart item and improve usability on mobile. ([3734](https://github.com/woocommerce/woocommerce-gutenberg-products-block/pull/3734))

#### Bug Fixes

- Fix - Ensure empty categories are correctly hidden in the product categories block. ([3765](https://github.com/woocommerce/woocommerce-gutenberg-products-block/pull/3765))
- Fix - Added missing wrapper div within FeaturedCategory and FeatureProduct blocks. ([3746](https://github.com/woocommerce/woocommerce-gutenberg-products-block/pull/3746))
- Fix - Set correct text color in BlockErrorBoundary notices. ([3738](https://github.com/woocommerce/woocommerce-gutenberg-products-block/pull/3738))
- Hidden cart item meta data will not be rendered in the Cart and Checkout blocks. ([3732](https://github.com/woocommerce/woocommerce-gutenberg-products-block/pull/3732))
- Fix - Improved accessibility of product image links in the products block by using correct aria tags and hiding empty image placeholders. ([3722](https://github.com/woocommerce/woocommerce-gutenberg-products-block/pull/3722))
- Add missing aria-label for stars image in the review-list-item component. ([3706](https://github.com/woocommerce/woocommerce-gutenberg-products-block/pull/3706))
- Prevent "Nonce is invalid" error when going back to a page with the products block using the browser back button. ([3770](https://github.com/woocommerce/woocommerce-gutenberg-products-block/pull/3770))

#### compatibility

- Hide the All Products Block from the new Gutenberg Widget Areas until full support is achieved. ([3737](https://github.com/woocommerce/woocommerce-gutenberg-products-block/pull/3737))
- Legacy `star-rating` class name has been removed from Product rating block (inside All Products block). That element is still selectable with the `.wc-block-components-product-rating` class name. ([3717](https://github.com/woocommerce/woocommerce-gutenberg-products-block/pull/3717))

= 4.3.0 - 2021-01-20 =

#### Bug Fixes

- Update input colors and alignment. ([3597](https://github.com/woocommerce/woocommerce-gutenberg-products-block/pull/3597))

#### Enhancements

- Store API - Fix selected rate in cart shipping rates response. ([3680](https://github.com/woocommerce/woocommerce-gutenberg-products-block/pull/3680))
- Create get_item_responses_from_schema abstraction. ([3679](https://github.com/woocommerce/woocommerce-gutenberg-products-block/pull/3679))
- Show itemized fee rows in the cart/checkout blocks. ([3678](https://github.com/woocommerce/woocommerce-gutenberg-products-block/pull/3678))
- Extensibility: Show item data in Cart and Checkout blocks and update the variation data styles. ([3665](https://github.com/woocommerce/woocommerce-gutenberg-products-block/pull/3665))
- Introduce SlotFill for Sidebar. ([3361](https://github.com/woocommerce/woocommerce-gutenberg-products-block/pull/3361))

= 4.2.0 - 2021-01-06 =

#### Bug Fixes

- Fix an error that was blocking checkout with some user saved payment methods. ([3627](https://github.com/woocommerce/woocommerce-gutenberg-products-block/pull/3627))

= 4.1.0 - 2020-12-24 =

#### Enhancements

- Add the ability to directly upload an image in Featured Category and Featured Product blocks. ([3579](https://github.com/woocommerce/woocommerce-gutenberg-products-block/pull/3579))
- Fix coupon code button height not adapting to the font size. ([3575](https://github.com/woocommerce/woocommerce-gutenberg-products-block/pull/3575))
- Fixed Coupon Code panel not expanding/contracting in some themes. ([3569](https://github.com/woocommerce/woocommerce-gutenberg-products-block/pull/3569))
- Fix: Added fallback styling for screen reader text. ([3557](https://github.com/woocommerce/woocommerce-gutenberg-products-block/pull/3557))

#### Bug Fixes

- Fix nonce issues when adding product to cart from All Products. ([3598](https://github.com/woocommerce/woocommerce-gutenberg-products-block/pull/3598))
- Fix bug inside Product Search in the editor. ([3578](https://github.com/woocommerce/woocommerce-gutenberg-products-block/pull/3578))
- Fix console warnings in WordPress 5.6. ([3577](https://github.com/woocommerce/woocommerce-gutenberg-products-block/pull/3577))
- Fixed text visibility in select inputs when using Twenty Twenty-One theme's dark mode. ([3554](https://github.com/woocommerce/woocommerce-gutenberg-products-block/pull/3554))
- Fix product list images skewed in Widgets editor. ([3553](https://github.com/woocommerce/woocommerce-gutenberg-products-block/pull/3553))
- Add address validation to values posted to the Checkout via StoreApi. ([3552](https://github.com/woocommerce/woocommerce-gutenberg-products-block/pull/3552))
- Fix Fees not visible in Cart & Checkout blocks when order doesn't need shipping. ([3521](https://github.com/woocommerce/woocommerce-gutenberg-products-block/pull/3521))

#### compatibility

- Fix All Products block edit screen. ([3547](https://github.com/woocommerce/woocommerce-gutenberg-products-block/pull/3547))

#### wp dependency

- Removed compatibility with packages in WordPress 5.3. ([3541](https://github.com/woocommerce/woocommerce-gutenberg-products-block/pull/3541))
- Bumped the minimum WP required version to 5.4. ([3537](https://github.com/woocommerce/woocommerce-gutenberg-products-block/pull/3537))

= 4.0.0 - 2020-12-07 =

#### Enhancements

- Dev: Change register_endpoint_data to use an array of params instead of individual params. ([3478](https://github.com/woocommerce/woocommerce-gutenberg-products-block/pull/3478))
- Dev: Expose store/cart via ExtendSchema to extensions. ([3445](https://github.com/woocommerce/woocommerce-gutenberg-products-block/pull/3445))
- Dev: Added formatting classes to the Store API for extensions to consume.

#### Bug Fixes

- Checkout block: Prevent `Create an account` from creating up a user account if the order fails coupon validation. ([3423](https://github.com/woocommerce/woocommerce-gutenberg-products-block/pull/3423))
- Make sure cart is initialized before the CartItems route is used in the Store API. ([3488](https://github.com/woocommerce/woocommerce-gutenberg-products-block/pull/3488))
- Fix notice close button color in Twenty Twenty One dark mode. ([3472](https://github.com/woocommerce/woocommerce-gutenberg-products-block/pull/3472))
- Remove held stock for a draft order if an item is removed from the cart. ([3468](https://github.com/woocommerce/woocommerce-gutenberg-products-block/pull/3468))
- Ensure correct alignment of checkout notice's dismiss button. ([3455](https://github.com/woocommerce/woocommerce-gutenberg-products-block/pull/3455))
- Fixed a bug in Checkout block (Store API) causing checkout to fail when using an invalid coupon and creating an account.
- Checkout block: Correctly handle cases where the order fails with an error (e.g. invalid coupon) and a new user account is created. ([3429](https://github.com/woocommerce/woocommerce-gutenberg-products-block/pull/3429))
- Dev: Refactored and reordered Store API checkout processing to handle various edge cases and better support future extensibility. ([3454](https://github.com/woocommerce/woocommerce-gutenberg-products-block/pull/3454))

= 3.9.0 - 2020-11-25 =

See release post [here](https://developer.woocommerce.com/?p=8234)

#### Enhancements

- Expose `discount_type` in Store API coupon endpoints. ([3399](https://github.com/woocommerce/woocommerce-gutenberg-products-block/pull/3399))
- Exclude checkout-draft orders from WC Admin reports and My Account > Orders. ([3379](https://github.com/woocommerce/woocommerce-gutenberg-products-block/pull/3379))

#### Bug Fixes

- Hide spinner on cart block's "Proceed to Checkout" link when page unloads. ([3436](https://github.com/woocommerce/woocommerce-gutenberg-products-block/pull/3436))
- Fixed express payment methods processing not completing when Stripe payment method active. ([3432](https://github.com/woocommerce/woocommerce-gutenberg-products-block/pull/3432))
- Refresh PaymentRequest after cancelling payment to prevent addresses remaining populated on repeat attempts. ([3430](https://github.com/woocommerce/woocommerce-gutenberg-products-block/pull/3430))
- Ensure "Add a note to your order" section is styled correctly when disabled. ([3427](https://github.com/woocommerce/woocommerce-gutenberg-products-block/pull/3427))
- Prevent checkout step heading text overlapping actual heading on small viewports. ([3425](https://github.com/woocommerce/woocommerce-gutenberg-products-block/pull/3425))
- Improve Stripe payment request API payment method availability. ([3424](https://github.com/woocommerce/woocommerce-gutenberg-products-block/pull/3424))
- Stop hidden products from being linked in cart and checkout blocks. ([3415](https://github.com/woocommerce/woocommerce-gutenberg-products-block/pull/3415))
- Show Express Payment Method Error Notices after Payment Failure. ([3410](https://github.com/woocommerce/woocommerce-gutenberg-products-block/pull/3410))
- Fix cart block `isLarge` console error in the editor when running WordPress 5.6 beta. ([3408](https://github.com/woocommerce/woocommerce-gutenberg-products-block/pull/3408))
- Fix: Orders not being placed when paying with an Express payment method from the Cart block. ([3403](https://github.com/woocommerce/woocommerce-gutenberg-products-block/pull/3403))
- Fix incorrect usage of static method in Stripe payment method integration. ([3400](https://github.com/woocommerce/woocommerce-gutenberg-products-block/pull/3400))
- Cart and checkout should respect the global "Hide shipping costs until an address is entered" setting. ([3383](https://github.com/woocommerce/woocommerce-gutenberg-products-block/pull/3383))
- Sync shipping address with billing address when shipping address fields are disabled. This fixes a bug where taxes would not reflect changes in billing address when they are set to be calculated from billing address ([3358](https://github.com/woocommerce/woocommerce-gutenberg-products-block/pull/3358))

#### refactor

- Support a plain js configuration argument to payment method registration APIs. ([3404](https://github.com/woocommerce/woocommerce-gutenberg-products-block/pull/3404))

= 3.8.0 - 2020-11-10 =
- Show the phone number field in the billing section when shipping is disabled in settings. ([3376](https://github.com/woocommerce/woocommerce-gutenberg-products-block/pull/3376))
- Add new doc referencing feature flags and experimental interfaces. ([3348](https://github.com/woocommerce/woocommerce-gutenberg-products-block/pull/3348))
- Add __experimental_woocommerce_blocks_checkout_order_processed action. ([3238](https://github.com/woocommerce/woocommerce-gutenberg-products-block/pull/3238))

= 3.7.1 - 2020-11-05 =

#### Bug Fixes
- Ensure that accounts are not created via checkout block request if account registration is disabled for WooCommerce ([#3371](https://github.com/woocommerce/woocommerce-gutenberg-products-block/pull/3371))

= 3.7.0 - 2020-10-29 =

#### Enhancements

- Allow shoppers to sign-up for an account from the Checkout block. ([3331](https://github.com/woocommerce/woocommerce-gutenberg-products-block/pull/3331))
- Standardise & refactor colors scss to align with Gutenberg colors and WooCommerce brand. ([3300](https://github.com/woocommerce/woocommerce-gutenberg-products-block/pull/3300))

#### Bug Fixes

- Fix PHP 8 error when argument is not invocable in AssetsDataRegistry::Add_data. ([3315](https://github.com/woocommerce/woocommerce-gutenberg-products-block/pull/3315))
- Improve layout of Cart block line item quantity selector & price on smaller screens. ([3299](https://github.com/woocommerce/woocommerce-gutenberg-products-block/pull/3299))
- Correctly process orders with $0 total (e.g. via coupon) in Checkout block. ([3298](https://github.com/woocommerce/woocommerce-gutenberg-products-block/pull/3298))
- Respect Enable Taxes setting for checkout block taxes display. ([3291](https://github.com/woocommerce/woocommerce-gutenberg-products-block/pull/3291))
- Fix 3D secure payment errors. ([3272](https://github.com/woocommerce/woocommerce-gutenberg-products-block/pull/3272))
- Show current selected attributes when re-edit Products by Attribute block. ([3185](https://github.com/woocommerce/woocommerce-gutenberg-products-block/pull/3185))


= 3.6.0 - 2020-10-12 =

#### Bug Fixes

- Make 'retry' property on errors from checkoutAfterProcessingWithSuccess/Error observers default to true if it's undefined. ([3261](https://github.com/woocommerce/woocommerce-gutenberg-products-block/pull/3261))
- Ensure new payment methods are only displayed when no saved payment method is selected. ([3247](https://github.com/woocommerce/woocommerce-gutenberg-products-block/pull/3247))
- Load WC Blocks CSS after editor CSS. ([3219](https://github.com/woocommerce/woocommerce-gutenberg-products-block/pull/3219))
- Restore saved payment method data after closing an express payment method. ([3210](https://github.com/woocommerce/woocommerce-gutenberg-products-block/pull/3210))

#### refactor

- Don't load contents of payment method hidden tabs. ([3227](https://github.com/woocommerce/woocommerce-gutenberg-products-block/pull/3227))

= 3.5.0 - 2020-09-29 =

#### Bug Fixes

- Use light default background colour for country/state dropdowns. ([3189](https://github.com/woocommerce/woocommerce-gutenberg-products-block/pull/3189))
- Fix broken Express Payment Method use in the Checkout block for logged out or incognito users. ([3165](https://github.com/woocommerce/woocommerce-gutenberg-products-block/pull/3165))
- Fix State label for Spain. ([3147](https://github.com/woocommerce/woocommerce-gutenberg-products-block/pull/3147))
- Don't throw an error when registering a payment method fails. ([3134](https://github.com/woocommerce/woocommerce-gutenberg-products-block/pull/3134))

#### refactor

- Use noticeContexts from useEmitResponse instead of hardcoded values. ([3161](https://github.com/woocommerce/woocommerce-gutenberg-products-block/pull/3161))

= 3.4.0 - 2020-09-14 =

#### Bug Fixes

- Ensure shopper saved card is used as default payment method (default was being overwritten in some circumstances). ([3131](https://github.com/woocommerce/woocommerce-gutenberg-products-block/pull/3131))
- Fix Cart & Checkout sidebar layout broken in some themes. ([3111](https://github.com/woocommerce/woocommerce-gutenberg-products-block/pull/3111))
- Fix product reviews schema date fields to use new (WP 5.5) `date-time` format. ([3109](https://github.com/woocommerce/woocommerce-gutenberg-products-block/pull/3109))
- Use wp_login_url instead of hardcoding login path. ([3090](https://github.com/woocommerce/woocommerce-gutenberg-products-block/pull/3090))
- Fix an issue with COD not showing when first enabled. ([3088](https://github.com/woocommerce/woocommerce-gutenberg-products-block/pull/3088))
- Fix JS console error when COD is enabled and no shipping method is available. ([3086](https://github.com/woocommerce/woocommerce-gutenberg-products-block/pull/3086))

#### performance

- Create DebouncedValidatedTextInput component. ([3108](https://github.com/woocommerce/woocommerce-gutenberg-products-block/pull/3108))

#### refactor

- Merge ProductPrice atomic block and component. ([3065](https://github.com/woocommerce/woocommerce-gutenberg-products-block/pull/3065))

= 3.3.0 - 2020-09-02 =
- enhancement: Show express payment methods in the Cart block (for example: Apple Pay, Chrome Pay). [3004](https://github.com/woocommerce/woocommerce-gutenberg-products-block/pull/3004)
- bug: Fix alignment of discounted prices in Cart block. [3047](https://github.com/woocommerce/woocommerce-gutenberg-products-block/pull/3047)
- bug: Fix an issue with products sold individually (max of 1 per cart); the Checkout block now shows a notice if shopper attempts to add another instance of product via an `add-to-cart` link. [2854](https://github.com/woocommerce/woocommerce-gutenberg-products-block/pull/2854)
- bug: Fixed styling options of the Product Title block (in All Products). [3095](https://github.com/woocommerce/woocommerce-gutenberg-products-block/pull/3095)


= 3.2.0 - 2020-08-17 =
- Fix 'Add new product' link in All Products block 'No products' placeholder. [#2961](https://github.com/woocommerce/woocommerce-gutenberg-products-block/pull/2961)
- Fix an undefined variable PHP notice related to Product REST API. [#2962](https://github.com/woocommerce/woocommerce-gutenberg-products-block/pull/2962)
- Fixed an issue that was making some blocks not to render correctly in the Empty cart template. [#2904](https://github.com/woocommerce/woocommerce-gutenberg-products-block/pull/2904)
- Fixed an issue that was not rendering the Checkout block in editor when guest checkout was not allowed. [#2958](https://github.com/woocommerce/woocommerce-gutenberg-products-block/pull/2958)
- Hide the discount badge from Cart items if the value is negative. [#2955](https://github.com/woocommerce/woocommerce-gutenberg-products-block/pull/2955)
- Hide saved payment methods if their payment gateway has been disabled. [#2975](https://github.com/woocommerce/woocommerce-gutenberg-products-block/pull/2975)
- Add dark colors and background for Cart & Checkout blocks inputs to support dark backgrounds. [#2981](https://github.com/woocommerce/woocommerce-gutenberg-products-block/pull/2981)
- The Checkout block allows customers to introduce an order note. This feature can be disabled in the editor. [#2877](https://github.com/woocommerce/woocommerce-gutenberg-products-block/pull/2877)
- Cart and Checkout form fields show autocapitalized keyboard on mobile depending on the expected value. [#2884](https://github.com/woocommerce/woocommerce-gutenberg-products-block/pull/2884)
- Cart and Checkout will show a live preview inside the block inserter and style selector. [#2992](https://github.com/woocommerce/woocommerce-gutenberg-products-block/pull/2992)
- Payment gateways are shown in the correct order as configured in store settings. [#2934](https://github.com/woocommerce/woocommerce-gutenberg-products-block/pull/2934)
- Fix a cosmetic issue where payment form errors sometimes overlap with card icons. [#2977](https://github.com/woocommerce/woocommerce-gutenberg-products-block/pull/2977)
- Fixes a styling issue in the Product Search block in the editor. [#3014](https://github.com/woocommerce/woocommerce-gutenberg-products-block/pull/3014)
- Improved focus styles of error states on form elements. [#2974](https://github.com/woocommerce/woocommerce-gutenberg-products-block/pull/2974)
- Removed generic icons for Check and Stripe Credit Card to reduce visual clutter in Checkout block. [#2968](https://github.com/woocommerce/woocommerce-gutenberg-products-block/pull/2968)
- Deprecate wc.wcSettings.setSetting function. [#3010](https://github.com/woocommerce/woocommerce-gutenberg-products-block/pull/3010)
- Improve behaviour of draft order cleanup to account for clobbered custom shop order status. [#2912](https://github.com/woocommerce/woocommerce-gutenberg-products-block/pull/2912)

= 3.1.0 - 2020-07-29 =
- Fix missing permissions_callback arg in StoreApi route definitions [#2926](https://github.com/woocommerce/woocommerce-gutenberg-products-block/pull/2926)
- Fix 'Product Summary' in All Products block is not pulling in the short description of the product [#2913](https://github.com/woocommerce/woocommerce-gutenberg-products-block/issues/2913)
- dev: Add query filter when searching for a table [#2886](https://github.com/woocommerce/woocommerce-gutenberg-products-block/pull/2886) 👏 @pkelbert
- All Products block: Can now customize text size, color and alignment in Product Title child block. Heading level option is now in block toolbar (was in settings sidebar). [#2860](https://github.com/woocommerce/woocommerce-gutenberg-products-block/pull/2860)
- All Products block: Can now customize text size, color and alignment in Product Price child block. [#2881](https://github.com/woocommerce/woocommerce-gutenberg-products-block/pull/2881)

= 3.0.0 - 2020-07-20 =

This release adds support for Cash on Delivery and Bank Transfer payment methods to the checkout block. The payment method extension api for the blocks [has an update to the `canMakePayment` property](https://woocommerce.wordpress.com/?p=6830).

- build: Updated the `automattic/jetpack-autoloader` package to the 2.0 branch. [#2847](https://github.com/woocommerce/woocommerce-gutenberg-products-block/pull/2847)
- enhancement: Add support for the Bank Transfer (BACS) payment method in the Checkout block. [#2821](https://github.com/woocommerce/woocommerce-gutenberg-products-block/pull/2821)
- enhancement: Several improvements to make Credit Card input fields display more consistent across different themes and viewport sizes. [#2869](https://github.com/woocommerce/woocommerce-gutenberg-products-block/pull/2869)
- enhancement: Cart and Checkout blocks show a notification for products on backorder. [#2833](https://github.com/woocommerce/woocommerce-gutenberg-products-block/pull/2833)
- enhancement: Chip styles of the Filter Products by Attribute and Active Filters have been updated to give a more consistent experience. [#2765](https://github.com/woocommerce/woocommerce-gutenberg-products-block/pull/2765)
- enhancement: Add protection for rogue filters on order queries when executing cleanup draft orders logic. [#2874](https://github.com/woocommerce/woocommerce-gutenberg-products-block/pull/2874)
- enhancement: Extend payment gateway extension API so gateways (payment methods) can dynamically disable (hide), based on checkout or order data (such as cart items or shipping method). For example, `Cash on Delivery` can limit availability to specific shipping methods only. [#2840](https://github.com/woocommerce/woocommerce-gutenberg-products-block/pull/2840) [DN]
- enhancement: Support `Cash on Delivery` core payment gateway in the Checkout block. #2831 [#2831](https://github.com/woocommerce/woocommerce-gutenberg-products-block/pull/2831)
- performance: Don't load shortcode Cart and Checkout scripts when using the blocks. [#2842](https://github.com/woocommerce/woocommerce-gutenberg-products-block/pull/2842)
- performance: Scripts only relevant to the frontend side of blocks are no longer loaded in the editor. [#2788](https://github.com/woocommerce/woocommerce-gutenberg-products-block/pull/2788)
- performance: Lazy Loading Atomic Components [#2777](https://github.com/woocommerce/woocommerce-gutenberg-products-block/pull/2777)
- performance: Fix unnecessary checks happening for wc_reserved_stock table in site dashboard [#2895](https://github.com/woocommerce/woocommerce-gutenberg-products-block/pull/2895)
- refactor: Remove dashicon classes [#2848](https://github.com/woocommerce/woocommerce-gutenberg-products-block/pull/2848)

= 2.9.0 - 2020-07-07 =
- bug: Correctly sort translated state and country drop-down menus in Checkout block. [#2779](https://github.com/woocommerce/woocommerce-gutenberg-products-block/pull/2779)
- dev: Add storybook story for icon library. [#2787](https://github.com/woocommerce/woocommerce-gutenberg-products-block/pull/2787)
- dev: Add custom jest matcher `toRenderBlock`, used for confirming blocks are available in the editor in e2e tests. [#2780](https://github.com/woocommerce/woocommerce-gutenberg-products-block/pull/2780)
- dev: Use consistent Button component in Cart & Checkout blocks. [#2781](https://github.com/woocommerce/woocommerce-gutenberg-products-block/pull/2781)


= 2.8.0 - 2020-06-23 =
- bug: Cart and Checkout blocks display shipping methods with tax rates if that's how it's set in the settings. [#2748](https://github.com/woocommerce/woocommerce-gutenberg-products-block/pull/2748)
- bug: Fix an error appearing in the Product Categories List block with _Full Width_ align. [#2700](https://github.com/woocommerce/woocommerce-gutenberg-products-block/pull/2700)
- enhancement: Added aria-expanded attribute to Change address button in the Cart block [#2603](https://github.com/woocommerce/woocommerce-gutenberg-products-block/pull/2603)
- enhancement: Fix updating the `wc_reserve_stock` stock_quantity value after making changes to the cart inbetween checkouts. [#2747](https://github.com/woocommerce/woocommerce-gutenberg-products-block/pull/2747)
- enhancement: Remove background color from Express checkout title. [#2704](https://github.com/woocommerce/woocommerce-gutenberg-products-block/pull/2704)
- enhancement: Several style enhancements to the Cart and Checkout blocks sidebar. [#2694](https://github.com/woocommerce/woocommerce-gutenberg-products-block/pull/2694)
- enhancement: The Cart and Checkout blocks now use the font colors provided by the theme. [#2745](https://github.com/woocommerce/woocommerce-gutenberg-products-block/pull/2745)
- enhancement: Update some class names to match the new guidelines. [Check the docs](https://github.com/woocommerce/woocommerce-gutenberg-products-block/blob/trunk/docs/theming/README.md) in order to see which class names have been updated. [#2691](https://github.com/woocommerce/woocommerce-gutenberg-products-block/pull/2691) [DN]
- enhancement: Blocks now respect the product image cropping settings. For the All Products block, the user can switch between the cropped thumbnail and the full size image. [#2755](https://github.com/woocommerce/woocommerce-gutenberg-products-block/pull/2755)

= 2.7.1 - 2020-06-16 =
- bug: Use IE11 friendly code for Dashicon component replacement. [#2708](https://github.com/woocommerce/woocommerce-gutenberg-products-block/pull/2708)
- bug: Fix PHP warnings produced by StoreAPI endpoints when variations have no prices. [#2722](https://github.com/woocommerce/woocommerce-gutenberg-products-block/pull/2722)
- bug: Fix missing scoped variable in closure and missing schema definitions. [#2724](https://github.com/woocommerce/woocommerce-gutenberg-products-block/pull/2724)
- bug: Fix undefined index notice for query_type on the product collection data endpoint. [#2723](https://github.com/woocommerce/woocommerce-gutenberg-products-block/pull/2723)

= 2.7.0 - 2020-06-09 =
- bug: Fix bug in Checkout block preventing a retry of credit card payment when first credit card used fails and a new one is tried. [#2655](https://github.com/woocommerce/woocommerce-gutenberg-products-block/pull/2655)
- bug: Avoid some theme style properties leaking into the Cart and Checkout select controls. [#2647](https://github.com/woocommerce/woocommerce-gutenberg-products-block/pull/2647)
- bug: Fixes to the product grid blocks in Twenty Twenty: discounted prices are no longer underlined and the On Sale badge is correctly positioned in the All Products block. [#2573](https://github.com/woocommerce/woocommerce-gutenberg-products-block/pull/2573)
- bug: Improved alignment of credit card validation error messages. [#2662](https://github.com/woocommerce/woocommerce-gutenberg-products-block/pull/2662)
- bug: Show the 'No shipping methods' placeholder in the editor with the _Checkout_ block if there are shipping methods but all of them are disabled. [#2543](https://github.com/woocommerce/woocommerce-gutenberg-products-block/pull/2543)
- enhancement: Filter block font sizes have been adjusted to be in line with other blocks. [#2594](https://github.com/woocommerce/woocommerce-gutenberg-products-block/pull/2594)
- enhancement: The All Products block and the other product grid blocks now share more styles and the markup is more similar (see release post or docs to learn how to undo this change). [#2428](https://github.com/woocommerce/woocommerce-gutenberg-products-block/pull/2428) [DN]
- enhancement: The Cart and Checkout blocks now use the heading styles provided by the theme. [#2597](https://github.com/woocommerce/woocommerce-gutenberg-products-block/pull/2597)
- enhancement: The Cart block titles have been merged into one. [#2615](https://github.com/woocommerce/woocommerce-gutenberg-products-block/pull/2615)
- enhancement: The item count badges of the Checkout block have been updated so it looks better in light & dark backgrounds. [#2619](https://github.com/woocommerce/woocommerce-gutenberg-products-block/pull/2619)
- enhancement: Checkout step progress indicator design has been updated to match the theme headings style. [#2649](https://github.com/woocommerce/woocommerce-gutenberg-products-block/pull/2649)
- performance: Reduce bundlesize of blocks using @wordpress/components directly. [#2664](https://github.com/woocommerce/woocommerce-gutenberg-products-block/pull/2664)

= 2.6.1 - 2020-06-01 =

- fix: Updated the wc_reserved_stock table for compatibility with versions of MySql < 5.6.5. [#2590](https://github.com/woocommerce/woocommerce-gutenberg-products-block/pull/2590)

= 2.6.0 - 2020-05-25 =
**New Blocks**

The Cart and Checkout blocks are released in this version for wider review and testing as a part of our consideration for including them in WooCommerce Core. You can read more [about these blocks here](https://woocommerce.wordpress.com/?p=6384).

Also, note that we are aware of the increased file size for the All Products and Filter blocks frontend JavaScript. It is from some dependency changes. We will be addressing this in the next release.

You can read [more about the release here](https://woocommerce.wordpress.com/?p=6577)

- bug: Add placeholder to the on-sale products block when no results are found. [#1519](https://github.com/woocommerce/woocommerce-gutenberg-products-block/pull/1519)
- bug: Added correct ellipsis character in Product Search block [#1672](https://github.com/woocommerce/woocommerce-gutenberg-products-block/pull/1672)
- bug: If product is changed for featured product block, update the link in the button. [#1894](https://github.com/woocommerce/woocommerce-gutenberg-products-block/pull/1894)
- bug: Import from `@woocommerce/settings` in `@woocommerce/block-settings` [#2330](https://github.com/woocommerce/woocommerce-gutenberg-products-block/pull/2330)
- dev: Accessibility of the All Products block and filter blocks has been improved. [#1656](https://github.com/woocommerce/woocommerce-gutenberg-products-block/pull/1656)
- dev: All Products Block: Update sorting labels to match frontend options [#2462](https://github.com/woocommerce/woocommerce-gutenberg-products-block/pull/2462)
- dev: Change PropType validation for Icon component [#1737](https://github.com/woocommerce/woocommerce-gutenberg-products-block/pull/1737)
- dev: Changed default rows and columns for product grid blocks to 3x3. [#1613](https://github.com/woocommerce/woocommerce-gutenberg-products-block/pull/1613)
- dev: Check for instance of WP_Block in render_callback [#2258](https://github.com/woocommerce/woocommerce-gutenberg-products-block/pull/2258)
- dev: Devs: `ENABLE_REVIEW_RATING` setting was renamed to `REVIEW_RATINGS_ENABLED` and now it also verifies reviews are enabled, to better match WooCommerce API. [#1374](https://github.com/woocommerce/woocommerce-gutenberg-products-block/pull/1374)
- dev: Fix price filtering when stored prices do not match displayed prices (determined by tax settings). [#1612](https://github.com/woocommerce/woocommerce-gutenberg-products-block/pull/1612)
- dev: HTML editing is no longer supported in several blocks. [#1395](https://github.com/woocommerce/woocommerce-gutenberg-products-block/pull/1395)
- dev: Implement __experimentalCreateInterpolateElement for translations. [#1736](https://github.com/woocommerce/woocommerce-gutenberg-products-block/pull/1736)
- dev: Load WooCommerce Core translations for 'Sale!' and some other strings if translations are unavailable for WooCommerce Blocks. [#1694](https://github.com/woocommerce/woocommerce-gutenberg-products-block/pull/1694)
- dev: Prevent data hydration on REST requests [#2176](https://github.com/woocommerce/woocommerce-gutenberg-products-block/pull/2176)
- dev: Show relationship between terms in the active filters block. [#1630](https://github.com/woocommerce/woocommerce-gutenberg-products-block/pull/1630)
- dev: Table creation validation for install routine [#2287](https://github.com/woocommerce/woocommerce-gutenberg-products-block/pull/2287)
- dev: Update the icons used in the blocks. [#1644](https://github.com/woocommerce/woocommerce-gutenberg-products-block/pull/1644)
- enhancement: Add dropdown display style to Filter Products by Attribute block. [#1255](https://github.com/woocommerce/woocommerce-gutenberg-products-block/pull/1255)
- enhancement: Add option to display a Filter button to Filter Products by Attribute block. [#1332](https://github.com/woocommerce/woocommerce-gutenberg-products-block/pull/1332)
- enhancement: Add support for image for product categories block [#1739](https://github.com/woocommerce/woocommerce-gutenberg-products-block/pull/1739)
- enhancement: An error notice will be shown in All Product if the customer is trying to add a product above stock or sold individually. [#2278](https://github.com/woocommerce/woocommerce-gutenberg-products-block/pull/2278)
- performance: Improvements to REST API performance [#2248](https://github.com/woocommerce/woocommerce-gutenberg-products-block/pull/2248)
- performance: Avoid loading Assets API during REST requests [#2286](https://github.com/woocommerce/woocommerce-gutenberg-products-block/pull/2286)

= 2.5.16 - 2020-04-07 =
- Performance: Use the latest version of Jetpack Autoloader. #2132

= 2.5.15 - 2020-03-31 =
- Fix broken product grid blocks styles in old versions of WordPress. #2000

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
- Make price slider accurately represent the selected price. #1453

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
- Prevent Filter Products by Attribute block hiding non-matching options when Query Type is set to OR. #1339
- Fix price slider layout in narrow columns #1231

= 2.5.2 - 2019-12-02 =
- Fixed a PHP Notice in Featured Category Block when the category is invalid. #1291 👏 @strategio
- Filter Products by Attribute block now uses the attribute label instead of the slug to set the default title. #1271
- Fix Filter Products by Price slider being reset to 0-0 when filters were cleared from the Active Filters block. #1278
- Don't enqueue wcSettings unless the route requires it. #1292
- Add `getAdminLink()` utility method. #1244

= 2.5.1 - 2019-11-26 =
- Fix Products by Tag, Products by Attribute and Hand-picked Products blocks showing an invalid attributes error. #1254
- Fix the price slider updating instantly even when filter button was enabled. #1228
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
- Officially deprecate NPM package `@woocommerce/block-library`.
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
- Enhancement: Allow hidden products in Hand-picked Products block.
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
- Fix: Fix author access to API for Hand-picked Products block.

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
