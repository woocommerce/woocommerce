/**
 * External dependencies
 */
import { CHECKOUT_STORE_KEY } from '@woocommerce/block-data';
import { useSelect } from '@wordpress/data';
import { __ } from '@wordpress/i18n';

/**
 * Internal dependencies
 */
import { useCheckoutContext } from '../providers';
import { usePaymentMethodDataContext } from '../providers/cart-checkout/payment-methods';
import { usePaymentMethods } from './payment-methods/use-payment-methods';

/**
 * Returns the submitButtonText, onSubmit interface from the checkout context,
 * and an indication of submission status.
 */
export const useCheckoutSubmit = () => {
	const {
		isCalculating,
		isBeforeProcessing,
		isProcessing,
		isAfterProcessing,
		isComplete,
		hasError,
	} = useSelect( ( select ) => {
		const store = select( CHECKOUT_STORE_KEY );
		return {
			isCalculating: store.isCalculating(),
			isBeforeProcessing: store.isBeforeProcessing(),
			isProcessing: store.isProcessing(),
			isAfterProcessing: store.isAfterProcessing(),
			isComplete: store.isComplete(),
			hasError: store.hasError(),
		};
	} );

	const { onSubmit } = useCheckoutContext();

	const { paymentMethods = {} } = usePaymentMethods();
	const { activePaymentMethod, currentStatus: paymentStatus } =
		usePaymentMethodDataContext();
	const paymentMethod = paymentMethods[ activePaymentMethod ] || {};
	const waitingForProcessing =
		isProcessing || isAfterProcessing || isBeforeProcessing;
	const waitingForRedirect = isComplete && ! hasError;

	return {
		submitButtonText:
			paymentMethod?.placeOrderButtonLabel ||
			__( 'Place Order', 'woo-gutenberg-products-block' ),
		onSubmit,
		isCalculating,
		isDisabled: isProcessing || paymentStatus.isDoingExpressPayment,
		waitingForProcessing,
		waitingForRedirect,
	};
};
