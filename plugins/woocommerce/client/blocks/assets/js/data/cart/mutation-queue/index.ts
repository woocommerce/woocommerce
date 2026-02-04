/**
 * Cart Mutation Queue
 *
 * A state-machine based request batching system for cart operations.
 * Handles:
 * - Sequential batch processing (prevents race conditions)
 * - Request isolation (deep cloning at submission)
 * - Optimistic updates with smart reconciliation
 * - Legacy event compatibility
 */

// Core queue implementation
export { MutationQueue } from './mutation-queue';

// State manager adapter
export {
	createCartStateManager,
	createMockStateManager,
} from './state-manager';

// Consumer-facing cart actions API
export {
	CartActions,
	createCartActions,
	type AddToCartOptions,
	type UpdateQuantityOptions,
	type RemoveItemOptions,
	type VariationAttribute,
	type ContextProvider,
	type StateProvider,
} from './cart-actions';

// Types
export type {
	QueueState,
	MutationMethod,
	MutationRequest,
	MutationResult,
	MutationOptions,
	BatchResponse,
	BatchResponseItem,
	QueuedMutation,
	StateManager,
	CartMutationQueue,
	CartResponse,
} from './types';

// Type guards
export { isCartResponse, extractCartFromResponse } from './types';
