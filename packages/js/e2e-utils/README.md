# WooCommerce End to End Test Utilities

This package contains utilities to simplify writing e2e tests specific to WooCommerce.


## Installation

```bash
npm install @woocommerce/e2e-utils --save
```

## Usage

Example:
~~~js
import {
	shopper,
	merchant,
	createSimpleProduct
} from '@woocommerce/e2e-utils';

describe( 'Cart page', () => {
	beforeAll( async () => {
		await createSimpleProduct();
	} );

	it( 'should display no item in the cart', async () => {
		await shopper.goToCart();
		await expect( page ).toMatchElement( '.cart-empty', { text: 'Your cart is currently empty.' } );
	} );
} );
~~~

### Retries

This package provides support for enabling retries in tests:

- In the test environment set `E2E_RETEST=1`.
- To add conditional logic to your tests use the boolean constant `IS_RETEST_MODE`.

### Available constants

#### Dashboard

- `WP_ADMIN_LOGIN` - WordPress login
- `WP_ADMIN_DASHBOARD` - WordPress dashboard
- `WP_ADMIN_WP_UPDATES` - WordPress updates
- `WP_ADMIN_PLUGINS` - Plugin list
- `WP_ADMIN_PERMALINK_SETTINGS` - Permalink settings
- `WP_ADMIN_ALL_USERS_VIEW` - WordPress user list
- `WP_ADMIN_POST_TYPE` - Post listing
- `WP_ADMIN_NEW_POST_TYPE` - New post
- `WP_ADMIN_ALL_COUPONS_VIEW` - Coupons list
- `WP_ADMIN_NEW_COUPON` - New coupon
- `WP_ADMIN_ALL_ORDERS_VIEW` - Orders list
- `WP_ADMIN_NEW_ORDER` - New Order
- `WP_ADMIN_ALL_PRODUCTS_VIEW` - Products list
- `WP_ADMIN_NEW_PRODUCT` - New product
- `WP_ADMIN_IMPORT_PRODUCTS` - Import products
- `WP_ADMIN_PLUGIN_PAGE` - Plugin settings page root
- `WP_ADMIN_WC_HOME` - WooCommerce home screen
- `WP_ADMIN_SETUP_WIZARD` - WooCommerce setup/onboarding wizard
- `WP_ADMIN_ANALYTICS_PAGES` - WooCommerce analytics page root
- `WP_ADMIN_WC_SETTINGS` - WooCommerce settings page root
- `WP_ADMIN_NEW_SHIPPING_ZONE` - WooCommerce new shipping zone
- `WP_ADMIN_WC_EXTENSIONS` - WooCommerce extensions page
- `WP_ADMIN_PLUGIN_INSTALL` - WordPress plugin install page

#### Front end

- `SHOP_PAGE` - Shop page
- `SHOP_PRODUCT_PAGE` - Single product page
- `SHOP_CART_PAGE` - Cart page
- `SHOP_CHECKOUT_PAGE` - Checkout page
- `SHOP_MY_ACCOUNT_PAGE` - Customer account page
- `MY_ACCOUNT_ORDERS` - Customer orders
- `MY_ACCOUNT_DOWNLOADS` - Customer downloads
- `MY_ACCOUNT_ADDRESSES` - Customer addresses
- `MY_ACCOUNT_ACCOUNT_DETAILS` - Customer account details

## Test Functions

### Merchant `merchant`

| Function | Parameters | Description |
|----------|-------------|------------|
| `addDownloadableProductPermission` | `productName` | Add a downloadable permission for product in order |
| `collapseAdminMenu` | `collapse` | Collapse or expand the WP admin menu |
| `dismissOnboardingWizard` |  | Dismiss the onboarding wizard if present |
| `goToOrder` | `orderId` | Go to view a single order |
| `goToProduct` | `productId` | Go to view a single product |
| `login` | | Log in as merchant |
| `logout` | | Log out of merchant account |
| `openAllOrdersView` | | Go to the orders listing |
| `openAllProductsView` | | Go to the products listing |
| `openDashboard` | | Go to the WordPress dashboard  |
| `openNewCoupon` | | Go to the new coupon editor |
| `openNewOrder` | | Go to the new order editor |
| `openNewProduct` | | Go to the new product editor |
| `openPermalinkSettings` | | Go to Settings -> Permalinks |
| `openPlugins` | | Go to the Plugins screen |
| `openSettings` | | Go to WooCommerce -> Settings |
| `runSetupWizard` | | Open the onboarding profiler |
| `updateOrderStatus` | `orderId, status` | Update the status of an order |
| `openEmailLog` | | Open the WP Mail Log page |
| `openAnalyticsPage` | | Open any Analytics page |
| `openAllUsersView` | | Open the All Users page |
| `openImportProducts` | | Open the Import Products page |
| `openExtensions` | | Go to WooCommerce -> Extensions |
| `openWordPressUpdatesPage` | | Go to Dashboard -> Updates |
| `revokeDownloadableProductPermission` | `productName` | Remove a downloadable product permission from order |
| `installAllUpdates` | | Install all pending updates on Dashboard -> Updates|
| `updateDownloadableProductPermission` | `productName, expirationDate, downloadsRemaining` | Update the attributes of a downloadable product permission in order |
| `updateWordPress` | | Install pending WordPress updates on Dashboard -> Updates|
| `updatePlugins` | | Install all pending plugin updates on Dashboard -> Updates|
| `updateThemes` | | Install all pending theme updates on Dashboard -> Updates|
| `updateVariationDetails` | `variationDetails` | Update the details of a product variation |
| `verifyCannotDownloadFromBecause` | `page, reason` | Verify that cannot download a product from `page` because of `reason`  |
| `verifyDownloadableProductPermission` | `productName, expirationDate, downloadsRemaining` | Verify the attributes of a downloadable product permission in order |
| `verifyVariationDetails` | `expectedVariationDetails` | Verify that the actual variation details match the expected ones |
| `runDatabaseUpdate` || Runs the database update if needed |
| `openDownloadLink` | | Open the download link of a product |

### Shopper `shopper`

| Function | Parameters | Description |
|----------|------------|-------------|
| `addToCart` | | Add an item to the cart from a single product page |
| `addToCartFromShopPage` | `productIdOrTitle` | Add an item to the cart from the shop page |
| `fillBillingDetails` | `customerBillingDetails` | Fill billing fields in checkout form using configured address |
| `fillShippingDetails` | `customerShippingDetails` | Fill shipping fields in checkout form using configured address |
| `goToAddresses` |  | Go to My Account -> Address Details |
| `goToAccountDetails` |  | Go to My Account -> Details |
| `goToCart` |  | Go to the cart page |
| `goToCheckout` |  | Go to the checkout page |
| `goToDownloads` |  | Go to My Account -> Downloads |
| `goToMyAccount` |  | Go to the My Account page |
| `goToOrders` |  | Go to My Account -> Orders |
| `goToProduct` | `productId` | Go to a single product in the shop |
| `goToShop` |  | Go to the shop page |
| `login` |  | Log in as the shopper |
| `logout` |  | Log out of the shopper account |
| `placeOrder` |  | Place an order from the checkout page |
| `productIsInCheckout` | `productTitle, quantity, total, cartSubtotal` | Verify product is in cart on checkout page |
| `removeFromCart` | `productIdOrTitle` | Remove a product from the cart on the cart page |
| `setCartQuantity` | `productTitle, quantityValue` | Change the quantity of a product on the cart page |
| `searchForProduct` | | Searching for a product name and landing on its detail page |
| `emptyCart` | | Removes any products and coupons that are in the cart |

### REST API `withRestApi`

Please note: if you're using a non-SSL environment (such as a Docker container from [`wc-e2e`](https://www.npmjs.com/package/@woocommerce/e2e-environment)) you will need to use Basic Auth in order to authenticate with the API and use the `withRestApi` methods listed below. To do so, you will need to install the [the Basic Auth plugin](https://github.com/WP-API/Basic-Auth). One way this can be accomplished is by adding `wp plugin install https://github.com/WP-API/Basic-Auth/archive/master.zip --activate` to your `initialize.sh` script.

| Function | Parameters | Description |
|----------|------------|-------------|
| `addShippingZoneAndMethod` | `zoneName`, `zoneLocation`, `zipCode`, `zoneMethod`, `cost`, `additionalZoneMethods`, `testResponse` | Adds a shipping zone along with a shipping method |
| `batchCreateOrders` | `orders`, `testResponse` | Create a batch of orders using the "Batch Create Order" API endpoint |
| `addTaxClasses` | `taxClasses` | Add an array of tax classes if they do not exist |
| `addTaxRates` | `taxRates` | Add an array of tax rates if they do not exist |
| `createProductCategory` | `categoryName` | Create a product category with the provided name |
| `deleteAllCoupons` | | Permanently delete all coupons |
| `deleteAllOrders` | | Permanently delete all orders |
| `deleteAllProductAttributes` | `testResponse` | Permanently delete all product attributes |
| `deleteAllProductCategories` | `testResponse` | Permanently delete all product categories |
| `deleteAllProducts` | | Permanently delete all products |
| `deleteAllProductTags` | `testResponse` | Permanently delete all product tags |
| `deleteAllShippingClasses` | `testResponse` | Permanently delete all shipping classes |
| `deleteAllShippingZones` | `testResponse` | Permanently delete all shipping zones except the default |
| `deleteCoupon` | `couponId` | Permanently delete a coupon |
| `deleteCustomerByEmail` | `emailAddress` | Delete customer user account. Posts are reassigned to user ID 1 |
| `deleteOrder` | `orderId` | Permanently delete an order |
| `deleteProduct` | `productId` | Permanently delete a simple product |
| `getSystemEnvironment` | | Get the current environment from the WooCommerce system status API. |
| `resetOnboarding` | | Reset onboarding settings |
| `resetSettingsGroupToDefault` | `settingsGroup`, `testResponse` | Reset settings in settings group to default except `select` fields |
| `updateSettingOption` | `settingsGroup`, `settingID`, `payload` | Update a settings group |
| `updatePaymentGateway`| `paymentGatewayId`, `payload`, `testResponse` | Update a payment gateway |

### Classes

The package includes the following page specific utility class:

#### AdminEdit

The `AdminEdit` class is the base classic custom post type post editor class. It contains the following functions:

| Function | Parameters | Description |
|----------|------------|-------------|
| `verifyPublish` | `button, publishNotice, publishVerification` | Publish the post object currently being edited and verify publish status |
| `getId` | | Get the ID of the post object being edited |

### General Utilities

There is a general utilities object `utils` with the following functions:

| Function | Parameters | Description |
|----------|------------|-------------|
| `getSlug` | `text` | Take a string name and generate the slug for it |
| `describeIf` | `condition` | Return the `describe` or `describe.skip` function when the condition is true / false |
| `it` | `condition` | Return the `it` or `it.skip` function when the condition is true / false |
| `waitForTimeout` | `timeout` | Wait for a timeout in milliseconds |

### Page Utilities

| Function | Parameters | Description |
|----------|------------|-------------|
| `addProductToOrder` | `orderId, productName` | adds a product to an order using the product search |
| `applyCoupon` | `couponName` | helper method which applies a coupon in cart or checkout |
| `clearAndFillInput` | `selector, value` | Replace the contents of an input with the passed value |
| `clickFilter` | `selector` | helper method that clicks on a list page filter |
| `clickTab` | `tabName` | Click on a WooCommerce -> Settings tab |
| `clickUpdateOrder` | `noticeText`, `waitForSave` | Helper method to click the Update button on the order details page |
| `completeOnboardingWizard` | | completes the onboarding wizard with some default settings |
| `createCoupon` | `couponAmount`, `couponType` | creates a basic coupon. Default amount is 5. Default coupon type is fixed discount. Returns the generated coupon code. |
| `createGroupedProduct` | | creates a grouped product for the grouped product tests. Returns the product id. |
| `createSimpleDownloadableProduct` | `name, downloadLimit, downloadName, price` | Create a simple downloadable product |
| `createSimpleOrder` | `status` | creates a basic order with the provided status string |
| `createSimpleProduct` | | creates the simple product configured in default.json. Returns the product id. |
| `createSimpleProductWithCategory` | `name`, `price`,`categoryName` | creates a simple product used passed values. Returns the product id. |
| `createVariableProduct` | | creates a variable product for the variable product tests. Returns the product id. |
| `deleteAllEmailLogs` | | deletes the emails generated by WP Mail Logging plugin |
| `evalAndClick` | `selector` | helper method that clicks an element inserted in the DOM by a script |
| `getSelectorAttribute` | `selector, attribute` | Retrieve the desired HTML attribute from a selector |
| `moveAllItemsToTrash` | | helper method that checks every item in a list page and moves them to the trash |
| `orderPageSaveChanges` |  | Save the current order page |
| `permalinkSettingsPageSaveChanges` |  | Save the current Permalink settings |
| `removeCoupon` | | helper method that removes a single coupon within cart or checkout |
| `selectOptionInSelect2` | `selector, value` | helper method that searches for select2 type fields and select plus insert value inside |
| `selectOrderAction` | `action` | Helper method to select an order action in the `Order Actions` postbox |
| `setCheckbox` | `selector` | Check a checkbox |
| `settingsPageSaveChanges` |  | Save the current WooCommerce settings page |
| `uiUnblocked` |  | Wait until the page is unblocked |
| `unsetCheckbox` | `selector` | Uncheck a checkbox |
| `verifyAndPublish` | `noticeText` | Verify that an item can be published |
| `verifyCheckboxIsSet` | `selector` | Verify that a checkbox is checked |
| `verifyCheckboxIsUnset` | `selector` | Verify that a checkbox is unchecked |
| `verifyPublishAndTrash` | `button, publishNotice, publishVerification, trashVerification` | Verify that an item can be published and trashed |
| `verifyValueOfElementAttribute` | `selector, attribute, expectedValue` | Assert the value of the desired HTML attribute of a selector |
| `verifyValueOfInputField` | `selector, value` | Verify an input contains the passed value |
| `clickFilter` | `selector` | Click on a list page filter |
| `moveAllItemsToTrash` |  | Moves all items in a list view to the Trash |
| `selectOptionInSelect2` | `selector, value` | helper method that searches for select2 type fields and select plus insert value inside |
| `searchForOrder` | `value, orderId, customerName` | helper method that searches for an order via many different terms |
| `addShippingZoneAndMethod` | `zoneName, zoneLocation, zipCode, zoneMethod` | util helper method for adding shipping zones with shipping methods |
| `applyCoupon` | `couponName` | helper method which applies a coupon in cart or checkout |
| `removeCoupon` | | helper method that removes a single coupon within cart or checkout |
| `selectOrderAction` | `action` | Helper method to select an order action in the `Order Actions` postbox |
| `clickUpdateOrder` | `noticeText`, `waitForSave` | Helper method to click the Update button on the order details page |
| `deleteAllShippingZones` | | Delete all the existing shipping zones |
| `waitForSelectorWithoutThrow` | `selector`, `timeoutInSeconds` | conditionally wait for a selector without throwing an error. Default timeout is 5 seconds |
| `createOrder` | `orderOptions` | Creates an order using the API with the passed in details |
| `clickAndWaitForSelector` | `buttonSelector`, `resultSelector`, `timeout` | Click a button and wait for response |
| `click` | `selector` | Clicks an element only if it is enabled. Otherwise, rise an error  |
| `productPageSaveChanges` |  | Save the current product page |

### Test Utilities

As of version 0.1.3, all test utilities from [`@wordpress/e2e-test-utils`](https://www.npmjs.com/package/@wordpress/e2e-test-utils) are available through this package.
