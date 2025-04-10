/**
 * External dependencies
 */
import { __ } from '@wordpress/i18n';
import { type ShippingAddress } from '@woocommerce/settings';
import { select, dispatch } from '@wordpress/data';
import { validationStore } from '@woocommerce/block-data';

// If it's the shipping address form and the user starts entering address
// values without having set the country first, show an error.
const validateCountry = (
	addressType: string,
	values: ShippingAddress
): void => {
	const validationErrorId = `${ addressType }_country`;
	const hasValidationError =
		select( validationStore ).getValidationError( validationErrorId );

	if (
		! values.country &&
		( values.city || values.state || values.postcode )
	) {
		if ( hasValidationError ) {
			dispatch( validationStore ).showValidationError(
				validationErrorId
			);
		} else {
			dispatch( validationStore ).setValidationErrors( {
				[ validationErrorId ]: {
					message: __( 'Please select your country', 'woocommerce' ),
					hidden: false,
				},
			} );
		}
	}

	if ( hasValidationError && values.country ) {
		dispatch( validationStore ).clearValidationError( validationErrorId );
	}
};

export default validateCountry;
