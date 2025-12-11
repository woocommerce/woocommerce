/**
 * Internal dependencies
 */
import type { Store } from '../cart';

let mockRegisteredStore: {
	state: Store[ 'state' ];
	actions: Store[ 'actions' ];
} | null = null;

const mockState: Store[ 'state' ] = {} as Store[ 'state' ];

jest.mock(
	'@wordpress/interactivity',
	() => ( {
		getConfig: jest.fn(),
		store: jest.fn( ( _name, definition ) => {
			mockRegisteredStore = {
				state: mockState,
				actions: definition.actions,
			};
			return mockRegisteredStore;
		} ),
	} ),
	{ virtual: true }
);

jest.mock( '../legacy-events', () => ( {
	triggerAddedToCartEvent: jest.fn(),
} ) );

const fetchCalls: Array< { url: string; options: RequestInit } > = [];
const originalFetch = global.fetch;

const mockFetch = jest.fn(
	( url: string, options: RequestInit ): Promise< Response > => {
		fetchCalls.push( { url, options } );
		return Promise.resolve(
			new Response(
				JSON.stringify( { items: [], totals: {}, errors: [] } ),
				{ status: 200, headers: { 'Content-Type': 'application/json' } }
			)
		);
	}
);

function getStore() {
	if ( ! mockRegisteredStore ) {
		throw new Error( 'Cart store was not registered.' );
	}
	return mockRegisteredStore;
}

describe( 'WooCommerce Cart Interactivity API Store', () => {
	beforeEach( () => {
		jest.resetModules();
		mockFetch.mockClear();
		fetchCalls.length = 0;
		mockRegisteredStore = null;
		global.fetch = mockFetch as typeof global.fetch;

		mockState.restUrl = 'https://example.com/wp-json/';
		mockState.nonce = 'test-nonce-123';
		mockState.cart = {
			items: [
				{
					key: 'test-item-key',
					id: 1,
					quantity: 2,
					type: 'simple',
					name: 'Test Product',
					sold_individually: false,
				},
			],
			totals: {} as Store[ 'state' ][ 'cart' ][ 'totals' ],
			errors: [],
		} as Store[ 'state' ][ 'cart' ];

		// Isolate the module to prevent the store from being registered multiple times.
		jest.isolateModules( () => {
			require( '../cart' );
		} );
	} );

	afterEach( () => {
		global.fetch = originalFetch;
	} );

	describe( 'API requests include cache: no-store to prevent browser caching', () => {
		it( 'removeCartItem passes cache: no-store to fetch', () => {
			const store = getStore();
			const iterator = store.actions.removeCartItem( 'test-item-key' );
			iterator.next();

			expect( fetchCalls ).toHaveLength( 1 );
			const { url, options } = fetchCalls[ 0 ];
			expect( url ).toBe(
				'https://example.com/wp-json/wc/store/v1/cart/remove-item'
			);
			expect( options ).toEqual(
				expect.objectContaining( {
					method: 'POST',
					cache: 'no-store',
					headers: expect.objectContaining( {
						Nonce: 'test-nonce-123',
						'Content-Type': 'application/json',
					} ),
				} )
			);
		} );

		it( 'addCartItem passes cache: no-store to fetch when adding new item', () => {
			const store = getStore();
			const iterator = store.actions.addCartItem( {
				id: 99,
				quantity: 1,
				type: 'simple',
			} );
			iterator.next();

			expect( fetchCalls ).toHaveLength( 1 );
			const { url, options } = fetchCalls[ 0 ];
			expect( url ).toBe(
				'https://example.com/wp-json/wc/store/v1/cart/add-item'
			);
			expect( options ).toEqual(
				expect.objectContaining( {
					method: 'POST',
					cache: 'no-store',
				} )
			);
		} );

		it( 'addCartItem passes cache: no-store to fetch when updating existing item', () => {
			const store = getStore();
			const iterator = store.actions.addCartItem( {
				id: 1,
				quantity: 5,
				type: 'simple',
			} );
			iterator.next();

			expect( fetchCalls ).toHaveLength( 1 );
			const { url, options } = fetchCalls[ 0 ];
			expect( url ).toBe(
				'https://example.com/wp-json/wc/store/v1/cart/update-item'
			);
			expect( options ).toEqual(
				expect.objectContaining( {
					method: 'POST',
					cache: 'no-store',
				} )
			);
		} );

		it( 'batchAddCartItems passes cache: no-store to batch fetch request', () => {
			const store = getStore();
			const iterator = store.actions.batchAddCartItems( [
				{ id: 10, quantity: 1, type: 'simple' },
				{ id: 20, quantity: 2, type: 'simple' },
			] );
			iterator.next();

			expect( fetchCalls ).toHaveLength( 1 );
			const { url, options } = fetchCalls[ 0 ];
			expect( url ).toBe(
				'https://example.com/wp-json/wc/store/v1/batch'
			);
			expect( options ).toEqual(
				expect.objectContaining( {
					method: 'POST',
					cache: 'no-store',
				} )
			);

			const body = JSON.parse( options.body as string );
			expect( body.requests ).toHaveLength( 2 );
			body.requests.forEach(
				( request: { cache?: string; path: string } ) => {
					expect( request.cache ).toBe( 'no-store' );
				}
			);
		} );

		it( 'refreshCartItems passes cache: no-store to fetch', () => {
			const store = getStore();
			const iterator = store.actions.refreshCartItems();
			iterator.next();

			expect( fetchCalls ).toHaveLength( 1 );
			const { url, options } = fetchCalls[ 0 ];
			expect( url ).toBe(
				'https://example.com/wp-json/wc/store/v1/cart'
			);
			expect( options ).toEqual(
				expect.objectContaining( {
					method: 'GET',
					cache: 'no-store',
				} )
			);
		} );
	} );
} );
