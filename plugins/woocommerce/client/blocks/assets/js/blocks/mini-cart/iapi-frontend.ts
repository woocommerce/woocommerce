/**
 * External dependencies
 */
import {
	store,
	getContext,
	getConfig,
	getElement,
} from '@wordpress/interactivity';
import '@woocommerce/stores/woocommerce/cart';
import type { Store as WooCommerce } from '@woocommerce/stores/woocommerce/cart';

/**
 * Internal dependencies
 */
import setStyles from './utils/set-styles';
import {
	formatPriceWithCurrency,
	normalizeCurrencyResponse,
} from '../../../../packages/prices/utils/currency';
import { CartItem } from '../../types';

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
	displayCartPriceIncludingTax: boolean;
};

// Inject style tags for badge styles based on background colors of the document.
setStyles();

store( 'woocommerce/mini-cart', {
	state: {
		get drawerOverlayClass() {
			const { isOpen } = getContext< MiniCartContext >();
			const baseClasses =
				'wc-block-components-drawer__screen-overlay wc-block-components-drawer__screen-overlay--with-slide-out';

			return isOpen
				? `${ baseClasses } wc-block-components-drawer__screen-overlay--with-slide-in`
				: `${ baseClasses } wc-block-components-drawer__screen-overlay--is-hidden`;
		},

		get badgeIsVisible() {
			const cartHasItems = wooStoreState.totalItemsInCart > 0;
			const { productCountVisibility } = getContext< MiniCartContext >();

			return (
				productCountVisibility === 'always' ||
				( productCountVisibility === 'greater_than_zero' &&
					cartHasItems )
			);
		},

		get cartIsEmpty() {
			return wooStoreState.totalItemsInCart === 0;
		},

		get cartItemCount() {
			return wooStoreState.totalItemsInCart;
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
} );

store( 'woocommerce/mini-cart-title-items-counter-block', {
	state: {
		get itemsInCartText() {
			const { singularItemsText, pluralItemsText } = getContext< {
				singularItemsText: string;
				pluralItemsText: string;
			} >();

			const cartItemsCount = wooStoreState.totalItemsInCart;

			const template =
				cartItemsCount === 1 ? singularItemsText : pluralItemsText;

			return template.replace( '%d', cartItemsCount.toString() );
		},
	},
} );

store( 'woocommerce/mini-cart-footer-block', {
	state: {
		get formattedSubtotal(): string {
			const { displayCartPriceIncludingTax } = getContext< {
				displayCartPriceIncludingTax: boolean;
			} >( 'woocommerce/mini-cart' );

			const { currency } = getConfig( 'woocommerce' );

			const subtotal = displayCartPriceIncludingTax
				? parseInt( wooStoreState.cart.totals.total_items, 10 ) +
				  parseInt( wooStoreState.cart.totals.total_items_tax, 10 )
				: parseInt( wooStoreState.cart.totals.total_items, 10 );

			const normalizedCurrency = normalizeCurrencyResponse(
				wooStoreState.cart.totals,
				currency
			);

			return formatPriceWithCurrency( subtotal, normalizedCurrency );
		},
	},
} );

// TODO - type this store

type CartItemContext = {
	cartItem: CartItem;
};

store( 'woocommerce/mini-cart-items-block', {
	state: {
		// Intended to be used in context of a cart item in wp-each
		get reduceQuantityLabel() {
			const { reduceQuantityLabel } = getContext(
				'woocommerce/mini-cart-items-block'
			);
			const { cartItem } = getContext< CartItemContext >();

			return reduceQuantityLabel.replace( '%s', cartItem.name );
		},

		// Intended to be used in context of a cart item in wp-each
		get increaseQuantityLabel() {
			const { increaseQuantityLabel } = getContext(
				'woocommerce/mini-cart-items-block'
			);
			const { cartItem } = getContext< CartItemContext >();

			return increaseQuantityLabel.replace( '%s', cartItem.name );
		},

		// Intended to be used in context of a cart item in wp-each
		get quantityDescriptionLabel() {
			const { quantityDescriptionLabel } = getContext(
				'woocommerce/mini-cart-items-block'
			);
			const { cartItem } = getContext< CartItemContext >();

			return quantityDescriptionLabel.replace( '%s', cartItem.name );
		},

		// Intended to be used in context of a cart item in wp-each
		get removeFromCartLabel() {
			const { removeFromCartLabel } = getContext(
				'woocommerce/mini-cart-items-block'
			);
			const { cartItem } = getContext< CartItemContext >();

			return removeFromCartLabel.replace( '%s', cartItem.name );
		},

		get cartItems() {
			return wooStoreState.cart.items;
		},

		// Intended to be used in context of a cart item in wp-each
		get itemShortDescription() {
			const ctx = getContext< CartItemContext >();
			const el = getElement();

			if ( el.ref ) {
				const innerEl = el.ref.querySelector(
					'.wc-block-components-product-metadata__description'
				);

				// A workaround for the lack of dangerous set HTML directive in interactivity API
				if ( innerEl ) {
					innerEl.innerHTML = ctx.cartItem.short_description;
				}
			}
		},

		// Intended to be used in context of a cart item in wp-each
		get itemPrice(): string {
			const ctx = getContext< CartItemContext >();
			const { currency } = getConfig( 'woocommerce' );

			const normalizedCurrency = normalizeCurrencyResponse(
				wooStoreState.cart.totals,
				currency
			);

			return formatPriceWithCurrency(
				ctx.cartItem.prices.price,
				normalizedCurrency
			);
		},

		// Intended to be used in context of a cart item in wp-each
		get lineItemTotal(): string {
			const ctx = getContext< CartItemContext >();
			const { displayCartPriceIncludingTax } = getContext(
				'woocommerce/mini-cart'
			);
			const { currency } = getConfig( 'woocommerce' );

			const normalizedCurrency = normalizeCurrencyResponse(
				wooStoreState.cart.totals,
				currency
			);

			const totals = ctx.cartItem.totals;

			const totalLinePrice = displayCartPriceIncludingTax
				? parseInt( totals.line_subtotal, 10 ) +
				  parseInt( totals.line_subtotal_tax, 10 )
				: parseInt( totals.line_subtotal, 10 );

			return formatPriceWithCurrency(
				totalLinePrice,
				normalizedCurrency
			);
		},

		// Intended to be used in context of a cart item in wp-each
		get itemThumbnail(): string {
			const ctx = getContext< CartItemContext >();
			return ctx.cartItem.images[ 0 ]?.thumbnail || '';
		},
	},
} );
