/**
 * External dependencies
 */
import type { Cart, CartItem, CartVariationItem } from '@woocommerce/types';

/**
 * Internal dependencies
 */
import type { CartMutationQueue, MutationResult } from './types';

/**
 * Selected attributes for variation products.
 * Omits raw_attribute which is set by the server.
 */
export type SelectedAttributes = Omit< CartVariationItem, 'raw_attribute' >;

/**
 * Optimistic cart item representation (before server confirmation).
 */
export interface OptimisticCartItem {
	key?: string | undefined;
	id: number;
	quantity: number;
	variation?: CartVariationItem[] | undefined;
	type: string;
}

/**
 * Client-side cart item input (user's selection).
 */
export interface ClientCartItem {
	id: number;
	quantity: number;
	variation?: SelectedAttributes[] | undefined;
	type?: string | undefined;
}

/**
 * Context provider function type.
 * Returns current context values from the UI (e.g., selected product, quantity).
 */
export type ContextProvider = () => {
	productId?: number | undefined;
	quantityToAdd?: number | undefined;
};

/**
 * State provider function type.
 * Returns the current cart state.
 */
export type StateProvider = () => Cart;

/**
 * Configuration for creating CartActions.
 */
export interface CartActionsConfig {
	/** The mutation queue instance. */
	queue: CartMutationQueue;
	/** Function to get current UI context. */
	contextProvider: ContextProvider;
	/** Function to get current cart state. */
	stateProvider: StateProvider;
}

/**
 * Type guard to check if an item is a full CartItem (from server).
 */
function isCartItem( item: OptimisticCartItem | CartItem ): item is CartItem {
	return 'name' in item;
}

/**
 * Check if cart item variation matches the selected attributes.
 */
function doesCartItemMatchAttributes(
	cartItem: OptimisticCartItem | CartItem,
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
		// eslint-disable-next-line @typescript-eslint/naming-convention -- API uses snake_case
		( { raw_attribute, value }: CartVariationItem ) =>
			selectedAttributes.some(
				( attr: SelectedAttributes ) =>
					attr.attribute === raw_attribute &&
					( attr.value.toLowerCase() === value.toLowerCase() ||
						( attr.value && value === '' ) ) // Handle "any" attribute type
			)
	);
}

/**
 * Higher-level cart actions that handle context capture automatically.
 *
 * This is the consumer-facing API for cart operations. It provides
 * intuitive methods that automatically capture context at call time
 * and handle batching through the mutation queue.
 *
 * @example
 * ```typescript
 * const actions = createCartActions( {
 *   queue: mutationQueue,
 *   contextProvider: () => ( { productId: 123, quantityToAdd: 1 } ),
 *   stateProvider: () => cartState,
 * } );
 *
 * // Add to cart with automatic context capture
 * await actions.addCartItem();
 *
 * // Or specify values explicitly
 * await actions.addCartItem( { productId: 456, quantity: 2 } );
 * ```
 */
export class CartActions {
	private queue: CartMutationQueue;
	private contextProvider: ContextProvider;
	private stateProvider: StateProvider;

	constructor( config: CartActionsConfig ) {
		this.queue = config.queue;
		this.contextProvider = config.contextProvider;
		this.stateProvider = config.stateProvider;
	}

	/**
	 * Add an item to the cart.
	 *
	 * Context values (productId, quantity) are captured immediately
	 * at call time, ensuring they won't be affected by subsequent
	 * UI changes during async operations.
	 *
	 * @param options           Optional overrides for context values.
	 * @param options.productId Product ID to add.
	 * @param options.quantity  Quantity to add.
	 * @param options.variation Variation attributes.
	 * @return                  Promise resolving to the mutation result.
	 */
	async addCartItem( options?: {
		productId?: number | undefined;
		quantity?: number | undefined;
		variation?: SelectedAttributes[] | undefined;
	} ): Promise< MutationResult< Cart > > {
		// Capture context values immediately
		const context = this.contextProvider();
		const productId = options?.productId ?? context.productId;
		const quantity = options?.quantity ?? context.quantityToAdd ?? 1;
		const variation = options?.variation;

		if ( ! productId ) {
			return {
				success: false,
				error: new Error( 'Product ID is required' ),
			};
		}

		// Check if item already exists in cart
		const currentState = this.stateProvider();
		const existingItem = this.findExistingItem(
			currentState,
			productId,
			variation
		);

		const endpoint = existingItem ? 'update-item' : 'add-item';
		const newQuantity = existingItem
			? existingItem.quantity + quantity
			: quantity;

		return this.queue.submit< Cart >( {
			key: 'add-cart-item',
			mutate: () => ( {
				path: `/wc/store/v1/cart/${ endpoint }`,
				method: 'POST',
				body: existingItem
					? { key: existingItem.key, quantity: newQuantity }
					: {
							id: productId,
							quantity,
							...( variation && { variation } ),
					  },
			} ),
			optimisticUpdate: ( state ) =>
				this.optimisticallyAddItem(
					state,
					productId,
					quantity,
					variation
				),
			onSettled: ( result ) => {
				if ( result.success ) {
					// Fire legacy event for third-party compatibility
					this.dispatchLegacyEvent( 'wc-blocks_added_to_cart', {
						productId,
						quantity,
					} );
				}
			},
		} );
	}

	/**
	 * Update an item's quantity in the cart.
	 *
	 * Supports both absolute quantity and delta (increment/decrement).
	 *
	 * @param cartKey         The cart item key.
	 * @param update          The quantity update (absolute or delta).
	 * @param update.delta    Quantity change (positive or negative).
	 * @param update.absolute Absolute quantity to set.
	 * @return                Promise resolving to the mutation result.
	 */
	async updateQuantity(
		cartKey: string,
		update: { delta?: number; absolute?: number }
	): Promise< MutationResult< Cart > > {
		const currentState = this.stateProvider();
		const currentItem = currentState.items.find(
			( item ) => item.key === cartKey
		);

		if ( ! currentItem ) {
			return {
				success: false,
				error: new Error( `Item ${ cartKey } not found in cart` ),
			};
		}

		const newQuantity =
			update.absolute ?? currentItem.quantity + ( update.delta ?? 0 );

		if ( newQuantity <= 0 ) {
			// If quantity would be zero or negative, remove the item instead
			return this.removeCartItem( cartKey );
		}

		return this.queue.submit< Cart >( {
			key: 'update-quantity',
			mutate: () => ( {
				path: `/wc/store/v1/cart/update-item`,
				method: 'POST',
				body: { key: cartKey, quantity: newQuantity },
			} ),
			optimisticUpdate: ( state ) => ( {
				...state,
				items: state.items.map( ( item ) =>
					item.key === cartKey
						? { ...item, quantity: newQuantity }
						: item
				),
			} ),
		} );
	}

	/**
	 * Remove an item from the cart.
	 *
	 * @param cartKey The cart item key to remove.
	 * @return Promise resolving to the mutation result.
	 */
	async removeCartItem( cartKey: string ): Promise< MutationResult< Cart > > {
		return this.queue.submit< Cart >( {
			key: 'remove-item',
			mutate: () => ( {
				path: `/wc/store/v1/cart/remove-item`,
				method: 'POST',
				body: { key: cartKey },
			} ),
			optimisticUpdate: ( state ) => ( {
				...state,
				items: state.items.filter( ( item ) => item.key !== cartKey ),
			} ),
			onSettled: ( result ) => {
				if ( result.success ) {
					this.dispatchLegacyEvent( 'wc-blocks_removed_from_cart', {
						cartKey,
					} );
				}
			},
		} );
	}

	/**
	 * Batch add multiple items to the cart.
	 *
	 * All items are automatically batched into a single request
	 * through the mutation queue.
	 *
	 * @param items Array of items to add.
	 * @return Promise resolving to array of mutation results.
	 */
	async batchAddItems(
		items: Array< {
			productId: number;
			quantity: number;
			variation?: SelectedAttributes[];
		} >
	): Promise< Array< MutationResult< Cart > > > {
		const promises = items.map( ( item ) =>
			this.addCartItem( {
				productId: item.productId,
				quantity: item.quantity,
				variation: item.variation,
			} )
		);

		return Promise.all( promises );
	}

	/**
	 * Find an existing item in the cart by product ID and variation.
	 */
	private findExistingItem(
		state: Cart,
		productId: number,
		variation?: SelectedAttributes[]
	): CartItem | undefined {
		return state.items.find( ( item ) => {
			if ( item.id !== productId ) {
				return false;
			}

			// For variations, check that attributes match
			if ( variation && variation.length > 0 ) {
				return doesCartItemMatchAttributes( item, variation );
			}

			// For simple products, just match by ID
			return ! item.variation || item.variation.length === 0;
		} );
	}

	/**
	 * Create an optimistic update for adding an item.
	 */
	private optimisticallyAddItem(
		state: Cart,
		productId: number,
		quantity: number,
		variation?: SelectedAttributes[]
	): Cart {
		const existingItem = this.findExistingItem(
			state,
			productId,
			variation
		);

		if ( existingItem ) {
			// Check if item is sold individually
			if (
				isCartItem( existingItem ) &&
				existingItem.sold_individually
			) {
				// Don't increase quantity for sold_individually items
				return state;
			}

			return {
				...state,
				items: state.items.map( ( item ) =>
					item.key === existingItem.key
						? { ...item, quantity: item.quantity + quantity }
						: item
				),
			};
		}

		// Create optimistic item (server will fill in full details)
		const optimisticItem: OptimisticCartItem = {
			key: `temp-${ productId }-${ Date.now() }`,
			id: productId,
			quantity,
			type: variation ? 'variation' : 'simple',
			...( variation && { variation: variation as CartVariationItem[] } ),
		};

		return {
			...state,
			items: [ ...state.items, optimisticItem as unknown as CartItem ],
		};
	}

	/**
	 * Dispatch a legacy custom event for third-party compatibility.
	 */
	private dispatchLegacyEvent( eventName: string, detail: unknown ): void {
		if ( typeof window !== 'undefined' ) {
			window.dispatchEvent(
				new CustomEvent( eventName, {
					detail,
				} )
			);
		}
	}
}

/**
 * Factory function to create CartActions instance.
 */
export function createCartActions( config: CartActionsConfig ): CartActions {
	return new CartActions( config );
}
