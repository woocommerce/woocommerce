/**
 * External dependencies
 */
import type { ProductResponseItem } from '@woocommerce/types';

/**
 * Internal dependencies
 */
import type { Context } from '../frontend';

type MockStoreEntry = {
	state: Record< string, unknown >;
	actions: Record< string, unknown >;
	callbacks: Record< string, unknown >;
};

// `frontend.ts` registers `woocommerce` (read-only, to resolve the
// in-context product via `itemInContext`) and its own
// `woocommerce/product-elements` store. This registry merges every
// `store()` call for a namespace onto one persistent entry, mirroring the
// real Interactivity runtime, and `mockStoreCalls` records every namespace
// passed to `store()`. Named `mock*` (rather than e.g. `registry`) so the
// `jest.mock()` factory below — which may only close over `mock`-prefixed
// bindings — can reference it.
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

// The unified `woocommerce` store's state consulted by `updateValue`:
// `itemInContext.product` resolves the in-context product whose field the
// element mirrors.
let mockWooState: { itemInContext: { product: ProductResponseItem | null } };

// The element the current callback is bound to, as returned by
// `getElement()`.
let mockElement: { ref: HTMLElement | null };

// The block's own reactive context, as returned by `getContext()`.
let mockContext: Context;

jest.mock(
	'@wordpress/interactivity',
	() => ( {
		getElement: jest.fn( () => mockElement ),
		getContext: jest.fn( () => mockContext ),
		store: jest.fn(
			( name: string, definition?: Record< string, unknown > ) => {
				mockStoreCalls.push( name );
				if ( name === 'woocommerce' ) {
					return { state: mockWooState };
				}
				const entry = mockGetEntry( name );
				if ( definition?.callbacks ) {
					Object.assign( entry.callbacks, definition.callbacks );
				}
				return entry;
			}
		),
	} ),
	{ virtual: true }
);

// The root module's side-effect registration: the mocked `store()` above
// handles the actual `woocommerce` registration, so the real implementation
// must never load (it would otherwise register the namespace a second time
// against the mock).
jest.mock( '@woocommerce/stores/woocommerce', () => ( {} ), {
	virtual: true,
} );

/**
 * Loads a fresh copy of the product elements frontend module so it
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

describe( 'Product Elements frontend store', () => {
	beforeEach( () => {
		mockWooState = { itemInContext: { product: null } };
		mockElement = { ref: document.createElement( 'span' ) };
		mockContext = { productElementKey: 'name' };
	} );

	afterEach( () => {
		jest.clearAllMocks();
	} );

	describe( 'callbacks.updateValue', () => {
		it( 'writes the sanitized in-context product field into the element', () => {
			mockWooState.itemInContext.product = {
				name: 'A <strong>Product</strong>',
			} as ProductResponseItem;

			const { callbacks } = loadModule().get(
				'woocommerce/product-elements'
			) as MockStoreEntry;
			( callbacks.updateValue as () => void )();

			expect( mockElement.ref?.innerHTML ).toBe(
				'A <strong>Product</strong>'
			);
		} );

		it( 'strips disallowed tags and attributes while sanitizing', () => {
			mockWooState.itemInContext.product = {
				name: '<script>alert(1)</script><a href="/x" onclick="y">link</a>',
			} as ProductResponseItem;

			const { callbacks } = loadModule().get(
				'woocommerce/product-elements'
			) as MockStoreEntry;
			( callbacks.updateValue as () => void )();

			expect( mockElement.ref?.innerHTML ).toBe(
				'<a href="/x">link</a>'
			);
		} );

		it( 'reads the field named by the context productElementKey', () => {
			mockContext.productElementKey = 'short_description';
			mockWooState.itemInContext.product = {
				name: 'Ignored',
				short_description: 'The short description',
			} as ProductResponseItem;

			const { callbacks } = loadModule().get(
				'woocommerce/product-elements'
			) as MockStoreEntry;
			( callbacks.updateValue as () => void )();

			expect( mockElement.ref?.innerHTML ).toBe(
				'The short description'
			);
		} );

		it( 'does nothing when no product is in context', () => {
			mockWooState.itemInContext.product = null;
			const initialHtml = mockElement.ref
				? mockElement.ref.innerHTML
				: '';

			const { callbacks } = loadModule().get(
				'woocommerce/product-elements'
			) as MockStoreEntry;
			( callbacks.updateValue as () => void )();

			expect( mockElement.ref?.innerHTML ).toBe( initialHtml );
		} );

		it( 'does nothing when the element has no ref', () => {
			mockElement = { ref: null };
			mockWooState.itemInContext.product = {
				name: 'A Product',
			} as ProductResponseItem;

			const { callbacks } = loadModule().get(
				'woocommerce/product-elements'
			) as MockStoreEntry;

			expect( () =>
				( callbacks.updateValue as () => void )()
			).not.toThrow();
		} );

		it( 'does nothing when the addressed field is not a string', () => {
			mockContext.productElementKey = 'grouped_products';
			mockWooState.itemInContext.product = {
				name: 'A Product',
				grouped_products: [ 1, 2, 3 ],
			} as unknown as ProductResponseItem;
			const initialHtml = mockElement.ref?.innerHTML ?? '';

			const { callbacks } = loadModule().get(
				'woocommerce/product-elements'
			) as MockStoreEntry;
			( callbacks.updateValue as () => void )();

			expect( mockElement.ref?.innerHTML ).toBe( initialHtml );
		} );
	} );

	describe( 'store registration', () => {
		it( 'opens the unified woocommerce store, not a separate products namespace', () => {
			loadModule();

			expect( mockStoreCalls ).toEqual(
				expect.arrayContaining( [
					'woocommerce',
					'woocommerce/product-elements',
				] )
			);
			expect( mockStoreCalls ).not.toContain( 'woocommerce/products' );
		} );
	} );
} );
