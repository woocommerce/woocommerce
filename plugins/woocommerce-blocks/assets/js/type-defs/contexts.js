/**
 * @typedef {import('./cart').CartShippingOption} CartShippingOption
 * @typedef {import('./cart').CartShippingAddress} CartShippingAddress
 * @typedef {import('./cart').CartBillingAddress} CartBillingAddress
 * @typedef {import('./checkout').CheckoutDispatchActions} CheckoutDispatchActions
 */

/**
 * @typedef {Object} ShippingMethodDataContext
 *
 * @property {string}               shippingErrorStatus   The current error
 *                                                        status for shipping
 *                                                        rates if present.
 * @property {Function}             dispatchErrorStatus   A function for
 *                                                        dispatching a shipping
 *                                                        rate error status.
 * @property {ShippingErrorTypes}   shippingErrorTypes    The error type
 *                                                        constants for the
 *                                                        shipping rate error
 *                                                        status.
 * @property {CartShippingOption[]} shippingRates         An array of available
 *                                                        shipping rates.
 * @property {Function}             setShippingRates      Used to set the
 *                                                        available shipping
 *                                                        rates.
 * @property {boolean}              shippingRatesLoading  Whether or not the
 *                                                        shipping rates are
 *                                                        being loaded.
 * @property {string[]}             selectedRates         The ids of the rates
 *                                                        that are selected.
 * @property {Function}             setSelectedRates      A function for setting
 *                                                        the selected rates.
 * @property {CartShippingAddress}  shippingAddress       The current set
 *                                                        address for shipping.
 * @property {function()}           setShippingAddress    A function for setting
 *                                                        the shipping address.
 * @property {function()}           onShippingRateSuccess Used to register a
 *                                                        callback to be invoked
 *                                                        when shipping rates
 *                                                        are retrieved
 *                                                        successfully.
 * @property {function()}           onShippingRateSelectSuccess Used to register
 *                                                        a callback to be
 *                                                        invoked when shipping
 *                                                        rate is selected
 *                                                        successfully.
 * @property {function()}           onShippingRateSelectFail Used to register a
 *                                                        callback to be invoked
 *                                                        when shipping rate is
 *                                                        selected unsuccessfully
 * @property {function()}           onShippingRateFail    Used to register a
 *                                                        callback to be invoked
 *                                                        when there is an
 *                                                        error with retrieving
 *                                                        shipping rates.
 * @property {boolean}              needsShipping         True if the cart has
 *                                                        items requiring
 *                                                        shipping.
 */

/**
 * @typedef {Object} ShippingErrorTypes
 *
 * @property {string} NONE            No shipping error.
 * @property {string} INVALID_ADDRESS Error due to an invalid address for
 *                                    calculating shipping.
 * @property {string} UNKNOWN         When an unknown error has occurred in
 *                                    calculating/retrieving shipping rates.
 */

/**
 * @typedef {Object} PaymentMethodCurrentStatus
 *
 * This contains status information for the current active payment method in
 * the checkout.
 *
 * @property {boolean} isPristine   If true then the payment method state in
 *                                  checkout is pristine.
 * @property {boolean} isStarted    If true then the payment method has been
 *                                  initialized and has started.
 * @property {boolean} isProcessing If true then the payment method is
 *                                  processing payment.
 * @property {boolean} isFinished   If true then the payment method is in a
 *                                  finished state (which may mean it's status
 *                                  is either error, failed, or success)
 * @property {boolean} hasError     If true then the payment method is in an
 *                                  error state.
 * @property {boolean} hasFailed    If true then the payment method has failed
 *                                  (usually indicates a problem with the
 *                                  payment method used, not logic error)
 * @property {boolean} isSuccessful If true then the payment method has
 *                                  completed it's processing successfully.
 */

/**
 * @typedef {Object} PaymentStatusDispatchers
 *
 * @property {function()} started
 * @property {function()} processing
 * @property {function()} completed
 * @property {function(string)} error
 * @property {function(string, CartBillingAddress, Object)} failed
 * @property {function(CartBillingAddress, Object)} success
 */

/**
 * @typedef {function():PaymentStatusDispatchers|undefined} PaymentStatusDispatch
 */

/**
 * @typedef {Object} PaymentMethodDataContext
 *
 * @property {PaymentStatusDispatch}     setPaymentStatus        Sets the
 *                                                               payment status
 *                                                               for the payment
 *                                                               method.
 * @property {PaymentMethodCurrentStatus} currentStatus          The current
 *                                                               payment status.
 * @property {Object}                     paymentStatuses        An object of
 *                                                               payment status
 *                                                               constants.
 * @property {CartBillingAddress}         billingData            The current set
 *                                                               billing data.
 * @property {Object}                     paymentMethodData      Arbitrary data
 *                                                               to be passed
 *                                                               along for
 *                                                               processing by
 *                                                               the payment
 *                                                               method on the
 *                                                               server.
 * @property {string}                     errorMessage           An error
 *                                                               message provided
 *                                                               by the payment
 *                                                               method if there
 *                                                               is an error.
 * @property {string}                     activePaymentMethod    The active
 *                                                               payment method
 *                                                               slug.
 * @property {function()}                 setActivePaymentMethod A function for
 *                                                               setting the
 *                                                               active payment
 *                                                               method.
 * @property {function()}                 setBillingData         A function for
 *                                                               setting the
 *                                                               billing data.
 */

/**
 * @typedef {Object} CheckoutDataContext
 *
 * @property {string}                  submitLabel        The label to use for
 *                                                        the submit checkout
 *                                                        button.
 * @property {function()}              onSubmit           The callback to
 *                                                        register with the
 *                                                        checkout submit
 *                                                        button.
 * @property {boolean}                 isComplete         True when checkout is
 *                                                        complete and ready for
 *                                                        redirect.
 * @property {boolean}                 isIdle             True when the checkout
 *                                                        state has changed and
 *                                                        checkout has no
 *                                                        activity.
 * @property {boolean}                 isProcessing       True when checkout has
 *                                                        been submitted and is
 *                                                        being processed by the
 *                                                        server.
 * @property {boolean}                 isCalculating      True when something in
 *                                                        the checkout is
 *                                                        resulting in totals
 *                                                        being calculated.
 * @property {boolean}                 hasError           True when the checkout
 *                                                        is in an error state.
 *                                                        Whatever caused the
 *                                                        error
 *                                                        (validation/payment
 *                                                        method) will likely
 *                                                        have triggered a
 *                                                        notice.
 * @property {string}                  redirectUrl        This is the url that
 *                                                        checkout will redirect
 *                                                        to when it's ready.
 * @property {function()}              onCheckoutCompleteSuccess Used to register a
 *                                                        callback that will
 *                                                        fire when the checkout
 *                                                        is marked complete
 *                                                        successfully.
 * @property {function()}              onCheckoutCompleteError Used to register
 *                                                        a callback that will
 *                                                        fire when the checkout
 *                                                        is marked complete and
 *                                                        has an error.
 * @property {function()}              onCheckoutProcessing Used to register a
 *                                                        callback that will
 *                                                        fire when the checkout
 *                                                        has been submitted
 *                                                        before being sent off
 *                                                        to the server.
 * @property {CheckoutDispatchActions} dispatchActions    Various actions that
 *                                                        can be dispatched for
 *                                                        the checkout context
 *                                                        data.
 * @property {boolean}                 isEditor           Indicates whether in
 *                                                        the editor context
 *                                                        (true) or not (false).
 */

/**
 * @typedef {Object} EditorDataContext
 *
 * @property {boolean}                 isEditor           Indicates whether in
 *                                                        the editor context
 *                                                        (true) or not (false).
 * @property {number}                  currentPostId      The post ID being edited.
 */

/**
 * @typedef {Object} ValidationContext
 *
 * @property {function(string):Object}  getValidationError       Return validation error for the
 *                                                               given property.
 * @property {function(Object<Object>)} setValidationErrors      Receive an object of properties and
 *                                                               error messages as strings and adds
 *                                                               to the validation error state.
 * @property {function(string)}         clearValidationError     Clears a validation error for the
 *                                                               given property name.
 * @property {function()}               clearAllValidationErrors Clears all validation errors
 *                                                               currently in state.
 * @property {function(string)}         getValidationErrorId     Returns the css id for the
 *                                                               validation error using the given
 *                                                               inputId string.
 * @property {function(string)}         hideValidationError      Sets the hidden prop of a specific
 *                                                               error to true.
 * @property {function(string)}         showValidationError      Sets the hidden prop of a specific
 *                                                               error to false.
 * @property {function()}               showAllValidationErrors  Sets the hidden prop of all
 *                                                               errors to false.
 * @property {function():boolean}       hasValidationErrors      Returns true if there is at least
 *                                                               one error.
 */

export {};
