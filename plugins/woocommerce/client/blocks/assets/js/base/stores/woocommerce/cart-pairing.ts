/**
 * The cart-pairing ladder — shared matching primitives behind `findItem`'s
 * identity rung and the cart's own optimistic-lookup call sites.
 *
 * A folder-internal module (not a public export surface of either store):
 * it hosts {@link resolveBaseProduct}, {@link matchesSelectedAttributes},
 * {@link lineMatchesProduct}, {@link findCartLine}, and
 * {@link draftExtensionsMatchLine} — extracted, verbatim in behavior, out
 * of `cart.ts` so both the cart module and the root module (`index.ts`,
 * whose `findItem`/`itemInContext` pairing rung imports
 * {@link lineMatchesProduct} and {@link draftExtensionsMatchLine} directly)
 * can pair against them without either one value-importing the other.
 *
 * Reaches the shared `woocommerce` namespace's nested `products.items`/
 * `products.variations` maps by `store( 'woocommerce', {}, { lock:
 * universalLock } )`, read fresh on every call (see {@link
 * getProductsState} and `draft-internals.ts`'s own established precedent
 * for this folder's internal helper modules) rather than caching a
 * destructured reference at module scope, and rather than value-importing
 * `./index` — so this module never becomes a load-order dependency of the
 * root module and never value-imports `cart.ts` or `./index`.
 * `findCartLine` takes the candidate cart lines as an explicit `items`
 * argument instead of reading a namespace's `cart.items` internally, so
 * every caller (`cart.ts`'s `postDraftItems`/`addCartItemGenerator`, each
 * passing its own `state.cart.items`) owns that read and decides how to
 * guard it.
 */

/**
 * External dependencies
 */
import { store } from '@wordpress/interactivity';
import type {
	CartItem,
	CartVariationItem,
	ProductResponseItem,
} from '@woocommerce/types';
import fastDeepEqual from 'fast-deep-equal/es6';

/**
 * Internal dependencies
 */
import type { DraftItem, OptimisticCartItem, SelectedAttributes } from './cart';
import { attributeNamesMatch } from '../../utils/variations/attribute-matching';

// Stores are locked to prevent 3PD usage until the API is stable. The same
// literal every other store file in this folder uses, so this resolves the
// same lock-checked namespace `cart.ts` and `draft-internals.ts` do.
const universalLock =
	'I acknowledge that using a private store means my plugin will inevitably break on the next store release.';

/**
 * The nested product/variation maps this module reads off the shared
 * `woocommerce` namespace — the root module's own `state.products` shape
 * (`items` by product id, `variations` by variation id). Optional at every
 * level: on a page that never loads the root module, `store()` still
 * returns the empty stub every namespace starts as, and every reader below
 * degrades to a `null`/empty resolution rather than throwing.
 */
type ProductsState = {
	products?: {
		items?: Record< number, ProductResponseItem >;
		variations?: Record< number, ProductResponseItem >;
	};
};

/**
 * Returns the shared `woocommerce` namespace state, read fresh on every
 * call rather than cached at module scope — this module's `store()` call
 * always resolves the same shared namespace object `cart.ts` and
 * `draft-internals.ts` independently bind, so this never value-imports
 * either of them; reading fresh on every call (in place of a module-scope
 * destructure) mirrors `draft-internals.ts`'s own established precedent
 * for this folder's internal helper modules, and sidesteps any dependency
 * on this module's own load order relative to any store's registration.
 *
 * @return The shared `woocommerce` namespace's product/variation maps.
 */
function getProductsState(): ProductsState {
	return store< { state: ProductsState } >(
		'woocommerce',
		{},
		{ lock: universalLock }
	).state;
}

/**
 * Resolves the base (parent) product a given product/variation id belongs
 * to, consulting the shared `woocommerce` namespace's nested product maps
 * directly.
 *
 * Needed to resolve the `base` argument `effectiveVariationAttributes`
 * (see `draft-internals.ts`) takes at `findItem`'s pairing rung and
 * `postDraftItems`' per-item capture. `id` may already name the base
 * product, or one of its variations; either way the return is always the
 * top-level product entry, never a variation. Degrades to `null` when `id`
 * names a product the `woocommerce` namespace has no record of (a simple
 * product, or an id the store never loaded) — every caller then treats the
 * lookup as "no family data", per `effectiveVariationAttributes`'s own
 * degrade.
 *
 * @param id The product or variation id to resolve.
 * @return The base product, or `null` when it cannot be resolved.
 */
export function resolveBaseProduct( id: number ): ProductResponseItem | null {
	const productsState = getProductsState();
	const variation = productsState.products?.variations?.[ id ];
	if ( variation ) {
		return productsState.products?.items?.[ variation.parent ] ?? null;
	}
	return productsState.products?.items?.[ id ] ?? null;
}

/**
 * Returns `true` when a variation cart line's recorded attribute values
 * match a set of selected attributes.
 *
 * A module-private copy of the pairing algorithm in
 * `base/utils/variations/does-cart-item-match-attributes.ts`, duplicated
 * (rather than imported) so this ladder consults the shared `woocommerce`
 * namespace directly instead of through that util's import chain. The
 * standalone util is left in place, unchanged — it still backs the
 * shopper-lists blocks.
 *
 * Resolves each recorded attribute's term slug from the parent product's
 * attribute list (the shared `woocommerce` namespace's `products` state,
 * consulted one-directionally) to reconcile the Store API's label/slug
 * mismatches, then requires every recorded attribute to match a selected
 * one, case-insensitively and after normalizing WordPress's
 * `attribute_`/`attribute_pa_` prefixes.
 *
 * @param cartItem           The cart line to test.
 * @param selectedAttributes The attributes to match against.
 * @return `true` when every recorded attribute matches a selected one.
 */
export function matchesSelectedAttributes(
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

	const productsState = getProductsState();
	const parentProductId =
		productsState.products?.variations?.[ cartItem.id ]?.parent;
	const parentProduct =
		parentProductId !== undefined
			? productsState.products?.items?.[ parentProductId ]
			: undefined;
	const productAttributes = parentProduct?.attributes ?? [];

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
 * `variation.length` equality and {@link matchesSelectedAttributes}. The
 * matcher backing `findItem`'s identity rung and {@link findCartLine}.
 *
 * @param item      The cart line to test.
 * @param id        The product id to match against.
 * @param variation The variation attributes to match against, if any.
 * @return `true` when the line belongs to the specified product.
 */
export function lineMatchesProduct(
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
 * given, within the candidate lines the caller passes in.
 *
 * The matcher behind `addCartItem`/`updateItem`'s keyed-or-keyless lookup
 * and `addItem`'s per-draft lookup: an explicit `key` pairs exactly,
 * otherwise {@link lineMatchesProduct} resolves identity — which requires
 * `id`, so a call that gives neither `key` nor `id` degrades to "no match"
 * rather than throwing. Takes the candidate lines as an explicit `items`
 * argument rather than reading a namespace's `cart.items` internally, so the
 * caller owns that read — `cart.ts` passes its own `state.cart.items`; a
 * caller addressing a possibly-absent cart passes a guarded fallback. An
 * empty (or missing) `items` degrades to "no match" — `Array.prototype.find`
 * over `[]` never throws — never to an exception.
 *
 * @param items          The candidate cart lines to search.
 * @param args           Lookup arguments.
 * @param args.id        The product/variation id to match by identity when
 *                       no `key` is given; unused when `key` pairs exactly.
 * @param args.key       A known cart-line key; pairs exactly when given,
 *                       regardless of `id`/`variation`.
 * @param args.variation The variation attributes to match against, if any.
 * @return The matching cart line, or `undefined` when none matches.
 */
export function findCartLine(
	items: ( CartItem | OptimisticCartItem )[],
	{
		id,
		key,
		variation,
	}: {
		id?: number | undefined;
		key?: string | undefined;
		variation?: CartVariationItem[] | SelectedAttributes[] | undefined;
	}
): CartItem | OptimisticCartItem | undefined {
	if ( key ) {
		return items.find( ( cartItem ) => key === cartItem.key );
	}
	if ( id === undefined ) {
		return undefined;
	}
	return items.find( ( cartItem ) =>
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
 * Per `cart.ts`'s `DraftItem` type, extension props are payload-root keys
 * carrying a `/` (e.g. `'my-plugin/gift-note'`); `id`, `quantity`, and
 * `variation` never contain one, so a slash is what distinguishes an
 * extension prop from a core draft field.
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
export function draftExtensionsMatchLine(
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
