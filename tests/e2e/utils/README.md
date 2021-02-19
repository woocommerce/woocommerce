# WooCommerce End to End Test Utilities 

This package contains utilities to simplify writing e2e tests specific to WooCommmerce.


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
		await merchant.login();
		await createSimpleProduct();
		await merchant.logout();
	} );

	it( 'should display no item in the cart', async () => {
		await shopper.goToCart();
		await expect( page ).toMatchElement( '.cart-empty', { text: 'Your cart is currently empty.' } );
	} );
} );
~~~

## Test Function

### Merchant `merchant`

| Function | Parameters | Description |
|----------|-------------|------------|
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

### Shopper `shopper`

| Function | Parameters | Description |
|----------|------------|-------------|
| `addToCart` | | Add an item to the cart from a single product page |
| `addToCartFromShopPage` | `productTitle` | Add an item to the cart from a single product page |
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
| `placeOrder` |  | Place an order from the checkout page |
| `productIsInCheckout` | `productTitle, quantity, total, cartSubtotal` | Verify product is in cart on checkout page |
| `removeFromCart` | `productTitle` | Remove a product from the cart on the cart page |
| `setCartQuantity` | `productTitle, quantityValue` | Change the quantity of a product on the cart page |

### Page Utilities

| Function | Parameters | Description |
|----------|------------|-------------|
| `addProductToOrder` | `orderId, productName` | adds a product to an order using the product search |
| `clearAndFillInput` | `selector, value` | Replace the contents of an input with the passed value |
| `clickFilter` | `selector` | helper method that clicks on a list page filter |
| `clickTab` | `tabName` | Click on a WooCommerce -> Settings tab |
| `createCoupon` | `couponAmount` | creates a basic coupon. Default amount is 5. Returns the generated coupon code. |
| `createSimpleOrder` | `status` | creates a basic order with the provided status string |
| `moveAllItemsToTrash` | | helper method that checks every item in a list page and moves them to the trash |
| `settingsPageSaveChanges` |  | Save the current WooCommerce settings page |
| `permalinkSettingsPageSaveChanges` |  | Save the current Permalink settings |
| `setCheckbox` | `selector` | Check a checkbox |
| `unsetCheckbox` | `selector` | Uncheck a checkbox |
| `uiUnblocked` |  | Wait until the page is unblocked |
| `verifyPublishAndTrash` | `button, publishNotice, publishVerification, trashVerification` | Verify that an item can be published and trashed |
| `verifyCheckboxIsSet` | `selector` | Verify that a checkbox is checked |
| `verifyCheckboxIsUnset` | `selector` | Verify that a checkbox is unchecked |
| `verifyValueOfInputField` | `selector, value` | Verify an input contains the passed value |
| `clickFilter` | `selector` | Click on a list page filter |
| `moveAllItemsToTrash` |  | Moves all items in a list view to the Trash |
| `verifyAndPublish` | `noticeText` | Verify that an item can be published |
| `selectOptionInSelect2` | `selector, value` | helper method that searchs for select2 type fields and select plus insert value inside

### Test Utilities

As of version 0.1.2, all test utilities from [`@wordpress/e2e-test-utils`](https://www.npmjs.com/package/@wordpress/e2e-test-utils) are available through this package.
