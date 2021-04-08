/**
 * External dependencies
 */
import { __ } from '@wordpress/i18n';

/**
 * Internal dependencies
 */
import { useCheckoutContext } from '../providers/cart-checkout/checkout-state';
import { usePaymentMethodDataContext } from '../providers/cart-checkout/payment-methods';
import { usePaymentMethods } from './payment-methods/use-payment-methods';

/**
 * Returns the submitButtonText, onSubmit interface from the checkout context,
 * and an indication of submission status.
 */
export const useCheckoutSubmit = () => {
	const {
		onSubmit,
		isCalculating,
		isBeforeProcessing,
		isProcessing,
		isAfterProcessing,
		isComplete,
		hasError,
	} = useCheckoutContext();
	const { paymentMethods = {} } = usePaymentMethods();
	const { activePaymentMethod } = usePaymentMethodDataContext();
	const paymentMethod = paymentMethods[ activePaymentMethod ] || {};

	return {
		submitButtonText:
			paymentMethod?.placeOrderButtonLabel ||
			__( 'Place Order', 'woo-gutenberg-products-block' ),
		onSubmit,
		isCalculating,
		waitingForProcessing:
			isProcessing || isAfterProcessing || isBeforeProcessing,
		waitingForRedirect: isComplete && ! hasError,
	};
};
