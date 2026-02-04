/**
 * External dependencies
 */
import type { Cart, CartResponse } from '@woocommerce/types';

/**
 * State machine states for the mutation queue.
 *
 * IDLE: No active operations, system ready for new requests
 * COLLECTING: Gathering requests within a single microtask tick
 * SENDING: Batch request in-flight to server
 * RECORDING: Processing responses as they arrive
 * RECONCILING: Applying final state and resolving promises
 */
export type QueueState =
	| 'idle'
	| 'collecting'
	| 'sending'
	| 'recording'
	| 'reconciling';

/**
 * HTTP methods supported for cart mutations.
 */
export type MutationMethod = 'POST' | 'PUT' | 'DELETE';

/**
 * Represents a single mutation request to be sent to the server.
 * These are the individual requests that get batched together.
 */
export interface MutationRequest {
	/** The API endpoint path (relative to store API base) */
	path: string;
	/** HTTP method for the request */
	method: MutationMethod;
	/** Request body data */
	body: unknown;
	/** Optional additional headers */
	headers?: Record< string, string > | undefined;
}

/**
 * Result of a mutation operation, returned when the promise resolves.
 */
export interface MutationResult< T = unknown > {
	/** Whether the mutation succeeded */
	success: boolean;
	/** Response data if successful */
	data?: T;
	/** Error details if failed */
	error?: Error;
}

/**
 * Individual response from the batch endpoint for a single request.
 */
export interface BatchResponseItem {
	/** HTTP status code for this request */
	status: number;
	/** Response body (cart data or error) */
	body: unknown;
	/** Response headers if any */
	headers?: Record< string, string >;
}

/**
 * Response from the /wc/store/v1/batch endpoint.
 */
export interface BatchResponse {
	/** Whether the batch request itself succeeded */
	success: boolean;
	/** Array of individual responses */
	data?: {
		responses: BatchResponseItem[];
	};
	/** Error if the entire batch failed */
	error?: Error;
}

/**
 * Options for submitting a mutation to the queue.
 */
export interface MutationOptions< T = unknown > {
	/**
	 * Unique identifier for this mutation type.
	 * Used for logging and debugging.
	 */
	key: string;

	/**
	 * Function that returns the mutation request details.
	 * Called at submission time to capture the current state.
	 */
	mutate: () => MutationRequest;

	/**
	 * Optional optimistic update to apply immediately before the server responds.
	 * Receives current cart state and should return the new optimistic state.
	 */
	optimisticUpdate?: ( currentState: Cart ) => Cart;

	/**
	 * Callback invoked after this specific mutation settles (success or failure).
	 * Fired during reconciliation, before the processing flag is cleared.
	 */
	onSettled?: ( result: MutationResult< T > ) => void;
}

/**
 * Internal representation of a queued mutation with its promise resolvers.
 */
export interface QueuedMutation< T = unknown > {
	/** Unique identifier for this specific mutation instance */
	id: string;
	/** Key from MutationOptions */
	key: string;
	/** Isolated (deep cloned) request data */
	request: MutationRequest;
	/** Optimistic update function */
	optimisticUpdate?: ( ( state: Cart ) => Cart ) | undefined;
	/** Settled callback */
	onSettled?: ( ( result: MutationResult< T > ) => void ) | undefined;
	/** Promise resolver */
	resolve: ( result: MutationResult< T > ) => void;
	/** Promise rejecter (not used for normal errors, only for internal failures) */
	reject: ( error: Error ) => void;
}

/**
 * Adapter interface for connecting the mutation queue to the cart store.
 * This abstraction allows the queue to work with different store implementations.
 */
export interface StateManager {
	/**
	 * Get a snapshot of the current cart state.
	 * Called at the start of a batch cycle to capture the initial state for rollback.
	 */
	getSnapshot(): Cart;

	/**
	 * Apply an optimistic update to the cart state immediately.
	 * Called for each mutation that has an optimisticUpdate function.
	 */
	applyOptimisticUpdate( update: ( state: Cart ) => Cart ): void;

	/**
	 * Apply the server-returned state to the cart.
	 * Called during reconciliation with the final authoritative state.
	 */
	applyServerState( state: Cart ): void;

	/**
	 * Send a request to the server (the batch request).
	 * Should handle authentication, headers, and error handling.
	 */
	sendRequest< T >( request: MutationRequest ): Promise< T >;

	/**
	 * Display accumulated errors to the user.
	 * Called during reconciliation if any mutations failed.
	 */
	showErrors( errors: Error[] ): void;
}

/**
 * Public interface for the cart mutation queue.
 */
export interface CartMutationQueue {
	/**
	 * Submit a mutation to the queue.
	 * Returns a promise that resolves when the mutation completes.
	 */
	submit< T >(
		mutation: MutationOptions< T >
	): Promise< MutationResult< T > >;

	/**
	 * Check if any mutations are currently processing.
	 */
	isProcessing(): boolean;

	/**
	 * Subscribe to processing state changes.
	 * Returns an unsubscribe function.
	 */
	subscribe( listener: ( processing: boolean ) => void ): () => void;
}

/**
 * Cart response from API with snake_case keys (before camelCase conversion).
 * Re-export for convenience.
 */
export type { CartResponse };

/**
 * Type guard to check if a response body contains cart data.
 */
export function isCartResponse(
	body: unknown
): body is CartResponse | { cart: CartResponse } {
	if ( ! body || typeof body !== 'object' ) {
		return false;
	}
	// Check for direct cart response (has 'items' array)
	if ( 'items' in body && Array.isArray( ( body as CartResponse ).items ) ) {
		return true;
	}
	// Check for wrapped cart response (has 'cart' property)
	if (
		'cart' in body &&
		typeof ( body as { cart: unknown } ).cart === 'object'
	) {
		return true;
	}
	return false;
}

/**
 * Extract cart data from a response body.
 */
export function extractCartFromResponse(
	body: unknown
): CartResponse | undefined {
	if ( ! body || typeof body !== 'object' ) {
		return undefined;
	}
	// Direct cart response
	if ( 'items' in body && Array.isArray( ( body as CartResponse ).items ) ) {
		return body as CartResponse;
	}
	// Wrapped cart response
	if (
		'cart' in body &&
		typeof ( body as { cart: CartResponse } ).cart === 'object'
	) {
		return ( body as { cart: CartResponse } ).cart;
	}
	return undefined;
}
