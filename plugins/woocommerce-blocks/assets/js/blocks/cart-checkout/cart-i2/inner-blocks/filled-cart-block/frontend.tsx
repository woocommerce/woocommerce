/**
 * External dependencies
 */
import classnames from 'classnames';
import { SidebarLayout } from '@woocommerce/base-components/sidebar-layout';
import { useStoreCart } from '@woocommerce/base-context/hooks';

const FrontendBlock = ( {
	children,
}: {
	children: JSX.Element;
} ): JSX.Element | null => {
	const { cartItems, cartIsLoading } = useStoreCart();
	// @todo pass attributes to inner most blocks.
	const hasDarkControls = false;
	if ( cartIsLoading || cartItems.length >= 1 ) {
		return (
			<SidebarLayout
				className={ classnames( 'wc-block-cart', {
					'has-dark-controls': hasDarkControls,
				} ) }
			>
				{ children }
			</SidebarLayout>
		);
	}
	return null;
};

export default FrontendBlock;
