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
import Dinero from 'dinero.js';

/**
 * Internal dependencies
 */
import setStyles from './utils/set-styles';
import {
	formatPriceWithCurrency,
	normalizeCurrencyResponse,
} from '../../../../packages/prices/utils/currency';
import { CartItem, Currency } from '../../types';

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
	formattedSubtotal: string;
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
		setupOpenDrawerListener: () => void;
	};
};

// Destructure state in an empty call to the store, to ensure that state can be correctly typed.
const { state: miniCartState, callbacks } = store< MiniCart >(
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
			setupOpenDrawerListener() {
				const { addToCartBehaviour } = getConfig(
					'woocommerce/mini-cart'
				);

				if ( addToCartBehaviour === 'open_drawer' ) {
					document.body.addEventListener(
						'wc-blocks_added_to_cart',
						callbacks.openDrawer
					);
				}

				return () => {
					document.body.removeEventListener(
						'wc-blocks_added_to_cart',
						callbacks.openDrawer
					);
				};
			},

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

type CartItemContext = {
	cartItem: CartItem;
};

const { state } = store(
	'woocommerce/mini-cart-items-block',
	{
		state: {
			// As a workaround for a bug in context of wp-each we use state to find the cart item. Where we need
			// reactivity for the wp-each, use state.cartItem to get the cart item.
			get cartItem() {
				const {
					cartItem: { id },
				} = getContext< CartItemContext >();

				return wooStoreState.cart.items.find(
					( item ) => item.id === id
				) as CartItem | undefined;
			},

			get cartItems() {
				return wooStoreState.cart.items;
			},

			get currency(): Currency {
				const { currency } = getConfig( 'woocommerce' );

				return normalizeCurrencyResponse(
					wooStoreState.cart.totals,
					currency
				);
			},

			get cartItemDiscount(): string {
				const {
					cartItem: { prices },
				} = getContext< CartItemContext >();

				const regularAmountSingle = Dinero( {
					amount: parseInt( prices.raw_prices.regular_price, 10 ),
					precision: prices.raw_prices.precision,
				} );

				const purchaseAmountSingle = Dinero( {
					amount: parseInt( prices.raw_prices.price, 10 ),
					precision: prices.raw_prices.precision,
				} );

				const saleAmountSingle =
					regularAmountSingle.subtract( purchaseAmountSingle );

				const discountPrice = saleAmountSingle
					.convertPrecision( state.currency.minorUnit )
					.getAmount();

				return formatPriceWithCurrency( discountPrice, state.currency );
			},

			get lineItemDiscount(): string {
				if ( state.cartItem ) {
					const { quantity, prices } = state.cartItem;

					const regularAmountSingle = Dinero( {
						amount: parseInt( prices.raw_prices.regular_price, 10 ),
						precision: prices.raw_prices.precision,
					} );

					const purchaseAmountSingle = Dinero( {
						amount: parseInt( prices.raw_prices.price, 10 ),
						precision: prices.raw_prices.precision,
					} );

					const saleAmountLineItem = regularAmountSingle
						.subtract( purchaseAmountSingle )
						.multiply( quantity );

					const totalLineItemDiscount = saleAmountLineItem
						.convertPrecision( state.currency.minorUnit )
						.getAmount();

					return formatPriceWithCurrency(
						totalLineItemDiscount,
						state.currency
					);
				}

				return '';
			},

			get cartItemHasDiscount(): boolean {
				const { cartItem } = getContext< CartItemContext >();
				return cartItem.prices.regular_price !== cartItem.prices.price;
			},

			get cartItemMinimum(): number {
				const { cartItem } = getContext< CartItemContext >();
				return cartItem.quantity_limits.minimum;
			},

			get cartItemMaximum(): number {
				const { cartItem } = getContext< CartItemContext >();
				return cartItem.quantity_limits.maximum;
			},

			// Intended to be used in context of a cart item in wp-each
			get minimumReached(): boolean {
				if ( state.cartItem ) {
					const {
						quantity,
						quantity_limits: { minimum },
					} = state.cartItem;

					return quantity - 1 < minimum;
				}

				return false;
			},

			// Intended to be used in context of a cart item in wp-each
			get maximumReached(): boolean {
				if ( state.cartItem ) {
					const {
						quantity,
						quantity_limits: { maximum },
					} = state.cartItem;
					return quantity + 1 > maximum;
				}

				return false;
			},

			// Intended to be used in context of a cart item in wp-each
			get reduceQuantityLabel(): string {
				const { cartItem } = getContext< CartItemContext >();
				const { reduceQuantityLabel } = getConfig(
					'woocommerce/mini-cart-items-block'
				);
				return reduceQuantityLabel.replace( '%s', cartItem.name );
			},

			// Intended to be used in context of a cart item in wp-each
			get increaseQuantityLabel(): string {
				const { cartItem } = getContext< CartItemContext >();
				const { increaseQuantityLabel } = getConfig(
					'woocommerce/mini-cart-items-block'
				);

				return increaseQuantityLabel.replace( '%s', cartItem.name );
			},

			// Intended to be used in context of a cart item in wp-each
			get quantityDescriptionLabel(): string {
				const { cartItem } = getContext< CartItemContext >();
				const { quantityDescriptionLabel } = getConfig(
					'woocommerce/mini-cart-items-block'
				);

				return quantityDescriptionLabel.replace( '%s', cartItem.name );
			},

			// Intended to be used in context of a cart item in wp-each
			get removeFromCartLabel(): string {
				const { cartItem } = getContext< CartItemContext >();
				const { removeFromCartLabel } = getConfig(
					'woocommerce/mini-cart-items-block'
				);

				return removeFromCartLabel.replace( '%s', cartItem.name );
			},

			get cartItemName() {
				const { cartItem } = getContext< CartItemContext >();
				const txt = document.createElement( 'textarea' );
				txt.innerHTML = cartItem.name;
				return txt.value;
			},

			// Intended to be used in context of a cart item in wp-each
			get itemThumbnail(): string {
				const { cartItem } = getContext< CartItemContext >();
				return cartItem.images[ 0 ]?.thumbnail || '';
			},

			// Intended to be used in context of a cart item in wp-each
			itemShortDescription() {
				const el = getElement();
				const { cartItem } = getContext< CartItemContext >();

				if ( el.ref ) {
					const innerEl = el.ref.querySelector(
						'.wc-block-components-product-metadata__description'
					);

					// A workaround for the lack of dangerous set HTML directive in interactivity API
					if ( innerEl ) {
						innerEl.innerHTML = cartItem.short_description;
					}
				}
			},

			get priceWithoutDiscount(): string {
				const { cartItem } = getContext< CartItemContext >();

				return formatPriceWithCurrency(
					cartItem.prices.regular_price,
					state.currency
				);
			},

			// Intended to be used in context of a cart item in wp-each
			get itemPrice(): string {
				const { cartItem } = getContext< CartItemContext >();

				return formatPriceWithCurrency(
					cartItem.prices.price,
					state.currency
				);
			},

			// Intended to be used in context of a cart item in wp-each
			get lineItemTotal(): string {
				if ( state.cartItem ) {
					const { totals } = state.cartItem;
					const { displayCartPriceIncludingTax } = getConfig(
						'woocommerce/mini-cart'
					);
					const itemCurrency = state.currency;

					const totalLinePrice = displayCartPriceIncludingTax
						? parseInt( totals.line_subtotal, 10 ) +
						  parseInt( totals.line_subtotal_tax, 10 )
						: parseInt( totals.line_subtotal, 10 );

					return formatPriceWithCurrency(
						totalLinePrice,
						itemCurrency
					);
				}

				return '';
			},
		},

		actions: {
			overrideInvalidQuantity( e: InputEvent ) {
				const input = e.target as HTMLInputElement;
				const qty = input.value;

				const { cartItem } = getContext< CartItemContext >();
				const { minimum, maximum } = cartItem.quantity_limits;

				const quantity = parseInt( qty, 10 );

				if ( Number.isNaN( quantity ) ) {
					input.value = cartItem.quantity.toString();
					return;
				}

				let finalQuantity = quantity;

				if ( quantity < minimum ) {
					finalQuantity = minimum;
				} else if ( quantity > maximum ) {
					finalQuantity = maximum;
				}

				cartItem.quantity = finalQuantity;
			},

			*changeQuantity(): Generator< unknown, void > {
				const { cartItem } = getContext< CartItemContext >();
				const { actions } = store< WooCommerce >(
					'woocommerce',
					{},
					{ lock: universalLock }
				);

				yield actions.addCartItem( {
					id: cartItem.id,
					quantity: cartItem.quantity,
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
	},
	{ lock: true }
);

store(
	'woocommerce/mini-cart-title-items-counter-block',
	{
		state: {
			get cartItems() {
				return state.cartItems;
			},

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
