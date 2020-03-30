/**
 * @typedef {Object} CheckoutDispatchActions
 *
 * @property {function()}                 resetCheckout        Dispatches an action that resets
 *                                                             the checkout to a pristine state.
 * @property {function( string )}         setRedirectUrl       Dispatches an action that sets the
 *                                                             redirectUrl to the given value.
 * @property {function()}                 setHasError          Dispatches an action that sets the
 *                                                             checkout status to having an error.
 * @property {function()}                 clearError           Dispatches an action that clears the
 *                                                             hasError status for the checkout.
 * @property {function()}                 incrementCalculating Dispatches an action that increments
 *                                                             the calculating state for checkout by one.
 * @property {function()}                 decrementCalculating Dispatches an action that decrements
 *                                                             the calculating state for checkout by one.
 * @property {function( number, string )} setOrderId           Dispatches an action that stores the draft
 *                                                             order ID and key to state.
 */

/**
 * @typedef {Object} CheckoutStatusConstants
 *
 * @property {string} PRISTINE    Checkout is in it's initialized state.
 * @property {string} IDLE        When checkout state has changed but there is
 *                                no activity happening.
 * @property {string} CALCULATING When something in the checkout results in the
 *                                totals being recalculated, this will be the
 *                                state while calculating is happening.
 * @property {string} PROCESSING  This is the state when the checkout button has
 *                                been pressed and the checkout data has been
 *                                sent to the server for processing.
 * @property {string} COMPLETE    This is the status when the server has
 *                                completed processing the data successfully.
 */

export {};
