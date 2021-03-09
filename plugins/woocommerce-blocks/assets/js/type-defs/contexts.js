/**
 * @typedef {import('./billing').BillingData} BillingData
 * @typedef {import('./cart').CartShippingOption} CartShippingOption
 * @typedef {import('./shipping').ShippingAddress} CartShippingAddress
 * @typedef {import('./cart').CartData} CartData
 * @typedef {import('./checkout').CheckoutDispatchActions} CheckoutDispatchActions
 * @typedef {import('./add-to-cart-form').AddToCartFormDispatchActions} AddToCartFormDispatchActions
 * @typedef {import('./add-to-cart-form').AddToCartFormEventRegistration} AddToCartFormEventRegistration
 */

/**
 * @typedef {Object} CustomerDataContext
 *
 * @property {BillingData}          billingData        The current billing data, including address and email.
 * @property {CartShippingAddress}  shippingAddress    The current set address for shipping.
 * @property {Function}             setBillingData     A function for setting billing data.
 * @property {Function}             setShippingAddress A function for setting shipping address.
 */

/**
 * @typedef {Object} ShippingDataContext
 *
 * @property {ShippingErrorStatus}  shippingErrorStatus         The current shipping error status.
 * @property {Function}             dispatchErrorStatus         A function for dispatching a shipping rate error status.
 * @property {ShippingErrorTypes}   shippingErrorTypes          The error type constants for the shipping rate error
 *                                                              status.
 * @property {CartShippingOption[]} shippingRates               An array of available shipping rates.
 * @property {boolean}              shippingRatesLoading        Whether or not the shipping rates are being loaded.
 * @property {string[]}             selectedRates               The ids of the rates that are selected.
 * @property {Function}             setSelectedRates            Function for setting the selected rates.
 * @property {boolean}              isSelectingRate             True when rate is being selected.
 * @property {CartShippingAddress}  shippingAddress             The current set address for shipping.
 * @property {Function}             setShippingAddress          Function for setting the shipping address.
 * @property {function()}           onShippingRateSuccess       Used to register a callback to be invoked when shipping
 *                                                              rates are retrieved.
 * @property {function()}           onShippingRateSelectSuccess Used to register a callback to be invoked when shipping
 *                                                              rate is selected.
 * @property {function()}           onShippingRateSelectFail    Used to register a callback to be invoked when shipping
 *                                                              rate is selected unsuccessfully
 * @property {function()}           onShippingRateFail          Used to register a callback to be invoked when there is
 *                                                              an error with retrieving shipping rates.
 * @property {boolean}              needsShipping               True if the cart has items requiring shipping.
 * @property {boolean}              hasCalculatedShipping       True if the cart has calculated shipping costs.
 */

/**
 * @typedef {Object} ShippingErrorStatus
 *
 * @property {boolean} isPristine        Whether the status is pristine.
 * @property {boolean} isValid           Whether the status is valid.
 * @property {boolean} hasInvalidAddress Whether the address is invalid.
 * @property {boolean} hasError          Whether an error has happened.
 */

/**
 * @typedef {Object} ShippingErrorTypes
 *
 * @property {string} NONE            No shipping error.
 * @property {string} INVALID_ADDRESS Error due to an invalid address for calculating shipping.
 * @property {string} UNKNOWN         When an unknown error has occurred in calculating/retrieving shipping rates.
 */

/**
 * A saved customer payment method object (if exists)
 *
 * @typedef {Object} CustomerPaymentMethod
 *
 * @property {Object}  method     The payment method object (varies on what it might contain)
 * @property {string}  expires    Short form of expiry for payment method.
 * @property {boolean} is_default Whether it is the default payment method of the customer or not.
 * @property {number}  tokenId    The id of the saved payment method.
 * @property {Object}  actions    Varies, actions that can be done to interact with the payment method.
 */

/**
 * @typedef {Object} ShippingDataResponse
 *
 * @property {CartShippingAddress} address The address selected for shipping.
 */

/**
 * @typedef {Object} PaymentStatusDispatchers
 *
 * @property {function()}                        started    Sets started status.
 * @property {function()}                        processing Sets processing status.
 * @property {function()}                        completed  Sets complete status.
 * @property {function(string)}                  error      Sets error status.
 * @property {function(string, Object, Object=)} failed     Sets failed status.
 * @property {function(Object=,Object=,Object=)} success    Sets success status.
 */

/**
 * @typedef {function():PaymentStatusDispatchers} PaymentStatusDispatch
 */

/**
 * @typedef {Object} CheckoutDataContext
 *
 * @property {function()}                   onSubmit                             The callback to register with the
 *                                                                               checkout submit button.
 * @property {boolean}                      isComplete                           True when checkout is complete and
 *                                                                               ready for redirect.
 * @property {boolean}                      isBeforeProcessing                   True during any observers executing
 *                                                                               logic before checkout processing
 *                                                                               (eg. validation).
 * @property {boolean}                      isAfterProcessing                    True when checkout status is
 *                                                                               AFTER_PROCESSING.
 * @property {boolean}                      isIdle                               True when the checkout state has
 *                                                                               changed and checkout has no activity.
 * @property {boolean}                      isProcessing                         True when checkout has been submitted
 *                                                                               and is being processed. Note, payment
 *                                                                               related processing happens during this
 *                                                                               state. When payment status is success,
 *                                                                               processing happens on the server.
 * @property {boolean}                      isCalculating                        True when something in the checkout is
 *                                                                               resulting in totals being calculated.
 * @property {boolean}                      hasError                             True when the checkout is in an error
 *                                                                               state. Whatever caused the error
 *                                                                               (validation/payment method) will likely
 *                                                                               have triggered a notice.
 * @property {string}                       redirectUrl                          This is the url that checkout will
 *                                                                               redirect to when it's ready.
 * @property {function(function(),number=)} onCheckoutAfterProcessingWithSuccess Used to register a callback that will
 *                                                                               fire after checkout has been processed
 *                                                                               and there are no errors.
 * @property {function(function(),number=)} onCheckoutAfterProcessingWithError   Used to register a callback that will
 *                                                                               fire when the checkout has been
 *                                                                               processed and has an error.
 * @property {function(function(),number=)} onCheckoutBeforeProcessing           Used to register a callback that will
 *                                                                               fire when the checkout has been
 *                                                                               submitted before being sent off to the
 *                                                                               server.
 * @property {CheckoutDispatchActions}      dispatchActions                      Various actions that can be dispatched
 *                                                                               for the checkout context data.
 * @property {number}                       orderId                              This is the ID for the draft order if
 *                                                                               one exists.
 * @property {number}                       orderNotes                           Order notes introduced by the user in
 *                                                                               the checkout form.
 * @property {boolean}                      hasOrder                             True when the checkout has a draft
 *                                                                               order from the API.
 * @property {boolean}                      isCart                               When true, means the provider is
 *                                                                               providing data for the cart.
 * @property {number}                       customerId                           This is the ID of the customer the
 *                                                                               draft order belongs to.
 * @property {boolean}                      shouldCreateAccount                  Should a user account be created?
 * @property {function(boolean)}            setShouldCreateAccount               Function to update the
 *                                                                               shouldCreateAccount property.
 */

/**
 * @typedef {Object} EditorDataContext
 *
 * @property {boolean} isEditor      Indicates whether in the editor context.
 * @property {number}  currentPostId The post ID being edited.
 * @property {Object}  previewData   Object containing preview data for the editor.
 * @property {function(string):Object} getPreviewData Get data by name.
 */

/**
 * @typedef {Object} AddToCartFormContext
 *
 * @property {Object}                         product              The product object to add to the cart.
 * @property {string}                         productType          The name of the product type.
 * @property {boolean}                        productIsPurchasable True if the product can be purchased.
 * @property {boolean}                        productHasOptions    True if the product has additional options and thus
 *                                                                 needs a cart form.
 * @property {boolean}                        supportsFormElements True if the product type supports form elements.
 * @property {boolean}                        showFormElements     True if showing a full add to cart form (enabled and
 *                                                                 supported).
 * @property {number}                         quantity             Stores the quantity being added to the cart.
 * @property {number}                         minQuantity          Min quantity that can be added to the cart.
 * @property {number}                         maxQuantity          Max quantity than can be added to the cart.
 * @property {Object}                         requestParams        List of params to send to the API.
 * @property {boolean}                        isIdle               True when the form state has changed and has no
 *                                                                 activity.
 * @property {boolean}                        isDisabled           True when the form cannot be submitted.
 * @property {boolean}                        isProcessing         True when the form has been submitted and is being
 *                                                                 processed.
 * @property {boolean}                        isBeforeProcessing   True during any observers executing logic before form
 *                                                                 processing (eg. validation).
 * @property {boolean}                        isAfterProcessing    True when form status is AFTER_PROCESSING.
 * @property {boolean}                        hasError             True when the form is in an error state. Whatever
 *                                                                 caused the error (validation/payment method) will
 *                                                                 likely have triggered a notice.
 * @property {AddToCartFormEventRegistration} eventRegistration    Event emitters that can be subscribed to.
 * @property {AddToCartFormDispatchActions}   dispatchActions      Various actions that can be dispatched for the add to
 *                                                                 cart form context data.
 */

/**
 * @typedef {Object} ValidationContext
 *
 * @property {function(string):Object}  getValidationError       Return validation error for the given property.
 * @property {function(Object)}         setValidationErrors      Receive an object of properties and  error messages as
 *                                                               strings and adds to the validation error state.
 * @property {function(string)}         clearValidationError     Clears a validation error for the given property name.
 * @property {function()}               clearAllValidationErrors Clears all validation errors currently in state.
 * @property {function(string)}         getValidationErrorId     Returns the css id for the
 *                                                               validation error using the given inputId string.
 * @property {function(string)}         hideValidationError      Sets the hidden prop of a specific error to true.
 * @property {function(string)}         showValidationError      Sets the hidden prop of a specific error to false.
 * @property {function()}               showAllValidationErrors  Sets the hidden prop of all errors to false.
 * @property {boolean}                  hasValidationErrors      True if there is at least one error.
 */

/**
 * @typedef StoreNoticeObject
 *
 * @property {string} type   The type of notice.
 * @property {string} status The status of the notice.
 * @property {string} id     The id of the notice.
 */

/**
 * @typedef NoticeContext
 *
 * @property {Array<StoreNoticeObject>}              notices              An array of notice objects.
 * @property {function(string,string,any):undefined} createNotice         Creates a notice for the given arguments.
 * @property {function(string, any):undefined}       createSnackbarNotice Creates a snackbar notice type.
 * @property {function(string,string=):undefined}    removeNotice         Removes a notice with the given id and context
 * @property {string}                                context              The current context identifier for the notice
 *                                                                        provider
 * @property {function(boolean):void}                setIsSuppressed      Consumers can use this setter to suppress
 */

/**
 * @typedef {Object} ContainerWidthContext
 *
 * @property {boolean} hasContainerWidth  True once the class name has been derived.
 * @property {string}  containerClassName The class name derived from the width of the container.
 * @property {boolean} isMobile           True if the derived container width is mobile.
 * @property {boolean} isSmall            True if the derived container width is small.
 * @property {boolean} isMedium           True if the derived container width is medium.
 * @property {boolean} isLarge            True if the derived container width is large.
 */

export {};
