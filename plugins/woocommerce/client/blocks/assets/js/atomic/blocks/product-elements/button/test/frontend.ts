/**
 * External dependencies
 */
import type { ProductResponseItem } from '@woocommerce/types';

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
// also calls `store()` for the unified `woocommerce` namespace and
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
// from the unified `woocommerce` store instead.
let mockGetContextCalls: Array< string | undefined >;

// The `woocommerce/add-to-cart-with-options` store's state, read only by
// `handlePressedState` to gate the pressed state on form validity.
let mockAddToCartWithOptionsState: { isFormValid?: boolean };

// Every child id passed to `findItem({ id })`, keyed to the paired cart
// line's quantity — the fixture the grouped-aggregate getter sums over.
// Absent from the map (or the map itself unset) means "no paired line",
// mirroring the real envelope's `cartItem: undefined` on no match.
let mockChildCartQuantities: Record< number, number >;

// The unified `woocommerce` store's state: `itemInContext` backs the label
// and badge reads (`.product`/`.cartItem`), and `findItem` backs the
// grouped-aggregate getter, resolved against `mockChildCartQuantities`.
// `findItemInCart` is kept as a spy purely to prove the button still never
// calls it.
let mockWooState: {
	itemInContext: {
		product: ProductResponseItem | null;
		cartItem: { quantity: number } | undefined;
	};
	findItem: jest.Mock;
	findItemInCart: jest.Mock;
};

// The `woocommerce` store's action spies. `addItem`/`refresh` are the
// shipped actions; `addCartItem`/`refreshCartItems` are kept as spies purely
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
				if ( name === 'woocommerce' ) {
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
				if ( name === 'woocommerce/add-to-cart-with-options' ) {
					return { state: mockAddToCartWithOptionsState };
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
jest.mock( '@woocommerce/stores/woocommerce', () => ( {} ), {
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
		mockAddToCartWithOptionsState = {};
		mockChildCartQuantities = {};
		mockWooState = {
			itemInContext: {
				product: null,
				cartItem: undefined,
			},
			findItem: jest.fn( ( ref?: { id?: number } ) => ( {
				cartItem:
					ref?.id !== undefined &&
					mockChildCartQuantities[ ref.id ] !== undefined
						? { quantity: mockChildCartQuantities[ ref.id ] }
						: undefined,
			} ) ),
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

	describe( 'state.groupedInCartQuantity', () => {
		it( 'sums the paired cart line quantity for every child id', () => {
			mockWooState.itemInContext.product = {
				id: 10,
				type: 'grouped',
				grouped_products: [ 1, 2, 3 ],
			} as ProductResponseItem;
			mockChildCartQuantities = { 1: 2, 3: 1 };

			const { state } = loadStore();

			expect( state.groupedInCartQuantity ).toBe( 3 );
			expect( mockWooState.findItem ).toHaveBeenCalledWith( { id: 1 } );
			expect( mockWooState.findItem ).toHaveBeenCalledWith( { id: 2 } );
			expect( mockWooState.findItem ).toHaveBeenCalledWith( { id: 3 } );
		} );

		it( 'treats a child with no paired line as zero', () => {
			mockWooState.itemInContext.product = {
				id: 10,
				type: 'grouped',
				grouped_products: [ 1, 2 ],
			} as ProductResponseItem;
			mockChildCartQuantities = { 1: 4 };

			const { state } = loadStore();

			expect( state.groupedInCartQuantity ).toBe( 4 );
		} );

		it( 'resolves to 0 when no product is in context', () => {
			mockWooState.itemInContext.product = null;

			const { state } = loadStore();

			expect( state.groupedInCartQuantity ).toBe( 0 );
		} );
	} );

	describe( 'state.addToCartText', () => {
		it( 'shows the add-to-cart label when nothing is in the cart', () => {
			mockWooState.itemInContext.product = {
				id: 1,
				type: 'simple',
			} as ProductResponseItem;

			const { state } = loadStore();

			expect( state.addToCartText ).toBe( 'Add to cart' );
		} );

		it( 'interpolates the context tempQuantity into the in-cart label during IDLE', () => {
			mockWooState.itemInContext.product = {
				id: 1,
				type: 'simple',
			} as ProductResponseItem;
			mockContext.tempQuantity = 3;

			const { state } = loadStore();

			expect( state.addToCartText ).toBe( '3 in cart' );
		} );

		it( 'reads the paired cart line instead of tempQuantity once past IDLE/SLIDE_OUT', () => {
			mockWooState.itemInContext.product = {
				id: 1,
				type: 'simple',
			} as ProductResponseItem;
			mockContext.animationStatus =
				'SLIDE-IN' as Context[ 'animationStatus' ];
			mockContext.tempQuantity = 1;
			mockWooState.itemInContext.cartItem = { quantity: 5 };

			const { state } = loadStore();

			expect( state.addToCartText ).toBe( '5 in cart' );
		} );

		it( 'renders identically for a standalone button and one nested in a form', () => {
			mockWooState.itemInContext.product = {
				id: 1,
				type: 'simple',
			} as ProductResponseItem;
			// Past IDLE/SLIDE_OUT, the label reads the live paired cart line
			// rather than the locally-tracked `tempQuantity`.
			mockContext.animationStatus =
				'SLIDE-IN' as Context[ 'animationStatus' ];
			mockWooState.itemInContext.cartItem = { quantity: 2 };

			// No `woocommerce/add-to-cart-with-options` fixture is set up:
			// the label resolves the same whether or not a form wraps the
			// button, because it never reads that namespace.
			const { state } = loadStore();

			expect( state.addToCartText ).toBe( '2 in cart' );
		} );

		it( 'never reads the add-to-cart-with-options form context or findItemInCart to compute the label', () => {
			mockWooState.itemInContext.product = {
				id: 1,
				type: 'simple',
			} as ProductResponseItem;
			mockWooState.itemInContext.cartItem = { quantity: 2 };

			const { state } = loadStore();
			mockGetContextCalls = [];
			void state.addToCartText;

			expect( mockGetContextCalls ).not.toContain(
				'woocommerce/add-to-cart-with-options'
			);
			expect( mockWooState.findItemInCart ).not.toHaveBeenCalled();
		} );

		it( 'shows the default label for a grouped product with nothing in cart', () => {
			mockWooState.itemInContext.product = {
				id: 10,
				type: 'grouped',
				grouped_products: [ 1 ],
			} as ProductResponseItem;
			mockContext.hasPressedButton = true;

			const { state } = loadStore();

			expect( state.addToCartText ).toBe( 'Add to cart' );
		} );

		it( 'shows the static in-cart label for a grouped product once pressed', () => {
			mockWooState.itemInContext.product = {
				id: 10,
				type: 'grouped',
				grouped_products: [ 1, 2 ],
			} as ProductResponseItem;
			mockChildCartQuantities = { 1: 2 };
			mockContext.hasPressedButton = true;
			mockContext.inTheCartText = 'Added to cart';

			const { state } = loadStore();

			expect( state.addToCartText ).toBe( 'Added to cart' );
		} );

		it( 'keeps the default label for a grouped product in cart until the button is pressed', () => {
			mockWooState.itemInContext.product = {
				id: 10,
				type: 'grouped',
				grouped_products: [ 1 ],
			} as ProductResponseItem;
			mockChildCartQuantities = { 1: 2 };
			mockContext.hasPressedButton = false;

			const { state } = loadStore();

			expect( state.addToCartText ).toBe( 'Add to cart' );
		} );
	} );

	describe( 'state.displayViewCart', () => {
		it( 'is false when the context flag is off, regardless of cart contents', () => {
			mockContext.displayViewCart = false;
			mockWooState.itemInContext.cartItem = { quantity: 5 };

			const { state } = loadStore();

			expect( state.displayViewCart ).toBe( false );
		} );

		it( 'is true once something is in the cart and the flag is on', () => {
			mockContext.displayViewCart = true;
			mockWooState.itemInContext.cartItem = { quantity: 1 };

			const { state } = loadStore();

			expect( state.displayViewCart ).toBe( true );
		} );

		it( 'is false when the flag is on but nothing is in the cart', () => {
			mockContext.displayViewCart = true;
			mockWooState.itemInContext.cartItem = undefined;

			const { state } = loadStore();

			expect( state.displayViewCart ).toBe( false );
		} );

		it( 'sums the grouped children when displaying a grouped product', () => {
			mockContext.displayViewCart = true;
			mockWooState.itemInContext.product = {
				id: 10,
				type: 'grouped',
				grouped_products: [ 1, 2 ],
			} as ProductResponseItem;
			mockChildCartQuantities = { 2: 1 };

			const { state } = loadStore();

			expect( state.displayViewCart ).toBe( true );
		} );
	} );

	describe( 'actions.addItem', () => {
		it( 'does nothing when no product is in context', async () => {
			mockWooState.itemInContext.product = null;

			const { actions } = loadStore();
			await runAction(
				( actions.addItem as () => Iterator< unknown > )()
			);

			expect( mockAddItem ).not.toHaveBeenCalled();
			expect( mockAddCartItem ).not.toHaveBeenCalled();
		} );

		it( 'posts the in-context product as an explicit addItem() payload, never addCartItem', async () => {
			mockWooState.itemInContext.product = {
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
			mockWooState.itemInContext.product = {
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
			mockWooState.itemInContext.product = {
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
