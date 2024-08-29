---
post_title: Cart and Checkout - Filtering payment methods in the Checkout block
menu_title: Filtering Payment Methods
tags: how-to
---

<!-- markdownlint-disable MD024 -->

## The problem

You're an extension developer, and your extension is conditionally hiding payment gateways on the checkout step. You need to be able to hide payment gateways on the Checkout block using a front-end extensibility point.

### The solution

WooCommerce Blocks provides a function called `registerPaymentMethodExtensionCallbacks` which allows extensions to register callbacks for specific payment methods to determine if they can make payments.

### Importing

#### Aliased import

```js
import { registerPaymentMethodExtensionCallbacks } from '@woocommerce/blocks-registry';
```

#### `wc global`

```js
const { registerPaymentMethodExtensionCallbacks } = window.wc.wcBlocksRegistry;
```

### Signature

| Parameter   | Description                                                                                                         | Type                                              |
| ----------- | ------------------------------------------------------------------------------------------------------------------- | ------------------------------------------------- |
| `namespace` | Unique string to identify your extension. Choose something that eliminates a name collision with another extension. | `string`                                          |
| `callbacks` | An object containing callbacks registered for different payment methods                                             | Record< string, CanMakePaymentExtensionCallback > |

Read more below about [callbacks](#callbacks-registered-for-payment-methods).

#### Extension namespace collision

When trying to register callbacks under an extension namespace already used with `registerPaymentMethodExtensionCallbacks`, the registration will be aborted and you will be notified that you are not using a unique namespace. This will be shown in the JavaScript console.

### Usage example

```js
registerPaymentMethodExtensionCallbacks( 'my-hypothetical-extension', {
	cod: ( arg ) => {
		return arg.shippingAddress.city === 'Berlin';
	},
	cheque: ( arg ) => {
		return false;
	},
} );
```

### Callbacks registered for payment methods

Extensions can register only one callback per payment method:

```text
payment_method_name: ( arg ) => {...}
```

`payment_method_name` is the value of the [name](payment-method-integration.md#name-required) property used when the payment method was registered with WooCommerce Blocks.

The registered callbacks are used to determine whether the corresponding payment method should be available as an option for the shopper. The function will be passed an object containing data about the current order.

```ts
type CanMakePaymentExtensionCallback = (
	cartData: CanMakePaymentArgument
) => boolean;
```

Each callback will have access to the information bellow

```ts
interface CanMakePaymentArgument {
	cart: Cart;
	cartTotals: CartTotals;
	cartNeedsShipping: boolean;
	billingAddress: CartResponseBillingAddress;
	shippingAddress: CartResponseShippingAddress;
	selectedShippingMethods: Record< string, unknown >;
	paymentRequirements: Array< string >;
}
```

If you need data that is not available in the parameter received by the callback you can consider [exposing your data in the Store API](https://github.com/woocommerce/woocommerce/blob/1675c63bba94c59703f57c7ef06e7deff8fd6bba/plugins/woocommerce-blocks/docs/third-party-developers/extensibility/rest-api/extend-rest-api-add-data.md).


## Filtering payment methods using requirements

### The problem

Your extension has added functionality to your store in such a way that only specific payment gateways can process orders that contain certain products.

Using the example of `Bookings` if the shopper adds a `Bookable` product to their cart, for example a stay in a hotel, and you, the merchant, want to confirm all bookings before taking payment. You would still need to capture the customer's checkout details but not their payment method at that point.

### The solution

To allow the shopper to check out without entering payment details, but still require them to fill in the other checkout details it is possible to create a new payment method which will handle carts containing a `Bookable` item.

Using the `supports` configuration of payment methods it is possible to prevent other payment methods (such as credit card, PayPal etc.) from being used to check out, and only allow the one your extension has added to appear in the Checkout block.

For more information on how to register a payment method with WooCommerce Blocks, please refer to the [Payment method integration](./payment-method-integration.md) documentation.

### Basic usage

Following the documentation for registering payment methods linked above, you should register your payment method with a unique `supports` feature, for example `booking_availability`. This will be used to isolate it and prevent other methods from displaying.

First you will need to create a function that will perform the checks on the cart to determine what the specific payment requirements of the cart are. Below is an example of doing this for our `Bookable` products.

Then you will need to use the `register_payment_requirements` on the `ExtendSchema` class to tell the Checkout block to execute a callback to check for requirements.

### Putting it all together

This code example assumes there is some class called `Pseudo_Booking_Class` that has the `cart_contains_bookable_product` method available. The implementation of this method is not relevant here.

```php
/**
 * Check the content of the cart and add required payment methods.
 *
 *
 * @return array list of features required by cart items.
 */
function inject_payment_feature_requirements_for_cart_api() {
  // Cart contains a bookable product, so return an array containing our requirement of booking_availability.
  if ( Pseudo_Booking_Class::cart_contains_bookable_product() ) {
    return array( 'booking_availability' );
  }

  // No bookable products in the cart, no need to add anything.
  return array();
}
```

To summarise the above: if there's a bookable product in the cart then this function will return an array containing `booking_availability`, otherwise it will return an empty array.

The next step will tell the `ExtendSchema` class to execute this callback when checking which payment methods to display.

To do this you could use the following code:

```php
add_action('woocommerce_blocks_loaded', function() {
  woocommerce_store_api_register_payment_requirements(
    array(
      'data_callback' => 'inject_payment_feature_requirements_for_cart_api',
    )
  );
});
```

It is important to note the comment in this code block, you must not instantiate your own version of `ExtendSchema`.

If you've added your payment method correctly with the correct `supports` values then when you reach the checkout page with a `Bookable` item in your cart, any method that does not `supports` the `booking_availability` requirement should not display, while yours, the one that _does_ support this requirement _will_ display.
