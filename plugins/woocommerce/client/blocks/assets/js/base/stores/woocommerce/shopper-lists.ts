/**
 * External dependencies
 */
import { store } from '@wordpress/interactivity';
import type { AsyncAction, TypeYield } from '@wordpress/interactivity';
import type { CurrencyResponse } from '@woocommerce/types';
import type { Store as StoreNotices } from '@woocommerce/stores/store-notices';
import {
	variationMatchesSelection,
	type SelectedAttributes,
	type VariationAttribute,
} from '@woocommerce/stores/woocommerce/products';

/**
 * Mirror of `Automattic\WooCommerce\StoreApi\Schemas\V1\ShopperListItemSchema::get_properties()`.
 *
 * Keep this in sync with the schema. State here must not include any UI-derived
 * fields — display values belong in block-private stores or PHP SSR.
 * TO DO: decide where UI-derived state lives
 */
export type ShopperListItemImage = {
	id: number;
	src: string;
	thumbnail: string;
	srcset: string;
	sizes: string;
	name: string;
	alt: string;
	thumbnail_srcset: string;
	thumbnail_sizes: string;
};

export type ShopperListItemVariation = {
	raw_attribute: string;
	attribute: string;
	value: string;
};

export type ShopperListItemPrices = CurrencyResponse & {
	price: string;
	regular_price: string;
	sale_price: string;
};

export type RawShopperListItem = {
	key: string;
	id: number;
	product_id: number;
	variation_id: number;
	quantity: number;
	is_live: boolean;
	is_purchasable: boolean;
	name: string;
	permalink: string | null;
	images: ShopperListItemImage[];
	variation: ShopperListItemVariation[];
	prices: ShopperListItemPrices | null;
	price_html: string;
	image_html: string;
	date_added_gmt: string;
};

export type ShopperListState = {
	items: RawShopperListItem[];
	isLoading: boolean;
};

export type AddItemPayload = {
	product_id?: number;
	cart_item_key?: string;
	variation?: Array< { attribute: string; value: string } >;
	quantity?: number;
};

export type Store = {
	state: {
		restUrl: string;
		// TODO: revisit nonce handling when we look at authentication for
		// the shopper-lists routes. Today PHP seeds this via
		// `wp_create_nonce( 'wc_store_api' )` and we refresh it from
		// response headers (see restRequest below). Likely changes once
		// the routes start enforcing nonces server-side: align with the
		// cart store's bootstrap-from-response-header pattern, share the
		// cart's `state.nonce` instead of duplicating, or move to a
		// caching-friendlier transport.
		nonce: string;
		lists: Record< string, ShopperListState >;
		/**
		 * Find the list row a selection resolves to, applying the SAME
		 * STRUCTURAL variation-matching semantics as the products store's
		 * `findProduct` (`variationMatchesSelection`): an "any"/absent
		 * variation attribute never constrains, extra selected attributes are
		 * ignored, attribute names compare normalized. This is the single,
		 * unified matcher — it replaces the wishlist block's old bespoke
		 * `matchVariationItem` (which additionally required exact-length
		 * attribute sets), so a row and a selection pair by one structural
		 * rule everywhere.
		 *
		 * VALUE normalization happens at THIS boundary (and only here): both
		 * sides' values are trimmed and lower-cased before the structural
		 * compare. The two datasets genuinely differ — list rows store term
		 * DISPLAY NAMES ("Red", via `get_term_by( 'slug' )->name`) while
		 * selections carry slugs ("red") — so an exact compare is right on
		 * the products-store path (slug-to-slug) and wrong here.
		 * `variationMatchesSelection` itself stays exact; see
		 * `normalizeListMatchValue` for the normalization contract and why it
		 * is safe for non-latin values.
		 *
		 * A product with no options passes an empty `variation`; every row of
		 * the matching id then satisfies the (vacuously true) match, so id
		 * alone identifies the row. Returns the first matching row, or `null`.
		 */
		findListItem: ( args: {
			slug: string;
			id: number;
			variation?: SelectedAttributes[];
		} ) => RawShopperListItem | null;
	};
	actions: {
		loadList: ( slug: string ) => Promise< void >;
		addItem: ( slug: string, payload: AddItemPayload ) => Promise< void >;
		removeItem: ( slug: string, key: string ) => Promise< void >;
		showNoticeError: ( error: Error ) => Promise< void >;
	};
};

// Stores are locked to prevent 3PD usage until the API is stable.
const universalLock =
	'I acknowledge that using a private store means my plugin will inevitably break on the next store release.';

const isShopperListItem = ( value: unknown ): value is RawShopperListItem =>
	!! value &&
	typeof value === 'object' &&
	typeof ( value as { key?: unknown } ).key === 'string';

const ensureListState = (
	state: Store[ 'state' ],
	slug: string
): ShopperListState => {
	let list = state.lists[ slug ];
	if ( ! list ) {
		list = { items: [], isLoading: false };
		state.lists[ slug ] = list;
	}
	return list;
};

/**
 * Send a Store API request following the cart store's auth shape:
 * Nonce header, `wc_store_api` action on the server side, cookie auth via
 * `credentials: 'include'`, and `cache: 'no-store'` so user-specific data is
 * never cached.
 *
 * The starter nonce is seeded by PHP via `wp_interactivity_state` and
 * refreshed from the `Nonce` response header on every subsequent request,
 * so the server-side enforcement (landing in a follow-up PR) can be
 * flipped on without rewriting the client.
 */
async function restRequest< T >(
	state: Store[ 'state' ],
	path: string,
	init: RequestInit = {}
): Promise< T | null > {
	const headers = new Headers( init.headers );
	headers.set( 'Content-Type', 'application/json' );
	if ( state.nonce ) {
		headers.set( 'Nonce', state.nonce );
	}

	const response = await fetch( `${ state.restUrl }${ path }`, {
		...init,
		headers,
		cache: 'no-store',
		credentials: 'include',
	} );

	const nextNonce = response.headers.get( 'Nonce' );
	if ( nextNonce ) {
		state.nonce = nextNonce;
	}

	if ( response.status === 204 ) {
		return null;
	}

	const text = await response.text();
	const contentType = response.headers.get( 'Content-Type' ) || '';
	const json =
		text && contentType.includes( 'json' ) ? JSON.parse( text ) : null;

	if ( ! response.ok ) {
		const message =
			( json && typeof json === 'object' && 'message' in json
				? String( ( json as { message: unknown } ).message )
				: '' ) ||
			response.statusText ||
			'Request failed.';
		throw new Error( message );
	}

	return json as T | null;
}

/**
 * Value normalization for LIST-boundary matching (see `findListItem`).
 *
 * List rows store attribute values as term DISPLAY NAMES (the Store API's
 * `format_variation_data` maps the stored slug through
 * `get_term_by( 'slug' )->name`), while selections carry the slug the shopper
 * picked. The structural matcher (`variationMatchesSelection`) compares values
 * exactly — correct on the products-store path, where both sides are slugs —
 * so the display-name-vs-slug drift is bridged HERE, by normalizing both sides
 * before the structural compare.
 *
 * Normalization = `trim()` + `toLowerCase()`:
 * - `toLowerCase()` uses the locale-independent Unicode default case mapping,
 *   so the result is deterministic across environments (no Turkish-i surprises
 *   from locale rules). Caseless scripts (CJK, Arabic, Hebrew, …) map to
 *   themselves, so for non-latin values normalization is a no-op and can never
 *   merge distinct values — it only bridges case-only differences, which is
 *   exactly the display-name-vs-slug drift ("Red" vs "red").
 * - No dash/space folding on VALUES (unlike attribute-NAME normalization):
 *   conflating "t-shirt" with "t shirt" could merge genuinely distinct terms.
 *   Slug shapes that differ from the display name beyond case (e.g.
 *   "bright-red" vs "Bright Red") remain out of scope, as with the previous
 *   matcher — bridging those needs a slug→name lookup via the parent product.
 *
 * @param value The raw attribute value (display name or slug).
 * @return The normalized value for list-boundary comparison.
 */
const normalizeListMatchValue = ( value: string ): string =>
	value.trim().toLowerCase();

// Do NOT supply `nonce` / `restUrl` defaults here. iAPI's deep-merge has the
// JS-supplied state win over the existing (PHP-seeded) state for primitives,
// so an empty-string default would clobber the values seeded server-side via
// `wp_interactivity_state`. State for those fields comes purely from PHP. Same
// reason the cart store doesn't ship state defaults — see cart.ts.
const { state, actions } = store< Store >(
	'woocommerce/shopper-lists',
	{
		state: {
			// Do NOT declare `nonce` / `restUrl` / `lists` here — those are
			// PHP-seeded primitives/objects and iAPI's deep-merge would let a
			// JS default clobber the server value (see the note below the store
			// definition). A method is safe to declare: it adds behavior without
			// shadowing seeded data.
			findListItem( {
				slug,
				id,
				variation = [],
			}: {
				slug: string;
				id: number;
				variation?: SelectedAttributes[];
			} ): RawShopperListItem | null {
				const list = state.lists?.[ slug ];
				if ( ! list ) {
					return null;
				}
				// LIST-BOUNDARY VALUE NORMALIZATION: rows carry term display
				// names, selections carry slugs; both sides are trimmed +
				// lower-cased here so the structural matcher (which stays
				// exact) can pair them. See `normalizeListMatchValue`.
				const normalizedSelection = variation.map(
					( { attribute, value } ) => ( {
						attribute,
						value: normalizeListMatchValue( value ),
					} )
				);
				return (
					list.items.find( ( item ) => {
						if ( item.id !== id ) {
							return false;
						}
						// Match the row's stored attributes against the
						// selection with the unified structural rules. A row's
						// stored `variation` is the display projection
						// ({ attribute, value }); map it to the { name, value }
						// shape `variationMatchesSelection` expects, normalizing
						// values on this side too. With an empty selection every
						// row of the id matches (vacuously), so no-options
						// products resolve by id alone.
						const variationAttributes: VariationAttribute[] = (
							item.variation ?? []
						).map( ( entry ) => ( {
							name: entry.attribute,
							value:
								typeof entry.value === 'string'
									? normalizeListMatchValue( entry.value )
									: entry.value,
						} ) );
						return variationMatchesSelection(
							variationAttributes,
							normalizedSelection
						);
					} ) ?? null
				);
			},
		},
		actions: {
			*loadList( slug: string ): AsyncAction< void > {
				const list = ensureListState( state, slug );
				list.isLoading = true;

				try {
					const response = ( yield restRequest<
						RawShopperListItem[]
					>(
						state,
						`wc/store/v1/shopper-lists/${ encodeURIComponent(
							slug
						) }/items`,
						{ method: 'GET' }
					) ) as TypeYield<
						typeof restRequest< RawShopperListItem[] >
					>;

					if ( ! Array.isArray( response ) ) {
						throw new Error( 'Invalid shopper list response.' );
					}

					const items = response.filter( isShopperListItem );

					// TODO: track in-flight mutation count and skip applying
					// load results when mutations are pending, so a slow
					// loadList cannot clobber a fresh add/remove.
					list.items = items;
				} catch ( error ) {
					// No user trigger to attach a banner to; log for ops.
					// eslint-disable-next-line no-console
					console.error( error );
				} finally {
					list.isLoading = false;
				}
			},

			*addItem(
				slug: string,
				payload: AddItemPayload
			): AsyncAction< void > {
				const list = ensureListState( state, slug );

				try {
					const item = ( yield restRequest< RawShopperListItem >(
						state,
						`wc/store/v1/shopper-lists/${ encodeURIComponent(
							slug
						) }/items`,
						{
							method: 'POST',
							body: JSON.stringify( payload ),
						}
					) ) as TypeYield<
						typeof restRequest< RawShopperListItem >
					>;

					if ( ! isShopperListItem( item ) ) {
						throw new Error(
							'Invalid shopper list item response.'
						);
					}

					// Merge the returned item by key — replace if present,
					// append otherwise. Re-saving the same product POSTs
					// twice and the server merges quantity, so we mirror
					// that behaviour locally.
					const existingIndex = list.items.findIndex(
						( i ) => i.key === item.key
					);
					if ( existingIndex >= 0 ) {
						list.items[ existingIndex ] = item;
					} else {
						list.items.push( item );
					}
				} catch ( error ) {
					actions.showNoticeError( error as Error );
				}
			},

			*removeItem( slug: string, key: string ): AsyncAction< void > {
				const list = state.lists[ slug ];
				if ( ! list ) {
					return;
				}

				if ( list.items.findIndex( ( i ) => i.key === key ) < 0 ) {
					return;
				}

				// Pessimistic remove: leave the row in place until the
				// server confirms, so failures don't flash. Buttons are
				// disabled meanwhile via the block's `pendingKeys`.
				try {
					yield restRequest(
						state,
						`wc/store/v1/shopper-lists/${ encodeURIComponent(
							slug
						) }/items/${ encodeURIComponent( key ) }`,
						{ method: 'DELETE' }
					);
				} catch ( error ) {
					actions.showNoticeError( error as Error );
					return;
				}

				// Re-find — the list may have mutated during the await.
				const removedIndex = list.items.findIndex(
					( i ) => i.key === key
				);
				if ( removedIndex >= 0 ) {
					list.items.splice( removedIndex, 1 );
				}
			},

			// Mirrors `cart.ts::showNoticeError`.
			*showNoticeError( error: Error ): AsyncAction< void > {
				yield import( '@woocommerce/stores/store-notices' );
				const { actions: noticeActions } = store< StoreNotices >(
					'woocommerce/store-notices',
					{},
					{ lock: universalLock }
				);

				noticeActions.addNotice( {
					notice: error.message,
					type: 'error',
					dismissible: true,
				} );

				// eslint-disable-next-line no-console
				console.error( error );
			},
		},
	},
	{ lock: universalLock }
);

// Listen for shopper-list item additions emitted from the wp.data side (e.g.
// the cart store's saveForLater thunk). Mirrors the cart's iAPI → wp.data
// sync direction, which also ships a payload (`from_iAPI` carries
// `quantityChanges`). The event carries the saved item directly so we can
// splice it in without an extra GET — keeps the merge ordering deterministic
// and avoids the loadList-vs-mutation race the iAPI store's loadList still
// has a TODO about.
//
// Keeps the discriminator + payload contract in sync with
// `assets/js/data/cart/thunks.ts::saveForLater`.
window.addEventListener( 'wc-blocks_store_sync_required', ( event: Event ) => {
	const detail = ( event as CustomEvent ).detail as
		| { type?: string; slug?: string; item?: RawShopperListItem }
		| undefined;
	if ( detail?.type !== 'shopper-list-item-added' ) {
		return;
	}
	if (
		typeof detail.slug !== 'string' ||
		detail.slug.trim().length === 0 ||
		! isShopperListItem( detail.item )
	) {
		return;
	}
	const list = ensureListState( state, detail.slug );
	const item = detail.item;
	const existingIndex = list.items.findIndex( ( i ) => i.key === item.key );
	if ( existingIndex >= 0 ) {
		list.items[ existingIndex ] = item;
	} else {
		list.items.push( item );
	}
} );
