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

export const getCheckboxProps = ( {
	checked = false,
	className,
	name,
	onBlur,
	onChange,
}: gettersProps ) => {
	return {
		checked,
		className: classnames( 'woocommerce-product__checkbox', className ),
		onChange: ( isChecked: boolean ) => {
			recordEvent( `product_checkbox_${ name }`, {
				checked: isChecked,
			} );
			return onChange( isChecked );
		},
		onBlur,
	};
};

export const getTextControlProps = ( {
	className,
	onBlur,
	onChange,
	value = '',
	help,
}: gettersProps ) => {
	return {
		value,
		className: classnames( 'woocommerce-product__text', className ),
		onChange,
		onBlur,
		help,
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
