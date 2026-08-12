/**
 * External dependencies
 */
import { CURRENCY } from '@woocommerce/settings';

type CurrencyObject = {
	code: string;
	symbol: string;
	symbolPosition: string;
	precision: number;
	decimalSeparator?: string;
	thousandSeparator?: string;
};

export function getCurrencyObject(): CurrencyObject {
	const parsedPrecision = Number( CURRENCY.precision );
	return {
		code: CURRENCY.code || 'USD',
		symbol: CURRENCY.symbol || '$',
		symbolPosition: CURRENCY.symbolPosition || 'left',
		precision:
			Number.isFinite( parsedPrecision ) && parsedPrecision >= 0
				? parsedPrecision
				: 2,
		decimalSeparator: CURRENCY.decimalSeparator,
		thousandSeparator: CURRENCY.thousandSeparator,
	};
}

export function formatCurrency(
	value: number | string,
	currencyCode = getCurrencyObject().code
) {
	const amount = typeof value === 'number' ? value : Number( value );
	if ( ! Number.isFinite( amount ) ) {
		return '';
	}
	const locale =
		typeof document !== 'undefined'
			? document.documentElement.lang || 'en-US'
			: 'en-US';
	const { precision } = getCurrencyObject();
	const safeCurrencyCode = currencyCode || 'USD';

	try {
		return new Intl.NumberFormat( locale, {
			style: 'currency',
			currency: safeCurrencyCode,
			minimumFractionDigits: precision,
			maximumFractionDigits: precision,
		} ).format( amount );
	} catch {
		return new Intl.NumberFormat( 'en-US', {
			style: 'currency',
			currency: 'USD',
			minimumFractionDigits: 2,
			maximumFractionDigits: 2,
		} ).format( amount );
	}
}
