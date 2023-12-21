/**
 * External dependencies
 */
import { useContext, useEffect } from '@wordpress/element';
import { CurrencyContext } from '@woocommerce/currency';
import { numberFormat, parseNumber } from '@woocommerce/number';

/**
 * Escape special characters for user input in regex.
 *
 * @param {string} string
 * @return {string} string
 */
const escapeRegExp = ( string ) => {
	return string.replace( /[.*+?^${}()|[\]\\]/g, '\\$&' );
};

/**
 * Format number when it's exclusively a number or a string of numbers, otherwise return the input.
 */
export const safeNumberFormat = ( config, number ) => {
	if ( typeof number === 'number' ) {
		return numberFormat( config, number );
	}

	if ( typeof number === 'string' ) {
		const dot = escapeRegExp( config.decimalSeparator );
		const comma = escapeRegExp( config.thousandSeparator );

		// Regex to match strictly numbers with arbitrary thousands and decimal separators.
		// Example: /^\s*(\d+|\d{1,3}(?:,\d{3})*)(?:\.\d+)?\s*$/ for default config.
		const regex = new RegExp(
			`^\\s*(\\d+|\\d{1,3}(?:${ comma }\\d{3})*)(?:${ dot }\\d+)?\\s*$`
		);

		return number.replace( regex, ( n ) => {
			const parsed = parseNumber( config, n );
			return numberFormat( config, parsed );
		} );
	}

	return number;
};

export const ShippingCurrencyContext = () => {
	const context = useContext( CurrencyContext );

	useEffect( () => {
		window.wc.ShippingCurrencyContext =
			window.wc.ShippingCurrencyContext || context;
		window.wc.ShippingCurrencyNumberFormat =
			window.wc.ShippingCurrencyNumberFormat || safeNumberFormat;
	}, [ context ] );

	return null;
};
