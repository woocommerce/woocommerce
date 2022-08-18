/**
 * External dependencies
 */
import classnames from 'classnames';
import { recordEvent } from '@woocommerce/tracks';

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
	const { symbol, symbolPosition } = getCurrencyConfig();
	const currencyPosition = symbolPosition.includes( 'left' )
		? 'prefix'
		: 'suffix';

	// Since we are showing the currency symbol in the input, we need to remove it from the value.
	const currencyString =
		value === undefined
			? value
			: formatCurrency( value ).replace( /[^0-9.,]/g, '' );
	return {
		value: currencyString,
		[ currencyPosition ]: symbol,
		className: classnames( 'woocommerce-add-product__input', className ),
		onChange,
		onBlur,
	};
};
