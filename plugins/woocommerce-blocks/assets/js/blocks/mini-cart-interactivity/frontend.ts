/**
 * External dependencies
 */
import { getContext, store } from '@woocommerce/interactivity';
import { select, subscribe } from '@wordpress/data';

/**
 * Internal dependencies
 */
import { CART_STORE_KEY } from '../../data';

interface Store {
	state: {
		displayQuantityBadgeStyle: string;
	};
	callbacks: {
		initialize: () => void;
	};
}

store< Store >( 'woocommerce/mini-cart-interactivity', {
	state: {
		get displayQuantityBadgeStyle() {
			const context = getContext< { cartItemCount: number } >();
			return context.cartItemCount > 0 ? 'flex' : 'none';
		},
	},
	callbacks: {
		initialize: () => {
			const context = getContext< { cartItemCount: number } >();
			subscribe( () => {
				const cartData = select( CART_STORE_KEY ).getCartData();
				const isResolutionFinished =
					select( CART_STORE_KEY ).hasFinishedResolution(
						'getCartData'
					);
				if ( isResolutionFinished ) {
					context.cartItemCount = cartData.itemsCount;
				}
			} );
		},
	},
} );
