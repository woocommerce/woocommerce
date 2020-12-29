/**
 * @typedef {Object} CheckoutDispatchActions
 *
 * @property {function():void}              resetCheckout        Dispatches an action that resets the checkout to a pristine state.
 * @property {function(string):void}        setRedirectUrl       Dispatches an action that sets the redirectUrl.
 * @property {function(boolean=):void}      setHasError          Dispatches an action that sets the checkout status to having an error.
 * @property {function(Object):void}        setAfterProcessing   Dispatches an action that sets the checkout status to after processing and also sets the response data accordingly.
 * @property {function():void}              incrementCalculating Dispatches an action that increments  the calculating state for checkout by one.
 * @property {function():void}              decrementCalculating Dispatches an action that decrements the calculating state for checkout by one.
 * @property {function(number|string):void} setOrderId           Dispatches an action that stores the draft order ID and key.
 * @property {function(string):void}        setOrderNotes        Dispatches an action that sets the order notes.
 * @property {function(number):void}        setCustomerId        Dispatches an action that stores the customer ID.
 */

/**
 * @typedef {Object} CheckoutStatusConstants
 *
 * @property {string} PRISTINE          Checkout is in it's initialized state.
 * @property {string} IDLE              When checkout state has changed but there is no activity happening.
 * @property {string} BEFORE_PROCESSING This is the state before checkout processing begins after the checkout button has been pressed/submitted.
 * @property {string} PROCESSING        After BEFORE_PROCESSING status emitters have finished successfully. Payment processing is started on this checkout status.
 * @property {string} AFTER_PROCESSING  After server side checkout processing is completed this status is set.
 * @property {string} COMPLETE          After the AFTER_PROCESSING event emitters have completed. This status triggers the checkout redirect.
 */

export {};
