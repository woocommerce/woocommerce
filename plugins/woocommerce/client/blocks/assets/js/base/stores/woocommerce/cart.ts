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
 * Computes the canonical product token for accumulating per-product totals
 * across a batch.
 *
 * The token is stable: simple items produce `"<id>"` and variation items
 * produce `"<id>|<attr1>=<val1>&..."` with attributes sorted alphabetically
 * by name so insertion order differences do not produce different tokens.
 *
 * @param id        The product id.
 * @param variation The variation attributes, if any.
 * @return A canonical string token that uniquely identifies this product.
 */
function productToken(
	id: number,
	variation?: CartVariationItem[] | SelectedAttributes[]
): string {
	if ( ! variation || variation.length === 0 ) {
		return String( id );
	}
	const attrs = [ ...variation ]
		.sort( ( a, b ) => a.attribute.localeCompare( b.attribute ) )
		.map( ( v ) => `${ v.attribute }=${ v.value }` )
		.join( '&' );
	return `${ id }|${ attrs }`;
}

/**
 * Returns `true` when the given cart line matches the product identified by
 * `id` and `variation`, using the same matching logic as `findItemInCart`.
 *
 * Simple items match by `id` equality. Variation items additionally require
 * `variation.length` equality and `doesCartItemMatchAttributes`.
 *
 * @param item      The cart line to test.
 * @param id        The product id to match against.
 * @param variation The variation attributes to match against, if any.
 * @return `true` when the line belongs to the specified product.
 */
function lineMatchesProduct(
	item: OptimisticCartItem | CartItem,
	id: number,
	variation?: CartVariationItem[] | SelectedAttributes[]
): boolean {
	if ( item.type === 'variation' ) {
		if (
			id !== item.id ||
			! item.variation ||
			! variation ||
			item.variation.length !== variation.length
		) {
			return false;
		}
		return doesCartItemMatchAttributes( item, variation );
	}
	return id === item.id;
}

/**
 * Builds a `Set` of pre-existing cart-line keys to suppress from the
 * "quantity changed" auto-update notice after a successful keyless add.
 *
 * For each product entry in `products`, computes:
 *   - `serverTotal` = sum of the committed server cart's lines matching that
 *     product (using the same matcher as `findItemInCart`).
 *   - `expectedTotal` = pre-add total + sum of posted deltas.
 *
 * When `serverTotal === expectedTotal`, the add was exact for that product
 * (no server-initiated cap, redistribution, or concurrent change), so every
 * pre-existing line key captured for that product is added to the returned
 * set and will be skipped in the auto-UPDATE notice diff.
 *
 * When the totals diverge, the product's keys are left out of the set and
 * the diff fires normally, reporting the server's actual quantity.
 *
 * Only keyless adds should call this helper. Keyed `update-item` changes
 * must never populate `products`; leaving their line keys out of the
 * suppression set ensures the "your change was undone" notice keeps firing.
 *
 * @param products   Per-product capture records, one per added product.
 * @param serverCart The committed server cart to sum against.
 * @return The flat set of pre-existing line keys to suppress.
 */
function computeKeylessAddSuppressKeys(
	products: Array< {
		/** The product id used for matching. */
		id: number;
		/** The variation attributes used for matching, if any. */
		variation?: CartVariationItem[] | SelectedAttributes[] | undefined;
		/** Sum of all pre-add quantities across matching lines, captured before the optimistic bump. */
		preAddTotal: number;
		/** Sum of all posted deltas for this product in this add cycle. */
		deltaTotal: number;
		/** The pre-existing cart-line keys belonging to this product, captured before the optimistic bump. */
		preExistingKeys: string[];
	} >,
	serverCart: Cart
): Set< string > {
	const suppressKeys = new Set< string >();
	for ( const product of products ) {
		const serverTotal = serverCart.items
			.filter( ( item ) =>
				lineMatchesProduct( item, product.id, product.variation )
			)
			.reduce( ( sum, item ) => sum + item.quantity, 0 );
		const expectedTotal = product.preAddTotal + product.deltaTotal;
		if ( serverTotal === expectedTotal ) {
			for ( const key of product.preExistingKeys ) {
				suppressKeys.add( key );
			}
		}
	}
	return suppressKeys;
}

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
 * post-optimistic snapshot, with one suppression rule: any line whose key
 * appears in `suppressKeys` is skipped unconditionally. The action populates
 * `suppressKeys` with the pre-existing keys of products whose keyless add was
 * exact (server total == pre-add total + posted delta), so a successful keyless
 * add never emits a spurious "quantity changed" notice regardless of which
 * server line received the delta. Genuine server changes (cap, clamp, concurrent
 * mutation) still notify because they make the per-product totals diverge and
 * the keys are left out of the set.
 *
 * Keyed `update-item` changes and `removeCartItem` never populate
 * `suppressKeys` (the parameter defaults to an empty set), so their notice
 * behavior is byte-for-byte unchanged.
 *
 * @param oldCart      The post-optimistic cart snapshot used as the diff baseline.
 * @param newCart      The committed server cart to diff against.
 * @param suppressKeys Keys of pre-existing lines whose product's add was exact;
 *                     these lines are skipped in the auto-UPDATE filter.
 * @return The list of info notices to surface to the shopper.
 */
const getInfoNoticesFromCartUpdates = (
	oldCart: Store[ 'state' ][ 'cart' ],
	newCart: Cart,
	suppressKeys: Set< string > = new Set()
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
	// Lines whose key appears in suppressKeys are skipped: the action proved that
	// the product's add was exact (server total == expected total), so any
	// quantity difference on those lines is an intentional add result, not a
	// server-initiated change. Keyed update-item lines and removeCartItem lines
	// are never in suppressKeys, so their notice behavior is unchanged.
	const autoUpdatedToNotify = newItems.filter( ( item ) => {
		if ( ! isCartItem( item ) ) {
			return false;
		}
		if ( suppressKeys.has( item.key ) ) {
			return false; // The action proved this product's add was exact.
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
					void actions.showNoticeError( error as Error );
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

				// Per-product capture for the keyless-add exactness test.
				// On the keyless path (!isUpdate), capture by value — before the
				// optimistic bump mutates `existingItem.quantity` in place — the
				// set of pre-existing matching line keys and their summed quantity.
				// This is the single error-prone hotspot: `existingItem` is a live
				// reference into `state.cart.items`; reading `.quantity` after the
				// bump yields the post-bump value and silently corrupts the math.
				// Stays empty on the keyed `update-item` path so the "your change
				// was undone" notice keeps firing for steppers.
				type ProductCapture = {
					id: number;
					variation?:
						| CartVariationItem[]
						| SelectedAttributes[]
						| undefined;
					preAddTotal: number;
					deltaTotal: number;
					preExistingKeys: string[];
				};
				const productCaptures: ProductCapture[] = [];
				if ( ! isUpdate ) {
					// Sum all pre-add quantities across every cart line matching
					// this product (id + variation). A single product can occupy
					// multiple lines (e.g. a meta line ordered before a standalone
					// line). The per-product total lets us verify exactness even
					// when the server grows a different line than the one the
					// client bumped optimistically.
					const preExistingKeys: string[] = [];
					let preAddTotal = 0;
					for ( const cartLine of state.cart.items ) {
						if ( lineMatchesProduct( cartLine, id, variation ) ) {
							preAddTotal += cartLine.quantity;
							if ( cartLine.key ) {
								preExistingKeys.push( cartLine.key );
							}
						}
					}
					// `itemToSend.quantity` is the posted delta (quantityToSend
					// computed above). It is already computed before this capture
					// block and does not depend on the optimistic state, so it is
					// safe to read here.
					productCaptures.push( {
						id,
						variation,
						preAddTotal,
						deltaTotal: itemToSend.quantity,
						preExistingKeys,
					} );
				}

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
						// Compute the suppression set: for each added product,
						// check whether the server total matches the pre-add total
						// plus the posted delta. If so, the add was exact and the
						// pre-existing line keys are suppressed in the notice diff.
						const suppressKeys = computeKeylessAddSuppressKeys(
							productCaptures,
							cart
						);
						const infoNotices = getInfoNoticesFromCartUpdates(
							cartAfterOptimistic,
							cart,
							suppressKeys
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
					void actions.showNoticeError( error as Error );
				}
			},

			*batchAddCartItems(
				items: ClientCartItem[],
				{ showCartUpdatesNotices = true }: CartUpdateOptions = {}
			): AsyncAction< void > {
				const a11yModulePromise = import( '@wordpress/a11y' );
				const quantityChanges: QuantityChanges = {};

				try {
					// Per-product capture for the keyless-add exactness test,
					// accumulated across all keyless batch items. The map key is a
					// canonical product token (productToken(id, variation)) so that
					// multiple batch items for the same product accumulate into one
					// entry. The capture runs synchronously in the .map() below,
					// before applyOptimistic runs (applyOptimistic is gated behind
					// `await isNonceReady` inside sendCartRequest, so every
					// existingItem.quantity read during the .map() sees the same
					// pre-add cart). Keyed `update-item` items never contribute to
					// this map, so the "your change was undone" notice keeps firing.
					type BatchProductCapture = {
						id: number;
						variation?:
							| CartVariationItem[]
							| SelectedAttributes[]
							| undefined;
						preAddTotal: number;
						deltaTotal: number;
						preExistingKeys: string[];
					};
					const batchProductCaptures = new Map<
						string,
						BatchProductCapture
					>();
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
							// target quantity. The matched line is never sent as an
							// absolute quantity.
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

							// Accumulate the per-product capture for the exactness
							// test. On the first encounter of each product token,
							// sum all pre-add quantities across every matching line
							// and collect their keys — both captured as primitives
							// here, before applyOptimistic mutates the cart. On
							// subsequent encounters of the same product, add only
							// the posted delta to the running deltaTotal.
							const token = productToken(
								item.id,
								item.variation
							);
							if ( ! batchProductCaptures.has( token ) ) {
								const preExistingKeys: string[] = [];
								let preAddTotal = 0;
								for ( const cartLine of state.cart.items ) {
									if (
										lineMatchesProduct(
											cartLine,
											item.id,
											item.variation
										)
									) {
										preAddTotal += cartLine.quantity;
										if ( cartLine.key ) {
											preExistingKeys.push(
												cartLine.key
											);
										}
									}
								}
								batchProductCaptures.set( token, {
									id: item.id,
									variation: item.variation,
									preAddTotal,
									deltaTotal: quantityToSend,
									preExistingKeys,
								} );
							} else {
								// Same product seen again in this batch — add delta.
								const capture =
									batchProductCaptures.get( token );
								if ( capture ) {
									capture.deltaTotal += quantityToSend;
								}
							}
						}

						const isLastItem = index === items.length - 1;

						return sendCartRequest( state, {
							path: `/wc/store/v1/cart/${ endpoint }`,
							method: 'POST',
							body: itemToSend,
							applyOptimistic: () => {
								if ( existingItem ) {
									// As in addCartItem, this in-place
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
							// Compute the suppression set from the accumulated
							// per-product captures. For each product, if the server
							// total equals preAddTotal + sum of posted deltas, the
							// add was exact and the pre-existing keys are suppressed.
							const suppressKeys = computeKeylessAddSuppressKeys(
								[ ...batchProductCaptures.values() ],
								cart
							);
							const infoNotices = getInfoNoticesFromCartUpdates(
								cartAfterOptimistic,
								cart,
								suppressKeys
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
					void actions.showNoticeError( error as Error );
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
void actions.refreshCartItems();

window.addEventListener(
	'wc-blocks_store_sync_required',
	async ( event: Event ) => {
		const customEvent = event as CustomEvent< {
			type: string;
			id: number;
		} >;
		if ( customEvent.detail.type === 'from_@wordpress/data' ) {
			void actions.refreshCartItems();
		}
	}
);
