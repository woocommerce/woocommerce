/**
 * External dependencies
 */
import classnames from 'classnames';
import { recordEvent } from '@woocommerce/tracks';

/**
 * Internal dependencies
 */
import { NUMBERS_AND_ALLOWED_CHARS } from '../constants';

type gettersProps = {
	context?: {
		formatAmount: ( number: number | string ) => string;
		getCurrencyConfig: () => {
			code: string;
			symbol: string;
			symbolPosition: string;
			decimalSeparator: string;
			priceFormat: string;
			thousandSeparator: string;
			precision: number;
		};
	};
	value: string;
	name?: string;
	checked: boolean;
	selected?: boolean;
	// eslint-disable-next-line @typescript-eslint/no-explicit-any
	onChange: ( value: any ) => void;
	onBlur: () => void;
	className: string | undefined;
	help: string | null | undefined;
};

/**
 * Get additional props to be passed to all checkbox inputs.
 * @param name Name of the checkbox
 * @returns object Props.
 */
export const getCheckboxProps = ( name: string ) => {
	return {
		onChange: ( isChecked: boolean ) => {
			recordEvent( `product_checkbox_${ name }`, {
				checked: isChecked,
			} );
		},
	};
};

export const getInputControlProps = ( {
	className,
	context,
	onBlur,
	onChange,
	value = '',
	help,
}: gettersProps ) => {
	if ( ! context ) {
		return;
	}
	const { formatAmount, getCurrencyConfig } = context;
	const { decimalSeparator, symbol, symbolPosition, thousandSeparator } =
		getCurrencyConfig();
	const currencyPosition = symbolPosition.includes( 'left' )
		? 'prefix'
		: 'suffix';

	// Cleans the value to show.
	const regex = new RegExp(
		NUMBERS_AND_ALLOWED_CHARS.replace( '%s1', decimalSeparator ).replace(
			'%s2',
			thousandSeparator
		),
		'g'
	);
	const currencyString =
		value === undefined
			? value
			: formatAmount( value ).replace( regex, '' );
	return {
		value: currencyString,
		[ currencyPosition ]: symbol,
		className: classnames( 'woocommerce-product__input', className ),
		onChange,
		onBlur,
		help,
	};
};
