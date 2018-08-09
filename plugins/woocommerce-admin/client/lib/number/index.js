/**
 * Formats a number using site's current locale
 *
 * @format
 * @see https://developer.mozilla.org/en-US/docs/Web/JavaScript/Reference/Global_Objects/NumberFormat
 * @param {Number|String}  number    number to format
 * @returns {?String}                  A formatted string.
 */

export function numberFormat( number ) {
	const locale = wcSettings.siteLocale || 'en-US'; // Default so we don't break.

	if ( 'number' !== typeof number ) {
		number = parseFloat( number );
	}
	if ( isNaN( number ) ) {
		return '';
	}
	return new Intl.NumberFormat( locale ).format( number );
}
