/**
 * Cart Actions Consumer API
 *
 * This module provides a clean, developer-friendly API for cart mutations.
 * It handles:
 * - Automatic context capture at submission time
 * - Optimistic updates with proper item handling
 * - Legacy event firing for backwards compatibility
 * - Delta and absolute quantity updates
 */

/**
 * External dependencies
 */
import type { Cart, CartItem } from '@woocommerce/types';
import {
	triggerAddedToCartEvent,
	triggerAddingToCartEvent,
} from '@woocommerce/base-utils';

/**
 * Internal dependencies
 */
import type {
	CartMutationQueue,
	MutationResult,
	MutationRequest,
} from './types';

/**
 * Variation attribute for add to cart requests.
 */
export interface VariationAttribute {
	attribute: string;
	value: string;
}

/**
 * Options for add to cart action.
 */
export interface AddToCartOptions {
	/** Product ID to add */
	productId: number;
	/** Quantity to add (default: 1) */
	quantity?: number;
	/** Variation attributes for variable products */
	variation?: VariationAttribute[];
	/** Additional data to send with the request */
	additionalData?: Record< string, unknown >;
}

/**
 * Options for update quantity action.
 */
export interface UpdateQuantityOptions {
	/** Cart item key to update */
	cartItemKey: string;
	/** New absolute quantity (mutually exclusive with delta) */
	quantity?: number;
	/** Quantity change delta (mutually exclusive with quantity) */
	delta?: number;
}

/**
 * Options for remove item action.
 */
export interface RemoveItemOptions {
	/** Cart item key to remove */
	cartItemKey: string;
}

/**
 * Context provider function type.
 * Returns current context values for automatic capture.
 */
export type ContextProvider = () => {
	productId?: number;
	quantity?: number;
};

/**
 * State provider function type.
 * Returns current cart state for delta calculations.
 */
export type StateProvider = () => Cart;

/**
 * CartActions provides a clean API for cart mutations.
 * It wraps the CartMutationQueue with cart-specific logic.
 */
export class CartActions {
	private queue: CartMutationQueue;
	private contextProvider: ContextProvider;
	private stateProvider: StateProvider;

	/**
	 * Create a new CartActions instance.
	 *
	 * @param queue           The mutation queue to use.
	 * @param contextProvider Function returning current context (productId, quantity).
	 * @param stateProvider   Function returning current cart state.
	 */
	constructor(
		queue: CartMutationQueue,
		contextProvider: ContextProvider,
		stateProvider: StateProvider
	) {
		this.queue = queue;
		this.contextProvider = contextProvider;
		this.stateProvider = stateProvider;
	}

	/**
	 * Add an item to the cart.
	 *
	 * If productId is not provided, it will be captured from the context provider.
	 * This enables automatic context capture for simpler consumer code.
	 *
	 * @param options Add to cart options (all optional if context provides productId).
	 * @return Promise that resolves with the mutation result.
	 */
	async addToCart(
		options: Partial< AddToCartOptions > = {}
	): Promise< MutationResult< Cart > > {
		// Capture context values immediately
		const context = this.contextProvider();
		const productId = options.productId ?? context.productId;
		const quantity = options.quantity ?? context.quantity ?? 1;

		if ( ! productId ) {
			return {
				success: false,
				error: new Error( 'Product ID is required for add to cart' ),
			};
		}

		// Fire the adding to cart event
		triggerAddingToCartEvent();

		return this.queue.submit< Cart >( {
			key: `add-to-cart-${ productId }`,
			mutate: (): MutationRequest => ( {
				path: '/wc/store/v1/cart/add-item',
				method: 'POST',
				body: {
					id: productId,
					quantity,
					variation: options.variation,
					...options.additionalData,
				},
			} ),
			optimisticUpdate: ( state: Cart ): Cart => {
				return this.optimisticallyAddItem( state, productId, quantity );
			},
			onSettled: ( result: MutationResult< Cart > ): void => {
				if ( result.success ) {
					// Fire legacy event for third-party compatibility
					triggerAddedToCartEvent( { preserveCartData: true } );

					// Also dispatch custom event with details
					if ( typeof window !== 'undefined' ) {
						window.dispatchEvent(
							new CustomEvent( 'wc-blocks_added_to_cart', {
								detail: {
									productId,
									quantity,
								},
							} )
						);
					}
				}
			},
		} );
	}

	/**
	 * Update the quantity of a cart item.
	 *
	 * Can use either absolute quantity or delta (change amount).
	 * If both are provided, absolute quantity takes precedence.
	 *
	 * @param options Update quantity options.
	 * @return Promise that resolves with the mutation result.
	 */
	async updateQuantity(
		options: UpdateQuantityOptions
	): Promise< MutationResult< Cart > > {
		const { cartItemKey, quantity, delta } = options;

		// Get current item to calculate new quantity if using delta
		const currentState = this.stateProvider();
		const currentItem = currentState.items.find(
			( item ) => item.key === cartItemKey
		);

		if ( ! currentItem ) {
			return {
				success: false,
				error: new Error( `Cart item ${ cartItemKey } not found` ),
			};
		}

		// Calculate new quantity
		let newQuantity: number;
		if ( quantity !== undefined ) {
			newQuantity = quantity;
		} else if ( delta !== undefined ) {
			newQuantity = currentItem.quantity + delta;
		} else {
			return {
				success: false,
				error: new Error( 'Either quantity or delta must be provided' ),
			};
		}

		// Validate new quantity
		if ( newQuantity < 0 ) {
			newQuantity = 0;
		}

		// If quantity is 0, remove the item instead
		if ( newQuantity === 0 ) {
			return this.removeItem( { cartItemKey } );
		}

		return this.queue.submit< Cart >( {
			key: `update-quantity-${ cartItemKey }`,
			mutate: (): MutationRequest => ( {
				path: '/wc/store/v1/cart/update-item',
				method: 'POST',
				body: {
					key: cartItemKey,
					quantity: newQuantity,
				},
			} ),
			optimisticUpdate: ( state: Cart ): Cart => {
				return {
					...state,
					items: state.items.map( ( item ) =>
						item.key === cartItemKey
							? { ...item, quantity: newQuantity }
							: item
					),
				};
			},
		} );
	}

	/**
	 * Remove an item from the cart.
	 *
	 * @param options Remove item options.
	 * @return Promise that resolves with the mutation result.
	 */
	async removeItem(
		options: RemoveItemOptions
	): Promise< MutationResult< Cart > > {
		const { cartItemKey } = options;

		return this.queue.submit< Cart >( {
			key: `remove-item-${ cartItemKey }`,
			mutate: (): MutationRequest => ( {
				path: '/wc/store/v1/cart/remove-item',
				method: 'POST',
				body: {
					key: cartItemKey,
				},
			} ),
			optimisticUpdate: ( state: Cart ): Cart => {
				return {
					...state,
					items: state.items.filter(
						( item ) => item.key !== cartItemKey
					),
				};
			},
			onSettled: ( result: MutationResult< Cart > ): void => {
				if ( result.success && typeof window !== 'undefined' ) {
					window.dispatchEvent(
						new CustomEvent( 'wc-blocks_removed_from_cart', {
							detail: { cartItemKey },
						} )
					);
				}
			},
		} );
	}

	/**
	 * Add multiple items to the cart at once.
	 * All items will be batched into a single request.
	 *
	 * @param items Array of items to add.
	 * @return Promise that resolves when all items are added.
	 */
	async batchAddItems(
		items: AddToCartOptions[]
	): Promise< MutationResult< Cart >[] > {
		return Promise.all( items.map( ( item ) => this.addToCart( item ) ) );
	}

	/**
	 * Optimistically add an item to the cart state.
	 * Handles both new items and quantity increments.
	 */
	private optimisticallyAddItem(
		state: Cart,
		productId: number,
		quantity: number
	): Cart {
		const existingItem = state.items.find(
			( item ) => item.id === productId
		);

		if ( existingItem ) {
			// Increment quantity of existing item
			return {
				...state,
				items: state.items.map( ( item ) =>
					item.id === productId
						? { ...item, quantity: item.quantity + quantity }
						: item
				),
			};
		}

		// Add new item with placeholder data
		// The server response will provide the full item data
		const newItem: CartItem = {
			key: `temp-${ productId }-${ Date.now() }`,
			id: productId,
			type: 'simple',
			quantity,
			catalog_visibility: 'visible',
			quantity_limits: {
				minimum: 1,
				maximum: 9999,
				multiple_of: 1,
				editable: true,
			},
			name: '',
			summary: '',
			short_description: '',
			description: '',
			sku: '',
			low_stock_remaining: null,
			backorders_allowed: false,
			show_backorder_badge: false,
			sold_individually: false,
			permalink: '',
			images: [],
			variation: [],
			prices: {
				currency_code: state.totals.currency_code,
				currency_symbol: state.totals.currency_symbol,
				currency_minor_unit: state.totals.currency_minor_unit,
				currency_decimal_separator:
					state.totals.currency_decimal_separator,
				currency_thousand_separator:
					state.totals.currency_thousand_separator,
				currency_prefix: state.totals.currency_prefix,
				currency_suffix: state.totals.currency_suffix,
				price: '0',
				regular_price: '0',
				sale_price: '0',
				price_range: null,
				raw_prices: {
					precision: 6,
					price: '0',
					regular_price: '0',
					sale_price: '0',
				},
			},
			totals: {
				currency_code: state.totals.currency_code,
				currency_symbol: state.totals.currency_symbol,
				currency_minor_unit: state.totals.currency_minor_unit,
				currency_decimal_separator:
					state.totals.currency_decimal_separator,
				currency_thousand_separator:
					state.totals.currency_thousand_separator,
				currency_prefix: state.totals.currency_prefix,
				currency_suffix: state.totals.currency_suffix,
				line_subtotal: '0',
				line_subtotal_tax: '0',
				line_total: '0',
				line_total_tax: '0',
			},
			extensions: {},
			item_data: [],
		};

		return {
			...state,
			items: [ ...state.items, newItem ],
		};
	}
}

/**
 * Create a CartActions instance with the given dependencies.
 *
 * @param queue           The mutation queue to use.
 * @param contextProvider Function returning current context.
 * @param stateProvider   Function returning current cart state.
 * @return CartActions instance.
 */
export function createCartActions(
	queue: CartMutationQueue,
	contextProvider: ContextProvider,
	stateProvider: StateProvider
): CartActions {
	return new CartActions( queue, contextProvider, stateProvider );
}
