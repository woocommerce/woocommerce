/**
 * External dependencies
 */
import type { ProductsStoreState } from '@woocommerce/stores/woocommerce/products';

/**
 * Internal dependencies
 */
import type { ProductCollectionStoreContext } from '../frontend';
import { CoreCollectionNames } from '../types';

type MockStoreEntry = {
	state: Record< string, unknown >;
	actions: Record< string, unknown >;
	callbacks: Record< string, unknown >;
};

// `frontend.ts` registers `woocommerce/products` (read-only, to resolve the
// in-context product for the viewed-product event) and its own
// `woocommerce/product-collection` store. This registry merges every
// `store()` call for a namespace onto one persistent entry, mirroring the
// real Interactivity runtime, and `mockStoreCalls` records every namespace
// passed to `store()` so a test can prove `woocommerce/cart` is never
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

// The `woocommerce/products` store's state consulted by `actions.viewProduct`:
// `productInContext` resolves the per-card product from the loop item's own
// context — a getter distinct from, and unaffected by, the
// `mainProductInContext` → `baseProductInContext` rename.
let mockProductsState: Partial< ProductsStoreState >;

// The block's own reactive context, as returned by `getContext()`.
let mockContext: ProductCollectionStoreContext;

// The element the current action is bound to, as returned by `getElement()`.
let mockElement: { ref: HTMLElement | null };

// The `@wordpress/interactivity-router` action spies used by client-side
// pagination (`actions.navigate`).
let mockRouterNavigate: jest.Mock;
let mockRouterPrefetch: jest.Mock;

jest.mock(
	'@wordpress/interactivity',
	() => ( {
		getElement: jest.fn( () => mockElement ),
		getContext: jest.fn( () => mockContext ),
		store: jest.fn(
			( name: string, definition?: Record< string, unknown > ) => {
				mockStoreCalls.push( name );
				if ( name === 'woocommerce/products' ) {
					return { state: mockProductsState };
				}
				const entry = mockGetEntry( name );
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

jest.mock(
	'@wordpress/interactivity-router',
	() => ( {
		actions: {
			navigate: mockRouterNavigate,
			prefetch: mockRouterPrefetch,
		},
	} ),
	{ virtual: true }
);

// `frontend.ts` dispatches these as DOM CustomEvents for legacy jQuery/PHP
// listeners; mocked so tests can assert the payloads directly instead of
// asserting on dispatched-event side effects.
jest.mock( '../legacy-events', () => ( {
	triggerProductListRenderedEvent: jest.fn(),
	triggerViewedProductEvent: jest.fn(),
} ) );

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
 * Loads a fresh copy of the product collection frontend module so it
 * registers its stores against the mocked `store()`.
 *
 * @return The registry of every namespace the module registered.
 */
function loadModule(): Map< string, MockStoreEntry > {
	mockRegistry = new Map();
	mockStoreCalls = [];
	jest.isolateModules( () => require( '../frontend' ) );
	return mockRegistry;
}

/** Builds a same-origin anchor element that satisfies `isValidLink`. */
function makeValidLinkElement( href: string ): HTMLAnchorElement {
	const anchor = document.createElement( 'a' );
	anchor.href = href;
	return anchor;
}

describe( 'Product Collection frontend store', () => {
	beforeEach( () => {
		mockContext = {
			isPrefetchNextOrPreviousLink: '',
			collection: CoreCollectionNames.PRODUCT_CATALOG,
			hideNextPreviousButtons: false,
			isDisabledPrevious: false,
			isDisabledNext: false,
			ariaLabelPrevious: 'Previous',
			ariaLabelNext: 'Next',
		};
		mockProductsState = {};
		mockElement = { ref: null };
		mockRouterNavigate = jest.fn( () => Promise.resolve() );
		mockRouterPrefetch = jest.fn( () => Promise.resolve() );
	} );

	afterEach( () => {
		jest.clearAllMocks();
	} );

	describe( 'actions.viewProduct', () => {
		it( 'reads the product id from productsState.productInContext', async () => {
			const { triggerViewedProductEvent } = jest.requireMock(
				'../legacy-events'
			) as { triggerViewedProductEvent: jest.Mock };
			mockProductsState.productInContext = {
				id: 55,
			} as ProductsStoreState[ 'productInContext' ];

			const entries = loadModule();
			const { actions } = entries.get(
				'woocommerce/product-collection'
			) as MockStoreEntry;

			await runAction( ( actions.viewProduct as () => Iterator< unknown > )() );

			expect( triggerViewedProductEvent ).toHaveBeenCalledWith( {
				collection: CoreCollectionNames.PRODUCT_CATALOG,
				productId: 55,
			} );
		} );

		it( 'resolves a fresh product id per card, proving no card leaks into another', async () => {
			const { triggerViewedProductEvent } = jest.requireMock(
				'../legacy-events'
			) as { triggerViewedProductEvent: jest.Mock };

			const entries = loadModule();
			const { actions } = entries.get(
				'woocommerce/product-collection'
			) as MockStoreEntry;
			const viewProduct = actions.viewProduct as () => Iterator< unknown >;

			// First card / first paginated page.
			mockProductsState.productInContext = {
				id: 11,
			} as ProductsStoreState[ 'productInContext' ];
			await runAction( viewProduct() );

			// A different card — e.g. after a client-side pagination swap —
			// must resolve its own id, not the previous card's.
			mockProductsState.productInContext = {
				id: 22,
			} as ProductsStoreState[ 'productInContext' ];
			await runAction( viewProduct() );

			expect( triggerViewedProductEvent ).toHaveBeenNthCalledWith( 1, {
				collection: CoreCollectionNames.PRODUCT_CATALOG,
				productId: 11,
			} );
			expect( triggerViewedProductEvent ).toHaveBeenNthCalledWith( 2, {
				collection: CoreCollectionNames.PRODUCT_CATALOG,
				productId: 22,
			} );
		} );

		it( 'does not trigger the event when no product is in context', async () => {
			const { triggerViewedProductEvent } = jest.requireMock(
				'../legacy-events'
			) as { triggerViewedProductEvent: jest.Mock };
			mockProductsState.productInContext = null;

			const entries = loadModule();
			const { actions } = entries.get(
				'woocommerce/product-collection'
			) as MockStoreEntry;

			await runAction( ( actions.viewProduct as () => Iterator< unknown > )() );

			expect( triggerViewedProductEvent ).not.toHaveBeenCalled();
		} );
	} );

	describe( 'actions.navigate', () => {
		it( 'paginates client-side via the router without touching a cart store', async () => {
			const { triggerProductListRenderedEvent } = jest.requireMock(
				'../legacy-events'
			) as { triggerProductListRenderedEvent: jest.Mock };
			const href = `${ window.location.origin }/shop/page/2/`;
			mockElement = { ref: makeValidLinkElement( href ) };

			const entries = loadModule();
			const { actions } = entries.get(
				'woocommerce/product-collection'
			) as MockStoreEntry;

			const event = new MouseEvent( 'click', {
				button: 0,
				bubbles: true,
				cancelable: true,
			} );

			await runAction(
				( actions.navigate as ( e: MouseEvent ) => Iterator< unknown > )(
					event
				)
			);

			expect( mockRouterNavigate ).toHaveBeenCalledWith( href );
			expect( mockContext.isPrefetchNextOrPreviousLink ).toBe( href );
			expect( triggerProductListRenderedEvent ).toHaveBeenCalledWith( {
				collection: CoreCollectionNames.PRODUCT_CATALOG,
			} );
			expect( mockStoreCalls ).not.toContain( 'woocommerce/cart' );
		} );
	} );

	describe( 'store registration', () => {
		it( 'never registers or reads the woocommerce/cart store', () => {
			loadModule();

			expect( mockStoreCalls ).toEqual(
				expect.arrayContaining( [
					'woocommerce/products',
					'woocommerce/product-collection',
				] )
			);
			expect( mockStoreCalls ).not.toContain( 'woocommerce/cart' );
		} );
	} );
} );
