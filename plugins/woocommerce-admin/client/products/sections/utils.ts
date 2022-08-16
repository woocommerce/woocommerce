/**
 * External dependencies
 */
import classnames from 'classnames';
import { recordEvent } from '@woocommerce/tracks';

type gettersProps = {
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
