# Filtering payment methods in the Checkout block

## The problem

You're an extension developer, and your extension is conditionally hiding payment gateways on the checkout step. You need to be able to hide payment gateways on the Checkout block using a front-end extensibility point.

## The solution

WooCommerce Blocks provides a function called `registerPaymentMethodExtensionCallbacks` which allows extensions to register callbacks for specific payment methods to determine if they can make payments.

## Importing

_Aliased import_

```js
import { registerPaymentMethodExtensionCallbacks } from '@woocommerce/blocks-registry';
```

_wc global_

```js
const { registerPaymentMethodExtensionCallbacks } = wc.wcBlocksRegistry;
```

## Signature

| Parameter   | Description                                                                                                         | Type                                              |
| ----------- | ------------------------------------------------------------------------------------------------------------------- | ------------------------------------------------- |
| `namespace` | Unique string to identify your extension. Choose something that eliminates a name collision with another extension. | `string`                                          |
| `callbacks` | An object containing callbacks registered for different payment methods                                             | Record< string, CanMakePaymentExtensionCallback > |

Read more below about [callbacks](#callbacks-registered-for-payment-methods).

### Extension namespace collision

When trying to register callbacks under an extension namespace already used with `registerPaymentMethodExtensionCallbacks`, the registration will be aborted and you will be notified that you are not using a unique namespace. This will be shown in the JavaScript console.

## Usage example

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

## Callbacks registered for payment methods

Extensions can register only one callback per payment method:

```
payment_method_name: ( arg ) => {...}
```

`payment_method_name` is the value of the [name](payment-method-integration.md#name-required) property used when the payment method was registered with WooCommerce Blocks.

The registered callbacks are used to determine whether the corresponding payment method should be available as an option for the shopper. The function will be passed an object containing data about the current order.

```typescript
type CanMakePaymentExtensionCallback = (
	cartData: CanMakePaymentArgument
) => boolean;
```

Each callback will have access to the information bellow

```typescript
interface CanMakePaymentArgument {
	cart: Cart;
	cartTotals: CartTotals;
	cartNeedsShipping: boolean;
	billingData: CartResponseBillingAddress;
	shippingAddress: CartResponseShippingAddress;
	selectedShippingMethods: Record< string, unknown >;
	paymentRequirements: Array< string >;
}
```

If you need data that is not available in the parameter received by the callback you can consider [exposing your data in the Store API](extend-rest-api-add-data.md).
