const numberFormatter = require( 'locutus/php/strings/number_format' );

/**
 * Formats a number using site's current locale
 *
 * @see http://locutus.io/php/strings/number_format/
 * @param {Object} numberConfig number formatting configuration object.
 * @param numberConfig.precision
 * @param numberConfig.decimalSeparator
 * @param numberConfig.thousandSeparator
 * @param {number|string} number number to format
 * @return {?string} A formatted string.
 */
export function numberFormat(
	{ precision = null, decimalSeparator = '.', thousandSeparator = ',' },
	number
) {
	if ( typeof number !== 'number' ) {
		number = parseFloat( number );
	}

	if ( isNaN( number ) ) {
		return '';
	}

	let parsedPrecision = parseInt( precision, 10 );

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
 * Formats a number string based on type of `average` or `number`.
 *
 * @param {Object} numberConfig number formatting configuration object.
 * @param {string} type of number to format, average or number
 * @param {number} value to format.
 * @return {?string} A formatted string.
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
 * @param {number} primaryValue the value to calculate change for.
 * @param {number} secondaryValue the baseline which to calculdate the change against.
 * @return {?number} Percent change between the primaryValue from the secondaryValue.
 */
export function calculateDelta( primaryValue, secondaryValue ) {
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
