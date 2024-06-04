# Checkout Store (`wc/store/checkout`) <!-- omit in toc -->

> 💡 What's the difference between the Cart Store and the Checkout Store?
>
> The **Cart Store (`wc/store/cart`)** manages and retrieves data about the shopping cart, including items, customer data, and interactions like coupons.
>
> The **Checkout Store (`wc/store/checkout`)** manages and retrieves data related to the checkout process, customer IDs, order IDs, and checkout status.

## Table of Contents <!-- omit in toc -->

-   [Overview](#overview)
-   [Usage](#usage)
-   [Selectors](#selectors)
    -   [getCustomerId](#getcustomerid)
    -   [getOrderId](#getorderid)
    -   [getOrderNotes](#getordernotes)
    -   [getRedirectUrl](#getredirecturl)
    -   [getExtensionData](#getextensiondata)
    -   [getCheckoutStatus](#getcheckoutstatus)
    -   [getShouldCreateAccount](#getshouldcreateaccount)
    -   [getUseShippingAsBilling](#getuseshippingasbilling)
    -   [hasError](#haserror)
    -   [hasOrder](#hasorder)
    -   [isIdle](#isidle)
    -   [isBeforeProcessing](#isbeforeprocessing)
    -   [isProcessing](#isprocessing)
    -   [isAfterProcessing](#isafterprocessing)
    -   [isComplete](#iscomplete)
    -   [isCalculating](#iscalculating)
    -   [prefersCollection](#preferscollection)
-   [Actions](#actions)
    -   [setPrefersCollection](#setpreferscollection)

## Overview

The Checkout Store provides a collection of selectors to access and manage data during the checkout process. These selectors enable developers to fetch key details such as customer information, order status, and other checkout-related data.

## Usage

To utilize this store you will import the `CHECKOUT_STORE_KEY` in any module referencing it. Assuming `@woocommerce/block-data` is registered as an external pointing to `wc.wcBlocksData` you can import the key via:

```js
const { CHECKOUT_STORE_KEY } = window.wc.wcBlocksData;
```

## Selectors

### getCustomerId

Returns the WordPress user ID of the customer whose order is currently processed by the Checkout block.

#### _Returns_ <!-- omit in toc -->

-   `number`: The WordPress user ID of the customer.

#### _Example_ <!-- omit in toc -->

```js
const store = select( CHECKOUT_STORE_KEY );
const customerId = store.getCustomerId();
```

### getOrderId

Returns the WooCommerce order ID of the order that is currently being processed by the Checkout block.

#### _Returns_ <!-- omit in toc -->

-   `number`: The WooCommerce order ID.

#### _Example_ <!-- omit in toc -->

```js
const store = select( CHECKOUT_STORE_KEY );
const orderId = store.getOrderId();
```

### getOrderNotes

Returns the order notes.

#### _Returns_ <!-- omit in toc -->

-   `string`: The order notes.

#### _Example_ <!-- omit in toc -->

```js
const store = select( CHECKOUT_STORE_KEY );
const orderNotes = store.getOrderNotes();
```

### getRedirectUrl

Returns the URL to redirect to after checkout is complete.

#### _Returns_ <!-- omit in toc -->

-   `string`: The URL to redirect to.

#### _Example_ <!-- omit in toc -->

```js
const store = select( CHECKOUT_STORE_KEY );
const redirectUrl = store.getRedirectUrl();
```

### getExtensionData

Returns the extra data registered by extensions.

#### _Returns_ <!-- omit in toc -->

-   `object`: The extra data registered by extensions.

```js
{
    [ extensionNamespace ]: {
        [ key ]: value,
    },
}
```

#### _Example_ <!-- omit in toc -->

```js
const store = select( CHECKOUT_STORE_KEY );
const extensionData = store.getExtensionData();
```

### getCheckoutStatus

Returns the current status of the checkout process.

#### _Returns_ <!-- omit in toc -->

-   `string`: The current status of the checkout process. Possible values are: `pristine`, `before-processing`, `processing`, `after-processing`, `complete`, `idle`.

#### _Example_ <!-- omit in toc -->

```js
const store = select( CHECKOUT_STORE_KEY );
const checkoutStatus = store.getCheckoutStatus();
```

### getShouldCreateAccount

Returns true if the shopper has opted to create an account with their order.

#### _Returns_ <!-- omit in toc -->

-   `boolean`: True if the shopper has opted to create an account with their order.

#### _Example_ <!-- omit in toc -->

```js
const store = select( CHECKOUT_STORE_KEY );
const shouldCreateAccount = store.getShouldCreateAccount();
```

### getUseShippingAsBilling

Returns true if the shopper has opted to use their shipping address as their billing address.

#### _Returns_ <!-- omit in toc -->

-   `boolean`: True if the shipping address should be used as the billing address.

#### _Example_ <!-- omit in toc -->

```js
const store = select( CHECKOUT_STORE_KEY );
const useShippingAsBilling = store.getUseShippingAsBilling();
```

### hasError

Returns true if an error occurred, and false otherwise.

#### _Returns_ <!-- omit in toc -->

-   `boolean`: True if an error occurred.

#### _Example_ <!-- omit in toc -->

```js
const store = select( CHECKOUT_STORE_KEY );
const hasError = store.hasError();
```

### hasOrder

Returns true if a draft order had been created, and false otherwise.

#### _Returns_ <!-- omit in toc -->

-   `boolean`: True if a draft order had been created.

#### _Example_ <!-- omit in toc -->

```js
const store = select( CHECKOUT_STORE_KEY );
const hasOrder = store.hasOrder();
```

### isIdle

When the checkout status is `IDLE` this flag is true. Checkout will be this status after any change to checkout state after the block is loaded. It will also be this status when retrying a purchase is possible after processing happens with an error.

#### _Returns_ <!-- omit in toc -->

-   `boolean`: True if the checkout has had some activity, but is currently waiting for user input.

#### _Example_ <!-- omit in toc -->

```js
const store = select( CHECKOUT_STORE_KEY );
const isIdle = store.isIdle();
```

### isBeforeProcessing

When the checkout status is `BEFORE_PROCESSING` this flag is true. Checkout will be this status when the user submits checkout for processing.

#### _Returns_ <!-- omit in toc -->

-   `boolean`: True if an order is about to be processed.

#### _Example_ <!-- omit in toc -->

```js
const store = select( CHECKOUT_STORE_KEY );
const isBeforeProcessing = store.isBeforeProcessing();
```

### isProcessing

When the checkout status is `PROCESSING` this flag is true. Checkout will be this status when all the observers on the event emitted with the `BEFORE_PROCESSING` status are completed without error. It is during this status that the block will be sending a request to the server on the checkout endpoint for processing the order.

#### _Returns_ <!-- omit in toc -->

-   `boolean`: True if the checkout is processing.

#### _Example_ <!-- omit in toc -->

```js
const store = select( CHECKOUT_STORE_KEY );
const isProcessing = store.isProcessing();
```

### isAfterProcessing

When the checkout status is `AFTER_PROCESSING` this flag is true. Checkout will have this status after the block receives the response from the server side processing request.

#### _Returns_ <!-- omit in toc -->

-   `boolean`: True if an order had just been processed.

#### _Example_ <!-- omit in toc -->

```js
const store = select( CHECKOUT_STORE_KEY );
const isAfterProcessing = store.isAfterProcessing();
```

### isComplete

When the checkout status is `COMPLETE` this flag is true. Checkout will have this status after all observers on the events emitted during the `AFTER_PROCESSING` status are completed successfully. When checkout is at this status, the shopper's browser will be redirected to the value of `redirectUrl` at that point (usually the `order-received` route).

#### _Returns_ <!-- omit in toc -->

-   `boolean`: True if the order is complete.

#### _Example_ <!-- omit in toc -->

```js
const store = select( CHECKOUT_STORE_KEY );
const isComplete = store.isComplete();
```

### isCalculating

This is true when the total is being re-calculated for the order. There are numerous things that might trigger a recalculation of the total: coupons being added or removed, shipping rates updated, shipping rate selected etc. This flag consolidates all activity that might be occurring (including requests to the server that potentially affect calculation of totals). So instead of having to check each of those individual states you can reliably just check if this boolean is true (calculating) or false (not calculating).

#### _Returns_ <!-- omit in toc -->

-   `boolean`: True if there is an in-flight request to update any values.

#### _Example_ <!-- omit in toc -->

```js
const store = select( CHECKOUT_STORE_KEY );
const isCalculating = store.isCalculating();
```


### prefersCollection

Returns true if the customer prefers to collect their order, and false otherwise.

#### _Returns_ <!-- omit in toc -->

-   _prefersCollection_ `boolean`: True if the shopper prefers to collect their order.

#### _Example_ <!-- omit in toc -->

```js
const store = select( CHECKOUT_STORE_KEY );
const prefersCollection = store.prefersCollection();
```

## Actions

### setPrefersCollection

Sets the `prefersCollection` flag to true or false.

#### _Parameters_ <!-- omit in toc -->

-   _prefersCollection_ `boolean`: True if the shopper prefers to collect their order.

#### _Example_ <!-- omit in toc -->

```js
const store = dispatch( CHECKOUT_STORE_KEY );
store.setPrefersCollection( true );
```

<!-- FEEDBACK -->

---

[We're hiring!](https://woocommerce.com/careers/) Come work with us!

🐞 Found a mistake, or have a suggestion? [Leave feedback about this document here.](https://github.com/woocommerce/woocommerce-blocks/issues/new?assignees=&labels=type%3A+documentation&template=--doc-feedback.md&title=Feedback%20on%20./docs/third-party-developers/extensibility/data-store/checkout.md)

<!-- /FEEDBACK -->
