---
post_title: Cart and Checkout - Checkout flow and events
menu_title: Checkout Flow and Events
tags: reference
---

This document gives an overview of the flow for the checkout in the WooCommerce checkout block, and some general architectural overviews.

The architecture of the Checkout Block is derived from the following principles:

-   A single source of truth for data within the checkout flow.
-   Provide a consistent interface for extension integrations (eg Payment methods). This interface protects the integrity of the checkout process and isolates extension logic from checkout logic. The checkout block handles _all_ communication with the server for processing the order. Extensions are able to react to and communicate with the checkout block via the provided interface.
-   Checkout flow state is tracked by checkout status.
-   Extensions are able to interact with the checkout flow via subscribing to emitted events.

Here's a high level overview of the flow:

![checkout flow diagram](https://user-images.githubusercontent.com/1628454/113739726-f8c9df00-96f7-11eb-80f1-78e25ccc88cb.png)

## General Concepts

### Tracking flow through status

At any point in the checkout lifecycle, components should be able to accurately detect the state of the checkout flow. This includes things like:

-   Is something loading? What is loading?
-   Is there an error? What is the error?
-   is the checkout calculating totals?

Using simple booleans can be fine in some cases, but in others it can lead to complicated conditionals and bug prone code (especially for logic behaviour that reacts to various flow state).

To surface the flow state, the block uses statuses that are tracked in the various contexts. _As much as possible_ these statuses are set internally in reaction to various actions so there's no implementation needed in children components (components just have to _consume_ the status not set status).

The following statuses exist in the Checkout.

#### Checkout Data Store Status

There are various statuses that are exposed on the Checkout data store via selectors. All the selectors are detailed below and in the [Checkout API docs](https://github.com/woocommerce/woocommerce-blocks/blob/trunk/docs/internal-developers/block-client-apis/checkout/checkout-api.md).

You can use them in your component like so

```jsx
const { useSelect } = window.wp.data;
const { CHECKOUT_STORE_KEY } = window.wc.wcBlocksData;

const MyComponent = ( props ) => {
	const isComplete = useSelect( ( select ) =>
		select( CHECKOUT_STORE_KEY ).isComplete()
	);
	// do something with isComplete
};
```

The following boolean flags available related to status are:

**isIdle**: When the checkout status is `IDLE` this flag is true. Checkout will be this status after any change to checkout state after the block is loaded. It will also be this status when retrying a purchase is possible after processing happens with an error.

**isBeforeProcessing**: When the checkout status is `BEFORE_PROCESSING` this flag is true. Checkout will be this status when the user submits checkout for processing.

**isProcessing**: When the checkout status is `PROCESSING` this flag is true. Checkout will be this status when all the observers on the event emitted with the `BEFORE_PROCESSING` status are completed without error. It is during this status that the block will be sending a request to the server on the checkout endpoint for processing the order. **Note:** there are some checkout payment status changes that happen during this state as well (outlined in the `PaymentProvider` exposed statuses section).

**isAfterProcessing**: When the checkout status is `AFTER_PROCESSING` this flag is true. Checkout will have this status after the block receives the response from the server side processing request.

**isComplete**: When the checkout status is `COMPLETE` this flag is true. Checkout will have this status after all observers on the events emitted during the `AFTER_PROCESSING` status are completed successfully. When checkout is at this status, the shopper's browser will be redirected to the value of `redirectUrl` at that point (usually the `order-received` route).

#### Special States

The following are booleans exposed via the checkout provider that are independent from each other and checkout statuses but can be used in combination to react to various state in the checkout.

**isCalculating:** This is true when the total is being re-calculated for the order. There are numerous things that might trigger a recalculation of the total: coupons being added or removed, shipping rates updated, shipping rate selected etc. This flag consolidates all activity that might be occurring (including requests to the server that potentially affect calculation of totals). So instead of having to check each of those individual states you can reliably just check if this boolean is true (calculating) or false (not calculating).

**hasError:** This is true when anything in the checkout has created an error condition state. This might be validation errors, request errors, coupon application errors, payment processing errors etc.

### `ShippingProvider` Exposed Statuses

The shipping context provider exposes everything related to shipping in the checkout. Included in this are a set of error statuses that inform what error state the shipping context is in and the error state is affected by requests to the server on address changes, rate retrieval and selection.

Currently the error status may be one of `NONE`, `INVALID_ADDRESS` or `UNKNOWN` (note, this may change in the future).

The status is exposed on the `currentErrorStatus` object provided by the `useShippingDataContext` hook. This object has the following properties on it:

-   `isPristine` and `isValid`: Both of these booleans are connected to the same error status. When the status is `NONE` the values for these booleans will be `true`. It basically means there is no shipping error.
-   `hasInvalidAddress`: When the address provided for shipping is invalid, this will be true.
-   `hasError`: This is `true` when the error status for shipping is either `UNKNOWN` or `hasInvalidAddress`.

### Payment Method Data Store Status

The status of the payment lives in the payment data store. You can query the status with the following selectors:

```jsx
const { select } = window.wp.data;
const { PAYMENT_STORE_KEY } = window.wc.wcBlocksData;

const MyComponent = ( props ) => {
	const isPaymentIdle = select( PAYMENT_STORE_KEY ).isPaymentIdle();
	const isExpressPaymentStarted =
		select( PAYMENT_STORE_KEY ).isExpressPaymentStarted();
	const isPaymentProcessing =
		select( PAYMENT_STORE_KEY ).isPaymentProcessing();
	const isPaymentReady = select( PAYMENT_STORE_KEY ).isPaymentReady();
	const hasPaymentError = select( PAYMENT_STORE_KEY ).hasPaymentError();

	// do something with the boolean values
};
```

The status here will help inform the current state of _client side_ processing for the payment and are updated via the store actions at different points throughout the checkout processing cycle. _Client side_ means the state of processing any payments by registered and active payment methods when the checkout form is submitted via those payment methods registered client side components. It's still possible that payment methods might have additional server side processing when the order is being processed but that is not reflected by these statuses (more in the [payment method integration doc](./payment-method-integration.md)).

The possible _internal_ statuses that may be set are:

-   `IDLE`: This is the status when checkout is initialized and there are payment methods that are not doing anything. This status is also set whenever the checkout status is changed to `IDLE`.
-   `EXPRESS_STARTED`: **Express Payment Methods Only** - This status is used when an express payment method has been triggered by the user clicking it's button. This flow happens before processing, usually in a modal window.
-   `PROCESSING`: This status is set when the checkout status is `PROCESSING`, checkout `hasError` is false, checkout is not calculating, and the current payment status is not `FINISHED`. When this status is set, it will trigger the payment processing event emitter.
-   `READY`: This status is set after all the observers hooked into the payment processing event have completed successfully. The `CheckoutProcessor` component uses this along with the checkout `PROCESSING` status to signal things are ready to send the order to the server with data for processing and to take payment
-   `ERROR`: This status is set after an observer hooked into the payment processing event returns an error response. This in turn will end up causing the checkout `hasError` flag to be set to true.

### Emitting Events

Another tricky thing for extensibility, is providing opinionated, yet flexible interfaces for extensions to act and react to specific events in the flow. For stability, it's important that the core checkout flow _controls_ all communication to and from the server specific to checkout/order processing and leave extension specific requirements for the extension to handle. This allows for extensions to predictably interact with the checkout data and flow as needed without impacting other extensions hooking into it.

One of the most reliable ways to implement this type of extensibility is via the usage of an events system. Thus the various context providers:

-   expose subscriber APIs for extensions to subscribe _observers_ to the events they want to react to.
-   emit events at specific points of the checkout flow that in turn will feed data to the registered observers and, in some cases, react accordingly to the responses from observers.

One _**very important rule**_ when it comes to observers registered to any event emitter in this system is that they _cannot_ update context state. Updating state local to a specific component is okay but not any context or global state. The reason for this is that the observer callbacks are run sequentially at a specific point and thus subsequent observers registered to the same event will not react to any change in global/context state in earlier executed observers.

```jsx
const unsubscribe = emitter( myCallback );
```

You could substitute in whatever emitter you are registering for the `emitter` function. So for example if you are registering for the `onCheckoutValidation` event emitter, you'd have something like:

```jsx
const unsubscribe = onCheckoutValidation( myCallback );
```

You can also indicate what priority you want your observer to execute at. Lower priority is run before higher priority, so you can affect when your observer will run in the stack of observers registered to an emitter. You indicate priority via an number on the second argument:

```jsx
const unsubscribe = onCheckoutValidation( myCallback, 10 );
```

In the examples, `myCallback`, is your subscriber function. The subscriber function could receive data from the event emitter (described in the emitter details below) and may be expected to return a response in a specific shape (also described in the specific emitter details). The subscriber function can be a `Promise` and when the event emitter cycles through the registered observers it will await for any registered Promise to resolve.

Finally, the return value of the call to the emitter function is an unsubscribe function that can be used to unregister your observer. This is especially useful in a React component context where you need to make sure you unsubscribe the observer on component unmount. An example is usage in a `useEffect` hook:

```jsx
const MyComponent = ( { onCheckoutValidation } ) => {
	useEffect( () => {
		const unsubscribe = onCheckoutValidation( () => true );
		return unsubscribe;
	}, [ onCheckoutValidation ] );
	return null;
};
```

**`Event Emitter Utilities`**

There are a bunch of utility methods that can be used related to events. These are available in `assets/js/base/context/event-emit/utils.ts` and can be imported as follows:

```jsx
import {
	isSuccessResponse,
	isErrorResponse,
	isFailResponse,
	noticeContexts,
	responseTypes,
	shouldRetry,
} from '@woocommerce/base-context';
};
```

The helper functions are described below:

-   `isSuccessResponse`, `isErrorResponse` and `isFailResponse`: These are helper functions that receive a value and report via boolean whether the object is a type of response expected. For event emitters that receive responses from registered observers, a `type` property on the returned object from the observer indicates what type of response it is and event emitters will react according to that type. So for instance if an observer returned `{ type: 'success' }` the emitter could feed that to `isSuccessResponse` and it would return `true`. You can see an example of this being implemented for the payment processing emitted event [here](https://github.com/woocommerce/woocommerce-gutenberg-products-block/blob/34e17c3622637dbe8b02fac47b5c9b9ebf9e3596/assets/js/base/context/cart-checkout/payment-methods/payment-method-data-context.js#L281-L307).
-   `noticeContexts`: This is an object containing properties referencing areas where notices can be targeted in the checkout. The object has the following properties:
    -   `PAYMENTS`: This is a reference to the notice area in the payment methods step.
    -   `EXPRESS_PAYMENTS`: This is a reference to the notice area in the express payment methods step.
-   `responseTypes`: This is an object containing properties referencing the various response types that can be returned by observers for some event emitters. It makes it easier for autocompleting the types and avoiding typos due to human error. The types are `SUCCESS`, `FAIL`, `ERROR`. The values for these types also correspond to the [payment status types](https://github.com/woocommerce/woocommerce-gutenberg-products-block/blob/34e17c3622637dbe8b02fac47b5c9b9ebf9e3596/src/Payments/PaymentResult.php#L21) from the [checkout endpoint response from the server](https://github.com/woocommerce/woocommerce-gutenberg-products-block/blob/34e17c3622637dbe8b02fac47b5c9b9ebf9e3596/src/RestApi/StoreApi/Schemas/CheckoutSchema.php#L103-L113).
-   `shouldRetry`: This is a function containing the logic whether the checkout flow should allow the user to retry the payment after a previous payment failed. It receives the `response` object and by default checks whether the `retry` property is true/undefined or false. Refer to the [`onCheckoutSuccess`](#oncheckoutsuccess) documentation for more details.

Note: `noticeContexts` and `responseTypes` are exposed to payment methods via the `emitResponse` prop given to their component:

```jsx
const MyPaymentMethodComponent = ( { emitResponse } ) => {
	const { noticeContexts, responseTypes } = emitResponse;
	// other logic for payment method...
};
```

The following event emitters are available to extensions to register observers to:

### `onCheckoutValidation`

Observers registered to this event emitter will receive nothing as an argument. Also, all observers will be executed before the checkout handles the responses from the emitters. Observers registered to this emitter can return `true` if they have nothing to communicate back to checkout, `false` if they want checkout to go back to `IDLE` status state, or an object with any of the following properties:

-   `errorMessage`: This will be added as an error notice on the checkout context.
-   `validationErrors`: This will be set as inline validation errors on checkout fields. If your observer wants to trigger validation errors it can use the following shape for the errors:
    -   This is an object where keys are the property names the validation error is for (that correspond to a checkout field, eg `country` or `coupon`) and values are the error message describing the validation problem.

This event is emitted when the checkout status is `BEFORE_PROCESSING` (which happens at validation time, after the checkout form submission is triggered by the user - or Express Payment methods).

If all observers return `true` for this event, then the checkout status will be changed to `PROCESSING`.

This event emitter subscriber can be obtained via the checkout context using the `useCheckoutContext` hook or to payment method extensions as a prop on their registered component:

_For internal development:_

```jsx
import { useCheckoutContext } from '@woocommerce/base-contexts';
import { useEffect } from '@wordpress/element';

const Component = () => {
	const { onCheckoutValidation } = useCheckoutContext();
	useEffect( () => {
		const unsubscribe = onCheckoutValidation( () => true );
		return unsubscribe;
	}, [ onCheckoutValidation ] );
	return null;
};
```

_For registered payment method components:_

```jsx
const { useEffect } = window.wp.element;

const PaymentMethodComponent = ( { eventRegistration } ) => {
	const { onCheckoutValidation } = eventRegistration;
	useEffect( () => {
		const unsubscribe = onCheckoutValidation( () => true );
		return unsubscribe;
	}, [ onCheckoutValidation ] );
};
```

### ~~`onPaymentProcessing`~~

This is now deprecated and replaced by the `onPaymentSetup` event emitter.

### `onPaymentSetup`

This event emitter was fired when the payment method context status is `PROCESSING` and that status is set when the checkout status is `PROCESSING`, checkout `hasError` is false, checkout is not calculating, and the current payment status is not `FINISHED`.

This event emitter will execute through each registered observer (passing in nothing as an argument) _until_ an observer returns a non-truthy value at which point it will _abort_ further execution of registered observers.

When a payment method returns a non-truthy value, if it returns a valid response type the event emitter will update various internal statuses according to the response. Here's the possible response types that will get handled by the emitter:

#### Success

A successful response should be given when the user's entered data is correct and the payment checks are successful. A response is considered successful if, at a minimum, it is an object with this shape:

```js
const successResponse = { type: 'success' };
```

When a success response is returned, the payment method context status will be changed to `SUCCESS`. In addition, including any of the additional properties will result in extra actions:

-   `paymentMethodData`: The contents of this object will be included as the value for `payment_data` when checkout sends a request to the checkout endpoint for processing the order. This is useful if a payment method does additional server side processing.
-   `billingAddress`: This allows payment methods to update any billing data information in the checkout (typically used by Express payment methods) so it's included in the checkout processing request to the server. This data should be in the [shape outlined here](https://github.com/woocommerce/woocommerce/blob/trunk/plugins/woocommerce-blocks/assets/js/settings/shared/default-fields.ts).
-   `shippingAddress`: This allows payment methods to update any shipping data information for the order (typically used by Express payment methods) so it's included in the checkout processing request to the server. This data should be in the [shape outlined here](https://github.com/woocommerce/woocommerce/blob/trunk/plugins/woocommerce-blocks/assets/js/settings/shared/default-fields.ts).

If `billingAddress` or `shippingAddress` properties aren't in the response object, then the state for the data is left alone.

#### Fail

A fail response should be given when there is an error with the payment processing. A response is considered a fail response when it is an object with this shape:

```js
const failResponse = { type: 'failure' };
```

When a fail response is returned by an observer, the payment method context status will be changed to `FAIL`. In addition, including any of the following properties will result in extra actions:

-   `message`: The string provided here will be set as an error notice in the checkout.
-   `messageContext`: If provided, this will target the given area for the error notice (this is where `noticeContexts` mentioned earlier come in to play). Otherwise the notice will be added to the `noticeContexts.PAYMENTS` area.
-   `paymentMethodData`: (same as for success responses).
-   `billingAddress`: (same as for success responses).

#### Error

An error response should be given when there is an error with the user input on the checkout form. A response is considered an error response when it is an object with this shape:

```js
const errorResponse = { type: 'error' };
```

When an error response is returned by an observer, the payment method context status will be changed to `ERROR`. In addition, including any of the following properties will result in extra actions:

-   `message`: The string provided here will be set as an error notice.
-   `messageContext`: If provided, this will target the given area for the error notice (this is where `noticeContexts` mentioned earlier come in to play). Otherwise, the notice will be added to the `noticeContexts.PAYMENTS` area.
-   `validationErrors`: This will be set as inline validation errors on checkout fields. If your observer wants to trigger validation errors it can use the following shape for the errors:
    -   This is an object where keys are the property names the validation error is for (that correspond to a checkout field, eg `country` or `coupon`) and values are the error message describing the validation problem.

If the response object doesn't match any of the above conditions, then the fallback is to set the payment status as `SUCCESS`.

When the payment status is set to `SUCCESS` and the checkout status is `PROCESSING`, the `CheckoutProcessor` component will trigger the request to the server for processing the order.

This event emitter subscriber can be obtained via the checkout context using the `usePaymentEventsContext` hook or to payment method extensions as a prop on their registered component:

_For internal development:_

```jsx
import { usePaymentEventsContext } from '@woocommerce/base-contexts';
import { useEffect } from '@wordpress/element';

const Component = () => {
	const { onPaymentSetup } = usePaymentEventsContext();
	useEffect( () => {
		const unsubscribe = onPaymentSetup( () => true );
		return unsubscribe;
	}, [ onPaymentSetup ] );
	return null;
};
```

_For registered payment method components:_

```jsx
const { useEffect } = window.wp.element;

const PaymentMethodComponent = ( { eventRegistration } ) => {
	const { onPaymentSetup } = eventRegistration;
	useEffect( () => {
		const unsubscribe = onPaymentSetup( () => true );
		return unsubscribe;
	}, [ onPaymentSetup ] );
};
```

### `onCheckoutSuccess`

This event emitter is fired when the checkout status is `AFTER_PROCESSING` and the checkout `hasError` state is false. The `AFTER_PROCESSING` status is set by the `CheckoutProcessor` component after receiving a response from the server for the checkout processing request.

Observers registered to this event emitter will receive the following object as an argument:

```js
const onCheckoutProcessingData = {
	redirectUrl,
	orderId,
	customerId,
	orderNotes,
	paymentResult,
};
```

The properties are:

-   `redirectUrl`: This is a string that is the url the checkout will redirect to as returned by the processing on the server.
-   `orderId`: Is the id of the current order being processed.
-   `customerId`: Is the id for the customer making the purchase (that is attached to the order).
-   `orderNotes`: This will be any custom note the customer left on the order.
-   `paymentResult`: This is the value of [`payment_result` from the /checkout StoreApi response](https://github.com/woocommerce/woocommerce-gutenberg-products-block/blob/34e17c3622637dbe8b02fac47b5c9b9ebf9e3596/src/RestApi/StoreApi/Schemas/CheckoutSchema.php#L103-L138). The data exposed on this object is (via the object properties):
    -   `paymentStatus`: Whatever the status is for the payment after it was processed server side. Will be one of `success`, `failure`, `pending`, `error`.
    -   `paymentDetails`: This will be an arbitrary object that contains any data the payment method processing server side sends back to the client in the checkout processing response. Payment methods are able to hook in on the processing server side and set this data for returning.

This event emitter will invoke each registered observer until a response from any of the registered observers does not equal `true`. At that point any remaining non-invoked observers will be skipped and the response from the observer triggering the abort will be processed.

This emitter will handle a `success` response type (`{ type: success }`) by setting the checkout status to `COMPLETE`. Along with that, if the response includes `redirectUrl` then the checkout will redirect to the given address.

This emitter will also handle a `failure` response type or an `error` response type and if no valid type is detected it will treat it as an `error` response type.

In all cases, if there are the following properties in the response, additional actions will happen:

-   `message`: This string will be added as an error notice.
-   `messageContext`: If present, the notice will be configured to show in the designated notice area (otherwise it will just be a general notice for the checkout block).
-   `retry`: If this is `true` or not defined, then the checkout status will be set to `IDLE`. This basically means that the error is recoverable (for example try a different payment method) and so checkout will be reset to `IDLE` for another attempt by the shopper. If this is `false`, then the checkout status is set to `COMPLETE` and the checkout will redirect to whatever is currently set as the `redirectUrl`.
-   `redirectUrl`: If this is present, then the checkout will redirect to this url when the status is `COMPLETE`.

If all observers return `true`, then the checkout status will just be set to `COMPLETE`.

This event emitter subscriber can be obtained via the checkout context using the `useCheckoutContext` hook or to payment method extensions as a prop on their registered component:

_For internal development:_

```jsx
import { useCheckoutContext } from '@woocommerce/base-contexts';
import { useEffect } from '@wordpress/element';

const Component = () => {
	const { onCheckoutSuccess } = useCheckoutContext();
	useEffect( () => {
		const unsubscribe = onCheckoutSuccess( () => true );
		return unsubscribe;
	}, [ onCheckoutSuccess ] );
	return null;
};
```

_For registered payment method components:_

```jsx
const { useEffect } = window.wp.element;

const PaymentMethodComponent = ( { eventRegistration } ) => {
	const { onCheckoutSuccess } = eventRegistration;
	useEffect( () => {
		const unsubscribe = onCheckoutSuccess( () => true );
		return unsubscribe;
	}, [ onCheckoutSuccess ] );
};
```

### `onCheckoutFail`

This event emitter is fired when the checkout status is `AFTER_PROCESSING` and the checkout `hasError` state is `true`. The `AFTER_PROCESSING` status is set by the `CheckoutProcessor` component after receiving a response from the server for the checkout processing request.

Observers registered to this emitter will receive the same data package as those registered to `onCheckoutSuccess`.

The response from the first observer returning a value that does not `===` true will be handled similarly as the `onCheckoutSuccess` except it only handles when the type is `error` or `failure`.

If all observers return `true`, then the checkout status will just be set to `IDLE` and a default error notice will be shown in the checkout context.

This event emitter subscriber can be obtained via the checkout context using the `useCheckoutContext` hook or to payment method extensions as a prop on their registered component:

_For internal development:_

```jsx
import { useCheckoutContext } from '@woocommerce/base-contexts';
import { useEffect } from '@wordpress/element';

const Component = () => {
	const { onCheckoutFail } = useCheckoutContext();
	useEffect( () => {
		const unsubscribe = onCheckoutFail( () => true );
		return unsubscribe;
	}, [ onCheckoutFail ] );
	return null;
};
```

_For registered payment method components:_

```jsx
const { useEffect } = window.wp.element;

const PaymentMethodComponent = ( { eventRegistration } ) => {
	const { onCheckoutFail } = eventRegistration;
	useEffect( () => {
		const unsubscribe = onCheckoutFail( () => true );
		return unsubscribe;
	}, [ onCheckoutFail ] );
};
```

### `onShippingRateSuccess`

This event emitter is fired when shipping rates are not loading and the shipping data context error state is `NONE` and there are shipping rates available.

This event emitter doesn't care about any registered observer response and will simply execute all registered observers passing them the current shipping rates retrieved from the server.

### `onShippingRateFail`

This event emitter is fired when shipping rates are not loading and the shipping data context error state is `UNKNOWN` or `INVALID_ADDRESS`.

This event emitter doesn't care about any registered observer response and will simply execute all registered observers passing them the current error status in the context.

### `onShippingRateSelectSuccess`

This event emitter is fired when a shipping rate selection is not being persisted to the server and there are selected rates available and the current error status in the context is `NONE`.

This event emitter doesn't care about any registered observer response and will simply execute all registered observers passing them the current selected rates.

### `onShippingRateSelectFail`

This event emitter is fired when a shipping rate selection is not being persisted to the server and the shipping data context error state is `UNKNOWN` or `INVALID_ADDRESS`.

This event emitter doesn't care about any registered observer response and will simply execute all registered observers passing them the current error status in the context.
