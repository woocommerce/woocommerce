/**
 * Mutation Batcher - Order-dependent batching system for WooCommerce Store API
 *
 * This implements a state machine that:
 * 1. Takes ONE snapshot when first request arrives (start of batch cycle)
 * 2. Collects mutations within a single microtask tick
 * 3. Sends them as a batch to the server
 * 4. Handles out-of-order responses using batch indexing
 * 5. Reconciles: apply last server state OR rollback to snapshot (if all failed)
 *
 * Batching is invisible to callers - each queueMutation() returns a promise
 * that resolves/rejects based on that request's success/failure.
 *
 * States: IDLE -> COLLECTING -> SENDING -> RECORDING -> RECONCILING -> IDLE
 */

/**
 * Global state handler for the batch cycle
 */
export type BatchStateHandler< TState = unknown > = {
	/** Take a snapshot of state (called once at start of batch cycle) */
	takeSnapshot: () => TState;
	/** Rollback to snapshot (only if ALL batches had total failures) */
	rollback: ( snapshot: TState ) => void;
	/** Apply server state (called if ANY batch succeeded) */
	applyServerState: ( serverState: TState ) => void;
};

/**
 * A mutation request to be batched
 */
export type MutationRequest = {
	/** Unique identifier for this request */
	id: string;
	/** The path for this request (e.g., /wc/store/v1/cart/add-item) */
	path: string;
	/** HTTP method */
	method: 'POST' | 'PUT' | 'DELETE' | 'PATCH';
	/** Request body */
	body?: unknown;
	/** Apply optimistic update immediately (modifies state in place) */
	applyOptimistic?: () => void;
};

/**
 * Result of a single mutation in the batch
 */
export type MutationResult< TState = unknown > = {
	success: boolean;
	data?: TState;
	error?: Error;
	requestId: string;
};

/**
 * API response for a single request in the batch
 */
export type BatchItemResponse = {
	status: number;
	body: unknown;
	headers?: Record< string, string >;
};

/**
 * Configuration for the mutation batcher
 */
export type MutationBatcherConfig< TState = unknown > = {
	/** Base URL for the batch endpoint */
	batchEndpoint: string;
	/** Function to get auth headers (nonce, etc.) */
	getHeaders: () => Record< string, string >;
	/** Global state handler for the batch cycle */
	stateHandler: BatchStateHandler< TState >;
	/** Extract server state from a successful response body */
	extractServerState?: ( body: unknown ) => TState;
	/** Optional callback for state changes (debugging) */
	onStateChange?: ( state: BatcherState ) => void;
};

/**
 * Internal state of the batcher
 */
export type BatcherState =
	| 'idle'
	| 'collecting'
	| 'sending'
	| 'recording'
	| 'reconciling';

/**
 * Tracked request with its promise resolvers
 */
type TrackedRequest = {
	id: string;
	request: MutationRequest;
	resolve: ( result: MutationResult ) => void;
	reject: ( error: Error ) => void;
	batchIndex: number;
};

/**
 * In-flight batch information
 */
type InFlightBatch = {
	batchIndex: number;
	requestIds: string[];
};

/**
 * Creates a mutation batcher instance
 */
export function createMutationBatcher< TState >(
	config: MutationBatcherConfig< TState >
) {
	const {
		batchEndpoint,
		getHeaders,
		stateHandler,
		extractServerState = ( body ) => body as TState,
		onStateChange,
	} = config;

	// State machine state
	let currentState: BatcherState = 'idle';

	// Single snapshot for the entire batch cycle (taken at IDLE → COLLECTING)
	let snapshot: TState | null = null;

	// All tracked requests for the current cycle
	const trackedRequests: Map< string, TrackedRequest > = new Map();

	// Requests waiting to be batched (collected this tick)
	let pendingRequestIds: string[] = [];

	// Batch indexing for ordering responses
	let nextBatchIndex = 0;
	let lastStoredIndex = -1;
	let lastServerState: TState | null = null;

	// In-flight batches
	const inFlightBatches: Map< number, InFlightBatch > = new Map();

	// Accumulated errors (keyed by request id)
	const accumulatedErrors: Map< string, Error > = new Map();

	// Flag to track if we've scheduled a microtask
	let microtaskScheduled = false;

	/**
	 * Transition to a new state
	 */
	function transitionTo( newState: BatcherState ) {
		currentState = newState;
		onStateChange?.( newState );
	}

	/**
	 * Process the batch after the microtask tick completes
	 */
	async function processBatch() {
		microtaskScheduled = false;

		if ( pendingRequestIds.length === 0 ) {
			if ( inFlightBatches.size === 0 ) {
				transitionTo( 'idle' );
			}
			return;
		}

		// Assign batch index
		const batchIndex = nextBatchIndex++;
		const requestIdsToSend = [ ...pendingRequestIds ];

		// Update tracked requests with their batch index
		requestIdsToSend.forEach( ( id ) => {
			const tracked = trackedRequests.get( id );
			if ( tracked ) {
				tracked.batchIndex = batchIndex;
			}
		} );

		// Store this batch as in-flight
		inFlightBatches.set( batchIndex, {
			batchIndex,
			requestIds: requestIdsToSend,
		} );

		// Clear pending for next collection
		pendingRequestIds = [];

		// Transition to SENDING
		transitionTo( 'sending' );

		try {
			// Build the batch request - each inner request needs headers too
			const requestHeaders = getHeaders();
			const batchRequests = requestIdsToSend.map( ( id ) => {
				const tracked = trackedRequests.get( id )!;
				return {
					path: tracked.request.path,
					method: tracked.request.method,
					headers: {
						...requestHeaders,
						'Content-Type': 'application/json',
					},
					body: tracked.request.body,
				};
			} );

			// Send the batch
			const response = await fetch( batchEndpoint, {
				method: 'POST',
				cache: 'no-store',
				headers: {
					'Content-Type': 'application/json',
					...requestHeaders,
				},
				body: JSON.stringify( { requests: batchRequests } ),
			} );

			transitionTo( 'recording' );

			if ( ! response.ok ) {
				handleTotalFailure(
					batchIndex,
					new Error( `Batch request failed: ${ response.status }` )
				);
			} else {
				const json = await response.json();
				handleBatchResponse( batchIndex, json.responses || [] );
			}
		} catch ( error ) {
			transitionTo( 'recording' );
			handleTotalFailure(
				batchIndex,
				error instanceof Error ? error : new Error( String( error ) )
			);
		}
	}

	/**
	 * Handle total failure of a batch (network error, non-200 response)
	 */
	function handleTotalFailure( batchIndex: number, error: Error ) {
		const batch = inFlightBatches.get( batchIndex );
		if ( ! batch ) return;

		// Add errors for all requests in this batch
		batch.requestIds.forEach( ( id ) => {
			accumulatedErrors.set( id, error );
		} );

		// Remove from in-flight
		inFlightBatches.delete( batchIndex );

		checkAndReconcile();
	}

	/**
	 * Handle batch response (RESPONSE_RECORDING phase)
	 */
	function handleBatchResponse(
		batchIndex: number,
		responses: BatchItemResponse[]
	) {
		const batch = inFlightBatches.get( batchIndex );
		if ( ! batch ) return;

		let latestServerState: TState | null = null;

		// Process each response
		responses.forEach( ( itemResponse, index ) => {
			const requestId = batch.requestIds[ index ];
			if ( ! requestId ) return;

			const isSuccess =
				itemResponse.status >= 200 && itemResponse.status < 300;

			if ( isSuccess ) {
				// Extract server state from successful response
				latestServerState = extractServerState( itemResponse.body );
			} else {
				// Extract error
				const errorBody = itemResponse.body as {
					message?: string;
					code?: string;
				};
				const error = Object.assign(
					new Error( errorBody?.message || 'Request failed' ),
					{ code: errorBody?.code || 'unknown_error' }
				);
				accumulatedErrors.set( requestId, error );
			}
		} );

		// Store server state if this batch is newer than what we have
		if ( latestServerState !== null && batchIndex > lastStoredIndex ) {
			lastServerState = latestServerState;
			lastStoredIndex = batchIndex;
		}

		// Remove from in-flight
		inFlightBatches.delete( batchIndex );

		checkAndReconcile();
	}

	/**
	 * Check if we should reconcile
	 */
	function checkAndReconcile() {
		// If still in-flight batches, wait
		if ( inFlightBatches.size > 0 ) {
			transitionTo( 'sending' );
			return;
		}

		// If new requests came in, schedule them
		if ( pendingRequestIds.length > 0 ) {
			if ( ! microtaskScheduled ) {
				microtaskScheduled = true;
				queueMicrotask( () => processBatch() );
			}
			transitionTo( 'collecting' );
			return;
		}

		reconcile();
	}

	/**
	 * Final reconciliation (RECONCILIATION phase)
	 */
	function reconcile() {
		transitionTo( 'reconciling' );

		const hasServerState = lastServerState !== null;

		// Apply final state
		if ( hasServerState ) {
			// ANY batch succeeded → overwrite with last server state
			stateHandler.applyServerState( lastServerState! );
		} else if ( snapshot !== null ) {
			// ALL total failures → rollback to snapshot
			stateHandler.rollback( snapshot );
		}

		// Resolve/reject all pending promises
		trackedRequests.forEach( ( tracked ) => {
			const error = accumulatedErrors.get( tracked.id );

			if ( error ) {
				tracked.reject( error );
			} else {
				tracked.resolve( {
					success: true,
					data: lastServerState ?? undefined,
					requestId: tracked.id,
				} );
			}
		} );

		// Clear everything for next cycle
		snapshot = null;
		lastServerState = null;
		lastStoredIndex = -1;
		nextBatchIndex = 0;
		accumulatedErrors.clear();
		trackedRequests.clear();

		transitionTo( 'idle' );
	}

	/**
	 * Queue a mutation request
	 *
	 * Returns a promise that resolves when the batch cycle completes.
	 * Batching is invisible to the caller.
	 */
	function queueMutation(
		request: MutationRequest
	): Promise< MutationResult< TState > > {
		return new Promise( ( resolve, reject ) => {
			// If idle, take snapshot and transition to collecting
			if ( currentState === 'idle' ) {
				snapshot = stateHandler.takeSnapshot();
				transitionTo( 'collecting' );
			}

			// Apply optimistic update immediately
			if ( request.applyOptimistic ) {
				request.applyOptimistic();
			}

			// Track this request
			const tracked: TrackedRequest = {
				id: request.id,
				request,
				resolve: resolve as ( result: MutationResult ) => void,
				reject,
				batchIndex: -1,
			};

			trackedRequests.set( request.id, tracked );
			pendingRequestIds.push( request.id );

			// Schedule microtask if not already scheduled
			if ( ! microtaskScheduled ) {
				microtaskScheduled = true;
				queueMicrotask( () => processBatch() );
			}
		} );
	}

	/**
	 * Get current batcher state (for debugging/testing)
	 */
	function getStatus() {
		return {
			state: currentState,
			pendingCount: pendingRequestIds.length,
			inFlightCount: inFlightBatches.size,
			trackedCount: trackedRequests.size,
			hasSnapshot: snapshot !== null,
			hasServerState: lastServerState !== null,
			nextBatchIndex,
			lastStoredIndex,
		};
	}

	return {
		queueMutation,
		getStatus,
	};
}

/**
 * Type for the batcher instance
 */
export type MutationBatcher< TState = unknown > = ReturnType<
	typeof createMutationBatcher< TState >
>;
