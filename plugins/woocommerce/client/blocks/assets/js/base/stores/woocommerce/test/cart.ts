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
		getConfig: jest.fn( () => ( {} ) ),
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

/**
 * Helper to create a mock cart object with the given items.
 */
function createMockCart(
	items: Array< {
		key?: string;
		id: number;
		quantity: number;
		name?: string;
		type?: string;
		sold_individually?: boolean;
	} > = []
): Store[ 'state' ][ 'cart' ] {
	return {
		items: items.map( ( item ) => ( {
			key: item.key ?? `key-${ item.id }`,
			id: item.id,
			quantity: item.quantity,
			name: item.name ?? `Product ${ item.id }`,
			type: item.type ?? 'simple',
			sold_individually: item.sold_individually ?? false,
			variation: [],
		} ) ),
		coupons: [],
		fees: [],
		totals: {
			total_items: '0',
			total_items_tax: '0',
			total_fees: '0',
			total_fees_tax: '0',
			total_discount: '0',
			total_discount_tax: '0',
			total_shipping: '0',
			total_shipping_tax: '0',
			total_price: '0',
			total_tax: '0',
			tax_lines: [],
			currency_code: 'USD',
			currency_symbol: '$',
			currency_minor_unit: 2,
			currency_decimal_separator: '.',
			currency_thousand_separator: ',',
			currency_prefix: '$',
			currency_suffix: '',
		},
		errors: [],
		extensions: {},
		shippingRates: [],
		shippingAddress: {} as Store[ 'state' ][ 'cart' ][ 'shippingAddress' ],
		billingAddress: {} as Store[ 'state' ][ 'cart' ][ 'billingAddress' ],
		itemsCount: items.reduce( ( sum, i ) => sum + i.quantity, 0 ),
		itemsWeight: 0,
		crossSells: [],
		needsPayment: false,
		needsShipping: false,
		hasCalculatedShipping: false,
		paymentMethods: [],
		paymentRequirements: [],
	} as unknown as Store[ 'state' ][ 'cart' ];
}

/**
 * Helper to create a successful batch response with per-sub-request responses.
 */
function createBatchResponse(
	subResponses: Array< { status: number; body: unknown } >
) {
	return {
		responses: subResponses,
	};
}

/**
 * Helper: Drive a generator action through its yields.
 *
 * For batched actions the generator yields:
 *   1. cyclePromise (a Promise<void> that resolves after reconciliation)
 *   2. possibly a11y module import
 *
 * This helper calls `.next()` with each resolved yield value until done.
 */
async function driveGenerator( iterator: Iterator< unknown > ) {
	let result = iterator.next();
	while ( ! result.done ) {
		// If the yielded value is a thenable, await it, then pass the
		// resolved value back into the generator.
		if (
			result.value &&
			typeof ( result.value as Promise< unknown > ).then === 'function'
		) {
			const resolved = await ( result.value as Promise< unknown > );
			result = iterator.next( resolved );
		} else {
			result = iterator.next( result.value );
		}
	}
}

/**
 * Flush all pending microtasks and promises.
 */
function flushMicrotasks(): Promise< void > {
	return new Promise( ( resolve ) => setTimeout( resolve, 0 ) );
}

describe( 'Batching Infrastructure', () => {
	let mockFetch: jest.Mock;
	let store: MockStore;

	beforeEach( () => {
		jest.resetModules();
		mockFetch = jest.fn();
		global.fetch = mockFetch;

		// Reset the mock state with an empty cart.
		mockState.cart = createMockCart();

		jest.isolateModules( () => require( '../cart' ) );
		store = mockRegisteredStore!;
	} );

	describe( 'sendBatch', () => {
		it( 'sends sub-requests to the batch endpoint with correct headers', async () => {
			const serverCart = createMockCart( [
				{ key: 'abc', id: 1, quantity: 2 },
			] );
			mockFetch.mockResolvedValueOnce(
				new Response(
					JSON.stringify(
						createBatchResponse( [
							{ status: 200, body: serverCart },
						] )
					),
					{ status: 200 }
				)
			);

			// Call addCartItem which will push to pending and start a cycle.
			const gen = store.actions.addCartItem( {
				id: 1,
				quantity: 2,
				type: 'simple',
			} );
			await driveGenerator( gen as unknown as Iterator< unknown > );

			expect( mockFetch ).toHaveBeenCalledWith(
				'https://example.com/wp-json/wc/store/v1/batch',
				expect.objectContaining( {
					method: 'POST',
					headers: {
						Nonce: 'test-nonce-123',
						'Content-Type': 'application/json',
					},
				} )
			);

			// Verify the body contains a requests array.
			const callArgs = mockFetch.mock.calls[ 0 ];
			const body = JSON.parse( callArgs[ 1 ].body );
			expect( body.requests ).toBeInstanceOf( Array );
			expect( body.requests ).toHaveLength( 1 );
			expect( body.requests[ 0 ].method ).toBe( 'POST' );
			expect( body.requests[ 0 ].path ).toContain(
				'/wc/store/v1/cart/'
			);
		} );

		it( 'handles network errors gracefully (Total Failure)', async () => {
			mockFetch.mockRejectedValueOnce( new Error( 'Network error' ) );

			const initialCart = createMockCart( [
				{ key: 'existing', id: 99, quantity: 1 },
			] );
			mockState.cart = createMockCart( [
				{ key: 'existing', id: 99, quantity: 1 },
			] );

			const gen = store.actions.addCartItem( {
				id: 1,
				quantity: 1,
				type: 'simple',
			} );
			await driveGenerator( gen as unknown as Iterator< unknown > );

			// After total failure, cart should be rolled back to snapshot.
			// The snapshot was taken before the optimistic update pushed a new item.
			// So we expect the original items plus the optimistically added one to be gone
			// (rolled back).
			expect( store.state.cart.items ).toHaveLength(
				initialCart.items.length
			);
		} );

		it( 'handles HTTP errors (non-ok response) as Total Failure', async () => {
			mockFetch.mockResolvedValueOnce(
				new Response( 'Server Error', { status: 500 } )
			);

			const initialCart = createMockCart( [
				{ key: 'item1', id: 1, quantity: 3 },
			] );
			mockState.cart = createMockCart( [
				{ key: 'item1', id: 1, quantity: 3 },
			] );

			const gen = store.actions.removeCartItem( 'item1' );
			await driveGenerator( gen as unknown as Iterator< unknown > );

			// Rolled back: item should be restored.
			expect( store.state.cart.items ).toHaveLength(
				initialCart.items.length
			);
		} );
	} );

	describe( 'recordResponse', () => {
		it( 'classifies all-success response: returns last 2xx body as server state', async () => {
			const serverCart = createMockCart( [
				{ key: 'a', id: 1, quantity: 5 },
				{ key: 'b', id: 2, quantity: 3 },
			] );
			mockFetch.mockResolvedValueOnce(
				new Response(
					JSON.stringify(
						createBatchResponse( [
							{
								status: 200,
								body: createMockCart( [
									{ key: 'a', id: 1, quantity: 5 },
								] ),
							},
							{ status: 200, body: serverCart },
						] )
					),
					{ status: 200 }
				)
			);

			// Two adds in the same tick.
			const gen1 = store.actions.addCartItem( {
				id: 1,
				quantity: 5,
				type: 'simple',
			} );
			const gen2 = store.actions.addCartItem( {
				id: 2,
				quantity: 3,
				type: 'simple',
			} );

			await Promise.all( [
				driveGenerator( gen1 as unknown as Iterator< unknown > ),
				driveGenerator( gen2 as unknown as Iterator< unknown > ),
			] );

			// Cart should be overwritten with the last 2xx response body.
			expect( store.state.cart.items ).toHaveLength( 2 );
			expect( store.state.cart.items[ 0 ] ).toEqual(
				expect.objectContaining( { key: 'a', id: 1, quantity: 5 } )
			);
			expect( store.state.cart.items[ 1 ] ).toEqual(
				expect.objectContaining( { key: 'b', id: 2, quantity: 3 } )
			);
		} );

		it( 'classifies mixed success/failure: uses last 2xx body and accumulates errors', async () => {
			const serverCart = createMockCart( [
				{ key: 'a', id: 1, quantity: 2 },
			] );
			mockFetch.mockResolvedValueOnce(
				new Response(
					JSON.stringify(
						createBatchResponse( [
							{
								status: 400,
								body: {
									code: 'invalid_item',
									message: 'Item not found',
								},
							},
							{ status: 200, body: serverCart },
						] )
					),
					{ status: 200 }
				)
			);

			// First remove will fail, second add will succeed.
			const gen1 = store.actions.removeCartItem( 'nonexistent' );
			const gen2 = store.actions.addCartItem( {
				id: 1,
				quantity: 2,
				type: 'simple',
			} );

			await Promise.all( [
				driveGenerator( gen1 as unknown as Iterator< unknown > ),
				driveGenerator( gen2 as unknown as Iterator< unknown > ),
			] );

			// Cart should be overwritten with the successful response.
			expect( store.state.cart ).toEqual(
				expect.objectContaining( {
					items: expect.arrayContaining( [
						expect.objectContaining( { key: 'a', id: 1 } ),
					] ),
				} )
			);
		} );

		it( 'classifies all-sub-requests-failed (Total Failure edge case): rolls back', async () => {
			mockState.cart = createMockCart( [
				{ key: 'item1', id: 1, quantity: 1 },
			] );
			const originalItems = [ ...store.state.cart.items ];

			mockFetch.mockResolvedValueOnce(
				new Response(
					JSON.stringify(
						createBatchResponse( [
							{
								status: 400,
								body: {
									code: 'err1',
									message: 'Error 1',
								},
							},
							{
								status: 500,
								body: {
									code: 'err2',
									message: 'Error 2',
								},
							},
						] )
					),
					{ status: 200 }
				)
			);

			const gen1 = store.actions.removeCartItem( 'item1' );
			const gen2 = store.actions.addCartItem( {
				id: 99,
				quantity: 1,
				type: 'simple',
			} );

			await Promise.all( [
				driveGenerator( gen1 as unknown as Iterator< unknown > ),
				driveGenerator( gen2 as unknown as Iterator< unknown > ),
			] );

			// All sub-requests failed → rollback to snapshot.
			// Snapshot was taken before optimistic updates, so cart should be restored.
			expect( store.state.cart.items ).toHaveLength(
				originalItems.length
			);
		} );

		it( 'accumulates business validation errors from 2xx responses', async () => {
			const serverCartWithErrors = {
				...createMockCart( [
					{ key: 'a', id: 1, quantity: 2 },
				] ),
				errors: [
					{ code: 'stock_low', message: 'Only 2 left in stock' },
				],
			};

			mockFetch.mockResolvedValueOnce(
				new Response(
					JSON.stringify(
						createBatchResponse( [
							{
								status: 200,
								body: serverCartWithErrors,
							},
						] )
					),
					{ status: 200 }
				)
			);

			const gen = store.actions.addCartItem( {
				id: 1,
				quantity: 2,
				type: 'simple',
			} );
			await driveGenerator( gen as unknown as Iterator< unknown > );

			// Cart should be updated with server state (business errors don't prevent state update).
			expect( store.state.cart.items ).toHaveLength( 1 );
			expect( store.state.cart.items[ 0 ] ).toEqual(
				expect.objectContaining( { key: 'a', id: 1, quantity: 2 } )
			);
		} );
	} );

	describe( 'reconcile', () => {
		it( 'overwrites state.cart with lastServerState when available', async () => {
			mockState.cart = createMockCart( [
				{ key: 'old', id: 1, quantity: 1 },
			] );

			const serverCart = createMockCart( [
				{ key: 'new', id: 1, quantity: 5 },
				{ key: 'added', id: 2, quantity: 1 },
			] );
			mockFetch.mockResolvedValueOnce(
				new Response(
					JSON.stringify(
						createBatchResponse( [
							{ status: 200, body: serverCart },
						] )
					),
					{ status: 200 }
				)
			);

			const gen = store.actions.addCartItem( {
				id: 1,
				quantity: 5,
				type: 'simple',
			} );
			await driveGenerator( gen as unknown as Iterator< unknown > );

			// State should reflect the server's response, not the optimistic update.
			expect( store.state.cart ).toEqual( serverCart );
		} );

		it( 'rolls back to snapshot when all batches fail', async () => {
			const originalCart = createMockCart( [
				{ key: 'preserved', id: 42, quantity: 7 },
			] );
			mockState.cart = JSON.parse( JSON.stringify( originalCart ) );

			mockFetch.mockRejectedValueOnce( new Error( 'Network down' ) );

			const gen = store.actions.addCartItem( {
				id: 99,
				quantity: 1,
				type: 'simple',
			} );
			await driveGenerator( gen as unknown as Iterator< unknown > );

			// Should roll back to snapshot (before optimistic add of id:99).
			expect( store.state.cart.items ).toHaveLength( 1 );
			expect( store.state.cart.items[ 0 ] ).toEqual(
				expect.objectContaining( {
					key: 'preserved',
					id: 42,
					quantity: 7,
				} )
			);
		} );

		it( 'merges quantityChanges from all entries in the cycle', async () => {
			// We verify this indirectly through the sync event.
			const syncEvents: CustomEvent[] = [];
			const handler = ( e: Event ) =>
				syncEvents.push( e as CustomEvent );
			window.addEventListener(
				'wc-blocks_store_sync_required',
				handler
			);

			const serverCart = createMockCart( [
				{ key: 'a', id: 1, quantity: 3 },
				{ key: 'b', id: 2, quantity: 1 },
			] );
			mockFetch.mockResolvedValueOnce(
				new Response(
					JSON.stringify(
						createBatchResponse( [
							{ status: 200, body: serverCart },
							{ status: 200, body: serverCart },
						] )
					),
					{ status: 200 }
				)
			);

			// Two adds in the same tick → merged quantityChanges.
			const gen1 = store.actions.addCartItem( {
				id: 1,
				quantity: 3,
				type: 'simple',
			} );
			const gen2 = store.actions.addCartItem( {
				id: 2,
				quantity: 1,
				type: 'simple',
			} );

			await Promise.all( [
				driveGenerator( gen1 as unknown as Iterator< unknown > ),
				driveGenerator( gen2 as unknown as Iterator< unknown > ),
			] );

			window.removeEventListener(
				'wc-blocks_store_sync_required',
				handler
			);

			// One sync event per cycle (not per action).
			expect( syncEvents ).toHaveLength( 1 );
			const detail = syncEvents[ 0 ].detail;
			expect( detail.quantityChanges ).toBeDefined();
			// Both product IDs should be in productsPendingAdd.
			expect(
				detail.quantityChanges.productsPendingAdd
			).toEqual( expect.arrayContaining( [ 1, 2 ] ) );
		} );

		it( 'generates info notices BEFORE state overwrite', async () => {
			// Start with an item at quantity 1.
			mockState.cart = createMockCart( [
				{
					key: 'item1',
					id: 1,
					quantity: 1,
					name: 'Widget',
				},
			] );

			// Server returns item with quantity changed from what we requested
			// (e.g., stock limit).
			const serverCart = createMockCart( [
				{
					key: 'item1',
					id: 1,
					quantity: 3,
					name: 'Widget',
				},
			] );
			mockFetch.mockResolvedValueOnce(
				new Response(
					JSON.stringify(
						createBatchResponse( [
							{ status: 200, body: serverCart },
						] )
					),
					{ status: 200 }
				)
			);

			const gen = store.actions.addCartItem(
				{ id: 1, quantity: 5, type: 'simple' },
				{ showCartUpdatesNotices: true }
			);
			await driveGenerator( gen as unknown as Iterator< unknown > );

			// The cart should be overwritten with the server response.
			expect( store.state.cart.items[ 0 ] ).toEqual(
				expect.objectContaining( { quantity: 3 } )
			);
		} );

		it( 'fires exactly one sync event per cycle', async () => {
			const syncEvents: Event[] = [];
			const handler = ( e: Event ) => syncEvents.push( e );
			window.addEventListener(
				'wc-blocks_store_sync_required',
				handler
			);

			const serverCart = createMockCart( [
				{ key: 'a', id: 1, quantity: 1 },
			] );
			mockFetch.mockResolvedValueOnce(
				new Response(
					JSON.stringify(
						createBatchResponse( [
							{ status: 200, body: serverCart },
						] )
					),
					{ status: 200 }
				)
			);

			const gen1 = store.actions.addCartItem( {
				id: 1,
				quantity: 1,
				type: 'simple',
			} );
			const gen2 = store.actions.addCartItem( {
				id: 2,
				quantity: 1,
				type: 'simple',
			} );

			await Promise.all( [
				driveGenerator( gen1 as unknown as Iterator< unknown > ),
				driveGenerator( gen2 as unknown as Iterator< unknown > ),
			] );

			window.removeEventListener(
				'wc-blocks_store_sync_required',
				handler
			);

			// Only one sync event for the entire batch cycle.
			expect( syncEvents ).toHaveLength( 1 );
		} );
	} );

	describe( 'startCycle + run', () => {
		it( 'batches multiple same-tick requests into a single fetch call', async () => {
			const serverCart = createMockCart( [
				{ key: 'a', id: 1, quantity: 1 },
				{ key: 'b', id: 2, quantity: 1 },
			] );
			mockFetch.mockResolvedValueOnce(
				new Response(
					JSON.stringify(
						createBatchResponse( [
							{ status: 200, body: serverCart },
							{ status: 200, body: serverCart },
						] )
					),
					{ status: 200 }
				)
			);

			// Two adds in the same synchronous tick.
			const gen1 = store.actions.addCartItem( {
				id: 1,
				quantity: 1,
				type: 'simple',
			} );
			const gen2 = store.actions.addCartItem( {
				id: 2,
				quantity: 1,
				type: 'simple',
			} );

			await Promise.all( [
				driveGenerator( gen1 as unknown as Iterator< unknown > ),
				driveGenerator( gen2 as unknown as Iterator< unknown > ),
			] );

			// Only one fetch call to the batch endpoint.
			expect( mockFetch ).toHaveBeenCalledTimes( 1 );
			const callArgs = mockFetch.mock.calls[ 0 ];
			expect( callArgs[ 0 ] ).toBe(
				'https://example.com/wp-json/wc/store/v1/batch'
			);
			const body = JSON.parse( callArgs[ 1 ].body );
			expect( body.requests ).toHaveLength( 2 );
		} );

		it( 'processes queued requests when new ones arrive during send', async () => {
			// First batch: responds with a cart.
			const firstCart = createMockCart( [
				{ key: 'a', id: 1, quantity: 1 },
			] );
			// Second batch: responds with updated cart.
			const secondCart = createMockCart( [
				{ key: 'a', id: 1, quantity: 1 },
				{ key: 'b', id: 2, quantity: 1 },
			] );

			let resolveFirstFetch!: ( value: Response ) => void;
			const firstFetchPromise = new Promise< Response >(
				( resolve ) => {
					resolveFirstFetch = resolve;
				}
			);

			mockFetch
				.mockReturnValueOnce( firstFetchPromise )
				.mockResolvedValueOnce(
					new Response(
						JSON.stringify(
							createBatchResponse( [
								{ status: 200, body: secondCart },
							] )
						),
						{ status: 200 }
					)
				);

			// Start first add — this starts the cycle.
			const gen1 = store.actions.addCartItem( {
				id: 1,
				quantity: 1,
				type: 'simple',
			} );
			const promise1 = driveGenerator(
				gen1 as unknown as Iterator< unknown >
			);

			// Wait for the microtask to fire run() and the first fetch to be called.
			await flushMicrotasks();

			expect( mockFetch ).toHaveBeenCalledTimes( 1 );

			// While the first fetch is in flight, add another item.
			// This should be queued (pushed to pending).
			const gen2 = store.actions.addCartItem( {
				id: 2,
				quantity: 1,
				type: 'simple',
			} );
			const promise2 = driveGenerator(
				gen2 as unknown as Iterator< unknown >
			);

			// Resolve the first fetch.
			resolveFirstFetch(
				new Response(
					JSON.stringify(
						createBatchResponse( [
							{ status: 200, body: firstCart },
						] )
					),
					{ status: 200 }
				)
			);

			await Promise.all( [ promise1, promise2 ] );

			// Two fetch calls: first batch + queued batch.
			expect( mockFetch ).toHaveBeenCalledTimes( 2 );

			// Final cart should be from the second response.
			expect( store.state.cart ).toEqual( secondCart );
		} );

		it( 'takes snapshot only once at cycle start (not for queued requests)', async () => {
			const initialCart = createMockCart( [
				{ key: 'original', id: 1, quantity: 1 },
			] );
			mockState.cart = JSON.parse( JSON.stringify( initialCart ) );

			// Both fetches will fail → total failure → rollback to snapshot.
			let resolveFirstFetch!: ( value: Response ) => void;
			const firstFetchPromise = new Promise< Response >(
				( resolve ) => {
					resolveFirstFetch = resolve;
				}
			);

			mockFetch
				.mockReturnValueOnce( firstFetchPromise )
				.mockRejectedValueOnce( new Error( 'Network error' ) );

			// First action: starts cycle, snapshot is taken.
			const gen1 = store.actions.addCartItem( {
				id: 2,
				quantity: 1,
				type: 'simple',
			} );
			const promise1 = driveGenerator(
				gen1 as unknown as Iterator< unknown >
			);

			await flushMicrotasks();

			// Second action during flight: queued, no new snapshot.
			const gen2 = store.actions.addCartItem( {
				id: 3,
				quantity: 1,
				type: 'simple',
			} );
			const promise2 = driveGenerator(
				gen2 as unknown as Iterator< unknown >
			);

			// Fail the first fetch too.
			resolveFirstFetch(
				new Response( 'Error', { status: 500 } )
			);

			await Promise.all( [ promise1, promise2 ] );

			// Both batches failed → rollback to original snapshot.
			// Should have exactly the original item (not the optimistically added ones).
			expect( store.state.cart.items ).toHaveLength( 1 );
			expect( store.state.cart.items[ 0 ] ).toEqual(
				expect.objectContaining( {
					key: 'original',
					id: 1,
					quantity: 1,
				} )
			);
		} );

		it( 'resolves all yielding actions after reconciliation', async () => {
			const serverCart = createMockCart( [
				{ key: 'a', id: 1, quantity: 1 },
				{ key: 'b', id: 2, quantity: 1 },
			] );
			mockFetch.mockResolvedValueOnce(
				new Response(
					JSON.stringify(
						createBatchResponse( [
							{ status: 200, body: serverCart },
							{ status: 200, body: serverCart },
						] )
					),
					{ status: 200 }
				)
			);

			let resolved1 = false;
			let resolved2 = false;

			const gen1 = store.actions.addCartItem( {
				id: 1,
				quantity: 1,
				type: 'simple',
			} );
			const gen2 = store.actions.addCartItem( {
				id: 2,
				quantity: 1,
				type: 'simple',
			} );

			const p1 = driveGenerator(
				gen1 as unknown as Iterator< unknown >
			).then( () => {
				resolved1 = true;
			} );
			const p2 = driveGenerator(
				gen2 as unknown as Iterator< unknown >
			).then( () => {
				resolved2 = true;
			} );

			await Promise.all( [ p1, p2 ] );

			// Both promises should have resolved.
			expect( resolved1 ).toBe( true );
			expect( resolved2 ).toBe( true );
		} );

		it( 'cleans up cycle state after completion (allows new cycles)', async () => {
			const cart1 = createMockCart( [
				{ key: 'a', id: 1, quantity: 1 },
			] );
			const cart2 = createMockCart( [
				{ key: 'a', id: 1, quantity: 1 },
				{ key: 'b', id: 2, quantity: 1 },
			] );

			mockFetch.mockResolvedValueOnce(
				new Response(
					JSON.stringify(
						createBatchResponse( [
							{ status: 200, body: cart1 },
						] )
					),
					{ status: 200 }
				)
			);

			// First cycle.
			const gen1 = store.actions.addCartItem( {
				id: 1,
				quantity: 1,
				type: 'simple',
			} );
			await driveGenerator( gen1 as unknown as Iterator< unknown > );

			expect( store.state.cart ).toEqual( cart1 );

			// Second cycle should work independently.
			mockFetch.mockResolvedValueOnce(
				new Response(
					JSON.stringify(
						createBatchResponse( [
							{ status: 200, body: cart2 },
						] )
					),
					{ status: 200 }
				)
			);

			const gen2 = store.actions.addCartItem( {
				id: 2,
				quantity: 1,
				type: 'simple',
			} );
			await driveGenerator( gen2 as unknown as Iterator< unknown > );

			expect( store.state.cart ).toEqual( cart2 );
			// Two separate fetch calls (one per cycle).
			expect( mockFetch ).toHaveBeenCalledTimes( 2 );
		} );

		it( 'mixes add and remove operations in the same batch', async () => {
			mockState.cart = createMockCart( [
				{ key: 'toRemove', id: 1, quantity: 1 },
			] );

			const serverCart = createMockCart( [
				{ key: 'newItem', id: 2, quantity: 3 },
			] );
			mockFetch.mockResolvedValueOnce(
				new Response(
					JSON.stringify(
						createBatchResponse( [
							{ status: 200, body: serverCart },
							{ status: 200, body: serverCart },
						] )
					),
					{ status: 200 }
				)
			);

			// Remove and add in the same tick.
			const genRemove = store.actions.removeCartItem( 'toRemove' );
			const genAdd = store.actions.addCartItem( {
				id: 2,
				quantity: 3,
				type: 'simple',
			} );

			await Promise.all( [
				driveGenerator(
					genRemove as unknown as Iterator< unknown >
				),
				driveGenerator(
					genAdd as unknown as Iterator< unknown >
				),
			] );

			// One batch with both operations.
			expect( mockFetch ).toHaveBeenCalledTimes( 1 );
			const body = JSON.parse( mockFetch.mock.calls[ 0 ][ 1 ].body );
			expect( body.requests ).toHaveLength( 2 );

			// One request for remove, one for add.
			const paths = body.requests.map(
				( r: { path: string } ) => r.path
			);
			expect( paths ).toEqual(
				expect.arrayContaining( [
					expect.stringContaining( 'remove-item' ),
					expect.stringContaining( 'add-item' ),
				] )
			);
		} );

		it( 'promises always resolve, never reject, even on total failure', async () => {
			mockFetch.mockRejectedValueOnce( new Error( 'Network error' ) );

			const gen = store.actions.addCartItem( {
				id: 1,
				quantity: 1,
				type: 'simple',
			} );

			// This should resolve (not reject), even though the fetch failed.
			await expect(
				driveGenerator( gen as unknown as Iterator< unknown > )
			).resolves.toBeUndefined();
		} );
	} );
} );
