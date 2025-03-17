---
post_title: Checkout Block - Payment method integration
menu_title: Payment Method Integration
tags: reference
---

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

return &lt;button style={height: `${height}px`, borderRadius: `${borderRadius}px`} /&gt;
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

A big part of the payment method integration is the interface that is exposed for payment methods to use via props when the node provided is cloned and rendered on block mount. While all the props are listed below, you can find more details about what the props reference, their types etc via the [typedefs described in this file](../../../plugins/woocommerce/client/blocks/assets/js/types/type-defs/payment-method-interface.ts).

| Property                 | Type                                                                                                                                                                                                                                                                                                                                                                      | Description                                                                                                                                                                                                                                                                                                        |
| ------------------------ | ------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------- | ------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------ |
| `activePaymentMethod`    | String                                                                                                                                                                                                                                                                                                                                                                    | The slug of the current active payment method in the checkout.                                                                                                                                                                                                                                                     |
| `billing`                | { `billingAddress`, `cartTotal`, `currency`, `cartTotalItems`, `displayPricesIncludingTax`, `appliedCoupons`, `customerId` }                                                                                                                                                                                                                                             | Contains everything related to billing.                                                                                                                                                                                                                                                                            |
| `cartData`               | { `cartItems`, `cartFees`, `extensions` }                                                                                                                                                                                                                                                                                                                                 | Data exposed from the cart including items, fees, and any registered extension data. Note that this data should be treated as immutable (should not be modified/mutated) or it will result in errors in your application.                                                                                          |
| `checkoutStatus`         | { `isCalculating`, `isComplete`, `isIdle`, `isProcessing` }                                                                                                                                                                                                                                                                                                               | The current checkout status exposed as various boolean state.                                                                                                                                                                                                                                                      |
| `components`             | { `ValidationInputError`, `PaymentMethodLabel`, `PaymentMethodIcons`, `LoadingMask` }                                                                                                                                                                                                                                                                                      | It exposes React components that can be implemented by your payment method for various common interface elements used by payment methods.                                                                                                                                                                          |
| `emitResponse`           | { `noticeContexts`: { `PAYMENTS`, `EXPRESS_PAYMENTS` }, `responseTypes`: { `SUCCESS`, `FAIL`, `ERROR` } }                                                                                                                                                                                                                                                                 | Contains some constants that can be helpful when using the event emitter. Read the _[Emitting Events](https://github.com/woocommerce/woocommerce-gutenberg-products-block/blob/e267cd96a4329a4eeef816b2ef627e113ebb72a5/docs/extensibility/checkout-flow-and-events.md#emitting-events)_ section for more details. |
| `eventRegistration`      | { `onCheckoutValidation`, `onCheckoutSuccess`, `onCheckoutFail`, `onPaymentSetup`, `onShippingRateSuccess`, `onShippingRateFail`, `onShippingRateSelectSuccess`, `onShippingRateSelectFail` }                                                                                                                                                                            | Contains all the checkout event emitter registration functions. These are functions the payment method can register observers on to interact with various points in the checkout flow (see [this doc](./checkout-flow-and-events.md) for more info).                                                               |
| `onClick`                | Function                                                                                                                                                                                                                                                                                                                                                                   | **Provided to express payment methods** that should be triggered when the payment method button is clicked (which will signal to checkout the payment method has taken over payment processing)                                                                                                                    |
| `onClose`                | Function                                                                                                                                                                                                                                                                                                                                                                   | **Provided to express payment methods** that should be triggered when the express payment method modal closes and control is returned to checkout.                                                                                                                                                                 |
| `onSubmit`               | Function                                                                                                                                                                                                                                                                                                                                                                   | Submits the checkout and begins processing                                                                                                                                                                                                                                                                         |
| `buttonAttributes`       | { `height`, `borderRadius` }                                                                                                                                                                                                                                                                                                                                              | Styles set by the merchant that should be respected by all express payment buttons                                                                                                                                                                                                                                 |
| `paymentStatus`          | Object                                                                                                                                                                                                                                                                       | Various payment status helpers. Note, your payment method does not have to handle setting this status client side. Checkout will handle this via the responses your payment method gives from observers registered to [checkout event emitters](./checkout-flow-and-events.md).                                    |
| `paymentStatus.isPristine`             | Boolean                                                                                                                                                                                                                                                                                                                                                                    | This is true when the current payment status is `PRISTINE`.                                                                                                                                                                                                                                                        |
| `paymentStatus.isStarted`              | Boolean                                                                                                                                                                                                                                                                                                                                                                    | This is true when the current payment status is `EXPRESS_STARTED`.                                                                                                                                                                                                                                                  |
| `paymentStatus.isProcessing`           | Boolean                                                                                                                                                                                                                                                                                                                                                                    | This is true when the current payment status is `PROCESSING`.                                                                                                                                                                                                                                                      |
| `paymentStatus.isFinished`             | Boolean                                                                                                                                                                                                                                                                                                                                                                    | This is true when the current payment status is one of `ERROR`, `FAILED`, or `SUCCESS`.                                                                                                                                                                                                                            |
| `paymentStatus.hasError`               | Boolean                                                                                                                                                                                                                                                                                                                                                                    | This is true when the current payment status is `ERROR`.                                                                                                                                                                                                                                                           |
| `paymentStatus.hasFailed`              | Boolean                                                                                                                                                                                                                                                                                                                                                                    | This is true when the current payment status is `FAILED`.                                                                                                                                                                                                                                                          |
| `paymentStatus.isSuccessful`           | Boolean                                                                                                                                                                                                                                                                                                                                                                    | This is true when the current payment status is `SUCCESS`.                                                                                                                                                                                                                                                         |
| `setExpressPaymentError` | Function                                                                                                                                                                                                                                                                                                                                                                   | Receives a string and allows express payment methods to set an error notice for the express payment area on demand. This can be necessary because some express payment method processing might happen outside of checkout events.                                                                                  |
| `shippingData`           | { `shippingRates`, `shippingRatesLoading`, `selectedRates`, `setSelectedRates`, `isSelectingRate`, `shippingAddress`, `setShippingAddress`, `needsShipping` }                                                                                                                                                                                                             | Contains all shipping related data (outside of the shipping status).                                                                                                                                                                                                                                               |
| `shippingStatus`         | { `shippingErrorStatus`, `shippingErrorTypes` }                                                                                                                                                                                                                                                                                                                            | Various shipping status helpers.                                                                                                                                                                                                                                                                                   |
| `shouldSavePayment`      | Boolean                                                                                                                                                                                                                                                                                                                                                                    | Indicates whether or not the shopper has selected to save their payment method details (for payment methods that support saved payments). True if selected, false otherwise. Defaults to false.                                                                                                                    |

Any registered `savedTokenComponent` node will also receive a `token` prop which includes the id for the selected saved token in case your payment method needs to use it for some internal logic. However, keep in mind, this is just the id representing this token in the database (and the value of the radio input the shopper checked), not the actual customer payment token (since processing using that usually happens on the server for security).

## Server Side Integration

For the server side integration, you need to create a class that extends the `Automattic\WooCommerce\Blocks\Payments\Integrations\AbstractPaymentMethodType` class. 

This class is the server side representation of your payment method. It is used to handle the registration of your payment methods assets with the Store API and Checkout block at the correct time. It is not the same as the [Payment Gateway API](../../payments/payment-gateway-api.md) that you need to implement separately for payment processing.

### Example Payment Method Integration Class

```php
<?php
namespace MyPlugin\MyPaymentMethod;

use Automattic\WooCommerce\Blocks\Payments\Integrations\AbstractPaymentMethodType;

final class MyPaymentMethodType extends AbstractPaymentMethodType {
	/**
	 * This property is a string used to reference your payment method. It is important to use the same name as in your
	 * client-side JavaScript payment method registration.
	 *
	 * @var string
	 */
	protected $name = 'my_payment_method';

	/**
	 * Initializes the payment method.
	 * 
	 * This function will get called during the server side initialization process and is a good place to put any settings
	 * population etc. Basically anything you need to do to initialize your gateway. 
	 * 
	 * Note, this will be called on every request so don't put anything expensive here.
	 */
	public function initialize() {
		$this->settings = get_option( 'woocommerce_my_payment_method_settings', [] );
	}

	/**
	 * This should return whether the payment method is active or not. 
	 * 
	 * If false, the scripts will not be enqueued.
	 *
	 * @return boolean
	 */
	public function is_active() {
		return filter_var( $this->get_setting( 'enabled', false ), FILTER_VALIDATE_BOOLEAN );
	}

	/**
	 * Returns an array of scripts/handles to be registered for this payment method.
	 * 
	 * In this function you should register your payment method scripts (using `wp_register_script`) and then return the 
	 * script handles you registered with. This will be used to add your payment method as a dependency of the checkout script 
	 * and thus take sure of loading it correctly. 
	 * 
	 * Note that you should still make sure any other asset dependencies your script has are registered properly here, if 
	 * you're using Webpack to build your assets, you may want to use the WooCommerce Webpack Dependency Extraction Plugin
	 * (https://www.npmjs.com/package/@woocommerce/dependency-extraction-webpack-plugin) to make this easier for you.
	 *
	 * @return array
	 */
	public function get_payment_method_script_handles() {
		wp_register_script(
			'my-payment-method',
			'path/to/your/script/my-payment-method.js',
			[],
			'1.0.0',
			true
		);
		return [ 'my-payment-method' ];
	}

	/**
	 * Returns an array of script handles to be enqueued for the admin.
	 * 
	 * Include this if your payment method has a script you _only_ want to load in the editor context for the checkout block. 
	 * Include here any script from `get_payment_method_script_handles` that is also needed in the admin.
	 */
	public function get_payment_method_script_handles_for_admin() {
		return $this->get_payment_method_script_handles();
	}

	/**
	 * Returns an array of key=>value pairs of data made available to the payment methods script client side.
	 * 
	 * This data will be available client side via `wc.wcSettings.getSetting`. So for instance if you assigned `stripe` as the 
	 * value of the `name` property for this class, client side you can access any data via: 
	 * `wc.wcSettings.getSetting( 'stripe_data' )`. That would return an object matching the shape of the associative array 
	 * you returned from this function.
	 *
	 * @return array
	 */
	public function get_payment_method_data() {
		return [
			'title'       => $this->get_setting( 'title' ),
			'description' => $this->get_setting( 'description' ),
			'supports'    => $this->get_supported_features(),
		];
	}
}
```

### Registering the Payment Method Integration

After creating a class that extends `Automattic\WooCommerce\Blocks\Payments\Integrations\AbstractPaymentMethodType`, you need to register it with the server side handling of payment methods. 

You can do this by using the `register` method on the `PaymentMethodRegistry` class. 

```php
use MyPlugin\MyPaymentMethod\MyPaymentMethodType;
use Automattic\WooCommerce\Blocks\Payments\PaymentMethodRegistry;

add_action(
	'woocommerce_blocks_payment_method_type_registration',
	function( PaymentMethodRegistry $payment_method_registry ) {
		$payment_method_registry->register( new MyPaymentMethodType() );
	}
);
```

## Processing Payments (legacy support)

Payments are still handled via the [Payment Gateway API](../../payments/payment-gateway-api.md). This is a separate API from the one used for the payment methods integration above.

The checkout block converts incoming `payment_data` provided by the client-side script to `$_POST` and calls the Payment Gateway `process_payment` method. 

_If you already have a WooCommerce Payment method extension integrated with the shortcode checkout flow, the legacy handling will take care of processing your payment for you on the server side._

## Processing Payments via the Store API

There may be more advanced cases where the legacy payment processing mentioned earlier doesn't work for your existing payment method integration. For these cases, there is also an action hook you can use to handle the server side processing of the order which provides more context and is specific to the Store API.

This hook is the _preferred_ place to hook in your payment processing:

```php
do_action_ref_array( 'woocommerce_rest_checkout_process_payment_with_context', [ $context, &$result ] );
```

> Note: A good place to register your callback on this hook is in the `initialize` method of the payment method type class you created earlier

A callback on this hook will receive:

- A `PaymentContext` object which contains the chosen `payment_method` (this is the same as the `paymentMethodId` value defined when registering your payment method), the `order` being placed, and any additional `payment_data` provided by the payment method client.
- A `PaymentResult` object which you can use to set the status, redirect url, and any additional payment details back to the client via the Store API.

If you set a status on the provided `PaymentResult` object, legacy payment processing will be ignored for your payment method. If there is an error, your callback can throw an exception which will be handled by the Store API. 

Here is an example callback:

```php
add_action(
	'woocommerce_rest_checkout_process_payment_with_context',
	function( $context, $result ) {
		if ( $context->payment_method === 'my_payment_method' ) {
			// Order processing would happen here!
			// $context->order contains the order object if needed
			// ...

			// If the logic above was successful, we can set the status to success.
			$result->set_status( 'success' );
			$result->set_payment_details(
				array_merge(
					$result->payment_details,
					[
						'custom-data' => '12345',
					]
				)
			);
			$result->set_redirect_url( 'some/url/to/redirect/to' );
		}
	},
  10,
  2
);
```

## Passing values from the client to the server side payment processing

In this example, lets pass some data from the BACS payment method to the server. Registration of BACS looks something like this:

```js
// Get our settings that were provided when the payment method was registered
const settings = window.wc.wcSettings.getSetting( 'bacs_data' );

// This is a component that would be rendered in the checkout block when the BACS payment method is selected
const Content = () => {
	return decodeEntities( settings?.description || '' );
};

// This is the label for the payment method
const Label = ( props ) => {
	const { PaymentMethodLabel } = props.components;
	return <PaymentMethodLabel text={ decodeEntities( settings?.title || 'BACS' ) } />;
};

// Register the payment method
const bankTransferPaymentMethod = {
	name: 'BACS',
	label: <Label />,
	content: <Content />,
	edit: <Content />,
	canMakePayment: () => true,
	supports: {
		features: settings?.supports ?? [],
	},
};
```

Payment method nodes are passed everything from the [usePaymentMethodInterface hook](https://github.com/woocommerce/woocommerce-blocks/blob/trunk/docs/internal-developers/block-client-apis/checkout/checkout-api.md#usepaymentmethodinterface). So we can consume this in our `<Content />` component like this:

```js
const Content = ( props ) => {
	const { eventRegistration, emitResponse } = props;
	const { onPaymentProcessing } = eventRegistration;
	useEffect( () => {
		const unsubscribe = onPaymentProcessing( async () => {
			// Here we can do any processing we need, and then emit a response.
			// For example, we might validate a custom field, or perform an AJAX request, and then emit a response indicating it is valid or not.
			const myGatewayCustomData = '12345';
			const customDataIsValid = !! myGatewayCustomData.length;

			if ( customDataIsValid ) {
				return {
					type: emitResponse.responseTypes.SUCCESS,
					meta: {
						paymentMethodData: {
							myGatewayCustomData,
						},
					},
				};
			}

			return {
				type: emitResponse.responseTypes.ERROR,
				message: 'There was an error',
			};
		} );
		// Unsubscribes when this component is unmounted.
		return () => {
			unsubscribe();
		};
	}, [
		emitResponse.responseTypes.ERROR,
		emitResponse.responseTypes.SUCCESS,
		onPaymentProcessing,
	] );
	return decodeEntities( settings.description || '' );
};
```

Now when an order is placed, if we look at the API request payload, we can see the following JSON:

```json
{
	"shipping_address": {},
	"billing_address": {},
	"customer_note": "",
	"create_account": false,
	"payment_method": "bacs",
	"payment_data": [
		{
			"key": "myGatewayCustomData",
			"value": "12345"
		}
	],
	"extensions": {}
}
```

A callback on `woocommerce_rest_checkout_process_payment_with_context` can then access this data and use it to process the payment.

```php
add_action( 'woocommerce_rest_checkout_process_payment_with_context', function( $context, $result ) {
  if ( $context->payment_method === 'bacs' ) {
    $myGatewayCustomData = $context->payment_data['myGatewayCustomData'];
    // Here we would use the $myGatewayCustomData to process the payment
  }
}, 10, 2 );
