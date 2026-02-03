/**
 * Integration tests for the Cart Command Queue.
 *
 * These tests verify the full integration of:
 * - Cart commands (add, remove, update)
 * - Command queue (batching, execution, reconciliation)
 * - Cart state handler (state management, notices)
 *
 * Unlike unit tests, these test the complete flow from action to state change.
 */

/**
 * Internal dependencies
 */
import { createCommandQueue } from '../create-command-queue';
import { createCartStateHandler, type QuantityChanges } from '../cart-state-handler';
import {
	createAddItemCommand,
	createRemoveItemCommand,
	createBatchAddItemCommands,
	type CartState,
} from '../cart-commands';

/**
 * Create an empty cart state for testing.
 */
function createEmptyCartState(): CartState {
	return {
		items: [],
		coupons: [],
		shippingRates: [],
		shippingAddress: {
			first_name: '',
			last_name: '',
			company: '',
			address_1: '',
			address_2: '',
			city: '',
			state: '',
			postcode: '',
			country: '',
			phone: '',
		},
		billingAddress: {
			first_name: '',
			last_name: '',
			company: '',
			address_1: '',
			address_2: '',
			city: '',
			state: '',
			postcode: '',
			country: '',
			phone: '',
			email: '',
		},
		itemsCount: 0,
		itemsWeight: 0,
		crossSells: [],
		needsPayment: true,
		needsShipping: true,
		hasCalculatedShipping: false,
		fees: [],
		totals: {
			currency_code: 'USD',
			currency_symbol: '$',
			currency_minor_unit: 2,
			currency_decimal_separator: '.',
			currency_thousand_separator: ',',
			currency_prefix: '$',
			currency_suffix: '',
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
		},
		errors: [],
		paymentMethods: [],
		paymentRequirements: [],
		extensions: {},
	};
}

/**
 * Create a full cart item as returned by the server.
 */
function createServerCartItem( overrides = {} ) {
	return {
		key: `item-${ Math.random().toString( 36 ).slice( 2, 9 ) }`,
		id: 1,
		type: 'simple',
		quantity: 1,
		catalog_visibility: 'visible' as const,
		quantity_limits: {
			minimum: 1,
			maximum: 99,
			multiple_of: 1,
			editable: true,
		},
		name: 'Test Product',
		summary: 'Test summary',
		short_description: 'Short desc',
		description: 'Long desc',
		sku: 'TEST-001',
		low_stock_remaining: null,
		backorders_allowed: false,
		show_backorder_badge: false,
		sold_individually: false,
		permalink: 'https://example.com/product/test',
		images: [],
		variation: [],
		prices: {
			currency_code: 'USD',
			currency_symbol: '$',
			currency_minor_unit: 2,
			currency_decimal_separator: '.',
			currency_thousand_separator: ',',
			currency_prefix: '$',
			currency_suffix: '',
			price: '1000',
			regular_price: '1000',
			sale_price: '1000',
			price_range: null,
			raw_prices: {
				precision: 6,
				price: '1000000',
				regular_price: '1000000',
				sale_price: '1000000',
			},
		},
		totals: {
			currency_code: 'USD',
			currency_symbol: '$',
			currency_minor_unit: 2,
			currency_decimal_separator: '.',
			currency_thousand_separator: ',',
			currency_prefix: '$',
			currency_suffix: '',
			line_subtotal: '1000',
			line_subtotal_tax: '0',
			line_total: '1000',
			line_total_tax: '0',
		},
		extensions: {},
		item_data: [],
		...overrides,
	};
}

/**
 * Create a complete cart state with items (as returned by server).
 */
function createCartStateWithItems( items: ReturnType< typeof createServerCartItem >[] ): CartState {
	const base = createEmptyCartState();
	return {
		...base,
		items,
		itemsCount: items.reduce( ( sum, item ) => sum + item.quantity, 0 ),
	};
}

/**
 * Wait for microtasks to complete.
 */
function flushMicrotasks(): Promise< void > {
	return new Promise( ( resolve ) => queueMicrotask( resolve ) );
}

/**
 * Wait for multiple microtasks.
 */
async function flushAllMicrotasks( count = 5 ): Promise< void > {
	for ( let i = 0; i < count; i++ ) {
		await flushMicrotasks();
	}
}

describe( 'Cart Command Queue Integration', () => {
	const TEST_NONCE = 'test-nonce-123';
	// Note: no trailing slash - the command paths have a leading slash
	const TEST_REST_URL = 'https://example.com/wp-json';

	/**
	 * Helper to create a queue with mocked dependencies.
	 */
	function createTestQueue( options: {
		initialCart?: CartState;
		mockFetch?: jest.Mock;
		onCartUpdated?: ( changes: QuantityChanges ) => void;
		showErrorNotice?: jest.Mock;
		updateNotices?: jest.Mock;
	} = {} ) {
		const {
			initialCart = createEmptyCartState(),
			mockFetch = jest.fn(),
			onCartUpdated = jest.fn(),
			showErrorNotice = jest.fn(),
			updateNotices = jest.fn(),
		} = options;

		// Mutable cart state
		let cartState = initialCart;

		const stateHandler = createCartStateHandler( {
			getCartState: () => cartState,
			setCartState: ( cart ) => {
				cartState = cart;
			},
			onCartUpdated,
			showErrorNotice,
			updateNotices,
		} );

		const queue = createCommandQueue( stateHandler, {
			restUrl: TEST_REST_URL,
			nonce: TEST_NONCE,
			fetch: mockFetch,
		} );

		return {
			queue,
			getCartState: () => cartState,
			setCartState: ( cart: CartState ) => {
				cartState = cart;
			},
			mocks: {
				fetch: mockFetch,
				onCartUpdated,
				showErrorNotice,
				updateNotices,
			},
		};
	}

	/**
	 * Create a mock fetch that returns successful responses.
	 */
	function createSuccessFetch( responses: CartState[] ): jest.Mock {
		let callIndex = 0;
		return jest.fn( () => {
			const response = responses[ callIndex ] ?? responses[ responses.length - 1 ];
			callIndex++;
			return Promise.resolve( {
				ok: true,
				status: 200,
				json: () => Promise.resolve( response ),
			} as Response );
		} );
	}

	/**
	 * Create a mock fetch that returns error responses.
	 */
	function createErrorFetch( error: { code: string; message: string } ): jest.Mock {
		return jest.fn( () =>
			Promise.resolve( {
				ok: false,
				status: 400,
				json: () => Promise.resolve( error ),
			} as Response )
		);
	}

	describe( 'single cart operation', () => {
		it( 'should add item to empty cart with optimistic update', async () => {
			const serverResponse = createCartStateWithItems( [
				createServerCartItem( { id: 42, key: 'server-key-42', quantity: 2 } ),
			] );
			const mockFetch = createSuccessFetch( [ serverResponse ] );
			const { queue, getCartState } = createTestQueue( { mockFetch } );

			const command = createAddItemCommand(
				{ id: 42, quantity: 2, type: 'simple' },
				TEST_NONCE
			);
			const { promise } = queue.enqueue( command );

			// Optimistic update should be applied immediately
			expect( getCartState().items ).toHaveLength( 1 );
			expect( getCartState().items[ 0 ] ).toMatchObject( {
				id: 42,
				quantity: 2,
			} );

			// Wait for server response and reconciliation
			const result = await promise;

			expect( result.success ).toBe( true );
			// After reconciliation, state should have server's key
			expect( getCartState().items[ 0 ].key ).toBe( 'server-key-42' );
		} );

		it( 'should remove item from cart', async () => {
			const initialCart = createCartStateWithItems( [
				createServerCartItem( { key: 'item-to-remove', id: 1 } ),
				createServerCartItem( { key: 'item-to-keep', id: 2 } ),
			] );
			const serverResponse = createCartStateWithItems( [
				createServerCartItem( { key: 'item-to-keep', id: 2 } ),
			] );
			const mockFetch = createSuccessFetch( [ serverResponse ] );
			const { queue, getCartState } = createTestQueue( {
				initialCart,
				mockFetch,
			} );

			const command = createRemoveItemCommand( { key: 'item-to-remove' }, TEST_NONCE );
			const { promise } = queue.enqueue( command );

			// Optimistic update: item should be removed immediately
			expect( getCartState().items ).toHaveLength( 1 );
			expect( getCartState().items[ 0 ].key ).toBe( 'item-to-keep' );

			await promise;

			// After reconciliation, state should match server
			expect( getCartState().items ).toHaveLength( 1 );
		} );

		it( 'should update existing item quantity', async () => {
			const initialCart = createCartStateWithItems( [
				createServerCartItem( { key: 'item-key', id: 1, quantity: 2 } ),
			] );
			const serverResponse = createCartStateWithItems( [
				createServerCartItem( { key: 'item-key', id: 1, quantity: 5 } ),
			] );
			const mockFetch = createSuccessFetch( [ serverResponse ] );
			const { queue, getCartState } = createTestQueue( {
				initialCart,
				mockFetch,
			} );

			// Adding an item that already exists should update quantity
			const command = createAddItemCommand(
				{ id: 1, quantity: 5 },
				TEST_NONCE
			);
			const { promise } = queue.enqueue( command );

			// Optimistic update
			expect( getCartState().items[ 0 ].quantity ).toBe( 5 );

			await promise;

			// After reconciliation
			expect( getCartState().items[ 0 ].quantity ).toBe( 5 );
		} );
	} );

	describe( 'batched cart operations', () => {
		it( 'should batch multiple synchronous add operations', async () => {
			const serverResponse = createCartStateWithItems( [
				createServerCartItem( { key: 'key-1', id: 1, quantity: 2 } ),
				createServerCartItem( { key: 'key-2', id: 2, quantity: 1 } ),
				createServerCartItem( { key: 'key-3', id: 3, quantity: 3 } ),
			] );
			const mockFetch = createSuccessFetch( [
				serverResponse,
				serverResponse,
				serverResponse,
			] );
			const { queue, getCartState } = createTestQueue( { mockFetch } );

			// Enqueue multiple commands synchronously
			const command1 = createAddItemCommand( { id: 1, quantity: 2, type: 'simple' }, TEST_NONCE );
			const command2 = createAddItemCommand( { id: 2, quantity: 1, type: 'simple' }, TEST_NONCE );
			const command3 = createAddItemCommand( { id: 3, quantity: 3, type: 'simple' }, TEST_NONCE );

			const { promise: p1 } = queue.enqueue( command1 );
			const { promise: p2 } = queue.enqueue( command2 );
			const { promise: p3 } = queue.enqueue( command3 );

			// All optimistic updates should be applied
			expect( getCartState().items ).toHaveLength( 3 );

			// All should be batched in the same execution
			await Promise.all( [ p1, p2, p3 ] );

			// All 3 commands should have been sent (sequentially, not batched into single request)
			// because the queue executes each command separately
			expect( mockFetch ).toHaveBeenCalledTimes( 3 );
		} );

		it( 'should batch add and remove in same tick', async () => {
			const initialCart = createCartStateWithItems( [
				createServerCartItem( { key: 'existing-item', id: 99, quantity: 1 } ),
			] );
			const serverResponse = createCartStateWithItems( [
				createServerCartItem( { key: 'new-item', id: 1, quantity: 2 } ),
			] );
			const mockFetch = createSuccessFetch( [ serverResponse, serverResponse ] );
			const { queue, getCartState } = createTestQueue( {
				initialCart,
				mockFetch,
			} );

			// Add new item and remove existing item in same tick
			const addCommand = createAddItemCommand( { id: 1, quantity: 2, type: 'simple' }, TEST_NONCE );
			const removeCommand = createRemoveItemCommand( { key: 'existing-item' }, TEST_NONCE );

			const { promise: addPromise } = queue.enqueue( addCommand );
			const { promise: removePromise } = queue.enqueue( removeCommand );

			// Optimistic updates: should have new item, not old item
			expect( getCartState().items ).toHaveLength( 1 );
			expect( getCartState().items[ 0 ].id ).toBe( 1 );

			await Promise.all( [ addPromise, removePromise ] );

			// After reconciliation
			expect( getCartState().items ).toHaveLength( 1 );
			expect( getCartState().items[ 0 ].key ).toBe( 'new-item' );
		} );

		it( 'should use batchAddCartItems for multiple items', async () => {
			const serverResponse = createCartStateWithItems( [
				createServerCartItem( { key: 'key-1', id: 1, quantity: 1 } ),
				createServerCartItem( { key: 'key-2', id: 2, quantity: 2 } ),
			] );
			const mockFetch = createSuccessFetch( [ serverResponse, serverResponse ] );
			const { queue, getCartState } = createTestQueue( { mockFetch } );

			const commands = createBatchAddItemCommands(
				[
					{ id: 1, quantity: 1, type: 'simple' },
					{ id: 2, quantity: 2, type: 'simple' },
				],
				TEST_NONCE
			);

			const promises = commands.map( ( cmd ) => queue.enqueue( cmd ).promise );

			// All optimistic updates applied
			expect( getCartState().items ).toHaveLength( 2 );

			await Promise.all( promises );

			expect( mockFetch ).toHaveBeenCalledTimes( 2 );
		} );
	} );

	describe( 'error handling and rollback', () => {
		it( 'should rollback on API error', async () => {
			const initialCart = createCartStateWithItems( [
				createServerCartItem( { key: 'original-item', id: 99, quantity: 5 } ),
			] );
			const mockFetch = createErrorFetch( {
				code: 'out_of_stock',
				message: 'Product is out of stock',
			} );
			const showErrorNotice = jest.fn();
			const { queue, getCartState } = createTestQueue( {
				initialCart,
				mockFetch,
				showErrorNotice,
			} );

			const command = createAddItemCommand( { id: 1, quantity: 2, type: 'simple' }, TEST_NONCE );
			const { promise } = queue.enqueue( command );

			// Optimistic update applied
			expect( getCartState().items ).toHaveLength( 2 );

			const result = await promise;

			expect( result.success ).toBe( false );

			// Should rollback to initial state
			expect( getCartState().items ).toHaveLength( 1 );
			expect( getCartState().items[ 0 ].key ).toBe( 'original-item' );

			// Error notice should be shown
			expect( showErrorNotice ).toHaveBeenCalledWith(
				expect.objectContaining( { message: 'Product is out of stock' } ),
				undefined
			);
		} );

		it( 'should rollback all optimistic updates on batch failure', async () => {
			const initialCart = createEmptyCartState();
			// First request succeeds, second fails
			let callCount = 0;
			const mockFetch = jest.fn( () => {
				callCount++;
				if ( callCount === 1 ) {
					return Promise.resolve( {
						ok: true,
						status: 200,
						json: () =>
							Promise.resolve(
								createCartStateWithItems( [
									createServerCartItem( { key: 'key-1', id: 1, quantity: 1 } ),
								] )
							),
					} as Response );
				}
				return Promise.resolve( {
					ok: false,
					status: 400,
					json: () =>
						Promise.resolve( {
							code: 'error',
							message: 'Second item failed',
						} ),
				} as Response );
			} );

			const { queue, getCartState } = createTestQueue( {
				initialCart,
				mockFetch,
			} );

			const cmd1 = createAddItemCommand( { id: 1, quantity: 1, type: 'simple' }, TEST_NONCE );
			const cmd2 = createAddItemCommand( { id: 2, quantity: 1, type: 'simple' }, TEST_NONCE );

			const { promise: p1 } = queue.enqueue( cmd1 );
			const { promise: p2 } = queue.enqueue( cmd2 );

			// Optimistic: both items added
			expect( getCartState().items ).toHaveLength( 2 );

			await Promise.all( [ p1, p2 ] );

			// The last successful server state should be applied
			// Since second command failed, we get the state from first command's response
			expect( getCartState().items ).toHaveLength( 1 );
			expect( getCartState().items[ 0 ].id ).toBe( 1 );
		} );
	} );

	describe( 'single-flight constraint', () => {
		it( 'should queue commands while batch is executing', async () => {
			let resolveFirst: ( value: Response ) => void;
			const firstRequest = new Promise< Response >( ( resolve ) => {
				resolveFirst = resolve;
			} );

			const serverResponse1 = createCartStateWithItems( [
				createServerCartItem( { key: 'key-1', id: 1, quantity: 1 } ),
			] );
			const serverResponse2 = createCartStateWithItems( [
				createServerCartItem( { key: 'key-1', id: 1, quantity: 1 } ),
				createServerCartItem( { key: 'key-2', id: 2, quantity: 1 } ),
			] );

			let fetchCallCount = 0;
			const mockFetch = jest.fn( () => {
				fetchCallCount++;
				if ( fetchCallCount === 1 ) {
					return firstRequest;
				}
				return Promise.resolve( {
					ok: true,
					status: 200,
					json: () => Promise.resolve( serverResponse2 ),
				} as Response );
			} );

			const { queue, getCartState } = createTestQueue( { mockFetch } );

			// First command
			const cmd1 = createAddItemCommand( { id: 1, quantity: 1, type: 'simple' }, TEST_NONCE );
			const { promise: p1 } = queue.enqueue( cmd1 );

			// Start execution
			await flushMicrotasks();

			// While first is executing, enqueue another
			const cmd2 = createAddItemCommand( { id: 2, quantity: 1, type: 'simple' }, TEST_NONCE );
			const { promise: p2 } = queue.enqueue( cmd2 );

			// Both optimistic updates applied
			expect( getCartState().items ).toHaveLength( 2 );

			// Only first request sent so far
			expect( fetchCallCount ).toBe( 1 );

			// Resolve first request
			resolveFirst!( {
				ok: true,
				status: 200,
				json: () => Promise.resolve( serverResponse1 ),
			} as Response );

			await p1;

			// Now second should execute
			await flushAllMicrotasks();
			await p2;

			expect( fetchCallCount ).toBe( 2 );
		} );
	} );

	describe( 'callbacks and events', () => {
		it( 'should call onCartUpdated after successful batch', async () => {
			const serverResponse = createCartStateWithItems( [
				createServerCartItem( { key: 'key-1', id: 1, quantity: 2 } ),
			] );
			const mockFetch = createSuccessFetch( [ serverResponse ] );
			const onCartUpdated = jest.fn();
			const { queue } = createTestQueue( {
				mockFetch,
				onCartUpdated,
			} );

			const command = createAddItemCommand( { id: 1, quantity: 2, type: 'simple' }, TEST_NONCE );
			await queue.enqueue( command ).promise;

			expect( onCartUpdated ).toHaveBeenCalled();
		} );

		it( 'should emit queue events in correct order', async () => {
			const serverResponse = createCartStateWithItems( [
				createServerCartItem( { key: 'key-1', id: 1, quantity: 1 } ),
			] );
			const mockFetch = createSuccessFetch( [ serverResponse ] );
			const { queue } = createTestQueue( { mockFetch } );

			const events: string[] = [];
			queue.on( 'collecting', () => events.push( 'collecting' ) );
			queue.on( 'executing', () => events.push( 'executing' ) );
			queue.on( 'reconciled', () => events.push( 'reconciled' ) );
			queue.on( 'idle', () => events.push( 'idle' ) );

			const command = createAddItemCommand( { id: 1, quantity: 1, type: 'simple' }, TEST_NONCE );
			await queue.enqueue( command ).promise;

			expect( events ).toEqual( [ 'collecting', 'executing', 'reconciled', 'idle' ] );
		} );
	} );

	describe( 'flush behavior', () => {
		it( 'should immediately execute pending commands on flush', async () => {
			const serverResponse = createCartStateWithItems( [
				createServerCartItem( { key: 'key-1', id: 1, quantity: 1 } ),
			] );
			const mockFetch = createSuccessFetch( [ serverResponse ] );
			const { queue, getCartState } = createTestQueue( { mockFetch } );

			const command = createAddItemCommand( { id: 1, quantity: 1, type: 'simple' }, TEST_NONCE );
			queue.enqueue( command );

			// Flush immediately without waiting for microtask
			const result = await queue.flush();

			expect( result ).not.toBeNull();
			expect( getCartState().items ).toHaveLength( 1 );
			expect( mockFetch ).toHaveBeenCalledTimes( 1 );
		} );
	} );

	describe( 'cancellation', () => {
		it( 'should cancel pending command before execution', async () => {
			const mockFetch = jest.fn();
			const { queue, getCartState } = createTestQueue( { mockFetch } );

			const command = createAddItemCommand( { id: 1, quantity: 1, type: 'simple' }, TEST_NONCE );
			const { cancel, promise } = queue.enqueue( command );

			// Optimistic update applied
			expect( getCartState().items ).toHaveLength( 1 );

			// Cancel before execution
			const cancelled = cancel();
			expect( cancelled ).toBe( true );

			await expect( promise ).rejects.toThrow( 'Command cancelled' );

			// Fetch should not have been called
			await flushAllMicrotasks();
			expect( mockFetch ).not.toHaveBeenCalled();
		} );

		it( 'should abort in-flight requests on queue abort', async () => {
			let wasAborted = false;
			const mockFetch = jest.fn( ( _url: RequestInfo | URL, init?: RequestInit ) =>
				new Promise< Response >( ( _, reject ) => {
					if ( init?.signal ) {
						init.signal.addEventListener( 'abort', () => {
							wasAborted = true;
							reject( new DOMException( 'Aborted', 'AbortError' ) );
						} );
					}
				} )
			);

			const { queue } = createTestQueue( { mockFetch } );

			const command = createAddItemCommand( { id: 1, quantity: 1, type: 'simple' }, TEST_NONCE );
			const { promise } = queue.enqueue( command );

			// Start execution
			await flushMicrotasks();

			// Abort
			queue.abort();

			const result = await promise;

			expect( wasAborted ).toBe( true );
			expect( result.success ).toBe( false );
		} );
	} );

	describe( 'server reconciliation', () => {
		it( 'should use server state even when different from optimistic', async () => {
			const serverResponse = createCartStateWithItems( [
				// Server returns quantity 3 even though we requested 10 (maybe due to stock limit)
				createServerCartItem( { key: 'key-1', id: 1, quantity: 3 } ),
			] );
			const mockFetch = createSuccessFetch( [ serverResponse ] );
			const { queue, getCartState } = createTestQueue( { mockFetch } );

			const command = createAddItemCommand( { id: 1, quantity: 10, type: 'simple' }, TEST_NONCE );
			queue.enqueue( command );

			// Optimistic: shows 10
			expect( getCartState().items[ 0 ].quantity ).toBe( 10 );

			await queue.flush();

			// Reconciled: shows server's 3
			expect( getCartState().items[ 0 ].quantity ).toBe( 3 );
		} );

		it( 'should handle server adding unexpected items', async () => {
			// Server might add a free gift or bundle component
			const serverResponse = createCartStateWithItems( [
				createServerCartItem( { key: 'key-1', id: 1, quantity: 1 } ),
				createServerCartItem( { key: 'key-free', id: 999, quantity: 1, name: 'Free Gift' } ),
			] );
			const mockFetch = createSuccessFetch( [ serverResponse ] );
			const { queue, getCartState } = createTestQueue( { mockFetch } );

			const command = createAddItemCommand( { id: 1, quantity: 1, type: 'simple' }, TEST_NONCE );
			queue.enqueue( command );

			// Optimistic: just the one item
			expect( getCartState().items ).toHaveLength( 1 );

			await queue.flush();

			// Reconciled: includes server-added item
			expect( getCartState().items ).toHaveLength( 2 );
			expect( getCartState().items.find( ( i ) => i.id === 999 ) ).toBeDefined();
		} );

		it( 'should handle server removing items', async () => {
			const initialCart = createCartStateWithItems( [
				createServerCartItem( { key: 'key-1', id: 1, quantity: 1 } ),
			] );
			// Server removes the item (maybe it went out of stock)
			const serverResponse = createCartStateWithItems( [] );
			const mockFetch = createSuccessFetch( [ serverResponse ] );
			const { queue, getCartState } = createTestQueue( {
				initialCart,
				mockFetch,
			} );

			// Try to add more of the same item
			const command = createAddItemCommand( { id: 1, quantity: 5 }, TEST_NONCE );
			queue.enqueue( command );

			// Optimistic: quantity updated
			expect( getCartState().items[ 0 ].quantity ).toBe( 5 );

			await queue.flush();

			// Reconciled: server removed the item
			expect( getCartState().items ).toHaveLength( 0 );
		} );
	} );

	describe( 'request building', () => {
		it( 'should send correct headers and body for add-item', async () => {
			const serverResponse = createCartStateWithItems( [
				createServerCartItem( { key: 'key-1', id: 42, quantity: 3 } ),
			] );
			const mockFetch = createSuccessFetch( [ serverResponse ] );
			const { queue } = createTestQueue( { mockFetch } );

			const command = createAddItemCommand(
				{ id: 42, quantity: 3, type: 'simple' },
				TEST_NONCE
			);
			await queue.enqueue( command ).promise;

			// Command paths include leading slash, so URL is baseUrl + path
			expect( mockFetch ).toHaveBeenCalledWith(
				`${ TEST_REST_URL }/wc/store/v1/cart/add-item`,
				expect.objectContaining( {
					method: 'POST',
					headers: expect.objectContaining( {
						'Content-Type': 'application/json',
						Nonce: TEST_NONCE,
					} ),
					body: JSON.stringify( { id: 42, quantity: 3 } ),
				} )
			);
		} );

		it( 'should send correct request for remove-item', async () => {
			const initialCart = createCartStateWithItems( [
				createServerCartItem( { key: 'item-to-remove', id: 1 } ),
			] );
			const serverResponse = createCartStateWithItems( [] );
			const mockFetch = createSuccessFetch( [ serverResponse ] );
			const { queue } = createTestQueue( {
				initialCart,
				mockFetch,
			} );

			const command = createRemoveItemCommand( { key: 'item-to-remove' }, TEST_NONCE );
			await queue.enqueue( command ).promise;

			expect( mockFetch ).toHaveBeenCalledWith(
				`${ TEST_REST_URL }/wc/store/v1/cart/remove-item`,
				expect.objectContaining( {
					method: 'POST',
					body: JSON.stringify( { key: 'item-to-remove' } ),
				} )
			);
		} );

		it( 'should send update-item for existing item', async () => {
			const initialCart = createCartStateWithItems( [
				createServerCartItem( { key: 'existing-key', id: 1, quantity: 2 } ),
			] );
			const serverResponse = createCartStateWithItems( [
				createServerCartItem( { key: 'existing-key', id: 1, quantity: 5 } ),
			] );
			const mockFetch = createSuccessFetch( [ serverResponse ] );
			const { queue } = createTestQueue( {
				initialCart,
				mockFetch,
			} );

			const command = createAddItemCommand( { id: 1, quantity: 5 }, TEST_NONCE );
			await queue.enqueue( command ).promise;

			expect( mockFetch ).toHaveBeenCalledWith(
				`${ TEST_REST_URL }/wc/store/v1/cart/update-item`,
				expect.objectContaining( {
					method: 'POST',
					body: JSON.stringify( { key: 'existing-key', quantity: 5 } ),
				} )
			);
		} );
	} );
} );
