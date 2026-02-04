/**
 * Cart Mutation Queue
 *
 * A request batching/queueing system for WooCommerce cart operations.
 *
 * Features:
 * - Microtask batching: Groups synchronous submissions into a single batch request
 * - Sequential processing: Only one batch in-flight at a time to prevent race conditions
 * - Request isolation: Deep clones request data at submission time
 * - Smart reconciliation: Uses latest successful server state, rolls back on total failure
 * - Event timing: Fires onSettled callbacks before clearing processing flag
 *
 * @example
 * ```typescript
 * // Create a queue with a state manager
 * const queue = new MutationQueue( stateManager );
 *
 * // Submit a mutation
 * const result = await queue.submit( {
 *   key: 'add-item',
 *   mutate: () => ( {
 *     path: '/wc/store/v1/cart/add-item',
 *     method: 'POST',
 *     body: { id: productId, quantity: 1 },
 *   } ),
 *   optimisticUpdate: ( state ) => ( {
 *     ...state,
 *     items: [ ...state.items, { id: productId, quantity: 1 } ],
 *   } ),
 *   onSettled: ( result ) => {
 *     if ( result.success ) {
 *       // Fire legacy event
 *     }
 *   },
 * } );
 * ```
 */
export { MutationQueue } from './mutation-queue';
export { createCartStateManager } from './state-manager';
export { CartActions, createCartActions } from './cart-actions';
export type { CartStateManagerConfig } from './state-manager';
export type {
	CartActionsConfig,
	ClientCartItem,
	ContextProvider,
	OptimisticCartItem,
	SelectedAttributes,
	StateProvider,
} from './cart-actions';

export type {
	// Core types
	MutationRequest,
	MutationResult,
	MutationConfig,
	MutationMethod,
	// Queue types
	CartMutationQueue,
	QueueState,
	QueuedMutation,
	// State management
	StateManager,
	// Response types
	BatchResponse,
	BatchApiResponse,
	// Listener types
	ProcessingListener,
} from './types';
