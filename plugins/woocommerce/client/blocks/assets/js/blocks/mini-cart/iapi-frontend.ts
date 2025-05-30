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

type MiniCartState = {
	totalItemsInCart: number;
	drawerOverlayClass: string;
	badgeIsVisible: boolean;
	cartIsEmpty: boolean;
	cartItemCount: boolean;
};

type MiniCart = {
	state: MiniCartState;

	callbacks: {
		openDrawer: () => void;
		closeDrawer: () => void;
		overlayCloseDrawer: ( e: MouseEvent ) => void;
	};
};

// Destructure state in an empty call to the store, to ensure that state can be correctly typed.
const { state: miniCartState } = store< MiniCart >(
	'woocommerce/mini-cart',
	{},
	{ lock: true }
);

store< MiniCart >(
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

			get badgeIsVisible() {
				const cartHasItems = miniCartState.totalItemsInCart > 0;
				const { productCountVisibility } =
					getContext< MiniCartContext >();

				return (
					productCountVisibility === 'always' ||
					( productCountVisibility === 'greater_than_zero' &&
						cartHasItems )
				);
			},

			get cartIsEmpty() {
				return miniCartState.totalItemsInCart === 0;
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
	{ lock: universalLock }
);

store(
	'woocommerce/mini-cart-title-items-counter-block',
	{
		state: {
			get itemsInCartText() {
				const { singularItemsText, pluralItemsText } = getContext< {
					singularItemsText: string;
					pluralItemsText: string;
				} >();

				const cartItemsCount = miniCartState.totalItemsInCart;

				const template =
					cartItemsCount === 1 ? singularItemsText : pluralItemsText;

				return template.replace( '%d', cartItemsCount.toString() );
			},
		},
	},
	{ lock: true }
);
