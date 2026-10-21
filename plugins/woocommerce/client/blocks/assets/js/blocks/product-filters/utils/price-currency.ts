/**
 * External dependencies
 */
import {
	type Currency,
	type CurrencyResponse,
	type CartShippingPackageShippingRate,
} from '@woocommerce/types';
import { getConfig } from '@wordpress/interactivity';
import {
	getCurrencyPrefix,
	getCurrencySuffix,
} from '@woocommerce/settings/utils';

/**
 * Internal dependencies
 */
const currencyConfig = getConfig( 'woocommerce' ).currency;

const siteCurrency: Currency = {
	...currencyConfig,
	suffix: getCurrencySuffix(
		currencyConfig.symbol,
		currencyConfig.symbolPosition
	),
	prefix: getCurrencyPrefix(
		currencyConfig.symbol,
		currencyConfig.symbolPosition
	),
};

/**
 * Gets currency information in normalized format from an API response or the server.
 *
 * If no currency was provided, or currency_code is empty, the default store currency will be used.
 */
export const getCurrencyFromPriceResponse = (
	// Currency data object, for example an API response containing currency formatting data.
	currencyData?:
		| CurrencyResponse
		| Record< string, never >
		| CartShippingPackageShippingRate
): Currency => {
	if ( ! currencyData?.currency_code ) {
		return siteCurrency;
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
		code: code || siteCurrency.code,
		symbol: symbol || siteCurrency.symbol,
		thousandSeparator:
			typeof thousandSeparator === 'string'
				? thousandSeparator
				: siteCurrency.thousandSeparator,
		decimalSeparator:
			typeof decimalSeparator === 'string'
				? decimalSeparator
				: siteCurrency.decimalSeparator,
		minorUnit: Number.isFinite( minorUnit )
			? minorUnit
			: siteCurrency.minorUnit,
		prefix: typeof prefix === 'string' ? prefix : siteCurrency.prefix,
		suffix: typeof suffix === 'string' ? suffix : siteCurrency.suffix,
	};
};

/**
 * Gets currency information in normalized format, allowing overrides.
 */
export const getCurrency = (
	currencyData: Partial< Currency > = {}
): Currency => {
	return {
		...siteCurrency,
		...currencyData,
	};
};

/**
 * Parse a complete price in the store's format, including its optional currency affixes.
 * Returns major units, or null for an incomplete or malformed value.
 */
export const parsePriceInput = (
	input: string,
	currency: Currency = getCurrency()
): number | null => {
	const { prefix, suffix, decimalSeparator, thousandSeparator } = currency;
	let value = input.trim();
	if ( prefix && value.startsWith( prefix.trim() ) ) {
		value = value.slice( prefix.trim().length ).trim();
	}
	if ( suffix && value.endsWith( suffix.trim() ) ) {
		value = value.slice( 0, -suffix.trim().length ).trim();
	}

	const parts = decimalSeparator
		? value.split( decimalSeparator )
		: [ value ];
	if (
		parts.length > 2 ||
		( parts.length === 2 && ! /^[0-9]+$/.test( parts[ 1 ] ) )
	) {
		return null;
	}

	const groups = thousandSeparator
		? parts[ 0 ].split( thousandSeparator )
		: [ parts[ 0 ] ];
	if (
		groups.some( ( group ) => ! /^[0-9]+$/.test( group ) ) ||
		( groups.length > 1 &&
			( groups[ 0 ].length > 3 ||
				groups.slice( 1 ).some( ( group ) => group.length !== 3 ) ) )
	) {
		return null;
	}

	const price = Number( `${ groups.join( '' ) }.${ parts[ 1 ] || '0' }` );
	return Number.isFinite( price ) ? price : null;
};

const applyThousandSeparator = (
	numberString: string,
	thousandSeparator: string
): string => {
	return numberString.replace( /\B(?=(\d{3})+(?!\d))/g, thousandSeparator );
};

const splitDecimal = (
	numberString: string
): {
	beforeDecimal: string;
	afterDecimal: string;
} => {
	const parts = numberString.split( '.' );
	const beforeDecimal = parts[ 0 ];
	const afterDecimal = parts[ 1 ] || '';
	return {
		beforeDecimal,
		afterDecimal,
	};
};

const applyDecimal = (
	afterDecimal: string,
	decimalSeparator: string,
	minorUnit: number
): string => {
	if ( afterDecimal ) {
		return `${ decimalSeparator }${ afterDecimal.padEnd(
			minorUnit,
			'0'
		) }`;
	}

	if ( minorUnit > 0 ) {
		return `${ decimalSeparator }${ '0'.repeat( minorUnit ) }`;
	}

	return '';
};

/**
 * Format a price, provided using the smallest unit of the currency, as a
 * decimal complete with currency symbols using current store settings.
 */
export const formatPrice = (
	// Price in minor unit, e.g. cents.
	price: number | string,
	currencyData?: Currency
): string => {
	if ( price === '' || price === undefined ) {
		return '';
	}

	const priceInt: number =
		typeof price === 'number' ? price : parseInt( price, 10 );

	if ( ! Number.isFinite( priceInt ) ) {
		return '';
	}

	const currency: Currency = getCurrency( currencyData );

	const { minorUnit, prefix, suffix, decimalSeparator, thousandSeparator } =
		currency;

	const formattedPrice: number = priceInt / 10 ** minorUnit;

	const { beforeDecimal, afterDecimal } = splitDecimal(
		formattedPrice.toString()
	);

	const formattedValue = `${ prefix }${ applyThousandSeparator(
		beforeDecimal,
		thousandSeparator
	) }${ applyDecimal(
		afterDecimal,
		decimalSeparator,
		minorUnit
	) }${ suffix }`;

	return formattedValue;
};
