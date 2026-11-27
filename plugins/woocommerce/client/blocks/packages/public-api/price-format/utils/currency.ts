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
		minorUnit:
			Number.isInteger( minorUnit as number ) &&
			( minorUnit as number ) >= 0
				? ( minorUnit as number )
				: defaultCurrency.minorUnit,
		prefix: typeof prefix === 'string' ? prefix : defaultCurrency.prefix,
		suffix: typeof suffix === 'string' ? suffix : defaultCurrency.suffix,
	};
};

/**
 * Get how many decimals to show an amount with.
 *
 * A price in the store's currency can have more decimals than the store uses when it is shown as entered, such as
 * £1.995. Zeros beyond the store's decimals are dropped, so £2.100 shows as £2.10 and ¥200.0 as ¥200.
 *
 * @param amount        Amount in the currency's minor unit.
 * @param currency      Currency the amount is in.
 * @param storeCurrency The store's currency.
 * @return Number of decimals to show.
 */
export const getDisplayMinorUnit = (
	amount: number,
	currency: Currency,
	storeCurrency: Currency
): number => {
	if ( currency.code !== storeCurrency.code ) {
		return currency.minorUnit;
	}

	let minorUnit = currency.minorUnit;
	let remaining = amount;
	while ( minorUnit > storeCurrency.minorUnit && remaining % 10 === 0 ) {
		remaining /= 10;
		minorUnit--;
	}

	return minorUnit;
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

/**
 * Format a price, given in the currency's minor unit, as a string with currency symbols.
 *
 * @param price            Price in the currency's minor unit.
 * @param defaultCurrency  The store's currency.
 * @param currencyOverride Changes to the currency for this price, such as a price object's higher minor unit. Zeros
 *                         beyond the store currency's decimals are dropped.
 */
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
	const minorUnit = getDisplayMinorUnit(
		priceInt,
		currency,
		defaultCurrency
	);

	return formatNumberAsCurrencyString(
		priceInt / 10 ** ( currency.minorUnit - minorUnit ),
		{ ...currency, minorUnit }
	);
};
