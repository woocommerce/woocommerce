# Checkout API interface <!-- omit in toc -->

**Note on migration:** We are in the process of moving much of the data from contexts into data stores, so this portion of the docs may change often as we do this. We will try to keep it up to date while the work is carried out.

## Table of Contents <!-- omit in toc -->

-   [Data Stores](#data-stores)
    -   [Checkout Data Store](#checkout-data-store)
        -   [Selectors](../../../third-party-developers/extensibility/data-store/checkout.md#selectors)
        -   [Actions](#actions)
-   [Contexts](#contexts)
    -   [Shipping Method Data context](#shipping-method-data-context)
    -   [Payment Method Events Context](#payment-method-events-context)
    -   [Checkout Events Context](#checkout-events-context)
-   [Hooks](#hooks)
    -   [`usePaymentMethodInterface`](#usepaymentmethodinterface)
-   [Examples](#examples)
    -   [Passing a value from the client through to server side payment processing](#passing-a-value-from-the-client-through-to-server-side-payment-processing)

This document gives an overview of some of the major architectural components/APIs for the checkout block. If you haven't already, you may also want to read about the [Checkout Flow and Events](checkout-flow-and-events.md).

## Data Stores

We are transitioning much of what is now available in Contexts, to `@wordpress/data` stores.

### Checkout Data Store

This is responsible for holding all the data required for the checkout process.

For more details on the checkout data store, see the [Checkout Data Store](../../../third-party-developers/extensibility/data-store/checkout.md) docs.

#### Selectors

For a full list of selectors see the [Checkout Data Store](

Data can be accessed through the following selectors:

-   `isComplete()`: True when checkout has finished processing and the subscribed checkout processing callbacks have all been invoked along with a successful processing of the checkout by the server.
-   `isIdle()`: When the checkout status is `IDLE` this flag is true. Checkout will be this status after any change to checkout state after the block is loaded. It will also be this status when retrying a purchase is possible after processing happens with an error.
-   `isBeforeProcessing()`: When the checkout status is `BEFORE_PROCESSING` this flag is true. Checkout will be this status when the user submits checkout for processing.
-   `isProcessing()`: When the checkout status is `PROCESSING` this flag is true. Checkout will be this status when all the observers on the event emitted with the `BEFORE_PROCESSING` status are completed without error. It is during this status that the block will be sending a request to the server on the checkout endpoint for processing the order.
-   `isAfterProcessing()`: When the checkout status is `AFTER_PROCESSING` this flag is true. Checkout will have this status after the block receives the response from the server side processing request.
-   `isComplete()`: When the checkout status is `COMPLETE` this flag is true. Checkout will have this status after all observers on the events emitted during the `AFTER_PROCESSING` status are completed successfully. When checkout is at this status, the shopper's browser will be redirected to the value of `redirectUrl` at that point (usually the `order-received` route).
-   `isCalculating()`: This is true when the total is being re-calculated for the order. There are numerous things that might trigger a recalculation of the total: coupons being added or removed, shipping rates updated, shipping rate selected etc. This flag consolidates all activity that might be occurring (including requests to the server that potentially affect calculation of totals). So instead of having to check each of those individual states you can reliably just check if this boolean is true (calculating) or false (not calculating).
-   `hasOrder()`: This is true when orderId is truthy.
-   `hasError()`: This is true when the checkout has an error.
-   `getOrderNotes()`: Returns the order notes.
-   `getCustomerId()`: Returns the customer ID.
-   `getOrderId()`: Returns the order ID.
-   `getRedirectUrl()`: Returns the redirect URL.
-   `getExtensionData()`: Returns the data registered by extensions.
-   `getCheckoutStatus()`: Returns the checkout status.
-   `getShouldCreateAccount()`: Returns true if the shopper has opted to create an account with their order.
-   `getUseShippingAsBilling()`: Returns the value of the `useShippingAsBilling` flag.

#### Actions

The following actions can be dispatched from the Checkout data store:

-   `__internalSetIdle()`: Set `state.status` to `idle`
-   `__internalSetComplete()`: Set `state.status` to `complete`
-   `__internalSetProcessing()`: Set `state.status` to `processing`
-   `__internalSetBeforeProcessing()`: Set `state.status` to `before_processing`
-   `__internalSetAfterProcessing()`: Set `state.status` to `after_processing`
-   `__internalSrocessCheckoutResponse( response: CheckoutResponse )`: This is a thunk that will extract the paymentResult from the CheckoutResponse, and dispatch 3 actions: `__internalSetRedirectUrl`, `__internalSetPaymentResult` and `__internalSetAfterProcessing`.
-   `__internalSetRedirectUrl( url: string )`: Set `state.redirectUrl` to `url`
-   `__internalSetHasError( trueOrFalse: bool )`: Set `state.hasError` to `trueOrFalse`
-   `__internalIncrementCalculating()`: Increment `state.calculatingCount`
-   `__internalDecrementCalculating()`: Decrement `state.calculatingCount`
-   `__internalSetCustomerId( id: number )`: Set `state.customerId` to `id`
-   `__internalSetUseShippingAsBilling( useShippingAsBilling: boolean )`: Set `state.useShippingAsBilling` to `useShippingAsBilling`
-   `__internalSetShouldCreateAccount( shouldCreateAccount: boolean )`: Set `state.shouldCreateAccount` to `shouldCreateAccount`
-   `__internalSetOrderNotes( orderNotes: string )`: Set `state.orderNotes` to `orderNotes`
-   `__internalSetExtensionData( namespace: string, extensionData: Record< string, unknown > )`: Set `state.extensionData` to `extensionData`

## Contexts

Much of the data and api interface for components in the Checkout Block are constructed and exposed via [usage of `React.Context`](https://reactjs.org/docs/context.html). In some cases the context maintains the "tree" state within the context itself (via `useReducer`) and in others it interacts with a global `wp.data` store (for data that communicates with the server).

You can find type definitions (`typedef`) for contexts in [this file](../../../../assets/js/types/type-defs/contexts.js).

### Shipping Method Data context

The shipping method data context exposes the api interfaces for the following things (typedef `ShippingMethodDataContext`) via the `useShippingMethodData` hook:

-   `shippingErrorStatus`: The current error status for the context.
-   `dispatchErrorStatus`: A function for dispatching a shipping error status. Used in combination with...
-   `shippingErrorTypes`: An object with the various error statuses that can be dispatched (`NONE`, `INVALID_ADDRESS`, `UNKNOWN`)
-   `onShippingRateSuccess`: This is a function for registering a callback to be invoked when shipping rates are retrieved successfully. Callbacks will receive the new rates as an argument.
-   `onShippingRateFail`: This is a function for registering a callback to be invoked when shipping rates fail to be retrieved. Callbacks will receive the error status as an argument.
-   `onShippingRateSelectSuccess`: This is a function for registering a callback to be invoked when shipping rate selection is successful.
-   `onShippingRateSelectFail`: This is a function for registering a callback to be invoked when shipping rates selection is unsuccessful.

### Payment Method Events Context

The payment method events context exposes any event handlers related to payments (typedef `PaymentEventsContext`) via the `usePaymentEventsContext` hook.

-   `onPaymentSetup`: This is an event subscriber that can be used to subscribe observers to be called when the payment is being setup.
-   ~~`onPaymentProcessing`~~: This event was deprecated in favour of `onPaymentSetup`.

### Checkout Events Context

The checkout events contexy exposes any event handlers related to the processing of the checkout:

-   `onSubmit`: This is a callback to be invoked either by submitting the checkout button, or by express payment methods to start checkout processing after they have finished their initialization process when their button has been clicked.
-   `onCheckoutValidation`: Used to register observers that will be invoked at validation time, after the checkout has been submitted but before the processing request is sent to the server.
-   `onCheckoutSuccess`: Used to register observers that will be invoked after checkout has been processed by the server successfully.
-   `onCheckoutError`: Used to register observers that will be invoked after checkout has been processed by the server and there was an error.

## Hooks

These docs currently don't go into detail for all the hooks as that is fairly straightforward from existing implementations. However, one important extension interface hook will be highlighted here, `usePaymentMethodInterface`.

### `usePaymentMethodInterface`

This hook is used to expose all the interfaces for the registered payment method components to utilize. Essentially the result from this hook is fed in as props on the registered payment components when they are setup by checkout. You can use the typedef ([`PaymentMethodInterface`](../../../../assets/js/types/type-defs/payment-method-interface.ts)) to see what is fed to payment methods as props from this hook.

_Why don't payment methods just implement this hook_?

The contract is established through props fed to the payment method components via props. This allows us to avoid having to expose the hook publicly and experiment with how the props are retrieved and exposed in the future.

## Examples

### Passing a value from the client through to server side payment processing

In this example, lets pass some data from the BACS payment method to the server. Registration of BACS looks like this:

```js
const bankTransferPaymentMethod = {
	name: PAYMENT_METHOD_NAME,
	label: <Label />,
	content: <Content />,
	edit: <Content />,
	canMakePayment: () => true,
	ariaLabel: label,
	supports: {
		features: settings?.supports ?? [],
	},
};
```

If we look a the `Content` component, we can see it defined as follows:

```js
const Content = () => {
	return decodeEntities( settings.description || '' );
};
```

Payment method components are passed, by default, everything from the [`usePaymentMethodInterface` hook](https://github.com/woocommerce/woocommerce-blocks/blob/trunk/docs/internal-developers/block-client-apis/checkout/checkout-api.md#usepaymentmethodinterface). So we can consume this in our component like so:

```js
const Content = ( props ) => {
	const { eventRegistration, emitResponse } = props;
	const { onPaymentSetup } = eventRegistration;
	useEffect( () => {
		const unsubscribe = onPaymentSetup( async () => {
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
		onPaymentSetup,
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
