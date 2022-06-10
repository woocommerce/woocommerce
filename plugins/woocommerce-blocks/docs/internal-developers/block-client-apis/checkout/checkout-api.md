# Checkout API interface <!-- omit in toc -->

## Table of contents <!-- omit in toc -->

**Note on migration:** We are in the process of moving much of the data from contexts into data stores, so this portion of the docs may change often as we do this. We will endavour to keep it up to date while the work is carried out

-   [Checkout Block API overview](#checkout-block-api-overview)
    -   [Data Stores](#data-stores)
        -   [Checkout Data Store](#checkout-data-store)
    -   [Contexts](#contexts)
        -   [Notices Context](#notices-context)
        -   [Customer Data Context](#customer-data-context)
        -   [Billing Data Context](#billing-data-context)
        -   [Shipping Method Data context](#shipping-method-data-context)
        -   [Payment Method Data Context](#payment-method-data-context)
        -   [Checkout Context](#checkout-context)
    -   [Hooks](#hooks)
        -   [`usePaymentMethodInterface`](#usepaymentmethodinterface)

This document gives an overview of some of the major architectural components/APIs for the checkout block. If you haven't already, you may also want to read about the [Checkout Flow and Events](../../extensibility/checkout-flow-and-events.md).

### Data Stores

We are transitioning much of what is now available in Contexts, to `@wordpress/data` stores.

#### Checkout Data Store

This is responsible for holding all the data required for the checkout process.

The following data is available:

-   `status`: The status of the current checkout. Possible values are `pristine`, `idle`, `processing`, `complete`, `before_processing` or `after_processing`
-   `hasError`: This is true when anything in the checkout has created an error condition state. This might be validation errors, request errors, coupon application errors, payment processing errors etc.
-   `redirectUrl`: The current set url that the checkout will redirect to when it is complete.
-   `orderId`: The order id for the order attached to the current checkout.
-   `customerId`: The ID of the customer if the customer has an account, or `0` for guests.
-   `calculatingCount`: If any of the totals, taxes, shipping, etc need to be calculated, the count will be increased here.
-   `processingResponse`:The result of the payment processing
-   `useShippingAsBilling`: Should the billing form be hidden and inherit the shipping address?
-   `shouldCreateAccount`: Should a user account be created with this order?
-   `extensionData`: This is used by plugins that extend Cart & Checkout to pass custom data to the Store API on checkout processing
-   `orderNotes`: Order notes introduced by the user in the checkout form.

##### Selectors

Data can be accessed through the following selectors:

-   `getCheckoutState()`: Returns all the data above.
-   `isComplete()`: True when checkout has finished processing and the subscribed checkout processing callbacks have all been invoked along with a successful processing of the checkout by the server.
-   `isIdle()`: When the checkout status is `IDLE` this flag is true. Checkout will be this status after any change to checkout state after the block is loaded. It will also be this status when retrying a purchase is possible after processing happens with an error.
-   `isBeforeProcessing()`: When the checkout status is `BEFORE_PROCESSING` this flag is true. Checkout will be this status when the user submits checkout for processing.
-   `isProcessing()`: When the checkout status is `PROCESSING` this flag is true. Checkout will be this status when all the observers on the event emitted with the `BEFORE_PROCESSING` status are completed without error. It is during this status that the block will be sending a request to the server on the checkout endpoint for processing the order.
-   `isAfterProcessing()`: When the checkout status is `AFTER_PROCESSING` this flag is true. Checkout will have this status after the the block receives the response from the server side processing request.
-   `isComplete()`: When the checkout status is `COMPLETE` this flag is true. Checkout will have this status after all observers on the events emitted during the `AFTER_PROCESSING` status are completed successfully. When checkout is at this status, the shopper's browser will be redirected to the value of `redirectUrl` at that point (usually the `order-received` route).
-   `isCalculating()`: This is true when the total is being re-calculated for the order. There are numerous things that might trigger a recalculation of the total: coupons being added or removed, shipping rates updated, shipping rate selected etc. This flag consolidates all activity that might be occurring (including requests to the server that potentially affect calculation of totals). So instead of having to check each of those individual states you can reliably just check if this boolean is true (calculating) or false (not calculating).
-   `hasOrder()`: This is true when orderId is truthy.
-   `hasError()`: This is true when the checkout has an error.
-   `getOrderNotes()`: Returns the order notes.
-   `getCustomerId()`: Returns the customer ID.

##### Actions

The following actions can be dispatched from the Checkout data store:

-   `setPristine()`: Set `state.status` to `pristine`
-   `setIdle()`: Set `state.status` to `idle`
-   `setComplete()`: Set `state.status` to `complete`
-   `setProcessing()`: Set `state.status` to `processing`
-   `setProcessingResponse( response: PaymentResult )`: Set `state.processingResponse` to `response`
-   `setBeforeProcessing()`: Set `state.status` to `before_processing`
-   `setAfterProcessing()`: Set `state.status` to `after_processing`
-   `processCheckoutResponse( response: CheckoutResponse )`: This is a thunk that will extract the paymentResult from the CheckoutResponse, and dispatch 3 actions: `setRedirectUrl`, `setProcessingResponse` and `setAfterProcessing`.
-   `setRedirectUrl( url: string )`: Set `state.redirectUrl` to `url`
-   `setHasError( trueOrFalse: bool )`: Set `state.hasError` to `trueOrFalse`
-   `incrementCalculating()`: Increment `state.calculatingCount`
-   `decrementCalculating()`: Decrement `state.calculatingCount`
-   `setCustomerId( id: number )`: Set `state.customerId` to `id`
-   `setOrderId( id: number )`: Set `state.orderId` to `id`
-   `setUseShippingAsBilling( useShippingAsBilling: boolean )`: Set `state.useShippingAsBilling` to `useShippingAsBilling`
-   `setShouldCreateAccount( shouldCreateAccount: boolean )`: Set `state.shouldCreateAccount` to `shouldCreateAccount`
-   `setOrderNotes( orderNotes: string )`: Set `state.orderNotes` to `orderNotes`
-   `setExtensionData( extensionData: Record< string, Record< string, unknown > > )`: Set `state.extensionData` to `extensionData`

### Contexts

Much of the data and api interface for components in the Checkout Block are constructed and exposed via [usage of `React.Context`](https://reactjs.org/docs/context.html). In some cases the context maintains the "tree" state within the context itself (via `useReducer`) and in others it interacts with a global `wp.data` store (for data that communicates with the server).

You can find type definitions (`typedef`) for contexts in [this file](../../../../assets/js/types/type-defs/contexts.js).

### Notices Context

This system essentially does three things:

-   Contains and maintains a data store for keeping track of notices (using `@wordpress/notices` internally).
-   Optionally automatically outputs a notice container and outputs notices to that container for display when they are created.
-   Exposes (via the `useStoreNotices`) hook an api for getting and setting notices.

This system is exposed on components by wrapping them in a `<StoreNoticesProvider>` component. Currently the checkout block implements `<StoreNoticesProvider>` in three areas of the checkout block:

-   The entire block (identified by the `wc/checkout` context value).
-   The express payments areas (using the `wc/express-payment-area` context value)
-   The payment methods area (using the `wc/payment-area` context value).

### Customer Data Context

The customer data context exposes the api interfaces for the following things via the `useCustomerDataContext` hook:

-   `billingData`: The currently set billing data.
-   `setBillingData`: A state updated for updating the billing data state with new billing data.
-   `shippingAddress`: The current set shipping address.
-   `setShippingAddress`: A function for setting the shipping address. This will trigger shipping rates updates.

### Shipping Method Data context

The shipping method data context exposes the api interfaces for the following things (typedef `ShippingMethodDataContext`) via the `useShippingMethodData` hook:

-   `shippingErrorStatus`: The current error status for the context.
-   `dispatchErrorStatus`: A function for dispatching a shipping error status. Used in combination with...
-   `shippingErrorTypes`: An object with the various error statuses that can be dispatched (`NONE`, `INVALID_ADDRESS`, `UNKNOWN`)
-   `onShippingRateSuccess`: This is a function for registering a callback to be invoked when shipping rates are retrieved successfully. Callbacks will receive the new rates as an argument.
-   `onShippingRateFail`: This is a function for registering a callback to be invoked when shipping rates fail to be retrieved. Callbacks will receive the error status as an argument.
-   `onShippingRateSelectSuccess`: This is a function for registering a callback to be invoked when shipping rate selection is successful.
-   `onShippingRateSelectFail`: This is a function for registering a callback to be invoked when shipping rates selection is unsuccessful.

### Payment Method Data Context

The payment method data context exposes the api interfaces for the following things (typedef `PaymentMethodDataContext`) via the `usePaymentMethodData` hook.

-   `setPaymentStatus`: used to set the current active payment method flow status. Calling it returns an object of dispatcher methods with the following methods: `started()`, `processing()`, `completed()`, `error( errorMessage)`, `failed( errorMessage, paymentMethodData, billingData )`, `success( paymentMethodData, billingData, shippingData )`. Note `paymentMethodData` is attached to the order processing request so payment methods could hook in server side to do any processing of the payment as a part of checkout processing (and receive extra information from the payment method client side).
-   `currentStatus`: This is an object that returns helper methods for determining the current status of the active payment method flow. Includes: `isPristine()`, `isStarted()`, `isProcessing()`, `isFinished()`, `hasError()`, `hasFailed()`, `isSuccessful()`.
-   `paymentStatuses`: This is an object of payment method status constants.
-   `paymentMethodData`: This is the current extra data tracked in the context state. This is arbitrary data provided by the payment method extension after it has completed payment for checkout to include in the processing request. Typically this would contain things like payment `token` or `payment_method` name.
-   `errorMessage`: This exposes the current set error message provided by the active payment method (if present).
-   `activePaymentMethod`: This is the current active payment method in the checkout.
-   `setActivePaymentMethod`: This is used to set the active payment method and any related payment method data.
-   `onPaymentProcessing`: This is an event subscriber that can be used to subscribe observers to be called when the status for the context is `PROCESSING`.
-   `customerPaymentMethods`: This is an object containing any saved payment method information for the current logged in user. It is provided via the server and used to generate the ui for the shopper to select a saved payment method from a previous purchase.
-   `paymentMethods`: This is an object containing all the _initialized_ registered payment methods.
-   `expressPaymentMethods`: This is an object containing all the _initialized_ express payment methods.
-   `paymentMethodsInitialized`: This is `true` when all registered payment methods have been initialized.
-   `expressPaymentMethodsInitialized`: This is `true` when all registered express payment methods have been initialized.
-   `setExpressPaymentError`: This is exposed to express payment methods to enable them to set a specific error notice. This is needed because express payment methods might need to show/trigger an error outside any of the checkout block events.

### Checkout Context

This context is the main one. Internally via the `<CheckoutProvider>` it handles wrapping children in `<ShippingMethodDataProvider>`, `<CustomerDataProvider>` and `<PaymentMethodDataProvider>`. So the checkout components just need to be wrapped by `<CheckoutProvider>`.

The provider receives the following props:

-   `redirectUrl`: A string, this is used to indicate where the checkout redirects to when it is complete. This is optional and can be used to set a default url to redirect to.

Via `useCheckoutContext`, the following are exposed:

-   `onSubmit`: This is a callback to be invoked either by submitting the checkout button, or by express payment methods to start checkout processing after they have finished their initialization process when their button has been clicked.
-   `isComplete`: True when checkout has finished processing and the subscribed checkout processing callbacks have all been invoked along with a successful processing of the checkout by the server.
-   `isIdle`: When the checkout status is `IDLE` this flag is true. Checkout will be this status after any change to checkout state after the block is loaded. It will also be this status when retrying a purchase is possible after processing happens with an error.
-   `isBeforeProcessing`: When the checkout status is `BEFORE_PROCESSING` this flag is true. Checkout will be this status when the user submits checkout for processing.
-   `isProcessing`: When the checkout status is `PROCESSING` this flag is true. Checkout will be this status when all the observers on the event emitted with the `BEFORE_PROCESSING` status are completed without error. It is during this status that the block will be sending a request to the server on the checkout endpoint for processing the order.
-   `isAfterProcessing`: When the checkout status is `AFTER_PROCESSING` this flag is true. Checkout will have this status after the the block receives the response from the server side processing request.
-   `isComplete`: When the checkout status is `COMPLETE` this flag is true. Checkout will have this status after all observers on the events emitted during the `AFTER_PROCESSING` status are completed successfully. When checkout is at this status, the shopper's browser will be redirected to the value of `redirectUrl` at that point (usually the `order-received` route).
-   `isCalculating`: This is true when the total is being re-calculated for the order. There are numerous things that might trigger a recalculation of the total: coupons being added or removed, shipping rates updated, shipping rate selected etc. This flag consolidates all activity that might be occurring (including requests to the server that potentially affect calculation of totals). So instead of having to check each of those individual states you can reliably just check if this boolean is true (calculating) or false (not calculating).
-   `hasError`: This is true when anything in the checkout has created an error condition state. This might be validation errors, request errors, coupon application errors, payment processing errors etc.
-   `redirectUrl`: The current set url that the checkout will redirect to when it is complete.
-   `onCheckoutValidationBeforeProcessing`: Used to register observers that will be invoked at validation time, after the checkout has been submitted but before the processing request is sent to the server.
-   `onCheckoutAfterProcessingWithSuccess`: Used to register observers that will be invoked after checkout has been processed by the server successfully.
-   `onCheckoutAfterProcessingWithError`: Used to register observers that will be invoked after checkout has been processed by the server and there was an error.
-   `dispatchActions`: This is an object with various functions for dispatching status in the checkout. It is not exposed to extensions but is for internal use only.
-   `orderId`: The order id for the order attached to the current checkout.
-   `isCart`: This is true if the cart is being viewed. Note: usage of `CheckoutProvider` will automatically set this to false. There is also a `CartProvider` that wraps children in the `ShippingDataProvider` and exposes the same api as checkout context. The `CartProvider` automatically sets `isCart` to true. This allows components that implement `useCheckoutContext` to use the same api in either the cart or checkout context but still have specific behaviour to whether `isCart` is true or not.
-   `hasOrder`: This is true when orderId is truthy.
-   `customerId`: The ID of the customer if the customer has an account, or `0` for guests.

## Hooks

These docs currently don't go into detail for all the hooks as that is fairly straightforward from existing implementations. However, one important extension interface hook will be highlighted here, `usePaymentMethodInterface`.

### `usePaymentMethodInterface`

This hook is used to expose all the interfaces for the registered payment method components to utilize. Essentially the result from this hook is fed in as props on the registered payment components when they are setup by checkout. You can use the typedef ([`PaymentMethodInterface`](../../../../assets/js/types/type-defs/payment-method-interface.ts)) to see what is fed to payment methods as props from this hook.

_Why don't payment methods just implement this hook_?

The contract is established through props fed to the payment method components via props. This allows us to avoid having to expose the hook publicly and experiment with how the props are retrieved and exposed in the future.

<!-- FEEDBACK -->

---

[We're hiring!](https://woocommerce.com/careers/) Come work with us!

🐞 Found a mistake, or have a suggestion? [Leave feedback about this document here.](https://github.com/woocommerce/woocommerce-blocks/issues/new?assignees=&labels=type%3A+documentation&template=--doc-feedback.md&title=Feedback%20on%20./docs/internal-developers/block-client-apis/checkout/checkout-api.md)

<!-- /FEEDBACK -->
