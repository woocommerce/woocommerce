/**
 * External dependencies
 */
import { checkoutStore, paymentStore } from '@woocommerce/block-data';
import { useSelect } from '@wordpress/data';
import { useMemo } from '@wordpress/element';

/**
 * Internal dependencies
 */
import { useCheckoutEventsContext } from '../providers';
import { usePaymentMethods } from './payment-methods/use-payment-methods';
import { usePaymentMethodInterface } from '@woocommerce/base-context';

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
			isCalculating: store.isCalculating(),
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
	const paymentMethodInterface = usePaymentMethodInterface();

	const { paymentMethods = {} } = usePaymentMethods();
	const paymentMethod = paymentMethods[ activePaymentMethod ] || {};
	const waitingForProcessing =
		isProcessing || isAfterProcessing || isBeforeProcessing;
	const waitingForRedirect = isComplete && ! hasError;
	const paymentMethodButtonLabel = paymentMethod.placeOrderButtonLabel;

	const paymentMethodPlaceOrderButton = useMemo( () => {
		if ( ! paymentMethod.placeOrderButton ) {
			return null;
		}

		return () => paymentMethod.placeOrderButton( paymentMethodInterface );
	}, [paymentMethod.placeOrderButton, paymentMethodInterface] );

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
