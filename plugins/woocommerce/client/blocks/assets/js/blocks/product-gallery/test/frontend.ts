/**
 * External dependencies
 */
import type { ProductResponseItem } from '@woocommerce/types';

/**
 * Internal dependencies
 */
import type { ProductGalleryContext, ProductGalleryConfig } from '../types';

type MockStoreEntry = {
	state: Record< string, unknown >;
	actions: Record< string, unknown >;
	callbacks: Record< string, unknown >;
};

// `frontend.ts` registers `woocommerce` (read-only, to resolve the in-context
// base product and the selected variation id via `itemInContext`) and its
// own `woocommerce/product-gallery` store. This registry merges every
// `store()` call for a namespace onto one persistent entry, mirroring the
// real Interactivity runtime, and `mockStoreCalls` records every namespace
// passed to `store()` so a test can prove the cart machinery module is never
// touched. Named `mock*` (rather than e.g. `registry`) so the `jest.mock()`
// factory below — which may only close over `mock`-prefixed bindings — can
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

// The unified `woocommerce` store's state consulted by
// `listenToProductDataChanges`: `itemInContext.variation` (the derived
// envelope member, not the raw `variationId`) drives change detection, and
// `itemInContext.baseProduct` resolves the parent product whose configured
// image set gets applied.
let mockWooState: {
	itemInContext: {
		variation: ProductResponseItem | null;
		baseProduct: ProductResponseItem | null;
	};
};

// The gallery's own reactive context, as returned by `getContext()`.
let mockContext: ProductGalleryContext;

// The `woocommerce` shared config bag, as returned by `getConfig()` — carries
// the per-product configured image sets (`products[productId]`). This is the
// unchanged, cross-domain settings bag, not the retiring cart-store surface.
let mockConfig: ProductGalleryConfig;

// Whether the cart machinery module (`@woocommerce/stores/woocommerce/cart`)
// was ever loaded while requiring `frontend.ts`. It stays `false` as proof
// this block reads product data through the root module alone and never
// drags the cart's fetch/queue machinery onto a display-only page.
let mockCartModuleLoaded: boolean;

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
				if ( name === 'woocommerce' ) {
					return { state: mockWooState };
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
// mocked `store()` above handles the `woocommerce` registration directly, so
// the real implementation must never load (it would otherwise register the
// namespace a second time against the mock).
jest.mock( '@woocommerce/stores/woocommerce', () => ( {} ), {
	virtual: true,
} );

// The cart machinery module: never imported by `frontend.ts` itself, so this
// factory only runs — flipping the flag — if some future change reintroduces
// a coupling to it.
jest.mock(
	'@woocommerce/stores/woocommerce/cart',
	() => {
		mockCartModuleLoaded = true;
		return {};
	},
	{ virtual: true }
);

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
		mockWooState = {
			itemInContext: { variation: null, baseProduct: null },
		};
		mockCartModuleLoaded = false;
	} );

	afterEach( () => {
		jest.clearAllMocks();
	} );

	describe( 'callbacks.listenToProductDataChanges', () => {
		it( 'is a no-op on the initial render, before any variation is selected', () => {
			mockWooState.itemInContext.variation = null;

			const entries = loadModule();
			const { callbacks } = entries.get(
				'woocommerce/product-gallery'
			) as MockStoreEntry;

			( callbacks.listenToProductDataChanges as () => void )();

			expect( mockContext.imageData ).toEqual( [] );
		} );

		it( 'applies the selected variation image set, resolving the product via itemInContext.baseProduct', () => {
			const entries = loadModule();
			const { callbacks } = entries.get(
				'woocommerce/product-gallery'
			) as MockStoreEntry;
			const listenToProductDataChanges =
				callbacks.listenToProductDataChanges as () => void;

			// Initial render: no variation selected yet.
			mockWooState.itemInContext.variation = null;
			listenToProductDataChanges();

			// A variation gets selected. `itemInContext.baseProduct` is what
			// resolves the parent product whose configured image set is
			// applied; if it resolved nothing, no image update would occur.
			mockWooState.itemInContext.variation = {
				id: 55,
			} as ProductResponseItem;
			mockWooState.itemInContext.baseProduct = {
				id: 123,
			} as ProductResponseItem;
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

			mockWooState.itemInContext.variation = null;
			listenToProductDataChanges();

			mockWooState.itemInContext.variation = {
				id: 55,
			} as ProductResponseItem;
			mockWooState.itemInContext.baseProduct = {
				id: 123,
			} as ProductResponseItem;
			listenToProductDataChanges();

			mockWooState.itemInContext.variation = null;
			listenToProductDataChanges();

			expect( mockContext.imageData ).toEqual( [ 1, 2, 3 ] );
		} );

		it( 'does nothing when itemInContext.baseProduct resolves no product', () => {
			mockWooState.itemInContext.variation = null;

			const entries = loadModule();
			const { callbacks } = entries.get(
				'woocommerce/product-gallery'
			) as MockStoreEntry;
			const listenToProductDataChanges =
				callbacks.listenToProductDataChanges as () => void;

			listenToProductDataChanges();

			mockWooState.itemInContext.variation = {
				id: 55,
			} as ProductResponseItem;
			mockWooState.itemInContext.baseProduct = null;
			listenToProductDataChanges();

			expect( mockContext.imageData ).toEqual( [] );
		} );
	} );

	describe( 'store registration', () => {
		it( 'value-imports and reads the unified woocommerce store, never the cart machinery module', () => {
			loadModule();

			expect( mockStoreCalls ).toEqual(
				expect.arrayContaining( [
					'woocommerce',
					'woocommerce/product-gallery',
				] )
			);
			expect( mockCartModuleLoaded ).toBe( false );
		} );
	} );
} );
