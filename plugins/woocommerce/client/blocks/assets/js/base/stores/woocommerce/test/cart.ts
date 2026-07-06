/**
 * Internal dependencies
 */
import type { Store } from '../cart';

type MockStore = { state: Store[ 'state' ]; actions: Store[ 'actions' ] };

let mockRegisteredStore: MockStore | null = null;
const mockState = {} as Store[ 'state' ];

// `restUrl` (and the other infra values) now live in
// `wp_interactivity_config( 'woocommerce' )`, so the cart store reads them via
// `getConfig` instead of from reactive state.
const mockConfig = {
	restUrl: 'https://example.com/wp-json/',
	nonce: 'test-nonce-123',
};

jest.mock(
	'@wordpress/interactivity',
	() => ( {
		getConfig: jest.fn( () => mockConfig ),
		// The cart store registers under `woocommerce/cart` and re-registers a
		// delegating alias under `woocommerce`; both carry the same `actions`.
		// Record the actions from every registration that supplies them, so the
		// object under test is the real cart actions object.
		store: jest.fn( ( _name, definition ) => {
			mockRegisteredStore = {
				state: mockState,
				actions: definition?.actions ?? mockRegisteredStore?.actions,
			};
			return mockRegisteredStore;
		} ),
	} ),
	{ virtual: true }
);

jest.mock( '../legacy-events', () => ( {
	triggerAddedToCartEvent: jest.fn(),
} ) );

describe( 'WooCommerce Cart Interactivity API Store', () => {
	it( 'refreshCartItems passes cache: no-store to fetch to prevent browser caching', () => {
		const mockFetch = jest
			.fn()
			.mockResolvedValue(
				new Response(
					JSON.stringify( { items: [], totals: {}, errors: [] } )
				)
			);
		global.fetch = mockFetch;

		jest.isolateModules( () => require( '../cart' ) );

		const iterator = mockRegisteredStore?.actions.refreshCartItems();

		// Async actions are typed as void for consumers, but are actually generators internally.
		( iterator as unknown as Iterator< void > ).next();

		expect( mockFetch ).toHaveBeenCalledWith(
			'https://example.com/wp-json/wc/store/v1/cart',
			expect.objectContaining( {
				method: 'GET',
				cache: 'no-store',
			} )
		);
	} );
} );
