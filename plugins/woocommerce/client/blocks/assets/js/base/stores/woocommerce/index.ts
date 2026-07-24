/**
 * The unified `woocommerce` store's root module.
 *
 * Registers the shared namespace's read-only server data defaults
 * (`state.products`), the client-only editable home (`state.draftItems`),
 * and the store's one entry point: the in-context envelope
 * (`state.itemInContext`) and its explicit-addressing twin
 * (`state.findItem`). The read-only cart mirror (`state.cart`) and the cart
 * actions register separately, under the same `woocommerce` namespace, from
 * the cart machinery module (`./cart`) — partial registrations deep-merge
 * into one store, so this module never needs to know about the mirror or
 * the actions to expose a complete envelope.
 *
 * Bundles the folder-internal helper modules (the pairing ladder, the
 * product-resolution primitive, and the draft internals) rather than
 * value-importing the cart machinery module or any block: this module is
 * the slim one every display-only page loads, and it never drags the
 * cart's fetch/queue machinery along for the read.
 */

/**
 * External dependencies
 */
import { getContext, store } from '@wordpress/interactivity';
import type { CartItem, ProductResponseItem } from '@woocommerce/types';

/**
 * Internal dependencies
 */
import type {
	DraftItem,
	DraftKey,
	OptimisticCartItem,
	SelectedAttributes,
} from './cart';
import { draftExtensionsMatchLine, lineMatchesProduct } from './cart-pairing';
import {
	effectiveVariationAttributes,
	findFamilyDraft,
	resolveBaseProduct,
	resolveDraftKey,
	resolveDraftView,
	resolveFamilyVariation,
} from './draft-internals';
import { resolveProduct } from './product-resolution';

// The transitional aliases below keep resolving for consumer type imports
// until each consumer's own task migrates it (`products.ts` dissolves in a
// later task, once every consumer has re-pointed).
export type { ProductsStore, ProductsStoreState } from './products';
export type { DraftItem, SelectedAttributes } from './cart';

/**
 * The shape of the shared `woocommerce` context namespace this module
 * resolves addressing from: a container declares whichever of these keys
 * it needs to isolate or override; every key is independently optional and
 * inherited by nested surfaces that declare none of their own.
 */
type WooCommerceContext = {
	/** The base/family product id a container declares for its subtree. */
	productId?: number;
	/**
	 * The variation id a container declares for its subtree, or `null` to
	 * explicitly declare "no variation" (distinct from declaring neither
	 * key at all, which falls back to `state.products.variationId`).
	 */
	variationId?: number | null;
	/** The draft collection key a container declares for its subtree. */
	draftKey?: DraftKey;
};

/**
 * The read-only envelope returned by `itemInContext`/`findItem`.
 *
 * Every member is a **lazy accessor**: reading `product` runs only product
 * resolution; the pairing ladder runs only when `cartItem` is read;
 * `draftItem` returns the cached per-`(key, id)` view. The envelope object
 * is rebuilt per read (values, not identity); `draftItem`'s own identity is
 * stabilized by the view cache in `draft-internals.ts`.
 */
export type Envelope = {
	/** The base/family product id, resolving even when no product data is loaded. */
	productId: number | null;
	/** The resolved variation id, when one resolves. */
	variationId: number | null;
	/** The draft collection this envelope resolves its `draftItem`/pairing against. */
	draftKey: DraftKey;
	/**
	 * The resolved product — contract diverges by entry point:
	 * `itemInContext.product` falls back to the base product on a
	 * no-match variable selection (display never blanks, SSR parity);
	 * `findItem({ id, selectedAttributes }).product` is `null` on no
	 * match (the existence probe `validateVariation`/
	 * `setDefaultVariationId` depend on); `findItem({ id }).product` is
	 * variation-by-id direct, else the product unchanged.
	 */
	product: ProductResponseItem | null;
	/** The resolved variation, or `null`. Read-only — no assignable member. */
	variation: ProductResponseItem | null;
	/** The family base product. */
	baseProduct: ProductResponseItem | null;
	/**
	 * The resolved collection's live, family-aware draft view (see
	 * `draft-internals.ts`'s `resolveDraftView`): present whenever an id
	 * resolves, `undefined` only when no id is given and no product is in
	 * context. A read never materializes anything; a write merges onto the
	 * live draft, or composes and materializes a new one from the seed.
	 */
	draftItem?: DraftItem | undefined;
	/**
	 * The paired cart line, when the pairing ladder resolved to exactly one
	 * candidate. `undefined` when no line matches, or more than one
	 * candidate line matches and none can be told apart — the server owns
	 * cart-line identity, so this never guesses.
	 */
	cartItem?: CartItem | OptimisticCartItem | undefined;
};

/**
 * The explicit-addressing arguments `findItem` accepts, resolving the same
 * lazy {@link Envelope} `itemInContext` does for the item the surrounding
 * markup implies.
 */
export type FindItemRef = {
	/** Any product, variation, or grouped-child id. */
	id?: DraftItem[ 'id' ];
	/** With `id`: narrows a variable product to the matching variation. */
	selectedAttributes?: SelectedAttributes[] | null;
	/** An explicit cart-line key; pairs exactly when given. */
	key?: CartItem[ 'key' ];
	/** Caller-owned narrowing, in place of id-based identity matching. */
	filter?: ( item: CartItem | OptimisticCartItem ) => boolean;
};

/**
 * The unified `woocommerce` store's type. This root module registers only
 * `state.products`/`state.draftItems`/`state.itemInContext`/`state.findItem`
 * — the cart mirror (`state.cart`) and the cart actions register separately
 * under the same namespace, from the cart machinery module.
 */
export type WooCommerce = {
	state: {
		/** Server-hydrated product/variation cache and page addressing. */
		products: {
			/** Products keyed by id — parents and grouped children, in Store API format. */
			items: Record< number, ProductResponseItem >;
			/** Variations keyed by id, in Store API format. */
			variations: Record< number, ProductResponseItem >;
			/** The page's own product id — the `productId` context/addressing fallback. */
			productId: number;
			/** The page's own variation id — the `variationId` context/addressing fallback. */
			variationId: number | null;
		};
		/**
		 * The global draft home: every draft collection, keyed by an opaque
		 * {@link DraftKey}. Never server-seeded, reload-reset; see
		 * `draft-internals.ts` for the write path.
		 */
		draftItems: Record< DraftKey, DraftItem[] >;
		/**
		 * The read-only cart mirror — registered by the cart machinery
		 * module (`./cart`), not this one; declared here, optional, only so
		 * this module's own pairing-ladder read can be typed. Absent on a
		 * display-only page that never loads the cart module, which is
		 * exactly why every read below is guarded (`state.cart?.items ??
		 * []`) rather than assumed present.
		 */
		cart?: {
			items: ( CartItem | OptimisticCartItem )[];
		};
		/**
		 * The envelope for the in-context item, resolved from the
		 * `woocommerce` context bag with the `state.products.*` fallback.
		 * `undefined` product members when nothing resolves (no context,
		 * no product in context).
		 */
		itemInContext: Envelope;
		/**
		 * Finds an item by id/key/filter, or by id narrowed to a variation
		 * via `selectedAttributes`, returning the same lazy {@link Envelope}
		 * `itemInContext` does.
		 *
		 * @param ref The addressing to resolve the envelope for.
		 * @return The resolved envelope.
		 */
		findItem: ( ref?: FindItemRef ) => Envelope;
	};
};

/**
 * An alias of {@link WooCommerce}, matching the per-module `Store` naming
 * convention every other store file in this folder uses for its own
 * exported type.
 */
export type Store = WooCommerce;

// Stores are locked to prevent 3PD usage until the API is stable.
const universalLock =
	'I acknowledge that using a private store means my plugin will inevitably break on the next store release.';

// Opens the namespace to bind `state` before any helper function below
// references it — the same two-call shape `cart.ts`/`products.ts` use
// (an early open, then the real registration once every getter it feeds
// is declared). The real registration, passing the full state definition,
// is the last statement in this module.
const { state } = store< WooCommerce >(
	'woocommerce',
	{},
	{ lock: universalLock }
);

/**
 * Resolves the shared `woocommerce` context bag, tolerating a call made
 * with no directive currently executing on the call stack — exactly the
 * same degrade `draft-internals.ts`'s `resolveDraftKey` applies, extended
 * here to the addressing keys `resolveDraftKey` does not itself resolve.
 *
 * @return The context bag, or `null` when none is active (including when
 *         the read is made outside any directive).
 */
function resolveWooCommerceContext(): WooCommerceContext | null {
	try {
		return getContext< WooCommerceContext >( 'woocommerce' );
	} catch {
		return null;
	}
}

/**
 * Resolves the addressed base/family product id: the context's own
 * declared `productId`, when a container declared the key at all
 * (regardless of value), else the page's own `state.products.productId`.
 *
 * @param context The resolved context bag, or `null`.
 * @return The addressed product id (`0`/falsy when unresolved).
 */
function resolveAddressProductId(
	context: WooCommerceContext | null
): number | undefined {
	return context && 'productId' in context
		? context.productId
		: state.products.productId;
}

/**
 * Resolves the addressed variation id: the context's own declared
 * `variationId`, when a container declared the key at all (including an
 * explicit `null`, which is never overridden by the state fallback), else
 * the page's own `state.products.variationId`.
 *
 * @param context The resolved context bag, or `null`.
 * @return The addressed variation id, or `null` when unresolved.
 */
function resolveAddressVariationId(
	context: WooCommerceContext | null
): number | null {
	return context && 'variationId' in context
		? context.variationId ?? null
		: state.products.variationId;
}

/**
 * Resolves the in-context base product from the addressed product id.
 *
 * @param context The resolved context bag, or `null`.
 * @return The base product, or `null` when the addressed id resolves to
 *         nothing, or names no product `state.products.items` carries.
 */
function resolveBaseProductInContext(
	context: WooCommerceContext | null
): ProductResponseItem | null {
	const productId = resolveAddressProductId( context );
	return productId ? state.products.items[ productId ] ?? null : null;
}

/**
 * Resolves the in-context variation: the family draft in the nearest
 * collection — read from raw `state.draftItems` only, never a composed
 * getter (this module's own state, so no cross-namespace cycle risk) —
 * falling back to the `variationId`/`productId` addressing when no family
 * draft exists for the resolved base product.
 *
 * @param context  The resolved context bag, or `null`.
 * @param draftKey The resolved draft collection key.
 * @return The resolved variation, or `null`.
 */
function resolveVariationInContext(
	context: WooCommerceContext | null,
	draftKey: DraftKey
): ProductResponseItem | null {
	const base = resolveBaseProductInContext( context );
	if ( base ) {
		const collection = state.draftItems[ draftKey ];
		const familyDraft = collection
			? findFamilyDraft( base, collection )
			: undefined;
		if ( familyDraft ) {
			return resolveFamilyVariation( base, familyDraft );
		}
	}
	const variationId = resolveAddressVariationId( context );
	return variationId
		? state.products.variations[ variationId ] ?? null
		: null;
}

/**
 * Resolves the draft/pairing id for the in-context item: the resolved
 * item's own id (variation id when one is selected, else the base
 * product's), falling back to the addressed product id when no product
 * data resolves at all (addressing alone still resolves a draft/pairing
 * id).
 *
 * @param context  The resolved context bag, or `null`.
 * @param draftKey The resolved draft collection key.
 * @return The draft/pairing id, or `undefined` when nothing addresses an
 *         item at all.
 */
function resolvePairingIdInContext(
	context: WooCommerceContext | null,
	draftKey: DraftKey
): number | undefined {
	const base = resolveBaseProductInContext( context );
	const variation = resolveVariationInContext( context, draftKey );
	const product = variation ?? base;
	if ( product ) {
		return product.id;
	}
	return resolveAddressProductId( context ) || undefined;
}

/**
 * Resolves the paired cart line for a given id: the pairing ladder,
 * consulting the resolved draft view's *effective* attributes (its
 * specified `variation`, completed from the matching variation's own meta)
 * plus a namespaced extension-prop comparison. Reads `state.cart?.items ??
 * []` so a display-only page (the cart module unloaded, `state.cart`
 * absent) degrades to no candidates rather than throwing.
 *
 * @param id       The product/variation id to pair by identity.
 * @param draftKey The resolved draft collection key.
 * @return The paired cart line, or `undefined` when no single candidate
 *         resolves.
 */
function resolveCartItemForId(
	id: number,
	draftKey: DraftKey
): CartItem | OptimisticCartItem | undefined {
	const cartItems = state.cart?.items ?? [];
	const draftItem = resolveDraftView( draftKey, id );
	const base = resolveBaseProduct( id );
	const effectiveAttributes = effectiveVariationAttributes(
		base,
		id,
		draftItem.variation
	);
	const identityMatches =
		effectiveAttributes === undefined
			? []
			: cartItems.filter( ( item ) =>
					lineMatchesProduct( item, id, effectiveAttributes )
			  );
	const pairedMatches = identityMatches.filter( ( item ) =>
		draftExtensionsMatchLine( draftItem, item )
	);
	return pairedMatches.length === 1 ? pairedMatches[ 0 ] : undefined;
}

/**
 * Builds the in-context envelope, resolved fresh (a new object, new context
 * read) on every `state.itemInContext` access. Every member below is its
 * own lazy accessor sharing this one context/draftKey snapshot.
 *
 * @return The in-context envelope.
 */
function getItemInContext(): Envelope {
	const context = resolveWooCommerceContext();
	const draftKey = resolveDraftKey();

	return {
		get productId() {
			return resolveAddressProductId( context ) || null;
		},
		get variationId() {
			return resolveVariationInContext( context, draftKey )?.id ?? null;
		},
		get draftKey() {
			return draftKey;
		},
		get product() {
			return (
				resolveVariationInContext( context, draftKey ) ??
				resolveBaseProductInContext( context )
			);
		},
		get variation() {
			return resolveVariationInContext( context, draftKey );
		},
		get baseProduct() {
			return resolveBaseProductInContext( context );
		},
		get draftItem() {
			const id = resolvePairingIdInContext( context, draftKey );
			return id === undefined
				? undefined
				: resolveDraftView( draftKey, id );
		},
		get cartItem() {
			const id = resolvePairingIdInContext( context, draftKey );
			return id === undefined
				? undefined
				: resolveCartItemForId( id, draftKey );
		},
	};
}

/**
 * Builds an explicitly-addressed envelope for `findItem`, shared by every
 * addressing rung: each rung resolves its own target id (deferred — a
 * key/filter rung's id may depend on the very cart line `cartItem` pairs
 * to) and its own cart-item candidate, and this function wires both into
 * the same lazy member shape `getItemInContext` exposes.
 *
 * @param args                       The resolved-per-rung inputs.
 * @param args.draftKey              The resolved draft collection key.
 * @param args.selectedAttributes    The attributes to narrow a variable
 *                                   product's variations by, if any.
 * @param args.resolveTargetId       Resolves the id this envelope's
 *                                   product/variation/baseProduct/draftItem
 *                                   members address.
 * @param args.resolveCartItemMember Resolves this envelope's `cartItem`
 *                                   member.
 * @return The resolved envelope.
 */
function buildFoundEnvelope( {
	draftKey,
	selectedAttributes,
	resolveTargetId,
	resolveCartItemMember,
}: {
	draftKey: DraftKey;
	selectedAttributes: SelectedAttributes[] | null | undefined;
	resolveTargetId: () => number | undefined;
	resolveCartItemMember: () => CartItem | OptimisticCartItem | undefined;
} ): Envelope {
	/**
	 * Resolves this envelope's `product` member via the Task 1 primitive —
	 * variation-by-id direct, a variable product's matched variation (or
	 * `null` on no match), or the product unchanged.
	 *
	 * @return The resolved product, or `null`.
	 */
	function resolveProductMember(): ProductResponseItem | null {
		const targetId = resolveTargetId();
		return targetId === undefined
			? null
			: resolveProduct( state.products.items, state.products.variations, {
					id: targetId,
					selectedAttributes,
			  } );
	}

	/**
	 * Resolves this envelope's `variation` member: the resolved product,
	 * when it is itself a variation (present in `state.products.variations`
	 * under its own id), else `null`.
	 *
	 * @return The resolved variation, or `null`.
	 */
	function resolveVariationMember(): ProductResponseItem | null {
		const product = resolveProductMember();
		return product && state.products.variations[ product.id ] === product
			? product
			: null;
	}

	/**
	 * Resolves this envelope's `baseProduct` member.
	 *
	 * @return The resolved base product, or `null`.
	 */
	function resolveBaseProductMember(): ProductResponseItem | null {
		const targetId = resolveTargetId();
		return targetId === undefined ? null : resolveBaseProduct( targetId );
	}

	return {
		get productId() {
			// Falls back to the resolved target id itself when no base
			// product data resolves — addressing still resolves even when
			// no product record backs it (mirroring `getItemInContext`'s
			// own `product?.id ?? productId` fallback).
			return resolveBaseProductMember()?.id ?? resolveTargetId() ?? null;
		},
		get variationId() {
			return resolveVariationMember()?.id ?? null;
		},
		get draftKey() {
			return draftKey;
		},
		get product() {
			return resolveProductMember();
		},
		get variation() {
			return resolveVariationMember();
		},
		get baseProduct() {
			return resolveBaseProductMember();
		},
		get draftItem() {
			const targetId = resolveTargetId();
			return targetId === undefined
				? undefined
				: resolveDraftView( draftKey, targetId );
		},
		get cartItem() {
			return resolveCartItemMember();
		},
	};
}

/**
 * Finds an item by id/key/filter, or by id narrowed to a variation via
 * `selectedAttributes`, returning the same lazy {@link Envelope}
 * `itemInContext` does.
 *
 * Implements the four addressing forms: an explicit `key` pairs exactly (no
 * further identity/extension checks — the caller already knows precisely
 * which line this is), using the matched line's own id for
 * `draftItem`/product resolution when no `id` is also given; `filter`
 * replaces id-based identity matching entirely, for extensions with their
 * own notion of line identity; otherwise product/variation identity via the
 * pairing ladder resolves `cartItem`.
 *
 * @param ref The addressing to resolve the envelope for.
 * @return The resolved envelope.
 */
function findItem( ref: FindItemRef = {} ): Envelope {
	const { id, selectedAttributes, key, filter } = ref;
	const draftKey = resolveDraftKey();
	const cartItems = () => state.cart?.items ?? [];

	if ( key !== undefined ) {
		const findByKey = () =>
			cartItems().find( ( item ) => item.key === key );
		return buildFoundEnvelope( {
			draftKey,
			selectedAttributes,
			resolveTargetId: () => id ?? findByKey()?.id,
			resolveCartItemMember: findByKey,
		} );
	}

	if ( filter ) {
		return buildFoundEnvelope( {
			draftKey,
			selectedAttributes,
			resolveTargetId: () => id,
			resolveCartItemMember: () => {
				const matches = cartItems().filter( filter );
				return matches.length === 1 ? matches[ 0 ] : undefined;
			},
		} );
	}

	return buildFoundEnvelope( {
		draftKey,
		selectedAttributes,
		resolveTargetId: () => id,
		resolveCartItemMember: () =>
			id === undefined ? undefined : resolveCartItemForId( id, draftKey ),
	} );
}

// Todo: export this store once the store is public.
store< WooCommerce >(
	'woocommerce',
	{
		state: {
			products: {
				items: {},
				variations: {},
			},
			draftItems: {},
			get itemInContext(): Envelope {
				return getItemInContext();
			},
			findItem,
		},
	},
	{ lock: universalLock }
);
