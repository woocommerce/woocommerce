/**
 * External dependencies
 */
import { Currency, CurrencyResponse } from '@woocommerce/types';
import snakecaseKeys from 'snakecase-keys';

export const currencies: Record< string, Currency > = {
	EUR: {
		code: 'EUR',
		symbol: '€',
		thousandSeparator: '.',
		decimalSeparator: ',',
		minorUnit: 2,
		prefix: '',
		suffix: '€',
	},
	USD: {
		code: 'USD',
		symbol: '$',
		thousandSeparator: ',',
		decimalSeparator: '.',
		minorUnit: 2,
		prefix: '$',
		suffix: '',
	},
} as const;

export const currenciesAPIShape: Record<
	string,
	CurrencyResponse
> = Object.fromEntries(
	Object.entries( currencies ).map( ( [ key, value ] ) => [
		key,
		snakecaseKeys( value ),
	] )
);

export const currencyControl = {
	control: 'select',
	defaultValue: currencies.USD,
	mapping: currencies,
	options: Object.keys( currencies ),
};
