/**
 * External dependencies
 */
import type { Cart } from '@woocommerce/types';

/**
 * HTTP methods supported by cart mutation requests.
 */
export type MutationMethod = 'POST' | 'PUT' | 'DELETE';

/**
 * Represents a single mutation request to be sent to the server.
 * Request data is captured immutably at submission time.
 */
export interface MutationRequest {
	/** The API path for this request (e.g., '/wc/store/v1/cart/add-item'). */
	path: string;
	/** The HTTP method for this request. */
	method: MutationMethod;
	/** The request body - will be deep cloned at submission time. */
	body: unknown;
	/** Optional additional headers for this request. */
	headers?: Record< string, string > | undefined;
}

/**
 * Result of a mutation operation, returned when the mutation settles.
 */
export interface MutationResult< T = unknown > {
	/** Whether the mutation succeeded. */
	success: boolean;
	/** The response data if successful. */
	data?: T | undefined;
	/** The error if the mutation failed. */
	error?: Error | undefined;
}

/**
 * Configuration for submitting a mutation to the queue.
 */
export interface MutationConfig< T = unknown > {
	/**
	 * Unique identifier for this mutation type.
	 * Used for logging and debugging.
	 */
	key: string;

	/**
	 * Function that returns the mutation request.
	 * Called immediately at submission time to capture request data.
	 */
	mutate: () => MutationRequest;

	/**
	 * Optional function to optimistically update the cart state.
	 * Applied immediately before the request is sent.
	 */
	optimisticUpdate?: ( currentState: Cart ) => Cart;

	/**
	 * Optional callback fired when this mutation settles (success or failure).
	 * Called during reconciliation, before the processing flag is cleared.
	 */
	onSettled?: ( result: MutationResult< T > ) => void;
}

/**
 * Internal representation of a queued mutation with resolver functions.
 */
export interface QueuedMutation< T = unknown > {
	/** Unique identifier for this specific mutation instance. */
	id: string;
	/** The mutation type key. */
	key: string;
	/** The immutably captured request data. */
	request: MutationRequest;
	/** Optional optimistic update function. */
	optimisticUpdate?: ( ( state: Cart ) => Cart ) | undefined;
	/** Optional callback fired when mutation settles. */
	onSettled?: ( ( result: MutationResult< T > ) => void ) | undefined;
	/** Promise resolver for successful completion. */
	resolve: ( result: MutationResult< T > ) => void;
	/** Promise rejector for errors. */
	reject: ( error: Error ) => void;
}

/**
 * States of the mutation queue state machine.
 *
 * Flow: IDLE → COLLECTING → SENDING → RECORDING → RECONCILING → IDLE
 *
 * - IDLE: No active operations, ready for new requests
 * - COLLECTING: Gathering requests within a microtask tick
 * - SENDING: Batch request in-flight to server
 * - RECORDING: Processing responses as they arrive
 * - RECONCILING: Applying final state and resolving promises
 */
export type QueueState =
	| 'idle'
	| 'collecting'
	| 'sending'
	| 'recording'
	| 'reconciling';

/**
 * Response structure for the batch API endpoint.
 */
export interface BatchApiResponse {
	/** Array of individual responses for each request in the batch. */
	responses: Array< {
		/** HTTP status code for this request. */
		status: number;
		/** Response body - typically the updated Cart on success. */
		body: unknown;
		/** Optional response headers. */
		headers?: Record< string, string > | undefined;
	} >;
}

/**
 * Internal response structure used during recording phase.
 */
export interface BatchResponse {
	/** Whether the overall batch request succeeded. */
	success: boolean;
	/** The parsed batch response data. */
	data?: BatchApiResponse | undefined;
	/** Error if the batch request failed entirely. */
	error?: Error | undefined;
}

/**
 * Adapter interface for connecting the mutation queue to the cart state.
 * Decouples the queue from the specific state management implementation.
 */
export interface StateManager {
	/**
	 * Get a snapshot of the current cart state.
	 * Called before mutations begin to enable rollback.
	 */
	getSnapshot(): Cart;

	/**
	 * Apply an optimistic update to the cart state.
	 * Should update the state immediately for responsive UI.
	 */
	applyOptimisticUpdate( update: ( state: Cart ) => Cart ): void;

	/**
	 * Apply the authoritative server state to the cart.
	 * Called during reconciliation with the final state.
	 */
	applyServerState( state: Cart ): void;

	/**
	 * Send a request to the server.
	 * Handles the actual fetch operation with proper headers/nonce.
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
	 * Returns a promise that resolves when the mutation settles.
	 */
	submit< T >(
		mutation: MutationConfig< T >
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
 * Listener function type for processing state changes.
 */
export type ProcessingListener = ( processing: boolean ) => void;
