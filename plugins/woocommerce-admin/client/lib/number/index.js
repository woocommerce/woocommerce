/** @format */
/**
 * External dependencies
 */
import { get, isFinite } from 'lodash';
const number_format = require( 'locutus/php/strings/number_format' );
import { formatCurrency } from '@woocommerce/currency';

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

export function formatValue( type, value ) {
	if ( ! isFinite( value ) ) {
		return null;
	}

	switch ( type ) {
		case 'average':
			return Math.round( value );
		case 'currency':
			return formatCurrency( value );
		case 'number':
			return numberFormat( value );
	}
}

export function calculateDelta( primaryValue, secondaryValue ) {
	if ( ! isFinite( primaryValue ) || ! isFinite( secondaryValue ) ) {
		return null;
	}

	if ( secondaryValue === 0 ) {
		return 0;
	}

	return Math.round( ( primaryValue - secondaryValue ) / secondaryValue * 100 );
}
