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
		formatCurrency: ( number: number | string ) => string;
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
		className: classnames( 'woocommerce-add-product__checkbox', className ),
		onChange: ( isChecked: boolean ) => {
			recordEvent( `add_product_checkbox_${ name }`, {
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
}: gettersProps ) => {
	return {
		value,
		className: classnames( 'woocommerce-add-product__text', className ),
		onChange,
		onBlur,
	};
};

export const getInputControlProps = ( {
	className,
	context,
	onBlur,
	onChange,
	value = '',
}: gettersProps ) => {
	if ( ! context ) {
		return;
	}
	const { formatCurrency, getCurrencyConfig } = context;
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
			: formatCurrency( value ).replace( regex, '' );
	return {
		value: currencyString,
		[ currencyPosition ]: symbol,
		className: classnames( 'woocommerce-add-product__input', className ),
		onChange,
		onBlur,
	};
};
