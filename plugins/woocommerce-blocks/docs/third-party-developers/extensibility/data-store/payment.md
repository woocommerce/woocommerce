# Payment Store (`wc/store/payment`) <!-- omit in toc -->

## Table of Contents <!-- omit in toc -->

-   [Overview](#overview)
-   [Usage](#usage)
-   [Selectors](#selectors)
    -   [getState](#getstate)
    -   [isPaymentIdle](#ispaymentidle)
    -   [isExpressPaymentStarted](#isexpresspaymentstarted)
    -   [isPaymentProcessing](#ispaymentprocessing)
    -   [isPaymentReady](#ispaymentready)
    -   [hasPaymentError](#haspaymenterror)
    -   [isExpressPaymentMethodActive](#isexpresspaymentmethodactive)
    -   [getActiveSavedToken](#getactivesavedtoken)
    -   [getActivePaymentMethod](#getactivepaymentmethod)
    -   [getAvailablePaymentMethods](#getavailablepaymentmethods)
    -   [getAvailableExpressPaymentMethods](#getavailableexpresspaymentmethods)
    -   [getPaymentMethodData](#getpaymentmethoddata)
    -   [getSavedPaymentMethods](#getsavedpaymentmethods)
    -   [getActiveSavedPaymentMethods](#getactivesavedpaymentmethods)
    -   [getIncompatiblePaymentMethods](#getincompatiblepaymentmethods)
    -   [getShouldSavePaymentMethod](#getshouldsavepaymentmethod)
    -   [paymentMethodsInitialized](#paymentmethodsinitialized)
    -   [expressPaymentMethodsInitialized](#expresspaymentmethodsinitialized)
   	-   [getPaymentResult](#getpaymentresult)
    -   [(@deprecated) isPaymentPristine](#deprecated-ispaymentpristine)
    -   [(@deprecated) isPaymentStarted](#deprecated-ispaymentstarted)
    -   [(@deprecated) isPaymentSuccess](#deprecated-ispaymentsuccess)
    -   [(@deprecated) isPaymentFailed](#deprecated-ispaymentfailed)
    -   [(@deprecated) getCurrentStatus](#deprecated-getcurrentstatus)

## Overview

The payment data store is used to store payment method data and payment processing information. When the payment status changes, the data store will reflect this.

**⚠️ Currently, all the actions are internal-only while we determine which ones will be useful for extensions to interact with. We do not encourage extensions to dispatch actions onto this data store yet.**

An example of data held within the Payment Data Store is shown below. This example shows the state with several Payment Gateways active, and a saved token.

```js
{
    status: 'idle',
    activePaymentMethod: 'stripe',
    activeSavedToken: '1',
    availablePaymentMethods: {
      bacs: {
        name: 'bacs'
      },
      cheque: {
        name: 'cheque'
      },
      cod: {
        name: 'cod'
      },
      stripe: {
        name: 'stripe'
      }
    },
     availableExpressPaymentMethods: {
      payment_request: {
        name: 'payment_request'
      }
    },
    savedPaymentMethods: {
      cc: [
        {
          method: {
            gateway: 'stripe',
            last4: '4242',
            brand: 'Visa'
          },
          expires: '12/32',
          is_default: true,
          actions: {
            'delete': {
              url: 'https://store.local/checkout/delete-payment-method/1/?_wpnonce=123456',
              name: 'Delete'
            }
          },
          tokenId: 1
        }
      ]
    },
    paymentMethodData: {
      token: '1',
      payment_method: 'stripe',
      'wc-stripe-payment-token': '1',
      isSavedToken: true
    },
    paymentResult: null,
    paymentMethodsInitialized: true,
    expressPaymentMethodsInitialized: true,
    shouldSavePaymentMethod: false
}
```

## Usage

To utilize this store you will import the `PAYMENT_STORE_KEY` in any module referencing it. Assuming `@woocommerce/block-data` is registered as an external pointing to `wc.wcBlocksData` you can import the key via:

```js
const { PAYMENT_STORE_KEY } = window.wc.wcBlocksData;
```

## Selectors

### getState

Returns the current state of the payment store.

> 🚨 Instead of using this selector, the focused selectors should be used. This selector should only be used to mock selectors in our unit tests.

#### _Returns_ <!-- omit in toc -->

-   `object`: The current state of the payment store with the following properties:
   	-  _status_ `string`: The current status of the payment process. Possible values are: `idle`, `started`, `processing`, `ready`, `error`, `success`, `failed`.
   	-  _activePaymentMethod_ `string`: The ID of the active payment method.
   	-  _activeSavedToken_ `string`: The ID of the active saved token.
   	-  _availablePaymentMethods_ `object`: The available payment methods. This is currently just an object keyed by the payment method IDs. Each member contains a `name` entry with the payment method ID as its value.
   	-  _availableExpressPaymentMethods_ `object`: The available express payment methods. This is currently just an object keyed by the payment method IDs. Each member contains a `name` entry with the payment method ID as its value.
   	-  _savedPaymentMethods_ `object`: The saved payment methods for the current customer. This is an object, it will be specific to each payment method. As an example, Stripe's saved tokens are returned like so:
   	- _paymentMethodData_ `object`: The current payment method data. This is specific to each payment method so further details cannot be provided here.
   	- _paymentResult_ `object`: An object with the following properties:
      		- _message_ `string`: The message returned by the payment gateway.
      		- _paymentStatus_ `string`: The status of the payment. Possible values are: `success`, `failure`, `pending`, `error`, `not set`.
      		- _paymentDetails_ `object`: The payment details returned by the payment gateway.
      		- _redirectUrl_ `string`: The URL to redirect to after checkout is complete.
   	- _paymentMethodsInitialized_ `boolean`: True if the payment methods have been initialized, false otherwise.
   	- _expressPaymentMethodsInitialized_ `boolean`: True if the express payment methods have been initialized, false otherwise.
   	- _shouldSavePaymentMethod_ `boolean`: True if the payment method should be saved, false otherwise.

#### _Example_ <!-- omit in toc -->

```js
const store = select( PAYMENT_STORE_KEY );
const currentState = store.getState();
```

### isPaymentIdle

Queries if the status is `idle`.

#### _Returns_ <!-- omit in toc -->

-   `boolean`: True if the payment status is `idle`, false otherwise.

#### _Example_ <!-- omit in toc -->

```js
const store = select( PAYMENT_STORE_KEY );
const isPaymentIdle = store.isPaymentIdle();
```

### isExpressPaymentStarted

Queries if an express payment method has been clicked.

#### _Returns_ <!-- omit in toc -->

-   `boolean`: True if the button for an express payment method has been clicked, false otherwise.

#### _Example_ <!-- omit in toc -->

```js
const store = select( PAYMENT_STORE_KEY );
const isExpressPaymentStarted = store.isExpressPaymentStarted();
```

### isPaymentProcessing

Queries if the status is `processing`.

#### _Returns_ <!-- omit in toc -->

-   `boolean`: True if the payment status is `processing`, false otherwise.

#### _Example_ <!-- omit in toc -->

```js
const store = select( PAYMENT_STORE_KEY );
const isPaymentProcessing = store.isPaymentProcessing();
```

### isPaymentReady

Queries if the status is `ready`.

#### _Returns_ <!-- omit in toc -->

-   `boolean`: True if the payment status is `ready`, false otherwise.

#### _Example_ <!-- omit in toc -->

```js
const store = select( PAYMENT_STORE_KEY );
const isPaymentReady = store.isPaymentReady();
```

### hasPaymentError

Queries if the status is `error`.

#### _Returns_ <!-- omit in toc -->

-   `boolean`: True if the payment status is `error`, false otherwise.

#### _Example_ <!-- omit in toc -->

```js
const store = select( PAYMENT_STORE_KEY );
const hasPaymentError = store.hasPaymentError();
```

### isExpressPaymentMethodActive

Returns whether an express payment method is active, this will be true when the express payment method is open and taking user input. In the case of Google Pay it is when the modal is open but other payment methods may have different UIs.

#### _Returns_ <!-- omit in toc -->

-   `boolean`: Whether an express payment method is active.

#### _Example_ <!-- omit in toc -->

```js
const store = select( PAYMENT_STORE_KEY );
const isExpressPaymentMethodActive = store.isExpressPaymentMethodActive();
```

### getActiveSavedToken

Returns the active saved token. Payment methods that customers have saved to their account have tokens associated with them. If one of these is selected then this selector returns the token that is currently active. If one is not selected this will return an empty string.

#### _Returns_ <!-- omit in toc -->

-   `string`: The active saved token ID, or empty string if a saved token is not selected.

#### _Example_ <!-- omit in toc -->

```js
const store = select( PAYMENT_STORE_KEY );
const activeSavedToken = store.getActiveSavedToken();
```

### getActivePaymentMethod

Returns the active payment method's ID.

#### _Returns_ <!-- omit in toc -->

-   `string`: The active payment method's ID.

#### _Example_ <!-- omit in toc -->

```js
const store = select( PAYMENT_STORE_KEY );
const activePaymentMethod = store.getActivePaymentMethod();
```

### getAvailablePaymentMethods

Returns the available payment methods. This does not include express payment methods.

#### _Returns_ <!-- omit in toc -->

-   `object`: The available payment methods. This is currently just an object keyed by the payment method IDs. Each member contains a `name` entry with the payment method ID as its value.

#### _Example_ <!-- omit in toc -->

```js
const store = select( PAYMENT_STORE_KEY );
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
const store = select( PAYMENT_STORE_KEY );
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
const store = select( PAYMENT_STORE_KEY );
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
const store = select( PAYMENT_STORE_KEY );
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
const store = select( PAYMENT_STORE_KEY );
const activeSavedPaymentMethods = store.getActiveSavedPaymentMethods();
```

### getIncompatiblePaymentMethods

Returns the list of payment methods that are incompatible with Checkout block.

#### _Returns_ <!-- omit in toc -->

-   `object`: A list of incompatible payment methods with the following properties, or an empty object if no payment or express payment methods have been initialized:
   	-  _name_ `string`: The name of the payment method.

#### _Example_ <!-- omit in toc -->

```js
const store = select( PAYMENT_STORE_KEY );
const incompatiblePaymentMethods = store.getIncompatiblePaymentMethods();
```

### getShouldSavePaymentMethod

Returns whether the payment method should be saved to the customer's account.

#### _Returns_ <!-- omit in toc -->

-   `boolean`: True if the payment method should be saved, false otherwise.

#### _Example_ <!-- omit in toc -->

```js
const store = select( PAYMENT_STORE_KEY );
const shouldSavePaymentMethod = store.getShouldSavePaymentMethod();
```

### paymentMethodsInitialized

Returns whether the payment methods have been initialized.

#### _Returns_ <!-- omit in toc -->

-   `boolean`: True if the payment methods have been initialized, false otherwise.

#### _Example_ <!-- omit in toc -->

```js
const store = select( PAYMENT_STORE_KEY );
const paymentMethodsInitialized = store.paymentMethodsInitialized();
```

### expressPaymentMethodsInitialized

Returns whether the express payment methods have been initialized.

#### _Returns_ <!-- omit in toc -->

`boolean`: True if the express payment methods have been initialized, false otherwise.

#### _Example_ <!-- omit in toc -->

```js
const store = select( PAYMENT_STORE_KEY );
const expressPaymentMethodsInitialized =
	store.expressPaymentMethodsInitialized();
```

### getPaymentResult

Returns the result of the last payment attempt.

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
const store = select( PAYMENT_STORE_KEY );
const paymentResult = store.getPaymentResult();
```

### (@deprecated) isPaymentPristine

Queries if the status is `pristine`.

> ⚠️ This selector is deprecated and will be removed in a future release. Please use `isPaymentIdle` instead.

#### _Returns_ <!-- omit in toc -->

-   `boolean`: True if the payment status is `pristine`, false otherwise.

#### _Example_ <!-- omit in toc -->

```js
const store = select( PAYMENT_STORE_KEY );
const isPaymentPristine = store.isPaymentPristine();
```

### (@deprecated) isPaymentStarted

Queries if the status is `started`.

> ⚠️ This selector is deprecated and will be removed in a future release. Please use `isExpressPaymentStarted` instead.

#### _Returns_ <!-- omit in toc -->

-   `boolean`: True if the payment status is `started`, false otherwise.

#### _Example_ <!-- omit in toc -->

```js
const store = select( PAYMENT_STORE_KEY );
const isPaymentStarted = store.isPaymentStarted();
```

### (@deprecated) isPaymentSuccess

Queries if the status is `success`.

> ⚠️ This selector is deprecated and will be removed in a future release. Please use `isPaymentReady` instead.

#### _Returns_ <!-- omit in toc -->

-   `boolean`: True if the payment status is `success`, false otherwise.

#### _Example_ <!-- omit in toc -->

```js
const store = select( PAYMENT_STORE_KEY );
const isPaymentSuccess = store.isPaymentSuccess();
```

### (@deprecated) isPaymentFailed

Queries if the status is `failed`.

> ⚠️ This selector is deprecated and will be removed in a future release. Please use `hasPaymentError` instead.

#### _Returns_ <!-- omit in toc -->

-   `boolean`: True if the payment status is `failed`, false otherwise.

#### _Example_ <!-- omit in toc -->

```js
const store = select( PAYMENT_STORE_KEY );
const isPaymentFailed = store.isPaymentFailed();
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
const store = select( PAYMENT_STORE_KEY );
const currentStatus = store.getCurrentStatus();
```

<!-- FEEDBACK -->

---

[We're hiring!](woocommerce.com/careers/) Come work with us!

🐞 Found a mistake, or have a suggestion? [Leave feedback about this document here.](https://github.com/woocommerce/woocommerce-blocks/issues/new?assignees=&labels=type%3A+documentation&template=--doc-feedback.md&title=Feedback%20on%20./docs/third-party-developers/extensibility/data-store/payment.md)

<!-- /FEEDBACK -->
