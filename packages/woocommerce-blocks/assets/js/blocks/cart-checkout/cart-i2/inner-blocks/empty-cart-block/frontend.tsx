/**
 * External dependencies
 */
import { useStoreCart } from '@woocommerce/base-context/hooks';
import { useEffect } from '@wordpress/element';
import { dispatchEvent } from '@woocommerce/base-utils';

const FrontendBlock = ( {
	children,
}: {
	children: JSX.Element;
} ): JSX.Element | null => {
	const { cartItems, cartIsLoading } = useStoreCart();
	useEffect( () => {
		dispatchEvent( 'wc-blocks_render_blocks_frontend', {
			element: document.body.querySelector(
				'.wp-block-woocommerce-cart'
			),
		} );
	}, [] );
	if ( ! cartIsLoading && cartItems.length === 0 ) {
		return <>{ children }</>;
	}
	return null;
};

export default FrontendBlock;
