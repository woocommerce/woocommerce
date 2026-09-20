/**
 * External dependencies
 */
import type { CartItem } from '@woocommerce/types';

type StoreEntry = {
	state: Record< string, unknown >;
	actions: Record< string, ( ...args: unknown[] ) => unknown >;
	callbacks: Record< string, ( ...args: unknown[] ) => unknown >;
};

type WooCommerceContext = { cartItem: { key?: string } };

type CheckoutFilterArgs = {
	filterName: string;
	defaultValue: unknown;
	extensions?: unknown;
	arg: { context: string; cartItem: unknown; cart: unknown };
};

const mockUpdateCartItem = jest.fn();
const mockRemoveCartItem = jest.fn();
const mockFindProductScope = jest.fn();
const mockApplyCheckoutFilter = jest.fn(
	( args: CheckoutFilterArgs ) => args.defaultValue
);
const mockTranslateJQueryEventToNative = jest.fn( () => jest.fn() );
const mockGetElement = jest.fn( (): { ref: HTMLElement | null } => ( {
	ref: null,
} ) );

let mockCart: { items: unknown[]; totals: Record< string, unknown > };
let mockWooContext: WooCommerceContext;
let mockDefaultContext: Record< string, unknown >;
const registry = new Map< string, StoreEntry >();

/**
 * Builds a well-formed `CartItem` fixture, the shape the Store API returns
 * for one cart line. `overrides` replaces individual top-level fields.
 *
 * @param overrides Fields to override on the default fixture.
 * @return The cart item fixture.
 */
function buildCartItem( overrides: Partial< CartItem > = {} ): CartItem {
	return {
		key: 'item-key-1',
		id: 42,
		type: 'simple',
		quantity: 2,
		catalog_visibility: 'visible',
		quantity_limits: {
			minimum: 1,
			maximum: 10,
			multiple_of: 1,
			editable: true,
		},
		name: 'A product',
		summary: '',
		short_description: '',
		description: '',
		sku: '',
		low_stock_remaining: null,
		backorders_allowed: false,
		show_backorder_badge: false,
		sold_individually: false,
		permalink: 'https://example.com/product',
		images: [],
		variation: [],
		prices: {
			currency_code: 'USD',
			currency_symbol: '$',
			currency_minor_unit: 2,
			currency_decimal_separator: '.',
			currency_thousand_separator: ',',
			currency_prefix: '$',
			currency_suffix: '',
			price: '1000',
			regular_price: '1200',
			sale_price: '1000',
			price_range: null,
			raw_prices: {
				precision: 6,
				price: '1000000',
				regular_price: '1200000',
				sale_price: '1000000',
			},
		},
		totals: {
			currency_code: 'USD',
			currency_symbol: '$',
			currency_minor_unit: 2,
			currency_decimal_separator: '.',
			currency_thousand_separator: ',',
			currency_prefix: '$',
			currency_suffix: '',
			line_subtotal: '2000',
			line_subtotal_tax: '0',
			line_total: '2000',
			line_total_tax: '0',
		},
		extensions: {},
		item_data: [],
		...overrides,
	};
}

const mockWooState = {
	get cart() {
		return mockCart;
	},
	findProductScope: ( ref: unknown ) => mockFindProductScope( ref ),
};

function getEntry( namespace: string ): StoreEntry {
	let entry = registry.get( namespace );
	if ( ! entry ) {
		entry = { state: {}, actions: {}, callbacks: {} };
		registry.set( namespace, entry );
	}
	return entry;
}

const mockStore = jest.fn(
	(
		namespace: string,
		definition?: {
			state?: Record< string, unknown >;
			actions?: Record< string, ( ...args: unknown[] ) => unknown >;
			callbacks?: Record< string, ( ...args: unknown[] ) => unknown >;
		}
	) => {
		if ( namespace === 'woocommerce' ) {
			return {
				state: mockWooState,
				actions: {
					updateCartItem: mockUpdateCartItem,
					removeCartItem: mockRemoveCartItem,
				},
			};
		}

		const entry = getEntry( namespace );
		if ( definition?.state ) {
			Object.defineProperties(
				entry.state,
				Object.getOwnPropertyDescriptors( definition.state )
			);
		}
		if ( definition?.actions ) {
			Object.assign( entry.actions, definition.actions );
		}
		if ( definition?.callbacks ) {
			Object.assign( entry.callbacks, definition.callbacks );
		}
		return entry;
	}
);

jest.mock(
	'@wordpress/interactivity',
	() => ( {
		store: mockStore,
		getContext: jest.fn( ( namespace?: string ) => {
			if ( namespace === 'woocommerce' ) {
				return mockWooContext;
			}
			return mockDefaultContext;
		} ),
		getConfig: jest.fn( ( namespace?: string ) => {
			switch ( namespace ) {
				case 'woocommerce':
					return {
						currency: {
							code: 'USD',
							decimalSeparator: '.',
							minorUnit: 2,
							prefix: '$',
							suffix: '',
							symbol: '$',
							thousandSeparator: ',',
						},
						placeholderImgSrc: '',
						nonOptimisticProperties: [],
					};
				case 'woocommerce/mini-cart':
					return {
						onCartClickBehaviour: 'open_drawer',
						checkoutUrl: '/checkout',
						displayCartPriceIncludingTax: false,
						buttonAriaLabelTemplate: 'Cart: %1$d items, %2$s',
					};
				case 'woocommerce/mini-cart-products-table-block':
					return {
						reduceQuantityLabel: 'Reduce quantity of %s',
						increaseQuantityLabel: 'Increase quantity of %s',
						quantityDescriptionLabel: 'Quantity of %s in cart',
						removeFromCartLabel: 'Remove %s from cart',
					};
				case 'woocommerce/mini-cart-title-items-counter-block':
					return { itemsInCartTextTemplate: '%d items in cart' };
				default:
					return {};
			}
		} ),
		getElement: mockGetElement,
		useLayoutEffect: ( callback: () => void ) => callback(),
		useRef: ( initial: unknown ) => ( { current: initial } ),
		withSyncEvent: ( action: unknown ) => action,
	} ),
	{ virtual: true }
);

jest.mock( '@woocommerce/stores/woocommerce', () => ( {} ) );
jest.mock( '@woocommerce/stores/store-notices', () => ( {} ) );
jest.mock( '../../../base/stores/woocommerce/legacy-events', () => ( {
	translateJQueryEventToNative: mockTranslateJQueryEventToNative,
} ) );

const getMiniCart = (): StoreEntry => getEntry( 'woocommerce/mini-cart' );
const getProductsTable = (): StoreEntry =>
	getEntry( 'woocommerce/mini-cart-products-table-block' );

const runGenerator = async ( iterator: Generator ) => {
	let result = iterator.next();
	while ( ! result.done ) {
		await result.value;
		result = iterator.next();
	}
};

describe( 'Mini-Cart interactivity store', () => {
	beforeEach( () => {
		jest.resetModules();
		jest.clearAllMocks();
		registry.clear();

		mockCart = { items: [], totals: {} };
		mockWooContext = { cartItem: { key: 'item-key-1' } };
		mockDefaultContext = {};
		mockFindProductScope.mockImplementation( () => ( {
			cartItem: buildCartItem(),
		} ) );
		mockTranslateJQueryEventToNative.mockImplementation( () => jest.fn() );
		mockGetElement.mockReturnValue( { ref: null } );

		( window as unknown as { wc: unknown } ).wc = {
			blocksCheckout: { applyCheckoutFilter: mockApplyCheckoutFilter },
		};

		jest.isolateModules( () => {
			jest.requireActual( '../frontend' );
		} );
	} );

	describe( 'resolving a row by its cartItemKey', () => {
		it( "pairs a row's line through the unified store's product scope, not a direct cart-line lookup", () => {
			mockWooContext = { cartItem: { key: 'item-key-42' } };
			const cartItem = buildCartItem( { key: 'item-key-42' } );
			mockFindProductScope.mockReturnValue( { cartItem } );

			expect( getProductsTable().state.cartItem ).toEqual( cartItem );
			expect( mockFindProductScope ).toHaveBeenCalledWith( {
				cartItemKey: 'item-key-42',
			} );
		} );

		it( 'defaults variation and item_data to empty arrays when the scope resolves no line', () => {
			mockFindProductScope.mockReturnValue( { cartItem: null } );

			expect( getProductsTable().state.cartItem ).toEqual(
				expect.objectContaining( { variation: [], item_data: [] } )
			);
		} );
	} );

	describe( 'quantity change', () => {
		it( "posts the row's absolute quantity through updateCartItem, not the keyed addCartItem form", async () => {
			mockFindProductScope.mockReturnValue( {
				cartItem: buildCartItem( { key: 'item-key-1', quantity: 5 } ),
			} );

			await runGenerator(
				getProductsTable().actions.changeQuantity() as Generator
			);

			expect( mockUpdateCartItem ).toHaveBeenCalledWith( {
				key: 'item-key-1',
				quantity: 5,
			} );
		} );

		it( 'clamps a typed quantity to the minimum immediately, with no server round trip', () => {
			const cartItem = buildCartItem( {
				quantity: 5,
				quantity_limits: {
					minimum: 2,
					maximum: 10,
					multiple_of: 1,
					editable: true,
				},
			} );
			mockFindProductScope.mockReturnValue( { cartItem } );
			const input = { value: '0' } as unknown as HTMLInputElement;

			getProductsTable().actions.overrideInvalidQuantity( {
				target: input,
			} as unknown as InputEvent );

			expect( cartItem.quantity ).toBe( 2 );
			expect( mockUpdateCartItem ).not.toHaveBeenCalled();
		} );

		it( 'clamps a typed quantity to the maximum immediately, with no server round trip', () => {
			const cartItem = buildCartItem( {
				quantity: 5,
				quantity_limits: {
					minimum: 1,
					maximum: 8,
					multiple_of: 1,
					editable: true,
				},
			} );
			mockFindProductScope.mockReturnValue( { cartItem } );
			const input = { value: '20' } as unknown as HTMLInputElement;

			getProductsTable().actions.overrideInvalidQuantity( {
				target: input,
			} as unknown as InputEvent );

			expect( cartItem.quantity ).toBe( 8 );
		} );

		it( 'reverts the input to the current quantity when the typed value is not a number', () => {
			const cartItem = buildCartItem( { quantity: 4 } );
			mockFindProductScope.mockReturnValue( { cartItem } );
			const input = { value: 'abc' } as unknown as HTMLInputElement;

			getProductsTable().actions.overrideInvalidQuantity( {
				target: input,
			} as unknown as InputEvent );

			expect( input.value ).toBe( '4' );
			expect( cartItem.quantity ).toBe( 4 );
		} );

		it.each( [
			{ title: 'increment', action: 'incrementQuantity', delta: 1 },
			{ title: 'decrement', action: 'decrementQuantity', delta: -1 },
		] )(
			"$title posts the line's new absolute quantity, stepped by its multiple_of",
			async ( { action, delta } ) => {
				const cartItem = buildCartItem( {
					key: 'item-key-1',
					quantity: 6,
					quantity_limits: {
						minimum: 1,
						maximum: 20,
						multiple_of: 2,
						editable: true,
					},
				} );
				mockFindProductScope.mockReturnValue( { cartItem } );

				await runGenerator(
					getProductsTable().actions[ action ]() as Generator
				);

				expect( mockUpdateCartItem ).toHaveBeenCalledWith( {
					key: 'item-key-1',
					quantity: 6 + delta * 2,
				} );
			}
		);
	} );

	describe( 'removal', () => {
		it( "removes the row's line by key through removeCartItem", async () => {
			mockFindProductScope.mockReturnValue( {
				cartItem: buildCartItem( { key: 'item-key-9' } ),
			} );

			await runGenerator(
				getProductsTable().actions.removeItemFromCart() as Generator
			);

			expect( mockRemoveCartItem ).toHaveBeenCalledWith( 'item-key-9' );
		} );
	} );

	describe( 'the jQuery event bridge', () => {
		it( 'bridges added_to_cart and removed_from_cart to their native wc-blocks events', () => {
			( window as unknown as { jQuery: unknown } ).jQuery = {};

			(
				getMiniCart().callbacks.setupJQueryEventBridge() as Generator
			 ).next();

			expect( mockTranslateJQueryEventToNative ).toHaveBeenCalledWith(
				'added_to_cart',
				'wc-blocks_added_to_cart',
				true
			);
			expect( mockTranslateJQueryEventToNative ).toHaveBeenCalledWith(
				'removed_from_cart',
				'wc-blocks_removed_from_cart',
				true
			);

			delete ( window as unknown as { jQuery?: unknown } ).jQuery;
		} );

		it( 'sets up no bridge when jQuery is not on the page', () => {
			delete ( window as unknown as { jQuery?: unknown } ).jQuery;

			(
				getMiniCart().callbacks.setupJQueryEventBridge() as Generator
			 ).next();

			expect( mockTranslateJQueryEventToNative ).not.toHaveBeenCalled();
		} );
	} );

	describe( 'opening the drawer', () => {
		it( 'opens on request, when not configured to navigate to checkout', () => {
			const ref = {} as HTMLElement;
			mockGetElement.mockReturnValue( { ref } );

			getMiniCart().actions.openDrawer();

			expect( getMiniCart().state.isOpen ).toBe( true );
		} );
	} );

	describe( 'applyCheckoutFilter integrations still receive the cart', () => {
		const cartItem = buildCartItem( {
			name: 'Filtered product',
			extensions: { some: 'data' } as never,
		} );

		beforeEach( () => {
			mockCart = { items: [ cartItem ], totals: {} };
			mockFindProductScope.mockReturnValue( { cartItem } );
		} );

		it( 'itemName', () => {
			void getProductsTable().state.cartItemName;

			expect( mockApplyCheckoutFilter ).toHaveBeenCalledWith(
				expect.objectContaining( {
					filterName: 'itemName',
					arg: expect.objectContaining( { cart: mockCart } ),
				} )
			);
		} );

		it( 'cartItemPrice', () => {
			void getProductsTable().state.lineItemTotal;

			expect( mockApplyCheckoutFilter ).toHaveBeenCalledWith(
				expect.objectContaining( {
					filterName: 'cartItemPrice',
					arg: expect.objectContaining( { cart: mockCart } ),
				} )
			);
		} );

		it( 'subtotalPriceFormat, for both the before and after price', () => {
			void getProductsTable().state.beforeItemPrice;
			void getProductsTable().state.afterItemPrice;

			const subtotalCalls = mockApplyCheckoutFilter.mock.calls.filter(
				( [ call ] ) => call.filterName === 'subtotalPriceFormat'
			);
			expect( subtotalCalls ).toHaveLength( 2 );
			subtotalCalls.forEach( ( [ call ] ) => {
				expect( call.arg.cart ).toBe( mockCart );
			} );
		} );

		it( 'saleBadgePriceFormat', () => {
			void getProductsTable().state.lineItemDiscount;

			expect( mockApplyCheckoutFilter ).toHaveBeenCalledWith(
				expect.objectContaining( {
					filterName: 'saleBadgePriceFormat',
					arg: expect.objectContaining( { cart: mockCart } ),
				} )
			);
		} );

		it( 'showRemoveItemLink', () => {
			void getProductsTable().state.itemShowRemoveItemLink;

			expect( mockApplyCheckoutFilter ).toHaveBeenCalledWith(
				expect.objectContaining( {
					filterName: 'showRemoveItemLink',
					arg: expect.objectContaining( { cart: mockCart } ),
				} )
			);
		} );

		it( 'cartItemClass', () => {
			(
				getProductsTable().callbacks.filterCartItemClass as () => void
			 )();

			expect( mockApplyCheckoutFilter ).toHaveBeenCalledWith(
				expect.objectContaining( {
					filterName: 'cartItemClass',
					arg: expect.objectContaining( { cart: mockCart } ),
				} )
			);
		} );
	} );
} );
