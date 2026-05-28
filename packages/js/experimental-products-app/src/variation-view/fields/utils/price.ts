/**
 * External dependencies
 */
import { __ } from '@wordpress/i18n';

export const getLocalDefaultSaleStart = () => {
	const tomorrow = new Date();
	tomorrow.setDate( tomorrow.getDate() + 1 );
	tomorrow.setHours( 7, 0, 0, 0 );
	tomorrow.setMinutes( tomorrow.getMinutes() - tomorrow.getTimezoneOffset() );
	return tomorrow.toISOString().slice( 0, 16 );
};

export const toNumberOrNaN = ( value: unknown ) => {
	if ( typeof value === 'number' ) {
		return value;
	}

	if ( typeof value === 'string' && value.trim() !== '' ) {
		return Number.parseFloat( value );
	}

	return Number.NaN;
};

export function validatePrice( value: unknown ): string | null {
	if (
		value === undefined ||
		value === null ||
		( typeof value === 'string' && value.trim() === '' )
	) {
		return null;
	}

	const parsed = toNumberOrNaN( value );

	if ( Number.isNaN( parsed ) ) {
		return __( 'Please enter a valid price.', 'woocommerce' );
	}

	if ( parsed < 0 ) {
		return __( 'Price must not be negative.', 'woocommerce' );
	}

	return null;
}
