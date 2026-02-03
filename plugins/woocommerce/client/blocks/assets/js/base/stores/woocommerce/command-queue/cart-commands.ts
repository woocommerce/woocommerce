/**
 * Cart Command Factories
 *
 * Factory functions for creating cart mutation commands that work with the
 * Command Queue. Each factory creates a Command<CartState> that knows how to:
 * - Apply optimistic updates immediately
 * - Build the API request for the server
 * - Transform the server response
 *
 * These commands are designed to work with the WooCommerce Interactivity API
 * cart store and the WC Store API endpoints.
 */

/**
 * External dependencies
 */
import type { Cart, CartItem, CartVariationItem } from '@woocommerce/types';

/**
 * Internal dependencies
 */
import type { Command, CommandRequest } from './types';
import { generateCommandId } from './types';

/**
 * The cart state shape used by the iAPI store.
 * This matches the Store['state']['cart'] type from cart.ts.
 */
export type CartState = Omit< Cart, 'items' > & {
	items: ( OptimisticCartItem | CartItem )[];
};

/**
 * Optimistic cart item - a partial item representation used before
 * the server responds with the full cart item data.
 */
export type OptimisticCartItem = {
	key?: string | undefined;
	id: number;
	quantity: number;
	variation?: CartVariationItem[];
	type: string;
};

/**
 * Selected attributes for variation matching.
 * Omits raw_attribute since it's only in the CartVariationItem type.
 */
export type SelectedAttributes = Omit< CartVariationItem, 'raw_attribute' >;

/**
 * Client-side cart item input (what the caller provides).
 */
export type ClientCartItem = Omit< OptimisticCartItem, 'variation' > & {
	variation?: SelectedAttributes[];
};

/**
 * Type guard to check if an item is a full CartItem (from server)
 * vs an OptimisticCartItem (local placeholder).
 */
export function isCartItem(
	item: OptimisticCartItem | CartItem
): item is CartItem {
	return 'name' in item;
}

/**
 * Check if cart item attributes match selected attributes.
 * Used to find existing items when adding variations.
 */
function doesCartItemMatchAttributes(
	cartItem: OptimisticCartItem,
	selectedAttributes: SelectedAttributes[]
): boolean {
	if (
		! Array.isArray( cartItem.variation ) ||
		! Array.isArray( selectedAttributes )
	) {
		return false;
	}

	if ( cartItem.variation.length !== selectedAttributes.length ) {
		return false;
	}

	return cartItem.variation.every(
		( {
			// eslint-disable-next-line @typescript-eslint/naming-convention
			raw_attribute,
			value,
		}: CartVariationItem ) =>
			selectedAttributes.some( ( attr: SelectedAttributes ) => {
				return (
					attr.attribute === raw_attribute &&
					( attr.value.toLowerCase() === value.toLowerCase() ||
						// Handle "any" attribute type
						( attr.value && value === '' ) )
				);
			} )
	);
}

/**
 * Find an existing cart item that matches the given item input.
 * For variations, checks attribute matching.
 */
function findMatchingCartItem(
	items: ( OptimisticCartItem | CartItem )[],
	item: ClientCartItem
): ( OptimisticCartItem | CartItem ) | undefined {
	return items.find( ( cartItem ) => {
		if ( cartItem.type === 'variation' ) {
			// For variations, check ID and attribute matching
			if (
				item.id !== cartItem.id ||
				! cartItem.variation ||
				! item.variation ||
				cartItem.variation.length !== item.variation.length
			) {
				return false;
			}
			return doesCartItemMatchAttributes( cartItem, item.variation );
		}
		// For simple products, match by key or ID
		return item.key ? item.key === cartItem.key : item.id === cartItem.id;
	} );
}

/**
 * Payload for add/update item commands.
 */
export interface AddItemPayload {
	id: number;
	quantity: number;
	variation?: SelectedAttributes[];
	key?: string;
	type?: string;
}

/**
 * Payload for remove item commands.
 */
export interface RemoveItemPayload {
	key: string;
}

/**
 * Payload for update quantity commands.
 */
export interface UpdateQuantityPayload {
	key: string;
	quantity: number;
}

/**
 * Creates an add-item command.
 *
 * This command handles both adding new items and updating existing items.
 * It detects whether the item already exists in the cart and uses the
 * appropriate endpoint (add-item vs update-item).
 *
 * @param payload - The item to add
 * @param nonce   - Authentication nonce
 * @return A command that adds/updates the item
 */
export function createAddItemCommand(
	payload: AddItemPayload,
	nonce: string
): Command< CartState, AddItemPayload > {
	const commandId = generateCommandId();

	// Track whether this is an add or update (determined during optimistic update)
	let isUpdate = false;
	let existingItemKey: string | undefined;

	return {
		id: commandId,
		type: 'add-item',
		payload: { ...payload },

		applyOptimistic( state: CartState ): void {
			const existingItem = findMatchingCartItem( state.items, payload );

			if ( existingItem ) {
				isUpdate = true;
				existingItemKey = existingItem.key;

				// Check if sold individually (can't update quantity)
				const isSoldIndividually =
					isCartItem( existingItem ) &&
					existingItem.sold_individually;

				if ( existingItem.key && ! isSoldIndividually ) {
					existingItem.quantity = payload.quantity;
				}
			} else {
				// Add new optimistic item
				const optimisticItem: OptimisticCartItem = {
					id: payload.id,
					quantity: payload.quantity,
					type: payload.type || 'simple',
					...( payload.variation && {
						// Convert SelectedAttributes to CartVariationItem format
						variation: payload.variation.map( ( attr ) => ( {
							attribute: attr.attribute,
							value: attr.value,
							raw_attribute: attr.attribute,
						} ) ),
					} ),
				};
				state.items.push( optimisticItem );
			}
		},

		buildRequest(): CommandRequest {
			const endpoint = isUpdate ? 'update-item' : 'add-item';
			const body = isUpdate
				? { key: existingItemKey, quantity: payload.quantity }
				: {
						id: payload.id,
						quantity: payload.quantity,
						...( payload.variation && {
							variation: payload.variation,
						} ),
				  };

			return {
				method: 'POST',
				path: `/wc/store/v1/cart/${ endpoint }`,
				headers: { Nonce: nonce },
				body,
			};
		},

		transformResponse( response: unknown ): CartState | null {
			// The WC Store API returns the full cart on success
			if (
				response &&
				typeof response === 'object' &&
				'items' in response
			) {
				return response as CartState;
			}
			return null;
		},
	};
}

/**
 * Creates a remove-item command.
 *
 * @param payload - The item key to remove
 * @param nonce   - Authentication nonce
 * @return A command that removes the item
 */
export function createRemoveItemCommand(
	payload: RemoveItemPayload,
	nonce: string
): Command< CartState, RemoveItemPayload > {
	const commandId = generateCommandId();

	return {
		id: commandId,
		type: 'remove-item',
		payload: { ...payload },

		applyOptimistic( state: CartState ): void {
			state.items = state.items.filter(
				( item ) => item.key !== payload.key
			);
		},

		buildRequest(): CommandRequest {
			return {
				method: 'POST',
				path: '/wc/store/v1/cart/remove-item',
				headers: { Nonce: nonce },
				body: { key: payload.key },
			};
		},

		transformResponse( response: unknown ): CartState | null {
			if (
				response &&
				typeof response === 'object' &&
				'items' in response
			) {
				return response as CartState;
			}
			return null;
		},
	};
}

/**
 * Creates an update-quantity command.
 *
 * This is a simpler version of add-item that only updates quantity
 * for an existing item.
 *
 * @param payload - The item key and new quantity
 * @param nonce   - Authentication nonce
 * @return A command that updates the item quantity
 */
export function createUpdateQuantityCommand(
	payload: UpdateQuantityPayload,
	nonce: string
): Command< CartState, UpdateQuantityPayload > {
	const commandId = generateCommandId();

	return {
		id: commandId,
		type: 'update-quantity',
		payload: { ...payload },

		applyOptimistic( state: CartState ): void {
			const item = state.items.find( ( i ) => i.key === payload.key );
			if ( item ) {
				item.quantity = payload.quantity;
			}
		},

		buildRequest(): CommandRequest {
			return {
				method: 'POST',
				path: '/wc/store/v1/cart/update-item',
				headers: { Nonce: nonce },
				body: { key: payload.key, quantity: payload.quantity },
			};
		},

		transformResponse( response: unknown ): CartState | null {
			if (
				response &&
				typeof response === 'object' &&
				'items' in response
			) {
				return response as CartState;
			}
			return null;
		},
	};
}

/**
 * Creates multiple add-item commands from a batch.
 *
 * This is useful for the batchAddCartItems action that needs to
 * add multiple items at once.
 *
 * @param items - Array of items to add
 * @param nonce - Authentication nonce
 * @return Array of add-item commands
 */
export function createBatchAddItemCommands(
	items: ClientCartItem[],
	nonce: string
): Command< CartState, AddItemPayload >[] {
	return items.map( ( item ) =>
		createAddItemCommand(
			{
				id: item.id,
				quantity: item.quantity,
				variation: item.variation,
				key: item.key,
				type: item.type,
			},
			nonce
		)
	);
}
