/**
 * External dependencies
 */
import { useCallback } from '@wordpress/element';
import { useDispatch, select } from '@wordpress/data';
import { validationStore } from '@woocommerce/block-data';
import {
	checkoutEventsEmitter,
	CHECKOUT_EVENTS,
} from '@woocommerce/blocks-checkout-events';
import {
	isErrorResponse,
	isFailResponse,
	isSuccessResponse,
} from '@woocommerce/types';

/**
 * Scrolls to and focuses the first validation error element.
 */
const scrollToFirstValidationError = (): void => {
	const errorSelector = 'input:invalid, .has-error input, .has-error select';
	const firstErrorElement =
		document.querySelector< HTMLElement >( errorSelector );
	if ( firstErrorElement ) {
		firstErrorElement.scrollIntoView( { block: 'center' } );
		firstErrorElement.focus();
	}
};

/**
 * Hook that provides checkout validation with automatic scroll-to-error behavior.
 *
 * This hook validates the checkout form by emitting the CHECKOUT_VALIDATION event
 * and checking for validation errors in the validation store. If errors are found,
 * it automatically shows all validation errors and scrolls to the first error element.
 *
 * @return A function that validates checkout and returns a promise with the validation result.
 */
export const useValidateCheckout = (): ( () => Promise< {
	hasError: boolean;
} > ) => {
	const { showAllValidationErrors, setValidationErrors } =
		useDispatch( validationStore );

	return useCallback( async () => {
		// Emit validation event and collect responses from registered callbacks
		const responses = await checkoutEventsEmitter.emit(
			CHECKOUT_EVENTS.CHECKOUT_VALIDATION
		);

		// Check if any callback returned an error/fail response
		const hasCallbackError = responses.some(
			( response ) =>
				isErrorResponse( response ) || isFailResponse( response )
		);

		// Check if any callback returned a non-success response
		// (similar to __internalEmitValidateEvent behavior)
		const hasNonSuccessResponse =
			responses.length > 0 && ! responses.every( isSuccessResponse );

		// Check the validation store for field-level validation errors
		const hasValidationStoreErrors =
			select( validationStore ).hasValidationErrors();

		const hasError =
			hasCallbackError ||
			hasNonSuccessResponse ||
			hasValidationStoreErrors;

		if ( hasError ) {
			// Set any validation errors from callbacks
			responses.forEach( ( response ) => {
				if (
					isErrorResponse( response ) ||
					isFailResponse( response )
				) {
					if ( response.validationErrors ) {
						setValidationErrors( response.validationErrors );
					}
				}
			} );

			// Show all validation errors and scroll to the first one
			showAllValidationErrors();
			window.setTimeout( scrollToFirstValidationError, 50 );
		}

		return { hasError };
	}, [ showAllValidationErrors, setValidationErrors ] );
};
