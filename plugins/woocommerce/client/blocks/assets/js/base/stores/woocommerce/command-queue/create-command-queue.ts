/**
 * Create Command Queue
 *
 * Implementation of the command queue for cart mutations.
 * Follows a state machine: IDLE → COLLECTING → EXECUTING → RECONCILING → IDLE
 */

/**
 * Internal dependencies
 */
import type {
	Command,
	CommandId,
	CommandQueue,
	CommandResult,
	BatchResult,
	StateHandler,
	QueueConfig,
	QueueState,
	QueueEvents,
	QueueEventListener,
	EnqueueResult,
} from './types';

/**
 * Default configuration values.
 */
const DEFAULT_CONFIG: Required< Omit< QueueConfig, 'nonce' | 'restUrl' > > & {
	nonce: string | undefined;
	restUrl: string | undefined;
} = {
	timeout: 30000,
	maxBatchSize: 25,
	restUrl: undefined,
	nonce: undefined,
	fetch: globalThis.fetch?.bind( globalThis ),
};

/**
 * Internal command wrapper with promise resolvers.
 */
interface PendingCommand< TState > {
	command: Command< TState >;
	resolve: ( result: CommandResult< TState > ) => void;
	reject: ( error: Error ) => void;
	cancelled: boolean;
}

/**
 * Creates a command queue for managing cart mutations.
 *
 * The queue collects commands within a single microtask, then executes them
 * as a batch. Only one batch can be in-flight at a time to prevent
 * server-side race conditions (WooCommerce API is not atomic).
 *
 * @param stateHandler - Handler for state management
 * @param config       - Configuration options
 * @return             The command queue instance
 */
export function createCommandQueue< TState >(
	stateHandler: StateHandler< TState >,
	config?: QueueConfig
): CommandQueue< TState > {
	// Merge config with defaults
	const resolvedConfig = { ...DEFAULT_CONFIG, ...config };

	// Internal state
	let queueState: QueueState = 'idle';
	let pendingCommands: PendingCommand< TState >[] = [];
	let executingCommands: PendingCommand< TState >[] = [];
	let snapshot: TState | null = null;
	let isFlushScheduled = false;
	let abortController: AbortController | null = null;
	let isDestroyed = false;

	// Event listeners
	const listeners = new Map<
		keyof QueueEvents< TState >,
		Set< QueueEventListener< TState, keyof QueueEvents< TState > > >
	>();

	/**
	 * Deep clone state using provided cloner or JSON.
	 */
	function cloneState( state: TState ): TState {
		if ( stateHandler.cloneState ) {
			return stateHandler.cloneState( state );
		}
		return JSON.parse( JSON.stringify( state ) );
	}

	/**
	 * Emit an event to all listeners.
	 */
	function emit< K extends keyof QueueEvents< TState > >(
		event: K,
		data: QueueEvents< TState >[ K ]
	): void {
		const eventListeners = listeners.get( event );
		if ( eventListeners ) {
			eventListeners.forEach( ( listener ) => {
				try {
					( listener as QueueEventListener< TState, K > )( data );
				} catch ( error ) {
					// eslint-disable-next-line no-console
					console.error(
						`Error in queue event listener for '${ event }':`,
						error
					);
				}
			} );
		}
	}

	/**
	 * Transition to a new state.
	 */
	function transitionTo( newState: QueueState ): void {
		queueState = newState;
	}

	/**
	 * Check if a value looks like valid state.
	 * This is a basic check - extenders should use transformResponse for proper validation.
	 */
	function isValidState( value: unknown ): boolean {
		return value !== null && typeof value === 'object';
	}

	/**
	 * Execute a single API request.
	 */
	async function executeRequest(
		request: ReturnType< Command< TState >[ 'buildRequest' ] >,
		signal: AbortSignal
	): Promise< unknown > {
		const baseUrl = resolvedConfig.restUrl ?? '/wp-json';
		const url = `${ baseUrl }${ request.path }`;

		const headers: Record< string, string > = {
			'Content-Type': 'application/json',
			...( request.headers ?? {} ),
		};

		if ( resolvedConfig.nonce ) {
			headers[ 'X-WP-Nonce' ] = resolvedConfig.nonce;
		}

		const fetchFn = resolvedConfig.fetch;
		const response = await fetchFn( url, {
			method: request.method,
			headers,
			body: request.body ? JSON.stringify( request.body ) : undefined,
			signal,
		} );

		if ( ! response.ok ) {
			const errorData = await response.json().catch( () => ( {} ) );
			const message =
				( errorData as { message?: string } ).message ??
				`HTTP error ${ response.status }`;
			throw new Error( message );
		}

		return response.json();
	}

	/**
	 * Execute a batch of commands against the API.
	 */
	async function executeBatch(
		commands: PendingCommand< TState >[],
		signal: AbortSignal
	): Promise< BatchResult< TState > > {
		const results = new Map< CommandId, CommandResult< TState > >();
		let finalState: TState | null = null;
		const errors: Error[] = [];

		// Build requests for all commands
		const requests = commands.map( ( pc ) => ( {
			id: pc.command.id,
			request: pc.command.buildRequest(),
		} ) );

		// For now, execute commands sequentially
		// TODO: In future, could batch into a single API call using WC batch endpoint
		for ( const { id, request } of requests ) {
			// Check if aborted
			if ( signal.aborted ) {
				throw new DOMException( 'Aborted', 'AbortError' );
			}

			try {
				const response = await executeRequest( request, signal );

				// Find the command to potentially transform the response
				const pc = commands.find( ( c ) => c.command.id === id );
				let state: TState | null = null;

				if ( pc?.command.transformResponse ) {
					state = pc.command.transformResponse( response );
				} else if ( isValidState( response ) ) {
					state = response as TState;
				}

				if ( state !== null ) {
					finalState = state;
					results.set( id, { success: true, state } );
				} else {
					results.set( id, {
						success: true,
						state: finalState ?? stateHandler.getState(),
					} );
				}
			} catch ( error ) {
				// Re-throw abort errors so they can be handled at the batch level
				if (
					error instanceof Error &&
					( error.name === 'AbortError' ||
						( error instanceof DOMException &&
							error.message === 'Aborted' ) )
				) {
					throw error;
				}

				const err =
					error instanceof Error
						? error
						: new Error( String( error ) );
				errors.push( err );
				results.set( id, { success: false, error: err } );
			}
		}

		return {
			results,
			finalState,
			hasErrors: errors.length > 0,
			errors,
		};
	}

	/**
	 * Reconcile state after batch execution.
	 */
	async function reconcile( result: BatchResult< TState > ): Promise< void > {
		transitionTo( 'reconciling' );

		// Determine final state
		let stateToApply: TState;

		if ( result.finalState !== null ) {
			// Use server state (source of truth)
			stateToApply = result.finalState;
		} else if ( snapshot !== null ) {
			// All failed, rollback to snapshot
			stateToApply = snapshot;
		} else {
			// Fallback to current state (shouldn't happen)
			stateToApply = stateHandler.getState();
		}

		// Apply the state
		stateHandler.setState( stateToApply );

		// Resolve all command promises
		for ( const pc of executingCommands ) {
			const commandResult = result.results.get( pc.command.id );
			if ( commandResult ) {
				pc.resolve( commandResult );
			} else {
				// Command wasn't in results (shouldn't happen)
				pc.resolve( { success: true, state: stateToApply } );
			}
		}

		// Emit reconciled event
		emit( 'reconciled', { state: stateToApply, result } );

		// Report errors
		if ( result.hasErrors && result.errors.length > 0 ) {
			stateHandler.onErrors?.( result.errors );
		}

		// Notify processing end
		stateHandler.onProcessingEnd?.( result );

		// Clear executing commands
		executingCommands = [];

		// Check if more commands are pending
		if ( pendingCommands.length > 0 ) {
			// Continue with next batch
			// eslint-disable-next-line @typescript-eslint/no-use-before-define
			void executeFlush();
		} else {
			// Return to idle
			snapshot = null;
			transitionTo( 'idle' );
			emit( 'idle', {} );
		}
	}

	/**
	 * Execute the pending batch.
	 */
	async function executeFlush(): Promise< BatchResult< TState > | null > {
		// Don't execute if already executing or no pending commands
		if ( queueState === 'executing' || pendingCommands.length === 0 ) {
			return null;
		}

		// Filter out cancelled commands
		pendingCommands = pendingCommands.filter( ( pc ) => ! pc.cancelled );

		if ( pendingCommands.length === 0 ) {
			transitionTo( 'idle' );
			emit( 'idle', {} );
			return null;
		}

		// Take snapshot before execution (only if we don't have one)
		if ( snapshot === null ) {
			snapshot = cloneState( stateHandler.getState() );
		}

		// Move pending to executing
		executingCommands = pendingCommands.slice(
			0,
			resolvedConfig.maxBatchSize
		);
		pendingCommands = pendingCommands.slice( resolvedConfig.maxBatchSize );

		// Transition to executing
		transitionTo( 'executing' );

		// Notify processing start
		stateHandler.onProcessingStart?.();

		// Emit executing event
		emit( 'executing', {
			commands: executingCommands.map( ( pc ) => pc.command ),
		} );

		// Create abort controller with timeout
		abortController = new AbortController();
		let isTimeoutAbort = false;

		const timeoutId = setTimeout( () => {
			if ( abortController ) {
				isTimeoutAbort = true;
				abortController.abort( new Error( 'Request timeout' ) );
			}
		}, resolvedConfig.timeout );

		let batchResult: BatchResult< TState >;

		try {
			// Execute the batch
			batchResult = await executeBatch(
				executingCommands,
				abortController.signal
			);
		} catch ( error ) {
			// Handle abort/timeout
			clearTimeout( timeoutId );

			const isAborted =
				error instanceof Error &&
				( error.name === 'AbortError' ||
					( error instanceof DOMException &&
						error.message === 'Aborted' ) );

			// Determine the error to use for results
			// If timeout triggered the abort, use the timeout error
			let effectiveError: Error;
			if ( isTimeoutAbort ) {
				effectiveError = new Error( 'Request timeout' );
			} else if ( error instanceof Error ) {
				effectiveError = error;
			} else {
				effectiveError = new Error( String( error ) );
			}

			if ( isTimeoutAbort ) {
				emit( 'timeout', {
					commands: executingCommands.map( ( pc ) => pc.command ),
				} );
			} else if ( isAborted ) {
				emit( 'aborted', {
					commands: executingCommands.map( ( pc ) => pc.command ),
				} );
			}

			// Create failure result
			batchResult = {
				results: new Map(
					executingCommands.map( ( pc ) => [
						pc.command.id,
						{
							success: false as const,
							error: effectiveError,
						},
					] )
				),
				finalState: null,
				hasErrors: true,
				errors: [ effectiveError ],
			};
		} finally {
			clearTimeout( timeoutId );
			abortController = null;
		}

		// Emit response event
		emit( 'response', { result: batchResult } );

		// Reconcile state
		await reconcile( batchResult );

		return batchResult;
	}

	/**
	 * Schedule a flush for the next microtask.
	 */
	function scheduleFlush(): void {
		if ( isFlushScheduled || isDestroyed ) {
			return;
		}

		isFlushScheduled = true;
		queueMicrotask( () => {
			isFlushScheduled = false;
			if ( ! isDestroyed && pendingCommands.length > 0 ) {
				void executeFlush();
			}
		} );
	}

	/**
	 * The public queue interface.
	 */
	const queue: CommandQueue< TState > = {
		get state() {
			return queueState;
		},

		get isProcessing() {
			return queueState !== 'idle';
		},

		get pendingCount() {
			return (
				pendingCommands.filter( ( pc ) => ! pc.cancelled ).length +
				executingCommands.length
			);
		},

		enqueue( command: Command< TState > ): EnqueueResult< TState > {
			if ( isDestroyed ) {
				return {
					commandId: command.id,
					promise: Promise.reject(
						new Error( 'Queue has been destroyed' )
					),
					cancel: () => false,
				};
			}

			// Create pending command with promise
			let resolvePromise: ( result: CommandResult< TState > ) => void;
			let rejectPromise: ( error: Error ) => void;

			const promise = new Promise< CommandResult< TState > >(
				( resolve, reject ) => {
					resolvePromise = resolve;
					rejectPromise = reject;
				}
			);

			const pending: PendingCommand< TState > = {
				command,
				// eslint-disable-next-line @typescript-eslint/no-non-null-assertion
				resolve: resolvePromise!,
				// eslint-disable-next-line @typescript-eslint/no-non-null-assertion
				reject: rejectPromise!,
				cancelled: false,
			};

			// Take snapshot on first command (when idle)
			if ( queueState === 'idle' ) {
				snapshot = cloneState( stateHandler.getState() );
				transitionTo( 'collecting' );
				emit( 'collecting', { commandId: command.id } );
			}

			// Apply optimistic update immediately
			const currentState = stateHandler.getState();
			command.applyOptimistic( currentState );

			// Add to pending
			pendingCommands.push( pending );

			// Schedule flush
			scheduleFlush();

			return {
				commandId: command.id,
				promise,
				cancel: () => {
					if ( pending.cancelled ) {
						return false;
					}

					// Can only cancel if not yet executing
					const isInPending = pendingCommands.includes( pending );
					if ( isInPending ) {
						pending.cancelled = true;
						pending.reject( new Error( 'Command cancelled' ) );
						return true;
					}

					return false;
				},
			};
		},

		async flush(): Promise< BatchResult< TState > | null > {
			isFlushScheduled = false;
			return executeFlush();
		},

		abort(): void {
			// Abort in-flight request
			if ( abortController ) {
				abortController.abort();
			}

			// Cancel all pending
			for ( const pc of pendingCommands ) {
				if ( ! pc.cancelled ) {
					pc.cancelled = true;
					pc.reject( new Error( 'Queue aborted' ) );
				}
			}

			pendingCommands = [];

			// If executing, the abort will trigger reconciliation
			// If idle/collecting, just reset state
			if ( queueState !== 'executing' && snapshot !== null ) {
				stateHandler.setState( snapshot );
				snapshot = null;
				transitionTo( 'idle' );
				emit( 'idle', {} );
			}
		},

		cancel( commandId: CommandId ): boolean {
			const pending = pendingCommands.find(
				( pc ) => pc.command.id === commandId && ! pc.cancelled
			);

			if ( pending ) {
				pending.cancelled = true;
				pending.reject( new Error( 'Command cancelled' ) );
				return true;
			}

			return false;
		},

		on< K extends keyof QueueEvents< TState > >(
			event: K,
			listener: QueueEventListener< TState, K >
		): () => void {
			if ( ! listeners.has( event ) ) {
				listeners.set( event, new Set() );
			}

			// eslint-disable-next-line @typescript-eslint/no-non-null-assertion
			listeners
				.get( event )!
				.add(
					listener as QueueEventListener<
						TState,
						keyof QueueEvents< TState >
					>
				);

			return () => queue.off( event, listener );
		},

		off< K extends keyof QueueEvents< TState > >(
			event: K,
			listener: QueueEventListener< TState, K >
		): void {
			const eventListeners = listeners.get( event );
			if ( eventListeners ) {
				eventListeners.delete(
					listener as QueueEventListener<
						TState,
						keyof QueueEvents< TState >
					>
				);
			}
		},

		destroy(): void {
			isDestroyed = true;
			queue.abort();
			listeners.clear();
		},
	};

	return queue;
}
