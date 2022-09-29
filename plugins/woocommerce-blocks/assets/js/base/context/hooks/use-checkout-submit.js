/**
 * External dependencies
 */
import {
	CHECKOUT_STORE_KEY,
	PAYMENT_METHOD_DATA_STORE_KEY,
} from '@woocommerce/block-data';
import { useSelect } from '@wordpress/data';
import { __ } from '@wordpress/i18n';
import { __experimentalApplyCheckoutFilter } from '@woocommerce/blocks-checkout';

/**
 * Internal dependencies
 */
import { useCheckoutEventsContext } from '../providers';
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
	const { currentStatus: paymentStatus, activePaymentMethod } = useSelect(
		( select ) => {
			const store = select( PAYMENT_METHOD_DATA_STORE_KEY );

			return {
				currentStatus: store.getCurrentStatus(),
				activePaymentMethod: store.getActivePaymentMethod(),
			};
		}
	);

	const { onSubmit } = useCheckoutEventsContext();

	const { paymentMethods = {} } = usePaymentMethods();
	const paymentMethod = paymentMethods[ activePaymentMethod ] || {};
	const waitingForProcessing =
		isProcessing || isAfterProcessing || isBeforeProcessing;
	const waitingForRedirect = isComplete && ! hasError;
	const defaultLabel =
		paymentMethod.placeOrderButtonLabel ||
		__( 'Place Order', 'woo-gutenberg-products-block' );
	const label = __experimentalApplyCheckoutFilter( {
		filterName: 'placeOrderButtonLabel',
		defaultValue: defaultLabel,
	} );

	return {
		submitButtonText: label,
		onSubmit,
		isCalculating,
		isDisabled: isProcessing || paymentStatus.isDoingExpressPayment,
		waitingForProcessing,
		waitingForRedirect,
	};
};
