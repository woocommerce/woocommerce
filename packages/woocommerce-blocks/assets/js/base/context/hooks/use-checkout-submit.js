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
	const {
		activePaymentMethod,
		currentStatus: paymentStatus,
	} = usePaymentMethodDataContext();
	const paymentMethod = paymentMethods[ activePaymentMethod ] || {};
	const waitingForProcessing =
		isProcessing || isAfterProcessing || isBeforeProcessing;
	const waitingForRedirect = isComplete && ! hasError;

	return {
		submitButtonText:
			paymentMethod?.placeOrderButtonLabel ||
			__( 'Place Order', 'woocommerce' ),
		onSubmit,
		isCalculating,
		isDisabled: isProcessing || paymentStatus.isDoingExpressPayment,
		waitingForProcessing,
		waitingForRedirect,
	};
};
