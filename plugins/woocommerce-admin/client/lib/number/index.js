/** @format */
/**
 * External dependencies
 */
import { get } from 'lodash';
const number_format = require( 'locutus/php/strings/number_format' );

/**
 * Formats a number using site's current locale
 *
 * @see http://locutus.io/php/strings/number_format/
 * @param {Number|String} number number to format
 * @param {int|null} [precision=null] optional decimal precision
 * @returns {?String} A formatted string.
 */

export function numberFormat( number, precision = null ) {
	if ( 'number' !== typeof number ) {
		number = parseFloat( number );
	}

	if ( isNaN( number ) ) {
		return '';
	}

	const decimalSeparator = get( wcSettings, [ 'currency', 'decimal_separator' ], '.' );
	const thousandSeparator = get( wcSettings, [ 'currency', 'thousand_separator' ], ',' );
	precision = parseInt( precision );

	if ( isNaN( precision ) ) {
		const [ , decimals ] = number.toString().split( '.' );
		precision = decimals ? decimals.length : 0;
	}

	return number_format( number, precision, decimalSeparator, thousandSeparator );
}
