/**
 * External dependencies
 */
import numberFormatter from 'locutus/php/strings/number_format';

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

	return numberFormatter(
		number,
		parsedPrecision,
		decimalSeparator,
		thousandSeparator
	);
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
 * @param {number} secondaryValue the baseline which to calculdate the change against.
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
		( ( primaryValue - secondaryValue ) / secondaryValue ) * 100
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

	return Number.parseFloat(
		value
			.replace( new RegExp( `\\${ thousandSeparator }`, 'g' ), '' )
			.replace( new RegExp( `\\${ decimalSeparator }`, 'g' ), '.' )
	).toFixed( parsedPrecision );
}
