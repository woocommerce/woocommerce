/**
 * External dependencies
 */
import type { Currency, CurrencyResponse } from '@woocommerce/types';

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

const applyThousandSeparator = (
	numberString: string,
	thousandSeparator: string
): string => {
	return numberString.replace( /\B(?=(\d{3})+(?!\d))/g, thousandSeparator );
};

export const normalizeCurrencyResponse = (
	currencyData: Partial< CurrencyResponse > | undefined,
	defaultCurrency: Currency
): Currency => {
	if ( ! currencyData?.currency_code ) {
		return defaultCurrency;
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
		code: code || defaultCurrency.code,
		symbol: symbol || defaultCurrency.symbol,
		thousandSeparator:
			typeof thousandSeparator === 'string'
				? thousandSeparator
				: defaultCurrency.thousandSeparator,
		decimalSeparator:
			typeof decimalSeparator === 'string'
				? decimalSeparator
				: defaultCurrency.decimalSeparator,
		minorUnit: Number.isFinite( minorUnit )
			? minorUnit
			: defaultCurrency.minorUnit,
		prefix: typeof prefix === 'string' ? prefix : defaultCurrency.prefix,
		suffix: typeof suffix === 'string' ? suffix : defaultCurrency.suffix,
	};
};

const formatNumberAsCurrencyString = (
	priceInt: number,
	currency: Currency
): string => {
	const { minorUnit, prefix, suffix, decimalSeparator, thousandSeparator } =
		currency;

	const formattedPrice = priceInt / 10 ** minorUnit;

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

	// Use a textarea to decode HTML currency symbols.
	// This used to use @wordpress/html-entities, but that was not necessary
	// for this simple use case and avoids issues with ESM modules / @wordpress packages.
	const txt = document.createElement( 'textarea' );
	txt.innerHTML = formattedValue;
	return txt.value;
};

export const formatPriceWithCurrency = (
	price: number | string,
	defaultCurrency: Currency,
	currencyOverride?: Partial< Currency >
): string => {
	if ( price === '' || price === undefined ) {
		return '';
	}

	const priceInt = typeof price === 'number' ? price : parseInt( price, 10 );

	if ( ! Number.isFinite( priceInt ) ) {
		return '';
	}

	const currency: Currency = {
		...defaultCurrency,
		...currencyOverride,
	};

	return formatNumberAsCurrencyString( priceInt, currency );
};
