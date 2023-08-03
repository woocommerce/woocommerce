/**
 * External dependencies
 */
import { __ } from '@wordpress/i18n';
import { type ShippingAddress } from '@woocommerce/settings';
import { select, dispatch } from '@wordpress/data';
import { VALIDATION_STORE_KEY } from '@woocommerce/block-data';

// If it's the shipping address form and the user starts entering address
// values without having set the country first, show an error.
const validateShippingCountry = ( values: ShippingAddress ): void => {
	const validationErrorId = 'shipping_country';
	const hasValidationError =
		select( VALIDATION_STORE_KEY ).getValidationError( validationErrorId );
	if (
		! values.country &&
		( values.city || values.state || values.postcode )
	) {
		if ( hasValidationError ) {
			dispatch( VALIDATION_STORE_KEY ).showValidationError(
				validationErrorId
			);
		} else {
			dispatch( VALIDATION_STORE_KEY ).setValidationErrors( {
				[ validationErrorId ]: {
					message: __(
						'Please select your country',
						'woo-gutenberg-products-block'
					),
					hidden: false,
				},
			} );
		}
	}

	if ( hasValidationError && values.country ) {
		dispatch( VALIDATION_STORE_KEY ).clearValidationError(
			validationErrorId
		);
	}
};

export default validateShippingCountry;
