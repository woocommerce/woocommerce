/**
 * External dependencies
 */
import type { ProductResponseItem } from '@woocommerce/types';
import type { ProductsStoreState } from '@woocommerce/stores/woocommerce/products';

/**
 * Internal dependencies
 */
import type { Context } from '../frontend';

type MockStore = {
	state: Record< string, unknown >;
	actions: Record< string, unknown >;
	callbacks: Record< string, unknown >;
};

// The `woocommerce/product-button` store `frontend.ts` registers. `frontend.ts`
// also calls `store()` for `woocommerce/cart`, `woocommerce/products`, and
// `woocommerce/add-to-cart-with-options` purely to obtain state references —
// the mock below routes those to their own fixtures and only merges the
// local-namespace registration onto a persistent object, mirroring the real
// Interactivity runtime.
let mockRegisteredStore: MockStore | null = null;

// The shared `woocommerce/product-button` context `getContext()` returns.
let mockContext: Context;

// Every `getContext()` call's namespace argument, recorded to prove the
// button never reads another store's context (e.g. the add-to-cart-with-options
// form's) to compute its label or decide what to add — it derives everything
// from the products/cart stores instead.
let mockGetContextCalls: Array< string | undefined >;

// The `woocommerce/products` store's state, consulted one-directionally;
// tests set `productInContext` directly rather than exercising the real
// `woocommerce/products` getters.
let mockProductsState: Partial< ProductsStoreState >;

// The `woocommerce/add-to-cart-with-options` store's state, read only by
// `handlePressedState` to gate the pressed state on form validity.
let mockAddToCartWithOptionsState: { isFormValid?: boolean };

// The `woocommerce/cart` store's state: `inCartQuantity` backs the label,
// and `findItemInCart` is kept as a spy purely to prove the button no longer
// calls it.
let mockWooState: {
	inCartQuantity: number;
	findItemInCart: jest.Mock;
};

// The `woocommerce/cart` store's action spies. `addItem`/`refresh` are the
// proposed actions; `addCartItem`/`refreshCartItems` are kept as spies purely
// to prove the button no longer calls them.
let mockAddItem: jest.Mock;
let mockRefresh: jest.Mock;
let mockAddCartItem: jest.Mock;
let mockRefreshCartItems: jest.Mock;

jest.mock(
	'@wordpress/interactivity',
	() => ( {
		getContext: jest.fn( ( namespace?: string ) => {
			mockGetContextCalls.push( namespace );
			return mockContext;
		} ),
		useLayoutEffect: ( callback: () => void ) => callback(),
		store: jest.fn(
			( name: string, definition?: Record< string, unknown > ) => {
				if ( name === 'woocommerce/products' ) {
					return { state: mockProductsState };
				}
				if ( name === 'woocommerce/add-to-cart-with-options' ) {
					return { state: mockAddToCartWithOptionsState };
				}
				if ( name === 'woocommerce/cart' ) {
					return {
						state: mockWooState,
						actions: {
							addItem: mockAddItem,
							refresh: mockRefresh,
							addCartItem: mockAddCartItem,
							refreshCartItems: mockRefreshCartItems,
						},
					};
				}
				if ( ! mockRegisteredStore ) {
					mockRegisteredStore = {
						state: {},
						actions: {},
						callbacks: {},
					};
				}
				if ( definition?.state ) {
					Object.defineProperties(
						mockRegisteredStore.state,
						Object.getOwnPropertyDescriptors( definition.state )
					);
				}
				if ( definition?.actions ) {
					Object.assign(
						mockRegisteredStore.actions,
						definition.actions
					);
				}
				if ( definition?.callbacks ) {
					Object.assign(
						mockRegisteredStore.callbacks,
						definition.callbacks
					);
				}
				return mockRegisteredStore;
			}
		),
	} ),
	{ virtual: true }
);

// Side-effect store registrations `frontend.ts` imports for ordering only;
// the mocked `store()` above handles the registration calls directly.
jest.mock( '@woocommerce/stores/woocommerce/products', () => ( {} ), {
	virtual: true,
} );
jest.mock( '@woocommerce/stores/woocommerce/cart', () => ( {} ), {
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
 * Loads a fresh copy of the product button frontend module so it registers
 * its block store against the mocked `store()` and exposes its state/actions.
 *
 * @return The registered store.
 */
function loadStore(): MockStore {
	mockRegisteredStore = null;
	jest.isolateModules( () => require( '../frontend' ) );
	if ( ! mockRegisteredStore ) {
		throw new Error( 'Product Button store was not registered.' );
	}
	return mockRegisteredStore;
}

describe( 'Product Button frontend store', () => {
	beforeEach( () => {
		mockContext = {
			addToCartText: 'Add to cart',
			displayViewCart: false,
			quantityToAdd: 1,
			tempQuantity: 0,
			animationStatus: 'IDLE' as Context[ 'animationStatus' ],
			hasPressedButton: false,
			// The server pre-interpolates its own `%s` sprintf placeholder
			// with the literal `###` marker (see `ProductButton::get_in_the_cart_text()`);
			// the client's `state.addToCartText` getter is what replaces
			// `###` with the live quantity.
			inTheCartText: '### in cart',
		};
		mockGetContextCalls = [];
		mockProductsState = {};
		mockAddToCartWithOptionsState = {};
		mockWooState = {
			inCartQuantity: 0,
			findItemInCart: jest.fn(),
		};
		mockAddItem = jest.fn( () => Promise.resolve() );
		mockRefresh = jest.fn( () => Promise.resolve() );
		mockAddCartItem = jest.fn( () => Promise.resolve() );
		mockRefreshCartItems = jest.fn();
	} );

	afterEach( () => {
		jest.clearAllMocks();
	} );

	describe( 'state.addToCartText', () => {
		it( 'shows the add-to-cart label when nothing is in the cart', () => {
			mockProductsState.productInContext = {
				id: 1,
				type: 'simple',
			} as ProductResponseItem;

			const { state } = loadStore();

			expect( state.addToCartText ).toBe( 'Add to cart' );
		} );

		it( 'interpolates the context tempQuantity into the in-cart label during IDLE', () => {
			mockProductsState.productInContext = {
				id: 1,
				type: 'simple',
			} as ProductResponseItem;
			mockContext.tempQuantity = 3;

			const { state } = loadStore();

			expect( state.addToCartText ).toBe( '3 in cart' );
		} );

		it( 'reads inCartQuantity instead of tempQuantity once past IDLE/SLIDE_OUT', () => {
			mockProductsState.productInContext = {
				id: 1,
				type: 'simple',
			} as ProductResponseItem;
			mockContext.animationStatus =
				'SLIDE-IN' as Context[ 'animationStatus' ];
			mockContext.tempQuantity = 1;
			mockWooState.inCartQuantity = 5;

			const { state } = loadStore();

			expect( state.addToCartText ).toBe( '5 in cart' );
		} );

		it( 'renders identically for a standalone button and one nested in a form', () => {
			mockProductsState.productInContext = {
				id: 1,
				type: 'simple',
			} as ProductResponseItem;
			// Past IDLE/SLIDE_OUT, the label reads live `inCartQuantity`
			// rather than the locally-tracked `tempQuantity`.
			mockContext.animationStatus =
				'SLIDE-IN' as Context[ 'animationStatus' ];
			mockWooState.inCartQuantity = 2;

			// No `woocommerce/add-to-cart-with-options` fixture is set up:
			// the label resolves the same whether or not a form wraps the
			// button, because it never reads that namespace.
			const { state } = loadStore();

			expect( state.addToCartText ).toBe( '2 in cart' );
		} );

		it( 'never reads the add-to-cart-with-options form context or findItemInCart to compute the label', () => {
			mockProductsState.productInContext = {
				id: 1,
				type: 'simple',
			} as ProductResponseItem;
			mockWooState.inCartQuantity = 2;

			const { state } = loadStore();
			mockGetContextCalls = [];
			void state.addToCartText;

			expect( mockGetContextCalls ).not.toContain(
				'woocommerce/add-to-cart-with-options'
			);
			expect( mockWooState.findItemInCart ).not.toHaveBeenCalled();
		} );

		it( 'shows the default label for a grouped product with nothing in cart', () => {
			mockProductsState.productInContext = {
				id: 10,
				type: 'grouped',
			} as ProductResponseItem;
			mockWooState.inCartQuantity = 0;
			mockContext.hasPressedButton = true;

			const { state } = loadStore();

			expect( state.addToCartText ).toBe( 'Add to cart' );
		} );

		it( 'shows the static in-cart label for a grouped product once pressed', () => {
			mockProductsState.productInContext = {
				id: 10,
				type: 'grouped',
			} as ProductResponseItem;
			mockWooState.inCartQuantity = 2;
			mockContext.hasPressedButton = true;
			mockContext.inTheCartText = 'Added to cart';

			const { state } = loadStore();

			expect( state.addToCartText ).toBe( 'Added to cart' );
		} );

		it( 'keeps the default label for a grouped product in cart until the button is pressed', () => {
			mockProductsState.productInContext = {
				id: 10,
				type: 'grouped',
			} as ProductResponseItem;
			mockWooState.inCartQuantity = 2;
			mockContext.hasPressedButton = false;

			const { state } = loadStore();

			expect( state.addToCartText ).toBe( 'Add to cart' );
		} );
	} );

	describe( 'state.displayViewCart', () => {
		it( 'is false when the context flag is off, regardless of cart contents', () => {
			mockContext.displayViewCart = false;
			mockWooState.inCartQuantity = 5;

			const { state } = loadStore();

			expect( state.displayViewCart ).toBe( false );
		} );

		it( 'is true once something is in the cart and the flag is on', () => {
			mockContext.displayViewCart = true;
			mockWooState.inCartQuantity = 1;

			const { state } = loadStore();

			expect( state.displayViewCart ).toBe( true );
		} );

		it( 'is false when the flag is on but nothing is in the cart', () => {
			mockContext.displayViewCart = true;
			mockWooState.inCartQuantity = 0;

			const { state } = loadStore();

			expect( state.displayViewCart ).toBe( false );
		} );
	} );

	describe( 'actions.addItem', () => {
		it( 'does nothing when no product is in context', async () => {
			mockProductsState.productInContext = null;

			const { actions } = loadStore();
			await runAction(
				( actions.addItem as () => Iterator< unknown > )()
			);

			expect( mockAddItem ).not.toHaveBeenCalled();
			expect( mockAddCartItem ).not.toHaveBeenCalled();
		} );

		it( 'posts the in-context product as an explicit addItem() payload, never addCartItem', async () => {
			mockProductsState.productInContext = {
				id: 42,
				type: 'simple',
			} as ProductResponseItem;
			mockContext.quantityToAdd = 1;

			const { actions } = loadStore();
			await runAction(
				( actions.addItem as () => Iterator< unknown > )()
			);

			expect( mockAddItem ).toHaveBeenCalledTimes( 1 );
			expect( mockAddItem ).toHaveBeenCalledWith( {
				id: 42,
				quantity: 1,
			} );
			expect( mockAddCartItem ).not.toHaveBeenCalled();
		} );

		it( 'resolves the same product identity standalone and on a collection card, with no wrapper branching', async () => {
			mockProductsState.productInContext = {
				id: 7,
				type: 'simple',
			} as ProductResponseItem;
			mockContext.quantityToAdd = 2;

			const { actions } = loadStore();
			mockGetContextCalls = [];
			await runAction(
				( actions.addItem as () => Iterator< unknown > )()
			);

			expect( mockAddItem ).toHaveBeenCalledWith( {
				id: 7,
				quantity: 2,
			} );
			expect( mockGetContextCalls ).not.toContain(
				'woocommerce/add-to-cart-with-options'
			);
		} );

		it( 'sets displayViewCart on the context after adding', async () => {
			mockProductsState.productInContext = {
				id: 42,
				type: 'simple',
			} as ProductResponseItem;
			mockContext.displayViewCart = false;

			const { actions } = loadStore();
			await runAction(
				( actions.addItem as () => Iterator< unknown > )()
			);

			expect( mockContext.displayViewCart ).toBe( true );
		} );
	} );

	describe( 'actions.refresh', () => {
		it( 'calls the cart store refresh(), never refreshCartItems()', async () => {
			const { actions } = loadStore();
			await runAction(
				( actions.refresh as () => Iterator< unknown > )()
			);

			expect( mockRefresh ).toHaveBeenCalledTimes( 1 );
			expect( mockRefreshCartItems ).not.toHaveBeenCalled();
		} );
	} );
} );
