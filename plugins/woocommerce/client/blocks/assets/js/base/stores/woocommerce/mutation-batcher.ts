/**
 * Mutation Queue - Microtick-based request batcher for WooCommerce Store API
 *
 * Collects mutation requests within a single microtask tick and sends them
 * as one batch request. Only one batch is in-flight at a time; requests that
 * arrive while a batch is in-flight are queued for the next batch.
 *
 * Reconciliation after all batches complete:
 * - If ANY request succeeded → commit the last successful server state
 * - If ALL requests failed → rollback to the pre-cycle snapshot
 *
 * Each submit() returns a promise that resolves/rejects based on that
 * individual request's success or failure within the batch.
 */

export type MutationRequest< TState = unknown > = {
	path: string;
	method: 'POST' | 'PUT' | 'DELETE' | 'PATCH';
	body?: unknown;
	applyOptimistic?: () => void;
	/**
	 * Called synchronously after reconciliation, before isProcessing clears.
	 * Use for side effects that must complete before external code
	 * (like refreshCartItems) is allowed to run.
	 */
	onSettled?: ( result: MutationResult< TState > ) => void;
};

export type MutationResult< TState = unknown > = {
	success: boolean;
	data?: TState;
	error?: Error;
};

type BatchItemResponse = {
	status: number;
	body: unknown;
	headers?: Record< string, string >;
};

export type MutationQueueConfig< TState = unknown > = {
	endpoint: string;
	getHeaders: () => Record< string, string >;
	takeSnapshot: () => TState;
	rollback: ( snapshot: TState ) => void;
	commit: ( serverState: TState ) => void;
};

type TrackedRequest< TState = unknown > = {
	id: string;
	request: MutationRequest< TState >;
	resolve: ( result: MutationResult ) => void;
	reject: ( error: Error ) => void;
};

export function createMutationQueue< TState >(
	config: MutationQueueConfig< TState >
) {
	const { endpoint, getHeaders, takeSnapshot, rollback, commit } = config;

	// Snapshot taken once at the start of each processing cycle.
	let snapshot: TState | null = null;

	// All tracked requests for the current cycle.
	const trackedRequests: Map< string, TrackedRequest< TState > > = new Map();

	// Requests collected this tick, waiting to be sent.
	let pendingIds: string[] = [];

	// The single in-flight batch (null when idle).
	let inFlightIds: string[] | null = null;

	// The last successful server state seen in this cycle.
	let lastServerState: TState | null = null;

	// Per-request errors accumulated across all batches in the cycle.
	const errors: Map< string, Error > = new Map();

	let microtaskScheduled = false;
	let isProcessing = false;
	let idleResolvers: Array< () => void > = [];
	let nextId = 0;

	// reconcile - Commits server state (or rolls back on total failure), notifies callers, resets the cycle
	function reconcile() {
		if ( lastServerState !== null ) {
			commit( lastServerState );
		} else if ( snapshot !== null ) {
			rollback( snapshot );
		}

		// Run onSettled callbacks while isProcessing is still true.
		// This prevents refreshCartItems from running during these callbacks.
		trackedRequests.forEach( ( tracked ) => {
			const error = errors.get( tracked.id );
			tracked.request.onSettled?.( {
				success: ! error,
				...( lastServerState !== null && { data: lastServerState } ),
				...( error && { error } ),
			} );
		} );

		isProcessing = false;

		// Notify idle waiters.
		const resolvers = idleResolvers;
		idleResolvers = [];
		resolvers.forEach( ( r ) => r() );

		// Resolve/reject individual promises.
		trackedRequests.forEach( ( tracked ) => {
			const error = errors.get( tracked.id );
			if ( error ) {
				tracked.reject( error );
			} else {
				tracked.resolve( {
					success: true,
					...( lastServerState !== null && {
						data: lastServerState,
					} ),
				} );
			}
		} );

		// Reset for next cycle.
		snapshot = null;
		lastServerState = null;
		errors.clear();
		trackedRequests.clear();
	}

	// onBatchComplete - If more requests queued during flight, sends them. Otherwise, reconciles.
	function onBatchComplete() {
		inFlightIds = null;

		// If new requests arrived while in-flight, send them.
		if ( pendingIds.length > 0 ) {
			// eslint-disable-next-line @typescript-eslint/no-use-before-define
			processRequests();
			return;
		}

		reconcile();
	}

	// handleBatchFailure - Marks all items in the batch as failed (network error or bad status).
	function handleBatchFailure( requestIds: string[], error: Error ) {
		for ( const id of requestIds ) {
			errors.set( id, error );
		}
		onBatchComplete();
	}

	// handleBatchResponse - Records per-item success/failure from the server response.
	function handleBatchResponse(
		requestIds: string[],
		responses: BatchItemResponse[]
	) {
		responses.forEach( ( itemResponse, index ) => {
			const requestId = requestIds[ index ];
			if ( ! requestId ) return;

			const isSuccess =
				itemResponse.status >= 200 && itemResponse.status < 300;

			if ( isSuccess ) {
				lastServerState = itemResponse.body as TState;
			} else {
				const errorBody = itemResponse.body as {
					message?: string;
					code?: string;
				};
				errors.set(
					requestId,
					Object.assign(
						new Error( errorBody?.message || 'Request failed' ),
						{ code: errorBody?.code || 'unknown_error' }
					)
				);
			}
		} );

		onBatchComplete();
	}

	// processRequests - Drains the pending queue into one batch and sends it
	async function processRequests() {
		microtaskScheduled = false;

		if ( pendingIds.length === 0 || inFlightIds !== null ) {
			return;
		}

		// Move pending requests to in-flight.
		inFlightIds = [ ...pendingIds ];
		pendingIds = [];

		const requestIds = inFlightIds;
		const requestHeaders = getHeaders();

		try {
			const requests = requestIds
				.map( ( id ) => {
					const tracked = trackedRequests.get( id );
					if ( ! tracked ) return null;
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

			const response = await fetch( endpoint, {
				method: 'POST',
				headers: {
					'Content-Type': 'application/json',
					...requestHeaders,
				},
				body: JSON.stringify( { requests } ),
			} );

			if ( ! response.ok ) {
				handleBatchFailure(
					requestIds,
					new Error( `Request failed: ${ response.status }` )
				);
			} else {
				const json = await response.json();
				handleBatchResponse( requestIds, json.responses || [] );
			}
		} catch ( error ) {
			handleBatchFailure(
				requestIds,
				error instanceof Error ? error : new Error( String( error ) )
			);
		}
	}

	// submit - Queues a request. First call in a cycle takes a snapshot.
	function submit(
		request: MutationRequest< TState >
	): Promise< MutationResult< TState > > {
		return new Promise( ( resolve, reject ) => {
			const id = String( nextId++ );

			// First request in a cycle: snapshot and start processing.
			if ( ! isProcessing ) {
				snapshot = takeSnapshot();
				isProcessing = true;
			}

			// Deep-clone the body at submission time so that later optimistic
			// updates from subsequent calls cannot alter the payload that
			// will be sent to the server.
			const clonedBody = request.body
				? JSON.parse( JSON.stringify( request.body ) )
				: undefined;

			if ( request.applyOptimistic ) {
				request.applyOptimistic();
			}

			trackedRequests.set( id, {
				id,
				request: { ...request, body: clonedBody },
				resolve: resolve as ( result: MutationResult ) => void,
				reject,
			} );

			pendingIds.push( id );

			if ( ! microtaskScheduled && inFlightIds === null ) {
				microtaskScheduled = true;
				queueMicrotask( () => processRequests() );
			}
		} );
	}

	function getStatus() {
		return {
			isProcessing,
			pendingCount: pendingIds.length,
		};
	}

	// Returns a promise that resolves when the current cycle completes. Resolves immediately if idle.
	function waitForIdle(): Promise< void > {
		if ( ! isProcessing ) {
			return Promise.resolve();
		}
		return new Promise( ( resolve ) => {
			idleResolvers.push( resolve );
		} );
	}

	return { submit, getStatus, waitForIdle };
}

export type MutationQueue< TState = unknown > = ReturnType<
	typeof createMutationQueue< TState >
>;
