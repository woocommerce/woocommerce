# wc/store/checkout

## Table of Contents

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

## Selectors

### getCustomerId

Returns the WordPress user ID of the customer whose order is currently processed by the Checkout block.

#### _Returns_

-   `number`: WordPress user ID of the customer.

### getOrderId

Returns the WooCommerce order ID of the order that is currently being processed by the Checkout block.

#### _Returns_

-   `number`: WooCommerce order ID

### getOrderNotes

Returns the order notes.

#### _Returns_

-   `string`: Order notes.

### getRedirectUrl

Returns the URL to redirect to after checkout is complete.

#### _Returns_

-   `string`: URL to redirect to.

### getExtensionData

Returns the extra data registered by extensions.

#### _Returns_

-   `Object`: Extra data registered by extensions.

```js
{
    [ extensionNamespace ]: {
        [ key ]: value,
    },
}
```

### getCheckoutStatus

Returns the current status of the checkout process.

#### _Returns_

-   `string`: Current status of the checkout process. Possible values are: `pristine`, `before-processing`, `processing`, `after-processing`, `complete`, `idle`.

### getShouldCreateAccount

Returns true if the shopper has opted to create an account with their order.

#### _Returns_

-   `boolean`: True if the shopper has opted to create an account with their order.

### getUseShippingAsBilling

Returns true if the shopper has opted to use their shipping address as their billing address.

#### _Returns_

-   `boolean`: True if the shipping address should be used as the billing address.

### hasError

Returns true if an error occurred, and false otherwise.

#### _Returns_

-   `boolean`: Whether an error occurred.

### hasOrder

Returns true if a draft order had been created, and false otherwise.

#### _Returns_

-   `boolean`: Whether a draft order had been created.

### isIdle

When the checkout status is `IDLE` this flag is true. Checkout will be this status after any change to checkout state after the block is loaded. It will also be this status when retrying a purchase is possible after processing happens with an error.

#### _Returns_

-   `boolean`: Whether the checkout has had some activity, but is currently waiting for user input.

### isBeforeProcessing

When the checkout status is `BEFORE_PROCESSING` this flag is true. Checkout will be this status when the user submits checkout for processing.

#### _Returns_

-   `boolean`: Whether an order is about to be processed.

### isProcessing

When the checkout status is `PROCESSING` this flag is true. Checkout will be this status when all the observers on the event emitted with the `BEFORE_PROCESSING` status are completed without error. It is during this status that the block will be sending a request to the server on the checkout endpoint for processing the order.

#### _Returns_

-   `boolean`: Whether the checkout is processing.

### isAfterProcessing

When the checkout status is `AFTER_PROCESSING` this flag is true. Checkout will have this status after the the block receives the response from the server side processing request.

#### _Returns_

-   `boolean`: Whether an order had just been processed.

### isComplete

When the checkout status is `COMPLETE` this flag is true. Checkout will have this status after all observers on the events emitted during the `AFTER_PROCESSING` status are completed successfully. When checkout is at this status, the shopper's browser will be redirected to the value of `redirectUrl` at that point (usually the `order-received` route).

#### _Returns_

-   `boolean`: Whether the order is complete.

### isCalculating

This is true when the total is being re-calculated for the order. There are numerous things that might trigger a recalculation of the total: coupons being added or removed, shipping rates updated, shipping rate selected etc. This flag consolidates all activity that might be occurring (including requests to the server that potentially affect calculation of totals). So instead of having to check each of those individual states you can reliably just check if this boolean is true (calculating) or false (not calculating).

#### _Returns_

-   `boolean`: Whether there is an in-flight request to update any values.

<!-- FEEDBACK -->

---

[We're hiring!](https://woocommerce.com/careers/) Come work with us!

🐞 Found a mistake, or have a suggestion? [Leave feedback about this document here.](https://github.com/woocommerce/woocommerce-blocks/issues/new?assignees=&labels=type%3A+documentation&template=--doc-feedback.md&title=Feedback%20on%20./docs/third-party-developers/extensibility/data-store/checkout.md)

<!-- /FEEDBACK -->

