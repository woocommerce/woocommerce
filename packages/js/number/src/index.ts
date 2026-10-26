/**
 * External dependencies
 */
import { number_format as numberFormatter } from 'locutus/php/strings/number_format';

/**
 * Number formatting configuration object
 *
 * @typedef {Object} NumberConfig
 * @property {number|string|null} [precision]         Decimal precision.
 * @property {string}             [decimalSeparator]  Decimal separator.
 * @property {string}             [thousandSeparator] Character used to separate thousands groups.
 */
export type NumberConfig = {
	precision: number | string | null;
	decimalSeparator: string;
	thousandSeparator: string;
};

/**
 * Formats a number using site's current locale
 *
 * @see http://locutus.io/php/strings/number_format/
 * @param {NumberConfig}  numberConfig Number formatting configuration object.
 * @param {number|string} number       number to format
 * @return {string} A formatted string.
 */
export function numberFormat(
	{
		precision = null,
		decimalSeparator = '.',
		thousandSeparator = ',',
	}: Partial< NumberConfig >,
	number?: number | string
) {
	if ( number === undefined ) {
		return '';
	}

	if ( typeof number !== 'number' ) {
		number = parseFloat( number );
	}

	if ( isNaN( number ) ) {
		return '';
	}

	let parsedPrecision = precision === null ? NaN : Number( precision );
	if ( isNaN( parsedPrecision ) ) {
		const [ , decimals ] = number.toString().split( '.' );
		parsedPrecision = decimals ? decimals.length : 0;
	}

	// PHP's number_format() rounds half away from zero, while locutus rounds
	// half up (toward +Infinity) via Math.round, so negative exact halves
	// come out one unit toward zero (e.g. -1.005 -> '-1.00' vs PHP '-1.01').
	// Format the absolute value - on every tested row locutus matches PHP
	// for non-negative inputs - and restore the sign.
	const formatted = numberFormatter(
		Math.abs( number ),
		parsedPrecision,
		decimalSeparator,
		thousandSeparator
	);

	if (
		number < 0 &&
		! formatted.startsWith( 'NaN' ) &&
		formatted !==
			numberFormatter(
				0,
				parsedPrecision,
				decimalSeparator,
				thousandSeparator
			)
	) {
		// Restore the sign only for a finite, nonzero result:
		// - A finite formatted magnitude always begins with a digit, while
		//   the pre-existing locutus overflow artifact (e.g. 'NaN.00' for
		//   1e20 at precision 2) is always 'NaN'-prefixed, so the prefix
		//   check keeps that artifact sign-free exactly as trunk does.
		// - PHP drops the sign when the result rounds to zero ('0.00',
		//   never '-0.00'); the zero-compare works for any separator
		//   alphabet, including digits, because it compares formatted
		//   output against formatted output instead of parsing back.
		return `-${ formatted }`;
	}

	return formatted;
}

/**
 * Formats a number as average or number string according to the given `type`.
 *  - `type = 'average'` returns a rounded `Number`
 *  - `type = 'number'` returns a formatted `String`
 *
 * @param {NumberConfig} numberConfig number formatting configuration object.
 * @param {string}       type         of number to format, `'average'` or `'number'`
 * @param {number}       value        to format.
 * @return {string | number | null} A formatted string.
 */
export function formatValue(
	numberConfig: NumberConfig,
	type: string,
	value: number
) {
	if ( ! Number.isFinite( value ) ) {
		return null;
	}

	switch ( type ) {
		case 'average':
			return Math.round( value );
		case 'number':
			return numberFormat( { ...numberConfig, precision: null }, value );
	}
	return null;
}

/**
 * Calculates the delta/percentage change between two numbers.
 *
 * @param {number} primaryValue   the value to calculate change for.
 * @param {number} secondaryValue the baseline against which to calculate the change.
 * @return {?number} Percent change between the primaryValue from the secondaryValue.
 */
export function calculateDelta( primaryValue: number, secondaryValue: number ) {
	if (
		! Number.isFinite( primaryValue ) ||
		! Number.isFinite( secondaryValue )
	) {
		return null;
	}

	if ( secondaryValue === 0 ) {
		return 0;
	}

	return Math.round(
		( ( primaryValue - secondaryValue ) / Math.abs( secondaryValue ) ) * 100
	);
}

/**
 * Parse a string into a number using site's current config
 *
 * @param {NumberConfig} numberConfig Number formatting configuration object.
 * @param {string}       value        value to parse
 * @return {string} A parsed number.
 */
export function parseNumber(
	{
		precision = null,
		decimalSeparator = '.',
		thousandSeparator = ',',
	}: Partial< NumberConfig >,
	value: string
): string {
	if ( typeof value !== 'string' || value === '' ) {
		return '';
	}

	let parsedPrecision = precision === null ? NaN : Number( precision );
	if ( isNaN( parsedPrecision ) ) {
		const [ , decimals ] = value.split( decimalSeparator );
		parsedPrecision = decimals ? decimals.length : 0;
	}
	let parsedValue = value;
	if ( thousandSeparator ) {
		parsedValue = parsedValue.replace(
			new RegExp( `\\${ thousandSeparator }`, 'g' ),
			''
		);
	}
	if ( decimalSeparator ) {
		parsedValue = parsedValue.replace(
			new RegExp( `\\${ decimalSeparator }`, 'g' ),
			'.'
		);
	}

	return Number.parseFloat( parsedValue ).toFixed( parsedPrecision );
}
