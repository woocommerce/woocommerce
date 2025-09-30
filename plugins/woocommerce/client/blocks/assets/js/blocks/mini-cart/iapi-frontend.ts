/**
 * External dependencies
 */
import {
	store,
	getContext,
	getConfig,
	getElement,
	useLayoutEffect,
	useRef,
} from '@wordpress/interactivity';
import '@woocommerce/stores/woocommerce/cart';
import type {
	Store as WooCommerce,
	WooCommerceConfig,
} from '@woocommerce/stores/woocommerce/cart';
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
import { translateJQueryEventToNative } from '../../base/stores/woocommerce/legacy-events';

const universalLock =
	'I acknowledge that using a private store means my plugin will inevitably break on the next store release.';

const { currency, placeholderImgSrc } = getConfig(
	'woocommerce'
) as WooCommerceConfig;
const {
	addToCartBehaviour,
	onCartClickBehaviour,
	checkoutUrl,
	displayCartPriceIncludingTax,
	buttonAriaLabelTemplate,
} = getConfig( 'woocommerce/mini-cart' );
const {
	reduceQuantityLabel,
	increaseQuantityLabel,
	quantityDescriptionLabel,
	removeFromCartLabel,
	lowInStockLabel,
} = getConfig( 'woocommerce/mini-cart-products-table-block' );
const { itemsInCartTextTemplate } = getConfig(
	'woocommerce/mini-cart-title-items-counter-block'
);

// Inject style tags for badge styles based on background colors of the document.
setStyles();

type MiniCartContext = {
	productCountVisibility: 'never' | 'always' | 'greater_than_zero';
};

type MiniCart = {
	state: {
		isOpen: boolean;
		totalItemsInCart: number;
		formattedSubtotal: string;
		drawerOverlayClass: string;
		badgeIsVisible: boolean;
		cartIsEmpty: boolean;
		drawerRole: string | null;
		drawerTabIndex: string | null;
		buttonAriaLabel: string;
		shouldShowTaxLabel: boolean;
	};
	callbacks: {
		openDrawer: () => void;
		closeDrawer: () => void;
		overlayCloseDrawer: ( e: MouseEvent ) => void;
		setupEventListeners: () => void;
		disableScrollingOnBody: () => void;
	};
};

type CartItemContext = {
	cartItem: CartItem;
};

type CartItemDataAttr = {
	name?: string;
	value?: string;
	className?: string;
	hidden?: boolean;
};

type DataProperty = 'item_data' | 'variation';

const trimWords = ( html: string, maxWords = 15 ): string => {
	const words = html.trim().split( /\s+/ );
	if ( words.length <= maxWords ) {
		return html;
	}
	return words.slice( 0, maxWords ).join( ' ' ) + '…';
};

const { state: woocommerceState, actions } = store< WooCommerce >(
	'woocommerce',
	{},
	{ lock: universalLock }
);

const { state: miniCartState, callbacks } = store< MiniCart >(
	'woocommerce/mini-cart',
	{},
	{ lock: true }
);

// Getters cannot access `state` during hydration if it is not declared
// beforehand. This will be removed once the iAPI allows this case.
const { state } = store< MiniCart >(
	'woocommerce/mini-cart',
	{},
	{ lock: universalLock }
);

store< MiniCart >(
	'woocommerce/mini-cart',
	{
		state: {
			get totalItemsInCart() {
				return woocommerceState.cart.items.reduce< number >(
					( total, { quantity } ) => total + quantity,
					0
				);
			},

			get formattedSubtotal(): string {
				if ( ! currency ) {
					return '';
				}

				const subtotal = displayCartPriceIncludingTax
					? parseInt( woocommerceState.cart.totals.total_items, 10 ) +
					  parseInt(
							woocommerceState.cart.totals.total_items_tax,
							10
					  )
					: parseInt( woocommerceState.cart.totals.total_items, 10 );

				const normalizedCurrency = normalizeCurrencyResponse(
					woocommerceState.cart.totals,
					currency
				);

				return formatPriceWithCurrency( subtotal, normalizedCurrency );
			},

			get drawerRole() {
				return state.isOpen ? 'dialog' : null;
			},

			get drawerTabIndex() {
				return state.isOpen ? '-1' : null;
			},

			get drawerOverlayClass() {
				const baseClasses =
					'wc-block-components-drawer__screen-overlay wc-block-components-drawer__screen-overlay--with-slide-out';
				return state.isOpen
					? `${ baseClasses } wc-block-components-drawer__screen-overlay--with-slide-in`
					: `${ baseClasses } wc-block-components-drawer__screen-overlay--is-hidden`;
			},

			get badgeIsVisible(): boolean {
				const cartHasItems = miniCartState.totalItemsInCart > 0;
				const { productCountVisibility } =
					getContext< MiniCartContext >();

				return (
					productCountVisibility === 'always' ||
					( productCountVisibility === 'greater_than_zero' &&
						cartHasItems )
				);
			},

			get cartIsEmpty(): boolean {
				return miniCartState.totalItemsInCart === 0;
			},

			get buttonAriaLabel(): string {
				return buttonAriaLabelTemplate
					.replace( '%d', state.totalItemsInCart )
					.replace( '%1$d', state.totalItemsInCart )
					.replace( '%2$s', state.formattedSubtotal );
			},

			get shouldShowTaxLabel(): boolean {
				return (
					parseInt(
						woocommerceState.cart.totals.total_items_tax,
						10
					) > 0
				);
			},
		},

		callbacks: {
			*setupEventListeners() {
				// eslint-disable-next-line @typescript-eslint/no-empty-function
				const noop = () => {};
				let removeJQueryAddedToCartEvent = noop;
				let removeJQueryRemovedFromCartEvent = noop;
				if ( 'jQuery' in window ) {
					// Make it so we can read jQuery events triggered by WC Core elements.
					removeJQueryAddedToCartEvent = translateJQueryEventToNative(
						'added_to_cart',
						'wc-blocks_added_to_cart'
					);
					removeJQueryRemovedFromCartEvent =
						translateJQueryEventToNative(
							'removed_from_cart',
							'wc-blocks_removed_from_cart'
						);
				}
				document.body.addEventListener(
					'wc-blocks_added_to_cart',
					actions.refreshCartItems
				);
				document.body.addEventListener(
					'wc-blocks_removed_from_cart',
					actions.refreshCartItems
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
						actions.refreshCartItems
					);
					document.body.removeEventListener(
						'wc-blocks_removed_from_cart',
						actions.refreshCartItems
					);
					document.body.removeEventListener(
						'wc-blocks_added_to_cart',
						callbacks.openDrawer
					);
					if ( 'jQuery' in window ) {
						removeJQueryAddedToCartEvent();
						removeJQueryRemovedFromCartEvent();
					}
				};
			},

			openDrawer() {
				if ( onCartClickBehaviour === 'navigate_to_checkout' ) {
					window.location.href = checkoutUrl;
					return;
				}
				state.isOpen = true;
			},

			closeDrawer() {
				state.isOpen = false;
			},

			overlayCloseDrawer( e: MouseEvent ) {
				// Only close the drawer if the overlay itself was clicked.
				if ( e.target === e.currentTarget ) {
					state.isOpen = false;
				}
			},

			disableScrollingOnBody() {
				if ( state.isOpen ) {
					Object.assign( document.body.style, {
						overflow: 'hidden',
						paddingRight:
							window.innerWidth -
							document.documentElement.clientWidth +
							'px',
					} );
				} else {
					Object.assign( document.body.style, {
						overflow: '',
						paddingRight: 0,
					} );
				}
			},
		},
	},
	{ lock: universalLock }
);

const { state: cartItemState } = store(
	'woocommerce/mini-cart-products-table-block',
	{
		state: {
			// As a workaround for a bug in context of wp-each we use state to
			// find the cart item. Where we need reactivity for the wp-each, use
			// state.cartItem to get the cart item.
			get cartItem() {
				const {
					cartItem: { id },
				} = getContext< CartItemContext >( 'woocommerce' );

				const cartItem =
					woocommerceState.cart.items.find(
						( item ) => item.id === id
					) || ( {} as CartItem );

				return {
					variation: [],
					item_data: [],
					...cartItem,
				};
			},

			get currency(): Currency {
				return normalizeCurrencyResponse(
					woocommerceState.cart.totals,
					currency
				);
			},

			get cartItemDiscount(): string {
				const { prices } = cartItemState.cartItem;

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
					.convertPrecision( cartItemState.currency.minorUnit )
					.getAmount();

				const price = formatPriceWithCurrency(
					discountPrice,
					cartItemState.currency
				);

				// TODO: Add deprecation notice urging to replace with a
				// `data-wp-text` directive or an alternative solution.
				if (
					// eslint-disable-next-line @typescript-eslint/no-explicit-any
					( window.wc as any )?.blocksCheckout?.applyCheckoutFilter
				) {
					const priceText =
						// eslint-disable-next-line @typescript-eslint/no-explicit-any
						( window.wc as any ).blocksCheckout.applyCheckoutFilter(
							{
								filterName: 'saleBadgePriceFormat',
								defaultValue: '<price/>',
								extensions: cartItemState.cartItem.extensions,
								arg: {
									context: 'cart',
									cartItem: cartItemState.cartItem,
									cart: woocommerceState.cart,
								},
							}
						);

					return priceText.replace( '<price/>', price );
				}

				return price;
			},

			get lineItemDiscount(): string {
				const { quantity, prices } = cartItemState.cartItem;

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
					.convertPrecision( cartItemState.currency.minorUnit )
					.getAmount();

				const price = formatPriceWithCurrency(
					totalLineItemDiscount,
					cartItemState.currency
				);

				// TODO: Add deprecation notice urging to replace with a
				// `data-wp-text` directive or an alternative solution.
				if (
					// eslint-disable-next-line @typescript-eslint/no-explicit-any
					( window.wc as any )?.blocksCheckout?.applyCheckoutFilter
				) {
					const priceText =
						// eslint-disable-next-line @typescript-eslint/no-explicit-any
						( window.wc as any ).blocksCheckout.applyCheckoutFilter(
							{
								filterName: 'saleBadgePriceFormat',
								defaultValue: '<price/>',
								extensions: cartItemState.cartItem.extensions,
								arg: {
									context: 'cart',
									cartItem: cartItemState.cartItem,
									cart: woocommerceState.cart,
								},
							}
						);

					return priceText.replace( '<price/>', price );
				}

				return price;
			},

			get cartItemHasDiscount(): boolean {
				return (
					cartItemState.cartItem.prices.regular_price !==
					cartItemState.cartItem.prices.price
				);
			},

			get minimumReached(): boolean {
				const {
					quantity,
					quantity_limits: { minimum, multiple_of: multipleOf = 1 },
				} = cartItemState.cartItem;

				return quantity - multipleOf < minimum;
			},

			get maximumReached(): boolean {
				const {
					quantity,
					quantity_limits: { maximum, multiple_of: multipleOf = 1 },
				} = cartItemState.cartItem;
				return quantity + multipleOf > maximum;
			},

			get reduceQuantityLabel(): string {
				return reduceQuantityLabel.replace(
					'%s',
					cartItemState.cartItemName
				);
			},

			get increaseQuantityLabel(): string {
				return increaseQuantityLabel.replace(
					'%s',
					cartItemState.cartItemName
				);
			},

			get quantityDescriptionLabel(): string {
				return quantityDescriptionLabel.replace(
					'%s',
					cartItemState.cartItemName
				);
			},

			get removeFromCartLabel(): string {
				return removeFromCartLabel.replace(
					'%s',
					cartItemState.cartItemName
				);
			},

			get cartItemName(): string {
				const txt = document.createElement( 'textarea' );
				let { name } = cartItemState.cartItem;
				if (
					// eslint-disable-next-line @typescript-eslint/no-explicit-any
					( window.wc as any )?.blocksCheckout?.applyCheckoutFilter
				) {
					name =
						// eslint-disable-next-line @typescript-eslint/no-explicit-any
						( window.wc as any ).blocksCheckout.applyCheckoutFilter(
							{
								filterName: 'itemName',
								defaultValue: name,
								extensions: cartItemState.cartItem.extensions,
								arg: {
									context: 'cart',
									cartItem: cartItemState.cartItem,
									cart: woocommerceState.cart,
								},
							}
						);
				}
				txt.innerHTML = name;
				return txt.value;
			},

			get itemThumbnail(): string {
				return (
					cartItemState.cartItem.images[ 0 ]?.thumbnail ||
					placeholderImgSrc ||
					''
				);
			},

			get priceWithoutDiscount(): string {
				return formatPriceWithCurrency(
					parseInt( cartItemState.cartItem.prices.regular_price, 10 ),
					cartItemState.currency
				);
			},

			get beforeItemPrice(): string | null {
				// TODO: Add deprecation notice urging to replace with a
				// `data-wp-text` directive or an alternative solution.
				if (
					// eslint-disable-next-line @typescript-eslint/no-explicit-any
					( window.wc as any )?.blocksCheckout?.applyCheckoutFilter
				) {
					const priceText =
						// eslint-disable-next-line @typescript-eslint/no-explicit-any
						( window.wc as any ).blocksCheckout.applyCheckoutFilter(
							{
								filterName: 'subtotalPriceFormat',
								defaultValue: '<price/>',
								extensions: cartItemState.cartItem.extensions,
								arg: {
									context: 'cart',
									cartItem: cartItemState.cartItem,
									cart: woocommerceState.cart,
								},
							}
						);
					return priceText.split( '<price/>' )[ 0 ];
				}
				return null;
			},

			get afterItemPrice(): string | null {
				// TODO: Add deprecation notice urging to replace with a
				// `data-wp-text` directive or an alternative solution.
				if (
					// eslint-disable-next-line @typescript-eslint/no-explicit-any
					( window.wc as any )?.blocksCheckout?.applyCheckoutFilter
				) {
					const priceText =
						// eslint-disable-next-line @typescript-eslint/no-explicit-any
						( window.wc as any ).blocksCheckout.applyCheckoutFilter(
							{
								filterName: 'subtotalPriceFormat',
								defaultValue: '<price/>',
								extensions: cartItemState.cartItem.extensions,
								arg: {
									context: 'cart',
									cartItem: cartItemState.cartItem,
									cart: woocommerceState.cart,
								},
							}
						);
					return priceText.split( '<price/>' )[ 1 ];
				}
				return null;
			},

			get itemPrice(): string {
				return formatPriceWithCurrency(
					parseInt( cartItemState.cartItem.prices.price, 10 ),
					cartItemState.currency
				);
			},

			get lineItemTotal(): string {
				const { totals } = cartItemState.cartItem;
				const itemCurrency = cartItemState.currency;

				const totalLinePrice = displayCartPriceIncludingTax
					? parseInt( totals.line_subtotal, 10 ) +
					  parseInt( totals.line_subtotal_tax, 10 )
					: parseInt( totals.line_subtotal, 10 );

				const price = formatPriceWithCurrency(
					totalLinePrice,
					itemCurrency
				);

				// TODO: Add deprecation notice urging to replace with a
				// `data-wp-text` directive or an alternative solution.
				if (
					// eslint-disable-next-line @typescript-eslint/no-explicit-any
					( window.wc as any )?.blocksCheckout?.applyCheckoutFilter
				) {
					const priceText =
						// eslint-disable-next-line @typescript-eslint/no-explicit-any
						( window.wc as any ).blocksCheckout.applyCheckoutFilter(
							{
								filterName: 'cartItemPrice',
								defaultValue: '<price/>',
								extensions: cartItemState.cartItem.extensions,
								arg: {
									context: 'cart',
									cartItem: cartItemState.cartItem,
									cart: woocommerceState.cart,
								},
							}
						);

					return priceText.replace( '<price/>', price );
				}

				return price;
			},

			get isLineItemTotalDiscountVisible(): boolean {
				return (
					cartItemState.cartItemHasDiscount &&
					cartItemState.cartItem.quantity > 1
				);
			},

			get isProductHiddenFromCatalog(): boolean {
				const context = getContext< { isImageHidden: boolean } >();
				const { catalog_visibility: catalogVisibility } =
					cartItemState.cartItem;
				return (
					( catalogVisibility === 'hidden' ||
						catalogVisibility === 'search' ) &&
					! context.isImageHidden
				);
			},

			get isLowInStockVisible(): boolean {
				return (
					! cartItemState.cartItem.show_backorder_badge &&
					!! cartItemState.cartItem.low_stock_remaining
				);
			},

			get lowInStockLabel(): string {
				return lowInStockLabel.replace(
					'%d',
					cartItemState.cartItem.low_stock_remaining
				);
			},

			get itemShowRemoveItemLink(): boolean {
				// eslint-disable-next-line @typescript-eslint/no-explicit-any
				return ( window.wc as any )?.blocksCheckout?.applyCheckoutFilter
					? // eslint-disable-next-line @typescript-eslint/no-explicit-any
					  ( window.wc as any ).blocksCheckout.applyCheckoutFilter( {
							filterName: 'showRemoveItemLink',
							defaultValue: true,
							extensions: cartItemState.cartItem.extensions,
							arg: {
								context: 'cart',
								cartItem: cartItemState.cartItem,
								cart: woocommerceState.cart,
							},
					  } )
					: true;
			},

			get cartItemDataAttr(): CartItemDataAttr | null {
				const { itemData, dataProperty } = getContext< {
					itemData: {
						key: string;
						attribute: string;
						name: string;
						value: string;
						hidden: string;
					};
					dataProperty: DataProperty;
				} >();

				// Use the context if it is in a loop, otherwise use the unique item if it exists.
				const dataItemAttr =
					itemData || cartItemState.cartItem[ dataProperty ]?.[ 0 ];

				if ( ! dataItemAttr ) {
					return { hidden: true };
				}

				const dataItemAttrKey =
					dataItemAttr.key ||
					dataItemAttr.attribute ||
					dataItemAttr.name;

				// Decode entities.
				const nameTxt = document.createElement( 'textarea' );
				nameTxt.innerHTML = dataItemAttrKey + ':';
				const valueTxt = document.createElement( 'textarea' );
				valueTxt.innerHTML = dataItemAttr.value;

				return {
					name: nameTxt.value,
					value: valueTxt.value,
					className: `wc-block-components-product-details__${ dataItemAttrKey
						.replace( /([a-z])([A-Z])/g, '$1-$2' )
						.replace( /[\s_]+/g, '-' )
						.toLowerCase() }`,
					hidden: dataItemAttr.hidden === '1' ? true : false,
				};
			},

			get itemDataHasMultipleAttributes(): boolean {
				const { dataProperty } = getContext< {
					dataProperty: DataProperty;
				} >();
				return cartItemState.cartItem[ dataProperty ]?.length > 1;
			},

			get shouldHideProductDetails(): boolean {
				const { dataProperty } = getContext< {
					dataProperty: DataProperty;
				} >();
				return cartItemState.cartItem[ dataProperty ].length === 0;
			},

			get shouldHideSingleProductDetails(): boolean {
				return (
					cartItemState.shouldHideProductDetails ||
					cartItemState.itemDataHasMultipleAttributes
				);
			},

			get shouldHideMultipleProductDetails(): boolean {
				return (
					cartItemState.shouldHideProductDetails ||
					! cartItemState.itemDataHasMultipleAttributes
				);
			},
		},

		actions: {
			overrideInvalidQuantity( e: InputEvent ) {
				const input = e.target as HTMLInputElement;
				const qty = input.value;

				const { minimum, maximum } =
					cartItemState.cartItem.quantity_limits;

				const quantity = parseInt( qty, 10 );

				if ( Number.isNaN( quantity ) ) {
					input.value = cartItemState.cartItem.quantity.toString();
					return;
				}

				let finalQuantity = quantity;

				if ( quantity < minimum ) {
					finalQuantity = minimum;
				} else if ( quantity > maximum ) {
					finalQuantity = maximum;
				}

				cartItemState.cartItem.quantity = finalQuantity;
			},

			*changeQuantity(): Generator< unknown, void > {
				const variation = cartItemState.cartItem.variation.map(
					( { raw_attribute: rawAttribute, ...rest } ) => ( {
						...rest,
						attribute: rawAttribute,
					} )
				);
				yield actions.addCartItem( {
					id: cartItemState.cartItem.id,
					quantity: cartItemState.cartItem.quantity,
					variation,
					type: cartItemState.cartItem.type,
				} );
			},

			*removeItemFromCart(): Generator< unknown, void > {
				yield actions.removeCartItem( cartItemState.cartItem.key );
			},

			*incrementQuantity(): Generator< unknown, void > {
				const { multiple_of: multipleOf = 1 } =
					cartItemState.cartItem.quantity_limits;
				const variation = cartItemState.cartItem.variation.map(
					( { raw_attribute: rawAttribute, ...rest } ) => ( {
						...rest,
						attribute: rawAttribute,
					} )
				);
				yield actions.addCartItem( {
					id: cartItemState.cartItem.id,
					quantity: cartItemState.cartItem.quantity + multipleOf,
					variation,
					type: cartItemState.cartItem.type,
				} );
			},

			*decrementQuantity(): Generator< unknown, void > {
				const { multiple_of: multipleOf = 1 } =
					cartItemState.cartItem.quantity_limits;
				const variation = cartItemState.cartItem.variation.map(
					( { raw_attribute: rawAttribute, ...rest } ) => ( {
						...rest,
						attribute: rawAttribute,
					} )
				);
				yield actions.addCartItem( {
					id: cartItemState.cartItem.id,
					quantity: cartItemState.cartItem.quantity - multipleOf,
					variation,
					type: cartItemState.cartItem.type,
				} );
			},

			hideImage() {
				const context = getContext< { isImageHidden: boolean } >();
				context.isImageHidden = true;
			},
		},

		callbacks: {
			itemShortDescription() {
				const { ref } = getElement();

				if ( ref ) {
					const innerEl = ref.querySelector(
						'.wc-block-components-product-metadata__description'
					);
					const { short_description: shortDescription, description } =
						cartItemState.cartItem;

					// A workaround for the lack of dangerous set HTML directive
					// in interactivity API.
					if ( innerEl && ( shortDescription || description ) ) {
						innerEl.innerHTML = trimWords(
							shortDescription || description
						);
					}
				}
			},
			filterCartItemClass() {
				// TODO: Add deprecation notice urging to replace with a `data-wp-class` directive.
				// eslint-disable-next-line @typescript-eslint/no-explicit-any
				const applyCheckoutFilter = ( window.wc as any )?.blocksCheckout
					?.applyCheckoutFilter;
				// eslint-disable-next-line react-hooks/rules-of-hooks
				const previouslyAppliedClasses = useRef< string[] >( [] );

				// eslint-disable-next-line @typescript-eslint/ban-ts-comment
				// @ts-ignore -- It must run on every render.
				// eslint-disable-next-line react-hooks/rules-of-hooks
				useLayoutEffect( () => {
					if ( applyCheckoutFilter ) {
						const { ref } = getElement();

						// Remove previously applied classes.
						ref!.classList.remove(
							...previouslyAppliedClasses.current
						);

						const newClassesString = applyCheckoutFilter( {
							filterName: 'cartItemClass',
							defaultValue: '',
							extensions: cartItemState.cartItem.extensions,
							arg: {
								context: 'cart',
								cartItem: cartItemState.cartItem,
								cart: woocommerceState.cart,
							},
						} );

						// Apply new classes.
						previouslyAppliedClasses.current = newClassesString
							.split( ' ' )
							.filter( Boolean );
						ref!.classList.add(
							...previouslyAppliedClasses.current
						);
					}
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
			get itemsInCartText() {
				const cartItemsCount = miniCartState.totalItemsInCart;

				return itemsInCartTextTemplate.replace(
					'%d',
					cartItemsCount.toString()
				);
			},
		},
	},
	{ lock: true }
);
