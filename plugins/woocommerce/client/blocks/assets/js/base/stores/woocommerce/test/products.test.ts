/**
 * External dependencies
 */
import type { ProductResponseItem } from '@woocommerce/types';

/**
 * Internal dependencies
 */
import type { ProductsStore } from '../products';
import type { DraftItem, DraftKey } from '../cart';

let mockRegisteredStore: {
	state: ProductsStore[ 'state' ];
} | null = null;

let mockStoreState: ProductsStore[ 'state' ];

let mockContext: { productId?: number; variationId?: number | null } | null =
	null;

/**
 * The mocked `woocommerce/cart` namespace state `draft-internals.ts` reads
 * raw `state.draftItems` from. Kept as a single stable object per test (never
 * replaced wholesale) so a write recorded through the products-side setter
 * stays visible to a subsequent getter read within the same test — matching
 * the real Interactivity runtime returning the same persistent store object
 * across repeated `store()` calls.
 *
 * A products-only page where `cart.ts` never registered its state at all
 * (an empty stub with no `draftItems` key) is simulated by casting a bare
 * `{}` onto this variable directly, rather than by widening this type —
 * proving the getter's/setter's reads degrade rather than throw.
 */
let mockCartState: { draftItems: Record< DraftKey, DraftItem[] > };

/**
 * The mocked `getServerState( 'woocommerce/cart' )` payload's `draftSeeds`,
 * controlled per test. `undefined` simulates a page carrying no server-filed
 * seeds at all.
 */
let mockDraftSeeds: Record< DraftKey, Record< number, DraftItem > > | undefined;

/**
 * The `woocommerce/products` namespace's own state, held stable across the
 * repeated `store()` calls a single test makes: once for `products.ts`'s own
 * registration (carrying the real `state` definition — getters included),
 * and again for every lazy per-call read `draft-internals.ts` makes internally
 * (via `getProductsState()`, passing an effectively empty definition). Both
 * must resolve the identical object — never a freshly reconstructed one —
 * or a family-resolution helper's read would silently miss data the test
 * hydrated onto the first-returned reference.
 */
let mockProductsStateBase: {
	products: Record< number, ProductResponseItem >;
	productVariations: Record< number, ProductResponseItem >;
	productId: number;
	variationId: number | null;
};

const getMockStoreState = (): ProductsStore[ 'state' ] => {
	if ( mockRegisteredStore === null ) {
		throw new Error(
			'Expected woocommerce/products store to be registered.'
		);
	}
	return mockRegisteredStore.state;
};

const mockProduct = {
	id: 42,
	name: 'Test Product',
} as ProductResponseItem;

const mockVariation = {
	id: 99,
	name: 'Test Variation',
} as ProductResponseItem;

jest.mock(
	'@wordpress/interactivity',
	() => ( {
		store: jest.fn( ( namespace, definition ) => {
			if ( namespace === 'woocommerce/products' ) {
				// Simulate server-hydrated state merged with client
				// definition. Getters from definition.state are preserved,
				// and productId / variationId are added as plain values
				// (simulating wp_interactivity_state hydration). A repeated
				// call passing no `state` (draft-internals.ts's lazy reads)
				// merges nothing new and simply re-wraps the same base —
				// exactly how the real Interactivity runtime resolves an
				// already-registered namespace.
				if ( definition?.state ) {
					Object.defineProperties(
						mockProductsStateBase,
						Object.getOwnPropertyDescriptors( definition.state )
					);
				}
				mockRegisteredStore = {
					state: mockProductsStateBase as ProductsStore[ 'state' ],
				};
				return mockRegisteredStore;
			}
			if ( namespace === 'woocommerce/cart' ) {
				// A stable reference: repeated calls (draft-internals.ts
				// never caches state at module scope) must observe the same
				// object, so a write made through one call is visible to a
				// later read through another.
				return { state: mockCartState };
			}
			return {};
		} ),
		getContext: jest.fn( () => mockContext ),
		getServerState: jest.fn( () => ( { draftSeeds: mockDraftSeeds } ) ),
	} ),
	{ virtual: true }
);

describe( 'woocommerce/products store – product context derived state', () => {
	beforeEach( () => {
		mockRegisteredStore = null;
		mockContext = null;
		mockCartState = { draftItems: {} };
		mockDraftSeeds = undefined;
		mockProductsStateBase = {
			products: {},
			productVariations: {},
			productId: 0,
			variationId: null,
		};

		jest.isolateModules( () => require( '../products' ) );
		mockStoreState = getMockStoreState();

		// Hydrate products and variations after store is created.
		mockStoreState.products = { 42: mockProduct };
		mockStoreState.productVariations = { 99: mockVariation };
	} );

	it( 'has writable productId and variationId state', () => {
		mockStoreState.productId = 42;
		mockStoreState.variationId = 99;

		expect( mockStoreState.productId ).toBe( 42 );
		expect( mockStoreState.variationId ).toBe( 99 );
	} );

	describe( 'baseProductInContext', () => {
		it( 'returns the product when variationId is null', () => {
			mockStoreState.productId = 42;
			mockStoreState.variationId = null;

			expect( mockStoreState.baseProductInContext ).toBe( mockProduct );
		} );

		it( 'returns the product even when variationId is set', () => {
			mockStoreState.productId = 42;
			mockStoreState.variationId = 99;

			// product always returns the base product, never the variation.
			expect( mockStoreState.baseProductInContext ).toBe( mockProduct );
		} );

		it( 'returns null when product is not in the store', () => {
			mockStoreState.productId = 999;
			mockStoreState.variationId = null;

			expect( mockStoreState.baseProductInContext ).toBeNull();
		} );

		it( 'returns null when productId is 0', () => {
			expect( mockStoreState.baseProductInContext ).toBeNull();
		} );

		it( 'reads from block context when available', () => {
			mockStoreState.productId = 1;
			mockContext = { productId: 42 };

			expect( mockStoreState.baseProductInContext ).toBe( mockProduct );
		} );
	} );
} );
