/**
 * External dependencies
 */
import { SITE_CURRENCY } from '@woocommerce/settings';
import type {
	Currency,
	CurrencyResponse,
	CartShippingPackageShippingRate,
} from '@woocommerce/types';

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
		return SITE_CURRENCY;
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
		code: code || SITE_CURRENCY.code,
		symbol: symbol || SITE_CURRENCY.symbol,
		thousandSeparator:
			typeof thousandSeparator === 'string'
				? thousandSeparator
				: SITE_CURRENCY.thousandSeparator,
		decimalSeparator:
			typeof decimalSeparator === 'string'
				? decimalSeparator
				: SITE_CURRENCY.decimalSeparator,
		minorUnit: Number.isFinite( minorUnit )
			? minorUnit
			: SITE_CURRENCY.minorUnit,
		prefix: typeof prefix === 'string' ? prefix : SITE_CURRENCY.prefix,
		suffix: typeof suffix === 'string' ? suffix : SITE_CURRENCY.suffix,
	};
};

/**
 * Gets currency information in normalized format, allowing overrides.
 */
export const getCurrency = (
	currencyData: Partial< Currency > = {}
): Currency => {
	return {
		...SITE_CURRENCY,
		...currencyData,
	};
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

	// This uses a textarea to magically decode HTML currency symbols.
	const txt = document.createElement( 'textarea' );
	txt.innerHTML = formattedValue;
	return txt.value;
};
