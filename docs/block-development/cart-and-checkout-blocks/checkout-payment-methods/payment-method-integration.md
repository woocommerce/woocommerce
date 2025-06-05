---
post_title: Payment method integration
sidebar_label: Payment method integration

---

# Payment method integration

## Client Side integration

The client side integration consists of an API for registering both _regular_ and _express_ payment methods.

In both cases, the client side integration is done using registration methods exposed on the `blocks-registry` API. You can access this via the `wc` global in a WooCommerce environment (`wc.wcBlocksRegistry`).

> Note: In your build process, you could do something similar to what is done in the blocks repository which [aliases this API as an external on `@woocommerce/blocks-registry`](https://github.com/woocommerce/woocommerce-gutenberg-products-block/blob/e089ae17043fa525e8397d605f0f470959f2ae95/bin/webpack-helpers.js#L16-L35).

## Express Payment Methods

Express payment methods are payment methods that consist of a one-button payment process initiated by the shopper such as Stripe, ApplePay, or GooglePay.

![Express Payment Area](https://user-images.githubusercontent.com/1429108/79565636-17fed500-807f-11ea-8e5d-9af32e43b71d.png)

### Registration

To register an express payment method, you use the `registerExpressPaymentMethod` function from the blocks registry. 

```js
const { registerExpressPaymentMethod } = window.wc.wcBlocksRegistry;
```

If you're using an aliased import for `@woocommerce/blocks-registry`, you can import the function like this:

```js
import { registerExpressPaymentMethod } from '@woocommerce/blocks-registry';
```

The registry function expects a JavaScript object with options specific to the payment method:

```js
registerExpressPaymentMethod( options );
```

The options you feed the configuration instance should be an object in this shape (see `ExpressPaymentMethodConfiguration` typedef):

```js
const options = {
	name: 'my_payment_method',
	title: 'My Mayment Method',
	description: 'A setence or two about your payment method',
	gatewayId: 'gateway-id',
	label: <ReactNode />,
	content: <ReactNode />,
	edit: <ReactNode />,
	canMakePayment: () => true,
	paymentMethodId: 'my_payment_method',
	supports: {
		features: [],
		style: [],
	},
};
```

#### `ExpressPaymentMethodConfiguration`

| Option                | Type       | Description                                                                                                                                                                                                                                                                                                                                                     | Required |
|-----------------------|------------|-----------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------|----------|
| `name`                | String     | Unique identifier for the gateway client side.                                                                                                                                            | Yes      |
| `title`               | String     | Human readable name of your payment method. Displayed to the merchant in the editor.                                                                                                                                                                                                                        | No       |
| `description`         | String     | One or two sentences describing your payment gateway. Displayed to the merchant in the editor.                                                                                                                                                                                                                                                                  | No       |
| `gatewayId`           | String     | ID of the Payment Gateway registered server side. Used to direct the merchant to the right settings page within the editor. If this is not provided, the merchant will be redirected to the general Woo payment settings page.                                                                                                                                   | No       |
| `content`             | ReactNode  | React node output in the express payment method area when the block is rendered in the frontend. Receives props from the checkout payment method interface.                                                                                                                                                                                                     | Yes      |
| `edit`                | ReactNode  | React node output in the express payment method area when the block is rendered in the editor. Receives props from the payment method interface to checkout (with preview data).                                                                                                                                                                                | Yes      |
| `canMakePayment`      | Function   | Callback to determine whether the payment method should be available for the shopper.                                                                                                                                                          | Yes      |
| `paymentMethodId`     | String     | Identifier accompanying the checkout processing request to the server. Used to identify the payment method gateway class for processing the payment.                                                                                                                                                                                                            | No       |
| `supports:features`   | Array      | Array of payment features supported by the gateway. Used to crosscheck if the payment method can be used for the cart content. Defaults to `['products']` if no value is provided.                                                                                                                                                                              | No       |
| `supports:style`      | Array      | This is an array of style variations supported by the express payment method. These are styles that are applied across all the active express payment buttons and can be controlled from the express payment block in the editor. Supported values for these are one of `['height', 'borderRadius']`.                                                                                                                                 | No       |

#### The `canMakePayment` option

`canMakePayment` is a callback to determine whether the payment method should be available as an option for the shopper. The function will be passed an object containing data about the current order.

```ts
canMakePayment( {
	cart: Cart,
	cartTotals: CartTotals,
	cartNeedsShipping: boolean,
	shippingAddress: CartShippingAddress,
	billingAddress: CartBillingAddress,
	selectedShippingMethods: Record<string,unknown>,
	paymentRequirements: string[],
} )
```

`canMakePayment` returns a boolean value. If your gateway needs to perform async initialization to determine availability, you can return a promise (resolving to boolean). This allows a payment method to be hidden based on the cart, e.g. if the cart has physical/shippable products (example: [`Cash on delivery`](https://github.com/woocommerce/woocommerce-gutenberg-products-block/blob/e089ae17043fa525e8397d605f0f470959f2ae95/assets/js/payment-method-extensions/payment-methods/cod/index.js#L48-L70)); or for payment methods to control whether they are available depending on other conditions.

`canMakePayment` only runs on the frontend of the Store. In editor context, rather than use `canMakePayment`, the editor will assume the payment method is available (true) so that the defined `edit` component is shown to the merchant.

**Keep in mind this function could be invoked multiple times in the lifecycle of the checkout and thus any expensive logic in the callback provided on this property should be memoized.**

### Button Attributes for Express Payment Methods

This API provides a way to synchronise the look and feel of the express payment buttons for a coherent shopper experience. Express Payment Methods must prefer the values provided in the `buttonAttributes`, and use it's own configuration settings as backup when the buttons are rendered somewhere other than the Cart or Checkout block.

For example, in your button component, you would do something like this:

```js
// Get your extension specific settings and set defaults if not available
let {
	borderRadius = '4',
	height = '48',
} = getButtonSettingsFromConfig();

// In a cart & checkout block context, we receive `buttonAttributes` as a prop which overwrite the extension specific settings
if ( typeof buttonAttributes !== 'undefined' ) {
	height = buttonAttributes.height;
	borderRadius = buttonAttributes.borderRadius;
}
...

return <button style={height: `${height}px`, borderRadius: `${borderRadius}px`} />
```

## Payment Methods

Payment methods are the payment method options that are displayed in the checkout block. Examples include _cheque_, PayPal Standard, and Stripe Credit Card.

![Image 2021-02-24 at 4 24 05 PM](https://user-images.githubusercontent.com/1429108/109067640-c7073680-76bc-11eb-98e5-f04d35ddef99.jpg)

### Registration

To register a payment method, you use the `registerPaymentMethod` function from the blocks registry. 

```js
const { registerPaymentMethod } = window.wc.wcBlocksRegistry;
```

If you're using an aliased import for `@woocommerce/blocks-registry`, you can import the function like this:

```js
import { registerPaymentMethod } from '@woocommerce/blocks-registry';
```

The registry function expects a JavaScript object with options specific to the payment method:

```js
registerPaymentMethod( options );
```

The options you feed the configuration instance should be an object in this shape (see `PaymentMethodRegistrationOptions` typedef). The options you feed the configuration instance are the same as those for express payment methods with the following additions:

| Property | Type | Description |
|----------|------|-------------|
| `savedTokenComponent` | ReactNode | A React node that contains logic for handling saved payment methods. Rendered when a customer's saved token for this payment method is selected. |
| `label` | ReactNode | A React node used to output the label for the payment method option. Can be text or images. |
| `ariaLabel` | string | The label read by screen-readers when the payment method is selected. |
| `placeOrderButtonLabel` | string | Optional label to change the default "Place Order" button text when this payment method is selected. |
| `supports` | object | Contains information about supported features: |
| `supports.showSavedCards` | boolean | Determines if saved cards for this payment method are shown to the customer. |
| `supports.showSaveOption` | boolean | Controls whether to show the checkbox for saving the payment method for future use. |

## Props Fed to Payment Method Nodes

A big part of the payment method integration is the interface that is exposed for payment methods to use via props when the node provided is cloned and rendered on block mount. While all the props are listed below, you can find more details about what the props reference, their types etc via the [typedefs described in this file](https://github.com/woocommerce/woocommerce/blob/trunk/plugins/woocommerce/client/blocks/assets/js/types/type-defs/payment-method-interface.ts).

| Property | Type | Description |
|----------|------|-------------|
| `activePaymentMethod` | String | The slug of the current active payment method in the checkout. |
| `billing` | { `billingAddress`, `cartTotal`, `currency`, `cartTotalItems`, `displayPricesIncludingTax`, `appliedCoupons`, `customerId` } | Contains everything related to billing. |
| `cartData` | { `cartItems`, `cartFees`, `extensions` } | Data exposed from the cart including items, fees, and any registered extension data. Note that this data should be treated as immutable (should not be modified/mutated) or it will result in errors in your application. |
| `checkoutStatus` | { `isCalculating`, `isComplete`, `isIdle`, `isProcessing` } | The current checkout status exposed as various boolean state. |
| `components` | { `ValidationInputError`, `PaymentMethodLabel`, `PaymentMethodIcons`, `LoadingMask` } | It exposes React components that can be implemented by your payment method for various common interface elements used by payment methods. |
| `emitResponse` | Object containing `noticeContexts` and `responseTypes` | Contains some constants that can be helpful when using the event emitter. Read the [Emitting Events](./checkout-flow-and-events.md#emitting-events) section for more details. |
| `eventRegistration` | { `onCheckoutValidation`, `onCheckoutSuccess`, `onCheckoutFail`, `onPaymentSetup`, `onShippingRateSuccess`, `onShippingRateFail`, `onShippingRateSelectSuccess`, `onShippingRateSelectFail` } | Contains all the checkout event emitter registration functions. These are functions the payment method can register observers on to interact with various points in the checkout flow (see [this doc](./checkout-flow-and-events.md) for more info). |
| `onClick` | Function | **Provided to express payment methods** that should be triggered when the payment method button is clicked (which will signal to checkout the payment method has taken over payment processing) |
| `onClose` | Function | **Provided to express payment methods** that should be triggered when the express payment method modal closes and control is returned to checkout. |
| `onSubmit` | Function | Submits the checkout and begins processing |
| `buttonAttributes` | { `height`, `borderRadius` } | Styles set by the merchant that should be respected by all express payment buttons |
| `paymentStatus` | Object | Various payment status helpers. Note, your payment method does not have to handle setting this status client side. Checkout will handle this via the responses your payment method gives from observers registered to [checkout event emitters](./checkout-flow-and-events.md). |
| `paymentStatus.isPristine` | Boolean | This is true when the current payment status is `PRISTINE`. |
| `paymentStatus.isStarted`
