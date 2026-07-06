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
import { doesCartItemMatchAttributes } from '../../utils/variations/does-cart-item-match-attributes';
import {
	type CartItemFilterPredicate,
	type CartItemFilterReference,
	type DraftItem,
	getDraftExtensionProps,
	isGenericExactPair,
	narrowCandidates,
	resolveExactlyOne,
} from './cart-item-matching';

export type {
	DraftItem,
	CartItemFilterPredicate,
	CartItemFilterReference,
} from './cart-item-matching';

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

/**
 * The cart store's OWN context namespace, `woocommerce/cart` (domain-scoped —
 * T12). It carries a cart surface's line identity (`cartItemKey`) and the
 * optional `cartItemFilter` escape hatch — a SERIALIZED ACTION REFERENCE
 * (`{ namespace, action }`), never a live function (context is serialized).
 * Core resolves it to a predicate at envelope-derivation time (see
 * `resolveCartItemFilter`).
 *
 * `cartItem` is the IMPLICIT per-row context that a `data-wp-each--cart-item`
 * directive (iterating `woocommerce/cart::state.cart.items`) keys under this
 * same namespace. Its `key` is a first-class envelope step-1 source, so cart
 * rows resolve their exact line with no client-side key bridge (and with SSR
 * parity, since the each-item context also exists server-side).
 *
 * The context carries NO `productId`: cross-domain product identity is resolved
 * through derived state (`woocommerce/products` store's `mainProductInContext`),
 * never by reading the products context namespace (T12).
 */
export type CartScopeContext = {
	cartItemKey?: string;
	cartItemFilter?: CartItemFilterReference;
	cartItem?: { key?: string };
};

/**
 * The `itemInContext` / `findItem` envelope: the cart line (only when exactly
 * one candidate survives the ladder), the editable context draft, and whether
 * this configuration is in the cart.
 *
 * `cart` is the raw Store API cart line (`CartItem`) — never an optimistic
 * in-flight item. Optimistic items lack a `key` and carry no `extensions` /
 * `item_data`, so they can never be an exact pairing target; excluding them
 * keeps the "cart" side of the envelope strictly server-truth, which is what
 * consumers feeding `updateItem({ key })` require.
 *
 * `isInCart` is configuration-level: survivors AFTER narrowing > 0 — "THIS
 * configuration is in the cart". It is `false` when the product is present
 * only as lines the draft cannot account for (e.g. a decorated bundle child,
 * or note-split lines with no matching draft props) — such surfaces fall back
 * to plain add-button UI. "In the cart in any form" (banners) is a raw
 * `state.cart.items` scan, not this flag.
 */
export type ItemEnvelope = {
	cart?: CartItem;
	draft?: DraftItem;
	isInCart: boolean;
};

export type Store = {
	state: {
		findItemInCart: ( args: {
			id: ClientCartItem[ 'id' ];
			key?: ClientCartItem[ 'key' ];
			variation?: ClientCartItem[ 'variation' ];
		} ) => CartItem | OptimisticCartItem | undefined;
		cart: Omit< Cart, 'items' > & {
			items: ( OptimisticCartItem | CartItem )[];
			totals: CartResponseTotals;
		};
		/**
		 * Editable array of pure `cart/add-item` payloads. One draft per product
		 * context (identity rule 3); `id` is the main/context product id and
		 * doubles as the draft's identity. Shopper input is written through the
		 * draft actions — `upsertDraftItem`, `removeDraftItem`, `clearDraftItems`
		 * (write policy CONTRACT). `state.cart` is read-only for consumers.
		 */
		draftItems: DraftItem[];
		/**
		 * The envelope for the current context: `{ cart, draft, isInCart }`.
		 * Read-only derived getter. The draft is keyed by the products store's
		 * `mainProductInContext` id; the exact line key comes from the
		 * `woocommerce/cart` context (T12).
		 */
		itemInContext: ItemEnvelope;
		/**
		 * Resolve an envelope explicitly.
		 *
		 * - `key` bypasses the ladder entirely (exact line; ignores any filter).
		 * - `id` runs the ladder against the context draft (or a bare draft built
		 *   from the id).
		 * - `filter` controls narrowing:
		 *   - a predicate FUNCTION → that predicate is the sole narrowing
		 *     authority (overrides any `context.cartItemFilter`);
		 *   - `false` → explicitly opt out of context filtering (generic rules
		 *     only, ignore `context.cartItemFilter`);
		 *   - absent → inherit `context.cartItemFilter` when called in an iAPI
		 *     scope; out-of-scope calls degrade silently to generic rules.
		 */
		findItem: ( args: {
			key?: string;
			id?: number;
			filter?: CartItemFilterPredicate | false;
		} ) => ItemEnvelope;
	};
	actions: {
		removeCartItem: ( key: string ) => Promise< void >;
		addCartItem: (
			args: ClientCartItem,
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
		/**
		 * ALWAYS posts `add-item` (never converts to update-item by client
		 * matching — identity rule 5). Defaults to `itemInContext.draft`; falls
		 * back to `{ id: productInContext id, quantity: 1 }`. Resolves with the
		 * affected cart line from the batch response (provenance is a return
		 * value, never stored on drafts).
		 */
		addItem: ( payload?: DraftItem ) => Promise< CartItem | undefined >;
		updateItem: ( args: {
			key: string;
			quantity: number;
		} ) => Promise< void >;
		removeItem: ( key: string ) => Promise< void >;
		refresh: () => Promise< void >;
		/**
		 * Merge a partial payload into the context draft (matched by product id),
		 * creating it if missing. Returns the draft.
		 */
		upsertDraftItem: ( partialPayload: Partial< DraftItem > ) => DraftItem;
		removeDraftItem: ( args?: { productId?: number } ) => void;
		clearDraftItems: ( args?: { productId?: number } ) => void;
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
 * and store notices are cycle-level concerns (issues #63333 and #63560), so the
 * cart actions no longer fire them per request; instead each request contributes
 * to this per-cycle accumulator, and `handleCartCycleSettle` consumes it once.
 *
 * This is reset at the end of every settle cycle (see `handleCartCycleSettle`).
 */
type CartCycleAccumulator = {
	/** Aggregated quantity changes across every request in the cycle. */
	quantityChanges: QuantityChanges;
	/**
	 * True when the cycle contained at least one add/update request. Controls
	 * the legacy `wc-blocks_added_to_cart` event and the a11y announcement,
	 * which a pure removal cycle must not fire (preserves prior behavior where
	 * `removeCartItem` never triggered them).
	 */
	didAdd: boolean;
	/**
	 * Whether the cycle's info/error notice pass should run. Per-request
	 * opt-outs aggregate with logical AND: if ANY request in the cycle passes
	 * `showCartUpdatesNotices: false`, the whole cycle's notice pass is
	 * suppressed. A caller opting out is asserting "don't surface generic cart
	 * notices for my operation"; honoring that matches the old
	 * `batchAddCartItems`, where a single flag governed the entire batch.
	 * `removeCartItem` has no opt-out, so it always leaves this `true`.
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
 * `refreshCartItems` (which bails while processing) can run, preserving the
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
			// pass once per settle cycle (issues #63333 and #63560). Runs while
			// the queue is still processing, so refreshes stay guarded.
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
 * The cart store needs the products store only for deterministic variation
 * resolution (identity rule 6) — turning a draft's main product id + variation
 * selection into the purchasable id that a cart line carries. This is a
 * core-internal, documented lazy read (schema: "a lazy cross-store
 * `store('woocommerce/products', …)` read"). We read it lazily rather than at
 * module load so the products store's own registration order doesn't matter.
 */
function getProductsState(): ProductsStore[ 'state' ] | null {
	try {
		const { state: productsState } = store< ProductsStore >(
			'woocommerce/products',
			{},
			{ lock: universalLock }
		);
		return productsState;
	} catch {
		// Products store not registered on this surface (e.g. cart/checkout
		// pages that never load product data). Callers fall back to the draft's
		// own id — correct for simple products, and there is no variation to
		// resolve without product data anyway.
		return null;
	}
}

/**
 * Resolve a draft's main/context product id + variation selection to the
 * purchasable id that a matching cart line carries (identity rule 6).
 *
 * Cart lines expose the purchasable id in their `id` field — the variation id
 * for a variation, the product id otherwise (Store API `CartItemSchema`). To
 * pair a draft against lines we must resolve the same purchasable id the server
 * would, which `findProduct` does deterministically (mirrors the server's
 * `find_matching_product_variation`, T1). If the products store or the product
 * is unavailable, or the selection is ambiguous, we degrade to the draft's own
 * `id` — this is safe: for a simple product the id is already purchasable, and
 * an unresolved variable selection simply won't match any variation line.
 *
 * @param draft The draft whose purchasable id we want.
 * @return The resolved purchasable id (variation id or product id).
 */
function resolvePurchasableId( draft: DraftItem ): number {
	const productsState = getProductsState();
	if ( ! productsState ) {
		return draft.id;
	}

	const resolved: ProductResponseItem | null = productsState.findProduct( {
		id: draft.id,
		selectedAttributes: ( draft.variation ?? null ) as
			| SelectedAttributes[]
			| null,
	} );

	return resolved?.id ?? draft.id;
}

/**
 * Read the cart store's OWN context (`woocommerce/cart`), or `null` when called
 * outside a directive scope (e.g. from a plain module call). Out-of-scope reads
 * degrade silently to "no cart context" rather than throwing.
 *
 * This never reads the products context namespace — the context product id is
 * resolved through derived state (`getContextProductId`), per the T12
 * cross-domain rule.
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
 * `woocommerce/products` context or that store's global state). Its id is the
 * key the cart store's drafts live under (identity rule 3).
 *
 * Cross-domain resolution goes through this derived getter ONLY: the cart store
 * never reads the `woocommerce/products` context namespace itself (T12). Degrades
 * to `undefined` out of product scope (or when the products store isn't
 * registered) — the envelope then resolves conservatively (no draft).
 *
 * @return The context product id, or `undefined` when unavailable.
 */
function getContextProductId(): number | undefined {
	const productsState = getProductsState();
	if ( ! productsState ) {
		return undefined;
	}
	try {
		return productsState.mainProductInContext?.id;
	} catch {
		// mainProductInContext reads the products context; out of scope it
		// throws — degrade to "no context product".
		return undefined;
	}
}

/**
 * Find the draft whose `id` matches a product id (identity rule 3: one draft
 * per product context, keyed by the main/context product id).
 *
 * @param productId The main/context product id.
 * @return The matching draft, or undefined.
 */
function findDraftByProductId(
	productId: number | undefined
): DraftItem | undefined {
	if ( productId === undefined ) {
		return undefined;
	}
	return state.draftItems.find( ( draft ) => draft.id === productId );
}

/**
 * Type guard: only server-confirmed cart lines (which carry a `key`) can be an
 * exact pairing target. Optimistic in-flight items lack a key and carry no
 * `extensions` / `item_data`.
 */
function isServerCartItem(
	item: OptimisticCartItem | CartItem
): item is CartItem {
	return typeof ( item as CartItem ).key === 'string' && 'name' in item;
}

/**
 * Emit a dev-mode warning about a broken `cartItemFilter` reference.
 *
 * Gated behind `globalThis.SCRIPT_DEBUG` (the iAPI dev-mode flag): a broken
 * reference degrades gracefully to generic narrowing in production (no
 * warning), but surfaces loudly during development so the extension author sees
 * the mistake. Never throws — a bad reference must not break derivation.
 *
 * @param message The warning message.
 */
function warnFilter( message: string ): void {
	if ( ( globalThis as { SCRIPT_DEBUG?: boolean } ).SCRIPT_DEBUG ) {
		// eslint-disable-next-line no-console
		console.warn( `[woocommerce/cart] cartItemFilter: ${ message }` );
	}
}

/**
 * Resolve a serialized `cartItemFilter` reference (`{ namespace, action }`) to a
 * live predicate function, via the iAPI store registry.
 *
 * Resolution mechanics:
 *
 * - The `namespace` is looked up with the public `store( namespace )` accessor.
 *   We do NOT pass a lock: an extension exposing a filter action registers a
 *   *public* store, and a plain `store( namespace )` read of a public store is a
 *   no-op that returns its proxy. If the extension locked its store, the lookup
 *   throws — we catch it and treat it as an unresolvable reference (degrade).
 * - The `action` is either a plain name or a dotted path:
 *   - **dotted** (`"actions.matchLine"`, `"actions.filters.byNote"`) → walked
 *     from the store root, property by property.
 *   - **plain** (`"matchLine"`) → tried first at `store.actions[ action ]`, then
 *     at the store root `store[ action ]` (tolerates authors who expose the
 *     predicate off `state`/root rather than `actions`).
 * - The final target must be a function; anything else (unknown namespace,
 *   unknown action, non-function value) resolves to `null`.
 *
 * Failure behavior: unknown namespace/action or a non-function target returns
 * `null` — the caller behaves as if no filter is set (generic narrowing runs) —
 * with a dev-mode `console.warn` (see `warnFilter`). Never throws.
 *
 * @param reference The serialized filter reference from context.
 * @return The resolved predicate, or `null` when it cannot be resolved.
 */
function resolveCartItemFilter(
	reference: CartItemFilterReference | undefined
): CartItemFilterPredicate | null {
	if (
		! reference ||
		typeof reference.namespace !== 'string' ||
		typeof reference.action !== 'string'
	) {
		return null;
	}

	const { namespace, action } = reference;

	let storeProxy: Record< string, unknown >;
	try {
		// Public read: no lock passed. Locked extension stores throw here and
		// fall through to the catch → treated as unresolvable.
		storeProxy = store( namespace ) as Record< string, unknown >;
	} catch {
		warnFilter(
			`store "${ namespace }" could not be read (is it locked?); ignoring filter.`
		);
		return null;
	}

	// Resolve the target: dotted path walked from the store root; a plain name
	// tried on `actions` first, then the root.
	let target: unknown;
	if ( action.includes( '.' ) ) {
		target = action.split( '.' ).reduce< unknown >( ( node, segment ) => {
			if ( node && typeof node === 'object' ) {
				return ( node as Record< string, unknown > )[ segment ];
			}
			return undefined;
		}, storeProxy );
	} else {
		const actions = storeProxy.actions as
			| Record< string, unknown >
			| undefined;
		target = actions?.[ action ] ?? storeProxy[ action ];
	}

	if ( typeof target !== 'function' ) {
		warnFilter(
			`action "${ action }" on store "${ namespace }" is not a function; ignoring filter.`
		);
		return null;
	}

	return target as CartItemFilterPredicate;
}

/**
 * Resolve the `{ cart, draft, isInCart }` envelope via the resolution ladder.
 *
 * Ladder:
 *
 * 1. A cart-context line key (`context.cartItemKey`, or a `data-wp-each` item
 *    context's `cartItem.key` — both in the `woocommerce/cart` namespace) →
 *    that exact line. Filters NEVER run; cart surfaces are always exact.
 *    `isInCart` reflects whether the line exists. (This step is unchanged by
 *    the `cartItemFilter` escape hatch.)
 * 2. Candidates = lines whose purchasable id matches the draft's resolved id
 *    (variation-id resolution only — no attribute matching at cart level,
 *    Raluca's rule). Then EITHER:
 *    - `filter` (a resolved `cartItemFilter` predicate) is set → the predicate
 *      is the **sole** narrowing authority: it REPLACES the generic narrowing
 *      (per-namespace compare + presence heuristic), it does not compose with
 *      it. This is what lets a surface (e.g. a bundle editor) pair with a line
 *      the presence heuristic would otherwise exclude.
 *    - otherwise → generic narrowing: per-namespace deep-compare of the draft's
 *      namespaced props vs the line's `extensions[ns]`, plus the presence
 *      heuristic (a line with visible `item_data`/`extensions` the draft doesn't
 *      account for is excluded).
 * 3. Exactly one survivor → `cart`; zero or several → undefined. NEVER
 *    first-match fallback. `isInCart` = survivors AFTER narrowing > 0 ("THIS
 *    configuration is in the cart") — pre-narrowing candidates do NOT count:
 *    a product present only as lines the draft cannot account for (e.g. a
 *    decorated bundle child) yields `isInCart: false`, so the surface renders
 *    plain add-button UI instead of in-cart UI without a safe mutation target.
 *    Invisible bare twins both survive → `isInCart: true` with `cart`
 *    undefined (genuinely ambiguous presence). This exactly-one rule and the
 *    survivors-based `isInCart` apply to the filter's survivor set identically.
 *
 * @param opts.draft   The draft to pair (usually the context draft).
 * @param opts.key     An explicit cart item key (bypasses everything).
 * @param opts.filter  A resolved `cartItemFilter` predicate. When present it is
 *                     the sole narrowing authority (replaces generic narrowing).
 * @param opts.context The `woocommerce/cart` context, passed to the predicate.
 * @return The resolved envelope.
 */
function resolveEnvelope( opts: {
	draft?: DraftItem | undefined;
	key?: string | undefined;
	filter?: CartItemFilterPredicate | null | undefined;
	context?: CartScopeContext | null | undefined;
} ): ItemEnvelope {
	const { draft, key, filter, context } = opts;

	// Step 1: explicit key wins — exact, no filters, no narrowing.
	if ( key ) {
		const line = state.cart.items.find( ( item ) => item.key === key ) as
			| CartItem
			| undefined;
		return {
			...( line && { cart: line } ),
			...( draft && { draft } ),
			isInCart: Boolean( line ),
		};
	}

	// Without a draft (and without a key) there is nothing to pair against.
	if ( ! draft ) {
		return { isInCart: false };
	}

	// Step 2: id + variation candidates (purchasable-id resolution only).
	const purchasableId = resolvePurchasableId( draft );
	const candidates = state.cart.items.filter(
		( item ): item is CartItem =>
			isServerCartItem( item ) && item.id === purchasableId
	);

	// Step 2 narrowing. When a filter is set it is the SOLE narrowing authority
	// (replace, not compose — the generic narrowing does NOT also run). Step 3
	// (exactly-one + survivors-based isInCart) then applies identically to the
	// survivor set, whichever narrowing produced it.
	let narrow: ( line: CartItem ) => boolean;
	if ( filter ) {
		narrow = ( line ) =>
			filter( line, { draft, context: context ?? null } );
	} else {
		const draftProps = getDraftExtensionProps( draft );
		narrow = ( line ) => isGenericExactPair( draftProps, line );
	}
	const survivors = narrowCandidates( candidates, narrow );
	const cart = resolveExactlyOne( survivors );

	return {
		...( cart && { cart } ),
		draft,
		isInCart: survivors.length > 0,
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
			// NOTE: `draftItems` is deliberately NOT declared here. It is a plain
			// writable slot (like `cart`) that surfaces seed SERVER-SIDE via
			// `wp_interactivity_state` (draft "birth" — see the shared-stores
			// schema) and mutate directly (write policy). Declaring it in this
			// client definition would WIPE the server-seeded drafts: the iAPI
			// runtime merges server data first (`populateInitialData`,
			// override=false) and then merges this definition at module
			// registration with override=true — and arrays are not deep-merged,
			// so a `draftItems: []` here replaces the seeded array wholesale
			// (verified against @wordpress/interactivity deepMergeRecursive: the
			// non-plain-object branch runs `Object.defineProperty` whenever
			// `override` is set). The slot is instead initialized after
			// registration, only when no server seed exists — see below the
			// store definition. (T6 finding; shared-store change pending
			// maintainer sign-off.)

			/**
			 * The `{ cart, draft, isInCart }` envelope for the current context.
			 * Derived read-only getter.
			 *
			 * `draft` is the `draftItems` entry whose `id` === the context product
			 * id — resolved via derived state (`getContextProductId`, which reads
			 * the products store's `mainProductInContext`), NOT by reading the
			 * products context namespace (T12 cross-domain rule). Shopper input is
			 * written back through the draft actions (`upsertDraftItem`) per the
			 * write-policy contract. `cart` and `isInCart` come from the resolution
			 * ladder.
			 *
			 * The exact line key comes from the cart store's own context
			 * (`woocommerce/cart`): an explicit `cartItemKey`, or the each-item
			 * `cartItem.key` a `data-wp-each--cart-item` directive keys under this
			 * namespace (envelope step 1 accepts either).
			 *
			 * When `context.cartItemFilter` is set, its resolved predicate is the
			 * sole narrowing authority (it replaces generic narrowing). A broken
			 * reference resolves to `null` → generic narrowing runs (graceful
			 * degradation, dev-mode warning). Innermost-context shadowing falls
			 * out of iAPI context inheritance for free: `getCartContext()` reads
			 * the nearest `woocommerce/cart` context, so an inner region's
			 * `cartItemFilter` naturally overrides an outer one.
			 */
			get itemInContext(): ItemEnvelope {
				const context = getCartContext();
				const draft = findDraftByProductId( getContextProductId() );
				const key = context?.cartItemKey ?? context?.cartItem?.key;
				const filter = resolveCartItemFilter( context?.cartItemFilter );
				return resolveEnvelope( { draft, key, filter, context } );
			},

			/**
			 * Explicit envelope lookup.
			 *
			 * - `key` bypasses the ladder (exact line, no filter).
			 * - `id` runs the ladder against the context draft for that product
			 *   id (or a bare draft built from the id when no context draft
			 *   exists).
			 * - `filter` (add-on to the ladder):
			 *   - a predicate FUNCTION → overrides any `context.cartItemFilter`
			 *     (callers in JS can pass real functions);
			 *   - `false` → explicitly opt out of context filtering (generic
			 *     rules run, `context.cartItemFilter` ignored);
			 *   - absent → inherit `context.cartItemFilter`.
			 *
			 * Scope caveat: inheriting the context filter requires an iAPI scope,
			 * because it reads `getContext('woocommerce/cart')`. In scope means
			 * the call happens synchronously inside a directive callback or an
			 * action/derived-state getter (see `getCartContext`). Out of scope
			 * — a plain module call, a `setTimeout`/`Promise.then` callback not
			 * wrapped in `withScope`, or an async continuation after a `yield` in
			 * a generator (the scope is only guaranteed for the synchronous part)
			 * — `getContext` throws, `getCartContext()` returns `null`, and the
			 * lookup degrades silently to generic rules. Pass an explicit
			 * `filter` (or `filter: false`) to make the behavior deterministic
			 * regardless of scope.
			 */
			findItem( {
				key,
				id,
				filter,
			}: {
				key?: string;
				id?: number;
				filter?: CartItemFilterPredicate | false;
			} ): ItemEnvelope {
				if ( key ) {
					const draft =
						id !== undefined
							? findDraftByProductId( id )
							: undefined;
					return resolveEnvelope( { draft, key } );
				}

				const context = getCartContext();
				const draft =
					findDraftByProductId( id ) ??
					( id !== undefined ? { id } : undefined );

				// Filter precedence:
				// - explicit predicate → use it verbatim (overrides context).
				// - `false` → opt out: generic rules, ignore context filter.
				// - absent → inherit the (possibly resolved) context filter.
				let resolvedFilter: CartItemFilterPredicate | null;
				if ( typeof filter === 'function' ) {
					resolvedFilter = filter;
				} else if ( filter === false ) {
					resolvedFilter = null;
				} else {
					resolvedFilter = resolveCartItemFilter(
						context?.cartItemFilter
					);
				}

				return resolveEnvelope( {
					draft,
					filter: resolvedFilter,
					context,
				} );
			},

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
			/**
			 * @deprecated (T4) Use `removeItem( key )`. Thin delegating wrapper
			 * kept so untouched consumers and the `woocommerce` alias keep
			 * working until T6/T7 migrate them. Dies in T10.
			 */
			*removeCartItem( key: string ): AsyncAction< void > {
				// Contribute this request's changes to the cycle. Sync events
				// and notices are fired once per settle cycle by the batcher's
				// onCycleSettle handler (handleCartCycleSettle) — a removal is
				// not an "add", so it must not trigger the legacy event / a11y.
				accumulateCartCycle( {
					quantityChanges: { cartItemsPendingDelete: [ key ] },
					didAdd: false,
				} );

				try {
					yield sendCartRequest( state, {
						path: '/wc/store/v1/cart/remove-item',
						method: 'POST',
						body: { key },
						applyOptimistic: () => {
							state.cart.items = state.cart.items.filter(
								( item ) => item.key !== key
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
			 * @deprecated (T4) Use `addItem( payload )` / `updateItem({ key,
			 * quantity })`. Thin delegating wrapper retaining the legacy
			 * add-or-update-by-client-matching behavior so untouched consumers
			 * and the `woocommerce` alias keep working until T6/T7 migrate them.
			 * Dies in T10. New code MUST NOT rely on the implicit
			 * update-item-on-match path here — identity rule 5 ("adds are adds").
			 */
			*addCartItem(
				{ id, key, quantity, quantityToAdd, variation }: ClientCartItem,
				{ showCartUpdatesNotices = true }: CartUpdateOptions = {}
			): AsyncAction< void > {
				if ( quantity !== undefined && quantityToAdd !== undefined ) {
					throw new Error(
						'addCartItem: pass either quantity or quantityToAdd, not both.'
					);
				}

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

				// Only treat as update if the item has a key (server-confirmed item).
				// Optimistic items don't have keys, so we should add them instead.
				const isUpdate = !! existingItem?.key;
				const endpoint = isUpdate ? 'update-item' : 'add-item';

				// Track what changes we're making for the aggregated sync event.
				const quantityChanges: QuantityChanges = isUpdate
					? {
							cartItemsPendingQuantity: existingItem?.key
								? [ existingItem.key ]
								: [],
					  }
					: { productsPendingAdd: [ id ] };

				// Contribute to the cycle. The batcher fires the legacy event,
				// a11y announcement, sync event and notice pass once when the
				// queue settles (handleCartCycleSettle). This is an add/update,
				// so it flips the cycle's didAdd flag; `showCartUpdatesNotices`
				// aggregates with AND across the cycle.
				accumulateCartCycle( {
					quantityChanges,
					didAdd: true,
					showNotices: showCartUpdatesNotices,
				} );

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

				try {
					yield sendCartRequest( state, {
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
							// Capture post-optimistic cart for the cycle's
							// notice comparison (last writer in the cycle wins).
							cartCycle.optimisticSnapshot = JSON.parse(
								JSON.stringify( state.cart )
							);
						},
					} );
				} catch ( error ) {
					// Show error notice
					actions.showNoticeError( error as Error );
				}
			},

			/**
			 * @deprecated (T4) Use `refresh()`. Thin delegating wrapper kept for
			 * the `woocommerce` alias and untouched consumers until T6/T7
			 * migrate them. Dies in T10. (Still the real implementation the
			 * module bootstrap and the sync-event listener call.)
			 */
			*refreshCartItems(): AsyncAction< void > {
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

			// ---- New T4 API ----

			/**
			 * Add an item to the cart. ALWAYS posts `add-item` — never converts
			 * to `update-item` by client matching (identity rule 5, "adds are
			 * adds"; PR #65869). The server owns line identity: two adds that
			 * differ only in identity-affecting `cart_item_data` become two
			 * lines; two that don't get merged server-side.
			 *
			 * Payload resolution:
			 * 1. explicit `payload` argument, else
			 * 2. `itemInContext.draft`, else
			 * 3. `{ id: productInContext id, quantity: 1 }` fallback (a bare
			 *    Product Button with no seeded draft).
			 *
			 * The draft is POSTed as-is: the `add-item` endpoint accepts a
			 * variable parent id + full `variation` array and resolves the
			 * variation id server-side via `find_matching_product_variation`
			 * (verified in `CartController::parse_variation_data`), so no
			 * client-side purchasable-id swap is needed at send time.
			 *
			 * Resolves with the affected cart line from the batch response so
			 * callers can keep their own draft↔line link (provenance is a return
			 * value, never stored on drafts). Does NOT clear drafts — draft death
			 * is surface-owned (T6).
			 */
			*addItem(
				payload?: DraftItem
			): AsyncAction< CartItem | undefined > {
				// Resolve the payload: explicit → context draft → fallback.
				// The context product id is derived state (products store's
				// `mainProductInContext`), never a foreign context read (T12).
				let itemPayload = payload;
				if ( ! itemPayload ) {
					const contextProductId = getContextProductId();
					const contextDraft =
						findDraftByProductId( contextProductId );
					if ( contextDraft ) {
						itemPayload = contextDraft;
					} else if ( contextProductId !== undefined ) {
						itemPayload = { id: contextProductId, quantity: 1 };
					}
				}

				if ( ! itemPayload || itemPayload.id === undefined ) {
					// Nothing to add — no payload, no context draft, no product
					// in context. Surface a doubt via console rather than throw.
					// eslint-disable-next-line no-console
					console.error(
						'addItem: no payload, context draft, or product in context to add.'
					);
					return undefined;
				}

				const { id, quantity, variation } = itemPayload;
				const targetQuantity =
					typeof quantity === 'number' ? quantity : 1;

				// SEND-TIME PURCHASABLE-ID SWAP (identity rule 6, schema: "addItem()
				// resolves the purchasable id (selected variation) at send time via
				// findProduct"). Posting the parent id + `variation` array and
				// letting the server resolve it is NOT universally safe: the
				// draft's `variation` holds the shopper-facing attribute labels,
				// and for attributes with custom slugs (label ≠ slug) the server's
				// resolution fails with "No matching variation found" (verified in
				// the T6 e2e run against a custom-slug variable product). The
				// client's `findProduct` matches labels tolerantly AND mirrors the
				// server's tie-breaking (T1), so we swap in the resolved variation
				// id here; the server then validates the attributes against that
				// concrete variation, which accepts labels. Falls back to the
				// payload's own id when resolution is unavailable/ambiguous
				// (simple products, missing product data — same as before).
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

				try {
					const result = ( yield sendCartRequest( state, {
						path: '/wc/store/v1/cart/add-item',
						method: 'POST',
						body,
						applyOptimistic: () => {
							state.cart.items.push( optimisticItem );
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
			 * explicit key (identity rule 5) — never an inferred pairing.
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
			 * Remove a line by key. Delegates to the deprecated `removeCartItem`
			 * wrapper (identical behavior); the new name is the T4 public API.
			 */
			*removeItem( key: string ): AsyncAction< void > {
				yield actions.removeCartItem( key );
			},

			/**
			 * Refresh the cart from the server. Delegates to the deprecated
			 * `refreshCartItems` wrapper.
			 */
			*refresh(): AsyncAction< void > {
				yield actions.refreshCartItems();
			},

			/**
			 * Merge a partial payload into the current context draft (matched by
			 * the payload's `id`, else the context product id — derived state, not
			 * a foreign context read, T12), creating the draft if missing. Returns
			 * the (editable) draft. This is the canonical write path for shopper
			 * input (write policy CONTRACT).
			 */
			upsertDraftItem( partialPayload: Partial< DraftItem > ): DraftItem {
				const targetId = partialPayload.id ?? getContextProductId();

				if ( targetId === undefined ) {
					throw new Error(
						'upsertDraftItem: no product id in the payload or context.'
					);
				}

				const existing = findDraftByProductId( targetId );
				if ( existing ) {
					Object.assign( existing, partialPayload, { id: targetId } );
					return existing;
				}

				const draft = {
					...partialPayload,
					id: targetId,
				} as DraftItem;
				state.draftItems.push( draft );
				return draft;
			},

			/**
			 * Remove the draft for a product id (defaults to the context product
			 * id — derived state, not a foreign context read, T12).
			 */
			removeDraftItem( {
				productId,
			}: { productId?: number } = {} ): void {
				const targetId = productId ?? getContextProductId();
				if ( targetId === undefined ) {
					return;
				}
				state.draftItems = state.draftItems.filter(
					( draft ) => draft.id !== targetId
				);
			},

			/**
			 * Clear drafts. With a `productId` (or the context product id — derived
			 * state, T12), clears only that draft; called with an explicit `{}`/no
			 * context it clears all drafts.
			 */
			clearDraftItems( {
				productId,
			}: { productId?: number } = {} ): void {
				const targetId = productId ?? getContextProductId();
				if ( targetId === undefined ) {
					state.draftItems = [];
					return;
				}
				state.draftItems = state.draftItems.filter(
					( draft ) => draft.id !== targetId
				);
			},
		},
	},
	{ lock: universalLock }
);

// Guarantee the `draftItems` slot exists WITHOUT clobbering server-seeded
// drafts. `wp_interactivity_state` seeds (draft "birth") land in the store
// before this module registers; a conditional assignment preserves them, while
// pages with no PHP seed at all (no purchase surface rendered any draft, and
// `BlocksSharedState::load_cart_state` never ran) still get a usable empty
// array so `state.draftItems.find(...)` never explodes. This replaces the old
// `draftItems: []` in the store definition above, which wiped the seeds (see
// the NOTE in the definition).
if ( ! state.draftItems ) {
	state.draftItems = [];
}

/**
 * Backwards-compatibility alias on the bare `woocommerce` namespace.
 *
 * The cart store moved to `woocommerce/cart` (T2 of the shared-stores plan).
 * Existing consumers (mini-cart, product button, add-to-cart-with-options,
 * the wishlist family) still call `store( 'woocommerce' )` and read
 * `state.cart`, `state.findItemInCart` and the cart `actions`. This alias keeps
 * them working unchanged until each consumer migrates in its own task; it is
 * removed in T10.
 *
 * Why this is safe with the iAPI `store()` merge/lock semantics:
 *
 * - We expose the *same underlying* `actions` and `findItemInCart` **function
 *   references**. iAPI's `deepMerge` copies function/primitive properties by
 *   descriptor, so the alias shares the identical functions — a single
 *   implementation, no divergent copies. Those functions close over the
 *   module-level `state` (the `woocommerce/cart` proxy), so they operate on the
 *   real cart state regardless of which namespace invoked them.
 * - `cart` (and `draftItems`) are exposed as **getters** that read the live
 *   `state.*` slot. iAPI would otherwise deep-*copy* a plain-object/array into a
 *   separate `woocommerce` proxy (breaking cross-namespace reactivity); a getter
 *   is copied by descriptor and re-reads the single reactive source on every
 *   access, so consumers reading through the alias stay in sync with mutations
 *   applied to `woocommerce/cart`.
 * - `itemInContext` / `findItem` are copied by descriptor (getter / function),
 *   same as `findItemInCart`, and close over the module-level `state`. They
 *   resolve context from the DOMAIN-SCOPED namespaces (`woocommerce/cart` for
 *   the line key/filter, the products store's `mainProductInContext` for the
 *   draft product id — T12), so the envelope resolves identically whether the
 *   getter is reached directly or through this legacy state/actions alias.
 * - Both stores are created with the same `universalLock`, so the alias
 *   registration passes the lock check and no store is unlocked/published.
 */
store< Store >(
	'woocommerce',
	{
		state: {
			get cart() {
				return state.cart;
			},
			get draftItems() {
				return state.draftItems;
			},
			findItemInCart: state.findItemInCart,
			findItem: state.findItem,
			get itemInContext() {
				return state.itemInContext;
			},
		},
		actions,
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
