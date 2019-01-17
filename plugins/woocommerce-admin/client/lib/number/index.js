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

	if ( null === precision ) {
		const [ numberNoDecimals, decimals ] = number.toString().split( '.' );
		const formattedNumber = number_format(
			numberNoDecimals,
			0,
			decimalSeparator,
			thousandSeparator
		);

		if ( decimals ) {
			return formattedNumber + decimalSeparator + decimals;
		}

		return formattedNumber;
	}

	return number_format( number, precision, decimalSeparator, thousandSeparator );
}
