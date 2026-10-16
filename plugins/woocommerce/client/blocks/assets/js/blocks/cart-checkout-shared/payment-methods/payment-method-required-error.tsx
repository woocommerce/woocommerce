/**
 * External dependencies
 */
import { __ } from '@wordpress/i18n';
import { useEffect } from '@wordpress/element';
import { useDispatch, useSelect } from '@wordpress/data';
import { paymentStore, validationStore } from '@woocommerce/block-data';
import { ValidationInputError } from '@woocommerce/blocks-components';
import type { ReactNode } from 'react';

const ERROR_ID = 'payment-method';

/**
 * Asks the shopper to select a payment method when the server preselected nothing.
 *
 * The error is registered only while nothing is selected, which never happens when the server
 * preselects a method, since the block then always has one. It starts hidden and Place order
 * reveals it with the other field errors. While it shows, the options sit in a `has-error` wrapper
 * so the checkout focuses the first one, as for a field in error, and the message sits below the
 * list so it stays in view after that focus. The payment step itself only renders when the cart
 * needs payment.
 */
const PaymentMethodRequiredError = ( {
	children,
}: {
	children: ReactNode;
} ): JSX.Element => {
	const { paymentMethodRequired, visibleMessage } = useSelect( ( select ) => {
		const payment = select( paymentStore );
		const error = select( validationStore ).getValidationError( ERROR_ID );
		return {
			paymentMethodRequired:
				payment.paymentMethodsInitialized() &&
				Object.keys( payment.getAvailablePaymentMethods() ).length >
					0 &&
				! payment.getActivePaymentMethod(),
			// A string, so the component re-renders when the error is revealed.
			visibleMessage: error && ! error.hidden ? error.message : '',
		};
	}, [] );
	const { setValidationErrors, clearValidationError } =
		useDispatch( validationStore );

	useEffect( () => {
		if ( paymentMethodRequired ) {
			void setValidationErrors( {
				[ ERROR_ID ]: {
					message: __(
						'Please select a payment method.',
						'woocommerce'
					),
					hidden: true,
				},
			} );
		}
		return () => {
			void clearValidationError( ERROR_ID );
		};
	}, [ paymentMethodRequired, setValidationErrors, clearValidationError ] );

	// The wrapper is always there so the options don't remount when the error comes and goes.
	return (
		<>
			<div className={ visibleMessage ? 'has-error' : undefined }>
				{ children }
			</div>
			{ visibleMessage && (
				<ValidationInputError errorMessage={ visibleMessage } />
			) }
		</>
	);
};

export default PaymentMethodRequiredError;
