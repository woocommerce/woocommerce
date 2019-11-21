/** @format */
/* eslint-disable wpcalypso/import-docblock */

const number_format = require( 'locutus/php/strings/number_format' );

/**
 * Formats a number using site's current locale
 *
 * @see http://locutus.io/php/strings/number_format/
 * @param {Object} numberConfig number formatting configuration object.
 * @param {Number|String} number number to format
 * @returns {?String} A formatted string.
 */
export function numberFormat( {
	precision = null,
	decimalSeparator = '.',
	thousandSeparator = ',',
}, number ) {
	if ( 'number' !== typeof number ) {
		number = parseFloat( number );
	}

	if ( isNaN( number ) ) {
		return '';
	}

	precision = parseInt( precision );

	if ( isNaN( precision ) ) {
		const [ , decimals ] = number.toString().split( '.' );
		precision = decimals ? decimals.length : 0;
	}

	return number_format( number, precision, decimalSeparator, thousandSeparator );
}

/**
 * Formats a number string based on type of `average` or `number`.
 *
 * @param {Object} numberConfig number formatting configuration object.
 * @param {String} type of number to format, average or number
 * @param {int} value to format.
 * @returns {?String} A formatted string.
 */
export function formatValue( numberConfig, type, value ) {
	if ( ! Number.isFinite( value ) ) {
		return null;
	}

	switch ( type ) {
		case 'average':
			return Math.round( value );
		case 'number':
			return numberFormat( { ...numberConfig, precision: null }, value );
	}
}

/**
 * Calculates the delta/percentage change between two numbers.
 *
 * @param {int} primaryValue the value to calculate change for.
 * @param {int} secondaryValue the baseline which to calculdate the change against.
 * @returns {?int} Percent change between the primaryValue from the secondaryValue.
 */
export function calculateDelta( primaryValue, secondaryValue ) {
	if ( ! Number.isFinite( primaryValue ) || ! Number.isFinite( secondaryValue ) ) {
		return null;
	}

	if ( secondaryValue === 0 ) {
		return 0;
	}

	return Math.round( ( primaryValue - secondaryValue ) / secondaryValue * 100 );
}
