/**
 * External dependencies
 */
import type { Cart } from '@woocommerce/types';

/**
 * Internal dependencies
 */
import type {
	BatchResponse,
	CartMutationQueue,
	MutationConfig,
	MutationRequest,
	MutationResult,
	ProcessingListener,
	QueuedMutation,
	QueueState,
	StateManager,
	BatchApiResponse,
} from './types';

/**
 * Generates a unique ID for a mutation instance.
 */
function generateMutationId( key: string ): string {
	return `${ key }-${ Date.now() }-${ Math.random()
		.toString( 36 )
		.slice( 2, 9 ) }`;
}

/**
 * Deep clones a value using JSON serialization.
 * Used to isolate request data at submission time.
 */
function deepClone< T >( value: T ): T {
	return JSON.parse( JSON.stringify( value ) );
}

/**
 * Cart Mutation Queue Implementation
 *
 * Manages cart mutations with request batching, sequential processing,
 * and smart state reconciliation.
 *
 * State Machine Flow:
 * IDLE → COLLECTING → SENDING → RECORDING → RECONCILING → IDLE
 *
 * Key features:
 * - Microtask batching: Groups synchronous submissions into a single batch
 * - Sequential processing: Only one batch in-flight at a time
 * - Request isolation: Deep clones request data at submission time
 * - Smart reconciliation: Uses latest successful server state, rolls back on total failure
 * - Event timing: Fires onSettled callbacks before clearing processing flag
 */
export class MutationQueue implements CartMutationQueue {
	/** Current state of the queue state machine. */
	private state: QueueState = 'idle';

	/** Mutations being collected for the current batch. */
	private currentBatch: QueuedMutation[] = [];

	/** Mutations waiting for the next batch cycle. */
	private pendingQueue: QueuedMutation[] = [];

	/** Counter for tracking in-flight batches. */
	private inFlightCount = 0;

	/** Monotonically increasing index for ordering batches. */
	private batchIndex = 0;

	/** Snapshot of cart state before current batch started. */
	private snapshot: Cart | null = null;

	/** Latest successful server state received. */
	private lastServerState: Cart | null = null;

	/** Index of the batch that provided lastServerState. */
	private lastServerStateIndex = -1;

	/** Errors accumulated during the current batch cycle. */
	private accumulatedErrors: Error[] = [];

	/** Results for each mutation in the current batch. */
	private batchResults: Map< string, MutationResult > = new Map();

	/** The state manager adapter. */
	private stateManager: StateManager;

	/** Subscribers to processing state changes. */
	private listeners = new Set< ProcessingListener >();

	constructor( stateManager: StateManager ) {
		this.stateManager = stateManager;
	}

	/**
	 * Submit a mutation to the queue.
	 *
	 * The mutation's request data is captured immediately (deep cloned)
	 * to ensure isolation from subsequent mutations to the source data.
	 *
	 * Optimistic updates are applied immediately for responsive UI.
	 *
	 * Returns a promise that resolves when the mutation settles.
	 */
	async submit< T >(
		mutation: MutationConfig< T >
	): Promise< MutationResult< T > > {
		return new Promise( ( resolve, reject ) => {
			const id = generateMutationId( mutation.key );

			// Capture request data immediately (ensures isolation)
			const rawRequest = mutation.mutate();
			const isolatedRequest: MutationRequest = {
				path: rawRequest.path,
				method: rawRequest.method,
				body: deepClone( rawRequest.body ),
				headers: rawRequest.headers
					? { ...rawRequest.headers }
					: undefined,
			};

			const queuedMutation: QueuedMutation< T > = {
				id,
				key: mutation.key,
				request: isolatedRequest,
				optimisticUpdate: mutation.optimisticUpdate,
				onSettled: mutation.onSettled,
				resolve,
				reject,
			};

			// Enqueue first (takes snapshot before any mutations)
			this.enqueue( queuedMutation as QueuedMutation );

			// Then apply optimistic update
			if ( mutation.optimisticUpdate ) {
				this.stateManager.applyOptimisticUpdate(
					mutation.optimisticUpdate
				);
			}
		} );
	}

	/**
	 * Check if any mutations are currently processing.
	 */
	isProcessing(): boolean {
		return this.state !== 'idle';
	}

	/**
	 * Subscribe to processing state changes.
	 * Returns an unsubscribe function.
	 */
	subscribe( listener: ProcessingListener ): () => void {
		this.listeners.add( listener );
		return () => this.listeners.delete( listener );
	}

	/**
	 * Enqueue a mutation for processing.
	 * Internal method that handles mutations with any generic type.
	 */
	private enqueue( mutation: QueuedMutation ): void {
		if ( this.state === 'idle' ) {
			// Take snapshot before first mutation
			this.snapshot = deepClone( this.stateManager.getSnapshot() );
			this.startCollecting();
		}

		if ( this.state === 'collecting' ) {
			// Add to current batch
			this.currentBatch.push( mutation );
		} else {
			// Queue for next cycle (batch is in-flight)
			this.pendingQueue.push( mutation );
		}
	}

	/**
	 * Start the collection phase.
	 * Uses queueMicrotask to group synchronous submissions.
	 */
	private startCollecting(): void {
		this.state = 'collecting';
		this.notifyListeners( true );

		// Collect all synchronous submissions within this microtask
		queueMicrotask( () => {
			if ( this.state === 'collecting' ) {
				this.sendBatch();
			}
		} );
	}

	/**
	 * Send the current batch to the server.
	 */
	private async sendBatch(): Promise< void > {
		if ( this.currentBatch.length === 0 ) {
			this.state = 'idle';
			this.notifyListeners( false );
			return;
		}

		this.state = 'sending';
		const batchIndex = this.batchIndex++;
		const batch = [ ...this.currentBatch ];
		this.currentBatch = [];

		// Build batch request
		const batchRequest: MutationRequest = {
			path: '/wc/store/v1/batch',
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
				await this.stateManager.sendRequest< BatchApiResponse >(
					batchRequest
				);
			this.recordResponse( batch, batchIndex, {
				success: true,
				data: response,
			} );
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
	 * Record responses from a completed batch request.
	 */
	private recordResponse(
		batch: QueuedMutation[],
		batchIndex: number,
		response: BatchResponse
	): void {
		this.state = 'recording';

		if ( ! response.success || ! response.data ) {
			// Total failure - all requests failed
			this.accumulatedErrors.push(
				response.error || new Error( 'Batch request failed' )
			);
			batch.forEach( ( mutation ) => {
				const result: MutationResult = {
					success: false,
					error: response.error,
				};
				this.batchResults.set( mutation.id, result );
			} );
		} else {
			// Process individual responses
			const responses = response.data.responses;
			let latestServerState: Cart | null = null;

			batch.forEach( ( mutation, index ) => {
				const itemResponse = responses[ index ];
				const success =
					itemResponse &&
					itemResponse.status >= 200 &&
					itemResponse.status < 300;

				if ( success ) {
					// Check if response body contains cart state
					const body = itemResponse.body as Record< string, unknown >;
					if ( body && typeof body === 'object' ) {
						// The response might be the cart directly or contain it
						if ( 'items' in body && 'totals' in body ) {
							latestServerState = body as Cart;
						}
					}

					const result: MutationResult = {
						success: true,
						data: itemResponse.body,
					};
					this.batchResults.set( mutation.id, result );
				} else {
					const status = itemResponse?.status || 'unknown';
					const errorMessage =
						itemResponse?.body &&
						typeof itemResponse.body === 'object' &&
						'message' in itemResponse.body
							? String(
									( itemResponse.body as { message: string } )
										.message
							  )
							: `Request failed with status ${ status }`;

					const error = new Error( errorMessage );
					this.accumulatedErrors.push( error );

					const result: MutationResult = {
						success: false,
						error,
					};
					this.batchResults.set( mutation.id, result );
				}
			} );

			// Update last server state if this batch is newer
			if ( latestServerState && batchIndex > this.lastServerStateIndex ) {
				this.lastServerState = latestServerState;
				this.lastServerStateIndex = batchIndex;
			}
		}

		this.inFlightCount--;

		if ( this.inFlightCount === 0 ) {
			this.reconcile( batch );
		}
	}

	/**
	 * Reconcile state after all batches complete.
	 * Applies final state, fires callbacks, and processes pending queue.
	 */
	private reconcile( batch: QueuedMutation[] ): void {
		this.state = 'reconciling';

		// Determine final state: use last successful server state, or rollback to snapshot
		const finalState = this.lastServerState || this.snapshot!;

		// Apply final state
		this.stateManager.applyServerState( finalState );

		// Fire all onSettled callbacks synchronously
		// This ensures legacy events fire before processing flag clears
		batch.forEach( ( mutation ) => {
			const result = this.batchResults.get( mutation.id ) || {
				success: false,
				error: new Error( 'No result recorded' ),
			};

			if ( mutation.onSettled ) {
				try {
					mutation.onSettled( result );
				} catch ( e ) {
					// Don't let callback errors break the queue
					// eslint-disable-next-line no-console
					console.error( 'Error in onSettled callback:', e );
				}
			}

			// Resolve the promise
			mutation.resolve( result );
		} );

		// Show accumulated errors
		if ( this.accumulatedErrors.length > 0 ) {
			this.stateManager.showErrors( [ ...this.accumulatedErrors ] );
		}

		// Clear reconciliation state
		this.snapshot = null;
		this.lastServerState = null;
		this.lastServerStateIndex = -1;
		this.accumulatedErrors = [];
		this.batchResults.clear();

		// Process pending queue
		if ( this.pendingQueue.length > 0 ) {
			const pending = [ ...this.pendingQueue ];
			this.pendingQueue = [];

			// Take new snapshot for next batch
			this.snapshot = deepClone( this.stateManager.getSnapshot() );
			this.state = 'collecting';

			// Add pending mutations to current batch
			pending.forEach( ( m ) => this.currentBatch.push( m ) );

			// Schedule batch send
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
	 * Notify all listeners of processing state change.
	 */
	private notifyListeners( processing: boolean ): void {
		this.listeners.forEach( ( listener ) => {
			try {
				listener( processing );
			} catch ( e ) {
				// Don't let listener errors break the queue
				// eslint-disable-next-line no-console
				console.error( 'Error in processing listener:', e );
			}
		} );
	}
}
