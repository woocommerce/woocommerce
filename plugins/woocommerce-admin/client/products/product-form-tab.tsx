/**
 * External dependencies
 */
import clsx from 'clsx';

export const ProductFormTab: React.FC< {
	disabled?: boolean;
	name: string;
	title: string;
	children: JSX.Element | JSX.Element[] | string;
} > = ( { name, children } ) => {
	const classes = clsx(
		'woocommerce-product-form-tab',
		'woocommerce-product-form-tab__' + name
	);
	return <div className={ classes }>{ children }</div>;
};
