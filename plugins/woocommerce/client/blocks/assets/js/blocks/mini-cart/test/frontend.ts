/**
 * External dependencies
 */
import type { CartItem } from '@woocommerce/types';

type MockStoreEntry = {
	state: Record< string, unknown >;
	actions: Record< string, unknown >;
	callbacks: Record< string, unknown >;
};

// `frontend.ts` registers three of its own namespaces
// (`woocommerce/mini-cart`, `woocommerce/mini-cart-products-table-block`,
// `woocommerce/mini-cart-title-items-counter-block`), some across more than
// one `store()` call. This registry merges every call for a namespace onto
// one persistent entry, mirroring the real Interactivity runtime. Named
// `mock*` (rather than e.g. `registry`) so the `jest.mock()` factory below —
// which may only close over `mock`-prefixed bindings — can reference it.
let mockRegistry: Map< string, MockStoreEntry >;

function mockGetEntry( name: string ): MockStoreEntry {
	let entry = mockRegistry.get( name );
	if ( ! entry ) {
		entry = { state: {}, actions: {}, callbacks: {} };
		mockRegistry.set( name, entry );
	}
	return entry;
}

// The row's own `woocommerce/cart` context — set by the
// `data-wp-each--cart-item` directive that iterates `state.cart.items`
// directly (see `MiniCartProductsTableBlock::render()`).
let mockCartItemContext: { cartItem: { id: number; key?: string } };

// Every `getContext()` call's namespace argument, recorded to prove the
// row's cart item is looked up via the row's own context rather than a
// namespace `findItemInCart` never reads.
let mockGetContextCalls: Array< string | undefined >;

// The `woocommerce/cart` store's state consulted by the products-table
// block: `findItem` is the proposed envelope lookup; `findItemInCart` is
// kept as a spy purely to prove the row no longer calls it.
let mockWooState: {
	findItem: jest.Mock;
	findItemInCart: jest.Mock;
};

// The `woocommerce/cart` store's action spies. `updateItem`/`removeItem` are
// the proposed actions; `addCartItem`/`removeCartItem` are kept as spies
// purely to prove the mini-cart no longer calls them.
let mockUpdateItem: jest.Mock;
let mockRemoveItem: jest.Mock;
let mockAddCartItem: jest.Mock;
let mockRemoveCartItem: jest.Mock;

jest.mock(
	'@wordpress/interactivity',
	() => ( {
		getConfig: jest.fn( () => ( {} ) ),
		getContext: jest.fn( ( namespace?: string ) => {
			mockGetContextCalls.push( namespace );
			if ( namespace === 'woocommerce/cart' ) {
				return mockCartItemContext;
			}
			return {};
		} ),
		getElement: jest.fn( () => ( { ref: null } ) ),
		useLayoutEffect: ( callback: () => void ) => callback(),
		useRef: jest.fn( ( initial: unknown ) => ( { current: initial } ) ),
		withSyncEvent: ( fn: ( ...args: unknown[] ) => unknown ) => fn,
		store: jest.fn(
			( name: string, definition?: Record< string, unknown > ) => {
				if ( name === 'woocommerce/cart' ) {
					return {
						state: mockWooState,
						actions: {
							updateItem: mockUpdateItem,
							removeItem: mockRemoveItem,
							addCartItem: mockAddCartItem,
							removeCartItem: mockRemoveCartItem,
						},
					};
				}
				const entry = mockGetEntry( name );
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
		),
	} ),
	{ virtual: true }
);

// Side-effect-only imports `frontend.ts` makes for module ordering; the
// mocked `store()` above handles the `woocommerce/cart` registration
// directly, so the real implementations must never load.
jest.mock( '@woocommerce/stores/woocommerce/cart', () => ( {} ), {
	virtual: true,
} );
jest.mock( '@woocommerce/stores/store-notices', () => ( {} ), {
	virtual: true,
} );

/**
 * Drives an Interactivity API async action generator to completion.
 *
 * Each yielded value is awaited and fed back into the generator until done,
 * mirroring how the iAPI runtime drives an async action.
 *
 * @param action The async action return value, treated as a generator.
 * @return A promise resolving once the generator finishes.
 */
async function runAction( action: unknown ): Promise< void > {
	const iterator = action as Iterator< unknown, unknown, unknown >;
	let next = iterator.next();
	while ( ! next.done ) {
		// eslint-disable-next-line no-await-in-loop
		const resolved = await next.value;
		next = iterator.next( resolved );
	}
}

/**
 * Builds a minimal resolved cart line for the row under test.
 *
 * @param overrides Partial cart-line fields to override the defaults.
 * @return A cart line suitable as `findItem`'s resolved `cartItem`.
 */
function makeCartItem( overrides: Partial< CartItem > = {} ): CartItem {
	return {
		key: 'line-key-1',
		id: 42,
		type: 'simple',
		quantity: 2,
		quantity_limits: {
			minimum: 1,
			maximum: 10,
			multiple_of: 1,
			editable: true,
		},
		variation: [],
		item_data: [],
		...overrides,
	} as CartItem;
}

/**
 * Loads a fresh copy of the mini-cart frontend module so it registers its
 * block stores against the mocked `store()` and exposes their state/actions.
 *
 * @return The registry of every namespace the module registered.
 */
function loadModule(): Map< string, MockStoreEntry > {
	mockRegistry = new Map();
	jest.isolateModules( () => require( '../frontend' ) );
	return mockRegistry;
}

describe( 'Mini-Cart frontend store', () => {
	beforeEach( () => {
		mockCartItemContext = { cartItem: { id: 42, key: 'line-key-1' } };
		mockGetContextCalls = [];
		mockWooState = {
			findItem: jest.fn( () => ( {
				cartItem: makeCartItem(),
				draft: undefined,
				isInCart: true,
			} ) ),
			findItemInCart: jest.fn(),
		};
		mockUpdateItem = jest.fn( () => Promise.resolve() );
		mockRemoveItem = jest.fn( () => Promise.resolve() );
		mockAddCartItem = jest.fn( () => Promise.resolve() );
		mockRemoveCartItem = jest.fn( () => Promise.resolve() );
	} );

	afterEach( () => {
		jest.clearAllMocks();
	} );

	describe( 'state.cartItem', () => {
		it( 'resolves the row via findItem, never findItemInCart', () => {
			const entries = loadModule();
			const { state } = entries.get(
				'woocommerce/mini-cart-products-table-block'
			) as MockStoreEntry;

			const cartItem = state.cartItem as CartItem;

			expect( mockWooState.findItem ).toHaveBeenCalledWith( {
				id: 42,
				key: 'line-key-1',
			} );
			expect( mockWooState.findItemInCart ).not.toHaveBeenCalled();
			expect( cartItem.key ).toBe( 'line-key-1' );
			expect( cartItem.quantity ).toBe( 2 );
		} );

		it( 'reads the row identity from its own woocommerce/cart context', () => {
			mockCartItemContext = { cartItem: { id: 7, key: 'other-key' } };

			const entries = loadModule();
			const { state } = entries.get(
				'woocommerce/mini-cart-products-table-block'
			) as MockStoreEntry;

			void state.cartItem;

			expect( mockGetContextCalls ).toContain( 'woocommerce/cart' );
			expect( mockWooState.findItem ).toHaveBeenCalledWith( {
				id: 7,
				key: 'other-key',
			} );
		} );

		it( 'defaults variation/item_data when findItem resolves nothing', () => {
			mockWooState.findItem.mockReturnValue( {
				cartItem: undefined,
				draft: undefined,
				isInCart: false,
			} );

			const entries = loadModule();
			const { state } = entries.get(
				'woocommerce/mini-cart-products-table-block'
			) as MockStoreEntry;

			const cartItem = state.cartItem as CartItem;

			expect( cartItem.variation ).toEqual( [] );
			expect( cartItem.item_data ).toEqual( [] );
		} );
	} );

	describe( 'actions.changeQuantity', () => {
		it( 'calls updateItem with the row key and its current quantity', async () => {
			mockWooState.findItem.mockReturnValue( {
				cartItem: makeCartItem( { quantity: 5 } ),
				draft: undefined,
				isInCart: true,
			} );

			const entries = loadModule();
			const { actions } = entries.get(
				'woocommerce/mini-cart-products-table-block'
			) as MockStoreEntry;

			await runAction(
				( actions.changeQuantity as () => Iterator< unknown > )()
			);

			expect( mockUpdateItem ).toHaveBeenCalledTimes( 1 );
			expect( mockUpdateItem ).toHaveBeenCalledWith( {
				key: 'line-key-1',
				quantity: 5,
			} );
			expect( mockAddCartItem ).not.toHaveBeenCalled();
		} );
	} );

	describe( 'actions.incrementQuantity', () => {
		it( 'steps the quantity up by multiple_of via updateItem', async () => {
			mockWooState.findItem.mockReturnValue( {
				cartItem: makeCartItem( {
					quantity: 2,
					quantity_limits: {
						minimum: 1,
						maximum: 10,
						multiple_of: 3,
						editable: true,
					},
				} ),
				draft: undefined,
				isInCart: true,
			} );

			const entries = loadModule();
			const { actions } = entries.get(
				'woocommerce/mini-cart-products-table-block'
			) as MockStoreEntry;

			await runAction(
				( actions.incrementQuantity as () => Iterator< unknown > )()
			);

			expect( mockUpdateItem ).toHaveBeenCalledWith( {
				key: 'line-key-1',
				quantity: 5,
			} );
			expect( mockAddCartItem ).not.toHaveBeenCalled();
		} );
	} );

	describe( 'actions.decrementQuantity', () => {
		it( 'steps the quantity down by multiple_of via updateItem', async () => {
			mockWooState.findItem.mockReturnValue( {
				cartItem: makeCartItem( { quantity: 4 } ),
				draft: undefined,
				isInCart: true,
			} );

			const entries = loadModule();
			const { actions } = entries.get(
				'woocommerce/mini-cart-products-table-block'
			) as MockStoreEntry;

			await runAction(
				( actions.decrementQuantity as () => Iterator< unknown > )()
			);

			expect( mockUpdateItem ).toHaveBeenCalledWith( {
				key: 'line-key-1',
				quantity: 3,
			} );
			expect( mockAddCartItem ).not.toHaveBeenCalled();
		} );
	} );

	describe( 'actions.removeItemFromCart', () => {
		it( 'calls removeItem with the row key, never removeCartItem', async () => {
			const entries = loadModule();
			const { actions } = entries.get(
				'woocommerce/mini-cart-products-table-block'
			) as MockStoreEntry;

			await runAction(
				( actions.removeItemFromCart as () => Iterator< unknown > )()
			);

			expect( mockRemoveItem ).toHaveBeenCalledTimes( 1 );
			expect( mockRemoveItem ).toHaveBeenCalledWith( 'line-key-1' );
			expect( mockRemoveCartItem ).not.toHaveBeenCalled();
		} );
	} );
} );
