/**
 * Internal dependencies
 */
import { createMutationQueue } from '../mutation-batcher';

// Test state type
type TestState = { value: number };

// Helper to create a mock fetch that resolves with batch responses
function createMockFetch(
	responses: Array< { status: number; body: unknown } >
) {
	return jest.fn().mockResolvedValue( {
		ok: true,
		json: () => Promise.resolve( { responses } ),
	} );
}

// Helper to create a mock fetch that fails at the network level
function createFailingFetch( error: Error ) {
	return jest.fn().mockRejectedValue( error );
}

// Helper to create a mock fetch that returns non-200
function createBadResponseFetch( status: number ) {
	return jest.fn().mockResolvedValue( {
		ok: false,
		status,
		json: () => Promise.resolve( {} ),
	} );
}

// Helper to flush microtasks
function flushMicrotasks() {
	return new Promise( ( resolve ) => setTimeout( resolve, 0 ) );
}

describe( 'createMutationQueue', () => {
	let originalFetch: typeof global.fetch;
	let mockState: TestState;
	let snapshot: TestState | null;
	let stateHandler: {
		takeSnapshot: () => TestState;
		rollback: ( snap: TestState ) => void;
		commit: ( serverState: TestState ) => void;
	};

	beforeEach( () => {
		originalFetch = global.fetch;
		mockState = { value: 0 };
		snapshot = null;

		stateHandler = {
			takeSnapshot: () => {
				snapshot = { ...mockState };
				return snapshot;
			},
			rollback: ( snap ) => {
				mockState = { ...snap };
			},
			commit: ( serverState ) => {
				mockState = { ...serverState };
			},
		};
	} );

	afterEach( () => {
		global.fetch = originalFetch;
	} );

	describe( 'batching behavior', () => {
		it( 'batches multiple requests submitted in the same tick', async () => {
			const mockFetch = createMockFetch( [
				{ status: 200, body: { value: 10 } },
				{ status: 200, body: { value: 20 } },
				{ status: 200, body: { value: 30 } },
			] );
			global.fetch = mockFetch;

			const queue = createMutationQueue( {
				endpoint: '/batch',
				getHeaders: () => ( {} ),
				...stateHandler,
			} );

			// Submit 3 requests synchronously
			const p1 = queue.submit( { path: '/a', method: 'POST' } );
			const p2 = queue.submit( { path: '/b', method: 'POST' } );
			const p3 = queue.submit( { path: '/c', method: 'POST' } );

			await Promise.all( [ p1, p2, p3 ] );

			// Should have made exactly ONE fetch call with all 3 requests
			expect( mockFetch ).toHaveBeenCalledTimes( 1 );
			const requestBody = JSON.parse(
				mockFetch.mock.calls[ 0 ][ 1 ].body
			);
			expect( requestBody.requests ).toHaveLength( 3 );
			expect(
				requestBody.requests.map( ( r: { path: string } ) => r.path )
			).toEqual( [ '/a', '/b', '/c' ] );
		} );

		it( 'takes snapshot once at start of cycle, not per request', async () => {
			const mockFetch = createMockFetch( [
				{ status: 200, body: { value: 100 } },
				{ status: 200, body: { value: 100 } },
			] );
			global.fetch = mockFetch;

			const takeSnapshotSpy = jest.spyOn( stateHandler, 'takeSnapshot' );

			const queue = createMutationQueue( {
				endpoint: '/batch',
				getHeaders: () => ( {} ),
				...stateHandler,
			} );

			// First request with inline optimistic update
			queue.submit( {
				path: '/a',
				method: 'POST',
			} );
			mockState.value = 50;

			// Second request in same tick
			queue.submit( {
				path: '/b',
				method: 'POST',
			} );
			mockState.value = 75;

			await flushMicrotasks();

			// Snapshot should be taken exactly once, capturing state before optimistic updates
			expect( takeSnapshotSpy ).toHaveBeenCalledTimes( 1 );
			expect( snapshot ).toEqual( { value: 0 } );
		} );
	} );

	describe( 'response handling and reconciliation', () => {
		it( 'applies server state from last successful response', async () => {
			const mockFetch = createMockFetch( [
				{ status: 200, body: { value: 10 } },
				{ status: 200, body: { value: 20 } }, // This should win
			] );
			global.fetch = mockFetch;

			const queue = createMutationQueue( {
				endpoint: '/batch',
				getHeaders: () => ( {} ),
				...stateHandler,
			} );

			await Promise.all( [
				queue.submit( { path: '/a', method: 'POST' } ),
				queue.submit( { path: '/b', method: 'POST' } ),
			] );

			// State should be from the last successful response
			expect( mockState.value ).toBe( 20 );
		} );

		it( 'accumulates errors from failed items but still applies server state', async () => {
			const mockFetch = createMockFetch( [
				{
					status: 400,
					body: { message: 'Bad request', code: 'bad_request' },
				},
				{ status: 200, body: { value: 42 } },
			] );
			global.fetch = mockFetch;

			const queue = createMutationQueue( {
				endpoint: '/batch',
				getHeaders: () => ( {} ),
				...stateHandler,
			} );

			const p1 = queue.submit( { path: '/a', method: 'POST' } );
			const p2 = queue.submit( { path: '/b', method: 'POST' } );

			// First should reject, second should resolve
			await expect( p1 ).rejects.toThrow( 'Bad request' );
			await expect( p2 ).resolves.toMatchObject( { success: true } );

			// Server state should still be applied (from the successful request)
			expect( mockState.value ).toBe( 42 );
		} );

		it( 'rolls back to snapshot when ALL requests fail', async () => {
			const mockFetch = createMockFetch( [
				{ status: 400, body: { message: 'Error 1' } },
				{ status: 500, body: { message: 'Error 2' } },
			] );
			global.fetch = mockFetch;

			mockState.value = 100;

			const queue = createMutationQueue( {
				endpoint: '/batch',
				getHeaders: () => ( {} ),
				...stateHandler,
			} );

			// Apply optimistic updates inline
			const p1 = queue.submit( {
				path: '/a',
				method: 'POST',
			} );
			mockState.value = 200;

			const p2 = queue.submit( {
				path: '/b',
				method: 'POST',
			} );
			mockState.value = 300;

			await expect( p1 ).rejects.toThrow();
			await expect( p2 ).rejects.toThrow();

			// Should rollback to snapshot (value: 100)
			expect( mockState.value ).toBe( 100 );
		} );

		it( 'rolls back on total network failure', async () => {
			global.fetch = createFailingFetch( new Error( 'Network error' ) );

			mockState.value = 50;

			const queue = createMutationQueue( {
				endpoint: '/batch',
				getHeaders: () => ( {} ),
				...stateHandler,
			} );

			const p1 = queue.submit( {
				path: '/a',
				method: 'POST',
			} );
			mockState.value = 999;

			await expect( p1 ).rejects.toThrow( 'Network error' );
			expect( mockState.value ).toBe( 50 );
		} );

		it( 'rolls back on batch endpoint returning error status', async () => {
			global.fetch = createBadResponseFetch( 503 );

			mockState.value = 25;

			const queue = createMutationQueue( {
				endpoint: '/batch',
				getHeaders: () => ( {} ),
				...stateHandler,
			} );

			const p1 = queue.submit( {
				path: '/a',
				method: 'POST',
			} );
			mockState.value = 888;

			await expect( p1 ).rejects.toThrow( 'Request failed: 503' );
			expect( mockState.value ).toBe( 25 );
		} );
	} );

	describe( 'onSettled callback timing', () => {
		it( 'runs onSettled before isProcessing clears', async () => {
			const mockFetch = createMockFetch( [
				{ status: 200, body: { value: 1 } },
			] );
			global.fetch = mockFetch;

			const queue = createMutationQueue( {
				endpoint: '/batch',
				getHeaders: () => ( {} ),
				...stateHandler,
			} );

			let isProcessingDuringOnSettled: boolean | undefined;

			await queue.submit( {
				id: '1',
				path: '/a',
				method: 'POST',
				onSettled: () => {
					isProcessingDuringOnSettled =
						queue.getStatus().isProcessing;
				},
			} );

			// onSettled should run while isProcessing is still true
			expect( isProcessingDuringOnSettled ).toBe( true );

			// After promise resolves, isProcessing should be false
			expect( queue.getStatus().isProcessing ).toBe( false );
		} );

		it( 'provides success status and data to onSettled', async () => {
			const serverState = { value: 42 };
			const mockFetch = createMockFetch( [
				{ status: 200, body: serverState },
			] );
			global.fetch = mockFetch;

			const queue = createMutationQueue( {
				endpoint: '/batch',
				getHeaders: () => ( {} ),
				...stateHandler,
			} );

			let settledResult:
				| { success: boolean; data?: TestState }
				| undefined;

			await queue.submit( {
				id: '1',
				path: '/a',
				method: 'POST',
				onSettled: ( result ) => {
					settledResult = result;
				},
			} );

			expect( settledResult ).toEqual( {
				success: true,
				data: serverState,
			} );
		} );

		it( 'provides error to onSettled on failure', async () => {
			const mockFetch = createMockFetch( [
				{
					status: 400,
					body: {
						message: 'Validation failed',
						code: 'validation_error',
					},
				},
			] );
			global.fetch = mockFetch;

			const queue = createMutationQueue( {
				endpoint: '/batch',
				getHeaders: () => ( {} ),
				...stateHandler,
			} );

			let settledResult: { success: boolean; error?: Error } | undefined;

			// We need to catch the rejection
			try {
				await queue.submit( {
					id: '1',
					path: '/a',
					method: 'POST',
					onSettled: ( result ) => {
						settledResult = result;
					},
				} );
			} catch {
				// Expected
			}

			expect( settledResult?.success ).toBe( false );
			expect( settledResult?.error?.message ).toBe( 'Validation failed' );
		} );
	} );

	describe( 'single batch in-flight', () => {
		it( 'only allows one batch in-flight at a time to prevent server race conditions', async () => {
			// This test verifies that we don't send multiple batches concurrently,
			// which would cause race conditions on the server (lost cart updates).

			let fetchCallCount = 0;
			const fetchPromises: Array< {
				resolve: ( value: Response ) => void;
			} > = [];

			global.fetch = jest.fn( () => {
				fetchCallCount++;
				return new Promise< Response >( ( resolve ) => {
					fetchPromises.push( { resolve } );
				} );
			} ) as jest.Mock;

			const queue = createMutationQueue( {
				endpoint: '/batch',
				getHeaders: () => ( {} ),
				...stateHandler,
			} );

			// First batch - submit and let microtask fire
			const p1 = queue.submit( { path: '/a', method: 'POST' } );
			await flushMicrotasks();
			expect( fetchCallCount ).toBe( 1 );

			// Second request - submit while first is in-flight
			const p2 = queue.submit( { path: '/b', method: 'POST' } );
			await flushMicrotasks();

			// Should NOT have sent a second batch yet - only one in-flight allowed
			expect( fetchCallCount ).toBe( 1 );

			// Resolve first batch
			fetchPromises[ 0 ].resolve( {
				ok: true,
				json: () =>
					Promise.resolve( {
						responses: [ { status: 200, body: { value: 100 } } ],
					} ),
			} as Response );

			// Wait for first batch to complete and second to be sent
			await flushMicrotasks();
			expect( fetchCallCount ).toBe( 2 );

			// Resolve second batch
			fetchPromises[ 1 ].resolve( {
				ok: true,
				json: () =>
					Promise.resolve( {
						responses: [ { status: 200, body: { value: 200 } } ],
					} ),
			} as Response );

			await Promise.all( [ p1, p2 ] );

			// Should use value from the second (and last) batch
			expect( mockState.value ).toBe( 200 );
		} );
	} );

	describe( 'requests during in-flight processing', () => {
		it( 'collects new requests while batch is in-flight and sends them after', async () => {
			let fetchCallCount = 0;
			const fetchPromises: Array< {
				resolve: ( value: Response ) => void;
			} > = [];

			global.fetch = jest.fn( () => {
				fetchCallCount++;
				return new Promise< Response >( ( resolve ) => {
					fetchPromises.push( { resolve } );
				} );
			} ) as jest.Mock;

			const queue = createMutationQueue( {
				endpoint: '/batch',
				getHeaders: () => ( {} ),
				...stateHandler,
			} );

			// First request - starts the cycle
			const p1 = queue.submit( {
				id: '1',
				path: '/first',
				method: 'POST',
			} );
			await flushMicrotasks();
			expect( fetchCallCount ).toBe( 1 );

			// New request while first is in-flight
			const p2 = queue.submit( {
				id: '2',
				path: '/second',
				method: 'POST',
			} );

			// Resolve first batch
			fetchPromises[ 0 ].resolve( {
				ok: true,
				json: () =>
					Promise.resolve( {
						responses: [ { status: 200, body: { value: 1 } } ],
					} ),
			} as Response );

			await flushMicrotasks();

			// Second batch should now be sent
			expect( fetchCallCount ).toBe( 2 );

			// Resolve second batch
			fetchPromises[ 1 ].resolve( {
				ok: true,
				json: () =>
					Promise.resolve( {
						responses: [ { status: 200, body: { value: 2 } } ],
					} ),
			} as Response );

			await Promise.all( [ p1, p2 ] );

			// Both should succeed, final state from second batch
			expect( mockState.value ).toBe( 2 );
		} );
	} );

	describe( 'getStatus', () => {
		it( 'reports correct processing state and pending count', async () => {
			const fetchPromise: { resolve?: ( value: Response ) => void } = {};
			global.fetch = jest.fn(
				() =>
					new Promise< Response >( ( resolve ) => {
						fetchPromise.resolve = resolve;
					} )
			);

			const queue = createMutationQueue( {
				endpoint: '/batch',
				getHeaders: () => ( {} ),
				...stateHandler,
			} );

			// Initially idle
			expect( queue.getStatus() ).toEqual( {
				isProcessing: false,
				pendingCount: 0,
			} );

			// Submit request
			const p1 = queue.submit( { path: '/a', method: 'POST' } );

			// Now processing with 1 pending
			expect( queue.getStatus().isProcessing ).toBe( true );
			expect( queue.getStatus().pendingCount ).toBe( 1 );

			// After microtask, pending should be 0 (sent)
			await flushMicrotasks();
			expect( queue.getStatus().pendingCount ).toBe( 0 );
			expect( queue.getStatus().isProcessing ).toBe( true );

			// Resolve
			fetchPromise.resolve( {
				ok: true,
				json: () =>
					Promise.resolve( {
						responses: [ { status: 200, body: {} } ],
					} ),
			} as Response );

			await p1;

			// Back to idle
			expect( queue.getStatus() ).toEqual( {
				isProcessing: false,
				pendingCount: 0,
			} );
		} );
	} );

	describe( 'body cloning', () => {
		it( 'clones request body to prevent mutation corruption', async () => {
			// This test verifies that mutating an object after submission
			// does not affect the queued request body.
			let capturedBody: unknown;

			global.fetch = jest.fn( ( _url, options ) => {
				// Capture what was actually sent
				const parsed = JSON.parse( options.body as string );
				capturedBody = parsed.requests[ 0 ].body;
				return Promise.resolve( {
					ok: true,
					json: () =>
						Promise.resolve( {
							responses: [ { status: 200, body: { value: 1 } } ],
						} ),
				} );
			} ) as jest.Mock;

			const queue = createMutationQueue( {
				endpoint: '/batch',
				getHeaders: () => ( {} ),
				...stateHandler,
			} );

			// Create a mutable body object
			const body = { id: 1, quantity: 1 };

			// Submit the request
			const promise = queue.submit( {
				path: '/a',
				method: 'POST',
				body,
			} );

			// Mutate the body AFTER submission (simulating another click's
			// optimistic update finding and mutating the same object)
			body.quantity = 999;

			await promise;

			// The sent body should have the ORIGINAL value, not the mutated one
			expect( capturedBody ).toEqual( { id: 1, quantity: 1 } );
		} );

		it( 'isolates bodies when rapid submits share a reference to the same object', async () => {
			// Simulates rapid add-to-cart clicks on the same product.
			// Both submits reference the same `item` object from state.
			// The optimistic update between them mutates item.quantity,
			// which would corrupt the first request's body without cloning.
			const capturedBodies: unknown[] = [];

			global.fetch = jest.fn( ( _url, options ) => {
				const parsed = JSON.parse( options.body as string );
				for ( const req of parsed.requests ) {
					capturedBodies.push( req.body );
				}
				return Promise.resolve( {
					ok: true,
					json: () =>
						Promise.resolve( {
							responses: parsed.requests.map( () => ( {
								status: 200,
								body: { value: 1 },
							} ) ),
						} ),
				} );
			} ) as jest.Mock;

			const queue = createMutationQueue( {
				endpoint: '/batch',
				getHeaders: () => ( {} ),
				...stateHandler,
			} );

			// Shared mutable object — like an item reference in state.cart.items
			const item = { id: 1, quantity: 1 };

			// First click: submit with quantity 1, then optimistically set to 2
			queue.submit( {
				path: '/update-item',
				method: 'POST',
				body: item,
			} );
			item.quantity = 2; // optimistic update

			// Second click: submit with quantity 2, then optimistically set to 3
			queue.submit( {
				path: '/update-item',
				method: 'POST',
				body: item,
			} );
			item.quantity = 3; // optimistic update

			await flushMicrotasks();

			// Each request should have the quantity at the time it was submitted
			expect( capturedBodies ).toEqual( [
				{ id: 1, quantity: 1 },
				{ id: 1, quantity: 2 },
			] );
		} );
	} );

	describe( 'applyOptimistic and snapshot ordering', () => {
		it( 'takes snapshot before running applyOptimistic', async () => {
			const mockFetch = createMockFetch( [
				{ status: 200, body: { value: 99 } },
			] );
			global.fetch = mockFetch;

			const takeSnapshotSpy = jest.spyOn( stateHandler, 'takeSnapshot' );

			mockState.value = 42;

			const queue = createMutationQueue( {
				endpoint: '/batch',
				getHeaders: () => ( {} ),
				...stateHandler,
			} );

			await queue.submit( {
				path: '/a',
				method: 'POST',
				applyOptimistic: () => {
					mockState.value = 999;
				},
			} );

			// Snapshot should have captured the state BEFORE
			// applyOptimistic mutated it.
			expect( takeSnapshotSpy ).toHaveBeenCalledTimes( 1 );
			expect( snapshot ).toEqual( { value: 42 } );
		} );

		it( 'rolls back optimistic mutations when all requests fail', async () => {
			const mockFetch = createMockFetch( [
				{ status: 500, body: { message: 'Server error' } },
			] );
			global.fetch = mockFetch;

			mockState.value = 10;

			const queue = createMutationQueue( {
				endpoint: '/batch',
				getHeaders: () => ( {} ),
				...stateHandler,
			} );

			const p1 = queue.submit( {
				path: '/a',
				method: 'POST',
				applyOptimistic: () => {
					mockState.value = 20;
				},
			} );

			// Optimistic update should be applied immediately.
			expect( mockState.value ).toBe( 20 );

			await expect( p1 ).rejects.toThrow();

			// After failure, state should be rolled back to
			// pre-optimistic value.
			expect( mockState.value ).toBe( 10 );
		} );

		it( 'rolls back all optimistic updates from multiple rapid submits', async () => {
			const mockFetch = createMockFetch( [
				{ status: 400, body: { message: 'Error 1' } },
				{ status: 400, body: { message: 'Error 2' } },
				{ status: 400, body: { message: 'Error 3' } },
			] );
			global.fetch = mockFetch;

			mockState.value = 0;

			const queue = createMutationQueue( {
				endpoint: '/batch',
				getHeaders: () => ( {} ),
				...stateHandler,
			} );

			// Simulate three rapid clicks — each compounds on previous.
			const p1 = queue.submit( {
				path: '/a',
				method: 'POST',
				applyOptimistic: () => {
					mockState.value += 10;
				},
			} );
			const p2 = queue.submit( {
				path: '/b',
				method: 'POST',
				applyOptimistic: () => {
					mockState.value += 10;
				},
			} );
			const p3 = queue.submit( {
				path: '/c',
				method: 'POST',
				applyOptimistic: () => {
					mockState.value += 10;
				},
			} );

			// All three optimistic updates should have been applied.
			expect( mockState.value ).toBe( 30 );

			await expect( p1 ).rejects.toThrow();
			await expect( p2 ).rejects.toThrow();
			await expect( p3 ).rejects.toThrow();

			// Snapshot was taken before the first applyOptimistic,
			// so rollback should undo ALL three updates.
			expect( mockState.value ).toBe( 0 );
		} );

		it( 'clones body before applyOptimistic mutates shared references', async () => {
			let capturedBody: unknown;

			global.fetch = jest.fn( ( _url, options ) => {
				const parsed = JSON.parse( options.body as string );
				capturedBody = parsed.requests[ 0 ].body;
				return Promise.resolve( {
					ok: true,
					json: () =>
						Promise.resolve( {
							responses: [ { status: 200, body: { value: 1 } } ],
						} ),
				} );
			} ) as jest.Mock;

			const queue = createMutationQueue( {
				endpoint: '/batch',
				getHeaders: () => ( {} ),
				...stateHandler,
			} );

			// Shared object — like an item reference in state.cart.items.
			const item = { id: 1, quantity: 1 };

			await queue.submit( {
				path: '/update-item',
				method: 'POST',
				body: item,
				applyOptimistic: () => {
					// This mutates the same object passed as body.
					// The body must have been cloned before this runs.
					item.quantity = 5;
				},
			} );

			// Server should receive the pre-optimistic quantity.
			expect( capturedBody ).toEqual( { id: 1, quantity: 1 } );
		} );

		it( 'rolls back on network failure even with applyOptimistic', async () => {
			global.fetch = createFailingFetch( new Error( 'Network error' ) );

			mockState.value = 50;

			const queue = createMutationQueue( {
				endpoint: '/batch',
				getHeaders: () => ( {} ),
				...stateHandler,
			} );

			const p1 = queue.submit( {
				path: '/a',
				method: 'POST',
				applyOptimistic: () => {
					mockState.value = 777;
				},
			} );

			expect( mockState.value ).toBe( 777 );

			await expect( p1 ).rejects.toThrow( 'Network error' );
			expect( mockState.value ).toBe( 50 );
		} );
	} );

	describe( 'waitForIdle', () => {
		it( 'resolves immediately when not processing', async () => {
			const mockFetch = createMockFetch( [] );
			global.fetch = mockFetch;

			const queue = createMutationQueue( {
				endpoint: '/batch',
				getHeaders: () => ( {} ),
				...stateHandler,
			} );

			// Should resolve immediately — nothing in progress.
			await queue.waitForIdle();
		} );

		it( 'resolves after the processing cycle completes', async () => {
			const fetchPromise: { resolve?: ( value: Response ) => void } = {};
			global.fetch = jest.fn(
				() =>
					new Promise< Response >( ( resolve ) => {
						fetchPromise.resolve = resolve;
					} )
			);

			const queue = createMutationQueue( {
				endpoint: '/batch',
				getHeaders: () => ( {} ),
				...stateHandler,
			} );

			queue.submit( { path: '/a', method: 'POST' } );

			let idleResolved = false;
			queue.waitForIdle().then( () => {
				idleResolved = true;
			} );

			// Let the microtask fire (sends the batch), but fetch is still pending.
			await flushMicrotasks();
			expect( idleResolved ).toBe( false );

			// Resolve the fetch — completes the cycle.
			fetchPromise.resolve!( {
				ok: true,
				json: () =>
					Promise.resolve( {
						responses: [ { status: 200, body: { value: 42 } } ],
					} ),
			} as Response );

			await flushMicrotasks();
			await flushMicrotasks();

			expect( idleResolved ).toBe( true );
			expect( queue.getStatus().isProcessing ).toBe( false );
		} );

		it( 'resolves multiple waiters when cycle completes', async () => {
			const fetchPromise: { resolve?: ( value: Response ) => void } = {};
			global.fetch = jest.fn(
				() =>
					new Promise< Response >( ( resolve ) => {
						fetchPromise.resolve = resolve;
					} )
			);

			const queue = createMutationQueue( {
				endpoint: '/batch',
				getHeaders: () => ( {} ),
				...stateHandler,
			} );

			queue.submit( { path: '/a', method: 'POST' } );

			let waiter1Resolved = false;
			let waiter2Resolved = false;

			queue.waitForIdle().then( () => {
				waiter1Resolved = true;
			} );
			queue.waitForIdle().then( () => {
				waiter2Resolved = true;
			} );

			await flushMicrotasks();
			expect( waiter1Resolved ).toBe( false );
			expect( waiter2Resolved ).toBe( false );

			fetchPromise.resolve!( {
				ok: true,
				json: () =>
					Promise.resolve( {
						responses: [ { status: 200, body: { value: 1 } } ],
					} ),
			} as Response );

			await flushMicrotasks();
			await flushMicrotasks();

			expect( waiter1Resolved ).toBe( true );
			expect( waiter2Resolved ).toBe( true );
		} );
	} );
} );
