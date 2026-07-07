/**
 * External dependencies
 */
import { getConfig, getContext, store } from '@wordpress/interactivity';
import type { AsyncAction, TypeYield } from '@wordpress/interactivity';
import type {
	Cart,
	CartItem,
	CartVariationItem,
	ApiErrorResponse,
	CartResponseTotals,
	Currency,
	ProductResponseItem,
} from '@woocommerce/types';
import type {
	Store as StoreNotices,
	Notice,
} from '@woocommerce/stores/store-notices';
import type { ProductsStore } from '@woocommerce/stores/woocommerce/products';

/**
 * Internal dependencies
 */
import { triggerAddedToCartEvent } from './legacy-events';
import {
	createMutationQueue,
	MutationRequest,
	type MutationQueue,
	type MutationResult,
	type CycleSettleResult,
} from './mutation-batcher';
import {
	type CartItemFilterPredicate,
	type DraftItem,
	getDraftExtensionProps,
	isGenericExactPair,
} from './cart-item-matching';

export type { DraftItem, CartItemFilterPredicate } from './cart-item-matching';

// `SelectedAttributes` has a single definition — the products store owns it
// (the cart-store types already import from products). Re-exported here so the
// cart store's existing consumers keep importing it from `woocommerce/cart`.
export type { SelectedAttributes } from '@woocommerce/stores/woocommerce/products';

export type WooCommerceConfig = {
	messages?: {
		addedToCartText?: string;
	};
	placeholderImgSrc?: string;
	currency?: Currency;
	nonOptimisticProperties?: string[];
	/**
	 * REST API base URL. Seeded server-side via
	 * `wp_interactivity_config( 'woocommerce' )`. Infra, not commerce state.
	 */
	restUrl?: string;
	/**
	 * Bootstrap Store API nonce. Seeded server-side (optional). The live nonce
	 * is refreshed from the `Nonce` response header on every request — see the
	 * `currentNonce` module variable below.
	 */
	nonce?: string;
	/**
	 * Map of Store API error codes to user-friendly messages. Infra config,
	 * not commerce state.
	 */
	errorMessages?: {
		[ key: string ]: string;
	};
};

export type OptimisticCartItem = {
	key?: string | undefined;
	id: number;
	quantity: number;
	variation?: CartVariationItem[];
	type: string;
};

/**
 * The cart store's OWN context namespace, `woocommerce/cart`. It carries a cart
 * surface's line identity (`cartItemKey`) and an optional draft-scoping override
 * (`draftKey`).
 *
 * `cartItem` is the IMPLICIT per-row context that a `data-wp-each--cart-item`
 * directive (iterating `woocommerce/cart::state.cart.items`) keys under this
 * same namespace. Its `key` is a first-class envelope step-1 source, so cart
 * rows resolve their exact line with no client-side key bridge (and with SSR
 * parity, since the each-item context also exists server-side).
 *
 * `draftKey` is an OPTIONAL override for the draft this surface reads/writes.
 * When absent, the draft key defaults to `String(context product id)`, so
 * surfaces of the same product share one draft; declaring a distinct `draftKey`
 * isolates a surface's draft from its siblings' on the same product.
 *
 * The context carries NO `productId`: product identity is resolved through
 * derived state (`woocommerce/products` store's `mainProductInContext`), never
 * by reading the products context namespace. Custom line matching is an explicit
 * `findItem({ filter })` predicate, not a serialized context reference.
 */
export type CartScopeContext = {
	cartItemKey?: string;
	cartItem?: { key?: string };
	draftKey?: string;
};

/**
 * The `itemInContext` / `findItem` envelope: the cart line (only when exactly
 * one candidate survives the ladder) and the editable context draft.
 *
 * `cart` is the raw Store API cart line (`CartItem`) — never an optimistic
 * in-flight item. Optimistic items lack a `key` and carry no `extensions` /
 * `item_data`, so they can never be an exact pairing target; excluding them
 * keeps the "cart" side of the envelope strictly server-truth, which is what
 * consumers feeding `updateItem({ key })` require.
 *
 * `cart` is `undefined` when the product is present only as lines the draft
 * cannot account for (e.g. a decorated bundle child, or note-split lines with no
 * matching draft props) — such surfaces fall back to plain add-button UI.
 * "In the cart in any form" (banners) is a raw `state.cart.items` scan, not this
 * envelope.
 */
export type ItemEnvelope = {
	cart?: CartItem;
	draft?: DraftItem;
};

export type Store = {
	state: {
		cart: Omit< Cart, 'items' > & {
			items: ( OptimisticCartItem | CartItem )[];
			totals: CartResponseTotals;
		};
		/**
		 * Editable map of pure `cart/add-item` payloads, keyed by draft key. The
		 * draft key defaults to `String(productId)`, so surfaces of the same
		 * product share one draft with zero config; a surface opts into isolation
		 * by declaring its own `draftKey` in the `woocommerce/cart` scope context.
		 * Shopper input is written through the draft actions — `upsertDraftItem`,
		 * `removeDraftItem`. `state.cart` is read-only for consumers.
		 */
		draftItems: Record< string, DraftItem >;
		/**
		 * The envelope for the current context: `{ cart, draft }`. Read-only
		 * derived getter. The draft is resolved by the context's `draftKey`
		 * (defaulting to the products store's `mainProductInContext` id); the
		 * exact line key comes from the `woocommerce/cart` context.
		 */
		itemInContext: ItemEnvelope;
		/**
		 * Resolve an envelope explicitly.
		 *
		 * - `key` bypasses the ladder entirely (exact line; ignores any filter).
		 * - `id` runs the ladder against the draft STORED under `String(id)` (never
		 *   the context draft — `findItem` does not consult context). When no such
		 *   draft exists, a bare `{ id }` query object narrows candidates internally,
		 *   but the returned envelope's `draft` is the stored draft or `undefined` —
		 *   it never fabricates a draft.
		 * - `filter` — an optional predicate that REPLACES the generic narrowing
		 *   (per-namespace compare + presence heuristic) as the sole narrowing
		 *   authority. Absent → generic narrowing runs. Never inherits any context
		 *   filter (there is no context-reference machinery).
		 */
		findItem: ( args: {
			key?: string;
			id?: number;
			filter?: CartItemFilterPredicate;
		} ) => ItemEnvelope;
		/**
		 * Type-invariant in-cart quantity for a product. This is the polymorphic
		 * read that lets non-specialized surfaces (e.g. the Product Button) show
		 * "X in cart" without ever branching on product type — the getter resolves
		 * the number once, for every type. It is NOT "the total across every
		 * purchasable form": for a variable product it is specifically the
		 * RESOLVED-SELECTION line's quantity, not a sum over sibling variations.
		 *
		 * - simple:   the product's own line quantity.
		 * - variable: the currently RESOLVED variation's line quantity — the one
		 *             the draft's selection resolves to (via `findItem`'s
		 *             purchasable-id resolution). `0` while the selection is
		 *             unresolved (no variation picked yet); it does NOT aggregate
		 *             over other variation lines of the same parent.
		 * - grouped:  the SUM over the lines of the product's `grouped_products`
		 *             child ids (a grouped parent has no line of its own; its "in
		 *             cart" is the aggregate of its children).
		 *
		 * `id` defaults to the context product (`mainProductInContext`). Returns
		 * `0` when nothing is resolvable or the product is not in the cart.
		 */
		inCartQuantity: ( id?: number ) => number;
	};
	actions: {
		/**
		 * @internal Not contract surface. Test/internal plumbing — resolves once
		 * the mutation queue is idle. Not part of the shared-store API.
		 */
		waitForIdle: () => Promise< void >;
		/**
		 * @internal Not contract surface. Internal error → store-notice bridge,
		 * used by the store's own actions. Not part of the shared-store API.
		 */
		showNoticeError: ( error: Error | ApiErrorResponse ) => Promise< void >;
		/**
		 * @internal Not contract surface. Internal notice-reconciliation used by
		 * the cycle settle handler. Not part of the shared-store API.
		 */
		updateNotices: (
			notices: Notice[],
			removeOthers?: boolean
		) => Promise< void >;
		/**
		 * ALWAYS posts `add-item` (never converts to update-item by client
		 * matching). Defaults to `itemInContext.draft`; falls back to
		 * `{ id: mainProductInContext id, quantity: min purchase quantity }`.
		 * Resolves with the affected cart line from the batch response (provenance
		 * is a return value, never stored on drafts).
		 */
		addItem: ( payload?: DraftItem ) => Promise< CartItem | undefined >;
		updateItem: ( args: {
			key: string;
			quantity: number;
		} ) => Promise< void >;
		/**
		 * Remove a line by key. When `key` is omitted it defaults to the cart
		 * scope context's line key (`context.cartItemKey ?? context.cartItem?.key`).
		 * NO-OP (dev-mode warning) when nothing resolves — never throws.
		 */
		removeItem: ( key?: string ) => Promise< void >;
		refresh: () => Promise< void >;
		/**
		 * Merge a partial payload into the context draft, creating it if missing.
		 * The target draft key is `args.draftKey` (imperative override), else the
		 * context's `draftKey`, else `String(context product id)`. Returns the
		 * draft.
		 */
		upsertDraftItem: (
			partialPayload: Partial< DraftItem > & { draftKey?: string }
		) => DraftItem;
		/**
		 * Remove the draft for a draft key (defaults to the context's `draftKey`,
		 * else the context product). NO-OP when none is resolvable — never clears
		 * all drafts.
		 */
		removeDraftItem: ( args?: { draftKey?: string } ) => void;
	};
};

type QuantityChanges = {
	cartItemsPendingQuantity?: string[];
	cartItemsPendingDelete?: string[];
	productsPendingAdd?: number[];
};

/**
 * Distinguish server-confirmed cart lines from optimistic in-flight items. Only
 * server lines carry a `key` (the server's identity hash); optimistic items are
 * keyless and carry no `extensions` / `item_data`, so only server lines can be an
 * exact pairing target.
 */
function isCartItem( item: OptimisticCartItem | CartItem ): item is CartItem {
	return typeof ( item as CartItem ).key === 'string';
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
	newCart: Cart
): Notice[] => {
	const oldItems = oldCart.items;
	const newItems = newCart.items;

	// Items auto-removed by the server (stock change, product deleted, etc.).
	// We pass the optimistic snapshot as oldCart, so user-initiated removals
	// are already absent and do not generate spurious notices here.
	const autoDeletedToNotify = oldItems.filter(
		( old ): old is CartItem =>
			isCartItem( old ) &&
			! newItems.some( ( item ) => old.key === item.key )
	);

	// Items whose quantity was adjusted by the server (stock cap, sold-individually).
	// Comparing optimistic → server means intentional user changes are already
	// reflected in oldItems and will not trigger this notice.
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
 * Infra config (restUrl, nonce, errorMessages) lives in
 * `wp_interactivity_config( 'woocommerce' )`, not in reactive store state.
 */
const wooConfig = getConfig( 'woocommerce' ) as WooCommerceConfig;

/**
 * REST API base URL. Read once from config — it never changes at runtime.
 */
const restUrl = wooConfig.restUrl ?? '';

/**
 * Live Store API nonce.
 *
 * Config is read-only/static, but the nonce is refreshed per request from the
 * `Nonce` response header (rotating nonces). We therefore bootstrap it from
 * config and keep the current value in this module-level variable, updating it
 * whenever a response carries a fresh `Nonce` header (see `refreshNonce`). This
 * preserves the previous behavior where the refreshed nonce was written back
 * into `state.nonce`, without keeping it in reactive state.
 */
let currentNonce = wooConfig.nonce ?? '';

/**
 * Update the in-memory nonce from a response's `Nonce` header, if present.
 *
 * @param response Fetch response whose headers may carry a fresh nonce.
 */
function refreshNonce( response: Response ): void {
	currentNonce = response.headers.get( 'Nonce' ) || currentNonce;
}

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
 * Cycle accumulator.
 *
 * The mutation batcher fires `onCycleSettle` ONCE when the queue returns to
 * idle, regardless of how many requests the cycle contained. Sync/legacy events
 * and store notices are cycle-level concerns, so the cart actions no longer fire
 * them per request; instead each request contributes to this per-cycle
 * accumulator, and `handleCartCycleSettle` consumes it once.
 *
 * This is reset at the end of every settle cycle (see `handleCartCycleSettle`).
 */
type CartCycleAccumulator = {
	/** Aggregated quantity changes across every request in the cycle. */
	quantityChanges: QuantityChanges;
	/**
	 * True when the cycle contained at least one add/update request. Controls
	 * the legacy `wc-blocks_added_to_cart` event and the a11y announcement,
	 * which a pure removal cycle must not fire (a removal via `removeItem` never
	 * triggers them).
	 */
	didAdd: boolean;
	/**
	 * Whether the cycle's info/error notice pass should run. Per-request
	 * opt-outs aggregate with logical AND: if ANY request in the cycle passes
	 * `showCartUpdatesNotices: false`, the whole cycle's notice pass is
	 * suppressed. A caller opting out is asserting "don't surface generic cart
	 * notices for my operation"; honoring that matches the old
	 * `batchAddCartItems`, where a single flag governed the entire batch.
	 * `removeItem` has no opt-out, so it always leaves this `true`.
	 */
	showNotices: boolean;
	/**
	 * Cart state captured after the last optimistic update in the cycle, used
	 * as the "old cart" for notice extraction. We deliberately compare the
	 * post-optimistic cart (not the batcher's pre-optimistic snapshot) against
	 * the final server state so that user-initiated removals/quantity changes
	 * are already reflected and do not generate spurious auto-change notices —
	 * see `getInfoNoticesFromCartUpdates`.
	 */
	optimisticSnapshot: Store[ 'state' ][ 'cart' ] | null;
};

function createCartCycleAccumulator(): CartCycleAccumulator {
	return {
		quantityChanges: {},
		didAdd: false,
		showNotices: true,
		optimisticSnapshot: null,
	};
}

let cartCycle: CartCycleAccumulator = createCartCycleAccumulator();

/**
 * Merge a single request's contribution into the current cycle accumulator.
 *
 * Called from each action at submit time (synchronously, before the batch is
 * sent), so that by settle time the accumulator reflects every request.
 */
function accumulateCartCycle( contribution: {
	quantityChanges?: QuantityChanges;
	didAdd?: boolean;
	showNotices?: boolean;
} ): void {
	const { quantityChanges, didAdd, showNotices } = contribution;

	if ( quantityChanges?.cartItemsPendingQuantity ) {
		cartCycle.quantityChanges.cartItemsPendingQuantity = [
			...( cartCycle.quantityChanges.cartItemsPendingQuantity ?? [] ),
			...quantityChanges.cartItemsPendingQuantity,
		];
	}
	if ( quantityChanges?.cartItemsPendingDelete ) {
		cartCycle.quantityChanges.cartItemsPendingDelete = [
			...( cartCycle.quantityChanges.cartItemsPendingDelete ?? [] ),
			...quantityChanges.cartItemsPendingDelete,
		];
	}
	if ( quantityChanges?.productsPendingAdd ) {
		cartCycle.quantityChanges.productsPendingAdd = [
			...( cartCycle.quantityChanges.productsPendingAdd ?? [] ),
			...quantityChanges.productsPendingAdd,
		];
	}
	if ( didAdd ) {
		cartCycle.didAdd = true;
	}
	// AND semantics: a single opt-out suppresses the whole cycle's pass.
	if ( showNotices === false ) {
		cartCycle.showNotices = false;
	}
}

/**
 * Announce the "added to cart" text to screen readers, if configured.
 */
async function announceAddedToCart(): Promise< void > {
	const { messages } = getConfig( 'woocommerce' ) as WooCommerceConfig;
	if ( messages?.addedToCartText ) {
		const { speak } = await import( '@wordpress/a11y' );
		speak( messages.addedToCartText, 'polite' );
	}
}

/**
 * Cycle-level settle handler wired into the batcher via `onCycleSettle`.
 *
 * Fires exactly once per settle cycle, synchronously during `reconcile()` while
 * the batcher is still `isProcessing` — so events and notices complete before
 * `refresh` (which bails while processing) can run, preserving the
 * no-race-with-refreshes guarantee that the old per-request `onSettled`
 * callbacks provided.
 */
function handleCartCycleSettle( result: CycleSettleResult< Cart > ): void {
	const cycle = cartCycle;
	// Reset immediately so a nested cycle (should not happen synchronously, but
	// defensively) starts clean and this handler is idempotent per cycle.
	cartCycle = createCartCycleAccumulator();

	if ( result.hasSuccess ) {
		// Legacy event + screen-reader announcement only for add/update cycles.
		if ( cycle.didAdd ) {
			triggerAddedToCartEvent( { preserveCartData: true } );
			announceAddedToCart();
		}

		// Sync event carries the whole cycle's aggregated quantity changes.
		emitSyncEvent( { quantityChanges: cycle.quantityChanges } );

		// Info + server-error notices: compare the post-optimistic cart against
		// the final server state, once for the whole cycle.
		const cart = result.data;
		if ( cycle.showNotices && cart && cycle.optimisticSnapshot ) {
			const infoNotices = getInfoNoticesFromCartUpdates(
				cycle.optimisticSnapshot,
				cart
			);
			const errorNotices = ( cart.errors ?? [] ).map(
				generateErrorNotice
			);
			actions.updateNotices( [ ...infoNotices, ...errorNotices ], true );
		}
	}
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
			endpoint: `${ restUrl }wc/store/v1/batch`,
			getHeaders: () => ( {
				Nonce: currentNonce,
			} ),
			takeSnapshot: () => JSON.parse( JSON.stringify( stateRef.cart ) ),
			rollback: ( snapshot ) => {
				stateRef.cart = snapshot;
			},
			commit: ( serverState ) => {
				stateRef.cart = serverState;
			},
			// Batcher owns firing sync/legacy events and running the notice
			// pass once per settle cycle. Runs while the queue is still
			// processing, so refreshes stay guarded.
			onCycleSettle: handleCartCycleSettle,
			fetchHandler: async ( ...args ) => {
				const response = await fetch( ...args );
				refreshNonce( response );
				return response;
			},
		} );
	}

	return cartQueue.submit( options );
}
// Stores are locked to prevent 3PD usage until the API is stable.
const universalLock =
	'I acknowledge that using a private store means my plugin will inevitably break on the next store release.';

/**
 * Lazy cross-store read into `woocommerce/products`.
 *
 * The cart store reads the products store for deterministic variation resolution
 * (turning a draft's product id + variation selection into the purchasable id a
 * cart line carries) and for the context product id (`mainProductInContext`).
 * Read lazily rather than at module load so the products store's own registration
 * order doesn't matter. Coupling is one-directional: cart reads products, never
 * the reverse.
 */
function getProductsState(): ProductsStore[ 'state' ] {
	const { state: productsState } = store< ProductsStore >(
		'woocommerce/products',
		{},
		{ lock: universalLock }
	);
	return productsState;
}

/**
 * Resolve a draft's product id + variation selection to the purchasable id that a
 * matching cart line carries.
 *
 * Cart lines expose the purchasable id in their `id` field — the variation id
 * for a variation, the product id otherwise (Store API `CartItemSchema`). To
 * pair a draft against lines we resolve the same purchasable id the server would,
 * which `findProduct` does deterministically (mirroring the server's
 * `find_matching_product_variation`). When the product is unavailable or the
 * selection is ambiguous, we degrade to the draft's own `id` — safe because for a
 * simple product the id is already purchasable, and an unresolved variable
 * selection simply won't match any variation line.
 *
 * @param draft The draft whose purchasable id we want.
 * @return The resolved purchasable id (variation id or product id).
 */
function resolvePurchasableId( draft: DraftItem ): number {
	const resolved: ProductResponseItem | null = getProductsState().findProduct(
		{
			id: draft.id,
			selectedAttributes: draft.variation ?? null,
		}
	);

	return resolved?.id ?? draft.id;
}

/**
 * Read the cart store's OWN context (`woocommerce/cart`), or `null` when called
 * outside a directive scope (e.g. from a plain module call). Out-of-scope reads
 * degrade silently to "no cart context" rather than throwing.
 *
 * This never reads the products context namespace — the context product id is
 * resolved through derived state (`getContextProductId`).
 */
function getCartContext(): CartScopeContext | null {
	try {
		return getContext< CartScopeContext >( 'woocommerce/cart' ) ?? null;
	} catch {
		return null;
	}
}

/**
 * Resolve the context product id via derived state on the products store —
 * `mainProductInContext` (the top-level product for this surface, keyed off the
 * `woocommerce/products` context or that store's global state).
 *
 * The cart store never reads the `woocommerce/products` context namespace itself.
 * Degrades to `undefined` out of product scope — the envelope then resolves
 * conservatively (no draft).
 *
 * @return The context product id, or `undefined` when unavailable.
 */
function getContextProductId(): number | undefined {
	try {
		return getProductsState().mainProductInContext?.id;
	} catch {
		// mainProductInContext reads the products context; out of scope it
		// throws — degrade to "no context product".
		return undefined;
	}
}

/**
 * Resolve the cart line key from the current `woocommerce/cart` context — the
 * exact source envelope step 1 uses: an explicit `cartItemKey`, or the each-item
 * `cartItem.key` a `data-wp-each--cart-item` directive keys under this namespace.
 *
 * @return The context cart item key, or `undefined` when neither is present.
 */
function getContextCartItemKey(): string | undefined {
	const context = getCartContext();
	return context?.cartItemKey ?? context?.cartItem?.key;
}

/**
 * Resolve the draft key for the current context. Surfaces of the same product
 * share one draft by default (`String(context product id)`); a surface opts into
 * isolation by declaring `draftKey` in its `woocommerce/cart` scope context.
 *
 * @return The context draft key, or `undefined` when neither a `draftKey` nor a
 *         context product is resolvable.
 */
function getContextDraftKey(): string | undefined {
	const contextDraftKey = getCartContext()?.draftKey;
	if ( contextDraftKey !== undefined ) {
		return contextDraftKey;
	}
	const productId = getContextProductId();
	return productId === undefined ? undefined : String( productId );
}

/**
 * Look up the draft stored under a draft key.
 *
 * @param draftKey The draft key (defaults to `String(productId)`).
 * @return The matching draft, or undefined.
 */
function findDraftByKey( draftKey: string | undefined ): DraftItem | undefined {
	if ( draftKey === undefined ) {
		return undefined;
	}
	return state.draftItems[ draftKey ];
}

/**
 * The minimum purchase quantity for the context product, from the Store API
 * product's `add_to_cart.minimum`. Used as the `addItem` fallback quantity when a
 * bare surface (e.g. a Product Button with no draft) adds a min-purchase product.
 * Degrades to `1` out of product scope or when the constraint is unavailable.
 *
 * @return The context product's minimum purchase quantity, or `1`.
 */
function getContextMinPurchaseQuantity(): number {
	try {
		return getProductsState().productInContext?.add_to_cart?.minimum ?? 1;
	} catch {
		return 1;
	}
}

/**
 * Resolve the `{ cart, draft }` envelope via the resolution ladder.
 *
 * Ladder:
 *
 * 1. A cart-context line key (`context.cartItemKey`, or a `data-wp-each` item
 *    context's `cartItem.key` — both in the `woocommerce/cart` namespace) →
 *    that exact line. Custom filters NEVER run; cart surfaces are always exact.
 *    CROSS-PRODUCT GUARD: if a context draft is also present but its resolved
 *    product does NOT correspond to the keyed line's product, the draft is
 *    dropped from the envelope (`draft: undefined`) rather than pairing a line
 *    and a draft from different products — e.g. a mini-cart row for product B
 *    rendered on product A's page must not carry A's draft.
 * 2. Candidates = lines whose purchasable id matches the draft's resolved id
 *    (variation-id resolution only — no attribute matching at cart level). Then
 *    EITHER:
 *    - `filter` (an explicit `findItem` predicate) is set → the predicate is the
 *      **sole** narrowing authority: it REPLACES the generic narrowing
 *      (per-namespace compare + presence heuristic), it does not compose with
 *      it. This is what lets a caller (e.g. a bundle editor) pair with a line the
 *      presence heuristic would otherwise exclude.
 *    - otherwise → generic narrowing: per-namespace deep-compare of the draft's
 *      namespaced props vs the line's `extensions[ns]`, plus the presence
 *      heuristic (a line with visible `item_data`/`extensions` the draft doesn't
 *      account for is excluded).
 * 3. Exactly one survivor → `cart`; zero or several → undefined. NEVER
 *    first-match fallback — an inferred pairing feeds `updateItem({ key })` and
 *    becomes a wrong-line mutation. A product present only as lines the draft
 *    cannot account for (e.g. a decorated bundle child) yields `cart: undefined`,
 *    so the surface renders plain add-button UI instead of in-cart UI without a
 *    safe mutation target. Invisible bare twins both survive → still `cart`
 *    undefined (genuinely ambiguous presence). This exactly-one rule applies to
 *    the filter's survivor set identically.
 *
 * @param opts.draft  The draft to pair (usually the context draft).
 * @param opts.key    An explicit cart item key (bypasses everything).
 * @param opts.filter An explicit `findItem` predicate. When present it is the
 *                    sole narrowing authority (replaces generic narrowing).
 * @return The resolved envelope.
 */
function resolveEnvelope( opts: {
	draft?: DraftItem | undefined;
	key?: string | undefined;
	filter?: CartItemFilterPredicate | null | undefined;
} ): ItemEnvelope {
	const { draft, key, filter } = opts;

	// Step 1: explicit key wins — exact, no filters, no narrowing.
	if ( key ) {
		const line = state.cart.items.find( ( item ) => item.key === key ) as
			| CartItem
			| undefined;
		// Cross-product guard: only carry the draft when it belongs to the same
		// product as the resolved line. Pairing a line and a draft from different
		// products (e.g. a mini-cart row for product B on product A's page) would
		// hand the surface a foreign draft.
		const draftMatchesLine =
			draft !== undefined &&
			line !== undefined &&
			resolvePurchasableId( draft ) === line.id;
		return {
			...( line && { cart: line } ),
			...( draftMatchesLine && { draft } ),
		};
	}

	// Without a draft (and without a key) there is nothing to pair against.
	if ( ! draft ) {
		return {};
	}

	// Step 2: id + variation candidates (purchasable-id resolution only).
	const purchasableId = resolvePurchasableId( draft );
	const candidates = state.cart.items.filter(
		( item ): item is CartItem =>
			isCartItem( item ) && item.id === purchasableId
	);

	// Step 2 narrowing. When a filter is set it is the SOLE narrowing authority
	// (replace, not compose — the generic narrowing does NOT also run).
	let narrow: ( line: CartItem ) => boolean;
	if ( filter ) {
		narrow = ( line ) => filter( line, { draft } );
	} else {
		const draftProps = getDraftExtensionProps( draft );
		narrow = ( line ) => isGenericExactPair( draftProps, line );
	}
	const survivors = candidates.filter( narrow );

	// Step 3: exactly one survivor pairs; zero or several → undefined (never
	// first-match — an inferred pairing would become a wrong-line mutation).
	const cart = survivors.length === 1 ? survivors[ 0 ] : undefined;

	return {
		...( cart && { cart } ),
		draft,
	};
}

// The cart store now lives under the `woocommerce/cart` namespace. The bare
// `woocommerce` namespace holds only the shared context and
// `wp_interactivity_config` (see the alias at the bottom of this file).
//
// Todo: export this store once the store is public.
const { state } = store< Store >(
	'woocommerce/cart',
	{},
	{ lock: universalLock }
);
const { actions } = store< Store >(
	'woocommerce/cart',
	{
		state: {
			// Drafts are client-only: born lazily on first interaction via
			// `upsertDraftItem`, never seeded server-side. Keyed by draft key
			// (defaulting to `String(productId)`), so surfaces of the same product
			// share one draft unless a surface declares its own `draftKey`.
			draftItems: {},

			/**
			 * The `{ cart, draft }` envelope for the current context. Derived
			 * read-only getter.
			 *
			 * `draft` is the `draftItems` entry under the context draft key
			 * (`context.draftKey`, else `String(context product id)` resolved via
			 * `getContextProductId`). Shopper input is written back through the
			 * draft actions (`upsertDraftItem`). `cart` comes from the resolution
			 * ladder (generic narrowing only — there is no context filter).
			 *
			 * The exact line key comes from the cart store's own context
			 * (`woocommerce/cart`): an explicit `cartItemKey`, or the each-item
			 * `cartItem.key` a `data-wp-each--cart-item` directive keys under this
			 * namespace (envelope step 1 accepts either).
			 */
			get itemInContext(): ItemEnvelope {
				const draft = findDraftByKey( getContextDraftKey() );
				const key = getContextCartItemKey();
				return resolveEnvelope( { draft, key } );
			},

			/**
			 * Explicit envelope lookup.
			 *
			 * - `key` bypasses the ladder (exact line, no filter).
			 * - `id` runs the ladder against the draft keyed by `String(id)` (or a
			 *   bare draft built from the id when no such draft exists).
			 * - `filter` — an optional predicate `( cartItem, { draft } ) =>
			 *   boolean`. When present it REPLACES the generic narrowing
			 *   (per-namespace compare + presence heuristic) and is the sole
			 *   narrowing authority, so a caller can pair with a line the generic
			 *   rules would exclude (e.g. a bundle editor). Absent → generic
			 *   narrowing runs. `findItem` never inherits any context filter.
			 */
			findItem( {
				key,
				id,
				filter,
			}: {
				key?: string;
				id?: number;
				filter?: CartItemFilterPredicate;
			} ): ItemEnvelope {
				const draftKey = id !== undefined ? String( id ) : undefined;
				const storedDraft = findDraftByKey( draftKey );

				if ( key ) {
					return resolveEnvelope( {
						draft: storedDraft,
						key,
					} );
				}

				// Run the ladder against the stored draft when one exists, else a
				// bare `{ id }` QUERY object so id-only lookups still resolve their
				// line. That query object is internal to the ladder only — it must
				// NOT leak into the returned envelope's `draft`, which reports the
				// STORED draft (or `undefined`). Consumers that need the line read
				// `.cart`; nobody should mistake the query stand-in for a real draft.
				const queryDraft =
					storedDraft ?? ( id !== undefined ? { id } : undefined );

				const { cart } = resolveEnvelope( {
					draft: queryDraft,
					filter,
				} );

				return {
					...( cart && { cart } ),
					...( storedDraft && { draft: storedDraft } ),
				};
			},

			/**
			 * Type-invariant in-cart total for a product, in any purchasable
			 * form. See the type declaration on `Store['state'].inCartQuantity`
			 * for the polymorphism contract.
			 *
			 * Resolution is a single, type-agnostic rule expressed over data,
			 * never a type switch:
			 *
			 * 1. Resolve the product for `id` (default: `mainProductInContext`).
			 * 2. If it declares `grouped_products`, the "in cart" is the SUM of
			 *    each child's own in-cart quantity (a grouped parent carries no
			 *    line of its own). Simple/variable products have an empty
			 *    `grouped_products`, so they fall through to step 3 — the branch
			 *    is on a schema field, not on `type`.
			 * 3. Otherwise it is the product's own resolved line quantity, via
			 *    `findItem` (which resolves the purchasable/variation id).
			 *
			 * @param id Product id (defaults to the context product).
			 * @return The in-cart total, or `0` when unresolvable / absent.
			 */
			inCartQuantity( id?: number ): number {
				const productId = id ?? getContextProductId();
				if ( productId === undefined ) {
					return 0;
				}

				// Grouped parents have no line of their own — sum over children.
				// The branch is on a schema field (`grouped_products`), not on
				// product type, so it stays type-invariant (schema-field branches
				// are not type branches).
				const product = getProductsState().findProduct( {
					id: productId,
				} );
				const childIds = product?.grouped_products;
				if ( childIds && childIds.length > 0 ) {
					return childIds.reduce(
						( sum, childId ) =>
							sum + state.inCartQuantity( childId ),
						0
					);
				}

				return state.findItem( { id: productId } ).cart?.quantity ?? 0;
			},
		},
		actions: {
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
					wooConfig.errorMessages?.[ code ] || message;

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

			/**
			 * Add an item to the cart. ALWAYS posts `add-item` — never converts
			 * to `update-item` by client matching (adds are adds). The server owns
			 * line identity: two adds that differ only in identity-affecting
			 * `cart_item_data` become two lines; two that don't get merged
			 * server-side.
			 *
			 * Payload resolution:
			 * 1. explicit `payload` argument, else
			 * 2. `itemInContext.draft`, else
			 * 3. `{ id: mainProductInContext id, quantity: min purchase quantity }`
			 *    fallback (a bare Product Button with no draft). The min comes from
			 *    the product's `add_to_cart.minimum`, so a min-purchase product adds
			 *    a valid quantity.
			 *
			 * NO-OP with a dev-mode warning (returning `undefined`) when nothing
			 * is resolvable: no payload, no draft, and no product in context —
			 * there is nothing to add. Never throws (same policy as `removeItem`),
			 * because it is bound near user events.
			 *
			 * The purchasable id is resolved client-side at send time: posting the
			 * parent id and relying on server-side variation resolution is not
			 * universally safe, because the draft's `variation` carries shopper-
			 * facing attribute LABELS and custom-slug attributes (label ≠ slug) fail
			 * server resolution with "No matching variation found". `findProduct`
			 * resolves the variation id client-side; the full `variation` array is
			 * still sent so the server validates the attributes against the concrete
			 * variation. See the send-time purchasable-id swap below.
			 *
			 * Resolves with the affected cart line from the batch response so
			 * callers can keep their own draft↔line link (provenance is a return
			 * value, never stored on drafts). Does NOT clear drafts — draft death
			 * is surface-owned.
			 */
			*addItem(
				payload?: DraftItem
			): AsyncAction< CartItem | undefined > {
				// Resolve the payload: explicit → context draft → min-aware
				// fallback. The context product id is derived state (products
				// store's `mainProductInContext`), never a foreign context read.
				let itemPayload = payload;
				if ( ! itemPayload ) {
					const contextDraft = findDraftByKey( getContextDraftKey() );
					const contextProductId = getContextProductId();
					if ( contextDraft ) {
						itemPayload = contextDraft;
					} else if ( contextProductId !== undefined ) {
						itemPayload = {
							id: contextProductId,
							quantity: getContextMinPurchaseQuantity(),
						};
					}
				}

				if ( ! itemPayload || itemPayload.id === undefined ) {
					// Nothing to add — no payload, no context draft, no product
					// in context. NO-OP with a dev-mode warning, returning
					// `undefined` (the same failure policy as `removeItem`): these
					// actions are bound near user events, so a hard throw would
					// surface an uncaught error for a benign "nothing to act on"
					// state.
					if ( process.env.NODE_ENV !== 'production' ) {
						// eslint-disable-next-line no-console
						console.warn(
							'addItem: no payload, context draft, or product in context to add — nothing to add.'
						);
					}
					return undefined;
				}

				const { id, quantity, variation } = itemPayload;
				const targetQuantity =
					typeof quantity === 'number' ? quantity : 1;

				// SEND-TIME PURCHASABLE-ID SWAP. Posting the parent id + `variation`
				// array and letting the server resolve it is NOT universally safe:
				// the draft's `variation` holds the shopper-facing attribute labels,
				// and for attributes with custom slugs (label ≠ slug) the server's
				// resolution fails with "No matching variation found". The client's
				// `findProduct` matches labels tolerantly AND mirrors the server's
				// tie-breaking, so we swap in the resolved variation id here; the
				// server then validates the attributes against that concrete
				// variation, which accepts labels. Falls back to the payload's own
				// id when resolution is unavailable/ambiguous (simple products,
				// missing product data).
				const purchasableId = resolvePurchasableId( itemPayload );

				// Build the add-item body. The draft is the payload; strip the
				// bookkeeping-free reserved keys plus carry every namespaced
				// extension prop through to the server untouched (they may drive
				// `cart_item_data` via `woocommerce_store_api_add_to_cart_data`).
				const extensionProps = getDraftExtensionProps(
					itemPayload as Record< string, unknown >
				);
				const body = {
					id: purchasableId,
					quantity: targetQuantity,
					...( variation && { variation } ),
					...extensionProps,
				};

				// Contribute to the settle cycle (add semantics). Matches the
				// deprecated addCartItem's contribution for a fresh add.
				accumulateCartCycle( {
					quantityChanges: { productsPendingAdd: [ id ] },
					didAdd: true,
					showNotices: true,
				} );

				// Optimistic item: a keyless line the reconcile will replace.
				// `type` is a best-effort placeholder (there is no purchasable
				// type on the draft) until the server response commits the real
				// line; a variation selection implies a variation line.
				const optimisticItem: OptimisticCartItem = {
					id,
					quantity: targetQuantity,
					type: variation ? 'variation' : 'simple',
					...( variation && {
						variation: variation as CartVariationItem[],
					} ),
				};

				// MERGE-ONTO-CONFIRMED. When the posted identity matches EXACTLY
				// ONE already-confirmed line, we increment that line's quantity
				// optimistically instead of pushing a keyless synthetic line beside
				// it. This is what keeps the in-cart count ticking mid-flight (the
				// envelope and `inCartQuantity` exclude keyless lines, so a
				// synthetic twin would leave "3 in cart" reading the stale confirmed
				// quantity while the add is in flight — and would give the
				// exactly-one envelope rule two candidates, blanking the pairing).
				// The merge preserves the line's `key`, so a stepper acting on it
				// stays legal and rollback restores the pre-mutation quantity from
				// the queue snapshot exactly as before.
				//
				// Matching mirrors the envelope's generic narrowing: the purchasable
				// id must match AND the draft's namespaced props must deep-match the
				// line's `extensions[ns]` with no unaccounted content
				// (`isGenericExactPair`). Zero or several matches → keyless synthetic
				// push (genuinely ambiguous — let the server resolve identity).
				//
				// `sold_individually` lines are NEVER merged: the server rejects
				// add-to-existing for them (forcing quantity 1), so projecting an
				// incremented quantity would show a doomed value; we push the
				// synthetic instead and let the failure surface at settle as today.
				const draftProps = getDraftExtensionProps(
					itemPayload as Record< string, unknown >
				);
				const mergeCandidates = state.cart.items.filter(
					( item ): item is CartItem =>
						isCartItem( item ) &&
						item.id === purchasableId &&
						! item.sold_individually &&
						isGenericExactPair( draftProps, item )
				);
				const mergeTarget =
					mergeCandidates.length === 1
						? mergeCandidates[ 0 ]
						: undefined;

				try {
					const result = ( yield sendCartRequest( state, {
						path: '/wc/store/v1/cart/add-item',
						method: 'POST',
						body,
						applyOptimistic: () => {
							if ( mergeTarget ) {
								// Re-resolve the target inside applyOptimistic so we
								// mutate the live reactive line (the queue takes its
								// rollback snapshot before this runs, so the pre-merge
								// quantity is preserved for total-failure rollback).
								const liveTarget = state.cart.items.find(
									( item ): item is CartItem =>
										isCartItem( item ) &&
										item.key === mergeTarget.key
								);
								if ( liveTarget ) {
									liveTarget.quantity += targetQuantity;
								} else {
									state.cart.items.push( optimisticItem );
								}
							} else {
								state.cart.items.push( optimisticItem );
							}
							cartCycle.optimisticSnapshot = JSON.parse(
								JSON.stringify( state.cart )
							);
						},
					} ) ) as MutationResult< Cart >;

					if ( ! result.success ) {
						return undefined;
					}

					// Resolve the affected line from the committed cart via the
					// ladder (identity resolution is the server's, we just read
					// it back). `state.cart` is the committed response here.
					return resolveEnvelope( { draft: itemPayload } ).cart;
				} catch ( error ) {
					actions.showNoticeError( error as Error );
					return undefined;
				}
			},

			/**
			 * Update a server-confirmed line's quantity by key. Mutations use an
			 * explicit key — never an inferred pairing.
			 */
			*updateItem( {
				key,
				quantity,
			}: {
				key: string;
				quantity: number;
			} ): AsyncAction< void > {
				const existingItem = state.cart.items.find(
					( item ) => item.key === key
				);

				accumulateCartCycle( {
					quantityChanges: { cartItemsPendingQuantity: [ key ] },
					didAdd: true,
					showNotices: true,
				} );

				try {
					yield sendCartRequest( state, {
						path: '/wc/store/v1/cart/update-item',
						method: 'POST',
						body: { key, quantity },
						applyOptimistic: () => {
							if ( existingItem ) {
								const isSoldIndividually =
									isCartItem( existingItem ) &&
									existingItem.sold_individually;
								if ( ! isSoldIndividually ) {
									existingItem.quantity = quantity;
								}
							}
							cartCycle.optimisticSnapshot = JSON.parse(
								JSON.stringify( state.cart )
							);
						},
					} );
				} catch ( error ) {
					actions.showNoticeError( error as Error );
				}
			},

			/**
			 * Remove a line by key. Mutations use an explicit key — never an
			 * inferred pairing.
			 *
			 * The `key` is optional: when omitted it defaults to the current cart
			 * scope's line key (`context.cartItemKey ?? context.cartItem?.key` —
			 * the same source envelope step 1 uses), so a cart row can call
			 * `removeItem()` with no argument and remove its own line. NO-OP with
			 * a dev-mode warning when nothing resolves — it never throws, because
			 * it is bound near user events.
			 */
			*removeItem( key?: string ): AsyncAction< void > {
				const targetKey = key ?? getContextCartItemKey();
				if ( ! targetKey ) {
					if ( process.env.NODE_ENV !== 'production' ) {
						// eslint-disable-next-line no-console
						console.warn(
							'removeItem: no key passed and no cart line key in context — nothing to remove.'
						);
					}
					return;
				}

				// Contribute this request's changes to the cycle. Sync events
				// and notices are fired once per settle cycle by the batcher's
				// onCycleSettle handler (handleCartCycleSettle) — a removal is
				// not an "add", so it must not trigger the legacy event / a11y.
				accumulateCartCycle( {
					quantityChanges: { cartItemsPendingDelete: [ targetKey ] },
					didAdd: false,
				} );

				try {
					yield sendCartRequest( state, {
						path: '/wc/store/v1/cart/remove-item',
						method: 'POST',
						body: { key: targetKey },
						applyOptimistic: () => {
							state.cart.items = state.cart.items.filter(
								( item ) => item.key !== targetKey
							);
							// Capture post-optimistic cart for the cycle's
							// notice comparison (last writer in the cycle wins).
							cartCycle.optimisticSnapshot = JSON.parse(
								JSON.stringify( state.cart )
							);
						},
					} );
				} catch ( error ) {
					actions.showNoticeError( error as Error );
				}
			},

			/**
			 * Refresh the cart from the server.
			 */
			*refresh(): AsyncAction< void > {
				// Skip if queue is processing - it will apply server state when done
				if ( cartQueue?.getStatus().isProcessing ) {
					return;
				}

				// Skips if there's a pending request.
				if ( pendingRefresh ) return;

				pendingRefresh = true;

				try {
					const res = ( yield fetch( `${ restUrl }wc/store/v1/cart`, {
						method: 'GET',
						cache: 'no-store',
						headers: { 'Content-Type': 'application/json' },
					} ) ) as TypeYield< typeof fetch >;

					// Extract fresh nonce from response headers.
					refreshNonce( res );

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
					setTimeout( actions.refresh, refreshTimeout );

					// Increases the timeout exponentially.
					refreshTimeout *= 2;
				} finally {
					pendingRefresh = false;
				}
			},

			/**
			 * Merge a partial payload into a draft, creating it if missing.
			 * Returns the (editable) draft. This is the canonical write path for
			 * shopper input.
			 *
			 * Target draft key: `args.draftKey` (imperative override), else the
			 * context's `draftKey`, else `String(context product id)`. The draft's
			 * `id` (the product identity) comes from the payload, else the context
			 * product.
			 */
			upsertDraftItem(
				partialPayload: Partial< DraftItem > & { draftKey?: string }
			): DraftItem {
				const { draftKey: explicitKey, ...payload } = partialPayload;
				const productId = payload.id ?? getContextProductId();
				const draftKey =
					explicitKey ??
					getCartContext()?.draftKey ??
					( productId === undefined
						? undefined
						: String( productId ) );

				if ( draftKey === undefined || productId === undefined ) {
					throw new Error(
						'upsertDraftItem: no draft key or product id in the payload or context.'
					);
				}

				const existing = state.draftItems[ draftKey ];
				if ( existing ) {
					Object.assign( existing, payload, { id: productId } );
					return existing;
				}

				const draft = {
					...payload,
					id: productId,
				} as DraftItem;
				state.draftItems[ draftKey ] = draft;
				return draft;
			},

			/**
			 * Remove the draft for a draft key (defaults to the context's
			 * `draftKey`, else `String(context product id)`). NO-OP when none is
			 * resolvable — it never clears all drafts.
			 */
			removeDraftItem( { draftKey }: { draftKey?: string } = {} ): void {
				const targetKey = draftKey ?? getContextDraftKey();
				if ( targetKey === undefined ) {
					return;
				}
				delete state.draftItems[ targetKey ];
			},
		},
	},
	{ lock: universalLock }
);

// Trigger initial cart refresh.
actions.refresh();

window.addEventListener(
	'wc-blocks_store_sync_required',
	async ( event: Event ) => {
		const customEvent = event as CustomEvent< {
			type: string;
			id: number;
		} >;
		if ( customEvent.detail.type === 'from_@wordpress/data' ) {
			actions.refresh();
		}
	}
);
