/**
 * External dependencies
 */
import { getConfig, store } from '@wordpress/interactivity';
import type { AsyncAction, TypeYield } from '@wordpress/interactivity';
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
import { doesCartItemMatchAttributes } from '../../utils/variations/does-cart-item-match-attributes';

export type WooCommerceConfig = {
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

type CartUpdateOptions = {
	showCartUpdatesNotices?: boolean;
	/**
	 * When `true`, suppresses `addCartItem`'s per-item post-add side
	 * effects: the screen-reader announcement, the legacy added-to-cart
	 * event, the cross-store sync event, and (on server rejection) its own
	 * error notice. Defaults to `false`.
	 *
	 * A caller that adds several items as one grouped gesture sets this to
	 * `true` so it can fire those side effects once for the whole group —
	 * using each item's resolved {@link AddCartItemResult} to learn the
	 * outcome — instead of once per item.
	 */
	suppressPostAddSideEffects?: boolean;
};

/**
 * The outcome of a single `addCartItem` mutation.
 *
 * `addCartItem` never rejects: a server-rejected mutation resolves as
 * `{ success: false, error }` rather than throwing, so callers can await it
 * unconditionally.
 */
export type AddCartItemResult =
	| { success: true }
	| {
			success: false;
			/**
			 * The error raised for this item. `error.message` is the raw
			 * server-provided message and `error.code` (see
			 * {@link generateError}) is the server error code.
			 */
			error: Error;
	  };

export type Store = {
	state: {
		errorMessages?: {
			[ key: string ]: string;
		};
		restUrl: string;
		nonce: string;
		findItemInCart: ( args: {
			id: ClientCartItem[ 'id' ];
			key?: ClientCartItem[ 'key' ];
			variation?: ClientCartItem[ 'variation' ];
		} ) => CartItem | OptimisticCartItem | undefined;
		cart: Omit< Cart, 'items' > & {
			items: ( OptimisticCartItem | CartItem )[];
			totals: CartResponseTotals;
		};
	};
	actions: {
		removeCartItem: ( key: string ) => Promise< void >;
		addCartItem: (
			args: ClientCartItem,
			options?: CartUpdateOptions
		) => Promise< AddCartItemResult >;
		// Todo: Check why if I switch to an async function here the types of the store stop working.
		refreshCartItems: () => Promise< void >;
		waitForIdle: () => Promise< void >;
		showNoticeError: ( error: Error | ApiErrorResponse ) => Promise< void >;
		updateNotices: (
			notices: Notice[],
			removeOthers?: boolean
		) => Promise< void >;
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

/**
 * Derives the auto-update and auto-removal info notices from the diff between
 * the post-optimistic cart and the committed server cart.
 *
 * Auto-removal notices fire for lines present in `oldCart` that the server
 * dropped entirely (stock removal, product deletion, etc.). Because
 * `oldCart` is the post-optimistic snapshot, user-initiated removals are
 * already absent and do not produce spurious notices.
 *
 * Auto-update notices fire for server lines whose quantity differs from the
 * post-optimistic snapshot. When an add is exact, the post-optimistic
 * snapshot already reflects the same line and quantity the server confirms,
 * so the diff is empty and no notice fires. Genuine server-initiated changes
 * (stock cap, sold-individually clamp, concurrent mutation) still notify
 * because they make the compared line diverge.
 *
 * @param oldCart The post-optimistic cart snapshot used as the diff baseline.
 * @param newCart The committed server cart to diff against.
 * @return The list of info notices to surface to the shopper.
 */
const getInfoNoticesFromCartUpdates = (
	oldCart: Store[ 'state' ][ 'cart' ],
	newCart: Cart
): Notice[] => {
	const oldItems = oldCart.items;
	const newItems = newCart.items;

	// Items auto-removed by the server (stock change, product deleted, etc.).
	// We pass the optimistic snapshot as oldCart, so user-initiated removals
	// are already absent and do not generate spurious notices here.
	const autoDeletedToNotify = oldItems.filter(
		( old ) =>
			isCartItem( old ) &&
			! newItems.some( ( item ) => old.key === item.key )
	);

	// Items whose quantity was adjusted by the server (stock cap, sold-individually).
	// By default a line is compared optimistic → server, so intentional user
	// changes are already reflected in oldItems and do not trigger this notice.
	const autoUpdatedToNotify = newItems.filter( ( item ) => {
		if ( ! isCartItem( item ) ) {
			return false;
		}
		const old = oldItems.find( ( o ) => o.key === item.key );
		return old && item.quantity !== old.quantity;
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

let pendingRefresh = false;
let refreshTimeout = 3000;
let resolveNonceReady: ( () => void ) | null = null;
const isNonceReady = new Promise< void >( ( resolve ) => {
	resolveNonceReady = resolve;
} );

/**
 * Dispatches the cross-store cart-sync event other stores listen for.
 *
 * Fires a `wc-blocks_store_sync_required` {@link CustomEvent} on `window`
 * whose `detail` carries `type: 'from_iAPI'` and the supplied
 * `quantityChanges`. Consumers that mutate the cart as one grouped gesture
 * import this at runtime to fire the identical sync contract once for the
 * whole group rather than duplicating the private event shape.
 *
 * @param args                 The sync payload.
 * @param args.quantityChanges The pending cart changes to broadcast (products
 *                             to add, cart items to re-quantity, cart items to
 *                             delete).
 */
export function emitSyncEvent( {
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
async function sendCartRequest(
	stateRef: Store[ 'state' ],
	options: MutationRequest< Cart >
): Promise< MutationResult< Cart > > {
	await isNonceReady;
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
			fetchHandler: async ( ...args ) => {
				const response = await fetch( ...args );
				stateRef.nonce =
					response.headers.get( 'Nonce' ) || stateRef.nonce;
				return response;
			},
		} );
	}

	return cartQueue.submit( options );
}
// Stores are locked to prevent 3PD usage until the API is stable.
const universalLock =
	'I acknowledge that using a private store means my plugin will inevitably break on the next store release.';

// Todo: export this store once the store is public.
const { state } = store< Store >( 'woocommerce', {}, { lock: universalLock } );
const { actions } = store< Store >(
	'woocommerce',
	{
		state: {
			/**
			 * Finds the cart line for a product.
			 *
			 * With a `key`, returns the line for that exact key. Without a
			 * key, returns the standalone per-product line matched by `id`
			 * (and `variation` for variations), explicitly excluding
			 * meta-differentiated lines (`is_standalone_line: false`, e.g. a
			 * bundle child, booking, or add-on configuration) so the keyless
			 * match resolves only the standalone line the product-button count
			 * reflects.
			 */
			findItemInCart( {
				id,
				key,
				variation,
			}: {
				id: ClientCartItem[ 'id' ];
				key?: ClientCartItem[ 'key' ];
				variation?: ClientCartItem[ 'variation' ];
			} ) {
				return state.cart.items.find( ( cartItem ) => {
					if ( key ) {
						return key === cartItem.key;
					}
					// `isCartItem` narrows to server-confirmed lines; for optimistic
					// lines the guard short-circuits the `&&` before `is_standalone_line`
					// is read, so rapid-click compounding on standalone lines is
					// preserved. Keyed lookups short-circuit on the `key` check
					// above and never reach this guard.
					if (
						isCartItem( cartItem ) &&
						! cartItem.is_standalone_line
					) {
						return false;
					}
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
					return id === cartItem.id;
				} );
			},
		},
		actions: {
			*removeCartItem( key: string ): AsyncAction< void > {
				// Track what changes we're making for the sync event.
				const quantityChanges: QuantityChanges = {
					cartItemsPendingDelete: [ key ],
				};

				// Capture cart state after optimistic updates for notice comparison.
				let cartAfterOptimistic: typeof state.cart | null = null;

				try {
					const result = ( yield sendCartRequest( state, {
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
					} ) ) as TypeYield< typeof sendCartRequest >;

					// Show notices from server response.
					const cart = result.data as Cart;
					if ( cart && cartAfterOptimistic ) {
						const infoNotices = getInfoNoticesFromCartUpdates(
							cartAfterOptimistic,
							cart
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
				{
					showCartUpdatesNotices = true,
					suppressPostAddSideEffects = false,
				}: CartUpdateOptions = {}
			): AsyncAction< AddCartItemResult > {
				if ( quantity !== undefined && quantityToAdd !== undefined ) {
					throw new Error(
						'addCartItem: pass either quantity or quantityToAdd, not both.'
					);
				}

				// Keyless-requires-delta invariant. A keyless add always issues
				// `add-item`, whose quantity is a delta added to the existing
				// line; rapid-click compounding relies on that — each click sends
				// its own delta and the server sums them (N -> N+1 -> N+2). An
				// absolute `quantity` on a keyless add would be misread as a delta
				// and corrupt that compounding, so keyless callers must pass
				// `quantityToAdd`. An absolute `quantity` is legitimate only when
				// paired with an explicit `key`: that is the keyed-stepper path
				// (mini-cart / cart-block quantity controls), which targets one
				// known line via `update-item` and sets its quantity outright.
				// Those keyed callers are intentionally exempt from this guard.
				if (
					key === undefined &&
					quantity !== undefined &&
					quantityToAdd === undefined
				) {
					throw new Error(
						'addCartItem: a keyless add must pass quantityToAdd (a delta), not an absolute quantity.'
					);
				}

				const a11yModulePromise = import( '@wordpress/a11y' );

				// Find existing item
				const existingItem = state.findItemInCart( {
					id,
					key,
					variation,
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

				// Endpoint selection is a pure function of the caller-supplied
				// `key`, never of a line matched by id/variation. A keyless add
				// always issues `add-item` with a delta, even when an existing
				// line (including a server-keyed one) matches by product id, so
				// the server owns cart-line identity for adds. Only an explicit
				// caller `key` targets a specific line via `update-item`.
				const isUpdate = !! key;
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
					// Caller-keyed update: target the exact line by key and send
					// the absolute target quantity to the update-item endpoint.
					itemToSend = { ...existingItem, quantity: targetQuantity };
				} else {
					// Keyless add: build a fresh payload for the add-item
					// endpoint and never copy the matched line's key. The amount
					// sent is always a delta — add-item adds to the existing
					// quantity rather than setting it — so a match (by
					// id/variation, possibly carrying a server key) only tells us
					// how much delta is already accounted for in the running
					// optimistic total; with no match we post the full target
					// quantity. The matched line is never sent as an absolute
					// quantity: the posted amount is a function of the delta,
					// not of the match.
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
					const result = ( yield sendCartRequest( state, {
						path: `/wc/store/v1/cart/${ endpoint }`,
						method: 'POST',
						body: itemToSend,
						applyOptimistic: () => {
							if ( existingItem ) {
								// This in-place bump is render-only. It
								// makes the common re-add flicker-free, but it must
								// never feed back into endpoint selection or the
								// posted amount — those are already fixed above as a
								// pure function of key-presence and the delta. On a
								// keyless add the match may bump a server-keyed line's
								// rendered quantity (the accepted, self-correcting
								// meta-only blip the server reconciles away); it must
								// not flip the add into `update-item` or supply an
								// absolute quantity. A future edit that lets this
								// match drive the endpoint or the posted amount
								// resurrects the original "cannot update bundle item"
								// / wrong-line bug.
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
							if ( success && ! suppressPostAddSideEffects ) {
								// Dispatch legacy event
								triggerAddedToCartEvent( {
									preserveCartData: true,
								} );

								// Dispatch sync event
								emitSyncEvent( { quantityChanges } );
							}
						},
					} ) ) as TypeYield< typeof sendCartRequest >;

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
							cart
						);
						const errorNotices =
							cart.errors.map( generateErrorNotice );
						yield actions.updateNotices(
							[ ...infoNotices, ...errorNotices ],
							true
						);
					}

					// Announce to screen readers, unless the caller (a grouped
					// batch consumer) has opted to handle it itself.
					if ( ! suppressPostAddSideEffects ) {
						const { messages } = getConfig(
							'woocommerce'
						) as WooCommerceConfig;
						if ( messages?.addedToCartText ) {
							const { speak } =
								( yield a11yModulePromise ) as Awaited<
									typeof a11yModulePromise
								>;
							speak( messages.addedToCartText, 'polite' );
						}
					}

					return { success: true };
				} catch ( error ) {
					// Show error notice, unless the caller (a grouped batch
					// consumer) has opted to handle it itself.
					if ( ! suppressPostAddSideEffects ) {
						actions.showNoticeError( error as Error );
					}
					return { success: false, error: error as Error };
				}
			},

			*refreshCartItems(): AsyncAction< void > {
				// Skip if queue is processing - it will apply server state when done
				if ( cartQueue?.getStatus().isProcessing ) {
					return;
				}

				// Skips if there's a pending request.
				if ( pendingRefresh ) return;

				pendingRefresh = true;

				try {
					const res = ( yield fetch(
						`${ state.restUrl }wc/store/v1/cart`,
						{
							method: 'GET',
							cache: 'no-store',
							headers: { 'Content-Type': 'application/json' },
						}
					) ) as TypeYield< typeof fetch >;

					// Extract fresh nonce from response headers.
					state.nonce = res.headers.get( 'Nonce' ) || state.nonce;

					if ( resolveNonceReady ) {
						resolveNonceReady();
						resolveNonceReady = null;
					}

					const json = ( yield res.json() ) as Cart;

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

			*waitForIdle(): AsyncAction< void > {
				if ( cartQueue ) {
					yield cartQueue.waitForIdle();
				}
			},

			*showNoticeError(
				error: Error | ApiErrorResponse
			): AsyncAction< void > {
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

			*updateNotices(
				newNotices: Notice[] = [],
				removeOthers = false
			): AsyncAction< void > {
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
	{ lock: universalLock }
);

// Trigger initial cart refresh.
actions.refreshCartItems();

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
