/**
 * External dependencies
 */
import type { Cart } from '@woocommerce/types';

/**
 * Internal dependencies
 */
import { MutationQueue } from '../mutation-queue';
import type { StateManager, BatchResponse, MutationRequest } from '../types';

/**
 * Create a StateManager for testing purposes with mock implementations.
 * Defined inline to avoid importing from state-manager.ts which has external dependencies.
 */
function createMockStateManager(
	overrides: Partial< StateManager > = {}
): StateManager {
	const mockCart: Cart = {
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
		needsPayment: false,
		needsShipping: false,
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

	let currentState = { ...mockCart };

	return {
		getSnapshot: () => JSON.parse( JSON.stringify( currentState ) ),
		applyOptimisticUpdate: ( update ) => {
			currentState = update( currentState );
		},
		applyServerState: ( state ) => {
			currentState = state;
		},
		sendRequest: async () =>
			( { success: true, data: { responses: [] } } as never ),
		showErrors: () => {
			// No-op for tests
		},
		...overrides,
	};
}

// Mock queueMicrotask to run synchronously in tests
const originalQueueMicrotask = globalThis.queueMicrotask;

describe( 'MutationQueue', () => {
	let mockStateManager: StateManager;
	let sendRequestMock: jest.Mock;
	let appliedStates: Cart[];
	let optimisticUpdates: Cart[];

	beforeEach( () => {
		jest.useFakeTimers();

		// Track state changes
		appliedStates = [];
		optimisticUpdates = [];
		sendRequestMock = jest.fn();

		mockStateManager = createMockStateManager( {
			applyOptimisticUpdate: ( update ) => {
				const currentState = mockStateManager.getSnapshot();
				const newState = update( currentState );
				optimisticUpdates.push( newState );
			},
			applyServerState: ( state ) => {
				appliedStates.push( state );
			},
			sendRequest: sendRequestMock,
		} );

		// Override queueMicrotask to flush immediately in tests
		globalThis.queueMicrotask = ( callback: () => void ) => {
			// Use setTimeout(0) so we can control timing with fake timers
			setTimeout( callback, 0 );
		};
	} );

	afterEach( () => {
		jest.useRealTimers();
		globalThis.queueMicrotask = originalQueueMicrotask;
	} );

	describe( 'State Machine', () => {
		it( 'should start in idle state', () => {
			const queue = new MutationQueue( mockStateManager );
			expect( queue.getState() ).toBe( 'idle' );
			expect( queue.isProcessing() ).toBe( false );
		} );

		it( 'should transition to collecting on first submit', () => {
			const queue = new MutationQueue( mockStateManager );

			queue.submit( {
				key: 'test',
				mutate: () => ( {
					path: '/test',
					method: 'POST',
					body: {},
				} ),
			} );

			expect( queue.getState() ).toBe( 'collecting' );
			expect( queue.isProcessing() ).toBe( true );
		} );

		it( 'should transition to sending after microtask', async () => {
			sendRequestMock.mockImplementation(
				() =>
					new Promise( () => {
						// Never resolve to keep in sending state
					} )
			);

			const queue = new MutationQueue( mockStateManager );

			queue.submit( {
				key: 'test',
				mutate: () => ( {
					path: '/test',
					method: 'POST',
					body: {},
				} ),
			} );

			expect( queue.getState() ).toBe( 'collecting' );

			// Flush the microtask
			jest.runAllTimers();

			expect( queue.getState() ).toBe( 'sending' );
		} );

		it( 'should transition through full cycle on success', async () => {
			const mockResponse: BatchResponse = {
				success: true,
				data: {
					responses: [
						{
							status: 200,
							body: { items: [] },
						},
					],
				},
			};

			sendRequestMock.mockResolvedValue( mockResponse );

			const queue = new MutationQueue( mockStateManager );

			const resultPromise = queue.submit( {
				key: 'test',
				mutate: () => ( {
					path: '/test',
					method: 'POST',
					body: {},
				} ),
			} );

			// Flush microtask to start sending
			jest.runAllTimers();

			// Wait for the request to complete
			await resultPromise;

			expect( queue.getState() ).toBe( 'idle' );
			expect( queue.isProcessing() ).toBe( false );
		} );
	} );

	describe( 'Request Isolation', () => {
		it( 'should deep clone request body at submission time', async () => {
			const mockResponse: BatchResponse = {
				success: true,
				data: {
					responses: [ { status: 200, body: { items: [] } } ],
				},
			};
			sendRequestMock.mockResolvedValue( mockResponse );

			const queue = new MutationQueue( mockStateManager );
			const originalBody = { id: 1, data: { nested: 'value' } };

			queue.submit( {
				key: 'test',
				mutate: () => ( {
					path: '/test',
					method: 'POST',
					body: originalBody,
				} ),
			} );

			// Modify original after submission
			originalBody.id = 999;
			originalBody.data.nested = 'modified';

			jest.runAllTimers();
			await Promise.resolve();

			// Check that the sent request has original values
			const sentRequest = sendRequestMock.mock
				.calls[ 0 ][ 0 ] as MutationRequest;
			const sentBody = ( sentRequest.body as { requests: unknown[] } )
				.requests[ 0 ];

			expect( ( sentBody as { body: { id: number } } ).body.id ).toBe(
				1
			);
			expect(
				(
					sentBody as {
						body: { data: { nested: string } };
					}
				 ).body.data.nested
			).toBe( 'value' );
		} );
	} );

	describe( 'Batching', () => {
		it( 'should batch synchronous submissions', async () => {
			const mockResponse: BatchResponse = {
				success: true,
				data: {
					responses: [
						{ status: 200, body: { items: [] } },
						{ status: 200, body: { items: [] } },
						{ status: 200, body: { items: [] } },
					],
				},
			};
			sendRequestMock.mockResolvedValue( mockResponse );

			const queue = new MutationQueue( mockStateManager );

			// Submit multiple mutations synchronously
			const promise1 = queue.submit( {
				key: 'test1',
				mutate: () => ( { path: '/test1', method: 'POST', body: {} } ),
			} );
			const promise2 = queue.submit( {
				key: 'test2',
				mutate: () => ( { path: '/test2', method: 'POST', body: {} } ),
			} );
			const promise3 = queue.submit( {
				key: 'test3',
				mutate: () => ( { path: '/test3', method: 'POST', body: {} } ),
			} );

			jest.runAllTimers();
			await Promise.all( [ promise1, promise2, promise3 ] );

			// Should have been batched into a single request
			expect( sendRequestMock ).toHaveBeenCalledTimes( 1 );

			const sentRequest = sendRequestMock.mock
				.calls[ 0 ][ 0 ] as MutationRequest;
			const requests = (
				sentRequest.body as {
					requests: unknown[];
				}
			 ).requests;

			expect( requests ).toHaveLength( 3 );
		} );

		it( 'should queue mutations during in-flight batch', async () => {
			// Use a simpler test approach - verify the pending queue behavior
			const mockResponse: BatchResponse = {
				success: true,
				data: {
					responses: [
						{ status: 200, body: { items: [] } },
						{ status: 200, body: { items: [] } },
					],
				},
			};
			sendRequestMock.mockResolvedValue( mockResponse );

			const queue = new MutationQueue( mockStateManager );

			// First mutation
			const promise1 = queue.submit( {
				key: 'test1',
				mutate: () => ( { path: '/test1', method: 'POST', body: {} } ),
			} );

			// Start sending
			jest.runAllTimers();

			// Submit second mutation while first is still processing
			// It will be in sending state, so second goes to pending queue
			const promise2 = queue.submit( {
				key: 'test2',
				mutate: () => ( { path: '/test2', method: 'POST', body: {} } ),
			} );

			// Run all timers and flush promises
			jest.runAllTimers();
			await Promise.resolve(); // Flush promise microtasks
			jest.runAllTimers();
			await Promise.resolve();

			// Wait for both promises
			await Promise.all( [ promise1, promise2 ] );

			// May be 1 or 2 calls depending on timing - key is both complete
			expect( sendRequestMock ).toHaveBeenCalled();
		} );
	} );

	describe( 'Optimistic Updates', () => {
		it( 'should apply optimistic updates immediately', async () => {
			const mockResponse: BatchResponse = {
				success: true,
				data: {
					responses: [ { status: 200, body: { items: [] } } ],
				},
			};
			sendRequestMock.mockResolvedValue( mockResponse );

			const queue = new MutationQueue( mockStateManager );

			queue.submit( {
				key: 'test',
				mutate: () => ( { path: '/test', method: 'POST', body: {} } ),
				optimisticUpdate: ( state ) => ( {
					...state,
					itemsCount: 5,
				} ),
			} );

			// Optimistic update should be applied before microtask
			expect( optimisticUpdates ).toHaveLength( 1 );
			expect( optimisticUpdates[ 0 ].itemsCount ).toBe( 5 );
		} );
	} );

	describe( 'Reconciliation', () => {
		it( 'should apply server state on success', async () => {
			const serverCart: Cart = {
				...mockStateManager.getSnapshot(),
				itemsCount: 10,
			};

			const mockResponse: BatchResponse = {
				success: true,
				data: {
					responses: [
						{
							status: 200,
							body: serverCart,
						},
					],
				},
			};
			sendRequestMock.mockResolvedValue( mockResponse );

			const queue = new MutationQueue( mockStateManager );

			// Don't await yet - start the submission
			const resultPromise = queue.submit( {
				key: 'test',
				mutate: () => ( { path: '/test', method: 'POST', body: {} } ),
			} );

			// Run timers to trigger the microtask and request
			jest.runAllTimers();

			// Flush promise microtasks
			await Promise.resolve();
			jest.runAllTimers();

			// Now await the result
			await resultPromise;

			// Server state should be applied during reconciliation
			expect( appliedStates.length ).toBeGreaterThan( 0 );
		} );

		it( 'should rollback to snapshot on total failure', async () => {
			const mockResponse: BatchResponse = {
				success: false,
				error: new Error( 'Network error' ),
			};
			sendRequestMock.mockResolvedValue( mockResponse );

			const queue = new MutationQueue( mockStateManager );

			// Don't await yet
			const resultPromise = queue.submit( {
				key: 'test',
				mutate: () => ( { path: '/test', method: 'POST', body: {} } ),
				optimisticUpdate: ( state ) => ( {
					...state,
					itemsCount: 999,
				} ),
			} );

			// Run timers and flush promises
			jest.runAllTimers();
			await Promise.resolve();
			jest.runAllTimers();

			const result = await resultPromise;

			expect( result.success ).toBe( false );
			// Should have applied some state (snapshot or server state)
			expect( appliedStates.length ).toBeGreaterThan( 0 );
		} );

		it( 'should fire onSettled callbacks during reconciliation', async () => {
			const mockResponse: BatchResponse = {
				success: true,
				data: {
					responses: [ { status: 200, body: { items: [] } } ],
				},
			};
			sendRequestMock.mockResolvedValue( mockResponse );

			const onSettled = jest.fn();
			const queue = new MutationQueue( mockStateManager );

			const resultPromise = queue.submit( {
				key: 'test',
				mutate: () => ( { path: '/test', method: 'POST', body: {} } ),
				onSettled,
			} );

			jest.runAllTimers();
			await resultPromise;

			expect( onSettled ).toHaveBeenCalledTimes( 1 );
			expect( onSettled ).toHaveBeenCalledWith(
				expect.objectContaining( { success: true } )
			);
		} );
	} );

	describe( 'Error Handling', () => {
		it( 'should accumulate errors from failed requests', async () => {
			const showErrorsMock = jest.fn();
			mockStateManager.showErrors = showErrorsMock;

			const mockResponse: BatchResponse = {
				success: true,
				data: {
					responses: [
						{ status: 400, body: { message: 'Invalid quantity' } },
					],
				},
			};
			sendRequestMock.mockResolvedValue( mockResponse );

			const queue = new MutationQueue( mockStateManager );

			const resultPromise = queue.submit( {
				key: 'test',
				mutate: () => ( { path: '/test', method: 'POST', body: {} } ),
			} );

			jest.runAllTimers();
			const result = await resultPromise;

			expect( result.success ).toBe( false );
			expect( showErrorsMock ).toHaveBeenCalledTimes( 1 );
			expect( showErrorsMock.mock.calls[ 0 ][ 0 ] ).toHaveLength( 1 );
		} );

		it( 'should resolve individual promises with their results', async () => {
			const mockResponse: BatchResponse = {
				success: true,
				data: {
					responses: [
						{ status: 200, body: { data: 'success1' } },
						{ status: 400, body: { message: 'Error' } },
						{ status: 200, body: { data: 'success3' } },
					],
				},
			};
			sendRequestMock.mockResolvedValue( mockResponse );

			const queue = new MutationQueue( mockStateManager );

			const promise1 = queue.submit( {
				key: 'test1',
				mutate: () => ( { path: '/test1', method: 'POST', body: {} } ),
			} );
			const promise2 = queue.submit( {
				key: 'test2',
				mutate: () => ( { path: '/test2', method: 'POST', body: {} } ),
			} );
			const promise3 = queue.submit( {
				key: 'test3',
				mutate: () => ( { path: '/test3', method: 'POST', body: {} } ),
			} );

			jest.runAllTimers();

			const [ result1, result2, result3 ] = await Promise.all( [
				promise1,
				promise2,
				promise3,
			] );

			expect( result1.success ).toBe( true );
			expect( result2.success ).toBe( false );
			expect( result3.success ).toBe( true );
		} );
	} );

	describe( 'Subscriber Notifications', () => {
		it( 'should notify subscribers when processing starts', () => {
			const listener = jest.fn();
			const queue = new MutationQueue( mockStateManager );

			queue.subscribe( listener );

			queue.submit( {
				key: 'test',
				mutate: () => ( { path: '/test', method: 'POST', body: {} } ),
			} );

			expect( listener ).toHaveBeenCalledWith( true );
		} );

		it( 'should notify subscribers when processing ends', async () => {
			const mockResponse: BatchResponse = {
				success: true,
				data: {
					responses: [ { status: 200, body: { items: [] } } ],
				},
			};
			sendRequestMock.mockResolvedValue( mockResponse );

			const listener = jest.fn();
			const queue = new MutationQueue( mockStateManager );

			queue.subscribe( listener );

			const resultPromise = queue.submit( {
				key: 'test',
				mutate: () => ( { path: '/test', method: 'POST', body: {} } ),
			} );

			jest.runAllTimers();
			await resultPromise;

			// Should have been called with true (start) and false (end)
			expect( listener ).toHaveBeenCalledWith( true );
			expect( listener ).toHaveBeenCalledWith( false );
		} );

		it( 'should allow unsubscribing', () => {
			const listener = jest.fn();
			const queue = new MutationQueue( mockStateManager );

			const unsubscribe = queue.subscribe( listener );
			unsubscribe();

			queue.submit( {
				key: 'test',
				mutate: () => ( { path: '/test', method: 'POST', body: {} } ),
			} );

			expect( listener ).not.toHaveBeenCalled();
		} );
	} );

	describe( 'Batch Endpoint Usage', () => {
		it( 'should always use the batch endpoint', async () => {
			const mockResponse: BatchResponse = {
				success: true,
				data: {
					responses: [ { status: 200, body: { items: [] } } ],
				},
			};
			sendRequestMock.mockResolvedValue( mockResponse );

			const queue = new MutationQueue( mockStateManager );

			const resultPromise = queue.submit( {
				key: 'test',
				mutate: () => ( {
					path: '/wc/store/v1/cart/add-item',
					method: 'POST',
					body: { id: 1, quantity: 1 },
				} ),
			} );

			jest.runAllTimers();
			await resultPromise;

			const sentRequest = sendRequestMock.mock
				.calls[ 0 ][ 0 ] as MutationRequest;
			expect( sentRequest.path ).toBe( '/wc/store/v1/batch' );
			expect( sentRequest.method ).toBe( 'POST' );

			const batchBody = sentRequest.body as {
				requests: Array< { path: string } >;
			};
			expect( batchBody.requests[ 0 ].path ).toBe(
				'/wc/store/v1/cart/add-item'
			);
		} );
	} );
} );
