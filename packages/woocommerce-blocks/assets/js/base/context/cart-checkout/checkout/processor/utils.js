/**
 * @typedef {import('@woocommerce/type-defs/payments').PaymentDataItem} PaymentDataItem
 */

/**
 * Utility function for preparing payment data for the request.
 *
 * @param {Object}  paymentData          Arbitrary payment data provided by the payment method.
 * @param {boolean} shouldSave           Whether to save the payment method info to user account.
 * @param {Object}  activePaymentMethod  The current active payment method.
 *
 * @return {PaymentDataItem[]} Returns the payment data as an array of
 *                             PaymentDataItem objects.
 */
export const preparePaymentData = (
	paymentData,
	shouldSave,
	activePaymentMethod
) => {
	const apiData = Object.keys( paymentData ).map( ( property ) => {
		const value = paymentData[ property ];
		return { key: property, value };
	}, [] );
	const savePaymentMethodKey = `wc-${ activePaymentMethod }-new-payment-method`;
	apiData.push( {
		key: savePaymentMethodKey,
		value: shouldSave,
	} );
	return apiData;
};
