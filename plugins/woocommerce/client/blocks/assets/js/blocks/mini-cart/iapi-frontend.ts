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
	withSyncEvent,
} from '@wordpress/interactivity';
import '@woocommerce/stores/woocommerce/cart';
import '@woocommerce/stores/store-notices';
import type {
	Store as WooCommerce,
	WooCommerceConfig,
} from '@woocommerce/stores/woocommerce/cart';

/**
 * Internal dependencies
 */
import {
	formatPriceWithCurrency,
	normalizeCurrencyResponse,
} from '../../../../packages/prices/utils/currency';
import { CartItem, Currency } from '../../types';
import { translateJQueryEventToNative } from '../../base/stores/woocommerce/legacy-events';

const universalLock =
	'I acknowledge that using a private store means my plugin will inevitably break on the next store release.';

const {
	currency,
	placeholderImgSrc,
	nonOptimisticProperties = [],
} = getConfig( 'woocommerce' ) as WooCommerceConfig;
const {
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
} = getConfig( 'woocommerce/mini-cart-products-table-block' );
const { itemsInCartTextTemplate } = getConfig(
	'woocommerce/mini-cart-title-items-counter-block'
);

type ScalePriceArgs = {
	price: number;
	inputDecimals: number;
	outputDecimals?: number;
};

const scalePrice = ( {
	price,
	inputDecimals,
	outputDecimals = 0,
}: ScalePriceArgs ) => {
	const scaledPrice = price * Math.pow( 10, outputDecimals - inputDecimals );
	// Remove extra decimals.
	return Math.round( scaledPrice );
};

/**
 * Recursively traverses the DOM hierarchy to find the closest non-transparent color.
 *
 * @param element   The starting element to check.
 * @param colorType Either 'color' (text) or 'backgroundColor'.
 * @return The computed color as an RGB string, or null if not found.
 */
function getClosestColor(
	element: Element | null,
	colorType: 'color' | 'backgroundColor'
): string | null {
	if ( ! element ) {
		return null;
	}
	const color = window.getComputedStyle( element )[ colorType ];
	if ( color !== 'rgba(0, 0, 0, 0)' && color !== 'transparent' ) {
		const matches = color.match( /\d+/g );
		if ( ! matches || matches.length < 3 ) {
			return null;
		}
		const [ r, g, b ] = matches.slice( 0, 3 );
		return `rgb(${ r }, ${ g }, ${ b })`;
	}
	return getClosestColor( element.parentElement, colorType );
}

type MiniCart = {
	state: {
		isHydrated: boolean;
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
		miniCartButtonRef: HTMLElement | null;
		contentsBackgroundColor: string;
		badgeBackgroundColor: string | undefined;
		badgeTextColor: string | undefined;
		productCountColor: string;
	};
	actions: {
		openDrawer: () => void;
		closeDrawer: () => void;
		overlayCloseDrawer: ( e: MouseEvent ) => void;
		handleOverlayKeydown: ( e: KeyboardEvent ) => void;
	};
	callbacks: {
		markAsHydrated: () => void;
		setupJQueryEventBridge: () => void;
		disableScrollingOnBody: () => void;
		focusFirstElement: () => void;
	};
};

type MiniCartContext = {
	productCountVisibility: 'never' | 'always' | 'greater_than_zero';
};

type CartItemContext = {
	cartItem: CartItem;
};

type ItemData = {
	raw_attribute?: string | undefined;
	value?: string | undefined;
	display?: string;
	attribute?: string;
	hidden?: boolean | string | number;
} & ( { key: string; name?: never } | { key?: never; name: string } );

type CartItemDataAttr = {
	value: string;
	name: string;
	className: string;
	hidden: boolean;
};

type DataProperty = 'item_data' | 'variation';

const trimWords = ( html: string, maxWords = 15 ): string => {
	const words = html.trim().split( /\s+/ );
	if ( words.length <= maxWords ) {
		return html;
	}
	return words.slice( 0, maxWords ).join( ' ' ) + '…';
};

const focusableSelectors = `
	a[href],
	input:not([disabled]):not([type="hidden"]):not([aria-hidden]),
	select:not([disabled]):not([aria-hidden]),
	textarea:not([disabled]):not([aria-hidden]),
	button:not([disabled]):not([aria-hidden]),
	[contenteditable],
	[tabindex]:not([tabindex^="-"])
`;

const getFocusableElements = ( container: HTMLElement | null ) =>
	container
		? Array.from(
				container.querySelectorAll< HTMLElement >( focusableSelectors )
		  ).filter( ( el ) => el.offsetParent !== null )
		: [];

const { state: cartState, actions } = store< WooCommerce >(
	'woocommerce/cart',
	{},
	{ lock: universalLock }
);

const { state: miniCartState, actions: miniCartActions } = store< MiniCart >(
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
			isHydrated: false,
			get totalItemsInCart() {
				if ( nonOptimisticProperties.includes( 'cart.items_count' ) ) {
					return cartState.cart.items_count as number;
				}
				return cartState.cart.items.reduce< number >(
					( total, { quantity } ) => total + quantity,
					0
				);
			},

			get formattedSubtotal(): string {
				if ( ! currency ) {
					return '';
				}

				const subtotal = displayCartPriceIncludingTax
					? parseInt( cartState.cart.totals.total_items, 10 ) +
					  parseInt( cartState.cart.totals.total_items_tax, 10 )
					: parseInt( cartState.cart.totals.total_items, 10 );

				const normalizedCurrency = normalizeCurrencyResponse(
					cartState.cart.totals,
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
					parseInt( cartState.cart.totals.total_items_tax, 10 ) > 0
				);
			},

			get contentsBackgroundColor(): string {
				return (
					getComputedStyle( document.body ).backgroundColor || '#fff'
				);
			},

			get badgeBackgroundColor(): string | undefined {
				if ( state.isHydrated ) {
					if ( state.productCountColor ) {
						return state.productCountColor;
					}
					const { ref } = getElement();
					return getClosestColor( ref, 'color' ) || '#000';
				}
			},

			get badgeTextColor(): string | undefined {
				if ( state.isHydrated ) {
					const { ref } = getElement();
					return getClosestColor( ref, 'backgroundColor' ) || '#fff';
				}
			},
		},

		actions: {
			openDrawer() {
				if ( onCartClickBehaviour === 'navigate_to_checkout' ) {
					window.location.href = checkoutUrl;
					return;
				}
				const { ref } = getElement();
				state.miniCartButtonRef = ref;
				state.isOpen = true;
			},

			closeDrawer() {
				state.isOpen = false;
				state.miniCartButtonRef?.focus();
			},

			overlayCloseDrawer( e: MouseEvent ) {
				// Only close the drawer if the overlay itself was clicked.
				if ( e.target === e.currentTarget ) {
					miniCartActions.closeDrawer();
				}
			},

			handleOverlayKeydown: withSyncEvent( ( e: KeyboardEvent ) => {
				if ( state.isOpen ) {
					if ( e.key === 'Escape' ) {
						miniCartActions.closeDrawer();
					}

					// Trap focus if it is an overlay (main menu).
					if ( e.key === 'Tab' ) {
						const { ref } = getElement();
						const focusableElements = getFocusableElements( ref );
						const activeElement = ref.ownerDocument.activeElement;
						if (
							e.shiftKey &&
							activeElement === focusableElements?.[ 0 ]
						) {
							// Focus last element when shift+tab in the first one.
							e.preventDefault();
							focusableElements[
								focusableElements.length - 1
							]?.focus();
						} else if (
							! e.shiftKey &&
							activeElement ===
								focusableElements?.[
									focusableElements.length - 1
								]
						) {
							// Focus first element when tab in the last one.
							e.preventDefault();
							focusableElements?.[ 0 ]?.focus();
						}
					}
				}
			} ),
		},

		callbacks: {
			*setupJQueryEventBridge() {
				if ( ! ( 'jQuery' in window ) ) {
					return;
				}

				// Make it so we can read jQuery events triggered by WC Core elements.
				const removeJQueryAddedToCartEvent =
					translateJQueryEventToNative(
						'added_to_cart',
						'wc-blocks_added_to_cart',
						true
					);
				const removeJQueryRemovedFromCartEvent =
					translateJQueryEventToNative(
						'removed_from_cart',
						'wc-blocks_removed_from_cart',
						true
					);

				return () => {
					removeJQueryAddedToCartEvent();
					removeJQueryRemovedFromCartEvent();
				};
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

			focusFirstElement() {
				if ( state.isOpen ) {
					const { ref } = getElement();
					// Focus first element when the minicart is opened.
					getFocusableElements( ref )[ 0 ]?.focus();
				}
			},
			markAsHydrated() {
				state.isHydrated = true;
			},
		},
	},
	{ lock: universalLock }
);

/**
 * Returns the raw API value for an item_data field. Used by both innerHTML
 * callbacks and the cartItemDataAttr getter.
 */
function getItemDataRaw( field: 'name' | 'value' ): string {
	const { itemData, dataProperty } = getContext< {
		itemData: ItemData;
		dataProperty: DataProperty;
	} >();

	const dataItemAttr =
		// eslint-disable-next-line @typescript-eslint/no-use-before-define
		itemData || cartItemState.cartItem[ dataProperty ]?.[ 0 ];

	if ( ! dataItemAttr ) {
		return '';
	}

	if ( field === 'name' ) {
		return (
			dataItemAttr.key ||
			dataItemAttr.attribute ||
			dataItemAttr.name ||
			''
		);
	}

	return dataItemAttr.display || dataItemAttr.value || '';
}

const { state: cartItemState } = store(
	'woocommerce/mini-cart-products-table-block',
	{
		state: {
			// The `data-wp-each--cart-item` directive iterates
			// `woocommerce/cart::state.cart.items`, so the per-row item context
			// lives under the `woocommerce/cart` namespace. As a workaround for a
			// bug in the context of wp-each we re-read the line from cart state by
			// its key; where we need reactivity for the wp-each, use
			// `state.cartItem` to get the cart item.
			get cartItem(): CartItem {
				const {
					cartItem: { key },
				} = getContext< CartItemContext >( 'woocommerce/cart' );

				// Cart rows always operate on a server-confirmed line, so resolve
				// it by key through the shared envelope (step 1 — exact line).
				const cartItem = ( cartState.findItem( { key } ).cart ||
					{} ) as CartItem;

				cartItem.variation = cartItem.variation || [];
				cartItem.item_data = cartItem.item_data || [];

				return cartItem;
			},

			get currency(): Currency {
				return normalizeCurrencyResponse(
					cartState.cart.totals,
					currency as Currency
				);
			},

			get lineItemDiscount(): string {
				const { quantity, extensions } = cartItemState.cartItem;

				const totalLineItemDiscount =
					( cartItemState.regularAmountSingle -
						cartItemState.purchaseAmountSingle ) *
					quantity;

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
								extensions,
								arg: {
									context: 'cart',
									cartItem: cartItemState.cartItem,
									cart: cartState.cart,
								},
							}
						);

					return priceText.replace( '<price/>', price );
				}

				return price;
			},

			get cartItemHasDiscount(): boolean {
				const { raw_prices: rawPrices } = cartItemState.cartItem.prices;
				return (
					parseInt( rawPrices.regular_price, 10 ) >
					parseInt( rawPrices.price, 10 )
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
									cart: cartState.cart,
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

			get itemSrcset(): string {
				return (
					cartItemState.cartItem.images[ 0 ]?.thumbnail_srcset || ''
				);
			},

			get itemSizes(): string {
				return cartItemState.cartItem.images[ 0 ]?.thumbnail_srcset
					? '64px'
					: '';
			},

			get priceWithoutDiscount(): string {
				const { raw_prices: rawPrices } = cartItemState.cartItem.prices;
				const priceWithoutDiscount = scalePrice( {
					price: parseInt( rawPrices.regular_price, 10 ),
					inputDecimals: rawPrices.precision,
					outputDecimals: cartItemState.currency.minorUnit,
				} );

				return formatPriceWithCurrency(
					priceWithoutDiscount,
					cartItemState.currency
				);
			},

			get regularAmountSingle(): number {
				const { prices } = cartItemState.cartItem;

				return scalePrice( {
					price: parseInt( prices.raw_prices.regular_price, 10 ),
					inputDecimals: prices.raw_prices.precision,
					outputDecimals: cartItemState.currency.minorUnit,
				} );
			},

			get purchaseAmountSingle(): number {
				const { prices } = cartItemState.cartItem;

				return scalePrice( {
					price: parseInt( prices.raw_prices.price, 10 ),
					inputDecimals: prices.raw_prices.precision,
					outputDecimals: cartItemState.currency.minorUnit,
				} );
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
									cart: cartState.cart,
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
									cart: cartState.cart,
								},
							}
						);
					return priceText.split( '<price/>' )[ 1 ];
				}
				return null;
			},

			get itemPrice(): string {
				const { raw_prices: rawPrices } = cartItemState.cartItem.prices;
				const itemPrice = scalePrice( {
					price: parseInt( rawPrices.price, 10 ),
					inputDecimals: rawPrices.precision,
					outputDecimals: cartItemState.currency.minorUnit,
				} );

				return formatPriceWithCurrency(
					itemPrice,
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
									cart: cartState.cart,
								},
							}
						);

					return priceText.replace( '<price/>', price );
				}

				return price;
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
								cart: cartState.cart,
							},
					  } )
					: true;
			},

			get cartItemDataAttr(): CartItemDataAttr | null {
				const rawName = getItemDataRaw( 'name' );
				const rawValue = getItemDataRaw( 'value' );

				if ( ! rawName && ! rawValue ) {
					return null;
				}

				const nameTxt = document.createElement( 'textarea' );
				nameTxt.innerHTML = rawName;
				const valueTxt = document.createElement( 'textarea' );
				valueTxt.innerHTML = rawValue;

				const { itemData, dataProperty } = getContext< {
					itemData: ItemData;
					dataProperty: DataProperty;
				} >();
				const dataItemAttr =
					itemData || cartItemState.cartItem[ dataProperty ]?.[ 0 ];
				const hiddenValue = dataItemAttr?.hidden;

				return {
					name: nameTxt.value ? nameTxt.value + ':' : '',
					value: valueTxt.value,
					className: `wc-block-components-product-details__${ nameTxt.value
						.replace( /([a-z])([A-Z])/g, '$1-$2' )
						.replace( /<[^>]*>/g, '' )
						.replace( /[\s_&]+/g, '-' )
						.toLowerCase() }`,
					hidden:
						hiddenValue === true ||
						hiddenValue === 'true' ||
						hiddenValue === '1' ||
						hiddenValue === 1,
				};
			},

			get cartItemDataAttrHidden(): boolean {
				return (
					cartItemState.cartItemDataAttr === null ||
					!! cartItemState.cartItemDataAttr?.hidden
				);
			},

			// Used to index cart item data attributes for wp-each-key.
			get cartItemDataKey(): string {
				const { itemData, dataProperty } = getContext< {
					itemData: ItemData;
					dataProperty: DataProperty;
				} >();

				const dataItemAttr =
					itemData || cartItemState.cartItem[ dataProperty ]?.[ 0 ];

				if ( ! dataItemAttr ) {
					return '';
				}

				let name = '';
				let value = '';

				if ( dataProperty === 'variation' ) {
					// For variations use raw_attribute as name and value as value
					name = dataItemAttr.raw_attribute || '';
					value = dataItemAttr.value || '';
				} else {
					// For item_data, use key/name and display/value
					name = dataItemAttr.key || dataItemAttr.name || '';
					value = dataItemAttr.display || dataItemAttr.value || '';
				}

				return `${ name }:${ value }`;
			},

			get shouldHideProductDetails(): boolean {
				const { dataProperty } = getContext< {
					dataProperty: DataProperty;
				} >();
				return cartItemState.cartItem[ dataProperty ].length === 0;
			},

			get isLastCartItemDataAttr(): boolean {
				const { itemData, dataProperty } = getContext< {
					itemData: ItemData;
					dataProperty: DataProperty;
				} >();

				const items = cartItemState.cartItem[ dataProperty ];
				if ( ! items || items.length === 0 ) {
					return true;
				}

				// Filter out hidden items
				const visibleItems = items.filter(
					( item: ItemData ) =>
						! (
							item.hidden === true ||
							item.hidden === 'true' ||
							item.hidden === '1' ||
							// eslint-disable-next-line @typescript-eslint/no-explicit-any
							( item.hidden as any ) === 1
						)
				);

				if ( visibleItems.length === 0 ) {
					return true;
				}

				const lastItem = visibleItems[ visibleItems.length - 1 ];
				return itemData === lastItem;
			},
		},

		actions: {
			*overrideInvalidQuantity(
				e: InputEvent
			): Generator< unknown, void > {
				const input = e.target as HTMLInputElement;
				const { key, quantity: currentQuantity } =
					cartItemState.cartItem;
				const { minimum, maximum } =
					cartItemState.cartItem.quantity_limits;

				const quantity = parseInt( input.value, 10 );

				// Invalid input: reset the field to the line's current quantity
				// (direct DOM write — `state.cart` is read-only for consumers).
				if ( Number.isNaN( quantity ) ) {
					input.value = currentQuantity.toString();
					return;
				}

				let finalQuantity = quantity;

				if ( quantity < minimum ) {
					finalQuantity = minimum;
				} else if ( quantity > maximum ) {
					finalQuantity = maximum;
				}

				// Reflect the clamped value visually right away…
				input.value = finalQuantity.toString();

				// …and persist it by key when it actually changed. Never mutate
				// `state.cart` directly — go through `updateItem` (mutations
				// use an explicit key).
				if ( finalQuantity !== currentQuantity ) {
					yield actions.updateItem( {
						key,
						quantity: finalQuantity,
					} );
				}
			},

			*changeQuantity(): Generator< unknown, void > {
				// Mini-cart rows always operate on server-confirmed lines (they
				// carry a `key`), so quantity changes are `updateItem`s by key —
				// no client-side add/update inference).
				yield actions.updateItem( {
					key: cartItemState.cartItem.key,
					quantity: cartItemState.cartItem.quantity,
				} );
			},

			*removeItemFromCart(): Generator< unknown, void > {
				yield actions.removeItem( cartItemState.cartItem.key );
			},

			*incrementQuantity(): Generator< unknown, void > {
				const { multiple_of: multipleOf = 1 } =
					cartItemState.cartItem.quantity_limits;
				yield actions.updateItem( {
					key: cartItemState.cartItem.key,
					quantity: cartItemState.cartItem.quantity + multipleOf,
				} );
			},

			*decrementQuantity(): Generator< unknown, void > {
				const { multiple_of: multipleOf = 1 } =
					cartItemState.cartItem.quantity_limits;
				yield actions.updateItem( {
					key: cartItemState.cartItem.key,
					quantity: cartItemState.cartItem.quantity - multipleOf,
				} );
			},

			hideImage() {
				const context = getContext< { isImageHidden: boolean } >();
				context.isImageHidden = true;
			},
		},

		callbacks: {
			// No client-side key bridge: a cart row's each-item context —
			// `woocommerce/cart::{ cartItem }`, keyed by the
			// `data-wp-each--cart-item` directive that iterates
			// `woocommerce/cart::state.cart.items` — carries the line, and the
			// envelope ladder's step 1 accepts `cartItem.key` directly. So any
			// block placed inside a row resolves its exact line (SSR-resolvable
			// too, since the each-item context exists server-side) with no
			// `data-wp-context` slot to seed and no `data-wp-init` to fill it.
			itemShortDescription() {
				const { ref } = getElement();

				if ( ref ) {
					const innerEl = ref.querySelector(
						'.wc-block-components-product-metadata__description'
					);
					const { short_description: shortDescription, description } =
						cartItemState.cartItem;

					if ( innerEl && ( shortDescription || description ) ) {
						innerEl.innerHTML = trimWords(
							shortDescription || description
						);
					}
				}
			},

			itemDataNameInnerHTML() {
				const { ref } = getElement();
				const raw = getItemDataRaw( 'name' );
				if ( ref && raw ) {
					ref.innerHTML = trimWords( raw + ':' );
				}
			},

			itemDataValueInnerHTML() {
				const { ref } = getElement();
				const raw = getItemDataRaw( 'value' );
				if ( ref && raw ) {
					ref.innerHTML = trimWords( raw );
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
						if ( ref ) {
							ref.classList.remove(
								...previouslyAppliedClasses.current
							);
						}

						const newClassesString = applyCheckoutFilter( {
							filterName: 'cartItemClass',
							defaultValue: '',
							extensions: cartItemState.cartItem.extensions,
							arg: {
								context: 'cart',
								cartItem: cartItemState.cartItem,
								cart: cartState.cart,
							},
						} );

						// Apply new classes.
						previouslyAppliedClasses.current = newClassesString
							.split( ' ' )
							.filter( Boolean );
						if ( ref ) {
							ref.classList.add(
								...previouslyAppliedClasses.current
							);
						}
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
