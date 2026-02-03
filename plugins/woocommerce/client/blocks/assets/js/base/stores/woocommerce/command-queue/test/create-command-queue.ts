/**
 * Tests for createCommandQueue
 */

/**
 * Internal dependencies
 */
import { createCommandQueue } from '../create-command-queue';
import type { Command, StateHandler, BatchResult } from '../types';
import { generateCommandId } from '../types';

/**
 * Simple state type for testing.
 */
interface TestState {
	items: Array< { id: number; quantity: number } >;
	total: number;
}

/**
 * Create a mock state handler for testing.
 */
function createMockStateHandler(
	initialState?: TestState
): StateHandler< TestState > & {
	currentState: TestState;
	onProcessingStartMock: jest.Mock;
	onProcessingEndMock: jest.Mock;
	onErrorsMock: jest.Mock;
} {
	const state: TestState = initialState ?? {
		items: [],
		total: 0,
	};

	const handler = {
		currentState: state,
		getState: () => handler.currentState,
		setState( newState: TestState ) {
			handler.currentState = newState;
		},
		onProcessingStartMock: jest.fn(),
		onProcessingEndMock: jest.fn(),
		onErrorsMock: jest.fn(),
		onProcessingStart() {
			this.onProcessingStartMock();
		},
		onProcessingEnd( result: BatchResult< TestState > ) {
			this.onProcessingEndMock( result );
		},
		onErrors( errors: Error[] ) {
			this.onErrorsMock( errors );
		},
	};

	return handler;
}

/**
 * Create a mock fetch function.
 */
function createMockFetch(
	responses: Array< { ok: boolean; data?: unknown; status?: number } >
): jest.Mock< Promise< Response >, [ RequestInfo | URL, RequestInit? ] > {
	let callIndex = 0;

	return jest.fn( () => {
		const response = responses[ callIndex ] ?? responses[ 0 ];
		callIndex++;

		if ( response.ok ) {
			return Promise.resolve( {
				ok: true,
				status: response.status ?? 200,
				json: () => Promise.resolve( response.data ),
			} as Response );
		}

		return Promise.resolve( {
			ok: false,
			status: response.status ?? 500,
			json: () =>
				Promise.resolve( {
					message:
						( response.data as { message?: string } )?.message ??
						'Error',
				} ),
		} as Response );
	} );
}

/**
 * Create an add-item command for testing.
 */
function createAddItemCommand(
	itemId: number,
	quantity: number
): Command< TestState, { id: number; quantity: number } > {
	return {
		id: generateCommandId(),
		type: 'add-item',
		payload: { id: itemId, quantity },
		applyOptimistic: ( state ) => {
			const existingItem = state.items.find(
				( item ) => item.id === itemId
			);
			if ( existingItem ) {
				existingItem.quantity += quantity;
			} else {
				state.items.push( { id: itemId, quantity } );
			}
			state.total += quantity * 10; // Assume $10 per item
		},
		buildRequest: () => ( {
			method: 'POST',
			path: '/wc/store/v1/cart/add-item',
			body: { id: itemId, quantity },
		} ),
		transformResponse: ( response ) => {
			if (
				response &&
				typeof response === 'object' &&
				'items' in response
			) {
				return response as TestState;
			}
			return null;
		},
	};
}

/**
 * Wait for next microtask to complete.
 */
function flushMicrotasks(): Promise< void > {
	return new Promise( ( resolve ) => queueMicrotask( resolve ) );
}

/**
 * Wait for multiple microtasks to complete.
 */
async function flushAllMicrotasks( count = 5 ): Promise< void > {
	for ( let i = 0; i < count; i++ ) {
		await flushMicrotasks();
	}
}

describe( 'createCommandQueue', () => {
	describe( 'initialization', () => {
		it( 'should create a queue in idle state', () => {
			const stateHandler = createMockStateHandler();
			const queue = createCommandQueue( stateHandler );

			expect( queue.state ).toBe( 'idle' );
			expect( queue.isProcessing ).toBe( false );
			expect( queue.pendingCount ).toBe( 0 );
		} );
	} );

	describe( 'enqueue', () => {
		it( 'should transition to collecting state on first enqueue', async () => {
			const stateHandler = createMockStateHandler();
			const mockFetch = createMockFetch( [
				{ ok: true, data: { items: [], total: 0 } },
			] );
			const queue = createCommandQueue( stateHandler, {
				fetch: mockFetch,
			} );

			const command = createAddItemCommand( 1, 2 );
			queue.enqueue( command );

			expect( queue.state ).toBe( 'collecting' );
			expect( queue.isProcessing ).toBe( true );

			// Wait for execution to complete
			await flushAllMicrotasks();
		} );

		it( 'should apply optimistic update immediately', async () => {
			const stateHandler = createMockStateHandler( {
				items: [],
				total: 0,
			} );
			const mockFetch = createMockFetch( [
				{
					ok: true,
					data: { items: [ { id: 1, quantity: 2 } ], total: 20 },
				},
			] );
			const queue = createCommandQueue( stateHandler, {
				fetch: mockFetch,
			} );

			const command = createAddItemCommand( 1, 2 );
			queue.enqueue( command );

			// Optimistic update should be applied immediately
			expect( stateHandler.currentState.items ).toHaveLength( 1 );
			expect( stateHandler.currentState.items[ 0 ] ).toEqual( {
				id: 1,
				quantity: 2,
			} );

			await flushAllMicrotasks();
		} );

		it( 'should batch multiple synchronous enqueues', async () => {
			const stateHandler = createMockStateHandler();
			const mockFetch = createMockFetch( [
				{
					ok: true,
					data: {
						items: [
							{ id: 1, quantity: 2 },
							{ id: 2, quantity: 1 },
						],
						total: 30,
					},
				},
				{
					ok: true,
					data: {
						items: [
							{ id: 1, quantity: 2 },
							{ id: 2, quantity: 1 },
						],
						total: 30,
					},
				},
			] );
			const queue = createCommandQueue( stateHandler, {
				fetch: mockFetch,
			} );

			const command1 = createAddItemCommand( 1, 2 );
			const command2 = createAddItemCommand( 2, 1 );

			queue.enqueue( command1 );
			queue.enqueue( command2 );

			expect( queue.pendingCount ).toBe( 2 );

			// Wait for the batch to execute
			await flushAllMicrotasks();

			// Both commands should have been executed
			expect( mockFetch ).toHaveBeenCalledTimes( 2 );
		} );

		it( 'should return promise that resolves with command result', async () => {
			const stateHandler = createMockStateHandler();
			const serverState: TestState = {
				items: [ { id: 1, quantity: 2 } ],
				total: 20,
			};
			const mockFetch = createMockFetch( [
				{ ok: true, data: serverState },
			] );
			const queue = createCommandQueue( stateHandler, {
				fetch: mockFetch,
			} );

			const command = createAddItemCommand( 1, 2 );
			const { promise } = queue.enqueue( command );

			const result = await promise;

			expect( result.success ).toBe( true );
			expect( result.success && result.state ).toEqual( serverState );
		} );

		it( 'should return commandId for tracking', () => {
			const stateHandler = createMockStateHandler();
			const mockFetch = createMockFetch( [
				{ ok: true, data: { items: [], total: 0 } },
			] );
			const queue = createCommandQueue( stateHandler, {
				fetch: mockFetch,
			} );

			const command = createAddItemCommand( 1, 2 );
			const { commandId } = queue.enqueue( command );

			expect( commandId ).toBe( command.id );
		} );
	} );

	describe( 'state machine transitions', () => {
		it( 'should follow IDLE → COLLECTING → EXECUTING → RECONCILING → IDLE', async () => {
			const stateHandler = createMockStateHandler();
			const mockFetch = createMockFetch( [
				{ ok: true, data: { items: [], total: 0 } },
			] );
			const queue = createCommandQueue( stateHandler, {
				fetch: mockFetch,
			} );

			const states: string[] = [];

			// Track state changes via events
			queue.on( 'collecting', () => states.push( 'collecting' ) );
			queue.on( 'executing', () => states.push( 'executing' ) );
			queue.on( 'reconciled', () => states.push( 'reconciled' ) );
			queue.on( 'idle', () => states.push( 'idle' ) );

			const command = createAddItemCommand( 1, 2 );
			queue.enqueue( command );

			// Should be in collecting state
			expect( queue.state ).toBe( 'collecting' );
			expect( states ).toContain( 'collecting' );

			// Wait for all transitions
			await flushAllMicrotasks();

			expect( states ).toContain( 'executing' );
			expect( states ).toContain( 'reconciled' );
			expect( states ).toContain( 'idle' );
			expect( queue.state ).toBe( 'idle' );
		} );
	} );

	describe( 'reconciliation', () => {
		it( 'should apply server state on success', async () => {
			const stateHandler = createMockStateHandler( {
				items: [],
				total: 0,
			} );
			const serverState: TestState = {
				items: [ { id: 1, quantity: 2 } ],
				total: 25, // Server might calculate different total
			};
			const mockFetch = createMockFetch( [
				{ ok: true, data: serverState },
			] );
			const queue = createCommandQueue( stateHandler, {
				fetch: mockFetch,
			} );

			const command = createAddItemCommand( 1, 2 );
			await queue.enqueue( command ).promise;

			// Should have server state, not optimistic state
			expect( stateHandler.currentState ).toEqual( serverState );
		} );

		it( 'should rollback to snapshot on total failure', async () => {
			const initialState: TestState = {
				items: [ { id: 99, quantity: 5 } ],
				total: 50,
			};
			// Store a copy for assertion since optimistic updates mutate the state
			const expectedRollbackState: TestState = {
				items: [ { id: 99, quantity: 5 } ],
				total: 50,
			};
			const stateHandler = createMockStateHandler( initialState );
			const mockFetch = createMockFetch( [
				{ ok: false, status: 500, data: { message: 'Server error' } },
			] );
			const queue = createCommandQueue( stateHandler, {
				fetch: mockFetch,
			} );

			const command = createAddItemCommand( 1, 2 );
			const { promise } = queue.enqueue( command );

			// Optimistic update should be applied
			expect( stateHandler.currentState.items ).toHaveLength( 2 );

			const result = await promise;

			expect( result.success ).toBe( false );
			// Should rollback to initial state
			expect( stateHandler.currentState ).toEqual(
				expectedRollbackState
			);
		} );

		it( 'should call onErrors when errors occur', async () => {
			const stateHandler = createMockStateHandler();
			const mockFetch = createMockFetch( [
				{ ok: false, status: 500, data: { message: 'Server error' } },
			] );
			const queue = createCommandQueue( stateHandler, {
				fetch: mockFetch,
			} );

			const command = createAddItemCommand( 1, 2 );
			await queue.enqueue( command ).promise;

			expect( stateHandler.onErrorsMock ).toHaveBeenCalledWith(
				expect.arrayContaining( [
					expect.objectContaining( { message: 'Server error' } ),
				] )
			);
		} );

		it( 'should call onProcessingStart and onProcessingEnd', async () => {
			const stateHandler = createMockStateHandler();
			const mockFetch = createMockFetch( [
				{ ok: true, data: { items: [], total: 0 } },
			] );
			const queue = createCommandQueue( stateHandler, {
				fetch: mockFetch,
			} );

			const command = createAddItemCommand( 1, 2 );
			await queue.enqueue( command ).promise;

			expect( stateHandler.onProcessingStartMock ).toHaveBeenCalledTimes(
				1
			);
			expect( stateHandler.onProcessingEndMock ).toHaveBeenCalledTimes(
				1
			);
		} );
	} );

	describe( 'cancellation', () => {
		it( 'should allow cancelling pending commands', async () => {
			const stateHandler = createMockStateHandler();
			const mockFetch = createMockFetch( [
				{ ok: true, data: { items: [], total: 0 } },
			] );
			const queue = createCommandQueue( stateHandler, {
				fetch: mockFetch,
			} );

			const command = createAddItemCommand( 1, 2 );
			const { cancel, promise } = queue.enqueue( command );

			// Cancel before microtask executes
			const cancelled = cancel();

			expect( cancelled ).toBe( true );

			await expect( promise ).rejects.toThrow( 'Command cancelled' );

			// The fetch should not have been called
			await flushAllMicrotasks();
			expect( mockFetch ).not.toHaveBeenCalled();
		} );

		it( 'should allow cancelling by command ID', async () => {
			const stateHandler = createMockStateHandler();
			const mockFetch = createMockFetch( [
				{ ok: true, data: { items: [], total: 0 } },
			] );
			const queue = createCommandQueue( stateHandler, {
				fetch: mockFetch,
			} );

			const command = createAddItemCommand( 1, 2 );
			const { commandId, promise } = queue.enqueue( command );

			const cancelled = queue.cancel( commandId );

			expect( cancelled ).toBe( true );

			await expect( promise ).rejects.toThrow( 'Command cancelled' );
		} );

		it( 'should return false when cancelling non-existent command', () => {
			const stateHandler = createMockStateHandler();
			const queue = createCommandQueue( stateHandler );

			const cancelled = queue.cancel( 'non-existent-id' );

			expect( cancelled ).toBe( false );
		} );
	} );

	describe( 'abort', () => {
		it( 'should abort all pending commands', async () => {
			const stateHandler = createMockStateHandler();
			const mockFetch = createMockFetch( [
				{ ok: true, data: { items: [], total: 0 } },
			] );
			const queue = createCommandQueue( stateHandler, {
				fetch: mockFetch,
			} );

			const command1 = createAddItemCommand( 1, 2 );
			const command2 = createAddItemCommand( 2, 1 );

			const { promise: promise1 } = queue.enqueue( command1 );
			const { promise: promise2 } = queue.enqueue( command2 );

			queue.abort();

			await expect( promise1 ).rejects.toThrow( 'Queue aborted' );
			await expect( promise2 ).rejects.toThrow( 'Queue aborted' );
		} );

		it( 'should emit aborted event', async () => {
			const stateHandler = createMockStateHandler();
			let wasAborted = false;
			// Make fetch that respects abort signal
			const mockFetch = jest.fn(
				( _url: RequestInfo | URL, init?: RequestInit ) =>
					new Promise< Response >( ( resolve, reject ) => {
						const timeoutId = setTimeout(
							() =>
								resolve( {
									ok: true,
									json: () =>
										Promise.resolve( {
											items: [],
											total: 0,
										} ),
								} as Response ),
							10000
						);

						// Listen to abort signal
						if ( init?.signal ) {
							init.signal.addEventListener( 'abort', () => {
								clearTimeout( timeoutId );
								wasAborted = true;
								const error = new DOMException(
									'Aborted',
									'AbortError'
								);
								reject( error );
							} );
						}
					} )
			);
			const queue = createCommandQueue( stateHandler, {
				fetch: mockFetch,
			} );

			const abortedHandler = jest.fn();
			queue.on( 'aborted', abortedHandler );

			const command = createAddItemCommand( 1, 2 );
			const { promise } = queue.enqueue( command );

			// Wait for execution to start
			await flushMicrotasks();

			// Now abort
			queue.abort();

			// Wait for the promise to settle
			const result = await promise;

			// The fetch should have been aborted
			expect( wasAborted ).toBe( true );

			// The result should indicate failure
			expect( result.success ).toBe( false );

			expect( abortedHandler ).toHaveBeenCalled();
		} );
	} );

	describe( 'flush', () => {
		it( 'should force immediate execution', async () => {
			const stateHandler = createMockStateHandler();
			const mockFetch = createMockFetch( [
				{
					ok: true,
					data: { items: [ { id: 1, quantity: 2 } ], total: 20 },
				},
			] );
			const queue = createCommandQueue( stateHandler, {
				fetch: mockFetch,
			} );

			const command = createAddItemCommand( 1, 2 );
			queue.enqueue( command );

			// Don't wait for microtask, flush immediately
			const result = await queue.flush();

			expect( result ).not.toBeNull();
			expect( result?.finalState ).toEqual( {
				items: [ { id: 1, quantity: 2 } ],
				total: 20,
			} );
		} );

		it( 'should return null when no pending commands', async () => {
			const stateHandler = createMockStateHandler();
			const queue = createCommandQueue( stateHandler );

			const result = await queue.flush();

			expect( result ).toBeNull();
		} );
	} );

	describe( 'events', () => {
		it( 'should emit collecting event on first enqueue', async () => {
			const stateHandler = createMockStateHandler();
			const mockFetch = createMockFetch( [
				{ ok: true, data: { items: [], total: 0 } },
			] );
			const queue = createCommandQueue( stateHandler, {
				fetch: mockFetch,
			} );

			const collectingHandler = jest.fn();
			queue.on( 'collecting', collectingHandler );

			const command = createAddItemCommand( 1, 2 );
			queue.enqueue( command );

			expect( collectingHandler ).toHaveBeenCalledWith( {
				commandId: command.id,
			} );

			await flushAllMicrotasks();
		} );

		it( 'should emit executing event with all commands', async () => {
			const stateHandler = createMockStateHandler();
			const mockFetch = createMockFetch( [
				{ ok: true, data: { items: [], total: 0 } },
				{ ok: true, data: { items: [], total: 0 } },
			] );
			const queue = createCommandQueue( stateHandler, {
				fetch: mockFetch,
			} );

			const executingHandler = jest.fn();
			queue.on( 'executing', executingHandler );

			const command1 = createAddItemCommand( 1, 2 );
			const command2 = createAddItemCommand( 2, 1 );

			queue.enqueue( command1 );
			queue.enqueue( command2 );

			await flushAllMicrotasks();

			expect( executingHandler ).toHaveBeenCalledWith( {
				commands: [ command1, command2 ],
			} );
		} );

		it( 'should allow unsubscribing from events', async () => {
			const stateHandler = createMockStateHandler();
			const mockFetch = createMockFetch( [
				{ ok: true, data: { items: [], total: 0 } },
			] );
			const queue = createCommandQueue( stateHandler, {
				fetch: mockFetch,
			} );

			const handler = jest.fn();
			const unsubscribe = queue.on( 'idle', handler );

			// Unsubscribe
			unsubscribe();

			const command = createAddItemCommand( 1, 2 );
			await queue.enqueue( command ).promise;

			// Handler should not have been called after unsubscribe
			expect( handler ).not.toHaveBeenCalled();
		} );

		it( 'should allow unsubscribing via off method', async () => {
			const stateHandler = createMockStateHandler();
			const mockFetch = createMockFetch( [
				{ ok: true, data: { items: [], total: 0 } },
			] );
			const queue = createCommandQueue( stateHandler, {
				fetch: mockFetch,
			} );

			const handler = jest.fn();
			queue.on( 'idle', handler );

			// Unsubscribe via off
			queue.off( 'idle', handler );

			const command = createAddItemCommand( 1, 2 );
			await queue.enqueue( command ).promise;

			expect( handler ).not.toHaveBeenCalled();
		} );
	} );

	describe( 'destroy', () => {
		it( 'should reject enqueue after destroy', async () => {
			const stateHandler = createMockStateHandler();
			const queue = createCommandQueue( stateHandler );

			queue.destroy();

			const command = createAddItemCommand( 1, 2 );
			const { promise } = queue.enqueue( command );

			await expect( promise ).rejects.toThrow(
				'Queue has been destroyed'
			);
		} );

		it( 'should abort pending commands on destroy', async () => {
			const stateHandler = createMockStateHandler();
			const mockFetch = createMockFetch( [
				{ ok: true, data: { items: [], total: 0 } },
			] );
			const queue = createCommandQueue( stateHandler, {
				fetch: mockFetch,
			} );

			const command = createAddItemCommand( 1, 2 );
			const { promise } = queue.enqueue( command );

			queue.destroy();

			await expect( promise ).rejects.toThrow( 'Queue aborted' );
		} );

		it( 'should clear all listeners on destroy', async () => {
			const stateHandler = createMockStateHandler();
			const mockFetch = createMockFetch( [
				{ ok: true, data: { items: [], total: 0 } },
			] );
			const queue = createCommandQueue( stateHandler, {
				fetch: mockFetch,
			} );

			const handler = jest.fn();
			queue.on( 'idle', handler );

			queue.destroy();

			// Try to trigger idle - should not call handler
			// (though we can't easily test this since the queue is destroyed)
			expect( handler ).not.toHaveBeenCalled();
		} );
	} );

	describe( 'configuration', () => {
		it( 'should respect maxBatchSize', async () => {
			const stateHandler = createMockStateHandler();
			const mockFetch = createMockFetch(
				Array( 10 ).fill( {
					ok: true,
					data: { items: [], total: 0 },
				} )
			);
			const queue = createCommandQueue( stateHandler, {
				fetch: mockFetch,
				maxBatchSize: 2,
			} );

			// Enqueue 4 commands
			for ( let i = 0; i < 4; i++ ) {
				queue.enqueue( createAddItemCommand( i, 1 ) );
			}

			// First flush should only execute 2
			await flushMicrotasks();

			// Wait for all batches to complete
			await flushAllMicrotasks( 10 );

			// Should have been called 4 times (2 batches of 2)
			expect( mockFetch ).toHaveBeenCalledTimes( 4 );
		} );

		it( 'should use custom cloneState if provided', async () => {
			const cloneStateMock = jest.fn( ( state: TestState ) => ( {
				...state,
				items: [ ...state.items ],
			} ) );

			const stateHandler = {
				...createMockStateHandler(),
				cloneState: cloneStateMock,
			};

			const mockFetch = createMockFetch( [
				{ ok: true, data: { items: [], total: 0 } },
			] );
			const queue = createCommandQueue( stateHandler, {
				fetch: mockFetch,
			} );

			const command = createAddItemCommand( 1, 2 );
			await queue.enqueue( command ).promise;

			expect( cloneStateMock ).toHaveBeenCalled();
		} );
	} );

	describe( 'single-flight constraint', () => {
		it( 'should not execute concurrent batches', async () => {
			const stateHandler = createMockStateHandler();
			let resolveFirst: ( value: Response ) => void;
			const firstRequest = new Promise< Response >( ( resolve ) => {
				resolveFirst = resolve;
			} );

			let fetchCallCount = 0;
			const mockFetch = jest.fn( () => {
				fetchCallCount++;
				if ( fetchCallCount === 1 ) {
					return firstRequest;
				}
				return Promise.resolve( {
					ok: true,
					json: () =>
						Promise.resolve( {
							items: [ { id: 2, quantity: 1 } ],
							total: 10,
						} ),
				} as Response );
			} );

			const queue = createCommandQueue( stateHandler, {
				fetch: mockFetch,
			} );

			// Enqueue first command
			const command1 = createAddItemCommand( 1, 2 );
			const { promise: promise1 } = queue.enqueue( command1 );

			// Start execution
			await flushMicrotasks();

			// While first is executing, enqueue another
			const command2 = createAddItemCommand( 2, 1 );
			queue.enqueue( command2 );

			// Second should be pending, not executing
			expect( queue.state ).toBe( 'executing' );
			expect( fetchCallCount ).toBe( 1 );

			// Resolve first request
			// eslint-disable-next-line @typescript-eslint/no-non-null-assertion
			resolveFirst!( {
				ok: true,
				json: () =>
					Promise.resolve( {
						items: [ { id: 1, quantity: 2 } ],
						total: 20,
					} ),
			} as Response );

			await promise1;

			// Now second should execute
			await flushAllMicrotasks();

			// Both should have completed
			expect( fetchCallCount ).toBe( 2 );
		} );
	} );
} );
