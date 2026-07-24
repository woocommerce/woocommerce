/**
 * Draft internals — shared resolution and write plumbing for `woocommerce/cart`
 * and `woocommerce/products`.
 *
 * A folder-internal module (not a public export surface of either store): it
 * hosts draft-key/collection resolution, family-aware seed lookup,
 * family-resolution helpers, the setter's attribute-derivation helper, the
 * one draft write routine (materialize / merge / id-migration / invariant
 * warnings), the effective-payload helpers (effective attributes for
 * pairing; effective-seed resolution for posting), and the draft-view proxy
 * factory (with its per-`(key, id)` cache) both stores call.
 *
 * Reaches each store's state by namespace — `store( 'woocommerce/cart', {},
 * { lock: universalLock } )`, `store( 'woocommerce/products', {}, { lock:
 * universalLock } )` — rather than importing `./cart`/`./products` as
 * values, so this module never becomes a load-order dependency of either
 * store: `products.ts` importing this module (as a consumer will) never
 * drags `cart.ts`'s fetch/queue machinery onto a products-only page, and no
 * cycle exists in any load order. Every function below re-resolves state
 * fresh on each call rather than caching a destructured reference at module
 * scope: when this module's own top-level code runs before a store's
 * `store()` call that registers its real getters/actions, `store()` still
 * returns that same shared namespace object, but a value read out of it
 * before registration would freeze on the empty stub — reading inside a
 * function, on demand, always observes the fully-registered state.
 */

/**
 * External dependencies
 */
import { getContext, getServerState, store } from '@wordpress/interactivity';
import type { ProductResponseItem } from '@woocommerce/types';

/**
 * Internal dependencies
 */
import type {
	DraftItem,
	DraftKey,
	SelectedAttributes,
	Store as CartStore,
} from './cart';
import type { ProductsStore } from './products';

// Stores are locked to prevent 3PD usage until the API is stable. Both
// `store()` calls below use the same literal every other store file in this
// folder does, so they resolve the same lock-checked namespace.
const universalLock =
	'I acknowledge that using a private store means my plugin will inevitably break on the next store release.';

/**
 * The reserved key for the session-global draft collection: every purchase
 * surface wrapped in no container resolves this collection.
 *
 * No container ever declares this literal as its own key — containers
 * always mint one of their own — so it is the fixed fallback both
 * {@link resolveDraftKey} and the server's seed-filing PHP agree on.
 */
export const GLOBAL_DRAFT_KEY: DraftKey = 'woocommerce/global';

/**
 * The shape of the `woocommerce/cart` context namespace relevant to draft
 * collection resolution.
 *
 * A container block (a Product Collection loop item, a Single Product
 * block, or any other extension surface that wraps or repeats purchase UI)
 * declares its own server-minted `draftKey` here, isolating its subtree's
 * drafts into that key's collection; nested surfaces inherit the same bag
 * and resolve the same collection.
 */
type CartContext = { draftKey?: DraftKey };

/**
 * The `woocommerce/cart` server-rendered state shape relevant to seed
 * lookup: each purchase surface's initial-draft default, filed per
 * collection key and per product/variation id.
 *
 * Read only through `getServerState()` — the runtime's intact, per-page,
 * navigation-fresh copy — never through `state.draftSeeds`, which the
 * router's non-destructive client-side merge accumulates across
 * navigations and which this module never consults.
 */
type CartServerState = {
	draftSeeds?: Record< DraftKey, Record< DraftItem[ 'id' ], DraftItem > >;
};

/**
 * Returns the shared `woocommerce/cart` namespace state, read fresh on every
 * call (see the module doc comment for why this is never cached at module
 * scope).
 *
 * @return The `woocommerce/cart` store's shared state object.
 */
function getCartState(): CartStore[ 'state' ] {
	return store< CartStore >( 'woocommerce/cart', {}, { lock: universalLock } )
		.state;
}

/**
 * Returns the shared `woocommerce/products` namespace state, read fresh on
 * every call (see the module doc comment for why this is never cached at
 * module scope). On a products-only page that never loads `products.ts`,
 * `store()` still returns the empty stub every namespace starts as — every
 * consumer below degrades to an empty/undefined read rather than throwing.
 *
 * @return The `woocommerce/products` store's shared state object.
 */
function getProductsState(): ProductsStore[ 'state' ] {
	return store< ProductsStore >(
		'woocommerce/products',
		{},
		{ lock: universalLock }
	).state;
}

/**
 * Returns `true` when two attribute names refer to the same attribute.
 *
 * A module-local copy of the normalize-and-compare helper in
 * `base/utils/variations/attribute-matching.ts` (also duplicated, rather
 * than imported, by `woocommerce/products` itself), kept local so this
 * module's own value import list stays limited to `@wordpress/interactivity`.
 * Strips WordPress's `attribute_`/`attribute_pa_` prefixes and normalizes
 * hyphens/case so a Store API label (`"Color"`) matches a PHP context slug
 * (`"attribute_pa_color"`).
 *
 * @param a One attribute name (label or slug format).
 * @param b The other attribute name (label or slug format).
 * @return `true` when the names match after normalization.
 */
function attributeNamesMatch( a: string, b: string ): boolean {
	const normalize = ( name: string ) =>
		name
			.replace( /^attribute_(pa_)?/, '' )
			.replace( /-/g, ' ' )
			.toLowerCase();
	return normalize( a ) === normalize( b );
}

/**
 * Returns `true` for a value a shopper/caller actually specified — a
 * "concrete" value — and `false` for the falsy values (`null`, `''`,
 * `undefined`) this module canonicalizes as "unspecified" throughout the
 * family-resolution and effective-payload helpers (an "any" attribute's
 * meta value, or an omitted selection).
 *
 * @param value The value to test.
 * @return `true` when `value` is a non-empty string.
 */
function isConcreteValue( value: string | null | undefined ): value is string {
	return !! value;
}

/**
 * Resolves the draft key nearest to the calling surface: the current
 * context's own declared `draftKey`, when a container established one,
 * else {@link GLOBAL_DRAFT_KEY}.
 *
 * This is the single place that implements `context.draftKey ??
 * GLOBAL_DRAFT_KEY`; no other code, client or server, should re-implement
 * that fallback. `getContext()` throws when called with no directive
 * currently executing on the call stack (e.g. from code that runs outside
 * any directive-driven element); this resolver must never propagate that
 * failure — an out-of-directive call simply means "no declared key
 * available", so it degrades to `GLOBAL_DRAFT_KEY`, exactly as an
 * in-directive call whose context sets no `draftKey` would.
 *
 * @return The resolved draft key.
 */
export function resolveDraftKey(): DraftKey {
	try {
		return (
			getContext< CartContext >( 'woocommerce/cart' )?.draftKey ??
			GLOBAL_DRAFT_KEY
		);
	} catch {
		return GLOBAL_DRAFT_KEY;
	}
}

/**
 * Reads the draft collection filed under `key`, tolerating a
 * not-yet-created collection as empty rather than dereferencing it.
 *
 * Never creates a collection — only {@link writeDraft} (or the store's own
 * `upsertDraftItem`, while it still exists) does that, lazily, on its first
 * use of a key. A read against a key with no collection yet simply finds
 * nothing, indistinguishable from an existing-but-empty collection.
 *
 * @param key The resolved draft key.
 * @return The collection filed under `key`, or `undefined` when none has
 *         been created yet.
 */
export function resolveCollection( key: DraftKey ): DraftItem[] | undefined {
	return getCartState().draftItems[ key ];
}

/**
 * Finds a draft for the given product/variation id within a specific draft
 * collection.
 *
 * @param collection The draft collection to search.
 * @param id         The draft's product/variation id, or `undefined`
 *                   (nothing to find).
 * @return The matching draft, or `undefined` when none is found.
 */
export function findDraftInCollection(
	collection: DraftItem[],
	id: DraftItem[ 'id' ] | undefined
): DraftItem | undefined {
	if ( id === undefined ) {
		return undefined;
	}
	return collection.find( ( draft ) => draft.id === id );
}

/**
 * Reports a draft-write invariant violation.
 *
 * Per the write-policy design, invariant violations (a write that would
 * change an existing draft's `id`, a new draft missing a required field)
 * never throw and never partially apply — the calling routine returns
 * before touching the resolved draft collection. In a development build
 * this surfaces as a `console.warn` for the implementer; production builds
 * stay silent (`process.env.NODE_ENV` is inlined by the bundler, so this
 * check compiles away entirely there).
 *
 * @param message A human-readable description of the violated invariant.
 */
export function warnDraftInvariant( message: string ): void {
	if ( process.env.NODE_ENV !== 'production' ) {
		// eslint-disable-next-line no-console
		console.warn( `[woocommerce/cart] ${ message }` );
	}
}

/**
 * Reads a single collection/id's server-filed draft seed.
 *
 * Read only through `getServerState()` — the runtime's intact, per-page,
 * navigation-fresh copy, immune to the client merge that `state.draftSeeds`
 * would otherwise expose (see {@link CartServerState}).
 *
 * @param key The resolved draft key.
 * @param id  The product/variation id to look up.
 * @return The seed filed for `key`/`id`, or `undefined` when none was filed.
 */
export function getDraftSeed(
	key: DraftKey,
	id: DraftItem[ 'id' ]
): DraftItem | undefined {
	return getServerState< CartServerState >( 'woocommerce/cart' )
		?.draftSeeds?.[ key ]?.[ id ];
}

/**
 * Resolves the base (parent) product a given product/variation id belongs
 * to.
 *
 * `id` may already name the base product, or one of its variations — either
 * way, the return is always the top-level `woocommerce/products` product
 * entry, never a variation. Degrades to `null` when `id` names a product the
 * `woocommerce/products` store has no record of (e.g. `products.ts` never
 * loaded, or the id belongs to no known product) — a simple product, a
 * grouped child, or an extension surface with no loaded product data all
 * resolve `null` here, and every caller below degrades to non-family
 * behavior in that case.
 *
 * @param id The product or variation id to resolve.
 * @return The base product, or `null` when it cannot be resolved.
 */
function resolveBaseProduct(
	id: DraftItem[ 'id' ]
): ProductResponseItem | null {
	const productsState = getProductsState();
	const variation = productsState.productVariations?.[ id ];
	if ( variation ) {
		return productsState.products?.[ variation.parent ] ?? null;
	}
	return productsState.products?.[ id ] ?? null;
}

/**
 * Reads a seed for a variation-targeted materialization, falling back to
 * the parent-keyed seed when the variation itself was never seeded.
 *
 * Seeds are closed `{ id, quantity }` plus a conditional `variation` — no
 * filters — so a fallback to the parent's seed contributes only `quantity`;
 * it never invents a `variation` for the variation being materialized. This
 * is what turns a quantity-first edit on a default-attribute surface (whose
 * seed is filed only under the parent id) into a correctly-quantified
 * materialized draft at the resolved variation id.
 *
 * @param key      The resolved draft key.
 * @param id       The variation (or product) id to look up first.
 * @param parentId The base product id to fall back to when `id` carries no
 *                 seed of its own.
 * @return The seed filed for `id`, or the seed filed for `parentId`, or
 *         `undefined` when neither was filed.
 */
export function getFamilyDraftSeed(
	key: DraftKey,
	id: DraftItem[ 'id' ],
	parentId: DraftItem[ 'id' ]
): DraftItem | undefined {
	return getDraftSeed( key, id ) ?? getDraftSeed( key, parentId );
}

/**
 * Resolves the effective seed `addItem`'s no-payload path posts for an
 * untouched surface: the seed filed directly under `id`, or — when `id`
 * belongs to a recognized variable family and carries no seed of its own —
 * the family's parent-filed seed, re-addressed to `id`.
 *
 * For a parent id (`id` names the base product itself, unresolved) this is
 * an identity no-change: only the direct lookup ever applies, exactly
 * today's behavior. For a resolved variation id whose seed was filed only
 * under the parent (the common variable-surface case), this turns a v2-era
 * silent no-op into a minimal `{ ...parentSeed, id }` post the server fills
 * authoritatively. Non-family ids (grouped children, extension surfaces
 * with no product data) degrade to the direct lookup only.
 *
 * @param key The resolved draft key.
 * @param id  The in-context product/variation id to resolve the effective
 *            seed for.
 * @return The effective seed, or `undefined` when neither `id` nor its
 *         family's parent (when one resolves) carries a seed.
 */
export function resolveEffectiveSeed(
	key: DraftKey,
	id: DraftItem[ 'id' ]
): DraftItem | undefined {
	const direct = getDraftSeed( key, id );
	if ( direct ) {
		return direct;
	}

	const base = resolveBaseProduct( id );
	if ( ! base || base.id === id ) {
		return undefined;
	}

	const parentSeed = getDraftSeed( key, base.id );
	return parentSeed ? { ...parentSeed, id } : undefined;
}

/**
 * Finds the family draft in a raw collection: the draft whose `id` is the
 * base product's own id, or one of its variation ids.
 *
 * At most one family draft exists on the supported write path (see
 * {@link writeDraft}'s id-migration behavior), but a collection can carry
 * several when populated by raw, uninstrumented pushes (a documented
 * residual). When several match, the last one in collection order wins —
 * deterministic, unpromised.
 *
 * @param base       The family's base product.
 * @param collection The raw draft collection to search.
 * @return The last matching family draft in collection order, or
 *         `undefined` when none matches.
 */
export function findFamilyDraft(
	base: ProductResponseItem,
	collection: DraftItem[]
): DraftItem | undefined {
	const familyIds = new Set< number >( [
		base.id,
		...base.variations.map( ( variation ) => variation.id ),
	] );

	let match: DraftItem | undefined;
	for ( const draft of collection ) {
		if ( familyIds.has( draft.id ) ) {
			match = draft;
		}
	}
	return match;
}

/**
 * Resolves the live draft nearest to `id`: the exact-id draft when one
 * exists in the resolved collection, else the family draft (see
 * {@link findFamilyDraft}) when `id` belongs to a recognized product
 * family.
 *
 * Backs {@link writeDraft}'s merge-vs-materialize decision: a draft view
 * held across an id migration (a `variation` write re-filing the draft
 * under a different id) keeps addressing the same live draft because this
 * resolution is family-aware, not exact-id-only.
 *
 * @param key The resolved draft key.
 * @param id  The product/variation id to resolve the live draft for.
 * @return The live draft, or `undefined` when none exists yet.
 */
export function resolveLiveDraft(
	key: DraftKey,
	id: DraftItem[ 'id' ]
): DraftItem | undefined {
	const collection = resolveCollection( key ) ?? [];
	const exact = findDraftInCollection( collection, id );
	if ( exact ) {
		return exact;
	}
	const base = resolveBaseProduct( id );
	return base ? findFamilyDraft( base, collection ) : undefined;
}

/**
 * Matches a base product's variation against a set of selected attributes,
 * accepting only an actual variation — never the base product itself.
 *
 * `findProduct` falls back to returning the base product unchanged when the
 * product is not variable, or `selectedAttributes` is empty; that fallback
 * is never a valid family-resolution result here, so it is filtered out —
 * an unresolvable or empty selection yields `null`, never an invented
 * fallback to the parent.
 *
 * @param base  The family's base product.
 * @param attrs The selected attributes to match, if any.
 * @return The matched variation, or `null` when nothing matches.
 */
function matchFamilyVariation(
	base: ProductResponseItem,
	attrs: SelectedAttributes[] | undefined
): ProductResponseItem | null {
	if ( ! attrs || attrs.length === 0 ) {
		return null;
	}
	const result = getProductsState().findProduct( {
		id: base.id,
		selectedAttributes: attrs,
	} );
	return result && result.id !== base.id ? result : null;
}

/**
 * Maps a family draft to the variation it selects, in the resolution rungs
 * the design specifies:
 *
 * 1. A non-empty `variation` matches against the base product's variations
 *    (via `findProduct`); no match yields `null` — no invented fallback.
 * 2. No attributes, but `draft.id` already names a variation (**the
 *    id-direct rung** — load-bearing: it is how quantity-first and
 *    setter-selected surfaces resolve for display; it reads the full
 *    variation object for price/stock/image and never depends on
 *    `variation.attributes`, which the real serializer leaves empty): the
 *    populated `productVariations` entry, directly.
 * 3. `draft.id` names the base product itself, with empty/no attributes:
 *    `null` — an unconfigured family draft resolves to nothing.
 *
 * @param base  The family's base product.
 * @param draft The family draft to resolve, or `undefined` when none
 *              exists.
 * @return The resolved variation, or `null` when the draft resolves to
 *         nothing (or `draft` is `undefined`).
 */
export function resolveFamilyVariation(
	base: ProductResponseItem,
	draft: DraftItem | undefined
): ProductResponseItem | null {
	if ( ! draft ) {
		return null;
	}

	if ( draft.variation && draft.variation.length > 0 ) {
		return matchFamilyVariation( base, draft.variation );
	}

	if ( draft.id !== base.id ) {
		return getProductsState().productVariations?.[ draft.id ] ?? null;
	}

	return null;
}

/**
 * Derives a family variation's concrete `variation` attribute set for the
 * products-side setter, sourced from `base.variations[]`'s own entry for
 * `variationId` — **never** from the assigned variation object's own
 * `attributes`, which the real Store API serializer leaves empty.
 *
 * `base.variations[]` carries a concrete value for every attribute the
 * variation fixes, and a falsy value (`null`/`''`) for one it leaves "any"
 * — an "any" attribute's concrete value exists only in a shopper's (or a
 * prior caller's) selection, never on the variation data itself. For each
 * such attribute, this derives the concrete value from `existingDraft`'s
 * own `variation` (the family draft's current selection) when one is
 * recorded there, and **omits** the attribute entirely otherwise — never
 * inventing a value nobody ever specified. The resulting set can therefore
 * be incomplete; an incomplete set is the caller's (the setter's) signal to
 * file the write as a partial selection at the parent id.
 *
 * @param base          The family's base product, whose `variations[]`
 *                      backs this derivation.
 * @param variationId   The family variation id whose attributes to derive.
 * @param existingDraft The family's existing draft, whose `variation` backs
 *                      the "any"-attribute fallback, if one exists.
 * @return The variation's concrete attribute set — possibly incomplete when
 *         an "any" attribute has no derivable value; an empty array when
 *         `variationId` does not name one of `base`'s variations.
 */
export function deriveFamilyVariationAttributes(
	base: ProductResponseItem,
	variationId: number,
	existingDraft: DraftItem | undefined
): SelectedAttributes[] {
	const variationEntry = base.variations.find(
		( variation ) => variation.id === variationId
	);
	if ( ! variationEntry ) {
		return [];
	}

	const existingAttributes = existingDraft?.variation ?? [];

	return variationEntry.attributes.reduce< SelectedAttributes[] >(
		( derived, attr ) => {
			if ( isConcreteValue( attr.value ) ) {
				derived.push( { attribute: attr.name, value: attr.value } );
				return derived;
			}

			const existing = existingAttributes.find(
				( selected ) =>
					attributeNamesMatch( selected.attribute, attr.name ) &&
					isConcreteValue( selected.value )
			);
			if ( existing ) {
				derived.push( { attribute: attr.name, value: existing.value } );
			}
			return derived;
		},
		[]
	);
}

/**
 * Derives the *effective* variation attributes for a pairing comparison —
 * the pairing-side mirror of the Store API's `parse_variation_data` fill,
 * symmetric to {@link resolveFamilyVariation}: where that helper maps a
 * draft's specified selection to a variation for display, this one maps a
 * variation id back to the complete attribute set a comparison should use,
 * completing an incomplete specified selection from the variation's own
 * meta.
 *
 * For each entry of the family's `variations[]` map for `id`
 * (`{ name, value: slug | null }`), the effective value is the *specified*
 * value for that attribute (matched via `attributeNamesMatch`) when one was
 * genuinely given, else the meta's own concrete value. `null`/`''`/
 * `undefined` are canonicalized as "unspecified" on both sides (see
 * {@link isConcreteValue}) — this mirrors the server's own fill rule
 * exactly, and matches the `parse_variation_data` "specified beats meta,
 * meta beats nothing" precedence.
 *
 * Any entry left unvalued — an "any" attribute neither the caller specified
 * nor the variation's own meta fixes — makes the payload **incomplete**:
 * this returns `undefined`, the signal callers (`findItem`'s pairing rung)
 * treat as "pairs to nothing", mirroring the server's
 * `woocommerce_rest_missing_variation_data` 400. Never invents a value.
 *
 * A non-variation `id`, or absent family data (`base` not given, or naming
 * no product the store has loaded) degrades to exactly today's behavior:
 * `specified` passes through unchanged, untouched by this helper.
 *
 * Derived entries are `{ attribute, value }` — byte-identical in form to
 * UI-written selections — so they flow through the existing
 * `matchesSelectedAttributes` reconciliation with no predicate change.
 *
 * @param base      The family's base product, whose `variations[]` backs
 *                  the completion, or `null`/`undefined` when no product
 *                  data was loaded for this surface.
 * @param id        The variation id the comparison is against.
 * @param specified The specified selection to complete (a draft's or a
 *                  payload's own `variation`), or `undefined`.
 * @return The effective attribute set; `undefined` when the payload is
 *         incomplete (an unspecified "any" attribute); `specified`
 *         unchanged for a non-variation id or absent family data.
 */
export function effectiveVariationAttributes(
	base: ProductResponseItem | null | undefined,
	id: DraftItem[ 'id' ],
	specified: SelectedAttributes[] | undefined
): SelectedAttributes[] | undefined {
	const variationEntry = base?.variations.find(
		( variation ) => variation.id === id
	);
	if ( ! variationEntry ) {
		return specified;
	}

	const effective: SelectedAttributes[] = [];
	for ( const attr of variationEntry.attributes ) {
		const specifiedMatch = specified?.find( ( selected ) =>
			attributeNamesMatch( selected.attribute, attr.name )
		);
		const specifiedValue = specifiedMatch?.value;
		const value = isConcreteValue( specifiedValue )
			? specifiedValue
			: attr.value;

		if ( ! isConcreteValue( value ) ) {
			// An unspecified "any" attribute: the payload is incomplete.
			return undefined;
		}
		effective.push( { attribute: attr.name, value } );
	}
	return effective;
}

/**
 * Resolves the id a `variation` write should file its draft under: the
 * matched variation's id, or the base product's own id when nothing
 * matches.
 *
 * @param base  The family's base product.
 * @param attrs The attributes being written.
 * @return The variation id to file under, or `base.id` when `attrs`
 *         resolves to no variation.
 */
function resolveVariationWriteId(
	base: ProductResponseItem,
	attrs: SelectedAttributes[]
): number {
	const matched = matchFamilyVariation( base, attrs );
	return matched ? matched.id : base.id;
}

/**
 * Writes a single property to the draft nearest `id`, materializing a new
 * draft from the surface's seed when none exists yet.
 *
 * The one place that ever writes `state.draftItems` on the supported write
 * path — the draft view's set trap and the products store's
 * `productVariationInContext` setter both forward every write here.
 *
 * Resolution: {@link resolveLiveDraft} finds the live draft nearest `id` —
 * the exact-id draft first, then the family draft. When one exists, the
 * write forwards onto it (a merge, mutating the live draft in place — no
 * new array entry). Otherwise the write **materializes** a new draft,
 * composed as `{ ...seed, [prop]: value, id }` — the seed resolved
 * family-aware ({@link getFamilyDraftSeed}) whenever `id` belongs to a
 * recognized product family, so a quantity-first write that targets an
 * already-resolved variation id still finds the parent-filed seed's
 * `quantity` — assigned into the collection via the atomic-assignment form
 * (`state.draftItems[key] = [ ...existing, draft ]`, never `.push` on a
 * read-back array) — the assignment itself is what notifies a binding
 * reading a not-yet-existing collection.
 *
 * A `variation` write additionally re-resolves the family id
 * ({@link resolveVariationWriteId}) and re-files the *same* draft under the
 * resolved id in the same atomic step — on the merge path by mutating the
 * live draft's own `id`; on the materialize path by composing the new
 * draft's `id` directly — so `quantity` and extension props ride along
 * unchanged and at most one family draft results. When `id` belongs to no
 * recognized family, a `variation` write degrades to writing `variation` at
 * `id` unchanged — no migration is attempted with nothing to migrate
 * against.
 *
 * Invariant warnings (dev-warn + degrade, silent in production; see
 * {@link warnDraftInvariant}): writing `id` directly is always rejected
 * (warn + no-op) — a draft's identity is store-managed, following its
 * `variation`, never a direct write. A materializing write whose composed
 * draft lacks a numeric `quantity` still materializes, with a warning — the
 * relaxed invariant; a quantity-less draft is a valid `cart/add-item`
 * payload, and the server defaults it to the product minimum.
 *
 * @param id    The product/variation id nearest the write — the id a
 *              consumer resolved a draft view for, or the setter's
 *              in-context id.
 * @param prop  The draft property being written (`'quantity'`,
 *              `'variation'`, or a namespaced extension prop).
 * @param value The value to write.
 */
export function writeDraft(
	id: DraftItem[ 'id' ],
	prop: string,
	value: unknown
): void {
	if ( prop === 'id' ) {
		warnDraftInvariant(
			'cannot write "id" directly; a draft\'s id is store-managed and follows its "variation", not a direct write.'
		);
		return;
	}

	const key = resolveDraftKey();
	const collection = resolveCollection( key ) ?? [];
	const base = resolveBaseProduct( id );
	const existing = resolveLiveDraft( key, id );

	if ( existing ) {
		if ( prop === 'variation' ) {
			existing.variation = value as SelectedAttributes[];
			if ( base ) {
				existing.id = resolveVariationWriteId(
					base,
					value as SelectedAttributes[]
				);
			}
			return;
		}
		existing[ prop ] = value;
		return;
	}

	let targetId = id;
	if ( prop === 'variation' && base ) {
		targetId = resolveVariationWriteId(
			base,
			value as SelectedAttributes[]
		);
	}

	const seed = base
		? getFamilyDraftSeed( key, targetId, base.id )
		: getDraftSeed( key, targetId );

	const draft = {
		...seed,
		[ prop ]: value,
		id: targetId,
	} as DraftItem;

	if ( typeof draft.quantity !== 'number' ) {
		warnDraftInvariant(
			`materializing a draft for id ${ targetId } with no numeric "quantity"; materializing anyway.`
		);
	}

	getCartState().draftItems[ key ] = [ ...collection, draft ];
}

/**
 * Resolves the underlying plain object a draft view's reads answer from: the
 * live draft nearest `id` (see {@link resolveLiveDraft}) when one exists,
 * else the family-aware seed view (`draftSeeds[key][id] ?? draftSeeds[key][
 * parentId]`, fresh via `getServerState()` — see {@link getFamilyDraftSeed}),
 * or the direct seed lookup for a non-family id.
 *
 * Backs both the view's enumeration (`ownKeys`/`getOwnPropertyDescriptor`,
 * so `Object.keys()` sees exactly the source's own keys — never inventing an
 * enumerable `variation` the source does not itself carry, which would leak
 * into a raw draft posted verbatim) and {@link readDraftViewProp}'s general
 * (non-`id`, non-`variation`) property reads.
 *
 * @param key The resolved draft key the view was created for.
 * @param id  The product/variation id the view was created for.
 * @return The live draft, the seed, or `undefined` when neither exists.
 */
function resolveDraftViewSource(
	key: DraftKey,
	id: DraftItem[ 'id' ]
): DraftItem | undefined {
	const live = resolveLiveDraft( key, id );
	if ( live ) {
		return live;
	}
	const base = resolveBaseProduct( id );
	return base
		? getFamilyDraftSeed( key, id, base.id )
		: getDraftSeed( key, id );
}

/**
 * Resolves the value a draft view should answer for a single property read,
 * without materializing anything.
 *
 * `id` is special-cased: it always answers the *addressed* id, never a
 * family-fallback seed's own `id` field (a seed filed under a variable
 * family's parent carries the parent's id, not the variation id the view
 * was addressed for) — except when a live family draft exists, whose own
 * (possibly since-migrated) `id` is the answer, per the general "live draft
 * wins" rule. `variation` is also special-cased: it always answers a real
 * array, `[]` rather than `undefined` when neither the live draft nor the
 * seed specifies one (see the `DraftItem.variation` contract). Every other
 * property answers the live draft's value when one exists, else the seed's.
 *
 * @param key  The resolved draft key the view was created for.
 * @param id   The product/variation id the view was created for.
 * @param prop The property being read.
 * @return The value the view should answer for `prop`.
 */
function readDraftViewProp(
	key: DraftKey,
	id: DraftItem[ 'id' ],
	prop: string
): unknown {
	if ( prop === 'id' ) {
		return resolveLiveDraft( key, id )?.id ?? id;
	}
	const source = resolveDraftViewSource( key, id );
	if ( prop === 'variation' ) {
		return source?.variation ?? [];
	}
	return source?.[ prop ];
}

/**
 * Builds the draft view `Proxy` for a single `(key, id)` pair.
 *
 * The view's target is an empty, extensible plain object — every trap below
 * computes its answer independently of the target's own (nonexistent)
 * properties, so the target itself never accumulates state; the `Proxy`
 * exotic object is what callers hold and read/write through.
 *
 * - `get`/`has`/`ownKeys`/`getOwnPropertyDescriptor` are pure reads (see
 *   {@link readDraftViewProp}/{@link resolveDraftViewSource}): they never
 *   materialize a draft, and they subscribe to whatever reactive state they
 *   read the same way any other getter-driven read does.
 * - `set` forwards every write to {@link writeDraft} — the module's single
 *   write routine — passing `id` (fixed at creation) and the written
 *   `prop`/`value` through unchanged; `writeDraft` itself decides
 *   merge-vs-materialize and handles a `variation` write's id migration and
 *   a rejected `id` write.
 * - `deleteProperty` forwards to the live draft, when one exists (nothing
 *   to delete from a not-yet-materialized seed view).
 *
 * @param key The resolved draft key this view is scoped to.
 * @param id  The product/variation id this view is scoped to.
 * @return The draft view `Proxy`.
 */
function createDraftView( key: DraftKey, id: DraftItem[ 'id' ] ): DraftItem {
	return new Proxy( {} as DraftItem, {
		get( _target, prop ) {
			return typeof prop === 'string'
				? readDraftViewProp( key, id, prop )
				: undefined;
		},
		set( _target, prop, value ) {
			if ( typeof prop !== 'string' ) {
				return false;
			}
			writeDraft( id, prop, value );
			return true;
		},
		has( _target, prop ) {
			if ( typeof prop !== 'string' ) {
				return false;
			}
			if ( prop === 'id' || prop === 'variation' ) {
				return true;
			}
			return Object.prototype.hasOwnProperty.call(
				resolveDraftViewSource( key, id ) ?? {},
				prop
			);
		},
		ownKeys() {
			return Object.keys( resolveDraftViewSource( key, id ) ?? {} );
		},
		getOwnPropertyDescriptor( _target, prop ) {
			if (
				typeof prop !== 'string' ||
				! Object.prototype.hasOwnProperty.call(
					resolveDraftViewSource( key, id ) ?? {},
					prop
				)
			) {
				return undefined;
			}
			return {
				value: readDraftViewProp( key, id, prop ),
				writable: true,
				enumerable: true,
				configurable: true,
			};
		},
		deleteProperty( _target, prop ) {
			if ( typeof prop === 'string' ) {
				const live = resolveLiveDraft( key, id );
				if ( live ) {
					delete live[ prop ];
				}
			}
			return true;
		},
	} );
}

/**
 * The module-private per-`(key, id)` draft-view cache: a `DraftKey`-keyed
 * map of id-keyed maps, each holding the single `Proxy` instance already
 * built for that pair. Ensures repeated resolution of the same `(key, id)` —
 * across separate `findItem`/`itemInContext` calls, or across an id
 * migration a held view keeps addressing — returns the identical view
 * object rather than a fresh one each time.
 */
const draftViewCache = new Map<
	DraftKey,
	Map< DraftItem[ 'id' ], DraftItem >
>();

/**
 * Resolves the draft view for `(key, id)` — the live, family-aware `Proxy`
 * that `findItem`/`itemInContext` expose as `Envelope.draft` whenever an id
 * resolves. Reads answer the live draft's values, or the surface's seed
 * values pre-materialization, and never materialize anything; writes
 * forward to {@link writeDraft}. See {@link createDraftView} for the full
 * trap behavior.
 *
 * Cached per `(key, id)` in {@link draftViewCache}: a second resolution of
 * the same pair returns the exact same `Proxy` instance, so a view held
 * across a `variation` write's id migration keeps addressing the
 * now-migrated draft (family-aware resolution, not exact-id-only) rather
 * than going stale.
 *
 * @param key The resolved draft key to scope the view to.
 * @param id  The product/variation id to scope the view to.
 * @return The draft view for `(key, id)`.
 */
export function resolveDraftView(
	key: DraftKey,
	id: DraftItem[ 'id' ]
): DraftItem {
	let byId = draftViewCache.get( key );
	if ( ! byId ) {
		byId = new Map();
		draftViewCache.set( key, byId );
	}

	let view = byId.get( id );
	if ( ! view ) {
		view = createDraftView( key, id );
		byId.set( id, view );
	}
	return view;
}
