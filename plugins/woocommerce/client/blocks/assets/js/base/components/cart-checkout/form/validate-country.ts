/**
 * External dependencies
 */
import { __ } from '@wordpress/i18n';
import { type ShippingAddress } from '@woocommerce/settings';
import { select, dispatch } from '@wordpress/data';
import { validationStore } from '@woocommerce/block-data';
import {
	ALLOWED_COUNTRIES,
	SHIPPING_COUNTRIES,
} from '@woocommerce/block-settings';

// If it's the shipping address form and the user starts entering address
// values without having set the country first, show an error.
const validateCountry = (
	addressType: string,
	values: ShippingAddress
): void => {
	const validationErrorId = `${ addressType }_country`;
	const hasValidationError =
		select( validationStore ).getValidationError( validationErrorId );
	const hasCityStateOrPostcode =
		values.city || values.state || values.postcode;

	try {
		if ( ! values.country && hasCityStateOrPostcode ) {
			throw __( 'Please select your country', 'woocommerce' );
		}

		if (
			addressType === 'billing' &&
			values.country &&
			! Object.keys( ALLOWED_COUNTRIES ).includes( values.country )
		) {
			throw __(
				'Sorry, we do not allow orders from the selected country',
				'woocommerce'
			);
		}

		if (
			addressType === 'shipping' &&
			values.country &&
			! Object.keys( SHIPPING_COUNTRIES ).includes( values.country )
		) {
			throw __(
				'Sorry, we do not ship orders to the selected country',
				'woocommerce'
			);
		}

		// No errors, so clear from store if needed
		if ( hasValidationError ) {
			dispatch( validationStore ).clearValidationError(
				validationErrorId
			);
		}
	} catch ( error ) {
		if ( hasValidationError ) {
			dispatch( validationStore ).showValidationError(
				validationErrorId
			);
		} else {
			dispatch( validationStore ).setValidationErrors( {
				[ validationErrorId ]: {
					message: String( error ),
					hidden: false,
				},
			} );
		}
	}
};

export default validateCountry;
