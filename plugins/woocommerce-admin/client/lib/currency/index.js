/** @format */
/**
 * External dependencies
 */
import { isNaN } from 'lodash';

/**
 * Formats money with a given currency code. Uses site's current locale for symbol formatting
 *
 * @see https://developer.mozilla.org/en-US/docs/Web/JavaScript/Reference/Global_Objects/NumberFormat
 * @param   {Number|String}  number    number to format
 * @param   {String}         currency  currency code e.g. 'USD'
 * @returns {?String}                  A formatted string.
 */
export function formatCurrency( number, currency ) {
	const locale = wcSettings.locale || 'en-US'; // Default so we don't break.
	if ( 'number' !== typeof number ) {
		number = parseFloat( number );
	}
	if ( isNaN( number ) ) {
		return '';
	}
	return new Intl.NumberFormat( locale, { style: 'currency', currency } ).format( number );
}

/**
 * Get the rounded decimal value of a number at the precision used for a given currency.
 * This is a work-around for fraction-cents, meant to be used like `wc_format_decimal`
 *
 * @param {Number|String} number A floating point number (or integer), or string that converts to a number
 * @return {Number} The original number rounded to a decimal point
 */
export function getCurrencyFormatDecimal( number ) {
	const { precision = 2 } = wcSettings.currency;
	if ( 'number' !== typeof number ) {
		number = parseFloat( number );
	}
	if ( isNaN( number ) ) {
		return 0;
	}
	return Math.round( number * Math.pow( 10, precision ) ) / Math.pow( 10, precision );
}

/**
 * Get the string representation of a floating point number to the precision used by the current currency.
 * This is different from `formatCurrency` by not returning the currency symbol.
 *
 * @param  {Number|String} number A floating point number (or integer), or string that converts to a number
 * @return {String}               The original number rounded to a decimal point
 */
export function getCurrencyFormatString( number ) {
	const { precision = 2 } = wcSettings.currency;
	if ( 'number' !== typeof number ) {
		number = parseFloat( number );
	}
	if ( isNaN( number ) ) {
		return '';
	}
	return number.toFixed( precision );
}
