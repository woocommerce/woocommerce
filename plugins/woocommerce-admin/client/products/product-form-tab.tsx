/**
 * External dependencies
 */
import classnames from 'classnames';

export const ProductFormTab: React.FC< {
	disabled?: boolean;
	name: string;
	title: string;
	children: JSX.Element | JSX.Element[] | string;
} > = ( { name, children } ) => {
	const classes = classnames(
		'woocommerce-product-form-tab',
		'woocommerce-product-form-tab__' + name
	);
	return <div className={ classes }>{ children }</div>;
};
