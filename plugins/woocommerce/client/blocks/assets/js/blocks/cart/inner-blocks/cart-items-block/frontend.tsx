/**
 * External dependencies
 */
import { Main } from '@woocommerce/base-components/sidebar-layout';
import clsx from 'clsx';

const FrontendBlock = ( {
	children,
	className,
}: {
	children: JSX.Element;
	className: string;
} ): JSX.Element => {
	return (
		<Main className={ clsx( 'wc-block-cart__main', className ) }>
			{ children }
		</Main>
	);
};

export default FrontendBlock;
