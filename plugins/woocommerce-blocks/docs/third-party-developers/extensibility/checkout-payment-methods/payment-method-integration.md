# Payment Method Integration for the Checkout Block <!-- omit in toc -->

The checkout block has an API interface for payment methods to integrate that consists of both a server side and client side implementation.

## Table of Contents <!-- omit in toc -->

-   [Client Side integration](#client-side-integration)
    -   [Express payment methods - `registerExpressPaymentMethod( options )`](#express-payment-methods---registerexpresspaymentmethod-options-)
        -   [Aliased import](#aliased-import)
        -   [`wc global`](#wc-global)
        -   [The registration options](#the-registration-options)
        -   [`name` (required)](#name-required)
        -   [`content` (required)](#content-required)
        -   [`edit` (required)](#edit-required)
        -   [`canMakePayment` (required)](#canmakepayment-required)
        -   [`paymentMethodId`](#paymentmethodid)
        -   [`supports:features`](#supportsfeatures)
    -   [Payment Methods - `registerPaymentMethod( options )`](#payment-methods---registerpaymentmethod-options-)
        -   [Aliased import](#aliased-import-1)
        -   [`wc global`](#wc-global-1)
        -   [The registration options](#the-registration-options-1)
    -   [Props Fed to Payment Method Nodes](#props-fed-to-payment-method-nodes)
-   [Server Side Integration](#server-side-integration)
    -   [Processing Payment](#processing-payment)
    -   [Registering Assets](#registering-assets)
    -   [Hooking into the Checkout processing by the Store API](#hooking-into-the-checkout-processing-by-the-store-api)
    -   [Putting it all together](#putting-it-all-together)

## Client Side integration

The client side integration consists of an API for registering both _express_ payment methods (those that consist of a one-button payment process initiated by the shopper such as Stripe, ApplePay, or GooglePay), and payment methods such as _cheque_, PayPal Standard, or Stripe Credit Card.

In both cases, the client side integration is done using registration methods exposed on the `blocks-registry` API. You can access this via the `wc` global in a WooCommerce environment (`wc.wcBlocksRegistry`).

> Note: In your build process, you could do something similar to what is done in the blocks repository which [aliases this API as an external on `@woocommerce/blocks-registry`](https://github.com/woocommerce/woocommerce-gutenberg-products-block/blob/e089ae17043fa525e8397d605f0f470959f2ae95/bin/webpack-helpers.js#L16-L35).

### Express payment methods - `registerExpressPaymentMethod( options )`

![Express Payment Area](https://user-images.githubusercontent.com/1429108/79565636-17fed500-807f-11ea-8e5d-9af32e43b71d.png)

To register an express payment method, you use the `registerExpressPaymentMethod` function from the blocks registry. An example of importing this for use in your JavaScript file is:

#### Aliased import

```js
import { registerExpressPaymentMethod } from '@woocommerce/blocks-registry';
```

#### `wc global`

```js
const { registerExpressPaymentMethod } = window.wc.wcBlocksRegistry;
```

#### The registration options

The registry function expects a JavaScript object with options specific to the payment method:

```js
registerExpressPaymentMethod( options );
```

The options you feed the configuration instance should be an object in this shape (see `ExpressPaymentMethodConfiguration` typedef):

```js
const options = {
	name: 'my_payment_method',
	content: <div>A React node</div>,
	edit: <div>A React node</div>,
	canMakePayment: () => true,
	paymentMethodId: 'new_payment_method',
	supports: {
		features: [],
	},
};
```

Here's some more details on the configuration options:

#### `name` (required)

This should be a unique string (wise to try to pick something unique for your gateway that wouldn't be used by another implementation) that is used as the identifier for the gateway client side. If `paymentMethodId` is not provided, `name` is used for `paymentMethodId` as well.

#### `content` (required)

This should be a React node that will output in the express payment method area when the block is rendered in the frontend. It will be cloned in the rendering process. When cloned, this React node will receive props passed in from the checkout payment method interface that will allow your component to interact with checkout data (more on [these props later](./payment-method-integration.md#props-fed-to-payment-method-nodes)).

#### `edit` (required)

This should be a React node that will be output in the express payment method area when the block is rendered in the editor. It will be cloned in the rendering process. When cloned, this React node will receive props from the payment method interface to checkout (but they will contain preview data).

#### `canMakePayment` (required)

A callback to determine whether the payment method should be available as an option for the shopper. The function will be passed an object containing data about the current order.

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

Returns a boolean value - true if payment method is available for use. If your gateway needs to perform async initialization to determine availability, you can return a promise (resolving to boolean). This allows a payment method to be hidden based on the cart, e.g. if the cart has physical/shippable products (example: [`Cash on delivery`](https://github.com/woocommerce/woocommerce-gutenberg-products-block/blob/e089ae17043fa525e8397d605f0f470959f2ae95/assets/js/payment-method-extensions/payment-methods/cod/index.js#L48-L70)); or for payment methods to control whether they are available depending on other conditions.

`canMakePayment` only runs on the frontend of the Store. In editor context, rather than use `canMakePayment`, the editor will assume the payment method is available (true) so that the defined `edit` component is shown to the merchant.

**Keep in mind this function could be invoked multiple times in the lifecycle of the checkout and thus any expensive logic in the callback provided on this property should be memoized.**

#### `paymentMethodId`

This is the only optional configuration object. The value of this property is what will accompany the checkout processing request to the server and is used to identify what payment method gateway class to load for processing the payment (if the shopper selected the gateway). So for instance if this is `stripe`, then `WC_Gateway_Stripe::process_payment` will be invoked for processing the payment.

#### `supports:features`

This is an array of payment features supported by the gateway. It is used to crosscheck if the payment method can be used for the content of the cart. By default payment methods should support at least `products` feature. If no value is provided then this assumes that `['products']` are supported.

---

### Payment Methods - `registerPaymentMethod( options )`

![Image 2021-02-24 at 4 24 05 PM](https://user-images.githubusercontent.com/1429108/109067640-c7073680-76bc-11eb-98e5-f04d35ddef99.jpg)

To register a payment method, you use the `registerPaymentMethod` function from the blocks registry. An example of importing this for use in your JavaScript file is:

#### Aliased import

```js
import { registerPaymentMethod } from '@woocommerce/blocks-registry';
```

#### `wc global`

```js
const { registerPaymentMethod } = window.wc.wcBlocksRegistry;
```

#### The registration options

The registry function expects a JavaScript object with options specific to the payment method (see `PaymentMethodRegistrationOptions` typedef):

```js
registerPaymentMethod( options );
```

The options you feed the configuration instance are the same as those for express payment methods with the following additions:

-   `savedTokenComponent`: This should be a React node that contains logic handling any processing your payment method has to do with saved payment methods if your payment method supports them. This component will be rendered whenever a customer's saved token using your payment method for processing is selected for making the purchase.
-   `label`: This should be a React node that will be used to output the label for the option where the payment methods are. For example it might be `<strong>Credit/Debit Cart</strong>` or you might output images.
-   `ariaLabel`: This is the label that will be read out via screen-readers when the payment method is selected.
-   `placeOrderButtonLabel`: This is an optional label which will change the default "Place Order" button text to something else when the payment method is selected. As an example, the PayPal Standard payment method [changes the text of the button to "Proceed to PayPal"](https://github.com/woocommerce/woocommerce-gutenberg-products-block/blob/e089ae17043fa525e8397d605f0f470959f2ae95/assets/js/payment-method-extensions/payment-methods/paypal/index.js#L37-L40) when it is selected as the payment method for checkout because the payment method takes the shopper offsite to PayPal for completing the payment.
-   `supports`: This is an object containing information about what features your payment method supports. The following keys are valid here:
    -   `showSavedCards`: This value will determine whether saved cards associated with your payment method are shown to the customer.
    -   `showSaveOption`: This value will control whether to show the checkbox which allows customers to save their payment method for future payments.

### Props Fed to Payment Method Nodes

A big part of the payment method integration is the interface that is exposed for payment methods to use via props when the node provided is cloned and rendered on block mount. While all the props are listed below, you can find more details about what the props reference, their types etc via the [typedefs described in this file](../../../../assets/js/types/type-defs/payment-method-interface.ts).

| Property                 | Type     | Description                                                                                                                                                                                                                                                                                                        | Values                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                 |
| ------------------------ | -------- | ------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------ | ---------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------- |
| `activePaymentMethod`    | String   | The slug of the current active payment method in the checkout.                                                                                                                                                                                                                                                     | -                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                      |
| `billing`                | Object   | Contains everything related to billing.                                                                                                                                                                                                                                                                            | `billingAddress`, `cartTotal`, `currency`, `cartTotalItems`, `displayPricesIncludingTax`, `appliedCoupons`, `customerId`                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                               |
| `cartData`               | Object   | Data exposed from the cart including items, fees, and any registered extension data. Note that this data should be treated as immutable (should not be modified/mutated) or it will result in errors in your application.                                                                                          | `cartItems`, `cartFees`, `extensions`                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                  |
| `checkoutStatus`         | Object   | The current checkout status exposed as various boolean state.                                                                                                                                                                                                                                                      | `isCalculating`, `isComplete`, `isIdle`, `isProcessing`                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                |
| `components`             | Object   | It exposes React components that can be implemented by your payment method for various common interface elements used by payment methods.                                                                                                                                                                          | <ul><li>`ValidationInputError`: a container for holding validation errors which typically you'll include after any inputs</li><li>[`PaymentMethodLabel`](https://github.com/woocommerce/woocommerce-gutenberg-products-block/blob/e089ae17043fa525e8397d605f0f470959f2ae95/assets/js/payment-method-extensions/payment-methods/paypal/index.js#L37-L40): use this component for the payment method label, including an optional icon</li><li>`PaymentMethodIcons`: a React component used for displaying payment method icons</li><li>`LoadingMask`: a wrapper component that handles displaying a loading state when the isLoading prop is true. Exposes the [LoadingMask component](https://github.com/woocommerce/woocommerce-gutenberg-products-block/blob/c9074a4941919987dbad16a80f358b960336a09d/assets/js/base/components/loading-mask/index.js) </li></ul>                                                                                                                                                                                                                                                                                                    |
| `emitResponse`           | Object   | Contains some constants that can be helpful when using the event emitter. Read the _[Emitting Events](https://github.com/woocommerce/woocommerce-gutenberg-products-block/blob/e267cd96a4329a4eeef816b2ef627e113ebb72a5/docs/extensibility/checkout-flow-and-events.md#emitting-events)_ section for more details. | <ul><li>`noticeContexts`: This is an object containing properties referencing areas where notices can be targeted in the checkout. The object has the following properties: <ul><li>`PAYMENTS`: This is a reference to the notice area in the payment methods step.</li><li>`EXPRESS_PAYMENTS`: This is a reference to the notice area in the express payment methods step.</li></ul></li><li>`responseTypes`: This is an object containing properties referencing the various response types that can be returned by observers for some event emitters. It makes it easier for autocompleting the types and avoiding typos due to human error. The types are `SUCCESS`, `FAIL`, `ERROR`. The values for these types also correspond to the [payment status types](https://github.com/woocommerce/woocommerce-gutenberg-products-block/blob/34e17c3622637dbe8b02fac47b5c9b9ebf9e3596/src/Payments/PaymentResult.php#L21) from the [checkout endpoint response from the server](https://github.com/woocommerce/woocommerce-gutenberg-products-block/blob/34e17c3622637dbe8b02fac47b5c9b9ebf9e3596/src/RestApi/StoreApi/Schemas/CheckoutSchema.php#L103-L113).</li></ul> |
| `eventRegistration`      | object   | Contains all the checkout event emitter registration functions. These are functions the payment method can register observers on to interact with various points in the checkout flow (see [this doc](./checkout-flow-and-events.md) for more info).                                                               | `onCheckoutValidation`, `onCheckoutSuccess`, `onCheckoutFail`, `onPaymentSetup`, `onShippingRateSuccess`, `onShippingRateFail`, `onShippingRateSelectSuccess`, `onShippingRateSelectFail`                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                              |
| `onClick`                | Function | **Provided to express payment methods** that should be triggered when the payment method button is clicked (which will signal to checkout the payment method has taken over payment processing)                                                                                                                    | -                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                      |
| `onClose`                | Function | **Provided to express payment methods** that should be triggered when the express payment method modal closes and control is returned to checkout.                                                                                                                                                                 | -                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                      |
| `onSubmit`               | Function | Submits the checkout and begins processing                                                                                                                                                                                                                                                                         | -                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                      |
| `paymentStatus`          | Object   | Various payment status helpers. Note, your payment method does not have to handle setting this status client side. Checkout will handle this via the responses your payment method gives from observers registered to [checkout event emitters](./checkout-flow-and-events.md).                                    | `isPristine`, `isStarted`, `isProcessing`, `isFinished`, `hasError`, `hasFailed`, `isSuccessful` (see below for explanation)                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                           |
| `setExpressPaymentError` | Function | Receives a string and allows express payment methods to set an error notice for the express payment area on demand. This can be necessary because some express payment method processing might happen outside of checkout events.                                                                                  | -                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                      |
| `shippingData`           | Object   | Contains all shipping related data (outside of the shipping status).                                                                                                                                                                                                                                               | `shippingRates`, `shippingRatesLoading`, `selectedRates`, `setSelectedRates`, `isSelectingRate`, `shippingAddress`, `setShippingAddress`, and `needsShipping`                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                          |
| `shippingStatus`         | Object   | Various shipping status helpers.                                                                                                                                                                                                                                                                                   | <ul><li>`shippingErrorStatus`: an object with various error statuses that might exist for shipping</li><li>`shippingErrorTypes`: an object containing all the possible types for shipping error status</li></ul>                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                       |
| `shouldSavePayment`      | Boolean  | Indicates whether or not the shopper has selected to save their payment method details (for payment methods that support saved payments). True if selected, false otherwise. Defaults to false.                                                                                                                    | -                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                      |

-   `isPristine`: This is true when the current payment status is `PRISTINE`.
-   `isStarted`: This is true when the current payment status is `EXPRESS_STARTED`.
-   `isProcessing`: This is true when the current payment status is `PROCESSING`.
-   `isFinished`: This is true when the current payment status is one of `ERROR`, `FAILED`, or`SUCCESS`.
-   `hasError`: This is true when the current payment status is `ERROR`.
-   `hasFailed`: This is true when the current payment status is `FAILED`.
-   `isSuccessful`: This is true when the current payment status is `SUCCESS`

Any registered `savedTokenComponent` node will also receive a `token` prop which includes the id for the selected saved token in case your payment method needs to use it for some internal logic. However, keep in mind, this is just the id representing this token in the database (and the value of the radio input the shopper checked), not the actual customer payment token (since processing using that usually happens on the server for security).

## Server Side Integration

### Processing Payment

The checkout block currently has legacy handling for payment processing. It converts incoming `payment_data` provided by the client-side payment method to `$_POST` and calls the payment gateway's `process_payment` function. If you already have a WooCommerce Payment method extension integrated with the existing shortcode checkout flow, the checkout block's legacy handling will take care of processing your payment for you on the server side. However, If your payment method hooks into the core checkout `process_checkout` function in any way, you will need to account for this behavior and make appropriate adjustments. (See the section below about hooking into the checkout process via the Store API.)

See an example of [Passing a value from the client through to server side payment processing](https://github.com/woocommerce/woocommerce-blocks/blob/62243e1731a0773f51b81fb8406ebc2e8b180b40/docs/internal-developers/block-client-apis/checkout/checkout-api.md#passing-a-value-from-the-client-through-to-server-side-payment-processing)

### Registering Assets

Implementing the correct loading of your client side asset registration is tricky for the blocks integration. This is because there are some dependencies on the _loading order_ of dependent assets in the request. To remove the complexity of this for extension consumers here, the server side API interface helps with ensuring you can register any assets and data to pass along to your client side payment method from the server and handles the correct loading order of those assets.

First, you create a class that extends `Automattic\WooCommerce\Blocks\Payments\Integrations\AbstractPaymentMethodType` (or you can implement the `Automattic\WooCommerce\Blocks\Payments\PaymentMethodTypeInterface`, but you get some functionality for free via the abstract class).

In your class:

-   Define a `name` property (which is a string used to reference your payment method).
-   Define an `initialize` function. This function will get called during the server side initialization process and is a good place to put any settings population etc. Basically anything you need to do to initialize your gateway. **Note, this will be called on every request so don't put anything expensive here.**
-   Define an `is_active` function. This should return whether the payment method is active or not.
-   Define a `get_payment_method_script_handles` function. In this function you should register your payment method scripts (using `wp_register_script`) and then return the script handles you registered with. This will be used to add your payment method as a dependency of the checkout script and thus take sure of loading it correctly. **Note:** You should still make sure any other asset dependencies your script has are registered properly here, if you're using Webpack to build your assets, you may want to use the [WooCommerce Webpack Dependency Extraction Plugin](https://www.npmjs.com/package/@woocommerce/dependency-extraction-webpack-plugin) to make this easier for you.
-   Define a `get_payment_method_script_handles_for_admin` function. Include this if your payment method has a script you _only_ want to load in the editor context for the checkout block. Include here any script from `get_payment_method_script_handles` that is also needed in the admin.
-   Define a `get_payment_method_data` function. You can return from this function an associative array of data you want to be exposed for your payment method client side. This data will be available client side via `wc.wcSettings.getSetting`. So for instance if you assigned `stripe` as the value of the `name` property for this class, client side you can access any data via: `wc.wcSettings.getSetting( 'stripe_data' )`. That would return an object matching the shape of the associative array you returned from this function.

### Hooking into the Checkout processing by the Store API

There may be some cases where the fallback legacy processing of Checkout requests from the StoreAPI mentioned earlier doesn't work for your existing payment method integration. For these cases, there is also an [action hook you can implement to hook into the server side processing of the order](https://github.com/woocommerce/woocommerce-gutenberg-products-block/blob/308e968c700028180cab391f2223eb0a43dd2d4d/src/RestApi/StoreApi/Routes/Checkout.php#L350-L361). **Note:** a good place to register your callback on this hook is in the `initialize` method of the payment method class you created from the above instructions.

This hook is the _preferred_ place to hook in your payment processing and if you set a status on the provided `PaymentResult` object, then the legacy processing will be ignored for your payment method. Hook callbacks will receive:

[`Automattic\WooCommerce\StoreApi\Payments\PaymentContext`](https://github.com/woocommerce/woocommerce-gutenberg-products-block/blob/trunk/src/StoreApi/Payments/PaymentContext.php)

This contains various details about the payment extracted from the checkout processing request. Notably is the `payment_data` property that will contain an associative array of data your payment method client side provided to checkout. It also contains a string value for `payment_method` which contains the `paymentMethodId` value for the active payment method used during checkout processing. So you can use this to determine whether your payment method processes this data or not.

[`Automattic\WooCommerce\StoreApi\Payments\PaymentResult`](https://github.com/woocommerce/woocommerce-gutenberg-products-block/blob/trunk/src/StoreApi/Payments/PaymentResult.php)

This contains various details about the payment result returned to the client and exposed on the `onAfterCheckoutProcessingWithSucces/WithError` event. Server side, your payment method can use this to:

-   set the status to return for the payment method (one of `success`, `failure`, `pending`, `error`).
-   set a redirect url.
-   set any additional payment details (in case you need to return something for your client to further process with).

### Putting it all together

So you've created a class extending `Automattic\WooCommerce\Blocks\Payments\Integrations\AbstractPaymentMethodType`, but you still need to _register_ this with the server side handling of payment methods. In order to do this you need to register a callback on the `woocommerce_blocks_payment_method_type_registration` action. Your callback will receive an instance of `Automattic\WooCommerce\Blocks\Payments\PaymentMethodRegistry` which has a `register` method for registering an instance of the class you created. It's also recommended that you register your callback on this action within the context of a callback on the `woocommerce_blocks_loaded` action.

> Note: With Cart and Checkout Blocks currently only available in the WooCommerce Blocks Feature plugin, you will want to make sure you check for the availability of the `Automattic\WooCommerce\Blocks\Payments\Integrations\AbstractPaymentMethodType` class before registering your payment method integration server side.

So for example, assuming your class that extends `Automattic\WooCommerce\Blocks\Payments\Integrations\AbstractPaymentMethodType` is named `MyPaymentMethod`. You would have this somewhere in your extension's bootstrap:

```php
use MyPlugin\MyPaymentMethod;
use Automattic\WooCommerce\Blocks\Payments\PaymentMethodRegistry;

add_action( 'woocommerce_blocks_loaded', 'my_extension_woocommerce_blocks_support' );

function my_extension_woocommerce_blocks_support() {
  if ( class_exists( 'Automattic\WooCommerce\Blocks\Payments\Integrations\AbstractPaymentMethodType' ) ) {
    add_action(
      'woocommerce_blocks_payment_method_type_registration',
      function( PaymentMethodRegistry $payment_method_registry ) {
        $payment_method_registry->register( new MyPaymentMethod );
      }
    );
  }
}
```

As an example, you can see how the Stripe extension adds it's integration in this [pull request](https://github.com/woocommerce/woocommerce-gateway-stripe/pull/1467/files).

<!-- FEEDBACK -->

---

[We're hiring!](https://woo.com/careers/) Come work with us!

🐞 Found a mistake, or have a suggestion? [Leave feedback about this document here.](https://github.com/woocommerce/woocommerce-blocks/issues/new?assignees=&labels=type%3A+documentation&template=--doc-feedback.md&title=Feedback%20on%20./docs/third-party-developers/extensibility/checkout-payment-methods/payment-method-integration.md)

<!-- /FEEDBACK -->
