/**
 * External dependencies
 */
import { store, getContext, getConfig } from '@wordpress/interactivity';
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

type MiniCart = {
	state: {
		totalItemsInCart: number;
		drawerOverlayClass: string;
		badgeIsVisible: boolean;
		cartIsEmpty: boolean;
	};
	callbacks: {
		openDrawer: () => void;
		closeDrawer: () => void;
		overlayCloseDrawer: ( e: MouseEvent ) => void;
	};
};

const { state } = store< MiniCart >(
	'woocommerce/mini-cart',
	{
		state: {
			get totalItemsInCart() {
				return wooStoreState.cart.items.reduce< number >(
					( total, { quantity } ) => total + quantity,
					0
				);
			},

			get drawerOverlayClass() {
				const { isOpen } = getContext< MiniCartContext >();
				const baseClasses =
					'wc-block-components-drawer__screen-overlay wc-block-components-drawer__screen-overlay--with-slide-out';

				return isOpen
					? `${ baseClasses } wc-block-components-drawer__screen-overlay--with-slide-in`
					: `${ baseClasses } wc-block-components-drawer__screen-overlay--is-hidden`;
			},

			get badgeIsVisible(): boolean {
				const cartHasItems = state.totalItemsInCart > 0;
				const { productCountVisibility } =
					getContext< MiniCartContext >();

				return (
					productCountVisibility === 'always' ||
					( productCountVisibility === 'greater_than_zero' &&
						cartHasItems )
				);
			},

			get cartIsEmpty(): boolean {
				return state.totalItemsInCart === 0;
			},
		},

		callbacks: {
			openDrawer() {
				const ctx = getContext< MiniCartContext >();
				ctx.isOpen = true;
			},

			closeDrawer() {
				const ctx = getContext< MiniCartContext >();
				ctx.isOpen = false;
			},

			overlayCloseDrawer( e: MouseEvent ) {
				// Only close the drawer if the overlay itself was clicked.
				if ( e.target === e.currentTarget ) {
					const ctx = getContext< MiniCartContext >();
					ctx.isOpen = false;
				}
			},
		},
	},
	{ lock: true }
);

store(
	'woocommerce/mini-cart-title-items-counter-block',
	{
		state: {
			get itemsInCartText() {
				const { singularItemsText, pluralItemsText } = getConfig(
					'woocommerce/mini-cart-title-items-counter-block'
				);

				const cartItemsCount = state.totalItemsInCart;

				const template =
					cartItemsCount === 1 ? singularItemsText : pluralItemsText;

				return template.replace( '%d', cartItemsCount.toString() );
			},
		},
	},
	{ lock: true }
);
