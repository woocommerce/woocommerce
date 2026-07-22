/**
 * External dependencies
 */
import type { ProductsStoreState } from '@woocommerce/stores/woocommerce/products';

/**
 * Internal dependencies
 */
import type { ProductGalleryContext, ProductGalleryConfig } from '../types';

type MockStoreEntry = {
	state: Record< string, unknown >;
	actions: Record< string, unknown >;
	callbacks: Record< string, unknown >;
};

// `frontend.ts` registers `woocommerce/products` (read-only, to resolve the
// in-context base product and the selected variation id) and its own
// `woocommerce/product-gallery` store. This registry merges every `store()`
// call for a namespace onto one persistent entry, mirroring the real
// Interactivity runtime, and `mockStoreCalls` records every namespace passed
// to `store()` so a test can prove `woocommerce/cart` is never touched.
// Named `mock*` (rather than e.g. `registry`) so the `jest.mock()` factory
// below — which may only close over `mock`-prefixed bindings — can
// reference it.
let mockRegistry: Map< string, MockStoreEntry >;
let mockStoreCalls: string[];

function mockGetEntry( name: string ): MockStoreEntry {
	let entry = mockRegistry.get( name );
	if ( ! entry ) {
		entry = { state: {}, actions: {}, callbacks: {} };
		mockRegistry.set( name, entry );
	}
	return entry;
}

// The `woocommerce/products` store's state consulted by
// `listenToProductDataChanges`: `variationId` drives change detection, and
// `baseProductInContext` — the getter this store renamed from
// `mainProductInContext` — resolves the parent product whose configured
// image set gets applied.
let mockProductsState: Partial< ProductsStoreState >;

// The gallery's own reactive context, as returned by `getContext()`.
let mockContext: ProductGalleryContext;

// The `woocommerce` shared config bag, as returned by `getConfig()` — carries
// the per-product configured image sets (`products[productId]`). This is the
// unchanged, cross-domain settings bag, not the retiring cart-store surface.
let mockConfig: ProductGalleryConfig;

jest.mock(
	'@wordpress/interactivity',
	() => ( {
		getConfig: jest.fn( () => mockConfig ),
		getContext: jest.fn( () => mockContext ),
		getElement: jest.fn( () => ( { ref: null } ) ),
		withScope: ( fn: ( ...args: unknown[] ) => unknown ) => fn,
		withSyncEvent: ( fn: ( ...args: unknown[] ) => unknown ) => fn,
		store: jest.fn(
			( name: string, definition?: Record< string, unknown > ) => {
				mockStoreCalls.push( name );
				if ( name === 'woocommerce/products' ) {
					return { state: mockProductsState };
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

// Side-effect-only import `frontend.ts` makes for module ordering; the
// mocked `store()` above handles the `woocommerce/products` registration
// directly, so the real implementation must never load (it would otherwise
// register the namespace a second time against the mock).
jest.mock( '@woocommerce/stores/woocommerce/products', () => ( {} ), {
	virtual: true,
} );

/**
 * Loads a fresh copy of the product gallery frontend module so it registers
 * its stores against the mocked `store()`.
 *
 * @return The registry of every namespace the module registered.
 */
function loadModule(): Map< string, MockStoreEntry > {
	mockRegistry = new Map();
	mockStoreCalls = [];
	jest.isolateModules( () => require( '../frontend' ) );
	return mockRegistry;
}

function makeContext(
	overrides: Partial< ProductGalleryContext > = {}
): ProductGalleryContext {
	return {
		selectedImageId: -1,
		isDialogOpen: false,
		productId: '123',
		touchStartX: 0,
		touchCurrentX: 0,
		isDragging: false,
		imageData: [],
		thumbnailsOverflow: {
			top: false,
			bottom: false,
			left: false,
			right: false,
		},
		hideNextPreviousButtons: false,
		isDisabledPrevious: false,
		isDisabledNext: false,
		ariaLabelPrevious: 'Previous',
		ariaLabelNext: 'Next',
		...overrides,
	};
}

describe( 'Product Gallery frontend store', () => {
	beforeEach( () => {
		mockContext = makeContext();
		mockConfig = {
			products: {
				123: {
					image_ids: [ 1, 2, 3 ],
					variations: {
						55: { image_ids: [ 10, 20 ], image_id: 10 },
					},
				},
			},
		};
		mockProductsState = {};
	} );

	afterEach( () => {
		jest.clearAllMocks();
	} );

	describe( 'callbacks.listenToProductDataChanges', () => {
		it( 'is a no-op on the initial render, before any variation is selected', () => {
			mockProductsState.variationId = null;

			const entries = loadModule();
			const { callbacks } = entries.get(
				'woocommerce/product-gallery'
			) as MockStoreEntry;

			( callbacks.listenToProductDataChanges as () => void )();

			expect( mockContext.imageData ).toEqual( [] );
		} );

		it( 'applies the selected variation image set, resolving the product via baseProductInContext', () => {
			const entries = loadModule();
			const { callbacks } = entries.get(
				'woocommerce/product-gallery'
			) as MockStoreEntry;
			const listenToProductDataChanges =
				callbacks.listenToProductDataChanges as () => void;

			// Initial render: no variation selected yet.
			mockProductsState.variationId = null;
			listenToProductDataChanges();

			// A variation gets selected. `baseProductInContext` — the getter
			// renamed from `mainProductInContext` — is what resolves the
			// parent product whose configured image set is applied; if it
			// resolved nothing, no image update would occur.
			mockProductsState.variationId = 55;
			mockProductsState.baseProductInContext = {
				id: 123,
			} as ProductsStoreState[ 'baseProductInContext' ];
			listenToProductDataChanges();

			expect( mockContext.imageData ).toEqual( [ 10, 20 ] );
			expect( mockContext.selectedImageId ).toBe( 10 );
		} );

		it( 'falls back to the base product images once the variation is cleared', () => {
			const entries = loadModule();
			const { callbacks } = entries.get(
				'woocommerce/product-gallery'
			) as MockStoreEntry;
			const listenToProductDataChanges =
				callbacks.listenToProductDataChanges as () => void;

			mockProductsState.variationId = null;
			listenToProductDataChanges();

			mockProductsState.variationId = 55;
			mockProductsState.baseProductInContext = {
				id: 123,
			} as ProductsStoreState[ 'baseProductInContext' ];
			listenToProductDataChanges();

			mockProductsState.variationId = null;
			listenToProductDataChanges();

			expect( mockContext.imageData ).toEqual( [ 1, 2, 3 ] );
		} );

		it( 'does nothing when baseProductInContext resolves no product', () => {
			mockProductsState.variationId = null;

			const entries = loadModule();
			const { callbacks } = entries.get(
				'woocommerce/product-gallery'
			) as MockStoreEntry;
			const listenToProductDataChanges =
				callbacks.listenToProductDataChanges as () => void;

			listenToProductDataChanges();

			mockProductsState.variationId = 55;
			mockProductsState.baseProductInContext = null;
			listenToProductDataChanges();

			expect( mockContext.imageData ).toEqual( [] );
		} );
	} );

	describe( 'store registration', () => {
		it( 'never registers or reads the woocommerce/cart store', () => {
			loadModule();

			expect( mockStoreCalls ).toEqual(
				expect.arrayContaining( [
					'woocommerce/products',
					'woocommerce/product-gallery',
				] )
			);
			expect( mockStoreCalls ).not.toContain( 'woocommerce/cart' );
		} );
	} );
} );
