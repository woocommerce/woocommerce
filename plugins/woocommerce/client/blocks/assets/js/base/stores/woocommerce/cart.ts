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

type CartUpdateOptions = { showCartUpdatesNotices?: boolean };

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
		) => Promise< void >;
		batchAddCartItems: (
			items: ClientCartItem[],
			options?: CartUpdateOptions
		) => Promise< void >;
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
 * Pre-optimistic baselines for lines bumped in place by a keyless add.
 *
 * Maps a matched keyed line's `key` to the quantity it held before the
 * keyless-add optimistic bump mutated it in place. Used by {@link
 * getInfoNoticesFromCartUpdates} to diff such lines against their genuine
 * pre-add quantity instead of the post-optimistic snapshot, so a keyless add the
 * server resolves as a brand new standalone line does not emit a spurious
 * "quantity changed" notice for the meta line it merely bumped optimistically.
 */
type KeylessAddBaselines = Map< string, number >;

const getInfoNoticesFromCartUpdates = (
	oldCart: Store[ 'state' ][ 'cart' ],
	newCart: Cart,
	keylessAddBaselines: KeylessAddBaselines = new Map()
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
	// The one exception is the keyless-add baseline below: a line bumped in place
	// by a keyless add is compared against its pre-optimistic baseline instead of
	// that post-optimistic snapshot.
	const autoUpdatedToNotify = newItems.filter( ( item ) => {
		if ( ! isCartItem( item ) ) {
			return false;
		}
		// A line bumped in place by a keyless add is diffed against its
		// pre-optimistic baseline, not the post-optimistic snapshot. An
		// auto-update notice must reflect a *server*-initiated change, not the
		// client's own optimistic bump: when the server resolves the keyless
		// add as a brand new standalone line it leaves the matched meta line at
		// its pre-add quantity, so comparing server (e.g. 1) against the
		// baseline (1) reports nothing and no spurious "quantity changed"
		// notice fires on the successful new-line add. A genuine server change
		// to that same line in the same committed cart (server != baseline,
		// e.g. a coupon recompute or stock cap) still differs from the baseline
		// and is still reported. The override is scoped to keyless-add bumps
		// only: keyed `update-item` bumps (mini-cart steppers) are never
		// recorded in this set, so when the server returns a stepper-changed
		// line at its pre-bump quantity — meaning "your change was undone" —
		// that notice still fires. Do not widen this baseline override to keyed
		// bumps; it would suppress exactly that notice and regress the stepper.
		const baseline = keylessAddBaselines.get( item.key );
		if ( baseline !== undefined ) {
			return item.quantity !== baseline;
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
				{ showCartUpdatesNotices = true }: CartUpdateOptions = {}
			): AsyncAction< void > {
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
					// quantity: per the iron rule the posted amount is a function
					// of the delta, not of the match.
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

				// Pre-optimistic baselines for keyless adds that bump a matched
				// keyed line in place. Populated inside `applyOptimistic` below
				// (by value, before the bump) and threaded to the notice diff so
				// it compares the server quantity against the true pre-add
				// quantity (see getInfoNoticesFromCartUpdates). Starts empty and
				// stays empty on the keyed `update-item` path and when a new
				// optimistic line is pushed, so those cases — and non-add callers
				// that share the same diff, notably removeCartItem — behave exactly
				// as before this override existed.
				const keylessAddBaselines: KeylessAddBaselines = new Map();

				try {
					const result = ( yield sendCartRequest( state, {
						path: `/wc/store/v1/cart/${ endpoint }`,
						method: 'POST',
						body: itemToSend,
						applyOptimistic: () => {
							if ( existingItem ) {
								// Capture the matched keyed line's quantity as a
								// primitive, here — before the in-place bump below
								// mutates it. This is the single error-prone hotspot
								// of the whole mechanism: `existingItem` is a live
								// reference into `state.cart.items`, and the bump on
								// the next lines sets `existingItem.quantity` in
								// place, so holding the object and reading
								// `.quantity` later would read the post-bump value
								// and silently no-op the fix. The baseline must be
								// the integer read off the line now. Scoped to
								// keyless adds (!isUpdate): keyed `update-item` bumps
								// are intentionally left to diff against the
								// post-optimistic snapshot so their "your change was
								// undone" notice keeps firing.
								if ( ! isUpdate && existingItem.key ) {
									keylessAddBaselines.set(
										existingItem.key,
										existingItem.quantity
									);
								}
								// Iron rule: this in-place bump is render-only. It
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
							if ( success ) {
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
							cart,
							keylessAddBaselines
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
						const { speak } =
							( yield a11yModulePromise ) as Awaited<
								typeof a11yModulePromise
							>;
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
			): AsyncAction< void > {
				const a11yModulePromise = import( '@wordpress/a11y' );
				const quantityChanges: QuantityChanges = {};

				try {
					// Submit each item through the batcher. They'll be
					// collected into a single batch request automatically.
					//
					// Pre-optimistic baselines for keyless adds that bump a matched
					// keyed line in place, accumulated as the union across the whole
					// batch (earliest baseline per key — see the capture below) and
					// threaded to the single batch-wide notice diff. Starts empty
					// and stays empty for keyed `update-item` items and pushed new
					// lines, so the keyless-only override never touches them.
					const keylessAddBaselines: KeylessAddBaselines = new Map();
					const promises = items.map( ( item, index ) => {
						const existingItem = state.findItemInCart( {
							id: item.id,
							key: item.key,
							variation: item.variation,
						} );

						let quantity: number;
						if ( typeof item.quantityToAdd === 'number' ) {
							const currentQuantity = existingItem?.quantity ?? 0;
							quantity = currentQuantity + item.quantityToAdd;
						} else {
							quantity = item.quantity ?? 1;
						}
						// Endpoint selection is a pure function of the
						// caller-supplied `key`, never of a line matched by
						// id/variation. This mirrors the single-item
						// `addCartItem` path: a keyless batch item always
						// issues `add-item` with a delta, even when an existing
						// line (including a server-keyed one) matches by product
						// id, so the server owns cart-line identity for adds.
						// Only an explicit caller `key` targets a specific line
						// via `update-item`.
						const isUpdate = !! item.key;
						const endpoint = isUpdate ? 'update-item' : 'add-item';

						let itemToSend: OptimisticCartItem;
						if ( isUpdate && existingItem ) {
							// Caller-keyed update: target the exact line by key
							// and send the absolute target quantity to the
							// update-item endpoint.
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
							// Keyless add: build a fresh payload for the add-item
							// endpoint and never copy the matched line's key. As in
							// addCartItem, the amount sent is always a delta —
							// add-item adds to the existing quantity rather than
							// setting it — so a match (by id/variation, possibly
							// carrying a server key) only tells us how much delta is
							// already accounted for; with no match we post the full
							// target quantity. Per the iron rule the matched line is
							// never sent as an absolute quantity.
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
									// Capture the matched keyed line's quantity as a
									// primitive, here — before the bump below mutates
									// it. Same hotspot as addCartItem: `existingItem`
									// is a live reference into `state.cart.items` and
									// the bump sets `existingItem.quantity` in place,
									// so holding the object and reading `.quantity`
									// later would read the post-bump value and silently
									// no-op the fix. Scoped to keyless adds
									// (!isUpdate). Record only on the key's first bump
									// so that, if the same line is bumped twice in one
									// batch, the earliest (true pre-batch) baseline
									// wins.
									if (
										! isUpdate &&
										existingItem.key &&
										! keylessAddBaselines.has(
											existingItem.key
										)
									) {
										keylessAddBaselines.set(
											existingItem.key,
											existingItem.quantity
										);
									}
									// Iron rule (same as addCartItem): this in-place
									// bump is render-only and must never feed back into
									// endpoint selection or the posted amount, which are
									// already fixed above as a pure function of
									// key-presence and the delta. Bumping a server-keyed
									// line's rendered quantity on a keyless add is the
									// accepted meta-only blip the server reconciles; it
									// must not flip the add into `update-item` or post an
									// absolute quantity. Letting this match drive the
									// endpoint or amount reintroduces the bug.
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
							...( isLastItem && {
								onSettled: ( { data } ) => {
									if ( data ) {
										triggerAddedToCartEvent( {
											preserveCartData: true,
										} );
										emitSyncEvent( {
											quantityChanges,
										} );
									}
								},
							} ),
						} );
					} );

					// Capture cart state after optimistic updates for notices.
					const cartAfterOptimistic = JSON.parse(
						JSON.stringify( state.cart )
					);

					const results = ( yield Promise.allSettled(
						promises
					) ) as PromiseSettledResult< MutationResult< Cart > >[];

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
								keylessAddBaselines
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
							const { speak } =
								( yield a11yModulePromise ) as Awaited<
									typeof a11yModulePromise
								>;
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
