/**
 * External dependencies
 */
import { store, getContext } from '@wordpress/interactivity';
import '@woocommerce/stores/woocommerce/cart';
import type { Store as WooCommerce } from '@woocommerce/stores/woocommerce/cart';

/**
 * Internal dependencies
 */
import setStyles from './utils/set-styles';

const universalLock =
	'I acknowledge that using a private store means my plugin will inevitably break on the next store release.';

const { state: wooStoreState } = store< WooCommerce >(
	'woocommerce',
	{},
	{ lock: universalLock }
);

type MiniCartContext = {
	isOpen: boolean;
	productCountVisibility: 'never' | 'always' | 'greater_than_zero';
};

// Inject style tags for badge styles based on background colors of the document.
setStyles();

store( 'woocommerce/mini-cart', {
	state: {
		get drawerOverlayClass() {
			const { isOpen } = getContext< { isOpen: boolean } >();
			const baseClasses =
				'wc-block-components-drawer__screen-overlay wc-block-components-drawer__screen-overlay--with-slide-out';

			return isOpen
				? `${ baseClasses } wc-block-components-drawer__screen-overlay--with-slide-in`
				: `${ baseClasses } wc-block-components-drawer__screen-overlay--is-hidden`;
		},

		get badgeIsVisible() {
			const cartHasItems = wooStoreState.cart.items.length;
			const { productCountVisibility } = getContext< MiniCartContext >();

			return (
				productCountVisibility === 'always' ||
				( productCountVisibility === 'greater_than_zero' &&
					cartHasItems > 0 )
			);
		},

		get cartIsEmpty() {
			return (
				wooStoreState.cart.items.reduce(
					( t, { quantity } ) => t + quantity,
					0
				) === 0
			);
		},

		get cartItemCount() {
			return wooStoreState.cart.items.reduce(
				( t, { quantity } ) => t + quantity,
				0
			);
		},
	},

	callbacks: {
		openDrawer() {
			const ctx = getContext< { isOpen: boolean } >();
			ctx.isOpen = true;
		},

		closeDrawer() {
			const ctx = getContext< { isOpen: boolean } >();
			ctx.isOpen = false;
		},

		overlayCloseDrawer( e: MouseEvent ) {
			// Only close the drawer if the overlay itself was clicked.
			if ( e.target === e.currentTarget ) {
				const ctx = getContext< { isOpen: boolean } >();
				ctx.isOpen = false;
			}
		},
	},
} );
