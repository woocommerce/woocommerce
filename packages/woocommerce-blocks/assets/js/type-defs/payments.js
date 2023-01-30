/**
 * @typedef {Object} PaymentDataItem
 *
 * @property {string} key   Property for the payment data item.
 * @property {string} value Value for the payment data item.
 */

/**
 * @typedef {Object} ExpressPaymentMethodRegistrationOptions
 *
 * @property {string} name              A unique string to identify the payment method client side.
 * @property {Object} content           A react node for your payment method UI.
 * @property {Object} edit              A react node to display a preview of your payment method in the editor.
 * @property {Function} canMakePayment  A callback to determine whether the payment method should be shown in the checkout.
 * @property {string} [paymentMethodId] A unique string to represent the payment method server side. If not provided, defaults to name.
 */

/**
 * @typedef {Object} PaymentMethodRegistrationOptions
 *
 * @property {string} name                    A unique string to identify the payment method client side.
 * @property {Object} content                 A react node for your payment method UI.
 * @property {Object} edit                    A react node to display a preview of your payment method in the editor.
 * @property {Array} [icons]                  Array of card types (brands) supported by the payment method. (See stripe/credit-card for example.)
 * @property {Function} canMakePayment        A callback to determine whether the payment method should be shown in the checkout.
 * @property {string} [paymentMethodId]       A unique string to represent the payment method server side. If not provided, defaults to name.
 * @property {Object} label                   A react node that will be used as a label for the payment method in the checkout.
 * @property {string} ariaLabel               An accessibility label. Screen readers will output this label when the payment method is selected.
 * @property {string} [placeOrderButtonLabel] Optionally customise the label text for the checkout submit (`Place Order`) button.
 */

export {};
