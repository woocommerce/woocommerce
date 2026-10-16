/**
 * External dependencies
 */
import {
	cartStore,
	checkoutStore,
	paymentStore,
} from '@woocommerce/block-data';
import { useSelect } from '@wordpress/data';

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
		const store = select( checkoutStore );
		return {
			// Also wait for shipping address changes that are not sent yet. Otherwise the button is enabled while the
			// shopper types a postcode, then disables itself mid-click when the field blurs, and the click is lost.
			isCalculating:
				store.isCalculating() ||
				select( cartStore ).isShippingRatesStale(),
			isBeforeProcessing: store.isBeforeProcessing(),
			isProcessing: store.isProcessing(),
			isAfterProcessing: store.isAfterProcessing(),
			isComplete: store.isComplete(),
			hasError: store.hasError(),
		};
	} );
	const { activePaymentMethod, isExpressPaymentMethodActive } = useSelect(
		( select ) => {
			const store = select( paymentStore );

			return {
				activePaymentMethod: store.getActivePaymentMethod(),
				isExpressPaymentMethodActive:
					store.isExpressPaymentMethodActive(),
			};
		}
	);

	const { onSubmit } = useCheckoutEventsContext();

	const { paymentMethods = {} } = usePaymentMethods();
	const paymentMethod = paymentMethods[ activePaymentMethod ] || {};
	const waitingForProcessing =
		isProcessing || isAfterProcessing || isBeforeProcessing;
	const waitingForRedirect = isComplete && ! hasError;
	const paymentMethodButtonLabel = paymentMethod.placeOrderButtonLabel;
	const paymentMethodPlaceOrderButton = paymentMethod.placeOrderButton;

	return {
		paymentMethodButtonLabel,
		paymentMethodPlaceOrderButton,
		onSubmit,
		isCalculating,
		isDisabled: isProcessing || isExpressPaymentMethodActive,
		waitingForProcessing,
		waitingForRedirect,
	};
};
