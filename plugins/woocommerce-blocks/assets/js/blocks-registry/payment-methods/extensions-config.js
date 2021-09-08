// Keeps callbacks registered by extensions for different payment methods
/* eslint prefer-const: 0 */
export let canMakePaymentExtensionsCallbacks = {};

export const extensionsConfig = {
	canMakePayment: canMakePaymentExtensionsCallbacks,
};
