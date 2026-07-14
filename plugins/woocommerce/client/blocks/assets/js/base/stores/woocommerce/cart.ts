/**
 * External dependencies
 */
import {
	getConfig,
	getContext,
	getServerContext,
	store,
} from '@wordpress/interactivity';
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
import fastDeepEqual from 'fast-deep-equal/es6';

/**
 * Internal dependencies
 */
import '@woocommerce/stores/woocommerce/products';
import type { ProductsStore } from '@woocommerce/stores/woocommerce/products';
import { triggerAddedToCartEvent } from './legacy-events';
import {
	createMutationQueue,
	MutationRequest,
	type MutationQueue,
	type MutationResult,
} from './mutation-batcher';
import { attributeNamesMatch } from '../../utils/variations/attribute-matching';

export type WooCommerceConfig = {
	messages?: {
		addedToCartText?: string;
	};
	placeholderImgSrc?: string;
	currency?: Currency;
	nonOptimisticProperties?: string[];
};

export type SelectedAttributes = Omit< CartVariationItem, 'raw_attribute' >;

/**
 * An opaque scope identifier.
 *
 * Scopes are deterministic, namespaced strings minted by the surfaces that
 * establish them (the page itself, a Product Collection loop item, a Single
 * Product block, an extension). The only guaranteed property is that equal
 * strings denote the same scope; no other structure is assumed by the store.
 */
export type Scope = string;

/**
 * A single scope's draft cart item.
 *
 * A draft is exactly a Store API `cart/add-item` request payload — no
 * mapping layer. Extension props (e.g. `'my-plugin/gift-note'`) ride at the
 * payload root, namespaced, exactly as the Store API accepts them; there is
 * no separate wrapper for them here (contrast with the read-only
 * `CartItem.extensions`, which reflects the *server's* response shape once a
 * line exists).
 */
export type DraftItem = {
	/**
	 * The product or variation id being drafted. This is also the per-scope
	 * uniqueness key: `upsertDraftItem` merges into the draft whose `id`
	 * matches and appends otherwise, so a scope holds at most one draft per
	 * `id`.
	 */
	id: number;
	/** The quantity to add. */
	quantity: number;
	/** Chosen attributes, for a variation draft. */
	variation?: SelectedAttributes[];
	/** Namespaced extension props riding at the payload root (e.g. `'my-plugin/gift-note'`). */
	[ extensionProp: string ]: unknown;
};

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

/**
 * The read-only envelope returned by `findItem`/`itemInContext`.
 *
 * Pairing between a scope's draft and a cart line never guesses: `cartItem`
 * is populated only when the pairing ladder resolves to exactly one
 * candidate line — a context-known line `key`, or an unambiguous
 * product/variation identity plus namespaced extension-prop match. Any
 * remaining ambiguity (including "this product is in the cart, but not as a
 * single identifiable line") leaves `cartItem` `undefined`; the server owns
 * cart-line identity, so the client never guesses at it. No consumer needs
 * the "in the cart, but no single line can be paired" tri-state, so the
 * envelope carries only `cartItem`/`draft`.
 */
export type Envelope = {
	/**
	 * The paired cart line, when the pairing ladder resolved to exactly one
	 * candidate. `undefined` when no line matches, or more than one
	 * candidate line matches and none can be told apart.
	 */
	cartItem?: CartItem | OptimisticCartItem | undefined;
	/** The resolved scope's draft for the product, when one exists. */
	draft?: DraftItem | undefined;
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
		/**
		 * Draft cart items awaiting `addItem`, keyed by scope.
		 *
		 * Each scope holds an array of drafts, at most one per product `id`.
		 * Drafts live alongside — never inside — the read-only `cart`
		 * mirror above, and are written exclusively through
		 * `upsertDraftItem`/`removeDraftItem`.
		 */
		draftItems: Record< Scope, DraftItem[] >;
		/**
		 * The page-wide scope, established once per request.
		 *
		 * Server-seeded via `wp_interactivity_state( 'woocommerce/cart', … )`
		 * by the server-side scope service, so every surface that is not
		 * inside a scope-overriding container (a Product Collection loop
		 * item, a Single Product block) shares this scope and stays in sync.
		 *
		 * Deliberately never given a client-side initial value: the store
		 * definition's state deep-merges over the server-provided state
		 * during registration, so a client default would overwrite the
		 * server-seeded scope. On a page where the server seeds no cart
		 * state this stays `undefined` and `currentScope` degrades to an
		 * (invalid) empty scope.
		 */
		pageScope?: Scope;
		/**
		 * The scope to read from or write to when a consumer passes none.
		 *
		 * Resolves the `scope` carried in the shared `woocommerce` context
		 * namespace — set by a scope-overriding container for its subtree —
		 * when present, else falls back to `pageScope`. This getter is the
		 * single place that implements `context.scope ?? pageScope`; no
		 * other code, client or server, should re-implement that
		 * conditional. Reading the shared context outside of a directive's
		 * execution (no active Interactivity scope) degrades to `pageScope`
		 * rather than throwing.
		 */
		currentScope: Scope;
		/**
		 * Finds a scoped item by id/key, or by an extension-supplied
		 * predicate, returning the read-only envelope pairing its draft with
		 * its cart line.
		 *
		 * Implements the pairing ladder: an explicit `key` pairs exactly
		 * (no further checks); otherwise product/variation identity plus a
		 * namespaced extension-prop comparison against the candidate line's
		 * `extensions[<namespace>]` must resolve to exactly one candidate
		 * (using the resolved draft's own `variation`/extension props, when
		 * one exists); any remaining ambiguity leaves `cartItem`
		 * `undefined` — the server owns line identity, so this never
		 * guesses. `filter` replaces the id-based identity matching
		 * entirely, for extensions with their own notion of line identity.
		 *
		 * @param args        Lookup arguments.
		 * @param args.scope  The scope to read the draft from. Defaults to
		 *                    `currentScope`.
		 * @param args.id     The product/variation id to pair by identity.
		 * @param args.key    A known cart-line key; pairs exactly when
		 *                    given, regardless of `id`/`filter`.
		 * @param args.filter An extension-supplied predicate narrowing
		 *                    candidate lines directly, in place of
		 *                    id-based identity matching.
		 * @return The envelope: `{ cartItem?, draft? }`.
		 */
		findItem: ( args?: {
			scope?: Scope;
			id?: DraftItem[ 'id' ];
			key?: CartItem[ 'key' ];
			filter?: ( item: CartItem | OptimisticCartItem ) => boolean;
		} ) => Envelope;
		/**
		 * The envelope for the in-context product: its `currentScope`
		 * draft paired with its cart line, resolved via the products
		 * store's `productInContext`. `undefined` product in context
		 * (nothing rendered) yields an empty envelope. See `findItem` for
		 * the pairing ladder.
		 */
		itemInContext: Envelope;
		/**
		 * The in-context product's in-cart quantity.
		 *
		 * A grouped product aggregates its children's own in-cart
		 * quantities (each resolved independently, by id); a variable
		 * product resolves through its currently selected variation (the
		 * scope's draft selection, same resolution as `itemInContext`); a
		 * simple product is its own paired line's quantity. `0` when
		 * nothing pairs, or no product is in context.
		 */
		inCartQuantity: number;
	};
	actions: {
		/**
		 * Creates or updates a draft cart item in a scope's bucket.
		 *
		 * Resolves the target scope from `options.scope`, defaulting to
		 * `currentScope` when omitted, then merges `partial` into the
		 * scope's draft whose `id` matches — `id` resolves from
		 * `options.id` when given, else from `partial.id` — appending a new
		 * draft otherwise. The scope's bucket is created on first write.
		 *
		 * Rejects (leaving state unchanged) when: the resolved scope is not
		 * a valid, non-empty scope string; no numeric target `id` can be
		 * resolved; `partial.id` disagrees with an already-resolved target
		 * `id`, i.e. an attempt to change an existing draft's identity in
		 * place (remove the draft and add a new one instead); or a brand
		 * new draft is being created without a numeric `quantity`. Every
		 * rejection is a dev-build console warning and a silent no-op in
		 * production.
		 *
		 * @param partial       The draft fields to create or merge.
		 * @param options       Targeting options.
		 * @param options.scope The scope to write into. Defaults to
		 *                      `currentScope` when omitted.
		 * @param options.id    An explicit id identifying which existing
		 *                      draft to update, when it differs from
		 *                      `partial.id` (e.g. `partial` omits `id`).
		 */
		upsertDraftItem: (
			partial: Partial< DraftItem >,
			options?: { scope?: Scope; id?: DraftItem[ 'id' ] }
		) => void;
		/**
		 * Removes a scope's draft for the given product, pruning the
		 * scope's bucket once it becomes empty.
		 *
		 * Resolves the target scope from `options.scope`, defaulting to
		 * `currentScope` when omitted. Rejects (leaving state unchanged)
		 * when the resolved scope is not a valid, non-empty scope string,
		 * or no numeric `id` is given; a request naming a product/scope
		 * with no matching draft is a silent no-op. Every rejection is a
		 * dev-build console warning and a silent no-op in production.
		 *
		 * @param options       Targeting options.
		 * @param options.id    The product id whose draft to remove.
		 * @param options.scope The scope to remove it from. Defaults to
		 *                      `currentScope` when omitted.
		 */
		removeDraftItem: ( options?: {
			id?: DraftItem[ 'id' ];
			scope?: Scope;
		} ) => void;
		/**
		 * Seeds `draftItems[currentScope]` from the server-rendered
		 * `draftSeed`, when no draft for that product already exists there.
		 *
		 * Reads `getServerContext< { draftSeed?: DraftItem } >(
		 * 'woocommerce/cart' )?.draftSeed` — the **server-rendered** context,
		 * immune to the reactive proxy's client-side edits, unlike reading
		 * `state` — then resolves `currentScope` and copies the seed into
		 * that scope's bucket only when the scope holds no draft for the
		 * seed's product `id`. A no-op when no seed is present (a surface
		 * that emits none, or a directive execution context that resolves
		 * none) or when a draft for that product id already exists in the
		 * resolved scope — so a router-region re-render's seed read can
		 * never clobber a shopper's in-progress edits.
		 *
		 * Intended to be called from a `data-wp-init` on the purchase
		 * surface whose subtree context resolves both `currentScope` and the
		 * server-rendered `draftSeed`.
		 */
		seedDraftIfAbsent: () => void;
		/**
		 * Posts the in-context product's current-scope draft(s) to the cart,
		 * or an explicit payload verbatim.
		 *
		 * With no argument, resolves the in-context product (via
		 * `woocommerce/products`) and posts `currentScope`'s draft(s) for
		 * it: a simple/variable product's own single draft
		 * (`itemInContext.draft`), or, for a grouped product, every child's
		 * draft (children resolved one-directionally through the products
		 * store) whose `quantity` is greater than `0`. Multiple children's
		 * drafts are posted as one auto-batched request set through the
		 * mutation queue rather than one request per child. Never posts
		 * another scope's or another product's draft, and sends nothing
		 * when the resolution yields no draft.
		 *
		 * With an explicit `payload`, posts it verbatim — extension props
		 * at its root included — bypassing scope/product resolution
		 * entirely; this is the path an extension composing its own
		 * `cart/add-item` payload (e.g. a bundle) uses.
		 *
		 * Every posted item optimistically bumps a matching existing cart
		 * line's quantity in place (unless `sold_individually`) or is
		 * pushed as a new line, commits or rolls back through the mutation
		 * queue exactly like `addCartItem`, and fires
		 * `triggerAddedToCartEvent( { preserveCartData: true } )` once per
		 * call on success. A cycle whose requests all fail rolls the cart
		 * back to its pre-cycle snapshot and surfaces a
		 * `woocommerce/store-notices` notice.
		 *
		 * @param payload An explicit draft-shaped payload to post verbatim,
		 *                in place of resolving the in-context product's
		 *                draft(s).
		 */
		addItem: ( payload?: DraftItem ) => Promise< void >;
		/**
		 * Sets a cart line's quantity to an absolute value via
		 * `update-item`.
		 *
		 * The public name for the existing keyed path of `addCartItem`:
		 * resolves the current line for `key` and reproduces its
		 * optimistic-apply/commit-or-rollback and legacy-event behavior
		 * exactly. A no-op when no line matches `key`.
		 *
		 * @param args          The keyed update.
		 * @param args.key      The cart line's key.
		 * @param args.quantity The absolute quantity to set.
		 */
		updateItem: ( args: {
			key: string;
			quantity: number;
		} ) => Promise< void >;
		/**
		 * Removes a cart line by key.
		 *
		 * @param key The cart line's key.
		 */
		removeItem: ( key: string ) => Promise< void >;
		/**
		 * Re-fetches the server cart, bypassing the browser cache.
		 */
		refresh: () => Promise< void >;
		addCartItem: (
			args: ClientCartItem,
			options?: CartUpdateOptions
		) => Promise< void >;
		waitForIdle: () => Promise< void >;
		showNoticeError: ( error: Error | ApiErrorResponse ) => Promise< void >;
		updateNotices: (
			notices: Notice[],
			removeOthers?: boolean
		) => Promise< void >;
	};
};

// Stores are locked to prevent 3PD usage until the API is stable.
const universalLock =
	'I acknowledge that using a private store means my plugin will inevitably break on the next store release.';

/**
 * The `woocommerce/products` store's state, consulted one-directionally
 * (cart never becomes a dependency of products) to resolve the product in
 * context for `itemInContext`/`inCartQuantity` and to back the pairing
 * ladder's attribute matching (`matchesSelectedAttributes`).
 */
const { state: productsState } = store< ProductsStore >(
	'woocommerce/products',
	{},
	{ lock: universalLock }
);

// Todo: export this store once the store is public.
const { state } = store< Store >(
	'woocommerce/cart',
	{},
	{ lock: universalLock }
);

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
 * Returns `true` when `scope` is a valid, non-empty scope identifier.
 *
 * Scopes are opaque strings (see {@link Scope}); the only requirement is a
 * non-empty string. `upsertDraftItem`/`removeDraftItem` reject anything
 * else as a malformed-scope invariant violation.
 *
 * @param scope The candidate scope value.
 * @return `true` when `scope` is a non-empty string.
 */
function isValidScope( scope: unknown ): scope is Scope {
	return typeof scope === 'string' && scope.length > 0;
}

/**
 * The shape of the shared `woocommerce` context namespace relevant to scope
 * and item-pairing resolution.
 *
 * Scope-overriding containers (a Product Collection loop item, a Single
 * Product block) emit `scope` via `data-wp-context='woocommerce::{ "scope":
 * "…" }'` on their wrapper element; consumers nested inside inherit it. A
 * surface that already renders one specific, known cart line (e.g. a future
 * line-item wrapper) may additionally emit `key`, letting `itemInContext`
 * pair exactly via the pairing ladder's first rung instead of falling back
 * to product/variation identity matching.
 */
type SharedContext = { scope?: Scope; key?: CartItem[ 'key' ] };

/**
 * Reads the shared `woocommerce` context namespace, degrading to `undefined`
 * instead of throwing when read outside of a directive's execution.
 *
 * `getContext()` throws when called with no active Interactivity scope on
 * the call stack (e.g. from code that runs outside any directive-driven
 * element). `currentScope` must never propagate that failure — an
 * out-of-directive read simply means "no context override available", so it
 * falls back to `state.pageScope` exactly as an in-directive read with no
 * `scope` key would.
 *
 * @return The shared context, or `undefined` when none is available.
 */
function readSharedContext(): SharedContext | undefined {
	try {
		return getContext< SharedContext >( 'woocommerce' );
	} catch {
		return undefined;
	}
}

/**
 * Reports a draft-write invariant violation.
 *
 * Per the write-policy design, invariant violations (a malformed scope, an
 * upsert that would change an existing draft's `id`, a new draft missing a
 * required field) never throw and never partially apply — the calling
 * action returns before touching `state.draftItems`. In a development build
 * this surfaces as a `console.warn` for the implementer; production builds
 * stay silent (`process.env.NODE_ENV` is inlined by the bundler, so this
 * check compiles away entirely there).
 *
 * @param message A human-readable description of the violated invariant.
 */
function warnDraftInvariant( message: string ): void {
	if ( process.env.NODE_ENV !== 'production' ) {
		// eslint-disable-next-line no-console
		console.warn( `[woocommerce/cart] ${ message }` );
	}
}

/**
 * Returns `true` when a variation cart line's recorded attribute values
 * match a set of selected attributes.
 *
 * A module-private copy of the pairing algorithm in
 * `base/utils/variations/does-cart-item-match-attributes.ts`, duplicated
 * (rather than imported) so the cart store's own pairing ladder
 * (`lineMatchesProduct`, `findItem`, `itemInContext`) consults
 * `woocommerce/products` directly instead of through that util's import
 * chain. The standalone util is left in place, unchanged — it still backs
 * the shopper-lists blocks.
 *
 * Resolves each recorded attribute's term slug from the parent product's
 * attribute list (`woocommerce/products` state, consulted one-directionally)
 * to reconcile the Store API's label/slug mismatches, then requires every
 * recorded attribute to match a selected one, case-insensitively and after
 * normalizing WordPress's `attribute_`/`attribute_pa_` prefixes.
 *
 * @param cartItem           The cart line to test.
 * @param selectedAttributes The attributes to match against.
 * @return `true` when every recorded attribute matches a selected one.
 */
function matchesSelectedAttributes(
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

	const parentProductId =
		productsState.productVariations[ cartItem.id ]?.parent;
	const productAttributes =
		productsState.products[ parentProductId ]?.attributes ?? [];

	return cartItem.variation.every( ( { attribute, value: termName } ) =>
		selectedAttributes.some( ( selectedAttr ) => {
			const terms = productAttributes.find( ( attr ) =>
				attributeNamesMatch( attribute, attr.name )
			)?.terms;
			const termSlug =
				terms?.find( ( term ) => term.name === termName )?.slug ||
				termName;
			return (
				attributeNamesMatch( selectedAttr.attribute, attribute ) &&
				selectedAttr.value.toLowerCase() === termSlug?.toLowerCase()
			);
		} )
	);
}

/**
 * Returns `true` when the given cart line matches the product identified by
 * `id` and `variation`.
 *
 * Simple items match by `id` equality. Variation items additionally require
 * `variation.length` equality and `matchesSelectedAttributes`. The private
 * matcher backing `findItem`'s identity rung and {@link findCartLine}.
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
		return matchesSelectedAttributes( item, variation );
	}
	return id === item.id;
}

/**
 * Finds a cart line by key, or by product/variation identity when no key is
 * given.
 *
 * The private matcher behind `addCartItem`/`updateItem`'s keyed-or-keyless
 * lookup and `addItem`'s per-draft lookup, internalizing the former public
 * `findItemInCart` getter's exact behavior: an explicit `key` pairs exactly,
 * otherwise {@link lineMatchesProduct} resolves identity.
 *
 * @param args           Lookup arguments.
 * @param args.id        The product/variation id to match by identity.
 * @param args.key       A known cart-line key; pairs exactly when given,
 *                       regardless of `id`/`variation`.
 * @param args.variation The variation attributes to match against, if any.
 * @return The matching cart line, or `undefined` when none matches.
 */
function findCartLine( {
	id,
	key,
	variation,
}: {
	id: ClientCartItem[ 'id' ];
	key?: ClientCartItem[ 'key' ];
	variation?: ClientCartItem[ 'variation' ];
} ): CartItem | OptimisticCartItem | undefined {
	if ( key ) {
		return state.cart.items.find( ( cartItem ) => key === cartItem.key );
	}
	return state.cart.items.find( ( cartItem ) =>
		lineMatchesProduct( cartItem, id, variation )
	);
}

/**
 * A single namespaced extension prop parsed off a draft's payload root, e.g.
 * `{ 'my-plugin/gift-note': 'Hi' }` yields `{ namespace: 'my-plugin', key:
 * 'gift-note', value: 'Hi' }`.
 */
type DraftExtensionProp = {
	/** The plugin namespace the prop rides under (before the `/`). */
	namespace: string;
	/** The prop's own key, within its namespace (after the `/`). */
	key: string;
	/** The prop's value, as recorded on the draft. */
	value: unknown;
};

/**
 * Extracts a draft's namespaced extension props.
 *
 * Per {@link DraftItem}, extension props are payload-root keys carrying a
 * `/` (e.g. `'my-plugin/gift-note'`); `id`, `quantity`, and `variation`
 * never contain one, so a slash is what distinguishes an extension prop
 * from a core draft field.
 *
 * @param draft The draft to inspect, or `undefined` when none was resolved.
 * @return The draft's extension props, or an empty array when `draft` is
 *         `undefined` or carries none.
 */
function draftExtensionProps(
	draft: DraftItem | undefined
): DraftExtensionProp[] {
	if ( ! draft ) {
		return [];
	}
	return Object.keys( draft )
		.filter( ( propKey ) => propKey.includes( '/' ) )
		.map( ( propKey ) => {
			const slashIndex = propKey.indexOf( '/' );
			return {
				namespace: propKey.slice( 0, slashIndex ),
				key: propKey.slice( slashIndex + 1 ),
				value: draft[ propKey ],
			};
		} );
}

/**
 * Returns `true` when every namespaced extension prop on `draft` deep-equals
 * the corresponding value under the cart line's `extensions[<namespace>]`.
 *
 * A draft carrying no extension props always matches — the extension-prop
 * comparison is an additional constraint on the pairing ladder only when the
 * draft actually has one.
 *
 * @param draft The draft whose extension props to check, or `undefined`.
 * @param item  The cart line to compare against.
 * @return `true` when every extension prop matches, or `draft` has none.
 */
function draftExtensionsMatchLine(
	draft: DraftItem | undefined,
	item: OptimisticCartItem | CartItem
): boolean {
	const extensions = ( item as CartItem ).extensions;
	return draftExtensionProps( draft ).every(
		( { namespace, key, value } ) => {
			const namespaceData = extensions?.[ namespace ] as
				| Record< string, unknown >
				| undefined;
			return (
				!! namespaceData && fastDeepEqual( namespaceData[ key ], value )
			);
		}
	);
}

/**
 * Finds a scope's draft for the given product/variation id.
 *
 * @param scope The scope to look in.
 * @param id    The draft's product/variation id, or `undefined` (nothing to
 *              find).
 * @return The matching draft, or `undefined` when none is found.
 */
function findDraftInScope(
	scope: Scope,
	id: DraftItem[ 'id' ] | undefined
): DraftItem | undefined {
	if ( id === undefined ) {
		return undefined;
	}
	return ( state.draftItems[ scope ] as DraftItem[] | undefined )?.find(
		( draft ) => draft.id === id
	);
}

/**
 * Builds a `Set` of pre-existing cart-line keys to suppress from the
 * "quantity changed" auto-update notice after a successful keyless add.
 *
 * For each product entry in `products`, computes:
 *   - `serverTotal` = sum of the committed server cart's lines matching that
 *     product (via `lineMatchesProduct`).
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
 * Keyed `update-item` changes and `removeItem` never populate
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
	// server-initiated change. Keyed update-item lines and removeItem lines
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

/**
 * Posts a list of draft-shaped items to the cart's `add-item` endpoint,
 * auto-batched through the mutation queue when more than one is given.
 *
 * Each item is posted **verbatim** as the request body — no
 * reconstruction — matching the write policy that a draft is exactly a
 * Store API `cart/add-item` payload (extension props at its root
 * included). Optimistically, an item bumps a matching existing cart line's
 * quantity in place (unless the line is `sold_individually`) or is pushed
 * as a new line when no line matches, mirroring `addCartItem`'s
 * keyless-add behavior. `sendCartRequest` calls made synchronously in the
 * same tick — as this function's `.map()` below does — are auto-batched
 * into a single `wc/store/v1/batch` request by the mutation queue.
 *
 * On success, `triggerAddedToCartEvent` fires once for the whole call
 * (not once per item), and info/error notices are derived from the cart
 * diff. No request rejection ever throws out of this function — every
 * request is awaited via `Promise.allSettled` — so a failure surfaces as
 * an error notice; when every request in the cycle failed, the mutation
 * queue has already rolled the cart back to its pre-cycle snapshot.
 *
 * A no-op (nothing sent) when `items` is empty.
 *
 * Accepts the store's `actions` as an explicit parameter (rather than
 * closing over the module-level binding) purely so this declaration can
 * sit above the `store()` call that produces it.
 *
 * @param items        The items to post, each an exact `cart/add-item` payload.
 * @param storeActions The store's own actions, for dispatching notices.
 */
function* postDraftItems(
	items: DraftItem[],
	storeActions: Store[ 'actions' ]
): AsyncAction< void > {
	if ( items.length === 0 ) {
		return;
	}

	const quantityChanges: QuantityChanges = {
		productsPendingAdd: items.map( ( item ) => item.id ),
	};

	try {
		// Per-item capture for the keyless-add exactness test (see
		// `computeKeylessAddSuppressKeys`). Drafts are unique per product id
		// by construction (at most one draft per id per scope; grouped
		// children are distinct ids), so no per-token accumulation is needed.
		const productCaptures = items.map( ( item ) => {
			const preExistingKeys: string[] = [];
			let preAddTotal = 0;
			for ( const cartLine of state.cart.items ) {
				if ( lineMatchesProduct( cartLine, item.id, item.variation ) ) {
					preAddTotal += cartLine.quantity;
					if ( cartLine.key ) {
						preExistingKeys.push( cartLine.key );
					}
				}
			}
			return {
				id: item.id,
				variation: item.variation,
				preAddTotal,
				deltaTotal: item.quantity,
				preExistingKeys,
			};
		} );

		const requests = items.map( ( item, index ) => {
			const existingItem = findCartLine( {
				id: item.id,
				variation: item.variation,
			} );
			const isLastItem = index === items.length - 1;

			return sendCartRequest( state, {
				path: '/wc/store/v1/cart/add-item',
				method: 'POST',
				body: item,
				applyOptimistic: () => {
					if ( existingItem ) {
						const isSoldIndividually =
							isCartItem( existingItem ) &&
							existingItem.sold_individually;
						if ( ! isSoldIndividually ) {
							existingItem.quantity += item.quantity;
						}
					} else {
						state.cart.items.push( {
							...item,
						} as unknown as OptimisticCartItem );
					}
				},
				// Side effects run synchronously during reconciliation,
				// before isProcessing clears. Only the last item's callback
				// fires, to avoid firing the legacy event once per child.
				...( isLastItem && {
					onSettled: ( { success } ) => {
						if ( success ) {
							triggerAddedToCartEvent( {
								preserveCartData: true,
							} );
							emitSyncEvent( { quantityChanges } );
						}
					},
				} ),
			} );
		} );

		// Capture cart state after optimistic updates for notice comparison.
		const cartAfterOptimistic = JSON.parse( JSON.stringify( state.cart ) );

		const results = ( yield Promise.allSettled(
			requests
		) ) as PromiseSettledResult< MutationResult< Cart > >[];

		// Find the last successful result for the notice diff.
		const lastSuccess = [ ...results ]
			.reverse()
			.find(
				( r ): r is PromiseFulfilledResult< MutationResult< Cart > > =>
					r.status === 'fulfilled' && r.value.success
			);

		if ( lastSuccess ) {
			const cart = lastSuccess.value.data as Cart;
			const suppressKeys = computeKeylessAddSuppressKeys(
				productCaptures,
				cart
			);
			const infoNotices = getInfoNoticesFromCartUpdates(
				cartAfterOptimistic,
				cart,
				suppressKeys
			);
			const errorNotices = cart.errors.map( generateErrorNotice );
			yield storeActions.updateNotices(
				[ ...infoNotices, ...errorNotices ],
				true
			);
		}

		// Show error notices for failed items.
		const errorNotices = results
			.filter(
				( r ): r is PromiseRejectedResult => r.status === 'rejected'
			)
			.map( ( r ) =>
				generateErrorNotice( r.reason as ApiErrorResponse )
			);
		if ( errorNotices.length > 0 ) {
			yield storeActions.updateNotices( errorNotices );
		}
	} catch ( error ) {
		storeActions.showNoticeError( error as Error );
	}
}

/**
 * Removes a cart line by key.
 *
 * Backs the `removeItem` action, extracted to a standalone generator so its
 * optimistic-apply, commit-or-rollback, and notice behavior lives in one
 * place.
 *
 * Accepts the store's `actions` as an explicit parameter (rather than
 * closing over the module-level binding) purely so this declaration can
 * sit above the `store()` call that produces it.
 *
 * @param key          The cart line's key.
 * @param storeActions The store's own actions, for dispatching notices.
 */
function* removeCartItemGenerator(
	key: string,
	storeActions: Store[ 'actions' ]
): AsyncAction< void > {
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
			// before isProcessing clears. This prevents `refresh` from
			// running during these events.
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
			const errorNotices = cart.errors.map( generateErrorNotice );
			yield storeActions.updateNotices(
				[ ...infoNotices, ...errorNotices ],
				true
			);
		}
	} catch ( error ) {
		storeActions.showNoticeError( error as Error );
	}
}

/**
 * Adds or updates a cart line, choosing `add-item`/`update-item` from
 * whether an explicit `key` is given.
 *
 * The shared implementation behind both `addCartItem` (retained
 * permanently) and `updateItem`'s keyed path (the public name for this
 * function's `key`-present branch), extracted so both actions reproduce
 * the exact same endpoint selection, optimistic-apply,
 * commit-or-rollback, and notice behavior via `yield*` delegation.
 *
 * Accepts the store's `actions` as an explicit parameter (rather than
 * closing over the module-level binding) purely so this declaration can
 * sit above the `store()` call that produces it.
 *
 * @param args                           The item to add or update.
 * @param storeActions                   The store's own actions, for dispatching notices.
 * @param options                        Notice-display options.
 * @param options.showCartUpdatesNotices Whether to surface auto-update/removal notices.
 */
function* addCartItemGenerator(
	args: ClientCartItem,
	storeActions: Store[ 'actions' ],
	options: CartUpdateOptions = {}
): AsyncAction< void > {
	const { id, key, quantity, quantityToAdd, variation } = args;
	const { showCartUpdatesNotices = true } = options;
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
	const existingItem = findCartLine( {
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
		variation?: CartVariationItem[] | SelectedAttributes[] | undefined;
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
			// before isProcessing clears. This prevents `refresh` from
			// running during these events.
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

		// Success - handle side effects that don't trigger `refresh`
		const cart = result.data as Cart;

		// Show notices if enabled
		if ( showCartUpdatesNotices && cart && cartAfterOptimistic ) {
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
			const errorNotices = cart.errors.map( generateErrorNotice );
			yield storeActions.updateNotices(
				[ ...infoNotices, ...errorNotices ],
				true
			);
		}

		// Announce to screen readers
		const { messages } = getConfig( 'woocommerce' ) as WooCommerceConfig;
		if ( messages?.addedToCartText ) {
			const { speak } = ( yield a11yModulePromise ) as Awaited<
				typeof a11yModulePromise
			>;
			speak( messages.addedToCartText, 'polite' );
		}
	} catch ( error ) {
		// Show error notice
		storeActions.showNoticeError( error as Error );
	}
}

/**
 * Re-fetches the server cart, bypassing the browser cache, retrying with
 * exponential backoff on failure.
 *
 * Backs the `refresh` action, extracted to a standalone generator so its
 * refresh/retry behavior lives in one place.
 *
 * Accepts the store's `actions` as an explicit parameter (rather than
 * closing over the module-level binding) purely so this declaration can
 * sit above the `store()` call that produces it; it is also the retry
 * callback `setTimeout` invokes directly on failure.
 *
 * @param storeActions The store's own actions; `storeActions.refresh` is
 *                     the retry callback on failure.
 */
function* refreshCartItemsGenerator(
	storeActions: Store[ 'actions' ]
): AsyncAction< void > {
	// Skip if queue is processing - it will apply server state when done
	if ( cartQueue?.getStatus().isProcessing ) {
		return;
	}

	// Skips if there's a pending request.
	if ( pendingRefresh ) return;

	pendingRefresh = true;

	try {
		const res = ( yield fetch( `${ state.restUrl }wc/store/v1/cart`, {
			method: 'GET',
			cache: 'no-store',
			headers: { 'Content-Type': 'application/json' },
		} ) ) as TypeYield< typeof fetch >;

		// Extract fresh nonce from response headers.
		state.nonce = res.headers.get( 'Nonce' ) || state.nonce;

		if ( resolveNonceReady ) {
			resolveNonceReady();
			resolveNonceReady = null;
		}

		const json = ( yield res.json() ) as Cart;

		// Checks if the response contains an error.
		if ( isApiErrorResponse( res, json ) ) throw generateError( json );

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
		setTimeout( storeActions.refresh, refreshTimeout );

		// Increases the timeout exponentially.
		refreshTimeout *= 2;
	} finally {
		pendingRefresh = false;
	}
}

const { actions } = store< Store >(
	'woocommerce/cart',
	{
		state: {
			draftItems: {},
			// `pageScope` is intentionally absent here: it is server-seeded,
			// and a client-side initial value would overwrite the seeded
			// scope when this definition deep-merges over the server state
			// during store registration.
			get currentScope(): Scope {
				return readSharedContext()?.scope ?? state.pageScope ?? '';
			},
			findItem( {
				scope = state.currentScope,
				id,
				key,
				filter,
			}: {
				scope?: Scope;
				id?: DraftItem[ 'id' ];
				key?: CartItem[ 'key' ];
				filter?: ( item: CartItem | OptimisticCartItem ) => boolean;
			} = {} ): Envelope {
				// Rung 1: a context-known line key pairs exactly, no further
				// identity/extension checks — the caller already knows
				// precisely which line this is.
				if ( key !== undefined ) {
					const cartItem = state.cart.items.find(
						( item ) => item.key === key
					);
					return {
						cartItem,
						draft: findDraftInScope( scope, id ?? cartItem?.id ),
					};
				}

				// `filter` replaces id-based identity matching entirely,
				// for extensions with their own notion of line identity.
				if ( filter ) {
					const matches = state.cart.items.filter( filter );
					return {
						cartItem:
							matches.length === 1 ? matches[ 0 ] : undefined,
						draft: findDraftInScope( scope, id ),
					};
				}

				if ( id === undefined ) {
					return {
						cartItem: undefined,
						draft: undefined,
					};
				}

				// Rung 2: product/variation identity (using the resolved
				// draft's own `variation`, when one exists) plus a
				// namespaced extension-prop comparison against each
				// candidate line's `extensions[<namespace>]`. Ambiguity —
				// zero or more than one line surviving both checks — never
				// guesses: `cartItem` stays `undefined`.
				const draft = findDraftInScope( scope, id );
				const identityMatches = state.cart.items.filter( ( item ) =>
					lineMatchesProduct( item, id, draft?.variation )
				);
				const pairedMatches = identityMatches.filter( ( item ) =>
					draftExtensionsMatchLine( draft, item )
				);

				return {
					cartItem:
						pairedMatches.length === 1
							? pairedMatches[ 0 ]
							: undefined,
					draft,
				};
			},
			get itemInContext(): Envelope {
				const product = productsState.productInContext;
				if ( ! product ) {
					return {
						cartItem: undefined,
						draft: undefined,
					};
				}

				const contextKey = readSharedContext()?.key;
				return state.findItem(
					contextKey !== undefined
						? { id: product.id, key: contextKey }
						: { id: product.id }
				);
			},
			get inCartQuantity(): number {
				const product = productsState.productInContext;
				if ( ! product ) {
					return 0;
				}

				if ( product.type === 'grouped' ) {
					return product.grouped_products.reduce(
						( total, childId ) =>
							total +
							( state.findItem( { id: childId } ).cartItem
								?.quantity ?? 0 ),
						0
					);
				}

				return state.itemInContext.cartItem?.quantity ?? 0;
			},
		},
		actions: {
			upsertDraftItem(
				partial: Partial< DraftItem >,
				{
					scope = state.currentScope,
					id,
				}: { scope?: Scope; id?: DraftItem[ 'id' ] } = {}
			) {
				if ( ! isValidScope( scope ) ) {
					warnDraftInvariant(
						`upsertDraftItem: a valid "scope" is required, received ${ JSON.stringify(
							scope
						) }.`
					);
					return;
				}

				// The id used to look up an existing draft: an explicit
				// `id` names "the draft I mean to update" independently of
				// `partial.id`. When both are given they are compared below
				// to catch an in-place rename attempt; when only one is
				// given it doubles as both the lookup key and the value.
				const targetId = id ?? partial.id;
				if ( typeof targetId !== 'number' ) {
					warnDraftInvariant(
						'upsertDraftItem: a numeric "id" is required, via `partial.id` or `{ id }`.'
					);
					return;
				}

				const bucket = state.draftItems[ scope ] as
					| DraftItem[]
					| undefined;
				const existing = bucket?.find(
					( draft ) => draft.id === targetId
				);

				if ( existing ) {
					if (
						partial.id !== undefined &&
						partial.id !== existing.id
					) {
						warnDraftInvariant(
							`upsertDraftItem: cannot change draft id ${ existing.id } to ${ partial.id }; remove the draft and add a new one instead.`
						);
						return;
					}
					Object.assign( existing, partial, { id: existing.id } );
					return;
				}

				if ( typeof partial.quantity !== 'number' ) {
					warnDraftInvariant(
						'upsertDraftItem: a new draft requires a numeric "quantity".'
					);
					return;
				}

				const draft = { ...partial, id: targetId } as DraftItem;
				if ( bucket ) {
					bucket.push( draft );
				} else {
					state.draftItems[ scope ] = [ draft ];
				}
			},

			removeDraftItem( {
				id,
				scope = state.currentScope,
			}: { id?: DraftItem[ 'id' ]; scope?: Scope } = {} ) {
				if ( ! isValidScope( scope ) ) {
					warnDraftInvariant(
						`removeDraftItem: a valid "scope" is required, received ${ JSON.stringify(
							scope
						) }.`
					);
					return;
				}
				if ( typeof id !== 'number' ) {
					warnDraftInvariant(
						'removeDraftItem: a numeric "id" is required.'
					);
					return;
				}

				const bucket = state.draftItems[ scope ] as
					| DraftItem[]
					| undefined;
				const index =
					bucket?.findIndex( ( draft ) => draft.id === id ) ?? -1;
				if ( ! bucket || index === -1 ) {
					return;
				}

				bucket.splice( index, 1 );
				if ( bucket.length === 0 ) {
					delete state.draftItems[ scope ];
				}
			},

			seedDraftIfAbsent() {
				const seed = getServerContext< { draftSeed?: DraftItem } >(
					'woocommerce/cart'
				)?.draftSeed;
				if ( ! seed ) {
					return;
				}

				const scope = state.currentScope;
				if ( findDraftInScope( scope, seed.id ) ) {
					return;
				}

				actions.upsertDraftItem( seed, { scope } );
			},

			*addItem( payload?: DraftItem ): AsyncAction< void > {
				if ( payload ) {
					yield* postDraftItems( [ payload ], actions );
					return;
				}

				const product = productsState.productInContext;
				if ( ! product ) {
					return;
				}

				if ( product.type === 'grouped' ) {
					const drafts = product.grouped_products
						.map(
							( childId ) =>
								state.findItem( { id: childId } ).draft
						)
						.filter(
							( draft ): draft is DraftItem =>
								!! draft && draft.quantity > 0
						);
					yield* postDraftItems( drafts, actions );
					return;
				}

				const { draft } = state.itemInContext;
				if ( ! draft ) {
					return;
				}
				yield* postDraftItems( [ draft ], actions );
			},

			*updateItem( {
				key,
				quantity,
			}: {
				key: string;
				quantity: number;
			} ): AsyncAction< void > {
				const cartItem = state.cart.items.find(
					( item ) => item.key === key
				);
				if ( ! cartItem ) {
					return;
				}
				yield* addCartItemGenerator(
					{
						id: cartItem.id,
						key,
						quantity,
						type: cartItem.type,
					},
					actions
				);
			},

			*removeItem( key: string ): AsyncAction< void > {
				yield* removeCartItemGenerator( key, actions );
			},

			*refresh(): AsyncAction< void > {
				yield* refreshCartItemsGenerator( actions );
			},

			*addCartItem(
				args: ClientCartItem,
				options?: CartUpdateOptions
			): AsyncAction< void > {
				yield* addCartItemGenerator( args, actions, options );
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
