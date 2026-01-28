/**
 * Store part for cart-referencing Product Collections (e.g., cross-sells).
 *
 * This module extends the main product-collection store with callbacks
 * specific to cart-referencing collections. It should only be enqueued
 * when a cart-referencing Product Collection is present on the page.
 */

/**
 * External dependencies
 */
import { store } from '@wordpress/interactivity';

/**
 * Internal dependencies
 */
import type { MiniCart } from '../mini-cart/iapi-frontend';

const universalLock =
	'I acknowledge that using a private store means my plugin will inevitably break on the next store release.';

let wasMiniCartOpen = false;

/**
 * Store part with cart-referencing callbacks.
 */
const crossSellsStorePart = {
	callbacks: {
		/**
		 * Watches Mini Cart's drawer state and refreshes cart-referencing
		 * Product Collections (e.g., cross-sells) when the drawer opens.
		 * This is needed because Product Collections are SSR'd at page load,
		 * so they don't automatically update when cart items change.
		 */
		*onMiniCartOpen() {
			const { state: miniCartState } = store< MiniCart >(
				'woocommerce/mini-cart',
				{},
				{ lock: universalLock }
			);

			const isOpen = miniCartState.isOpen;
			const wasOpen = wasMiniCartOpen;
			wasMiniCartOpen = isOpen;

			// Only refresh on transition from closed to open.
			if ( ! isOpen || isOpen === wasOpen ) {
				return;
			}

			const { actions: routerActions }: typeof import( '@wordpress/interactivity-router' ) =
				yield import( '@wordpress/interactivity-router' );
			yield routerActions.navigate( window.location.href );
		},
	},
};

/**
 * Extend the product-collection store with cart-referencing callbacks.
 */
store( 'woocommerce/product-collection', crossSellsStorePart, {
	lock: universalLock,
} );
