/**
 * Cart Mutation Queue Implementation
 *
 * A state-machine based queue for batching cart mutations. This ensures:
 * 1. Sequential processing - only one batch in-flight at a time
 * 2. Request isolation - all request data is captured at submission time
 * 3. Smart reconciliation - applies latest successful server state
 * 4. Optimistic updates - UI updates immediately before server response
 */

/**
 * External dependencies
 */
import type { Cart } from '@woocommerce/types';

/**
 * Internal dependencies
 */
import type {
	QueueState,
	MutationRequest,
	MutationResult,
	MutationOptions,
	QueuedMutation,
	StateManager,
	BatchResponse,
	CartMutationQueue,
} from './types';
import { extractCartFromResponse } from './types';

/**
 * The batch API endpoint path.
 */
const BATCH_ENDPOINT = '/wc/store/v1/batch';

/**
 * MutationQueue implements a state machine for batching cart mutations.
 *
 * State transitions:
 * IDLE → COLLECTING (on first submit)
 * COLLECTING → SENDING (on microtask completion)
 * SENDING → RECORDING (on response received)
 * RECORDING → RECONCILING (when all in-flight complete)
 * RECONCILING → IDLE (after cleanup) or COLLECTING (if pending queue has items)
 */
export class MutationQueue implements CartMutationQueue {
	/**
	 * Current state of the queue.
	 */
	private state: QueueState = 'idle';

	/**
	 * Mutations being collected in the current microtask tick.
	 */
	private currentBatch: QueuedMutation[] = [];

	/**
	 * Mutations queued while a batch is in-flight.
	 */
	private pendingQueue: QueuedMutation[] = [];

	/**
	 * Count of in-flight batch requests.
	 * Should always be 0 or 1 due to sequential processing.
	 */
	private inFlightCount = 0;

	/**
	 * Index counter for tracking batch order.
	 * Used to handle out-of-order responses correctly.
	 */
	private batchIndex = 0;

	/**
	 * Snapshot of cart state at the start of a batch cycle.
	 * Used for rollback on total failure.
	 */
	private snapshot: Cart | null = null;

	/**
	 * The most recent successfully returned cart state.
	 * Takes precedence over snapshot during reconciliation.
	 */
	private lastServerState: Cart | null = null;

	/**
	 * Index of the batch that produced lastServerState.
	 * Prevents older responses from overwriting newer state.
	 */
	private lastServerStateIndex = -1;

	/**
	 * Errors accumulated during the batch cycle.
	 * Displayed to user during reconciliation.
	 */
	private accumulatedErrors: Error[] = [];

	/**
	 * Results for each mutation in the current batch.
	 * Used to fire onSettled callbacks with correct results.
	 */
	private batchResults: Map< string, MutationResult > = new Map();

	/**
	 * Adapter for interacting with the cart store.
	 */
	private stateManager: StateManager;

	/**
	 * Subscribers to processing state changes.
	 */
	private listeners = new Set< ( processing: boolean ) => void >();

	/**
	 * Create a new MutationQueue.
	 *
	 * @param stateManager Adapter for cart store operations.
	 */
	constructor( stateManager: StateManager ) {
		this.stateManager = stateManager;
	}

	/**
	 * Submit a mutation to the queue.
	 *
	 * The mutation's request data is captured immediately (deep cloned)
	 * to prevent external modifications after submission.
	 *
	 * @param mutation The mutation options.
	 * @return Promise that resolves with the mutation result.
	 */
	submit< T >(
		mutation: MutationOptions< T >
	): Promise< MutationResult< T > > {
		return new Promise( ( resolve, reject ) => {
			// Generate unique ID for this mutation instance
			const id = `${ mutation.key }-${ Date.now() }-${ Math.random()
				.toString( 36 )
				.slice( 2, 7 ) }`;

			// Capture and isolate request data immediately
			const request = mutation.mutate();
			const isolatedRequest: MutationRequest = {
				path: request.path,
				method: request.method,
				// Deep clone body to prevent external mutations
				body:
					request.body !== undefined
						? JSON.parse( JSON.stringify( request.body ) )
						: undefined,
				headers: request.headers ? { ...request.headers } : undefined,
			};

			const queuedMutation: QueuedMutation< T > = {
				id,
				key: mutation.key,
				request: isolatedRequest,
				optimisticUpdate: mutation.optimisticUpdate,
				onSettled: mutation.onSettled,
				resolve: resolve as ( result: MutationResult< T > ) => void,
				reject,
			};

			// Apply optimistic update immediately
			if ( mutation.optimisticUpdate ) {
				this.stateManager.applyOptimisticUpdate(
					mutation.optimisticUpdate
				);
			}

			this.enqueue( queuedMutation );
		} );
	}

	/**
	 * Add a mutation to the appropriate queue based on current state.
	 * Uses type assertion since we store mutations of different types in the same array.
	 */
	private enqueue< T >( mutation: QueuedMutation< T > ): void {
		if ( this.state === 'idle' ) {
			// Take snapshot before first mutation
			this.snapshot = this.stateManager.getSnapshot();
			this.startCollecting();
		}

		// Cast is safe - we only read the generic parts during reconciliation
		// and the resolve function maintains its type internally
		const typedMutation = mutation as QueuedMutation< unknown >;

		if ( this.state === 'collecting' ) {
			// Add to current batch being collected
			this.currentBatch.push( typedMutation );
		} else {
			// Queue for next cycle (batch is in-flight or reconciling)
			this.pendingQueue.push( typedMutation );
		}
	}

	/**
	 * Start the collection phase.
	 * Uses queueMicrotask to collect all synchronous submissions.
	 */
	private startCollecting(): void {
		this.state = 'collecting';
		this.notifyListeners( true );

		// Collect all synchronous submissions in a single microtask
		queueMicrotask( () => {
			if ( this.state === 'collecting' ) {
				this.sendBatch();
			}
		} );
	}

	/**
	 * Send the collected batch to the server.
	 */
	private async sendBatch(): Promise< void > {
		if ( this.currentBatch.length === 0 ) {
			this.state = 'idle';
			this.notifyListeners( false );
			return;
		}

		this.state = 'sending';
		const batchIndex = this.batchIndex++;
		const batch = this.currentBatch;
		this.currentBatch = [];

		// Build the batch request payload
		const batchRequest: MutationRequest = {
			path: BATCH_ENDPOINT,
			method: 'POST',
			body: {
				requests: batch.map( ( m ) => ( {
					path: m.request.path,
					method: m.request.method,
					body: m.request.body,
					headers: m.request.headers,
				} ) ),
			},
		};

		this.inFlightCount++;

		try {
			const response =
				await this.stateManager.sendRequest< BatchResponse >(
					batchRequest
				);
			this.recordResponse( batch, batchIndex, response );
		} catch ( error ) {
			this.recordResponse( batch, batchIndex, {
				success: false,
				error:
					error instanceof Error
						? error
						: new Error( String( error ) ),
			} );
		}
	}

	/**
	 * Process the response from a batch request.
	 */
	private recordResponse(
		batch: QueuedMutation[],
		batchIndex: number,
		response: BatchResponse
	): void {
		this.state = 'recording';

		if ( ! response.success || ! response.data ) {
			// Total failure - all mutations in this batch failed
			const error = response.error || new Error( 'Batch request failed' );
			this.accumulatedErrors.push( error );

			batch.forEach( ( mutation ) => {
				const result: MutationResult = { success: false, error };
				this.batchResults.set( mutation.id, result );
				mutation.resolve( result );
			} );
		} else {
			// Process individual responses
			const responses = response.data.responses;
			let latestCartState: Cart | null = null;

			batch.forEach( ( mutation, index ) => {
				const itemResponse = responses[ index ];
				const success =
					itemResponse &&
					itemResponse.status >= 200 &&
					itemResponse.status < 300;

				if ( success ) {
					// Extract cart state from response if available
					const cartData = extractCartFromResponse(
						itemResponse.body
					);
					if ( cartData ) {
						// Use camelCaseKeys would be done in StateManager
						latestCartState = cartData as unknown as Cart;
					}

					const result: MutationResult = {
						success: true,
						data: itemResponse.body,
					};
					this.batchResults.set( mutation.id, result );
					mutation.resolve( result );
				} else {
					const errorMessage =
						itemResponse?.body &&
						typeof itemResponse.body === 'object' &&
						'message' in itemResponse.body
							? String(
									( itemResponse.body as { message: string } )
										.message
							  )
							: `Request failed with status ${
									itemResponse?.status || 'unknown'
							  }`;

					const error = new Error( errorMessage );
					this.accumulatedErrors.push( error );

					const result: MutationResult = {
						success: false,
						error,
					};
					this.batchResults.set( mutation.id, result );
					mutation.resolve( result );
				}
			} );

			// Update last server state if this batch is newer
			if ( latestCartState && batchIndex > this.lastServerStateIndex ) {
				this.lastServerState = latestCartState;
				this.lastServerStateIndex = batchIndex;
			}
		}

		this.inFlightCount--;

		if ( this.inFlightCount === 0 ) {
			this.reconcile( batch );
		}
	}

	/**
	 * Reconcile state and resolve all promises.
	 */
	private reconcile( batch: QueuedMutation[] ): void {
		this.state = 'reconciling';

		// Determine final state - prefer last successful server state, fallback to snapshot
		const finalState = this.lastServerState || this.snapshot;

		if ( finalState ) {
			this.stateManager.applyServerState( finalState );
		}

		// Fire all onSettled callbacks synchronously
		// This ensures legacy events fire before processing flag clears
		batch.forEach( ( mutation ) => {
			const result = this.batchResults.get( mutation.id );
			if ( mutation.onSettled && result ) {
				try {
					mutation.onSettled( result );
				} catch ( callbackError ) {
					// Don't let callback errors break the queue
					// eslint-disable-next-line no-console
					console.error(
						'Error in onSettled callback:',
						callbackError
					);
				}
			}
		} );

		// Show accumulated errors
		if ( this.accumulatedErrors.length > 0 ) {
			this.stateManager.showErrors( this.accumulatedErrors );
		}

		// Clear reconciliation state
		this.snapshot = null;
		this.lastServerState = null;
		this.lastServerStateIndex = -1;
		this.accumulatedErrors = [];
		this.batchResults.clear();

		// Process pending queue if any
		if ( this.pendingQueue.length > 0 ) {
			const pending = this.pendingQueue;
			this.pendingQueue = [];

			// Take new snapshot for next cycle
			this.snapshot = this.stateManager.getSnapshot();

			// Start collecting with pending items
			this.state = 'collecting';
			this.currentBatch = pending;

			queueMicrotask( () => {
				if ( this.state === 'collecting' ) {
					this.sendBatch();
				}
			} );
		} else {
			this.state = 'idle';
			this.notifyListeners( false );
		}
	}

	/**
	 * Check if any mutations are currently processing.
	 */
	isProcessing(): boolean {
		return this.state !== 'idle';
	}

	/**
	 * Get the current queue state (for debugging/testing).
	 */
	getState(): QueueState {
		return this.state;
	}

	/**
	 * Subscribe to processing state changes.
	 *
	 * @param listener Function called when processing state changes.
	 * @return Unsubscribe function.
	 */
	subscribe( listener: ( processing: boolean ) => void ): () => void {
		this.listeners.add( listener );
		return () => this.listeners.delete( listener );
	}

	/**
	 * Notify all listeners of processing state change.
	 */
	private notifyListeners( processing: boolean ): void {
		this.listeners.forEach( ( listener ) => {
			try {
				listener( processing );
			} catch ( error ) {
				// Don't let listener errors break the queue
				// eslint-disable-next-line no-console
				console.error( 'Error in processing listener:', error );
			}
		} );
	}
}
