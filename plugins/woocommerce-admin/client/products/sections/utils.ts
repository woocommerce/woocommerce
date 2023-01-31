/**
 * External dependencies
 */
import { ChangeEvent } from 'react';
import { Product } from '@woocommerce/data';
import { recordEvent } from '@woocommerce/tracks';

/**
 * Internal dependencies
 */
import { NUMBERS_AND_ALLOWED_CHARS } from '../constants';

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
 * Get additional props to be passed to all checkbox inputs.
 *
 * @param name Name of the checkbox.
 * @return Props.
 */
export function getCheckboxTracks< T = Product >( name: string ) {
	return {
		onChange: (
			isChecked: ChangeEvent< HTMLInputElement > | T[ keyof T ]
		) => {
			recordEvent( `product_checkbox_${ name }`, {
				checked: isChecked,
			} );
		},
	};
}

/**
 * Get input props for currency related values and symbol positions.
 *
 * @param {Object} currencyConfig - Currency context
 * @return {Object} Props.
 */
export const getCurrencySymbolProps = ( currencyConfig: CurrencyConfig ) => {
	const { symbol, symbolPosition } = currencyConfig;
	const currencyPosition = symbolPosition.includes( 'left' )
		? 'prefix'
		: 'suffix';

	return {
		[ currencyPosition ]: symbol,
	};
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
