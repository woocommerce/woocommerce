/**
 * External dependencies
 */
import type { Cart } from '@woocommerce/types';

/**
 * Internal dependencies
 */
import { MutationQueue } from '../mutation-queue';
import type { StateManager, BatchApiResponse } from '../types';

/**
 * Creates a mock StateManager for testing.
 */
function createMockStateManager( initialState: Partial< Cart > = {} ): {
	stateManager: StateManager;
	mocks: {
		getSnapshot: jest.Mock;
		applyOptimisticUpdate: jest.Mock;
		applyServerState: jest.Mock;
		sendRequest: jest.Mock;
		showErrors: jest.Mock;
	};
	getState: () => Cart;
} {
	let state: Cart = {
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
		...initialState,
	} as Cart;

	const getSnapshot = jest.fn( () => state );
	const applyOptimisticUpdate = jest.fn( ( update: ( s: Cart ) => Cart ) => {
		state = update( state );
	} );
	const applyServerState = jest.fn( ( newState: Cart ) => {
		state = newState;
	} );
	const sendRequest = jest.fn();
	const showErrors = jest.fn();

	return {
		stateManager: {
			getSnapshot,
			applyOptimisticUpdate,
			applyServerState,
			sendRequest,
			showErrors,
		},
		mocks: {
			getSnapshot,
			applyOptimisticUpdate,
			applyServerState,
			sendRequest,
			showErrors,
		},
		getState: () => state,
	};
}

/**
 * Creates a successful batch API response.
 */
function createBatchResponse(
	responses: Array< { status: number; body: unknown } >
): BatchApiResponse {
	return { responses };
}

/**
 * Flushes microtask queue to allow queueMicrotask callbacks to execute.
 */
async function flushMicrotasks(): Promise< void > {
	await new Promise( ( resolve ) => setTimeout( resolve, 0 ) );
}

describe( 'MutationQueue', () => {
	describe( 'State Machine', () => {
		it( 'starts in idle state', () => {
			const { stateManager } = createMockStateManager();
			const queue = new MutationQueue( stateManager );

			expect( queue.isProcessing() ).toBe( false );
		} );

		it( 'transitions to processing when mutation submitted', async () => {
			const { stateManager, mocks } = createMockStateManager();
			mocks.sendRequest.mockResolvedValue(
				createBatchResponse( [
					{ status: 200, body: { items: [], totals: {} } },
				] )
			);

			const queue = new MutationQueue( stateManager );

			// Submit mutation
			const promise = queue.submit( {
				key: 'test',
				mutate: () => ( {
					path: '/wc/store/v1/cart/add-item',
					method: 'POST',
					body: { id: 1 },
				} ),
			} );

			// Should be processing
			expect( queue.isProcessing() ).toBe( true );

			await promise;

			// Should return to idle
			expect( queue.isProcessing() ).toBe( false );
		} );
	} );

	describe( 'Request Isolation', () => {
		it( 'deep clones request body at submission time', async () => {
			const { stateManager, mocks } = createMockStateManager();
			mocks.sendRequest.mockResolvedValue(
				createBatchResponse( [
					{ status: 200, body: { items: [], totals: {} } },
				] )
			);

			const queue = new MutationQueue( stateManager );

			const body = { id: 1, quantity: 1 };

			queue.submit( {
				key: 'test',
				mutate: () => ( {
					path: '/wc/store/v1/cart/add-item',
					method: 'POST',
					body,
				} ),
			} );

			// Modify original body
			body.quantity = 999;

			await flushMicrotasks();

			// Sent request should have original value
			const sentRequest = mocks.sendRequest.mock.calls[ 0 ][ 0 ];
			const batchBody = sentRequest.body as {
				requests: Array< { body: unknown } >;
			};
			expect( batchBody.requests[ 0 ].body ).toEqual( {
				id: 1,
				quantity: 1,
			} );
		} );
	} );

	describe( 'Microtask Batching', () => {
		it( 'batches synchronous submissions into single request', async () => {
			const { stateManager, mocks } = createMockStateManager();
			mocks.sendRequest.mockResolvedValue(
				createBatchResponse( [
					{ status: 200, body: { items: [], totals: {} } },
					{ status: 200, body: { items: [], totals: {} } },
					{ status: 200, body: { items: [], totals: {} } },
				] )
			);

			const queue = new MutationQueue( stateManager );

			// Submit multiple mutations synchronously
			const p1 = queue.submit( {
				key: 'add-1',
				mutate: () => ( {
					path: '/wc/store/v1/cart/add-item',
					method: 'POST',
					body: { id: 1 },
				} ),
			} );
			const p2 = queue.submit( {
				key: 'add-2',
				mutate: () => ( {
					path: '/wc/store/v1/cart/add-item',
					method: 'POST',
					body: { id: 2 },
				} ),
			} );
			const p3 = queue.submit( {
				key: 'add-3',
				mutate: () => ( {
					path: '/wc/store/v1/cart/add-item',
					method: 'POST',
					body: { id: 3 },
				} ),
			} );

			await Promise.all( [ p1, p2, p3 ] );

			// Should have made only one batch request
			expect( mocks.sendRequest ).toHaveBeenCalledTimes( 1 );

			// Batch should contain all three requests
			const sentRequest = mocks.sendRequest.mock.calls[ 0 ][ 0 ];
			const batchBody = sentRequest.body as {
				requests: Array< { body: unknown } >;
			};
			expect( batchBody.requests ).toHaveLength( 3 );
		} );
	} );

	describe( 'Sequential Processing', () => {
		it( 'queues new requests while batch is in-flight', async () => {
			const { stateManager, mocks } = createMockStateManager();

			// First request takes time
			let resolveFirst: ( value: BatchApiResponse ) => void;
			mocks.sendRequest.mockImplementationOnce(
				() =>
					new Promise( ( resolve ) => {
						resolveFirst = resolve;
					} )
			);
			mocks.sendRequest.mockResolvedValueOnce(
				createBatchResponse( [
					{ status: 200, body: { items: [], totals: {} } },
				] )
			);

			const queue = new MutationQueue( stateManager );

			// Submit first mutation
			const p1 = queue.submit( {
				key: 'first',
				mutate: () => ( {
					path: '/wc/store/v1/cart/add-item',
					method: 'POST',
					body: { id: 1 },
				} ),
			} );

			// Let the batch start
			await flushMicrotasks();

			// Submit second mutation while first is in-flight
			const p2 = queue.submit( {
				key: 'second',
				mutate: () => ( {
					path: '/wc/store/v1/cart/add-item',
					method: 'POST',
					body: { id: 2 },
				} ),
			} );

			// First batch should be the only request so far
			expect( mocks.sendRequest ).toHaveBeenCalledTimes( 1 );

			// Complete first request
			resolveFirst!(
				createBatchResponse( [
					{ status: 200, body: { items: [], totals: {} } },
				] )
			);

			await p1;
			await flushMicrotasks();
			await p2;

			// Now second batch should have been sent
			expect( mocks.sendRequest ).toHaveBeenCalledTimes( 2 );
		} );
	} );

	describe( 'Optimistic Updates', () => {
		it( 'applies optimistic updates immediately', async () => {
			const { stateManager, mocks, getState } = createMockStateManager();
			mocks.sendRequest.mockResolvedValue(
				createBatchResponse( [
					{ status: 200, body: { items: [], totals: {} } },
				] )
			);

			const queue = new MutationQueue( stateManager );

			queue.submit( {
				key: 'test',
				mutate: () => ( {
					path: '/wc/store/v1/cart/add-item',
					method: 'POST',
					body: { id: 1 },
				} ),
				optimisticUpdate: ( state ) => ( {
					...state,
					itemsCount: 99,
				} ),
			} );

			// Optimistic update should be applied immediately
			expect( mocks.applyOptimisticUpdate ).toHaveBeenCalledTimes( 1 );
			expect( getState().itemsCount ).toBe( 99 );
		} );
	} );

	describe( 'Reconciliation', () => {
		it( 'applies server state on success', async () => {
			const { stateManager, mocks } = createMockStateManager();

			const serverCart = {
				items: [ { id: 1, quantity: 1, key: 'abc' } ],
				totals: { total_price: '100' },
			};

			mocks.sendRequest.mockResolvedValue(
				createBatchResponse( [ { status: 200, body: serverCart } ] )
			);

			const queue = new MutationQueue( stateManager );

			await queue.submit( {
				key: 'test',
				mutate: () => ( {
					path: '/wc/store/v1/cart/add-item',
					method: 'POST',
					body: { id: 1 },
				} ),
			} );

			expect( mocks.applyServerState ).toHaveBeenCalledWith( serverCart );
		} );

		it( 'rolls back to snapshot on total failure', async () => {
			const initialState = { itemsCount: 5 } as Partial< Cart >;
			const { stateManager, mocks } =
				createMockStateManager( initialState );

			mocks.sendRequest.mockRejectedValue( new Error( 'Network error' ) );

			const queue = new MutationQueue( stateManager );

			await queue.submit( {
				key: 'test',
				mutate: () => ( {
					path: '/wc/store/v1/cart/add-item',
					method: 'POST',
					body: { id: 1 },
				} ),
				optimisticUpdate: ( state ) => ( {
					...state,
					itemsCount: 99,
				} ),
			} );

			// Should rollback to original state (snapshot)
			const appliedState = mocks.applyServerState.mock.calls[ 0 ][ 0 ];
			expect( appliedState.itemsCount ).toBe( 5 );
		} );

		it( 'shows errors on failure', async () => {
			const { stateManager, mocks } = createMockStateManager();
			mocks.sendRequest.mockRejectedValue( new Error( 'Test error' ) );

			const queue = new MutationQueue( stateManager );

			await queue.submit( {
				key: 'test',
				mutate: () => ( {
					path: '/wc/store/v1/cart/add-item',
					method: 'POST',
					body: { id: 1 },
				} ),
			} );

			expect( mocks.showErrors ).toHaveBeenCalledTimes( 1 );
			expect( mocks.showErrors ).toHaveBeenCalledWith(
				expect.arrayContaining( [
					expect.objectContaining( { message: 'Test error' } ),
				] )
			);
		} );
	} );

	describe( 'onSettled Callbacks', () => {
		it( 'fires onSettled on success', async () => {
			const { stateManager, mocks } = createMockStateManager();
			mocks.sendRequest.mockResolvedValue(
				createBatchResponse( [
					{ status: 200, body: { items: [], totals: {} } },
				] )
			);

			const queue = new MutationQueue( stateManager );
			const onSettled = jest.fn();

			await queue.submit( {
				key: 'test',
				mutate: () => ( {
					path: '/wc/store/v1/cart/add-item',
					method: 'POST',
					body: { id: 1 },
				} ),
				onSettled,
			} );

			expect( onSettled ).toHaveBeenCalledTimes( 1 );
			expect( onSettled ).toHaveBeenCalledWith(
				expect.objectContaining( { success: true } )
			);
		} );

		it( 'fires onSettled on failure', async () => {
			const { stateManager, mocks } = createMockStateManager();
			mocks.sendRequest.mockRejectedValue( new Error( 'Test error' ) );

			const queue = new MutationQueue( stateManager );
			const onSettled = jest.fn();

			await queue.submit( {
				key: 'test',
				mutate: () => ( {
					path: '/wc/store/v1/cart/add-item',
					method: 'POST',
					body: { id: 1 },
				} ),
				onSettled,
			} );

			expect( onSettled ).toHaveBeenCalledTimes( 1 );
			expect( onSettled ).toHaveBeenCalledWith(
				expect.objectContaining( { success: false } )
			);
		} );
	} );

	describe( 'Subscribe', () => {
		it( 'notifies subscribers of processing state changes', async () => {
			const { stateManager, mocks } = createMockStateManager();
			mocks.sendRequest.mockResolvedValue(
				createBatchResponse( [
					{ status: 200, body: { items: [], totals: {} } },
				] )
			);

			const queue = new MutationQueue( stateManager );
			const listener = jest.fn();

			queue.subscribe( listener );

			const promise = queue.submit( {
				key: 'test',
				mutate: () => ( {
					path: '/wc/store/v1/cart/add-item',
					method: 'POST',
					body: { id: 1 },
				} ),
			} );

			// Should have been called with true (processing started)
			expect( listener ).toHaveBeenCalledWith( true );

			await promise;

			// Should have been called with false (processing ended)
			expect( listener ).toHaveBeenCalledWith( false );
		} );

		it( 'returns unsubscribe function', async () => {
			const { stateManager, mocks } = createMockStateManager();
			mocks.sendRequest.mockResolvedValue(
				createBatchResponse( [
					{ status: 200, body: { items: [], totals: {} } },
				] )
			);

			const queue = new MutationQueue( stateManager );
			const listener = jest.fn();

			const unsubscribe = queue.subscribe( listener );
			unsubscribe();

			await queue.submit( {
				key: 'test',
				mutate: () => ( {
					path: '/wc/store/v1/cart/add-item',
					method: 'POST',
					body: { id: 1 },
				} ),
			} );

			// Should not have been called after unsubscribe
			expect( listener ).not.toHaveBeenCalled();
		} );
	} );

	describe( 'Mixed Success/Failure', () => {
		it( 'uses last successful server state with mixed results', async () => {
			const { stateManager, mocks } = createMockStateManager();

			const successCart = {
				items: [ { id: 1, quantity: 1, key: 'abc' } ],
				totals: { total_price: '100' },
			};

			mocks.sendRequest.mockResolvedValue(
				createBatchResponse( [
					{ status: 200, body: successCart },
					{ status: 400, body: { message: 'Error' } },
					{ status: 200, body: successCart },
				] )
			);

			const queue = new MutationQueue( stateManager );

			const results = await Promise.all( [
				queue.submit( {
					key: 'add-1',
					mutate: () => ( {
						path: '/wc/store/v1/cart/add-item',
						method: 'POST',
						body: { id: 1 },
					} ),
				} ),
				queue.submit( {
					key: 'add-2',
					mutate: () => ( {
						path: '/wc/store/v1/cart/add-item',
						method: 'POST',
						body: { id: 2 },
					} ),
				} ),
				queue.submit( {
					key: 'add-3',
					mutate: () => ( {
						path: '/wc/store/v1/cart/add-item',
						method: 'POST',
						body: { id: 3 },
					} ),
				} ),
			] );

			// First and third should succeed, second should fail
			expect( results[ 0 ].success ).toBe( true );
			expect( results[ 1 ].success ).toBe( false );
			expect( results[ 2 ].success ).toBe( true );

			// Should apply the last successful cart state
			expect( mocks.applyServerState ).toHaveBeenCalledWith(
				successCart
			);

			// Should show errors for the failed request
			expect( mocks.showErrors ).toHaveBeenCalledTimes( 1 );
		} );
	} );
} );
