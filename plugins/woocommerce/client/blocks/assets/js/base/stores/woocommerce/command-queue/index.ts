/**
 * Command Queue
 *
 * A robust, single-flight command queue for cart mutations.
 *
 * @example
 * ```typescript
 * import { createCommandQueue, generateCommandId } from './command-queue';
 * import type { Cart } from '@woocommerce/types';
 *
 * const queue = createCommandQueue<Cart>({
 *   getState: () => state.cart,
 *   setState: (cart) => { state.cart = cart; },
 *   onErrors: (errors) => showErrorNotices(errors),
 * });
 *
 * // Enqueue commands - they batch automatically
 * queue.enqueue(createAddItemCommand({ id: 1, quantity: 2 }));
 * queue.enqueue(createAddItemCommand({ id: 2, quantity: 1 }));
 * // Both execute in a single batch on next microtask
 * ```
 */

// Types
export type {
	CommandId,
	QueueState,
	CommandResult,
	Command,
	CommandRequest,
	BatchResult,
	StateHandler,
	QueueConfig,
	QueueEvents,
	QueueEventListener,
	EnqueueResult,
	CommandQueue,
	CreateCommandQueue,
	CommandFactory,
	CartCommandType,
} from './types';

// Utilities
export { generateCommandId } from './types';

// Implementation
export { createCommandQueue } from './create-command-queue';
