/** @format */
/**
 * External dependencies
 */
import { get } from 'lodash';
const number_format = require( 'locutus/php/strings/number_format' );

/**
 * Formats a number using site's current locale
 *
 * @see https://locutus.io/php/strings/number_format/
 * @param {Number|String} number number to format
 * @returns {?String} A formatted string.
 */

export function numberFormat( number ) {
	if ( 'number' !== typeof number ) {
		number = parseFloat( number );
	}

	if ( isNaN( number ) ) {
		return '';
	}

	const precision = get( wcSettings, [ 'currency', 'precision' ], 2 );
	const decimalSeparator = get( wcSettings, [ 'currency', 'decimal_separator' ], '.' );
	const thousandSeparator = get( wcSettings, [ 'currency', 'thousand_separator' ], ',' );

	return number_format( number, precision, decimalSeparator, thousandSeparator );
}
