# Payment Store (`wc/store/payment`) <!-- omit in toc -->

## Table of Contents <!-- omit in toc -->

-   [Overview](#overview)
-   [Usage](#usage)
-   [Selectors](#selectors)
    -   [(@deprecated) isPaymentPristine](#deprecated-ispaymentpristine)
    -   [isPaymentIdle](#ispaymentidle)
    -   [(@deprecated) isPaymentStarted](#deprecated-ispaymentstarted)
    -   [isExpressPaymentStarted](#isexpresspaymentstarted)
    -   [isPaymentProcessing](#ispaymentprocessing)
    -   [(@deprecated) isPaymentSuccess](#deprecated-ispaymentsuccess)
    -   [isPaymentReady](#ispaymentready)
    -   [(@deprecated) isPaymentFailed](#deprecated-ispaymentfailed)
    -   [hasPaymentError](#haspaymenterror)
    -   [(@deprecated) getCurrentStatus](#deprecated-getcurrentstatus)
    -   [isExpressPaymentMethodActive](#isexpresspaymentmethodactive)
    -   [getActiveSavedToken](#getactivesavedtoken)
    -   [getActivePaymentMethod](#getactivepaymentmethod)
    -   [getAvailablePaymentMethods](#getavailablepaymentmethods)
    -   [getAvailableExpressPaymentMethods](#getavailableexpresspaymentmethods)
    -   [getPaymentMethodData](#getpaymentmethoddata)
    -   [getSavedPaymentMethods](#getsavedpaymentmethods)
    -   [getActiveSavedPaymentMethods](#getactivesavedpaymentmethods)
    -   [getShouldSavePaymentMethod](#getshouldsavepaymentmethod)
    -   [paymentMethodsInitialized](#paymentmethodsinitialized)
    -   [expressPaymentMethodsInitialized](#expresspaymentmethodsinitialized)
    -   [getPaymentResult](#getpaymentresult)

## Overview

The payment data store is used to store payment method data and payment processing information. When the payment status changes, the data store will reflect this.

Currently, all the actions are internal-only while we determine which ones will be useful for extensions to interact with. We do not encourage extensions to dispatch actions onto this data store yet.

## Usage

To utilize this store you will import the `PAYMENT_STORE_KEY` in any module referencing it. Assuming `@woocommerce/block-data` is registered as an external pointing to `wc.wcBlocksData` you can import the key via:

```js
import { PAYMENT_STORE_KEY } from '@woocommerce/block-data';
```

## Selectors

### (@deprecated) isPaymentPristine

Queries if the status is `pristine`

> ⚠️ This selector is deprecated and will be removed in a future release. Please use `isPaymentIdle` instead.

#### _Returns_ <!-- omit in toc -->

-   `boolean`: True if the payment status is `pristine`, false otherwise.

#### _Example_ <!-- omit in toc -->

```js
const store = select( 'wc/store/payment' );
const isPaymentPristine = store.isPaymentPristine();
```

### isPaymentIdle

Queries if the status is `idle`

#### _Returns_ <!-- omit in toc -->

-   `boolean`: True if the payment status is `idle`, false otherwise.

#### _Example_ <!-- omit in toc -->

```js
const store = select( 'wc/store/payment' );
const isPaymentIdle = store.isPaymentIdle();
```

### (@deprecated) isPaymentStarted

Queries if the status is `started`.

> ⚠️ This selector is deprecated and will be removed in a future release. Please use `isExpressPaymentStarted` instead.

#### _Returns_ <!-- omit in toc -->

-   `boolean`: True if the payment status is `started`, false otherwise.

#### _Example_ <!-- omit in toc -->

```js
const store = select( 'wc/store/payment' );
const isPaymentStarted = store.isPaymentStarted();
```

### isExpressPaymentStarted

Queries if an express payment method has been clicked.

#### _Returns_ <!-- omit in toc -->

-   `boolean`: True if the button for an express payment method has been clicked, false otherwise.

#### _Example_ <!-- omit in toc -->

```js
const store = select( 'wc/store/payment' );
const isExpressPaymentStarted = store.isExpressPaymentStarted();
```

### isPaymentProcessing

Queries if the status is `processing`.

#### _Returns_ <!-- omit in toc -->

-   `boolean`: True if the payment status is `processing`, false otherwise.

#### _Example_ <!-- omit in toc -->

```js
const store = select( 'wc/store/payment' );
const isPaymentProcessing = store.isPaymentProcessing();
```

### (@deprecated) isPaymentSuccess

Queries if the status is `success`.

> ⚠️ This selector is deprecated and will be removed in a future release. Please use isPaymentReady instead.

#### _Returns_ <!-- omit in toc -->

-   `boolean`: True if the payment status is `success`, false otherwise.

#### _Example_ <!-- omit in toc -->

```js
const store = select( 'wc/store/payment' );
const isPaymentSuccess = store.isPaymentSuccess();
```

### isPaymentReady

Queries if the status is `ready`.

#### _Returns_ <!-- omit in toc -->

-   `boolean`: True if the payment status is `ready`, false otherwise.

#### _Example_ <!-- omit in toc -->

```js
const store = select( 'wc/store/payment' );
const isPaymentReady = store.isPaymentReady();
```

### (@deprecated) isPaymentFailed

Queries if the status is `failed`.

> ⚠️ This selector is deprecated and will be removed in a future release. Please use `hasPaymentError` instead.

#### _Returns_ <!-- omit in toc -->

-   `boolean`: True if the payment status is `failed`, false otherwise.

#### _Example_ <!-- omit in toc -->

```js
const store = select( 'wc/store/payment' );
const isPaymentFailed = store.isPaymentFailed();
```

### hasPaymentError

Queries if the status is `error`.

#### _Returns_ <!-- omit in toc -->

-   `boolean`: True if the payment status is `error`, false otherwise.

#### _Example_ <!-- omit in toc -->

```js
const store = select( 'wc/store/payment' );
const hasPaymentError = store.hasPaymentError();
```

### (@deprecated) getCurrentStatus

Returns an object with booleans representing the payment status.

> ⚠️ This selector is deprecated and will be removed in a future release. Please use the selectors above.

#### _Returns_ <!-- omit in toc -->

-   `object`: The current payment status with the following keys:
    -   _isPristine_ `boolean`: True if the payment process has not started, does not have an error and has not finished. This is true initially.
    -   _isStarted_ `boolean`: True if the payment process has started.
    -   _isProcessing_ `boolean`: True if the payment is processing.
    -   _hasError_ `boolean`: True if the payment process has resulted in an error.
    -   _hasFailed_ `boolean`: True if the payment process has failed.
    -   _isSuccessful_ `boolean`: True if the payment process is successful.
    -   _isDoingExpressPayment_ `boolean`: True if an express payment method is active, false otherwise

#### _Example_ <!-- omit in toc -->

```js
const store = select( 'wc/store/payment' );
const currentStatus = store.getCurrentStatus();
```

### isExpressPaymentMethodActive

Returns whether an express payment method is active, this will be true when the express payment method is open and taking user input. In the case of Google Pay it is when the modal is open but other payment methods may have different UIs.

#### _Returns_ <!-- omit in toc -->

-   `boolean`: Whether an express payment method is active.

#### _Example_ <!-- omit in toc -->

```js
const store = select( 'wc/store/payment' );
const isExpressPaymentMethodActive = store.isExpressPaymentMethodActive();
```

### getActiveSavedToken

Returns the active saved token. Payment methods that customers have saved to their account have tokens associated with them. If one of these is selected then this selector returns the token that is currently active. If one is not selected this will return an empty string.

#### _Returns_ <!-- omit in toc -->

-   `string`: The active saved token ID, or empty string if a saved token is not selected.

#### _Example_ <!-- omit in toc -->

```js
const store = select( 'wc/store/payment' );
const activeSavedToken = store.getActiveSavedToken();
```

### getActivePaymentMethod

Returns the active payment method's ID.

#### _Returns_ <!-- omit in toc -->

-   `string`: The active payment method's ID.

#### _Example_ <!-- omit in toc -->

```js
const store = select( 'wc/store/payment' );
const activePaymentMethod = store.getActivePaymentMethod();
```

### getAvailablePaymentMethods

Returns the available payment methods. This does not include express payment methods.

#### _Returns_ <!-- omit in toc -->

-   `object`: The available payment methods. This is currently just an object keyed by the payment method IDs. Each member contains a `name` entry with the payment method ID as its value.

#### _Example_ <!-- omit in toc -->

```js
const store = select( 'wc/store/payment' );
const availablePaymentMethods = store.getAvailablePaymentMethods();
```

`availablePaymentMethods` will look like this:

```js
{
    "cheque": {
        name: "cheque",
    },
    "cod": {
        name: "cod",
    },
    "bacs": {
        name: "bacs",
    },
}
```

### getAvailableExpressPaymentMethods

Returns the available express payment methods.

#### _Returns_ <!-- omit in toc -->

-   `object`: The available express payment methods. This is currently just an object keyed by the payment method IDs. Each member contains a `name` entry with the payment method ID as its value.

#### _Example_ <!-- omit in toc -->

```js
const store = select( 'wc/store/payment' );
const availableExpressPaymentMethods =
	store.getAvailableExpressPaymentMethods();
```

`availableExpressPaymentMethods` will look like this:

```js
{
    "payment_request": {
        name: "payment_request",
    },
    "other_express_method": {
        name: "other_express_method",
    },
}
```

### getPaymentMethodData

Returns the current payment method data. This will change every time the active payment method changes and is not persisted for each payment method. For example, if the customer has PayPal selected, the payment method data will be specific to that payment method. If they switch to Stripe, the payment method data will be overwritten by Stripe, and the previous value (when PayPal was selected) will not be available anymore.

#### _Returns_ <!-- omit in toc -->

-   `object`: The current payment method data. This is specific to each payment method so further details cannot be provided here.

#### _Example_ <!-- omit in toc -->

```js
const store = select( 'wc/store/payment' );
const paymentMethodData = store.getPaymentMethodData();
```

### getSavedPaymentMethods

Returns all saved payment methods for the current customer.

#### _Returns_ <!-- omit in toc -->

-   `object`: The saved payment methods for the current customer. This is an object, it will be specific to each payment method. As an example, Stripe's saved tokens are returned like so:

```js
savedPaymentMethods: {
	cc: [
		{
			method: {
				gateway: 'stripe',
				last4: '4242',
				brand: 'Visa',
			},
			expires: '04/24',
			is_default: true,
			actions: {
				wcs_deletion_error: {
					url: '#choose_default',
					name: 'Delete',
				},
			},
			tokenId: 2,
		},
	];
}
```

#### _Example_ <!-- omit in toc -->

```js
const store = select( 'wc/store/payment' );
const savedPaymentMethods = store.getSavedPaymentMethods();
```

### getActiveSavedPaymentMethods

Returns the saved payment methods for the current customer that are active, i.e. the ones that can be used to pay for the current order.

#### _Returns_ <!-- omit in toc -->

`object` - The saved payment methods for the current customer that are active, i.e. the ones that can be used to pay for this order. This is an object, it will be specific to each payment method. As an example, Stripe's saved tokens are returned like so:

```js
activeSavedPaymentMethods: {
	cc: [
		{
			method: {
				gateway: 'stripe',
				last4: '4242',
				brand: 'Visa',
			},
			expires: '04/24',
			is_default: true,
			actions: {
				wcs_deletion_error: {
					url: '#choose_default',
					name: 'Delete',
				},
			},
			tokenId: 2,
		},
	];
}
```

#### _Example_ <!-- omit in toc -->

```js
const store = select( 'wc/store/payment' );
const activeSavedPaymentMethods = store.getActiveSavedPaymentMethods();
```

### getShouldSavePaymentMethod

Returns whether the payment method should be saved to the customer's account.

#### _Returns_ <!-- omit in toc -->

-   `boolean`: True if the payment method should be saved, false otherwise.

#### _Example_ <!-- omit in toc -->

```js
const store = select( 'wc/store/payment' );
const shouldSavePaymentMethod = store.getShouldSavePaymentMethod();
```

### paymentMethodsInitialized

Returns whether the payment methods have been initialized.

#### _Returns_ <!-- omit in toc -->

-   `boolean`: True if the payment methods have been initialized, false otherwise.

#### _Example_ <!-- omit in toc -->

```js
const store = select( 'wc/store/payment' );
const paymentMethodsInitialized = store.paymentMethodsInitialized();
```

### expressPaymentMethodsInitialized

Returns whether the express payment methods have been initialized.

#### _Returns_ <!-- omit in toc -->

`boolean`: True if the express payment methods have been initialized, false otherwise.

#### _Example_ <!-- omit in toc -->

```js
const store = select( 'wc/store/payment' );
const expressPaymentMethodsInitialized =
	store.expressPaymentMethodsInitialized();
```

### getPaymentResult

Returns the result of the last payment attempt

#### _Returns_ <!-- omit in toc -->

-   `object`: An object with the following properties:

```ts
{
	message: string;
	paymentStatus: 'success' | 'failure' | 'pending' | 'error' | 'not set';
	paymentDetails: Record< string, string > | Record< string, never >;
	redirectUrl: string;
}
```

#### _Example_ <!-- omit in toc -->

```js
const store = select( 'wc/store/payment' );
const paymentResult = store.getPaymentResult();
```

<!-- FEEDBACK -->

---

[We're hiring!](https://woocommerce.com/careers/) Come work with us!

🐞 Found a mistake, or have a suggestion? [Leave feedback about this document here.](https://github.com/woocommerce/woocommerce-blocks/issues/new?assignees=&labels=type%3A+documentation&template=--doc-feedback.md&title=Feedback%20on%20./docs/third-party-developers/extensibility/data-store/payment.md)

<!-- /FEEDBACK -->
