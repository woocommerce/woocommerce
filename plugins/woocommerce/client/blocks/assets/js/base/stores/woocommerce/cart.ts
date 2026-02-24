/**
 * External dependencies
 */
import { getConfig, store } from '@wordpress/interactivity';
import type {
	Cart,
	CartItem,
	CartVariationItem,
	ApiErrorResponse,
	CartResponseTotals,
	Currency,
} from '@woocommerce/types';
import type {
	Store as StoreNotices,
	Notice,
} from '@woocommerce/stores/store-notices';

/**
 * Internal dependencies
 */
import { triggerAddedToCartEvent } from './legacy-events';
import {
	createMutationQueue,
	MutationRequest,
	type MutationQueue,
	type MutationResult,
} from './mutation-batcher';

export type WooCommerceConfig = {
	products?: {
		[ productId: number ]: ProductData;
	};
	messages?: {
		addedToCartText?: string;
	};
	placeholderImgSrc?: string;
	currency?: Currency;
	nonOptimisticProperties?: string[];
};

export type SelectedAttributes = Omit< CartVariationItem, 'raw_attribute' >;

export type OptimisticCartItem = {
	key?: string | undefined;
	id: number;
	quantity: number;
	variation?: CartVariationItem[];
	type: string;
};

export type ClientCartItem = Omit<
	OptimisticCartItem,
	'variation' | 'quantity'
> & {
	variation?: SelectedAttributes[];
	/** The target quantity (absolute). Either this or quantityToAdd must be provided. */
	quantity?: number;
	/** Optional: add this delta to current quantity instead of setting absolute quantity */
	quantityToAdd?: number;
};

export type VariationData = {
	attributes: Record< string, string >;
	is_in_stock: boolean;
	sold_individually: boolean;
	price_html?: string;
	image_id?: number;
	availability?: string;
	variation_description?: string;
	sku?: string;
	weight?: string;
	dimensions?: string;
	min?: number;
	max?: number;
	step?: number;
};

export type ProductData = {
	type: string;
	is_in_stock: boolean;
	sold_individually: boolean;
	price_html?: string;
	image_id?: number;
	availability?: string;
	sku?: string;
	weight?: string;
	dimensions?: string;
	min?: number;
	max?: number;
	step?: number;
	variations?: Record< number, VariationData >;
};

type CartUpdateOptions = { showCartUpdatesNotices?: boolean };

export type Store = {
	state: {
		errorMessages?: {
			[ key: string ]: string;
		};
		restUrl: string;
		nonce: string;
		cart: Omit< Cart, 'items' > & {
			items: ( OptimisticCartItem | CartItem )[];
			totals: CartResponseTotals;
		};
	};
	actions: {
		removeCartItem: ( key: string ) => void;
		addCartItem: (
			args: ClientCartItem,
			options?: CartUpdateOptions
		) => void;
		batchAddCartItems: (
			items: ClientCartItem[],
			options?: CartUpdateOptions
		) => void;
		// Todo: Check why if I switch to an async function here the types of the store stop working.
		refreshCartItems: () => void;
		waitForIdle: () => void;
		showNoticeError: ( error: Error | ApiErrorResponse ) => void;
		updateNotices: ( notices: Notice[], removeOthers?: boolean ) => void;
	};
};

type QuantityChanges = {
	cartItemsPendingQuantity?: string[];
	cartItemsPendingDelete?: string[];
	productsPendingAdd?: number[];
};

// Guard to distinguish between optimistic and cart items.
function isCartItem( item: OptimisticCartItem | CartItem ): item is CartItem {
	return 'name' in item;
}

function isApiErrorResponse(
	res: Response,
	json: unknown
): json is ApiErrorResponse {
	return ! res.ok;
}

function generateError( error: ApiErrorResponse ): Error {
	return Object.assign( new Error( error.message || 'Unknown error.' ), {
		code: error.code || 'unknown_error',
	} );
}

const generateErrorNotice = ( error: Error | ApiErrorResponse ): Notice => ( {
	notice: error.message,
	type: 'error',
	dismissible: true,
} );

const generateInfoNotice = ( message: string ): Notice => ( {
	notice: message,
	type: 'notice',
	dismissible: true,
} );

const getInfoNoticesFromCartUpdates = (
	oldCart: Store[ 'state' ][ 'cart' ],
	newCart: Cart,
	quantityChanges: QuantityChanges
): Notice[] => {
	const oldItems = oldCart.items;
	const newItems = newCart.items;

	const {
		productsPendingAdd: pendingAdd = [],
		cartItemsPendingQuantity: pendingQuantity = [],
		cartItemsPendingDelete: pendingDelete = [],
	} = quantityChanges;

	const autoDeletedToNotify = oldItems.filter(
		( old ) =>
			old.key &&
			isCartItem( old ) &&
			! newItems.some( ( item ) => old.key === item.key ) &&
			! pendingDelete.includes( old.key )
	);

	const autoUpdatedToNotify = newItems.filter( ( item ) => {
		if ( ! isCartItem( item ) ) {
			return false;
		}
		const old = oldItems.find( ( o ) => o.key === item.key );
		return old
			? ! pendingQuantity.includes( item.key ) &&
					item.quantity !== old.quantity
			: ! pendingAdd.includes( item.id );
	} );
	return [
		...autoDeletedToNotify.map( ( item ) =>
			// TODO: move the message template to iAPI config.
			generateInfoNotice(
				'"%s" was removed from your cart.'.replace( '%s', item.name )
			)
		),
		...autoUpdatedToNotify.map( ( item ) =>
			// TODO: move the message template to iAPI config.
			generateInfoNotice(
				'The quantity of "%1$s" was changed to %2$d.'
					.replace( '%1$s', item.name )
					.replace( '%2$d', item.quantity.toString() )
			)
		),
	];
};

// Same as the one in /assets/js/base/utils/variations/does-cart-item-match-attributes.ts.
const doesCartItemMatchAttributes = (
	cartItem: OptimisticCartItem,
	selectedAttributes: SelectedAttributes[]
) => {
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
			// eslint-disable-next-line
			raw_attribute,
			value,
		}: {
			raw_attribute: string;
			value: string;
		} ) =>
			selectedAttributes.some( ( item: SelectedAttributes ) => {
				return (
					item.attribute === raw_attribute &&
					( item.value.toLowerCase() === value.toLowerCase() ||
						( item.value && value === '' ) ) // Handle "any" attribute type
				);
			} )
	);
};

let pendingRefresh = false;
let refreshTimeout = 3000;

function emitSyncEvent( {
	quantityChanges,
}: {
	quantityChanges: QuantityChanges;
} ) {
	window.dispatchEvent(
		new CustomEvent( 'wc-blocks_store_sync_required', {
			detail: {
				type: 'from_iAPI',
				quantityChanges,
			},
		} )
	);
}

/**
 * Cart request queue singleton
 *
 * Lazily initialized on first use since state isn't available at module load.
 * Queues cart requests and handles optimistic updates and reconciliation.
 */
let cartQueue: MutationQueue< Cart > | null = null;

/**
 * Send a cart request through the queue.
 *
 * Handles optimistic updates, request queuing, and state reconciliation.
 */
function sendCartRequest(
	stateRef: Store[ 'state' ],
	options: MutationRequest< Cart >
): Promise< MutationResult< Cart > > {
	// Lazily initialize queue on first use.
	if ( ! cartQueue ) {
		cartQueue = createMutationQueue< Cart >( {
			endpoint: `${ stateRef.restUrl }wc/store/v1/batch`,
			getHeaders: () => ( {
				Nonce: stateRef.nonce,
			} ),
			takeSnapshot: () => JSON.parse( JSON.stringify( stateRef.cart ) ),
			rollback: ( snapshot ) => {
				stateRef.cart = snapshot;
			},
			commit: ( serverState ) => {
				stateRef.cart = serverState;
			},
		} );
	}

	return cartQueue.submit( options );
}

// Todo: export this store once the store is public.
const { state, actions } = store< Store >(
	'woocommerce',
	{
		actions: {
			*removeCartItem( key: string ) {
				// Track what changes we're making for notice comparison.
				const quantityChanges: QuantityChanges = {
					cartItemsPendingDelete: [ key ],
				};

				// Capture cart state after optimistic updates for notice comparison.
				let cartAfterOptimistic: typeof state.cart | null = null;

				try {
					const result = yield sendCartRequest( state, {
						path: '/wc/store/v1/cart/remove-item',
						method: 'POST',
						body: { key },
						applyOptimistic: () => {
							state.cart.items = state.cart.items.filter(
								( item ) => item.key !== key
							);
							// Capture state after optimistic update.
							cartAfterOptimistic = JSON.parse(
								JSON.stringify( state.cart )
							);
						},
						// Side effects run synchronously during reconciliation,
						// before isProcessing clears. This prevents
						// refreshCartItems from running during these events.
						onSettled: ( { success } ) => {
							if ( success ) {
								emitSyncEvent( { quantityChanges } );
							}
						},
					} );

					// Show notices from server response.
					const cart = result.data as Cart;
					if ( cart && cartAfterOptimistic ) {
						const infoNotices = getInfoNoticesFromCartUpdates(
							cartAfterOptimistic,
							cart,
							quantityChanges
						);
						const errorNotices =
							cart.errors.map( generateErrorNotice );
						yield actions.updateNotices(
							[ ...infoNotices, ...errorNotices ],
							true
						);
					}
				} catch ( error ) {
					actions.showNoticeError( error as Error );
				}
			},

			*addCartItem(
				{ id, key, quantity, quantityToAdd, variation }: ClientCartItem,
				{ showCartUpdatesNotices = true }: CartUpdateOptions = {}
			) {
				if ( quantity !== undefined && quantityToAdd !== undefined ) {
					throw new Error(
						'addCartItem: pass either quantity or quantityToAdd, not both.'
					);
				}

				const a11yModulePromise = import( '@wordpress/a11y' );

				// Find existing item
				const existingItem = state.cart.items.find( ( cartItem ) => {
					if ( cartItem.type === 'variation' ) {
						if (
							id !== cartItem.id ||
							! cartItem.variation ||
							! variation ||
							cartItem.variation.length !== variation.length
						) {
							return false;
						}
						return doesCartItemMatchAttributes(
							cartItem,
							variation
						);
					}
					return key ? key === cartItem.key : id === cartItem.id;
				} );

				// Determine the target quantity.
				// If quantityToAdd is provided, calculate target based on current
				// cart state (which includes optimistic updates from previous clicks).
				// This ensures rapid clicks compound correctly.
				let targetQuantity: number;
				if ( typeof quantityToAdd === 'number' ) {
					const currentQuantity = existingItem?.quantity ?? 0;
					targetQuantity = currentQuantity + quantityToAdd;
				} else if ( typeof quantity === 'number' ) {
					targetQuantity = quantity;
				} else {
					// Neither provided - default to 1
					targetQuantity = 1;
				}

				// Only treat as update if the item has a key (server-confirmed item).
				// Optimistic items don't have keys, so we should add them instead.
				const isUpdate = !! existingItem?.key;
				const endpoint = isUpdate ? 'update-item' : 'add-item';

				// Track what changes we're making for notice comparison.
				const quantityChanges: QuantityChanges = isUpdate
					? {
							cartItemsPendingQuantity: existingItem?.key
								? [ existingItem.key ]
								: [],
					  }
					: { productsPendingAdd: [ id ] };

				// Prepare the item to send.
				let itemToSend: OptimisticCartItem;
				if ( isUpdate && existingItem ) {
					// Server-confirmed item: include the key for update-item endpoint.
					itemToSend = { ...existingItem, quantity: targetQuantity };
				} else {
					// New item or optimistic item: build fresh for add-item endpoint.
					// For optimistic items (existingItem without key), calculate delta
					// since add-item adds to existing quantity, not sets it.
					const quantityToSend = existingItem
						? targetQuantity - existingItem.quantity
						: targetQuantity;

					itemToSend = {
						id,
						quantity: quantityToSend,
						...( variation && { variation } ),
					} as OptimisticCartItem;
				}

				// Capture cart state after optimistic updates for notice comparison.
				let cartAfterOptimistic: typeof state.cart | null = null;

				try {
					const result = yield sendCartRequest( state, {
						path: `/wc/store/v1/cart/${ endpoint }`,
						method: 'POST',
						body: itemToSend,
						applyOptimistic: () => {
							if ( existingItem ) {
								// Update existing item's quantity (whether server-confirmed or optimistic).
								const isSoldIndividually =
									isCartItem( existingItem ) &&
									existingItem.sold_individually;
								if ( ! isSoldIndividually ) {
									existingItem.quantity = targetQuantity;
								}
							} else {
								// No existing item: push new optimistic item.
								state.cart.items.push( itemToSend );
							}
							// Capture state after optimistic update.
							cartAfterOptimistic = JSON.parse(
								JSON.stringify( state.cart )
							);
						},
						// Side effects run synchronously during reconciliation,
						// before isProcessing clears. This prevents
						// refreshCartItems from running during these events.
						onSettled: ( { success } ) => {
							if ( success ) {
								// Dispatch legacy event
								triggerAddedToCartEvent( {
									preserveCartData: true,
								} );

								// Dispatch sync event
								emitSyncEvent( { quantityChanges } );
							}
						},
					} );

					// Success - handle side effects that don't trigger refreshCartItems
					const cart = result.data as Cart;

					// Show notices if enabled
					if (
						showCartUpdatesNotices &&
						cart &&
						cartAfterOptimistic
					) {
						const infoNotices = getInfoNoticesFromCartUpdates(
							cartAfterOptimistic,
							cart,
							quantityChanges
						);
						const errorNotices =
							cart.errors.map( generateErrorNotice );
						yield actions.updateNotices(
							[ ...infoNotices, ...errorNotices ],
							true
						);
					}

					// Announce to screen readers
					const { messages } = getConfig(
						'woocommerce'
					) as WooCommerceConfig;
					if ( messages?.addedToCartText ) {
						const { speak } = yield a11yModulePromise;
						speak( messages.addedToCartText, 'polite' );
					}
				} catch ( error ) {
					// Show error notice
					actions.showNoticeError( error as Error );
				}
			},

			*batchAddCartItems(
				items: ClientCartItem[],
				{ showCartUpdatesNotices = true }: CartUpdateOptions = {}
			) {
				const a11yModulePromise = import( '@wordpress/a11y' );
				const quantityChanges: QuantityChanges = {};

				try {
					// Submit each item through the batcher. They'll be
					// collected into a single batch request automatically.
					const promises = items.map( ( item, index ) => {
						const existingItem = state.cart.items.find(
							( { id: productId } ) => item.id === productId
						);

						let quantity: number;
						if ( typeof item.quantityToAdd === 'number' ) {
							const currentQuantity = existingItem?.quantity ?? 0;
							quantity = currentQuantity + item.quantityToAdd;
						} else {
							quantity = item.quantity ?? 1;
						}
						const isUpdate = !! existingItem?.key;
						const endpoint = isUpdate ? 'update-item' : 'add-item';

						let itemToSend: OptimisticCartItem;
						if ( isUpdate && existingItem ) {
							itemToSend = {
								key: existingItem.key,
								id: existingItem.id,
								quantity,
							} as OptimisticCartItem;
							quantityChanges.cartItemsPendingQuantity = [
								...( quantityChanges.cartItemsPendingQuantity ??
									[] ),
								existingItem.key as string,
							];
						} else {
							const quantityToSend = existingItem
								? quantity - existingItem.quantity
								: quantity;
							itemToSend = {
								id: item.id,
								quantity: quantityToSend,
								...( item.variation && {
									variation: item.variation,
								} ),
							} as OptimisticCartItem;
							quantityChanges.productsPendingAdd = [
								...( quantityChanges.productsPendingAdd ?? [] ),
								item.id,
							];
						}

						const isLastItem = index === items.length - 1;

						return sendCartRequest( state, {
							path: `/wc/store/v1/cart/${ endpoint }`,
							method: 'POST',
							body: itemToSend,
							applyOptimistic: () => {
								if ( existingItem ) {
									existingItem.quantity = quantity;
								} else {
									state.cart.items.push( itemToSend );
								}
							},
							// Only fire events on the last item to avoid
							// duplicate notifications mid-batch.
							// Fire events when ANY item in the batch
							// succeeded (data is set from the last
							// successful server state). Only the last
							// item's callback fires to avoid duplicates.
							onSettled: isLastItem
								? ( { data } ) => {
										if ( data ) {
											triggerAddedToCartEvent( {
												preserveCartData: true,
											} );
											emitSyncEvent( {
												quantityChanges,
											} );
										}
								  }
								: undefined,
						} );
					} );

					// Capture cart state after optimistic updates for notices.
					const cartAfterOptimistic = JSON.parse(
						JSON.stringify( state.cart )
					);

					const results: PromiseSettledResult<
						MutationResult< Cart >
					>[] = yield Promise.allSettled( promises );

					// Find the last successful result for notices/a11y.
					const lastSuccess = [ ...results ]
						.reverse()
						.find(
							(
								r
							): r is PromiseFulfilledResult<
								MutationResult< Cart >
							> => r.status === 'fulfilled' && r.value.success
						);

					if ( lastSuccess ) {
						const cart = lastSuccess.value.data as Cart;

						if ( showCartUpdatesNotices ) {
							const infoNotices = getInfoNoticesFromCartUpdates(
								cartAfterOptimistic,
								cart,
								quantityChanges
							);
							const errorNotices =
								cart.errors.map( generateErrorNotice );
							yield actions.updateNotices(
								[ ...infoNotices, ...errorNotices ],
								true
							);
						}

						const { messages } = getConfig(
							'woocommerce'
						) as WooCommerceConfig;
						if ( messages?.addedToCartText ) {
							const { speak } = yield a11yModulePromise;
							speak( messages.addedToCartText, 'polite' );
						}
					}

					// Show error notices for failed items.
					const errorNotices = results
						.filter(
							( r ): r is PromiseRejectedResult =>
								r.status === 'rejected'
						)
						.map( ( r ) =>
							generateErrorNotice( r.reason as ApiErrorResponse )
						);
					if ( errorNotices.length > 0 ) {
						yield actions.updateNotices( errorNotices );
					}
				} catch ( error ) {
					actions.showNoticeError( error as Error );
				}
			},

			*refreshCartItems() {
				// Skip if queue is processing - it will apply server state when done
				if ( cartQueue?.getStatus().isProcessing ) {
					return;
				}

				// Skips if there's a pending request.
				if ( pendingRefresh ) return;

				pendingRefresh = true;

				try {
					const res: Response = yield fetch(
						`${ state.restUrl }wc/store/v1/cart`,
						{
							method: 'GET',
							cache: 'no-store',
							headers: { 'Content-Type': 'application/json' },
						}
					);
					const json: Cart = yield res.json();

					// Checks if the response contains an error.
					if ( isApiErrorResponse( res, json ) )
						throw generateError( json );

					// If the batcher started a cycle while we were fetching,
					// discard this response — the batcher will reconcile.
					if ( cartQueue?.getStatus().isProcessing ) {
						return;
					}

					// Updates the local cart.
					state.cart = json;

					// Resets the timeout.
					refreshTimeout = 3000;
				} catch ( error ) {
					// Tries again after the timeout.
					setTimeout( actions.refreshCartItems, refreshTimeout );

					// Increases the timeout exponentially.
					refreshTimeout *= 2;
				} finally {
					pendingRefresh = false;
				}
			},

			*waitForIdle() {
				if ( cartQueue ) {
					yield cartQueue.waitForIdle();
				}
			},

			*showNoticeError( error: Error | ApiErrorResponse ) {
				// Todo: Use the module exports instead of `store()` once the store-notices
				// store is public.
				yield import( '@woocommerce/stores/store-notices' );
				const { actions: noticeActions } = store< StoreNotices >(
					'woocommerce/store-notices',
					{},
					{
						lock: 'I acknowledge that using a private store means my plugin will inevitably break on the next store release.',
					}
				);

				const { code, message } = error as ApiErrorResponse;

				const userFriendlyMessage =
					state.errorMessages?.[ code ] || message;

				// Todo: Check what should happen if the notice is already displayed.
				noticeActions.addNotice( {
					notice: userFriendlyMessage,
					type: 'error',
					dismissible: true,
				} );

				// Emmits console.error for troubleshooting.
				// eslint-disable-next-line no-console
				console.error( error );
			},

			*updateNotices( newNotices: Notice[] = [], removeOthers = false ) {
				// Todo: Use the module exports instead of `store()` once the store-notices
				// store is public.
				yield import( '@woocommerce/stores/store-notices' );
				const { state: noticeState, actions: noticeActions } =
					store< StoreNotices >(
						'woocommerce/store-notices',
						{},
						{
							lock: 'I acknowledge that using a private store means my plugin will inevitably break on the next store release.',
						}
					);

				// Todo: Check what should happen if the notice is already displayed.
				const noticeIds = newNotices.map( ( notice ) =>
					noticeActions.addNotice( notice )
				);

				const { notices } = noticeState;
				if ( removeOthers ) {
					notices
						.map( ( { id } ) => id )
						.filter( ( id ) => ! noticeIds.includes( id ) )
						.forEach( ( id ) => noticeActions.removeNotice( id ) );
				}
			},
		},
	},
	{ lock: true }
);

window.addEventListener(
	'wc-blocks_store_sync_required',
	async ( event: Event ) => {
		const customEvent = event as CustomEvent< {
			type: string;
			id: number;
		} >;
		if ( customEvent.detail.type === 'from_@wordpress/data' ) {
			actions.refreshCartItems();
		}
	}
);
