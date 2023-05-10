/**
 * Internal dependencies
 */
import { NUMBERS_AND_ALLOWED_CHARS } from './constants';

type CurrencyConfig = {
	code: string;
	symbol: string;
	symbolPosition: string;
	decimalSeparator: string;
	priceFormat: string;
	thousandSeparator: string;
	precision: number;
};

/**
 * Cleans and formats the currency value shown to the user.
 *
 * @param {string} value          Form value.
 * @param {Object} currencyConfig Currency context.
 * @return {string} Display value.
 */
export const formatCurrencyDisplayValue = (
	value: string,
	currencyConfig: CurrencyConfig,
	format: ( number: number | string ) => string
) => {
	const { decimalSeparator, thousandSeparator } = currencyConfig;

	const regex = new RegExp(
		NUMBERS_AND_ALLOWED_CHARS.replace( '%s1', decimalSeparator ).replace(
			'%s2',
			thousandSeparator
		),
		'g'
	);

	return value === undefined ? value : format( value ).replace( regex, '' );
};
