# Payment Method Integration for the Checkout Block <!-- omit in toc -->

The checkout block has an API interface for payment methods to integrate that consists of both a server side and client side implementation.

> **Note:** This API is fairly stable, but we're still really early in the checkout block release plan so it _is_ possible this might slightly change as more payment methods are integrated and we discover areas needing improvement. So monitoring this API will be needed.

## Table of Contents <!-- omit in toc -->

- [Client Side integration](#client-side-integration)
  - [Express payment methods - `registerExpressPaymentMethod( paymentMethodCreator )`](#express-payment-methods---registerexpresspaymentmethod-paymentmethodcreator-)
  - [Payment Methods - `registerPaymentMethod( paymentMethodCreator )`](#payment-methods---registerpaymentmethod-paymentmethodcreator-)
  - [Props Fed to Payment Method Nodes](#props-fed-to-payment-method-nodes)
- [Server Side Integration](#server-side-integration)
  - [Processing Payment](#processing-payment)
  - [Registering Assets](#registering-assets)

## Client Side integration

The client side integration consists of an API for registering both _express_ payment methods (those that consist of a one button payment process initiated by the shopper such as Stripe ApplePay or GooglePay), and payment methods such as _cheque_, Paypal Standard, or Stripe Credit Card.

In both cases, the client side integration is done using registration methods exposed on the `blocks-registry` API. You can access this via the `wc` global in a WooCommerce environment (`wc.wcBlocksRegistry`). You'll see that we also Webpack configured in the blocks repository to expose this API on `@woocommerce/blocks-registry` which you can implement in your own build process as well.

### Express payment methods - `registerExpressPaymentMethod( paymentMethodCreator )`

![Express Payment Area](https://user-images.githubusercontent.com/1429108/79565636-17fed500-807f-11ea-8e5d-9af32e43b71d.png)

To register an express payment method, you use the `registerExpressPaymentMethod` function from the blocks registry. An example of importing this for use in your JavaScript file is:

_Aliased import_

```js
import { registerExpressPaymentMethod } from '@woocommerce/blocks-registry';
```

_wc global_

```js
const { registerExpressPaymentMethod } = wc.wcBlocksRegistry;
```

The registry function expects a function that will receive an `ExpressPaymentMethodConfig` creator as an argument and is expected to return a valid `ExpressPaymentMethodConfig` instance. As a very basic example, something like this:

```js
registerExpressPaymentMethod(
	( ExpressPaymentMethodConfig ) => new ExpressPaymentMethodConfig( options )
);
```

The options you feed the configuration instance should be an object in this shape:

```js
const options = {
	name: 'my_payment_method',
	content: <div>A react node</div>,
	edit: <div> A react node </div>,
	canMakePayment: () => true,
	paymentMethodId: 'new_payment_method',
};
```

Here's some more details on the configuration options:

-   `name` (required): This should be a unique string (wise to try to pick something unique for your gateway that wouldn't be used by another implementation) that is used as the identifier for the gateway client side. If `paymentMethodId` is not provided, `name` is used for `paymentMethodId` as well.
-   `content` (required): This should be a react node that will output in the express payment method area when the block is rendered in the frontend. It will be cloned in the rendering process. When cloned, this react node will receive props from the payment method interface to checkout that will allow your component to interact with checkout data (more on this later).
-   `edit` (required): This should be a react node that will be output in the express payment method area when the block is rendered in the editor. It will be cloned in the rendering process. When cloned, this react node will receive props from the payment method interface to checkout (but they will contain preview data).
-   `canMakePayment` (required): This should be a function that returns whether the payment method can make a payment in the current environment. The function _can_ return a promise and will receive [`cartData`](https://github.com/woocommerce/woocommerce-gutenberg-products-block/blob/308e968c700028180cab391f2223eb0a43dd2d4d/assets/js/type-defs/cart.js#L163-L177) as an argument (so the payment method can use that to determine if it can be used). Most payment methods will probably just return `true` from the function, but this does allow payment methods like `ApplePay` which have restrictions on where they work to return `false` and thus be excluded as an option in specific browser environments (or other conditions). Keep in mind this function is only invoked once in the mounting process of the checkout block.
-   `paymentMethodId`: This is the only optional configuration object. The value of this property is what will accompany the checkout processing request to the server and used to identify what payment method gateway class to load for processing the payment (if the shopper selected the gateway). So for instance if this is `stripe`, then `WC_Gateway_Stripe::process_payment` will be invoked for processing the payment.

### Payment Methods - `registerPaymentMethod( paymentMethodCreator )`

![Payment Method Area](https://user-images.githubusercontent.com/1429108/79565774-5d230700-807f-11ea-9335-0111ec306a47.png)

To register a payment method, you use the `registerPaymentMethod` function from the blocks registry. An example of importing this for use in your JavaScript file is:

_Aliased import_

```js
import { registerPaymentMethod } from '@woocommerce/blocks-registry';
```

_wc global_

```js
const { registerPaymentMethod } = wc.wcBlocksRegistry;
```

The registry function expects a function that will receive an `PaymentMethodConfig` creator as an argument and is expected to return a valid `PaymentMethodConfig` instance. As a very basic example, something like this:

```js
registerPaymentMethod(
	( PaymentMethodConfig ) => new PaymentMethodConfig( options )
);
```

The options you feed the configuration instance are the same as those for express payment methods with the following additions:

-   `label`: This should be a react node that will be used to output the label for the tab in the payment methods are. For example it might be `<strong>Credit/Debit Cart</strong>` or you might output images.
-   `ariaLabel`: This is the label that will be read out via screen-readers when the payment method is selected.
- `placeOrderButtonLabel`: This is an optional label which will change the default "Place Order" button text to something else when the payment method is selected.

### Props Fed to Payment Method Nodes

A big part of the payment method integration is the interface that is exposed for payment methods to use via props when the node provided is cloned and rendered on block mount. While all the props are listed below, you can find more details about what the props reference, their types etc via the [typedefs described in this file](https://github.com/woocommerce/woocommerce-gutenberg-products-block/blob/master/assets/js/type-defs/registered-payment-method-props.js).

-   `checkoutStatus`: This is an object with the following checkout status properties - `isCalculating`, `isComplete`, `isIdle`, and `isProcessing`.
-   `paymentStatus`: This is an object with the [following payment status properties](https://github.com/woocommerce/woocommerce-gutenberg-products-block/blob/308e968c700028180cab391f2223eb0a43dd2d4d/assets/js/type-defs/contexts.js#L91-L113) - `isPristine`, `isStarted`, `isProcessing`, `isFinished`, `hasError`, `hasFailed`, `isSuccessful`. Note, your payment method does not have to handle setting this status client side. Checkout will handle this via the responses your payment method gives from observers registered to [checkout event emitters](./checkout-flow-and-events.md).
-   `shippingStatus`: This object has two properties - `shippingErrorStatus`, which is an object with various error statuses that might exist for shipping, and `shippingErrorTypes`, which is an object containing all the possible types for shipping error status.
-   `shippingData`: This object contains all shipping related data (outside of status) - `shippingRates`, `shippingRatesLoading`, `selectedRates`, `setSelectedRates`, `isSelectingRate`, `shippingAddress`, `setShippingAddress`, and `needsShipping`.
-   `billing`: This object contains everything related to billing - `billingData`, `cartTotal`, `currency`, `cartTotalItems`, `displayPricesIncludingTax`, `appliedCoupons`, `customerId`
-   `eventRegistration`: This object contains all the checkout event emitter registration functions. These are functions the payment method can register observers on to interact with various points in the checkout flow (see [this doc](./checkout-flow-and-events.md) for more info). The following properties are available - `onCheckoutBeforeProcessing`, `onCheckoutAfterProcessingWithSuccess`, `onCheckoutAfterProcessingWithError`, `onPaymentProcessing`, `onShippingRateSuccess`, `onShippingRateFail`, `onShippingRateSelectSuccess`, `onShippingRateSelectFail`
-   `components`: The properties on this object are exposed components that can be implemented by your payment method for various common interface elements used by payment methods. Currently the available components on this property are: `ValidationInputError` (a container for holding validation errors which typically you'll include after any inputs), and `CheckboxControl`(which is usually used for indicating to save the payment method). **Note: this last one is subject to change**
-   `setExpressPaymentError`: This function receives a string and allows express payment methods to set an error notice for the express payment area on demand. This can be necessary because some express payment method processing might happen outside of checkout events.

## Server Side Integration

> **Note:** This portion of the doc is still in development and there could be changes to the API here.

### Processing Payment

Currently, the checkout block has legacy handling for payment processing (by converting incoming `payment_data` provided by the client side payment method to `$_POST` and calling the payment gateway's `process_payment` function ). So unless your payment method hooks into the core checkout `process_checkout` function in any way, this will take care of processing your payment for you.

### Registering Assets

Since implementing the correct loading of your client side asset registration is tricky with the variables around the block loading process, there is an API for ensuring you can register any assets and data to pass along to your client side payment method from the server.

First, you create a class that extends `Automattic\WooCommerce\Blocks\Payments\Integration\AbstractPaymentMethodType` (or you can implement the `Automattic\WooCommerce\Blocks\Payments\PaymentMethodTypeInterface`, but you get some functionality for free via the abstract class).

In your class:

-   Define a `name` property (which is a string used to reference your payment method).
-   Define an `initialize` function. This function will get called during the server side initialization process and is a good place to put any settings population etc. Basically anything you need to do to initialize your gateway. Note, this will be called on every request so don't put anything expensive here.
-   Define an `is_active` function. This should return whether the payment method is active or not.
-   Define a `get_payment_method_script_handles` function. In this function you should register your payment method scripts (using `wp_register_script`) and then return the script handles you registered with. This will be used to add your payment method as a dependency of the checkout script and thus take sure of loading it correctly.
-   Define a `get_payment_method_script_handles_for_admin` function. Include this if your payment method has a script you _only_ want to load in the editor context for the checkout block.
-   Define a `get_payment_method_data` function. You can return from this function an associative array of data you want to be exposed for your payment method client side. This data will be available client side via `wc.wcSettings.getSetting`. So for instance if you assigned `stripe` as the value of the `name` property for this class, client side you can access any data via: `wc.wcSettings.getSetting( 'stripe_data' )`. That would return an object matching the shape of the associative array you returned from this function.

There is also a [hook you can implement to hook into the server side processing of the order](https://github.com/woocommerce/woocommerce-gutenberg-products-block/blob/308e968c700028180cab391f2223eb0a43dd2d4d/src/RestApi/StoreApi/Routes/Checkout.php#L350-L361). **Note:** a good place to register your callback on this hook is in the `initialize` method of the payment method class you created from the above instructions.

This hook is the required place to hook in your payment processing and if you set a status on the provided `PaymentResult` object, then the legacy processing will be ignored for your payment method. Hook callbacks will receive:

[`Automattic\WooCommerce\Blocks\Payments\PaymentContext`](https://github.com/woocommerce/woocommerce-gutenberg-products-block/blob/master/src/Payments/PaymentContext.php)

This contains various details about the payment extracted from the checkout processing request. Notably is the `payment_data` property that will contain an associative array of data your payment method client side provided to checkout. It also contains a string value for `payment_method` which contains the `paymentMethodId` value for the active payment method used during checkout processing. So you can use this to determine whether your payment method processes this data or not.

[`Automattic\WooCommerce\Blocks\Payments\PaymentResult`](https://github.com/woocommerce/woocommerce-gutenberg-products-block/blob/master/src/Payments/PaymentResult.php)

This contains various details about the payment result returned to the client and exposed on the `onAfterCheckoutProcessingWithSucces/WithError` event. Server side, your payment method can use this to:

-   set the status to return for the payment method (one of `success`, `failure`, `pending`, `error`).
-   set a redirect url.
-   set any additional payment details (in case you need to return something for your client to further process with).
