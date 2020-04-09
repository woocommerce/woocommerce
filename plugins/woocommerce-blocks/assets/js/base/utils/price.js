/**
 * External dependencies
 */
import { CURRENCY } from '@woocommerce/settings';

/**
 * Get currency prefix.
 *
 * @param {string} symbol Currency symbol.
 * @param {string} symbolPosition Position of currency symbol from settings.
 */
const getPrefix = ( symbol, symbolPosition ) => {
	const prefixes = {
		left: symbol,
		left_space: ' ' + symbol,
		right: '',
		right_space: '',
	};
	return prefixes[ symbolPosition ] || '';
};

/**
 * Get currency suffix.
 *
 * @param {string} symbol Currency symbol.
 * @param {string} symbolPosition Position of currency symbol from settings.
 */
const getSuffix = ( symbol, symbolPosition ) => {
	const suffixes = {
		left: '',
		left_space: '',
		right: symbol,
		right_space: ' ' + symbol,
	};
	return suffixes[ symbolPosition ] || '';
};

/**
 * Currency information in normalized format from server settings.
 */
const siteCurrencySettings = {
	code: CURRENCY.code,
	symbol: CURRENCY.symbol,
	thousandSeparator: CURRENCY.thousandSeparator,
	decimalSeparator: CURRENCY.decimalSeparator,
	minorUnit: CURRENCY.precision,
	prefix: getPrefix( CURRENCY.symbol, CURRENCY.symbolPosition ),
	suffix: getSuffix( CURRENCY.symbol, CURRENCY.symbolPosition ),
};

/**
 * Gets currency information in normalized format from an API response or the server.
 *
 * @param {Object} currencyData Currency data object, for example an API response containing currency formatting data.
 * @return {Object} Normalized currency info.
 */
export const getCurrencyFromPriceResponse = ( currencyData ) => {
	if ( ! currencyData || typeof currencyData !== 'object' ) {
		return siteCurrencySettings;
	}

	const {
		currency_code: code,
		currency_symbol: symbol,
		currency_thousand_separator: thousandSeparator,
		currency_decimal_separator: decimalSeparator,
		currency_minor_unit: minorUnit,
		currency_prefix: prefix,
		currency_suffix: suffix,
	} = currencyData;

	return {
		code: code || 'USD',
		symbol: symbol || '$',
		thousandSeparator:
			typeof thousandSeparator === 'string' ? thousandSeparator : ',',
		decimalSeparator:
			typeof decimalSeparator === 'string' ? decimalSeparator : '.',
		minorUnit: Number.isFinite( minorUnit ) ? minorUnit : 2,
		prefix: typeof prefix === 'string' ? prefix : '$',
		suffix: typeof suffix === 'string' ? suffix : '',
	};
};

/**
 * Gets currency information in normalized format, allowing overrides.
 *
 * @param {Object} currencyData Currency data object.
 * @return {Object} Normalized currency info.
 */
export const getCurrency = ( currencyData = {} ) => {
	return {
		...siteCurrencySettings,
		...currencyData,
	};
};

/**
 * Format a price, provided using the smallest unit of the currency, as a
 * decimal complete with currency symbols using current store settings.
 *
 * @param {number|string} price Price in minor unit, e.g. cents.
 * @param {Object} currencyData Currency data object.
 */
export const formatPrice = ( price, currencyData ) => {
	if ( price === '' || price === undefined ) {
		return '';
	}

	const priceInt = parseInt( price, 10 );

	if ( ! Number.isFinite( priceInt ) ) {
		return '';
	}

	const currency = getCurrency( currencyData );
	const formattedPrice = priceInt / 10 ** currency.minorUnit;
	const formattedValue = currency.prefix + formattedPrice + currency.suffix;

	// This uses a textarea to magically decode HTML currency symbols.
	const txt = document.createElement( 'textarea' );
	txt.innerHTML = formattedValue;
	return txt.value;
};
