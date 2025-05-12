/**
 * External dependencies
 */
import { __ } from '@wordpress/i18n';

/**
 * Determines the label for the sort code field based on the country.
 *
 * @param  country The country code (e.g., 'AU', 'CA', etc.).
 *
 * @return {string} The label for the sort code field.
 */
export const getSortcodeLabel = ( country: string ) => {
	switch ( country ) {
		case 'AU':
			return __( 'BSB', 'woocommerce' );
		case 'CA':
			return __( 'Bank transit number', 'woocommerce' );
		case 'IN':
			return __( 'IFSC', 'woocommerce' );
		case 'IT':
			return __( 'Branch sort', 'woocommerce' );
		case 'NZ':
		case 'SE':
			return __( 'Bank code', 'woocommerce' );
		case 'US':
			return __( 'Routing number', 'woocommerce' );
		case 'ZA':
			return __( 'Branch code', 'woocommerce' );
		default:
			return __( 'Sort code', 'woocommerce' );
	}
};

/**
 * Generates a random ID.
 *
 * @return {string} A random ID string.
 */
export const generateId = (): string =>
	Math.random().toString( 36 ).substring( 2, 10 );
