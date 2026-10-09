/**
 * External dependencies
 */
import { store } from '@wordpress/interactivity';

/**
 * Internal dependencies
 */
import type { CatalogState } from '../types';

// The acknowledgement string the module's `store()` call passes. Copied
// here rather than imported, so the test fails if the source value ever
// drifts from what the other stores in this folder pass.
const storeConsent =
	'I acknowledge that using a private store means my plugin will inevitably break on the next store release.';

let mockRegisteredStore: {
	state: CatalogState;
} | null = null;

jest.mock(
	'@wordpress/interactivity',
	() => ( {
		store: jest.fn( ( namespace, definition ) => {
			if ( namespace === 'woocommerce' ) {
				// Simulate the real library's behaviour: every `store()`
				// call for the same namespace merges its state onto one
				// shared object and returns the same registered store, so a
				// later call passing the shared acknowledgement string
				// still resolves it — the `woocommerce/products` view and
				// the cart-line matcher both rely on this.
				mockRegisteredStore ??= { state: {} as CatalogState };
				if ( definition?.state ) {
					Object.defineProperties(
						mockRegisteredStore.state,
						Object.getOwnPropertyDescriptors( definition.state )
					);
				}
				return mockRegisteredStore;
			}
			return {};
		} ),
	} ),
	{ virtual: true }
);

describe( 'woocommerce store — catalog layer', () => {
	beforeEach( () => {
		mockRegisteredStore = null;
		( store as jest.Mock ).mockClear();

		jest.isolateModules( () => require( '../index' ) );
	} );

	it( 'registers the woocommerce store exactly once, with the shared acknowledgement string, and empty products/productVariations', () => {
		const woocommerceRegistrations = (
			store as jest.Mock
		 ).mock.calls.filter(
			( [ namespace, definition ] ) =>
				namespace === 'woocommerce' && definition?.state
		);

		expect( woocommerceRegistrations ).toHaveLength( 1 );
		expect( woocommerceRegistrations[ 0 ][ 2 ] ).toEqual( {
			lock: storeConsent,
		} );

		const state = ( mockRegisteredStore as { state: CatalogState } ).state;
		expect( state.products ).toEqual( {} );
		expect( state.productVariations ).toEqual( {} );
	} );

	it( 'carries no concrete value for either server-seeded key, so registering the store never overrides server-seeded data', () => {
		// `store()` merges the client's initial state onto the
		// server-seeded state with `override: true`, so a concrete value
		// here would replace real server data instead of leaving it alone.
		// An empty object is safe: the merge iterates its keys to decide
		// what to override, and an empty object has none.
		const state = ( mockRegisteredStore as { state: CatalogState } ).state;
		expect( Object.keys( state.products ) ).toHaveLength( 0 );
		expect( Object.keys( state.productVariations ) ).toHaveLength( 0 );
	} );
} );
