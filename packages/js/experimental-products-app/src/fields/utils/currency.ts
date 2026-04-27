/**
 * External dependencies
 */
import {
	CURRENCY,
	// @ts-expect-error - The CURRENCY object doesn't have types yet.
} from '@woocommerce/settings';

type CurrencyObject = {
	code: string;
	symbol: string;
	symbolPosition: string;
	precision: number;
	decimalSeparator?: string;
	thousandSeparator?: string;
};

export function getCurrencyObject(): CurrencyObject {
	return {
		code: CURRENCY.code || 'USD',
		symbol: CURRENCY.symbol || '$',
		symbolPosition: CURRENCY.symbolPosition || 'left',
		precision: Number( CURRENCY.precision ?? 2 ),
		decimalSeparator: CURRENCY.decimalSeparator,
		thousandSeparator: CURRENCY.thousandSeparator,
	};
}

export function formatCurrency(
	value: number | string,
	currencyCode = getCurrencyObject().code
) {
	const amount = typeof value === 'number' ? value : Number( value );
	const locale =
		typeof document !== 'undefined'
			? document.documentElement.lang || 'en-US'
			: 'en-US';
	const { precision } = getCurrencyObject();

	return new Intl.NumberFormat( locale, {
		style: 'currency',
		currency: currencyCode,
		minimumFractionDigits: precision,
		maximumFractionDigits: precision,
	} ).format( amount );
}
