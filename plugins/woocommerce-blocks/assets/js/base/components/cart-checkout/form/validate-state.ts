/**
 * External dependencies
 */
import { dispatch, select } from '@wordpress/data';
import { ShippingAddress } from '@woocommerce/settings';
import { VALIDATION_STORE_KEY } from '@woocommerce/block-data';
import { __ } from '@wordpress/i18n';

export const validateState = (
	addressType: string,
	values: ShippingAddress,
	isRequired: boolean
) => {
	const validationErrorId = `${ addressType }_state`;
	const hasValidationError =
		select( VALIDATION_STORE_KEY ).getValidationError( validationErrorId );

	if ( values.country && isRequired && ! values.state ) {
		if ( hasValidationError ) {
			dispatch( VALIDATION_STORE_KEY ).showValidationError(
				validationErrorId
			);
		} else {
			dispatch( VALIDATION_STORE_KEY ).setValidationErrors( {
				[ validationErrorId ]: {
					message: __( 'Please select your state', 'woocommerce' ),
					hidden: true,
				},
			} );
		}
	}

	// If the user changed to a country that does not require a state, or a value was selected, clear the error.
	if ( hasValidationError && ( ! isRequired || values.state ) ) {
		dispatch( VALIDATION_STORE_KEY ).clearValidationError(
			validationErrorId
		);
	}
};
