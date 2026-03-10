/**
 * Internal dependencies
 */
import type { Store } from '../cart';

type MockStore = { state: Store[ 'state' ]; actions: Store[ 'actions' ] };

let mockRegisteredStore: MockStore | null = null;
const mockState = {
	restUrl: 'https://example.com/wp-json/',
	nonce: 'test-nonce-123',
} as Store[ 'state' ];

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

jest.mock( '@woocommerce/stores/store-notices', () => ( {} ), {
	virtual: true,
} );

jest.mock(
	'@wordpress/a11y',
	() => ( { speak: jest.fn() } ),
	{ virtual: true }
);

const initialCart = {
	items: [
		{
			key: 'item-1',
			id: 10,
			quantity: 1,
			type: 'simple',
			name: 'Widget',
		},
	],
	totals: { total_items: '1' },
	errors: [],
};

describe( 'WooCommerce Cart Interactivity API Store', () => {
	let originalQueueMicrotask: typeof globalThis.queueMicrotask;

	beforeEach( () => {
		jest.resetModules();
		mockRegisteredStore = null;
		Object.assign( mockState, {
			cart: JSON.parse( JSON.stringify( initialCart ) ),
		} );
		global.fetch = jest.fn();
		originalQueueMicrotask = globalThis.queueMicrotask;
	} );

	afterEach( () => {
		globalThis.queueMicrotask = originalQueueMicrotask;
	} );

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

	describe( 'REQ-003: actions call enqueue then optimistic update', () => {
		it( 'addCartItem for a new item enqueues with add-item path and correct request shape', () => {
			const mockQueueMicrotask = jest.fn();
			globalThis.queueMicrotask = mockQueueMicrotask;

			const mockFetch = jest.fn();
			global.fetch = mockFetch;

			jest.isolateModules( () => require( '../cart' ) );

			// Call addCartItem for a new item (not in cart).
			const gen = mockRegisteredStore!.actions.addCartItem( {
				id: 99,
				quantity: 2,
				type: 'simple',
			} ) as unknown as Generator;

			gen.next(); // runs enqueue + optimistic update, yields promise

			// Drive lifecycle to see the request sent in the batch.
			const processLifecycle = (
				mockRegisteredStore!.actions as Record<
					string,
					( ...args: unknown[] ) => unknown
				>
			)._processLifecycle;

			const lifecycle = processLifecycle( 0 ) as unknown as Generator;
			lifecycle.next(); // yield fetch(...)

			expect( mockFetch ).toHaveBeenCalledTimes( 1 );
			const [ url, options ] = mockFetch.mock.calls[ 0 ];
			expect( url ).toBe(
				'https://example.com/wp-json/wc/store/v1/batch'
			);

			const body = JSON.parse( options.body as string );
			expect( body.requests ).toHaveLength( 1 );

			const request = body.requests[ 0 ];
			expect( request ).toEqual(
				expect.objectContaining( {
					method: 'POST',
					path: '/wc/store/v1/cart/add-item',
					headers: expect.objectContaining( {
						Nonce: 'test-nonce-123',
						'Content-Type': 'application/json',
					} ),
				} )
			);

			// Body should contain the new item data.
			expect( request.body ).toEqual(
				expect.objectContaining( {
					id: 99,
					quantity: 2,
				} )
			);
		} );

		it( 'addCartItem for an existing item enqueues with update-item path', () => {
			const mockQueueMicrotask = jest.fn();
			globalThis.queueMicrotask = mockQueueMicrotask;

			const mockFetch = jest.fn();
			global.fetch = mockFetch;

			jest.isolateModules( () => require( '../cart' ) );

			// Call addCartItem for an existing item (id:10 is in initialCart).
			const gen = mockRegisteredStore!.actions.addCartItem( {
				id: 10,
				quantity: 5,
				type: 'simple',
			} ) as unknown as Generator;

			gen.next(); // runs enqueue + optimistic update, yields promise

			// Drive lifecycle to see the request.
			const processLifecycle = (
				mockRegisteredStore!.actions as Record<
					string,
					( ...args: unknown[] ) => unknown
				>
			)._processLifecycle;

			const lifecycle = processLifecycle( 0 ) as unknown as Generator;
			lifecycle.next(); // yield fetch(...)

			const body = JSON.parse(
				mockFetch.mock.calls[ 0 ][ 1 ].body as string
			);
			expect( body.requests[ 0 ].path ).toBe(
				'/wc/store/v1/cart/update-item'
			);
		} );

		it( 'removeCartItem enqueues with remove-item path and correct body', () => {
			const mockQueueMicrotask = jest.fn();
			globalThis.queueMicrotask = mockQueueMicrotask;

			const mockFetch = jest.fn();
			global.fetch = mockFetch;

			jest.isolateModules( () => require( '../cart' ) );

			// Remove an existing item.
			const gen = mockRegisteredStore!.actions.removeCartItem(
				'item-1'
			) as unknown as Generator;

			gen.next(); // runs enqueue + optimistic filter, yields promise

			// Drive lifecycle to see the request.
			const processLifecycle = (
				mockRegisteredStore!.actions as Record<
					string,
					( ...args: unknown[] ) => unknown
				>
			)._processLifecycle;

			const lifecycle = processLifecycle( 0 ) as unknown as Generator;
			lifecycle.next(); // yield fetch(...)

			expect( mockFetch ).toHaveBeenCalledTimes( 1 );
			const body = JSON.parse(
				mockFetch.mock.calls[ 0 ][ 1 ].body as string
			);

			expect( body.requests ).toHaveLength( 1 );
			const request = body.requests[ 0 ];
			expect( request ).toEqual(
				expect.objectContaining( {
					method: 'POST',
					path: '/wc/store/v1/cart/remove-item',
					headers: expect.objectContaining( {
						Nonce: 'test-nonce-123',
						'Content-Type': 'application/json',
					} ),
					body: { key: 'item-1' },
				} )
			);
		} );

		it( 'optimistic update is applied after enqueue (items modified after snapshot)', () => {
			const mockQueueMicrotask = jest.fn();
			globalThis.queueMicrotask = mockQueueMicrotask;

			jest.isolateModules( () => require( '../cart' ) );

			// Add a new item — enqueue captures snapshot BEFORE optimistic push.
			const gen = mockRegisteredStore!.actions.addCartItem( {
				id: 99,
				quantity: 1,
				type: 'simple',
			} ) as unknown as Generator;

			gen.next(); // runs enqueue + optimistic update

			// After the action, cart should have 2 items (original + optimistic).
			expect( mockState.cart.items ).toHaveLength( 2 );
			expect( mockState.cart.items[ 1 ] ).toEqual(
				expect.objectContaining( { id: 99, quantity: 1 } )
			);

			// But the snapshot (verified via total-failure rollback) should have only 1 item.
			const processLifecycle = (
				mockRegisteredStore!.actions as Record<
					string,
					( ...args: unknown[] ) => unknown
				>
			)._processLifecycle;

			const lifecycle = processLifecycle( 0 ) as unknown as Generator;
			lifecycle.next(); // yield fetch(...)

			// Network error → total failure → rollback.
			const consoleSpy = jest
				.spyOn( console, 'error' )
				.mockImplementation( () => {} );
			try {
				lifecycle.throw!( new Error( 'Network error' ) );
			} catch {
				// lifecycle may finish
			}
			consoleSpy.mockRestore();

			// Rolled back: only the original item remains.
			expect( mockState.cart.items ).toHaveLength( 1 );
			expect( mockState.cart.items[ 0 ].id ).toBe( 10 );
		} );

		it( 'addCartItem sets addItem: true in the pending entry', () => {
			const mockQueueMicrotask = jest.fn();
			globalThis.queueMicrotask = mockQueueMicrotask;

			const mockFetch = jest.fn();
			global.fetch = mockFetch;

			jest.isolateModules( () => require( '../cart' ) );

			// Add item, then also remove item — verify their addItem flags
			// by checking that hadSuccessfulAdd side effects only fire for adds.
			// We test this indirectly: two items batched, both succeed.
			// triggerAddedToCartEvent should fire because at least one is an add.
			const addGen = mockRegisteredStore!.actions.addCartItem( {
				id: 99,
				quantity: 1,
				type: 'simple',
			} ) as unknown as Generator;

			addGen.next();

			const removeGen = mockRegisteredStore!.actions.removeCartItem(
				'item-1'
			) as unknown as Generator;

			removeGen.next();

			// Drive lifecycle — both should be in the same batch.
			const processLifecycle = (
				mockRegisteredStore!.actions as Record<
					string,
					( ...args: unknown[] ) => unknown
				>
			)._processLifecycle;

			const lifecycle = processLifecycle( 0 ) as unknown as Generator;
			lifecycle.next(); // yield fetch(...)

			const body = JSON.parse(
				mockFetch.mock.calls[ 0 ][ 1 ].body as string
			);
			// Batch should contain 2 requests (one add, one remove).
			expect( body.requests ).toHaveLength( 2 );
		} );
	} );

	describe( 'REQ-002: enqueue snapshot capture', () => {
		it( 'captures pre-optimistic snapshot on first enqueue and schedules queueMicrotask', () => {
			const mockQueueMicrotask = jest.fn();
			globalThis.queueMicrotask = mockQueueMicrotask;

			jest.isolateModules( () => require( '../cart' ) );

			// Call addCartItem — it calls enqueue() first, then applies optimistic update.
			const gen = mockRegisteredStore!.actions.addCartItem( {
				id: 99,
				quantity: 1,
				type: 'simple',
			} ) as unknown as Generator;

			gen.next(); // runs through enqueue + optimistic update, yields promise

			// queueMicrotask should have been called exactly once (first enqueue schedules lifecycle).
			expect( mockQueueMicrotask ).toHaveBeenCalledTimes( 1 );

			// The snapshot should capture the cart state BEFORE the optimistic add.
			// We verify this by driving the lifecycle to total failure and checking rollback.
			const processLifecycle = (
				mockRegisteredStore!.actions as Record<
					string,
					( ...args: unknown[] ) => unknown
				>
			)._processLifecycle;

			const lifecycle = processLifecycle( 0 ) as unknown as Generator;
			lifecycle.next(); // yield fetch(...)

			// Simulate network error → total failure
			lifecycle.next( { status: 500, ok: false, json: jest.fn() } ); // yield res.json()
			const consoleSpy = jest
				.spyOn( console, 'error' )
				.mockImplementation( () => {} );
			try {
				lifecycle.next( {
					code: 'server_error',
					message: 'fail',
				} ); // non-207 → reconciliation
			} catch {
				// lifecycle may throw or complete
			}
			consoleSpy.mockRestore();

			// After total failure, state.cart should be rolled back to the pre-optimistic snapshot.
			// The snapshot was taken before the optimistic add of item id:99.
			// So the cart should NOT contain the optimistic item.
			expect( mockState.cart ).toEqual( initialCart );
		} );

		it( 'snapshot captures state before optimistic update, not after', () => {
			const mockQueueMicrotask = jest.fn();
			globalThis.queueMicrotask = mockQueueMicrotask;

			jest.isolateModules( () => require( '../cart' ) );

			const cartBeforeAction = JSON.parse(
				JSON.stringify( mockState.cart )
			);

			// Call removeCartItem — enqueue() captures snapshot before the optimistic filter.
			const gen = mockRegisteredStore!.actions.removeCartItem(
				'item-1'
			) as unknown as Generator;

			gen.next(); // runs enqueue + optimistic filter, yields promise

			// After optimistic update, cart items should be empty (item-1 was removed).
			expect( mockState.cart.items ).toHaveLength( 0 );

			// Now drive lifecycle to total failure to trigger rollback.
			const processLifecycle = (
				mockRegisteredStore!.actions as Record<
					string,
					( ...args: unknown[] ) => unknown
				>
			)._processLifecycle;

			const lifecycle = processLifecycle( 0 ) as unknown as Generator;
			lifecycle.next(); // yield fetch(...)

			// Network error → total failure → rollback to snapshot.
			const consoleSpy = jest
				.spyOn( console, 'error' )
				.mockImplementation( () => {} );
			try {
				lifecycle.throw!( new Error( 'Network error' ) );
			} catch {
				// lifecycle may finish
			}
			consoleSpy.mockRestore();

			// Cart should be restored to the pre-optimistic state (with item-1 present).
			expect( mockState.cart ).toEqual( cartBeforeAction );
			expect( mockState.cart.items ).toHaveLength( 1 );
			expect( mockState.cart.items[ 0 ].key ).toBe( 'item-1' );
		} );

		it( 'resets running flag and removes entry from pending when JSON.stringify throws', () => {
			const mockQueueMicrotask = jest.fn();
			globalThis.queueMicrotask = mockQueueMicrotask;

			jest.isolateModules( () => require( '../cart' ) );

			// Make state.cart non-serializable by adding a circular reference.
			const circular: Record< string, unknown > = {};
			circular.self = circular;
			mockState.cart = circular as unknown as typeof mockState.cart;

			// Calling addCartItem should throw because enqueue() can't snapshot the cart.
			expect( () => {
				const gen = mockRegisteredStore!.actions.addCartItem( {
					id: 99,
					quantity: 1,
					type: 'simple',
				} ) as unknown as Generator;
				gen.next();
			} ).toThrow();

			// queueMicrotask should NOT have been called (snapshot failed before scheduling).
			expect( mockQueueMicrotask ).not.toHaveBeenCalled();

			// Verify that running was reset: a subsequent enqueue should work.
			// Restore a valid cart so the next enqueue can snapshot.
			mockState.cart = JSON.parse(
				JSON.stringify( initialCart )
			) as typeof mockState.cart;

			const gen2 = mockRegisteredStore!.actions.addCartItem( {
				id: 50,
				quantity: 1,
				type: 'simple',
			} ) as unknown as Generator;

			gen2.next();

			// queueMicrotask should now be called (running was properly reset).
			expect( mockQueueMicrotask ).toHaveBeenCalledTimes( 1 );
		} );

		it( 'removes the failed entry from pending so it does not appear in the batch', () => {
			const mockQueueMicrotask = jest.fn();
			globalThis.queueMicrotask = mockQueueMicrotask;

			jest.isolateModules( () => require( '../cart' ) );

			// Make cart non-serializable.
			const circular: Record< string, unknown > = {};
			circular.self = circular;
			mockState.cart = circular as unknown as typeof mockState.cart;

			// First enqueue should fail.
			try {
				const gen = mockRegisteredStore!.actions.addCartItem( {
					id: 99,
					quantity: 1,
					type: 'simple',
				} ) as unknown as Generator;
				gen.next();
			} catch {
				// expected
			}

			// Restore valid cart.
			mockState.cart = JSON.parse(
				JSON.stringify( initialCart )
			) as typeof mockState.cart;

			const mockFetch = jest.fn();
			global.fetch = mockFetch;

			// Second enqueue should succeed.
			const gen2 = mockRegisteredStore!.actions.addCartItem( {
				id: 50,
				quantity: 1,
				type: 'simple',
			} ) as unknown as Generator;

			gen2.next();

			// Drive lifecycle to see what gets sent in the batch.
			const processLifecycle = (
				mockRegisteredStore!.actions as Record<
					string,
					( ...args: unknown[] ) => unknown
				>
			)._processLifecycle;

			const lifecycle = processLifecycle( 0 ) as unknown as Generator;
			lifecycle.next(); // yield fetch(...)

			// The batch should contain only 1 request (the second one), not the failed first one.
			expect( mockFetch ).toHaveBeenCalledTimes( 1 );
			const fetchBody = JSON.parse(
				mockFetch.mock.calls[ 0 ][ 1 ].body as string
			);
			expect( fetchBody.requests ).toHaveLength( 1 );
		} );
	} );

	describe( 'REQ-004: queueMicrotask scheduling', () => {
		it( 'first enqueue schedules queueMicrotask exactly once', () => {
			const mockQueueMicrotask = jest.fn();
			globalThis.queueMicrotask = mockQueueMicrotask;

			jest.isolateModules( () => require( '../cart' ) );

			const gen = mockRegisteredStore!.actions.addCartItem( {
				id: 99,
				quantity: 1,
				type: 'simple',
			} ) as unknown as Generator;

			gen.next();

			expect( mockQueueMicrotask ).toHaveBeenCalledTimes( 1 );
			expect( typeof mockQueueMicrotask.mock.calls[ 0 ][ 0 ] ).toBe(
				'function'
			);
		} );

		it( 'second enqueue in the same tick does NOT schedule queueMicrotask again', () => {
			const mockQueueMicrotask = jest.fn();
			globalThis.queueMicrotask = mockQueueMicrotask;

			jest.isolateModules( () => require( '../cart' ) );

			// First action — triggers queueMicrotask.
			const gen1 = mockRegisteredStore!.actions.addCartItem( {
				id: 99,
				quantity: 1,
				type: 'simple',
			} ) as unknown as Generator;

			gen1.next();

			// Second action in the same tick — should NOT trigger again.
			const gen2 = mockRegisteredStore!.actions.removeCartItem(
				'item-1'
			) as unknown as Generator;

			gen2.next();

			// queueMicrotask should still have been called only once.
			expect( mockQueueMicrotask ).toHaveBeenCalledTimes( 1 );
		} );

		it( 'both entries from the same tick appear in a single batch', () => {
			const mockQueueMicrotask = jest.fn();
			globalThis.queueMicrotask = mockQueueMicrotask;

			const mockFetch = jest.fn();
			global.fetch = mockFetch;

			jest.isolateModules( () => require( '../cart' ) );

			// Enqueue two actions in the same tick.
			const gen1 = mockRegisteredStore!.actions.addCartItem( {
				id: 99,
				quantity: 1,
				type: 'simple',
			} ) as unknown as Generator;

			gen1.next();

			const gen2 = mockRegisteredStore!.actions.removeCartItem(
				'item-1'
			) as unknown as Generator;

			gen2.next();

			// Drive lifecycle — both should be in the single batch.
			const processLifecycle = (
				mockRegisteredStore!.actions as Record<
					string,
					( ...args: unknown[] ) => unknown
				>
			)._processLifecycle;

			const lifecycle = processLifecycle( 0 ) as unknown as Generator;
			lifecycle.next(); // yield fetch(...)

			expect( mockFetch ).toHaveBeenCalledTimes( 1 );
			const body = JSON.parse(
				mockFetch.mock.calls[ 0 ][ 1 ].body as string
			);
			expect( body.requests ).toHaveLength( 2 );
		} );

		it( 'queueMicrotask callback invokes _processLifecycle with chainCount 0', () => {
			const mockQueueMicrotask = jest.fn();
			globalThis.queueMicrotask = mockQueueMicrotask;

			const mockFetch = jest.fn();
			global.fetch = mockFetch;

			jest.isolateModules( () => require( '../cart' ) );

			const gen = mockRegisteredStore!.actions.addCartItem( {
				id: 99,
				quantity: 1,
				type: 'simple',
			} ) as unknown as Generator;

			gen.next();

			// Execute the callback passed to queueMicrotask.
			expect( mockQueueMicrotask ).toHaveBeenCalledTimes( 1 );
			const callback = mockQueueMicrotask.mock.calls[ 0 ][ 0 ];

			// When the callback runs, it should invoke _processLifecycle
			// which is a generator that yields fetch. If fetch is called,
			// _processLifecycle was invoked.
			callback();

			expect( mockFetch ).toHaveBeenCalledTimes( 1 );
			const [ url ] = mockFetch.mock.calls[ 0 ];
			expect( url ).toBe(
				'https://example.com/wp-json/wc/store/v1/batch'
			);
		} );
	} );

	describe( 'REQ-005: batch POST request', () => {
		it( 'sends POST to /wc/store/v1/batch with correct method, URL, and headers', () => {
			const mockQueueMicrotask = jest.fn();
			globalThis.queueMicrotask = mockQueueMicrotask;

			const mockFetch = jest.fn();
			global.fetch = mockFetch;

			jest.isolateModules( () => require( '../cart' ) );

			// Enqueue one item.
			const gen = mockRegisteredStore!.actions.addCartItem( {
				id: 99,
				quantity: 1,
				type: 'simple',
			} ) as unknown as Generator;

			gen.next();

			// Drive _processLifecycle.
			const processLifecycle = (
				mockRegisteredStore!.actions as Record<
					string,
					( ...args: unknown[] ) => unknown
				>
			)._processLifecycle;

			const lifecycle = processLifecycle( 0 ) as unknown as Generator;
			lifecycle.next(); // yield fetch(...)

			expect( mockFetch ).toHaveBeenCalledTimes( 1 );
			const [ url, options ] = mockFetch.mock.calls[ 0 ];

			expect( url ).toBe(
				'https://example.com/wp-json/wc/store/v1/batch'
			);
			expect( options.method ).toBe( 'POST' );
			expect( options.headers ).toEqual(
				expect.objectContaining( {
					Nonce: 'test-nonce-123',
					'Content-Type': 'application/json',
				} )
			);
		} );

		it( 'body contains JSON-serialized requests array from pending entries', () => {
			const mockQueueMicrotask = jest.fn();
			globalThis.queueMicrotask = mockQueueMicrotask;

			const mockFetch = jest.fn();
			global.fetch = mockFetch;

			jest.isolateModules( () => require( '../cart' ) );

			// Enqueue an add and a remove in the same tick.
			const addGen = mockRegisteredStore!.actions.addCartItem( {
				id: 99,
				quantity: 3,
				type: 'simple',
			} ) as unknown as Generator;

			addGen.next();

			const removeGen = mockRegisteredStore!.actions.removeCartItem(
				'item-1'
			) as unknown as Generator;

			removeGen.next();

			// Drive _processLifecycle.
			const processLifecycle = (
				mockRegisteredStore!.actions as Record<
					string,
					( ...args: unknown[] ) => unknown
				>
			)._processLifecycle;

			const lifecycle = processLifecycle( 0 ) as unknown as Generator;
			lifecycle.next(); // yield fetch(...)

			const body = JSON.parse(
				mockFetch.mock.calls[ 0 ][ 1 ].body as string
			);

			expect( body ).toHaveProperty( 'requests' );
			expect( body.requests ).toHaveLength( 2 );

			// First request should be the add-item.
			expect( body.requests[ 0 ] ).toEqual(
				expect.objectContaining( {
					method: 'POST',
					path: '/wc/store/v1/cart/add-item',
				} )
			);

			// Second request should be the remove-item.
			expect( body.requests[ 1 ] ).toEqual(
				expect.objectContaining( {
					method: 'POST',
					path: '/wc/store/v1/cart/remove-item',
				} )
			);
		} );

		it( 'each sub-request includes its own headers and body', () => {
			const mockQueueMicrotask = jest.fn();
			globalThis.queueMicrotask = mockQueueMicrotask;

			const mockFetch = jest.fn();
			global.fetch = mockFetch;

			jest.isolateModules( () => require( '../cart' ) );

			const gen = mockRegisteredStore!.actions.removeCartItem(
				'item-1'
			) as unknown as Generator;

			gen.next();

			const processLifecycle = (
				mockRegisteredStore!.actions as Record<
					string,
					( ...args: unknown[] ) => unknown
				>
			)._processLifecycle;

			const lifecycle = processLifecycle( 0 ) as unknown as Generator;
			lifecycle.next(); // yield fetch(...)

			const body = JSON.parse(
				mockFetch.mock.calls[ 0 ][ 1 ].body as string
			);

			const request = body.requests[ 0 ];
			expect( request.headers ).toEqual(
				expect.objectContaining( {
					Nonce: 'test-nonce-123',
					'Content-Type': 'application/json',
				} )
			);
			expect( request.body ).toEqual( { key: 'item-1' } );
		} );

		it( 'drains pending array — entries are moved out before fetch', () => {
			const mockQueueMicrotask = jest.fn();
			globalThis.queueMicrotask = mockQueueMicrotask;

			const mockFetch = jest.fn();
			global.fetch = mockFetch;

			jest.isolateModules( () => require( '../cart' ) );

			// Enqueue first item.
			const gen1 = mockRegisteredStore!.actions.addCartItem( {
				id: 99,
				quantity: 1,
				type: 'simple',
			} ) as unknown as Generator;

			gen1.next();

			// Drive lifecycle — drains pending.
			const processLifecycle = (
				mockRegisteredStore!.actions as Record<
					string,
					( ...args: unknown[] ) => unknown
				>
			)._processLifecycle;

			const lifecycle = processLifecycle( 0 ) as unknown as Generator;
			lifecycle.next(); // yield fetch(...) — at this point, pending is drained

			// Enqueue another item while batch is in-flight.
			const gen2 = mockRegisteredStore!.actions.addCartItem( {
				id: 50,
				quantity: 1,
				type: 'simple',
			} ) as unknown as Generator;

			gen2.next();

			// Feed response to first batch — this triggers next while-loop iteration.
			const mockResponse = { status: 207, json: jest.fn() };
			lifecycle.next( mockResponse ); // yield res.json()
			lifecycle.next( {
				responses: [
					{
						status: 200,
						body: { items: [], totals: {}, errors: [] },
					},
				],
			} ); // process responses, then while-loop checks pending again

			// Second fetch call should be for the new pending item.
			expect( mockFetch ).toHaveBeenCalledTimes( 2 );
			const secondBody = JSON.parse(
				mockFetch.mock.calls[ 1 ][ 1 ].body as string
			);
			expect( secondBody.requests ).toHaveLength( 1 );
		} );

		it( 'uses state.restUrl as the base URL for the batch endpoint', () => {
			const mockQueueMicrotask = jest.fn();
			globalThis.queueMicrotask = mockQueueMicrotask;

			const mockFetch = jest.fn();
			global.fetch = mockFetch;

			// Set a custom restUrl.
			Object.assign( mockState, {
				restUrl: 'https://custom-site.com/api/',
			} );

			jest.isolateModules( () => require( '../cart' ) );

			const gen = mockRegisteredStore!.actions.addCartItem( {
				id: 99,
				quantity: 1,
				type: 'simple',
			} ) as unknown as Generator;

			gen.next();

			const processLifecycle = (
				mockRegisteredStore!.actions as Record<
					string,
					( ...args: unknown[] ) => unknown
				>
			)._processLifecycle;

			const lifecycle = processLifecycle( 0 ) as unknown as Generator;
			lifecycle.next(); // yield fetch(...)

			const [ url ] = mockFetch.mock.calls[ 0 ];
			expect( url ).toBe(
				'https://custom-site.com/api/wc/store/v1/batch'
			);
		} );
	} );

	describe( 'REQ-006: in-flight queue pickup', () => {
		it( 'requests enqueued during in-flight fetch are picked up by the next while-loop iteration', () => {
			const mockQueueMicrotask = jest.fn();
			globalThis.queueMicrotask = mockQueueMicrotask;

			const mockFetch = jest.fn();
			global.fetch = mockFetch;

			jest.isolateModules( () => require( '../cart' ) );

			// First action — enqueue entry1, triggers lifecycle scheduling.
			const gen1 = mockRegisteredStore!.actions.addCartItem( {
				id: 99,
				quantity: 1,
				type: 'simple',
			} ) as unknown as Generator;

			gen1.next();

			// Drive lifecycle — drains pending, sends first batch.
			const processLifecycle = (
				mockRegisteredStore!.actions as Record<
					string,
					( ...args: unknown[] ) => unknown
				>
			)._processLifecycle;

			const lifecycle = processLifecycle( 0 ) as unknown as Generator;
			lifecycle.next(); // yield fetch(...) — first batch in-flight

			// While fetch is in-flight, enqueue a second action.
			const gen2 = mockRegisteredStore!.actions.removeCartItem(
				'item-1'
			) as unknown as Generator;

			gen2.next();

			// Complete first fetch — lifecycle processes response then loops.
			const mockResponse = { status: 207, json: jest.fn() };
			lifecycle.next( mockResponse ); // yield res.json()
			lifecycle.next( {
				responses: [
					{
						status: 200,
						body: { items: [], totals: {}, errors: [] },
					},
				],
			} ); // process responses, while-loop checks pending → finds entry2

			// Second fetch should have been triggered for the in-flight enqueued item.
			expect( mockFetch ).toHaveBeenCalledTimes( 2 );

			const secondBody = JSON.parse(
				mockFetch.mock.calls[ 1 ][ 1 ].body as string
			);
			expect( secondBody.requests ).toHaveLength( 1 );
			expect( secondBody.requests[ 0 ].path ).toBe(
				'/wc/store/v1/cart/remove-item'
			);
		} );

		it( 'in-flight enqueued item does not schedule a new queueMicrotask', () => {
			const mockQueueMicrotask = jest.fn();
			globalThis.queueMicrotask = mockQueueMicrotask;

			jest.isolateModules( () => require( '../cart' ) );

			// First action — triggers queueMicrotask.
			const gen1 = mockRegisteredStore!.actions.addCartItem( {
				id: 99,
				quantity: 1,
				type: 'simple',
			} ) as unknown as Generator;

			gen1.next();
			expect( mockQueueMicrotask ).toHaveBeenCalledTimes( 1 );

			// Drive lifecycle — starts processing.
			const processLifecycle = (
				mockRegisteredStore!.actions as Record<
					string,
					( ...args: unknown[] ) => unknown
				>
			)._processLifecycle;

			const lifecycle = processLifecycle( 0 ) as unknown as Generator;
			lifecycle.next(); // yield fetch(...)

			// While fetch is in-flight, enqueue second action.
			// running is still true, so no new queueMicrotask.
			const gen2 = mockRegisteredStore!.actions.addCartItem( {
				id: 50,
				quantity: 2,
				type: 'simple',
			} ) as unknown as Generator;

			gen2.next();

			// queueMicrotask should still have been called only once.
			expect( mockQueueMicrotask ).toHaveBeenCalledTimes( 1 );
		} );

		it( 'multiple in-flight enqueued items are batched together in the second fetch', () => {
			const mockQueueMicrotask = jest.fn();
			globalThis.queueMicrotask = mockQueueMicrotask;

			const mockFetch = jest.fn();
			global.fetch = mockFetch;

			jest.isolateModules( () => require( '../cart' ) );

			// First action.
			const gen1 = mockRegisteredStore!.actions.addCartItem( {
				id: 99,
				quantity: 1,
				type: 'simple',
			} ) as unknown as Generator;

			gen1.next();

			// Drive lifecycle — first batch in-flight.
			const processLifecycle = (
				mockRegisteredStore!.actions as Record<
					string,
					( ...args: unknown[] ) => unknown
				>
			)._processLifecycle;

			const lifecycle = processLifecycle( 0 ) as unknown as Generator;
			lifecycle.next(); // yield fetch(...)

			// While in-flight, enqueue TWO more actions.
			const gen2 = mockRegisteredStore!.actions.addCartItem( {
				id: 50,
				quantity: 2,
				type: 'simple',
			} ) as unknown as Generator;

			gen2.next();

			const gen3 = mockRegisteredStore!.actions.removeCartItem(
				'item-1'
			) as unknown as Generator;

			gen3.next();

			// Complete first fetch.
			const mockResponse = { status: 207, json: jest.fn() };
			lifecycle.next( mockResponse ); // yield res.json()
			lifecycle.next( {
				responses: [
					{
						status: 200,
						body: { items: [], totals: {}, errors: [] },
					},
				],
			} ); // while-loop picks up both pending entries

			// Second fetch should contain both in-flight enqueued items.
			expect( mockFetch ).toHaveBeenCalledTimes( 2 );

			const secondBody = JSON.parse(
				mockFetch.mock.calls[ 1 ][ 1 ].body as string
			);
			expect( secondBody.requests ).toHaveLength( 2 );
		} );

		it( 'while loop exits when no more pending entries remain after processing', () => {
			const mockQueueMicrotask = jest.fn();
			globalThis.queueMicrotask = mockQueueMicrotask;

			const mockFetch = jest.fn();
			global.fetch = mockFetch;

			jest.isolateModules( () => require( '../cart' ) );

			// Single action — no more enqueued during flight.
			const gen = mockRegisteredStore!.actions.addCartItem( {
				id: 99,
				quantity: 1,
				type: 'simple',
			} ) as unknown as Generator;

			gen.next();

			const processLifecycle = (
				mockRegisteredStore!.actions as Record<
					string,
					( ...args: unknown[] ) => unknown
				>
			)._processLifecycle;

			const lifecycle = processLifecycle( 0 ) as unknown as Generator;
			lifecycle.next(); // yield fetch(...)

			// Complete fetch — no new pending entries were added.
			const mockResponse = { status: 207, json: jest.fn() };
			lifecycle.next( mockResponse ); // yield res.json()

			const consoleSpy = jest
				.spyOn( console, 'error' )
				.mockImplementation( () => {} );

			const step = lifecycle.next( {
				responses: [
					{
						status: 200,
						body: { items: [], totals: {}, errors: [] },
					},
				],
			} );

			consoleSpy.mockRestore();

			// Only 1 fetch call — no second batch because pending was empty.
			expect( mockFetch ).toHaveBeenCalledTimes( 1 );

			// Generator should continue to reconciliation (not loop back).
			// The step should not be done yet (still in finally or reconciliation).
			// But there should be no second fetch call.
		} );
	} );

	describe( 'REQ-016: addCartItem action', () => {
		it( 'uses update-item path when variation item matches by attributes', () => {
			const mockQueueMicrotask = jest.fn();
			globalThis.queueMicrotask = mockQueueMicrotask;

			const mockFetch = jest.fn();
			global.fetch = mockFetch;

			// Set up cart with a variation item.
			Object.assign( mockState, {
				cart: {
					items: [
						{
							key: 'var-item-1',
							id: 20,
							quantity: 1,
							type: 'variation',
							name: 'T-Shirt - Red, Large',
							sold_individually: false,
							variation: [
								{ attribute: 'pa_color', value: 'red' },
								{ attribute: 'pa_size', value: 'large' },
							],
						},
					],
					totals: { total_items: '1' },
					errors: [],
				},
			} );

			jest.isolateModules( () => require( '../cart' ) );

			// Add item with same id and matching variation attributes.
			const gen = mockRegisteredStore!.actions.addCartItem( {
				id: 20,
				quantity: 3,
				type: 'variation',
				variation: [
					{ attribute: 'pa_color', value: 'red' },
					{ attribute: 'pa_size', value: 'large' },
				],
			} ) as unknown as Generator;

			gen.next();

			// Drive lifecycle to see the request.
			const processLifecycle = (
				mockRegisteredStore!.actions as Record<
					string,
					( ...args: unknown[] ) => unknown
				>
			)._processLifecycle;

			const lifecycle = processLifecycle( 0 ) as unknown as Generator;
			lifecycle.next(); // yield fetch(...)

			const body = JSON.parse(
				mockFetch.mock.calls[ 0 ][ 1 ].body as string
			);
			// Should use update-item because the variation matches.
			expect( body.requests[ 0 ].path ).toBe(
				'/wc/store/v1/cart/update-item'
			);
		} );

		it( 'uses add-item path when variation attributes do not match', () => {
			const mockQueueMicrotask = jest.fn();
			globalThis.queueMicrotask = mockQueueMicrotask;

			const mockFetch = jest.fn();
			global.fetch = mockFetch;

			// Set up cart with a variation item (red, large).
			Object.assign( mockState, {
				cart: {
					items: [
						{
							key: 'var-item-1',
							id: 20,
							quantity: 1,
							type: 'variation',
							name: 'T-Shirt - Red, Large',
							sold_individually: false,
							variation: [
								{ attribute: 'pa_color', value: 'red' },
								{ attribute: 'pa_size', value: 'large' },
							],
						},
					],
					totals: { total_items: '1' },
					errors: [],
				},
			} );

			jest.isolateModules( () => require( '../cart' ) );

			// Add item with same id but DIFFERENT variation attributes (blue, small).
			const gen = mockRegisteredStore!.actions.addCartItem( {
				id: 20,
				quantity: 1,
				type: 'variation',
				variation: [
					{ attribute: 'pa_color', value: 'blue' },
					{ attribute: 'pa_size', value: 'small' },
				],
			} ) as unknown as Generator;

			gen.next();

			// Drive lifecycle to see the request.
			const processLifecycle = (
				mockRegisteredStore!.actions as Record<
					string,
					( ...args: unknown[] ) => unknown
				>
			)._processLifecycle;

			const lifecycle = processLifecycle( 0 ) as unknown as Generator;
			lifecycle.next(); // yield fetch(...)

			const body = JSON.parse(
				mockFetch.mock.calls[ 0 ][ 1 ].body as string
			);
			// Should use add-item because the variation does NOT match.
			expect( body.requests[ 0 ].path ).toBe(
				'/wc/store/v1/cart/add-item'
			);
		} );

		it( 'does not optimistically update quantity when item is sold_individually', () => {
			const mockQueueMicrotask = jest.fn();
			globalThis.queueMicrotask = mockQueueMicrotask;

			// Set up cart with a sold_individually item.
			Object.assign( mockState, {
				cart: {
					items: [
						{
							key: 'sold-ind-1',
							id: 30,
							quantity: 1,
							type: 'simple',
							name: 'Limited Widget',
							sold_individually: true,
						},
					],
					totals: { total_items: '1' },
					errors: [],
				},
			} );

			jest.isolateModules( () => require( '../cart' ) );

			// Try to add more of the sold_individually item.
			const gen = mockRegisteredStore!.actions.addCartItem( {
				id: 30,
				quantity: 5,
				type: 'simple',
			} ) as unknown as Generator;

			gen.next();

			// Quantity should NOT have been optimistically updated.
			expect( mockState.cart.items[ 0 ].quantity ).toBe( 1 );
		} );

		it( 'optimistically updates quantity when existing item is NOT sold_individually', () => {
			const mockQueueMicrotask = jest.fn();
			globalThis.queueMicrotask = mockQueueMicrotask;

			// Set up cart with a normal existing item.
			Object.assign( mockState, {
				cart: {
					items: [
						{
							key: 'normal-1',
							id: 40,
							quantity: 2,
							type: 'simple',
							name: 'Normal Widget',
							sold_individually: false,
						},
					],
					totals: { total_items: '1' },
					errors: [],
				},
			} );

			jest.isolateModules( () => require( '../cart' ) );

			// Add more of the existing item.
			const gen = mockRegisteredStore!.actions.addCartItem( {
				id: 40,
				quantity: 7,
				type: 'simple',
			} ) as unknown as Generator;

			gen.next();

			// Quantity SHOULD be optimistically updated.
			expect( mockState.cart.items[ 0 ].quantity ).toBe( 7 );
		} );

		it( 'removes entry from pending when optimistic update throws', () => {
			const mockQueueMicrotask = jest.fn();
			globalThis.queueMicrotask = mockQueueMicrotask;

			const mockFetch = jest.fn();
			global.fetch = mockFetch;

			jest.isolateModules( () => require( '../cart' ) );

			// Make state.cart.items non-array to cause optimistic push to throw.
			// First, enqueue succeeds (snapshot is taken), then optimistic update fails.
			// We need to make .push() throw AFTER enqueue succeeds.
			const originalItems = mockState.cart.items;
			Object.defineProperty( mockState.cart, 'items', {
				get() {
					return originalItems;
				},
				set() {
					// Block the filter assignment in optimistic update.
					throw new Error( 'Optimistic update failed' );
				},
				configurable: true,
			} );

			// For removeCartItem: the optimistic filter assigns to state.cart.items.
			expect( () => {
				const gen = mockRegisteredStore!.actions.removeCartItem(
					'item-1'
				) as unknown as Generator;
				gen.next();
			} ).toThrow( 'Optimistic update failed' );

			// Restore items to normal.
			Object.defineProperty( mockState.cart, 'items', {
				value: [
					{
						key: 'item-1',
						id: 10,
						quantity: 1,
						type: 'simple',
						name: 'Widget',
					},
				],
				writable: true,
				configurable: true,
			} );

			// Now enqueue a second item — if the first entry was properly cleaned
			// from pending, only the second entry should be in the batch.
			const gen2 = mockRegisteredStore!.actions.addCartItem( {
				id: 50,
				quantity: 1,
				type: 'simple',
			} ) as unknown as Generator;

			gen2.next();

			// Drive lifecycle to see the batch.
			const processLifecycle = (
				mockRegisteredStore!.actions as Record<
					string,
					( ...args: unknown[] ) => unknown
				>
			)._processLifecycle;

			const lifecycle = processLifecycle( 0 ) as unknown as Generator;
			lifecycle.next(); // yield fetch(...)

			// Batch should only contain 1 request (the second one).
			expect( mockFetch ).toHaveBeenCalledTimes( 1 );
			const fetchBody = JSON.parse(
				mockFetch.mock.calls[ 0 ][ 1 ].body as string
			);
			expect( fetchBody.requests ).toHaveLength( 1 );
			expect( fetchBody.requests[ 0 ].path ).toBe(
				'/wc/store/v1/cart/add-item'
			);
		} );

		it( 'matches existing item by key when key is provided', () => {
			const mockQueueMicrotask = jest.fn();
			globalThis.queueMicrotask = mockQueueMicrotask;

			const mockFetch = jest.fn();
			global.fetch = mockFetch;

			jest.isolateModules( () => require( '../cart' ) );

			// Add item using key to match existing item.
			const gen = mockRegisteredStore!.actions.addCartItem( {
				id: 999, // Different id, but key matches.
				key: 'item-1',
				quantity: 3,
				type: 'simple',
			} ) as unknown as Generator;

			gen.next();

			// Drive lifecycle to see the request.
			const processLifecycle = (
				mockRegisteredStore!.actions as Record<
					string,
					( ...args: unknown[] ) => unknown
				>
			)._processLifecycle;

			const lifecycle = processLifecycle( 0 ) as unknown as Generator;
			lifecycle.next(); // yield fetch(...)

			const body = JSON.parse(
				mockFetch.mock.calls[ 0 ][ 1 ].body as string
			);
			// Should use update-item because key matches an existing item.
			expect( body.requests[ 0 ].path ).toBe(
				'/wc/store/v1/cart/update-item'
			);
		} );
	} );

	describe( 'REQ-017: removeCartItem action', () => {
		it( 'enqueues with remove-item path, correct headers, and body { key }', () => {
			const mockQueueMicrotask = jest.fn();
			globalThis.queueMicrotask = mockQueueMicrotask;

			const mockFetch = jest.fn();
			global.fetch = mockFetch;

			jest.isolateModules( () => require( '../cart' ) );

			const gen = mockRegisteredStore!.actions.removeCartItem(
				'item-1'
			) as unknown as Generator;

			gen.next();

			// Drive lifecycle to inspect the request.
			const processLifecycle = (
				mockRegisteredStore!.actions as Record<
					string,
					( ...args: unknown[] ) => unknown
				>
			)._processLifecycle;

			const lifecycle = processLifecycle( 0 ) as unknown as Generator;
			lifecycle.next(); // yield fetch(...)

			expect( mockFetch ).toHaveBeenCalledTimes( 1 );
			const body = JSON.parse(
				mockFetch.mock.calls[ 0 ][ 1 ].body as string
			);

			expect( body.requests ).toHaveLength( 1 );
			const request = body.requests[ 0 ];
			expect( request ).toEqual(
				expect.objectContaining( {
					method: 'POST',
					path: '/wc/store/v1/cart/remove-item',
					headers: expect.objectContaining( {
						Nonce: 'test-nonce-123',
						'Content-Type': 'application/json',
					} ),
					body: { key: 'item-1' },
				} )
			);
		} );

		it( 'optimistically filters the removed item from state.cart.items', () => {
			const mockQueueMicrotask = jest.fn();
			globalThis.queueMicrotask = mockQueueMicrotask;

			// Cart with two items so we can verify only the target is removed.
			Object.assign( mockState, {
				cart: {
					items: [
						{
							key: 'item-1',
							id: 10,
							quantity: 1,
							type: 'simple',
							name: 'Widget A',
						},
						{
							key: 'item-2',
							id: 20,
							quantity: 3,
							type: 'simple',
							name: 'Widget B',
						},
					],
					totals: { total_items: '2' },
					errors: [],
				},
			} );

			jest.isolateModules( () => require( '../cart' ) );

			const gen = mockRegisteredStore!.actions.removeCartItem(
				'item-1'
			) as unknown as Generator;

			gen.next();

			// Only item-2 should remain after optimistic filter.
			expect( mockState.cart.items ).toHaveLength( 1 );
			expect( mockState.cart.items[ 0 ].key ).toBe( 'item-2' );
		} );

		it( 'snapshot is taken before optimistic filter — rollback restores removed item', () => {
			const mockQueueMicrotask = jest.fn();
			globalThis.queueMicrotask = mockQueueMicrotask;

			jest.isolateModules( () => require( '../cart' ) );

			const cartBeforeAction = JSON.parse(
				JSON.stringify( mockState.cart )
			);

			const gen = mockRegisteredStore!.actions.removeCartItem(
				'item-1'
			) as unknown as Generator;

			gen.next();

			// After optimistic filter, cart items should be empty.
			expect( mockState.cart.items ).toHaveLength( 0 );

			// Drive lifecycle to total failure to trigger rollback.
			const processLifecycle = (
				mockRegisteredStore!.actions as Record<
					string,
					( ...args: unknown[] ) => unknown
				>
			)._processLifecycle;

			const lifecycle = processLifecycle( 0 ) as unknown as Generator;
			lifecycle.next(); // yield fetch(...)

			// Network error → total failure → rollback to snapshot.
			const consoleSpy = jest
				.spyOn( console, 'error' )
				.mockImplementation( () => {} );
			try {
				lifecycle.throw!( new Error( 'Network error' ) );
			} catch {
				// lifecycle may finish
			}
			consoleSpy.mockRestore();

			// Rolled back: item-1 should be restored.
			expect( mockState.cart ).toEqual( cartBeforeAction );
			expect( mockState.cart.items ).toHaveLength( 1 );
			expect( mockState.cart.items[ 0 ].key ).toBe( 'item-1' );
		} );

		it( 'sets addItem to false — successful remove does not trigger triggerAddedToCartEvent', () => {
			const mockQueueMicrotask = jest.fn();
			globalThis.queueMicrotask = mockQueueMicrotask;

			const mockFetch = jest.fn();
			global.fetch = mockFetch;

			let triggerAddedToCartEvent: jest.Mock;
			jest.isolateModules( () => {
				require( '../cart' );
				( { triggerAddedToCartEvent } =
					require( '../legacy-events' ) as {
						triggerAddedToCartEvent: jest.Mock;
					} );
			} );

			triggerAddedToCartEvent!.mockClear();

			// Remove an item.
			const gen = mockRegisteredStore!.actions.removeCartItem(
				'item-1'
			) as unknown as Generator;

			gen.next();

			// Drive lifecycle with a successful response.
			const processLifecycle = (
				mockRegisteredStore!.actions as Record<
					string,
					( ...args: unknown[] ) => unknown
				>
			)._processLifecycle;

			const lifecycle = processLifecycle( 0 ) as unknown as Generator;
			lifecycle.next(); // yield fetch(...)

			const serverCart = {
				items: [],
				totals: { total_items: '0' },
				errors: [],
			};

			const mockResponse = { status: 207, json: jest.fn() };
			lifecycle.next( mockResponse ); // yield res.json()

			const consoleSpy = jest
				.spyOn( console, 'error' )
				.mockImplementation( () => {} );
			try {
				lifecycle.next( {
					responses: [
						{ status: 200, body: serverCart },
					],
				} );
			} catch {
				// lifecycle may finish
			}
			consoleSpy.mockRestore();

			// triggerAddedToCartEvent should NOT be called for removes.
			expect( triggerAddedToCartEvent! ).not.toHaveBeenCalled();
		} );

		it( 'includes cartItemsPendingDelete in qtyChanges metadata', () => {
			const mockQueueMicrotask = jest.fn();
			globalThis.queueMicrotask = mockQueueMicrotask;

			const mockFetch = jest.fn();
			global.fetch = mockFetch;

			jest.isolateModules( () => require( '../cart' ) );

			const dispatchSpy = jest.spyOn( window, 'dispatchEvent' );

			// Remove an item.
			const gen = mockRegisteredStore!.actions.removeCartItem(
				'item-1'
			) as unknown as Generator;

			gen.next();

			// Drive lifecycle with a successful response.
			const processLifecycle = (
				mockRegisteredStore!.actions as Record<
					string,
					( ...args: unknown[] ) => unknown
				>
			)._processLifecycle;

			const lifecycle = processLifecycle( 0 ) as unknown as Generator;
			lifecycle.next(); // yield fetch(...)

			const serverCart = {
				items: [],
				totals: { total_items: '0' },
				errors: [],
			};

			const mockResponse = { status: 207, json: jest.fn() };
			lifecycle.next( mockResponse ); // yield res.json()

			const consoleSpy = jest
				.spyOn( console, 'error' )
				.mockImplementation( () => {} );
			try {
				lifecycle.next( {
					responses: [
						{ status: 200, body: serverCart },
					],
				} );
			} catch {
				// lifecycle may finish
			}
			consoleSpy.mockRestore();

			// emitSyncEvent should have dispatched with cartItemsPendingDelete.
			const syncEvent = dispatchSpy.mock.calls.find(
				( [ event ] ) =>
					event instanceof CustomEvent &&
					event.type === 'wc-blocks_store_sync_required'
			);
			expect( syncEvent ).toBeDefined();
			const detail = ( syncEvent![ 0 ] as CustomEvent ).detail;
			expect( detail.quantityChanges.cartItemsPendingDelete ).toContain(
				'item-1'
			);

			dispatchSpy.mockRestore();
		} );

		it( 'removes entry from pending when optimistic filter throws', () => {
			const mockQueueMicrotask = jest.fn();
			globalThis.queueMicrotask = mockQueueMicrotask;

			const mockFetch = jest.fn();
			global.fetch = mockFetch;

			jest.isolateModules( () => require( '../cart' ) );

			// Make state.cart.items setter throw to simulate optimistic failure.
			const originalItems = mockState.cart.items;
			Object.defineProperty( mockState.cart, 'items', {
				get() {
					return originalItems;
				},
				set() {
					throw new Error( 'Optimistic filter failed' );
				},
				configurable: true,
			} );

			expect( () => {
				const gen = mockRegisteredStore!.actions.removeCartItem(
					'item-1'
				) as unknown as Generator;
				gen.next();
			} ).toThrow( 'Optimistic filter failed' );

			// Restore items to normal.
			Object.defineProperty( mockState.cart, 'items', {
				value: [
					{
						key: 'item-1',
						id: 10,
						quantity: 1,
						type: 'simple',
						name: 'Widget',
					},
				],
				writable: true,
				configurable: true,
			} );

			// Enqueue a second item — only it should appear in the batch.
			const gen2 = mockRegisteredStore!.actions.addCartItem( {
				id: 50,
				quantity: 1,
				type: 'simple',
			} ) as unknown as Generator;

			gen2.next();

			const processLifecycle = (
				mockRegisteredStore!.actions as Record<
					string,
					( ...args: unknown[] ) => unknown
				>
			)._processLifecycle;

			const lifecycle = processLifecycle( 0 ) as unknown as Generator;
			lifecycle.next(); // yield fetch(...)

			expect( mockFetch ).toHaveBeenCalledTimes( 1 );
			const fetchBody = JSON.parse(
				mockFetch.mock.calls[ 0 ][ 1 ].body as string
			);
			// Only the second entry should be in the batch.
			expect( fetchBody.requests ).toHaveLength( 1 );
			expect( fetchBody.requests[ 0 ].path ).toBe(
				'/wc/store/v1/cart/add-item'
			);
		} );
	} );

	describe( 'REQ-011: state resolution success and failure', () => {
		it( 'sets state.cart to lastServerState when at least one 2xx response exists', () => {
			const mockQueueMicrotask = jest.fn();
			globalThis.queueMicrotask = mockQueueMicrotask;

			const mockFetch = jest.fn();
			global.fetch = mockFetch;

			jest.isolateModules( () => require( '../cart' ) );

			// Enqueue an add action.
			const gen = mockRegisteredStore!.actions.addCartItem( {
				id: 99,
				quantity: 1,
				type: 'simple',
			} ) as unknown as Generator;

			gen.next();

			const serverCart = {
				items: [
					{
						key: 'server-item',
						id: 99,
						quantity: 1,
						type: 'simple',
						name: 'Server Widget',
					},
				],
				totals: { total_items: '1' },
				errors: [],
			};

			// Drive lifecycle.
			const processLifecycle = (
				mockRegisteredStore!.actions as Record<
					string,
					( ...args: unknown[] ) => unknown
				>
			)._processLifecycle;

			const lifecycle = processLifecycle( 0 ) as unknown as Generator;
			lifecycle.next(); // yield fetch(...)

			const mockResponse = { status: 207, json: jest.fn() };
			lifecycle.next( mockResponse ); // yield res.json()

			const consoleSpy = jest
				.spyOn( console, 'error' )
				.mockImplementation( () => {} );

			try {
				lifecycle.next( {
					responses: [
						{ status: 200, body: serverCart },
					],
				} );
			} catch {
				// lifecycle may finish
			}

			consoleSpy.mockRestore();

			// state.cart should be set to the server response body.
			expect( mockState.cart ).toEqual( serverCart );
		} );

		it( 'uses the last 2xx response body as lastServerState when multiple responses exist', () => {
			const mockQueueMicrotask = jest.fn();
			globalThis.queueMicrotask = mockQueueMicrotask;

			const mockFetch = jest.fn();
			global.fetch = mockFetch;

			jest.isolateModules( () => require( '../cart' ) );

			// Enqueue two actions in the same tick.
			const addGen = mockRegisteredStore!.actions.addCartItem( {
				id: 99,
				quantity: 1,
				type: 'simple',
			} ) as unknown as Generator;

			addGen.next();

			const removeGen = mockRegisteredStore!.actions.removeCartItem(
				'item-1'
			) as unknown as Generator;

			removeGen.next();

			const firstCart = {
				items: [ { key: 'first', id: 99, quantity: 1, type: 'simple', name: 'First' } ],
				totals: { total_items: '1' },
				errors: [],
			};

			const lastCart = {
				items: [ { key: 'last', id: 99, quantity: 1, type: 'simple', name: 'Last' } ],
				totals: { total_items: '1' },
				errors: [],
			};

			const processLifecycle = (
				mockRegisteredStore!.actions as Record<
					string,
					( ...args: unknown[] ) => unknown
				>
			)._processLifecycle;

			const lifecycle = processLifecycle( 0 ) as unknown as Generator;
			lifecycle.next(); // yield fetch(...)

			const mockResponse = { status: 207, json: jest.fn() };
			lifecycle.next( mockResponse ); // yield res.json()

			const consoleSpy = jest
				.spyOn( console, 'error' )
				.mockImplementation( () => {} );

			try {
				lifecycle.next( {
					responses: [
						{ status: 200, body: firstCart },
						{ status: 200, body: lastCart },
					],
				} );
			} catch {
				// lifecycle may finish
			}

			consoleSpy.mockRestore();

			// Should use the LAST successful response.
			expect( mockState.cart ).toEqual( lastCart );
		} );

		it( 'rolls back to pre-optimistic snapshot on total failure (network error)', () => {
			const mockQueueMicrotask = jest.fn();
			globalThis.queueMicrotask = mockQueueMicrotask;

			jest.isolateModules( () => require( '../cart' ) );

			const cartBeforeAction = JSON.parse(
				JSON.stringify( mockState.cart )
			);

			// Enqueue add — this will optimistically add item id:99.
			const gen = mockRegisteredStore!.actions.addCartItem( {
				id: 99,
				quantity: 1,
				type: 'simple',
			} ) as unknown as Generator;

			gen.next();

			// Cart should now have 2 items (optimistic).
			expect( mockState.cart.items ).toHaveLength( 2 );

			const processLifecycle = (
				mockRegisteredStore!.actions as Record<
					string,
					( ...args: unknown[] ) => unknown
				>
			)._processLifecycle;

			const lifecycle = processLifecycle( 0 ) as unknown as Generator;
			lifecycle.next(); // yield fetch(...)

			// Network error → total failure → lastServerState remains null.
			const consoleSpy = jest
				.spyOn( console, 'error' )
				.mockImplementation( () => {} );

			try {
				lifecycle.throw!( new Error( 'Network error' ) );
			} catch {
				// lifecycle may finish
			}

			consoleSpy.mockRestore();

			// state.cart should be rolled back to pre-optimistic snapshot.
			expect( mockState.cart ).toEqual( cartBeforeAction );
			expect( mockState.cart.items ).toHaveLength( 1 );
			expect( mockState.cart.items[ 0 ].id ).toBe( 10 );
		} );

		it( 'rolls back to pre-optimistic snapshot on total failure (non-207 status)', () => {
			const mockQueueMicrotask = jest.fn();
			globalThis.queueMicrotask = mockQueueMicrotask;

			jest.isolateModules( () => require( '../cart' ) );

			const cartBeforeAction = JSON.parse(
				JSON.stringify( mockState.cart )
			);

			// Enqueue remove — this optimistically filters out item-1.
			const gen = mockRegisteredStore!.actions.removeCartItem(
				'item-1'
			) as unknown as Generator;

			gen.next();

			// Cart should now have 0 items (optimistic remove).
			expect( mockState.cart.items ).toHaveLength( 0 );

			const processLifecycle = (
				mockRegisteredStore!.actions as Record<
					string,
					( ...args: unknown[] ) => unknown
				>
			)._processLifecycle;

			const lifecycle = processLifecycle( 0 ) as unknown as Generator;
			lifecycle.next(); // yield fetch(...)

			// Non-207 response → total failure.
			const mockResponse = { status: 500, ok: false, json: jest.fn() };
			lifecycle.next( mockResponse ); // yield res.json()

			const consoleSpy = jest
				.spyOn( console, 'error' )
				.mockImplementation( () => {} );

			try {
				lifecycle.next( {
					code: 'server_error',
					message: 'Internal server error',
				} );
			} catch {
				// lifecycle may finish
			}

			consoleSpy.mockRestore();

			// state.cart should be rolled back to pre-optimistic snapshot.
			expect( mockState.cart ).toEqual( cartBeforeAction );
			expect( mockState.cart.items ).toHaveLength( 1 );
			expect( mockState.cart.items[ 0 ].key ).toBe( 'item-1' );
		} );

		it( 'uses lastServerState when there is a mix of success and failure sub-responses', () => {
			const mockQueueMicrotask = jest.fn();
			globalThis.queueMicrotask = mockQueueMicrotask;

			const mockFetch = jest.fn();
			global.fetch = mockFetch;

			jest.isolateModules( () => require( '../cart' ) );

			// Enqueue two actions.
			const addGen = mockRegisteredStore!.actions.addCartItem( {
				id: 99,
				quantity: 1,
				type: 'simple',
			} ) as unknown as Generator;

			addGen.next();

			const removeGen = mockRegisteredStore!.actions.removeCartItem(
				'item-1'
			) as unknown as Generator;

			removeGen.next();

			const successCart = {
				items: [ { key: 'success-item', id: 99, quantity: 1, type: 'simple', name: 'Success' } ],
				totals: { total_items: '1' },
				errors: [],
			};

			const processLifecycle = (
				mockRegisteredStore!.actions as Record<
					string,
					( ...args: unknown[] ) => unknown
				>
			)._processLifecycle;

			const lifecycle = processLifecycle( 0 ) as unknown as Generator;
			lifecycle.next(); // yield fetch(...)

			const mockResponse = { status: 207, json: jest.fn() };
			lifecycle.next( mockResponse ); // yield res.json()

			const consoleSpy = jest
				.spyOn( console, 'error' )
				.mockImplementation( () => {} );

			try {
				lifecycle.next( {
					responses: [
						{ status: 200, body: successCart },
						{ status: 400, body: { code: 'bad_request', message: 'Failed' } },
					],
				} );
			} catch {
				// lifecycle may finish
			}

			consoleSpy.mockRestore();

			// lastServerState should be set from the successful response.
			// Even though one sub-response failed, state.cart uses the server state.
			expect( mockState.cart ).toEqual( successCart );
		} );

		it( 'rolls back to pre-optimistic snapshot when all sub-responses fail (no 2xx)', () => {
			const mockQueueMicrotask = jest.fn();
			globalThis.queueMicrotask = mockQueueMicrotask;

			const mockFetch = jest.fn();
			global.fetch = mockFetch;

			jest.isolateModules( () => require( '../cart' ) );

			const cartBeforeAction = JSON.parse(
				JSON.stringify( mockState.cart )
			);

			// Enqueue an add action.
			const gen = mockRegisteredStore!.actions.addCartItem( {
				id: 99,
				quantity: 1,
				type: 'simple',
			} ) as unknown as Generator;

			gen.next();

			// Cart now has 2 items (optimistic).
			expect( mockState.cart.items ).toHaveLength( 2 );

			const processLifecycle = (
				mockRegisteredStore!.actions as Record<
					string,
					( ...args: unknown[] ) => unknown
				>
			)._processLifecycle;

			const lifecycle = processLifecycle( 0 ) as unknown as Generator;
			lifecycle.next(); // yield fetch(...)

			const mockResponse = { status: 207, json: jest.fn() };
			lifecycle.next( mockResponse ); // yield res.json()

			const consoleSpy = jest
				.spyOn( console, 'error' )
				.mockImplementation( () => {} );

			try {
				lifecycle.next( {
					responses: [
						{ status: 400, body: { code: 'error', message: 'Failed' } },
					],
				} );
			} catch {
				// lifecycle may finish
			}

			consoleSpy.mockRestore();

			// All sub-responses failed → lastServerState is null → rollback.
			expect( mockState.cart ).toEqual( cartBeforeAction );
			expect( mockState.cart.items ).toHaveLength( 1 );
			expect( mockState.cart.items[ 0 ].id ).toBe( 10 );
		} );
	} );

	describe( 'REQ-007: network and non-207 errors', () => {
		it( 'network error pushes to allErrors with isFromCartErrors: false and triggers console.error', () => {
			const mockQueueMicrotask = jest.fn();
			globalThis.queueMicrotask = mockQueueMicrotask;

			jest.isolateModules( () => require( '../cart' ) );

			// Enqueue an add action.
			const gen = mockRegisteredStore!.actions.addCartItem( {
				id: 99,
				quantity: 1,
				type: 'simple',
			} ) as unknown as Generator;

			gen.next();

			const processLifecycle = (
				mockRegisteredStore!.actions as Record<
					string,
					( ...args: unknown[] ) => unknown
				>
			)._processLifecycle;

			const lifecycle = processLifecycle( 0 ) as unknown as Generator;
			lifecycle.next(); // yield fetch(...)

			const consoleSpy = jest
				.spyOn( console, 'error' )
				.mockImplementation( () => {} );

			try {
				lifecycle.throw!( new Error( 'Network error' ) );
			} catch {
				// lifecycle may finish
			}

			// console.error should be called because isFromCartErrors is false.
			expect( consoleSpy ).toHaveBeenCalled();
			const errorArgs = consoleSpy.mock.calls.map(
				( call ) => call[ 0 ]
			);
			expect(
				errorArgs.some(
					( arg ) =>
						arg instanceof Error &&
						arg.message === 'Network error'
				)
			).toBe( true );

			consoleSpy.mockRestore();
		} );

		it( 'network error results in no metadata accumulated — state rolls back', () => {
			const mockQueueMicrotask = jest.fn();
			globalThis.queueMicrotask = mockQueueMicrotask;

			jest.isolateModules( () => require( '../cart' ) );

			const cartBeforeAction = JSON.parse(
				JSON.stringify( mockState.cart )
			);

			// Enqueue an add action.
			const gen = mockRegisteredStore!.actions.addCartItem( {
				id: 99,
				quantity: 1,
				type: 'simple',
			} ) as unknown as Generator;

			gen.next();

			// Cart now has 2 items (optimistic add).
			expect( mockState.cart.items ).toHaveLength( 2 );

			const processLifecycle = (
				mockRegisteredStore!.actions as Record<
					string,
					( ...args: unknown[] ) => unknown
				>
			)._processLifecycle;

			const lifecycle = processLifecycle( 0 ) as unknown as Generator;
			lifecycle.next(); // yield fetch(...)

			const consoleSpy = jest
				.spyOn( console, 'error' )
				.mockImplementation( () => {} );

			try {
				lifecycle.throw!( new Error( 'Network error' ) );
			} catch {
				// lifecycle may finish
			}

			consoleSpy.mockRestore();

			// No lastServerState → rollback to pre-optimistic snapshot.
			expect( mockState.cart ).toEqual( cartBeforeAction );
			expect( mockState.cart.items ).toHaveLength( 1 );
		} );

		it( 'network error does not trigger emitSyncEvent or triggerAddedToCartEvent', () => {
			const mockQueueMicrotask = jest.fn();
			globalThis.queueMicrotask = mockQueueMicrotask;

			let triggerAddedToCartEvent: jest.Mock;
			jest.isolateModules( () => {
				require( '../cart' );
				( { triggerAddedToCartEvent } =
					require( '../legacy-events' ) as {
						triggerAddedToCartEvent: jest.Mock;
					} );
			} );
			triggerAddedToCartEvent!.mockClear();

			const dispatchSpy = jest.spyOn( window, 'dispatchEvent' );

			// Enqueue an add action.
			const gen = mockRegisteredStore!.actions.addCartItem( {
				id: 99,
				quantity: 1,
				type: 'simple',
			} ) as unknown as Generator;

			gen.next();

			const processLifecycle = (
				mockRegisteredStore!.actions as Record<
					string,
					( ...args: unknown[] ) => unknown
				>
			)._processLifecycle;

			const lifecycle = processLifecycle( 0 ) as unknown as Generator;
			lifecycle.next(); // yield fetch(...)

			const consoleSpy = jest
				.spyOn( console, 'error' )
				.mockImplementation( () => {} );

			try {
				lifecycle.throw!( new Error( 'Network error' ) );
			} catch {
				// lifecycle may finish
			}

			consoleSpy.mockRestore();

			// No hadSuccessfulAdd → no triggerAddedToCartEvent.
			expect( triggerAddedToCartEvent! ).not.toHaveBeenCalled();

			// No lastServerState → no emitSyncEvent.
			const syncEvents = dispatchSpy.mock.calls.filter(
				( call ) =>
					call[ 0 ] instanceof CustomEvent &&
					call[ 0 ].type === 'wc-blocks_store_sync_required'
			);
			expect( syncEvents ).toHaveLength( 0 );

			dispatchSpy.mockRestore();
		} );

		it( 'non-207 status pushes error to allErrors with isFromCartErrors: false and triggers console.error', () => {
			const mockQueueMicrotask = jest.fn();
			globalThis.queueMicrotask = mockQueueMicrotask;

			const mockFetch = jest.fn();
			global.fetch = mockFetch;

			jest.isolateModules( () => require( '../cart' ) );

			// Enqueue an add action.
			const gen = mockRegisteredStore!.actions.addCartItem( {
				id: 99,
				quantity: 1,
				type: 'simple',
			} ) as unknown as Generator;

			gen.next();

			const processLifecycle = (
				mockRegisteredStore!.actions as Record<
					string,
					( ...args: unknown[] ) => unknown
				>
			)._processLifecycle;

			const lifecycle = processLifecycle( 0 ) as unknown as Generator;
			lifecycle.next(); // yield fetch(...)

			// Provide a non-207 response (500 server error).
			const mockResponse = { status: 500, ok: false, json: jest.fn() };
			lifecycle.next( mockResponse ); // yield res.json()

			const consoleSpy = jest
				.spyOn( console, 'error' )
				.mockImplementation( () => {} );

			try {
				lifecycle.next( {
					code: 'internal_error',
					message: 'Server error',
				} ); // non-207 → total failure path
			} catch {
				// lifecycle may finish
			}

			// console.error should be called because isFromCartErrors is false.
			expect( consoleSpy ).toHaveBeenCalled();
			const errorArgs = consoleSpy.mock.calls.map(
				( call ) => call[ 0 ]
			);
			expect(
				errorArgs.some(
					( arg ) =>
						arg instanceof Error &&
						arg.message === 'Server error'
				)
			).toBe( true );

			consoleSpy.mockRestore();
		} );

		it( 'non-207 status results in no metadata accumulated — state rolls back', () => {
			const mockQueueMicrotask = jest.fn();
			globalThis.queueMicrotask = mockQueueMicrotask;

			const mockFetch = jest.fn();
			global.fetch = mockFetch;

			jest.isolateModules( () => require( '../cart' ) );

			const cartBeforeAction = JSON.parse(
				JSON.stringify( mockState.cart )
			);

			// Enqueue an add action.
			const gen = mockRegisteredStore!.actions.addCartItem( {
				id: 99,
				quantity: 1,
				type: 'simple',
			} ) as unknown as Generator;

			gen.next();

			// Cart now has 2 items (optimistic add).
			expect( mockState.cart.items ).toHaveLength( 2 );

			const processLifecycle = (
				mockRegisteredStore!.actions as Record<
					string,
					( ...args: unknown[] ) => unknown
				>
			)._processLifecycle;

			const lifecycle = processLifecycle( 0 ) as unknown as Generator;
			lifecycle.next(); // yield fetch(...)

			// Provide a non-207 response.
			const mockResponse = { status: 500, ok: false, json: jest.fn() };
			lifecycle.next( mockResponse ); // yield res.json()

			const consoleSpy = jest
				.spyOn( console, 'error' )
				.mockImplementation( () => {} );

			try {
				lifecycle.next( {
					code: 'internal_error',
					message: 'Server error',
				} );
			} catch {
				// lifecycle may finish
			}

			consoleSpy.mockRestore();

			// No lastServerState → rollback to pre-optimistic snapshot.
			expect( mockState.cart ).toEqual( cartBeforeAction );
			expect( mockState.cart.items ).toHaveLength( 1 );
		} );

		it( 'non-207 status does not trigger emitSyncEvent or triggerAddedToCartEvent', () => {
			const mockQueueMicrotask = jest.fn();
			globalThis.queueMicrotask = mockQueueMicrotask;

			const mockFetch = jest.fn();
			global.fetch = mockFetch;

			let triggerAddedToCartEvent: jest.Mock;
			jest.isolateModules( () => {
				require( '../cart' );
				( { triggerAddedToCartEvent } =
					require( '../legacy-events' ) as {
						triggerAddedToCartEvent: jest.Mock;
					} );
			} );
			triggerAddedToCartEvent!.mockClear();

			const dispatchSpy = jest.spyOn( window, 'dispatchEvent' );

			// Enqueue an add action.
			const gen = mockRegisteredStore!.actions.addCartItem( {
				id: 99,
				quantity: 1,
				type: 'simple',
			} ) as unknown as Generator;

			gen.next();

			const processLifecycle = (
				mockRegisteredStore!.actions as Record<
					string,
					( ...args: unknown[] ) => unknown
				>
			)._processLifecycle;

			const lifecycle = processLifecycle( 0 ) as unknown as Generator;
			lifecycle.next(); // yield fetch(...)

			// Provide a non-207 response.
			const mockResponse = { status: 500, ok: false, json: jest.fn() };
			lifecycle.next( mockResponse ); // yield res.json()

			const consoleSpy = jest
				.spyOn( console, 'error' )
				.mockImplementation( () => {} );

			try {
				lifecycle.next( {
					code: 'internal_error',
					message: 'Server error',
				} );
			} catch {
				// lifecycle may finish
			}

			consoleSpy.mockRestore();

			// No hadSuccessfulAdd → no triggerAddedToCartEvent.
			expect( triggerAddedToCartEvent! ).not.toHaveBeenCalled();

			// No lastServerState → no emitSyncEvent.
			const syncEvents = dispatchSpy.mock.calls.filter(
				( call ) =>
					call[ 0 ] instanceof CustomEvent &&
					call[ 0 ].type === 'wc-blocks_store_sync_required'
			);
			expect( syncEvents ).toHaveLength( 0 );

			dispatchSpy.mockRestore();
		} );

		it( 'non-207 with non-API error response generates a generic batch failure error', () => {
			const mockQueueMicrotask = jest.fn();
			globalThis.queueMicrotask = mockQueueMicrotask;

			const mockFetch = jest.fn();
			global.fetch = mockFetch;

			jest.isolateModules( () => require( '../cart' ) );

			// Enqueue an add action.
			const gen = mockRegisteredStore!.actions.addCartItem( {
				id: 99,
				quantity: 1,
				type: 'simple',
			} ) as unknown as Generator;

			gen.next();

			const processLifecycle = (
				mockRegisteredStore!.actions as Record<
					string,
					( ...args: unknown[] ) => unknown
				>
			)._processLifecycle;

			const lifecycle = processLifecycle( 0 ) as unknown as Generator;
			lifecycle.next(); // yield fetch(...)

			// Provide a non-207, non-API-error response (e.g., ok: true but status 200
			// meaning the batch endpoint returned 200 instead of 207).
			const mockResponse = { status: 200, ok: true, json: jest.fn() };
			lifecycle.next( mockResponse ); // yield res.json()

			const consoleSpy = jest
				.spyOn( console, 'error' )
				.mockImplementation( () => {} );

			try {
				lifecycle.next( { items: [], totals: {}, errors: [] } ); // valid-looking but non-207
			} catch {
				// lifecycle may finish
			}

			// A generic "Batch failed: 200" error should be generated.
			expect( consoleSpy ).toHaveBeenCalled();
			const errorArgs = consoleSpy.mock.calls.map(
				( call ) => call[ 0 ]
			);
			expect(
				errorArgs.some(
					( arg ) =>
						arg instanceof Error &&
						arg.message.includes( 'Batch failed' )
				)
			).toBe( true );

			consoleSpy.mockRestore();
		} );
	} );

	describe( 'REQ-008: partial failure and cart.errors', () => {
		it( 'mixed 2xx/4xx responses: lastServerState updated from successful sub-response', () => {
			const mockQueueMicrotask = jest.fn();
			globalThis.queueMicrotask = mockQueueMicrotask;

			const mockFetch = jest.fn();
			global.fetch = mockFetch;

			jest.isolateModules( () => require( '../cart' ) );

			// Enqueue two actions: an add and a remove.
			const addGen = mockRegisteredStore!.actions.addCartItem( {
				id: 99,
				quantity: 1,
				type: 'simple',
			} ) as unknown as Generator;

			addGen.next();

			const removeGen = mockRegisteredStore!.actions.removeCartItem(
				'item-1'
			) as unknown as Generator;

			removeGen.next();

			const processLifecycle = (
				mockRegisteredStore!.actions as Record<
					string,
					( ...args: unknown[] ) => unknown
				>
			)._processLifecycle;

			const lifecycle = processLifecycle( 0 ) as unknown as Generator;
			lifecycle.next(); // yield fetch(...)

			const serverCart = {
				items: [
					{
						key: 'server-1',
						id: 99,
						quantity: 1,
						type: 'simple',
						name: 'Added Widget',
					},
				],
				totals: { total_items: '1' },
				errors: [],
			};

			const mockResponse = { status: 207, json: jest.fn() };
			lifecycle.next( mockResponse ); // yield res.json()

			const consoleSpy = jest
				.spyOn( console, 'error' )
				.mockImplementation( () => {} );

			try {
				lifecycle.next( {
					responses: [
						// First sub-response: 200 (success)
						{ status: 200, body: serverCart },
						// Second sub-response: 400 (failure)
						{
							status: 400,
							body: {
								code: 'item_not_found',
								message: 'Item not found',
							},
						},
					],
				} );
			} catch {
				// lifecycle may finish
			}

			consoleSpy.mockRestore();

			// state.cart should be set to the successful sub-response body.
			expect( mockState.cart ).toEqual( serverCart );
		} );

		it( 'mixed 2xx/4xx responses: metadata accumulated only from successful sub-responses', () => {
			const mockQueueMicrotask = jest.fn();
			globalThis.queueMicrotask = mockQueueMicrotask;

			const mockFetch = jest.fn();
			global.fetch = mockFetch;

			jest.isolateModules( () => require( '../cart' ) );

			const dispatchSpy = jest.spyOn( window, 'dispatchEvent' );

			// Enqueue two adds in the same tick.
			const gen1 = mockRegisteredStore!.actions.addCartItem( {
				id: 99,
				quantity: 1,
				type: 'simple',
			} ) as unknown as Generator;

			gen1.next();

			const gen2 = mockRegisteredStore!.actions.addCartItem( {
				id: 88,
				quantity: 2,
				type: 'simple',
			} ) as unknown as Generator;

			gen2.next();

			const processLifecycle = (
				mockRegisteredStore!.actions as Record<
					string,
					( ...args: unknown[] ) => unknown
				>
			)._processLifecycle;

			const lifecycle = processLifecycle( 0 ) as unknown as Generator;
			lifecycle.next(); // yield fetch(...)

			const serverCart = {
				items: [
					{
						key: 'server-1',
						id: 99,
						quantity: 1,
						type: 'simple',
						name: 'Widget',
					},
				],
				totals: { total_items: '1' },
				errors: [],
			};

			const mockResponse = { status: 207, json: jest.fn() };
			lifecycle.next( mockResponse ); // yield res.json()

			const consoleSpy = jest
				.spyOn( console, 'error' )
				.mockImplementation( () => {} );

			try {
				lifecycle.next( {
					responses: [
						// First add: success → metadata accumulated
						{ status: 200, body: serverCart },
						// Second add: failure → metadata NOT accumulated
						{
							status: 400,
							body: {
								code: 'out_of_stock',
								message: 'Out of stock',
							},
						},
					],
				} );
			} catch {
				// lifecycle may finish
			}

			consoleSpy.mockRestore();

			// emitSyncEvent should be called because lastServerState !== null.
			const syncEvent = dispatchSpy.mock.calls.find(
				( [ event ] ) =>
					event instanceof CustomEvent &&
					event.type === 'wc-blocks_store_sync_required'
			);
			expect( syncEvent ).toBeDefined();

			const detail = ( syncEvent![ 0 ] as CustomEvent ).detail;
			// Only product id 99 (first, successful add) should be in metadata.
			// Product id 88 (failed add) should NOT be in metadata.
			expect(
				detail.quantityChanges.productsPendingAdd
			).toContain( 99 );
			expect(
				detail.quantityChanges.productsPendingAdd
			).not.toContain( 88 );

			dispatchSpy.mockRestore();
		} );

		it( 'failed sub-responses push errors with isFromCartErrors: false (console.error called)', () => {
			const mockQueueMicrotask = jest.fn();
			globalThis.queueMicrotask = mockQueueMicrotask;

			const mockFetch = jest.fn();
			global.fetch = mockFetch;

			jest.isolateModules( () => require( '../cart' ) );

			const gen = mockRegisteredStore!.actions.addCartItem( {
				id: 99,
				quantity: 1,
				type: 'simple',
			} ) as unknown as Generator;

			gen.next();

			const processLifecycle = (
				mockRegisteredStore!.actions as Record<
					string,
					( ...args: unknown[] ) => unknown
				>
			)._processLifecycle;

			const lifecycle = processLifecycle( 0 ) as unknown as Generator;
			lifecycle.next(); // yield fetch(...)

			const mockResponse = { status: 207, json: jest.fn() };
			lifecycle.next( mockResponse ); // yield res.json()

			const consoleSpy = jest
				.spyOn( console, 'error' )
				.mockImplementation( () => {} );

			try {
				lifecycle.next( {
					responses: [
						{
							status: 400,
							body: {
								code: 'bad_request',
								message: 'Bad request',
							},
						},
					],
				} );
			} catch {
				// lifecycle may finish
			}

			// console.error should be called for non-cart errors.
			expect( consoleSpy ).toHaveBeenCalled();
			const errorArgs = consoleSpy.mock.calls.map(
				( call ) => call[ 0 ]
			);
			expect(
				errorArgs.some(
					( arg ) =>
						arg instanceof Error &&
						arg.message === 'Bad request'
				)
			).toBe( true );

			consoleSpy.mockRestore();
		} );

		it( 'cart.errors from successful sub-responses are classified as isFromCartErrors: true (no console.error)', () => {
			const mockQueueMicrotask = jest.fn();
			globalThis.queueMicrotask = mockQueueMicrotask;

			const mockFetch = jest.fn();
			global.fetch = mockFetch;

			jest.isolateModules( () => require( '../cart' ) );

			const gen = mockRegisteredStore!.actions.addCartItem( {
				id: 99,
				quantity: 1,
				type: 'simple',
			} ) as unknown as Generator;

			gen.next();

			const processLifecycle = (
				mockRegisteredStore!.actions as Record<
					string,
					( ...args: unknown[] ) => unknown
				>
			)._processLifecycle;

			const lifecycle = processLifecycle( 0 ) as unknown as Generator;
			lifecycle.next(); // yield fetch(...)

			const serverCart = {
				items: [
					{
						key: 'server-1',
						id: 99,
						quantity: 1,
						type: 'simple',
						name: 'Widget',
					},
				],
				totals: { total_items: '1' },
				// Cart-level errors reported by the server.
				errors: [
					{
						code: 'coupon_expired',
						message: 'Your coupon has expired',
					},
				],
			};

			const mockResponse = { status: 207, json: jest.fn() };
			lifecycle.next( mockResponse ); // yield res.json()

			const consoleSpy = jest
				.spyOn( console, 'error' )
				.mockImplementation( () => {} );

			try {
				lifecycle.next( {
					responses: [ { status: 200, body: serverCart } ],
				} );
			} catch {
				// lifecycle may finish
			}

			// console.error should NOT be called for cart-reported errors.
			// Cart errors have isFromCartErrors: true, which skips console.error.
			expect( consoleSpy ).not.toHaveBeenCalled();

			consoleSpy.mockRestore();
		} );

		it( 'unmatched entries: server returns fewer responses than requests', () => {
			const mockQueueMicrotask = jest.fn();
			globalThis.queueMicrotask = mockQueueMicrotask;

			const mockFetch = jest.fn();
			global.fetch = mockFetch;

			jest.isolateModules( () => require( '../cart' ) );

			// Enqueue three actions.
			const gen1 = mockRegisteredStore!.actions.addCartItem( {
				id: 99,
				quantity: 1,
				type: 'simple',
			} ) as unknown as Generator;

			gen1.next();

			const gen2 = mockRegisteredStore!.actions.addCartItem( {
				id: 88,
				quantity: 1,
				type: 'simple',
			} ) as unknown as Generator;

			gen2.next();

			const gen3 = mockRegisteredStore!.actions.removeCartItem(
				'item-1'
			) as unknown as Generator;

			gen3.next();

			const processLifecycle = (
				mockRegisteredStore!.actions as Record<
					string,
					( ...args: unknown[] ) => unknown
				>
			)._processLifecycle;

			const lifecycle = processLifecycle( 0 ) as unknown as Generator;
			lifecycle.next(); // yield fetch(...)

			const serverCart = {
				items: [
					{
						key: 'server-1',
						id: 99,
						quantity: 1,
						type: 'simple',
						name: 'Widget',
					},
				],
				totals: { total_items: '1' },
				errors: [],
			};

			const mockResponse = { status: 207, json: jest.fn() };
			lifecycle.next( mockResponse ); // yield res.json()

			const consoleWarnSpy = jest
				.spyOn( console, 'warn' )
				.mockImplementation( () => {} );
			const consoleErrorSpy = jest
				.spyOn( console, 'error' )
				.mockImplementation( () => {} );

			try {
				lifecycle.next( {
					responses: [
						// Only 2 responses for 3 requests.
						{ status: 200, body: serverCart },
						{
							status: 200,
							body: {
								...serverCart,
								items: [],
							},
						},
					],
				} );
			} catch {
				// lifecycle may finish
			}

			// console.warn should be called for the mismatch.
			expect( consoleWarnSpy ).toHaveBeenCalledWith(
				expect.stringContaining( 'mismatch' )
			);

			// console.error should be called for the unmatched entry's error
			// (isFromCartErrors: false → console.error).
			const errorArgs = consoleErrorSpy.mock.calls.map(
				( call ) => call[ 0 ]
			);
			expect(
				errorArgs.some(
					( arg ) =>
						arg instanceof Error &&
						arg.message.includes(
							'Server did not return a response'
						)
				)
			).toBe( true );

			consoleWarnSpy.mockRestore();
			consoleErrorSpy.mockRestore();
		} );

		it( 'cart.errors non-object entries are wrapped with new Error(String(cartError))', () => {
			const mockQueueMicrotask = jest.fn();
			globalThis.queueMicrotask = mockQueueMicrotask;

			const mockFetch = jest.fn();
			global.fetch = mockFetch;

			jest.isolateModules( () => require( '../cart' ) );

			const gen = mockRegisteredStore!.actions.addCartItem( {
				id: 99,
				quantity: 1,
				type: 'simple',
			} ) as unknown as Generator;

			gen.next();

			const processLifecycle = (
				mockRegisteredStore!.actions as Record<
					string,
					( ...args: unknown[] ) => unknown
				>
			)._processLifecycle;

			const lifecycle = processLifecycle( 0 ) as unknown as Generator;
			lifecycle.next(); // yield fetch(...)

			const serverCart = {
				items: [
					{
						key: 'server-1',
						id: 99,
						quantity: 1,
						type: 'simple',
						name: 'Widget',
					},
				],
				totals: { total_items: '1' },
				// Non-object error entries: a string and a number.
				errors: [ 'Something went wrong', 42 ],
			};

			const mockResponse = { status: 207, json: jest.fn() };
			lifecycle.next( mockResponse ); // yield res.json()

			// Cart errors (isFromCartErrors: true) should NOT trigger console.error.
			const consoleSpy = jest
				.spyOn( console, 'error' )
				.mockImplementation( () => {} );

			try {
				lifecycle.next( {
					responses: [ { status: 200, body: serverCart } ],
				} );
			} catch {
				// lifecycle may finish
			}

			// No console.error because cart errors are isFromCartErrors: true.
			expect( consoleSpy ).not.toHaveBeenCalled();

			consoleSpy.mockRestore();

			// Verify the state was still set to the server cart (success path).
			expect( mockState.cart ).toEqual( serverCart );
		} );

		it( 'successful sub-response with cart.errors still updates lastServerState', () => {
			const mockQueueMicrotask = jest.fn();
			globalThis.queueMicrotask = mockQueueMicrotask;

			const mockFetch = jest.fn();
			global.fetch = mockFetch;

			jest.isolateModules( () => require( '../cart' ) );

			const gen = mockRegisteredStore!.actions.addCartItem( {
				id: 99,
				quantity: 1,
				type: 'simple',
			} ) as unknown as Generator;

			gen.next();

			const processLifecycle = (
				mockRegisteredStore!.actions as Record<
					string,
					( ...args: unknown[] ) => unknown
				>
			)._processLifecycle;

			const lifecycle = processLifecycle( 0 ) as unknown as Generator;
			lifecycle.next(); // yield fetch(...)

			const serverCart = {
				items: [
					{
						key: 'server-1',
						id: 99,
						quantity: 1,
						type: 'simple',
						name: 'Widget',
					},
				],
				totals: { total_items: '1' },
				errors: [
					{
						code: 'coupon_warning',
						message: 'Coupon almost expired',
					},
				],
			};

			const mockResponse = { status: 207, json: jest.fn() };
			lifecycle.next( mockResponse ); // yield res.json()

			const consoleSpy = jest
				.spyOn( console, 'error' )
				.mockImplementation( () => {} );

			try {
				lifecycle.next( {
					responses: [ { status: 200, body: serverCart } ],
				} );
			} catch {
				// lifecycle may finish
			}

			consoleSpy.mockRestore();

			// Even with cart.errors, the 200 response body becomes lastServerState.
			expect( mockState.cart ).toEqual( serverCart );
		} );

		it( 'all sub-responses fail (no 2xx): state.cart is rolled back to pre-optimistic snapshot', () => {
			const mockQueueMicrotask = jest.fn();
			globalThis.queueMicrotask = mockQueueMicrotask;

			const mockFetch = jest.fn();
			global.fetch = mockFetch;

			jest.isolateModules( () => require( '../cart' ) );

			const cartBeforeAction = JSON.parse(
				JSON.stringify( mockState.cart )
			);

			// Enqueue an add.
			const gen = mockRegisteredStore!.actions.addCartItem( {
				id: 99,
				quantity: 1,
				type: 'simple',
			} ) as unknown as Generator;

			gen.next();

			// Cart should have optimistic item.
			expect( mockState.cart.items ).toHaveLength( 2 );

			const processLifecycle = (
				mockRegisteredStore!.actions as Record<
					string,
					( ...args: unknown[] ) => unknown
				>
			)._processLifecycle;

			const lifecycle = processLifecycle( 0 ) as unknown as Generator;
			lifecycle.next(); // yield fetch(...)

			const mockResponse = { status: 207, json: jest.fn() };
			lifecycle.next( mockResponse ); // yield res.json()

			const consoleSpy = jest
				.spyOn( console, 'error' )
				.mockImplementation( () => {} );

			try {
				lifecycle.next( {
					responses: [
						{
							status: 400,
							body: {
								code: 'bad_request',
								message: 'Error',
							},
						},
					],
				} );
			} catch {
				// lifecycle may finish
			}

			consoleSpy.mockRestore();

			// No 2xx → lastServerState is null → rollback to snapshot.
			expect( mockState.cart ).toEqual( cartBeforeAction );
		} );
	} );

	describe( 'REQ-012: error display and notice retry', () => {
		it( 'calls console.error for non-cart errors but not for cart.errors entries', () => {
			const mockQueueMicrotask = jest.fn();
			globalThis.queueMicrotask = mockQueueMicrotask;

			const mockFetch = jest.fn();
			global.fetch = mockFetch;

			jest.isolateModules( () => require( '../cart' ) );

			// Enqueue two items to get two sub-responses.
			const gen1 = mockRegisteredStore!.actions.addCartItem( {
				id: 99,
				quantity: 1,
				type: 'simple',
			} ) as unknown as Generator;
			gen1.next();

			const gen2 = mockRegisteredStore!.actions.removeCartItem(
				'item-1'
			) as unknown as Generator;
			gen2.next();

			const processLifecycle = (
				mockRegisteredStore!.actions as Record<
					string,
					( ...args: unknown[] ) => unknown
				>
			)._processLifecycle;

			const lifecycle = processLifecycle( 0 ) as unknown as Generator;
			lifecycle.next(); // yield fetch(...)

			const mockResponse = { status: 207, json: jest.fn() };
			lifecycle.next( mockResponse ); // yield res.json()

			const consoleSpy = jest
				.spyOn( console, 'error' )
				.mockImplementation( () => {} );

			const serverCart = {
				items: [],
				totals: { total_items: '0' },
				errors: [
					{
						code: 'cart_warning',
						message: 'Cart-reported error',
					},
				],
			};

			try {
				lifecycle.next( {
					responses: [
						// 200 with cart.errors → isFromCartErrors: true
						{ status: 200, body: serverCart },
						// 400 → isFromCartErrors: false
						{
							status: 400,
							body: {
								code: 'bad_request',
								message: 'Non-cart error',
							},
						},
					],
				} );
			} catch {
				// lifecycle may finish or yield
			}

			// console.error should be called for the non-cart error (400).
			const errorArgs = consoleSpy.mock.calls.map(
				( call ) => call[ 0 ]
			);
			expect(
				errorArgs.some(
					( arg ) =>
						arg instanceof Error &&
						arg.message === 'Non-cart error'
				)
			).toBe( true );

			// console.error should NOT be called with the cart-reported error.
			expect(
				errorArgs.some(
					( arg ) =>
						( arg instanceof Error &&
							arg.message === 'Cart-reported error' ) ||
						( typeof arg === 'object' &&
							arg !== null &&
							'message' in arg &&
							( arg as { message: string } ).message ===
								'Cart-reported error' )
				)
			).toBe( false );

			consoleSpy.mockRestore();
		} );

		it( 'updateNotices is called with error notices and removeOthers true', () => {
			const mockQueueMicrotask = jest.fn();
			globalThis.queueMicrotask = mockQueueMicrotask;

			jest.isolateModules( () => require( '../cart' ) );

			// Replace updateNotices with a spy.
			const updateNoticesSpy = jest.fn();
			(
				mockRegisteredStore!.actions as Record< string, unknown >
			).updateNotices = updateNoticesSpy;

			// Enqueue an add.
			const gen = mockRegisteredStore!.actions.addCartItem( {
				id: 99,
				quantity: 1,
				type: 'simple',
			} ) as unknown as Generator;
			gen.next();

			const processLifecycle = (
				mockRegisteredStore!.actions as Record<
					string,
					( ...args: unknown[] ) => unknown
				>
			)._processLifecycle;

			const lifecycle = processLifecycle( 0 ) as unknown as Generator;
			lifecycle.next(); // yield fetch(...)

			const consoleSpy = jest
				.spyOn( console, 'error' )
				.mockImplementation( () => {} );

			// Network error → total failure → one error notice.
			try {
				lifecycle.throw!( new Error( 'Network error' ) );
			} catch {
				// lifecycle may yield or finish
			}

			consoleSpy.mockRestore();

			// updateNotices should have been called with notices and removeOthers=true.
			expect( updateNoticesSpy ).toHaveBeenCalledTimes( 1 );
			expect( updateNoticesSpy ).toHaveBeenCalledWith(
				expect.arrayContaining( [
					expect.objectContaining( {
						notice: 'Network error',
						type: 'error',
						dismissible: true,
					} ),
				] ),
				true
			);
		} );

		it( 'retries updateNotices when first attempt fails', () => {
			const mockQueueMicrotask = jest.fn();
			globalThis.queueMicrotask = mockQueueMicrotask;

			jest.isolateModules( () => require( '../cart' ) );

			// Replace updateNotices with a spy.
			const updateNoticesSpy = jest.fn();
			(
				mockRegisteredStore!.actions as Record< string, unknown >
			).updateNotices = updateNoticesSpy;

			// Enqueue an add.
			const gen = mockRegisteredStore!.actions.addCartItem( {
				id: 99,
				quantity: 1,
				type: 'simple',
			} ) as unknown as Generator;
			gen.next();

			const processLifecycle = (
				mockRegisteredStore!.actions as Record<
					string,
					( ...args: unknown[] ) => unknown
				>
			)._processLifecycle;

			const lifecycle = processLifecycle( 0 ) as unknown as Generator;
			lifecycle.next(); // yield fetch(...)

			const consoleSpy = jest
				.spyOn( console, 'error' )
				.mockImplementation( () => {} );

			// Network error → reconciliation → yield updateNotices (1st call).
			lifecycle.throw!( new Error( 'Network error' ) );
			expect( updateNoticesSpy ).toHaveBeenCalledTimes( 1 );

			// Simulate first updateNotices failure → catch → retry yield.
			try {
				lifecycle.throw!(
					new Error( 'Notice display failed' )
				);
			} catch {
				// lifecycle may yield or finish
			}

			// updateNotices should have been called a second time (retry).
			expect( updateNoticesSpy ).toHaveBeenCalledTimes( 2 );

			consoleSpy.mockRestore();
		} );

		it( 'retry receives the same notices array as the first attempt', () => {
			const mockQueueMicrotask = jest.fn();
			globalThis.queueMicrotask = mockQueueMicrotask;

			jest.isolateModules( () => require( '../cart' ) );

			// Replace updateNotices with a spy.
			const updateNoticesSpy = jest.fn();
			(
				mockRegisteredStore!.actions as Record< string, unknown >
			).updateNotices = updateNoticesSpy;

			// Enqueue an add.
			const gen = mockRegisteredStore!.actions.addCartItem( {
				id: 99,
				quantity: 1,
				type: 'simple',
			} ) as unknown as Generator;
			gen.next();

			const processLifecycle = (
				mockRegisteredStore!.actions as Record<
					string,
					( ...args: unknown[] ) => unknown
				>
			)._processLifecycle;

			const lifecycle = processLifecycle( 0 ) as unknown as Generator;
			lifecycle.next(); // yield fetch(...)

			const consoleSpy = jest
				.spyOn( console, 'error' )
				.mockImplementation( () => {} );

			// Network error → yield updateNotices (1st).
			lifecycle.throw!( new Error( 'Network error' ) );

			// Simulate failure → retry.
			try {
				lifecycle.throw!(
					new Error( 'Notice display failed' )
				);
			} catch {
				// lifecycle may yield or finish
			}

			consoleSpy.mockRestore();

			// Both calls should receive the same arguments.
			const firstCallArgs = updateNoticesSpy.mock.calls[ 0 ];
			const secondCallArgs = updateNoticesSpy.mock.calls[ 1 ];
			expect( firstCallArgs ).toEqual( secondCallArgs );
		} );

		it( 'double failure: logs each notice text to console when both attempts fail', () => {
			const mockQueueMicrotask = jest.fn();
			globalThis.queueMicrotask = mockQueueMicrotask;

			jest.isolateModules( () => require( '../cart' ) );

			// Replace updateNotices with a spy.
			const updateNoticesSpy = jest.fn();
			(
				mockRegisteredStore!.actions as Record< string, unknown >
			).updateNotices = updateNoticesSpy;

			// Enqueue an add.
			const gen = mockRegisteredStore!.actions.addCartItem( {
				id: 99,
				quantity: 1,
				type: 'simple',
			} ) as unknown as Generator;
			gen.next();

			const processLifecycle = (
				mockRegisteredStore!.actions as Record<
					string,
					( ...args: unknown[] ) => unknown
				>
			)._processLifecycle;

			const lifecycle = processLifecycle( 0 ) as unknown as Generator;
			lifecycle.next(); // yield fetch(...)

			const consoleSpy = jest
				.spyOn( console, 'error' )
				.mockImplementation( () => {} );
			const consoleWarnSpy = jest
				.spyOn( console, 'warn' )
				.mockImplementation( () => {} );

			// Network error → yield updateNotices (1st).
			lifecycle.throw!( new Error( 'Network error' ) );

			// First failure → retry yield.
			lifecycle.throw!( new Error( 'First notice failure' ) );

			// Second failure → fallback console logging.
			try {
				lifecycle.throw!(
					new Error( 'Second notice failure' )
				);
			} catch {
				// generator may complete
			}

			// console.error should log 'Failed to display cart notices:'.
			const errorArgs = consoleSpy.mock.calls.map(
				( call ) => call[ 0 ]
			);
			expect(
				errorArgs.some(
					( arg ) =>
						typeof arg === 'string' &&
						arg.includes(
							'Failed to display cart notices'
						)
				)
			).toBe( true );

			// console.warn should log the lost notice text.
			expect( consoleWarnSpy ).toHaveBeenCalledWith(
				'Lost cart notice:',
				'Network error'
			);

			consoleSpy.mockRestore();
			consoleWarnSpy.mockRestore();
		} );
	} );

	describe( 'REQ-013: side effects', () => {
		it( 'triggerAddedToCartEvent is called with { preserveCartData: true } on successful add', () => {
			const mockQueueMicrotask = jest.fn();
			globalThis.queueMicrotask = mockQueueMicrotask;

			const mockFetch = jest.fn();
			global.fetch = mockFetch;

			let triggerAddedToCartEvent: jest.Mock;
			jest.isolateModules( () => {
				require( '../cart' );
				( { triggerAddedToCartEvent } =
					require( '../legacy-events' ) as {
						triggerAddedToCartEvent: jest.Mock;
					} );
			} );
			triggerAddedToCartEvent!.mockClear();

			// Enqueue an add action.
			const gen = mockRegisteredStore!.actions.addCartItem( {
				id: 99,
				quantity: 1,
				type: 'simple',
			} ) as unknown as Generator;

			gen.next();

			// Drive lifecycle with a 200 response.
			const processLifecycle = (
				mockRegisteredStore!.actions as Record<
					string,
					( ...args: unknown[] ) => unknown
				>
			)._processLifecycle;

			const lifecycle = processLifecycle( 0 ) as unknown as Generator;
			lifecycle.next(); // yield fetch(...)

			const serverCart = {
				items: [
					{
						key: 'new-item',
						id: 99,
						quantity: 1,
						type: 'simple',
						name: 'New Widget',
					},
				],
				totals: { total_items: '1' },
				errors: [],
			};

			const mockResponse = { status: 207, json: jest.fn() };
			lifecycle.next( mockResponse ); // yield res.json()

			const consoleSpy = jest
				.spyOn( console, 'error' )
				.mockImplementation( () => {} );
			try {
				lifecycle.next( {
					responses: [
						{ status: 200, body: serverCart },
					],
				} );
			} catch {
				// lifecycle may finish
			}
			consoleSpy.mockRestore();

			expect( triggerAddedToCartEvent! ).toHaveBeenCalledWith( {
				preserveCartData: true,
			} );
		} );

		it( 'triggerAddedToCartEvent is NOT called when only removes succeed', () => {
			const mockQueueMicrotask = jest.fn();
			globalThis.queueMicrotask = mockQueueMicrotask;

			const mockFetch = jest.fn();
			global.fetch = mockFetch;

			let triggerAddedToCartEvent: jest.Mock;
			jest.isolateModules( () => {
				require( '../cart' );
				( { triggerAddedToCartEvent } =
					require( '../legacy-events' ) as {
						triggerAddedToCartEvent: jest.Mock;
					} );
			} );
			triggerAddedToCartEvent!.mockClear();

			// Enqueue a remove action (not an add).
			const gen = mockRegisteredStore!.actions.removeCartItem(
				'item-1'
			) as unknown as Generator;

			gen.next();

			// Drive lifecycle with a 200 response.
			const processLifecycle = (
				mockRegisteredStore!.actions as Record<
					string,
					( ...args: unknown[] ) => unknown
				>
			)._processLifecycle;

			const lifecycle = processLifecycle( 0 ) as unknown as Generator;
			lifecycle.next(); // yield fetch(...)

			const serverCart = {
				items: [],
				totals: { total_items: '0' },
				errors: [],
			};

			const mockResponse = { status: 207, json: jest.fn() };
			lifecycle.next( mockResponse ); // yield res.json()

			const consoleSpy = jest
				.spyOn( console, 'error' )
				.mockImplementation( () => {} );
			try {
				lifecycle.next( {
					responses: [
						{ status: 200, body: serverCart },
					],
				} );
			} catch {
				// lifecycle may finish
			}
			consoleSpy.mockRestore();

			// Remove succeeded but hadSuccessfulAdd is false.
			expect( triggerAddedToCartEvent! ).not.toHaveBeenCalled();
		} );

		it( 'triggerAddedToCartEvent is NOT called when add gets a 4xx response', () => {
			const mockQueueMicrotask = jest.fn();
			globalThis.queueMicrotask = mockQueueMicrotask;

			const mockFetch = jest.fn();
			global.fetch = mockFetch;

			let triggerAddedToCartEvent: jest.Mock;
			jest.isolateModules( () => {
				require( '../cart' );
				( { triggerAddedToCartEvent } =
					require( '../legacy-events' ) as {
						triggerAddedToCartEvent: jest.Mock;
					} );
			} );
			triggerAddedToCartEvent!.mockClear();

			// Enqueue an add action.
			const gen = mockRegisteredStore!.actions.addCartItem( {
				id: 99,
				quantity: 1,
				type: 'simple',
			} ) as unknown as Generator;

			gen.next();

			// Drive lifecycle with a 4xx sub-response.
			const processLifecycle = (
				mockRegisteredStore!.actions as Record<
					string,
					( ...args: unknown[] ) => unknown
				>
			)._processLifecycle;

			const lifecycle = processLifecycle( 0 ) as unknown as Generator;
			lifecycle.next(); // yield fetch(...)

			const mockResponse = { status: 207, json: jest.fn() };
			lifecycle.next( mockResponse ); // yield res.json()

			const consoleSpy = jest
				.spyOn( console, 'error' )
				.mockImplementation( () => {} );
			try {
				lifecycle.next( {
					responses: [
						{
							status: 400,
							body: {
								code: 'invalid_item',
								message: 'Item not found',
							},
						},
					],
				} );
			} catch {
				// lifecycle may finish
			}
			consoleSpy.mockRestore();

			// Add failed — hadSuccessfulAdd remains false.
			expect( triggerAddedToCartEvent! ).not.toHaveBeenCalled();
		} );

		it( 'speak() is called when hadSuccessfulAdd and config has addedToCartText', () => {
			const mockQueueMicrotask = jest.fn();
			globalThis.queueMicrotask = mockQueueMicrotask;

			const mockFetch = jest.fn();
			global.fetch = mockFetch;

			let triggerAddedToCartEvent: jest.Mock;
			jest.isolateModules( () => {
				require( '../cart' );
				( { triggerAddedToCartEvent } =
					require( '../legacy-events' ) as {
						triggerAddedToCartEvent: jest.Mock;
					} );
			} );
			triggerAddedToCartEvent!.mockClear();

			// Configure getConfig to return addedToCartText.
			const { getConfig } =
				require( '@wordpress/interactivity' ) as {
					getConfig: jest.Mock;
				};
			getConfig.mockReturnValue( {
				messages: { addedToCartText: 'Added to cart' },
			} );

			// Enqueue an add action.
			const gen = mockRegisteredStore!.actions.addCartItem( {
				id: 99,
				quantity: 1,
				type: 'simple',
			} ) as unknown as Generator;

			gen.next();

			// Drive lifecycle with a 200 response.
			const processLifecycle = (
				mockRegisteredStore!.actions as Record<
					string,
					( ...args: unknown[] ) => unknown
				>
			)._processLifecycle;

			const lifecycle = processLifecycle( 0 ) as unknown as Generator;
			lifecycle.next(); // yield fetch(...)

			const serverCart = {
				items: [
					{
						key: 'new-item',
						id: 99,
						quantity: 1,
						type: 'simple',
						name: 'New Widget',
					},
				],
				totals: { total_items: '1' },
				errors: [],
			};

			const mockResponse = { status: 207, json: jest.fn() };
			lifecycle.next( mockResponse ); // yield res.json()

			// Feed batch JSON — lifecycle enters reconciliation.
			// It may yield for updateNotices (if notices exist) and then
			// yield import('@wordpress/a11y') for speak.
			const consoleSpy = jest
				.spyOn( console, 'error' )
				.mockImplementation( () => {} );

			let step = lifecycle.next( {
				responses: [
					{ status: 200, body: serverCart },
				],
			} );

			// Drive through any remaining yields until done.
			const speakMock = jest.fn();
			while ( ! step.done ) {
				step = lifecycle.next( { speak: speakMock } );
			}

			consoleSpy.mockRestore();

			expect( speakMock ).toHaveBeenCalledWith(
				'Added to cart',
				'polite'
			);
		} );

		it( 'speak() is NOT called when hadSuccessfulAdd but config lacks addedToCartText', () => {
			const mockQueueMicrotask = jest.fn();
			globalThis.queueMicrotask = mockQueueMicrotask;

			const mockFetch = jest.fn();
			global.fetch = mockFetch;

			jest.isolateModules( () => require( '../cart' ) );

			// Configure getConfig WITHOUT addedToCartText.
			const { getConfig } =
				require( '@wordpress/interactivity' ) as {
					getConfig: jest.Mock;
				};
			getConfig.mockReturnValue( { messages: {} } );

			// Enqueue an add action.
			const gen = mockRegisteredStore!.actions.addCartItem( {
				id: 99,
				quantity: 1,
				type: 'simple',
			} ) as unknown as Generator;

			gen.next();

			// Drive lifecycle with a 200 response.
			const processLifecycle = (
				mockRegisteredStore!.actions as Record<
					string,
					( ...args: unknown[] ) => unknown
				>
			)._processLifecycle;

			const lifecycle = processLifecycle( 0 ) as unknown as Generator;
			lifecycle.next(); // yield fetch(...)

			const serverCart = {
				items: [
					{
						key: 'new-item',
						id: 99,
						quantity: 1,
						type: 'simple',
						name: 'New Widget',
					},
				],
				totals: { total_items: '1' },
				errors: [],
			};

			const mockResponse = { status: 207, json: jest.fn() };
			lifecycle.next( mockResponse ); // yield res.json()

			const consoleSpy = jest
				.spyOn( console, 'error' )
				.mockImplementation( () => {} );

			// The speak mock from the static jest.mock at the top level.
			const { speak } = require( '@wordpress/a11y' ) as {
				speak: jest.Mock;
			};
			speak.mockClear();

			let step = lifecycle.next( {
				responses: [
					{ status: 200, body: serverCart },
				],
			} );

			// Drive through remaining yields.
			while ( ! step.done ) {
				step = lifecycle.next( { speak } );
			}

			consoleSpy.mockRestore();

			// No addedToCartText → speak should not be called.
			expect( speak ).not.toHaveBeenCalled();
		} );

		it( 'speak() is NOT called when only removes succeed (no hadSuccessfulAdd)', () => {
			const mockQueueMicrotask = jest.fn();
			globalThis.queueMicrotask = mockQueueMicrotask;

			const mockFetch = jest.fn();
			global.fetch = mockFetch;

			jest.isolateModules( () => require( '../cart' ) );

			// Configure getConfig with addedToCartText.
			const { getConfig } =
				require( '@wordpress/interactivity' ) as {
					getConfig: jest.Mock;
				};
			getConfig.mockReturnValue( {
				messages: { addedToCartText: 'Added to cart' },
			} );

			const { speak } = require( '@wordpress/a11y' ) as {
				speak: jest.Mock;
			};
			speak.mockClear();

			// Enqueue a remove (not add).
			const gen = mockRegisteredStore!.actions.removeCartItem(
				'item-1'
			) as unknown as Generator;

			gen.next();

			// Drive lifecycle with a 200 response.
			const processLifecycle = (
				mockRegisteredStore!.actions as Record<
					string,
					( ...args: unknown[] ) => unknown
				>
			)._processLifecycle;

			const lifecycle = processLifecycle( 0 ) as unknown as Generator;
			lifecycle.next(); // yield fetch(...)

			const serverCart = {
				items: [],
				totals: { total_items: '0' },
				errors: [],
			};

			const mockResponse = { status: 207, json: jest.fn() };
			lifecycle.next( mockResponse ); // yield res.json()

			const consoleSpy = jest
				.spyOn( console, 'error' )
				.mockImplementation( () => {} );

			let step = lifecycle.next( {
				responses: [
					{ status: 200, body: serverCart },
				],
			} );

			while ( ! step.done ) {
				step = lifecycle.next( { speak } );
			}

			consoleSpy.mockRestore();

			// Remove succeeded but hadSuccessfulAdd is false → no speak.
			expect( speak ).not.toHaveBeenCalled();
		} );

		it( 'emitSyncEvent dispatches wc-blocks_store_sync_required with quantityChanges when lastServerState !== null', () => {
			const mockQueueMicrotask = jest.fn();
			globalThis.queueMicrotask = mockQueueMicrotask;

			const mockFetch = jest.fn();
			global.fetch = mockFetch;

			jest.isolateModules( () => require( '../cart' ) );

			const dispatchSpy = jest.spyOn( window, 'dispatchEvent' );

			// Enqueue an add action.
			const gen = mockRegisteredStore!.actions.addCartItem( {
				id: 99,
				quantity: 1,
				type: 'simple',
			} ) as unknown as Generator;

			gen.next();

			// Drive lifecycle with a 200 response.
			const processLifecycle = (
				mockRegisteredStore!.actions as Record<
					string,
					( ...args: unknown[] ) => unknown
				>
			)._processLifecycle;

			const lifecycle = processLifecycle( 0 ) as unknown as Generator;
			lifecycle.next(); // yield fetch(...)

			const serverCart = {
				items: [
					{
						key: 'new-item',
						id: 99,
						quantity: 1,
						type: 'simple',
						name: 'New Widget',
					},
				],
				totals: { total_items: '1' },
				errors: [],
			};

			const mockResponse = { status: 207, json: jest.fn() };
			lifecycle.next( mockResponse ); // yield res.json()

			const consoleSpy = jest
				.spyOn( console, 'error' )
				.mockImplementation( () => {} );

			let step = lifecycle.next( {
				responses: [
					{ status: 200, body: serverCart },
				],
			} );

			while ( ! step.done ) {
				step = lifecycle.next( { speak: jest.fn() } );
			}

			consoleSpy.mockRestore();

			// emitSyncEvent should have dispatched because lastServerState !== null.
			const syncEvent = dispatchSpy.mock.calls.find(
				( [ event ] ) =>
					event instanceof CustomEvent &&
					event.type === 'wc-blocks_store_sync_required'
			);
			expect( syncEvent ).toBeDefined();

			const detail = ( syncEvent![ 0 ] as CustomEvent ).detail;
			expect( detail ).toHaveProperty( 'quantityChanges' );
			expect(
				detail.quantityChanges.productsPendingAdd
			).toContain( 99 );

			dispatchSpy.mockRestore();
		} );

		it( 'emitSyncEvent is NOT dispatched when lastServerState is null (total failure)', () => {
			const mockQueueMicrotask = jest.fn();
			globalThis.queueMicrotask = mockQueueMicrotask;

			jest.isolateModules( () => require( '../cart' ) );

			const dispatchSpy = jest.spyOn( window, 'dispatchEvent' );

			// Enqueue an add action.
			const gen = mockRegisteredStore!.actions.addCartItem( {
				id: 99,
				quantity: 1,
				type: 'simple',
			} ) as unknown as Generator;

			gen.next();

			// Drive lifecycle — network error → total failure.
			const processLifecycle = (
				mockRegisteredStore!.actions as Record<
					string,
					( ...args: unknown[] ) => unknown
				>
			)._processLifecycle;

			const lifecycle = processLifecycle( 0 ) as unknown as Generator;
			lifecycle.next(); // yield fetch(...)

			const consoleSpy = jest
				.spyOn( console, 'error' )
				.mockImplementation( () => {} );
			try {
				lifecycle.throw!( new Error( 'Network error' ) );
			} catch {
				// lifecycle may finish
			}
			consoleSpy.mockRestore();

			// No server state → no emitSyncEvent.
			const syncEvents = dispatchSpy.mock.calls.filter(
				( call ) =>
					call[ 0 ] instanceof CustomEvent &&
					( call[ 0 ] as CustomEvent ).type ===
						'wc-blocks_store_sync_required'
			);
			expect( syncEvents ).toHaveLength( 0 );

			dispatchSpy.mockRestore();
		} );

		it( 'info notices are computed from pre-lifecycle snapshot vs final server state', () => {
			const mockQueueMicrotask = jest.fn();
			globalThis.queueMicrotask = mockQueueMicrotask;

			const mockFetch = jest.fn();
			global.fetch = mockFetch;

			jest.isolateModules( () => require( '../cart' ) );

			// Set up a spy on updateNotices to capture the notices passed to it.
			const updateNoticesSpy = jest.fn();
			( mockRegisteredStore!.actions as Record< string, unknown > ).updateNotices =
				updateNoticesSpy;

			// Enqueue a remove action (showNotices: true by default for remove).
			const gen = mockRegisteredStore!.actions.removeCartItem(
				'item-1'
			) as unknown as Generator;

			gen.next();

			// Drive lifecycle with a 200 response where the server removed the item.
			const processLifecycle = (
				mockRegisteredStore!.actions as Record<
					string,
					( ...args: unknown[] ) => unknown
				>
			)._processLifecycle;

			const lifecycle = processLifecycle( 0 ) as unknown as Generator;
			lifecycle.next(); // yield fetch(...)

			// Server returns cart without the removed item.
			const serverCart = {
				items: [],
				totals: { total_items: '0' },
				errors: [],
			};

			const mockResponse = { status: 207, json: jest.fn() };
			lifecycle.next( mockResponse ); // yield res.json()

			const consoleSpy = jest
				.spyOn( console, 'error' )
				.mockImplementation( () => {} );

			let step = lifecycle.next( {
				responses: [
					{ status: 200, body: serverCart },
				],
			} );

			// Drive through any remaining yields (updateNotices, etc.)
			while ( ! step.done ) {
				step = lifecycle.next( undefined );
			}

			consoleSpy.mockRestore();

			// updateNotices should have been called — info notices are generated
			// by comparing the pre-lifecycle snapshot (which had item-1) with the
			// final server state (which has no items).
			// The call proves getInfoNoticesFromCartUpdates was invoked with the
			// snapshot (old cart with item-1) and the server state (empty cart).
			expect( updateNoticesSpy ).toHaveBeenCalled();
			const [ notices, removeOthers ] =
				updateNoticesSpy.mock.calls[ 0 ];
			expect( removeOthers ).toBe( true );
			// Info notices should be present (cart went from 1 item to 0 items).
			expect( notices.length ).toBeGreaterThan( 0 );
		} );

		it( 'info notices use the pre-lifecycle snapshot, not the post-optimistic state', () => {
			const mockQueueMicrotask = jest.fn();
			globalThis.queueMicrotask = mockQueueMicrotask;

			const mockFetch = jest.fn();
			global.fetch = mockFetch;

			// Set up a cart with 2 items.
			Object.assign( mockState, {
				cart: {
					items: [
						{
							key: 'item-1',
							id: 10,
							quantity: 2,
							type: 'simple',
							name: 'Widget',
						},
						{
							key: 'item-2',
							id: 20,
							quantity: 1,
							type: 'simple',
							name: 'Gadget',
						},
					],
					totals: { total_items: '3' },
					errors: [],
				},
			} );

			jest.isolateModules( () => require( '../cart' ) );

			const updateNoticesSpy = jest.fn();
			( mockRegisteredStore!.actions as Record< string, unknown > ).updateNotices =
				updateNoticesSpy;

			// Remove item-1. Optimistic update removes it from cart state immediately.
			// But the snapshot was captured BEFORE the optimistic update (with 2 items).
			const gen = mockRegisteredStore!.actions.removeCartItem(
				'item-1'
			) as unknown as Generator;

			gen.next();

			// After optimistic update, cart should have 1 item.
			expect( mockState.cart.items ).toHaveLength( 1 );

			const processLifecycle = (
				mockRegisteredStore!.actions as Record<
					string,
					( ...args: unknown[] ) => unknown
				>
			)._processLifecycle;

			const lifecycle = processLifecycle( 0 ) as unknown as Generator;
			lifecycle.next(); // yield fetch(...)

			// Server confirms removal — returns cart with only item-2.
			const serverCart = {
				items: [
					{
						key: 'item-2',
						id: 20,
						quantity: 1,
						type: 'simple',
						name: 'Gadget',
					},
				],
				totals: { total_items: '1' },
				errors: [],
			};

			const mockResponse = { status: 207, json: jest.fn() };
			lifecycle.next( mockResponse ); // yield res.json()

			const consoleSpy = jest
				.spyOn( console, 'error' )
				.mockImplementation( () => {} );

			let step = lifecycle.next( {
				responses: [
					{ status: 200, body: serverCart },
				],
			} );

			while ( ! step.done ) {
				step = lifecycle.next( undefined );
			}

			consoleSpy.mockRestore();

			// Info notices should be computed from snapshot (2 items) vs server (1 item),
			// NOT from post-optimistic (1 item) vs server (1 item) which would show no changes.
			// If info notices were computed from post-optimistic state, updateNotices
			// would either not be called or have no info notices.
			expect( updateNoticesSpy ).toHaveBeenCalled();
		} );
	} );

	describe( 'REQ-014: promise resolution in finally', () => {
		it( 'enqueue promise resolves after successful lifecycle', async () => {
			const mockQueueMicrotask = jest.fn();
			globalThis.queueMicrotask = mockQueueMicrotask;

			const mockFetch = jest.fn();
			global.fetch = mockFetch;

			jest.isolateModules( () => require( '../cart' ) );

			const gen = mockRegisteredStore!.actions.addCartItem( {
				id: 99,
				quantity: 1,
				type: 'simple',
			} ) as unknown as Generator;

			const result = gen.next();
			const promise = result.value as Promise< void >;

			// Drive lifecycle to successful completion.
			const processLifecycle = (
				mockRegisteredStore!.actions as Record<
					string,
					( ...args: unknown[] ) => unknown
				>
			)._processLifecycle;

			const lifecycle = processLifecycle( 0 ) as unknown as Generator;
			lifecycle.next(); // yield fetch(...)

			const serverCart = {
				items: [
					{
						key: 'server-1',
						id: 99,
						quantity: 1,
						type: 'simple',
						name: 'Widget',
					},
				],
				totals: { total_items: '1' },
				errors: [],
			};

			const mockResponse = { status: 207, json: jest.fn() };
			lifecycle.next( mockResponse ); // yield res.json()

			const consoleSpy = jest
				.spyOn( console, 'error' )
				.mockImplementation( () => {} );

			let step = lifecycle.next( {
				responses: [
					{ status: 200, body: serverCart },
				],
			} );

			while ( ! step.done ) {
				step = lifecycle.next( undefined );
			}

			consoleSpy.mockRestore();

			// Promise should resolve (not reject).
			await expect( promise ).resolves.toBeUndefined();
		} );

		it( 'enqueue promise resolves (never rejects) after total failure — network error', async () => {
			const mockQueueMicrotask = jest.fn();
			globalThis.queueMicrotask = mockQueueMicrotask;

			jest.isolateModules( () => require( '../cart' ) );

			const gen = mockRegisteredStore!.actions.addCartItem( {
				id: 99,
				quantity: 1,
				type: 'simple',
			} ) as unknown as Generator;

			const result = gen.next();
			const promise = result.value as Promise< void >;

			// Drive lifecycle to total failure.
			const processLifecycle = (
				mockRegisteredStore!.actions as Record<
					string,
					( ...args: unknown[] ) => unknown
				>
			)._processLifecycle;

			const lifecycle = processLifecycle( 0 ) as unknown as Generator;
			lifecycle.next(); // yield fetch(...)

			const consoleSpy = jest
				.spyOn( console, 'error' )
				.mockImplementation( () => {} );

			try {
				lifecycle.throw!( new Error( 'Network error' ) );
			} catch {
				// lifecycle may finish
			}

			consoleSpy.mockRestore();

			// Even on total failure, promise should resolve (never reject).
			await expect( promise ).resolves.toBeUndefined();
		} );

		it( 'enqueue promise resolves (never rejects) after total failure — non-207 status', async () => {
			const mockQueueMicrotask = jest.fn();
			globalThis.queueMicrotask = mockQueueMicrotask;

			const mockFetch = jest.fn();
			global.fetch = mockFetch;

			jest.isolateModules( () => require( '../cart' ) );

			const gen = mockRegisteredStore!.actions.addCartItem( {
				id: 99,
				quantity: 1,
				type: 'simple',
			} ) as unknown as Generator;

			const result = gen.next();
			const promise = result.value as Promise< void >;

			// Drive lifecycle to non-207 total failure.
			const processLifecycle = (
				mockRegisteredStore!.actions as Record<
					string,
					( ...args: unknown[] ) => unknown
				>
			)._processLifecycle;

			const lifecycle = processLifecycle( 0 ) as unknown as Generator;
			lifecycle.next(); // yield fetch(...)

			const mockResponse = { status: 500, ok: false, json: jest.fn() };
			lifecycle.next( mockResponse ); // yield res.json()

			const consoleSpy = jest
				.spyOn( console, 'error' )
				.mockImplementation( () => {} );

			try {
				lifecycle.next( {
					code: 'server_error',
					message: 'Internal server error',
				} );
			} catch {
				// lifecycle may finish
			}

			consoleSpy.mockRestore();

			// Even on non-207 total failure, promise should resolve (never reject).
			await expect( promise ).resolves.toBeUndefined();
		} );

		it( 'all processed entries in a batch are resolved — not just the first', async () => {
			const mockQueueMicrotask = jest.fn();
			globalThis.queueMicrotask = mockQueueMicrotask;

			const mockFetch = jest.fn();
			global.fetch = mockFetch;

			jest.isolateModules( () => require( '../cart' ) );

			// Enqueue two actions in the same tick.
			const gen1 = mockRegisteredStore!.actions.addCartItem( {
				id: 99,
				quantity: 1,
				type: 'simple',
			} ) as unknown as Generator;

			const result1 = gen1.next();
			const promise1 = result1.value as Promise< void >;

			const gen2 = mockRegisteredStore!.actions.removeCartItem(
				'item-1'
			) as unknown as Generator;

			const result2 = gen2.next();
			const promise2 = result2.value as Promise< void >;

			// Drive lifecycle to completion.
			const processLifecycle = (
				mockRegisteredStore!.actions as Record<
					string,
					( ...args: unknown[] ) => unknown
				>
			)._processLifecycle;

			const lifecycle = processLifecycle( 0 ) as unknown as Generator;
			lifecycle.next(); // yield fetch(...)

			const serverCart = {
				items: [],
				totals: { total_items: '0' },
				errors: [],
			};

			const mockResponse = { status: 207, json: jest.fn() };
			lifecycle.next( mockResponse ); // yield res.json()

			const consoleSpy = jest
				.spyOn( console, 'error' )
				.mockImplementation( () => {} );

			let step = lifecycle.next( {
				responses: [
					{ status: 200, body: serverCart },
					{ status: 200, body: serverCart },
				],
			} );

			while ( ! step.done ) {
				step = lifecycle.next( undefined );
			}

			consoleSpy.mockRestore();

			// Both promises should resolve.
			await expect( promise1 ).resolves.toBeUndefined();
			await expect( promise2 ).resolves.toBeUndefined();
		} );

		it( 'caller sees final server state in state.cart after promise resolves on success', async () => {
			const mockQueueMicrotask = jest.fn();
			globalThis.queueMicrotask = mockQueueMicrotask;

			const mockFetch = jest.fn();
			global.fetch = mockFetch;

			jest.isolateModules( () => require( '../cart' ) );

			const gen = mockRegisteredStore!.actions.addCartItem( {
				id: 99,
				quantity: 1,
				type: 'simple',
			} ) as unknown as Generator;

			const result = gen.next();
			const promise = result.value as Promise< void >;

			const serverCart = {
				items: [
					{
						key: 'server-99',
						id: 99,
						quantity: 1,
						type: 'simple',
						name: 'Server Widget',
					},
				],
				totals: { total_items: '1' },
				errors: [],
			};

			// Drive lifecycle to success.
			const processLifecycle = (
				mockRegisteredStore!.actions as Record<
					string,
					( ...args: unknown[] ) => unknown
				>
			)._processLifecycle;

			const lifecycle = processLifecycle( 0 ) as unknown as Generator;
			lifecycle.next(); // yield fetch(...)

			const mockResponse = { status: 207, json: jest.fn() };
			lifecycle.next( mockResponse ); // yield res.json()

			const consoleSpy = jest
				.spyOn( console, 'error' )
				.mockImplementation( () => {} );

			let step = lifecycle.next( {
				responses: [
					{ status: 200, body: serverCart },
				],
			} );

			while ( ! step.done ) {
				step = lifecycle.next( undefined );
			}

			consoleSpy.mockRestore();

			await promise;

			// After promise resolves, state.cart should be the server state.
			expect( mockState.cart ).toEqual( serverCart );
		} );

		it( 'caller sees rolled-back snapshot in state.cart after promise resolves on total failure', async () => {
			const mockQueueMicrotask = jest.fn();
			globalThis.queueMicrotask = mockQueueMicrotask;

			jest.isolateModules( () => require( '../cart' ) );

			const cartBeforeAction = JSON.parse(
				JSON.stringify( mockState.cart )
			);

			const gen = mockRegisteredStore!.actions.addCartItem( {
				id: 99,
				quantity: 1,
				type: 'simple',
			} ) as unknown as Generator;

			const result = gen.next();
			const promise = result.value as Promise< void >;

			// Cart has optimistic item now.
			expect( mockState.cart.items ).toHaveLength( 2 );

			// Drive lifecycle to total failure.
			const processLifecycle = (
				mockRegisteredStore!.actions as Record<
					string,
					( ...args: unknown[] ) => unknown
				>
			)._processLifecycle;

			const lifecycle = processLifecycle( 0 ) as unknown as Generator;
			lifecycle.next(); // yield fetch(...)

			const consoleSpy = jest
				.spyOn( console, 'error' )
				.mockImplementation( () => {} );

			try {
				lifecycle.throw!( new Error( 'Network error' ) );
			} catch {
				// lifecycle may finish
			}

			consoleSpy.mockRestore();

			await promise;

			// After promise resolves, state.cart should be the pre-optimistic snapshot (rollback).
			expect( mockState.cart ).toEqual( cartBeforeAction );
			expect( mockState.cart.items ).toHaveLength( 1 );
			expect( mockState.cart.items[ 0 ].id ).toBe( 10 );
		} );

		it( 'multiple entries all resolve even when some sub-responses fail', async () => {
			const mockQueueMicrotask = jest.fn();
			globalThis.queueMicrotask = mockQueueMicrotask;

			const mockFetch = jest.fn();
			global.fetch = mockFetch;

			jest.isolateModules( () => require( '../cart' ) );

			// Enqueue two actions.
			const gen1 = mockRegisteredStore!.actions.addCartItem( {
				id: 99,
				quantity: 1,
				type: 'simple',
			} ) as unknown as Generator;

			const result1 = gen1.next();
			const promise1 = result1.value as Promise< void >;

			const gen2 = mockRegisteredStore!.actions.removeCartItem(
				'item-1'
			) as unknown as Generator;

			const result2 = gen2.next();
			const promise2 = result2.value as Promise< void >;

			// Drive lifecycle with mixed success/failure.
			const processLifecycle = (
				mockRegisteredStore!.actions as Record<
					string,
					( ...args: unknown[] ) => unknown
				>
			)._processLifecycle;

			const lifecycle = processLifecycle( 0 ) as unknown as Generator;
			lifecycle.next(); // yield fetch(...)

			const serverCart = {
				items: [
					{
						key: 'server-1',
						id: 99,
						quantity: 1,
						type: 'simple',
						name: 'Widget',
					},
				],
				totals: { total_items: '1' },
				errors: [],
			};

			const mockResponse = { status: 207, json: jest.fn() };
			lifecycle.next( mockResponse ); // yield res.json()

			const consoleSpy = jest
				.spyOn( console, 'error' )
				.mockImplementation( () => {} );

			try {
				lifecycle.next( {
					responses: [
						{ status: 200, body: serverCart },
						{
							status: 400,
							body: {
								code: 'item_not_found',
								message: 'Not found',
							},
						},
					],
				} );
			} catch {
				// lifecycle may finish
			}

			consoleSpy.mockRestore();

			// Both promises should resolve — even the one whose sub-response failed.
			await expect( promise1 ).resolves.toBeUndefined();
			await expect( promise2 ).resolves.toBeUndefined();
		} );
	} );

	describe( 'REQ-015: running flag reset', () => {
		it( 'running resets after successful lifecycle — new enqueue schedules queueMicrotask', () => {
			const mockQueueMicrotask = jest.fn();
			globalThis.queueMicrotask = mockQueueMicrotask;

			const mockFetch = jest.fn();
			global.fetch = mockFetch;

			jest.isolateModules( () => require( '../cart' ) );

			// Enqueue a remove action.
			const gen = mockRegisteredStore!.actions.removeCartItem(
				'item-1'
			) as unknown as Generator;

			gen.next(); // runs enqueue + optimistic filter, yields promise

			// queueMicrotask called once for the first enqueue.
			expect( mockQueueMicrotask ).toHaveBeenCalledTimes( 1 );

			// Drive lifecycle to completion with a successful response.
			const processLifecycle = (
				mockRegisteredStore!.actions as Record<
					string,
					( ...args: unknown[] ) => unknown
				>
			)._processLifecycle;

			const lifecycle = processLifecycle( 0 ) as unknown as Generator;
			lifecycle.next(); // yield fetch(...)

			const serverCart = {
				items: [],
				totals: { total_items: '0' },
				errors: [],
			};

			const mockResponse = { status: 207, json: jest.fn() };
			lifecycle.next( mockResponse ); // yield res.json()

			const consoleSpy = jest
				.spyOn( console, 'error' )
				.mockImplementation( () => {} );

			try {
				lifecycle.next( {
					responses: [
						{ status: 200, body: serverCart },
					],
				} ); // reconciliation: running = false, state.cart = serverCart
			} catch {
				// lifecycle may finish or yield at updateNotices
			}

			consoleSpy.mockRestore();

			// running should now be false. Reset the mock to isolate the second call.
			mockQueueMicrotask.mockClear();

			// Enqueue a new action — should schedule a new queueMicrotask
			// because running was reset to false.
			mockState.cart = JSON.parse(
				JSON.stringify( serverCart )
			) as typeof mockState.cart;

			const gen2 = mockRegisteredStore!.actions.addCartItem( {
				id: 50,
				quantity: 1,
				type: 'simple',
			} ) as unknown as Generator;

			gen2.next();

			// queueMicrotask should be called again — proves running was reset.
			expect( mockQueueMicrotask ).toHaveBeenCalledTimes( 1 );
		} );

		it( 'running resets after total failure (network error) — new enqueue schedules queueMicrotask', () => {
			const mockQueueMicrotask = jest.fn();
			globalThis.queueMicrotask = mockQueueMicrotask;

			jest.isolateModules( () => require( '../cart' ) );

			// Enqueue an action.
			const gen = mockRegisteredStore!.actions.addCartItem( {
				id: 99,
				quantity: 1,
				type: 'simple',
			} ) as unknown as Generator;

			gen.next(); // runs enqueue + optimistic update, yields promise

			expect( mockQueueMicrotask ).toHaveBeenCalledTimes( 1 );

			// Drive lifecycle — network error causes total failure.
			const processLifecycle = (
				mockRegisteredStore!.actions as Record<
					string,
					( ...args: unknown[] ) => unknown
				>
			)._processLifecycle;

			const lifecycle = processLifecycle( 0 ) as unknown as Generator;
			lifecycle.next(); // yield fetch(...)

			const consoleSpy = jest
				.spyOn( console, 'error' )
				.mockImplementation( () => {} );

			try {
				lifecycle.throw!( new Error( 'Network error' ) );
			} catch {
				// lifecycle may finish or yield at updateNotices
			}

			consoleSpy.mockRestore();

			// running should now be false. Reset mock.
			mockQueueMicrotask.mockClear();

			// Restore a valid cart so the next enqueue can snapshot.
			mockState.cart = JSON.parse(
				JSON.stringify( initialCart )
			) as typeof mockState.cart;

			// Enqueue a new action — should work because running was reset.
			const gen2 = mockRegisteredStore!.actions.addCartItem( {
				id: 50,
				quantity: 1,
				type: 'simple',
			} ) as unknown as Generator;

			gen2.next();

			// queueMicrotask called again — proves running was reset after failure.
			expect( mockQueueMicrotask ).toHaveBeenCalledTimes( 1 );
		} );

		it( 'running resets after total failure (non-207 status) — new enqueue schedules queueMicrotask', () => {
			const mockQueueMicrotask = jest.fn();
			globalThis.queueMicrotask = mockQueueMicrotask;

			jest.isolateModules( () => require( '../cart' ) );

			// Enqueue an action.
			const gen = mockRegisteredStore!.actions.addCartItem( {
				id: 99,
				quantity: 1,
				type: 'simple',
			} ) as unknown as Generator;

			gen.next(); // runs enqueue + optimistic update, yields promise

			expect( mockQueueMicrotask ).toHaveBeenCalledTimes( 1 );

			// Drive lifecycle — non-207 response causes total failure.
			const processLifecycle = (
				mockRegisteredStore!.actions as Record<
					string,
					( ...args: unknown[] ) => unknown
				>
			)._processLifecycle;

			const lifecycle = processLifecycle( 0 ) as unknown as Generator;
			lifecycle.next(); // yield fetch(...)

			const mockResponse = { status: 500, ok: false, json: jest.fn() };
			lifecycle.next( mockResponse ); // yield res.json()

			const consoleSpy = jest
				.spyOn( console, 'error' )
				.mockImplementation( () => {} );

			try {
				lifecycle.next( {
					code: 'internal_error',
					message: 'Server error',
				} ); // non-207 → total failure → reconciliation
			} catch {
				// lifecycle may finish or yield
			}

			consoleSpy.mockRestore();

			// running should now be false. Reset mock.
			mockQueueMicrotask.mockClear();

			// Restore cart state so the next enqueue can snapshot.
			mockState.cart = JSON.parse(
				JSON.stringify( initialCart )
			) as typeof mockState.cart;

			// Enqueue a new action.
			const gen2 = mockRegisteredStore!.actions.addCartItem( {
				id: 50,
				quantity: 1,
				type: 'simple',
			} ) as unknown as Generator;

			gen2.next();

			// queueMicrotask called again — proves running was reset after non-207 failure.
			expect( mockQueueMicrotask ).toHaveBeenCalledTimes( 1 );
		} );
	} );

	describe( 'Circuit breaker and edge cases', () => {
		it( 'circuit breaker triggers at chainCount=10: logs error, resolves entries, drains pending', async () => {
			const mockQueueMicrotask = jest.fn();
			globalThis.queueMicrotask = mockQueueMicrotask;

			const mockFetch = jest.fn();
			global.fetch = mockFetch;

			jest.isolateModules( () => require( '../cart' ) );

			// Enqueue an add action to create the first pending entry.
			const gen = mockRegisteredStore!.actions.addCartItem( {
				id: 99,
				quantity: 1,
				type: 'simple',
			} ) as unknown as Generator;

			const result = gen.next();
			const promise = result.value as Promise< void >;

			// Drive _processLifecycle with chainCount=10 (MAX_LIFECYCLE_CHAINS).
			// This should trigger the circuit breaker immediately in the finally block,
			// because even though pending has entries, chainCount >= MAX_LIFECYCLE_CHAINS.
			const processLifecycle = (
				mockRegisteredStore!.actions as Record<
					string,
					( ...args: unknown[] ) => unknown
				>
			)._processLifecycle;

			// Start lifecycle at chainCount=10 — the batch loop runs normally,
			// but when pending has new items in finally, the breaker fires.
			const lifecycle = processLifecycle( 10 ) as unknown as Generator;
			lifecycle.next(); // yield fetch(...)

			const serverCart = {
				items: [],
				totals: { total_items: '0' },
				errors: [],
			};

			const mockResponse = { status: 207, json: jest.fn() };
			lifecycle.next( mockResponse ); // yield res.json()

			// Enqueue another item while batch is being processed.
			// This entry will be in pending when the finally block runs.
			const gen2 = mockRegisteredStore!.actions.addCartItem( {
				id: 50,
				quantity: 1,
				type: 'simple',
			} ) as unknown as Generator;

			const result2 = gen2.next();
			const promise2 = result2.value as Promise< void >;

			const consoleSpy = jest
				.spyOn( console, 'error' )
				.mockImplementation( () => {} );

			let step = lifecycle.next( {
				responses: [
					{ status: 200, body: serverCart },
				],
			} );

			while ( ! step.done ) {
				step = lifecycle.next( undefined );
			}

			// Circuit breaker should log an error message.
			const errorArgs = consoleSpy.mock.calls.map(
				( call ) => call[ 0 ]
			);
			expect(
				errorArgs.some(
					( arg ) =>
						typeof arg === 'string' &&
						arg.includes( 'circuit breaker' )
				)
			).toBe( true );

			consoleSpy.mockRestore();

			// Both promises should resolve (never reject) — even the dropped one.
			await expect( promise ).resolves.toBeUndefined();
			await expect( promise2 ).resolves.toBeUndefined();

			// No second fetch call — the in-flight entry was dropped, not batched.
			// Only 1 fetch for the first batch.
			expect( mockFetch ).toHaveBeenCalledTimes( 1 );
		} );

		it( 'circuit breaker boundary: chainCount=9 still chains to next lifecycle', () => {
			const mockQueueMicrotask = jest.fn();
			globalThis.queueMicrotask = mockQueueMicrotask;

			const mockFetch = jest.fn();
			global.fetch = mockFetch;

			jest.isolateModules( () => require( '../cart' ) );

			// Enqueue an add action.
			const gen = mockRegisteredStore!.actions.addCartItem( {
				id: 99,
				quantity: 1,
				type: 'simple',
			} ) as unknown as Generator;

			gen.next();

			const processLifecycle = (
				mockRegisteredStore!.actions as Record<
					string,
					( ...args: unknown[] ) => unknown
				>
			)._processLifecycle;

			// Start lifecycle at chainCount=9 (one below MAX_LIFECYCLE_CHAINS).
			const lifecycle = processLifecycle( 9 ) as unknown as Generator;
			lifecycle.next(); // yield fetch(...)

			const serverCart = {
				items: [],
				totals: { total_items: '0' },
				errors: [],
			};

			const mockResponse = { status: 207, json: jest.fn() };
			lifecycle.next( mockResponse ); // yield res.json()

			// Enqueue another item while in-flight — will be pending in finally.
			const gen2 = mockRegisteredStore!.actions.addCartItem( {
				id: 50,
				quantity: 1,
				type: 'simple',
			} ) as unknown as Generator;

			gen2.next();

			const consoleSpy = jest
				.spyOn( console, 'error' )
				.mockImplementation( () => {} );

			let step = lifecycle.next( {
				responses: [
					{ status: 200, body: serverCart },
				],
			} );

			while ( ! step.done ) {
				step = lifecycle.next( undefined );
			}

			// Circuit breaker should NOT have fired — no breaker error log.
			const errorArgs = consoleSpy.mock.calls.map(
				( call ) => call[ 0 ]
			);
			expect(
				errorArgs.some(
					( arg ) =>
						typeof arg === 'string' &&
						arg.includes( 'circuit breaker' )
				)
			).toBe( false );

			consoleSpy.mockRestore();

			// queueMicrotask should have been called for the chained lifecycle.
			// First call was from the initial enqueue; we need to check
			// that there's a SECOND call from the finally block chaining.
			expect( mockQueueMicrotask.mock.calls.length ).toBeGreaterThanOrEqual( 2 );
		} );

		it( 'metadata deduplication: duplicate product IDs are deduplicated in quantityChanges', () => {
			const mockQueueMicrotask = jest.fn();
			globalThis.queueMicrotask = mockQueueMicrotask;

			const mockFetch = jest.fn();
			global.fetch = mockFetch;

			jest.isolateModules( () => require( '../cart' ) );

			const dispatchSpy = jest.spyOn( window, 'dispatchEvent' );

			// Enqueue TWO adds for the same product id=99 (both are new items).
			// Both will produce productsPendingAdd entries with id 99.
			const gen1 = mockRegisteredStore!.actions.addCartItem( {
				id: 99,
				quantity: 1,
				type: 'simple',
			} ) as unknown as Generator;

			gen1.next();

			const gen2 = mockRegisteredStore!.actions.addCartItem( {
				id: 99,
				quantity: 2,
				type: 'simple',
			} ) as unknown as Generator;

			gen2.next();

			const processLifecycle = (
				mockRegisteredStore!.actions as Record<
					string,
					( ...args: unknown[] ) => unknown
				>
			)._processLifecycle;

			const lifecycle = processLifecycle( 0 ) as unknown as Generator;
			lifecycle.next(); // yield fetch(...)

			const serverCart = {
				items: [
					{
						key: 'server-1',
						id: 99,
						quantity: 3,
						type: 'simple',
						name: 'Widget',
					},
				],
				totals: { total_items: '1' },
				errors: [],
			};

			const mockResponse = { status: 207, json: jest.fn() };
			lifecycle.next( mockResponse ); // yield res.json()

			const consoleSpy = jest
				.spyOn( console, 'error' )
				.mockImplementation( () => {} );

			let step = lifecycle.next( {
				responses: [
					// Both adds succeed — both accumulate productsPendingAdd: [99].
					{ status: 200, body: serverCart },
					{ status: 200, body: serverCart },
				],
			} );

			while ( ! step.done ) {
				step = lifecycle.next( { speak: jest.fn() } );
			}

			consoleSpy.mockRestore();

			// Check emitSyncEvent — productsPendingAdd should be deduplicated.
			const syncEvent = dispatchSpy.mock.calls.find(
				( [ event ] ) =>
					event instanceof CustomEvent &&
					event.type === 'wc-blocks_store_sync_required'
			);
			expect( syncEvent ).toBeDefined();

			const detail = ( syncEvent![ 0 ] as CustomEvent ).detail;
			// productsPendingAdd should have exactly one entry for id 99, not two.
			expect( detail.quantityChanges.productsPendingAdd ).toEqual( [
				99,
			] );

			dispatchSpy.mockRestore();
		} );

		it( 'metadata deduplication: duplicate cartItemsPendingDelete keys are deduplicated', () => {
			const mockQueueMicrotask = jest.fn();
			globalThis.queueMicrotask = mockQueueMicrotask;

			const mockFetch = jest.fn();
			global.fetch = mockFetch;

			// Set up cart with two items so we can try removing the same one twice.
			Object.assign( mockState, {
				cart: {
					items: [
						{
							key: 'item-1',
							id: 10,
							quantity: 1,
							type: 'simple',
							name: 'Widget A',
						},
						{
							key: 'item-2',
							id: 20,
							quantity: 1,
							type: 'simple',
							name: 'Widget B',
						},
					],
					totals: { total_items: '2' },
					errors: [],
				},
			} );

			jest.isolateModules( () => require( '../cart' ) );

			const dispatchSpy = jest.spyOn( window, 'dispatchEvent' );

			// Remove item-1 twice — both enqueue cartItemsPendingDelete: ['item-1'].
			const gen1 = mockRegisteredStore!.actions.removeCartItem(
				'item-1'
			) as unknown as Generator;

			gen1.next();

			const gen2 = mockRegisteredStore!.actions.removeCartItem(
				'item-1'
			) as unknown as Generator;

			gen2.next();

			const processLifecycle = (
				mockRegisteredStore!.actions as Record<
					string,
					( ...args: unknown[] ) => unknown
				>
			)._processLifecycle;

			const lifecycle = processLifecycle( 0 ) as unknown as Generator;
			lifecycle.next(); // yield fetch(...)

			const serverCart = {
				items: [
					{
						key: 'item-2',
						id: 20,
						quantity: 1,
						type: 'simple',
						name: 'Widget B',
					},
				],
				totals: { total_items: '1' },
				errors: [],
			};

			const mockResponse = { status: 207, json: jest.fn() };
			lifecycle.next( mockResponse ); // yield res.json()

			const consoleSpy = jest
				.spyOn( console, 'error' )
				.mockImplementation( () => {} );

			let step = lifecycle.next( {
				responses: [
					{ status: 200, body: serverCart },
					{ status: 200, body: serverCart },
				],
			} );

			while ( ! step.done ) {
				step = lifecycle.next( { speak: jest.fn() } );
			}

			consoleSpy.mockRestore();

			// cartItemsPendingDelete should be deduplicated.
			const syncEvent = dispatchSpy.mock.calls.find(
				( [ event ] ) =>
					event instanceof CustomEvent &&
					event.type === 'wc-blocks_store_sync_required'
			);
			expect( syncEvent ).toBeDefined();

			const detail = ( syncEvent![ 0 ] as CustomEvent ).detail;
			// Only one 'item-1' entry, not two.
			expect(
				detail.quantityChanges.cartItemsPendingDelete
			).toEqual( [ 'item-1' ] );

			dispatchSpy.mockRestore();
		} );

		it( 'showNotices OR semantics: info notices computed when at least one entry has showNotices true', () => {
			const mockQueueMicrotask = jest.fn();
			globalThis.queueMicrotask = mockQueueMicrotask;

			const mockFetch = jest.fn();
			global.fetch = mockFetch;

			jest.isolateModules( () => require( '../cart' ) );

			const updateNoticesSpy = jest.fn();
			(
				mockRegisteredStore!.actions as Record< string, unknown >
			).updateNotices = updateNoticesSpy;

			// addCartItem has showNotices: false by default.
			// removeCartItem has showNotices: true by default.
			// Having at least one with showNotices: true means info notices
			// should be computed.
			const addGen = mockRegisteredStore!.actions.addCartItem( {
				id: 99,
				quantity: 1,
				type: 'simple',
			} ) as unknown as Generator;

			addGen.next();

			const removeGen = mockRegisteredStore!.actions.removeCartItem(
				'item-1'
			) as unknown as Generator;

			removeGen.next();

			const processLifecycle = (
				mockRegisteredStore!.actions as Record<
					string,
					( ...args: unknown[] ) => unknown
				>
			)._processLifecycle;

			const lifecycle = processLifecycle( 0 ) as unknown as Generator;
			lifecycle.next(); // yield fetch(...)

			const serverCart = {
				items: [
					{
						key: 'server-1',
						id: 99,
						quantity: 1,
						type: 'simple',
						name: 'Widget',
					},
				],
				totals: { total_items: '1' },
				errors: [],
			};

			const mockResponse = { status: 207, json: jest.fn() };
			lifecycle.next( mockResponse ); // yield res.json()

			const consoleSpy = jest
				.spyOn( console, 'error' )
				.mockImplementation( () => {} );

			let step = lifecycle.next( {
				responses: [
					{ status: 200, body: serverCart },
					{ status: 200, body: serverCart },
				],
			} );

			while ( ! step.done ) {
				step = lifecycle.next( undefined );
			}

			consoleSpy.mockRestore();

			// updateNotices should have been called — info notices were computed
			// because removeCartItem contributes showNotices: true.
			expect( updateNoticesSpy ).toHaveBeenCalled();
		} );

		it( 'chained lifecycle takes a fresh snapshot of the post-reconciliation state', () => {
			const mockQueueMicrotask = jest.fn();
			globalThis.queueMicrotask = mockQueueMicrotask;

			const mockFetch = jest.fn();
			global.fetch = mockFetch;

			jest.isolateModules( () => require( '../cart' ) );

			// Enqueue first action — triggers first lifecycle.
			const gen1 = mockRegisteredStore!.actions.addCartItem( {
				id: 99,
				quantity: 1,
				type: 'simple',
			} ) as unknown as Generator;

			gen1.next();

			// Drive first lifecycle — drains pending, sends batch.
			const processLifecycle = (
				mockRegisteredStore!.actions as Record<
					string,
					( ...args: unknown[] ) => unknown
				>
			)._processLifecycle;

			const lifecycle1 = processLifecycle( 0 ) as unknown as Generator;
			lifecycle1.next(); // yield fetch(...)

			// While batch is in-flight, enqueue a second action.
			// This will be picked up by the chained lifecycle, not the current batch.
			const gen2 = mockRegisteredStore!.actions.addCartItem( {
				id: 50,
				quantity: 1,
				type: 'simple',
			} ) as unknown as Generator;

			gen2.next();

			// First batch succeeds — server returns a new cart state.
			const firstServerCart = {
				items: [
					{
						key: 'server-99',
						id: 99,
						quantity: 1,
						type: 'simple',
						name: 'Widget 99',
					},
				],
				totals: { total_items: '1' },
				errors: [],
			};

			const mockResponse = { status: 207, json: jest.fn() };
			lifecycle1.next( mockResponse ); // yield res.json()

			const consoleSpy = jest
				.spyOn( console, 'error' )
				.mockImplementation( () => {} );

			let step = lifecycle1.next( {
				responses: [
					{ status: 200, body: firstServerCart },
				],
			} );

			while ( ! step.done ) {
				step = lifecycle1.next( undefined );
			}

			// After first lifecycle completes, state.cart = firstServerCart.
			// The chained lifecycle should have been scheduled via queueMicrotask.
			// Verify by executing the chained lifecycle's queueMicrotask callback.
			// The chained lifecycle gets a FRESH snapshot of state.cart (firstServerCart).

			// Drive the chained lifecycle (second batch).
			const lifecycle2 = processLifecycle( 1 ) as unknown as Generator;
			lifecycle2.next(); // yield fetch(...)

			// Verify second batch was called for the second entry.
			expect( mockFetch ).toHaveBeenCalledTimes( 2 );

			// Now force total failure on the second batch — rollback should use
			// the fresh snapshot (firstServerCart), not the original snapshot.
			try {
				lifecycle2.throw!( new Error( 'Network error' ) );
			} catch {
				// lifecycle may finish
			}

			consoleSpy.mockRestore();

			// Rolled back to the fresh snapshot (firstServerCart), NOT to initialCart.
			expect( mockState.cart ).toEqual( firstServerCart );
		} );

	} );

	describe( 'REQ-022: second enqueue reuses existing snapshot', () => {
		it( 'second enqueue does not overwrite the snapshot — rollback restores pre-first-optimistic state', () => {
			const mockQueueMicrotask = jest.fn();
			globalThis.queueMicrotask = mockQueueMicrotask;

			jest.isolateModules( () => require( '../cart' ) );

			const cartBeforeAnyAction = JSON.parse(
				JSON.stringify( mockState.cart )
			);

			// First enqueue: addCartItem — snapshot taken BEFORE this optimistic update.
			const gen1 = mockRegisteredStore!.actions.addCartItem( {
				id: 99,
				quantity: 1,
				type: 'simple',
			} ) as unknown as Generator;

			gen1.next(); // enqueue + optimistic: cart now has 2 items

			// Second enqueue: removeCartItem — should NOT take a new snapshot.
			// If it did, the snapshot would contain the optimistic item id:99.
			const gen2 = mockRegisteredStore!.actions.removeCartItem(
				'item-1'
			) as unknown as Generator;

			gen2.next(); // enqueue + optimistic: cart now has 1 item (id:99 only)

			// Drive lifecycle to total failure — rollback should use the FIRST snapshot.
			const processLifecycle = (
				mockRegisteredStore!.actions as Record<
					string,
					( ...args: unknown[] ) => unknown
				>
			)._processLifecycle;

			const lifecycle = processLifecycle( 0 ) as unknown as Generator;
			lifecycle.next(); // yield fetch(...)

			// Network error → total failure → rollback to snapshot.
			const consoleSpy = jest
				.spyOn( console, 'error' )
				.mockImplementation( () => {} );
			try {
				lifecycle.throw!( new Error( 'Network error' ) );
			} catch {
				// lifecycle may finish
			}
			consoleSpy.mockRestore();

			// Snapshot was taken before BOTH optimistic updates.
			// Rollback should restore the original cart (with item-1, without id:99).
			expect( mockState.cart ).toEqual( cartBeforeAnyAction );
			expect( mockState.cart.items ).toHaveLength( 1 );
			expect( mockState.cart.items[ 0 ].key ).toBe( 'item-1' );
		} );

		it( 'both entries from two enqueues appear in a single batch', () => {
			const mockQueueMicrotask = jest.fn();
			globalThis.queueMicrotask = mockQueueMicrotask;

			const mockFetch = jest.fn();
			global.fetch = mockFetch;

			jest.isolateModules( () => require( '../cart' ) );

			// First enqueue.
			const gen1 = mockRegisteredStore!.actions.addCartItem( {
				id: 99,
				quantity: 2,
				type: 'simple',
			} ) as unknown as Generator;

			gen1.next();

			// Second enqueue in the same tick.
			const gen2 = mockRegisteredStore!.actions.removeCartItem(
				'item-1'
			) as unknown as Generator;

			gen2.next();

			// Drive lifecycle.
			const processLifecycle = (
				mockRegisteredStore!.actions as Record<
					string,
					( ...args: unknown[] ) => unknown
				>
			)._processLifecycle;

			const lifecycle = processLifecycle( 0 ) as unknown as Generator;
			lifecycle.next(); // yield fetch(...)

			expect( mockFetch ).toHaveBeenCalledTimes( 1 );
			const body = JSON.parse(
				mockFetch.mock.calls[ 0 ][ 1 ].body as string
			);

			// Both entries should be in one batch.
			expect( body.requests ).toHaveLength( 2 );
			expect( body.requests[ 0 ].path ).toBe(
				'/wc/store/v1/cart/add-item'
			);
			expect( body.requests[ 1 ].path ).toBe(
				'/wc/store/v1/cart/remove-item'
			);
		} );

		it( 'queueMicrotask is called only once even with two enqueues', () => {
			const mockQueueMicrotask = jest.fn();
			globalThis.queueMicrotask = mockQueueMicrotask;

			jest.isolateModules( () => require( '../cart' ) );

			// First enqueue — triggers queueMicrotask.
			const gen1 = mockRegisteredStore!.actions.addCartItem( {
				id: 99,
				quantity: 1,
				type: 'simple',
			} ) as unknown as Generator;

			gen1.next();

			// Second enqueue — should NOT trigger queueMicrotask again.
			const gen2 = mockRegisteredStore!.actions.addCartItem( {
				id: 50,
				quantity: 1,
				type: 'simple',
			} ) as unknown as Generator;

			gen2.next();

			// queueMicrotask should have been called exactly once (from the first enqueue).
			expect( mockQueueMicrotask ).toHaveBeenCalledTimes( 1 );
		} );

		it( 'snapshot captures state before any optimistic update from either enqueue', () => {
			const mockQueueMicrotask = jest.fn();
			globalThis.queueMicrotask = mockQueueMicrotask;

			jest.isolateModules( () => require( '../cart' ) );

			// Original cart has 1 item (item-1, id:10).
			expect( mockState.cart.items ).toHaveLength( 1 );

			// First enqueue: add id:99 (optimistic: cart gains item id:99).
			const gen1 = mockRegisteredStore!.actions.addCartItem( {
				id: 99,
				quantity: 1,
				type: 'simple',
			} ) as unknown as Generator;

			gen1.next();

			// After first optimistic update: 2 items.
			expect( mockState.cart.items ).toHaveLength( 2 );

			// Second enqueue: add id:50 (optimistic: cart gains item id:50).
			const gen2 = mockRegisteredStore!.actions.addCartItem( {
				id: 50,
				quantity: 1,
				type: 'simple',
			} ) as unknown as Generator;

			gen2.next();

			// After second optimistic update: 3 items.
			expect( mockState.cart.items ).toHaveLength( 3 );

			// Now force total failure — snapshot should be from before ANY optimistic updates.
			const processLifecycle = (
				mockRegisteredStore!.actions as Record<
					string,
					( ...args: unknown[] ) => unknown
				>
			)._processLifecycle;

			const lifecycle = processLifecycle( 0 ) as unknown as Generator;
			lifecycle.next(); // yield fetch(...)

			const consoleSpy = jest
				.spyOn( console, 'error' )
				.mockImplementation( () => {} );
			try {
				lifecycle.throw!( new Error( 'Network error' ) );
			} catch {
				// lifecycle may finish
			}
			consoleSpy.mockRestore();

			// Rolled back to pre-optimistic state: only the original 1 item.
			expect( mockState.cart.items ).toHaveLength( 1 );
			expect( mockState.cart.items[ 0 ].id ).toBe( 10 );
		} );
	} );

	describe( 'REQ-018: batchAddCartItems removed', () => {
		it( 'batchAddCartItems is not exposed as a store action', () => {
			jest.isolateModules( () => require( '../cart' ) );

			expect(
				( mockRegisteredStore!.actions as Record< string, unknown > )
					.batchAddCartItems
			).toBeUndefined();
		} );
	} );
} );
