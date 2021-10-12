=== WooCommerce Blocks ===
Contributors: automattic, woocommerce, claudiulodro, tiagonoronha, jameskoster, ryelle, levinmedia, aljullu, mikejolley, nerrad, joshuawold, assassinateur, haszari, mppfeiffer, nielslange, opr18, ralucastn, tjcafferkey
Tags: gutenberg, woocommerce, woo commerce, products, blocks, woocommerce blocks
Requires at least: 5.8
Tested up to: 5.8
Requires PHP: 7.0
Stable tag: 6.1.0
License: GPLv3
License URI: https://www.gnu.org/licenses/gpl-3.0.html

== Description ==

WooCommerce Blocks are the easiest, most flexible way to display your products on posts and pages!

For more information on what blocks are available, and how to use them, check out the official documentation: https://docs.woocommerce.com/document/woocommerce-blocks/

**Note: Feature plugin for WooCommerce + Gutenberg. This plugin serves as a space to iterate and explore new Blocks and updates to existing blocks for WooCommerce, and how WooCommerce might work with the block editor.**

Use this plugin if you want access to the bleeding edge of available blocks for WooCommerce. However, stable blocks are bundled into WooCommerce, and can be added from the "WooCommerce" section in the block inserter.

- **Featured Product Block**
- **Featured Category Block**
- **Hand-picked Products Block**
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
- **Filter Products by Stock**
- **Active Product Filters**
- **Cart**
- **Checkout**

== Getting Started ==

= Minimum Requirements =

* Latest release versions of WordPress and WooCommerce ([read more here](https://developer.woocommerce.com/?p=9998))
* PHP version 7.0 or greater (PHP 7.4 or greater is recommended)
* MySQL version 5.6 or greater

Visit the [WooCommerce server requirements documentation](https://docs.woocommerce.com/document/server-requirements/) for a detailed list of server requirements.

= Automatic installation =

Automatic installation is the easiest option as WordPress handles the file transfers itself and you don’t need to leave your web browser. To do an automatic install of this plugin, log in to your WordPress dashboard, navigate to the Plugins menu and click Add New.

In the search field type “WooCommerce Blocks” and click Search Plugins. Once you’ve found this plugin you can view details about it such as the point release, rating and description. Most importantly of course, you can install it by simply clicking “Install Now”.

= Manual installation =

The manual installation method involves downloading the plugin and uploading it to your webserver via your favourite FTP application. The WordPress codex contains [instructions on how to do this here](https://codex.wordpress.org/Managing_Plugins#Manual_Plugin_Installation).

= Sample data =

WooCommerce comes with some sample data you can use to populate the products and get started building Products blocks quickly and easily. You can use the core [CSV importer](https://docs.woocommerce.com/document/product-csv-importer-exporter/) or our [CSV Import Suite plugin](https://woocommerce.com/products/product-csv-import-suite/) to import sample_products.csv.

= Where can I report bugs or contribute to the project? =

Bugs should be reported in the [WooCommerce Blocks GitHub repository](https://github.com/woocommerce/woocommerce-gutenberg-products-block/).

= This is awesome! Can I contribute? =

Yes you can! Join in on our [GitHub repository](https://github.com/woocommerce/woocommerce-gutenberg-products-block/) :)

Release and roadmap notes available on the [WooCommerce Developers Blog](https://woocommerce.wordpress.com/2019/01/15/woocommerce-blocks-1-3-0-release-notes/)

== Screenshots ==

1. The Featured Product Block
2. Selecting a product for the Featured Product Block
3. Selecting Products for the Hand-picked Products Block
4. Selecting categories in the Products By Category block
5. WooCommerce Product Blocks in the block inserter menu

== Changelog ==

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
- Fix - Set correct text color in BlockErrorBoundry notices. ([3738](https://github.com/woocommerce/woocommerce-gutenberg-products-block/pull/3738))
- Hidden cart item meta data will not be rendered in the Cart and Checkout blocks. ([3732](https://github.com/woocommerce/woocommerce-gutenberg-products-block/pull/3732))
- Fix - Improved accessibility of product image links in the products block by using correct aria tags and hiding empty image placeholders. ([3722](https://github.com/woocommerce/woocommerce-gutenberg-products-block/pull/3722))
- Add missing aria-label for stars image in the review-list-item component. ([3706](https://github.com/woocommerce/woocommerce-gutenberg-products-block/pull/3706))
- Prevent "X-WC-Store-API-Nonce is invalid" error when going back to a page with the products block using the browser back button. ([3770](https://github.com/woocommerce/woocommerce-gutenberg-products-block/pull/3770))

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
