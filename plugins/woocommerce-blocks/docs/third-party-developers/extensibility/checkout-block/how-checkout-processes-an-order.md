# How The Checkout Block Processes an Order

This document will shed some light on the inner workings of the Checkout flow. More specifically, what happens after a user hits the “Place Order” button.

## Structure

The following areas are associated with processing the checkout for a user.

### The Payment Registry [:file_folder:](https://href.li/?https://github.com/woocommerce/woocommerce-blocks/blob/4af2c0916a936369be8a4f0044683b90b3af4f0d/assets/js/blocks-registry/payment-methods/registry.ts#L1)

The payment registry stores all the configuration information for each payment method. We can register a new payment method here with the `registerPaymentMethod` and `registerExpressPaymentMethod `functions, also available to other plugins.

### Data Stores

Data stores are used to keep track of data that is likely to change during a user’s session, such as the active payment method, whether the checkout has an error, etc. We split these data stores by areas of concern, so we have 2 data stores related to the checkout: `wc/store/checkout` [:file_folder:](https://href.li/?https://github.com/woocommerce/woocommerce-blocks/blob/4af2c0916a936369be8a4f0044683b90b3af4f0d/assets/js/data/checkout/index.ts#L1) and `wc/store/payment` [:file_folder:](https://href.li/?https://github.com/woocommerce/woocommerce-blocks/blob/4af2c0916a936369be8a4f0044683b90b3af4f0d/assets/js/data/payment-methods/index.ts#L1) . Data stores live in the `assets/js/data` folder.

### Contexts

Contexts are used to make data available to the Checkout block. Each of these provide data and functions related to a specific area of concern, via the use of a hook. For example, if we wanted to use the `onPaymentSetup` handler from the `PaymentEventsContext` context, we can do it like this:

```js
const { onPaymentSetup } = usePaymentEventsContext();
```

The other job of contexts is to run side effects for our Checkout block. What typically happens is that the `CheckoutEvents` and `PaymentEvents` will listen for changes in the checkout and payment data stores, and dispatch actions on those stores based on some logic.

For example, in the `CheckoutEvents` context, we dispatch the `emitValidateEvent` action when the checkout status is `before_processing`. There is a lot of similar logic that reacts to changes in status and other state data from these two stores.

The Checkout contexts are:

| Context                                                                                                                                                                                                                | Description                                          |
| ---------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------- | ---------------------------------------------------- |
| [CheckoutEvents](https://github.com/woocommerce/woocommerce-blocks/blob/4af2c0916a936369be8a4f0044683b90b3af4f0d/assets/js/base/context/providers/cart-checkout/checkout-events/index.tsx#L4)                          | Provides some checkout related event handlers        |
| [ PaymentEvents ](https://github.com/woocommerce/woocommerce-blocks/blob/4af2c0916a936369be8a4f0044683b90b3af4f0d/assets/js/base/context/providers/cart-checkout/payment-methods/payment-method-events-context.tsx#L3) | Provides event handlers related to payments          |
| [ CustomerData ](https://github.com/woocommerce/woocommerce-blocks/blob/4af2c0916a936369be8a4f0044683b90b3af4f0d/assets/js/base/context/providers/cart-checkout/customer/index.tsx#L1)                                 | Provides data related to the current customer        |
| [ ShippingData ](https://github.com/woocommerce/woocommerce-blocks/blob/4af2c0916a936369be8a4f0044683b90b3af4f0d/assets/js/base/context/providers/cart-checkout/shipping/index.js#L1)                                  | Provides data and actions related to shipping errors |

### The Checkout Processor (checkout-processor.js)

The checkout processor component subscribes to changes in the checkout or payment data stores, packages up some of this data and calls the StoreApi `/checkout` endpoint when the conditions are right.

## The Checkout Provider

The [checkout provider](https://github.com/woocommerce/woocommerce-blocks/blob/trunk/assets/js/base/context/providers/cart-checkout/checkout-provider.js), wraps all the contexts mentioned above around the `CheckoutProcessor` component.

---

## Checkout User Flow

Below is the complete checkout flow

### 1\. Click the "Place Order" button

The checkout process starts when a user clicks the button

### 2\. Checkout status is set to `before_processing` [:file_folder:](https://github.com/woocommerce/woocommerce-blocks/blob/4af2c0916a936369be8a4f0044683b90b3af4f0d/assets/js/base/context/providers/cart-checkout/checkout-events/index.tsx#L167)

As soon as the user clicks the "Place Order" button, we change the checkout status to _"before_processing"_. This is where we handle the validation of the checkout information.

### 3\. Emit the `checkout_validation_before_processing` event [:file_folder:](https://github.com/woocommerce/woocommerce-blocks/blob/4af2c0916a936369be8a4f0044683b90b3af4f0d/assets/js/base/context/providers/cart-checkout/checkout-events/index.tsx#L113)

This is where the WooCommerce Blocks plugin and other plugins can register event listeners for dealing with validation. The event listeners for this event will run and if there are errors, we set checkout status to `idle` and display the errors to the user.

If there are no errors, we move to step 4 below.

### 4\. Checkout status is set to `processing` [:file_folder:](https://github.com/woocommerce/woocommerce-blocks/blob/4af2c0916a936369be8a4f0044683b90b3af4f0d/assets/js/data/checkout/thunks.ts#L76)

The processing status is used by step 5 below to change the payment status

### 5\. Payment status is set to `processing` [:file_folder:](https://github.com/woocommerce/woocommerce-blocks/blob/4af2c0916a936369be8a4f0044683b90b3af4f0d/assets/js/base/context/providers/cart-checkout/payment-methods/payment-method-events-context.tsx#L94)

Once all the checkout processing is done and if there are no errors, the payment processing begins

### 6\. Emit the `payment_processing` event [:file_folder:](https://github.com/woocommerce/woocommerce-blocks/blob/4af2c0916a936369be8a4f0044683b90b3af4f0d/assets/js/data/payment-methods/thunks.ts#L42)

The `payment_processing` event is emitted. Otherplugins can register event listeners for this event and run their own code.

For example, the Stripe plugin checks the address and payment details here, and uses the stripe APIs to create a customer and payment reference within Stripe.

**Important note: The actual payment is not taken here**. **It acts like a pre-payment hook to run any payment plugin code before the actual payment is attempted.**

### 7\. Execute the `payment_processing` event listeners and set the payment and checkout states accordingly [:file_folder:](https://github.com/woocommerce/woocommerce-blocks/blob/4af2c0916a936369be8a4f0044683b90b3af4f0d/assets/js/data/payment-methods/thunks.ts#L54-L132)

If the registered event listeners return errors, we will display this to the user.

If the event listeners are considered successful, we sync up the address of the checkout with the payment address, store the `paymentMethodData` in the payment store, and set the payment status property `{ isProcessing: true }`.

### 8\. POST to `/wc/store/v1/checkout` [:file_folder:](https://github.com/woocommerce/woocommerce-blocks/blob/4af2c0916a936369be8a4f0044683b90b3af4f0d/assets/js/base/context/providers/cart-checkout/checkout-processor.js#L234)

The `/checkout` StoreApi endpoint is called if there are no payment errors. This will take the final customer addresses and chosen payment method, and any additional payment data, then attempts payment and returns the result.

**Important: payment is attempted through the StoreApi, NOT through the `payment_processing` event that is sent from the client**

### 9\. The `processCheckoutResponse` action is triggered on the checkout store [:file_folder:](https://github.com/woocommerce/woocommerce-blocks/blob/4af2c0916a936369be8a4f0044683b90b3af4f0d/assets/js/data/checkout/thunks.ts#L33)

Once the fetch to the StoreApi `/checkout` endpoint returns a response, we pass this to the `processCheckoutResponse` action on the `checkout` data store.

It will perform the following actions:

-   It sets the redirect url to redirect to once the order is complete
-   It stores the payment result in the `checkout` data store.
-   It changes the checkout status to `after_processing` (step 10)

### 10\. Checkout status is set to `after_processing` [:file_folder:](https://github.com/woocommerce/woocommerce-blocks/blob/4af2c0916a936369be8a4f0044683b90b3af4f0d/assets/js/data/checkout/thunks.ts#L42)

The `after_processing` status indicates that we've finished the main checkout processing step. In this step, we run cleanup actions and display any errors that have been triggered during the last step.

### 11\. Emit the `checkout_after_processing_with_success` event [:file_folder:](https://github.com/woocommerce/woocommerce-blocks/blob/4af2c0916a936369be8a4f0044683b90b3af4f0d/assets/js/data/checkout/thunks.ts#L118-L128)

If there are no errors, the `checkout_after_processing_with_success` event is triggered. This is where other plugins can run some code after the checkout process has been successful.

Any event listeners registered on the `checkout_after_processing_with_success` event will be executed. If there are no errors from the event listeners, `setComplete` action is called on the `checkout` data store to set the status to `complete` (step 13). An event listener can also return an error here, which will be displayed to the user.

### 12\. Emit the `checkout_after_processing_with_error` event [:file_folder:](https://github.com/woocommerce/woocommerce-blocks/blob/4af2c0916a936369be8a4f0044683b90b3af4f0d/assets/js/data/checkout/thunks.ts#L104-L116)

If there has been an error from step 5, the `checkout_after_processing_with_error` event will be emitted. Other plugins can register event listeners to run here to display an error to the user. The event listeners are processed and display any errors to the user.

### 13\. Set checkout status to `complete` [:file_folder:](https://github.com/woocommerce/woocommerce-blocks/blob/4af2c0916a936369be8a4f0044683b90b3af4f0d/assets/js/data/checkout/utils.ts#L146)

If there are no errors, the `status` property changes to `complete` in the checkout data store. This indicates that the checkout process is complete.

### 14\. Redirect [:file_folder:](https://github.com/woocommerce/woocommerce-blocks/blob/4af2c0916a936369be8a4f0044683b90b3af4f0d/assets/js/base/context/providers/cart-checkout/checkout-processor.js#L193-L197)

Once the checkout status is set to `complete`, if there is a `redirectUrl` in the checkout data store, then we redirect the user to the URL.

## Other notable things

### Checking payment methods

A payment method is registered with a [configuration object](https://github.com/woocommerce/woocommerce-blocks/blob/4af2c0916a936369be8a4f0044683b90b3af4f0d/assets/js/types/type-defs/payments.ts#L60-L83). It must include a function named `canMakePayment`. This function should return true if the payment method can be used to pay for the current cart. The current cart (items, addresses, shipping methods etc.) is passed to this function, and each payment method is responsible for reporting whether it can be used.

The `checkPaymentMethodsCanPay()` [function](https://github.com/woocommerce/woocommerce-blocks/blob/4af2c0916a936369be8a4f0044683b90b3af4f0d/assets/js/data/payment-methods/check-payment-methods.ts#L26) goes through all the registered payment methods, checks if they can pay, and if so, adds them to the `availablePaymentMethods` property on the payment data store.

The `checkPaymentMethodsCanPay()` [function](https://github.com/woocommerce/woocommerce-blocks/blob/4af2c0916a936369be8a4f0044683b90b3af4f0d/assets/js/data/payment-methods/check-payment-methods.ts#L26) must be called in a few places in order to validate the payment methods before they can be displayed to the user as viable options.

-   [Here](https://github.com/woocommerce/woocommerce-blocks/blob/4af2c0916a936369be8a4f0044683b90b3af4f0d/assets/js/data/cart/index.ts#L46-L57), once the cart loads, we want to be able to display express payment methods, so we need to validate them first.
-   [Here](https://github.com/woocommerce/woocommerce-blocks/blob/4af2c0916a936369be8a4f0044683b90b3af4f0d/assets/js/data/cart/index.ts#L42-L43), once the cart changes, we may want to enable/disable certain payment methods
-   [Here](https://github.com/woocommerce/woocommerce-blocks/blob/4af2c0916a936369be8a4f0044683b90b3af4f0d/assets/js/data/checkout/index.ts#L44-L49), once the checkout loads, we want to verify all registered payment methods
<!-- FEEDBACK -->

---

[We're hiring!](https://woocommerce.com/careers/) Come work with us!

🐞 Found a mistake, or have a suggestion? [Leave feedback about this document here.](https://github.com/woocommerce/woocommerce-blocks/issues/new?assignees=&labels=type%3A+documentation&template=--doc-feedback.md&title=Feedback%20on%20./docs/third-party-developers/extensibility/checkout-block/how-checkout-processes-an-order.md)

<!-- /FEEDBACK -->
