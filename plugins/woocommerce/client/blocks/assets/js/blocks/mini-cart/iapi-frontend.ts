/**
 * External dependencies
 */
import { store, getContext } from '@wordpress/interactivity';

store( 'woocommerce/mini-cart', {
	state: {
		get drawerOverlayClass() {
			const { isOpen } = getContext< { isOpen: boolean } >();

			return ! isOpen
				? 'wc-block-components-drawer__screen-overlay wc-block-components-drawer__screen-overlay--is-hidden'
				: 'wc-block-components-drawer__screen-overlay wc-block-components-drawer__screen-overlay';
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
	},
} );
