/**
 * Mutation Queue - Order-dependent request queue for WooCommerce Store API
 *
 * This implements a state machine that:
 * 1. Takes ONE snapshot when first request arrives (start of processing cycle)
 * 2. Collects mutations within a single microtask tick
 * 3. Sends them to the server
 * 4. Handles out-of-order responses using request indexing
 * 5. Reconciles: apply last server state OR rollback to snapshot (if all failed)
 *
 * Queuing is invisible to callers - each submit() returns a promise
 * that resolves/rejects based on that request's success/failure.
 */

/**
 * State handler for managing snapshots and state application
 */
export type StateHandler< TState = unknown > = {
	/** Take a snapshot of state (called once at start of processing cycle) */
	takeSnapshot: () => TState;
	/** Rollback to snapshot (only if ALL requests failed) */
	rollback: ( snapshot: TState ) => void;
	/** Apply server state (called if ANY request succeeded) */
	applyServerState: ( serverState: TState ) => void;
};

/**
 * Result passed to onSettled callback
 */
export type SettledResult< TState = unknown > = {
	success: boolean;
	data?: TState;
	error?: Error;
};

/**
 * A mutation request to be queued
 */
export type MutationRequest< TState = unknown > = {
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
	/**
	 * Called synchronously after reconciliation, before isProcessing clears.
	 * Use this for side effects (events, notifications) that must complete
	 * before external code (like refreshCartItems) is allowed to run.
	 */
	onSettled?: ( result: SettledResult< TState > ) => void;
};

/**
 * Result of a single mutation
 */
export type MutationResult< TState = unknown > = {
	success: boolean;
	data?: TState;
	error?: Error;
	requestId: string;
};

/**
 * API response for a single request
 */
type BatchItemResponse = {
	status: number;
	body: unknown;
	headers?: Record< string, string >;
};

/**
 * Configuration for the mutation queue
 */
export type MutationQueueConfig< TState = unknown > = {
	/** URL for the batch endpoint */
	endpoint: string;
	/** Function to get auth headers (nonce, etc.) */
	getHeaders: () => Record< string, string >;
	/** State handler for snapshots and reconciliation */
	stateHandler: StateHandler< TState >;
	/** Extract server state from a successful response body */
	extractServerState?: ( body: unknown ) => TState;
};

/**
 * Internal state of the queue
 */
type QueueState =
	| 'idle'
	| 'collecting'
	| 'sending'
	| 'recording'
	| 'reconciling';

/**
 * Tracked request with its promise resolvers
 */
type TrackedRequest< TState = unknown > = {
	id: string;
	request: MutationRequest< TState >;
	resolve: ( result: MutationResult ) => void;
	reject: ( error: Error ) => void;
	requestIndex: number;
};

/**
 * In-flight request group information
 */
type InFlightGroup = {
	groupIndex: number;
	requestIds: string[];
};

/**
 * Creates a mutation queue instance
 */
export function createMutationQueue< TState >(
	config: MutationQueueConfig< TState >
) {
	const {
		endpoint,
		getHeaders,
		stateHandler,
		extractServerState = ( body ) => body as TState,
	} = config;

	// State machine state
	let currentState: QueueState = 'idle';

	// Single snapshot for the entire processing cycle (taken at IDLE → COLLECTING)
	let snapshot: TState | null = null;

	// All tracked requests for the current cycle
	const trackedRequests: Map< string, TrackedRequest< TState > > = new Map();

	// Requests waiting to be sent (collected this tick)
	let pendingRequestIds: string[] = [];

	// Request group indexing for ordering responses
	let nextGroupIndex = 0;
	let lastStoredIndex = -1;
	let lastServerState: TState | null = null;

	// In-flight request groups
	const inFlightGroups: Map< number, InFlightGroup > = new Map();

	// Accumulated errors (keyed by request id)
	const accumulatedErrors: Map< string, Error > = new Map();

	// Flag to track if we've scheduled a microtask
	let microtaskScheduled = false;

	// Flag to track if we're actively processing requests
	let isProcessing = false;

	/**
	 * Transition to a new state.
	 */
	function transitionTo( newState: QueueState ) {
		currentState = newState;
	}

	/**
	 * Final reconciliation (RECONCILIATION phase).
	 */
	function reconcile() {
		transitionTo( 'reconciling' );

		// Apply final state.
		if ( lastServerState !== null ) {
			// ANY request succeeded → overwrite with last server state.
			stateHandler.applyServerState( lastServerState );
		} else if ( snapshot !== null ) {
			// ALL total failures → rollback to snapshot.
			stateHandler.rollback( snapshot );
		}

		// Run onSettled callbacks synchronously BEFORE clearing isProcessing
		// This allows side effects (like firing events) to complete while
		// external code (like refreshCartItems) is still blocked.
		trackedRequests.forEach( ( tracked ) => {
			const error = accumulatedErrors.get( tracked.id );
			tracked.request.onSettled?.( {
				success: ! error,
				...( lastServerState !== null && { data: lastServerState } ),
				...( error && { error } ),
			} );
		} );

		// Clear processing flag synchronously after onSettled callbacks.
		isProcessing = false;

		// Now resolve/reject promises (generators will resume async).
		trackedRequests.forEach( ( tracked ) => {
			const error = accumulatedErrors.get( tracked.id );

			if ( error ) {
				tracked.reject( error );
			} else {
				tracked.resolve( {
					success: true,
					...( lastServerState !== null && {
						data: lastServerState,
					} ),
					requestId: tracked.id,
				} );
			}
		} );

		// Clear everything for next cycle.
		snapshot = null;
		lastServerState = null;
		lastStoredIndex = -1;
		nextGroupIndex = 0;
		accumulatedErrors.clear();
		trackedRequests.clear();

		// Transition to idle.
		transitionTo( 'idle' );
	}

	/**
	 * Check if we should reconcile.
	 */
	function checkAndReconcile() {
		// If still in-flight groups, wait.
		if ( inFlightGroups.size > 0 ) {
			transitionTo( 'sending' );
			return;
		}

		// If new requests came in, schedule them.
		if ( pendingRequestIds.length > 0 ) {
			if ( ! microtaskScheduled ) {
				microtaskScheduled = true;
				// eslint-disable-next-line @typescript-eslint/no-use-before-define -- processRequests is defined below, but called async via queueMicrotask
				queueMicrotask( () => processRequests() );
			}
			transitionTo( 'collecting' );
			return;
		}

		reconcile();
	}

	/**
	 * Handle total failure of a request group (network error, non-200 response).
	 */
	function handleTotalFailure( groupIndex: number, error: Error ) {
		const group = inFlightGroups.get( groupIndex );
		if ( ! group ) return;

		// Add errors for all requests in this group.
		group.requestIds.forEach( ( id ) => {
			accumulatedErrors.set( id, error );
		} );

		// Remove from in-flight.
		inFlightGroups.delete( groupIndex );

		checkAndReconcile();
	}

	/**
	 * Handle response (RESPONSE_RECORDING phase).
	 */
	function handleResponse(
		groupIndex: number,
		responses: BatchItemResponse[]
	) {
		const group = inFlightGroups.get( groupIndex );
		if ( ! group ) return;

		let latestServerState: TState | null = null;

		// Process each response.
		responses.forEach( ( itemResponse, index ) => {
			const requestId = group.requestIds[ index ];
			if ( ! requestId ) return;

			const isSuccess =
				itemResponse.status >= 200 && itemResponse.status < 300;

			if ( isSuccess ) {
				// Extract server state from successful response.
				latestServerState = extractServerState( itemResponse.body );
			} else {
				// Extract error.
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

		// Store server state if this group is newer than what we have.
		if ( latestServerState !== null && groupIndex > lastStoredIndex ) {
			lastServerState = latestServerState;
			lastStoredIndex = groupIndex;
		}

		// Remove from in-flight.
		inFlightGroups.delete( groupIndex );

		checkAndReconcile();
	}

	/**
	 * Process pending requests after the microtask tick completes.
	 */
	async function processRequests() {
		microtaskScheduled = false;

		if ( pendingRequestIds.length === 0 ) {
			if ( inFlightGroups.size === 0 ) {
				transitionTo( 'idle' );
			}
			return;
		}

		// Assign group index.
		const groupIndex = nextGroupIndex++;
		const requestIdsToSend = [ ...pendingRequestIds ];

		// Update tracked requests with their group index.
		requestIdsToSend.forEach( ( id ) => {
			const tracked = trackedRequests.get( id );
			if ( tracked ) {
				tracked.requestIndex = groupIndex;
			}
		} );

		// Store this group as in-flight.
		inFlightGroups.set( groupIndex, {
			groupIndex,
			requestIds: requestIdsToSend,
		} );

		// Clear pending for next collection.
		pendingRequestIds = [];

		// Transition to SENDING.
		transitionTo( 'sending' );

		try {
			// Build the request - each inner request needs headers too.
			const requestHeaders = getHeaders();
			const requests = requestIdsToSend
				.map( ( id ) => {
					const tracked = trackedRequests.get( id );
					if ( ! tracked ) {
						return null;
					}
					return {
						path: tracked.request.path,
						method: tracked.request.method,
						headers: {
							...requestHeaders,
							'Content-Type': 'application/json',
						},
						body: tracked.request.body,
					};
				} )
				.filter( Boolean );

			// Send the request.
			const response = await fetch( endpoint, {
				method: 'POST',
				cache: 'no-store',
				headers: {
					'Content-Type': 'application/json',
					...requestHeaders,
				},
				body: JSON.stringify( { requests } ),
			} );

			transitionTo( 'recording' );

			if ( ! response.ok ) {
				handleTotalFailure(
					groupIndex,
					new Error( `Request failed: ${ response.status }` )
				);
			} else {
				const json = await response.json();
				handleResponse( groupIndex, json.responses || [] );
			}
		} catch ( error ) {
			transitionTo( 'recording' );
			handleTotalFailure(
				groupIndex,
				error instanceof Error ? error : new Error( String( error ) )
			);
		}
	}

	/**
	 * Submit a mutation request.
	 *
	 * Returns a promise that resolves when processing completes.
	 * Queuing is invisible to the caller.
	 */
	function submit(
		request: MutationRequest< TState >
	): Promise< MutationResult< TState > > {
		return new Promise( ( resolve, reject ) => {
			// If idle, take snapshot and transition to collecting.
			if ( currentState === 'idle' ) {
				snapshot = stateHandler.takeSnapshot();
				isProcessing = true;
				transitionTo( 'collecting' );
			}

			// Apply optimistic update immediately.
			if ( request.applyOptimistic ) {
				request.applyOptimistic();
			}

			// Track this request.
			const tracked: TrackedRequest< TState > = {
				id: request.id,
				request,
				resolve: resolve as ( result: MutationResult ) => void,
				reject,
				requestIndex: -1,
			};

			trackedRequests.set( request.id, tracked );
			pendingRequestIds.push( request.id );

			// Schedule microtask if not already scheduled.
			if ( ! microtaskScheduled ) {
				microtaskScheduled = true;
				queueMicrotask( () => processRequests() );
			}
		} );
	}

	/**
	 * Get current queue status.
	 */
	function getStatus() {
		return {
			isProcessing,
			pendingCount: pendingRequestIds.length,
		};
	}

	return {
		submit,
		getStatus,
	};
}

/**
 * Type for the queue instance.
 */
export type MutationQueue< TState = unknown > = ReturnType<
	typeof createMutationQueue< TState >
>;
