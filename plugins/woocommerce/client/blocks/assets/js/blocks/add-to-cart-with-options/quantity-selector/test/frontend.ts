/**
 * External dependencies
 */
import type { ProductResponseItem } from '@woocommerce/types';
import type { ProductsStoreState } from '@woocommerce/stores/woocommerce/products';
import type { Envelope } from '@woocommerce/stores/woocommerce/cart';

/**
 * Internal dependencies
 */
import type { Context } from '../frontend';
import type { AddToCartWithOptionsStore } from '../../frontend';

type MockStore = {
	state: Record< string, unknown >;
	actions: Record< string, ( ...args: unknown[] ) => void >;
	callbacks: Record< string, ( ...args: unknown[] ) => void >;
};

// The `woocommerce/add-to-cart-with-options-quantity-selector` store this
// module registers.
let mockRegisteredStore: MockStore | null = null;

// The `woocommerce/add-to-cart-with-options-quantity-selector` context
// (`allowZero`/`inputElement`) `getContext()` returns.
let mockContext: Context;

// The `woocommerce/products` store's state, consulted one-directionally;
// tests set `productInContext` directly rather than exercising the real
// `woocommerce/products` getters.
let mockProductsState: Partial< ProductsStoreState >;

// The `woocommerce/add-to-cart-with-options` store's state this module reads
// (`state.quantity`), standing in for this block instance's own
// locally-tracked quantity map.
let mockAddToCartWithOptionsState: Partial<
	AddToCartWithOptionsStore[ 'state' ]
>;

// The `woocommerce/cart` store's state this module reads (`itemInContext`).
// Setting `itemInContext.draft` simulates a draft already present in the
// current scope — whether seeded, written by this same surface, or written
// by another surface sharing the scope; setting it to an empty envelope
// simulates no draft for the in-context product (including one belonging to
// a different scope, which `itemInContext` would never surface here).
let mockCartState: { itemInContext: Envelope };

jest.mock(
	'@wordpress/interactivity',
	() => ( {
		getElement: jest.fn( () => ( { ref: null } ) ),
		getContext: jest.fn( () => mockContext ),
		store: jest.fn(
			( name: string, definition?: Record< string, unknown > ) => {
				if ( name === 'woocommerce/products' ) {
					return { state: mockProductsState };
				}
				if ( name === 'woocommerce/cart' ) {
					return { state: mockCartState };
				}
				if ( name === 'woocommerce/add-to-cart-with-options' ) {
					return {
						state: mockAddToCartWithOptionsState,
						actions: { setQuantity: jest.fn() },
					};
				}
				mockRegisteredStore = {
					state: ( definition?.state ?? {} ) as Record<
						string,
						unknown
					>,
					actions: ( definition?.actions ??
						{} ) as MockStore[ 'actions' ],
					callbacks: ( definition?.callbacks ??
						{} ) as MockStore[ 'callbacks' ],
				};
				return mockRegisteredStore;
			}
		),
	} ),
	{ virtual: true }
);

jest.mock( '@woocommerce/stores/woocommerce/cart', () => ( {} ), {
	virtual: true,
} );
jest.mock( '@woocommerce/stores/woocommerce/products', () => ( {} ), {
	virtual: true,
} );

/**
 * Loads a fresh copy of the quantity selector frontend module so it
 * registers its store against the mocked `store()`.
 *
 * @return The registered store.
 */
function loadStore(): MockStore {
	mockRegisteredStore = null;
	jest.isolateModules( () => require( '../frontend' ) );
	if ( ! mockRegisteredStore ) {
		throw new Error( 'Quantity selector store was not registered.' );
	}
	return mockRegisteredStore;
}

/**
 * Builds a minimal in-context product for the getters under test.
 *
 * @param overrides Fields to override on the default product.
 * @return A fake in-context product.
 */
function makeProduct(
	overrides: Partial< ProductResponseItem > = {}
): ProductResponseItem {
	return {
		id: 42,
		add_to_cart: {
			text: '',
			description: '',
			url: '',
			minimum: 1,
			maximum: 10,
			multiple_of: 1,
			single_text: '',
		},
		...overrides,
	} as ProductResponseItem;
}

describe( 'Quantity selector frontend store', () => {
	beforeEach( () => {
		mockContext = {};
		mockProductsState = {};
		mockAddToCartWithOptionsState = { quantity: {} };
		mockCartState = { itemInContext: {} };
	} );

	afterEach( () => {
		jest.clearAllMocks();
	} );

	describe( 'inputQuantity', () => {
		it( "displays another surface's draft update for the same scope, not this instance's stale local quantity", () => {
			mockProductsState.productInContext = makeProduct();
			mockAddToCartWithOptionsState.quantity = { 42: 1 };
			mockCartState.itemInContext = {
				draft: { id: 42, quantity: 3 },
			};

			const { state } = loadStore();

			expect( state.inputQuantity ).toBe( 3 );
		} );

		it( 'falls back to the local quantity when the scope holds no draft for the product (including a draft belonging to a different scope)', () => {
			mockProductsState.productInContext = makeProduct();
			mockAddToCartWithOptionsState.quantity = { 42: 1 };
			mockCartState.itemInContext = {}; // No draft resolved for this scope.

			const { state } = loadStore();

			expect( state.inputQuantity ).toBe( 1 );
		} );

		it( 'returns 0 when no product is in context', () => {
			mockProductsState.productInContext = null;

			const { state } = loadStore();

			expect( state.inputQuantity ).toBe( 0 );
		} );

		it( 'returns 0 when neither the draft nor the local quantity has an entry for the product', () => {
			mockProductsState.productInContext = makeProduct();
			mockAddToCartWithOptionsState.quantity = {};
			mockCartState.itemInContext = {};

			const { state } = loadStore();

			expect( state.inputQuantity ).toBe( 0 );
		} );

		it( 'prioritizes a transient local NaN over the draft, so a forced input refresh still reaches the bound value', () => {
			mockProductsState.productInContext = makeProduct();
			mockAddToCartWithOptionsState.quantity = { 42: NaN };
			mockCartState.itemInContext = {
				draft: { id: 42, quantity: 3 },
			};

			const { state } = loadStore();

			expect( Number.isNaN( state.inputQuantity as number ) ).toBe(
				true
			);
		} );
	} );

	describe( 'allowsDecrease / allowsIncrease', () => {
		it( "gate the stepper buttons on another surface's draft update, not this instance's stale local quantity", () => {
			// minimum: 1, multiple_of: 1. At the stale local quantity (1),
			// decreasing would go below the minimum (1 - 1 = 0) and is
			// disallowed; at the shared draft's quantity (3), it is allowed.
			mockProductsState.productInContext = makeProduct();
			mockAddToCartWithOptionsState.quantity = { 42: 1 };
			mockCartState.itemInContext = {
				draft: { id: 42, quantity: 3 },
			};

			const { state } = loadStore();

			expect( state.allowsDecrease ).toBe( true );
		} );

		it( 'falls back to the local quantity to gate the stepper buttons when the scope holds no draft', () => {
			mockProductsState.productInContext = makeProduct();
			mockAddToCartWithOptionsState.quantity = { 42: 1 };
			mockCartState.itemInContext = {};

			const { state } = loadStore();

			expect( state.allowsDecrease ).toBe( false );
		} );

		it( "gates the increase button on another surface's draft update", () => {
			// maximum: 10, multiple_of: 1. At the stale local quantity (1),
			// increasing stays under the maximum and would be allowed either
			// way, so drive the local value to the maximum instead — only
			// the shared draft's lower quantity (3) permits a further
			// increase.
			mockProductsState.productInContext = makeProduct( {
				add_to_cart: {
					text: '',
					description: '',
					url: '',
					minimum: 1,
					maximum: 10,
					multiple_of: 1,
					single_text: '',
				},
			} );
			mockAddToCartWithOptionsState.quantity = { 42: 10 };
			mockCartState.itemInContext = {
				draft: { id: 42, quantity: 3 },
			};

			const { state } = loadStore();

			expect( state.allowsIncrease ).toBe( true );
		} );
	} );
} );
