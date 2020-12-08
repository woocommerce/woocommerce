=== WooCommerce Blocks ===
Contributors: automattic, woocommerce, claudiulodro, tiagonoronha, jameskoster, ryelle, levinmedia, aljullu, mikejolley, nerrad, joshuawold, assassinateur, haszari
Tags: gutenberg, woocommerce, woo commerce, products, blocks, woocommerce blocks
Requires at least: 5.3
Tested up to: 5.6
Requires PHP: 7.0
Stable tag: 4.0.0
License: GPLv3
License URI: https://www.gnu.org/licenses/gpl-3.0.html

== Description ==

WooCommerce Blocks are the easiest, most flexible way to display your products on posts and pages!

For more information on what blocks are available, and how to use them, check out the official documentation: https://docs.woocommerce.com/document/woocommerce-blocks/

**Note: Feature plugin for WooCommerce + Gutenberg. This plugin serves as a space to iterate and explore new Blocks and updates to existing blocks for WooCommerce, and how WooCommerce might work with the block editor.**

Use this plugin if you want access to the bleeding edge of available blocks for WooCommerce. However, stable blocks are bundled into WooCommerce, and can be added from the "WooCommerce" section in the block inserter.

- **Featured Product Block**
- **Featured Category Block**
- **Hand-Picked products Block**
- **Best Selling Products Block**
- **Top Rated Products Block**
- **Newest Products Block**
- **On Sale Products Block**
- **Products by Category Block**
- **Products by Tag Block**
- **Products by Attribute Block**
- **Product Categories List Block**
- **Reviews by Product**
- **Reviews by Category**
- **All Reviews**
- **Product Search**
- **All Products**
- **Filter Products by Price**
- **Filter Products by Attribute**
- **Active Product Filters**
- **Cart**
- **Checkout**

== Getting Started ==

= Minimum Requirements =

* WordPress 5.3 or greater
* WooCommerce 4.3 or greater
* PHP version 7.0 or greater (PHP 7.4 or greater is recommended)
* MySQL version 5.6 or greater

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

= 4.0.0 - 2020-12-07 =

#### Enhancements

- Dev: Change register_endpoint_data to use an array of params instead of individual params. ([3478](https://github.com/woocommerce/woocommerce-gutenberg-products-block/pull/3478))
- Dev: Expose store/cart via ExtendRestApi to extensions. ([3445](https://github.com/woocommerce/woocommerce-gutenberg-products-block/pull/3445))
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
- Fix Products by Tag, Products by Attribute and Handpicked products blocks showing an invalid attributes error. #1254
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
