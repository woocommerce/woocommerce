/**
 * External dependencies
 */
import { useStoreCart } from '@woocommerce/base-context/hooks';
import { useEffect, useRef } from '@wordpress/element';
import {
	dispatchEvent,
	hydrateInteractivityRegions,
} from '@woocommerce/base-utils';

/**
 * Internal dependencies
 */
import './style.scss';

const FrontendBlock = ( {
	children,
	className,
}: {
	children: JSX.Element;
	className: string;
} ): JSX.Element | null => {
	const { cartItems, cartIsLoading } = useStoreCart();
	const isCartEmpty = ! cartIsLoading && cartItems.length === 0;
	const containerRef = useRef< HTMLDivElement >( null );
	useEffect( () => {
		if ( ! isCartEmpty ) {
			return;
		}
		dispatchEvent( 'wc-blocks_render_blocks_frontend', {
			element: document.body.querySelector(
				'.wp-block-woocommerce-cart'
			),
		} );
		// The contents of the empty cart are mounted after the Interactivity
		// API runtime has already hydrated the page, so any Interactivity API
		// powered blocks they contain (e.g. Product Collection with its Add
		// to Cart button) must be hydrated manually.
		if ( containerRef.current ) {
			hydrateInteractivityRegions( containerRef.current );
		}
	}, [ isCartEmpty ] );
	if ( isCartEmpty ) {
		return (
			<div ref={ containerRef } className={ className }>
				{ children }
			</div>
		);
	}
	return null;
};

export default FrontendBlock;
