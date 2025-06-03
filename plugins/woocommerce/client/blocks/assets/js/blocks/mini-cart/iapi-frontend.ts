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
};

// Inject style tags for badge styles based on background colors of the document.
setStyles();

type MiniCartState = {
	totalItemsInCart: number;
	drawerOverlayClass: string;
	badgeIsVisible: boolean;
	cartIsEmpty: boolean;
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
				const { singularItemsText, pluralItemsText } = getConfig(
					'woocommerce/mini-cart-title-items-counter-block'
				);

				const cartItemsCount = miniCartState.totalItemsInCart;

				const template =
					cartItemsCount === 1 ? singularItemsText : pluralItemsText;

				return template.replace( '%d', cartItemsCount.toString() );
			},
		},
	},
	{ lock: true }
);

store(
	'woocommerce/mini-cart-footer-block',
	{
		state: {
			get formattedSubtotal(): string {
				const { displayCartPriceIncludingTax } = getConfig(
					'woocommerce/mini-cart-footer-block'
				);

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
	},
	{ lock: true }
);

type CartItemContext = {
	cartItem: CartItem;
};

store( 'woocommerce/mini-cart-items-block', {
	state: {
		// Intended to be used in context of a cart item in wp-each
		get minimumReached() {
			const { cartItem } = getContext< CartItemContext >();
			return cartItem.quantity - 1 < cartItem.quantity_limits.minimum;
		},

		// Intended to be used in context of a cart item in wp-each
		get maximumReached() {
			const { cartItem } = getContext< CartItemContext >();
			return cartItem.quantity + 1 > cartItem.quantity_limits.maximum;
		},

		// Intended to be used in context of a cart item in wp-each
		get reduceQuantityLabel() {
			const { reduceQuantityLabel } = getConfig(
				'woocommerce/mini-cart-items-block'
			);
			const { cartItem } = getContext< CartItemContext >();

			return reduceQuantityLabel.replace( '%s', cartItem.name );
		},

		// Intended to be used in context of a cart item in wp-each
		get increaseQuantityLabel() {
			const { increaseQuantityLabel } = getConfig(
				'woocommerce/mini-cart-items-block'
			);
			const { cartItem } = getContext< CartItemContext >();

			return increaseQuantityLabel.replace( '%s', cartItem.name );
		},

		// Intended to be used in context of a cart item in wp-each
		get quantityDescriptionLabel() {
			const { quantityDescriptionLabel } = getConfig(
				'woocommerce/mini-cart-items-block'
			);
			const { cartItem } = getContext< CartItemContext >();

			return quantityDescriptionLabel.replace( '%s', cartItem.name );
		},

		// Intended to be used in context of a cart item in wp-each
		get removeFromCartLabel() {
			const { removeFromCartLabel } = getConfig(
				'woocommerce/mini-cart-items-block'
			);
			const { cartItem } = getContext< CartItemContext >();

			return removeFromCartLabel.replace( '%s', cartItem.name );
		},

		get cartItems() {
			return wooStoreState.cart.items;
		},

		// Intended to be used in context of a cart item in wp-each
		itemShortDescription() {
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
			const { displayCartPriceIncludingTax } = getConfig(
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
	actions: {
		*changeQuantity( e: InputEvent ): Generator< unknown, void > {
			const input = e.target as HTMLInputElement;
			const qty = input.value;

			const { cartItem } = getContext< CartItemContext >();
			const { actions } = store< WooCommerce >(
				'woocommerce',
				{},
				{ lock: universalLock }
			);
			const { minimum, maximum } = cartItem.quantity_limits;
			const quantity = parseInt( qty );

			let finalQuantity = quantity;

			if ( quantity < minimum ) {
				input.value = `${ minimum }`;
				finalQuantity = minimum;
			} else if ( quantity > maximum ) {
				input.value = `${ maximum }`;
				finalQuantity = maximum;
			} else if ( qty === '' ) {
				input.value = `${ minimum }`;
				finalQuantity = minimum;
			}

			yield actions.addCartItem( {
				id: cartItem.id,
				quantity: finalQuantity,
			} );
		},

		*removeItemFromCart(): Generator< unknown, void > {
			const { cartItem } = getContext< CartItemContext >();
			const { actions } = store< WooCommerce >(
				'woocommerce',
				{},
				{ lock: universalLock }
			);

			yield actions.removeCartItem( cartItem.key );
		},

		*incrementQuantity(): Generator< unknown, void > {
			const { cartItem } = getContext< CartItemContext >();
			const { actions } = store< WooCommerce >(
				'woocommerce',
				{},
				{ lock: universalLock }
			);

			yield actions.addCartItem( {
				id: cartItem.id,
				quantity: cartItem.quantity + 1,
			} );
		},

		*decrementQuantity(): Generator< unknown, void > {
			const { cartItem } = getContext< CartItemContext >();
			const { actions } = store< WooCommerce >(
				'woocommerce',
				{},
				{ lock: universalLock }
			);

			yield actions.addCartItem( {
				id: cartItem.id,
				quantity: cartItem.quantity - 1,
			} );
		},
	},
} );
