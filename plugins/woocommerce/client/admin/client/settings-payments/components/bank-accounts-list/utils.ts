/**
 * External dependencies
 */
import { __ } from '@wordpress/i18n';

/**
 * Determines the label for the sort code field based on the country.
 *
 * @param country The country code (e.g., 'AU', 'CA', etc.).
 *
 * @return {string} The label for the sort code field.
 */
export const getSortcodeLabel = ( country: string ) => {
	if ( country === 'AU' ) return __( 'BSB', 'woocommerce' );
	if ( country === 'CA' ) return __( 'Bank transit number', 'woocommerce' );
	if ( country === 'IN' ) return __( 'IFSC', 'woocommerce' );
	if ( country === 'IT' ) return __( 'Branch sort', 'woocommerce' );
	if ( country === 'NZ' ) return __( 'Bank code', 'woocommerce' );
	if ( country === 'SE' ) return __( 'Bank code', 'woocommerce' );
	if ( country === 'US' ) return __( 'Routing number', 'woocommerce' );
	if ( country === 'ZA' ) return __( 'Branch code', 'woocommerce' );

	return __( 'Sort code', 'woocommerce' );
};

/**
 * Generates a random ID.
 *
 * @return {string} A random ID string.
 */
export const generateId = (): string =>
	Math.random().toString( 36 ).substring( 2, 10 );
