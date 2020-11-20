/**
 * @typedef {import('@woocommerce/type-defs/cart').CartTotalItem} CartTotalItem
 * @typedef {import('@woocommerce/type-defs/cart').CartShippingOption} CartShippingOption
 * @typedef {import('@woocommerce/type-defs/shipping').ShippingAddress} ShippingAddress
 * @typedef {import('@woocommerce/type-defs/billing').BillingData} BillingData
 * @typedef {import('@woocommerce/type-defs/contexts').PaymentMethodCurrentStatus} PaymentMethodCurrentStatus
 * @typedef {import('@woocommerce/type-defs/contexts').PaymentStatusDispatch} PaymentStatusDispatch
 * @typedef {import('@woocommerce/type-defs/contexts').ShippingErrorStatus} ShippingErrorStatus
 * @typedef {import('@woocommerce/type-defs/contexts').ShippingErrorTypes} ShippingErrorTypes
 * @typedef {import('@woocommerce/type-defs/settings').WooCommerceSiteCurrency} SiteCurrency
 * @typedef {import('@woocommerce/type-defs/hooks').EmitResponseTypes} EmitResponseTypes
 * @typedef {import('@woocommerce/type-defs/hooks').NoticeContexts} NoticeContexts
 */

/**
 * Payment Event Status Action Creators
 *
 * @typedef {Object} PaymentStatusActions
 *
 * @property {Function} started    Set started status.
 * @property {Function} processing Set processing status.
 * @property {Function} completed  Set completed status.
 * @property {Function} error      Set error status.
 * @property {Function} failed     Set failed status.
 * @property {Function} success    Set success status.
 */

/**
 * @typedef {Object} PaymentMethodShipping
 *
 * @property {boolean}              required      If shipping is required this
 *                                                will be true.
 * @property {CartShippingOption[]} options       Available shipping options.
 * @property {Function}             selectOptions Used to set the selected
 *                                                shipping options.
 * @property {Function}             updateAddress Used to update the shipping
 *                                                address.
 * @property {Function}             setStatus     Used to set the current
 *                                                shipping options status.
 * @property {Function}             selectStatus  Returns helpers for
 *                                                determining the current
 *                                                shipping options status.
 * @property {string}               status        What the current shipping
 *                                                options status is.
 */

/**
 * @typedef {Object} PaymentMethodEvents
 *
 * @property {boolean}  isCalculating      If true, means the cart/checkout is
 *                                         currently calculating totals.
 * @property {boolean}  isCheckoutComplete If true, means the checkout process
 *                                         is complete (useful if the payment
 *                                         method has something to do after
 *                                         checkout is complete before setting
 *                                         payment status to complete. Redirect
 *                                         doesn't happen until both checkout
 *                                         and payment status is complete)
 * @property {PaymentStatusActions} dispatchStatus     Used to dispatch a payment event.
 * @property {Function} selectStatus       Returns helpers for determining
 *                                         current payment event status.
 * @property {string}   status             What the current payment event status
 *                                         is.
 */

/**
 * @typedef CheckoutStatusProps
 *
 * @property {boolean} isCalculating If true then totals are being calculated in
 *                                   the checkout.
 * @property {boolean} isComplete    If true then the checkout has completed
 *                                   it's processing.
 * @property {boolean} isIdle        If true then the checkout is idle (no
 *                                   activity happening).
 * @property {boolean} isProcessing  If true then checkout is processing
 *                                   (finalizing) the order with the server.
 */

/**
 * @typedef ShippingStatusProps
 *
 * @property {ShippingErrorStatus} shippingErrorStatus Current error status for
 *                                                     shipping.
 * @property {ShippingErrorTypes}  shippingErrorTypes  An object containing all
 *                                                     the possible types for
 *                                                     shipping error status.
 */

/**
 * @typedef ShippingDataProps
 *
 * @property {CartShippingOption[]} shippingRates        All the available
 *                                                       shipping rates.
 * @property {boolean}              shippingRatesLoading Whether the rates are
 *                                                       loading or not.
 * @property {string[]}             selectedRates        An array of selected
 *                                                       rates (rate ids).
 * @property {Function}             setSelectedRates     A function for setting
 *                                                       selected rates
 *                                                       (receives id)
 * @property {boolean}              isSelectingRate      True when rates are
 *                                                       being selected.
 * @property {ShippingAddress}      shippingAddress      The current set
 *                                                       shipping address.
 * @property {Function}             setShippingAddress   A function for setting
 *                                                       the shipping address.
 * @property {boolean}              needsShipping        True if cart requires
 *                                                       shipping.
 */

/**
 * @typedef PreparedCartTotalItem
 *
 * @property {string} label  The label for the total item.
 * @property {number} value  The value for the total item.
 */

/**
 * @typedef BillingDataProps
 *
 * @property {BillingData}             billingData               The address used for billing.
 * @property {PreparedCartTotalItem}   cartTotal                 The total item for the cart.
 * @property {SiteCurrency}            currency                  Currency object.
 * @property {PreparedCartTotalItem[]} cartTotalItems            The various subtotal amounts.
 * @property {boolean}                 displayPricesIncludingTax True means that the site is
 *                                                               configured to display prices
 *                                                               including tax.
 * @property {string[]}                appliedCoupons            All the coupons that were applied.
 * @property {number}                  customerId                The customer Id the order belongs to.
 */

/**
 * @typedef EventRegistrationProps
 *
 * @property {function(function())} onCheckoutAfterProcessingWithSuccess Used to subscribe callbacks
 *                                                              firing when checkout has completed
 *                                                              processing successfully.
 * @property {function(function())} onCheckoutAfterProcessingWithError Used to subscribe callbacks
 *                                                              firing when checkout has completed
 *                                                              processing with an error.
 * @property {function(function())} onCheckoutBeforeProcessing  Used to subscribe callbacks that
 *                                                              will fire when checkout begins
 *                                                              processing (as a part of the
 *                                                              processing process).
 * @property {function(function())} onShippingRateSuccess       Used to subscribe callbacks that
 *                                                              will fire when shipping rates for a
 *                                                              given address have been received
 *                                                              successfully.
 * @property {function(function())} onShippingRateFail          Used to subscribe callbacks that
 *                                                              will fire when retrieving shipping
 *                                                              rates failed.
 * @property {function(function())} onShippingRateSelectSuccess Used to subscribe callbacks that
 *                                                              will fire after selecting a
 *                                                              shipping rate successfully.
 * @property {function(function())} onShippingRateSelectFail    Used to subscribe callbacks that
 *                                                              will fire after selecting a shipping
 *                                                              rate unsuccessfully.
 * @property {function(function())} onPaymentProcessing         Event registration callback for
 *                                                              registering observers for the
 *                                                              payment processing event.
 */

/**
 * @typedef ComponentProps
 *
 * @property {function(Object):Object} ValidationInputError  A container for holding validation
 *                                                           errors
 * @property {function(Object):Object} PaymentMethodIcons    A component used for displaying payment
 *                                                           method icons.
 * @property {function(Object):Object} PaymentMethodLabel    A component used for displaying payment
 *                                                           method labels, including an icon.
 */

/**
 * @typedef EmitResponseProps
 *
 * @property {EmitResponseTypes} responseTypes  Response types that can be returned from emitter
 *                                              observers.
 * @property {NoticeContexts}    noticeContexts Available contexts that can be returned as the value
 *                                              for the messageContext property on the object
 *                                              returned from an emitter observer.
 */

/**
 * Registered payment method props
 *
 * @typedef {Object} RegisteredPaymentMethodProps
 *
 * @property {CheckoutStatusProps}        checkoutStatus           The current checkout status exposed
 *                                                                 as various boolean state.
 * @property {PaymentMethodCurrentStatus} paymentStatus            Various payment status helpers.
 * @property {ShippingStatusProps}        shippingStatus           Various shipping status helpers.
 * @property {ShippingDataProps}          shippingData             Various data related to shipping.
 * @property {BillingDataProps}           billing                  Various billing data items.
 * @property {EventRegistrationProps}     eventRegistration        Various event registration helpers
 *                                                                 for subscribing callbacks for
 *                                                                 events.
 * @property {EmitResponseProps}          emitResponse             Utilities for usage in event
 *                                                                 observer response objects.
 * @property {Function}                   [onSubmit]               Used to trigger checkout
 *                                                                 processing.
 * @property {string}                     activePaymentMethod      Indicates what the active payment
 *                                                                 method is.
 * @property {ComponentProps}             components               Components exposed to payment
 *                                                                 methods for use.
 * @property {function(string)}           [setExpressPaymentError] For setting an error (error
 *                                                                 message string) for express
 *                                                                 payment methods. Does not change
 *                                                                 payment status.
 * @property {function()}                 [onClick]                Provided to express payment
 *                                                                 methods that should be triggered
 *                                                                 when the payment method button
 *                                                                 is clicked (which will signal to
 *                                                                 checkout the payment method has
 *                                                                 taken over payment processing)
 * @property {function()}                 [onClose]                Provided to express payment
 *                                                                 methods that should be triggered
 *                                                                 when the express payment method
 *                                                                 modal closes and control is
 *                                                                 returned to checkout.
 */

export {};
