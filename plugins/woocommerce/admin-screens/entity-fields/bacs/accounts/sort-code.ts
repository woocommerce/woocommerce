/**
 * External dependencies
 */
import { __ } from '@wordpress/i18n';

/**
 * The countries whose bank accounts have a sort code or its local equivalent.
 */
const SORT_CODE_COUNTRIES = [
	'AU',
	'CA',
	'GB',
	'IE',
	'IN',
	'IT',
	'NZ',
	'SE',
	'US',
	'ZA',
];

/**
 * Whether accounts in the country have a sort code.
 */
export function hasSortCode( country: string ): boolean {
	return SORT_CODE_COUNTRIES.includes( country );
}

/**
 * Get the local name of the sort code in the country.
 */
export function getSortCodeLabel( country: string ): string {
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
}

/**
 * Whether the country writes sort codes as three pairs of digits, such as 12-34-56.
 */
function hasPairedSortCode( country: string ): boolean {
	return country === 'GB' || country === 'IE';
}

/**
 * Show a stored sort code the way the country writes it.
 */
export function formatSortCode( sortCode: string, country: string ): string {
	if ( ! hasPairedSortCode( country ) ) {
		return sortCode;
	}
	return (
		sortCode
			.replace( /\D/g, '' )
			.substring( 0, 6 )
			.match( /.{1,2}/g )
			?.join( '-' ) ?? ''
	);
}

/**
 * Turn a typed sort code into the stored value, which is digits only where the country pairs them.
 */
export function parseSortCode( sortCode: string, country: string ): string {
	return hasPairedSortCode( country )
		? sortCode.replace( /\D/g, '' ).substring( 0, 6 )
		: sortCode;
}
