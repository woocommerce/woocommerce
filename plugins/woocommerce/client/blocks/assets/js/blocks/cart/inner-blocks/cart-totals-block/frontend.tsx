/**
 * External dependencies
 */
import clsx from 'clsx';
import { Sidebar } from '@woocommerce/base-components/sidebar-layout';

/**
 * Internal dependencies
 */
import './style.scss';

const FrontendBlock = ( {
	children,
	className = '',
}: {
	children: JSX.Element | JSX.Element[];
	className?: string;
} ): JSX.Element => {
	return (
		<Sidebar className={ clsx( 'wc-block-cart__sidebar', className ) }>
			{ children }
		</Sidebar>
	);
};

export default FrontendBlock;
