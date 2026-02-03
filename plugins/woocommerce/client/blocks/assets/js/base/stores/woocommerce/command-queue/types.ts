/**
 * Command Queue Types
 *
 * A robust, single-flight command queue for cart mutations that:
 * - Prevents concurrent API requests (server ops are non-atomic)
 * - Supports optimistic updates with automatic rollback
 * - Handles timeouts and request cancellation
 * - Provides a clean API for extenders
 *
 * Design Principles:
 * 1. Server is the source of truth - always reconcile to server state
 * 2. Single batch in-flight - no concurrent mutations
 * 3. Optimistic UI - immediate feedback, eventual consistency
 * 4. Graceful degradation - handle all network conditions
 */

/**
 * Unique identifier for commands. Used for tracking, cancellation, and debugging.
 */
export type CommandId = string;

/**
 * Queue states following the state machine:
 * IDLE → COLLECTING → EXECUTING → RECONCILING → IDLE
 */
export type QueueState = 'idle' | 'collecting' | 'executing' | 'reconciling';

/**
 * Result of a single command execution.
 */
export type CommandResult< TState, TError = Error > =
	| { success: true; state: TState }
	| { success: false; error: TError; state?: TState };

/**
 * A command represents a single mutation operation.
 *
 * Commands are first-class citizens in the queue. Each command:
 * - Has a unique ID for tracking
 * - Carries its payload for the API request
 * - Knows how to apply itself optimistically
 * - Can be cancelled before execution
 *
 * @template TState   - The state type (e.g., Cart)
 * @template TPayload - The payload type for this command
 */
export interface Command< TState, TPayload = unknown > {
	/**
	 * Unique identifier for this command.
	 * Used for tracking, cancellation, and debugging.
	 */
	readonly id: CommandId;

	/**
	 * The type of operation this command performs.
	 * Used for batching similar operations and debugging.
	 */
	readonly type: string;

	/**
	 * The payload to send to the API.
	 * This is cloned when the command is created to prevent
	 * mutations from affecting the request.
	 */
	readonly payload: TPayload;

	/**
	 * Apply this command optimistically to the current state.
	 * Called immediately when the command is enqueued.
	 *
	 * @param state - Current state (mutable)
	 */
	applyOptimistic: ( state: TState ) => void;

	/**
	 * Build the API request for this command.
	 * Called when the batch is ready to execute.
	 *
	 * @return The request configuration
	 */
	buildRequest: () => CommandRequest;

	/**
	 * Optional: Transform the response before it's applied.
	 * Useful for extracting relevant data from batch responses.
	 *
	 * @param response - The raw API response
	 * @return         The transformed state or null if this response doesn't apply
	 */
	transformResponse?: ( response: unknown ) => TState | null;
}

/**
 * Request configuration for a command.
 * Compatible with the WooCommerce batch API format.
 */
export interface CommandRequest {
	/** HTTP method */
	method: 'GET' | 'POST' | 'PUT' | 'DELETE';

	/** API endpoint path (relative to REST base) */
	path: string;

	/** Request headers */
	headers?: Record< string, string >;

	/** Request body (will be JSON stringified) */
	body?: unknown;
}

/**
 * Batch execution result.
 * Contains results for all commands in the batch.
 */
export interface BatchResult< TState > {
	/**
	 * Results indexed by command ID.
	 */
	results: Map< CommandId, CommandResult< TState > >;

	/**
	 * The final reconciled state to apply.
	 * This is the server's authoritative state after all operations.
	 */
	finalState: TState | null;

	/**
	 * Whether any commands failed.
	 */
	hasErrors: boolean;

	/**
	 * Errors from failed commands.
	 */
	errors: Error[];
}

/**
 * State handler interface for extenders.
 *
 * Extenders implement this interface to integrate the queue with their store.
 * This provides a clean separation between the queue logic and state management.
 */
export interface StateHandler< TState > {
	/**
	 * Get the current state.
	 * Called when taking a snapshot before optimistic updates.
	 */
	getState: () => TState;

	/**
	 * Apply new state (from server or rollback).
	 * Called during reconciliation.
	 *
	 * @param state - The state to apply
	 */
	setState: ( state: TState ) => void;

	/**
	 * Create a deep copy of the state for snapshots.
	 * Default uses JSON.parse(JSON.stringify()), but extenders
	 * can provide more efficient implementations.
	 *
	 * @param state - The state to clone
	 * @return      A deep copy of the state
	 */
	cloneState?: ( state: TState ) => TState;

	/**
	 * Optional: Called when the queue starts processing.
	 * Use for setting loading states.
	 */
	onProcessingStart?: () => void;

	/**
	 * Optional: Called when the queue finishes processing.
	 * Use for clearing loading states.
	 *
	 * @param result - The batch execution result
	 */
	onProcessingEnd?: ( result: BatchResult< TState > ) => void;

	/**
	 * Optional: Called when errors occur.
	 * Use for displaying error notices.
	 *
	 * @param errors - Array of errors from failed commands
	 */
	onErrors?: ( errors: Error[] ) => void;
}

/**
 * Queue configuration options.
 */
export interface QueueConfig {
	/**
	 * Timeout for batch execution in milliseconds.
	 * After this time, pending requests are aborted.
	 * Default: 30000 (30 seconds)
	 */
	timeout?: number;

	/**
	 * Maximum commands per batch.
	 * Prevents extremely large batches that might timeout.
	 * Default: 25
	 */
	maxBatchSize?: number;

	/**
	 * REST API base URL.
	 * Default: Uses WordPress REST API base
	 */
	restUrl?: string;

	/**
	 * Nonce for authentication.
	 * Required for authenticated requests.
	 */
	nonce?: string;

	/**
	 * Custom fetch function for testing.
	 * Default: window.fetch
	 */
	fetch?: typeof fetch;
}

/**
 * Queue event names.
 */
export type QueueEventName =
	| 'collecting'
	| 'executing'
	| 'response'
	| 'reconciled'
	| 'idle'
	| 'timeout'
	| 'aborted';

/**
 * Events emitted by the queue.
 * Extenders can subscribe to these for monitoring and debugging.
 */
export interface QueueEvents< TState > {
	/**
	 * Fired when the queue transitions to collecting state.
	 * First command has been enqueued.
	 */
	collecting: { commandId: CommandId };

	/**
	 * Fired when a batch is about to be sent.
	 * Contains all commands in the batch.
	 */
	executing: { commands: Command< TState >[] };

	/**
	 * Fired when a batch response is received.
	 * Before reconciliation.
	 */
	response: { result: BatchResult< TState > };

	/**
	 * Fired when reconciliation is complete.
	 * Final state has been applied.
	 */
	reconciled: { state: TState; result: BatchResult< TState > };

	/**
	 * Fired when the queue returns to idle.
	 * No pending commands.
	 */
	idle: Record< string, never >;

	/**
	 * Fired when a timeout occurs.
	 * Batch was aborted.
	 */
	timeout: { commands: Command< TState >[] };

	/**
	 * Fired when the queue is manually aborted.
	 */
	aborted: { commands: Command< TState >[] };
}

/**
 * Event listener function type.
 */
export type QueueEventListener<
	TState,
	K extends keyof QueueEvents< TState >
> = ( event: QueueEvents< TState >[ K ] ) => void;

/**
 * Promise returned by enqueue operations.
 * Resolves when the command completes (after reconciliation).
 */
export interface EnqueueResult< TState > {
	/**
	 * The command ID for tracking.
	 */
	commandId: CommandId;

	/**
	 * Promise that resolves with the command result.
	 * Resolves after reconciliation, not after individual request completion.
	 */
	promise: Promise< CommandResult< TState > >;

	/**
	 * Cancel this specific command if it hasn't been sent yet.
	 * Returns true if cancelled, false if already executed.
	 */
	cancel: () => boolean;
}

/**
 * The main Command Queue interface.
 *
 * Usage:
 * ```typescript
 * const queue = createCommandQueue<Cart>({
 *   getState: () => state.cart,
 *   setState: (cart) => { state.cart = cart; },
 *   onErrors: (errors) => showErrorNotices(errors),
 * });
 *
 * // Enqueue a command
 * const { promise } = queue.enqueue({
 *   id: generateId(),
 *   type: 'add-item',
 *   payload: { id: 123, quantity: 1 },
 *   applyOptimistic: (cart) => {
 *     cart.items.push({ id: 123, quantity: 1 });
 *   },
 *   buildRequest: () => ({
 *     method: 'POST',
 *     path: '/wc/store/v1/cart/add-item',
 *     body: { id: 123, quantity: 1 },
 *   }),
 * });
 *
 * // Wait for result
 * const result = await promise;
 * if (result.success) {
 *   console.log('Item added', result.state);
 * } else {
 *   console.error('Failed', result.error);
 * }
 * ```
 */
export interface CommandQueue< TState > {
	/**
	 * Current queue state.
	 */
	readonly state: QueueState;

	/**
	 * Whether the queue is currently processing commands.
	 */
	readonly isProcessing: boolean;

	/**
	 * Number of pending commands (not yet sent).
	 */
	readonly pendingCount: number;

	/**
	 * Enqueue a command for execution.
	 *
	 * Commands are collected until the next microtask, then executed
	 * as a single batch. Returns immediately with a promise that
	 * resolves after reconciliation.
	 *
	 * @param command - The command to enqueue
	 * @return        EnqueueResult with command ID, promise, and cancel function
	 */
	enqueue: ( command: Command< TState > ) => EnqueueResult< TState >;

	/**
	 * Force immediate execution of pending commands.
	 *
	 * Normally commands are batched automatically via microtask.
	 * Use this to force execution (e.g., before navigation).
	 *
	 * @return Promise that resolves when the batch completes
	 */
	flush: () => Promise< BatchResult< TState > | null >;

	/**
	 * Abort all pending and in-flight commands.
	 *
	 * Triggers rollback to the last known good state.
	 * Use when user navigates away or cancels an operation.
	 */
	abort: () => void;

	/**
	 * Cancel a specific pending command by ID.
	 *
	 * @param commandId - The command ID to cancel
	 * @return          true if cancelled, false if not found or already executed
	 */
	cancel: ( commandId: CommandId ) => boolean;

	/**
	 * Subscribe to queue events.
	 *
	 * @param event    - Event name
	 * @param listener - Event handler
	 * @return         Unsubscribe function
	 */
	on: < K extends keyof QueueEvents< TState > >(
		event: K,
		listener: QueueEventListener< TState, K >
	) => () => void;

	/**
	 * Unsubscribe from queue events.
	 *
	 * @param event    - Event name
	 * @param listener - Event handler to remove
	 */
	off: < K extends keyof QueueEvents< TState > >(
		event: K,
		listener: QueueEventListener< TState, K >
	) => void;

	/**
	 * Destroy the queue and clean up resources.
	 *
	 * Aborts any pending operations and removes all listeners.
	 * Call this when unmounting the component that owns the queue.
	 */
	destroy: () => void;
}

/**
 * Factory function to create a command queue.
 */
export type CreateCommandQueue = < TState >(
	stateHandler: StateHandler< TState >,
	config?: QueueConfig
) => CommandQueue< TState >;

/**
 * Helper type for creating commands with less boilerplate.
 * Used by command factories (e.g., createAddItemCommand).
 */
export interface CommandFactory< TState, TPayload, TOptions = void > {
	( payload: TPayload, options?: TOptions ): Command< TState, TPayload >;
}

/**
 * Cart-specific command types for the WooCommerce cart.
 */
export type CartCommandType =
	| 'add-item'
	| 'remove-item'
	| 'update-quantity'
	| 'apply-coupon'
	| 'remove-coupon'
	| 'select-shipping-rate'
	| 'update-customer';

/**
 * Utility to generate unique command IDs.
 *
 * @return A unique command ID string
 */
export function generateCommandId(): CommandId {
	return `cmd-${ Date.now() }-${ Math.random()
		.toString( 36 )
		.slice( 2, 9 ) }`;
}
